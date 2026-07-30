<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Support\Facades\Auth;
use App\Audit;
use App\Services\LeaveService;
use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class LeaveController extends Controller
{
    use ApiResponse, GeneratesPdf;

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
                    // Original decrypting CASE expressions kept for reference:
                    // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN CONCAT(c.first_name,' ',c.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) END as cancelled_by_name"),
                    DB::raw("CONCAT(c.first_name,' ',c.last_name) as cancelled_by_name"),
                    // DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN CONCAT(c1.first_name,' ',c1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) END as cancelled_by_name_2"),
                    DB::raw("CONCAT(c1.first_name,' ',c1.last_name) as cancelled_by_name_2"),
                    // DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN CONCAT(c2.first_name,' ',c2.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) END as cancelled_by_name_3"),
                    DB::raw("CONCAT(c2.first_name,' ',c2.last_name) as cancelled_by_name_3"),
                    // DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN CONCAT(d.first_name,' ',d.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key')) END as approver_1"),
                    DB::raw("CONCAT(d.first_name,' ',d.last_name) as approver_1"),
                    // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as approver_2"),
                    DB::raw("CONCAT(e.first_name,' ',e.last_name) as approver_2"),
                    // DB::raw("CASE WHEN ISNULL(f.is_encrypted,0) = 0 THEN CONCAT(f.first_name,' ',f.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](f.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](f.last_name,'$app_key')) END as approver_3"),
                    DB::raw("CONCAT(f.first_name,' ',f.last_name) as approver_3"),
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
                        // Original (decrypting) approver/cancelled-by name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN CONCAT(c.first_name,' ',c.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) END as name"),
                        DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
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
                        // Original decrypting cancel_1/cancel_2 kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN CONCAT(c1.first_name,' ',c1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) END as cancel_1"),
                        DB::raw("CONCAT(c1.first_name,' ',c1.last_name) as cancel_1"),
                        // DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN CONCAT(c2.first_name,' ',c2.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) END as cancel_2")
                        DB::raw("CONCAT(c2.first_name,' ',c2.last_name) as cancel_2")
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
                        /* Original decrypting name kept for reference:
                         * DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN CONCAT(c.first_name,' ',c.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) END as name"),
                         */
                        DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
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
                        // Original decrypting cancel_1/cancel_2 kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN CONCAT(c1.first_name,' ',c1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) END as cancel_1"),
                        DB::raw("CONCAT(c1.first_name,' ',c1.last_name) as cancel_1"),
                        // DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN CONCAT(c2.first_name,' ',c2.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) END as cancel_2")
                        DB::raw("CONCAT(c2.first_name,' ',c2.last_name) as cancel_2")
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
                        /* Original decrypting name kept for reference:
                         * DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN CONCAT(c.first_name,' ',c.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) END as name"),
                         */
                        DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
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
                        // Original decrypting cancel_1/cancel_2 kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN CONCAT(c1.first_name,' ',c1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) END as cancel_1"),
                        DB::raw("CONCAT(c1.first_name,' ',c1.last_name) as cancel_1"),
                        // DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN CONCAT(c2.first_name,' ',c2.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) END as cancel_2")
                        DB::raw("CONCAT(c2.first_name,' ',c2.last_name) as cancel_2")
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
                        /* Original decrypting name kept for reference:
                         * DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN CONCAT(c.first_name,' ',c.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) END as name"),
                         */
                        DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
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
                        // Original decrypting cancel_1/cancel_2 kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN CONCAT(c1.first_name,' ',c1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) END as cancel_1"),
                        DB::raw("CONCAT(c1.first_name,' ',c1.last_name) as cancel_1"),
                        // DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN CONCAT(c2.first_name,' ',c2.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) END as cancel_2")
                        DB::raw("CONCAT(c2.first_name,' ',c2.last_name) as cancel_2")
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
                        /* Original decrypting name kept for reference:
                         * DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN CONCAT(c.first_name,' ',c.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) END as name"),
                         */
                        DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
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
                        // Original decrypting cancel_1/cancel_2 kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN CONCAT(c1.first_name,' ',c1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) END as cancel_1"),
                        DB::raw("CONCAT(c1.first_name,' ',c1.last_name) as cancel_1"),
                        // DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN CONCAT(c2.first_name,' ',c2.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) END as cancel_2"),
                        DB::raw("CONCAT(c2.first_name,' ',c2.last_name) as cancel_2"),
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
                        /* Original decrypting name kept for reference:
                         * DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN CONCAT(c.first_name,' ',c.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) END as name"),
                         */
                        DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
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
                        // Original decrypting cancel_1/cancel_2 kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN CONCAT(c1.first_name,' ',c1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) END as cancel_1"),
                        DB::raw("CONCAT(c1.first_name,' ',c1.last_name) as cancel_1"),
                        // DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN CONCAT(c2.first_name,' ',c2.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) END as cancel_2")
                        DB::raw("CONCAT(c2.first_name,' ',c2.last_name) as cancel_2"),
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
                        /* Original decrypting name kept for reference:
                         * DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN CONCAT(c.first_name,' ',c.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) END as name"),
                         */
                        DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
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
                        // Original decrypting cancel_1/cancel_2 kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN CONCAT(c1.first_name,' ',c1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) END as cancel_1"),
                        DB::raw("CONCAT(c1.first_name,' ',c1.last_name) as cancel_1"),
                        // DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN CONCAT(c2.first_name,' ',c2.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) END as cancel_2")
                        DB::raw("CONCAT(c2.first_name,' ',c2.last_name) as cancel_2"),
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
                        /* Original decrypting name kept for reference:
                         * DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN CONCAT(c.first_name,' ',c.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) END as name"),
                         */
                        DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
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
                        // Original decrypting cancel_1/cancel_2 kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN CONCAT(c1.first_name,' ',c1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) END as cancel_1"),
                        DB::raw("CONCAT(c1.first_name,' ',c1.last_name) as cancel_1"),
                        // DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN CONCAT(c2.first_name,' ',c2.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) END as cancel_2"),
                        DB::raw("CONCAT(c2.first_name,' ',c2.last_name) as cancel_2"),
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
                        /* Original decrypting name kept for reference:
                         * DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN CONCAT(c.first_name,' ',c.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) END as name"),
                         */
                        DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
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
                        // Original decrypting cancel_1/cancel_2 kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN CONCAT(c1.first_name,' ',c1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) END as cancel_1"),
                        DB::raw("CONCAT(c1.first_name,' ',c1.last_name) as cancel_1"),
                        // DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN CONCAT(c2.first_name,' ',c2.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) END as cancel_2")
                        DB::raw("CONCAT(c2.first_name,' ',c2.last_name) as cancel_2"),
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
                        /* Original decrypting name kept for reference:
                         * DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN CONCAT(c.first_name,' ',c.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) END as name"),
                         */
                        DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
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
                        // Original decrypting cancel_1/cancel_2 kept for reference:
                        // DB::raw("CASE WHEN ISNULL(c1.is_encrypted,0) = 0 THEN CONCAT(c1.first_name,' ',c1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c1.last_name,'$app_key')) END as cancel_1"),
                        DB::raw("CONCAT(c1.first_name,' ',c1.last_name) as cancel_1"),
                        // DB::raw("CASE WHEN ISNULL(c2.is_encrypted,0) = 0 THEN CONCAT(c2.first_name,' ',c2.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c2.last_name,'$app_key')) END as cancel_2"),
                        DB::raw("CONCAT(c2.first_name,' ',c2.last_name) as cancel_2"),
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
                $date->addDay();
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
                    $file_path = storage_path('app/leave_attachments/' . 'LV' . $data['employee_id'] . '_' . $file_name);
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
                $date->addDay();
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
        // Admin bypass similar to OB: allow admin to process without approver mapping constraints
        if (Auth::user() && Auth::user()->is_admin) {
            try {
                $process_date = date("Y-m-d", strtotime(now()));

                $update = [];
                if ($process_id == 1) { // Approve
                    $update = [
                        'approved' => true,
                        'disapproved' => false,
                        'processed_date' => $process_date,
                        'processed_by' => 0,
                        'approved_2' => true,
                        'disapproved_2' => false,
                        'processed_date_2' => $process_date,
                        'processed_by_2' => 0,
                        'approved_remarks' => $remarks,
                        'approved_2_remarks' => $remarks,
                    ];
                } elseif ($process_id == 2) { // Disapprove
                    $update = [
                        'approved' => false,
                        'disapproved' => true,
                        'processed_date' => $process_date,
                        'processed_by' => 0,
                        'approved_2' => false,
                        'disapproved_2' => true,
                        'processed_date_2' => $process_date,
                        'processed_by_2' => 0,
                        'disapproved_remarks' => $remarks,
                        'disapproved_2_remarks' => $remarks,
                    ];
                } elseif ($process_id == 4) { // Cancel
                    $update = [
                        'approved' => false,
                        'disapproved' => false,
                        'is_cancel' => 1,
                        'canceled_by' => 0,
                        'canceled_date' => now(),
                        'canceled_remarks' => $remarks,
                    ];
                }

                if (!empty($update)) {
                    DB::table('leave_headers')->where('id', $id)->update($update);

                    $data_audit = array(
                        'user_id' => Auth::user()->id,
                        'module'  => 'Timekeeping Module',
                        'menu'    => 'Leave Application',
                        'activity' => ($process_id == 1 ? 'Approved' : ($process_id == 2 ? 'Disapproved' : 'Cancelled')),
                        'description' => ($process_id == 1 ? 'Approved' : ($process_id == 2 ? 'Disapproved' : 'Cancelled')) . ' leave application (Admin Bypass).',
                    );
                    Audit::create($data_audit);

                    return $this->successResponse(['id' => $id], 'Leave processed successfully');
                }
            } catch (\Throwable $th) {
                return $this->serverErrorResponse('Failed to process leave (admin bypass): ' . $th->getMessage());
            }
        }

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
            ->leftJoin('positions as pos', 'c.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'c.department_id', '=', 'dept.id')
            // Use left joins so headers without credits/details still appear in monitoring
            ->leftJoin('leave_credits as d', function ($join) {
                $join->on('a.employee_id', '=', 'd.employee_id')
                    ->on('a.leave_type_id', '=', 'd.leave_type_id');
            })
            ->leftJoin('leave_details as e', 'a.id', '=', 'e.leave_id')
            ->leftJoin('employees as c1', 'c1.id', '=', 'a.canceled_by')
            ->leftJoin('employees as d1', 'd1.id', '=', 'a.processed_by')
            ->leftJoin('employees as e1', 'e1.id', '=', 'a.processed_by_2')
            ->leftJoin('employees as c2', 'c2.id', '=', 'a.canceled_by_2')
            // approver_details.employee_id = leave applicant; approver_details.approver_id -> approver_headers.id
            // approver_headers.type_id=1 (Leave); approver_headers.approver_id_1/2/3 -> employees.id
            ->leftJoin(DB::raw('(SELECT employee_id, approver_id FROM (
                SELECT ad.employee_id, ad.approver_id,
                    ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn
                FROM approver_details ad
                INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 1
            ) x WHERE rn = 1) ad'), 'ad.employee_id', '=', 'c.id')
            ->leftJoin('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
            // Join with employees table to get approver names from approver_headers
            ->leftJoin('employees as app1', 'app1.id', '=', 'ah.approver_id_1')
            ->leftJoin('employees as app2', 'app2.id', '=', 'ah.approver_id_2')
            ->leftJoin('employees as app3', 'app3.id', '=', 'ah.approver_id_3')
            ->leftJoin('employees as app4', 'app4.id', '=', 'ah.approver_id_4')
            ->select(
                'a.id',
                'c.id as employee_id',
                'c.photo',
                'c.employee_no',
                'c.position_id',
                'c.department_id',
                /*
                 * Original (decrypting) name selection kept for reference:
                 * DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                 *                 CASE 
                 *                     WHEN ISNULL(c.middle_name,'') = '' THEN CONCAT(c.last_name, ', ', c.first_name)
                 *                     ELSE CONCAT(c.last_name, ', ', c.first_name, ' ', UPPER(SUBSTRING(c.middle_name, 1, 1)), '.')
                 *                 END
                 *             ELSE
                 *                 CASE 
                 *                     WHEN ISNULL([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),'') = '' 
                 *                         THEN CONCAT(RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')), ', ', RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key')))
                 *                     ELSE
                 *                         CONCAT(
                 *                             RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')), ', ',
                 *                             RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key')), ' ',
                 *                             UPPER(SUBSTRING([dbo].[ufn_DecryptString](c.middle_name,'$app_key'), 1, 1)), '.'
                 *                         )
                 *                 END
                 *             END as name"),
                 */
                DB::raw("CASE 
                            WHEN ISNULL(c.middle_name,'') = '' THEN CONCAT(c.last_name, ', ', c.first_name)
                            ELSE CONCAT(c.last_name, ', ', c.first_name, ' ', UPPER(SUBSTRING(c.middle_name, 1, 1)), '.')
                        END as name"),
                'pos.name as position',
                'dept.name as department',
                'b.id as leave_type_id',
                'b.name as leave_type',
                DB::raw("CASE WHEN ISNULL(d.credits,0) < 0 THEN 0 ELSE d.credits END as balance"),
                DB::raw("CASE WHEN a.day_type_id = 1 then 'Whole Day' else 'Half Day' end as day_type"),
                // Include raw date_from/date_to for frontend mapping
                'a.date_from',
                'a.date_to',
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
                'a.processed_date_3',
                'a.approved_remarks',
                'a.disapproved_remarks',
                'a.approved_2_remarks',
                'a.disapproved_2_remarks',
                'a.approved_3_remarks',
                'a.disapproved_3_remarks',
                /*
                 * Original (decrypting) cancelled_by_name/cancelled_by_name_2 kept for reference:
                 * - cancel uses middle-initial formatting
                 * - ELSE branch used ufn_DecryptString
                 */
                DB::raw("CASE 
                            WHEN ISNULL(c1.middle_name,'') = '' THEN CONCAT(c1.last_name, ', ', c1.first_name)
                            ELSE CONCAT(c1.last_name, ', ', c1.first_name, ' ', UPPER(SUBSTRING(c1.middle_name, 1, 1)), '.')
                        END as cancelled_by_name"),
                DB::raw("CASE 
                            WHEN ISNULL(c2.middle_name,'') = '' THEN CONCAT(c2.last_name, ', ', c2.first_name)
                            ELSE CONCAT(c2.last_name, ', ', c2.first_name, ' ', UPPER(SUBSTRING(c2.middle_name, 1, 1)), '.')
                        END as cancelled_by_name_2"),
                // Designated approvers from approver_headers hierarchy
                /* DB::raw("CASE WHEN ISNULL(app1.is_encrypted,0) = 0 THEN
                                CASE 
                                    WHEN ISNULL(app1.middle_name,'') = '' THEN CONCAT(app1.last_name, ', ', app1.first_name)
                                    ELSE CONCAT(app1.last_name, ', ', app1.first_name, ' ', UPPER(SUBSTRING(app1.middle_name, 1, 1)), '.')
                                END
                            ELSE
                                CASE
                                    WHEN ISNULL([dbo].[ufn_DecryptString](app1.middle_name,'$app_key'),'') = '' 
                                        THEN CONCAT(RTRIM([dbo].[ufn_DecryptString](app1.last_name,'$app_key')), ', ', RTRIM([dbo].[ufn_DecryptString](app1.first_name,'$app_key')))
                                    ELSE
                                        CONCAT(
                                            RTRIM([dbo].[ufn_DecryptString](app1.last_name,'$app_key')), ', ',
                                            RTRIM([dbo].[ufn_DecryptString](app1.first_name,'$app_key')), ' ',
                                            UPPER(SUBSTRING([dbo].[ufn_DecryptString](app1.middle_name,'$app_key'), 1, 1)), '.'
                                        )
                                END
                            END as approver_1"), */
                DB::raw("CASE 
                                WHEN ISNULL(app1.middle_name,'') = '' THEN CONCAT(app1.last_name, ', ', app1.first_name)
                                ELSE CONCAT(app1.last_name, ', ', app1.first_name, ' ', UPPER(SUBSTRING(app1.middle_name, 1, 1)), '.')
                            END as approver_1"),
                /* DB::raw("CASE WHEN ISNULL(app2.is_encrypted,0) = 0 THEN
                                CASE 
                                    WHEN ISNULL(app2.middle_name,'') = '' THEN CONCAT(app2.last_name, ', ', app2.first_name)
                                    ELSE CONCAT(app2.last_name, ', ', app2.first_name, ' ', UPPER(SUBSTRING(app2.middle_name, 1, 1)), '.')
                                END
                            ELSE
                                CASE
                                    WHEN ISNULL([dbo].[ufn_DecryptString](app2.middle_name,'$app_key'),'') = '' 
                                        THEN CONCAT(RTRIM([dbo].[ufn_DecryptString](app2.last_name,'$app_key')), ', ', RTRIM([dbo].[ufn_DecryptString](app2.first_name,'$app_key')))
                                    ELSE
                                        CONCAT(
                                            RTRIM([dbo].[ufn_DecryptString](app2.last_name,'$app_key')), ', ',
                                            RTRIM([dbo].[ufn_DecryptString](app2.first_name,'$app_key')), ' ',
                                            UPPER(SUBSTRING([dbo].[ufn_DecryptString](app2.middle_name,'$app_key'), 1, 1)), '.'
                                        )
                                END
                            END as approver_2"), */
                DB::raw("CASE 
                            WHEN ISNULL(app2.middle_name,'') = '' THEN CONCAT(app2.last_name, ', ', app2.first_name)
                            ELSE CONCAT(app2.last_name, ', ', app2.first_name, ' ', UPPER(SUBSTRING(app2.middle_name, 1, 1)), '.')
                        END as approver_2"),
                /* DB::raw("CASE WHEN ISNULL(app3.is_encrypted,0) = 0 THEN
                                CASE 
                                    WHEN ISNULL(app3.middle_name,'') = '' THEN CONCAT(app3.last_name, ', ', app3.first_name)
                                    ELSE CONCAT(app3.last_name, ', ', app3.first_name, ' ', UPPER(SUBSTRING(app3.middle_name, 1, 1)), '.')
                                END
                            ELSE
                                CASE
                                    WHEN ISNULL([dbo].[ufn_DecryptString](app3.middle_name,'$app_key'),'') = '' 
                                        THEN CONCAT(RTRIM([dbo].[ufn_DecryptString](app3.last_name,'$app_key')), ', ', RTRIM([dbo].[ufn_DecryptString](app3.first_name,'$app_key')))
                                    ELSE
                                        CONCAT(
                                            RTRIM([dbo].[ufn_DecryptString](app3.last_name,'$app_key')), ', ',
                                            RTRIM([dbo].[ufn_DecryptString](app3.first_name,'$app_key')), ' ',
                                            UPPER(SUBSTRING([dbo].[ufn_DecryptString](app3.middle_name,'$app_key'), 1, 1)), '.'
                                        )
                                END
                            END as approver_3"), */
                DB::raw("CASE 
                            WHEN ISNULL(app3.middle_name,'') = '' THEN CONCAT(app3.last_name, ', ', app3.first_name)
                            ELSE CONCAT(app3.last_name, ', ', app3.first_name, ' ', UPPER(SUBSTRING(app3.middle_name, 1, 1)), '.')
                        END as approver_3"),
                /* DB::raw("CASE WHEN ISNULL(app4.is_encrypted,0) = 0 THEN
                                CASE 
                                    WHEN ISNULL(app4.middle_name,'') = '' THEN CONCAT(app4.last_name, ', ', app4.first_name)
                                    ELSE CONCAT(app4.last_name, ', ', app4.first_name, ' ', UPPER(SUBSTRING(app4.middle_name, 1, 1)), '.')
                                END
                            ELSE
                                CASE
                                    WHEN ISNULL([dbo].[ufn_DecryptString](app4.middle_name,'$app_key'),'') = '' 
                                        THEN CONCAT(RTRIM([dbo].[ufn_DecryptString](app4.last_name,'$app_key')), ', ', RTRIM([dbo].[ufn_DecryptString](app4.first_name,'$app_key')))
                                    ELSE
                                        CONCAT(
                                            RTRIM([dbo].[ufn_DecryptString](app4.last_name,'$app_key')), ', ',
                                            RTRIM([dbo].[ufn_DecryptString](app4.first_name,'$app_key')), ' ',
                                            UPPER(SUBSTRING([dbo].[ufn_DecryptString](app4.middle_name,'$app_key'), 1, 1)), '.'
                                        )
                                END
                            END as approver_4"), */
                DB::raw("CASE 
                            WHEN ISNULL(app4.middle_name,'') = '' THEN CONCAT(app4.last_name, ', ', app4.first_name)
                            ELSE CONCAT(app4.last_name, ', ', app4.first_name, ' ', UPPER(SUBSTRING(app4.middle_name, 1, 1)), '.')
                        END as approver_4"),
                // Keep processed_by names for tracking who actually processed the leave
                /* DB::raw("CASE WHEN ISNULL(d1.is_encrypted,0) = 0 THEN
                                CASE 
                                    WHEN ISNULL(d1.middle_name,'') = '' THEN CONCAT(d1.last_name, ', ', d1.first_name)
                                    ELSE CONCAT(d1.last_name, ', ', d1.first_name, ' ', UPPER(SUBSTRING(d1.middle_name, 1, 1)), '.')
                                END
                            ELSE
                                CASE
                                    WHEN ISNULL([dbo].[ufn_DecryptString](d1.middle_name,'$app_key'),'') = '' 
                                        THEN CONCAT(RTRIM([dbo].[ufn_DecryptString](d1.last_name,'$app_key')), ', ', RTRIM([dbo].[ufn_DecryptString](d1.first_name,'$app_key')))
                                    ELSE
                                        CONCAT(
                                            RTRIM([dbo].[ufn_DecryptString](d1.last_name,'$app_key')), ', ',
                                            RTRIM([dbo].[ufn_DecryptString](d1.first_name,'$app_key')), ' ',
                                            UPPER(SUBSTRING([dbo].[ufn_DecryptString](d1.middle_name,'$app_key'), 1, 1)), '.'
                                        )
                                END
                            END as processed_by_name_1"), */
                DB::raw("CASE 
                            WHEN ISNULL(d1.middle_name,'') = '' THEN CONCAT(d1.last_name, ', ', d1.first_name)
                            ELSE CONCAT(d1.last_name, ', ', d1.first_name, ' ', UPPER(SUBSTRING(d1.middle_name, 1, 1)), '.')
                        END as processed_by_name_1"),
                /* DB::raw("CASE WHEN ISNULL(e1.is_encrypted,0) = 0 THEN
                                CASE 
                                    WHEN ISNULL(e1.middle_name,'') = '' THEN CONCAT(e1.last_name, ', ', e1.first_name)
                                    ELSE CONCAT(e1.last_name, ', ', e1.first_name, ' ', UPPER(SUBSTRING(e1.middle_name, 1, 1)), '.')
                                END
                            ELSE
                                CASE
                                    WHEN ISNULL([dbo].[ufn_DecryptString](e1.middle_name,'$app_key'),'') = '' 
                                        THEN CONCAT(RTRIM([dbo].[ufn_DecryptString](e1.last_name,'$app_key')), ', ', RTRIM([dbo].[ufn_DecryptString](e1.first_name,'$app_key')))
                                    ELSE
                                        CONCAT(
                                            RTRIM([dbo].[ufn_DecryptString](e1.last_name,'$app_key')), ', ',
                                            RTRIM([dbo].[ufn_DecryptString](e1.first_name,'$app_key')), ' ',
                                            UPPER(SUBSTRING([dbo].[ufn_DecryptString](e1.middle_name,'$app_key'), 1, 1)), '.'
                                        )
                                END
                            END as processed_by_name_2"), */
                DB::raw("CASE 
                            WHEN ISNULL(e1.middle_name,'') = '' THEN CONCAT(e1.last_name, ', ', e1.first_name)
                            ELSE CONCAT(e1.last_name, ', ', e1.first_name, ' ', UPPER(SUBSTRING(e1.middle_name, 1, 1)), '.')
                        END as processed_by_name_2"),
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
                DB::raw("sum(e.without_pay) as without_pay"),
                // Status: dynamic by configured approver levels (1..3)
                DB::raw("CASE WHEN
                            (ah.approver_id_1 IS NULL OR ISNULL(a.approved, 0) = 1)
                            AND (ah.approver_id_2 IS NULL OR ISNULL(a.approved_2, 0) = 1)
                            AND (ah.approver_id_3 IS NULL OR ISNULL(a.approved_3, 0) = 1)
                            AND (a.disapproved_3 = 0 OR a.disapproved_3 IS NULL)
                            AND (a.is_cancel = 0 OR a.is_cancel IS NULL)
                            AND (a.is_cancel_2 = 0 OR a.is_cancel_2 IS NULL)
                            AND (a.is_cancel_3 = 0 OR a.is_cancel_3 IS NULL)
                           THEN 'Approved' 
                           WHEN a.disapproved = 1 OR a.disapproved_2 = 1 OR a.disapproved_3 = 1 THEN 'Disapproved'
                           WHEN (a.is_cancel = 1 OR a.is_cancel_2 = 1 OR a.is_cancel_3 = 1) THEN 'Cancelled'
                           ELSE 'Pending' END as status")
            )
            ->groupBy(
                'a.id',
                'c.id',
                'c.photo',
                'c.employee_no',
                'c.position_id',
                'c.department_id',
                'c.first_name',
                'c.last_name',
                'c.middle_name',
                'pos.name',
                'dept.name',
                'b.id',
                'b.name',
                'd.credits',
                'a.day_type_id',
                'a.date_from',
                'a.date_to',
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
                'a.processed_date_3',
                'a.approved_remarks',
                'a.disapproved_remarks',
                'a.approved_2_remarks',
                'a.disapproved_2_remarks',
                'a.approved_3_remarks',
                'a.disapproved_3_remarks',
                'c1.first_name',
                'c1.last_name',
                'c1.middle_name',
                'c2.first_name',
                'c2.last_name',
                'c2.middle_name',
                'd1.first_name',
                'd1.last_name',
                'd1.middle_name',
                'e1.first_name',
                'e1.last_name',
                'e1.middle_name',
                // Approver fields from approver_headers
                'app1.first_name',
                'app1.last_name',
                'app1.middle_name',
                'app2.first_name',
                'app2.last_name',
                'app2.middle_name',
                'app3.first_name',
                'app3.last_name',
                'app3.middle_name',
                'app4.first_name',
                'app4.last_name',
                'app4.middle_name',
                'a.is_cancel',
                'a.is_cancel_2',
                'a.is_cancel_3',
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
                'e1.is_encrypted',
                'app1.is_encrypted',
                'app2.is_encrypted',
                'app3.is_encrypted',
                'app4.is_encrypted',
                'ah.approver_id_1',
                'ah.approver_id_2',
                'ah.approver_id_3'
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
            Log::warning('Logo file not found: ' . $logoPath);
            $image = null;
        } else {
            $image = base64_encode(file_get_contents($logoPath));
        }

        $leave = DB::table('leave_headers as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
            ->leftJoin('positions as d', 'b.position_id', '=', 'd.id')
            ->leftJoin('companies as e', 'b.company_id', '=', 'e.id')
            ->leftJoin('leave_types as f', 'a.leave_type_id', '=', 'f.id')
            ->select(
                'a.employee_id',
                // Original (decrypting) name selects kept for reference:
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                DB::raw("b.first_name as first_name"),
                DB::raw("b.middle_name as middle_name"),
                DB::raw("b.last_name as last_name"),
                'b.salary',
                'b.salary_grade_id',
                'c.name as department',
                'd.name as position',
                'e.name as company',
                'e.address',
                'a.leave_type_id',
                'f.name as leave_type_name',
                'a.day_type_id',
                'a.date_from',
                'a.date_to',
                'a.created_at',
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

        // Matches leave list "Approved" status: required approver slots satisfied, not disapproved.
        $approverConfig = DB::table('leave_headers as lh')
            ->join('approver_details as ad', 'lh.employee_id', '=', 'ad.employee_id')
            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
            ->where('lh.id', $id)
            ->select('ah.approver_id_1', 'ah.approver_id_2', 'ah.approver_id_3')
            ->first();

        $headerRow = $leave[0];
        $truthy = static function ($v): bool {
            return $v === true || $v === 1 || $v === '1';
        };
        $isFullyApproved = false;
        if ($approverConfig) {
            $step1 = $approverConfig->approver_id_1 === null || $truthy($headerRow->approved ?? null);
            $step2 = $approverConfig->approver_id_2 === null || $truthy($headerRow->approved_2 ?? null);
            $step3 = $approverConfig->approver_id_3 === null || $truthy($headerRow->approved_3 ?? null);
            $notDisapproved = !$truthy($headerRow->disapproved ?? null)
                && !$truthy($headerRow->disapproved_2 ?? null)
                && !$truthy($headerRow->disapproved_3 ?? null);
            $isFullyApproved = $step1 && $step2 && $step3 && $notDisapproved;
        }

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
            Log::error('PDF generation error: ' . $e->getMessage());
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
                $file_path = storage_path('app/leave_cancelled_documents/' . 'DOCS' . $id . '_' . $file_name);
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
     * Download leave application as DOCX using PhpWord native API with editable cells.
     */
    public function downloadDocx($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Check if logo file exists and load it
            $logoPath = public_path('/dist/img/logo.png');
            $logoImage = null;
            if (file_exists($logoPath)) {
                $logoImage = $logoPath;
            }

            $leave = DB::table('leave_headers as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('positions as d', 'b.position_id', '=', 'd.id')
                ->leftJoin('companies as e', 'b.company_id', '=', 'e.id')
                ->leftJoin('leave_types as f', 'a.leave_type_id', '=', 'f.id')
                ->select(
                    'a.employee_id',
                    // Original decrypting name selects kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                    DB::raw("b.middle_name as middle_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
                    'b.salary',
                    'c.name as department',
                    'd.name as position',
                    'e.name as company',
                    'e.address',
                    'a.leave_type_id',
                    'f.name as leave_type_name',
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

            if ($leave->isEmpty()) {
                return $this->errorResponse('Leave application not found.');
            }

            $dtl = $leave[0];

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
                    'employee_id' => isset($dtl->employee_id) ? $dtl->employee_id : 0,
                    'leave_type_id' => 16
                ])
                ->get();

            $leave_credeits_sl = DB::table('leave_credits as a')
                ->where([
                    'employee_id' => isset($dtl->employee_id) ? $dtl->employee_id : 0,
                    'leave_type_id' => 3
                ])
                ->get();

            if (count($leave_credeits_vl) == 0) {
                $leave_credeits_vl = collect([(object)['credits' => 0]]);
            }

            if (count($leave_credeits_sl) == 0) {
                $leave_credeits_sl = collect([(object)['credits' => 0]]);
            }

            // Helper function to check leave type
            $isLeaveType = function($typeName, $typeId) use ($dtl) {
                $leaveTypeName = isset($dtl->leave_type_name) ? strtolower(trim($dtl->leave_type_name)) : '';
                $leaveTypeId = isset($dtl->leave_type_id) ? $dtl->leave_type_id : 0;
                return (strtolower(trim($typeName)) === $leaveTypeName) || ($typeId == $leaveTypeId);
            };

            // Helper function to get other leave type
            $getOtherLeaveType = function() use ($dtl) {
                $predefinedTypeNames = [
                    'vacation leave', 'forced leave', 'mandatory leave', 'sick leave',
                    'maternity leave', 'paternity leave', 'special privilege leave',
                    'solo parent leave', 'study leave', 'vawc leave', '10-day vawc leave',
                    'rehabilitation privilege', 'rehabilitation leave',
                    'special leave benefits for women', 'special leave benefits',
                    'special emergency (calamity) leave', 'emergency leave',
                    'special emergency leave', 'adoption leave'
                ];
                $predefinedTypeIds = [1, 2, 3, 4, 5, 6, 7, 8, 11];
                $leaveTypeName = isset($dtl->leave_type_name) ? strtolower(trim($dtl->leave_type_name)) : '';
                $leaveTypeId = isset($dtl->leave_type_id) ? $dtl->leave_type_id : 0;
                $isPredefined = in_array($leaveTypeName, $predefinedTypeNames) || in_array($leaveTypeId, $predefinedTypeIds);
                return !$isPredefined && !empty($dtl->leave_type_name) ? $dtl->leave_type_name : '';
            };

            // Calculate original balance at the time this request was processed
            // Get the processed date of this leave request (could be processed_date, processed_date_2, or processed_date_3)
            $current_leave_header = DB::table('leave_headers')
                ->where('id', $id)
                ->select('processed_date', 'processed_date_2', 'processed_date_3', 'employee_id', 'leave_type_id')
                ->first();
            
            $processed_date = null;
            if ($current_leave_header) {
                // Get the earliest processed date (when it was first processed)
                $dates = array_filter([
                    $current_leave_header->processed_date,
                    $current_leave_header->processed_date_2,
                    $current_leave_header->processed_date_3
                ]);
                if (!empty($dates)) {
                    $processed_date = min($dates);
                }
            }

            // Calculate balances
            $vl_balance = $leave_credeits_vl[0]->credits;
            $sl_balance = isset($leave_credeits_sl[0]) ? $leave_credeits_sl[0]->credits : 0;
            $vl_less = ($dtl->leave_type_id == 16) ? ($data_with_pay[0]->with_pay ?? 0) : 0;
            $sl_less = ($dtl->leave_type_id == 3) ? ($data_with_pay[0]->with_pay ?? 0) : 0;
            
            // Initialize original balances and balance after request
            $vl_original_balance = $vl_balance;
            $sl_original_balance = $sl_balance;
            $vl_balance_after_request = $vl_balance;
            $sl_balance_after_request = $sl_balance;
            
            $vacationTypeId = 16;
            $sickTypeId = 3;
            
            if ($processed_date && $current_leave_header) {
                $employee_id = $current_leave_header->employee_id;
                $is_vacation_leave = ($current_leave_header->leave_type_id == $vacationTypeId || 
                                      stripos($dtl->leave_type_name ?? '', 'vacation') !== false);
                $is_sick_leave = ($current_leave_header->leave_type_id == $sickTypeId || 
                                 stripos($dtl->leave_type_name ?? '', 'sick') !== false);
                
                // Calculate sum of all VL deductions processed AFTER this request
                $subsequent_vl_deductions = DB::table('leave_headers as a')
                    ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                    ->where('a.employee_id', $employee_id)
                    ->where('a.leave_type_id', $vacationTypeId)
                    ->where('a.id', '!=', $id)
                    ->where(function($query) use ($processed_date, $id) {
                        $query->where(function($q) use ($processed_date) {
                            $q->where('a.processed_date', '>', $processed_date)
                              ->orWhere('a.processed_date_2', '>', $processed_date)
                              ->orWhere('a.processed_date_3', '>', $processed_date);
                        })
                        ->orWhere(function($q) use ($processed_date, $id) {
                            // Also include requests processed on the same date but with later time or higher ID
                            $q->where(function($subQ) use ($processed_date) {
                                $subQ->where('a.processed_date', '=', $processed_date)
                                     ->orWhere('a.processed_date_2', '=', $processed_date)
                                     ->orWhere('a.processed_date_3', '=', $processed_date);
                            })
                            ->where('a.id', '>', $id);
                        });
                    })
                    ->where(function($query) {
                        $query->where('a.approved', true)
                              ->orWhere('a.approved_2', true)
                              ->orWhere('a.approved_3', true);
                    })
                    ->where(function($query) {
                        $query->whereNull('a.is_cancel')
                              ->orWhere('a.is_cancel', '!=', 1);
                    })
                    ->select(DB::raw("COALESCE(SUM(b.with_pay), 0) as total_deduction"))
                    ->value('total_deduction');
                
                // Calculate sum of all SL deductions processed AFTER this request
                $subsequent_sl_deductions = DB::table('leave_headers as a')
                    ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                    ->where('a.employee_id', $employee_id)
                    ->where('a.leave_type_id', $sickTypeId)
                    ->where('a.id', '!=', $id)
                    ->where(function($query) use ($processed_date, $id) {
                        $query->where(function($q) use ($processed_date) {
                            $q->where('a.processed_date', '>', $processed_date)
                              ->orWhere('a.processed_date_2', '>', $processed_date)
                              ->orWhere('a.processed_date_3', '>', $processed_date);
                        })
                        ->orWhere(function($q) use ($processed_date, $id) {
                            $q->where(function($subQ) use ($processed_date) {
                                $subQ->where('a.processed_date', '=', $processed_date)
                                     ->orWhere('a.processed_date_2', '=', $processed_date)
                                     ->orWhere('a.processed_date_3', '=', $processed_date);
                            })
                            ->where('a.id', '>', $id);
                        });
                    })
                    ->where(function($query) {
                        $query->where('a.approved', true)
                              ->orWhere('a.approved_2', true)
                              ->orWhere('a.approved_3', true);
                    })
                    ->where(function($query) {
                        $query->whereNull('a.is_cancel')
                              ->orWhere('a.is_cancel', '!=', 1);
                    })
                    ->select(DB::raw("COALESCE(SUM(b.with_pay), 0) as total_deduction"))
                    ->value('total_deduction');
                
                // Calculate original balances
                // For VL: add this request's deduction (if it's VL) + all subsequent VL deductions
                $this_vl_deduction = $is_vacation_leave ? ($data_with_pay[0]->with_pay ?? 0) : 0;
                $vl_original_balance = $vl_balance + $this_vl_deduction + ($subsequent_vl_deductions ?? 0);
                
                // For SL: add this request's deduction (if it's SL) + all subsequent SL deductions
                $this_sl_deduction = $is_sick_leave ? ($data_with_pay[0]->with_pay ?? 0) : 0;
                $sl_original_balance = $sl_balance + $this_sl_deduction + ($subsequent_sl_deductions ?? 0);
                
                // Calculate balance immediately after this request was processed
                $vl_balance_after_request = $vl_original_balance - $this_vl_deduction;
                $sl_balance_after_request = $sl_original_balance - $this_sl_deduction;
            } else {
                // If no processed_date, fallback to simple calculation (for pending/unprocessed requests)
                // Credits haven't been deducted yet, so just add this request's deduction
                if (stripos($dtl->leave_type_name ?? '', 'vacation') !== false) {
                    $vl_original_balance = $vl_balance + ($data_with_pay[0]->with_pay ?? 0);
                    $vl_balance_after_request = $vl_balance; // Current balance (before this request is processed)
                } else {
                    $vl_original_balance = $vl_balance;
                    $vl_balance_after_request = $vl_balance;
                }
                if (stripos($dtl->leave_type_name ?? '', 'sick') !== false) {
                    $sl_original_balance = $sl_balance + ($data_with_pay[0]->with_pay ?? 0);
                    $sl_balance_after_request = $sl_balance; // Current balance (before this request is processed)
                } else {
                    $sl_original_balance = $sl_balance;
                    $sl_balance_after_request = $sl_balance;
                }
            }
            
            // For backward compatibility, keep vl_final and sl_final but use balance_after_request
            // Use balance_after_request for VL if this is a vacation leave request, otherwise use current balance
            $vl_final = (stripos($dtl->leave_type_name ?? '', 'vacation') !== false) ? max(0, $vl_balance_after_request) : max(0, $vl_balance);
            // Use balance_after_request for SL if this is a sick leave request, otherwise use current balance
            $sl_final = (stripos($dtl->leave_type_name ?? '', 'sick') !== false) ? max(0, $sl_balance_after_request) : max(0, $sl_balance);

            // Build DOCX with PhpWord
            $phpWord = new PhpWord();
            
            // A4 Portrait section with margins
            $section = $phpWord->addSection([
                'marginTop' => 600,
                'marginRight' => 600,
                'marginBottom' => 600,
                'marginLeft' => 600
            ]);

            // Define styles
            $smallTextStyle = ['size' => 9, 'spaceAfter' => 0];
            $tinyTextStyle = ['size' => 8, 'spaceAfter' => 0];
            $boldSmallStyle = ['bold' => true, 'size' => 9, 'spaceAfter' => 0];
            $boldTinyStyle = ['bold' => true, 'size' => 8, 'spaceAfter' => 0];
            $italicSmallStyle = ['italic' => true, 'size' => 9, 'spaceAfter' => 0];
            $centerStyle = ['alignment' => Jc::CENTER];
            $rightStyle = ['alignment' => Jc::RIGHT];

            // Header: Civil Service Form No. 6 and Annex A
            $headerTable = $section->addTable(['width' => 100 * 50]);
            $headerRow = $headerTable->addRow();
            $headerRow->addCell(5000)->addText('Civil Service Form No. 6', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $headerRow->addCell(5000)->addText('Annex A', $boldSmallStyle, array_merge($rightStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            $headerRow = $headerTable->addRow();
            $headerRow->addCell(5000)->addText('Revised 2020', $smallTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);

            // Main header with logo: Republic of the Philippines, PTTC, etc.
            // Create a table to position logo on left and text in center
            $headerTable2 = $section->addTable(['width' => 100 * 50]);
            $headerRow2 = $headerTable2->addRow();
            
            // Add an empty cell at the start for left margin (increased for more visible spacing)
            $leftMarginCell = $headerRow2->addCell(1200, ['valign' => 'top']);
            $leftMarginCell->addText('', $smallTextStyle); // Empty cell creates left margin
            
            // Left cell for logo (matches blade template: width: 70px, float: left)
            $logoCell = $headerRow2->addCell(1500, ['valign' => 'top']);
            if ($logoImage && file_exists($logoImage)) {
                try {
                    $logoCell->addImage($logoImage, [
                        'width' => 50,
                        'height' => 50,
                        'alignment' => Jc::LEFT,
                        'wrappingStyle' => 'inline',
                        'spaceAfter' => 0,
                        'spaceBefore' => 0,
                    ]);
                } catch (\Exception $e) {
                    // If image fails to load, just leave empty
                }
            }
            
            // Center cell for header text (centered alignment)
            // Adjust width to account for left margin cell (1200) - reduced from 7000 to 5800
            $headerTextCell = $headerRow2->addCell(5800, ['valign' => 'top']);
            $headerTextCell->addText('Republic of the Philippines', $smallTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            $headerTextCell->addText(CompanyHelper::getName(), $smallTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            $headerTextCell->addText(CompanyHelper::getAddress(), $smallTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            $headerTextCell->addText('APPLICATION FOR LEAVE', $boldSmallStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            
            // Right cell (empty for spacing)
            $headerRow2->addCell(1500, ['valign' => 'top']);

            // Table 1: Office/Department and Name
            $table1 = $section->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => '000000'
            ]);
            $row1 = $table1->addRow();
            $row1->addCell(3600, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => '000000', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF'])->addText('1. OFFICE/DEPARTMENT', $smallTextStyle, ['spaceAfter' => 0]);
            $row1->addCell(1850, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF'])->addText('2. NAME :', $smallTextStyle, ['spaceAfter' => 0]);
            $row1->addCell(1850, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF'])->addText('(Last)', $smallTextStyle, ['spaceAfter' => 0]);
            $row1->addCell(1850, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF'])->addText('(First)', $smallTextStyle, ['spaceAfter' => 0]);
            $row1->addCell(1850, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => '000000'])->addText('(Middle)', $smallTextStyle, ['spaceAfter' => 0]);
            
            $row2 = $table1->addRow();
            $row2->addCell(3600, ['borderTopSize' => 6, 'borderTopColor' => 'FFFFFF', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => '000000', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF'])->addText($dtl->department ?? '', $smallTextStyle, ['spaceAfter' => 0]);
            $row2->addCell(1850, ['borderTopSize' => 6, 'borderTopColor' => 'FFFFFF', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF'])->addText('', $smallTextStyle, ['spaceAfter' => 0]);
            $row2->addCell(1850, ['borderTopSize' => 6, 'borderTopColor' => 'FFFFFF', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF'])->addText($dtl->last_name ?? '', $smallTextStyle, ['spaceAfter' => 0]);
            $row2->addCell(1850, ['borderTopSize' => 6, 'borderTopColor' => 'FFFFFF', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF'])->addText($dtl->first_name ?? '', $smallTextStyle, ['spaceAfter' => 0]);
            $row2->addCell(1850, ['borderTopSize' => 6, 'borderTopColor' => 'FFFFFF', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => '000000'])->addText($dtl->middle_name ?? '', $smallTextStyle, ['spaceAfter' => 0]);

            // Table 2: Date of Filing, Position, Salary
            $table2 = $section->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => '000000'
            ]);
            $row3 = $table2->addRow();
            // DATE OF FILING - Label
            $row3->addCell(2000, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => '000000', 'borderLeftSize' => 6, 'borderLeftColor' => '000000', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF'])->addText('3. DATE OF FILING', $smallTextStyle, ['spaceAfter' => 0]);
            // DATE OF FILING - Data
            $row3->addCell(1500, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => '000000', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => '000000'])->addText(date('M d, Y', strtotime($dtl->date_from ?? 'now')), array_merge($smallTextStyle, ['underline' => 'single']), [ 'spaceAfter' => 0]);
            
            // POSITION - Label
            $row3->addCell(1200, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => '000000', 'borderLeftSize' => 6, 'borderLeftColor' => '000000', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF'])->addText('4. POSITION', $smallTextStyle, ['spaceAfter' => 0]);
            // POSITION - Data
            $row3->addCell(3000, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => '000000', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => '000000'])->addText($dtl->position ?? '', $smallTextStyle, ['underline' => 'single', 'alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            
            // SALARY - Label
            $row3->addCell(1700, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => '000000', 'borderLeftSize' => 6, 'borderLeftColor' => '000000', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF'])->addText('5. SALARY', $smallTextStyle, ['spaceAfter' => 0]);
            // SALARY - Data
            $row3->addCell(1600, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => '000000', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => '000000'])->addText(number_format($dtl->salary ?? 0, 2, '.', ','), $smallTextStyle, ['underline' => 'single', 'spaceAfter' => 0]);

            // Table 3: Details of Application header
            $table3 = $section->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => '000000'
            ]);
            $row4 = $table3->addRow();
            $row4->addCell(11000, ['borderSize' => 6, 'borderColor' => '000000'])->addText('6. DETAILS OF APPLICATION', $boldSmallStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));

            // Table 4: Leave types and details (two columns)
            $table4 = $section->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => '000000'
            ]);
            $row5 = $table4->addRow();
            
            // Left column: Leave types
            // Align with section 7's divider at 5500
            $leftCol = $row5->addCell(5500, ['borderSize' => 6, 'borderColor' => '000000', 'valign' => 'top']);

            // Helper function to add checkbox row - creates editable checkbox cells
            // Format: leave type name in bold, legal reference in regular font
            $addCheckboxRow = function($cell, $checked, $leaveTypeName, $legalRef = '') use ($smallTextStyle, $tinyTextStyle, $boldTinyStyle, $centerStyle) {
                $checkboxTable = $cell->addTable(['width' => 100 * 50]);
                
                // Add very small top spacer row with white borders (matching checkbox row structure)
                $topSpacerRow = $checkboxTable->addRow();
                $topSpacerRow->addCell(200, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('x',  ['size' => 0.8],['spaceAfter' => 0, 'spaceBefore' => 0]);
                $topSpacerRow->addCell(200, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('x',  ['size' => 0.8],['spaceAfter' => 0, 'spaceBefore' => 0]);
                $topSpacerRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('x',  ['size' => 0.8],['spaceAfter' => 0, 'spaceBefore' => 0]);
                $topSpacerRow->addCell(4500, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('x',  ['size' => 0.8],['spaceAfter' => 0, 'spaceBefore' => 0]);
                
                // Main checkbox row
                $checkboxRow = $checkboxTable->addRow();
                
                // Add empty spacer column for left margin
                $spacerCell = $checkboxRow->addCell(200, ['borderTopSize' => 6, 'borderTopColor' => 'FFFFFF', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF']);
                $spacerCell->addText('', $tinyTextStyle);
                
                // Small bordered cell for checkbox (editable) - split into 2 cells: top (checkbox) and bottom (blank)
                $checkboxCell = $checkboxRow->addCell(200, [
                    'borderSize' => 6,
                    'borderColor' => 'FFFFFF',
                    'valign' => 'top'
                ]);
                // Create nested table with 2 rows: top for checkbox, bottom blank
                $checkboxInnerTable = $checkboxCell->addTable(['width' => 100 * 50]);
                // Top row: actual checkbox
                $checkboxTopRow = $checkboxInnerTable->addRow();
                $checkboxTopCell = $checkboxTopRow->addCell(200, [
                    'borderSize' => 6,
                    'borderColor' => '000000',
                    'valign' => 'center',
                    'spaceAfter' => 0,
                    'spaceBefore' => 0
                ]);
                $checkboxTopCell->addText($checked ? 'X' : '', $tinyTextStyle, ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]);
                // Bottom row: blank cell
                //$checkboxBottomRow = $checkboxInnerTable->addRow();
                //$checkboxBottomCell = $checkboxBottomRow->addCell(200, [
                //    'borderSize' => 0,
                //    'borderColor' => 'FFFFFf',
                //    'valign' => 'top'
                //]);
                //$checkboxBottomCell->addText('', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
                
                // Add small spacer between checkbox and label
                $spacerCell2 = $checkboxRow->addCell(100, ['borderTopSize' => 6, 'borderTopColor' => 'FFFFFF', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => 'ffffff']);
                $spacerCell2->addText('', $tinyTextStyle);
                
                // Label cell (editable) - with bold leave type name and regular legal reference
                // Adjust width to account for smaller checkbox (200 + 200 + 100 = 500, so 4500 for label)
                $labelCell = $checkboxRow->addCell(4500, ['valign' => 'top']); // Changed to 'top' to align with checkbox
                $textRun = $labelCell->addTextRun(['spaceAfter' => 0]);
                $textRun->addText($leaveTypeName, array_merge($tinyTextStyle, ['bold' => true]), ['spaceAfter' => 0]);
                if (!empty($legalRef)) {
                    $textRun->addText(' ' . $legalRef, $tinyTextStyle, ['spaceAfter' => 0]);
                }
                
            };

            $addCheckboxRow($leftCol, $isLeaveType('vacation leave', 1), 'Vacation Leave', '(Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292)');
            $addCheckboxRow($leftCol, $isLeaveType('forced leave', 6) || $isLeaveType('mandatory leave', 6), 'Mandatory/Forced Leave', '(Sec. 25, Rule XVI, Omnibus Rules Implementing E.O. No. 292)');
            $addCheckboxRow($leftCol, $isLeaveType('sick leave', 2), 'Sick Leave', '(Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292)');
            $addCheckboxRow($leftCol, $isLeaveType('maternity leave', 7), 'Maternity Leave', '(R.A. No. 11210 / IRR issued by CSC, DOLE and SSS)');
            $addCheckboxRow($leftCol, $isLeaveType('paternity leave', 8), 'Paternity Leave', '(R.A. No. 8187 / CSC MC No. 71, s. 1998, as amended)');
            $addCheckboxRow($leftCol, $isLeaveType('special privilege leave', 3), 'Special Privilege Leave', '(Sec. 21, Rule XVI, Omnibus Rules Implementing E.O. No. 292)');
            $addCheckboxRow($leftCol, $isLeaveType('solo parent leave', 4), 'Solo Parent Leave', '(RA No. 8972 / CSC MC No. 8, s. 2004)');
            $addCheckboxRow($leftCol, $isLeaveType('study leave', 5), 'Study Leave', '(Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292)');
            $addCheckboxRow($leftCol, strpos(strtolower($dtl->leave_type_name ?? ''), 'vawc') !== false, '10-Day VAWC Leave', '(RA No. 9262 / CSC MC No. 15, s. 2005)');
            $addCheckboxRow($leftCol, strpos(strtolower($dtl->leave_type_name ?? ''), 'rehabilitation') !== false, 'Rehabilitation Privilege', '(Sec. 55, Rule XVI, Omnibus Rules Implementing E.O. No. 292)');
            $addCheckboxRow($leftCol, strpos(strtolower($dtl->leave_type_name ?? ''), 'special leave benefits') !== false, 'Special Leave Benefits for Women', '(RA No. 9710 / CSC MC No. 25, s. 2010)');
            $addCheckboxRow($leftCol, $isLeaveType('special emergency (calamity) leave', 11), 'Special Emergency (Calamity) Leave', '(CSC MC No. 2, s. 2012, as amended)');
            $addCheckboxRow($leftCol, strpos(strtolower($dtl->leave_type_name ?? ''), 'adoption') !== false, 'Adoption Leave', '(R.A. No. 8552)');
            
            // Others: with underline (long horizontal line for write-in)
            $othersTextRun = $leftCol->addTextRun();
            $othersTextRun->addText('Others:', array_merge($smallTextStyle, ['bold' => true]));
            $otherType = $getOtherLeaveType();
            if (!empty($otherType)) {
                // If there's an other type, show it with underline
                $othersTextRun->addText(' ' . $otherType, array_merge($smallTextStyle, ['underline' => 'single']));
            } else {
                // Add long underline space for write-in (spaces with underline to create a line)
                $othersTextRun->addText(' ' . str_repeat(' ', 60), array_merge($smallTextStyle, ['underline' => 'single']));
            }

            // Right column: Details of Leave
            $rightCol = $row5->addCell(5500, ['borderSize' => 6, 'borderColor' => '000000', 'valign' => 'top', 'spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // Create a padding table to add margin inside
            $paddingTable = $rightCol->addTable(['width' => 100 * 50]);
            $paddingMiddleRow = $paddingTable->addRow();
            // Left padding
            $paddingLeftCell = $paddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $paddingLeftCell->addText('', $tinyTextStyle);
            
            // Center cell with the actual content table
            $paddingCenterCell = $paddingMiddleRow->addCell(5300, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'top']);
            
            // Wrap contents in a table with 1 column/1 row to create a box effect
            $rightColTable = $paddingCenterCell->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => 'FFFFFF'
            ]);
            $rightColRow = $rightColTable->addRow();
            $rightColCell = $rightColRow->addCell(5300, [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'top'
            ]);
            
            // Right padding
            $paddingRightCell = $paddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $paddingRightCell->addText('', $tinyTextStyle);
            
            
            $rightColCell->addText('6.B DETAILS OF LEAVE', $boldTinyStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightColCell->addText('In case of Vacation/Special Privilege Leave:', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            $addCheckboxRow($rightColCell, $dtl->incase_vacation_leave_id == 1, 'Within the Philippines');
            if ($dtl->incase_vacation_leave_id == 1) {
                $rightColCell->addText($dtl->incase_vacation_leave_specify ?? '', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            }
            
            $addCheckboxRow($rightColCell, $dtl->incase_vacation_leave_id == 2, 'Abroad (Specify)');
            if ($dtl->incase_vacation_leave_id == 2) {
                $rightColCell->addText($dtl->incase_vacation_leave_specify ?? '', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            }
            
            $rightColCell->addText('In case of Sick Leave:', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $addCheckboxRow($rightColCell, $dtl->incase_sick_leave_id == 1, 'In Hospital (Specify Illness)');
            if ($dtl->incase_sick_leave_id == 1) {
                $rightColCell->addText($dtl->incase_sick_leave_specify ?? '', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            }
            $addCheckboxRow($rightColCell, $dtl->incase_sick_leave_id == 2, 'Out Patient (Specify Illness)');
            if ($dtl->incase_sick_leave_id == 2) {
                $rightColCell->addText($dtl->incase_sick_leave_specify ?? '', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            }
            
            $rightColCell->addText('In case of Special Leave Benefits for Women:', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightColCell->addText('(Specify Illness)', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightColCell->addText($dtl->incase_special_leave_specify ?? '', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            $rightColCell->addText('In case of Study Leave:', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $addCheckboxRow($rightColCell, $dtl->incase_study_leave_id == 1, 'Completion of Master\'s Degree');
            $addCheckboxRow($rightColCell, $dtl->incase_study_leave_id == 2, 'BAR/Board Examination Review');
            
            $rightColCell->addText('Other purpose:', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $addCheckboxRow($rightColCell, $dtl->other_purpose_id == 1, 'Monetization of Leave Credits');
            $addCheckboxRow($rightColCell, $dtl->other_purpose_id == 2, 'Terminal Leave');

            // Table 5: Number of days and Commutation
            $row6 = $table4->addRow();
            $leftCell = $row6->addCell(5500, ['borderSize' => 6, 'borderColor' => '000000', 'valign' => 'top']);
            
            // Create a padding table to add margin inside (same as 6.B)
            $leftPaddingTable = $leftCell->addTable(['width' => 100 * 50]);
            $leftPaddingMiddleRow = $leftPaddingTable->addRow();
            // Left padding
            $leftPaddingLeftCell = $leftPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $leftPaddingLeftCell->addText('', $tinyTextStyle);
            
            // Center cell with the actual content table
            $leftPaddingCenterCell = $leftPaddingMiddleRow->addCell(5300, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'top']);
            
            // Wrap contents in a table with 1 column/1 row to create a box effect
            $leftContentTable = $leftPaddingCenterCell->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => 'FFFFFF'
            ]);
            $leftContentRow = $leftContentTable->addRow();
            $leftContentCell = $leftContentRow->addCell(5300, [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'top'
            ]);
            
            // Right padding
            $leftPaddingRightCell = $leftPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $leftPaddingRightCell->addText('', $tinyTextStyle);
            
            // Create a table for label-value pairs in rows
            $leftContentTable2 = $leftContentCell->addTable(['width' => 100 * 50]);
            
            // Row 1: NUMBER OF WORKING DAYS APPLIED FOR
            $leftRow1 = $leftContentTable2->addRow();
            $leftRow1->addCell(12000, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('6.C NUMBER OF WORKING DAYS APPLIED FOR', $boldTinyStyle, ['spaceAfter' => 0]);
            $leftRow1->addCell(2400, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText($data_with_pay[0]->total_applied_days ?? '0', $smallTextStyle, ['spaceAfter' => 0]);
            
            // Row 2: INCLUSIVE DATES
            $leftRow2 = $leftContentTable2->addRow();
            $inclusiveDates = ($dtl->date_from == $dtl->date_to) 
                ? date('m-d-Y', strtotime($dtl->date_from))
                : date('m-d-Y', strtotime($dtl->date_from)) . ' - ' . date('m-d-Y', strtotime($dtl->date_to));
            // Combine label and data in one cell
            $inclusiveDatesCell = $leftRow2->addCell(4800, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $inclusiveDatesTextRun = $inclusiveDatesCell->addTextRun(['spaceAfter' => 0]);
            $inclusiveDatesTextRun->addText('INCLUSIVE DATES ', $boldTinyStyle);
            $inclusiveDatesTextRun->addText($inclusiveDates, $smallTextStyle);
            
            $rightCell = $row6->addCell(5500, ['borderSize' => 6, 'borderColor' => '000000', 'valign' => 'top']);
            
            // Create a padding table to add margin inside (same as 6.C)
            $rightPaddingTable = $rightCell->addTable(['width' => 100 * 50]);
            $rightPaddingMiddleRow = $rightPaddingTable->addRow();
            // Left padding
            $rightPaddingLeftCell = $rightPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $rightPaddingLeftCell->addText('', $tinyTextStyle);
            
            // Center cell with the actual content table
            $rightPaddingCenterCell = $rightPaddingMiddleRow->addCell(5300, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'top']);
            
            // Wrap contents in a table with 1 column/1 row to create a box effect
            $rightContentTable = $rightPaddingCenterCell->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => 'FFFFFF'
            ]);
            $rightContentRow = $rightContentTable->addRow();
            $rightContentCell = $rightContentRow->addCell(5300, [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'top'
            ]);
            
            // Right padding
            $rightPaddingRightCell = $rightPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $rightPaddingRightCell->addText('', $tinyTextStyle);
            
            $rightContentCell->addText('6.D COMMUTATION', $boldTinyStyle, ['spaceAfter' => 0]);
            $addCheckboxRow($rightContentCell, $dtl->commutation_id == 2, 'Not Requested');
            $addCheckboxRow($rightContentCell, $dtl->commutation_id == 1, 'Requested');
            $rightContentCell->addText('(Signature of Applicant)', $smallTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));

            // Table 6: Details of Action on Application
            $table6 = $section->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => '000000'
            ]);
            $row7 = $table6->addRow();
            $row7->addCell(11000, ['borderSize' => 6, 'borderColor' => '000000', 'gridSpan' => 2])
                ->addText('7. DETAILS OF ACTION ON APPLICATION', $boldSmallStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            
            $row8 = $table6->addRow();
            $leftAction = $row8->addCell(5500, ['borderSize' => 6, 'borderColor' => '000000', 'valign' => 'top']);
            
            // Create a padding table to add margin inside (same as 6.C)
            $leftActionPaddingTable = $leftAction->addTable(['width' => 100 * 50]);
            $leftActionPaddingMiddleRow = $leftActionPaddingTable->addRow();
            // Left padding
            $leftActionPaddingLeftCell = $leftActionPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $leftActionPaddingLeftCell->addText('', $tinyTextStyle);
            
            // Center cell with the actual content table
            $leftActionPaddingCenterCell = $leftActionPaddingMiddleRow->addCell(5300, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'top']);
            
            // Wrap contents in a table with 1 column/1 row to create a box effect
            $leftActionContentTable = $leftActionPaddingCenterCell->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => 'FFFFFF'
            ]);
            $leftActionContentRow = $leftActionContentTable->addRow();
            $leftActionContentCell = $leftActionContentRow->addCell(5300, [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'top'
            ]);
            
            // Right padding
            $leftActionPaddingRightCell = $leftActionPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $leftActionPaddingRightCell->addText('', $tinyTextStyle);
            
            $leftActionContentCell->addText('7.A CERTIFICATION OF LEAVE CREDITS', $boldTinyStyle, ['spaceAfter' => 0]);
            $leftActionContentCell->addText('As of ' . date('m-d-Y'), $tinyTextStyle, ['spaceAfter' => 0]);
            
            // Leave credits table
            $creditsTable = $leftActionContentCell->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => '000000'
            ]);
            $creditsHeader = $creditsTable->addRow();
            $creditsHeader->addCell(3333, ['borderSize' => 6, 'borderColor' => '000000'])->addText('', $tinyTextStyle);
            $creditsHeader->addCell(3333, ['borderSize' => 6, 'borderColor' => '000000'])->addText('Vacation Leave', $tinyTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            $creditsHeader->addCell(3334, ['borderSize' => 6, 'borderColor' => '000000'])->addText('Sick Leave', $tinyTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            
            $creditsRow1 = $creditsTable->addRow();
            $creditsRow1->addCell(3333, ['borderSize' => 6, 'borderColor' => '000000'])->addText('Total Earned', $italicSmallStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            // Use original balance for VL if this is a vacation leave request, otherwise use current balance
            $vl_total_earned = (stripos($dtl->leave_type_name ?? '', 'vacation') !== false) ? $vl_original_balance : $vl_balance;
            $creditsRow1->addCell(3333, ['borderSize' => 6, 'borderColor' => '000000'])->addText(number_format(max(0, $vl_total_earned), 3, '.', ','), $smallTextStyle, $centerStyle);
            // Use original balance for SL if this is a sick leave request, otherwise use current balance
            $sl_total_earned = (stripos($dtl->leave_type_name ?? '', 'sick') !== false) ? $sl_original_balance : $sl_balance;
            $creditsRow1->addCell(3334, ['borderSize' => 6, 'borderColor' => '000000'])->addText(number_format(max(0, $sl_total_earned), 3, '.', ','), $smallTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            
            $creditsRow2 = $creditsTable->addRow();
            $creditsRow2->addCell(3333, ['borderSize' => 6, 'borderColor' => '000000'])->addText('Less this application', $italicSmallStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            $creditsRow2->addCell(3333, ['borderSize' => 6, 'borderColor' => '000000'])->addText(number_format($vl_less, 3, '.', ','), $smallTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            $creditsRow2->addCell(3334, ['borderSize' => 6, 'borderColor' => '000000'])->addText(number_format($sl_less, 3, '.', ','), $smallTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            
            $creditsRow3 = $creditsTable->addRow();
            $creditsRow3->addCell(3333, ['borderSize' => 6, 'borderColor' => '000000'])->addText('Balance', $italicSmallStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            $creditsRow3->addCell(3333, ['borderSize' => 6, 'borderColor' => '000000'])->addText(number_format($vl_final, 3, '.', ','), $smallTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            $creditsRow3->addCell(3334, ['borderSize' => 6, 'borderColor' => '000000'])->addText(number_format($sl_final, 3, '.', ','), $smallTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            $leftActionContentCell->addText('', $smallTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftActionContentCell->addText($leave_signatories[0]->approver_1 ?? '', $smallTextStyle, array_merge($centerStyle, [ 'spaceAfter' => 0, 'spaceBefore' => 0]));
            $leftActionContentCell->addText('(Authorized Officer)', $smallTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            
            $rightAction = $row8->addCell(5500, ['borderSize' => 6, 'borderColor' => '000000', 'valign' => 'top']);
            
            // Create a padding table to add margin inside (same as 6.C)
            $rightActionPaddingTable = $rightAction->addTable(['width' => 100 * 50]);
            $rightActionPaddingMiddleRow = $rightActionPaddingTable->addRow();
            // Left padding
            $rightActionPaddingLeftCell = $rightActionPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $rightActionPaddingLeftCell->addText('', $tinyTextStyle);
            
            // Center cell with the actual content table
            $rightActionPaddingCenterCell = $rightActionPaddingMiddleRow->addCell(5300, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'top']);
            
            // Wrap contents in a table with 1 column/1 row to create a box effect
            $rightActionContentTable = $rightActionPaddingCenterCell->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => 'FFFFFF'
            ]);
            $rightActionContentRow = $rightActionContentTable->addRow();
            $rightActionContentCell = $rightActionContentRow->addCell(5300, [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'top'
            ]);
            
            // Right padding
            $rightActionPaddingRightCell = $rightActionPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $rightActionPaddingRightCell->addText('', $tinyTextStyle);
            
            $rightActionContentCell->addText('7.B RECOMMENDATION', $boldTinyStyle, ['spaceAfter' => 0]);
            
            // For Approval with remarks on same line - use addCheckboxRow but modify to include remarks inline
            $approvalTable = $rightActionContentCell->addTable(['width' => 100 * 50]);
            $approvalRow = $approvalTable->addRow();
            // Checkbox cell (small)
            $approvalCheckboxCell = $approvalRow->addCell(200, [
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top'
            ]);
            $approvalCheckboxInnerTable = $approvalCheckboxCell->addTable(['width' => 100 * 50]);
            $approvalCheckboxInnerRow = $approvalCheckboxInnerTable->addRow();
            $approvalCheckboxInnerCell = $approvalCheckboxInnerRow->addCell(200, [
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
                'spaceAfter' => 0,
                'spaceBefore' => 0
            ]);
            $approvalCheckboxInnerCell->addText('', $tinyTextStyle, ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]);
            // Text cell with "For Approval" and remarks on same line
            $approvalTextCell = $approvalRow->addCell(5100, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'top']);
            $approvalTextRun = $approvalTextCell->addTextRun(['spaceAfter' => 0]);
            $approvalTextRun->addText('For Approval ', $tinyTextStyle);

            // For disapproval due to with remarks on same line
            $disapprovalTable = $rightActionContentCell->addTable(['width' => 100 * 50]);
            $disapprovalRow = $disapprovalTable->addRow();
            // Checkbox cell (small)
            $disapprovalCheckboxCell = $disapprovalRow->addCell(200, [
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top'
            ]);
            $disapprovalCheckboxInnerTable = $disapprovalCheckboxCell->addTable(['width' => 100 * 50]);
            $disapprovalCheckboxInnerRow = $disapprovalCheckboxInnerTable->addRow();
            $disapprovalCheckboxInnerCell = $disapprovalCheckboxInnerRow->addCell(200, [
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
                'spaceAfter' => 0,
                'spaceBefore' => 0
            ]);
            $disapprovalCheckboxInnerCell->addText('', $tinyTextStyle, ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]);
            // Text cell with "For disapproval due to" and remarks on same line
            $disapprovalTextCell = $disapprovalRow->addCell(5100, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'top']);
            $disapprovalTextRun = $disapprovalTextCell->addTextRun(['spaceAfter' => 0]);
            $disapprovalTextRun->addText('For disapproval due to ', $tinyTextStyle);
            $disapprovalTextRun->addText($dtl->disapproved_remarks ?? '________________________________', $tinyTextStyle);
            // Add 2 blank cells above signatory
            $rightActionContentCell->addText('', $smallTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightActionContentCell->addText('', $smallTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // Signatory name with underline
            $signatoryTextRun = $rightActionContentCell->addTextRun(array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));
            $signatoryTextRun->addText($leave_signatories[0]->approver_2 ?? '', array_merge($smallTextStyle, ['underline' => 'single']));
            $rightActionContentCell->addText('(Authorized Officer)', $smallTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));

            // Table 7: Approved for / Disapproved due to
            $row9 = $table6->addRow();
            $approvedCell = $row9->addCell(5500, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => '000000', 'borderRightSize' => 6, 'borderRightColor' => 'FFFFFF', 'valign' => 'top']);
            
            // Create a padding table to add margin inside (same as 6.C)
            $approvedPaddingTable = $approvedCell->addTable(['width' => 100 * 50]);
            $approvedPaddingMiddleRow = $approvedPaddingTable->addRow();
            // Left padding
            $approvedPaddingLeftCell = $approvedPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $approvedPaddingLeftCell->addText('', $tinyTextStyle);
            
            // Center cell with the actual content table
            $approvedPaddingCenterCell = $approvedPaddingMiddleRow->addCell(5300, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'top']);
            
            // Wrap contents in a table with 1 column/1 row to create a box effect
            $approvedContentTable = $approvedPaddingCenterCell->addTable([
                'width' => 100 * 50,
                'borderTopSize' => 6,
                'borderTopColor' => 'FFFFFF',
                'borderBottomSize' => 6,
                'borderBottomColor' => 'FFFFFF',
                'borderLeftSize' => 6,
                'borderLeftColor' => '000000',
                'borderRightSize' => 6,
                'borderRightColor' => 'FFFFFF',
            ]);
            $approvedContentRow = $approvedContentTable->addRow();
            $approvedContentCell = $approvedContentRow->addCell(5300, [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'top'
            ]);
            
            // Right padding
            $approvedPaddingRightCell = $approvedPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $approvedPaddingRightCell->addText('', $tinyTextStyle);
            
            $approvedContentCell->addText('7.C APPROVED FOR:', $boldTinyStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // Create a table for aligned bottom borders
            $approvedTable = $approvedContentCell->addTable(['width' => 100 * 50]);
            
            // Days with pay - value with bottom border
            $withPayRow = $approvedTable->addRow();
            $withPayValueCell = $withPayRow->addCell(2000, [
                'borderTopSize' => 0,
                'borderTopColor' => 'FFFFFF',
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
                'borderLeftSize' => 0,
                'borderLeftColor' => 'FFFFFF',
                'borderRightSize' => 0,
                'borderRightColor' => 'FFFFFF',
                'valign' => 'bottom'
            ]);
            $withPayValueCell->addText(number_format($data_with_pay[0]->with_pay ?? 0, 2, '.', ','), $smallTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $withPayLabelCell = $withPayRow->addCell(3300, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'bottom'
            ]);
            $withPayLabelCell->addText(' days with pay', $smallTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // Days without pay - value with bottom border
            $withoutPayRow = $approvedTable->addRow();
            $withoutPayValueCell = $withoutPayRow->addCell(2000, [
                'borderTopSize' => 0,
                'borderTopColor' => 'FFFFFF',
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
                'borderLeftSize' => 0,
                'borderLeftColor' => 'FFFFFF',
                'borderRightSize' => 0,
                'borderRightColor' => 'FFFFFF',
                'valign' => 'bottom'
            ]);
            $withoutPayValueCell->addText(number_format($data_with_pay[0]->without_pay ?? 0, 2, '.', ','), $smallTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $withoutPayLabelCell = $withoutPayRow->addCell(3300, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'bottom'
            ]);
            $withoutPayLabelCell->addText(' days without pay', $smallTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // Others (Specify) - with bottom border aligned
            $othersRow = $approvedTable->addRow();
            $othersValueCell = $othersRow->addCell(2000, [
                'borderTopSize' => 0,
                'borderTopColor' => 'FFFFFF',
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
                'borderLeftSize' => 0,
                'borderLeftColor' => 'FFFFFF',
                'borderRightSize' => 0,
                'borderRightColor' => 'FFFFFF',
                'valign' => 'bottom'
            ]);
            $othersValueCell->addText('', $smallTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]); // Empty cell for alignment
            $othersLabelCell = $othersRow->addCell(3300, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'bottom'
            ]);
            $othersLabelCell->addText('others (Specify)', $smallTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            $disapprovedCell = $row9->addCell(5500, ['borderTopSize' => 6, 'borderTopColor' => '000000', 'borderBottomSize' => 6, 'borderBottomColor' => 'FFFFFF', 'borderLeftSize' => 6, 'borderLeftColor' => 'FFFFFF', 'borderRightSize' => 6, 'borderRightColor' => '000000', 'valign' => 'top']);
            
            // Create a padding table to add margin inside (same as 6.C)
            $disapprovedPaddingTable = $disapprovedCell->addTable(['width' => 100 * 50]);
            $disapprovedPaddingMiddleRow = $disapprovedPaddingTable->addRow();
            // Left padding
            $disapprovedPaddingLeftCell = $disapprovedPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $disapprovedPaddingLeftCell->addText('', $tinyTextStyle);
            
            // Center cell with the actual content table
            $disapprovedPaddingCenterCell = $disapprovedPaddingMiddleRow->addCell(5300, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'top']);
            
            // Wrap contents in a table with 1 column/1 row to create a box effect
            $disapprovedContentTable = $disapprovedPaddingCenterCell->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => 'FFFFFF'
            ]);
            $disapprovedContentRow = $disapprovedContentTable->addRow();
            $disapprovedContentCell = $disapprovedContentRow->addCell(5300, [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'top'
            ]);
            
            // Right padding
            $disapprovedPaddingRightCell = $disapprovedPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $disapprovedPaddingRightCell->addText('', $tinyTextStyle);
            
            $disapprovedContentCell->addText('7.D DISAPPROVED DUE TO:', $boldTinyStyle);
            $disapprovedContentCell->addText($dtl->disapproved_remarks ?? '', $smallTextStyle);
            
            $row10 = $table6->addRow();
            $signatureCell = $row10->addCell(11000, [
                'borderTopSize' => 6,
                'borderTopColor' => 'FFFFFF',
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
                'borderLeftSize' => 6,
                'borderLeftColor' => '000000',
                'borderRightSize' => 6,
                'borderRightColor' => '000000',
                'gridSpan' => 2
            ]);
            $signatureCell->addText(
                ($data_with_pay[0]->total_applied_days ?? 0) > 3 
                    ? ($leave_signatories[0]->approver_4 ?? '')
                    : ($leave_signatories[0]->approver_3 ?? ''),
                $smallTextStyle,
                array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0])
            );
            $signatureCell->addText('(Authorized Official)', $smallTextStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));

            // Page break for second page
            $section->addPageBreak();

            // Second Page: INSTRUCTIONS AND REQUIREMENTS
            // Header: INSTRUCTIONS AND REQUIREMENTS
            $instructionsHeaderTable = $section->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => '000000'
            ]);
            $instructionsHeaderRow = $instructionsHeaderTable->addRow();
            $instructionsHeaderCell = $instructionsHeaderRow->addCell(10500, [
                'borderSize' => 6,
                'borderColor' => '000000'
            ]);
            $instructionsHeaderCell->addText('INSTRUCTIONS AND REQUIREMENTS', $boldSmallStyle, array_merge($centerStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]));

            // Main two-column table for instructions
            $instructionsTable = $section->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => '000000'
            ]);
            $instructionsRow = $instructionsTable->addRow();
            
            // Left Column with padding
            $leftInstructionsCol = $instructionsRow->addCell(5000, [
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top'
            ]);
            
            // Create a padding table to add margin inside (same as other sections)
            $leftInstructionsPaddingTable = $leftInstructionsCol->addTable(['width' => 100 * 50]);
            $leftInstructionsPaddingMiddleRow = $leftInstructionsPaddingTable->addRow();
            // Left padding
            $leftInstructionsPaddingLeftCell = $leftInstructionsPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $leftInstructionsPaddingLeftCell->addText('', $tinyTextStyle);
            
            // Center cell with the actual content table
            $leftInstructionsPaddingCenterCell = $leftInstructionsPaddingMiddleRow->addCell(4800, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'top']);
            
            // Wrap contents in a table with 1 column/1 row to create a box effect
            $leftInstructionsContentTable = $leftInstructionsPaddingCenterCell->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => 'FFFFFF'
            ]);
            $leftInstructionsContentRow = $leftInstructionsContentTable->addRow();
            $leftInstructionsContentCell = $leftInstructionsContentRow->addCell(4800, [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'top'
            ]);
            
            // Right padding
            $leftInstructionsPaddingRightCell = $leftInstructionsPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $leftInstructionsPaddingRightCell->addText('', $tinyTextStyle);
            
            // Introductory paragraph
            $leftInstructionsContentCell->addText('Application for any type of leave shall be made on this Form and to be accomplished at least in duplicate with documentary requirements, as follows:', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);

            
            // 1. Vacation leave*
            $leftInstructionsContentCell->addText('1. Vacation leave*', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('It shall be filed five (5) days in advance, whenever possible, of the effective date of such leave.  Vacation leave within in the Philippines or abroad shall be indicated in the form for purposes of securing travel authority and completing clearance from money and work accountabilities.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 2. Mandatory/Forced leave
            $leftInstructionsContentCell->addText('2. Mandatory/Forced leave', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('Annual five-day vacation leave shall be forfeited if not taken during the year.  In case the scheduled leave has been cancelled in the exigency of the service by the head of agency, it shall no longer be deducted from the accumulated vacation leave.  Availment of one (1) day or more Vacation Leave (VL) shall be considered for complying the mandatory/forced leave subject to the conditions under Section 25, Rule XVI of the Omnibus Rules Implementing E.O. No. 292.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 3. Sick leave*
            $leftInstructionsContentCell->addText('3. Sick leave*', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('• It shall be filed immediately upon employee\'s return from such leave.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('• If filed in advance or exceeding five (5) days, application shall be accompanied by a medical certificate.  In case medical consultation was not availed of, an affidavit should be executed by an applicant.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 4. Maternity leave* – 105 days
            $leftInstructionsContentCell->addText('4. Maternity leave* – 105 days', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('• Proof of pregnancy e.g. ultrasound, doctor\'s certificate on the expected date of delivery', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('• Accomplished Notice of Allocation of Maternity Leave Credits (CS Form No. 6a), if needed', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('• Seconded female employees shall enjoy maternity leave with full pay in the recipient agency.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 5. Paternity leave – 7 days
            $leftInstructionsContentCell->addText('5. Paternity leave – 7 days', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('Proof of child\'s delivery e.g. birth certificate, medical certificate and marriage contract', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 6. Special Privilege leave – 3 days
            $leftInstructionsContentCell->addText('6. Special Privilege leave – 3 days', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('It shall be filed/approved for at least one (1) week prior to availment, except on emergency cases.  Special privilege leave within the Philippines or abroad shall be indicated in the form for purposes of securing travel authority and completing clearance from money and work accountabilities.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 7. Solo Parent leave – 7 days
            $leftInstructionsContentCell->addText('7. Solo Parent leave – 7 days', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('It shall be filed in advance or whenever possible five (5) days before going on such leave with updated Solo Parent Identification Card.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 8. Study leave* – up to 6 months
            $leftInstructionsContentCell->addText('8. Study leave* – up to 6 months', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('• Shall meet the agency\'s internal requirements, if any;', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('• Contract between the agency head or authorized representative and the employee concerned.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 9. VAWC leave – 10 days
            $leftInstructionsContentCell->addText('9. VAWC leave – 10 days', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('• It shall be filed in advance or immediately upon the woman employee\'s return from such leave.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('• It shall be accompanied by any of the following supporting documents:', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('a. Barangay Protection Order (BPO) obtained from the barangay;', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('b. Temporary/Permanent Protection Order (TPO/PPO) obtained from the court;', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $leftInstructionsContentCell->addText('c. If the protection order is not yet issued by the barangay or the court, a certification issued by the Punong Barangay/Kagawad or Prosecutor or the Clerk of Court that the application for the BPO, TPO or PPO has been filed with the said office shall be sufficient to support the application for the ten-day leave; or', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            //Middle Column
            $middleInstructionsCol = $instructionsRow->addCell(500, [
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top'
            ]);
            $middleInstructionsCol->addText('', ['spaceAfter' => 0, 'spaceBefore' => 0]);
            

            // Right Column with padding
            $rightInstructionsCol = $instructionsRow->addCell(5000, [
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top'
            ]);
            
            // Create a padding table to add margin inside (same as other sections)
            $rightInstructionsPaddingTable = $rightInstructionsCol->addTable(['width' => 100 * 50]);
            $rightInstructionsPaddingMiddleRow = $rightInstructionsPaddingTable->addRow();
            // Left padding
            $rightInstructionsPaddingLeftCell = $rightInstructionsPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $rightInstructionsPaddingLeftCell->addText('', $tinyTextStyle);
            
            // Center cell with the actual content table
            $rightInstructionsPaddingCenterCell = $rightInstructionsPaddingMiddleRow->addCell(4800, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'top']);
            
            // Wrap contents in a table with 1 column/1 row to create a box effect
            $rightInstructionsContentTable = $rightInstructionsPaddingCenterCell->addTable([
                'width' => 100 * 50,
                'borderSize' => 6,
                'borderColor' => 'FFFFFF'
            ]);
            $rightInstructionsContentRow = $rightInstructionsContentTable->addRow();
            $rightInstructionsContentCell = $rightInstructionsContentRow->addCell(4800, [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'top'
            ]);
            
            // Right padding
            $rightInstructionsPaddingRightCell = $rightInstructionsPaddingMiddleRow->addCell(100, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $rightInstructionsPaddingRightCell->addText('', $tinyTextStyle);
            
            // Continuation text (from VAWC leave point c)
            $rightInstructionsContentCell->addText('TPO or PPO has been filed with the said office shall be sufficient to support the application for the ten-day leave; or', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // Point d.
            $rightInstructionsContentCell->addText('d. In the absence of the BPO/TPO/PPO or the certification, a police report specifying the details of the occurrence of violence on the victim and a medical certificate may be considered, at the discretion of the immediate supervisor of the woman employee concerned.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 10. Rehabilitation leave* – up to 6 months
            $rightInstructionsContentCell->addText('10. Rehabilitation leave* – up to 6 months', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightInstructionsContentCell->addText('• Application shall be made within one (1) week from the time of the accident except when a longer period is warranted.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightInstructionsContentCell->addText('• Letter request supported by relevant reports such as the police report, if any,', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightInstructionsContentCell->addText('• Medical certificate on the nature of the injuries, the course of treatment involved, and the need to undergo rest, recuperation, and rehabilitation, as the case may be.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightInstructionsContentCell->addText('• Written concurrence of a government physician should be obtained relative to the recommendation for rehabilitation if the attending physician is a private practitioner, particularly on the duration of the period of rehabilitation.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 11. Special leave benefits for women* – up to 2 months
            $rightInstructionsContentCell->addText('11. Special leave benefits for women* – up to 2 months', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightInstructionsContentCell->addText('• The application may be filed in advance, that is, at least five (5) days prior to the scheduled date of the gynecological surgery that will be undergone by the employee.  In case of emergency, the application for special leave shall be filed immediately upon employee\'s return but during confinement the agency shall be notified of said surgery.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightInstructionsContentCell->addText('• The application shall be accompanied by a medical certificate filled out by the proper medical authorities, e.g. the attending surgeon accompanied by a clinical summary reflecting the gynecological disorder which shall be addressed or was addressed by the said surgery; the histopathological report; the operative technique used for the surgery; the duration of the surgery including the perioperative period (period of confinement around surgery); as well as the employees estimated period of recuperation for the same.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 12. Special Emergency (Calamity) leave – up to 5 days
            $rightInstructionsContentCell->addText('12. Special Emergency (Calamity) leave – up to 5 days', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightInstructionsContentCell->addText('• The special emergency leave can be applied for a maximum of five (5) straight working days or staggered basis within thirty (30) days from the actual occurrence of the natural calamity/disaster. Said privilege shall be enjoyed once a year, not in every instance of calamity or disaster.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightInstructionsContentCell->addText('• The head of office shall take full responsibility for the grant of special emergency leave and verification of the employee\'s eligibility to be granted thereof.  Said verification shall include: validation of place of residence based on latest available records of the affected employee; verification that the place of residence is covered in the declaration of calamity area by the proper government agency; and such other proofs as may be necessary.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 13. Monetization of leave credits
            $rightInstructionsContentCell->addText('13. Monetization of leave credits', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightInstructionsContentCell->addText('Application for monetization of fifty percent (50%) or more of the accumulated leave credits shall be accompanied by letter request to the head of the agency stating the valid and justifiable reasons.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 14. Terminal leave*
            $rightInstructionsContentCell->addText('14. Terminal leave*', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightInstructionsContentCell->addText('Proof of employee\'s resignation or retirement or separation from the service.', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            
            // 15. Adoption Leave
            $rightInstructionsContentCell->addText('15. Adoption Leave', $boldSmallStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);
            $rightInstructionsContentCell->addText('• Application for adoption leave shall be filed with an authenticated copy of the Pre-Adoptive Placement Authority issued by the Department of Social Welfare and Development (DSWD).', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);

            // Footer row - spans all 3 columns
            $footerRow = $instructionsTable->addRow();
            $footerCell = $footerRow->addCell(10500, [
                'borderSize' => 6,
                'borderColor' => '000000',
                'gridSpan' => 3
            ]);
            $footerCell->addText('* For leave of absence for thirty (30) calendar days or more and terminal leave, application shall be accompanied by a clearance from money, property and work-related accountabilities (pursuant to CSC Memorandum Circular No. 2, s. 1985).', $tinyTextStyle, ['spaceAfter' => 0, 'spaceBefore' => 0]);

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $tempFile = tempnam(sys_get_temp_dir(), 'leave_docx_');
            $objWriter->save($tempFile);

            $filename = "leave_application_{$id}_" . date('Y-m-d') . ".docx";

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate leave DOCX: ' . $e->getMessage());
        }
    }

}
