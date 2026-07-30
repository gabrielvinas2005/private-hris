<?php

namespace App\Http\Controllers;

use PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObligationRequestController extends Controller
{
    use ApiResponse;

    public function orsPayroll($id)
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

            $pdf = PDF::loadView('obligation_requests.obligation_request_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'ors_payroll_' . $id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ORS Payroll PDF: ' . $e->getMessage());
        }
    }

    public function orsOvertime($id)
    {
        try {
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $particulars = DB::table('payroll_periods as a')
                ->select(
                    DB::raw("CONCAT('OVERTIME PAYROLL - ',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date)) as particular"),
                )
                ->where('a.id', $id)
                ->get();

            $particular = $particulars[0]->particular;

            $data = DB::table('overtime_payroll_headers as e')
                ->join('overtime_payroll_details as d', 'e.id', '=', 'd.overtime_payroll_id')
                ->join('overtime_applications as f', 'd.employee_id', '=', 'f.employee_id')
                ->join('overtime_types as c', 'f.overtime_type_id', '=', 'c.id')
                ->join('employees as b', 'f.employee_id', '=', 'b.id')
                ->select(
                    DB::raw("'$particular' as particular"),
                    DB::raw("(
                    (((b.salary/ 22 / 8) * c.rate) * f.total_hours) 
                    - (
                            (
                                select top(1) (isnull(percentage,0) / 100) from overtime_taxes 
                                where 
                                amount_from <= ((((b.salary/ 22 / 8) * c.rate) * f.total_hours)) 
                                and 
                                amount_to >= ((((b.salary/ 22 / 8) * c.rate) * f.total_hours))
                            ) 
                        * (((b.salary/ 22 / 8) * c.rate) * f.total_hours)
                      )
                    ) AS amount")
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
                        ->where('a.posted', true)
                        ->distinct();
                })
                ->distinct()
                ->get();

            $pdf = PDF::loadView('obligation_requests.obligation_request_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'ors_overtime_' . $id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ORS Overtime PDF: ' . $e->getMessage());
        }
    }

    public function orsRATA($id)
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

            $pdf = PDF::loadView('obligation_requests.obligation_request_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'ors_rata_' . $id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ORS RATA PDF: ' . $e->getMessage());
        }
    }

    public function orsMonetization($id)
    {
        try {
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $data = DB::table('monetization_payroll_headers as a')
                ->leftJoin('monetization_payroll_details as b', 'a.id', '=', 'b.monetization_payroll_id')
                ->leftJoin('employees as c', 'b.employee_id', '=', 'c.id')
                ->leftJoin('leave_monetizations as e', 'b.monetization_id', '=', 'e.id')
                ->select(
                    DB::raw("CONCAT('MONETIZATION PAYROLL - ', COALESCE(a.month,''),' ', COALESCE(a.year_id,'')) as particular"),
                    DB::raw("COALESCE(SUM(e.amount),0) as amount")
                )
                ->where('a.id', $id)
                ->groupBy(
                    'a.month',
                    'a.year_id'
                )
                ->get();

            $pdf = PDF::loadView('obligation_requests.obligation_request_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'ors_monetization_' . $id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ORS Monetization PDF: ' . $e->getMessage());
        }
    }

    public function orsClothing($id)
    {
        try {
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $data = DB::table('uniform_clothing_header as a')
                ->leftJoin('uniform_clothing_details as b', 'a.id', '=', 'b.uniform_clothing_header_id')
                ->leftJoin('employees as c', 'c.id', '=', 'b.employee_id')
                ->leftJoin('uniform_clothing_setup as h', 'h.id', '=', 'b.uniform_clothing_setup_id')
                ->join('months', 'months.id', '=', 'a.month_id')
                ->select(
                    DB::raw("CONCAT('Uniform & Clothing Allowance Payroll - ',months.name,' ',a.year) as particular"),
                    DB::raw("SUM(ISNULL(h.cloth_rate, 0) + ISNULL(h.uniform_rate, 0)) as amount")
                )
                ->where('a.id', $id)
                ->groupBy('months.name', 'a.year')
                ->get();

            $pdf = PDF::loadView('obligation_requests.obligation_request_report', compact('image','company','data'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();

            $filename = 'ors_clothing_' . $id . '_' . date('Y-m-d') . '.pdf';
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ORS Clothing PDF: ' . $e->getMessage());
        }
    }

    public function orsLoyalty($id)
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

            $pdf = PDF::loadView('obligation_requests.obligation_request_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'ors_loyalty_' . $id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ORS Loyalty PDF: ' . $e->getMessage());
        }
    }

    public function orsMidYear(Request $request, $year_id)
    {
        try {
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $branchId = $request->query('branch_id');
            $departmentId = $request->query('department_id');

            $query = DB::table('midyear_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    DB::raw("CONCAT('Mid Year Bonus - ',a.years) as particular"),
                    DB::raw("sum(a.bonus_amount) as amount")
                )
                ->where([
                    'a.years' => $year_id,
                    'b.active' => true,
                    'b.is_employee' => true,
                ]);

            if (!empty($branchId)) {
                $query->where('a.branch_id', $branchId);
            }

            if (!empty($departmentId) && $departmentId !== 'all') {
                $query->where('a.department_id', $departmentId);
            }

            $data = $query->groupBy('a.years')->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('No mid-year bonus data found for the selected filters');
            }

            $pdf = PDF::loadView('obligation_requests.obligation_request_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'ors_midyear_' . $year_id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ORS Mid Year PDF: ' . $e->getMessage());
        }
    }

    public function orsYearEnd($year_id)
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

            $pdf = PDF::loadView('obligation_requests.obligation_request_report', compact(
                'image',
                'company',
                'data'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'ors_yearend_' . $year_id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ORS Year End PDF: ' . $e->getMessage());
        }
    }
}
