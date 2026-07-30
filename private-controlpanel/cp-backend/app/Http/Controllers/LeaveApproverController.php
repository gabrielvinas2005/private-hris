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
                ->leftJoin('approver_type as at', 'a.type_id', '=', 'at.id')
                ->select(
                    'a.id',
                    DB::raw($this->sqlEmployeeDisplayName('c') . ' as approver_1'),
                    DB::raw($this->sqlEmployeeDisplayName('d') . ' as approver_2'),
                    DB::raw($this->sqlEmployeeDisplayName('e') . ' as approver_3'),
                    DB::raw($this->sqlEmployeeDisplayName('ff') . ' as approver_4'),
                    DB::raw($this->sqlEmployeeDisplayName('cc') . ' as branch_approver'),
                    DB::raw($this->sqlEmployeeDisplayName('dd') . ' as division_approver'),
                    DB::raw($this->sqlEmployeeDisplayName('ee') . ' as section_approver'),
                    'g.name as branch',
                    'f.name as department',
                    'h.name as division',
                    'j.name as section',
                    'at.name as approver_type'
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
                    DB::raw($this->sqlEmployeeDisplayName('c') . ' as name'),
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
            $approver_headers = DB::table('approver_headers')->where('id', $id)->first();

            if (!$approver_headers) {
                $approver_headers = array(
                    'id' => 0,
                    'branch_id' => 0,
                    'department_id' => 0,
                    'division_id' => 0,
                    'section_id' => 0,
                    'type_id' => null,
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
                    DB::raw($this->sqlEmployeeDisplayName('b') . ' as name'),
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
                    DB::raw($this->sqlEmployeeDisplayName('b') . ' as name'),
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
                    DB::raw($this->sqlEmployeeDisplayName('b') . ' as name'),
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
                    DB::raw($this->sqlEmployeeDisplayName('b') . ' as name'),
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
            $approver_types = DB::table('approver_type')->get();

            $approvers = DB::table('employees as a')
                ->select(
                    'a.id',
                    DB::raw($this->sqlEmployeeDisplayName('a') . ' as name'),
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
                'approver_types' => $approver_types,
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
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Normalize type_id: treat 0/empty as null so it saves correctly and compares correctly.
            $type_id = $request->input('type_id', null);
            if ($type_id === 0 || $type_id === '0' || $type_id === '' || $type_id === false) {
                $type_id = null;
            }

            // Normalize incoming "key" fields (treat null/empty as 0 for numeric ids; type_id stays nullable)
            $branch_id = (int)$request->input('branch_id', 0);
            $department_id = (int)$request->input('department_id', 0);
            $division_id = (int)$request->input('division_id', 0);
            $section_id = (int)$request->input('section_id', 0);

            // Only enforce uniqueness when creating a NEW approver setup (id == 0).
            // For edits, allow saving even if legacy duplicates exist, to avoid blocking updates.
            if ((int)$id === 0) {
                $query = DB::table('approver_headers')
                    ->where('branch_id', $branch_id)
                    ->where('department_id', $department_id)
                    ->where('division_id', $division_id)
                    ->where('section_id', $section_id)
                    ->where('id', '<>', $id);

                if ($type_id === null) {
                    $query->whereNull('type_id');
                } else {
                    $query->where('type_id', $type_id);
                }

                if ($query->exists()) {
                    return $this->errorResponse('Save Failed. A setup with the same Branch, Office, Division, Section, and Approver Type already exists.');
                }
            }

            $data = array(
                'branch_id' => $branch_id,
                'division_id' => $division_id,
                'section_id' => $section_id,
                'type_id' => $type_id,
                'branch_approver_id_1' => isset($request->branch_approver_1) ? $request->branch_approver_1 : 0,
                'section_approver_id_1' => isset($request->section_approver_1) ? $request->section_approver_1 : 0,
                'division_approver_id_1' => isset($request->division_approver_1) ? $request->division_approver_1 : 0,
                'department_id' => $department_id,
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
                    'description' => 'Created Approvers information',
                );
            } else {
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel Module',
                    'menu'    => 'Approver Setup',
                    'activity' => 'Updated',
                    'description' => 'Updated Approvers information',
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
     *
     * Query param leave_type_id (approver_headers.type_id): when set with type_id path = 2 (department),
     * employees already used as office subordinates or as chain approvers on another header with the same
     * department_id and leave approver type are excluded.
     */
    public function subordinates(Request $request, $department_id, $id, $type_id)
    {
        try {
            $leaveTypeParam = $request->query('leave_type_id');
            $leaveTypeIdForExclude = null;
            if ($leaveTypeParam !== null && $leaveTypeParam !== '') {
                $lt = (int) $leaveTypeParam;
                if ($lt > 0) {
                    $leaveTypeIdForExclude = $lt;
                }
            }

            if ($id == 0) {
                if ($type_id == 1) {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw($this->sqlEmployeeDisplayName('a') . ' as name'),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.branch_id' => $department_id
                        ])
                        ->get();
                } elseif ($type_id == 2) {
                    $q = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw($this->sqlEmployeeDisplayName('a') . ' as name'),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.department_id' => $department_id
                        ]);
                    if ($leaveTypeIdForExclude !== null) {
                        $excludeIds = $this->excludedEmployeeIdsForDepartmentLeaveType($department_id, $leaveTypeIdForExclude, 0);
                        if (! empty($excludeIds)) {
                            $q->whereNotIn('a.id', $excludeIds);
                        }
                    }
                    $subordinates = $q->get();
                } elseif ($type_id == 3) {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw($this->sqlEmployeeDisplayName('a') . ' as name'),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.division_id' => $department_id
                        ])
                        ->get();
                } else {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw($this->sqlEmployeeDisplayName('a') . ' as name'),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.section_id' => $department_id
                        ])
                        ->get();
                }
            } else {
                if ($type_id == 1) {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw($this->sqlEmployeeDisplayName('a') . ' as name'),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.branch_id' => $department_id
                        ])
                        ->whereNotIn('a.id', function ($query) use ($id) {
                            $query->select('branch_approver_id_1')->from('approver_headers')->where('id', $id);
                        })
                        ->get();
                } elseif ($type_id == 2) {
                    $q = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw($this->sqlEmployeeDisplayName('a') . ' as name'),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.department_id' => $department_id
                        ])
                        ->whereNotIn('a.id', function ($query) use ($id) {
                            $query->select('approver_id_1')->from('approver_headers')->where('id', $id);
                        });
                    if ($leaveTypeIdForExclude !== null) {
                        $excludeIds = $this->excludedEmployeeIdsForDepartmentLeaveType($department_id, $leaveTypeIdForExclude, (int) $id);
                        if (! empty($excludeIds)) {
                            $q->whereNotIn('a.id', $excludeIds);
                        }
                    }
                    $subordinates = $q->get();
                } elseif ($type_id == 3) {
                    $subordinates = DB::table('employees as a')
                        ->join('departments as b', 'a.department_id', '=', 'b.id')
                        ->join('positions as c', 'a.position_id', '=', 'c.id')
                        ->select(
                            'a.id',
                            DB::raw($this->sqlEmployeeDisplayName('a') . ' as name'),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.division_id' => $department_id
                        ])
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
                            DB::raw($this->sqlEmployeeDisplayName('a') . ' as name'),
                            'c.name as position',
                            'b.name as department'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'a.section_id' => $department_id
                        ])
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
     * Delete approver header and its subordinates
     */
    public function destroy($id)
    {
        try {
            $exists = DB::table('approver_headers')->where('id', $id)->first();
            if (!$exists) {
                return $this->notFoundResponse('Approver setup not found');
            }

            DB::table('approver_details')->where('approver_id', $id)->delete();
            DB::table('approver_headers')->where('id', $id)->delete();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel Module',
                'menu'    => 'Approver Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Approver Setup ID: ' . $id,
            );
            Audit::create($data_audit);

            return $this->successResponse(null, 'Approver setup deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete leave approver: ' . $e->getMessage());
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

    /**
     * Employee IDs already tied to another approver setup for the same department + leave approver type:
     * office subordinates (is_department) or listed as 1st–4th approver on approver_headers.
     *
     * @param  int  $excludeHeaderId  Current approver_headers.id (excluded from conflict scan); 0 when creating new.
     */
    private function excludedEmployeeIdsForDepartmentLeaveType($departmentId, int $leaveTypeId, int $excludeHeaderId): array
    {
        $departmentId = (int) $departmentId;
        $ids = [];

        $subQ = DB::table('approver_details as ad')
            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
            ->where('ad.is_department', 1)
            ->where('ah.department_id', $departmentId)
            ->where('ah.type_id', $leaveTypeId);
        if ($excludeHeaderId > 0) {
            $subQ->where('ah.id', '!=', $excludeHeaderId);
        }
        foreach ($subQ->pluck('ad.employee_id') as $eid) {
            $eid = (int) $eid;
            if ($eid > 0) {
                $ids[$eid] = true;
            }
        }

        $headerQ = DB::table('approver_headers')
            ->where('department_id', $departmentId)
            ->where('type_id', $leaveTypeId);
        if ($excludeHeaderId > 0) {
            $headerQ->where('id', '!=', $excludeHeaderId);
        }
        foreach ($headerQ->get(['approver_id_1', 'approver_id_2', 'approver_id_3', 'approver_id_4']) as $h) {
            foreach (['approver_id_1', 'approver_id_2', 'approver_id_3', 'approver_id_4'] as $col) {
                $v = (int) $h->$col;
                if ($v > 0) {
                    $ids[$v] = true;
                }
            }
        }

        return array_keys($ids);
    }

    /**
     * SQL expression for employee display name (First M. Last), treating null/empty middle as omitted.
     * Required so encrypted (+) concatenation does not NULL out the whole name when middle_name is null.
     */
    private function sqlEmployeeDisplayName(string $alias): string
    {
        $app_key = str_replace("'", "''", env('APP_KEY', ''));

        return "CASE WHEN ISNULL({$alias}.is_encrypted,0) = 0 THEN
                               CASE WHEN ISNULL({$alias}.middle_name,'') = '' THEN
                                    CONCAT({$alias}.first_name,' ',{$alias}.last_name)
                                ELSE
                                    CONCAT({$alias}.first_name,' ',substring({$alias}.middle_name,1,1),'. ',{$alias}.last_name)
                                END
                            ELSE
                                CASE WHEN ISNULL({$alias}.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString]({$alias}.first_name,'{$app_key}'))+' '+RTRIM([dbo].[ufn_DecryptString]({$alias}.last_name,'{$app_key}'))
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString]({$alias}.first_name,'{$app_key}'))+' '+UPPER(substring([dbo].[ufn_DecryptString]({$alias}.middle_name,'{$app_key}'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString]({$alias}.last_name,'{$app_key}'))
                                END
                            END";
    }
}
