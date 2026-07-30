<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\PMT;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PMTController extends Controller
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

    /**
     * Get all PMT records
     */
    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('pmt as p')
                ->leftJoin('employees as e', 'p.employee_id', '=', 'e.id')
                ->leftJoin('departments as d', 'p.department_id', '=', 'd.id')
                ->leftJoin('divisions as v', 'p.division_id', '=', 'v.id')
                ->leftJoin('sections as s', 'p.section_id', '=', 's.id')
                ->select(
                    'p.id',
                    'p.employee_id',
                    'p.department_id',
                    'p.division_id',
                    'p.section_id',
                    'e.employee_no as Employee_no',
                    'd.code as Department_no',
                    'v.code as Division_no',
                    's.code as Section_no',
                    DB::raw("CASE WHEN ISNULL(p.is_ipcr,0) = 1 THEN 1 ELSE 0 END as is_ipcr"),
                    DB::raw("CASE WHEN ISNULL(p.is_opcr,0) = 1 THEN 1 ELSE 0 END as is_opcr"),
                    DB::raw("CASE WHEN ISNULL(p.is_dpcr,0) = 1 THEN 1 ELSE 0 END as is_dpcr"),
                    'p.created_at',
                    'p.updated_at',
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
                            END as employee_name"),
                    'd.name as department_name',
                    'v.name as division_name',
                    's.name as section_name'
                )
                ->orderBy('p.created_at', 'desc')
                ->get();

            // Convert boolean fields to proper booleans
            $data = $data->map(function ($item) {
                $item->is_ipcr = (bool) $item->is_ipcr;
                $item->is_opcr = (bool) $item->is_opcr;
                $item->is_dpcr = (bool) $item->is_dpcr;
                return $item;
            });

            return $this->successResponse($data, 'PMT records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve PMT records: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for adding PMT
     */
    public function add()
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees as a')
                ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
                ->leftJoin('divisions as v', 'a.division_id', '=', 'v.id')
                ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
                ->select(
                    'a.id as employee_id',
                    'a.employee_no',
                    'd.id as department_id',
                    'd.code as department_code',
                    'v.id as division_id',
                    'v.name as division_name',
                    's.id as section_id',
                    's.name as section_name',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CASE WHEN ISNULL(a.middle_name,'') = '' THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                                END
                            ELSE
                                CASE WHEN ISNULL(a.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                                END
                            END as name")
                )
                ->where([
                    'a.active' => true,
                    'a.is_employee' => true
                ])
                ->orderBy('a.first_name', 'asc')
                ->get();

            $departments = DB::table('departments')
                ->where('active', true)
                ->select('id', 'code', 'name')
                ->orderBy('name', 'asc')
                ->get();

            $divisions = DB::table('divisions')
                ->where('active', true)
                ->select('id', 'code', 'name')
                ->orderBy('name', 'asc')
                ->get();

            $sections = DB::table('sections')
                ->where('active', true)
                ->select('id', 'code', 'name')
                ->orderBy('name', 'asc')
                ->get();

            return $this->successResponse([
                'employees' => $employees,
                'departments' => $departments,
                'divisions' => $divisions,
                'sections' => $sections
            ], 'PMT form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve PMT form data: ' . $e->getMessage());
        }
    }

    /**
     * Store PMT record
     */
    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee_id' => 'required|integer|exists:employees,id',
                'department_id' => 'required|integer|exists:departments,id',
                'division_id' => 'nullable|integer|exists:divisions,id',
                'section_id' => 'nullable|integer|exists:sections,id',
                'is_ipcr' => 'boolean',
                'is_opcr' => 'boolean',
                'is_dpcr' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Check if at least one rating type is selected
            if (!$request->is_ipcr && !$request->is_opcr && !$request->is_dpcr) {
                return $this->errorResponse('At least one rating type (IPCR, OPCR, or DPCR) must be selected.');
            }

            // Check for duplicate
            $existing = DB::table('pmt')
                ->where('employee_id', $request->employee_id)
                ->where('department_id', $request->department_id)
                ->first();

            if ($existing) {
                return $this->errorResponse('This employee is already assigned to this department in PMT.');
            }

            $form_data = array(
                'employee_id' => $request->employee_id,
                'department_id' => $request->department_id,
                'division_id' => $request->input('division_id') ?: null,
                'section_id' => $request->input('section_id') ?: null,
                'is_ipcr' => $request->input('is_ipcr', false) === true || $request->input('is_ipcr') === 'true' || $request->input('is_ipcr') === '1',
                'is_opcr' => $request->input('is_opcr', false) === true || $request->input('is_opcr') === 'true' || $request->input('is_opcr') === '1',
                'is_dpcr' => $request->input('is_dpcr', false) === true || $request->input('is_dpcr') === 'true' || $request->input('is_dpcr') === '1',
            );

            $pmt = PMT::create($form_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'PMT Setup',
                'activity' => 'Add',
                'description' => 'Added PMT record for employee ID: ' . $request->employee_id,
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $pmt->id], 'PMT record added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add PMT record: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for editing PMT
     */
    public function edit($id)
    {
        try {
            $pmt = DB::table('pmt')->where('id', $id)->first();

            if (!$pmt) {
                return $this->notFoundResponse('PMT record not found');
            }

            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees as a')
                ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
                ->leftJoin('divisions as v', 'a.division_id', '=', 'v.id')
                ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
                ->select(
                    'a.id as employee_id',
                    'a.employee_no',
                    'd.id as department_id',
                    'd.code as department_code',
                    'v.id as division_id',
                    'v.name as division_name',
                    's.id as section_id',
                    's.name as section_name',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CASE WHEN ISNULL(a.middle_name,'') = '' THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                                END
                            ELSE
                                CASE WHEN ISNULL(a.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                                END
                            END as name")
                )
                ->where([
                    'a.active' => true,
                    'a.is_employee' => true
                ])
                ->orderBy('a.first_name', 'asc')
                ->get();

            $departments = DB::table('departments')
                ->where('active', true)
                ->select('id', 'code', 'name')
                ->orderBy('name', 'asc')
                ->get();

            $divisions = DB::table('divisions')
                ->where('active', true)
                ->select('id', 'code', 'name')
                ->orderBy('name', 'asc')
                ->get();

            $sections = DB::table('sections')
                ->where('active', true)
                ->select('id', 'code', 'name')
                ->orderBy('name', 'asc')
                ->get();

            return $this->successResponse([
                'pmt' => $pmt,
                'employees' => $employees,
                'departments' => $departments,
                'divisions' => $divisions,
                'sections' => $sections
            ], 'PMT data for editing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve PMT data: ' . $e->getMessage());
        }
    }

    /**
     * Update PMT record
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'employee_id' => 'required|integer|exists:employees,id',
                'department_id' => 'required|integer|exists:departments,id',
                'division_id' => 'nullable|integer|exists:divisions,id',
                'section_id' => 'nullable|integer|exists:sections,id',
                'is_ipcr' => 'boolean',
                'is_opcr' => 'boolean',
                'is_dpcr' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Check if at least one rating type is selected
            if (!$request->is_ipcr && !$request->is_opcr && !$request->is_dpcr) {
                return $this->errorResponse('At least one rating type (IPCR, OPCR, or DPCR) must be selected.');
            }

            $pmt = DB::table('pmt')->where('id', $id)->first();

            if (!$pmt) {
                return $this->notFoundResponse('PMT record not found');
            }

            // Check for duplicate (excluding current record)
            $existing = DB::table('pmt')
                ->where('employee_id', $request->employee_id)
                ->where('department_id', $request->department_id)
                ->where('id', '<>', $id)
                ->first();

            if ($existing) {
                return $this->errorResponse('This employee is already assigned to this department in PMT.');
            }

            $update_data = array(
                'employee_id' => $request->employee_id,
                'department_id' => $request->department_id,
                'division_id' => $request->input('division_id') ?: null,
                'section_id' => $request->input('section_id') ?: null,
                'is_ipcr' => $request->input('is_ipcr', false) === true || $request->input('is_ipcr') === 'true' || $request->input('is_ipcr') === '1',
                'is_opcr' => $request->input('is_opcr', false) === true || $request->input('is_opcr') === 'true' || $request->input('is_opcr') === '1',
                'is_dpcr' => $request->input('is_dpcr', false) === true || $request->input('is_dpcr') === 'true' || $request->input('is_dpcr') === '1',
            );

            DB::table('pmt')->where('id', $id)->update($update_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'PMT Setup',
                'activity' => 'Update',
                'description' => 'Updated PMT record for employee ID: ' . $request->employee_id,
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'PMT record updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update PMT record: ' . $e->getMessage());
        }
    }

    /**
     * Delete PMT record
     */
    public function destroy($id)
    {
        try {
            $pmt = DB::table('pmt')->where('id', $id)->first();

            if (!$pmt) {
                return $this->notFoundResponse('PMT record not found');
            }

            DB::table('pmt')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'PMT Setup',
                'activity' => 'Delete',
                'description' => 'Deleted PMT record for employee ID: ' . $pmt->employee_id,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'PMT record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete PMT record: ' . $e->getMessage());
        }
    }
}

