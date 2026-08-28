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
                    'a.date_from',
                    'a.date_to',
                    'a.created_at',
                    'a.reason',
                    'a.incase_vacation_leave_id',
                    'a.incase_vacation_leave_specify',
                    'a.incase_sick_leave_id',
                    'a.incase_sick_leave_specify',
                    'a.is_advance_filing',
                    'a.incase_special_leave_specify',
                    'a.incase_study_leave_id',
                    'a.other_purpose_id',
                    'a.monetization',
                    'a.monetization_amount',
                    'a.terminal_leave',
                    'a.commutation_id',
                    'a.remarks',
                    'a.approved_remarks',
                    'a.approved_2_remarks',
                    'a.approved_3_remarks',
                    'a.disapproved_remarks',
                    'a.disapproved_2_remarks',
                    'a.disapproved_3_remarks',
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
                                CONCAT(c.first_name,' ',c.last_name)
                            END as cancelled_by_name"),
                    DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                CONCAT(c1.first_name,' ',c1.last_name)
                            END as cancelled_by_name_2"),
                    DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                            CONCAT(c2.first_name,' ',c2.last_name)
                        ELSE
                            CONCAT(c2.first_name,' ',c2.last_name)
                        END as cancelled_by_name_3"),
                    DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN
                                CONCAT(d.first_name,' ',d.last_name)
                            ELSE
                                CONCAT(d.first_name,' ',d.last_name)
                            END as approver_1"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CONCAT(e.first_name,' ',e.last_name)
                            ELSE
                                CONCAT(e.first_name,' ',e.last_name)
                            END as approver_2"),
                    DB::raw("CASE WHEN ISNULL(f.is_encrypted,0) = 0 THEN
                                CONCAT(f.first_name,' ',f.last_name)
                            ELSE
                                CONCAT(f.first_name,' ',f.last_name)
                            END as approver_3"),
                    'a.processed_date_2',
                    'a.processed_date_3',
                    'a.is_cancel_3',
                    'a.canceled_by_3',
                    'a.canceled_date_3',
                    'a.canceled_remarks_3',
                    'a.employee_id' // Add employee_id for enrichment function
                )
                ->where('a.employee_id', $emp_id)
                ->orderBy('a.date_to', 'desc')
                ->get();

            // Approvers are only from approver_headers (approver_id_1, approver_id_2, approver_id_3); employees in approver_details are the ones whose leaves they can approve. type_id = 1 = Leave.
            $approver_1 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select('a.id', 'a.approver_id_1 as supervisor_id')
                ->where('a.approver_id_1', $emp_id)
                ->where('a.type_id', 1)
                ->distinct()
                ->get();

            $approver_2 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select('a.id', 'a.approver_id_2 as supervisor_id')
                ->where('a.approver_id_2', $emp_id)
                ->where('a.type_id', 1)
                ->distinct()
                ->get();

            $approver_3 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select('a.id', 'a.approver_id_3 as supervisor_id')
                ->where(function ($q) use ($emp_id) {
                    $q->where('a.approver_id_3', $emp_id)->orWhere('a.approver_id_4', $emp_id);
                })
                ->where('a.type_id', 1)
                ->distinct()
                ->get();

            $approver_4 = collect(); // approver_id_4 is treated as level 3 with approver_3

            $leave_for_approvals = [];

            // Check approver_3 first so level-3 approvers see their pending leaves even if they're also approver_1/2 in other headers
            if ($approver_3->isNotEmpty()) {
                $supervisor_id = true;

                // Level 3: only leaves where level 1 and level 2 have responded, pending at level 3; approver is approver_id_3 or approver_id_4
                $leave_for_approvals = DB::table('leave_headers as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->leftJoin('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id')
                            ->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->leftJoin('employees as proc1', 'proc1.id', '=', 'a.processed_by')
                    ->leftJoin('employees as proc2', 'proc2.id', '=', 'a.processed_by_2')
                    ->leftJoin('employees as proc3', 'proc3.id', '=', 'a.processed_by_3')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                CONCAT(c.first_name,' ',c.last_name)
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
                        'a.is_force_leave',
                        'a.is_cancel_3',
                        'a.canceled_by_3',
                        'a.canceled_date_3',
                        'a.canceled_remarks_3',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(3 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                CONCAT(c1.first_name,' ',c1.last_name)
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                CONCAT(c2.first_name,' ',c2.last_name)
                            END as cancel_2"),
                        DB::raw("CASE WHEN ISNULL(proc1.is_encrypted,0) = 0 THEN
                                CONCAT(proc1.first_name,' ',proc1.last_name)
                            ELSE
                                CONCAT(proc1.first_name,' ',proc1.last_name)
                            END as approver_1"),
                        DB::raw("CASE WHEN ISNULL(proc2.is_encrypted,0) = 0 THEN
                                CONCAT(proc2.first_name,' ',proc2.last_name)
                            ELSE
                                CONCAT(proc2.first_name,' ',proc2.last_name)
                            END as approver_2"),
                        DB::raw("CASE WHEN ISNULL(proc3.is_encrypted,0) = 0 THEN
                                CONCAT(proc3.first_name,' ',proc3.last_name)
                            ELSE
                                CONCAT(proc3.first_name,' ',proc3.last_name)
                            END as approver_3")
                    )
                    // Include leaves where the employee is also the approver
                    // (previously excluded via whereNotIn on employee id).
                    ->where(function($query) use ($emp_id) {
                        $query->whereIn('a.id', function ($subQuery) use ($emp_id) {
                            $subQuery->select('c.id')
                                ->from('approver_details as a')
                                ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                                ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                                ->where('b.type_id', 1)
                                ->where(function ($q) use ($emp_id) {
                                    $q->where('b.approver_id_3', $emp_id)->orWhere('b.approver_id_4', $emp_id);
                                })
                                ->whereRaw("(ISNULL(c.processed_by,0) <> 0)")
                                ->whereRaw("(ISNULL(c.processed_by_2,0) <> 0)")
                                ->whereRaw("(ISNULL(c.processed_by_3,0) = 0 AND ISNULL(c.approved_3,0) = 0 AND ISNULL(c.disapproved_3,0) = 0)")
                                ->whereRaw("(ISNULL(c.is_cancel,0) = 0 AND ISNULL(c.is_cancel_2,0) = 0 AND ISNULL(c.is_cancel_3,0) = 0)");
                        })
                        ->orWhere('a.processed_by_3', $emp_id);
                    })
                    ->distinct()
                    ->orderBy('a.date_to', 'desc')
                    ->get();
            } elseif ($approver_1->isNotEmpty() && $approver_2->isEmpty()) { // Approver 1 only
                $supervisor_id = true;

                $leave_for_approvals = DB::table('leave_headers as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->leftJoin('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id');
                        $join->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->leftJoin('employees as proc1', 'proc1.id', '=', 'a.processed_by')
                    ->leftJoin('employees as proc2', 'proc2.id', '=', 'a.processed_by_2')
                    ->leftJoin('employees as proc3', 'proc3.id', '=', 'a.processed_by_3')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                CONCAT(c.first_name,' ',c.last_name)
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
                        'a.is_cancel_3',
                        'a.canceled_by_3',
                        'a.canceled_date_3',
                        'a.canceled_remarks_3',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(1 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                CONCAT(c1.first_name,' ',c1.last_name)
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                CONCAT(c2.first_name,' ',c2.last_name)
                            END as cancel_2"),
                        DB::raw("CASE WHEN ISNULL(proc1.is_encrypted,0) = 0 THEN
                                CONCAT(proc1.first_name,' ',proc1.last_name)
                            ELSE
                                CONCAT(proc1.first_name,' ',proc1.last_name)
                            END as approver_1"),
                        DB::raw("CASE WHEN ISNULL(proc2.is_encrypted,0) = 0 THEN
                                CONCAT(proc2.first_name,' ',proc2.last_name)
                            ELSE
                                CONCAT(proc2.first_name,' ',proc2.last_name)
                            END as approver_2"),
                        DB::raw("CASE WHEN ISNULL(proc3.is_encrypted,0) = 0 THEN
                                CONCAT(proc3.first_name,' ',proc3.last_name)
                            ELSE
                                CONCAT(proc3.first_name,' ',proc3.last_name)
                            END as approver_3")
                    )
                    // Include leaves where the employee is also the approver
                    ->where(function($query) use ($emp_id) {
                        $query->whereIn('a.id', function ($subQuery) use ($emp_id) {
                            $subQuery->select('c.id')
                                ->from('approver_details as a')
                                ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                                ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                                ->where('b.type_id', 1)
                                ->where('b.approver_id_1', $emp_id)
                                ->whereRaw("(ISNULL(c.processed_by,0) = 0 AND ISNULL(c.approved,0) = 0 AND ISNULL(c.disapproved,0) = 0)")
                                ->whereRaw("(ISNULL(c.is_cancel,0) = 0 AND ISNULL(c.is_cancel_2,0) = 0 AND ISNULL(c.is_cancel_3,0) = 0)");
                        })
                        ->orWhere('a.processed_by', $emp_id);
                    })
                    ->distinct()
                    ->orderBy('a.date_to', 'desc')
                    ->get();
            } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) { // Approver 2 only
                $supervisor_id = true;

                $leave_for_approvals = DB::table('leave_headers as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->leftJoin('leave_credits as d', function ($join) {
                        $join->on('a.employee_id', '=', 'd.employee_id');
                        $join->on('a.leave_type_id', '=', 'd.leave_type_id');
                    })
                    ->leftJoin('employees as c1', 'a.canceled_by', '=', 'c1.id')
                    ->leftJoin('employees as c2', 'a.canceled_by_2', '=', 'c2.id')
                    ->leftJoin('employees as proc1', 'proc1.id', '=', 'a.processed_by')
                    ->leftJoin('employees as proc2', 'proc2.id', '=', 'a.processed_by_2')
                    ->leftJoin('employees as proc3', 'proc3.id', '=', 'a.processed_by_3')
                    ->select(
                        'a.id',
                        'c.id as employee_id',
                        'c.photo',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                CONCAT(c.first_name,' ',c.last_name)
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
                        'a.is_cancel_3',
                        'a.canceled_by_3',
                        'a.canceled_date_3',
                        'a.canceled_remarks_3',
                        'a.date_from',
                        'a.date_to',
                        DB::raw("CAST(2 as int) as approver_level_id"),
                        DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                CONCAT(c1.first_name,' ',c1.last_name)
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                CONCAT(c2.first_name,' ',c2.last_name)
                            END as cancel_2"),
                        DB::raw("CASE WHEN ISNULL(proc1.is_encrypted,0) = 0 THEN
                                CONCAT(proc1.first_name,' ',proc1.last_name)
                            ELSE
                                CONCAT(proc1.first_name,' ',proc1.last_name)
                            END as approver_1"),
                        DB::raw("CASE WHEN ISNULL(proc2.is_encrypted,0) = 0 THEN
                                CONCAT(proc2.first_name,' ',proc2.last_name)
                            ELSE
                                CONCAT(proc2.first_name,' ',proc2.last_name)
                            END as approver_2"),
                        DB::raw("CASE WHEN ISNULL(proc3.is_encrypted,0) = 0 THEN
                                CONCAT(proc3.first_name,' ',proc3.last_name)
                            ELSE
                                CONCAT(proc3.first_name,' ',proc3.last_name)
                            END as approver_3")
                    )
                    // Include leaves where the employee is also the approver
                    ->where(function($query) use ($emp_id) {
                        $query->whereIn('a.id', function ($subQuery) use ($emp_id) {
                            $subQuery->select('c.id')
                                ->from('approver_details as a')
                                ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                                ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                                ->where('b.type_id', 1)
                                ->where('b.approver_id_2', $emp_id)
                                ->whereRaw("(ISNULL(c.processed_by,0) <> 0 AND (ISNULL(c.approved,0) = 1 OR ISNULL(c.disapproved,0) = 1))")
                                ->whereRaw("(ISNULL(c.processed_by_2,0) = 0 AND ISNULL(c.approved_2,0) = 0 AND ISNULL(c.disapproved_2,0) = 0)")
                                ->whereRaw("(ISNULL(c.is_cancel,0) = 0 AND ISNULL(c.is_cancel_2,0) = 0 AND ISNULL(c.is_cancel_3,0) = 0)");
                        })
                        ->orWhere('a.processed_by_2', $emp_id);
                    })
                    ->distinct()
                    ->orderBy('a.date_to', 'desc')
                    ->get();
            } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) { // User is both level 1 and 2: show level-1-pending first, then level-2-pending
                $supervisor_id = true;

                $leave_for_approvals_1 = DB::table('leave_headers as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->leftJoin('leave_credits as d', function ($join) {
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
                                CONCAT(c.first_name,' ',c.last_name)
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
                                CONCAT(c1.first_name,' ',c1.last_name)
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                CONCAT(c2.first_name,' ',c2.last_name)
                            END as cancel_2")
                    )
                    // Include leaves where the employee is also the approver
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                            ->where('b.type_id', 1)
                            ->where('b.approver_id_1', $emp_id)
                            ->whereRaw("(ISNULL(c.processed_by,0) = 0 AND ISNULL(c.approved,0) = 0 AND ISNULL(c.disapproved,0) = 0)")
                            ->whereRaw("(ISNULL(c.is_cancel,0) = 0 AND ISNULL(c.is_cancel_2,0) = 0 AND ISNULL(c.is_cancel_3,0) = 0)");
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
                    ->leftJoin('leave_credits as d', function ($join) {
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
                                CONCAT(c.first_name,' ',c.last_name)
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
                                CONCAT(c1.first_name,' ',c1.last_name)
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                CONCAT(c2.first_name,' ',c2.last_name)
                            END as cancel_2")
                    )
                    // Include leaves where the employee is also the approver
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_headers as c', 'a.employee_id', '=', 'c.employee_id')
                            ->where('b.type_id', 1)
                            ->where('b.approver_id_2', $emp_id)
                            ->whereRaw("(ISNULL(c.processed_by,0) <> 0 AND (ISNULL(c.approved,0) = 1 OR ISNULL(c.disapproved,0) = 1))")
                            ->whereRaw("(ISNULL(c.processed_by_2,0) = 0 AND ISNULL(c.approved_2,0) = 0 AND ISNULL(c.disapproved_2,0) = 0)")
                            ->whereRaw("(ISNULL(c.is_cancel,0) = 0 AND ISNULL(c.is_cancel_2,0) = 0 AND ISNULL(c.is_cancel_3,0) = 0)");
                    })
                    ->distinct()
                    ->union($leave_for_approvals_1)
                    ->orderBy('date_to', 'desc')
                    ->get();
            } elseif ($approver_4->isNotEmpty()) {
                $supervisor_id = true;

                $leave_for_approvals_1 = DB::table('leave_headers as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->leftJoin('leave_credits as d', function ($join) {
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
                                CONCAT(c.first_name,' ',c.last_name)
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
                                CONCAT(c1.first_name,' ',c1.last_name)
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                CONCAT(c2.first_name,' ',c2.last_name)
                            END as cancel_2"),
                        'a.is_force_leave',
                        'a.is_cancel_3',
                        'a.canceled_by_3',
                        'a.canceled_date_3',
                        'a.canceled_remarks_3'
                    )
                    // Include leaves where the employee is also the approver
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
                    ->leftJoin('leave_credits as d', function ($join) {
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
                                CONCAT(c.first_name,' ',c.last_name)
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
                                CONCAT(c1.first_name,' ',c1.last_name)
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                CONCAT(c2.first_name,' ',c2.last_name)
                            END as cancel_2"),
                        'a.is_force_leave',
                        'a.is_cancel_3',
                        'a.canceled_by_3',
                        'a.canceled_date_3',
                        'a.canceled_remarks_3'
                    )
                    // Include leaves where the employee is also the approver
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
                    ->leftJoin('leave_credits as d', function ($join) {
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
                                CONCAT(c.first_name,' ',c.last_name)
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
                                CONCAT(c1.first_name,' ',c1.last_name)
                            END as cancel_1"),
                        DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                CONCAT(c2.first_name,' ',c2.last_name)
                            END as cancel_2"),
                        'a.is_force_leave',
                        'a.is_cancel_3',
                        'a.canceled_by_3',
                        'a.canceled_date_3',
                        'a.canceled_remarks_3'
                    )
                    // Include leaves where the employee is also the approver
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

        // Check if With Approver for Leave (type_id = 1).
        $with_approvers = DB::table('approver_details as ad')
            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
            ->where('ad.employee_id', $emp_id)
            ->where('ah.type_id', 1) // Leave type
            ->get();

        if (count($with_approvers) > 0) {
            $allowed = 1;
        } else {
            $allowed = 0;
        }

        // trigger auto-approved leave
        // (new LeaveService)->autoApproved();

        // Enrich both user leaves and approval data with approver configuration
        if (!empty($leaves)) {
            $leaves = $this->enrichLeavesWithApproverConfig($leaves);
        }
        if (!empty($leave_for_approvals)) {
            // Ensure approval rows carry the same leave detail fields used by frontend reason rendering.
            $approvalIds = collect($leave_for_approvals)->pluck('id')->filter()->values()->all();
            if (!empty($approvalIds)) {
                $approvalMetaRows = DB::table('leave_headers')
                    ->select(
                        'id',
                        'incase_vacation_leave_id',
                        'incase_vacation_leave_specify',
                        'incase_sick_leave_id',
                        'incase_sick_leave_specify',
                        'is_advance_filing',
                        'incase_special_leave_specify',
                        'incase_study_leave_id',
                        'other_purpose_id',
                        'monetization',
                        'monetization_amount',
                        'terminal_leave',
                        'commutation_id',
                        'created_at',
                        'approved_remarks',
                        'approved_2_remarks',
                        'approved_3_remarks',
                        'disapproved_remarks',
                        'disapproved_2_remarks',
                        'disapproved_3_remarks',
                        'canceled_remarks'
                    )
                    ->whereIn('id', $approvalIds)
                    ->get()
                    ->keyBy('id');

                foreach ($leave_for_approvals as $idx => $row) {
                    $meta = $approvalMetaRows->get($row->id);
                    if (!$meta) {
                        continue;
                    }
                    $leave_for_approvals[$idx]->incase_vacation_leave_id = $meta->incase_vacation_leave_id;
                    $leave_for_approvals[$idx]->incase_vacation_leave_specify = $meta->incase_vacation_leave_specify;
                    $leave_for_approvals[$idx]->incase_sick_leave_id = $meta->incase_sick_leave_id;
                    $leave_for_approvals[$idx]->incase_sick_leave_specify = $meta->incase_sick_leave_specify;
                    $leave_for_approvals[$idx]->is_advance_filing = $meta->is_advance_filing;
                    $leave_for_approvals[$idx]->incase_special_leave_specify = $meta->incase_special_leave_specify;
                    $leave_for_approvals[$idx]->incase_study_leave_id = $meta->incase_study_leave_id;
                    $leave_for_approvals[$idx]->other_purpose_id = $meta->other_purpose_id;
                    $leave_for_approvals[$idx]->monetization = $meta->monetization;
                    $leave_for_approvals[$idx]->monetization_amount = $meta->monetization_amount;
                    $leave_for_approvals[$idx]->terminal_leave = $meta->terminal_leave;
                    $leave_for_approvals[$idx]->commutation_id = $meta->commutation_id;
                    $leave_for_approvals[$idx]->created_at = $meta->created_at;
                    $leave_for_approvals[$idx]->approved_remarks = $meta->approved_remarks;
                    $leave_for_approvals[$idx]->approved_2_remarks = $meta->approved_2_remarks;
                    $leave_for_approvals[$idx]->approved_3_remarks = $meta->approved_3_remarks;
                    $leave_for_approvals[$idx]->disapproved_remarks = $meta->disapproved_remarks;
                    $leave_for_approvals[$idx]->disapproved_2_remarks = $meta->disapproved_2_remarks;
                    $leave_for_approvals[$idx]->disapproved_3_remarks = $meta->disapproved_3_remarks;
                    $leave_for_approvals[$idx]->canceled_remarks = $meta->canceled_remarks;
                }
            }
        }
        if (!empty($leave_for_approvals)) {
            $leave_for_approvals = $this->enrichLeavesWithApproverConfig($leave_for_approvals);
        }

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

        $leave_types = $leave_types->map(function ($leaveType) {
            $leaveType->is_el = (int) ($leaveType->is_el ?? 0);
            return $leaveType;
        });

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
            $isOtherPurpose = in_array((int) $request->other_purpose_id, [1, 2], true);
            $validate = Validator::make($request->all(), [
            'date_from' => $isOtherPurpose ? 'nullable|date' : 'date',
            'date_to' => $isOtherPurpose ? 'nullable|date' : 'date'
        ], [
            'date_from.date' => 'Date From must be valid date format (MM/DD/YYYY).',
            'date_to.date' => 'Date To must be valid date format (MM/DD/YYYY).',
        ]);

        if ($validate->fails()) {
            return $this->validationErrorResponse($validate->errors());
        }

        // Check if employee has approver configured for Leave (type_id = 1)
        $has_approver = DB::table('approver_details as ad')
            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
            ->where('ad.employee_id', $request->employee_id)
            ->where('ah.type_id', 1) // Leave type
            ->exists();

        if (!$has_approver) {
            return $this->errorResponse('You cannot apply for leave. No approver has been configured for leave applications.');
        }

        $data = array(
            'employee_id' => $request->employee_id,
            'leave_type_id' => $request->leave_type_id,
            'day_type_id' => $isOtherPurpose ? null : $request->day_type_id,
            'date_from' => $isOtherPurpose ? null : $request->date_from,
            'date_to' => $isOtherPurpose ? null : $request->date_to,
            'reason' => $request->reason,
            'incase_vacation_leave_id' => $request->incase_vacation_leave_id,
            'incase_vacation_leave_specify' => $request->incase_vacation_leave_specify,
            'incase_sick_leave_id' => $request->incase_sick_leave_id,
            'incase_sick_leave_specify' => $request->incase_sick_leave_specify,
            'incase_special_leave_specify' => $request->incase_special_leave_specify,
            'incase_study_leave_id' => $request->incase_study_leave_id,
            'other_purpose_id' => $request->other_purpose_id,
            'commutation_id' => $request->commutation_id,
            'monetization' => $request->has('monetization') ? (bool) $request->monetization : false,
            'monetization_amount' => $request->monetization_amount,
            'terminal_leave' => $request->has('terminal_leave') ? (bool) $request->terminal_leave : false,
            'is_force_leave' => $request->has('is_force_leave') ? true : false,
            'is_emergency_leave' => $request->boolean('is_emergency_leave'),
            'is_advance_filing' => $request->boolean('is_advance_filing'),
        );

        if (!$isOtherPurpose) {
        $is_lwop = (int) $request->leave_type_id === 13;

        // get credit balance (LWOP does not use leave credits)
        if ($is_lwop) {
            $credits = 0;
        } else {
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

        $leaveType = DB::table('leave_types')->where('id', $request->leave_type_id)->first();
        $leaveTypeName = strtolower((string) ($leaveType->name ?? ''));
        $requiresVacationAdvanceNotice = $leaveType && (
            strpos($leaveTypeName, 'vacation') !== false ||
            strpos($leaveTypeName, 'special privilege') !== false
        );
        $allowsEmergencyLeave = $leaveType && (int) ($leaveType->is_el ?? 0) === 1;

        if ($request->boolean('is_emergency_leave') && !$allowsEmergencyLeave) {
            return $this->errorResponse('Emergency leave is not allowed for the selected leave type.');
        }

        $isSickLeaveType = strpos($leaveTypeName, 'sick') !== false;

        if ($request->boolean('is_advance_filing') && !$isSickLeaveType) {
            return $this->errorResponse('Advance sick leave filing is only allowed for sick leave.');
        }

        if ($isSickLeaveType && $request->boolean('is_advance_filing')) {
            $today = Carbon::today()->startOfDay();
            if ($date_from->lt($today) || $date_to->lt($today)) {
                return $this->errorResponse('Advance sick leave must be filed for today or a future date (e.g. scheduled consultation or operation).');
            }
        }

        if ($requiresVacationAdvanceNotice) {
            $isEmergencyLeave = $request->boolean('is_emergency_leave') && $allowsEmergencyLeave;
            $minDate = $isEmergencyLeave
                ? Carbon::today()->startOfDay()
                : $this->addWorkingDaysForward(Carbon::today()->startOfDay(), 5);

            if ($date_from->lt($minDate) || $date_to->lt($minDate)) {
                $minDateLabel = $minDate->format('M d, Y');
                $message = $isEmergencyLeave
                    ? 'Leave dates cannot be in the past.'
                    : "Vacation leave must be filed at least 5 working days in advance. The earliest date you can select is {$minDateLabel}.";

                return $this->errorResponse($message);
            }
        }

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
            $canInsertDate = false;
            if ($is_lwop) {
                // LWOP should not be blocked by overlap/holiday/weekend checks.
                $canInsertDate = true;
            } else {
                $isUnavailableDate = in_array(date("d-m-Y", strtotime($date)), $data_un_date, true);
                $isWeekend = date('w', strtotime($date)) == 6 || date('w', strtotime($date)) == 0;
                $canInsertDate = !$isUnavailableDate && !$isWeekend;
            }

            if ($canInsertDate) {

                    // insert or update leave headers
                    if ($id == 0) {
                        DB::table('leave_headers')->insert(array_merge($data, [
                            'created_at' => Carbon::now()
                        ]));
                        $id = DB::table('leave_headers')->max('id');
                    } else {
                        DB::table('leave_headers')->updateOrInsert(['id' => $id], $data);
                    }

                    $ctr = $ctr + 1;

                    if ($is_lwop) {
                        $day_type_value = 0;
                        $leave_detail_data = [
                            'leave_id' => $id,
                            'leave_date' => date("Y-m-d", strtotime("$date")),
                            'with_pay' => 0,
                            'without_pay' => $request->day_type_id == 1 ? 1 : 0.5,
                        ];
                    } elseif ($credits == 0) {
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
            $date->addDay(1);
        }

        if ($ctr == 0) {
            return response()->json(['error' => 'Invalid Leave Application.'], 500);
        }
        } else {
            // For monetization/terminal leave, persist header-only and keep leave date/day fields null.
            DB::table('leave_details')->where('leave_id', '=', $id)->delete();
            if ($id == 0) {
                DB::table('leave_headers')->insert(array_merge($data, [
                    'created_at' => Carbon::now()
                ]));
                $id = DB::table('leave_headers')->max('id');
            } else {
                DB::table('leave_headers')->updateOrInsert(['id' => $id], $data);
            }
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
        if ((int) $leave_id === 13) {
            return $this->successResponse(0, 'Leave validation completed');
        }

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

        $employee_id = $leave_headers->isNotEmpty() ? $leave_headers[0]->employee_id : 0;
        $emp_id = $emp_id_data[0]->id ?? 0;

        // Load approver header for this leave's employee (Leave = type_id 1). Only approver_headers define approvers (approver_id_1, approver_id_2, approver_id_3); employees in approver_details are the ones who can submit leave under this header.
        $approver_header = DB::table('approver_headers as a')
            ->join('approver_details as b', 'b.approver_id', '=', 'a.id')
            ->where('b.employee_id', $employee_id)
            ->where('a.type_id', 1) // Leave
            ->select(
                'a.approver_id_1',
                'a.approver_id_2',
                'a.approver_id_3',
                'a.approver_id_4'
            )
            ->first();

        // Max approver level (1, 2, or 3): how many levels are configured for this employee
        $max_approver_level = 1;
        if ($approver_header) {
            if (!empty($approver_header->approver_id_3) || !empty($approver_header->approver_id_4)) {
                $max_approver_level = 3;
            } elseif (!empty($approver_header->approver_id_2)) {
                $max_approver_level = 2;
            }
        }

        // Only employees in approver_headers (approver_id_1, approver_id_2, approver_id_3) can approve; link via approver_details (employee_id = leave applicant, approver_id = approver_headers.id)
        $approver_1 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_1 as supervisor_id')
            ->where('b.employee_id', $employee_id)
            ->where('a.approver_id_1', $emp_id)
            ->where('a.type_id', 1)
            ->distinct()
            ->get();

        $approver_2 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_2 as supervisor_id')
            ->where('b.employee_id', $employee_id)
            ->where('a.approver_id_2', $emp_id)
            ->where('a.type_id', 1)
            ->distinct()
            ->get();

        $approver_3 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_3 as supervisor_id')
            ->where('b.employee_id', $employee_id)
            ->where(function ($q) use ($emp_id) {
                $q->where('a.approver_id_3', $emp_id)->orWhere('a.approver_id_4', $emp_id);
            })
            ->where('a.type_id', 1)
            ->distinct()
            ->get();

        // Hierarchy: level 2 cannot act until level 1 has responded; level 3 cannot act until level 2 has responded
        $lh = $leave_headers->isNotEmpty() ? $leave_headers[0] : null;
        $level_1_responded = $lh && (int)($lh->processed_by ?? 0) !== 0 && ((int)($lh->approved ?? 0) === 1 || (int)($lh->disapproved ?? 0) === 1);
        $level_2_responded = $lh && (int)($lh->processed_by_2 ?? 0) !== 0 && ((int)($lh->approved_2 ?? 0) === 1 || (int)($lh->disapproved_2 ?? 0) === 1);

        if ($process_id == 1 || $process_id == 2) {
            if ($approver_2->isNotEmpty() && !$level_1_responded) {
                return response()->json(['success' => false, 'message' => 'Level 2 approver cannot act until Level 1 has approved or disapproved.'], 400);
            }
            if ($approver_3->isNotEmpty() && !$level_2_responded) {
                return response()->json(['success' => false, 'message' => 'Level 3 approver cannot act until Level 2 has approved or disapproved.'], 400);
            }
        }

        // Current user's highest approver level (1, 2, or 3)
        $current_user_approver_level = $approver_3->isNotEmpty() ? 3 : ($approver_2->isNotEmpty() ? 2 : ($approver_1->isNotEmpty() ? 1 : 0));

        $is_lwop = (int) ($leave_headers[0]->leave_type_id ?? 0) === 13;

        // Deduct leave_credits only when the current approver is the last required level (fully approved after this action)
        $credit_leave_process = (!$is_lwop && $process_id == 1 && $current_user_approver_level > 0 && $current_user_approver_level == $max_approver_level);

        // Revert credits on cancel only when leave was fully approved (all required levels had approved)
        $is_fully_approved = false;
        if ($leave_headers->isNotEmpty()) {
            $lh = $leave_headers[0];
            if ($max_approver_level == 1) {
                $is_fully_approved = (int)($lh->approved ?? 0) === 1;
            } elseif ($max_approver_level == 2) {
                $is_fully_approved = (int)($lh->approved ?? 0) === 1 && (int)($lh->approved_2 ?? 0) === 1;
            } else {
                $is_fully_approved = (int)($lh->approved ?? 0) === 1 && (int)($lh->approved_2 ?? 0) === 1 && (int)($lh->approved_3 ?? 0) === 1;
            }
        }
        $revert_cancel_leave = (!$is_lwop && $process_id == 3 && $is_fully_approved);

        if ($is_lwop) {
            $credits = 0;
        } else {
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
        }

        $date_from = date("Y-m-d", strtotime($leave_headers[0]->date_from));
        $date_to = date("Y-m-d", strtotime($leave_headers[0]->date_to));
        $date = date("Y-m-d", strtotime($leave_headers[0]->date_from));

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

                            // Sync time_data for DTR consistency
                            try {
                                $existingTimeData = DB::table('time_data')
                                    ->where('employee_id', $leave_headers[0]->employee_id)
                                    ->whereDate('date', $date)
                                    ->first();
                                if ($existingTimeData) {
                                    DB::table('time_data')
                                        ->where('id', $existingTimeData->id)
                                        ->update(['is_leave' => 1, 'leave' => $day_type_value]);
                                } else {
                                    DB::table('time_data')->insert([
                                        'employee_id' => $leave_headers[0]->employee_id,
                                        'date' => $date,
                                        'is_leave' => 1,
                                        'leave' => $day_type_value,
                                        'absent' => 0,
                                    ]);
                                }
                            } catch (\Exception $e) {}

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

        // main leave table update (emp_id and employee_id already set above; approver_1/2/3 already loaded)
        $process_date = date("Y-m-d", strtotime(now()));

        if ($process_id == 1) { // Approving — set only the next pending level (processed_by = approver_id_1, processed_by_2 = approver_id_2, processed_by_3 = approver_id_3)
            $level_1_pending = $lh && (int)($lh->processed_by ?? 0) === 0;
            $level_2_pending = $lh && $level_1_responded && (int)($lh->processed_by_2 ?? 0) === 0;
            $level_3_pending = $lh && $level_2_responded && (int)($lh->processed_by_3 ?? 0) === 0;

            if ($approver_1->isNotEmpty() && $level_1_pending) {
                $process_data = array(
                    'approved' => true,
                    'disapproved' => false,
                    'processed_date' => $process_date,
                    'processed_by' => $emp_id,
                    'approved_remarks' => $remarks
                );
            } elseif ($approver_2->isNotEmpty() && $level_2_pending) {
                $process_data = array(
                    'approved_2' => true,
                    'disapproved_2' => false,
                    'processed_date_2' => $process_date,
                    'processed_by_2' => $emp_id,
                    'approved_2_remarks' => $remarks
                );
            } elseif ($approver_3->isNotEmpty() && $level_3_pending) {
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
        } elseif ($process_id == 2) { // Disapproving — set only the next pending level
            $level_1_pending = $lh && (int)($lh->processed_by ?? 0) === 0;
            $level_2_pending = $lh && $level_1_responded && (int)($lh->processed_by_2 ?? 0) === 0;
            $level_3_pending = $lh && $level_2_responded && (int)($lh->processed_by_3 ?? 0) === 0;

            if ($approver_1->isNotEmpty() && $level_1_pending) {
                $process_data = array(
                    'approved' => false,
                    'disapproved' => true,
                    'processed_date' => $process_date,
                    'processed_by' => $emp_id,
                    'disapproved_remarks' => $remarks
                );
            } elseif ($approver_2->isNotEmpty() && $level_2_pending) {
                $process_data = array(
                    'approved_2' => false,
                    'disapproved_2' => true,
                    'processed_date_2' => $process_date,
                    'processed_by_2' => $emp_id,
                    'disapproved_2_remarks' => $remarks
                );
            } elseif ($approver_3->isNotEmpty() && $level_3_pending) {
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

            // Revert credits only when leave was fully approved (all required approver levels had approved)
            $is_approved = (int)($leave_headers[0]->approved ?? 0) === 1 ||
                          (int)($leave_headers[0]->approved_2 ?? 0) === 1 ||
                          (int)($leave_headers[0]->approved_3 ?? 0) === 1;

            // Check if current user is the employee who owns the leave
            $is_employee_owner = $employee_id == $emp_id;

            // For fully approved leaves, revert credits when canceling (whether by employee or approver)
            if ($is_fully_approved) {
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

                if ($with_pay > 0) {
                    $credits = $credits + $with_pay;

                    // return credits with pay to leave credits table
                    DB::table('leave_credits')
                        ->where([
                            'leave_type_id' => $leave_headers[0]->leave_type_id,
                            'employee_id' => $leave_headers[0]->employee_id
                        ])
                        ->update(['credits' => $credits]);
                }
            } elseif ($revert_cancel_leave == true) {
                // Handle credit reversal for non-approved leaves if needed
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

                if ($with_pay > 0) {
                    $credits = $credits + $with_pay;

                    // return credits with pay to leave credits table
                    DB::table('leave_credits')
                        ->where([
                            'leave_type_id' => $leave_headers[0]->leave_type_id,
                            'employee_id' => $leave_headers[0]->employee_id
                        ])
                        ->update(['credits' => $credits]);
                }
            }

            // Check if current user is the employee who owns the leave
            $is_employee_owner = $employee_id == $emp_id;

            // If employee is canceling their own approved leave, reset all approval flags
            if ($is_employee_owner && $is_approved) {
                $process_data = array(
                    'approved' => false,
                    'approved_2' => false,
                    'approved_3' => false,
                    'disapproved' => false,
                    'disapproved_2' => false,
                    'disapproved_3' => false,
                    'processed_date' => null,
                    'processed_date_2' => null,
                    'processed_date_3' => null,
                    'processed_by' => null,
                    'processed_by_2' => null,
                    'processed_by_3' => null,
                    'is_cancel' => 1,
                    'canceled_by' => $emp_id,
                    'canceled_date' => $process_date,
                    'canceled_remarks' => $remarks
                );
            } elseif ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
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
            } elseif ($is_employee_owner) {
                // Employee canceling their own non-approved leave
                $process_data = array(
                    'is_cancel' => 1,
                    'canceled_by' => $emp_id,
                    'canceled_date' => $process_date,
                    'canceled_remarks' => $remarks
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

        // Ensure process_data is set before updating
        if (!isset($process_data) || empty($process_data)) {
            return response()->json(array('success' => false, 'message' => 'Unable to process leave. Invalid process or insufficient permissions.'), 400);
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
                    ->leftJoin('leave_credits as d', function ($join) {
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
                                CONCAT(c.first_name,' ',c.last_name)
                            END as name"),
                'b.name as leave_type',
                DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
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
                'a.processed_date_2',
                DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN
                                CONCAT(c1.first_name,' ',c1.last_name)
                            ELSE
                                CONCAT(c1.first_name,' ',c1.last_name)
                            END as cancelled_by_name"),
                DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN
                                CONCAT(c2.first_name,' ',c2.last_name)
                            ELSE
                                CONCAT(c2.first_name,' ',c2.last_name)
                            END as cancelled_by_name_2"),
                DB::raw("CASE WHEN ISNULL(d1.is_encrypted,0) = 0 THEN
                                CONCAT(d1.first_name,' ',d1.last_name)
                            ELSE
                                CONCAT(d1.first_name,' ',d1.last_name)
                            END as approver_1"),
                DB::raw("CASE WHEN ISNULL(e1.is_encrypted,0) = 0 THEN
                                CONCAT(e1.first_name,' ',e1.last_name)
                            ELSE
                                CONCAT(e1.first_name,' ',e1.last_name)
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
            ->join('leave_types as lt', 'a.leave_type_id', '=', 'lt.id')
            ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
            ->leftJoin('positions as d', 'b.position_id', '=', 'd.id')
            ->leftJoin('companies as e', 'b.company_id', '=', 'e.id')
            ->select(
                'a.employee_id',
                DB::raw("b.first_name as first_name"),
                DB::raw("b.middle_name as middle_name"),
                DB::raw("b.last_name as last_name"),
                'b.employee_no',
                'b.salary_grade_id',
                'c.name as department',
                'd.name as position',
                'e.name as company',
                'e.address',
                'lt.name as leave_type',
                // explicit alias used by updated Blade template for more robust matching
                'lt.name as leave_type_name',
                'a.leave_type_id',
                'a.day_type_id',
                'a.date_from',
                'a.date_to',
                'a.reason',
                'a.incase_vacation_leave_id',
                'a.incase_vacation_leave_specify',
                'a.incase_sick_leave_id',
                'a.incase_sick_leave_specify',
                'a.is_advance_filing',
                'a.incase_special_leave_specify',
                'a.incase_study_leave_id',
                'a.other_purpose_id',
                'a.monetization',
                'a.terminal_leave',
                'a.monetization_amount',
                'a.commutation_id',
                'a.disapproved_remarks',
                'a.approved_remarks',
                'a.approved_2_remarks',
                'a.disapproved_2_remarks',
                'a.disapproved_3_remarks',
                'a.approved',
                'a.approved_2',
                'a.approved_3',
                'a.disapproved',
                'a.disapproved_2',
                'a.disapproved_3',
                'a.processed_by',
                'a.processed_by_2',
                'a.created_at'
            )
            ->where('a.id', $id)
            ->get();

        // Enrich with approver configuration flags so we can determine "fully approved"
        $leave = $this->enrichLeavesWithApproverConfig($leave);

        // Determine if this leave is fully approved across all configured levels
        $toBool = function ($val) {
            return $val === true || $val === 1 || $val === '1';
        };

        $hasLevel2 = isset($leave[0]->has_approver_level_2) && $toBool($leave[0]->has_approver_level_2);
        $hasLevel3 = isset($leave[0]->has_approver_level_3) && $toBool($leave[0]->has_approver_level_3);
        $highestLevel = $hasLevel3 ? 3 : ($hasLevel2 ? 2 : 1);

        $isFullyApproved = true;
        if ($highestLevel >= 1 && !$toBool($leave[0]->approved ?? 0)) {
            $isFullyApproved = false;
        }
        if ($highestLevel >= 2 && !$toBool($leave[0]->approved_2 ?? 0)) {
            $isFullyApproved = false;
        }
        if ($highestLevel >= 3 && !$toBool($leave[0]->approved_3 ?? 0)) {
            $isFullyApproved = false;
        }

        // Get Signatories
        // if (($leave[0]->approved == true || $leave[0]->disapproved == true) && ($leave[0]->approved_2 == false && $leave[0]->disapproved_2 == false)) {
        //     $leave_signatories = DB::table('leave_headers as a')
        //         ->join('employees as b', 'a.processed_by', '=', 'b.id')
        //         ->select(
        //             // DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as approver")
        //             DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
        //                          UPPER(CONCAT(b.first_name,' ',b.last_name))
        //                      ELSE
        //                          CONCAT(b.first_name,' ',b.last_name)
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
        //                         CONCAT(b.first_name,' ',b.last_name)
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
        //                         CONCAT(b.first_name,' ',b.last_name)
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
                                UPPER(CONCAT(
                                    c.first_name,
                                    CASE
                                        WHEN ISNULL(NULLIF(LTRIM(RTRIM(c.middle_name)), ''), '') = '' THEN ' '
                                        ELSE CONCAT(' ', LEFT(LTRIM(RTRIM(c.middle_name)), 1), '. ')
                                    END,
                                    c.last_name
                                ))
                            ELSE
                                UPPER(CONCAT(
                                    c.first_name,
                                    CASE
                                        WHEN ISNULL(NULLIF(LTRIM(RTRIM(c.middle_name)), ''), '') = '' THEN ' '
                                        ELSE CONCAT(' ', LEFT(LTRIM(RTRIM(c.middle_name)), 1), '. ')
                                    END,
                                    c.last_name
                                ))
                            END
                        ELSE
                            CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(
                                    b.first_name,
                                    CASE
                                        WHEN ISNULL(NULLIF(LTRIM(RTRIM(b.middle_name)), ''), '') = '' THEN ' '
                                        ELSE CONCAT(' ', LEFT(LTRIM(RTRIM(b.middle_name)), 1), '. ')
                                    END,
                                    b.last_name
                                ))
                            ELSE
                                UPPER(CONCAT(
                                    b.first_name,
                                    CASE
                                        WHEN ISNULL(NULLIF(LTRIM(RTRIM(b.middle_name)), ''), '') = '' THEN ' '
                                        ELSE CONCAT(' ', LEFT(LTRIM(RTRIM(b.middle_name)), 1), '. ')
                                    END,
                                    b.last_name
                                ))
                            END
                        END as approver_1"),
                DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(
                                    d.first_name,
                                    CASE
                                        WHEN ISNULL(NULLIF(LTRIM(RTRIM(d.middle_name)), ''), '') = '' THEN ' '
                                        ELSE CONCAT(' ', LEFT(LTRIM(RTRIM(d.middle_name)), 1), '. ')
                                    END,
                                    d.last_name
                                ))
                            ELSE
                                UPPER(CONCAT(
                                    d.first_name,
                                    CASE
                                        WHEN ISNULL(NULLIF(LTRIM(RTRIM(d.middle_name)), ''), '') = '' THEN ' '
                                        ELSE CONCAT(' ', LEFT(LTRIM(RTRIM(d.middle_name)), 1), '. ')
                                    END,
                                    d.last_name
                                ))
                            END as approver_2"),
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            UPPER(CONCAT(
                                e.first_name,
                                CASE
                                    WHEN ISNULL(NULLIF(LTRIM(RTRIM(e.middle_name)), ''), '') = '' THEN ' '
                                    ELSE CONCAT(' ', LEFT(LTRIM(RTRIM(e.middle_name)), 1), '. ')
                                END,
                                e.last_name
                            ))
                        ELSE
                            UPPER(CONCAT(
                                e.first_name,
                                CASE
                                    WHEN ISNULL(NULLIF(LTRIM(RTRIM(e.middle_name)), ''), '') = '' THEN ' '
                                    ELSE CONCAT(' ', LEFT(LTRIM(RTRIM(e.middle_name)), 1), '. ')
                                END,
                                e.last_name
                            ))
                        END as approver_3"),
                DB::raw("CASE WHEN ISNULL(f.is_encrypted,0) = 0 THEN
                        UPPER(CONCAT(
                            f.first_name,
                            CASE
                                WHEN ISNULL(NULLIF(LTRIM(RTRIM(f.middle_name)), ''), '') = '' THEN ' '
                                ELSE CONCAT(' ', LEFT(LTRIM(RTRIM(f.middle_name)), 1), '. ')
                            END,
                            f.last_name
                        ))
                    ELSE
                        UPPER(CONCAT(
                            f.first_name,
                            CASE
                                WHEN ISNULL(NULLIF(LTRIM(RTRIM(f.middle_name)), ''), '') = '' THEN ' '
                                ELSE CONCAT(' ', LEFT(LTRIM(RTRIM(f.middle_name)), 1), '. ')
                            END,
                            f.last_name
                        ))
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

        // Dynamically determine which leave_types are used for Vacation and Sick Leave credits,
        // based on their names, so we don't rely on hard-coded IDs.
        $vacationTypeId = DB::table('leave_types')
            ->where('active', true)
            ->where('name', 'like', '%Vacation%')
            ->value('id');

        $sickTypeId = DB::table('leave_types')
            ->where('active', true)
            ->where('name', 'like', '%Sick%')
            ->value('id');

        // Fallback to legacy IDs if not found, to avoid breaking older data.
        if (!$vacationTypeId) {
            $vacationTypeId = 16;
        }
        if (!$sickTypeId) {
            $sickTypeId = 3;
        }

        $employeeIdForCredits = isset($leave[0]->employee_id) ? $leave[0]->employee_id : 0;

        $leave_credeits_vl = DB::table('leave_credits as a')
            ->where([
                'employee_id' => $employeeIdForCredits,
                'leave_type_id' => $vacationTypeId
            ])
            ->get();

        $leave_credeits_sl = DB::table('leave_credits as a')
            ->where([
                'employee_id' => $employeeIdForCredits,
                'leave_type_id' => $sickTypeId
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
                'leave_signatories',
                'isFullyApproved'
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

    /**
     * Enrich leave data with approver configuration levels
     */
    private function enrichLeavesWithApproverConfig($leaves)
    {
        $enriched = [];
        
        foreach ($leaves as $leave) {
            $leave = (array) $leave;
            
            // Skip if no employee_id
            if (!isset($leave['employee_id']) || empty($leave['employee_id'])) {
                $leave['has_approver_level_2'] = 0;
                $leave['has_approver_level_3'] = 0;
                $leave['has_approver_level_4'] = 0;
                $enriched[] = (object) $leave;
                continue;
            }
            
            // Get approver configuration for this employee (use DISTINCT to avoid duplicates)
            // Note: Only check up to level 3 since leave_headers table only supports 3 approval levels
            $approverConfig = DB::table('approver_details as ad')
                ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                ->where('ad.employee_id', $leave['employee_id'])
                ->select(
                    DB::raw("MAX(CASE WHEN ah.approver_id_2 IS NOT NULL AND ah.approver_id_2 > 0 THEN 1 ELSE 0 END) as has_approver_level_2"),
                    DB::raw("MAX(CASE WHEN ah.approver_id_3 IS NOT NULL AND ah.approver_id_3 > 0 THEN 1 ELSE 0 END) as has_approver_level_3")
                )
                ->first();
            
            if ($approverConfig) {
                $leave['has_approver_level_2'] = $approverConfig->has_approver_level_2;
                $leave['has_approver_level_3'] = $approverConfig->has_approver_level_3;
            } else {
                $leave['has_approver_level_2'] = 0;
                $leave['has_approver_level_3'] = 0;
            }
            
            $enriched[] = (object) $leave;
        }
        
        return $enriched;
    }

    private function addWorkingDaysForward(Carbon $startDate, int $workingDays): Carbon
    {
        $current = $startDate->copy()->startOfDay();
        $counted = 0;

        while ($counted < $workingDays) {
            $current->addDay();
            if (!$current->isWeekend()) {
                $counted++;
            }
        }

        return $current;
    }
}
