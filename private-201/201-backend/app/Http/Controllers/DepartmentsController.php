<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Department;
use App\Services\DepartmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentsController extends Controller
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
     * Get all departments
     */
    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('departments as a')
                ->leftJoin('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                   CONCAT(b.first_name,' ',substring(b.middle_name,1,1),' ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                                END as supervisor"),
                )
                ->orderBy('a.code', 'asc')
                ->get();

            return $this->successResponse($data, 'Departments retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve departments: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for adding department
     */
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
                ->orderBy('employees.first_name', 'asc')
                ->get();

            $branches = DB::table('branches')->get();

            return $this->successResponse([
                'employees' => $employees,
                'branches' => $branches
            ], 'Department form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load department form data: ' . $e->getMessage());
        }
    }

    /**
     * Store department
     */
    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|min:3|unique:departments',
                'branch_id' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->branch_id == 0) {
                return $this->errorResponse('Branch Is Required.');
            }

            $id = DB::table('departments')->max('id') + 1001;
            $department_code = 'D' . $id;

            $dept_data = [
                'code' => $department_code,
                'name' =>  $request->name,
                'functionality' => $request->functionality,
                'branch_id' => $request->branch_id,
                'is_academic' => $request->has('is_academic') ? true : false,
                'employee_id' => $request->employee_id,
                'active' => $request->has('active') ? true : false,
            ];

            $department = Department::create($dept_data);

            (new DepartmentService)->store($dept_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Department Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on department setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $department->id], 'Department added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add department: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for editing department
     */
    public function edit($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $department = DB::table('departments')->where('id', $id)->first();

            if (!$department) {
                return $this->notFoundResponse('Department not found');
            }

            $employees = DB::table('employees')
                ->select(
                    'employees.id',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                   CONCAT(employees.first_name,' ',substring(employees.middle_name,1,1),'. ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name"),
                )
                ->where(['active' => true, 'is_employee' => true])
                ->orderBy('employees.first_name', 'asc')
                ->get();

            $branches = DB::table('branches')->get();

            return $this->successResponse([
                'department' => $department,
                'employees' => $employees,
                'branches' => $branches
            ], 'Department edit data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load department edit data: ' . $e->getMessage());
        }
    }

    /**
     * Update department
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|min:3|unique:departments,name,' . $id,
                'branch_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->branch_id == 0) {
                return $this->errorResponse('Branch Is Required.');
            }

            $department = DB::table('departments')->where('id', $id)->first();

            if (!$department) {
                return $this->notFoundResponse('Department not found');
            }

            if ($request->code == null || $request->code == '') {
                $new_id = DB::table('departments')->max('id') + 1000;
                $department_code = 'D' . $new_id;
            } else {
                $department_code = $request->code;
            }

            $dept_data = array(
                'code' => $department_code,
                'name' =>  $request->name,
                'functionality' => $request->functionality,
                'employee_id' => $request->employee_id,
                'branch_id' => $request->branch_id,
                'is_academic' => $request->has('is_academic') ? true : false,
                'active' => $request->has('active') ? true : false,
            );

            DB::table('departments')->where('id', $id)->update($dept_data);

            (new DepartmentService)->update($dept_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Department Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on department setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Department updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update department: ' . $e->getMessage());
        }
    }

    /**
     * Show specific department
     */
    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('departments as a')
                ->leftJoin('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                   CONCAT(b.first_name,' ',substring(b.middle_name,1,1),' ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                                END as supervisor"),
                )
                ->where('a.id', $id)
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Department not found');
            }

            return $this->successResponse($data, 'Department retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve department: ' . $e->getMessage());
        }
    }

    /**
     * Create new department form data
     */
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
                ->orderBy('employees.first_name', 'asc')
                ->get();

            $branches = DB::table('branches')->get();

            return $this->successResponse([
                'employees' => $employees,
                'branches' => $branches,
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'functionality' => ['type' => 'textarea', 'required' => false],
                    'branch_id' => ['type' => 'select', 'required' => true],
                    'employee_id' => ['type' => 'select', 'required' => false],
                    'is_academic' => ['type' => 'checkbox', 'required' => false],
                    'active' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Create department form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Delete department
     */
    public function destroy($id)
    {
        try {
            $department = DB::table('departments')->where('id', $id)->first();

            if (!$department) {
                return $this->notFoundResponse('Department not found');
            }

            DB::table('departments')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Department Setup',
                'activity' => 'Delete',
                'description' => 'Deleted department: ' . $department->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Department deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete department: ' . $e->getMessage());
        }
    }
}
