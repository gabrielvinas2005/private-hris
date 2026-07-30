<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PendingDeductionController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $branches = DB::table('branches')->get();

            return $this->successResponse($branches, 'Pending deduction data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve pending deduction data: ' . $e->getMessage());
        }
    }

    public function view(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $validator = Validator::make($request->all(), [
                'branch_id' => 'required',
                'employee_id' => 'required',
                'year_id' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = DB::table('pending_deductions as a')
                ->join('payroll_periods as b', 'a.payroll_period_id', '=', 'b.id')
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->join('branches as d', 'c.branch_id', '=', 'd.id')
                ->select(
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                    CONCAT(c.first_name,' ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                                END as name"),
                    DB::raw("DATENAME(M, b.release_date) as month"),
                    DB::raw("DATEPART(M, b.release_date) as month_id"),
                    DB::raw("case when is_absent = 1 then
                                    'Absent'
                                    when is_late = 1 then
                                    'Late'
                                    when is_undertime = 1 then
                                    'Undertime'
                            end deduction_type"),
                    DB::raw("0 as duration"),
                    'a.amount as amount_due',
                    DB::raw("isnull(amount_paid,0) as amount_deducted"),
                    DB::raw("case when (isnull(a.amount,0) - isnull(amount_paid,0)) < 0 then 
                                cast(0 as decimal(18,2)) 
                             else 
                                (isnull(a.amount,0) - isnull(amount_paid,0)) 
                             end as pending_deduction"),
                    'a.employee_id'
                )
                ->where([
                    'c.branch_id' => $request->branch_id,
                    'c.id' => $request->employee_id
                ])
                ->whereYear('b.release_date', $request->year_id)
                ->distinct()
                ->orderBy('deduction_type', 'asc')
                ->get();

            $month_header = DB::table('months')->orderBy('id', 'asc')->get();

            $year = $request->year_id;

            return $this->successResponse([
                'data' => $data,
                'month_header' => $month_header,
                'year' => $year
            ], 'Pending deduction view data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve pending deduction view data: ' . $e->getMessage());
        }
    }
}
