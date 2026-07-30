<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\MidYearTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MidYearController extends Controller
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

    public function index()
    {
        try {
            $data = DB::table('midyear_table')->orderby('months', 'asc')->get();

            return $this->successResponse($data, 'Mid-year bonus table retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve mid-year bonus table: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            $arr_len = count($data['id']);

            $midyear_data = [];


            for ($i = 1; $i < $arr_len; $i++) {

                if ($data['months'][$i] != NULL && $data['percentage'][$i] != NULL) {

                    if ($data['id'][$i] == 0) {
                        $id = DB::table('midyear_table')->max('id') + 1;
                    } else {
                        $id = $data['id'][$i];
                    }

                    $months = $data['months'][$i];
                    $percentage = $data['percentage'][$i];

                    $rules = [
                        'months.' . $i => 'numeric|min:0',
                        'percentage.' . $i => 'numeric|min:0',
                    ];

                    $messages = [
                        'months.' . $i . '.numeric' => 'The months field must be a number.',
                        'months.' . $i . '.min' => 'The months field must be at least 0.',
                        'percentage.' . $i . '.numeric' => 'The percentage field must be a number.',
                        'percentage.' . $i . '.min' => 'The percentage field must be at least 0.',
                    ];

                    $validator = Validator::make($data, $rules, $messages);

                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }


                    $midyear_data = [
                        'months' => $months,
                        'percentage' => $percentage,
                    ];

                    $midyear_months = DB::table('midyear_table')->where('months', $months)->get();

                    if ($midyear_months->isNotEmpty() && $id > 0) {
                        DB::unprepared('SET IDENTITY_INSERT midyear_table ON');
                        DB::table('midyear_table')->updateOrInsert(['months' => $months], $midyear_data);
                        // DB::table('midyear_table')->updateOrInsert(['months' => $data['months'][$i]], $midyear_data);
                        DB::unprepared('SET IDENTITY_INSERT midyear_table OFF');
                    } elseif ($midyear_months->isNotEmpty() && $id == 0) {
                        DB::unprepared('SET IDENTITY_INSERT midyear_table ON');
                        DB::table('midyear_table')->updateOrInsert(['months' => $months], $midyear_data);
                        DB::unprepared('SET IDENTITY_INSERT midyear_table OFF');
                    } else {
                        DB::unprepared('SET IDENTITY_INSERT midyear_table ON');
                        DB::table('midyear_table')->updateOrInsert(['id' => $id], $midyear_data);
                        DB::unprepared('SET IDENTITY_INSERT midyear_table OFF');
                    }
                } else {
                    return $this->errorResponse('No. of Aggregate Months of Service and Percentage of Basic Monthly Salary are required.');
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Mid Year Table Setup',
                'activity' => 'Update',
                'description' => 'Updated midyear table information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Mid-year bonus table updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update mid-year bonus table: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('midyear_table')->where('id', $id)->get();

            return $this->successResponse($data, 'Mid-year bonus table data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve mid-year bonus table for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('midyear_table')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Mid Year Table Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Mid Year table information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Mid-year bonus table deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete mid-year bonus table: ' . $e->getMessage());
        }
    }
}
