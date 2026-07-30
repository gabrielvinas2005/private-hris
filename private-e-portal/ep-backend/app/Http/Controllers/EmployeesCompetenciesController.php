<?php

namespace App\Http\Controllers;

use Auth;
use Image;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class EmployeesCompetenciesController extends Controller
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

            if (Auth::user()->access_all_branches) {
                $data = DB::table('employees')
                    ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                    ->join('departments', 'departments.id', '=', 'employees.department_id')
                    ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                    ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                    ->leftJoin('plantillas', 'plantillas.id', '=', 'employees.plantilla_id')
                    ->select(
                        'employees.photo',
                        'employees.id',
                        'employees.employee_no',
                        'employees.email',
                        'employees.salary_grade_id',
                        'employees.plantilla_id',
                        'employees.position_id',
                        'plantillas.code as plantilla_code',
                        'employment_types.name as employment_type',
                        'positions.name as position',
                        'departments.name as department',
                        'branches.name as branch',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                    )
                    ->orderBy('employees.first_name', 'asc')
                    ->where([
                        'employees.is_employee' => true,
                        'employees.active' => true,
                        'employees.is_plantilla' => true
                    ])
                    ->get();
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->get();

                $data = DB::table('employees')
                    ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                    ->join('departments', 'departments.id', '=', 'employees.department_id')
                    ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                    ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                    ->leftJoin('plantillas', 'plantillas.id', '=', 'employees.plantilla_id')
                    ->select(
                        'employees.photo',
                        'employees.id',
                        'employees.employee_no',
                        'employees.email',
                        'employees.salary_grade_id',
                        'employees.plantilla_id',
                        'employees.position_id',
                        'plantillas.code as plantilla_code',
                        'employment_types.name as employment_type',
                        'positions.name as position',
                        'departments.name as department',
                        'branches.name as branch',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                    )
                    ->orderBy('employees.first_name', 'asc')
                    ->where([
                        'employees.is_employee' => true,
                        'employees.active' => true,
                        'employees.branch_id' => $user_branch_id[0]->branch_id,
                        'employees.is_plantilla' => true
                    ])
                    ->get();
            }

            return $this->successResponse($data, 'Employee competencies retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee competencies: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->join('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->leftJoin('plantillas', 'plantillas.id', '=', 'employees.plantilla_id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.employee_no',
                    'employees.email',
                    'employees.salary_grade_id',
                    'employees.plantilla_id',
                    'employees.position_id',
                    'plantillas.code as plantilla_code',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                )
                ->orderBy('employees.first_name', 'asc')
                ->where([
                    'employees.is_employee' => true,
                    'employees.active' => true,
                    'employees.is_plantilla' => true,
                    'employees.id' => $id
                ])
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $plantilla_id = $data[0]->plantilla_id;
            $position_id = $data[0]->position_id;

            $competency = DB::table('competencies as a')
                ->leftjoin('subcompetencies as b', 'a.id', '=', 'b.competency_id')
                ->leftjoin('plantilla_competencies as c', 'b.id', '=', 'c.subcompetency_id')
                ->leftjoin('employee_competencies as d', function ($join) {
                    $join->on('c.plantilla_id', '=', 'd.plantilla_id');
                    $join->on('c.subcompetency_id', '=', 'd.subcompetency_id');
                })
                ->select(
                    'a.id',
                    'a.name',
                    'b.id as subcompetency_id',
                    'b.name as subcompetency_name',
                    'b.description as subcompetency_description',
                    'b.code as subcompetency_code',
                    'c.level',
                    'd.level_attained'
                )
                ->where([
                    'c.plantilla_id' => $plantilla_id
                ])
                ->get();

            $competency2 = DB::table('competencies as a')
                ->leftjoin('subcompetencies as b', 'a.id', '=', 'b.competency_id')
                ->leftjoin('plantilla_competencies as c', 'b.id', '=', 'c.subcompetency_id')
                ->select(
                    'a.id',
                    'a.name',
                    'b.id as subcompetency_id',
                    'b.name as subcompetency_name',
                    'b.description as subcompetency_description',
                    'b.code as subcompetency_code',
                    'c.level',
                    db::raw("cast(0 as int) as level_attained")
                )
                ->where([
                    'c.plantilla_id' => $plantilla_id
                ])
                ->get();

            if ($competency->isEmpty() && $competency2->isNotEmpty()) {
                $grouped_arr = [];

                foreach ($competency2 as $element) {
                    $elemName = $element->name;

                    if (!isset($grouped_arr[$elemName])) {
                        $grouped_arr[$elemName] = [];
                    }

                    array_push($grouped_arr[$elemName], $element);
                }
            } else if ($competency->isEmpty() && $competency2->isEmpty()) {
                return $this->errorResponse('Competencies for this position has not been setup. Please update needed data for this position.', 400);
            } else {
                $grouped_arr = [];

                foreach ($competency as $element) {
                    $elemName = $element->name;

                    if (!isset($grouped_arr[$elemName])) {
                        $grouped_arr[$elemName] = [];
                    }

                    array_push($grouped_arr[$elemName], $element);
                }
            }

            return $this->successResponse([
                'employee' => $data[0],
                'competencies' => $grouped_arr,
                'summary' => [
                    'employee_id' => $id,
                    'employee_name' => $data[0]->name,
                    'employee_no' => $data[0]->employee_no,
                    'position' => $data[0]->position,
                    'department' => $data[0]->department,
                    'branch' => $data[0]->branch,
                    'plantilla_id' => $plantilla_id,
                    'position_id' => $position_id,
                    'competencies_count' => count($grouped_arr),
                    'subcompetencies_count' => array_sum(array_map('count', $grouped_arr))
                ]
            ], 'Employee competencies retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee competencies: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'subcompetency_id' => 'required|array',
                'subcompetency_id.*' => 'integer|exists:subcompetencies,id',
                'level_attained' => 'required|array',
                'level_attained.*' => 'integer|min:0|max:5'
            ], [
                'subcompetency_id.required' => 'Subcompetency IDs are required.',
                'subcompetency_id.array' => 'Subcompetency IDs must be an array.',
                'subcompetency_id.*.integer' => 'Subcompetency ID must be an integer.',
                'subcompetency_id.*.exists' => 'Selected subcompetency does not exist.',
                'level_attained.required' => 'Level attained values are required.',
                'level_attained.array' => 'Level attained values must be an array.',
                'level_attained.*.integer' => 'Level attained must be an integer.',
                'level_attained.*.min' => 'Level attained must be at least 0.',
                'level_attained.*.max' => 'Level attained cannot exceed 5.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->join('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.employee_no',
                    'employees.email',
                    'employees.salary_grade_id',
                    'employees.plantilla_id',
                    'employees.position_id',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                )
                ->orderBy('employees.first_name', 'asc')
                ->where([
                    'employees.is_employee' => true,
                    'employees.active' => true,
                    'employees.is_plantilla' => true,
                    'employees.id' => $id
                ])
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $plantilla_id = $data[0]->plantilla_id;
            $position_id = $data[0]->position_id;

            // Save Competencies
            $data_competencies = $request->all();
            $arr_len_competencies = count($data_competencies["subcompetency_id"]);
            $competencies_data = [];
            $updated_count = 0;
            $created_count = 0;

            for ($i = 0; $i < $arr_len_competencies; $i++) {
                if ($data_competencies["subcompetency_id"][$i] != NULL) {
                    $competencies_data = [
                        'plantilla_id' => $plantilla_id,
                        'employee_id' => $id,
                        'subcompetency_id' => $data_competencies["subcompetency_id"][$i],
                        'level_attained' => $data_competencies["level_attained"][$i] == null ? 0 : $data_competencies["level_attained"][$i]
                    ];

                    $existing = DB::table('employee_competencies')
                        ->where('employee_id', $id)
                        ->where('subcompetency_id', $data_competencies["subcompetency_id"][$i])
                        ->first();

                    if ($existing) {
                        DB::table('employee_competencies')
                            ->where('employee_id', $id)
                            ->where('subcompetency_id', $data_competencies["subcompetency_id"][$i])
                            ->update($competencies_data);
                        $updated_count++;
                    } else {
                        DB::table('employee_competencies')->insert($competencies_data);
                        $created_count++;
                    }
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Competency View',
                'activity' => 'Update',
                'description' => 'Update competency details for employee ' . $data[0]->name . '.',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'employee_id' => $id,
                'employee_name' => $data[0]->name,
                'plantilla_id' => $plantilla_id,
                'position_id' => $position_id,
                'summary' => [
                    'created_count' => $created_count,
                    'updated_count' => $updated_count,
                    'total_processed' => $created_count + $updated_count
                ]
            ], 'Employee competency details updated successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update employee competency details: ' . $e->getMessage());
        }
    }

    public function details($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $users_data = DB::table('users')->where('id', $id)->get();

            if ($users_data->isEmpty()) {
                return $this->notFoundResponse('User not found');
            }

            $emp_no = $users_data[0]->employee_no;

            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->join('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->leftJoin('plantillas', 'plantillas.id', '=', 'employees.plantilla_id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.employee_no',
                    'employees.email',
                    'employees.salary_grade_id',
                    'employees.plantilla_id',
                    'employees.position_id',
                    'plantillas.code as plantilla_code',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                )
                ->orderBy('employees.first_name', 'asc')
                ->where([
                    'employees.is_employee' => true,
                    'employees.active' => true,
                    'employees.is_plantilla' => true,
                    'employees.employee_no' => $emp_no
                ])
                ->get();

            if ($data->isEmpty()) {
                $plantilla_id = 0;
                $position_id = 0;
                $emp_id = 0;
            } else {
                $plantilla_id = $data[0]->plantilla_id;
                $position_id = $data[0]->position_id;
                $emp_id = $data[0]->id;
            }

            $competency = DB::table('competencies as a')
                ->leftjoin('subcompetencies as b', 'a.id', '=', 'b.competency_id')
                ->leftjoin('plantilla_competencies as c', 'b.id', '=', 'c.subcompetency_id')
                ->leftjoin('employee_competencies as d', function ($join) {
                    $join->on('c.plantilla_id', '=', 'd.plantilla_id');
                    $join->on('c.subcompetency_id', '=', 'd.subcompetency_id');
                })
                ->select(
                    'a.id',
                    'a.name',
                    'b.id as subcompetency_id',
                    'b.name as subcompetency_name',
                    'b.description as subcompetency_description',
                    'b.code as subcompetency_code',
                    'c.level',
                    'd.level_attained'
                )
                ->where([
                    'c.plantilla_id' => $plantilla_id,
                    'd.employee_id' => $emp_id
                ])
                ->get();

            $grouped_arr = [];

            foreach ($competency as $element) {
                $elemName = $element->name;

                if (!isset($grouped_arr[$elemName])) {
                    $grouped_arr[$elemName] = [];
                }

                array_push($grouped_arr[$elemName], $element);
            }

            return $this->successResponse([
                'employee' => $data->isEmpty() ? null : $data[0],
                'competencies' => $grouped_arr,
                'summary' => [
                    'user_id' => $id,
                    'employee_no' => $emp_no,
                    'employee_id' => $emp_id,
                    'plantilla_id' => $plantilla_id,
                    'position_id' => $position_id,
                    'competencies_count' => count($grouped_arr),
                    'subcompetencies_count' => array_sum(array_map('count', $grouped_arr)),
                    'has_competencies' => !empty($grouped_arr)
                ]
            ], 'Employee competencies retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee competency details: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->join('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->leftJoin('plantillas', 'plantillas.id', '=', 'employees.plantilla_id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.employee_no',
                    'employees.email',
                    'employees.salary_grade_id',
                    'employees.plantilla_id',
                    'employees.position_id',
                    'plantillas.code as plantilla_code',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                )
                ->where([
                    'employees.is_employee' => true,
                    'employees.active' => true,
                    'employees.is_plantilla' => true,
                    'employees.id' => $id
                ])
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Employee not found');
            }

            return $this->successResponse([
                'employee' => $data,
                'summary' => [
                    'employee_id' => $data->id,
                    'employee_name' => $data->name,
                    'employee_no' => $data->employee_no,
                    'position' => $data->position,
                    'department' => $data->department,
                    'branch' => $data->branch,
                    'plantilla_id' => $data->plantilla_id,
                    'position_id' => $data->position_id
                ]
            ], 'Employee competency data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee competency data: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->join('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->leftJoin('plantillas', 'plantillas.id', '=', 'employees.plantilla_id')
                ->select(
                    'employees.id',
                    'employees.employee_no',
                    'employees.plantilla_id',
                    'employees.position_id',
                    'plantillas.code as plantilla_code',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                )
                ->where([
                    'employees.is_employee' => true,
                    'employees.active' => true,
                    'employees.is_plantilla' => true
                ])
                ->orderBy('employees.first_name', 'asc')
                ->get();

            return $this->successResponse([
                'employees' => $employees,
                'fields' => [
                    'employee_id' => ['type' => 'select', 'required' => true, 'label' => 'Employee'],
                    'subcompetency_id' => ['type' => 'array', 'required' => true, 'label' => 'Subcompetencies'],
                    'level_attained' => ['type' => 'array', 'required' => true, 'label' => 'Level Attained', 'min' => 0, 'max' => 5]
                ]
            ], 'Create employee competency form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }
}
