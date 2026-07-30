<?php

namespace App\Http\Controllers;

use App\Services\LeaveService;
use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProcessAttendanceController extends Controller
{
    use ApiResponse, GeneratesPdf;

    /** Bump when changing async/inline worker behavior (verify Docker rebuild picked up code). */
    private const ATTENDANCE_WORKER_BUILD = '2026-06-01-v6';

    private function isProcessRunAlreadyCompleted(?string $processRunId): bool
    {
        if (empty($processRunId)) {
            return false;
        }
        $status = DB::table('attendance_process_runs')
            ->where('id', $processRunId)
            ->value('status');

        return strtolower((string) $status) === 'completed';
    }

    /**
     * Employment types linked to this payroll period via payroll_period_Etype.
     */
    private function getPayrollPeriodEmploymentTypeIds(int $payrollPeriodId): \Illuminate\Support\Collection
    {
        return DB::table('payroll_period_Etype')
            ->where('payrollperiod_id', $payrollPeriodId)
            ->pluck('employmenttype_id')
            ->filter(fn($id) => $id !== null)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Restrict an employees query to payroll-period employment types.
     */
    private function applyPayrollPeriodEmploymentTypeScope($query, \Illuminate\Support\Collection $allowedEmploymentTypeIds, string $column = 'employment_type_id')
    {
        if ($allowedEmploymentTypeIds->isNotEmpty()) {
            $query->whereIn($column, $allowedEmploymentTypeIds->all());
        }
        return $query;
    }

    private function validatePayrollPeriodEmploymentTypes(int $payrollPeriodId, \Illuminate\Support\Collection $allowedEmploymentTypeIds): ?array
    {
        if ($allowedEmploymentTypeIds->isEmpty()) {
            return [
                'success' => false,
                'message' => "No employment type is configured for payroll period ID {$payrollPeriodId}. Configure payroll_period_Etype before processing attendance.",
                'data' => null,
            ];
        }

        return null;
    }

    /**
     * Track user id for async workers via payload / Sanctum request only (no SessionGuard).
     */
    private function bindProcessAttendanceWorkerUser(array $payload): void
    {
        $userId = $payload['user_id'] ?? null;
        if (!$userId) {
            $requestUser = request()->user();
            if ($requestUser) {
                $userId = $requestUser->getAuthIdentifier();
            }
        }
        if ($userId) {
            app()->instance('process_attendance.worker_user_id', (int) $userId);
        }
    }

    private function resolveProcessAttendanceUserId(): ?int
    {
        if (app()->bound('process_attendance.worker_user_id')) {
            return (int) app('process_attendance.worker_user_id');
        }
        $requestUser = request()->user();
        if ($requestUser) {
            return (int) $requestUser->getAuthIdentifier();
        }
        return null;
    }

    /** Sanctum/API user id for encoder fields; fallback 1 when worker has no user context. */
    private function resolveProcessAttendanceEncoderId(): int
    {
        return $this->resolveProcessAttendanceUserId() ?? 1;
    }

    /**
     * Connection for attendance_process_runs progress reads/writes.
     * Uses a separate PDO link so updates commit outside the main process transaction.
     */
    private function attendanceProcessRunsProgressConnection(): \Illuminate\Database\Connection
    {
        $override = config('database.attendance_process_progress_connection');
        if (!empty($override)) {
            return DB::connection($override);
        }

        return match (config('database.default')) {
            'mysql' => DB::connection('attendance_process_progress_mysql'),
            'sqlsrv' => DB::connection('attendance_process_progress'),
            // Other drivers: use default (same as worker). For live progress during a long
            // transaction, set ATTENDANCE_PROGRESS_DB_CONNECTION to a duplicate of default.
            default => DB::connection(config('database.default')),
        };
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // API controller: avoid web session auth lock contention on concurrent
        // long-running process + polling requests.
        $this->middleware('auth:sanctum');
    }

    /**
     * Docker/php-fpm often cannot spawn a reliable background `php artisan` from a web request
     * (exec returns a PID but the child never runs, or dies before writing progress).
     * In that case run the worker inline after the HTTP response is flushed.
     */
    private function shouldUseInlineAttendanceWorker(): bool
    {
        if (filter_var(env('PROCESS_ATTENDANCE_INLINE_WORKER', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        if (
            file_exists('/.dockerenv')
            && !filter_var(env('PROCESS_ATTENDANCE_FORCE_DETACHED_WORKER', false), FILTER_VALIDATE_BOOLEAN)
        ) {
            return true;
        }

        return false;
    }

    private function scheduleInlineAttendanceWorker(array $payload): void
    {
        $processRunId = $payload['process_run_id'] ?? null;

        register_shutdown_function(function () use ($processRunId, $payload): void {
            try {
                ignore_user_abort(true);
                set_time_limit(3600);

                if (function_exists('fastcgi_finish_request')) {
                    @fastcgi_finish_request();
                }

                Log::info('[ProcessAttendance][Async] Inline worker starting', [
                    'process_run_id' => $processRunId,
                    'payroll_period_id' => $payload['payroll_period_id'] ?? null,
                ]);

                $this->runAttendanceProcessWorker($payload);

                Log::info('[ProcessAttendance][Async] Inline worker finished', [
                    'process_run_id' => $processRunId,
                ]);
            } catch (\Throwable $e) {
                if ($this->isProcessRunAlreadyCompleted($processRunId)) {
                    Log::warning('[ProcessAttendance][Async] Inline worker post-cleanup error ignored (run already completed)', [
                        'process_run_id' => $processRunId,
                        'worker_build' => self::ATTENDANCE_WORKER_BUILD,
                        'error' => $e->getMessage(),
                    ]);
                    return;
                }
                Log::error('[ProcessAttendance][Async] Inline worker failed', [
                    'process_run_id' => $processRunId,
                    'error' => $e->getMessage(),
                ]);
                if (!empty($processRunId)) {
                    $this->setAttendanceProcessRunStatus($processRunId, 'failed');
                    $this->updateAttendanceProcessRunProgress(
                        $processRunId,
                        'failed',
                        null,
                        null,
                        null,
                        null,
                        null,
                        'Inline worker failed (' . self::ATTENDANCE_WORKER_BUILD . '): ' . mb_substr($e->getMessage(), 0, 400)
                    );
                }
            }
        });
    }

    /**
     * Execute attendance processing worker logic (shared by inline + artisan worker).
     */
    public function runAttendanceProcessWorker(array $payload): void
    {
        $this->bindProcessAttendanceWorkerUser($payload);

        Log::info('[ProcessAttendance][Async] Worker running', [
            'worker_build' => self::ATTENDANCE_WORKER_BUILD,
            'process_run_id' => $payload['process_run_id'] ?? null,
            'user_id' => $this->resolveProcessAttendanceUserId(),
        ]);

        $payload['run_async_worker'] = true;
        $task = (string) ($payload['task'] ?? 'process');
        if ($task === 'reprocess_all') {
            $request = Request::create('/api/process-attendance/reprocess-all', 'POST', $payload);
            $this->reprocessAll($request);
            return;
        }

        $request = Request::create('/api/process-attendance', 'POST', $payload);
        $this->process($request);
    }

    /**
     * Spawn a detached artisan command so heavy attendance processing runs in a
     * separate PHP process (works with php artisan serve in dev and normal prod stacks).
     */
    private function launchDetachedAttendanceProcess(array $payload): void
    {
        if ($this->shouldUseInlineAttendanceWorker()) {
            Log::info('[ProcessAttendance][Async] Using inline worker (Docker / PROCESS_ATTENDANCE_INLINE_WORKER)', [
                'process_run_id' => $payload['process_run_id'] ?? null,
                'payroll_period_id' => $payload['payroll_period_id'] ?? null,
            ]);
            $this->scheduleInlineAttendanceWorker($payload);
            return;
        }

        $encoded = base64_encode(json_encode($payload));
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $command = sprintf(
            '%s %s attendance:run-process --payload=%s',
            escapeshellarg($php),
            escapeshellarg($artisan),
            escapeshellarg($encoded)
        );
        Log::info('[ProcessAttendance][Async] Launching worker', [
            'process_run_id' => $payload['process_run_id'] ?? null,
            'payroll_period_id' => $payload['payroll_period_id'] ?? null,
        ]);

        // Some hardened PHP runtimes disable process functions. If so, fail fast so the UI
        // doesn't poll forever showing a "stuck" progress percentage.
        $processRunId = $payload['process_run_id'] ?? null;

        // Windows
        if (DIRECTORY_SEPARATOR === '\\') {
            if (!function_exists('popen') || !function_exists('pclose')) {
                Log::error('[ProcessAttendance][Async] popen/pclose disabled; cannot spawn worker', [
                    'process_run_id' => $processRunId,
                ]);
                $this->setAttendanceProcessRunStatus($processRunId, 'failed');
                $this->updateAttendanceProcessRunProgress($processRunId, 'failed', null, null, null, null, null, 'Async worker could not start (popen/pclose disabled).');
                return;
            }
            pclose(popen('start /B "" ' . $command . ' > NUL 2>&1', 'r'));
            return;
        }

        // Linux/macOS
        if (!function_exists('exec')) {
            Log::error('[ProcessAttendance][Async] exec disabled; cannot spawn worker', [
                'process_run_id' => $processRunId,
            ]);
            $this->setAttendanceProcessRunStatus($processRunId, 'failed');
            $this->updateAttendanceProcessRunProgress($processRunId, 'failed', null, null, null, null, null, 'Async worker could not start (exec disabled).');
            return;
        }

        // Use nohup to detach reliably in containerized runtimes, and capture PID.
        // If we can't spawn (missing nohup, permission, etc.), fail fast so UI won't poll forever.
        $workerLog = storage_path('logs/attendance-async-worker.log');
        $spawn = 'nohup ' . $command . ' >> ' . escapeshellarg($workerLog) . ' 2>&1 & echo $!';
        $out = [];
        $code = 0;
        exec($spawn, $out, $code);
        $pid = trim((string) (is_array($out) ? ($out[count($out) - 1] ?? '') : ''));

        if ($code !== 0 || $pid === '' || !ctype_digit($pid)) {
            Log::error('[ProcessAttendance][Async] Worker spawn failed', [
                'process_run_id' => $processRunId,
                'exit_code' => $code,
                'pid' => $pid,
                'output' => $out,
            ]);
            $this->setAttendanceProcessRunStatus($processRunId, 'failed');
            $this->updateAttendanceProcessRunProgress(
                $processRunId,
                'failed',
                null,
                null,
                null,
                null,
                null,
                'Async worker could not start (spawn failed). Check container PHP disable_functions / permissions.'
            );
            return;
        }
    }

    private function upsertAttendanceProcessRun(?string $processRunId, ?int $payrollPeriodId = null, ?int $employeeId = null): void
    {
        if (empty($processRunId)) {
            return;
        }

        $existingStatus = DB::table('attendance_process_runs')
            ->where('id', $processRunId)
            ->value('status');

        DB::table('attendance_process_runs')->updateOrInsert(
            ['id' => $processRunId],
            [
                'user_id' => $this->resolveProcessAttendanceUserId(),
                'payroll_period_id' => $payrollPeriodId,
                'employee_id' => $employeeId,
                'status' => 'running',
                'started_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function setAttendanceProcessRunStatus(?string $processRunId, string $status): void
    {
        if (empty($processRunId)) {
            return;
        }

        $update = [
            'status' => $status,
            'updated_at' => now(),
        ];

        if ($status === 'completed') {
            $update['completed_at'] = now();
        }

        DB::table('attendance_process_runs')
            ->where('id', $processRunId)
            ->update($update);
    }

    private function updateAttendanceProcessRunProgress(
        ?string $processRunId,
        ?string $phase = null,
        ?string $currentDate = null,
        ?int $currentDateIndex = null,
        ?int $totalDates = null,
        ?int $currentEmployeeIndex = null,
        ?int $totalEmployees = null,
        ?string $message = null
    ): void {
        if (empty($processRunId)) {
            return;
        }

        $payload = ['updated_at' => now()];
        if ($phase !== null) {
            $payload['phase'] = $phase;
        }
        if ($currentDate !== null) {
            $payload['current_date'] = $currentDate;
        }
        if ($currentDateIndex !== null) {
            $payload['current_date_index'] = $currentDateIndex;
        }
        if ($totalDates !== null) {
            $payload['total_dates'] = $totalDates;
        }
        if ($currentEmployeeIndex !== null) {
            $payload['current_employee_index'] = $currentEmployeeIndex;
        }
        if ($totalEmployees !== null) {
            $payload['total_employees'] = $totalEmployees;
        }
        if ($message !== null) {
            $payload['progress_message'] = $message;
        }

        $this->attendanceProcessRunsProgressConnection()
            ->table('attendance_process_runs')
            ->where('id', $processRunId)
            ->update($payload);
    }

    public function getLatestProcessProgress(Request $request)
    {
        $payrollPeriodId = $request->query('payroll_period_id');
        $requestedRunId = trim((string) ($request->query('process_run_id') ?? ''));
        $conn = $this->attendanceProcessRunsProgressConnection();
        $driver = (string) $conn->getDriverName();

        if ($driver === 'sqlsrv') {
            $conn->statement('SET LOCK_TIMEOUT 1000');

            if ($requestedRunId !== '') {
                // Primary reference: specific run id from attendance_process_runs.
                $sql = "SELECT TOP 1 * FROM attendance_process_runs WITH (NOLOCK) WHERE id = ?";
                $bindings = [$requestedRunId];
                $rows = $conn->select($sql, $bindings);
                $candidate = $rows[0] ?? null;
                if ($candidate) {
                    return $this->successResponse([
                        'id' => $candidate->id,
                        'status' => $candidate->status,
                        'phase' => $candidate->phase ?? null,
                        'current_date' => $candidate->current_date ?? null,
                        'current_date_index' => $candidate->current_date_index ?? null,
                        'total_dates' => $candidate->total_dates ?? null,
                        'current_employee_index' => $candidate->current_employee_index ?? null,
                        'total_employees' => $candidate->total_employees ?? null,
                        'progress_message' => $candidate->progress_message ?? null,
                        'started_at' => $candidate->started_at ?? null,
                        'updated_at' => $candidate->updated_at ?? null,
                    ]);
                }
            }

            $sql = "SELECT TOP 1 * FROM attendance_process_runs WITH (NOLOCK) WHERE 1=1";
            $bindings = [];
            if (!empty($payrollPeriodId)) {
                $sql .= " AND payroll_period_id = ?";
                $bindings[] = (int) $payrollPeriodId;
            }
            $sql .= " ORDER BY CASE WHEN status = 'running' THEN 0 WHEN status = 'completed' THEN 1 ELSE 2 END, updated_at DESC";
            $rows = $conn->select($sql, $bindings);
            $run = $rows[0] ?? null;
        } else {
            if ($requestedRunId !== '') {
                $candidate = $conn->table('attendance_process_runs')
                    ->where('id', $requestedRunId)
                    ->first();
                if ($candidate) {
                    return $this->successResponse([
                        'id' => $candidate->id,
                        'status' => $candidate->status,
                        'phase' => $candidate->phase ?? null,
                        'current_date' => $candidate->current_date ?? null,
                        'current_date_index' => $candidate->current_date_index ?? null,
                        'total_dates' => $candidate->total_dates ?? null,
                        'current_employee_index' => $candidate->current_employee_index ?? null,
                        'total_employees' => $candidate->total_employees ?? null,
                        'progress_message' => $candidate->progress_message ?? null,
                        'started_at' => $candidate->started_at ?? null,
                        'updated_at' => $candidate->updated_at ?? null,
                    ]);
                }
            }

            $query = $conn->table('attendance_process_runs');
            if (!empty($payrollPeriodId)) {
                $query->where('payroll_period_id', (int) $payrollPeriodId);
            }

            $run = $query
                ->orderByRaw("CASE WHEN status = 'running' THEN 0 WHEN status = 'completed' THEN 1 ELSE 2 END")
                ->orderByDesc('updated_at')
                ->first();
        }

        if (!$run) {
            return $this->errorResponse('Process run not found', 404);
        }

        return $this->successResponse([
            'id' => $run->id,
            'status' => $run->status,
            'phase' => $run->phase ?? null,
            'current_date' => $run->current_date ?? null,
            'current_date_index' => $run->current_date_index ?? null,
            'total_dates' => $run->total_dates ?? null,
            'current_employee_index' => $run->current_employee_index ?? null,
            'total_employees' => $run->total_employees ?? null,
            'progress_message' => $run->progress_message ?? null,
            'started_at' => $run->started_at ?? null,
            'updated_at' => $run->updated_at ?? null,
        ]);
    }

    /**
     * Edit daily time entries (AM/PM IN/OUT, Break IN/OUT) for an employee within a payroll period.
     * Expects payload:
     * {
     *   payroll_period_id: number,
     *   employee_id: number,
     *   records: [{ id?: number, date: 'YYYY-MM-DD', am_in?: 'HH:mm'|'HH:mm:ss'|null, am_out?: ..., break_in?: ..., break_out?: ..., pm_in?: ..., pm_out?: ... }]
     * }
     * Updates time_data rows and then reprocesses computed fields for the employee.
     */
    public function editTimes(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'payroll_period_id' => 'required|integer',
            'employee_id' => 'required|integer',
            'records' => 'required|array|min:1',
            'records.*.id' => 'nullable|integer',
            'records.*.date' => 'required|date',
            'records.*.source' => 'nullable|string|in:time_data,time_data_adj,current',
            'records.*.target_payroll_period_id' => 'nullable|integer',
            'records.*.am_in' => 'nullable|string',
            'records.*.am_out' => 'nullable|string',
            'records.*.break_in' => 'nullable|string',
            'records.*.break_out' => 'nullable|string',
            'records.*.pm_in' => 'nullable|string',
            'records.*.pm_out' => 'nullable|string',
        ]);

        $employee_id = (int)$validated['employee_id'];
        $payroll_period_id = (int)$validated['payroll_period_id'];
        $records = $validated['records'];

        DB::beginTransaction();
        try {
            // Get current Philippine time (Asia/Manila is UTC+8)
            $philippineTime = \Carbon\Carbon::now('Asia/Manila');

            foreach ($records as $rec) {
                $id = $rec['id'] ?? null;
                $date = $rec['date'];

                // Normalize date to ensure exact match (YYYY-MM-DD format)
                $dateFormatted = \Carbon\Carbon::parse($date)->format('Y-m-d');

                $normalize = function ($v) {
                    if ($v === null || $v === '' || strtolower((string)$v) === 'null') return null;
                    $s = (string)$v;
                    // Accept HH:mm or HH:mm:ss; normalize to HH:mm:ss
                    if (strlen($s) === 5) {
                        return $s . ':00';
                    }
                    return $s;
                };

                // Decide target table: time_data (current period) vs time_data_adj (preceding period snapshot)
                $source = $rec['source'] ?? 'time_data';
                $table = $source === 'time_data_adj' ? 'time_data_adj' : 'time_data';

                // Fetch existing row FIRST to detect changes - use exact date match
                $existingQuery = DB::table($table)
                    ->where('employee_id', $employee_id);
                if ($id) {
                    $existingQuery->where('id', $id);
                } else {
                    // Use exact date comparison to match only the specific date
                    $existingQuery->where('payroll_period_id', $payroll_period_id)
                        ->whereRaw('CAST(date AS DATE) = ?', [$dateFormatted]);
                }

                // For time_data_adj, further narrow by target_payroll_period_id when provided
                if ($table === 'time_data_adj' && !empty($rec['target_payroll_period_id'])) {
                    $existingQuery->where('target_payroll_period_id', (int)$rec['target_payroll_period_id']);
                }
                $existing = $existingQuery->first();

                if (!$existing) {
                    continue; // Skip this record if it doesn't exist
                }

                // Build update array with only fields that are being changed
                $update = [];
                $hasTimeChanges = false; // Track if any time fields actually changed

                // Normalize function for comparison (handles NULL, empty, and time formats)
                $normalizeForComparison = function ($v) {
                    if ($v === null || $v === '' || strtolower((string)$v) === 'null') return null;
                    $s = (string)$v;
                    // Remove seconds if present for comparison (07:57:00 vs 07:57 should be equal)
                    if (strlen($s) >= 8 && substr($s, 5, 1) === ':') {
                        return substr($s, 0, 5); // Return HH:mm
                    }
                    if (strlen($s) === 5) {
                        return $s; // Already HH:mm
                    }
                    return $s;
                };

                // Check each time field and only add to update if it actually changed
                $timeFields = ['am_in', 'am_out', 'break_in', 'break_out', 'pm_in', 'pm_out'];
                foreach ($timeFields as $field) {
                    if (array_key_exists($field, $rec)) {
                        $newValue = $normalize($rec[$field]);
                        $existingValue = $existing->$field ?? null;

                        // Normalize both values for comparison
                        $normalizedNew = $normalizeForComparison($newValue);
                        $normalizedExisting = $normalizeForComparison($existingValue);

                        // Only add to update if value actually changed
                        if ($normalizedNew !== $normalizedExisting) {
                            $update[$field] = $newValue;
                            $hasTimeChanges = true;
                        }
                    }
                }

                // For preceding-period snapshots (time_data_adj), keep absent in sync with presence:
                // - If user supplies any IN/OUT time (am_in or pm_out) where previously absent = 1,
                //   flip absent to 0 so the snapshot no longer counts as an absence.
                if ($table === 'time_data_adj') {
                    // Determine new effective values after this edit
                    $newAmIn  = array_key_exists('am_in', $update)  ? $update['am_in']  : $existing->am_in;
                    $newPmOut = array_key_exists('pm_out', $update) ? $update['pm_out'] : $existing->pm_out;
                    $wasAbsent = floatval($existing->absent ?? 0) > 0;

                    // If there is now any presence and it used to be an absence, clear the absent flag.
                    if (($newAmIn !== null || $newPmOut !== null) && $wasAbsent) {
                        $update['absent'] = 0.0;
                        $hasTimeChanges = true;
                    }
                }

                // Only proceed if there are actual time changes
                if (!$hasTimeChanges) {
                    continue; // Skip this record - no changes
                }

                // Mark as edited only if there are actual time changes
                $update['is_edited'] = 1;
                $update['updated_at'] = $philippineTime;

                // Update only the specific date row - use exact date match
                if (!empty($update)) {
                    $updatedCount = 0;
                    if ($id) {
                        // When ID is provided, use primary key targeting. Date comparisons can be
                        // brittle across DB drivers/timezones for DATE vs DATETIME values.
                        $updateQuery = DB::table($table)
                            ->where('id', $id)
                            ->where('employee_id', $employee_id);

                        if ($table === 'time_data_adj' && !empty($rec['target_payroll_period_id'])) {
                            $updateQuery->where('target_payroll_period_id', (int)$rec['target_payroll_period_id']);
                        }

                        $updatedCount = $updateQuery->update($update);
                    } else {
                        // When no ID, use exact date match to update only the specific date
                        $updateQuery = DB::table($table)
                            ->where('employee_id', $employee_id)
                            ->where('payroll_period_id', $payroll_period_id)
                            ->whereRaw('CAST(date AS DATE) = ?', [$dateFormatted]);

                        if ($table === 'time_data_adj' && !empty($rec['target_payroll_period_id'])) {
                            $updateQuery->where('target_payroll_period_id', (int)$rec['target_payroll_period_id']);
                        }

                        $updatedCount = $updateQuery->update($update);
                    }
                }
            }

            // Intentionally do not auto-reprocess here.
            // Reprocess should run only from explicit user actions
            // (Process Attendance on already-processed period, or Reprocess button).

            DB::commit();
            return $this->successResponse([
                'message' => 'Time entries updated successfully',
                'employee_id' => $employee_id,
                'payroll_period_id' => $payroll_period_id
            ], 'Time entries updated successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update time entries', 500, [
                'exception' => $e->getMessage()
            ]);
        }
    }
    public function index()
    {
        try {
            $intervals = DB::table('payroll_intervals')->where('active', true)->get();
            $dummy_daily_time_records = array(
                'id' => 0,
                'photo' => null,
                'employee_no'  => null,
                'department' => null,
                'position' => null,
                'name' => null
            );

            $daily_time_records = (object)$dummy_daily_time_records;
            $daily_time_records = collect([$daily_time_records]);

            // trigger auto-approved leave
            // (new LeaveService)->autoApproved();

            return $this->successResponse([
                'intervals' => $intervals,
                'daily_time_records' => $daily_time_records
            ], 'Process attendance data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve process attendance data: ' . $e->getMessage());
        }
    }

    /**
     * Get payroll periods for a specific interval.
     * Accepts optional query param `type`:
     *   - 'regular' → only periods linked to employment types 1, 3, 5, 7
     *   - 'cos'     → only periods linked to employment types 2, 4, 6
     *   - (omitted) → all periods (no employment-type filter)
     */
    public function getPayrollPeriods($intervalId)
    {
        try {
            $type = request()->query('type'); // 'regular', 'cos', or null

            $regularEtypes = [1, 3, 5, 7];
            $cosEtypes     = [2, 4, 6];

            $query = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.attendance_start_date',
                    'a.attendance_end_date',
                    'a.payroll_start_date',
                    'a.payroll_end_date',
                    'a.release_date',
                    'a.posted',
                    'a.active',
                    'b.name as interval_name',
                    'c.name as cutoff_name',
                    DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name")
                )
                ->where([
                    'a.active' => true,
                    'a.payroll_interval_id' => $intervalId,
                ]);

            if ($type === 'regular') {
                $query->whereExists(function ($q) use ($regularEtypes) {
                    $q->select(DB::raw(1))
                        ->from('payroll_period_Etype as ppe')
                        ->whereColumn('ppe.payrollperiod_id', 'a.id')
                        ->whereIn('ppe.employmenttype_id', $regularEtypes);
                });
            } elseif ($type === 'cos') {
                $query->whereExists(function ($q) use ($cosEtypes) {
                    $q->select(DB::raw(1))
                        ->from('payroll_period_Etype as ppe')
                        ->whereColumn('ppe.payrollperiod_id', 'a.id')
                        ->whereIn('ppe.employmenttype_id', $cosEtypes);
                });
            }

            $periods = $query->orderBy('a.release_date', 'desc')->get();

            return $this->successResponse($periods, 'Payroll periods retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll periods: ' . $e->getMessage());
        }
    }

    /**
     * Check if payroll period is posted and has time_data_summary records
     */
    public function checkPayrollPeriodStatus($payrollPeriodId)
    {
        try {
            $payrollPeriod = DB::table('payroll_periods')
                ->where('id', $payrollPeriodId)
                ->select('id', 'posted')
                ->first();

            if (!$payrollPeriod) {
                return $this->errorResponse('Payroll period not found');
            }

            $hasSummaryRecords = DB::table('time_data_summary')
                ->where('Payroll_Period_ID', $payrollPeriodId)
                ->exists();

            return $this->successResponse([
                'is_posted' => $payrollPeriod->posted == 1,
                'has_summary_records' => $hasSummaryRecords,
                'posted' => $payrollPeriod->posted == 1
            ], 'Payroll period status retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to check payroll period status: ' . $e->getMessage());
        }
    }

    /**
     * Preview leave reversals that may be credited back due to work suspension overlap.
     * Used by Process Attendance UI before running processing.
     */
    public function getLeaveReversalPreview($payrollPeriodId, Request $request)
    {
        try {
            $payrollPeriod = DB::table('payroll_periods')
                ->select('id', 'payroll_interval_id', 'attendance_start_date', 'attendance_end_date')
                ->where('id', $payrollPeriodId)
                ->first();

            if (!$payrollPeriod) {
                return $this->errorResponse('Payroll period not found');
            }

            $precedingPayrollPeriodId = $request->query('preceding_payroll_period_id');
            $includedPrecedingPeriod = null;
            /** @var object|null Row for the included preceding period (id + attendance bounds). */
            $precedingPeriodRow = null;

            if (!empty($precedingPayrollPeriodId)) {
                $precedingPeriodRow = DB::table('payroll_periods')
                    ->select('id', 'attendance_start_date', 'attendance_end_date')
                    ->where('id', (int) $precedingPayrollPeriodId)
                    ->first();

                if ($precedingPeriodRow) {
                    $includedPrecedingPeriod = (int) $precedingPeriodRow->id;
                }
            } elseif (!empty($payrollPeriod->payroll_interval_id) && !empty($payrollPeriod->attendance_start_date)) {
                // First-time Process Attendance often omits preceding period; without it the preview
                // cannot include adjusted dates (time_data_adj) mapped from preceding -> current.
                // Default to the chronologically prior period in the same interval (same rule as UI).
                $precedingPeriodRow = DB::table('payroll_periods')
                    ->select('id', 'attendance_start_date', 'attendance_end_date')
                    ->where('payroll_interval_id', $payrollPeriod->payroll_interval_id)
                    ->where('attendance_start_date', '<', $payrollPeriod->attendance_start_date)
                    ->orderByDesc('attendance_start_date')
                    ->orderByDesc('id')
                    ->first();
                if ($precedingPeriodRow) {
                    $includedPrecedingPeriod = (int) $precedingPeriodRow->id;
                }
            }

            // Period ownership rules for preview:
            // 1) Non-adjusted dates are owned by their native current period.
            // 2) Adjusted dates (mapped via time_data_adj source->target) are owned only by
            //    the target current period when that preceding period is selected.
            $dateScopeSql = "
                (
                    ld.leave_date BETWEEN ? AND ?
                    AND NOT EXISTS (
                        SELECT 1
                        FROM dbo.time_data_adj tda_ex
                        WHERE tda_ex.employee_id = lr.employee_id
                          AND tda_ex.date = ld.leave_date
                          AND tda_ex.source_time_data_id IS NOT NULL
                          AND tda_ex.status IN ('PENDING', 'APPLIED')
                    )
                )
            ";
            $bindings = [
                $payrollPeriod->attendance_start_date,
                $payrollPeriod->attendance_end_date,
            ];

            // Include preceding-period dates when a preceding period is selected or inferred:
            // (a) Adjusted rows mapped into the current period via time_data_adj (exists after first process).
            // (b) Any leave dated inside the preceding period's attendance window. Needed for first-time
            //     process: leaves (e.g. Sept 14 first-half) overlap work cancellation before time_data_adj
            //     rows exist; reprocess already matched via (a).
            if ($includedPrecedingPeriod !== null) {
                $dateScopeSql .= "
                    OR EXISTS (
                        SELECT 1
                        FROM dbo.time_data_adj tda
                        WHERE tda.employee_id = lr.employee_id
                          AND tda.date = ld.leave_date
                          AND tda.payroll_period_id = ?
                          AND tda.target_payroll_period_id = ?
                          AND tda.source_time_data_id IS NOT NULL
                          AND tda.status IN ('PENDING', 'APPLIED')
                    )
                ";
                $bindings[] = $includedPrecedingPeriod;
                $bindings[] = (int) $payrollPeriodId;

                if (
                    $precedingPeriodRow
                    && !empty($precedingPeriodRow->attendance_start_date)
                    && !empty($precedingPeriodRow->attendance_end_date)
                ) {
                    $dateScopeSql .= "
                    OR (
                        ld.leave_date BETWEEN ? AND ?
                    )";
                    $bindings[] = $precedingPeriodRow->attendance_start_date;
                    $bindings[] = $precedingPeriodRow->attendance_end_date;
                }
            }

            $rows = DB::select(
                "
                ;WITH Candidate AS (
                    SELECT
                        e.id AS employee_id,
                        e.employee_no,
                        CONCAT(
                            ISNULL(e.first_name, ''),
                            CASE WHEN ISNULL(e.middle_name, '') = '' THEN '' ELSE ' ' + LEFT(e.middle_name, 1) + '.' END,
                            CASE WHEN ISNULL(e.last_name, '') = '' THEN '' ELSE ' ' + e.last_name END
                        ) AS employee_name,
                        lr.id AS leave_header_id,
                        ld.id AS leave_detail_id,
                        lt.name AS leave_type_name,
                        lr.leave_type_id,
                        ld.leave_date AS work_date,
                        wc.id AS work_cancellation_id,
                        ISNULL(ld.with_pay, 0) + ISNULL(ld.without_pay, 0) AS leave_qty,
                        ov.scheduled_minutes,
                        ov.overlap_minutes
                    FROM dbo.leave_headers lr
                    INNER JOIN dbo.leave_details ld
                        ON ld.leave_id = lr.id
                    INNER JOIN dbo.employees e
                        ON e.id = lr.employee_id
                    LEFT JOIN dbo.leave_types lt
                        ON lt.id = lr.leave_type_id
                    INNER JOIN dbo.work_cancellations wc
                        ON ld.leave_date BETWEEN wc.date_from AND wc.date_to
                    LEFT JOIN dbo.fix_schedules_details fsd
                        ON e.is_shifting = 0
                       AND fsd.fix_schedule_id = e.work_schedule_id
                       AND fsd.day_id = CASE
                                           WHEN DATEPART(WEEKDAY, ld.leave_date) = 1 THEN 7
                                           ELSE DATEPART(WEEKDAY, ld.leave_date) - 1
                                        END
                    LEFT JOIN dbo.shift_schedules_details ssd
                        ON e.is_shifting = 1
                       AND ssd.shift_schedule_id = e.work_schedule_id
                       AND ssd.shift_date = ld.leave_date
                    CROSS APPLY (
                        SELECT
                            CAST(DATEADD(SECOND, DATEDIFF(SECOND, '00:00:00', COALESCE(ssd.am_in, fsd.am_in)), CAST(ld.leave_date AS datetime2)) AS datetime2) AS sched_start_dt,
                            CAST(DATEADD(SECOND, DATEDIFF(SECOND, '00:00:00', COALESCE(ssd.pm_out, fsd.pm_out)), CAST(ld.leave_date AS datetime2)) AS datetime2) AS sched_end_dt,
                            CAST(DATEADD(SECOND, DATEDIFF(SECOND, '00:00:00', CAST(wc.time_from AS time)), CAST(wc.date_from AS datetime2)) AS datetime2) AS cancel_start_dt,
                            CAST(DATEADD(SECOND, DATEDIFF(SECOND, '00:00:00', CAST(wc.time_to   AS time)), CAST(wc.date_to   AS datetime2)) AS datetime2) AS cancel_end_dt
                    ) dt
                    CROSS APPLY (
                        SELECT
                            CASE WHEN dt.cancel_start_dt > dt.sched_start_dt THEN dt.cancel_start_dt ELSE dt.sched_start_dt END AS overlap_start_dt,
                            CASE WHEN dt.cancel_end_dt   < dt.sched_end_dt   THEN dt.cancel_end_dt   ELSE dt.sched_end_dt   END AS overlap_end_dt
                    ) ovdt
                    CROSS APPLY (
                        SELECT
                            CAST(ROUND(ISNULL(COALESCE(ssd.work_hours, fsd.work_hours), 0) * 60, 0) AS INT) AS scheduled_minutes,
                            CASE
                                WHEN dt.sched_start_dt IS NULL OR dt.sched_end_dt IS NULL THEN 0
                                WHEN ovdt.overlap_end_dt > ovdt.overlap_start_dt THEN DATEDIFF(MINUTE, ovdt.overlap_start_dt, ovdt.overlap_end_dt)
                                ELSE 0
                            END AS overlap_span_minutes,
                            DATEDIFF(MINUTE, dt.sched_start_dt, dt.sched_end_dt) AS schedule_span_minutes
                    ) ov0
                    CROSS APPLY (
                        SELECT
                            CASE
                                WHEN dt.sched_start_dt IS NULL OR dt.sched_end_dt IS NULL OR ov0.schedule_span_minutes < 60
                                    THEN NULL
                                ELSE DATEADD(MINUTE, ((ov0.schedule_span_minutes - 60) / 2), dt.sched_start_dt)
                            END AS break_start_dt,
                            CASE
                                WHEN dt.sched_start_dt IS NULL OR dt.sched_end_dt IS NULL OR ov0.schedule_span_minutes < 60
                                    THEN NULL
                                ELSE DATEADD(MINUTE, ((ov0.schedule_span_minutes - 60) / 2) + 60, dt.sched_start_dt)
                            END AS break_end_dt
                    ) br
                    CROSS APPLY (
                        SELECT
                            CASE
                                WHEN br.break_start_dt IS NULL OR br.break_end_dt IS NULL THEN 0
                                ELSE
                                    CASE
                                        WHEN
                                            (CASE WHEN ovdt.overlap_start_dt > br.break_start_dt THEN ovdt.overlap_start_dt ELSE br.break_start_dt END)
                                            <
                                            (CASE WHEN ovdt.overlap_end_dt   < br.break_end_dt   THEN ovdt.overlap_end_dt   ELSE br.break_end_dt   END)
                                        THEN DATEDIFF(
                                                MINUTE,
                                                (CASE WHEN ovdt.overlap_start_dt > br.break_start_dt THEN ovdt.overlap_start_dt ELSE br.break_start_dt END),
                                                (CASE WHEN ovdt.overlap_end_dt   < br.break_end_dt   THEN ovdt.overlap_end_dt   ELSE br.break_end_dt   END)
                                            )
                                        ELSE 0
                                    END
                            END AS overlapped_break_minutes
                    ) brm
                    CROSS APPLY (
                        SELECT
                            CASE
                                WHEN ov0.overlap_span_minutes - brm.overlapped_break_minutes > 0
                                    THEN ov0.overlap_span_minutes - brm.overlapped_break_minutes
                                ELSE 0
                            END AS overlap_minutes,
                            ov0.scheduled_minutes
                    ) ov
                    WHERE (
                            {$dateScopeSql}
                          )
                      AND ov.overlap_minutes > 0
                      AND (
                            (lr.is_cancel = 0 OR lr.is_cancel IS NULL)
                        AND (lr.is_cancel_2 = 0 OR lr.is_cancel_2 IS NULL)
                        AND (lr.is_cancel_3 = 0 OR lr.is_cancel_3 IS NULL)
                      )
                      AND EXISTS (
                            SELECT 1
                            FROM dbo.approver_headers ah
                            INNER JOIN dbo.employees emp ON emp.id = lr.employee_id
                            WHERE ah.department_id = emp.department_id
                              AND ah.type_id = 1
                              AND (ah.approver_id_1 IS NULL OR lr.approved   = 1)
                              AND (ah.approver_id_2 IS NULL OR lr.approved_2 = 1)
                              AND (ah.approver_id_3 IS NULL OR lr.approved_3 = 1)
                      )
                )
                SELECT
                    Candidate.employee_id,
                    Candidate.employee_no,
                    Candidate.employee_name,
                    Candidate.leave_header_id,
                    Candidate.leave_detail_id,
                    Candidate.leave_type_id,
                    Candidate.leave_type_name,
                    Candidate.work_date,
                    Candidate.work_cancellation_id,
                    Candidate.scheduled_minutes,
                    Candidate.overlap_minutes,
                    CASE
                        WHEN Candidate.scheduled_minutes <= 0 THEN 'NONE'
                        WHEN Candidate.overlap_minutes >= Candidate.scheduled_minutes THEN 'WHOLE'
                        WHEN Candidate.overlap_minutes * 2 >= Candidate.scheduled_minutes THEN 'HALF'
                        ELSE 'PARTIAL'
                    END AS cancel_segment,
                    CAST(Candidate.leave_qty AS DECIMAL(18,3)) AS leave_qty,
                    CAST(
                        CASE
                            WHEN wclr.id IS NOT NULL THEN
                                ISNULL(lc.credits, 0) - (
                                    CASE
                                        WHEN Candidate.scheduled_minutes <= 0 THEN 0.000
                                        ELSE
                                            CASE
                                                WHEN Candidate.leave_qty * dbo.fn_MinutesToDayFraction(Candidate.overlap_minutes) > Candidate.leave_qty
                                                    THEN Candidate.leave_qty
                                                ELSE Candidate.leave_qty * dbo.fn_MinutesToDayFraction(Candidate.overlap_minutes)
                                            END
                                    END
                                )
                            ELSE ISNULL(lc.credits, 0)
                        END
                    AS DECIMAL(18,3)) AS leave_credits_before,
                    CAST(
                        CASE
                            WHEN Candidate.scheduled_minutes <= 0 THEN 0.000
                            ELSE
                                CASE
                                    WHEN Candidate.leave_qty * dbo.fn_MinutesToDayFraction(Candidate.overlap_minutes) > Candidate.leave_qty
                                        THEN Candidate.leave_qty
                                    ELSE Candidate.leave_qty * dbo.fn_MinutesToDayFraction(Candidate.overlap_minutes)
                                END
                        END
                    AS DECIMAL(18,3)) AS revert_qty,
                    CAST(
                        CASE
                            WHEN wclr.id IS NOT NULL THEN ISNULL(lc.credits, 0)
                            ELSE
                                ISNULL(lc.credits, 0) +
                                (
                                    CASE
                                        WHEN Candidate.scheduled_minutes <= 0 THEN 0.000
                                        ELSE
                                            CASE
                                                WHEN Candidate.leave_qty * dbo.fn_MinutesToDayFraction(Candidate.overlap_minutes) > Candidate.leave_qty
                                                    THEN Candidate.leave_qty
                                                ELSE Candidate.leave_qty * dbo.fn_MinutesToDayFraction(Candidate.overlap_minutes)
                                            END
                                    END
                                )
                        END
                    AS DECIMAL(18,3)) AS leave_credits_after,
                    CAST(CASE WHEN wclr.id IS NOT NULL THEN 1 ELSE 0 END AS BIT) AS is_redeemed
                FROM Candidate
                LEFT JOIN dbo.leave_credits lc
                    ON lc.employee_id = Candidate.employee_id
                   AND lc.leave_type_id = Candidate.leave_type_id
                LEFT JOIN dbo.work_cancellation_leave_reversals wclr
                    ON wclr.employee_id = Candidate.employee_id
                   AND wclr.leave_detail_id = Candidate.leave_detail_id
                   AND wclr.work_date = Candidate.work_date
                ORDER BY work_date ASC, employee_name ASC, leave_detail_id ASC
                ",
                $bindings
            );

            $rowsArray = array_map(static function ($row) {
                return [
                    'employee_id' => (int) $row->employee_id,
                    'employee_no' => $row->employee_no,
                    'employee_name' => trim((string) $row->employee_name),
                    'leave_header_id' => (int) $row->leave_header_id,
                    'leave_detail_id' => (int) $row->leave_detail_id,
                    'leave_type_id' => (int) $row->leave_type_id,
                    'leave_type_name' => $row->leave_type_name,
                    'work_date' => $row->work_date,
                    'work_cancellation_id' => (int) $row->work_cancellation_id,
                    'scheduled_minutes' => (int) $row->scheduled_minutes,
                    'overlap_minutes' => (int) $row->overlap_minutes,
                    'cancel_segment' => $row->cancel_segment,
                    'leave_qty' => (float) $row->leave_qty,
                    'leave_credits_before' => (float) $row->leave_credits_before,
                    'revert_qty' => (float) $row->revert_qty,
                    'leave_credits_after' => (float) $row->leave_credits_after,
                    'is_redeemed' => (bool) ((int) ($row->is_redeemed ?? 0) === 1),
                ];
            }, $rows);

            $summary = [
                'employee_count' => count(array_unique(array_column($rowsArray, 'employee_id'))),
                'leave_detail_count' => count($rowsArray),
                'total_revert_qty' => round(array_sum(array_column($rowsArray, 'revert_qty')), 3),
            ];

            return $this->successResponse([
                'rows' => $rowsArray,
                'summary' => $summary,
                'payroll_period_id' => (int) $payrollPeriodId,
                'preceding_payroll_period_id' => $includedPrecedingPeriod,
                'preceding_payroll_period_id_requested' => !empty($precedingPayrollPeriodId) ? (int) $precedingPayrollPeriodId : null,
                'current_period_range' => [
                    'attendance_start_date' => $payrollPeriod->attendance_start_date,
                    'attendance_end_date' => $payrollPeriod->attendance_end_date,
                ],
            ], 'Leave reversal preview retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load leave reversal preview: ' . $e->getMessage());
        }
    }

    /**
     * Get employee attendance data with calculations for process attendance table
     */
    public function getEmployeeAttendanceData($payrollPeriodId, Request $request)
    {
        set_time_limit(3600);

        $callId = uniqid('pa_getEmp_', true);
        $startedAt = microtime(true);

        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 10);
        $search = (string) ($request->get('search', '') ?? '');

        \Log::info('[ProcessAttendance][TIMING] getEmployeeAttendanceData START', [
            'call_id' => $callId,
            'payroll_period_id' => $payrollPeriodId,
            'page' => $page,
            'per_page' => $perPage,
            'search_len' => strlen($search),
            'userId' => auth()->id(),
        ]);

        try {
            // Get user's employee_no to find their branch
            $user_employee = DB::table('users')
                ->where('id', auth()->user()->id)
                ->select('employee_no')
                ->first();

            $user_branch_id = null;
            if ($user_employee && $user_employee->employee_no) {
                $employee_branch = DB::table('employees')
                    ->where('employee_no', $user_employee->employee_no)
                    ->select('branch_id')
                    ->first();
                $user_branch_id = $employee_branch ? $employee_branch->branch_id : null;
            }

            // Get search and filter parameters (page/per_page/search already read above for timing logs)
            $departmentId = $request->get('departmentId');
            $positionId = $request->get('positionId');

            $payroll_period = DB::table('payroll_periods')->where('id', $payrollPeriodId)->first();
            $from_date = $payroll_period->attendance_start_date ?? null;
            $to_date = $payroll_period->attendance_end_date ?? null;
            $allowedEmploymentTypeIds = $this->getPayrollPeriodEmploymentTypeIds((int) $payrollPeriodId);
            if ($error = $this->validatePayrollPeriodEmploymentTypes((int) $payrollPeriodId, $allowedEmploymentTypeIds)) {
                return $this->errorResponse($error['message']);
            }


            // When every in-scope employee with time_data has a matching summary row, list from time_data_summary
            // (no heavy GROUP BY over all time_data rows).
            // Count DISTINCT employee_id only for active is_employee rows — same as this endpoint's time_data query
            // and bulkInsertTimeDataSummaryForPeriod. Raw time_data can include inactive employees; summaries do not.
            $tdEmpDistinctQuery = DB::table('time_data as td')
                ->join('employees as eb', 'eb.id', '=', 'td.employee_id')
                ->where('td.payroll_period_id', $payrollPeriodId)
                ->where('eb.active', true)
                ->where('eb.is_employee', true);
            $this->applyPayrollPeriodEmploymentTypeScope($tdEmpDistinctQuery, $allowedEmploymentTypeIds, 'eb.employment_type_id');
            $tdEmpDistinct = (int) $tdEmpDistinctQuery
                ->selectRaw('COUNT(DISTINCT td.employee_id) as c')
                ->value('c');
            $summaryCount = (int) DB::table('time_data_summary')
                ->where('Payroll_Period_ID', $payrollPeriodId)
                ->count();
            $useSummaryList = $summaryCount > 0 && $tdEmpDistinct > 0 && $summaryCount === $tdEmpDistinct;

            \Log::info('[ProcessAttendance][TIMING] getEmployeeAttendanceData path', [
                'call_id' => $callId,
                'use_summary_list' => $useSummaryList,
                'summary_count' => $summaryCount,
                'time_data_distinct_employees_active' => $tdEmpDistinct,
            ]);

            $nameOrderExpr = "CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)";
            $offset = ($page - 1) * $perPage;

            if ($useSummaryList) {
                $query = DB::table('time_data_summary as tds')
                    ->join('employees as b', 'tds.Employee_ID', '=', 'b.id')
                    ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
                    ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
                    ->leftJoin('employment_types as e', 'e.id', '=', 'b.employment_type_id')
                    ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'b.employment_type_id')
                    ->leftJoin('fix_schedules_details as fsd', function ($join) {
                        $join->on('fsd.fix_schedule_id', '=', 'b.work_schedule_id')
                            ->where('fsd.day_id', '=', 1)
                            ->whereNotNull('fsd.am_in')
                            ->whereNotNull('fsd.pm_out');
                    })
                    ->where('tds.Payroll_Period_ID', $payrollPeriodId)
                    ->where([
                        'b.active' => true,
                        'b.is_employee' => true,
                    ]);
                $this->applyPayrollPeriodEmploymentTypeScope($query, $allowedEmploymentTypeIds, 'b.employment_type_id');
                $query->select(
                    'b.id as employee_id',
                    'b.employee_no',
                    'b.photo',
                    'b.department_id',
                    'c.code as department_code',
                    'c.name as department',
                    'd.name as position',
                    'e.name as employment_type',
                    'b.salary',
                    'tks.work_days as setup_work_days',
                    'tks.work_hours as setup_work_hours',
                    'fsd.am_in as scheduled_am_in',
                    'fsd.pm_out as scheduled_pm_out',
                    DB::raw("{$nameOrderExpr} as full_name"),
                    DB::raw("{$nameOrderExpr} as name"),
                    DB::raw('CAST(ISNULL(tds.Adjustment_Amount, 0) AS DECIMAL(18,2)) as adjustment_amount'),
                    DB::raw('CAST(ISNULL(tds.Adjustment_Amount_OT_Holiday, 0) AS DECIMAL(18,2)) as adjustment_amount_ot_holiday'),
                    'tds.Adjustment_Period_ID as adjustment_period_id'
                );

                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('b.employee_no', 'LIKE', "%{$search}%")
                            ->orWhere('c.name', 'LIKE', "%{$search}%")
                            ->orWhere('d.name', 'LIKE', "%{$search}%")
                            ->orWhere('e.name', 'LIKE', "%{$search}%")
                            ->orWhere('b.first_name', 'LIKE', "%{$search}%")
                            ->orWhere('b.last_name', 'LIKE', "%{$search}%");
                    });
                }

                if (!empty($departmentId)) {
                    $query->where('b.department_id', $departmentId);
                }

                if (!empty($positionId)) {
                    $query->where('b.position_id', $positionId);
                }

                $query->orderByRaw($nameOrderExpr . ' asc');

                $total = (clone $query)->reorder()->count();
                $data = $query->offset($offset)->limit($perPage)->get();
            } else {
                // Build the base query
                // IMPORTANT: Left join with time_data_summary to use saved values when viewing processed attendance
                $query = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
                    ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
                    ->leftJoin('employment_types as e', 'e.id', '=', 'b.employment_type_id')
                    ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'b.employment_type_id')
                    ->leftJoin('time_data_summary as tds', function ($join) use ($payrollPeriodId) {
                        $join->on('tds.Employee_ID', '=', 'b.id')
                            ->where('tds.Payroll_Period_ID', '=', $payrollPeriodId);
                    })
                    ->leftJoin('fix_schedules_details as fsd', function ($join) {
                        $join->on('fsd.fix_schedule_id', '=', 'b.work_schedule_id')
                            ->where('fsd.day_id', '=', 1) // Monday as representative
                            ->whereNotNull('fsd.am_in')
                            ->whereNotNull('fsd.pm_out');
                    })
                    ->select(
                        'b.id as employee_id',
                        'b.employee_no',
                        'b.photo',
                        'b.department_id',
                        'c.code as department_code',
                        'c.name as department',
                        'd.name as position',
                        'e.name as employment_type',
                        'b.salary',
                        'tks.work_days as setup_work_days',
                        'tks.work_hours as setup_work_hours',
                        'fsd.am_in as scheduled_am_in',
                        'fsd.pm_out as scheduled_pm_out',
                        DB::raw("CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name) as full_name"),
                        DB::raw("CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name) as name"),
                        // Count days covered (days with time data)
                        DB::raw("COUNT(DISTINCT a.date) as days_covered"),
                        // Sum late, undertime, absent amounts (no rounding)
                        // Use CAST to ensure proper decimal precision and handle NULL values correctly
                        // NOTE: We'll use time_data_summary values in PHP code if available
                        DB::raw("CAST(SUM(ISNULL(a.late, 0)) AS DECIMAL(18,3)) as total_late"),
                        DB::raw("CAST(SUM(ISNULL(a.undertime, 0)) AS DECIMAL(18,3)) as total_undertime"),
                        // IMPORTANT: For absent, we want to count ONLY actual absences
                        // When applied_offset = 1, the absent value is moved to absent_offset
                        // So we need to check BOTH absent and absent_offset (when applied_offset = 1)
                        // Exclude days where employee is on leave, holiday, OB, or rest day
                        // Count: absent (when applied_offset = 0) + absent_offset (when applied_offset = 1)
                        // But exclude leave/holiday/OB/rest days from both
                        // WFH days with partial absences (absent > 0) should still be counted as absences
                        DB::raw("CAST(SUM(CASE
                        WHEN ISNULL(a.leave, 0) > 0 THEN 0
                        WHEN a.is_holiday = 1 THEN 0
                        WHEN a.is_ob = 1 THEN 0
                        WHEN a.is_restday = 1 THEN 0
                        WHEN a.applied_offset = 1 THEN ISNULL(a.absent_offset, 0)
                        ELSE ISNULL(a.absent, 0)
                      END) AS DECIMAL(18,3)) as total_absent"),
                        // Sum offset values for display when offset is applied
                        // Use CAST to ensure proper decimal precision for small values
                        DB::raw("CAST(SUM(ISNULL(a.late_offset, 0)) AS DECIMAL(18,3)) as total_late_offset"),
                        DB::raw("CAST(SUM(ISNULL(a.undertime_offset, 0)) AS DECIMAL(18,3)) as total_undertime_offset"),
                        // For absent_offset: When applied_offset = 1, true absences are stored here
                        // But we need to exclude special non-working days (holidays, typhoons, rest days, etc.)
                        // We only count absent_offset when it's NOT a holiday/OB/leave/rest day
                        // AND when it represents a true absence (not a special non-working day)
                        // For now, we exclude all absent_offset from absence totals to avoid counting special days
                        // The frontend will use total_absent_offset for display purposes only
                        DB::raw("CAST(SUM(CASE
                        WHEN ISNULL(a.leave, 0) > 0 THEN 0
                        WHEN a.is_holiday = 1 THEN 0
                        WHEN a.is_ob = 1 THEN 0
                        WHEN a.is_restday = 1 THEN 0
                        ELSE ISNULL(a.absent_offset, 0)
                      END) AS DECIMAL(18,3)) as total_absent_offset"),
                        // Count rows with applied_offset = 1
                        DB::raw("SUM(CASE WHEN a.applied_offset = 1 THEN 1 ELSE 0 END) as offset_applied_count"),
                        DB::raw("COUNT(a.id) as total_row_count"),
                        // Flags to indicate presence of any tardiness records
                        // Check both regular late/undertime AND offset values
                        DB::raw("MAX(CASE WHEN ISNULL(a.late, 0) > 0 THEN 1 ELSE 0 END) as has_any_late_record"),
                        DB::raw("MAX(CASE WHEN ISNULL(a.undertime, 0) > 0 THEN 1 ELSE 0 END) as has_any_undertime_record"),
                        // IMPORTANT: Also check for offset values - these indicate tardiness even when late/undertime are 0
                        DB::raw("MAX(CASE WHEN ISNULL(a.late_offset, 0) > 0 THEN 1 ELSE 0 END) as has_any_late_offset_record"),
                        DB::raw("MAX(CASE WHEN ISNULL(a.undertime_offset, 0) > 0 THEN 1 ELSE 0 END) as has_any_undertime_offset_record"),
                        // Flags to indicate presence of any absence records
                        // Check both regular absent AND offset values
                        // IMPORTANT: Only count absences on regular work days (exclude leave/holiday/OB/rest days/special days)
                        // An employee has absences only if absent > 0 on a day that's NOT leave/holiday/OB/rest day/special day
                        // Note: is_holiday, is_ob, and is_restday are boolean NOT NULL columns, so we can check directly
                        // Note: absent_offset > 0 indicates special non-working days, so exclude them
                        // WFH days with partial absences (absent > 0) should still be counted as absences
                        DB::raw("MAX(CASE
                        WHEN ISNULL(a.leave, 0) > 0 THEN 0
                        WHEN a.is_holiday = 1 THEN 0
                        WHEN a.is_ob = 1 THEN 0
                        WHEN a.is_restday = 1 THEN 0
                        WHEN ISNULL(a.absent_offset, 0) > 0 THEN 0
                        WHEN ISNULL(a.absent, 0) > 0 THEN 1
                        ELSE 0
                    END) as has_any_absent_record"),
                        // For absent_offset flag, we don't count it as an absence since it indicates special non-working days
                        // So we always return 0 for this flag
                        DB::raw("0 as has_any_absent_offset_record"),
                        // Add adjustment columns from time_data_summary
                        DB::raw("CAST(ISNULL(tds.Adjustment_Amount, 0) AS DECIMAL(18,2)) as adjustment_amount"),
                        DB::raw("CAST(ISNULL(tds.Adjustment_Amount_OT_Holiday, 0) AS DECIMAL(18,2)) as adjustment_amount_ot_holiday"),
                        'tds.Adjustment_Period_ID as adjustment_period_id',
                        // Sum work hours for gross pay calculation
                        // NOTE: We'll use time_data_summary values in PHP code if available
                        DB::raw("CAST(SUM(ISNULL(a.work_hours, 0)) AS DECIMAL(18,4)) as total_work_hours")
                    )
                    ->where([
                        'a.payroll_period_id' => $payrollPeriodId,
                        'b.active' => true,
                        'b.is_employee' => true
                    ]);
                $this->applyPayrollPeriodEmploymentTypeScope($query, $allowedEmploymentTypeIds, 'b.employment_type_id');

                // Apply search filter
                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('b.employee_no', 'LIKE', "%{$search}%")
                            ->orWhere('c.name', 'LIKE', "%{$search}%")
                            ->orWhere('d.name', 'LIKE', "%{$search}%")
                            ->orWhere('e.name', 'LIKE', "%{$search}%")
                            ->orWhere('b.first_name', 'LIKE', "%{$search}%")
                            ->orWhere('b.last_name', 'LIKE', "%{$search}%");
                    });
                }

                // Apply department filter
                if (!empty($departmentId)) {
                    $query->where('b.department_id', $departmentId);
                }

                // Apply position filter
                if (!empty($positionId)) {
                    $query->where('b.position_id', $positionId);
                }

                // Group by and order
                $query->groupBy(
                    'b.id',
                    'b.employee_no',
                    'b.photo',
                    'b.department_id',
                    'c.code',
                    'c.name',
                    'd.name',
                    'e.name',
                    'b.salary',
                    'tks.work_days',
                    'tks.work_hours',
                    'b.first_name',
                    'b.middle_name',
                    'b.last_name',
                    'fsd.am_in',
                    'fsd.pm_out',
                    'tds.Adjustment_Amount',
                    'tds.Adjustment_Amount_OT_Holiday',
                    'tds.Adjustment_Period_ID'
                )
                    ->orderBy('full_name', 'asc');

                // Get total count for pagination
                $totalQuery = clone $query;
                // Avoid materializing the full grouped dataset in PHP.
                // We count rows of the grouped query as a subquery so SQL returns only the count.
                // Note: remove ORDER BY to keep SQL Server happy inside subqueries.
                $totalQuery->reorder();
                $subSql = $totalQuery->toSql();
                $total = DB::table(DB::raw("($subSql) as total_sub"))
                    ->mergeBindings($totalQuery)
                    ->count();

                // Apply pagination
                $data = $query->offset($offset)->limit($perPage)->get();
            }

            // Get processed employees from time_data_summary for this payroll period
            $processedEmployees = DB::table('time_data_summary')
                ->where('Payroll_Period_ID', $payrollPeriodId)
                ->pluck('Employee_ID')
                ->toArray();

            // Calculate overtime pay for each employee and add to total_amount (slow path only; summary path uses saved OT)
            $employee_ids = $data->pluck('employee_id')->toArray();

            $flagsByEmployee = collect();
            if ($useSummaryList && !empty($employee_ids)) {
                $flagsByEmployee = DB::table('time_data')
                    ->where('payroll_period_id', $payrollPeriodId)
                    ->whereIn('employee_id', $employee_ids)
                    ->select(
                        'employee_id',
                        DB::raw('MAX(CASE WHEN ISNULL(late, 0) > 0 THEN 1 ELSE 0 END) as has_any_late_record'),
                        DB::raw('MAX(CASE WHEN ISNULL(undertime, 0) > 0 THEN 1 ELSE 0 END) as has_any_undertime_record'),
                        DB::raw('MAX(CASE WHEN ISNULL(late_offset, 0) > 0 THEN 1 ELSE 0 END) as has_any_late_offset_record'),
                        DB::raw('MAX(CASE WHEN ISNULL(undertime_offset, 0) > 0 THEN 1 ELSE 0 END) as has_any_undertime_offset_record'),
                        DB::raw("MAX(CASE
                        WHEN ISNULL(leave, 0) > 0 THEN 0
                        WHEN is_holiday = 1 THEN 0
                        WHEN is_ob = 1 THEN 0
                        WHEN is_restday = 1 THEN 0
                        WHEN ISNULL(absent_offset, 0) > 0 THEN 0
                        WHEN ISNULL(absent, 0) > 0 THEN 1
                        ELSE 0
                    END) as has_any_absent_record")
                    )
                    ->groupBy('employee_id')
                    ->get()
                    ->keyBy('employee_id');
            }

            $overtime_pay_map = [];

            if (!$useSummaryList && !empty($employee_ids) && $from_date && $to_date) {
                // Get all overtime applications for these employees in the payroll period
                // Only include approved OT where payroll = 1 and service_credits = 0
                $overtime_applications = DB::table('overtime_applications as a')
                    ->join('overtime_types as b', 'b.id', '=', 'a.overtime_type_id')
                    ->join('employees as e', 'e.id', '=', 'a.employee_id')
                    ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'e.employment_type_id')
                    // Dynamic approver setup (type_id = 3 Overtime): determines whether approved_2/approved_3 are required.
                    // If approver_id_2 is NULL/0, we do not require approved_2 = 1.
                    // If approver_id_3 and approver_id_4 are both NULL/0, we do not require approved_3 = 1.
                    ->leftJoin(DB::raw("(SELECT x.employee_id,\n                            CASE WHEN ISNULL(ah2.approver_id_2, 0) = 0 THEN 0 ELSE 1 END as has_appr2,\n                            CASE WHEN ISNULL(ah2.approver_id_3, 0) = 0 AND ISNULL(ah2.approver_id_4, 0) = 0 THEN 0 ELSE 1 END as has_appr3\n                        FROM (\n                            SELECT ad.employee_id, ad.approver_id,\n                                   ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn\n                            FROM approver_details ad\n                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3\n                        ) x\n                        INNER JOIN approver_headers ah2 ON ah2.id = x.approver_id\n                        WHERE x.rn = 1) otappr"), 'otappr.employee_id', '=', 'a.employee_id')
                    ->select(
                        'a.employee_id',
                        'a.overtime_type_id',
                        'a.date',
                        'a.total_hours',
                        'b.rate as overtime_rate',
                        'e.salary',
                        'e.work_schedule_id',
                        'e.is_shifting',
                        'tks.work_days as setup_work_days',
                        'tks.work_hours as setup_work_hours'
                    )
                    ->whereIn('a.employee_id', $employee_ids)
                    ->whereBetween('a.date', [$from_date, $to_date])
                    // Only include OT approved by required approvers per approver_headers setup
                    ->where('a.approved', 1)
                    ->where(function ($q) {
                        $q->whereRaw('ISNULL(otappr.has_appr2, 0) = 0')->orWhere('a.approved_2', 1);
                    })
                    ->where(function ($q) {
                        $q->whereRaw('ISNULL(otappr.has_appr3, 0) = 0')->orWhere('a.approved_3', 1);
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved', 0)
                            ->orWhereNull('a.disapproved');
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved_2', 0)->orWhereNull('a.disapproved_2');
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved_3', 0)->orWhereNull('a.disapproved_3');
                    })
                    ->where(function ($q) {
                        $q->where('a.is_cancel', 0)->orWhereNull('a.is_cancel');
                    })
                    ->where('a.payroll', 1)
                    ->where('a.service_credits', 0)
                    ->get();

                // Group by employee and calculate total OT pay
                // Daily rate from single source of truth: computeRateMetrics (Salary/22)
                foreach ($overtime_applications as $ot) {
                    $employee_id = $ot->employee_id;

                    if (!isset($overtime_pay_map[$employee_id])) {
                        $overtime_pay_map[$employee_id] = 0;
                    }

                    $salary = floatval($ot->salary ?? 0);
                    $total_hours = floatval($ot->total_hours ?? 0);
                    $rateMetrics = $this->computeRateMetrics($salary, $ot->setup_work_days ?? null, $ot->setup_work_hours ?? null);
                    $daily_rate = $rateMetrics['daily_rate'];
                    $total_minutes = round($total_hours * 60);
                    $day_fraction = \App\Helpers\Time_Calculation::minutesToDayFraction($total_minutes);
                    $ot_pay = ($daily_rate * $day_fraction) * floatval($ot->overtime_rate ?? 0);

                    $overtime_pay_map[$employee_id] += $ot_pay;
                }
            }

            // Get time_data_summary records for this payroll period to use saved values
            $summaryRecords = DB::table('time_data_summary')
                ->where('Payroll_Period_ID', $payrollPeriodId)
                ->get()
                ->keyBy('Employee_ID');

            // Days present per employee: count distinct dates where work_hours > 0 OR is_ob = 1
            $daysPresentMap = DB::table('time_data')
                ->where('payroll_period_id', $payrollPeriodId)
                ->whereIn('employee_id', $employee_ids)
                ->select('employee_id', DB::raw("COUNT(DISTINCT CASE WHEN (COALESCE(work_hours, 0) > 0 OR is_ob = 1) THEN date END) as days_present"))
                ->groupBy('employee_id')
                ->pluck('days_present', 'employee_id');

            // Is_adjusted count per employee: distinct dates with is_adjusted = 1, excluding schedule/adjusted
            // rest days (is_restday = 1) from the numeric count. Adjusted rest days still appear in
            // is_adjusted_dates in view() for the period summary label.
            $isAdjustedCountMap = DB::table('time_data')
                ->where('payroll_period_id', $payrollPeriodId)
                ->whereIn('employee_id', $employee_ids)
                ->where('is_adjusted', 1)
                ->select(
                    'employee_id',
                    DB::raw('COUNT(DISTINCT CASE WHEN ISNULL(is_restday, 0) = 0 THEN date END) as is_adjusted_count')
                )
                ->groupBy('employee_id')
                ->pluck('is_adjusted_count', 'employee_id');

            // Employees with any time_data row in the period where remarks contains 'Absent' (for "Employees with Absences" bucket)
            $employeeIdsWithAbsentRemark = [];
            if (!empty($employee_ids)) {
                $employeeIdsWithAbsentRemark = DB::table('time_data')
                    ->where('payroll_period_id', $payrollPeriodId)
                    ->whereIn('employee_id', $employee_ids)
                    ->whereNotNull('remarks')
                    ->where('remarks', 'LIKE', '%Absent%')
                    ->distinct()
                    ->pluck('employee_id')
                    ->toArray();
            }

            $calculatedTotalsByEmployee = !empty($employee_ids)
                ? $this->calculateTimeDataTotalsForEmployees($employee_ids, $payrollPeriodId)
                : [];

            $holidaysInPeriod = collect();
            $timeDataByEmployeeDate = [];
            $leavePaidDaysByEmployee = [];
            if (!$useSummaryList && !empty($employee_ids) && $from_date && $to_date) {
                $holidaysInPeriod = DB::table('holidays as a')
                    ->leftJoin('holiday_types as b', 'b.id', '=', 'a.holiday_type')
                    ->select(
                        'a.date',
                        'b.rate as holiday_rate',
                        'b.absent_with_pay'
                    )
                    ->whereBetween('a.date', [$from_date, $to_date])
                    ->where('a.active', 1)
                    ->get();

                $tdHolidayRows = DB::table('time_data')
                    ->where('payroll_period_id', $payrollPeriodId)
                    ->whereIn('employee_id', $employee_ids)
                    ->select('employee_id', 'date', 'absent', 'am_in', 'pm_in', 'work_hours')
                    ->get();

                foreach ($tdHolidayRows as $td) {
                    $d = $td->date instanceof \DateTimeInterface
                        ? $td->date->format('Y-m-d')
                        : substr((string) $td->date, 0, 10);
                    $eid = (int) $td->employee_id;
                    $timeDataByEmployeeDate[$eid][$d] = $td;
                }

                $leaveRowsBulk = DB::table('leave_headers as a')
                    ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                    ->whereIn('a.employee_id', $employee_ids)
                    ->whereBetween('b.leave_date', [$from_date, $to_date])
                    ->where('a.approved', 1)
                    ->where('a.approved_2', 1)
                    ->where('a.approved_3', 1)
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('a.disapproved', 0)->orWhereNull('a.disapproved');
                        })
                            ->where(function ($q2) {
                                $q2->where('a.disapproved_2', 0)->orWhereNull('a.disapproved_2');
                            })
                            ->where(function ($q2) {
                                $q2->where('a.disapproved_3', 0)->orWhereNull('a.disapproved_3');
                            });
                    })
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('a.is_cancel', 0)->orWhereNull('a.is_cancel');
                        })
                            ->where(function ($q2) {
                                $q2->where('a.is_cancel_2', 0)->orWhereNull('a.is_cancel_2');
                            })
                            ->where(function ($q2) {
                                $q2->where('a.is_cancel_3', 0)->orWhereNull('a.is_cancel_3');
                            });
                    })
                    ->select('a.employee_id', 'b.with_pay')
                    ->get();

                foreach ($leaveRowsBulk as $lr) {
                    if (floatval($lr->with_pay ?? 0) == 1.00) {
                        $eid = (int) $lr->employee_id;
                        $leavePaidDaysByEmployee[$eid] = ($leavePaidDaysByEmployee[$eid] ?? 0) + 1;
                    }
                }
            }

            $defaultCalculatedTotals = [
                'late' => 0,
                'undertime' => 0,
                'absent' => 0,
                'absent_raw' => 0,
                'late_offset' => 0,
                'undertime_offset' => 0,
                'absent_offset' => 0,
                'work_hours' => 0,
            ];

            // Add is_processed field and calculate total_amount for each employee record
            foreach ($data as $employee) {
                $summaryRecord = $summaryRecords[$employee->employee_id] ?? null;

                if ($useSummaryList && $summaryRecord) {
                    $employee->is_processed = 1;

                    $calculatedTotals = $calculatedTotalsByEmployee[$employee->employee_id] ?? $defaultCalculatedTotals;

                    $rateMetrics = $this->computeRateMetrics(
                        floatval($employee->salary ?? 0),
                        $employee->setup_work_days ?? null,
                        $employee->setup_work_hours ?? null
                    );
                    $daily_rate_unrounded = $rateMetrics['daily_rate'];
                    $daily_rate = round($daily_rate_unrounded, 2);
                    $hourly_rate = $rateMetrics['hourly_rate'];

                    $total_work_hours = floatval($summaryRecord->Hours_Worked ?? $summaryRecord->Working_Hours ?? $summaryRecord->Work_Hours ?? 0);
                    $overtime_pay = floatval($summaryRecord->Overtime ?? 0);

                    $employee->days_covered = intval($summaryRecord->Days_Covered ?? 0);

                    $lateOffsetFraction = $calculatedTotals['late_offset'];
                    $undertimeOffsetFraction = $calculatedTotals['undertime_offset'];
                    $absentOffsetFraction = $calculatedTotals['absent_offset'];

                    $baseLateFraction = floatval($summaryRecord->Late ?? 0);
                    $baseUndertimeFraction = floatval($summaryRecord->Undertime ?? 0);
                    $baseAbsentDays = floatval($summaryRecord->Absent ?? 0);

                    $lateMinutesNet = \App\Helpers\Time_Calculation::dayFractionToMinutes($baseLateFraction);
                    $undertimeMinutesNet = \App\Helpers\Time_Calculation::dayFractionToMinutes($baseUndertimeFraction);
                    $lateOffsetMinutes = \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($lateOffsetFraction ?? 0));
                    $undertimeOffsetMinutes = \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($undertimeOffsetFraction ?? 0));

                    $lateMinutesGross = $lateMinutesNet + $lateOffsetMinutes;
                    $undertimeMinutesGross = $undertimeMinutesNet + $undertimeOffsetMinutes;

                    $late_amount = floatval($summaryRecord->Late_Amount ?? 0);
                    $undertime_amount = floatval($summaryRecord->Undertime_Amount ?? 0);
                    $absent_amount = floatval($summaryRecord->Absent_Amount ?? 0);

                    $gross_pay = $hourly_rate * $total_work_hours;

                    $is_adjusted_count = (int)($isAdjustedCountMap[$employee->employee_id] ?? 0);
                    $employee->is_adjusted_count = $is_adjusted_count;

                    $eid = $employee->employee_id;
                    $flags = $flagsByEmployee->get($eid)
                        ?? $flagsByEmployee->get((string) $eid)
                        ?? $flagsByEmployee->get((int) $eid);

                    $employee->total_late_offset = round($lateOffsetMinutes / 60, 4);
                    $employee->total_undertime_offset = round($undertimeOffsetMinutes / 60, 4);
                    $employee->total_absent_offset = round($absentOffsetFraction, 3);

                    $grossLateDayFraction = \App\Helpers\Time_Calculation::minutesToDayFraction($lateMinutesGross);
                    $grossUndertimeDayFraction = \App\Helpers\Time_Calculation::minutesToDayFraction($undertimeMinutesGross);
                    $grossAbsentDays = $baseAbsentDays + $absentOffsetFraction;

                    $employee->total_late_raw = round($grossLateDayFraction, 3);
                    $employee->total_undertime_raw = round($grossUndertimeDayFraction, 3);
                    $employee->total_absent_before_offset = round($grossAbsentDays, 3);

                    $employee->total_amount = round(floatval($summaryRecord->Total_Amount ?? 0), 2);
                    $employee->ot_pay = round($overtime_pay, 2);

                    $employee->daily_rate = round($daily_rate, 2);
                    $employee->late_amount = round($late_amount, 2);
                    $employee->undertime_amount = round($undertime_amount, 2);
                    $employee->absent_amount = round($absent_amount, 2);

                    $employee->gross_pay_unrounded = $gross_pay;
                    $employee->daily_rate_unrounded = $daily_rate;
                    $employee->hourly_rate_unrounded = $hourly_rate;
                    $employee->late_amount_unrounded = $late_amount;
                    $employee->undertime_amount_unrounded = $undertime_amount;
                    $employee->absent_amount_unrounded = $absent_amount;

                    $employee->total_late = $lateMinutesNet / 60.0;
                    $employee->total_undertime = $undertimeMinutesNet / 60.0;

                    $employee->total_late_day_fraction = $baseLateFraction;
                    $employee->total_undertime_day_fraction = $baseUndertimeFraction;

                    $employee->total_absent = round($baseAbsentDays, 3);
                    $employee->total_absent_raw = floatval($calculatedTotals['absent_raw'] ?? 0);
                    $employee->has_remark_absent = in_array($employee->employee_id, $employeeIdsWithAbsentRemark) ? 1 : 0;
                    $employee->total_work_hours = $total_work_hours;

                    $employee->has_any_late_record = $flags ? (int) $flags->has_any_late_record : 0;
                    $employee->has_any_undertime_record = $flags ? (int) $flags->has_any_undertime_record : 0;
                    $employee->has_any_late_offset_record = $flags ? (int) $flags->has_any_late_offset_record : 0;
                    $employee->has_any_undertime_offset_record = $flags ? (int) $flags->has_any_undertime_offset_record : 0;
                    $employee->has_any_absent_record = $flags ? (int) $flags->has_any_absent_record : 0;
                    $employee->has_any_absent_offset_record = 0;

                    continue;
                }

                $employee->is_processed = in_array($employee->employee_id, $processedEmployees) ? 1 : 0;

                $rateMetrics = $this->computeRateMetrics(
                    floatval($employee->salary ?? 0),
                    $employee->setup_work_days ?? null,
                    $employee->setup_work_hours ?? null
                );

                // Get unrounded daily_rate for calculation (Salary / 22)
                $daily_rate_unrounded = $rateMetrics['daily_rate'];
                $daily_rate = round($daily_rate_unrounded, 2); // Rounded for display only
                $hourly_rate = $rateMetrics['hourly_rate'];

                // ALWAYS calculate current totals from time_data logs to ensure accuracy
                // This prevents stale/corrupted values in time_data_summary from persisting after reprocess
                $calculatedTotals = $calculatedTotalsByEmployee[$employee->employee_id] ?? $defaultCalculatedTotals;

                if ($summaryRecord) {
                    // Use saved values from time_data_summary for work metadata and manually entered pay
                    $total_work_hours = floatval($summaryRecord->Hours_Worked ?? $summaryRecord->Working_Hours ?? $summaryRecord->Work_Hours ?? 0);
                    $overtime_pay = floatval($summaryRecord->Overtime ?? 0);

                    // IMPORTANT: Use saved days_covered from time_data_summary to ensure consistency
                    $employee->days_covered = intval($summaryRecord->Days_Covered ?? $employee->days_covered ?? 0);
                } else {
                    // No saved data - use calculated work hours and overtime pay map
                    $total_work_hours = $calculatedTotals['work_hours'];
                    $overtime_pay = floatval($overtime_pay_map[$employee->employee_id] ?? 0);
                    $employee->days_covered = intval($employee->days_covered ?? 0);
                }

                // Base fractions (net tardiness remaining in time_data after any offsets on each row)
                $baseLateFraction = $calculatedTotals['late'];        // net late (day fraction)
                $baseUndertimeFraction = $calculatedTotals['undertime']; // net undertime (day fraction)
                // calculateTimeDataTotals() already excludes leave, holiday, OB, and rest days from absent count.
                // Do NOT subtract "absent on absent_with_pay holidays" here — that would double-exclude and
                // incorrectly zero out non-holiday absences (e.g. absent on a regular work day).
                $baseAbsentDays = $calculatedTotals['absent'];        // net absent days

                // Offset fractions from time_data (day fractions)
                $lateOffsetFraction = $calculatedTotals['late_offset'];           // portion offsetted via VL, etc.
                $undertimeOffsetFraction = $calculatedTotals['undertime_offset'];
                $absentOffsetFraction = $calculatedTotals['absent_offset'];

                // Convert base and offset fractions to minutes for accurate aggregation
                $lateMinutesNet = \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($baseLateFraction ?? 0));
                $undertimeMinutesNet = \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($baseUndertimeFraction ?? 0));
                $lateOffsetMinutes = \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($lateOffsetFraction ?? 0));
                $undertimeOffsetMinutes = \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($undertimeOffsetFraction ?? 0));

                // Gross (before offset) = net + offset, per CSC dayfraction table
                $lateMinutesGross = $lateMinutesNet + $lateOffsetMinutes;
                $undertimeMinutesGross = $undertimeMinutesNet + $undertimeOffsetMinutes;

                // Net values (used for deduction amounts) come directly from base fractions
                $late_minutes_for_display = $lateMinutesNet;
                $undertime_minutes_for_display = $undertimeMinutesNet;
                $total_absent_days = $baseAbsentDays;

                $late_day_fraction = $baseLateFraction;
                $undertime_day_fraction = $baseUndertimeFraction;

                // Calculate deduction amounts using unrounded daily rate and day fractions (NO ROUNDING)
                $late_amount = $late_day_fraction * $daily_rate_unrounded;
                $undertime_amount = $undertime_day_fraction * $daily_rate_unrounded;
                $absent_amount = $total_absent_days * $daily_rate_unrounded;

                // Days present (same as Attendance Details modal) for consistent Total Amount
                $daysPresent = (int)($daysPresentMap[$employee->employee_id] ?? 0);

                // Calculate gross pay (for display/reference only; total_amount uses days_present formula)
                $gross_pay = $hourly_rate * $total_work_hours;

                // Calculate holiday pay and leave pay for this employee (same logic as view() method)
                // Days with holiday pay must not be included in Days_Present (same rule as time_data_summary_adj).
                $total_holiday_pay = 0;
                $paid_holiday_days_count = 0;
                $total_leave_pay = 0;

                if ($from_date && $to_date) {
                    $empIdForHoliday = (int) $employee->employee_id;
                    $empTdByDate = $timeDataByEmployeeDate[$empIdForHoliday] ?? [];

                    foreach ($holidaysInPeriod as $holiday) {
                        $holiday_rate = floatval($holiday->holiday_rate ?? 0);
                        $absent_with_pay_raw = $holiday->absent_with_pay ?? null;
                        $absent_with_pay_bool = false;
                        if ($absent_with_pay_raw !== null) {
                            $absent_with_pay_bool = is_bool($absent_with_pay_raw) ? $absent_with_pay_raw : (bool) intval($absent_with_pay_raw);
                        }

                        $hDate = $holiday->date instanceof \DateTimeInterface
                            ? $holiday->date->format('Y-m-d')
                            : substr((string) $holiday->date, 0, 10);
                        $td = $empTdByDate[$hDate] ?? null;

                        $is_present = $td && (!empty($td->am_in) || !empty($td->pm_in));
                        $is_absent = $td ? (floatval($td->absent ?? 0) > 0) : false;

                        if ($absent_with_pay_bool === true) {
                            // absent_with_pay = 1: Do NOT insert into Holiday_Pay. Amount is already in Overtime (overtime_type_id = 4 "Holiday Overtime") via overtime_application; OT_Pay insert is correct. Not considered Holiday_Pay.
                            // (Previously: daily_rate + hourly×work_hours was added here — commented out; that pay is in OT_Pay instead.)
                        } elseif ($holiday_rate > 0 && $absent_with_pay_bool === false && $is_present && !$is_absent) {
                            $total_holiday_pay += $daily_rate_unrounded * $holiday_rate;
                            $paid_holiday_days_count++;
                        }
                    }

                    $total_leave_pay = ($leavePaidDaysByEmployee[$empIdForHoliday] ?? 0) * $daily_rate_unrounded;
                }

                // Is_adjusted count: assumed perfect attendance days (validation ONLY is_adjusted = 1)
                $is_adjusted_count = (int)($isAdjustedCountMap[$employee->employee_id] ?? 0);
                $is_adjusted_amount = $is_adjusted_count * $daily_rate_unrounded;
                // Expose for frontend "Days Present" display.
                $employee->is_adjusted_count = $is_adjusted_count;

                // Days_Present excludes days that were paid as holiday (no double-counting; same rule as time_data_summary_adj).
                $daysPresentExclHoliday = max(0, $daysPresent - $paid_holiday_days_count);

                // Calculate total amount using SAME formula as UI (Attendance Details modal):
                // (Daily Rate × Days Present excl. holiday) - Late - Undertime + OT + Holiday + Leave + is_adjusted - Preceding Period Adj
                // Preceding Period Adj: deduct Adjustment_Amount (Late+Undertime+Absent), add Adjustment_Amount_OT_Holiday (OT+Holiday). Net = (467.88 − 856.71) = −388.83.
                $adj_amount = floatval($employee->adjustment_amount ?? 0);
                $adj_ot_holiday = floatval($employee->adjustment_amount_ot_holiday ?? 0);
                $total_amount = max(0, $daily_rate_unrounded * $daysPresentExclHoliday
                    - $late_amount
                    - $undertime_amount
                    + $overtime_pay
                    + round($total_holiday_pay, 2)
                    + round($total_leave_pay, 2)
                    + $is_adjusted_amount
                    - $adj_amount
                    + $adj_ot_holiday);

                // Prepare offset values for UI display (decimal hours / days)
                $employee->total_late_offset = round($lateOffsetMinutes / 60, 4);          // hours offsetted
                $employee->total_undertime_offset = round($undertimeOffsetMinutes / 60, 4); // hours offsetted
                $employee->total_absent_offset = round($absentOffsetFraction, 3);          // days offsetted

                // Raw (before offset) values for UI display — gross = net + offset
                $grossLateDayFraction = \App\Helpers\Time_Calculation::minutesToDayFraction($lateMinutesGross);
                $grossUndertimeDayFraction = \App\Helpers\Time_Calculation::minutesToDayFraction($undertimeMinutesGross);
                $grossAbsentDays = $baseAbsentDays + $absentOffsetFraction;

                $employee->total_late_raw = round($grossLateDayFraction, 3);
                $employee->total_undertime_raw = round($grossUndertimeDayFraction, 3);
                $employee->total_absent_before_offset = round($grossAbsentDays, 3);

                // Only round the final total_amount to 2 decimals
                $employee->total_amount = round($total_amount, 2);
                $employee->ot_pay = round($overtime_pay, 2);

                // Store computed values (rounded for display)
                $employee->daily_rate = round($daily_rate, 2);
                $employee->late_amount = round($late_amount, 2);
                $employee->undertime_amount = round($undertime_amount, 2);
                $employee->absent_amount = round($absent_amount, 2);

                // Store unrounded values for accurate calculation (round only for display)
                $employee->gross_pay_unrounded = $gross_pay;
                $employee->daily_rate_unrounded = $daily_rate;
                $employee->hourly_rate_unrounded = $hourly_rate;
                $employee->late_amount_unrounded = $late_amount;
                $employee->undertime_amount_unrounded = $undertime_amount;
                $employee->absent_amount_unrounded = $absent_amount;

                // Convert late/undertime totals (minutes) to decimal hours for UI display
                $late_decimal_hours = $late_minutes_for_display / 60.0;
                $undertime_decimal_hours = $undertime_minutes_for_display / 60.0;

                // Update the totals from time_data
                // For UI display: use decimal hours
                $employee->total_late = $late_decimal_hours; // Decimal hours for UI display
                $employee->total_undertime = $undertime_decimal_hours; // Decimal hours for UI display

                // For storage/save: provide day fractions (CSC compliance)
                $employee->total_late_day_fraction = $late_day_fraction; // Day fraction for storage
                $employee->total_undertime_day_fraction = $undertime_day_fraction; // Day fraction for storage

                $employee->total_absent = round($total_absent_days, 3);
                // Raw absent (all days with absent > 0, including on holidays). For display/reference only; "Complete Attendance" uses total_absent (net, same as Total Amount).
                $employee->total_absent_raw = floatval($calculatedTotals['absent_raw'] ?? 0);
                // Flag: 1 if any time_data in period has remarks containing 'Absent' — used so "Employees with Absences" includes them even when net total_absent is 0 (e.g. rest day).
                $employee->has_remark_absent = in_array($employee->employee_id, $employeeIdsWithAbsentRemark) ? 1 : 0;
                $employee->total_work_hours = $total_work_hours;
            }

            // Calculate pagination info
            $from = $total === 0 ? 0 : $offset + 1;
            $to = min($offset + $perPage, $total);

            $pagination = [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => $total,
                'from' => $from,
                'to' => $to,
                'last_page' => ceil($total / $perPage)
            ];

            return $this->successResponse([
                'data' => $data,
                'pagination' => $pagination
            ], 'Employee attendance data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee attendance data: ' . $e->getMessage());
        } finally {
            $durationMs = round((microtime(true) - $startedAt) * 1000);
            \Log::info('[ProcessAttendance][TIMING] getEmployeeAttendanceData END', [
                'call_id' => $callId,
                'duration_ms' => $durationMs,
            ]);
        }
    }

    /**
     * Sync time_data.payroll_period_id values for the selected payroll period.
     * This is used when viewing processed attendance to make sure overlapping
     * time data rows are assigned to the currently selected payroll period.
     */
    public function syncTimeDataPayrollPeriod(Request $request)
    {
        $callId = uniqid('pa_sync_', true);
        $startedAt = microtime(true);
        $updatedCount = null;

        \Log::info('[ProcessAttendance][TIMING] syncTimeDataPayrollPeriod START', [
            'call_id' => $callId,
            'payroll_period_id' => $request->input('payroll_period_id'),
            'employee_id' => $request->input('employee_id'),
            'userId' => auth()->id(),
        ]);

        try {
            $validated = $request->validate([
                'payroll_period_id' => 'required|integer|min:1',
                'employee_id' => 'nullable|integer|min:1'
            ], [
                'payroll_period_id.required' => 'Payroll period ID is required',
                'payroll_period_id.integer' => 'Payroll period ID must be an integer value',
                'employee_id.integer' => 'Employee ID must be an integer value',
            ]);

            $payroll_period_id = (int) $validated['payroll_period_id'];
            $employee_id = $validated['employee_id'] ?? null;

            $payrollPeriod = DB::table('payroll_periods')
                ->select('id', 'attendance_start_date', 'attendance_end_date')
                ->where('id', $payroll_period_id)
                ->first();

            if (!$payrollPeriod) {
                return $this->errorResponse("Payroll period not found (ID: {$payroll_period_id}).");
            }

            if (!$payrollPeriod->attendance_start_date || !$payrollPeriod->attendance_end_date) {
                return $this->errorResponse('Payroll period attendance dates are incomplete. Please configure the payroll period properly.');
            }

            $philippineTime = Carbon::now('Asia/Manila');
            $allowedEmploymentTypeIds = $this->getPayrollPeriodEmploymentTypeIds($payroll_period_id);

            $query = DB::table('time_data as td')
                ->join('employees as e', 'e.id', '=', 'td.employee_id')
                ->whereBetween('td.date', [$payrollPeriod->attendance_start_date, $payrollPeriod->attendance_end_date]);
            $this->applyPayrollPeriodEmploymentTypeScope($query, $allowedEmploymentTypeIds, 'e.employment_type_id');

            if ($employee_id) {
                $query->where('td.employee_id', $employee_id);
            }

            $updatedCount = $query->update([
                'td.payroll_period_id' => $payroll_period_id,
                'td.updated_at' => $philippineTime
            ]);


            return $this->successResponse([
                'updated_rows' => $updatedCount,
                'payroll_period_id' => $payroll_period_id,
                'attendance_start_date' => $payrollPeriod->attendance_start_date,
                'attendance_end_date' => $payrollPeriod->attendance_end_date,
                'employee_id' => $employee_id
            ], $updatedCount > 0 ? 'Time data rows synchronized to selected payroll period.' : 'No time data rows required updating for this payroll period.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to synchronize time data payroll period: ' . $e->getMessage());
        } finally {
            $durationMs = round((microtime(true) - $startedAt) * 1000);
            \Log::info('[ProcessAttendance][TIMING] syncTimeDataPayrollPeriod END', [
                'call_id' => $callId,
                'duration_ms' => $durationMs,
                'updated_rows' => $updatedCount,
            ]);
        }
    }

    /**
     * Core processing logic for attendance - reusable for both process and reprocess
     *
     * @param int $payroll_period_id The payroll period ID
     * @param int|null $employee_id Optional employee ID for single employee reprocessing. If null, processes all employees.
     * @param bool $skipAlreadyProcessedCheck Whether to skip the "already processed" check (for reprocess)
     * @return array Returns an array with 'success', 'data', and 'message' keys
     */
    private function processAttendanceCore($payroll_period_id, $employee_id = null, $skipAlreadyProcessedCheck = false, $datesFilter = null, $devDateOverride = null, $forceReprocess = false, ?string $processRunId = null)
    {
        // Get payroll period details
        $payroll_period = DB::table('payroll_periods')
            ->where('id', $payroll_period_id)
            ->first();

        if (!$payroll_period) {
            return [
                'success' => false,
                'message' => "Payroll period not found (ID: {$payroll_period_id}). Please select a valid payroll period.",
                'data' => null
            ];
        }

        $from_date = $payroll_period->attendance_start_date;
        $to_date = $payroll_period->attendance_end_date;
        $allowedEmploymentTypeIds = $this->getPayrollPeriodEmploymentTypeIds((int) $payroll_period_id);

        if (!$from_date || !$to_date) {
            return [
                'success' => false,
                'message' => "Invalid payroll period dates. The payroll period (ID: {$payroll_period_id}) is missing required date information.",
                'data' => null
            ];
        }

        if ($error = $this->validatePayrollPeriodEmploymentTypes((int) $payroll_period_id, $allowedEmploymentTypeIds)) {
            return $error;
        }

        // Resolve in-scope employees before reassigning time_data or calling SP_ProcessTimeData.
        if ($employee_id !== null) {
            $employeeQuery = DB::table('employees')
                ->select('id', 'work_schedule_id')
                ->where('id', $employee_id)
                ->where('active', true)
                ->where('is_employee', true);

            $this->applyPayrollPeriodEmploymentTypeScope($employeeQuery, $allowedEmploymentTypeIds);

            $employee = $employeeQuery->first();

            if (!$employee) {
                return [
                    'success' => false,
                    'message' => "Employee not found, inactive, or not in the employment type(s) configured for this payroll period (ID: {$employee_id}).",
                    'data' => null,
                ];
            }

            $employees = collect([$employee]);
        } else {
            $employeesQuery = DB::table('employees')
                ->select('id', 'work_schedule_id')
                ->where([
                    'active' => true,
                    'is_employee' => true,
                ])
                ->where('work_schedule_id', '<>', 0);

            $this->applyPayrollPeriodEmploymentTypeScope($employeesQuery, $allowedEmploymentTypeIds);

            $employees = $employeesQuery->get();

            if ($employees->isEmpty()) {
                return [
                    'success' => false,
                    'message' => "No employees found to process attendance for the selected payroll period and employment type selection. Please ensure employees are active, tagged as employees, and assigned schedules (work_schedule_id > 0).",
                    'data' => null
                ];
            }
        }

        $scopedEmployeeIds = $employees->pluck('id')->map(function ($id) {
            return (int) $id;
        })->values();

        // Reassign payroll_period_id only for in-scope employees in this period's attendance range.
        $philippineTime = \Carbon\Carbon::now('Asia/Manila');

        if ($employee_id !== null) {
            $reassignedCount = DB::table('time_data')
                ->where('employee_id', $employee_id)
                ->whereBetween('date', [$from_date, $to_date])
                ->update([
                    'payroll_period_id' => $payroll_period_id,
                    'updated_at' => $philippineTime
                ]);
        } else {
            $reassignedCount = 0;
            if ($scopedEmployeeIds->isNotEmpty()) {
                $reassignedCount = DB::table('time_data')
                    ->whereIn('employee_id', $scopedEmployeeIds->all())
                    ->whereBetween('date', [$from_date, $to_date])
                    ->update([
                        'payroll_period_id' => $payroll_period_id,
                        'updated_at' => $philippineTime,
                    ]);
            }
        }

        // Check if attendance data has already been processed (only for bulk process, skip for reprocess)
        if (!$skipAlreadyProcessedCheck) {
            $existingRecords = DB::table('time_data_summary')
                ->where('Payroll_Period_ID', $payroll_period_id)
                ->count();

            if ($existingRecords > 0) {
                return [
                    'success' => true,
                    'already_processed' => true,
                    'message' => 'Attendance data has already been processed for this payroll period. Payroll period IDs have been reassigned for overlapping dates. Displaying existing data.',
                    'data' => [
                        'already_processed' => true,
                        'existing_records_count' => $existingRecords,
                        'reassigned_records' => $reassignedCount
                    ]
                ];
            }
        }

        // Ensure dates are properly formatted as Carbon instances
        $startDate = Carbon::parse($from_date)->startOfDay();
        $endDate = Carbon::parse($to_date)->startOfDay();

        // Validate date range
        if ($startDate->gt($endDate)) {
            return [
                'success' => false,
                'message' => 'Invalid date range: attendance_start_date cannot be greater than attendance_end_date',
                'data' => null
            ];
        }

        $totalDates = $startDate->diffInDays($endDate) + 1; // +1 to include both start and end dates
        $processedDates = 0;
        $failedDates = [];
        $failedRemarksDates = [];


        // Build list of dates to process
        $datesToIterate = [];
        if (is_array($datesFilter) && !empty($datesFilter)) {
            // Use provided dates only, but clamp to period range
            $unique = array_values(array_unique(array_map(function ($d) {
                return (string)$d;
            }, $datesFilter)));
            foreach ($unique as $d) {
                try {
                    $cd = Carbon::parse($d)->startOfDay();
                    if (!$cd->lt($startDate) && !$cd->gt($endDate)) {
                        $datesToIterate[] = $cd->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                    // skip invalid date
                }
            }
            sort($datesToIterate);
        } else {
            // Full period
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $datesToIterate[] = $currentDate->format('Y-m-d');
                $currentDate->addDay();
            }
        }

        // Track if Step 4 (CreateAssumedRecords) has already run for this period.
        // Step 4 creates assumed records from @DateToProcess through period end, so we only need to run it ONCE.
        // Running it per-date causes massive slowdown (Step 4 runs 6+ times with heavy INSERTs/UPDATEs).
        $createAssumedRecordsAlreadyRun = false;

        // Calendar "today" vs processing "today" (must stay distinct):
        // - $realToday: actual calendar date in Asia/Manila — source of truth for wall-clock day when no override is in use.
        // - $effectiveToday: date used for payroll processing rules that depend on "current" day — pre-cutoff snapshot,
        //   CreateAssumedRecords (first date >= this), gap snapshots, etc.
        // When dev_date_override is sent, $effectiveToday follows it so development can simulate being on another day;
        // timestamps (e.g. updated_at) still use real clock time via Carbon::now(), not the override.
        $realToday = Carbon::now('Asia/Manila')->toDateString();
        $effectiveToday = $realToday;
        $parsedDevOverride = null;
        // $devOverrideEnabled = app()->environment(['local', 'testing']); // Development-only: uncomment to restrict Dev Date Override to local/testing
        // if (!$devOverrideEnabled && !empty($devDateOverride)) {
        //     // Safety: never allow overriding "today" outside dev/test.
        //     \Log::warning('[ProcessAttendance] Dev Date Override ignored (non-dev environment)', [
        //         'payroll_period_id' => $payroll_period_id,
        //         'employee_id' => $employee_id,
        //         'dev_date_override' => $devDateOverride,
        //         'app_env' => app()->environment(),
        //     ]);
        // }
        // if ($devOverrideEnabled && !empty($devDateOverride)) {
        if (!empty($devDateOverride)) {
            try {
                $parsedDevOverride = Carbon::parse($devDateOverride, 'Asia/Manila')->toDateString();
                $effectiveToday = $parsedDevOverride;
            } catch (\Throwable $e) {
                // Ignore invalid dev override; fall back to real current date
                $parsedDevOverride = null;
            }
        }

        // [PERF LOG] Process Attendance start
        $processStartTime = microtime(true);
        $this->updateAttendanceProcessRunProgress(
            $processRunId,
            'validating',
            null,
            0,
            count($datesToIterate),
            0,
            $scopedEmployeeIds->count(),
            'Validating attendance scope...'
        );
        \Log::info('[ProcessAttendance] START', [
            'payroll_period_id' => $payroll_period_id,
            'employee_id' => $employee_id,
            'date_range' => [$from_date, $to_date],
            'dates_count' => count($datesToIterate),
            'employees_count' => $employees->count(),
            'dev_date_override' => $devDateOverride,
            // real_today = calendar day in Asia/Manila; effective_today = processing "current" day (override or real)
            'real_today' => $realToday,
            'effective_today' => $effectiveToday,
            'processing_uses_override' => $effectiveToday !== $realToday,
            'dev_override_enabled' => !empty($devDateOverride),
        ]);

        // Process each date
        foreach ($datesToIterate as $dateIndex => $dateToProcess) {
            $dateLoopStart = microtime(true);
            $this->updateAttendanceProcessRunProgress(
                $processRunId,
                'calculating',
                $dateToProcess,
                ((int) $dateIndex) + 1,
                count($datesToIterate),
                0,
                $scopedEmployeeIds->count(),
                "Calculating attendance for date {$dateToProcess}"
            );


            // Update step increments for employees on this date
            foreach ($employees as $emp) {
                $this->updateNewStepIncrement($emp->id, $dateToProcess);
            }

            // When process date is effective "today" (or dev date override) and time_data already has am_in,
            // snapshot that row in time_data_adj and link time_data (time_data_adj_id, is_adjusted = 1).
            // If a time_data_adj row already exists for (employee_id, date, payroll_period_id), we update it
            // instead of inserting, so reprocess and dev-date-override runs do not hit duplicate key.
            if ($dateToProcess === $effectiveToday && Schema::hasTable('time_data_adj') && Schema::hasColumn('time_data', 'time_data_adj_id')) {
                \Log::info('[ProcessAttendance] Pre-cutoff snapshot trigger', [
                    'dateToProcess' => $dateToProcess,
                    'effective_today' => $effectiveToday,
                    'dev_date_override' => $devDateOverride,
                ]);
                $transferQuery = DB::table('time_data')
                    ->where('date', $dateToProcess)
                    ->whereNotNull('am_in')
                    ->whereNull('pm_out');
                if ($employee_id === null && $scopedEmployeeIds->isNotEmpty()) {
                    $transferQuery->whereIn('employee_id', $scopedEmployeeIds->all());
                }
                $transferQuery->where(function ($q) {
                    $q->whereNull('time_data_adj_id')->orWhere('time_data_adj_id', 0);
                });
                $rowsToTransfer = $transferQuery->get();
                $createdBy = $this->resolveProcessAttendanceUserId() ?? 0;
                $now = \Carbon\Carbon::now('Asia/Manila');
                foreach ($rowsToTransfer as $row) {
                    $periodId = (int) ($row->payroll_period_id ?? $payroll_period_id);
                    $existing = DB::table('time_data_adj')
                        ->where('employee_id', $row->employee_id)
                        ->where('date', $row->date)
                        ->where('payroll_period_id', $periodId)
                        ->first();

                    $snapshotPayload = [
                        'am_in' => $row->am_in,
                        'am_out' => $row->am_out ?? null,
                        'break_in' => $row->break_in ?? null,
                        'break_out' => $row->break_out ?? null,
                        'pm_in' => $row->pm_in ?? null,
                        'pm_out' => $row->pm_out ?? null,
                        'work_hours' => $row->work_hours ?? 0,
                        'late' => $row->late ?? 0,
                        'undertime' => $row->undertime ?? 0,
                        'absent' => $row->absent ?? 0,
                        'leave' => $row->leave ?? 0,
                        'is_ob' => (int) ($row->is_ob ?? 0),
                        'ob_id' => $row->ob_id ?? null,
                        'ob_hours' => $row->ob_hours ?? 0,
                        'is_holiday' => (int) ($row->is_holiday ?? 0),
                        'holiday_id' => $row->holiday_id ?? null,
                        'holiday_type_id' => $row->holiday_type_id ?? null,
                        'holiday_pay' => $row->holiday_pay ?? 0,
                        'is_ot' => (int) ($row->is_ot ?? 0),
                        'ot_id' => $row->ot_id ?? null,
                        'ot_hours' => $row->ot_hours ?? 0,
                        'ot_pay' => $row->ot_pay ?? 0,
                        'overtime_type_id' => $row->overtime_type_id ?? null,
                        'nd_hours' => $row->nd_hours ?? 0,
                        'nd_pay' => $row->nd_pay ?? 0,
                        'nd_rate' => $row->nd_rate ?? 0,
                        'nd_start' => $row->nd_start ?? null,
                        'nd_end' => $row->nd_end ?? null,
                        'applied_offset' => $row->applied_offset ?? 0,
                        'late_offset' => $row->late_offset ?? 0,
                        'undertime_offset' => $row->undertime_offset ?? 0,
                        'absent_offset' => $row->absent_offset ?? 0,
                        'excess_hours' => $row->excess_hours ?? 0,
                        'is_restday' => (int) ($row->is_restday ?? 0),
                        'is_shifting' => (int) ($row->is_shifting ?? 0),
                        'is_wfh' => (int) ($row->is_wfh ?? 0),
                        'lwop' => (int) ($row->lwop ?? 0),
                        'for_approval' => (int) ($row->for_approval ?? 0),
                        'is_edited' => (int) ($row->is_edited ?? 0),
                        'is_assumed' => (int) ($row->is_assumed ?? 0),
                        'assumption_reason' => $row->assumption_reason ?? null,
                        'work_schedule_id' => $row->work_schedule_id ?? null,
                        'dtr_request_id' => $row->dtr_request_id ?? null,
                        'wfh_reason' => $row->wfh_reason ?? null,
                        'wfh_location' => $row->wfh_location ?? null,
                        'wfh_approved_by' => $row->wfh_approved_by ?? null,
                        'wfh_approved_at' => $row->wfh_approved_at ?? null,
                        'manual_entry_source' => $row->manual_entry_source ?? null,
                        'entry_timestamp' => $row->entry_timestamp ?? null,
                        'attachment_name' => $row->attachment_name ?? null,
                        'path' => $row->path ?? null,
                        'extension' => $row->extension ?? null,
                        'remarks' => $row->remarks ?? null,
                        'note' => $row->note ?? null,
                        'source_time_data_id' => $row->id,
                        'target_payroll_period_id' => $payroll_period_id,
                        'adjustment_type' => 'PRE_CUTOFF_SNAPSHOT',
                        'status' => 'APPLIED',
                        'reason' => 'Pre-cutoff snapshot - process date is current date',
                        'updated_at' => $now,
                    ];

                    if ($existing) {
                        $adjId = $existing->id;
                        DB::table('time_data_adj')->where('id', $existing->id)->update($snapshotPayload);
                    } else {
                        $adjId = DB::table('time_data_adj')->insertGetId(array_merge($snapshotPayload, [
                            'employee_id' => $row->employee_id,
                            'payroll_period_id' => $periodId,
                            'date' => $row->date,
                            'created_by' => $createdBy,
                            'created_at' => $now,
                        ]));
                    }

                    DB::table('time_data')
                        ->where('id', $row->id)
                        ->update([
                            'time_data_adj_id' => $adjId,
                            'is_adjusted' => 1,
                            'updated_at' => \Carbon\Carbon::now('Asia/Manila'),
                        ]);
                }

                // Additionally, when we are at effective "today", create pre-cutoff snapshots for the GAP
                // between effective_today (exclusive) and the payroll period's attendance_end_date (inclusive),
                // excluding rest days. These gap snapshots are based on time_data rows that already exist
                // (typically assumed records created by SP_ProcessTimeData STEP 4).
                try {
                    if (!empty($to_date) && $effectiveToday < $to_date) {
                        $gapStart = Carbon::parse($effectiveToday, 'Asia/Manila')->addDay()->toDateString();
                        $gapEnd = Carbon::parse($to_date, 'Asia/Manila')->toDateString();

                        $gapQuery = DB::table('time_data')
                            ->whereBetween('date', [$gapStart, $gapEnd])
                            ->where('payroll_period_id', $payroll_period_id);

                        // If processing a single employee, restrict to that employee
                        if ($employee_id !== null) {
                            $gapQuery->where('employee_id', $employee_id);
                        } elseif ($scopedEmployeeIds->isNotEmpty()) {
                            $gapQuery->whereIn('employee_id', $scopedEmployeeIds->all());
                        }

                        // Exclude rows already linked to time_data_adj (same update-instead-of-insert logic applies if they were included)
                        $gapQuery->where(function ($q) {
                            $q->whereNull('time_data_adj_id')
                                ->orWhere('time_data_adj_id', 0);
                        });

                        // Exclude explicit rest days (we only want working days in the gap)
                        $gapQuery->where(function ($q) {
                            $q->whereNull('is_restday')
                                ->orWhere('is_restday', 0);
                        });

                        $gapRows = $gapQuery->get();
                        $gapCreated = 0;

                        foreach ($gapRows as $row) {
                            $periodId = (int) ($row->payroll_period_id ?? $payroll_period_id);
                            $existingGap = DB::table('time_data_adj')
                                ->where('employee_id', $row->employee_id)
                                ->where('date', $row->date)
                                ->where('payroll_period_id', $periodId)
                                ->first();

                            $gapPayload = [
                                'am_in' => $row->am_in,
                                'am_out' => $row->am_out ?? null,
                                'break_in' => $row->break_in ?? null,
                                'break_out' => $row->break_out ?? null,
                                'pm_in' => $row->pm_in ?? null,
                                'pm_out' => $row->pm_out ?? null,
                                'work_hours' => $row->work_hours ?? 0,
                                'late' => $row->late ?? 0,
                                'undertime' => $row->undertime ?? 0,
                                'absent' => $row->absent ?? 0,
                                'leave' => $row->leave ?? 0,
                                'is_ob' => (int) ($row->is_ob ?? 0),
                                'ob_id' => $row->ob_id ?? null,
                                'ob_hours' => $row->ob_hours ?? 0,
                                'is_holiday' => (int) ($row->is_holiday ?? 0),
                                'holiday_id' => $row->holiday_id ?? null,
                                'holiday_type_id' => $row->holiday_type_id ?? null,
                                'holiday_pay' => $row->holiday_pay ?? 0,
                                'is_ot' => (int) ($row->is_ot ?? 0),
                                'ot_id' => $row->ot_id ?? null,
                                'ot_hours' => $row->ot_hours ?? 0,
                                'ot_pay' => $row->ot_pay ?? 0,
                                'overtime_type_id' => $row->overtime_type_id ?? null,
                                'nd_hours' => $row->nd_hours ?? 0,
                                'nd_pay' => $row->nd_pay ?? 0,
                                'nd_rate' => $row->nd_rate ?? 0,
                                'nd_start' => $row->nd_start ?? null,
                                'nd_end' => $row->nd_end ?? null,
                                'applied_offset' => $row->applied_offset ?? 0,
                                'late_offset' => $row->late_offset ?? 0,
                                'undertime_offset' => $row->undertime_offset ?? 0,
                                'absent_offset' => $row->absent_offset ?? 0,
                                'excess_hours' => $row->excess_hours ?? 0,
                                'is_restday' => (int) ($row->is_restday ?? 0),
                                'is_shifting' => (int) ($row->is_shifting ?? 0),
                                'is_wfh' => (int) ($row->is_wfh ?? 0),
                                'lwop' => (int) ($row->lwop ?? 0),
                                'for_approval' => (int) ($row->for_approval ?? 0),
                                'is_edited' => (int) ($row->is_edited ?? 0),
                                'is_assumed' => (int) ($row->is_assumed ?? 0),
                                'assumption_reason' => $row->assumption_reason ?? null,
                                'work_schedule_id' => $row->work_schedule_id ?? null,
                                'dtr_request_id' => $row->dtr_request_id ?? null,
                                'wfh_reason' => $row->wfh_reason ?? null,
                                'wfh_location' => $row->wfh_location ?? null,
                                'wfh_approved_by' => $row->wfh_approved_by ?? null,
                                'wfh_approved_at' => $row->wfh_approved_at ?? null,
                                'manual_entry_source' => $row->manual_entry_source ?? null,
                                'entry_timestamp' => $row->entry_timestamp ?? null,
                                'attachment_name' => $row->attachment_name ?? null,
                                'path' => $row->path ?? null,
                                'extension' => $row->extension ?? null,
                                'remarks' => $row->remarks ?? null,
                                'note' => $row->note ?? null,
                                'source_time_data_id' => $row->id,
                                'target_payroll_period_id' => $payroll_period_id,
                                'adjustment_type' => 'PRE_CUTOFF_SNAPSHOT',
                                'status' => 'APPLIED',
                                'reason' => 'Pre-cutoff snapshot - future assumed date between effective_today and attendance_end_date',
                                'updated_at' => $now,
                            ];

                            if ($existingGap) {
                                $adjId = $existingGap->id;
                                DB::table('time_data_adj')->where('id', $existingGap->id)->update($gapPayload);
                            } else {
                                $adjId = DB::table('time_data_adj')->insertGetId(array_merge($gapPayload, [
                                    'employee_id' => $row->employee_id,
                                    'payroll_period_id' => $periodId,
                                    'date' => $row->date,
                                    'created_by' => $createdBy,
                                    'created_at' => $now,
                                ]));
                            }

                            DB::table('time_data')
                                ->where('id', $row->id)
                                ->update([
                                    'time_data_adj_id' => $adjId,
                                    'is_adjusted' => 1,
                                    'updated_at' => \Carbon\Carbon::now('Asia/Manila'),
                                ]);

                            $gapCreated++;
                        }

                        \Log::info('[ProcessAttendance] Pre-cutoff gap snapshots created', [
                            'effective_today' => $effectiveToday,
                            'gap_start' => $gapStart,
                            'gap_end' => $gapEnd,
                            'rows_created' => $gapCreated,
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('[ProcessAttendance] Pre-cutoff gap snapshot failed', [
                        'effective_today' => $effectiveToday,
                        'to_date' => $to_date ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Step 1: Call SP_ProcessTimeData scoped to selected employee set.
            try {
                // Pass CreateAssumedRecords = 1 only ONCE for the first date >= today.
                // Step 4 creates assumed records from @DateToProcess through period end - no need to run per-date.
                // Running per-date caused severe slowdown (Step 4 runs 6+ times with heavy INSERTs/UPDATEs).
                $createAssumedRecords = (!$createAssumedRecordsAlreadyRun && $dateToProcess >= $effectiveToday) ? 1 : 0;
                if ($createAssumedRecords === 1) {
                    $createAssumedRecordsAlreadyRun = true;
                }
                $spProcessStart = microtime(true);
                \Log::info('[ProcessAttendance] SP_ProcessTimeData START', [
                    'date' => $dateToProcess,
                    'CreateAssumedRecords' => $createAssumedRecords,
                    'effective_today' => $effectiveToday,
                    'dev_date_override' => $devDateOverride,
                    'scoped_employees_count' => $scopedEmployeeIds->count(),
                ]);
                foreach ($scopedEmployeeIds as $employeeIndex => $scopedEmployeeId) {
                    $this->updateAttendanceProcessRunProgress(
                        $processRunId,
                        'calculating',
                        $dateToProcess,
                        ((int) $dateIndex) + 1,
                        count($datesToIterate),
                        ((int) $employeeIndex) + 1,
                        $scopedEmployeeIds->count(),
                        "Calculating {$dateToProcess}: employee " . ((((int) $employeeIndex) + 1)) . ' of ' . $scopedEmployeeIds->count()
                    );
                    DB::statement("EXEC [dbo].[SP_ProcessTimeData] @DateToProcess = ?, @PayrollPeriodId = ?, @CreateAssumedRecords = ?, @ForceReprocess = ?, @EmployeeId = ?", [
                        $dateToProcess,
                        $payroll_period_id,
                        $createAssumedRecords,
                        $forceReprocess ? 1 : 0,
                        $scopedEmployeeId
                    ]);
                }
                $spProcessDuration = round((microtime(true) - $spProcessStart) * 1000);
                \Log::info('[ProcessAttendance] SP_ProcessTimeData END', [
                    'date' => $dateToProcess,
                    'duration_ms' => $spProcessDuration,
                ]);

                // Log how many records still have NULL values after SP execution
                $recordsStillNull = DB::table('time_data')
                    ->where('date', $dateToProcess)
                    ->where('payroll_period_id', $payroll_period_id)
                    ->whereIn('employee_id', $scopedEmployeeIds->all())
                    ->where(function ($query) {
                        $query->whereNull('work_hours')
                            ->orWhereNull('late')
                            ->orWhereNull('undertime');
                    })
                    ->count();

                if ($recordsStillNull > 0) {
                }

                // After SP execution, log the results for the employee to verify recalculation
                // Note: SP_ProcessTimeData handles WFH records (is_wfh = 1) and recalculates work_hours, late, undertime
                // based on WFH attendance rules (same as office attendance but with schedule enforcement)
                if ($employee_id !== null) {
                    $resultRows = DB::table('time_data')
                        ->where('employee_id', $employee_id)
                        ->where('date', $dateToProcess)
                        ->get();

                    foreach ($resultRows as $resultRow) {
                        $isWfh = $resultRow->is_wfh ?? 0;

                        // Check if recalculation seems correct
                        if ($resultRow->is_edited == 1 && $resultRow->work_hours == 8.00) {
                        }

                        // Log WFH-specific calculations
                        if ($isWfh == 1) {
                        }
                    }
                }
            } catch (\Exception $e) {
                $failedDates[] = $dateToProcess;
                // Continue to next date if time data processing fails
                continue;
            }

            // Step 1.5 and Step 1.6 intentionally removed:
            // SP_ProcessTimeData is the single source of truth for leave and holiday tagging.

            // Step 1.7: Work cancellations are now handled by SP_ProcessTimeData STEP 1e
            // The stored procedure handles:
            // 1. All employees with absent records on cancelled work dates: absent = 1 (unpaid), work_hours = 0
            // 2. Employees with approved vacation leave (leave_type_id=1) on cancelled work dates: absent = 0, leave = 1, work_hours = 0
            // No backend processing needed here - SP_ProcessTimeData handles it during STEP 1e

            // Step 2: Call SP_GenerateTimeRemarks to generate remarks after processing time data
            // Note: SP_GenerateTimeRemarks will handle remarks for holidays and work cancellations
            try {
                $spRemarksStart = microtime(true);
                DB::statement("EXEC [dbo].[SP_GenerateTimeRemarks] @DateToProcess = ?", [$dateToProcess]);
                $spRemarksDuration = round((microtime(true) - $spRemarksStart) * 1000);
                \Log::info('[ProcessAttendance] SP_GenerateTimeRemarks', [
                    'date' => $dateToProcess,
                    'duration_ms' => $spRemarksDuration,
                ]);
                $processedDates++;
            } catch (\Exception $e) {
                $failedRemarksDates[] = $dateToProcess;
                // Still count as processed since time data was processed successfully
                $processedDates++;
            }

            // Keep is_edited flag so users can see entries were manually edited

            $dateLoopDuration = round((microtime(true) - $dateLoopStart) * 1000);
            \Log::info('[ProcessAttendance] Date loop iteration', [
                'date' => $dateToProcess,
                'total_duration_ms' => $dateLoopDuration,
            ]);
        }

        $this->updateAttendanceProcessRunProgress(
            $processRunId,
            'completed',
            null,
            count($datesToIterate),
            count($datesToIterate),
            $scopedEmployeeIds->count(),
            $scopedEmployeeIds->count(),
            'Attendance calculation completed'
        );

        if (!empty($failedDates)) {
        }
        if (!empty($failedRemarksDates)) {
        }

        // Process Employee Leave Earned
        $attendance_from = $from_date;
        $attendance_to = $to_date;

        // Get employees for leave earned processing
        if ($employee_id !== null) {
            // Single employee - process leave earned for this employee only
            $employee_leave_earned = collect([(object)['id' => $employee_id]]);
        } else {
            // Bulk process - get all employees with time data in this payroll period
            $employeeLeaveEarnedQuery = DB::table('employees as a')
                ->join('time_data as b', 'a.id', '=', 'b.employee_id')
                ->select('a.id')
                ->where('b.payroll_period_id', $payroll_period_id);
            $this->applyPayrollPeriodEmploymentTypeScope($employeeLeaveEarnedQuery, $allowedEmploymentTypeIds, 'a.employment_type_id');
            $employee_leave_earned = $employeeLeaveEarnedQuery->distinct()->get();
        }

        $employeeLeaveEarnedLoopIdx = 0;
        foreach ($employee_leave_earned as $employee_earned) {
            $employeeLeaveEarnedLoopIdx++;
            if ($employeeLeaveEarnedLoopIdx % 25 === 0) {
            }
            $emp_id = $employee_earned->id;

            // Get timekeeping and tardiness data
            $totals = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    DB::raw("sum(a.leave) as leave"),
                    DB::raw("sum(a.absent) as absent"),
                    DB::raw("sum(a.late) as late"),
                    DB::raw("sum(a.undertime) as undertime"),
                )
                ->where([
                    'a.payroll_period_id' => $payroll_period_id,
                    'b.id' => $emp_id,
                    'b.active' => true,
                    'b.is_employee' => true
                ])
                ->groupBy('a.employee_id')
                ->first();

            $data_check = DB::table('employee_leave_earned')
                ->where([
                    'employee_id' => $emp_id,
                    'payroll_period_id' => $payroll_period_id
                ])
                ->first();

            // Process Leave Accrual start
            $from = Carbon::parse($attendance_from);
            $to = Carbon::parse($attendance_to);
            $calendar_days = $from->diffInDays($to) + 1;
            $absent = isset($totals->absent) ? $totals->absent : 0;
            $late = isset($totals->late) ? $totals->late : 0;
            $undertime = isset($totals->undertime) ? $totals->undertime : 0;
            $leave = isset($totals->leave) ? $totals->leave : 0;
            $month_id = $from->month;
            $year_id = $from->year;

            if ($absent < 0) {
                $absent = 0;
            }

            if ($late < 0) {
                $late = 0;
            }

            if ($undertime < 0) {
                $undertime = 0;
            }

            if ($leave < 0) {
                $leave = 0;
            }

            $total_present = ($calendar_days - ($late + $undertime + $absent));

            if ($total_present < 0) {
                $total_present = 0;
            }

            if ($total_present >= 30) {
                $vl = 1.250;
                $sl = 1.250;
            } else {
                $leave_earned_data = DB::table('leave_earnings')->where('days_present', $total_present)->first();

                if ($leave_earned_data) {
                    $leave_earned = $leave_earned_data->leave_earned;
                } else {
                    $leave_earned = 0;
                }

                $leave_earned_vl = DB::table('leave_types')->where('id', 16)->first();

                if ($leave_earned_vl) {
                    $vl = $leave_earned;
                } else {
                    $vl = 0;
                }

                $leave_earned_sl = DB::table('leave_types')->where('id', 3)->first();

                if ($leave_earned_sl) {
                    $sl = $leave_earned;
                } else {
                    $sl = 0;
                }
            }

            // If data exists, subtract previous values first
            if ($data_check) {
                $less_vl = $data_check->vl_earned;
                $less_sl = $data_check->sl_earned;

                // Less VL Earned
                DB::table('leave_credits')
                    ->where([
                        'leave_type_id' => 16,
                        'employee_id' => $emp_id
                    ])
                    ->update(['credits' => DB::raw("credits - $less_vl")]);

                // Less SL Earned
                DB::table('leave_credits')
                    ->where([
                        'leave_type_id' => 3,
                        'employee_id' => $emp_id
                    ])
                    ->update(['credits' => DB::raw("credits - $less_sl")]);

                $employee_leave_earned_id = $data_check->id;
            } else {
                $employee_leave_earned_id = DB::table('employee_leave_earned')->max('id') + 1;
                if (!$employee_leave_earned_id || $employee_leave_earned_id == 0) {
                    $employee_leave_earned_id = 1;
                }
            }

            // VL Earned
            DB::table('leave_credits')
                ->where([
                    'leave_type_id' => 16,
                    'employee_id' => $emp_id
                ])
                ->update(['credits' => DB::raw("credits + $vl")]);

            // SL Earned
            DB::table('leave_credits')
                ->where([
                    'leave_type_id' => 3,
                    'employee_id' => $emp_id
                ])
                ->update(['credits' => DB::raw("credits + $sl")]);

            $data_leave_earned = array(
                'employee_id' => $emp_id,
                'payroll_period_id' => $payroll_period_id,
                'month_id' => $month_id,
                'year_id' => $year_id,
                'vl_earned' => $vl,
                'sl_earned' => $sl,
                'absent' => $total_present
            );

            DB::unprepared('SET IDENTITY_INSERT employee_leave_earned ON');
            DB::table('employee_leave_earned')->updateOrInsert(['id' => $employee_leave_earned_id], $data_leave_earned);
            DB::unprepared('SET IDENTITY_INSERT employee_leave_earned OFF');
            // Process Leave Accrual end
        }

        $processTotalDuration = round((microtime(true) - $processStartTime) * 1000);
        \Log::info('[ProcessAttendance] END', [
            'payroll_period_id' => $payroll_period_id,
            'total_duration_ms' => $processTotalDuration,
            'total_duration_sec' => round($processTotalDuration / 1000, 2),
            'processed_dates' => $processedDates,
            'total_dates' => $totalDates,
            'failed_dates' => count($failedDates),
            'failed_remarks_dates' => count($failedRemarksDates),
        ]);

        return [
            'success' => true,
            'already_processed' => false,
            'message' => $employee_id !== null ? 'Successfully reprocessed attendance for employee!' : 'Successfully Process Attendance!',
            'data' => [
                'already_processed' => false,
                'processed_dates' => $processedDates,
                'total_dates' => $totalDates,
                'reassigned_records' => $reassignedCount ?? 0,
                'failed_dates' => $failedDates,
                'failed_remarks_dates' => $failedRemarksDates
            ]
        ];
    }

    public function process(Request $request)
    {
        set_time_limit(3600); // 1 hour timeout

        // Default path (UI/API): enqueue detached processing and return immediately.
        // Internal worker path sets run_async_worker=true to execute full sync logic below.
        if (!filter_var($request->input('run_async_worker', false), FILTER_VALIDATE_BOOLEAN)) {
            $validatedStart = $request->validate([
                'payroll_period_id' => 'required|integer|min:1',
                'payroll_interval_id' => 'nullable|integer|min:1',
                'preceding_payroll_period_id' => 'nullable|integer|min:1',
                'dev_date_override' => 'nullable|date',
                'process_run_id' => 'nullable|string|max:64',
            ]);

            $processRunId = $validatedStart['process_run_id'] ?? (string) Str::uuid();
            $this->upsertAttendanceProcessRun($processRunId, (int) $validatedStart['payroll_period_id'], null);
            $this->setAttendanceProcessRunStatus($processRunId, 'running');

            $this->launchDetachedAttendanceProcess([
                'task' => 'process',
                'payroll_period_id' => (int) $validatedStart['payroll_period_id'],
                'payroll_interval_id' => $validatedStart['payroll_interval_id'] ?? null,
                'preceding_payroll_period_id' => $validatedStart['preceding_payroll_period_id'] ?? null,
                'dev_date_override' => $validatedStart['dev_date_override'] ?? null,
                'process_run_id' => $processRunId,
                'run_async_worker' => true,
                'user_id' => $this->resolveProcessAttendanceUserId(),
            ]);

            return $this->successResponse([
                'process_run_id' => $processRunId,
                'queued' => true,
                'worker_build' => self::ATTENDANCE_WORKER_BUILD,
            ], 'Attendance processing started.');
        }

        try {
            $processRunId = null;

            // $devOverrideEnabled = app()->environment(['local', 'testing']); // Development-only: uncomment to restrict Dev Date Override to local/testing

            // Validate request with more specific rules
            $rules = [
                'payroll_period_id' => 'required|integer|min:1',
                'payroll_interval_id' => 'nullable|integer|min:1',
                'preceding_payroll_period_id' => 'nullable|integer|min:1',
                // Dev Date Override: overrides the effective "today" used by controller logic (pre-cutoff snapshot trigger + assumed record generation).
                // if ($devOverrideEnabled) { ... } // Development-only: wrap in this to restrict to local/testing
                'dev_date_override' => 'nullable|date',
                'process_run_id' => 'nullable|string|max:64',
            ];

            $validated = $request->validate($rules, [
                'payroll_period_id.required' => 'Payroll period ID is required',
                'payroll_period_id.integer' => 'Payroll period ID must be a valid integer',
                'payroll_interval_id.integer' => 'Payroll interval ID must be a valid integer',
                'preceding_payroll_period_id.integer' => 'Preceding payroll period ID must be a valid integer',
            ]);

            $payroll_period_id = $validated['payroll_period_id'];
            $requested_interval_id = $validated['payroll_interval_id'] ?? null;
            $preceding_payroll_period_id = $validated['preceding_payroll_period_id'] ?? null;
            $dev_date_override = $validated['dev_date_override'] ?? null; // was: $devOverrideEnabled ? ($validated['dev_date_override'] ?? null) : null;
            $processRunId = $validated['process_run_id'] ?? null;

            // Track the run so the UI can poll process progress.
            $this->upsertAttendanceProcessRun($processRunId, (int) $payroll_period_id, null);

            // if (!$devOverrideEnabled && !empty($request->input('dev_date_override'))) {
            //     \Log::warning('[ProcessAttendance] Dev Date Override ignored (non-dev environment)', [
            //         'payroll_period_id' => $payroll_period_id,
            //         'dev_date_override' => $request->input('dev_date_override'),
            //         'app_env' => app()->environment(),
            //     ]);
            // }

            if (!empty($dev_date_override)) {
                \Log::info('[ProcessAttendance] Dev Date Override request received', [
                    'payroll_period_id' => $payroll_period_id,
                    'dev_date_override' => $dev_date_override,
                ]);
            }

            // Log preceding period selection for debugging
            if ($preceding_payroll_period_id) {
                \Log::info('[ProcessAttendance] Preceding payroll period selected', [
                    'current_period' => $payroll_period_id,
                    'preceding_period' => $preceding_payroll_period_id
                ]);
            }

            // Get payroll period details for validation
            $payroll_period = DB::table('payroll_periods')
                ->where('id', $payroll_period_id)
                ->first();

            if (!$payroll_period) {
                return $this->errorResponse("Payroll period not found (ID: {$payroll_period_id}). Please select a valid payroll period.");
            }

            // Get payroll_interval_id from the payroll period (not from employees)
            $payroll_interval_id = $payroll_period->payroll_interval_id;

            if (!$payroll_interval_id || $payroll_interval_id == 0) {
                return $this->errorResponse("Payroll period does not have a valid payroll interval assigned. Please verify the payroll period configuration.");
            }

            // If payroll_interval_id was provided in request, validate it matches the payroll period's interval
            if ($requested_interval_id && $requested_interval_id != $payroll_interval_id) {
            }

            // Call core processing logic (process all employees)
            $result = $this->processAttendanceCore($payroll_period_id, null, false, null, $dev_date_override, false, $processRunId);

            // After core processing: create time_data_summary rows for the current period so reconciliation can update them
            if ($result['success'] && empty($result['already_processed'])) {
                try {
                    $this->bulkInsertTimeDataSummaryForPeriod($payroll_period_id, $processRunId);
                } catch (\Exception $e) {
                    \Log::error('[ProcessAttendance] bulkInsertTimeDataSummaryForPeriod failed', [
                        'payroll_period_id' => $payroll_period_id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->setAttendanceProcessRunStatus($processRunId, 'failed');

                    return $this->serverErrorResponse('Attendance processed but summary creation failed: ' . $e->getMessage());
                }
            }

            // Process time_data_adj for the preceding payroll period
            if ($preceding_payroll_period_id && $result['success']) {
                $forceReprocessAdj = filter_var($request->input('force_reprocess', false), FILTER_VALIDATE_BOOLEAN);
                $this->processTimeDataAdjForPrecedingPeriod($payroll_period_id, $preceding_payroll_period_id, $result, $forceReprocessAdj, $processRunId);
            }

            if (!$result['success']) {
                $this->setAttendanceProcessRunStatus($processRunId, 'failed');

                return $this->errorResponse($result['message']);
            }

            if ($result['already_processed']) {
                $this->setAttendanceProcessRunStatus($processRunId, 'completed');

                return $this->successResponse($result['data'], $result['message']);
            }

            $this->setAttendanceProcessRunStatus($processRunId, 'completed');
            return $this->successResponse($result['data'], $result['message']);
        } catch (ValidationException $e) {
            // Let Laravel handle validation exceptions (returns 422)
            throw $e;
        } catch (\RuntimeException $e) {
            $this->setAttendanceProcessRunStatus($processRunId, 'failed');
            return $this->serverErrorResponse('Failed to process attendance: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->setAttendanceProcessRunStatus($processRunId, 'failed');
            return $this->serverErrorResponse('Failed to process attendance: ' . $e->getMessage());
        }
    }

    public function view($id, $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Determine the first "adjusted" date for this employee + payroll period.
            // Any dates on or after this will be treated as part of the adjusted tail
            // (including rest days), and should be excluded from the current-period
            // Edit Times section.
            $firstAdjusted = DB::table('time_data')
                ->where('employee_id', $id)
                ->where('payroll_period_id', $payroll_period_id)
                ->where('is_adjusted', 1)
                ->orderBy('date', 'asc')
                ->value('date');

            // get daily time records
            // IMPORTANT for Edit Times UI:
            // - Only include current-period time_data rows where:
            //   - is_adjusted != 1 (exclude pre-cutoff snapshots), and
            //   - when a first adjusted date exists, date < firstAdjusted
            $dailyQuery = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('payroll_periods as e', 'e.id', '=', 'a.payroll_period_id')
                ->leftJoin('employment_types as f', 'f.id', '=', 'b.employment_type_id')
                ->select(
                    'a.id',
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    // Original (decrypting) name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                    //                CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                    //            ELSE
                    //                RTRIM(RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')))
                    //            END as name"),
                    // Replacement (non-decrypting):
                    DB::raw("CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name) as name"),
                    'a.date',
                    'a.am_in',
                    'a.am_out',
                    'a.break_in',
                    'a.break_out',
                    'a.pm_in',
                    'a.pm_out',
                    'a.work_hours',
                    'a.late',
                    'a.undertime',
                    'a.absent',
                    'a.leave',
                    'a.is_ob',
                    'a.ob_id',
                    'a.is_holiday',
                    'a.holiday_id',
                    'a.holiday_pay',
                    'a.is_ot',
                    'a.ot_id',
                    'a.ot_pay',
                    'a.nd_pay',
                    'a.remarks',
                    'b.is_shifting',
                    'b.work_schedule_id',
                    'a.ot_hours',
                    'e.attendance_start_date',
                    'e.attendance_end_date',
                    'f.name as employment_type',
                    'a.note',
                    'a.late_offset',
                    'a.undertime_offset',
                    'a.absent_offset',
                    'a.applied_offset',
                    'a.excess_hours',
                    'a.nd_start',
                    'a.nd_end',
                    'a.nd_hours',
                    'a.nd_rate',
                    'a.nd_pay',
                    'a.is_edited',
                    'a.is_adjusted'
                )
                ->where([
                    'a.payroll_period_id' => $payroll_period_id,
                    'b.id' => $id,
                    'b.active' => true,
                    'b.is_employee' => true
                ])
                ->where(function ($q) {
                    $q->whereNull('a.is_adjusted')
                        ->orWhere('a.is_adjusted', '=', 0);
                });

            if ($firstAdjusted) {
                $dailyQuery->where('a.date', '<', \Carbon\Carbon::parse($firstAdjusted)->toDateString());
            }

            $daily_time_records = $dailyQuery
                ->orderBy('date', 'asc')
                ->get();

            // get totals (without rounding)
            $totals = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'b.employment_type_id')
                ->select(
                    DB::raw("sum(a.excess_hours) as excess_hours"),
                    DB::raw("sum(a.ot_hours) as ot"),
                    DB::raw("sum(a.late) as late"),
                    DB::raw("sum(a.undertime) as undertime"),
                    DB::raw("sum(a.leave) as leave"),
                    DB::raw("sum(a.absent) as absent"),
                    DB::raw("CAST(SUM(ISNULL(a.work_hours, 0)) AS DECIMAL(18,4)) as work_hours"),
                    DB::raw("SUM(ISNULL(a.late_offset, 0)) as late_offset"),
                    DB::raw("SUM(ISNULL(a.undertime_offset, 0)) as undertime_offset"),
                    DB::raw("SUM(ISNULL(a.absent_offset, 0)) as absent_offset"),
                    'b.salary',
                    'tks.work_days as setup_work_days',
                    'tks.work_hours as setup_work_hours'
                )
                ->where([
                    'a.payroll_period_id' => $payroll_period_id,
                    'b.id' => $id,
                    'b.active' => true,
                    'b.is_employee' => true
                ])
                ->groupBy('a.employee_id', 'b.salary', 'tks.work_days', 'tks.work_hours')
                ->first();

            // Get scheduled work hours from fix_schedules_details
            $employee = DB::table('employees')->where('id', $id)->first();
            $scheduled_work_hours = null;
            if ($employee && $employee->work_schedule_id) {
                $schedule_detail = DB::table('fix_schedules_details')
                    ->where('fix_schedule_id', $employee->work_schedule_id)
                    ->where('day_id', 1) // Get Monday (day_id = 1) as representative
                    ->whereNotNull('am_in')
                    ->whereNotNull('pm_out')
                    ->select('am_in', 'pm_out')
                    ->first();

                if ($schedule_detail) {
                    $scheduled_work_hours = [
                        'am_in' => $schedule_detail->am_in,
                        'pm_out' => $schedule_detail->pm_out
                    ];
                }
            }

            // Get payroll period date range
            $payroll_period = DB::table('payroll_periods')->where('id', $payroll_period_id)->first();
            $from_date = $payroll_period->attendance_start_date ?? null;
            $to_date = $payroll_period->attendance_end_date ?? null;

            // Get employee data for calculations
            $employee = DB::table('employees')
                ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'employees.employment_type_id')
                ->select(
                    'employees.salary',
                    'employees.work_schedule_id',
                    'employees.is_shifting',
                    'employees.employment_type_id',
                    'tks.work_days as setup_work_days',
                    'tks.work_hours as setup_work_hours'
                )
                ->where('employees.id', $id)
                ->first();

            $employeeRateMetrics = $this->computeRateMetrics(
                floatval($employee->salary ?? 0),
                $employee->setup_work_days ?? null,
                $employee->setup_work_hours ?? null
            );

            // Preceding period used for time_data_adj reconciliation (for OT rows in Attendance Details)
            $viewAdjustmentPeriodId = DB::table('time_data_summary')
                ->where('Payroll_Period_ID', $payroll_period_id)
                ->where('Employee_ID', $id)
                ->value('Adjustment_Period_ID');
            if ($viewAdjustmentPeriodId !== null && $viewAdjustmentPeriodId !== '') {
                $viewAdjustmentPeriodId = (int) round(floatval($viewAdjustmentPeriodId));
            } else {
                $viewAdjustmentPeriodId = null;
            }

            // Get Overtime records with details (only approved OT)
            $overtime_records = [];
            $has_nd_schedule = false; // Track if employee has any schedule with ND
            $total_overtime_pay = 0; // Total OT pay for Attendance Calculations
            $total_nd_pay_calc = 0; // Total ND pay for Attendance Calculations
            $total_ot_hours_calc = 0; // Total OT hours for calculations (unrounded)
            $ot_applications = collect(); // Initialize as empty collection

            if ($from_date && $to_date && $employee) {
                // Get OT applications
                // Only include approved OT where payroll = 1 and service_credits = 0
                $ot_applications = DB::table('overtime_applications as a')
                    ->join('overtime_types as b', 'b.id', '=', 'a.overtime_type_id')
                    ->leftJoin(DB::raw("(SELECT x.employee_id,\n                            CASE WHEN ISNULL(ah2.approver_id_2, 0) = 0 THEN 0 ELSE 1 END as has_appr2,\n                            CASE WHEN ISNULL(ah2.approver_id_3, 0) = 0 AND ISNULL(ah2.approver_id_4, 0) = 0 THEN 0 ELSE 1 END as has_appr3\n                        FROM (\n                            SELECT ad.employee_id, ad.approver_id,\n                                   ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn\n                            FROM approver_details ad\n                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3\n                        ) x\n                        INNER JOIN approver_headers ah2 ON ah2.id = x.approver_id\n                        WHERE x.rn = 1) otappr"), 'otappr.employee_id', '=', 'a.employee_id')
                    ->select(
                        'a.overtime_type_id',
                        'a.date',
                        'a.total_hours',
                        'a.date_time_from',
                        'a.date_time_to',
                        'b.name as overtime_type',
                        'b.rate as overtime_rate'
                    )
                    ->where('a.employee_id', $id)
                    ->whereBetween('a.date', [$from_date, $to_date])
                    // Only include OT approved by required approvers per approver_headers setup
                    ->where('a.approved', 1)
                    ->where(function ($q) {
                        $q->whereRaw('ISNULL(otappr.has_appr2, 0) = 0')->orWhere('a.approved_2', 1);
                    })
                    ->where(function ($q) {
                        $q->whereRaw('ISNULL(otappr.has_appr3, 0) = 0')->orWhere('a.approved_3', 1);
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved', 0)
                            ->orWhereNull('a.disapproved');
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved_2', 0)->orWhereNull('a.disapproved_2');
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved_3', 0)->orWhereNull('a.disapproved_3');
                    })
                    ->where(function ($q) {
                        $q->where('a.is_cancel', 0)->orWhereNull('a.is_cancel');
                    })
                    ->where('a.payroll', 1)
                    ->where('a.service_credits', 0)
                    ->orderBy('a.date', 'asc')
                    ->get();

                // Calculate OT pay for each record. Formula for all overtime_types: Salary/22 × total_hours as day fraction (same day-fraction function as elsewhere).
                $daily_rate = $employeeRateMetrics['daily_rate'];
                $default_hourly_rate = $employeeRateMetrics['hourly_rate'];

                foreach ($ot_applications as $ot) {
                    $total_hours = floatval($ot->total_hours ?? 0);
                    $total_minutes = round($total_hours * 60);
                    $day_fraction = \App\Helpers\Time_Calculation::minutesToDayFraction($total_minutes);
                    $ot_pay = ($daily_rate * $day_fraction) * floatval($ot->overtime_rate ?? 0);
                    $total_overtime_pay += $ot_pay; // Accumulate for Attendance Calculations
                    $total_ot_hours_calc += $ot->total_hours; // Accumulate OT hours (unrounded)

                    // Resolve schedule for this OT date (for ND calculation)
                    $schedule = null;
                    if ($employee->work_schedule_id && !$employee->is_shifting) {
                        $ot_date = Carbon::parse($ot->date);
                        $day_id = $ot_date->dayOfWeek === 0 ? 7 : $ot_date->dayOfWeek;
                        $schedule = DB::table('fix_schedules_details')
                            ->where('fix_schedule_id', $employee->work_schedule_id)
                            ->where('day_id', $day_id)
                            ->first();
                    }
                    $work_hours = $schedule->work_hours ?? 8;
                    $hourly_rate = $work_hours > 0 ? ($daily_rate / $work_hours) : $default_hourly_rate;

                    // Check if schedule has ND enabled
                    $with_nd = ($schedule && isset($schedule->with_nd)) ? $schedule->with_nd : 0;
                    $nd_pay = 0;
                    $nd_hours = 0;

                    if ($with_nd == 1 && $schedule) {
                        $has_nd_schedule = true;

                        // Get ND time range from schedule
                        $nd_start = $schedule->nd_start ?? null;
                        $nd_end = $schedule->nd_end ?? null;
                        $nd_rate = $schedule->nd_rate ?? 0;

                        if ($nd_start && $nd_end && $nd_rate > 0) {
                            // Parse OT time range
                            // date_time_from and date_time_to can be DATETIME or TIME fields
                            $ot_date_str = Carbon::parse($ot->date)->format('Y-m-d');

                            // Parse date_time_from - could be DATETIME or TIME
                            $date_time_from_str = (string)$ot->date_time_from;
                            if (strlen($date_time_from_str) <= 8 && strpos($date_time_from_str, ' ') === false) {
                                // It's a TIME field (e.g., "17:00:00")
                                $ot_time_from = Carbon::parse($ot_date_str . ' ' . $date_time_from_str);
                            } else {
                                // It's a DATETIME field, extract time portion and combine with OT date
                                $parsed_from = Carbon::parse($ot->date_time_from);
                                $ot_time_from = Carbon::parse($ot_date_str . ' ' . $parsed_from->format('H:i:s'));
                            }

                            // Parse date_time_to - could be DATETIME or TIME
                            $date_time_to_str = (string)$ot->date_time_to;
                            if (strlen($date_time_to_str) <= 8 && strpos($date_time_to_str, ' ') === false) {
                                // It's a TIME field (e.g., "21:00:00")
                                $ot_time_to = Carbon::parse($ot_date_str . ' ' . $date_time_to_str);
                            } else {
                                // It's a DATETIME field, extract time portion and combine with OT date
                                $parsed_to = Carbon::parse($ot->date_time_to);
                                $ot_time_to = Carbon::parse($ot_date_str . ' ' . $parsed_to->format('H:i:s'));
                            }

                            // Handle OT time that spans midnight
                            if ($ot_time_from->gt($ot_time_to)) {
                                $ot_time_to->addDay();
                            }

                            // Parse ND time range (same date as OT)
                            $nd_start_time = Carbon::parse($ot_date_str . ' ' . $nd_start);
                            $nd_end_time = Carbon::parse($ot_date_str . ' ' . $nd_end);

                            // Handle ND time that spans midnight
                            if ($nd_start_time->gt($nd_end_time)) {
                                $nd_end_time->addDay();
                            }

                            // Calculate overlap between OT and ND hours
                            $overlap_start = $ot_time_from->gt($nd_start_time) ? $ot_time_from->copy() : $nd_start_time->copy();
                            $overlap_end = $ot_time_to->lt($nd_end_time) ? $ot_time_to->copy() : $nd_end_time->copy();

                            if ($overlap_start->lt($overlap_end)) {
                                // Calculate ND hours (in hours) - NO ROUNDING
                                $nd_hours = $overlap_start->diffInMinutes($overlap_end) / 60.0;

                                // Calculate ND pay: (Daily Rate / work_hours) x nd_hours x nd_rate (NO ROUNDING)
                                $nd_pay = $hourly_rate * $nd_hours * $nd_rate;
                                $total_nd_pay_calc += $nd_pay; // Accumulate for Attendance Calculations
                            }
                        }
                    }

                    // Build OT record (round only for display). ot_pay_per_hour = equivalent hourly from Salary/22 × day-fraction formula
                    $ot_pay_per_hour = $total_hours > 0 ? ($ot_pay / $total_hours) : 0;
                    $overtime_records[] = [
                        'date' => $ot->date,
                        'total_hours' => $ot->total_hours,
                        'ot_pay_per_hour' => round($ot_pay_per_hour, 2), // Round only for display
                        'ot_pay' => round($ot_pay, 2), // Round only for display
                        'nd_pay' => round($nd_pay, 2), // Round only for display
                        'nd_hours' => round($nd_hours, 2), // Round only for display
                        'overtime_type' => $ot->overtime_type,
                        'overtime_rate' => $ot->overtime_rate
                    ];
                }
            }

            // Preceding-period adjustment OT (time_data_adj): same row shape as application OT for Attendance Details
            if ($employee && $viewAdjustmentPeriodId) {
                $daily_rate_adj = floatval($employeeRateMetrics['daily_rate']);
                $srcAdjSummary = DB::table('time_data_summary')
                    ->where('Payroll_Period_ID', $viewAdjustmentPeriodId)
                    ->where('Employee_ID', $id)
                    ->first();
                if ($srcAdjSummary && isset($srcAdjSummary->Daily)) {
                    $daily_rate_adj = floatval($srcAdjSummary->Daily);
                }
                if ($daily_rate_adj <= 0) {
                    $daily_rate_adj = floatval($employeeRateMetrics['daily_rate']);
                }

                $precedingPeriodForAdj = DB::table('payroll_periods')->where('id', $viewAdjustmentPeriodId)->first();
                $fromAdj = $precedingPeriodForAdj && !empty($precedingPeriodForAdj->attendance_start_date)
                    ? \Carbon\Carbon::parse($precedingPeriodForAdj->attendance_start_date)->toDateString()
                    : null;
                $toAdj = $precedingPeriodForAdj && !empty($precedingPeriodForAdj->attendance_end_date)
                    ? \Carbon\Carbon::parse($precedingPeriodForAdj->attendance_end_date)->toDateString()
                    : null;
                $includedIdsAdj = ($fromAdj && $toAdj)
                    ? $this->getIncludedOvertimeApplicationIdsForEmployeeDateRange((int) $id, $fromAdj, $toAdj)
                    : [];

                $adjOtRows = DB::table('time_data_adj as tda')
                    ->leftJoin('overtime_types as ot', 'ot.id', '=', 'tda.overtime_type_id')
                    ->where('tda.employee_id', $id)
                    ->where('tda.payroll_period_id', $viewAdjustmentPeriodId)
                    ->where('tda.target_payroll_period_id', $payroll_period_id)
                    ->where('tda.is_ot', 1)
                    ->whereRaw('ISNULL(tda.ot_hours, 0) > 0')
                    ->orderBy('tda.date', 'asc')
                    ->select([
                        'tda.date',
                        'tda.ot_hours',
                        'tda.ot_pay',
                        'tda.ot_id',
                        'tda.overtime_type_id',
                        'ot.name as overtime_type_name',
                    ])
                    ->get();

                foreach ($adjOtRows as $ar) {
                    $hours = floatval($ar->ot_hours ?? 0);
                    if ($hours <= 0) {
                        continue;
                    }

                    $otPayStored = floatval($ar->ot_pay ?? 0);
                    $ot_pay = 0.0;

                    if ($otPayStored > 0) {
                        $ot_pay = $otPayStored;
                    } elseif (!empty($ar->ot_id) && in_array((int) $ar->ot_id, $includedIdsAdj, true)) {
                        $oa = DB::table('overtime_applications as a')
                            ->join('overtime_types as b', 'b.id', '=', 'a.overtime_type_id')
                            ->where('a.id', $ar->ot_id)
                            ->select('a.total_hours', 'b.rate as overtime_rate')
                            ->first();
                        if ($oa) {
                            $th = floatval($oa->total_hours ?? 0);
                            $tm = round($th * 60);
                            $df = \App\Helpers\Time_Calculation::minutesToDayFraction($tm);
                            $ot_pay = ($daily_rate_adj * $df) * floatval($oa->overtime_rate ?? 0);
                        }
                    } else {
                        $rate = $this->resolveOvertimeRateForTimeDataAdjRow($ar);
                        $total_minutes = round($hours * 60);
                        $day_fraction = \App\Helpers\Time_Calculation::minutesToDayFraction($total_minutes);
                        $ot_pay = ($daily_rate_adj * $day_fraction) * $rate;
                    }

                    $typePrefix = 'Preceding period — ';
                    $typeName = trim((string) ($ar->overtime_type_name ?? ''));
                    $ot_pay_per_hour = $hours > 0 ? ($ot_pay / $hours) : 0;

                    $total_overtime_pay += $ot_pay;
                    $total_ot_hours_calc += $hours;

                    $overtime_records[] = [
                        'date' => $ar->date,
                        'total_hours' => $hours,
                        'ot_pay_per_hour' => round($ot_pay_per_hour, 2),
                        'ot_pay' => round($ot_pay, 2),
                        'nd_pay' => 0,
                        'nd_hours' => 0,
                        'overtime_type' => $typeName !== '' ? $typePrefix . $typeName : $typePrefix . 'Adjustment',
                        'overtime_rate' => null,
                    ];
                }
            }

            // Get Leave records with details - match leave_headers/leave_details with time_data when available
            // Show all fully approved leaves within the period, even if time_data doesn't have leave=1 set yet
            // This ensures "Leave Taken" shows correctly even if SP_ProcessTimeData hasn't run or payroll_period_id doesn't match
            $leave_records = [];
            $total_leave_pay = 0; // Total leave pay for Attendance Calculations (only paid leaves)
            if ($from_date && $to_date) {
                // Query from leave_headers/leave_details (source of truth) and LEFT JOIN time_data
                // This way we show leaves even if time_data doesn't have leave=1 or wrong payroll_period_id
                $leave_records = DB::table('leave_headers as a')
                    ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                    ->join('leave_types as c', 'c.id', '=', 'a.leave_type_id')
                    ->leftJoin('time_data as td', function ($join) use ($id, $payroll_period_id) {
                        $join->on('td.date', '=', 'b.leave_date')
                            ->where('td.employee_id', '=', $id)
                            ->where('td.payroll_period_id', '=', $payroll_period_id);
                    })
                    ->select(
                        'b.leave_date as date',
                        'c.name as leave_type',
                        'b.with_pay',
                        'a.approved as approved_1',
                        'a.approved_2',
                        'a.approved_3'
                    )
                    ->where('a.employee_id', $id)
                    // Only show leaves within the payroll period (attendance_start_date to attendance_end_date)
                    ->whereBetween('b.leave_date', [$from_date, $to_date])
                    // Only show fully approved leaves (all 3 approvers)
                    ->where('a.approved', 1)
                    ->where('a.approved_2', 1)
                    ->where('a.approved_3', 1)
                    // Exclude disapproved leaves
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('a.disapproved', 0)
                                ->orWhereNull('a.disapproved');
                        })
                            ->where(function ($q2) {
                                $q2->where('a.disapproved_2', 0)
                                    ->orWhereNull('a.disapproved_2');
                            })
                            ->where(function ($q2) {
                                $q2->where('a.disapproved_3', 0)
                                    ->orWhereNull('a.disapproved_3');
                            });
                    })
                    // Exclude cancelled leaves (check all cancellation fields: is_cancel, is_cancel_2, is_cancel_3)
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('a.is_cancel', 0)
                                ->orWhereNull('a.is_cancel');
                        })
                            ->where(function ($q2) {
                                $q2->where('a.is_cancel_2', 0)
                                    ->orWhereNull('a.is_cancel_2');
                            })
                            ->where(function ($q2) {
                                $q2->where('a.is_cancel_3', 0)
                                    ->orWhereNull('a.is_cancel_3');
                            });
                    })
                    ->distinct()
                    ->orderBy('b.leave_date', 'asc')
                    ->get()
                    ->toArray();

                // Calculate leave pay (only for paid leaves - with_pay = 1.00)
                // Leave pay = daily_rate * number of paid leave days
                if ($employee && !empty($leave_records)) {
                    $daily_rate = $employeeRateMetrics['daily_rate'];
                    foreach ($leave_records as $leave) {
                        // Compare with_pay as float to handle decimal values (1.00, 1.0, etc.)
                        $with_pay = floatval($leave->with_pay ?? 0);
                        if ($with_pay == 1.00) {
                            // Only count paid leaves
                            $total_leave_pay += $daily_rate; // NO ROUNDING in calculation
                        }
                    }
                }
            }

            // Get OB (Official Business) records with details
            // For OB that spans multiple days, we need to get all dates in the range
            $ob_records = [];
            if ($from_date && $to_date) {
                $ob_applications = DB::table('official_business_applications as a')
                    ->select(
                        'a.id',
                        'a.purpose as ob_type',
                        DB::raw("CAST(a.date_time_from AS DATE) as start_date"),
                        DB::raw("CAST(a.date_time_to AS DATE) as end_date")
                    )
                    ->where('a.employee_id', $id)
                    ->where(function ($q) use ($from_date, $to_date) {
                        $q->whereBetween(DB::raw("CAST(date_time_from AS DATE)"), [$from_date, $to_date])
                            ->orWhereBetween(DB::raw("CAST(date_time_to AS DATE)"), [$from_date, $to_date])
                            ->orWhere(function ($q2) use ($from_date, $to_date) {
                                $q2->where(DB::raw("CAST(date_time_from AS DATE)"), '<=', $from_date)
                                    ->where(DB::raw("CAST(date_time_to AS DATE)"), '>=', $to_date);
                            });
                    })
                    ->where(function ($q) {
                        $q->where('a.approved', 1)
                            ->orWhere('a.approved_2', 1)
                            ->orWhere('a.approved_3', 1);
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved', 0)
                            ->orWhereNull('a.disapproved');
                    })
                    ->orderBy('a.date_time_from', 'asc')
                    ->get();

                // Expand OB records to include all dates in the range that fall within payroll period
                foreach ($ob_applications as $ob) {
                    $startDate = Carbon::parse($ob->start_date);
                    $endDate = Carbon::parse($ob->end_date);
                    $periodStart = Carbon::parse($from_date);
                    $periodEnd = Carbon::parse($to_date);

                    // Get the actual date range (intersection of OB range and payroll period range)
                    $actualStart = $startDate->greaterThan($periodStart) ? $startDate : $periodStart;
                    $actualEnd = $endDate->lessThan($periodEnd) ? $endDate : $periodEnd;

                    // Add a record for each date in the range
                    $currentDate = $actualStart->copy();
                    while ($currentDate->lte($actualEnd)) {
                        $ob_records[] = [
                            'date' => $currentDate->format('Y-m-d'),
                            'ob_type' => $ob->ob_type ?? 'Official Business'
                        ];
                        $currentDate->addDay();
                    }
                }
            }

            // Preceding-period adjustment OB (time_data_adj): same shape as application OB for Attendance Details
            if ($employee && $viewAdjustmentPeriodId) {
                $adjObRows = DB::table('time_data_adj as tda')
                    ->leftJoin('official_business_applications as oba', 'oba.id', '=', 'tda.ob_id')
                    ->where('tda.employee_id', $id)
                    ->where('tda.payroll_period_id', $viewAdjustmentPeriodId)
                    ->where('tda.target_payroll_period_id', $payroll_period_id)
                    ->where(function ($q) {
                        $q->where('tda.is_ob', 1)
                            ->orWhereRaw('ISNULL(tda.ob_hours, 0) > 0');
                    })
                    ->orderBy('tda.date', 'asc')
                    ->select([
                        'tda.date',
                        DB::raw('COALESCE(oba.purpose, \'Official Business\') as ob_type'),
                    ])
                    ->get();

                $obRowKeys = [];
                foreach ($ob_records as $existing) {
                    $d = (string) ($existing['date'] ?? '');
                    $t = (string) ($existing['ob_type'] ?? '');
                    $obRowKeys[$d . "\0" . $t] = true;
                }

                foreach ($adjObRows as $row) {
                    if (empty($row->date)) {
                        continue;
                    }
                    $dateStr = \Carbon\Carbon::parse($row->date)->toDateString();
                    $obType = trim((string) ($row->ob_type ?? '')) ?: 'Official Business';
                    $key = $dateStr . "\0" . $obType;
                    if (isset($obRowKeys[$key])) {
                        continue;
                    }
                    $obRowKeys[$key] = true;
                    $ob_records[] = [
                        'date' => $dateStr,
                        'ob_type' => $obType,
                    ];
                }
            }

            if (!empty($ob_records)) {
                usort($ob_records, function ($a, $b) {
                    return strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
                });
            }

            // Get Holiday records with details - show all holidays within payroll period
            // LEFT JOIN to time_data to get employee attendance (if any)
            $holiday_records = [];
            $total_holiday_pay = 0; // Full amount added to total_amount (includes automatic daily rate for absent_with_pay)
            $total_holiday_pay_display = 0; // Amount shown in Holiday row: for absent_with_pay=1 only (hourly_rate × work_hours) when worked, else 0
            $absent_exclude_holiday_days = 0.0; // Absent on absent_with_pay holidays: exclude from absent count/amount
            if ($from_date && $to_date && $employee) {
                // Get all active holidays within the payroll period date range
                // LEFT JOIN to time_data to get employee's attendance status for each holiday
                $holiday_records = DB::table('holidays as a')
                    ->leftJoin('holiday_types as b', 'b.id', '=', 'a.holiday_type')
                    ->leftJoin('time_data as td', function ($join) use ($id, $payroll_period_id) {
                        $join->on('td.date', '=', 'a.date')
                            ->where('td.employee_id', '=', $id)
                            ->where('td.payroll_period_id', '=', $payroll_period_id);
                    })
                    ->select(
                        'a.date',
                        'a.id as holiday_id',
                        'a.name as holiday_name',
                        DB::raw("COALESCE(b.name, a.name, 'Holiday') as holiday_type"),
                        'b.rate as holiday_rate',
                        'b.absent_with_pay',
                        DB::raw("COALESCE(td.absent, 0) as absent"),
                        'td.am_in',
                        'td.pm_in',
                        DB::raw("COALESCE(td.work_hours, 0) as work_hours"),
                        DB::raw("COALESCE(td.is_holiday, 0) as is_holiday"),
                        'td.holiday_pay'
                    )
                    ->whereBetween('a.date', [$from_date, $to_date])
                    ->where('a.active', 1)
                    ->orderBy('a.date', 'asc')
                    ->get()
                    ->toArray();

                // Calculate holiday pay based on absent_with_pay and rate
                // For absent_with_pay = 1: all employees get daily rate; if they worked (work_hours > 0), add (hourly_rate × work_hours). These days are excluded from absent count/amount.
                $daily_rate = $employeeRateMetrics['daily_rate'];
                $setup_work_hours = floatval($employee->setup_work_hours ?? 0) ?: 8;
                $hourly_rate = $setup_work_hours > 0 ? ($daily_rate / $setup_work_hours) : ($daily_rate / 8);
                foreach ($holiday_records as $holiday) {
                    $holiday_rate = floatval($holiday->holiday_rate ?? 0);
                    // Handle absent_with_pay: can be boolean, integer (0/1), or NULL
                    // Convert to boolean for logic, but keep original for display
                    $absent_with_pay_raw = $holiday->absent_with_pay ?? null;
                    $absent_with_pay_bool = false;
                    if ($absent_with_pay_raw !== null) {
                        if (is_bool($absent_with_pay_raw)) {
                            $absent_with_pay_bool = $absent_with_pay_raw;
                        } else {
                            $absent_with_pay_bool = (bool)intval($absent_with_pay_raw);
                        }
                    }

                    // Check if employee was present (has attendance punches)
                    // Note: If there's no time_data record, am_in and pm_in will be NULL, so is_present will be false
                    $is_present = !empty($holiday->am_in) || !empty($holiday->pm_in);
                    $is_absent = floatval($holiday->absent ?? 0) > 0;
                    $has_time_data = !is_null($holiday->am_in) || !is_null($holiday->pm_in) || !is_null($holiday->absent);
                    $work_hours = floatval($holiday->work_hours ?? 0);

                    $holiday_pay_amount = 0;

                    if ($absent_with_pay_bool === true) {
                        // absent_with_pay = 1: Do NOT insert into Holiday_Pay. Amount is in Overtime (overtime_type_id = 4 "Holiday Overtime") via overtime_application; not considered Holiday_Pay. Display 0.
                        // (We still exclude absent on these days from absent count below.)
                        $absent_exclude_holiday_days += floatval($holiday->absent ?? 0);
                        // holiday_pay_amount stays 0 — not added to total_holiday_pay or Holiday_Pay column.
                    } else if ($holiday_rate > 0 && $absent_with_pay_bool === false) {
                        // Holiday with absent_with_pay = 0: Only pay if employee was present
                        if ($is_present && !$is_absent) {
                            $holiday_pay_amount = $daily_rate * $holiday_rate; // NO ROUNDING
                        }
                        // If employee was absent or no time_data record: Don't pay
                    }

                    // Accumulate full holiday pay for Total Amount calculation (absent_with_pay = 1 contributes 0)
                    $total_holiday_pay += $holiday_pay_amount;

                    // Holiday row display: for absent_with_pay = 1 we show 0 (pay for working is in OT); for absent_with_pay = 0 show full amount
                    $display_holiday_pay = 0;
                    if ($absent_with_pay_bool === true) {
                        $display_holiday_pay = 0; // Not considered Holiday_Pay; work pay is in Overtime (Holiday Overtime type)
                    } else {
                        $display_holiday_pay = $holiday_pay_amount; // non–absent_with_pay: show full amount
                    }
                    $total_holiday_pay_display += $display_holiday_pay;
                    $holiday->calculated_holiday_pay = round($display_holiday_pay, 2);
                    $holiday->is_paid = $display_holiday_pay > 0;
                    // Ensure absent_with_pay is properly set for frontend display (0 or 1)
                    // If absent_with_pay is NULL, default to 0 (no pay if absent)
                    $holiday->absent_with_pay = $absent_with_pay_raw !== null ? ($absent_with_pay_bool ? 1 : 0) : 0;
                }
            }

            // Get Work Cancellation records with details - show ALL work cancellations within payroll period (for viewing purposes)
            // IMPORTANT: Work Cancellation is "no work no pay" - never added to pay calculation
            $work_cancellation_records = [];
            $total_work_cancellation_pay = 0; // Always 0 - Work Cancellation is never paid
            $total_work_cancellation_hours = 0; // Total work cancellation hours to exclude from gross pay (for display/reporting only)
            if ($from_date && $to_date && $employee) {
                // Get all work cancellations that overlap with the payroll period date range
                $work_cancellations = DB::table('work_cancellations')
                    ->where(function ($q) use ($from_date, $to_date) {
                        // Work cancellation overlaps with payroll period if:
                        // - work_cancellation.date_from <= payroll_period.to_date AND
                        // - work_cancellation.date_to >= payroll_period.from_date
                        $q->where('date_from', '<=', $to_date)
                            ->where('date_to', '>=', $from_date);
                    })
                    ->orderBy('date_from', 'asc')
                    ->get();

                // For viewing purposes, show ALL work cancellations that overlap with the payroll period
                // regardless of whether there are matching time_data records
                foreach ($work_cancellations as $wc) {
                    $work_cancellation_records[] = [
                        'date_from' => $wc->date_from,
                        'date_to' => $wc->date_to,
                        'reason' => $wc->reason ?? 'Work Cancellation',
                        'work_cancellation_id' => $wc->id
                    ];
                }

                // Also calculate work cancellation hours from time_data for pay calculation purposes
                // This is separate from the display records above
                foreach ($work_cancellations as $wc) {
                    $wc_start = Carbon::parse($wc->date_from);
                    $wc_end = Carbon::parse($wc->date_to);
                    $period_start = Carbon::parse($from_date);
                    $period_end = Carbon::parse($to_date);

                    // Get the intersection of work cancellation period and payroll period
                    $actual_start = $wc_start->greaterThan($period_start) ? $wc_start : $period_start;
                    $actual_end = $wc_end->lessThan($period_end) ? $wc_end : $period_end;

                    // Check if there's any overlap
                    if ($actual_start->lte($actual_end)) {
                        // Generate all dates in the intersection range
                        $current_date = $actual_start->copy();
                        while ($current_date->lte($actual_end)) {
                            $date_str = $current_date->format('Y-m-d');

                            // Check if this date has a time_data record matching work cancellation pattern:
                            // - All time fields are NULL (am_in, am_out, break_in, break_out, pm_in, pm_out)
                            // - For with_pay = 1: work_hours > 0 and absent = 0.00
                            // - For with_pay = 0: absent = 1.00 and work_hours = 0.00
                            // - Not on leave, holiday, OB, or WFH
                            $time_data_record = DB::table('time_data')
                                ->where('employee_id', $id)
                                ->where('payroll_period_id', $payroll_period_id)
                                ->where('date', $date_str)
                                ->whereNull('am_in')
                                ->whereNull('am_out')
                                ->whereNull('break_in')
                                ->whereNull('break_out')
                                ->whereNull('pm_in')
                                ->whereNull('pm_out')
                                ->where(function ($q) use ($wc) {
                                    if ($wc->with_pay == 1) {
                                        // For with_pay = 1: work_hours > 0 and absent = 0.00
                                        $q->where('work_hours', '>', 0)
                                            ->where('absent', 0.00);
                                    } else {
                                        // For with_pay = 0: absent = 1.00
                                        $q->where('absent', 1.00);
                                    }
                                })
                                ->where(function ($q) {
                                    // Exclude WFH, leave, holiday, OB employees (work cancellation doesn't apply to them)
                                    $q->where(function ($q2) {
                                        $q2->where('is_wfh', 0)->orWhereNull('is_wfh');
                                    })
                                        ->where(function ($q2) {
                                            $q2->where('leave', 0)->orWhereNull('leave');
                                        })
                                        ->where(function ($q2) {
                                            $q2->where('is_holiday', 0)->orWhereNull('is_holiday');
                                        })
                                        ->where(function ($q2) {
                                            $q2->where('is_ob', 0)->orWhereNull('is_ob');
                                        });
                                })
                                ->first();

                            // If time_data record matches work cancellation pattern, track hours for pay calculation
                            if ($time_data_record) {
                                // Work Cancellation is "no work no pay" - never add to pay calculation
                                // Track work cancellation hours to exclude from gross pay (for display/reporting purposes only)
                                $total_work_cancellation_hours += floatval($time_data_record->work_hours ?? 0);
                                // Note: $total_work_cancellation_pay remains 0 (never paid)
                            }

                            $current_date->addDay();
                        }
                    }
                }

                // Sort by date_from
                usort($work_cancellation_records, function ($a, $b) {
                    return strcmp($a['date_from'] ?? '', $b['date_from'] ?? '');
                });
            }

            if (!empty($overtime_records)) {
                usort($overtime_records, function ($a, $b) {
                    return strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
                });
            }

            // Calculate OT totals for display (use unrounded values we calculated above)
            $ot_totals = null;
            if (!empty($overtime_records) && $employee) {
                // Use the unrounded values we calculated above
                $total_ot_hours = $total_ot_hours_calc; // Use accumulated unrounded value
                $total_ot_pay_unrounded = $total_overtime_pay;
                $total_nd_pay_unrounded = $total_nd_pay_calc;

                // Calculate weighted average OT Pay per hour (NO ROUNDING in calculation)
                // OT Pay per hour = Total OT Pay / Total OT Hours
                // This represents: (Hourly Rate × Overtime Rate) weighted average
                $ot_pay_per_hour = 0;
                if ($total_ot_hours > 0) {
                    $ot_pay_per_hour = $total_ot_pay_unrounded / $total_ot_hours;
                }

                $ot_totals = [
                    'total_ot_hours' => round($total_ot_hours, 2), // Round only for display
                    'ot_pay_per_hour' => round($ot_pay_per_hour, 2), // Round only for display - represents hourly_rate × overtime_rate (weighted average)
                    'total_ot_pay' => round($total_ot_pay_unrounded, 2), // Round only for display
                    'total_nd_pay' => round($total_nd_pay_unrounded, 2), // Round only for display
                    'total_ot_amount' => round($total_ot_pay_unrounded + $total_nd_pay_unrounded, 2), // Round only for display
                    'ot_days_count' => count($overtime_records)
                ];
            }

            // Calculate Days Present: count days where work_hours > 0 OR is_ob = 1
            $days_present = 0;
            if ($from_date && $to_date && $id) {
                $daysPresentResult = DB::table('time_data')
                    ->where('payroll_period_id', $payroll_period_id)
                    ->where('employee_id', $id)
                    ->select(DB::raw("COUNT(DISTINCT CASE WHEN (COALESCE(work_hours, 0) > 0 OR is_ob = 1) THEN date END) as days_present"))
                    ->first();
                $days_present = $daysPresentResult ? (int)$daysPresentResult->days_present : 0;
            }

            // Is_adjusted count and dates: all distinct dates with is_adjusted = 1 are listed (including
            // adjusted rest days, is_restday = 1). The numeric count excludes rest days so they are not
            // treated as extra "assumed perfect attendance" days; plain schedule rest days never have
            // is_adjusted and are not listed.
            $is_adjusted_count = 0;
            $is_adjusted_dates = [];
            if ($from_date && $to_date && $id) {
                $isAdjustedCountResult = DB::table('time_data')
                    ->where('payroll_period_id', $payroll_period_id)
                    ->where('employee_id', $id)
                    ->where('is_adjusted', 1)
                    ->select(DB::raw('COUNT(DISTINCT CASE WHEN ISNULL(is_restday, 0) = 0 THEN date END) as is_adjusted_count'))
                    ->first();
                $is_adjusted_count = $isAdjustedCountResult ? (int)$isAdjustedCountResult->is_adjusted_count : 0;

                $is_adjusted_dates = DB::table('time_data')
                    ->where('payroll_period_id', $payroll_period_id)
                    ->where('employee_id', $id)
                    ->where('is_adjusted', 1)
                    ->distinct()
                    ->orderBy('date')
                    ->pluck('date')
                    ->map(function ($d) {
                        return \Carbon\Carbon::parse($d)->toDateString();
                    })
                    ->values()
                    ->all();
            }

            // Calculate total amount for Attendance Calculations
            // Formula: Total Amount = Total Salary - Late Amount - Undertime Amount - Absent Amount
            // Where:
            //   Total Salary = Daily Rate (unrounded) × Days Present
            //   Late Amount = (Salary / 22) × minutesToDayFraction(late_minutes)
            //   Undertime Amount = (Salary / 22) × minutesToDayFraction(undertime_minutes)
            //   Absent Amount = Absent (days) × Daily Rate (unrounded)
            // Daily Rate = Salary / 22 (standard, regardless of workdays in a month)
            // Days Present = Count of distinct dates where work_hours > 0 OR is_ob = 1
            // Note: All calculations use unrounded Daily Rate. Only the final Total Amount is rounded to 2 decimals.
            // Late and undertime values are converted from minutes to day fractions using MinutesToDayFraction lookup table.
            if ($totals && $employee) {
                // Get unrounded daily_rate for calculation (Salary / 22)
                $daily_rate_unrounded = $employeeRateMetrics['daily_rate'];
                // Get rounded daily_rate for UI display only
                $daily_rate = round($daily_rate_unrounded, 2);

                // Aggregate NET (remaining) tardiness and OFFSET portions separately,
                // then derive gross (raw before offset) using minute-based conversion.
                $lateOffsetFraction = floatval($totals->late_offset ?? 0);           // day fraction offsetted
                $undertimeOffsetFraction = floatval($totals->undertime_offset ?? 0); // day fraction offsetted
                $absentOffsetFraction = floatval($totals->absent_offset ?? 0);       // days offsetted

                // Net day fractions saved in time_data (after any per-day offsets)
                $late_day_fraction_net = floatval($totals->late ?? 0);
                $undertime_day_fraction_net = floatval($totals->undertime ?? 0);

                // Exclude absent on absent_with_pay holidays from count and amount (net side only)
                $absent_days_net = max(0, floatval($totals->absent ?? 0) - floatval($absent_exclude_holiday_days ?? 0));

                // Convert to minutes for accurate aggregation
                $lateMinutesNet = \App\Helpers\Time_Calculation::dayFractionToMinutes($late_day_fraction_net);
                $undertimeMinutesNet = \App\Helpers\Time_Calculation::dayFractionToMinutes($undertime_day_fraction_net);
                $lateOffsetMinutes = \App\Helpers\Time_Calculation::dayFractionToMinutes($lateOffsetFraction);
                $undertimeOffsetMinutes = \App\Helpers\Time_Calculation::dayFractionToMinutes($undertimeOffsetFraction);

                // Gross (before offset) = net + offset (per CSC dayfraction table)
                $lateMinutesGross = $lateMinutesNet + $lateOffsetMinutes;
                $undertimeMinutesGross = $undertimeMinutesNet + $undertimeOffsetMinutes;

                // Net day fractions (used for deduction amounts)
                $late_day_fraction = \App\Helpers\Time_Calculation::minutesToDayFraction($lateMinutesNet);
                $undertime_day_fraction = \App\Helpers\Time_Calculation::minutesToDayFraction($undertimeMinutesNet);
                $absent_days_total = $absent_days_net;

                // For UI labels (Late/Undertime in hrs+mins), use GROSS minutes
                $late_minutes_total = $lateMinutesGross;
                $undertime_minutes_total = $undertimeMinutesGross;

                // Calculate Total Salary = Daily Rate (unrounded) × Days Present (all unrounded)
                $total_salary = $daily_rate_unrounded * $days_present;

                // Is_adjusted amount: assumed perfect attendance (validation ONLY is_adjusted = 1)
                $is_adjusted_amount = $is_adjusted_count * $daily_rate_unrounded;

                // Calculate deduction amounts using Unrounded Daily Rate and day fractions (all unrounded)
                // Formula: (Salary / 22) × day_fraction
                // No rounding during calculation - only round for display
                $late_amount = $late_day_fraction * $daily_rate_unrounded;
                $undertime_amount = $undertime_day_fraction * $daily_rate_unrounded;
                $absent_amount = $absent_days_total * $daily_rate_unrounded;

                // UI-based rounding to ensure displayed totals and backend totals match exactly
                $ui_daily_rate = round($daily_rate_unrounded, 2);
                $ui_total_salary = $ui_daily_rate * $days_present;
                $ui_late_amount = round($late_amount, 2);
                $ui_undertime_amount = round($undertime_amount, 2);
                $ui_absent_amount = round($absent_amount, 2);
                $ui_total_holiday_pay = round($total_holiday_pay, 2);
                $ui_total_overtime_pay = round($total_overtime_pay, 2);

                // Calculate Total Amount using the same rounded values shown in the UI
                // Note: Absent is already reflected in days_present (ui_total_salary), so do not deduct absent again
                // IMPORTANT: Ensure total_amount never goes negative (unrealistic for payroll)
                // Add is_adjusted amount: (is_adjusted_count × daily_rate) for assumed perfect attendance
                $ui_total_leave_pay = round($total_leave_pay, 2);
                $ui_is_adjusted_amount = round($is_adjusted_amount, 2);
                $total_amount = max(0, $ui_total_salary
                    + $ui_total_holiday_pay
                    + $ui_total_leave_pay
                    + $ui_total_overtime_pay
                    + $ui_is_adjusted_amount
                    - $ui_late_amount
                    - $ui_undertime_amount);

                // Convert totals to decimal hours for UI display (formatHoursMinsForDisplay expects decimal hours)
                $late_decimal_hours = $late_minutes_total / 60.0;
                $undertime_decimal_hours = $undertime_minutes_total / 60.0;

                // Add calculated values to totals (round only for display)
                $totals->days_present = $days_present; // Days Present for display
                $totals->is_adjusted_count = $is_adjusted_count;
                $totals->is_adjusted_dates = $is_adjusted_dates;
                $totals->total_overtime_pay = round($total_overtime_pay, 2); // Round only for display
                $totals->total_leave_pay = round($total_leave_pay, 2); // Round only for display
                $totals->total_holiday_pay = round($total_holiday_pay_display ?? $total_holiday_pay, 2); // Holiday row: display amount only (0 when absent on absent_with_pay=1)
                $totals->total_work_cancellation_pay = round($total_work_cancellation_pay, 2); // Round only for display
                $totals->total_work_cancellation_hours = round($total_work_cancellation_hours, 2); // Round only for display
                $totals->total_nd_pay = round($total_nd_pay_calc, 2); // Round only for display
                $totals->total_amount = round($total_amount, 2); // Round only final result to 2 decimals

                // IMPORTANT:
                // Do NOT override totals->total_amount with time_data_summary.Total_Amount here.
                // For the Attendance Calculations view, the bold "Total Amount" should follow the
                // same breakdown formula shown in the UI:
                //   Salary/22 × Days Present
                //   − Late
                //   − Undertime
                //   + Overtime
                //   + Holiday
                //   + Leave
                //   + Is Adjusted
                //   − Preceding Period Adj (Adjustment_Amount = deductions − Adj. OT − Adj. Holiday),
                // all using the rounded UI values. The persisted Total_Amount in time_data_summary
                // remains the source of truth for payroll posting, but the breakdown view should
                // be self-consistent with its own formula instead of being forced to match the
                // previously saved summary value.

                $totals->late_amount = round($late_amount, 2); // Round only for display
                $totals->undertime_amount = round($undertime_amount, 2); // Round only for display
                $totals->absent_amount = round($absent_amount, 2); // Round only for display
                $totals->daily_rate = round($daily_rate, 2); // Rounded for display only

                // Store late/undertime as decimal hours for UI display (formatHoursMinsForDisplay expects this)
                // Keep original day fractions for calculation, but send decimal hours for display
                $totals->late = $late_decimal_hours; // Decimal hours (gross) for UI display
                $totals->undertime = $undertime_decimal_hours; // Decimal hours (gross) for UI display
                $totals->late_offset = round($lateOffsetMinutes / 60, 4); // Decimal hours offsetted
                $totals->undertime_offset = round($undertimeOffsetMinutes / 60, 4); // Decimal hours offsetted
                $totals->absent_offset = round($absentOffsetFraction, 3); // Days offsetted
                $totals->absent = round($absent_days_total, 3); // Net absent days
            }

            // Get payroll period details for preview
            $payroll_period_details = null;
            if ($payroll_period) {
                $payroll_period_details = [
                    'id' => $payroll_period->id,
                    'attendance_start_date' => $payroll_period->attendance_start_date,
                    'attendance_end_date' => $payroll_period->attendance_end_date,
                    'payroll_start_date' => $payroll_period->payroll_start_date ?? null,
                    'payroll_end_date' => $payroll_period->payroll_end_date ?? null,
                    'release_date' => $payroll_period->release_date ?? null
                ];
            }

            // Get adjustment details from time_data_summary_adj if they exist
            // These should ONLY be pulled when the current payroll period actually has a linked
            // preceding period in time_data_summary (Adjustment_Period_ID). This prevents
            // unrelated or legacy pending adjustments from other periods from affecting
            // payroll periods that were processed without a preceding period selected.
            $adjustment_summary = null;

            // Look up the configured preceding period for this employee + payroll period
            $adjustment_period_id = DB::table('time_data_summary')
                ->where('Payroll_Period_ID', $payroll_period_id)
                ->where('Employee_ID', $id)
                ->value('Adjustment_Period_ID');

            // Normalize to int (DB may return decimal e.g. 6.000)
            if ($adjustment_period_id !== null && $adjustment_period_id !== '') {
                $adjustment_period_id = (int) round(floatval($adjustment_period_id));
            }

            if ($adjustment_period_id) {
                $adjustment_summary = DB::table('time_data_summary_adj')
                    ->where('Employee_ID', $id)
                    ->where('Preceding_Payroll_Period_ID', $adjustment_period_id)
                    ->where('Status', 'PENDING') // Only show pending adjustments that haven't been applied yet
                    ->orderBy('Date_Stamp', 'desc')
                    ->first();
            }

            // Adj. Overtime must include OT from time_data_adj (is_ot / ot_hours) when ot_pay is 0 or SP-only;
            // reconciliation previously used only overtime_applications, so the UI showed ₱0.00.
            if ($adjustment_summary && $adjustment_period_id) {
                $precedingForOt = (int) $adjustment_summary->Preceding_Payroll_Period_ID;
                $precedingPeriodForOt = DB::table('payroll_periods')->where('id', $precedingForOt)->first();
                if ($precedingPeriodForOt && !empty($precedingPeriodForOt->attendance_start_date) && !empty($precedingPeriodForOt->attendance_end_date)) {
                    $fromOt = \Carbon\Carbon::parse($precedingPeriodForOt->attendance_start_date)->toDateString();
                    $toOt = \Carbon\Carbon::parse($precedingPeriodForOt->attendance_end_date)->toDateString();
                    $dailyOt = floatval($adjustment_summary->Daily ?? 0);
                    if ($dailyOt <= 0) {
                        $srcSum = DB::table('time_data_summary')
                            ->where('Payroll_Period_ID', $precedingForOt)
                            ->where('Employee_ID', $id)
                            ->first();
                        if ($srcSum && isset($srcSum->Daily)) {
                            $dailyOt = floatval($srcSum->Daily);
                        }
                    }
                    if ($dailyOt <= 0) {
                        $empRowOt = DB::table('employees as e')
                            ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'e.employment_type_id')
                            ->where('e.id', $id)
                            ->select('e.salary', 'tks.work_days', 'tks.work_hours')
                            ->first();
                        $rmOt = $this->computeRateMetrics(floatval($empRowOt->salary ?? 0), $empRowOt->work_days ?? null, $empRowOt->work_hours ?? null);
                        $dailyOt = $rmOt['daily_rate'];
                    }
                    $includedIdsOt = $this->getIncludedOvertimeApplicationIdsForEmployeeDateRange($id, $fromOt, $toOt);
                    $otAppsView = $this->computeOvertimePayFromApplicationsForEmployeeDateRange($id, $fromOt, $toOt);
                    $suppOtView = $this->computeSupplementalOvertimePayFromTimeDataAdj($id, $precedingForOt, (int) $payroll_period_id, $dailyOt, $includedIdsOt);
                    $adjustment_summary->Overtime = round($otAppsView + $suppOtView, 3);
                }
            }

            // Preceding period adj total (deductions − Adj. OT − Adj. Holiday) is DEDUCTED from Total Amount (negative = addition).
            // For the Attendance Calculations view, Total Amount in the UI must match exactly the value saved in
            // time_data_summary.Total_Amount using the formula:
            //   Total Amount =
            //     (Salary/22 × Days Present)
            //     − Late
            //     − Undertime
            //     + Overtime
            //     + Holiday
            //     + Leave
            //     + Is Adjusted
            //     − Preceding Period Adj.
            //
            // The Preceding Period Adj total is stored in time_data_summary.Adjustment_Amount during reconciliation.
            // Here, we ensure the in-memory totals->total_amount and the persisted Total_Amount column stay in sync
            // by applying the same subtraction once when an adjustment_summary exists.
            if ($totals && $adjustment_summary) {
                $adj_deductions = floatval($adjustment_summary->Late_Amount ?? 0)
                    + floatval($adjustment_summary->Undertime_Amount ?? 0)
                    + floatval($adjustment_summary->Absent_Amount ?? 0);
                $adj_ot_holiday = floatval($adjustment_summary->Overtime ?? 0) + floatval($adjustment_summary->Holiday_Pay ?? 0);
                // Net preceding period effect: − deductions + (OT + Holiday) e.g. −467.88 + 856.71 = +388.83
                $totals->total_amount = round(max(0, $totals->total_amount - $adj_deductions + $adj_ot_holiday), 2);

                // Persist the recalculated Total_Amount so time_data_summary matches the UI breakdown.
                DB::table('time_data_summary')
                    ->where('Payroll_Period_ID', $payroll_period_id)
                    ->where('Employee_ID', $id)
                    ->update(['Total_Amount' => $totals->total_amount]);
            }

            // Build list of specific dates included in the preceding period adjustment calculation
            // and a per-date detail list for the "View Detailed Adjustment Summary" UI.
            $adjustment_dates = [];
            $adjustment_details = [];
            if ($adjustment_summary && isset($adjustment_summary->Preceding_Payroll_Period_ID) && $adjustment_summary->Preceding_Payroll_Period_ID) {
                try {
                    // Use the explicit preceding period id recorded on the summary; this
                    // should match the Adjustment_Period_ID that was linked to the current
                    // payroll period when processing attendance.
                    $precedingId = (int) $adjustment_summary->Preceding_Payroll_Period_ID;

                    // Aggregate raw and offset totals for the preceding period adjustments directly
                    // from time_data_adj so the UI can show correct raw durations and "Offsetted"
                    // annotations using minute-based aggregation (dayFraction → minutes → sum → dayFraction).
                    try {
                        $adjTotals = $this->calculateTimeDataAdjTotals($id, $precedingId, $payroll_period_id);

                        // Prefer recalculated raw day fractions from adj totals (minute-based) for display.
                        // This avoids adding small day-fraction decimals directly, which can drift from the
                        // canonical day-fraction table.
                        if (isset($adjTotals['late'])) {
                            $adjustment_summary->Late = $adjTotals['late'];
                        }
                        if (isset($adjTotals['undertime'])) {
                            $adjustment_summary->Undertime = $adjTotals['undertime'];
                        }
                        if (isset($adjTotals['absent'])) {
                            $adjustment_summary->Absent = $adjTotals['absent'];
                        }

                        // Attach offset fractions as dynamic properties; frontend reads
                        // Late_Offset/Undertime_Offset/Absent_Offset and converts these
                        // day fractions to hrs/mins/days for display.
                        $adjustment_summary->Late_Offset = $adjTotals['late_offset'] ?? 0;
                        $adjustment_summary->Undertime_Offset = $adjTotals['undertime_offset'] ?? 0;
                        $adjustment_summary->Absent_Offset = $adjTotals['absent_offset'] ?? 0;
                    } catch (\Throwable $e) {
                        // If anything fails while enriching with offset totals, keep existing summary
                        // and continue; UI will simply show no "Offsetted" annotation.
                    }

                    $query = DB::table('time_data_adj as tda')
                        ->leftJoin('time_data as td', 'td.id', '=', 'tda.source_time_data_id')
                        ->where('tda.employee_id', $id)
                        ->where('tda.payroll_period_id', $precedingId)      // SOURCE period
                        ->where('tda.target_payroll_period_id', $payroll_period_id) // TARGET = current period being viewed
                        ->where(function ($q) {
                            // Include dates that contribute to the adjustment OR have leave.
                            $q->where(function ($q2) {
                                $q2->where('tda.late', '<>', 0)
                                    ->orWhere('tda.undertime', '<>', 0)
                                    ->orWhere('tda.absent', '<>', 0);
                            })->orWhere(function ($q2) {
                                $q2->where('tda.late_offset', '<>', 0)
                                    ->orWhere('tda.undertime_offset', '<>', 0)
                                    ->orWhere('tda.absent_offset', '<>', 0);
                            })->orWhere('tda.leave', '<>', 0)
                                ->orWhere('td.is_adjusted', 1)
                                ->orWhere(function ($q2) {
                                    $q2->where('tda.is_ot', 1)
                                        ->whereRaw('ISNULL(tda.ot_hours, 0) > 0');
                                });
                        })
                        ->orderBy('tda.date');

                    $rows = $query->get([
                        'tda.date',
                        'tda.am_in',
                        'tda.pm_out',
                        'tda.late',
                        'tda.undertime',
                        'tda.is_ot',
                        'tda.ot_hours',
                        'tda.ot_pay',
                        'tda.leave',
                        'tda.is_ob',
                        'tda.ob_hours',
                        'tda.is_wfh',
                        'td.is_adjusted',
                    ]);

                    if ($rows && $rows->count() > 0) {
                        // Unique list of dates for the header label
                        $adjustment_dates = $rows->pluck('date')
                            ->map(function ($d) {
                                return \Carbon\Carbon::parse($d)->toDateString();
                            })
                            ->unique()
                            ->values()
                            ->all();

                        // Detailed per-date breakdown for UI (late/undertime are day fractions; frontend shows hrs/mins)
                        foreach ($rows as $r) {
                            $date = $r->date ? \Carbon\Carbon::parse($r->date)->toDateString() : null;
                            $hasOt = ($r->is_ot ?? 0) == 1
                                || floatval($r->ot_hours ?? 0) > 0
                                || floatval($r->ot_pay ?? 0) > 0;
                            $hasLeave = floatval($r->leave ?? 0) > 0;
                            $hasOb = ($r->is_ob ?? 0) == 1
                                || floatval($r->ob_hours ?? 0) > 0;

                            $adjustment_details[] = [
                                'date' => $date,
                                'am_in' => $r->am_in,
                                'pm_out' => $r->pm_out,
                                'late' => floatval($r->late ?? 0),
                                'undertime' => floatval($r->undertime ?? 0),
                                'is_wfh' => (int) ($r->is_wfh ?? 0) === 1,
                                'has_ot' => (bool) $hasOt,
                                'has_leave' => (bool) $hasLeave,
                                'has_ob' => (bool) $hasOb,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    // If anything goes wrong building the lists, just fall back to empty arrays
                    $adjustment_dates = [];
                    $adjustment_details = [];
                }
            }

            // Build unified editable records list for EditTimesModal (preceding + current period).
            $edit_time_records = [];

            // Current period: use daily_time_records (time_data for this payroll_period_id)
            foreach ($daily_time_records as $record) {
                $edit_time_records[] = [
                    'source' => 'current',
                    'payroll_period_id' => $payroll_period_id,
                    'time_data_id' => $record->id,
                    'date' => $record->date,
                    'am_in' => $record->am_in,
                    'am_out' => $record->am_out,
                    'break_in' => $record->break_in,
                    'break_out' => $record->break_out,
                    'pm_in' => $record->pm_in,
                    'pm_out' => $record->pm_out,
                    'remarks' => $record->remarks ?? '',
                ];
            }

            // Preceding period: editable snapshot rows come from time_data_adj, but we want to edit
            // the ORIGINAL time_data rows in the preceding period (source_time_data_id).
            if (!empty($adjustment_period_id)) {
                // For preceding period editing, we modify time_data_adj snapshots, not the original time_data rows.
                $precedingEditRows = DB::table('time_data_adj as tda')
                    ->where('tda.employee_id', $id)
                    ->where('tda.payroll_period_id', $adjustment_period_id)      // SOURCE (preceding)
                    ->where('tda.target_payroll_period_id', $payroll_period_id) // TARGET = current
                    ->orderBy('tda.date')
                    ->get([
                        'tda.id as adj_id',
                        'tda.date',
                        'tda.am_in',
                        'tda.am_out',
                        'tda.break_in',
                        'tda.break_out',
                        'tda.pm_in',
                        'tda.pm_out',
                        'tda.remarks',
                    ]);

                foreach ($precedingEditRows as $row) {
                    if (empty($row->adj_id)) {
                        continue;
                    }
                    $edit_time_records[] = [
                        'source' => 'time_data_adj',
                        'payroll_period_id' => $adjustment_period_id,
                        'target_payroll_period_id' => $payroll_period_id,
                        'time_data_id' => null,
                        'adj_id' => $row->adj_id,
                        'date' => $row->date ? \Carbon\Carbon::parse($row->date)->toDateString() : null,
                        'am_in' => $row->am_in,
                        'am_out' => $row->am_out,
                        'break_in' => $row->break_in,
                        'break_out' => $row->break_out,
                        'pm_in' => $row->pm_in,
                        'pm_out' => $row->pm_out,
                        'remarks' => $row->remarks ?? '',
                    ];
                }
            }

            return $this->successResponse([
                'daily_time_records' => $daily_time_records,
                'edit_time_records' => $edit_time_records,
                'totals' => $totals,
                'payroll_period' => $payroll_period_details,
                'scheduled_work_hours' => $scheduled_work_hours,
                'overtime' => [
                    'records' => $overtime_records,
                    'totals' => $ot_totals,
                    'has_ot' => !empty($overtime_records),
                    'has_nd' => $has_nd_schedule // Only show ND if employee has schedule with ND enabled
                ],
                'leave' => [
                    'records' => $leave_records,
                    'has_leave' => !empty($leave_records)
                ],
                'ob' => [
                    'records' => $ob_records,
                    'has_ob' => !empty($ob_records)
                ],
                'holiday' => [
                    'records' => $holiday_records,
                    'has_holiday' => !empty($holiday_records)
                ],
                'work_cancellation' => [
                    'records' => $work_cancellation_records,
                    'has_work_cancellation' => !empty($work_cancellation_records)
                ],
                'adjustment_summary' => $adjustment_summary,
                'adjustment_dates' => $adjustment_dates,
                'adjustment_details' => $adjustment_details
            ], 'Process attendance view data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve process attendance view data: ' . $e->getMessage());
        }
    }

    /**
     * Return days present per employee for a payroll period
     * IMPORTANT: When viewing processed attendance, use saved Days_Present from time_data_summary for consistency
     * Otherwise, calculate from time_data
     * days_present = count of distinct dates where work_hours > 0 OR is_ob = 1
     * This is different from days_covered which includes all days with time_data records (absences, leaves, etc.)
     */
    public function daysPresentSummary($payroll_period_id)
    {
        $callId = uniqid('pa_days_', true);
        $startedAt = microtime(true);

        \Log::info('[ProcessAttendance][TIMING] daysPresentSummary START', [
            'call_id' => $callId,
            'payroll_period_id' => $payroll_period_id,
            'userId' => auth()->id(),
        ]);

        try {
            $payroll_period = DB::table('payroll_periods')->where('id', $payroll_period_id)->first();
            if (!$payroll_period) {
                return $this->errorResponse('Payroll period not found');
            }

            // First, check if we have saved data in time_data_summary
            $summaryRecords = DB::table('time_data_summary')
                ->where('Payroll_Period_ID', $payroll_period_id)
                ->select('Employee_ID', 'Days_Present')
                ->get()
                ->keyBy('Employee_ID');

            // If we have saved records, use Days_Present from time_data_summary for consistency
            if ($summaryRecords->isNotEmpty()) {
                $result = [];
                foreach ($summaryRecords as $employeeId => $record) {
                    // Use Days_Present from time_data_summary for processed employees
                    // This ensures consistency when viewing processed attendance
                    $result[(string)$employeeId] = intval($record->Days_Present ?? 0);
                }

                // Also include employees that might not be in time_data_summary yet (calculate from time_data: work_hours > 0 OR is_ob = 1)
                $calculatedRows = DB::table('time_data')
                    ->select('employee_id', DB::raw("COUNT(DISTINCT CASE WHEN (COALESCE(work_hours, 0) > 0 OR is_ob = 1) THEN date END) as days_present"))
                    ->where('payroll_period_id', $payroll_period_id)
                    ->whereNotIn('employee_id', $summaryRecords->keys()->toArray())
                    ->groupBy('employee_id')
                    ->get();

                foreach ($calculatedRows as $r) {
                    $result[(string)$r->employee_id] = (int)$r->days_present;
                }

                return $this->successResponse($result);
            } else {
                // No saved records - calculate from time_data (for unprocessed periods): work_hours > 0 OR is_ob = 1
                $rows = DB::table('time_data')
                    ->select('employee_id', DB::raw("COUNT(DISTINCT CASE WHEN (COALESCE(work_hours, 0) > 0 OR is_ob = 1) THEN date END) as days_present"))
                    ->where('payroll_period_id', $payroll_period_id)
                    ->groupBy('employee_id')
                    ->get();

                $result = [];
                foreach ($rows as $r) {
                    $result[(string)$r->employee_id] = (int)$r->days_present;
                }

                return $this->successResponse($result);
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get days present');
        } finally {
            $durationMs = round((microtime(true) - $startedAt) * 1000);
            \Log::info('[ProcessAttendance][TIMING] daysPresentSummary END', [
                'call_id' => $callId,
                'duration_ms' => $durationMs,
            ]);
        }
    }


    /**
     * Reprocess all employees for a payroll period
     * Deletes time_data_summary records and reprocesses all employees
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reprocessAll(Request $request)
    {
        set_time_limit(3600); // 1 hour timeout

        // Default path (UI/API): enqueue detached reprocess-all and return immediately.
        // Internal worker path sets run_async_worker=true to execute full sync logic below.
        if (!filter_var($request->input('run_async_worker', false), FILTER_VALIDATE_BOOLEAN)) {
            $validatedStart = $request->validate([
                'payroll_period_id' => 'required|integer|min:1',
                'process_run_id' => 'nullable|string|max:64',
                'preceding_payroll_period_id' => 'nullable|integer|min:1',
                'dev_date_override' => 'nullable|date',
            ], [
                'payroll_period_id.required' => 'Payroll period ID is required',
                'payroll_period_id.integer' => 'Payroll period ID must be a valid integer',
            ]);

            $processRunId = $validatedStart['process_run_id'] ?? (string) Str::uuid();
            $this->upsertAttendanceProcessRun($processRunId, (int) $validatedStart['payroll_period_id'], null);
            $this->setAttendanceProcessRunStatus($processRunId, 'running');

            $this->launchDetachedAttendanceProcess([
                'task' => 'reprocess_all',
                'payroll_period_id' => (int) $validatedStart['payroll_period_id'],
                'process_run_id' => $processRunId,
                'preceding_payroll_period_id' => $validatedStart['preceding_payroll_period_id'] ?? null,
                'dev_date_override' => $validatedStart['dev_date_override'] ?? null,
                'run_async_worker' => true,
                'user_id' => $this->resolveProcessAttendanceUserId(),
            ]);

            return $this->successResponse([
                'process_run_id' => $processRunId,
                'queued' => true,
                'worker_build' => self::ATTENDANCE_WORKER_BUILD,
            ], 'Reprocess-all started.');
        }

        try {
            $processRunId = null;

            // $devOverrideEnabled = app()->environment(['local', 'testing']); // Development-only: uncomment to restrict Dev Date Override to local/testing

            // Validate request
            $validated = $request->validate([
                'payroll_period_id' => 'required|integer|min:1',
                'process_run_id' => 'nullable|string|max:64',
                'preceding_payroll_period_id' => 'nullable|integer|min:1',
            ], [
                'payroll_period_id.required' => 'Payroll period ID is required',
                'payroll_period_id.integer' => 'Payroll period ID must be a valid integer',
            ]);

            $payroll_period_id = $validated['payroll_period_id'];
            $processRunId = $validated['process_run_id'] ?? null;


            // Validate payroll period exists
            $payroll_period = DB::table('payroll_periods')
                ->where('id', $payroll_period_id)
                ->first();

            if (!$payroll_period) {
                return $this->errorResponse("Payroll period not found (ID: {$payroll_period_id})");
            }

            // Track the run so the UI can poll process progress.
            $this->upsertAttendanceProcessRun($processRunId, (int) $payroll_period_id, null);

            // Delete time_data_summary records for this payroll period.
            // GUARD: Only preserve records where Is_Offset = 1 AND there are actual
            // time_data records with applied_offset = 1 for that employee.
            // Records with stale Is_Offset flags (no actual offsets) are deleted and recreated correctly.
            $offsetEmployeeIds = DB::table('time_data')
                ->where('payroll_period_id', $payroll_period_id)
                ->where('applied_offset', 1)
                ->distinct()
                ->pluck('employee_id')
                ->toArray();

            $deletedCount = DB::table('time_data_summary')
                ->where('Payroll_Period_ID', $payroll_period_id)
                ->where(function ($q) use ($offsetEmployeeIds) {
                    // Delete if: Is_Offset is 0/NULL, OR Is_Offset = 1 but employee has no actual offsets
                    $q->where(function ($q2) {
                        $q2->where('Is_Offset', 0)->orWhereNull('Is_Offset');
                    });
                    if (!empty($offsetEmployeeIds)) {
                        $q->orWhere(function ($q2) use ($offsetEmployeeIds) {
                            $q2->where('Is_Offset', 1)
                                ->whereNotIn('Employee_ID', $offsetEmployeeIds);
                        });
                    } else {
                        // No employees have offsets, delete all
                        $q->orWhere('Is_Offset', 1);
                    }
                })
                ->delete();


            $dev_date_override = $request->input('dev_date_override') ?? $request->input('devDateOverride'); // was: $devOverrideEnabled ? (...) : null;
            // if (!$devOverrideEnabled && (!empty($request->input('dev_date_override')) || !empty($request->input('devDateOverride')))) {
            //     \Log::warning('[ProcessAttendance] Dev Date Override ignored (non-dev environment)', [
            //         'payroll_period_id' => $payroll_period_id,
            //         'dev_date_override' => $request->input('dev_date_override') ?? $request->input('devDateOverride'),
            //         'app_env' => app()->environment(),
            //     ]);
            // }

            // Call core processing logic (process all employees, skip already processed check)
            // Reprocess-all defaults force_reprocess to true so approved applications
            // on already-processed dates are re-evaluated by the SP.
            $forceReprocess = filter_var($request->input('force_reprocess', true), FILTER_VALIDATE_BOOLEAN);
            $result = $this->processAttendanceCore($payroll_period_id, null, true, null, $dev_date_override, $forceReprocess, $processRunId);

            if (!$result['success']) {
                $this->setAttendanceProcessRunStatus($processRunId, 'failed');
                return $this->errorResponse($result['message']);
            }

            // Preceding-period adjustments: only when the client explicitly sends
            // preceding_payroll_period_id (same rule as process()). Do not infer from
            // time_data_adj — pre-cutoff/gap snapshots also use that table and would
            // incorrectly trigger SP_ProcessTimeDataAdj when the user chose N/A.
            $precedingPayrollPeriodId = $validated['preceding_payroll_period_id'] ?? null;
            if (!empty($precedingPayrollPeriodId)) {
                $this->processTimeDataAdjForPrecedingPeriod($payroll_period_id, (int) $precedingPayrollPeriodId, $result, $forceReprocess, $processRunId);
            }

            $this->setAttendanceProcessRunStatus($processRunId, 'completed');

            return $this->successResponse($result['data'], $result['message']);
        } catch (\RuntimeException $e) {
            $this->setAttendanceProcessRunStatus($processRunId, 'failed');
            return $this->serverErrorResponse('Failed to reprocess all employees: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->setAttendanceProcessRunStatus($processRunId, 'failed');
            return $this->serverErrorResponse('Failed to reprocess all employees: ' . $e->getMessage());
        }
    }

    public function reprocess(Request $request, $id, $payroll_period_id)
    {
        set_time_limit(3600); // 1 hour timeout

        try {
            // $devOverrideEnabled = app()->environment(['local', 'testing']); // Development-only: uncomment to restrict Dev Date Override to local/testing

            // For single employee reprocess, default to processing only edited dates
            // unless explicitly overridden via request parameter
            $processAllDates = filter_var($request->input('process_all_dates', false), FILTER_VALIDATE_BOOLEAN);
            $updatedAfter = $request->input('updated_after'); // e.g. '2025-11-11 00:58:17.580'

            // Validate employee exists
            $employee = DB::table('employees')
                ->where('id', $id)
                ->where('active', true)
                ->where('is_employee', true)
                ->first();

            if (!$employee) {
                return $this->errorResponse("Employee not found or inactive (ID: {$id})");
            }

            // Validate payroll period exists
            $payroll_period = DB::table('payroll_periods')
                ->where('id', $payroll_period_id)
                ->first();

            if (!$payroll_period) {
                return $this->errorResponse("Payroll period not found (ID: {$payroll_period_id})");
            }

            $dev_date_override = $request->input('dev_date_override') ?? $request->input('devDateOverride'); // was: $devOverrideEnabled ? (...) : null;

            $processRunId = $request->input('process_run_id');

            // Track the run so the UI can poll process progress.
            $this->upsertAttendanceProcessRun($processRunId, (int) $payroll_period_id, (int) $id);
            // if (!$devOverrideEnabled && (!empty($request->input('dev_date_override')) || !empty($request->input('devDateOverride')))) {
            //     \Log::warning('[ProcessAttendance] Dev Date Override ignored (non-dev environment)', [
            //         'payroll_period_id' => $payroll_period_id,
            //         'employee_id' => $id,
            //         'dev_date_override' => $request->input('dev_date_override') ?? $request->input('devDateOverride'),
            //         'app_env' => app()->environment(),
            //     ]);
            // }

            // For single employee reprocess, automatically filter to relevant dates
            // unless process_all_dates=true is explicitly set
            $datesFilter = null;
            if (!$processAllDates) {
                // Automatically find dates that need reprocessing:
                // 1. Dates with is_edited = 1 (manually edited)
                // 2. Dates with NULL work_hours, late, or undertime (incomplete processing)
                // 3. Dates covered by approved leave/OB applications
                // 4. Dates with approved OT applications
                // Note: Don't filter by payroll_period_id to avoid missing rows with mismatched period.
                $baseDates = DB::table('time_data')
                    ->where('employee_id', $id)
                    ->where(function ($query) {
                        $query->where('is_edited', 1)
                            ->orWhereNull('work_hours')
                            ->orWhereNull('late')
                            ->orWhereNull('undertime');
                    });

                // Optional: filter by updated_after timestamp
                if (!empty($updatedAfter)) {
                    try {
                        $ts = \Carbon\Carbon::parse($updatedAfter);
                        $baseDates->where('updated_at', '>=', $ts->format('Y-m-d H:i:s.u'));
                    } catch (\Throwable $e) {
                    }
                }

                $datesSet = [];
                foreach ($baseDates->pluck('date')->toArray() as $d) {
                    $datesSet[(string) $d] = true;
                }

                $attendanceStart = \Carbon\Carbon::parse($payroll_period->attendance_start_date)->startOfDay();
                $attendanceEnd = \Carbon\Carbon::parse($payroll_period->attendance_end_date)->startOfDay();

                $appendDateRange = function ($fromDate, $toDate) use (&$datesSet, $attendanceStart, $attendanceEnd) {
                    try {
                        $from = \Carbon\Carbon::parse($fromDate)->startOfDay();
                        $to = \Carbon\Carbon::parse($toDate)->startOfDay();
                    } catch (\Throwable $e) {
                        return;
                    }

                    if ($to->lt($attendanceStart) || $from->gt($attendanceEnd)) {
                        return;
                    }

                    if ($from->lt($attendanceStart)) {
                        $from = $attendanceStart->copy();
                    }
                    if ($to->gt($attendanceEnd)) {
                        $to = $attendanceEnd->copy();
                    }

                    while ($from->lte($to)) {
                        $datesSet[$from->toDateString()] = true;
                        $from->addDay();
                    }
                };

                // Approved leaves
                $approvedLeaves = DB::table('leave_headers')
                    ->where('employee_id', $id)
                    ->where('approved', 1)
                    ->where('approved_2', 1)
                    ->where('approved_3', 1)
                    ->where(function ($query) use ($attendanceStart, $attendanceEnd) {
                        $query->whereBetween('date_from', [$attendanceStart->toDateString(), $attendanceEnd->toDateString()])
                            ->orWhereBetween('date_to', [$attendanceStart->toDateString(), $attendanceEnd->toDateString()])
                            ->orWhere(function ($q) use ($attendanceStart, $attendanceEnd) {
                                $q->where('date_from', '<=', $attendanceStart->toDateString())
                                    ->where('date_to', '>=', $attendanceEnd->toDateString());
                            });
                    })
                    ->get(['date_from', 'date_to']);
                foreach ($approvedLeaves as $leaveRow) {
                    $appendDateRange($leaveRow->date_from, $leaveRow->date_to);
                }

                // Approved official business applications
                $approvedOb = DB::table('official_business_applications')
                    ->where('employee_id', $id)
                    ->where('approved', 1)
                    ->where('approved_2', 1)
                    ->where('approved_3', 1)
                    ->where(function ($query) use ($attendanceStart, $attendanceEnd) {
                        $query->whereBetween(DB::raw('CAST(date_time_from AS DATE)'), [$attendanceStart->toDateString(), $attendanceEnd->toDateString()])
                            ->orWhereBetween(DB::raw('CAST(date_time_to AS DATE)'), [$attendanceStart->toDateString(), $attendanceEnd->toDateString()])
                            ->orWhere(function ($q) use ($attendanceStart, $attendanceEnd) {
                                $q->whereRaw('CAST(date_time_from AS DATE) <= ?', [$attendanceStart->toDateString()])
                                    ->whereRaw('CAST(date_time_to AS DATE) >= ?', [$attendanceEnd->toDateString()]);
                            });
                    })
                    ->get([
                        DB::raw('CAST(date_time_from AS DATE) as date_from'),
                        DB::raw('CAST(date_time_to AS DATE) as date_to')
                    ]);
                foreach ($approvedOb as $obRow) {
                    $appendDateRange($obRow->date_from, $obRow->date_to);
                }

                // Approved overtime applications (single-date)
                $approvedOtDates = DB::table('overtime_applications')
                    ->where('employee_id', $id)
                    ->where('approved', 1)
                    ->where('approved_2', 1)
                    ->where('approved_3', 1)
                    ->whereBetween('date', [$attendanceStart->toDateString(), $attendanceEnd->toDateString()])
                    ->pluck('date')
                    ->toArray();
                foreach ($approvedOtDates as $otDate) {
                    $datesSet[(string) $otDate] = true;
                }

                $datesFilter = array_keys($datesSet);
                sort($datesFilter);

                if (empty($datesFilter)) {
                    return $this->successResponse([
                        'message' => 'No relevant dates found that need reprocessing for this employee.',
                        'dates_processed' => 0
                    ], 'No dates need reprocessing');
                }
            } else {
            }

            // Call core processing logic (process single employee, skip already processed check), with optional dates filter
            // Single-employee reprocess defaults force_reprocess to true so late-filed
            // applications (e.g., OB/leave/holiday/OT) can re-evaluate a previously processed date.

            $forceReprocess = filter_var($request->input('force_reprocess', true), FILTER_VALIDATE_BOOLEAN);
            $result = $this->processAttendanceCore($payroll_period_id, $id, true, $datesFilter, $dev_date_override, $forceReprocess, $processRunId);

            if (!$result['success']) {
                $this->setAttendanceProcessRunStatus($processRunId, 'failed');

                return $this->errorResponse($result['message']);
            }

            // Preceding-period adjustments: only when explicitly requested (same as process()).
            // Do not infer from time_data_adj — snapshot rows can falsely imply a pair.
            $precedingPayrollPeriodId = $request->input('preceding_payroll_period_id');
            if (!empty($precedingPayrollPeriodId)) {
                $this->processTimeDataAdjForPrecedingPeriod($payroll_period_id, (int) $precedingPayrollPeriodId, $result, $forceReprocess, $processRunId);
            }

            // After successfully reprocessing time data for this employee, regenerate time remarks
            // for the affected dates by calling SP_GenerateTimeRemarks, similar to bulk processing.
            try {
                $datesForRemarks = [];
                if (is_array($datesFilter) && !empty($datesFilter)) {
                    $datesForRemarks = $datesFilter;
                } else {
                    $fromDate = $payroll_period->attendance_start_date ?? null;
                    $toDate = $payroll_period->attendance_end_date ?? null;
                    if ($fromDate && $toDate) {
                        $from = \Carbon\Carbon::parse($fromDate)->startOfDay();
                        $to = \Carbon\Carbon::parse($toDate)->startOfDay();
                        while ($from->lte($to)) {
                            $datesForRemarks[] = $from->toDateString();
                            $from->addDay();
                        }
                    }
                }

                foreach ($datesForRemarks as $dateToProcess) {
                    DB::statement("EXEC [dbo].[SP_GenerateTimeRemarks] @DateToProcess = ?", [$dateToProcess]);
                }
            } catch (\Exception $e) {
                // If remarks regeneration fails, keep the time data changes and continue.
                // Optionally log, but don't treat as hard failure for reprocess.
                \Log::warning('[ProcessAttendance][Reprocess] SP_GenerateTimeRemarks failed for single employee', [
                    'employee_id' => $id,
                    'payroll_period_id' => $payroll_period_id,
                    'dates' => $datesFilter,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->setAttendanceProcessRunStatus($processRunId, 'completed');

            return $this->successResponse($result['data'], $result['message']);
        } catch (\RuntimeException $e) {
            $this->setAttendanceProcessRunStatus($processRunId, 'failed');
            return $this->serverErrorResponse('Failed to reprocess attendance: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->setAttendanceProcessRunStatus($processRunId, 'failed');

            return $this->serverErrorResponse('Failed to reprocess attendance: ' . $e->getMessage());
        }
    }

    public function print($employee_id, $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $companies = DB::table('companies')->get();

            if ($employee_id == 0) {
                $time_data_absent = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->select(
                        'a.employee_id',
                        DB::raw("SUM(isnull(a.absent,0)) as days_absent")
                    )
                    ->where([
                        'a.employee_id' => $employee_id,
                        'a.payroll_period_id' => $payroll_period_id
                    ])
                    ->groupBy(
                        'a.employee_id'
                    )
                    ->get();
                $time_data = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->select(
                        'a.*',
                        // Original (decrypting) name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        //        CONCAT(b.first_name,' ',b.last_name)
                        //    ELSE
                        //        RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                        //    END as name"),
                        DB::raw("CONCAT(b.first_name,' ',b.last_name) as name"),
                    )
                    ->where([
                        'a.payroll_period_id' => $payroll_period_id
                    ])
                    ->orderBy('a.employee_id', 'asc')
                    ->orderBy('a.date', 'asc')
                    ->get();


                $employees = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->join('departments as c', 'b.department_id', '=', 'c.id')
                    ->join('positions as d', 'b.position_id', '=', 'd.id')
                    ->select(
                        'b.id as employee_id',
                        // Original (decrypting) name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        //        CONCAT(b.first_name,' ',b.last_name)
                        //    ELSE
                        //        RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                        //    END as name"),
                        DB::raw("CONCAT(b.first_name,' ',b.last_name) as name"),
                        'b.employee_no',
                        'c.name as department',
                        'd.name as position'
                    )
                    ->distinct()
                    ->where('a.payroll_period_id', $payroll_period_id)
                    ->orderby('b.id', 'asc')
                    ->get();
            } else {
                $time_data_absent = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->select(
                        'a.employee_id',
                        DB::raw("SUM(isnull(a.absent,0)) as days_absent")
                    )
                    ->where([
                        'a.employee_id' => $employee_id,
                        'a.payroll_period_id' => $payroll_period_id
                    ])
                    ->groupBy(
                        'a.employee_id'
                    )
                    ->get();
                $time_data = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->select(
                        'a.*',
                        // Original (decrypting) name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        //        CONCAT(b.first_name,' ',b.last_name)
                        //    ELSE
                        //        RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                        //    END as name"),
                        DB::raw("CONCAT(b.first_name,' ',b.last_name) as name"),
                    )
                    ->where([
                        'a.employee_id' => $employee_id,
                        'a.payroll_period_id' => $payroll_period_id
                    ])
                    ->orderBy('a.employee_id', 'asc')
                    ->orderBy('a.date', 'asc')
                    ->get();

                $employees = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->join('departments as c', 'b.department_id', '=', 'c.id')
                    ->join('positions as d', 'b.position_id', '=', 'd.id')
                    ->leftJoin('branches as br', 'br.id', '=', 'b.branch_id')
                    ->select(
                        'b.id as employee_id',
                        // Original (decrypting) name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        //        CONCAT(b.first_name,' ',b.last_name)
                        //    ELSE
                        //        RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                        //    END as name"),
                        DB::raw("CONCAT(b.first_name,' ',b.last_name) as name"),
                        'b.employee_no',
                        'c.name as department',
                        'd.name as position',
                        DB::raw("CASE WHEN ISNULL(b.branch_id,0) <> 0 THEN
                              CASE WHEN br.is_main_branch = 1 THEN
                                    CAST(1 as INT)
                                   ELSE
                                    CAST(0 as INT)
                               END
                              ELSE
                               CAST(2 AS INT)
                         END AS from_branch")
                    )
                    ->distinct()
                    ->where([
                        'a.employee_id' => $employee_id,
                        'a.payroll_period_id' => $payroll_period_id
                    ])
                    ->orderby('b.id', 'asc')
                    ->get();
            }

            $document_no = DB::table('document_numbers')->where('id', 7)->get();

            if ($document_no->isNotEmpty() && $employees->isNotEmpty()) {
                if ($employees[0]->from_branch == 1) {
                    $footer = [
                        'document_no' => $document_no[0]->co_document_number,
                        'revision' => $document_no[0]->co_revision,
                    ];
                } elseif ($employees[0]->from_branch == 0) {
                    $footer = [
                        'document_no' => $document_no[0]->rd_document_number,
                        'revision' => $document_no[0]->rd_revision,
                    ];
                } else {
                    $footer = [
                        'document_no' => '',
                        'revision' => '',
                    ];
                }
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }

            $pdf = PDF::loadView('process_attendance.process_attendance_report', compact('time_data', 'employees', 'time_data_absent', 'companies', 'footer'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'process_attendance_report_' . $employee_id . '_' . $payroll_period_id . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate process attendance report: ' . $e->getMessage());
        }
    }

    public function offset(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $dataX = $request->all();
            $late_config = $dataX['late_config'];
            $ut_config = $dataX['ut_config'];
            $absent_config = $dataX['absent_config'];
            $is_config = $dataX['is_config'];
            $id = $dataX['id'];
            $payroll_period_id = $dataX['payroll_period_id'];
            $time_data = DB::table('time_data as a')
                ->join('leave_credits as b', 'a.employee_id', '=', 'b.employee_id')
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->select(
                    // Original (decrypting) name selection kept for reference:
                    // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                    //            CONCAT(c.first_name,' ',c.last_name)
                    //        ELSE
                    //            RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                    //        END as name"),
                    DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
                    'b.credits',
                    'a.id',
                    'a.employee_id',
                    'a.applied_offset',
                    DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) as late"),
                    DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) as ut"),
                    DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as absent"),
                    DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as total")
                )
                ->where('payroll_period_id', $payroll_period_id)
                ->where([
                    'active' => true,
                    'is_employee' => true,
                    'b.leave_type_id' => 1,
                    'a.id' => $id
                ])
                ->groupBy(
                    'c.is_encrypted',
                    'c.first_name',
                    'c.last_name',
                    'b.credits',
                    'a.employee_id',
                    'a.applied_offset',
                    'a.id'
                )
                ->get();

            $time_data_details = DB::table('time_data as a')
                ->where('payroll_period_id', $payroll_period_id)
                ->where([
                    'a.id' => $id
                ])
                ->get();



            if ($is_config == "true") {

                $late_offset = $time_data[0]->late <= $late_config ? $time_data_details[0]->late : $late_config * 8;
                $late = $time_data[0]->late <= $late_config ? 0 : ($time_data_details[0]->late - $late_config * 8);
                $ut_offset = $time_data[0]->ut <= $ut_config ? $time_data_details[0]->undertime : $ut_config * 8;
                $ut = $time_data[0]->ut <= $ut_config ? 0 : ($time_data_details[0]->undertime - $ut_config * 8);
                $absent_offset = $time_data[0]->absent <= $absent_config ? $time_data[0]->absent : $absent_config;
                $absent = $time_data[0]->absent <= $absent_config ? 0 : ($time_data[0]->absent - $absent_config);
                $offset_total = ($late_offset / 8) + ($ut_offset / 8) + $absent_offset;

                if (($time_data[0]->applied_offset == 1) || ($offset_total) > ($time_data[0]->credits)) {
                    $leave_credits_update = ($time_data[0]->credits) - 0;
                    $is_offset = 0;
                    $leave_offset = 0;
                } else {

                    $leave_credits_update = ($time_data[0]->credits) - ($offset_total);
                    $leave_offset = $offset_total;
                    DB::table('time_data')
                        ->where([
                            'id' => $id,
                            'payroll_period_id' => $payroll_period_id
                        ])
                        ->update([
                            'applied_offset' => 1,
                            'late_offset' => $late_offset,
                            'undertime_offset' => $ut_offset,
                            'absent_offset' => $absent_offset,
                            'late' => $late,
                            'undertime' => $ut,
                            'absent' => $absent,
                            'leave' => $leave_offset
                        ]);
                }
            } else {

                if (($time_data[0]->applied_offset == 1) || ($time_data[0]->total) > ($time_data[0]->credits)) {
                    $leave_credits_update = floatval($time_data[0]->credits) - 0;
                    $is_offset = 0;
                    $leave_offset = 0;
                } else {
                    $leave_credits_update = floatval($time_data[0]->credits) - floatval($time_data[0]->total);
                    $leave_offset = $time_data[0]->total;
                    DB::table('time_data')
                        ->where([
                            'id' => $id,
                            'payroll_period_id' => $payroll_period_id
                        ])
                        ->update([
                            'applied_offset' => 1,
                            'late' => 0,
                            'undertime' => 0,
                            'absent' => 0,
                            'leave' => $leave_offset
                        ]);
                }
            }

            DB::table('leave_credits')
                ->where([
                    'employee_id' => $time_data[0]->employee_id,
                    'leave_type_id' => 16
                ])
                ->update(['credits' => $leave_credits_update]);

            // Refresh summary amounts so Late/UT/Absent amounts reflect remaining after offset (supports partial offsets)
            try {
                $this->updateTimeDataSummaryAfterOffsetCancel($time_data[0]->employee_id, $payroll_period_id);
            } catch (\Exception $e) {
                \Log::warning('Failed to update time_data_summary after offset(): ' . $e->getMessage());
            }

            return $this->successResponse([
                'leave_credits_update' => $leave_credits_update,
                'leave_offset' => $leave_offset,
                'is_offset' => $is_offset ?? 1
            ], 'Offset applied successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to apply offset: ' . $e->getMessage());
        }
    }

    public function cancel_offset($id, $payroll_period_id)
    {
        try {
            $is_adj = (bool) request()->input('is_adj', false);

            \Log::info('[cancel_offset] START', [
                'id' => $id,
                'payroll_period_id' => $payroll_period_id,
                'is_adj' => $is_adj,
                'id_type' => gettype($id),
                'pp_type' => gettype($payroll_period_id),
            ]);

            if ($is_adj) {
                return $this->cancel_offset_adj_record($id, $payroll_period_id);
            }

            // Get the time_data record
            $time_data_record = DB::table('time_data')
                ->where('id', $id)
                ->where('payroll_period_id', $payroll_period_id)
                ->first();

            \Log::info('[cancel_offset] time_data_record lookup', [
                'found' => !!$time_data_record,
                'applied_offset' => $time_data_record->applied_offset ?? 'N/A',
                'applied_offset_type' => gettype($time_data_record->applied_offset ?? null),
            ]);

            if (!$time_data_record) {
                return $this->errorResponse('Time data record not found');
            }

            // Check if offset is actually applied
            if ($time_data_record->applied_offset != 1) {
                \Log::warning('[cancel_offset] applied_offset != 1, returning error', [
                    'applied_offset_value' => $time_data_record->applied_offset,
                ]);
                return $this->errorResponse('No offset applied to this record');
            }

            $employee_id = $time_data_record->employee_id;
            $date = $time_data_record->date;

            // Get employee's vacation leave credits (leave_type_id = 1 for Vacation Leave)
            $leave_credit = DB::table('leave_credits')
                ->where('employee_id', $employee_id)
                ->where('leave_type_id', 1) // Vacation Leave
                ->first();

            if (!$leave_credit) {
                return $this->errorResponse('Employee leave credits not found');
            }

            // Calculate offset days to restore
            // Get work_hours from schedule to calculate offset days correctly
            $work_hours = 8.0; // Default
            if ($time_data_record->is_shifting == 1) {
                // Get from shift_schedules_details
                $shift_schedule = DB::table('shift_schedules_details')
                    ->where('shift_schedule_id', $time_data_record->work_schedule_id)
                    ->where('shift_date', $date)
                    ->first();
                if ($shift_schedule) {
                    $work_hours = floatval($shift_schedule->work_hours ?? 8.0);
                }
            } else {
                // Get from fix_schedules_details
                $dateCarbon = \Carbon\Carbon::parse($date);
                $dayOfWeek = $dateCarbon->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday
                $dayId = $dayOfWeek == 0 ? 7 : $dayOfWeek; // Convert to 1=Monday, 7=Sunday

                $fix_schedule = DB::table('fix_schedules_details')
                    ->where('fix_schedule_id', $time_data_record->work_schedule_id)
                    ->where('day_id', $dayId)
                    ->first();
                if ($fix_schedule) {
                    $work_hours = floatval($fix_schedule->work_hours ?? 8.0);
                }
            }

            if ($work_hours <= 0) {
                $work_hours = 8.0; // Fallback to 8 if work_hours is 0 or null
            }

            // Offsets currently stored on the record
            // Note: late/undertime/absent and their *_offset counterparts are stored as day fractions, not minutes.
            $late_offset = round(floatval($time_data_record->late_offset ?? 0), 3);
            $undertime_offset = round(floatval($time_data_record->undertime_offset ?? 0), 3);
            $absent_offset = round(floatval($time_data_record->absent_offset ?? 0), 3);

            // When cancelling offset we want to fully restore the original values:
            // original = remaining (current main fields) + offset (stored in *_offset).
            $restored_late = round(floatval($time_data_record->late ?? 0) + $late_offset, 3);
            $restored_undertime = round(floatval($time_data_record->undertime ?? 0) + $undertime_offset, 3);
            $restored_absent = round(floatval($time_data_record->absent ?? 0) + $absent_offset, 3);

            // Vacation leave to restore is based ONLY on the offsetted portion.
            $offset_days = round($late_offset + $undertime_offset + $absent_offset, 3);

            // Restore VL credits (round to 3 decimals)
            $current_credits = floatval($leave_credit->credits ?? 0);
            $new_credits = round($current_credits + $offset_days, 3);

            DB::table('leave_credits')
                ->where('employee_id', $employee_id)
                ->where('leave_type_id', 1) // Vacation Leave
                ->update(['credits' => $new_credits]);

            // Calculate work_hours from late, undertime, and absent using lookup table
            // Formula: work_hours = scheduled_work_hours - (late + undertime + absent in minutes) as day fraction
            $calculated_work_hours = \App\Helpers\Time_Calculation::calculateWorkHoursFromOffset(
                $restored_late,
                $restored_undertime,
                $restored_absent,
                $work_hours
            );

            // Restore values back to original columns
            // When offset was applied: part (or all) of late/undertime/absent was moved to *_offset.
            // Now we restore by setting late/undertime/absent to the full original (remaining + offset)
            $rowsAffected = DB::table('time_data')
                ->where('id', $id)
                ->where('payroll_period_id', $payroll_period_id)
                ->update([
                    'applied_offset' => 0,
                    'late' => $restored_late,
                    'undertime' => $restored_undertime,
                    'absent' => $restored_absent,
                    'late_offset' => 0.000,
                    'undertime_offset' => 0.000,
                    'absent_offset' => 0.000,
                    'work_hours' => $calculated_work_hours, // Calculate work_hours from offset values
                    'remarks' => '' // Clear remarks so they get regenerated
                ]);

            \Log::info('[cancel_offset] UPDATE result', [
                'rows_affected' => $rowsAffected,
                'id' => $id,
                'payroll_period_id' => $payroll_period_id,
                'restored_late' => $restored_late,
                'restored_undertime' => $restored_undertime,
                'restored_absent' => $restored_absent,
                'calculated_work_hours' => $calculated_work_hours,
            ]);

            // Verify the update actually took effect
            $verify = DB::table('time_data')->where('id', $id)->select('applied_offset')->first();
            \Log::info('[cancel_offset] VERIFY after update', [
                'applied_offset_after' => $verify->applied_offset ?? 'RECORD NOT FOUND',
            ]);

            // Regenerate remarks for this specific employee's record
            try {
                // Get the updated record with restored values
                $updated_record = DB::table('time_data')
                    ->where('id', $id)
                    ->first();

                if ($updated_record) {
                    // Generate remarks for this specific employee's record only
                    $this->generateRemarksForRecord($updated_record, $id);
                }
            } catch (\Exception $e) {
                // Continue even if remarks generation fails - the offset cancellation is still successful
            }

            // Update time_data_summary if it exists to reflect the restored absent values
            try {
                $this->updateTimeDataSummaryAfterOffsetCancel($employee_id, $payroll_period_id);
            } catch (\Exception $e) {
                // Log error but don't fail the offset cancellation
                \Log::warning('Failed to update time_data_summary after offset cancellation: ' . $e->getMessage());
            }

            return $this->successResponse([
                'offset_cancelled' => true,
                'vl_restored' => $offset_days,
                'new_credits' => $new_credits
            ], 'Offset cancelled successfully. ' . number_format($offset_days, 3) . ' days restored to vacation leave credits.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to cancel offset: ' . $e->getMessage());
        }
    }

    /**
     * Cancel all offsets in the payroll period for all employees.
     * Loops through all time_data and time_data_adj records with applied_offset=1 and cancels each.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOffsetAll(Request $request)
    {
        try {
            $validated = $request->validate([
                'payroll_period_id' => 'required|integer|min:1',
            ]);
            $payroll_period_id = $validated['payroll_period_id'];
            $allowedEmploymentTypeIds = $this->getPayrollPeriodEmploymentTypeIds((int) $payroll_period_id);

            $timeDataIdsQuery = DB::table('time_data as td')
                ->join('employees as e', 'e.id', '=', 'td.employee_id')
                ->where('td.payroll_period_id', $payroll_period_id)
                ->where('td.applied_offset', 1);
            $this->applyPayrollPeriodEmploymentTypeScope($timeDataIdsQuery, $allowedEmploymentTypeIds, 'e.employment_type_id');
            $time_data_ids = $timeDataIdsQuery->pluck('td.id')->all();

            $time_data_adj_ids = [];
            if (Schema::hasTable('time_data_adj')) {
                $timeDataAdjIdsQuery = DB::table('time_data_adj as tda')
                    ->join('employees as e', 'e.id', '=', 'tda.employee_id')
                    ->where('tda.target_payroll_period_id', $payroll_period_id)
                    ->where('tda.applied_offset', 1);
                $this->applyPayrollPeriodEmploymentTypeScope($timeDataAdjIdsQuery, $allowedEmploymentTypeIds, 'e.employment_type_id');
                $time_data_adj_ids = $timeDataAdjIdsQuery->pluck('tda.id')->all();
            }

            $cancelled = 0;
            $failed = 0;
            $total_vl_restored = 0.0;
            $errors = [];

            foreach ($time_data_ids as $id) {
                $result = $this->executeCancelOffsetForRecord($id, $payroll_period_id, false);
                if ($result['success']) {
                    $cancelled++;
                    $total_vl_restored += floatval($result['vl_restored'] ?? 0);
                } else {
                    $failed++;
                    $errors[] = $result['message'] ?? 'Record ' . $id . ' failed';
                }
            }
            foreach ($time_data_adj_ids as $id) {
                $result = $this->executeCancelOffsetForRecord($id, $payroll_period_id, true);
                if ($result['success']) {
                    $cancelled++;
                    $total_vl_restored += floatval($result['vl_restored'] ?? 0);
                } else {
                    $failed++;
                    $errors[] = $result['message'] ?? 'Adj record ' . $id . ' failed';
                }
            }

            $message = $cancelled > 0
                ? "Cancelled offsets for {$cancelled} record(s); " . number_format($total_vl_restored, 3) . " days restored to VL."
                : "No offsets to cancel.";
            if ($failed > 0) {
                $message .= " {$failed} record(s) failed.";
            }

            return $this->successResponse([
                'records_cancelled' => $cancelled,
                'records_failed' => $failed,
                'total_vl_restored' => round($total_vl_restored, 3),
                'errors' => $errors,
            ], $message);
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed: ' . json_encode($e->errors()));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to cancel all offsets: ' . $e->getMessage());
        }
    }

    /**
     * Execute cancel offset for a single record (time_data or time_data_adj).
     * Used by cancel_offset, cancel_offset_adj_record, and cancelOffsetAll.
     *
     * @param int $id Record ID (time_data.id or time_data_adj.id)
     * @param int $payroll_period_id
     * @param bool $is_adj True for time_data_adj
     * @return array ['success' => bool, 'vl_restored' => float, 'message' => string]
     */
    private function executeCancelOffsetForRecord($id, $payroll_period_id, $is_adj = false)
    {
        if ($is_adj) {
            return $this->executeCancelOffsetAdjRecord($id, $payroll_period_id);
        }
        return $this->executeCancelOffsetTimeDataRecord($id, $payroll_period_id);
    }

    /**
     * @return array ['success' => bool, 'vl_restored' => float, 'message' => string]
     */
    private function executeCancelOffsetTimeDataRecord($id, $payroll_period_id)
    {
        $time_data_record = DB::table('time_data')
            ->where('id', $id)
            ->where('payroll_period_id', $payroll_period_id)
            ->first();
        if (!$time_data_record) {
            return ['success' => false, 'vl_restored' => 0, 'message' => 'Time data record not found'];
        }
        if ($time_data_record->applied_offset != 1) {
            return ['success' => false, 'vl_restored' => 0, 'message' => 'No offset applied to this record'];
        }
        $employee_id = $time_data_record->employee_id;
        $date = $time_data_record->date;
        $leave_credit = DB::table('leave_credits')
            ->where('employee_id', $employee_id)
            ->where('leave_type_id', 1)
            ->first();
        if (!$leave_credit) {
            return ['success' => false, 'vl_restored' => 0, 'message' => 'Employee leave credits not found'];
        }
        $work_hours = 8.0;
        if ($time_data_record->is_shifting == 1) {
            $shift_schedule = DB::table('shift_schedules_details')
                ->where('shift_schedule_id', $time_data_record->work_schedule_id)
                ->where('shift_date', $date)
                ->first();
            if ($shift_schedule) {
                $work_hours = floatval($shift_schedule->work_hours ?? 8.0);
            }
        } else {
            $dateCarbon = \Carbon\Carbon::parse($date);
            $dayOfWeek = $dateCarbon->dayOfWeek;
            $dayId = $dayOfWeek == 0 ? 7 : $dayOfWeek;
            $fix_schedule = DB::table('fix_schedules_details')
                ->where('fix_schedule_id', $time_data_record->work_schedule_id)
                ->where('day_id', $dayId)
                ->first();
            if ($fix_schedule) {
                $work_hours = floatval($fix_schedule->work_hours ?? 8.0);
            }
        }
        if ($work_hours <= 0) {
            $work_hours = 8.0;
        }
        $late_offset = round(floatval($time_data_record->late_offset ?? 0), 3);
        $undertime_offset = round(floatval($time_data_record->undertime_offset ?? 0), 3);
        $absent_offset = round(floatval($time_data_record->absent_offset ?? 0), 3);
        $restored_late = round(floatval($time_data_record->late ?? 0) + $late_offset, 3);
        $restored_undertime = round(floatval($time_data_record->undertime ?? 0) + $undertime_offset, 3);
        $restored_absent = round(floatval($time_data_record->absent ?? 0) + $absent_offset, 3);
        $offset_days = round($late_offset + $undertime_offset + $absent_offset, 3);
        $current_credits = floatval($leave_credit->credits ?? 0);
        $new_credits = round($current_credits + $offset_days, 3);

        DB::table('leave_credits')
            ->where('employee_id', $employee_id)
            ->where('leave_type_id', 1)
            ->update(['credits' => $new_credits]);

        $calculated_work_hours = \App\Helpers\Time_Calculation::calculateWorkHoursFromOffset(
            $restored_late,
            $restored_undertime,
            $restored_absent,
            $work_hours
        );

        DB::table('time_data')
            ->where('id', $id)
            ->where('payroll_period_id', $payroll_period_id)
            ->update([
                'applied_offset' => 0,
                'late' => $restored_late,
                'undertime' => $restored_undertime,
                'absent' => $restored_absent,
                'late_offset' => 0.000,
                'undertime_offset' => 0.000,
                'absent_offset' => 0.000,
                'work_hours' => $calculated_work_hours,
                'remarks' => ''
            ]);

        try {
            $updated_record = DB::table('time_data')->where('id', $id)->first();
            if ($updated_record) {
                $this->generateRemarksForRecord($updated_record, $id);
            }
        } catch (\Exception $e) {
        }

        try {
            $this->updateTimeDataSummaryAfterOffsetCancel($employee_id, $payroll_period_id);
        } catch (\Exception $e) {
        }

        return ['success' => true, 'vl_restored' => $offset_days, 'message' => ''];
    }

    /**
     * @return array ['success' => bool, 'vl_restored' => float, 'message' => string]
     */
    private function executeCancelOffsetAdjRecord($id, $payroll_period_id)
    {
        $record = DB::table('time_data_adj')->where('id', $id)->first();
        if (!$record) {
            $sourceMapping = DB::table('time_data')
                ->select('time_data_adj_id')
                ->where('id', $id)
                ->whereNotNull('time_data_adj_id')
                ->first();
            if ($sourceMapping && !empty($sourceMapping->time_data_adj_id)) {
                $id = (int) $sourceMapping->time_data_adj_id;
                $record = DB::table('time_data_adj')->where('id', $id)->first();
            }
        }
        if (!$record) {
            return ['success' => true, 'vl_restored' => 0, 'message' => 'Already cleared'];
        }
        if (intval($record->applied_offset ?? 0) !== 1) {
            return ['success' => false, 'vl_restored' => 0, 'message' => 'No offset applied'];
        }
        $employee_id = $record->employee_id;
        $date = $record->date;
        $preceding_payroll_period_id = (int) round(floatval($record->payroll_period_id ?? 0));
        $leave_credit = DB::table('leave_credits')
            ->where('employee_id', $employee_id)
            ->where('leave_type_id', 1)
            ->first();
        if (!$leave_credit) {
            return ['success' => false, 'vl_restored' => 0, 'message' => 'Employee leave credits not found'];
        }
        $work_hours = 8.0;
        if (!empty($record->work_schedule_id)) {
            if (!empty($record->is_shifting)) {
                $shift_schedule = DB::table('shift_schedules_details')
                    ->where('shift_schedule_id', $record->work_schedule_id)
                    ->where('shift_date', $date)
                    ->first();
                if ($shift_schedule) {
                    $work_hours = floatval($shift_schedule->work_hours ?? 8.0);
                }
            } else {
                $dateCarbon = \Carbon\Carbon::parse($date);
                $dayOfWeek = $dateCarbon->dayOfWeek;
                $dayId = $dayOfWeek == 0 ? 7 : $dayOfWeek;
                $fix_schedule = DB::table('fix_schedules_details')
                    ->where('fix_schedule_id', $record->work_schedule_id)
                    ->where('day_id', $dayId)
                    ->first();
                if ($fix_schedule) {
                    $work_hours = floatval($fix_schedule->work_hours ?? 8.0);
                }
            }
        }
        if ($work_hours <= 0) {
            $work_hours = 8.0;
        }
        $late_offset = round(floatval($record->late_offset ?? 0), 3);
        $undertime_offset = round(floatval($record->undertime_offset ?? 0), 3);
        $absent_offset = round(floatval($record->absent_offset ?? 0), 3);
        $offset_days = round($late_offset + $undertime_offset + $absent_offset, 3);
        $current_credits = floatval($leave_credit->credits ?? 0);
        $new_credits = round($current_credits + $offset_days, 3);

        DB::table('leave_credits')
            ->where('employee_id', $employee_id)
            ->where('leave_type_id', 1)
            ->update(['credits' => $new_credits]);

        $calculated_work_hours = \App\Helpers\Time_Calculation::calculateWorkHoursFromOffset(
            $late_offset,
            $undertime_offset,
            $absent_offset,
            $work_hours
        );

        DB::table('time_data_adj')
            ->where('id', $id)
            ->where('target_payroll_period_id', $payroll_period_id)
            ->update([
                'applied_offset' => 0,
                'late' => $late_offset,
                'undertime' => $undertime_offset,
                'absent' => $absent_offset,
                'late_offset' => 0.000,
                'undertime_offset' => 0.000,
                'absent_offset' => 0.000,
                'work_hours' => $calculated_work_hours,
            ]);

        try {
            $this->updateTimeDataSummaryAfterOffsetCancel($employee_id, $payroll_period_id);
        } catch (\Exception $e) {
        }
        try {
            if ($preceding_payroll_period_id > 0) {
                $this->updateTimeDataSummaryAdjAfterOffsetChange($employee_id, $preceding_payroll_period_id, $payroll_period_id);
            }
        } catch (\Exception $e) {
        }

        return ['success' => true, 'vl_restored' => $offset_days, 'message' => ''];
    }

    /**
     * Cancel offset for a time_data_adj record (preceding period adjustment targeting this period).
     */
    private function cancel_offset_adj_record($id, $payroll_period_id)
    {
        // Try to look up directly by time_data_adj primary key first.
        $record = DB::table('time_data_adj')
            ->where('id', $id)
            ->first();

        // Fallback: some flows may accidentally send the source time_data.id instead of time_data_adj.id.
        // When that happens, resolve via time_data.time_data_adj_id.
        if (!$record) {
            $sourceMapping = DB::table('time_data')
                ->select('time_data_adj_id')
                ->where('id', $id)
                ->whereNotNull('time_data_adj_id')
                ->first();

            if ($sourceMapping && !empty($sourceMapping->time_data_adj_id)) {
                $resolvedId = (int) $sourceMapping->time_data_adj_id;
                $record = DB::table('time_data_adj')
                    ->where('id', $resolvedId)
                    ->first();
                if ($record) {
                    $id = $resolvedId; // ensure subsequent updates target the correct adj row
                }
            }
        }

        // If we still can't find an adjustment row, treat this as a no-op success instead of a hard error.
        // This avoids blocking the UI when the offset has already been cleared or the mapping is missing.
        if (!$record) {
            return $this->successResponse([
                'offset_cancelled' => false,
                'vl_restored' => 0,
                'new_credits' => null,
            ], 'Adjustment time data record already cleared or not linked; nothing to cancel.');
        }

        if (intval($record->applied_offset ?? 0) !== 1) {
            return $this->errorResponse('No offset applied to this record');
        }

        $employee_id = $record->employee_id;
        $date = $record->date;
        $preceding_payroll_period_id = (int) round(floatval($record->payroll_period_id ?? 0));

        $leave_credit = DB::table('leave_credits')
            ->where('employee_id', $employee_id)
            ->where('leave_type_id', 1)
            ->first();

        if (!$leave_credit) {
            return $this->errorResponse('Employee leave credits not found');
        }

        $work_hours = 8.0;
        if (!empty($record->work_schedule_id)) {
            if (!empty($record->is_shifting)) {
                $shift_schedule = DB::table('shift_schedules_details')
                    ->where('shift_schedule_id', $record->work_schedule_id)
                    ->where('shift_date', $date)
                    ->first();
                if ($shift_schedule) {
                    $work_hours = floatval($shift_schedule->work_hours ?? 8.0);
                }
            } else {
                $dateCarbon = \Carbon\Carbon::parse($date);
                $dayOfWeek = $dateCarbon->dayOfWeek;
                $dayId = $dayOfWeek == 0 ? 7 : $dayOfWeek;
                $fix_schedule = DB::table('fix_schedules_details')
                    ->where('fix_schedule_id', $record->work_schedule_id)
                    ->where('day_id', $dayId)
                    ->first();
                if ($fix_schedule) {
                    $work_hours = floatval($fix_schedule->work_hours ?? 8.0);
                }
            }
        }
        if ($work_hours <= 0) {
            $work_hours = 8.0;
        }

        $late_offset = round(floatval($record->late_offset ?? 0), 3);
        $undertime_offset = round(floatval($record->undertime_offset ?? 0), 3);
        $absent_offset = round(floatval($record->absent_offset ?? 0), 3);
        $offset_days = round($late_offset + $undertime_offset + $absent_offset, 3);

        $current_credits = floatval($leave_credit->credits ?? 0);
        $new_credits = round($current_credits + $offset_days, 3);

        DB::table('leave_credits')
            ->where('employee_id', $employee_id)
            ->where('leave_type_id', 1)
            ->update(['credits' => $new_credits]);

        $calculated_work_hours = \App\Helpers\Time_Calculation::calculateWorkHoursFromOffset(
            $late_offset,
            $undertime_offset,
            $absent_offset,
            $work_hours
        );

        DB::table('time_data_adj')
            ->where('id', $id)
            ->where('target_payroll_period_id', $payroll_period_id)
            ->update([
                'applied_offset' => 0,
                'late' => $late_offset,
                'undertime' => $undertime_offset,
                'absent' => $absent_offset,
                'late_offset' => 0.000,
                'undertime_offset' => 0.000,
                'absent_offset' => 0.000,
                'work_hours' => $calculated_work_hours,
            ]);

        try {
            $this->updateTimeDataSummaryAfterOffsetCancel($employee_id, $payroll_period_id);
        } catch (\Exception $e) {
            \Log::warning('Failed to update time_data_summary after adj offset cancellation: ' . $e->getMessage());
        }

        try {
            if ($preceding_payroll_period_id > 0) {
                $this->updateTimeDataSummaryAdjAfterOffsetChange($employee_id, $preceding_payroll_period_id, $payroll_period_id);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to update time_data_summary_adj after adj offset cancellation: ' . $e->getMessage());
        }

        return $this->successResponse([
            'offset_cancelled' => true,
            'vl_restored' => $offset_days,
            'new_credits' => $new_credits
        ], 'Offset cancelled successfully. ' . number_format($offset_days, 3) . ' days restored to vacation leave credits.');
    }

    /**
     * Get offset data for a specific employee with daily breakdown
     * Returns employee info and daily time_data records with their vacation leave credits
     */
    public function getOffsetData($payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Get all employees with late, undertime, or absences along with their vacation leave credits
            $offset_data = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('leave_credits as lc', function ($join) {
                    $join->on('a.employee_id', '=', 'lc.employee_id')
                        ->where('lc.leave_type_id', '=', 1); // 1 = Vacation Leave
                })
                ->leftJoin('departments as d', 'd.id', '=', 'b.department_id')
                ->select(
                    'a.id',
                    'a.employee_id',
                    'b.employee_no',
                    // Original (decrypting) name selection kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                    //            CONCAT(b.first_name,' ',b.last_name)
                    //        ELSE
                    //            RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                    //        END as name"),
                    DB::raw("CONCAT(b.first_name,' ',b.last_name) as name"),
                    'd.name as department',
                    DB::raw("CONVERT(DECIMAL(18,3), SUM(ISNULL(a.late,0))) as late"),
                    DB::raw("CONVERT(DECIMAL(18,3), SUM(ISNULL(a.undertime,0))) as undertime"),
                    DB::raw("CONVERT(DECIMAL(18,3), SUM(ISNULL(a.absent,0))) as absent"),
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(lc.credits,0)) as leave_credits"),
                    'a.applied_offset'
                )
                ->where('a.payroll_period_id', $payroll_period_id)
                ->where([
                    'b.active' => true,
                    'b.is_employee' => true
                ])
                ->groupBy(
                    'a.id',
                    'a.employee_id',
                    'b.employee_no',
                    'b.is_encrypted',
                    'b.first_name',
                    'b.last_name',
                    'd.name',
                    'lc.credits',
                    'a.applied_offset'
                )
                ->havingRaw('(SUM(ISNULL(a.late,0)) > 0 OR SUM(ISNULL(a.undertime,0)) > 0 OR SUM(ISNULL(a.absent,0)) > 0)')
                ->get();

            return $this->successResponse($offset_data, 'Offset data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get offset data: ' . $e->getMessage());
        }
    }

    /**
     * Get offset status by employee: offsetted (have applied_offset) vs no_offset_yet (have deductions but no offset).
     * Used by the "View Offsetted Employees and Employees with no Offsets yet" dialog.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOffsetEmployeeSummary($payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $allowedEmploymentTypeIds = $this->getPayrollPeriodEmploymentTypeIds((int) $payroll_period_id);

            // Employees with at least one time_data or time_data_adj record with applied_offset = 1
            $offsettedQuery = DB::table('time_data as td')
                ->join('employees as e', 'e.id', '=', 'td.employee_id')
                ->where('td.payroll_period_id', $payroll_period_id)
                ->where('td.applied_offset', 1);
            $this->applyPayrollPeriodEmploymentTypeScope($offsettedQuery, $allowedEmploymentTypeIds, 'e.employment_type_id');
            $offsetted_employee_ids = $offsettedQuery->distinct()->pluck('td.employee_id')->all();
            if (Schema::hasTable('time_data_adj')) {
                $adjOffsettedQuery = DB::table('time_data_adj as tda')
                    ->join('employees as e', 'e.id', '=', 'tda.employee_id')
                    ->where('tda.target_payroll_period_id', $payroll_period_id)
                    ->where('tda.applied_offset', 1);
                $this->applyPayrollPeriodEmploymentTypeScope($adjOffsettedQuery, $allowedEmploymentTypeIds, 'e.employment_type_id');
                $adj_offsetted = $adjOffsettedQuery->distinct()->pluck('tda.employee_id')->all();
                $offsetted_employee_ids = array_values(array_unique(array_merge($offsetted_employee_ids, $adj_offsetted)));
            }

            // Employees with late/undertime/absent > 0 but NO applied_offset on any record (no_offset_yet)
            $noOffsetQuery = DB::table('time_data as td')
                ->join('employees as e', 'e.id', '=', 'td.employee_id')
                ->where('td.payroll_period_id', $payroll_period_id)
                ->where('e.active', true)
                ->where('e.is_employee', true)
                ->where('td.applied_offset', 0)
                ->where(function ($q) {
                    $q->whereRaw('ISNULL(td.late,0) > 0')
                        ->orWhereRaw('ISNULL(td.undertime,0) > 0')
                        ->orWhereRaw('ISNULL(td.absent,0) > 0');
                });
            $this->applyPayrollPeriodEmploymentTypeScope($noOffsetQuery, $allowedEmploymentTypeIds, 'e.employment_type_id');
            $no_offset_ids = $noOffsetQuery->distinct()->pluck('td.employee_id')->all();
            if (Schema::hasTable('time_data_adj')) {
                $adjNoOffsetQuery = DB::table('time_data_adj as tda')
                    ->join('employees as e', 'e.id', '=', 'tda.employee_id')
                    ->where('tda.target_payroll_period_id', $payroll_period_id)
                    ->where('e.active', true)
                    ->where('e.is_employee', true)
                    ->where('tda.applied_offset', 0)
                    ->where(function ($q) {
                        $q->whereRaw('ISNULL(tda.late,0) > 0')
                            ->orWhereRaw('ISNULL(tda.undertime,0) > 0')
                            ->orWhereRaw('ISNULL(tda.absent,0) > 0');
                    });
                $this->applyPayrollPeriodEmploymentTypeScope($adjNoOffsetQuery, $allowedEmploymentTypeIds, 'e.employment_type_id');
                $adj_no_offset = $adjNoOffsetQuery->distinct()->pluck('tda.employee_id')->all();
                $no_offset_ids = array_values(array_unique(array_merge($no_offset_ids, $adj_no_offset)));
            }
            $no_offset_ids = array_values(array_diff($no_offset_ids, $offsetted_employee_ids)); // exclude offsetted

            $buildEmployeeRows = function ($employee_ids) use ($payroll_period_id, $app_key) {
                if (empty($employee_ids)) {
                    return [];
                }
                return DB::table('employees as e')
                    ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                    ->leftJoin('leave_credits as lc', function ($j) {
                        $j->on('e.id', '=', 'lc.employee_id')->where('lc.leave_type_id', '=', 1);
                    })
                    ->select(
                        'e.id as employee_id',
                        'e.employee_no',
                        // Original (decrypting) name selection kept for reference:
                        // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                        //            CONCAT(e.first_name,' ',e.last_name)
                        //        ELSE
                        //            RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                        //        END as name"),
                        DB::raw("CONCAT(e.first_name,' ',e.last_name) as name"),
                        'd.name as department',
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(lc.credits,0)) as leave_credits")
                    )
                    ->whereIn('e.id', $employee_ids)
                    ->where('e.active', true)
                    ->where('e.is_employee', true)
                    ->orderBy('e.employee_no')
                    ->get()
                    ->map(function ($row) {
                        return (array) $row;
                    })
                    ->all();
            };

            return $this->successResponse([
                'offsetted' => $buildEmployeeRows($offsetted_employee_ids),
                'no_offset_yet' => $buildEmployeeRows($no_offset_ids),
            ], 'Offset employee summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get offset employee summary: ' . $e->getMessage());
        }
    }

    /**
     * Get daily offset data for a specific employee
     * Returns employee info and daily time_data records
     */
    public function getEmployeeOffsetDetails($employee_id, $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Get employee info with vacation leave credits
            $employee = DB::table('employees as b')
                ->leftJoin('leave_credits as lc', function ($join) {
                    $join->on('b.id', '=', 'lc.employee_id')
                        ->where('lc.leave_type_id', '=', 1); // 1 = Vacation Leave
                })
                ->leftJoin('departments as d', 'd.id', '=', 'b.department_id')
                ->select(
                    'b.id as employee_id',
                    'b.employee_no',
                    // Original (decrypting) name selection kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                    //            CONCAT(b.first_name,' ',b.last_name)
                    //        ELSE
                    //            RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                    //        END as name"),
                    DB::raw("CONCAT(b.first_name,' ',b.last_name) as name"),
                    'd.name as department',
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(lc.credits,0)) as leave_credits")
                )
                ->where('b.id', $employee_id)
                ->where('b.active', true)
                ->where('b.is_employee', true)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee not found');
            }

            // Get daily time_data records with late, undertime, and absent (current payroll period)
            // Include offset columns to show applied offsets
            // Use DECIMAL(18,3) for 3 decimal precision
            $time_data_daily_records = DB::table('time_data as a')
                ->select(
                    'a.id',
                    'a.date',
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(a.late,0)) as late"),
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(a.undertime,0)) as undertime"),
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(a.absent,0)) as absent"),
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(a.late_offset,0)) as late_offset"),
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(a.undertime_offset,0)) as undertime_offset"),
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(a.absent_offset,0)) as absent_offset"),
                    DB::raw("CONVERT(INT, ISNULL(a.applied_offset,0)) as applied_offset"),
                    DB::raw("0 as is_adj")
                )
                ->where('a.employee_id', $employee_id)
                ->where('a.payroll_period_id', $payroll_period_id)
                ->orderBy('a.date', 'asc')
                ->get();

            // Get time_data_adj records that target this payroll period (preceding period adjustments)
            $time_data_adj_records = DB::table('time_data_adj as a')
                ->select(
                    'a.id',
                    'a.date',
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(a.late,0)) as late"),
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(a.undertime,0)) as undertime"),
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(a.absent,0)) as absent"),
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(a.late_offset,0)) as late_offset"),
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(a.undertime_offset,0)) as undertime_offset"),
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(a.absent_offset,0)) as absent_offset"),
                    DB::raw("CONVERT(INT, ISNULL(a.applied_offset,0)) as applied_offset"),
                    DB::raw("1 as is_adj")
                )
                ->where('a.employee_id', $employee_id)
                ->where('a.target_payroll_period_id', $payroll_period_id)
                ->orderBy('a.date', 'asc')
                ->get();

            // Merge current-period and adjustment records into a single collection for the UI
            $daily_records = $time_data_daily_records->merge($time_data_adj_records)->sortBy('date')->values();

            // Filter records:
            // 1. ALWAYS include ALL records with applied_offset = 1 (for Offsetted Days table) - regardless of any other values
            // 2. Include records with late, undertime, or absent > 0 (for Select Days for Offset table)
            // 3. Include records with offset values > 0 (in case offset was applied but applied_offset flag is 0)
            $filtered_records = $daily_records->filter(function ($record) {
                // CRITICAL: Always include if applied_offset = 1, regardless of late/undertime/absent or offset values
                // This ensures ALL offsetted days are shown, even if offset values are 0
                // Convert to integer for reliable comparison
                $appliedOffsetInt = intval($record->applied_offset ?? 0);
                if ($appliedOffsetInt === 1) {
                    return true; // Always include records with applied_offset = 1
                }
                // Otherwise, include if there are any deductions or offset values (for records without applied_offset)
                return floatval($record->late ?? 0) > 0
                    || floatval($record->undertime ?? 0) > 0
                    || floatval($record->absent ?? 0) > 0
                    || floatval($record->late_offset ?? 0) > 0
                    || floatval($record->undertime_offset ?? 0) > 0
                    || floatval($record->absent_offset ?? 0) > 0;
            })->values();

            // Debug: Log counts to verify all records with applied_offset = 1 are included
            $totalRecords = $daily_records->count();
            $appliedOffsetCount = $daily_records->filter(function ($r) {
                return intval($r->applied_offset ?? 0) === 1;
            })->count();
            $filteredCount = $filtered_records->count();
            $filteredAppliedOffsetCount = $filtered_records->filter(function ($r) {
                return intval($r->applied_offset ?? 0) === 1;
            })->count();


            // Ensure all records with applied_offset = 1 are included
            if ($appliedOffsetCount !== $filteredAppliedOffsetCount) {
            }

            // Calculate total days for each record using lookup table
            $records_with_totals = $filtered_records->map(function ($record) {
                $late = floatval($record->late ?? 0);
                $undertime = floatval($record->undertime ?? 0);
                $absent = floatval($record->absent ?? 0);
                $lateOffset = floatval($record->late_offset ?? 0);
                $undertimeOffset = floatval($record->undertime_offset ?? 0);
                $absentOffset = floatval($record->absent_offset ?? 0);

                // Calculate total days from regular values using lookup table (for records without applied_offset)
                $totalFromRegular = $this->calculateOffsetDaysFromLookup($late, $undertime, $absent);

                // Calculate total days from offset values using lookup table (for records with applied_offset = 1)
                $totalFromOffset = $this->calculateOffsetDaysFromLookup($lateOffset, $undertimeOffset, $absentOffset);

                // Add calculated totals to record
                $record->total_days = $totalFromRegular;
                $record->total_offset_days = $totalFromOffset;

                return $record;
            });

            return $this->successResponse([
                'employee' => $employee,
                'daily_records' => $records_with_totals
            ], 'Employee offset details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get employee offset details: ' . $e->getMessage());
        }
    }

    /**
     * Apply offset for specific time_data records of an employee
     * Deducts late, undertime, and absent from vacation leave credits for selected days
     */
    public function applyOffset(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|integer',
                'payroll_period_id' => 'required|integer',
                // Allow applying offset to time_data, time_data_adj, or both
                'time_data_ids' => 'array',
                'time_data_ids.*' => 'integer',
                'time_data_adj_ids' => 'array',
                'time_data_adj_ids.*' => 'integer',
            ]);

            $employee_id = $validated['employee_id'];
            $payroll_period_id = $validated['payroll_period_id'];
            $time_data_ids = $validated['time_data_ids'] ?? [];
            $time_data_adj_ids = $validated['time_data_adj_ids'] ?? [];
            $app_key = env("APP_KEY", "");

            // Get employee info with current leave credits
            $employee = DB::table('employees as b')
                ->leftJoin('leave_credits as lc', function ($join) {
                    $join->on('b.id', '=', 'lc.employee_id')
                        ->where('lc.leave_type_id', '=', 1); // 1 = Vacation Leave
                })
                ->select(
                    'b.id as employee_id',
                    // Original (decrypting) name selection kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                    //        CONCAT(b.first_name,' ',b.last_name)
                    //    ELSE
                    //        RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                    //    END as name"),
                    DB::raw("CONCAT(b.first_name,' ',b.last_name) as name"),
                    DB::raw("CONVERT(DECIMAL(18,3), ISNULL(lc.credits,0)) as leave_credits")
                )
                ->where('b.id', $employee_id)
                ->where('b.active', true)
                ->where('b.is_employee', true)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee not found');
            }

            // Get the selected time_data records
            // IMPORTANT: Get both main fields and offset fields to handle cases where offset is already applied
            $time_data_records = collect();
            if (!empty($time_data_ids)) {
                $time_data_records = DB::table('time_data')
                    ->select(
                        'id',
                        'date',
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(late,0)) as late"),
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(undertime,0)) as undertime"),
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(absent,0)) as absent"),
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(late_offset,0)) as late_offset"),
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(undertime_offset,0)) as undertime_offset"),
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(absent_offset,0)) as absent_offset"),
                        'applied_offset'
                    )
                    ->where('employee_id', $employee_id)
                    ->where('payroll_period_id', $payroll_period_id)
                    ->whereIn('id', $time_data_ids)
                    ->get();
            }

            // Get selected time_data_adj records (preceding period adjustments targeting this payroll period)
            $time_data_adj_records = collect();
            if (!empty($time_data_adj_ids)) {
                $time_data_adj_records = DB::table('time_data_adj')
                    ->select(
                        'id',
                        'payroll_period_id',
                        'target_payroll_period_id',
                        'date',
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(late,0)) as late"),
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(undertime,0)) as undertime"),
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(absent,0)) as absent"),
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(late_offset,0)) as late_offset"),
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(undertime_offset,0)) as undertime_offset"),
                        DB::raw("CONVERT(DECIMAL(18,3), ISNULL(absent_offset,0)) as absent_offset"),
                        'applied_offset'
                    )
                    ->where('employee_id', $employee_id)
                    ->where('target_payroll_period_id', $payroll_period_id)
                    ->whereIn('id', $time_data_adj_ids)
                    ->get();
            }

            if ($time_data_records->isEmpty() && $time_data_adj_records->isEmpty()) {
                return $this->errorResponse('No time data records found for the selected days');
            }

            // Calculate total offset in days for all selected records (time_data + time_data_adj) using lookup table
            // Note: late and undertime are already stored as day fractions, not hours
            // IMPORTANT: If offset is already applied (applied_offset = 1), use offset fields
            // If offset is not applied (applied_offset = 0), use main fields
            $total_offset_days = 0;
            foreach ($time_data_records as $record) {
                // Skip records that already have offset applied - they're already offsetted
                if ($record->applied_offset == 1) {
                    continue;
                }

                // Use main fields for records without offset applied
                $late = round(floatval($record->late), 3);
                $undertime = round(floatval($record->undertime), 3);
                // CLIENT REQUIREMENT: Absences are not included in Offsets.
                // Keep original absent usage commented for reference.
                // $absent = round(floatval($record->absent), 3);
                $absent = 0;

                // Calculate total using lookup table for each record (late + undertime only)
                $recordTotal = $this->calculateOffsetDaysFromLookup($late, $undertime, $absent);
                $total_offset_days += $recordTotal;
            }

            // Include time_data_adj records in total offset days
            foreach ($time_data_adj_records as $record) {
                if ($record->applied_offset == 1) {
                    continue;
                }

                $late = round(floatval($record->late), 3);
                $undertime = round(floatval($record->undertime), 3);
                // $absent = round(floatval($record->absent), 3);
                $absent = 0;

                $recordTotal = $this->calculateOffsetDaysFromLookup($late, $undertime, $absent);
                $total_offset_days += $recordTotal;
            }
            $total_offset_days = round($total_offset_days, 3);

            // Check if employee has sufficient leave credits
            $current_leave_credits = floatval($employee->leave_credits);
            if ($total_offset_days > $current_leave_credits) {
                return $this->errorResponse('Insufficient vacation leave credits. Required: ' . number_format($total_offset_days, 3) . ' days, Available: ' . number_format($current_leave_credits, 3) . ' days');
            }

            // Update leave credits - deduct the offset amount (round to 3 decimals)
            $new_leave_credits = round($current_leave_credits - $total_offset_days, 3);

            DB::table('leave_credits')
                ->where([
                    'employee_id' => $employee_id,
                    'leave_type_id' => 1
                ])
                ->update(['credits' => $new_leave_credits]);

            // Update each selected time_data record to mark offset as applied
            foreach ($time_data_records as $record) {
                // IMPORTANT: Check if offset is already applied
                // If applied_offset = 1, the values are already in offset fields, so skip
                // If applied_offset = 0, move values from main fields to offset fields
                if ($record->applied_offset == 1) {
                    // Offset already applied - skip to prevent double application
                    continue;
                }

                // Get current values from main fields (these are the values to offset)
                // IMPORTANT: Clear any existing offset values first to prevent accumulation
                $record_late = round(floatval($record->late), 3);
                $record_undertime = round(floatval($record->undertime), 3);
                // CLIENT REQUIREMENT: Absences not included in offsets.
                // $record_absent = round(floatval($record->absent), 3);

                // Only apply offset if there are actual values to offset (late/undertime only)
                if ($record_late > 0 || $record_undertime > 0 /* || $record_absent > 0 */) {
                    DB::table('time_data')
                        ->where('id', $record->id)
                        ->update([
                            'applied_offset' => 1,
                            // Clear any existing offset values first, then set new ones
                            'late_offset' => $record_late,
                            'undertime_offset' => $record_undertime,
                            // 'absent_offset' => $record_absent,
                            'late' => 0.000,
                            'undertime' => 0.000,
                            // 'absent' => 0.000
                        ]);
                }
            }

            // Update each selected time_data_adj record to mark offset as applied
            foreach ($time_data_adj_records as $record) {
                if ($record->applied_offset == 1) {
                    continue;
                }

                $record_late = round(floatval($record->late), 3);
                $record_undertime = round(floatval($record->undertime), 3);
                // $record_absent = round(floatval($record->absent), 3);

                if ($record_late > 0 || $record_undertime > 0 /* || $record_absent > 0 */) {
                    DB::table('time_data_adj')
                        ->where('id', $record->id)
                        ->update([
                            'applied_offset' => 1,
                            'late_offset' => $record_late,
                            'undertime_offset' => $record_undertime,
                            // 'absent_offset' => $record_absent,
                            'late' => 0.000,
                            'undertime' => 0.000,
                            // 'absent' => 0.000
                        ]);
                }
            }

            // Update time_data_summary if it exists to reflect the offset application
            try {
                $this->updateTimeDataSummaryAfterOffsetCancel($employee_id, $payroll_period_id);
            } catch (\Exception $e) {
                // Log error but don't fail the offset application
                \Log::warning('Failed to update time_data_summary after offset application: ' . $e->getMessage());
            }

            // Update time_data_summary_adj amounts if adjustment rows were offsetted
            try {
                if (!$time_data_adj_records->isEmpty()) {
                    $precedingIds = $time_data_adj_records
                        ->pluck('payroll_period_id')
                        ->filter(function ($v) {
                            return $v !== null && $v !== '';
                        })
                        ->map(function ($v) {
                            return (int) round(floatval($v));
                        })
                        ->unique()
                        ->values()
                        ->all();

                    foreach ($precedingIds as $precedingId) {
                        if ($precedingId > 0) {
                            $this->updateTimeDataSummaryAdjAfterOffsetChange($employee_id, $precedingId, $payroll_period_id);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to update time_data_summary_adj after offset application: ' . $e->getMessage());
            }

            return $this->successResponse([
                'employee_name' => $employee->name,
                'days_processed' => count($time_data_records) + count($time_data_adj_records),
                'offset_applied' => $total_offset_days,
                'remaining_leave_credits' => $new_leave_credits
            ], 'Offset applied successfully for ' . count($time_data_records) . ' day(s)');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed: ' . json_encode($e->errors()));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to apply offset: ' . $e->getMessage());
        }
    }

    /**
     * Apply offset for ALL employees in the payroll period in one go.
     * For each employee with tardiness/absences, applies offset to all their unoffset time_data
     * and time_data_adj records (same as Apply Offset but bulk). Skips employees with
     * insufficient VL credits.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyOffsetAll(Request $request)
    {
        try {
            $validated = $request->validate([
                'payroll_period_id' => 'required|integer|min:1',
            ]);
            $payroll_period_id = $validated['payroll_period_id'];
            $app_key = env("APP_KEY", "");
            $allowedEmploymentTypeIds = $this->getPayrollPeriodEmploymentTypeIds((int) $payroll_period_id);

            // Employees with at least one time_data row in period that has deductions and is not offset
            $employeeIdsFromTimeDataQuery = DB::table('time_data as td')
                ->join('employees as e', 'e.id', '=', 'td.employee_id')
                ->where('td.payroll_period_id', $payroll_period_id)
                ->where('e.active', true)
                ->where('e.is_employee', true)
                ->where('td.applied_offset', 0)
                ->where(function ($q) {
                    $q->whereRaw('ISNULL(td.late,0) > 0')
                        ->orWhereRaw('ISNULL(td.undertime,0) > 0')
                        ->orWhereRaw('ISNULL(td.absent,0) > 0');
                });
            $this->applyPayrollPeriodEmploymentTypeScope($employeeIdsFromTimeDataQuery, $allowedEmploymentTypeIds, 'e.employment_type_id');
            $employee_ids_from_time_data = $employeeIdsFromTimeDataQuery->distinct()->pluck('td.employee_id')->all();

            // Employees with at least one time_data_adj row targeting this period, with deductions, not offset
            $employee_ids_from_adj = [];
            if (Schema::hasTable('time_data_adj')) {
                $employeeIdsFromAdjQuery = DB::table('time_data_adj as tda')
                    ->join('employees as e', 'e.id', '=', 'tda.employee_id')
                    ->where('tda.target_payroll_period_id', $payroll_period_id)
                    ->where('e.active', true)
                    ->where('e.is_employee', true)
                    ->where('tda.applied_offset', 0)
                    ->where(function ($q) {
                        $q->whereRaw('ISNULL(tda.late,0) > 0')
                            ->orWhereRaw('ISNULL(tda.undertime,0) > 0')
                            ->orWhereRaw('ISNULL(tda.absent,0) > 0');
                    });
                $this->applyPayrollPeriodEmploymentTypeScope($employeeIdsFromAdjQuery, $allowedEmploymentTypeIds, 'e.employment_type_id');
                $employee_ids_from_adj = $employeeIdsFromAdjQuery->distinct()->pluck('tda.employee_id')->all();
            }

            $all_employee_ids = array_values(array_unique(array_merge($employee_ids_from_time_data, $employee_ids_from_adj)));
            if (empty($all_employee_ids)) {
                return $this->successResponse([
                    'employees_processed' => 0,
                    'employees_skipped' => 0,
                    'total_offset_days' => 0,
                    'skipped' => [],
                    'errors' => [],
                ], 'No employees with unoffset tardiness/absences in this period.');
            }

            $processed = 0;
            $skipped = [];
            $errors = [];
            $total_offset_days_all = 0.0;

            foreach ($all_employee_ids as $employee_id) {
                try {
                    // Get all time_data IDs for this employee in period that are unoffset and have deductions
                    $time_data_ids = DB::table('time_data')
                        ->where('employee_id', $employee_id)
                        ->where('payroll_period_id', $payroll_period_id)
                        ->where('applied_offset', 0)
                        ->where(function ($q) {
                            $q->whereRaw('ISNULL(late,0) > 0')
                                ->orWhereRaw('ISNULL(undertime,0) > 0')
                                ->orWhereRaw('ISNULL(absent,0) > 0');
                        })
                        ->pluck('id')
                        ->all();

                    $time_data_adj_ids = [];
                    if (Schema::hasTable('time_data_adj')) {
                        $time_data_adj_ids = DB::table('time_data_adj')
                            ->where('employee_id', $employee_id)
                            ->where('target_payroll_period_id', $payroll_period_id)
                            ->where('applied_offset', 0)
                            ->where(function ($q) {
                                $q->whereRaw('ISNULL(late,0) > 0')
                                    ->orWhereRaw('ISNULL(undertime,0) > 0')
                                    ->orWhereRaw('ISNULL(absent,0) > 0');
                            })
                            ->pluck('id')
                            ->all();
                    }

                    if (empty($time_data_ids) && empty($time_data_adj_ids)) {
                        continue;
                    }

                    // Build request with input so applyOffset validation and logic receive the data
                    $fakeRequest = Request::create('/process-attendance/apply-offset', 'POST', [
                        'employee_id' => $employee_id,
                        'payroll_period_id' => $payroll_period_id,
                        'time_data_ids' => $time_data_ids,
                        'time_data_adj_ids' => $time_data_adj_ids,
                    ]);
                    $response = $this->applyOffset($fakeRequest);
                    $responseData = $response->getData(true);

                    if ($response->getStatusCode() !== 200 || ($responseData['success'] ?? false) !== true) {
                        $msg = $responseData['message'] ?? 'Apply offset failed';
                        $skipped[] = ['employee_id' => $employee_id, 'reason' => $msg];
                        $errors[] = $msg;
                        continue;
                    }

                    $data = $responseData['data'] ?? [];
                    $processed++;
                    $total_offset_days_all += floatval($data['offset_applied'] ?? 0);
                } catch (\Exception $e) {
                    $employee_name = DB::table('employees')->where('id', $employee_id)->value('first_name');
                    $skipped[] = ['employee_id' => $employee_id, 'reason' => $e->getMessage()];
                    $errors[] = 'Employee ' . ($employee_name ?: $employee_id) . ': ' . $e->getMessage();
                }
            }

            $message = $processed > 0
                ? "Auto-deduct completed. Offset applied for {$processed} employee(s); total " . number_format($total_offset_days_all, 3) . " days deducted from VL."
                : "No offsets were applied.";
            if (count($skipped) > 0) {
                $message .= ' ' . count($skipped) . ' employee(s) skipped (insufficient VL or error).';
            }

            return $this->successResponse([
                'employees_processed' => $processed,
                'employees_skipped' => count($skipped),
                'total_offset_days' => round($total_offset_days_all, 3),
                'skipped' => $skipped,
                'errors' => $errors,
            ], $message);
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed: ' . json_encode($e->errors()));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to auto-deduct: ' . $e->getMessage());
        }
    }

    /**
     * Generate DTR (Daily Time Record) PDF for an employee
     */
    public function viewDTR($employee_id, $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Get payroll period details
            $payroll_period = DB::table('payroll_periods')
                ->where('id', $payroll_period_id)
                ->first();

            if (!$payroll_period) {
                return $this->errorResponse('Payroll period not found');
            }

            $dtrPayrollPeriodIds = $this->getDtrIncludedPayrollPeriodIds($payroll_period);

            // Get daily time records (same query as view method)
            $daily_time_records = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('payroll_periods as e', 'e.id', '=', 'a.payroll_period_id')
                ->leftJoin('employment_types as f', 'f.id', '=', 'b.employment_type_id')
                ->leftJoin('branches as br', 'br.id', '=', 'b.branch_id')
                ->select(
                    'a.id',
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    'b.employee_no',
                    'b.first_name',
                    'b.middle_name',
                    'b.last_name',
                    'c.name as department',
                    'd.name as position',
                    // Original (decrypting) name selection kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                    //            CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                    //        ELSE
                    //            RTRIM(RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')))
                    //        END as name"),
                    DB::raw("CASE WHEN ISNULL(LTRIM(RTRIM(b.middle_name)), '') = '' THEN CONCAT(b.first_name, ' ', b.last_name) ELSE CONCAT(b.first_name, ' ', SUBSTRING(LTRIM(b.middle_name), 1, 1), '. ', b.last_name) END as name"),
                    'a.date',
                    'a.am_in',
                    'a.am_out',
                    'a.break_in',
                    'a.break_out',
                    'a.pm_in',
                    'a.pm_out',
                    'a.work_hours',
                    'a.late',
                    'a.undertime',
                    'a.absent',
                    'a.leave',
                    'a.is_ob',
                    'a.ob_id',
                    'a.is_holiday',
                    'a.holiday_id',
                    'a.holiday_pay',
                    'a.is_ot',
                    'a.ot_id',
                    'a.ot_pay',
                    'a.nd_pay',
                    'a.remarks',
                    'b.is_shifting',
                    'b.work_schedule_id',
                    'a.ot_hours',
                    'e.attendance_start_date',
                    'e.attendance_end_date',
                    'f.name as employment_type',
                    'a.note',
                    'a.late_offset',
                    'a.undertime_offset',
                    'a.absent_offset',
                    'a.applied_offset',
                    'a.excess_hours',
                    'a.nd_start',
                    'a.nd_end',
                    'a.nd_hours',
                    'a.nd_rate',
                    'a.nd_pay',
                    'a.is_adjusted',
                    'a.is_restday',
                    DB::raw("CASE WHEN ISNULL(b.branch_id,0) <> 0 THEN
                              CASE WHEN br.is_main_branch = 1 THEN
                                    CAST(1 as INT)
                                   ELSE
                                    CAST(0 as INT)
                               END
                              ELSE
                               CAST(2 AS INT)
                         END AS from_branch")
                )
                ->whereIn('a.payroll_period_id', $dtrPayrollPeriodIds)
                ->where([
                    'b.id' => $employee_id,
                    'b.active' => true,
                    'b.is_employee' => true
                ])
                ->orderBy('date', 'asc')
                ->get();

            $daily_time_records = $this->applyDtrSecondHalfSnapshotOverrides($daily_time_records, (int) $employee_id, $payroll_period);

            if ($daily_time_records->isEmpty()) {
                return $this->errorResponse('No time records found for this employee');
            }

            foreach ($daily_time_records as $record) {
                if (! empty($record->time_data_work_schedule_id)) {
                    $record->work_schedule_id = $record->time_data_work_schedule_id;
                }
            }

            // Get totals
            $totals = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    DB::raw("sum(a.excess_hours) as excess_hours"),
                    DB::raw("sum(a.ot_hours) as ot"),
                    DB::raw("sum(a.late) as late"),
                    DB::raw("sum(a.undertime) as undertime"),
                    DB::raw("sum(a.leave) as leave"),
                    DB::raw("sum(a.absent) as absent"),
                    DB::raw("CAST(SUM(ISNULL(a.work_hours, 0)) AS DECIMAL(18,4)) as work_hours")
                )
                ->whereIn('a.payroll_period_id', $dtrPayrollPeriodIds)
                ->where([
                    'b.id' => $employee_id,
                    'b.active' => true,
                    'b.is_employee' => true
                ])
                ->groupBy('a.employee_id')
                ->first();

            // Get company information
            $companies = DB::table('companies')->get();

            // Get document number for footer
            $document_no = DB::table('document_numbers')->where('id', 7)->get();

            $from_branch = $daily_time_records->first()->from_branch ?? 2;

            if ($document_no->isNotEmpty()) {
                if ($from_branch == 1) {
                    $footer = [
                        'document_no' => $document_no[0]->co_document_number,
                        'revision' => $document_no[0]->co_revision,
                    ];
                } elseif ($from_branch == 0) {
                    $footer = [
                        'document_no' => $document_no[0]->rd_document_number,
                        'revision' => $document_no[0]->rd_revision,
                    ];
                } else {
                    $footer = [
                        'document_no' => '',
                        'revision' => '',
                    ];
                }
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }

            // Get employee info for header
            $employee = $daily_time_records->first();

            // Get work schedule information for official hours
            $regular_hours = '8:00 AM - 5:00 PM';
            $saturday_hours = '';

            if ($employee->work_schedule_id && !$employee->is_shifting) {
                // Get regular schedule (Monday = 1, Tuesday = 2, ..., Saturday = 6, Sunday = 7)
                // Get Monday schedule (day_id = 1) for regular days
                $regularSchedule = DB::table('fix_schedules_details')
                    ->where([
                        'fix_schedule_id' => $employee->work_schedule_id,
                        'day_id' => 1 // Monday
                    ])
                    ->first();

                if ($regularSchedule) {
                    $amIn = $regularSchedule->am_in ? \Carbon\Carbon::parse($regularSchedule->am_in)->format('h:i A') : '8:00 AM';
                    $pmOut = $regularSchedule->pm_out ? \Carbon\Carbon::parse($regularSchedule->pm_out)->format('h:i A') : '5:00 PM';
                    $regular_hours = $amIn . ' - ' . $pmOut;
                }

                // Get Saturday schedule (day_id = 6) for Saturdays
                $saturdaySchedule = DB::table('fix_schedules_details')
                    ->where([
                        'fix_schedule_id' => $employee->work_schedule_id,
                        'day_id' => 6 // Saturday
                    ])
                    ->first();

                if ($saturdaySchedule) {
                    $satAmIn = $saturdaySchedule->am_in ? \Carbon\Carbon::parse($saturdaySchedule->am_in)->format('h:i A') : '';
                    $satPmOut = $saturdaySchedule->pm_out ? \Carbon\Carbon::parse($saturdaySchedule->pm_out)->format('h:i A') : '';
                    if ($satAmIn && $satPmOut) {
                        $saturday_hours = $satAmIn . ' - ' . $satPmOut;
                    }
                }
            }

            // Create a map of date => time_data record for easier lookup
            $timeDataMap = [];
            foreach ($daily_time_records as $record) {
                $dateKey = \Carbon\Carbon::parse($record->date)->format('Y-m-d');
                $timeDataMap[$dateKey] = $record;
            }

            $tableStart = Carbon::parse($payroll_period->attendance_start_date)->startOfMonth();
            $tableEnd = Carbon::parse($payroll_period->attendance_end_date)->endOfMonth();
            $recordsByDate = [];
            foreach ($daily_time_records as $record) {
                $recordsByDate[\Carbon\Carbon::parse($record->date)->format('Y-m-d')] = $record;
            }
            $scheduleTimesMap = $this->buildDtrScheduleTimesMap($employee, $tableStart, $tableEnd, $recordsByDate);

            // Get incharge signatory from departments.employee_id only
            $approvers = [
                'approver_1' => null
            ];

            $app_key = env("APP_KEY", "");
            $employeeRecord = DB::table('employees')->where('id', $employee_id)->first();

            // In Charge: always from departments.employee_id (links to employees.id)
            $department_head_name = '';
            $department_name = '';
            if ($employeeRecord && $employeeRecord->department_id) {
                $department = DB::table('departments')
                    ->where('id', $employeeRecord->department_id)
                    ->first();

                if ($department) {
                    $department_name = $department->name ?? '';

                    if ($department->employee_id) {
                        $deptHeadResult = DB::table('employees')
                            ->select(
                                DB::raw("CONCAT(first_name,' ',substring(middle_name,1,1),'. ',last_name) as name")
                            )
                            ->where('id', $department->employee_id)
                            ->first();

                        if ($deptHeadResult && isset($deptHeadResult->name)) {
                            $approvers['approver_1'] = [
                                'name' => $deptHeadResult->name,
                                'department_name' => $department_name
                            ];
                        }
                    }
                }
            }

            $payroll_period = $this->normalizeDtrPayrollPeriodForDisplay($payroll_period);

            // Generate PDF
            $pdf = PDF::loadView('process_attendance.dtr_report', compact(
                'daily_time_records',
                'totals',
                'companies',
                'footer',
                'employee',
                'payroll_period',
                'timeDataMap',
                'scheduleTimesMap',
                'regular_hours',
                'saturday_hours',
                'department_head_name',
                'department_name',
                'approvers'
            ))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'portrait');
            $pdfContent = $pdf->output();

            $filename = 'dtr_' . $employee->employee_no . '_' . $payroll_period_id . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate DTR: ' . $e->getMessage());
        }
    }

    /**
     * Get DTR data for template-based PDF preview
     */
    public function getDTRData($employee_id, $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Get payroll period details
            $payroll_period = DB::table('payroll_periods')
                ->where('id', $payroll_period_id)
                ->first();

            if (!$payroll_period) {
                return $this->errorResponse('Payroll period not found');
            }

            $dtrPayrollPeriodIds = $this->getDtrIncludedPayrollPeriodIds($payroll_period);

            // Get daily time records
            $daily_time_records = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('payroll_periods as e', 'e.id', '=', 'a.payroll_period_id')
                ->leftJoin('employment_types as f', 'f.id', '=', 'b.employment_type_id')
                ->leftJoin('branches as br', 'br.id', '=', 'b.branch_id')
                ->select(
                    'a.id',
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    'b.employee_no',
                    'b.first_name',
                    'b.middle_name',
                    'b.last_name',
                    'c.name as department',
                    'd.name as position',
                    // Original (decrypting) name selection kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                    //            CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                    //        ELSE
                    //            RTRIM(RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')))
                    //        END as name"),
                    DB::raw("CASE WHEN ISNULL(LTRIM(RTRIM(b.middle_name)), '') = '' THEN CONCAT(b.first_name, ' ', b.last_name) ELSE CONCAT(b.first_name, ' ', SUBSTRING(LTRIM(b.middle_name), 1, 1), '. ', b.last_name) END as name"),
                    'a.date',
                    'a.am_in',
                    'a.am_out',
                    'a.break_in',
                    'a.break_out',
                    'a.pm_in',
                    'a.pm_out',
                    'a.work_hours',
                    'a.late',
                    'a.undertime',
                    'a.absent',
                    'a.leave',
                    'a.is_ob',
                    'a.ob_id',
                    'a.is_holiday',
                    'a.holiday_id',
                    'a.holiday_pay',
                    'a.is_ot',
                    'a.ot_id',
                    'a.ot_pay',
                    'a.remarks',
                    'b.is_shifting',
                    'b.work_schedule_id',
                    'a.work_schedule_id as time_data_work_schedule_id',
                    'a.ot_hours',
                    'e.attendance_start_date',
                    'e.attendance_end_date',
                    'f.name as employment_type',
                    'a.note',
                    'a.late_offset',
                    'a.undertime_offset',
                    'a.absent_offset',
                    'a.applied_offset',
                    'a.excess_hours',
                    'a.nd_start',
                    'a.nd_end',
                    'a.nd_hours',
                    'a.nd_rate',
                    'a.nd_pay',
                    'a.is_adjusted',
                    'a.is_restday',
                    DB::raw("CASE WHEN ISNULL(b.branch_id,0) <> 0 THEN
                              CASE WHEN br.is_main_branch = 1 THEN
                                    CAST(1 as INT)
                                   ELSE
                                    CAST(0 as INT)
                               END
                              ELSE
                               CAST(2 AS INT)
                         END AS from_branch")
                )
                ->whereIn('a.payroll_period_id', $dtrPayrollPeriodIds)
                ->where([
                    'b.id' => $employee_id,
                    'b.active' => true,
                    'b.is_employee' => true
                ])
                ->orderBy('date', 'asc')
                ->get();

            $daily_time_records = $this->applyDtrSecondHalfSnapshotOverrides($daily_time_records, (int) $employee_id, $payroll_period);

            if ($daily_time_records->isEmpty()) {
                return $this->errorResponse('No time records found for this employee');
            }

            foreach ($daily_time_records as $record) {
                if (! empty($record->time_data_work_schedule_id)) {
                    $record->work_schedule_id = $record->time_data_work_schedule_id;
                }
            }

            // Get totals
            $totals = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    DB::raw("sum(a.excess_hours) as excess_hours"),
                    DB::raw("sum(a.ot_hours) as ot"),
                    DB::raw("sum(a.late) as late"),
                    DB::raw("sum(a.undertime) as undertime"),
                    DB::raw("sum(a.leave) as leave"),
                    DB::raw("sum(a.absent) as absent"),
                    DB::raw("CAST(SUM(ISNULL(a.work_hours, 0)) AS DECIMAL(18,4)) as work_hours")
                )
                ->whereIn('a.payroll_period_id', $dtrPayrollPeriodIds)
                ->where([
                    'b.id' => $employee_id,
                    'b.active' => true,
                    'b.is_employee' => true
                ])
                ->groupBy('a.employee_id')
                ->first();

            // Get company information
            $companies = DB::table('companies')->get();

            // Get document number for footer
            $document_no = DB::table('document_numbers')->where('id', 7)->get();

            $from_branch = $daily_time_records->first()->from_branch ?? 2;

            if ($document_no->isNotEmpty()) {
                if ($from_branch == 1) {
                    $footer = [
                        'document_no' => $document_no[0]->co_document_number,
                        'revision' => $document_no[0]->co_revision,
                    ];
                } elseif ($from_branch == 0) {
                    $footer = [
                        'document_no' => $document_no[0]->rd_document_number,
                        'revision' => $document_no[0]->rd_revision,
                    ];
                } else {
                    $footer = [
                        'document_no' => '',
                        'revision' => '',
                    ];
                }
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }

            // Get employee info for header
            $employee = $daily_time_records->first();

            // Get work schedule information for official hours
            $regular_hours = '8:00 AM - 5:00 PM';
            $saturday_hours = '';

            if ($employee->work_schedule_id && !$employee->is_shifting) {
                // Get regular schedule (Monday = 1, Tuesday = 2, ..., Saturday = 6, Sunday = 7)
                // Get Monday schedule (day_id = 1) for regular days
                $regularSchedule = DB::table('fix_schedules_details')
                    ->where([
                        'fix_schedule_id' => $employee->work_schedule_id,
                        'day_id' => 1 // Monday
                    ])
                    ->first();

                if ($regularSchedule) {
                    $amIn = $regularSchedule->am_in ? \Carbon\Carbon::parse($regularSchedule->am_in)->format('h:i A') : '8:00 AM';
                    $pmOut = $regularSchedule->pm_out ? \Carbon\Carbon::parse($regularSchedule->pm_out)->format('h:i A') : '5:00 PM';
                    $regular_hours = $amIn . ' - ' . $pmOut;
                }

                // Get Saturday schedule (day_id = 6) for Saturdays
                $saturdaySchedule = DB::table('fix_schedules_details')
                    ->where([
                        'fix_schedule_id' => $employee->work_schedule_id,
                        'day_id' => 6 // Saturday
                    ])
                    ->first();

                if ($saturdaySchedule) {
                    $satAmIn = $saturdaySchedule->am_in ? \Carbon\Carbon::parse($saturdaySchedule->am_in)->format('h:i A') : '';
                    $satPmOut = $saturdaySchedule->pm_out ? \Carbon\Carbon::parse($saturdaySchedule->pm_out)->format('h:i A') : '';
                    if ($satAmIn && $satPmOut) {
                        $saturday_hours = $satAmIn . ' - ' . $satPmOut;
                    }
                }
            }

            // Get incharge signatory from departments.employee_id only
            $approvers = [
                'approver_1' => null
            ];

            $app_key = env("APP_KEY", "");
            $employeeRecord = DB::table('employees')->where('id', $employee_id)->first();

            // In Charge: always from departments.employee_id (links to employees.id)
            $department_head_name = '';
            $department_name = '';
            if ($employeeRecord && $employeeRecord->department_id) {
                $department = DB::table('departments')
                    ->where('id', $employeeRecord->department_id)
                    ->first();

                if ($department) {
                    $department_name = $department->name ?? '';

                    if ($department->employee_id) {
                        $deptHeadResult = DB::table('employees')
                            ->select(
                                // Original (decrypting) name selection kept for reference:
                                // DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN
                                //        CONCAT(first_name,' ',substring(middle_name,1,1),'. ',last_name)
                                //    ELSE
                                //        RTRIM(RTRIM([dbo].[ufn_DecryptString](first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](last_name,'$app_key')))
                                //    END as name")
                                DB::raw("CONCAT(first_name,' ',substring(middle_name,1,1),'. ',last_name) as name")
                            )
                            ->where('id', $department->employee_id)
                            ->first();

                        if ($deptHeadResult && isset($deptHeadResult->name)) {
                            $approvers['approver_1'] = [
                                'name' => $deptHeadResult->name,
                                'department_name' => $department_name
                            ];
                        }
                    }
                }
            }

            // Create a map of date => time_data record for easier lookup
            $timeDataMap = [];
            $scheduleDayMap = []; // Map to store schedule day names for each date

            // Get employee work schedule ID
            $employee = $daily_time_records->first();
            $workScheduleId = $employee->work_schedule_id ?? null;

            // Build schedule day map - get day names from schedule_days table
            // For each date, determine the actual day of week and get the corresponding schedule day name
            $startDate = \Carbon\Carbon::parse($payroll_period->attendance_start_date);
            $endDate = \Carbon\Carbon::parse($payroll_period->attendance_end_date);
            if ($this->isDtrSecondHalfPayrollPeriod($payroll_period)) {
                $startDate = $startDate->copy()->startOfMonth();
                $endDate = $endDate->copy()->endOfMonth();
            }
            $currentDate = $startDate->copy();

            // Get all schedule days mapping (day_id => name)
            $allScheduleDays = DB::table('schedule_days')
                ->select('id', 'name')
                ->get();
            $dayIdToName = [];
            foreach ($allScheduleDays as $sd) {
                $dayIdToName[$sd->id] = $sd->name;
            }

            // Carbon dayOfWeek: 0=Sunday, 1=Monday, ..., 6=Saturday
            // schedule_days day_id: 1=Monday, 2=Tuesday, ..., 7=Sunday
            // Map Carbon dayOfWeek to schedule_days day_id
            $carbonToScheduleDayId = [
                0 => 7, // Sunday -> day_id 7
                1 => 1, // Monday -> day_id 1
                2 => 2, // Tuesday -> day_id 2
                3 => 3, // Wednesday -> day_id 3
                4 => 4, // Thursday -> day_id 4
                5 => 5, // Friday -> day_id 5
                6 => 6, // Saturday -> day_id 6
            ];

            while ($currentDate->lte($endDate)) {
                $dateKey = $currentDate->format('Y-m-d');
                $carbonDayOfWeek = $currentDate->dayOfWeek; // 0-6
                $scheduleDayId = $carbonToScheduleDayId[$carbonDayOfWeek] ?? null;

                // Get day name from schedule_days table
                if ($scheduleDayId && isset($dayIdToName[$scheduleDayId])) {
                    $scheduleDayMap[$dateKey] = $dayIdToName[$scheduleDayId];
                } else {
                    // Fallback to standard day name if schedule not found
                    $scheduleDayMap[$dateKey] = $currentDate->format('l'); // Monday, Tuesday, etc.
                }

                $currentDate->addDay();
            }

            foreach ($daily_time_records as $record) {
                $dateKey = \Carbon\Carbon::parse($record->date)->format('Y-m-d');
                $timeDataMap[$dateKey] = (array)$record;
            }

            $tableStart = Carbon::parse($payroll_period->attendance_start_date)->startOfMonth();
            $tableEnd = Carbon::parse($payroll_period->attendance_end_date)->endOfMonth();
            $recordsByDate = [];
            foreach ($daily_time_records as $record) {
                $recordsByDate[\Carbon\Carbon::parse($record->date)->format('Y-m-d')] = $record;
            }
            $scheduleTimesMap = $this->buildDtrScheduleTimesMap($employee, $tableStart, $tableEnd, $recordsByDate);

            // Convert collections to arrays for JSON response
            return $this->successResponse([
                'daily_time_records' => $daily_time_records->map(function ($record) {
                    return (array)$record;
                })->toArray(),
                'totals' => $totals ? (array)$totals : null,
                'companies' => $companies->map(function ($company) {
                    return (array)$company;
                })->toArray(),
                'footer' => $footer,
                'employee' => (array)$employee,
                'payroll_period' => $this->normalizeDtrPayrollPeriodForDisplay($payroll_period),
                'timeDataMap' => $timeDataMap,
                'scheduleTimesMap' => $scheduleTimesMap,
                'scheduleDayMap' => $scheduleDayMap, // Add schedule day names for each date
                'regular_hours' => $regular_hours,
                'saturday_hours' => $saturday_hours,
                'department_head_name' => $department_head_name,
                'department_name' => $department_name,
                'approvers' => $approvers
            ], 'DTR data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get DTR data: ' . $e->getMessage());
        }
    }

    public function offset_details($id, $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $time_data = DB::table('time_data as a')
                ->join('leave_credits as b', 'a.employee_id', '=', 'b.employee_id')
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->select(
                    // Original (decrypting) name selection kept for reference:
                    // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                    //        CONCAT(c.first_name,' ',c.last_name)
                    //    ELSE
                    //        RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                    //    END as name"),
                    DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
                    'b.credits',
                    'a.employee_id',
                    'a.applied_offset',
                    DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) as late"),
                    DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) as ut"),
                    DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as absent"),
                    DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as total")
                )
                ->where('payroll_period_id', $payroll_period_id)
                ->where('b.credits', '>', 0)
                ->where([
                    'active' => true,
                    'is_employee' => true,
                    'b.leave_type_id' => 16,
                    'a.id' => $id
                ])
                ->where(function ($query) {
                    $query->where('a.late_offset', '>', 0);
                    $query->orWhere('a.undertime_offset', '>', 0);
                    $query->orWhere('a.absent_offset', '>', 0);
                })
                ->groupBy(
                    'c.is_encrypted',
                    'c.first_name',
                    'c.last_name',
                    'b.credits',
                    'a.employee_id',
                    'a.applied_offset'
                )
                ->get();

            if (($time_data[0]->applied_offset == 1) || ($time_data[0]->total) > ($time_data[0]->credits)) {
                $leave_credits_update = ($time_data[0]->credits) - 0;
            } else {
                $leave_credits_update = ($time_data[0]->credits) - ($time_data[0]->total);
                $leave_offset = $time_data[0]->total;
            }
            $emp_id = $time_data[0]->employee_id;
            DB::table('leave_credits')->where(['employee_id' => $emp_id, 'leave_type_id' => 16])->update(['credits' => $leave_credits_update]);
            DB::table('time_data')
                ->where([
                    'id' => $id,
                    'payroll_period_id' => $payroll_period_id
                ])
                ->where(function ($query) {
                    $query->where('late', '>', 0);
                    $query->orWhere('undertime', '>', 0);
                    $query->orWhere('absent', '>', 0);
                })
                ->update([
                    'applied_offset' => 1,
                    'late' => 0,
                    'undertime' => 0,
                    'absent' => 0,
                    'leave' => $leave_offset
                ]);
            return $this->successResponse(null, 'You have successfully offsetted tardiness for this period!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to offset tardiness details: ' . $e->getMessage());
        }
    }

    public function cancel_offset_details($id, $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $time_data = DB::table('time_data as a')
                ->join('leave_credits as b', 'a.employee_id', '=', 'b.employee_id')
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->select(
                    // Original (decrypting) name selection kept for reference:
                    // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                    //        CONCAT(c.first_name,' ',c.last_name)
                    //    ELSE
                    //        RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                    //    END as name"),
                    DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
                    'b.credits',
                    'a.employee_id',
                    'a.applied_offset',
                    DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) as late"),
                    DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) as ut"),
                    DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as absent"),
                    DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as total")
                )
                ->where('payroll_period_id', $payroll_period_id)
                ->where('b.credits', '>', 0)
                ->where([
                    'active' => true,
                    'is_employee' => true,
                    'b.leave_type_id' => 16,
                    'a.id' => $id
                ])
                ->where(function ($query) {
                    $query->where('a.late_offset', '>', 0);
                    $query->orWhere('a.undertime_offset', '>', 0);
                    $query->orWhere('a.absent_offset', '>', 0);
                })
                ->groupBy(
                    'c.is_encrypted',
                    'c.first_name',
                    'c.last_name',
                    'b.credits',
                    'a.employee_id',
                    'a.applied_offset'
                )
                ->get();
            if (($time_data[0]->applied_offset == 0)) {
                $leave_credits_update = ($time_data[0]->credits) + 0;
            } else {
                $leave_credits_update = ($time_data[0]->credits) + ($time_data[0]->total);
            }
            $emp_id = $time_data[0]->employee_id;
            DB::table('leave_credits')->where(['employee_id' => $emp_id, 'leave_type_id' => 16])->update(['credits' => $leave_credits_update]);

            // Restore late/undertime/absent from their stored _offset counterparts in a single
            // atomic UPDATE — using DB::raw() so SQL reads the column values directly without
            // a separate SELECT that could silently skip if the _offset > 0 filter doesn't match.
            DB::table('time_data')
                ->where('id', $id)
                ->where('payroll_period_id', $payroll_period_id)
                ->where('applied_offset', 1)
                ->update([
                    'applied_offset' => 0,
                    'late'           => DB::raw('ISNULL(late_offset, 0)'),
                    'undertime'      => DB::raw('ISNULL(undertime_offset, 0)'),
                    'absent'         => DB::raw('ISNULL(absent_offset, 0)'),
                    'leave'          => 0,
                ]);

            return $this->successResponse(null, 'You have successfully cancelled offset details for this period!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to cancel offset details: ' . $e->getMessage());
        }
    }

    private function updateNewStepIncrement($employee_id, $date)
    {

        $step_increment = DB::table('step_increments')
            ->where([
                'employee_id' => $employee_id,
                'is_approved' => true
            ])
            ->whereDate('effectivity_date', $date)
            ->get();

        if ($step_increment->isNotEmpty()) {
            // Update Employee Info
            $data_employee = [
                'salary_step_id' => $step_increment[0]->new_salary_step_id,
                'salary' => $step_increment[0]->new_salary,
                'tax_amount' => $step_increment[0]->new_tax_amount,
                'gsis_amount' => $step_increment[0]->new_gsis_amount,
                'sss_amount' => $step_increment[0]->new_sss_amount,
                'pagibig_amount' => $step_increment[0]->new_pagibig_amount,
                'philhealth_amount' => $step_increment[0]->new_philhealth_amount
            ];

            DB::table('employees')
                ->where('id', $employee_id)
                ->update($data_employee);
        }
    }

    /**
     * Save employee attendance data to time_data_summary table
     */
    public function save(Request $request)
    {
        try {
            $data = $request->validate([
                'payroll_period_id' => 'required|integer',
                'employee_id' => 'required|integer',
                'daily_rate' => 'required|numeric',
                'days_covered' => 'required|integer',
                'total_late' => 'required|numeric',
                'late_amount' => 'required|numeric',
                'total_undertime' => 'required|numeric',
                'undertime_amount' => 'required|numeric',
                'total_absent' => 'required|numeric',
                'absent_amount' => 'required|numeric',
                'total_amount' => 'required|numeric',
                'hours_worked' => 'nullable|numeric',
                'working_hours' => 'nullable|numeric',
                'work_hours' => 'nullable|numeric',
                'overtime_pay' => 'nullable|numeric',
                'adjustment_amount' => 'nullable|numeric',
                'adjustment_period_id' => 'nullable|numeric', // accept "6.000" from frontend; cast to int in code
            ]);

            // Check if record already exists
            $existingRecord = DB::table('time_data_summary')
                ->where('Payroll_Period_ID', $data['payroll_period_id'])
                ->where('Employee_ID', $data['employee_id'])
                ->first();

            // Use adjustment amount from request or existing record
            $adjustmentAmount = isset($data['adjustment_amount']) ? floatval($data['adjustment_amount']) : (float)($existingRecord->Adjustment_Amount ?? 0);
            $adjustmentPeriodId = isset($data['adjustment_period_id'])
                ? (int) round(floatval($data['adjustment_period_id']))
                : (isset($existingRecord->Adjustment_Period_ID) && $existingRecord->Adjustment_Period_ID !== '' ? (int) round(floatval($existingRecord->Adjustment_Period_ID)) : null);
            // Adjustment_Amount_OT_Holiday: prefer time_data_summary_adj (Overtime + Holiday_Pay) when we have an adjustment period, so it's correct even if the row was created after Process (preceding period update may have affected 0 rows)
            $adjustmentAmountOtHoliday = (float)($existingRecord->Adjustment_Amount_OT_Holiday ?? 0);
            if ($adjustmentPeriodId && Schema::hasColumn('time_data_summary', 'Adjustment_Amount_OT_Holiday')) {
                $adjSummary = DB::table('time_data_summary_adj')
                    ->where('Employee_ID', $data['employee_id'])
                    ->where('Preceding_Payroll_Period_ID', $adjustmentPeriodId)
                    ->orderBy('Date_Stamp', 'desc')
                    ->first();
                if ($adjSummary) {
                    $adjustmentAmountOtHoliday = round(floatval($adjSummary->Overtime ?? 0) + floatval($adjSummary->Holiday_Pay ?? 0), 3);
                }
            }

            $hoursWorked = $request->input('hours_worked');
            if ($hoursWorked === null) {
                $result = DB::table('time_data')
                    ->where('payroll_period_id', $data['payroll_period_id'])
                    ->where('employee_id', $data['employee_id'])
                    ->select(DB::raw('CAST(SUM(COALESCE(work_hours, 0)) AS DECIMAL(18,4)) as total_hours'))
                    ->first();
                $hoursWorked = $result ? (float)$result->total_hours : 0.0;
            } else {
                $hoursWorked = (float)$hoursWorked;
            }
            // Keep full precision (4 decimals) for calculations, only round for display
            // $hoursWorked = round(floatval($hoursWorked ?? 0), 2); // REMOVED - keep 4 decimal precision

            $workingHours = $request->input('working_hours');
            $workHours = $request->input('work_hours');
            if ($workingHours === null) {
                $workingHours = $hoursWorked;
            }
            if ($workHours === null) {
                $workHours = $hoursWorked;
            }
            // Keep 3 decimal precision to match time_data_summary schema (decimal 18,3)
            $workingHours = round(floatval($workingHours), 3);
            $workHours = round(floatval($workHours), 3);

            // Recalculate overtime pay from overtime_applications (same logic as getEmployeeAttendanceData) so that
            // Overtime and OT_Pay in time_data_summary are always correct. Do not trust request payload — it may be 0
            // when the UI was built from an existing summary record that had Overtime = 0.
            $payroll_period = DB::table('payroll_periods')->where('id', $data['payroll_period_id'])->first();
            $from_date = $payroll_period->attendance_start_date ?? null;
            $to_date = $payroll_period->attendance_end_date ?? null;
            $overtimePay = 0;
            if ($from_date && $to_date) {
                $ot_applications = DB::table('overtime_applications as a')
                    ->join('overtime_types as b', 'b.id', '=', 'a.overtime_type_id')
                    ->join('employees as e', 'e.id', '=', 'a.employee_id')
                    ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'e.employment_type_id')
                    ->leftJoin(DB::raw("(SELECT x.employee_id,\n                            CASE WHEN ISNULL(ah2.approver_id_2, 0) = 0 THEN 0 ELSE 1 END as has_appr2,\n                            CASE WHEN ISNULL(ah2.approver_id_3, 0) = 0 AND ISNULL(ah2.approver_id_4, 0) = 0 THEN 0 ELSE 1 END as has_appr3\n                        FROM (\n                            SELECT ad.employee_id, ad.approver_id,\n                                   ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn\n                            FROM approver_details ad\n                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3\n                        ) x\n                        INNER JOIN approver_headers ah2 ON ah2.id = x.approver_id\n                        WHERE x.rn = 1) otappr"), 'otappr.employee_id', '=', 'a.employee_id')
                    ->select(
                        'a.overtime_type_id',
                        'a.date',
                        'a.total_hours',
                        'b.rate as overtime_rate',
                        'e.salary',
                        'e.work_schedule_id',
                        'e.is_shifting',
                        'tks.work_days as setup_work_days',
                        'tks.work_hours as setup_work_hours'
                    )
                    ->where('a.employee_id', $data['employee_id'])
                    ->whereBetween('a.date', [$from_date, $to_date])
                    ->where('a.approved', 1)
                    ->where(function ($q) {
                        $q->whereRaw('ISNULL(otappr.has_appr2, 0) = 0')->orWhere('a.approved_2', 1);
                    })
                    ->where(function ($q) {
                        $q->whereRaw('ISNULL(otappr.has_appr3, 0) = 0')->orWhere('a.approved_3', 1);
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved', 0)->orWhereNull('a.disapproved');
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved_2', 0)->orWhereNull('a.disapproved_2');
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved_3', 0)->orWhereNull('a.disapproved_3');
                    })
                    ->where(function ($q) {
                        $q->where('a.is_cancel', 0)->orWhereNull('a.is_cancel');
                    })
                    ->where('a.payroll', 1)
                    ->where('a.service_credits', 0)
                    ->get();
                foreach ($ot_applications as $ot) {
                    $salary = floatval($ot->salary ?? 0);
                    $total_hours = floatval($ot->total_hours ?? 0);
                    $rateMetrics = $this->computeRateMetrics($salary, $ot->setup_work_days ?? null, $ot->setup_work_hours ?? null);
                    $daily_rate = $rateMetrics['daily_rate'];
                    $total_minutes = round($total_hours * 60);
                    $day_fraction = \App\Helpers\Time_Calculation::minutesToDayFraction($total_minutes);
                    $ot_pay = ($daily_rate * $day_fraction) * floatval($ot->overtime_rate ?? 0);
                    $overtimePay += $ot_pay;
                }
            }
            $overtimePay = round(floatval($overtimePay), 3);

            // Calculate Days_Present: count days where work_hours > 0 OR is_ob = 1
            $daysPresentResult = DB::table('time_data')
                ->where('payroll_period_id', $data['payroll_period_id'])
                ->where('employee_id', $data['employee_id'])
                ->select(DB::raw("COUNT(DISTINCT CASE WHEN (COALESCE(work_hours, 0) > 0 OR is_ob = 1) THEN date END) as days_present"))
                ->first();

            $daysPresent = $daysPresentResult ? (int)$daysPresentResult->days_present : 0;

            // Is_adjusted count: distinct adjusted dates excluding rest days (same as list/view)
            $isAdjustedCountResult = DB::table('time_data')
                ->where('payroll_period_id', $data['payroll_period_id'])
                ->where('employee_id', $data['employee_id'])
                ->where('is_adjusted', 1)
                ->select(DB::raw('COUNT(DISTINCT CASE WHEN ISNULL(is_restday, 0) = 0 THEN date END) as is_adjusted_count'))
                ->first();
            $is_adjusted_count = $isAdjustedCountResult ? (int)$isAdjustedCountResult->is_adjusted_count : 0;

            // Get payroll period date range for holiday and leave pay calculation
            $payroll_period = DB::table('payroll_periods')->where('id', $data['payroll_period_id'])->first();
            $from_date = $payroll_period->attendance_start_date ?? null;
            $to_date = $payroll_period->attendance_end_date ?? null;

            // IMPORTANT: Recalculate absent_amount, late_amount, and undertime_amount from time_data
            // This ensures time_data_summary always matches what the UI displays (source of truth)
            // DO NOT trust the request payload values - they may be stale
            // Compute daily rate from employee salary (Salary/22) so stored Daily is correctly rounded to 3 decimals (e.g. 1801.818 not 1801.820)
            $employeeForRate = DB::table('employees as e')
                ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'e.employment_type_id')
                ->where('e.id', $data['employee_id'])
                ->select('e.salary', 'tks.work_days as setup_work_days', 'tks.work_hours as setup_work_hours')
                ->first();
            $rateMetrics = $this->computeRateMetrics(
                floatval($employeeForRate->salary ?? 0),
                $employeeForRate->setup_work_days ?? null,
                $employeeForRate->setup_work_hours ?? null
            );
            $daily_rate_unrounded = $rateMetrics['daily_rate'];
            $hourly_rate = $rateMetrics['hourly_rate'];

            // Get totals using DIRECT SQL SUM (same as UI view() method)
            // This includes ALL records - no exclusion of special days (leave/holiday/OB/restday)
            $totals = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'b.employment_type_id')
                ->select(
                    DB::raw("sum(a.late) as late"),
                    DB::raw("sum(a.undertime) as undertime"),
                    DB::raw("sum(a.absent) as absent"),
                    DB::raw("SUM(ISNULL(a.late_offset, 0)) as late_offset"),
                    DB::raw("SUM(ISNULL(a.undertime_offset, 0)) as undertime_offset"),
                    DB::raw("SUM(ISNULL(a.absent_offset, 0)) as absent_offset")
                )
                ->where([
                    'a.payroll_period_id' => $data['payroll_period_id'],
                    'b.id' => $data['employee_id'],
                    'b.active' => true,
                    'b.is_employee' => true
                ])
                ->groupBy('a.employee_id', 'b.salary', 'tks.work_days', 'tks.work_hours')
                ->first();

            // Get offset fractions (same as UI)
            $lateOffsetFraction = floatval($totals->late_offset ?? 0);
            $undertimeOffsetFraction = floatval($totals->undertime_offset ?? 0);
            $absentOffsetFraction = floatval($totals->absent_offset ?? 0);

            // Determine if this employee has any offset applied in this period (check actual time_data records)
            $hasOffset = DB::table('time_data')
                ->where('employee_id', $data['employee_id'])
                ->where('payroll_period_id', $data['payroll_period_id'])
                ->where('applied_offset', 1)
                ->exists();

            // Get raw (gross/before-offset) day fractions.
            // IMPORTANT: When offset is applied on a time_data record, the main fields (late, undertime, absent)
            // are zeroed out and the original values are moved to *_offset fields.
            // So the gross (before offset) = SUM(main) + SUM(offset).
            // computeNetDayFractionWithOffset(gross, offset) then correctly yields the net.
            $late_day_fraction_raw = floatval($totals->late ?? 0) + $lateOffsetFraction;
            $undertime_day_fraction_raw = floatval($totals->undertime ?? 0) + $undertimeOffsetFraction;
            $absent_days_total_raw = floatval($totals->absent ?? 0) + $absentOffsetFraction;

            // Exclude absent on absent_with_pay holidays (same as view() and reprocess)
            if ($from_date && $to_date) {
                $absent_exclude_holiday = DB::table('holidays as a')
                    ->leftJoin('holiday_types as b', 'b.id', '=', 'a.holiday_type')
                    ->leftJoin('time_data as td', function ($join) use ($data) {
                        $join->on('td.date', '=', 'a.date')
                            ->where('td.employee_id', '=', $data['employee_id'])
                            ->where('td.payroll_period_id', '=', $data['payroll_period_id']);
                    })
                    ->whereBetween('a.date', [$from_date, $to_date])
                    ->where('a.active', 1)
                    ->where(function ($q) {
                        $q->where('b.absent_with_pay', 1)->orWhere('b.absent_with_pay', '1');
                    })
                    ->select(DB::raw('COALESCE(SUM(td.absent), 0) as total'))
                    ->value('total');
                $absent_days_total_raw = max(0, $absent_days_total_raw - floatval($absent_exclude_holiday ?? 0));
            }

            // Apply offsets to get net values (same as UI)
            $lateCalc = $this->computeNetDayFractionWithOffset($late_day_fraction_raw, $lateOffsetFraction);
            $undertimeCalc = $this->computeNetDayFractionWithOffset($undertime_day_fraction_raw, $undertimeOffsetFraction);
            $absentCalc = $this->computeNetDayFractionWithOffset($absent_days_total_raw, $absentOffsetFraction);

            // Get net day fractions (same as UI)
            $late_day_fraction = $lateCalc['net_day_fraction'];
            $undertime_day_fraction = $undertimeCalc['net_day_fraction'];
            $absent_days_total = $absentCalc['net_day_fraction'];

            // Calculate amounts using unrounded daily rate and day fractions (NO ROUNDING during calculation)
            // Same formula as UI: (Daily Rate × day_fraction)
            $late_amount = $late_day_fraction * $daily_rate_unrounded;
            $undertime_amount = $undertime_day_fraction * $daily_rate_unrounded;
            $absent_amount = $absent_days_total * $daily_rate_unrounded;

            // Round only for storage (3 decimals to match schema); UI can still format as needed
            $late_amount = round($late_amount, 3);
            $undertime_amount = round($undertime_amount, 3);
            $absent_amount = round($absent_amount, 3);

            // Calculate holiday pay and leave pay (same logic as view() and getEmployeeAttendanceData() methods)
            // Days with holiday pay must not be included in Days_Present (same rule as time_data_summary_adj).
            // total_holiday_pay = full amount for Total_Amount formula. total_holiday_pay_for_db = only when employee worked (for Holiday_Pay column).
            $total_holiday_pay = 0;
            $total_holiday_pay_for_db = 0; // Holiday_Pay column: only amount when employee worked (both absent_with_pay=0 and =1)
            $paid_holiday_days_count = 0;
            $total_leave_pay = 0;

            if ($from_date && $to_date) {
                $setup_work_hours = 8;
                $empTks = DB::table('employees as e')
                    ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'e.employment_type_id')
                    ->where('e.id', $data['employee_id'])
                    ->value('tks.work_hours');
                if ($empTks !== null && floatval($empTks) > 0) {
                    $setup_work_hours = floatval($empTks);
                }
                $hourly_rate = $setup_work_hours > 0 ? ($daily_rate_unrounded / $setup_work_hours) : ($daily_rate_unrounded / 8);

                // Calculate holiday pay (same formula as view() and reprocess)
                $holiday_records = DB::table('holidays as a')
                    ->leftJoin('holiday_types as b', 'b.id', '=', 'a.holiday_type')
                    ->leftJoin('time_data as td', function ($join) use ($data) {
                        $join->on('td.date', '=', 'a.date')
                            ->where('td.employee_id', '=', $data['employee_id'])
                            ->where('td.payroll_period_id', '=', $data['payroll_period_id']);
                    })
                    ->select(
                        'a.date',
                        'b.rate as holiday_rate',
                        'b.absent_with_pay',
                        DB::raw("COALESCE(td.absent, 0) as absent"),
                        'td.am_in',
                        'td.pm_in',
                        DB::raw("COALESCE(td.work_hours, 0) as work_hours")
                    )
                    ->whereBetween('a.date', [$from_date, $to_date])
                    ->where('a.active', 1)
                    ->get();

                foreach ($holiday_records as $holiday) {
                    $holiday_rate = floatval($holiday->holiday_rate ?? 0);
                    $absent_with_pay_raw = $holiday->absent_with_pay ?? null;
                    $absent_with_pay_bool = false;
                    if ($absent_with_pay_raw !== null) {
                        $absent_with_pay_bool = is_bool($absent_with_pay_raw) ? $absent_with_pay_raw : (bool)intval($absent_with_pay_raw);
                    }

                    $is_present = !empty($holiday->am_in) || !empty($holiday->pm_in);
                    $is_absent = floatval($holiday->absent ?? 0) > 0;
                    $work_hours = floatval($holiday->work_hours ?? 0);

                    if ($absent_with_pay_bool === true) {
                        // absent_with_pay = 1: Do NOT insert into Holiday_Pay or total_holiday_pay. Amount is in Overtime (overtime_type_id = 4 "Holiday Overtime") via overtime_application; OT_Pay insert is correct; not considered Holiday_Pay.
                        // (Previously: amount could be added here for absent_with_pay=1 — that logic is not used; pay is in OT_Pay instead.)
                    } else if ($holiday_rate > 0 && $absent_with_pay_bool === false && $is_present && !$is_absent) {
                        $amt = $daily_rate_unrounded * $holiday_rate;
                        $total_holiday_pay += $amt;
                        $total_holiday_pay_for_db += $amt; // DB: only when worked (present and not absent), and only for absent_with_pay=0 holidays
                        $paid_holiday_days_count++;
                    }
                }

                // Calculate leave pay (only paid leaves - with_pay = 1.00)
                $leave_records = DB::table('leave_headers as a')
                    ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                    ->where('a.employee_id', $data['employee_id'])
                    ->whereBetween('b.leave_date', [$from_date, $to_date])
                    ->where('a.approved', 1)
                    ->where('a.approved_2', 1)
                    ->where('a.approved_3', 1)
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('a.disapproved', 0)->orWhereNull('a.disapproved');
                        })
                            ->where(function ($q2) {
                                $q2->where('a.disapproved_2', 0)->orWhereNull('a.disapproved_2');
                            })
                            ->where(function ($q2) {
                                $q2->where('a.disapproved_3', 0)->orWhereNull('a.disapproved_3');
                            });
                    })
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('a.is_cancel', 0)->orWhereNull('a.is_cancel');
                        })
                            ->where(function ($q2) {
                                $q2->where('a.is_cancel_2', 0)->orWhereNull('a.is_cancel_2');
                            })
                            ->where(function ($q2) {
                                $q2->where('a.is_cancel_3', 0)->orWhereNull('a.is_cancel_3');
                            });
                    })
                    ->select('b.with_pay')
                    ->get();

                foreach ($leave_records as $leave) {
                    $with_pay = floatval($leave->with_pay ?? 0);
                    if ($with_pay == 1.00) {
                        $total_leave_pay += $daily_rate_unrounded;
                    }
                }
            }

            // Recompute Total_Amount using the SAME formula as the UI (Attendance Details modal):
            // Preceding period adj (deductions − Adj. OT − Adj. Holiday) is DEDUCTED from total; negative Adjustment_Amount = addition.
            // Days_Present excludes days that were paid as holiday (no double-counting; same rule as time_data_summary_adj).
            $daily_rate = $daily_rate_unrounded; // Already computed from salary (Salary/22) above
            $is_adjusted_amount = $is_adjusted_count * $daily_rate;
            $daysPresentExclHoliday = max(0, $daysPresent - $paid_holiday_days_count);
            $computed_total_amount = max(0, $daily_rate * $daysPresentExclHoliday
                - $late_amount
                - $undertime_amount
                + $overtimePay
                + round($total_holiday_pay, 3)
                + round($total_leave_pay, 3)
                + $is_adjusted_amount
                - $adjustmentAmount
                + $adjustmentAmountOtHoliday);
            // Final total stored with 3-decimal precision
            $computed_total_amount = round($computed_total_amount, 3);

            // Calculate Total_Deduction: sum of Absent_Amount + Late_Amount + Undertime_Amount
            // Use RECALCULATED amounts from time_data, not request payload
            $total_deduction = round($absent_amount + $late_amount + $undertime_amount, 3);

            $summaryData = [
                'Payroll_Period_ID' => $data['payroll_period_id'],
                'Employee_ID' => $data['employee_id'],
                // Store summary hours with 3-decimal precision to match schema
                'Hours_Worked' => round($hoursWorked, 3),
                // Store daily rate with 3-decimal precision (Salary/22 rounded to 3 decimals, e.g. 1801.818)
                'Daily' => round($daily_rate_unrounded, 3),
                'Hourly_Rate' => round($hourly_rate, 3),
                'Late' => round($late_day_fraction, 3), // Net day fraction (after offset)
                'Late_Amount' => $late_amount, // Net amount (after offset) for payroll deductions
                'Undertime' => round($undertime_day_fraction, 3), // Net day fraction (after offset)
                'Undertime_Amount' => $undertime_amount, // Net amount (after offset) for payroll deductions
                'Absent' => round($absent_days_total, 3), // Net absent days (after offset)
                'Absent_Amount' => $absent_amount, // Net amount (after offset) for payroll deductions
                'Total_Deduction' => $total_deduction,
                'Total_Amount' => $computed_total_amount,
                'Overtime' => $overtimePay,
                'OT_Pay' => round($overtimePay, 3), // Overtime amount stored in OT_Pay column (same as Overtime)
                'Date_Stamp' => now(),
                'Encoder_ID' => $this->resolveProcessAttendanceEncoderId(),
                'Is_Offset' => $hasOffset ? true : false,
                'Days_Covered' => $data['days_covered'],
                'Days_Present' => (int)$daysPresent, // Count of dates where work_hours > 0 OR is_ob = 1; Total_Amount uses daysPresentExclHoliday internally
                'Adjustment_Amount' => round($adjustmentAmount, 3),
                'Adjustment_Period_ID' => $adjustmentPeriodId,
                'Gross_Pay' => round(round($daily_rate_unrounded, 3) * ($daysPresent + $is_adjusted_count) + $overtimePay + ($total_holiday_pay_for_db ?? 0), 3),
                'Adjustment_Amount_OT_Holiday' => round($adjustmentAmountOtHoliday, 3),
                'Holiday_Pay' => round($total_holiday_pay_for_db ?? 0, 3),
            ];

            if ($existingRecord) {
                // GUARD: Only preserve offset values if BOTH the existing record has Is_Offset = 1
                // AND there are actual time_data records with applied_offset = 1.
                // If Is_Offset was incorrectly set (no actual offsets), allow the update to correct it.
                if (!empty($existingRecord->Is_Offset) && $hasOffset) {
                    $preservedData = $summaryData;
                    // Keep offset-calculated deduction fields from existing record
                    $preservedData['Late'] = floatval($existingRecord->Late ?? 0);
                    $preservedData['Late_Amount'] = floatval($existingRecord->Late_Amount ?? 0);
                    $preservedData['Undertime'] = floatval($existingRecord->Undertime ?? 0);
                    $preservedData['Undertime_Amount'] = floatval($existingRecord->Undertime_Amount ?? 0);
                    $preservedData['Absent'] = floatval($existingRecord->Absent ?? 0);
                    $preservedData['Absent_Amount'] = floatval($existingRecord->Absent_Amount ?? 0);
                    $preservedData['Total_Deduction'] = floatval($existingRecord->Total_Deduction ?? 0);
                    $preservedData['Is_Offset'] = 1;
                    DB::table('time_data_summary')
                        ->where('Payroll_Period_ID', $data['payroll_period_id'])
                        ->where('Employee_ID', $data['employee_id'])
                        ->update($preservedData);
                    $message = 'Employee attendance data updated successfully (offset values preserved)';
                    return $this->successResponse(null, $message);
                }

                // Update existing record so Total_Amount (and other fields) stay in sync with UI
                DB::table('time_data_summary')
                    ->where('Payroll_Period_ID', $data['payroll_period_id'])
                    ->where('Employee_ID', $data['employee_id'])
                    ->update($summaryData);
                $message = 'Employee attendance data updated successfully';
                return $this->successResponse(null, $message);
            } else {
                // Insert new record
                DB::table('time_data_summary')->insert($summaryData);
                $message = 'Employee attendance data saved successfully';
                return $this->successResponse(null, $message);
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save employee attendance data: ' . $e->getMessage());
        }
    }

    /**
     * Single source of truth for daily rate and hourly rate.
     * Compute monthly work metrics and derived daily/hourly rates based on salary and employment type setup.
     * All daily-rate-based calculations (OT, deductions, holiday, leave, total amount) should use this method.
     *
     * @param float $salary
     * @param float|null $workDaysSetting  Weekly work days from time_keeping_setups
     * @param float|null $workHoursSetting Weekly work hours from time_keeping_setups
     * @return array{monthly_work_days: float, monthly_work_hours: float, daily_rate: float, hourly_rate: float}
     */
    private function computeRateMetrics(float $salary, $workDaysSetting = null, $workHoursSetting = null): array
    {
        $workDaysSetting = $workDaysSetting !== null ? floatval($workDaysSetting) : 0.0;
        $workHoursSetting = $workHoursSetting !== null ? floatval($workHoursSetting) : 0.0;

        // Derive monthly equivalent working days/hours (default to 20 days / 160 hours)
        $monthlyWorkDays = 0.0;
        if ($workDaysSetting > 0) {
            // Work days are stored weekly -> multiply by 4 weeks
            $monthlyWorkDays = $workDaysSetting * 4.0;
        } elseif ($workHoursSetting > 0) {
            // Convert weekly work hours to monthly days (assuming 8 hours per day)
            $monthlyWorkDays = ($workHoursSetting * 4.0) / 8.0;
        }
        if ($monthlyWorkDays <= 0) {
            $monthlyWorkDays = 20.0;
        }

        $monthlyWorkHours = 0.0;
        if ($workHoursSetting > 0) {
            $monthlyWorkHours = $workHoursSetting * 4.0;
        } else {
            $monthlyWorkHours = $monthlyWorkDays * 8.0;
        }

        // Daily rate is standard: Salary / 22 (regardless of workdays in a month)
        $daily_rate = $salary / 22.0;

        // Hourly rate calculation remains based on monthly work hours
        $hourly_rate = $monthlyWorkHours > 0 ? ($salary / $monthlyWorkHours) : ($daily_rate / 8.0);

        return [
            'monthly_work_days' => $monthlyWorkDays,
            'monthly_work_hours' => $monthlyWorkHours,
            'daily_rate' => $daily_rate,
            'hourly_rate' => $hourly_rate
        ];
    }

    /**
     * Calculate total offset days from late, undertime, and absent using MinutesToDayFraction lookup table.
     * Late and undertime are stored as day fractions, absent is stored as days.
     *
     * @param float $late Day fraction value (e.g., 0.017 = 8 minutes)
     * @param float $undertime Day fraction value (e.g., 0.002 = 1 minute)
     * @param float $absent Days value (e.g., 1.000 = 1 day)
     * @return float Total days calculated using lookup table
     */
    private function calculateOffsetDaysFromLookup(float $late, float $undertime, float $absent): float
    {
        // Convert late day fraction to minutes
        $lateMinutes = \App\Helpers\Time_Calculation::dayFractionToMinutes($late);

        // Convert undertime day fraction to minutes
        $undertimeMinutes = \App\Helpers\Time_Calculation::dayFractionToMinutes($undertime);

        // CLIENT REQUIREMENT (2026-03): Absences are no longer included in Offsets.
        // Keep the original absent handling commented for reference.
        // // Convert absent days to minutes (1 day = 8 hours = 480 minutes)
        // $absentMinutes = round($absent * 480);
        $absentMinutes = 0;

        // Total minutes (late + undertime only; absences excluded from offsets per client requirement)
        $totalMinutes = $lateMinutes + $undertimeMinutes + $absentMinutes;

        // Convert total minutes back to days using database function
        // Use the same logic as fn_MinutesToDayFraction
        $hours = intval($totalMinutes / 60);
        $remainingMinutes = intval($totalMinutes % 60);
        $hoursFraction = $hours * 0.125;

        // Get minutes fraction from lookup table
        $minuteFraction = DB::table('MinutesToDayFraction')
            ->where('minutes', $remainingMinutes)
            ->value('day_fraction');

        $minutesFraction = floatval($minuteFraction ?? 0);

        // Total days = hours fraction + minutes fraction
        return round($hoursFraction + $minutesFraction, 3);
    }

    /**
     * Compute net day fraction/minutes after applying offsets.
     *
     * @param float $baseDayFraction The original day fraction value (e.g., total late)
     * @param float $offsetDayFraction The offset day fraction applied (e.g., total late offset)
     * @return array{net_day_fraction: float, net_minutes: float, offset_minutes: float, offset_day_fraction: float}
     */
    private function computeNetDayFractionWithOffset($baseDayFraction, $offsetDayFraction): array
    {
        $baseMinutes = \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($baseDayFraction ?? 0));
        $offsetMinutes = \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($offsetDayFraction ?? 0));
        $netMinutes = max($baseMinutes - $offsetMinutes, 0);

        return [
            'net_day_fraction' => \App\Helpers\Time_Calculation::minutesToDayFraction($netMinutes),
            'net_minutes' => $netMinutes,
            'offset_minutes' => $offsetMinutes,
            'offset_day_fraction' => \App\Helpers\Time_Calculation::minutesToDayFraction($offsetMinutes)
        ];
    }

    /**
     * Calculate work_hours for a single time_data record
     * This is used to recalculate work_hours for a specific employee without processing all employees
     */
    private function calculateWorkHoursForRecord($record)
    {
        // Parse time strings to minutes since midnight
        $parseTimeToMinutes = function ($timeStr) {
            if (!$timeStr) return null;
            $parts = explode(':', $timeStr);
            if (count($parts) < 2) return null;
            return (intval($parts[0]) * 60) + intval($parts[1]);
        };

        // Calculate hours between two times
        $hoursBetween = function ($startMinutes, $endMinutes) {
            if ($startMinutes === null || $endMinutes === null) {
                return 0;
            }
            // Handle same-day times (end >= start) or overnight (end < start)
            $diffMinutes = $endMinutes >= $startMinutes
                ? ($endMinutes - $startMinutes)
                : ((24 * 60 - $startMinutes) + $endMinutes);
            return $diffMinutes > 0 ? $diffMinutes / 60.0 : 0;
        };

        $amInMinutes = $parseTimeToMinutes($record->am_in ?? null);
        $amOutMinutes = $parseTimeToMinutes($record->am_out ?? null);
        $pmInMinutes = $parseTimeToMinutes($record->pm_in ?? null);
        $pmOutMinutes = $parseTimeToMinutes($record->pm_out ?? null);

        // Work hours = AM segment + PM segment, ignore breaks
        $morningHours = $hoursBetween($amInMinutes, $amOutMinutes);
        $afternoonHours = $hoursBetween($pmInMinutes, $pmOutMinutes);
        // Round to 4 decimal places to match database precision (decimal 8,4)
        $computedWorkHours = round($morningHours + $afternoonHours, 4);

        return $computedWorkHours;
    }

    /**
     * Convert day fractions to total days using MinutesToDayFraction lookup table
     * API endpoint for frontend to calculate totals
     */
    public function calculateOffsetTotalDays(Request $request)
    {
        try {
            $validated = $request->validate([
                'late' => 'nullable|numeric|min:0',
                'undertime' => 'nullable|numeric|min:0',
                'absent' => 'nullable|numeric|min:0'
            ]);

            $late = floatval($validated['late'] ?? 0);
            $undertime = floatval($validated['undertime'] ?? 0);
            $absent = floatval($validated['absent'] ?? 0);

            $totalDays = $this->calculateOffsetDaysFromLookup($late, $undertime, $absent);

            return $this->successResponse([
                'total_days' => $totalDays,
                'late' => $late,
                'undertime' => $undertime,
                'absent' => $absent
            ], 'Total days calculated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to calculate total days: ' . $e->getMessage());
        }
    }

    /**
     * Generate remarks for a single time_data record
     * This is used to regenerate remarks for a specific employee without processing all employees
     */
    private function generateRemarksForRecord($record, $time_data_id)
    {
        try {
            $remarks = [];

            // Check for WFH
            if ($record->is_wfh == 1) {
                $remarks[] = 'WFH';
            }

            // Check for late (if late > 0)
            if (floatval($record->late ?? 0) > 0) {
                $remarks[] = 'Late';
            }

            // Check for undertime (if undertime > 0)
            if (floatval($record->undertime ?? 0) > 0) {
                $remarks[] = 'UT';
            }

            // Check for holiday
            if ($record->is_holiday == 1 && $record->holiday_id) {
                $holiday = DB::table('holidays')
                    ->where('id', $record->holiday_id)
                    ->first();
                if ($holiday) {
                    $remarks[] = $holiday->name;
                }
            }

            // Check for leave
            if ($record->leave == 1) {
                $leaveHeader = DB::table('leave_headers')
                    ->where('employee_id', $record->employee_id)
                    ->where('date_from', '<=', $record->date)
                    ->where('date_to', '>=', $record->date)
                    ->where('status', 'Approved')
                    ->first();
                if ($leaveHeader) {
                    $leaveDetail = DB::table('leave_details')
                        ->where('leave_header_id', $leaveHeader->id)
                        ->where('date', $record->date)
                        ->first();
                    if ($leaveDetail) {
                        $leaveType = DB::table('leave_types')
                            ->where('id', $leaveDetail->leave_type_id)
                            ->first();
                        if ($leaveType) {
                            $remarks[] = $leaveType->name;
                        }
                    }
                }
            }

            // Check for OB
            if ($record->is_ob == 1 && $record->ob_id) {
                $ob = DB::table('official_business')
                    ->where('id', $record->ob_id)
                    ->first();
                if ($ob) {
                    $remarks[] = 'OB';
                }
            }

            // Check for work cancellation
            $workCancellation = DB::table('work_cancellations')
                ->where('date_from', '<=', $record->date)
                ->where('date_to', '>=', $record->date)
                ->first();
            if ($workCancellation) {
                $remarks[] = $workCancellation->reason;
            }

            // Check for rest day
            if ($record->is_restday == 1) {
                $remarks[] = 'Rest Day';
            }

            // Join remarks with ' & ' separator
            $remarksString = !empty($remarks) ? implode(' & ', $remarks) : '';

            // Update remarks for this specific record only
            DB::table('time_data')
                ->where('id', $time_data_id)
                ->update(['remarks' => $remarksString]);
        } catch (\Exception $e) {
            // Continue even if remarks generation fails
        }
    }

    /**
     * Calculate late, undertime, and absent totals from time_data records
     * Converts each day fraction to minutes, sums minutes, then converts back to day fraction
     * This ensures accurate calculation when summing multiple day fractions
     *
     * @param int $employee_id
     * @param int $payroll_period_id
     * @return array ['late' => float, 'undertime' => float, 'absent' => float, 'late_offset' => float, 'undertime_offset' => float, 'absent_offset' => float, 'work_hours' => float]
     */
    private function calculateTimeDataTotals($employee_id, $payroll_period_id)
    {
        $timeDataRecords = DB::table('time_data as a')
            ->select(
                'a.late',
                'a.undertime',
                'a.absent',
                'a.late_offset',
                'a.undertime_offset',
                'a.absent_offset',
                'a.applied_offset',
                'a.leave',
                'a.is_holiday',
                'a.is_ob',
                'a.is_restday',
                'a.work_hours'
            )
            ->where('a.payroll_period_id', $payroll_period_id)
            ->where('a.employee_id', $employee_id)
            ->get();

        return $this->aggregateTimeDataTotalsFromRecords($timeDataRecords);
    }

    /**
     * Same aggregation as calculateTimeDataTotals(), for an in-memory iterable of time_data rows.
     *
     * @param  iterable  $timeDataRecords
     */
    private function aggregateTimeDataTotalsFromRecords($timeDataRecords): array
    {
        $totalLateMinutes = 0;
        $totalUndertimeMinutes = 0;
        $totalAbsentDays = 0.0;
        $totalAbsentDaysRaw = 0.0;
        $totalLateOffsetMinutes = 0;
        $totalUndertimeOffsetMinutes = 0;
        $totalAbsentOffsetDays = 0.0;
        $totalWorkHours = 0.0;

        foreach ($timeDataRecords as $record) {
            $appliedOffsetInt = intval($record->applied_offset ?? 0);

            $isSpecialDay = floatval($record->leave ?? 0) > 0 ||
                $record->is_holiday == 1 ||
                $record->is_ob == 1 ||
                $record->is_restday == 1;

            if (!$isSpecialDay) {
                $lateMinutesMain = \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($record->late ?? 0));
                $undertimeMinutesMain = \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($record->undertime ?? 0));
                $absentDaysMain = floatval($record->absent ?? 0);

                $totalLateMinutes += $lateMinutesMain;
                $totalUndertimeMinutes += $undertimeMinutesMain;
                $totalAbsentDays += $absentDaysMain;

                if ($appliedOffsetInt === 1) {
                    $lateMinutesOffset = \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($record->late_offset ?? 0));
                    $undertimeMinutesOffset = \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($record->undertime_offset ?? 0));
                    $absentDaysOffset = floatval($record->absent_offset ?? 0);

                    $totalLateOffsetMinutes += $lateMinutesOffset;
                    $totalUndertimeOffsetMinutes += $undertimeMinutesOffset;
                    $totalAbsentOffsetDays += $absentDaysOffset;
                }
            }

            $absentDaysRaw = floatval($record->absent ?? 0) + floatval($record->absent_offset ?? 0);
            $totalAbsentDaysRaw += $absentDaysRaw;

            $totalWorkHours += floatval($record->work_hours ?? 0);
        }

        $lateFraction = \App\Helpers\Time_Calculation::minutesToDayFraction($totalLateMinutes);
        $undertimeFraction = \App\Helpers\Time_Calculation::minutesToDayFraction($totalUndertimeMinutes);
        $lateOffsetFraction = \App\Helpers\Time_Calculation::minutesToDayFraction($totalLateOffsetMinutes);
        $undertimeOffsetFraction = \App\Helpers\Time_Calculation::minutesToDayFraction($totalUndertimeOffsetMinutes);

        return [
            'late' => $lateFraction,
            'undertime' => $undertimeFraction,
            'absent' => $totalAbsentDays,
            'absent_raw' => round($totalAbsentDaysRaw, 3),
            'late_offset' => $lateOffsetFraction,
            'undertime_offset' => $undertimeOffsetFraction,
            'absent_offset' => $totalAbsentOffsetDays,
            'work_hours' => round($totalWorkHours, 4)
        ];
    }

    /**
     * Batch version of calculateTimeDataTotals() — one DB round-trip for many employees.
     *
     * @param  array<int>  $employee_ids
     * @return array<int, array<string, mixed>>
     */
    private function calculateTimeDataTotalsForEmployees(array $employee_ids, $payroll_period_id): array
    {
        $employee_ids = array_values(array_unique(array_filter(array_map('intval', $employee_ids))));
        if (empty($employee_ids)) {
            return [];
        }

        $rows = DB::table('time_data as a')
            ->select(
                'a.employee_id',
                'a.late',
                'a.undertime',
                'a.absent',
                'a.late_offset',
                'a.undertime_offset',
                'a.absent_offset',
                'a.applied_offset',
                'a.leave',
                'a.is_holiday',
                'a.is_ob',
                'a.is_restday',
                'a.work_hours'
            )
            ->where('a.payroll_period_id', $payroll_period_id)
            ->whereIn('a.employee_id', $employee_ids)
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $eid = (int) $row->employee_id;
            if (!isset($grouped[$eid])) {
                $grouped[$eid] = [];
            }
            $grouped[$eid][] = $row;
        }

        $result = [];
        foreach ($employee_ids as $eid) {
            $result[$eid] = $this->aggregateTimeDataTotalsFromRecords($grouped[$eid] ?? []);
        }

        return $result;
    }

    /**
     * OT pay from approved overtime_applications in a date range (same rules as reconciliation / list view).
     */
    private function computeOvertimePayFromApplicationsForEmployeeDateRange(int $employeeId, string $fromDate, string $toDate): float
    {
        $rows = DB::table('overtime_applications as a')
            ->join('overtime_types as b', 'b.id', '=', 'a.overtime_type_id')
            ->join('employees as e', 'e.id', '=', 'a.employee_id')
            ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'e.employment_type_id')
            ->select(
                'a.total_hours',
                'b.rate as overtime_rate',
                'e.salary',
                'tks.work_days as setup_work_days',
                'tks.work_hours as setup_work_hours'
            )
            ->where('a.employee_id', $employeeId)
            ->whereBetween('a.date', [$fromDate, $toDate])
            ->where('a.approved', 1)
            ->where('a.approved_2', 1)
            ->where('a.approved_3', 1)
            ->where(function ($q) {
                $q->where('a.disapproved', 0)->orWhereNull('a.disapproved');
            })
            ->where('a.payroll', 1)
            ->where('a.service_credits', 0)
            ->get();

        $sum = 0.0;
        foreach ($rows as $ot) {
            $rateMetrics = $this->computeRateMetrics(floatval($ot->salary ?? 0), $ot->setup_work_days ?? null, $ot->setup_work_hours ?? null);
            $daily_rate = $rateMetrics['daily_rate'];
            $total_minutes = round(floatval($ot->total_hours ?? 0) * 60);
            $day_fraction = \App\Helpers\Time_Calculation::minutesToDayFraction($total_minutes);
            $sum += ($daily_rate * $day_fraction) * floatval($ot->overtime_rate ?? 0);
        }

        return round($sum, 3);
    }

    /**
     * IDs of overtime_applications counted by computeOvertimePayFromApplicationsForEmployeeDateRange (for dedup with time_data_adj.ot_id).
     *
     * @return int[]
     */
    private function getIncludedOvertimeApplicationIdsForEmployeeDateRange(int $employeeId, string $fromDate, string $toDate): array
    {
        return DB::table('overtime_applications as a')
            ->where('a.employee_id', $employeeId)
            ->whereBetween('a.date', [$fromDate, $toDate])
            ->where('a.approved', 1)
            ->where('a.approved_2', 1)
            ->where('a.approved_3', 1)
            ->where(function ($q) {
                $q->where('a.disapproved', 0)->orWhereNull('a.disapproved');
            })
            ->where('a.payroll', 1)
            ->where('a.service_credits', 0)
            ->pluck('a.id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    /**
     * Resolve overtime type rate for a time_data_adj row (ot_id → overtime_applications, then overtime_type_id, then default).
     */
    private function resolveOvertimeRateForTimeDataAdjRow(object $row): float
    {
        if (!empty($row->ot_id)) {
            $oa = DB::table('overtime_applications as a')
                ->join('overtime_types as b', 'b.id', '=', 'a.overtime_type_id')
                ->where('a.id', $row->ot_id)
                ->select('b.rate as overtime_rate')
                ->first();
            if ($oa && floatval($oa->overtime_rate ?? 0) > 0) {
                return floatval($oa->overtime_rate);
            }
        }
        if (!empty($row->overtime_type_id)) {
            $r = floatval(DB::table('overtime_types')->where('id', $row->overtime_type_id)->value('rate') ?? 0);
            if ($r > 0) {
                return $r;
            }
        }

        return 1.25;
    }

    /**
     * OT pay from time_data_adj snapshots: ot_pay when set, else hours × daily_rate × rate.
     * Skips rows whose ot_id already appears in $includedOvertimeApplicationIds (already counted in application total).
     */
    private function computeSupplementalOvertimePayFromTimeDataAdj(
        int $employeeId,
        int $precedingPayrollPeriodId,
        int $targetPayrollPeriodId,
        float $dailyRateUnrounded,
        array $includedOvertimeApplicationIds
    ): float {
        $rows = DB::table('time_data_adj')
            ->where('employee_id', $employeeId)
            ->where('payroll_period_id', $precedingPayrollPeriodId)
            ->where('target_payroll_period_id', $targetPayrollPeriodId)
            ->where('is_ot', 1)
            ->whereRaw('ISNULL(ot_hours, 0) > 0')
            ->get(['ot_hours', 'ot_pay', 'ot_id', 'overtime_type_id']);

        $total = 0.0;
        foreach ($rows as $r) {
            $otPayStored = floatval($r->ot_pay ?? 0);
            if ($otPayStored > 0) {
                if (!empty($r->ot_id) && in_array((int) $r->ot_id, $includedOvertimeApplicationIds, true)) {
                    continue;
                }
                $total += $otPayStored;
                continue;
            }

            if (!empty($r->ot_id) && in_array((int) $r->ot_id, $includedOvertimeApplicationIds, true)) {
                continue;
            }

            $hours = floatval($r->ot_hours ?? 0);
            if ($hours <= 0) {
                continue;
            }

            $rate = $this->resolveOvertimeRateForTimeDataAdjRow($r);
            $total_minutes = round($hours * 60);
            $day_fraction = \App\Helpers\Time_Calculation::minutesToDayFraction($total_minutes);
            $total += ($dailyRateUnrounded * $day_fraction) * $rate;
        }

        return round($total, 3);
    }

    /**
     * Calculate late, undertime, and absent totals from time_data_adj records (preceding period adjustments).
     * Sums main and offset values separately so partial offsets are supported.
     *
     * @return array{late: float, undertime: float, absent: float, late_offset: float, undertime_offset: float, absent_offset: float, work_hours: float, days_present: int}
     */
    private function calculateTimeDataAdjTotals($employee_id, $preceding_payroll_period_id, $target_payroll_period_id)
    {
        $rows = DB::table('time_data_adj as tda')
            ->select(
                'tda.date',
                'tda.work_hours',
                'tda.is_ob',
                'tda.late',
                'tda.undertime',
                'tda.absent',
                'tda.late_offset',
                'tda.undertime_offset',
                'tda.absent_offset'
            )
            ->where('tda.employee_id', $employee_id)
            ->where('tda.payroll_period_id', $preceding_payroll_period_id) // SOURCE (preceding)
            ->where('tda.target_payroll_period_id', $target_payroll_period_id) // TARGET (current)
            ->get();

        $totalLateMinutes = 0;
        $totalUndertimeMinutes = 0;
        $totalAbsentDays = 0.0;
        $totalLateOffsetMinutes = 0;
        $totalUndertimeOffsetMinutes = 0;
        $totalAbsentOffsetDays = 0.0;
        $totalWorkHours = 0.0;
        $daysPresentDates = [];

        foreach ($rows as $r) {
            // Main + offset values (adjustments already exclude holidays/leaves at source via SP logic)
            $totalLateMinutes += \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($r->late ?? 0));
            $totalUndertimeMinutes += \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($r->undertime ?? 0));
            $totalAbsentDays += floatval($r->absent ?? 0);

            $totalLateOffsetMinutes += \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($r->late_offset ?? 0));
            $totalUndertimeOffsetMinutes += \App\Helpers\Time_Calculation::dayFractionToMinutes(floatval($r->undertime_offset ?? 0));
            $totalAbsentOffsetDays += floatval($r->absent_offset ?? 0);

            $wh = floatval($r->work_hours ?? 0);
            $totalWorkHours += $wh;

            $isPresent = ($wh > 0) || (intval($r->is_ob ?? 0) === 1);
            if ($isPresent && !empty($r->date)) {
                $daysPresentDates[(string) $r->date] = true;
            }
        }

        return [
            'late' => \App\Helpers\Time_Calculation::minutesToDayFraction($totalLateMinutes),
            'undertime' => \App\Helpers\Time_Calculation::minutesToDayFraction($totalUndertimeMinutes),
            'absent' => round($totalAbsentDays, 3),
            'late_offset' => \App\Helpers\Time_Calculation::minutesToDayFraction($totalLateOffsetMinutes),
            'undertime_offset' => \App\Helpers\Time_Calculation::minutesToDayFraction($totalUndertimeOffsetMinutes),
            'absent_offset' => round($totalAbsentOffsetDays, 3),
            'work_hours' => round($totalWorkHours, 4),
            'days_present' => count($daysPresentDates),
        ];
    }

    /**
     * Recalculate and update time_data_summary_adj and linked time_data_summary adjustment fields after offset changes.
     * This ensures Adj. Late/Undertime/Absent amounts reflect the REMAINING (net) values after offset.
     */
    private function updateTimeDataSummaryAdjAfterOffsetChange($employee_id, $preceding_payroll_period_id, $target_payroll_period_id)
    {
        // Update only pending reconciliation rows (these are what the UI shows).
        $adj = DB::table('time_data_summary_adj')
            ->where('Employee_ID', $employee_id)
            ->where('Preceding_Payroll_Period_ID', $preceding_payroll_period_id)
            ->where('Status', 'PENDING')
            ->orderBy('Date_Stamp', 'desc')
            ->first();

        if (!$adj) {
            return;
        }

        $daily_rate = floatval($adj->Daily ?? 0);
        if ($daily_rate <= 0) {
            return;
        }

        $totals = $this->calculateTimeDataAdjTotals($employee_id, $preceding_payroll_period_id, $target_payroll_period_id);

        // For adjustment summaries, Late/Undertime/Absent in time_data_summary_adj should represent
        // the remaining (net) tardiness AFTER offset. time_data_adj already stores net values in
        // the main Late/Undertime/Absent columns, while *_offset holds the offsetted portions.
        // So we use the summed main columns directly (minute-based aggregation is handled inside
        // calculateTimeDataAdjTotals) and DO NOT subtract offsets again here.
        $late_day_fraction = floatval($totals['late']);
        $undertime_day_fraction = floatval($totals['undertime']);
        $absent_days_total = floatval($totals['absent']);

        $late_amount = round($late_day_fraction * $daily_rate, 3);
        $undertime_amount = round($undertime_day_fraction * $daily_rate, 3);
        $absent_amount = round($absent_days_total * $daily_rate, 3);
        $total_deduction = round($late_amount + $undertime_amount + $absent_amount, 3);

        // Preserve OT/Holiday pay from existing adj summary; offset does not affect these.
        $overtimePay = round(floatval($adj->Overtime ?? 0), 3);
        $holidayPay = round(floatval($adj->Holiday_Pay ?? 0), 3);

        // Recompute paid holiday count (to keep total_amount consistent with the original insertion rules)
        $paidHolidayDaysCount = 0;
        try {
            $period = DB::table('payroll_periods')->where('id', $preceding_payroll_period_id)->first();
            if ($period && !empty($period->attendance_start_date) && !empty($period->attendance_end_date)) {
                $from = \Carbon\Carbon::parse($period->attendance_start_date)->toDateString();
                $to = \Carbon\Carbon::parse($period->attendance_end_date)->toDateString();

                $holiday_rows = DB::table('holidays as h')
                    ->leftJoin('holiday_types as ht', 'ht.id', '=', 'h.holiday_type')
                    ->leftJoin('time_data_adj as tda', function ($join) use ($employee_id, $preceding_payroll_period_id) {
                        $join->on('tda.date', '=', 'h.date')
                            ->where('tda.employee_id', '=', $employee_id)
                            ->where('tda.payroll_period_id', '=', $preceding_payroll_period_id);
                    })
                    ->select(
                        'h.date',
                        'ht.rate as holiday_rate',
                        'ht.absent_with_pay',
                        DB::raw("COALESCE(tda.absent, 0) as absent"),
                        'tda.am_in',
                        'tda.pm_in'
                    )
                    ->whereBetween('h.date', [$from, $to])
                    ->where('h.active', 1)
                    ->get();

                foreach ($holiday_rows as $h) {
                    $holiday_rate = floatval($h->holiday_rate ?? 0);
                    $absent_with_pay_raw = $h->absent_with_pay ?? null;
                    $absent_with_pay_bool = false;
                    if ($absent_with_pay_raw !== null) {
                        $absent_with_pay_bool = is_bool($absent_with_pay_raw) ? $absent_with_pay_raw : (bool) intval($absent_with_pay_raw);
                    }
                    $is_present = !empty($h->am_in) || !empty($h->pm_in);
                    $is_absent = floatval($h->absent ?? 0) > 0;

                    // Only count paid holidays that were added to Holiday_Pay (absent_with_pay=1 is excluded by design)
                    if ($absent_with_pay_bool === false && $holiday_rate > 0 && $is_present && !$is_absent) {
                        $paidHolidayDaysCount++;
                    }
                }
            }
        } catch (\Throwable $e) {
            // no-op (keep paidHolidayDaysCount = 0)
        }

        $daysPresentRaw = intval($adj->Days_Present ?? $totals['days_present'] ?? 0);
        $daysPresentExclHoliday = max(0, $daysPresentRaw - $paidHolidayDaysCount);

        $computed_total_amount = max(0, round(
            ($daily_rate * $daysPresentExclHoliday)
                - $late_amount
                - $undertime_amount
                + $overtimePay
                + $holidayPay,
            3
        ));

        // Check if any time_data_adj records have applied_offset = 1 for this employee+period
        $hasOffsetAdj = DB::table('time_data_adj')
            ->where('employee_id', $employee_id)
            ->where('payroll_period_id', $preceding_payroll_period_id)
            ->where('applied_offset', 1)
            ->exists();

        DB::table('time_data_summary_adj')
            ->where('Employee_ID', $employee_id)
            ->where('Preceding_Payroll_Period_ID', $preceding_payroll_period_id)
            ->where('Status', 'PENDING')
            ->update([
                'Late' => round($late_day_fraction, 3),
                'Late_Amount' => $late_amount,
                'Undertime' => round($undertime_day_fraction, 3),
                'Undertime_Amount' => $undertime_amount,
                'Absent' => round($absent_days_total, 3),
                'Absent_Amount' => $absent_amount,
                'Total_Deduction' => $total_deduction,
                'Total_Amount' => $computed_total_amount,
                'Hours_Worked' => round(floatval($totals['work_hours'] ?? 0), 3),
                'Is_Offset' => $hasOffsetAdj ? 1 : 0,
                'Date_Stamp' => now(),
                'Updated_At' => now(),
                'Encoder_ID' => $this->resolveProcessAttendanceEncoderId(),
            ]);

        // Keep current period summary's adjustment numbers in sync (used by posting + some views)
        DB::table('time_data_summary')
            ->where('Payroll_Period_ID', $target_payroll_period_id)
            ->where('Employee_ID', $employee_id)
            ->update([
                'Adjustment_Amount' => $total_deduction, // deductions only (Late + UT + Absent)
                'Adjustment_Amount_OT_Holiday' => round($overtimePay + $holidayPay, 3),
                'Adjustment_Period_ID' => $preceding_payroll_period_id,
            ]);
    }

    /**
     * Update time_data_summary after cancelling an offset
     * Recalculates absent, absent_amount, late, late_amount, undertime, undertime_amount, and total_amount
     * from the updated time_data records
     */
    private function updateTimeDataSummaryAfterOffsetCancel($employee_id, $payroll_period_id)
    {
        // Check if time_data_summary record exists
        $summaryRecord = DB::table('time_data_summary')
            ->where('Payroll_Period_ID', $payroll_period_id)
            ->where('Employee_ID', $employee_id)
            ->first();

        if (!$summaryRecord) {
            // No summary record exists, nothing to update
            return;
        }

        // Get employee data for rate calculations
        $employee = DB::table('employees')
            ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'employees.employment_type_id')
            ->select(
                'employees.salary',
                'employees.employment_type_id',
                'tks.work_days as setup_work_days',
                'tks.work_hours as setup_work_hours'
            )
            ->where('employees.id', $employee_id)
            ->first();

        if (!$employee) {
            return;
        }

        // Calculate rate metrics
        $rateMetrics = $this->computeRateMetrics(
            floatval($employee->salary ?? 0),
            $employee->setup_work_days ?? null,
            $employee->setup_work_hours ?? null
        );

        $daily_rate_unrounded = $rateMetrics['daily_rate'];
        $hourly_rate = $rateMetrics['hourly_rate'];

        // Calculate totals using helper method (converts to minutes, sums, converts back).
        // IMPORTANT: calculateTimeDataTotals already returns NET (remaining) day fractions for
        // late/undertime/absent, with *_offset holding the offsetted portions. We MUST NOT
        // subtract offsets again here, or we'd double-apply offsets and zero out the values
        // in time_data_summary (the bug you observed).
        $totals = $this->calculateTimeDataTotals($employee_id, $payroll_period_id);

        // Net day fractions from totals (remaining after per-record offset)
        $late_day_fraction_net = $totals['late'];
        $undertime_day_fraction_net = $totals['undertime'];
        $total_absent_days_net = $totals['absent'];

        // Offset fractions
        $late_offset_fraction = $totals['late_offset'];
        $undertime_offset_fraction = $totals['undertime_offset'];
        $absent_offset_days = $totals['absent_offset'];

        // Gross (raw, before offset) = net + offset
        // These are stored in Late/Undertime/Absent columns (raw day fraction before offset)
        $late_day_fraction_raw = $late_day_fraction_net + $late_offset_fraction;
        $undertime_day_fraction_raw = $undertime_day_fraction_net + $undertime_offset_fraction;
        $total_absent_days_raw = $total_absent_days_net + $absent_offset_days;

        // Calculate amounts using unrounded daily rate from NET values (after offset)
        $late_amount = $late_day_fraction_net * $daily_rate_unrounded;
        $undertime_amount = $undertime_day_fraction_net * $daily_rate_unrounded;
        $absent_amount = $total_absent_days_net * $daily_rate_unrounded;

        // Total work hours from helper method
        $total_work_hours = $totals['work_hours'];

        // Get overtime pay from summary (preserve existing value)
        $overtime_pay = floatval($summaryRecord->Overtime ?? 0);

        // Is_adjusted count: distinct adjusted dates excluding rest days (same as list/view)
        $isAdjustedCountResult = DB::table('time_data')
            ->where('payroll_period_id', $payroll_period_id)
            ->where('employee_id', $employee_id)
            ->where('is_adjusted', 1)
            ->select(DB::raw('COUNT(DISTINCT CASE WHEN ISNULL(is_restday, 0) = 0 THEN date END) as is_adjusted_count'))
            ->first();
        $is_adjusted_count = $isAdjustedCountResult ? (int)$isAdjustedCountResult->is_adjusted_count : 0;
        $is_adjusted_amount = $is_adjusted_count * $daily_rate_unrounded;

        // Calculate gross pay and total amount (absent already reflected in total_work_hours)
        // Add is_adjusted amount: (is_adjusted_count × daily_rate) for assumed perfect attendance
        $gross_pay = $hourly_rate * $total_work_hours;
        $total_amount = max(0, $gross_pay + $overtime_pay + $is_adjusted_amount);

        // Determine if this employee has any offset applied (check actual time_data records)
        $hasOffset = DB::table('time_data')
            ->where('employee_id', $employee_id)
            ->where('payroll_period_id', $payroll_period_id)
            ->where('applied_offset', 1)
            ->exists();

        // Update time_data_summary
        // Late/Undertime/Absent columns store NET (after offset) day fractions
        // _Amount columns store NET amount (after offset) for payroll deductions
        $updatePayload = [
            'Late' => round($late_day_fraction_net, 3),
            'Late_Amount' => round($late_amount, 2),
            'Undertime' => round($undertime_day_fraction_net, 3),
            'Undertime_Amount' => round($undertime_amount, 2),
            'Absent' => round($total_absent_days_net, 3),
            'Absent_Amount' => round($absent_amount, 2),
            'Total_Deduction' => round($absent_amount + $late_amount + $undertime_amount, 3),
            'Total_Amount' => round($total_amount, 2),
            'Hours_Worked' => round($total_work_hours, 4),
            'Hourly_Rate' => round($hourly_rate, 3),
            'Is_Offset' => $hasOffset ? true : false,
            'Date_Stamp' => now(),
            'Encoder_ID' => $this->resolveProcessAttendanceEncoderId(),
        ];
        if (Schema::hasColumn('time_data_summary', 'Gross_Pay')) {
            $days_present = (int)($summaryRecord->Days_Present ?? 0);
            $holiday_pay = floatval($summaryRecord->Holiday_Pay ?? 0);
            $daily_rate_3dec = round($daily_rate_unrounded, 3);
            $gross_pay_value = $daily_rate_3dec * ($days_present + $is_adjusted_count) + $overtime_pay + $holiday_pay;
            $updatePayload['Gross_Pay'] = round($gross_pay_value, 3);
        }
        if (Schema::hasColumn('time_data_summary', 'OT_Pay')) {
            $updatePayload['OT_Pay'] = round($overtime_pay, 3);
        }
        DB::table('time_data_summary')
            ->where('Payroll_Period_ID', $payroll_period_id)
            ->where('Employee_ID', $employee_id)
            ->update($updatePayload);
    }

    /**
     * After Process Attendance core: create one time_data_summary row per employee that has time_data
     * in the given payroll period. Uses the same formulas as the list view and save().
     * Reconciliation (preceding period) then updates these rows with Adjustment_Amount and Adjustment_Period_ID.
     *
     * @param int $payroll_period_id
     * @return void
     */
    private function bulkInsertTimeDataSummaryForPeriod($payroll_period_id, ?string $processRunId = null)
    {
        $payroll_period = DB::table('payroll_periods')->where('id', $payroll_period_id)->first();
        if (!$payroll_period) {
            return;
        }
        $from_date = $payroll_period->attendance_start_date ?? null;
        $to_date = $payroll_period->attendance_end_date ?? null;
        $allowedEmploymentTypeIds = $this->getPayrollPeriodEmploymentTypeIds((int) $payroll_period_id);

        $employee_ids = DB::table('time_data')
            ->where('payroll_period_id', $payroll_period_id)
            ->distinct()
            ->pluck('employee_id')
            ->toArray();
        if (empty($employee_ids)) {
            return;
        }

        $employeesQuery = DB::table('employees as e')
            ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'e.employment_type_id')
            ->select('e.id as employee_id', 'e.salary', 'tks.work_days as setup_work_days', 'tks.work_hours as setup_work_hours')
            ->whereIn('e.id', $employee_ids)
            ->where('e.active', true)
            ->where('e.is_employee', true);
        $this->applyPayrollPeriodEmploymentTypeScope($employeesQuery, $allowedEmploymentTypeIds, 'e.employment_type_id');
        $employees = $employeesQuery->get();

        if ($employees->isEmpty()) {
            return;
        }

        $overtime_pay_map = [];
        if ($from_date && $to_date) {
            $overtime_applications = DB::table('overtime_applications as a')
                ->join('overtime_types as b', 'b.id', '=', 'a.overtime_type_id')
                ->join('employees as e', 'e.id', '=', 'a.employee_id')
                ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'e.employment_type_id')
                ->leftJoin(DB::raw("(SELECT x.employee_id,\n                            CASE WHEN ISNULL(ah2.approver_id_2, 0) = 0 THEN 0 ELSE 1 END as has_appr2,\n                            CASE WHEN ISNULL(ah2.approver_id_3, 0) = 0 AND ISNULL(ah2.approver_id_4, 0) = 0 THEN 0 ELSE 1 END as has_appr3\n                        FROM (\n                            SELECT ad.employee_id, ad.approver_id,\n                                   ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn\n                            FROM approver_details ad\n                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3\n                        ) x\n                        INNER JOIN approver_headers ah2 ON ah2.id = x.approver_id\n                        WHERE x.rn = 1) otappr"), 'otappr.employee_id', '=', 'a.employee_id')
                ->select(
                    'a.employee_id',
                    'a.overtime_type_id',
                    'a.date',
                    'a.total_hours',
                    'b.rate as overtime_rate',
                    'e.salary',
                    'tks.work_days as setup_work_days',
                    'tks.work_hours as setup_work_hours'
                )
                ->whereIn('a.employee_id', $employee_ids)
                ->whereBetween('a.date', [$from_date, $to_date])
                ->where('a.approved', 1)
                ->where(function ($q) {
                    $q->whereRaw('ISNULL(otappr.has_appr2, 0) = 0')->orWhere('a.approved_2', 1);
                })
                ->where(function ($q) {
                    $q->whereRaw('ISNULL(otappr.has_appr3, 0) = 0')->orWhere('a.approved_3', 1);
                })
                ->where(function ($q) {
                    $q->where('a.disapproved', 0)->orWhereNull('a.disapproved');
                })
                ->where(function ($q) {
                    $q->where('a.disapproved_2', 0)->orWhereNull('a.disapproved_2');
                })
                ->where(function ($q) {
                    $q->where('a.disapproved_3', 0)->orWhereNull('a.disapproved_3');
                })
                ->where(function ($q) {
                    $q->where('a.is_cancel', 0)->orWhereNull('a.is_cancel');
                })
                ->where('a.payroll', 1)
                ->where('a.service_credits', 0)
                ->get();
            foreach ($overtime_applications as $ot) {
                $eid = $ot->employee_id;
                if (!isset($overtime_pay_map[$eid])) {
                    $overtime_pay_map[$eid] = 0;
                }
                $rateMetrics = $this->computeRateMetrics(floatval($ot->salary ?? 0), $ot->setup_work_days ?? null, $ot->setup_work_hours ?? null);
                $daily_rate = $rateMetrics['daily_rate'];
                $total_minutes = round(floatval($ot->total_hours ?? 0) * 60);
                $day_fraction = \App\Helpers\Time_Calculation::minutesToDayFraction($total_minutes);
                $overtime_pay_map[$eid] += ($daily_rate * $day_fraction) * floatval($ot->overtime_rate ?? 0);
            }
        }

        $daysPresentMap = DB::table('time_data')
            ->where('payroll_period_id', $payroll_period_id)
            ->whereIn('employee_id', $employee_ids)
            ->select('employee_id', DB::raw("COUNT(DISTINCT CASE WHEN (COALESCE(work_hours, 0) > 0 OR is_ob = 1) THEN date END) as days_present"))
            ->groupBy('employee_id')
            ->pluck('days_present', 'employee_id');

        $isAdjustedCountMap = DB::table('time_data')
            ->where('payroll_period_id', $payroll_period_id)
            ->whereIn('employee_id', $employee_ids)
            ->where('is_adjusted', 1)
            ->select(
                'employee_id',
                DB::raw('COUNT(DISTINCT CASE WHEN ISNULL(is_restday, 0) = 0 THEN date END) as is_adjusted_count')
            )
            ->groupBy('employee_id')
            ->pluck('is_adjusted_count', 'employee_id');

        $encoder_id = $this->resolveProcessAttendanceEncoderId();
        $now = now();

        $bulkSummaryLoopIdx = 0;
        foreach ($employees as $emp) {
            $bulkSummaryLoopIdx++;
            if ($bulkSummaryLoopIdx % 25 === 0) {
            }
            $employee_id = $emp->employee_id;
            $rateMetrics = $this->computeRateMetrics(
                floatval($emp->salary ?? 0),
                $emp->setup_work_days ?? null,
                $emp->setup_work_hours ?? null
            );
            $daily_rate_unrounded = $rateMetrics['daily_rate'];
            $hourly_rate = $rateMetrics['hourly_rate'];

            $calculatedTotals = $this->calculateTimeDataTotals($employee_id, $payroll_period_id);
            $total_work_hours = $calculatedTotals['work_hours'];
            $overtime_pay = round(floatval($overtime_pay_map[$employee_id] ?? 0), 3);

            $baseLateFraction = $calculatedTotals['late'];
            $baseUndertimeFraction = $calculatedTotals['undertime'];
            $baseAbsentDays = $calculatedTotals['absent'];

            if ($from_date && $to_date) {
                $absent_exclude_holiday = DB::table('holidays as a')
                    ->leftJoin('holiday_types as b', 'b.id', '=', 'a.holiday_type')
                    ->leftJoin('time_data as td', function ($join) use ($employee_id, $payroll_period_id) {
                        $join->on('td.date', '=', 'a.date')
                            ->where('td.employee_id', '=', $employee_id)
                            ->where('td.payroll_period_id', '=', $payroll_period_id);
                    })
                    ->whereBetween('a.date', [$from_date, $to_date])
                    ->where('a.active', 1)
                    ->where(function ($q) {
                        $q->where('b.absent_with_pay', 1)->orWhere('b.absent_with_pay', '1');
                    })
                    ->select(DB::raw('COALESCE(SUM(td.absent), 0) as total'))
                    ->value('total');
                $baseAbsentDays = max(0, floatval($baseAbsentDays) - floatval($absent_exclude_holiday ?? 0));
            }

            // IMPORTANT:
            // calculateTimeDataTotals returns NET (remaining) day fractions for late/undertime/absent,
            // with *_offset holding the offsetted portions.
            // Gross (before offset) = net + offset
            // Late/Undertime/Absent columns store RAW (gross) values.
            // _Amount columns use NET values (after offset).
            $lateOffsetFraction = $calculatedTotals['late_offset'];
            $undertimeOffsetFraction = $calculatedTotals['undertime_offset'];
            $absentOffsetDays = $calculatedTotals['absent_offset'];

            // Gross (raw, before offset) = net + offset
            $late_day_fraction_raw = $baseLateFraction + $lateOffsetFraction;
            $undertime_day_fraction_raw = $baseUndertimeFraction + $undertimeOffsetFraction;
            $total_absent_days_raw = $baseAbsentDays + $absentOffsetDays;

            // Net values for deduction amounts
            $late_day_fraction = $baseLateFraction;
            $undertime_day_fraction = $baseUndertimeFraction;
            $total_absent_days = $baseAbsentDays;

            // Determine if offset is applied (check actual time_data records)
            $hasOffset = DB::table('time_data')
                ->where('employee_id', $employee_id)
                ->where('payroll_period_id', $payroll_period_id)
                ->where('applied_offset', 1)
                ->exists();

            // Amounts: use NET day fractions × unrounded daily rate
            $late_amount = $late_day_fraction * $daily_rate_unrounded;
            $undertime_amount = $undertime_day_fraction * $daily_rate_unrounded;
            $absent_amount = $total_absent_days * $daily_rate_unrounded;

            $daysPresent = (int)($daysPresentMap[$employee_id] ?? 0);
            $total_holiday_pay = 0;
            $total_holiday_pay_for_db = 0;
            $paid_holiday_days_count = 0;
            $total_leave_pay = 0;

            if ($from_date && $to_date) {
                $holiday_records = DB::table('holidays as a')
                    ->leftJoin('holiday_types as b', 'b.id', '=', 'a.holiday_type')
                    ->leftJoin('time_data as td', function ($join) use ($employee_id, $payroll_period_id) {
                        $join->on('td.date', '=', 'a.date')
                            ->where('td.employee_id', '=', $employee_id)
                            ->where('td.payroll_period_id', '=', $payroll_period_id);
                    })
                    ->select(
                        'a.date',
                        'b.rate as holiday_rate',
                        'b.absent_with_pay',
                        DB::raw("COALESCE(td.absent, 0) as absent"),
                        'td.am_in',
                        'td.pm_in',
                        DB::raw("COALESCE(td.work_hours, 0) as work_hours")
                    )
                    ->whereBetween('a.date', [$from_date, $to_date])
                    ->where('a.active', 1)
                    ->get();
                foreach ($holiday_records as $holiday) {
                    $holiday_rate = floatval($holiday->holiday_rate ?? 0);
                    $absent_with_pay_raw = $holiday->absent_with_pay ?? null;
                    $absent_with_pay_bool = false;
                    if ($absent_with_pay_raw !== null) {
                        $absent_with_pay_bool = is_bool($absent_with_pay_raw) ? $absent_with_pay_raw : (bool)intval($absent_with_pay_raw);
                    }
                    $is_present = !empty($holiday->am_in) || !empty($holiday->pm_in);
                    $is_absent = floatval($holiday->absent ?? 0) > 0;
                    if (!$absent_with_pay_bool && $holiday_rate > 0 && $is_present && !$is_absent) {
                        $amt = $daily_rate_unrounded * $holiday_rate;
                        $total_holiday_pay += $amt;
                        $total_holiday_pay_for_db += $amt;
                        $paid_holiday_days_count++;
                    }
                }
                $leave_records = DB::table('leave_headers as a')
                    ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                    ->where('a.employee_id', $employee_id)
                    ->whereBetween('b.leave_date', [$from_date, $to_date])
                    ->where('a.approved', 1)
                    ->where('a.approved_2', 1)
                    ->where('a.approved_3', 1)
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('a.disapproved', 0)->orWhereNull('a.disapproved');
                        })
                            ->where(function ($q2) {
                                $q2->where('a.disapproved_2', 0)->orWhereNull('a.disapproved_2');
                            })
                            ->where(function ($q2) {
                                $q2->where('a.disapproved_3', 0)->orWhereNull('a.disapproved_3');
                            });
                    })
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('a.is_cancel', 0)->orWhereNull('a.is_cancel');
                        })
                            ->where(function ($q2) {
                                $q2->where('a.is_cancel_2', 0)->orWhereNull('a.is_cancel_2');
                            })
                            ->where(function ($q2) {
                                $q2->where('a.is_cancel_3', 0)->orWhereNull('a.is_cancel_3');
                            });
                    })
                    ->select('b.with_pay')
                    ->get();
                foreach ($leave_records as $leave) {
                    if (floatval($leave->with_pay ?? 0) == 1.00) {
                        $total_leave_pay += $daily_rate_unrounded;
                    }
                }
            }

            $is_adjusted_count = (int)($isAdjustedCountMap[$employee_id] ?? 0);
            $is_adjusted_amount = $is_adjusted_count * $daily_rate_unrounded;
            $daysPresentExclHoliday = max(0, $daysPresent - $paid_holiday_days_count);
            $adjustmentAmount = 0;
            $adjustmentAmountOtHoliday = 0;
            $computed_total_amount = max(0, $daily_rate_unrounded * $daysPresentExclHoliday
                - $late_amount
                - $undertime_amount
                + $overtime_pay
                + round($total_holiday_pay, 3)
                + round($total_leave_pay, 3)
                + $is_adjusted_amount
                - $adjustmentAmount
                + $adjustmentAmountOtHoliday);
            $computed_total_amount = round($computed_total_amount, 3);
            $total_deduction = round($absent_amount + $late_amount + $undertime_amount, 3);
            $days_covered = $daysPresent;

            $summaryData = [
                'Payroll_Period_ID' => $payroll_period_id,
                'Employee_ID' => $employee_id,
                'Hours_Worked' => round($total_work_hours, 3),
                'Daily' => round($daily_rate_unrounded, 3),
                'Hourly_Rate' => round($hourly_rate, 3),
                'Late' => round($late_day_fraction, 3),
                'Late_Amount' => round($late_amount, 3),
                'Undertime' => round($undertime_day_fraction, 3),
                'Undertime_Amount' => round($undertime_amount, 3),
                'Absent' => round($total_absent_days, 3),
                'Absent_Amount' => round($absent_amount, 3),
                'Total_Deduction' => $total_deduction,
                'Total_Amount' => $computed_total_amount,
                'Overtime' => $overtime_pay,
                'OT_Pay' => round($overtime_pay, 3),
                'Date_Stamp' => $now,
                'Encoder_ID' => $encoder_id,
                'Is_Offset' => $hasOffset ? true : false,
                'Days_Covered' => $days_covered,
                'Days_Present' => $daysPresent,
                'Adjustment_Amount' => 0,
                'Adjustment_Period_ID' => null,
                'Adjustment_Amount_OT_Holiday' => 0,
                'Holiday_Pay' => round($total_holiday_pay_for_db, 3),
            ];
            if (Schema::hasColumn('time_data_summary', 'Gross_Pay')) {
                $summaryData['Gross_Pay'] = round(round($daily_rate_unrounded, 3) * ($daysPresent + $is_adjusted_count) + $overtime_pay + $total_holiday_pay_for_db, 3);
            }

            // GUARD: If there's already a time_data_summary record with Is_Offset = 1 for this
            // employee+period, skip insertion so offset-calculated values are not overwritten.
            $existingSummary = DB::table('time_data_summary')
                ->where('Payroll_Period_ID', $payroll_period_id)
                ->where('Employee_ID', $employee_id)
                ->first();

            if ($existingSummary && !empty($existingSummary->Is_Offset) && $hasOffset) {
                continue; // Preserve offset-calculated values (verified actual offsets exist)
            }

            if ($existingSummary) {
                // Update existing record (non-offset)
                DB::table('time_data_summary')
                    ->where('Payroll_Period_ID', $payroll_period_id)
                    ->where('Employee_ID', $employee_id)
                    ->update($summaryData);
            } else {
                DB::table('time_data_summary')->insert($summaryData);
            }
        }
    }

    /**
     * Get time_data records by date with employee information
     * Returns all time_data records for a specific date with employee details
     */
    public function getTimeDataByDate(Request $request)
    {
        try {
            $date = $request->get('date');

            if (!$date) {
                return $this->errorResponse('Date parameter is required');
            }

            // Validate date format
            try {
                $dateObj = Carbon::parse($date);
                $date = $dateObj->format('Y-m-d');
            } catch (\Exception $e) {
                return $this->errorResponse('Invalid date format. Please use YYYY-MM-DD format');
            }

            // Get time_data records with employee information (current payroll data)
            $app_key = env("APP_KEY", "");
            $timeDataRecords = DB::table('time_data as td')
                ->leftJoin('employees as e', 'td.employee_id', '=', 'e.id')
                ->leftJoin('departments as d', 'e.department_id', '=', 'd.id')
                ->leftJoin('positions as p', 'e.position_id', '=', 'p.id')
                ->leftJoin('name_suffixes as ns', 'ns.id', '=', 'e.name_suffix_id')
                ->where('td.date', $date)
                // Exclude is_adjusted = 1 rows; their reference data lives in time_data_adj
                ->where(function ($q) {
                    $q->whereNull('td.is_adjusted')
                        ->orWhere('td.is_adjusted', '<>', 1);
                })
                ->select(
                    'td.*',
                    'e.employee_no',
                    'e.first_name',
                    'e.middle_name',
                    'e.last_name',
                    'ns.name as suffix',
                    // Original (decrypting) employee_name selection kept for reference:
                    // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                    //     CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                    //         CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, ''), CASE WHEN ns.name IS NOT NULL AND ns.name != '' THEN CONCAT(' ', ns.name) ELSE '' END)
                    //     ELSE
                    //         CONCAT(COALESCE(e.first_name, ''), ' ', SUBSTRING(COALESCE(e.middle_name, ''), 1, 1), '. ', COALESCE(e.last_name, ''), CASE WHEN ns.name IS NOT NULL AND ns.name != '' THEN CONCAT(' ', ns.name) ELSE '' END)
                    //     END
                    // ELSE
                    //     CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                    //         RTRIM(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')))+CASE WHEN ns.name IS NOT NULL AND ns.name != '' THEN ' '+ns.name ELSE '' END
                    //     ELSE
                    //         RTRIM(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+UPPER(SUBSTRING([dbo].[ufn_DecryptString](e.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')))+CASE WHEN ns.name IS NOT NULL AND ns.name != '' THEN ' '+ns.name ELSE '' END
                    //     END
                    // END as employee_name"),
                    DB::raw("CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                        CONCAT(
                            COALESCE(e.first_name, ''),
                            ' ',
                            COALESCE(e.last_name, ''),
                            CASE WHEN ns.name IS NOT NULL AND ns.name != '' THEN CONCAT(' ', ns.name) ELSE '' END
                        )
                    ELSE
                        CONCAT(
                            COALESCE(e.first_name, ''),
                            ' ',
                            UPPER(SUBSTRING(COALESCE(e.middle_name, ''), 1, 1)),
                            '. ',
                            COALESCE(e.last_name, ''),
                            CASE WHEN ns.name IS NOT NULL AND ns.name != '' THEN CONCAT(' ', ns.name) ELSE '' END
                        )
                    END as employee_name"),
                    'd.name as department',
                    'p.name as position',
                    DB::raw('0 as is_adj')
                )
                ->orderBy('e.employee_no', 'asc')
                ->get();

            // Get time_data_adj records that fall on the same date and target any payroll period (preceding adjustments)
            $timeDataAdjRecords = DB::table('time_data_adj as td')
                ->leftJoin('employees as e', 'td.employee_id', '=', 'e.id')
                ->leftJoin('departments as d', 'e.department_id', '=', 'd.id')
                ->leftJoin('positions as p', 'e.position_id', '=', 'p.id')
                ->leftJoin('name_suffixes as ns', 'ns.id', '=', 'e.name_suffix_id')
                ->where('td.date', $date)
                ->select(
                    'td.*',
                    'e.employee_no',
                    'e.first_name',
                    'e.middle_name',
                    'e.last_name',
                    'ns.name as suffix',
                    // Original (decrypting) employee_name selection kept for reference:
                    // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                    //     CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                    //         CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, ''), CASE WHEN ns.name IS NOT NULL AND ns.name != '' THEN CONCAT(' ', ns.name) ELSE '' END)
                    //     ELSE
                    //         CONCAT(COALESCE(e.first_name, ''), ' ', SUBSTRING(COALESCE(e.middle_name, ''), 1, 1), '. ', COALESCE(e.last_name, ''), CASE WHEN ns.name IS NOT NULL AND ns.name != '' THEN CONCAT(' ', ns.name) ELSE '' END)
                    //     END
                    // ELSE
                    //     CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                    //         RTRIM(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')))+CASE WHEN ns.name IS NOT NULL AND ns.name != '' THEN ' '+ns.name ELSE '' END
                    //     ELSE
                    //         RTRIM(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+UPPER(SUBSTRING([dbo].[ufn_DecryptString](e.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')))+CASE WHEN ns.name IS NOT NULL AND ns.name != '' THEN ' '+ns.name ELSE '' END
                    //     END
                    // END as employee_name"),
                    DB::raw("CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                        CONCAT(
                            COALESCE(e.first_name, ''),
                            ' ',
                            COALESCE(e.last_name, ''),
                            CASE WHEN ns.name IS NOT NULL AND ns.name != '' THEN CONCAT(' ', ns.name) ELSE '' END
                        )
                    ELSE
                        CONCAT(
                            COALESCE(e.first_name, ''),
                            ' ',
                            UPPER(SUBSTRING(COALESCE(e.middle_name, ''), 1, 1)),
                            '. ',
                            COALESCE(e.last_name, ''),
                            CASE WHEN ns.name IS NOT NULL AND ns.name != '' THEN CONCAT(' ', ns.name) ELSE '' END
                        )
                    END as employee_name"),
                    'd.name as department',
                    'p.name as position',
                    DB::raw('1 as is_adj')
                )
                ->orderBy('e.employee_no', 'asc')
                ->get();

            // Merge current-period and adjustment records so the UI can see all attendance for that date
            // IMPORTANT: Do NOT show parent rows in time_data where is_adjusted = 1,
            // because their detailed/child rows already exist in time_data_adj.
            $records = $timeDataRecords->merge($timeDataAdjRecords)
                ->filter(function ($row) {
                    // Keep all adjustment rows (is_adj = 1)
                    // For main time_data rows (is_adj = 0), hide those where is_adjusted = 1
                    $isAdj = intval($row->is_adj ?? 0) === 1;
                    $isAdjustedParent = !$isAdj && intval($row->is_adjusted ?? 0) === 1;
                    return !$isAdjustedParent;
                })
                ->sortBy(['employee_no', 'date'])
                ->values();

            return $this->successResponse($records, 'Time data records loaded successfully');
        } catch (\Throwable $th) {
            return $this->serverErrorResponse('Failed to load time data records: ' . $th->getMessage());
        }
    }

    /**
     * Run SP_ProcessTimeDataAdj per date for attendance-state processing, then
     * compute and save amount summaries from the resulting time_data_adj rows.
     *
     * Boundary:
     * - SP_ProcessTimeDataAdj owns attendance-state mutations in time_data_adj.
     * - Controller owns amount aggregation/persistence in summary tables.
     *
     * @param int   $payroll_period_id           Current (target) payroll period ID
     * @param int   $preceding_payroll_period_id Preceding (source) payroll period ID
     * @param array $result                      Result array to enrich with adjustment metadata (by reference)
     * @param bool  $forceReprocess              When true, passes @ForceReprocess = 1 to SP_ProcessTimeDataAdj (reset + re-evaluate)
     * @return void
     */
    private function processTimeDataAdjForPrecedingPeriod($payroll_period_id, $preceding_payroll_period_id, array &$result, $forceReprocess = true, ?string $processRunId = null)
    {
        $preceding_period = DB::table('payroll_periods')
            ->where('id', $preceding_payroll_period_id)
            ->first();

        if (!$preceding_period) {
            return;
        }

        $adjStartDate = Carbon::parse($preceding_period->attendance_start_date);
        $adjEndDate = Carbon::parse($preceding_period->attendance_end_date);

        $adjProcessedDates = 0;
        for ($d = $adjStartDate->copy(); $d->lte($adjEndDate); $d->addDay()) {
            $adjDateToProcess = $d->toDateString();
            try {
                // For adjustments, we treat the preceding payroll period as the SOURCE period
                // and the currently selected payroll period as the TARGET period.
                // IMPORTANT: Parameter names in the EXEC must match the SP signature exactly.
                DB::statement("EXEC [dbo].[SP_ProcessTimeDataAdj] @DateToProcess = ?, @SourcePayrollPeriodId = ?, @TargetPeriodId = ?, @AdjustmentId = ?, @ForceReprocess = ?", [
                    $adjDateToProcess,
                    $preceding_payroll_period_id, // source period (preceding)
                    $payroll_period_id,            // target period (current dropdown)
                    null,                          // @AdjustmentId: process all rows for this date
                    $forceReprocess ? 1 : 0
                ]);

                // Regenerate remarks for both time_data and time_data_adj for this date.
                // SP_GenerateTimeRemarks already handles status-scoped time_data_adj rows.
                try {
                    DB::statement("EXEC [dbo].[SP_GenerateTimeRemarks] @DateToProcess = ?", [$adjDateToProcess]);
                } catch (\Exception $remarksEx) {
                    \Log::warning('[ProcessAttendance] SP_GenerateTimeRemarks failed after SP_ProcessTimeDataAdj', [
                        'date' => $adjDateToProcess,
                        'preceding_period' => $preceding_payroll_period_id,
                        'current_period' => $payroll_period_id,
                        'error' => $remarksEx->getMessage()
                    ]);
                }

                $adjProcessedDates++;
            } catch (\Exception $e) {
                \Log::warning('[ProcessAttendance] SP_ProcessTimeDataAdj failed', [
                    'date' => $adjDateToProcess,
                    'preceding_period' => $preceding_payroll_period_id,
                    'current_period' => $payroll_period_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        \Log::info('[ProcessAttendance] SP_ProcessTimeDataAdj completed', [
            'preceding_period' => $preceding_payroll_period_id,
            'target_period' => $payroll_period_id,
            'force_reprocess' => $forceReprocess,
            'dates_processed' => $adjProcessedDates
        ]);

        // ========== INSERT/UPDATE time_data_summary_adj ==========
        // Controller keeps amount-saving logic here by design.
        // Attendance-state changes must remain in SP_ProcessTimeDataAdj.
        try {
            // IMPORTANT:
            //  - payroll_period_id       = SOURCE (preceding) payroll period
            //  - target_payroll_period_id = TARGET (current) payroll period
            // For reconciliation, we must use the adjustment rows that:
            //  - belong to the preceding period (source), AND
            //  - explicitly target the CURRENT payroll period being processed.
            $adjEmployees = DB::table('time_data_adj as tda')
                ->join('employees as e', 'tda.employee_id', '=', 'e.id')
                ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'e.employment_type_id')
                ->where('tda.payroll_period_id', $preceding_payroll_period_id)
                ->where('tda.target_payroll_period_id', $payroll_period_id)
                ->where('e.active', true)
                ->where('e.is_employee', true)
                ->select(
                    'tda.employee_id',
                    'e.salary',
                    'tks.work_days',
                    'tks.work_hours as tks_work_hours',
                    DB::raw("CAST(SUM(COALESCE(tda.work_hours, 0)) AS DECIMAL(18,4)) as total_hours_worked"),
                    DB::raw("SUM(tda.late) as total_late"),
                    DB::raw("SUM(tda.undertime) as total_undertime"),
                    DB::raw("SUM(tda.absent) as total_absent"),
                    DB::raw("SUM(ISNULL(tda.ot_hours, 0)) as total_ot_hours"),
                    DB::raw("SUM(ISNULL(tda.late_offset, 0)) as total_late_offset"),
                    DB::raw("SUM(ISNULL(tda.undertime_offset, 0)) as total_undertime_offset"),
                    DB::raw("SUM(ISNULL(tda.absent_offset, 0)) as total_absent_offset"),
                    DB::raw("COUNT(DISTINCT tda.date) as days_covered"),
                    DB::raw("COUNT(DISTINCT CASE WHEN (COALESCE(tda.work_hours, 0) > 0 OR tda.is_ob = 1) THEN tda.date END) as days_present")
                )
                ->groupBy('tda.employee_id', 'e.salary', 'tks.work_days', 'tks.work_hours')
                ->get();

            // Build overtime pay map for preceding period using SAME formula and joins as time_data_summary
            $from_date_adj = $adjStartDate->toDateString();
            $to_date_adj = $adjEndDate->toDateString();
            $adj_employee_ids = $adjEmployees->pluck('employee_id')->unique()->values()->all();
            $adjOvertimePayMap = [];
            if (!empty($adj_employee_ids) && $from_date_adj && $to_date_adj) {
                $overtime_applications_adj = DB::table('overtime_applications as a')
                    ->join('overtime_types as b', 'b.id', '=', 'a.overtime_type_id')
                    ->join('employees as e', 'e.id', '=', 'a.employee_id')
                    ->leftJoin('time_keeping_setups as tks', 'tks.employment_type_id', '=', 'e.employment_type_id')
                    ->select(
                        'a.id',
                        'a.employee_id',
                        'a.overtime_type_id',
                        'a.date',
                        'a.total_hours',
                        'b.rate as overtime_rate',
                        'e.salary',
                        'e.work_schedule_id',
                        'e.is_shifting',
                        'tks.work_days as setup_work_days',
                        'tks.work_hours as setup_work_hours'
                    )
                    ->whereIn('a.employee_id', $adj_employee_ids)
                    ->whereBetween('a.date', [$from_date_adj, $to_date_adj])
                    ->where('a.approved', 1)
                    ->where('a.approved_2', 1)
                    ->where('a.approved_3', 1)
                    ->where(function ($q) {
                        $q->where('a.disapproved', 0)->orWhereNull('a.disapproved');
                    })
                    ->where('a.payroll', 1)
                    ->where('a.service_credits', 0)
                    ->get();

                $includedAppIdsByEmployee = [];
                foreach ($overtime_applications_adj as $ot) {
                    $employee_id = $ot->employee_id;
                    if (!isset($adjOvertimePayMap[$employee_id])) {
                        $adjOvertimePayMap[$employee_id] = 0;
                    }
                    $salary = floatval($ot->salary ?? 0);
                    $total_hours = floatval($ot->total_hours ?? 0);
                    $rateMetrics = $this->computeRateMetrics($salary, $ot->setup_work_days ?? null, $ot->setup_work_hours ?? null);
                    $daily_rate = $rateMetrics['daily_rate'];
                    $total_minutes = round($total_hours * 60);
                    $day_fraction = \App\Helpers\Time_Calculation::minutesToDayFraction($total_minutes);
                    $ot_pay = ($daily_rate * $day_fraction) * floatval($ot->overtime_rate ?? 0);
                    $adjOvertimePayMap[$employee_id] += $ot_pay;
                    if (!isset($includedAppIdsByEmployee[$employee_id])) {
                        $includedAppIdsByEmployee[$employee_id] = [];
                    }
                    if (isset($ot->id)) {
                        $includedAppIdsByEmployee[$employee_id][] = (int) $ot->id;
                    }
                }
            } else {
                $includedAppIdsByEmployee = [];
            }

            $adjSummaryCount = 0;
            foreach ($adjEmployees as $adjEmp) {
                try {
                    // Get source summary for the preceding period, if it exists.
                    // We will reuse its Daily rate so that adjustment computations
                    // match exactly the original time_data_summary insertion logic.
                    $sourceSummary = DB::table('time_data_summary')
                        ->where('Payroll_Period_ID', $preceding_payroll_period_id)
                        ->where('Employee_ID', $adjEmp->employee_id)
                        ->first();

                    // Determine daily rate:
                    // 1) Prefer the Daily column from source time_data_summary (exact same as original)
                    // 2) Otherwise use single source of truth: computeRateMetrics (salary/22)
                    if ($sourceSummary && isset($sourceSummary->Daily)) {
                        $daily_rate = floatval($sourceSummary->Daily);
                    } else {
                        $salary = floatval($adjEmp->salary ?? 0);
                        $rateMetrics = $this->computeRateMetrics($salary, $adjEmp->work_days ?? null, $adjEmp->work_hours ?? null);
                        $daily_rate = $rateMetrics['daily_rate'];
                    }

                    // Exclude absent on absent_with_pay holidays from absent count/amount (preceding period)
                    $base_absent_adj = floatval($adjEmp->total_absent ?? 0);
                    if ($from_date_adj && $to_date_adj) {
                        $absent_exclude_adj = DB::table('holidays as a')
                            ->leftJoin('holiday_types as b', 'b.id', '=', 'a.holiday_type')
                            ->leftJoin('time_data_adj as td', function ($join) use ($adjEmp, $preceding_payroll_period_id) {
                                $join->on('td.date', '=', 'a.date')
                                    ->where('td.employee_id', '=', $adjEmp->employee_id)
                                    ->where('td.payroll_period_id', '=', $preceding_payroll_period_id);
                            })
                            ->whereBetween('a.date', [$from_date_adj, $to_date_adj])
                            ->where('a.active', 1)
                            ->where(function ($q) {
                                $q->where('b.absent_with_pay', 1)->orWhere('b.absent_with_pay', '1');
                            })
                            ->select(DB::raw('COALESCE(SUM(td.absent), 0) as total'))
                            ->value('total');
                        $base_absent_adj = max(0, $base_absent_adj - floatval($absent_exclude_adj ?? 0));
                    }

                    // Raw day fractions BEFORE offset reduction (stored in Late/Undertime/Absent columns)
                    $late_day_fraction_raw_adj = floatval($adjEmp->total_late ?? 0);
                    $undertime_day_fraction_raw_adj = floatval($adjEmp->total_undertime ?? 0);
                    $absent_days_total_raw_adj = $base_absent_adj;

                    // Offset fractions
                    $lateOffsetAdj = floatval($adjEmp->total_late_offset ?? 0);
                    $undertimeOffsetAdj = floatval($adjEmp->total_undertime_offset ?? 0);
                    $absentOffsetAdj = floatval($adjEmp->total_absent_offset ?? 0);

                    // Determine if this employee has any offset applied (check actual time_data_adj records for this specific period pair)
                    $hasOffsetAdj = DB::table('time_data_adj')
                        ->where('employee_id', $adjEmp->employee_id)
                        ->where('payroll_period_id', $preceding_payroll_period_id)
                        ->where('target_payroll_period_id', $payroll_period_id)
                        ->where('applied_offset', 1)
                        ->exists();

                    // Apply offsets to get net values (same helper as main summary)
                    $lateCalc = $this->computeNetDayFractionWithOffset($late_day_fraction_raw_adj, $lateOffsetAdj);
                    $undertimeCalc = $this->computeNetDayFractionWithOffset($undertime_day_fraction_raw_adj, $undertimeOffsetAdj);
                    $absentCalc = $this->computeNetDayFractionWithOffset($absent_days_total_raw_adj, $absentOffsetAdj);

                    $late_day_fraction = $lateCalc['net_day_fraction'];
                    $undertime_day_fraction = $undertimeCalc['net_day_fraction'];
                    $absent_days_total = $absentCalc['net_day_fraction'];

                    // Amounts: same formula as main summary (daily × net day fraction, rounded at the end)
                    // Use 3-decimal precision to match time_data_summary_adj schema
                    $late_amount = round($late_day_fraction * $daily_rate, 3);
                    $undertime_amount = round($undertime_day_fraction * $daily_rate, 3);
                    $absent_amount = round($absent_days_total * $daily_rate, 3);
                    $total_deduction = round($absent_amount + $late_amount + $undertime_amount, 3);

                    $daysPresent = intval($adjEmp->days_present ?? 0);
                    $supplementalOt = $this->computeSupplementalOvertimePayFromTimeDataAdj(
                        (int) $adjEmp->employee_id,
                        (int) $preceding_payroll_period_id,
                        (int) $payroll_period_id,
                        (float) $daily_rate,
                        $includedAppIdsByEmployee[$adjEmp->employee_id] ?? []
                    );
                    $overtimePay = round(floatval($adjOvertimePayMap[$adjEmp->employee_id] ?? 0) + $supplementalOt, 3);

                    // Holiday pay: same formula and logic as time_data_summary (holidays + holiday_types, presence from time_data_adj for preceding period)
                    // Days with holiday pay must not be included in Days_Present to avoid double-counting (daily rate × days + holiday pay).
                    // total_holiday_pay_adj = full amount for Total_Amount. total_holiday_pay_adj_for_db = only when employee worked (for Holiday_Pay column).
                    $total_holiday_pay_adj = 0;
                    $total_holiday_pay_adj_for_db = 0;
                    $paid_holiday_days_count = 0;
                    if ($from_date_adj && $to_date_adj) {
                        $holiday_records_adj = DB::table('holidays as a')
                            ->leftJoin('holiday_types as b', 'b.id', '=', 'a.holiday_type')
                            ->leftJoin('time_data_adj as td', function ($join) use ($adjEmp, $preceding_payroll_period_id) {
                                $join->on('td.date', '=', 'a.date')
                                    ->where('td.employee_id', '=', $adjEmp->employee_id)
                                    ->where('td.payroll_period_id', '=', $preceding_payroll_period_id);
                            })
                            ->select(
                                'a.date',
                                'b.rate as holiday_rate',
                                'b.absent_with_pay',
                                DB::raw("COALESCE(td.absent, 0) as absent"),
                                'td.am_in',
                                'td.pm_in',
                                DB::raw("COALESCE(td.work_hours, 0) as work_hours")
                            )
                            ->whereBetween('a.date', [$from_date_adj, $to_date_adj])
                            ->where('a.active', 1)
                            ->get();

                        foreach ($holiday_records_adj as $holiday) {
                            $holiday_rate = floatval($holiday->holiday_rate ?? 0);
                            $absent_with_pay_raw = $holiday->absent_with_pay ?? null;
                            $absent_with_pay_bool = false;
                            if ($absent_with_pay_raw !== null) {
                                $absent_with_pay_bool = is_bool($absent_with_pay_raw) ? $absent_with_pay_raw : (bool)intval($absent_with_pay_raw);
                            }
                            $is_present = !empty($holiday->am_in) || !empty($holiday->pm_in);
                            $is_absent = floatval($holiday->absent ?? 0) > 0;
                            $work_hours = floatval($holiday->work_hours ?? 0);

                            // When absent_with_pay = 1, do NOT add to Holiday_Pay: work on such holidays is via overtime_application (Holiday Overtime, type id = 4) and is already in Overtime/OT_Pay.
                            // if ($absent_with_pay_bool === true) {
                            //     $setup_work_hours_adj = floatval($adjEmp->tks_work_hours ?? 0) ?: 8;
                            //     $holiday_hourly_rate = $setup_work_hours_adj > 0 ? ($daily_rate / $setup_work_hours_adj) : ($daily_rate / 8);
                            //     if ($work_hours > 0) {
                            //         $holiday_pay_amount = $daily_rate + ($holiday_hourly_rate * $work_hours);
                            //         $total_holiday_pay_adj += $holiday_pay_amount;
                            //         $total_holiday_pay_adj_for_db += $holiday_pay_amount;
                            //         $paid_holiday_days_count++;
                            //     } else {
                            //         $total_holiday_pay_adj += $daily_rate;
                            //     }
                            // }
                            if ($absent_with_pay_bool === true) {
                                // absent_with_pay = 1: Do NOT insert into Holiday_Pay. Amount is in Overtime (overtime_type_id = 4 "Holiday Overtime") via overtime_application; not considered Holiday_Pay. (Code above is commented out.)
                            } else if ($holiday_rate > 0 && $absent_with_pay_bool === false && $is_present && !$is_absent) {
                                $holiday_pay_amount = $daily_rate * $holiday_rate;
                                $total_holiday_pay_adj += $holiday_pay_amount;
                                $total_holiday_pay_adj_for_db += $holiday_pay_amount;
                                $paid_holiday_days_count++;
                            }
                        }
                    }
                    $total_holiday_pay_adj = round($total_holiday_pay_adj, 3);
                    $total_holiday_pay_adj_for_db = round($total_holiday_pay_adj_for_db, 3);

                    // Days_Present excludes days that were paid as holiday (no double-counting)
                    $daysPresentExclHoliday = max(0, $daysPresent - $paid_holiday_days_count);

                    $computed_total_amount = max(0, round(
                        ($daily_rate * $daysPresentExclHoliday)
                            - $late_amount
                            - $undertime_amount
                            + $overtimePay
                            + $total_holiday_pay_adj,
                        3
                    ));

                    $adjSummaryData = [
                        'Employee_ID' => $adjEmp->employee_id,
                        'Preceding_Payroll_Period_ID' => $preceding_payroll_period_id,
                        'Hours_Worked' => round(floatval($adjEmp->total_hours_worked ?? 0), 3),
                        'Daily' => round($daily_rate, 3),
                        'Late' => round($late_day_fraction_raw_adj, 3), // Raw day fraction (before offset)
                        'Late_Amount' => $late_amount, // Net amount (after offset)
                        'Undertime' => round($undertime_day_fraction_raw_adj, 3), // Raw day fraction (before offset)
                        'Undertime_Amount' => $undertime_amount, // Net amount (after offset)
                        'Absent' => round($absent_days_total_raw_adj, 3), // Raw day fraction (before offset)
                        'Absent_Amount' => $absent_amount, // Net amount (after offset)
                        'Total_Deduction' => $total_deduction,
                        'Total_Amount' => $computed_total_amount,
                        'Overtime' => round($overtimePay, 3),
                        'Holiday_Pay' => $total_holiday_pay_adj_for_db, // Only amount when employee worked
                        'Active' => 1,
                        'Date_Stamp' => now(),
                        'Encoder_ID' => $this->resolveProcessAttendanceEncoderId(),
                        'Is_Offset' => $hasOffsetAdj ? 1 : 0,
                        'Days_Covered' => floatval($adjEmp->days_covered ?? 0),
                        'Days_Present' => $daysPresent, // Raw count; Total_Amount uses daysPresentExclHoliday
                        'Source_Summary_ID' => $sourceSummary->Time_Data_Summary_ID ?? null,
                        'Adjustment_Type' => 'RECONCILIATION',
                        'Status' => 'PENDING',
                        'Reason' => 'Auto-generated from SP_ProcessTimeDataAdj reconciliation',
                        'Created_By' => $this->resolveProcessAttendanceEncoderId(),
                        'Updated_At' => now(),
                    ];

                    // Check if record already exists
                    $existingAdj = DB::table('time_data_summary_adj')
                        ->where('Preceding_Payroll_Period_ID', $preceding_payroll_period_id)
                        ->where('Employee_ID', $adjEmp->employee_id)
                        ->first();

                    if ($existingAdj) {
                        // GUARD: Only preserve offset values if BOTH the existing record has Is_Offset = 1
                        // AND there are actual time_data_adj records with applied_offset = 1.
                        // If Is_Offset was incorrectly set (no actual offsets), allow the update to correct it.
                        if (!empty($existingAdj->Is_Offset) && $hasOffsetAdj) {
                            // Still update reconciliation fields in time_data_summary below,
                            // but use the EXISTING adj deduction totals instead of recalculated ones.
                            $total_deduction = floatval($existingAdj->Total_Deduction ?? 0);
                            $late_amount = floatval($existingAdj->Late_Amount ?? 0);
                            $undertime_amount = floatval($existingAdj->Undertime_Amount ?? 0);
                            $absent_amount = floatval($existingAdj->Absent_Amount ?? 0);
                        } else {
                            DB::table('time_data_summary_adj')
                                ->where('Preceding_Payroll_Period_ID', $preceding_payroll_period_id)
                                ->where('Employee_ID', $adjEmp->employee_id)
                                ->update($adjSummaryData);
                        }
                    } else {
                        $adjSummaryData['Created_At'] = now();
                        DB::table('time_data_summary_adj')->insert($adjSummaryData);
                    }

                    // ========== RECONCILIATION ADJUSTMENT ==========
                    // Store TWO columns in time_data_summary (no net):
                    //   Adjustment_Amount = Adj. Late + Adj. Undertime + Adj. Absent (e.g. 467.88)
                    //   Adjustment_Amount_OT_Holiday = Adj. Overtime + Adj. Holiday (e.g. 856.71)
                    // Total_Amount formula then does: ... − Adjustment_Amount + Adjustment_Amount_OT_Holiday
                    // so Adj. Period Total = 467.88 − 856.71 = −388.83 (credit).
                    $adjustmentAmount = round($total_deduction, 3); // deductions only
                    $reconciliationDiff = 0;
                    if ($sourceSummary) {
                        $originalDeduction = floatval($sourceSummary->Total_Deduction ?? 0);
                        $actualDeduction = floatval($total_deduction);
                        $reconciliationDiff = round($originalDeduction - $actualDeduction, 3);
                    }

                    // Update current period's time_data_summary with the Preceding Period Adj total
                    // Adjustment_Amount = deductions only (Late + Undertime + Absent).
                    // Adjustment_Amount_OT_Holiday = Adj. Overtime + Adj. Holiday (treat null as 0). Always set.
                    // Note: $payroll_period_id is the CURRENT period we are processing
                    $adjOvertime = floatval($overtimePay ?? 0);
                    $adjHoliday = floatval($total_holiday_pay_adj_for_db ?? 0);
                    $adjustmentAmountOtHoliday = round($adjOvertime + $adjHoliday, 3);
                    $updatePayload = [
                        'Adjustment_Amount' => $adjustmentAmount,
                        'Adjustment_Period_ID' => $preceding_payroll_period_id,
                        'Adjustment_Amount_OT_Holiday' => $adjustmentAmountOtHoliday,
                    ];
                    DB::table('time_data_summary')
                        ->where('Payroll_Period_ID', $payroll_period_id)
                        ->where('Employee_ID', $adjEmp->employee_id)
                        ->update($updatePayload);

                    \Log::info('[ProcessAttendance] Reconciliation applied', [
                        'employee_id' => $adjEmp->employee_id,
                        'preceding_period' => $preceding_payroll_period_id,
                        'current_period' => $payroll_period_id,
                        'adjustment_amount' => $adjustmentAmount,
                        'adjustment_amount_ot_holiday' => $adjustmentAmountOtHoliday,
                        'reconciliation_diff' => $reconciliationDiff,
                    ]);

                    $adjSummaryCount++;
                } catch (\Exception $empEx) {
                    Log::warning('[ProcessAttendance] time_data_summary_adj employee failed', [
                        'employee_id' => $adjEmp->employee_id ?? null,
                        'preceding_period' => $preceding_payroll_period_id,
                        'error' => $empEx->getMessage()
                    ]);
                    continue;
                }
            }

            Log::info('[ProcessAttendance] time_data_summary_adj completed', [
                'preceding_period' => $preceding_payroll_period_id,
                'employees_processed' => $adjSummaryCount
            ]);

            $result['data']['adj_summary_count'] = $adjSummaryCount;

            // Set Adjustment_Period_ID for ALL employees in this period so the view can show
            // "Preceding Period Adjustments" and load adjustment_summary when a row exists.
            DB::table('time_data_summary')
                ->where('Payroll_Period_ID', $payroll_period_id)
                ->update(['Adjustment_Period_ID' => $preceding_payroll_period_id]);
        } catch (\Exception $e) {
            Log::error('[ProcessAttendance] time_data_summary_adj failed', [
                'preceding_period' => $preceding_payroll_period_id,
                'error' => $e->getMessage()
            ]);
        }

        // Add adjustment info to result data
        $result['data']['adj_dates_processed'] = $adjProcessedDates;
        $result['data']['preceding_payroll_period_id'] = $preceding_payroll_period_id;
    }

    /**
     * Pick the best fix-schedule day row to use as a template (Mon–Fri with complete times).
     */
    private function resolveFixScheduleTemplateDetail(array $detailsByDayId): ?object
    {
        if (
            isset($detailsByDayId[1]) && (int) ($detailsByDayId[1]->is_restday ?? 0) === 0
            && ! empty($detailsByDayId[1]->am_in) && ! empty($detailsByDayId[1]->pm_out)
        ) {
            return $detailsByDayId[1];
        }

        $best = null;
        $bestPm = '';
        foreach ([1, 2, 3, 4, 5, 6, 7] as $dayId) {
            $row = $detailsByDayId[$dayId] ?? null;
            if (! $row || (int) ($row->is_restday ?? 0) === 1 || empty($row->am_in) || empty($row->pm_out)) {
                continue;
            }
            $pm = (string) $row->pm_out;
            if ($best === null || $pm > $bestPm) {
                $best = $row;
                $bestPm = $pm;
            }
        }

        return $best;
    }

    /**
     * Merge a calendar-day schedule row with the template (fill missing times, use later PM out for flexi).
     */
    private function mergeFixScheduleDetailForDtr($detail, ?object $template): ?object
    {
        if (! $template) {
            return $detail;
        }
        if (! $detail || (int) ($detail->is_restday ?? 0) === 1) {
            return $template;
        }

        $merged = clone $template;
        if (! empty($detail->am_in)) {
            $merged->am_in = $detail->am_in;
        }
        if (! empty($detail->pm_out)) {
            $detailPm = Carbon::parse($detail->pm_out);
            $templatePm = Carbon::parse($template->pm_out);
            $merged->pm_out = $detailPm->gte($templatePm) ? $detail->pm_out : $template->pm_out;
        }
        if (floatval($detail->flexi_hours ?? 0) > 0) {
            $merged->flexi_hours = $detail->flexi_hours;
            $merged->grace_period = $detail->grace_period;
        }
        if (floatval($detail->work_hours ?? 0) > 0) {
            $merged->work_hours = $detail->work_hours;
        }

        return $merged;
    }

    /**
     * Build one DTR schedule map entry from a fix/shift schedule detail row.
     */
    private function buildDtrScheduleEntryFromDetail($detail, float $defaultFlexi = 0.0, float $defaultGrace = 0.0): array
    {
        $entry = [
            'am_in' => '',
            'pm_out' => '',
            'am_in_raw' => '',
            'pm_out_raw' => '',
            'flexi_hours' => 0.0,
            'grace_period' => 0.0,
            'work_hours' => 8.0,
            'lunch_break_minutes' => 60,
        ];

        if (! $detail) {
            return $entry;
        }

        if ((int) ($detail->is_restday ?? 0) === 1) {
            return $entry;
        }

        if (! empty($detail->am_in)) {
            $entry['am_in_raw'] = Carbon::parse($detail->am_in)->format('H:i:s');
            $entry['am_in'] = Carbon::parse($detail->am_in)->format('h:i A');
        }
        if (! empty($detail->pm_out)) {
            $entry['pm_out_raw'] = Carbon::parse($detail->pm_out)->format('H:i:s');
            $entry['pm_out'] = Carbon::parse($detail->pm_out)->format('h:i A');
        }

        $flexi = floatval($detail->flexi_hours ?? 0);
        $grace = floatval($detail->grace_period ?? 0);
        if ($flexi <= 0 && $defaultFlexi > 0) {
            $flexi = $defaultFlexi;
            $grace = $defaultGrace;
        }
        $entry['flexi_hours'] = $flexi;
        $entry['grace_period'] = $grace;
        $entry['work_hours'] = floatval($detail->work_hours ?? 8);
        if ($entry['work_hours'] <= 0) {
            $entry['work_hours'] = 8.0;
        }

        $lunchMinutes = 60;
        if (! empty($detail->break_in) && ! empty($detail->break_out)) {
            $lunchMinutes = max(0, Carbon::parse($detail->break_out)->diffInMinutes(Carbon::parse($detail->break_in)));
        } elseif (! empty($detail->am_out) && ! empty($detail->pm_in)) {
            $lunchMinutes = max(0, Carbon::parse($detail->pm_in)->diffInMinutes(Carbon::parse($detail->am_out)));
        }
        $entry['lunch_break_minutes'] = $lunchMinutes;

        return $entry;
    }

    /**
     * Per calendar date: scheduled AM/PM times and flexi context for DTR tardiness display.
     *
     * @param  object  $employee  Row with work_schedule_id, is_shifting
     * @param  array<string, object>  $recordsByDate  Optional time_data rows keyed by Y-m-d
     * @return array<string, array<string, mixed>>
     */
    private function buildDtrScheduleTimesMap($employee, Carbon $tableStart, Carbon $tableEnd, array $recordsByDate = []): array
    {
        $map = [];
        $current = $tableStart->copy();

        $emptyEntry = [
            'am_in' => '',
            'pm_out' => '',
            'am_in_raw' => '',
            'pm_out_raw' => '',
            'flexi_hours' => 0.0,
            'grace_period' => 0.0,
            'work_hours' => 8.0,
            'lunch_break_minutes' => 60,
        ];

        $employeeWid = (int) ($employee->work_schedule_id ?? 0);
        if ($employeeWid <= 0) {
            while ($current->lte($tableEnd)) {
                $map[$current->format('Y-m-d')] = $emptyEntry;
                $current->addDay();
            }

            return $map;
        }

        $fixScheduleCache = [];

        $isShifting = (int) ($employee->is_shifting ?? 0) === 1;

        if ($isShifting) {
            $dates = [];
            $d = $tableStart->copy();
            while ($d->lte($tableEnd)) {
                $dates[] = $d->format('Y-m-d');
                $d->addDay();
            }

            $shiftRows = DB::table('shift_schedules_details')
                ->where('shift_schedule_id', $employeeWid)
                ->whereIn('shift_date', $dates)
                ->get();

            $shiftByDate = [];
            $defaultShiftFlexi = 0.0;
            $defaultShiftGrace = 0.0;
            foreach ($shiftRows as $sr) {
                $k = Carbon::parse($sr->shift_date)->format('Y-m-d');
                $shiftByDate[$k] = $sr;
                $rowFlexi = floatval($sr->flexi_hours ?? 0);
                if ($rowFlexi > 0) {
                    $defaultShiftFlexi = $rowFlexi;
                    $defaultShiftGrace = floatval($sr->grace_period ?? 0);
                }
            }

            while ($current->lte($tableEnd)) {
                $key = $current->format('Y-m-d');
                $row = $shiftByDate[$key] ?? null;
                $map[$key] = $row
                    ? $this->buildDtrScheduleEntryFromDetail($row, $defaultShiftFlexi, $defaultShiftGrace)
                    : $emptyEntry;
                $current->addDay();
            }

            return $map;
        }

        $carbonToDayId = [0 => 7, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6];

        while ($current->lte($tableEnd)) {
            $key = $current->format('Y-m-d');
            $record = $recordsByDate[$key] ?? null;
            $wid = (int) ($record->work_schedule_id ?? 0);
            if ($wid <= 0) {
                $wid = $employeeWid;
            }

            if (! isset($fixScheduleCache[$wid])) {
                $fixRows = DB::table('fix_schedules_details')
                    ->where('fix_schedule_id', $wid)
                    ->get();
                $detailsByDayId = [];
                $defaultFlexi = 0.0;
                $defaultGrace = 0.0;
                foreach ($fixRows as $fr) {
                    $detailsByDayId[(int) $fr->day_id] = $fr;
                    $rowFlexi = floatval($fr->flexi_hours ?? 0);
                    if ($rowFlexi > 0) {
                        $defaultFlexi = $rowFlexi;
                        $defaultGrace = floatval($fr->grace_period ?? 0);
                    }
                }
                if (isset($detailsByDayId[1]) && floatval($detailsByDayId[1]->flexi_hours ?? 0) > 0) {
                    $defaultFlexi = floatval($detailsByDayId[1]->flexi_hours);
                    $defaultGrace = floatval($detailsByDayId[1]->grace_period ?? 0);
                }
                $templateDetail = $this->resolveFixScheduleTemplateDetail($detailsByDayId);
                $fixScheduleCache[$wid] = [
                    'by_day' => $detailsByDayId,
                    'default_flexi' => $defaultFlexi,
                    'default_grace' => $defaultGrace,
                    'template' => $templateDetail,
                ];
            }

            $context = $fixScheduleCache[$wid];
            $dayId = $carbonToDayId[$current->dayOfWeek];
            $dayDetail = $context['by_day'][$dayId] ?? null;
            $detail = $this->mergeFixScheduleDetailForDtr($dayDetail, $context['template'] ?? null);

            // If this date has attendance but the day row is empty/restday, use the template outright.
            $record = $recordsByDate[$key] ?? null;
            if ($record && (! $detail || ($dayDetail && (int) ($dayDetail->is_restday ?? 0) === 1))) {
                $detail = $context['template'] ?? $dayDetail;
            }

            $map[$key] = $this->buildDtrScheduleEntryFromDetail(
                $detail,
                $context['default_flexi'],
                $context['default_grace']
            );
            $current->addDay();
        }

        return $map;
    }

    /**
     * Whether this payroll period is a second-half cutoff (Regular, COS, etc.).
     */
    private function isDtrSecondHalfPayrollPeriod($payrollPeriod): bool
    {
        if (!$payrollPeriod) {
            return false;
        }

        $cutoffId = (int) ($payrollPeriod->payroll_cutoff_id ?? 0);
        if ($cutoffId <= 0) {
            return false;
        }

        $cutoffName = DB::table('payroll_cutoffs')
            ->where('id', $cutoffId)
            ->value('name');

        if (is_string($cutoffName) && $cutoffName !== '') {
            $lower = strtolower($cutoffName);
            if (str_contains($lower, '2nd') || str_contains($lower, 'second')) {
                return true;
            }
            if (str_contains($lower, '1st') || str_contains($lower, 'first')) {
                return false;
            }
        }

        // Legacy fallback: regular second-half cutoff id
        return $cutoffId === 3;
    }

    /**
     * Resolve the paired first-half payroll period for a second-half DTR view.
     * Scopes by payroll_interval_id, calendar month, and shared payroll_period_Etype rows
     * so COS 2nd half resolves to COS 1st half (not regular).
     */
    private function resolveDtrFirstHalfPayrollPeriodId($payrollPeriod): ?int
    {
        $currentId = (int) ($payrollPeriod->id ?? 0);
        $intervalId = (int) ($payrollPeriod->payroll_interval_id ?? 0);
        $start = !empty($payrollPeriod->attendance_start_date)
            ? Carbon::parse($payrollPeriod->attendance_start_date)
            : null;

        if ($currentId <= 0 || $intervalId <= 0 || !$start) {
            return null;
        }

        $employmentTypeIds = DB::table('payroll_period_Etype')
            ->where('payrollperiod_id', $currentId)
            ->pluck('employmenttype_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $firstHalfCutoffIds = DB::table('payroll_cutoffs')
            ->where('payroll_interval_id', $intervalId)
            ->where(function ($q) {
                $q->where('name', 'LIKE', '%1st%')
                    ->orWhere('name', 'LIKE', '%1ST%')
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%first%half%']);
            })
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (empty($firstHalfCutoffIds)) {
            // Legacy fallback for regular payroll cutoffs
            $firstHalfCutoffIds = [1];
        }

        $query = DB::table('payroll_periods as pp')
            ->where('pp.payroll_interval_id', $intervalId)
            ->whereIn('pp.payroll_cutoff_id', $firstHalfCutoffIds)
            ->whereYear('pp.attendance_start_date', $start->year)
            ->whereMonth('pp.attendance_start_date', $start->month);

        if (!empty($employmentTypeIds)) {
            $query->whereExists(function ($q) use ($employmentTypeIds) {
                $q->select(DB::raw(1))
                    ->from('payroll_period_Etype as ppe')
                    ->whereColumn('ppe.payrollperiod_id', 'pp.id')
                    ->whereIn('ppe.employmenttype_id', $employmentTypeIds);
            });
        }

        $firstHalfId = $query->value('pp.id');

        return !empty($firstHalfId) ? (int) $firstHalfId : null;
    }

    /**
     * For Second-Half payroll periods, include both 1st and 2nd half time_data for the
     * same month so the DTR month grid shows complete data (Regular and COS).
     *
     * @return int[]
     */
    private function getDtrIncludedPayrollPeriodIds($payrollPeriod): array
    {
        $currentId = (int) ($payrollPeriod->id ?? 0);
        if ($currentId <= 0) {
            return [];
        }

        $ids = [$currentId];

        if (!$this->isDtrSecondHalfPayrollPeriod($payrollPeriod)) {
            return $ids;
        }

        $firstHalfId = $this->resolveDtrFirstHalfPayrollPeriodId($payrollPeriod);
        if (!empty($firstHalfId)) {
            $ids[] = $firstHalfId;
        }

        $ids = array_values(array_unique(array_filter($ids, fn($v) => (int) $v > 0)));
        sort($ids);

        return $ids;
    }

    /**
     * Expand second-half payroll period bounds to the full calendar month for DTR display.
     */
    private function normalizeDtrPayrollPeriodForDisplay($payrollPeriod): array
    {
        $period = (array) $payrollPeriod;

        if (
            $this->isDtrSecondHalfPayrollPeriod($payrollPeriod)
            && !empty($period['attendance_start_date'])
            && !empty($period['attendance_end_date'])
        ) {
            $period['attendance_start_date'] = Carbon::parse($period['attendance_start_date'])
                ->startOfMonth()
                ->format('Y-m-d');
            $period['attendance_end_date'] = Carbon::parse($period['attendance_end_date'])
                ->endOfMonth()
                ->format('Y-m-d');
        }

        return $period;
    }

    /**
     * For second-half DTR, overwrite first-half adjusted rows with true pre-cutoff snapshot values.
     */
    private function applyDtrSecondHalfSnapshotOverrides($dailyTimeRecords, int $employeeId, $payrollPeriod)
    {
        $currentPeriodId = (int) ($payrollPeriod->id ?? 0);
        if (!$this->isDtrSecondHalfPayrollPeriod($payrollPeriod) || $currentPeriodId <= 0 || $employeeId <= 0) {
            return $dailyTimeRecords;
        }

        $snapshots = DB::table('time_data_adj')
            ->select(
                'date',
                'am_in',
                'am_out',
                'break_in',
                'break_out',
                'pm_in',
                'pm_out',
                'work_hours',
                'late',
                'undertime',
                'absent',
                'leave',
                'is_ob',
                'ob_id',
                'is_holiday',
                'holiday_id',
                'holiday_pay',
                'is_ot',
                'ot_id',
                'ot_pay',
                'remarks',
                'note',
                'late_offset',
                'undertime_offset',
                'absent_offset',
                'applied_offset',
                'excess_hours',
                'ot_hours',
                'work_schedule_id',
                'is_shifting',
                'is_restday'
            )
            ->where('employee_id', $employeeId)
            ->where('target_payroll_period_id', $currentPeriodId)
            ->where('adjustment_type', 'PRE_CUTOFF_SNAPSHOT')
            ->where(function ($q) {
                $q->whereNull('is_assumed')->orWhere('is_assumed', 0);
            })
            ->get();

        if ($snapshots->isEmpty()) {
            return $dailyTimeRecords;
        }

        $byDate = [];
        foreach ($dailyTimeRecords as $record) {
            $dateKey = Carbon::parse($record->date)->format('Y-m-d');
            $byDate[$dateKey] = $record;
        }

        foreach ($snapshots as $snap) {
            $dateKey = Carbon::parse($snap->date)->format('Y-m-d');
            $base = $byDate[$dateKey] ?? null;
            if (!$base) {
                continue;
            }

            $base->am_in = $snap->am_in;
            $base->am_out = $snap->am_out;
            $base->break_in = $snap->break_in;
            $base->break_out = $snap->break_out;
            $base->pm_in = $snap->pm_in;
            $base->pm_out = $snap->pm_out;
            $base->work_hours = $snap->work_hours;
            $base->late = $snap->late;
            $base->undertime = $snap->undertime;
            $base->absent = $snap->absent;
            $base->leave = $snap->leave;
            $base->is_ob = $snap->is_ob;
            $base->ob_id = $snap->ob_id;
            $base->is_holiday = $snap->is_holiday;
            $base->holiday_id = $snap->holiday_id;
            $base->holiday_pay = $snap->holiday_pay;
            $base->is_ot = $snap->is_ot;
            $base->ot_id = $snap->ot_id;
            $base->ot_pay = $snap->ot_pay;
            $base->remarks = $snap->remarks;
            $base->note = $snap->note;
            $base->late_offset = $snap->late_offset;
            $base->undertime_offset = $snap->undertime_offset;
            $base->absent_offset = $snap->absent_offset;
            $base->applied_offset = $snap->applied_offset;
            $base->excess_hours = $snap->excess_hours;
            $base->ot_hours = $snap->ot_hours;
            $base->work_schedule_id = $snap->work_schedule_id;
            $base->is_shifting = $snap->is_shifting;
            $base->is_restday = $snap->is_restday;

            $byDate[$dateKey] = $base;
        }

        return collect($byDate)
            ->sortBy(function ($row) {
                return $row->date;
            })
            ->values();
    }
}
