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

    public function index(Request $request)
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

            // Get month filter from request
            $monthId = $request->input('month_id');
            $year = $request->input('year', date('Y')); // Default to current year

            if (Auth::user()->access_all_branches) {
                $coc_total_hours = DB::table('overtime_applications as a')
                    ->join('employees as c', 'c.id', '=', 'a.employee_id')
                    ->leftJoin('positions as p', 'c.position_id', '=', 'p.id')
                    ->leftJoin('departments as d', 'c.department_id', '=', 'd.id')
                    ->select(
                        'a.employee_id',
                        'c.employee_no',
                        'c.photo',
                        'c.position_id',
                        'c.department_id',
                        // Original (decrypting) name selection kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                        //            CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                        //        ELSE
                        //            RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                        //        END as name"),
                        // Replacement (non-decrypting):
                        DB::raw("CONCAT(c.first_name,' ',UPPER(substring(c.middle_name,1,1)),'. ',c.last_name) as name"),
                        DB::raw('p.name as position'),
                        DB::raw('d.name as department'),
                        DB::raw('d.name as department_name'),
                        DB::raw("DATENAME(month, a.date) as months"),
                        DB::raw("MONTH(a.date) as months_num"),
                        DB::raw("YEAR(a.date) as years"),
                        DB::raw("SUM(isnull(a.total_hours,0)) as total_hours"),
                        db::raw("cast(0 as decimal(18,2)) as carryover")
                    )
                    ->where(function($q) {
                        $q->where('a.approved', 1)
                          ->orWhere('a.approved_2', 1)
                          ->orWhere('a.approved_3', 1);
                    })
                    ->where(function($q) {
                        $q->where('a.disapproved', 0)
                          ->orWhereNull('a.disapproved');
                    })
                    ->where('a.service_credits', 1)
                    ->where('a.payroll', 0)
                    ->where('c.is_employee', true)
                    ->where('c.active', true);
                    
                    // Apply month filter if provided
                    if ($monthId) {
                        $coc_total_hours->whereMonth('a.date', $monthId);
                    }
                    if ($year) {
                        $coc_total_hours->whereYear('a.date', $year);
                    }
                    
                    $coc_total_hours->groupBy(
                        DB::raw("DATENAME(month, a.date)"),
                        DB::raw("MONTH(a.date)"),
                        DB::raw("YEAR(a.date)"),
                        'a.employee_id',
                        'c.employee_no',
                        'c.photo',
                        'c.position_id',
                        'c.department_id',
                        // Original (decrypting) name for GROUP BY kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                        //            CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                        //        ELSE
                        //            RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                        //        END"),
                        // Replacement (non-decrypting):
                        DB::raw("CONCAT(c.first_name,' ',UPPER(substring(c.middle_name,1,1)),'. ',c.last_name)"),
                        DB::raw('p.name'),
                        DB::raw('d.name')
                    );

                // Leave Used from leave_headers + leave_details (day fraction); approved on all levels
                $cto_leave_days = DB::table('leave_headers as a')
                    ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                    ->join('employees as c', 'c.id', '=', 'a.employee_id')
                    ->select(
                        'a.employee_id',
                        DB::raw("DATENAME(month, b.leave_date) as months"),
                        DB::raw("MONTH(b.leave_date) as months_num"),
                        DB::raw("YEAR(b.leave_date) as years"),
                        DB::raw("SUM(isnull(b.with_pay,0)) as total_leave_days")
                    )
                    ->where('a.leave_type_id', $coc_id)
                    ->where('a.approved', 1)
                    ->where('a.approved_2', 1)
                    ->where(function ($q) {
                        $q->where('a.approved_3', 1)->orWhereNull('a.approved_3');
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved', 0)->orWhereNull('a.disapproved');
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved_2', 0)->orWhereNull('a.disapproved_2');
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved_3', 0)->orWhereNull('a.disapproved_3');
                    })
                    ->where(function ($q) {
                        $q->where('a.is_cancel', 0)->orWhereNull('a.is_cancel');
                    })
                    ->where(function ($q) {
                        $q->where('a.is_cancel_2', 0)->orWhereNull('a.is_cancel_2');
                    })
                    ->where(function ($q) {
                        $q->where('a.is_cancel_3', 0)->orWhereNull('a.is_cancel_3');
                    })
                    ->where('c.is_employee', true)
                    ->where('c.active', true);
                if ($monthId) {
                    $cto_leave_days->whereMonth('b.leave_date', $monthId);
                }
                if ($year) {
                    $cto_leave_days->whereYear('b.leave_date', $year);
                }
                $cto_leave_days->groupBy(
                    DB::raw("DATENAME(month, b.leave_date)"),
                    DB::raw("MONTH(b.leave_date)"),
                    DB::raw("YEAR(b.leave_date)"),
                    'a.employee_id'
                );
                $cto_total_hours = $cto_leave_days;
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
                    ->join('employees as c', 'c.id', '=', 'a.employee_id')
                    ->leftJoin('positions as p', 'c.position_id', '=', 'p.id')
                    ->leftJoin('departments as d', 'c.department_id', '=', 'd.id')
                    ->select(
                        'a.employee_id',
                        'c.employee_no',
                        'c.photo',
                        'c.position_id',
                        'c.department_id',
                        // Original (decrypting) name selection kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                        //            CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                        //        ELSE
                        //            RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                        //        END as name"),
                        // Replacement (non-decrypting):
                        DB::raw("CONCAT(c.first_name,' ',UPPER(substring(c.middle_name,1,1)),'. ',c.last_name) as name"),
                        DB::raw('p.name as position'),
                        DB::raw('d.name as department'),
                        DB::raw('d.name as department_name'),
                        DB::raw("DATENAME(month, a.date) as months"),
                        DB::raw("MONTH(a.date) as months_num"),
                        DB::raw("YEAR(a.date) as years"),
                        DB::raw("SUM(isnull(a.total_hours,0)) as total_hours"),
                        db::raw("cast(0 as decimal(18,2)) as carryover")
                    )
                    ->where(function($q) {
                        $q->where('a.approved', 1)
                          ->orWhere('a.approved_2', 1)
                          ->orWhere('a.approved_3', 1);
                    })
                    ->where(function($q) {
                        $q->where('a.disapproved', 0)
                          ->orWhereNull('a.disapproved');
                    })
                    ->where('a.service_credits', 1)
                    ->where('a.payroll', 0)
                    ->where('c.is_employee', true)
                    ->where('c.active', true)
                    ->where('c.branch_id', $user_branch_id[0]->branch_id);
                    
                    // Apply month filter if provided
                    if ($monthId) {
                        $coc_total_hours->whereMonth('a.date', $monthId);
                    }
                    if ($year) {
                        $coc_total_hours->whereYear('a.date', $year);
                    }
                    
                    $coc_total_hours->groupBy(
                        DB::raw("DATENAME(month, a.date)"),
                        DB::raw("MONTH(a.date)"),
                        DB::raw("YEAR(a.date)"),
                        'a.employee_id',
                        'c.employee_no',
                        'c.photo',
                        'c.position_id',
                        'c.department_id',
                        // Original (decrypting) name for GROUP BY kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                        //            CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                        //        ELSE
                        //            RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                        //        END"),
                        // Replacement (non-decrypting):
                        DB::raw("CONCAT(c.first_name,' ',UPPER(substring(c.middle_name,1,1)),'. ',c.last_name)"),
                        DB::raw('p.name'),
                        DB::raw('d.name')
                    );

                // Leave Used from leave_headers + leave_details (day fraction); approved on all levels
                $cto_leave_days = DB::table('leave_headers as a')
                    ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                    ->join('employees as c', 'c.id', '=', 'a.employee_id')
                    ->select(
                        'a.employee_id',
                        DB::raw("DATENAME(month, b.leave_date) as months"),
                        DB::raw("MONTH(b.leave_date) as months_num"),
                        DB::raw("YEAR(b.leave_date) as years"),
                        DB::raw("SUM(isnull(b.with_pay,0)) as total_leave_days")
                    )
                    ->where('a.leave_type_id', $coc_id)
                    ->where('a.approved', 1)
                    ->where('a.approved_2', 1)
                    ->where(function ($q) {
                        $q->where('a.approved_3', 1)->orWhereNull('a.approved_3');
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved', 0)->orWhereNull('a.disapproved');
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved_2', 0)->orWhereNull('a.disapproved_2');
                    })
                    ->where(function ($q) {
                        $q->where('a.disapproved_3', 0)->orWhereNull('a.disapproved_3');
                    })
                    ->where(function ($q) {
                        $q->where('a.is_cancel', 0)->orWhereNull('a.is_cancel');
                    })
                    ->where(function ($q) {
                        $q->where('a.is_cancel_2', 0)->orWhereNull('a.is_cancel_2');
                    })
                    ->where(function ($q) {
                        $q->where('a.is_cancel_3', 0)->orWhereNull('a.is_cancel_3');
                    })
                    ->where('c.is_employee', true)
                    ->where('c.active', true)
                    ->where('c.branch_id', $user_branch_id[0]->branch_id);
                if ($monthId) {
                    $cto_leave_days->whereMonth('b.leave_date', $monthId);
                }
                if ($year) {
                    $cto_leave_days->whereYear('b.leave_date', $year);
                }
                $cto_leave_days->groupBy(
                    DB::raw("DATENAME(month, b.leave_date)"),
                    DB::raw("MONTH(b.leave_date)"),
                    DB::raw("YEAR(b.leave_date)"),
                    'a.employee_id'
                );
                $cto_total_hours = $cto_leave_days;
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
                    'a.employee_no',
                    'a.name',
                    'a.photo',
                    'a.position_id',
                    'a.department_id',
                    'a.position',
                    'a.department',
                    'a.department_name',
                    DB::raw("CONCAT(a.months,', ',a.years) as months"),
                    DB::raw('a.months_num as month_id'),
                    DB::raw('a.years as year'),
                    DB::raw("SUM(isnull(a.total_hours,0)) OVER (PARTITION BY a.employee_id ORDER BY a.months_num) as total_hours"),
                    DB::raw("isnull(b.total_leave_days,0) as total_leave_days"),
                    DB::raw("isnull(a.total_hours,0) as carryover"),
                    db::raw("SUM(isnull(a.total_hours,0)) OVER (PARTITION BY a.employee_id ORDER BY a.months_num) - (isnull(b.total_leave_days,0) * 8) as remaining_balance"),
                    db::raw("(SUM(isnull(a.total_hours,0)) OVER (PARTITION BY a.employee_id ORDER BY a.months_num) - (isnull(b.total_leave_days,0) * 8)) / 8 as converted_days")
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
