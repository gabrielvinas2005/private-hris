<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class COSPayslipController extends Controller
{
    use ApiResponse;

    /**
     * List COS payslip periods for the authenticated employee (by user id).
     *
     * This is similar to PayslipController@index but:
     * - it does NOT depend on time_data
     * - it looks at COS non-DTR tasks marked for payroll
     * - it only includes periods where the payroll_periods row is posted
     */
    public function index($userId)
    {
        try {
            // Map user -> employee id
            $emp_id_data = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->selectRaw('CASE WHEN b.id IS NULL THEN 0 ELSE b.id END as id')
                ->where('a.id', $userId)
                ->get();

            if ($emp_id_data->isEmpty() || !$emp_id_data[0]->id) {
                return $this->successResponse(collect([]), 'No COS payslip periods found');
            }

            $employeeId = $emp_id_data[0]->id;

            // Find COS payroll periods where this employee has approved COS tasks
            $records = DB::table('payroll_periods as p')
                ->join('payroll_intervals as i', 'i.id', '=', 'p.payroll_interval_id')
                ->join('payroll_cutoffs as c', 'c.id', '=', 'p.payroll_cutoff_id')
                ->join('non_dtr_entries as e', function ($join) {
                    $join->on('e.work_date', '>=', 'p.attendance_start_date')
                        ->on('e.work_date', '<=', 'p.attendance_end_date');
                })
                ->join('non_dtr_task as t', 'e.non_dtr_task_id', '=', 't.id')
                ->join('employees as emp', 't.employee_id', '=', 'emp.id')
                ->join('employment_types as et', 'emp.employment_type_id', '=', 'et.id')
                ->where('t.employee_id', $employeeId)
                ->where('t.for_payroll', 1)
                ->where('p.posted', 1)
                ->where(function ($q) {
                    $q->where('et.name', 'Contract of Service')
                        ->orWhere('et.name', 'LIKE', '%Contract of Service%');
                })
                ->select(
                    'p.id',
                    't.employee_id',
                    'i.name as payroll_interval',
                    'c.name as cut_off',
                    'p.payroll_start_date',
                    'p.payroll_end_date'
                )
                ->orderBy('p.payroll_start_date', 'asc')
                ->distinct()
                ->get();

            return $this->successResponse($records, 'COS payslip periods retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve COS payslip periods: ' . $e->getMessage());
        }
    }

    /**
     * View COS payslip details for a specific employee and payroll period.
     *
     * This uses the COS non-DTR entries as the basis for the "payslip",
     * and uses the employee's salary as the net pay for now.
     */
    public function view($employeeId, $payrollPeriodId)
    {
        try {
            $period = DB::table('payroll_periods as p')
                ->join('payroll_intervals as i', 'i.id', '=', 'p.payroll_interval_id')
                ->join('payroll_cutoffs as c', 'c.id', '=', 'p.payroll_cutoff_id')
                ->select(
                    'p.*',
                    'i.name as payroll_interval',
                    'c.name as cut_off'
                )
                ->where('p.id', $payrollPeriodId)
                ->first();

            if (!$period) {
                return $this->notFoundResponse('Payroll period not found.');
            }

            $startDate = $period->attendance_start_date;
            $endDate = $period->attendance_end_date;

            $app_key = env("APP_KEY", "");

            $rows = DB::table('non_dtr_entries as e')
                ->join('non_dtr_task as t', 'e.non_dtr_task_id', '=', 't.id')
                ->join('employees as emp', 't.employee_id', '=', 'emp.id')
                ->join('employment_types as et', 'emp.employment_type_id', '=', 'et.id')
                ->where('t.employee_id', $employeeId)
                ->whereBetween('e.work_date', [$startDate, $endDate])
                ->whereDate('e.work_date', '>=', DB::raw('t.period_from'))
                ->whereDate('e.work_date', '<=', DB::raw('t.period_to'))
                ->where(function ($q) {
                    $q->where('et.name', 'Contract of Service')
                        ->orWhere('et.name', 'LIKE', '%Contract of Service%');
                })
                ->select(
                    'e.id as entry_id',
                    'e.non_dtr_task_id',
                    'e.work_date',
                    'e.time_in',
                    'e.time_out',
                    'e.hours_worked',
                    'e.accomplishments',
                    'e.output_description',
                    'e.location',
                    't.for_payroll',
                    'emp.id as employee_id',
                    'emp.employee_no',
                    'emp.salary',
                    DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN
                                CONCAT(emp.first_name,' ',emp.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](emp.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](emp.last_name,'$app_key'))
                            END as employee_name")
                )
                ->orderBy('e.work_date')
                ->orderBy('e.id')
                ->get();

            if ($rows->isEmpty()) {
                return $this->notFoundResponse('No COS payroll entries found for this period.');
            }

            $first = $rows->first();

            $entries = $rows->map(function ($row) {
                return [
                    'entry_id' => $row->entry_id,
                    'non_dtr_task_id' => $row->non_dtr_task_id,
                    'work_date' => $row->work_date,
                    'hours_worked' => $row->hours_worked,
                    'accomplishments' => $row->accomplishments,
                    'output_description' => $row->output_description,
                    'location' => $row->location,
                ];
            });

            $payslip = [
                'employee' => [
                    'id' => $first->employee_id,
                    'employee_no' => $first->employee_no,
                    'name' => $first->employee_name,
                    'salary' => $first->salary,
                    'net_pay' => $first->salary,
                ],
                'period' => $period,
                'entries' => $entries,
            ];

            return $this->successResponse($payslip, 'COS payslip data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve COS payslip data: ' . $e->getMessage());
        }
    }
}

