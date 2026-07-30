<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $app_key = env("APP_KEY", "");

            $fix_schedules = DB::table('fix_schedules')->orderBy('id', 'asc')->get();

            $fix_schedule_id = DB::table('fix_schedules')->min('id');

            if (Auth::user()->access_all_branches) {
                $emp_schedule = DB::table('employees')
                    ->select(
                        'id',
                        'photo',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                    )
                    ->selectRaw('case when work_schedule_id = 0 then 0 else 1 end as assign')
                    ->where(['is_employee' => true, 'active' => true, 'is_shifting' => false, 'work_schedule_id' => $fix_schedule_id]);

                $employees = DB::table('employees')
                    ->select(
                        'id',
                        'photo',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                    )
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

                if ($user_branch_id->isEmpty()) {
                    return $this->notFoundResponse('User branch not found');
                }

                $emp_schedule = DB::table('employees')
                    ->select(
                        'id', 
                        'photo',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                    )
                    ->selectRaw('case when work_schedule_id = 0 then 0 else 1 end as assign')
                    ->where(['is_employee' => true, 'active' => true, 'is_shifting' => false, 'work_schedule_id' => $fix_schedule_id, 'branch_id' => $user_branch_id[0]->branch_id]);

                $employees = DB::table('employees')
                    ->select(
                        'id', 
                        'photo', 
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                    )
                    ->selectRaw('case when work_schedule_id = 0 then 0 else 1 end as assign')
                    ->where(['is_employee' => true, 'active' => true, 'is_shifting' => false, 'work_schedule_id' => 0, 'branch_id' => $user_branch_id[0]->branch_id])
                    ->union($emp_schedule)
                    ->orderBy('name', 'asc')
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

            // Reset all employees' work schedule to 0
            DB::table('employees')->where('work_schedule_id', $data['fix_schedule_id'])->update(['work_schedule_id' => 0]);

            $assigned_count = 0;

            if (isset($data['employee_id']) && is_array($data['employee_id'])) {
                $arr_len = count($data['employee_id']);

                for ($i = 0; $i < $arr_len; $i++) {
                    if ($data['employee_id'][$i] != NULL) {
                        DB::table('employees')->where('id', $data['employee_id'][$i])->update(['work_schedule_id' => $data['fix_schedule_id']]);
                        $assigned_count++;
                    }
                }
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
                'assigned_count' => $assigned_count,
                'action' => 'assigned'
            ], 'Employees assigned to fix schedule successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to assign employees to fix schedule: ' . $e->getMessage());
        }
    }
}
