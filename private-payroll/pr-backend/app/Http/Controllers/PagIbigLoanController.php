<?php

namespace App\Http\Controllers;

use PDF;
use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class PagIbigLoanController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.payroll_cutoff_id',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name"),
                    'a.release_date',
                    'c.name as cutoff_name'
                )
                ->orderBy('a.release_date', 'desc')
                ->orderBy('a.id', 'desc')
                ->get()
                ->unique(function ($row) {
                    $month = $row->release_date
                        ? date('Y-m', strtotime($row->release_date))
                        : 'unknown';
                    return $month . '|' . (int) $row->payroll_cutoff_id;
                })
                ->values();

            $membership_programs = DB::table('deductions')
                ->select('id', 'name')
                ->where('name', 'LIKE', '%M2%')
                ->get();

            $membership_programs->prepend((object) ['id' => 'mandatory', 'name' => 'Mandatory']);

            $signatory = array(
                'signatory' => '',
                'position' => '',
            );

            return $this->successResponse([
                'pay_periods' => $pay_periods,
                'membership_programs' => $membership_programs
            ], 'Pagibig loan data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve Pagibig loan data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $validator = Validator::make($request->all(), [
                'signatory' => 'required|string|max:255',
                'accsignatory' => 'required|string|max:255',
                'position' => 'required|string|max:255',
                'payroll_period' => 'required',
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $companies = DB::table('companies')->get();

            // RETRIEVE VALUES from request
            $signatory = $request->signatory;
            $accsignatory = $request->accsignatory;
            $position = $request->position;
            $date = $request->date;

            $dtl = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('payroll_periods as c', 'a.payroll_period_id', '=', 'c.id')
                ->leftJoin('name_suffixes as e', 'b.name_suffix_id', '=', 'e.id')
                ->select(
                    'a.payroll_period_id',
                    DB::raw("FORMAT(c.release_date, 'MMMM yyyy') as month_year"),
                    'a.id',
                    'b.pagibig_no',
                    'c.release_date',
                    'b.employee_no',
                    DB::raw("
                        CASE
                            WHEN ISNULL(b.is_encrypted, 0) = 0 THEN b.last_name
                            ELSE dbo.ufn_DecryptString(b.last_name, '$app_key')
                        END as last_name
                    "),
                    DB::raw("
                        CASE
                            WHEN ISNULL(b.is_encrypted, 0) = 0 THEN b.last_name
                            ELSE dbo.ufn_DecryptString(b.first_name, '$app_key')
                        END as first_name
                    "),
                    DB::raw("
                        CASE
                            WHEN ISNULL(b.is_encrypted, 0) = 0 THEN b.last_name
                            ELSE dbo.ufn_DecryptString(b.middle_name, '$app_key')
                        END as middle_name
                    "),
                    'e.name as suffix',
                    'a.pagibig',
                    'a.philhealth as philhealth_ps',
                    'a.salary',
                )
                ->distinct()
                ->where('a.pagibig', '>', 0)
                ->where('a.payroll_period_id', $request->payroll_period)
                ->get();

            if ($dtl->isEmpty()) {
                return $this->errorResponse('No data found.');
            }

            $pdf = PDF::loadView('pagibigloan.pagibig_loan_print', compact('companies','dtl', 'signatory', 'accsignatory', 'position', 'date'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'pagibig_loan_report_' . $request->payroll_period . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate Pagibig loan report: ' . $e->getMessage());
        }
    }

    public function printMP2(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $validator = Validator::make($request->all(), [
                'signatory' => 'required|string|max:255',
                'accsignatory' => 'required|string|max:255',
                'position' => 'required|string|max:255',
                'payroll_period' => 'required',
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $companies = DB::table('companies')->get();

            $signatory = $request->signatory;
            $accsignatory = $request->accsignatory;
            $position = $request->position;
            $date = $request->date;

            // Get MP2 deduction ID from deductions table
            $mp2DeductionId = DB::table('deductions')->where('name', 'LIKE', '%M2%')->value('id');

            // Get employees with MP2 deductions
            $dtl = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('payroll_periods as c', 'a.payroll_period_id', '=', 'c.id')
                ->join('payroll_deductions as pd', function ($join) use ($mp2DeductionId) {
                    $join->on('a.payroll_period_id', '=', 'pd.payroll_period_id')
                        ->on('a.employee_id', '=', 'pd.employee_id')
                        ->where('pd.deduction_id', '=', $mp2DeductionId);
                })
                ->leftJoin('name_suffixes as e', 'b.name_suffix_id', '=', 'e.id')
                ->select(
                    'a.payroll_period_id',
                    DB::raw("FORMAT(c.release_date, 'MMMM yyyy') as month_year"),
                    'a.id',
                    'b.pagibig_no',
                    'c.release_date',
                    'b.employee_no',
                    DB::raw("
                    CASE
                        WHEN ISNULL(b.is_encrypted, 0) = 0 THEN b.last_name
                        ELSE dbo.ufn_DecryptString(b.last_name, '$app_key')
                    END as last_name
                "),
                    DB::raw("
                    CASE
                        WHEN ISNULL(b.is_encrypted, 0) = 0 THEN b.first_name
                        ELSE dbo.ufn_DecryptString(b.first_name, '$app_key')
                    END as first_name
                "),
                    DB::raw("
                    CASE
                        WHEN ISNULL(b.is_encrypted, 0) = 0 THEN b.middle_name
                        ELSE dbo.ufn_DecryptString(b.middle_name, '$app_key')
                    END as middle_name
                "),
                    'e.name as suffix',
                    'a.pagibig',
                    'a.salary',
                    'pd.amount as mp2_amount' // Get the MP2 deduction amount
                )
                ->where('a.payroll_period_id', $request->payroll_period)
                ->whereNotNull('pd.amount') // Ensure MP2 amount is not null
                ->where('pd.amount', '>', 0) // Ensure MP2 amount is greater than 0
                ->get();

            if ($dtl->isEmpty()) {
                return $this->errorResponse('No MP2 data found.');
            }

            $pdf = PDF::loadView('pagibigloan.mp2_loan_print', compact('companies','dtl', 'signatory', 'accsignatory', 'position', 'date'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'pagibig_mp2_report_' . $request->payroll_period . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate Pagibig MP2 report: ' . $e->getMessage());
        }
    }

    public function loan()
    {
        try {
            $app_key = env("APP_KEY", "");

            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.payroll_cutoff_id',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name"),
                    'a.release_date',
                    'c.name as cutoff_name'
                )
                ->orderBy('a.release_date', 'desc')
                ->orderBy('a.id', 'desc')
                ->get()
                ->unique(function ($row) {
                    $month = $row->release_date
                        ? date('Y-m', strtotime($row->release_date))
                        : 'unknown';
                    return $month . '|' . (int) $row->payroll_cutoff_id;
                })
                ->values();

            $membership_programs = DB::table('deductions')
                ->select('id', 'name')
                ->where('is_pagibig', true)
                ->where('active', true)
                ->get();

            $signatory = array(
                'signatory' => '',
                'position' => '',
            );

            return $this->successResponse([
                'pay_periods' => $pay_periods,
                'membership_programs' => $membership_programs
            ], 'Pagibig loan form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load Pagibig loan form data: ' . $e->getMessage());
        }
    }

    public function loanprint(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $validator = Validator::make(
                $request->all(),
                [
                    'signatory' => 'required|string|max:255',
                    'accsignatory' => 'required|string|max:255',
                    'position' => 'required|string|max:255',
                    'payroll_period' => 'required',
                    'date' => 'required|date',
                    'membership_program' => 'required',
                ],
                [],
                [
                    'signatory' => 'Authorized Representative',
                    'accsignatory' => 'OIC-Municipal Accountant',
                    'position' => 'Position/Designation',
                    'payroll_period' => 'Payroll Period',
                    'date' => 'Date',
                    'membership_program' => 'Loan Type',
                ]
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse(
                    $validator->errors(),
                    'Please complete all required report fields.'
                );
            }

            $companies = DB::table('companies')->get();
            $signatory = $request->signatory;
            $accsignatory = $request->accsignatory;
            $position = $request->position;
            $date = $request->date;
            $deductionId = $request->membership_program;

            $loanType = DB::table('deductions')
                ->where('id', $deductionId)
                ->value('name');

            if (!$loanType) {
                return $this->errorResponse('The selected loan type was not found.', 400);
            }

            // UI groups 1st/2nd half under one month but sends one period id — include both halves.
            $selectedPeriod = DB::table('payroll_periods')
                ->where('id', $request->payroll_period)
                ->first();

            if (!$selectedPeriod) {
                return $this->errorResponse('The selected payroll period was not found.', 400);
            }

            $monthPeriodIds = DB::table('payroll_periods')
                ->where('payroll_interval_id', $selectedPeriod->payroll_interval_id)
                ->whereYear('release_date', date('Y', strtotime($selectedPeriod->release_date)))
                ->whereMonth('release_date', date('n', strtotime($selectedPeriod->release_date)))
                ->pluck('id')
                ->all();

            if (empty($monthPeriodIds)) {
                $monthPeriodIds = [(int) $request->payroll_period];
            }

            // Get employees with selected deduction type
            $dtl = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('payroll_periods as c', 'a.payroll_period_id', '=', 'c.id')
                ->join('payroll_deductions as pd', function ($join) use ($deductionId) {
                    $join->on('a.payroll_period_id', '=', 'pd.payroll_period_id')
                        ->on('a.employee_id', '=', 'pd.employee_id')
                        ->where('pd.deduction_id', '=', $deductionId);
                })
                ->leftJoin('name_suffixes as e', 'b.name_suffix_id', '=', 'e.id')
                ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
                ->select(
                    'a.payroll_period_id',
                    DB::raw("FORMAT(c.release_date, 'MMMM yyyy') as month_year"),
                    'a.id',
                    'b.pagibig_no',
                    'c.release_date',
                    'b.employee_no',
                    DB::raw("
                    CASE
                        WHEN ISNULL(b.is_encrypted, 0) = 0 THEN
                            CONCAT(RTRIM(b.last_name), ', ', RTRIM(b.first_name), ' ', RTRIM(ISNULL(SUBSTRING(b.middle_name, 1, 1), '')), '. ', ISNULL(e.name, ''))
                        ELSE
                            CONCAT(RTRIM(dbo.ufn_DecryptString(b.last_name, '$app_key')), ', ', RTRIM(dbo.ufn_DecryptString(b.first_name, '$app_key')), ' ', RTRIM(ISNULL(SUBSTRING(dbo.ufn_DecryptString(b.middle_name, '$app_key'), 1, 1), '')), '. ', ISNULL(e.name, ''))
                    END as name
                "),
                    'pos.name as position',
                    'a.pagibig',
                    'a.salary',
                    'pd.amount as deduction_amount'
                )
                ->whereIn('a.payroll_period_id', $monthPeriodIds)
                ->whereNotNull('pd.amount')
                ->where('pd.amount', '>', 0)
                ->get();

            if ($dtl->isEmpty()) {
                return $this->errorResponse(
                    'No Pag-IBIG loan deductions found for the selected payroll period and loan type. '
                    . 'Encode deductions in Income/Deduction Adjustment and ensure payroll has been processed for that month.',
                    400
                );
            }

            $pdf = PDF::loadView('pagibigloan.loan_print', compact('companies','dtl', 'signatory', 'accsignatory', 'position', 'date', 'loanType'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'pagibig_loan_deduction_report_' . $request->payroll_period . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate Pagibig loan deduction report: ' . $e->getMessage());
        }
    }
}