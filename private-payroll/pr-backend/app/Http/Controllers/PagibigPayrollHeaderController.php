<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\PayrollBenefitsEmployeeScope;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PagibigPayrollHeaderController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $payroll_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name"),
                    'a.release_date',
                    'c.name as cutoff_name'
                )
                ->where(['posted' => false])
                ->orderBy('a.release_date', 'desc')
                ->get();

            return $this->successResponse($payroll_periods, 'Pagibig payroll periods retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve Pagibig payroll periods: ' . $e->getMessage());
        }
    }

    public function loadEmployees(int $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Fetch payroll periods
            $payroll_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name"),
                    'a.release_date',
                    'a.payroll_start_date'
                )
                ->where(['posted' => false])
                ->orderBy('a.release_date', 'desc')
                ->get();

            $selected_period = DB::table('payroll_periods')
                ->where('id', $payroll_period_id)
                ->select('id', 'payroll_start_date', 'release_date')
                ->first();

            if (!$selected_period) {
                return $this->notFoundResponse('Selected payroll period was not found.');
            }

            // Get the year from the payroll start date
            $year = $selected_period->payroll_start_date;

            // Get the default amount from Pag-IBIG setups for the year
            $default_pagibig_amount = DB::table('pagibig_setups')
                ->whereRaw("year = YEAR('$year')")
                ->value('amount') ?? 200;

            $pagibigPayrollsQuery = DB::table('time_data_summary as tds')
                ->join('employees as a', 'tds.employee_id', '=', 'a.id')
                ->leftJoin('companies as b', 'a.company_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                ->leftJoin('payroll_summaries as ps', function ($join) use ($payroll_period_id) {
                    $join->on('ps.employee_id', '=', 'a.id')
                        ->where('ps.payroll_period_id', '=', $payroll_period_id);
                })
                ->leftJoin('pagibig_payroll_headers as curr', function ($join) use ($payroll_period_id) {
                    $join->on('curr.employee_id', '=', 'a.id')
                        ->where('curr.payroll_period_id', '=', $payroll_period_id);
                })
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            CONCAT(a.first_name,' ',a.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                        END as name"),
                    'b.name as company',
                    'c.name as department',
                    'd.name as division',
                    DB::raw("
                        CAST(
                            COALESCE(
                                curr.amount,
                                (
                                    SELECT TOP 1 prev.amount
                                    FROM pagibig_payroll_headers AS prev
                                    JOIN payroll_periods AS pp ON pp.id = prev.payroll_period_id
                                    WHERE prev.employee_id = a.id
                                        AND prev.payroll_period_id <> $payroll_period_id
                                        AND pp.release_date <= '{$selected_period->release_date}'
                                    ORDER BY pp.release_date DESC, prev.id DESC
                                ),
                                $default_pagibig_amount
                            ) AS decimal(18,2)
                        ) as amount"),
                    'ps.salary',
                    'ps.net_pay',
                    'ps.tax as tax_amount',
                    'ps.philhealth as philhealth_amount',
                    'ps.gsis as gsis_amount'
                )
                ->where([
                    'tds.payroll_period_id' => $payroll_period_id,
                    'a.active' => true,
                    'a.is_employee' => true,
                ]);
            PayrollBenefitsEmployeeScope::apply($pagibigPayrollsQuery, 'a');
            $pagibig_payrolls = $pagibigPayrollsQuery
                ->distinct()
                ->orderBy('name')
                ->get();

            return $this->successResponse([
                'payroll_periods' => $payroll_periods,
                'pagibig_payrolls' => $pagibig_payrolls
            ], 'Pagibig payroll table retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load Pagibig payroll employees: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payroll_period_id' => 'required|integer',
                'employee_id' => 'required|array',
                'employee_id.*' => 'required|integer',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = $request->all();
            $updated_count = 0;
            $created_count = 0;

            for ($i = 0; $i < count($data['employee_id']); $i++) {

                $payroll_year = DB::table('payroll_periods')
                    ->where('id', $request->payroll_period_id)
                    ->value(DB::raw("YEAR(release_date)"));

                $default_pagibig_amount = DB::table('pagibig_setups')
                    ->whereYear('year', $payroll_year)
                    ->value('amount') ?? 200;

                $minimum_net_amount = DB::table('payroll_summaries')
                    ->where([
                        'payroll_period_id' => $request->payroll_period_id,
                        'employee_id' => $data['employee_id'][$i]
                    ])
                    ->value('net_pay') ?? $this->getInitialSalary($data['employee_id'][$i], $data['amount'][$i]);

                $amount = $data['amount'][$i];

                $minimum_net_amount = $minimum_net_amount < 5000 ? 5000 : $minimum_net_amount;

                if ($default_pagibig_amount > $data['amount'][$i]) {
                    $amount = $default_pagibig_amount;
                }

                if ($minimum_net_amount < $amount) {
                    $amount = $default_pagibig_amount;
                }

                $pagibig_payroll_data = [
                    'employee_id' => $data['employee_id'][$i],
                    'payroll_period_id' => $request->payroll_period_id,
                    'amount' => $amount,
                ];

                // Check if record exists
                $existing = DB::table('pagibig_payroll_headers')
                    ->where([
                        'employee_id' => $data['employee_id'][$i],
                        'payroll_period_id' => $request->payroll_period_id
                    ])
                    ->exists();

                DB::table('pagibig_payroll_headers')->updateOrInsert([
                    'employee_id' => $data['employee_id'][$i],
                    'payroll_period_id' => $request->payroll_period_id
                ], $pagibig_payroll_data);

                if ($existing) {
                    $updated_count++;
                } else {
                    $created_count++;
                }

                // update employee records pagibig amount
                $pagibig_amount = DB::table('pagibig_payroll_headers as a')
                    ->join('payroll_periods as b', 'a.payroll_period_id', '=', 'b.id')
                    ->select('a.amount')
                    ->where([
                        'a.employee_id' => $data['employee_id'][$i],
                    ])
                    ->orderByRaw("YEAR(b.release_date) DESC")
                    ->limit(1)
                    ->get();

                DB::table('employees')
                    ->where('id', $data['employee_id'][$i])
                    ->update(['pagibig_amount' => $pagibig_amount[0]->amount ?? $amount]);
            }

            return $this->successResponse([
                'created_count' => $created_count,
                'updated_count' => $updated_count,
                'total_processed' => count($data['employee_id'])
            ], 'The HDMF Premium has been successfully updated.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update HDMF Premium: ' . $e->getMessage());
        }
    }

    public function validateAmount($employee_id, $amount, $payroll_period_id)
    {
        try {
            $validator = Validator::make([
                'employee_id' => $employee_id,
                'amount' => $amount,
                'payroll_period_id' => $payroll_period_id
            ], [
                'employee_id' => 'required|integer',
                'amount' => 'required|numeric|min:0',
                'payroll_period_id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $payroll_year = DB::table('payroll_periods')
                ->where('id', $payroll_period_id)
                ->value(DB::raw("YEAR(release_date)"));

            $default_pagibig_amount = DB::table('pagibig_setups')
                ->whereYear('year', $payroll_year)
                ->value('amount') ?? 200;

            $minimum_net_amount = DB::table('payroll_summaries')
                ->where([
                    'payroll_period_id' => $payroll_period_id,
                    'employee_id' => $employee_id
                ])
                ->value('net_pay') ?? $this->getInitialSalary($employee_id, $amount);

            $minimum_net_amount = $minimum_net_amount < 5000 ? 5000 : $minimum_net_amount;

            if ($default_pagibig_amount > $amount) {
                return $this->errorResponse('The amount entered is lower than the government-mandated HDMF premium. Please enter a valid amount.');
            }

            if ($minimum_net_amount < $amount) {
                return $this->errorResponse('The entered amount would result in an amount less than the minimum required net take-home pay PHP 5,000. Please enter a different amount.');
            }

            return $this->successResponse(['valid' => true], 'Valid amount.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to validate amount: ' . $e->getMessage());
        }
    }

    protected function getInitialSalary($employee_id, $pagibig_amount)
    {
        try {
            $employee = DB::table('employees')
                ->where('id', $employee_id)
                ->get();

            if ($employee->isEmpty()) {
                return 0;
            }

            $salary = $employee[0]->salary - ($employee[0]->philhealth_amount + $employee[0]->tax_amount + $employee[0]->gsis_amount + $pagibig_amount);

            return $salary;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
