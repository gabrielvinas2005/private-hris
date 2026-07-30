<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
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
     * Get all branches
     */
    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");
            $data = DB::table('branches')->orderby('id', 'asc')->get();

            $employees = DB::table('employees')
                ->select(
                    'id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN
                                   CONCAT(first_name,' ',substring(middle_name,1,1),'. ',last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](last_name,'$app_key'))
                                END as name"),
                    DB::raw("isnull(branch_id,0) as branch_id")
                )
                ->where([
                    'active' => true,
                    'is_employee' => true
                ])
                ->get();

            return $this->successResponse([
                'branches' => $data,
                'employees' => $employees
            ], 'Branch data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve branch data: ' . $e->getMessage());
        }
    }

    /**
     * Store branches
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['name']) || !is_array($data['name'])) {
                return $this->errorResponse('Invalid data format. Expected array of branches.');
            }

            $arr_len = count($data['name']);

            $branch_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL) {

                    $nameExists = DB::table('branches')
                        ->where('name', $data['name'][$i])
                        ->when($data['id'][$i], function ($query, $id) {
                            return $query->where('id', '<>', $id);
                        })
                        ->exists();

                    if ($nameExists) {
                        return $this->errorResponse('The branch name "' . $data['name'][$i] . '" already exists.');
                    }

                    if (isset($data['is_main_branch'])) {
                        if (in_array($data['id'][$i], $data['is_main_branch'])) {
                            $is_main_branch = true;
                        } else {
                            $is_main_branch = false;
                        }
                    } else {
                        $is_main_branch = false;
                    };

                    if ($data['id'][$i] == null || $data['id'][$i] == 0) {
                        $id = DB::table('branches')->max('id') + 1;
                    } else {
                        $id = $data['id'][$i];
                    }

                    $branch_data = [
                        'code' => $data['code'][$i],
                        'name' => $data['name'][$i],
                        'branch_head_id' => $data['branch_head_id'][$i],
                        'is_main_branch' => $is_main_branch
                    ];

                    DB::unprepared('SET IDENTITY_INSERT branches ON');
                    DB::table('branches')->updateOrInsert(['id' => $id], $branch_data);
                    DB::unprepared('SET IDENTITY_INSERT branches OFF');
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Branch Setup',
                'activity' => 'Update',
                'description' => 'Updated branch information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Branches updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update branches: ' . $e->getMessage());
        }
    }

    /**
     * Get branch for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('branches')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Branch not found');
            }

            return $this->successResponse($data, 'Branch data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve branch: ' . $e->getMessage());
        }
    }

    /**
     * Delete branch
     */
    public function destroy($id)
    {
        try {
            $branch = DB::table('branches')->where('id', $id)->first();

            if (!$branch) {
                return $this->notFoundResponse('Branch not found');
            }

            DB::table('branches')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Branch Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Branch information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Branch deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete branch: ' . $e->getMessage());
        }
    }

    /**
     * Show specific branch
     */
    public function show($id)
    {
        try {
            $data = DB::table('branches')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Branch not found');
            }

            return $this->successResponse($data, 'Branch retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve branch: ' . $e->getMessage());
        }
    }

    /**
     * Create new branch form data
     */
    public function create()
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees')
                ->select(
                    'id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN
                                   CONCAT(first_name,' ',substring(middle_name,1,1),'. ',last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](last_name,'$app_key'))
                                END as name")
                )
                ->where([
                    'active' => true,
                    'is_employee' => true
                ])
                ->get();

            return $this->successResponse([
                'employees' => $employees,
                'fields' => [
                    'code' => ['type' => 'text', 'required' => false],
                    'name' => ['type' => 'text', 'required' => true],
                    'branch_head_id' => ['type' => 'select', 'required' => false],
                    'is_main_branch' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Create branch form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit branch form data
     */
    public function edit($id)
    {
        try {
            $data = DB::table('branches')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Branch not found');
            }

            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees')
                ->select(
                    'id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN
                                   CONCAT(first_name,' ',substring(middle_name,1,1),'. ',last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](last_name,'$app_key'))
                                END as name")
                )
                ->where([
                    'active' => true,
                    'is_employee' => true
                ])
                ->get();

            return $this->successResponse([
                'branch' => $data,
                'employees' => $employees
            ], 'Branch retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve branch: ' . $e->getMessage());
        }
    }

    /**
     * Update branch
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|unique:branches,name,' . $id,
                'code' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $branch = DB::table('branches')->where('id', $id)->first();

            if (!$branch) {
                return $this->notFoundResponse('Branch not found');
            }

            $branch_data = [
                'code' => $request->code,
                'name' => $request->name,
                'branch_head_id' => $request->branch_head_id,
                'is_main_branch' => $request->has('is_main_branch') ? true : false
            ];

            DB::table('branches')->where('id', $id)->update($branch_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Branch Setup',
                'activity' => 'Update',
                'description' => 'Updated branch: ' . $request->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Branch updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update branch: ' . $e->getMessage());
        }
    }
}
