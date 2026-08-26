<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use PDF;
use Auth;
use App\Audit;
use App\User;
use App\Traits\ApiResponse;
use Notification;
use App\Notifications\EmailOBApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\OfficialBusinessApplication;
use App\RequestForPickup;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OfficialBusinessApplicationController extends Controller
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

            // Supervisor for OB Slip / Pass Slip should be based on approver_headers.approver_id_1
            // where type_id = 5 (OB Slip/Pass Slip) and the employee is in approver_details.
            $supervisor_id = 0;
            $obApprover = DB::table('approver_details as ad')
                ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                ->select('ah.approver_id_1')
                ->where('ad.employee_id', $emp_id)
                ->where('ah.type_id', 5) // 5 = OB Slip / Pass Slip
                ->orderBy('ah.id', 'desc')
                ->first();

            if ($obApprover && !empty($obApprover->approver_id_1)) {
                $supervisor_id = (int) $obApprover->approver_id_1;
            }
        }

        // Check if Approver Start
        $approver_1 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.approver_id_1 as supervisor_id'
            )
            ->whereIn('a.type_id', [2, 6]) // Official Business / Request for Pickup
            ->where(function ($query) use ($emp_id) {
                $query->where('a.branch_approver_id_1', $emp_id)
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
            ->where('a.approver_id_2', $emp_id)
            ->whereIn('a.type_id', [2, 6]) // Official Business / Request for Pickup
            ->distinct()
            ->get();

        $approver_3 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.approver_id_3 as supervisor_id'
            )
            ->where('a.approver_id_3', $emp_id)
            ->whereIn('a.type_id', [2, 6]) // Official Business / Request for Pickup
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
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.ob_type',
                    'a.type_id',
                    'a.employee_id',
                    'a.client',
                    'a.purpose',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.is_cancel',
                    'a.approved',
                    'a.disapproved',
                    'a.approved_2',
                    'a.disapproved_2',
                    'a.approved_3',
                    'a.disapproved_3',
                    'a.processed_by',
                    'a.processed_date',
                    'a.processed_by_2',
                    'a.processed_date_2',
                    'a.processed_by_3',
                    'a.processed_date_3',
                    DB::raw("CASE WHEN EXISTS (
                                SELECT 1
                                FROM approver_details ad
                                INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                                WHERE ad.employee_id = a.employee_id
                                  AND ah.type_id = 2
                                  AND ISNULL(ah.approver_id_2,0) > 0
                            ) THEN 1 ELSE 0 END as requires_second_level"),
                    DB::raw("CASE WHEN EXISTS (
                                SELECT 1
                                FROM approver_details ad
                                INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                                WHERE ad.employee_id = a.employee_id
                                  AND ah.type_id = 2
                                  AND ISNULL(ah.approver_id_3,0) > 0
                            ) THEN 1 ELSE 0 END as requires_third_level"),
                    DB::raw("(SELECT TOP 1
                                CASE WHEN ISNULL(emp1.is_encrypted,0) = 0 THEN
                                        CONCAT(emp1.first_name,' ',emp1.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](emp1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp1.last_name,'$app_key'))
                                END
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
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
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
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
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
                            INNER JOIN employees emp3 ON emp3.id = ah.approver_id_3
                            WHERE ad.employee_id = a.employee_id AND ISNULL(ah.approver_id_3,0) > 0
                            ORDER BY ah.id DESC) as approver_3"),
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
                )
                ->where(['a.approved' => false, 'a.disapproved' => false])
                ->where(function ($query) use ($emp_id) {
                    // For ob_type=2 & type_id=2 (Travel Authority): only approver_id_1/2/3 from approver_headers. Others: branch/division/section.
                    $query->where(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', '!=', 2)->orWhere('a.type_id', '!=', 2);
                    })->whereIn('b.id', function ($sub) use ($emp_id) {
                        $sub->select('ad.employee_id')->from('approver_details as ad')
                            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                            ->where('ah.type_id', 2)
                            ->where(function ($w) use ($emp_id) {
                                $w->where('ah.branch_approver_id_1', $emp_id)->orWhere('ah.approver_id_1', $emp_id)
                                    ->orWhere('ah.division_approver_id_1', $emp_id)->orWhere('ah.section_approver_id_1', $emp_id);
                            });
                    })->orWhere(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', 2)->where('a.type_id', 2)
                            ->whereIn('b.id', function ($sub) use ($emp_id) {
                                $sub->select('ad.employee_id')->from('approver_details as ad')
                                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                                    ->where('ah.type_id', 2)
                                    ->where(function ($w) use ($emp_id) {
                                        $w->where('ah.approver_id_1', $emp_id)->orWhere('ah.approver_id_2', $emp_id)->orWhere('ah.approver_id_3', $emp_id);
                                    });
                            });
                    })->orWhere(function ($q) use ($emp_id) {
                        // Request for Pickup (ob_type = 4): only approver_id_1/2/3 from approver_headers.type_id = 6
                        $q->where('a.ob_type', 4)
                            ->whereIn('b.id', function ($sub) use ($emp_id) {
                                $sub->select('ad.employee_id')->from('approver_details as ad')
                                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                                    ->where('ah.type_id', 6)
                                    ->where(function ($w) use ($emp_id) {
                                        $w->where('ah.approver_id_1', $emp_id)->orWhere('ah.approver_id_2', $emp_id)->orWhere('ah.approver_id_3', $emp_id);
                                    });
                            });
                    });
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->where('is_cancel', false)
                ->orderby('a.date', 'desc')
                ->distinct()
                ->get();

            $ApprovedEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('employees as proc1', 'proc1.id', '=', 'a.processed_by')
                ->leftJoin('employees as proc2', 'proc2.id', '=', 'a.processed_by_2')
                ->leftJoin('employees as proc3', 'proc3.id', '=', 'a.processed_by_3')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.ob_type',
                    'a.type_id',
                    'a.employee_id',
                    'a.client',
                    'a.purpose',
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.is_cancel',
                    'a.approved',
                    'a.disapproved',
                    'a.approved_2',
                    'a.disapproved_2',
                    'a.approved_3',
                    'a.disapproved_3',
                    'a.processed_by',
                    'a.processed_date',
                    'a.processed_by_2',
                    'a.processed_date_2',
                    'a.processed_by_3',
                    'a.processed_date_3',
                    DB::raw("CASE WHEN EXISTS (
                                SELECT 1
                                FROM approver_details ad
                                INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                                WHERE ad.employee_id = a.employee_id
                                  AND ah.type_id = 2
                                  AND ISNULL(ah.approver_id_2,0) > 0
                            ) THEN 1 ELSE 0 END as requires_second_level"),
                    DB::raw("CASE WHEN EXISTS (
                                SELECT 1
                                FROM approver_details ad
                                INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                                WHERE ad.employee_id = a.employee_id
                                  AND ah.type_id = 2
                                  AND ISNULL(ah.approver_id_3,0) > 0
                            ) THEN 1 ELSE 0 END as requires_third_level"),
                    DB::raw("CASE WHEN ISNULL(proc1.is_encrypted,0) = 0 THEN
                                CONCAT(proc1.first_name,' ',proc1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](proc1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](proc1.last_name,'$app_key'))
                            END as approver_1"),
                    DB::raw("CASE WHEN ISNULL(proc2.is_encrypted,0) = 0 THEN
                                CONCAT(proc2.first_name,' ',proc2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](proc2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](proc2.last_name,'$app_key'))
                            END as approver_2"),
                    DB::raw("CASE WHEN ISNULL(proc3.is_encrypted,0) = 0 THEN
                                CONCAT(proc3.first_name,' ',proc3.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](proc3.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](proc3.last_name,'$app_key'))
                            END as approver_3"),
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
                )
                ->where(['a.approved' => true, 'a.disapproved' => false])
                ->where(function ($query) use ($emp_id) {
                    $query->where(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', '!=', 2)->orWhere('a.type_id', '!=', 2);
                    })->whereIn('b.id', function ($sub) use ($emp_id) {
                        $sub->select('ad.employee_id')->from('approver_details as ad')
                            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                            ->where('ah.type_id', 2)
                            ->where(function ($w) use ($emp_id) {
                                $w->where('ah.branch_approver_id_1', $emp_id)->orWhere('ah.approver_id_1', $emp_id)
                                    ->orWhere('ah.division_approver_id_1', $emp_id)->orWhere('ah.section_approver_id_1', $emp_id);
                            });
                    })->orWhere(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', 2)->where('a.type_id', 2)
                            ->whereIn('b.id', function ($sub) use ($emp_id) {
                                $sub->select('ad.employee_id')->from('approver_details as ad')
                                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                                    ->where('ah.type_id', 2)
                                    ->where(function ($w) use ($emp_id) {
                                        $w->where('ah.approver_id_1', $emp_id)->orWhere('ah.approver_id_2', $emp_id)->orWhere('ah.approver_id_3', $emp_id);
                                    });
                            });
                    });
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->orderby('a.date', 'desc')
                ->distinct()
                ->get();

            $DisapprovedEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    DB::raw("a.disapproved_remark as remarks"),
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
                )
                ->where(['a.approved' => false, 'a.disapproved' => true])
                ->where(function ($query) use ($emp_id) {
                    $query->where(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', '!=', 2)->orWhere('a.type_id', '!=', 2);
                    })->whereIn('b.id', function ($sub) use ($emp_id) {
                        $sub->select('ad.employee_id')->from('approver_details as ad')
                            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                            ->where('ah.type_id', 2)
                            ->where(function ($w) use ($emp_id) {
                                $w->where('ah.branch_approver_id_1', $emp_id)->orWhere('ah.approver_id_1', $emp_id)
                                    ->orWhere('ah.division_approver_id_1', $emp_id)->orWhere('ah.section_approver_id_1', $emp_id);
                            });
                    })->orWhere(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', 2)->where('a.type_id', 2)
                            ->whereIn('b.id', function ($sub) use ($emp_id) {
                                $sub->select('ad.employee_id')->from('approver_details as ad')
                                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                                    ->where('ah.type_id', 2)
                                    ->where(function ($w) use ($emp_id) {
                                        $w->where('ah.approver_id_1', $emp_id)->orWhere('ah.approver_id_2', $emp_id)->orWhere('ah.approver_id_3', $emp_id);
                                    });
                            });
                    });
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->orderby('a.date', 'desc')
                ->distinct()
                ->get();

            $CancelledEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    'a.canceled_remarks as remarks',
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
                )
                ->where(['a.approved' => false, 'a.disapproved' => false])
                ->where(function ($query) use ($emp_id) {
                    $query->where(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', '!=', 2)->orWhere('a.type_id', '!=', 2);
                    })->whereIn('b.id', function ($sub) use ($emp_id) {
                        $sub->select('ad.employee_id')->from('approver_details as ad')
                            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                            ->where('ah.type_id', 2)
                            ->where(function ($w) use ($emp_id) {
                                $w->where('ah.branch_approver_id_1', $emp_id)->orWhere('ah.approver_id_1', $emp_id)
                                    ->orWhere('ah.division_approver_id_1', $emp_id)->orWhere('ah.section_approver_id_1', $emp_id);
                            });
                    })->orWhere(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', 2)->where('a.type_id', 2)
                            ->whereIn('b.id', function ($sub) use ($emp_id) {
                                $sub->select('ad.employee_id')->from('approver_details as ad')
                                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                                    ->where('ah.type_id', 2)
                                    ->where(function ($w) use ($emp_id) {
                                        $w->where('ah.approver_id_1', $emp_id)->orWhere('ah.approver_id_2', $emp_id)->orWhere('ah.approver_id_3', $emp_id);
                                    });
                            });
                    });
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
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    DB::raw("(SELECT TOP 1
                                CASE WHEN ISNULL(emp1.is_encrypted,0) = 0 THEN
                                        CONCAT(emp1.first_name,' ',emp1.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](emp1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp1.last_name,'$app_key'))
                                END
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
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
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
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
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
                            INNER JOIN employees emp3 ON emp3.id = ah.approver_id_3
                            WHERE ad.employee_id = a.employee_id AND ISNULL(ah.approver_id_3,0) > 0
                            ORDER BY ah.id DESC) as approver_3"),
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
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
                ->leftJoin('employees as proc1', 'proc1.id', '=', 'a.processed_by')
                ->leftJoin('employees as proc2', 'proc2.id', '=', 'a.processed_by_2')
                ->leftJoin('employees as proc3', 'proc3.id', '=', 'a.processed_by_3')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    'a.purpose',
                    'a.type_id',
                    'a.employee_id',
                    'a.is_cancel',
                    'a.approved',
                    'a.disapproved',
                    'a.approved_2',
                    'a.disapproved_2',
                    'a.approved_3',
                    'a.disapproved_3',
                    'a.processed_by',
                    'a.processed_date',
                    'a.processed_by_2',
                    'a.processed_date_2',
                    'a.processed_by_3',
                    'a.processed_date_3',
                    DB::raw("CASE WHEN EXISTS (
                                SELECT 1
                                FROM approver_details ad
                                INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                                WHERE ad.employee_id = a.employee_id
                                  AND ah.type_id = 2
                                  AND ISNULL(ah.approver_id_2,0) > 0
                            ) THEN 1 ELSE 0 END as requires_second_level"),
                    DB::raw("CASE WHEN EXISTS (
                                SELECT 1
                                FROM approver_details ad
                                INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                                WHERE ad.employee_id = a.employee_id
                                  AND ah.type_id = 2
                                  AND ISNULL(ah.approver_id_3,0) > 0
                            ) THEN 1 ELSE 0 END as requires_third_level"),
                    DB::raw("CASE WHEN ISNULL(proc1.is_encrypted,0) = 0 THEN
                                CONCAT(proc1.first_name,' ',proc1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](proc1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](proc1.last_name,'$app_key'))
                            END as approver_1"),
                    DB::raw("CASE WHEN ISNULL(proc2.is_encrypted,0) = 0 THEN
                                CONCAT(proc2.first_name,' ',proc2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](proc2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](proc2.last_name,'$app_key'))
                            END as approver_2"),
                    DB::raw("CASE WHEN ISNULL(proc3.is_encrypted,0) = 0 THEN
                                CONCAT(proc3.first_name,' ',proc3.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](proc3.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](proc3.last_name,'$app_key'))
                            END as approver_3"),
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
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
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    DB::raw("a.disapproved_2_remark as remarks"),
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
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
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    'a.canceled_remarks as remarks',
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
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
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',
                    'a.date_time_from',
                    'a.ob_type',            // ← Position 6 (matches main query)
                    'a.funds',              // ← Position 7 (matches main query)
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.date_time_to',       // ← Move date_time_to AFTER recommending_position
                    'a.client',
                    'a.purpose',
                    'a.is_cancel',
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
                )
                ->where(['a.approved' => false, 'a.disapproved' => false])
                ->where(function ($query) use ($emp_id) {
                    $query->where(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', '!=', 2)->orWhere('a.type_id', '!=', 2);
                    })->whereIn('b.id', function ($sub) use ($emp_id) {
                        $sub->select('ad.employee_id')->from('approver_details as ad')
                            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                            ->where('ah.type_id', 2)
                            ->where(function ($w) use ($emp_id) {
                                $w->where('ah.branch_approver_id_1', $emp_id)->orWhere('ah.approver_id_1', $emp_id)
                                    ->orWhere('ah.division_approver_id_1', $emp_id)->orWhere('ah.section_approver_id_1', $emp_id);
                            });
                    })->orWhere(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', 2)->where('a.type_id', 2)
                            ->whereIn('b.id', function ($sub) use ($emp_id) {
                                $sub->select('ad.employee_id')->from('approver_details as ad')
                                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                                    ->where('ah.type_id', 2)
                                    ->where(function ($w) use ($emp_id) {
                                        $w->where('ah.approver_id_1', $emp_id)->orWhere('ah.approver_id_2', $emp_id)->orWhere('ah.approver_id_3', $emp_id);
                                    });
                            });
                    });
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->where('is_cancel', false)
                ->distinct();

            $ApprovedEmployeeOB_1 = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    'a.type_id',
                    'a.employee_id',
                    'a.approved',
                    'a.disapproved',
                    'a.approved_2',
                    'a.disapproved_2',
                    'a.approved_3',
                    'a.disapproved_3',
                    'a.processed_by',
                    'a.processed_date',
                    'a.processed_by_2',
                    'a.processed_date_2',
                    'a.processed_by_3',
                    'a.processed_date_3',
                    DB::raw("CASE WHEN EXISTS (
                                SELECT 1
                                FROM approver_details ad
                                INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                                WHERE ad.employee_id = a.employee_id
                                  AND ah.type_id = 2
                                  AND ISNULL(ah.approver_id_2,0) > 0
                            ) THEN 1 ELSE 0 END as requires_second_level"),
                    DB::raw("CASE WHEN EXISTS (
                                SELECT 1
                                FROM approver_details ad
                                INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                                WHERE ad.employee_id = a.employee_id
                                  AND ah.type_id = 2
                                  AND ISNULL(ah.approver_id_3,0) > 0
                            ) THEN 1 ELSE 0 END as requires_third_level"),
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
                )
                ->where(['a.approved' => true, 'a.disapproved' => false])
                ->where(function ($query) use ($emp_id) {
                    $query->where(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', '!=', 2)->orWhere('a.type_id', '!=', 2);
                    })->whereIn('b.id', function ($sub) use ($emp_id) {
                        $sub->select('ad.employee_id')->from('approver_details as ad')
                            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                            ->where('ah.type_id', 2)
                            ->where(function ($w) use ($emp_id) {
                                $w->where('ah.branch_approver_id_1', $emp_id)->orWhere('ah.approver_id_1', $emp_id)
                                    ->orWhere('ah.division_approver_id_1', $emp_id)->orWhere('ah.section_approver_id_1', $emp_id);
                            });
                    })->orWhere(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', 2)->where('a.type_id', 2)
                            ->whereIn('b.id', function ($sub) use ($emp_id) {
                                $sub->select('ad.employee_id')->from('approver_details as ad')
                                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                                    ->where('ah.type_id', 2)
                                    ->where(function ($w) use ($emp_id) {
                                        $w->where('ah.approver_id_1', $emp_id)->orWhere('ah.approver_id_2', $emp_id)->orWhere('ah.approver_id_3', $emp_id);
                                    });
                            });
                    });
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->distinct();

            $DisapprovedEmployeeOB_1 = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    DB::raw("a.disapproved_remark as remarks"),
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
                )
                ->where(['a.approved' => false, 'a.disapproved' => true])
                ->where(function ($query) use ($emp_id) {
                    $query->where(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', '!=', 2)->orWhere('a.type_id', '!=', 2);
                    })->whereIn('b.id', function ($sub) use ($emp_id) {
                        $sub->select('ad.employee_id')->from('approver_details as ad')
                            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                            ->where('ah.type_id', 2)
                            ->where(function ($w) use ($emp_id) {
                                $w->where('ah.branch_approver_id_1', $emp_id)->orWhere('ah.approver_id_1', $emp_id)
                                    ->orWhere('ah.division_approver_id_1', $emp_id)->orWhere('ah.section_approver_id_1', $emp_id);
                            });
                    })->orWhere(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', 2)->where('a.type_id', 2)
                            ->whereIn('b.id', function ($sub) use ($emp_id) {
                                $sub->select('ad.employee_id')->from('approver_details as ad')
                                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                                    ->where('ah.type_id', 2)
                                    ->where(function ($w) use ($emp_id) {
                                        $w->where('ah.approver_id_1', $emp_id)->orWhere('ah.approver_id_2', $emp_id)->orWhere('ah.approver_id_3', $emp_id);
                                    });
                            });
                    });
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->distinct();

            $CancelledEmployeeOB_1 = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    'a.canceled_remarks as remarks',
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
                )
                ->where(['a.approved' => false, 'a.disapproved' => false])
                ->where(function ($query) use ($emp_id) {
                    $query->where(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', '!=', 2)->orWhere('a.type_id', '!=', 2);
                    })->whereIn('b.id', function ($sub) use ($emp_id) {
                        $sub->select('ad.employee_id')->from('approver_details as ad')
                            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                            ->where('ah.type_id', 2)
                            ->where(function ($w) use ($emp_id) {
                                $w->where('ah.branch_approver_id_1', $emp_id)->orWhere('ah.approver_id_1', $emp_id)
                                    ->orWhere('ah.division_approver_id_1', $emp_id)->orWhere('ah.section_approver_id_1', $emp_id);
                            });
                    })->orWhere(function ($q) use ($emp_id) {
                        $q->where('a.ob_type', 2)->where('a.type_id', 2)
                            ->whereIn('b.id', function ($sub) use ($emp_id) {
                                $sub->select('ad.employee_id')->from('approver_details as ad')
                                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                                    ->where('ah.type_id', 2)
                                    ->where(function ($w) use ($emp_id) {
                                        $w->where('ah.approver_id_1', $emp_id)->orWhere('ah.approver_id_2', $emp_id)->orWhere('ah.approver_id_3', $emp_id);
                                    });
                            });
                    });
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->where('is_cancel', true)
                ->distinct();

            $ForapprovalEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    'a.is_cancel',
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
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
                ->orderby('created_at', 'desc')
                ->get();

            $ApprovedEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'a.created_at',
                    'a.date',                 // ← Move date here
                    'a.date_time_from',
                    'a.date_time_to',
                    'a.ob_type',              // ← Move ob_type here (after date_time_to)
                    'a.funds',
                    'a.recommending_approval',
                    'a.approver_position',
                    'a.approver',
                    'a.recommending_position',
                    'a.client',
                    'a.purpose',
                    'a.is_cancel',            // ← ADD THIS
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
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
                ->orderby('created_at', 'desc')
                ->get();

            $DisapprovedEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    DB::raw("a.disapproved_2_remark as remarks"),
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
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
                ->orderby('created_at', 'desc')
                ->get();

            $CancelledEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    'a.canceled_remarks as remarks',
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
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
                ->orderby('created_at', 'desc')
                ->get();
        } elseif ($approver_3->isNotEmpty()) {
            $approver_id = $approver_3[0]->id;
            $supervisor_id = $approver_3[0]->supervisor_id;

            // Debug: Log approver 3 detection
            \Log::info('✅ Approver 3 Detected:', [
                'emp_id' => $emp_id,
                'approver_id' => $approver_id,
                'supervisor_id' => $supervisor_id,
                'approver_3_count' => $approver_3->count(),
                'approver_3_data' => $approver_3->toArray()
            ]);

            // Debug: Check if there are any applications matching the criteria
            $testQuery = DB::table('official_business_applications as a')
                ->where('a.approved', 1)
                ->where('a.approved_2', 1)
                ->whereRaw('ISNULL(a.approved_3, 0) = 0')
                ->whereRaw('ISNULL(a.disapproved, 0) = 0')
                ->whereRaw('ISNULL(a.disapproved_2, 0) = 0')
                ->whereRaw('ISNULL(a.disapproved_3, 0) = 0')
                ->where('is_cancel', false)
                ->count();

            \Log::info('🔍 Approver 3 Test - Total applications matching criteria:', [
                'emp_id' => $emp_id,
                'total_matching' => $testQuery,
                'approver_3_config' => $approver_3->toArray()
            ]);

            // Applications pending approver 3's approval (approved = 1 AND approved_2 = 1 AND approved_3 = 0)
            $ForapprovalEmployeeOBQuery = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
                ->select(
                    'a.id',
                    'a.employee_id',
                    'a.approved',
                    'a.approved_2',
                    'a.approved_3',
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
                    'a.is_cancel',
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
                )
                ->where('a.approved', 1)
                ->where('a.approved_2', 1)
                ->whereRaw('ISNULL(a.approved_3, 0) = 0')
                ->whereRaw('ISNULL(a.disapproved, 0) = 0')
                ->whereRaw('ISNULL(a.disapproved_2, 0) = 0')
                ->whereRaw('ISNULL(a.disapproved_3, 0) = 0')
                ->whereIn('b.id', function ($query) use ($emp_id) {
                    $query->select('a.employee_id')
                        ->from('approver_details as a')
                        ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                        ->where('b.approver_id_3', $emp_id)
                        ->where('b.type_id', 2); // Official Business type
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->where('is_cancel', false)
                ->orderby('a.date', 'desc')
                ->distinct();

            // Debug: Log the pending query
            \Log::info('🔍 Approver 3 ForapprovalEmployeeOB Query:', [
                'emp_id' => $emp_id,
                'sql' => $ForapprovalEmployeeOBQuery->toSql(),
                'bindings' => $ForapprovalEmployeeOBQuery->getBindings(),
                'count' => $ForapprovalEmployeeOBQuery->get()->count(),
                'sample_records' => $ForapprovalEmployeeOBQuery->get()->take(3)->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'employee_id' => $item->employee_id,
                        'approved' => $item->approved,
                        'approved_2' => $item->approved_2,
                        'approved_3' => $item->approved_3 ?? 'NULL'
                    ];
                })
            ]);

            $ForapprovalEmployeeOB = $ForapprovalEmployeeOBQuery->get();

            // Applications approved by approver 3 (approved = 1 AND approved_2 = 1 AND approved_3 = 1)
            $ApprovedEmployeeOBQuery = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    'a.is_cancel',
                    'a.approved_3',
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
                )
                ->whereRaw('ISNULL(a.approved, 0) = 1')
                ->whereRaw('ISNULL(a.approved_2, 0) = 1')
                ->whereRaw('a.approved_3 = 1')  // Must be explicitly 1, not 0 or NULL
                ->whereRaw('ISNULL(a.disapproved, 0) = 0')
                ->whereRaw('ISNULL(a.disapproved_2, 0) = 0')
                ->whereRaw('ISNULL(a.disapproved_3, 0) = 0')
                ->whereIn('b.id', function ($query) use ($emp_id) {
                    $query->select('a.employee_id')
                        ->from('approver_details as a')
                        ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                        ->where('b.approver_id_3', $emp_id)
                        ->where('b.type_id', 2); // Official Business type
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->orderby('a.date', 'desc')
                ->distinct();

            // Debug: Log the query SQL and results
            \Log::info(' Approver 3 ApprovedEmployeeOB Query:', [
                'sql' => $ApprovedEmployeeOBQuery->toSql(),
                'bindings' => $ApprovedEmployeeOBQuery->getBindings(),
                'count' => $ApprovedEmployeeOBQuery->get()->count(),
                'sample_records' => $ApprovedEmployeeOBQuery->get()->take(3)->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'approved_3' => $item->approved_3 ?? 'NULL'
                    ];
                })
            ]);

            $ApprovedEmployeeOB = $ApprovedEmployeeOBQuery->get();

            // Applications disapproved by approver 3
            $DisapprovedEmployeeOB = DB::table('official_business_applications as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('ta_type as ta', function ($join) {
                    $join->on('a.type_id', '=', 'ta.id')
                        ->where('a.ob_type', '=', 2);
                })
                ->leftJoin('to_type as to_t', function ($join) {
                    $join->on('a.type_id', '=', 'to_t.id')
                        ->where('a.ob_type', '=', 3);
                })
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
                    DB::raw("a.disapproved_3_remark as remarks"),
                    DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                    DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name")
                )
                ->where(['a.approved' => true, 'a.approved_2' => true, 'a.disapproved_3' => true])
                ->whereIn('b.id', function ($query) use ($emp_id) {
                    $query->select('a.employee_id')
                        ->from('approver_details as a')
                        ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                        ->where('b.approver_id_3', $emp_id)
                        ->where('b.type_id', 2); // Official Business type
                })
                ->where('a.employee_id', '!=', $emp_id)
                ->orderby('a.date', 'desc')
                ->distinct()
                ->get();

            $CancelledEmployeeOB = [];
        } else {
            $approver_id = 0;
            $supervisor_id = 0;

            $ForapprovalEmployeeOB = [];
            $ApprovedEmployeeOB = [];
            $DisapprovedEmployeeOB = [];
            $CancelledEmployeeOB = [];
        }

        $requires_second_level = false;
        $requires_third_level = false;

        if ($emp_id > 0) {
            $requires_second_level = DB::table('approver_details as ad')
                ->join('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
                ->where('ad.employee_id', $emp_id)
                ->where('ah.type_id', 2) // Official Business type
                ->where('ah.approver_id_2', '>', 0)
                ->exists();

            $requires_third_level = DB::table('approver_details as ad')
                ->join('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
                ->where('ad.employee_id', $emp_id)
                ->where('ah.type_id', 2) // Official Business type
                ->where('ah.approver_id_3', '>', 0)
                ->exists();
        }

        $PendingOBQuery = DB::table('official_business_applications as a')
            ->leftJoin('employees as d', 'd.id', '=', 'a.processed_by')
            ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
            ->leftJoin('employees as f', 'f.id', '=', 'a.processed_by_3')
            ->leftJoin('ta_type as ta', function ($join) {
                $join->on('a.type_id', '=', 'ta.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('2'));
            })
            ->leftJoin('to_type as to_t', function ($join) {
                $join->on('a.type_id', '=', 'to_t.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('3'));
            })
            ->select(
                'a.*',
                DB::raw("CASE WHEN EXISTS (
                            SELECT 1
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                            WHERE ad.employee_id = a.employee_id
                              AND ah.type_id = 2
                              AND ISNULL(ah.approver_id_2,0) > 0
                        ) THEN 1 ELSE 0 END as requires_second_level"),
                DB::raw("CASE WHEN EXISTS (
                            SELECT 1
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                            WHERE ad.employee_id = a.employee_id
                              AND ah.type_id = 2
                              AND ISNULL(ah.approver_id_3,0) > 0
                        ) THEN 1 ELSE 0 END as requires_third_level"),
                DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name"),
                DB::raw("CASE
                            WHEN ISNULL(a.processed_by,0) > 0 THEN
                                CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN
                                        CONCAT(d.first_name,' ',d.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key'))
                                END
                            ELSE
                                (SELECT TOP 1
                                    CASE WHEN ISNULL(emp1.is_encrypted,0) = 0 THEN
                                            CONCAT(emp1.first_name,' ',emp1.last_name)
                                        ELSE
                                            RTRIM([dbo].[ufn_DecryptString](emp1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp1.last_name,'$app_key'))
                                    END
                                 FROM approver_details ad
                                 INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
                                 INNER JOIN employees emp1 ON emp1.id = ah.approver_id_1
                                 WHERE ad.employee_id = a.employee_id
                                 ORDER BY ah.id DESC)
                        END as approver_1"),
                DB::raw("CASE
                            WHEN ISNULL(a.processed_by_2,0) > 0 THEN
                                CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                        CONCAT(e.first_name,' ',e.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                                END
                            ELSE
                                (SELECT TOP 1
                                    CASE WHEN ISNULL(emp2.is_encrypted,0) = 0 THEN
                                            CONCAT(emp2.first_name,' ',emp2.last_name)
                                        ELSE
                                            RTRIM([dbo].[ufn_DecryptString](emp2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp2.last_name,'$app_key'))
                                    END
                                 FROM approver_details ad
                                 INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
                                 INNER JOIN employees emp2 ON emp2.id = ah.approver_id_2
                                 WHERE ad.employee_id = a.employee_id AND ISNULL(ah.approver_id_2,0) > 0
                                 ORDER BY ah.id DESC)
                        END as approver_2"),
                DB::raw("CASE
                            WHEN ISNULL(a.processed_by_3,0) > 0 THEN
                                CASE WHEN ISNULL(f.is_encrypted,0) = 0 THEN
                                        CONCAT(f.first_name,' ',f.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](f.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](f.last_name,'$app_key'))
                                END
                            ELSE
                                (SELECT TOP 1
                                    CASE WHEN ISNULL(emp3.is_encrypted,0) = 0 THEN
                                            CONCAT(emp3.first_name,' ',emp3.last_name)
                                        ELSE
                                            RTRIM([dbo].[ufn_DecryptString](emp3.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp3.last_name,'$app_key'))
                                    END
                                 FROM approver_details ad
                                 INNER JOIN approver_headers ah ON ah.id = ad.approver_id AND ah.type_id = 2
                                 INNER JOIN employees emp3 ON emp3.id = ah.approver_id_3
                                 WHERE ad.employee_id = a.employee_id AND ISNULL(ah.approver_id_3,0) > 0
                                 ORDER BY ah.id DESC)
                        END as approver_3")
            )
            ->where('a.employee_id', $emp_id)
            ->whereRaw('ISNULL(a.is_cancel,0) = 0')
            ->where('a.disapproved', false)
            ->where('a.disapproved_2', false)
            ->where('a.disapproved_3', false)
            ->where(function ($query) {
                $query->where(function ($basePending) {
                    $basePending->where('a.approved', false);
                })
                ->orWhere(function ($secondLevelPending) {
                    $secondLevelPending->where('a.approved', true)
                        ->where('a.approved_2', false)
                        ->where('a.disapproved_2', false);
                })
                ->orWhere(function ($thirdLevelPending) {
                    $thirdLevelPending->where('a.approved', true)
                        ->where('a.approved_2', true)
                        ->where('a.approved_3', false)
                        ->where('a.disapproved_3', false);
                });
            })
            ->orderby('a.date', 'desc');

        $PendingOB = $PendingOBQuery->get();

        // DEBUG: Log query results
        \Log::info('🔍 PendingOB Query Results:', [
            'count' => $PendingOB->count(),
            'first_record' => $PendingOB->first(),
            'sql' => $PendingOBQuery->toSql()
        ]);

        $ApprovedOBQuery = DB::table('official_business_applications as a')
            ->leftJoin('employees as d', 'd.id', '=', 'a.processed_by')
            ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
            ->leftJoin('employees as f', 'f.id', '=', 'a.processed_by_3')
            ->leftJoin('ta_type as ta', function ($join) {
                $join->on('a.type_id', '=', 'ta.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('2'));
            })
            ->leftJoin('to_type as to_t', function ($join) {
                $join->on('a.type_id', '=', 'to_t.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('3'));
            })
            ->select(
                'a.*',
                DB::raw("CASE WHEN EXISTS (
                            SELECT 1
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                            WHERE ad.employee_id = a.employee_id
                              AND ah.type_id = 2
                              AND ISNULL(ah.approver_id_2,0) > 0
                        ) THEN 1 ELSE 0 END as requires_second_level"),
                DB::raw("CASE WHEN EXISTS (
                            SELECT 1
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                            WHERE ad.employee_id = a.employee_id
                              AND ah.type_id = 2
                              AND ISNULL(ah.approver_id_3,0) > 0
                        ) THEN 1 ELSE 0 END as requires_third_level"),
                DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name"),
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
                            END as approver_3")
            )
            ->where([
                'a.approved' => true,
                'a.disapproved' => false,
                'a.disapproved_2' => false,
                'a.disapproved_3' => false,
                'a.employee_id' => $emp_id
            ]);

        if ($requires_second_level) {
            $ApprovedOBQuery->where('a.approved_2', true);
        }

        if ($requires_third_level) {
            $ApprovedOBQuery->where('a.approved_3', true);
        }

        $ApprovedOB = $ApprovedOBQuery
            ->orderby('a.date', 'desc')
            ->get();

        $DisapprovedOB = DB::table('official_business_applications as a')
            ->leftJoin('employees as d', 'd.id', '=', 'a.processed_by')
            ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
            ->leftJoin('employees as f', 'f.id', '=', 'a.processed_by_3')
            ->leftJoin('ta_type as ta', function ($join) {
                $join->on('a.type_id', '=', 'ta.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('2'));
            })
            ->leftJoin('to_type as to_t', function ($join) {
                $join->on('a.type_id', '=', 'to_t.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('3'));
            })
            ->select(
                'a.*',
                DB::raw("CASE WHEN EXISTS (
                            SELECT 1
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                            WHERE ad.employee_id = a.employee_id
                              AND ah.type_id = 2
                              AND ISNULL(ah.approver_id_2,0) > 0
                        ) THEN 1 ELSE 0 END as requires_second_level"),
                DB::raw("CASE WHEN EXISTS (
                            SELECT 1
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                            WHERE ad.employee_id = a.employee_id
                              AND ah.type_id = 2
                              AND ISNULL(ah.approver_id_3,0) > 0
                        ) THEN 1 ELSE 0 END as requires_third_level"),
                DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name"),
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
                            END as approver_3")
            )
            ->where('a.employee_id', $emp_id)
            ->where('a.disapproved', true)
            ->orWhere('a.disapproved_2', true)
            ->orderby('date', 'desc')
            ->get();

        $CancelledOB = DB::table('official_business_applications as a')
            ->leftJoin('employees as b', 'a.canceled_by', '=', 'b.id')
            ->leftJoin('employees as d', 'd.id', '=', 'a.processed_by')
            ->leftJoin('employees as e', 'e.id', '=', 'a.processed_by_2')
            ->leftJoin('employees as f', 'f.id', '=', 'a.processed_by_3')
            ->leftJoin('ta_type as ta', function ($join) {
                $join->on('a.type_id', '=', 'ta.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('2'));
            })
            ->leftJoin('to_type as to_t', function ($join) {
                $join->on('a.type_id', '=', 'to_t.id')
                    ->on(DB::raw('a.ob_type'), '=', DB::raw('3'));
            })
            ->select(
                'a.*',
                DB::raw("CASE WHEN EXISTS (
                            SELECT 1
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                            WHERE ad.employee_id = a.employee_id
                              AND ah.type_id = 2
                              AND ISNULL(ah.approver_id_2,0) > 0
                        ) THEN 1 ELSE 0 END as requires_second_level"),
                DB::raw("CASE WHEN EXISTS (
                            SELECT 1
                            FROM approver_details ad
                            INNER JOIN approver_headers ah ON ah.id = ad.approver_id
                            WHERE ad.employee_id = a.employee_id
                              AND ah.type_id = 2
                              AND ISNULL(ah.approver_id_3,0) > 0
                        ) THEN 1 ELSE 0 END as requires_third_level"),
                DB::raw("CASE WHEN a.ob_type = 2 THEN ta.name ELSE NULL END as ta_type_name"),
                DB::raw("CASE WHEN a.ob_type = 3 THEN to_t.name ELSE NULL END as to_type_name"),
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as cancelled_by"),
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
                            END as approver_3")
            )
            ->where(['approved' => false, 'disapproved' => false, 'is_cancel' => true, 'employee_id' => $emp_id])
            ->orderby('date', 'desc')
            ->get();

        // OB Attachments
        $ob_attachments = DB::table('ob_attachments')->orderBy('ob_id', 'asc')->get();

        // Check if With Approver for Official Business (type_id = 2).
        $with_approvers = DB::table('approver_details as ad')
            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
            ->where('ad.employee_id', $emp_id)
            ->where('ah.type_id', 2) // Official Business type
            ->get();

        if (count($with_approvers) > 0) {
            $allowed = 1;
        } else {
            $allowed = 0;
        }

        // DEBUG: Log response data
        \Log::info('🚀 Returning OB Data:', [
            'PendingOB_count' => $PendingOB->count(),
            'PendingOB_first' => $PendingOB->first(),
            'PendingOB_first_type_fields' => $PendingOB->first() ? [
                'ob_type' => $PendingOB->first()->ob_type,
                'type_id' => $PendingOB->first()->type_id,
                'ta_type_name' => $PendingOB->first()->ta_type_name ?? 'NULL',
                'to_type_name' => $PendingOB->first()->to_type_name ?? 'NULL'
            ] : null
        ]);

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
            $ob_type = isset($data['ob_type']) ? (int)$data['ob_type'] : null;

            $officialbusinessapplication = OfficialBusinessApplication::findOrNew($id);

            // Different validation rules for Request for Pickup (ob_type = 4)
            if ($ob_type === 4) {
                $validate = Validator::make($request->all(), [
                    'date' => 'required|date_format:Y-m-d|after:2000-01-01',
                    'from' => 'required|string',
                    'company' => 'required|string',
                    'address' => 'required|string',
                    'contact_no' => 'required|string',
                    'documents_materials' => 'required|string',
                ], [
                    'date.date_format' => 'Date invalid date.',
                    'from.required' => 'From field is required.',
                    'company.required' => 'Company field is required.',
                    'address.required' => 'Address field is required.',
                    'contact_no.required' => 'Contact No. field is required.',
                    'documents_materials.required' => 'Type of Documents/Materials field is required.',
                ]);
            } else {
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
            }

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

            // Sync resolved employee_id back into data array
            $data['employee_id'] = $employee_id;
            $request->merge(['employee_id' => $employee_id]);

            // Check if employee has approver configured.
            // OB Slip/Pass Slip approver setup uses type_id = 5
            // Travel Authority/Travel Order uses type_id = 2
            // Request for Pickup uses type_id = 6
            $approver_type_id = ($ob_type === 4) ? 6 : 2;
            $has_approver = DB::table('approver_details as ad')
                ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                ->where('ad.employee_id', $employee_id)
                ->where('ah.type_id', $approver_type_id)
                ->exists();

            if (!$has_approver) {
                \Log::info('No explicit approver detail for OB/Travel type_id=' . $approver_type_id . ', allowing filing with default approver workflow.');
            }

            // Limit Official Business applications to 3 per month per employee
            // IMPORTANT: Do not enforce (and do not count) Request for Pick-up (ob_type = 4)
            // Use the OB date to determine the month/year
            if ($ob_type !== 4 && isset($data['date']) && !empty($data['date'])) {
                try {
                    $obDate = \Carbon\Carbon::parse($data['date']);
                    $startOfMonth = $obDate->copy()->startOfMonth()->format('Y-m-d');
                    $endOfMonth = $obDate->copy()->endOfMonth()->format('Y-m-d');

                    $existingCount = DB::table('official_business_applications')
                        ->where('employee_id', $employee_id)
                        ->whereBetween('date', [$startOfMonth, $endOfMonth])
                        ->where('is_cancel', false)
                        ->where('ob_type', '!=', 4)
                        ->when($id != 0, function ($query) use ($id) {
                            // Exclude current record when updating
                            $query->where('id', '!=', $id);
                        })
                        ->count();

                    // Only enforce limit on new applications (id == 0)
                    if ($id == 0 && $existingCount >= 3) {
                        return response()->json(['error' => 'You have reached the maximum of 3 Official Business applications for this month.'], 400);
                    }
                } catch (\Exception $e) {
                    // If date parsing fails, let the normal validation handle it
                }
            }

            // For Request for Pickup (ob_type = 4), set minimal data
            if ($ob_type === 4) {
                $ob_data = array(
                    'employee_id' => $employee_id,
                    'date' => $data['date'],
                    'ob_type' => 4, // Request for Pick-up
                    'date_time_from' => null,
                    'date_time_to' => null,
                    'purpose' => 'Request for Pick-up',
                    'client' => isset($data['company']) ? $data['company'] : null,
                );
            } else {
                // Normalize recommending_approval so that we always store a string name,
                // even if the frontend sends an employees.id value.
                $recommendingApprovalValue = $data['recommending_approval'] ?? null;
                $recommendingApproval = null;

                if (!is_null($recommendingApprovalValue) && $recommendingApprovalValue !== '') {
                if (is_numeric($recommendingApprovalValue)) {
                    // Look up employee name parts by ID and build a formatted display string
                    $emp = DB::table('employees as a')
                        ->select(
                            'a.id',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')) END as first_name"),
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE RTRIM([dbo].[ufn_DecryptString](a.middle_name,'$app_key')) END as middle_name"),
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) END as last_name")
                        )
                        ->where('a.id', (int)$recommendingApprovalValue)
                        ->first();

                    if ($emp) {
                        $first = $this->formatNamePart($emp->first_name ?? '');
                        $middle = $this->formatMiddleInitial($emp->middle_name ?? '');
                        $last = $this->formatLastName($emp->last_name ?? '');
                        $recommendingApproval = trim(implode(' ', array_filter([$first, $middle, $last])));
                    } else {
                        $recommendingApproval = (string)$recommendingApprovalValue;
                    }
                    } else {
                        // Already a string – store as-is
                        $recommendingApproval = (string)$recommendingApprovalValue;
                    }
                }

                $ob_data = array(
                    'employee_id' => $employee_id, // Use the mapped employee_id
                    'date' => $data['date'],
                    'date_time_from' => date("Y/m/d H:i:s", strtotime($data['date_time_from'])),
                    'date_time_to' => date("Y/m/d H:i:s", strtotime($data['date_time_to'])),
                    'purpose' => $data['purpose'],
                    'ob_type' => (int)$data['ob_type'], // Convert to integer
                    'client' => $data['client'], // Add client field
                    'telephone_numbers' => $data['telephone_numbers'] ?? null,
                    'recommending_approval' => $recommendingApproval,
                    'recommending_position' => $data['recommending_position'],
                    'approver' => $data['approver'],
                );

                // Add type_id if provided (for Travel Order)
                if (isset($data['type_id']) && !empty($data['type_id'])) {
                    $ob_data['type_id'] = (int)$data['type_id'];
                }
            }

            $officialbusinessapplication->fill($ob_data);
            $officialbusinessapplication->save();

            if ($id == 0) {
                $ob_id = DB::table('official_business_applications')->max('id');
            } else {
                $ob_id = $id;
            }

            // Save Request for Pickup details if ob_type is 4
            if ($ob_type === 4) {
                // Check if request_for_pickup record already exists
                $requestForPickup = RequestForPickup::where('ob_id', $ob_id)->first();

                if (!$requestForPickup) {
                    $requestForPickup = new RequestForPickup();
                    $requestForPickup->ob_id = $ob_id;
                }

                // Resolve requested by employee
                $requestedByEmployeeId = isset($data['requested_by_employee_id']) && !empty($data['requested_by_employee_id'])
                    ? (int)$data['requested_by_employee_id']
                    : $employee_id; // fall back to the current employee

                $requestedByEmployee = null;
                $requestedByDisplay = null;

                if ($requestedByEmployeeId) {
                    $requestedByEmployee = DB::table('employees as a')
                        ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                        ->select(
                            'a.id',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                        CONCAT(a.first_name,' ',COALESCE(SUBSTRING(a.middle_name,1,1),''),'. ',a.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(COALESCE(SUBSTRING([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1),''))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                                    END as name"),
                            'd.name as division_name'
                        )
                        ->where('a.id', $requestedByEmployeeId)
                        ->first();

                    if ($requestedByEmployee) {
                        $requestedByDisplay = $requestedByEmployee->division_name
                            ? $requestedByEmployee->name . ' / ' . $requestedByEmployee->division_name
                            : $requestedByEmployee->name;
                    }
                }

                $requestForPickup->from = isset($data['from']) ? $data['from'] : null;
                $requestForPickup->company = isset($data['company']) ? $data['company'] : null;
                $requestForPickup->address = isset($data['address']) ? $data['address'] : null;
                $requestForPickup->contact_no = isset($data['contact_no']) ? $data['contact_no'] : null;
                $requestForPickup->documents_materials = isset($data['documents_materials']) ? $data['documents_materials'] : null;
                $requestForPickup->picked_up_by = isset($data['picked_up_by']) ? $data['picked_up_by'] : null;
                $requestForPickup->pickup_date = isset($data['pickup_date']) && !empty($data['pickup_date']) ? $data['pickup_date'] : null;
                $requestForPickup->requested_by_employee_id = $requestedByEmployee ? $requestedByEmployee->id : null;
                $requestForPickup->requested_by = $requestedByDisplay ?? (isset($data['requested_by']) ? $data['requested_by'] : null);
                $requestForPickup->received_by = isset($data['received_by']) ? $data['received_by'] : null;
                $requestForPickup->date = isset($data['date']) ? $data['date'] : null;

                $requestForPickup->save();
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

            // Base validation rules
            $rules = [
                'date' => 'required|date_format:Y-m-d|after:2000-01-01',
                'date_time_from' => 'required|date_format:"Y-m-d\TH:i"|after:2000-01-01 00:00',
                'date_time_to' => 'required|date_format:Y-m-d\TH:i|after:2000-01-01 00:00',
                'client' => 'required',
                'ob_type' => 'required',
            ];

            // For Travel Authority (ob_type = 2) and type_id not equal to 1 ("Personal"),
            // require funds and purpose. For Personal Travel Authority (type_id = 1),
            // only From/To/Destination are required.
            $obType = isset($data['ob_type']) ? (int)$data['ob_type'] : null;
            $typeId = isset($data['type_id']) ? (int)$data['type_id'] : null;
            if (!($obType === 2 && $typeId === 1)) {
                $rules['funds'] = 'required';
                $rules['purpose'] = 'required';
            }

            $validate = Validator::make($request->all(), $rules, [
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

            // Check if employee has approver configured for Official Business (type_id = 2)
            $has_approver = DB::table('approver_details as ad')
                ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                ->where('ad.employee_id', $employee_id)
                ->where('ah.type_id', 2) // Official Business type
                ->exists();

            if (!$has_approver) {
                return response()->json(['error' => 'You cannot apply for official business. No approver has been configured for official business applications.'], 400);
            }

            // Get approver information from approver_headers for Travel Authority
            $recommending_approval = '';
            $recommending_position = '';
            $approver = '';
            $approver_position = '';

            $approver_setup = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->leftJoin('employees as e1', 'a.approver_id_1', '=', 'e1.id')
                ->leftJoin('employees as e2', 'a.approver_id_2', '=', 'e2.id')
                ->leftJoin('positions as p1', 'e1.position_id', '=', 'p1.id')
                ->leftJoin('positions as p2', 'e2.position_id', '=', 'p2.id')
                ->where('b.employee_id', $employee_id)
                ->where('a.type_id', 2) // Official Business type
                ->select(
                    DB::raw("CASE WHEN ISNULL(e1.is_encrypted,0) = 0 THEN
                                CONCAT(e1.first_name,' ',e1.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e1.last_name,'$app_key'))
                            END as recommending_approval"),
                    'p1.name as recommending_position',
                    DB::raw("CASE WHEN ISNULL(e2.is_encrypted,0) = 0 THEN
                                CONCAT(e2.first_name,' ',e2.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e2.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e2.last_name,'$app_key'))
                            END as approver"),
                    'p2.name as approver_position'
                )
                ->first();

            if ($approver_setup) {
                $recommending_approval = $approver_setup->recommending_approval ?? '';
                $recommending_position = $approver_setup->recommending_position ?? '';
                $approver = $approver_setup->approver ?? '';
                $approver_position = $approver_setup->approver_position ?? '';
            }

            // Override approver for Personal Travel Authority (type_id = 1)
            // Default approver: CLARE MARI S. TORRALBA, Executive Director
            $obType = isset($data['ob_type']) ? (int)$data['ob_type'] : null;
            $typeId = isset($data['type_id']) ? (int)$data['type_id'] : null;
            if ($obType === 2 && $typeId === 1) {
                $approver = 'CLARE MARI S. TORRALBA';
                $approver_position = 'Executive Director';
            }

            // Normalize recommending_approval so that we always store a string name,
            // even if the frontend sends an employees.id value. If nothing is provided
            // from the form, fall back to the approver setup default.
            $recommendingApprovalValue = $data['recommending_approval'] ?? null;
            $normalizedRecommendingApproval = $recommending_approval; // default from approver setup

            if (!is_null($recommendingApprovalValue) && $recommendingApprovalValue !== '') {
                if (is_numeric($recommendingApprovalValue)) {
                    // Look up employee name parts by ID and build a formatted display string
                    $emp = DB::table('employees as a')
                        ->select(
                            'a.id',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')) END as first_name"),
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE RTRIM([dbo].[ufn_DecryptString](a.middle_name,'$app_key')) END as middle_name"),
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) END as last_name")
                        )
                        ->where('a.id', (int)$recommendingApprovalValue)
                        ->first();

                    if ($emp) {
                        $first = $this->formatNamePart($emp->first_name ?? '');
                        $middle = $this->formatMiddleInitial($emp->middle_name ?? '');
                        $last = $this->formatLastName($emp->last_name ?? '');
                        $normalizedRecommendingApproval = trim(implode(' ', array_filter([$first, $middle, $last])));
                    } else {
                        $normalizedRecommendingApproval = (string)$recommendingApprovalValue;
                    }
                } else {
                    // Already a string – format and store
                    $normalizedRecommendingApproval = $this->formatNamePart($recommendingApprovalValue);
                }
            } else {
                // If coming from approver setup, normalize that as well
                $normalizedRecommendingApproval = $this->formatNamePart($normalizedRecommendingApproval);
            }

            $ob_data = array(
                'employee_id' => $employee_id, // Use the mapped employee_id
                'date' => $data['date'],
                'date_time_from' => date("Y/m/d H:i:s", strtotime($data['date_time_from'])),
                'date_time_to' => date("Y/m/d H:i:s", strtotime($data['date_time_to'])),
                'ob_type' => (int)$data['ob_type'], // Convert to integer
                'funds' => $data['funds'],
                'recommending_approval' => $normalizedRecommendingApproval,
                'recommending_position' => $recommending_position,
                'approver' => $approver,
                'approver_position' => $approver_position,
                'client' => $data['client'],
                'telephone_numbers' => $data['telephone_numbers'] ?? null,
                'purpose' => $data['purpose'],
            );

            // Add type_id if provided (for Travel Authority)
            if (isset($data['type_id']) && $data['type_id'] !== null && $data['type_id'] !== '' && $data['type_id'] !== 'null' && $data['type_id'] !== 0) {
                $ob_data['type_id'] = (int)$data['type_id'];
            }

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

        $ob_to_approved = DB::table('official_business_applications')->select('employee_id', 'ob_type', 'type_id')->where('id', $id)->first();

        if ($ob_to_approved) {
            $emp_id = $ob_to_approved->employee_id;
        } else {
            $emp_id = 0;
        }

        $ob_type = $ob_to_approved ? (int)($ob_to_approved->ob_type ?? 0) : 0;
        $ob_type_id = $ob_to_approved ? (int)($ob_to_approved->type_id ?? 0) : 0;
        $is_travel_authority = ($ob_type === 2 && $ob_type_id === 2);
        $is_request_pickup = ($ob_type === 4);
        $approver_header_type_id = $is_request_pickup ? 6 : 2;
        $is_request_pickup = ($ob_type === 4);
        $approver_header_type_id = $is_request_pickup ? 6 : 2;

        // Check if Approver.
        // - For Travel Authority (ob_type=2 & type_id=2): only approver_id_1/2/3 from approver_headers.type_id=2
        // - For Request for Pickup (ob_type=4): only approver_id_1/2/3 from approver_headers.type_id=6
        // - Otherwise: existing branch/division/section logic.
        if ($is_travel_authority || $is_request_pickup) {
            $approver_1 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select('a.id', 'a.approver_id_1 as supervisor_id')
                ->where('b.employee_id', $emp_id)
                ->where('a.type_id', $approver_header_type_id)
                ->where('a.approver_id_1', $approver_emp_id)
                ->distinct()
                ->get();
        } else {
            $approver_1 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select('a.id', 'a.approver_id_1 as supervisor_id')
                ->whereRaw("
                            b.employee_id = $emp_id
                            AND (a.branch_approver_id_1 = $approver_emp_id OR a.approver_id_1  = $approver_emp_id or a.division_approver_id_1 = $approver_emp_id or a.section_approver_id_1 = $approver_emp_id)
                        ")
                ->distinct()
                ->get();
        }

        $approver_2 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.approver_id_2 as supervisor_id'
            )
            ->where('b.employee_id', $emp_id)
            ->where('a.approver_id_2', $approver_emp_id)
            ->where(function ($q) use ($is_travel_authority) {
                if ($is_travel_authority) {
                    $q->where('a.type_id', 2);
                }
            })
            ->where(function ($q) use ($is_request_pickup) {
                if ($is_request_pickup) {
                    $q->where('a.type_id', 6);
                }
            })
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
            ->where(function ($q) use ($is_travel_authority) {
                if ($is_travel_authority) {
                    $q->where('a.type_id', 2);
                }
            })
            ->where(function ($q) use ($is_request_pickup) {
                if ($is_request_pickup) {
                    $q->where('a.type_id', 6);
                }
            })
            ->distinct()
            ->get();

        $process_date = date("Y-m-d", strtotime(now()));

        // Get current application state to determine which approval level to set
        $current_ob = DB::table('official_business_applications')
            ->select('approved', 'approved_2', 'approved_3', 'disapproved', 'disapproved_2', 'disapproved_3', 'processed_by', 'processed_by_2')
            ->where('id', $id)
            ->first();

        // For Travel Authority (ob_type=2, type_id=2): only approvers from approver_headers can act; hierarchy enforced below.
        if (($is_travel_authority || $is_request_pickup) && $approver_1->isEmpty() && $approver_2->isEmpty() && $approver_3->isEmpty()) {
            return response()->json(['error' => 'You are not authorized to approve this application.'], 403);
        }

        // Check approver 3 first - only if approved and approved_2 are already true (hierarchy: level 3 after level 2)
        if ($approver_3->isNotEmpty() && $current_ob && $current_ob->approved == true && $current_ob->approved_2 == true && ($current_ob->approved_3 == false || $current_ob->approved_3 == null)) {
            // Third level approver - set only third level approval
            $ob_data = array(
                'approved_3' => true,
                'disapproved_3' => false,
                'processed_date_3' => $process_date,
                'processed_by_3' => $approver_emp_id,
                'approved_remarks' => $remarks
            );
        } elseif ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
            // Only first level approver
            $ob_data = array(
                'approved' => true,
                'disapproved' => false,
                'processed_date' => $process_date,
                'processed_by' => $approver_emp_id,
                'approved_remarks' => $remarks
            );
        } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty() && $current_ob && ($current_ob->approved == true || $current_ob->disapproved == true)) {
            // Only second level approver - hierarchy: level 1 must have responded first
            $ob_data = array(
                'approved_2' => true,
                'disapproved_2' => false,
                'processed_date_2' => $process_date,
                'processed_by_2' => $approver_emp_id,
                'approved_remarks' => $remarks
            );
        } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
            // User is both first and second level approver - check current state
            if ($current_ob && $current_ob->approved == false) {
                // First approval not done yet - set only first level approval
                $ob_data = array(
                    'approved' => true,
                    'disapproved' => false,
                    'processed_date' => $process_date,
                    'processed_by' => $approver_emp_id,
                    'approved_remarks' => $remarks
                );
            } elseif ($current_ob && $current_ob->approved == true && ($current_ob->approved_2 == false || $current_ob->approved_2 == null)) {
                // First approval done, second not done - set only second level approval
                $ob_data = array(
                    'approved_2' => true,
                    'disapproved_2' => false,
                    'processed_date_2' => $process_date,
                    'processed_by_2' => $approver_emp_id,
                    'approved_remarks' => $remarks
                );
            } else {
                // Both already approved or unexpected state - set only first level (fallback)
                $ob_data = array(
                    'approved' => true,
                    'disapproved' => false,
                    'processed_date' => $process_date,
                    'processed_by' => $approver_emp_id,
                    'approved_remarks' => $remarks
                );
            }
        } elseif ($approver_3->isNotEmpty()) {
            // Approver 3 but previous approvals not done - this shouldn't happen, but handle it
            $ob_data = array(
                'approved_3' => true,
                'disapproved_3' => false,
                'processed_date_3' => $process_date,
                'processed_by_3' => $approver_emp_id,
                'approved_remarks' => $remarks
            );
        }

        if (!isset($ob_data)) {
            return response()->json(['error' => 'You cannot approve this application. You are not an approver for it, or the previous approval level must respond first.'], 403);
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

        return response()->json([
            'success' => true,
            'message' => 'You have successfully approved official business application!'
        ]);
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

        $ob_to_approved = DB::table('official_business_applications')->select('employee_id', 'ob_type', 'type_id')->where('id', $id)->first();

        if ($ob_to_approved) {
            $emp_id = $ob_to_approved->employee_id;
        } else {
            $emp_id = 0;
        }

        $ob_type = $ob_to_approved ? (int)($ob_to_approved->ob_type ?? 0) : 0;
        $ob_type_id = $ob_to_approved ? (int)($ob_to_approved->type_id ?? 0) : 0;
        $is_travel_authority = ($ob_type === 2 && $ob_type_id === 2);

        // Check if Approver.
        // - For Travel Authority (ob_type=2 & type_id=2): only approver_id_1/2/3 from approver_headers.type_id=2
        // - For Request for Pickup (ob_type=4): only approver_id_1/2/3 from approver_headers.type_id=6
        // - Otherwise: existing branch/division/section logic.
        if ($is_travel_authority || $is_request_pickup) {
            $approver_1 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select('a.id', 'a.approver_id_1 as supervisor_id')
                ->where('b.employee_id', $emp_id)
                ->where('a.type_id', $approver_header_type_id)
                ->where('a.approver_id_1', $approver_emp_id)
                ->distinct()
                ->get();
        } else {
            $approver_1 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select('a.id', 'a.approver_id_1 as supervisor_id')
                ->whereRaw("
                            b.employee_id = $emp_id
                            AND (a.branch_approver_id_1 = $approver_emp_id OR a.approver_id_1  = $approver_emp_id or a.division_approver_id_1 = $approver_emp_id or a.section_approver_id_1 = $approver_emp_id)
                        ")
                ->distinct()
                ->get();
        }

        $approver_2 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_2 as supervisor_id')
            ->where('b.employee_id', $emp_id)
            ->where('a.approver_id_2', $approver_emp_id)
            ->where(function ($q) use ($is_travel_authority) {
                if ($is_travel_authority) {
                    $q->where('a.type_id', 2);
                }
            })
            ->where(function ($q) use ($is_request_pickup) {
                if ($is_request_pickup) {
                    $q->where('a.type_id', 6);
                }
            })
            ->distinct()
            ->get();

        $approver_3 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_3 as supervisor_id')
            ->where('b.employee_id', $emp_id)
            ->where('a.approver_id_3', $approver_emp_id)
            ->where(function ($q) use ($is_travel_authority) {
                if ($is_travel_authority) {
                    $q->where('a.type_id', 2);
                }
            })
            ->where(function ($q) use ($is_request_pickup) {
                if ($is_request_pickup) {
                    $q->where('a.type_id', 6);
                }
            })
            ->distinct()
            ->get();

        $process_date = date("Y-m-d", strtotime(now()));

        // Get current application state to determine which disapproval level to set
        $current_ob = DB::table('official_business_applications')
            ->select('approved', 'approved_2', 'approved_3', 'disapproved', 'disapproved_2', 'disapproved_3')
            ->where('id', $id)
            ->first();

        if (($is_travel_authority || $is_request_pickup) && $approver_1->isEmpty() && $approver_2->isEmpty() && $approver_3->isEmpty()) {
            return response()->json(['error' => 'You are not authorized to disapprove this application.'], 403);
        }

        // Check approver 3 first - only if approved and approved_2 are already true (hierarchy)
        if ($approver_3->isNotEmpty() && $current_ob && $current_ob->approved == true && $current_ob->approved_2 == true && ($current_ob->disapproved_3 == false || $current_ob->disapproved_3 == null)) {
            // Third level approver - set only third level disapproval
            $ob_data = array(
                'approved_3' => false,
                'disapproved_3' => true,
                'processed_date_3' => $process_date,
                'processed_by_3' => $approver_emp_id,
                'disapproved_3_remark' => $remarks
            );
        } elseif ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
            // Only first level approver
            $ob_data = array(
                'approved' => false,
                'disapproved' => true,
                'processed_date' => $process_date,
                'processed_by' => $approver_emp_id,
                'disapproved_remark' => $remarks
            );
        } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty() && $current_ob && ($current_ob->approved == true || $current_ob->disapproved == true)) {
            // Only second level approver - hierarchy: level 1 must have responded first
            $ob_data = array(
                'approved_2' => false,
                'disapproved_2' => true,
                'processed_date_2' => $process_date,
                'processed_by_2' => $approver_emp_id,
                'disapproved_2_remark' => $remarks
            );
        } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
            // User is both first and second level approver - check current state
            if ($current_ob && ($current_ob->approved == false || $current_ob->disapproved == true)) {
                // First level not approved yet or already disapproved - set only first level disapproval
                $ob_data = array(
                    'approved' => false,
                    'disapproved' => true,
                    'processed_date' => $process_date,
                    'processed_by' => $approver_emp_id,
                    'disapproved_remark' => $remarks
                );
            } elseif ($current_ob && $current_ob->approved == true && ($current_ob->approved_2 == false || $current_ob->approved_2 == null) && ($current_ob->disapproved_2 == false || $current_ob->disapproved_2 == null)) {
                // First approval done, second not done - set only second level disapproval
                $ob_data = array(
                    'approved_2' => false,
                    'disapproved_2' => true,
                    'processed_date_2' => $process_date,
                    'processed_by_2' => $approver_emp_id,
                    'disapproved_2_remark' => $remarks
                );
            } else {
                // Unexpected state - set only first level disapproval (fallback)
                $ob_data = array(
                    'approved' => false,
                    'disapproved' => true,
                    'processed_date' => $process_date,
                    'processed_by' => $approver_emp_id,
                    'disapproved_remark' => $remarks
                );
            }
        } elseif ($approver_3->isNotEmpty()) {
            // Approver 3 but previous approvals not done - this shouldn't happen, but handle it
            $ob_data = array(
                'approved_3' => false,
                'disapproved_3' => true,
                'processed_date_3' => $process_date,
                'processed_by_3' => $approver_emp_id,
                'disapproved_3_remark' => $remarks
            );
        }

        if (!isset($ob_data)) {
            return response()->json(['error' => 'You cannot disapprove this application. You are not an approver for it, or the previous approval level must respond first.'], 403);
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

        return response()->json([
            'success' => true,
            'message' => 'You have successfully disapproved official business application!'
        ]);
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

        return response()->json([
            'success' => true,
            'message' => 'You have canceled official business application!'
        ]);
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

    /**
     * Get list of Budget Officers (or all active employees) for Travel Authority recommending approval.
     * Accessible to all authenticated portal users (no HR-only restriction).
     */
    public function getBudgetOfficers()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->unauthorizedResponse('Unauthorized');
            }

            $app_key = env("APP_KEY", "");

            // Basic list of active employees from employees table.
            // If you later want to restrict to specific positions (e.g. containing 'Budget'),
            // you can join positions table and add appropriate where clauses here.
            $employees = DB::table('employees as a')
                ->select(
                    'a.id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')) END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE RTRIM([dbo].[ufn_DecryptString](a.middle_name,'$app_key')) END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) END as last_name")
                )
                ->where('a.is_employee', true)
                ->where('a.active', true)
                ->orderBy('a.last_name', 'asc')
                ->get();

            // Format names as "First M. Last" without prefixes/suffixes
            $formatted = $employees->map(function ($emp) {
                $first = $this->formatNamePart($emp->first_name ?? '');
                $middle = $this->formatMiddleInitial($emp->middle_name ?? '');
                $last = $this->formatLastName($emp->last_name ?? '');

                $parts = array_filter([$first, $middle, $last]);

                return [
                    'id' => $emp->id,
                    'employee_no' => $emp->employee_no,
                    'name' => implode(' ', $parts),
                ];
            });

            return $this->successResponse($formatted, 'Budget officers retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve budget officers: ' . $e->getMessage());
        }
    }

    /**
     * Format name parts into "First M. Last" with proper casing.
     */
    private function formatNamePart(?string $name): string
    {
        $name = trim((string)$name);
        if ($name === '') {
            return '';
        }

        // Normalize to title case (handles ALL CAPS, all lower, etc.)
        $name = mb_strtolower($name, 'UTF-8');
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    private function formatMiddleInitial(?string $middle): string
    {
        $middle = trim((string)$middle);
        if ($middle === '') {
            return '';
        }

        $initial = mb_substr($middle, 0, 1, 'UTF-8');
        return mb_strtoupper($initial, 'UTF-8') . '.';
    }

    private function formatLastName(?string $last): string
    {
        $last = trim((string)$last);
        if ($last === '') {
            return '';
        }

        // Title-case each word in the last name (e.g., "DE LA CRUZ" -> "De La Cruz")
        $last = mb_strtolower($last, 'UTF-8');
        return mb_convert_case($last, MB_CASE_TITLE, 'UTF-8');
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
            // Join approver_headers via approver_details to get approver_id_1 for OB Slip / Pass Slip (type_id = 5)
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
                'a.telephone_numbers',
                'a.ob_type',
                'a.purpose',
                'a.recommending_approval',
                'a.approver_position',
                'a.approver',
                'a.recommending_position',
                'a.recommending_position',
                'a.approver',
                // Supervisor (approver_id_1 for type_id=5) in format: First Name M.I. Last Name
                DB::raw("CASE WHEN ISNULL(appr1.is_encrypted,0) = 0 THEN
                                CASE WHEN ISNULL(appr1.middle_name,'') = '' THEN
                                    CONCAT(appr1.first_name,' ',appr1.last_name)
                                ELSE
                                    CONCAT(appr1.first_name,' ',substring(appr1.middle_name,1,1),'. ',appr1.last_name)
                                END
                            ELSE
                                CASE WHEN ISNULL(appr1.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString](appr1.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](appr1.last_name,'$app_key'))
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](appr1.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](appr1.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](appr1.last_name,'$app_key'))
                                END
                            END as approved_by_name"),
                // Employee name in format: First Name M.I. Last Name (kept uppercase as in existing layout)
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CASE WHEN ISNULL(b.middle_name,'') = '' THEN
                                    UPPER(CONCAT(b.first_name,' ',b.last_name))
                                ELSE
                                    UPPER(CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name))
                                END
                            ELSE
                                CASE WHEN ISNULL(b.middle_name,'') = '' THEN
                                    UPPER(RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')))
                                ELSE
                                    UPPER(RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1)+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')))
                                END
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
                'a.type_id',
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
        // Travel Authority specific header / footer images
        $taLogo = null;
        $taFooter = null;
        $taLogoPath = resource_path('img/TA-logo.png');
        $taFooterPath = resource_path('img/TA-footer.png');
        if (file_exists($taLogoPath)) {
            $taLogo = base64_encode(file_get_contents($taLogoPath));
        }
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
        if ($ob->isNotEmpty() && (int)$ob[0]->ob_type === 2) {
            if ((int)$ob[0]->type_id === 1) {
                // Personal Travel Authority
                $pdf = PDF::loadView(
                    'official_business_application.travel_authority_personal_report',
                    compact('ob', 'footer', 'taLogo', 'taFooter')
                )->setOptions(['defaultFont' => 'sans-serif']);
            } elseif ((int)$ob[0]->type_id === 2) {
                // Official business / Annex F Travel Authority
                $pdf = PDF::loadView(
                    'official_business_application.travel_authority_report',
                    compact('ob', 'footer', 'taLogo', 'taFooter')
                )->setOptions(['defaultFont' => 'sans-serif']);
            } else {
                // Unknown TA type, fall back
                $pdf = PDF::loadView(
                    'official_business_application.unofficial_business_report',
                    compact('ob', 'footer', 'image')
                )->setOptions(['defaultFont' => 'sans-serif']);
            }
        } else {
            // Non-TA: use existing unofficial business report
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
                'a.type_id',
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

        // Use TA header/footer logos for Travel Order as well
        $taLogo = null;
        $taFooter = null;
        $taLogoPath = resource_path('img/TA-logo.png');
        $taFooterPath = resource_path('img/TA-footer.png');
        if (file_exists($taLogoPath)) {
            $taLogo = base64_encode(file_get_contents($taLogoPath));
        }
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

        // Validation:
        // - If ob_type = 3 and branch is LDSD → use LDSD Travel Order report
        // - If ob_type = 3 and type_id = 2 → use LDSD Travel Order report
        // - If ob_type = 3 and type_id = 3 → use Personal Leave Travel Order report
        // - If ob_type = 3 and type_id = 4 → use Annex A Travel Order report
        // - Otherwise (other ob_type = 3) → legacy order_business_report
        if ($ob->isNotEmpty() && (int)$ob[0]->ob_type === 3) {
            $branch = $ob[0]->branch ?? '';
            $typeId = (int)($ob[0]->type_id ?? 0);

            // Check if branch is Learning and Development Support Division or type_id = 2
            if (
                stripos($branch, 'Learning and Development Support Division') !== false ||
                stripos($branch, 'LDSD') !== false ||
                $typeId === 2
            ) {
                // LDSD Travel Order report
                $pdf = PDF::loadView(
                    'official_business_application.travel_order_LDSD_report',
                    compact('ob', 'footer', 'taLogo', 'taFooter')
                )->setOptions(['defaultFont' => 'sans-serif']);
                $pdf->setPaper('A4');
            } elseif ($typeId === 3) {
                // Travel Order report (ob_type=3, type_id=3) from travel_order module view
                $headerImage = null;
                $footerImage = null;
                $headerPath = resource_path('img/Header.jpg');
                $footerPath = resource_path('img/Footer.jpg');
                if (file_exists($headerPath)) {
                    $headerImage = base64_encode(file_get_contents($headerPath));
                }
                if (file_exists($footerPath)) {
                    $footerImage = base64_encode(file_get_contents($footerPath));
                }

                // Map OB record to the expected variables of travel_order.travel_order view
                $rec = $ob[0];
                $fromDate = $rec->date_time_from ? \Carbon\Carbon::parse($rec->date_time_from)->format('F d, Y') : '';
                $toDate = $rec->date_time_to ? \Carbon\Carbon::parse($rec->date_time_to)->format('F d, Y') : '';
                $travel_dates = trim($fromDate . ($toDate && $toDate !== $fromDate ? ' - ' . $toDate : ''));
                $employee_name = $rec->name ?? '';
                $designation = $rec->position ?? '';
                $destination = $rec->client ?? '';
                $purpose = $rec->purpose ?? '';
                $date = $rec->date ? \Carbon\Carbon::parse($rec->date)->format('F d, Y') : '';
                $memo_number = null;
                $series_year = \Carbon\Carbon::now()->format('Y');
                $pronoun = 'her';

                $budget_officer = $rec->recommending_approval ?? '';
                $budget_officer_position = 'Budget Officer';

                // Approver comes from approver_headers where type_id=2 via approver_details (same as other TO/TA reports)
                $approver_name = $rec->approver ?? '';
                $approver_position = $rec->approver_position ?? '';

                try {
                    $appKey = env('APP_KEY', '');
                    $toApprover = DB::table('official_business_applications as obx')
                        ->join('approver_details as ad', 'ad.employee_id', '=', 'obx.employee_id')
                        ->join('approver_headers as ah', function ($join) {
                            $join->on('ah.id', '=', 'ad.approver_id')
                                ->where('ah.type_id', 2);
                        })
                        ->join('employees as emp', 'ah.approver_id_1', '=', 'emp.id')
                        ->leftJoin('positions as pos', 'emp.position_id', '=', 'pos.id')
                        ->where('obx.id', $rec->id)
                        ->orderBy('ah.id', 'desc')
                        ->selectRaw("
                            CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.first_name
                                ELSE RTRIM([dbo].[ufn_DecryptString](emp.first_name,'$appKey')) END as first_name,
                            CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.middle_name
                                ELSE RTRIM([dbo].[ufn_DecryptString](emp.middle_name,'$appKey')) END as middle_name,
                            CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.last_name
                                ELSE RTRIM([dbo].[ufn_DecryptString](emp.last_name,'$appKey')) END as last_name,
                            pos.name as position
                        ")
                        ->first();

                    if ($toApprover) {
                        $first = ucwords(strtolower(trim($toApprover->first_name ?? '')));
                        $last = ucwords(strtolower(trim($toApprover->last_name ?? '')));
                        $middle = trim($toApprover->middle_name ?? '');
                        $middleInitial = $middle !== '' ? strtoupper(mb_substr($middle, 0, 1, 'UTF-8')) . '.' : '';
                        $approver_name = trim(implode(' ', array_filter([$first, $middleInitial, $last])));
                        $approver_position = $toApprover->position ?: $approver_position;
                    }
                } catch (\Throwable $e) {
                    // keep fallback
                }

                $date_of_approval = $date;

                $pdf = PDF::loadView(
                    'travel_order.travel_order',
                    compact(
                        'headerImage',
                        'footerImage',
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
                        'date_of_approval'
                    )
                )->setOptions(['defaultFont' => 'sans-serif']);
                $pdf->setPaper('A4');
            } elseif ($typeId === 4) {
                // New Annex A Travel Order report with TA header/footer logos
                $pdf = PDF::loadView(
                    'official_business_application.travel_order_report',
                    compact('ob', 'footer', 'taLogo', 'taFooter')
                )->setOptions(['defaultFont' => 'sans-serif']);
                $pdf->setPaper('A4');
            } else {
                // Default/legacy Travel Order report
                $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
                $image2 = base64_encode(file_get_contents(public_path('/dist/img/reports/Pilipinas_logo.png')));

                $pdf = PDF::loadView(
                    'official_business_application.order_business_report',
                    compact('ob', 'footer', 'image', 'image2')
                )->setOptions(['defaultFont' => 'sans-serif']);
                $pdf->setPaper('legal');
            }
        } else {
            // Not a Travel Order – still use legacy report
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
            $image2 = base64_encode(file_get_contents(public_path('/dist/img/reports/Pilipinas_logo.png')));

            $pdf = PDF::loadView(
                'official_business_application.order_business_report',
                compact('ob', 'footer', 'image', 'image2')
            )->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('legal');
        }
        $pdf->output();
        return $pdf->stream();
    }

    /**
     * Get Travel Authority Types (ta_type)
     */
    public function getTATypes()
    {
        try {
            $query = DB::table('ta_type');

            // Check if active column exists before filtering
            if (Schema::hasColumn('ta_type', 'active')) {
                $query->where('active', true);
            }

            $ta_types = $query->orderBy('name', 'asc')
                ->select('id', 'name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $ta_types
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Get Travel Order Types (to_type)
     */
    public function getTOTypes()
    {
        try {
            $query = DB::table('to_type');

            // Check if active column exists before filtering
            if (Schema::hasColumn('to_type', 'active')) {
                $query->where('active', true);
            }

            $to_types = $query->orderBy('name', 'asc')
                ->select('id', 'name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $to_types
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
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

        // Get request_for_pickup data
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
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(e.first_name,' ',COALESCE(SUBSTRING(e.middle_name,1,1),''),'. ',e.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+UPPER(COALESCE(SUBSTRING([dbo].[ufn_DecryptString](e.middle_name,'$app_key'),1,1),''))+'. '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                        END as requested_by_name"),
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
        $createForm = function ($section, $pickupData, $requestedByResolved, $ob) {
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
        $createForm = function ($sheet, $startRow, $pickupData, $requestedByResolved) {
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
            ->leftJoin('approver_headers as ah', function ($join) {
                $join->on('ah.id', '=', 'ad.approver_id')
                    ->where('ah.type_id', '=', 2); // Official Business type
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
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        UPPER(CONCAT(b.first_name,' ',b.last_name))
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                    END as name")
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
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                            UPPER(CONCAT(b.first_name,' ',b.last_name))
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                        END as name")
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
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    UPPER(CONCAT(b.first_name,' ',b.last_name))
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                                END as name"),
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
            $createPassSlip = function ($section, $date, $purpose, $officialChecked, $personalChecked, $dateTimeFrom, $dateTimeTo, $recommendingApproval, $recommendingPosition, $approver) {
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
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    UPPER(CONCAT(b.first_name,' ',b.last_name))
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                                END as name"),
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
            $createPassSlip = function ($sheet, $startRow, $date, $purpose, $officialChecked, $personalChecked, $dateTimeFrom, $dateTimeTo, $recommendingApproval, $recommendingPosition, $approver) {
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
}
