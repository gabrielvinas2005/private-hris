<?php

namespace App\Http\Controllers;

use PDF;
use Auth;
use App\Audit;
use App\User;
use Notification;
use App\Notifications\EmailOBApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\OfficialBusinessApplication;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class OfficialBusinessApplicationController extends Controller
{
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
            ->where('a.id', $id)
            ->get();

        if (count($emp_id_data) == 0) {
            $emp_id = 0;
            $supervisor_id = 0;
            $dummy_info =
                array(
                    'id' => 0,
                );
            $info = (object) $dummy_info;
            $info = collect([$info]);
        } else {
            $info = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->select(
                    'b.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name")
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

        $ForapprovalEmployeeOB = [];
        $ApprovedEmployeeOB = [];
        $DisapprovedEmployeeOB = [];
        $CancelledEmployeeOB = [];

        if ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
            $approver_id = $approver_1[0]->id;
            $supervisor_id = $approver_1[0]->supervisor_id;

            $ForapprovalEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.ob_type',
                    'a.client',
                    'a.purpose',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.is_cancel'
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
                ->distinct()
                ->get();

            $ApprovedEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.ob_type',
                    'a.date_time_to',
                    'a.client',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.purpose'
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
                ->distinct()
                ->get();

            $DisapprovedEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.ob_type',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.client',
                    'a.purpose',
                    DB::raw("a.disapproved_remark as remarks")
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
                ->distinct()
                ->get();

            $CancelledEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.ob_type',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.client',
                    'a.purpose',
                    'a.is_cancel',
                    'a.canceled_remarks as remarks'
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
                ->distinct()
                ->get();
        } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
            $approver_id = $approver_2[0]->id;
            $supervisor_id = $approver_2[0]->supervisor_id;

            $ForapprovalEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.ob_type',
                    'a.date_time_to',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.client',
                    'a.purpose',
                    'a.is_cancel'
                )
                ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => false, 'a.disapproved_2' => false])
                ->whereIn('b.id', function ($query) use ($emp_id) {
                    $query->select('a.employee_id')
                        ->from('approver_details as a')
                        ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                        ->where('b.approver_id_2', $emp_id);
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->where('is_cancel', false)
                ->orderby('a.date', 'desc')
                ->distinct()
                ->get();

            $ApprovedEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.ob_type',
                    'a.date_time_to',
                    'a.client',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.purpose'
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
                ->distinct()
                ->get();

            $DisapprovedEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.ob_type',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.date_time_to',
                    'a.client',
                    'a.purpose',
                    DB::raw("a.disapproved_2_remark as remarks")
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
                ->distinct()
                ->get();

            $CancelledEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.ob_type',
                    'a.client',
                    'a.purpose',
                    'a.is_cancel',
                    'a.canceled_remarks as remarks'
                )
                ->where(['a.approved' => false, 'a.disapproved' => false])
                ->whereIn('b.id', function ($query) use ($emp_id) {
                    $query->select('a.employee_id')
                        ->from('approver_details as a')
                        ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                        ->where('b.approver_id_2', $emp_id);
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->where('is_cancel', true)
                ->orderby('a.date', 'desc')
                ->distinct()
                ->get();
        } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
            $approver_id = $approver_2[0]->id;
            $supervisor_id = $approver_2[0]->supervisor_id;

            $ForapprovalEmployeeOB_1 = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.ob_type',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.client',
                    'a.purpose',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.is_cancel'
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

            $ApprovedEmployeeOB_1 = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.ob_type',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.client',
                    'a.purpose'
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

            $DisapprovedEmployeeOB_1 = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.ob_type',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.client',
                    'a.purpose',
                    DB::raw("a.disapproved_remark as remarks")
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

            $CancelledEmployeeOB_1 = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.client',
                    'a.purpose',
                    'a.ob_type',
                    'a.is_cancel',
                    'a.canceled_remarks as remarks'
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

            $ForapprovalEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.ob_type',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.date_time_to',
                    'a.client',
                    'a.purpose',
                    'a.is_cancel'
                )
                ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => false, 'a.disapproved_2' => false])
                ->whereIn('b.id', function ($query) use ($emp_id) {
                    $query->select('a.employee_id')
                        ->from('approver_details as a')
                        ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                        ->where('b.approver_id_2', $emp_id);
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->where('is_cancel', false)
                ->union($ForapprovalEmployeeOB_1)
                ->orderby('date', 'desc')
                ->get();

            $ApprovedEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.ob_type',
                    'a.date',
                    'a.date_time_from',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.date_time_to',
                    'a.client',
                    'a.purpose'
                )
                ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => true, 'a.disapproved_2' => false])
                ->whereIn('b.id', function ($query) use ($emp_id) {
                    $query->select('a.employee_id')
                        ->from('approver_details as a')
                        ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                        ->where('b.approver_id_2', $emp_id);
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->union($ApprovedEmployeeOB_1)
                ->orderby('date', 'desc')
                ->get();

            $DisapprovedEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.ob_type',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.client',
                    'a.purpose',
                    DB::raw("a.disapproved_2_remark as remarks")
                )
                ->where(['a.approved' => true, 'a.disapproved' => false, 'a.approved_2' => false, 'a.disapproved_2' => true])
                ->whereIn('b.id', function ($query) use ($emp_id) {
                    $query->select('a.employee_id')
                        ->from('approver_details as a')
                        ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                        ->where('b.approver_id_2', $emp_id);
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->union($DisapprovedEmployeeOB_1)
                ->orderby('date', 'desc')
                ->get();

            $CancelledEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.ob_type',
                    'a.date_time_to',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.client',
                    'a.purpose',
                    'a.is_cancel',
                    'a.canceled_remarks as remarks'
                )
                ->where(['a.approved' => false, 'a.disapproved' => false])
                ->whereIn('b.id', function ($query) use ($emp_id) {
                    $query->select('a.employee_id')
                        ->from('approver_details as a')
                        ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                        ->where('b.approver_id_2', $emp_id);
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->where('is_cancel', true)
                ->union($CancelledEmployeeOB_1)
                ->orderby('date', 'desc')
                ->get();
        } elseif ($approver_3->isNotEmpty()) {
            $approver_id = 0;
            $supervisor_id = 0;

            $ForapprovalEmployeeOB = [];
            $ApprovedEmployeeOB = [];
            $DisapprovedEmployeeOB = [];
            $CancelledEmployeeOB = [];
        } else {
            $approver_id = 0;
            $supervisor_id = 0;

            $ForapprovalEmployeeOB = [];
            $ApprovedEmployeeOB = [];
            $DisapprovedEmployeeOB = [];
            $CancelledEmployeeOB = [];
        }

        $PendingOB = DB::table('official_business_applications')->where(['approved' => false, 'disapproved' => false, 'employee_id' => $emp_id])
            ->where(db::raw("isnull(is_cancel,0)"), false)
            ->orderby('date', 'desc')
            ->get();

        $ApprovedOB = DB::table('official_business_applications as a')
            ->leftJoin('employees as d', 'd.id', '=', 'a.processed_by')
            ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
            ->select(
                'a.*',
                DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN
                                CONCAT(d.first_name,' ',d.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key')) 
                            END as approver_1"),
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CONCAT(e.first_name,' ',e.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) 
                            END as approver_2")
            )
            ->where([
                'a.approved' => true,
                'a.disapproved' => false,
                'a.disapproved_2' => false,
                'a.disapproved_3' => false,
                'a.employee_id' => $emp_id
            ])
            ->orderby('a.date', 'desc')
            ->get();

        $DisapprovedOB = DB::table('official_business_applications as a')
            ->leftJoin('employees as d', 'd.id', '=', 'a.processed_by')
            ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
            ->select(
                'a.*',
                DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN
                                CONCAT(d.first_name,' ',d.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key')) 
                            END as approver_1"),
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CONCAT(e.first_name,' ',e.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) 
                            END as approver_2")
            )
            ->where('a.employee_id', $emp_id)
            ->where('a.disapproved', true)
            ->orWhere('a.disapproved_2', true)
            ->orderby('date', 'desc')
            ->get();

        $CancelledOB = DB::table('official_business_applications as a')
            ->leftJoin('employees as b', 'a.canceled_by', '=', 'b.id')
            ->select(
                'a.*',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as cancelled_by")
            )
            ->where(['approved' => false, 'disapproved' => false, 'is_cancel' => true, 'employee_id' => $emp_id])
            ->orderby('date', 'desc')
            ->get();

        // OB Attachments
        $ob_attachments = DB::table('ob_attachments')->orderBy('ob_id', 'asc')->get();

        // Check if With Approver.
        $with_approvers = DB::table('approver_details')->where('employee_id', $emp_id)->get();

        if (count($with_approvers) > 0) {
            $allowed = 1;
        } else {
            $allowed = 0;
        }

        return $this->successResponse([
            'info' => $info,
            'PendingOB' => $PendingOB,
            'ApprovedOB' => $ApprovedOB,
            'DisapprovedOB' => $DisapprovedOB,
            'supervisor_id' => $supervisor_id,
            'ForapprovalEmployeeOB' => $ForapprovalEmployeeOB,
            'ApprovedEmployeeOB' => $ApprovedEmployeeOB,
            'DisapprovedEmployeeOB' => $DisapprovedEmployeeOB,
            'CancelledEmployeeOB' => $CancelledEmployeeOB,
            'CancelledOB' => $CancelledOB,
            'ob_attachments' => $ob_attachments,
            'emp_id' => $emp_id,
            'allowed' => $allowed
        ], 'Official business applications retrieved successfully');
    }

    public function store(Request $request)
    {
        $app_key = env("APP_KEY", "");

        try {
            $data = $request->all();

            $id = $data['official_business_id'];

            $officialbusinessapplication = OfficialBusinessApplication::findOrNew($id);

            $validate = Validator::make($request->all(), [
                'date' => 'required|date_format:Y-m-d|after:2000-01-01',
                'date_time_from' => 'required|date_format:"Y-m-d\TH:i"|after:2000-01-01 00:00',
                'date_time_to' => 'required|date_format:Y-m-d\TH:i|after:2000-01-01 00:00',
                'purpose' => 'required'
            ], [
                'date.date_format' => 'Departure date invalid date.',
                'date_time_from.date_format' => 'Dete time from invalid date.',
                'date_time_to.date_format' => 'Dete time to invalid date.',
            ]);

            if ($validate->fails()) {
                return response()->json(['error' => 'Failed to Save. Please check required fields. ' . Arr::first(Arr::flatten($validate->messages()->get('*')))], 500);
            }

            // Handle both user_id and employee_id
            $employee_id = null;
            if (isset($data["employee_id"])) {
                // Check if this is actually a user_id by looking in users table
                $user_check = DB::table('users')->where('id', $data["employee_id"])->first();
                if ($user_check) {
                    // This is a user_id, get the corresponding employee_id
                    $emp_data = DB::table('employees')->where('employee_no', $user_check->employee_no)->first();
                    $employee_id = $emp_data ? $emp_data->id : null;
                } else {
                    // This is already an employee_id
                    $employee_id = $data["employee_id"];
                }
            }

            if (!$employee_id) {
                return response()->json(['error' => 'Employee not found. Please ensure your account is linked to an employee record.'], 400);
            }

            $ob_data = array(
                'employee_id' => $employee_id, // Use the mapped employee_id
                'date' => $data['date'],
                'date_time_from' => date("Y/m/d H:i:s", strtotime($data['date_time_from'])),
                'date_time_to' => date("Y/m/d H:i:s", strtotime($data['date_time_to'])),
                'purpose' => $data['purpose'],
                'ob_type' => (int)$data['ob_type'], // Convert to integer
                'client' => $data['client'], // Add client field
                'recommending_approval' => $data['recommending_approval'],
                'recommending_position' => $data['recommending_position'],
                'approver' => $data['approver'],
            );

            $officialbusinessapplication->fill($ob_data);
            $officialbusinessapplication->save();

            if ($id == 0) {
                $ob_id = DB::table('official_business_applications')->max('id');
            } else {
                $ob_id = $id;
            }

            // Save OB Attachments
            if ($request->hasFile('attachments')) {

                $allowedfileExtension = ['jpg', 'jpeg', 'png', 'pdf', 'xls', 'xlsx', 'doc', 'docx'];
                $files = $request->file('attachments');
                $ctr = 0;

                foreach ($files as $file) {
                    $file_name = $file->getClientOriginalName();
                    $file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'ob_attachments\\' . 'OB' . $data['employee_id'] . '_' . $file_name;
                    $extension = $file->getClientOriginalExtension();
                    $check = in_array($extension, $allowedfileExtension);

                    if ($check) {
                        // Save record of attachments to database.
                        $ob_attachment_data = [
                            'ob_id' => $ob_id,
                            'attachment_name' => $file_name,
                            'attachment_path' => $file_path
                        ];

                        DB::table('ob_attachments')->insert($ob_attachment_data);

                        // Save attachment to path.
                        $request->attachments[$ctr]->storeAs('ob_attachments', 'OB' . $data['employee_id'] . '_' . $file_name);
                        $ctr++;
                    } else {
                        return response()->json(['error' => 'Invalid file attachment.'], 500);
                    }
                }
            }

            // Send Attachment Email Notifications.
            // $ob_attachments = DB::table('ob_attachments as a')
            //     ->join('official_business_applications as b', 'a.ob_id', '=', 'b.id')
            //     ->join('employees as c', 'b.employee_id', '=', 'c.id')
            //     ->select(
            //         'a.ob_id',
            //         'a.attachment_name',
            //         'a.attachment_path',
            //         DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
            //                     CONCAT(c.first_name,' ',c.last_name)
            //                 ELSE
            //                     RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
            //                 END as name")
            //     )
            //     ->where('a.ob_id', $ob_id)
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
            //     Notification::send($users, new EmailOBApplication($user_account, $ob_attachments));
            // }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Control Panel',
                'menu' => 'Official Business Applcation',
                'activity' => 'Add',
                'description' => 'Add official business application informations.',
            );

            Audit::create($data_audit);

            return response()->json(['success' => true, 'message' => 'Official business application saved successfully!'], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function storeunofficial(Request $request)
    {
        $app_key = env("APP_KEY", "");

        try {
            $data = $request->all();

            $id = $data['official_business_id'];

            $officialbusinessapplication = OfficialBusinessApplication::findOrNew($id);

            $validate = Validator::make($request->all(), [
                'date' => 'required|date_format:Y-m-d|after:2000-01-01',
                'date_time_from' => 'required|date_format:"Y-m-d\TH:i"|after:2000-01-01 00:00',
                'date_time_to' => 'required|date_format:Y-m-d\TH:i|after:2000-01-01 00:00',
                'client' => 'required',
                'ob_type' => 'required',
                'funds' => 'required',
                'recommending_approval' => 'required',
                'recommending_position' => 'required',
                'approver' => 'required',
                'approver_position' => 'required',
                'purpose' => 'required'
            ], [
                'date.date_format' => 'Departure date invalid date.',
                'date_time_from.date_format' => 'Dete time from invalid date.',
                'date_time_to.date_format' => 'Dete time to invalid date.',
            ]);

            if ($validate->fails()) {
                return response()->json(['error' => 'Failed to Save. Please check required fields. ' . Arr::first(Arr::flatten($validate->messages()->get('*')))], 500);
            }

            // Handle both user_id and employee_id
            $employee_id = null;
            if (isset($data["employee_id"])) {
                // Check if this is actually a user_id by looking in users table
                $user_check = DB::table('users')->where('id', $data["employee_id"])->first();
                if ($user_check) {
                    // This is a user_id, get the corresponding employee_id
                    $emp_data = DB::table('employees')->where('employee_no', $user_check->employee_no)->first();
                    $employee_id = $emp_data ? $emp_data->id : null;
                } else {
                    // This is already an employee_id
                    $employee_id = $data["employee_id"];
                }
            }

            if (!$employee_id) {
                return response()->json(['error' => 'Employee not found. Please ensure your account is linked to an employee record.'], 400);
            }

            $ob_data = array(
                'employee_id' => $employee_id, // Use the mapped employee_id
                'date' => $data['date'],
                'date_time_from' => date("Y/m/d H:i:s", strtotime($data['date_time_from'])),
                'date_time_to' => date("Y/m/d H:i:s", strtotime($data['date_time_to'])),
                'ob_type' => (int)$data['ob_type'], // Convert to integer
                'funds' => $data['funds'],
                'recommending_approval' => $data['recommending_approval'],
                'recommending_position' => $data['recommending_position'],
                'approver' => $data['approver'],
                'approver_position' => $data['approver_position'],
                'client' => $data['client'],
                'purpose' => $data['purpose'],
            );

            $officialbusinessapplication->fill($ob_data);
            $officialbusinessapplication->save();

            if ($id == 0) {
                $ob_id = DB::table('official_business_applications')->max('id');
            } else {
                $ob_id = $id;
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Control Panel',
                'menu' => 'Official Business Applcation',
                'activity' => 'Add',
                'description' => 'Add official business application informations.',
            );

            Audit::create($data_audit);

            return response()->json(['success' => true, 'message' => 'Travel authority application saved successfully!'], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function approve($id, $remarks)
    {
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

        $ob_to_approved = DB::table('official_business_applications')->select('employee_id')->where('id', $id)->get();

        if ($ob_to_approved->isNotEmpty()) {
            $emp_id = $ob_to_approved[0]->employee_id;
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
            $ob_data = array(
                'approved' => true,
                'disapproved' => false,
                'processed_date' => $process_date,
                'processed_by' => $approver_emp_id,
                'approved_remarks' => $remarks
            );
        } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
            $ob_data = array(
                'approved_2' => true,
                'disapproved_2' => false,
                'processed_date_2' => $process_date,
                'processed_by_2' => $approver_emp_id,
                'approved_remarks' => $remarks
            );
        } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
            $ob_data = array(
                'approved' => true,
                'disapproved' => false,
                'processed_date' => $process_date,
                'processed_by' => $approver_emp_id,
                'approved_remarks' => $remarks,
                'approved_2' => true,
                'disapproved_2' => false,
                'processed_date_2' => $process_date,
                'processed_by_2' => $approver_emp_id,
                'approved_remarks' => $remarks
            );
        } elseif ($approver_3->isNotEmpty()) {
            $ob_data = array(
                'approved_3' => true,
                'disapproved_3' => false,
                'processed_date_3' => $process_date,
                'processed_by_3' => $approver_emp_id,
                'approved_remarks' => $remarks
            );
        }

        DB::table('official_business_applications')->where('id', $id)->update($ob_data);

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module' => 'Main',
            'menu' => 'Official Business Application',
            'activity' => 'Approved Official Business Application',
            'description' => 'Approved employee official business application.',
        );

        Audit::create($data_audit);

        return back()->with('success', 'You have successfully approved official business application!');
    }

    public function disapprove($id, $remarks)
    {
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

        $ob_to_approved = DB::table('official_business_applications')->select('employee_id')->where('id', $id)->get();

        if ($ob_to_approved->isNotEmpty()) {
            $emp_id = $ob_to_approved[0]->employee_id;
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
            $ob_data = array(
                'approved' => false,
                'disapproved' => true,
                'processed_date' => $process_date,
                'processed_by' => $approver_emp_id,
                'disapproved_remark' => $remarks
            );
        } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
            $ob_data = array(
                'approved_2' => false,
                'disapproved_2' => true,
                'processed_date_2' => $process_date,
                'processed_by_2' => $approver_emp_id,
                'disapproved_2_remark' => $remarks
            );
        } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
            $ob_data = array(
                'approved' => false,
                'disapproved' => true,
                'processed_date' => $process_date,
                'processed_by' => $approver_emp_id,
                'disapproved_remark' => $remarks,
                'approved_2' => false,
                'disapproved_2' => true,
                'processed_date_2' => $process_date,
                'processed_by_2' => $approver_emp_id,
                'disapproved_2_remark' => $remarks
            );
        } elseif ($approver_3->isNotEmpty()) {
            $ob_data = array(
                'approved_3' => false,
                'disapproved_3' => true,
                'processed_date_3' => $process_date,
                'processed_by_3' => $approver_emp_id,
                'disapproved_3_remark' => $remarks
            );
        }

        DB::table('official_business_applications')->where('id', $id)->update($ob_data);

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module' => 'Main',
            'menu' => 'Official Business Application',
            'activity' => 'Disapproved Official Business Application',
            'description' => 'Disapproved employee official business application.',
        );

        Audit::create($data_audit);

        return back()->with('success', 'You have successfully approved official business application!');
    }

    public function cancel($id, $remarks)
    {

        $emp_id_data = DB::table('users as a')
            ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
            ->leftJoin('departments as c', 'b.id', '=', 'c.employee_id')
            ->selectRaw('case when b.id = null then 0 else b.id end as id')
            ->selectRaw('case when c.employee_id = null then 0 else c.employee_id end as supervisor_id')
            ->where('a.id', Auth::user()->id)
            ->get();

        $ob_data = array(
            'approved' => false,
            'disapproved' => false,
            'is_cancel' => true,
            'canceled_by' => $emp_id_data[0]->id,
            'canceled_date' => now(),
            'canceled_remarks' => $remarks
        );

        DB::table('official_business_applications')->where('id', $id)->update($ob_data);

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module' => 'Main',
            'menu' => 'Official Business Application',
            'activity' => 'Cancel Official Business Application',
            'description' => 'Cancel employee official business application.',
        );

        Audit::create($data_audit);

        // return back()->with('success', 'You have canceled approved official business application!');

        return json_encode('success');
    }

    public function destroy($id)
    {
        DB::table('official_business_applications')->where('id', $id)->delete();

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module' => 'Official Business Application',
            'menu' => 'Official Business Application',
            'activity' => 'Delete',
            'description' => 'Deleted official business application.',
        );

        Audit::create($data_audit);

        return response()->json(['success' => true, 'message' => 'Official business application deleted successfully']);
    }

    public function monitoring()
    {
        $app_key = env("APP_KEY", "");

        $ForapprovalEmployeeOB = DB::table('official_business_applications as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->select(
                'a.id',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                'a.created_at',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'a.client',
                'a.purpose',
                'a.attachment_name'
            )
            ->where([
                'a.approved' => false,
                'a.disapproved' => false,
                'a.approved_2' => false,
                'a.disapproved_2' => false,
                'is_cancel' => false
            ])
            ->get();

        $ApprovedEmployeeOB = DB::table('official_business_applications as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('employees as d', 'd.id', '=', 'a.processed_by')
            ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
            ->select(
                'a.id',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                'a.created_at',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'a.client',
                'a.purpose',
                'a.processed_date',
                'a.processed_date_2',
                DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN
                                CONCAT(d.first_name,' ',d.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key')) 
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

        $DisapprovedEmployeeOB = DB::table('official_business_applications as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('employees as d', 'd.id', '=', 'a.processed_by')
            ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
            ->select(
                'a.id',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                'a.created_at',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'a.client',
                'a.purpose',
                'a.disapproved_remark as remarks',
                'a.processed_date',
                'a.processed_date_2',
                DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN
                                CONCAT(d.first_name,' ',d.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key')) 
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

        $CancelledEmployeeOB = DB::table('official_business_applications as a')
            ->leftJoin('employees as b', 'a.canceled_by', '=', 'b.id')
            ->join('employees as c', 'c.id', '=', 'a.employee_id')
            ->select(
                'a.*',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as cancelled_by")
            )
            ->where(['is_cancel' => true])
            ->orderby('created_at', 'asc')
            ->get();


        return $this->successResponse([
            'ForapprovalEmployeeOB' => $ForapprovalEmployeeOB,
            'ApprovedEmployeeOB' => $ApprovedEmployeeOB,
            'DisapprovedEmployeeOB' => $DisapprovedEmployeeOB,
            'CancelledEmployeeOB' => $CancelledEmployeeOB
        ], 'OB approvals retrieved successfully');
    }

    public function attachments($id)
    {
        $attachments = DB::table('ob_attachments')->where('ob_id', $id)->get();

        return json_encode($attachments);
    }

    public function remove_attachments($id)
    {
        $data = DB::table('ob_attachments')->where('id', $id)->delete();

        return json_encode($data);
    }

    public function cancel_attachment(Request $request)
    {

        $id = $request->ob_header_id;
        $attachment_data = [];

        // Save Leave Attachments
        if ($request->hasFile('attachment')) {
            $files = $request->file('attachment');
            $file_name = $files->getClientOriginalName();
            $file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'OB_cancelled_documents\\' . 'DOCS' . $id . '_' . $file_name;
            $extension = $files->getClientOriginalExtension();

            $attachment_data = [
                'attachment_name' => $file_name,
                'path' => $file_path,
                'extension' => $extension
            ];

            // Update Time Data Table
            DB::table('official_business_applications')->where('id', $id)->update($attachment_data);

            // Save attachment to path.
            $request->attachment->storeAs('OB_cancelled_documents', 'DOCS' . $id . '_' . $file_name);
        }

        return $this->successResponse(null, 'Successfully Cancelled Application.');
    }

    public function download($id)
    {
        $documents = DB::table('official_business_applications as a')
            ->select(
                'a.id',
                'a.employee_id',
                'a.attachment_name'
            )
            ->where('a.id', $id)
            ->get();

        $pathToFile = storage_path('app/OB_cancelled_documents/' . 'DOCS' . $documents[0]->id . '_' . $documents[0]->attachment_name);

        return response()->download($pathToFile);
    }

    public function downloadapproval($id)
    {
        $documents = DB::table('official_business_applications as a')
            ->join('ob_attachments as d', 'a.id', '=', 'd.ob_id')
            ->select(
                'a.id',
                'a.employee_id',
                'd.attachment_name'
            )
            ->where('d.id', $id)
            ->get();

        $pathToFile = storage_path('app/ob_attachments/' . 'OB' . $documents[0]->employee_id . '_' . $documents[0]->attachment_name);

        return response()->download($pathToFile);
    }

    public function downloadAttachment($id)
    {
        $documents = DB::table('ob_attachments as a')
            ->join('official_business_applications as b', 'a.ob_id', '=', 'b.id')
            ->select(
                'a.id',
                'b.employee_id',
                'a.attachment_name'
            )
            ->where('a.ob_id', $id)
            ->get();

        $pathToFile = storage_path('app/ob_attachments/' . 'OB' . $documents[0]->employee_id . '_' . $documents[0]->attachment_name);

        return response()->download($pathToFile);
    }

    public function print($id)
    {
        $app_key = env("APP_KEY", "");

        $ob = DB::table('official_business_applications as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->join('positions as c', 'b.position_id', '=', 'c.id')
            ->leftJoin('branches as br', 'br.id', '=', 'b.branch_id')
            ->select(
                'a.id',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'a.client',
                'a.ob_type',
                'a.purpose',
                'a.recommending_approval',
                'a.approver_position',
                'a.approver',
                'a.recommending_position',
                'a.recommending_position',
                'a.approver',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(b.first_name,' ',b.last_name))
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                'c.name as position',
                DB::raw("CASE WHEN ISNULL(b.branch_id,0) <> 0 THEN
                              CASE WHEN br.is_main_branch = 1 THEN
                                    CAST(1 as INT)
                                   ELSE
                                    CAST(0 as INT)
                               END
                              ELSE
                               CAST(2 AS INT)
                         END AS from_branch")
            )
            ->where('a.id', $id)
            ->get();

        $document_no = DB::table('document_numbers')->where('id', 9)->get();

        if ($document_no->isNotEmpty()) {
            if ($ob[0]->from_branch == 1) {
                $footer = [
                    'document_no' => $document_no[0]->co_document_number,
                    'revision' => $document_no[0]->co_revision,
                ];
            } elseif ($ob[0]->from_branch == 0) {
                $footer = [
                    'document_no' => $document_no[0]->rd_document_number,
                    'revision' => $document_no[0]->rd_revision,
                ];
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }
        } else {
            $footer = [
                'document_no' => '',
                'revision' => '',
            ];
        }

        $pdf = PDF::loadView('official_business_application.official_business_report', compact('ob', 'footer'))->setOptions(['defaultFont' => 'sans-serif']);
        $pdf->setPaper('A4');
        $pdf->output();
        return $pdf->stream();
    }

    public function printunofficial($id)
    {
        $app_key = env("APP_KEY", "");

        $ob = DB::table('official_business_applications as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->join('positions as c', 'b.position_id', '=', 'c.id')
            ->join('departments as e', 'b.department_id', '=', 'e.id') // Keep only one join for positions
            ->leftJoin('branches as d', 'd.id', '=', 'b.branch_id') // Use correct alias for branches
            ->select(
                'a.id',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'c.name as position',
                'd.name as branch', // Change 'br' to 'd' to match alias
                'a.client',
                'a.purpose',
                'a.ob_type',
                'a.funds',
                'a.recommending_approval',
                'a.approver_position',
                'a.approver',
                'a.recommending_position',
                'e.name as department',
                'a.recommending_approval',
                'a.approver_position',
                'a.approver',
                'a.recommending_position',
                'a.recommending_position',
                'a.approver',
                'a.approver_position',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        UPPER(CONCAT(b.first_name,' ',b.last_name))
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                    END as name"),
                DB::raw("CASE WHEN ISNULL(b.branch_id,0) <> 0 THEN
                      CASE WHEN d.is_main_branch = 1 THEN
                            CAST(1 as INT)
                           ELSE
                            CAST(0 as INT)
                       END
                      ELSE
                       CAST(2 AS INT)
                 END AS from_branch")
            )
            ->where('a.id', $id)
            ->get();


        $document_no = DB::table('document_numbers')->where('id', 9)->get();

        $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

        if ($document_no->isNotEmpty()) {
            if ($ob[0]->from_branch == 1) {
                $footer = [
                    'document_no' => $document_no[0]->co_document_number,
                    'revision' => $document_no[0]->co_revision,
                ];
            } elseif ($ob[0]->from_branch == 0) {
                $footer = [
                    'document_no' => $document_no[0]->rd_document_number,
                    'revision' => $document_no[0]->rd_revision,
                ];
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }
        } else {
            $footer = [
                'document_no' => '',
                'revision' => '',
            ];
        }

        $pdf = PDF::loadView('official_business_application.unofficial_business_report', compact('ob', 'footer', 'image'))->setOptions(['defaultFont' => 'sans-serif']);
        $pdf->setPaper('A4');
        $pdf->output();
        return $pdf->stream();
    }

    public function printorder($id)
    {
        $app_key = env("APP_KEY", "");

        $ob = DB::table('official_business_applications as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->join('positions as c', 'b.position_id', '=', 'c.id') // Keep only one join for positions
            ->leftJoin('branches as d', 'd.id', '=', 'b.branch_id') // Use correct alias for branches
            ->select(
                'a.id',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'c.name as position',
                'd.name as branch', // Change 'br' to 'd' to match alias
                'a.client',
                'a.purpose',
                'a.ob_type',
                'a.funds',
                'a.recommending_approval',
                'a.approver_position',
                'a.approver',
                'a.recommending_position',
                'a.recommending_approval',
                'a.approver_position',
                'a.approver',
                'a.recommending_position',
                'a.recommending_position',
                'a.approver',
                'a.approver_position',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        UPPER(CONCAT(b.first_name,' ',b.last_name))
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                    END as name"),
                DB::raw("CASE WHEN ISNULL(b.branch_id,0) <> 0 THEN
                      CASE WHEN d.is_main_branch = 1 THEN
                            CAST(1 as INT)
                           ELSE
                            CAST(0 as INT)
                       END
                      ELSE
                       CAST(2 AS INT)
                 END AS from_branch")
            )
            ->where('a.id', $id)
            ->get();


        $document_no = DB::table('document_numbers')->where('id', 9)->get();

        $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

        $image2 = base64_encode(file_get_contents(public_path('/dist/img/reports/Pilipinas_logo.png')));

        if ($document_no->isNotEmpty()) {
            if ($ob[0]->from_branch == 1) {
                $footer = [
                    'document_no' => $document_no[0]->co_document_number,
                    'revision' => $document_no[0]->co_revision,
                ];
            } elseif ($ob[0]->from_branch == 0) {
                $footer = [
                    'document_no' => $document_no[0]->rd_document_number,
                    'revision' => $document_no[0]->rd_revision,
                ];
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }
        } else {
            $footer = [
                'document_no' => '',
                'revision' => '',
            ];
        }

        $pdf = PDF::loadView('official_business_application.order_business_report', compact('ob', 'footer', 'image', 'image2'))->setOptions(['defaultFont' => 'sans-serif']);
        $pdf->setPaper('legal');
        $pdf->output();
        return $pdf->stream();
    }
}
