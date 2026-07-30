<?php

namespace App\Http\Controllers;

use PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisbursementVoucherController extends Controller
{
    use ApiResponse;

    public function dvPayroll($id)
    {
        try {
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $data = DB::table('payroll_summaries as a')
                ->join('payroll_periods as b', 'a.payroll_period_id', '=', 'b.id')
                ->select(
                    DB::raw("CONCAT('MONTHLY PAYROLL - ',DATENAME(MONTH,b.release_date),' ',DATEPART(YEAR,b.release_date)) as particular"),
                    DB::raw("sum(case when isnull(a.net_pay,0) < 0 then 0 else isnull(a.net_pay,0) end) as amount")
                )
                ->where('payroll_period_id', $id)
                ->groupBy('b.release_date')
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('No payroll data found for the specified period');
            }

            $pdf = PDF::loadView('disbursement_vouchers.disbursement_vouchers_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "dv_payroll_{$id}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payroll disbursement voucher: ' . $e->getMessage());
        }
    }

    public function dvOvertime($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $particulars = DB::table('payroll_periods as a')
                ->select(
                    DB::raw("CONCAT('OVERTIME PAYROLL - ',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date)) as particular"),
                )
                ->where('a.id', $id)
                ->get();

            if ($particulars->isEmpty()) {
                return $this->notFoundResponse('Payroll period not found');
            }

            $particular = $particulars[0]->particular;

            $data = DB::table('overtime_payroll_headers as e')
                ->join('overtime_payroll_details as d', 'e.id', '=', 'd.overtime_payroll_id')
                ->join('overtime_applications as f', 'd.employee_id', '=', 'f.employee_id')
                ->join('overtime_types as c', 'f.overtime_type_id', '=', 'c.id')
                ->join('employees as b', 'f.employee_id', '=', 'b.id')
                ->select(
                    DB::raw("'$particular' as particular"),
                    'f.date',
                    'b.salary',
                    'c.rate',
                    'f.employee_id',
                    DB::raw('SUM(f.total_hours) as total_hours'),
                    DB::raw("
                    (
                        (((b.salary / 22) / 8) * SUM(f.total_hours)) * c.rate
                    ) as earned
                ")
                )
                ->where([
                    'f.approved' => true,
                    'f.approved_2' => true,
                    'f.payroll' => true,
                    'e.payroll_period_id' => $id,
                    'e.posted' => true
                ])
                ->whereIn('e.id', function ($query) use ($id) {
                    $query->select('a.id')->from('overtime_payroll_headers as a')
                        ->join('overtime_payroll_details as b', 'a.id', '=', 'b.overtime_payroll_id')
                        ->where('a.payroll_period_id', $id)
                        ->where('a.posted', true);
                })
                ->groupBy(
                    'f.date',
                    'b.salary',
                    'c.rate',
                    'f.employee_id',
                    'e.id'
                )
                ->get();

            $employees = DB::table('overtime_payroll_headers as a')
                ->join('overtime_payroll_details as b', 'a.id', '=', 'b.overtime_payroll_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->join('positions as d', 'c.position_id', '=', 'd.id')
                ->join('overtime_applications as e', function ($join) {
                    $join->on('b.employee_id', '=', 'e.employee_id');
                    $join->on('b.overtime_type_id', '=', 'e.overtime_type_id');
                })
                ->join('overtime_types as f', 'e.overtime_type_id', '=', 'f.id')
                ->select(
                    'b.employee_id',
                    'c.photo',
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted, 0) = 0 THEN
                               CONCAT(c.first_name, ' ', c.last_name)
                           ELSE
                               RTRIM([dbo].[ufn_DecryptString](c.first_name, '$app_key')) + ' ' + RTRIM([dbo].[ufn_DecryptString](c.last_name, '$app_key')) 
                           END as name"),
                    DB::raw("SUM(e.total_hours) AS total_hours"),
                    'd.name as position',
                    'c.salary',
                    DB::raw("MAX(f.name) as ot_type"),
                    DB::raw("MAX(f.rate) as rate"),
                    DB::raw("MAX(b.id) as dtl_id"),
                    DB::raw("MAX(a.id) as id")
                )
                ->where([
                    'a.id' => $id,
                    'e.payroll' => true,
                    'e.approved' => true,
                    'e.approved_2' => true
                ])
                ->groupBy([
                    'b.employee_id',
                    'c.photo',
                    'c.is_encrypted',
                    'c.employee_no',
                    'c.first_name',
                    'c.last_name',
                    'd.name',
                    'c.salary'
                ])
                ->distinct()
                ->get();

            if ($data->isEmpty() && $employees->isEmpty()) {
                return $this->notFoundResponse('No overtime data found for the specified period');
            }

            $pdf = PDF::loadView('disbursement_vouchers.disbursement_vouchers_report', compact(
                'image',
                'company',
                'data',
                'employees'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "dv_overtime_{$id}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate overtime disbursement voucher: ' . $e->getMessage());
        }
    }

    public function dvRATA($id)
    {
        try {
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $data = DB::table('rata_payroll_headers as a')
                ->join('rata_payroll_details as b', 'a.id', '=', 'b.rata_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->select(
                    DB::raw("CONCAT('REPRESENTATION & TRANSPORTATION PAYROLL - ',a.month,' ',a.year_id) as particular"),
                    DB::raw("(sum(b.ra_amount) + sum(b.ta_amount)) as amount")
                )
                ->where('a.id', $id)
                ->groupBy(
                    'a.month',
                    'a.year_id'
                )
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('No RATA data found for the specified period');
            }

            $pdf = PDF::loadView('disbursement_vouchers.disbursement_vouchers_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "dv_rata_{$id}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate RATA disbursement voucher: ' . $e->getMessage());
        }
    }

    public function dvMonetization($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
            $monetization_setup = DB::table('monetization_setups')->get();

            if ($monetization_setup->isNotEmpty()) {
                $cf_rate = $monetization_setup[0]->cf_rate;
            } else {
                $cf_rate = 0.0481927;
            }

            if ($cf_rate == 0) {
                $cf_rate = 0.0481927;
            }

            $data = DB::table('monetization_payroll_headers as a')
                ->join('monetization_payroll_details as b', 'a.id', '=', 'b.monetization_payroll_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->join('leave_monetizations as e', 'b.monetization_id', '=', 'e.id')
                ->select(
                    'c.id as employee_id',
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                    CONCAT(c.first_name,' ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                                END as name"),
                    DB::raw("CONCAT(CAST((c.salary/22) AS DECIMAL(18,2)),' * ',e.total_days,' * ',$cf_rate) as particular"),
                    DB::raw("e.amount as amount"),
                    'e.total_days'
                )
                ->where('a.id', $id)
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('No monetization data found for the specified period');
            }

            $pdf = PDF::loadView('disbursement_vouchers.disbursement_vouchers_report_new', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "dv_monetization_{$id}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate monetization disbursement voucher: ' . $e->getMessage());
        }
    }

    public function dvClothing($id)
    {
        try {
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $data = DB::table('uniform_clothing_header as a')
                ->join('uniform_clothing_details as b', 'a.id', '=', 'b.uniform_clothing_header_id')
                ->join('employees as c', 'c.id', '=', 'b.employee_id')
                ->join('uniform_clothing_setup as h', 'h.id', '=', 'b.uniform_clothing_setup_id')
                ->join('months', 'months.id', '=', 'a.month_id')
                ->select(
                    DB::raw("CONCAT('Uniform & Clothing Allowance Payroll - ',months.name,' ',a.year) as particular"),
                    DB::raw("(sum(h.cloth_rate) + sum(h.uniform_rate)) as amount")
                )
                ->where('a.id', $id)
                ->groupBy(
                    'months.name',
                    'a.year'
                )
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('No clothing allowance data found for the specified period');
            }

            $pdf = PDF::loadView('disbursement_vouchers.disbursement_vouchers_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "dv_clothing_{$id}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate clothing allowance disbursement voucher: ' . $e->getMessage());
        }
    }

    public function dvLoyalty($id)
    {
        try {
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $data = DB::table('loyalty_award_header as a')
                ->join('loyalty_award_details as b', 'a.id', '=', 'b.loyalty_award_header_id')
                ->join('employees as c', 'c.id', '=', 'b.employee_id')
                ->join('loyalty_award_setup as h', 'h.id', '=', 'b.loyalty_award_setup_id')
                ->join('months', 'months.id', '=', 'a.month_id')
                ->select(
                    DB::raw("CONCAT('Loyalty Award Payroll - ',months.name,' ',a.year) as particular"),
                    DB::raw("sum(h.cash_award) as amount")
                )
                ->where('a.id', $id)
                ->groupBy(
                    'months.name',
                    'a.year'
                )
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('No loyalty award data found for the specified period');
            }

            $pdf = PDF::loadView('disbursement_vouchers.disbursement_vouchers_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "dv_loyalty_{$id}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate loyalty award disbursement voucher: ' . $e->getMessage());
        }
    }

    public function dvMidYear($year_id)
    {
        try {
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $data = DB::table('midyear_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    DB::raw("CONCAT('Mid Year Bonus - ',a.years) as particular"),
                    DB::raw("sum(a.bonus_amount) as amount")
                )
                ->where([
                    'a.years' => $year_id,
                    'b.active' => true,
                    'b.is_employee' => true,
                ])
                ->groupBy('a.years')
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('No mid-year bonus data found for the specified year');
            }

            $pdf = PDF::loadView('disbursement_vouchers.disbursement_vouchers_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "dv_midyear_{$year_id}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate mid-year bonus disbursement voucher: ' . $e->getMessage());
        }
    }

    public function dvYearEnd($year_id)
    {
        try {
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $data = DB::table('yearend_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    DB::raw("CONCAT('Year End Bonus - ',a.years) as particular"),
                    DB::raw("sum((isnull(a.bonus_amount,0) + isnull(a.cash_gift_amount,0))) as amount")
                )
                ->where([
                    'a.years' => $year_id,
                    'b.active' => true,
                    'b.is_employee' => true
                ])
                ->groupBy('a.years')
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('No year-end bonus data found for the specified year');
            }

            $pdf = PDF::loadView('disbursement_vouchers.disbursement_vouchers_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "dv_yearend_{$year_id}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate year-end bonus disbursement voucher: ' . $e->getMessage());
        }
    }
}
