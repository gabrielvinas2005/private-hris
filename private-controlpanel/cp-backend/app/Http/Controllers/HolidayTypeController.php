<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HolidayTypeController extends Controller
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
            $data = DB::table('holiday_types')->orderby('name', 'asc')->get();

            return $this->successResponse($data, 'Holiday types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve holiday types: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['name']) || !is_array($data['name'])) {
                return $this->errorResponse('Invalid data format. Expected array of holiday types.');
            }

            $arr_len = count($data['name']);
            $processed_count = 0;
            $created_count = 0;
            $updated_count = 0;

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL) {
                    $name = $data['name'][$i];
                    $rate = $data['rate'][$i];

                    // Validation rules
                    $rules = [
                        'rate.' . $i => 'numeric|min:0', // Rate should be a non-negative numeric value
                    ];

                    // Custom error messages
                    $messages = [
                        'rate.' . $i . '.min' => 'The rate must be a non-negative value.',
                    ];

                    // Validate the request data
                    $validator = Validator::make($data, $rules, $messages);

                    // Check if validation fails
                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    $id = $data['id'][$i];
                    $name = $data['name'][$i];

                    $rules = [
                        'name.' . $i => 'required|string|unique:holiday_types,name,' . $id,
                    ];

                    $validator = Validator::make($data, $rules);

                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    if (isset($data['active'])) {
                        if (in_array($data['id'][$i], $data['active'])) {
                            $active = true;
                        } else {
                            $active = false;
                        }
                    } else {
                        $active = false;
                    }

                    if (isset($data['absent_with_pay'])) {
                        if (in_array($data['id'][$i], $data['absent_with_pay'])) {
                            $absent_with_pay = true;
                        } else {
                            $absent_with_pay = false;
                        }
                    } else {
                        $absent_with_pay = false;
                    }

                    if ($data['id'][$i] == null || $data['id'][$i] == 0) {

                        $is_exist = DB::table('holiday_types')->where('name', $data['name'][$i])->get();
                        if ($is_exist->isNotEmpty()) {
                            return $this->errorResponse('"' . $data['name'][$i] . '" already exists.');
                        }
                        $id = DB::table('holiday_types')->max('id') + 1;
                        $created_count++;
                    } else {
                        $id = $data['id'][$i];
                        $updated_count++;
                    }

                    $holiday_data = [
                        'name' => $name,
                        'rate' => $data['rate'][$i],
                        'active' => $active,
                        'absent_with_pay' => $absent_with_pay,
                    ];

                    DB::unprepared('SET IDENTITY_INSERT holiday_types ON');
                    DB::table('holiday_types')->updateOrInsert(['id' => $id], $holiday_data);
                    DB::unprepared('SET IDENTITY_INSERT holiday_types OFF');

                    $processed_count++;
                }
            }

            // Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Holiday Types Setup',
                'activity' => 'Update',
                'description' => 'Updated holiday table information',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'processed_count' => $processed_count,
                'created_count' => $created_count,
                'updated_count' => $updated_count
            ], 'Holiday types updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update holiday types: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('holiday_types')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Holiday type not found');
            }

            return $this->successResponse($data, 'Holiday type data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve holiday type data for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $holiday_type = DB::table('holiday_types')->where('id', $id)->first();

            if (!$holiday_type) {
                return $this->notFoundResponse('Holiday type not found');
            }

            DB::table('holiday_types')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Holiday Types Setup',
                'activity' => 'Delete',
                'description' => 'Deleted holiday information',
            );

            Audit::create($data_audit);

            return $this->successResponse(['deleted_id' => $id, 'deleted_name' => $holiday_type->name], 'Holiday type deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete holiday type: ' . $e->getMessage());
        }
    }
}
