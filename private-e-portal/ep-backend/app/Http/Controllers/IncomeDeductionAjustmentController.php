<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class IncomeDeductionAjustmentController extends Controller
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
            $PayrollPeriodType = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name"),
                    'a.release_date'
                )
                ->where(['posted' => false])
                ->orderBy('a.release_date', 'desc')
                ->get();

            $EmploymentType = DB::table('employment_types')->where('active', true)->get();

            return $this->successResponse([
                'payroll_period_types' => $PayrollPeriodType,
                'employment_types' => $EmploymentType
            ], 'Income deduction adjustment data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve income deduction adjustment data: ' . $e->getMessage());
        }
    }

    public function storeIncome(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'income_id' => 'required',
                'payroll_period_type_id' => 'required',
                'employment_type_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $dataX = $request->all();

            $payroll_period_type_id = $request->payroll_period_type_id;
            $employment_type_id = $request->employment_type_id;
            $income_id = $request->income_id;

            // Store Incomes
            $arr_len = count($dataX['employee_id']);
            $payroll_income = [];
            $processed_count = 0;

            for ($i = 0; $i < $arr_len; $i++) {
                if ($dataX['employee_id'][$i] != NULL && $dataX['amount'][$i] >= 0) {

                    $payroll_income = [
                        'payroll_period_id'      => $payroll_period_type_id,
                        'employment_id' => $employment_type_id,
                        'income_id' => $income_id,
                        'employee_id'     => $dataX['employee_id'][$i],
                        'amount'     => $dataX['amount'][$i],
                    ];

                    DB::table('payroll_incomes')->updateOrInsert(['payroll_period_id' => $payroll_period_type_id, 'employment_id' => $employment_type_id, 'income_id' => $income_id, 'employee_id' => $dataX['employee_id'][$i]], $payroll_income);
                    $processed_count++;
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Income and Deductions',
                'activity' => 'Update',
                'description' => 'Update Payroll Income',
            );

            Audit::create($data_audit);

            return $this->successResponse(['processed_count' => $processed_count], 'Payroll incomes updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update payroll incomes: ' . $e->getMessage());
        }
    }

    public function storeDeduction(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'deduction_id' => 'required',
                'payroll_period_id' => 'required',
                'employment_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $dataX = $request->all();

            $payroll_period_type_id = $request->payroll_period_id;
            $employment_type_id = $request->employment_id;
            $deduction_id = $request->deduction_id;

            // Store Incomes
            $arr_len = count($dataX['employee_id']);
            $payroll_deduction = [];
            $processed_count = 0;

            for ($i = 0; $i < $arr_len; $i++) {
                if ($dataX['employee_id'][$i] != NULL && $dataX['amount'][$i] >= 0) {

                    $payroll_deduction = [
                        'payroll_period_id' => $payroll_period_type_id,
                        'employment_id' => $employment_type_id,
                        'deduction_id' => $deduction_id,
                        'employee_id'     => $dataX['employee_id'][$i],
                        'amount'     => $dataX['amount'][$i],
                    ];

                    DB::table('payroll_deductions')->updateOrInsert(['payroll_period_id' => $payroll_period_type_id, 'employment_id' => $employment_type_id, 'deduction_id' => $deduction_id, 'employee_id' => $dataX['employee_id'][$i]], $payroll_deduction);
                    $processed_count++;
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Income and Deductions',
                'activity' => 'Update',
                'description' => 'Update Payroll Deduction',
            );

            Audit::create($data_audit);

            return $this->successResponse(['processed_count' => $processed_count], 'Payroll deductions updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update payroll deductions: ' . $e->getMessage());
        }
    }

    public function getIncomeList($payroll_period_type_id, $employment_type_id)
    {
        try {
            $interval = DB::table('payroll_periods')->select('payroll_interval_id')->where('id', $payroll_period_type_id)->get();

            if ($interval->isEmpty()) {
                return $this->notFoundResponse('Payroll period not found');
            }

            $data = DB::table('incomes as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.income_id')
                ->join('payroll_item_schedule_headers as c', 'b.payroll_item_schedule_header_id', '=', 'c.id')
                ->select('a.id', 'a.name')
                ->where(['c.payroll_interval_type_id' => $interval[0]->payroll_interval_id, 'c.employment_type_id' => $employment_type_id, 'b.active' => true])
                ->orderBy('a.id', 'asc')
                ->distinct()
                ->get();

            return $this->successResponse($data, 'Income list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve income list: ' . $e->getMessage());
        }
    }

    public function getDeductionList($payroll_period_type_id, $employment_type_id)
    {
        try {
            $interval = DB::table('payroll_periods')->select('payroll_interval_id')->where('id', $payroll_period_type_id)->get();

            if ($interval->isEmpty()) {
                return $this->notFoundResponse('Payroll period not found');
            }

            $data = DB::table('deductions as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.deduction_id')
                ->join('payroll_item_schedule_headers as c', 'b.payroll_item_schedule_header_id', '=', 'c.id')
                ->select('a.id', 'a.name')
                ->where(['c.payroll_interval_type_id' => $interval[0]->payroll_interval_id, 'c.employment_type_id' => $employment_type_id, 'b.active' => true])
                ->orderBy('a.id', 'asc')
                ->distinct()
                ->get();

            return $this->successResponse($data, 'Deduction list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve deduction list: ' . $e->getMessage());
        }
    }

    public function getEmployeeList($payroll_period_type_id, $employment_type_id, $type_id, $item_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($type_id == 1) { //if deduction

                $loans = DB::table('loan_applications as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->leftjoin('name_suffixes as c', 'b.name_suffix_id', '=', 'c.id')
                    ->leftjoin('companies as d', 'b.company_id', '=', 'd.id')
                    ->leftjoin('branches as e', 'b.branch_id', '=', 'e.id')
                    ->select(
                        'b.id as employee_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        db::raw("upper(c.name) as suffix"),
                        'd.name as company',
                        db::raw("case when (a.loan_amortization > a.balance) then a.balance
                                when (a.loan_amortization <= a.balance) then a.loan_amortization
                                else cast(0 as decimal(18,2))
                        end as amount")
                    )
                    ->where('a.balance', '>', 0)
                    ->where([
                        'b.active' => true,
                        'b.is_employee' => true,
                        'a.deduction_id' => $item_id
                    ]);

                $data = DB::table('employees as a')
                    ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                    ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->join('payroll_periods as f', 'a.payroll_interval_id', '=', 'f.payroll_interval_id')
                    ->select(
                        'a.id as employee_id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                        db::raw("upper(c.name) as suffix"),
                        'd.name as company',
                        db::raw("cast(0 as decimal(18,2)) as amount")
                    )
                    ->where(['a.employment_type_id' => $employment_type_id, 'f.id' => $payroll_period_type_id, 'a.active' => true])
                    ->whereNotIn('a.id', function ($query) {
                        $query->select('employee_id')->from('loan_applications')->where('balance', '>', 0)->get();
                    })
                    ->unionAll($loans)
                    ->orderBy('last_name', 'asc')
                    ->get();
            } else { //if income
                $data = DB::table('employees as a')
                    ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                    ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->join('payroll_periods as f', 'a.payroll_interval_id', '=', 'f.payroll_interval_id')
                    ->select(
                        'a.id as employee_id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                        db::raw("upper(c.name) as suffix"),
                        'd.name as company'
                    )
                    ->where(['a.employment_type_id' => $employment_type_id, 'f.id' => $payroll_period_type_id, 'a.active' => true])
                    ->orderBy('a.last_name', 'asc')
                    ->get();
            }

            return $this->successResponse($data, 'Employee list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee list: ' . $e->getMessage());
        }
    }

    public function getEmployeeIncome($payroll_period_type_id, $employment_type_id, $income_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $data_new = DB::table('employees as a')
                ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    db::raw("upper(c.name) as suffix"),
                    db::raw("ISNULL(d.name,'') as company"),
                    db::raw("CAST(0 AS DECIMAL(18,2)) as amount")
                )
                ->where([
                    'a.employment_type_id' => $employment_type_id,
                    'a.active' => true,
                    'a.is_employee' => true
                ])
                ->whereNotIn('a.id', function ($query) use ($payroll_period_type_id, $income_id) {
                    $query->select('employee_id')->from('payroll_incomes')->where([
                        'payroll_period_id' => $payroll_period_type_id,
                        'income_id' => $income_id
                    ]);
                });

            $data = DB::table('employees as a')
                ->join('payroll_incomes as b', 'a.id', '=', 'b.employee_id')
                ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    db::raw("upper(c.name) as suffix"),
                    db::raw("ISNULL(d.name,'') as company"),
                    'b.amount'
                )
                ->where([
                    'a.employment_type_id' => $employment_type_id,
                    'b.payroll_period_id' => $payroll_period_type_id,
                    'a.active' => true,
                    'a.is_employee' => true,
                    'b.income_id' => $income_id
                ])
                ->union($data_new)
                ->orderBy('last_name', 'asc')
                ->get();

            return $this->successResponse($data, 'Employee income data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee income data: ' . $e->getMessage());
        }
    }

    public function getEmployeePreviousIncome($payroll_period_type_id, $employment_type_id, $income_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $payroll_period = DB::table('payroll_periods')
                ->where('id', $payroll_period_type_id)
                ->get();

            if ($payroll_period->isEmpty()) {
                return $this->notFoundResponse('Payroll period not found');
            }

            $payroll_interval_id = $payroll_period[0]->payroll_interval_id;
            $payroll_cutoff_id = $payroll_period[0]->payroll_cutoff_id;
            $payroll_start_date = $payroll_period[0]->payroll_start_date;

            $payroll_period2 = DB::table('payroll_periods')
                ->where(['payroll_interval_id' => $payroll_interval_id, 'payroll_cutoff_id' => $payroll_cutoff_id])
                ->where('payroll_start_date', '<', $payroll_start_date)
                ->orderBy('payroll_start_date', 'desc')
                ->get();

            if ($payroll_period2->isNotEmpty()) {
                $payroll_period_type_id2 = $payroll_period2[0]->id;

                $data_new = DB::table('employees as a')
                    ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                    ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id as employee_id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                        db::raw("upper(c.name) as suffix"),
                        db::raw("ISNULL(d.name,'') as company"),
                        db::raw("CAST(0 AS DECIMAL(18,2)) as amount")
                    )
                    ->where([
                        'a.employment_type_id' => $employment_type_id,
                        'a.active' => true,
                        'a.is_employee' => true
                    ])
                    ->whereNotIn('a.id', function ($query) use ($payroll_period_type_id) {
                        $query->select('employee_id')->from('payroll_incomes')->where('payroll_period_id', $payroll_period_type_id);
                    });

                $data = DB::table('employees as a')
                    ->join('payroll_incomes as b', 'a.id', '=', 'b.employee_id')
                    ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                    ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id as employee_id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                        db::raw("upper(c.name) as suffix"),
                        db::raw("ISNULL(d.name,'') as company"),
                        'b.amount'
                    )
                    ->where(['a.employment_type_id' => $employment_type_id, 'b.payroll_period_id' => $payroll_period_type_id2, 'a.active' => true, 'b.income_id' => $income_id])
                    ->unionAll($data_new)
                    ->orderBy('last_name', 'asc')
                    ->get();

                return $this->successResponse($data, 'Employee previous income data retrieved successfully');
            } else {
                return $this->successResponse([], 'No previous payroll period found');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee previous income data: ' . $e->getMessage());
        }
    }

    public function getEmployeeDeduction($payroll_period_type_id, $employment_type_id, $deduction_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $loans = DB::table('loan_applications as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftjoin('name_suffixes as c', 'b.name_suffix_id', '=', 'c.id')
                ->leftjoin('companies as d', 'b.company_id', '=', 'd.id')
                ->leftjoin('branches as e', 'b.branch_id', '=', 'e.id')
                ->select(
                    'b.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    db::raw("upper(isnull(c.name,'')) as suffix"),
                    db::raw("ISNULL(d.name,'') as company"),
                    db::raw("case when (a.loan_amortization > a.balance) then a.balance
                                when (a.loan_amortization <= a.balance) then a.loan_amortization
                                else cast(0 as decimal(18,2))
                        end as amount")
                )
                ->where('a.balance', '>', 0)
                ->whereNotIn(
                    'b.id',
                    function ($query) use ($payroll_period_type_id, $deduction_id) {
                        $query->select('employee_id')->from('payroll_deductions')->where([
                            'payroll_period_id' => $payroll_period_type_id,
                            'deduction_id' => $deduction_id
                        ])->get();
                    }
                )
                ->where([
                    'b.active' => true,
                    'b.is_employee' => true,
                    'a.deduction_id' => $deduction_id
                ]);

            $data_new = DB::table('employees as a')
                ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                ->join('payroll_periods as f', 'a.payroll_interval_id', '=', 'f.payroll_interval_id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    db::raw("upper(isnull(c.name,'')) as suffix"),
                    db::raw("ISNULL(d.name,'') as company"),
                    db::raw("cast(0 as decimal(18,2)) as amount")
                )
                ->where(['a.employment_type_id' => $employment_type_id, 'f.id' => $payroll_period_type_id, 'a.active' => true])
                ->whereNotIn('a.id', function ($query) use ($deduction_id) {
                    $query->select('employee_id')->from('loan_applications')->where('balance', '>', 0)->where('deduction_id', $deduction_id)->get();
                })
                ->whereNotIn('a.id', function ($query) use ($payroll_period_type_id, $deduction_id) {
                    $query->select('employee_id')->from('payroll_deductions')->where([
                        'payroll_period_id' => $payroll_period_type_id,
                        'deduction_id' => $deduction_id
                    ])->get();
                })
                ->unionAll($loans);

            $data = DB::table('employees as a')
                ->join('payroll_deductions as b', 'a.id', '=', 'b.employee_id')
                ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    db::raw("upper(isnull(c.name,'')) as suffix"),
                    db::raw("ISNULL(d.name,'') as company"),
                    'b.amount'
                )
                ->where([
                    'a.employment_type_id' => $employment_type_id,
                    'b.payroll_period_id' => $payroll_period_type_id,
                    'a.active' => true,
                    'a.is_employee' => true,
                    'b.deduction_id' => $deduction_id
                ])
                ->unionAll($data_new)
                ->orderBy('last_name', 'asc')
                ->get();

            return $this->successResponse($data, 'Employee deduction data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee deduction data: ' . $e->getMessage());
        }
    }

    public function getEmployeePreviousDeduction($payroll_period_type_id, $employment_type_id, $deduction_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $payroll_period = DB::table('payroll_periods')
                ->where('id', $payroll_period_type_id)
                ->get();

            if ($payroll_period->isEmpty()) {
                return $this->notFoundResponse('Payroll period not found');
            }

            $payroll_interval_id = $payroll_period[0]->payroll_interval_id;
            $payroll_cutoff_id = $payroll_period[0]->payroll_cutoff_id;
            $payroll_start_date = $payroll_period[0]->payroll_start_date;

            $payroll_period2 = DB::table('payroll_periods')
                ->where(['payroll_interval_id' => $payroll_interval_id, 'payroll_cutoff_id' => $payroll_cutoff_id])
                ->where('payroll_start_date', '<', $payroll_start_date)
                ->orderBy('payroll_start_date', 'desc')
                ->get();

            if (count($payroll_period2) > 0) {
                $payroll_period_type_id2 = $payroll_period2[0]->id;

                $data = DB::table('employees as a')
                    ->join('payroll_deductions as b', 'a.id', '=', 'b.employee_id')
                    ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                    ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id as employee_id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                        db::raw("upper(c.name) as suffix"),
                        db::raw("ISNULL(d.name,'') as company"),
                        'b.amount'
                    )
                    ->where(['a.employment_type_id' => $employment_type_id, 'b.payroll_period_id' => $payroll_period_type_id2, 'a.active' => true, 'b.deduction_id' => $deduction_id])
                    ->orderBy('a.last_name', 'asc')
                    ->get();

                return $this->successResponse($data, 'Employee previous deduction data retrieved successfully');
            } else {
                return $this->successResponse([], 'No previous payroll period found');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee previous deduction data: ' . $e->getMessage());
        }
    }
}
