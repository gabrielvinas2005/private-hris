<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Traits\ApiResponse;

class AssignFixScheduleController extends Controller
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
            $fix_schedules = Cache::remember('assign_fix_schedule:list', 300, function () {
                return DB::table('fix_schedules')->orderBy('id', 'asc')->get();
            });

            if (Auth::user()->access_all_branches) {
                // Only return employees who are not assigned to any fix schedule (work_schedule_id = 0)
                $employees = DB::table('employees as a')
                    ->join('positions as b', 'a.position_id', '=', 'b.id')
                    ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                    ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.photo',
                        'a.employee_no',
                        'a.department_id',
                        'a.position_id',
                        'a.employment_type_id',
                        'a.first_name',
                        'a.middle_name',
                        'a.last_name',
                        DB::raw("LTRIM(RTRIM(ISNULL(a.first_name, ''))) + ' ' + LTRIM(RTRIM(ISNULL(a.last_name, ''))) as name"),
                        'b.name as position',
                        'b.name as position_name',
                        DB::raw('c.name as department_name'),
                        DB::raw('c.name as department'),
                        DB::raw('d.name as employment_type_name'),
                        DB::raw('d.name as employment_type')
                    )
                    ->selectRaw('0 as assign')
                    ->where(['a.is_employee' => true, 'a.active' => true, 'a.is_shifting' => false, 'a.work_schedule_id' => 0])
                    ->orderBy('a.last_name', 'asc')
                    ->orderBy('a.first_name', 'asc')
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

                // Only return employees who are not assigned to any fix schedule (work_schedule_id = 0)
                $employees = DB::table('employees as a')
                    ->join('positions as b', 'a.position_id', '=', 'b.id')
                    ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                    ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                    ->select(
                        'a.id', 
                        'a.photo',
                        'a.employee_no',
                        'a.department_id',
                        'a.position_id',
                        'a.employment_type_id',
                        'a.first_name',
                        'a.middle_name',
                        'a.last_name',
                        DB::raw("LTRIM(RTRIM(ISNULL(a.first_name, ''))) + ' ' + LTRIM(RTRIM(ISNULL(a.last_name, ''))) as name"),
                        'b.name as position',
                        'b.name as position_name',
                        DB::raw('c.name as department_name'),
                        DB::raw('c.name as department'),
                        DB::raw('d.name as employment_type_name'),
                        DB::raw('d.name as employment_type')
                    )
                    ->selectRaw('0 as assign')
                    ->where(['a.is_employee' => true, 'a.active' => true, 'a.is_shifting' => false, 'a.work_schedule_id' => 0, 'a.branch_id' => $user_branch_id->branch_id])
                    ->orderBy('a.last_name', 'asc')
                    ->orderBy('a.first_name', 'asc')
                    ->get();
            }

            return $this->successResponse([
                'fix_schedules' => $fix_schedules,
                'employees' => $employees
            ], 'Assign fix schedule data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve assign fix schedule data: ' . $e->getMessage());
        }
    }

    /**
     * Get only employees (without schedules) for faster loading
     * This endpoint is used when schedules are already loaded separately
     */
    public function employees()
    {
        try {
            $startedAt = microtime(true);
            $employees = collect();
            $user_branch_id = null;

            if (Auth::user()->access_all_branches) {
                $cacheKey = 'assign_fix_schedule:employees:all';
                $employees = Cache::remember($cacheKey, 60, function () {
                    return DB::table('employees as a')
                        ->select(
                            'a.id',
                            'a.photo',
                            'a.employee_no',
                            'a.department_id',
                            'a.position_id',
                            'a.employment_type_id',
                            'a.first_name',
                            'a.middle_name',
                            'a.last_name',
                            DB::raw("LTRIM(RTRIM(ISNULL(a.first_name, ''))) + ' ' + LTRIM(RTRIM(ISNULL(a.last_name, ''))) as name")
                        )
                        ->selectRaw('0 as assign')
                        ->where([
                            'a.is_employee' => true,
                            'a.active' => true,
                            'a.is_shifting' => false,
                            'a.work_schedule_id' => 0
                        ])
                        ->orderBy('a.last_name', 'asc')
                        ->orderBy('a.first_name', 'asc')
                        ->get();
                });
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->first();

                if (!$user_branch_id) {
                    return $this->notFoundResponse('User branch not found');
                }

                $cacheKey = 'assign_fix_schedule:employees:branch:' . $user_branch_id->branch_id;
                $employees = Cache::remember($cacheKey, 60, function () use ($user_branch_id) {
                    return DB::table('employees as a')
                        ->select(
                            'a.id',
                            'a.photo',
                            'a.employee_no',
                            'a.department_id',
                            'a.position_id',
                            'a.employment_type_id',
                            'a.first_name',
                            'a.middle_name',
                            'a.last_name',
                            DB::raw("LTRIM(RTRIM(ISNULL(a.first_name, ''))) + ' ' + LTRIM(RTRIM(ISNULL(a.last_name, ''))) as name")
                        )
                        ->selectRaw('0 as assign')
                        ->where([
                            'a.is_employee' => true,
                            'a.active' => true,
                            'a.is_shifting' => false,
                            'a.work_schedule_id' => 0,
                            'a.branch_id' => $user_branch_id->branch_id
                        ])
                        ->orderBy('a.last_name', 'asc')
                        ->orderBy('a.first_name', 'asc')
                        ->get();
                });
            }

            // Log for debugging (only in development or if needed)
            \Log::info('AssignFixScheduleController@employees', [
                'user_id' => Auth::user()->id,
                'access_all_branches' => Auth::user()->access_all_branches,
                'employees_count' => $employees->count(),
                'branch_id' => isset($user_branch_id) ? $user_branch_id->branch_id : 'all',
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000)
            ]);

            return $this->successResponse([
                'employees' => $employees
            ], 'Employees retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('AssignFixScheduleController@employees error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse('Failed to retrieve employees: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            // Validate required fields
            if (!isset($data['fix_schedule_id'])) {
                return $this->errorResponse('Fix schedule ID is required', 400);
            }

            $schedule = DB::table('fix_schedules')->where('id', $data['fix_schedule_id'])->first();
            
            if (!$schedule) {
                return $this->notFoundResponse('Fix schedule not found');
            }

            $schedule_name = $schedule->name;

            $assigned_count = 0;
            $newly_assigned_count = 0;

            if (isset($data['employee_id']) && is_array($data['employee_id'])) {
                $arr_len = count($data['employee_id']);

                for ($i = 0; $i < $arr_len; $i++) {
                    if ($data['employee_id'][$i] != NULL) {
                        // Check if employee is already assigned to this schedule
                        $employee = DB::table('employees')->where('id', $data['employee_id'][$i])->first();
                        
                        if ($employee && $employee->work_schedule_id != $data['fix_schedule_id']) {
                            // Only update if not already assigned to this schedule
                            DB::table('employees')->where('id', $data['employee_id'][$i])->update(['work_schedule_id' => $data['fix_schedule_id']]);
                            $newly_assigned_count++;
                            
                            // Also reflect the assignment in time_data without overwriting existing schedules
                            DB::table('time_data')
                                ->where('employee_id', $data['employee_id'][$i])
                                ->where(function($q){
                                    $q->whereNull('work_schedule_id')->orWhere('work_schedule_id', 0);
                                })
                                ->update(['work_schedule_id' => $data['fix_schedule_id']]);
                        }
                        $assigned_count++;
                    }
                }
            }

            // Clear short-lived caches so updated assignments reflect quickly.
            Cache::forget('assign_fix_schedule:employees:all');
            $updatedBranchIds = DB::table('employees')
                ->whereIn('id', $data['employee_id'] ?? [])
                ->whereNotNull('branch_id')
                ->distinct()
                ->pluck('branch_id');
            foreach ($updatedBranchIds as $branchId) {
                Cache::forget('assign_fix_schedule:employees:branch:' . $branchId);
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Assign Fix Schedule',
                'activity' => 'Assign',
                'description' => 'Assigned Employees to fix schedule: ' . $schedule_name . '.',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'fix_schedule_id' => $data['fix_schedule_id'],
                'schedule_name' => $schedule_name,
                'assigned_count' => $newly_assigned_count,
                'total_processed' => $assigned_count,
                'action' => 'assigned'
            ], 'Employees assigned to fix schedule successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to assign employees to fix schedule: ' . $e->getMessage());
        }
    }
}
