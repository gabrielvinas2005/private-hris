<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Carbon\Carbon;
use Auth;
use App\Audit;
use App\User;
use Notification;
use App\Notifications\EmailOBApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\OfficialBusinessApplication;
use App\Traits\GeneratesPdf;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OfficialBusinessApplicationController extends Controller
{
    use GeneratesPdf;

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
                    // Original (decrypting) supervisor/display name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                    //             CONCAT(b.first_name,' ',b.last_name)
                    //         ELSE
                    //             RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                    //         END as name")
                    DB::raw("CONCAT(b.first_name,' ',b.last_name) as name")
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
                    // Original decrypting name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                    // Original decrypting employee name kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("b.first_name as first_name"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    DB::raw("b.last_name as last_name"),
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
                /*
                 * Original decrypting approver names kept for reference:
                 * DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN CONCAT(d.first_name,' ',d.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key')) END as approver_1"),
                 * DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as approver_2")
                 */
                DB::raw("CONCAT(d.first_name,' ',d.last_name) as approver_1"),
                DB::raw("CONCAT(e.first_name,' ',e.last_name) as approver_2")
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
                /*
                 * Original decrypting approver names kept for reference:
                 * DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN CONCAT(d.first_name,' ',d.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key')) END as approver_1"),
                 * DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as approver_2")
                 */
                DB::raw("CONCAT(d.first_name,' ',d.last_name) as approver_1"),
                DB::raw("CONCAT(e.first_name,' ',e.last_name) as approver_2")
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
                /*
                 * Original decrypting cancelled_by name kept for reference:
                 * DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) END as cancelled_by")
                 */
                DB::raw("CONCAT(b.first_name,' ',b.last_name) as cancelled_by")
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

        // Re-bucket monitoring rows using dynamic approver requirements (levels 1..3).
        // This keeps API status consistent with SP logic where non-configured levels are not required.
        $forApprovalCollection = collect($ForapprovalEmployeeOB ?? []);
        $approvedCollection = collect($ApprovedEmployeeOB ?? []);
        $disapprovedCollection = collect($DisapprovedEmployeeOB ?? []);
        $cancelledCollection = collect($CancelledEmployeeOB ?? []);
        $allMonitoringRows = $forApprovalCollection
            ->merge($approvedCollection)
            ->merge($disapprovedCollection)
            ->merge($cancelledCollection)
            ->unique('id')
            ->values();

        $ExpiredEmployeeOB = collect();
        if ($allMonitoringRows->isNotEmpty()) {
            $ids = $allMonitoringRows->pluck('id')->filter()->unique()->values()->all();
            $employeeIds = $allMonitoringRows->pluck('employee_id')->filter()->unique()->values()->all();

            $flagsById = DB::table('official_business_applications')
                ->select(
                    'id',
                    'employee_id',
                    'date',
                    'date_time_from',
                    'date_time_to',
                    'approved',
                    'approved_2',
                    'approved_3',
                    'disapproved',
                    'disapproved_2',
                    'disapproved_3',
                    'is_cancel',
                    'is_cancel_2',
                    'is_cancel_3'
                )
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            $approverConfigByEmployee = DB::table(DB::raw('(SELECT employee_id, approver_id FROM (
                    SELECT ad.employee_id, ad.approver_id,
                        ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn
                    FROM approver_details ad
                    INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id IN (2,5)
                ) x WHERE rn = 1) ad'))
                ->join('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
                ->whereIn('ad.employee_id', $employeeIds)
                ->select('ad.employee_id', 'ah.approver_id_2', 'ah.approver_id_3')
                ->get()
                ->keyBy('employee_id');

            $isTrue = function ($v) {
                return $v === true || $v === 1 || $v === '1';
            };

            $forApprovalDyn = collect();
            $approvedDyn = collect();
            $disapprovedDyn = collect();
            $cancelledDyn = collect();
            $expiredDyn = collect();

            foreach ($allMonitoringRows as $row) {
                $flag = $flagsById->get($row->id);
                if (!$flag) {
                    // Fallback: keep rows visible even if flag lookup misses.
                    $forApprovalDyn->push($row);
                    continue;
                }

                $cfg = $approverConfigByEmployee->get($flag->employee_id);
                $require2 = !is_null($cfg->approver_id_2 ?? null);
                $require3 = !is_null($cfg->approver_id_3 ?? null);

                $isCancelled = $isTrue($flag->is_cancel ?? 0) || $isTrue($flag->is_cancel_2 ?? 0) || $isTrue($flag->is_cancel_3 ?? 0);
                $isDisapproved = $isTrue($flag->disapproved ?? 0) || $isTrue($flag->disapproved_2 ?? 0) || $isTrue($flag->disapproved_3 ?? 0);
                $isApproved = $isTrue($flag->approved ?? 0)
                    && (!$require2 || $isTrue($flag->approved_2 ?? 0))
                    && (!$require3 || $isTrue($flag->approved_3 ?? 0));

                if ($isCancelled) {
                    $cancelledDyn->push($row);
                    continue;
                }
                if ($isDisapproved) {
                    $disapprovedDyn->push($row);
                    continue;
                }
                if ($isApproved) {
                    $approvedDyn->push($row);
                    continue;
                }

                $from = !empty($flag->date_time_from) ? \Carbon\Carbon::parse($flag->date_time_from) : null;
                $to = !empty($flag->date_time_to) ? \Carbon\Carbon::parse($flag->date_time_to) : null;
                $date = !empty($flag->date) ? \Carbon\Carbon::parse($flag->date)->endOfDay() : null;
                $endPoint = $to ?: ($from ?: $date);

                if ($endPoint && $endPoint->lt(\Carbon\Carbon::now())) {
                    $expiredDyn->push($row);
                } else {
                    $forApprovalDyn->push($row);
                }
            }

            $ForapprovalEmployeeOB = $forApprovalDyn->values();
            $ApprovedEmployeeOB = $approvedDyn->values();
            $DisapprovedEmployeeOB = $disapprovedDyn->values();
            $CancelledEmployeeOB = $cancelledDyn->values();
            $ExpiredEmployeeOB = $expiredDyn->values();
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
            'ExpiredEmployeeOB' => $ExpiredEmployeeOB,
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
        // Absolute approver bypass: if the current user is admin, approve immediately at all levels
        if (Auth::user() && Auth::user()->is_admin) {
            $process_date = date("Y-m-d", strtotime(now()));

            $ob_data = array(
                'approved' => true,
                'disapproved' => false,
                'processed_date' => $process_date,
                'processed_by' => 0,
                'approved_2' => true,
                'disapproved_2' => false,
                'processed_date_2' => $process_date,
                'processed_by_2' => 0,
                'approved_3' => false,
                'disapproved_3' => false,
                'approved_remarks' => $remarks
            );

            DB::table('official_business_applications')->where('id', $id)->update($ob_data);

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Main',
                'menu' => 'Official Business Application',
                'activity' => 'Approved Official Business Application (Admin Bypass)',
                'description' => 'Admin approved official business application at all levels.',
            );

            Audit::create($data_audit);

            return back()->with('success', 'You have successfully approved official business application!');
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
        // Absolute approver bypass: if the current user is admin, disapprove immediately at all levels
        if (Auth::user() && Auth::user()->is_admin) {
            $process_date = date("Y-m-d", strtotime(now()));

            $ob_data = array(
                'approved' => false,
                'disapproved' => true,
                'processed_date' => $process_date,
                'processed_by' => 0,
                'approved_2' => false,
                'disapproved_2' => true,
                'processed_date_2' => $process_date,
                'processed_by_2' => 0,
                'approved_3' => false,
                'disapproved_3' => false,
                'disapproved_remark' => $remarks,
                'disapproved_2_remark' => $remarks
            );

            DB::table('official_business_applications')->where('id', $id)->update($ob_data);

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Main',
                'menu' => 'Official Business Application',
                'activity' => 'Disapproved Official Business Application (Admin Bypass)',
                'description' => 'Admin disapproved official business application at all levels.',
            );

            Audit::create($data_audit);

            return back()->with('success', 'You have successfully approved official business application!');
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

		// Only approved OB can be cancelled
		$ob_status = DB::table('official_business_applications')
			->select('approved', 'approved_2', 'approved_3', 'disapproved', 'disapproved_2', 'disapproved_3', 'is_cancel')
			->where('id', $id)
			->first();

		if (!$ob_status) {
			return response()->json(['message' => 'Official business application not found'], 404);
		}

		if ($ob_status->is_cancel) {
			return response()->json(['message' => 'Official business application already cancelled'], 422);
		}

		$alreadyApproved = (bool)($ob_status->approved || $ob_status->approved_2);
		if (!$alreadyApproved) {
			return response()->json(['message' => 'Only approved official business application can be cancelled'], 422);
		}

        $emp_id_data = DB::table('users as a')
            ->leftJoin('employees as b', 'b.employee_no', '=', 'a.employee_no')
            ->selectRaw('ISNULL(b.id,0) as id')
            ->where('a.id', Auth::user()->id)
            ->first();

        $ob_data = array(
            'approved' => null,
            'approved_2' => null,
            'approved_3' => null,
            'approved_remarks' => null,
            'approver' => null,
            'approver_position' => null,
            'recommending_approval' => null,
            'disapproved' => false,
            'disapproved_2' => false,
            'disapproved_3' => false,
            'is_cancel' => true,
            'canceled_by' => ($emp_id_data ? ($emp_id_data->id ?? 0) : 0),
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

		return $this->successResponse(null, 'Successfully Cancelled Application.');
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
            ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
            ->leftJoin('ta_type as ta', function ($join) {
                $join->on('a.type_id', '=', 'ta.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('2'));
            })
            ->leftJoin('to_type as to_t', function ($join) {
                $join->on('a.type_id', '=', 'to_t.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('3'));
            })
            // approver_details.employee_id = OB applicant; approver_details.approver_id -> approver_headers.id
            // approver_headers.type_id=2 (Official Business); approver_headers.approver_id_1/2/3 -> employees.id
            ->leftJoin(DB::raw('(SELECT employee_id, approver_id FROM (
                SELECT ad.employee_id, ad.approver_id,
                    ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn
                FROM approver_details ad
                INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
            ) x WHERE rn = 1) ad'), 'ad.employee_id', '=', 'b.id')
            ->leftJoin('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
            // Join with employees table to get approver names from approver_headers
            ->leftJoin('employees as app1', 'app1.id', '=', 'ah.approver_id_1')
            ->leftJoin('employees as app2', 'app2.id', '=', 'ah.approver_id_2')
            ->leftJoin('employees as app3', 'app3.id', '=', 'ah.approver_id_3')
            ->leftJoin('employees as app4', 'app4.id', '=', 'ah.approver_id_4')
            // Join for branch_approver_id_1 (Travel Order approver)
            ->leftJoin('employees as branch_app', 'branch_app.id', '=', 'ah.branch_approver_id_1')
            ->leftJoin('positions as branch_app_pos', 'branch_app_pos.id', '=', 'branch_app.position_id')
            ->select(
                'a.id',
                'b.id as employee_id',
                'b.photo',
                'b.employee_no',
                'b.position_id',
                'b.department_id',
                // Original decrypting applicant name kept for reference:
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                DB::raw("b.first_name as first_name"),
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                DB::raw("b.last_name as last_name"),
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                DB::raw("b.middle_name as middle_name"),
                'pos.name as position',
                'dept.name as department',
                'a.created_at',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'a.ob_type',
                'a.type_id',
                'a.recommending_approval',
                DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name"),
                'a.client',
                'a.purpose',
                'a.attachment_name',
                'a.approved',
                'a.approved_2',
                'a.approved_3',
                'a.processed_date',
                'a.processed_date_2',
                'a.processed_date_3',
                'b.mobile_no',
                'b.telephone_no',
                // Designated approvers from approver_headers hierarchy
                /*
                 * Original decrypting approver names kept for reference:
                 * (commented out to avoid runtime dbo.ufn_DecryptString usage)
                 */
                /* DB::raw("CASE WHEN ISNULL(app1.is_encrypted,0) = 0 THEN
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
                            END as approver_1"), */
                DB::raw("CASE WHEN ISNULL(app1.middle_name,'') = '' THEN
                                CONCAT(app1.first_name,' ',app1.last_name)
                            ELSE
                                CONCAT(app1.first_name,' ',UPPER(SUBSTRING(app1.middle_name,1,1)),'. ',app1.last_name)
                            END as approver_1"),
                /* DB::raw("CASE WHEN ISNULL(app2.is_encrypted,0) = 0 THEN
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
                            END as approver_2"), */
                DB::raw("CASE WHEN ISNULL(app2.middle_name,'') = '' THEN
                                CONCAT(app2.first_name,' ',app2.last_name)
                            ELSE
                                CONCAT(app2.first_name,' ',UPPER(SUBSTRING(app2.middle_name,1,1)),'. ',app2.last_name)
                            END as approver_2"),
                /* DB::raw("CASE WHEN ISNULL(app3.is_encrypted,0) = 0 THEN
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
                            END as approver_3"), */
                DB::raw("CASE WHEN ISNULL(app3.middle_name,'') = '' THEN
                                CONCAT(app3.first_name,' ',app3.last_name)
                            ELSE
                                CONCAT(app3.first_name,' ',UPPER(SUBSTRING(app3.middle_name,1,1)),'. ',app3.last_name)
                            END as approver_3"),
                /* DB::raw("CASE WHEN ISNULL(app4.is_encrypted,0) = 0 THEN
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
                            END as approver_4"), */
                DB::raw("CASE WHEN ISNULL(app4.middle_name,'') = '' THEN
                                CONCAT(app4.first_name,' ',app4.last_name)
                            ELSE
                                CONCAT(app4.first_name,' ',UPPER(SUBSTRING(app4.middle_name,1,1)),'. ',app4.last_name)
                            END as approver_4"),
                // Branch approver (for Travel Order)
                /* DB::raw("CASE WHEN ISNULL(branch_app.is_encrypted,0) = 0 THEN
                                CASE WHEN ISNULL(branch_app.middle_name,'') = '' THEN
                                    UPPER(CONCAT(branch_app.first_name,' ',branch_app.last_name))
                                ELSE
                                    UPPER(CONCAT(branch_app.first_name,' ',substring(branch_app.middle_name,1,1),'. ',branch_app.last_name))
                                END
                            ELSE
                                CASE WHEN ISNULL(branch_app.middle_name,'') = '' THEN
                                    UPPER(RTRIM([dbo].[ufn_DecryptString](branch_app.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](branch_app.last_name,'$app_key')))
                                ELSE
                                    UPPER(RTRIM([dbo].[ufn_DecryptString](branch_app.first_name,'$app_key'))+' '+substring([dbo].[ufn_DecryptString](branch_app.middle_name,'$app_key'),1,1)+'. '+RTRIM([dbo].[ufn_DecryptString](branch_app.last_name,'$app_key')))
                                END
                            END as branch_approver_name"), */
                DB::raw("CASE WHEN ISNULL(branch_app.middle_name,'') = '' THEN
                                UPPER(CONCAT(branch_app.first_name,' ',branch_app.last_name))
                            ELSE
                                UPPER(CONCAT(branch_app.first_name,' ',UPPER(SUBSTRING(branch_app.middle_name,1,1)),'. ',branch_app.last_name))
                            END as branch_approver_name"),
                DB::raw("ISNULL(branch_app_pos.name,'') as branch_approver_position"),
                // Status: Only show as approved if approved_3 = true
                DB::raw("CASE WHEN a.approved_3 = 1 AND a.disapproved_3 = 0 AND (a.is_cancel = 0 OR a.is_cancel IS NULL) THEN 'Approved' 
                           WHEN a.disapproved = 1 OR a.disapproved_2 = 1 OR a.disapproved_3 = 1 THEN 'Disapproved'
                           WHEN a.is_cancel = 1 THEN 'Cancelled'
                           ELSE 'Pending' END as status")
            )
            ->where('a.disapproved', false)
            ->where('a.disapproved_2', false)
            ->where(function ($q) {
                $q->where('a.disapproved_3', false)->orWhereNull('a.disapproved_3');
            })
            ->where(function ($q) {
                $q->where('a.is_cancel', false)->orWhereNull('a.is_cancel');
            })
            ->where(function($query) {
                // For Approval: approved_3 = 0 (not yet approved by 3rd approver)
                $query->where(function($q) {
                    $q->where('a.approved_3', false)->orWhereNull('a.approved_3');
                });
            })
            // Cutoff based on a.date: only applications still available for approving (date >= today)
            ->whereDate('a.date', '>=', Carbon::today()->toDateString())
            ->distinct()
            ->get();

        $ApprovedEmployeeOB = DB::table('official_business_applications as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
            ->leftJoin('ta_type as ta', function ($join) {
                $join->on('a.type_id', '=', 'ta.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('2'));
            })
            ->leftJoin('to_type as to_t', function ($join) {
                $join->on('a.type_id', '=', 'to_t.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('3'));
            })
            ->leftJoin('employees as d', 'd.id', '=', 'a.processed_by')
            ->leftJoin('positions as pd', 'pd.id', '=', 'd.position_id')
            ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
            ->leftJoin('positions as pe', 'pe.id', '=', 'e.position_id')
            // approver_details.employee_id = OB applicant; approver_details.approver_id -> approver_headers.id
            // approver_headers.type_id=2 (Official Business); approver_headers.approver_id_1/2/3 -> employees.id
            ->leftJoin(DB::raw('(SELECT employee_id, approver_id FROM (
                SELECT ad.employee_id, ad.approver_id,
                    ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn
                FROM approver_details ad
                INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
            ) x WHERE rn = 1) ad'), 'ad.employee_id', '=', 'b.id')
            ->leftJoin('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
            // Join with employees table to get approver names from approver_headers
            ->leftJoin('employees as app1', 'app1.id', '=', 'ah.approver_id_1')
            ->leftJoin('employees as app2', 'app2.id', '=', 'ah.approver_id_2')
            ->leftJoin('employees as app3', 'app3.id', '=', 'ah.approver_id_3')
            ->leftJoin('employees as app4', 'app4.id', '=', 'ah.approver_id_4')
            // Join for branch_approver_id_1 (Travel Order approver)
            ->leftJoin('employees as branch_app', 'branch_app.id', '=', 'ah.branch_approver_id_1')
            ->leftJoin('positions as branch_app_pos', 'branch_app_pos.id', '=', 'branch_app.position_id')
            ->select(
                'a.id', 'a.employee_id',
                'b.photo',
                'b.employee_no',
                'b.position_id',
                'b.department_id',
                // Original decrypting applicant name kept for reference:
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                DB::raw("b.first_name as first_name"),
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                DB::raw("b.last_name as last_name"),
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                DB::raw("b.middle_name as middle_name"),
                'pos.name as position',
                'dept.name as department',
                'a.created_at',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'a.ob_type',
                'a.type_id',
                'a.recommending_approval',
                DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name"),
                'a.client',
                'a.purpose',
                'a.approved',
                'a.approved_2',
                'a.approved_3',
                'a.disapproved',
                'a.disapproved_2',
                'a.disapproved_3',
                'a.processed_date',
                'a.processed_date_2',
                'a.processed_date_3',
                'b.mobile_no',
                'b.telephone_no',
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
                // Branch approver (for Travel Order)
                DB::raw("CASE WHEN ISNULL(branch_app.is_encrypted,0) = 0 THEN
                                CASE WHEN ISNULL(branch_app.middle_name,'') = '' THEN
                                    UPPER(CONCAT(branch_app.first_name,' ',branch_app.last_name))
                                ELSE
                                    UPPER(CONCAT(branch_app.first_name,' ',substring(branch_app.middle_name,1,1),'. ',branch_app.last_name))
                                END
                            ELSE
                                CASE WHEN ISNULL(branch_app.middle_name,'') = '' THEN
                                    UPPER(RTRIM([dbo].[ufn_DecryptString](branch_app.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](branch_app.last_name,'$app_key')))
                                ELSE
                                    UPPER(RTRIM([dbo].[ufn_DecryptString](branch_app.first_name,'$app_key'))+' '+substring([dbo].[ufn_DecryptString](branch_app.middle_name,'$app_key'),1,1)+'. '+RTRIM([dbo].[ufn_DecryptString](branch_app.last_name,'$app_key')))
                                END
                            END as branch_approver_name"),
                DB::raw("ISNULL(branch_app_pos.name,'') as branch_approver_position"),
                // Keep processed_by names for tracking who actually processed
                DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN
                                CASE WHEN ISNULL(d.middle_name,'') = '' THEN
                                    CONCAT(d.first_name,' ',d.last_name)
                                ELSE
                                    CONCAT(d.first_name,' ',substring(d.middle_name,1,1),'. ',d.last_name)
                                END
                            ELSE
                                CASE WHEN ISNULL(d.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key'))
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](d.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key'))
                                END
                            END as processed_by_name_1"),
                DB::raw("ISNULL(pd.name,'') as approver_1_position"),
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
                DB::raw("ISNULL(pe.name,'') as approver_2_position")
            )
            ->where('a.approved', true)
            ->where('a.approved_2', true)
            ->where('a.approved_3', true)
            ->where('a.disapproved', false)
            ->where('a.disapproved_2', false)
            ->where(function ($q) {
                $q->where('a.disapproved_3', false)->orWhereNull('a.disapproved_3');
            })
            ->where(function ($q) {
                $q->where('a.is_cancel', false)->orWhereNull('a.is_cancel');
            })
            ->distinct()
            ->get();

        $DisapprovedEmployeeOB = DB::table('official_business_applications as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
            ->leftJoin('employees as d', 'd.id', '=', 'a.processed_by')
            ->leftJoin('positions as pd', 'pd.id', '=', 'd.position_id')
            ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
            ->leftJoin('positions as pe', 'pe.id', '=', 'e.position_id')
            ->leftJoin('ta_type as ta', function ($join) {
                $join->on('a.type_id', '=', 'ta.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('2'));
            })
            ->leftJoin('to_type as to_t', function ($join) {
                $join->on('a.type_id', '=', 'to_t.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('3'));
            })
            // approver_details.employee_id = OB applicant; approver_details.approver_id -> approver_headers.id
            // approver_headers.type_id=2 (Official Business); approver_headers.approver_id_1/2/3 -> employees.id
            ->leftJoin(DB::raw('(SELECT employee_id, approver_id FROM (
                SELECT ad.employee_id, ad.approver_id,
                    ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn
                FROM approver_details ad
                INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
            ) x WHERE rn = 1) ad'), 'ad.employee_id', '=', 'b.id')
            ->leftJoin('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
            // Join with employees table to get approver names from approver_headers
            ->leftJoin('employees as app1', 'app1.id', '=', 'ah.approver_id_1')
            ->leftJoin('employees as app2', 'app2.id', '=', 'ah.approver_id_2')
            ->leftJoin('employees as app3', 'app3.id', '=', 'ah.approver_id_3')
            ->leftJoin('employees as app4', 'app4.id', '=', 'ah.approver_id_4')
            ->select(
                'a.id', 'a.employee_id',
                'b.photo',
                'b.employee_no',
                'b.position_id',
                'b.department_id',
                // Original decrypting applicant name kept for reference:
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                DB::raw("b.first_name as first_name"),
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                DB::raw("b.last_name as last_name"),
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                DB::raw("b.middle_name as middle_name"),
                'pos.name as position',
                'dept.name as department',
                'a.created_at',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'a.ob_type',
                'a.type_id',
                'a.recommending_approval',
                DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name"),
                'a.client',
                'a.purpose',
                'a.disapproved_remark as remarks',
                'a.processed_date',
                'a.processed_date_2',
                'b.mobile_no',
                'b.telephone_no',
                // Designated approvers from approver_headers hierarchy
                // Original decrypting approver names kept for reference:
                // DB::raw("CASE WHEN ISNULL(app1.is_encrypted,0) = 0 THEN ... ELSE RTRIM([dbo].[ufn_DecryptString](app1.first_name,'$app_key')) ... END as approver_1"),
                DB::raw("CASE WHEN ISNULL(app1.middle_name,'') = '' THEN CONCAT(app1.first_name,' ',app1.last_name) ELSE CONCAT(app1.first_name,' ',UPPER(SUBSTRING(app1.middle_name,1,1)),'. ',app1.last_name) END as approver_1"),
                // DB::raw("CASE WHEN ISNULL(app2.is_encrypted,0) = 0 THEN ... ELSE RTRIM([dbo].[ufn_DecryptString](app2.first_name,'$app_key')) ... END as approver_2"),
                DB::raw("CASE WHEN ISNULL(app2.middle_name,'') = '' THEN CONCAT(app2.first_name,' ',app2.last_name) ELSE CONCAT(app2.first_name,' ',UPPER(SUBSTRING(app2.middle_name,1,1)),'. ',app2.last_name) END as approver_2"),
                // DB::raw("CASE WHEN ISNULL(app3.is_encrypted,0) = 0 THEN ... ELSE RTRIM([dbo].[ufn_DecryptString](app3.first_name,'$app_key')) ... END as approver_3"),
                DB::raw("CASE WHEN ISNULL(app3.middle_name,'') = '' THEN CONCAT(app3.first_name,' ',app3.last_name) ELSE CONCAT(app3.first_name,' ',UPPER(SUBSTRING(app3.middle_name,1,1)),'. ',app3.last_name) END as approver_3"),
                // DB::raw("CASE WHEN ISNULL(app4.is_encrypted,0) = 0 THEN ... ELSE RTRIM([dbo].[ufn_DecryptString](app4.first_name,'$app_key')) ... END as approver_4"),
                DB::raw("CASE WHEN ISNULL(app4.middle_name,'') = '' THEN CONCAT(app4.first_name,' ',app4.last_name) ELSE CONCAT(app4.first_name,' ',UPPER(SUBSTRING(app4.middle_name,1,1)),'. ',app4.last_name) END as approver_4"),
                // Keep processed_by names for tracking who actually processed
                // DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN ... ELSE RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key')) ... END as processed_by_name_1"),
                DB::raw("CASE WHEN ISNULL(d.middle_name,'') = '' THEN CONCAT(d.first_name,' ',d.last_name) ELSE CONCAT(d.first_name,' ',UPPER(SUBSTRING(d.middle_name,1,1)),'. ',d.last_name) END as processed_by_name_1"),
                DB::raw("ISNULL(pd.name,'') as approver_1_position"),
                // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN ... ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')) ... END as processed_by_name_2"),
                DB::raw("CASE WHEN ISNULL(e.middle_name,'') = '' THEN CONCAT(e.first_name,' ',e.last_name) ELSE CONCAT(e.first_name,' ',UPPER(SUBSTRING(e.middle_name,1,1)),'. ',e.last_name) END as processed_by_name_2"),
                DB::raw("ISNULL(pe.name,'') as approver_2_position")
            )
            ->where('is_cancel', false)
            ->where(function($query) {
                $query->where('a.disapproved', true)
                      ->orWhere('a.disapproved_2', true)
                      ->orWhere('a.disapproved_3', true);
            })
            ->distinct()
            ->get();

        // Expired: coverage date passed AND not fully approved (applies to any level 1, 2, or 3)
        $expiredCutoffDate = Carbon::today()->toDateString();
        $ExpiredEmployeeOB = DB::table('official_business_applications as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
            ->leftJoin('ta_type as ta', function ($join) {
                $join->on('a.type_id', '=', 'ta.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('2'));
            })
            ->leftJoin('to_type as to_t', function ($join) {
                $join->on('a.type_id', '=', 'to_t.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('3'));
            })
            ->leftJoin(DB::raw('(SELECT employee_id, approver_id FROM (
                SELECT ad.employee_id, ad.approver_id,
                    ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id) as rn
                FROM approver_details ad
                INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
            ) x WHERE rn = 1) ad'), 'ad.employee_id', '=', 'b.id')
            ->leftJoin('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
            ->leftJoin('employees as app1', 'app1.id', '=', 'ah.approver_id_1')
            ->leftJoin('employees as app2', 'app2.id', '=', 'ah.approver_id_2')
            ->leftJoin('employees as app3', 'app3.id', '=', 'ah.approver_id_3')
            ->leftJoin('employees as app4', 'app4.id', '=', 'ah.approver_id_4')
            ->leftJoin('employees as branch_app', 'branch_app.id', '=', 'ah.branch_approver_id_1')
            ->leftJoin('positions as branch_app_pos', 'branch_app_pos.id', '=', 'branch_app.position_id')
            ->select(
                'a.id',
                'b.id as employee_id',
                'b.photo',
                'b.employee_no',
                'b.position_id',
                'b.department_id',
                // Original decrypting applicant name kept for reference:
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                DB::raw("b.first_name as first_name"),
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                DB::raw("b.last_name as last_name"),
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                DB::raw("b.middle_name as middle_name"),
                'pos.name as position',
                'dept.name as department',
                'a.created_at',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'a.ob_type',
                'a.type_id',
                'a.recommending_approval',
                DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name"),
                'a.client',
                'a.purpose',
                'a.attachment_name',
                'a.approved',
                'a.approved_2',
                'a.approved_3',
                'a.processed_date',
                'a.processed_date_2',
                'a.processed_date_3',
                'b.mobile_no',
                'b.telephone_no',
                // Original decrypting approver names kept for reference:
                // DB::raw("CASE WHEN ISNULL(app1.is_encrypted,0) = 0 THEN CASE WHEN ISNULL(app1.middle_name,'') = '' THEN CONCAT(app1.first_name,' ',app1.last_name) ELSE CONCAT(app1.first_name,' ',substring(app1.middle_name,1,1),'. ',app1.last_name) END ELSE CASE WHEN ISNULL(app1.middle_name,'') = '' THEN RTRIM([dbo].[ufn_DecryptString](app1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app1.last_name,'$app_key')) ELSE RTRIM([dbo].[ufn_DecryptString](app1.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app1.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app1.last_name,'$app_key')) END END as approver_1"),
                DB::raw("CASE WHEN ISNULL(app1.middle_name,'') = '' THEN CONCAT(app1.first_name,' ',app1.last_name) ELSE CONCAT(app1.first_name,' ',UPPER(SUBSTRING(app1.middle_name,1,1)),'. ',app1.last_name) END as approver_1"),
                // DB::raw("CASE WHEN ISNULL(app2.is_encrypted,0) = 0 THEN CASE WHEN ISNULL(app2.middle_name,'') = '' THEN CONCAT(app2.first_name,' ',app2.last_name) ELSE CONCAT(app2.first_name,' ',substring(app2.middle_name,1,1),'. ',app2.last_name) END ELSE CASE WHEN ISNULL(app2.middle_name,'') = '' THEN RTRIM([dbo].[ufn_DecryptString](app2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app2.last_name,'$app_key')) ELSE RTRIM([dbo].[ufn_DecryptString](app2.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app2.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app2.last_name,'$app_key')) END END as approver_2"),
                DB::raw("CASE WHEN ISNULL(app2.middle_name,'') = '' THEN CONCAT(app2.first_name,' ',app2.last_name) ELSE CONCAT(app2.first_name,' ',UPPER(SUBSTRING(app2.middle_name,1,1)),'. ',app2.last_name) END as approver_2"),
                // DB::raw("CASE WHEN ISNULL(app3.is_encrypted,0) = 0 THEN CASE WHEN ISNULL(app3.middle_name,'') = '' THEN CONCAT(app3.first_name,' ',app3.last_name) ELSE CONCAT(app3.first_name,' ',substring(app3.middle_name,1,1),'. ',app3.last_name) END ELSE CASE WHEN ISNULL(app3.middle_name,'') = '' THEN RTRIM([dbo].[ufn_DecryptString](app3.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app3.last_name,'$app_key')) ELSE RTRIM([dbo].[ufn_DecryptString](app3.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app3.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app3.last_name,'$app_key')) END END as approver_3"),
                DB::raw("CASE WHEN ISNULL(app3.middle_name,'') = '' THEN CONCAT(app3.first_name,' ',app3.last_name) ELSE CONCAT(app3.first_name,' ',UPPER(SUBSTRING(app3.middle_name,1,1)),'. ',app3.last_name) END as approver_3"),
                // DB::raw("CASE WHEN ISNULL(app4.is_encrypted,0) = 0 THEN CASE WHEN ISNULL(app4.middle_name,'') = '' THEN CONCAT(app4.first_name,' ',app4.last_name) ELSE CONCAT(app4.first_name,' ',substring(app4.middle_name,1,1),'. ',app4.last_name) END ELSE CASE WHEN ISNULL(app4.middle_name,'') = '' THEN RTRIM([dbo].[ufn_DecryptString](app4.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](app4.last_name,'$app_key')) ELSE RTRIM([dbo].[ufn_DecryptString](app4.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](app4.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](app4.last_name,'$app_key')) END END as approver_4"),
                DB::raw("CASE WHEN ISNULL(app4.middle_name,'') = '' THEN CONCAT(app4.first_name,' ',app4.last_name) ELSE CONCAT(app4.first_name,' ',UPPER(SUBSTRING(app4.middle_name,1,1)),'. ',app4.last_name) END as approver_4"),
                // Original decrypting branch_approver_name kept for reference:
                // DB::raw("CASE WHEN ISNULL(branch_app.is_encrypted,0) = 0 THEN CASE WHEN ISNULL(branch_app.middle_name,'') = '' THEN UPPER(CONCAT(branch_app.first_name,' ',branch_app.last_name)) ELSE UPPER(CONCAT(branch_app.first_name,' ',substring(branch_app.middle_name,1,1),'. ',branch_app.last_name)) END ELSE CASE WHEN ISNULL(branch_app.middle_name,'') = '' THEN UPPER(RTRIM([dbo].[ufn_DecryptString](branch_app.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](branch_app.last_name,'$app_key'))) ELSE UPPER(RTRIM([dbo].[ufn_DecryptString](branch_app.first_name,'$app_key'))+' '+substring([dbo].[ufn_DecryptString](branch_app.middle_name,'$app_key'),1,1)+'. '+RTRIM([dbo].[ufn_DecryptString](branch_app.last_name,'$app_key'))) END END as branch_approver_name"),
                DB::raw("CASE WHEN ISNULL(branch_app.middle_name,'') = '' THEN UPPER(CONCAT(branch_app.first_name,' ',branch_app.last_name)) ELSE UPPER(CONCAT(branch_app.first_name,' ',UPPER(SUBSTRING(branch_app.middle_name,1,1)),'. ',branch_app.last_name)) END as branch_approver_name"),
                DB::raw("ISNULL(branch_app_pos.name,'') as branch_approver_position"),
                DB::raw("'Expired' as status")
            )
            ->where('a.disapproved', false)
            ->where('a.disapproved_2', false)
            ->where(function ($q) {
                $q->where('a.disapproved_3', false)->orWhereNull('a.disapproved_3');
            })
            ->where(function ($q) {
                $q->where('a.is_cancel', false)->orWhereNull('a.is_cancel');
            })
            ->whereRaw('NOT (ISNULL(a.approved, 0) = 1 AND ISNULL(a.approved_2, 0) = 1 AND ISNULL(a.approved_3, 0) = 1)')
            ->whereDate('a.date', '<', $expiredCutoffDate)
            ->distinct()
            ->get();

        $CancelledEmployeeOB = DB::table('official_business_applications as a')
            ->leftJoin('employees as b', 'a.canceled_by', '=', 'b.id')
            ->leftJoin('positions as pb', 'pb.id', '=', 'b.position_id')
            ->join('employees as c', 'c.id', '=', 'a.employee_id')
            ->leftJoin('positions as pos', 'c.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'c.department_id', '=', 'dept.id')
            ->leftJoin('ta_type as ta', function ($join) {
                $join->on('a.type_id', '=', 'ta.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('2'));
            })
            ->leftJoin('to_type as to_t', function ($join) {
                $join->on('a.type_id', '=', 'to_t.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('3'));
            })
            ->select(
                'a.*', 'a.employee_id',
                'c.photo',
                'c.employee_no',
                'c.position_id',
                'c.department_id',
                'c.mobile_no',
                'c.telephone_no',
                DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name"),
                // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN c.first_name ELSE dbo.ufn_DecryptString(c.first_name,'$app_key') END as first_name"),
                // Original decrypting employee name kept for reference:
                // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN c.first_name ELSE dbo.ufn_DecryptString(c.first_name,'$app_key') END as first_name"),
                DB::raw("c.first_name as first_name"),
                // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN c.last_name ELSE dbo.ufn_DecryptString(c.last_name,'$app_key') END as last_name"),
                DB::raw("c.last_name as last_name"),
                // DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN c.middle_name ELSE dbo.ufn_DecryptString(c.middle_name,'$app_key') END as middle_name"),
                DB::raw("c.middle_name as middle_name"),
                'pos.name as position',
                'dept.name as department',
                // Original decrypting cancelled_by name kept for reference:
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) END as cancelled_by"),
                DB::raw("CONCAT(b.first_name,' ',b.last_name) as cancelled_by"),
                DB::raw("ISNULL(pb.name,'') as cancelled_by_position")
            )
            ->where(['is_cancel' => true])
            ->orderby('created_at', 'asc')
            ->get();


        // Get Budget Officer (employee with position_id = 16)
        $budgetOfficer = DB::table('employees')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->select(
                // Original decrypting budget officer name kept for reference:
                // DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN ... ELSE ... dbo.ufn_DecryptString(...) ... END as name"),
                DB::raw("UPPER(CONCAT(employees.first_name,' ',
                            CASE WHEN ISNULL(employees.middle_name,'') = '' THEN '' ELSE CONCAT(SUBSTRING(employees.middle_name,1,1),'. ') END,
                            employees.last_name)) as name"),
                'positions.name as position'
            )
            ->where('employees.position_id', 16)
            ->where('employees.active', true)
            ->first();

        return $this->successResponse([
            'ForapprovalEmployeeOB' => $ForapprovalEmployeeOB,
            'ApprovedEmployeeOB' => $ApprovedEmployeeOB,
            'DisapprovedEmployeeOB' => $DisapprovedEmployeeOB,
            'CancelledEmployeeOB' => $CancelledEmployeeOB,
            'ExpiredEmployeeOB' => $ExpiredEmployeeOB,
            'budget_officer' => $budgetOfficer ? $budgetOfficer->name : '',
            'budget_officer_position' => $budgetOfficer ? $budgetOfficer->position : ''
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
            // Ensure we only pick ONE approver_headers row for type_id=5 per employee,
            // otherwise multiple approver_details rows can duplicate the OB row and the view will render the "wrong" first row.
            ->leftJoin(DB::raw("(
                SELECT x.employee_id, ah2.approver_id_1
                FROM (
                    SELECT ad.employee_id, ad.approver_id,
                           ROW_NUMBER() OVER (PARTITION BY ad.employee_id ORDER BY ah.id DESC) as rn
                    FROM approver_details ad
                    INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 5
                ) x
                INNER JOIN approver_headers ah2 ON ah2.id = x.approver_id
                WHERE x.rn = 1
            ) obappr"), 'obappr.employee_id', '=', 'a.employee_id')
            ->leftJoin('employees as appr1', 'appr1.id', '=', 'obappr.approver_id_1')
            ->select(
                'a.id',
                'a.employee_id',
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
                // Supervisor (approver_id_1 for type_id=5) in format: First Name M.I. Last Name
                // Original decrypting approved_by_name kept for reference:
                // DB::raw("CASE WHEN ISNULL(appr1.is_encrypted,0) = 0 THEN ... ELSE RTRIM([dbo].[ufn_DecryptString](appr1.first_name,'$app_key')) ... END as approved_by_name"),
                DB::raw("CASE WHEN ISNULL(appr1.middle_name,'') = '' THEN CONCAT(appr1.first_name,' ',appr1.last_name) ELSE CONCAT(appr1.first_name,' ',UPPER(SUBSTRING(appr1.middle_name,1,1)),'. ',appr1.last_name) END as approved_by_name"),
                // Employee name in format: First Name M.I. Last Name
                // Original decrypting employee name kept for reference:
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN ... ELSE RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')) ... END as name"),
                DB::raw("CASE WHEN ISNULL(b.middle_name,'') = '' THEN UPPER(CONCAT(b.first_name,' ',b.last_name)) ELSE UPPER(CONCAT(b.first_name,' ',UPPER(SUBSTRING(b.middle_name,1,1)),'. ',b.last_name)) END as name"),
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
                'a.ob_type',
                'a.type_id',
                'c.name as position',
                'd.name as branch', // Change 'br' to 'd' to match alias
                'a.client',
                'a.purpose',
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
                DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as name"),
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

        // Travel Authority assets (logo and footer), passed as base64 (if available)
        $taLogo = null;
        $taFooter = null;

        $taLogoPath = resource_path('img/TA-logo.png');
        if (file_exists($taLogoPath)) {
            $taLogo = base64_encode(file_get_contents($taLogoPath));
        }

        $taFooterPath = resource_path('img/TA-footer.png');
        if (file_exists($taFooterPath)) {
            $taFooter = base64_encode(file_get_contents($taFooterPath));
        }

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

        // Travel Authority reports by type:
        // - type_id = 1: Personal TA (narrative format)
        // - type_id = 2: Annex F TA (tabular format)
        if ($ob->isNotEmpty() && (int) $ob[0]->ob_type === 2) {
            if ((int) $ob[0]->type_id === 1) {
                // Personal Travel Authority
                $pdf = PDF::loadView(
                    'official_business_application.travel_authority_personal_report',
                    compact('ob', 'footer', 'taLogo', 'taFooter')
                )->setOptions(['defaultFont' => 'sans-serif']);
            } elseif ((int) $ob[0]->type_id === 2) {
                // Annex F Travel Authority
                $pdf = PDF::loadView(
                    'official_business_application.travel_authority_report',
                    compact('ob', 'footer', 'taLogo', 'taFooter')
                )->setOptions(['defaultFont' => 'sans-serif']);
            } else {
                // Fallback to legacy unofficial report
                $pdf = PDF::loadView(
                    'official_business_application.unofficial_business_report',
                    compact('ob', 'footer', 'image')
                )->setOptions(['defaultFont' => 'sans-serif']);
            }
        } else {
            // Non-Travel Authority: legacy unofficial report
            $pdf = PDF::loadView(
                'official_business_application.unofficial_business_report',
                compact('ob', 'footer', 'image')
            )->setOptions(['defaultFont' => 'sans-serif']);
        }

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
                'a.ob_type',
                'a.type_id',
                'c.name as position',
                'd.name as branch', // Change 'br' to 'd' to match alias
                'a.client',
                'a.purpose',
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
                DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as name"),
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

        // Travel Order assets (logo and footer), passed as base64 (if available)
        $taLogo = null;
        $taFooter = null;

        $taLogoPath = resource_path('img/TA-logo.png');
        if (file_exists($taLogoPath)) {
            $taLogo = base64_encode(file_get_contents($taLogoPath));
        }

        $taFooterPath = resource_path('img/TA-footer.png');
        if (file_exists($taFooterPath)) {
            $taFooter = base64_encode(file_get_contents($taFooterPath));
        }

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

        // Travel Order templates:
        // - LDSD Travel Order when:
        //   ob_type = 3 AND (type_id = 2 OR branch contains 'Learning and Development Support Division' OR 'LDSD')
        // - Travel Order (ob_type = 3, type_id = 3): uses resources/views/travel_order/travel_order.blade.php (new layout)
        // - Standard Annex A Travel Order when ob_type = 3 AND type_id = 4
        // - All other Travel Orders fall back to legacy order_business_report.
        $paperSize = 'legal';
        if ($ob->isNotEmpty() && (int) $ob[0]->ob_type === 3) {
            $branchName = strtolower($ob[0]->branch ?? '');
            $isLdsdBranch = strpos($branchName, 'learning and development support division') !== false
                || strpos($branchName, 'ldsd') !== false;

            if ((int) $ob[0]->type_id === 2 || $isLdsdBranch) {
                // LDSD Travel Order
                $pdf = PDF::loadView(
                    'official_business_application.travel_order_LDSD_report',
                    compact('ob', 'footer', 'taLogo', 'taFooter')
                )->setOptions(['defaultFont' => 'sans-serif']);
            } elseif ((int) $ob[0]->type_id === 3) {
                // Travel Order (type_id = 3): use the new travel_order template at resources/views/travel_order/travel_order.blade.php
                $record = $ob[0];

                $date = !empty($record->date)
                    ? \Carbon\Carbon::parse($record->date)->format('F d, Y')
                    : '';
                $fromDate = !empty($record->date_time_from)
                    ? \Carbon\Carbon::parse($record->date_time_from)->format('F d, Y')
                    : '';
                $toDate = !empty($record->date_time_to)
                    ? \Carbon\Carbon::parse($record->date_time_to)->format('F d, Y')
                    : '';
                $travel_dates = trim($fromDate . ($toDate && $toDate !== $fromDate ? ' - ' . $toDate : ''));

                $employee_name = $record->name ?? '';
                $designation = trim(($record->position ?? '') . (!empty($record->branch) ? (' / ' . $record->branch) : ''));
                $destination = $record->client ?? '';
                $purpose = $record->purpose ?? 'an approved personal leave';

                // Simple pronoun logic; default to 'her'
                $pronoun = 'her';

                // Signatories
                $budget_officer = $record->recommending_approval ?? '';
                $budget_officer_position = $record->recommending_position ?? 'Budget Officer';
                $approver_name = $record->approver ?? '';
                $approver_position = $record->approver_position ?? '';
                $date_of_approval = '';

                // Resolve approver dynamically from approver_headers / approver_details (type_id = 2)
                try {
                    $toApprover = \DB::table('official_business_applications as obx')
                        ->join('approver_details as ad', 'ad.employee_id', '=', 'obx.employee_id')
                        ->join('approver_headers as ah', function ($join) {
                            $join->on('ah.id', '=', 'ad.approver_id')
                                 ->where('ah.type_id', 2); // Official Business approver setup
                        })
                        ->join('employees as emp', 'ah.approver_id_1', '=', 'emp.id')
                        ->leftJoin('positions as pos', 'emp.position_id', '=', 'pos.id')
                        ->where('obx.id', $record->id)
                        ->orderBy('ah.id', 'desc')
                        ->select(
                            'emp.first_name',
                            'emp.middle_name',
                            'emp.last_name',
                            'pos.name as position'
                        )
                        ->first();

                    if ($toApprover) {
                        $first = trim($toApprover->first_name ?? '');
                        $last = trim($toApprover->last_name ?? '');
                        $middle = trim($toApprover->middle_name ?? '');
                        $middleInitial = $middle !== '' ? mb_substr($middle, 0, 1, 'UTF-8') . '.' : '';
                        $nameParts = array_filter([$first, $middleInitial, $last]);
                        $approver_name = implode(' ', $nameParts) ?: $approver_name;
                        $approver_position = $toApprover->position ?: $approver_position;
                    }
                } catch (\Throwable $e) {
                    // Fallback silently to existing $approver_name / $approver_position
                }

                // Header/footer images for this layout
                $headerImage = null;
                $footerImage = null;
                $headerPath = public_path('dist/img/travel_order_header.png');
                if (file_exists($headerPath)) {
                    $headerImage = base64_encode(file_get_contents($headerPath));
                }
                $footerPath = public_path('dist/img/travel_order_footer.png');
                if (file_exists($footerPath)) {
                    $footerImage = base64_encode(file_get_contents($footerPath));
                }

                // Memo info
                $memo_number = '____';
                $series_year = !empty($record->date)
                    ? \Carbon\Carbon::parse($record->date)->format('Y')
                    : date('Y');

                $pdf = PDF::loadView(
                    'travel_order.travel_order',
                    compact(
                        'memo_number',
                        'series_year',
                        'date',
                        'employee_name',
                        'designation',
                        'travel_dates',
                        'destination',
                        'purpose',
                        'pronoun',
                        'budget_officer',
                        'budget_officer_position',
                        'approver_name',
                        'approver_position',
                        'date_of_approval',
                        'headerImage',
                        'footerImage'
                    )
                )->setOptions(['defaultFont' => 'sans-serif']);
                $paperSize = 'A4';
            } elseif ((int) $ob[0]->type_id === 4) {
                // Standard Annex A Travel Order
                $pdf = PDF::loadView(
                    'official_business_application.travel_order_report',
                    compact('ob', 'footer', 'taLogo', 'taFooter')
                )->setOptions(['defaultFont' => 'sans-serif']);
            } else {
                // Other Travel Orders
                $pdf = PDF::loadView(
                    'official_business_application.order_business_report',
                    compact('ob', 'footer', 'image', 'image2')
                )->setOptions(['defaultFont' => 'sans-serif']);
            }
        } else {
            // Non-Travel Order: legacy order_business_report
            $pdf = PDF::loadView(
                'official_business_application.order_business_report',
                compact('ob', 'footer', 'image', 'image2')
            )->setOptions(['defaultFont' => 'sans-serif']);
        }

        $pdf->setPaper($paperSize);
        $pdf->output();
        return $pdf->stream();
    }

    /**
     * Download Pass Slip (Official Business Report) as Word document
     */
    public function downloadOfficialBusinessDocx($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Retrieve the same data as print
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
                    DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as name"),
                    'c.name as position'
                )
                ->where('a.id', $id)
                ->first();

            if (!$ob) {
                return response()->json(['error' => 'Official Business record not found.'], 404);
            }

            // Format data
            $date = $ob->date ? \Carbon\Carbon::parse($ob->date)->format('M d, Y') : '';
            $dateTimeFrom = $ob->date_time_from ? \Carbon\Carbon::parse($ob->date_time_from)->format('M d, Y h:i A') : '';
            $dateTimeTo = $ob->date_time_to ? \Carbon\Carbon::parse($ob->date_time_to)->format('M d, Y h:i A') : '';
            $purpose = $ob->purpose ?? '';
            $recommendingApproval = $ob->recommending_approval ?? '';
            $recommendingPosition = $ob->recommending_position ?? '';
            $approver = $ob->approver ?? '';
            $obType = (int)($ob->ob_type ?? 0);
            
            // Checkbox logic: Official checked if ob_type == 5, Personal checked if ob_type == 1
            $officialChecked = ($obType == 5);
            $personalChecked = ($obType == 1);

            // Build DOCX with PhpWord
            $phpWord = new PhpWord();

            // A4 Portrait section with margins
            $section = $phpWord->addSection([
                'marginTop' => 720,   // 0.5 inch
                'marginRight' => 720,
                'marginBottom' => 720,
                'marginLeft' => 720
            ]);

            // Helper function to create one Pass Slip form
            $createPassSlip = function($section, $date, $purpose, $officialChecked, $personalChecked, $dateTimeFrom, $dateTimeTo, $recommendingApproval, $recommendingPosition, $approver) {
                // Header
                $section->addText('Republic of the Philippines', [], [
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                    'spaceAfter' => 0
                ]);
                $section->addText(strtoupper(CompanyHelper::getName()), ['bold' => true], [
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                    'spaceAfter' => 0
                ]);
                $section->addText(CompanyHelper::getAddress(), [], [
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                    'spaceAfter' => 240
                ]);

                // Title - centered
                $section->addText('PASS SLIP', ['bold' => true, 'size' => 16], [
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                    'spaceAfter' => 0
                ]);

                // Date section - right aligned below title
                $dateTable = $section->addTable();
                $dateRow = $dateTable->addRow();
                // Left spacer cell
                $dateRow->addCell(7000, ['valign' => 'top'])->addText('');
                // Right cell with Date label and date
                $dateCell = $dateRow->addCell(3000, ['valign' => 'top']);
                $dateTextRun = $dateCell->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
                $dateTextRun->addText('Date:', []);
                $dateTextRun->addText($date, ['underline' => 'single']);

                $section->addTextBreak(1);

                // Signature over Printed Name with border-top effect
                $signatureTable = $section->addTable();
                $signatureTable->addRow();
                $signatureCell = $signatureTable->addCell(4000, [
                    'borderTopSize' => 6,
                    'borderTopColor' => '000000',
                    'valign' => 'bottom'
                ]);
                $signatureCell->addText('Signature over Printed Name:', [], ['spaceAfter' => 0]);


                // Reason/s
                $reasonTextRun = $section->addTextRun();
                $reasonTextRun->addText('Reason/s: ', []);
                $reasonTextRun->addText($purpose, ['underline' => 'single']);


                // Checkboxes section
                $checkboxTable = $section->addTable();
                $checkboxTable->addRow();
                
                // Official checkbox
                $officialCell = $checkboxTable->addCell(2000, ['valign' => 'top']);
                $officialTextRun = $officialCell->addTextRun();
                $officialTextRun->addText('Official', ['bold' => true, 'size' => 14]);
                $officialTextRun->addText($officialChecked ? '☑' : '☐', ['size' => 12]);

                // Spacer cell
                $checkboxTable->addCell(3000, ['valign' => 'top'])->addText('');

                // Personal checkbox
                $personalCell = $checkboxTable->addCell(2000, ['valign' => 'top']);
                $personalTextRun = $personalCell->addTextRun();
                $personalTextRun->addText('Personal', ['bold' => true, 'size' => 14]);
                $personalTextRun->addText($personalChecked ? '☑' : '☐', ['size' => 12]);

                // Time of Departure and Expected Time of Arrival
                $timeTable = $section->addTable();
                $timeTable->addRow();
                
                $departureCell = $timeTable->addCell(5000, ['valign' => 'top']);
                $departureTextRun = $departureCell->addTextRun();
                $departureTextRun->addText('Time of Departure: ', []);
                $departureTextRun->addText($dateTimeFrom, ['underline' => 'single']);

                $arrivalCell = $timeTable->addCell(5000, ['valign' => 'top']);
                $arrivalTextRun = $arrivalCell->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
                $arrivalTextRun->addText('Expected Time Of Arrival: ', []);
                $arrivalTextRun->addText($dateTimeTo, ['underline' => 'single']);

                // Recommending Approval
                $section->addText('Recommending Approval: ', [], ['spaceAfter' => 0]);
                $recommendingTextRun = $section->addTextRun();
                $recommendingTextRun->addText($recommendingApproval, ['underline' => 'single']);
                $section->addText($recommendingPosition, [], ['spaceAfter' => 240]);

                // Approved section
                $approvedTextRun = $section->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
                $approvedTextRun->addText('Approved:', []);
                $approvedTextRun->addTextBreak();
                $approvedTextRun->addTextBreak();
                $approvedTextRun->addText($approver, ['underline' => 'single']);

                // Note
                $section->addText('Note: if the purpose is personal in nature it is considered as under time and it shall be deducted outright in the employee leave ledger card', ['size' => 10], [
                    'spaceAfter' => 480
                ]);
            };

            // Create 2 Pass Slip forms
            $createPassSlip($section, $date, $purpose, $officialChecked, $personalChecked, $dateTimeFrom, $dateTimeTo, $recommendingApproval, $recommendingPosition, $approver);
            $createPassSlip($section, $date, $purpose, $officialChecked, $personalChecked, $dateTimeFrom, $dateTimeTo, $recommendingApproval, $recommendingPosition, $approver);

            // Save document
            $filename = 'Pass_Slip_' . $id . '.docx';
            $tempFile = tempnam(sys_get_temp_dir(), 'pass_slip_');
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate Pass Slip Word document.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download Pass Slip (Official Business Report) as Excel document
     */
    public function downloadOfficialBusinessExcel($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Retrieve the same data as print
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
                    DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as name"),
                    'c.name as position'
                )
                ->where('a.id', $id)
                ->first();

            if (!$ob) {
                return response()->json(['error' => 'Official Business record not found.'], 404);
            }

            // Format data
            $date = $ob->date ? \Carbon\Carbon::parse($ob->date)->format('M d, Y') : '';
            $dateTimeFrom = $ob->date_time_from ? \Carbon\Carbon::parse($ob->date_time_from)->format('M d, Y h:i A') : '';
            $dateTimeTo = $ob->date_time_to ? \Carbon\Carbon::parse($ob->date_time_to)->format('M d, Y h:i A') : '';
            $purpose = $ob->purpose ?? '';
            $recommendingApproval = $ob->recommending_approval ?? '';
            $recommendingPosition = $ob->recommending_position ?? '';
            $approver = $ob->approver ?? '';
            $obType = (int)($ob->ob_type ?? 0);
            
            // Checkbox logic: Official checked if ob_type == 5, Personal checked if ob_type == 1
            $officialChecked = ($obType == 5);
            $personalChecked = ($obType == 1);

            // Build Excel with PhpSpreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set page orientation to portrait
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
            $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(40);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(40);

            // Helper function to create one Pass Slip form
            $createPassSlip = function($sheet, $startRow, $date, $purpose, $officialChecked, $personalChecked, $dateTimeFrom, $dateTimeTo, $recommendingApproval, $recommendingPosition, $approver) {
                $row = $startRow;

                // Header
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->setCellValue('A' . $row, 'Republic of the Philippines');
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;

                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->setCellValue('A' . $row, strtoupper(CompanyHelper::getName()));
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;

                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->setCellValue('A' . $row, CompanyHelper::getAddress());
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
                $row += 2;

                // Title - centered
                $sheet->mergeCells('A' . $row . ':B' . $row);
                $sheet->setCellValue('A' . $row, 'PASS SLIP');
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;

                // Date - positioned below title on the right, to mirror DOCX layout
                $sheet->setCellValue('C' . $row, 'Date:');
                $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue('D' . $row, $date);
                $sheet->getStyle('D' . $row)->getFont()->setUnderline(true);
                $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $row += 2;

                // Signature over Printed Name
                $sheet->setCellValue('A' . $row, 'Signature over Printed Name:');
                $sheet->getStyle('A' . $row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
                $row += 2;

                // Reason/s
                $sheet->setCellValue('A' . $row, 'Reason/s:');
                $sheet->setCellValue('B' . $row, $purpose);
                $sheet->getStyle('B' . $row)->getFont()->setUnderline(true);
                $row += 2;

                // Checkboxes
                $sheet->setCellValue('A' . $row, 'Official');
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $sheet->setCellValue('A' . ($row + 1), $officialChecked ? '☑' : '☐');
                
                $sheet->setCellValue('C' . $row, 'Personal');
                $sheet->getStyle('C' . $row)->getFont()->setBold(true)->setSize(14);
                $sheet->setCellValue('C' . ($row + 1), $personalChecked ? '☑' : '☐');
                $row += 3;

                // Time of Departure and Expected Time of Arrival
                $sheet->setCellValue('A' . $row, 'Time of Departure:');
                $sheet->setCellValue('B' . $row, $dateTimeFrom);
                $sheet->getStyle('B' . $row)->getFont()->setUnderline(true);
                
                $sheet->setCellValue('C' . $row, 'Expected Time Of Arrival:');
                $sheet->setCellValue('D' . $row, $dateTimeTo);
                $sheet->getStyle('D' . $row)->getFont()->setUnderline(true);
                $row += 2;

                // Recommending Approval
                $sheet->setCellValue('A' . $row, 'Recommending Approval:');
                $row++;
                $sheet->setCellValue('A' . $row, $recommendingApproval);
                $sheet->getStyle('A' . $row)->getFont()->setUnderline(true);
                $row++;
                $sheet->setCellValue('A' . $row, $recommendingPosition);
                $row += 2;

                // Approved
                $sheet->setCellValue('C' . ($row - 2), 'Approved:');
                $sheet->getStyle('C' . ($row - 2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue('C' . $row, $approver);
                $sheet->getStyle('C' . $row)->getFont()->setUnderline(true);
                $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $row += 2;

                // Note
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->setCellValue('A' . $row, 'Note: if the purpose is personal in nature it is considered as under time and it shall be deducted outright in the employee leave ledger card');
                $sheet->getStyle('A' . $row)->getFont()->setSize(10);
                $row += 3;

                return $row;
            };

            // Create 2 Pass Slip forms
            $nextRow = $createPassSlip($sheet, 1, $date, $purpose, $officialChecked, $personalChecked, $dateTimeFrom, $dateTimeTo, $recommendingApproval, $recommendingPosition, $approver);
            $createPassSlip($sheet, $nextRow, $date, $purpose, $officialChecked, $personalChecked, $dateTimeFrom, $dateTimeTo, $recommendingApproval, $recommendingPosition, $approver);

            // Save document
            $filename = 'Pass_Slip_' . $id . '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'pass_slip_');
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate Pass Slip Excel document.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadTravelAuthorityPersonalDocx($id)
    {
        $app_key = env("APP_KEY", "");

        try {
            // Get OB data for Personal Travel Authority (ob_type = 2, type_id = 1)
            $ob = DB::table('official_business_applications as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->join('departments as e', 'b.department_id', '=', 'e.id')
                ->leftJoin('branches as d', 'd.id', '=', 'b.branch_id')
                ->select(
                    'a.id',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.type_id',
                    'c.name as position',
                    'd.name as branch',
                    'a.client',
                    'a.ob_type',
                    'e.name as department',
                    DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as name")
                )
                ->where('a.id', $id)
                ->where('a.ob_type', 2)
                ->where('a.type_id', 1)
                ->first();

            if (!$ob) {
                return response()->json(['error' => 'Personal Travel Authority not found'], 404);
            }

            // Format dates
            try {
                $fromDate = ($ob->date_time_from && !empty(trim($ob->date_time_from))) 
                    ? \Carbon\Carbon::parse($ob->date_time_from)->format('F d, Y') 
                    : '';
                $toDate = ($ob->date_time_to && !empty(trim($ob->date_time_to))) 
                    ? \Carbon\Carbon::parse($ob->date_time_to)->format('F d, Y') 
                    : '';
                $inclusive = trim($fromDate . ($toDate && $toDate !== $fromDate ? ' – ' . $toDate : ''));
                if (empty($inclusive)) {
                    $inclusive = '';
                }
            } catch (\Exception $e) {
                $inclusive = '';
            }
            
            $destination = !empty(trim($ob->client ?? '')) ? trim($ob->client) : '';
            
            try {
                $date = ($ob->date && !empty(trim($ob->date))) 
                    ? \Carbon\Carbon::parse($ob->date)->format('d F Y') 
                    : '';
            } catch (\Exception $e) {
                $date = '';
            }

            // Get TA logo if exists
            $taLogoPath = resource_path('img/TA-logo.png');
            $taLogo = null;
            if (file_exists($taLogoPath)) {
                $taLogo = $taLogoPath;
            }

            // Build DOCX with PhpWord
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(12);

            $section = $phpWord->addSection([
                'marginTop' => 1440,    // 1 inch = 1440 twips
                'marginRight' => 1440,  // 1 inch
                'marginBottom' => 720,  // 0.5 inch
                'marginLeft' => 1440    // 1 inch
            ]);

            // TA Logo
            if ($taLogo) {
                try {
                    $section->addImage($taLogo, [
                        'width' => null,
                        'height' => 80,
                        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
                    ]);
                    $section->addTextBreak(1);
                } catch (\Exception $e) {
                    // If image fails, continue without it
                }
            }

            // Date and memo number
            if (!empty($date)) {
                $section->addText($date, [], ['spaceAfter' => 0]);
            }
            $section->addTextBreak(1);
            $section->addText('MEMORANDUM ORDER NO. _________', [], ['spaceAfter' => 0]);
            $seriesYear = \Carbon\Carbon::now()->year;
            $section->addText('Series of ' . $seriesYear, [], ['spaceAfter' => 0]);

            // Title: TRAVEL AUTHORITY
            $section->addTextBreak(2);
            $section->addText('TRAVEL AUTHORITY', ['bold' => true, 'size' => 12], [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter' => 240
            ]);

            // Numbered list items
            $section->addTextBreak(1);
            
            // Item 1
            $item1TextRun = $section->addTextRun(['spaceAfter' => 240, 'indentation' => ['left' => 360, 'hanging' => 360]]);
            $item1TextRun->addText('1.  ', []);
            $item1TextRun->addText('This is to authorize ', []);
            $item1TextRun->addText($ob->name ?? '', ['bold' => true]);
            $item1TextRun->addText(', ' . ($ob->position ?? '') . ' of the DTI – ' . CompanyHelper::getName() . ' to travel to ', []);
            $item1TextRun->addText($destination, ['bold' => true]);
            $item1TextRun->addText(' on ', []);
            $item1TextRun->addText($inclusive, ['bold' => true]);
            $item1TextRun->addText(' (inclusive of travel time) while on approved personal leave.', []);

            // Item 2
            $item2TextRun = $section->addTextRun(['spaceAfter' => 240, 'indentation' => ['left' => 360, 'hanging' => 360]]);
            $item2TextRun->addText('2.  ', []);
            $item2TextRun->addText('It is understood that the above-mentioned personnel will shoulder all expenses to be incurred during the travel, thus relieving the Department of any financial obligations.', []);

            // Item 3
            $item3TextRun = $section->addTextRun(['spaceAfter' => 240, 'indentation' => ['left' => 360, 'hanging' => 360]]);
            $item3TextRun->addText('3.  ', []);
            $item3TextRun->addText('In the interest of service, ', []);
            $item3TextRun->addText($ob->name ?? 'the personnel', []);
            $item3TextRun->addText(' will turn over whatever pending matters and assignments to her/his colleague to ensure smooth functioning of the unit during her/his absence.', []);

            // Item 4
            $item4TextRun = $section->addTextRun(['spaceAfter' => 480, 'indentation' => ['left' => 360, 'hanging' => 360]]);
            $item4TextRun->addText('4.  ', []);
            $item4TextRun->addText('The travel documentation is being undertaken in accordance with the provision of Department Order 10-57, DTI Foreign Travel Policy.', []);

            // Signatory
            $section->addTextBreak(2);
            $section->addText('CLARE MARI S. TORRALBA', ['bold' => true], ['spaceAfter' => 0]);
            $section->addText('Executive Director', [], ['spaceAfter' => 0]);

            // Save document
            $filename = 'Travel_Authority_Personal_' . $id . '.docx';
            $tempFile = tempnam(sys_get_temp_dir(), 'ta_personal_');
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Download Travel Authority (Annex F) as Word (DOCX)
     */
    public function downloadTravelAuthorityDocx($id)
    {
        $app_key = env("APP_KEY", "");

        // Get OB data with same query as printunofficial, plus approver position from positions table
        $ob = DB::table('official_business_applications as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->join('positions as c', 'b.position_id', '=', 'c.id')
            ->join('departments as e', 'b.department_id', '=', 'e.id')
            ->leftJoin('branches as d', 'd.id', '=', 'b.branch_id')
            ->leftJoin('approver_details as ad', 'ad.employee_id', '=', 'a.employee_id')
            ->leftJoin('approver_headers as ah', function($join) {
                $join->on('ah.id', '=', 'ad.approver_id')
                     ->where('ah.type_id', '=', 2) // Official Business type
                     ->whereRaw('ah.department_id = b.department_id')
                     ->whereRaw('(ah.branch_id = b.branch_id OR ah.branch_id IS NULL OR ah.branch_id = 0)')
                     ->whereRaw('(ah.division_id = b.division_id OR ah.division_id IS NULL OR ah.division_id = 0)')
                     ->whereRaw('(ah.section_id = b.section_id OR ah.section_id IS NULL OR ah.section_id = 0)');
            })
            ->leftJoin('employees as approver_emp', 'ah.approver_id_2', '=', 'approver_emp.id')
            ->leftJoin('positions as approver_pos', 'approver_emp.position_id', '=', 'approver_pos.id')
            ->select(
                'a.id',
                'a.date',
                'a.date_time_from',
                'a.date_time_to',
                'a.type_id',
                'c.name as position',
                'd.name as branch',
                'a.client',
                'a.purpose',
                'a.ob_type',
                'a.funds',
                'a.recommending_approval',
                'a.approver',
                'a.recommending_position',
                'e.name as department',
                'approver_pos.name as approver_position',
                DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as name")
            )
            ->where('a.id', $id)
            ->where('a.ob_type', 2)
            ->where('a.type_id', 2)
            ->first();

        if (!$ob) {
            return response()->json(['error' => 'Travel Authority (Annex F) not found'], 404);
        }

        // Format dates - handle null/empty values
        try {
            $fromDate = ($ob->date_time_from && !empty(trim($ob->date_time_from))) 
                ? \Carbon\Carbon::parse($ob->date_time_from)->format('F d, Y') 
                : '';
            $toDate = ($ob->date_time_to && !empty(trim($ob->date_time_to))) 
                ? \Carbon\Carbon::parse($ob->date_time_to)->format('F d, Y') 
                : '';
            $inclusive = trim($fromDate . ($toDate && $toDate !== $fromDate ? ' - ' . $toDate : ''));
            if (empty($inclusive)) {
                $inclusive = '';
            }
        } catch (\Exception $e) {
            $inclusive = '';
        }
        
        $destination = !empty(trim($ob->client ?? '')) ? trim($ob->client) : '';
        $purpose = !empty(trim($ob->purpose ?? '')) ? trim($ob->purpose) : '';
        $funds = !empty(trim($ob->funds ?? '')) ? trim($ob->funds) : '';
        
        try {
            $date = ($ob->date && !empty(trim($ob->date))) 
                ? \Carbon\Carbon::parse($ob->date)->format('F d, Y') 
                : '';
        } catch (\Exception $e) {
            $date = '';
        }

        // Get TA logo if exists
        $taLogoPath = resource_path('img/TA-logo.png');
        $taLogo = null;
        if (file_exists($taLogoPath)) {
            $taLogo = $taLogoPath;
        }

        // Build DOCX with PhpWord
        $phpWord = new PhpWord();

        // A4 Portrait section with margins: top 1in, right 1in, bottom 0.5in, left 1in
        $section = $phpWord->addSection([
            'marginTop' => 1440,  // 1 inch = 1440 twips
            'marginRight' => 1440,
            'marginBottom' => 720, // 0.5 inch = 720 twips
            'marginLeft' => 1440
        ]);

        // Header table: Logo on left, "ANNEX F" on right
        $headerTable = $section->addTable(['width' => 100 * 50]);
        $headerRow = $headerTable->addRow();
        $logoCell = $headerRow->addCell(7000, ['valign' => 'top']);
        if ($taLogo && file_exists($taLogo)) {
            try {
                $logoCell->addImage($taLogo, [
                    'width' => 80,
                    'height' => 80,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
                ]);
            } catch (\Exception $e) {
                // If image fails, just continue without it
            }
        }
        $annexCell = $headerRow->addCell(3000, ['valign' => 'top']);
        $annexCell->addText('ANNEX F', ['bold' => true, 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);

        // Memorandum Order section
        $section->addText('MEMORANDUM ORDER NO. ______', ['bold' => true], ['spaceAfter' => 0]);
        $section->addText('Series of _____', [], ['spaceAfter' => 0]);
        $dateTextRun = $section->addTextRun([
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT,
        ]);
        $dateTextRun->addText('Date: ', []);
        $dateTextRun->addText($date, ['underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE]);

        // Title: TRAVEL AUTHORITY
        $section->addText('TRAVEL AUTHORITY', ['bold' => true, 'size' => 12], [
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'spaceAfter' => 240
        ]);

        // Section A: Personnel table
        $section->addText('A.', ['bold' => true], ['spaceAfter' => 0]);
        $section->addText('The following official or personnel are hereby authorized to travel to the destination indicated opposite their respective names:', [], ['spaceAfter' => 120]);

        // Table for personnel
        $personnelTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'width' => 100 * 50
        ]);
        // Header row
        $headerRow = $personnelTable->addRow();
        $headerCell1 = $headerRow->addCell(4500, ['valign' => 'center']);
        $headerCell1->addText('Name', ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $headerCell1->addText('Designation/Official Station', ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $headerCell2 = $headerRow->addCell(2500, ['valign' => 'center']);
        $headerCell2->addText('Inclusive Dates of Travel', ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $headerCell3 = $headerRow->addCell(3000, ['valign' => 'center']);
        $headerCell3->addText('Destination', ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        
        // Data row
        $dataRow = $personnelTable->addRow();
        $nameCell = $dataRow->addCell(4500, ['valign' => 'top']);
        $nameCell->addText($ob->name ?? '', ['bold' => true], ['spaceAfter' => 0]);
        $nameCell->addText($ob->position ?? '', [], ['spaceAfter' => 0]);
        $nameCell->addText($ob->branch ?? $ob->department ?? '', [], ['spaceAfter' => 0]);
        $dateCell = $dataRow->addCell(2500, ['valign' => 'center']);
        $dateCell->addText($inclusive, [], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $destCell = $dataRow->addCell(3000, ['valign' => 'top']);
        $destCell->addText($destination, [], ['spaceAfter' => 0]);

        // Section B: Purpose
        $section->addText('B.', ['bold' => true], ['spaceAfter' => 0]);
        $purposeTextRun = $section->addTextRun(['spaceAfter' => 240]);
        $purposeTextRun->addText('Purpose of the Travel. The official or personnel are authorized to travel for the purpose of ', []);
        $purposeTextRun->addText($purpose, ['underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE]);
        $purposeTextRun->addText('.', []);

        // Section C: Allowable expenses
        $section->addText('C.', ['bold' => true], ['spaceAfter' => 0]);
        $section->addText('The allowable travel expenses based on Executive Order No. 77, s. 2019 and Department Order No. 25-63, s. 2025 is hereby authorized subject to availability of fund, and pertinent accounting, auditing, and procurement rules and regulations.', [], ['spaceAfter' => 240]);

        // Section D: Funds
        $section->addText('D.', ['bold' => true], ['spaceAfter' => 0]);
        $fundsTextRun = $section->addTextRun(['spaceAfter' => 480]);
        $fundsTextRun->addText('The allowable travel expenses shall be charged to the appropriation of ', []);
        $fundsTextRun->addText($funds, ['underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE]);
        $fundsTextRun->addText('.', []);

        // Funds Available section
        $section->addText('Funds Available:', ['bold' => true], ['spaceAfter' => 120]);
        $section->addText($ob->recommending_approval ?? '________________________', ['bold' => true], ['spaceAfter' => 0]);
        $section->addText('Budget Officer', [], ['spaceAfter' => 480]);

        // Approved section
        $section->addText('Approved:', ['bold' => true], ['spaceAfter' => 0]);
        $section->addText('(By Authority of the Secretary)', ['size' => 10], ['spaceAfter' => 240]);
        $section->addText($ob->approver ?? '________________________', ['bold' => true], ['spaceAfter' => 0]);
        $section->addText($ob->approver_position ?? 'Designation', [], ['spaceAfter' => 480]);

        // Date of Approval
        $approvalDateTextRun = $section->addTextRun(['spaceAfter' => 0]);
        $approvalDateTextRun->addText('Date of Approval: ', []);
        $approvalDateTextRun->addText($date, ['underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE]);

        // Footer image if exists
        $taFooterPath = resource_path('img/TA-footer.png');
        if (file_exists($taFooterPath)) {
            try {
                $section->addImage($taFooterPath, [
                    'width' => 500,
                    'height' => null,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                ]);
            } catch (\Exception $e) {
                // If footer image fails, just continue without it
            }
        }

        // Save to temp file
        try {
            $tempFile = tempnam(sys_get_temp_dir(), 'travel-authority-');
            if (!$tempFile) {
                throw new \Exception('Could not create temporary file');
            }
            
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            $downloadFileName = 'Travel_Authority_' . $ob->id . '.docx';

            return response()->download($tempFile, $downloadFileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
            return response()->json(['error' => 'Failed to generate Word document: ' . $e->getMessage()], 500);
        }
    }

        /**
     * Print Request for Pick-up (ob_type = 4)
     */
    public function printRequestForPickup($id)
    {
        $app_key = env("APP_KEY", "");

        $ob = DB::table('official_business_applications as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->select(
                'a.id',
                'a.date',
                'a.ob_type',
                DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as name")
            )
            ->where('a.id', $id)
            ->where('a.ob_type', 4)
            ->first();

        if (!$ob) {
            return response()->json(['error' => 'Request for Pick-up not found'], 404);
        }

        // Get request_for_pickup data
        $pickupData = DB::table('request_for_pickup as r')
            ->leftJoin('employees as e', 'r.requested_by_employee_id', '=', 'e.id')
            ->leftJoin('divisions as d', 'e.division_id', '=', 'd.id')
            ->select(
                'r.*',
                DB::raw("CASE WHEN ISNULL(e.middle_name,'') = '' THEN CONCAT(e.first_name,' ',e.last_name) ELSE CONCAT(e.first_name,' ',UPPER(SUBSTRING(e.middle_name,1,1)),'. ',e.last_name) END as requested_by_name"),
                'd.name as requested_by_division'
            )
            ->where('r.ob_id', $id)
            ->first();

        $pdf = PDF::loadView('official_business_application.request_for_pickup_report', compact('ob', 'pickupData'))
            ->setOptions(['defaultFont' => 'sans-serif']);
        $pdf->setPaper('A4', 'portrait');
        $pdf->output();
        return $pdf->stream();
    }

    /**
     * Download Request for Pick-up as Word (DOCX) - 2 forms per A4 page
     */
    public function downloadRequestForPickupDocx($id)
    {
        $app_key = env("APP_KEY", "");

        // Reuse the same OB query as PDF print
        $ob = DB::table('official_business_applications as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->select(
                'a.id',
                'a.date',
                'a.ob_type',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        UPPER(CONCAT(b.first_name,' ',b.last_name))
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                    END as name")
            )
            ->where('a.id', $id)
            ->where('a.ob_type', 4)
            ->first();

        if (!$ob) {
            return response()->json(['error' => 'Request for Pick-up not found'], 404);
        }

        // Same pickup data (with requested_by employee join) as PDF print
        $pickupData = DB::table('request_for_pickup as r')
            ->leftJoin('employees as e', 'r.requested_by_employee_id', '=', 'e.id')
            ->leftJoin('divisions as d', 'e.division_id', '=', 'd.id')
            ->select(
                'r.*',
                DB::raw("CASE WHEN ISNULL(e.middle_name,'') = '' THEN CONCAT(e.first_name,' ',e.last_name) ELSE CONCAT(e.first_name,' ',UPPER(SUBSTRING(e.middle_name,1,1)),'. ',e.last_name) END as requested_by_name"),
                'd.name as requested_by_division'
            )
            ->where('r.ob_id', $id)
            ->first();

        // Resolve requested by display exactly like the PDF
        $requestedByResolved = null;
        if ($pickupData) {
            if (!empty(trim($pickupData->requested_by_name ?? ''))) {
                $requestedByResolved = $pickupData->requested_by_name . ' / ' . $pickupData->requested_by_division;
            } elseif (!empty(trim($pickupData->requested_by ?? ''))) {
                $requestedByResolved = $pickupData->requested_by;
            }
        }

        // Build DOCX with PhpWord
        $phpWord = new PhpWord();
        
        // A4 Portrait section with tighter margins
        $section = $phpWord->addSection([
            'marginTop' => 400,
            'marginRight' => 600,
            'marginBottom' => 400,
            'marginLeft' => 600
        ]);

        // Register table style once (outside closure)
        $phpWord->addTableStyle('ContainerStyle', [
            'cellMargin' => 80,
            'width' => 100 * 50 // 100% width
        ]);

        // Helper function to create one form
        $createForm = function($section, $pickupData, $requestedByResolved, $ob) {
            // Reference number (top right)
            $header = $section->addTable();
            $header->addRow();
            $header->addCell(7000, ['valign' => 'bottom'])->addText('');
            $header->addCell(3000, ['valign' => 'bottom'])->addText('AFM-PER.FRM/REV 00/15-16-14', ['size' => 7], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            
            // Main container table with border only on outer edges
            
            $container = $section->addTable('ContainerStyle');
            
            // Title row - only outer borders (top, left, right), no bottom border
            $row = $container->addRow();
            $cell = $row->addCell(10000, [
                'gridSpan' => 2,
                'valign' => 'bottom',
                'borderTopSize' => 6,
                'borderTopColor' => '000000',
                'borderBottomSize' => 1,
                'borderBottomColor' => 'FFFFFF', // White border to hide it
                'borderLeftSize' => 6,
                'borderLeftColor' => '000000',
                'borderRightSize' => 6,
                'borderRightColor' => '000000'
            ]);
            $cell->addText('PHILIPPINE TRADE TRAINING CENTER', ['bold' => true, 'size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $cell->addText('REQUEST FOR PICK-UP', ['bold' => true, 'size' => 10], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            
            // From, Company, Address, Contact - Left column | Date - Right column
            // Use two cells but ensure no border between them
            $row = $container->addRow();
            $leftCell = $row->addCell(7000, [
                'valign' => 'bottom',
                'borderTopSize' => 1,
                'borderTopColor' => 'FFFFFF', // White border to hide it
                'borderBottomSize' => 1,
                'borderBottomColor' => 'FFFFFF', // White border to hide it
                'borderLeftSize' => 6,
                'borderLeftColor' => '000000',
                'borderRightSize' => 1,
                'borderRightColor' => 'FFFFFF' // White border to hide it
            ]);
            $leftCell->addText('From: ' . ($pickupData->from ?? ''), ['size' => 9]);
            $leftCell->addText('Company: ' . ($pickupData->company ?? ''), ['size' => 9]);
            $leftCell->addText('Address: ' . ($pickupData->address ?? ''), ['size' => 9]);
            $leftCell->addText('Contact No: ' . ($pickupData->contact_no ?? ''), ['size' => 9]);
            
            $rightCell = $row->addCell(3000, [
                'valign' => 'bottom',
                'borderTopSize' => 1,
                'borderTopColor' => 'FFFFFF', // White border to hide it
                'borderBottomSize' => 1,
                'borderBottomColor' => 'FFFFFF', // White border to hide it
                'borderLeftSize' => 1,
                'borderLeftColor' => 'FFFFFF', // White border to hide it
                'borderRightSize' => 6,
                'borderRightColor' => '000000'
            ]);
            $rightCell->addText('Date: ' . (($pickupData && $pickupData->date) ? date('M d, Y', strtotime($pickupData->date)) : ''), ['size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            
            // Type of Document/Materials header - only left and right borders, no top/bottom
            $row = $container->addRow();
            $row->addCell(10000, [
                'gridSpan' => 2,
                'valign' => 'bottom',
                'borderTopSize' => 1,
                'borderTopColor' => 'FFFFFF', // White border to hide it
                'borderBottomSize' => 1,
                'borderBottomColor' => 'FFFFFF', // White border to hide it
                'borderLeftSize' => 6,
                'borderLeftColor' => '000000',
                'borderRightSize' => 6,
                'borderRightColor' => '000000'
            ])->addText('Type of Document/Materials', ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
            
            // Documents/Materials content - split across 4 lines with bottom borders
            $documentsText = $pickupData->documents_materials ?? '';
            
            // Split content into 4 lines (approximately 60 characters per line)
            // Break at word boundaries to avoid cutting words
            $lines = [];
            if (!empty($documentsText)) {
                $words = explode(' ', $documentsText);
                $currentLine = '';
                $maxCharsPerLine = 60;
                
                foreach ($words as $word) {
                    $testLine = $currentLine ? $currentLine . ' ' . $word : $word;
                    if (strlen($testLine) <= $maxCharsPerLine) {
                        $currentLine = $testLine;
                    } else {
                        if ($currentLine) {
                            $lines[] = $currentLine;
                        }
                        $currentLine = $word;
                    }
                }
                if ($currentLine) {
                    $lines[] = $currentLine;
                }
            }
            
            // Pad to 4 lines
            while (count($lines) < 4) {
                $lines[] = '';
            }
            // Keep only first 4 lines
            $lines = array_slice($lines, 0, 4);
            
            // Create 4 rows with bottom borders
            for ($i = 0; $i < 4; $i++) {
                $row = $container->addRow(300);
                $cell = $row->addCell(10000, [
                    'gridSpan' => 2,
                    'valign' => 'bottom',
                    'borderTopSize' => 1,
                    'borderTopColor' => 'FFFFFF', // White border to hide it
                    'borderBottomSize' => 6,
                    'borderBottomColor' => '000000',
                    'borderLeftSize' => 6,
                    'borderLeftColor' => '000000',
                    'borderRightSize' => 6,
                    'borderRightColor' => '000000'
                ]);
                // Add text with bold formatting if content exists
                if (!empty($lines[$i])) {
                    $cell->addText($lines[$i], ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                } else {
                    $cell->addText('', ['size' => 9]);
                }
            }
            
            // Bottom signature row - bottom, left, and right borders, no top border
            $row = $container->addRow();
            $leftSigCell = $row->addCell(5000, [
                'valign' => 'bottom',
                'borderTopSize' => 1,
                'borderTopColor' => 'FFFFFF', // White border to hide it
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
                'borderLeftSize' => 6,
                'borderLeftColor' => '000000',
                'borderRightSize' => 1,
                'borderRightColor' => 'FFFFFF' // White border to hide it
            ]);
            $leftSigCell->addText('Picked up by: ' . ($pickupData->picked_up_by ?? '_______________________'), ['size' => 9]);
            $leftSigCell->addText('Date: ' . (($pickupData && $pickupData->pickup_date) ? date('M d, Y', strtotime($pickupData->pickup_date)) : '_______________________'), ['size' => 9]);
            
            $rightSigCell = $row->addCell(5000, [
                'valign' => 'bottom',
                'borderTopSize' => 1,
                'borderTopColor' => 'FFFFFF', // White border to hide it
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
                'borderLeftSize' => 1,
                'borderLeftColor' => 'FFFFFF', // White border to hide it
                'borderRightSize' => 6,
                'borderRightColor' => '000000'
            ]);
            $rightSigCell->addText('Requested By: ' . ($requestedByResolved ?: '_______________________'), ['size' => 9]);
            $rightSigCell->addText('Received By: ' . ($pickupData->received_by ?? '_______________________'), ['size' => 9]);
        };

        // Create first form
        $createForm($section, $pickupData, $requestedByResolved, $ob);
        
        // Create second form (duplicate)
        $createForm($section, $pickupData, $requestedByResolved, $ob);

        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'req-pickup-');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        $downloadFileName = 'Request_for_Pickup_' . $ob->id . '.docx';

        return response()->download($tempFile, $downloadFileName)->deleteFileAfterSend(true);
    }

    /**
     * Download Request for Pick-up as Excel (XLSX)
     */
    public function downloadRequestForPickupExcel($id)
    {
        $app_key = env("APP_KEY", "");

        // Reuse the same OB query as PDF/Word
        $ob = DB::table('official_business_applications as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->select(
                'a.id',
                'a.date',
                'a.ob_type',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        UPPER(CONCAT(b.first_name,' ',b.last_name))
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                    END as name")
            )
            ->where('a.id', $id)
            ->where('a.ob_type', 4)
            ->first();

        if (!$ob) {
            return response()->json(['error' => 'Request for Pick-up not found'], 404);
        }

        // Same pickup data (with requested_by employee join) as PDF/Word
        $pickupData = DB::table('request_for_pickup as r')
            ->leftJoin('employees as e', 'r.requested_by_employee_id', '=', 'e.id')
            ->leftJoin('divisions as d', 'e.division_id', '=', 'd.id')
            ->select(
                'r.*',
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(e.first_name,' ',COALESCE(SUBSTRING(e.middle_name,1,1),''),'. ',e.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+UPPER(COALESCE(SUBSTRING([dbo].[ufn_DecryptString](e.middle_name,'$app_key'),1,1),''))+'. '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                        END as requested_by_name"),
                'd.name as requested_by_division'
            )
            ->where('r.ob_id', $id)
            ->first();

        // Resolve requested by display exactly like PDF/Word
        $requestedByResolved = null;
        if ($pickupData) {
            if (!empty(trim($pickupData->requested_by_name ?? ''))) {
                $requestedByResolved = $pickupData->requested_by_division
                    ? $pickupData->requested_by_name . ' / ' . $pickupData->requested_by_division
                    : $pickupData->requested_by_name;
            } elseif (!empty(trim($pickupData->requested_by ?? ''))) {
                $requestedByResolved = $pickupData->requested_by;
            }
        }

        // Build Excel with PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set page orientation to portrait
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(35);

        // Helper function to create one form
        $createForm = function($sheet, $startRow, $pickupData, $requestedByResolved) {
            $row = $startRow;

            // Header - Title
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('A' . $row, 'PHILIPPINE TRADE TRAINING CENTER');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(13);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            $row++;
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('A' . $row, 'REQUEST FOR PICK-UP');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Reference number (top right)
            $sheet->setCellValue('C' . ($row - 1), 'AFM-PER.FRM/REV 00/15-16-14');
            $sheet->getStyle('C' . ($row - 1))->getFont()->setSize(7);
            $sheet->getStyle('C' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // From / Date row
            $row++;
            $sheet->setCellValue('A' . $row, 'From:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->setCellValue('B' . $row, $pickupData->from ?? '');
            $sheet->setCellValue('C' . $row, 'Date:');
            $sheet->getStyle('C' . $row)->getFont()->setBold(true);
            $sheet->setCellValue('D' . $row, ($pickupData && $pickupData->date) ? date('M d, Y', strtotime($pickupData->date)) : '');
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Company
            $row++;
            $sheet->setCellValue('A' . $row, 'Company:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $row . ':D' . $row);
            $sheet->setCellValue('B' . $row, $pickupData->company ?? '');

            // Address
            $row++;
            $sheet->setCellValue('A' . $row, 'Address:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $row . ':D' . $row);
            $sheet->setCellValue('B' . $row, $pickupData->address ?? '');

            // Contact No
            $row++;
            $sheet->setCellValue('A' . $row, 'Contact No:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $row . ':D' . $row);
            $sheet->setCellValue('B' . $row, $pickupData->contact_no ?? '');
            $row++;

            // Type of Document/Materials header
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('A' . $row, 'Type of Document/Materials');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;

            // Documents/Materials content - split across 4 lines
            $documentsText = $pickupData->documents_materials ?? '';
            $lines = [];
            if (!empty($documentsText)) {
                $words = explode(' ', $documentsText);
                $currentLine = '';
                $maxCharsPerLine = 60;
                
                foreach ($words as $word) {
                    $testLine = $currentLine ? $currentLine . ' ' . $word : $word;
                    if (strlen($testLine) <= $maxCharsPerLine) {
                        $currentLine = $testLine;
                    } else {
                        if ($currentLine) {
                            $lines[] = $currentLine;
                        }
                        $currentLine = $word;
                    }
                }
                if ($currentLine) {
                    $lines[] = $currentLine;
                }
            }
            while (count($lines) < 4) {
                $lines[] = '';
            }
            $lines = array_slice($lines, 0, 4);

            $docStartRow = $row;

            // Create 4 rows for documents content
            for ($i = 0; $i < 4; $i++) {
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->setCellValue('A' . $row, $lines[$i] ?? '');
                if (!empty($lines[$i])) {
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                }
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('A' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
                $sheet->getRowDimension($row)->setRowHeight(20);
                // Add bottom border only on last line
                if ($i === 3) {
                    $sheet->getStyle('A' . $row . ':D' . $row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);
                }
                $row++;
            }

            // Bottom signature section
            $row++;
            $sheet->setCellValue('A' . $row, 'Picked up by:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
            $sheet->setCellValue('B' . $row, $pickupData->picked_up_by ?? '_______________________');
            $sheet->getStyle('B' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
            $sheet->setCellValue('C' . $row, 'Requested By:');
            $sheet->getStyle('C' . $row)->getFont()->setBold(true);
            $sheet->getStyle('C' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
            $sheet->setCellValue('D' . $row, $requestedByResolved ?: '_______________________');
            $sheet->getStyle('D' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);

            $row++;
            $sheet->setCellValue('A' . $row, 'Date:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
            $sheet->setCellValue('B' . $row, ($pickupData && $pickupData->pickup_date) ? date('M d, Y', strtotime($pickupData->pickup_date)) : '_______________________');
            $sheet->getStyle('B' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
            $sheet->setCellValue('C' . $row, 'Received By:');
            $sheet->getStyle('C' . $row)->getFont()->setBold(true);
            $sheet->getStyle('C' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
            $sheet->setCellValue('D' . $row, $pickupData->received_by ?? '_______________________');
            $sheet->getStyle('D' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);

            $endRow = $row;

            // Add outer borders (top, left, right, bottom)
            $sheet->getStyle('A' . $startRow . ':D' . $endRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
            $sheet->getStyle('A' . $startRow . ':D' . $endRow)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THICK);
            $sheet->getStyle('A' . $startRow . ':D' . $endRow)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THICK);
            $sheet->getStyle('A' . $endRow . ':D' . $endRow)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);

            // Remove internal borders (horizontal and vertical)
            $sheet->getStyle('A' . ($startRow + 2) . ':D' . ($docStartRow - 1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_NONE);
            $sheet->getStyle('A' . ($docStartRow) . ':D' . ($docStartRow + 3))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_NONE);
            $sheet->getStyle('A' . ($docStartRow + 4) . ':D' . $endRow)->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_NONE);
            $sheet->getStyle('A' . $startRow . ':D' . $endRow)->getBorders()->getVertical()->setBorderStyle(Border::BORDER_NONE);
            $sheet->getStyle('A' . ($docStartRow - 1) . ':D' . ($docStartRow - 1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_NONE);

            return $endRow;
        };

        // Create first form
        $firstFormEnd = $createForm($sheet, 1, $pickupData, $requestedByResolved);
        
        // Add spacing between forms
        $spacing = 2;
        
        // Create second form (duplicate)
        $secondFormStart = $firstFormEnd + $spacing + 1;
        $createForm($sheet, $secondFormStart, $pickupData, $requestedByResolved);

        // Save to temp file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'req-pickup-excel-');
        $tempFile .= '.xlsx';
        $writer->save($tempFile);

        $downloadFileName = 'Request_for_Pickup_' . $ob->id . '.xlsx';

        return response()->download($tempFile, $downloadFileName)->deleteFileAfterSend(true);
    }


       /**
     * Get current employee info with division for Request for Pickup
     */
    public function getEmployeeInfoForPickup($id)
    {
        $app_key = env("APP_KEY", "");

        try {
            // Get user's employee record
            $user = DB::table('users')->where('id', $id)->first();
            
            if (!$user || !$user->employee_no) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not linked to an employee'
                ], 404);
            }

            // Get employee info with division
            $employeeInfo = DB::table('employees as a')
                ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',COALESCE(SUBSTRING(a.middle_name,1,1),''),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(COALESCE(SUBSTRING([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1),''))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'd.name as division_name'
                )
                ->where('a.employee_no', $user->employee_no)
                ->where('a.is_employee', true)
                ->where('a.active', true)
                ->first();

            if (!$employeeInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee record not found'
                ], 404);
            }

            // Format: "Name / Division" or just "Name" if no division
            $requestedBy = $employeeInfo->division_name 
                ? $employeeInfo->name . ' / ' . $employeeInfo->division_name
                : $employeeInfo->name;

            return response()->json([
                'success' => true,
                'data' => [
                    'employee_id' => $employeeInfo ? $employeeInfo->employee_id : null,
                    'name' => $employeeInfo->name,
                    'division_name' => $employeeInfo->division_name,
                    'requested_by' => $requestedBy
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage()
            ], 500);
        }
    }

       /**
     * Download Travel Order LDSD as Word document
     */
    public function downloadTravelOrderLDSDDocx($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Retrieve the same data as printorder
            $ob = DB::table('official_business_applications as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->leftJoin('branches as d', 'd.id', '=', 'b.branch_id')
                ->select(
                    'a.id',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.type_id',
                    'c.name as position',
                    'd.name as branch',
                    'a.client',
                    'a.purpose',
                    'a.ob_type',
                    'a.funds',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                            UPPER(CONCAT(b.first_name,' ',b.last_name))
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                        END as name")
                )
                ->where('a.id', $id)
                ->first();

            if (!$ob) {
                return response()->json(['error' => 'Travel Order not found.'], 404);
            }

            // Format data
            $fromDate = $ob->date_time_from ? \Carbon\Carbon::parse($ob->date_time_from)->format('F d, Y') : '';
            $toDate = $ob->date_time_to ? \Carbon\Carbon::parse($ob->date_time_to)->format('F d, Y') : '';
            $inclusive = trim($fromDate . ($toDate && $toDate !== $fromDate ? ' - ' . $toDate : ''));
            $destination = $ob->client ?? '';
            $purpose = $ob->purpose ?? '';
            $date = $ob->date ? \Carbon\Carbon::parse($ob->date)->format('F d, Y') : '';
            $name = $ob->name ?? '';
            
            // Format name with title prefix if needed
            $fullName = $name;
            if (!empty($name) && stripos($name, 'Mr.') === false && stripos($name, 'Ms.') === false && stripos($name, 'Mrs.') === false) {
                $fullName = 'Mr. ' . $name;
            }

            // Get current year for series
            $seriesYear = \Carbon\Carbon::now()->year;

            // Build DOCX with PhpWord
            $phpWord = new PhpWord();

            // A4 Portrait section with margins: top 1in, right 1in, bottom 0.5in, left 1in
            $section = $phpWord->addSection([
                'marginTop' => 1440,  // 1 inch = 1440 twips
                'marginRight' => 1440,
                'marginBottom' => 720, // 0.5 inch = 720 twips
                'marginLeft' => 1440
            ]);

            // Header section
            $section->addText('MEMORANDUM ORDER NO. ________', [], ['spaceAfter' => 0]);
            $section->addText('Series of ' . $seriesYear, [], ['spaceAfter' => 120]);
            
            // Date on the right
            $dateTextRun = $section->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            $dateTextRun->addText('Date: ', []);
            $dateTextRun->addText($date, ['underline' => 'single']);

            // Title: TRAVEL ORDER
            $section->addTextBreak(2);
            $section->addText('TRAVEL ORDER', ['bold' => true, 'size' => 12], [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter' => 240
            ]);

            // Numbered list items
            $section->addTextBreak(1);

            // Item 1
            $item1TextRun = $section->addTextRun(['spaceAfter' => 240, 'indentation' => ['left' => 360, 'hanging' => 360]]);
            $item1TextRun->addText('1.  ', []);
            $item1TextRun->addText('In the interest of the service, ', []);
            $item1TextRun->addText($fullName, ['bold' => true]);
            $item1TextRun->addText(', is hereby authorized to travel to ', []);
            $item1TextRun->addText($destination, ['bold' => true]);
            $item1TextRun->addText(' on ', []);
            $item1TextRun->addText($inclusive, ['bold' => true]);
            $item1TextRun->addText(' (inclusive of travel time) to facilitate the ', []);
            $item1TextRun->addText($purpose, ['bold' => true]);
            if (!empty($destination)) {
                $item1TextRun->addText(' to be held at ', []);
                $item1TextRun->addText($destination, ['bold' => true]);
            }
            $item1TextRun->addText('.', []);

            // Item 2
            $item2TextRun = $section->addTextRun(['spaceAfter' => 240, 'indentation' => ['left' => 360, 'hanging' => 360]]);
            $item2TextRun->addText('2.  ', []);
            $item2TextRun->addText(BranchHelper::getMainBranchCode() . ' shall shoulder the incidental expenses and applicable per diem charged to ' . BranchHelper::getMainBranchCode() . ' Trust Fund subject to applicable government rules and regulations.', []);

            // Item 3
            $item3TextRun = $section->addTextRun(['spaceAfter' => 240, 'indentation' => ['left' => 360, 'hanging' => 360]]);
            $item3TextRun->addText('3.  ', []);
            $item3TextRun->addText('The above-mentioned personnel shall submit within thirty (30) days the required Certificate of Travel Completed, together with the transportation tickets and Certificate of Appearance and other necessary supporting papers, if any, and to refund any excess cash advance within ten (10) days upon return to official station.', []);

            // Item 4 (empty)
            $item4TextRun = $section->addTextRun(['spaceAfter' => 240, 'indentation' => ['left' => 360, 'hanging' => 360]]);
            $item4TextRun->addText('4.  ', []);

            // Item 5
            $item5TextRun = $section->addTextRun(['spaceAfter' => 480, 'indentation' => ['left' => 360, 'hanging' => 360]]);
            $item5TextRun->addText('5.  ', []);
            $item5TextRun->addText('This Order shall take effect immediately.', []);

            // Signatory
            $section->addTextBreak(2);
            $section->addText('CLARE MARI S. TORRALBA', ['bold' => true], ['spaceAfter' => 0]);
            $section->addText('Executive Director', [], ['spaceAfter' => 0]);

            // Save document
            $filename = 'Travel_Order_LDSD_' . $id . '.docx';
            $tempFile = tempnam(sys_get_temp_dir(), 'travel_order_ldsd_');
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate Travel Order LDSD Word document.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
