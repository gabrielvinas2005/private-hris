<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\PayrollInterval;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayrollIntervalController extends Controller
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
            $data = DB::table('payroll_intervals')->orderby('id', 'asc')->get();

            return $this->successResponse($data, 'Payroll intervals retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll intervals: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $arr_len = count($data['name']);
            $payroll_interval_data = [];
            $created_count = 0;
            $updated_count = 0;
            $errors = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL && $data['day_interval'][$i] != NULL && $data['month_frequency'][$i] != NULL && $data['year_frequency'][$i] != NULL) {

                    if ($data['id'][$i] == null) {
                        $id = DB::table('payroll_intervals')->max('id') + 1;
                    } else {
                        $id = $data['id'][$i];
                    }

                    $rules = [
                        'name.' . $i => 'required|unique:payroll_intervals,name' . ($id ? ",$id" : ''),
                        'day_interval.' . $i => 'required|numeric|min:0',
                        'month_frequency.' . $i => 'required|numeric|min:0',
                        'year_frequency.' . $i => 'required|numeric|min:0',
                    ];
                    $messages = [
                        'name.' . $i . '.required' => 'The payroll interval name field is required.',
                        'name.' . $i . '.unique' => 'The payroll interval name must be unique.',
                        'day_interval.' . $i . '.required' => 'The day interval field is required.',
                        'day_interval.' . $i . '.numeric' => 'The day interval field must be a number.',
                        'day_interval.' . $i . '.min' => 'The day interval field must be at least :min.',
                        'month_frequency.' . $i . '.required' => 'The month frequency field is required.',
                        'month_frequency.' . $i . '.numeric' => 'The month frequency field must be a number.',
                        'month_frequency.' . $i . '.min' => 'The month frequency field must be at least :min.',
                        'year_frequency.' . $i . '.required' => 'The year frequency field is required.',
                        'year_frequency.' . $i . '.numeric' => 'The year frequency field must be a number.',
                        'year_frequency.' . $i . '.min' => 'The year frequency field must be at least :min.',
                    ];

                    $validator = Validator::make($data, $rules, $messages);

                    if ($validator->fails()) {
                        $errors[] = [
                            'row' => $i + 1,
                            'errors' => $validator->errors()->toArray()
                        ];
                        continue;
                    }

                    $day_interval = $data['day_interval'][$i];
                    $month_frequency = $data['month_frequency'][$i];
                    $year_frequency = $data['year_frequency'][$i];

                    $payroll_interval_data = [
                        'name' => $data['name'][$i],
                        'day_interval' => $day_interval,
                        'month_frequency' => $month_frequency,
                        'year_frequency' => $year_frequency,
                    ];

                    // Check if record exists
                    $existing = DB::table('payroll_intervals')
                        ->where('id', $id)
                        ->exists();

                    DB::unprepared('SET IDENTITY_INSERT payroll_intervals ON');
                    DB::table('payroll_intervals')->updateOrInsert(['id' => $id], $payroll_interval_data);
                    DB::unprepared('SET IDENTITY_INSERT payroll_intervals OFF');

                    if ($existing) {
                        $updated_count++;
                    } else {
                        $created_count++;
                    }
                }
            }

            // If there are validation errors, return them
            if (!empty($errors)) {
                return $this->validationErrorResponse(['bulk_errors' => $errors]);
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Payroll Interval Setup',
                'activity' => 'Update',
                'description' => 'Updated Payroll Interval information',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'created_count' => $created_count,
                'updated_count' => $updated_count,
                'total_processed' => $created_count + $updated_count
            ], 'You have successfully updated payroll interval!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update payroll interval: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('payroll_intervals')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Payroll interval record not found');
            }

            return $this->successResponse($data, 'Payroll interval data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll interval data: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $record = DB::table('payroll_intervals')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Payroll interval record not found');
            }

            DB::table('payroll_intervals')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Payroll Interval Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Payroll Interval information',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'deleted_id' => $id,
                'deleted_name' => $record->name,
                'deleted_day_interval' => $record->day_interval,
                'deleted_month_frequency' => $record->month_frequency,
                'deleted_year_frequency' => $record->year_frequency
            ], 'Successfully deleted!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete payroll interval: ' . $e->getMessage());
        }
    }

    public function updateActive(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'active' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $record = DB::table('payroll_intervals')->where('id', $id)->first();
            if (!$record) {
                return $this->notFoundResponse('Payroll interval record not found');
            }

            $active = $request->boolean('active') ? 1 : 0;
            DB::table('payroll_intervals')->where('id', $id)->update(['active' => $active]);

            $data_audit = array(
                'user_id' => auth()->id(),
                'module'  => 'Control Panel',
                'menu'    => 'Payroll Interval Setup',
                'activity' => 'Update',
                'description' => 'Updated Payroll Interval active status.',
            );
            Audit::create($data_audit);

            return $this->successResponse([
                'id' => (int) $id,
                'active' => (bool) $active
            ], 'Payroll interval status updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update payroll interval status: ' . $e->getMessage());
        }
    }
}
