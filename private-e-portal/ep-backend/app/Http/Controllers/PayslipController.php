<?php

namespace App\Http\Controllers;

use PDF;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class PayslipController extends Controller
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

    public function index($id)
    {
        try {
            // Initialize payslip_records as empty collection
            $payslip_records = collect();

            // Get employee id from user id
            $emp_id_data = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->selectRaw('ISNULL(b.id,0) as id')
                ->where('a.id', $id)
                ->get();

            if ($emp_id_data->isNotEmpty()) {
                $employeeId = (int) $emp_id_data[0]->id;

                $payslip_records = DB::table('payroll_periods as a')
                    ->join('payroll_intervals as b', 'b.id', '=', 'a.payroll_interval_id')
                    ->join('payroll_summaries as s', 's.payroll_period_id', '=', 'a.id')
                    ->where('s.employee_id', $employeeId)
                    ->where('a.active', 1)
                    ->where('a.posted', 1)
                    // Only include months where the whole month is posted (for semi-monthly intervals)
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
                    // GROUP to one row per month
                    ->groupBy('a.payroll_interval_id', 'b.name', DB::raw('YEAR(a.release_date)'), DB::raw('MONTH(a.release_date)'))
                    ->select(
                        // Use latest period id in that month (usually 2nd half)
                        DB::raw('MAX(a.id) as id'),
                        DB::raw($employeeId . ' as employee_id'),
                        // Payroll Period column should just be "Monthly"
                        'b.name as payroll_interval',
                        // Release Date = 2nd half (latest release_date in that month)
                        DB::raw('MAX(a.release_date) as release_date')
                    )
                    ->orderBy(DB::raw('YEAR(MAX(a.release_date))'))
                    ->orderBy(DB::raw('MONTH(MAX(a.release_date))'))
                    ->get();
            }

            // Only create dummy record if no records found AND we have employee data
            if ($payslip_records->isEmpty() && $emp_id_data->isNotEmpty()) {
                $daily_payslip_records = array(
                    'id' => 0,
                    'employee_id' => $emp_id_data[0]->id,
                    'payroll_interval' => null,
                    'cut_off' => null,
                    'payroll_start_date' => null,
                    'payroll_end_date' => null,
                    'release_date' => null
                );

                $payslip_records = collect([(object)$daily_payslip_records]);
            }

            return $this->successResponse($payslip_records, 'Payslip list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payslip list: ' . $e->getMessage());
        }
    }

    /**
     * Returns an error message string if the payroll period's month is not fully posted,
     * or null if it is safe to proceed.
     * For semi-monthly intervals, BOTH halves must be posted.
     */
    private function requireFullMonthPosted(int $periodId): ?string
    {
        $period = DB::table('payroll_periods')->where('id', $periodId)->first();
        if (!$period) return 'Payroll period not found.';
        if (!$period->posted) return 'This payroll period has not been posted yet.';

        $cutoffCount = DB::table('payroll_cutoffs')
            ->where('payroll_interval_id', $period->payroll_interval_id)
            ->count();

        if ($cutoffCount < 2) return null; // Monthly — single period, already posted.

        $unpostedCount = DB::table('payroll_periods')
            ->where('payroll_interval_id', $period->payroll_interval_id)
            ->whereYear('release_date', date('Y', strtotime($period->release_date)))
            ->whereMonth('release_date', date('n', strtotime($period->release_date)))
            ->where('active', true)
            ->where('posted', false)
            ->count();

        if ($unpostedCount > 0) {
            return 'Cannot generate payslip: both the 1st half and 2nd half of this month must be posted first.';
        }

        return null;
    }

    public function view($id, $payroll_id)
    {
        try {
            $blockMsg = $this->requireFullMonthPosted((int) $payroll_id);
            if ($blockMsg) return $this->errorResponse($blockMsg);

            $app_key = env("APP_KEY", "");

            // 1) Determine the month / interval of the selected period
            $basePeriod = DB::table('payroll_periods')->where('id', $payroll_id)->first();
            if (!$basePeriod) {
                return $this->notFoundResponse('Payroll period not found');
            }

            $year  = date('Y', strtotime($basePeriod->release_date));
            $month = date('n', strtotime($basePeriod->release_date));

            // All posted, active periods for this interval & month (1st + 2nd half)
            $periodIds = DB::table('payroll_periods')
                ->where('payroll_interval_id', $basePeriod->payroll_interval_id)
                ->whereYear('release_date', $year)
                ->whereMonth('release_date', $month)
                ->where('active', true)
                ->where('posted', true)
                ->pluck('id');

            if ($periodIds->isEmpty()) {
                return $this->notFoundResponse('No payroll periods found for this month.');
            }

            // 2) Load all summaries for this employee across those periods
            $rawSummaries = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('departments as c', 'c.id', '=', 'b.department_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->join('employment_types as e', 'e.id', '=', 'b.employment_type_id')
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                   CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
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
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.net_pay'
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->where('a.employee_id', $id)
                ->orderBy('b.first_name', 'asc')
                ->get();

            if ($rawSummaries->isEmpty()) {
                return $this->notFoundResponse('Payslip data not found');
            }

            // 3) Use the row with the highest gross_amount as the "main" monthly summary
            $primary = $rawSummaries->sortByDesc('gross_amount')->first();

            // Sum amounts across all periods for the month (1st half + 2nd half)
            $primary->total_income    = $rawSummaries->sum('total_income');
            $primary->gross_amount    = $rawSummaries->sum('gross_amount');
            $primary->late_amount     = $rawSummaries->sum('late_amount');
            $primary->ut_amount       = $rawSummaries->sum('ut_amount');
            $primary->absent_amount   = $rawSummaries->sum('absent_amount');
            $primary->tax             = $rawSummaries->sum('tax');
            $primary->total_deduction = $rawSummaries->sum('total_deduction');
            $primary->net_pay          = $rawSummaries->sum('net_pay');
            $primary->tardiness_amount = $primary->late_amount + $primary->ut_amount + $primary->absent_amount;
            // Keep gsis/pagibig/philhealth from the main row
            $payrolls = collect([$primary]);

            // 4) Format payroll period as "Monthly - Month Year"
            $interval = DB::table('payroll_intervals')
                ->where('id', $basePeriod->payroll_interval_id)
                ->value('name');

            $monthName = date('F', strtotime($basePeriod->release_date));
            $payroll_period = $interval . ' - ' . $monthName . ' ' . $year;

            // 5) Get additional incomes from all periods in the month
            $incomes = DB::table("payroll_incomes as a")
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.amount',
                    'b.name as income'
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->where('a.employee_id', $id)
                ->where('a.amount', '>', 0)
                ->get();

            // 6) Get additional deductions from all periods in the month
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name as deduction',
                    DB::raw("isnull(a.amount,0) as amount")
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->where('a.employee_id', $id)
                ->where('a.amount', '>', 0)
                ->get();

            // Get period info for display
            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(b.name,' - ',DATENAME(MONTH, a.release_date),' ',YEAR(a.release_date)) as name")
                )
                ->where(['a.id' => $payroll_id])
                ->first();

            return $this->successResponse([
                'payroll_period' => $payroll_period,
                'payrolls' => $payrolls,
                'incomes' => $incomes,
                'deductions' => $deductions,
                'data' => $data ? collect([$data]) : collect()
            ], 'Payslip data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payslip data: ' . $e->getMessage());
        }
    }

    public function print($id, $payroll_id)
    {
        try {
            // Ensure both halves of the month are posted before printing
            $blockMsg = $this->requireFullMonthPosted((int) $payroll_id);
            if ($blockMsg) return $this->errorResponse($blockMsg);

            $app_key = env("APP_KEY", "");
            $logo1 = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
            $logo2 = base64_encode(file_get_contents(public_path('/dist/img/reports/Pilipinas_logo.png')));
            $companies = DB::table('companies')->get();

            // 1) Determine the month / interval of the selected period
            $basePeriod = DB::table('payroll_periods')->where('id', $payroll_id)->first();
            if (!$basePeriod) {
                return $this->errorResponse('Payroll period not found.');
            }

            $year  = date('Y', strtotime($basePeriod->release_date));
            $month = date('n', strtotime($basePeriod->release_date));

            // All posted, active periods for this interval & month (1st + 2nd half)
            $periodIds = DB::table('payroll_periods')
                ->where('payroll_interval_id', $basePeriod->payroll_interval_id)
                ->whereYear('release_date', $year)
                ->whereMonth('release_date', $month)
                ->where('active', true)
                ->where('posted', true)
                ->pluck('id');

            if ($periodIds->isEmpty()) {
                return $this->errorResponse('No payroll periods found for this month.');
            }

            // 2) Load all summaries for this employee across those periods
            $rawSummaries = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('departments as c', 'c.id', '=', 'b.department_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('employment_types as e', 'e.id', '=', 'b.employment_type_id')
                ->join('payroll_periods as h', 'a.payroll_period_id', '=', 'h.id')
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
                    'a.ot_amount',
                    'a.nd_amount',
                    'a.holiday_amount',
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
                    'a.lwop_amount',
                    'a.net_pay',
                    'h.release_date'
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->where('a.employee_id', $id)
                ->orderBy('b.first_name', 'asc')
                ->get();

            if ($rawSummaries->isEmpty()) {
                return $this->errorResponse('No data to print.');
            }


            $first_pay = DB::table('payroll_periods as p')
                ->join('payroll_cutoffs as c', 'p.payroll_cutoff_id', '=', 'c.id')
                ->join('payroll_summaries as s', 'p.id', '=', 's.payroll_period_id')
                ->whereIn('p.id', $periodIds)
                ->where('s.employee_id', $id, 'p.')
                ->where('p.payroll_cutoff_id', '=', '1')
                ->select('s.net_pay', 'p.release_date', 'c.name as cutoff_name', 'p.id as period_id')
                ->orderBy('p.release_date', 'asc')
                ->first();
            $second_pay = DB::table('payroll_periods as p')
                ->join('payroll_cutoffs as c', 'p.payroll_cutoff_id', '=', 'c.id')
                ->join('payroll_summaries as s', 'p.id', '=', 's.payroll_period_id')
                ->whereIn('p.id', $periodIds)
                ->where('s.employee_id', $id, 'p.')
                ->where('p.payroll_cutoff_id', '=', value: '3')
                ->select('s.net_pay', 'p.release_date', 'c.name as cutoff_name', 'p.id as period_id')
                ->orderBy('p.release_date', 'asc')
                ->first();


            // 3) Use the row with the highest gross_amount as the "main" monthly summary
            $primary = $rawSummaries->sortByDesc('gross_amount')->first();
            // Assign first_pay and second_pay from the queries
            $primary->first_pay = $first_pay ? (float) ($first_pay->net_pay ?? 0) : 0;
            $primary->second_pay = $second_pay ? (float) ($second_pay->net_pay ?? 0) : 0;
            // Sum attendance across all periods for the month

            $primary->gsis = $rawSummaries->sum('gsis');
            $primary->philhealth = $rawSummaries->sum('philhealth');
            $primary->total_deduction = $rawSummaries->sum('total_deduction');
            $primary->late_amount   = $rawSummaries->sum('late_amount');
            $primary->ut_amount     = $rawSummaries->sum('ut_amount');
            $primary->absent_amount = $rawSummaries->sum('absent_amount');
            $primary->tax = $rawSummaries->sum('tax');
            // Keep gsis/pagibig/philhealth/tax/total_deduction/net_pay/gross_amount from the main row
            $payrolls = collect([$primary]);

            // 4) Incomes from all periods in the months
            $incomes = DB::table("payroll_incomes as a")
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.amount',
                    'b.name as income'
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->where('a.employee_id', $id)
                ->where('a.amount', '>', 0)
                ->get();

            // 5) Deductions from all periods in the month
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name as deduction',
                    DB::raw("isnull(a.amount,0) as amount")
                )
                ->whereIn('a.payroll_period_id', $periodIds)
                ->where('a.employee_id', $id)
                ->where('a.amount', '>', 0)
                ->get();

            $signatories = [
                'name' => null,
                'position' => null,
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
            $pdfContent = $pdf->output();

            $filename = "payslip_{$id}_{$payroll_id}_" . date('Y-m-d') . ".pdf";

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=\"" . $filename . "\"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payslip: ' . $e->getMessage());
        }
    }
}
