<?php

namespace App\Http\Controllers;

use PDF;
use Auth;
use App\Audit;
use App\Services\LeaveService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
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

    public function index($id)
    {
        $app_key = env("APP_KEY", "");

        $emp_id_data = DB::table('users as a')
            ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
            ->leftJoin('departments as c', 'b.id', '=', 'c.employee_id')
            ->selectRaw('case when b.id = null then 0 else b.id end as id')
            ->selectRaw('case when c.employee_id = null then 0 else c.employee_id end as supervisor_id')
            ->selectRaw('b.gender_id')
            ->where('a.id', $id)
            ->get();

        if ($emp_id_data->isEmpty()) {
            $emp_id = 0;
            $supervisor_id = 0;

            $leave_balances = DB::table('leave_credits as a')
                ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                ->select('b.name as type', 'a.credits as balance')
                ->where('a.employee_id', $emp_id)
                ->where('b.active', true)
                ->get();

            $leaves = [];
            $leave_for_approvals = [];
        } else {
            $emp_id = $emp_id_data[0]->id;

            if ($emp_id_data[0]->gender_id == 1) {
                $leave_balances = DB::table('leave_credits as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->select('b.name as type', 'a.credits as balance')
                    ->where('a.employee_id', $emp_id)
                    ->where('b.name', '<>', 'Paternity Leave')
                    ->where('b.active', true)
                    ->get();
            } else {
                $leave_balances = DB::table('leave_credits as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->select('b.name as type', 'a.credits as balance')
                    ->where('a.employee_id', $emp_id)
                    ->where('b.name', '<>', 'Maternity Leave')
                    ->where('b.active', true)
                    ->get();
            }

            $leaves = DB::table('leave_headers as a')
                ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                ->leftJoin('employees as c', 'c.id', '=', 'a.canceled_by')
                ->leftJoin('employees as d', 'd.id', '=', 'a.processed_by')
                ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
                ->leftJoin('employees as f', 'f.id', '=', 'a.processed_by_3')
                ->leftJoin('employees as c1', 'c1.id', '=', 'a.canceled_by_2')
                ->leftJoin('employees as c2', 'c2.id', '=', 'a.canceled_by_3')
                ->select(
                    'a.id',
                    'b.name as leave_type',
                    'b.id as leave_type_id',
                    DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                    DB::raw("CONCAT(CONVERT(NVARCHAR(50),a.date_from,110),' - ',CONVERT(NVARCHAR(50),a.date_to,110)) as date_covered"),
                    'a.reason',
                    'a.remarks',
                    'a.approved',
                    'a.approved_2',
                    'a.approved_3',
                    'a.disapproved',
                    'a.disapproved_2',
                    'a.disapproved_3',
                    'a.processed_date',
                    'a.is_cancel',
                    'a.is_cancel_2',
                    'a.canceled_by',
                    'a.canceled_date',
                    'a.canceled_remarks',
                    'a.canceled_by_2',
                    'a.canceled_date_2',
                    'a.canceled_remarks_2',
                    'a.attachment_name',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as cancelled_by_name"),
                    DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) 
                            END as cancelled_by_name_2"),
                    DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                            CONCAT(c2.first_name,' ',c2.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) 
                        END as cancelled_by_name_3"),
                    DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN
                                CONCAT(d.first_name,' ',d.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key')) 
                            END as approver_1"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CONCAT(e.first_name,' ',e.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) 
                            END as approver_2"),
                    DB::raw("CASE WHEN ISNULL(f.is_encrypted,0) = 0 THEN
                                CONCAT(f.first_name,' ',f.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](f.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](f.last_name,'$app_key')) 
                            END as approver_3"),
                    'a.processed_date_2',
                    'a.processed_date_3',
                    'a.is_cancel_3',
                    'a.canceled_by_3',
                    'a.canceled_date_3',
                    'a.canceled_remarks_3'
                )
                ->where('a.employee_id', $emp_id)
                ->orderBy('a.date_to', 'desc')
                ->get();

            // Check if Approver Start
            $approver_1 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.branch_approver_id_1 as supervisor_id'
                )
                ->where('a.branch_approver_id_1', $emp_id)
                ->orWhere('a.approver_id_1', $emp_id)
                ->orWhere('a.division_approver_id_1', $emp_id)
                ->orWhere('a.section_approver_id_1', $emp_id)
                ->distinct()
                ->get();

            $approver_2 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_2 as supervisor_id'
                )
                ->where('a.approver_id_2', $emp_id)
                ->distinct()
                ->get();

            $approver_3 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_3 as supervisor_id'
                )
                ->where('a.approver_id_3', $emp_id)
                ->distinct()
                ->get();

            $approver_4 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_4 as supervisor_id'
                )
                ->where('a.approver_id_4', $emp_id)
                ->distinct()
                ->get();

            $leave_for_approvals = [];

            if ($approver_1->isNotEmpty() && $approver_2->isEmpty()) { // Approver 1 only
                $supervisor_id = true;

                $leave_for_approvals = DB::table('leave_headers as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->join('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id');
                        $join->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                        'b.name as leave_type',
                        'b.id as leave_type_id',
                        DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
                        DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                        DB::raw("CONCAT(CONVERT(NVARCHAR(50),a.date_from,110),' - ',CONVERT(NVARCHAR(50),a.date_to,110)) as date_covered"),
                        'a.reason',
                        'a.remarks',
                        'a.approved',
                        'a.disapproved',
                        'a.processed_date',
                        'a.processed_by',
                        'a.approved_2',
                        'a.disapproved_2',
                        'a.processed_date_2',
                        'a.processed_by_2',
                        'a.is_cancel',
                        'a.canceled_by',
                        'a.canceled_date',
                        'a.canceled_remarks',
                        'a.is_cancel_2',
                        'a.canceled_by_2',
                        'a.canceled_date_2',
                        'a.canceled_remarks_2',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(1 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) 
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) 
                            END as cancel_2")
                    )
                    ->whereNotIn('c.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                            ->whereRaw("
                                (ISNULL(c.approved,0) = 0 OR ISNULL(c.approved,0) = 1)
                                AND (b.branch_approver_id_1 = $emp_id OR b.approver_id_1  = $emp_id or b.division_approver_id_1 = $emp_id or b.section_approver_id_1 = $emp_id)
                            ");
                    })
                    ->distinct()
                    ->orderBy('a.date_to', 'desc')
                    ->get();
            } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) { // Approver 2 only
                $supervisor_id = true;

                $leave_for_approvals = DB::table('leave_headers as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->join('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id');
                        $join->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                        'b.name as leave_type',
                        'b.id as leave_type_id',
                        DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
                        DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                        DB::raw("CONCAT(CONVERT(NVARCHAR(50),a.date_from,110),' - ',CONVERT(NVARCHAR(50),a.date_to,110)) as date_covered"),
                        'a.reason',
                        'a.remarks',
                        'a.approved',
                        'a.disapproved',
                        'a.processed_date',
                        'a.processed_by',
                        'a.approved_2',
                        'a.disapproved_2',
                        'a.processed_date_2',
                        'a.processed_by_2',
                        'a.is_cancel',
                        'a.canceled_by',
                        'a.canceled_date',
                        'a.canceled_remarks',
                        'a.is_cancel_2',
                        'a.canceled_by_2',
                        'a.canceled_date_2',
                        'a.canceled_remarks_2',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(2 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) 
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) 
                            END as cancel_2")
                    )
                    ->whereNotIn('c.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                            ->whereRaw("
                                c.approved = 1 OR (c.is_cancel = 1 OR c.is_cancel_2 = 1) AND b.approver_id_2 = $emp_id
                            ");
                    })
                    ->distinct()
                    ->orderBy('a.date_to', 'desc')
                    ->get();
            } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) { // Both Approver 1 and 2
                $supervisor_id = true;

                $leave_for_approvals_1 = DB::table('leave_headers as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->join('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id')
                            ->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                        'b.name as leave_type',
                        'b.id as leave_type_id',
                        DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
                        DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                        DB::raw("CONCAT(CONVERT(NVARCHAR(50),a.date_from,110),' - ',CONVERT(NVARCHAR(50),a.date_to,110)) as date_covered"),
                        'a.reason',
                        'a.remarks',
                        'a.approved',
                        'a.disapproved',
                        'a.processed_date',
                        'a.processed_by',
                        'a.approved_2',
                        'a.disapproved_2',
                        'a.processed_date_2',
                        'a.processed_by_2',
                        'a.is_cancel',
                        'a.canceled_by',
                        'a.canceled_date',
                        'a.canceled_remarks',
                        'a.is_cancel_2',
                        'a.canceled_by_2',
                        'a.canceled_date_2',
                        'a.canceled_remarks_2',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(2 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) 
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) 
                            END as cancel_2")
                    )
                    ->whereNotIn('c.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                            ->whereRaw("
                                (ISNULL(c.approved,0) = 0 OR ISNULL(c.approved,0) = 1)
                                AND (b.branch_approver_id_1 = $emp_id OR b.approver_id_1  = $emp_id or b.division_approver_id_1 = $emp_id or b.section_approver_id_1 = $emp_id)
                            ");
                    })
                    ->distinct();

                $leave_for_approvals = DB::table('leave_headers as a')
                    ->join(
                        'leave_types as b',
                        'b.id',
                        '=',
                        'a.leave_type_id'
                    )
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->join('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id')
                            ->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                        'b.name as leave_type',
                        'b.id as leave_type_id',
                        DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
                        DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                        DB::raw("CONCAT(CONVERT(NVARCHAR(50),a.date_from,110),' - ',CONVERT(NVARCHAR(50),a.date_to,110)) as date_covered"),
                        'a.reason',
                        'a.remarks',
                        'a.approved',
                        'a.disapproved',
                        'a.processed_date',
                        'a.processed_by',
                        'a.approved_2',
                        'a.disapproved_2',
                        'a.processed_date_2',
                        'a.processed_by_2',
                        'a.is_cancel',
                        'a.canceled_by',
                        'a.canceled_date',
                        'a.canceled_remarks',
                        'a.is_cancel_2',
                        'a.canceled_by_2',
                        'a.canceled_date_2',
                        'a.canceled_remarks_2',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(2 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) 
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) 
                            END as cancel_2")
                    )
                    ->whereNotIn('c.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                            ->whereRaw("
                                c.approved = 1 OR (c.is_cancel = 1 OR c.is_cancel_2 = 1) AND b.approver_id_2 = $emp_id
                            ");
                    })
                    ->distinct()
                    ->union($leave_for_approvals_1)
                    ->orderBy('date_to', 'desc')
                    ->get();
            } elseif ($approver_3->isNotEmpty()) {
                $supervisor_id = true;

                $leave_for_approvals_1 = DB::table('leave_headers as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->join('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id')
                            ->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                        'b.name as leave_type',
                        'b.id as leave_type_id',
                        DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
                        DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                        DB::raw("CONCAT(CONVERT(NVARCHAR(50),a.date_from,110),' - ',CONVERT(NVARCHAR(50),a.date_to,110)) as date_covered"),
                        'a.reason',
                        'a.remarks',
                        'a.approved',
                        'a.disapproved',
                        'a.processed_date',
                        'a.processed_by',
                        'a.approved_2',
                        'a.disapproved_2',
                        'a.processed_date_2',
                        'a.processed_by_2',
                        'a.approved_3',
                        'a.disapproved_3',
                        'a.processed_date_3',
                        'a.processed_by_3',
                        'a.is_cancel',
                        'a.canceled_by',
                        'a.canceled_date',
                        'a.canceled_remarks',
                        'a.is_cancel_2',
                        'a.canceled_by_2',
                        'a.canceled_date_2',
                        'a.canceled_remarks_2',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(3 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) 
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) 
                            END as cancel_2"),
                        'a.is_force_leave',
                        'a.is_cancel_3',
                        'a.canceled_by_3',
                        'a.canceled_date_3',
                        'a.canceled_remarks_3'
                    )
                    ->whereNotIn('c.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                            ->whereRaw("
                                (ISNULL(c.approved,0) = 0 OR ISNULL(c.approved,0) = 1)
                                AND (b.branch_approver_id_1 = $emp_id OR b.approver_id_1  = $emp_id or b.division_approver_id_1 = $emp_id or b.section_approver_id_1 = $emp_id)
                            ");
                    })
                    ->whereIn('a.id', function ($query) {
                        $query->select('a.id')->from('leave_headers as a')
                            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                            ->groupBy('a.id')
                            ->having(DB::raw('count(b.id)'), '<', 4);
                    })
                    ->distinct();

                $leave_for_approvals_2 = DB::table('leave_headers as a')
                    ->join(
                        'leave_types as b',
                        'b.id',
                        '=',
                        'a.leave_type_id'
                    )
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->join('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id')
                            ->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                        'b.name as leave_type',
                        'b.id as leave_type_id',
                        DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
                        DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                        DB::raw("CONCAT(CONVERT(NVARCHAR(50),a.date_from,110),' - ',CONVERT(NVARCHAR(50),a.date_to,110)) as date_covered"),
                        'a.reason',
                        'a.remarks',
                        'a.approved',
                        'a.disapproved',
                        'a.processed_date',
                        'a.processed_by',
                        'a.approved_2',
                        'a.disapproved_2',
                        'a.processed_date_2',
                        'a.processed_by_2',
                        'a.approved_3',
                        'a.disapproved_3',
                        'a.processed_date_3',
                        'a.processed_by_3',
                        'a.is_cancel',
                        'a.canceled_by',
                        'a.canceled_date',
                        'a.canceled_remarks',
                        'a.is_cancel_2',
                        'a.canceled_by_2',
                        'a.canceled_date_2',
                        'a.canceled_remarks_2',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(3 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) 
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) 
                            END as cancel_2"),
                        'a.is_force_leave',
                        'a.is_cancel_3',
                        'a.canceled_by_3',
                        'a.canceled_date_3',
                        'a.canceled_remarks_3'
                    )
                    ->whereNotIn('c.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                            ->whereRaw("
                                c.approved = 1 OR (c.is_cancel = 1 OR c.is_cancel_2 = 1) AND b.approver_id_2 = $emp_id
                            ");
                    })
                    ->whereIn('a.id', function ($query) {
                        $query->select('a.id')->from('leave_headers as a')
                            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                            ->groupBy('a.id')
                            ->having(DB::raw('count(b.id)'), '<', 4);
                    })
                    ->distinct();

                $leave_for_approvals = DB::table('leave_headers as a')
                    ->join(
                        'leave_types as b',
                        'b.id',
                        '=',
                        'a.leave_type_id'
                    )
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->join('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id')
                            ->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                        'b.name as leave_type',
                        'b.id as leave_type_id',
                        DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
                        DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                        DB::raw("CONCAT(CONVERT(NVARCHAR(50),a.date_from,110),' - ',CONVERT(NVARCHAR(50),a.date_to,110)) as date_covered"),
                        'a.reason',
                        'a.remarks',
                        'a.approved',
                        'a.disapproved',
                        'a.processed_date',
                        'a.processed_by',
                        'a.approved_2',
                        'a.disapproved_2',
                        'a.processed_date_2',
                        'a.processed_by_2',
                        'a.approved_3',
                        'a.disapproved_3',
                        'a.processed_date_3',
                        'a.processed_by_3',
                        'a.is_cancel',
                        'a.canceled_by',
                        'a.canceled_date',
                        'a.canceled_remarks',
                        'a.is_cancel_2',
                        'a.canceled_by_2',
                        'a.canceled_date_2',
                        'a.canceled_remarks_2',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(3 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) 
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) 
                            END as cancel_2"),
                        'a.is_force_leave',
                        'a.is_cancel_3',
                        'a.canceled_by_3',
                        'a.canceled_date_3',
                        'a.canceled_remarks_3'
                    )
                    ->whereNotIn('c.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                            ->whereRaw("
                                c.approved = 1 and c.approved_2 = 1 OR (c.is_cancel = 1 OR c.is_cancel_2 = 1) AND b.approver_id_3 = $emp_id
                            ");
                    })
                    ->whereIn('a.id', function ($query) {
                        $query->select('a.id')->from('leave_headers as a')
                            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                            ->groupBy('a.id')
                            ->having(DB::raw('count(b.id)'), '<', 4);
                    })
                    ->distinct()
                    ->union($leave_for_approvals_1)
                    ->union($leave_for_approvals_2)
                    ->orderBy('date_to', 'desc')
                    ->get();
            } elseif ($approver_4->isNotEmpty()) {
                $supervisor_id = true;

                $leave_for_approvals_1 = DB::table('leave_headers as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->join('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id')
                            ->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                        'b.name as leave_type',
                        'b.id as leave_type_id',
                        DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
                        DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                        DB::raw("CONCAT(CONVERT(NVARCHAR(50),a.date_from,110),' - ',CONVERT(NVARCHAR(50),a.date_to,110)) as date_covered"),
                        'a.reason',
                        'a.remarks',
                        'a.approved',
                        'a.disapproved',
                        'a.processed_date',
                        'a.processed_by',
                        'a.approved_2',
                        'a.disapproved_2',
                        'a.processed_date_2',
                        'a.processed_by_2',
                        'a.approved_3',
                        'a.disapproved_3',
                        'a.processed_date_3',
                        'a.processed_by_3',
                        'a.is_cancel',
                        'a.canceled_by',
                        'a.canceled_date',
                        'a.canceled_remarks',
                        'a.is_cancel_2',
                        'a.canceled_by_2',
                        'a.canceled_date_2',
                        'a.canceled_remarks_2',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(3 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) 
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) 
                            END as cancel_2"),
                        'a.is_force_leave',
                        'a.is_cancel_3',
                        'a.canceled_by_3',
                        'a.canceled_date_3',
                        'a.canceled_remarks_3'
                    )
                    ->whereNotIn('c.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                            ->whereRaw("
                                (ISNULL(c.approved,0) = 0 OR ISNULL(c.approved,0) = 1)
                                AND (b.branch_approver_id_1 = $emp_id OR b.approver_id_1  = $emp_id or b.division_approver_id_1 = $emp_id or b.section_approver_id_1 = $emp_id)
                            ");
                    })
                    ->whereIn('a.id', function ($query) {
                        $query->select('a.id')->from('leave_headers as a')
                            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                            ->groupBy('a.id')
                            ->having(DB::raw('count(b.id)'), '>', 3);
                    })
                    ->distinct();

                $leave_for_approvals_2 = DB::table('leave_headers as a')
                    ->join(
                        'leave_types as b',
                        'b.id',
                        '=',
                        'a.leave_type_id'
                    )
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->join('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id')
                            ->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                        'b.name as leave_type',
                        'b.id as leave_type_id',
                        DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
                        DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                        DB::raw("CONCAT(CONVERT(NVARCHAR(50),a.date_from,110),' - ',CONVERT(NVARCHAR(50),a.date_to,110)) as date_covered"),
                        'a.reason',
                        'a.remarks',
                        'a.approved',
                        'a.disapproved',
                        'a.processed_date',
                        'a.processed_by',
                        'a.approved_2',
                        'a.disapproved_2',
                        'a.processed_date_2',
                        'a.processed_by_2',
                        'a.approved_3',
                        'a.disapproved_3',
                        'a.processed_date_3',
                        'a.processed_by_3',
                        'a.is_cancel',
                        'a.canceled_by',
                        'a.canceled_date',
                        'a.canceled_remarks',
                        'a.is_cancel_2',
                        'a.canceled_by_2',
                        'a.canceled_date_2',
                        'a.canceled_remarks_2',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(3 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) 
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) 
                            END as cancel_2"),
                        'a.is_force_leave',
                        'a.is_cancel_3',
                        'a.canceled_by_3',
                        'a.canceled_date_3',
                        'a.canceled_remarks_3'
                    )
                    ->whereNotIn('c.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                            ->whereRaw("
                                c.approved = 1 OR (c.is_cancel = 1 OR c.is_cancel_2 = 1) AND b.approver_id_2 = $emp_id
                            ");
                    })
                    ->whereIn('a.id', function ($query) {
                        $query->select('a.id')->from('leave_headers as a')
                            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                            ->groupBy('a.id')
                            ->having(DB::raw('count(b.id)'), '>', 3);
                    })
                    ->distinct();

                $leave_for_approvals = DB::table('leave_headers as a')
                    ->join(
                        'leave_types as b',
                        'b.id',
                        '=',
                        'a.leave_type_id'
                    )
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->join('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id')
                            ->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                        'b.name as leave_type',
                        'b.id as leave_type_id',
                        DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
                        DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                        DB::raw("CONCAT(CONVERT(NVARCHAR(50),a.date_from,110),' - ',CONVERT(NVARCHAR(50),a.date_to,110)) as date_covered"),
                        'a.reason',
                        'a.remarks',
                        'a.approved',
                        'a.disapproved',
                        'a.processed_date',
                        'a.processed_by',
                        'a.approved_2',
                        'a.disapproved_2',
                        'a.processed_date_2',
                        'a.processed_by_2',
                        'a.approved_3',
                        'a.disapproved_3',
                        'a.processed_date_3',
                        'a.processed_by_3',
                        'a.is_cancel',
                        'a.canceled_by',
                        'a.canceled_date',
                        'a.canceled_remarks',
                        'a.is_cancel_2',
                        'a.canceled_by_2',
                        'a.canceled_date_2',
                        'a.canceled_remarks_2',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(3 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) 
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) 
                            END as cancel_2"),
                        'a.is_force_leave',
                        'a.is_cancel_3',
                        'a.canceled_by_3',
                        'a.canceled_date_3',
                        'a.canceled_remarks_3'
                    )
                    ->whereNotIn('c.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                            ->whereRaw("
                                c.approved = 1 and c.approved_2 = 1 OR (c.is_cancel = 1 OR c.is_cancel_2 = 1) AND b.approver_id_4 = $emp_id
                            ");
                    })
                    ->whereIn('a.id', function ($query) {
                        $query->select('a.id')->from('leave_headers as a')
                            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                            ->groupBy('a.id')
                            ->having(DB::raw('count(b.id)'), '>', 3);
                    })
                    ->distinct()
                    ->union($leave_for_approvals_1)
                    ->union($leave_for_approvals_2)
                    ->orderBy('date_to', 'desc')
                    ->get();
            } else {
                $supervisor_id = false;
                $leave_for_approvals = [];
            }
        }

        // Check if With Approver.
        $with_approvers = DB::table('approver_details')->where('employee_id', $emp_id)->get();

        if (count($with_approvers) > 0) {
            $allowed = 1;
        } else {
            $allowed = 0;
        }

        // trigger auto-approved leave
        // (new LeaveService)->autoApproved();

        return $this->successResponse([
            'employee_id' => $emp_id,
            'supervisor_id' => $supervisor_id,
            'leave_balances' => $leave_balances,
            'leaves' => $leaves,
            'leave_for_approvals' => $leave_for_approvals,
            'permissions' => [
                'can_approve' => $allowed
            ]
        ], 'Leave data retrieved successfully');
    }

    public function add($id, $view)
    {
        $emp_id_data = DB::table('users as a')
            ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
            ->selectRaw('case when b.id = null then 0 else b.id end as id')
            ->selectRaw('b.gender_id')
            ->where('a.id', Auth::user()->id)
            ->get();

        if ($emp_id_data[0]->gender_id == 1) {
            $leave_types = DB::table('leave_types')->where('active', true)->where('name', '<>', 'Paternity Leave')->get();
        } else {
            $leave_types = DB::table('leave_types')->where('active', true)->where('name', '<>', 'Maternity Leave')->get();
        }

        if ($id == 0) {
            $dummy_leave_info = array(
                'id' => 0,
                'employee_id' => 0,
                'leave_type_id' => 0,
                'day_type_id' => 0,
                'date_from' => null,
                'date_to' => null,
                'reason' => null,
                'incase_vacation_leave_id' => 0,
                'incase_vacation_leave_specify' => null,
                'incase_sick_leave_id' => 0,
                'incase_sick_leave_specify' => null,
                'incase_special_leave_specify' => null,
                'incase_study_leave_id' => 0,
                'other_purpose_id' => 0,
                'commutation_id' => 0,
                'is_force_leave' => false,
            );
            $leave_info = (object)$dummy_leave_info;
            $leave_info = collect([$leave_info]);

            $dummy_leave_details = array(
                'leave_id' => 0,
                'leave_date' => null,
                'with_pay' => null,
                'without_pay' => null,
            );

            $leave_details = (object)$dummy_leave_details;
            $leave_details = collect([$leave_details]);
        } else {
            $leave_info = DB::table('leave_headers')->where('id', $id)->get();
            $leave_details = DB::table('leave_details')->where('leave_id', $id)->get();
        }

        return $this->successResponse([
            'leave_types' => $leave_types,
            'leave_info' => $leave_info,
            'employee_data' => $emp_id_data,
            'leave_details' => $leave_details,
            'view_mode' => $view
        ], 'Leave application form data retrieved successfully');
    }

    public function store(Request $request, $id)
    {
        try {
            $validate = Validator::make($request->all(), [
            'date_from' => 'date',
            'date_to' => 'date'
        ], [
            'date_from.date' => 'Date From must be valid date format (MM/DD/YYYY).',
            'date_to.date' => 'Date To must be valid date format (MM/DD/YYYY).',
        ]);

        if ($validate->fails()) {
            return $this->validationErrorResponse($validate->errors());
        }

        $data = array(
            'employee_id' => $request->employee_id,
            'leave_type_id' => $request->leave_type_id,
            'day_type_id' => $request->day_type_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'reason' => $request->reason,
            'incase_vacation_leave_id' => $request->incase_vacation_leave_id,
            'incase_vacation_leave_specify' => $request->incase_vacation_leave_specify,
            'incase_sick_leave_id' => $request->incase_sick_leave_id,
            'incase_sick_leave_specify' => $request->incase_sick_leave_specify,
            'incase_special_leave_specify' => $request->incase_special_leave_specify,
            'incase_study_leave_id' => $request->incase_study_leave_id,
            'other_purpose_id' => $request->other_purpose_id,
            'commutation_id' => $request->commutation_id,
            'is_force_leave' => $request->has('is_force_leave') ? true : false,
        );

        // get credit balance
        $credit_balance = DB::table('leave_credits')->where(['leave_type_id' => $request->leave_type_id, 'employee_id' => $request->employee_id])->get();

        if (count($credit_balance) == 0) {
            $credits = 0;
        } else {
            if ($credit_balance[0]->credits < 0.5) {
                $credits = 0;
            } else {
                $credits = $credit_balance[0]->credits;
            }
        }

        $leave_maximum_availment_per_year = DB::table('leave_types')->where([
            'leave_balance_policy_id' => 3,
            'id' => $request->leave_type_id
        ])->get();

        if ($leave_maximum_availment_per_year->isNotEmpty()) {
            // check if reach limit
            $leave_allowed = isset($leave_maximum_availment_per_year[0]->accrual_amount) ? $leave_maximum_availment_per_year[0]->accrual_amount : 0;

            $aplied_leaves = DB::table('leave_headers as a')
                ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                ->where([
                    'a.employee_id' => $request->employee_id,
                    'a.leave_type_id' => $request->leave_type_id
                ])
                ->whereRaw("YEAR(b.leave_date) = YEAR(GETDATE())")
                ->where([
                    'disapproved' => false,
                    'disapproved_2' => false,
                    'disapproved_3' => false,
                    'is_cancel' => false,
                    'is_cancel_2' => false,
                    'is_cancel_3' => false,
                ])
                ->count();

            // $to_apply_leaves = (new DateTime($request->date_from))->diff(new DateTime($request->date_to))->days + 1;

            if ($leave_allowed <= ($aplied_leaves)) {
                return $this->errorResponse('Failed to Apply Leave. Maximum leave availment for this year has been reached. You are not allowed to apply this leave.');
            }
        }

        if ($request->leave_type_id == 26 && $credits <= 0) {
            return $this->errorResponse('Maximum Paternity leave has been used for this year. You are not allowed to apply this leave.');
        }

        if ($request->leave_type_id == 7 && $credits <= 0) {
            return $this->errorResponse('Maximum Maternity leave has been used for this year. You are not allowed to apply this leave.');
        }

        $date_from = Carbon::parse($request->date_from);
        $date_to = Carbon::parse($request->date_to);
        $date = Carbon::parse($request->date_from);
        $date = Carbon::parse($date);

        // delete detail data first
        DB::table('leave_details')->where('leave_id', '=', $id)->delete();

        $leave_detail_data = [];

        // get unavailable dates
        $employee = db::table('employees')->select('branch_id', 'work_schedule_id')->where('id', $request->employee_id)->get();

        if ($employee->isEmpty()) {
            $employee = db::table('branches')->select('id as branch_id', db::raw("cast(1 as int) as work_schedule_id"))->where('is_main_branch', true)->get();
        }

        $applied_leaves = DB::table('leave_headers as a')
            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
            ->select(
                db::raw("CONVERT(NVARCHAR(50),DATEPART(D,b.leave_date))+'-'+CONVERT(NVARCHAR(50),DATEPART(M,b.leave_date))+'-'+CONVERT(NVARCHAR(50),DATEPART(YEAR,b.leave_date)) as un_date")
            )
            ->where('a.employee_id', $request->employee_id)
            ->where('a.is_cancel', false)
            ->where('a.is_cancel_2', false);

        $un_dates = DB::table('holidays as c')
            ->select(
                db::raw("CONVERT(NVARCHAR(50),DATEPART(D,c.date))+'-'+CONVERT(NVARCHAR(50),DATEPART(M,c.date))+'-'+CONVERT(NVARCHAR(50),DATEPART(YEAR,c.date)) as un_date")
            )
            ->where('c.active', true)
            ->whereIn('c.branch', [0, $employee[0]->branch_id])
            ->unionAll($applied_leaves)
            ->orderBy('un_date', 'asc')
            ->get();

        $data_un_date = [];

        foreach ($un_dates as $un) {
            array_push($data_un_date, date("d-m-Y", strtotime($un->un_date)));
        }

        $ctr = 0;

        while ($date <= $date_to) {
            if (in_array(date("d-m-Y", strtotime($date)), $data_un_date, true) == false) {
                if (date('w', strtotime($date)) != 6 && date('w', strtotime($date)) != 0) {

                    // insert or update leave headers
                    if ($id == 0) {
                        DB::table('leave_headers')->insert($data);
                        $id = DB::table('leave_headers')->max('id');
                    } else {
                        DB::table('leave_headers')->updateOrInsert(['id' => $id], $data);
                    }

                    $ctr = $ctr + 1;

                    if ($credits == 0) {
                        $leave_detail_data = [
                            'leave_id' => $id,
                            'leave_date' => date("Y-m-d", strtotime("$date")),
                            'with_pay' => 0,
                            'without_pay' => $request->day_type_id == 1 ? 1 : 0.5,
                        ];

                        $day_type_value = 0;
                    } else {

                        if ($request->day_type_id == 1) {
                            $day_type_value = 1;
                        } else {
                            $day_type_value = 0.5;
                        }

                        if ($credits >= $day_type_value) {
                            if ($credits < 1) {
                                $leave_detail_data = [
                                    'leave_id' => $id,
                                    'leave_date' => date("Y-m-d", strtotime("$date")),
                                    'with_pay' => $day_type_value,
                                    'without_pay' => $day_type_value,
                                ];
                            } else {
                                $leave_detail_data = [
                                    'leave_id' => $id,
                                    'leave_date' => date("Y-m-d", strtotime("$date")),
                                    'with_pay' => $day_type_value,
                                    'without_pay' => 0,
                                ];
                            }
                        } else {

                            if ($day_type_value == 1 && ($credits < 1 && $credits >= 0.5)) {

                                $day_type_value = 0.5;

                                $leave_detail_data = [
                                    'leave_id' => $id,
                                    'leave_date' => date("Y-m-d", strtotime("$date")),
                                    'with_pay' => $day_type_value,
                                    'without_pay' => $day_type_value,
                                ];
                            } else {
                                if ($day_type_value == 0.5 && $credits < 0.5) {
                                    $leave_detail_data = [
                                        'leave_id' => $id,
                                        'leave_date' => date("Y-m-d", strtotime("$date")),
                                        'with_pay' => 0,
                                        'without_pay' => 1,
                                    ];

                                    $day_type_value = 1;
                                } else {
                                    $leave_detail_data = [
                                        'leave_id' => $id,
                                        'leave_date' => date("Y-m-d", strtotime("$date")),
                                        'with_pay' => 0,
                                        'without_pay' => 1,
                                    ];

                                    $day_type_value = 1;
                                }
                            }
                        }
                    }

                    // insert leave details
                    DB::table('leave_details')->Insert($leave_detail_data);
                    $credits = $credits - $day_type_value;
                }
            }
            $date->addDay(1);
        }

        if ($ctr == 0) {
            return response()->json(['error' => 'Invalid Leave Application.'], 500);
        }

        // Save Leave Attachments
        if ($request->hasFile('attachments')) {

            $allowedfileExtension = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xlsx', 'xls'];
            $files = $request->file('attachments');
            $ctr = 0;

            foreach ($files as $file) {
                $file_name = $file->getClientOriginalName();
                $file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'leave_attachments\\' . 'LV' . $data['employee_id'] . '_' . $file_name;
                $extension = $file->getClientOriginalExtension();
                $check = in_array($extension, $allowedfileExtension);

                if ($check) {
                    // Save record of attachments to database.
                    $leave_attachment_data = [
                        'leave_id' => $id,
                        'attachment_name' => $file_name,
                        'attachment_path' => $file_path
                    ];

                    DB::table('leave_attachments')->insert($leave_attachment_data);

                    // Save attachment to path.
                    $request->attachments[$ctr]->storeAs('leave_attachments', 'LV' . $data['employee_id'] . '_' . $file_name);
                    $ctr++;
                }
            }
        }

        //Save audit trail
        if ($id == 0) {
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Leave Application',
                'activity' => 'Add',
                'description' => 'Added Leave Application.',
            );
        } else {
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Leave Application',
                'activity' => 'Update',
                'description' => 'Updated Leave Application.',
            );
        }

        Audit::create($data_audit);

        return $this->successResponse(['id' => $id], 'Leave applied successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to apply leave: ' . $e->getMessage());
        }
    }

    public function leaveValidation($leave_id, $employee_id, $date_from, $date_to)
    {
        try {
            // get credit balance
        $credit_balance = DB::table('leave_credits')->where(['leave_type_id' => $leave_id, 'employee_id' => $employee_id])->get();

        if (count($credit_balance) == 0) {
            $credits = 0;
        } else {
            if ($credit_balance[0]->credits < 0.5) {
                $credits = 0;
            } else {
                $credits = $credit_balance[0]->credits;
            }
        }

        $date_from = Carbon::parse($date_from);
        $date_to = Carbon::parse($date_to);
        $date = Carbon::parse($date_from);
        $date = Carbon::parse($date);

        // get unavailable dates
        $employee = db::table('employees')->select('branch_id', 'work_schedule_id')->where('id', $employee_id)->get();

        if ($employee->isEmpty()) {
            $employee = db::table('branches')->select('id as branch_id', db::raw("cast(1 as int) as work_schedule_id"))->where('is_main_branch', true)->get();
        }

        // $applied_leaves = DB::table('leave_headers as a')
        //     ->join(
        //         'leave_details as b',
        //         'a.id',
        //         '=',
        //         'b.leave_id'
        //     )
        //     ->select(
        //         db::raw("CONVERT(NVARCHAR(50),DATEPART(D,b.leave_date))+'-'+CONVERT(NVARCHAR(50),DATEPART(M,b.leave_date))+'-'+CONVERT(NVARCHAR(50),DATEPART(YEAR,b.leave_date)) as un_date")
        //     )
        //     ->where('a.employee_id', $employee_id)
        //     ->where(db::raw("isnull(a.is_cancel,0)"), false)
        //     ->where('a.disapproved', false);

        $un_dates = DB::table('holidays as c')
            ->select(
                db::raw("CONVERT(NVARCHAR(50),DATEPART(D,c.date))+'-'+CONVERT(NVARCHAR(50),DATEPART(M,c.date))+'-'+CONVERT(NVARCHAR(50),DATEPART(YEAR,c.date)) as un_date")
            )
            ->where([
                'c.active' => true
            ])
            ->whereIn('c.branch', [$employee[0]->branch_id, 0])
            ->orderBy('un_date', 'asc')
            ->get();

        $data_un_date = [];
        $applied_days = 0;

        foreach ($un_dates as $un) {
            array_push($data_un_date, date("d-m-Y", strtotime($un->un_date)));
        }

        while ($date <= $date_to) {
            if (in_array(date("d-m-Y", strtotime($date)), $data_un_date, true) == false) {
                if (date('w', strtotime($date)) != 6 && date('w', strtotime($date)) != 0) {
                    $applied_days = $applied_days + 1;
                }
            } # code...
            $date->addDay(1);
        }

        if ($credits < $applied_days) {
            $data = 1;
        } else {
            $data = 0;
        }

        // $data = date("d-m-Y", strtotime($date_from)) . ' ' . $credits . ' ' . $applied_days . ' ' . $data . ' from ' . $date_from . ' to ' . $date_to . ' ' . $data_un_date;

        return $this->successResponse($data, 'Leave validation completed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to validate leave: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('leave_headers')->where('id', $id)->delete();
        DB::table('leave_details')->where('leave_id', $id)->delete();

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Timekeeping Module',
            'menu'    => 'Leave Application',
            'activity' => 'Delete',
            'description' => 'Deleted leave informations.',
        );

        Audit::create($data_audit);

        return $this->successResponse(null, 'Leave deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete leave: ' . $e->getMessage());
        }
    }

    public function process($id, $process_id, $remarks)
    {

        $emp_id_data = DB::table('users as a')
            ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
            ->selectRaw('case when b.id = null then 0 else b.id end as id')
            ->where('a.id', Auth::user()->id)
            ->get();

        $leave_headers = DB::table('leave_headers')->where('id', $id)->get();

        DB::table('leave_headers')->where('id', $id)->update(['remarks' => $remarks]);

        // get credit balance
        $credit_balance = DB::table('leave_credits')
            ->where([
                'leave_type_id' => $leave_headers[0]->leave_type_id,
                'employee_id' => $leave_headers[0]->employee_id,
            ])
            ->get();

        if ($credit_balance->isEmpty()) {
            $credits = 0;
        } else {
            $credits = $credit_balance[0]->credits;
        }

        $date_from = date("Y-m-d", strtotime($leave_headers[0]->date_from));
        $date_to = date("Y-m-d", strtotime($leave_headers[0]->date_to));
        $date = date("Y-m-d", strtotime($leave_headers[0]->date_from));

        $approver_2nd_approved = DB::table('approver_headers as a')
            ->join('approver_details as b', 'b.approver_id', '=', 'a.id')
            ->join('leave_headers as c', 'c.employee_id', '=', 'b.employee_id')
            ->select(
                'a.id',
                'a.approver_id_2 as supervisor_id'
            )
            ->where('c.id', $id)
            ->where('a.approver_id_2', $emp_id_data[0]->id)
            ->get();

        $approver_3rd_approved = DB::table('approver_headers as a')
            ->join('approver_details as b', 'b.approver_id', '=', 'a.id')
            ->join('leave_headers as c', 'c.employee_id', '=', 'b.employee_id')
            ->select(
                'a.id',
                'a.approver_id_3 as supervisor_id'
            )
            ->where('c.id', $id)
            ->where('a.approver_id_3', $emp_id_data[0]->id)
            ->orWhere('a.approver_id_4', $emp_id_data[0]->id)
            ->get();

        if ($approver_2nd_approved->isNotEmpty() && $approver_3rd_approved->isNotEmpty()) {
            $credit_leave_process = true;

            if ($leave_headers[0]->is_force_leave == true) {
                $revert_cancel_leave = true;
            } else {
                $revert_cancel_leave = false;
            }
        } elseif ($approver_2nd_approved->isNotEmpty() && $approver_3rd_approved->isEmpty()) {
            if ($leave_headers[0]->leave_type_id == 7) {
                $credit_leave_process = true;
            } else {
                if ($leave_headers[0]->is_force_leave == true) {
                    $credit_leave_process = false;
                } else {
                    $credit_leave_process = true;
                }
            }

            if ($leave_headers[0]->is_force_leave == true) {
                $revert_cancel_leave = false;
            } else {
                if ($leave_headers[0]->leave_type_id == 7) {
                    $revert_cancel_leave = false;
                } else {
                    $revert_cancel_leave = true;
                }
            }
        } elseif ($approver_2nd_approved->isEmpty() && $approver_3rd_approved->isNotEmpty()) {
            if ($leave_headers[0]->leave_type_id == 7) {
                $credit_leave_process = true;
            } else {
                if ($leave_headers[0]->is_force_leave == true) {
                    $credit_leave_process = true;
                } else {
                    $credit_leave_process = false;
                }
            }

            if ($leave_headers[0]->is_force_leave == true) {
                $revert_cancel_leave = true;
            } else {
                if ($leave_headers[0]->leave_type_id == 7) {
                    $revert_cancel_leave = true;
                } else {
                    $revert_cancel_leave = false;
                }
            }
        } else {
            $credit_leave_process = false;
            $revert_cancel_leave = false;
        }

        if ($process_id == 1) { // if approve leave
            if ($credit_leave_process == true) {
                // delete detail data first
                DB::table('leave_details')->where('leave_id', $id)->delete();

                // Check Leave type.
                $leave_policy_reset = DB::table('leave_types')
                    ->where('leave_balance_policy_id', 3)
                    ->where('id', $leave_headers[0]->leave_type_id)
                    ->get();

                // get unavailable dates
                $employee = db::table('employees')->select('branch_id', 'work_schedule_id')->where('id', $leave_headers[0]->employee_id)->get();

                if ($employee->isEmpty()) {
                    $employee = db::table('branches')->select('id as branch_id', db::raw("cast(1 as int) as work_schedule_id"))->where('is_main_branch', true)->get();
                }

                $applied_leaves = DB::table('leave_headers as a')
                    ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                    ->select(
                        db::raw("CONVERT(NVARCHAR(50),DATEPART(D,b.leave_date))+'-'+CONVERT(NVARCHAR(50),DATEPART(M,b.leave_date))+'-'+CONVERT(NVARCHAR(50),DATEPART(YEAR,b.leave_date)) as un_date")
                    )
                    ->where('a.employee_id', $leave_headers[0]->employee_id)
                    ->where('a.is_cancel', false)
                    ->where('a.is_cancel_2', false)
                    ->where('a.id', '<>', $id);

                $un_dates = DB::table('holidays as c')
                    ->select(
                        db::raw("CONVERT(NVARCHAR(50),DATEPART(D,c.date))+'-'+CONVERT(NVARCHAR(50),DATEPART(M,c.date))+'-'+CONVERT(NVARCHAR(50),DATEPART(YEAR,c.date)) as un_date")
                    )
                    ->where('c.active', true)
                    ->whereIn('c.branch', [0, $employee[0]->branch_id])
                    ->unionAll($applied_leaves)
                    ->orderBy('un_date', 'asc')
                    ->get();

                $data_un_date = [];

                foreach ($un_dates as $un) {
                    array_push($data_un_date, date("d-m-Y", strtotime($un->un_date)));
                }

                $leave_detail_data = [];

                for ($date = $date_from; $date <= $date_to; $date = date("Y-m-d", strtotime("$date +1 day"))) {
                    if (in_array(date("d-m-Y", strtotime($date)), $data_un_date, true) == false) {
                        if (date('w', strtotime($date)) != 6 && date('w', strtotime($date)) != 0) {
                            if ($credits == 0) {
                                if ($leave_headers[0]->day_type_id == 1) {
                                    $day_type_value = 1;
                                } else {
                                    $day_type_value = 0.5;
                                }

                                $leave_detail_data = [
                                    'leave_id' => $id,
                                    'leave_date' => date("Y-m-d", strtotime("$date")),
                                    'with_pay' => 0,
                                    'without_pay' => $day_type_value,
                                ];
                            } else {

                                if ($leave_headers[0]->day_type_id == 1) {
                                    $day_type_value = 1;
                                } else {
                                    $day_type_value = 0.5;
                                }

                                if ($credits >= $day_type_value) {
                                    $leave_detail_data = [
                                        'leave_id' => $id,
                                        'leave_date' => date("Y-m-d", strtotime("$date")),
                                        'with_pay' => $day_type_value,
                                        'without_pay' => 0,
                                    ];
                                } else {
                                    $leave_detail_data = [
                                        'leave_id' => $id,
                                        'leave_date' => date("Y-m-d", strtotime("$date")),
                                        'with_pay' => 0,
                                        'without_pay' => $day_type_value,
                                    ];
                                }
                            }

                            // insert leave details
                            DB::table('leave_details')->Insert($leave_detail_data);
                            $credits = $credits - $day_type_value;

                            if ($credits < 0) {
                                $credits = 0;
                            }

                            // less credits with pay to leave credits table
                            DB::table('leave_credits')
                                ->where([
                                    'leave_type_id' => $leave_headers[0]->leave_type_id,
                                    'employee_id' => $leave_headers[0]->employee_id
                                ])
                                ->update(['credits' => $credits]);

                            // if ($leave_policy_reset->isEmpty()) {
                            //     // less credits with pay to leave credits table
                            //     DB::table('leave_credits')
                            //         ->where([
                            //             'leave_type_id' => $leave_headers[0]->leave_type_id,
                            //             'employee_id' => $leave_headers[0]->employee_id
                            //         ])
                            //         ->update(['credits' => $credits]);
                            // }
                        }
                    }
                }
            }
        } else if ($process_id == 3 && $revert_cancel_leave == true) { // if cancel from approve tab
            // get total approve credits
            $credits_return = DB::table('leave_details')
                ->select(
                    db::raw("SUM(with_pay) as with_pay")
                )
                ->where('leave_id', $id)
                ->get();

            if ($credits_return->isEmpty()) {
                $with_pay = 0;
            } else {
                $with_pay = $credits_return[0]->with_pay;
            }

            $credits = $credits + $with_pay;

            // return credits with pay to leave credits table
            DB::table('leave_credits')
                ->where([
                    'leave_type_id' => $leave_headers[0]->leave_type_id,
                    'employee_id' => $leave_headers[0]->employee_id
                ])
                ->update(['credits' => $credits]);
        }

        // main leave table update
        $emp_id = $emp_id_data[0]->id;
        $process_date = date("Y-m-d", strtotime(now()));

        if ($leave_headers->isNotEmpty()) {
            $employee_id = $leave_headers[0]->employee_id;
        } else {
            $employee_id = 0;
        }

        // Check if Approver
        $approver_1 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.branch_approver_id_1 as supervisor_id'
            )
            ->whereRaw("
                        b.employee_id = $employee_id
                        AND (a.branch_approver_id_1 = $emp_id OR a.approver_id_1  = $emp_id or a.division_approver_id_1 = $emp_id or a.section_approver_id_1 = $emp_id)
                    ")
            ->distinct()
            ->get();

        $approver_2 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.approver_id_2 as supervisor_id'
            )
            ->where('b.employee_id', $employee_id)
            ->where('a.approver_id_2', $emp_id)
            ->distinct()
            ->get();

        $approver_3 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.approver_id_3 as supervisor_id'
            )
            ->where('b.employee_id', $employee_id)
            ->where('a.approver_id_3', $emp_id)
            ->orWhere('a.approver_id_4', $emp_id)
            ->distinct()
            ->get();

        if ($process_id == 1) { // Approving
            if ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
                $process_data = array(
                    'approved' => true,
                    'disapproved' => false,
                    'processed_date' => $process_date,
                    'processed_by' => $emp_id,
                    'approved_remarks' => $remarks
                );
            } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
                $process_data = array(
                    'approved_2' => true,
                    'disapproved_2' => false,
                    'processed_date_2' => $process_date,
                    'processed_by_2' => $emp_id,
                    'approved_2_remarks' => $remarks
                );
            } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
                $process_data = array(
                    'approved' => true,
                    'disapproved' => false,
                    'processed_date' => $process_date,
                    'processed_by' => $emp_id,
                    'approved_remarks' => $remarks,
                    'approved_2' => true,
                    'disapproved_2' => false,
                    'processed_date_2' => $process_date,
                    'processed_by_2' => $emp_id,
                    'approved_2_remarks' => $remarks
                );
            } elseif ($approver_3->isNotEmpty()) {
                $process_data = array(
                    'approved_3' => true,
                    'disapproved_3' => false,
                    'processed_date_3' => $process_date,
                    'processed_by_3' => $emp_id,
                    'approved_3_remarks' => $remarks
                );
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Leave Application',
                'activity' => 'Approved',
                'description' => 'Approved leave informations.',
            );

            Audit::create($data_audit);
        } elseif ($process_id == 2) { // Disapproving

            if ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
                $process_data = array(
                    'approved' => false,
                    'disapproved' => true,
                    'processed_date' => $process_date,
                    'processed_by' => $emp_id,
                    'disapproved_remarks' => $remarks
                );
            } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
                $process_data = array(
                    'approved_2' => false,
                    'disapproved_2' => true,
                    'processed_date_2' => $process_date,
                    'processed_by_2' => $emp_id,
                    'disapproved_2_remarks' => $remarks
                );
            } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
                $process_data = array(
                    'approved' => false,
                    'disapproved' => true,
                    'processed_date' => $process_date,
                    'processed_by' => $emp_id,
                    'disapproved_remarks' => $remarks,
                    'approved_2' => false,
                    'disapproved_2' => true,
                    'processed_date_2' => $process_date,
                    'processed_by_2' => $emp_id,
                    'disapproved_2_remarks' => $remarks
                );
            } elseif ($approver_3->isNotEmpty()) {
                $process_data = array(
                    'approved_3' => false,
                    'disapproved_3' => true,
                    'processed_date_3' => $process_date,
                    'processed_by_3' => $emp_id,
                    'disapproved_3_remarks' => $remarks
                );
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Leave Application',
                'activity' => 'Disapproved',
                'description' => 'Disapproved leave informations.',
            );

            Audit::create($data_audit);
        } elseif ($process_id == 4) { // Cancellation

            if ($revert_cancel_leave == true) {
                // get total approve credits
                $credits_return = DB::table('leave_details')
                    ->select(
                        db::raw("SUM(with_pay) as with_pay")
                    )
                    ->where('leave_id', $id)
                    ->get();

                if ($credits_return->isEmpty()) {
                    $with_pay = 0;
                } else {
                    $with_pay = $credits_return[0]->with_pay;
                }

                $credits = $credits + $with_pay;

                // return credits with pay to leave credits table
                DB::table('leave_credits')
                    ->where([
                        'leave_type_id' => $leave_headers[0]->leave_type_id,
                        'employee_id' => $leave_headers[0]->employee_id
                    ])
                    ->update(['credits' => $credits]);
            }

            if ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
                $process_data = array(
                    'approved' => false,
                    'disapproved' => false,
                    'processed_date' => null,
                    'processed_by' => null,
                    'is_cancel' => 1,
                    'canceled_by' => $emp_id,
                    'canceled_date' => $process_date,
                    'canceled_remarks' => $remarks
                );
            } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
                $process_data = array(
                    'approved_2' => false,
                    'disapproved_2' => false,
                    'processed_date_2' => null,
                    'processed_by_2' => null,
                    'is_cancel_2' => 1,
                    'canceled_by_2' => $emp_id,
                    'canceled_date_2' => $process_date,
                    'canceled_remarks_2' => $remarks
                );
            } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
                $process_data = array(
                    'approved' => false,
                    'disapproved' => false,
                    'processed_date' => null,
                    'processed_by' => null,
                    'is_cancel' => 1,
                    'canceled_by' => $emp_id,
                    'canceled_date' => $process_date,
                    'canceled_remarks' => $remarks,
                    'approved_2' => false,
                    'disapproved_2' => false,
                    'processed_date_2' => null,
                    'processed_by_2' => null,
                    'is_cancel_2' => 1,
                    'canceled_by_2' => $emp_id,
                    'canceled_date_2' => $process_date,
                    'canceled_remarks_2' => $remarks
                );
            } elseif ($approver_3->isNotEmpty()) {
                $process_data = array(
                    'approved_3' => false,
                    'disapproved_3' => false,
                    'processed_date_3' => null,
                    'processed_by_3' => null,
                    'is_cancel_3' => 1,
                    'canceled_by_3' => $emp_id,
                    'canceled_date_3' => $process_date,
                    'canceled_remarks_3' => $remarks
                );
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Leave Application',
                'activity' => 'Cancel',
                'description' => 'Cancel leave informations.',
            );

            Audit::create($data_audit);
        } else { // Cancellation
            if ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
                $process_data = array(
                    'approved' => false,
                    'disapproved' => false,
                    'processed_date' => null,
                    'processed_by' => null,
                    'is_cancel' => 1,
                    'canceled_by' => $emp_id,
                    'canceled_date' => $process_date,
                    'canceled_remarks' => $remarks
                );
            } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
                $process_data = array(
                    'approved_2' => false,
                    'disapproved_2' => false,
                    'processed_date_2' => null,
                    'processed_by_2' => null,
                    'is_cancel_2' => 1,
                    'canceled_by_2' => $emp_id,
                    'canceled_date_2' => $process_date,
                    'canceled_remarks_2' => $remarks
                );
            } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
                $process_data = array(
                    'approved' => false,
                    'disapproved' => false,
                    'processed_date' => null,
                    'processed_by' => null,
                    'is_cancel' => 1,
                    'canceled_by' => $emp_id,
                    'canceled_date' => $process_date,
                    'canceled_remarks' => $remarks,
                    'approved_2' => false,
                    'disapproved_2' => false,
                    'processed_date_2' => null,
                    'processed_by_2' => null,
                    'is_cancel_2' => 1,
                    'canceled_by_2' => $emp_id,
                    'canceled_date_2' => $process_date,
                    'canceled_remarks_2' => $remarks
                );
            } elseif ($approver_3->isNotEmpty()) {
                $process_data = array(
                    'approved_3' => false,
                    'disapproved_3' => false,
                    'processed_date_3' => null,
                    'processed_by_3' => null,
                    'is_cancel_3' => 1,
                    'canceled_by_3' => $emp_id,
                    'canceled_date_3' => $process_date,
                    'canceled_remarks_3' => $remarks
                );
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Leave Application',
                'activity' => 'Cancel',
                'description' => 'Cancel leave informations.',
            );

            Audit::create($data_audit);
        }

        // update leave header table
        DB::table('leave_headers')->where('id', $id)->update($process_data);

        return response()->json(array('success' => true));
    }

    public function monitoring()
    {
        $app_key = env("APP_KEY", "");

        $leave_for_approvals = DB::table('leave_headers as a')
            ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
            ->join('employees as c', 'a.employee_id', '=', 'c.id')
            ->join('leave_credits as d', function ($join) {
                $join->on('a.employee_id', '=', 'd.employee_id')
                    ->on('a.leave_type_id', '=', 'd.leave_type_id');
            })
            ->join('leave_details as e', 'a.id', '=', 'e.leave_id')
            ->leftJoin('employees as c1', 'c1.id', '=', 'a.canceled_by')
            ->leftJoin('employees as d1', 'd1.id', '=', 'a.processed_by')
            ->leftJoin('employees as e1', 'e1.id', '=', 'a.processed_by_2')
            ->leftJoin('employees as c2', 'c2.id', '=', 'a.canceled_by_2')
            ->select(
                'a.id',
                'c.id as employee_id',
                'c.photo',
                DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                'b.name as leave_type',
                DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
                DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                DB::raw("CONCAT(CONVERT(NVARCHAR(50),a.date_from,110),' - ',CONVERT(NVARCHAR(50),a.date_to,110)) as date_covered"),
                'a.reason',
                'a.remarks',
                'a.approved',
                'a.approved_2',
                'a.disapproved',
                'a.disapproved_2',
                'a.processed_date',
                'a.processed_date_2',
                DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) 
                            END as cancelled_by_name"),
                DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) 
                            END as cancelled_by_name_2"),
                DB::raw("CASE WHEN ISNULL(d1.is_encrypted,0) = 0 THEN
                                CONCAT(d1.first_name,' ',d1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](d1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d1.last_name,'$app_key')) 
                            END as approver_1"),
                DB::raw("CASE WHEN ISNULL(e1.is_encrypted,0) = 0 THEN
                                CONCAT(e1.first_name,' ',e1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e1.last_name,'$app_key')) 
                            END as approver_2"),
                'a.is_cancel',
                'a.is_cancel_2',
                'a.canceled_by',
                'a.canceled_date',
                'a.canceled_remarks',
                'a.canceled_by_2',
                'a.canceled_date_2',
                'a.canceled_remarks_2',
                'a.attachment_name',
                'a.is_cancel_2',
                DB::raw("sum(e.with_pay) as with_pay"),
                DB::raw("sum(e.without_pay) as without_pay")
            )
            ->groupBy(
                'a.id',
                'c.id',
                'c.photo',
                'c.first_name',
                'c.last_name',
                'b.name',
                'd.credits',
                'a.day_type_id',
                'a.date_from',
                'a.date_to',
                'a.reason',
                'a.remarks',
                'a.approved',
                'a.approved_2',
                'a.disapproved',
                'a.disapproved_2',
                'a.processed_date',
                'a.processed_date_2',
                'c1.first_name',
                'c1.last_name',
                'c2.first_name',
                'c2.last_name',
                'd1.first_name',
                'd1.last_name',
                'e1.first_name',
                'e1.last_name',
                'a.is_cancel',
                'a.is_cancel_2',
                'a.canceled_by',
                'a.canceled_date',
                'a.canceled_remarks',
                'a.canceled_by_2',
                'a.canceled_date_2',
                'a.canceled_remarks_2',
                'a.attachment_name',
                'c.is_encrypted',
                'c1.is_encrypted',
                'c2.is_encrypted',
                'd1.is_encrypted',
                'e1.is_encrypted'
            )
            ->orderBy('a.date_from', 'asc')
            ->get();

        return $this->successResponse($leave_for_approvals, 'Leave approvals retrieved successfully');
    }

    public function print($id)
    {
        $app_key = env("APP_KEY", "");

        // Check if logo file exists
        $logoPath = public_path('/dist/img/logo.png');
        if (!file_exists($logoPath)) {
            \Log::warning('Logo file not found: ' . $logoPath);
            $image = null;
        } else {
            $image = base64_encode(file_get_contents($logoPath));
        }

        $leave = DB::table('leave_headers as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
            ->leftJoin('positions as d', 'b.position_id', '=', 'd.id')
            ->leftJoin('companies as e', 'b.company_id', '=', 'e.id')
            ->select(
                'a.employee_id',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                'b.salary',
                'c.name as department',
                'd.name as position',
                'e.name as company',
                'e.address',
                'a.leave_type_id',
                'a.day_type_id',
                'a.date_from',
                'a.date_to',
                'a.reason',
                'a.incase_vacation_leave_id',
                'a.incase_vacation_leave_specify',
                'a.incase_sick_leave_id',
                'a.incase_sick_leave_specify',
                'a.incase_special_leave_specify',
                'a.incase_study_leave_id',
                'a.other_purpose_id',
                'a.commutation_id',
                'a.disapproved_remarks',
                'a.approved_remarks',
                'a.approved',
                'a.approved_2',
                'a.disapproved',
                'a.disapproved_2',
                'a.processed_by',
                'a.processed_by_2'
            )
            ->where('a.id', $id)
            ->get();

        // Get Signatories
        // if (($leave[0]->approved == true || $leave[0]->disapproved == true) && ($leave[0]->approved_2 == false && $leave[0]->disapproved_2 == false)) {
        //     $leave_signatories = DB::table('leave_headers as a')
        //         ->join('employees as b', 'a.processed_by', '=', 'b.id')
        //         ->select(
        //             // DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as approver")
        //             DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
        //                          UPPER(CONCAT(b.first_name,' ',b.last_name))
        //                      ELSE
        //                          RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
        //                      END as approver")
        //         )
        //         ->where('a.id', $id)
        //         ->get();
        // }

        // if (($leave[0]->approved == true || $leave[0]->disapproved == true) && ($leave[0]->approved_2 == true || $leave[0]->disapproved_2 == true)) {
        //     $leave_signatories = DB::table('leave_headers as a')
        //         ->join('employees as b', 'a.processed_by_2', '=', 'b.id')
        //         ->select(
        //             // DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as approver")
        //             DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
        //                         UPPER(CONCAT(b.first_name,' ',b.last_name))
        //                     ELSE
        //                         RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
        //                     END as approver")
        //         )
        //         ->where('a.id', $id)
        //         ->get();
        // }

        // if (($leave[0]->approved == false || $leave[0]->disapproved == false) && ($leave[0]->approved_2 == true || $leave[0]->disapproved_2 == true)) {
        //     $leave_signatories = DB::table('leave_headers as a')
        //         ->join('employees as b', 'a.processed_by_2', '=', 'b.id')
        //         ->select(
        //             // DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as approver")
        //             DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
        //                         UPPER(CONCAT(b.first_name,' ',b.last_name))
        //                     ELSE
        //                         RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
        //                     END as approver")
        //         )
        //         ->where('a.id', $id)
        //         ->get();
        // } else {
        //     $leave_signatories = ['approver' => ''];

        //     $leave_signatories = (object)$leave_signatories;
        //     $leave_signatories = collect([$leave_signatories]);
        // }

        $leave_signatories = DB::table('approver_headers as a')
            ->leftJoin('employees as b', 'a.approver_id_1', '=', 'b.id')
            ->leftJoin('employees as c', 'a.section_approver_id_1', '=', 'c.id')
            ->leftJoin('employees as d', 'a.approver_id_2', '=', 'd.id')
            ->leftJoin('employees as e', 'a.approver_id_3', '=', 'e.id')
            ->leftJoin('employees as f', 'a.approver_id_4', '=', 'f.id')
            ->select(
                DB::raw("CASE WHEN ISNULL(b.id,0) = 0 THEN
                            CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(c.first_name,' ',c.last_name))
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END
                        ELSE
                            CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(b.first_name,' ',b.last_name))
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END
                        END as approver_1"),
                DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(d.first_name,' ',d.last_name))
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key')) 
                            END as approver_2"),
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            UPPER(CONCAT(e.first_name,' ',e.last_name))
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) 
                        END as approver_3"),
                DB::raw("CASE WHEN ISNULL(f.is_encrypted,0) = 0 THEN
                        UPPER(CONCAT(f.first_name,' ',f.last_name))
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](f.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](f.last_name,'$app_key')) 
                    END as approver_4")
            )
            ->whereIn('a.id', function ($query) use ($id) {
                $query->select('b.approver_id')
                    ->from('leave_headers as a')
                    ->join('approver_details as b', 'a.employee_id', '=', 'b.employee_id')
                    ->where('a.id', $id);
            })
            ->get();

        $data_with_pay = DB::table('leave_headers as a')
            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
            ->select(
                DB::raw("count(leave_date) as total_applied_days"),
                DB::raw("sum(with_pay) as with_pay"),
                DB::raw("sum(without_pay) as without_pay")
            )
            ->where('a.id', $id)
            ->get();

        $leave_credeits_vl = DB::table('leave_credits as a')
            ->where([
                'employee_id' => isset($leave[0]->employee_id) ? $leave[0]->employee_id : 0,
                'leave_type_id' => 16
            ])
            ->get();

        $leave_credeits_sl = DB::table('leave_credits as a')
            ->where([
                'employee_id' => isset($leave[0]->employee_id) ? $leave[0]->employee_id : 0,
                'leave_type_id' => 3
            ])
            ->get();

        if (count($leave_credeits_vl) == 0) {
            $leave_credeits_vl = [
                'credits' => 0
            ];

            $leave_credeits_vl = (object)$leave_credeits_vl;
            $leave_credeits_vl = collect([$leave_credeits_vl]);
        }

        if (count($leave_credeits_sl) == 0) {
            $leave_credeits_sl = [
                'credits' => 0
            ];

            $leave_credeits_sl = (object)$leave_credeits_sl;
            $leave_credeits_sl = collect([$leave_credeits_sl]);
        }

        // Debug logging
        \Log::info('Print method called for leave ID: ' . $id);
        \Log::info('Leave data count: ' . count($leave));
        \Log::info('Data with pay count: ' . count($data_with_pay));
        \Log::info('Leave credits VL count: ' . count($leave_credeits_vl));
        \Log::info('Leave credits SL count: ' . count($leave_credeits_sl));
        \Log::info('Leave signatories count: ' . count($leave_signatories));

        try {
            $pdf = PDF::loadView('leaves.leave_print', compact(
                'leave',
                'image',
                'data_with_pay',
                'leave_credeits_vl',
                'leave_credeits_sl',
                'leave_signatories'
            ))->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('A4');
            
            return $pdf->stream('leave_application_' . $id . '.pdf');
        } catch (\Exception $e) {
            \Log::error('PDF generation error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }

    public function attachments($id)
    {
        $attachments = DB::table('leave_attachments')->where('leave_id', $id)->get();

        return json_encode($attachments);
    }

    public function remove_attachments($id)
    {
        $data = DB::table('leave_attachments')->where('id', $id)->delete();

        return json_encode($data);
    }

    public function checkLWOP($id, $leave_type_id)
    {
        $lwop = db::table('leave_headers as a')
            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
            ->select(
                'a.id',
                'b.id as leave_dtl_id',
                'a.employee_id',
                'b.without_pay',
                'b.with_pay',
                'a.leave_type_id'
            )
            ->where([
                'a.employee_id' => $id,
                'a.leave_type_id' => $leave_type_id,
                'a.approved' => true
            ])
            ->where('b.without_pay', '>', 0)
            ->get();

        return json_encode($lwop);
    }

    public function creditCancelledLeave($leave_cancelled_id)
    {

        // get cancelled leave data
        $cancelled_leave = DB::table('leave_headers as a')
            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
            ->select(
                'a.employee_id',
                'a.leave_type_id',
                'b.with_pay',
                'b.leave_date'
            )
            ->where('a.id', $leave_cancelled_id)
            ->where('b.with_pay', '>', 0)
            ->get();

        // get credit leave data
        $credit_leave = DB::table('leave_headers as a')
            ->join(
                'leave_details as b',
                'a.id',
                '=',
                'b.leave_id'
            )
            ->select(
                'a.id',
                'b.id as leave_dtl_id',
                'a.employee_id',
                'b.without_pay',
                'b.with_pay',
                'a.leave_type_id',
                'b.leave_date'
            )
            ->where([
                'a.employee_id' => $cancelled_leave[0]->employee_id,
                'a.leave_type_id' => $cancelled_leave[0]->leave_type_id,
                'a.approved' => true,
                'a.is_cancel' => false
            ])
            ->where('b.without_pay', '>', 0)
            ->get();

        $total_cancelled_with_pay = 0;

        for ($i = 0; $i < count($cancelled_leave); $i++) {
            $total_cancelled_with_pay = $total_cancelled_with_pay + $cancelled_leave[$i]->with_pay;
        }

        // get credit balance
        $credit_balance = DB::table('leave_credits')->where(['leave_type_id' => $cancelled_leave[0]->leave_type_id, 'employee_id' => $cancelled_leave[0]->employee_id])->get();

        if ($credit_balance->isEmpty()) {
            $credits_orig = 0;
        } else {
            $credits_orig = $credit_balance[0]->credits;
        }

        $credits = $total_cancelled_with_pay;

        // compute with pay
        if ($credits > 0) {
            foreach ($credit_leave as $cl) {
                if ($credits > 0) {
                    if ($cl->without_pay == 1) {
                        $day_type_value = 1;
                    } else {
                        $day_type_value = 0.5;
                    }

                    if ($credits >= $day_type_value) {
                        $leave_detail_data = [
                            'with_pay' => $day_type_value,
                            'without_pay' => 0,
                        ];
                    } else {
                        $day_type_value = $credits;

                        $leave_detail_data = [
                            'with_pay' => $day_type_value,
                            'without_pay' => $cl->without_pay - $day_type_value,
                        ];
                    }

                    // insert leave details
                    DB::table('leave_details')->updateOrInsert(['id' => $cl->leave_dtl_id], $leave_detail_data);
                    $credits = $credits - $day_type_value;
                    $credits_orig = $credits_orig - $day_type_value;

                    if ($credits < 0) {
                        $credits = 0;
                    }

                    if ($credits_orig < 0) {
                        $credits_orig = 0;
                    }

                    // less credits with pay to leave credits table
                    DB::table('leave_credits')
                        ->where([
                            'leave_type_id' => $cl->leave_type_id,
                            'employee_id' => $cl->employee_id
                        ])
                        ->update(['credits' => $credits_orig]);
                }
            }
        }

        return json_encode('success');
    }

    public function cancel_attachment(Request $request)
    {

        $id = $request->leave_header_id;
        $data = $request->all();

        $attachment_data = [];

        // Save Leave Attachments
        if ($request->hasFile('attachment')) {

            $allowedfileExtension = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xlsx', 'xls'];
            $files = $request->file('attachment');

            if (isset($files)) {
                $file_name = $files->getClientOriginalName();
                $file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'leave_cancelled_documents\\' . 'DOCS' . $id . '_' . $file_name;
                $extension = $files->getClientOriginalExtension();
                $check = in_array($extension, $allowedfileExtension);

                if ($check) {
                    $attachment_data = [
                        'attachment_name' => $file_name,
                        'path' => $file_path,
                        'extension' => $extension
                    ];

                    // Update Time Data Table
                    DB::table('leave_headers')->where('id', $id)->update($attachment_data);

                    // Save attachment to path.
                    $request->attachment->storeAs('leave_cancelled_documents', 'DOCS' . $id . '_' . $file_name);
                }
            }
        }

        return $this->successResponse(null, 'Successfully Cancelled Application.');
    }

    public function download($id)
    {
        $documents = DB::table('leave_headers as a')
            ->select(
                'a.id',
                'a.employee_id',
                'a.attachment_name'
            )
            ->where('a.id', $id)
            ->get();

        $pathToFile = storage_path('app/leave_cancelled_documents/' . 'DOCS' . $documents[0]->id . '_' . $documents[0]->attachment_name);

        return response()->download($pathToFile);
    }

    public function downloadAttachment($id)
    {
        $documents = DB::table('leave_attachments as a')
            ->join('leave_headers as b', 'a.leave_id', '=', 'b.id')
            ->select(
                'a.id',
                'b.employee_id',
                'a.attachment_name'
            )
            ->where('a.id', $id)
            ->get();

        $pathToFile = storage_path('app/leave_attachments/' . 'LV' . $documents[0]->employee_id . '_' . $documents[0]->attachment_name);

        return response()->download($pathToFile);
    }
}
