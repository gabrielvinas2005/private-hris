<?php

namespace App\Http\Controllers;

use PDF;
use App\Support\PayrollBenefitsEmployeeScope;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PagIbigContributionController extends Controller
{
    use ApiResponse;

    public function report()
    {
        try {
            $app_key = env("APP_KEY", "");

            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name"),
                    'a.release_date',
                    'c.name as cutoff_name'
                )
                ->orderBy('a.release_date', 'asc')
                ->get();

            $employee_options = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->select(
                    'e.id',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(RTRIM(e.last_name), ', ', RTRIM(e.first_name), ' ', LEFT(RTRIM(e.middle_name), 1))
                        ELSE
                            CONCAT(
                                RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), ', ',
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), ' ',
                                LEFT(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), 1)
                            )
                        END as name"),
                    'p.name as position'
                )
                ->where([
                    'e.active' => true,
                    'e.is_employee' => true,
                ]);
            PayrollBenefitsEmployeeScope::apply($employee_options, 'e');
            $employee_options = $employee_options->orderBy('name', 'asc')->get();

            $signatory = [
                'signatory' => '',
                'position' => 'Municipal Accountant',
                'date' => date('Y-m-d'),
            ];

            return $this->successResponse([
                'pay_periods' => $pay_periods,
                'signatory' => $signatory,
                'employee_options' => $employee_options,
            ], 'Pag-IBIG contribution data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve Pag-IBIG contribution data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $validator = Validator::make($request->all(), [
                'signatory' => 'required|string|max:255',
                'position' => 'required|string|max:255',
                'payroll_period' => 'required',
                'payroll_period_2' => 'nullable',
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $companies = DB::table('companies')->get();

            $signatory = $request->signatory;
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
                        CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN b.last_name
                        ELSE dbo.ufn_DecryptString(b.last_name, '$app_key') END as last_name
                    "),
                    DB::raw("
                        CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN b.first_name
                        ELSE dbo.ufn_DecryptString(b.first_name, '$app_key') END as first_name
                    "),
                    DB::raw("
                        CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN b.middle_name
                        ELSE dbo.ufn_DecryptString(b.middle_name, '$app_key') END as middle_name
                    "),
                    'e.name as suffix',
                    'a.pagibig',
                    'a.salary'
                )
                ->where('a.pagibig', '>', 0)
                ->whereIn('a.payroll_period_id', array_values(array_filter([
                    $request->payroll_period,
                    $request->payroll_period_2
                ])))
                ->orderBy('b.last_name')
                ->get();

            if ($dtl->isEmpty()) {
                return $this->errorResponse('No Pag-IBIG contribution data found.');
            }

            $dtl = $dtl->map(function ($row) {
                $row->name = trim(($row->first_name ?? '') . ' ' . ($row->middle_name ?? '') . ' ' . ($row->last_name ?? '') . ' ' . ($row->suffix ?? ''));
                return $row;
            });

            $pdf = PDF::loadView('pagibigcontribution.pagibig_contribution_print', compact('companies','dtl', 'signatory', 'position', 'date'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'pagibig_contribution_report_' . $request->payroll_period . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate Pag-IBIG contribution report: ' . $e->getMessage());
        }
    }
}


