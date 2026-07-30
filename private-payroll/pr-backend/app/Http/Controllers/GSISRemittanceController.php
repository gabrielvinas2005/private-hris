<?php

namespace App\Http\Controllers;

use PDF;
use App\Http\Controllers\Controller;
use App\Support\ReportDivisionFilter;
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
            $divisions = ReportDivisionFilter::activeDivisions();

            return $this->successResponse([
                'payroll_intervals' => $payroll_intervals,
                'divisions' => $divisions,
                'departments' => $divisions,
            ], 'GSIS remittance form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load GSIS remittance form data: ' . $e->getMessage());
        }
    }

    public function periods(Request $request)
    {
        try {
            $validator = validator(
                $request->all(),
                [
                    'payroll_interval_id' => 'required|integer',
                    'division_id' => 'required_without:department_id',
                    'department_id' => 'required_without:division_id',
                ],
                [],
                [
                    'payroll_interval_id' => 'Payroll Interval',
                    'division_id' => 'Division',
                    'department_id' => 'Division',
                ]
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse(
                    $validator->errors(),
                    'Please select Payroll Interval and Division to load payroll periods.'
                );
            }

            $divisionId = ReportDivisionFilter::resolveId($request);
            if (!$divisionId) {
                return $this->validationErrorResponse(
                    ['division_id' => ['Please select a Division.']],
                    'Please select a Division to load payroll periods.'
                );
            }

            $periods = DB::table('payroll_periods as pp')
                ->join('payroll_cutoffs as pc', 'pp.payroll_cutoff_id', '=', 'pc.id')
                ->join('payroll_summaries as ps', 'pp.id', '=', 'ps.payroll_period_id')
                ->join('employees as e', 'ps.employee_id', '=', 'e.id')
                ->where('pp.payroll_interval_id', $request->payroll_interval_id)
                ->where('e.division_id', $divisionId)
                ->where('e.is_employee', true)
                ->where('e.active', true)
                ->select('pp.id', 'pp.release_date', 'pc.name as cutoff_name')
                ->distinct()
                ->orderBy('pp.release_date', 'desc')
                ->get();

            return $this->successResponse($periods, 'GSIS periods loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load GSIS periods: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = validator(
                $request->all(),
                [
                    'payroll_interval_id' => 'required',
                    'payroll_period_id' => 'required',
                    'division_id' => 'required_without:department_id',
                    'department_id' => 'required_without:division_id',
                ],
                [],
                [
                    'payroll_interval_id' => 'Payroll Interval',
                    'payroll_period_id' => 'Payroll Period',
                    'division_id' => 'Division',
                    'department_id' => 'Division',
                ]
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse(
                    $validator->errors(),
                    'Please complete all required fields before generating the report.'
                );
            }

            $divisionId = ReportDivisionFilter::resolveId($request);
            if (!$divisionId) {
                return $this->validationErrorResponse(
                    ['division_id' => ['Please select a Division.']],
                    'Please select a Division before generating the report.'
                );
            }

            $app_key = env("APP_KEY", "");

            $companies = DB::table('companies')->get();

            // UI groups 1st/2nd half under one month but sends one period id — include both halves.
            $selectedPeriod = DB::table('payroll_periods')
                ->where('id', $request->payroll_period_id)
                ->first();

            if (!$selectedPeriod) {
                return $this->errorResponse(
                    'The selected payroll period was not found. Choose another period and try again.',
                    400
                );
            }

            $monthPeriodIds = DB::table('payroll_periods')
                ->where('payroll_interval_id', $selectedPeriod->payroll_interval_id)
                ->whereYear('release_date', date('Y', strtotime($selectedPeriod->release_date)))
                ->whereMonth('release_date', date('n', strtotime($selectedPeriod->release_date)))
                ->pluck('id')
                ->all();

            if (empty($monthPeriodIds)) {
                $monthPeriodIds = [(int) $request->payroll_period_id];
            }

            $gsis_remittances = DB::table('payroll_summaries')
                ->join('employees', 'payroll_summaries.employee_id', '=', 'employees.id')
                ->join('divisions', 'employees.division_id', '=', 'divisions.id')
                ->join('payroll_periods', 'payroll_summaries.payroll_period_id', '=', 'payroll_periods.id')
                ->join('payroll_intervals', 'payroll_periods.payroll_interval_id', '=', 'payroll_intervals.id')
                ->leftJoin('name_prefixes', 'employees.name_prefix_id', '=', 'name_prefixes.id')
                ->leftJoin('name_suffixes', 'employees.name_suffix_id', '=', 'name_suffixes.id')
                ->whereIn('payroll_summaries.payroll_period_id', $monthPeriodIds)
                ->where('employees.division_id', $divisionId)
                ->where('employees.is_employee', true)
                ->where('employees.active', true)
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
                    'divisions.code as department_code',
                    'payroll_periods.release_date',
                    'payroll_summaries.gsis'
                )
                ->orderBy('employees.id', 'asc')
                ->get();

            if ($gsis_remittances->isEmpty()) {
                return $this->errorResponse(
                    'No GSIS remittance data found for the selected Division and payroll period. '
                    . 'Process payroll for that month and ensure employees have GSIS amounts in payroll summary.',
                    400
                );
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
                ->where('a.is_gsis', true)
                ->where('a.active', true)
                ->whereIn('b.payroll_period_id', $monthPeriodIds)
                ->where('d.division_id', $divisionId)
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
                ->where('a.is_gsis', true)
                ->where('a.active', true)
                ->whereIn('b.payroll_period_id', $monthPeriodIds)
                ->where('d.division_id', $divisionId)
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
                ->where('a.is_gsis', true)
                ->where('a.active', true)
                ->whereIn('b.payroll_period_id', $monthPeriodIds)
                ->where('d.division_id', $divisionId)
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
            $filename = 'gsis_remittance_' . $request->payroll_period_id . '_' . $divisionId . '_' . date('Y-m-d') . '.pdf';

            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate GSIS remittance PDF: ' . $e->getMessage());
        }
    }
}
