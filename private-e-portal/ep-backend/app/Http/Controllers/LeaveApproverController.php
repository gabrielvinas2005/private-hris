<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveApproverController extends Controller
{
    use ApiResponse;

    /**
     * Get all leave approvers
     */
    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('approver_headers as a')
                ->leftJoin('employees as c', 'a.approver_id_1', '=', 'c.id')
                ->leftJoin('employees as d', 'a.approver_id_2', '=', 'd.id')
                ->leftJoin('employees as e', 'a.approver_id_3', '=', 'e.id')
                ->leftJoin('departments as f', 'a.department_id', '=', 'f.id')
                ->leftJoin('branches as g', 'a.branch_id', '=', 'g.id')
                ->leftJoin('divisions as h', 'a.division_id', '=', 'h.id')
                ->leftJoin('sections as j', 'a.section_id', '=', 'j.id')
                ->leftJoin('employees as cc', 'a.branch_approver_id_1', '=', 'cc.id')
                ->leftJoin('employees as dd', 'a.division_approver_id_1', '=', 'dd.id')
                ->leftJoin('employees as ee', 'a.section_approver_id_1', '=', 'ee.id')
                ->leftJoin('employees as ff', 'a.approver_id_4', '=', 'ff.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                               CASE WHEN ISNULL(c.middle_name,'') = '' THEN
                                    CONCAT(c.first_name,' ',c.last_name)
                                ELSE
                                    CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                                END
                            ELSE
                                CASE WHEN ISNULL(c.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                                END
                            END as approver_1"),
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
                            END as approver_2"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                               CONCAT(e.first_name,' ',substring(e.middle_name,1,1),'. ',e.last_name)
                            ELSE
                                CASE WHEN ISNULL(e.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) 
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](e.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) 
                                END
                            END as approver_3"),
                    DB::raw("CASE WHEN ISNULL(ff.is_encrypted,0) = 0 THEN
                            CONCAT(ff.first_name,' ',substring(ff.middle_name,1,1),'. ',ff.last_name)
                         ELSE
                             CASE WHEN ISNULL(ff.middle_name,'') = '' THEN
                                 RTRIM([dbo].[ufn_DecryptString](ff.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](ff.last_name,'$app_key')) 
                             ELSE
                                 RTRIM([dbo].[ufn_DecryptString](ff.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](ff.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](ff.last_name,'$app_key')) 
                             END
                         END as approver_4"),
                    DB::raw("CASE WHEN ISNULL(cc.is_encrypted,0) = 0 THEN
                                CASE WHEN ISNULL(cc.middle_name,'') = '' THEN
                                    CONCAT(cc.first_name,' ',cc.last_name)
                                ELSE
                                    CONCAT(cc.first_name,' ',substring(cc.middle_name,1,1),'. ',cc.last_name)
                                END
                            ELSE
                                CASE WHEN ISNULL(cc.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString](cc.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](cc.last_name,'$app_key')) 
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](cc.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](cc.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](cc.last_name,'$app_key')) 
                                END
                            END as branch_approver"),
                    DB::raw("CASE WHEN ISNULL(dd.is_encrypted,0) = 0 THEN
                               CASE WHEN ISNULL(dd.middle_name,'') = '' THEN
                                    CONCAT(dd.first_name,' ',dd.last_name)
                                ELSE
                                    CONCAT(dd.first_name,' ',substring(dd.middle_name,1,1),'. ',dd.last_name)
                                END
                            ELSE
                                CASE WHEN ISNULL(dd.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString](dd.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](dd.last_name,'$app_key')) 
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](dd.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](dd.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](dd.last_name,'$app_key')) 
                                END 
                            END as division_approver"),
                    DB::raw("CASE WHEN ISNULL(ee.is_encrypted,0) = 0 THEN
                               CASE WHEN ISNULL(ee.middle_name,'') = '' THEN
                                    CONCAT(ee.first_name,' ',ee.last_name)
                                ELSE
                                    CONCAT(ee.first_name,' ',substring(ee.middle_name,1,1),'. ',ee.last_name)
                                END
                            ELSE
                                CASE WHEN ISNULL(ee.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString](ee.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](ee.last_name,'$app_key')) 
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](ee.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](ee.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](ee.last_name,'$app_key')) 
                                END
                            END as section_approver"),
                    'g.name as branch',
                    'f.name as department',
                    'h.name as division',
                    'j.name as section'
                )
                ->distinct()
                ->orderBy('branch', 'asc')
                ->get();

            $subordinates = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'b.employee_id',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CASE WHEN ISNULL(c.middle_name,'') = '' THEN
                                    CONCAT(c.first_name,' ',c.last_name)
                                ELSE
                                    CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                                END
                            ELSE
                                CASE WHEN ISNULL(c.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                                END
                         END as name"),
                    'b.is_branch',
                    'b.is_department',
                    'b.is_division',
                    'b.is_section'
                )
                ->get();

            return $this->successResponse([
                'data' => $data,
                'subordinates' => $subordinates
            ], 'Leave approver list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave approver list: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for adding/editing leave approver
     */
    public function add($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $approver_headers = DB::table('approver_headers')->where('id', $id)->first();

            if (!$approver_headers) {
                $approver_headers = array(
                    'id' => 0,
                    'branch_id' => 0,
                    'department_id' => 0,
                    'division_id' => 0,
                    'section_id' => 0,
                    'branch_approver_id_1' => 0,
                    'approver_id_1' => 0,
                    'section_approver_id_1' => 0,
                    'division_approver_id_1' => 0,
                    'approver_id_2' => 0,
                    'approver_id_3' => 0,
                    'approver_id_4' => 0,
                );

                $approver_headers = (object)$approver_headers;
            }

            $approver_details_branch = DB::table('approver_details as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->select(
                    'a.employee_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                    'c.name as position'
                )
                ->where([
                    'a.approver_id' => $id,
                    'a.is_branch' => true
                ])
                ->get();

            $approver_details = DB::table('approver_details as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->select(
                    'a.employee_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                    'c.name as position'
                )
                ->where([
                    'a.approver_id' => $id,
                    'a.is_department' => true
                ])
                ->get();

            $approver_details_division = DB::table('approver_details as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->select(
                    'a.employee_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                    'c.name as position'
                )
                ->where([
                    'a.approver_id' => $id,
                    'a.is_division' => true
                ])
                ->get();

            $approver_details_section = DB::table('approver_details as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->select(
                    'a.employee_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                    'c.name as position'
                )
                ->where([
                    'a.approver_id' => $id,
                    'a.is_section' => true
                ])
                ->get();

            $branches = DB::table('branches')->get();
            $departments = DB::table('departments')->where('active', true)->get();
            $sections = DB::table('sections')->where('active', true)->get();

            $approvers = DB::table('employees as a')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                )
                ->where([
                    'active' => true,
                    'is_employee' => true
                ])
                ->orderBy('a.first_name', 'asc')
                ->get();

            return $this->successResponse([
                'approvers' => $approvers,
                'approver_headers' => $approver_headers,
                'approver_details_branch' => $approver_details_branch,
                'approver_details' => $approver_details,
                'approver_details_division' => $approver_details_division,
                'approver_details_section' => $approver_details_section,
                'branches' => $branches,
                'departments' => $departments,
                'sections' => $sections,
            ], 'Leave approver form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave approver form data: ' . $e->getMessage());
        }
    }

    /**
     * Store leave approver
     */
    public function store(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'department_id' => 'required',
                'approver_1' => 'required',
                'approver_2' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data_exist = DB::table('approver_headers')
                ->where([
                    'branch_id' => $request->branch_id,
                    'department_id' => $request->department_id,
                    'division_id' => $request->division_id,
                    'section_id' => $request->section_id
                ])
                ->where('id', '<>', $id)
                ->get();

            if (count($data_exist) > 0) {
                return $this->errorResponse('Save Failed. Data with the same Branch, Office, Division and Section already exist.');
            }

            $data = array(
                'branch_id' => $request->input('branch_id', 0),
                'division_id' => isset($request->division_id) ? $request->division_id : 0,
                'section_id' => isset($request->section_id) ? $request->section_id : 0,
                'branch_approver_id_1' => isset($request->branch_approver_1) ? $request->branch_approver_1 : 0,
                'section_approver_id_1' => isset($request->section_approver_1) ? $request->section_approver_1 : 0,
                'division_approver_id_1' => isset($request->division_approver_1) ? $request->division_approver_1 : 0,
                'department_id' => isset($request->department_id) ? $request->department_id : 0,
                'approver_id_1' => isset($request->approver_1) ? $request->approver_1 : 0,
                'approver_id_2' => isset($request->approver_2) ? $request->approver_2 : 0,
                'approver_id_3' => isset($request->approver_3) ? $request->approver_3 : 0,
                'approver_id_4' => isset($request->approver_4) ? $request->approver_4 : 0,
            );

            if ($id == 0) {
                $id = DB::table('approver_headers')->max('id') + 1;
                DB::table('approver_headers')->Insert($data);
            } else {
                DB::table('approver_headers')->where('id', $id)->update($data);
            }

            if ($id == 0) {
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel Module',
                    'menu'    => 'Approver Setup',
                    'activity' => 'Created',
                    'description' => 'Created Approvers informations.',
                );
            } else {
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel Module',
                    'menu'    => 'Approver Setup',
                    'activity' => 'Updated',
                    'description' => 'Updated Approvers informations.',
                );
            }

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Leave approver saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save leave approver: ' . $e->getMessage());
        }
    }

    /**
     * Get subordinates for specific department/type
     */
    public function subordinates($department_id, $id, $type_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($id == 0) {
                if ($type_id == 1) {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.branch_id' => $department_id
                        ])
                        ->whereNotIn('a.id', function ($query) {
                            $query->select('employee_id')->from('approver_details');
                        })
                        ->get();
                } elseif ($type_id == 2) {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.department_id' => $department_id
                        ])
                        ->whereNotIn('a.id', function ($query) {
                            $query->select('employee_id')->from('approver_details');
                        })
                        ->get();
                } elseif ($type_id == 3) {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.division_id' => $department_id
                        ])
                        ->whereNotIn('a.id', function ($query) {
                            $query->select('employee_id')->from('approver_details');
                        })
                        ->get();
                } else {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.section_id' => $department_id
                        ])
                        ->whereNotIn('a.id', function ($query) {
                            $query->select('employee_id')->from('approver_details');
                        })
                        ->get();
                }
            } else {
                if ($type_id == 1) {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.branch_id' => $department_id
                        ])
                        ->whereNotIn('a.id', function ($query) {
                            $query->select('employee_id')->from('approver_details');
                        })
                        ->whereNotIn('a.id', function ($query) use ($id) {
                            $query->select('branch_approver_id_1')->from('approver_headers')->where('id', $id);
                        })
                        ->get();
                } elseif ($type_id == 2) {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.department_id' => $department_id
                        ])
                        ->whereNotIn('a.id', function ($query) {
                            $query->select('employee_id')->from('approver_details');
                        })
                        ->whereNotIn('a.id', function ($query) use ($id) {
                            $query->select('approver_id_1')->from('approver_headers')->where('id', $id);
                        })
                        ->get();
                } elseif ($type_id == 3) {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.division_id' => $department_id
                        ])
                        ->whereNotIn('a.id', function ($query) {
                            $query->select('employee_id')->from('approver_details');
                        })
                        ->whereNotIn('a.id', function ($query) use ($id) {
                            $query->select('division_approver_id_1')->from('approver_headers')->where('id', $id);
                        })
                        ->get();
                } else {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.section_id' => $department_id
                        ])
                        ->whereNotIn('a.id', function ($query) {
                            $query->select('employee_id')->from('approver_details');
                        })
                        ->whereNotIn('a.id', function ($query) use ($id) {
                            $query->select('section_approver_id_1')->from('approver_headers')->where('id', $id);
                        })
                        ->get();
                }
            }

            return $this->successResponse($subordinates, 'Subordinates retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve subordinates: ' . $e->getMessage());
        }
    }

    /**
     * Add subordinates to approver
     */
    public function addSubordinates(Request $request, $id, $type_id)
    {
        try {
            $employee_data = $request->all();

            $data = [];

            if (isset($employee_data['employee_id'])) {
                $arr_len = count($employee_data['employee_id']);
                for ($i = 0; $i < $arr_len; $i++) {
                    if ($employee_data['employee_id'][$i] != NULL) {

                        if ($type_id == 1) {
                            $data = [
                                'approver_id' => $id,
                                'employee_id' => $employee_data['employee_id'][$i],
                                'is_branch' => true
                            ];
                        } elseif ($type_id == 2) {
                            $data = [
                                'approver_id' => $id,
                                'employee_id' => $employee_data['employee_id'][$i],
                                'is_department' => true
                            ];
                        } elseif ($type_id == 3) {
                            $data = [
                                'approver_id' => $id,
                                'employee_id' => $employee_data['employee_id'][$i],
                                'is_division' => true
                            ];
                        } else {
                            $data = [
                                'approver_id' => $id,
                                'employee_id' => $employee_data['employee_id'][$i],
                                'is_section' => true
                            ];
                        }

                        DB::table('approver_details')->insert($data);
                    }
                }
            }

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel Module',
                'menu'    => 'Approver Setup',
                'activity' => 'Added',
                'description' => 'Added Approvers subordinates.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Subordinates added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add subordinates: ' . $e->getMessage());
        }
    }

    /**
     * Delete subordinate from approver
     */
    public function deleteSubordinates($id)
    {
        try {
            DB::table('approver_details')->where('employee_id', $id)->delete();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel Module',
                'menu'    => 'Approver Setup',
                'activity' => 'Remove',
                'description' => 'Removed Approvers subordinates.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Subordinate removed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to remove subordinate: ' . $e->getMessage());
        }
    }

    /**
     * Get departments for specific branch
     */
    public function getDepartments($id)
    {
        try {
            $data = DB::table('departments as a')
                ->join('branches as b', 'a.branch_id', '=', 'b.id')
                ->select(
                    'a.id',
                    'a.name',
                    'b.is_main_branch'
                )
                ->where('branch_id', $id)
                ->get();

            return $this->successResponse($data, 'Departments retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve departments: ' . $e->getMessage());
        }
    }

    /**
     * Get divisions for specific department
     */
    public function getDivisions($id)
    {
        try {
            $data = DB::table('divisions as a')
                ->select(
                    'a.id',
                    'a.name'
                )
                ->where('department_id', $id)
                ->get();

            return $this->successResponse($data, 'Divisions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve divisions: ' . $e->getMessage());
        }
    }

    /**
     * Get sections for specific division
     */
    public function getSections($id)
    {
        try {
            $data = DB::table('sections as a')
                ->select(
                    'a.id',
                    'a.name'
                )
                ->where('division_id', $id)
                ->get();

            return $this->successResponse($data, 'Sections retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve sections: ' . $e->getMessage());
        }
    }
}
