<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Division;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DivisionsController extends Controller
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

    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('divisions as a')
                ->leftJoin('employees as b', 'a.division_chief_id', '=', 'b.id')
                ->leftJoin('departments as c', 'c.id', '=', 'a.department_id')
                ->select(
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                   CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                                END as division_chief"),
                    'c.name as office'
                )
                ->orderBy('a.code', 'asc')
                ->get();

            return $this->successResponse($data, 'Divisions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve divisions: ' . $e->getMessage());
        }
    }

    public function add()
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees')
                ->select(
                    'employees.id',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                   CONCAT(employees.first_name,' ',substring(employees.middle_name,1,1),'. ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name"),
                )
                ->whereNotIn('employees.id', function ($query) {
                    $query->select(DB::raw("isnull(division_chief_id,0)"))->from('divisions')->get();
                })
                ->orderBy('name', 'asc')
                ->get();

            $departments = DB::table('departments')->where('active', true)->get();

            return $this->successResponse([
                'employees' => $employees,
                'departments' => $departments
            ], 'Division form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load division form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|min:3|unique:divisions',
                'department_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->department_id == 0) {
                return $this->errorResponse('Department Is Required.');
            }

            $code = 'DV' . (1000 + DB::table('divisions')->max('id') + 1);

            $div_data = array(
                'code' => $code,
                'name' =>  $request->name,
                'department_id' => $request->department_id,
                'division_chief_id' => $request->division_chief_id,
                'active' => $request->has('active') ? true : false,
            );

            $division = Division::create($div_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Division Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on division setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $division->id], 'Division added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add division: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $division = DB::table('divisions')->where('id', $id)->first();

            if (!$division) {
                return $this->notFoundResponse('Division not found');
            }

            $employees_selected = DB::table('employees as a')
                ->join('divisions as  b', 'a.id', '=', 'b.division_chief_id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                   CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                                END as name"),
                )
                ->where('b.id', $id);

            $employees = DB::table('employees')
                ->select(
                    'employees.id',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                   CONCAT(employees.first_name,' ',substring(employees.middle_name,1,1),'. ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name"),
                )
                ->whereNotIn('employees.id', function ($query) {
                    $query->select(DB::raw("isnull(division_chief_id,0)"))->from('divisions')->get();
                })
                ->unionAll($employees_selected)
                ->orderBy('name', 'asc')
                ->get();

            $departments = DB::table('departments')->where('active', true)->get();

            return $this->successResponse([
                'division' => $division,
                'employees' => $employees,
                'departments' => $departments
            ], 'Division edit data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load division edit data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|min:3|unique:divisions,name,' . $id,
                'department_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->department_id == 0) {
                return $this->errorResponse('Department Is Required.');
            }

            $division = DB::table('divisions')->where('id', $id)->first();

            if (!$division) {
                return $this->notFoundResponse('Division not found');
            }

            $div_data = array(
                'name' =>  $request->name,
                'department_id' => $request->department_id,
                'division_chief_id' => $request->division_chief_id,
                'active' => $request->has('active') ? true : false,
            );

            DB::table('divisions')->where('id', $id)->update($div_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Division Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on division setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Division updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update division: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('divisions as a')
                ->leftJoin('employees as b', 'a.division_chief_id', '=', 'b.id')
                ->leftJoin('departments as c', 'c.id', '=', 'a.department_id')
                ->select(
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                   CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                                END as division_chief"),
                    'c.name as office'
                )
                ->where('a.id', $id)
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Division not found');
            }

            return $this->successResponse($data, 'Division retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve division: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees')
                ->select(
                    'employees.id',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                   CONCAT(employees.first_name,' ',substring(employees.middle_name,1,1),'. ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name"),
                )
                ->orderBy('name', 'asc')
                ->get();

            $departments = DB::table('departments')->where('active', true)->get();

            return $this->successResponse([
                'employees' => $employees,
                'departments' => $departments,
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'department_id' => ['type' => 'select', 'required' => true],
                    'division_chief_id' => ['type' => 'select', 'required' => false],
                    'active' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Create division form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $division = DB::table('divisions')->where('id', $id)->first();

            if (!$division) {
                return $this->notFoundResponse('Division not found');
            }

            DB::table('divisions')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Division Setup',
                'activity' => 'Delete',
                'description' => 'Deleted division: ' . $division->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Division deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete division: ' . $e->getMessage());
        }
    }
}
