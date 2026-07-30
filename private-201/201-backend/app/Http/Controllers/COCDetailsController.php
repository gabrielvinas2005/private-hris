<?php

namespace App\Http\Controllers;

use Auth;
use Image;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Traits\ApiResponse;

class COCDetailsController extends Controller
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
            $Service_Credits = DB::table('leave_types')->where('service_credit', true)->get();

            if (count($Service_Credits) == 0) {
                $Service_Credit = 0;
                $coc_id = 0;
            } else {
                $Service_Credit = 1;
                $coc_id = $Service_Credits[0]->id;
            }

            if (Auth::user()->access_all_branches) {
                $coc_total_hours = DB::table('overtime_applications as a')
                    ->join('overtime_types as b', 'a.overtime_type_id', '=', 'b.id')
                    ->join('employees as c', 'c.id', '=', 'a.employee_id')
                    ->select(
                        'a.employee_id',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                   CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                                END as name"),
                        DB::raw("DATENAME(month, a.date) as months"),
                        DB::raw("MONTH(a.date) as months_num"),
                        DB::raw("YEAR(a.date) as years"),
                        DB::raw("SUM(isnull(a.total_hours,0) * b.rate) as total_hours"),
                        db::raw("cast(0 as decimal(18,2)) as carryover")
                    )
                    ->where(['a.service_credits' => true, 'a.approved' => true, 'c.is_employee' => true, 'c.active' => true])
                    ->groupBy(
                        DB::raw("DATENAME(month, a.date)"),
                        DB::raw("MONTH(a.date)"),
                        DB::raw("YEAR(a.date)"),
                        'a.employee_id',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                   CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                                END"),
                    );

                $cto_total_hours = DB::table('leave_headers as a')
                    ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                    ->join('employees as c', 'c.id', '=', 'a.employee_id')
                    ->select(
                        'a.employee_id',
                        DB::raw("DATENAME(month, b.leave_date) as months"),
                        DB::raw("MONTH(b.leave_date) as months_num"),
                        DB::raw("YEAR(b.leave_date) as years"),
                        DB::raw("SUM(isnull(b.with_pay,0)) * 8 as total_leave_hours")
                    )
                    ->where(['a.leave_type_id' => $coc_id, 'a.approved' => true, 'c.is_employee' => true, 'c.active' => true])
                    ->groupBy(
                        DB::raw("DATENAME(month, b.leave_date)"),
                        DB::raw("MONTH(b.leave_date)"),
                        DB::raw("YEAR(b.leave_date)"),
                        'a.employee_id'
                    );
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->get();

                if ($user_branch_id->isEmpty()) {
                    return $this->errorResponse('User branch information not found.');
                }

                $coc_total_hours = DB::table('overtime_applications as a')
                    ->join('overtime_types as b', 'a.overtime_type_id', '=', 'b.id')
                    ->join('employees as c', 'c.id', '=', 'a.employee_id')
                    ->select(
                        'a.employee_id',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                   CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                                END as name"),
                        DB::raw("DATENAME(month, a.date) as months"),
                        DB::raw("MONTH(a.date) as months_num"),
                        DB::raw("YEAR(a.date) as years"),
                        DB::raw("SUM(isnull(a.total_hours,0) * b.rate) as total_hours"),
                        db::raw("cast(0 as decimal(18,2)) as carryover")
                    )
                    ->where(['a.service_credits' => true, 'a.approved' => true, 'c.is_employee' => true, 'c.active' => true, 'c.branch_id' => $user_branch_id[0]->branch_id])
                    ->groupBy(
                        DB::raw("DATENAME(month, a.date)"),
                        DB::raw("MONTH(a.date)"),
                        DB::raw("YEAR(a.date)"),
                        'a.employee_id',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                   CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                                END"),
                    );

                $cto_total_hours = DB::table('leave_headers as a')
                    ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                    ->join('employees as c', 'c.id', '=', 'a.employee_id')
                    ->select(
                        'a.employee_id',
                        DB::raw("DATENAME(month, b.leave_date) as months"),
                        DB::raw("MONTH(b.leave_date) as months_num"),
                        DB::raw("YEAR(b.leave_date) as years"),
                        DB::raw("SUM(isnull(b.with_pay,0)) * 8 as total_leave_hours")
                    )
                    ->where(['a.leave_type_id' => $coc_id, 'a.approved' => true, 'c.is_employee' => true, 'c.active' => true, 'c.branch_id' => $user_branch_id[0]->branch_id])
                    ->groupBy(
                        DB::raw("DATENAME(month, b.leave_date)"),
                        DB::raw("MONTH(b.leave_date)"),
                        DB::raw("YEAR(b.leave_date)"),
                        'a.employee_id'
                    );
            }

            $coc_details = DB::query()->fromSub($coc_total_hours, 'a');
            $coc = $coc_details
                ->leftjoinSub($cto_total_hours, 'b', function ($join) {
                    $join->on('a.employee_id', '=', 'b.employee_id');
                    $join->on('a.months_num', '=', 'b.months_num');
                    $join->on('a.years', '=', 'b.years');
                })
                ->select(
                    'a.employee_id',
                    'a.name',
                    DB::raw("CONCAT(a.months,', ',a.years) as months"),
                    DB::raw("SUM(isnull(a.total_hours,0)) OVER (PARTITION BY a.employee_id ORDER BY a.months_num) as total_hours"),
                    DB::raw("isnull(b.total_leave_hours,0) as total_leave_hours"),
                    DB::raw("isnull(a.total_hours,0) as carryover"),
                    db::raw("SUM(isnull(a.total_hours,0)) OVER (PARTITION BY a.employee_id ORDER BY a.months_num) - isnull(b.total_leave_hours,0) as remaining_balance"),
                    db::raw("(SUM(isnull(a.total_hours,0)) OVER (PARTITION BY a.employee_id ORDER BY a.months_num) - isnull(b.total_leave_hours,0)) / 8 as converted_days")
                )
                ->orderby('a.name', 'asc')
                ->orderby('a.months_num', 'asc')
                ->orderby('a.years', 'desc')
                ->get();

            // Calculate summary statistics
            $summary = [
                'total_employees' => $coc->unique('employee_id')->count(),
                'total_records' => $coc->count(),
                'service_credit_available' => $Service_Credit,
                'coc_leave_type_id' => $coc_id,
                'date_range' => [
                    'earliest_month' => $coc->min('months'),
                    'latest_month' => $coc->max('months')
                ]
            ];

            return $this->successResponse([
                'coc_details' => $coc,
                'summary' => $summary
            ], 'COC details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve COC details: ' . $e->getMessage());
        }
    }
}
