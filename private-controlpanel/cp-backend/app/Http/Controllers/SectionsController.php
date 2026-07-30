<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Sections;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionsController extends Controller
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
     * Get all sections
     */
    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");
            $data = DB::table('sections as a')
                ->leftJoin('employees as b', 'a.section_chief_id', '=', 'b.id')
                ->leftJoin('divisions as c', 'a.division_id', '=', 'c.id')
                ->select(
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                   CONCAT(b.first_name, CASE WHEN NULLIF(LTRIM(RTRIM(b.middle_name)),'') IS NULL THEN ' ' ELSE CONCAT(' ', SUBSTRING(b.middle_name,1,1), '. ') END, b.last_name)
                                ELSE
                                    CONCAT(RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')), CASE WHEN NULLIF(LTRIM(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))),'') IS NULL THEN ' ' ELSE CONCAT(' ', UPPER(SUBSTRING([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1)), '. ') END, RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')))
                                END as supervisor"),
                    'c.name as division'
                )
                ->orderBy('a.code', 'asc')
                ->get();

            return $this->successResponse($data, 'Sections retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve sections: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for creating new section
     */
    public function add()
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees')
                ->select(
                    'id',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                   CONCAT(employees.first_name, CASE WHEN NULLIF(LTRIM(RTRIM(employees.middle_name)),'') IS NULL THEN ' ' ELSE CONCAT(' ', SUBSTRING(employees.middle_name,1,1), '. ') END, employees.last_name)
                                ELSE
                                    CONCAT(RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key')), CASE WHEN NULLIF(LTRIM(RTRIM([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'))),'') IS NULL THEN ' ' ELSE CONCAT(' ', UPPER(SUBSTRING([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1)), '. ') END, RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')))
                                END as name"),
                )
                ->whereNotIn('id', function ($query) {
                    $query->select(DB::raw("isnull(division_chief_id,0)"))->from('divisions')->get();
                })
                ->whereNotIn('id', function ($query) {
                    $query->select(DB::raw("isnull(section_chief_id,0)"))->from('sections')->get();
                })
                ->orderBy('name', 'asc')
                ->get();

            $divisions = DB::table('divisions')->where('active', true)->get();

            return $this->successResponse([
                'employees' => $employees,
                'divisions' => $divisions
            ], 'Section form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load section form data: ' . $e->getMessage());
        }
    }

    /**
     * Store new section
     */
    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|min:3|unique:sections'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->division_id == 0) {
                return $this->errorResponse('Division Is Required.');
            }

            $code = 'SC' . (1000 + DB::table('sections')->max('id') + 1);

            $sec_data = array(
                'code' => $code,
                'name' =>  $request->name,
                'division_id' => $request->division_id,
                'section_chief_id' => !empty($request->section_chief_id) ? (int) $request->section_chief_id : null,
                'active' => $request->has('active') ? 1 : 0,
            );

            $section = Sections::create($sec_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Section Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on section setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse($section, 'Section added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add section: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for editing section
     */
    public function edit($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $section = DB::table('sections')->where('id', $id)->first();

            if (!$section) {
                return $this->notFoundResponse('Section not found');
            }

            $employees_selected = DB::table('employees as a')
                ->join('sections as  b', 'a.id', '=', 'b.section_chief_id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                   CONCAT(a.first_name, CASE WHEN NULLIF(LTRIM(RTRIM(a.middle_name)),'') IS NULL THEN ' ' ELSE CONCAT(' ', SUBSTRING(a.middle_name,1,1), '. ') END, a.last_name)
                                ELSE
                                    CONCAT(RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), CASE WHEN NULLIF(LTRIM(RTRIM([dbo].[ufn_DecryptString](a.middle_name,'$app_key'))),'') IS NULL THEN ' ' ELSE CONCAT(' ', UPPER(SUBSTRING([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1)), '. ') END, RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')))
                                END as name"),
                )
                ->where('b.id', $id);

            $employees = DB::table('employees')
                ->select(
                    'id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN
                                   CONCAT(first_name, CASE WHEN NULLIF(LTRIM(RTRIM(middle_name)),'') IS NULL THEN ' ' ELSE CONCAT(' ', SUBSTRING(middle_name,1,1), '. ') END, last_name)
                                ELSE
                                    CONCAT(RTRIM([dbo].[ufn_DecryptString](first_name,'$app_key')), CASE WHEN NULLIF(LTRIM(RTRIM([dbo].[ufn_DecryptString](middle_name,'$app_key'))),'') IS NULL THEN ' ' ELSE CONCAT(' ', UPPER(SUBSTRING([dbo].[ufn_DecryptString](middle_name,'$app_key'),1,1)), '. ') END, RTRIM([dbo].[ufn_DecryptString](last_name,'$app_key')))
                                END as name")
                )
                ->whereNotIn('id', function ($query) {
                    $query->select(DB::raw("isnull(division_chief_id,0)"))->from('divisions')->get();
                })
                ->whereNotIn('id', function ($query) {
                    $query->select(DB::raw("isnull(section_chief_id,0)"))->from('sections')->get();
                })
                ->unionAll($employees_selected)
                ->orderBy('name', 'asc')
                ->get();

            $divisions = DB::table('divisions')->where('active', true)->get();

            return $this->successResponse([
                'section' => $section,
                'employees' => $employees,
                'divisions' => $divisions
            ], 'Section form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load section form data: ' . $e->getMessage());
        }
    }

    /**
     * Update section
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|min:3'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->division_id == 0) {
                return $this->errorResponse('Division Is Required.');
            }

            $section = DB::table('sections')->where('id', $id)->first();

            if (!$section) {
                return $this->notFoundResponse('Section not found');
            }

            $sec_data = array(
                'name' =>  $request->name,
                'division_id' => $request->division_id,
                'section_chief_id' => !empty($request->section_chief_id) ? (int) $request->section_chief_id : null,
                'active' => $request->has('active') ? true : false,
            );

            DB::table('sections')->where('id', $id)->update($sec_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Section Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on section setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Section updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update section: ' . $e->getMessage());
        }
    }

    /**
     * Show specific section
     */
    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $data = DB::table('sections as a')
                ->leftJoin('employees as b', 'a.section_chief_id', '=', 'b.id')
                ->leftJoin('divisions as c', 'a.division_id', '=', 'c.id')
                ->select(
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                   CONCAT(b.first_name, CASE WHEN NULLIF(LTRIM(RTRIM(b.middle_name)),'') IS NULL THEN ' ' ELSE CONCAT(' ', SUBSTRING(b.middle_name,1,1), '. ') END, b.last_name)
                                ELSE
                                    CONCAT(RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')), CASE WHEN NULLIF(LTRIM(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))),'') IS NULL THEN ' ' ELSE CONCAT(' ', UPPER(SUBSTRING([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1)), '. ') END, RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')))
                                END as supervisor"),
                    'c.name as division'
                )
                ->where('a.id', $id)
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Section not found');
            }

            return $this->successResponse($data, 'Section retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve section: ' . $e->getMessage());
        }
    }

    /**
     * Delete section
     */
    public function destroy($id)
    {
        try {
            $section = DB::table('sections')->where('id', $id)->first();

            if (!$section) {
                return $this->notFoundResponse('Section not found');
            }

            DB::table('sections')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Section Setup',
                'activity' => 'Delete',
                'description' => 'Deleted section: ' . $section->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Section deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete section: ' . $e->getMessage());
        }
    }

    /**
     * Create new section form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'division_id' => ['type' => 'select', 'required' => true],
                    'section_chief_id' => ['type' => 'select', 'required' => false],
                    'active' => ['type' => 'boolean', 'required' => false]
                ]
            ], 'Create section form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }
}
