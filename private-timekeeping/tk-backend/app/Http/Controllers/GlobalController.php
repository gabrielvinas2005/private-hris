<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class GlobalController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getPlantilla($id)
    {

        if ($id != 0) {
            $employees = DB::table('employees')->where('plantilla_id', $id)->get();

            if ($employees->isNotEmpty()) {
                $salary_step = DB::table('step_increments')->where('employee_id', $employees[0]->id)->get();

                if (isset($salary_step)) {
                    $data = DB::table('employees')
                        ->select('salary_grade_id', 'salary_step_id', 'position_id', 'salary as amount')
                        ->where('plantilla_id', $id)
                        ->get();
                } else {
                    $data = DB::table('plantillas')
                        ->join('salary_schedules_details', function ($join) {
                            $join->on('plantillas.salary_grade_id', '=', 'salary_schedules_details.salary_grade_id')
                                ->on('plantillas.salary_step_id', '=', 'salary_schedules_details.salary_step_id');
                        })
                        ->join('salary_schedules', 'salary_schedules.id', '=', 'salary_schedules_details.salary_schedule_id')
                        ->select('plantillas.salary_grade_id', 'plantillas.salary_step_id', 'plantillas.position_id', 'salary_schedules_details.amount')
                        ->where(['plantillas.id' => $id, 'salary_schedules.active' => true])
                        ->get();
                }
            } else {
                $data = DB::table('plantillas')
                    ->leftJoin('salary_schedules_details', function ($join) {
                        $join->on('plantillas.salary_grade_id', '=', 'salary_schedules_details.salary_grade_id')
                            ->on('plantillas.salary_step_id', '=', 'salary_schedules_details.salary_step_id');
                    })
                    ->leftJoin('salary_schedules', 'salary_schedules.id', '=', 'salary_schedules_details.salary_schedule_id')
                    ->select('plantillas.salary_grade_id', 'plantillas.salary_step_id', 'plantillas.position_id', 'salary_schedules_details.amount')
                    ->where(['plantillas.id' => $id, 'salary_schedules.active' => true])
                    ->get();
            }
        } else {
            $data = DB::table('plantillas')
                ->leftJoin('salary_schedules_details', function ($join) {
                    $join->on('plantillas.salary_grade_id', '=', 'salary_schedules_details.salary_grade_id')
                        ->on('plantillas.salary_step_id', '=', 'salary_schedules_details.salary_step_id');
                })
                ->leftJoin('salary_schedules', 'salary_schedules.id', '=', 'salary_schedules_details.salary_schedule_id')
                ->select('plantillas.salary_grade_id', 'plantillas.salary_step_id', 'plantillas.position_id', 'salary_schedules_details.amount')
                ->where(['plantillas.id' => $id, 'salary_schedules.active' => true])
                ->get();
        }

        return json_encode($data);
    }

    public function getSalary($amount)
    {
        // get daily rate
        $daily_rate = ($amount / 22);

        // get hourly rate
        $hourly_rate = (($amount / 22) / 8);

        // get pagibig amount
        $pagibig_amount = 200;

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
                break;
            } else {
                $tax_amount = 0;
            }
        }

        $data = array([
            'daily_rate' => $daily_rate,
            'hourly_rate' => $hourly_rate,
            'pagibig_amount' => $pagibig_amount,
            'philhealth_amount' => $philhealth_amount,
            'gsis_amount' => $gsis_amount,
            'sss_amount' => $sss_amount,
            'tax_amount' => $tax_amount
        ]);

        return json_encode($data);
    }

    public function getAddress($id)
    {
        $data = DB::table('employees')
            ->select(
                'ra_region',
                'ra_province',
                'ra_city',
                'ra_barangay',
                'pa_region',
                'pa_province',
                'pa_city',
                'pa_barangay',
                'indicate_country'
            )
            ->where('id', $id)
            ->get();

        return json_encode($data);
    }

    public function getAddress_temp($request_id)
    {
        $data = DB::table('employees_temps')
            ->select(
                'ra_region',
                'ra_province',
                'ra_city',
                'ra_barangay',
                'pa_region',
                'pa_province',
                'pa_city',
                'pa_barangay',
                'indicate_country'
            )
            ->where('id', $request_id)
            ->get();

        return json_encode($data);
    }

    public function getEmployeePromotion($id)
    {
        $data = DB::table('employees')
            ->select(
                'position_id',
                'plantilla_id',
                'is_plantilla',
                'is_teaching',
                'employment_type_id',
                'department_id',
                'branch_id',
                'payroll_interval_id',
                'salary',
                'tax_amount',
                'gsis_amount',
                'sss_amount',
                'pagibig_amount',
                'philhealth_amount',
            )
            ->where('id', $id)
            ->get();

        return json_encode($data);
    }

    public function getEmployeePlantilla($id)
    {
        // Get Plantilla
        $plantilla_emp = DB::table('plantillas')
            ->join('positions', 'positions.id', '=', 'plantillas.position_id')
            ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
            ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
            ->select('plantillas.*')
            ->where(['plantillas.employee_id' => $id, 'plantillas.active' => true]);

        $data = DB::table('plantillas')
            ->join('positions', 'positions.id', '=', 'plantillas.position_id')
            ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
            ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
            ->select('plantillas.*')
            ->where(['plantillas.employee_id' => 0, 'plantillas.active' => true])->union($plantilla_emp)->get();

        return json_encode($data);
    }

    public function getAssignSchedule($id)
    {
        $app_key = env("APP_KEY", "");

        if (Auth::user()->access_all_branches) {
            $emp_schedule = DB::table('employees')
                ->select(
                    'id',
                    'photo',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                               CONCAT(employees.first_name,' ',substring(employees.middle_name,1,1),'. ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                            END as name")
                )
                ->selectRaw('case when work_schedule_id = 0 then 0 else 1 end as assign')
                ->where(['is_employee' => true, 'active' => true, 'is_shifting' => false, 'work_schedule_id' => $id]);

            $data = DB::table('employees')
                ->select('id', 'photo', DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                               CONCAT(employees.first_name,' ',substring(employees.middle_name,1,1),'. ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                            END as name"))
                ->selectRaw('case when work_schedule_id = 0 then 0 else 1 end as assign')
                ->where(['is_employee' => true, 'active' => true, 'is_shifting' => false, 'work_schedule_id' => 0])
                ->union($emp_schedule)
                ->orderBy('name', 'asc')
                ->get();
        } else {
            $user_branch_id = DB::table('users as a')
                ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('b.branch_id')
                ->where('a.id', Auth::user()->id)
                ->get();

            $emp_schedule = DB::table('employees')
                ->select('id', 'photo', DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                               CONCAT(employees.first_name,' ',substring(employees.middle_name,1,1),'. ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                            END as name"))
                ->selectRaw('case when work_schedule_id = 0 then 0 else 1 end as assign')
                ->where(['is_employee' => true, 'active' => true, 'is_shifting' => false, 'work_schedule_id' => $id, 'branch_id' => $user_branch_id[0]->branch_id]);

            $data = DB::table('employees')
                ->select('id', 'photo', DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                               CONCAT(employees.first_name,' ',substring(employees.middle_name,1,1),'. ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                            END as name"))
                ->selectRaw('case when work_schedule_id = 0 then 0 else 1 end as assign')
                ->where(['is_employee' => true, 'active' => true, 'is_shifting' => false, 'work_schedule_id' => 0, 'branch_id' => $user_branch_id[0]->branch_id])
                ->union($emp_schedule)
                ->orderBy('name', 'asc')
                ->get();
        }

        return json_encode($data);
    }

    public function getHolidays($id, $header_id)
    {
        $data = DB::table('holidays')
            ->where(['holiday_type' => $id])
            ->whereNotIn('id', function ($query) use ($header_id) {
                $query->select('holiday_id')
                    ->from('holiday_tagging_details')
                    ->where('holiday_tagging_id', $header_id);
            })
            ->get();

        return json_encode($data);
    }

    public function getEmployeeStep($id)
    {

        $data = DB::table('employees as a')
            ->join('plantillas as b', 'b.id', '=', 'a.plantilla_id')
            ->join('positions as c', 'c.id', '=', 'b.position_id')
            ->join('departments as d', 'd.id', '=', 'a.department_id')
            ->join('employment_types as e', 'e.id', '=', 'a.employment_type_id')
            ->select(
                'a.id',
                'a.plantilla_id',
                'a.salary_grade_id',
                'a.salary_step_id',
                'a.salary',
                'c.name as position',
                'd.name as department',
                'e.name as employment_type',
                DB::raw("CASE WHEN a.salary_step_id >= (SELECT MAX(id) FROM salary_steps) THEN a.salary_step_id ELSE a.salary_step_id + 1 END as new_step_id")
            )
            ->where('a.id', $id)
            ->get();

        return json_encode($data);
    }

    public function getHolidayTaggingDetails($holiday_type_id, $branch_id, $year)
    {
        $data = DB::table('holiday_tagging_headers')
            ->join('holiday_tagging_details', 'holiday_tagging_details.holiday_tagging_id', '=', 'holiday_tagging_headers.id')
            ->join('holidays', 'holidays.id', '=', 'holiday_tagging_details.holiday_id')
            ->join('holiday_types', 'holiday_types.id', '=', 'holiday_tagging_headers.holiday_type_id')
            ->select('holiday_tagging_details.holiday_id as holiday_id', 'holiday_tagging_headers.id as header_id', 'holiday_tagging_headers.year as year', 'holidays.date as date', 'holidays.name as name', 'holiday_types.name as type')
            ->where(['holiday_tagging_headers.holiday_type_id' => $holiday_type_id, 'holiday_tagging_headers.branch_id' => $branch_id, 'holiday_tagging_headers.year' => $year])
            ->get();

        return json_encode($data);
    }

    public function getPlantillaStep($id, $step_id)
    {

        $salary_step_id = DB::table('plantillas')->select('salary_step_id')->where('id', $id)->get();

        // $step_id = $salary_step_id[0]->salary_step_id + 1;

        $data = DB::table('plantillas')
            ->join('salary_schedules_details', function ($join) use ($step_id) {
                $join->on('plantillas.salary_grade_id', '=', 'salary_schedules_details.salary_grade_id')
                    ->on('salary_schedules_details.salary_step_id', '=', DB::raw($step_id));
            })
            ->join('salary_schedules', 'salary_schedules.id', '=', 'salary_schedules_details.salary_schedule_id')
            ->select('plantillas.salary_grade_id', 'plantillas.salary_step_id', 'plantillas.position_id', 'salary_schedules_details.amount')
            ->where(['plantillas.id' => $id, 'salary_schedules.active' => 1])->get();

        return json_encode($data);
    }

    public function getPlantillaStepID($id, $step_id)
    {
        $data = DB::table('plantillas')
            ->join('salary_schedules_details', function ($join) use ($step_id) {
                $join->on('plantillas.salary_grade_id', '=', 'salary_schedules_details.salary_grade_id')
                    ->on('salary_schedules_details.salary_step_id', '=', DB::raw($step_id));
            })
            ->join('salary_schedules', 'salary_schedules.id', '=', 'salary_schedules_details.salary_schedule_id')
            ->select('plantillas.salary_grade_id', 'plantillas.salary_step_id', 'plantillas.position_id', 'salary_schedules_details.amount')
            ->where(['plantillas.id' => $id, 'salary_schedules.active' => 1])->get();

        return json_encode($data);
    }

    public function getEmployeeForLeaveCredits($id, $user_id)
    {

        $app_key = env("APP_KEY", "");

        $leave_type_info = DB::table('leave_types')->where('id', $id)->get();

        if ($leave_type_info->isNotEmpty()) {
            $leave_accrual_amount = isset($leave_type_info[0]->accrual_amount) ? $leave_type_info[0]->accrual_amount : 0;
        } else {
            $leave_accrual_amount = 0;
        }

        if (Auth::user()->access_all_branches) {

            if ($leave_type_info[0]->name == 'Maternity Leave') {
                $employee = DB::table('employees as a')
                    ->leftJoin('positions as c', 'a.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
                    ->leftJoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                        DB::raw("isnull(c.name,'') as position"),
                        DB::raw("isnull(d.name,'') as department"),
                        DB::raw("isnull(e.name,'') as branch"),
                        DB::raw("case when a.gender_id = 1 then 
                                            $leave_accrual_amount 
                                      else 
                                            cast(0 as decimal(18,3)) 
                                end as credits"),
                        DB::raw("0 as is_editable_id")
                    )
                    ->where([
                        'a.active' => true,
                        'a.is_employee' => true,
                        'a.gender_id' =>  1,
                    ])
                    ->whereNotIn('a.id', function ($query) use ($id) {
                        $query->select('employee_id')->from('leave_credits')->where('leave_type_id', $id);
                    });

                $data = DB::table('leave_credits as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                    ->leftJoin('branches as e', 'b.branch_id', '=', 'e.id')
                    ->join('leave_types as f', 'a.leave_type_id', '=', 'f.id')
                    ->select(
                        'b.id',
                        'b.photo',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                        DB::raw("isnull(c.name,'') as position"),
                        DB::raw("isnull(d.name,'') as department"),
                        DB::raw("isnull(e.name,'') as branch"),
                        DB::raw("case when b.gender_id = 2 then 
                                    0
                                 else
                                    case when isnull(a.credits,0) = 0 then 
                                        case when isnull(f.leave_balance_policy_id,0) = 1 then
                                                case when 
                                                            (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        $leave_accrual_amount
                                                     end
                                             when isnull(f.leave_balance_policy_id,0) = 2 then
                                                case when 
                                                            (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        (isnull(a.credits,0) + $leave_accrual_amount)
                                                     end
                                             else
                                                case when 
                                                            (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        $leave_accrual_amount
                                                     end
                                             end
                                    else 
                                        isnull(a.credits,0)
                                    end 
                                 end as credits"),
                        'f.is_editable_id'
                    )
                    ->where([
                        'a.leave_type_id' => $id,
                        'b.active' => true,
                        'b.is_employee' => true,
                        'b.gender_id' =>  1,
                    ])
                    ->union($employee)
                    ->orderBy('name', 'asc')
                    ->get();
            } elseif ($leave_type_info[0]->name == 'Paternity Leave') {
                $employee = DB::table('employees as a')
                    ->leftJoin('positions as c', 'a.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
                    ->leftJoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                        DB::raw("isnull(c.name,'') as position"),
                        DB::raw("isnull(d.name,'') as department"),
                        DB::raw("isnull(e.name,'') as branch"),
                        DB::raw("case when a.gender_id = 2 then 
                                    $leave_accrual_amount 
                                 else 
                                    cast(0 as decimal(18,3)) 
                                 end as credits"),
                        DB::raw("0 as is_editable_id")
                    )
                    ->where([
                        'a.active' => true,
                        'a.is_employee' => true,
                        'a.gender_id' =>  2,
                    ])
                    ->whereNotIn('a.id', function ($query) use ($id) {
                        $query->select('employee_id')->from('leave_credits')->where('leave_type_id', $id);
                    });

                $data = DB::table('leave_credits as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                    ->leftJoin('branches as e', 'b.branch_id', '=', 'e.id')
                    ->join('leave_types as f', 'a.leave_type_id', '=', 'f.id')
                    ->select(
                        'b.id',
                        'b.photo',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                        DB::raw("isnull(c.name,'') as position"),
                        DB::raw("isnull(d.name,'') as department"),
                        DB::raw("isnull(e.name,'') as branch"),
                        DB::raw("case when b.gender_id = 1 then 
                                    0
                                 else
                                    case when isnull(a.credits,0) = 0 then 
                                        case when isnull(f.leave_balance_policy_id,0) = 1 then
                                                case when 
                                                            (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        $leave_accrual_amount
                                                     end
                                            when isnull(f.leave_balance_policy_id,0) = 2 then
                                                case when 
                                                           (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                       (isnull(a.credits,0) + $leave_accrual_amount)
                                                     end
                                            else
                                                case when 
                                                            (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        $leave_accrual_amount
                                                     end
                                            end
                                    else 
                                        isnull(a.credits,0)
                                    end 
                                 end as credits"),
                        'f.is_editable_id'
                    )
                    ->where([
                        'a.leave_type_id' => $id,
                        'b.active' => true,
                        'b.is_employee' => true,
                        'b.gender_id' =>  2
                    ])
                    ->union($employee)
                    ->orderBy('name', 'asc')
                    ->get();
            } else {
                $employee = DB::table('employees as a')
                    ->leftJoin('positions as c', 'a.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
                    ->leftJoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                        DB::raw("isnull(c.name,'') as position"),
                        DB::raw("isnull(d.name,'') as department"),
                        DB::raw("isnull(e.name,'') as branch"),
                        DB::raw("$leave_accrual_amount as credits"),
                        DB::raw("0 as is_editable_id")
                    )
                    ->where([
                        'a.active' => true,
                        'a.is_employee' => true
                    ])
                    ->whereNotIn('a.id', function ($query) use ($id) {
                        $query->select('employee_id')->from('leave_credits')->where('leave_type_id', $id);
                    });

                $data = DB::table('leave_credits as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                    ->leftJoin('branches as e', 'b.branch_id', '=', 'e.id')
                    ->join('leave_types as f', 'a.leave_type_id', '=', 'f.id')
                    ->select(
                        'b.id',
                        'b.photo',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                        DB::raw("isnull(c.name,'') as position"),
                        DB::raw("isnull(d.name,'') as department"),
                        DB::raw("isnull(e.name,'') as branch"),
                        DB::raw("case when isnull(a.credits,0) = 0 then 
                                    case when isnull(f.leave_balance_policy_id,0) = 1 then
                                            case when 
                                                        (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        $leave_accrual_amount
                                                     end
                                        when isnull(f.leave_balance_policy_id,0) = 2 then
                                            case when 
                                                        (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        (isnull(a.credits,0) + $leave_accrual_amount)
                                                     end
                                        else
                                            case when 
                                                        (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        $leave_accrual_amount
                                                     end
                                        end
                                else 
                                    isnull(a.credits,0)
                                end as credits"),
                        'f.is_editable_id'
                    )
                    ->where([
                        'a.leave_type_id' => $id,
                        'b.active' => true,
                        'b.is_employee' => true
                    ])
                    ->union($employee)
                    ->orderBy('name', 'asc')
                    ->get();
            }
        } else {
            $user_branch_id = DB::table('users as a')
                ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('b.branch_id')
                ->where('a.id', Auth::user()->id)
                ->get();

            if ($leave_type_info[0]->name == 'Maternity Leave') {
                $employee = DB::table('employees as a')
                    ->leftJoin('positions as c', 'a.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
                    ->leftJoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                        DB::raw("isnull(c.name,'') as position"),
                        DB::raw("isnull(d.name,'') as department"),
                        DB::raw("isnull(e.name,'') as branch"),
                        DB::raw("case when a.gender_id = 1 then $leave_accrual_amount else cast(0 as decimal(18,3)) end as credits"),
                        DB::raw("0 as is_editable_id")
                    )
                    ->where([
                        'a.active' => true,
                        'a.is_employee' => true,
                        'a.gender_id' =>  1,
                        'a.branch_id' => isset($user_branch_id[0]->branch_id) ? $user_branch_id[0]->branch_id : 0
                    ])
                    ->whereNotIn('a.id', function ($query) use ($id) {
                        $query->select('employee_id')->from('leave_credits')->where('leave_type_id', $id);
                    });

                $data = DB::table('leave_credits as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                    ->leftJoin('branches as e', 'b.branch_id', '=', 'e.id')
                    ->join('leave_types as f', 'a.leave_type_id', '=', 'f.id')
                    ->select(
                        'b.id',
                        'b.photo',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                        DB::raw("isnull(c.name,'') as position"),
                        DB::raw("isnull(d.name,'') as department"),
                        DB::raw("isnull(e.name,'') as branch"),
                        DB::raw("case when b.gender_id = 2 then 0
                                 else
                                        case when isnull(a.credits,0) = 0 then 
                                        case when isnull(f.leave_balance_policy_id,0) = 1 then
                                                case when 
                                                          (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        $leave_accrual_amount
                                                     end
                                            when isnull(f.leave_balance_policy_id,0) = 2 then
                                                case when 
                                                          (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        (isnull(a.credits,0) + $leave_accrual_amount)
                                                     end
                                            else
                                                case when 
                                                          (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        $leave_accrual_amount
                                                     end
                                            end
                                    else 
                                        isnull(a.credits,0)
                                    end 
                                end as credits"),
                        DB::raw("0 as is_editable_id")
                    )
                    ->where([
                        'a.leave_type_id' => $id,
                        'b.active' => true,
                        'b.is_employee' => true,
                        'b.gender_id' =>  1,
                        'b.branch_id' => isset($user_branch_id[0]->branch_id) ? $user_branch_id[0]->branch_id : 0
                    ])
                    ->union($employee)
                    ->orderBy('name', 'asc')
                    ->get();
            } elseif ($leave_type_info[0]->name == 'Paternity Leave') {
                $employee = DB::table('employees as a')
                    ->leftJoin('positions as c', 'a.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
                    ->leftJoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                        DB::raw("isnull(c.name,'') as position"),
                        DB::raw("isnull(d.name,'') as department"),
                        DB::raw("isnull(e.name,'') as branch"),
                        DB::raw("case when a.gender_id = 2 then $leave_accrual_amount else cast(0 as decimal(18,3)) end as credits"),
                        DB::raw("0 as is_editable_id")
                    )
                    ->where([
                        'a.active' => true,
                        'a.is_employee' => true,
                        'a.gender_id' =>  2,
                        'a.branch_id' => isset($user_branch_id[0]->branch_id) ? $user_branch_id[0]->branch_id : 0
                    ])
                    ->whereNotIn('a.id', function ($query) use ($id) {
                        $query->select('employee_id')->from('leave_credits')->where('leave_type_id', $id);
                    });

                $data = DB::table('leave_credits as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                    ->leftJoin('branches as e', 'b.branch_id', '=', 'e.id')
                    ->join('leave_types as f', 'a.leave_type_id', '=', 'f.id')
                    ->select(
                        'b.id',
                        'b.photo',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                        DB::raw("isnull(c.name,'') as position"),
                        DB::raw("isnull(d.name,'') as department"),
                        DB::raw("isnull(e.name,'') as branch"),
                        DB::raw("case when b.gender_id = 1 then 
                                        cast(0 as int)
                                 else
                                        case when isnull(a.credits,0) = 0 then 
                                            case when isnull(f.leave_balance_policy_id,0) = 1 then
                                                    case when 
                                                            (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        $leave_accrual_amount
                                                     end
                                                when isnull(f.leave_balance_policy_id,0) = 2 then
                                                    case when 
                                                            (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        (isnull(a.credits,0) + $leave_accrual_amount)
                                                     end
                                                else
                                                    case when 
                                                            (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        $leave_accrual_amount
                                                     end
                                                end
                                    else 
                                        isnull(a.credits,0)
                                    end 
                                end as credits"),
                        DB::raw("0 as is_editable_id")
                    )
                    ->where([
                        'a.leave_type_id' => $id,
                        'b.active' => true,
                        'b.is_employee' => true,
                        'b.gender_id' =>  2,
                        'b.branch_id' => isset($user_branch_id[0]->branch_id) ? $user_branch_id[0]->branch_id : 0
                    ])
                    ->union($employee)
                    ->orderBy('name', 'asc')
                    ->get();
            } else {
                $employee = DB::table('employees as a')
                    ->leftJoin('positions as c', 'a.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
                    ->leftJoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                        DB::raw("isnull(c.name,'') as position"),
                        DB::raw("isnull(d.name,'') as department"),
                        DB::raw("isnull(e.name,'') as branch"),
                        DB::raw("$leave_accrual_amount as credits"),
                        DB::raw("0 as is_editable_id")
                    )
                    ->where([
                        'a.active' => true,
                        'a.is_employee' => true,
                        'a.branch_id' => isset($user_branch_id[0]->branch_id) ? $user_branch_id[0]->branch_id : 0
                    ])
                    ->whereNotIn('a.id', function ($query) use ($id) {
                        $query->select('employee_id')->from('leave_credits')->where('leave_type_id', $id);
                    });

                $data = DB::table('leave_credits as a')
                    ->join('employees as b', 'b.id', '=', 'a.employee_id')
                    ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                    ->leftJoin('branches as e', 'b.branch_id', '=', 'e.id')
                    ->join('leave_types as f', 'a.leave_type_id', '=', 'f.id')
                    ->select(
                        'b.id',
                        'b.photo',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                        DB::raw("isnull(c.name,'') as position"),
                        DB::raw("isnull(d.name,'') as department"),
                        DB::raw("isnull(e.name,'') as branch"),
                        DB::raw("case when isnull(a.credits,0) = 0 then 
                                case when isnull(f.leave_balance_policy_id,0) = 1 then
                                        case when 
                                                   (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        $leave_accrual_amount
                                                     end
                                     when isnull(f.leave_balance_policy_id,0) = 2 then
                                        case when 
                                                    (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        (isnull(a.credits,0) + $leave_accrual_amount)
                                                     end
                                     else
                                        case when 
                                                     (select isnull(sum(bb.with_pay),0) 
                                                                from leave_headers as a 
                                                                    inner join leave_details as bb on a.id = bb.leave_id
                                                                where a.employee_id = b.id
                                                                    and a.leave_type_id = f.id
                                                                    and bb.with_pay > 0
                                                                    and DATEPART(YEAR,bb.leave_date) = DATEPART(YEAR,GETDATE())) > 0 then
                                                         isnull(a.credits,0)
                                                     else
                                                        $leave_accrual_amount
                                                     end
                                     end
                            else 
                                isnull(a.credits,0)
                            end as credits"),
                        DB::raw("0 as is_editable_id")
                    )
                    ->where([
                        'a.leave_type_id' => $id,
                        'b.active' => true,
                        'b.is_employee' => true,
                        'b.branch_id' => isset($user_branch_id[0]->branch_id) ? $user_branch_id[0]->branch_id : 0
                    ])
                    ->union($employee)
                    ->orderBy('name', 'asc')
                    ->get();
            }
        }

        // auto update leave credits
        foreach ($data as $d) {
            $data_leave_credits = [];

            $data_leave_credits = [
                'credits' => $d->credits <= 0 ? 0 : $d->credits,
                'created_at' => now(),
                'updated_at' => now()
            ];

            DB::table('leave_credits')->updateOrInsert([
                'employee_id' => $d->id,
                'leave_type_id' => $id
            ], $data_leave_credits);
        }

        return json_encode($data);
    }

    public function getPayrollCutoff($id)
    {

        $data = DB::table('payroll_cutoffs')
            ->where('payroll_interval_id', $id)
            ->orderBy('name', 'asc')
            ->get();

        return json_encode($data);
    }

    public function getPayrollPeriod($id)
    {
        $data = DB::table('payroll_periods as a')
            ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
            ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
            ->select(
                'a.id',
                DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name")
            )
            ->where([
                'a.active' => true,
                'a.posted' => false,
                'a.payroll_interval_id' => $id
            ])
            ->orderBy('a.release_date', 'desc')
            ->get();

        return json_encode($data);
    }

    public function getPayrollPeriodPosted($id)
    {
        $data = DB::table('payroll_periods as a')
            ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
            ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
            ->join('time_data as d', 'a.id', '=', 'd.payroll_period_id')
            ->select(
                'a.id',
                DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name")
            )
            ->where(['a.posted' => true, 'a.payroll_interval_id' => $id])
            ->distinct()
            ->get();

        return json_encode($data);
    }

    public function getOvertimePayrollPeriod($id, $overtime_payroll_id)
    {
        if ($overtime_payroll_id == 0) {
            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                // ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name"),
                    'a.release_date'
                )
                ->where(['a.active' => true, 'a.payroll_interval_id' => $id])
                ->whereNotIn('a.id', function ($query) {
                    $query->select('payroll_period_id')->from('overtime_payroll_headers');
                })
                ->orderBy('a.release_date', 'desc')
                ->distinct()
                ->get();
        } else {

            $data_header = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                // ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name"),
                    'a.release_date',
                    'a.id as selected_period'
                )
                ->where(['a.active' => true, 'a.payroll_interval_id' => $id])
                ->whereIn('a.id', function ($query) use ($overtime_payroll_id) {
                    $query->select('payroll_period_id')->from('overtime_payroll_headers')->where('id', $overtime_payroll_id);
                });

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                // ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name"),
                    'a.release_date',
                    DB::raw("CAST(0 AS INT) as selected_period")
                )
                ->where(['a.active' => true, 'a.payroll_interval_id' => $id])
                ->whereNotIn('a.id', function ($query) {
                    $query->select('payroll_period_id')->from('overtime_payroll_headers');
                })
                ->unionAll($data_header)
                ->orderBy('release_date', 'desc')
                ->distinct()
                ->get();
        }

        return json_encode($data);
    }

    public function getOvertimePayrollPeriodPrint($id)
    {
        $data = DB::table('payroll_periods as a')
            ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
            ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
            ->select(
                'a.id',
                DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name")
            )
            ->where(['a.active' => true, 'a.payroll_interval_id' => $id])
            ->whereIn('a.id', function ($query) {
                $query->select('payroll_period_id')->from('overtime_payroll_headers')->where('posted', true);
            })
            ->orderBy('a.release_date', 'desc')
            ->get();

        return json_encode($data);
    }

    public function getBranchesPayroll($id)
    {
        $data = DB::table('branches as a')
            ->select(
                'a.id',
                'a.name'
            )
            ->whereIn('id', function ($query) use ($id) {
                $query->select('b.branch_id')
                    ->from('payroll_summaries as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->where('a.payroll_period_id', $id)
                    ->get();
            })
            ->get();

        return json_encode($data);
    }

    public function getDepartments($id, $payroll_period_id)
    {
        $data = DB::table('departments as a')
            ->join('branches as b', 'a.branch_id', '=', 'b.id')
            ->select(
                'a.id',
                'a.name',
                'b.is_main_branch'
            )
            ->where('branch_id', $id)
            ->whereIn('a.id', function ($query) use ($payroll_period_id) {
                $query->select('b.department_id')
                    ->from('payroll_summaries as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->where('a.payroll_period_id', $payroll_period_id)
                    ->get();
            })
            ->get();

        return json_encode($data);
    }

    public function getEmployeeDepartments($id, $payroll_period_id)
    {
        $app_key = env("APP_KEY", "");

        $data = DB::table('employees as a')
            ->join('branches as b', 'a.branch_id', '=', 'b.id')
            ->join('departments as c', 'a.department_id', '=', 'c.id')
            ->select(
                'a.id',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                'b.is_main_branch'
            )
            ->where('a.branch_id', $id)
            ->whereIn('a.id', function ($query) use ($payroll_period_id) {
                $query->select('a.employee_id')
                    ->from('payroll_summaries as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->where('a.payroll_period_id', $payroll_period_id)
                    ->get();
            })
            ->get();

        return json_encode($data);
    }

    public function getDeductions()
    {
        $data = DB::table('deductions')
            ->get();

        return json_encode($data);
    }

    public function getIncomes()
    {
        $data = DB::table('incomes')
            ->get();

        return json_encode($data);
    }

    public function getTimeData($id)
    {
        $app_key = env("APP_KEY", "");

        if (Auth::user()->access_all_branches) {
            $data = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('fix_schedules as e', 'b.work_schedule_id', '=', 'e.id')
                ->leftJoin('shift_schedules_headers as f', 'b.work_schedule_id', '=', 'f.id')
                ->select(
                    'b.id',
                    'a.payroll_period_id',
                    'b.photo',
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    DB::raw("case when b.is_shifting = 1 then 'Shifting Schedule' else 'Fixed Schedule' end work_schedule_type"),
                    DB::raw("case when b.is_shifting = 1 then f.name else e.name end work_schedule"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                )
                ->where([
                    'a.payroll_period_id' => $id,
                    'b.active' => true,
                    'b.is_employee' => true
                ])
                ->distinct()
                ->orderBy('name', 'asc')
                ->get();
        } else {
            $user_branch_id = DB::table('users as a')
                ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('b.branch_id')
                ->where('a.id', Auth::user()->id)
                ->get();

            $data = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('fix_schedules as e', 'b.work_schedule_id', '=', 'e.id')
                ->leftJoin('shift_schedules_headers as f', 'b.work_schedule_id', '=', 'f.id')
                ->select(
                    'b.id',
                    'a.payroll_period_id',
                    'b.photo',
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    DB::raw("case when b.is_shifting = 1 then 'Shifting Schedule' else 'Fixed Schedule' end work_schedule_type"),
                    DB::raw("case when b.is_shifting = 1 then f.name else e.name end work_schedule"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                )
                ->where([
                    'a.payroll_period_id' => $id,
                    'b.active' => true,
                    'b.is_employee' => true,
                    'b.branch_id' => $user_branch_id[0]->branch_id
                ])
                ->distinct()
                ->orderBy('name', 'asc')
                ->get();
        }

        return json_encode($data);
    }

    public function getTimeDataOffset($id)
    {
        $app_key = env("APP_KEY", "");
        if (Auth::user()->access_all_branches) {
            $data = DB::table('time_data as a')
                ->join('leave_credits as b', 'a.employee_id', '=', 'b.employee_id')
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->select(
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                               CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                    'a.id',
                    'b.credits',
                    'a.employee_id',
                    DB::raw("CONVERT(NVARCHAR(50),date,110) as date"),
                    'a.applied_offset',
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.late,0)/8)) as late"),
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.undertime,0)/8)) as ut"),
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.absent,0))) as absent"),
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.late,0)/8)) + CONVERT(DECIMAL(18,2),(isnull(a.undertime,0)/8)) + CONVERT(DECIMAL(18,2),(isnull(a.absent,0))) as total")
                )
                ->where('payroll_period_id', $id)
                ->where([
                    'active' => true,
                    'is_employee' => true,
                    'b.leave_type_id' => 16
                ])
                ->where(function ($query) {
                    $query->where('a.late_offset', '>', 0);
                    $query->orWhere('a.undertime_offset', '>', 0);
                    $query->orWhere('a.absent_offset', '>', 0);
                    $query->orWhere('a.late', '>', 0);
                    $query->orWhere('a.undertime', '>', 0);
                    $query->orWhere('a.absent', '>', 0);
                    $query->orWhere('a.applied_offset', '=', 1);
                })
                ->get();
        } else {
            $user_branch_id = DB::table('users as a')
                ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('b.branch_id')
                ->where('a.id', Auth::user()->id)
                ->get();
            $data = DB::table('time_data as a')
                ->join('leave_credits as b', 'a.employee_id', '=', 'b.employee_id')
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->select(
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                    'a.id',
                    'b.credits',
                    'a.employee_id',
                    DB::raw("CONVERT(NVARCHAR(50),date,110) as date"),
                    'a.applied_offset',
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.late,0)/8)) as late"),
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.undertime,0)/8)) as ut"),
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.absent,0))) as absent"),
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.late,0)/8)) + CONVERT(DECIMAL(18,2),(isnull(a.undertime,0)/8)) + CONVERT(DECIMAL(18,2),(isnull(a.absent,0))) as total")
                )
                // ->where('payroll_period_id', $request->payroll_period_id)
                ->where([
                    'active' => true,
                    'is_employee' => true,
                    'b.leave_type_id' => 16,
                    // 'payroll_interval_id' => $request->payroll_interval_id,
                    'c.branch_id' => $user_branch_id[0]->branch_id
                ])
                ->where(function ($query) {
                    $query->where('a.late_offset', '>', 0);
                    $query->orWhere('a.undertime_offset', '>', 0);
                    $query->orWhere('a.absent_offset', '>', 0);
                    $query->orWhere('a.late', '>', 0);
                    $query->orWhere('a.undertime', '>', 0);
                    $query->orWhere('a.absent', '>', 0);
                })
                ->get();
        }
        return json_encode($data);
    }
    public function getTimeDataOffset_details($id, $employee_id)
    {
        $app_key = env("APP_KEY", "");

        if (Auth::user()->access_all_branches) {
            $data = DB::table('time_data as a')
                ->join('leave_credits as b', 'a.employee_id', '=', 'b.employee_id')
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->select(
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                    'a.id',
                    'b.credits',
                    'a.employee_id',
                    'a.date',
                    'a.applied_offset',
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.late,0)/8)) as late"),
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.undertime,0)/8)) as ut"),
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.absent,0))) as absent"),
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.late,0)/8)) + CONVERT(DECIMAL(18,2),(isnull(a.undertime,0)/8)) + CONVERT(DECIMAL(18,2),(isnull(a.absent,0))) as total")
                )
                ->where('payroll_period_id', $id)
                ->where('b.credits', '>', 0)
                ->where([
                    'active' => true,
                    'is_employee' => true,
                    'b.leave_type_id' => 16,
                    'a.employee_id' => $employee_id
                ])
                ->where(function ($query) {
                    $query->where('a.late_offset', '>', 0);
                    $query->orWhere('a.undertime_offset', '>', 0);
                    $query->orWhere('a.absent_offset', '>', 0);
                })
                ->get();
        } else {
            $user_branch_id = DB::table('users as a')
                ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('b.branch_id')
                ->where('a.id', Auth::user()->id)
                ->get();
            $data = DB::table('time_data as a')
                ->join('leave_credits as b', 'a.employee_id', '=', 'b.employee_id')
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->select(
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                    'b.credits',
                    'a.employee_id',
                    'a.date',
                    'a.applied_offset',
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.late,0)/8)) as late"),
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.undertime,0)/8)) as ut"),
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.absent,0))) as absent"),
                    DB::raw("CONVERT(DECIMAL(18,2),(isnull(a.late,0)/8)) + CONVERT(DECIMAL(18,2),(isnull(a.undertime,0)/8)) + CONVERT(DECIMAL(18,2),(isnull(a.absent,0))) as total")
                )
                // ->where('payroll_period_id', $request->payroll_period_id)
                ->where('b.credits', '>', 0)
                ->where([
                    'active' => true,
                    'is_employee' => true,
                    'b.leave_type_id' => 16,
                    // 'payroll_interval_id' => $request->payroll_interval_id,
                    'c.branch_id' => $user_branch_id[0]->branch_id,
                    'a.employee_id' => $employee_id
                ])
                ->where(function ($query) {
                    $query->where('a.late_offset', '>', 0);
                    $query->orWhere('a.undertime_offset', '>', 0);
                    $query->orWhere('a.absent_offset', '>', 0);
                })
                ->get();
        }
        return json_encode($data);
    }

    public function getUnavailableDates($id)
    {
        $employee = db::table('employees')->select('branch_id', 'work_schedule_id')->where('id', $id)->get();

        if ($employee->isEmpty()) {
            $employee = db::table('branches')->select('id as branch_id', db::raw("cast(1 as int) as work_schedule_id"))->where('is_main_branch', true)->get();
        }

        $applied_leaves = DB::table('leave_headers as a')
            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
            ->select(
                db::raw("CONVERT(NVARCHAR(50),DATEPART(D,b.leave_date))+'-'+CONVERT(NVARCHAR(50),DATEPART(M,b.leave_date))+'-'+CONVERT(NVARCHAR(50),DATEPART(YEAR,b.leave_date)) as un_date")
            )
            ->where('a.employee_id', $id)
            ->where('a.is_cancel', false)
            ->Where('a.is_cancel_2', false)
            ->Where('a.is_cancel_3', false);

        $holidays = DB::table('holidays as c')
            ->select(
                db::raw("CONVERT(NVARCHAR(50),DATEPART(D,c.date))+'-'+CONVERT(NVARCHAR(50),DATEPART(M,c.date))+'-'+CONVERT(NVARCHAR(50),DATEPART(YEAR,GETDATE())) as un_date")
            )
            ->where('c.active', true)
            ->whereIn('c.branch', [0, $employee[0]->branch_id])
            ->unionAll($applied_leaves)
            ->orderBy('un_date', 'asc')
            ->get();

        return json_encode($holidays);
    }

    public function getDocumentNumbers($key)
    {
        $data = DB::table('document_numbers')->where('key', $key)->get();

        return json_encode($data);
    }

    public function getUnavailableDates_OT($id)
    {

        $applied_ot = DB::table('overtime_applications as a')
            ->select(
                db::raw("CONVERT(NVARCHAR(50),DATEPART(D,a.date))+'-'+CONVERT(NVARCHAR(50),DATEPART(M,a.date))+'-'+CONVERT(NVARCHAR(50),DATEPART(YEAR,a.date)) as un_date")
            )
            ->where('a.employee_id', $id)
            ->where(db::raw("isnull(a.is_cancel,0)"), false)->get();
        return json_encode($applied_ot);
    }

    public function getApprovers($id, $type_id, $approver_id)
    {
        $app_key = env("APP_KEY", "");

        if ($approver_id == 0) {
            if ($type_id == 1) { //branch
                $data = DB::table('employees as a')
                    ->select(
                        'a.id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name")
                    )
                    ->where([
                        'active' => true,
                        'is_employee' => true
                    ])
                    ->where('branch_id', $id)
                    ->orderBy('a.first_name', 'asc')
                    ->get();
            } elseif ($type_id == 2) { // office
                $data = DB::table('employees as a')
                    ->select(
                        'a.id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name")
                    )
                    ->where([
                        'active' => true,
                        'is_employee' => true
                    ])
                    ->where('department_id', $id)
                    ->orderBy('a.first_name', 'asc')
                    ->get();
            } elseif ($type_id == 3) { // division
                $data = DB::table('employees as a')
                    ->select(
                        'a.id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name")
                    )
                    ->where([
                        'active' => true,
                        'is_employee' => true
                    ])
                    ->where('division_id', $id)
                    ->orderBy('a.first_name', 'asc')
                    ->get();
            } else { // section
                $data = DB::table('employees as a')
                    ->select(
                        'a.id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name")
                    )
                    ->where([
                        'active' => true,
                        'is_employee' => true
                    ])
                    ->where('section_id', $id)
                    ->orderBy('a.first_name', 'asc')
                    ->get();
            }
        } else {
            if ($type_id == 1) { //branch
                $data = DB::table('employees as a')
                    ->select(
                        'a.id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name")
                    )
                    ->where([
                        'active' => true,
                        'is_employee' => true
                    ])
                    ->where('branch_id', $id)
                    ->whereNotIn('a.id', function ($query) use ($approver_id) {
                        $query->select('employee_id')->from('approver_details')
                            ->where([
                                'approver_id' => $approver_id,
                                'is_branch' => true
                            ]);
                    })
                    ->orderBy('a.first_name', 'asc')
                    ->get();
            } elseif ($type_id == 2) { // office
                $data = DB::table('employees as a')
                    ->select(
                        'a.id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name")
                    )
                    ->where([
                        'active' => true,
                        'is_employee' => true
                    ])
                    ->where('department_id', $id)
                    ->whereNotIn('a.id', function ($query) use ($approver_id) {
                        $query->select('employee_id')->from('approver_details')
                            ->where([
                                'approver_id' => $approver_id,
                                'is_department' => true
                            ]);
                    })
                    ->orderBy('a.first_name', 'asc')
                    ->get();
            } elseif ($type_id == 3) { // division
                $data = DB::table('employees as a')
                    ->select(
                        'a.id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name")
                    )
                    ->where([
                        'active' => true,
                        'is_employee' => true
                    ])
                    ->where('division_id', $id)
                    ->whereNotIn('a.id', function ($query) use ($approver_id) {
                        $query->select('employee_id')->from('approver_details')
                            ->where([
                                'approver_id' => $approver_id,
                                'is_division' => true
                            ]);
                    })
                    ->orderBy('a.first_name', 'asc')
                    ->get();
            } else { // section
                $data = DB::table('employees as a')
                    ->select(
                        'a.id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name")
                    )
                    ->where([
                        'active' => true,
                        'is_employee' => true
                    ])
                    ->where('section_id', $id)
                    ->whereNotIn('a.id', function ($query) use ($approver_id) {
                        $query->select('employee_id')->from('approver_details')
                            ->where([
                                'approver_id' => $approver_id,
                                'is_section' => true
                            ]);
                    })
                    ->orderBy('a.first_name', 'asc')
                    ->get();
            }
        }

        return json_encode($data);
    }

    public function getApproversData($id)
    {
        $data = DB::table('approver_headers')->where('id', $id)->get();

        return json_encode($data);
    }

    public function getLoanApplicationFilter(Request $request)
    {
        $app_key = env("APP_KEY", "");
        $dataX = $request->all();
        $gsis = $dataX['gsis'];
        $isgsis = $dataX['isgsis'];
        $philhealth = $dataX['philhealth'];
        $pagibig = $dataX['pagibig'];
        $ispagibig = $dataX['ispagibig'];
        $isother = $dataX['isother'];
        $other = $dataX['other'];
        $data = DB::table('loan_applications as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->join('deductions as c', 'c.id', '=', 'deduction_id')
            ->select(
                'a.*',
                'b.photo',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                DB::raw("CASE WHEN a.is_approve = 'true' then 'Yes' else 'No' end as approve"),
                'c.name as loan',
            )
            // ->orWhere(db::raw("isnull(c.is_gsis,0)"), $gsis)

            ->orderBy('a.effectivity_date', 'desc');
        if ($isgsis == 'true') {
            $data = $data->orWhereIn(db::raw("isnull(c.id,0)"), $gsis);
        }

        // if ($philhealth  == 'true') {
        //     $data = $data->orWhere(db::raw("isnull(c.is_sss,0)"), $philhealth);
        // }
        if ($ispagibig  == 'true') {
            $data = $data->orWhereIn(db::raw("isnull(c.id,0)"), $pagibig);
        }
        if ($isother  == 'true') {
            $data = $data->where(db::raw("isnull(c.is_pagibig,0)"), false)
                ->where(db::raw("isnull(c.is_gsis,0)"), false)
                ->WhereIn(db::raw("isnull(c.id,0)"), $other);
        }

        return json_encode($data->get());
    }

    public function getCOCdetails()
    {
        $app_key = env("APP_KEY", "");
        $Service_Credits = DB::table('leave_types')->where('service_credit', true)->get();

        if (count($Service_Credits) == 0) {
            $Service_Credit = 0;
            $coc_id = 0;
        } else {
            $Service_Credit = 1;
            $coc_id = $Service_Credits[0]->id;
        }

        if (Auth::user()->access_all_branches) {
            $coc_total_hours = DB::table('overtime_applications as a')
                ->join('overtime_types as b', 'a.overtime_type_id', '=', 'b.id')
                ->join('employees as c', 'c.id', '=', 'a.employee_id')
                ->select(
                    'a.employee_id',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                               CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                    DB::raw("DATENAME(month, a.date) as months"),
                    DB::raw("MONTH(a.date) as months_num"),
                    DB::raw("YEAR(a.date) as years"),
                    DB::raw("SUM(isnull(a.total_hours,0) * b.rate) as total_hours"),
                    db::raw("cast(0 as decimal(18,2)) as carryover")
                )
                ->where(['a.service_credits' => true, 'a.approved' => true, 'c.is_employee' => true, 'c.active' => true])
                ->groupBy(
                    DB::raw("DATENAME(month, a.date)"),
                    DB::raw("MONTH(a.date)"),
                    DB::raw("YEAR(a.date)"),
                    'a.employee_id',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                               CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END"),
                );

            $cto_total_hours = DB::table('leave_headers as a')
                ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                ->join('employees as c', 'c.id', '=', 'a.employee_id')
                ->select(
                    'a.employee_id',
                    DB::raw("DATENAME(month, b.leave_date) as months"),
                    DB::raw("MONTH(b.leave_date) as months_num"),
                    DB::raw("YEAR(b.leave_date) as years"),
                    DB::raw("SUM(isnull(b.with_pay,0)) * 8 as total_leave_hours")
                )
                ->where(['a.leave_type_id' => $coc_id, 'a.approved' => true, 'c.is_employee' => true, 'c.active' => true])
                ->groupBy(
                    DB::raw("DATENAME(month, b.leave_date)"),
                    DB::raw("MONTH(b.leave_date)"),
                    DB::raw("YEAR(b.leave_date)"),
                    'a.employee_id'
                );
        } else {
            $user_branch_id = DB::table('users as a')
                ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('b.branch_id')
                ->where('a.id', Auth::user()->id)
                ->get();

            $coc_total_hours = DB::table('overtime_applications as a')
                ->join('overtime_types as b', 'a.overtime_type_id', '=', 'b.id')
                ->join('employees as c', 'c.id', '=', 'a.employee_id')
                ->select(
                    'a.employee_id',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                               CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                    DB::raw("DATENAME(month, a.date) as months"),
                    DB::raw("MONTH(a.date) as months_num"),
                    DB::raw("YEAR(a.date) as years"),
                    DB::raw("SUM(isnull(a.total_hours,0) * b.rate) as total_hours"),
                    db::raw("cast(0 as decimal(18,2)) as carryover")
                )
                ->where(['a.service_credits' => true, 'a.approved' => true, 'c.is_employee' => true, 'c.active' => true, 'c.branch_id' => $user_branch_id[0]->branch_id])
                ->groupBy(
                    DB::raw("DATENAME(month, a.date)"),
                    DB::raw("MONTH(a.date)"),
                    DB::raw("YEAR(a.date)"),
                    'a.employee_id',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                               CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END"),
                );

            $cto_total_hours = DB::table('leave_headers as a')
                ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                ->join('employees as c', 'c.id', '=', 'a.employee_id')
                ->select(
                    'a.employee_id',
                    DB::raw("DATENAME(month, b.leave_date) as months"),
                    DB::raw("MONTH(b.leave_date) as months_num"),
                    DB::raw("YEAR(b.leave_date) as years"),
                    DB::raw("SUM(isnull(b.with_pay,0)) * 8 as total_leave_hours")
                )
                ->where(['a.leave_type_id' => $coc_id, 'a.approved' => true, 'c.is_employee' => true, 'c.active' => true, 'c.branch_id' => $user_branch_id[0]->branch_id])
                ->groupBy(
                    DB::raw("DATENAME(month, b.leave_date)"),
                    DB::raw("MONTH(b.leave_date)"),
                    DB::raw("YEAR(b.leave_date)"),
                    'a.employee_id'
                );
        }

        $coc_details = DB::query()->fromSub($coc_total_hours, 'a');
        $coc = $coc_details
            ->leftjoinSub($cto_total_hours, 'b', function ($join) {
                $join->on('a.employee_id', '=', 'b.employee_id');
                $join->on('a.months_num', '=', 'b.months_num');
                $join->on('a.years', '=', 'b.years');
            })
            ->select(
                'a.employee_id',
                'a.name',
                DB::raw("CONCAT(a.months,', ',a.years) as months"),
                DB::raw("SUM(isnull(a.total_hours,0)) OVER (PARTITION BY a.employee_id ORDER BY a.months_num) as total_hours"),
                DB::raw("isnull(b.total_leave_hours,0) as total_leave_hours"),
                DB::raw("isnull(a.total_hours,0) as carryover"),
                db::raw("SUM(isnull(a.total_hours,0)) OVER (PARTITION BY a.employee_id ORDER BY a.months_num) - isnull(b.total_leave_hours,0) as remaining_balance"),
                db::raw("(SUM(isnull(a.total_hours,0)) OVER (PARTITION BY a.employee_id ORDER BY a.months_num) - isnull(b.total_leave_hours,0)) / 8 as converted_days")
            )
            ->orderby('a.name', 'asc')
            ->orderby('a.months_num', 'asc')
            ->orderby('a.years', 'desc')
            ->get();

        return json_encode($coc);
    }

    public function getBranchEmployee($branch_id)
    {
        $app_key = env("APP_KEY", "");
        $data = DB::table('employees as a')
            ->select(
                'a.*',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name")
            )
            ->where('branch_id', $branch_id)
            ->where('is_employee', 1)
            ->where('active', 1)
            ->get();

        return json_encode($data);
    }

    public function get_loyalty_award_signatory($branch_id)
    {
        $data = DB::table('loyalty_award_signatories')
            ->where('branch_id', $branch_id)
            ->get();

        return json_encode($data);
    }

    public function getMidYearBonus($years, $branch_id)
    {
        $app_key = env("APP_KEY", "");
        if (Auth::user()->access_all_branches) {
            $data = DB::table('midyear_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.id',
                    'a.branch_id',
                    'b.photo',
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.bonus_amount as amount',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                )
                ->where([
                    'a.years' => $years,
                    'b.active' => true,
                    'b.is_employee' => true,
                    'a.branch_id' => $branch_id
                ])
                ->distinct()
                ->orderBy('name', 'asc')
                ->get();
        } else {
            $user_branch_id = DB::table('users as a')
                ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('b.branch_id')
                ->where('a.id', Auth::user()->id)
                ->get();

            $data = DB::table('midyear_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.id',
                    'a.branch_id',
                    'b.photo',
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.bonus_amount as amount',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                )
                ->where([
                    'a.years' => $years,
                    'b.active' => true,
                    'b.is_employee' => true,
                    'b.branch_id' => $user_branch_id[0]->branch_id
                ])
                ->distinct()
                ->orderBy('name', 'asc')
                ->get();
        }

        return json_encode($data);
    }

    public function get_uniform_clothing_allowance_signatory($branch_id)
    {
        $data = DB::table('uniform_clothing_allowance_signatories')
            ->where('branch_id', $branch_id)
            ->get();

        return json_encode($data);
    }

    public function getYearEndBonus($years, $branch_id)
    {
        $app_key = env("APP_KEY", "");
        if (Auth::user()->access_all_branches) {
            $data = DB::table('yearend_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('departments as c', 'c.id', '=', 'b.department_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.id',
                    'a.branch_id',
                    'b.photo',
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.cash_gift_incentive',
                    'a.bonus_amount as amount',
                    'a.cash_gift_amount',
                    DB::raw("(isnull(a.bonus_amount,0) + isnull(a.cash_gift_amount,0)) as total_amount"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                )
                ->where([
                    'a.years' => $years,
                    'b.active' => true,
                    'b.is_employee' => true,
                    'a.branch_id' => $branch_id
                ])
                ->distinct()
                ->orderBy('name', 'asc')
                ->get();
        } else {
            $user_branch_id = DB::table('users as a')
                ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('b.branch_id')
                ->where('a.id', Auth::user()->id)
                ->get();

            $data = DB::table('yearend_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('departments as c', 'c.id', '=', 'b.department_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.id',
                    'a.branch_id',
                    'b.photo',
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.cash_gift_incentive',
                    'a.bonus_amount as amount',
                    'a.cash_gift_amount',
                    DB::raw("(isnull(a.bonus_amount,0) + isnull(a.cash_gift_amount,0)) as total_amount"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                )
                ->where([
                    'a.years' => $years,
                    'b.active' => true,
                    'b.is_employee' => true,
                    'b.branch_id' => $user_branch_id[0]->branch_id
                ])
                ->distinct()
                ->orderBy('name', 'asc')
                ->get();
        }

        return json_encode($data);
    }

    public function getMidYearSignatory($branch_id)
    {
        $data = DB::table('payroll_signatories')
            ->where(['branch_id' => $branch_id, 'report_name' => "Mid Year Bonus"])
            ->get();

        return json_encode($data);
    }
    public function getYearEndSignatory($branch_id)
    {
        $data = DB::table('payroll_signatories')
            ->where(['branch_id' => $branch_id, 'report_name' => "Year End Bonus"])
            ->get();

        return json_encode($data);
    }

    public function getEmployeesWithoutSchedule()
    {
        $app_key = env("APP_KEY", "");

        $data = DB::table('employees as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->select(
                'a.employee_no',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                'b.name as position'
            )
            ->where('work_schedule_id', 0)
            ->get();

        return json_encode($data);
    }

    public function getEmployeesWithoutPayrollID()
    {
        $app_key = env("APP_KEY", "");

        $data = DB::table('employees as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->select(
                'a.employee_no',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                'b.name as position'
            )
            ->where('payroll_interval_id', 0)
            ->get();

        return json_encode($data);
    }

    public function getEmployeeLeave($id, $year)
    {
        $leave_available = DB::table('leave_credits as a')
            ->join('leave_types as b', 'a.leave_type_id', '=', 'b.id')
            ->select(
                'b.name as leave_types',
                DB::raw("cast(0 as decimal(18,3)) as leave_taken"),
                'a.credits as leave_balance',
                'b.id as leave_type_id'
            )
            ->where('a.employee_id', $id)
            ->whereNotIn('b.id', function ($query) use ($id, $year) {
                $query->select('a.leave_type_id')->from('leave_headers as a')
                    ->join('leave_details as b', 'b.leave_id', '=', 'a.id')
                    ->where('a.employee_id', $id)
                    ->whereYear('b.leave_date', $year);
            });

        $data = DB::table('leave_headers as a')
            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
            ->join('leave_credits as c', function ($join) {
                $join->on('a.employee_id', '=', 'c.employee_id')
                    ->on('a.leave_type_id', '=', 'c.leave_type_id');
            })
            ->join('leave_types as d', 'c.leave_type_id', '=', 'd.id')
            ->select(
                'd.name as leave_types',
                DB::raw("sum(isnull(b.with_pay,0)) as leave_taken"),
                'c.credits as leave_balance',
                'd.id as leave_type_id'
            )
            ->where('a.employee_id', $id)
            ->whereYear('b.leave_date', $year)
            ->groupBy(
                'd.name',
                'c.credits',
                'd.id'
            )
            ->unionAll($leave_available)
            ->get();

        return json_encode($data);
    }

    public function getEmployeeIncomes($id)
    {
        $payroll_period_id = DB::table('payroll_incomes')->max('payroll_period_id');

        $data = DB::table('payroll_incomes as a')
            ->join('incomes as b', 'a.income_id', '=', 'b.id')
            ->select(
                'a.id',
                'b.name as item',
                'a.amount'
            )
            ->where([
                'a.employee_id' => $id,
                'a.payroll_period_id' => $payroll_period_id
            ])
            ->get();

        return json_encode($data);
    }
    public function getEmployeeBonus($id)
    {
        $midyear_period = DB::table('midyear_bonus')->max('years');
        $yearend_period = DB::table('yearend_bonus')->max('years');

        $midyear_bonus = DB::table('midyear_bonus as a')
            ->select(
                'a.employee_id',
                DB::raw("CAST('Mid Year Bonus' as varchar(255)) as item"),
                'a.bonus_amount as amount'
            )
            ->where([
                'a.employee_id' => $id,
                'a.years' => $midyear_period
            ]);
        $yearend_bonus = DB::table('yearend_bonus as a')
            ->select(
                'a.employee_id',
                DB::raw("CAST('Year End Bonus' as varchar(255)) as item"),
                'a.bonus_amount as amount'
            )
            ->where([
                'a.employee_id' => $id,
                'a.years' => $yearend_period
            ]);

        $data = $midyear_bonus
            ->union($yearend_bonus)
            ->get();

        return json_encode($data);
    }

    public function getEmployeeLeaveEarned($id)
    {
        $app_key = env("APP_KEY", "");

        $data = DB::table('employee_leave_earned as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->join('positions as c', 'b.position_id', '=', 'c.id')
            ->select(
                'a.employee_id',
                'b.photo',
                'b.employee_no',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                'c.name as position',
                db::raw("case when isnull(a.absent,0) <= 0 then 0 else isnull(a.absent,0) end as days_present"),
                'a.vl_earned',
                'a.sl_earned'
            )
            ->where('a.payroll_period_id', $id)
            ->get();

        return json_encode($data);
    }

    public function getSubCompetencies($id)
    {

        $data = DB::table('subcompetencies as a')
            ->join('competencies as b', 'a.competency_id', '=', 'b.id')
            ->select(
                'a.id',
                'a.name'
            )
            ->where('b.id', $id)
            ->get();

        return json_encode($data);
    }

    public function getTrainingEmployeeList($id, $position_id)
    {
        $app_key = env("APP_KEY", "");

        $data = DB::table('employee_competencies as a')
            ->join('plantilla_competencies as b', function ($join) {
                $join->on('a.plantilla_id', '=', 'b.plantilla_id');
                $join->on('a.subcompetency_id', '=', 'b.subcompetency_id');
            })
            ->join('employees as c', 'a.employee_id', '=', 'c.id')
            ->join('plantillas as f', 'a.plantilla_id', '=', 'f.id')
            ->join('positions as d', 'f.position_id', '=', 'd.id')
            ->join('subcompetencies as e', 'a.subcompetency_id', '=', 'e.id')
            ->select(
                'a.id',
                DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                               CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                'c.position_id',
                'd.name as position',
                'a.subcompetency_id',
                'e.name as department',
                'b.level as required_level',
                'a.level_attained',
                DB::raw("ISNULL(a.is_submitted,0) as is_submitted")
            )
            ->where('f.position_id', $position_id)
            ->where('a.subcompetency_id', $id)
            ->where(DB::raw('ISNULL(a.level_attained,0)'), '<', DB::raw('ISNULL(b.level,0)'))
            ->get();

        return json_encode($data);
    }

    public function getTrainingEmployeeListAll($position_id)
    {
        $app_key = env("APP_KEY", "");
        $data = DB::table('employee_competencies as a')
            ->join('plantilla_competencies as b', function ($join) {
                $join->on('a.plantilla_id', '=', 'b.plantilla_id');
                $join->on('a.subcompetency_id', '=', 'b.subcompetency_id');
            })
            ->join('employees as c', 'a.employee_id', '=', 'c.id')
            ->join('plantillas as f', 'a.plantilla_id', '=', 'f.id')
            ->join('positions as d', 'f.position_id', '=', 'd.id')
            ->join('departments as e', 'c.department_id', '=', 'e.id')
            ->select(
                'c.id',
                DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                               CONCAT(c.first_name,' ',substring(c.middle_name,1,1),'. ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](c.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                            END as name"),
                'c.position_id',
                'd.name as position',
                'c.department_id',
                'e.name as department',
                DB::raw("(SELECT string_agg(CONCAT(D.name, ' - ', C.name), ',') FROM employee_competencies A
			  JOIN plantilla_competencies B ON A.plantilla_id = B.plantilla_id AND A.subcompetency_id = B.subcompetency_id
			  JOIN subcompetencies C ON A.subcompetency_id = C.id
			  JOIN competencies D On C.competency_id = D.id
              JOIN plantillas E ON a.plantilla_id = e.id
				WHERE ISNULL(A.level_attained,0) < ISNULL(B.level,0) AND e.position_id = f.position_id) as comp_details"),
                DB::raw("(SELECT CASE WHEN COUNT(A.id) > 0 THEN 1 ELSE 0 END FROM employee_competencies A 
                JOIN plantilla_competencies B ON A.plantilla_id = B.plantilla_id AND A.subcompetency_id = B.subcompetency_id 
                JOIN employees C ON A.employee_id = c.id
                WHERE is_submitted = 1 AND a.plantilla_id = f.id) as is_submitted")
            )
            ->where('f.position_id', $position_id)
            ->where(DB::raw('ISNULL(a.level_attained,0)'), '<', DB::raw('ISNULL(b.level,0)'))
            ->distinct()
            ->get();

        return json_encode($data);
    }

    public function getDepartmentsReq($id)
    {
        $data = DB::table('departments as a')
            ->join('branches as b', 'a.branch_id', '=', 'b.id')
            ->select(
                'a.id',
                'a.name',
                'b.is_main_branch'
            )
            ->where('branch_id', $id)
            ->get();

        return json_encode($data);
    }

    public function getLeaveIsMaximumAvailment($leave_type_id)
    {
        $leave = DB::table('leave_types')->where([
            'id' => $leave_type_id,
            'leave_balance_policy_id' => 3
        ])->get();

        if ($leave->isNotEmpty()) {
            $is_maximum_availment = true;
            $credits = $leave[0]->accrual_amount;
        } else {
            $is_maximum_availment = false;
            $credits = 0;
        }

        return response()->json(['data' => $is_maximum_availment, 'credit' => $credits], 200);
    }
}
