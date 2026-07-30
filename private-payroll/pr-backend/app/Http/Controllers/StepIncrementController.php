<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StepIncrementController extends Controller
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
     * Get all step increments
     */
    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            if (Auth::user()->access_all_branches) {
                $data = DB::table('step_increments as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->select(
                        'a.id',
                        'b.photo',
                        'a.effectivity_date',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.first_name,' ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                                END as name")

                    )
                    ->orderBy('a.effectivity_date', 'desc')
                    ->get();

                $no_steps = DB::table('employees as a')
                    ->join('positions as b', 'a.position_id', '=', 'b.id')
                    ->join('departments as c', 'a.department_id', '=', 'c.id')
                    ->join('salary_grades as d', 'a.salary_grade_id', '=', 'd.id')
                    ->join('salary_steps as e', 'a.salary_step_id', '=', 'e.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                                END as name"),
                        'c.name as department',
                        'b.name as position',
                        'd.name as salary_grade',
                        'e.name as salary_step',
                        'a.date_hired as effectivity_date'
                    )
                    ->where(['is_employee' => true, 'is_plantilla' => true])
                    ->whereYear('date_hired', '>=', Carbon::now()->subyear(3))
                    ->whereNotIn('a.id', function ($query) {
                        $query->select('employee_id')
                            ->from('step_increments');
                    });

                $employee_no_step = DB::table('employees as a')
                    ->join('positions as b', 'a.position_id', '=', 'b.id')
                    ->join('departments as c', 'a.department_id', '=', 'c.id')
                    ->join('salary_grades as d', 'a.salary_grade_id', '=', 'd.id')
                    ->join('salary_steps as e', 'a.salary_step_id', '=', 'e.id')
                    ->join('step_increments as f', 'a.id', '=', 'f.employee_id')
                    ->select(
                        'a.id',
                        'a.photo',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                                END as name"),
                        'c.name as department',
                        'b.name as position',
                        'd.name as salary_grade',
                        'e.name as salary_step',
                        'f.effectivity_date'
                    )
                    ->where(['is_employee' => true, 'is_plantilla' => true])
                    ->whereYear('date_hired', '>=', Carbon::now()->subyear(3))
                    ->whereIn('f.id', function ($query) {
                        $query->select(DB::raw("MAX(id)"),)->from('step_increments')->whereYear('effectivity_date', '<=', Carbon::now()->subyear(3))->groupBy('employee_id');
                    })
                    ->union($no_steps)
                    ->orderBy('effectivity_date', 'asc')
                    ->get();
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->get();

                $data = DB::table('step_increments as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->select(
                        'a.id',
                        'b.photo',
                        'a.effectivity_date',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.first_name,' ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                                END as name")
                    )
                    ->where('b.branch_id', $user_branch_id[0]->branch_id)
                    ->orderBy('a.effectivity_date', 'desc')
                    ->get();

                $no_steps = DB::table('employees as a')
                    ->join('positions as b', 'a.position_id', '=', 'b.id')
                    ->join('departments as c', 'a.department_id', '=', 'c.id')
                    ->join('salary_grades as d', 'a.salary_grade_id', '=', 'd.id')
                    ->join('salary_steps as e', 'a.salary_step_id', '=', 'e.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                                END as name"),
                        'c.name as department',
                        'b.name as position',
                        'd.name as salary_grade',
                        'e.name as salary_step',
                        'a.date_hired as effectivity_date'
                    )
                    ->where(['is_employee' => true, 'is_plantilla' => true, 'a.branch_id' => $user_branch_id[0]->branch_id])
                    ->whereYear('date_hired', '>=', Carbon::now()->subyear(3))
                    ->whereNotIn('a.id', function ($query) {
                        $query->select('employee_id')
                            ->from('step_increments');
                    });

                $employee_no_step = DB::table('employees as a')
                    ->join('positions as b', 'a.position_id', '=', 'b.id')
                    ->join('departments as c', 'a.department_id', '=', 'c.id')
                    ->join('salary_grades as d', 'a.salary_grade_id', '=', 'd.id')
                    ->join('salary_steps as e', 'a.salary_step_id', '=', 'e.id')
                    ->join('step_increments as f', 'a.id', '=', 'f.employee_id')
                    ->select(
                        'a.id',
                        'a.photo',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                                END as name"),
                        'c.name as department',
                        'b.name as position',
                        'd.name as salary_grade',
                        'e.name as salary_step',
                        'f.effectivity_date'
                    )
                    ->where(['is_employee' => true, 'is_plantilla' => true, 'a.branch_id' => $user_branch_id[0]->branch_id])
                    ->whereYear('date_hired', '>=', Carbon::now()->subyear(3))
                    ->whereIn('f.id', function ($query) {
                        $query->select(DB::raw("MAX(id)"),)->from('step_increments')->whereYear('effectivity_date', '<=', Carbon::now()->subyear(3))->groupBy('employee_id');
                    })
                    ->union($no_steps)
                    ->orderBy('effectivity_date', 'asc')
                    ->get();
            }

            return $this->successResponse([
                'step_increments' => $data,
                'employees_no_step' => $employee_no_step
            ], 'Step increments retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve step increments: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for step increment
     */
    public function add($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            if (Auth::user()->access_all_branches) {
                $employees = DB::table('employees')
                    ->select(
                        '*',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name"),
                        DB::raw("CASE WHEN (DATEDIFF(YEAR,employees.date_hired,GETDATE())) > 1 THEN 
                                        CONVERT(Nvarchar(20),DATEDIFF(YEAR,employees.date_hired,GETDATE())) + ' Years in service.' 
                                      ELSE 
                                        CONVERT(Nvarchar(20),DATEDIFF(YEAR,employees.date_hired,GETDATE())) + ' Year in service.'
                                      END as years_in_service")
                    )
                    ->where(['is_plantilla' => true, 'is_employee' => true, 'active' => true])
                    ->whereRaw("DATEDIFF(YEAR,employees.date_hired,GETDATE()) >= 3")
                    ->orderBy('employees.first_name', 'asc')
                    ->get();
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->get();

                $employees = DB::table('employees')
                    ->select(
                        '*',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name"),
                        DB::raw("CASE WHEN (DATEDIFF(YEAR,employees.date_hired,GETDATE())) > 1 THEN 
                                        CONVERT(Nvarchar(20),DATEDIFF(YEAR,employees.date_hired,GETDATE())) + ' Years in service.' 
                                      ELSE 
                                        CONVERT(Nvarchar(20),DATEDIFF(YEAR,employees.date_hired,GETDATE())) + ' Year in service.'
                                      END as years_in_service")
                    )
                    ->where(['is_plantilla' => true, 'is_employee' => true, 'active' => true, 'branch_id' => $user_branch_id[0]->branch_id])
                    ->whereRaw("DATEDIFF(YEAR,employees.date_hired,GETDATE()) >= 3")
                    ->orderBy('employees.first_name', 'asc')
                    ->get();
            }

            $salary_grades = DB::table('salary_grades')->where('active', true)->orderBy('id', 'asc')->get();
            $salary_steps = DB::table('salary_steps')->where('active', true)->orderBy('id', 'asc')->get();

            if ($id == 0) {

                $dummy_step_increments = array(
                    'id' => 0,
                    'employee_id' => 0,
                    'effectivity_date' => null,
                    'current_salary_grade_id' => 0,
                    'current_salary_step_id' => 0,
                    'current_salary' => null,
                    'new_salary_grade_id' => 0,
                    'new_salary_step_id' => 0,
                    'new_salary' => null,
                    'new_tax_amount' => null,
                    'new_gsis_amount' => null,
                    'new_sss_amount' => null,
                    'new_pagibig_amount' => null,
                    'new_philhealth_amount' => null,
                    'position' => null,
                    'department' => null,
                    'employment_type' => null,
                );

                $step_increments = (object)$dummy_step_increments;
                $step_increments = collect([$step_increments]);
            } else {
                $step_increments = DB::table('step_increments as s')
                    ->join('employees as a', 'a.id', '=', 's.employee_id')
                    ->join('plantillas as b', 'b.id', '=', 'a.plantilla_id')
                    ->join('positions as c', 'c.id', '=', 'b.position_id')
                    ->join('departments as d', 'd.id', '=', 'a.department_id')
                    ->join('employment_types as e', 'e.id', '=', 'a.employment_type_id')
                    ->select(
                        's.*',
                        'c.name as position',
                        'd.name as department',
                        'e.name as employment_type'
                    )
                    ->where('s.id', $id)
                    ->get();
            }

            return $this->successResponse([
                'employees' => $employees,
                'salary_grades' => $salary_grades,
                'salary_steps' => $salary_steps,
                'step_increments' => $step_increments
            ], 'Step increment form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load step increment form data: ' . $e->getMessage());
        }
    }

    /**
     * Store step increment
     */
    public function store(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'employee_id' => 'required',
                'effectivity_date' => 'required',
                'new_salary_step' => 'required',
                'new_salary' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $effectivity_date = Carbon::parse($request->effectivity_date);

            if ($id == 0) {
                $step_increments = DB::table('step_increments as s')
                    ->select(
                        's.*'
                    )
                    ->where('s.employee_id', $request->employee_id)
                    ->whereDate('effectivity_date', $effectivity_date)
                    ->get();

                if ($step_increments->isNotEmpty()) {
                    return $this->errorResponse('Step Increment for this employee have already been processed. Please check!');
                } else {
                    $id = DB::table('step_increments')->max('id') + 1;
                }
            }

            $data_step_increment = array(
                'employee_id' => $request->employee_id,
                'effectivity_date' => $request->effectivity_date,
                'current_salary_grade_id' => $request->current_grade_id,
                'current_salary_step_id' => $request->current_step_id,
                'current_salary' => $request->current_salary,
                'new_salary_grade_id' => $request->new_grade_id,
                'new_salary_step_id' => $request->new_step_id,
                'new_salary' => $request->new_salary,
                'new_tax_amount' => $request->new_tax_amount,
                'new_gsis_amount' => $request->new_gsis_amount,
                'new_sss_amount' => $request->new_sss_amount,
                'new_pagibig_amount' => $request->new_pagibig_amount,
                'new_philhealth_amount' => $request->new_philhealth_amount
            );

            DB::unprepared('SET IDENTITY_INSERT step_increments ON');
            DB::table('step_increments')->updateOrInsert(['id' => $id], $data_step_increment);
            DB::unprepared('SET IDENTITY_INSERT step_increments OFF');

            $employee_id = $request->employee_id;

            // Update Employee Info
            $data_employee = [
                'salary_step_id' => $request->new_step_id,
                'salary' => $request->new_salary,
                'tax_amount' => $request->new_tax_amount,
                'gsis_amount' => $request->new_gsis_amount,
                'sss_amount' => $request->new_sss_amount,
                'pagibig_amount' => $request->new_pagibig_amount,
                'philhealth_amount' => $request->new_philhealth_amount
            ];

            DB::table('employees')
                ->where('id', $employee_id)
                ->update($data_employee);

            // Save Service Record
            $employees = DB::table('employees')->where('id', $employee_id)->get();

            $position_name = DB::table('positions')->select('name')->where('id', $employees[0]->position_id)->get();
            $employment_type_name = DB::table('employment_types')->select('name')->where('id', $employees[0]->employment_type_id)->get();
            $anual_salary = ($request->new_salary * 12);
            $company_name = DB::table('companies')->select('name')->where('id', 1)->get();;
            $branch_name = DB::table('branches')->select('name')->where('id', $employees[0]->branch_id)->get();;

            $serv_data = [
                'employee_id' => $employee_id,
                'start_date' => $request->effectivity_date,
                'end_date' => $request->effectivity_date,
                'designation' => isset($position_name[0]->name) ? $position_name[0]->name : '',
                'employment_type' => $employment_type_name[0]->name,
                'annual_salary' => $anual_salary,
                'place_of_assignment' => isset($company_name[0]->name) ? $company_name[0]->name : '',
                'leave_without_pay' => 0,
                'separation_date' => null,
                'cause' => 'Step Increment',
                'branch' => isset($branch_name[0]->name) ? $branch_name[0]->name : '',
            ];

            $serv_id = DB::table('service_records')->max('service_record_id') + 1;

            DB::unprepared('SET IDENTITY_INSERT service_records ON');
            DB::table('service_records')->updateOrInsert(['service_record_id' => $serv_id], $serv_data);
            DB::unprepared('SET IDENTITY_INSERT service_records OFF');

            //Save audit trail
            if ($id == 0) {

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'Step Increment',
                    'activity' => 'Add',
                    'description' => 'Added employee step increment informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Employee step increment added successfully');
            } else {

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'Step Increment',
                    'activity' => 'Update',
                    'description' => 'Updated employee step increment informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Employee step increment updated successfully');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save step increment: ' . $e->getMessage());
        }
    }

    /**
     * Add step increment for multiple employees
     */
    public function addStep(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['employee_id'])) {
                return $this->errorResponse('No employee Selected!');
            }

            $arr_len = count($data['employee_id']);
            $processed_count = 0;

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['employee_id'][$i] != NULL) {
                    $empid = $data['employee_id'][$i];
                    $employeestep = DB::table('employees as a')
                        ->join('plantillas as b', 'b.id', '=', 'a.plantilla_id')
                        ->join('positions as c', 'c.id', '=', 'b.position_id')
                        ->join('departments as d', 'd.id', '=', 'a.department_id')
                        ->join('employment_types as e', 'e.id', '=', 'a.employment_type_id')
                        ->select(
                            'a.id',
                            'a.plantilla_id',
                            'a.salary_grade_id',
                            'a.salary_step_id as curr_salary_step_id',
                            DB::raw("(ISNULL(a.salary_step_id,0) + 1) as salary_step_id"),
                            'a.salary',
                            'c.name as position',
                            'd.name as department',
                            'e.name as employment_type'
                        )
                        ->where('a.id', $empid)
                        ->get();

                    $step_id = $employeestep[0]->salary_step_id;
                    $plantilla_id = $employeestep[0]->plantilla_id;

                    $plantillastep = DB::table('employees')
                        ->join('salary_schedules_details', function ($join) use ($step_id) {
                            $join->on('employees.salary_grade_id', '=', 'salary_schedules_details.salary_grade_id')
                                ->on('salary_schedules_details.salary_step_id', '=', DB::raw($step_id));
                        })
                        ->join('salary_schedules', 'salary_schedules.id', '=', 'salary_schedules_details.salary_schedule_id')
                        ->select('employees.salary_grade_id', 'employees.salary_step_id', 'salary_schedules_details.amount')
                        ->where(['employees.id' => $empid, 'salary_schedules.active' => 1])->get();
                    if ($plantillastep->isNotEmpty()) {
                        $amount = $plantillastep[0]->amount;
                    } else {
                        return $this->errorResponse('Salary details not found. Please update salary schedule.');
                    }

                    // get daily rate
                    $daily_rate = ($amount / 22);

                    // get hourly rate
                    $hourly_rate = (($amount / 22) / 8);

                    // get pagibig amount
                    $pagibig_amount = 100;

                    // get philhealth amount
                    $ph_data = DB::table('philhealths')->orderBy('year', 'desc')->get();
                    $ph_multiplier = $ph_data[0]->multiplier;
                    $ph_income_ceilling = $ph_data[0]->income_ceiling;
                    $ph_income_floor = $ph_data[0]->income_floor;
                    $ph_fix_rate = $ph_data[0]->fix_rate;

                    if ($amount >= $ph_income_ceilling) {
                        $philhealth_amount = $ph_fix_rate;
                    } elseif ($amount <= $ph_income_floor) {
                        $philhealth_amount = 0;
                    } else {
                        $philhealth_amount = (($amount * $ph_multiplier) / 2);
                    }

                    // get gsis amount
                    $gsis_data = DB::table('gsis')->orderBy('year', 'desc')->get();
                    $gsis_amount = ($amount * $gsis_data[0]->multiplier);

                    // get sss amount
                    $sss_amount = 0;

                    // get tax amount
                    $tax_data = DB::table('tax_tables')->get();
                    $arr_len = DB::table('tax_tables')->count('id');

                    $taxable_amount = (($amount) - (($gsis_amount) + ($philhealth_amount) + ($pagibig_amount)));
                    $tax_amount = 0;

                    for ($i = 0; $i < $arr_len; $i++) {
                        if ($taxable_amount >= $tax_data[$i]->min_amount && $taxable_amount <= $tax_data[$i]->max_amount) {
                            $tax_amount = ((($taxable_amount - $tax_data[$i]->min_amount) * $tax_data[$i]->percentage) + $tax_data[$i]->base_tax);
                        }
                    }

                    $data_step_increment = array(
                        'employee_id' => $empid,
                        'effectivity_date' => now(),
                        'current_salary_grade_id' => $employeestep[0]->salary_grade_id,
                        'current_salary_step_id' => $employeestep[0]->curr_salary_step_id,
                        'current_salary' => $employeestep[0]->salary,
                        'new_salary_grade_id' => $employeestep[0]->salary_grade_id,
                        'new_salary_step_id' => $employeestep[0]->salary_step_id,
                        'new_salary' => $amount,
                        'new_tax_amount' => $tax_amount,
                        'new_gsis_amount' => $gsis_amount,
                        'new_sss_amount' => $sss_amount,
                        'new_pagibig_amount' => $pagibig_amount,
                        'new_philhealth_amount' => $philhealth_amount
                    );

                    $id = DB::table('step_increments')->max('id') + 1;

                    DB::unprepared('SET IDENTITY_INSERT step_increments ON');
                    DB::table('step_increments')->updateOrInsert(['id' => $id], $data_step_increment);
                    DB::unprepared('SET IDENTITY_INSERT step_increments OFF');

                    $employee_id = $empid;

                    // Update Employee Info
                    $data_employee = array(
                        'salary_step_id' => $employeestep[0]->salary_step_id,
                        'salary' => $amount,
                        'tax_amount' => $tax_amount,
                        'gsis_amount' => $gsis_amount,
                        'sss_amount' => $sss_amount,
                        'pagibig_amount' => $pagibig_amount,
                        'philhealth_amount' => $philhealth_amount
                    );

                    DB::table('employees')->where('id', $employee_id)->update($data_employee);

                    // Save Service Record
                    $employees = DB::table('employees')->where('id', $employee_id)->get();

                    $position_name = DB::table('positions')->select('name')->where('id', $employees[0]->position_id)->get();
                    $employment_type_name = DB::table('employment_types')->select('name')->where('id', $employees[0]->employment_type_id)->get();
                    $anual_salary = ($amount * 12);
                    $company_name = DB::table('companies')->select('name')->where('id', 1)->get();;
                    $branch_name = DB::table('branches')->select('name')->where('id', $employees[0]->branch_id)->get();;

                    $serv_data = [
                        'employee_id' => $employee_id,
                        'start_date' => now(),
                        'end_date' => null,
                        'designation' => isset($position_name[0]->name) ? $position_name[0]->name : '',
                        'employment_type' => $employment_type_name[0]->name,
                        'annual_salary' => $anual_salary,
                        'place_of_assignment' => isset($company_name[0]->name) ? $company_name[0]->name : '',
                        'leave_without_pay' => 0,
                        'separation_date' => null,
                        'cause' => 'Step Increment',
                        'branch' => isset($branch_name[0]->name) ? $branch_name[0]->name : '',
                    ];

                    $serv_id = DB::table('service_records')->max('service_record_id') + 1;

                    DB::unprepared('SET IDENTITY_INSERT service_records ON');
                    DB::table('service_records')->updateOrInsert(['service_record_id' => $serv_id], $serv_data);
                    DB::unprepared('SET IDENTITY_INSERT service_records OFF');

                    //Save audit trail
                    $data_audit = array(
                        'user_id' => Auth::user()->id,
                        'module'  => 'HR Module',
                        'menu'    => 'Step Increment',
                        'activity' => 'Add',
                        'description' => 'Added employee step increment informations.',
                    );

                    Audit::create($data_audit);
                    $processed_count++;
                }
            }

            return $this->successResponse(['processed_count' => $processed_count], 'Successfully processed ' . $processed_count . ' employee step increments');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process step increments: ' . $e->getMessage());
        }
    }

    /**
     * Show specific step increment
     */
    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            
            $data = DB::table('step_increments as a')
                ->join('employees as b', 'b.id', '=', 'a.employee_id')
                ->select(
                    'a.*',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as employee_name")
                )
                ->where('a.id', $id)
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Step increment not found');
            }

            return $this->successResponse($data, 'Step increment retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve step increment: ' . $e->getMessage());
        }
    }

    /**
     * Delete step increment
     */
    public function destroy($id)
    {
        try {
            $step_increment = DB::table('step_increments')->where('id', $id)->first();

            if (!$step_increment) {
                return $this->notFoundResponse('Step increment not found');
            }

            DB::table('step_increments')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Step Increment',
                'activity' => 'Delete',
                'description' => 'Deleted step increment informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Step increment deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete step increment: ' . $e->getMessage());
        }
    }
}
