<?php

namespace App\Http\Controllers;

use PDF;
use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Color;

class PayrollProcessController extends Controller
{
    use ApiResponse;

    private function payrollProcessCacheVersionKey(int $payrollPeriodId): string
    {
        return "payroll_process:{$payrollPeriodId}:v";
    }

    private function payrollProcessCacheVersion(int $payrollPeriodId): int
    {
        return (int) Cache::get($this->payrollProcessCacheVersionKey($payrollPeriodId), 1);
    }

    private function bumpPayrollProcessCacheVersion(int $payrollPeriodId): void
    {
        $key = $this->payrollProcessCacheVersionKey($payrollPeriodId);
        $current = (int) Cache::get($key, 1);
        Cache::forever($key, $current + 1);
    }

    /** Invalidate server-side summary/breakdown cache after payroll mutations. */
    private function invalidatePayrollProcessCache(int $payrollPeriodId): int
    {
        $this->bumpPayrollProcessCacheVersion($payrollPeriodId);

        return $this->payrollProcessCacheVersion($payrollPeriodId);
    }

    private function payrollProcessCacheKey(string $scope, int $payrollPeriodId, ?int $employeeId = null): string
    {
        $userId = (int) (Auth::user()?->id ?? 0);
        $ver = $this->payrollProcessCacheVersion($payrollPeriodId);
        $emp = $employeeId ? ":emp:{$employeeId}" : "";
        return "payroll_process:{$payrollPeriodId}:v{$ver}:{$scope}{$emp}:u{$userId}";
    }

    /** Employment type ids configured on the selected payroll period. */
    private function resolvePayrollPeriodEmploymentTypeIds(int $payrollPeriodId): array
    {
        return DB::table('payroll_period_Etype')
            ->where('payrollperiod_id', $payrollPeriodId)
            ->pluck('employmenttype_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /** Sorted employment-type key for pairing semi-monthly periods in the same stream. */
    private function payrollPeriodEmploymentTypeKey(int $payrollPeriodId): string
    {
        $ids = $this->resolvePayrollPeriodEmploymentTypeIds($payrollPeriodId);
        sort($ids);

        return implode(',', $ids);
    }

    /**
     * Month periods that share the same payroll_period_Etype set as the period being processed.
     * Prevents Plantilla 1st/2nd halves from being paired with COS periods in the same month.
     */
    private function getMonthPeriodIdsForPayrollStream(
        int $payrollPeriodId,
        int $payrollIntervalId,
        int $year,
        int $month
    ) {
        $currentTypeKey = $this->payrollPeriodEmploymentTypeKey($payrollPeriodId);

        return DB::table('payroll_periods')
            ->where('payroll_interval_id', $payrollIntervalId)
            ->whereYear('release_date', $year)
            ->whereMonth('release_date', $month)
            ->where('active', true)
            ->orderBy('attendance_start_date')
            ->orderBy('id')
            ->pluck('id')
            ->filter(fn ($pid) => $this->payrollPeriodEmploymentTypeKey((int) $pid) === $currentTypeKey)
            ->values();
    }

    private function eligiblePayrollPeriodEmployeesQuery(int $payrollPeriodId)
    {
        $employmentTypeIds = $this->resolvePayrollPeriodEmploymentTypeIds($payrollPeriodId);
        if (empty($employmentTypeIds)) {
            return DB::table('employees as a')->whereRaw('1 = 0');
        }

        return DB::table('employees as a')
            ->whereIn('a.employment_type_id', $employmentTypeIds)
            ->where(function ($q) {
                $q->where('a.active', true)
                    ->orWhere('a.active', 1)
                    ->orWhere('a.active', 'true');
            });
    }

    private function countEligiblePayrollPeriodEmployees(int $payrollPeriodId): int
    {
        return (int) $this->eligiblePayrollPeriodEmployeesQuery($payrollPeriodId)->count('a.id');
    }

    /** Employees to process — all assigned via payroll_period_Etype for this period. */
    private function getPayrollPeriodEmployeesForProcessing(int $payrollPeriodId)
    {
        return $this->eligiblePayrollPeriodEmployeesQuery($payrollPeriodId)
            ->select(
                'a.id',
                'a.salary',
                'a.gsis_amount',
                'a.sss_amount',
                'a.pagibig_amount',
                'a.philhealth_amount',
                'a.tax_amount',
                'a.employment_type_id'
            )
            ->orderBy('a.id')
            ->get();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Returns an error message if the payroll period's month is not fully posted,
     * null if safe to proceed. For semi-monthly intervals (2 cutoffs), both halves
     * must be posted before summary reports can be generated.
     */
    private function requireFullMonthPosted(int $periodId): ?string
    {
        $period = DB::table('payroll_periods')->where('id', $periodId)->first();
        if (!$period) return 'Payroll period not found.';
        if (!$period->posted) return 'This payroll period has not been posted yet.';

        $cutoffCount = DB::table('payroll_cutoffs')
            ->where('payroll_interval_id', $period->payroll_interval_id)
            ->count();

        if ($cutoffCount < 2) return null;

        // For semi-monthly intervals, only block "full-month" reports.
        // If the selected payroll period is a 1st/2nd half cutoff, allow printing that half.
        $cutoffName = (string) DB::table('payroll_cutoffs')
            ->where('id', $period->payroll_cutoff_id)
            ->value('name');
        $cutoffLower = strtolower(trim($cutoffName));
        $isMonthlyCutoff = str_contains($cutoffLower, 'monthly');
        if (!$isMonthlyCutoff) {
            return null;
        }

        $unpostedCount = DB::table('payroll_periods')
            ->where('payroll_interval_id', $period->payroll_interval_id)
            ->whereYear('release_date', date('Y', strtotime($period->release_date)))
            ->whereMonth('release_date', date('n', strtotime($period->release_date)))
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('posted')
                    ->orWhere('posted', false)
                    ->orWhere('posted', 0)
                    ->orWhere('posted', 'false');
            })
            ->count();

        if ($unpostedCount > 0) {
            $monthYear = date('F Y', strtotime($period->release_date));
            return 'This report requires a fully-posted month. Please post BOTH 1st Half and 2nd Half payroll periods for ' . $monthYear . ' then try again.';
        }

        return null;
    }

    public function index()
    {
        try {

            // Regular Payroll Period — periods with plantilla/regular employment types (not COS-only)
            $payrolls = DB::table('payroll_periods as b')
                ->join('payroll_intervals as c', 'c.id', '=', 'b.payroll_interval_id')
                ->join('payroll_cutoffs as d', 'd.id', '=', 'b.payroll_cutoff_id')
                ->where('b.active', true)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('payroll_period_Etype as ppe')
                        ->join('employment_types as et', 'et.id', '=', 'ppe.employmenttype_id')
                        ->whereColumn('ppe.payrollperiod_id', 'b.id')
                        ->where(function ($q) {
                            $q->where('et.name', 'Plantilla')
                                ->orWhere('et.name', 'LIKE', '%Plantilla%')
                                ->orWhere('et.name', 'Permanent')
                                ->orWhere('et.name', 'LIKE', 'Regular-%')
                                ->orWhere('et.name', 'LIKE', 'Regular %');
                        });
                })
                ->select(
                    'b.*',
                    DB::raw("CONCAT(c.name,' (',DATENAME(MONTH,b.release_date),' ',DATEPART(YEAR,b.release_date),') ') as payroll"),
                    'd.name as cutoff_name'
                )
                ->orderBy('b.release_date', 'asc')
                ->distinct()
                ->get();

            // Convert posted field to boolean
            $payrolls = $payrolls->map(function ($payroll) {
                $payroll->posted = (bool) $payroll->posted;
                return $payroll;
            });

            // Attach employment types per payroll period
            $payrolls = $payrolls->map(function ($payroll) {
                $employmentTypes = DB::table('payroll_period_Etype as ppe')
                    ->join('employment_types as et', 'ppe.employmenttype_id', '=', 'et.id')
                    ->where('ppe.payrollperiod_id', $payroll->id)
                    ->select('et.id', 'et.name')
                    ->get();
                $payroll->employment_types = $employmentTypes;
                return $payroll;
            });

            // Attach employee count per period (all assigned employees, not only time_data rows)
            $payrolls = $payrolls->map(function ($payroll) {
                $payroll->employee_count = $this->countEligiblePayrollPeriodEmployees((int) $payroll->id);
                return $payroll;
            });

            return $this->successResponse($payrolls, 'Payroll processes retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll processes: ' . $e->getMessage());
        }
    }

    public function process(Request $request, $id)
    {
        try {
            // Resolve global include/exclude options from request (default to true to preserve existing behavior)
            $resolveFlag = function (string $key, bool $default = true) use ($request): bool {
                if (!$request->has($key)) {
                    return $default;
                }

                $value = $request->input($key);

                if (is_bool($value)) {
                    return $value;
                }

                if (is_string($value)) {
                    $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    return $filtered === null ? $default : $filtered;
                }

                return (bool) $value;
            };

            $includeGsisOption = $resolveFlag('includeGsis', true);
            $includeSssOption = $resolveFlag('includeSss', true);
            $includePagibigOption = $resolveFlag('includePagibig', true);
            $includePhilhealthOption = $resolveFlag('includePhilhealth', true);
            $includeTaxOption = $resolveFlag('includeTax', true);
            $includeAttendanceOption = $resolveFlag('includeAttendance', true);
            $includeOvertimeOption = $resolveFlag('includeOvertime', true);
            $includeHolidayOption = $resolveFlag('includeHoliday', true);

            // Tax table type: 1=Annual, 2=Monthly, 3=Semi-Monthly (default 2 for backwards compatibility).
            // Semi-monthly 1st/2nd half runs always use annualized TRAIN tax; dialog table type is ignored.
            $taxTableType = (int) ($request->input('taxTable', 2));
            if ($taxTableType < 1 || $taxTableType > 3) {
                $taxTableType = 2;
            }
            if ($resolveFlag('useAnnualizedTax', false)) {
                $taxTableType = 1;
            }

            // Check GSIS and tax setup only when those components are included
            if ($includeGsisOption) {
                $gsis_data = DB::table('gsis')->get();

                if ($gsis_data->isEmpty()) {
                    return $this->errorResponse("No GSIS Setup. Unable to process payroll.");
                }
            }

            $taxTableName = $taxTableType === 1 ? 'annual_tax_table' : ($taxTableType === 3 ? 'semi_monthly_tax_table' : 'tax_tables');
            if ($includeTaxOption) {
                $tax_data_check = DB::table($taxTableName)->get();

                if ($tax_data_check->isEmpty()) {
                    return $this->errorResponse("No Tax Setup for selected table ({$taxTableName}). Unable to process payroll.");
                }
            }

            $payroll_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'b.id', '=', 'a.payroll_interval_id')
                ->select(
                    'a.*',
                    'b.month_frequency'
                )
                ->where('a.id', $id)->get();

            if ($payroll_periods->isEmpty()) {
                return $this->notFoundResponse('Payroll period not found');
            }

            // Find a representative payroll_period id for this cutoff type (used in payroll_item_schedule_headers)
            $representative_period = DB::table('payroll_periods')
                ->where('payroll_interval_id', $payroll_periods[0]->payroll_interval_id)
                ->where('payroll_cutoff_id', $payroll_periods[0]->payroll_cutoff_id)
                ->where('active', true)
                ->orderBy('id', 'asc')
                ->first();

            $payroll_period_type_id = $representative_period ? $representative_period->id : $payroll_periods[0]->id;

            $payroll_item_schedules = DB::table('payroll_item_schedule_headers')
                ->where([
                    'payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                    'payroll_period_type_id' => $payroll_period_type_id,
                ])
                ->get()
                ->keyBy('employment_type_id');

            // get release year.
            $release_year = date('Y', strtotime($payroll_periods[0]->release_date));

            // set month frequency.
            $month_frequency = $payroll_periods[0]->month_frequency;

            // Semi-monthly: process 1st-half and 2nd-half payroll periods separately (never as one combined monthly run).
            $cutoff_count = DB::table('payroll_cutoffs')
                ->where('payroll_interval_id', $payroll_periods[0]->payroll_interval_id)
                ->count();

            $cutoffName = DB::table('payroll_cutoffs')
                ->where('id', $payroll_periods[0]->payroll_cutoff_id)
                ->value('name');
            $cutoffNameNormalized = is_string($cutoffName) ? strtolower($cutoffName) : '';
            $cutoffId = (int) ($payroll_periods[0]->payroll_cutoff_id ?? 0);

            $carry_year = (int) date('Y', strtotime($payroll_periods[0]->release_date));
            $carry_month = (int) date('n', strtotime($payroll_periods[0]->release_date));

            // Periods in this month with the same payroll_period_Etype set (1st/2nd half pairing).
            $month_period_ids = $this->getMonthPeriodIdsForPayrollStream(
                (int) $id,
                (int) $payroll_periods[0]->payroll_interval_id,
                $carry_year,
                $carry_month
            );

            $is_first_half_period = false;
            $is_second_half_period = false;

            if ($cutoff_count >= 2 && $month_period_ids->count() >= 2) {
                if ((int) $month_period_ids[0] === (int) $id) {
                    $is_first_half_period = true;
                } elseif ((int) $month_period_ids[1] === (int) $id) {
                    $is_second_half_period = true;
                }
            }

            // Name-based detection when month ordering alone is insufficient (e.g. only one row in month so far).
            if ($cutoff_count >= 2 && !$is_first_half_period && !$is_second_half_period) {
                $is_first_half_period = str_contains($cutoffNameNormalized, '1st')
                    || str_contains($cutoffNameNormalized, 'first')
                    || $cutoffId === 1;
                $is_second_half_period = str_contains($cutoffNameNormalized, '2nd')
                    || str_contains($cutoffNameNormalized, 'second')
                    || $cutoffId === 3;
            }

            $is_half_payroll_period = $is_first_half_period || $is_second_half_period;

            // Block a separate combined Monthly row (3rd slot or not one of the two semi-monthly periods).
            $is_monthly_period = str_contains($cutoffNameNormalized, 'monthly')
                && !$is_half_payroll_period;

            if ($cutoff_count >= 2 && $is_monthly_period) {
                return $this->errorResponse(
                    'Process First Half and Second Half payroll periods separately. Do not process the combined Monthly cutoff for semi-monthly payroll.'
                );
            }

            // Used for divisor / semi-monthly splits (GSIS, Pag-IBIG, etc.).
            $is_first_or_second_half = $is_half_payroll_period;

            // Semi-monthly period pairing for contribution reconciliation.
            $first_half_period_id = ($month_period_ids->count() >= 1) ? (int) $month_period_ids[0] : null;
            $second_half_period_id = ($month_period_ids->count() >= 2) ? (int) $month_period_ids[1] : null;
            if (!$is_second_half_period) {
                $first_half_period_id = $is_first_half_period ? (int) $id : $first_half_period_id;
            }

            // Auto-copy income items from previous period based on schedule
            // Find previous period of the same type (same interval and cutoff).
            // Note: `payroll_start_date` can be NULL for 2nd-half cutoffs, so use attendance date for ordering.
            $previous_period = DB::table('payroll_periods')
                ->where('payroll_interval_id', $payroll_periods[0]->payroll_interval_id)
                ->where('payroll_cutoff_id', $payroll_periods[0]->payroll_cutoff_id)
                ->where('attendance_start_date', '<', $payroll_periods[0]->attendance_start_date)
                ->where('active', true)
                ->orderBy('attendance_start_date', 'desc')
                ->first();

            if ($previous_period) {
                // Get active income items from schedule for all employment types
                $schedule_income_items = DB::table('payroll_item_schedule_headers as a')
                    ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                    ->where([
                        'a.payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                        'a.payroll_period_type_id' => $payroll_period_type_id,
                        'b.active' => true
                    ])
                    ->where('b.income_id', '>', 0)
                    ->select('a.employment_type_id', 'b.income_id')
                    ->distinct()
                    ->get();

                // Get employees for current period
                $current_employees = DB::table('employees')
                    ->where('active', true)
                    ->where('is_employee', true)
                    ->whereIn('employment_type_id', function ($q) use ($id) {
                        $q->select('employmenttype_id')
                            ->from('payroll_period_Etype')
                            ->where('payrollperiod_id', $id);
                    })
                    ->pluck('id', 'employment_type_id')
                    ->toArray();

                // For each scheduled income item, copy from previous period if exists
                foreach ($schedule_income_items as $schedule_item) {
                    $employment_type_id = $schedule_item->employment_type_id;
                    $income_id = $schedule_item->income_id;

                    // Get employees of this employment type for current period
                    // First check if this employment type is included in the payroll period
                    $is_employment_type_included = DB::table('payroll_period_Etype')
                        ->where('payrollperiod_id', $id)
                        ->where('employmenttype_id', $employment_type_id)
                        ->exists();

                    if (!$is_employment_type_included) {
                        continue; // Skip if this employment type is not in the current period
                    }

                    $employees_of_type = DB::table('employees')
                        ->where('active', true)
                        ->where('is_employee', true)
                        ->where('employment_type_id', $employment_type_id)
                        ->pluck('id')
                        ->toArray();

                    // Get previous period incomes for this income_id and employment_type
                    $previous_incomes = DB::table('payroll_incomes')
                        ->where('payroll_period_id', $previous_period->id)
                        ->where('income_id', $income_id)
                        ->where('employment_id', $employment_type_id)
                        ->whereIn('employee_id', $employees_of_type)
                        ->get();

                    // Copy to current period if not already exists
                    foreach ($previous_incomes as $prev_income) {
                        $exists = DB::table('payroll_incomes')
                            ->where('payroll_period_id', $id)
                            ->where('employee_id', $prev_income->employee_id)
                            ->where('income_id', $income_id)
                            ->where('employment_id', $employment_type_id)
                            ->exists();

                        if (!$exists && $prev_income->amount > 0) {
                            DB::table('payroll_incomes')->insert([
                                'payroll_period_id' => $id,
                                'employment_id' => $employment_type_id,
                                'income_id' => $income_id,
                                'employee_id' => $prev_income->employee_id,
                                'amount' => $prev_income->amount,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }
                }
            }

            // All employees assigned to this payroll period (attendance optional; zeros when no time_data).
            $employees = $this->getPayrollPeriodEmployeesForProcessing((int) $id);

            if ($employees->isEmpty()) {
                return $this->errorResponse('No employees assigned to this payroll period.', 400);
            }

            $use_contribution_reconciliation_period = $cutoff_count >= 2
                && $month_period_ids->count() >= 2
                && $month_period_ids->take(2)->contains((int) $id);

            // Reprocess must recalc fixed annualized tax — do not keep old per-employee overrides (e.g. ₱1,500).
            if ($use_contribution_reconciliation_period) {
                DB::table('payroll_tax_adjustments')->where('payroll_period_id', $id)->delete();
            }

            foreach ($employees as $emp) {

                    $schedule = $payroll_item_schedules->get($emp->employment_type_id);
                    // Combine per-employment-type schedule flags with global include options from the UI
                    $include_gsis = $includeGsisOption && ($schedule ? (bool) $schedule->gsis : false);
                    $include_sss = $includeSssOption && ($schedule ? (bool) $schedule->sss : false);
                    $include_pagibig = $includePagibigOption && ($schedule ? (bool) $schedule->pagibig : false);
                    $include_philhealth = $includePhilhealthOption && ($schedule ? (bool) $schedule->philhealth : false);
                    $include_tax = $includeTaxOption && ($schedule ? (bool) $schedule->tax : false);

                    $gsis_divisor = DB::table('payroll_item_schedule_headers as a')
                        ->join('payroll_intervals as b', 'a.payroll_interval_type_id', '=', 'b.id')
                        ->join('payroll_periods as c', 'a.payroll_period_type_id', '=', 'c.id')
                        ->where([
                            'a.payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                            'a.employment_type_id' => $emp->employment_type_id,
                            'a.gsis' => true
                        ])
                        ->count();

                    $sss_divisor = DB::table('payroll_item_schedule_headers as a')
                        ->join('payroll_intervals as b', 'a.payroll_interval_type_id', '=', 'b.id')
                        ->join('payroll_periods as c', 'a.payroll_period_type_id', '=', 'c.id')
                        ->where([
                            'a.payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                            'a.employment_type_id' => $emp->employment_type_id,
                            'a.sss' => true
                        ])
                        ->count();

                    $pagibig_divisor = DB::table('payroll_item_schedule_headers as a')
                        ->join('payroll_intervals as b', 'a.payroll_interval_type_id', '=', 'b.id')
                        ->join('payroll_periods as c', 'a.payroll_period_type_id', '=', 'c.id')
                        ->where([
                            'a.payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                            'a.employment_type_id' => $emp->employment_type_id,
                            'a.pagibig' => true
                        ])
                        ->count();

                    $philhealth_divisor = DB::table('payroll_item_schedule_headers as a')
                        ->join('payroll_intervals as b', 'a.payroll_interval_type_id', '=', 'b.id')
                        ->join('payroll_periods as c', 'a.payroll_period_type_id', '=', 'c.id')
                        ->where([
                            'a.payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                            'a.employment_type_id' => $emp->employment_type_id,
                            'a.philhealth' => true
                        ])
                        ->count();

                    $tax_divisor = DB::table('payroll_item_schedule_headers as a')
                        ->join('payroll_intervals as b', 'a.payroll_interval_type_id', '=', 'b.id')
                        ->join('payroll_periods as c', 'a.payroll_period_type_id', '=', 'c.id')
                        ->where([
                            'a.payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                            'a.employment_type_id' => $emp->employment_type_id,
                            'a.tax' => true
                        ])
                        ->count();

                    $employee_id = $emp->id;
                    $monthly_salary = (float) $emp->salary;
                    // Basic salary: use full monthly amount (no division for first/second half)
                    $salary = round($monthly_salary, 2);
                    $basic_pay = $salary;

                    $time_keeping = DB::table('time_keeping_setups')->where('employment_type_id', $emp->employment_type_id)->get();

                    if ($time_keeping->isNotEmpty()) {
                        $tk_days = $time_keeping[0]->work_days == 0 ? 22 : $time_keeping[0]->work_days;
                        $tk_hours = $time_keeping[0]->work_hours == 0 ? 8 : $time_keeping[0]->work_hours;
                    } else {
                        $tk_days = 22;
                        $tk_hours = 8;
                    }

                    $taxable_incomes = DB::table('payroll_incomes as a')
                        ->join('incomes as b', 'b.id', '=', 'a.income_id')
                        ->select(DB::raw("sum(a.amount) as income"))
                        ->where('a.payroll_period_id', $id)
                        ->where('a.employee_id', $employee_id)
                        ->where('b.is_taxable', 1)
                        ->groupBy('a.employee_id')
                        ->get();

                    if ($taxable_incomes->isEmpty()) {
                        $total_taxable_income = 0;
                    } else {
                        $total_taxable_income = $taxable_incomes[0]->income;
                    }

                    $daily_rate = round(($salary / $tk_days), 2);

                    $hourly_rate = round(($daily_rate / $tk_hours), 2);

                    $timeSummary = DB::table('time_data_summary')
                        ->where(['payroll_period_id' => $id, 'employee_id' => $employee_id])
                        ->first();

                    $effectiveAbsentAmount = 0;

                    if (!$timeSummary) {
                        $late_amount = 0;
                        $ut_amount = 0;
                        $absent_amount = 0;
                        $lwop_amount = 0;
                        $total_tardiness = 0;
                    } else {
                        $employee_salary = $emp->salary ?? 0;
                        $max_reasonable_amount = max($employee_salary * 2, 1000000);

                        $late_amount_raw = isset($timeSummary->Late_Amount) ? $timeSummary->Late_Amount : 0;
                        $late_amount = is_numeric($late_amount_raw) ? (float)$late_amount_raw : 0;
                        if ($late_amount < 0 || $late_amount > $max_reasonable_amount || !is_finite($late_amount)) {
                            $late_amount = 0;
                        }

                        $ut_amount_raw = isset($timeSummary->Undertime_Amount) ? $timeSummary->Undertime_Amount : 0;
                        $ut_amount = is_numeric($ut_amount_raw) ? (float)$ut_amount_raw : 0;

                        if ($ut_amount < 0 || $ut_amount > $max_reasonable_amount || !is_finite($ut_amount)) {
                            $ut_amount = 0;
                        }


                        $absent_amount_raw = isset($timeSummary->Absent_Amount) ? $timeSummary->Absent_Amount : 0;
                        $absent_amount = is_numeric($absent_amount_raw) ? (float)$absent_amount_raw : 0;

                        if ($absent_amount < 0 || $absent_amount > $max_reasonable_amount || !is_finite($absent_amount)) {
                            $absent_amount = 0;
                        }

                        // Safely convert and validate lwop_amount
                        $lwop_amount_raw = isset($timeSummary->LWOP_Amount) ? $timeSummary->LWOP_Amount : 0;
                        $lwop_amount = is_numeric($lwop_amount_raw) ? (float)$lwop_amount_raw : 0;

                        if ($lwop_amount < 0 || $lwop_amount > $max_reasonable_amount || !is_finite($lwop_amount)) {
                            $lwop_amount = 0;
                        }

                        // Cap total tardiness deductions at basic_pay so bad time data cannot overflow DB or produce invalid net pay
                        $total_tardiness = $late_amount + $ut_amount + $absent_amount;
                        if ($total_tardiness > $basic_pay && $basic_pay > 0) {
                            $ratio = (float) $basic_pay / $total_tardiness;
                            $late_amount = round($late_amount * $ratio, 2);
                            $ut_amount = round($ut_amount * $ratio, 2);
                            $absent_amount = round($absent_amount * $ratio, 2);
                            $total_tardiness = $late_amount + $ut_amount + $absent_amount;
                        }

                        // Per spec: leave credits absorb tardiness/UT first, then absences.
                        // Deductions only apply once credits are exhausted.
                        $leaveWithPayDays = (float) DB::table('time_data')
                            ->where('employee_id', $employee_id)
                            ->where('payroll_period_id', $id)
                            ->sum('leave');
                        $leaveWithPayAmount = round($leaveWithPayDays * $daily_rate, 2);

                        // Absorb late
                        $lateOffset = min($leaveWithPayAmount, $late_amount);
                        $late_amount = max(0, $late_amount - $lateOffset);
                        $leaveWithPayAmount -= $lateOffset;

                        // Absorb undertime
                        $utOffset = min($leaveWithPayAmount, $ut_amount);
                        $ut_amount = max(0, $ut_amount - $utOffset);
                        $leaveWithPayAmount -= $utOffset;

                        // Absorb absences
                        $absentOffset = min($leaveWithPayAmount, $absent_amount);
                        $effectiveAbsentAmount = max(0, $absent_amount - $absentOffset);

                        $total_tardiness = $late_amount + $ut_amount + $effectiveAbsentAmount;
                    }

                    // Optionally exclude attendance-based deductions (late, undertime, absent)
                    if (!$includeAttendanceOption) {
                        $late_amount = 0;
                        $ut_amount = 0;
                        $absent_amount = 0;
                        $effectiveAbsentAmount = 0;
                        $total_tardiness = 0;
                    }

                    // Preceding period adjustment from time_data_summary (Adjustment_Amount)
                    $preceding_period_adjustment = ($timeSummary && isset($timeSummary->Adjustment_Amount) && is_numeric($timeSummary->Adjustment_Amount))
                        ? (float) $timeSummary->Adjustment_Amount
                        : 0;

                    $period_income_total = (float) (DB::table('payroll_incomes')
                        ->where(['payroll_period_id' => $id, 'employee_id' => $employee_id])
                        ->sum('amount') ?? 0);

                    $pagibig_payrolls = DB::table('pagibig_payroll_headers as a')
                        ->where([
                            'a.employee_id' => $employee_id
                        ])
                        ->where('a.payroll_period_id', '<=', $id)
                        ->orderBy('a.payroll_period_id', 'desc')
                        ->limit(1)
                        ->get();

                    $year = $payroll_periods[0]->payroll_start_date;
                    $pagibig_setup_amount = DB::table('pagibig_setups')->whereRaw("year = YEAR('$year')")->get();

                    if ($pagibig_payrolls->isNotEmpty()) {
                        $pagibig_amount = $pagibig_payrolls[0]->amount;
                        $pagibig_tax_amount = $pagibig_amount;
                    } else {
                        if ($pagibig_setup_amount->isNotEmpty()) {
                            $pagibig_amount = $pagibig_setup_amount[0]->amount;
                            $pagibig_tax_amount = $pagibig_setup_amount[0]->amount;
                        } else {
                            $pagibig_amount = 200;
                            $pagibig_tax_amount = 200;
                        }
                    }

                    // GSIS and PhilHealth will be calculated AFTER tax computation (per custom requirements)
                    // Initialize to 0 here, will be recalculated later
                    $philhealth_amount = 0;
                    $philhealth_amount_gs = 0;
                    $gsis_amount = 0;

                    $sss_amount = 0;


                    $employee_tax_adjustments = DB::table('payroll_tax_adjustments')
                        ->where([
                            'employee_id' => $employee_id,
                            'payroll_period_id' => $id
                        ])
                        ->get();

                    // --- TRAIN Law: annual taxable = monthly taxable × 12
                    // $periods_per_year = ($payroll_periods[0]->month_frequency > 0) ? ((int) $payroll_periods[0]->month_frequency * 12) : 24;
                    // $tax_divisor_effective = ($tax_divisor == null || $tax_divisor == 0) ? $periods_per_year : (int) $tax_divisor;
                    // $periods_per_year_train = $is_first_or_second_half ? 24 : ($periods_per_year > 0 ? $periods_per_year : 24);

                    // $tax_from_adjustment = false;
                    // if ($employee_tax_adjustments->isNotEmpty()) {
                    //     $tax_amount = $employee_tax_adjustments[0]->tax_amount ?? 0;
                    //     $tax_from_adjustment = true;
                    // } else {
                    //     $tax_data = DB::table('tax_tables')->orderBy('min_amount', 'asc')->get();
                    //     $arr_len = $tax_data->count();

                    //     $taxable_amount_per_period = (($salary + $total_taxable_income) - ($gsis_amount + $philhealth_amount + $pagibig_tax_amount + $total_tardiness));
                    //     $taxable_amount_monthly = $taxable_amount_per_period * ($periods_per_year_train / 12);
                    //     $taxable_amount_annual = $taxable_amount_monthly * 12;
                    //     $tax_amount = 0;

                    //     if ($taxable_amount_annual > 0 && $arr_len > 0) {
                    //         if ($taxable_amount_annual < $tax_data[0]->min_amount) {
                    //             $tax_amount = 0;
                    //         } else {
                    //             $found = false;
                    //             for ($i = 0; $i < $arr_len; $i++) {
                    //                 if ($taxable_amount_annual >= $tax_data[$i]->min_amount && $taxable_amount_annual <= $tax_data[$i]->max_amount) {
                    //                     $tax_amount = ((($taxable_amount_annual - $tax_data[$i]->min_amount) * $tax_data[$i]->percentage) + $tax_data[$i]->base_tax);
                    //                     $found = true;
                    //                     break;
                    //                 } elseif ($i == ($arr_len - 1) && $taxable_amount_annual > $tax_data[$i]->max_amount) {
                    //                     $tax_amount = ((($taxable_amount_annual - $tax_data[$i]->min_amount) * $tax_data[$i]->percentage) + $tax_data[$i]->base_tax);
                    //                     $found = true;
                    //                     break;
                    //                 }
                    //             }
                    //             if (!$found) {
                    //                 $tax_amount = 0;
                    //             }
                    //         }
                    //     }
                    // }



                    // --- Tax table uses monthly brackets: lookup by monthly taxable, then split to per-period
                    // CUSTOM TAX CALCULATION (Per User Requirements)
                    // Step 1: (Basic Salary + Taxable Income) - (Late + Undertime + Absent) = Taxable Amount
                    // Step 2: Calculate tax on that amount
                    // Step 3: Compute GSIS and PhilHealth from after-tax amount
                    $periods_per_year = ($payroll_periods[0]->month_frequency > 0) ? ((int) $payroll_periods[0]->month_frequency * 12) : 24;
                    $tax_divisor_effective = ($tax_divisor == null || $tax_divisor == 0) ? $periods_per_year : (int) $tax_divisor;
                    $periods_per_year_train = $is_first_or_second_half ? 24 : ($periods_per_year > 0 ? $periods_per_year : 24);
                    $periods_per_month = $periods_per_year_train / 12; // 2 for semi-monthly, 1 for monthly

                    // Semi-monthly: always annualized fixed tax on the two periods in the month (dialog tax table ignored).
                    $use_contribution_reconciliation = $use_contribution_reconciliation_period;
                    $period_basic_pay = $is_half_payroll_period ? ($monthly_salary / 2) : $monthly_salary;
                    $half_basic_gross = $period_basic_pay;
                    $tax_per_period = 0.0;

                    $gsis_data = DB::table('gsis')->where('year', '<=', $release_year)->orderBy('year', 'desc')->get();
                    if ($gsis_data->isEmpty()) {
                        $gsis_data = DB::table('gsis')->orderBy('year', 'desc')->get();
                    }

                    $ph_data = DB::table('philhealths')
                        ->where('year', '<=', $release_year)
                        ->orderBy('year', 'desc')
                        ->get();
                    if ($ph_data->isEmpty()) {
                        $ph_data = DB::table('philhealths')->orderBy('year', 'desc')->get();
                    }

                    $tax_from_adjustment = false;

                    if ($use_contribution_reconciliation) {
                        // --- Annualized fixed tax: same amount on 1st and 2nd half (monthly tax ÷ 2); not affected by LWOP ---
                        // payroll_tax_adjustments are cleared on reprocess; use Adjust Tax after run for manual overrides.
                        if ($include_tax) {
                            // Tax base only: projected FULL-YEAR contributions (not actual half deductions / recon).
                            $annual_taxable = $this->computeAnnualTaxableIncome(
                                $monthly_salary,
                                $gsis_data,
                                $ph_data,
                                (float) $pagibig_amount
                            );
                            $tax_monthly_fixed = $this->computeAnnualizedFixedMonthlyTax($annual_taxable);
                            $tax_per_period = $tax_monthly_fixed / max($periods_per_month, 1);
                            $tax_amount = $tax_monthly_fixed;
                        } else {
                            $tax_monthly_fixed = 0.0;
                            $tax_per_period = 0.0;
                            $tax_amount = 0.0;
                        }

                        // --- GSIS / PhilHealth: flexible recon when LWOP/absent; 2nd half always nets vs 1st half paid ---
                        $gsis_amount = 0.0;
                        $philhealth_amount = 0.0;
                        $philhealth_amount_gs = 0.0;
                        $fhRecon = null;
                        $recon_base = null;
                        // Trigger recon when LWOP/absent or preceding-period adjustment affects the base.
                        $period_has_lwop = $this->periodHasLwopOrAbsent(
                            (float) $lwop_amount,
                            (float) $absent_amount
                        );
                        $period_has_recon_adjustment = $period_has_lwop
                            || $preceding_period_adjustment > 0;
                        $attendance_reduction_for_recon = $this->attendanceReductionForContributionRecon(
                            (float) $lwop_amount,
                            (float) $absent_amount,
                            (float) $effectiveAbsentAmount
                        );

                        $period_gross_before_recon = $half_basic_gross + $period_income_total;
                        $current_period_gross = $this->computePeriodReconGross(
                            $period_gross_before_recon,
                            $attendance_reduction_for_recon,
                            $preceding_period_adjustment
                        );

                        if ($is_first_half_period && $period_has_recon_adjustment) {
                            // 2nd half: full half-month basic when not yet processed (perfect attendance assumed).
                            $second_half_gross = $this->resolveSecondHalfGrossForRecon(
                                $second_half_period_id,
                                $employee_id,
                                round($monthly_salary / 2, 2),
                                $monthly_salary,
                                $tk_days,
                                $tk_hours
                            );
                            $recon_base = $current_period_gross + $second_half_gross;
                        } elseif ($is_second_half_period) {
                            $fhRecon = $this->resolveFirstHalfReconContext(
                                $first_half_period_id,
                                $employee_id,
                                $half_basic_gross,
                                $monthly_salary,
                                $tk_days,
                                $tk_hours
                            );
                            $recon_base = $fhRecon['first_half_gross'] + $current_period_gross;
                        }

                        if ($include_gsis && $gsis_data->isNotEmpty()) {
                            $gsis_multiplier = (float) $gsis_data[0]->multiplier;
                            if ($is_first_half_period) {
                                if ($period_has_recon_adjustment && $recon_base !== null) {
                                    $monthly_gsis_total = $recon_base * $gsis_multiplier;
                                    $gsis_amount = round($monthly_gsis_total / 2, 2);
                                } else {
                                    $gsis_amount = ($monthly_salary * $gsis_multiplier) / 2;
                                }
                            } elseif ($is_second_half_period && $fhRecon !== null && $recon_base !== null) {
                                $monthly_gsis_total = $recon_base * $gsis_multiplier;
                                $gsis_amount = max(
                                    0.0,
                                    $monthly_gsis_total - $fhRecon['first_half_gsis_paid']
                                );
                            }
                        }

                        if ($include_philhealth && $ph_data->isNotEmpty()) {
                            $ph_row = $ph_data[0];
                            $monthly_ph_share = $this->computeMonthlyPhilhealthShare($monthly_salary, $ph_row);
                            if ($is_first_half_period) {
                                if ($period_has_recon_adjustment && $recon_base !== null) {
                                    $monthly_ph_total = $this->computeMonthlyPhilhealthShare($recon_base, $ph_row);
                                    $philhealth_amount = $monthly_ph_total / max((float) $philhealth_divisor, 1);
                                } else {
                                    $philhealth_amount = $monthly_ph_share / max((float) $philhealth_divisor, 1);
                                }
                                $philhealth_amount_gs = $philhealth_amount;
                            } elseif ($is_second_half_period && $fhRecon !== null && $recon_base !== null) {
                                $monthly_ph_total = $this->computeMonthlyPhilhealthShare($recon_base, $ph_row);
                                $philhealth_amount = max(
                                    0.0,
                                    $monthly_ph_total - $fhRecon['first_half_ph_paid']
                                );
                                $philhealth_amount_gs = $philhealth_amount;
                            }
                        }
                    } elseif ($employee_tax_adjustments->isNotEmpty()) {
                        $tax_amount = $employee_tax_adjustments[0]->tax_amount ?? 0;
                        $tax_from_adjustment = true;
                        $tax_per_period = $tax_amount / max($periods_per_month, 1);
                    } else {
                        // BIR TRAIN Law: each table type uses different taxable base
                        // 1=Annual: brackets are annual → use monthly_salary*12, get annual tax, divide by 12
                        // 2=Monthly: brackets are monthly → use monthly_salary, get monthly tax directly
                        // 3=Semi-Monthly: brackets are per pay period → use monthly_salary/2, get tax per period directly
                        $tax_data = DB::table($taxTableName)->orderBy('min_amount', 'asc')->get();
                        $arr_len = $tax_data->count();

                        $taxable_lookup = 0;
                        $tax_result = 0;

                        if ($taxTableType === 1) {
                            $taxable_lookup = $monthly_salary * 12;
                        } elseif ($taxTableType === 2) {
                            $taxable_lookup = $monthly_salary;
                        } else {
                            $taxable_lookup = $monthly_salary / 2;
                        }

                        if ($arr_len > 0 && $taxable_lookup > 0) {
                            if ($taxable_lookup < $tax_data[0]->min_amount) {
                                $tax_result = 0;
                            } else {
                                for ($i = 0; $i < $arr_len; $i++) {
                                    if ($taxable_lookup >= $tax_data[$i]->min_amount && $taxable_lookup <= $tax_data[$i]->max_amount) {
                                        $tax_result = (($taxable_lookup - $tax_data[$i]->min_amount) * $tax_data[$i]->percentage) + $tax_data[$i]->base_tax;
                                        break;
                                    } elseif ($i == ($arr_len - 1) && $taxable_lookup > $tax_data[$i]->max_amount) {
                                        $tax_result = (($taxable_lookup - $tax_data[$i]->min_amount) * $tax_data[$i]->percentage) + $tax_data[$i]->base_tax;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($taxTableType === 1) {
                            $tax_amount = $tax_result / 12;
                            $tax_per_period = $tax_amount / max($periods_per_month, 1);
                        } elseif ($taxTableType === 2) {
                            $tax_amount = $tax_result;
                            $tax_per_period = $tax_amount / max($periods_per_month, 1);
                        } else {
                            $tax_per_period = $tax_result;
                            $tax_amount = $tax_per_period * max($periods_per_month, 1);
                        }
                    }

                    if (!$use_contribution_reconciliation) {
                    // ---------- GSIS COMPUTATION (legacy) ----------
                    // Toggle: set to true = GSIS based on adjusted_basic_pay (revised logic); set to false = GSIS based on full monthly salary (old).
                    $useGsisAdjustedBasic = true;

                    if ($gsis_data->isEmpty()) {
                        $gsis_data = DB::table('gsis')->where('year', '<=', $release_year)->orderBy('year', 'desc')->get();
                        if ($gsis_data->isEmpty()) {
                            $gsis_data = DB::table('gsis')->orderBy('year', 'desc')->get();
                        }
                    }
                    if ($gsis_data->isNotEmpty()) {
                        if ($useGsisAdjustedBasic) {
                            // Revised: GSIS = computed based on adjusted_basic_pay (monthly salary net of tardiness/UT/absences), same as PhilHealth.
                            $gsis_tardiness_total = (float) $total_tardiness;
                            if ($is_second_half_period && !empty($first_half_period_id)) {
                                $firstHalfTimeGsis = DB::table('time_data_summary')
                                    ->where([
                                        'payroll_period_id' => $first_half_period_id,
                                        'employee_id' => $employee_id,
                                    ])
                                    ->select('Late_Amount', 'Undertime_Amount', 'Absent_Amount')
                                    ->first();
                                if ($firstHalfTimeGsis) {
                                    $fh_late_g = is_numeric($firstHalfTimeGsis->Late_Amount ?? null) ? (float) $firstHalfTimeGsis->Late_Amount : 0.0;
                                    $fh_ut_g = is_numeric($firstHalfTimeGsis->Undertime_Amount ?? null) ? (float) $firstHalfTimeGsis->Undertime_Amount : 0.0;
                                    $fh_absent_g = is_numeric($firstHalfTimeGsis->Absent_Amount ?? null) ? (float) $firstHalfTimeGsis->Absent_Amount : 0.0;
                                    $fh_late_g = ($fh_late_g > 0 && is_finite($fh_late_g)) ? $fh_late_g : 0.0;
                                    $fh_ut_g = ($fh_ut_g > 0 && is_finite($fh_ut_g)) ? $fh_ut_g : 0.0;
                                    $fh_absent_g = ($fh_absent_g > 0 && is_finite($fh_absent_g)) ? $fh_absent_g : 0.0;
                                    $gsis_tardiness_total = $fh_late_g + $fh_ut_g + $fh_absent_g;
                                }
                            }
                            if ($gsis_tardiness_total > $basic_pay && $basic_pay > 0) {
                                $gsis_tardiness_total = (float) $basic_pay;
                            }
                            $adjusted_salary_gsis = $monthly_salary - $gsis_tardiness_total;
                            $gsis_monthly = max(0, $adjusted_salary_gsis) * (float) $gsis_data[0]->multiplier;
                        } else {
                            // Old: full monthly basic salary × GSIS multiplier; for half-month, split by 2.
                            $gsis_monthly = $monthly_salary * (float) $gsis_data[0]->multiplier;
                        }

                        if ($include_gsis == 0) {
                            $gsis_amount = 0;
                        } elseif (!$is_first_or_second_half || $gsis_divisor === null || $gsis_divisor === 0 || $gsis_divisor === 1) {
                            $gsis_amount = $gsis_monthly;
                        } else {
                            $gsis_amount = $gsis_monthly / (float) $gsis_divisor;
                        }
                    }

                    // PHILHEALTH COMPUTATION (legacy)
                    if ($ph_data->isEmpty()) {
                        $ph_data = DB::table('philhealths')
                            ->where('year', '<=', $release_year)
                            ->orderBy('year', 'desc')
                            ->get();
                        if ($ph_data->isEmpty()) {
                            $ph_data = DB::table('philhealths')->orderBy('year', 'desc')->get();
                        }
                    }
                    if ($ph_data->isNotEmpty()) {
                        $ph_multiplier = (float) $ph_data[0]->multiplier;
                        $ph_income_ceilling = (float) $ph_data[0]->income_ceiling;
                        $ph_income_floor = (float) $ph_data[0]->income_floor;
                        $ph_fix_rate = (float) $ph_data[0]->fix_rate;
                        // $ph_fix_rate = (float) $ph_data[0]->philhealth > 2500 ? 2500 : (float) $ph_data[0]->philhealth;


                        // PhilHealth base uses monthly salary net of attendance deductions (tardiness/absences).
                        // For 2nd half periods, use the 1st half month's tardiness so PhilHealth stays consistent across halves.
                        $philhealth_tardiness_total = (float) $total_tardiness;
                        if ($is_second_half_period && !empty($first_half_period_id)) {
                            $firstHalfTime = DB::table('time_data_summary')
                                ->where([
                                    'payroll_period_id' => $first_half_period_id,
                                    'employee_id' => $employee_id,
                                ])
                                ->select('Late_Amount', 'Undertime_Amount', 'Absent_Amount')
                                ->first();

                            if ($firstHalfTime) {
                                $fh_late = is_numeric($firstHalfTime->Late_Amount ?? null) ? (float) $firstHalfTime->Late_Amount : 0.0;
                                $fh_ut = is_numeric($firstHalfTime->Undertime_Amount ?? null) ? (float) $firstHalfTime->Undertime_Amount : 0.0;
                                $fh_absent = is_numeric($firstHalfTime->Absent_Amount ?? null) ? (float) $firstHalfTime->Absent_Amount : 0.0;

                                $fh_late = ($fh_late > 0 && is_finite($fh_late)) ? $fh_late : 0.0;
                                $fh_ut = ($fh_ut > 0 && is_finite($fh_ut)) ? $fh_ut : 0.0;
                                $fh_absent = ($fh_absent > 0 && is_finite($fh_absent)) ? $fh_absent : 0.0;

                                $philhealth_tardiness_total = $fh_late + $fh_ut + $fh_absent;
                            }
                        }

                        // Cap at monthly basic to avoid negative bases from bad time data
                        if ($philhealth_tardiness_total > $basic_pay && $basic_pay > 0) {
                            $philhealth_tardiness_total = (float) $basic_pay;
                        }

                        $adjusted_salary = $monthly_salary - $philhealth_tardiness_total;

                        if ($adjusted_salary <= 0 || $adjusted_salary <= $ph_income_floor) {
                            $philhealth_full = 0;
                        } elseif ($adjusted_salary >= $ph_income_ceilling) {
                            // Per spec: salary above ceiling uses fixed employee share (fix_rate / 2 = 2,500)
                            $philhealth_full = $ph_fix_rate / 2;
                        } else {
                            $philhealth_full = ($adjusted_salary * $ph_multiplier) / 2;
                        }

                        if ($include_philhealth == 0) {
                            $philhealth_amount = 0;
                            $philhealth_amount_gs = 0;
                        } elseif (!$is_first_or_second_half || $philhealth_divisor === null || $philhealth_divisor === 0 || $philhealth_divisor === 1) {
                            $philhealth_amount = $philhealth_full;
                            $philhealth_amount_gs = $philhealth_full;
                        } else {
                            $philhealth_amount = $philhealth_full / (float) $philhealth_divisor;
                            $philhealth_amount_gs = $philhealth_full;
                        }
                    }
                    //     if ($monthly_salary >= $ph_income_ceilling) {
                    //         $philhealth_full = $ph_fix_rate; // monthly cap; divisor logic below splits per period
                    //     } elseif ($monthly_salary <= $ph_income_floor) {
                    //         $philhealth_full = 0;
                    //     } else {
                    //         // Basic Salary (monthly) * PhilHealth multiplier = monthly contribution; divisor splits per period
                    //         $philhealth_full = $monthly_salary * $ph_multiplier / 2;
                    //     }

                    //     if ($include_philhealth == 0) {
                    //         $philhealth_amount = 0;
                    //         $philhealth_amount_gs = 0;
                    //     } elseif (!$is_first_or_second_half || $philhealth_divisor === null || $philhealth_divisor === 0 || $philhealth_divisor === 1) {
                    //         $philhealth_amount = $philhealth_full;
                    //         $philhealth_amount_gs = $philhealth_full;
                    //     } else {
                    //         $philhealth_amount = $philhealth_full / (float) $philhealth_divisor;
                    //         $philhealth_amount_gs = $philhealth_full;
                    //     }
                    // }
                    } // end legacy GSIS/PhilHealth

                    if ($gsis_amount > 0) {
                        $gsis = $gsis_amount;
                    } else {
                        $gsis = 0;
                    }

                    if ($sss_amount > 0) {
                        $sss = $include_sss == 0 ? 0 : $sss_amount;
                    } else {
                        $sss = 0;
                    }

                    if ($pagibig_amount > 0) {
                        if ($include_pagibig == 0) {
                            $pagibig = 0;
                        } elseif ($is_first_or_second_half && ($pagibig_divisor == 1 || $pagibig_divisor === null)) {
                            $pagibig = $pagibig_amount;
                        } else {
                            $pagibig = $is_first_or_second_half ? ($pagibig_amount / 2) : $pagibig_amount;
                        }
                    } else {
                        $pagibig = 0;
                    }


                    if ($philhealth_amount > 0) {
                        $philhealth = $philhealth_amount;
                    } else {
                        $philhealth = 0;
                    }



                    //divisor for tax is per-period
                    if ($include_tax == 0) {
                        $tax = 0;
                    } elseif ($use_contribution_reconciliation) {
                        $tax = (float) $tax_per_period;
                    } elseif ($tax_amount > 0) {
                        if ($tax_from_adjustment) {
                            $tax = (float) $tax_amount;
                        } elseif ($is_first_or_second_half && ($tax_divisor == 1 || $tax_divisor === null)) {
                            // Tax only on this period in the schedule → deduct full monthly tax in this run
                            $tax = (float) $tax_amount;
                        } else {
                            $tax = $tax_amount / $periods_per_month;
                        }
                    } else {
                        $tax = 0;
                    }

                    $holiday_amount = isset($timeSummary->Holiday_Pay) ? (float)$timeSummary->Holiday_Pay : 0;
                    $ot_amount = isset($timeSummary->OT_Pay) ? (float)$timeSummary->OT_Pay : 0;
                    $nd_amount = isset($timeSummary->ND_Pay) ? (float)$timeSummary->ND_Pay : 0;

                    if (!$includeHolidayOption) {
                        $holiday_amount = 0;
                    }

                    if (!$includeOvertimeOption) {
                        $ot_amount = 0;
                    }

                    // OT/Holiday adjustment from time_data_summary (Adjustment_Amount_OT_Holiday)
                    $adjustment_amount_ot_holiday = ($timeSummary && isset($timeSummary->Adjustment_Amount_OT_Holiday) && is_numeric($timeSummary->Adjustment_Amount_OT_Holiday))
                        ? (float) $timeSummary->Adjustment_Amount_OT_Holiday
                        : 0;

                    $incomes = DB::table('payroll_incomes')
                        ->select(
                            DB::raw("sum(amount) as income")
                        )
                        ->where(['payroll_period_id' => $id, 'employee_id' => $employee_id])
                        ->groupBy('employee_id')
                        ->get();

                    $deductions = DB::table('payroll_deductions')
                        ->select(
                            DB::raw("sum(amount) as deduction")
                        )
                        ->where(['payroll_period_id' => $id, 'employee_id' => $employee_id])
                        ->groupBy('employee_id')
                        ->get();

                    $total_income = $incomes->isEmpty() ? 0 : $incomes[0]->income;
                    $total_deduction = $deductions->isEmpty() ? 0 : $deductions[0]->deduction;
                    $other_deductions_total = (float) ($deductions->isEmpty() ? 0 : $deductions[0]->deduction);

                    $taxable_income_for_gross = isset($total_taxable_income)
                        ? (float) $total_taxable_income
                        : 0.0;

                    // Guard against negative values or cases where rounding causes taxable > total
                    if ($taxable_income_for_gross < 0) {
                        $taxable_income_for_gross = 0.0;
                    }
                    if ($taxable_income_for_gross > $total_income) {
                        $taxable_income_for_gross = (float) $total_income;
                    }

                    $non_taxable_income = (float) ($total_income - $taxable_income_for_gross);

                    $original_netpay = 0;
                    $net_pay = 0;


                    // // OLD GROSS COMPUTATION -chad 02/25/26
                    // // Gross amount: prefer time_data_summary.Gross_Pay for time-based portion, then add taxable incomes
                    // $time_based_gross = null;
                    // if ($timeSummary && isset($timeSummary->Gross_Pay) && is_numeric($timeSummary->Gross_Pay)) {
                    //     $time_based_gross = (float) $timeSummary->Gross_Pay;
                    // }
                    // if ($time_based_gross !== null) {
                    //     $gross_amount = $time_based_gross + $taxable_income_for_gross;
                    // } else {
                    //     // Fallback: salary + holiday + TAXABLE incomes + OT + ND
                    //     $gross_amount = ($basic_pay + $holiday_amount + $taxable_income_for_gross + $ot_amount + $nd_amount);
                    // }

                    // // For net pay, the employee should still receive non-taxable incomes.
                    // // So we add non-taxable income back on top of the taxable gross before subtracting deductions.
                    // $gross_for_net = $gross_amount + $non_taxable_income + $adjustment_amount_ot_holiday;

                    $step_increment_record = DB::table('step_increments')
                        ->where('employee_id', $employee_id)
                        ->where('is_approved', 1)
                        ->where('is_disapproved', 0)
                        ->where('is_has_reflected', 0)
                        ->where('month_id', $carry_month)
                        ->where('year_id', $carry_year)
                        ->first();


                    $step_inc_amount = $step_increment_record ? (float) ($step_increment_record->new_salary - $step_increment_record->current_salary) : 0;

                    // Salary Adjustment: fetch the manual salary adjustment for this period

                    $salary_adj_amount = DB::table('payroll_salary_adjustments')
                        ->where('employee_id', $employee_id)
                        ->where('payroll_period_id', $id)
                        ->value('amount') ?? 0;
                    $salary_adj_amount = (float) $salary_adj_amount;

                    // $gross_amount = $basic_pay + $ot_amount + $holiday_amount + $nd_amount + $total_income + $step_inc_amount + $salary_adj_amount;

                    // Per-period gross: half-month basic for 1st/2nd half runs, full month only for true monthly period.
                    $gross_amount = $period_basic_pay + $total_income + $step_inc_amount;

                    $gross_for_net = $gross_amount + $adjustment_amount_ot_holiday;
                    // $gross_for_net = $gross_amount;


                    // Period deduction (government contributions + manual deductions from payroll_deductions e.g. loans
                    // + preceding-period adjustment sourced from time_data_summary).
                    $period_total_deduction = $gsis + $sss + $philhealth + $pagibig + $tax + $preceding_period_adjustment + $other_deductions_total;
                    // LEGACY_MONTHLY_TOTAL_DEDUCTION:
                    // $monthly_total_deduction = $gsis + $sss + $philhealth + $pagibig + $tax + $preceding_period_adjustment + $other_deductions_total;

                    // BASE NET PAY
                    $base_net_pay = $gross_for_net - $period_total_deduction;

                    $original_netpay = max($base_net_pay, 0);

                    // Start from computed net (without manual salary adjustment).
                    // Manual salary adjustments are applied AFTER net computation so they do not affect deductions/tax.
                    $net_pay = $base_net_pay;


                    $pending_amount_to_pay = 0;

                    $total_deduction = $period_total_deduction;


                    // if ($month_frequency == 0) {
                    //     $net_pay = $net_pay;
                    // } else {
                    //     $net_pay = ($net_pay / $month_frequency);
                    // }

                    // Semi-monthly computation (period-based):
                    // Net pay per half = (basic salary / 2) + period incomes/adjustments
                    //                    - period deductions (includes preceding adjustment)
                    //                    - period tardiness (late/undertime/absent).
                    //
                    // This aligns the selected half's net pay with the actual selected half's values.
                    //
                    // LEGACY_SEMIMONTHLY_SPLIT_WITH_CARRYOVER:
                    // Previous behavior split base net pay into halves with centavo carry:
                    // 1st half: floor((base_net_pay / 2) - tardiness)
                    // 2nd half: ((base_net_pay / 2) - tardiness) + carryover
                    //
                    // --- START LEGACY CODE (commented for easy rollback) ---
                    // if ($is_first_half_period) {
                    //     $tardiness_total = $late_amount + $ut_amount + $effectiveAbsentAmount;
                    //     $first_half_raw = ($base_net_pay / 2) - $tardiness_total;
                    //     $first_half_raw = max((float) $first_half_raw, 0.0);
                    //
                    //     $first_half_whole = (int) floor($first_half_raw);
                    //     $carry_amount = round($first_half_raw - (float) $first_half_whole, 4);
                    //     $carry_amount = max($carry_amount, 0.0);
                    //
                    //     DB::table('payroll_net_pay_carryovers')->updateOrInsert(
                    //         ['employee_id' => $employee_id, 'year' => $carry_year, 'month' => $carry_month],
                    //         [
                    //             'carried_amount' => $carry_amount,
                    //             'updated_at' => now(),
                    //             'created_at' => now(),
                    //         ]
                    //     );
                    //
                    //     $net_pay = (float) $first_half_whole;
                    //
                    // } elseif ($is_second_half_period) {
                    //     $first_half_base_net_pay = null;
                    //
                    //     if ($first_half_period_id) {
                    //         $first_half_summary = DB::table('payroll_summaries')
                    //             ->where('payroll_period_id', $first_half_period_id)
                    //             ->where('employee_id', $employee_id)
                    //             ->value('base_net_pay');
                    //
                    //         if ($first_half_summary !== null) {
                    //             $first_half_base_net_pay = (float) $first_half_summary;
                    //         }
                    //     }
                    //
                    //     $base_net_pay_for_second = $first_half_base_net_pay !== null
                    //         ? $first_half_base_net_pay
                    //         : $base_net_pay;
                    //
                    //     $tardiness_total_second = $late_amount + $ut_amount + $effectiveAbsentAmount;
                    //
                    //     $carryover = DB::table('payroll_net_pay_carryovers')
                    //         ->where('employee_id', $employee_id)
                    //         ->where('year', $carry_year)
                    //         ->where('month', $carry_month)
                    //         ->value('carried_amount');
                    //     $carryover = $carryover !== null ? (float) $carryover : 0.0;
                    //
                    //     $second_half_raw = ($base_net_pay_for_second / 2)
                    //         - $tardiness_total_second
                    //         + $carryover;
                    //
                    //     $net_pay = max((float) round($second_half_raw, 2), 0.0);
                    // } else {
                    //     $net_pay = max($base_net_pay - $late_amount - $ut_amount - $effectiveAbsentAmount, 0);
                    //     $net_pay = (float) round($net_pay, 2);
                    // }
                    // --- END LEGACY CODE ---
                    $net_pay = (float) round($net_pay, 2); // base_net_pay

                    if ($is_first_half_period) {
                        $tardiness_total = $late_amount + $ut_amount + $effectiveAbsentAmount;
                        $period_half_gross = $period_basic_pay + $total_income + $step_inc_amount;
                        $first_half_raw = (float) round($period_half_gross - $period_total_deduction - $tardiness_total, 2);
                        $first_half_raw = max($first_half_raw, 0.0);

                        // Keep first-half release as whole pesos; carry centavos to second half.
                        $first_half_whole = (int) floor($first_half_raw);
                        $carry_amount = round($first_half_raw - (float) $first_half_whole, 4);
                        $carry_amount = max($carry_amount, 0.0);

                        DB::table('payroll_net_pay_carryovers')->updateOrInsert(
                            ['employee_id' => $employee_id, 'year' => $carry_year, 'month' => $carry_month],
                            [
                                'carried_amount' => $carry_amount,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );

                        $net_pay = (float) $first_half_whole;
                    } elseif ($is_second_half_period) {
                        $tardiness_total_second = $late_amount + $ut_amount + $effectiveAbsentAmount;
                        $lwop_for_net = ((float) $lwop_amount > 0) ? (float) $lwop_amount : 0.0;
                        $period_half_gross = $period_basic_pay + $total_income + $step_inc_amount;
                        $carryover = DB::table('payroll_net_pay_carryovers')
                            ->where('employee_id', $employee_id)
                            ->where('year', $carry_year)
                            ->where('month', $carry_month)
                            ->value('carried_amount');
                        $carryover = $carryover !== null ? (float) $carryover : 0.0;

                        $second_half_raw = (float) round(
                            ($period_half_gross - $period_total_deduction - $tardiness_total_second - $lwop_for_net) + $carryover,
                            2
                        );
                        $net_pay = max($second_half_raw, 0.0);
                    } else {
                        // Monthly — no split; deduct tardiness from full month net
                        $net_pay = max($base_net_pay - $late_amount - $ut_amount - $effectiveAbsentAmount, 0);
                        $net_pay = (float) round($net_pay, 2);
                    }

                    // Apply manual salary adjustment to NET pay (post-deduction).
                    // This ensures gov't contributions/tax are computed from base payroll, not from manual adjustments.
                    $net_pay = (float) round($net_pay + $salary_adj_amount, 2);
                    $net_pay = max($net_pay, 0.0);

                    // Cast to float so SQL Server receives numeric type (avoids nvarchar overflow on decimal columns)
                    $payroll_data = array(
                        'payroll_period_id' => (int) $id,
                        'employee_id' => (int) $employee_id,
                        'salary' => (float) round($basic_pay, 2),
                        'gsis' => (float) round($gsis, 2),
                        'sss' => (float) round($sss, 2),
                        'pagibig' => (float) round($pagibig, 2),
                        'philhealth' => (float) round($philhealth, 2),
                        // 'philhealth_gs' => $philhealth_amount_gs,
                        'tax' => (float) round($tax, 2),
                        'late_amount' => (float) round($late_amount, 2),
                        'ut_amount' => (float) round($ut_amount, 2),
                        'absent_amount' => (float) round($effectiveAbsentAmount, 2),
                        'holiday_amount' => (float) round($holiday_amount, 2),
                        'ot_amount' => (float) round($ot_amount, 2),
                        'nd_amount' => (float) round($nd_amount, 2),
                        'total_income' => (float) round($total_income, 2),
                        'total_deduction' => (float) round($total_deduction, 2),
                        'gross_amount' => (float) round($gross_amount, 2),
                        'net_pay' => (float) round($net_pay, 2),
                        'original_netpay' => (float) round($original_netpay, 2),
                        'base_net_pay' => (float) round($base_net_pay, 2),
                        'pending_amount_payment' => (float) round($pending_amount_to_pay, 2),
                        'lwop_amount' => (float) round($lwop_amount, 2),
                        'step_inc_amount' => (float) round($step_inc_amount, 2),
                        'salary_adj_amount' => (float) round($salary_adj_amount, 2)
                    );

                    DB::table('payroll_summaries')->updateOrInsert(['payroll_period_id' => $id, 'employee_id' => $employee_id], $payroll_data);

                    if ($step_increment_record) {
                        DB::table('step_increments')
                            ->where('id', $step_increment_record->id)
                            ->update(['is_has_reflected' => 1]);
                    }
            }

            // Save Audit Trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Process',
                'activity' => 'Process',
                'description' => 'Process Payroll informations.',
            );

            Audit::create($data_audit);

            $cacheVersion = $this->invalidatePayrollProcessCache((int) $id);

            return $this->successResponse(
                ['payroll_period_id' => (int) $id, 'cache_version' => $cacheVersion],
                'You have successfully process payroll!'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process payroll: ' . $e->getMessage());
        }
    }

    public function summary($id)
    {
        try {
            $id = (int) $id;
            $cacheKey = $this->payrollProcessCacheKey('summary', $id);
            $skipCache = request()->boolean('refresh');

            if (!$skipCache) {
                $cached = Cache::get($cacheKey);
                if (is_array($cached)) {
                    return $this->successResponse($cached, 'Payroll process summary data loaded successfully');
                }
            }

            $app_key = env("APP_KEY", "");

            $payrolls = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('divisions as c', 'c.id', '=', 'b.division_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('payroll_salary_adjustments as psa', function ($join) use ($id) {
                    $join->on('psa.employee_id', '=', 'a.employee_id')
                        ->where('psa.payroll_period_id', '=', $id);
                })
                ->leftJoin('time_data_summary as tds', function ($join) use ($id) {
                    $join->on('tds.employee_id', '=', 'a.employee_id')
                        ->where('tds.payroll_period_id', '=', $id);
                })
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'b.employment_type_id',
                    'b.division_id',
                    'c.name as department',
                    'c.name as division_name',
                    'd.name as position',
                    'a.salary',
                    'a.ot_amount as ot_pay',
                    'a.nd_amount as nd_pay',
                    'a.holiday_amount as holiday_pay',
                    'a.total_income',
                    'a.step_inc_amount',
                    'a.salary_adj_amount as salary_adj_amount_saved',
                    DB::raw('ISNULL(psa.amount, 0) as salary_adj_amount'),
                    'a.gross_amount',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.lwop_amount',
                    'a.gsis',
                    DB::raw("ISNULL(a.sss, b.sss_amount) as sss"),
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.base_net_pay',
                    'a.net_pay',
                    'a.original_netpay',
                    'a.pending_amount_payment',
                    'b.is_hold',
                    'b.hold_remarks',
                    DB::raw('ISNULL(tds.Adjustment_Amount, 0) as preceding_period_adjustment'),
                    DB::raw('ISNULL(tds.Adjustment_Amount_OT_Holiday, 0) as adjustment_amount_ot_holiday')
                )
                ->where('a.payroll_period_id', $id)
                ->orderBy('b.first_name', 'asc')
                ->get();

            // Ensure payroll summary reflects the latest saved salary adjustments even without re-processing.
            // Net pay stored in payroll_summaries may include the "saved" adjustment at process time, so:
            // displayed_net = stored_net - saved_adj + latest_adj
            foreach ($payrolls as $row) {
                $savedAdj = isset($row->salary_adj_amount_saved) ? (float) $row->salary_adj_amount_saved : 0.0;
                $latestAdj = isset($row->salary_adj_amount) ? (float) $row->salary_adj_amount : 0.0;
                $storedNet = isset($row->net_pay) ? (float) $row->net_pay : 0.0;

                $row->net_pay = (float) round(($storedNet - $savedAdj) + $latestAdj, 2);
                if ($row->net_pay < 0) {
                    $row->net_pay = 0.0;
                }
            }

            // UI expects split fields for "First Half Net Pay" and "Second Half Net Pay".
            // For Monthly (single-row model), we split the full-month net pay into two halves
            // using floor + centavo carry (fractional part) to match semi-monthly behavior.
            // For semi-monthly periods, we show the current period's net pay in the appropriate half column.
            $periodCutoff = DB::table('payroll_periods as pp')
                ->join('payroll_cutoffs as pc', 'pp.payroll_cutoff_id', '=', 'pc.id')
                ->select('pc.name as cutoff_name')
                ->where('pp.id', $id)
                ->first();

            $cutoffNameNormalized = strtolower(trim((string)($periodCutoff?->cutoff_name ?? '')));
            $isMonthlyPeriod = str_contains($cutoffNameNormalized, 'monthly');
            $isSecondHalfPeriod = !$isMonthlyPeriod && (
                str_contains($cutoffNameNormalized, '2nd') ||
                str_contains($cutoffNameNormalized, 'second')
            );

            // Attach the sum of manual deductions (e.g. loans) from payroll_deductions per employee.
            // This is returned as `other_deductions` so the frontend can display it directly
            // instead of computing it by fragile subtraction.
            if ($payrolls->isNotEmpty()) {
                $employeeIdsForOtherDed = $payrolls->pluck('employee_id')->unique()->values()->all();
                $otherDeductionsMap = DB::table('payroll_deductions')
                    ->select('employee_id', DB::raw('SUM(ISNULL(amount, 0)) as other_deductions'))
                    ->where('payroll_period_id', $id)
                    ->whereIn('employee_id', $employeeIdsForOtherDed)
                    ->groupBy('employee_id')
                    ->get()
                    ->keyBy('employee_id');

                $payrolls = $payrolls->map(function ($row) use ($otherDeductionsMap) {
                    $empId = (int) ($row->employee_id ?? 0);
                    $otherDed = $otherDeductionsMap->get($empId);
                    $row->other_deductions = $otherDed ? (float) $otherDed->other_deductions : 0.0;
                    return $row;
                });
            }

            // Attach display-only per-half govt contribution values derived from the month total.
            // This avoids confusion where the 1st half shows the full-month deductions and the 2nd half shows 0,
            // while the net pay is split into 2 halves.
            if ($payrolls->isNotEmpty()) {
                $employeeIds = $payrolls->pluck('employee_id')->unique()->values()->all();

                $periodInfoForDisplay = DB::table('payroll_periods')
                    ->select('id', 'payroll_interval_id', 'attendance_start_date', 'release_date')
                    ->where('id', $id)
                    ->first();

                if ($periodInfoForDisplay && !empty($employeeIds)) {
                    // Use release_date as month basis to match UI labels (e.g., "March 2026").
                    // attendance_start_date can fall in the previous month for semi-monthly cutoffs.
                    $dateBasis = $periodInfoForDisplay->release_date ?: $periodInfoForDisplay->attendance_start_date;
                    $year = $dateBasis ? (int) date('Y', strtotime($dateBasis)) : null;
                    $month = $dateBasis ? (int) date('n', strtotime($dateBasis)) : null;

                    $monthPeriodIds = [$id];
                    if (!empty($year) && !empty($month)) {
                        // All payroll periods for the same interval & month (covers 1st/2nd half)
                        $monthPeriodIds = DB::table('payroll_periods')
                            ->where('payroll_interval_id', $periodInfoForDisplay->payroll_interval_id)
                            ->whereYear('release_date', $year)
                            ->whereMonth('release_date', $month)
                            ->where('active', true)
                            ->pluck('id')
                            ->toArray();
                    }

                    if (empty($monthPeriodIds)) {
                        $monthPeriodIds = [$id];
                    }

                    // Single-row Monthly model => only 1 payroll_period row per month.
                    // Semi-monthly => 2 rows per month. Use real monthPeriodIds count.
                    $periodsPerMonth = max((int) count($monthPeriodIds), 1);

                    $govMonthly = DB::table('payroll_summaries as ps')
                        ->whereIn('ps.payroll_period_id', $monthPeriodIds)
                        ->whereIn('ps.employee_id', $employeeIds)
                        ->select(
                            'ps.employee_id',
                            DB::raw('SUM(ISNULL(ps.gsis, 0)) as gsis_monthly'),
                            DB::raw('SUM(ISNULL(ps.sss, 0)) as sss_monthly'),
                            DB::raw('SUM(ISNULL(ps.pagibig, 0)) as pagibig_monthly'),
                            DB::raw('SUM(ISNULL(ps.philhealth, 0)) as philhealth_monthly'),
                            DB::raw('SUM(ISNULL(ps.tax, 0)) as tax_monthly')
                        )
                        ->groupBy('ps.employee_id')
                        ->get()
                        ->keyBy('employee_id');

                    $payrolls = $payrolls->map(function ($row) use ($govMonthly, $periodsPerMonth) {
                        $empId = (int) ($row->employee_id ?? 0);
                        $monthly = $govMonthly->get($empId);

                        $gsisMonthly = $monthly ? (float) ($monthly->gsis_monthly ?? 0) : 0.0;
                        $sssMonthly = $monthly ? (float) ($monthly->sss_monthly ?? 0) : 0.0;
                        $pagibigMonthly = $monthly ? (float) ($monthly->pagibig_monthly ?? 0) : 0.0;
                        $philhealthMonthly = $monthly ? (float) ($monthly->philhealth_monthly ?? 0) : 0.0;
                        $taxMonthly = $monthly ? (float) ($monthly->tax_monthly ?? 0) : 0.0;

                        $row->gsis_monthly = $gsisMonthly;
                        $row->sss_monthly = $sssMonthly;
                        $row->pagibig_monthly = $pagibigMonthly;
                        $row->philhealth_monthly = $philhealthMonthly;
                        $row->tax_monthly = $taxMonthly;

                        $divisor = max((int) $periodsPerMonth, 1);
                        $row->gsis_display = $gsisMonthly / $divisor;
                        $row->sss_display = $sssMonthly / $divisor;
                        $row->pagibig_display = $pagibigMonthly / $divisor;
                        $row->philhealth_display = $philhealthMonthly / $divisor;
                        $row->tax_display = $taxMonthly / $divisor;

                        // Display-only total deductions: keep "other" deductions as-is for this period,
                        // but replace per-period govt contribution/tax with the per-half display amounts.
                        $gsisActual = (float) ($row->gsis ?? 0);
                        $sssActual = (float) ($row->sss ?? 0);
                        $pagibigActual = (float) ($row->pagibig ?? 0);
                        $philhealthActual = (float) ($row->philhealth ?? 0);
                        $taxActual = (float) ($row->tax ?? 0);

                        $totalDedActual = (float) ($row->total_deduction ?? 0);
                        $govtActualSum = $gsisActual + $sssActual + $pagibigActual + $philhealthActual + $taxActual;
                        $govtDisplaySum = (float) ($row->gsis_display + $row->sss_display + $row->pagibig_display + $row->philhealth_display + $row->tax_display);
                        $row->total_deduction_display = max(0.0, ($totalDedActual - $govtActualSum) + $govtDisplaySum);

                        return $row;
                    });
                }
            }

            // Attach PERA (monthly) per employee so that both 1st- and 2nd-half views
            // can consistently display the full-month PERA (no split display) when
            // PERA is active in payroll item schedule for the employee's employment type.
            if ($payrolls->isNotEmpty()) {
                $employeeIds = $payrolls->pluck('employee_id')->unique()->values()->all();
                $employmentTypeIds = $payrolls->pluck('employment_type_id')->filter()->unique()->values()->all();

                $periodInfo = DB::table('payroll_periods')
                    ->select('id', 'payroll_interval_id', 'payroll_cutoff_id', 'release_date')
                    ->where('id', $id)
                    ->first();

                if ($periodInfo && !empty($employeeIds)) {
                    $year = (int) date('Y', strtotime($periodInfo->release_date));
                    $month = (int) date('n', strtotime($periodInfo->release_date));

                    // All payroll periods for the same interval & month (covers 1st/2nd half)
                    $monthPeriodIds = DB::table('payroll_periods')
                        ->where('payroll_interval_id', $periodInfo->payroll_interval_id)
                        ->whereYear('release_date', $year)
                        ->whereMonth('release_date', $month)
                        ->pluck('id')
                        ->toArray();

                    if (empty($monthPeriodIds)) {
                        $monthPeriodIds = [$id];
                    }

                    $representativePeriod = DB::table('payroll_periods')
                        ->where('payroll_interval_id', $periodInfo->payroll_interval_id)
                        ->where('payroll_cutoff_id', $periodInfo->payroll_cutoff_id)
                        ->where('active', true)
                        ->orderBy('id', 'asc')
                        ->first();
                    $payrollPeriodTypeId = $representativePeriod ? $representativePeriod->id : $periodInfo->id;

                    // Identify PERA income IDs (any income whose name contains 'PERA')
                    $peraIncomeIds = DB::table('incomes')
                        ->where('name', 'LIKE', '%PERA%')
                        ->pluck('id')
                        ->toArray();

                    if (!empty($peraIncomeIds)) {
                        // PERA amount encoded in current selected payroll period.
                        $peraCurrentByEmployee = DB::table('payroll_incomes as pi')
                            ->where('pi.payroll_period_id', $id)
                            ->whereIn('pi.employee_id', $employeeIds)
                            ->whereIn('pi.income_id', $peraIncomeIds)
                            ->select('pi.employee_id', DB::raw('SUM(pi.amount) as period_pera'))
                            ->groupBy('pi.employee_id')
                            ->pluck('period_pera', 'employee_id');

                        $peraByEmployee = DB::table('payroll_incomes as pi')
                            ->whereIn('pi.payroll_period_id', $monthPeriodIds)
                            ->whereIn('pi.employee_id', $employeeIds)
                            ->whereIn('pi.income_id', $peraIncomeIds)
                            ->select('pi.employee_id', DB::raw('SUM(pi.amount) as total_pera'))
                            ->groupBy('pi.employee_id')
                            ->pluck('total_pera', 'employee_id');

                        $peraActiveByEmploymentType = collect();
                        if (!empty($employmentTypeIds)) {
                            $peraActiveByEmploymentType = DB::table('payroll_item_schedule_headers as h')
                                ->join('payroll_item_schedule_details as d', 'h.id', '=', 'd.payroll_item_schedule_header_id')
                                ->where('h.payroll_interval_type_id', $periodInfo->payroll_interval_id)
                                ->where('h.payroll_period_type_id', $payrollPeriodTypeId)
                                ->whereIn('h.employment_type_id', $employmentTypeIds)
                                ->whereIn('d.income_id', $peraIncomeIds)
                                ->where('d.active', true)
                                ->select('h.employment_type_id', DB::raw('COUNT(*) as cnt'))
                                ->groupBy('h.employment_type_id')
                                ->pluck('cnt', 'h.employment_type_id');
                        }

                        $payrolls = $payrolls->map(function ($row) use ($peraByEmployee, $peraCurrentByEmployee, $peraActiveByEmploymentType) {
                            $employmentTypeId = (int) ($row->employment_type_id ?? 0);
                            $isPeraActive = $employmentTypeId > 0 && isset($peraActiveByEmploymentType[$employmentTypeId]) && (int) $peraActiveByEmploymentType[$employmentTypeId] > 0;
                            $displayPera = $isPeraActive && isset($peraByEmployee[$row->employee_id])
                                ? (float) $peraByEmployee[$row->employee_id]
                                : 0.0;
                            $periodPera = isset($peraCurrentByEmployee[$row->employee_id])
                                ? (float) $peraCurrentByEmployee[$row->employee_id]
                                : 0.0;

                            // Disable PERA split in summary display by replacing current-period PERA
                            // with full displayed PERA (when active in schedule).
                            $row->pera_monthly = $displayPera;
                            $row->total_income_display = max(0.0, (float) ($row->total_income ?? 0) - $periodPera + $displayPera);
                            return $row;
                        });
                    } else {
                        // No PERA income configured; default to 0 for all employees.
                        $payrolls = $payrolls->map(function ($row) {
                            $row->pera_monthly = 0.0;
                            $row->total_income_display = (float) ($row->total_income ?? 0);
                            return $row;
                        });
                    }
                } else {
                    // Fallback: ensure field exists even when period info is missing.
                    $payrolls = $payrolls->map(function ($row) {
                        $row->pera_monthly = 0.0;
                        $row->total_income_display = (float) ($row->total_income ?? 0);
                        return $row;
                    });
                }
            }

            // Attach net-pay split fields expected by the frontend.
            if ($payrolls->isNotEmpty()) {
                $payrolls = $payrolls->map(function ($row) use ($isMonthlyPeriod, $isSecondHalfPeriod) {
                    $netPay = (float)($row->net_pay ?? 0);

                    if ($isMonthlyPeriod) {
                        // Split full-month net pay into two halves:
                        // - first half = floor(net/2)
                        // - second half = net - first half (keeps centavo carry)
                        $halfRaw = $netPay / 2;
                        $firstHalfWhole = (float) floor($halfRaw);
                        $secondHalf = max((float) round($netPay - $firstHalfWhole, 2), 0.0);

                        $row->first_half_net_pay = (float) round($firstHalfWhole, 2);
                        $row->second_half_net_pay = (float) $secondHalf;
                    } else {
                        $row->first_half_net_pay = $isSecondHalfPeriod ? 0.0 : $netPay;
                        $row->second_half_net_pay = $isSecondHalfPeriod ? $netPay : 0.0;
                    }

                    return $row;
                });
            }

            $payrolls_less_netpay = DB::table('payroll_summaries as a')
                ->where('a.payroll_period_id', $id)
                ->where('a.net_pay', '<', 5000)
                ->get();

            $payrolls_adjust_late = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftjoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('branches as g', 'b.branch_id', '=', 'g.id')
                ->join('pending_deductions as e', function ($join) {
                    $join->on('e.payroll_period_id', '=', 'a.payroll_period_id');
                    $join->on('e.employee_id', '=', 'a.employee_id');
                })
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(b.first_name,' ',b.last_name))
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'g.name as branch',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.net_pay',
                    'a.original_netpay',
                    db::raw("'Late' as deduction"),
                    'e.amount'
                )
                ->where(['a.payroll_period_id' => $id, 'is_late' => true])
                ->where('a.original_netpay', '<', 5000);

            $payrolls_adjust_undertime = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftjoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('branches as g', 'b.branch_id', '=', 'g.id')
                ->join('pending_deductions as e', function ($join) {
                    $join->on('e.payroll_period_id', '=', 'a.payroll_period_id');
                    $join->on('e.employee_id', '=', 'a.employee_id');
                })
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(b.first_name,' ',b.last_name))
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'g.name as branch',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.net_pay',
                    'a.original_netpay',
                    db::raw("'Undertime' as deduction"),
                    'e.amount'
                )
                ->where(['a.payroll_period_id' => $id, 'is_undertime' => true])
                ->where('a.original_netpay', '<', 5000);

            $payrolls_adjust = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftjoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('branches as g', 'b.branch_id', '=', 'g.id')
                ->join('pending_deductions as e', function ($join) {
                    $join->on('e.payroll_period_id', '=', 'a.payroll_period_id');
                    $join->on('e.employee_id', '=', 'a.employee_id');
                })
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(b.first_name,' ',b.last_name))
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'g.name as branch',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.net_pay',
                    'a.original_netpay',
                    db::raw("'Absent' as deduction"),
                    'e.amount'
                )
                ->unionAll($payrolls_adjust_late)
                ->unionAll($payrolls_adjust_undertime)
                ->where(['a.payroll_period_id' => $id, 'is_absent' => true])
                ->where('a.original_netpay', '<', 5000)
                ->orderBy('name', 'asc')
                ->get();

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(c.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name")
                )
                ->where(['a.id' => $id])
                ->orderBy('a.release_date', 'desc')
                ->get();

            $payroll_period = $data[0]->name;

            // leave Earned details
            $leave_earned_details = DB::table('employee_leave_earned as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->select(
                    'a.employee_id',
                    'b.employee_no',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(b.first_name,' ',b.last_name))
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'c.name as position',
                    db::raw("case when isnull(a.absent,0) <= 0 then 0 else isnull(a.absent,0) end as days_present"),
                    'a.vl_earned',
                    'a.sl_earned'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            $employee_for_tax_adjustments_data = DB::table('employees as a')
                ->join('payroll_summaries as b', 'a.id', '=', 'b.employee_id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            UPPER(CONCAT(a.first_name,' ',a.last_name))
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                        END as name"),
                    'b.tax'
                )
                ->where('b.payroll_period_id', $id);

            $employee_for_tax_adjustments = DB::table('employees as a')
                ->join('payroll_tax_adjustments as b', 'a.id', '=', 'b.employee_id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            UPPER(CONCAT(a.first_name,' ',a.last_name))
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                        END as name"),
                    'b.tax_amount as tax'
                )
                ->where('b.payroll_period_id', $id)
                ->union($employee_for_tax_adjustments_data)
                ->orderBy('name', 'asc')
                ->get();



            // Employees assigned to this payroll period (matches process() roster)
            $eligibleEmployeeCount = $this->countEligiblePayrollPeriodEmployees($id);

            // validate if payroll was processed. if yes, then validate if payroll needs reprocessing.

            $is_processed = $payrolls->count() > 0;

            // validate if payroll needs reprocessing.

            $needs_reprocess = false;
            if ($is_processed) {
                $has_tax_adjustments = DB::table('payroll_tax_adjustments')
                    ->where('payroll_period_id', $id)
                    ->count() > 0;

                $has_pending_deductions = DB::table('pending_deductions')
                    ->where('payroll_period_id', $id)
                    ->count() > 0;

                $rosterMismatch = $eligibleEmployeeCount !== $payrolls->count();

                $needs_reprocess = $has_tax_adjustments || $has_pending_deductions || $rosterMismatch;
            }

            $divisions = $this->getActiveDivisions();

            $payload = [
                'id' => $id,
                'payroll_period' => $payroll_period,
                'payrolls' => $payrolls,
                'divisions' => $divisions,
                'eligible_employee_count' => $eligibleEmployeeCount,
                'time_data_employee_count' => $eligibleEmployeeCount,
                'data' => $data,
                'payrolls_adjust' => $payrolls_adjust,
                'payrolls_less_netpay' => $payrolls_less_netpay,
                'leave_earned_details' => $leave_earned_details,
                'employee_for_tax_adjustments' => $employee_for_tax_adjustments,
                'processing_status' => [
                    'is_processed' => $is_processed,
                    'needs_reprocess' => $needs_reprocess
                ],
                'cache_version' => $this->payrollProcessCacheVersion($id),
            ];

            // Short TTL + versioned key => fast but near-real-time; invalidated on mutations.
            Cache::put($cacheKey, $payload, now()->addSeconds(20));

            return $this->successResponse($payload, 'Payroll process summary data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll process summary: ' . $e->getMessage());
        }
    }

    public function getEmployeeBreakdown($payroll_period_id, $employee_id)
    {
        try {
            $payroll_period_id = (int) $payroll_period_id;
            $employee_id = (int) $employee_id;
            $cacheKey = $this->payrollProcessCacheKey('breakdown', $payroll_period_id, $employee_id);
            $skipCache = request()->boolean('refresh');

            if (!$skipCache) {
                $cached = Cache::get($cacheKey);
                if (is_array($cached)) {
                    return $this->successResponse($cached, 'Employee payroll breakdown loaded successfully');
                }
            }

            $app_key = env("APP_KEY", "");

            // Get basic payroll summary
            $payroll_summary = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('divisions as c', 'c.id', '=', 'b.division_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.first_name,' ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                                END as name"),
                    'b.employee_no',
                    'b.employment_type_id',
                    'b.division_id',
                    'c.name as department',
                    'c.name as division_name',
                    'd.name as position',
                    'a.salary',
                    'a.ot_amount as ot_pay',
                    'a.nd_amount as nd_pay',
                    'a.holiday_amount as holiday_pay',
                    'a.total_income',
                    'a.step_inc_amount',
                    'a.salary_adj_amount',
                    'a.gross_amount',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.lwop_amount',
                    'a.gsis',
                    // LEGACY_BREAKDOWN_SSS_FALLBACK:
                    // DB::raw("ISNULL(a.sss, b.sss_amount) as sss"),
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.net_pay',
                    'a.original_netpay',
                    'a.pending_amount_payment'
                )
                ->where([
                    'a.payroll_period_id' => $payroll_period_id,
                    'a.employee_id' => $employee_id
                ])
                ->first();

            if (!$payroll_summary) {
                return $this->errorResponse('Payroll summary not found for this employee.');
            }

            // Get all deductions with details
            // First, get all deductions for this employee in this payroll period
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.id as deduction_id',
                    'b.name as deduction_name',
                    'a.amount',
                    'a.deduction_id'
                )
                ->where([
                    'a.payroll_period_id' => $payroll_period_id,
                    'a.employee_id' => $employee_id
                ])
                ->where('a.amount', '>', 0)
                ->orderBy('b.name', 'asc')
                ->get();

            // Now check which deductions are loans by checking loan_applications table
            $deduction_ids = $deductions->pluck('deduction_id')->toArray();
            $loans_data = DB::table('loan_applications')
                ->where('employee_id', $employee_id)
                ->whereIn('deduction_id', $deduction_ids)
                ->where('is_approve', true)
                ->where('is_disapprove', false)
                ->where('balance', '>=', 0)
                ->select(
                    'id as loan_id',
                    'deduction_id',
                    'loan_amount',
                    'payment as loan_payment',
                    'balance as loan_balance',
                    'loan_amortization'
                )
                ->get()
                ->keyBy('deduction_id');

            // Enrich deductions with loan information
            foreach ($deductions as $deduction) {
                if (isset($loans_data[$deduction->deduction_id])) {
                    $loan = $loans_data[$deduction->deduction_id];
                    $deduction->loan_id = $loan->loan_id;
                    $deduction->loan_amount = $loan->loan_amount;
                    $deduction->loan_payment = $loan->loan_payment;
                    $deduction->loan_balance = $loan->loan_balance;
                    $deduction->loan_amortization = $loan->loan_amortization;
                } else {
                    $deduction->loan_id = null;
                    $deduction->loan_amount = null;
                    $deduction->loan_payment = null;
                    $deduction->loan_balance = null;
                    $deduction->loan_amortization = null;
                }
            }

            // Get all incomes
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'b.id as income_id',
                    'b.name as income_name',
                    'a.amount'
                )
                ->where([
                    'a.payroll_period_id' => $payroll_period_id,
                    'a.employee_id' => $employee_id
                ])
                ->where('a.amount', '>', 0)
                ->orderBy('b.name', 'asc')
                ->get();

            // Get income cap/threshold information
            $payroll_period = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->select('a.*', 'b.month_frequency')
                ->where('a.id', $payroll_period_id)
                ->first();

            // Resolve which government contributions are active for this period and employment type.
            // Use the same period-type resolution as process() so breakdown visibility aligns with schedule behavior.
            $contribution_visibility = [
                'sss' => true,
                'gsis' => true,
                'pagibig' => true,
                'philhealth' => true,
                'tax' => true,
            ];
            if ($payroll_period) {
                // LEGACY_BREAKDOWN_MONTHLY_SPLIT_DISPLAY:
                // Previous behavior averaged month totals and displayed the same per-half amount
                // for gsis/sss/pagibig/philhealth/tax in breakdown.
                $representative_period = DB::table('payroll_periods')
                    ->where('payroll_interval_id', $payroll_period->payroll_interval_id)
                    ->where('payroll_cutoff_id', $payroll_period->payroll_cutoff_id)
                    ->where('active', true)
                    ->orderBy('id', 'asc')
                    ->first();
                $payroll_period_type_id = $representative_period ? $representative_period->id : $payroll_period->id;

                $schedule = DB::table('payroll_item_schedule_headers')
                    ->where([
                        'payroll_interval_type_id' => $payroll_period->payroll_interval_id,
                        'payroll_period_type_id' => $payroll_period_type_id,
                        'employment_type_id' => $payroll_summary->employment_type_id,
                    ])
                    ->first();

                if ($schedule) {
                    $contribution_visibility = [
                        'sss' => (bool) $schedule->sss,
                        'gsis' => (bool) $schedule->gsis,
                        'pagibig' => (bool) $schedule->pagibig,
                        'philhealth' => (bool) $schedule->philhealth,
                        'tax' => (bool) $schedule->tax,
                    ];
                }
            }

            $income_cap_info = [];
            if ($payroll_period) {
                $year = date('Y', strtotime($payroll_period->release_date));

                // Determine if semi-monthly (2 periods per month) or monthly
                $cutoff_count = DB::table('payroll_cutoffs')
                    ->where('payroll_interval_id', $payroll_period->payroll_interval_id)
                    ->count();
                $is_semi_monthly = ($cutoff_count >= 2);

                // PhilHealth income ceiling
                $philhealth_data = DB::table('philhealths')
                    ->where('year', $year)
                    ->orderBy('year', 'desc')
                    ->first();

                if ($philhealth_data) {
                    $income_cap_info['philhealth'] = [
                        'income_ceiling' => $philhealth_data->income_ceiling,
                        'income_floor' => $philhealth_data->income_floor,
                        'multiplier' => $philhealth_data->multiplier,
                        'fix_rate' => $philhealth_data->fix_rate,
                        'current_gross' => $payroll_summary->gross_amount
                    ];
                }

                // GSIS multiplier
                $gsis_data = DB::table('gsis')
                    ->where('year', $year)
                    ->orderBy('year', 'desc')
                    ->first();

                if ($gsis_data) {
                    $income_cap_info['gsis'] = [
                        'multiplier' => $gsis_data->multiplier,
                        'current_gross' => $payroll_summary->gross_amount
                    ];
                }

                // Pag-IBIG setup
                $pagibig_setup = DB::table('pagibig_setups')
                    ->where('year', $year)
                    ->orderBy('year', 'desc')
                    ->first();

                if ($pagibig_setup) {
                    $income_cap_info['pagibig'] = [
                        'amount' => $pagibig_setup->amount
                    ];
                }

                // YTD contributions
                $ytd_contributions = [];
                $year_start = $year . '-01-01';
                $current_period_date = $payroll_period->release_date;

                // Sum periods up to and including current (release_date, then id).
                $ytd_totals = DB::table('payroll_summaries as a')
                    ->join('payroll_periods as b', 'a.payroll_period_id', '=', 'b.id')
                    ->select(
                        DB::raw("SUM(a.gsis) as total_gsis"),
                        DB::raw("SUM(ISNULL(a.sss, 0)) as total_sss"),
                        DB::raw("SUM(a.pagibig) as total_pagibig"),
                        DB::raw("SUM(a.philhealth) as total_philhealth"),
                        DB::raw("SUM(a.tax) as total_tax"),
                        DB::raw("SUM(a.gross_amount) as total_gross"),
                        DB::raw("SUM(a.total_income) as total_income_all")
                    )
                    ->where('a.employee_id', $employee_id)
                    ->whereYear('b.release_date', $year)
                    ->where(function ($q) use ($current_period_date, $payroll_period_id) {
                        $q->where('b.release_date', '<', $current_period_date)
                            ->orWhere(function ($q2) use ($current_period_date, $payroll_period_id) {
                                $q2->where('b.release_date', '=', $current_period_date)
                                    ->where('b.id', '<=', $payroll_period_id);
                            });
                    })
                    ->first();

                $current_gsis = (float) ($payroll_summary->gsis ?? 0);
                $current_philhealth = (float) ($payroll_summary->philhealth ?? 0);
                $current_pagibig = (float) ($payroll_summary->pagibig ?? 0);

                // Annual limits: semi-monthly = salary*2*12, else salary*12
                $monthly_salary = $is_semi_monthly ? ($payroll_summary->salary * 2) : $payroll_summary->salary;
                $annual_salary = $monthly_salary * 12;

                if ($gsis_data) {
                    $gsis_monthly = $monthly_salary * (float) $gsis_data->multiplier;
                    $gsis_annual = $gsis_monthly * 12;
                    $ytd_gsis = (float) ($ytd_totals->total_gsis ?? 0);
                    $ytd_contributions['gsis'] = [
                        'annual_limit' => $gsis_annual,
                        'ytd_paid' => $ytd_gsis,
                        'remaining' => max(0, $gsis_annual - $ytd_gsis),
                        'percentage_paid' => $gsis_annual > 0 ? ($ytd_gsis / $gsis_annual) * 100 : 0
                    ];
                }

                if ($philhealth_data) {
                    $philhealth_monthly = 0;
                    if ($monthly_salary >= $philhealth_data->income_ceiling) {
                        $philhealth_monthly = (float) $philhealth_data->fix_rate;
                    } elseif ($monthly_salary > $philhealth_data->income_floor) {
                        $philhealth_monthly = $monthly_salary * (float) $philhealth_data->multiplier;
                    }
                    $philhealth_annual = $philhealth_monthly * 12;
                    $ytd_philhealth = (float) ($ytd_totals->total_philhealth ?? 0);
                    $ytd_contributions['philhealth'] = [
                        'annual_limit' => $philhealth_annual,
                        'ytd_paid' => $ytd_philhealth,
                        'remaining' => max(0, $philhealth_annual - $ytd_philhealth),
                        'percentage_paid' => $philhealth_annual > 0 ? ($ytd_philhealth / $philhealth_annual) * 100 : 0
                    ];
                }

                // Pag-IBIG: when current=0 use latest previous period's amount for annual limit; cap >500 at 200.
                if ($pagibig_setup) {
                    $pagibig_per_period = (float) ($payroll_summary->pagibig ?? 0);
                    if ($pagibig_per_period <= 0) {
                        $prev_pagibig = DB::table('payroll_summaries as a')
                            ->join('payroll_periods as b', 'a.payroll_period_id', '=', 'b.id')
                            ->where('a.employee_id', $employee_id)
                            ->whereYear('b.release_date', $year)
                            ->where(function ($q) use ($current_period_date, $payroll_period_id) {
                                $q->where('b.release_date', '<', $current_period_date)
                                    ->orWhere(function ($q2) use ($current_period_date, $payroll_period_id) {
                                        $q2->where('b.release_date', '=', $current_period_date)
                                            ->where('b.id', '<', $payroll_period_id);
                                    });
                            })
                            ->where('a.pagibig', '>', 0)
                            ->orderBy('b.release_date', 'desc')
                            ->orderBy('b.id', 'desc')
                            ->value('a.pagibig');
                        $pagibig_per_period = $prev_pagibig !== null ? (float) $prev_pagibig : (float) $pagibig_setup->amount;
                        if ($pagibig_per_period > 500) {
                            $pagibig_per_period = 200;
                        }
                    }
                    $pagibig_annual = $pagibig_per_period * 12;
                    $ytd_pagibig = (float) ($ytd_totals->total_pagibig ?? 0);
                    $ytd_contributions['pagibig'] = [
                        'annual_limit' => $pagibig_annual,
                        'ytd_paid' => $ytd_pagibig,
                        'remaining' => max(0, $pagibig_annual - $ytd_pagibig),
                        'percentage_paid' => $pagibig_annual > 0 ? ($ytd_pagibig / $pagibig_annual) * 100 : 0
                    ];
                }

                // Income threshold (₱90k for benefits; YTD includes current period)
                $income_threshold_info = [];
                $ytd_total_income = (float) ($ytd_totals->total_income_all ?? 0);
                $benefits_threshold = 90000;

                $excess_income = max(0, $ytd_total_income - $benefits_threshold);

                $income_threshold_info = [
                    'taxable_threshold' => $benefits_threshold,
                    'ytd_gross_income' => $ytd_total_income,
                    'excess_income' => $excess_income,
                    'current_period_gross' => $payroll_summary->gross_amount,
                    'current_period_total_income' => $payroll_summary->total_income,
                    'ytd_gross_before_current' => (float) ($ytd_totals->total_gross ?? 0) - (float) $payroll_summary->gross_amount,
                    'ytd_total_income_before_current' => $ytd_total_income - (float) $payroll_summary->total_income
                ];

                $income_cap_info['ytd_contributions'] = $ytd_contributions;
                $income_cap_info['income_threshold'] = $income_threshold_info;
                $income_cap_info['gov_contribution_history'] = $this->buildEmployeeGovContributionHistory(
                    $employee_id,
                    (int) $year,
                    $current_period_date,
                    $payroll_period_id
                );
            }

            // Separate loans from other deductions
            $loans = [];
            $other_deductions = [];

            foreach ($deductions as $deduction) {
                if ($deduction->loan_id) {
                    $currentPayment = (float) ($deduction->amount ?? 0);
                    $paidSoFar = (float) ($deduction->loan_payment ?? 0);
                    $currentBalance = (float) ($deduction->loan_balance ?? 0);

                    // Show projected values (after this payment is applied) so the breakdown
                    // accurately reflects what the balance will be once this payroll is posted.
                    $loans[] = [
                        'loan_id' => $deduction->loan_id,
                        'loan_type' => $deduction->deduction_name,
                        'loan_amount' => $deduction->loan_amount,
                        'current_payment' => $currentPayment,
                        'total_payment' => $paidSoFar + $currentPayment,
                        'remaining_balance' => max(0.0, $currentBalance - $currentPayment),
                        'amortization' => $deduction->loan_amortization
                    ];
                } else {
                    $other_deductions[] = [
                        'deduction_id' => $deduction->deduction_id,
                        'deduction_name' => $deduction->deduction_name,
                        'amount' => $deduction->amount
                    ];
                }
            }

            // Calculate other deductions total (excluding standard deductions)
            $standard_deductions = [
                'late_amount' => $payroll_summary->late_amount,
                'ut_amount' => $payroll_summary->ut_amount,
                'absent_amount' => $payroll_summary->absent_amount,
                'gsis' => (float) ($payroll_summary->gsis ?? 0),
                'sss' => (float) ($payroll_summary->sss ?? 0),
                'pagibig' => (float) ($payroll_summary->pagibig ?? 0),
                'philhealth' => (float) ($payroll_summary->philhealth ?? 0),
                'tax' => (float) ($payroll_summary->tax ?? 0)
            ];

            $other_deductions_total = $payroll_summary->total_deduction -
                array_sum(array_values($standard_deductions));

            $payload = [
                'payroll_summary' => $payroll_summary,
                'incomes' => $incomes,
                'loans' => $loans,
                'other_deductions' => $other_deductions,
                'standard_deductions' => $standard_deductions,
                'contribution_visibility' => $contribution_visibility,
                'other_deductions_total' => $other_deductions_total > 0 ? $other_deductions_total : 0,
                'income_cap_info' => $income_cap_info
            ];

            Cache::put($cacheKey, $payload, now()->addSeconds(20));

            return $this->successResponse($payload, 'Employee payroll breakdown loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employee payroll breakdown: ' . $e->getMessage());
        }
    }

    public function posting($id, $type_id)
    {
        try {
            // loan application payment.
            $loans = DB::table('loan_applications as a')
                ->join('payroll_deductions as b', function ($join) {
                    $join->on('b.deduction_id', '=', 'a.deduction_id');
                    $join->on('b.employee_id', '=', 'a.employee_id');
                })
                ->select(
                    'a.id',
                    'b.amount',
                    'a.payment',
                    'a.balance',
                    'a.loan_amount',
                    'a.employee_id',
                    'a.deduction_id'
                )
                ->where([
                    'b.payroll_period_id' => $id,
                    'a.is_approve' => true,
                    'a.is_disapprove' => false
                ])
                ->where('a.balance', '>=', 0)
                ->where('b.amount', '>', 0)
                ->get();

            if ($type_id == 1) {

                // Block posting when any employee's net pay is below ₱5,000.
                // Management must apply a salary adjustment and reprocess first.
                $belowThresholdEmployees = DB::table('payroll_summaries as ps')
                    ->join('employees as e', 'e.id', '=', 'ps.employee_id')
                    ->where('ps.payroll_period_id', $id)
                    ->where('ps.net_pay', '<', 5000)
                    ->select('e.first_name', 'e.last_name', 'ps.net_pay')
                    ->get();

                if ($belowThresholdEmployees->isNotEmpty()) {
                    $count = $belowThresholdEmployees->count();
                    return $this->errorResponse(
                        'Cannot post payroll. ' . $count . ' employees have a net pay below ₱5,000. '
                            . 'Apply a salary adjustment and reprocess the payroll before posting.'
                    );
                }

                if ($loans->isNotEmpty()) {

                    // process loans
                    $loan_data = [];

                    for ($i = 0; $i < count($loans); $i++) {

                        $balance = $loans[$i]->balance - $loans[$i]->amount;
                        $payment = $loans[$i]->payment + $loans[$i]->amount;

                        $loan_data = [
                            'payment' => $payment,
                            'balance' => $balance
                        ];

                        DB::table('loan_applications')->updateOrInsert(['id' => $loans[$i]->id], $loan_data);
                    }
                }

                // post payroll
                DB::table('payroll_periods')->where('id', $id)->update(['posted' => 1]);

                // Save Audit Trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Process',
                    'activity' => 'Posting',
                    'description' => 'Posted Payroll informations.',
                );

                Audit::create($data_audit);

                // Invalidate cached summary/breakdowns for this payroll period.
                $this->invalidatePayrollProcessCache((int) $id);
            } else {

                if ($loans->isNotEmpty()) {

                    // process loans
                    $loan_data = [];

                    for ($i = 0; $i < count($loans); $i++) {

                        $balance = $loans[$i]->balance + $loans[$i]->amount;
                        $payment = $loans[$i]->payment - $loans[$i]->amount;

                        $loan_data = [
                            'payment' => $payment,
                            'balance' => $balance
                        ];

                        DB::table('loan_applications')->updateOrInsert(['id' => $loans[$i]->id], $loan_data);
                    }
                }

                // post payroll
                DB::table('payroll_periods')->where('id', $id)->update(['posted' => 0]);

                // Save Audit Trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Process',
                    'activity' => 'Posting',
                    'description' => 'Un-posted Payroll informations.',
                );

                Audit::create($data_audit);

                // Invalidate cached summary/breakdowns for this payroll period.
                $this->invalidatePayrollProcessCache((int) $id);
            }

            $posted = ((int) $type_id === 1);
            return $this->successResponse(
                ['payroll_period_id' => (int) $id, 'posted' => $posted],
                $posted ? 'Payroll posted successfully.' : 'Payroll unposted successfully.'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process payroll posting: ' . $e->getMessage());
        }
    }

    public function print(Request $request, $id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $companies = DB::table('companies')->get();
            $preparedBy = $request->query('prepared_by');
            $preparedByPosition = $request->query('prepared_by_position');
            $approvedBy = $request->query('approved_by');
            $approvedByPosition = $request->query('approved_by_position');

            $payrolls = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftjoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.ot_amount as ot_pay',
                    'a.nd_amount as nd_pay',
                    'a.holiday_amount as holiday_pay',
                    'a.total_income',
                    'a.gross_amount',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.net_pay'
                )
                ->where('payroll_period_id', $id)
                ->orderBy('b.first_name', 'asc')
                ->get();

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name")
                )
                ->where(['a.id' => $id])
                ->orderBy('a.release_date', 'desc')
                ->get();

            $payroll_period = $data[0]->name;

            // get all income items.
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            // get all deduction items.
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            $pdf = PDF::loadView('payroll_processes.payroll_print', compact('companies', 'payrolls', 'payroll_period', 'incomes', 'deductions', 'preparedBy', 'preparedByPosition', 'approvedBy', 'approvedByPosition'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('Legal', 'landscape');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = "payroll_report_{$id}_" . date('Y-m-d') . ".pdf";

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payroll report: ' . $e->getMessage());
        }
    }

    public function printTabulate($id)
    {
        try {
            $blockMsg = $this->requireFullMonthPosted((int) $id);
            if ($blockMsg) return $this->errorResponse($blockMsg);

            $app_key = env("APP_KEY", "");

            $companies = DB::table('companies')->get();

            $payrolls = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftjoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.payroll_period_id',
                    'b.employee_no',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.ot_amount as ot_pay',
                    'a.nd_amount as nd_pay',
                    'a.holiday_amount as holiday_pay',
                    'a.total_income',
                    'a.gross_amount',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.late_amount',
                    'a.ut_amount',
                    'absent_amount',
                    'a.net_pay'
                )
                ->where('payroll_period_id', $id)
                ->orderBy('b.first_name', 'asc')
                ->get();

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    'payroll_start_date',
                    'payroll_end_date',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name")
                )
                ->where(['a.id' => $id])
                ->orderBy('a.release_date', 'desc')
                ->get();

            $payroll_period = date('F d', strtotime($data[0]->payroll_start_date)) . ' - ' . date('d, Y', strtotime($data[0]->payroll_end_date));

            $income_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('incomes as c', 'b.income_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.income_id',
                    'c.name as income'
                )
                ->where('e.id', $id)
                ->where('b.active', true)
                ->distinct()
                ->get();

            $deduction_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('deductions as c', 'b.deduction_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.deduction_id',
                    'c.name as deduction'
                )
                ->where('e.id', $id)
                ->where('b.active', true)
                ->distinct()
                ->get();

            // get all income items.
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'b.id as income_id',
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            // get all deduction items.
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.id as deduction_id',
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            $pdf = PDF::loadView('payroll_processes.payroll_print_tabulate', compact(
                'payrolls',
                'payroll_period',
                'incomes',
                'deductions',
                'companies',
                'data',
                'income_headers',
                'deduction_headers',
                'companies'
            ))->setOptions(['defaultFont' => 'sans-serif']);

            $paper_size = array(0, 0, 500, 2200);
            $pdf->setPaper($paper_size, 'landscape');
            // $pdf->setPaper('Legal', 'landscape');
            $pdfContent = $pdf->output();

            $filename = "payroll_tabulate_report_{$id}_" . date('Y-m-d') . ".pdf";

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payroll tabulate report: ' . $e->getMessage());
        }
    }

    public function payrollSummary()
    {
        try {
            $payroll_intervals = DB::table('payroll_intervals')
                ->where('active', true)
                ->whereIn('id', function ($query) {
                    $query->select('payroll_interval_id')->from('payroll_periods')->where('posted', true)->get();
                })
                ->get();

            $companies = DB::table('companies')->get();
            $branches = DB::table('branches')->get();
            $divisions = $this->getActiveDivisions();

            return $this->successResponse([
                'payroll_intervals' => $payroll_intervals,
                'branches' => $branches,
                'divisions' => $divisions,
                'companies' => $companies
            ], 'Payroll process print data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll summary data: ' . $e->getMessage());
        }
    }

    public function payrollSummaryDetail()
    {
        try {
            $payroll_intervals = DB::table('payroll_intervals')
                ->where('active', true)
                ->whereIn('id', function ($query) {
                    $query->select('payroll_interval_id')->from('payroll_periods')->where('posted', true)->get();
                })
                ->get();

            $companies = DB::table('companies')->get();
            $branches = DB::table('branches')->get();
            $divisions = $this->getActiveDivisions();

            return $this->successResponse([
                'payroll_intervals' => $payroll_intervals,
                'branches' => $branches,
                'divisions' => $divisions,
                'departments' => $divisions,
                'companies' => $companies
            ], 'Payroll process detail print data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll summary detail data: ' . $e->getMessage());
        }
    }

    public function payrollSummaryPrint(Request $request)
    {
        try {
            // Prevent timeouts/memory issues when generating large reports (e.g., All Departments)
            @set_time_limit(0);
            @ini_set('memory_limit', '1024M');

            $app_key = env("APP_KEY", "");

            $validator = \Validator::make($request->all(), [
                'payroll_interval_id' => 'required',
                'payroll_period_id' => 'required',
                'payroll_period_id_2' => 'nullable',
            ], [
                'payroll_interval_id.required' => 'Payroll Interval is required.',
                'payroll_period_id.required' => 'Payroll Period is required.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $id = $request->payroll_period_id;
            $id2 = $request->payroll_period_id_2;
            $divisionFilter = $this->resolveDivisionFilterFromRequest($request);
            $branch_id = $request->branch_id;

            // If the UI only provides a single "monthly" option (grouped month),
            // automatically resolve the companion half-period and merge both.
            if (empty($id2)) {
                [$resolvedFirstId, $resolvedSecondId] = $this->resolveMonthlyPayrollPeriodPair($id);
                if (!empty($resolvedSecondId) && (string) $resolvedSecondId !== (string) $resolvedFirstId) {
                    $id = $resolvedFirstId;
                    $id2 = $resolvedSecondId;
                } else {
                    $id = $resolvedFirstId;
                }
            }

            // Only block when generating a full-month report (merged halves / monthly cutoff).
            // If only one half is posted and user selected that half, allow printing.
            $blockMsg = $this->requireFullMonthPosted((int) $id);
            if ($blockMsg && !empty($id2)) {
                return $this->errorResponse($blockMsg, 400, [
                    'payroll_period_id' => [$blockMsg],
                ]);
            }

            \Log::info('payrollSummaryPrint: start', [
                'payroll_period_id' => $id,
                'payroll_period_id_2' => $id2,
                'division_id' => $divisionFilter,
                'branch_id' => $branch_id,
            ]);

            $companies = DB::table('companies')->get();

            $departmentName = $this->getDivisionDisplayName($divisionFilter);
            $showAllColumns = in_array(
                strtolower((string) $request->get('column_display_mode', 'all')),
                ['all', 'show_all', 'display_all'],
                true
            );

            // Load payroll summaries for the selected period (and optional second period)
            $payrollsFirstHalf = $this->fetchGeneralPayrollSummariesForPeriod($id, $divisionFilter, $app_key);
            $payrolls = $payrollsFirstHalf;

            if (!empty($id2)) {
                $payrollsSecondHalf = $this->fetchGeneralPayrollSummariesForPeriod($id2, $divisionFilter, $app_key);
                $payrolls = $this->mergePayrollHalves($payrollsFirstHalf, $payrollsSecondHalf);
            }

            $periodIds = !empty($id2) ? [$id, $id2] : [$id];

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    'attendance_start_date',
                    'attendance_end_date',
                    'release_date',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name")
                )
                ->whereIn('a.id', $periodIds)
                ->orderBy('a.attendance_start_date', 'asc')
                ->get();

            if ($data->isEmpty()) {
                return $this->serverErrorResponse('Payroll period not found.');
            }

            $firstPeriod = $data->first();
            $lastPeriod = $data->last();

            $payroll_period = date('F d', strtotime($firstPeriod->attendance_start_date)) . ' - ' . date('d, Y', strtotime($lastPeriod->attendance_end_date));
            $payroll_month = date(
                'F',
                strtotime($lastPeriod->release_date ?? $lastPeriod->attendance_end_date)
            );
            $payroll_year = date(
                'Y',
                strtotime($lastPeriod->release_date ?? $lastPeriod->attendance_end_date)
            );

            $income_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('incomes as c', 'b.income_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.income_id',
                    'c.name as income'
                )
                ->whereIn('e.id', $periodIds)
                ->where('b.active', true)
                ->whereIn('c.id', function ($query) use ($periodIds) {
                    $query->select('income_id')->from('payroll_incomes')
                        ->where('amount', '>', 0)
                        ->whereIn('payroll_period_id', $periodIds)
                        ->distinct();
                })
                ->distinct()
                ->get();

            $deduction_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('deductions as c', 'b.deduction_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.deduction_id',
                    'c.name as deduction'
                )
                ->whereIn('e.id', $periodIds)
                ->where('b.active', true)
                ->whereIn('c.id', function ($query) use ($periodIds) {
                    $query->select('deduction_id')->from('payroll_deductions')
                        ->where('amount', '>', 0)
                        ->whereIn('payroll_period_id', $periodIds)
                        ->distinct();
                })
                ->distinct()
                ->orderBy('b.deduction_id', 'asc')
                ->get();

            $payrolls = $this->attachUnlistedLoanOtherDeductionsForGeneralPayroll($payrolls, $periodIds, $deduction_headers);

            // get all income items (combined across selected periods)
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'b.id as income_id',
                    'a.employee_id',
                    'b.name',
                    DB::raw('SUM(a.amount) as amount')
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->groupBy('b.id', 'a.employee_id', 'b.name')
                ->get();

            // get all deduction items (combined across selected periods)
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.id as deduction_id',
                    'a.employee_id',
                    'b.name',
                    DB::raw('SUM(a.amount) as amount')
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->groupBy('b.id', 'a.employee_id', 'b.name')
                ->get();

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $signatories = [
                'signatory_1' => $request->signatory_1,
                'signatory_position_1' => $request->signatory_position_1,
                'signatory_2' => $request->signatory_2,
                'signatory_position_2' => $request->signatory_position_2,
                'signatory_3' => $request->signatory_3,
                'signatory_position_3' => $request->signatory_position_3,
                'signatory_4' => $request->signatory_4,
                'signatory_position_4' => $request->signatory_position_4,
                'signatory_5' => $request->signatory_5,
                'signatory_position_5' => $request->signatory_position_5,
            ];

            $pdf = PDF::loadView('payroll_processes.general_payroll_report', compact(
                'payrolls',
                'payroll_period',
                'payroll_month',
                'payroll_year',
                'incomes',
                'deductions',
                'companies',
                'data',
                'income_headers',
                'deduction_headers',
                'signatories',
                'image',
                'departmentName',
                'showAllColumns',
            ))->setOptions(['defaultFont' => 'sans-serif',  'isPhpEnabled' => true,]);

            $pdf->setPaper('tabloid', 'landscape');

            \Log::info('payrollSummaryPrint: pdf generated', [
                'payroll_period_id' => $id,
                'payroll_period_id_2' => $id2,
                'row_count' => is_countable($payrolls) ? count($payrolls) : null,
            ]);

            // Return PDF directly as binary response (like legacy system)
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="payroll_summary_report.pdf"'
            ]);
        } catch (\Exception $e) {
            \Log::error('payrollSummaryPrint: exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->serverErrorResponse('Failed to generate payroll summary report: ' . $e->getMessage());
        }
    }

    /**
     * Generate General Payroll Report DOCX
     */
    public function payrollSummaryPrintDocx(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $validator = \Validator::make($request->all(), [
                'payroll_interval_id' => 'required',
                'payroll_period_id' => 'required',
                'payroll_period_id_2' => 'nullable',
            ], [
                'payroll_interval_id.required' => 'Payroll Interval is required.',
                'payroll_period_id.required' => 'Payroll Period is required.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $id = $request->payroll_period_id;
            $id2 = $request->payroll_period_id_2;
            $divisionFilter = $this->resolveDivisionFilterFromRequest($request);
            $branch_id = $request->branch_id;

            // Auto-resolve month pair when UI provides a single monthly option
            if (empty($id2)) {
                [$resolvedFirstId, $resolvedSecondId] = $this->resolveMonthlyPayrollPeriodPair($id);
                if (!empty($resolvedSecondId) && (string) $resolvedSecondId !== (string) $resolvedFirstId) {
                    $id = $resolvedFirstId;
                    $id2 = $resolvedSecondId;
                } else {
                    $id = $resolvedFirstId;
                }
            }

            $companies = DB::table('companies')->get();

            $departmentName = $this->getDivisionDisplayName($divisionFilter);

            // Load payroll summaries for the selected period (and optional second period)
            $payrollsFirstHalf = $this->fetchGeneralPayrollSummariesForPeriod($id, $divisionFilter, $app_key);
            $payrolls = $payrollsFirstHalf;

            if (!empty($id2)) {
                $payrollsSecondHalf = $this->fetchGeneralPayrollSummariesForPeriod($id2, $divisionFilter, $app_key);
                $payrolls = $this->mergePayrollHalves($payrollsFirstHalf, $payrollsSecondHalf);
            }

            $periodIds = !empty($id2) ? [$id, $id2] : [$id];

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    'attendance_start_date',
                    'attendance_end_date',
                    'release_date',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name")
                )
                ->whereIn('a.id', $periodIds)
                ->orderBy('a.attendance_start_date', 'asc')
                ->get();

            if ($data->isEmpty()) {
                return $this->serverErrorResponse('Payroll period not found.');
            }

            $firstPeriod = $data->first();
            $lastPeriod = $data->last();

            $payroll_period = date('F d', strtotime($firstPeriod->attendance_start_date)) . ' - ' . date('d, Y', strtotime($lastPeriod->attendance_end_date));
            $payroll_month = date(
                'F',
                strtotime($lastPeriod->release_date ?? $lastPeriod->attendance_end_date)
            );
            $payroll_year = date(
                'Y',
                strtotime($lastPeriod->release_date ?? $lastPeriod->attendance_end_date)
            );

            $income_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('incomes as c', 'b.income_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.income_id',
                    'c.name as income'
                )
                ->whereIn('e.id', $periodIds)
                ->where('b.active', true)
                ->whereIn('c.id', function ($query) use ($periodIds) {
                    $query->select('income_id')->from('payroll_incomes')
                        ->where('amount', '>', 0)
                        ->whereIn('payroll_period_id', $periodIds)
                        ->distinct();
                })
                ->distinct()
                ->get();

            $deduction_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('deductions as c', 'b.deduction_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.deduction_id',
                    'c.name as deduction'
                )
                ->whereIn('e.id', $periodIds)
                ->where('b.active', true)
                ->whereIn('c.id', function ($query) use ($periodIds) {
                    $query->select('deduction_id')->from('payroll_deductions')
                        ->where('amount', '>', 0)
                        ->whereIn('payroll_period_id', $periodIds)
                        ->distinct();
                })
                ->distinct()
                ->orderBy('b.deduction_id', 'asc')
                ->get();

            $payrolls = $this->attachUnlistedLoanOtherDeductionsForGeneralPayroll($payrolls, $periodIds, $deduction_headers);

            // get all income items.
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'b.id as income_id',
                    'a.employee_id',
                    'b.name',
                    DB::raw('SUM(a.amount) as amount')
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->groupBy('b.id', 'a.employee_id', 'b.name')
                ->get();

            // get all deduction items.
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.id as deduction_id',
                    'a.employee_id',
                    'b.name',
                    DB::raw('SUM(a.amount) as amount')
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->groupBy('b.id', 'a.employee_id', 'b.name')
                ->get();

            // Helper function to get acronym
            $acronym = function ($text) {
                if (!$text) {
                    return '';
                }
                $stopWords = ['OF', 'THE', 'AND', 'IN'];
                $words = preg_split('/\s+/', trim($text));
                $letters = collect($words)
                    ->map(fn($w) => strtoupper($w))
                    ->reject(fn($w) => in_array($w, $stopWords))
                    ->map(fn($w) => $w[0] ?? '')
                    ->filter()
                    ->join('');
                return $letters ?: strtoupper(substr(trim($text), 0, 1));
            };

            // Build income and deduction lookups
            $incomeLookup = collect($incomes ?? [])
                ->groupBy('employee_id')
                ->map(fn($items) => $items->keyBy('income_id')->map(fn($item) => (array) $item)->toArray())
                ->toArray();

            $deductionLookup = collect($deductions ?? [])
                ->groupBy('employee_id')
                ->map(fn($items) => $items->keyBy('deduction_id')->map(fn($item) => (array) $item)->toArray())
                ->toArray();

            $peraHeader = collect($income_headers ?? [])->first(fn($header) => stripos($header->income ?? '', 'pera') !== false);
            $mpliteHeader = collect($deduction_headers ?? [])->first(fn($header) => stripos($header->deduction ?? '', 'mplite') !== false);
            $pabahayHeader = collect($deduction_headers ?? [])->first(fn($header) => stripos($header->deduction ?? '', 'pabahay') !== false);
            $consoHeader = collect($deduction_headers ?? [])->first(fn($header) => stripos($header->deduction ?? '', 'conso') !== false);
            $geHeader = collect($deduction_headers ?? [])->first(fn($header) => stripos($header->deduction ?? '', 'gsis ge') !== false);
            $cplHeader = collect($deduction_headers ?? [])->first(fn($header) => stripos($header->deduction ?? '', 'gsis cpl') !== false)
                ?? collect($deduction_headers ?? [])->first(fn($header) => stripos($header->deduction ?? '', 'cpl') !== false);
            $pPremHeader = collect($deduction_headers ?? [])->first(fn($header) => (
                stripos($header->deduction ?? '', 'p prem') !== false
                || stripos($header->deduction ?? '', 'p_prem') !== false
                || stripos($header->deduction ?? '', 'p-prem') !== false
            ));
            $pMplHeader = collect($deduction_headers ?? [])->first(fn($header) => (
                stripos($header->deduction ?? '', 'p mpl') !== false
                || stripos($header->deduction ?? '', 'p_mpl') !== false
                || stripos($header->deduction ?? '', 'p-mpl') !== false
            ));

            // Calculate totals
            $totals = [
                'basic' => 0,
                'step_inc' => 0,
                'diff' => 0,
                'adcomp' => 0,
                'pera' => 0,
                'gross' => 0,
                'gsis' => 0,
                'medicare' => 0,
                'gsis_ge' => 0,
                'gsis_cpl' => 0,
                'p_prem' => 0,
                'p_mpl' => 0,
                'gsis_opt' => 0,
                'gsis_mplite' => 0,
                'gsis_conso' => 0,
                'pol_loan' => 0,
                'pagcal' => 0,
                'phsg_loan' => 0,
                'gsis_hsg' => 0,
                'gsis_pabahay' => 0,
                'pagibig' => 0,
                'pagibig_gs' => 0,
                'pagibig_loan' => 0,
                'philhealth' => 0,
                'philhealth_employer' => 0,
                'others' => 0,
                'hmo' => 0,
                'ecash' => 0,
                'ea' => 0,
                'coop' => 0,
                'pagibig_mp2' => 0,
                'w_tax' => 0,
                'u_mid' => 0,
                'res_sal' => 0,
                'loan_emergency' => 0,
                'deduction' => 0,
                'net' => 0,
                'first' => 0,
                'second' => 0,
            ];

            // Process payrolls and calculate values (matching blade template logic)
            $processedPayrolls = collect($payrolls)->map(function ($payroll) use ($incomeLookup, $deductionLookup, $peraHeader, $mpliteHeader, $pabahayHeader, $consoHeader, $geHeader, $cplHeader, $pPremHeader, $pMplHeader, &$totals, $acronym) {
                $stepInc = data_get($payroll, 'step_increment', 0);
                $diff = data_get($payroll, 'differential', 0);
                $adComp = data_get($payroll, 'additional_comp', 0);
                $pera = data_get($payroll, 'pera', 0);
                if ((!$pera || $pera == 0) && $peraHeader) {
                    $pera = data_get($incomeLookup, "{$payroll->employee_id}.{$peraHeader->income_id}.amount", 0);
                }
                $gsis = $payroll->gsis ?? 0;
                $medicare = data_get($payroll, 'philhealth', 0);
                $gsisGe = data_get($payroll, 'gsis_ge', 0);
                $gsisCpl = data_get($payroll, 'gsis_cpl', 0);
                $pPrem = data_get($payroll, 'p_prem', 0);
                $pMpl = data_get($payroll, 'p_mpl', 0);
                if ((!$gsisGe || $gsisGe == 0) && $geHeader) {
                    $gsisGe = data_get($deductionLookup, "{$payroll->employee_id}.{$geHeader->deduction_id}.amount", 0);
                }
                if ((!$gsisCpl || $gsisCpl == 0) && $cplHeader) {
                    $gsisCpl = data_get($deductionLookup, "{$payroll->employee_id}.{$cplHeader->deduction_id}.amount", 0);
                }
                if ((!$pPrem || $pPrem == 0) && $pPremHeader) {
                    $pPrem = data_get($deductionLookup, "{$payroll->employee_id}.{$pPremHeader->deduction_id}.amount", 0);
                }
                if ((!$pMpl || $pMpl == 0) && $pMplHeader) {
                    $pMpl = data_get($deductionLookup, "{$payroll->employee_id}.{$pMplHeader->deduction_id}.amount", 0);
                }
                $gsisOpt = data_get($payroll, 'gsis_opt_life', 0);
                $gsisMpl = data_get($payroll, 'gsis_mpl', 0);
                $gsisMplite = data_get($payroll, 'gsis_mplite', 0);
                if ((!$gsisMplite || $gsisMplite == 0) && $mpliteHeader) {
                    $gsisMplite = data_get($deductionLookup, "{$payroll->employee_id}.{$mpliteHeader->deduction_id}.amount", 0);
                }
                $gsisConso = data_get($payroll, 'gsis_conso', $gsisMpl);
                if ((!$gsisConso || $gsisConso == 0) && $consoHeader) {
                    $gsisConso = data_get($deductionLookup, "{$payroll->employee_id}.{$consoHeader->deduction_id}.amount", 0);
                }
                $polLoan = data_get($payroll, 'pol_loan', data_get($payroll, 'gsis_policy', 0));
                $pagcal = data_get($payroll, 'pagcal', 0);
                $phsgLoan = data_get($payroll, 'phsg_loan', 0);
                $gsisHsg = data_get($payroll, 'gsis_hsg', data_get($payroll, 'gsis_phsg', 0));
                $gsisPabahay = data_get($payroll, 'gsis_pabahay', data_get($payroll, 'gsis_pahabay', 0));
                if ((!$gsisPabahay || $gsisPabahay == 0) && $pabahayHeader) {
                    $gsisPabahay = data_get($deductionLookup, "{$payroll->employee_id}.{$pabahayHeader->deduction_id}.amount", 0);
                }
                $pagibig = $payroll->pagibig ?? 0;
                $pagibigGs = data_get($payroll, 'pagibig_gs', 0);
                $pagibigLoan = data_get($payroll, 'pagibig_loan', 0);
                $philhealth = $payroll->philhealth ?? 0;
                $philhealthEmployer = data_get($payroll, 'philhealth_employer', 0);
                $wTax = (float) data_get($payroll, 'withholding_tax', data_get($payroll, 'w_tax', data_get($payroll, 'tax', 0)));
                $sssDeduct = (float) data_get($payroll, 'sss', 0);
                $precedingAdj = (float) data_get($payroll, 'preceding_period_adjustment', 0);
                if (isset($payroll->other_deductions)) {
                    $other = max(0, (float) $payroll->other_deductions);
                } else {
                    $other = ($payroll->total_deduction ?? 0) - ($gsis + $gsisGe + $gsisCpl + $pPrem + $pMpl + $gsisOpt + $gsisMpl + $gsisHsg + $gsisPabahay + $pagibig + $pagibigLoan + $philhealth + $wTax + $sssDeduct + $precedingAdj);
                    $other = max($other, 0);
                }
                $totalDeduction = ($payroll->total_deduction ?? 0) ?: ($gsis + $pagibig + $philhealth + $other);
                $firstPay = data_get($payroll, 'first_pay', ($payroll->net_pay ?? 0) / 2);
                $secondPay = data_get($payroll, 'second_pay', ($payroll->net_pay ?? 0) / 2);
                $hmo = data_get($payroll, 'hmo', 0);
                $ecash = data_get($payroll, 'ecash', 0);
                $ea = data_get($payroll, 'ea', 0);
                $coop = data_get($payroll, 'coop', 0);
                $pagibigMp2 = data_get($payroll, 'pagibig_mp2', 0);
                $uMid = data_get($payroll, 'u_mid', 0);
                $resSal = data_get($payroll, 'res_sal', 0);
                $loanEmergency = data_get($payroll, 'loan_emergency', 0);
                $basicUndertime = data_get($payroll, 'basic_undertime', 0);
                $stepIncUndertime = data_get($payroll, 'step_inc_undertime', 0);
                $diffUndertime = data_get($payroll, 'diff_undertime', 0);
                $adcompUndertime = data_get($payroll, 'adcomp_undertime', 0);
                $peraUndertime = data_get($payroll, 'pera_undertime', 0);

                // Update totals
                $totals['basic'] += $payroll->salary ?? 0;
                $totals['step_inc'] += $stepInc;
                $totals['diff'] += $diff;
                $totals['adcomp'] += $adComp;
                $totals['pera'] += $pera;
                $totals['gross'] += $payroll->gross_amount ?? 0;
                $totals['gsis'] += $gsis;
                $totals['medicare'] += $medicare;
                $totals['gsis_ge'] += $gsisGe;
                $totals['gsis_cpl'] += $gsisCpl;
                $totals['p_prem'] += $pPrem;
                $totals['p_mpl'] += $pMpl;
                $totals['gsis_opt'] += $gsisOpt;
                $totals['gsis_mplite'] += $gsisMplite;
                $totals['gsis_conso'] += $gsisConso;
                $totals['pol_loan'] += $polLoan;
                $totals['pagcal'] += $pagcal;
                $totals['phsg_loan'] += $phsgLoan;
                $totals['gsis_hsg'] += $gsisHsg;
                $totals['gsis_pabahay'] += $gsisPabahay;
                $totals['pagibig'] += $pagibig;
                $totals['pagibig_gs'] += $pagibigGs;
                $totals['pagibig_loan'] += $pagibigLoan;
                $totals['philhealth'] += $philhealth;
                $totals['philhealth_employer'] += $philhealthEmployer;
                $totals['others'] += $other;
                $totals['hmo'] += $hmo;
                $totals['ecash'] += $ecash;
                $totals['ea'] += $ea;
                $totals['coop'] += $coop;
                $totals['pagibig_mp2'] += $pagibigMp2;
                $totals['w_tax'] += $wTax;
                $totals['u_mid'] += $uMid;
                $totals['res_sal'] += $resSal;
                $totals['loan_emergency'] += $loanEmergency;
                $totals['deduction'] += $totalDeduction;
                $totals['net'] += $payroll->net_pay ?? 0;
                $totals['first'] += $firstPay;
                $totals['second'] += $secondPay;

                return (object)[
                    'div' => $acronym($payroll->department),
                    'name' => strtoupper($payroll->name),
                    'position' => $payroll->position,
                    'basic' => $payroll->salary ?? 0,
                    'basic_undertime' => $basicUndertime,
                    'step_inc' => $stepInc,
                    'step_inc_undertime' => $stepIncUndertime,
                    'diff' => $diff,
                    'diff_undertime' => $diffUndertime,
                    'adcomp' => $adComp,
                    'adcomp_undertime' => $adcompUndertime,
                    'pera' => $pera,
                    'pera_undertime' => $peraUndertime,
                    'gross' => $payroll->gross_amount ?? 0,
                    'gsis' => $gsis,
                    'medicare' => $medicare,
                    'gsis_ge' => $gsisGe,
                    'gsis_cpl' => $gsisCpl,
                    'p_prem' => $pPrem,
                    'p_mpl' => $pMpl,
                    'gsis_opt' => $gsisOpt,
                    'gsis_mplite' => $gsisMplite,
                    'gsis_conso' => $gsisConso,
                    'pol_loan' => $polLoan,
                    'pagcal' => $pagcal,
                    'phsg_loan' => $phsgLoan,
                    'gsis_hsg' => $gsisHsg,
                    'gsis_pabahay' => $gsisPabahay,
                    'pagibig' => $pagibig,
                    'pagibig_gs' => $pagibigGs,
                    'pagibig_loan' => $pagibigLoan,
                    'philhealth' => $philhealth,
                    'philhealth_employer' => $philhealthEmployer,
                    'other' => $other,
                    'hmo' => $hmo,
                    'ecash' => $ecash,
                    'ea' => $ea,
                    'coop' => $coop,
                    'pagibig_mp2' => $pagibigMp2,
                    'w_tax' => $wTax,
                    'u_mid' => $uMid,
                    'res_sal' => $resSal,
                    'loan_emergency' => $loanEmergency,
                    'total_deduction' => $totalDeduction,
                    'net_pay' => $payroll->net_pay ?? 0,
                    'first_pay' => $firstPay,
                    'second_pay' => $secondPay,
                    'remarks' => $payroll->remarks ?? '',
                ];
            });

            $signatories = [
                'signatory_1' => $request->signatory_1 ?? '',
                'signatory_position_1' => $request->signatory_position_1 ?? 'Chief Administrative Officer',
                'signatory_2' => $request->signatory_2 ?? '',
                'signatory_position_2' => $request->signatory_position_2 ?? 'Budget Officer',
            ];

            // Helper function to escape special characters for PhpWord (XML-safe)
            // PhpWord uses XML internally, so special characters like &, <, >, ", ' need to be escaped
            $escapeXml = function ($text) {
                if ($text === null || ($text === '' && $text !== '0')) {
                    return '';
                }
                // Convert to string if not already
                $text = (string) $text;
                // Escape XML special characters: & < > " '
                // ENT_XML1 is for XML 1.0, ENT_QUOTES escapes both single and double quotes
                // The false parameter prevents double encoding
                return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8', false);
            };

            // Generate DOCX
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(8);

            // Section with landscape orientation and tabloid size (11" x 17" in landscape = 17" x 11")
            $section = $phpWord->addSection([
                'orientation' => 'landscape',
                'pageSizeW' => 24480, // 17 inches in twips
                'pageSizeH' => 15840,  // 11 inches in twips
                'marginTop' => 360,    // 0.25 inch
                'marginBottom' => 360,
                'marginLeft' => 360,
                'marginRight' => 360,
            ]);

            // Default font color for all text
            $defaultFontColor = '1a4c8f';

            // Title
            $section->addText('GENERAL PAYROLL', ['bold' => true, 'size' => 22, 'color' => $defaultFontColor], ['alignment' => 'center', 'spaceAfter' => 100]);

            // Subtext
            $subtext = 'WE HEREBY ACKNOWLEDGE to have received of the Phil. Trade Training Center the sums opposite our names for the month of ' . $payroll_month . ' ' . $payroll_year;
            $section->addText($escapeXml($subtext), ['size' => 10, 'color' => $defaultFontColor], ['alignment' => 'center', 'spaceAfter' => 200]);

            // Division/Office
            $section->addText('DIVISION / OFFICE: ' . $escapeXml($payrolls[0]->department ?? ''), ['bold' => true, 'size' => 8, 'color' => $defaultFontColor], ['spaceAfter' => 200]);

            // Main Data Table - 28 columns with narrow widths for landscape tabloid
            // Total width: ~23520 twips (16.3 inches) - distributed across 28 columns
            // Column widths: 400, 2000, 700, 700, 600, 700, 700, 800, 700, 600, 600, 700, 800, 700, 800, 700, 700, 700, 600, 600, 600, 600, 700, 700, 800, 800, 800, 1000
            $mainTable = $section->addTable([
                'width' => 23520,
                'unit' => 'dxa',
                'borderSize' => 6,
                'borderColor' => '1a4c8f',
                'cellMargin' => 30
            ]);

            // Border style for header/label cells: all borders white
            $headerCellBorderStyle = [
                'borderTopSize' => 6,
                'borderTopColor' => 'FFFFFF',
                'borderLeftSize' => 6,
                'borderLeftColor' => 'FFFFFF',
                'borderRightSize' => 6,
                'borderRightColor' => 'FFFFFF',
                'borderBottomSize' => 6,
                'borderBottomColor' => 'FFFFFF',
            ];

            // Font style for headers/labels: blue color
            $headerFontStyle = ['bold' => true, 'size' => 9, 'color' => $defaultFontColor];
            $subHeaderFontStyle = ['bold' => true, 'size' => 8, 'color' => $defaultFontColor];

            // Header Row with stacked headers
            $headerRow = $mainTable->addRow();
            $headerRow->addCell(400, $headerCellBorderStyle)->addText('DIV', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(2000, $headerCellBorderStyle)->addText('NAME', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(700, $headerCellBorderStyle)->addText('BASIC', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(700, $headerCellBorderStyle)->addText('STEP INC', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(600, $headerCellBorderStyle)->addText('DIFF', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(700, $headerCellBorderStyle)->addText('ADCOMP', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(700, $headerCellBorderStyle)->addText('PERA', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(800, $headerCellBorderStyle)->addText('GROSS', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(700, $headerCellBorderStyle)->addText('LIFE / RET', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(600, $headerCellBorderStyle)->addText('GE', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(600, $headerCellBorderStyle)->addText('P PREM', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(700, $headerCellBorderStyle)->addText('OPT LIFE INS', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(800, $headerCellBorderStyle)->addText('GSIS CONSO/MPL', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(700, $headerCellBorderStyle)->addText('PAGCal', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(800, $headerCellBorderStyle)->addText('GSIS HSG', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(700, $headerCellBorderStyle)->addText('HDMF', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(700, $headerCellBorderStyle)->addText('HDMF', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(700, $headerCellBorderStyle)->addText('PHIC', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(600, $headerCellBorderStyle)->addText('OTHERS', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(600, $headerCellBorderStyle)->addText('HMO', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(600, $headerCellBorderStyle)->addText('EA', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(600, $headerCellBorderStyle)->addText('PAGIBIG MP2', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(700, $headerCellBorderStyle)->addText('W TAX', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(700, $headerCellBorderStyle)->addText('RES SAL', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(800, $headerCellBorderStyle)->addText('DEDUCTIONS', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(800, $headerCellBorderStyle)->addText('FIRST PAY', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow->addCell(1000, $headerCellBorderStyle)->addText('REMARKS', $headerFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);

            // Add subheader row for stacked labels
            $subHeaderRow = $mainTable->addRow();
            $subHeaderRow->addCell(400, $headerCellBorderStyle)->addText('', [], ['spaceAfter' => 0]);
            $subHeaderRow->addCell(2000, $headerCellBorderStyle)->addText('DESIGNATION', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(700, $headerCellBorderStyle)->addText('UNDERTIME', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(700, $headerCellBorderStyle)->addText('UNDERTIME', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(600, $headerCellBorderStyle)->addText('UNDERTIME', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(700, $headerCellBorderStyle)->addText('UNDERTIME', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(700, $headerCellBorderStyle)->addText('UNDERTIME', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(800, $headerCellBorderStyle)->addText('', [], ['spaceAfter' => 0]);
            $subHeaderRow->addCell(700, $headerCellBorderStyle)->addText('MEDICARE', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(600, $headerCellBorderStyle)->addText('GSISCPL', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(600, $headerCellBorderStyle)->addText('P_MPL', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(700, $headerCellBorderStyle)->addText('GSIS MPLite', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(800, $headerCellBorderStyle)->addText('POL LOAN', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(700, $headerCellBorderStyle)->addText('P-HSG LOAN', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(800, $headerCellBorderStyle)->addText('GSIS PABAHAY', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(700, $headerCellBorderStyle)->addText('G/S', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(700, $headerCellBorderStyle)->addText('LOAN', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(700, $headerCellBorderStyle)->addText('G/S', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(600, $headerCellBorderStyle)->addText('', [], ['spaceAfter' => 0]);
            $subHeaderRow->addCell(600, $headerCellBorderStyle)->addText('ECASH++', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(600, $headerCellBorderStyle)->addText('COOP', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(600, $headerCellBorderStyle)->addText('', [], ['spaceAfter' => 0]);
            $subHeaderRow->addCell(700, $headerCellBorderStyle)->addText('U_MID', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(700, $headerCellBorderStyle)->addText('LOAN EMERGENCY', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(800, $headerCellBorderStyle)->addText('NET PAY', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(800, $headerCellBorderStyle)->addText('SECOND PAY', $subHeaderFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);
            $subHeaderRow->addCell(1000, $headerCellBorderStyle)->addText('', [], ['spaceAfter' => 0]);

            // Border style for data row cells: bottom border blue, other borders white
            $dataCellBorderStyle = [
                'borderTopSize' => 6,
                'borderTopColor' => 'FFFFFF',
                'borderLeftSize' => 6,
                'borderLeftColor' => 'FFFFFF',
                'borderRightSize' => 6,
                'borderRightColor' => 'FFFFFF',
                'borderBottomSize' => 6,
                'borderBottomColor' => '1a4c8f',
            ];

            // Font style for data rows: blue color
            $dataFontStyle = ['size' => 8, 'color' => $defaultFontColor];
            $dataBoldFontStyle = ['bold' => true, 'size' => 8, 'color' => $defaultFontColor];

            // Data Rows
            foreach ($processedPayrolls as $payroll) {
                $dataRow = $mainTable->addRow();

                $dataRow->addCell(400, $dataCellBorderStyle)->addText($escapeXml($payroll->div), $dataFontStyle, ['alignment' => 'center', 'spaceAfter' => 0]);

                $nameCell = $dataRow->addCell(2000, $dataCellBorderStyle);
                $nameCell->addText($escapeXml($payroll->name), $dataBoldFontStyle, ['spaceAfter' => 0]);
                $nameCell->addText($escapeXml($payroll->position), $dataFontStyle, ['spaceAfter' => 0]);

                $dataRow->addCell(700, $dataCellBorderStyle)->addText(number_format($payroll->basic, 2) . "\n" . number_format($payroll->basic_undertime, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(700, $dataCellBorderStyle)->addText(number_format($payroll->step_inc, 2) . "\n" . number_format($payroll->step_inc_undertime, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(600, $dataCellBorderStyle)->addText(number_format($payroll->diff, 2) . "\n" . number_format($payroll->diff_undertime, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(700, $dataCellBorderStyle)->addText(number_format($payroll->adcomp, 2) . "\n" . number_format($payroll->adcomp_undertime, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(700, $dataCellBorderStyle)->addText(number_format($payroll->pera, 2) . "\n" . number_format($payroll->pera_undertime, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(800, $dataCellBorderStyle)->addText(number_format($payroll->gross, 2) . "\n", $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(700, $dataCellBorderStyle)->addText(number_format($payroll->gsis, 2) . "\n" . number_format($payroll->medicare, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(600, $dataCellBorderStyle)->addText(number_format($payroll->gsis_ge, 2) . "\n" . number_format($payroll->gsis_cpl, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(600, $dataCellBorderStyle)->addText(number_format($payroll->p_prem, 2) . "\n" . number_format($payroll->p_mpl, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(700, $dataCellBorderStyle)->addText(number_format($payroll->gsis_opt, 2) . "\n" . number_format($payroll->gsis_mplite, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(800, $dataCellBorderStyle)->addText(number_format($payroll->gsis_conso, 2) . "\n" . number_format($payroll->pol_loan, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(700, $dataCellBorderStyle)->addText(number_format($payroll->pagcal, 2) . "\n" . number_format($payroll->phsg_loan, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(800, $dataCellBorderStyle)->addText(number_format($payroll->gsis_hsg, 2) . "\n" . number_format($payroll->gsis_pabahay, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(700, $dataCellBorderStyle)->addText(number_format($payroll->pagibig, 2) . "\n" . number_format($payroll->pagibig_gs, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(700, $dataCellBorderStyle)->addText(number_format($payroll->pagibig_loan, 2) . "\n", $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(700, $dataCellBorderStyle)->addText(number_format($payroll->philhealth, 2) . "\n" . number_format($payroll->philhealth_employer, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(600, $dataCellBorderStyle)->addText(number_format($payroll->other, 2) . "\n", $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(600, $dataCellBorderStyle)->addText(number_format($payroll->hmo, 2) . "\n" . number_format($payroll->ecash, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(600, $dataCellBorderStyle)->addText(number_format($payroll->ea, 2) . "\n" . number_format($payroll->coop, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(600, $dataCellBorderStyle)->addText(number_format($payroll->pagibig_mp2, 2) . "\n", $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(700, $dataCellBorderStyle)->addText(number_format($payroll->w_tax, 2) . "\n" . number_format($payroll->u_mid, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(700, $dataCellBorderStyle)->addText(number_format($payroll->res_sal, 2) . "\n" . number_format($payroll->loan_emergency, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(800, $dataCellBorderStyle)->addText(number_format($payroll->total_deduction, 2) . "\n" . number_format($payroll->net_pay, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(800, $dataCellBorderStyle)->addText(number_format($payroll->first_pay, 2) . "\n" . number_format($payroll->second_pay, 2), $dataFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(1000, $dataCellBorderStyle)->addText($escapeXml($payroll->remarks), $dataFontStyle, ['spaceAfter' => 0]);
            }

            // Font style for totals: blue color
            $totalFontStyle = ['bold' => true, 'size' => 8, 'color' => $defaultFontColor];
            $totalLabelFontStyle = ['bold' => true, 'size' => 8, 'italic' => true, 'color' => $defaultFontColor];

            // Totals Row
            $totalRow = $mainTable->addRow();
            $totalRow->addCell(400, $headerCellBorderStyle)->addText('', [], ['spaceAfter' => 0]);
            $totalRow->addCell(2000, $headerCellBorderStyle)->addText('SUB-TOTALS', $totalLabelFontStyle, ['spaceAfter' => 0]);
            $totalRow->addCell(700, $headerCellBorderStyle)->addText(number_format($totals['basic'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(700, $headerCellBorderStyle)->addText(number_format($totals['step_inc'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(600, $headerCellBorderStyle)->addText(number_format($totals['diff'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(700, $headerCellBorderStyle)->addText(number_format($totals['adcomp'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(700, $headerCellBorderStyle)->addText(number_format($totals['pera'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(800, $headerCellBorderStyle)->addText(number_format($totals['gross'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(700, $headerCellBorderStyle)->addText(number_format($totals['gsis'], 2) . "\n" . number_format($totals['medicare'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(600, $headerCellBorderStyle)->addText(number_format($totals['gsis_ge'], 2) . "\n" . number_format($totals['gsis_cpl'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(600, $headerCellBorderStyle)->addText(number_format($totals['p_prem'], 2) . "\n" . number_format($totals['p_mpl'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(700, $headerCellBorderStyle)->addText(number_format($totals['gsis_opt'], 2) . "\n" . number_format($totals['gsis_mplite'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(800, $headerCellBorderStyle)->addText(number_format($totals['gsis_conso'], 2) . "\n" . number_format($totals['pol_loan'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(700, $headerCellBorderStyle)->addText(number_format($totals['pagcal'], 2) . "\n" . number_format($totals['phsg_loan'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(800, $headerCellBorderStyle)->addText(number_format($totals['gsis_hsg'], 2) . "\n" . number_format($totals['gsis_pabahay'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(700, $headerCellBorderStyle)->addText(number_format($totals['pagibig'], 2) . "\n" . number_format($totals['pagibig_gs'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(700, $headerCellBorderStyle)->addText(number_format($totals['pagibig_loan'], 2) . "\n", $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(700, $headerCellBorderStyle)->addText(number_format($totals['philhealth'], 2) . "\n" . number_format($totals['philhealth_employer'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(600, $headerCellBorderStyle)->addText(number_format($totals['others'], 2) . "\n", $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(600, $headerCellBorderStyle)->addText(number_format($totals['hmo'], 2) . "\n" . number_format($totals['ecash'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(600, $headerCellBorderStyle)->addText(number_format($totals['ea'], 2) . "\n" . number_format($totals['coop'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(600, $headerCellBorderStyle)->addText(number_format($totals['pagibig_mp2'], 2) . "\n", $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(700, $headerCellBorderStyle)->addText(number_format($totals['w_tax'], 2) . "\n" . number_format($totals['u_mid'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(700, $headerCellBorderStyle)->addText(number_format($totals['res_sal'], 2) . "\n" . number_format($totals['loan_emergency'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(800, $headerCellBorderStyle)->addText(number_format($totals['deduction'], 2) . "\n" . number_format($totals['net'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(800, $headerCellBorderStyle)->addText(number_format($totals['first'], 2) . "\n" . number_format($totals['second'], 2), $totalFontStyle, ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(1000, $headerCellBorderStyle)->addText('', [], ['spaceAfter' => 0]);

            // Font style for signature section: blue color
            $sigFontStyle = ['size' => 10, 'color' => $defaultFontColor];
            $sigBoldFontStyle = ['bold' => true, 'size' => 10, 'color' => $defaultFontColor];

            // Signature Section
            $section->addText('', [], ['spaceAfter' => 200]);
            $sigTable = $section->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 50]);
            $sigRow = $sigTable->addRow();

            $sigCell1 = $sigRow->addCell(5000);
            $sigCell1->addText('I CERTIFY on my oath that the above Payroll is Correct and that the services have been duly rendered as stated.', $sigFontStyle, ['spaceAfter' => 200]);
            $sigCell1->addText($escapeXml($signatories['signatory_1']), $sigBoldFontStyle, ['spaceAfter' => 0]);
            $sigCell1->addText($escapeXml($signatories['signatory_position_1']), $sigFontStyle, ['spaceAfter' => 0]);

            $sigCell2 = $sigRow->addCell(5000);
            $sigCell2->addText('APPROVED, payable for appropriation for P __________', $sigFontStyle, ['spaceAfter' => 200]);
            $sigCell2->addText($escapeXml($signatories['signatory_2']), $sigBoldFontStyle, ['spaceAfter' => 0]);
            $sigCell2->addText($escapeXml($signatories['signatory_position_2']), $sigFontStyle, ['spaceAfter' => 0]);

            // Save to temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'general_payroll_');
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            // Read the file content
            $docxContent = file_get_contents($tempFile);
            unlink($tempFile);

            $filename = 'general_payroll_report_' . $id . '_' . date('Y-m-d') . '.docx';

            return response($docxContent)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($docxContent));
        } catch (\Exception $e) {
            \Log::error('General Payroll Report DOCX Generation Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('Request data: ' . json_encode($request->all()));
            return $this->serverErrorResponse('Failed to generate General Payroll Report DOCX: ' . $e->getMessage());
        }
    }

    /**
     * Generate General Payroll Report Excel
     */
    public function payrollSummaryPrintExcel(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $validator = \Validator::make($request->all(), [
                'payroll_interval_id' => 'required',
                'payroll_period_id' => 'required',
                'payroll_period_id_2' => 'nullable',
            ], [
                'payroll_interval_id.required' => 'Payroll Interval is required.',
                'payroll_period_id.required' => 'Payroll Period is required.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $id = $request->payroll_period_id;
            $id2 = $request->payroll_period_id_2;
            $divisionFilter = $this->resolveDivisionFilterFromRequest($request);
            $branch_id = $request->branch_id;

            // Auto-resolve month pair when UI provides a single monthly option
            if (empty($id2)) {
                [$resolvedFirstId, $resolvedSecondId] = $this->resolveMonthlyPayrollPeriodPair($id);
                if (!empty($resolvedSecondId) && (string) $resolvedSecondId !== (string) $resolvedFirstId) {
                    $id = $resolvedFirstId;
                    $id2 = $resolvedSecondId;
                } else {
                    $id = $resolvedFirstId;
                }
            }

            $companies = DB::table('companies')->get();

            $departmentName = $this->getDivisionDisplayName($divisionFilter);

            // Load payroll summaries for the selected period (and optional second period)
            $payrollsFirstHalf = $this->fetchGeneralPayrollSummariesForPeriod($id, $divisionFilter, $app_key);
            $payrolls = $payrollsFirstHalf;

            if (!empty($id2)) {
                $payrollsSecondHalf = $this->fetchGeneralPayrollSummariesForPeriod($id2, $divisionFilter, $app_key);
                $payrolls = $this->mergePayrollHalves($payrollsFirstHalf, $payrollsSecondHalf);
            }

            $periodIds = !empty($id2) ? [$id, $id2] : [$id];

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    'attendance_start_date',
                    'attendance_end_date',
                    'release_date',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name")
                )
                ->whereIn('a.id', $periodIds)
                ->orderBy('a.attendance_start_date', 'asc')
                ->get();

            if ($data->isEmpty()) {
                return $this->serverErrorResponse('Payroll period not found.');
            }

            $firstPeriod = $data->first();
            $lastPeriod = $data->last();

            $payroll_period = date('F d', strtotime($firstPeriod->attendance_start_date)) . ' - ' . date('d, Y', strtotime($lastPeriod->attendance_end_date));
            $payroll_month = date(
                'F',
                strtotime($lastPeriod->release_date ?? $lastPeriod->attendance_end_date)
            );
            $payroll_year = date(
                'Y',
                strtotime($lastPeriod->release_date ?? $lastPeriod->attendance_end_date)
            );

            $income_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('incomes as c', 'b.income_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.income_id',
                    'c.name as income'
                )
                ->whereIn('e.id', $periodIds)
                ->where('b.active', true)
                ->whereIn('c.id', function ($query) use ($periodIds) {
                    $query->select('income_id')->from('payroll_incomes')
                        ->where('amount', '>', 0)
                        ->whereIn('payroll_period_id', $periodIds)
                        ->distinct();
                })
                ->distinct()
                ->get();

            $deduction_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('deductions as c', 'b.deduction_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.deduction_id',
                    'c.name as deduction'
                )
                ->whereIn('e.id', $periodIds)
                ->where('b.active', true)
                ->whereIn('c.id', function ($query) use ($periodIds) {
                    $query->select('deduction_id')->from('payroll_deductions')
                        ->where('amount', '>', 0)
                        ->whereIn('payroll_period_id', $periodIds)
                        ->distinct();
                })
                ->distinct()
                ->orderBy('b.deduction_id', 'asc')
                ->get();

            $payrolls = $this->attachUnlistedLoanOtherDeductionsForGeneralPayroll($payrolls, $periodIds, $deduction_headers);

            // get all income items.
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'b.id as income_id',
                    'a.employee_id',
                    'b.name',
                    DB::raw('SUM(a.amount) as amount')
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->groupBy('b.id', 'a.employee_id', 'b.name')
                ->get();

            // get all deduction items.
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.id as deduction_id',
                    'a.employee_id',
                    'b.name',
                    DB::raw('SUM(a.amount) as amount')
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->groupBy('b.id', 'a.employee_id', 'b.name')
                ->get();

            // Helper function to get acronym
            $acronym = function ($text) {
                if (!$text) {
                    return '';
                }
                $stopWords = ['OF', 'THE', 'AND', 'IN'];
                $words = preg_split('/\s+/', trim($text));
                $letters = collect($words)
                    ->map(fn($w) => strtoupper($w))
                    ->reject(fn($w) => in_array($w, $stopWords))
                    ->map(fn($w) => $w[0] ?? '')
                    ->filter()
                    ->join('');
                return $letters ?: strtoupper(substr(trim($text), 0, 1));
            };

            // Build income and deduction lookups
            $incomeLookup = collect($incomes ?? [])
                ->groupBy('employee_id')
                ->map(fn($items) => $items->keyBy('income_id')->map(fn($item) => (array) $item)->toArray())
                ->toArray();

            $deductionLookup = collect($deductions ?? [])
                ->groupBy('employee_id')
                ->map(fn($items) => $items->keyBy('deduction_id')->map(fn($item) => (array) $item)->toArray())
                ->toArray();

            $peraHeader = collect($income_headers ?? [])->first(fn($header) => stripos($header->income ?? '', 'pera') !== false);
            $mpliteHeader = collect($deduction_headers ?? [])->first(fn($header) => stripos($header->deduction ?? '', 'mplite') !== false);
            $pabahayHeader = collect($deduction_headers ?? [])->first(fn($header) => stripos($header->deduction ?? '', 'pabahay') !== false);
            $consoHeader = collect($deduction_headers ?? [])->first(fn($header) => stripos($header->deduction ?? '', 'conso') !== false);
            $geHeader = collect($deduction_headers ?? [])->first(fn($header) => stripos($header->deduction ?? '', 'gsis ge') !== false);
            $cplHeader = collect($deduction_headers ?? [])->first(fn($header) => stripos($header->deduction ?? '', 'gsis cpl') !== false)
                ?? collect($deduction_headers ?? [])->first(fn($header) => stripos($header->deduction ?? '', 'cpl') !== false);
            $pPremHeader = collect($deduction_headers ?? [])->first(fn($header) => (
                stripos($header->deduction ?? '', 'p prem') !== false
                || stripos($header->deduction ?? '', 'p_prem') !== false
                || stripos($header->deduction ?? '', 'p-prem') !== false
            ));
            $pMplHeader = collect($deduction_headers ?? [])->first(fn($header) => (
                stripos($header->deduction ?? '', 'p mpl') !== false
                || stripos($header->deduction ?? '', 'p_mpl') !== false
                || stripos($header->deduction ?? '', 'p-mpl') !== false
            ));

            // Calculate totals
            $totals = [
                'basic' => 0,
                'step_inc' => 0,
                'diff' => 0,
                'adcomp' => 0,
                'pera' => 0,
                'gross' => 0,
                'gsis' => 0,
                'medicare' => 0,
                'gsis_ge' => 0,
                'gsis_cpl' => 0,
                'p_prem' => 0,
                'p_mpl' => 0,
                'gsis_opt' => 0,
                'gsis_mplite' => 0,
                'gsis_conso' => 0,
                'pol_loan' => 0,
                'pagcal' => 0,
                'phsg_loan' => 0,
                'gsis_hsg' => 0,
                'gsis_pabahay' => 0,
                'pagibig' => 0,
                'pagibig_gs' => 0,
                'pagibig_loan' => 0,
                'philhealth' => 0,
                'philhealth_employer' => 0,
                'others' => 0,
                'hmo' => 0,
                'ecash' => 0,
                'ea' => 0,
                'coop' => 0,
                'pagibig_mp2' => 0,
                'w_tax' => 0,
                'u_mid' => 0,
                'res_sal' => 0,
                'loan_emergency' => 0,
                'deduction' => 0,
                'net' => 0,
                'first' => 0,
                'second' => 0,
            ];

            // Process payrolls and calculate values (matching blade template logic)
            $processedPayrolls = collect($payrolls)->map(function ($payroll) use ($incomeLookup, $deductionLookup, $peraHeader, $mpliteHeader, $pabahayHeader, $consoHeader, $geHeader, $cplHeader, $pPremHeader, $pMplHeader, &$totals, $acronym) {
                $stepInc = data_get($payroll, 'step_increment', 0);
                $diff = data_get($payroll, 'differential', 0);
                $adComp = data_get($payroll, 'additional_comp', 0);
                $pera = data_get($payroll, 'pera', 0);
                if ((!$pera || $pera == 0) && $peraHeader) {
                    $pera = data_get($incomeLookup, "{$payroll->employee_id}.{$peraHeader->income_id}.amount", 0);
                }
                $gsis = $payroll->gsis ?? 0;
                $medicare = data_get($payroll, 'medicare', 0);
                $gsisGe = data_get($payroll, 'gsis_ge', 0);
                $gsisCpl = data_get($payroll, 'gsis_cpl', 0);
                $pPrem = data_get($payroll, 'p_prem', 0);
                $pMpl = data_get($payroll, 'p_mpl', 0);
                if ((!$gsisGe || $gsisGe == 0) && $geHeader) {
                    $gsisGe = data_get($deductionLookup, "{$payroll->employee_id}.{$geHeader->deduction_id}.amount", 0);
                }
                if ((!$gsisCpl || $gsisCpl == 0) && $cplHeader) {
                    $gsisCpl = data_get($deductionLookup, "{$payroll->employee_id}.{$cplHeader->deduction_id}.amount", 0);
                }
                if ((!$pPrem || $pPrem == 0) && $pPremHeader) {
                    $pPrem = data_get($deductionLookup, "{$payroll->employee_id}.{$pPremHeader->deduction_id}.amount", 0);
                }
                if ((!$pMpl || $pMpl == 0) && $pMplHeader) {
                    $pMpl = data_get($deductionLookup, "{$payroll->employee_id}.{$pMplHeader->deduction_id}.amount", 0);
                }
                $gsisOpt = data_get($payroll, 'gsis_opt_life', 0);
                $gsisMpl = data_get($payroll, 'gsis_mpl', 0);
                $gsisMplite = data_get($payroll, 'gsis_mplite', 0);
                if ((!$gsisMplite || $gsisMplite == 0) && $mpliteHeader) {
                    $gsisMplite = data_get($deductionLookup, "{$payroll->employee_id}.{$mpliteHeader->deduction_id}.amount", 0);
                }
                $gsisConso = data_get($payroll, 'gsis_conso', $gsisMpl);
                if ((!$gsisConso || $gsisConso == 0) && $consoHeader) {
                    $gsisConso = data_get($deductionLookup, "{$payroll->employee_id}.{$consoHeader->deduction_id}.amount", 0);
                }
                $polLoan = data_get($payroll, 'pol_loan', data_get($payroll, 'gsis_policy', 0));
                $pagcal = data_get($payroll, 'pagcal', 0);
                $phsgLoan = data_get($payroll, 'phsg_loan', 0);
                $gsisHsg = data_get($payroll, 'gsis_hsg', data_get($payroll, 'gsis_phsg', 0));
                $gsisPabahay = data_get($payroll, 'gsis_pabahay', data_get($payroll, 'gsis_pahabay', 0));
                if ((!$gsisPabahay || $gsisPabahay == 0) && $pabahayHeader) {
                    $gsisPabahay = data_get($deductionLookup, "{$payroll->employee_id}.{$pabahayHeader->deduction_id}.amount", 0);
                }
                $pagibig = $payroll->pagibig ?? 0;
                $pagibigGs = data_get($payroll, 'pagibig_gs', 0);
                $pagibigLoan = data_get($payroll, 'pagibig_loan', 0);
                $philhealth = $payroll->philhealth ?? 0;
                $philhealthEmployer = data_get($payroll, 'philhealth_employer', 0);
                $wTax = (float) data_get($payroll, 'withholding_tax', data_get($payroll, 'w_tax', data_get($payroll, 'tax', 0)));
                $sssDeduct = (float) data_get($payroll, 'sss', 0);
                $precedingAdj = (float) data_get($payroll, 'preceding_period_adjustment', 0);
                if (isset($payroll->other_deductions)) {
                    $other = max(0, (float) $payroll->other_deductions);
                } else {
                    $other = ($payroll->total_deduction ?? 0) - ($gsis + $gsisGe + $gsisCpl + $pPrem + $pMpl + $gsisOpt + $gsisMpl + $gsisHsg + $gsisPabahay + $pagibig + $pagibigLoan + $philhealth + $wTax + $sssDeduct + $precedingAdj);
                    $other = max($other, 0);
                }
                $totalDeduction = ($payroll->total_deduction ?? 0) ?: ($gsis + $pagibig + $philhealth + $other);
                $firstPay = data_get($payroll, 'first_pay', ($payroll->net_pay ?? 0) / 2);
                $secondPay = data_get($payroll, 'second_pay', ($payroll->net_pay ?? 0) / 2);
                $hmo = data_get($payroll, 'hmo', 0);
                $ecash = data_get($payroll, 'ecash', 0);
                $ea = data_get($payroll, 'ea', 0);
                $coop = data_get($payroll, 'coop', 0);
                $pagibigMp2 = data_get($payroll, 'pagibig_mp2', 0);
                $uMid = data_get($payroll, 'u_mid', 0);
                $resSal = data_get($payroll, 'res_sal', 0);
                $loanEmergency = data_get($payroll, 'loan_emergency', 0);
                $basicUndertime = data_get($payroll, 'basic_undertime', 0);
                $stepIncUndertime = data_get($payroll, 'step_inc_undertime', 0);
                $diffUndertime = data_get($payroll, 'diff_undertime', 0);
                $adcompUndertime = data_get($payroll, 'adcomp_undertime', 0);
                $peraUndertime = data_get($payroll, 'pera_undertime', 0);

                // Update totals
                $totals['basic'] += $payroll->salary ?? 0;
                $totals['step_inc'] += $stepInc;
                $totals['diff'] += $diff;
                $totals['adcomp'] += $adComp;
                $totals['pera'] += $pera;
                $totals['gross'] += $payroll->gross_amount ?? 0;
                $totals['gsis'] += $gsis;
                $totals['philhealth'] += $medicare;
                $totals['gsis_ge'] += $gsisGe;
                $totals['gsis_cpl'] += $gsisCpl;
                $totals['p_prem'] += $pPrem;
                $totals['p_mpl'] += $pMpl;
                $totals['gsis_opt'] += $gsisOpt;
                $totals['gsis_mplite'] += $gsisMplite;
                $totals['gsis_conso'] += $gsisConso;
                $totals['pol_loan'] += $polLoan;
                $totals['pagcal'] += $pagcal;
                $totals['phsg_loan'] += $phsgLoan;
                $totals['gsis_hsg'] += $gsisHsg;
                $totals['gsis_pabahay'] += $gsisPabahay;
                $totals['pagibig'] += $pagibig;
                $totals['pagibig_gs'] += $pagibigGs;
                $totals['pagibig_loan'] += $pagibigLoan;
                $totals['philhealth'] += $philhealth;
                $totals['philhealth_employer'] += $philhealthEmployer;
                $totals['others'] += $other;
                $totals['hmo'] += $hmo;
                $totals['ecash'] += $ecash;
                $totals['ea'] += $ea;
                $totals['coop'] += $coop;
                $totals['pagibig_mp2'] += $pagibigMp2;
                $totals['w_tax'] += $wTax;
                $totals['u_mid'] += $uMid;
                $totals['res_sal'] += $resSal;
                $totals['loan_emergency'] += $loanEmergency;
                $totals['deduction'] += $totalDeduction;
                $totals['net'] += $payroll->net_pay ?? 0;
                $totals['first'] += $firstPay;
                $totals['second'] += $secondPay;

                return (object)[
                    'div' => $acronym($payroll->department),
                    'name' => strtoupper($payroll->name),
                    'position' => $payroll->position,
                    'basic' => $payroll->salary ?? 0,
                    'basic_undertime' => $basicUndertime,
                    'step_inc' => $stepInc,
                    'step_inc_undertime' => $stepIncUndertime,
                    'diff' => $diff,
                    'diff_undertime' => $diffUndertime,
                    'adcomp' => $adComp,
                    'adcomp_undertime' => $adcompUndertime,
                    'pera' => $pera,
                    'pera_undertime' => $peraUndertime,
                    'gross' => $payroll->gross_amount ?? 0,
                    'gsis' => $gsis,
                    'medicare' => $medicare,
                    'gsis_ge' => $gsisGe,
                    'gsis_cpl' => $gsisCpl,
                    'p_prem' => $pPrem,
                    'p_mpl' => $pMpl,
                    'gsis_opt' => $gsisOpt,
                    'gsis_mplite' => $gsisMplite,
                    'gsis_conso' => $gsisConso,
                    'pol_loan' => $polLoan,
                    'pagcal' => $pagcal,
                    'phsg_loan' => $phsgLoan,
                    'gsis_hsg' => $gsisHsg,
                    'gsis_pabahay' => $gsisPabahay,
                    'pagibig' => $pagibig,
                    'pagibig_gs' => $pagibigGs,
                    'pagibig_loan' => $pagibigLoan,
                    'philhealth' => $philhealth,
                    'philhealth_employer' => $philhealthEmployer,
                    'other' => $other,
                    'hmo' => $hmo,
                    'ecash' => $ecash,
                    'ea' => $ea,
                    'coop' => $coop,
                    'pagibig_mp2' => $pagibigMp2,
                    'w_tax' => $wTax,
                    'u_mid' => $uMid,
                    'res_sal' => $resSal,
                    'loan_emergency' => $loanEmergency,
                    'total_deduction' => $totalDeduction,
                    'net_pay' => $payroll->net_pay ?? 0,
                    'first_pay' => $firstPay,
                    'second_pay' => $secondPay,
                    'remarks' => $payroll->remarks ?? '',
                ];
            });

            $signatories = [
                'signatory_1' => $request->signatory_1 ?? '',
                'signatory_position_1' => $request->signatory_position_1 ?? 'Chief Administrative Officer',
                'signatory_2' => $request->signatory_2 ?? '',
                'signatory_position_2' => $request->signatory_position_2 ?? 'Budget Officer',
            ];

            // Create Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('General Payroll');

            // Set page orientation to landscape
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_TABLOID);

            // Default font color for all text
            $defaultFontColor = '1a4c8f';

            $currentRow = 1;

            // Title
            $sheet->setCellValue('A' . $currentRow, 'GENERAL PAYROLL');
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(22)->setColor(new Color($defaultFontColor));
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('A' . $currentRow . ':AA' . $currentRow);
            $currentRow++;

            // Subtext
            $subtext = 'WE HEREBY ACKNOWLEDGE to have received of the Phil. Trade Training Center the sums opposite our names for the month of ' . $payroll_month . ' ' . $payroll_year;
            $sheet->setCellValue('A' . $currentRow, $subtext);
            $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10)->setColor(new Color($defaultFontColor));
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('A' . $currentRow . ':AA' . $currentRow);
            $currentRow++;

            // Division/Office
            $sheet->setCellValue('A' . $currentRow, 'DIVISION / OFFICE: ' . ($payrolls[0]->department ?? ''));
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(8)->setColor(new Color($defaultFontColor));
            $currentRow += 2;

            // Column headers mapping: A=div, B=name, C=basic, D=step_inc, E=diff, F=adcomp, G=pera, H=gross, I=life/ret, J=ge, K=p_prem, L=opt_life, M=gsis_conso, N=pagcal, O=gsis_hsg, P=hdmf, Q=hdmf_loan, R=phic, S=others, T=hmo, U=ea, V=pagibig_mp2, W=w_tax, X=res_sal, Y=deductions, Z=first_pay (with second_pay below), AA=remarks
            $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA'];

            // Header Row 1
            $headerRow1 = $currentRow;
            $sheet->setCellValue('A' . $headerRow1, 'DIV');
            $sheet->setCellValue('B' . $headerRow1, 'NAME');
            $sheet->setCellValue('C' . $headerRow1, 'BASIC');
            $sheet->setCellValue('D' . $headerRow1, 'STEP INC');
            $sheet->setCellValue('E' . $headerRow1, 'DIFF');
            $sheet->setCellValue('F' . $headerRow1, 'ADCOMP');
            $sheet->setCellValue('G' . $headerRow1, 'PERA');
            $sheet->setCellValue('H' . $headerRow1, 'GROSS');
            $sheet->setCellValue('I' . $headerRow1, 'LIFE / RET');
            $sheet->setCellValue('J' . $headerRow1, 'GE');
            $sheet->setCellValue('K' . $headerRow1, 'P PREM');
            $sheet->setCellValue('L' . $headerRow1, 'OPT LIFE INS');
            $sheet->setCellValue('M' . $headerRow1, 'GSIS CONSO/MPL');
            $sheet->setCellValue('N' . $headerRow1, 'PAGCal');
            $sheet->setCellValue('O' . $headerRow1, 'GSIS HSG');
            $sheet->setCellValue('P' . $headerRow1, 'HDMF');
            $sheet->setCellValue('Q' . $headerRow1, 'HDMF');
            $sheet->setCellValue('R' . $headerRow1, 'PHIC');
            $sheet->setCellValue('S' . $headerRow1, 'OTHERS');
            $sheet->setCellValue('T' . $headerRow1, 'HMO');
            $sheet->setCellValue('U' . $headerRow1, 'EA');
            $sheet->setCellValue('V' . $headerRow1, 'PAGIBIG MP2');
            $sheet->setCellValue('W' . $headerRow1, 'W TAX');
            $sheet->setCellValue('X' . $headerRow1, 'RES SAL');
            $sheet->setCellValue('Y' . $headerRow1, 'DEDUCTIONS');
            $sheet->setCellValue('Z' . $headerRow1, 'FIRST PAY');
            $sheet->setCellValue('AA' . $headerRow1, 'REMARKS');

            // Apply header styling
            foreach ($columns as $col) {
                $sheet->getStyle($col . $headerRow1)->getFont()->setBold(true)->setSize(9)->setColor(new Color($defaultFontColor));
                $sheet->getStyle($col . $headerRow1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            }

            // Header Row 2 (subheaders)
            $headerRow2 = $currentRow + 1;
            $sheet->setCellValue('B' . $headerRow2, 'DESIGNATION');
            $sheet->setCellValue('C' . $headerRow2, 'UNDERTIME');
            $sheet->setCellValue('D' . $headerRow2, 'UNDERTIME');
            $sheet->setCellValue('E' . $headerRow2, 'UNDERTIME');
            $sheet->setCellValue('F' . $headerRow2, 'UNDERTIME');
            $sheet->setCellValue('G' . $headerRow2, 'UNDERTIME');
            $sheet->setCellValue('I' . $headerRow2, 'MEDICARE');
            $sheet->setCellValue('J' . $headerRow2, 'GSISCPL');
            $sheet->setCellValue('K' . $headerRow2, 'P_MPL');
            $sheet->setCellValue('L' . $headerRow2, 'GSIS MPLite');
            $sheet->setCellValue('M' . $headerRow2, 'POL LOAN');
            $sheet->setCellValue('N' . $headerRow2, 'P-HSG LOAN');
            $sheet->setCellValue('O' . $headerRow2, 'GSIS PABAHAY');
            $sheet->setCellValue('P' . $headerRow2, 'G/S');
            $sheet->setCellValue('Q' . $headerRow2, 'LOAN');
            $sheet->setCellValue('R' . $headerRow2, 'G/S');
            $sheet->setCellValue('T' . $headerRow2, 'ECASH++');
            $sheet->setCellValue('U' . $headerRow2, 'COOP');
            $sheet->setCellValue('W' . $headerRow2, 'U_MID');
            $sheet->setCellValue('X' . $headerRow2, 'LOAN EMERGENCY');
            $sheet->setCellValue('Y' . $headerRow2, 'NET PAY');
            $sheet->setCellValue('Z' . $headerRow2, 'SECOND PAY');

            // Apply subheader styling
            foreach ($columns as $col) {
                $sheet->getStyle($col . $headerRow2)->getFont()->setBold(true)->setSize(8)->setColor(new Color($defaultFontColor));
                $sheet->getStyle($col . $headerRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            }

            // Merge header cells for stacked headers
            $sheet->mergeCells('A' . $headerRow1 . ':A' . $headerRow2);
            $sheet->mergeCells('B' . $headerRow1 . ':B' . $headerRow2);
            $sheet->mergeCells('H' . $headerRow1 . ':H' . $headerRow2);
            $sheet->mergeCells('S' . $headerRow1 . ':S' . $headerRow2);
            $sheet->mergeCells('V' . $headerRow1 . ':V' . $headerRow2);
            $sheet->mergeCells('AA' . $headerRow1 . ':AA' . $headerRow2);

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(10);
            $sheet->getColumnDimension('D')->setWidth(10);
            $sheet->getColumnDimension('E')->setWidth(8);
            $sheet->getColumnDimension('F')->setWidth(10);
            $sheet->getColumnDimension('G')->setWidth(10);
            $sheet->getColumnDimension('H')->setWidth(12);
            $sheet->getColumnDimension('I')->setWidth(12);
            $sheet->getColumnDimension('J')->setWidth(8);
            $sheet->getColumnDimension('K')->setWidth(8);
            $sheet->getColumnDimension('L')->setWidth(12);
            $sheet->getColumnDimension('M')->setWidth(15);
            $sheet->getColumnDimension('N')->setWidth(10);
            $sheet->getColumnDimension('O')->setWidth(12);
            $sheet->getColumnDimension('P')->setWidth(10);
            $sheet->getColumnDimension('Q')->setWidth(10);
            $sheet->getColumnDimension('R')->setWidth(10);
            $sheet->getColumnDimension('S')->setWidth(8);
            $sheet->getColumnDimension('T')->setWidth(8);
            $sheet->getColumnDimension('U')->setWidth(8);
            $sheet->getColumnDimension('V')->setWidth(12);
            $sheet->getColumnDimension('W')->setWidth(10);
            $sheet->getColumnDimension('X')->setWidth(12);
            $sheet->getColumnDimension('Y')->setWidth(12);
            $sheet->getColumnDimension('Z')->setWidth(12);
            $sheet->getColumnDimension('AA')->setWidth(15);

            // Border style for data rows: bottom border blue, other borders white
            $dataBorderStyle = [
                'borders' => [
                    'left' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'top' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '1a4c8f'],
                    ],
                ],
            ];

            // Header border style (all borders)
            $headerBorderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '1a4c8f'],
                    ],
                ],
            ];
            $sheet->getStyle('A' . $headerRow1 . ':AA' . $headerRow2)->applyFromArray($headerBorderStyle);

            $currentRow = $headerRow2 + 1;

            // Data Rows
            foreach ($processedPayrolls as $payroll) {
                $sheet->setCellValue('A' . $currentRow, $payroll->div);
                $sheet->setCellValue('B' . $currentRow, $payroll->name . "\n" . $payroll->position);
                $sheet->setCellValue('C' . $currentRow, number_format($payroll->basic, 2) . "\n" . number_format($payroll->basic_undertime, 2));
                $sheet->setCellValue('D' . $currentRow, number_format($payroll->step_inc, 2) . "\n" . number_format($payroll->step_inc_undertime, 2));
                $sheet->setCellValue('E' . $currentRow, number_format($payroll->diff, 2) . "\n" . number_format($payroll->diff_undertime, 2));
                $sheet->setCellValue('F' . $currentRow, number_format($payroll->adcomp, 2) . "\n" . number_format($payroll->adcomp_undertime, 2));
                $sheet->setCellValue('G' . $currentRow, number_format($payroll->pera, 2) . "\n" . number_format($payroll->pera_undertime, 2));
                $sheet->setCellValue('H' . $currentRow, number_format($payroll->gross, 2));
                $sheet->setCellValue('I' . $currentRow, number_format($payroll->gsis, 2) . "\n" . number_format($payroll->medicare, 2));
                $sheet->setCellValue('J' . $currentRow, number_format($payroll->gsis_ge, 2) . "\n" . number_format($payroll->gsis_cpl, 2));
                $sheet->setCellValue('K' . $currentRow, number_format($payroll->p_prem, 2) . "\n" . number_format($payroll->p_mpl, 2));
                $sheet->setCellValue('L' . $currentRow, number_format($payroll->gsis_opt, 2) . "\n" . number_format($payroll->gsis_mplite, 2));
                $sheet->setCellValue('M' . $currentRow, number_format($payroll->gsis_conso, 2) . "\n" . number_format($payroll->pol_loan, 2));
                $sheet->setCellValue('N' . $currentRow, number_format($payroll->pagcal, 2) . "\n" . number_format($payroll->phsg_loan, 2));
                $sheet->setCellValue('O' . $currentRow, number_format($payroll->gsis_hsg, 2) . "\n" . number_format($payroll->gsis_pabahay, 2));
                $sheet->setCellValue('P' . $currentRow, number_format($payroll->pagibig, 2) . "\n" . number_format($payroll->pagibig_gs, 2));
                $sheet->setCellValue('Q' . $currentRow, number_format($payroll->pagibig_loan, 2));
                $sheet->setCellValue('R' . $currentRow, number_format($payroll->philhealth, 2) . "\n" . number_format($payroll->philhealth_employer, 2));
                $sheet->setCellValue('S' . $currentRow, number_format($payroll->other, 2));
                $sheet->setCellValue('T' . $currentRow, number_format($payroll->hmo, 2) . "\n" . number_format($payroll->ecash, 2));
                $sheet->setCellValue('U' . $currentRow, number_format($payroll->ea, 2) . "\n" . number_format($payroll->coop, 2));
                $sheet->setCellValue('V' . $currentRow, number_format($payroll->pagibig_mp2, 2));
                $sheet->setCellValue('W' . $currentRow, number_format($payroll->w_tax, 2) . "\n" . number_format($payroll->u_mid, 2));
                $sheet->setCellValue('X' . $currentRow, number_format($payroll->res_sal, 2) . "\n" . number_format($payroll->loan_emergency, 2));
                $sheet->setCellValue('Y' . $currentRow, number_format($payroll->total_deduction, 2) . "\n" . number_format($payroll->net_pay, 2));
                $sheet->setCellValue('Z' . $currentRow, number_format($payroll->first_pay, 2) . "\n" . number_format($payroll->second_pay, 2));
                $sheet->setCellValue('AA' . $currentRow, $payroll->remarks);

                // Apply styling to data row
                foreach ($columns as $col) {
                    $sheet->getStyle($col . $currentRow)->getFont()->setSize(8)->setColor(new Color($defaultFontColor));
                    $sheet->getStyle($col . $currentRow)->getAlignment()->setWrapText(true);
                    if (in_array($col, ['A', 'B', 'AA'])) {
                        $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    } else {
                        $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }
                $sheet->getStyle('B' . $currentRow)->getFont()->setBold(true)->setColor(new Color($defaultFontColor));
                $sheet->getStyle('A' . $currentRow . ':AA' . $currentRow)->applyFromArray($dataBorderStyle);

                $currentRow++;
            }

            // Totals Row
            $sheet->setCellValue('A' . $currentRow, '');
            $sheet->setCellValue('B' . $currentRow, 'SUB-TOTALS');
            $sheet->setCellValue('C' . $currentRow, number_format($totals['basic'], 2));
            $sheet->setCellValue('D' . $currentRow, number_format($totals['step_inc'], 2));
            $sheet->setCellValue('E' . $currentRow, number_format($totals['diff'], 2));
            $sheet->setCellValue('F' . $currentRow, number_format($totals['adcomp'], 2));
            $sheet->setCellValue('G' . $currentRow, number_format($totals['pera'], 2));
            $sheet->setCellValue('H' . $currentRow, number_format($totals['gross'], 2));
            $sheet->setCellValue('I' . $currentRow, number_format($totals['gsis'], 2) . "\n" . number_format($totals['medicare'], 2));
            $sheet->setCellValue('J' . $currentRow, number_format($totals['gsis_ge'], 2) . "\n" . number_format($totals['gsis_cpl'], 2));
            $sheet->setCellValue('K' . $currentRow, number_format($totals['p_prem'], 2) . "\n" . number_format($totals['p_mpl'], 2));
            $sheet->setCellValue('L' . $currentRow, number_format($totals['gsis_opt'], 2) . "\n" . number_format($totals['gsis_mplite'], 2));
            $sheet->setCellValue('M' . $currentRow, number_format($totals['gsis_conso'], 2) . "\n" . number_format($totals['pol_loan'], 2));
            $sheet->setCellValue('N' . $currentRow, number_format($totals['pagcal'], 2) . "\n" . number_format($totals['phsg_loan'], 2));
            $sheet->setCellValue('O' . $currentRow, number_format($totals['gsis_hsg'], 2) . "\n" . number_format($totals['gsis_pabahay'], 2));
            $sheet->setCellValue('P' . $currentRow, number_format($totals['pagibig'], 2) . "\n" . number_format($totals['pagibig_gs'], 2));
            $sheet->setCellValue('Q' . $currentRow, number_format($totals['pagibig_loan'], 2));
            $sheet->setCellValue('R' . $currentRow, number_format($totals['philhealth'], 2) . "\n" . number_format($totals['philhealth_employer'], 2));
            $sheet->setCellValue('S' . $currentRow, number_format($totals['others'], 2));
            $sheet->setCellValue('T' . $currentRow, number_format($totals['hmo'], 2) . "\n" . number_format($totals['ecash'], 2));
            $sheet->setCellValue('U' . $currentRow, number_format($totals['ea'], 2) . "\n" . number_format($totals['coop'], 2));
            $sheet->setCellValue('V' . $currentRow, number_format($totals['pagibig_mp2'], 2));
            $sheet->setCellValue('W' . $currentRow, number_format($totals['w_tax'], 2) . "\n" . number_format($totals['u_mid'], 2));
            $sheet->setCellValue('X' . $currentRow, number_format($totals['res_sal'], 2) . "\n" . number_format($totals['loan_emergency'], 2));
            $sheet->setCellValue('Y' . $currentRow, number_format($totals['deduction'], 2) . "\n" . number_format($totals['net'], 2));
            $sheet->setCellValue('Z' . $currentRow, number_format($totals['first'], 2) . "\n" . number_format($totals['second'], 2));
            $sheet->setCellValue('AA' . $currentRow, '');

            // Apply totals row styling
            foreach ($columns as $col) {
                $sheet->getStyle($col . $currentRow)->getFont()->setBold(true)->setSize(8)->setColor(new Color($defaultFontColor));
                $sheet->getStyle($col . $currentRow)->getAlignment()->setWrapText(true);
                if (in_array($col, ['A', 'B', 'AA'])) {
                    $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                } else {
                    $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            }
            $sheet->getStyle('B' . $currentRow)->getFont()->setItalic(true)->setColor(new Color($defaultFontColor));
            $sheet->getStyle('A' . $currentRow . ':AA' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F4FF');
            $sheet->getStyle('A' . $currentRow . ':AA' . $currentRow)->applyFromArray($dataBorderStyle);
            $currentRow += 2;

            // Signature Section
            $sigRow = $currentRow;
            $sheet->setCellValue('A' . $sigRow, 'I CERTIFY on my oath that the above Payroll is Correct and that the services have been duly rendered as stated.');
            $sheet->getStyle('A' . $sigRow)->getFont()->setSize(10)->setColor(new Color($defaultFontColor));
            $sheet->mergeCells('A' . $sigRow . ':M' . $sigRow);
            $currentRow++;

            $sheet->setCellValue('A' . $currentRow, $signatories['signatory_1']);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(10)->setColor(new Color($defaultFontColor));
            $sheet->mergeCells('A' . $currentRow . ':D' . $currentRow);
            $sigNameStyle = $sheet->getStyle('A' . $currentRow . ':D' . $currentRow);
            $sigNameStyle->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $sigNameStyle->getBorders()->getBottom()->setColor(new Color('000000'));
            $currentRow++;

            $sheet->setCellValue('A' . $currentRow, $signatories['signatory_position_1']);
            $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10)->setColor(new Color($defaultFontColor));
            $sheet->mergeCells('A' . $currentRow . ':D' . $currentRow);
            $currentRow = $sigRow + 1;

            $sheet->setCellValue('N' . $sigRow, 'APPROVED, payable for appropriation for P __________');
            $sheet->getStyle('N' . $sigRow)->getFont()->setSize(10)->setColor(new Color($defaultFontColor));
            $sheet->mergeCells('N' . $sigRow . ':AA' . $sigRow);
            $currentRow = $sigRow + 1;

            $sheet->setCellValue('N' . $currentRow, $signatories['signatory_2']);
            $sheet->getStyle('N' . $currentRow)->getFont()->setBold(true)->setSize(10)->setColor(new Color($defaultFontColor));
            $sheet->mergeCells('N' . $currentRow . ':Q' . $currentRow);
            $sigNameStyle2 = $sheet->getStyle('N' . $currentRow . ':Q' . $currentRow);
            $sigNameStyle2->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $sigNameStyle2->getBorders()->getBottom()->setColor(new Color('000000'));
            $currentRow++;

            $sheet->setCellValue('N' . $currentRow, $signatories['signatory_position_2']);
            $sheet->getStyle('N' . $currentRow)->getFont()->setSize(10)->setColor(new Color($defaultFontColor));
            $sheet->mergeCells('N' . $currentRow . ':Q' . $currentRow);

            // Save to temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'general_payroll_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            // Read the file content
            $excelContent = file_get_contents($tempFile);
            unlink($tempFile);

            $filename = 'general_payroll_report_' . $id . '_' . date('Y-m-d') . '.xlsx';

            return response($excelContent)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($excelContent));
        } catch (\Exception $e) {
            \Log::error('General Payroll Report Excel Generation Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('Request data: ' . json_encode($request->all()));
            return $this->serverErrorResponse('Failed to generate General Payroll Report Excel: ' . $e->getMessage());
        }
    }

    /**
     * Substrings matched against schedule deduction labels (case-insensitive) to treat a
     * deduction as having its own General Payroll column (excluded from OTHERS).
     */
    private function generalPayrollListedDeductionNameSubstrings(): array
    {
        return [
            'mplite',
            'mp lite',
            'conso',
            'consol',
            'pabahay',
            'pol loan',
            'policy loan',
            'gsis pol',
            'pagcal',
            'pag cal',
            'calamity loan',
            'p-hsg',
            'p hsg',
            'phsg',
            'gsis hsg',
            'gsis housing',
            'hdmf loan',
            'pag-ibig loan',
            'pag ibig loan',
            'multi-purpose loan',
            'multi purpose loan',
            'mpl loan',
            'pagibig mp2',
            ' mp2',
            'loan emergency',
            'emergency loan',
            'res sal',
            'resignation',
            'hmo',
            'ecash',
            'e-cash',
            'cooperative',
            ' coop',
            'u-mid',
            'u_mid',
            'umid',
            'gsis ge',
            'gsis cpl',
            'gsiscpl',
            'opt life',
            'optional life',
            'p prem',
            'p_prem',
            'p mpl',
            'p_mpl',
            'life/ret',
            'life ret',
            'life / ret',
            'philhealth',
            'phic',
            'withholding',
            'w tax',
            'hdmf',
            'pag-ibig',
            'life and ret',
            'gsis life',
        ];
    }

    /**
     * Deduction IDs that already map to a dedicated column on the General Payroll (listed loans/deductions).
     *
     * @param  \Illuminate\Support\Collection|array|null  $deductionHeaders  Rows with deduction_id + deduction (name), same query as the report.
     */
    private function resolveGeneralPayrollListedDeductionIds($deductionHeaders): array
    {
        $ids = [];
        $headers = collect($deductionHeaders ?? []);
        if ($headers->isEmpty()) {
            return $ids;
        }

        foreach (['mplite', 'conso', 'pabahay'] as $needle) {
            $h = $headers->first(function ($row) use ($needle) {
                return stripos($row->deduction ?? '', $needle) !== false;
            });
            if ($h && isset($h->deduction_id)) {
                $ids[] = (int) $h->deduction_id;
            }
        }

        $patterns = $this->generalPayrollListedDeductionNameSubstrings();
        foreach ($headers as $row) {
            if (!isset($row->deduction_id)) {
                continue;
            }
            $name = strtolower((string) ($row->deduction ?? ''));
            foreach ($patterns as $pattern) {
                $p = strtolower((string) $pattern);
                if ($p !== '' && str_contains($name, $p)) {
                    $ids[] = (int) $row->deduction_id;
                    break;
                }
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * OTHERS on General Payroll: sum of unlisted payroll_deductions
     * (deduction_ids that don't have their own fixed/listed columns).
     * Runs after deduction_headers load.
     */
    private function attachUnlistedLoanOtherDeductionsForGeneralPayroll($payrolls, array $periodIds, $deductionHeaders)
    {
        $payrolls = collect($payrolls);
        if ($payrolls->isEmpty() || empty($periodIds)) {
            return $payrolls;
        }
        $employeeIds = $payrolls->pluck('employee_id')->unique()->filter()->values()->all();
        if (empty($employeeIds)) {
            return $payrolls;
        }

        $query = DB::table('payroll_deductions as pd')
            ->join('deductions as d', 'd.id', '=', 'pd.deduction_id')
            ->whereIn('pd.payroll_period_id', $periodIds)
            ->whereIn('pd.employee_id', $employeeIds)
            ->where(function ($q) {
                $patterns = $this->generalPayrollListedDeductionNameSubstrings();
                foreach ($patterns as $pattern) {
                    $q->whereRaw('LOWER(d.name) NOT LIKE ?', ['%' . strtolower((string) $pattern) . '%']);
                }
                // EA has fixed column but can be labeled as exact short code.
                $q->whereRaw("LOWER(LTRIM(RTRIM(d.name))) NOT IN ('ea', 'e a')");
                $q->whereRaw("LOWER(d.name) NOT LIKE '%emergency allowance%'");
            });

        $otherDeductionsMap = $query
            ->select('pd.employee_id', DB::raw('SUM(ISNULL(pd.amount, 0)) as other_deductions'))
            ->groupBy('pd.employee_id')
            ->get()
            ->keyBy('employee_id');

        return $payrolls->map(function ($row) use ($otherDeductionsMap) {
            $empId = (int) ($row->employee_id ?? 0);
            $otherDed = $otherDeductionsMap->get($empId) ?? $otherDeductionsMap->get((string) $empId);
            $row->other_deductions = $otherDed ? (float) $otherDed->other_deductions : 0.0;
            return $row;
        });
    }

    /**
     * Active divisions for payroll summary report filters.
     */
    private function getActiveDivisions()
    {
        return DB::table('divisions')
            ->where('active', true)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Resolve division filter from request (supports division_id or legacy department_id).
     */
    private function resolveDivisionFilterFromRequest(Request $request)
    {
        $id = $request->input('division_id', $request->input('department_id'));

        if ($id === 'all' || $id === '' || $id === null) {
            return null;
        }

        return $id;
    }

    /**
     * Display name for report header when filtering by division.
     */
    private function getDivisionDisplayName($divisionFilter): string
    {
        if (empty($divisionFilter)) {
            return 'ALL DIVISIONS';
        }

        return DB::table('divisions')->where('id', $divisionFilter)->value('name') ?: 'ALL DIVISIONS';
    }

    /**
     * Helper: fetch payroll_summaries joined with employee/division/position
     * for General Payroll style reports.
     */
    private function fetchGeneralPayrollSummariesForPeriod($periodId, $divisionFilter, $app_key)
    {
        return DB::table('payroll_summaries as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->leftJoin('divisions as c', 'c.id', '=', 'b.division_id')
            ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
            ->leftJoin('time_data_summary as tds', function ($join) use ($periodId) {
                $join->on('tds.employee_id', '=', 'a.employee_id')
                    ->where('tds.payroll_period_id', '=', $periodId);
            })
            ->select(
                'a.*',
                'b.employee_no',
                'a.employee_id',
                'b.photo',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                'b.division_id',
                'c.name as department',
                'd.name as position',
                DB::raw('ISNULL(tds.Adjustment_Amount, 0) as preceding_period_adjustment')
            )
            ->where('a.payroll_period_id', $periodId)
            ->when(!empty($divisionFilter), function ($query) use ($divisionFilter) {
                return $query->where('b.division_id', $divisionFilter);
            })
            ->orderBy('b.division_id', 'asc')
            ->get();
    }

    /**
     * Helper: merge first and second half payroll summaries into a single
     * collection per employee for full-month General Payroll.
     *
     * Monetary fields are summed; first_pay / second_pay reflect each half's
     * net_pay, and net_pay becomes the sum of both halves.
     */
    private function mergePayrollHalves($firstHalfPayrolls, $secondHalfPayrolls)
    {
        $firstByEmployee = collect($firstHalfPayrolls ?? [])->keyBy('employee_id');
        $secondByEmployee = collect($secondHalfPayrolls ?? [])->keyBy('employee_id');

        $allEmployeeIds = $firstByEmployee->keys()
            ->merge($secondByEmployee->keys())
            ->unique();

        // Fields to sum across both halves (extend as needed)
        $sumFields = [
            'total_income',
            'late_amount',
            'ut_amount',
            'absent_amount',
            'lwop_amount',
            'gsis',
            'sss',
            'pagibig',
            'philhealth',
            'tax',
            'total_deduction',
            'pending_amount_payment',
            'holiday_amount',
            'ot_amount',
            'nd_amount',
            'original_netpay',
            'base_net_pay',
            'step_inc_amount',
            'salary_adj_amount',
            'pera',
            'step_increment',
            'differential',
            'additional_comp',
            'gsis_ge',
            'gsis_cpl',
            'p_prem',
            'p_mpl',
            'gsis_opt_life',
            'gsis_mpl',
            'gsis_mplite',
            'gsis_conso',
            'pagcal',
            'phsg_loan',
            'gsis_hsg',
            'gsis_pabahay',
            'pagibig_gs',
            'pagibig_loan',
            'philhealth_employer',
            'other_deductions',
            'hmo',
            'ecash',
            'ea',
            'coop',
            'pagibig_mp2',
            'withholding_tax',
            'w_tax',
            'u_mid',
            'res_sal',
            'loan_emergency',
            'basic_undertime',
            'step_inc_undertime',
            'diff_undertime',
            'adcomp_undertime',
            'pera_undertime',
            'preceding_period_adjustment',
        ];

        return $allEmployeeIds->map(function ($employeeId) use ($firstByEmployee, $secondByEmployee, $sumFields) {
            $first = $firstByEmployee->get($employeeId);
            $second = $secondByEmployee->get($employeeId);

            // If employee exists only in one half, just return that record
            if ($first && !$second) {
                $record = (array) $first;
                $record['first_pay'] = data_get($first, 'net_pay', 0);
                $record['second_pay'] = 0;
                $record['net_pay'] = $record['first_pay'];
                return (object) $record;
            }

            if ($second && !$first) {
                $record = (array) $second;
                $record['first_pay'] = 0;
                $record['second_pay'] = data_get($second, 'net_pay', 0);
                $record['net_pay'] = $record['second_pay'];
                return (object) $record;
            }

            if (!$first && !$second) {
                return null;
            }

            // Start from first half as the base
            $combined = (array) $first;

            foreach ($sumFields as $field) {
                $combined[$field] = (float) data_get($first, $field, 0) + (float) data_get($second, $field, 0);
            }

            // For monthly-level fields like basic salary, use the latest rate (typically 2nd half).
            // Then recompute a true monthly gross using the aggregated incomes from both halves,
            // so that General Payroll reports reflect the full month's income (1st+2nd half).
            $sourceForMonthly = $second ?? $first;
            $salaryMonthly = (float) data_get($sourceForMonthly, 'salary', 0);
            $combined['salary'] = $salaryMonthly;

            $combined['gross_amount'] =
                $salaryMonthly
                + (float) ($combined['holiday_amount'] ?? 0)
                + (float) ($combined['ot_amount'] ?? 0)
                + (float) ($combined['nd_amount'] ?? 0)
                + (float) ($combined['total_income'] ?? 0)
                + (float) ($combined['step_inc_amount'] ?? 0)
                + (float) ($combined['salary_adj_amount'] ?? 0);

            $firstNet = (float) data_get($first, 'net_pay', 0);
            $secondNet = (float) data_get($second, 'net_pay', 0);

            $combined['first_pay'] = $firstNet;
            $combined['second_pay'] = $secondNet;
            $combined['net_pay'] = $firstNet + $secondNet;

            return (object) $combined;
        })->filter()->values();
    }

    /**
     * Resolve the first-half and second-half payroll_period IDs for the same month
     * as the provided payroll period ID (semi-monthly intervals).
     *
     * Returns: [firstHalfPeriodId, secondHalfPeriodId|null]
     */
    private function resolveMonthlyPayrollPeriodPair($periodId): array
    {
        if (empty($periodId)) {
            return [$periodId, null];
        }

        $current = DB::table('payroll_periods as a')
            ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
            ->select(
                'a.id',
                'a.payroll_interval_id',
                'a.attendance_start_date',
                'a.release_date',
                'c.id as cutoff_id',
                'c.name as cutoff_name'
            )
            ->where('a.id', $periodId)
            ->first();

        if (!$current) {
            return [$periodId, null];
        }

        $dateBasis = $current->attendance_start_date ?: $current->release_date;
        if (!$dateBasis) {
            return [$periodId, null];
        }

        $year = (int) date('Y', strtotime($dateBasis));
        $month = (int) date('n', strtotime($dateBasis));

        $cutoffs = DB::table('payroll_cutoffs')
            ->where('payroll_interval_id', $current->payroll_interval_id)
            ->get();

        $firstCutoff = $cutoffs->first(function ($c) {
            $name = strtolower($c->name ?? '');
            return str_contains($name, '1st') || str_contains($name, 'first');
        });
        $secondCutoff = $cutoffs->first(function ($c) {
            $name = strtolower($c->name ?? '');
            return str_contains($name, '2nd') || str_contains($name, 'second');
        });

        if (!$firstCutoff || !$secondCutoff) {
            return [$periodId, null];
        }

        // Match by attendance_start_date month/year (fallback to release_date if attendance is null)
        $firstHalf = DB::table('payroll_periods')
            ->where('payroll_interval_id', $current->payroll_interval_id)
            ->where('payroll_cutoff_id', $firstCutoff->id)
            ->where(function ($q) use ($year, $month) {
                $q->where(function ($q2) use ($year, $month) {
                    $q2->whereNotNull('attendance_start_date')
                        ->whereYear('attendance_start_date', $year)
                        ->whereMonth('attendance_start_date', $month);
                })->orWhere(function ($q2) use ($year, $month) {
                    $q2->whereNull('attendance_start_date')
                        ->whereYear('release_date', $year)
                        ->whereMonth('release_date', $month);
                });
            })
            ->where('posted', true)
            ->where('active', true)
            ->first();

        $secondHalf = DB::table('payroll_periods')
            ->where('payroll_interval_id', $current->payroll_interval_id)
            ->where('payroll_cutoff_id', $secondCutoff->id)
            ->where(function ($q) use ($year, $month) {
                $q->where(function ($q2) use ($year, $month) {
                    $q2->whereNotNull('attendance_start_date')
                        ->whereYear('attendance_start_date', $year)
                        ->whereMonth('attendance_start_date', $month);
                })->orWhere(function ($q2) use ($year, $month) {
                    $q2->whereNull('attendance_start_date')
                        ->whereYear('release_date', $year)
                        ->whereMonth('release_date', $month);
                });
            })
            ->where('posted', true)
            ->where('active', true)
            ->first();

        // If we couldn't find a companion period, return original id
        if (!$firstHalf && !$secondHalf) {
            return [$periodId, null];
        }

        $firstId = $firstHalf ? $firstHalf->id : $periodId;
        $secondId = $secondHalf ? $secondHalf->id : null;

        return [$firstId, $secondId];
    }

    public function payrollSummaryDetailsPrint(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $validator = \Validator::make($request->all(), [
                'payroll_interval_id' => 'required',
                'payroll_period_id' => 'required'
            ], [
                'payroll_interval_id.required' => 'Payroll Interval is required.',
                'payroll_period_id.required' => 'Payroll Period is required.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $id = $request->payroll_period_id;
            $divisionFilter = $this->resolveDivisionFilterFromRequest($request);
            $branch_id = $request->branch_id;

            $companies = DB::table('companies')->get();

            $payrolls = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('divisions as c', 'c.id', '=', 'b.division_id')
                ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.payroll_period_id',
                    'b.employee_no',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'b.division_id',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.ot_amount as ot_pay',
                    'a.nd_amount as nd_pay',
                    'a.holiday_amount as holiday_pay',
                    'a.total_income',
                    'a.gross_amount',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.lwop_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.late_amount',
                    'a.ut_amount',
                    'absent_amount',
                    'a.net_pay',
                    'a.pending_amount_payment'
                )
                ->where('payroll_period_id', $id)
                ->when(!empty($divisionFilter), function ($query) use ($divisionFilter) {
                    return $query->where('b.division_id', $divisionFilter);
                })
                ->orderBy('b.division_id', 'asc')
                ->get();

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    'attendance_start_date',
                    'attendance_end_date',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name")
                )
                ->where(['a.id' => $id])
                ->orderBy('a.release_date', 'desc')
                ->get();

            $payroll_period = date('F d', strtotime($data[0]->attendance_start_date)) . ' - ' . date('d, Y', strtotime($data[0]->attendance_end_date));

            $income_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('incomes as c', 'b.income_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.income_id',
                    'c.name as income'
                )
                ->where('e.id', $id)
                ->where('b.active', true)
                ->whereIn('c.id', function ($query) use ($id) {
                    $query->select('income_id')->from('payroll_incomes')
                        ->where('amount', '>', 0)
                        ->where('payroll_period_id', $id)
                        ->distinct();
                })
                ->distinct()
                ->get();

            $deduction_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('deductions as c', 'b.deduction_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.deduction_id',
                    'c.mfo_pap',
                    'c.uacs',
                    'c.name as deduction'
                )
                ->where('e.id', $id)
                ->where('b.active', true)
                ->whereIn('c.id', function ($query) use ($id) {
                    $query->select('deduction_id')->from('payroll_deductions')
                        ->where('amount', '>', 0)
                        ->where('payroll_period_id', $id)
                        ->distinct();
                })
                ->distinct()
                ->orderBy('b.deduction_id', 'asc')
                ->get();

            // get all income items.
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'b.id as income_id',
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            // get all deduction items.
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.id as deduction_id',
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            // get all deduction items total.
            $deduction_totals = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.id as deduction_id',
                    'b.name',
                    DB::raw("SUM(a.amount) as amount")
                )
                ->where('a.payroll_period_id', $id)
                ->groupBy([
                    'b.id',
                    'b.name',
                ])
                ->get();

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $signatories = [
                'signatory_1' => $request->signatory_1,
                'signatory_position_1' => $request->signatory_position_1,
                'signatory_2' => $request->signatory_2,
                'signatory_position_2' => $request->signatory_position_2,
                'signatory_3' => $request->signatory_3,
                'signatory_position_3' => $request->signatory_position_3,
                'signatory_4' => $request->signatory_4,
                'signatory_position_4' => $request->signatory_position_4,
                'signatory_5' => $request->signatory_5,
                'signatory_position_5' => $request->signatory_position_5,
            ];

            $payroll_period_1 = date('F', strtotime($data[0]->attendance_start_date)) . ' 1-15 ' . date('Y', strtotime($data[0]->attendance_end_date));

            $paroll_cutoffs = DB::table('payroll_periods as a')
                ->join('payroll_cutoffs as b', 'a.payroll_cutoff_id', '=', 'b.id')
                ->where('a.id', $id)
                ->whereRaw("b.name LIKE '2nd%'")
                ->get();

            if ($paroll_cutoffs->isNotEmpty()) {
                $is_second_half = true;
            } else {
                $is_second_half = false;
            }

            $pdf = PDF::loadView('payroll_processes.general_payroll_with_deductions_report', compact(
                'payrolls',
                'payroll_period_1',
                'payroll_period',
                'incomes',
                'deductions',
                'companies',
                'data',
                'income_headers',
                'deduction_headers',
                'deduction_totals',
                'signatories',
                'image',
                'is_second_half'
            ))->setOptions(['defaultFont' => 'sans-serif',  'isPhpEnabled' => true,]);

            $pdf->setPaper([0, 0, 900.44, 1842.07], 'landscape');
            $pdfContent = $pdf->output();

            return $this->successResponse([
                'pdf_content' => base64_encode($pdfContent),
                'filename' => 'payroll_summary_details_report.pdf',
                'content_type' => 'application/pdf',
                'file_size' => strlen($pdfContent)
            ], 'Payroll summary details report generated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payroll summary details report: ' . $e->getMessage());
        }
    }

    public function adjustment(Request $request, $payroll_period_id)
    {
        try {
            $employee_data = $request->all();
            $data = [];

            for ($i = 0; $i < count($employee_data["employee_id"]); $i++) {
                $data = [
                    'tax_amount' => $employee_data['tax_amount'][$i],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                DB::table('payroll_tax_adjustments')->updateOrInsert([
                    'employee_id' => $employee_data['employee_id'][$i],
                    'payroll_period_id' => $payroll_period_id,
                ], $data);

                $payroll = DB::table('payroll_summaries')->where([
                    'employee_id' => $employee_data['employee_id'][$i],
                    'payroll_period_id' => $payroll_period_id,
                ])->get();

                // update - chad 2/25/26
                // if ($payroll->isNotEmpty()) {
                //     $net_pay = ($payroll[0]->gross_amount - ($payroll[0]->gsis + $payroll[0]->sss + $payroll[0]->pagibig + $payroll[0]->philhealth + $employee_data['tax_amount'][$i] + $payroll[0]->late_amount + $payroll[0]->ut_amount + $payroll[0]->absent_amount + $payroll[0]->total_deduction));
                // }

                if ($payroll->isNotEmpty()) {
                    $old_tax = (float) $payroll[0]->tax;
                    $new_tax = (float) $employee_data['tax_amount'][$i];
                    $adjusted_total_deduction = (float) $payroll[0]->total_deduction - $old_tax + $new_tax;
                    $net_pay = (float) $payroll[0]->gross_amount - $adjusted_total_deduction;
                    $net_pay = max($net_pay, 0);
                }

                DB::table('payroll_summaries')->where([
                    'employee_id' => $employee_data['employee_id'][$i],
                    'payroll_period_id' => $payroll_period_id,
                ])->update([
                    'tax' => $new_tax,
                    'total_deduction' => $adjusted_total_deduction,
                    'net_pay' => $net_pay,
                ]);
            }

            $this->invalidatePayrollProcessCache((int) $payroll_period_id);

            return $this->successResponse(['payroll_period_id' => $payroll_period_id], 'Successfully Adjusted Tax Amounts.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to adjust tax amounts: ' . $e->getMessage());
        }
    }

    /**
     * Load per-employee salary adjustments for a specific payroll period.
     */
    public function getSalaryAdjustments($payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $adjustments = DB::table('payroll_salary_adjustments as psa')
                ->join('employees as e', 'e.id', '=', 'psa.employee_id')
                ->select(
                    'psa.id',
                    'psa.employee_id',
                    'psa.payroll_period_id',
                    'psa.amount',
                    'psa.remarks',
                    'e.employee_no',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                    CONCAT(e.first_name,' ',e.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                                END as name")
                )
                ->where('psa.payroll_period_id', $payroll_period_id)
                ->orderBy('name', 'asc')
                ->get();

            return $this->successResponse($adjustments, 'Salary adjustments retrieved successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary adjustments: ' . $e->getMessage());
        }
    }

    /**
     * Save per-employee salary adjustments for a specific payroll period.
     */
    public function saveSalaryAdjustments(Request $request, $payroll_period_id)
    {
        $validated = $request->validate([
            'adjustments' => 'required|array',
            'adjustments.*.employee_id' => 'required|integer|exists:employees,id',
            'adjustments.*.amount' => 'required|numeric',
            'adjustments.*.remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['adjustments'] as $item) {
                DB::table('payroll_salary_adjustments')->updateOrInsert(
                    [
                        'employee_id' => $item['employee_id'],
                        'payroll_period_id' => $payroll_period_id,
                    ],
                    [
                        'amount' => $item['amount'],
                        'remarks' => $item['remarks'] ?? null,
                        'created_by' => Auth::id(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $data_audit = [
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Process',
                'activity' => 'Salary Adjustment',
                'description' => 'Updated salary adjustments for payroll period ' . $payroll_period_id . '.',
            ];

            Audit::create($data_audit);

            DB::commit();

            // Invalidate cached summary/breakdowns for this payroll period.
            $this->invalidatePayrollProcessCache((int) $payroll_period_id);

            return $this->successResponse(
                ['payroll_period_id' => $payroll_period_id],
                'Salary adjustments saved successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to save salary adjustments: ' . $e->getMessage());
        }
    }

    /**
     * Annual taxable income for fixed monthly tax (semi-monthly reconciliation).
     * Uses projected monthly GSIS + PhilHealth + Pag-IBIG × 12 before TRAIN bracket lookup.
     * These amounts are for TAX COMPUTATION ONLY — not the actual payroll contribution deductions.
     */
    private function computeAnnualTaxableIncome(
        float $monthlySalary,
        $gsisData,
        $phData,
        float $monthlyPagibigAmount
    ): float {
        $annualBasic = $monthlySalary * 12;
        $annualContributions = 0.0;

        if ($gsisData->isNotEmpty()) {
            $annualContributions += 12 * ($monthlySalary * (float) $gsisData[0]->multiplier);
        }

        if ($phData->isNotEmpty()) {
            $annualContributions += 12 * $this->computeMonthlyPhilhealthShare($monthlySalary, $phData[0]);
        }

        if ($monthlyPagibigAmount > 0) {
            $annualContributions += 12 * $monthlyPagibigAmount;
        }

        return max(0.0, $annualBasic - $annualContributions);
    }

    /**
     * Fixed monthly withholding from annualized taxable income (TRAIN annual table ÷ 12).
     */
    private function computeAnnualizedFixedMonthlyTax(float $annualTaxable): float
    {
        $annualTaxData = DB::table('annual_tax_table')->orderBy('min_amount', 'asc')->get();
        $annualTax = $this->applyTrainTaxBracket($annualTaxable, $annualTaxData);

        return $annualTax / 12;
    }

    /**
     * TRAIN tax bracket lookup (annual, monthly, or semi-monthly table rows).
     */
    private function applyTrainTaxBracket(float $taxableAmount, $taxData): float
    {
        if ($taxableAmount <= 0 || $taxData->isEmpty()) {
            return 0.0;
        }

        $arrLen = $taxData->count();
        if ($taxableAmount < (float) $taxData[0]->min_amount) {
            return 0.0;
        }

        for ($i = 0; $i < $arrLen; $i++) {
            $row = $taxData[$i];
            $min = (float) $row->min_amount;
            $max = (float) $row->max_amount;
            if ($taxableAmount >= $min && $taxableAmount <= $max) {
                return (($taxableAmount - $min) * (float) $row->percentage) + (float) $row->base_tax;
            }
            if ($i === ($arrLen - 1) && $taxableAmount > $max) {
                return (($taxableAmount - $min) * (float) $row->percentage) + (float) $row->base_tax;
            }
        }

        return 0.0;
    }

    /**
     * 1st-half amounts used for 2nd-half GSIS/PhilHealth reconciliation.
     * Gross is recon-adjusted (half basic + incomes − unpaid LWOP/absent), not raw summary gross.
     *
     * @return array{first_half_gross: float, first_half_gsis_paid: float, first_half_ph_paid: float}
     */
    private function resolveFirstHalfReconContext(
        ?int $firstHalfPeriodId,
        int $employeeId,
        float $fallbackHalfBasicGross,
        float $monthlySalary,
        int $tkDays,
        int $tkHours
    ): array {
        $firstHalfGross = $this->resolvePeriodReconGross(
            $firstHalfPeriodId,
            $employeeId,
            $fallbackHalfBasicGross,
            $monthlySalary,
            $tkDays,
            $tkHours
        );
        $firstHalfGsisPaid = 0.0;
        $firstHalfPhPaid = 0.0;

        if (!empty($firstHalfPeriodId)) {
            $fhSummary = DB::table('payroll_summaries')
                ->where('payroll_period_id', $firstHalfPeriodId)
                ->where('employee_id', $employeeId)
                ->first();

            if ($fhSummary) {
                $firstHalfGsisPaid = (float) ($fhSummary->gsis ?? 0);
                $firstHalfPhPaid = (float) ($fhSummary->philhealth ?? 0);
            }
        }

        return [
            'first_half_gross' => $firstHalfGross,
            'first_half_gsis_paid' => $firstHalfGsisPaid,
            'first_half_ph_paid' => $firstHalfPhPaid,
        ];
    }

    private function periodHasLwopOrAbsent(float $lwopAmount, float $absentAmount): bool
    {
        return $lwopAmount > 0 || $absentAmount > 0;
    }

    /**
     * Unpaid absence/LWOP amount that reduces the GSIS/PH reconciliation gross base.
     * Uses effective absent (after leave-with-pay offsets), not the displayed gross absent alone.
     */
    private function attendanceReductionForContributionRecon(
        float $lwopAmount,
        float $rawAbsentAmount,
        float $effectiveAbsentAmount
    ): float {
        if ($lwopAmount > 0) {
            return $lwopAmount;
        }

        return $effectiveAbsentAmount > 0 ? $effectiveAbsentAmount : $rawAbsentAmount;
    }

    /**
     * Period gross for GSIS/PH recon: gross − absent/LWOP (if any) − preceding (if any).
     */
    private function computePeriodReconGross(
        float $periodGross,
        float $attendanceReduction = 0.0,
        float $precedingPeriodAdjustment = 0.0
    ): float {
        $result = $periodGross;

        if ($attendanceReduction > 0) {
            $result -= $attendanceReduction;
        }
        if ($precedingPeriodAdjustment > 0) {
            $result -= $precedingPeriodAdjustment;
        }

        return max(0.0, $result);
    }

    /**
     * Other-half gross for monthly recon (half basic + incomes − unpaid LWOP/absent).
     */
    private function resolveSecondHalfGrossForRecon(
        ?int $secondHalfPeriodId,
        int $employeeId,
        float $fallbackHalfBasicGross,
        float $monthlySalary,
        int $tkDays,
        int $tkHours
    ): float {
        return $this->resolvePeriodReconGross(
            $secondHalfPeriodId,
            $employeeId,
            $fallbackHalfBasicGross,
            $monthlySalary,
            $tkDays,
            $tkHours
        );
    }

    /**
     * Reconciliation gross for a payroll period (any half): gross − absent/LWOP − preceding (each if present).
     */
    private function resolvePeriodReconGross(
        ?int $periodId,
        int $employeeId,
        float $halfBasicGross,
        float $monthlySalary,
        int $tkDays,
        int $tkHours
    ): float {
        if (empty($periodId)) {
            return $halfBasicGross;
        }

        $periodIncome = (float) (DB::table('payroll_incomes')
            ->where(['payroll_period_id' => $periodId, 'employee_id' => $employeeId])
            ->sum('amount') ?? 0);

        $attendance = $this->resolvePeriodAttendanceAmountsForRecon(
            $periodId,
            $employeeId,
            $monthlySalary,
            $tkDays,
            $tkHours
        );
        $reduction = $this->attendanceReductionForContributionRecon(
            $attendance['lwop'],
            $attendance['raw_absent'],
            $attendance['effective_absent']
        );

        return $this->computePeriodReconGross(
            $halfBasicGross + $periodIncome,
            $reduction,
            $attendance['preceding']
        );
    }

    /**
     * LWOP / absent / preceding for contribution recon (leave-with-pay offsets applied to absent).
     *
     * @return array{lwop: float, raw_absent: float, effective_absent: float, preceding: float}
     */
    private function resolvePeriodAttendanceAmountsForRecon(
        int $periodId,
        int $employeeId,
        float $monthlySalary,
        int $tkDays,
        int $tkHours
    ): array {
        $empty = ['lwop' => 0.0, 'raw_absent' => 0.0, 'effective_absent' => 0.0, 'preceding' => 0.0];

        $timeSummary = DB::table('time_data_summary')
            ->where(['payroll_period_id' => $periodId, 'employee_id' => $employeeId])
            ->first();

        if (!$timeSummary) {
            return $empty;
        }

        $preceding = is_numeric($timeSummary->Adjustment_Amount ?? null)
            ? (float) $timeSummary->Adjustment_Amount
            : 0.0;
        if ($preceding < 0 || !is_finite($preceding)) {
            $preceding = 0.0;
        }

        $maxReasonable = max($monthlySalary * 2, 1000000);
        $lwop = is_numeric($timeSummary->LWOP_Amount ?? null) ? (float) $timeSummary->LWOP_Amount : 0.0;
        $rawAbsent = is_numeric($timeSummary->Absent_Amount ?? null) ? (float) $timeSummary->Absent_Amount : 0.0;
        $late = is_numeric($timeSummary->Late_Amount ?? null) ? (float) $timeSummary->Late_Amount : 0.0;
        $ut = is_numeric($timeSummary->Undertime_Amount ?? null) ? (float) $timeSummary->Undertime_Amount : 0.0;

        if ($lwop < 0 || $lwop > $maxReasonable || !is_finite($lwop)) {
            $lwop = 0.0;
        }
        if ($rawAbsent < 0 || $rawAbsent > $maxReasonable || !is_finite($rawAbsent)) {
            $rawAbsent = 0.0;
        }
        if ($late < 0 || $late > $maxReasonable || !is_finite($late)) {
            $late = 0.0;
        }
        if ($ut < 0 || $ut > $maxReasonable || !is_finite($ut)) {
            $ut = 0.0;
        }

        if ($monthlySalary > 0) {
            $totalTardiness = $late + $ut + $rawAbsent;
            if ($totalTardiness > $monthlySalary) {
                $ratio = $monthlySalary / $totalTardiness;
                $late = round($late * $ratio, 2);
                $ut = round($ut * $ratio, 2);
                $rawAbsent = round($rawAbsent * $ratio, 2);
            }
        }

        $workDays = $tkDays > 0 ? $tkDays : 22;
        $dailyRate = round($monthlySalary / $workDays, 2);
        $leaveWithPayDays = (float) DB::table('time_data')
            ->where('employee_id', $employeeId)
            ->where('payroll_period_id', $periodId)
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
            'lwop' => $lwop,
            'raw_absent' => $rawAbsent,
            'effective_absent' => $effectiveAbsent,
            'preceding' => $preceding,
        ];
    }

    /**
     * Monthly PhilHealth employee share (used for reconciliation and annualized tax base).
     */
    private function computeMonthlyPhilhealthShare(float $monthlySalary, $phRow): float
    {
        if (!$phRow || $monthlySalary <= 0) {
            return 0.0;
        }

        $multiplier = (float) $phRow->multiplier;
        $ceiling = (float) $phRow->income_ceiling;
        $floor = (float) $phRow->income_floor;
        $fixRate = (float) $phRow->fix_rate;

        if ($monthlySalary <= $floor) {
            return 0.0;
        }

        if ($monthlySalary >= $ceiling) {
            return $fixRate / 2;
        }

        return ($monthlySalary * $multiplier) / 2;
    }

    /**
     * YTD government contributions and tax paid, grouped by calendar month with per-period detail.
     *
     * @return array{year: int, ytd_totals: array<string, float>, ytd_grand_total: float, months: array<int, array>}
     */
    private function buildEmployeeGovContributionHistory(
        int $employeeId,
        int $year,
        string $currentPeriodDate,
        int $payrollPeriodId
    ): array {
        $periodRows = DB::table('payroll_summaries as a')
            ->join('payroll_periods as b', 'a.payroll_period_id', '=', 'b.id')
            ->join('payroll_cutoffs as c', 'c.id', '=', 'b.payroll_cutoff_id')
            ->select(
                'b.id as payroll_period_id',
                'b.release_date',
                'c.name as cutoff_name',
                'a.gsis',
                'a.sss',
                'a.pagibig',
                'a.philhealth',
                'a.tax'
            )
            ->where('a.employee_id', $employeeId)
            ->whereYear('b.release_date', $year)
            ->where(function ($q) use ($currentPeriodDate, $payrollPeriodId) {
                $q->where('b.release_date', '<', $currentPeriodDate)
                    ->orWhere(function ($q2) use ($currentPeriodDate, $payrollPeriodId) {
                        $q2->where('b.release_date', '=', $currentPeriodDate)
                            ->where('b.id', '<=', $payrollPeriodId);
                    });
            })
            ->orderBy('b.release_date', 'asc')
            ->orderBy('b.id', 'asc')
            ->get();

        $months = [];
        $ytdTotals = [
            'gsis' => 0.0,
            'sss' => 0.0,
            'pagibig' => 0.0,
            'philhealth' => 0.0,
            'tax' => 0.0,
        ];

        foreach ($periodRows as $row) {
            $amounts = [
                'gsis' => (float) ($row->gsis ?? 0),
                'sss' => (float) ($row->sss ?? 0),
                'pagibig' => (float) ($row->pagibig ?? 0),
                'philhealth' => (float) ($row->philhealth ?? 0),
                'tax' => (float) ($row->tax ?? 0),
            ];

            foreach ($amounts as $key => $value) {
                $ytdTotals[$key] += $value;
            }

            $periodTotal = array_sum($amounts);
            $monthKey = date('Y-m', strtotime((string) $row->release_date));

            if (!isset($months[$monthKey])) {
                $months[$monthKey] = [
                    'year_month' => $monthKey,
                    'month_label' => date('F Y', strtotime($monthKey . '-01')),
                    'gsis' => 0.0,
                    'sss' => 0.0,
                    'pagibig' => 0.0,
                    'philhealth' => 0.0,
                    'tax' => 0.0,
                    'total' => 0.0,
                    'periods' => [],
                ];
            }

            foreach ($amounts as $key => $value) {
                $months[$monthKey][$key] += $value;
            }
            $months[$monthKey]['total'] += $periodTotal;

            $months[$monthKey]['periods'][] = [
                'payroll_period_id' => (int) $row->payroll_period_id,
                'period_label' => (string) ($row->cutoff_name ?? 'Payroll period'),
                'release_date' => $row->release_date,
                'gsis' => $amounts['gsis'],
                'sss' => $amounts['sss'],
                'pagibig' => $amounts['pagibig'],
                'philhealth' => $amounts['philhealth'],
                'tax' => $amounts['tax'],
                'total' => $periodTotal,
            ];
        }

        return [
            'year' => $year,
            'ytd_totals' => $ytdTotals,
            'ytd_grand_total' => array_sum($ytdTotals),
            'months' => array_values($months),
        ];
    }
}
