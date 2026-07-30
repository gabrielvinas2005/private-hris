<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\SalarySchedule;
use App\SalaryScheduleDetails;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalarySchedulesController extends Controller
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
     * Get all salary schedules
     */
    public function index()
    {
        try {
            $data = SalarySchedule::all();

            return $this->successResponse($data, 'Salary schedules retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary schedules: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for creating new salary schedule
     */
    public function add()
    {
        try {
            $step = DB::table('salary_steps')->where('active', 1)->orderBy('id', 'asc')->get();
            $grade = DB::table('salary_grades')->where('active', 1)->orderBy('id', 'asc')->get();

            return $this->successResponse([
                'grade' => $grade,
                'step' => $step
            ], 'Salary schedule form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load salary schedule form data: ' . $e->getMessage());
        }
    }

    /**
     * Store new salary schedule
     */
    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|min:3|unique:salary_schedules',
                'effectivity' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $isActive = filter_var($request->input('active', false), FILTER_VALIDATE_BOOLEAN);

            if ($isActive) {
                SalarySchedule::where('active', true)->update(['active' => false]);
            }

            $data = array(
                'name' => $request->name,
                'enabling_law'  => $request->enabling_law,
                'effectivity'   => $request->effectivity,
                'active'        => $isActive,
            );

            $salary_schedule_id = SalarySchedule::create($data)->id;

            $data_salary = $request->all();
            $arr_len = count($data_salary['salary_grade_id']);

            $salary_schedule = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data_salary['amount'][$i] != NULL) {
                    $salary_schedule  = [
                        'salary_schedule_id'    => $salary_schedule_id,
                        'salary_grade_id'       => $data_salary['salary_grade_id'][$i],
                        'salary_step_id'        => $data_salary['salary_step_id'][$i],
                        'amount'                => $data_salary['amount'][$i],
                    ];

                    DB::table('salary_schedules_details')->insert($salary_schedule);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Salary Schedule Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' salary schedule information',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $salary_schedule_id], 'Salary schedule added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add salary schedule: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for editing salary schedule
     */
    public function edit($id)
    {
        try {
            $step = DB::table('salary_steps')->where('active', 1)->orderBy('id', 'asc')->get();
            $grade = DB::table('salary_grades')->where('active', 1)->orderBy('id', 'asc')->get();
            $salary_schedule = DB::table('salary_schedules')->where('id', $id)->first();

            if (!$salary_schedule) {
                return $this->notFoundResponse('Salary schedule not found');
            }

            $salary_schedule_details = DB::table('salary_schedules_details')->where('salary_schedule_id', $id)
                ->orderBy('salary_grade_id', 'asc')
                ->orderBy('salary_step_id', 'asc')
                ->get();

            return $this->successResponse([
                'grade' => $grade,
                'step' => $step,
                'salary_schedule' => $salary_schedule,
                'salary_schedule_details' => $salary_schedule_details
            ], 'Salary schedule form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load salary schedule form data: ' . $e->getMessage());
        }
    }

    /**
     * Update salary schedule
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|min:3',
                'effectivity' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $salary_schedule = DB::table('salary_schedules')->where('id', $id)->first();

            if (!$salary_schedule) {
                return $this->notFoundResponse('Salary schedule not found');
            }

            $isActive = filter_var($request->input('active', false), FILTER_VALIDATE_BOOLEAN);

            if ($isActive) {
                SalarySchedule::where('active', true)->update(['active' => false]);
            }

            $data = array(
                'name' => $request->name,
                'enabling_law'  => $request->enabling_law,
                'effectivity'   => $request->effectivity,
                'active'        => $isActive,
            );

            DB::table('salary_schedules')->where('id', $id)->update($data);

            $data_salary = $request->all();

            $arr_len = count($data_salary['salary_grade_id']);

            $salary_schedule = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data_salary['amount'][$i] != NULL) {
                    $salary_schedule  = [
                        'salary_schedule_id'    => $id,
                        'salary_grade_id'       => $data_salary['salary_grade_id'][$i],
                        'salary_step_id'        => $data_salary['salary_step_id'][$i],
                        'amount'                => $data_salary['amount'][$i],
                    ];

                    DB::table('salary_schedules_details')->updateOrInsert(['salary_schedule_id' =>  $id, 'salary_grade_id' =>  $data_salary['salary_grade_id'][$i], 'salary_step_id' => $data_salary['salary_step_id'][$i]], $salary_schedule);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Salary Schedule Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' salary schedule information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Salary schedule updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update salary schedule: ' . $e->getMessage());
        }
    }

    /**
     * Get salary schedule detail for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('salary_schedules_details')
                ->join('salary_grades', 'salary_grades.id', '=', 'salary_schedules_details.salary_grade_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'salary_schedules_details.salary_step_id')
                ->select('salary_schedules_details.id', 'salary_schedules_details.amount', 'salary_grades.name as grade_name', 'salary_steps.name as step_name')
                ->where('salary_schedules_details.id', $id)
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Salary schedule detail not found');
            }

            return $this->successResponse($data, 'Salary schedule detail retrieved for deletion');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary schedule detail: ' . $e->getMessage());
        }
    }

    /**
     * Delete salary schedule detail
     */
    public function destroy($id)
    {
        try {
            $record = DB::table('salary_schedules_details')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Salary schedule detail not found');
            }

            DB::table('salary_schedules_details')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Salary Schedule Setup',
                'activity' => 'Delete',
                'description' => 'Deleted salary schedule information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Salary schedule detail deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete salary schedule detail: ' . $e->getMessage());
        }
    }

    /**
     * Show specific salary schedule
     */
    public function show($id)
    {
        try {
            $salary_schedule = DB::table('salary_schedules')->where('id', $id)->first();

            if (!$salary_schedule) {
                return $this->notFoundResponse('Salary schedule not found');
            }

            $details = DB::table('salary_schedules_details')
                ->join('salary_grades', 'salary_grades.id', '=', 'salary_schedules_details.salary_grade_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'salary_schedules_details.salary_step_id')
                ->select('salary_schedules_details.*', 'salary_grades.name as grade_name', 'salary_steps.name as step_name')
                ->where('salary_schedules_details.salary_schedule_id', $id)
                ->orderBy('salary_grade_id', 'asc')
                ->orderBy('salary_step_id', 'asc')
                ->get();

            return $this->successResponse([
                'salary_schedule' => $salary_schedule,
                'details' => $details
            ], 'Salary schedule retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve salary schedule: ' . $e->getMessage());
        }
    }

    /**
     * Create new salary schedule form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'enabling_law' => ['type' => 'text', 'required' => false],
                    'effectivity' => ['type' => 'date', 'required' => true],
                    'active' => ['type' => 'boolean', 'required' => false]
                ]
            ], 'Create salary schedule form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }
}
