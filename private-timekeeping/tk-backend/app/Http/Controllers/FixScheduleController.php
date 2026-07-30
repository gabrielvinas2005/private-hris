<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FixScheduleController extends Controller
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
            // Only return the columns actually used by the frontend (id, name, flags),
            // to keep the payload small and the query fast.
            $data = DB::table('fix_schedules')
                ->select('id', 'name', 'no_late', 'no_undertime', 'is_complete_attendance')
                ->orderBy('name', 'asc')
                ->get();

            return $this->successResponse($data, 'Fix schedules retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve fix schedules: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            if ($id == 0) {

                $dummy_fix_schedules = array(
                    'id' => 0,
                    'name' => null,
                    'no_late' => false,
                    'no_undertime' => false,
                    'is_complete_attendance' => false,
                );

                $fix_schedules = (object) $dummy_fix_schedules;
                $fix_schedules = collect([$fix_schedules]);

                $days = DB::table('schedule_days')
                    ->select('id as day_id', 'name')
                    ->selectRaw(
                        '0 as id,
                        null as am_in,
                        null as am_out,
                        null as break_in,
                        null as break_out,
                        null as pm_in,
                        null as pm_out,
                        null as with_nd,
                        null as nd_start,
                        null as nd_end,
                        null as nd_rate,
                        null as grace_period,
                        null as flexi_hours,
                        null as work_hours,
                        CASE WHEN id = 5 THEN 1 ELSE 0 END as is_wfh'
                    )
                    ->orderBy('day_id', 'asc')
                    ->get();
            } else {

                $fix_schedules = DB::table('fix_schedules')->where('id', $id)->get();

                $days = DB::table('fix_schedules_details as a')
                    ->join('schedule_days as b', 'a.day_id', '=', 'b.id')
                    ->select('a.*', 'b.name as name')
                    ->where('fix_schedule_id', $id)
                    ->orderBy('b.id', 'asc')
                    ->get();
            }

            return $this->successResponse([
                'fix_schedules' => $fix_schedules,
                'days' => $days
            ], 'Fix schedule form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load fix schedule form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $request->validate([
                'schedule_name' => 'required|unique:fix_schedules,name' . ($id ? ",$id" : '')
            ]);

            if ($id == 0) {
                $id = DB::table('fix_schedules')->max('id') + 1;
            }

            $header_data = array(
                'name' => $request->schedule_name,
                'no_late' => $request->has('with_late') ? true : false,
                'no_undertime' => $request->has('with_undertime') ? true : false,
                'is_complete_attendance' => $request->has('is_complete_attendance') ? true : false,
            );

            DB::unprepared('SET IDENTITY_INSERT fix_schedules ON');
            DB::table('fix_schedules')->updateOrInsert(['id' => $id], $header_data);
            DB::unprepared('SET IDENTITY_INSERT fix_schedules OFF');

            $data = $request->all();

            $arr_len = count($data['day_id']);

            $detail_data = [];

            if ($id == 0) {
                $id = DB::table('fix_schedules')->max('id');
            }

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['day_name'][$i] != NULL) {

                    if ($data['id'][$i] == 0) {
                        $dtl_id = 0 + DB::table('fix_schedules_details')->max('id');
                        $dtl_id += 1;
                    } else {
                        $dtl_id = $data['id'][$i];
                    }

                    // compute work hours as (am_out - am_in) + (pm_out - pm_in)
                    // Parse time string (HH:mm or HH:mm:ss) to minutes since midnight
                    $parseTimeToMinutes = function ($timeString) {
                        if ($timeString === null || $timeString === '') {
                            return null;
                        }
                        // Remove seconds if present (HH:mm:ss -> HH:mm)
                        $timeString = preg_replace('/:\d{2}$/', '', $timeString);
                        $parts = explode(':', $timeString);
                        if (count($parts) !== 2) {
                            return null;
                        }
                        $hours = (int)$parts[0];
                        $minutes = (int)$parts[1];
                        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
                            return null;
                        }
                        return $hours * 60 + $minutes;
                    };

                    // Calculate hours between two times (in minutes since midnight)
                    $hoursBetween = function ($startMinutes, $endMinutes) {
                        if ($startMinutes === null || $endMinutes === null) {
                            return 0;
                        }
                        // Handle same-day times (end >= start) or overnight (end < start)
                        $diffMinutes = $endMinutes >= $startMinutes 
                            ? ($endMinutes - $startMinutes) 
                            : ((24 * 60 - $startMinutes) + $endMinutes);
                        return $diffMinutes > 0 ? $diffMinutes / 60.0 : 0;
                    };

                    $amInMinutes      = $parseTimeToMinutes($data['am_in'][$i] ?? null);
                    $amOutMinutes     = $parseTimeToMinutes($data['am_out'][$i] ?? null);
                    $pmInMinutes      = $parseTimeToMinutes($data['pm_in'][$i] ?? null);
                    $pmOutMinutes     = $parseTimeToMinutes($data['pm_out'][$i] ?? null);

                    // Work hours calculation:
                    // If am_out and pm_in are provided, use AM + PM segments
                    // Otherwise, use total time (pm_out - am_in) minus 1 hour break
                    if ($amOutMinutes !== null && $pmInMinutes !== null) {
                        $morningHours   = $hoursBetween($amInMinutes, $amOutMinutes);
                        $afternoonHours = $hoursBetween($pmInMinutes, $pmOutMinutes);
                        $computedWorkHours = round($morningHours + $afternoonHours, 2);
                    } else {
                        // Simplified calculation: total time minus 1 hour break
                        $totalHours = $hoursBetween($amInMinutes, $pmOutMinutes);
                        $computedWorkHours = round(max(0, $totalHours - 1), 2);
                    }

                    $detail_data = [
                        'fix_schedule_id' => $id,
                        'day_id' => $data['day_id'][$i],
                        'am_in' => $data['am_in'][$i] ?? null,
                        'am_out' => $data['am_out'][$i] ?? null,
                        'break_in' => $data['break_in'][$i] ?? null,
                        'break_out' => $data['break_out'][$i] ?? null,
                        'pm_in' => $data['pm_in'][$i] ?? null,
                        'pm_out' => $data['pm_out'][$i] ?? null,
                        'nd_start' => $data['nd_start'][$i],
                        'nd_end' => $data['nd_end'][$i],
                        'nd_rate' => $data['nd_rate'][$i],
                        'with_nd' => isset($data['with_nd'][$data['day_id'][$i]]) ? true : false,
                        'grace_period' => $data['grace_period'][$i],
                        'flexi_hours' => $data['flexi_hours'][$i],
                        'work_hours' => $computedWorkHours,
                        'is_restday' => isset($data['is_restday'][$i]) ? ((int)$data['is_restday'][$i] === 1) : false,
                        'is_wfh' => isset($data['is_wfh'][$i]) ? ((int)$data['is_wfh'][$i] === 1) : false,
                    ];

                    DB::unprepared('SET IDENTITY_INSERT fix_schedules_details ON');
                    DB::table('fix_schedules_details')->updateOrInsert(['id' => $dtl_id], $detail_data);
                    DB::unprepared('SET IDENTITY_INSERT fix_schedules_details OFF');
                }
            }

            if ($id == 0) {
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'Timekeeping Module',
                    'menu' => 'Fix Schedule',
                    'activity' => 'Add',
                    'description' => 'Added fix schedule: ' . $request->schedule_name . ' informations.',
                );

                Audit::create($data_audit);
            } else {
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'Timekeeping Module',
                    'menu' => 'Fix Schedule',
                    'activity' => 'Update',
                    'description' => 'Updated fix schedule: ' . $request->schedule_name . ' informations.',
                );

                Audit::create($data_audit);
            }

            return $this->successResponse(['id' => $id], 'Fix schedule saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save fix schedule: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        // Support GET delete route (legacy/frontend compatibility)
        return $this->destroy($id);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Revert employees assigned to this schedule
            DB::table('employees')
                ->where('work_schedule_id', $id)
                ->update(['work_schedule_id' => 0]);

            // Delete details first due to FK constraints
            DB::table('fix_schedules_details')->where('fix_schedule_id', $id)->delete();
            DB::table('fix_schedules')->where('id', $id)->delete();

            // Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Timekeeping Module',
                'menu' => 'Fix Schedule',
                'activity' => 'Delete',
                'description' => 'Deleted fix schedule id: ' . $id,
            );
            Audit::create($data_audit);

            DB::commit();

            return $this->successResponse(['id' => $id], 'Fix schedule deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete fix schedule: ' . $e->getMessage());
        }
    }

    public function employees($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            if (Auth::user()->access_all_branches) {
                $employees = DB::table('employees as a')
                    ->join('positions as b', 'a.position_id', '=', 'b.id')
                    ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                    ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        'a.employee_no',
                        DB::raw("a.first_name as first_name"),
                        DB::raw("a.middle_name as middle_name"),
                        DB::raw("a.last_name as last_name"),
                        DB::raw("CONCAT(a.first_name,' ',a.last_name) as name"),
                        'b.name as position',
                        DB::raw('c.name as department_name'),
                        DB::raw('c.name as department'),
                        DB::raw('d.name as employment_type')
                    )
                    ->where(['a.is_employee' => true, 'a.active' => true, 'a.is_shifting' => false, 'a.work_schedule_id' => $id])
                    ->orderBy('name', 'asc')
                    ->get();
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->first();

                if (!$user_branch_id) {
                    return $this->notFoundResponse('User branch not found');
                }

                $employees = DB::table('employees as a')
                    ->join('positions as b', 'a.position_id', '=', 'b.id')
                    ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                    ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        'a.employee_no',
                        DB::raw("a.first_name as first_name"),
                        DB::raw("a.middle_name as middle_name"),
                        DB::raw("a.last_name as last_name"),
                        DB::raw("CONCAT(a.first_name,' ',a.last_name) as name"),
                        'b.name as position',
                        DB::raw('c.name as department_name'),
                        DB::raw('c.name as department'),
                        DB::raw('d.name as employment_type')
                    )
                    ->where(['a.is_employee' => true, 'a.active' => true, 'a.is_shifting' => false, 'a.work_schedule_id' => $id, 'a.branch_id' => $user_branch_id->branch_id])
                    ->orderBy('name', 'asc')
                    ->get();
            }

            return $this->successResponse(['employees' => $employees], 'Assigned employees retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load assigned employees: ' . $e->getMessage());
        }
    }

    public function addEmployees(Request $request, $id)
    {
        try {
            // Verify the schedule exists
            $schedule = DB::table('fix_schedules')->where('id', $id)->first();
            
            if (!$schedule) {
                return $this->notFoundResponse('Fix schedule not found');
            }

            $data = $request->all();

            if (!isset($data['employee_id']) || !is_array($data['employee_id'])) {
                return $this->errorResponse('Employee IDs array is required', 400);
            }

            $assigned_count = 0;

            foreach ($data['employee_id'] as $employee_id) {
                if ($employee_id != null) {
                    // Update employee's work schedule
                    DB::table('employees')
                        ->where('id', $employee_id)
                        ->update(['work_schedule_id' => $id]);
                    
                    $assigned_count++;
                }
            }

            // Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Timekeeping Module',
                'menu' => 'Fix Schedule',
                'activity' => 'Assign Employee',
                'description' => 'Assigned ' . $assigned_count . ' employee(s) to fix schedule: ' . $schedule->name,
            );
            Audit::create($data_audit);

            return $this->successResponse([
                'fix_schedule_id' => $id,
                'assigned_count' => $assigned_count
            ], 'Employees assigned to fix schedule successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to assign employees to fix schedule: ' . $e->getMessage());
        }
    }

    public function removeEmployee($id, $employee_id)
    {
        try {
            // Verify the schedule exists
            $schedule = DB::table('fix_schedules')->where('id', $id)->first();
            
            if (!$schedule) {
                return $this->notFoundResponse('Fix schedule not found');
            }

            // Verify the employee exists and is assigned to this schedule
            $employee = DB::table('employees')
                ->where(['id' => $employee_id, 'work_schedule_id' => $id])
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found or not assigned to this schedule');
            }

            // Remove employee from schedule
            DB::table('employees')
                ->where(['id' => $employee_id, 'work_schedule_id' => $id])
                ->update(['work_schedule_id' => 0]);

            // Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Timekeeping Module',
                'menu' => 'Fix Schedule',
                'activity' => 'Remove Employee',
                'description' => 'Removed employee from fix schedule: ' . $schedule->name,
            );
            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Employee removed from fix schedule successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to remove employee from fix schedule: ' . $e->getMessage());
        }
    }
}
