<?php

namespace App\Http\Controllers;

use PDF;
use Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;

class BankRemittanceController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $departments = DB::table('departments')
                ->select('id', 'name')
                ->get();

            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name"),
                    'a.release_date'
                )
                ->orderBy('a.release_date', 'asc')
                ->get();

            $deductions = DB::table('deductions')
                ->select('id', 'name')
                ->where('is_bank', true)
                ->get();

            $signatory = array(
                'signatory' => '',
                'position' =>  '',
                'date' => '',
            );

            return $this->successResponse([
                'departments' => $departments,
                'signatory' => $signatory,
                'pay_periods' => $pay_periods,
                'deductions' => $deductions
            ], 'Bank remittance report data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve bank remittance report data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            // Validate user input for signatory
            $validator = Validator::make($request->all(), [
                'signatory' => 'required|string|max:255',
                'position' => 'required|string|max:255',
                'date' => 'required|date',
                'department' => 'required',
                'payroll_period' => 'required',
                'deduction' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $companies = DB::table('companies')->get();
            $bank = DB::table('payroll_deductions as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('departments as c', 'b.department_id', '=', 'c.id')
                ->join('deductions as d', 'a.deduction_id', '=', 'd.id')
                ->join("payroll_periods as pp", "a.payroll_period_id", "=", "pp.id")
                ->leftJoin('name_suffixes as e', 'b.name_suffix_id', '=', 'e.id')
                ->select(
                    'a.payroll_period_id',
                    'a.amount',
                    DB::raw("FORMAT(pp.release_date, 'MMMM yyyy') as month_year"),
                    'a.id',
                    'pp.release_date',
                    DB::raw("CONCAT(
                        CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END,
                        ', ',
                        CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END,
                        ' ',
                        CASE
                            WHEN ISNULL(b.is_encrypted, 0) = 0 THEN LEFT(b.middle_name, 1)
                            ELSE LEFT(dbo.ufn_DecryptString(b.middle_name, '$app_key'), 1)
                        END,
                        CASE WHEN b.middle_name IS NOT NULL AND b.middle_name <> '' THEN '. ' ELSE ' ' END,
                        CASE WHEN e.name IS NOT NULL THEN e.name ELSE '' END
                    ) as full_name"),
                    'b.employee_no',
                    'c.name as department',
                    'd.name as deduction',

                )
                ->distinct()
                ->where('d.is_bank', true)
                ->where('a.amount', '>', 0)
                ->where('a.payroll_period_id', $request->payroll_period) // Filter by payroll period
                ->where('b.department_id', $request->department) // Filter by department
                ->where('a.deduction_id', $request->deduction) // Filter by deduction
                ->orderBy('full_name', 'asc')
                ->get();

            if ($bank->isEmpty()) {
                return $this->notFoundResponse('No bank remittance data found for the specified criteria');
            }

            $signatory = [
                'signatory' => $request->signatory,
                'position' => $request->position,
                'date' => $request->date,
            ];

            // Generate PDF
            $pdf = PDF::loadView('bank_remittance.bank_remittance_print', compact(
                'bank',
                'image',
                'signatory',
                'companies'
            ))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'portrait');

            // Generate PDF content as base64 for API response
            $pdfContent = $pdf->output();
            $base64Content = base64_encode($pdfContent);

            // Calculate total amount
            $totalAmount = $bank->sum('amount');
            $employeeCount = $bank->count();

            $filename = 'bank_remittance_' . $request->department . '_' . $request->payroll_period . '_' . $request->deduction . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate bank remittance PDF: ' . $e->getMessage());
        }
    }
}
