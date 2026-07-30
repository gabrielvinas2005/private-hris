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

            return $this->successResponse($salary_schedules, 'Salary schedules retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary schedules: ' . $e->getMessage());
        }
    }

    public function process(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'salary_schedule_id' => 'required|exists:salary_schedules,id'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

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
                    $salary = 0;
                    $release_year  = date('Y', strtotime(now()));
                } else {
                    $salary = $salary_schedules[0]->amount;
                    $release_year = date('Y', strtotime($salary_schedules[0]->effectivity));
                }

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
                        $philhealth_amount = (($salary * $ph_multiplier) / 2);
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

                    // get tax amount
                    $tax_data = DB::table('tax_tables')->get();
                    $arr_len = DB::table('tax_tables')->count('id');

                    $taxable_amount = (($salary) - ($gsis_amount + $philhealth_amount + $pagibig_amount));
                    $tax_amount = 0;

                    for ($i = 0; $i < $arr_len; $i++) {
                        if ($taxable_amount >= $tax_data[$i]->min_amount && $taxable_amount <= $tax_data[$i]->max_amount) {
                            $tax_amount = ((($taxable_amount - $tax_data[$i]->min_amount) * $tax_data[$i]->percentage) + $tax_data[$i]->base_tax);
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
                        );
                    } else {
                        $data_salary_adjustment = array(
                            'employee_id' => $employee_id,
                            'new_salary' => $salary_schedules[0]->amount,
                            'new_salary_grade_id' => $salary_schedules[0]->salary_grade_id,
                            'new_salary_step_id' => $salary_schedules[0]->salary_step_id,
                            'salary_schedule_id' => $salary_schedules[0]->id,
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
                'description' => 'Process Salary Adjustment information',
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
}
