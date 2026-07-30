<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
                    'a.release_date'
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

            // Get the year from the payroll start date
            $year = $payroll_periods[0]->payroll_start_date;

            // Get the default amount from Pag-IBIG setups for the year
            $default_pagibig_amount = DB::table('pagibig_setups')
                ->whereRaw("year = YEAR('$year')")
                ->value('amount') ?? 200;

            // Fetch the latest Pag-IBIG amount for employees from past payroll periods
            $latest_pagibig_payrolls = DB::table('pagibig_payroll_headers as p1')
                ->join('employees as a', 'p1.employee_id', '=', 'a.id')
                ->leftJoin('companies as c', 'a.company_id', '=', 'c.id')
                ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            CONCAT(a.first_name,' ',a.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                        END as name"),
                    'c.name as company',
                    'd.name as division',
                    'p1.amount'
                )
                ->whereRaw('p1.id = (SELECT MAX(p2.id) FROM pagibig_payroll_headers AS p2 WHERE p2.employee_id = p1.employee_id)');

            $employees_without_payroll = DB::table('employees as a')
                ->leftJoin('companies as c', 'a.company_id', '=', 'c.id')
                ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            CONCAT(a.first_name,' ',a.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                        END as name"),
                    'c.name as company',
                    'd.name as division',
                    DB::raw("cast($default_pagibig_amount as decimal(18,2)) as amount")
                )
                ->whereNotIn('a.id', function ($query) use ($payroll_period_id) {
                    $query->select('employee_id')
                        ->from('pagibig_payroll_headers')
                        ->where('payroll_period_id', $payroll_period_id);
                })
                ->where([
                    'a.active' => true,
                    'a.is_employee' => true,
                ]);

            // Combine the latest payroll data and employees without payroll data, ensuring no duplicates
            $pagibig_payrolls = DB::table('pagibig_payroll_headers as p1')
                ->join('employees as a', 'p1.employee_id', '=', 'a.id')
                ->leftJoin('companies as c', 'a.company_id', '=', 'c.id')
                ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                        CONCAT(a.first_name,' ',a.last_name)
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                    END as name"),
                    'c.name as company',
                    'd.name as division',
                    'p1.amount'
                )
                ->where([
                    'p1.payroll_period_id' => $payroll_period_id,
                    'a.active' => true,
                    'a.is_employee' => true,
                ])
                ->union($latest_pagibig_payrolls)
                ->union($employees_without_payroll)
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
