<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeStepIncrementController extends Controller
{
    use ApiResponse;

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
                        DB::raw("CAST(1 AS BIT) as [select]"), // Changed to 1 (true) - these employees are already in this step increment
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
                        's.current_salary_grade_id as salary_grade_id',
                        'f.name as salary_grade',
                        's.current_salary_step_id as salary_step_id',
                        'g.name as salary_step',
                        'a.salary',
                        's.new_salary_grade_id',
                        'ff.name as new_salary_grade',
                        's.new_salary_step_id',
                        'gg.name as new_salary_step',
                        's.new_salary'
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
                    ->leftJoin('plantillas as p', 'a.plantilla_id', '=', 'p.id')
                    ->leftJoin('salary_grades as f', 'a.salary_grade_id', '=', 'f.id')
                    ->leftJoin('salary_steps as g', 'a.salary_step_id', '=', 'g.id')
                    ->leftJoin('salary_steps as g_next', function ($join) {
                        $join->on('g_next.id', '=', DB::raw('isnull(a.salary_step_id,0) + 1'));
                    })
                    ->leftJoin('salary_schedules_details as scd_next', function ($join) {
                        $join->on('a.salary_grade_id', '=', 'scd_next.salary_grade_id')
                            ->on('scd_next.salary_step_id', '=', DB::raw('isnull(a.salary_step_id,0) + 1'));
                    })
                    ->leftJoin('salary_schedules as sch', 'sch.id', '=', 'scd_next.salary_schedule_id')
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
                        'a.salary_grade_id as salary_grade_id',
                        'f.name as salary_grade',
                        'a.salary_step_id',
                        'g.name as salary_step',
                        'a.salary',
                        'a.salary_grade_id as new_salary_grade_id',
                        'f.name as new_salary_grade',
                        DB::raw("(a.salary_step_id + 1) as new_salary_step_id"),
                        'g_next.name as new_salary_step',
                        'scd_next.amount as new_salary'
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

            // Note: Do not delete the whole period. We upsert per employee to preserve
            // previously saved rows for the same period.

            for ($i = 0; $i < count($data['step_increment_id']); $i++) {
                if (in_array($data['employee_id'][$i], $data['select'],)) {

                    $employee = DB::table('employees')->where('id', $data['employee_id'][$i])->get();

                    if ($employee->isEmpty()) {
                        return $this->errorResponse("Employee not found (ID: {$data['employee_id'][$i]}).");
                    }

                    $plantilla_id = $employee[0]->plantilla_id;
                    $current_step_id = (int) ($employee[0]->salary_step_id ?? 0);
                    $current_salary = $employee[0]->salary;
                    $current_grade_id = $employee[0]->salary_grade_id;
                    $requested_grade_id = $data['new_salary_grade_id'][$i] ?? null;
                    $requested_step_id = $data['new_salary_step_id'][$i] ?? null;
                    $requested_new_salary = $data['new_salary'][$i] ?? null;

                    // Determine target salary step: always move to the next step (current + 1)
                    $new_salary_step = max(1, $current_step_id + 1);

                    // Get plantilla to get the salary grade
                    $plantilla_info = DB::table('plantillas')->where('id', $plantilla_id)->first();

                    if (!$plantilla_info) {
                        return $this->errorResponse("Plantilla not found for employee ID: {$data['employee_id'][$i]}");
                    }

                    $salary_grade_id = $plantilla_info->salary_grade_id;
                    $current_grade_id = $salary_grade_id ?? $current_grade_id;
                    $grade_for_lookup = $requested_grade_id ?: $salary_grade_id;
                    $step_for_lookup = $requested_step_id ?: $new_salary_step;

                    // Query salary schedule details directly with explicit grade and step
                    // Order by effectivity date descending to get the most recent active schedule
                    $salary_detail = DB::table('salary_schedules_details as scd')
                        ->join('salary_schedules as sch', 'sch.id', '=', 'scd.salary_schedule_id')
                        ->select(
                            'scd.amount',
                            'scd.salary_grade_id',
                            'scd.salary_step_id',
                            'sch.id as schedule_id',
                            'sch.name as schedule_name'
                        )
                        ->where([
                            'scd.salary_grade_id' => $grade_for_lookup,
                            'scd.salary_step_id' => $step_for_lookup,
                            'sch.active' => 1
                        ])
                        ->orderBy('sch.effectivity', 'desc')
                        ->first();

                    if (!$salary_detail) {
                        $employee_name = $employee[0]->first_name . ' ' . $employee[0]->last_name;
                        return $this->errorResponse(
                            "Salary schedule not found for employee {$employee_name} (ID: {$data['employee_id'][$i]}). " .
                                "Grade {$grade_for_lookup}, Step {$step_for_lookup} is not configured in any active salary schedule. " .
                                "Please check the salary schedule setup."
                        );
                    }

                    if ($requested_new_salary !== null && bccomp((string) $requested_new_salary, (string) $salary_detail->amount, 2) !== 0) {
                        $employee_name = $employee[0]->first_name . ' ' . $employee[0]->last_name;
                        return $this->errorResponse(
                            "New salary mismatch for employee {$employee_name} (ID: {$data['employee_id'][$i]}). " .
                                "Submitted amount does not match the configured salary schedule for Grade {$grade_for_lookup}, Step {$step_for_lookup}.",
                            422
                        );
                    }

                    // ALWAYS use absolute salary from salary schedule for the new step
                    // Step increments must follow the salary schedule setup - no manual overrides
                    $amount = $salary_detail->amount;

                    // Validation: Step increment must result in salary increase
                    // If salary schedule amount is less than or equal to current salary, reject it
                    if ($amount <= $current_salary) {
                        $employee_name = $employee[0]->first_name . ' ' . $employee[0]->last_name;
                        return $this->errorResponse(
                            "Step increment validation failed for employee {$employee_name} (ID: {$data['employee_id'][$i]}). " .
                                "The salary schedule amount (₱" . number_format($amount, 2) . ") for Step {$new_salary_step} " .
                                "is not higher than the current salary (₱" . number_format($current_salary, 2) . "). " .
                                "Please check the salary schedule setup or select a higher step."
                        );
                    }

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
                        $gsis_amount = ($amount * (float) $gsis_data[0]->multiplier);
                    } else {
                        // Default GSIS multiplier when table is empty
                        $gsis_amount = 0;
                    }

                    // get sss amount
                    $sss_amount = 0;

                    // get tax amount - Monthly calculation
                    // Formula: tax = percentage * (basic_salary - min_amount) + base_tax
                    // Use basic salary directly, not taxable amount after deductions
                    $tax_data = DB::table('tax_tables')->orderBy('min_amount')->get();

                    if ($tax_data->isNotEmpty()) {
                        $arr_len = DB::table('tax_tables')->count('id');
                        $tax_amount = 0;

                        for ($ii = 0; $ii < $arr_len; $ii++) {
                            if ($amount >= $tax_data[$ii]->min_amount && $amount <= $tax_data[$ii]->max_amount) {
                                $tax_amount = ((($amount - $tax_data[$ii]->min_amount) * $tax_data[$ii]->percentage) + $tax_data[$ii]->base_tax);
                                break;
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
                            'new_salary_grade_id' => $grade_for_lookup,
                            'new_salary_step_id' => $step_for_lookup,
                            'new_salary' => $amount,
                            'new_tax_amount' => $new_tax_amount,
                            'new_gsis_amount' => $new_gsis_amount,
                            'new_sss_amount' => $new_sss_amount,
                            'new_pagibig_amount' => $new_pagibig_amount,
                            'new_philhealth_amount' => $new_philhealth_amount
                        );

                        DB::table('step_increments')->where([
                            'month_id' => $month_id,
                            'year_id' => $year_id,
                            'employee_id' => $data['employee_id'][$i],
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
                            'new_salary_grade_id' => $grade_for_lookup,
                            'new_salary_step_id' => $step_for_lookup,
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

    public function changeNewSalary(Request $request, $step_increment_id, $employee_id)
    {
        try {
            $employee = DB::table('employees')->where('id', $employee_id)->first();

            if (!$employee) {
                return $this->errorResponse('Employee not found');
            }

            $plantilla_id = $employee->plantilla_id;

            if (!$plantilla_id) {
                return $this->errorResponse('Employee does not have a plantilla assigned');
            }

            // Get plantilla details to know the salary grade
            $plantilla_info = DB::table('plantillas')->where('id', $plantilla_id)->first();

            if (!$plantilla_info) {
                return $this->errorResponse('Plantilla not found');
            }

            $default_grade_id = $plantilla_info->salary_grade_id;
            $current_step = (int) ($employee->salary_step_id ?? 0);
            $default_step_id = max(1, $current_step + 1);

            $step_increment = null;
            if ($step_increment_id && $step_increment_id !== 'null') {
                $step_increment = DB::table('step_increments')->where('id', $step_increment_id)->first();
            }

            $grade_for_lookup = $request->input('new_salary_grade_id')
                ?? $request->input('salary_grade_id')
                ?? ($step_increment->new_salary_grade_id ?? null)
                ?? $default_grade_id;

            $step_for_lookup = $request->input('new_salary_step_id')
                ?? $request->input('salary_step_id')
                ?? ($step_increment->new_salary_step_id ?? null)
                ?? $default_step_id;

            // Query salary schedule details directly with explicit grade and step
            // Order by effectivity date descending to get the most recent active schedule
            $salary_detail = DB::table('salary_schedules_details as scd')
                ->join('salary_schedules as sch', 'sch.id', '=', 'scd.salary_schedule_id')
                ->select(
                    'scd.amount',
                    'scd.salary_grade_id',
                    'scd.salary_step_id',
                    'sch.id as schedule_id',
                    'sch.name as schedule_name',
                    'sch.effectivity'
                )
                ->where([
                    'scd.salary_grade_id' => $grade_for_lookup,
                    'scd.salary_step_id' => $step_for_lookup,
                    'sch.active' => 1
                ])
                ->orderBy('sch.effectivity', 'desc')
                ->first();

            if (!$salary_detail) {
                return $this->errorResponse(
                    "Salary schedule not found for Grade {$grade_for_lookup}, Step {$step_for_lookup}. " .
                        "Please ensure the salary schedule is set up correctly with this grade and step combination."
                );
            }

            $amount = $salary_detail->amount;

            return $this->successResponse([
                'salary' => $amount,
                'salary_grade_id' => $salary_detail->salary_grade_id,
                'salary_step_id' => $salary_detail->salary_step_id,
                'schedule_name' => $salary_detail->schedule_name
            ], 'Salary calculated successfully');
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
                ->leftJoin('plantillas as p', 'a.plantilla_id', '=', 'p.id')
                ->leftJoin('salary_grades as f', 'a.salary_grade_id', '=', 'f.id')
                ->leftJoin('salary_steps as g', 'a.salary_step_id', '=', 'g.id')
                ->leftJoin('salary_steps as g_next', function ($join) {
                    $join->on('g_next.id', '=', DB::raw('isnull(a.salary_step_id,0) + 1'));
                })
                ->leftJoin('salary_schedules_details as scd_next', function ($join) {
                    $join->on('a.salary_grade_id', '=', 'scd_next.salary_grade_id')
                        ->on('scd_next.salary_step_id', '=', DB::raw('isnull(a.salary_step_id,0) + 1'));
                })
                ->leftJoin('salary_schedules as sch', 'sch.id', '=', 'scd_next.salary_schedule_id')
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
                    'a.salary_grade_id as salary_grade_id',
                    'f.name as salary_grade',
                    'a.salary_step_id',
                    'g.name as salary_step',
                    'a.salary',
                    'a.salary_grade_id as new_salary_grade_id',
                    'f.name as new_salary_grade',
                    DB::raw("(a.salary_step_id + 1) as new_salary_step_id"),
                    'g_next.name as new_salary_step',
                    'scd_next.amount as new_salary'
                )
                ->whereRaw("(DATEDIFF(D,a.date_hired,'$year_id-$month_id-01')/365) >= 3")
                ->where([
                    'a.active' => true,
                    'a.is_employee' => true,
                    'sch.active' => true

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
                    's.current_salary_grade_id as salary_grade_id',
                    'f.name as salary_grade',
                    's.current_salary_step_id as salary_step_id',
                    'g.name as salary_step',
                    'a.salary',
                    's.new_salary_grade_id as new_salary_grade_id',
                    'ff.name as new_salary_grade',
                    's.new_salary_step_id as new_salary_step_id',
                    'gg.name as new_salary_step',
                    's.new_salary'
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
            $app_key = env("APP_KEY", "");

            // Return employee-level items that are forwarded and still pending
            $employees = DB::table('step_increments as s')
                ->join('employees as a', 's.employee_id', '=', 'a.id')
                ->leftJoin('months as m', 's.month_id', '=', 'm.id')
                ->leftJoin('salary_grades as f', 's.current_salary_grade_id', '=', 'f.id')
                ->leftJoin('salary_steps as g', 's.current_salary_step_id', '=', 'g.id')
                ->leftJoin('salary_grades as ff', 's.new_salary_grade_id', '=', 'ff.id')
                ->leftJoin('salary_steps as gg', 's.new_salary_step_id', '=', 'gg.id')
                ->select(
                    's.id',
                    'a.id as employee_id',
                    'a.photo',
                    's.effectivity_date',
                    's.forwarded_date',
                    's.is_forwarded',
                    DB::raw('ISNULL(s.is_approved, 0) as is_approved'),
                    DB::raw('ISNULL(s.is_disapproved, 0) as is_disapproved'),
                    's.month_id',
                    's.year_id',
                    'm.name as month',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            CONCAT(a.first_name,' ',a.last_name)
                         ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                         END as name"),
                    's.current_salary_grade_id',
                    's.current_salary_step_id',
                    'f.name as current_salary_grade',
                    'g.name as current_salary_step',
                    's.current_salary',
                    's.new_salary_grade_id',
                    's.new_salary_step_id',
                    'ff.name as new_salary_grade',
                    'gg.name as new_salary_step',
                    's.new_salary',
                    's.new_tax_amount',
                    's.new_gsis_amount',
                    's.new_sss_amount',
                    's.new_pagibig_amount',
                    's.new_philhealth_amount'
                )
                ->where('s.is_forwarded', true)
                ->whereRaw('ISNULL(s.is_approved, 0) = 0')
                ->whereRaw('ISNULL(s.is_disapproved, 0) = 0')
                ->orderBy('s.forwarded_date', 'desc')
                ->get();

            Log::info('Step Increment Approval - fetched', [
                'count' => $employees->count(),
                'first' => $employees->first()
            ]);

            return $this->successResponse($employees, 'Step increments for approval retrieved successfully');
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
