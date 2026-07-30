<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;

class ShiftScheduleController extends Controller
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
     * Get all shift schedules
     */
    public function index()
    {
        try {
            $data = DB::table('shift_schedules_headers')->orderBy('date_from', 'desc')->get();

            return $this->successResponse($data, 'Shift schedules retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve shift schedules: ' . $e->getMessage());
        }
    }

    /**
     * Get shift schedule form data
     */
    public function add($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            if ($id == 0) {

                $dummy_shift_schedules = array(
                    'id' => 0,
                    'name' => null,
                    'date_from' => null,
                    'date_to' => null
                );

                $shift_schedules = (object) $dummy_shift_schedules;
                $shift_schedules = collect([$shift_schedules]);

                $dummy_shift_schedules_details = array(
                    'id' => 0,
                    'shift_date' => null,
                    'am_in' => null,
                    'am_out' => null,
                    'break_in' => null,
                    'break_out' => null,
                    'pm_in' => null,
                    'pm_out' => null,
                    'with_nd' => null,
                    'nd_start' => null,
                    'nd_end' => null,
                    'nd_rate' => null,
                    'is_wfh' => null,
                    'grace_period' => null,
                    'flexi_hours' => null,
                    'work_hours' => null
                );
                $shift_schedules_details = (object) $dummy_shift_schedules_details;
                $shift_schedules_details = collect([$shift_schedules_details]);
            } else {

                $shift_schedules = DB::table('shift_schedules_headers')
                    ->where('id', $id)
                    ->get();

                if ($shift_schedules->isEmpty()) {
                    return $this->notFoundResponse('Shift schedule not found');
                }

                $shift_schedules_details = DB::table('shift_schedules_details')->where('shift_schedule_id', $id)
                    ->orderBy('shift_date', 'asc')
                    ->get();
            }

            if (Auth::user()->access_all_branches) {
                // load employeess to assign to shift schedule
                $employees = DB::table('employees as a')
                    ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                    ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                    ->select(
                        'a.id', 
                        'a.photo',
                        'a.employee_no',
                        'a.department_id',
                        'a.position_id',
                        DB::raw("CONCAT(a.first_name,' ',a.last_name) as name"), 
                        'b.name as position',
                        'b.name as position_name',
                        DB::raw('c.name as department_name'),
                        DB::raw('c.name as department')
                    )
                    ->selectRaw('case when a.work_schedule_id = 0 then 0 else 1 end as assign')
                    ->where([
                        'a.is_employee' => true,
                        'a.active' => true,
                        'a.is_shifting' => true,
                        'a.work_schedule_id' => $id
                    ])
                    ->orderBy('name', 'asc')
                    ->get();
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->get();

                // load employeess to assign to shift schedule
                $employees = DB::table('employees as a')
                    ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                    ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                    ->select(
                        'a.id', 
                        'a.photo',
                        'a.employee_no',
                        'a.department_id',
                        'a.position_id',
                        DB::raw("CONCAT(a.first_name,' ',a.last_name) as name"), 
                        'b.name as position',
                        'b.name as position_name',
                        DB::raw('c.name as department_name'),
                        DB::raw('c.name as department')
                    )
                    ->selectRaw('case when a.work_schedule_id = 0 then 0 else 1 end as assign')
                    ->where(['a.is_employee' => true, 'a.active' => true, 'a.is_shifting' => true, 'a.work_schedule_id' => $id, 'a.branch_id' => $user_branch_id[0]->branch_id])
                    ->orderBy('name', 'asc')
                    ->get();
            }

            return $this->successResponse([
                'shift_schedules' => $shift_schedules,
                'shift_schedules_details' => $shift_schedules_details,
                'employees' => $employees
            ], 'Shift schedule form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load shift schedule form data: ' . $e->getMessage());
        }
    }

    /**
     * Store shift schedule
     */
    public function store(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|unique:shift_schedules_headers,name' . ($id ? ",$id" : ''),
                'date_from' => 'required',
                'date_to' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = array(
                'name' => $request->name,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to
            );

            if ($id == 0) {

                // Create header and get the actual inserted ID
                $id = DB::table('shift_schedules_headers')->insertGetId($data);

                // iterate date range for shift schedule details
                $date_from = date("Y-m-d", strtotime($request->date_from));
                $date_to = date("Y-m-d", strtotime($request->date_to));
                $date = date("Y-m-d", strtotime($request->date_from));

                $shift_details = [];

                for ($date = $date_from; $date <= $date_to; $date = date("Y-m-d", strtotime("$date +1 day"))) {
                    $shift_details = [
                        'shift_schedule_id' => $id,
                        'shift_date' => $date,
                        'am_in' => null,
                        'am_out' => null,
                        'break_in' => null,
                        'break_out' => null,
                        'pm_in' => null,
                        'pm_out' => null,
                        'with_nd' => null,
                        'nd_start' => null,
                        'nd_end' => null,
                        'nd_rate' => null,
                        'is_wfh' => 0,
                        'grace_period' => null,
                        'flexi_hours' => null,
                        'work_hours' => null
                    ];

                    DB::table('shift_schedules_details')->insert($shift_details);
                }
            } else {
                // Update existing header
                DB::table('shift_schedules_headers')->where('id', $id)->update($data);

                $data_details = $request->all();
                $shift_details = [];

                $parseTimeToMinutes = function ($timeString) {
                    if ($timeString === null || $timeString === '') {
                        return null;
                    }
                    // Normalize HH:mm:ss to HH:mm
                    $timeString = preg_replace('/:\\d{2}$/', '', $timeString);
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

                $hoursBetween = function ($startMinutes, $endMinutes) {
                    if ($startMinutes === null || $endMinutes === null) {
                        return 0;
                    }
                    $diffMinutes = $endMinutes >= $startMinutes
                        ? ($endMinutes - $startMinutes)
                        : ((24 * 60 - $startMinutes) + $endMinutes);
                    return $diffMinutes > 0 ? $diffMinutes / 60.0 : 0;
                };

                for ($i = 0; $i < count($data_details['shift_date']); $i++) {
                    if ($data_details['shift_date'][$i] != null) {
                        // Compute grace/flexi relationship similar to frontend enforcement
                        $flexiHours = $data_details['flexi_hours'][$i];
                        $gracePeriod = $data_details['grace_period'][$i];

                        // Adjust grace period based on flexi hours and vice versa
                        if ($flexiHours > 0) {
                            $gracePeriod = 0;
                        } elseif ($gracePeriod > 0) {
                            $flexiHours = 0;
                        }
                        $amInMinutes = $parseTimeToMinutes($data_details['am_in'][$i] ?? null);
                        $amOutMinutes = $parseTimeToMinutes($data_details['am_out'][$i] ?? null);
                        $pmInMinutes = $parseTimeToMinutes($data_details['pm_in'][$i] ?? null);
                        $pmOutMinutes = $parseTimeToMinutes($data_details['pm_out'][$i] ?? null);
                        $breakInMinutes = $parseTimeToMinutes($data_details['break_in'][$i] ?? null);
                        $breakOutMinutes = $parseTimeToMinutes($data_details['break_out'][$i] ?? null);

                        $amComplete = $amInMinutes !== null && $amOutMinutes !== null;
                        $pmComplete = $pmInMinutes !== null && $pmOutMinutes !== null;
                        if ($amComplete && $pmComplete) {
                            $computedWorkHours = round(
                                $hoursBetween($amInMinutes, $amOutMinutes) + $hoursBetween($pmInMinutes, $pmOutMinutes),
                                2
                            );
                        } elseif ($amInMinutes !== null && $pmOutMinutes !== null) {
                            $gross = $hoursBetween($amInMinutes, $pmOutMinutes);
                            $breakHours = ($breakInMinutes !== null && $breakOutMinutes !== null)
                                ? $hoursBetween($breakInMinutes, $breakOutMinutes)
                                : 0;
                            $computedWorkHours = round(max(0, $gross - $breakHours), 2);
                        } else {
                            $computedWorkHours = round(
                                ($amComplete ? $hoursBetween($amInMinutes, $amOutMinutes) : 0)
                                + ($pmComplete ? $hoursBetween($pmInMinutes, $pmOutMinutes) : 0),
                                2
                            );
                        }

                        $isWfh = isset($data_details['is_wfh'][$i]) ? (((int) $data_details['is_wfh'][$i]) === 1 ? 1 : 0) : 0;
                        $withNd = isset($data_details['with_nd'][$i]) ? (((int) $data_details['with_nd'][$i]) === 1 ? 1 : 0) : 0;

                        $shift_details = [
                            'shift_schedule_id' => $id,
                            'shift_date' => $data_details['shift_date'][$i],
                            'am_in' => $data_details['am_in'][$i],
                            'am_out' => $data_details['am_out'][$i],
                            'break_in' => $data_details['break_in'][$i],
                            'break_out' => $data_details['break_out'][$i],
                            'pm_in' => $data_details['pm_in'][$i],
                            'pm_out' => $data_details['pm_out'][$i],
                            'with_nd' => $withNd,
                            'nd_start' => $data_details['nd_start'][$i],
                            'nd_end' => $data_details['nd_end'][$i],
                            'nd_rate' => $data_details['nd_rate'][$i],
                            'is_wfh' => $isWfh,
                            'grace_period' => $gracePeriod,
                            'flexi_hours' => $flexiHours,
                            'work_hours' => $computedWorkHours
                        ];

                        // Upsert by composite key (schedule + date) to avoid ID collisions
                        DB::table('shift_schedules_details')->updateOrInsert(
                            [
                                'shift_schedule_id' => $id,
                                'shift_date' => $data_details['shift_date'][$i]
                            ],
                            $shift_details
                        );
                    }
                }

                // incase the user change date-to to add additional work schedule
                $last_date = DB::table('shift_schedules_details')->select('shift_date')->where('shift_schedule_id', $id)->orderBy('shift_date', 'desc')->first();

                $date_from = date("Y-m-d", strtotime("$last_date->shift_date +1 day"));
                $date_to = date("Y-m-d", strtotime($request->date_to));
                $date = date("Y-m-d", strtotime($request->date_from));

                if ($date_to > $date_from) {
                    // add additional dates
                    for ($date = $date_from; $date <= $date_to; $date = date("Y-m-d", strtotime("$date +1 day"))) {
                        $shift_details = [
                            'shift_schedule_id' => $id,
                            'shift_date' => $date,
                            'am_in' => null,
                            'am_out' => null,
                            'break_in' => null,
                            'break_out' => null,
                            'pm_in' => null,
                            'pm_out' => null,
                            'with_nd' => null,
                            'nd_start' => null,
                            'nd_end' => null,
                            'nd_rate' => null,
                            'is_wfh' => 0,
                            'grace_period' => null,
                            'flexi_hours' => null,
                            'work_hours' => null
                        ];

                        DB::table('shift_schedules_details')->insert($shift_details);
                    }
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Timekeeping Module',
                'menu' => 'Shifting Schedule',
                'activity' => 'Create',
                'description' => 'Created shifting schedule informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Shift schedule saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save shift schedule: ' . $e->getMessage());
        }
    }

    /**
     * Load unassigned employees
     */
    public function loadUnassignedEmployees()
    {
        try {
            $app_key = env("APP_KEY", "");

            if (Auth::user()->access_all_branches) {

                // load employeess to assign to shift schedule
                $employees = DB::table('employees as a')
                    ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                    ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                    ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        'a.employee_no',
                        'a.department_id',
                        'a.position_id',
                        'a.employment_type_id',
                        // Original (decrypting) name selection kept for reference:
                        // DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                        //            CONCAT(a.first_name,' ',a.last_name)
                        //        ELSE
                        //            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                        //        END as name"),
                        // Replacement (non-decrypting):
                        DB::raw("CONCAT(a.first_name,' ',a.last_name) as name"),
                        'b.name as position',
                        'b.name as position_name',
                        DB::raw('c.name as department_name'),
                        DB::raw('c.name as department'),
                        DB::raw('d.name as employment_type_name'),
                        DB::raw('d.name as employment_type')
                    )
                    ->selectRaw('case when a.work_schedule_id = 0 then 0 else 1 end as assign')
                    ->where([
                        'a.is_employee' => true,
                        'a.active' => true,
                        'a.is_shifting' => false,
                        'a.work_schedule_id' => 0
                    ])
                    ->orderBy('name', 'asc')
                    ->get();
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->get();

                // load employeess to assign to shift schedule
                $employees = DB::table('employees as a')
                    ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                    ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                    ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        'a.employee_no',
                        'a.department_id',
                        'a.position_id',
                        'a.employment_type_id',
                        // Original (decrypting) name selection kept for reference:
                        // DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                        //            CONCAT(a.first_name,' ',a.last_name)
                        //        ELSE
                        //            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                        //        END as name"),
                        // Replacement (non-decrypting):
                        DB::raw("CONCAT(a.first_name,' ',a.last_name) as name"),
                        'b.name as position',
                        'b.name as position_name',
                        DB::raw('c.name as department_name'),
                        DB::raw('c.name as department'),
                        DB::raw('d.name as employment_type_name'),
                        DB::raw('d.name as employment_type')
                    )
                    ->selectRaw('case when a.work_schedule_id = 0 then 0 else 1 end as assign')
                    ->where([
                        'a.is_employee' => true,
                        'a.active' => true,
                        'a.is_shifting' => false,
                        'a.work_schedule_id' => 0,
                        'a.branch_id' => $user_branch_id[0]->branch_id
                    ])
                    ->orderBy('name', 'asc')
                    ->get();
            }

            return $this->successResponse($employees, 'Unassigned employees loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load unassigned employees: ' . $e->getMessage());
        }
    }

    /**
     * Add employees to shift schedule
     */
    public function addEmployees(Request $request, $id)
    {
        try {
            $employee_data = $request->all();

            if (isset($employee_data['employee_id'])) {
                $arr_len = count($employee_data['employee_id']);

                for ($i = 0; $i < $arr_len; $i++) {
                    if ($employee_data['employee_id'][$i] != NULL) {
                        DB::table('employees')->where('id', $employee_data['employee_id'][$i])->update(['work_schedule_id' => $id, 'is_shifting' => true]);
                    }
                }
            }

            return $this->successResponse(null, 'Employees added to shift schedule successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add employees to shift schedule: ' . $e->getMessage());
        }
    }

    /**
     * Remove employee from shift schedule
     */
    public function removeEmployees($id, $employee_id)
    {
        try {
            // update employees assigned to shift schedule
            DB::table('employees')->where(['work_schedule_id' => $id, 'is_shifting' => true, 'id' => $employee_id])->update(['work_schedule_id' => 0, 'is_shifting' => false]);

            return $this->successResponse(null, 'Employee removed from shift schedule successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to remove employee from shift schedule: ' . $e->getMessage());
        }
    }

    /**
     * Show specific shift schedule
     */
    public function show($id)
    {
        try {
            $data = DB::table('shift_schedules_headers')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Shift schedule not found');
            }

            $details = DB::table('shift_schedules_details')
                ->where('shift_schedule_id', $id)
                ->orderBy('shift_date', 'asc')
                ->get();

            return $this->successResponse([
                'shift_schedule' => $data,
                'details' => $details
            ], 'Shift schedule retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve shift schedule: ' . $e->getMessage());
        }
    }

    /**
     * Delete shift schedule
     */
    public function destroy($id)
    {
        try {
            $shift_schedule = DB::table('shift_schedules_headers')->where('id', $id)->first();

            if (!$shift_schedule) {
                return $this->notFoundResponse('Shift schedule not found');
            }

            // Remove employees from this schedule
            DB::table('employees')->where('work_schedule_id', $id)->update(['work_schedule_id' => 0, 'is_shifting' => false]);

            // Delete schedule details
            DB::table('shift_schedules_details')->where('shift_schedule_id', $id)->delete();

            // Delete schedule header
            DB::table('shift_schedules_headers')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Timekeeping Module',
                'menu' => 'Shifting Schedule',
                'activity' => 'Delete',
                'description' => 'Deleted shifting schedule: ' . $shift_schedule->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Shift schedule deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete shift schedule: ' . $e->getMessage());
        }
    }

    /**
     * Create new shift schedule form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'date_from' => ['type' => 'date', 'required' => true],
                    'date_to' => ['type' => 'date', 'required' => true]
                ]
            ], 'Create shift schedule form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit shift schedule form data
     */
    public function edit($id)
    {
        try {
            $shift_schedule = DB::table('shift_schedules_headers')->where('id', $id)->first();

            if (!$shift_schedule) {
                return $this->notFoundResponse('Shift schedule not found');
            }

            $details = DB::table('shift_schedules_details')
                ->where('shift_schedule_id', $id)
                ->orderBy('shift_date', 'asc')
                ->get();

            return $this->successResponse([
                'shift_schedule' => $shift_schedule,
                'details' => $details
            ], 'Shift schedule retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve shift schedule: ' . $e->getMessage());
        }
    }

    /**
     * Update shift schedule
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|unique:shift_schedules_headers,name,' . $id,
                'date_from' => 'required',
                'date_to' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $shift_schedule = DB::table('shift_schedules_headers')->where('id', $id)->first();

            if (!$shift_schedule) {
                return $this->notFoundResponse('Shift schedule not found');
            }

            $data = array(
                'name' => $request->name,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to
            );

            DB::table('shift_schedules_headers')->where('id', $id)->update($data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Timekeeping Module',
                'menu' => 'Shifting Schedule',
                'activity' => 'Update',
                'description' => 'Updated shifting schedule: ' . $request->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Shift schedule updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update shift schedule: ' . $e->getMessage());
        }
    }
}
