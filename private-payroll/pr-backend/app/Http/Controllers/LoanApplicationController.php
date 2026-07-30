<?php

namespace App\Http\Controllers;

use App\Audit;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LoanApplicationController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $loans = DB::table('loan_applications as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('deductions as c', 'c.id', '=', 'a.deduction_id')
                ->select(
                    'a.*',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.first_name,' ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                                END as name"),
                    DB::raw("CASE WHEN a.is_approve = 'true' then 'Yes' else 'No' end as approve"),
                    'c.name as loan',
                )
                ->where(db::raw("isnull(a.active,1)"), 1)
                ->orderBy('a.effectivity_date', 'desc')
                ->get();

            $gsis = DB::table('loan_applications as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('deductions as c', 'c.id', '=', 'deduction_id')
                ->select(
                    'c.id',
                    'c.name'
                )
                ->where(db::raw("isnull(c.is_gsis,0)"), 1)
                ->distinct()
                ->get();

            $pagibig = DB::table('loan_applications as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('deductions as c', 'c.id', '=', 'deduction_id')
                ->select(
                    'c.id',
                    'c.name'
                )
                ->where(db::raw("isnull(c.is_pagibig,0)"), 1)
                ->distinct()
                ->get();
            $other = DB::table('loan_applications as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('deductions as c', 'c.id', '=', 'deduction_id')
                ->select(
                    'c.id',
                    'c.name'
                )
                ->where(db::raw("isnull(c.is_gsis,0)"), 0)
                ->where(db::raw("isnull(c.is_pagibig,0)"), 0)
                ->distinct()
                ->get();

            return $this->successResponse([
                'loans' => $loans,
                'gsis' => $gsis,
                'pagibig' => $pagibig,
                'other' => $other
            ], 'Loan application list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve loan applications: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $loan_app = DB::table('loan_applications')->where('id', $id)->get();

            if ($loan_app->isEmpty()) {
                $dummy_loan_app = array(
                    'id' => 0,
                    'deduction_id' => 0,
                    'employee_id' => 0,
                    'loan_amount' => null,
                    'interest_rate' => null,
                    'term' => null,
                    'loan_amortization' => null,
                    'remarks' => null,
                    'effectivity_date' => null,
                    'end_date' => null,
                    'is_approve' => false,
                    'is_disapprove' => false,
                    'voucher_number' => ''
                );

                $loan_app = (object)$dummy_loan_app;
                $loan_app = collect([$loan_app]);
            }

            $employees = DB::table('employees')
                ->select(
                    'id',
                     DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                END as name")
                )
                ->where(['active' => true, 'is_employee' => true])
                ->orderBy('employees.first_name', 'asc')
                ->get();

            $deductions = DB::table('deductions')->where('active', true)->orderBy('name', 'asc')->get();

            return $this->successResponse([
                'employees' => $employees,
                'deductions' => $deductions,
                'loan_app' => $loan_app
            ], 'Loan application form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load loan application form: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $request->validate([
                'deduction_id' => 'required|numeric',
                'employee_id' => 'required|numeric',
                'loan_amount' => 'required|numeric|min:1',
                'interest_rate' => 'required|numeric|min:0|max:100',
                'term' => 'required|integer|min:1|max:120',
                'loan_amortization' => 'required|numeric|min:0',
                'effectivity_date' => 'required|date',
                'end_date' => 'required|date|after:effectivity_date',
                'voucher_number' => 'required|string'
            ]);

        // validate if same loan is applied with balance
        if ($id == 0) {
            $loans = DB::table('loan_applications')
                ->where([
                    'employee_id' => $request->employee_id,
                    'deduction_id' => $request->deduction_id
                ])
                ->where('balance', '>', 0)
                ->count();

            if ($loans > 0) {
                return $this->errorResponse('Invalid loan application! Same loan is already applied with existing balance.');
            }
            $loans_voucher = DB::table('loan_applications')
                ->where([
                    'voucher_number' => $request->voucher_number
                ])
                ->count();

            if ($loans_voucher > 0) {
                return $this->errorResponse('Invalid loan application! Same voucher number is already applied.');
            }
        }

        $employee_salary = DB::table('employees')
                ->select(
                'id',
                DB::raw("isnull(salary,0) - 5000 as salary"),
                DB::raw("isnull(gsis_amount,0) as gsis_amount"),
                DB::raw("isnull(philhealth_amount,0) as philhealth_amount"),
                DB::raw("isnull(pagibig_amount,0) as pagibig_amount"),
                DB::raw("isnull(tax_amount,0) as tax_amount")
                )
                ->where([
                    'id' => $request->employee_id
                ])
                ->get();

        if ($employee_salary->isEmpty()) {
            return $this->errorResponse('Employee not found.');
        }

        $loans_total = DB::table('loan_applications')
                ->select(
                'employee_id',
                DB::raw("SUM(isnull(loan_amortization,0)) as total_loans")
                )
                ->where([
                    'employee_id' => $request->employee_id
                ])
                ->where('balance', '>', 0)
                ->where(db::raw("isnull(active,1)"), true)
                ->groupBy(
                    'employee_id'
                )
                ->get();
        $totalloans = $loans_total->isEmpty() ? 0 : ($loans_total[0]->total_loans ?? 0);

        $pending_deductions_absent = DB::table('pending_deductions as a')
                            ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                            ->select(
                                DB::raw("(SUM(isnull(a.amount,0)) - SUM(isnull(amount_paid,0))) as pending_deduction")
                            )
                            ->where([
                                'employee_id' => $request->employee_id,
                                'deduction_id' => 102
                            ])
                            ->where(DB::raw('isnull(a.is_absent,0)'), '=', 1)
                            ->get();

        $pending_absent = $pending_deductions_absent->isEmpty() 
            ? 0 
            : ($pending_deductions_absent[0]->pending_deduction ?? 0);

        $pending_deductions_late = DB::table('pending_deductions as a')
                            ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                            ->select(
                                DB::raw("(SUM(isnull(a.amount,0)) - SUM(isnull(amount_paid,0))) as pending_deduction")
                            )
                            ->where([
                                'employee_id' => $request->employee_id,
                                'deduction_id' => 102
                            ])
                            ->where(DB::raw('isnull(a.is_late,0)'), '=', 1)
                            ->get();

        $pending_late = $pending_deductions_late->isEmpty() 
            ? 0 
            : ($pending_deductions_late[0]->pending_deduction ?? 0);

        $pending_deductions_undertime = DB::table('pending_deductions as a')
                            ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                            ->select(
                                DB::raw("(SUM(isnull(a.amount,0)) - SUM(isnull(amount_paid,0))) as pending_deduction")
                            )
                            ->where([
                                'employee_id' => $request->employee_id,
                                'deduction_id' => 102
                            ])
                            ->where(DB::raw('isnull(a.is_undertime,0)'), '=', 1)
                            ->get();

        $pending_undertime = $pending_deductions_undertime->isEmpty() 
            ? 0 
            : ($pending_deductions_undertime[0]->pending_deduction ?? 0);

        $total_pending_deductions = $pending_absent + $pending_late + $pending_undertime;

        $total_deductions = $totalloans 
            + ($employee_salary[0]->gsis_amount ?? 0) 
            + ($employee_salary[0]->philhealth_amount ?? 0) 
            + ($employee_salary[0]->pagibig_amount ?? 0) 
            + ($employee_salary[0]->tax_amount ?? 0) 
            + $total_pending_deductions 
            + $request->loan_amortization;

        // $employee_salary_amount = $employee_salary[0]->salary ?? 0;
        // if ($total_deductions > $employee_salary_amount) {
        //     return response()->json([
        //         'error' => 'The Total Deduction for the employee is exceeding the Monthly Salary of the Employee. Please check!'
        //     ], 500);
        // }

        $data = [
            'deduction_id' => $request->deduction_id,
            'employee_id' => $request->employee_id,
            'loan_amount' => $request->loan_amount,
            'interest_rate' => $request->interest_rate,
            'term' => $request->term,
            'loan_amortization' => $request->loan_amortization,
            'remarks' => $request->remarks,
            'effectivity_date' => $request->effectivity_date,
            'end_date' => $request->end_date,
            'is_approve' => true,
            'is_disapprove' => false,
            'balance' => $request->loan_amount,
            'voucher_number' => $request->voucher_number,
            'active' => true
        ];

        if ($id == null || $id == 0) {
            $max_id = DB::table('loan_applications')->max('id');
            $id = ($max_id ? $max_id : 0) + 1;
        }

        DB::unprepared('SET IDENTITY_INSERT loan_applications ON');
        DB::table('loan_applications')->updateOrInsert(['id' => $id], $data);
        DB::unprepared('SET IDENTITY_INSERT loan_applications OFF');

        // Save audit trail
        try {
            $data_audit = [
                'user_id' => Auth::user()->id ?? 1,
                'module' => 'Payroll Module',
                'menu' => 'Loan Application',
                'activity' => $id == 0 ? 'Save' : 'Update',
                'description' => 'Process loan application.',
            ];
            Audit::create($data_audit);
        } catch (\Exception $auditError) {
            Log::warning('Failed to create audit trail', ['error' => $auditError->getMessage()]);
        }

        return $this->successResponse(null, 'You have successfully processed loan application!');
        } catch (\Exception $e) {
            Log::error('Loan Application Store Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse('Failed to process loan application: ' . $e->getMessage());
        }
    }

    public function reconstruct($id)
    {
        $app_key = env("APP_KEY", "");

        $loan_app = DB::table('loan_applications')->where('id', $id)->get();

        if ($loan_app->isEmpty()) {
            $dummy_loan_app = array(
                'id' => 0,
                'deduction_id' => 0,
                'employee_id' => 0,
                'loan_amount' => null,
                'interest_rate' => null,
                'term' => null,
                'loan_amortization' => null,
                'remarks' => null,
                'effectivity_date' => null,
                'end_date' => null,
                'is_approve' => false,
                'is_disapprove' => false,
                'voucher_number' => ''
            );

            $loan_app = (object)$dummy_loan_app;
            $loan_app = collect([$loan_app]);
        }

        $employees = DB::table('employees')
            ->select(
                'id',
                DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                            END as name")
            )
            ->where(['active' => true, 'is_employee' => true])
            ->orderBy('employees.first_name', 'asc')
            ->get();

        $deductions = DB::table('deductions')->where('active', true)->orderBy('name', 'asc')->get();

        return $this->successResponse([
            'employees' => $employees,
            'deductions' => $deductions,
            'loan_app' => $loan_app
        ], 'Loan application reconstruct data loaded successfully');
    }

    public function reconstructstore(Request $request, $id)
    {
        $request->validate([
            'deduction_id' => 'required',
            'employee_id' => 'required',
            'loan_amount' => 'required|numeric|min:1',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'term' => 'required|integer|min:1|max:120',
            'loan_amortization' => 'required|numeric|min:0',
            'effectivity_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:effectivity_date',
            'voucher_number' => 'required'
        ]);
        $loans_voucher = DB::table('loan_applications')
                ->where('voucher_number', $request->voucher_number)
                ->where('id', '!=', $id)
                ->where(DB::raw("isnull(active,1)"), 1)
                ->count();

            if ($loans_voucher > 0) {
                // return back()->with('error', 'Invalid loan application! Same loan is already applied with existing balance.');
                return response()->json(['error' => 'Invalid loan application! Same voucher number is already applied.'], 500);
            }
        $employee_salary = DB::table('employees')
                ->select(
                'id',
                DB::raw("isnull(salary,0) - 5000 as salary"),
                DB::raw("isnull(gsis_amount,0) as gsis_amount"),
                DB::raw("isnull(philhealth_amount,0) as philhealth_amount"),
                DB::raw("isnull(pagibig_amount,0) as pagibig_amount"),
                DB::raw("isnull(tax_amount,0) as tax_amount")
                )
                ->where([
                    'id' => $request->employee_id
                ])
                ->get();

        $loans_total = DB::table('loan_applications')
                ->select(
                'employee_id',
                DB::raw("SUM(loan_amortization) as total_loans")
                )
                ->where([
                    'employee_id' => $request->employee_id
                ])
                ->where('balance', '>', 0)
                ->where(db::raw("isnull(active,1)"), true)
                ->groupBy(
                    'employee_id'
                )
                ->get();

        if ($loans_total->isEmpty()) {
            $totalloans = 0;
        }
        else {
            $totalloans = $loans_total[0]->total_loans;
        }

        $pending_deductions_absent = DB::table('pending_deductions as a')
                            ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                            ->select(
                                DB::raw("(SUM(isnull(a.amount,0)) - SUM(isnull(amount_paid,0))) as pending_deduction")
                            )
                            ->where([
                                'employee_id' => $request->employee_id,
                                'deduction_id' => 102
                            ])
                            ->where( DB::raw('isnull(a.is_absent,0)'), '=', 1 )
                            ->get();

        if ($pending_deductions_absent->isEmpty()) {
            $pending_absent = 0;
        }
        else {
            $pending_absent = $pending_deductions_absent[0]->pending_deduction ?? 0;
        }

        $pending_deductions_late = DB::table('pending_deductions as a')
                            ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                            ->select(
                                DB::raw("(SUM(isnull(a.amount,0)) - SUM(isnull(amount_paid,0))) as pending_deduction")
                            )
                            ->where([
                                'employee_id' => $request->employee_id,
                                'deduction_id' => 102
                            ])
                            ->where( DB::raw('isnull(a.is_late,0)'), '=', 1 )
                            ->get();

        if ($pending_deductions_late->isEmpty()) {
            $pending_late = 0;
        }
        else {
            $pending_late = $pending_deductions_late[0]->pending_deduction ?? 0;
        }

        $pending_deductions_undertime = DB::table('pending_deductions as a')
                            ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                            ->select(
                                DB::raw("(SUM(isnull(a.amount,0)) - SUM(isnull(amount_paid,0))) as pending_deduction")
                            )
                            ->where([
                                'employee_id' => $request->employee_id,
                                'deduction_id' => 102
                            ])
                            ->where( DB::raw('isnull(a.is_undertime,0)'), '=', 1 )
                            ->get();

        if ($pending_deductions_undertime->isEmpty()) {
            $pending_undertime = 0;
        }
        else {
            $pending_undertime = $pending_deductions_undertime[0]->pending_deduction ?? 0;
        }

        $total_pending_deductions = $pending_absent + $pending_late +$pending_undertime;

        $total_deductions = $totalloans + $employee_salary[0]->gsis_amount + $employee_salary[0]->philhealth_amount + $employee_salary[0]->pagibig_amount + $employee_salary[0]->tax_amount + $total_pending_deductions + $request->loan_amortization;

        if ($total_deductions > ($employee_salary[0]->salary)) {
            return response()->json(['error' => 'The Total Deduction for the employee is exceeding the Monthly Salary of the Employee. Please check!'], 500);
            // return back()->with('error', 'The Total Deduction for the employee is exceeding the Monthly Salary of the Employee. Please check!');
        }
        DB::table('loan_applications')
                    ->where([
                        'id' => $id
                    ])
                    ->update([
                        'active' => 0,
                        'reconstructed' => 1,
                        'reconstructed_date' => now(),
                        // Free the original voucher to satisfy the unique index
                        // so the new reconstructed row can keep the same voucher number.
                        'voucher_number' => DB::raw("CONCAT(voucher_number, '-R', CAST(id AS VARCHAR(20)))"),
                        'user_id' => Auth::user()->id
                    ]);

        $id = 0;
        // // validate if same loan is applied with balance
        // if ($id == 0) {
        //     $loans = DB::table('loan_applications')
        //         ->where([
        //             'employee_id' => $request->employee_id,
        //             'deduction_id' => $request->deduction_id
        //         ])
        //         ->where('balance', '>', 0,)
        //         ->count();

        //     if ($loans > 0) {
        //         return back()->with('error', 'Invalid loan application! Same loan is already applied with existing balance.');
        //     }
        // }

        $data = array(
            'deduction_id' => $request->deduction_id,
            'employee_id' => $request->employee_id,
            'loan_amount' => $request->loan_amount,
            'interest_rate' => $request->interest_rate,
            'term' => $request->term,
            'loan_amortization' => $request->loan_amortization,
            'remarks' => $request->remarks,
            'effectivity_date' => $request->effectivity_date,
            'end_date' => $request->end_date,
            'is_approve' => true,
            'is_disapprove' => false,
            'balance' => $request->loan_amount,
            'voucher_number' => $request->voucher_number,
            'active' => 1,
            'reconstructed' => 0,
            'user_id' => Auth::user()->id
        );

        if ($id == null || $id == 0) {
            $id = 0 + DB::table('loan_applications')->max('id');
            $id += 1;
        }
        DB::unprepared('SET IDENTITY_INSERT loan_applications ON');
        DB::table('loan_applications')->updateOrInsert(['id' => $id], $data);
        DB::unprepared('SET IDENTITY_INSERT loan_applications OFF');

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Payroll Module',
            'menu'    => 'Loan Application',
            'activity' => 'Reconstruct',
            'description' => 'Process loan application.',
        );

        Audit::create($data_audit);

        return $this->successResponse(null, 'You have successfully processed loan application!');
    }

    public function loanValidation($id)
    {
        try {
            if ($id == 0) {
            $data = 0;
        } else {
            $data = 1;
        }

        // $data = $credits . ' ' . $applied_days . ' ' . $data . ' from ' . $date_from . ' to ' . $date_to . ' ' . json_encode($data_un_date);

        return $this->successResponse($data, 'Loan validation completed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to validate loan: ' . $e->getMessage());
        }
    }

    public function getEmployeeTakeHome(Request $request)
    {
        try {
            $employee_id = $request->input('employee_id');

            if (!$employee_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee ID is required',
                    'debug' => ['received' => $request->all()]
                ]);
            }

            // Get employee with deduction amounts
            $employee = DB::table('employees')
                ->select(
                    'id',
                    'first_name',
                    'last_name',
                    'salary',
                    'tax_amount',
                    'gsis_amount',
                    'pagibig_amount',
                    'philhealth_amount'
                )
                ->where('id', $employee_id)
                ->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found',
                    'debug' => ['employee_id' => $employee_id]
                ]);
            }

            $salary = $employee->salary ?? 0;

            // Get stored deduction amounts or calculate if zero
            $gsis_amount = $employee->gsis_amount ?? 0;
            $philhealth_amount = $employee->philhealth_amount ?? 0;
            $pagibig_amount = $employee->pagibig_amount ?? 0;
            $tax_amount = $employee->tax_amount ?? 0;

            // Calculate dynamic deductions if stored values are 0
            if ($gsis_amount == 0) {
                $gsis_data = DB::table('gsis')->where('year', '<=', date('Y'))->orderBy('year', 'desc')->first();
                $gsis_amount = $gsis_data ? ($salary * $gsis_data->multiplier) : 0;
            }

            if ($philhealth_amount == 0) {
                $philhealth_data = DB::table('philhealth')->where('year', '<=', date('Y'))->orderBy('year', 'desc')->first();
                if ($philhealth_data) {
                    if ($salary >= $philhealth_data->income_ceiling) {
                        $philhealth_amount = $philhealth_data->fix_rate;
                    } elseif ($salary <= $philhealth_data->income_floor) {
                        $philhealth_amount = 0;
                    } else {
                        $philhealth_amount = (($salary * $philhealth_data->multiplier) / 2);
                    }
                }
            }

            if ($pagibig_amount == 0) {
                $pagibig_data = DB::table('pagibig')->where('year', '<=', date('Y'))->orderBy('year', 'desc')->first();
                $pagibig_amount = $pagibig_data ? ($salary * $pagibig_data->multiplier) : 0;
            }

            if ($tax_amount == 0) {
                $taxable_amount = $salary - ($gsis_amount + $philhealth_amount + $pagibig_amount);
                $tax_data = DB::table('tax_tables')->orderBy('min_amount')->get();
                foreach ($tax_data as $tax) {
                    if ($taxable_amount >= $tax->min_amount && $taxable_amount <= $tax->max_amount) {
                        $tax_amount = ((($taxable_amount - $tax->min_amount) * $tax->percentage) + $tax->base_tax);
                        break;
                    }
                }
            }

            // Get existing loan deductions
            $current_loan_deductions = 0;
            try {
                $existing_loans = DB::table('loan_applications')
                    ->selectRaw('SUM(ISNULL(loan_amortization, 0)) as total_loan_amortization')
                    ->where('employee_id', $employee_id)
                    ->where('balance', '>', 0)
                    ->whereRaw('ISNULL(active, 1) = 1')
                    ->first();
                $current_loan_deductions = $existing_loans->total_loan_amortization ?? 0;
            } catch (Exception $e) {
                $current_loan_deductions = 0;
            }

            // Calculate take-home salary
            $gross_amount = $salary;
            $total_mandatory_deductions = $gsis_amount + $philhealth_amount + $pagibig_amount + $tax_amount;
            $take_home_before_loans = $gross_amount - $total_mandatory_deductions;
            $take_home_after_loans = $take_home_before_loans - $current_loan_deductions;

            // Ensure no negative values
            $take_home_before_loans = max(0, $take_home_before_loans);
            $take_home_after_loans = max(0, $take_home_after_loans);

            // Calculate max recommended (30% of take-home before loans)
            $max_recommended_amortization = $take_home_before_loans * 0.30;

            return response()->json([
                'success' => true,
                'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                'basic_salary' => number_format($salary, 2),
                'gross_amount' => number_format($gross_amount, 2),
                'mandatory_deductions' => [
                    'gsis' => number_format($gsis_amount, 2),
                    'philhealth' => number_format($philhealth_amount, 2),
                    'pagibig' => number_format($pagibig_amount, 2),
                    'tax' => number_format($tax_amount, 2),
                    'total' => number_format($total_mandatory_deductions, 2)
                ],
                'other_deductions' => '0.00',
                'current_loan_deductions' => number_format($current_loan_deductions, 2),
                'take_home_before_loans' => number_format($take_home_before_loans, 2),
                'take_home_after_loans' => number_format($take_home_after_loans, 2),
                'max_recommended_amortization' => number_format($max_recommended_amortization, 2),
                'max_recommended_percentage' => '30%',
                'calculation_method' => [
                    'gsis' => ($employee->gsis_amount ?? 0) > 0 ? 'stored' : 'calculated',
                    'philhealth' => ($employee->philhealth_amount ?? 0) > 0 ? 'stored' : 'calculated',
                    'pagibig' => ($employee->pagibig_amount ?? 0) > 0 ? 'stored' : 'calculated',
                    'tax' => ($employee->tax_amount ?? 0) > 0 ? 'stored' : 'calculated'
                ],
                'debug' => [
                    'test_mode' => true,
                    'employee_found' => true,
                    'raw_salary' => $employee->salary,
                    'calculations' => [
                        'gsis_raw' => $gsis_amount,
                        'philhealth_raw' => $philhealth_amount,
                        'pagibig_raw' => $pagibig_amount,
                        'tax_raw' => $tax_amount,
                        'take_home_raw' => $take_home_after_loans
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'debug' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    public function getEmployeeTakeHomeOLD(Request $request)
    {
        $employee_id = null;
        $debug_info = [];
        $step = 'initialization';

        try {
            // Step 0: Input Validation
            $step = 'input_validation';
            $debug_info['step'] = $step;

            $request->validate([
                'employee_id' => 'required|integer'
            ]);

            $employee_id = $request->employee_id;
            $app_key = env("APP_KEY", "");

            $debug_info['employee_id'] = $employee_id;
            $debug_info['app_key_exists'] = !empty($app_key);

            // Step 1: Get employee information
            $step = 'fetch_employee_data';
            $debug_info['step'] = $step;

            try {
                $employee = DB::table('employees')
                    ->select(
                        'id',
                        'salary',
                        'employment_type_id',
                        'tax_amount',
                        'gsis_amount',
                        'pagibig_amount',
                        'philhealth_amount',
                        'first_name',
                        'last_name',
                        'is_encrypted'
                    )
                    ->where('id', $employee_id)
                    ->first();

                if (!$employee) {
                    \Log::error('Employee not found in getEmployeeTakeHome', [
                        'employee_id' => $employee_id,
                        'step' => $step
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Employee not found',
                        'debug' => array_merge($debug_info, ['error' => 'No employee record with ID: ' . $employee_id])
                    ]);
                }

                $debug_info['employee_found'] = true;
                $debug_info['salary'] = $employee->salary;
                $debug_info['is_encrypted'] = $employee->is_encrypted ?? false;

            } catch (Exception $e) {
                \Log::error('Database error fetching employee in getEmployeeTakeHome', [
                    'employee_id' => $employee_id,
                    'step' => $step,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Database error fetching employee: ' . $e->getMessage(),
                    'debug' => array_merge($debug_info, ['error' => $e->getMessage()])
                ], 500);
            }

            // Step 2: Handle employee name (with encryption support)
            $step = 'process_employee_name';
            $debug_info['step'] = $step;

            try {
                if ($employee->is_encrypted) {
                    $name_query = DB::select("SELECT
                        CASE WHEN ISNULL(is_encrypted,0) = 0 THEN
                            CONCAT(first_name,' ',last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](first_name,?))+' '+RTRIM([dbo].[ufn_DecryptString](last_name,?))
                        END as full_name
                        FROM employees WHERE id = ?", [$app_key, $app_key, $employee_id]);

                    $full_name = $name_query[0]->full_name ?? ($employee->first_name . ' ' . $employee->last_name);
                } else {
                    $full_name = $employee->first_name . ' ' . $employee->last_name;
                }

                $debug_info['full_name'] = $full_name;

            } catch (Exception $e) {
                \Log::error('Error processing employee name in getEmployeeTakeHome', [
                    'employee_id' => $employee_id,
                    'step' => $step,
                    'error' => $e->getMessage()
                ]);

                // Fallback to basic name
                $full_name = $employee->first_name . ' ' . $employee->last_name;
                $debug_info['name_error'] = $e->getMessage();
            }

            $salary = $employee->salary ?? 0;
            $basic_pay = $salary;

            $debug_info['salary'] = $salary;

            // Step 3: Process stored deduction amounts
            $step = 'process_stored_deductions';
            $debug_info['step'] = $step;

            $gsis_amount = $employee->gsis_amount ?? 0;
            $philhealth_amount = $employee->philhealth_amount ?? 0;
            $pagibig_amount = $employee->pagibig_amount ?? 0;
            $tax_amount = $employee->tax_amount ?? 0;

            $debug_info['stored_deductions'] = [
                'gsis' => $gsis_amount,
                'philhealth' => $philhealth_amount,
                'pagibig' => $pagibig_amount,
                'tax' => $tax_amount
            ];

            // Step 4: Calculate dynamic deductions if needed
            $step = 'calculate_dynamic_deductions';
            $debug_info['step'] = $step;

            // GSIS Calculation
            if ($gsis_amount == 0) {
                try {
                    $gsis_data = DB::table('gsis')->where('year', '<=', date('Y'))->orderBy('year', 'desc')->first();
                    $gsis_amount = $gsis_data ? ($salary * $gsis_data->multiplier) : 0;
                    $debug_info['gsis_calculated'] = true;
                    $debug_info['gsis_rate'] = $gsis_data->multiplier ?? 'N/A';
                } catch (Exception $e) {
                    $gsis_amount = 0;
                    $debug_info['gsis_error'] = $e->getMessage();
                }
            }

            // PhilHealth Calculation
            if ($philhealth_amount == 0) {
                try {
                    $philhealth_data = DB::table('philhealth')->where('year', '<=', date('Y'))->orderBy('year', 'desc')->first();
                    if ($philhealth_data) {
                        if ($salary >= $philhealth_data->income_ceiling) {
                            $philhealth_amount = $philhealth_data->fix_rate;
                        } elseif ($salary <= $philhealth_data->income_floor) {
                            $philhealth_amount = 0;
                        } else {
                            $philhealth_amount = (($salary * $philhealth_data->multiplier) / 2);
                        }
                    }
                    $debug_info['philhealth_calculated'] = true;
                } catch (Exception $e) {
                    $philhealth_amount = 0;
                    $debug_info['philhealth_error'] = $e->getMessage();
                }
            }

            // Pag-IBIG Calculation
            if ($pagibig_amount == 0) {
                try {
                    $pagibig_data = DB::table('pagibig')->where('year', '<=', date('Y'))->orderBy('year', 'desc')->first();
                    $pagibig_amount = $pagibig_data ? ($salary * $pagibig_data->multiplier) : 0;
                    $debug_info['pagibig_calculated'] = true;
                } catch (Exception $e) {
                    $pagibig_amount = 0;
                    $debug_info['pagibig_error'] = $e->getMessage();
                }
            }

            // Tax Calculation
            if ($tax_amount == 0) {
                try {
                    $taxable_amount = $salary - ($gsis_amount + $philhealth_amount + $pagibig_amount);
                    $tax_data = DB::table('tax_tables')->orderBy('min_amount')->get();
                    foreach ($tax_data as $tax) {
                        if ($taxable_amount >= $tax->min_amount && $taxable_amount <= $tax->max_amount) {
                            $tax_amount = ((($taxable_amount - $tax->min_amount) * $tax->percentage) + $tax->base_tax);
                            break;
                        }
                    }
                    $debug_info['tax_calculated'] = true;
                    $debug_info['taxable_amount'] = $taxable_amount;
                } catch (Exception $e) {
                    $tax_amount = 0;
                    $debug_info['tax_error'] = $e->getMessage();
                }
            }

            // Step 5: Get existing loan deductions
            $step = 'fetch_loan_deductions';
            $debug_info['step'] = $step;

            $current_loan_deductions = 0;
            try {
                $existing_loans = DB::table('loan_applications')
                    ->selectRaw('SUM(ISNULL(loan_amortization, 0)) as total_loan_amortization')
                    ->where('employee_id', $employee_id)
                    ->where('balance', '>', 0)
                    ->whereRaw('ISNULL(active, 1) = 1')
                    ->first();
                $current_loan_deductions = $existing_loans->total_loan_amortization ?? 0;
                $debug_info['loan_deductions_fetched'] = true;
            } catch (Exception $e) {
                $current_loan_deductions = 0;
                $debug_info['loan_deductions_error'] = $e->getMessage();
            }

            // Step 6: Get other deductions
            $step = 'fetch_other_deductions';
            $debug_info['step'] = $step;

            $other_deduction_amount = 0;
            try {
                $other_deductions = DB::table('payroll_deductions')
                    ->selectRaw('SUM(ISNULL(amount, 0)) as total_other_deductions')
                    ->where('employee_id', $employee_id)
                    ->whereNotIn('deduction_id', [100, 101, 102, 103, 104])
                    ->where('active', 1)
                    ->first();
                $other_deduction_amount = $other_deductions->total_other_deductions ?? 0;
                $debug_info['other_deductions_fetched'] = true;
            } catch (Exception $e) {
                $other_deduction_amount = 0;
                $debug_info['other_deductions_error'] = $e->getMessage();
                $debug_info['other_deductions_note'] = 'Table may not exist - continuing with 0';
            }

            // Step 7: Calculate final amounts
            $step = 'calculate_final_amounts';
            $debug_info['step'] = $step;

            $gross_amount = $basic_pay;
            $total_mandatory_deductions = $gsis_amount + $philhealth_amount + $pagibig_amount + $tax_amount;
            $take_home_before_loans = $gross_amount - $total_mandatory_deductions - $other_deduction_amount;
            $take_home_after_loans = $take_home_before_loans - $current_loan_deductions;

            // Calculate maximum recommendable loan amortization (30% rule)
            $max_recommended_amortization = max(0, $take_home_before_loans * 0.30);

            // Ensure no negative values
            $take_home_before_loans = max(0, $take_home_before_loans);
            $take_home_after_loans = max(0, $take_home_after_loans);

            $debug_info['final_calculations'] = [
                'gross_amount' => $gross_amount,
                'total_mandatory_deductions' => $total_mandatory_deductions,
                'take_home_before_loans' => $take_home_before_loans,
                'take_home_after_loans' => $take_home_after_loans,
                'max_recommended_amortization' => $max_recommended_amortization
            ];

            // Step 8: Prepare response
            $step = 'prepare_response';
            $debug_info['step'] = $step;

            $response = [
                'success' => true,
                'employee_name' => $full_name,
                'basic_salary' => number_format($salary, 2),
                'gross_amount' => number_format($gross_amount, 2),
                'mandatory_deductions' => [
                    'gsis' => number_format($gsis_amount, 2),
                    'philhealth' => number_format($philhealth_amount, 2),
                    'pagibig' => number_format($pagibig_amount, 2),
                    'tax' => number_format($tax_amount, 2),
                    'total' => number_format($total_mandatory_deductions, 2)
                ],
                'other_deductions' => number_format($other_deduction_amount, 2),
                'current_loan_deductions' => number_format($current_loan_deductions, 2),
                'take_home_before_loans' => number_format($take_home_before_loans, 2),
                'take_home_after_loans' => number_format($take_home_after_loans, 2),
                'max_recommended_amortization' => number_format($max_recommended_amortization, 2),
                'max_recommended_percentage' => '30%',
                'calculation_method' => [
                    'gsis' => ($employee->gsis_amount ?? 0) > 0 ? 'stored' : 'calculated',
                    'philhealth' => ($employee->philhealth_amount ?? 0) > 0 ? 'stored' : 'calculated',
                    'pagibig' => ($employee->pagibig_amount ?? 0) > 0 ? 'stored' : 'calculated',
                    'tax' => ($employee->tax_amount ?? 0) > 0 ? 'stored' : 'calculated'
                ]
            ];

            // Add debug info if in debug mode
            if (config('app.debug')) {
                $response['debug'] = $debug_info;
            }

            \Log::info('getEmployeeTakeHome completed successfully', [
                'employee_id' => $employee_id,
                'employee_name' => $full_name,
                'salary' => $salary,
                'take_home_after_loans' => $take_home_after_loans
            ]);

            return response()->json($response);

        } catch (ValidationException $e) {
            \Log::error('Validation error in getEmployeeTakeHome', [
                'employee_id' => $employee_id,
                'step' => $step,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
                'errors' => $e->errors(),
                'debug' => array_merge($debug_info, ['validation_errors' => $e->errors()])
            ], 422);

        } catch (Exception $e) {
            \Log::error('Unexpected error in getEmployeeTakeHome', [
                'employee_id' => $employee_id,
                'step' => $step,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $error_response = [
                'success' => false,
                'message' => 'Error calculating salary information at step: ' . $step,
                'error_details' => $e->getMessage(),
                'debug' => array_merge($debug_info, [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ])
            ];

            return response()->json($error_response, 500);
        }
    }

    /**
     * Export loan applications to PDF
     */
    public function exportPdf()
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('loan_applications as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('deductions as c', 'c.id', '=', 'deduction_id')
                ->select(
                    'a.id',
                    'a.voucher_number',
                    'a.loan_amount',
                    'a.loan_amortization',
                    'a.payment',
                    'a.balance',
                    'a.effectivity_date',
                    'a.end_date',
                    'a.interest_rate',
                    'a.term',
                    'a.remarks',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.first_name,' ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                                END as employee_name"),
                    'c.name as loan_type',
                    DB::raw("CASE WHEN a.is_approve = 'true' then 'Approved' else 'Pending' end as status")
                )
                ->where(DB::raw("isnull(a.active,1)"), 1)
                ->orderBy('a.effectivity_date', 'desc')
                ->get();

            $companies = DB::table('companies')->get();

            // Get logo for header
            $image = null;
            if (file_exists(public_path('/dist/img/logo.png'))) {
                $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
            }

            $pdf = PDF::loadView('loan_applications.export_pdf', compact('data', 'companies', 'image'))
                ->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('A4', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'loan_applications_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate loan applications PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export loan applications to Excel
     */
    public function exportExcel()
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('loan_applications as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('deductions as c', 'c.id', '=', 'deduction_id')
                ->select(
                    'a.id',
                    'a.voucher_number',
                    'a.loan_amount',
                    'a.loan_amortization',
                    'a.payment',
                    'a.balance',
                    'a.effectivity_date',
                    'a.end_date',
                    'a.interest_rate',
                    'a.term',
                    'a.remarks',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.first_name,' ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                                END as employee_name"),
                    'c.name as loan_type',
                    DB::raw("CASE WHEN a.is_approve = 'true' then 'Approved' else 'Pending' end as status")
                )
                ->where(DB::raw("isnull(a.active,1)"), 1)
                ->orderBy('a.effectivity_date', 'desc')
                ->get();

            // Transform data for Excel export
            $exportData = $data->map(function ($item) {
                return [
                    $item->employee_name,
                    $item->loan_type,
                    $item->voucher_number,
                    number_format($item->loan_amount, 2),
                    number_format($item->loan_amortization, 2),
                    number_format($item->payment ?? 0, 2),
                    number_format($item->balance, 2),
                    Carbon::parse($item->effectivity_date)->format('m-d-Y'),
                    Carbon::parse($item->end_date)->format('m-d-Y'),
                    $item->status
                ];
            })->toArray();

            $filename = 'loan_applications_' . date('Y-m-d') . '.xlsx';

            return Excel::download(new class($exportData) implements FromArray, WithHeadings, WithStyles, WithColumnWidths {
                private $data;

                public function __construct($data)
                {
                    $this->data = $data;
                }

                public function array(): array
                {
                    return $this->data;
                }

                public function headings(): array
                {
                    return [
                        'Employee Name',
                        'Loan Type',
                        'Voucher No.',
                        'Amount',
                        'Amortization',
                        'Payment',
                        'Balance',
                        'Effectivity Date',
                        'End Date',
                        'Status'
                    ];
                }

                public function styles(Worksheet $sheet)
                {
                    return [
                        1 => [
                            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'D3D3D3']
                            ]
                        ],
                    ];
                }

                public function columnWidths(): array
                {
                    return [
                        'A' => 20, // Employee Name
                        'B' => 20, // Loan Type
                        'C' => 15, // Voucher No.
                        'D' => 15, // Amount
                        'E' => 15, // Amortization
                        'F' => 15, // Payment
                        'G' => 15, // Balance
                        'H' => 15, // Effectivity Date
                        'I' => 15, // End Date
                        'J' => 12, // Status
                    ];
                }
            }, $filename);

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate loan applications Excel: ' . $e->getMessage());
        }
    }

    /**
     * Generate print view for loan applications
     */
    public function print()
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('loan_applications as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('deductions as c', 'c.id', '=', 'deduction_id')
                ->select(
                    'a.id',
                    'a.voucher_number',
                    'a.loan_amount',
                    'a.loan_amortization',
                    'a.payment',
                    'a.balance',
                    'a.effectivity_date',
                    'a.end_date',
                    'a.interest_rate',
                    'a.term',
                    'a.remarks',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.first_name,' ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                                END as employee_name"),
                    'c.name as loan_type',
                    DB::raw("CASE WHEN a.is_approve = 'true' then 'Approved' else 'Pending' end as status")
                )
                ->where(DB::raw("isnull(a.active,1)"), 1)
                ->orderBy('a.effectivity_date', 'desc')
                ->get();

            $companies = DB::table('companies')->get();

            // Get logo for header
            $image = null;
            if (file_exists(public_path('/dist/img/logo.png'))) {
                $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
            }

            return view('loan_applications.print', compact('data', 'companies', 'image'));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate print view: ' . $e->getMessage()], 500);
        }
    }
}
