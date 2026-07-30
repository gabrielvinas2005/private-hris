<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class EmployeeStepIncrementController extends Controller
{
    use ApiResponse, GeneratesPdf;

    public function index()
    {
        try {
            $step_increments = DB::table("step_increments as a")
                ->join('months as b', 'a.month_id', '=', 'b.id')
                ->select(
                    'b.name as month',
                    'a.month_id',
                    'a.year_id as year',
                    'a.is_forwarded',
                    'a.is_approved',
                    'a.is_disapproved'
                )
                ->groupBy(
                    'b.name',
                    'a.month_id',
                    'a.year_id',
                    'a.is_forwarded',
                    'a.is_approved',
                    'a.is_disapproved'
                )
                ->distinct()
                ->get();

            return $this->successResponse($step_increments, 'Step increments retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve step increments: ' . $e->getMessage());
        }
    }

    public function add()
    {
        try {
            return $this->successResponse([], 'Step increment form loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load step increment form: ' . $e->getMessage());
        }
    }

    public function loadEmployees($month_id, $year_id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $salary_steps = DB::table('salary_steps')->get();

            // check if data exists
            $step_increments_data = DB::table('step_increments')
                ->where([
                    'month_id' => $month_id,
                    'year_id' => $year_id,
                ])
                ->get();

            $except_employees = DB::table('step_increments')
                ->select('employee_id')
                ->where([
                    'month_id' => $month_id,
                    'year_id' => $year_id
                ])
                ->whereRaw("(DATEDIFF(D,effectivity_date,GETDATE())/365) < 3")
                ->get();

            if ($step_increments_data->isNotEmpty()) {
                $employees = DB::table('step_increments as s')
                    ->join('employees as a', 's.employee_id', '=', 'a.id')
                    ->leftJoin('departments as b', 'a.department_id', '=', 'b.id')
                    ->leftJoin('divisions as c', 'a.division_id', '=', 'c.id')
                    ->leftJoin('positions as d', 'a.position_id', '=', 'd.id')
                    ->leftJoin('employment_types as e', 'a.employment_type_id', '=', 'e.id')
                    ->leftJoin('salary_grades as f', 's.current_salary_grade_id', '=', 'f.id')
                    ->leftJoin('salary_steps as g', 's.current_salary_step_id', '=', 'g.id')
                    ->leftJoin('salary_grades as ff', 's.new_salary_grade_id', '=', 'ff.id')
                    ->leftJoin('salary_steps as gg', 's.new_salary_step_id', '=', 'gg.id')
                    ->select(
                        DB::raw("CAST(1 AS BIT) as [select]"),
                        's.id as step_increment_id',
                        'a.id as employee_id',
                        'a.employee_no',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                                END as name"),
                        DB::raw("CONVERT(Nvarchar(20),(DATEDIFF(D,a.date_hired,'$year_id-$month_id-01')/365)) + ' Year in service.' as years_in_service"),
                        'b.name as department',
                        'c.name as division',
                        'd.name as position',
                        'e.name as employment_type',
                        'f.name as salary_grade',
                        'g.name as salary_step',
                        'a.salary',
                        'ff.name as new_salary_grade',
                        'gg.name as new_salary_step',
                        's.new_salary',
                        's.current_salary_step_id as salary_step_id'
                    )
                    ->where([
                        's.month_id' => $month_id,
                        's.year_id' => $year_id,
                        'a.active' => true,
                        'a.is_employee' => true,
                    ])
                    ->whereNotIn('a.id', function ($query) {
                        $query->select('employee_id')->from('step_increments')
                            ->whereRaw("(DATEDIFF(D,effectivity_date,GETDATE())/365) < 3")
                            ->where('is_disapproved', false);
                    })
                    ->orderBy('name')
                    ->distinct()
                    ->get();
            } else {
                $employees = DB::table('employees as a')
                    ->leftJoin('departments as b', 'a.department_id', '=', 'b.id')
                    ->leftJoin('divisions as c', 'a.division_id', '=', 'c.id')
                    ->leftJoin('positions as d', 'a.position_id', '=', 'd.id')
                    ->leftJoin('employment_types as e', 'a.employment_type_id', '=', 'e.id')
                    ->leftJoin('salary_grades as f', 'a.salary_grade_id', '=', 'f.id')
                    ->leftJoin('salary_steps as g', 'a.salary_step_id', '=', 'g.id')
                    ->leftJoin('plantillas as p', 'a.plantilla_id', '=', 'p.id')
                    ->leftJoin('salary_schedules_details as scd', function ($join) {
                        $join->on('p.salary_grade_id', '=', 'scd.salary_grade_id')
                            ->on('scd.salary_step_id', '=', db::raw('isnull(a.salary_step_id,0) + 1'));
                    })
                    ->leftJoin('salary_schedules as sch', 'sch.id', '=', 'scd.salary_schedule_id')
                    ->select(
                        DB::raw("CAST(1 AS BIT) as [select]"),
                        DB::raw('CAST(0 as int) as step_increment_id'),
                        'a.id as employee_id',
                        'a.employee_no',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                                END as name"),
                        DB::raw("CONVERT(Nvarchar(20),(DATEDIFF(D,a.date_hired,'$year_id-$month_id-01')/365)) + ' Year in service.' as years_in_service"),
                        'b.name as department',
                        'c.name as division',
                        'd.name as position',
                        'e.name as employment_type',
                        'f.name as salary_grade',
                        'g.name as salary_step',
                        'a.salary',
                        'f.name as new_salary_grade',
                        'g.name as new_salary_step',
                        'scd.amount as new_salary',
                        DB::raw("(a.salary_step_id + 1) as salary_step_id")
                    )
                    ->whereRaw("(DATEDIFF(D,a.date_hired,'$year_id-$month_id-01')/365) >= 3")
                    ->where([
                        'a.active' => true,
                        'a.is_employee' => true,
                        'sch.active' => true,
                    ])
                    ->whereNotIn('a.id', function ($query) {
                        $query->select('employee_id')->from('step_increments')
                            ->whereRaw("(DATEDIFF(D,effectivity_date,GETDATE())/365) < 3")
                            ->where('is_disapproved', false);
                    })
                    ->orderBy('name')
                    ->get();
            }

            return $this->successResponse([
                'salary_steps' => $salary_steps,
                'employees' => $employees
            ], 'Step increment table data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employees for step increment: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            set_time_limit(16000);

            $data = $request->all();
            $month_id = $request->month_id;
            $year_id = $request->year_id;
            $effectivity_date = $request->date_of_effectivity;

            if (!$request->has('employee_id')) {
                return $this->errorResponse('Save failed! No employee(s) to add for step increment.', 400);
            }

            if (!$request->has('select')) {
                return $this->errorResponse('Save failed! Please select atleast one employee to add for step increment process.', 400);
            }

            // Delete Record First
            DB::table('step_increments')->where([
                'month_id' => $month_id,
                'year_id' => $year_id,
                'effectivity_date' => $effectivity_date,
            ])->delete();

            for ($i = 0; $i < count($data['step_increment_id']); $i++) {
                if (in_array($data['employee_id'][$i], $data['select'],)) {


                    $employee = DB::table('employees')->where('id', $data['employee_id'][$i])->get();

                    $plantilla_id = $employee[0]->plantilla_id;
                    $current_grade_id = $employee[0]->salary_grade_id;
                    $current_step_id = $employee[0]->salary_step_id;
                    $current_salary = $employee[0]->salary;
                    $new_salary_step = $data["new_salary_step"][$i];

                    $plantilla = DB::table('plantillas')
                        ->join('salary_schedules_details', function ($join) use ($new_salary_step) {
                            $join->on('plantillas.salary_grade_id', '=', 'salary_schedules_details.salary_grade_id')
                                ->on('salary_schedules_details.salary_step_id', '=', DB::raw($new_salary_step));
                        })
                        ->join('salary_schedules', 'salary_schedules.id', '=', 'salary_schedules_details.salary_schedule_id')
                        ->select(
                            'plantillas.salary_grade_id',
                            'plantillas.salary_step_id',
                            'plantillas.position_id',
                            'salary_schedules_details.amount'
                        )
                        ->where([
                            'plantillas.id' => $plantilla_id,
                            'salary_schedules.active' => 1
                        ])
                        ->get();

                    if ($plantilla->isNotEmpty()) {
                        $amount = $plantilla[0]->amount;

                        // get pagibig amount
                        $pagibig_amount = 100;

                        // get philhealth amount
                        $ph_data = DB::table('philhealths')->orderBy('year', 'desc')->get();

                        if ($ph_data->isNotEmpty()) {
                            $ph_multiplier = $ph_data[0]->multiplier;
                            $ph_income_ceilling = $ph_data[0]->income_ceiling;
                            $ph_income_floor = $ph_data[0]->income_floor;
                            $ph_fix_rate = $ph_data[0]->fix_rate;
                        } else {
                            $ph_multiplier = 0;
                            $ph_income_ceilling = 0;
                            $ph_income_floor = 0;
                            $ph_fix_rate = 0;
                        }

                        if ($amount >= $ph_income_ceilling) {
                            $philhealth_amount = $ph_fix_rate;
                        } elseif ($amount <= $ph_income_floor) {
                            $philhealth_amount = 0;
                        } else {
                            $philhealth_amount = (($amount * $ph_multiplier) / 2);
                        }

                        // get gsis amount
                        $gsis_data = DB::table('gsis')->orderBy('year', 'desc')->get();

                        if ($gsis_data->isNotEmpty()) {
                            $gsis_amount = ($amount * $gsis_data[0]->multiplier);
                        } else {
                            $gsis_amount = ($amount * $gsis_data[0]->multiplier);
                        }

                        // get sss amount
                        $sss_amount = 0;

                        // get tax amount
                        $tax_data = DB::table('tax_tables')->get();

                        if ($tax_data->isNotEmpty()) {
                            $arr_len = DB::table('tax_tables')->count('id');

                            $taxable_amount = (($amount) - (($gsis_amount) + ($philhealth_amount) + ($pagibig_amount)));
                            $tax_amount = 0;

                            for ($ii = 0; $ii < $arr_len; $ii++) {
                                if ($taxable_amount >= $tax_data[$ii]->min_amount && $taxable_amount <= $tax_data[$ii]->max_amount) {
                                    $tax_amount = ((($taxable_amount - $tax_data[$ii]->min_amount) * $tax_data[$ii]->percentage) + $tax_data[$ii]->base_tax);
                                    break;
                                } else {
                                    $tax_amount = 0;
                                }
                            }
                        } else {
                            $tax_amount = 0;
                        }

                        $new_pagibig_amount = $pagibig_amount;
                        $new_philhealth_amount = $philhealth_amount;
                        $new_gsis_amount = $gsis_amount;
                        $new_sss_amount = $sss_amount;
                        $new_tax_amount = $tax_amount;
                    } else {
                        $amount = 0;

                        $new_pagibig_amount = 0;
                        $new_philhealth_amount = 0;
                        $new_gsis_amount = 0;
                        $new_sss_amount = 0;
                        $new_tax_amount = 0;
                    }

                    $data_exist = DB::table('step_increments')
                        ->where([
                            'month_id' => $month_id,
                            'year_id' => $year_id,
                            'employee_id' => $data['employee_id'][$i],
                        ])
                        ->get();

                    if ($data_exist->isNotEmpty()) {
                        $data_step_increment = array(
                            'employee_id' => $data['employee_id'][$i],
                            'month_id' => $month_id,
                            'year_id' => $year_id,
                            'effectivity_date' => $effectivity_date,
                            'current_salary_grade_id' => $current_grade_id,
                            'current_salary_step_id' => $current_step_id,
                            'current_salary' => $current_salary,
                            'new_salary_grade_id' => $current_grade_id,
                            'new_salary_step_id' => $new_salary_step,
                            'new_salary' => $amount,
                            'new_tax_amount' => $new_tax_amount,
                            'new_gsis_amount' => $new_gsis_amount,
                            'new_sss_amount' => $new_sss_amount,
                            'new_pagibig_amount' => $new_pagibig_amount,
                            'new_philhealth_amount' => $new_philhealth_amount
                        );

                        DB::table('step_increments')->where([
                            'id' => $data['step_increment_id'][$i],
                        ])->update($data_step_increment);
                    } else {
                        $data_step_increment = array(
                            'month_id' => $month_id,
                            'year_id' => $year_id,
                            'employee_id' => $data['employee_id'][$i],
                            'effectivity_date' => $effectivity_date,
                            'current_salary_grade_id' => $current_grade_id,
                            'current_salary_step_id' => $current_step_id,
                            'current_salary' => $current_salary,
                            'new_salary_grade_id' => $current_grade_id,
                            'new_salary_step_id' => $new_salary_step,
                            'new_salary' => $amount,
                            'new_tax_amount' => $new_tax_amount,
                            'new_gsis_amount' => $new_gsis_amount,
                            'new_sss_amount' => $new_sss_amount,
                            'new_pagibig_amount' => $new_pagibig_amount,
                            'new_philhealth_amount' => $new_philhealth_amount
                        );

                        DB::table('step_increments')->insert($data_step_increment);
                    }
                }
            }

            return $this->successResponse(['month_id' => $month_id, 'year_id' => $year_id], 'Successfully Saved Step Increment.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save step increment: ' . $e->getMessage());
        }
    }

    public function changeNewSalary($step_increment_id, $employee_id)
    {
        try {
            $employee = DB::table('employees')->where('id', $employee_id)->get();

            $plantilla_id = $employee[0]->plantilla_id;

            $plantilla = DB::table('plantillas')
                ->join('salary_schedules_details', function ($join) use ($step_increment_id) {
                    $join->on('plantillas.salary_grade_id', '=', 'salary_schedules_details.salary_grade_id')
                        ->on('salary_schedules_details.salary_step_id', '=', DB::raw($step_increment_id));
                })
                ->join('salary_schedules', 'salary_schedules.id', '=', 'salary_schedules_details.salary_schedule_id')
                ->select(
                    'plantillas.salary_grade_id',
                    'plantillas.salary_step_id',
                    'plantillas.position_id',
                    'salary_schedules_details.amount'
                )
                ->where([
                    'plantillas.id' => $plantilla_id,
                    'salary_schedules.active' => 1
                ])
                ->get();

            if ($plantilla->isNotEmpty()) {
                $amount = $plantilla[0]->amount;
            } else {
                $amount = 0;
            }

            return $this->successResponse(['salary' => $amount], 'Salary calculated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to calculate salary: ' . $e->getMessage());
        }
    }

    public function edit($month_id, $year_id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $salary_steps = DB::table('salary_steps')->get();

            $step_increment_data = DB::table('step_increments as a')
                ->join('months as b', 'b.id', '=', 'a.month_id')
                ->select(
                    'a.*',
                    'b.name as month'
                )
                ->where([
                    'a.month_id' => $month_id,
                    'a.year_id' => $year_id,
                ])
                ->get();

            $employees_unselected = DB::table('employees as a')
                ->leftJoin('departments as b', 'a.department_id', '=', 'b.id')
                ->leftJoin('divisions as c', 'a.division_id', '=', 'c.id')
                ->leftJoin('positions as d', 'a.position_id', '=', 'd.id')
                ->leftJoin('employment_types as e', 'a.employment_type_id', '=', 'e.id')
                ->leftJoin('salary_grades as f', 'a.salary_grade_id', '=', 'f.id')
                ->leftJoin('salary_steps as g', 'a.salary_step_id', '=', 'g.id')
                ->leftJoin('plantillas as p', 'a.plantilla_id', '=', 'p.id')
                ->leftJoin('salary_schedules_details as scd', function ($join) {
                    $join->on('p.salary_grade_id', '=', 'scd.salary_grade_id')
                        ->on('scd.salary_step_id', '=', db::raw('isnull(a.salary_step_id,0) + 1'));
                })
                ->leftJoin('salary_schedules as sch', 'sch.id', '=', 'scd.salary_schedule_id')
                ->select(
                    DB::raw("CAST(0 AS BIT) as [select]"),
                    DB::raw('CAST(0 as int) as step_increment_id'),
                    'a.id as employee_id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    DB::raw("CONVERT(Nvarchar(20),(DATEDIFF(D,a.date_hired,'$year_id-$month_id-01')/365)) + ' Year in service.' as years_in_service"),
                    'b.name as department',
                    'c.name as division',
                    'd.name as position',
                    'e.name as employment_type',
                    'f.name as salary_grade',
                    'g.name as salary_step',
                    'a.salary',
                    'f.name as new_salary_grade',
                    'g.name as new_salary_step',
                    'scd.amount as new_salary',
                    DB::raw("(a.salary_step_id + 1) as salary_step_id")
                )
                ->whereRaw("(DATEDIFF(D,a.date_hired,'$year_id-$month_id-01')/365) >= 3")
                ->where([
                    'a.active' => true,
                    'a.is_employee' => true,
                    'sch.active' => true,
                ])
                ->whereNotIn('a.id', function ($query) {
                    $query->select('employee_id')->from('step_increments')
                        ->whereRaw("(DATEDIFF(D,effectivity_date,GETDATE())/365) < 3")
                        ->where('is_disapproved', false);
                });

            $employees = DB::table('step_increments as s')
                ->join('employees as a', 's.employee_id', '=', 'a.id')
                ->leftJoin('departments as b', 'a.department_id', '=', 'b.id')
                ->leftJoin('divisions as c', 'a.division_id', '=', 'c.id')
                ->leftJoin('positions as d', 'a.position_id', '=', 'd.id')
                ->leftJoin('employment_types as e', 'a.employment_type_id', '=', 'e.id')
                ->leftJoin('salary_grades as f', 's.current_salary_grade_id', '=', 'f.id')
                ->leftJoin('salary_steps as g', 's.current_salary_step_id', '=', 'g.id')
                ->leftJoin('salary_grades as ff', 's.new_salary_grade_id', '=', 'ff.id')
                ->leftJoin('salary_steps as gg', 's.new_salary_step_id', '=', 'gg.id')
                ->select(
                    DB::raw("CAST(1 AS BIT) as [select]"),
                    's.id as step_increment_id',
                    'a.id as employee_id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    DB::raw("CONVERT(Nvarchar(20),(DATEDIFF(D,a.date_hired,'$year_id-$month_id-01')/365)) + ' Year in service.' as years_in_service"),
                    'b.name as department',
                    'c.name as division',
                    'd.name as position',
                    'e.name as employment_type',
                    'f.name as salary_grade',
                    'g.name as salary_step',
                    'a.salary',
                    'ff.name as new_salary_grade',
                    'gg.name as new_salary_step',
                    's.new_salary',
                    's.new_salary_step_id as salary_step_id'
                )
                ->where([
                    's.month_id' => $month_id,
                    's.year_id' => $year_id,
                    'a.active' => true,
                    'a.is_employee' => true,
                ])
                ->union($employees_unselected)
                ->orderBy('name')
                ->distinct()
                ->get();

            return $this->successResponse([
                'salary_steps' => $salary_steps,
                'employees' => $employees,
                'step_increment_data' => $step_increment_data
            ], 'Employee step increment edit data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employee step increment edit data: ' . $e->getMessage());
        }
    }

    public function forwarded($month_id, $year_id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $salary_steps = DB::table('salary_steps')->get();

            $step_increment_data = DB::table('step_increments as a')
                ->join('months as b', 'b.id', '=', 'a.month_id')
                ->select(
                    'a.*',
                    'b.name as month'
                )
                ->where([
                    'a.month_id' => $month_id,
                    'a.year_id' => $year_id,
                ])
                ->get();

            $employees = DB::table('step_increments as s')
                ->join('employees as a', 's.employee_id', '=', 'a.id')
                ->leftJoin('departments as b', 'a.department_id', '=', 'b.id')
                ->leftJoin('divisions as c', 'a.division_id', '=', 'c.id')
                ->leftJoin('positions as d', 'a.position_id', '=', 'd.id')
                ->leftJoin('employment_types as e', 'a.employment_type_id', '=', 'e.id')
                ->leftJoin('salary_grades as f', 's.current_salary_grade_id', '=', 'f.id')
                ->leftJoin('salary_steps as g', 's.current_salary_step_id', '=', 'g.id')
                ->leftJoin('salary_grades as ff', 's.new_salary_grade_id', '=', 'ff.id')
                ->leftJoin('salary_steps as gg', 's.new_salary_step_id', '=', 'gg.id')
                ->select(
                    's.id as step_increment_id',
                    'a.id as employee_id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    DB::raw("CONVERT(Nvarchar(20),(DATEDIFF(D,a.date_hired,'$year_id-$month_id-01')/365)) + ' Year in service.' as years_in_service"),
                    'b.name as department',
                    'c.name as division',
                    'd.name as position',
                    'e.name as employment_type',
                    'f.name as salary_grade',
                    'g.name as salary_step',
                    'a.salary',
                    'ff.name as new_salary_grade',
                    'gg.name as new_salary_step',
                    's.new_salary',
                    's.current_salary_step_id as salary_step_id',
                    's.is_forwarded',
                    's.is_approved',
                    's.is_disapproved'
                )
                ->where([
                    's.month_id' => $month_id,
                    's.year_id' => $year_id,
                    'a.active' => true,
                    'a.is_employee' => true,
                ])
                ->orderBy('name')
                ->distinct()
                ->get();

            return $this->successResponse([
                'salary_steps' => $salary_steps,
                'employees' => $employees,
                'step_increment_data' => $step_increment_data
            ], 'Employee step increment forwarded data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employee step increment forwarded data: ' . $e->getMessage());
        }
    }

    function forwardApprover($month_id, $year_id)
    {
        try {
            DB::table('step_increments')->where([
                'month_id' => $month_id,
                'year_id' => $year_id
            ])->update([
                'is_forwarded' => true,
                'forwarded_date' => now(),
            ]);

            return $this->successResponse(['month_id' => $month_id, 'year_id' => $year_id], 'Successfully Forwarded to Approver.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to forward to approver: ' . $e->getMessage());
        }
    }

    function process()
    {
        try {
            $step_increments = DB::table("step_increments as a")
                ->join('months as b', 'a.month_id', '=', 'b.id')
                ->select(
                    'b.name as month',
                    'a.month_id',
                    'a.year_id as year',
                    'a.effectivity_date',
                    'a.forwarded_date',
                    'a.approved_date',
                    'a.is_forwarded',
                    'a.is_approved',
                    'a.is_disapproved'
                )
                ->where('a.is_forwarded', true)
                ->groupBy(
                    'b.name',
                    'a.month_id',
                    'a.year_id',
                    'a.effectivity_date',
                    'a.forwarded_date',
                    'a.approved_date',
                    'a.is_forwarded',
                    'a.is_approved',
                    'a.is_disapproved'
                )
                ->distinct()
                ->get();

            return $this->successResponse($step_increments, 'Step increments for approval retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve step increments for approval: ' . $e->getMessage());
        }
    }

    public function forProcess($month_id, $year_id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $salary_steps = DB::table('salary_steps')->get();

            $step_increment_data = DB::table('step_increments as a')
                ->join('months as b', 'b.id', '=', 'a.month_id')
                ->select(
                    'a.*',
                    'b.name as month'
                )
                ->where([
                    'a.month_id' => $month_id,
                    'a.year_id' => $year_id,
                ])
                ->get();

            $employees = DB::table('step_increments as s')
                ->join('employees as a', 's.employee_id', '=', 'a.id')
                ->leftJoin('departments as b', 'a.department_id', '=', 'b.id')
                ->leftJoin('divisions as c', 'a.division_id', '=', 'c.id')
                ->leftJoin('positions as d', 'a.position_id', '=', 'd.id')
                ->leftJoin('employment_types as e', 'a.employment_type_id', '=', 'e.id')
                ->leftJoin('salary_grades as f', 's.current_salary_grade_id', '=', 'f.id')
                ->leftJoin('salary_steps as g', 's.current_salary_step_id', '=', 'g.id')
                ->leftJoin('salary_grades as ff', 's.new_salary_grade_id', '=', 'ff.id')
                ->leftJoin('salary_steps as gg', 's.new_salary_step_id', '=', 'gg.id')
                ->select(
                    's.id as step_increment_id',
                    'a.id as employee_id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    DB::raw("CONVERT(Nvarchar(20),(DATEDIFF(D,a.date_hired,'$year_id-$month_id-01')/365)) + ' Year in service.' as years_in_service"),
                    'b.name as department',
                    'c.name as division',
                    'd.name as position',
                    'e.name as employment_type',
                    'f.name as salary_grade',
                    'g.name as salary_step',
                    'a.salary',
                    'ff.name as new_salary_grade',
                    'gg.name as new_salary_step',
                    's.new_salary',
                    's.current_salary_step_id as salary_step_id',
                    's.is_forwarded',
                    's.is_approved',
                    's.is_disapproved'
                )
                ->where([
                    's.month_id' => $month_id,
                    's.year_id' => $year_id,
                    'a.active' => true,
                    'a.is_employee' => true,
                ])
                ->orderBy('name')
                ->distinct()
                ->get();

            return $this->successResponse([
                'salary_steps' => $salary_steps,
                'employees' => $employees,
                'step_increment_data' => $step_increment_data
            ], 'Employee step increment process data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employee step increment process data: ' . $e->getMessage());
        }
    }

    function approved($month_id, $year_id)
    {
        try {
            DB::table('step_increments')->where([
                'month_id' => $month_id,
                'year_id' => $year_id
            ])->update([
                'is_approved' => true,
                'approved_by_id' => auth()->user()->id,
                'approved_date' => now(),
            ]);

            return $this->successResponse(['month_id' => $month_id, 'year_id' => $year_id], 'Step increment approved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to approve step increment: ' . $e->getMessage());
        }
    }

    function disapproved($month_id, $year_id, $remarks)
    {
        try {
            DB::table('step_increments')->where([
                'month_id' => $month_id,
                'year_id' => $year_id
            ])->update([
                'is_disapproved' => true,
                'approved_by_id' => auth()->user()->id,
                'approved_date' => now(),
                'cancelled_remarks' => $remarks,
            ]);

            return $this->successResponse(['month_id' => $month_id, 'year_id' => $year_id, 'remarks' => $remarks], 'Step increment disapproved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to disapprove step increment: ' . $e->getMessage());
        }
    }

    function print($month_id, $year_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $step_increment_data = DB::table('step_increments as a')
                ->join('months as b', 'b.id', '=', 'a.month_id')
                ->select(
                    'a.*',
                    'b.name as month'
                )
                ->where([
                    'a.month_id' => $month_id,
                    'a.year_id' => $year_id,
                ])
                ->get();

            $employees = DB::table('step_increments as s')
                ->join('employees as a', 's.employee_id', '=', 'a.id')
                ->leftJoin('departments as b', 'a.department_id', '=', 'b.id')
                ->leftJoin('divisions as c', 'a.division_id', '=', 'c.id')
                ->leftJoin('positions as d', 'a.position_id', '=', 'd.id')
                ->leftJoin('employment_types as e', 'a.employment_type_id', '=', 'e.id')
                ->leftJoin('salary_grades as f', 's.current_salary_grade_id', '=', 'f.id')
                ->leftJoin('salary_steps as g', 's.current_salary_step_id', '=', 'g.id')
                ->leftJoin('salary_grades as ff', 's.new_salary_grade_id', '=', 'ff.id')
                ->leftJoin('salary_steps as gg', 's.new_salary_step_id', '=', 'gg.id')
                ->select(
                    's.id as step_increment_id',
                    'a.id as employee_id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    DB::raw("CONVERT(Nvarchar(20),(DATEDIFF(D,a.date_hired,'$year_id-$month_id-01')/365)) + ' Year in service.' as years_in_service"),
                    'b.name as department',
                    'c.name as division',
                    'd.name as position',
                    'e.name as employment_type',
                    'f.name as salary_grade',
                    'g.name as salary_step',
                    'a.salary',
                    'ff.name as new_salary_grade',
                    'gg.name as new_salary_step',
                    's.new_salary',
                    's.current_salary_step_id as salary_step_id',
                    's.is_forwarded',
                    's.is_approved',
                    's.is_disapproved'
                )
                ->where([
                    's.month_id' => $month_id,
                    's.year_id' => $year_id,
                    'a.active' => true,
                    'a.is_employee' => true,
                ])
                ->orderBy('name')
                ->distinct()
                ->get();

            $pdf = PDF::loadView(
                'step_increments.employee_step_increment_print',
                compact('employees', 'step_increment_data')
            )->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('legal', 'landscape');
            $pdf->output();

            $pdfContent = $pdf->output();
            $base64Content = base64_encode($pdfContent);

            return $this->successResponse([
                'pdf_content' => $base64Content,
                'filename' => "step_increment_{$month_id}_{$year_id}_" . date('Y-m-d') . ".pdf",
                'content_type' => 'application/pdf',
                'file_size' => strlen($pdfContent),
                'employees' => $employees,
                'step_increment_data' => $step_increment_data
            ], 'Step increment PDF generated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate step increment PDF: ' . $e->getMessage());
        }
    }

    public function delete($step_increment_id)
    {
        try {
            DB::table('step_increments')->where('id', $step_increment_id)->delete();

            return $this->successResponse(['step_increment_id' => $step_increment_id], 'Step increment deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete step increment: ' . $e->getMessage());
        }
    }
}
