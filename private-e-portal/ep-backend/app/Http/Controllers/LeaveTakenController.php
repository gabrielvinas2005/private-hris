<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveTakenController extends Controller
{
    public function index()
    {
        $app_key = env("APP_KEY", "");

        $employees = DB::table('employees as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->select(
                'a.id',
                'a.photo',
                'a.employee_no',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                'b.name as position'
            )
            ->where([
                'a.active' => true,
                'a.is_employee' => true
            ])
            ->orderBy('name', 'asc')
            ->get();

        return $this->successResponse($employees, 'Leave taken list retrieved successfully');
    }

    public function load($id)
    {
        $app_key = env("APP_KEY", "");

        $year = Carbon::now()->format('Y');

        $data = DB::table('employees as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->select(
                'a.id',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                'b.name as position'
            )
            ->where('a.id', $id)
            ->get();

        $leave_available = DB::table('leave_credits as a')
            ->join('leave_types as b', 'a.leave_type_id', '=', 'b.id')
            ->select(
                'b.name as leave_types',
                DB::raw("cast(0 as decimal(18,3)) as leave_taken"),
                'a.credits as leave_balance',
                'b.id as leave_type_id'
            )
            ->where('a.employee_id', $id)
            ->whereNotIn('b.id', function ($query) use ($id, $year) {
                $query->select('a.leave_type_id')->from('leave_headers as a')
                    ->join('leave_details as b', 'b.leave_id', '=', 'a.id')
                    ->where('a.employee_id', $id)
                    ->whereYear('b.leave_date', $year);
            });

        $leaves = DB::table('leave_headers as a')
            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
            ->join('leave_credits as c', function ($join) {
                $join->on('a.employee_id', '=', 'c.employee_id')
                    ->on('a.leave_type_id', '=', 'c.leave_type_id');
            })
            ->join('leave_types as d', 'c.leave_type_id', '=', 'd.id')
            ->select(
                'd.name as leave_types',
                DB::raw("sum(isnull(b.with_pay,0)) as leave_taken"),
                'c.credits as leave_balance',
                'd.id as leave_type_id'
            )
            ->where('a.employee_id', $id)
            ->whereYear('b.leave_date', $year)
            ->groupBy(
                'd.name',
                'c.credits',
                'd.id'
            )
            ->unionAll($leave_available)
            ->get();

        return $this->successResponse([
            'data' => $data,
            'leaves' => $leaves
        ], 'Leave taken data retrieved successfully');
    }
}
