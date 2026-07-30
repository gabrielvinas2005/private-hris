<?php

namespace App\Http\Controllers;

use PDF;
use Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Support\ReportDivisionFilter;
use App\Traits\ApiResponse;

class BankRemittanceController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $divisions = ReportDivisionFilter::activeDivisions();

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
                'divisions' => $divisions,
                'departments' => $divisions,
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
            // Log that we've reached the controller (for debugging)
            Log::info('BankRemittanceController@print called', [
                'request_data' => $request->all()
            ]);

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
            
            // First, verify the deduction exists and is marked as bank
            $deduction = DB::table('deductions')
                ->where('id', $request->deduction)
                ->first();
            
            if (!$deduction) {
                return $this->errorResponse('Selected deduction not found', 400);
            }
            
            if (!$deduction->is_bank) {
                return $this->errorResponse('Selected deduction is not marked as a bank deduction. Please check the deduction settings.', 400);
            }

            // The UI groups payroll periods by month (1st/2nd half tags) but sends a single period id.
            // Expand that id to include all periods in the same interval + month so deductions encoded
            // under either half can be included in this remittance report.
            $selectedPeriod = DB::table('payroll_periods')->where('id', $request->payroll_period)->first();
            if (!$selectedPeriod) {
                return $this->errorResponse('Selected payroll period not found', 400);
            }

            $monthPeriodIds = DB::table('payroll_periods')
                ->where('payroll_interval_id', $selectedPeriod->payroll_interval_id)
                ->whereYear('release_date', date('Y', strtotime($selectedPeriod->release_date)))
                ->whereMonth('release_date', date('n', strtotime($selectedPeriod->release_date)))
                ->where('active', true)
                ->pluck('id')
                ->toArray();

            if (empty($monthPeriodIds)) {
                $monthPeriodIds = [(int) $request->payroll_period];
            }
            
            // Check if any payroll_deductions exist for this period (for debugging)
            $total_records = DB::table('payroll_deductions as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('deductions as d', 'a.deduction_id', '=', 'd.id')
                ->whereIn('a.payroll_period_id', $monthPeriodIds)
                ->where('a.deduction_id', $request->deduction)
                ->where('d.is_bank', true)
                ->count();
            
            // Check if any records exist for this department
            $department_records = DB::table('payroll_deductions as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('deductions as d', 'a.deduction_id', '=', 'd.id')
                ->whereIn('a.payroll_period_id', $monthPeriodIds)
                ->where('b.division_id', $request->department)
                ->where('a.deduction_id', $request->deduction)
                ->where('d.is_bank', true)
                ->count();
            
            // Check if records exist with amount > 0
            $amount_records = DB::table('payroll_deductions as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('deductions as d', 'a.deduction_id', '=', 'd.id')
                ->whereIn('a.payroll_period_id', $monthPeriodIds)
                ->where('b.division_id', $request->department)
                ->where('a.deduction_id', $request->deduction)
                ->where('d.is_bank', true)
                ->where('a.amount', '>', 0)
                ->count();
            
            // Get list of departments that actually have data for this period and deduction
            $departments_with_data = DB::table('payroll_deductions as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('divisions as c', 'b.division_id', '=', 'c.id')
                ->join('deductions as d', 'a.deduction_id', '=', 'd.id')
                ->whereIn('a.payroll_period_id', $monthPeriodIds)
                ->where('a.deduction_id', $request->deduction)
                ->where('d.is_bank', true)
                ->where('a.amount', '>', 0)
                ->select('c.id', 'c.name')
                ->distinct()
                ->get();
            
            Log::info('BankRemittanceController@print - Data check', [
                'payroll_period_id' => $request->payroll_period,
                'month_period_ids' => $monthPeriodIds,
                'department_id' => $request->department,
                'deduction_id' => $request->deduction,
                'total_records_for_period' => $total_records,
                'department_records' => $department_records,
                'records_with_amount' => $amount_records,
                'departments_with_data' => $departments_with_data->pluck('name')->toArray()
            ]);
            
            $bank = DB::table('payroll_deductions as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('divisions as c', 'b.division_id', '=', 'c.id')
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
                ->whereIn('a.payroll_period_id', $monthPeriodIds) // Filter by month payroll periods
                ->where('b.division_id', $request->department) // Filter by division (legacy param name: department)
                ->where('a.deduction_id', $request->deduction) // Filter by deduction
                ->orderBy('full_name', 'asc')
                ->get();

            // Provide more detailed error messages
            if ($bank->isEmpty()) {
                $errorMessage = 'No bank remittance data found for the specified criteria. ';
                
                if ($total_records == 0) {
                    $errorMessage .= 'No payroll deductions have been set up for this payroll period and deduction. ';
                    $errorMessage .= 'Please go to "Income/Deduction Adjustment" to set up bank loan deductions for this payroll period.';
                } else if ($department_records == 0) {
                    $errorMessage .= 'No employees found in the selected department with this deduction for this payroll period.';
                    
                    // Suggest departments that have data
                    if ($departments_with_data->isNotEmpty()) {
                        $department_names = $departments_with_data->pluck('name')->toArray();
                        $errorMessage .= ' However, data exists for the following departments: ' . implode(', ', $department_names) . '. ';
                        $errorMessage .= 'Please select the correct department or ensure employees are assigned to the selected department.';
                    }
                } else if ($amount_records == 0) {
                    $errorMessage .= 'Deduction amounts are set to zero. Please set deduction amounts greater than zero.';
                } else {
                    $errorMessage .= 'Please verify that employees have bank loan deductions set up for this payroll period.';
                }
                
                Log::warning('BankRemittanceController@print - No data found', [
                    'error_message' => $errorMessage,
                    'total_records' => $total_records,
                    'department_records' => $department_records,
                    'amount_records' => $amount_records,
                    'departments_with_data' => $departments_with_data->pluck('name')->toArray()
                ]);
                
                return $this->errorResponse($errorMessage, 400);
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
            // Log the exception for debugging
            Log::error('BankRemittanceController@print error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse('Failed to generate bank remittance PDF: ' . $e->getMessage());
        }
    }
}
