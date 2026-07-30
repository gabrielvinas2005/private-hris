<?php

namespace App\Console\Commands;

use App\Http\Controllers\ProcessAttendanceController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunProcessAttendance extends Command
{
    protected $signature = 'attendance:run-process {--payload=}';
    protected $description = 'Run Process Attendance in a detached worker process';

    public function handle(): int
    {
        $encoded = (string) ($this->option('payload') ?? '');
        if ($encoded === '') {
            $this->error('Missing --payload.');
            return self::FAILURE;
        }

        $decoded = base64_decode($encoded, true);
        $payload = json_decode($decoded ?: '', true);
        if (!is_array($payload)) {
            $this->error('Invalid payload.');
            return self::FAILURE;
        }

        $processRunId = $payload['process_run_id'] ?? null;
        Log::info('[ProcessAttendance][Async] Worker started', [
            'process_run_id' => $processRunId,
            'payroll_period_id' => $payload['payroll_period_id'] ?? null,
            'user_id' => $payload['user_id'] ?? null,
        ]);

        $markFailed = function (string $message) use ($processRunId): void {
            if (empty($processRunId)) {
                return;
            }
            try {
                $existing = DB::table('attendance_process_runs')
                    ->where('id', $processRunId)
                    ->value('status');
                if (strtolower((string) $existing) === 'completed') {
                    Log::warning('[ProcessAttendance][Async] Worker error ignored (run already completed)', [
                        'process_run_id' => $processRunId,
                        'message' => $message,
                    ]);
                    return;
                }
                DB::table('attendance_process_runs')
                    ->where('id', $processRunId)
                    ->update([
                        'status' => 'failed',
                        'updated_at' => now(),
                        'progress_message' => mb_substr($message, 0, 500),
                    ]);
            } catch (Throwable $e) {
                Log::error('[ProcessAttendance][Async] Failed to mark run failed', [
                    'process_run_id' => $processRunId,
                    'error' => $e->getMessage(),
                ]);
            }
        };

        register_shutdown_function(function () use ($markFailed, $processRunId): void {
            $err = error_get_last();
            if (!$err) {
                return;
            }
            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
            if (!in_array($err['type'] ?? null, $fatalTypes, true)) {
                return;
            }
            $markFailed('Worker crashed: ' . ($err['message'] ?? 'Fatal error'));
            Log::error('[ProcessAttendance][Async] Worker shutdown (fatal)', [
                'process_run_id' => $processRunId,
                'error' => $err,
            ]);
        });

        try {
            /** @var ProcessAttendanceController $controller */
            $controller = app(ProcessAttendanceController::class);
            $controller->runAttendanceProcessWorker($payload);
            Log::info('[ProcessAttendance][Async] Worker finished', [
                'process_run_id' => $processRunId,
                'task' => $payload['task'] ?? 'process',
            ]);
        } catch (Throwable $e) {
            $msg = $e->getMessage() ?: 'Unknown worker error';
            Log::error('[ProcessAttendance][Async] Worker failed', [
                'process_run_id' => $processRunId,
                'task' => $payload['task'] ?? 'process',
                'error' => $msg,
            ]);
            $markFailed('Worker failed: ' . $msg);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
