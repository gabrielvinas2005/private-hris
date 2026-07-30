<?php

namespace App\Http\Controllers;

use PDF;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GSISRemittanceController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $payroll_intervals = DB::table('payroll_intervals')->get();
            $departments = DB::table('departments')->get();

            return $this->successResponse([
                'payroll_intervals' => $payroll_intervals,
                'departments' => $departments
            ], 'GSIS remittance form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load GSIS remittance form data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'payroll_interval_id' => 'required',
                'payroll_period_id' => 'required',
                'department_id' => 'required',
            ], [
                'payroll_interval_id.required' => 'Please select Payroll Interval',
                'payroll_period_id.required' => 'Please select Payroll Period',
                'department_id.required' => 'Please select Department',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $companies = DB::table('companies')->get();

            $gsis_remittances = DB::table('payroll_summaries')
                ->join('employees', 'payroll_summaries.employee_id', '=', 'employees.id')
                ->join('departments', 'employees.department_id', '=', 'departments.id')
                ->join('payroll_periods', 'payroll_summaries.payroll_period_id', '=', 'payroll_periods.id')
                ->join('payroll_intervals', 'payroll_periods.payroll_interval_id', '=', 'payroll_intervals.id')
                ->leftJoin('name_prefixes', 'employees.name_prefix_id', '=', 'name_prefixes.id')
                ->leftJoin('name_suffixes', 'employees.name_suffix_id', '=', 'name_suffixes.id')
                ->where([
                    'payroll_summaries.payroll_period_id' => $request->payroll_period_id,
                    'employees.department_id' => $request->department_id,
                    'employees.is_employee' => true,
                    'employees.active' => true
                ])
                ->select(
                    'employees.id as employee_id',
                    DB::raw("CASE WHEN employees.is_encrypted = 1 THEN
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))
                                ELSE
                                    employees.first_name
                                END as first_name"),
                    DB::raw("CASE WHEN employees.is_encrypted = 1 THEN
                                    RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                ELSE
                                    employees.last_name
                                END as last_name"),
                    DB::raw("CASE WHEN employees.is_encrypted = 1 THEN
                                    RTRIM(SUBSTRING([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'.'
                                ELSE
                                    SUBSTRING(employees.middle_name,1,1)+'.'
                                END as middle_name"),
                    'name_prefixes.name as name_prefix',
                    'name_suffixes.name as name_suffix',
                    'employees.crn_no',
                    'employees.salary',
                    'employees.birthdate',
                    'employees.gsis_no',
                    'departments.code as department_code',
                    'payroll_periods.release_date',
                    'payroll_summaries.gsis'
                )
                ->orderBy('employees.id', 'asc')
                ->get();

            if ($gsis_remittances->isEmpty()) {
                return $this->notFoundResponse('No GSIS remittance data found for the selected criteria');
            }

            $gsis_loan_headers = DB::table('deductions as a')
                ->join('payroll_deductions as b', 'a.id', '=', 'b.deduction_id')
                ->join('payroll_summaries as c', function ($join) {
                    $join->on('b.payroll_period_id', '=', 'c.payroll_period_id');
                    $join->on('b.employee_id', '=', 'c.employee_id');
                })
                ->join('employees as d', 'b.employee_id', '=', 'd.id')
                ->select(
                    'a.id as deduction_id',
                    'a.name'
                )
                ->where([
                    'a.is_gsis' => true,
                    'a.active' => true,
                    'b.payroll_period_id' => $request->payroll_period_id,
                    'd.department_id' => $request->department_id,
                ])
                ->where('b.amount', '>', 0)
                ->distinct()
                ->orderBy('a.name', 'asc')
                ->get();

            $gsis_loan_totals = DB::table('deductions as a')
                ->join('payroll_deductions as b', 'a.id', '=', 'b.deduction_id')
                ->join('payroll_summaries as c', function ($join) {
                    $join->on('b.payroll_period_id', '=', 'c.payroll_period_id');
                    $join->on('b.employee_id', '=', 'c.employee_id');
                })
                ->join('employees as d', 'b.employee_id', '=', 'd.id')
                ->select(
                    'a.id as deduction_id',
                    'a.name',
                    DB::raw("SUM(b.amount) as amount")
                )
                ->where([
                    'a.is_gsis' => true,
                    'a.active' => true,
                    'b.payroll_period_id' => $request->payroll_period_id,
                    'd.department_id' => $request->department_id,
                ])
                ->where('b.amount', '>', 0)
                ->groupBy([
                    'a.id',
                    'a.name',
                ])
                ->orderBy('a.name', 'asc')
                ->get();

            $gsis_loans = DB::table('deductions as a')
                ->join('payroll_deductions as b', 'a.id', '=', 'b.deduction_id')
                ->join('payroll_summaries as c', function ($join) {
                    $join->on('b.payroll_period_id', '=', 'c.payroll_period_id');
                    $join->on('b.employee_id', '=', 'c.employee_id');
                })
                ->join('employees as d', 'b.employee_id', '=', 'd.id')
                ->select(
                    'a.id as deduction_id',
                    'b.employee_id',
                    'a.name',
                    'b.amount'
                )
                ->where([
                    'a.is_gsis' => true,
                    'a.active' => true,
                    'b.payroll_period_id' => $request->payroll_period_id,
                    'd.department_id' => $request->department_id,
                ])
                ->where('b.amount', '>', 0)
                ->orderBy('a.name', 'asc')
                ->get();

            $signatories = [
                'name' => $request->input('certified_correct', null),
                'position' => $request->input('position', null),
            ];

            $pdf = PDF::loadView('gsis_remittance.gsis_remittance_print', compact(
                'gsis_remittances',
                'gsis_loans',
                'gsis_loan_headers',
                'gsis_loan_totals',
                'signatories',
                'companies',
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('tabloid', 'landscape');
            
            $pdf_content = $pdf->output();
            $filename = 'gsis_remittance_' . $request->payroll_period_id . '_' . $request->department_id . '_' . date('Y-m-d') . '.pdf';

            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate GSIS remittance PDF: ' . $e->getMessage());
        }
    }
}
