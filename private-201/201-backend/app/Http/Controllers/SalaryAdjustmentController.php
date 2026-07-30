<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class SalaryAdjustmentController extends Controller
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
            $salary_schedules = DB::table('salary_schedules')->where('active', true)->orderby('name', 'asc')->get();

            // Get the most recent processed date across all schedules
            $lastProcessed = DB::table('salary_adjustments')
                ->orderBy('updated_at', 'desc')
                ->first();

            $response = [
                'schedules' => $salary_schedules,
                'last_processed_date' => $lastProcessed ? $lastProcessed->updated_at : null
            ];

            return $this->successResponse($response, 'Salary schedules retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary schedules: ' . $e->getMessage());
        }
    }

    public function process(Request $request)
    {
        try {
            // This endpoint can touch many employees; avoid PHP's default 30s timeout.
            // (Timeout here was surfacing to the frontend as Axios "Network Error".)
            ini_set('max_execution_time', '300');
            set_time_limit(300);

            $validator = validator($request->all(), [
                'salary_schedule_id' => 'required|exists:salary_schedules,id'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Get employees eligible for salary adjustment
            // Criteria:
            // 1. active = true (employee is active)
            // 2. is_employee = true (is an employee)
            // 3. is_plantilla = true (is on plantilla)
            // 4. is_hold = false/null/0 (payroll is not on hold)
            $employees = DB::table('employees')
                ->select(
                    'id',
                    'employment_type_id',
                    'salary_grade_id',
                    'salary_step_id',
                    'salary'
                )
                ->where([
                    'active' => true,
                    'is_employee' => true,
                    'is_plantilla' => true
                ])
                ->where(function ($query) {
                    $query->where('is_hold', false)
                          ->orWhereNull('is_hold')
                          ->orWhere('is_hold', 0);
                })
                ->get();

            $processed_count = 0;
            $errors = [];

            foreach ($employees as $emp) {
                // get employee salary and basic pay.
                $employee_id = $emp->id;

                $salary_schedules = DB::table('salary_schedules as a')
                    ->join('salary_schedules_details as b', 'a.id', '=', 'b.salary_schedule_id')
                    ->select(
                        'a.id',
                        'a.effectivity',
                        'b.salary_grade_id',
                        'b.salary_step_id',
                        'b.amount'
                    )
                    ->where([
                        'a.id' => $request->salary_schedule_id,
                        'b.salary_grade_id' => $emp->salary_grade_id,
                        'b.salary_step_id' => $emp->salary_step_id
                    ])
                    ->get();

                if ($salary_schedules->isEmpty()) {
                    $errors[] = 'No salary schedule detail found for employee_id ' . $employee_id .
                        ' (salary_grade_id: ' . $emp->salary_grade_id .
                        ', salary_step_id: ' . $emp->salary_step_id . ').';
                    continue;
                }

                $salary = $salary_schedules[0]->amount;
                if ($salary === null || (float) $salary == 0) {
                    $errors[] = 'Salary schedule detail amount is 0/NULL for employee_id ' . $employee_id .
                        ' (salary_grade_id: ' . $emp->salary_grade_id .
                        ', salary_step_id: ' . $emp->salary_step_id . ').';
                    continue;
                }

                $release_year = date('Y', strtotime($salary_schedules[0]->effectivity));

                if ($salary != 0 || $salary != null) {
                    // re-compute employee contribution.
                    // get pagibig amount
                    $pagibig_amount = 100;

                    // get philhealth amount
                    $ph_data = DB::table('philhealths')->where('year', '<=', $release_year)->orderBy('year', 'desc')->get();

                    if ($ph_data->isEmpty()) { // if empty get topset-up.
                        $ph_data = DB::table('philhealths')->orderBy('year', 'desc')->get();
                    }

                    if ($ph_data->isEmpty()) {
                        $errors[] = 'Please Setup Philhealth Multipliers to continue processing salary adjustments.';
                        continue;
                    }

                    $ph_multiplier = $ph_data[0]->multiplier;
                    $ph_income_ceilling = $ph_data[0]->income_ceiling;
                    $ph_income_floor = $ph_data[0]->income_floor;
                    $ph_fix_rate = $ph_data[0]->fix_rate;

                    if ($salary >= $ph_income_ceilling) {
                        $philhealth_amount = $ph_fix_rate;
                    } elseif ($salary <= $ph_income_floor) {
                        $philhealth_amount = 0;
                    } else {
                        $philhealth_amount = (($salary * $ph_multiplier));
                    }

                    // get gsis amount
                    $gsis_data = DB::table('gsis')->where('year', '<=', $release_year)->orderBy('year', 'desc')->get();

                    if ($gsis_data->isEmpty()) { // if empty get topset-up.
                        $gsis_data = DB::table('gsis')->orderBy('year', 'desc')->get();
                    }

                    if ($gsis_data->isEmpty()) {
                        $errors[] = 'Please Setup GSIS Multipliers to continue processing salary adjustments.';
                        continue;
                    }

                    $gsis_amount = ($salary * $gsis_data[0]->multiplier);

                    // get sss amount
                    $sss_amount = 0;

                    // get tax amount - Monthly calculation
                    // Formula: tax = percentage * (basic_salary - min_amount) + base_tax
                    // Use basic salary directly, not taxable amount after deductions
                    $tax_data = DB::table('tax_tables')->orderBy('min_amount')->get();
                    $arr_len = DB::table('tax_tables')->count('id');

                    $tax_amount = 0;

                    // Apply monthly tax table to basic salary
                    for ($i = 0; $i < $arr_len; $i++) {
                        if ($salary >= $tax_data[$i]->min_amount && $salary <= $tax_data[$i]->max_amount) {
                            $tax_amount = ((($salary - $tax_data[$i]->min_amount) * $tax_data[$i]->percentage) + $tax_data[$i]->base_tax);
                            break; // Stop after finding the correct bracket
                        }
                    }

                    // insert salary adjustment table

                    $salary_adjustment = DB::table('salary_adjustments')->where([
                        'employee_id' => $employee_id,
                        'salary_schedule_id' => $request->salary_schedule_id
                    ])
                        ->get();

                    if (count($salary_adjustment) == 0) {

                        $data_salary_adjustment = array(
                            'employee_id' => $employee_id,
                            'old_salary' => $salary == null ? 0 : $emp->salary,
                            'old_salary_grade_id' => $emp->salary_grade_id == null ? 0 : $emp->salary_grade_id,
                            'old_salary_step_id' => $emp->salary_step_id == null ? 0 : $emp->salary_step_id,
                            'new_salary' => $salary_schedules[0]->amount,
                            'new_salary_grade_id' => $salary_schedules[0]->salary_grade_id,
                            'new_salary_step_id' => $salary_schedules[0]->salary_step_id,
                            'salary_schedule_id' => $salary_schedules[0]->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        );
                    } else {
                        $data_salary_adjustment = array(
                            'employee_id' => $employee_id,
                            'new_salary' => $salary_schedules[0]->amount,
                            'new_salary_grade_id' => $salary_schedules[0]->salary_grade_id,
                            'new_salary_step_id' => $salary_schedules[0]->salary_step_id,
                            'salary_schedule_id' => $salary_schedules[0]->id,
                            'updated_at' => now(), // Update the timestamp when reprocessing
                        );
                    }

                    DB::table('salary_adjustments')->updateOrInsert(['employee_id' => $employee_id, 'salary_schedule_id' => $salary_schedules[0]->id], $data_salary_adjustment);

                    $data = array(
                        'salary' => $salary,
                        'gsis_amount' => $gsis_amount,
                        'sss_amount' => $sss_amount,
                        'pagibig_amount' => $pagibig_amount,
                        'philhealth_amount' => $philhealth_amount,
                        'tax_amount' => $tax_amount
                    );

                    DB::table('employees')->where('id', $employee_id)->update($data);
                    $processed_count++;
                }
            }

            // Save Audit Trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Salary Adjustment',
                'activity' => 'Process',
                'description' => 'Process Salary Adjustment informations.',
            );

            Audit::create($data_audit);

            $response_data = [
                'processed_count' => $processed_count,
                'total_employees' => $employees->count(),
                'errors' => $errors
            ];

            return $this->successResponse($response_data, 'Salary adjustment processed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process salary adjustment: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $salary_adjustment = DB::table('salary_adjustments')
                ->join('employees', 'employees.id', '=', 'salary_adjustments.employee_id')
                ->join('salary_schedules', 'salary_schedules.id', '=', 'salary_adjustments.salary_schedule_id')
                ->select('salary_adjustments.*', 'employees.first_name', 'employees.last_name', 'salary_schedules.name as schedule_name')
                ->where('salary_adjustments.id', $id)
                ->first();

            if (!$salary_adjustment) {
                return $this->notFoundResponse('Salary adjustment not found');
            }

            return $this->successResponse($salary_adjustment, 'Salary adjustment retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary adjustment: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $salary_schedules = DB::table('salary_schedules')->where('active', true)->orderby('name', 'asc')->get();
            $employees = DB::table('employees')
                ->where(['active' => true, 'is_employee' => true, 'is_plantilla' => true])
                ->orderby('last_name', 'asc')
                ->get();

            return $this->successResponse([
                'salary_schedules' => $salary_schedules,
                'employees' => $employees
            ], 'Create salary adjustment form data');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get create form: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $salary_adjustment = DB::table('salary_adjustments')
                ->join('employees', 'employees.id', '=', 'salary_adjustments.employee_id')
                ->join('salary_schedules', 'salary_schedules.id', '=', 'salary_adjustments.salary_schedule_id')
                ->select('salary_adjustments.*', 'employees.first_name', 'employees.last_name', 'salary_schedules.name as schedule_name')
                ->where('salary_adjustments.id', $id)
                ->first();

            if (!$salary_adjustment) {
                return $this->notFoundResponse('Salary adjustment not found');
            }

            $salary_schedules = DB::table('salary_schedules')->where('active', true)->orderby('name', 'asc')->get();

            return $this->successResponse([
                'salary_adjustment' => $salary_adjustment,
                'salary_schedules' => $salary_schedules
            ], 'Salary adjustment edit data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary adjustment for editing: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'salary_schedule_id' => 'required|exists:salary_schedules,id',
                'new_salary' => 'required|numeric|min:0',
                'new_salary_grade_id' => 'required|exists:salary_grades,id',
                'new_salary_step_id' => 'required|exists:salary_steps,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $salary_adjustment = DB::table('salary_adjustments')->where('id', $id)->first();

            if (!$salary_adjustment) {
                return $this->notFoundResponse('Salary adjustment not found');
            }

            DB::table('salary_adjustments')->where('id', $id)->update([
                'employee_id' => $request->employee_id,
                'salary_schedule_id' => $request->salary_schedule_id,
                'new_salary' => $request->new_salary,
                'new_salary_grade_id' => $request->new_salary_grade_id,
                'new_salary_step_id' => $request->new_salary_step_id,
            ]);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Salary Adjustment',
                'activity' => 'Update',
                'description' => 'Updated salary adjustment information.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Salary adjustment updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update salary adjustment: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $salary_adjustment = DB::table('salary_adjustments')->where('id', $id)->first();

            if (!$salary_adjustment) {
                return $this->notFoundResponse('Salary adjustment not found');
            }

            DB::table('salary_adjustments')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Salary Adjustment',
                'activity' => 'Delete',
                'description' => 'Deleted salary adjustment information.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Salary adjustment deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete salary adjustment: ' . $e->getMessage());
        }
    }

    /**
     * Get affected employees for a salary schedule
     */
    public function getAffectedEmployees($salary_schedule_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Get all employees that would be affected by this salary schedule
            // Criteria:
            // 1. active = true (employee is active)
            // 2. is_employee = true (is an employee)
            // 3. is_plantilla = true (is on plantilla)
            // 4. is_hold = false/null/0 (payroll is not on hold)
            // 5. Must have matching salary schedule detail (f.amount is not null)
            $employees = DB::table('employees as a')
                ->leftJoin('departments as b', 'a.department_id', '=', 'b.id')
                ->leftJoin('positions as c', 'a.position_id', '=', 'c.id')
                ->leftJoin('salary_grades as d', 'a.salary_grade_id', '=', 'd.id')
                ->leftJoin('salary_steps as e', 'a.salary_step_id', '=', 'e.id')
                ->leftJoin('salary_schedules_details as f', function ($join) use ($salary_schedule_id) {
                    $join->on('f.salary_schedule_id', '=', DB::raw($salary_schedule_id))
                         ->on('f.salary_grade_id', '=', 'a.salary_grade_id')
                         ->on('f.salary_step_id', '=', 'a.salary_step_id');
                })
                ->select(
                    'a.id as employee_id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as department',
                    'c.name as position',
                    'd.name as salary_grade',
                    'e.name as salary_step',
                    'a.salary as current_salary',
                    'f.amount as new_salary'
                )
                ->where([
                    'a.active' => true,
                    'a.is_employee' => true,
                    'a.is_plantilla' => true
                ])
                ->where(function ($query) {
                    $query->where('a.is_hold', false)
                          ->orWhereNull('a.is_hold')
                          ->orWhere('a.is_hold', 0);
                })
                ->whereNotNull('f.amount')
                ->orderBy('name')
                ->get();

            // Get last processed date and total count from salary_adjustments
            // Use updated_at to get the most recent processing date (since updateOrInsert updates updated_at)
            $lastProcessed = DB::table('salary_adjustments')
                ->where('salary_schedule_id', $salary_schedule_id)
                ->orderBy('updated_at', 'desc')
                ->first();

            $totalProcessed = DB::table('salary_adjustments')
                ->where('salary_schedule_id', $salary_schedule_id)
                ->distinct()
                ->count('employee_id');

            return $this->successResponse([
                'employees' => $employees,
                'total_employees' => $employees->count(),
                'last_processed_date' => $lastProcessed ? $lastProcessed->updated_at : null,
                'total_processed' => $totalProcessed
            ], 'Affected employees retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve affected employees: ' . $e->getMessage());
        }
    }

    /**
     * Get already processed employees for a given salary schedule.
     * This is derived from `salary_adjustments` records.
     */
    public function getProcessedEmployees($salary_schedule_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('salary_adjustments as sa')
                ->join('employees as a', 'a.id', '=', 'sa.employee_id')
                ->leftJoin('departments as b', 'a.department_id', '=', 'b.id')
                ->leftJoin('positions as c', 'a.position_id', '=', 'c.id')
                ->leftJoin('salary_grades as d', 'a.salary_grade_id', '=', 'd.id')
                ->leftJoin('salary_steps as e', 'a.salary_step_id', '=', 'e.id')
                ->leftJoin('salary_schedules as ss', 'ss.id', '=', 'sa.salary_schedule_id')
                ->where('sa.salary_schedule_id', (int) $salary_schedule_id)
                ->select(
                    'sa.employee_id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as department',
                    'c.name as position',
                    'd.name as salary_grade',
                    'e.name as salary_step',
                    'sa.old_salary',
                    'sa.new_salary',
                    'ss.name as schedule_name',
                    'sa.updated_at'
                )
                ->orderBy('sa.updated_at', 'desc')
                ->get();

            return $this->successResponse([
                'employees' => $employees,
                'total_processed' => $employees->count(),
            ], 'Processed employees retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve processed employees: ' . $e->getMessage());
        }
    }

    /**
     * Get employees that should have been processed for a schedule but
     * are missing a `salary_adjustments` record.
     *
     * The "reason" is computed from current DB state:
     * - is_hold flag
     * - missing salary schedule detail (grade/step)
     * - missing PhilHealth/GSIS multipliers tables (global)
     */
    public function getUnprocessedEmployees($salary_schedule_id)
    {
        try {
            // This endpoint may also iterate many employees.
            ini_set('max_execution_time', '300');
            set_time_limit(300);

            $app_key = env("APP_KEY", "");

            $salarySchedule = DB::table('salary_schedules')
                ->select('id', 'effectivity')
                ->where('id', (int) $salary_schedule_id)
                ->first();

            $release_year = ($salarySchedule && !empty($salarySchedule->effectivity))
                ? date('Y', strtotime($salarySchedule->effectivity))
                : date('Y', strtotime(now()));

            $ph_data = DB::table('philhealths')
                ->where('year', '<=', $release_year)
                ->orderBy('year', 'desc')
                ->get();

            if ($ph_data->isEmpty()) {
                $ph_data = DB::table('philhealths')->orderBy('year', 'desc')->get();
            }

            $gsis_data = DB::table('gsis')
                ->where('year', '<=', $release_year)
                ->orderBy('year', 'desc')
                ->get();

            if ($gsis_data->isEmpty()) {
                $gsis_data = DB::table('gsis')->orderBy('year', 'desc')->get();
            }

            $missing_ph = $ph_data->isEmpty();
            $missing_gsis = $gsis_data->isEmpty();

            // Base set: matches `process()` employee selection (no is_hold filter there),
            // but we also left-join salary schedule detail for grade/step matching.
            $employees = DB::table('employees as a')
                ->leftJoin('departments as b', 'a.department_id', '=', 'b.id')
                ->leftJoin('positions as c', 'a.position_id', '=', 'c.id')
                ->leftJoin('salary_grades as d', 'a.salary_grade_id', '=', 'd.id')
                ->leftJoin('salary_steps as e', 'a.salary_step_id', '=', 'e.id')
                ->leftJoin('salary_schedules_details as f', function ($join) use ($salary_schedule_id) {
                    $join->on('f.salary_schedule_id', '=', DB::raw($salary_schedule_id))
                         ->on('f.salary_grade_id', '=', 'a.salary_grade_id')
                         ->on('f.salary_step_id', '=', 'a.salary_step_id');
                })
                ->select(
                    'a.id as employee_id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as department',
                    'c.name as position',
                    'd.name as salary_grade',
                    'e.name as salary_step',
                    'a.salary as current_salary',
                    'f.amount as new_salary',
                    'a.is_hold as is_hold'
                )
                ->where([
                    'a.active' => true,
                    'a.is_employee' => true,
                    'a.is_plantilla' => true
                ])
                ->get();

            // Avoid N+1 queries: build a quick lookup of already-processed employee_ids.
            $processedEmployeeIds = DB::table('salary_adjustments')
                ->where('salary_schedule_id', (int) $salary_schedule_id)
                ->pluck('employee_id')
                ->flip()
                ->toArray();

            $unprocessed = [];

            foreach ($employees as $emp) {
                if (isset($processedEmployeeIds[$emp->employee_id])) {
                    continue;
                }

                $reasons = [];

                // Payroll hold check: the criteria for "affected employees" uses this,
                // but `process()` currently doesn't, so we report it for guidance.
                if ($emp->is_hold === 1 || $emp->is_hold === true) {
                    $reasons[] = 'Employee payroll is on hold. Clear `is_hold` to allow salary adjustment processing.';
                }

                if ($emp->new_salary === null) {
                    $reasons[] = 'No salary schedule detail for the employee salary grade/step. Please configure `salary_schedules_details` for this grade and step.';
                }

                if ($missing_ph) {
                    $reasons[] = 'Missing PhilHealth multipliers. Please set up PhilHealth multipliers to continue processing.';
                }

                if ($missing_gsis) {
                    $reasons[] = 'Missing GSIS multipliers. Please set up GSIS multipliers to continue processing.';
                }

                if (empty($reasons)) {
                    $reasons[] = 'Salary adjustment record was not created for this employee. Please re-run processing or check processing logs.';
                }

                $unprocessed[] = [
                    'employee_id' => (int) $emp->employee_id,
                    'employee_no' => $emp->employee_no,
                    'name' => $emp->name,
                    'department' => $emp->department,
                    'position' => $emp->position,
                    'salary_grade' => $emp->salary_grade,
                    'salary_step' => $emp->salary_step,
                    'current_salary' => $emp->current_salary,
                    'new_salary' => $emp->new_salary,
                    'reason' => implode(' ', $reasons),
                ];
            }

            return $this->successResponse([
                'employees' => $unprocessed,
                'total_unprocessed' => count($unprocessed),
            ], 'Unprocessed employees retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve unprocessed employees: ' . $e->getMessage());
        }
    }
}
