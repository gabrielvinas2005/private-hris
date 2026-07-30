<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\SalaryGrade;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryGradeController extends Controller
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
     * Get all salary grades
     */
    public function index()
    {
        try {
            $data = DB::table('salary_grades')
                ->select('id', 'name', 'active')
                ->where('active', 1)
                ->orderby('id', 'asc')
                ->get();

            return $this->successResponse($data, 'Salary grades retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary grades: ' . $e->getMessage());
        }
    }

    /**
     * Store salary grades
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['name']) || !is_array($data['name'])) {
                return $this->errorResponse('Invalid data format. Expected array of salary grades.');
            }

            $arr_len = count($data['name']);
            $grade_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL) {

                    if ($data['id'][$i] == null || $data['id'][$i] == 0) {

                        $is_exist = DB::table('salary_grades')->where('name', $data['name'][$i])->get();

                        if ($is_exist->isNotEmpty()) {
                            return $this->errorResponse('"' . $data['name'][$i] . '" already exist.');
                        }

                        $id = 0 + DB::table('salary_grades')->max('id');
                        $id += 1;
                    } else {
                        $id = $data['id'][$i];
                    }

                    $grade_data = [
                        'name' => $data['name'][$i],
                        'active' => isset($data['active'][$data['id'][$i]]) ? true : false
                    ];

                    DB::unprepared('SET IDENTITY_INSERT salary_grades ON');
                    DB::table('salary_grades')->updateOrInsert(['id' => $id], $grade_data);
                    DB::unprepared('SET IDENTITY_INSERT salary_grades OFF');
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Salary Grade Setup',
                'activity' => 'Update',
                'description' => 'Updated salary grade table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Salary grade table updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update salary grade table: ' . $e->getMessage());
        }
    }

    /**
     * Get salary grade for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('salary_grades')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Salary grade not found');
            }

            return $this->successResponse($data, 'Salary grade retrieved for deletion');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary grade: ' . $e->getMessage());
        }
    }

    /**
     * Delete salary grade
     */
    public function destroy($id)
    {
        try {
            $record = DB::table('salary_grades')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Salary grade not found');
            }

            DB::table('salary_grades')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Salary Grade Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Salary Grade informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Salary grade deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete salary grade: ' . $e->getMessage());
        }
    }

    /**
     * Show specific salary grade
     */
    public function show($id)
    {
        try {
            $data = DB::table('salary_grades')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Salary grade not found');
            }

            return $this->successResponse($data, 'Salary grade retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary grade: ' . $e->getMessage());
        }
    }

    /**
     * Create new salary grade form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'active' => ['type' => 'boolean', 'required' => false]
                ]
            ], 'Create salary grade form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit salary grade form data
     */
    public function edit($id)
    {
        try {
            $data = DB::table('salary_grades')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Salary grade not found');
            }

            return $this->successResponse($data, 'Salary grade retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary grade: ' . $e->getMessage());
        }
    }

    /**
     * Update salary grade
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $record = DB::table('salary_grades')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Salary grade not found');
            }

            $grade_data = [
                'name' => $request->name,
                'active' => $request->has('active') ? true : false
            ];

            DB::table('salary_grades')->where('id', $id)->update($grade_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Salary Grade Setup',
                'activity' => 'Update',
                'description' => 'Updated salary grade: ' . $request->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Salary grade updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update salary grade: ' . $e->getMessage());
        }
    }
}
