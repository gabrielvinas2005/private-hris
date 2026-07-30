<?php

namespace App\Http\Controllers;

use Auth;
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

    /**
     * Convert an approved overtime application to CTO by setting service_credits = 1.
     * Only allowed if approved = 1 OR approved_2 = 1 OR approved_3 = 1 and not disapproved/cancelled.
     */
    public function convertToCTO($id)
    {
        try {
            $affected = DB::table('overtime_applications')
                ->where('id', $id)
                ->where(function ($q) {
                    $q->where('approved', true)
                        ->orWhere('approved_2', true)
                        ->orWhere('approved_3', true);
                })
                ->where(function ($q) {
                    $q->whereNull('is_cancel')->orWhere('is_cancel', false);
                })
                ->where(function ($q) {
                    $q->whereNull('disapproved')->orWhere('disapproved', false);
                })
                ->update(['service_credits' => true]);

            if ($affected === 0) {
                return $this->errorResponse('Record not eligible for conversion or not found.');
            }

            return $this->successResponse(['id' => (int)$id, 'service_credits' => true]);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    /**
     * Revert CTO conversion back to cash OT by setting service_credits = 0.
     * Only allowed if not disapproved/cancelled and currently service_credits = 1
     */
    public function revertCTO($id)
    {
        try {
            $affected = DB::table('overtime_applications')
                ->where('id', $id)
                ->where(function ($q) {
                    $q->where('approved', true)
                        ->orWhere('approved_2', true)
                        ->orWhere('approved_3', true);
                })
                ->where(function ($q) {
                    $q->whereNull('is_cancel')->orWhere('is_cancel', false);
                })
                ->where(function ($q) {
                    $q->whereNull('disapproved')->orWhere('disapproved', false);
                })
                ->where('service_credits', true)
                ->update(['service_credits' => false]);

            if ($affected === 0) {
                return $this->errorResponse('Record not eligible to revert or not found.');
            }

            return $this->successResponse(['id' => (int)$id, 'service_credits' => false]);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
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

            // Check if Approver Start
            $approver_1 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_1 as supervisor_id'
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
                    // ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        // Original (decrypting) employee name fields kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.first_name as first_name"),
                        DB::raw("b.middle_name as middle_name"),
                        DB::raw("b.last_name as last_name"),
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
                        // 'e.attachment_name'
                    )
                    ->where(['a.approved' => false, 'a.disapproved' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.branch_approver_id_1', $emp_id)
                            ->orWhere('b.approver_id_1', $emp_id)
                            ->orWhere('b.division_approver_id_1', $emp_id)
                            ->orWhere('b.section_approver_id_1', $emp_id);
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
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.branch_approver_id_1', $emp_id)
                            ->orWhere('b.approver_id_1', $emp_id)
                            ->orWhere('b.division_approver_id_1', $emp_id)
                            ->orWhere('b.section_approver_id_1', $emp_id);
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
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
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
                            ->where('b.branch_approver_id_1', $emp_id)
                            ->orWhere('b.approver_id_1', $emp_id)
                            ->orWhere('b.division_approver_id_1', $emp_id)
                            ->orWhere('b.section_approver_id_1', $emp_id);
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
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
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
                            ->where('b.branch_approver_id_1', $emp_id)
                            ->orWhere('b.approver_id_1', $emp_id)
                            ->orWhere('b.division_approver_id_1', $emp_id)
                            ->orWhere('b.section_approver_id_1', $emp_id);
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
                    // ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
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
                        // 'e.attachment_name'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => false, 'a.disapproved_2' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
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
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => true, 'a.disapproved_2' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
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
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
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
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
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
                    // ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
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
                        // 'e.attachment_name'
                    )
                    ->where(['a.approved' => false, 'a.disapproved' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.branch_approver_id_1', $emp_id)
                            ->orWhere('b.approver_id_1', $emp_id)
                            ->orWhere('b.division_approver_id_1', $emp_id)
                            ->orWhere('b.section_approver_id_1', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->where('is_cancel', false)
                    ->distinct();

                $ForapprovalEmployeeOT = DB::table('overtime_applications as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                    // ->leftJoin('overtime_attachments as e', 'a.id', '=', 'e.overtime_id')
                    ->select(
                        'a.id',
                        'a.overtime_type_id',
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
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
                        // 'e.attachment_name'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => false, 'a.disapproved_2' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
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
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.branch_approver_id_1', $emp_id)
                            ->orWhere('b.approver_id_1', $emp_id)
                            ->orWhere('b.division_approver_id_1', $emp_id)
                            ->orWhere('b.section_approver_id_1', $emp_id);
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
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
                        'a.created_at',
                        'a.date',
                        'a.date_time_from',
                        'a.date_time_to',
                        'a.total_hours',
                        'a.remarks',
                        'a.payroll',
                        'a.service_credits',
                        'd.name as overtime_type_name',
                        'a.employee_id'
                    )
                    ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => true, 'a.disapproved_2' => false])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
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
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
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
                            ->where('b.branch_approver_id_1', $emp_id)
                            ->orWhere('b.approver_id_1', $emp_id)
                            ->orWhere('b.division_approver_id_1', $emp_id)
                            ->orWhere('b.section_approver_id_1', $emp_id);
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
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
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
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
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
                            ->where('b.branch_approver_id_1', $emp_id)
                            ->orWhere('b.approver_id_1', $emp_id)
                            ->orWhere('b.division_approver_id_1', $emp_id)
                            ->orWhere('b.section_approver_id_1', $emp_id);
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
                        // Original decrypting employee name kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("b.first_name as first_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("b.middle_name as middle_name"),
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        DB::raw("b.last_name as last_name"),
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
                            ->where('b.approver_id_2', $emp_id);
                    })
                    ->where('a.employee_id', '!=', $emp_id)
                    ->where(db::raw("isnull(is_cancel,0)"), true)
                    ->union($CancelEmployeeOT_1)
                    ->orderby('date', 'desc')
                    ->get();
            } elseif ($approver_3->isNotEmpty()) {
                $approver_id = 0;
                $supervisor_id = 0;

                $ForapprovalEmployeeOT = [];
                $ApprovedEmployeeOT = [];
                $DisapprovedEmployeeOT = [];
                $CancelEmployeeOT = [];
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
                    'd.name as overtime_type_name'
                )
                ->where(['a.approved' => false, 'a.disapproved' => false, 'a.employee_id' => $emp_id])
                ->where(db::raw("isnull(is_cancel,0)"), false)
                ->orderby('a.date', 'desc')
                ->get();

            $ApprovedOvertime = DB::table('overtime_applications as a')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->leftJoin('employees as d1', 'd1.id', '=', 'a.processed_by')
                ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
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
                    // Original decrypting approver names kept for reference:
                    // DB::raw("CASE WHEN ISNULL(d1.is_encrypted,0) = 0 THEN CONCAT(d1.first_name,' ',d1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](d1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d1.last_name,'$app_key')) END as approver_1"),
                    DB::raw("CONCAT(d1.first_name,' ',d1.last_name) as approver_1"),
                    // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as approver_2"),
                    DB::raw("CONCAT(e.first_name,' ',e.last_name) as approver_2"),
                    'a.processed_date',
                    'a.processed_date_2'
                )
                ->where(['a.approved' => true, 'a.disapproved' => false, 'a.employee_id' => $emp_id])
                ->orderby('a.date', 'desc')->get();

            $DisapprovedOvertime = DB::table('overtime_applications as a')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->leftJoin('employees as d1', 'd1.id', '=', 'a.processed_by')
                ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
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
                    // Original decrypting approver names kept for reference:
                    // DB::raw("CASE WHEN ISNULL(d1.is_encrypted,0) = 0 THEN CONCAT(d1.first_name,' ',d1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](d1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d1.last_name,'$app_key')) END as approver_1"),
                    DB::raw("CONCAT(d1.first_name,' ',d1.last_name) as approver_1"),
                    // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as approver_2"),
                    DB::raw("CONCAT(e.first_name,' ',e.last_name) as approver_2"),
                    'a.processed_date',
                    'a.processed_date_2'
                )
                ->where('a.employee_id', $emp_id)
                ->where('a.disapproved', true)
                ->orWhere('a.disapproved_2', true)
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
                    'a.payroll',
                    'a.service_credits',
                    'd.name as overtime_type_name',
                    'a.canceled_date',
                    'a.canceled_remarks',
                    // Original decrypting cancelled_by name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN CONCAT(c.first_name,' ',c.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) END as cancelled_by"),
                    DB::raw("CONCAT(c.first_name,' ',c.last_name) as cancelled_by"),
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

            // Check if With Approver.
            $with_approvers = DB::table('approver_details')->where('employee_id', $emp_id)->get();

            if (count($with_approvers) > 0) {
                $allowed = 1;
            } else {
                $allowed = 0;
            }

            $ot_attachments = DB::table('overtime_attachments')->get();

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
                return response()->json(['error' => 'Failed to Save. Please check required fields. ' . Arr::first(Arr::flatten($validate->messages()->get('*')))], 500);
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
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN first_name ELSE dbo.ufn_DecryptString(first_name,'$app_key') END as first_name"),
                    DB::raw("first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN last_name ELSE dbo.ufn_DecryptString(last_name,'$app_key') END as last_name"),
                    DB::raw("last_name as last_name"),
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

                if ($rest_day) {
                    $absent = $absent;
                } else {
                    $absent = $time_data[0]->absent;
                }
            } else {
                $late = 0;
                $undertime = 0;
                if ($rest_day) {
                    $absent = $absent;
                } else {
                    $absent = 0;
                }
            }

            if ($data['total_hours'] < $overtime_types[0]->min_ot) {
                return $this->errorResponse('Total overtime hours did not reached required minimum hours for this application.');
            } elseif ($data['total_hours'] > $overtime_types[0]->max_ot) {
                return $this->errorResponse('Total overtime hours exceeds allowable maximum hours(' . $overtime_types[0]->max_ot . '). Please update to continue application');
            } elseif ($absent > 0) {
                return $this->errorResponse('Overtime application for this date is not eligible. Please update to continue application');
            } elseif ($late > 0) {
                return $this->errorResponse('Overtime application for this date is not eligible due to tardiness. Please update to continue application');
            } elseif ($undertime > 0) {
                return $this->errorResponse('Overtime application for this date is not eligible due to tardiness. Please update to continue application');
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
                        $file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'overtime_attachments\\' . 'OT' . $data['employee_id'] . '_' . $file_name;
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
            if (!Auth::check()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
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
                $nd_to = $nd_to->addDay(1);
                $nd_setup_hours = ($nd_to->diffInMinutes($nd_from, true) / 60);
                $with_nd = true;

                if ($ot_end >= $nd_end) {

                    $from = Carbon::parse($nd_start);
                    $to = Carbon::parse($ot_end);
                    $to = $to->addDay(1);

                    $nd_hours = ($to->diffInMinutes($from, true) / 60);

                    if ($nd_hours > $nd_setup_hours) {
                        $from = Carbon::parse($nd_start);
                        $to = Carbon::parse($nd_end);
                        $to = $to->addDay(1);

                        $nd_hours = ($to->diffInMinutes($from, true) / 60);
                    }
                } elseif ($ot_end < $nd_end) {
                    $from = Carbon::parse($nd_start);
                    $to = Carbon::parse($nd_end);
                    $to = $to->addDay(1);
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

            // Check if Approver
            $approver_1 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_1 as supervisor_id'
                )
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
                    'approved' =>  true,
                    'disapproved' =>  false,
                    'ot_amount' => $ot_amount,
                    'nd_amount' => $ot_nd_amount,
                    'processed_date' => $process_date,
                    'processed_by' => $approver_emp_id,
                    'approved_remarks' => $remarks,
                    'approved_2' =>  true,
                    'disapproved_2' =>  false,
                    'processed_date_2' => $process_date,
                    'processed_by_2' => $approver_emp_id,
                    'approved_remarks' => $remarks
                );
            } elseif ($approver_3->isNotEmpty()) {
                $ot_data = array(
                    'approved_3' =>  true,
                    'disapproved_3' =>  false,
                    'processed_date_3' => $process_date,
                    'processed_by_3' => $approver_emp_id,
                    'approved_remarks' => $remarks
                );
            }

            // Dev fallback: when no approver match, allow auto-approval in DEV to unblock testing
            if (!isset($ot_data)) {
                if (env('DEV_AUTH_ENABLED', false)) {
                    $ot_data = array(
                        'approved' =>  true,
                        'disapproved' => false,
                        'processed_date' => $process_date,
                        'processed_by' => $approver_emp_id,
                        'approved_remarks' => $remarks
                    );
                } else {
                    return response()->json(['success' => false, 'message' => 'Approver privileges required'], 403);
                }
            }

            DB::table('overtime_applications')->where('id', $id)->update($ot_data);

            //Save audit trail
            if (Auth::check()) {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Overtime Application',
                    'activity' => 'Approved Overtime',
                    'description' => 'Approved employee overtime application.',
                );

                Audit::create($data_audit);
            }

            return $this->successResponse(['id' => $id], 'You have successfully approved overtime application!');
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

        // Check if Approver
        $approver_1 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.approver_id_1 as supervisor_id'
            )
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
                'ot_amount' => 0,
                'nd_amount' => 0,
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


        $ot_hours = isset($overtime_data[0]) ? $overtime_data[0]->total_hours : 0;

        if (isset($schedule_data[0]) && $schedule_data[0]->work_hours > 0) {    
            $work_hours = $schedule_data[0]->work_hours;
        } else {
            $work_hours = isset($emp_data2[0]) ? $emp_data2[0]->work_hours : 8;
        }

        // Save Service Credits
        if (isset($overtime_data[0]) && $overtime_data[0]->service_credits == true) {

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

        $canceled_by_emp_id = ($emp_id_data && count($emp_id_data) > 0) ? $emp_id_data[0]->id : 0;

        $ot_data = array(
            'approved' =>  false,
            'disapproved' =>  false,
            'ot_amount' => 0,
            'nd_amount' => 0,
            'is_cancel' => true,
            'canceled_by' => $canceled_by_emp_id,
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
            DB::table('overtime_applications')->where('id', $id)->delete();

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Overtime Application',
            'menu'    => 'Overtime Application',
            'activity' => 'Delete',
            'description' => 'Deleted overtime application.',
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
                ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
                ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                // approver_details.employee_id = OT applicant; approver_details.approver_id -> approver_headers.id
                // approver_headers.type_id=3 (Overtime); approver_headers.approver_id_1/2/3 -> employees.id
                ->leftJoin(DB::raw('(SELECT employee_id, approver_id FROM (
                    SELECT ad.employee_id, ad.approver_id,
                        ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn
                    FROM approver_details ad
                    INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                ) x WHERE rn = 1) ad'), 'ad.employee_id', '=', 'b.id')
                ->leftJoin('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
                // Join with employees table to get approver names from approver_headers
                ->leftJoin('employees as app1', 'app1.id', '=', 'ah.approver_id_1')
                ->leftJoin('positions as app1_pos', 'app1_pos.id', '=', 'app1.position_id')
                ->leftJoin('employees as app2', 'app2.id', '=', 'ah.approver_id_2')
                ->leftJoin('employees as app3', 'app3.id', '=', 'ah.approver_id_3')
                ->leftJoin('employees as app4', 'app4.id', '=', 'ah.approver_id_4')
                // Join for branch_approver_id_1
                ->leftJoin('employees as branch_app', 'branch_app.id', '=', 'ah.branch_approver_id_1')
                ->leftJoin('positions as branch_app_pos', 'branch_app_pos.id', '=', 'branch_app.position_id')
                ->select(
                    'a.id',
                    'a.overtime_type_id',
                    'b.photo',
                    'b.employee_no',
                    'b.position_id',
                    'b.department_id',
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                    DB::raw("b.middle_name as middle_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
                    'pos.name as position',
                    'dept.name as department',
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
                    // Designated approvers from approver_headers hierarchy
                    // Original decrypting approver CASE blocks replaced (see git history).
                    DB::raw("CASE WHEN ISNULL(app1.middle_name,'') = '' THEN CONCAT(app1.first_name,' ',app1.last_name) ELSE CONCAT(app1.first_name,' ',UPPER(SUBSTRING(app1.middle_name,1,1)),'. ',app1.last_name) END as approver_1"),
                    DB::raw("CASE WHEN ISNULL(app2.middle_name,'') = '' THEN CONCAT(app2.first_name,' ',app2.last_name) ELSE CONCAT(app2.first_name,' ',UPPER(SUBSTRING(app2.middle_name,1,1)),'. ',app2.last_name) END as approver_2"),
                    DB::raw("CASE WHEN ISNULL(app3.middle_name,'') = '' THEN CONCAT(app3.first_name,' ',app3.last_name) ELSE CONCAT(app3.first_name,' ',UPPER(SUBSTRING(app3.middle_name,1,1)),'. ',app3.last_name) END as approver_3"),
                    DB::raw("CASE WHEN ISNULL(app4.middle_name,'') = '' THEN CONCAT(app4.first_name,' ',app4.last_name) ELSE CONCAT(app4.first_name,' ',UPPER(SUBSTRING(app4.middle_name,1,1)),'. ',app4.last_name) END as approver_4"),
                    // Position of approver_id_1
                    'app1_pos.name as approver_1_position',
                    // Branch approver name and position
                    DB::raw("CASE WHEN ISNULL(branch_app.middle_name,'') = '' THEN UPPER(CONCAT(branch_app.first_name,' ',branch_app.last_name)) ELSE UPPER(CONCAT(branch_app.first_name,' ',UPPER(SUBSTRING(branch_app.middle_name,1,1)),'. ',branch_app.last_name)) END as branch_approver_name"),
                    'branch_app_pos.name as branch_approver_position',
                    // DEBUG: Add intermediate IDs to trace the join chain
                    'ad.approver_id as debug_approver_id',
                    'ah.approver_id_1 as debug_approver_id_1',
                    'ah.branch_approver_id_1 as debug_branch_approver_id_1',
                    'app1.id as debug_app1_id',
                    'branch_app.id as debug_branch_app_id'
                )
                // Pending / For Approval: not fully approved by configured approvers (dynamic 1..3),
                // not disapproved, not cancelled; OT date still within approval window (>= today)
                ->whereRaw("NOT (
                    (ah.approver_id_1 IS NULL OR ISNULL(a.approved, 0) = 1)
                    AND (ah.approver_id_2 IS NULL OR ISNULL(a.approved_2, 0) = 1)
                    AND (ah.approver_id_3 IS NULL OR ISNULL(a.approved_3, 0) = 1)
                )")
                ->where('a.disapproved', false)
                ->where('a.disapproved_2', false)
                ->where(function ($q) {
                    $q->where('a.disapproved_3', false)->orWhereNull('a.disapproved_3');
                })
                ->where(function ($q) {
                    $q->where('a.is_cancel', false)->orWhereNull('a.is_cancel');
                })
                // Cutoff based on overtime_application.date: only show applications still available for approving (date >= today)
                ->whereDate('a.date', '>=', Carbon::today()->toDateString())
                ->get();

            $ApprovedEmployeeOT = DB::table('overtime_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
                ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->leftJoin('employees as d1', 'd1.id', '=', 'a.processed_by')
                ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
                // approver_details.employee_id = OT applicant; approver_details.approver_id -> approver_headers.id
                // approver_headers.type_id=3 (Overtime); approver_headers.approver_id_1/2/3 -> employees.id
                ->leftJoin(DB::raw('(SELECT employee_id, approver_id FROM (
                    SELECT ad.employee_id, ad.approver_id,
                        ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn
                    FROM approver_details ad
                    INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                ) x WHERE rn = 1) ad'), 'ad.employee_id', '=', 'b.id')
                ->leftJoin('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
                // Join with employees table to get approver names from approver_headers
                ->leftJoin('employees as app1', 'app1.id', '=', 'ah.approver_id_1')
                ->leftJoin('employees as app2', 'app2.id', '=', 'ah.approver_id_2')
                ->leftJoin('employees as app3', 'app3.id', '=', 'ah.approver_id_3')
                ->leftJoin('employees as app4', 'app4.id', '=', 'ah.approver_id_4')
                ->select(
                    'a.id',
                    'a.overtime_type_id',
                    'b.photo',
                    'b.employee_no',
                    'b.position_id',
                    'b.department_id',
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                    DB::raw("b.middle_name as middle_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
                    'pos.name as position',
                    'dept.name as department',
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
                    // Designated approvers from approver_headers hierarchy
                    DB::raw("CASE WHEN ISNULL(app1.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(app1.middle_name,'') = '' THEN
                                        CONCAT(app1.first_name,' ',app1.last_name)
                                    ELSE
                                        CONCAT(app1.first_name,' ',substring(app1.middle_name,1,1),'. ',app1.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(app1.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](app1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app1.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](app1.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app1.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app1.last_name,'$app_key'))
                                    END
                                END as approver_1"),
                    DB::raw("CASE WHEN ISNULL(app2.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(app2.middle_name,'') = '' THEN
                                        CONCAT(app2.first_name,' ',app2.last_name)
                                    ELSE
                                        CONCAT(app2.first_name,' ',substring(app2.middle_name,1,1),'. ',app2.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(app2.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](app2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app2.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](app2.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app2.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app2.last_name,'$app_key'))
                                    END
                                END as approver_2"),
                    DB::raw("CASE WHEN ISNULL(app3.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(app3.middle_name,'') = '' THEN
                                        CONCAT(app3.first_name,' ',app3.last_name)
                                    ELSE
                                        CONCAT(app3.first_name,' ',substring(app3.middle_name,1,1),'. ',app3.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(app3.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](app3.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app3.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](app3.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app3.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app3.last_name,'$app_key'))
                                    END
                                END as approver_3"),
                    DB::raw("CASE WHEN ISNULL(app4.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(app4.middle_name,'') = '' THEN
                                        CONCAT(app4.first_name,' ',app4.last_name)
                                    ELSE
                                        CONCAT(app4.first_name,' ',substring(app4.middle_name,1,1),'. ',app4.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(app4.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](app4.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app4.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](app4.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app4.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app4.last_name,'$app_key'))
                                    END
                                END as approver_4"),
                    // Keep processed_by names for tracking who actually processed
                    DB::raw("CASE WHEN ISNULL(d1.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(d1.middle_name,'') = '' THEN
                                        CONCAT(d1.first_name,' ',d1.last_name)
                                    ELSE
                                        CONCAT(d1.first_name,' ',substring(d1.middle_name,1,1),'. ',d1.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(d1.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](d1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d1.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](d1.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](d1.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](d1.last_name,'$app_key'))
                                    END
                                END as processed_by_name_1"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                                        CONCAT(e.first_name,' ',e.last_name)
                                    ELSE
                                        CONCAT(e.first_name,' ',substring(e.middle_name,1,1),'. ',e.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](e.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                                    END
                                END as processed_by_name_2"),
                    // Status: Approved when all configured approvers (1..3) approved
                    DB::raw("CASE WHEN
                                (ah.approver_id_1 IS NULL OR ISNULL(a.approved, 0) = 1)
                                AND (ah.approver_id_2 IS NULL OR ISNULL(a.approved_2, 0) = 1)
                                AND (ah.approver_id_3 IS NULL OR ISNULL(a.approved_3, 0) = 1)
                                AND (a.disapproved_3 = 0 OR a.disapproved_3 IS NULL)
                                AND (a.is_cancel = 0 OR a.is_cancel IS NULL)
                               THEN 'Approved'
                               WHEN a.disapproved = 1 OR a.disapproved_2 = 1 OR a.disapproved_3 = 1 THEN 'Disapproved'
                               WHEN a.is_cancel = 1 THEN 'Cancelled'
                               ELSE 'Pending' END as status")
                )
                // Approved: all configured approvers (1..3) approved
                ->whereRaw("
                    (ah.approver_id_1 IS NULL OR ISNULL(a.approved, 0) = 1)
                    AND (ah.approver_id_2 IS NULL OR ISNULL(a.approved_2, 0) = 1)
                    AND (ah.approver_id_3 IS NULL OR ISNULL(a.approved_3, 0) = 1)
                ")
                ->where('a.disapproved', false)
                ->where('a.disapproved_2', false)
                ->where(function ($q) {
                    $q->where('a.disapproved_3', false)->orWhereNull('a.disapproved_3');
                })
                ->where(function ($q) {
                    $q->where('a.is_cancel', false)->orWhereNull('a.is_cancel');
                })
                ->get();

            $DisapprovedEmployeeOT = DB::table('overtime_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
                ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->leftJoin('employees as d1', 'd1.id', '=', 'a.processed_by')
                ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
                // approver_details.employee_id = OT applicant; approver_details.approver_id -> approver_headers.id
                // approver_headers.type_id=3 (Overtime); approver_headers.approver_id_1/2/3 -> employees.id
                ->leftJoin(DB::raw('(SELECT employee_id, approver_id FROM (
                    SELECT ad.employee_id, ad.approver_id,
                        ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn
                    FROM approver_details ad
                    INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                ) x WHERE rn = 1) ad'), 'ad.employee_id', '=', 'b.id')
                ->leftJoin('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
                // Join with employees table to get approver names from approver_headers
                ->leftJoin('employees as app1', 'app1.id', '=', 'ah.approver_id_1')
                ->leftJoin('employees as app2', 'app2.id', '=', 'ah.approver_id_2')
                ->leftJoin('employees as app3', 'app3.id', '=', 'ah.approver_id_3')
                ->leftJoin('employees as app4', 'app4.id', '=', 'ah.approver_id_4')
                ->select(
                    'a.id',
                    'a.overtime_type_id',
                    'b.photo',
                    'b.employee_no',
                    'b.position_id',
                    'b.department_id',
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                    DB::raw("b.middle_name as middle_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
                    'pos.name as position',
                    'dept.name as department',
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
                    // Designated approvers from approver_headers hierarchy
                    DB::raw("CASE WHEN ISNULL(app1.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(app1.middle_name,'') = '' THEN
                                        CONCAT(app1.first_name,' ',app1.last_name)
                                    ELSE
                                        CONCAT(app1.first_name,' ',substring(app1.middle_name,1,1),'. ',app1.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(app1.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](app1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app1.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](app1.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app1.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app1.last_name,'$app_key'))
                                    END
                                END as approver_1"),
                    DB::raw("CASE WHEN ISNULL(app2.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(app2.middle_name,'') = '' THEN
                                        CONCAT(app2.first_name,' ',app2.last_name)
                                    ELSE
                                        CONCAT(app2.first_name,' ',substring(app2.middle_name,1,1),'. ',app2.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(app2.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](app2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app2.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](app2.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app2.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app2.last_name,'$app_key'))
                                    END
                                END as approver_2"),
                    DB::raw("CASE WHEN ISNULL(app3.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(app3.middle_name,'') = '' THEN
                                        CONCAT(app3.first_name,' ',app3.last_name)
                                    ELSE
                                        CONCAT(app3.first_name,' ',substring(app3.middle_name,1,1),'. ',app3.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(app3.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](app3.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app3.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](app3.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app3.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app3.last_name,'$app_key'))
                                    END
                                END as approver_3"),
                    DB::raw("CASE WHEN ISNULL(app4.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(app4.middle_name,'') = '' THEN
                                        CONCAT(app4.first_name,' ',app4.last_name)
                                    ELSE
                                        CONCAT(app4.first_name,' ',substring(app4.middle_name,1,1),'. ',app4.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(app4.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](app4.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app4.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](app4.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app4.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app4.last_name,'$app_key'))
                                    END
                                END as approver_4"),
                    // Keep processed_by names for tracking who actually processed
                    DB::raw("CASE WHEN ISNULL(d1.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(d1.middle_name,'') = '' THEN
                                        CONCAT(d1.first_name,' ',d1.last_name)
                                    ELSE
                                        CONCAT(d1.first_name,' ',substring(d1.middle_name,1,1),'. ',d1.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(d1.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](d1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d1.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](d1.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](d1.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](d1.last_name,'$app_key'))
                                    END
                                END as processed_by_name_1"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                                        CONCAT(e.first_name,' ',e.last_name)
                                    ELSE
                                        CONCAT(e.first_name,' ',substring(e.middle_name,1,1),'. ',e.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](e.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                                    END
                                END as processed_by_name_2")
                )
                ->where('is_cancel', false)
                ->where(function ($q) {
                    $q->where('a.disapproved', true)
                      ->orWhere('a.disapproved_2', true)
                      ->orWhere('a.disapproved_3', true);
                })
                ->get();

            // Expired: coverage date passed AND not fully approved (applies to any level 1, 2, or 3)
            $expiredCutoffDate = Carbon::today()->toDateString();
            $ExpiredEmployeeOT = DB::table('overtime_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
                ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->leftJoin('employees as d1', 'd1.id', '=', 'a.processed_by')
                ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
                ->leftJoin(DB::raw('(SELECT employee_id, approver_id FROM (
                    SELECT ad.employee_id, ad.approver_id,
                        ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn
                    FROM approver_details ad
                    INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                ) x WHERE rn = 1) ad'), 'ad.employee_id', '=', 'b.id')
                ->leftJoin('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
                ->leftJoin('employees as app1', 'app1.id', '=', 'ah.approver_id_1')
                ->leftJoin('employees as app2', 'app2.id', '=', 'ah.approver_id_2')
                ->leftJoin('employees as app3', 'app3.id', '=', 'ah.approver_id_3')
                ->leftJoin('employees as app4', 'app4.id', '=', 'ah.approver_id_4')
                ->select(
                    'a.id',
                    'a.overtime_type_id',
                    'b.photo',
                    'b.employee_no',
                    'b.position_id',
                    'b.department_id',
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                    DB::raw("b.middle_name as middle_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
                    'pos.name as position',
                    'dept.name as department',
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
                    'a.approved',
                    'a.approved_2',
                    'a.approved_3',
                    'a.processed_date',
                    'a.processed_date_2',
                    // Original decrypting approver names kept for reference:
                    // DB::raw("CASE WHEN ISNULL(app1.is_encrypted,0) = 0 THEN CONCAT(app1.first_name,' ',app1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](app1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app1.last_name,'$app_key')) END as approver_1"),
                    DB::raw("CASE WHEN ISNULL(app1.middle_name,'') = '' THEN CONCAT(app1.first_name,' ',app1.last_name) ELSE CONCAT(app1.first_name,' ',UPPER(SUBSTRING(app1.middle_name,1,1)),'. ',app1.last_name) END as approver_1"),
                    // DB::raw("CASE WHEN ISNULL(app2.is_encrypted,0) = 0 THEN CONCAT(app2.first_name,' ',app2.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](app2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app2.last_name,'$app_key')) END as approver_2"),
                    DB::raw("CASE WHEN ISNULL(app2.middle_name,'') = '' THEN CONCAT(app2.first_name,' ',app2.last_name) ELSE CONCAT(app2.first_name,' ',UPPER(SUBSTRING(app2.middle_name,1,1)),'. ',app2.last_name) END as approver_2"),
                    // DB::raw("CASE WHEN ISNULL(app3.is_encrypted,0) = 0 THEN CONCAT(app3.first_name,' ',app3.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](app3.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app3.last_name,'$app_key')) END as approver_3"),
                    DB::raw("CASE WHEN ISNULL(app3.middle_name,'') = '' THEN CONCAT(app3.first_name,' ',app3.last_name) ELSE CONCAT(app3.first_name,' ',UPPER(SUBSTRING(app3.middle_name,1,1)),'. ',app3.last_name) END as approver_3"),
                    // DB::raw("CASE WHEN ISNULL(app4.is_encrypted,0) = 0 THEN CONCAT(app4.first_name,' ',app4.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](app4.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app4.last_name,'$app_key')) END as approver_4"),
                    DB::raw("CASE WHEN ISNULL(app4.middle_name,'') = '' THEN CONCAT(app4.first_name,' ',app4.last_name) ELSE CONCAT(app4.first_name,' ',UPPER(SUBSTRING(app4.middle_name,1,1)),'. ',app4.last_name) END as approver_4")
                )
                ->whereRaw("NOT (
                    (ah.approver_id_1 IS NULL OR ISNULL(a.approved, 0) = 1)
                    AND (ah.approver_id_2 IS NULL OR ISNULL(a.approved_2, 0) = 1)
                    AND (ah.approver_id_3 IS NULL OR ISNULL(a.approved_3, 0) = 1)
                )")
                ->where('a.disapproved', false)
                ->where('a.disapproved_2', false)
                ->where(function ($q) {
                    $q->where('a.disapproved_3', false)->orWhereNull('a.disapproved_3');
                })
                ->where(function ($q) {
                    $q->where('a.is_cancel', false)->orWhereNull('a.is_cancel');
                })
                // Cutoff based on overtime_application.date: only applications past cutoff (date < today) go to Expired
                ->whereDate('a.date', '<', $expiredCutoffDate)
                ->get();

            $CancelledEmployeeOT = DB::table('overtime_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
                ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
                ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
                ->leftJoin('employees as b1', 'a.canceled_by', '=', 'b1.id')
                ->select(
                    'a.id',
                    'a.overtime_type_id',
                    'b.photo',
                    'b.employee_no',
                    'b.position_id',
                    'b.department_id',
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                    DB::raw("b.middle_name as middle_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
                    'pos.name as position',
                    'dept.name as department',
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
                    // Original decrypting cancelled_by name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b1.is_encrypted,0) = 0 THEN CONCAT(b1.first_name,' ',b1.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](b1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b1.last_name,'$app_key')) END as cancelled_by"),
                    DB::raw("CONCAT(b1.first_name,' ',b1.last_name) as cancelled_by"),
                    'a.canceled_date',
                    'a.canceled_remarks'
                )
                ->where(['is_cancel' => true])
                ->get();

            // Get Executive Director (position_id = 37)
            $executiveDirector = DB::table('employees as e')
                ->leftJoin('positions as p', 'e.position_id', '=', 'p.id')
                ->select(
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                                    CONCAT(e.first_name,' ',e.last_name)
                                ELSE
                                    CONCAT(e.first_name,' ',substring(e.middle_name,1,1),'. ',e.last_name)
                                END
                            ELSE
                                CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](e.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                                END
                            END as name"),
                    'p.name as position'
                )
                ->where([
                    'e.position_id' => 37,
                    'e.active' => true,
                    'e.is_employee' => true
                ])
                ->first();

            return $this->successResponse([
                'for_approval' => $ForapprovalEmployeeOT,
                'approved' => $ApprovedEmployeeOT,
                'disapproved' => $DisapprovedEmployeeOT,
                'cancelled' => $CancelledEmployeeOT,
                'expired' => $ExpiredEmployeeOT,
                'executive_director' => $executiveDirector ? [
                    'name' => $executiveDirector->name ?? '',
                    'position' => $executiveDirector->position ?? ''
                ] : null
            ], 'Overtime applications monitoring data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve overtime applications monitoring data: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $overtime_application = DB::table('overtime_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('positions as d', 'b.position_id', '=', 'd.id')
                ->leftJoin('overtime_types as e', 'a.overtime_type_id', '=', 'e.id')
                // approver_details.employee_id = OT applicant; approver_details.approver_id -> approver_headers.id
                // approver_headers.type_id=3 (Overtime); approver_headers.approver_id_1/2/3 -> employees.id
                ->leftJoin(DB::raw('(SELECT employee_id, approver_id FROM (
                    SELECT ad.employee_id, ad.approver_id,
                        ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn
                    FROM approver_details ad
                    INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
                ) x WHERE rn = 1) ad'), 'ad.employee_id', '=', 'b.id')
                ->leftJoin('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
                // Join with employees table to get approver names from approver_headers
                ->leftJoin('employees as app1', 'app1.id', '=', 'ah.approver_id_1')
                ->leftJoin('employees as app2', 'app2.id', '=', 'ah.approver_id_2')
                ->leftJoin('employees as app3', 'app3.id', '=', 'ah.approver_id_3')
                ->leftJoin('employees as app4', 'app4.id', '=', 'ah.approver_id_4')
                ->select(
                    'a.id',
                    'a.employee_id',
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                    DB::raw("b.middle_name as middle_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.total_hours',
                    'a.remarks',
                    'a.payroll',
                    'a.service_credits',
                    'a.approved',
                    'a.approved_2',
                    'a.approved_3',
                    'a.disapproved',
                    'a.disapproved_2',
                    'a.disapproved_3',
                    'a.processed_by',
                    'a.processed_date',
                    'a.processed_by_2',
                    'a.processed_date_2',
                    'a.processed_by_3',
                    'a.processed_date_3',
                    'a.approved_remarks',
                    'a.disapprove_remarks',
                    'a.ot_amount',
                    'a.nd_amount',
                    'a.is_cancel',
                    'a.canceled_by',
                    'a.canceled_date',
                    'a.canceled_remarks',
                    'a.attachment_name',
                    'a.path',
                    'a.extension',
                    'e.name as overtime_type_name',
                    'c.name as department',
                    'd.name as position',
                    'b.photo',
                    'b.employee_no',
                    // Designated approvers from approver_headers hierarchy
                    DB::raw("CASE WHEN ISNULL(app1.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(app1.middle_name,'') = '' THEN
                                        CONCAT(app1.first_name,' ',app1.last_name)
                                    ELSE
                                        CONCAT(app1.first_name,' ',substring(app1.middle_name,1,1),'. ',app1.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(app1.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](app1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app1.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](app1.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app1.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app1.last_name,'$app_key'))
                                    END
                                END as approver_1"),
                    DB::raw("CASE WHEN ISNULL(app2.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(app2.middle_name,'') = '' THEN
                                        CONCAT(app2.first_name,' ',app2.last_name)
                                    ELSE
                                        CONCAT(app2.first_name,' ',substring(app2.middle_name,1,1),'. ',app2.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(app2.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](app2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app2.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](app2.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app2.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app2.last_name,'$app_key'))
                                    END
                                END as approver_2"),
                    DB::raw("CASE WHEN ISNULL(app3.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(app3.middle_name,'') = '' THEN
                                        CONCAT(app3.first_name,' ',app3.last_name)
                                    ELSE
                                        CONCAT(app3.first_name,' ',substring(app3.middle_name,1,1),'. ',app3.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(app3.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](app3.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app3.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](app3.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app3.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app3.last_name,'$app_key'))
                                    END
                                END as approver_3"),
                    DB::raw("CASE WHEN ISNULL(app4.is_encrypted,0) = 0 THEN
                                    CASE WHEN ISNULL(app4.middle_name,'') = '' THEN
                                        CONCAT(app4.first_name,' ',app4.last_name)
                                    ELSE
                                        CONCAT(app4.first_name,' ',substring(app4.middle_name,1,1),'. ',app4.last_name)
                                    END
                                ELSE
                                    CASE WHEN ISNULL(app4.middle_name,'') = '' THEN
                                        RTRIM([dbo].[ufn_DecryptString](app4.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app4.last_name,'$app_key'))
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](app4.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app4.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app4.last_name,'$app_key'))
                                    END
                                END as approver_4")
                )
                ->where('a.id', $id)
                ->first();

            if (!$overtime_application) {
                return $this->notFoundResponse('Overtime application not found');
            }

            // Ensure approval flags are integers so the UI can reliably show Approved/Pending/Disapproved
            $data = (array) $overtime_application;
            $data['approved'] = (int) ($data['approved'] ?? 0);
            $data['approved_2'] = (int) ($data['approved_2'] ?? 0);
            $data['approved_3'] = (int) ($data['approved_3'] ?? 0);
            $data['disapproved'] = (int) ($data['disapproved'] ?? 0);
            $data['disapproved_2'] = (int) ($data['disapproved_2'] ?? 0);
            $data['disapproved_3'] = (int) ($data['disapproved_3'] ?? 0);

            return $this->successResponse($data, 'Overtime application retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve overtime application: ' . $e->getMessage());
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
                    $file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'OT_cancelled_documents\\' . 'DOCS' . $id . '_' . $file_name;
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
            $table->addCell(2800, $headerBg)->addText('PERIOD COVERED', $hdrStyle, $hdrAlign);
            $table->addCell(2500, $headerBg)->addText('PERSON RESPONSIBLE', $hdrStyle, $hdrAlign);
            $table->addCell(2500, $headerBg)->addText('WITH / WITHOUT OT PAY', $hdrStyle, $hdrAlign);

            // Body rows
            $items = $data['items'] ?? [];
            foreach ($items as $item) {
                $table->addRow();
                $table->addCell(3000)->addText($item['activity'] ?? '', ['size' => 9]);
                $table->addCell(1500)->addText((string)($item['quantity'] ?? ''), ['size' => 9], $hdrAlign);
                $table->addCell(1500)->addText((string)($item['mh_needed'] ?? ''), ['size' => 9], $hdrAlign);
                $table->addCell(2800)->addText($item['period'] ?? '', ['size' => 9]);
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
                $table->addCell(2800)->addText('', ['size' => 9]);
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
                $leftCell->addText($data['dept_head_name'] ?? '', ['bold' => true, 'size' => 10, 'underline' => 'single'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                if (!empty($data['dept_head_position'])) {
                    $leftCell->addText($data['dept_head_position'] ?? '', ['size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                }
            }
            // Label directly under the line
            $leftCell->addText('Dept Head', ['size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

            // Executive Director (same source as PDF: position_id = 37 employee)
            $rightCell = $sigTable->addCell(5000);
            if (empty($data['has_executive_director_data'])) {
                $rightCell->addText('______________________________', [], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }
            $approverNameDocx = trim((string) ($data['approver_name'] ?? ''));
            if ($approverNameDocx !== '') {
                $rightCell->addText($approverNameDocx, ['bold' => true, 'size' => 10, 'underline' => 'single'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }
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
            $sheet->getColumnDimension('D')->setWidth(42);
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

            // Signature section – centered in their halves (rows aligned left/right)
            $sigRow = $row;

            // Dept Head (left: columns B–C)
            $sheet->mergeCells("B{$sigRow}:C{$sigRow}");
            $sheet->setCellValue(
                "B{$sigRow}",
                empty($data['has_dept_head_data']) ? '______________________________' : ''
            );
            $sheet->getStyle("B{$sigRow}:C{$sigRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Executive Director (right: columns E–F) — same top row as dept head rule line
            $sheet->mergeCells("E{$sigRow}:F{$sigRow}");
            $sheet->setCellValue(
                "E{$sigRow}",
                empty($data['has_executive_director_data']) ? '______________________________' : ''
            );
            $sheet->getStyle("E{$sigRow}:F{$sigRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $nameRow = $sigRow + 1;

            $sheet->mergeCells("B{$nameRow}:C{$nameRow}");
            $sheet->setCellValue("B{$nameRow}", $data['dept_head_name'] ?? '');
            $sheet->getStyle("B{$nameRow}:C{$nameRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            if (!empty(trim((string) ($data['dept_head_name'] ?? '')))) {
                $sheet->getStyle("B{$nameRow}:C{$nameRow}")->getFont()->setBold(true)->setUnderline(\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE);
            }

            $sheet->mergeCells("E{$nameRow}:F{$nameRow}");
            $sheet->setCellValue("E{$nameRow}", $data['approver_name'] ?? '');
            $sheet->getStyle("E{$nameRow}:F{$nameRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            if (!empty(trim((string) ($data['approver_name'] ?? '')))) {
                $sheet->getStyle("E{$nameRow}:F{$nameRow}")->getFont()->setBold(true)->setUnderline(\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE);
            }

            $posRow = $nameRow + 1;

            $sheet->mergeCells("B{$posRow}:C{$posRow}");
            $sheet->setCellValue("B{$posRow}", 'Dept Head');
            $sheet->getStyle("B{$posRow}:C{$posRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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
     * Build normalized data for Overtime Authorization Request
     * to be shared by both DOCX and Excel generators.
     *
     * @param array $ids
     * @return array [data array, records collection]
     */
    protected function buildOvertimeAuthorizationData(array $ids)
    {
        $app_key = env('APP_KEY');

        // Fetch the selected overtime records with related employee, department and approver info
        $records = DB::table('overtime_applications as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
            ->leftJoin('overtime_types as d', 'a.overtime_type_id', '=', 'd.id')
            ->leftJoin(DB::raw('(SELECT employee_id, approver_id FROM (
                SELECT ad.employee_id, ad.approver_id,
                    ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn
                FROM approver_details ad
                INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 3
            ) x WHERE rn = 1) ad'), 'ad.employee_id', '=', 'b.id')
            ->leftJoin('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
            ->leftJoin('employees as app1', 'app1.id', '=', 'ah.approver_id_1')
            ->select(
                'a.id',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'a.total_hours',
                'a.remarks',
                'a.service_credits',
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                    DB::raw("b.middle_name as middle_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
                'dept.name as department',
                'pos.name as position',
                // Dept Head (approver 1)
                /*
                 * Original decrypting dept_head_name kept for reference:
                 * DB::raw("CASE WHEN ISNULL(app1.is_encrypted,0) = 0 THEN ... ELSE ... dbo.ufn_DecryptString(...) ... END as dept_head_name"),
                 */
                DB::raw("CASE WHEN ISNULL(app1.middle_name,'') = '' THEN CONCAT(app1.first_name,' ',app1.last_name) ELSE CONCAT(app1.first_name,' ',UPPER(SUBSTRING(app1.middle_name,1,1)),'. ',app1.last_name) END as dept_head_name")
            )
            ->whereIn('a.id', $ids)
            ->get();

        if ($records->isEmpty()) {
            return [null, $records];
        }

        // Base dates: use first OT record as reference
        $first = $records->first();
        $today = Carbon::now();
        $otDate = $first->date ? Carbon::parse($first->date) : $today;

        // Build items list (one line per overtime record)
        $items = [];
        foreach ($records as $row) {
            $employeeName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $remarks = $row->remarks ?: 'Overtime Work';
            $totalHours = (float)($row->total_hours ?? 0);
            $serviceCredits = $row->service_credits;
            $isServiceCredits = $serviceCredits === true
                || $serviceCredits === 1
                || $serviceCredits === '1'
                || $serviceCredits === 'true';

            // e.g. "May 13, 2026 04:00 PM - 06:00 PM" from OT date + time range
            $periodCovered = '';
            if ($row->date) {
                $fromStr = $row->date_time_from ?? null;
                $toStr = $row->date_time_to ?? null;
                if ($fromStr && $toStr) {
                    $datePart = Carbon::parse($row->date)->format('Y-m-d');
                    $start = Carbon::parse(trim($datePart . ' ' . $fromStr));
                    $end = Carbon::parse(trim($datePart . ' ' . $toStr));
                    if ($end->timestamp <= $start->timestamp) {
                        $end->addDay();
                    }
                    $periodCovered = $start->format('F j, Y h:i A') . ' - ';
                    $periodCovered .= $start->isSameDay($end)
                        ? $end->format('h:i A')
                        : $end->format('F j, Y h:i A');
                } else {
                    $periodCovered = Carbon::parse($row->date)->format('F j, Y');
                }
            }

            $items[] = [
                'activity'      => $remarks,
                'quantity'      => '1',
                'mh_needed'     => number_format($totalHours, 2) . ' hrs',
                'period'        => $periodCovered,
                'person'        => $employeeName,
                'ot_pay_status' => $isServiceCredits ? 'WITHOUT OT PAY (COC)' : 'WITH OT PAY',
            ];
        }

        // Signatories — match OT PDF preview: Dept Head from approver_id_1; ED from employee with position_id = 37 (not approver_headers.branch / approver_id_4)
        $deptHeadName = trim((string) ($first->dept_head_name ?? ''));
        $deptHeadPosition = $first->position ?? '';
        $hasDeptHead = $deptHeadName !== '';

        $execName = '';
        $execPosition = 'Executive Director';
        $hasExec = false;
        $globalEd = $this->getExecutiveDirectorEmployeeForOvertimeForms();
        if ($globalEd) {
            $edName = trim((string) ($globalEd->name ?? ''));
            if ($edName !== '' && strcasecmp($edName, $deptHeadName) !== 0) {
                $execName = $edName;
                $pos = trim((string) ($globalEd->position ?? ''));
                $execPosition = $pos !== '' ? $pos : 'Executive Director';
                $hasExec = true;
            }
        }

        $data = [
            'date_prepared'             => $today->format('F j, Y'),
            'month'                     => $otDate->format('F'),
            'year'                      => $otDate->format('Y'),
            'section_division'          => $first->department ?? '',
            'items'                     => $items,
            'dept_head_name'            => $deptHeadName,
            'dept_head_position'        => $deptHeadPosition,
            'approver_name'             => $execName,
            'approver_position'         => $execPosition,
            'has_dept_head_data'        => $hasDeptHead,
            'has_executive_director_data' => $hasExec,
        ];

        return [$data, $records];
    }

    /**
     * Executive Director for overtime authorization (same source as OT monitoring PDF: position_id = 37).
     */
    protected function getExecutiveDirectorEmployeeForOvertimeForms(): ?object
    {
        return DB::table('employees as e')
            ->leftJoin('positions as p', 'e.position_id', '=', 'p.id')
            ->select(
                DB::raw("CASE WHEN ISNULL(e.middle_name,'') = '' THEN CONCAT(e.first_name,' ',e.last_name) ELSE CONCAT(e.first_name,' ',UPPER(SUBSTRING(e.middle_name,1,1)),'. ',e.last_name) END as name"),
                'p.name as position'
            )
            ->where('e.position_id', 37)
            ->where('e.active', true)
            ->where('e.is_employee', true)
            ->first();
    }
}
