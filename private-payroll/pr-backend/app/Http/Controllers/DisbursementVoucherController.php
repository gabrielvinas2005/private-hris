<?php

namespace App\Http\Controllers;

use PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

            // Aggregate to a single amount and use unified individual DV layout
            $amount = $data->sum(function ($row) {
                return isset($row->amount) ? (float) $row->amount : 0;
            });

            $first = $data->first();
            $releaseDate = $first->release_date ?? null;
            $year = $releaseDate ? date('Y', strtotime($releaseDate)) : date('Y');
            $bonusYear = $year;
            $description = $first->particular ?? 'Monthly Payroll';
            $employee = (object) ['name' => 'Various Employees'];
            $current_date = date('F d, Y');
            $signatories = [];

            $pdf = PDF::loadView('disbursement_vouchers.individual_DV_report', compact(
                'image',
                'company',
                'employee',
                'bonusYear',
                'year',
                'amount',
                'description',
                'signatories',
                'current_date'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('letter', 'portrait');
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

            // Aggregate total amount for DV and use unified individual DV layout
            $amount = $data->sum(function ($row) {
                return isset($row->earned) ? (float) $row->earned : 0;
            });

            $firstDate = $data->first()->date ?? null;
            $year = $firstDate ? date('Y', strtotime($firstDate)) : date('Y');
            $bonusYear = $year;
            $description = 'Overtime Payroll';
            $employee = (object) ['name' => 'Various Employees'];
            $current_date = date('F d, Y');
            $signatories = [];

            $pdf = PDF::loadView('disbursement_vouchers.individual_DV_report', compact(
                'image',
                'company',
                'employee',
                'bonusYear',
                'year',
                'amount',
                'description',
                'signatories',
                'current_date'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('letter', 'portrait');
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

            $amount = $data->sum(function ($row) {
                return isset($row->amount) ? (float) $row->amount : 0;
            });

            $first = $data->first();
            $year = isset($first->year_id) ? (int) $first->year_id : date('Y');
            $bonusYear = $year;
            $description = 'RATA Payroll';
            $employee = (object) ['name' => 'Various Employees'];
            $current_date = date('F d, Y');
            $signatories = [];

            $pdf = PDF::loadView('disbursement_vouchers.individual_DV_report', compact(
                'image',
                'company',
                'employee',
                'bonusYear',
                'year',
                'amount',
                'description',
                'signatories',
                'current_date'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('letter', 'portrait');
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
                ->leftJoin('monetization_payroll_details as b', 'a.id', '=', 'b.monetization_payroll_id')
                ->leftJoin('employees as c', 'b.employee_id', '=', 'c.id')
                ->leftJoin('leave_monetizations as e', 'b.monetization_id', '=', 'e.id')
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

            $amount = $data->sum(function ($row) {
                return isset($row->amount) ? (float) $row->amount : 0;
            });

            $first = $data->first();
            $year = date('Y');
            $bonusYear = $year;
            $description = 'Monetization Payroll';
            $employee = (object) ['name' => 'Various Employees'];
            $current_date = date('F d, Y');
            $signatories = [];

            $pdf = PDF::loadView('disbursement_vouchers.individual_DV_report', compact(
                'image',
                'company',
                'employee',
                'bonusYear',
                'year',
                'amount',
                'description',
                'signatories',
                'current_date'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('letter', 'portrait');
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
                ->leftJoin('uniform_clothing_details as b', 'a.id', '=', 'b.uniform_clothing_header_id')
                ->leftJoin('employees as c', 'c.id', '=', 'b.employee_id')
                ->leftJoin('uniform_clothing_setup as h', 'h.id', '=', 'b.uniform_clothing_setup_id')
                ->join('months', 'months.id', '=', 'a.month_id')
                ->select(
                    DB::raw("CONCAT('Uniform & Clothing Allowance Payroll - ',months.name,' ',a.year) as particular"),
                    DB::raw("SUM(ISNULL(h.cloth_rate, 0) + ISNULL(h.uniform_rate, 0)) as amount"),
                    'a.year'
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

            $amount = $data->sum(function ($row) {
                return isset($row->amount) ? (float) $row->amount : 0;
            });

            $first = $data->first();
            $year = isset($first->year) ? (int) $first->year : date('Y');
            $bonusYear = $year;
            $description = 'Uniform & Clothing Allowance Payroll';
            $employee = (object) ['name' => 'Various Employees'];
            $current_date = date('F d, Y');
            $signatories = [];

            $pdf = PDF::loadView('disbursement_vouchers.individual_DV_report', compact(
                'image',
                'company',
                'employee',
                'bonusYear',
                'year',
                'amount',
                'description',
                'signatories',
                'current_date'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('letter', 'portrait');
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
                ->leftJoin('loyalty_award_details as b', 'a.id', '=', 'b.loyalty_award_header_id')
                ->leftJoin('loyalty_award_setup as h', 'h.id', '=', 'b.loyalty_award_setup_id')
                ->join('months', 'months.id', '=', 'a.month_id')
                ->select(
                    DB::raw("CONCAT('Loyalty Award Payroll - ',months.name,' ',a.year) as particular"),
                    DB::raw("COALESCE(SUM(h.cash_award), 0) as amount"),
                    'a.year'
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

            $amount = $data->sum(function ($row) {
                return isset($row->amount) ? (float) $row->amount : 0;
            });

            $first = $data->first();
            $year = isset($first->year) ? (int) $first->year : date('Y');
            $bonusYear = $year;
            $description = 'Loyalty Award Payroll';
            $employee = (object) ['name' => 'Various Employees'];
            $current_date = date('F d, Y');
            $signatories = [];

            $pdf = PDF::loadView('disbursement_vouchers.individual_DV_report', compact(
                'image',
                'company',
                'employee',
                'bonusYear',
                'year',
                'amount',
                'description',
                'signatories',
                'current_date'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('letter', 'portrait');
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

    public function dvMidYear(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'years' => 'required|integer|min:2000|max:2100',
                'branch_id' => 'nullable|integer|exists:branches,id',
                'department_id' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if ($value === null || $value === '' || $value === 'all') {
                            return;
                        }
                        if (!is_numeric($value)) {
                            $fail('The selected department is invalid.');
                            return;
                        }
                        $exists = DB::table('departments')->where('id', $value)->exists();
                        if (!$exists) {
                            $fail('The selected department is invalid.');
                        }
                    }
                ],
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $year_id = $request->years;
            $branchId = $request->branch_id;
            $departmentId = $request->department_id;
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $query = DB::table('midyear_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
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

            $data = $query->first();

            if (!$data || !$data->amount || $data->amount <= 0) {
                return $this->notFoundResponse('No mid-year bonus data found for the specified year');
            }

            $total_amount = $data->amount;
            $year = $year_id;
            $bonusYear = $year;
            $current_date = date('F d, Y');
            $description = 'Mid-year Bonus Payroll';
            $employee = (object) ['name' => 'Various Employees'];

            // Get signatories from request or use defaults
            $signatories = [
                'certifying_officer_name' => $request->certifying_officer_name ?? 'MARIA ANTONIETTE S. ZOILO',
                'certifying_officer_position' => $request->certifying_officer_position ?? 'Administrative Officer V',
                'accountant_name' => $request->accountant_name ?? 'GERALENE Q. NADELA',
                'accountant_position' => $request->accountant_position ?? 'Accountant III',
                'accountant_role' => $request->accountant_role ?? 'Head, Accounting Unit/Authorized Representative',
                'approving_officer_name' => $request->approving_officer_name ?? 'CLARE MARI S. TORRALBA',
                'approving_officer_position' => $request->approving_officer_position ?? 'Executive Director',
                'approving_officer_role' => $request->approving_officer_role ?? 'Agency Head/Authorized Representative',
            ];

            $amount = $total_amount;

            $pdf = PDF::loadView('disbursement_vouchers.individual_DV_report', compact(
                'image',
                'company',
                'employee',
                'bonusYear',
                'year',
                'amount',
                'description',
                'signatories',
                'current_date'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('letter', 'portrait');
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

            $amount = $data->sum(function ($row) {
                return isset($row->amount) ? (float) $row->amount : 0;
            });

            $year = $year_id;
            $bonusYear = $year;
            $description = 'Year End Bonus Payroll';
            $employee = (object) ['name' => 'Various Employees'];
            $current_date = date('F d, Y');
            $signatories = [];

            $pdf = PDF::loadView('disbursement_vouchers.individual_DV_report', compact(
                'image',
                'company',
                'employee',
                'bonusYear',
                'year',
                'amount',
                'description',
                'signatories',
                'current_date'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('letter', 'portrait');
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
