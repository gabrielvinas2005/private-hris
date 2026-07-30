<?php

namespace App\Http\Controllers;

use App\Support\PayslipPrecedingTotals;
use PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeePayslipReportController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Returns an error message if the payroll period's month is not fully posted,
     * null if safe to proceed. For semi-monthly intervals, BOTH halves must be posted.
     */
    private function requireFullMonthPosted(int $periodId): ?string
    {
        $period = DB::table('payroll_periods')->where('id', $periodId)->first();
        if (!$period) return 'Payroll period not found.';
        $isPosted = ($period->posted === true || $period->posted === 1 || $period->posted === 'true' || $period->posted === '1');
        if (!$isPosted) return 'This payroll period has not been posted yet.';

        $cutoffCount = DB::table('payroll_cutoffs')
            ->where('payroll_interval_id', $period->payroll_interval_id)
            ->count();

        if ($cutoffCount < 2) return null;

        // Determine if each cutoff (1st/2nd) has at least one posted payroll period in the month.
        // This avoids false blocks when there are duplicate/unposted payroll_period rows for the same cutoff.
        $monthRows = DB::table('payroll_periods as p2')
            ->join('payroll_cutoffs as c2', 'p2.payroll_cutoff_id', '=', 'c2.id')
            ->where('p2.payroll_interval_id', $period->payroll_interval_id)
            ->whereYear('p2.release_date', date('Y', strtotime($period->release_date)))
            ->whereMonth('p2.release_date', date('n', strtotime($period->release_date)))
            ->where('p2.active', true)
            ->select('p2.posted', 'c2.name as cutoff_name')
            ->get();

        $cutoffs = $monthRows
            ->pluck('cutoff_name')
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique()
            ->values();

        // Only enforce the rule when the interval has >= 2 distinct cutoffs.
        if ($cutoffs->count() >= 2) {
            $postedByCutoff = [];
            foreach ($monthRows as $row) {
                $name = trim((string) ($row->cutoff_name ?? ''));
                if ($name === '') continue;
                $postedVal = $row->posted;
                $rowPosted = ($postedVal === true || $postedVal === 1 || $postedVal === 'true' || $postedVal === '1');
                $postedByCutoff[$name] = ($postedByCutoff[$name] ?? false) || $rowPosted;
            }

            $missingCutoffs = [];
            foreach ($cutoffs as $name) {
                if (!($postedByCutoff[$name] ?? false)) {
                    $missingCutoffs[] = $name;
                }
            }

            if (!empty($missingCutoffs)) {
                $monthYear = date('F Y', strtotime($period->release_date));
                $missing = implode(' and ', $missingCutoffs);
                return "Cannot generate payslip for {$monthYear}: both halves must be posted first. Missing: {$missing}.";
            }
        }

        return null;
    }

    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $intervals = DB::table('payroll_intervals')->where('active', true)->get();
            $divisions = DB::table('divisions')
                ->where('active', true)
                ->orderBy('name', 'asc')
                ->get();

            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->leftJoin('divisions', 'divisions.id', '=', 'employees.division_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.employee_no',
                    'employees.email',
                    'employees.date_hired',
                    'employees.division_id',
                    'employees.payroll_interval_id',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'divisions.name as division',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                END as name")
                )
                ->orderBy('employees.first_name', 'asc')
                ->where(['employees.is_employee' => true, 'employees.active' => true])
                ->paginate(10000);

            // Only show periods where the ENTIRE month is fully posted.
            // For semi-monthly intervals, both halves must be posted before the payslip
            // generator makes them available.
            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(b.name, ' ', c.name, ' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name"),
                    'a.release_date'
                )
                ->where('a.posted', true)
                ->where(function ($q) {
                    $q->whereRaw(
                        "(SELECT COUNT(*) FROM payroll_cutoffs pc WHERE pc.payroll_interval_id = a.payroll_interval_id) = 1"
                    )->orWhereRaw(
                        "(SELECT COUNT(*) FROM payroll_periods p2
                          WHERE p2.payroll_interval_id = a.payroll_interval_id
                            AND YEAR(p2.release_date) = YEAR(a.release_date)
                            AND MONTH(p2.release_date) = MONTH(a.release_date)
                            AND p2.active = 1
                            AND p2.posted = 0) = 0"
                    );
                })
                ->orderBy('a.release_date', 'desc')
                ->distinct()
                ->get();

            return $this->successResponse([
                'employees' => $data,
                'pay_periods' => $pay_periods,
                'intervals' => $intervals,
                'divisions' => $divisions,
                'departments' => $divisions,
            ], 'Payslip report data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payslip report data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $blockMsg = $this->requireFullMonthPosted((int) $request->input('payroll_period_id'));
            if ($blockMsg) return $this->errorResponse($blockMsg);

            $validator = validator($request->all(), [
                'payroll_interval_id' => 'required|integer|exists:payroll_intervals,id',
                'payroll_period_id' => 'required|integer|exists:payroll_periods,id',
                'payroll_period_id_2' => 'nullable|integer|exists:payroll_periods,id',
                'employee' => 'required|integer|exists:employees,id',
                'certified_correct' => 'nullable|string|max:100',
                'position' => 'nullable|string|max:100'
            ], [
                'payroll_interval_id.required' => 'Payroll Interval is required.',
                'payroll_interval_id.exists' => 'Selected payroll interval does not exist.',
                'payroll_period_id.required' => 'Payroll Period is required.',
                'payroll_period_id.exists' => 'Selected payroll period does not exist.',
                'employee.required' => 'Employee is required.',
                'employee.exists' => 'Selected employee does not exist.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $logo1 = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
            $logo2 = base64_encode(file_get_contents(public_path('/dist/img/reports/Pilipinas_logo.png')));

            $companies = DB::table('companies')->get();

            $payroll_id = $request->payroll_period_id;
            $payroll_id_2 = $request->input('payroll_period_id_2');
            $id = $request->employee;

            $periodIds = array_values(array_filter(array_unique([
                $payroll_id,
                $payroll_id_2
            ])));

            $payrollRows = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('divisions as c', 'c.id', '=', 'b.division_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('employment_types as e', 'e.id', '=', 'b.employment_type_id')
                ->join('payroll_periods as h', 'a.payroll_period_id', '=', 'h.id')
                ->join('payroll_intervals as i', 'h.payroll_interval_id', '=', 'i.id')
                ->join('payroll_cutoffs as j', 'h.payroll_cutoff_id', '=', 'j.id')
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.last_name,', ',b.first_name,' ',substring(b.middle_name,1,1),'.')
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+', '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM(SUBSTRING([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'.'
                                END as name"),
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    'e.name as employment_type',
                    'a.salary',
                    'a.ot_amount as ot_pay',
                    'a.nd_amount as nd_pay',
                    'a.holiday_amount as holiday_pay',
                    'a.total_income',
                    'a.gross_amount',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    DB::raw("isnull(a.late_amount,0) + isnull(a.ut_amount,0) + isnull(a.absent_amount,0) as tardiness_amount"),
                    DB::raw('ISNULL((SELECT SUM(ISNULL(ta.Late_Amount,0))      FROM time_data_summary_adj ta WHERE ta.Employee_ID = a.employee_id AND ta.Preceding_Payroll_Period_ID = a.payroll_period_id), 0) as preceding_late_amount'),
                    DB::raw('ISNULL((SELECT SUM(ISNULL(ta.Undertime_Amount,0)) FROM time_data_summary_adj ta WHERE ta.Employee_ID = a.employee_id AND ta.Preceding_Payroll_Period_ID = a.payroll_period_id), 0) as preceding_ut_amount'),
                    DB::raw('ISNULL((SELECT SUM(ISNULL(ta.Absent_Amount,0))    FROM time_data_summary_adj ta WHERE ta.Employee_ID = a.employee_id AND ta.Preceding_Payroll_Period_ID = a.payroll_period_id), 0) as preceding_absent_amount'),
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.lwop_amount',
                    'a.net_pay',
                    DB::raw("CONCAT(DATENAME(month, h.release_date),' ', DATENAME(year, h.release_date)) as payroll_period"),
                    'h.release_date as payroll_release_date',
                    'j.name as cutoff_name'
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->where('a.employee_id', $id)
                ->orderBy('b.first_name', 'asc')
                ->get();

            if ($payrollRows->isEmpty()) {
                return $this->notFoundResponse('No payslip data found for the selected criteria');
            }

            // Merge 1st + 2nd half into one payslip row when payroll_period_id_2 is provided.
            // Notes:
            // - Salary is monthly-level; we keep a single monthly basic salary.
            // - Most other monetary fields (OT, incomes, deductions, etc.) are summed across both halves.
            // - Gross amount for the merged payslip is recomputed as:
            //     basic salary + total incomes + OT + ND + holiday (for the whole month),
            //   so that the "TOTAL" earnings line on the payslip matches the gross used in payroll processing.
            // - First Pay / Second Pay reflect the actual net pay of each half.
            $firstRow = null;
            $secondRow = null;
            if (count($periodIds) === 1) {
                $firstRow = $payrollRows->first();
            } else {
                $byPeriod = $payrollRows->keyBy('payroll_period_id');

                // Identify first/second half by cutoff name; fall back to release date ordering.
                $meta = DB::table('payroll_periods as p')
                    ->join('payroll_cutoffs as c', 'p.payroll_cutoff_id', '=', 'c.id')
                    ->whereIn('p.id', $periodIds)
                    ->select('p.id', 'p.release_date', 'c.name as cutoff_name')
                    ->get()
                    ->keyBy('id');

                $firstHalfId = null;
                $secondHalfId = null;
                foreach ($periodIds as $pid) {
                    $cutoff = strtolower((string) data_get($meta, "{$pid}.cutoff_name", ''));
                    if ($cutoff && (strpos($cutoff, 'first') !== false || strpos($cutoff, '1st') !== false)) {
                        $firstHalfId = $pid;
                    } elseif ($cutoff && (strpos($cutoff, 'second') !== false || strpos($cutoff, '2nd') !== false)) {
                        $secondHalfId = $pid;
                    }
                }
                if (!$firstHalfId || !$secondHalfId) {
                    $sorted = collect($periodIds)->sortBy(function ($pid) use ($meta) {
                        return (string) data_get($meta, "{$pid}.release_date", '');
                    })->values();
                    $firstHalfId = $firstHalfId ?: $sorted->first();
                    $secondHalfId = $secondHalfId ?: $sorted->last();
                }

                $firstRow = $byPeriod->get($firstHalfId) ?: $payrollRows->first();
                $secondRow = $byPeriod->get($secondHalfId);
            }

            $combined = (array) ($firstRow ?: $payrollRows->first());
            $sumFields = [
                'ot_pay',
                'nd_pay',
                'holiday_pay',
                'total_income',
                'late_amount',
                'ut_amount',
                'absent_amount',
                'tardiness_amount',
                'gsis',
                'sss',
                'pagibig',
                'philhealth',
                'tax',
                'total_deduction',
                'lwop_amount',
            ];
            if ($secondRow) {
                foreach ($sumFields as $field) {
                    $combined[$field] = (float) data_get($firstRow, $field, 0) + (float) data_get($secondRow, $field, 0);
                }
                // Monthly-level basic salary (same for both halves).
                $combined['salary'] = (float) data_get($secondRow, 'salary', data_get($firstRow, 'salary', 0));
                // Recompute monthly gross so it includes full-month OT, incomes, holiday, ND, etc.
                $monthlyBasic = (float) $combined['salary'];
                $monthlyHoliday = (float) ($combined['holiday_pay'] ?? 0);
                $monthlyIncomes = (float) ($combined['total_income'] ?? 0);
                $monthlyOt = (float) ($combined['ot_pay'] ?? 0);
                $monthlyNd = (float) ($combined['nd_pay'] ?? 0);
                $combined['gross_amount'] = $monthlyBasic + $monthlyHoliday + $monthlyIncomes + $monthlyOt + $monthlyNd;
                $combined['payroll_period'] = data_get($secondRow, 'payroll_period', data_get($firstRow, 'payroll_period'));
                $combined['payroll_release_date'] = data_get($secondRow, 'payroll_release_date', data_get($firstRow, 'payroll_release_date'));
                $combined['cutoff_name'] = data_get($secondRow, 'cutoff_name', data_get($firstRow, 'cutoff_name'));

                $firstNet = (float) data_get($firstRow, 'net_pay', 0);
                $secondNet = (float) data_get($secondRow, 'net_pay', 0);
                $combined['first_pay'] = $firstNet;
                $combined['second_pay'] = $secondNet;
                $combined['net_pay'] = $firstNet + $secondNet;
            } else {
                $netPay = (float) data_get($firstRow, 'net_pay', 0);
                $cutoffLower = strtolower((string) data_get($firstRow, 'cutoff_name', ''));
                // Single posted period for the whole month (Monthly cutoff): match Payroll Process Summary
                // split — floor(net/2) on first pay, remainder on second (centavo carry).
                if (str_contains($cutoffLower, 'monthly')) {
                    $firstHalfWhole = (float) floor($netPay / 2);
                    $combined['first_pay'] = (float) round($firstHalfWhole, 2);
                    $combined['second_pay'] = max((float) round($netPay - $firstHalfWhole, 2), 0.0);
                } else {
                    $combined['first_pay'] = $netPay;
                    $combined['second_pay'] = 0.0;
                }
                $combined['net_pay'] = $netPay;
            }

            // Preceding deduction: sum time_data_summary.Adjustment_Amount for ALL payroll periods in the same
            // calendar month (1st + 2nd half). The print request often sends only one period id;
            // row-level subqueries would then show only that half unless we aggregate by month.
            $prec = PayslipPrecedingTotals::fromTimeDataSummary(
                (int) $id,
                (int) data_get($firstRow, 'payroll_period_id')
            );
            $combined['preceding_late_amount'] = $prec['preceding_late_amount'];
            $combined['preceding_ut_amount'] = $prec['preceding_ut_amount'];
            $combined['preceding_absent_amount'] = $prec['preceding_absent_amount'];
            $combined['preceding_deduction_total'] = $prec['preceding_deduction_total'];

            // Store per-half attendance amounts for transparent formula display on the payslip.
            $fh_late   = (float) data_get($firstRow,  'late_amount',   0);
            $fh_ut     = (float) data_get($firstRow,  'ut_amount',     0);
            $fh_absent = (float) data_get($firstRow,  'absent_amount', 0);
            $sh_late   = (float) data_get($secondRow, 'late_amount',   0);
            $sh_ut     = (float) data_get($secondRow, 'ut_amount',     0);
            $sh_absent = (float) data_get($secondRow, 'absent_amount', 0);

            // 1st-half breakdown (used in FIRST PAY formula)
            $combined['first_half_absent_amount']    = $fh_absent;
            $combined['first_half_late_amount']      = $fh_late;
            $combined['first_half_ut_amount']        = $fh_ut;
            $combined['first_half_tardiness_total']  = $fh_late + $fh_ut + $fh_absent;

            // 2nd-half breakdown (used in SECOND PAY formula)
            $combined['second_half_absent_amount']   = $sh_absent;
            $combined['second_half_late_amount']     = $sh_late;
            $combined['second_half_ut_amount']       = $sh_ut;
            $combined['second_half_tardiness_total'] = $sh_late + $sh_ut + $sh_absent;

            $payrolls = collect([(object) $combined]);

            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name as deduction',
                    DB::raw("SUM(isnull(a.amount,0)) as amount")
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->where('a.employee_id', $id)
                ->where('a.amount', '>', 0)
                ->groupBy('a.employee_id', 'b.name')
                ->get();

            $incomes = DB::table("payroll_incomes as a")
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    DB::raw("SUM(isnull(a.amount,0)) as amount"),
                    'b.name as income'
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->where('a.employee_id', $id)
                ->where('a.amount', '>', 0)
                ->groupBy('b.name')
                ->get();

            $signatories = [
                'name' => $request->input('certified_correct'),
                'position' => $request->input('position'),
            ];

            $pdf = PDF::loadView('payslips.payslip_print', compact(
                'payrolls',
                'deductions',
                'incomes',
                'logo1',
                'logo2',
                'signatories',
                'companies'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "payslip_{$id}_{$payroll_id}_" . date('Y-m-d') . ".pdf";

            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate employee payslip PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get payroll periods that have payroll data for a given employee and interval
     */
    public function employeePeriods($employee_id, $interval_id)
    {
        try {
            $employeeId = (int) $employee_id;
            $intervalId = (int) $interval_id;

            $buildQuery = function (?int $filterIntervalId) use ($employeeId) {
                $q = DB::table('payroll_summaries as a')
                    ->join('payroll_periods as p', 'a.payroll_period_id', '=', 'p.id')
                    ->join('payroll_intervals as i', 'p.payroll_interval_id', '=', 'i.id')
                    ->join('payroll_cutoffs as c', 'p.payroll_cutoff_id', '=', 'c.id')
                    ->where('a.employee_id', $employeeId)
                    ->where('p.active', true)
                    ->where(function ($posted) {
                        $posted->where('p.posted', true)
                            ->orWhere('p.posted', 1)
                            ->orWhere('p.posted', 'true');
                    });

                if ($filterIntervalId) {
                    $q->where('p.payroll_interval_id', $filterIntervalId);
                }

                return $q;
            };

            $select = [
                'p.id',
                'p.posted',
                'p.payroll_interval_id',
                DB::raw("CONCAT(i.name, ' ', c.name, ' (',DATENAME(MONTH,p.release_date),' ',DATEPART(YEAR,p.release_date),') ') as name"),
                'p.release_date',
                'c.name as cutoff_name',
            ];

            // 1) Selected interval
            $periods = $buildQuery($intervalId ?: null)
                ->select($select)
                ->orderBy('p.release_date', 'desc')
                ->distinct()
                ->get();

            // 2) Employee's assigned payroll interval (when UI interval does not match)
            if ($periods->isEmpty() && $intervalId) {
                $empIntervalId = (int) DB::table('employees')
                    ->where('id', $employeeId)
                    ->value('payroll_interval_id');

                if ($empIntervalId > 0 && $empIntervalId !== $intervalId) {
                    $periods = $buildQuery($empIntervalId)
                        ->select($select)
                        ->orderBy('p.release_date', 'desc')
                        ->distinct()
                        ->get();
                }
            }

            // 3) Any posted period with payroll summary for this employee
            if ($periods->isEmpty()) {
                $periods = $buildQuery(null)
                    ->select($select)
                    ->orderBy('p.release_date', 'desc')
                    ->distinct()
                    ->get();
            }

            return $this->successResponse($periods, 'Employee payroll periods retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee payroll periods: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $employee = DB::table('employees as a')
                ->leftJoin('branches as b', 'b.id', '=', 'a.branch_id')
                ->leftJoin('departments as c', 'c.id', '=', 'a.department_id')
                ->leftJoin('positions as d', 'd.id', '=', 'a.position_id')
                ->leftJoin('employment_types as e', 'e.id', '=', 'a.employment_type_id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    'a.email',
                    'a.date_hired',
                    'a.salary',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as branch',
                    'c.name as department',
                    'd.name as position',
                    'e.name as employment_type'
                )
                ->where('a.id', $id)
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found');
            }

            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name"),
                    'a.release_date'
                )
                ->orderBy('a.release_date', 'desc')
                ->get();

            return $this->successResponse([
                'employee' => $employee,
                'pay_periods' => $pay_periods,
                'summary' => [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'employee_no' => $employee->employee_no,
                    'department' => $employee->department,
                    'position' => $employee->position,
                    'employment_type' => $employee->employment_type,
                    'salary' => $employee->salary,
                    'date_hired' => $employee->date_hired,
                    'pay_periods_count' => $pay_periods->count()
                ]
            ], 'Employee payslip data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee payslip data: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $app_key = env("APP_KEY", "");

            $intervals = DB::table('payroll_intervals')->where('active', true)->get();
            $departments = DB::table('departments')->where('active', true)->get();

            $employees = DB::table('employees as a')
                ->leftJoin('branches as b', 'b.id', '=', 'a.branch_id')
                ->leftJoin('departments as c', 'c.id', '=', 'a.department_id')
                ->leftJoin('positions as d', 'd.id', '=', 'a.position_id')
                ->leftJoin('employment_types as e', 'e.id', '=', 'a.employment_type_id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    'a.salary',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as branch',
                    'c.name as department',
                    'd.name as position',
                    'e.name as employment_type'
                )
                ->where(['a.is_employee' => true, 'a.active' => true])
                ->orderBy('a.first_name', 'asc')
                ->get();

            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name"),
                    'a.release_date'
                )
                ->orderBy('a.release_date', 'desc')
                ->get();

            return $this->successResponse([
                'employees' => $employees,
                'pay_periods' => $pay_periods,
                'intervals' => $intervals,
                'departments' => $departments,
                'fields' => [
                    'payroll_interval_id' => ['type' => 'select', 'required' => true, 'label' => 'Payroll Interval'],
                    'payroll_period_id' => ['type' => 'select', 'required' => true, 'label' => 'Payroll Period'],
                    'employee' => ['type' => 'select', 'required' => true, 'label' => 'Employee'],
                    'certified_correct' => ['type' => 'text', 'required' => false, 'label' => 'Certified Correct By'],
                    'position' => ['type' => 'text', 'required' => false, 'label' => 'Position']
                ]
            ], 'Create payslip report form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }
}
