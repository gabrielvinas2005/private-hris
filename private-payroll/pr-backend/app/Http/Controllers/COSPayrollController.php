<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CosPayrollController extends Controller
{
    use ApiResponse;

    private const COS_NVAT_RATE = 0.03;
    private const COS_EWT_RATE = 0.02;
    private const COS_PREMIUM_RATE = 0.20;

    /**
     * List COS non-DTR tasks that are marked for payroll.
     *
     * This endpoint returns rows from non_dtr_task where for_payroll = 1.
     * Optionally it can be filtered by task period (period_from/period_to).
     *
     * Assumptions:
     * - Table name: non_dtr_task
     * - Relevant columns: id, employee_id, period_from, period_to, for_payroll, created_at, updated_at.
     */
    public function index(Request $request)
    {
        try {
            $query = DB::table('non_dtr_task')
                ->where('for_payroll', 1);

            // Optional date range filters based on task period
            if ($request->filled('date_from')) {
                $query->whereDate('period_to', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('period_from', '<=', $request->input('date_to'));
            }

            $entries = $query
                ->orderBy('period_from')
                ->orderBy('period_to')
                ->orderBy('id')
                ->get();

            return $this->successResponse(
                $entries,
                'COS payroll entries retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                'Failed to retrieve COS payroll entries: ' . $e->getMessage()
            );
        }
    }

    /**
     * List COS payroll periods that have Contract of Service employment type.
     *
     * COS payroll uses time_data_summary (DTR attendance) — process attendance before payroll.
     */
    public function periods()
    {
        try {
            // Base query for active payroll periods
            $periods = DB::table('payroll_periods as p')
                ->join('payroll_intervals as i', 'i.id', '=', 'p.payroll_interval_id')
                ->join('payroll_cutoffs as c', 'c.id', '=', 'p.payroll_cutoff_id')
                ->join('payroll_period_Etype as ppe', 'ppe.payrollperiod_id', '=', 'p.id')
                ->join('employment_types as et', 'et.id', '=', 'ppe.employmenttype_id')
                ->where('p.active', true)
                ->where(function ($q) {
                    $q->where('et.name', 'Contract of Service')
                        ->orWhere('et.name', 'LIKE', '%Contract of Service%');
                })
                ->select(
                    'p.*',
                    DB::raw("CONCAT(i.name,' (',DATENAME(MONTH,p.release_date),' ',DATEPART(YEAR,p.release_date),') ') as payroll")
                )
                ->orderBy('p.release_date', 'asc')
                ->distinct()
                ->get();

            // Attach employment_types and net_pay per payroll period
            // NOTE: I intentionally kept the original `p.posted` flag coming from `payroll_periods`.
            // The frontend's "Posted" status for COS periods is determined directly by that DB column.
            $periods = $periods->map(function ($payroll) {
                // Employment types
                $employmentTypes = DB::table('payroll_period_Etype as ppe')
                    ->join('employment_types as et', 'ppe.employmenttype_id', '=', 'et.id')
                    ->where('ppe.payrollperiod_id', $payroll->id)
                    ->select('et.id', 'et.name')
                    ->get();
                $payroll->employment_types = $employmentTypes;

                // Get total net pay for COS employees in this period from payroll_summaries
                $netPayTotal = DB::table('payroll_summaries as s')
                    ->join('employees as e', 's.employee_id', '=', 'e.id')
                    ->join('employment_types as et', 'e.employment_type_id', '=', 'et.id')
                    ->where('s.payroll_period_id', $payroll->id)
                    ->where(function ($q) {
                        $q->where('et.name', 'Contract of Service')
                            ->orWhere('et.name', 'LIKE', '%Contract of Service%');
                    })
                    ->sum('s.net_pay');

                $payroll->total_net_pay = $netPayTotal ?? 0;

                // template can display both payout dates consistently.
                $payroll->first_half = $payroll->payroll_start_date
                    ? (object) ['release_date' => $payroll->payroll_start_date]
                    : null;
                $payroll->second_half = $payroll->payroll_end_date
                    ? (object) ['release_date' => $payroll->payroll_end_date]
                    : null;

                return $payroll;
            });

            return $this->successResponse(
                $periods,
                'COS payroll periods retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                'Failed to retrieve COS payroll periods: ' . $e->getMessage()
            );
        }
    }


    public function employeesByPeriod($payrollPeriodId)
    {
        try {
            $period = DB::table('payroll_periods')
                ->where('id', (int) $payrollPeriodId)
                ->first();

            if (!$period) {
                return $this->notFoundResponse('Payroll period not found.');
            }

            $payload = $this->buildCosPayrollPeriodPayload($period, (int) $payrollPeriodId);

            return $this->successResponse(
                $payload,
                'COS payroll employees retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                'Failed to retrieve COS payroll employees: ' . $e->getMessage()
            );
        }
    }

    /**
     * Process COS payroll for a period: load employees/entries and compute contributions,
     * gross, deductions, and net pay. Does not save to payroll_summaries; use post() to finalize.
     */
    public function process($payrollPeriodId)
    {
        try {
            $payrollPeriodId = (int) $payrollPeriodId;
            $period = DB::table('payroll_periods')
                ->where('id', $payrollPeriodId)
                ->first();

            if (!$period) {
                return $this->notFoundResponse('Payroll period not found.');
            }

            $cosEmployees = $this->getCosEmployeesForPeriod($payrollPeriodId);
            $eligibleEmployees = $this->filterCosPayrollEligibleEmployees(
                $cosEmployees,
                $period,
                $payrollPeriodId
            );

            if ($eligibleEmployees->isEmpty()) {
                return $this->errorResponse(
                    'No COS payroll to process. Only employees with a valid contract (not on hold) and supervisor-approved work details are included.',
                    400
                );
            }

            $payload = $this->buildCosPayrollPeriodPayload($period, $payrollPeriodId);
            $payload['payroll_stats'] = [
                'eligible_count' => $eligibleEmployees->count(),
                'skipped_count' => max(0, $cosEmployees->count() - $eligibleEmployees->count()),
            ];

            return $this->successResponse(
                $payload,
                'COS payroll processed. Contributions, attendance deductions, and net pay computed.'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                'Failed to process COS payroll: ' . $e->getMessage()
            );
        }
    }

    /**
     * Mark a COS payroll period as posted.
     *
     * This:
     * 1. Generates payroll_summaries rows only for COS employees with supervisor-approved tasks
     * 2. Applies payroll item schedule rules (taxes, deductions) based on employment type
     * 3. Sets payroll_periods.posted = 1
     *
     * Employees without approved non-DTR tasks are skipped and will not receive payslips.
     */
    public function post($payrollPeriodId)
    {
        try {
            $payrollPeriodId = (int) $payrollPeriodId;
            $period = DB::table('payroll_periods')
                ->where('id', $payrollPeriodId)
                ->first();

            if (!$period) {
                return $this->notFoundResponse('Payroll period not found.');
            }

            $cosEmployees = $this->getCosEmployeesForPeriod($payrollPeriodId);
            if ($cosEmployees->isEmpty()) {
                return $this->errorResponse(
                    'No COS payroll to post. Please process attendance for this period first.',
                    400
                );
            }

            $eligibleEmployees = $this->filterCosPayrollEligibleEmployees(
                $cosEmployees,
                $period,
                $payrollPeriodId
            );

            if ($eligibleEmployees->isEmpty()) {
                return $this->errorResponse(
                    'No COS employees with valid contract and supervisor-approved work details to post for this period.',
                    400
                );
            }

            $context = $this->buildCosPayrollContext($period, $payrollPeriodId);
            $eligibleEmployeeIds = $eligibleEmployees->pluck('employee_id');
            $skippedCount = $cosEmployees->count() - $eligibleEmployees->count();

            DB::table('payroll_summaries')
                ->where('payroll_period_id', $payrollPeriodId)
                ->whereIn('employee_id', $eligibleEmployeeIds)
                ->delete();

            foreach ($eligibleEmployees as $employee) {
                $employeeId = (int) $employee->employee_id;
                $payroll = $this->computeCosEmployeePayroll($employee, $context);
                $this->saveCosPayrollSummary($payrollPeriodId, $employeeId, $payroll);
            }

            DB::table('payroll_periods')
                ->where('id', $payrollPeriodId)
                ->update(['posted' => 1]);

            $message = $skippedCount > 0
                ? "COS payroll posted for {$eligibleEmployees->count()} employee(s) with valid contract and approved work details. {$skippedCount} employee(s) were skipped."
                : 'COS payroll period posted and payslips generated successfully';

            return $this->successResponse(
                [
                    'employees_processed' => $eligibleEmployees->count(),
                    'employees_skipped' => $skippedCount,
                ],
                $message
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                'Failed to post COS payroll period: ' . $e->getMessage()
            );
        }
    }

    /**
     * Return COS employees for a period with computed EWT (for manual override dialog).
     */
    public function getTaxEmployees($payrollPeriodId)
    {
        try {
            $payrollPeriodId = (int) $payrollPeriodId;
            $period = DB::table('payroll_periods')
                ->where('id', $payrollPeriodId)
                ->first();

            if (!$period) {
                return $this->notFoundResponse('Payroll period not found.');
            }

            $cosEmployees = $this->getCosEmployeesForPeriod($payrollPeriodId);
            $eligibleEmployees = $this->filterCosPayrollEligibleEmployees(
                $cosEmployees,
                $period,
                $payrollPeriodId
            );
            $context = $this->buildCosPayrollContext($period, $payrollPeriodId);

            $result = $eligibleEmployees->map(function ($employee) use ($context) {
                $payroll = $this->computeCosEmployeePayroll($employee, $context);

                return [
                    'employee_id' => (int) $employee->employee_id,
                    'name' => $employee->employee_name,
                    'tax' => $payroll['ewt'],
                ];
            });

            return $this->successResponse($result, 'COS EWT employees retrieved.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve COS EWT employees: ' . $e->getMessage());
        }
    }

    /**
     * Save COS EWT overrides for a payroll period (stored in payroll_tax_adjustments.tax_amount).
     */
    public function adjustTax(Request $request, $payrollPeriodId)
    {
        try {
            $payrollPeriodId = (int) $payrollPeriodId;
            $data = $request->all();
            $employeeIds = $data['employee_id'] ?? [];

            for ($i = 0; $i < count($employeeIds); $i++) {
                $employeeId = (int) $employeeIds[$i];
                $newEwt = (float) ($data['tax_amount'][$i] ?? 0);

                DB::table('payroll_tax_adjustments')->updateOrInsert(
                    ['employee_id' => $employeeId, 'payroll_period_id' => $payrollPeriodId],
                    ['tax_amount' => $newEwt, 'created_at' => now(), 'updated_at' => now()]
                );

                $summary = DB::table('payroll_summaries')
                    ->where(['employee_id' => $employeeId, 'payroll_period_id' => $payrollPeriodId])
                    ->first();

                if ($summary) {
                    $oldEwt = (float) $summary->tax;
                    $adjustedTotalDeduction = (float) $summary->total_deduction - $oldEwt + $newEwt;
                    $premium = (float) ($summary->total_income ?? 0);
                    $netPay = max(
                        0,
                        (float) $summary->gross_amount + $premium - $adjustedTotalDeduction
                    );

                    DB::table('payroll_summaries')
                        ->where(['employee_id' => $employeeId, 'payroll_period_id' => $payrollPeriodId])
                        ->update([
                            'tax' => $newEwt,
                            'total_deduction' => $adjustedTotalDeduction,
                            'net_pay' => $netPay,
                            'updated_at' => now(),
                        ]);
                }
            }

            return $this->successResponse(
                ['payroll_period_id' => $payrollPeriodId],
                'COS EWT adjustments saved successfully.'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save COS EWT adjustments: ' . $e->getMessage());
        }
    }

    /**
     * Debug endpoint to check if a COS employee should see their payslip.
     *
     * This helps diagnose why an employee might not be seeing their payslip.
     */
    public function debug($employeeId)
    {
        try {
            // Check if employee exists
            $employee = DB::table('employees')->where('id', $employeeId)->first();
            if (!$employee) {
                return $this->errorResponse("Employee ID $employeeId not found");
            }

            // Check if employee has a user account
            $user = DB::table('users')->where('employee_no', $employee->employee_no)->first();

            // Check payroll_summaries for this employee
            $summaries = DB::table('payroll_summaries as s')
                ->join('payroll_periods as p', 's.payroll_period_id', '=', 'p.id')
                ->where('s.employee_id', $employeeId)
                ->select('s.*', 'p.posted', 'p.release_date')
                ->get();

            // Check what PayslipController@index would return for this user
            $payslips = null;
            if ($user) {
                $payslips = DB::table('payroll_periods as a')
                    ->join('payroll_intervals as b', 'b.id', '=', 'a.payroll_interval_id')
                    ->join('payroll_cutoffs as c', 'c.id', '=', 'a.payroll_cutoff_id')
                    ->join('payroll_summaries as s', 's.payroll_period_id', '=', 'a.id')
                    ->select(
                        'a.id',
                        'a.posted',
                        's.employee_id',
                        'b.name as payroll_interval',
                        'c.name as cut_off',
                        'a.payroll_start_date',
                        'a.payroll_end_date'
                    )
                    ->where(['s.employee_id' => $employeeId, 'a.posted' => true])
                    ->orderBy('a.payroll_start_date', 'asc')
                    ->get();
            }

            return $this->successResponse([
                'employee' => [
                    'id' => $employee->id,
                    'employee_no' => $employee->employee_no,
                    'has_user_account' => $user ? true : false,
                    'user_id' => $user ? $user->id : null,
                ],
                'payroll_summaries_count' => $summaries->count(),
                'payroll_summaries' => $summaries,
                'visible_payslips' => $payslips,
                'note' => $user ? 'Employee has user account' : 'WARNING: Employee has NO user account - cannot login to see payslips'
            ]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Debug failed: ' . $e->getMessage());
        }
    }

    /**
     * Mark a COS payroll period as unposted.
     *
     * This removes payroll_summaries for COS employees in this period
     * and sets payroll_periods.posted = 0.
     */
    public function unpost($payrollPeriodId)
    {
        try {
            $payrollPeriodId = (int) $payrollPeriodId;
            $period = DB::table('payroll_periods')
                ->where('id', $payrollPeriodId)
                ->first();

            if (!$period) {
                return $this->notFoundResponse('Payroll period not found.');
            }

            $cosEmployees = $this->getCosEmployeesForPeriod($payrollPeriodId);

            if ($cosEmployees->isNotEmpty()) {
                $employeeIds = $cosEmployees->pluck('employee_id');
                DB::table('payroll_summaries')
                    ->where('payroll_period_id', $payrollPeriodId)
                    ->whereIn('employee_id', $employeeIds)
                    ->delete();
                DB::table('payroll_deductions')
                    ->where('payroll_period_id', $payrollPeriodId)
                    ->whereIn('employee_id', $employeeIds)
                    ->whereIn('deduction_id', $this->getCosDeductionTypeIds())
                    ->delete();
            }

            DB::table('payroll_periods')
                ->where('id', $payrollPeriodId)
                ->update(['posted' => 0]);

            return $this->successResponse(null, 'COS payroll period unposted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                'Failed to unpost COS payroll period: ' . $e->getMessage()
            );
        }
    }

    /**
     * Return attachment metadata from WTIHRIS_PTTC_ATTACHMENTS.
     *
     * @param int $attachment_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function attachmentInfo($attachment_id)
    {
        $attachment = $this->findNonDtrAttachment((int) $attachment_id);

        if (!$attachment) {
            return $this->notFoundResponse('Attachment not found.');
        }

        return $this->successResponse([
            'file_name' => $attachment->file_name,
            'file_type' => $attachment->file_type ?? null,
            'file_size' => (int) ($attachment->file_size ?? 0),
            'description' => $attachment->description ?? null,
            'storage_source' => 'attachments_database',
            'attachments_database' => config('database.connections.attachments.database'),
            'has_file_content' => (int) ($attachment->file_size ?? 0) > 0,
        ], 'Attachment info retrieved.');
    }

    /**
     * Download/serve a non-DTR attachment from WTIHRIS_PTTC_ATTACHMENTS.file_content.
     *
     * @param int $attachment_id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadAttachment($attachment_id)
    {
        try {
            $attachment = $this->findNonDtrAttachment((int) $attachment_id, true);

            if (!$attachment) {
                return $this->notFoundResponse('Attachment not found.');
            }

            $contentType = $attachment->file_type ?? 'application/octet-stream';
            $disposition = 'inline; filename="' . addslashes($attachment->file_name) . '"';

            $payload = $this->resolveNonDtrAttachmentBinary((int) $attachment_id, $attachment);
            if ($payload !== null) {
                return response($payload['content'], 200, [
                    'Content-Type' => $payload['content_type'] ?? $contentType,
                    'Content-Disposition' => $disposition,
                ]);
            }

            Log::warning('COS attachment missing in attachments database', [
                'attachment_id' => $attachment_id,
                'file_name' => $attachment->file_name,
                'attachments_database' => config('database.connections.attachments.database'),
            ]);

            return $this->notFoundResponse(
                'Attachment file content not found in attachments database ('
                    . config('database.connections.attachments.database')
                    . '.dbo.non_dtr_attachments).'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                'Failed to download attachment: ' . $e->getMessage()
            );
        }
    }

    /**
     * COS employees with processed attendance for the payroll period.
     */
    private function getCosEmployeesForPeriod(int $payrollPeriodId)
    {
        $appKey = env('APP_KEY', '');

        return DB::table('employees as emp')
            ->join('time_data_summary as tds', function ($join) use ($payrollPeriodId) {
                $join->on('emp.id', '=', 'tds.employee_id')
                    ->where('tds.payroll_period_id', '=', $payrollPeriodId);
            })
            ->join('employment_types as et', 'emp.employment_type_id', '=', 'et.id')
            ->where('emp.active', true)
            ->where('emp.is_employee', true)
            ->tap(function ($query) {
                $this->applyEmployeeNotOnHoldFilter($query, 'emp.is_hold');
            })
            ->where(function ($q) {
                $q->where('et.name', 'Contract of Service')
                    ->orWhere('et.name', 'LIKE', '%Contract of Service%');
            })
            ->whereIn('emp.employment_type_id', function ($q) use ($payrollPeriodId) {
                $q->select('employmenttype_id')
                    ->from('payroll_period_Etype')
                    ->where('payrollperiod_id', $payrollPeriodId);
            })
            ->select(
                'emp.id as employee_id',
                'emp.salary',
                'emp.employment_type_id',
                'emp.employee_no',
                'emp.last_name',
                'emp.first_name',
                'emp.is_hold',
                DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN
                            CONCAT(emp.first_name,' ',emp.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](emp.first_name,'$appKey'))+' '+RTRIM([dbo].[ufn_DecryptString](emp.last_name,'$appKey'))
                        END as employee_name")
            )
            ->orderBy('emp.last_name')
            ->orderBy('emp.first_name')
            ->get()
            ->unique('employee_id')
            ->values();
    }

    /**
     * @return array{
     *   payroll_period_id: int,
     *   tk_days: int,
     *   tk_hours: int,
     *   is_half_payroll_period: bool,
     *   is_first_half_period: bool,
     *   is_second_half_period: bool,
     *   is_monthly_period: bool,
     *   first_half_period_id: ?int,
     *   second_half_period_id: ?int
     * }
     */
    private function buildCosPayrollContext(object $period, int $payrollPeriodId): array
    {
        return array_merge(
            [
                'payroll_period_id' => $payrollPeriodId,
                'tk_days' => 22,
                'tk_hours' => 8,
            ],
            $this->resolveCosPeriodHalfInfo($period, $payrollPeriodId)
        );
    }

    /**
     * Detect 1st/2nd half payroll periods (semi-monthly) for COS, aligned with regular payroll.
     *
     * @return array{
     *   is_half_payroll_period: bool,
     *   is_first_half_period: bool,
     *   is_second_half_period: bool,
     *   is_monthly_period: bool,
     *   first_half_period_id: ?int,
     *   second_half_period_id: ?int
     * }
     */
    private function resolveCosPeriodHalfInfo(object $period, int $payrollPeriodId): array
    {
        $cutoffCount = DB::table('payroll_cutoffs')
            ->where('payroll_interval_id', $period->payroll_interval_id)
            ->count();

        $cutoffName = DB::table('payroll_cutoffs')
            ->where('id', $period->payroll_cutoff_id)
            ->value('name');
        $cutoffNameNormalized = is_string($cutoffName) ? strtolower($cutoffName) : '';
        $cutoffId = (int) ($period->payroll_cutoff_id ?? 0);

        $dateBasis = $period->attendance_start_date ?: $period->release_date;
        $carryYear = $dateBasis ? (int) date('Y', strtotime($dateBasis)) : null;
        $carryMonth = $dateBasis ? (int) date('n', strtotime($dateBasis)) : null;

        $monthPeriodIds = $this->getCosPeriodIdsForAttendanceMonth(
            (int) $period->payroll_interval_id,
            $carryYear,
            $carryMonth
        );

        $isFirstHalfPeriod = false;
        $isSecondHalfPeriod = false;

        if ($cutoffCount >= 2 && $monthPeriodIds->count() >= 2) {
            if ((int) $monthPeriodIds[0] === $payrollPeriodId) {
                $isFirstHalfPeriod = true;
            } elseif ((int) $monthPeriodIds[1] === $payrollPeriodId) {
                $isSecondHalfPeriod = true;
            }
        }

        if ($cutoffCount >= 2 && !$isFirstHalfPeriod && !$isSecondHalfPeriod) {
            $isFirstHalfPeriod = str_contains($cutoffNameNormalized, '1st')
                || str_contains($cutoffNameNormalized, 'first')
                || $cutoffId === 1;
            $isSecondHalfPeriod = str_contains($cutoffNameNormalized, '2nd')
                || str_contains($cutoffNameNormalized, 'second')
                || $cutoffId === 3;
        }

        $isHalfPayrollPeriod = $isFirstHalfPeriod || $isSecondHalfPeriod;
        $isMonthlyPeriod = str_contains($cutoffNameNormalized, 'monthly') && !$isHalfPayrollPeriod;

        $firstHalfPeriodId = null;
        $secondHalfPeriodId = null;

        if ($isFirstHalfPeriod) {
            $firstHalfPeriodId = $payrollPeriodId;
            $secondHalfPeriodId = $this->resolveCosSiblingPeriodId(
                $period,
                $payrollPeriodId,
                $monthPeriodIds,
                false
            );
        } elseif ($isSecondHalfPeriod) {
            $secondHalfPeriodId = $payrollPeriodId;
            $firstHalfPeriodId = $this->resolveCosSiblingPeriodId(
                $period,
                $payrollPeriodId,
                $monthPeriodIds,
                true
            );
        } elseif ($monthPeriodIds->count() >= 2) {
            $firstHalfPeriodId = (int) $monthPeriodIds[0];
            $secondHalfPeriodId = (int) $monthPeriodIds[1];
        } elseif ($monthPeriodIds->count() === 1) {
            $firstHalfPeriodId = (int) $monthPeriodIds[0];
        }

        return [
            'is_half_payroll_period' => $isHalfPayrollPeriod,
            'is_first_half_period' => $isFirstHalfPeriod,
            'is_second_half_period' => $isSecondHalfPeriod,
            'is_monthly_period' => $isMonthlyPeriod,
            'first_half_period_id' => $firstHalfPeriodId,
            'second_half_period_id' => $secondHalfPeriodId,
        ];
    }

    /**
     * COS payroll periods whose attendance starts in the same calendar month.
     */
    private function getCosPeriodIdsForAttendanceMonth(
        int $payrollIntervalId,
        ?int $year,
        ?int $month
    ) {
        if (!$year || !$month) {
            return collect();
        }

        return DB::table('payroll_periods as p')
            ->join('payroll_period_Etype as ppe', 'ppe.payrollperiod_id', '=', 'p.id')
            ->join('employment_types as et', 'et.id', '=', 'ppe.employmenttype_id')
            ->where('p.payroll_interval_id', $payrollIntervalId)
            ->where('p.active', true)
            ->whereYear('p.attendance_start_date', $year)
            ->whereMonth('p.attendance_start_date', $month)
            ->where(function ($q) {
                $q->where('et.name', 'Contract of Service')
                    ->orWhere('et.name', 'LIKE', '%Contract of Service%');
            })
            ->select('p.id', 'p.attendance_start_date')
            ->orderBy('p.attendance_start_date')
            ->orderBy('p.id')
            ->get()
            ->unique('id')
            ->pluck('id')
            ->values();
    }

    /**
     * @deprecated Use getCosPeriodIdsForAttendanceMonth()
     */
    private function getCosPeriodIdsForReleaseMonth(
        int $payrollIntervalId,
        ?int $year,
        ?int $month
    ) {
        return $this->getCosPeriodIdsForAttendanceMonth($payrollIntervalId, $year, $month);
    }

    /**
     * Resolve the other semi-monthly COS period (same release month first, then cutoff/attendance).
     */
    private function resolveCosSiblingPeriodId(
        object $period,
        int $currentPeriodId,
        $monthPeriodIds,
        bool $findFirstHalf
    ): ?int {
        $otherInMonth = $monthPeriodIds->first(function ($id) use ($currentPeriodId) {
            return (int) $id !== $currentPeriodId;
        });

        if ($otherInMonth !== null) {
            return (int) $otherInMonth;
        }

        return $this->findCosSiblingHalfPeriodId($period, $currentPeriodId, $findFirstHalf);
    }

    /**
     * Locate the paired COS half period within the same attendance month.
     */
    private function findCosSiblingHalfPeriodId(
        object $period,
        int $currentPeriodId,
        bool $findFirstHalf
    ): ?int {
        $attendanceBasis = $period->attendance_start_date ?: $period->release_date;
        if (!$attendanceBasis) {
            return null;
        }

        $attendanceYear = (int) date('Y', strtotime($attendanceBasis));
        $attendanceMonth = (int) date('n', strtotime($attendanceBasis));

        $query = DB::table('payroll_periods as p')
            ->join('payroll_period_Etype as ppe', 'ppe.payrollperiod_id', '=', 'p.id')
            ->join('employment_types as et', 'et.id', '=', 'ppe.employmenttype_id')
            ->join('payroll_cutoffs as c', 'c.id', '=', 'p.payroll_cutoff_id')
            ->where('p.payroll_interval_id', $period->payroll_interval_id)
            ->where('p.active', true)
            ->where('p.id', '!=', $currentPeriodId)
            ->whereYear('p.attendance_start_date', $attendanceYear)
            ->whereMonth('p.attendance_start_date', $attendanceMonth)
            ->where(function ($q) {
                $q->where('et.name', 'Contract of Service')
                    ->orWhere('et.name', 'LIKE', '%Contract of Service%');
            });

        if ($findFirstHalf) {
            $query->where(function ($q) {
                $q->whereRaw('LOWER(c.name) LIKE ?', ['%1st%'])
                    ->orWhereRaw('LOWER(c.name) LIKE ?', ['%first%']);
            })
                ->where('p.attendance_end_date', '<=', $period->attendance_start_date)
                ->orderByDesc('p.attendance_end_date');
        } else {
            $query->where(function ($q) {
                $q->whereRaw('LOWER(c.name) LIKE ?', ['%2nd%'])
                    ->orWhereRaw('LOWER(c.name) LIKE ?', ['%second%']);
            })
                ->where('p.attendance_start_date', '>=', $period->attendance_end_date)
                ->orderBy('p.attendance_start_date');
        }

        $id = $query->value('p.id');

        return $id ? (int) $id : null;
    }

    /**
     * Resolve attendance amounts from time_data_summary (leave-with-pay offsets applied to absent).
     *
     * @return array{
     *   late: float, ut: float, absent: float, lwop: float,
     *   gross: float, balance_after_attendance: float,
     *   premium: float, nvat: float, ewt: float,
     *   total_deduction: float, net_pay: float
     * }
     */
    private function computeCosEmployeePayroll(object $employee, array $context): array
    {
        $employeeId = (int) $employee->employee_id;
        $payrollPeriodId = (int) $context['payroll_period_id'];
        $monthlySalary = round((float) ($employee->salary ?? 0), 2);
        $isHalfPeriod = !empty($context['is_half_payroll_period']);
        $gross = $isHalfPeriod ? round($monthlySalary / 2, 2) : $monthlySalary;

        $attendance = $this->resolveCosAttendanceAmounts(
            $employeeId,
            $payrollPeriodId,
            $gross,
            (int) $employee->employment_type_id,
            $monthlySalary
        );

        $balance = max(0.0, round(
            $gross - $attendance['late'] - $attendance['ut'] - $attendance['effective_absent'] - $attendance['lwop'],
            2
        ));

        // Premium is added to pay; NVAT/EWT are assessed on balance + premium.
        $premium = round($balance * self::COS_PREMIUM_RATE, 2);
        $balanceWithPremium = round($balance + $premium, 2);
        $nvat = round($balanceWithPremium * self::COS_NVAT_RATE, 2);

        $ewtOverride = DB::table('payroll_tax_adjustments')
            ->where('employee_id', $employeeId)
            ->where('payroll_period_id', $payrollPeriodId)
            ->value('tax_amount');
        $ewt = $ewtOverride !== null
            ? round((float) $ewtOverride, 2)
            : round($balanceWithPremium * self::COS_EWT_RATE, 2);

        $attendanceTotal = $attendance['late'] + $attendance['ut'] + $attendance['effective_absent'] + $attendance['lwop'];
        $totalDeduction = round($attendanceTotal + $nvat + $ewt, 2);
        $netPay = max(0.0, round($gross - $attendanceTotal + $premium - $nvat - $ewt, 2));

        return [
            'monthly_salary' => $monthlySalary,
            'is_hold' => $this->isEmployeeOnHold($employee),
            'gross_amount' => $gross,
            'late_amount' => $attendance['late'],
            'ut_amount' => $attendance['ut'],
            'absent_amount' => $attendance['effective_absent'],
            'lwop_amount' => $attendance['lwop'],
            'attendance_total' => round($attendanceTotal, 2),
            'balance_after_attendance' => $balance,
            'balance_after_premium' => $balanceWithPremium,
            'premium' => $premium,
            'nvat' => $nvat,
            'ewt' => $ewt,
            'total_deduction' => $totalDeduction,
            'net_pay' => $netPay,
        ];
    }

    /**
     * @return array{
     *   late: float,
     *   ut: float,
     *   raw_absent: float,
     *   effective_absent: float,
     *   lwop: float
     * }
     */
    private function resolveCosAttendanceAmounts(
        int $employeeId,
        int $payrollPeriodId,
        float $gross,
        int $employmentTypeId,
        float $monthlySalary
    ): array {
        $tkDays = 22;
        $tkHours = 8;
        $timeKeeping = DB::table('time_keeping_setups')
            ->where('employment_type_id', $employmentTypeId)
            ->first();
        if ($timeKeeping) {
            $tkDays = (int) ($timeKeeping->work_days ?: 22);
            $tkHours = (int) ($timeKeeping->work_hours ?: 8);
        }
        $empty = [
            'late' => 0.0,
            'ut' => 0.0,
            'raw_absent' => 0.0,
            'effective_absent' => 0.0,
            'lwop' => 0.0,
        ];

        $timeSummary = DB::table('time_data_summary')
            ->where(['payroll_period_id' => $payrollPeriodId, 'employee_id' => $employeeId])
            ->first();

        if (!$timeSummary) {
            return $empty;
        }

        $maxReasonable = max($gross * 2, 1000000);
        $late = $this->sanitizeAmount($timeSummary->Late_Amount ?? 0, $maxReasonable);
        $ut = $this->sanitizeAmount($timeSummary->Undertime_Amount ?? 0, $maxReasonable);
        $rawAbsent = $this->sanitizeAmount($timeSummary->Absent_Amount ?? 0, $maxReasonable);
        $lwop = $this->sanitizeAmount($timeSummary->LWOP_Amount ?? 0, $maxReasonable);

        $totalTardiness = $late + $ut + $rawAbsent;
        if ($gross > 0 && $totalTardiness > $gross) {
            $ratio = $gross / $totalTardiness;
            $late = round($late * $ratio, 2);
            $ut = round($ut * $ratio, 2);
            $rawAbsent = round($rawAbsent * $ratio, 2);
        }

        $workDays = $tkDays > 0 ? $tkDays : 22;
        $dailyRate = round($monthlySalary / $workDays, 2);
        $leaveWithPayDays = (float) DB::table('time_data')
            ->where('employee_id', $employeeId)
            ->where('payroll_period_id', $payrollPeriodId)
            ->sum('leave');
        $leaveWithPayAmount = round($leaveWithPayDays * $dailyRate, 2);

        $leaveRemaining = $leaveWithPayAmount;
        $lateOffset = min($leaveRemaining, $late);
        $late = max(0.0, $late - $lateOffset);
        $leaveRemaining -= $lateOffset;

        $utOffset = min($leaveRemaining, $ut);
        $ut = max(0.0, $ut - $utOffset);
        $leaveRemaining -= $utOffset;

        $absentOffset = min($leaveRemaining, $rawAbsent);
        $effectiveAbsent = max(0.0, $rawAbsent - $absentOffset);

        return [
            'late' => $late,
            'ut' => $ut,
            'raw_absent' => $rawAbsent,
            'effective_absent' => $effectiveAbsent,
            'lwop' => $lwop,
        ];
    }

    private function sanitizeAmount($value, float $maxReasonable): float
    {
        $amount = is_numeric($value) ? (float) $value : 0.0;
        if ($amount < 0 || $amount > $maxReasonable || !is_finite($amount)) {
            return 0.0;
        }

        return $amount;
    }

    /**
     * Daily DTR rows for COS employees in the period, with optional computed payroll summary fields.
     *
     * @param  array<int, array>  $computedByEmployee
     */
    private function fetchCosAttendanceDetailRows(int $payrollPeriodId, array $computedByEmployee = [])
    {
        $period = DB::table('payroll_periods')->where('id', $payrollPeriodId)->first();
        if (!$period) {
            return collect();
        }

        $appKey = env('APP_KEY', '');
        $startDate = $period->attendance_start_date;
        $endDate = $period->attendance_end_date;

        $rows = DB::table('time_data as td')
            ->join('employees as emp', 'td.employee_id', '=', 'emp.id')
            ->join('employment_types as et', 'emp.employment_type_id', '=', 'et.id')
            ->leftJoin('payroll_summaries as ps', function ($join) use ($payrollPeriodId) {
                $join->on('ps.employee_id', '=', 'emp.id')
                    ->where('ps.payroll_period_id', '=', $payrollPeriodId);
            })
            ->where('td.payroll_period_id', $payrollPeriodId)
            ->whereBetween('td.date', [$startDate, $endDate])
            ->tap(function ($query) {
                $this->applyEmployeeNotOnHoldFilter($query, 'emp.is_hold');
            })
            ->where(function ($q) {
                $q->where('et.name', 'Contract of Service')
                    ->orWhere('et.name', 'LIKE', '%Contract of Service%');
            })
            ->whereIn('emp.employment_type_id', function ($q) use ($payrollPeriodId) {
                $q->select('employmenttype_id')
                    ->from('payroll_period_Etype')
                    ->where('payrollperiod_id', $payrollPeriodId);
            })
            ->select(
                'td.id as entry_id',
                'td.date as work_date',
                'td.am_in as time_in',
                'td.pm_out as time_out',
                'td.work_hours as hours_worked',
                'td.late',
                'td.undertime',
                'td.absent',
                'td.lwop',
                'td.remarks',
                DB::raw('NULL as accomplishments'),
                DB::raw('NULL as output_description'),
                DB::raw('NULL as location'),
                DB::raw('1 as for_payroll'),
                'emp.id as employee_id',
                'emp.employee_no',
                'emp.salary',
                DB::raw((int) $payrollPeriodId . ' as payroll_period_id'),
                DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN
                            CONCAT(emp.first_name,' ',emp.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](emp.first_name,'$appKey'))+' '+RTRIM([dbo].[ufn_DecryptString](emp.last_name,'$appKey'))
                        END as employee_name"),
                DB::raw('NULL as attachment_id'),
                DB::raw('NULL as file_name'),
                DB::raw('NULL as file_path'),
                DB::raw('NULL as file_type'),
                DB::raw('NULL as file_size'),
                DB::raw('NULL as attachment_description'),
                'ps.gross_amount as summary_gross',
                'ps.total_deduction as summary_total_deduction',
                'ps.net_pay as summary_net_pay',
                'ps.late_amount as summary_late',
                'ps.ut_amount as summary_ut',
                'ps.absent_amount as summary_absent',
                'ps.lwop_amount as summary_lwop',
                'ps.tax as summary_ewt'
            )
            ->orderBy('emp.last_name')
            ->orderBy('emp.first_name')
            ->orderBy('td.date')
            ->orderBy('td.id')
            ->get();

        $employeeIdsWithRows = $rows->pluck('employee_id')->unique();
        $cosEmployees = $this->getCosEmployeesForPeriod($payrollPeriodId);

        foreach ($cosEmployees as $emp) {
            if ($employeeIdsWithRows->contains($emp->employee_id)) {
                continue;
            }
            $rows->push((object) [
                'entry_id' => 0,
                'work_date' => $period->attendance_start_date,
                'time_in' => null,
                'time_out' => null,
                'hours_worked' => null,
                'late' => null,
                'undertime' => null,
                'absent' => null,
                'lwop' => null,
                'remarks' => 'No daily DTR rows',
                'employee_id' => $emp->employee_id,
                'employee_no' => $emp->employee_no,
                'salary' => $emp->salary,
                'employee_name' => $emp->employee_name,
                'payroll_period_id' => $payrollPeriodId,
                'summary_gross' => null,
                'summary_total_deduction' => null,
                'summary_net_pay' => null,
                'summary_late' => null,
                'summary_ut' => null,
                'summary_absent' => null,
                'summary_lwop' => null,
                'summary_ewt' => null,
            ]);
        }

        $computedByEmployee = $this->normalizeComputedByEmployee($computedByEmployee);

        foreach ($rows as $row) {
            $employeeId = (int) $row->employee_id;
            $sum = $computedByEmployee[$employeeId] ?? null;
            if ($sum !== null) {
                $row->summary_gross = $sum['gross_amount'];
                $row->summary_total_deduction = $sum['total_deduction'];
                $row->summary_net_pay = $sum['net_pay'];
                $row->summary_late = $sum['late_amount'];
                $row->summary_ut = $sum['ut_amount'];
                $row->summary_absent = $sum['absent_amount'];
                $row->summary_lwop = $sum['lwop_amount'];
                $row->summary_premium = $sum['premium'];
                $row->summary_nvat = $sum['nvat'];
                $row->summary_ewt = $sum['ewt'];
                $row->summary_balance = $sum['balance_after_attendance'];
                $row->summary_attendance_total = $sum['attendance_total'];
            } elseif (!empty($row->summary_net_pay)) {
                $row->summary_premium = (float) ($row->summary_premium ?? 0);
                $row->summary_nvat = $this->loadCosDeductionAmount(
                    $payrollPeriodId,
                    $employeeId,
                    'COS NVAT'
                );
                if ($row->summary_premium <= 0) {
                    $row->summary_premium = (float) (DB::table('payroll_summaries')
                        ->where(['payroll_period_id' => $payrollPeriodId, 'employee_id' => $employeeId])
                        ->value('total_income') ?? 0);
                }
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array>  $computedByEmployee
     */
    private function normalizeComputedByEmployee(array $computedByEmployee): array
    {
        $normalized = [];
        foreach ($computedByEmployee as $employeeId => $payroll) {
            $normalized[(int) $employeeId] = $payroll;
        }

        return $normalized;
    }

    /**
     * Build full COS payroll payload for a period, including paired 1st/2nd half data.
     */
    private function buildCosPayrollPeriodPayload(object $period, int $payrollPeriodId): array
    {
        $cosEmployees = $this->getCosEmployeesForPeriod($payrollPeriodId);
        $context = $this->buildCosPayrollContext($period, $payrollPeriodId);
        $eligibleEmployees = $this->filterCosPayrollEligibleEmployees(
            $cosEmployees,
            $period,
            $payrollPeriodId
        );
        $computedByEmployee = [];
        foreach ($cosEmployees as $employee) {
            $computedByEmployee[(int) $employee->employee_id] = $this->computeCosEmployeePayroll($employee, $context);
        }

        $halfInfo = $this->resolveCosPeriodHalfInfo($period, $payrollPeriodId);
        $firstHalfPeriodId = $halfInfo['first_half_period_id'] ?? null;
        $secondHalfPeriodId = $halfInfo['second_half_period_id'] ?? null;

        $firstHalfPayrolls = $firstHalfPeriodId
            ? $this->resolveCosPayrollMapForPeriod($firstHalfPeriodId, $payrollPeriodId, $computedByEmployee)
            : [];
        $secondHalfPayrolls = $secondHalfPeriodId
            ? $this->resolveCosPayrollMapForPeriod($secondHalfPeriodId, $payrollPeriodId, $computedByEmployee)
            : [];

        $firstHalfPeriod = $firstHalfPeriodId
            ? DB::table('payroll_periods')->where('id', $firstHalfPeriodId)->first()
            : null;
        $secondHalfPeriod = $secondHalfPeriodId
            ? DB::table('payroll_periods')->where('id', $secondHalfPeriodId)->first()
            : null;

        $rows = collect();
        if ($firstHalfPeriodId) {
            $rows = $rows->merge(
                $this->fetchCosAttendanceDetailRows($firstHalfPeriodId, $firstHalfPayrolls)
            );
        }
        if ($secondHalfPeriodId && $secondHalfPeriodId !== $firstHalfPeriodId) {
            $rows = $rows->merge(
                $this->fetchCosAttendanceDetailRows($secondHalfPeriodId, $secondHalfPayrolls)
            );
        }
        if ($rows->isEmpty()) {
            $rows = $this->fetchCosAttendanceDetailRows($payrollPeriodId, $computedByEmployee);
        }

        return $this->buildCosPayrollPayload(
            $period,
            $rows,
            $computedByEmployee,
            $halfInfo,
            $firstHalfPayrolls,
            $secondHalfPayrolls,
            $firstHalfPeriod,
            $secondHalfPeriod,
            $eligibleEmployees
        );
    }

    /**
     * @param  array<int, array>  $computedByEmployee
     * @param  array<int, array>  $firstHalfPayrolls
     * @param  array<int, array>  $secondHalfPayrolls
     */
    private function buildCosPayrollPayload(
        object $period,
        $rows,
        array $computedByEmployee,
        ?array $halfInfo = null,
        array $firstHalfPayrolls = [],
        array $secondHalfPayrolls = [],
        ?object $firstHalfPeriod = null,
        ?object $secondHalfPeriod = null,
        $eligibleEmployees = null
    ): array {
        $computedByEmployee = $this->normalizeComputedByEmployee($computedByEmployee);
        $halfInfo = $halfInfo ?? $this->resolveCosPeriodHalfInfo($period, (int) $period->id);

        if (empty($firstHalfPayrolls) && !empty($halfInfo['first_half_period_id'])) {
            $firstHalfPayrolls = $this->resolveCosPayrollMapForPeriod(
                (int) $halfInfo['first_half_period_id'],
                (int) $period->id,
                $computedByEmployee
            );
        }
        if (empty($secondHalfPayrolls) && !empty($halfInfo['second_half_period_id'])) {
            $secondHalfPayrolls = $this->resolveCosPayrollMapForPeriod(
                (int) $halfInfo['second_half_period_id'],
                (int) $period->id,
                $computedByEmployee
            );
        }

        $employeeIds = array_unique(array_merge(
            array_keys($computedByEmployee),
            array_keys($firstHalfPayrolls),
            array_keys($secondHalfPayrolls)
        ));

        $employeePayrolls = [];
        foreach ($employeeIds as $employeeId) {
            $employeeId = (int) $employeeId;
            $first = $firstHalfPayrolls[$employeeId] ?? null;
            $second = $secondHalfPayrolls[$employeeId] ?? null;
            $current = $computedByEmployee[$employeeId] ?? null;
            $currentPeriodId = (int) $period->id;
            $firstHalfPeriodId = (int) ($halfInfo['first_half_period_id'] ?? 0);
            $secondHalfPeriodId = (int) ($halfInfo['second_half_period_id'] ?? 0);

            if ($current !== null) {
                if ($firstHalfPeriodId === $currentPeriodId) {
                    $first = $current;
                }
                if ($secondHalfPeriodId === $currentPeriodId) {
                    $second = $current;
                }
            }

            $base = $current ?? $first ?? $second ?? [];

            $firstHalfNet = (float) ($first['net_pay'] ?? 0);
            $secondHalfNet = (float) ($second['net_pay'] ?? 0);

            $employeePayrolls[] = array_merge(
                ['employee_id' => $employeeId],
                $base,
                [
                    'first_half' => $this->attachCosHalfPeriodMeta($first, $firstHalfPeriod, $employeeId),
                    'second_half' => $this->attachCosHalfPeriodMeta($second, $secondHalfPeriod, $employeeId),
                    'first_half_net_pay' => round($firstHalfNet, 2),
                    'second_half_net_pay' => round($secondHalfNet, 2),
                    'month_net_pay' => round($firstHalfNet + $secondHalfNet, 2),
                ]
            );
        }

        $formattedRows = $rows->map(function ($row) {
            return [
                'entry_id' => $row->entry_id ?? null,
                'work_date' => $row->work_date ?? null,
                'time_in' => $row->time_in ?? null,
                'time_out' => $row->time_out ?? null,
                'hours_worked' => $row->hours_worked ?? null,
                'late' => $row->late ?? null,
                'undertime' => $row->undertime ?? null,
                'absent' => $row->absent ?? null,
                'lwop' => $row->lwop ?? null,
                'remarks' => $row->remarks ?? null,
                'employee_id' => (int) ($row->employee_id ?? 0),
                'employee_no' => $row->employee_no ?? null,
                'employee_name' => $row->employee_name ?? null,
                'payroll_period_id' => isset($row->payroll_period_id)
                    ? (int) $row->payroll_period_id
                    : null,
                'salary' => $row->salary ?? null,
                'summary_gross' => $row->summary_gross ?? null,
                'summary_total_deduction' => $row->summary_total_deduction ?? null,
                'summary_net_pay' => $row->summary_net_pay ?? null,
                'summary_late' => $row->summary_late ?? null,
                'summary_ut' => $row->summary_ut ?? null,
                'summary_absent' => $row->summary_absent ?? null,
                'summary_lwop' => $row->summary_lwop ?? null,
                'summary_premium' => $row->summary_premium ?? null,
                'summary_nvat' => $row->summary_nvat ?? null,
                'summary_ewt' => $row->summary_ewt ?? null,
                'summary_balance' => $row->summary_balance ?? null,
                'summary_attendance_total' => $row->summary_attendance_total ?? null,
            ];
        })->values()->all();

        return [
            'period' => $period,
            'half_info' => array_merge($halfInfo, [
                'current_payroll_period_id' => (int) $period->id,
                'attendance_start_date' => $period->attendance_start_date ?? null,
                'attendance_end_date' => $period->attendance_end_date ?? null,
                'first_half_attendance_start' => $firstHalfPeriod->attendance_start_date ?? null,
                'first_half_attendance_end' => $firstHalfPeriod->attendance_end_date ?? null,
                'second_half_attendance_start' => $secondHalfPeriod->attendance_start_date ?? null,
                'second_half_attendance_end' => $secondHalfPeriod->attendance_end_date ?? null,
            ]),
            'rows' => $formattedRows,
            'employee_payrolls' => $employeePayrolls,
            'payroll_stats' => [
                'eligible_count' => ($eligibleEmployees ?? collect())->count(),
                'skipped_count' => max(0, count($employeeIds) - ($eligibleEmployees ?? collect())->count()),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $payroll
     * @return array<string, mixed>|null
     */
    private function attachCosHalfPeriodMeta(?array $payroll, ?object $period, ?int $employeeId = null): ?array
    {
        if ($payroll === null) {
            return null;
        }

        $meta = [
            'payroll_period_id' => $period ? (int) $period->id : null,
            'attendance_start_date' => $period->attendance_start_date ?? null,
            'attendance_end_date' => $period->attendance_end_date ?? null,
        ];

        if ($employeeId !== null && $period) {
            $approval = $this->resolveCosTaskApproval($employeeId, (int) $period->id, $period);
            $meta['task_approval'] = $approval;
            $meta['is_payroll_eligible'] = !empty($approval['is_approved']);
        }

        return array_merge($payroll, $meta);
    }

    /**
     * Division-head approved COS tasks for an employee in a payroll period.
     *
     * @return array{
     *   task_id: ?int,
     *   status: string,
     *   is_approved: bool,
     *   tasks: array<int, string>,
     *   task_1: ?string,
     *   task_2: ?string,
     *   task_3: ?string,
     *   period_from: ?string,
     *   period_to: ?string,
     *   approved_by_name: ?string,
     *   approved_at: ?string,
     *   remarks: ?string,
     *   attachments: array<int, array{
     *     id: int,
     *     file_name: string,
     *     file_type: ?string,
     *     file_size: int,
     *     description: ?string
     *   }>
     * }
     */
    private function resolveCosTaskApproval(int $employeeId, int $payrollPeriodId, ?object $period): array
    {
        $empty = [
            'task_id' => null,
            'status' => 'none',
            'is_approved' => false,
            'tasks' => [],
            'task_1' => null,
            'task_2' => null,
            'task_3' => null,
            'period_from' => null,
            'period_to' => null,
            'approved_by_name' => null,
            'approved_at' => null,
            'remarks' => null,
            'attachments' => [],
        ];

        $baseQuery = DB::table('non_dtr_task')
            ->where('employee_id', $employeeId)
            ->whereNull('deleted_at');

        $task = (clone $baseQuery)
            ->where('payroll_period_id', $payrollPeriodId)
            ->orderByDesc('updated_at')
            ->first();

        if (!$task && $period) {
            $startDate = $period->attendance_start_date ?? null;
            $endDate = $period->attendance_end_date ?? null;

            if ($startDate && $endDate) {
                // Legacy tasks without payroll_period_id: dates must fall inside this half only.
                $task = (clone $baseQuery)
                    ->where(function ($q) {
                        $q->whereNull('payroll_period_id')
                            ->orWhere('payroll_period_id', 0);
                    })
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereDate('period_from', '>=', $startDate)
                            ->whereDate('period_to', '<=', $endDate);
                    })
                    ->orderByDesc('updated_at')
                    ->first();
            }
        }

        if (!$task) {
            return $empty;
        }

        $taskPeriodId = (int) ($task->payroll_period_id ?? 0);
        if ($taskPeriodId > 0 && $taskPeriodId !== $payrollPeriodId) {
            return $empty;
        }

        $tasks = array_values(array_filter([
            trim((string) ($task->task_1 ?? '')),
            trim((string) ($task->task_2 ?? '')),
            trim((string) ($task->task_3 ?? '')),
        ], fn($value) => $value !== ''));

        $status = strtolower(trim((string) ($task->status ?? 'pending')));
        $isApproved = $status === 'approved';

        return [
            'task_id' => (int) $task->id,
            'payroll_period_id' => $taskPeriodId > 0 ? $taskPeriodId : $payrollPeriodId,
            'status' => $status,
            'is_approved' => $isApproved,
            'tasks' => $tasks,
            'task_1' => $task->task_1 ?? null,
            'task_2' => $task->task_2 ?? null,
            'task_3' => $task->task_3 ?? null,
            'period_from' => $task->period_from ?? null,
            'period_to' => $task->period_to ?? null,
            'approved_by_name' => $this->resolveCosEmployeeDisplayName((int) ($task->approved_by ?? 0)),
            'approved_at' => $task->approved_at ?? null,
            'remarks' => $task->remarks ?? null,
            'attachments' => $this->fetchCosTaskAttachments((int) $task->id),
        ];
    }

    /**
     * @return array<int, array{
     *   id: int,
     *   file_name: string,
     *   file_type: ?string,
     *   file_size: int,
     *   description: ?string
     * }>
     */
    private function fetchCosTaskAttachments(int $taskId): array
    {
        return $this->nonDtrAttachmentsQuery()
            ->where('non_dtr_task_id', $taskId)
            ->select([
                'id',
                'file_name',
                'file_type',
                'file_size',
                'description',
            ])
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'file_name' => (string) ($row->file_name ?? ''),
                    'file_type' => $row->file_type ?? null,
                    'file_size' => (int) ($row->file_size ?? 0),
                    'description' => $row->description ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveCosEmployeeDisplayName(int $employeeId): ?string
    {
        if ($employeeId <= 0) {
            return null;
        }

        $appKey = env('APP_KEY', '');
        $employee = DB::table('employees')
            ->where('id', $employeeId)
            ->select(
                'first_name',
                'last_name',
                'is_encrypted',
                DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN
                            CONCAT(first_name,' ',last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](first_name,'$appKey'))+' '+RTRIM([dbo].[ufn_DecryptString](last_name,'$appKey'))
                        END as employee_name")
            )
            ->first();

        if (!$employee) {
            return null;
        }

        return trim((string) ($employee->employee_name ?? '')) ?: null;
    }

    /**
     * SQL Server bit/boolean: employee is on hold (e.g. no valid contract).
     */
    private function isEmployeeOnHold(object $employee): bool
    {
        $value = $employee->is_hold ?? false;

        return $value === true
            || $value === 1
            || $value === '1'
            || strtolower((string) $value) === 'true';
    }

    /**
     * Exclude employees flagged on hold in the employees table.
     */
    private function applyEmployeeNotOnHoldFilter($query, string $column = 'emp.is_hold')
    {
        return $query->where(function ($q) use ($column) {
            $q->where($column, false)
                ->orWhere($column, 0)
                ->orWhere($column, 'false')
                ->orWhereNull($column);
        });
    }

    /**
     * COS employees whose non-DTR tasks are approved and who are not on hold.
     */
    private function filterCosPayrollEligibleEmployees($cosEmployees, object $period, int $payrollPeriodId)
    {
        return $cosEmployees
            ->filter(function ($employee) use ($period, $payrollPeriodId) {
                if ($this->isEmployeeOnHold($employee)) {
                    return false;
                }

                $approval = $this->resolveCosTaskApproval(
                    (int) $employee->employee_id,
                    $payrollPeriodId,
                    $period
                );

                return !empty($approval['is_approved']);
            })
            ->values();
    }

    /**
     * Full COS payroll per employee for a period (posted summary or computed from attendance).
     *
     * @param  array<int, array>  $currentComputed
     * @return array<int, array>
     */
    private function resolveCosPayrollMapForPeriod(
        int $periodId,
        int $currentPeriodId,
        array $currentComputed
    ): array {
        if ($periodId === $currentPeriodId) {
            return $this->normalizeComputedByEmployee($currentComputed);
        }

        $fromSummary = $this->loadCosPayrollMapFromSummaries($periodId);
        if (!empty($fromSummary)) {
            return $fromSummary;
        }

        $period = DB::table('payroll_periods')->where('id', $periodId)->first();
        if (!$period) {
            return [];
        }

        $context = $this->buildCosPayrollContext($period, $periodId);
        $employees = $this->getCosEmployeesForPeriod($periodId);
        $map = [];
        foreach ($employees as $employee) {
            $map[(int) $employee->employee_id] = $this->computeCosEmployeePayroll($employee, $context);
        }

        return $map;
    }

    /**
     * @return array<int, array>
     */
    private function loadCosPayrollMapFromSummaries(int $periodId): array
    {
        $rows = DB::table('payroll_summaries as s')
            ->join('employees as e', 's.employee_id', '=', 'e.id')
            ->join('employment_types as et', 'e.employment_type_id', '=', 'et.id')
            ->where('s.payroll_period_id', $periodId)
            ->where(function ($q) {
                $q->where('et.name', 'Contract of Service')
                    ->orWhere('et.name', 'LIKE', '%Contract of Service%');
            })
            ->select(
                's.employee_id',
                's.gross_amount',
                's.late_amount',
                's.ut_amount',
                's.absent_amount',
                's.lwop_amount',
                's.total_income',
                's.tax',
                's.total_deduction',
                's.net_pay',
                'e.salary'
            )
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $employeeId = (int) $row->employee_id;
            $attendanceTotal = (float) ($row->late_amount ?? 0)
                + (float) ($row->ut_amount ?? 0)
                + (float) ($row->absent_amount ?? 0)
                + (float) ($row->lwop_amount ?? 0);
            $nvat = $this->loadCosDeductionAmount($periodId, $employeeId, 'COS NVAT');
            $premium = (float) ($row->total_income ?? 0);
            $ewt = (float) ($row->tax ?? 0);
            $gross = (float) ($row->gross_amount ?? 0);
            $balance = max(0.0, round($gross - $attendanceTotal, 2));

            $map[$employeeId] = [
                'monthly_salary' => round((float) ($row->salary ?? 0), 2),
                'gross_amount' => $gross,
                'late_amount' => (float) ($row->late_amount ?? 0),
                'ut_amount' => (float) ($row->ut_amount ?? 0),
                'absent_amount' => (float) ($row->absent_amount ?? 0),
                'lwop_amount' => (float) ($row->lwop_amount ?? 0),
                'attendance_total' => round($attendanceTotal, 2),
                'balance_after_attendance' => $balance,
                'premium' => $premium,
                'nvat' => $nvat,
                'ewt' => $ewt,
                'total_deduction' => (float) ($row->total_deduction ?? 0),
                'net_pay' => (float) ($row->net_pay ?? 0),
            ];
        }

        return $map;
    }

    private function loadCosDeductionAmount(int $payrollPeriodId, int $employeeId, string $deductionName): float
    {
        $amount = DB::table('payroll_deductions as pd')
            ->join('deductions as d', 'd.id', '=', 'pd.deduction_id')
            ->where('pd.payroll_period_id', $payrollPeriodId)
            ->where('pd.employee_id', $employeeId)
            ->where('d.name', $deductionName)
            ->value('pd.amount');

        return $amount !== null ? (float) $amount : 0.0;
    }

    private function saveCosPayrollSummary(int $payrollPeriodId, int $employeeId, array $payroll): void
    {
        DB::table('payroll_summaries')->insert([
            'payroll_period_id' => $payrollPeriodId,
            'employee_id' => $employeeId,
            'salary' => $payroll['gross_amount'],
            'gross_amount' => $payroll['gross_amount'],
            'total_income' => $payroll['premium'],
            'late_amount' => $payroll['late_amount'],
            'ut_amount' => $payroll['ut_amount'],
            'absent_amount' => $payroll['absent_amount'],
            'lwop_amount' => $payroll['lwop_amount'],
            'gsis' => 0,
            'sss' => 0,
            'pagibig' => 0,
            'philhealth' => 0,
            'tax' => $payroll['ewt'],
            'ot_amount' => 0,
            'nd_amount' => 0,
            'holiday_amount' => 0,
            'total_deduction' => $payroll['total_deduction'],
            'net_pay' => $payroll['net_pay'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->syncCosOtherDeductions($payrollPeriodId, $employeeId, $payroll);
    }

    private function syncCosOtherDeductions(int $payrollPeriodId, int $employeeId, array $payroll): void
    {
        $deductionMap = [
            'COS NVAT' => $payroll['nvat'],
        ];

        $deductionIds = $this->getCosDeductionTypeIds();

        DB::table('payroll_deductions')
            ->where('payroll_period_id', $payrollPeriodId)
            ->where('employee_id', $employeeId)
            ->whereIn('deduction_id', array_values($deductionIds))
            ->delete();

        foreach ($deductionMap as $name => $amount) {
            if ($amount <= 0) {
                continue;
            }
            $deductionId = $deductionIds[$name] ?? null;
            if (!$deductionId) {
                continue;
            }
            DB::table('payroll_deductions')->insert([
                'payroll_period_id' => $payrollPeriodId,
                'employee_id' => $employeeId,
                'deduction_id' => $deductionId,
                'amount' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return array<string, int>
     */
    private function getCosDeductionTypeIds(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $names = ['COS Premium', 'COS NVAT'];
        $cache = [];
        foreach ($names as $name) {
            $existing = DB::table('deductions')->where('name', $name)->value('id');
            if ($existing) {
                $cache[$name] = (int) $existing;
                continue;
            }
            $cache[$name] = (int) DB::table('deductions')->insertGetId([
                'name' => $name,
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $cache;
    }

    /**
     * Compute withholding tax for a COS employee using the monthly tax table (BIR TRAIN Law).
     *
     * @deprecated COS payroll uses 2% EWT on balance after attendance; kept for reference only.
     */
    private function computeCOSTax($schedule, float $monthly_salary, bool $is_half_month, int $payrollPeriodId, int $employeeId): float
    {
        if (!$schedule || empty($schedule->tax)) {
            return 0;
        }

        // Manual override takes priority (same as payroll_tax_adjustments in regular payroll)
        $adjustment = DB::table('payroll_tax_adjustments')
            ->where('employee_id', $employeeId)
            ->where('payroll_period_id', $payrollPeriodId)
            ->first();

        if ($adjustment) {
            $tax_monthly = (float) ($adjustment->tax_amount ?? 0);
            return $is_half_month ? round($tax_monthly / 2, 2) : round($tax_monthly, 2);
        }

        // BIR TRAIN Law monthly bracket lookup (tax_tables = taxTableType 2)
        $tax_data = DB::table('tax_tables')->orderBy('min_amount', 'asc')->get();
        if ($tax_data->isEmpty() || $monthly_salary <= 0) {
            return 0;
        }

        $taxable = $monthly_salary;
        $tax_monthly = 0;
        $arr_len = $tax_data->count();

        for ($i = 0; $i < $arr_len; $i++) {
            $bracket = $tax_data[$i];
            if ($taxable >= $bracket->min_amount && $taxable <= $bracket->max_amount) {
                $tax_monthly = (($taxable - $bracket->min_amount) * $bracket->percentage) + $bracket->base_tax;
                break;
            } elseif ($i === $arr_len - 1 && $taxable > $bracket->max_amount) {
                $tax_monthly = (($taxable - $bracket->min_amount) * $bracket->percentage) + $bracket->base_tax;
                break;
            }
        }

        return $is_half_month ? round($tax_monthly / 2, 2) : round($tax_monthly, 2);
    }

    /**
     * SQL connection for non_dtr_attachments (WTIHRIS_PTTC_ATTACHMENTS).
     */
    private function attachmentsConnection(): string
    {
        return (string) config('cos_payroll.attachments_connection', 'attachments');
    }

    private function nonDtrAttachmentsQuery()
    {
        return DB::connection($this->attachmentsConnection())
            ->table((string) config('cos_payroll.attachments_table', 'non_dtr_attachments'));
    }

    /**
     * @return object|null
     */
    private function findNonDtrAttachment(int $attachmentId, bool $withContent = false)
    {
        $query = $this->nonDtrAttachmentsQuery()->where('id', $attachmentId);

        if (!$withContent) {
            $query->select([
                'id',
                'non_dtr_task_id',
                'non_dtr_entry_id',
                'file_name',
                'file_type',
                'file_size',
                'description',
                'uploaded_by',
                'created_at',
                'updated_at',
            ]);
        }

        return $query->first();
    }

    /**
     * Resolve attachment bytes from attachments DB, then legacy disk/API fallbacks.
     *
     * @return array{content: string, content_type: ?string}|null
     */
    private function resolveNonDtrAttachmentBinary(int $attachmentId, ?object $attachment = null): ?array
    {
        if ($attachment === null || !isset($attachment->file_content)) {
            $attachment = $this->findNonDtrAttachment($attachmentId, true);
        }

        if ($attachment) {
            $content = $this->getAttachmentContentFromDb($attachment);
            if ($content !== null && strlen($content) > 0) {
                return [
                    'content' => $content,
                    'content_type' => $attachment->file_type ?? null,
                ];
            }
        }

        $fullPath = $attachment ? $this->resolveNonDtrAttachmentFullPath($attachment) : null;
        if ($fullPath !== null) {
            $bytes = @file_get_contents($fullPath);
            if ($bytes !== false && strlen($bytes) > 0) {
                return [
                    'content' => $bytes,
                    'content_type' => $attachment->file_type ?? null,
                ];
            }
        }

        return $this->fetchNonDtrAttachmentViaHrisApi($attachmentId);
    }

    /**
     * @return list<string>
     */
    private function eportalStorageRoots(): array
    {
        $roots = config('cos_payroll.eportal_storage_roots', []);
        if (!is_array($roots)) {
            $roots = [];
        }

        $explicit = rtrim((string) env('EPORTAL_STORAGE_ROOT', ''), '/\\');
        $normalized = [];

        // Always honor .env override — do not gate on is_dir() (Windows deploy paths may differ per process).
        if ($explicit !== '') {
            $normalized[] = $explicit;
        }

        foreach ($roots as $root) {
            $root = rtrim((string) $root, '/\\');
            if ($root === '' || $root === $explicit) {
                continue;
            }
            if (is_dir($root)) {
                $normalized[] = $root;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param list<string> $eportalRoots
     * @return list<string>
     */
    private function expectedNonDtrAttachmentPaths(object $attachment, array $eportalRoots): array
    {
        $fileName = trim((string) ($attachment->file_name ?? ''));
        $filePath = trim(str_replace('\\', '/', (string) ($attachment->file_path ?? '')), '/');
        $paths = [];

        foreach ($eportalRoots as $root) {
            if ($filePath !== '') {
                $paths[] = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
            }
            if ($fileName !== '') {
                $paths[] = $root . DIRECTORY_SEPARATOR . 'non_dtr_attachments' . DIRECTORY_SEPARATOR . $fileName;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * Resolve non-DTR attachment on E-Portal (ep-backend) or optional shared HRIS storage.
     * Does not use payroll (pr-backend) storage — uploads live in E-Portal only.
     */
    private function resolveNonDtrAttachmentFullPath(object $attachment): ?string
    {
        $filePath = trim(str_replace('\\', '/', (string) ($attachment->file_path ?? '')), '/');
        $fileName = trim((string) ($attachment->file_name ?? ''));
        $candidates = $this->expectedNonDtrAttachmentPaths($attachment, $this->eportalStorageRoots());

        $hrisRoot = rtrim((string) config('cos_payroll.hris_storage_root', ''), '/\\');
        if ($hrisRoot !== '') {
            if ($filePath !== '') {
                $candidates[] = $hrisRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
                $candidates[] = $hrisRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
            }
            if ($fileName !== '') {
                $candidates[] = $hrisRoot . DIRECTORY_SEPARATOR . 'non_dtr_attachments' . DIRECTORY_SEPARATOR . $fileName;
                $candidates[] = $hrisRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR
                    . 'non_dtr_attachments' . DIRECTORY_SEPARATOR . $fileName;
            }
        }

        foreach (array_unique(array_filter($candidates)) as $path) {
            if (is_file($path)) {
                return $path;
            }
            $resolved = realpath($path);
            if ($resolved !== false && is_file($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @return array{content: string, content_type: ?string}|null
     */
    private function fetchNonDtrAttachmentViaHrisApi(int $attachmentId): ?array
    {
        $base = rtrim((string) config('cos_payroll.hris_api_url', ''), '/');
        if ($base === '') {
            return null;
        }

        $urls = [
            $base . '/employee-non-dtr/attachment/' . $attachmentId . '/view',
            $base . '/employee-non-dtr/attachment/' . $attachmentId . '/download',
            $base . '/cos-payroll/attachments/' . $attachmentId . '/download',
        ];

        foreach ($urls as $url) {
            $payload = $this->requestNonDtrAttachmentFromUrl($url, $attachmentId);
            if ($payload !== null) {
                return $payload;
            }
        }

        return null;
    }

    /**
     * @return array{content: string, content_type: ?string}|null
     */
    private function requestNonDtrAttachmentFromUrl(string $url, int $attachmentId): ?array
    {
        try {
            $request = Http::timeout(30)->accept('*/*');
            if ($token = request()->bearerToken()) {
                $request = $request->withToken($token);
            }

            $response = $request->get($url);
            if (!$response->successful()) {
                return null;
            }

            $contentType = strtolower((string) $response->header('Content-Type', ''));
            if (str_contains($contentType, 'application/json') || str_contains($contentType, 'text/html')) {
                return null;
            }

            $body = $response->body();
            if ($body === '') {
                return null;
            }

            return [
                'content' => $body,
                'content_type' => $response->header('Content-Type'),
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch COS attachment from HRIS API', [
                'attachment_id' => $attachmentId,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get attachment binary content from DB if a BLOB/content column exists (e.g. file_content, content).
     * Supports raw binary, base64, and SQL Server varbinary hex (0x...).
     *
     * @param object $attachment row from non_dtr_attachments
     * @return string|null binary content or null if not stored in DB
     */
    private function getAttachmentContentFromDb($attachment): ?string
    {
        $possibleColumns = ['file_content', 'content', 'file_data', 'data', 'attachment_content', 'file_blob'];
        foreach ($possibleColumns as $col) {
            if (!isset($attachment->{$col}) || $attachment->{$col} === null) {
                continue;
            }
            $val = $attachment->{$col};
            if (is_resource($val)) {
                return stream_get_contents($val);
            }
            if (!is_string($val) || strlen($val) === 0) {
                continue;
            }
            // SQL Server varbinary often returned as hex with 0x prefix
            if (substr($val, 0, 2) === '0x' && strlen($val) > 2) {
                $decoded = @hex2bin(substr($val, 2));
                if ($decoded !== false) {
                    return $decoded;
                }
            }
            // Base64-encoded content
            if (preg_match('/^[A-Za-z0-9+\/=]+$/', $val) && strlen($val) % 4 === 0) {
                $decoded = base64_decode($val, true);
                if ($decoded !== false) {
                    return $decoded;
                }
            }
            // Raw binary
            return $val;
        }
        return null;
    }
}
