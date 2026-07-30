<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\EmployeePromotion;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeePromotionController extends Controller
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
                    ->join('employee_promotions', 'employee_promotions.employee_id', '=', 'employees.id')
                    ->join('promotion_natures', 'promotion_natures.id', '=', 'employee_promotions.nature_of_appointment_id')
                    ->select(
                        'employee_promotions.id',
                        'employees.id as employee_id',
                        'employees.photo',
                        'employees.employee_no',
                        'promotion_natures.name as nature',
                        'employee_promotions.date_of_effectivity as effectivity',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                            END as name")
                    )
                    ->orderBy('employee_promotions.date_of_effectivity', 'desc')
                    ->get();
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->get();

                $data = DB::table('employees')
                    ->join('employee_promotions', 'employee_promotions.employee_id', '=', 'employees.id')
                    ->join('promotion_natures', 'promotion_natures.id', '=', 'employee_promotions.nature_of_appointment_id')
                    ->select(
                        'employee_promotions.id',
                        'employees.id as employee_id',
                        'employees.photo',
                        'employees.employee_no',
                        'promotion_natures.name as nature',
                        'employee_promotions.date_of_effectivity as effectivity',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                            END as name")
                    )
                    ->where('employees.branch_id', $user_branch_id[0]->branch_id)
                    ->orderBy('employee_promotions.date_of_effectivity', 'desc')
                    ->get();
            }

            return $this->successResponse($data, 'Employee promotions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee promotions: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $natures = DB::table('promotion_natures')->where('active', true)->orderBy('id', 'asc')->get();
            $branches = DB::table('branches')->orderBy('id', 'asc')->get();
            $departments = DB::table('departments')->where('active', true)->orderBy('name', 'asc')->get();
            $employment_types = DB::table('employment_types')->where('active', true)->orderBy('id', 'asc')->get();
            $positions = DB::table('positions')->where('active', true)->orderBy('name', 'asc')->get();
            $user_branch_id = DB::table('users as a')
                ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('b.branch_id')
                ->where('a.id', Auth::user()->id)
                ->get();

            // Get Plantilla
            $plantillas = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->select('plantillas.*')
                ->where(['plantillas.employee_id' => 0, 'plantillas.active' => true])
                ->get();

            $salary_grades = DB::table('salary_grades')->where('active', true)->orderBy('id', 'asc')->get();
            $salary_steps = DB::table('salary_steps')->where('active', true)->orderBy('id', 'asc')->get();
            $payroll_intervals = DB::table('payroll_intervals')->where('active', true)->orderBy('id', 'asc')->get();

            if (Auth::user()->access_all_branches) {
                $employees = DB::table('employees')
                    ->select(
                        'id',
                        'photo',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                            END as name")
                    )
                    ->where([
                        'is_employee' => true,
                        'active' => true
                    ])
                    ->orderBy('first_name', 'asc')
                    ->get();
            } else {
                $employees = DB::table('employees')
                    ->select(
                        'id',
                        'photo',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                            END as name")
                    )
                    ->where([
                        'is_employee' => true,
                        'active' => true,
                        'branch_id' => $user_branch_id[0]->branch_id
                    ])
                    ->orderBy('first_name', 'asc')
                    ->get();
            }

            if ($id == 0) {
                $dummy_promotions = array(
                    'id' => 0,
                    'nature_of_appointment_id' => 0,
                    'employee_id' => 0,
                    'branch_id' => 0,
                    'department_id' => 0,
                    'employment_type_id' => 0,
                    'payroll_interval_id' => 0,
                    'date_position_appointed' => null,
                    'is_plantilla' => false,
                    'is_teaching' => false,
                    'plantilla_id' => 0,
                    'position_id' => 0,
                    'date_of_effectivity' => null,
                    'old_salary' => null,
                    'old_tax_amount' => null,
                    'old_gsis_amount' => null,
                    'old_sss_amount' => null,
                    'old_pagibig_amount' => null,
                    'old_philhealth_amount' => null,
                    'new_salary' => null,
                    'new_tax_amount' => null,
                    'new_gsis_amount' => null,
                    'new_sss_amount' => null,
                    'new_pagibig_amount' => null,
                    'new_philhealth_amount' => null
                );

                $promotions = (object)$dummy_promotions;
                $promotions = collect([$promotions]);
            } else {
                $promotions = DB::table('employee_promotions')->where('id', $id)->get();
            }

            return $this->successResponse([
                'natures' => $natures,
                'employees' => $employees,
                'branches' => $branches,
                'departments' => $departments,
                'employment_types' => $employment_types,
                'plantillas' => $plantillas,
                'salary_grades' => $salary_grades,
                'salary_steps' => $salary_steps,
                'positions' => $positions,
                'payroll_intervals' => $payroll_intervals,
                'promotions' => $promotions
            ], 'Employee promotion form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employee promotion form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'nature_of_appointment_id' => 'required|integer|exists:promotion_natures,id',
                'employee_id' => 'required|integer|exists:employees,id',
                'branch_id' => 'required|integer|exists:branches,id',
                'department_id' => 'required|integer|exists:departments,id',
                'employment_type_id' => 'required|integer|exists:employment_types,id',
                'payroll_interval_id' => 'required|integer|exists:payroll_intervals,id',
                'date_position_appointed' => 'required|date',
                'position_id' => 'required|integer|exists:positions,id',
                'date_of_effectivity' => 'required|date',
                'new_salary' => 'required|numeric|min:0',
                'old_salary' => 'nullable|numeric|min:0',
                'old_tax_amount' => 'nullable|numeric|min:0',
                'old_gsis_amount' => 'nullable|numeric|min:0',
                'old_sss_amount' => 'nullable|numeric|min:0',
                'old_pagibig_amount' => 'nullable|numeric|min:0',
                'old_philhealth_amount' => 'nullable|numeric|min:0',
                'new_tax_amount' => 'nullable|numeric|min:0',
                'new_gsis_amount' => 'nullable|numeric|min:0',
                'new_sss_amount' => 'nullable|numeric|min:0',
                'new_pagibig_amount' => 'nullable|numeric|min:0',
                'new_philhealth_amount' => 'nullable|numeric|min:0',
                'plantilla_id' => 'nullable|integer|exists:plantillas,id',
                'salary_grade_id' => 'nullable|integer|exists:salary_grades,id',
                'salary_step_id' => 'nullable|integer|exists:salary_steps,id'
            ], [
                'nature_of_appointment_id.required' => 'Nature of appointment is required.',
                'nature_of_appointment_id.exists' => 'Selected nature of appointment does not exist.',
                'employee_id.required' => 'Employee is required.',
                'employee_id.exists' => 'Selected employee does not exist.',
                'branch_id.required' => 'Branch is required.',
                'branch_id.exists' => 'Selected branch does not exist.',
                'department_id.required' => 'Department is required.',
                'department_id.exists' => 'Selected department does not exist.',
                'employment_type_id.required' => 'Employment type is required.',
                'employment_type_id.exists' => 'Selected employment type does not exist.',
                'payroll_interval_id.required' => 'Payroll interval is required.',
                'payroll_interval_id.exists' => 'Selected payroll interval does not exist.',
                'date_position_appointed.required' => 'Date position appointed is required.',
                'date_position_appointed.date' => 'Date position appointed must be a valid date.',
                'position_id.required' => 'Position is required.',
                'position_id.exists' => 'Selected position does not exist.',
                'date_of_effectivity.required' => 'Date of effectivity is required.',
                'date_of_effectivity.date' => 'Date of effectivity must be a valid date.',
                'new_salary.required' => 'New salary is required.',
                'new_salary.numeric' => 'New salary must be a number.',
                'new_salary.min' => 'New salary must be at least 0.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Save Promotion
            $data_promotion = array(
                'nature_of_appointment_id' => $request->nature_of_appointment_id,
                'employee_id' => $request->employee_id,
                'branch_id' => $request->branch_id,
                'department_id' => $request->department_id,
                'employment_type_id' => $request->employment_type_id,
                'payroll_interval_id' => $request->payroll_interval_id,
                'date_position_appointed' => $request->date_position_appointed,
                'is_plantilla' => $request->has('is_plantilla') ? true : false,
                'is_teaching' => $request->has('is_teaching') ? true : false,
                'plantilla_id' => isset($request->plantilla_id) ? $request->plantilla_id : 0,
                'position_id' => $request->position_id,
                'date_of_effectivity' => $request->date_of_effectivity,
                'old_salary' => $request->old_salary,
                'old_tax_amount' => $request->old_tax_amount,
                'old_gsis_amount' => $request->old_gsis_amount,
                'old_sss_amount' => $request->old_sss_amount,
                'old_pagibig_amount' => $request->old_pagibig_amount,
                'old_philhealth_amount' => $request->old_philhealth_amount,
                'new_salary' => $request->new_salary,
                'new_tax_amount' => $request->new_tax_amount,
                'new_gsis_amount' => $request->new_gsis_amount,
                'new_sss_amount' => $request->new_sss_amount,
                'new_pagibig_amount' => $request->new_pagibig_amount,
                'new_philhealth_amount' => $request->new_philhealth_amount
            );

            if ($id == 0) {
                $id = 0 + DB::table('employee_promotions')->max('id');
                $id += 1;
            }

            DB::unprepared('SET IDENTITY_INSERT employee_promotions ON');
            DB::table('employee_promotions')->updateOrInsert(['id' => $id], $data_promotion);
            DB::unprepared('SET IDENTITY_INSERT employee_promotions OFF');

            $employee_id = $request->employee_id;

            // Update Employee Info
            $data_employee = array(
                'branch_id' => $request->branch_id,
                'department_id' => $request->department_id,
                'employment_type_id' => $request->employment_type_id,
                'payroll_interval_id' => $request->payroll_interval_id,
                'is_plantilla' => $request->has('is_plantilla') ? true : false,
                'is_teaching' => $request->has('is_teaching') ? true : false,
                'plantilla_id' => isset($request->plantilla_id) ? $request->plantilla_id : 0,
                'salary_grade_id' => $request->salary_grade_id,
                'salary_step_id' => $request->salary_step_id,
                'position_id' => $request->position_id,
                'salary' => $request->new_salary,
                'tax_amount' => isset($request->new_tax_amount) ? $request->new_tax_amount : 0,
                'gsis_amount' => isset($request->new_gsis_amount) ? $request->new_gsis_amount : 0,
                'sss_amount' => isset($request->new_sss_amount) ? $request->new_sss_amount : 0,
                'pagibig_amount' => isset($request->new_pagibig_amount) ? $request->new_pagibig_amount : 0,
                'philhealth_amount' => isset($request->new_philhealth_amount) ? $request->new_philhealth_amount : 0
            );

            DB::table('employees')->where('id', $employee_id)->update($data_employee);

            // Update Plantilla
            if ($request->has('is_plantilla')) {
                $plantilla_data_reset = array(
                    'employee_id' => 0
                );

                DB::table('plantillas')->where('employee_id', $employee_id)->update($plantilla_data_reset);

                $plantilla_data = array(
                    'employee_id' => $employee_id
                );

                DB::table('plantillas')->where('id', $request->plantilla_id)->update($plantilla_data);
            } else {
                $plantilla_data = array(
                    'employee_id' => 0
                );

                DB::table('plantillas')->where('employee_id', $employee_id)->update($plantilla_data);
            }

            // Save Service Record
            $position_name = DB::table('positions')->select('name')->where('id', $request->position_id)->get();
            $employment_type_name = DB::table('employment_types')->select('name')->where('id', $request->employment_type_id)->get();
            $anual_salary = ($request->new_salary * 12);
            $company_name = DB::table('companies')->select('name')->where('id', 1)->get();
            $branch_name = DB::table('branches')->select('name')->where('id', $request->branch_id)->get();

            $serv_data = [
                'employee_id' => $employee_id,
                'start_date' => $request->date_of_effectivity,
                'end_date' => $request->date_of_effectivity,
                'designation' => isset($position_name[0]->name) ? $position_name[0]->name : '',
                'employment_type' => isset($employment_type_name[0]->name) ? $employment_type_name[0]->name : '',
                'annual_salary' => $anual_salary,
                'place_of_assignment' => isset($company_name[0]->name) ? $company_name[0]->name : '',
                'leave_without_pay' => 0,
                'separation_date' => null,
                'cause' => 'Promotion',
                'branch' => isset($branch_name[0]->name) ? $branch_name[0]->name : '',
            ];

            $serv_id = 0;
            $serv_id = 0 + DB::table('service_records')->max('service_record_id');
            $serv_id += 1;

            DB::unprepared('SET IDENTITY_INSERT service_records ON');
            DB::table('service_records')->updateOrInsert(['service_record_id' => $serv_id], $serv_data);
            DB::unprepared('SET IDENTITY_INSERT service_records OFF');

            //Save audit trail
            if ($id == 0) {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'Promotion',
                    'activity' => 'Add',
                    'description' => 'Added employee promotion information',
                );

                Audit::create($data_audit);

                return $this->successResponse([
                    'id' => $id,
                    'employee_id' => $request->employee_id,
                    'nature_of_appointment_id' => $request->nature_of_appointment_id,
                    'branch_id' => $request->branch_id,
                    'department_id' => $request->department_id,
                    'employment_type_id' => $request->employment_type_id,
                    'position_id' => $request->position_id,
                    'date_of_effectivity' => $request->date_of_effectivity,
                    'new_salary' => $request->new_salary,
                    'service_record_id' => $serv_id,
                    'summary' => [
                        'action' => 'added',
                        'employee_id' => $request->employee_id,
                        'position' => isset($position_name[0]->name) ? $position_name[0]->name : '',
                        'employment_type' => isset($employment_type_name[0]->name) ? $employment_type_name[0]->name : '',
                        'date_of_effectivity' => $request->date_of_effectivity,
                        'old_salary' => $request->old_salary,
                        'new_salary' => $request->new_salary,
                        'salary_increase' => $request->new_salary - ($request->old_salary ?? 0),
                        'service_record_id' => $serv_id
                    ]
                ], 'Employee promotion information added successfully!');
            } else {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'Promotion',
                    'activity' => 'Update',
                    'description' => 'Updated employee promotion information',
                );

                Audit::create($data_audit);

                return $this->successResponse([
                    'id' => $id,
                    'employee_id' => $request->employee_id,
                    'nature_of_appointment_id' => $request->nature_of_appointment_id,
                    'branch_id' => $request->branch_id,
                    'department_id' => $request->department_id,
                    'employment_type_id' => $request->employment_type_id,
                    'position_id' => $request->position_id,
                    'date_of_effectivity' => $request->date_of_effectivity,
                    'new_salary' => $request->new_salary,
                    'service_record_id' => $serv_id,
                    'summary' => [
                        'action' => 'updated',
                        'employee_id' => $request->employee_id,
                        'position' => isset($position_name[0]->name) ? $position_name[0]->name : '',
                        'employment_type' => isset($employment_type_name[0]->name) ? $employment_type_name[0]->name : '',
                        'date_of_effectivity' => $request->date_of_effectivity,
                        'old_salary' => $request->old_salary,
                        'new_salary' => $request->new_salary,
                        'salary_increase' => $request->new_salary - ($request->old_salary ?? 0),
                        'service_record_id' => $serv_id
                    ]
                ], 'Employee promotion information updated successfully!');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save employee promotion information: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $promotion = DB::table('employee_promotions as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('promotion_natures as c', 'a.nature_of_appointment_id', '=', 'c.id')
                ->join('branches as d', 'a.branch_id', '=', 'd.id')
                ->join('departments as e', 'a.department_id', '=', 'e.id')
                ->join('employment_types as f', 'a.employment_type_id', '=', 'f.id')
                ->join('positions as g', 'a.position_id', '=', 'g.id')
                ->join('payroll_intervals as h', 'a.payroll_interval_id', '=', 'h.id')
                ->select(
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as employee_name"),
                    'b.employee_no',
                    'c.name as nature_name',
                    'd.name as branch_name',
                    'e.name as department_name',
                    'f.name as employment_type_name',
                    'g.name as position_name',
                    'h.name as payroll_interval_name'
                )
                ->where('a.id', $id)
                ->first();

            if (!$promotion) {
                return $this->notFoundResponse('Promotion record not found');
            }

            return $this->successResponse([
                'promotion' => $promotion,
                'summary' => [
                    'promotion_id' => $promotion->id,
                    'employee_id' => $promotion->employee_id,
                    'employee_name' => $promotion->employee_name,
                    'employee_no' => $promotion->employee_no,
                    'nature' => $promotion->nature_name,
                    'position' => $promotion->position_name,
                    'department' => $promotion->department_name,
                    'branch' => $promotion->branch_name,
                    'employment_type' => $promotion->employment_type_name,
                    'date_of_effectivity' => $promotion->date_of_effectivity,
                    'old_salary' => $promotion->old_salary,
                    'new_salary' => $promotion->new_salary,
                    'salary_increase' => $promotion->new_salary - ($promotion->old_salary ?? 0)
                ]
            ], 'Employee promotion data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee promotion data: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $app_key = env("APP_KEY", "");

            $natures = DB::table('promotion_natures')->where('active', true)->orderBy('id', 'asc')->get();
            $branches = DB::table('branches')->orderBy('id', 'asc')->get();
            $departments = DB::table('departments')->where('active', true)->orderBy('name', 'asc')->get();
            $employment_types = DB::table('employment_types')->where('active', true)->orderBy('id', 'asc')->get();
            $positions = DB::table('positions')->where('active', true)->orderBy('name', 'asc')->get();
            $salary_grades = DB::table('salary_grades')->where('active', true)->orderBy('id', 'asc')->get();
            $salary_steps = DB::table('salary_steps')->where('active', true)->orderBy('id', 'asc')->get();
            $payroll_intervals = DB::table('payroll_intervals')->where('active', true)->orderBy('id', 'asc')->get();

            // Get Plantilla
            $plantillas = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->select('plantillas.*')
                ->where(['plantillas.employee_id' => 0, 'plantillas.active' => true])
                ->get();

            $employees = DB::table('employees')
                ->select(
                    'id',
                    'photo',
                    'employee_no',
                    'salary',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                            END as name")
                )
                ->where(['is_employee' => true, 'active' => true])
                ->orderBy('first_name', 'asc')
                ->get();

            return $this->successResponse([
                'natures' => $natures,
                'employees' => $employees,
                'branches' => $branches,
                'departments' => $departments,
                'employment_types' => $employment_types,
                'plantillas' => $plantillas,
                'salary_grades' => $salary_grades,
                'salary_steps' => $salary_steps,
                'positions' => $positions,
                'payroll_intervals' => $payroll_intervals,
                'fields' => [
                    'nature_of_appointment_id' => ['type' => 'select', 'required' => true, 'label' => 'Nature of Appointment'],
                    'employee_id' => ['type' => 'select', 'required' => true, 'label' => 'Employee'],
                    'branch_id' => ['type' => 'select', 'required' => true, 'label' => 'Branch'],
                    'department_id' => ['type' => 'select', 'required' => true, 'label' => 'Department'],
                    'employment_type_id' => ['type' => 'select', 'required' => true, 'label' => 'Employment Type'],
                    'payroll_interval_id' => ['type' => 'select', 'required' => true, 'label' => 'Payroll Interval'],
                    'date_position_appointed' => ['type' => 'date', 'required' => true, 'label' => 'Date Position Appointed'],
                    'position_id' => ['type' => 'select', 'required' => true, 'label' => 'Position'],
                    'date_of_effectivity' => ['type' => 'date', 'required' => true, 'label' => 'Date of Effectivity'],
                    'new_salary' => ['type' => 'number', 'required' => true, 'label' => 'New Salary'],
                    'old_salary' => ['type' => 'number', 'required' => false, 'label' => 'Old Salary'],
                    'is_plantilla' => ['type' => 'checkbox', 'required' => false, 'label' => 'Is Plantilla'],
                    'is_teaching' => ['type' => 'checkbox', 'required' => false, 'label' => 'Is Teaching'],
                    'plantilla_id' => ['type' => 'select', 'required' => false, 'label' => 'Plantilla'],
                    'salary_grade_id' => ['type' => 'select', 'required' => false, 'label' => 'Salary Grade'],
                    'salary_step_id' => ['type' => 'select', 'required' => false, 'label' => 'Salary Step']
                ]
            ], 'Create employee promotion form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }
}
