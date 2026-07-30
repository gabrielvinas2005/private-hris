<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\SalaryStep;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalaryStepController extends Controller
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
     * Get all salary steps
     */
    public function index()
    {
        try {
            $data = DB::table('salary_steps')->orderby('id', 'asc')->get();

            return $this->successResponse($data, 'Salary steps retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary steps: ' . $e->getMessage());
        }
    }

    /**
     * Store salary steps
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['name']) || !is_array($data['name'])) {
                return $this->errorResponse('Invalid data format. Expected array of salary steps.');
            }

            $arr_len = count($data['name']);
            $step_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL) {

                    if ($data['id'][$i] == 0) {
                        $id = DB::table('salary_steps')->max('id') + 1;
                    } else {
                        $id = $data['id'][$i];
                    }

                    $rules = [
                        'name.' . $i => 'required|unique:salary_steps,name' . ($id ? ",$id" : ''),
                    ];
                    $messages = [
                        'name.' . $i . '.required' => 'The Salary Step field is required.',
                        'name.' . $i . '.unique' => 'The Salary Step field must be unique.',
                    ];

                    $validator = Validator::make($data, $rules, $messages);

                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    $step_data = [
                        'name' => $data['name'][$i],
                        'active' => isset($data['active'][$data['id'][$i]]) ? true : false
                    ];

                    DB::unprepared('SET IDENTITY_INSERT salary_steps ON');
                    DB::table('salary_steps')->updateOrInsert(['id' => $id], $step_data);
                    DB::unprepared('SET IDENTITY_INSERT salary_steps OFF');
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Salary Step Setup',
                'activity' => 'Update',
                'description' => 'Updated salary step table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Salary step table updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update salary step table: ' . $e->getMessage());
        }
    }

    /**
     * Get salary step for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('salary_steps')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Salary step not found');
            }

            return $this->successResponse($data, 'Salary step retrieved for deletion');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary step: ' . $e->getMessage());
        }
    }

    /**
     * Delete salary step
     */
    public function destroy($id)
    {
        try {
            $record = DB::table('salary_steps')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Salary step not found');
            }

            DB::table('salary_steps')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Salary Step Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Salary Step informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Salary step deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete salary step: ' . $e->getMessage());
        }
    }

    /**
     * Show specific salary step
     */
    public function show($id)
    {
        try {
            $data = DB::table('salary_steps')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Salary step not found');
            }

            return $this->successResponse($data, 'Salary step retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary step: ' . $e->getMessage());
        }
    }

    /**
     * Create new salary step form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'active' => ['type' => 'boolean', 'required' => false]
                ]
            ], 'Create salary step form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit salary step form data
     */
    public function edit($id)
    {
        try {
            $data = DB::table('salary_steps')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Salary step not found');
            }

            return $this->successResponse($data, 'Salary step retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary step: ' . $e->getMessage());
        }
    }

    /**
     * Update salary step
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|max:255|unique:salary_steps,name,' . $id
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $record = DB::table('salary_steps')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Salary step not found');
            }

            $step_data = [
                'name' => $request->name,
                'active' => $request->has('active') ? true : false
            ];

            DB::table('salary_steps')->where('id', $id)->update($step_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Salary Step Setup',
                'activity' => 'Update',
                'description' => 'Updated salary step: ' . $request->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Salary step updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update salary step: ' . $e->getMessage());
        }
    }
}
