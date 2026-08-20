<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Traits\ApiResponse;

class WFHAttendanceController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get employee information for WFH attendance
     */
    public function getEmployeeInfo($userId)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Resolve the actual employee using the current user's employee_no
            $employee = DB::table('users as u')
                ->join('employees as e', 'u.employee_no', '=', 'e.employee_no')
                ->leftJoin('departments as d', 'e.department_id', '=', 'd.id')
                ->leftJoin('positions as p', 'e.position_id', '=', 'p.id')
                ->leftJoin('employment_types as t', 'e.employment_type_id', '=', 't.id')
                ->select([
                    'e.id as employee_id',
                    'e.employee_no',
                    'e.photo',
                    'd.name as department',
                    'p.name as position',
                    't.name as employment_type',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                               CONCAT(e.first_name,' ',substring(e.middle_name,1,1),'. ',e.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](e.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                            END as name")
                ])
                ->where('u.id', $userId)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee not found', 404);
            }

            return $this->successResponse([
                'employee_info' => $employee
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load employee information: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get active payroll periods for WFH attendance
     */
    public function getPayrollPeriods($employeeId)
    {
        try {
            $periods = DB::table('payroll_periods')
                ->select([
                    'id',
                    'payroll_cutoff_id as cut_off',
                    'attendance_start_date',
                    'attendance_end_date',
                    'posted as is_posted'
                ])
                ->where('posted', 0) // Only unposted periods
                ->orderBy('attendance_start_date', 'desc')
                ->get();

            return $this->successResponse([
                'payroll_periods' => $periods
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load payroll periods: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get WFH time data for a specific period
     */
    public function getTimeData($employeeId, $payrollPeriodId)
    {
        try {
            $payrollPeriod = DB::table('payroll_periods')
                ->where('id', $payrollPeriodId)
                ->first();

            if (!$payrollPeriod) {
                return $this->errorResponse('Payroll period not found', 404);
            }

            // Get existing time data for the period
            $timeData = DB::table('time_data')
                ->where('employee_id', $employeeId)
                ->where('payroll_period_id', $payrollPeriodId)
                ->where('is_wfh', 1) // Only WFH records
                ->orderBy('date', 'asc')
                ->get();

            // Generate all dates in the payroll period
            $startDate = Carbon::parse($payrollPeriod->attendance_start_date);
            $endDate = Carbon::parse($payrollPeriod->attendance_end_date);
            $allDates = [];

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dateStr = $date->format('Y-m-d');

                // Find existing record for this date
                $existingRecord = $timeData->firstWhere('date', $dateStr);

                if ($existingRecord) {
                    $allDates[] = [
                        'id' => $existingRecord->id,
                        'date' => $dateStr,
                        'am_in' => $existingRecord->am_in,
                        'am_out' => $existingRecord->am_out,
                        'break_in' => $existingRecord->break_in,
                        'break_out' => $existingRecord->break_out,
                        'pm_in' => $existingRecord->pm_in,
                        'pm_out' => $existingRecord->pm_out,
                        'work_hours' => $existingRecord->work_hours,
                        'remarks' => $existingRecord->remarks,
                        'is_wfh' => $existingRecord->is_wfh,
                        'wfh_reason' => $existingRecord->wfh_reason,
                        'wfh_location' => $existingRecord->wfh_location,
                        'status' => $this->getAttendanceStatus($existingRecord)
                    ];
                } else {
                    $allDates[] = [
                        'id' => null,
                        'date' => $dateStr,
                        'am_in' => null,
                        'am_out' => null,
                        'break_in' => null,
                        'break_out' => null,
                        'pm_in' => null,
                        'pm_out' => null,
                        'work_hours' => 0,
                        'remarks' => '',
                        'is_wfh' => 0,
                        'wfh_reason' => '',
                        'wfh_location' => '',
                        'status' => 'empty'
                    ];
                }
            }

            return $this->successResponse([
                'time_data' => $allDates,
                'payroll_period' => $payrollPeriod
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load time data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Time In for WFH
     */
    public function timeIn(Request $request, $employeeId)
    {
        try {
            $data = $request->validate([
                'wfh_reason' => 'nullable|string|max:255',
                'wfh_location' => 'nullable|string|max:255',
            ]);

            $today = now()->format('Y-m-d');

            // --- Schedule Gate: always resolve schedule from fix_schedules_details ---
            $scheduleDetail = $this->getEmployeeScheduleForDate($employeeId, $today);

            if ($scheduleDetail) {
                // Block clock-in on scheduled rest days
                if ((int) $scheduleDetail->is_restday === 1) {
                    return $this->errorResponse(
                        'Today is a scheduled rest day. Portal clock-in is not available.',
                        400
                    );
                }

                // Block portal clock-in when schedule is on-site (is_wfh = 0)
                if ((int) $scheduleDetail->is_wfh === 0) {
                    return $this->errorResponse(
                        'Your schedule today requires on-site attendance. Please use the company hardware attendance machine.',
                        403
                    );
                }
            }
            // --- End Schedule Gate ---

            // Get employee's work_schedule_id
            $employee = DB::table('employees')
                ->where('id', $employeeId)
                ->select('work_schedule_id')
                ->first();

            $workScheduleId = $employee->work_schedule_id ?? 0;

            // Tag source based on schedule (wfh_portal when is_wfh=1, web_clock as fallback)
            $entrySource = ($scheduleDetail && (int) $scheduleDetail->is_wfh === 1)
                ? 'wfh_portal'
                : 'web_clock';

            // Check if already timed in today
            $existingRecord = DB::table('time_data')
                ->where('employee_id', $employeeId)
                ->where('date', $today)
                ->where('is_wfh', 1)
                ->first();

            if ($existingRecord && $existingRecord->am_in) {
                return $this->errorResponse('You have already timed in today.', 400);
            }

            $currentTime = now()->format('H:i:s');

            if ($existingRecord) {
                DB::table('time_data')
                    ->where('id', $existingRecord->id)
                    ->update([
                        'am_in'                => $currentTime,
                        'work_schedule_id'     => $workScheduleId,
                        'wfh_reason'           => $data['wfh_reason'] ?? '',
                        'wfh_location'         => $data['wfh_location'] ?? '',
                        'manual_entry_source'  => $entrySource,
                        'entry_timestamp'      => now(),
                        'updated_at'           => now(),
                    ]);

                $recordId = $existingRecord->id;
            } else {
                $recordId = DB::table('time_data')->insertGetId([
                    'employee_id'          => $employeeId,
                    'payroll_period_id'    => $this->getCurrentPayrollPeriodId(),
                    'work_schedule_id'     => $workScheduleId,
                    'date'                 => $today,
                    'am_in'               => $currentTime,
                    'is_wfh'              => 1,
                    'wfh_reason'           => $data['wfh_reason'] ?? '',
                    'wfh_location'         => $data['wfh_location'] ?? '',
                    'manual_entry_source'  => $entrySource,
                    'entry_timestamp'      => now(),
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }

            return $this->successResponse([
                'message'         => 'Time in recorded successfully',
                'time_in'         => $currentTime,
                'record_id'       => $recordId,
                'entry_source'    => $entrySource,
                'record'          => DB::table('time_data')->where('id', $recordId)->first(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to record time in: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Time Out for WFH
     */
    public function timeOut(Request $request, $employeeId)
    {
        try {
            $data = $request->validate([
                'demo_date' => 'nullable|date' // Add demo_date support
            ]);

            // Use demo_date if provided, otherwise use actual date
            $today = $data['demo_date'] ?? now()->format('Y-m-d');

            // Find today's record
            $existingRecord = DB::table('time_data')
                ->where('employee_id', $employeeId)
                ->where('date', $today)
                ->where('is_wfh', 1)
                ->first();

            if (!$existingRecord) {
                return $this->errorResponse('No time in record found for today', 400);
            }

            if ($existingRecord->pm_out) {
                return $this->errorResponse('You have already timed out today', 400);
            }

            $currentTime = now()->format('H:i:s');
            $workHours = $this->calculateWorkHoursFromRecord($existingRecord, $currentTime);

            // Update record
            DB::table('time_data')
                ->where('id', $existingRecord->id)
                ->update([
                    'pm_out' => $currentTime,
                    'work_hours' => $workHours,
                    'updated_at' => now()
                ]);

            return $this->successResponse([
                'message' => 'Time out recorded successfully',
                'time_out' => $currentTime,
                'work_hours' => $workHours
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to record time out: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Break In for WFH
     */
    public function breakIn(Request $request, $employeeId)
    {
        try {
            $data = $request->validate([
                'demo_date' => 'nullable|date' // Add demo_date support
            ]);

            // Use demo_date if provided, otherwise use actual date
            $today = $data['demo_date'] ?? now()->format('Y-m-d');

            // Find today's record
            $existingRecord = DB::table('time_data')
                ->where('employee_id', $employeeId)
                ->where('date', $today)
                ->where('is_wfh', 1)
                ->first();

            if (!$existingRecord || !$existingRecord->am_in) {
                return $this->errorResponse('Please time in first', 400);
            }

            if ($existingRecord->break_in) {
                return $this->errorResponse('You are already on break', 400);
            }

            $currentTime = now()->format('H:i:s');

            // Update record
            DB::table('time_data')
                ->where('id', $existingRecord->id)
                ->update([
                    'break_in' => $currentTime,
                    'updated_at' => now()
                ]);

            return $this->successResponse([
                'message' => 'Break in recorded successfully',
                'break_in' => $currentTime
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to record break in: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Break Out for WFH
     */
    public function breakOut(Request $request, $employeeId)
    {
        try {
            $data = $request->validate([
                'demo_date' => 'nullable|date' // Add demo_date support
            ]);

            // Use demo_date if provided, otherwise use actual date
            $today = $data['demo_date'] ?? now()->format('Y-m-d');

            // Find today's record
            $existingRecord = DB::table('time_data')
                ->where('employee_id', $employeeId)
                ->where('date', $today)
                ->where('is_wfh', 1)
                ->first();

            if (!$existingRecord || !$existingRecord->break_in) {
                return $this->errorResponse('No break in record found', 400);
            }

            if ($existingRecord->break_out) {
                return $this->errorResponse('You have already ended your break', 400);
            }

            $currentTime = now()->format('H:i:s');

            // Update record
            DB::table('time_data')
                ->where('id', $existingRecord->id)
                ->update([
                    'break_out' => $currentTime,
                    'updated_at' => now()
                ]);

            return $this->successResponse([
                'message' => 'Break out recorded successfully',
                'break_out' => $currentTime
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to record break out: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get today's WFH attendance status
     */
    public function getTodayStatus($employeeId)
    {
        try {
            $today = now()->format('Y-m-d');

            // --- Always resolve schedule from fix_schedules_details ---
            $scheduleDetail = $this->getEmployeeScheduleForDate($employeeId, $today);

            $isWfhDay   = $scheduleDetail ? (int) $scheduleDetail->is_wfh === 1   : null;
            $isRestDay  = $scheduleDetail ? (int) $scheduleDetail->is_restday === 1 : null;

            // Determine working mode for the frontend
            $workingMode = 'unknown';
            if ($isRestDay) {
                $workingMode = 'rest_day';
            } elseif ($isWfhDay === true) {
                $workingMode = 'wfh';
            } elseif ($isWfhDay === false) {
                $workingMode = 'onsite';
            }

            $hasApprovedWfh = DB::table('wfh_application')
                ->where('employee_id', $employeeId)
                ->where('cancelled', 0)
                ->where('disapproved', 0)
                ->where('disapproved_2', 0)
                ->where('disapproved_3', 0)
                ->where('approved_3', 1)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->exists();

            $record = DB::table('time_data')
                ->where('employee_id', $employeeId)
                ->where('date', $today)
                ->where('is_wfh', 1)
                ->first();

            if (!$record) {
                return $this->successResponse([
                    'status'          => 'not_started',
                    'message'         => 'No attendance record for today',
                    'record'          => null,
                    'has_approved_wfh'=> $hasApprovedWfh,
                    'working_mode'    => $workingMode,
                    'schedule_detail' => $scheduleDetail,
                ]);
            }

            $status = $this->determineAttendanceStatus($record);

            return $this->successResponse([
                'status'          => $status,
                'record'          => $record,
                'current_time'    => now()->format('H:i:s'),
                'has_approved_wfh'=> $hasApprovedWfh,
                'working_mode'    => $workingMode,
                'schedule_detail' => $scheduleDetail,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get today\'s status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store WFH attendance data (legacy method for bulk updates)
     */
    public function store(Request $request, $employeeId)
    {
        try {
            $data = $request->validate([
                'payroll_period_id' => 'required|integer',
                'attendance_data' => 'required|array',
                'attendance_data.*.date' => 'required|date',
                'attendance_data.*.am_in' => 'nullable|date_format:H:i',
                'attendance_data.*.am_out' => 'nullable|date_format:H:i',
                'attendance_data.*.break_in' => 'nullable|date_format:H:i',
                'attendance_data.*.break_out' => 'nullable|date_format:H:i',
                'attendance_data.*.pm_in' => 'nullable|date_format:H:i',
                'attendance_data.*.pm_out' => 'nullable|date_format:H:i',
                'attendance_data.*.wfh_reason' => 'nullable|string|max:255',
                'attendance_data.*.wfh_location' => 'nullable|string|max:255',
                'attendance_data.*.remarks' => 'nullable|string|max:255'
            ]);

            $payrollPeriod = DB::table('payroll_periods')
                ->where('id', $data['payroll_period_id'])
                ->first();

            if (!$payrollPeriod) {
                return $this->errorResponse('Payroll period not found', 404);
            }

            if ($payrollPeriod->posted) {
                return $this->errorResponse('Cannot modify attendance for posted payroll period', 400);
            }

            $savedRecords = [];
            $errors = [];

            // Get employee's work_schedule_id once
            $employee = DB::table('employees')
                ->where('id', $employeeId)
                ->select('work_schedule_id')
                ->first();

            $workScheduleId = $employee->work_schedule_id ?? 0;

            foreach ($data['attendance_data'] as $attendance) {
                // Skip if no time entries and no WFH reason
                if (!$this->hasTimeEntries($attendance) && empty($attendance['wfh_reason'])) {
                    continue;
                }

                $validationErrors = $this->validateWFHAttendanceData($attendance, $payrollPeriod);
                if (!empty($validationErrors)) {
                    $errors = array_merge($errors, $validationErrors);
                    continue;
                }

                $workHours = $this->calculateWorkHours(
                    $attendance['am_in'] ?? null,
                    $attendance['pm_out'] ?? null,
                    $attendance['break_in'] ?? null,
                    $attendance['break_out'] ?? null
                );

                $timeData = [
                    'employee_id' => $employeeId,
                    'payroll_period_id' => $data['payroll_period_id'],
                    'work_schedule_id' => $workScheduleId, // Add this
                    'date' => $attendance['date'],
                    'am_in' => $attendance['am_in'] ?? null,
                    'am_out' => $attendance['am_out'] ?? null,
                    'break_in' => $attendance['break_in'] ?? null,
                    'break_out' => $attendance['break_out'] ?? null,
                    'pm_in' => $attendance['pm_in'] ?? null,
                    'pm_out' => $attendance['pm_out'] ?? null,
                    'work_hours' => $workHours,
                    'is_wfh' => 1,
                    'wfh_reason' => $attendance['wfh_reason'] ?? '',
                    'wfh_location' => $attendance['wfh_location'] ?? '',
                    'remarks' => $attendance['remarks'] ?? '',
                    'manual_entry_source' => 'wfh_portal',
                    'entry_timestamp' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                // Check if record already exists
                $existingRecord = DB::table('time_data')
                    ->where('employee_id', $employeeId)
                    ->where('payroll_period_id', $data['payroll_period_id'])
                    ->where('date', $attendance['date'])
                    ->first();

                if ($existingRecord) {
                    // Update existing record
                    DB::table('time_data')
                        ->where('id', $existingRecord->id)
                        ->update($timeData);

                    $savedRecords[] = $existingRecord->id;
                } else {
                    // Insert new record
                    $recordId = DB::table('time_data')->insertGetId($timeData);
                    $savedRecords[] = $recordId;
                }
            }

            if (!empty($errors)) {
                return $this->errorResponse('Validation errors occurred: ' . implode(', ', $errors), 422);
            }

            return $this->successResponse([
                'message' => 'WFH attendance data saved successfully',
                'saved_records' => $savedRecords
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to save WFH attendance: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get WFH attendance summary
     */
    public function getAttendanceSummary($employeeId, $payrollPeriodId)
    {
        try {
            $summary = DB::table('time_data')
                ->where('employee_id', $employeeId)
                ->where('payroll_period_id', $payrollPeriodId)
                ->where('is_wfh', 1)
                ->selectRaw('
                    COUNT(*) as total_days,
                    COUNT(CASE WHEN work_hours > 0 THEN 1 END) as present_days,
                    COUNT(CASE WHEN work_hours = 0 OR work_hours IS NULL THEN 1 END) as absent_days,
                    SUM(work_hours) as total_work_hours,
                    AVG(work_hours) as average_work_hours
                ')
                ->first();

            return $this->successResponse([
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load attendance summary: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete WFH attendance record
     */
    public function delete($employeeId, $timeDataId)
    {
        try {
            $deleted = DB::table('time_data')
                ->where('id', $timeDataId)
                ->where('employee_id', $employeeId)
                ->where('is_wfh', 1)
                ->delete();

            if ($deleted) {
                return $this->successResponse(['message' => 'WFH attendance record deleted successfully']);
            } else {
                return $this->errorResponse('Record not found or cannot be deleted', 404);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete record: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Private helper methods
     */
    private function hasTimeEntries($attendance)
    {
        return !empty($attendance['am_in']) ||
            !empty($attendance['am_out']) ||
            !empty($attendance['break_in']) ||
            !empty($attendance['break_out']) ||
            !empty($attendance['pm_in']) ||
            !empty($attendance['pm_out']);
    }

    private function calculateWorkHours($amIn, $pmOut, $breakIn = null, $breakOut = null)
    {
        if (!$amIn || !$pmOut) {
            return 0;
        }

        try {
            $amInTime = Carbon::createFromFormat('H:i', $amIn);
            $pmOutTime = Carbon::createFromFormat('H:i', $pmOut);
            $totalMinutes = $pmOutTime->diffInMinutes($amInTime);

            if ($breakIn && $breakOut) {
                $breakInTime = Carbon::createFromFormat('H:i', $breakIn);
                $breakOutTime = Carbon::createFromFormat('H:i', $breakOut);
                $breakMinutes = $breakOutTime->diffInMinutes($breakInTime);
                $totalMinutes -= $breakMinutes;
            }

            return max(0, $totalMinutes / 60);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function validateWFHAttendanceData($attendance, $payrollPeriod)
    {
        $errors = [];
        $attendanceDate = Carbon::parse($attendance['date']);
        $startDate = Carbon::parse($payrollPeriod->attendance_start_date);
        $endDate = Carbon::parse($payrollPeriod->attendance_end_date);

        if ($attendanceDate->lt($startDate) || $attendanceDate->gt($endDate)) {
            $errors[] = "Date {$attendance['date']} is outside the payroll period";
        }

        // Validate time sequence
        $times = [
            'am_in' => $attendance['am_in'] ?? null,
            'am_out' => $attendance['am_out'] ?? null,
            'break_in' => $attendance['break_in'] ?? null,
            'break_out' => $attendance['break_out'] ?? null,
            'pm_in' => $attendance['pm_in'] ?? null,
            'pm_out' => $attendance['pm_out'] ?? null
        ];

        $timeSequence = ['am_in', 'am_out', 'break_in', 'break_out', 'pm_in', 'pm_out'];
        $lastTime = null;

        foreach ($timeSequence as $timeField) {
            if ($times[$timeField]) {
                try {
                    $currentTime = Carbon::createFromFormat('H:i', $times[$timeField]);
                    if ($lastTime && $currentTime->lt($lastTime)) {
                        $errors[] = "Time sequence error on {$attendance['date']}: {$timeField} cannot be before previous time";
                    }
                    $lastTime = $currentTime;
                } catch (\Exception $e) {
                    $errors[] = "Invalid time format for {$timeField} on {$attendance['date']}";
                }
            }
        }

        return $errors;
    }

    private function getAttendanceStatus($record)
    {
        if (!$record->am_in && !$record->pm_out) {
            return 'empty';
        }

        if ($record->am_in && $record->pm_out) {
            return 'complete';
        }

        return 'partial';
    }


    private function getCurrentPayrollPeriodId()
    {
        $period = DB::table('payroll_periods')
            ->where('posted', 0)
            ->where('active', 1)
            ->orderBy('attendance_start_date', 'desc')
            ->first();

        return $period ? $period->id : 0;
    }

    private function calculateWorkHoursFromRecord($record, $pmOut = null)
    {
        if (!$record->am_in) {
            return 0;
        }

        $pmOutTime = $pmOut ?: $record->pm_out;
        if (!$pmOutTime) {
            return 0;
        }

        try {
            $amInTime = Carbon::createFromFormat('H:i:s', $record->am_in);
            $pmOutTime = Carbon::createFromFormat('H:i:s', $pmOutTime);
            $totalMinutes = $pmOutTime->diffInMinutes($amInTime);

            if ($record->break_in && $record->break_out) {
                $breakInTime = Carbon::createFromFormat('H:i:s', $record->break_in);
                $breakOutTime = Carbon::createFromFormat('H:i:s', $record->break_out);
                $breakMinutes = $breakOutTime->diffInMinutes($breakInTime);
                $totalMinutes -= $breakMinutes;
            }

            return max(0, $totalMinutes / 60);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function determineAttendanceStatus($record)
    {
        if (!$record->am_in) {
            return 'not_started';
        }

        if ($record->pm_out) {
            return 'completed';
        }

        if ($record->break_in && !$record->break_out) {
            return 'on_break';
        }

        if ($record->am_in && !$record->pm_out) {
            return 'working';
        }

        return 'partial';
    }

    /**
     * Resolve today's fix_schedules_details row for an employee.
     *
     * Uses employees.work_schedule_id → fix_schedules_details
     * day_id follows ISO-8601: 1=Monday … 7=Sunday (Carbon::dayOfWeekIso)
     *
     * @param  int|string  $employeeId
     * @param  string      $date  Y-m-d
     * @return object|null
     */
    private function getEmployeeScheduleForDate($employeeId, string $date): ?object
    {
        try {
            $employee = DB::table('employees')
                ->where('id', $employeeId)
                ->select('work_schedule_id')
                ->first();

            if (!$employee || !$employee->work_schedule_id) {
                return null;
            }

            // ISO day: 1 = Monday … 7 = Sunday
            $dayId = Carbon::parse($date)->dayOfWeekIso;

            return DB::table('fix_schedules_details')
                ->where('fix_schedule_id', $employee->work_schedule_id)
                ->where('day_id', $dayId)
                ->first();
        } catch (\Throwable $e) {
            \Log::warning('WFHAttendanceController::getEmployeeScheduleForDate failed', [
                'employee_id' => $employeeId,
                'date'        => $date,
                'error'       => $e->getMessage(),
            ]);
            return null;
        }
    }
}
