<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Support\PayslipPrecedingTotals;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
            $emp_id_data = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->selectRaw('case when b.id = null then 0 else b.id end as id')
                ->where('a.id', $id)
                ->get();

            if ($emp_id_data->isNotEmpty()) {
                // Only show periods where the ENTIRE month is posted.
                // For semi-monthly intervals (2 cutoffs), both halves must be posted before
                // the employee can view or print a payslip.
                $payslip_records = DB::table('payroll_periods as a')
                    ->join('payroll_intervals as b', 'b.id', '=', 'a.payroll_interval_id')
                    ->join('payroll_cutoffs as c', 'c.id', '=', 'a.payroll_cutoff_id')
                    ->join('payroll_summaries as s', 's.payroll_period_id', '=', 'a.id')
                    ->select(
                        'a.id',
                        's.employee_id',
                        'b.name as payroll_interval',
                        'c.name as cut_off',
                        'a.payroll_start_date',
                        'a.payroll_end_date'
                    )
                    ->where('s.employee_id', $emp_id_data[0]->id)
                    ->where('a.posted', 1)
                    ->where(function ($q) {
                        // Monthly intervals (1 cutoff): already filtered by posted = 1 above.
                        // Semi-monthly intervals: ensure NO other half of the same month is still unposted.
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
                    ->orderBy('a.payroll_start_date', 'asc')
                    ->distinct()
                    ->get();

                if ($payslip_records->isEmpty()) {
                    $daily_payslip_records = array(
                        'id' => 0,
                        'employee_id' => $emp_id_data[0]->id,
                        'payroll_interval' => null,
                        'cut_off' => null,
                        'payroll_start_date' => null,
                        'payroll_end_date' => null
                    );

                    $payslip_records = (object)$daily_payslip_records;
                    $payslip_records = collect([$payslip_records]);
                }
            } else {
                $dummy_daily_time_records = array(
                    'id' => 0,
                    'employee_id' => 0,
                    'payroll_interval' => null,
                    'cut_off' => null,
                    'payroll_start_date' => null,
                    'payroll_end_date' => null
                );

                $payslip_records = (object)$dummy_daily_time_records;
                $payslip_records = collect([$payslip_records]);
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

            $payrolls = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('divisions as c', 'c.id', '=', 'b.division_id')
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
                ->where(['payroll_period_id' => $payroll_id, 'a.employee_id' => $id])
                ->orderBy('b.first_name', 'asc')
                ->get();

            if ($payrolls->isEmpty()) {
                return $this->notFoundResponse('Payslip data not found');
            }

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name")
                )
                ->where(['a.id' => $payroll_id])
                ->orderBy('a.release_date', 'desc')
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Payroll period not found');
            }

            $payroll_period = $data[0]->name;

            // Get additional incomes
            $incomes = DB::table("payroll_incomes as a")
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.amount',
                    'b.name as income'
                )
                ->where([
                    'a.payroll_period_id' => $payroll_id,
                    'a.employee_id' => $id
                ])
                ->where('a.amount', '>', 0)
                ->get();

            // Get additional deductions
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name as deduction',
                    DB::raw("isnull(a.amount,0) as amount")
                )
                ->where([
                    'a.payroll_period_id' => $payroll_id,
                    'a.employee_id' => $id
                ])
                ->where('a.amount', '>', 0)
                ->get();

            return $this->successResponse([
                'payroll_period' => $payroll_period,
                'payrolls' => $payrolls,
                'incomes' => $incomes,
                'deductions' => $deductions,
                'data' => $data
            ], 'Payslip data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payslip data: ' . $e->getMessage());
        }
    }

    public function print($id, $payroll_id)
    {
        try {
            $blockMsg = $this->requireFullMonthPosted((int) $payroll_id);
            if ($blockMsg) return $this->errorResponse($blockMsg);

            $app_key = env("APP_KEY", "");
            $logo1 = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
            $logo2 = base64_encode(file_get_contents(public_path('/dist/img/reports/Pilipinas_logo.png')));
            $companies = DB::table('companies')->get();

            $payrolls = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('divisions as c', 'c.id', '=', 'b.division_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('employment_types as e', 'e.id', '=', 'b.employment_type_id')
                ->leftJoin('time_data_summary as tds', function ($join) use ($payroll_id) {
                    $join->on('tds.employee_id', '=', 'a.employee_id')
                        ->where('tds.payroll_period_id', '=', $payroll_id);
                })
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
                    DB::raw('ISNULL(tds.Adjustment_Amount, 0) as preceding_period_adjustment'),
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
                    'j.name as cutoff_name',
                )
                ->where(['a.payroll_period_id' => $payroll_id, 'a.employee_id' => $id])
                ->orderBy('b.first_name', 'asc')
                ->get();

            if ($payrolls->isEmpty()) {
                return $this->errorResponse('No data to print.');
            }

            $payrolls = $payrolls->map(function ($row) use ($id) {
                $netPay = (float) ($row->net_pay ?? 0);
                $cutoffLower = strtolower((string) ($row->cutoff_name ?? ''));
                if (str_contains($cutoffLower, 'monthly')) {
                    $firstHalfWhole = (float) floor($netPay / 2);
                    $row->first_pay = (float) round($firstHalfWhole, 2);
                    $row->second_pay = max((float) round($netPay - $firstHalfWhole, 2), 0.0);
                } else {
                    $row->first_pay = $netPay;
                    $row->second_pay = 0.0;
                }
                $prec = PayslipPrecedingTotals::fromTimeDataSummary(
                    (int) $id,
                    (int) ($row->payroll_period_id ?? 0)
                );
                $row->preceding_late_amount = $prec['preceding_late_amount'];
                $row->preceding_ut_amount = $prec['preceding_ut_amount'];
                $row->preceding_absent_amount = $prec['preceding_absent_amount'];
                $row->preceding_deduction_total = $prec['preceding_deduction_total'];
                return $row;
            });

            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name as deduction',
                    DB::raw("isnull(a.amount,0) as amount")
                )
                ->where([
                    'a.payroll_period_id' => $payroll_id,
                    'a.employee_id' => $id
                ])
                ->where('a.amount', '>', 0)
                ->get();

            $incomes = DB::table("payroll_incomes as a")
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.amount',
                    'b.name as income'
                )
                ->where([
                    'a.payroll_period_id' => $payroll_id,
                    'a.employee_id' => $id
                ])
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
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payslip: ' . $e->getMessage());
        }
    }
}
