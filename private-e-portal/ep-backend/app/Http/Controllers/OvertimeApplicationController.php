<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\User;
use App\Audit;
use Carbon\Carbon;
use App\OvertimeApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\EmailOvertimeApplication;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OvertimeApplicationController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $emp_id_data = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->leftJoin('departments as c', 'b.id', '=', 'c.employee_id')
                ->selectRaw('case when b.id = null then 0 else b.id end as id')
                ->selectRaw('case when c.employee_id = null then 0 else c.employee_id end as supervisor_id')
                ->where('a.id', $id)
                ->get();

            if (count($emp_id_data) == 0) {
                $emp_id = 0;
                $supervisor_id = 0;
                $dummy_info =
                    array(
                        'id' => 0,
                    );
                $info = (object)$dummy_info;
                $info = collect([$info]);
            } else {
                $info = DB::table('users as a')
                    ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                    ->select(
                        'b.id',
                    )
                    ->where(['b.is_employee' => true, 'b.active' => true, 'a.id' => $id])
                    ->get();

                $emp_id = $info[0]->id;
            }

            // Check if Approver (overtime type_id = 3 only; hierarchical: L2 sees only after L1 acted, L3 after L2)
            $approver_1 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_1 as supervisor_id'
                )
                ->where('a.type_id', 3)
                ->where(function ($q) use ($emp_id) {
                    $q->where('a.branch_approver_id_1', $emp_id)
                        ->orWhere('a.approver_id_1', $emp_id)
                        ->orWhere('a.division_approver_id_1', $emp_id)
                        ->orWhere('a.section_approver_id_1', $emp_id);
                })
                ->distinct()
                ->get();

            $approver_2 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_2 as supervisor_id'
                )
                ->where('a.type_id', 3)
                ->where('a.approver_id_2', $emp_id)
                ->distinct()
                ->get();

            $approver_3 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_3 as supervisor_id'
                )
                ->where('a.type_id', 3)
                ->where('a.approver_id_3', $emp_id)
                ->distinct()
                ->get();

            $ForapprovalEmployeeOT = [];
            $ApprovedEmployeeOT = [];
            $DisapprovedEmployeeOT = [];
            $CancelEmployeeOT = [];

            if ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
                $approver_id = $approver_1[0]->id;
                $supervisor_id = $approver_1[0]->supervisor_id;

                $ForapprovalEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.last_name, ', ', b.first_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) + ', ' + RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))
                                END as employee_name_formatted"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'a.employee_id',
                        'd.name as overtime_type_name',
                        'a.is_cancel',
                        'canceled_remarks',
                        'e.attachment_name',
                        'e.id as attachment_id'
                    )
                    ->where(['a.approved' => false, 'a.disapproved' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where(function ($q) use ($emp_id) {
                                $q->where('b.branch_approver_id_1', $emp_id)
                                    ->orWhere('b.approver_id_1', $emp_id)
                                    ->orWhere('b.division_approver_id_1', $emp_id)
                                    ->orWhere('b.section_approver_id_1', $emp_id);
                            });
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->where('is_cancel', false)
                    ->orderby('a.date', 'desc')
                    ->get();

                $ApprovedEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id',
                        'a.attachment_name'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where(function ($q) use ($emp_id) {
                                $q->where('b.branch_approver_id_1', $emp_id)
                                    ->orWhere('b.approver_id_1', $emp_id)
                                    ->orWhere('b.division_approver_id_1', $emp_id)
                                    ->orWhere('b.section_approver_id_1', $emp_id);
                            });
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->orderby('a.date', 'desc')
                    ->get();

                $DisapprovedEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id',
                        'a.disapprove_remarks'
                    )
                    ->where(['a.approved' => false, 'a.disapproved' => true])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where(function ($q) use ($emp_id) {
                                $q->where('b.branch_approver_id_1', $emp_id)
                                    ->orWhere('b.approver_id_1', $emp_id)
                                    ->orWhere('b.division_approver_id_1', $emp_id)
                                    ->orWhere('b.section_approver_id_1', $emp_id);
                            });
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->orderby('a.date', 'desc')
                    ->get();

                $CancelEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'a.employee_id',
                        'd.name as overtime_type_name',
                        'a.is_cancel',
                        'canceled_remarks',
                        'e.attachment_name'
                    )
                    ->where(['a.approved' => false, 'a.disapproved' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where(function ($q) use ($emp_id) {
                                $q->where('b.branch_approver_id_1', $emp_id)
                                    ->orWhere('b.approver_id_1', $emp_id)
                                    ->orWhere('b.division_approver_id_1', $emp_id)
                                    ->orWhere('b.section_approver_id_1', $emp_id);
                            });
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->where('is_cancel', true)
                    ->orderby('a.date', 'desc')
                    ->get();
            } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
                $approver_id = $approver_2[0]->id;
                $supervisor_id = $approver_2[0]->supervisor_id;

                $ForapprovalEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.last_name, ', ', b.first_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) + ', ' + RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))
                                END as employee_name_formatted"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'a.employee_id',
                        'd.name as overtime_type_name',
                        'a.is_cancel',
                        'canceled_remarks',
                        'e.attachment_name',
                        'e.id as attachment_id'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => false, 'a.disapproved_2' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where('b.approver_id_2', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->where(db::raw("isnull(is_cancel,0)"), false)
                    ->orderby('a.date', 'desc')
                    ->get();

                $ApprovedEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id',
                        'a.attachment_name'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => true, 'a.disapproved_2' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where('b.approver_id_2', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->orderby('a.date', 'desc')
                    ->get();

                $DisapprovedEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id',
                        'a.disapprove_remarks'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => false, 'a.disapproved_2' => true])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where('b.approver_id_2', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->orderby('a.date', 'desc')
                    ->get();

                $CancelEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'a.employee_id',
                        'd.name as overtime_type_name',
                        'a.is_cancel',
                        'canceled_remarks',
                        'e.attachment_name'
                    )
                    ->where(['a.approved' => false, 'a.disapproved' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where('b.approver_id_2', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->where(db::raw("isnull(is_cancel,0)"), true)
                    ->orderby('a.date', 'desc')
                    ->get();
            } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
                $approver_id = $approver_2[0]->id;
                $supervisor_id = $approver_2[0]->supervisor_id;

                $ForapprovalEmployeeOT_1 = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.last_name, ', ', b.first_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) + ', ' + RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))
                                END as employee_name_formatted"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'a.employee_id',
                        'd.name as overtime_type_name',
                        'a.is_cancel',
                        'canceled_remarks',
                        'e.attachment_name',
                        'e.id as attachment_id'
                    )
                    ->where(['a.approved' => false, 'a.disapproved' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where(function ($q) use ($emp_id) {
                                $q->where('b.branch_approver_id_1', $emp_id)
                                    ->orWhere('b.approver_id_1', $emp_id)
                                    ->orWhere('b.division_approver_id_1', $emp_id)
                                    ->orWhere('b.section_approver_id_1', $emp_id);
                            });
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->where('is_cancel', false)
                    ->distinct();

                $ForapprovalEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.last_name, ', ', b.first_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) + ', ' + RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))
                                END as employee_name_formatted"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'a.employee_id',
                        'd.name as overtime_type_name',
                        'a.is_cancel',
                        'canceled_remarks',
                        'e.attachment_name',
                        'e.id as attachment_id'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => false, 'a.disapproved_2' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where('b.approver_id_2', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->where(db::raw("isnull(is_cancel,0)"), false)
                    ->union($ForapprovalEmployeeOT_1)
                    ->orderby('date', 'desc')
                    ->get();

                $ApprovedEmployeeOT_1 = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id',
                        'a.attachment_name'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where(function ($q) use ($emp_id) {
                                $q->where('b.branch_approver_id_1', $emp_id)
                                    ->orWhere('b.approver_id_1', $emp_id)
                                    ->orWhere('b.division_approver_id_1', $emp_id)
                                    ->orWhere('b.section_approver_id_1', $emp_id);
                            });
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->distinct();

                $ApprovedEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id',
                        'a.attachment_name'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => true, 'a.disapproved_2' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where('b.approver_id_2', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->union($ApprovedEmployeeOT_1)
                    ->orderby('date', 'desc')
                    ->get();

                $DisapprovedEmployeeOT_1 = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id',
                        'a.disapprove_remarks'
                    )
                    ->where(['a.approved' => false, 'a.disapproved' => true])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where(function ($q) use ($emp_id) {
                                $q->where('b.branch_approver_id_1', $emp_id)
                                    ->orWhere('b.approver_id_1', $emp_id)
                                    ->orWhere('b.division_approver_id_1', $emp_id)
                                    ->orWhere('b.section_approver_id_1', $emp_id);
                            });
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->distinct();

                $DisapprovedEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id',
                        'a.disapprove_remarks'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => false, 'a.disapproved_2' => true])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where('b.approver_id_2', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->union($DisapprovedEmployeeOT_1)
                    ->orderby('date', 'desc')
                    ->get();

                $CancelEmployeeOT_1 = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'a.employee_id',
                        'd.name as overtime_type_name',
                        'a.is_cancel',
                        'canceled_remarks',
                        'e.attachment_name'
                    )
                    ->where(['a.approved' => false, 'a.disapproved' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where(function ($q) use ($emp_id) {
                                $q->where('b.branch_approver_id_1', $emp_id)
                                    ->orWhere('b.approver_id_1', $emp_id)
                                    ->orWhere('b.division_approver_id_1', $emp_id)
                                    ->orWhere('b.section_approver_id_1', $emp_id);
                            });
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->where('is_cancel', true)
                    ->distinct();

                $CancelEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'a.employee_id',
                        'd.name as overtime_type_name',
                        'a.is_cancel',
                        'canceled_remarks',
                        'e.attachment_name'
                    )
                    ->where(['a.approved' => false, 'a.disapproved' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where('b.approver_id_2', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->where(db::raw("isnull(is_cancel,0)"), true)
                    ->union($CancelEmployeeOT_1)
                    ->orderby('date', 'desc')
                    ->get();
            } elseif ($approver_3->isNotEmpty()) {
                $approver_id = $approver_3[0]->id;
                $supervisor_id = $approver_3[0]->supervisor_id;

                // For Approval: approved_2 = 1 (level 2 approved), waiting for level 3
                $ForapprovalEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'a.employee_id',
                        'd.name as overtime_type_name',
                        'a.is_cancel',
                        'canceled_remarks',
                        'e.attachment_name',
                        'e.id as attachment_id'
                    )
                    ->where(['a.approved' => true, 'a.approved_2' => true, 'a.approved_3' => false, 'a.disapproved_3' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where('b.approver_id_3', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->where(db::raw("isnull(is_cancel,0)"), false)
                    ->orderby('a.date', 'desc')
                    ->get();

                // Approved: All 3 levels approved
                $ApprovedEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id',
                        'a.attachment_name'
                    )
                    ->where(['a.approved' => true, 'a.approved_2' => true, 'a.approved_3' => true, 'a.disapproved_3' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where('b.approver_id_3', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->orderby('a.date', 'desc')
                    ->get();

                // Disapproved: Level 3 disapproved
                $DisapprovedEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id',
                        'a.disapprove_remarks'
                    )
                    ->where(['a.disapproved_3' => true])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where('b.approver_id_3', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->orderby('a.date', 'desc')
                    ->get();

                // Cancelled
                $CancelEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key'))+' '+RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')) END as employee_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id',
                        'canceled_remarks'
                    )
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.type_id', 3)
                            ->where('b.approver_id_3', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->where(db::raw("isnull(is_cancel,0)"), true)
                    ->orderby('a.date', 'desc')
                    ->get();
            } else {
                $approver_id = 0;
                $supervisor_id = 0;

                $ForapprovalEmployeeOT = [];
                $ApprovedEmployeeOT = [];
                $DisapprovedEmployeeOT = [];
                $CancelEmployeeOT = [];
            }

            $LeaveType = DB::table('overtime_types')->where('overtime_types.active', 1)->orderby('name', 'asc')->get();

            $PendingOvertime = DB::table('overtime_applications as a')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->select(
                    'a.id',
                    'a.employee_id',
                    'a.overtime_type_id',
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.total_hours',
                    'a.remarks',
                    'a.payroll',
                    'a.service_credits',
                    'd.name as overtime_type_name',
                    'a.approved',
                    'a.approved_2',
                    'a.approved_3',
                    'a.disapproved',
                    'a.disapproved_2',
                    'a.disapproved_3',
                    'a.is_cancel',
                    'a.attachment_name',
                    DB::raw("(SELECT TOP 1
                                CASE WHEN ISNULL(emp1.is_encrypted,0) = 0 THEN
                                        CONCAT(emp1.first_name,' ',emp1.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](emp1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp1.last_name,'$app_key'))
                                END
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                            INNER JOIN employees emp1 ON emp1.id = ah.approver_id_1
                            WHERE ad.employee_id = a.employee_id
                            ORDER BY ah.id DESC) as approver_1"),
                    DB::raw("(SELECT TOP 1
                                CASE WHEN ISNULL(emp2.is_encrypted,0) = 0 THEN
                                        CONCAT(emp2.first_name,' ',emp2.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](emp2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp2.last_name,'$app_key'))
                                END
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                            INNER JOIN employees emp2 ON emp2.id = ah.approver_id_2
                            WHERE ad.employee_id = a.employee_id AND ISNULL(ah.approver_id_2,0) > 0
                            ORDER BY ah.id DESC) as approver_2"),
                    DB::raw("(SELECT TOP 1
                                CASE WHEN ISNULL(emp3.is_encrypted,0) = 0 THEN
                                        CONCAT(emp3.first_name,' ',emp3.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](emp3.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp3.last_name,'$app_key'))
                                END
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                            INNER JOIN employees emp3 ON emp3.id = ah.approver_id_3
                            WHERE ad.employee_id = a.employee_id AND ISNULL(ah.approver_id_3,0) > 0
                            ORDER BY ah.id DESC) as approver_3")
                )
                ->where(['a.employee_id' => $emp_id])
                ->where(db::raw("isnull(is_cancel,0)"), false)
                ->where(function ($query) {
                    $query->where(function ($q) {
                        // Truly pending - no approvals or disapprovals at any level
                        $q->where('a.approved', false)
                            ->where('a.approved_2', false)
                            ->where('a.approved_3', false)
                            ->where('a.disapproved', false)
                            ->where('a.disapproved_2', false)
                            ->where('a.disapproved_3', false);
                    })->orWhere(function ($q) {
                        // Partially approved - some levels approved but not all
                        $q->where(function ($sub) {
                            $sub->where('a.approved', true)
                                ->where(function ($s) {
                                    $s->where('a.approved_2', false)
                                        ->orWhere('a.approved_3', false);
                                });
                        });
                    });
                })
                ->orderby('a.date', 'desc')
                ->get();

            $ApprovedOvertime = DB::table('overtime_applications as a')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->select(
                    'a.id',
                    'a.employee_id',
                    'a.overtime_type_id',
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.total_hours',
                    'a.remarks',
                    'a.payroll',
                    'a.service_credits',
                    'd.name as overtime_type_name',
                    'a.approved',
                    'a.approved_2',
                    'a.approved_3',
                    'a.disapproved',
                    'a.disapproved_2',
                    'a.disapproved_3',
                    'a.is_cancel',
                    DB::raw("(SELECT TOP 1
                                CASE WHEN ISNULL(emp1.is_encrypted,0) = 0 THEN
                                        CONCAT(emp1.first_name,' ',emp1.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](emp1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp1.last_name,'$app_key'))
                                END
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                            INNER JOIN employees emp1 ON emp1.id = ah.approver_id_1
                            WHERE ad.employee_id = a.employee_id
                            ORDER BY ah.id DESC) as approver_1"),
                    DB::raw("(SELECT TOP 1
                                CASE WHEN ISNULL(emp2.is_encrypted,0) = 0 THEN
                                        CONCAT(emp2.first_name,' ',emp2.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](emp2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp2.last_name,'$app_key'))
                                END
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                            INNER JOIN employees emp2 ON emp2.id = ah.approver_id_2
                            WHERE ad.employee_id = a.employee_id AND ISNULL(ah.approver_id_2,0) > 0
                            ORDER BY ah.id DESC) as approver_2"),
                    DB::raw("(SELECT TOP 1
                                CASE WHEN ISNULL(emp3.is_encrypted,0) = 0 THEN
                                        CONCAT(emp3.first_name,' ',emp3.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](emp3.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp3.last_name,'$app_key'))
                                END
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                            INNER JOIN employees emp3 ON emp3.id = ah.approver_id_3
                            WHERE ad.employee_id = a.employee_id AND ISNULL(ah.approver_id_3,0) > 0
                            ORDER BY ah.id DESC) as approver_3"),
                    'a.processed_date',
                    'a.processed_date_2',
                    'a.processed_date_3',
                    'a.attachment_name'
                )
                ->where(['a.employee_id' => $emp_id])
                ->where(function ($query) {
                    $query->where('a.approved', true)
                        ->orWhere('a.approved_2', true)
                        ->orWhere('a.approved_3', true);
                })
                ->where(db::raw("isnull(is_cancel,0)"), false)
                ->orderby('a.date', 'desc')->get();

            $DisapprovedOvertime = DB::table('overtime_applications as a')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->select(
                    'a.id',
                    'a.employee_id',
                    'a.overtime_type_id',
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.total_hours',
                    'a.remarks',
                    'a.payroll',
                    'a.service_credits',
                    'd.name as overtime_type_name',
                    'a.approved',
                    'a.approved_2',
                    'a.approved_3',
                    'a.disapproved',
                    'a.disapproved_2',
                    'a.disapproved_3',
                    'a.is_cancel',
                    DB::raw("(SELECT TOP 1
                                CASE WHEN ISNULL(emp1.is_encrypted,0) = 0 THEN
                                        CONCAT(emp1.first_name,' ',emp1.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](emp1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp1.last_name,'$app_key'))
                                END
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                            INNER JOIN employees emp1 ON emp1.id = ah.approver_id_1
                            WHERE ad.employee_id = a.employee_id
                            ORDER BY ah.id DESC) as approver_1"),
                    DB::raw("(SELECT TOP 1
                                CASE WHEN ISNULL(emp2.is_encrypted,0) = 0 THEN
                                        CONCAT(emp2.first_name,' ',emp2.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](emp2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp2.last_name,'$app_key'))
                                END
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                            INNER JOIN employees emp2 ON emp2.id = ah.approver_id_2
                            WHERE ad.employee_id = a.employee_id AND ISNULL(ah.approver_id_2,0) > 0
                            ORDER BY ah.id DESC) as approver_2"),
                    DB::raw("(SELECT TOP 1
                                CASE WHEN ISNULL(emp3.is_encrypted,0) = 0 THEN
                                        CONCAT(emp3.first_name,' ',emp3.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](emp3.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp3.last_name,'$app_key'))
                                END
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                            INNER JOIN employees emp3 ON emp3.id = ah.approver_id_3
                            WHERE ad.employee_id = a.employee_id AND ISNULL(ah.approver_id_3,0) > 0
                            ORDER BY ah.id DESC) as approver_3"),
                    'a.processed_date',
                    'a.processed_date_2',
                    'a.processed_date_3',
                    'a.disapprove_remarks',
                    'a.attachment_name'
                )
                ->where(['a.employee_id' => $emp_id])
                ->where(function ($query) {
                    $query->where('a.disapproved', true)
                        ->orWhere('a.disapproved_2', true)
                        ->orWhere('a.disapproved_3', true);
                })
                ->where(db::raw("isnull(is_cancel,0)"), false)
                ->orderby('a.date', 'desc')
                ->get();

            $CancelledOvertime = DB::table('overtime_applications as a')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->leftJoin('employees as c', 'a.canceled_by', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.employee_id',
                    'a.overtime_type_id',
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.total_hours',
                    'a.remarks',
                    'a.approved',
                    'a.approved_2',
                    'a.approved_3',
                    'a.disapproved',
                    'a.disapproved_2',
                    'a.disapproved_3',
                    'a.is_cancel',
                    'a.payroll',
                    'a.service_credits',
                    'd.name as overtime_type_name',
                    'a.canceled_date',
                    'a.canceled_remarks',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                    CONCAT(c.first_name,' ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                                END as cancelled_by"),
                    'a.attachment_name'
                )
                ->where(['a.approved' => false, 'a.disapproved' => false, 'a.employee_id' => $emp_id])
                ->where(db::raw("isnull(is_cancel,0)"), true)
                ->orderby('a.date', 'desc')
                ->get();

            $Service_Credits = DB::table('leave_types')->where('service_credit', true)->get();

            if (count($Service_Credits) == 0) {
                $Service_Credit = 0;
                $coc_id = 0;
            } else {
                $Service_Credit = 1;
                $coc_id = $Service_Credits[0]->id;
            }

            $coc_total_hours = DB::table('overtime_applications as a')
                ->join('overtime_types as b', 'a.overtime_type_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    DB::raw("DATENAME(month, a.date) as months"),
                    DB::raw("MONTH(a.date) as months_num"),
                    DB::raw("YEAR(a.date) as years"),
                    DB::raw("SUM(isnull(a.total_hours,0) * b.rate) as total_hours"),
                    db::raw("cast(0 as decimal(18,2)) as carryover")
                )
                ->where(['a.employee_id' => $emp_id, 'a.service_credits' => true, 'a.approved' => true])
                ->groupBy(
                    DB::raw("DATENAME(month, a.date)"),
                    DB::raw("MONTH(a.date)"),
                    DB::raw("YEAR(a.date)"),
                    'a.employee_id'
                );

            $cto_total_hours = DB::table('leave_headers as a')
                ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                ->select(
                    'a.employee_id',
                    DB::raw("DATENAME(month, b.leave_date) as months"),
                    DB::raw("MONTH(b.leave_date) as months_num"),
                    DB::raw("YEAR(b.leave_date) as years"),
                    DB::raw("SUM(isnull(b.with_pay,0)) * 8 as total_leave_hours")
                )
                ->where(['a.employee_id' => $emp_id, 'a.leave_type_id' => $coc_id, 'a.approved' => true])
                ->groupBy(
                    DB::raw("DATENAME(month, b.leave_date)"),
                    DB::raw("MONTH(b.leave_date)"),
                    DB::raw("YEAR(b.leave_date)"),
                    'a.employee_id'
                );

            $coc_details = DB::query()->fromSub($coc_total_hours, 'a');
            $coc = $coc_details
                ->leftjoinSub($cto_total_hours, 'b', function ($join) {
                    $join->on('a.employee_id', '=', 'b.employee_id');
                    $join->on('a.months_num', '=', 'b.months_num');
                    $join->on('a.years', '=', 'b.years');
                })
                ->select(
                    'a.employee_id',
                    DB::raw("CONCAT(a.months,', ',a.years) as months"),
                    DB::raw("SUM(isnull(a.total_hours,0)) OVER (ORDER BY a.months_num) as total_hours"),
                    'b.total_leave_hours',
                    'a.total_hours as carryover',
                    db::raw("SUM(isnull(a.total_hours,0)) OVER (ORDER BY a.months_num) - isnull(b.total_leave_hours,0) as remaining_balance"),
                    db::raw("(SUM(isnull(a.total_hours,0)) OVER (ORDER BY a.months_num) - isnull(b.total_leave_hours,0)) / 8 as converted_days")
                )
                ->orderby('a.months_num', 'asc')
                ->orderby('a.years', 'desc')
                ->get();

            // Overtime Table Query
            $overtime_tax_code = DB::table('overtime_taxes')->get();

            // Check if With Approver for Overtime (type_id = 3).
            $with_approvers = DB::table('approver_details as ad')
                ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                ->where('ad.employee_id', $emp_id)
                ->where('ah.type_id', 3) // Overtime type
                ->get();

            if (count($with_approvers) > 0) {
                $allowed = 1;
            } else {
                $allowed = 0;
            }

            $ot_attachments = DB::table('overtime_attachments')->get();

            // Enrich all overtime data with approver configuration
            if (!empty($PendingOvertime)) {
                $PendingOvertime = $this->enrichOvertimesWithApproverConfig($PendingOvertime);
            }
            if (!empty($ApprovedOvertime)) {
                $ApprovedOvertime = $this->enrichOvertimesWithApproverConfig($ApprovedOvertime);
            }
            if (!empty($DisapprovedOvertime)) {
                $DisapprovedOvertime = $this->enrichOvertimesWithApproverConfig($DisapprovedOvertime);
            }
            if (!empty($ForapprovalEmployeeOT)) {
                $ForapprovalEmployeeOT = $this->enrichOvertimesWithApproverConfig($ForapprovalEmployeeOT);
            }
            if (!empty($ApprovedEmployeeOT)) {
                $ApprovedEmployeeOT = $this->enrichOvertimesWithApproverConfig($ApprovedEmployeeOT);
            }
            if (!empty($DisapprovedEmployeeOT)) {
                $DisapprovedEmployeeOT = $this->enrichOvertimesWithApproverConfig($DisapprovedEmployeeOT);
            }
            if (!empty($CancelEmployeeOT)) {
                $CancelEmployeeOT = $this->enrichOvertimesWithApproverConfig($CancelEmployeeOT);
            }
            if (!empty($CancelledOvertime)) {
                $CancelledOvertime = $this->enrichOvertimesWithApproverConfig($CancelledOvertime);
            }

            return $this->successResponse([
                'info' => $info,
                'LeaveType' => $LeaveType,
                'PendingOvertime' => $PendingOvertime,
                'ApprovedOvertime' => $ApprovedOvertime,
                'DisapprovedOvertime' => $DisapprovedOvertime,
                'supervisor_id' => $supervisor_id,
                'ForapprovalEmployeeOT' => $ForapprovalEmployeeOT,
                'ApprovedEmployeeOT' => $ApprovedEmployeeOT,
                'DisapprovedEmployeeOT' => $DisapprovedEmployeeOT,
                'CancelEmployeeOT' => $CancelEmployeeOT,
                'CancelledOvertime' => $CancelledOvertime,
                'emp_id' => $emp_id,
                'Service_Credit' => $Service_Credit,
                'coc' => $coc,
                'overtime_tax_code' => $overtime_tax_code,
                'allowed' => $allowed,
                'ot_attachments' => $ot_attachments
            ]);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    public function store(Request $request)
    {
        $app_key = env("APP_KEY", "");

        try {
            // Resolve employee_id if user_id was passed
            $input_emp_id = $request->input('employee_id');
            if ($input_emp_id) {
                $user_check = DB::table('users')->where('id', $input_emp_id)->first();
                if ($user_check) {
                    $emp_data = DB::table('employees')->where('employee_no', $user_check->employee_no)->first();
                    if ($emp_data) {
                        $request->merge(['employee_id' => $emp_data->id]);
                    }
                }
            }

            $validate = Validator::make(
                $request->all(),
                [
                    'employee_id' => 'required|exists:employees,id',
                    'overtime_type_id' => 'required|exists:overtime_types,id',
                    'date' =>  'required|date_format:Y-m-d|after:2000-01-01',
                    // Accept either H:i or H:i:s and normalize later
                    'date_time_from' =>  'required',
                    'date_time_to' =>  'required',
                ],
                [
                    'employee_id.required' => 'Employee is required.',
                    'employee_id.exists' => 'Employee not found.',
                    'overtime_type_id.required' => 'Overtime type is required.',
                    'overtime_type_id.exists' => 'Invalid overtime type.',
                    'date.date_format' =>  'Overtime date invalid date.',
                    'date_time_from.required' =>  'Time from is required.',
                    'date_time_to.required' =>  'Time to is required.',
                ]
            );

            if ($validate->fails()) {
                return response()->json(['error' => 'Failed to Save. Please check required fields. ' . Arr::first(Arr::flatten($validate->errors()->get('*')))], 500);
            }

            // Check if employee has approver configured for Overtime (type_id = 3)
            $has_approver = DB::table('approver_details as ad')
                ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                ->where('ad.employee_id', $request->employee_id)
                ->where('ah.type_id', 3)
                ->exists();

            if (!$has_approver) {
                // Fallback check: check if any overtime approver headers exist in system
                $has_header = DB::table('approver_headers')->where('type_id', 3)->exists();
                if (!$has_header) {
                    \Log::info('No explicit approver header for OT type_id=3, allowing filing with default approver.');
                }
            }

            $data = $request->all();
            $id = $request->overtime_id;
            $emp_id = $request->employee_id;
            $otdate = $request->date;

            $overtime_types = DB::table('overtime_types')->where('id', $request->overtime_type_id)->orderBy('id', 'asc')->get();

            if ($overtime_types->isEmpty()) {
                return $this->errorResponse('Invalid overtime type.');
            }

            $employees = DB::table('employees')
                ->select(
                    'id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN first_name ELSE dbo.ufn_DecryptString(first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN last_name ELSE dbo.ufn_DecryptString(last_name,'$app_key') END as last_name"),
                    'is_shifting',
                    'work_schedule_id',
                    'date_hired',
                    'payroll_interval_id',
                    'work_schedule_id'
                )
                ->where('id', $emp_id)
                ->get();

            if ($employees->isEmpty()) {
                return $this->errorResponse('Employee record not found.');
            }

            $date = date("Y/m/d", strtotime($data['date']));
            // Normalize incoming time strings to H:i:s
            $date_from = date("H:i:s", strtotime($data['date_time_from']));
            $date_to = date("H:i:s", strtotime($data['date_time_to']));
            if ($date_from === false || $date_to === false) {
                return $this->errorResponse('Invalid time format for time from/to.');
            }

            // get employee schedules if date applied is rest day and was absent the day before OT application.
            if ($employees[0]->is_shifting == true) {
                // get shifting schedule.
                $shifting_schedule = DB::table('shift_schedules_details')
                    ->where([
                        'shift_schedule_id' => $employees[0]->work_schedule_id,
                        'shift_date' => $data['date']
                    ])
                    ->get();

                if ($shifting_schedule->isNotEmpty()) {
                    $am_in_schedule = strtotime($shifting_schedule[0]->am_in);
                    $pm_out_schedule = strtotime($shifting_schedule[0]->pm_out);
                } else {
                    $am_in_schedule = '';
                    $pm_out_schedule = '';
                }

                if ($am_in_schedule == null && $pm_out_schedule == null || $am_in_schedule == '' && $pm_out_schedule == '') {
                    $rest_day = true;
                    $date_before = date('Y-m-d', (strtotime('-1 day', strtotime($data['date']))));

                    $existing_time_data = db::table('time_data')
                        ->where('employee_id', $emp_id)
                        ->where('date', $date_before)
                        ->get();
                } else {
                    $rest_day = false;
                }
            } else {

                $day_id = Carbon::parse($data['date'])->dayOfWeek;

                // if sunday
                if ($day_id == 0) {
                    $day_id = 7;
                }

                //get fix schedule
                $fix_schedule = DB::table('fix_schedules_details')
                    ->where([
                        'fix_schedule_id' => $employees[0]->work_schedule_id,
                        'day_id' => $day_id
                    ])
                    ->get();

                if ($fix_schedule->isNotEmpty()) {
                    $am_in_schedule = strtotime($fix_schedule[0]->am_in);
                    $pm_out_schedule = strtotime($fix_schedule[0]->pm_out);
                } else {
                    $am_in_schedule = '';
                    $pm_out_schedule = '';
                }

                if ($am_in_schedule == null && $pm_out_schedule == null || $am_in_schedule == '' && $pm_out_schedule == '') {
                    $rest_day = true;
                    $date_before = date('Y-m-d', (strtotime('-1 day', strtotime($data['date']))));

                    $existing_time_data = db::table('time_data')
                        ->where('employee_id', $emp_id)
                        ->where('date', $date_before)
                        ->get();
                } else {
                    $rest_day = false;
                }
            }

            if ($rest_day) {
                if ($existing_time_data->isNotEmpty()) {
                    $am_in = $existing_time_data[0]->am_in;
                    $pm_out = $existing_time_data[0]->pm_out;
                } else {
                    $am_in = '';
                    $pm_out = '';
                    $absent = 1;
                }

                if ($am_in == null && $pm_out == null || $am_in == '' && $pm_out == '') {
                    $absent = 1;
                } else {
                    $absent = 0;
                }
            } else {
                $absent = 0;
            }

            // Check if has tardiness
            $time_data = DB::table('time_data')->where('employee_id', $emp_id)->whereDate('date', $data['date'])->get();

            if ($time_data->isNotEmpty()) {
                $late = $time_data[0]->late;
                $undertime = $time_data[0]->undertime;

                if (!$rest_day) {
                    $absent = $time_data[0]->absent;
                }
            } else {
                $late = 0;
                $undertime = 0;
                if (!$rest_day) {
                    $absent = 0;
                }
            }

            if ($data['total_hours'] < $overtime_types[0]->min_ot) {
                return $this->errorResponse('Total overtime hours did not reached required minimum hours for this application.');
            } elseif ($data['total_hours'] > $overtime_types[0]->max_ot) {
                return $this->errorResponse('Total overtime hours exceeds allowable maximum hours(' . $overtime_types[0]->max_ot . '). Please update to continue application');
            } elseif ($absent > 0) {
                return $this->errorResponse(
                    'Overtime application is not eligible for the selected date because the employee is tagged as ABSENT / has no valid time logs for that day. ' .
                    'Please update/correct the employee DTR (time logs / time_data) then try again.'
                );
            } elseif ($late > 0) {
                return $this->errorResponse(
                    'Overtime application is not eligible for the selected date because the employee has TARDINESS (late minutes recorded). ' .
                    'Please update/correct the employee DTR (time logs / time_data) then try again.'
                );
            } elseif ($undertime > 0) {
                return $this->errorResponse(
                    'Overtime application is not eligible for the selected date because the employee has UNDERTIME minutes recorded. ' .
                    'Please update/correct the employee DTR (time logs / time_data) then try again.'
                );
            } elseif ($otdate == null || $otdate == '') {
                return $this->errorResponse('Overtime date is required. Please update to continue application');
            } else {

                if ($data['selectRadio'] == 1) {
                    $payroll = true;
                    $service_credits = false;
                } else {
                    $payroll = false;
                    $service_credits = true;
                }

                $overtimeapplication = OvertimeApplication::findOrNew($id);

                $date = date("Y/m/d", strtotime($data['date']));
                $date_from = date("Y/m/d H:i:s", strtotime("$date $date_from"));
                $date_to = date("Y/m/d H:i:s", strtotime("$date $date_to"));

                $overtime_data = array(
                    'employee_id' => $data['employee_id'],
                    'overtime_type_id' => $data['overtime_type_id'],
                    'date' => $date,
                    'date_time_from' => $date_from,
                    'date_time_to' => $date_to,
                    'total_hours' => $data['total_hours'],
                    'remarks' => $data['remarks'],
                    'payroll' => $payroll,
                    'service_credits' => $service_credits,
                );

                $overtimeapplication->fill($overtime_data);
                $overtimeapplication->save();

                if ($id == 0) {
                    $overtime_id = DB::table('overtime_applications')->max('id');
                } else {
                    $overtime_id = $id;
                }

                // Save Overtime Attachments
                if ($request->hasFile('attachments')) {

                    $allowedfileExtension = ['jpg', 'jpeg', 'png', 'pdf', 'xls', 'xlsx', 'doc', 'docx'];
                    $files = $request->file('attachments');
                    $ctr = 0;

                    foreach ($files as $file) {
                        $file_name = $file->getClientOriginalName();
                        $file_path = storage_path('app/overtime_attachments/' . 'OT' . $data['employee_id'] . '_' . $file_name);
                        $extension = $file->getClientOriginalExtension();
                        $check = in_array($extension, $allowedfileExtension);

                        if ($check) {
                            // Save record of attachments to database.
                            $overtime_attachment_data = [
                                'overtime_id' => $overtime_id,
                                'attachment_name' => $file_name,
                                'attachment_path' => $file_path
                            ];

                            DB::table('overtime_attachments')->insert($overtime_attachment_data);

                            // Save attachment to path.
                            $request->attachments[$ctr]->storeAs('overtime_attachments', 'OT' . $data['employee_id'] . '_' . $file_name);
                            $ctr++;
                        } else {
                            return $this->errorResponse('Invalid file attachment.');
                        }
                    }
                }

                // Send Attachment Email Notifications.
                // $overtime_attachments = DB::table('overtime_attachments as a')
                //     ->join('overtime_applications as b', 'a.overtime_id', '=', 'b.id')
                //     ->join('employees as c', 'b.employee_id', '=', 'c.id')
                //     ->select(
                //         'a.overtime_id',
                //         'a.attachment_name',
                //         'a.attachment_path',
                //         DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                //                CONCAT(c.first_name,' ',c.last_name)
                //            ELSE
                //                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                //            END as name")
                //     )
                //     ->where('a.overtime_id', $overtime_id)
                //     ->get();

                // $department_id = DB::table('employees')->select('department_id')->where('id', $data['employee_id'])->get();

                // $user_account = DB::table('users as a')
                //     ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                //     ->select('a.*')
                //     ->whereIn('b.id', function ($query) use ($department_id) {
                //         $query->select(DB::raw('employee_id'))->from('departments')->where('id', $department_id[0]->department_id);
                //     })
                //     ->get();

                // if ($user_account->isNotEmpty()) {
                //     $users = User::where('id', $user_account[0]->id)->get();
                //     Notification::send($users, new EmailOvertimeApplication($user_account, $overtime_attachments));
                // }

                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Overtime Applcation',
                    'activity' => 'Add',
                    'description' => 'Add overtime application informations.',
                );

                Audit::create($data_audit);
                return $this->successResponse(['id' => $overtime_id], 'You have successfully update overtime!');
            }
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function approve(Request $request)
    {
        try {
            $dataX = $request->all();
            $id = $dataX['id'];
            $emp_id = $dataX['emp_id'];
            $remarks = $dataX['remarks'];

            $emp_data2 = DB::table('employees as a')
                ->join('time_keeping_setups as b', 'b.employment_type_id', '=', 'a.employment_type_id')
                ->select('b.work_hours', 'b.work_days', 'a.salary', 'a.work_schedule_id', 'a.is_shifting')
                ->where('a.id', $emp_id)
                ->get();

            if (count($emp_data2) == 0) {
                $dummy_emp_data2 = array(
                    'work_hours' => null,
                    'work_days' => null,
                    'salary' => 0,
                    'work_schedule_id' => 0,
                    'is_shifting' => false
                );

                $emp_data2 = (object)$dummy_emp_data2;
                $emp_data2 = collect([$emp_data2]);
            }

            $overtime_data = DB::table('overtime_applications as a')
                ->join('overtime_types as b', 'a.overtime_type_id', '=', 'b.id')
                ->where('a.id', $id)
                ->get();

            if (count($overtime_data) == 0) {
                $dummy_overtime_data = array(
                    'date' => null,
                    'total_hours' => 0,
                    'rate' => 0,
                    'service_credits' => false
                );

                $overtime_data = (object)$dummy_overtime_data;
                $overtime_data = collect([$overtime_data]);
            }

            $date = $overtime_data[0]->date;
            $day_id = Carbon::parse($date)->isoWeekday();

            $work_schedule_id = $emp_data2[0]->work_schedule_id;
            $is_shifting = $emp_data2[0]->is_shifting;

            if ($is_shifting == false) {
                $schedule_data = DB::table('fix_schedules as a')
                    ->join('fix_schedules_details as b', 'a.id', '=', 'b.fix_schedule_id')
                    ->select('b.work_hours')
                    ->where(['a.id' => $work_schedule_id, 'b.day_id' => $day_id])
                    ->get();
            } else {
                $schedule_data = DB::table('shift_schedules_headers as a')
                    ->join('shift_schedules_details as b', 'a.id', '=', 'b.shift_schedule_id')
                    ->select('b.work_hours')
                    ->where(['a.id' => $work_schedule_id, 'b.shift_date' => $date])
                    ->get();
            }

            if (count($schedule_data) == 0) {
                $dummy_schedule_data = array(
                    'work_hours' => 0
                );

                $schedule_data = (object)$dummy_schedule_data;
                $schedule_data = collect([$schedule_data]);
            }

            $salary = $emp_data2[0]->salary;
            $work_days = $emp_data2[0]->work_days;
            $ot_start = strtotime($overtime_data[0]->date_time_from);
            $ot_end = strtotime($overtime_data[0]->date_time_to);
            $ot_rate = $overtime_data[0]->rate;
            $nd_rate = $overtime_data[0]->nd_rating;
            $nd_start = $overtime_data[0]->nd_from == null ? 0 : strtotime($overtime_data[0]->nd_from);
            $nd_end = $overtime_data[0]->nd_to == null ? 0 : strtotime($overtime_data[0]->nd_to);
            $ot_hours = $overtime_data[0]->total_hours;

            $nd_hours = 0;
            $with_nd = false;

            if ($schedule_data[0]->work_hours > 0) {
                $work_hours = $schedule_data[0]->work_hours;
            } else {
                $work_hours = $emp_data2[0]->work_hours;
            }

            if ($work_hours == 0 || $work_hours == null) {
                $work_hours = 8;
            }

            if ($salary == 0) {
                $hourly_rate = 0;
            } else {
                $hourly_rate = (($salary / $work_days) / $work_hours);
            }

            // Compute Nght Differential.
            if ($nd_start != 0 && $nd_end != 0) {
                $nd_from = Carbon::parse($nd_start);
                $nd_to = Carbon::parse($nd_end);
                $nd_to = $nd_to->addDay();
                $nd_setup_hours = ($nd_to->diffInMinutes($nd_from, true) / 60);
                $with_nd = true;

                if ($ot_end >= $nd_end) {

                    $from = Carbon::parse($nd_start);
                    $to = Carbon::parse($ot_end);
                    $to = $to->addDay();

                    $nd_hours = ($to->diffInMinutes($from, true) / 60);

                    if ($nd_hours > $nd_setup_hours) {
                        $from = Carbon::parse($nd_start);
                        $to = Carbon::parse($nd_end);
                        $to = $to->addDay();

                        $nd_hours = ($to->diffInMinutes($from, true) / 60);
                    }
                } elseif ($ot_end < $nd_end) {
                    $from = Carbon::parse($nd_start);
                    $to = Carbon::parse($nd_end);
                    $to = $to->addDay();
                    $nd_hours = ($to->diffInMinutes($from, true) / 60);
                } else {
                    $nd_hours = 0;
                }
            } else {
                $with_nd = false;
                $nd_hours = 0;
            }

            if ($with_nd) {
                $ot_hours = $ot_hours - $nd_hours;
                $ot_amount = (($hourly_rate * $ot_rate) * $ot_hours);
                $ot_nd_amount = ((($hourly_rate * $ot_rate) * $nd_rate) * $nd_hours);
            } else {
                $ot_amount = (($hourly_rate * $ot_rate) * $ot_hours);
                $ot_nd_amount = 0;
            }

            $emp_id_data = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->leftJoin('departments as c', 'b.id', '=', 'c.employee_id')
                ->selectRaw('case when b.id = null then 0 else b.id end as id')
                ->selectRaw('case when c.employee_id = null then 0 else c.employee_id end as supervisor_id')
                ->where('a.id', Auth::user()->id)
                ->get();

            if ($emp_id_data->isNotEmpty()) {
                $approver_emp_id = $emp_id_data[0]->id;
            } else {
                $approver_emp_id = 0;
            }

            // Check if Approver (overtime type_id = 3 only; hierarchical)
            $approver_1 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_1 as supervisor_id'
                )
                ->where('a.type_id', 3)
                ->whereRaw("
                        b.employee_id = $emp_id
                        AND (a.branch_approver_id_1 = $approver_emp_id OR a.approver_id_1  = $approver_emp_id OR a.division_approver_id_1 = $approver_emp_id OR a.section_approver_id_1 = $approver_emp_id)
                    ")
                ->distinct()
                ->get();

            $approver_2 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_2 as supervisor_id'
                )
                ->where('a.type_id', 3)
                ->where('b.employee_id', $emp_id)
                ->where('a.approver_id_2', $approver_emp_id)
                ->distinct()
                ->get();

            $approver_3 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_3 as supervisor_id'
                )
                ->where('a.type_id', 3)
                ->where('b.employee_id', $emp_id)
                ->where('a.approver_id_3', $approver_emp_id)
                ->distinct()
                ->get();

            $process_date = date("Y-m-d", strtotime(now()));

            if ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
                $ot_data = array(
                    'approved' =>  true,
                    'disapproved' => false,
                    'ot_amount' => $ot_amount,
                    'nd_amount' => $ot_nd_amount,
                    'processed_date' => $process_date,
                    'processed_by' => $approver_emp_id,
                    'approved_remarks' => $remarks
                );
            } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {

                // Save Service Credits
                if ($overtime_data[0]->service_credits == true) {

                    $Service_Credits = DB::table('leave_types')->where('service_credit', true)->get();

                    $credits = (($ot_hours * $ot_rate) / $work_hours);

                    if ($Service_Credits->isEmpty()) {
                        $leave_type_id = 0;
                    } else {
                        $leave_type_id = $Service_Credits[0]->id;
                    }

                    $Leave_Credits = DB::table('leave_credits')->where(['leave_type_id' => $leave_type_id, 'employee_id' => $emp_id])->get();

                    if (count($Leave_Credits) == 0) {
                        $leave_credits = array(
                            'employee_id' =>  $emp_id,
                            'leave_type_id' =>  $leave_type_id,
                            'credits' => $credits,
                        );

                        DB::table('leave_credits')->insert($leave_credits);
                    } else {

                        $credit_final = $Leave_Credits[0]->credits + $credits;

                        $leave_credits = array(
                            'credits' => $credit_final,
                        );

                        DB::table('leave_credits')->where(['leave_type_id' => $leave_type_id, 'employee_id' => $emp_id])->update($leave_credits);
                    }
                }

                $ot_data = array(
                    'approved_2' =>  true,
                    'disapproved_2' =>  false,
                    'processed_date_2' => $process_date,
                    'processed_by_2' => $approver_emp_id,
                    'approved_remarks' => $remarks
                );
            } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
                // User is both level 1 and level 2 approver – set only the level that has not yet acted
                $ot = $overtime_data[0];
                $level1_done = !empty($ot->approved);
                $level2_done = !empty($ot->approved_2) || !empty($ot->disapproved_2);

                if (!$level1_done) {
                    // Act as level 1 only – do not set approved_2
                    $ot_data = array(
                        'approved' =>  true,
                        'disapproved' =>  false,
                        'ot_amount' => $ot_amount,
                        'nd_amount' => $ot_nd_amount,
                        'processed_date' => $process_date,
                        'processed_by' => $approver_emp_id,
                        'approved_remarks' => $remarks
                    );
                } else if (!$level2_done) {
                    // Act as level 2 only
                    if ($overtime_data[0]->service_credits == true) {
                        $Service_Credits = DB::table('leave_types')->where('service_credit', true)->get();
                        $credits = (($ot_hours * $ot_rate) / $work_hours);
                        if ($Service_Credits->isEmpty()) {
                            $leave_type_id = 0;
                        } else {
                            $leave_type_id = $Service_Credits[0]->id;
                        }
                        $Leave_Credits = DB::table('leave_credits')->where(['leave_type_id' => $leave_type_id, 'employee_id' => $emp_id])->get();
                        if (count($Leave_Credits) == 0) {
                            DB::table('leave_credits')->insert([
                                'employee_id' => $emp_id,
                                'leave_type_id' => $leave_type_id,
                                'credits' => $credits,
                            ]);
                        } else {
                            $credit_final = $Leave_Credits[0]->credits + $credits;
                            DB::table('leave_credits')->where(['leave_type_id' => $leave_type_id, 'employee_id' => $emp_id])->update(['credits' => $credit_final]);
                        }
                    }
                    $ot_data = array(
                        'approved_2' =>  true,
                        'disapproved_2' =>  false,
                        'processed_date_2' => $process_date,
                        'processed_by_2' => $approver_emp_id,
                        'approved_remarks' => $remarks
                    );
                } else {
                    // Both levels already done – should not normally reach here
                    $ot_data = array();
                }
            } elseif ($approver_3->isNotEmpty()) {
                $ot_data = array(
                    'approved_3' =>  true,
                    'disapproved_3' =>  false,
                    'processed_date_3' => $process_date,
                    'processed_by_3' => $approver_emp_id,
                    'approved_remarks' => $remarks
                );
            }

            if (!empty($ot_data)) {
                DB::table('overtime_applications')->where('id', $id)->update($ot_data);

                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Overtime Application',
                    'activity' => 'Approved Overtime',
                    'description' => 'Approved employee overtime application.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'You have successfully approved overtime application!');
            }

            return $this->errorResponse('No approval action available for this application.', 400);
        } catch (\Throwable $th) {
            return $this->serverErrorResponse('Failed to approve overtime application: ' . $th->getMessage());
        }
    }

    public function disapprove($id, $remarks)
    {
        try {
            $emp_id_data = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->leftJoin('departments as c', 'b.id', '=', 'c.employee_id')
                ->selectRaw('case when b.id = null then 0 else b.id end as id')
                ->selectRaw('case when c.employee_id = null then 0 else c.employee_id end as supervisor_id')
                ->where('a.id', Auth::user()->id)
                ->get();

            if ($emp_id_data->isNotEmpty()) {
                $approver_emp_id = $emp_id_data[0]->id;
            } else {
                $approver_emp_id = 0;
            }

            $ot_to_approved = DB::table('overtime_applications')->select('employee_id')->where('id', $id)->get();

            if ($ot_to_approved->isNotEmpty()) {
                $emp_id = $ot_to_approved[0]->employee_id;
            } else {
                $emp_id = 0;
            }

            // Check if Approver (overtime type_id = 3 only; hierarchical)
            $approver_1 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_1 as supervisor_id'
                )
                ->where('a.type_id', 3)
                ->whereRaw("
                        b.employee_id = $emp_id
                        AND (a.branch_approver_id_1 = $approver_emp_id OR a.approver_id_1  = $approver_emp_id or a.division_approver_id_1 = $approver_emp_id or a.section_approver_id_1 = $approver_emp_id)
                    ")
                ->distinct()
                ->get();

            $approver_2 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_2 as supervisor_id'
                )
                ->where('a.type_id', 3)
                ->where('b.employee_id', $emp_id)
                ->where('a.approver_id_2', $approver_emp_id)
                ->distinct()
                ->get();

            $approver_3 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_3 as supervisor_id'
                )
                ->where('a.type_id', 3)
                ->where('b.employee_id', $emp_id)
                ->where('a.approver_id_3', $approver_emp_id)
                ->distinct()
                ->get();

            $process_date = date("Y-m-d", strtotime(now()));

            if ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
                $ot_data = array(
                    'approved' =>  false,
                    'disapproved' =>  true,
                    'ot_amount' => 0,
                    'nd_amount' => 0,
                    'disapprove_remarks' => $remarks,
                    'processed_date' => $process_date,
                    'processed_by' => $approver_emp_id
                );
            } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
                $ot_data = array(
                    'approved_2' =>  false,
                    'disapproved_2' =>  true,
                    'ot_amount' => 0,
                    'nd_amount' => 0,
                    'disapproved_2_remark' => $remarks,
                    'processed_date_2' => $process_date,
                    'processed_by_2' => $approver_emp_id
                );
            } elseif (($approver_1->isEmpty() || $approver_1->isNotEmpty()) && $approver_2->isNotEmpty()) {
                $ot_data = array(
                    'approved' =>  false,
                    'disapproved' =>  true,
                    'ot_amount' => 0,
                    'nd_amount' => 0,
                    'disapprove_remarks' => $remarks,
                    'processed_date' => $process_date,
                    'processed_by' => $approver_emp_id,
                    'approved_2' =>  false,
                    'disapproved_2' =>  true,
                    'disapproved_2_remark' => $remarks,
                    'processed_date_2' => $process_date,
                    'processed_by_2' => $approver_emp_id
                );
            } elseif ($approver_3->isNotEmpty()) {
                $ot_data = array(
                    'approved_3' =>  false,
                    'disapproved_3' =>  true,
                    'ot_amount' => 0,
                    'nd_amount' => 0,
                    'disapproved_3_remark' => $remarks,
                    'processed_date_3' => $process_date,
                    'processed_by_3' => $approver_emp_id
                );
            }

            DB::table('overtime_applications')->where('id', $id)->update($ot_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Main',
                'menu'    => 'Overtime Application',
                'activity' => 'Disapproved Overtime',
                'description' => 'Disapproved employee overtime application.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'You have successfully disapproved overtime application!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to disapprove overtime application: ' . $e->getMessage());
        }
    }

    public function cancel($id, $emp_id, $remarks)
    {
        try {
            $emp_data2 = DB::table('employees as a')
                ->join('time_keeping_setups as b', 'b.employment_type_id', '=', 'a.employment_type_id')
                ->select('b.work_hours', 'b.work_days', 'a.salary', 'a.work_schedule_id', 'a.is_shifting')
                ->where('a.id', $emp_id)
                ->get();

            if (count($emp_data2) == 0) {
                $dummy_emp_data2 = array(
                    'work_hours' => null,
                    'work_days' => null,
                    'salary' => 0,
                    'work_schedule_id' => 0,
                    'is_shifting' => false
                );

                $emp_data2 = (object)$dummy_emp_data2;
                $emp_data2 = collect([$emp_data2]);
            }

            $overtime_data = DB::table('overtime_applications as a')
                ->join('overtime_types as b', 'a.overtime_type_id', '=', 'b.id')
                ->where('a.id', $id)
                ->get();

            if (count($overtime_data) == 0) {
                $dummy_overtime_data = array(
                    'date' => null,
                    'total_hours' => null,
                    'rate' => 0,
                    'service_credits' => null
                );

                $overtime_data = (object)$dummy_overtime_data;
                $overtime_data = collect([$overtime_data]);
            }

            $date = $overtime_data[0]->date;
            $day_id = Carbon::parse($date)->isoWeekday();

            $work_schedule_id = $emp_data2[0]->work_schedule_id;
            $is_shifting = $emp_data2[0]->is_shifting;

            if ($is_shifting == false) {
                $schedule_data = DB::table('fix_schedules as a')
                    ->join('fix_schedules_details as b', 'a.id', '=', 'b.fix_schedule_id')
                    ->select('b.work_hours')
                    ->where(['a.id' => $work_schedule_id, 'b.day_id' => $day_id])
                    ->get();
            } else {
                $schedule_data = DB::table('shift_schedules_headers as a')
                    ->join('shift_schedules_details as b', 'a.id', '=', 'b.shift_schedule_id')
                    ->select('b.work_hours')
                    ->where(['a.id' => $work_schedule_id, 'b.shift_date' => $date])
                    ->get();
            }

            if (count($schedule_data) == 0) {
                $dummy_schedule_data = array(
                    'work_hours' => 0
                );

                $schedule_data = (object)$dummy_schedule_data;
                $schedule_data = collect([$schedule_data]);
            }


            $ot_hours = $overtime_data[0]->total_hours;

            if ($schedule_data[0]->work_hours > 0) {
                $work_hours = $schedule_data[0]->work_hours;
            } else {
                $work_hours = $emp_data2[0]->work_hours;
            }

            // Save Service Credits
            if ($overtime_data[0]->service_credits == true) {

                $Service_Credits = DB::table('leave_types')->where('service_credit', true)->get();

                if (isset($work_hours)) {
                    $credits = $ot_hours / $work_hours;
                } else {
                    $credits = $ot_hours / 8;
                }

                if ($Service_Credits->isEmpty()) {
                    $leave_type_id = 0;
                } else {
                    $leave_type_id = $Service_Credits[0]->id;
                }

                $Leave_Credits = DB::table('leave_credits')->where(['leave_type_id' => $leave_type_id, 'employee_id' => $emp_id])->get();

                if (count($Leave_Credits) > 0) {
                    $credit_final = $Leave_Credits[0]->credits - $credits;

                    $leave_credits = array(
                        'credits' => $credit_final,
                    );

                    DB::table('leave_credits')->where(['leave_type_id' => $leave_type_id, 'employee_id' => $emp_id])->update($leave_credits);
                }
            }

            $emp_id_data = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->leftJoin('departments as c', 'b.id', '=', 'c.employee_id')
                ->selectRaw('case when b.id = null then 0 else b.id end as id')
                ->selectRaw('case when c.employee_id = null then 0 else c.employee_id end as supervisor_id')
                ->where('a.id', Auth::user()->id)
                ->get();

            $ot_data = array(
                'approved' =>  false,
                'disapproved' =>  false,
                'ot_amount' => 0,
                'nd_amount' => 0,
                'is_cancel' => true,
                'canceled_by' => $emp_id_data[0]->id,
                'canceled_date' => now(),
                'canceled_remarks' => $remarks
            );

            DB::table('overtime_applications')->where('id', $id)->update($ot_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Main',
                'menu'    => 'Overtime Application',
                'activity' => 'Cancel Overtime',
                'description' => 'Cancel employee overtime application.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'You have canceled approved overtime application!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to cancel overtime application: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $ot = DB::table('overtime_applications')->where('id', $id)->first();
            if (!$ot) {
                return $this->errorResponse('Overtime application not found.', 404);
            }

            $emp_id_data = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->where('a.id', Auth::user()->id)
                ->select('b.id')
                ->get();

            if ($emp_id_data->isEmpty()) {
                return $this->errorResponse('Employee record not found.', 403);
            }
            $applicant_employee_id = (int) $emp_id_data[0]->id;

            // Only the applicant may delete; only when fully approved
            if ((int) $ot->employee_id !== $applicant_employee_id) {
                return $this->errorResponse('Only the applicant may cancel this overtime application.', 403);
            }

            $approved = (bool) $ot->approved;
            $disapproved = (bool) $ot->disapproved;
            $disapproved_2 = (bool) $ot->disapproved_2;
            $disapproved_3 = (bool) $ot->disapproved_3;
            $approved_2 = (bool) $ot->approved_2;
            $approved_3 = (bool) $ot->approved_3;
            $fully_approved = $approved && !$disapproved && !$disapproved_2 && !$disapproved_3
                && ($approved_2 || (!$approved_2 && !$disapproved_2))
                && ($approved_3 || (!$approved_3 && !$disapproved_3));

            if (!$fully_approved) {
                return $this->errorResponse('Only fully approved overtime applications can be cancelled (deleted) by the applicant.', 400);
            }

            DB::table('overtime_applications')->where('id', $id)->delete();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Overtime Application',
                'menu'    => 'Overtime Application',
                'activity' => 'Delete',
                'description' => 'Deleted (cancelled) fully approved overtime application.',
            );
            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Successfully deleted!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete overtime application: ' . $e->getMessage());
        }
    }

    public function monitoring()
    {
        try {
            $app_key = env("APP_KEY", "");

            $ForapprovalEmployeeOT = DB::table('overtime_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->select(
                    'a.id',
                    'a.overtime_type_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.total_hours',
                    'a.remarks',
                    'a.payroll',
                    'a.service_credits',
                    'a.employee_id',
                    'd.name as overtime_type_name'
                )
                ->where([
                    'a.approved' => false,
                    'a.disapproved' => false,
                    'a.approved_2' => false,
                    'a.disapproved_2' => false,
                    'a.is_cancel' => false
                ])
                ->get();

            $ApprovedEmployeeOT = DB::table('overtime_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->leftJoin('employees as d1', 'd1.id', '=', 'a.processed_by')
                ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
                ->select(
                    'a.id',
                    'a.overtime_type_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.total_hours',
                    'a.remarks',
                    'a.payroll',
                    'a.service_credits',
                    'a.employee_id',
                    'd.name as overtime_type_name',
                    'a.processed_date',
                    'a.processed_date_2',
                    DB::raw("CASE WHEN ISNULL(d1.is_encrypted,0) = 0 THEN
                                    CONCAT(d1.first_name,' ',d1.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](d1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d1.last_name,'$app_key'))
                                END as approver_1"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                   CONCAT(e.first_name,' ',e.last_name)
                               ELSE
                                   RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                               END as approver_2")
                )
                ->where([
                    'a.disapproved' => false,
                    'a.disapproved_2' => false,
                    'a.is_cancel' => false
                ])
                ->where('a.approved', true)
                ->orWhere('a.approved_2', true)
                ->get();

            $DisapprovedEmployeeOT = DB::table('overtime_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->leftJoin('employees as d1', 'd1.id', '=', 'a.processed_by')
                ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
                ->select(
                    'a.id',
                    'a.overtime_type_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.total_hours',
                    'a.remarks',
                    'a.payroll',
                    'a.service_credits',
                    'a.employee_id',
                    'd.name as overtime_type_name',
                    'a.disapprove_remarks',
                    'a.processed_date',
                    'a.processed_date_2',
                    DB::raw("CASE WHEN ISNULL(d1.is_encrypted,0) = 0 THEN
                                   CONCAT(d1.first_name,' ',d1.last_name)
                               ELSE
                                   RTRIM([dbo].[ufn_DecryptString](d1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d1.last_name,'$app_key'))
                               END as approver_1"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                    CONCAT(e.first_name,' ',e.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                                END as approver_2")
                )
                ->where('is_cancel', false)
                ->where('a.disapproved', true)
                ->orWhere('a.disapproved_2', true)
                ->get();

            $CancelledEmployeeOT = DB::table('overtime_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->leftJoin('employees as b1', 'a.canceled_by', '=', 'b1.id')
                ->select(
                    'a.id',
                    'a.overtime_type_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.total_hours',
                    'a.remarks',
                    'a.payroll',
                    'a.service_credits',
                    'a.employee_id',
                    'd.name as overtime_type_name',
                    'a.disapprove_remarks',
                    DB::raw("CASE WHEN ISNULL(b1.is_encrypted,0) = 0 THEN
                                    CONCAT(b1.first_name,' ',b1.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b1.last_name,'$app_key'))
                                END as cancelled_by"),
                    'a.canceled_date',
                    'a.canceled_remarks'
                )
                ->where(['is_cancel' => true])
                ->get();

            return $this->successResponse([
                'for_approval' => $ForapprovalEmployeeOT,
                'approved' => $ApprovedEmployeeOT,
                'disapproved' => $DisapprovedEmployeeOT,
                'cancelled' => $CancelledEmployeeOT
            ], 'Overtime applications monitoring data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve overtime applications monitoring data: ' . $e->getMessage());
        }
    }

    public function attachments($id)
    {
        try {
            $attachments = DB::table('overtime_attachments')->where('overtime_id', $id)->get();

            return $this->successResponse($attachments, 'Overtime attachments retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve overtime attachments: ' . $e->getMessage());
        }
    }

    public function remove_attachments($id)
    {
        try {
            $attachment = DB::table('overtime_attachments')->where('id', $id)->first();

            if (!$attachment) {
                return $this->notFoundResponse('Attachment not found');
            }

            $data = DB::table('overtime_attachments')->where('id', $id)->delete();

            return $this->successResponse($data, 'Attachment removed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to remove attachment: ' . $e->getMessage());
        }
    }

    public function cancel_attachment(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'overtime_header_id' => 'required|exists:overtime_applications,id',
                'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx,doc,docx|max:10240'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $id = $request->overtime_header_id;
            $attachment_data = [];

            // Save Leave Attachments
            if ($request->hasFile('attachment')) {
                $allowedfileExtension = ['jpg', 'jpeg', 'png', 'pdf', 'xls', 'xlsx', 'doc', 'docx'];
                $files = $request->file('attachment');

                if (isset($files)) {
                    $file_name = $files->getClientOriginalName();
                    $file_path = storage_path('app/OT_cancelled_documents/' . 'DOCS' . $id . '_' . $file_name);
                    $extension = $files->getClientOriginalExtension();
                    $check = in_array($extension, $allowedfileExtension);

                    if ($check) {
                        $attachment_data = [
                            'attachment_name' => $file_name,
                            'path' => $file_path,
                            'extension' => $extension
                        ];

                        // Update Time Data Table
                        DB::table('overtime_applications')->where('id', $id)->update($attachment_data);

                        // Save attachment to path.
                        $request->attachment->storeAs('OT_cancelled_documents', 'DOCS' . $id . '_' . $file_name);
                    } else {
                        return $this->errorResponse('Invalid file type. Allowed types: ' . implode(', ', $allowedfileExtension), 400);
                    }
                }
            }

            return $this->successResponse(null, 'Successfully Cancelled Application.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to cancel application: ' . $e->getMessage());
        }
    }

    public function download($id)
    {
        try {
            $documents = DB::table('overtime_applications as a')
                ->select(
                    'a.id',
                    'a.employee_id',
                    'a.attachment_name'
                )
                ->where('a.id', $id)
                ->first();

            if (!$documents) {
                return $this->notFoundResponse('Document not found');
            }

            $pathToFile = storage_path('app/OT_cancelled_documents/' . 'DOCS' . $documents->id . '_' . $documents->attachment_name);

            if (!file_exists($pathToFile)) {
                return $this->notFoundResponse('File not found on server');
            }

            $fileContent = file_get_contents($pathToFile);
            $base64Content = base64_encode($fileContent);

            return $this->successResponse([
                'file_content' => $base64Content,
                'filename' => $documents->attachment_name,
                'content_type' => mime_content_type($pathToFile)
            ], 'Document downloaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download document: ' . $e->getMessage());
        }
    }

    public function downloadapproval($id)
    {
        try {
            $documents = DB::table('overtime_applications as a')
                ->leftJoin('overtime_attachments as d', 'a.id', '=', 'd.overtime_id')
                ->select(
                    'a.id',
                    'a.employee_id',
                    'd.attachment_name'
                )
                ->where('a.id', $id)
                ->first();

            if (!$documents) {
                return $this->notFoundResponse('Document not found');
            }

            $pathToFile = storage_path('app/overtime_attachments/' . 'OT' . $documents->employee_id . '_' . $documents->attachment_name);

            if (!file_exists($pathToFile)) {
                return $this->notFoundResponse('File not found on server');
            }

            $fileContent = file_get_contents($pathToFile);
            $base64Content = base64_encode($fileContent);

            return $this->successResponse([
                'file_content' => $base64Content,
                'filename' => $documents->attachment_name,
                'content_type' => mime_content_type($pathToFile)
            ], 'Approval document downloaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download approval document: ' . $e->getMessage());
        }
    }

    public function downloadAttachment($id)
    {
        try {
            $documents = DB::table('overtime_attachments as a')
                ->join('overtime_applications as b', 'a.overtime_id', '=', 'b.id')
                ->select(
                    'a.id',
                    'b.employee_id',
                    'a.attachment_name'
                )
                ->where('a.overtime_id', $id)
                ->first();

            if (!$documents) {
                return $this->notFoundResponse('Attachment not found');
            }

            $pathToFile = storage_path('app/overtime_attachments/' . 'OT' . $documents->employee_id . '_' . $documents->attachment_name);

            if (!file_exists($pathToFile)) {
                return $this->notFoundResponse('File not found on server');
            }

            $fileContent = file_get_contents($pathToFile);
            $base64Content = base64_encode($fileContent);

            return $this->successResponse([
                'file_content' => $base64Content,
                'filename' => $documents->attachment_name,
                'content_type' => mime_content_type($pathToFile)
            ], 'Attachment downloaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download attachment: ' . $e->getMessage());
        }
    }

    /**
     * Build common data for Overtime Authorization Request based on selected IDs.
     */
    protected function buildOvertimeAuthorizationData(array $ids)
    {
        $app_key = env("APP_KEY", "");

        // Load selected overtime applications with employee and department info
        $records = DB::table('overtime_applications as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
            ->select(
                'a.id',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'a.total_hours',
                'a.remarks',
                'a.payroll',
                'a.service_credits',
                'a.employee_id',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                            CONCAT(b.first_name,' ',b.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                        END as employee_name"),
                'd.name as department_name'
            )
            ->whereIn('a.id', $ids)
            ->orderBy('a.date')
            ->get();

        if ($records->isEmpty()) {
            return [null, null];
        }

        $first = $records->first();

        // Date prepared is today
        $date_prepared = \Carbon\Carbon::now()->format('F d, Y');

        // Month and year based on first overtime record date
        $month = $first->date ? \Carbon\Carbon::parse($first->date)->format('F') : '';
        $year = $first->date ? \Carbon\Carbon::parse($first->date)->format('Y') : \Carbon\Carbon::now()->year;

        // Section / Division from department
        $section_division = $first->department_name ?? '';

        // Build items for the table
        $items = [];
        foreach ($records as $rec) {
            $period = '';
            if ($rec->date) {
                $datePart = \Carbon\Carbon::parse($rec->date)->format('M d, Y');
                $timeFrom = $rec->date_time_from ? \Carbon\Carbon::parse($rec->date_time_from)->format('h:i A') : '';
                $timeTo = $rec->date_time_to ? \Carbon\Carbon::parse($rec->date_time_to)->format('h:i A') : '';
                if ($timeFrom || $timeTo) {
                    $period = trim($datePart . ' ' . trim($timeFrom . ' - ' . $timeTo));
                } else {
                    $period = $datePart;
                }
            }

            // Determine OT pay status - simple rule based on service credits / payroll flags
            $otPayStatus = '';
            if (!is_null($rec->service_credits) && $rec->service_credits > 0) {
                $otPayStatus = 'WITHOUT OT PAY (Service Credit)';
            } elseif (!is_null($rec->payroll) && $rec->payroll) {
                $otPayStatus = 'WITH OT PAY';
            }

            $items[] = [
                'activity' => $rec->remarks ?? '',
                'quantity' => 1,
                'mh_needed' => $rec->total_hours,
                'period' => $period,
                'person' => $rec->employee_name ?? '',
                'ot_pay_status' => $otPayStatus
            ];
        }

        // Dept head info - default to empty (signature line will show)
        $dept_head_name = '';
        $dept_head_position = '';
        $has_dept_head_data = false;

        // Executive Director (approver) - fixed default like other reports
        $approver_name = 'CLARE MARI S. TORRALBA';
        $approver_position = 'Executive Director';
        $has_executive_director_data = true;

        $data = [
            'date_prepared' => $date_prepared,
            'month' => $month,
            'year' => $year,
            'section_division' => $section_division,
            'items' => $items,
            'dept_head_name' => $dept_head_name,
            'dept_head_position' => $dept_head_position,
            'has_dept_head_data' => $has_dept_head_data,
            'approver_name' => $approver_name,
            'approver_position' => $approver_position,
            'has_executive_director_data' => $has_executive_director_data
        ];

        return [$data, $records];
    }

    /**
     * Print Overtime Authorization Request form for selected overtime applications (PDF).
     */
    public function printOvertimeAuthorizationRequest(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (!is_array($ids) || empty($ids)) {
                return response()->json(['success' => false, 'message' => 'No overtime records selected.'], 400);
            }

            [$data, $records] = $this->buildOvertimeAuthorizationData($ids);

            if (!$data) {
                return response()->json(['success' => false, 'message' => 'Selected overtime records not found.'], 404);
            }

            $pdf = \PDF::loadView('overtime_authorization_request', $data)->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'landscape');
            $pdf->output();

            return $pdf->stream('Overtime_Authorization_Request.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Overtime Authorization Request form.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download Overtime Authorization Request as Word document (DOCX).
     */
    public function downloadOvertimeAuthorizationDocx(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (!is_array($ids) || empty($ids)) {
                return response()->json(['success' => false, 'message' => 'No overtime records selected.'], 400);
            }

            [$data, $records] = $this->buildOvertimeAuthorizationData($ids);

            if (!$data) {
                return response()->json(['success' => false, 'message' => 'Selected overtime records not found.'], 404);
            }

            $phpWord = new PhpWord();

            $section = $phpWord->addSection([
                'orientation' => 'landscape',
                'marginTop' => 720,
                'marginRight' => 720,
                'marginBottom' => 720,
                'marginLeft' => 720
            ]);

            // Header right: form number and underlined date prepared (to match PDF)
            $headerRun1 = $section->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            $headerRun1->addText('AFM-PER.FR#14/Rev.00/05-16-14', ['size' => 9]);

            $headerRun2 = $section->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            $headerRun2->addText('Date Prepared: ', ['size' => 10]);
            $headerRun2->addText($data['date_prepared'] ?? '', ['size' => 10, 'underline' => 'single']);

            // Title section (centered)
            $section->addText('PHILIPPINE TRADE TRAINING CENTER', ['bold' => true, 'size' => 12], [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter' => 0
            ]);
            $section->addText('OVERTIME AUTHORIZATION REQUEST', ['bold' => true, 'size' => 11], [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter' => 0
            ]);

            // "FOR THE MONTH OF: ____ January ____ 2026" style line
            $monthYearRun = $section->addTextRun([
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter' => 240
            ]);
            $monthYearRun->addText('FOR THE MONTH OF: ', ['size' => 10]);
            $monthYearRun->addText($data['month'] ?? '', ['size' => 10, 'underline' => 'single']);
            $monthYearRun->addText('  ', ['size' => 10]); // small gap
            $monthYearRun->addText($data['year'] ?? '', ['size' => 10, 'underline' => 'single']);

            // Section/Division with underlined label
            $sectionRun = $section->addTextRun(['spaceAfter' => 240]);
            $sectionRun->addText('SECTION/DIVISION: ', ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $sectionRun->addText($data['section_division'] ?? '', ['size' => 10]);

            // Main table - full grid with header shading like PDF
            $table = $section->addTable([
                'cellMargin'   => 80,
                'alignment'    => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
                'borderSize'   => 6,
                'borderColor'  => '000000'
            ]);

            // Header row
            $table->addRow();
            $hdrStyle = ['bold' => true, 'size' => 9];
            $hdrAlign = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];
            $headerBg = ['bgColor' => 'FFF8DC'];
            $table->addCell(3000, $headerBg)->addText('ACTIVITIES TO BE ACCOMPLISHED', $hdrStyle, $hdrAlign);
            $table->addCell(1500, $headerBg)->addText('ESTIMATED QUANTITY', $hdrStyle, $hdrAlign);
            $table->addCell(1500, $headerBg)->addText('EST. MH NEEDED', $hdrStyle, $hdrAlign);
            $table->addCell(2000, $headerBg)->addText('PERIOD COVERED', $hdrStyle, $hdrAlign);
            $table->addCell(2500, $headerBg)->addText('PERSON RESPONSIBLE', $hdrStyle, $hdrAlign);
            $table->addCell(2500, $headerBg)->addText('WITH / WITHOUT OT PAY', $hdrStyle, $hdrAlign);

            // Body rows
            $items = $data['items'] ?? [];
            foreach ($items as $item) {
                $table->addRow();
                $table->addCell(3000)->addText($item['activity'] ?? '', ['size' => 9]);
                $table->addCell(1500)->addText((string)($item['quantity'] ?? ''), ['size' => 9], $hdrAlign);
                $table->addCell(1500)->addText((string)($item['mh_needed'] ?? ''), ['size' => 9], $hdrAlign);
                $table->addCell(2000)->addText($item['period'] ?? '', ['size' => 9]);
                $table->addCell(2500)->addText($item['person'] ?? '', ['size' => 9]);
                $table->addCell(2500)->addText($item['ot_pay_status'] ?? '', ['size' => 9]);
            }

            // Pad to 8 rows, keeping grid look
            $rowCount = count($items);
            for ($i = $rowCount; $i < 8; $i++) {
                $table->addRow();
                $table->addCell(3000)->addText('', ['size' => 9]);
                $table->addCell(1500)->addText('', ['size' => 9]);
                $table->addCell(1500)->addText('', ['size' => 9]);
                $table->addCell(2000)->addText('', ['size' => 9]);
                $table->addCell(2500)->addText('', ['size' => 9]);
                $table->addCell(2500)->addText('', ['size' => 9]);
            }

            // Add whitespace after main table before signatories
            $section->addTextBreak(2);

            // Signature section (match PDF: line + Dept Head on left; underlined ED on right)
            $sigTable = $section->addTable([
                'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER
            ]);
            $sigTable->addRow();

            // Dept Head
            $leftCell = $sigTable->addCell(5000);
            if (empty($data['has_dept_head_data'])) {
                $leftCell->addText('______________________________', [], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }
            // Only show name/position when actual data exists to avoid large gap
            if (!empty($data['has_dept_head_data']) && !empty($data['dept_head_name'])) {
                $leftCell->addText($data['dept_head_name'] ?? '', ['bold' => true, 'size' => 10], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                if (!empty($data['dept_head_position'])) {
                    $leftCell->addText($data['dept_head_position'] ?? '', ['size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                }
            }
            // Label directly under the line
            $leftCell->addText('Dept Head', ['size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

            // Executive Director
            $rightCell = $sigTable->addCell(5000);
            if (empty($data['has_executive_director_data'])) {
                $rightCell->addText('______________________________', [], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }
            $rightCell->addText($data['approver_name'] ?? '', ['bold' => true, 'size' => 10, 'underline' => 'single'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $rightCell->addText($data['approver_position'] ?? '', ['size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

            $tempFile = tempnam(sys_get_temp_dir(), 'ot_auth_req_');
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            $fileName = 'Overtime_Authorization_Request.docx';

            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Overtime Authorization Request Word document.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download Overtime Authorization Request as Excel document.
     */
    public function downloadOvertimeAuthorizationExcel(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (!is_array($ids) || empty($ids)) {
                return response()->json(['success' => false, 'message' => 'No overtime records selected.'], 400);
            }

            [$data, $records] = $this->buildOvertimeAuthorizationData($ids);

            if (!$data) {
                return response()->json(['success' => false, 'message' => 'Selected overtime records not found.'], 404);
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Page setup to mirror landscape A4
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

            // Column widths approximating Word table
            $sheet->getColumnDimension('A')->setWidth(40);
            $sheet->getColumnDimension('B')->setWidth(14);
            $sheet->getColumnDimension('C')->setWidth(14);
            $sheet->getColumnDimension('D')->setWidth(18);
            $sheet->getColumnDimension('E')->setWidth(24);
            $sheet->getColumnDimension('F')->setWidth(24);

            $row = 1;

            // Header right (form no. and underlined date prepared)
            $sheet->setCellValue("F{$row}", 'AFM-PER.FR#14/Rev.00/05-16-14');
            $sheet->getStyle("F{$row}")->getFont()->setSize(9);
            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;

            $sheet->setCellValue("E{$row}", 'Date Prepared:');
            $sheet->setCellValue("F{$row}", $data['date_prepared'] ?? '');
            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F{$row}")->getFont()->setUnderline(true);
            $row += 2;

            // Title block
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->setCellValue("A{$row}", 'PHILIPPINE TRADE TRAINING CENTER');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;

            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->setCellValue("A{$row}", 'OVERTIME AUTHORIZATION REQUEST');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;

            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->setCellValue("A{$row}", 'FOR THE MONTH OF: ' . ($data['month'] ?? '') . ' ' . ($data['year'] ?? ''));
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row += 2;

            // Section / Division (label emphasized)
            $sheet->setCellValue("A{$row}", 'SECTION/DIVISION: ' . ($data['section_division'] ?? ''));
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setUnderline(true);
            $row += 2;

            // Table header with light yellow background and borders
            $sheet->setCellValue("A{$row}", 'ACTIVITIES TO BE ACCOMPLISHED');
            $sheet->setCellValue("B{$row}", 'ESTIMATED QUANTITY');
            $sheet->setCellValue("C{$row}", 'EST. MH NEEDED');
            $sheet->setCellValue("D{$row}", 'PERIOD COVERED');
            $sheet->setCellValue("E{$row}", 'PERSON RESPONSIBLE');
            $sheet->setCellValue("F{$row}", 'WITH / WITHOUT OT PAY');

            $headerRange = "A{$row}:F{$row}";
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF8DC');
            $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;

            // Body rows
            $items = $data['items'] ?? [];
            $startBodyRow = $row;
            foreach ($items as $item) {
                $sheet->setCellValue("A{$row}", $item['activity'] ?? '');
                $sheet->setCellValue("B{$row}", (string)($item['quantity'] ?? ''));
                $sheet->setCellValue("C{$row}", (string)($item['mh_needed'] ?? ''));
                $sheet->setCellValue("D{$row}", $item['period'] ?? '');
                $sheet->setCellValue("E{$row}", $item['person'] ?? '');
                $sheet->setCellValue("F{$row}", $item['ot_pay_status'] ?? '');
                $row++;
            }

            // Pad to 8 total body rows
            $rowCount = count($items);
            for ($i = $rowCount; $i < 8; $i++) {
                $sheet->setCellValue("A{$row}", '');
                $sheet->setCellValue("B{$row}", '');
                $sheet->setCellValue("C{$row}", '');
                $sheet->setCellValue("D{$row}", '');
                $sheet->setCellValue("E{$row}", '');
                $sheet->setCellValue("F{$row}", '');
                $row++;
            }

            // Apply grid borders to body
            $sheet->getStyle("A{$startBodyRow}:F" . ($row - 1))
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Whitespace after table before signatures
            $row += 2;

            // Signature section – centered in their halves
            // Dept Head (left: columns B–C)
            $sheet->mergeCells("B{$row}:C{$row}");
            $sheet->setCellValue("B{$row}", '______________________________');
            $sheet->getStyle("B{$row}:C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;

            $sheet->mergeCells("B{$row}:C{$row}");
            $sheet->setCellValue("B{$row}", 'Dept Head');
            $sheet->getStyle("B{$row}:C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Executive Director (right: columns E–F)
            $sigRow = $row - 1; // align top line with Dept Head line
            $sheet->mergeCells("E{$sigRow}:F{$sigRow}");
            $sheet->setCellValue("E{$sigRow}", '______________________________');
            $sheet->getStyle("E{$sigRow}:F{$sigRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $nameRow = $sigRow + 1;
            $sheet->mergeCells("E{$nameRow}:F{$nameRow}");
            $sheet->setCellValue("E{$nameRow}", $data['approver_name'] ?? '');
            $sheet->getStyle("E{$nameRow}:F{$nameRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$nameRow}:F{$nameRow}")->getFont()->setBold(true)->setUnderline(true);

            $posRow = $nameRow + 1;
            $sheet->mergeCells("E{$posRow}:F{$posRow}");
            $sheet->setCellValue("E{$posRow}", $data['approver_position'] ?? '');
            $sheet->getStyle("E{$posRow}:F{$posRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $filename = 'Overtime_Authorization_Request.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'ot_auth_req_xlsx_');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Overtime Authorization Request Excel document.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enrich overtime data with approver configuration levels
     */
    private function enrichOvertimesWithApproverConfig($overtimes)
    {
        $enriched = [];

        foreach ($overtimes as $overtime) {
            $overtime = (array) $overtime;

            // Skip if no employee_id
            if (!isset($overtime['employee_id']) || empty($overtime['employee_id'])) {
                $overtime['has_approver_level_2'] = 0;
                $overtime['has_approver_level_3'] = 0;
                $enriched[] = (object) $overtime;
                continue;
            }

            // Get approver configuration for this employee
            $approverConfig = DB::table('approver_details as ad')
                ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                ->where('ad.employee_id', $overtime['employee_id'])
                ->where('ah.type_id', 3) // Overtime approver setup
                ->select(
                    DB::raw("MAX(CASE WHEN ah.approver_id_2 IS NOT NULL AND ah.approver_id_2 > 0 THEN 1 ELSE 0 END) as has_approver_level_2"),
                    DB::raw("MAX(CASE WHEN ah.approver_id_3 IS NOT NULL AND ah.approver_id_3 > 0 THEN 1 ELSE 0 END) as has_approver_level_3")
                )
                ->first();

            if ($approverConfig) {
                $overtime['has_approver_level_2'] = $approverConfig->has_approver_level_2;
                $overtime['has_approver_level_3'] = $approverConfig->has_approver_level_3;
            } else {
                $overtime['has_approver_level_2'] = 0;
                $overtime['has_approver_level_3'] = 0;
            }

            $enriched[] = (object) $overtime;
        }

        return $enriched;
    }
}
