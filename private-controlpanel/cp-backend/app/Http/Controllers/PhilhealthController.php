<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Philhealth;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PhilhealthController extends Controller
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
     * Get all philhealth records
     */
    public function index()
    {
        try {
            $data = DB::table('philhealths')->orderby('year', 'desc')->get();

            return $this->successResponse($data, 'Philhealth records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve philhealth records: ' . $e->getMessage());
        }
    }

    /**
     * Store philhealth records
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['id']) || !is_array($data['id'])) {
                return $this->errorResponse('Invalid data format. Expected array of philhealth records.');
            }

            $arr_len = count($data['id']);
            $philhealth_data = [];
            $errors = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['year'][$i] != NULL) {
                    $year = $data['year'][$i];
                    $multiplier = $data['multiplier'][$i];
                    $income_floor = $data['income_floor'][$i];
                    $income_ceiling = $data['income_ceiling'][$i];
                    $fix_rate = $data['fix_rate'][$i];

                    $rules = [
                        'multiplier.' . $i => 'required|numeric|min:0',
                        'income_floor.' . $i => 'required|numeric|min:0',
                        'income_ceiling.' . $i => 'required|numeric|min:0',
                        'fix_rate.' . $i => 'required|numeric|min:0',
                    ];

                    $messages = [
                        'multiplier.' . $i . '.min' => 'The multiplier must be a non-negative value.',
                        'income_floor.' . $i . '.min' => 'The income floor must be a non-negative value.',
                        'income_ceiling.' . $i . '.min' => 'The income ceiling must be a non-negative value.',
                        'fix_rate.' . $i . '.min' => 'The fix rate must be a non-negative value.',
                    ];

                    $validator = Validator::make($data, $rules, $messages);
                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    if ($data['year'][$i] == null || $data['year'][$i] == 0) {
                        $year = DB::table('philhealths')->max('year') + 1;
                    } else {
                        $year = $data['year'][$i];
                    }

                    $philhealth_data = [
                        'year' => $year,
                        'multiplier' => $multiplier,
                        'income_floor' => $income_floor,
                        'income_ceiling' => $income_ceiling,
                        'fix_rate' => $fix_rate,
                    ];
                    DB::table('philhealths')->updateOrInsert(['year' => $data['year'][$i]], $philhealth_data);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'PhilHealth Table Setup',
                'activity' => 'Update',
                'description' => 'Updated philhealth table information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Philhealth table updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update philhealth table: ' . $e->getMessage());
        }
    }

    /**
     * Get philhealth record for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('philhealths')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Philhealth record not found');
            }

            return $this->successResponse($data, 'Philhealth record retrieved for deletion');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve philhealth record: ' . $e->getMessage());
        }
    }

    /**
     * Delete philhealth record
     */
    public function destroy($id)
    {
        try {
            $record = DB::table('philhealths')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Philhealth record not found');
            }

            DB::table('philhealths')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'PhilHealth Table Setup',
                'activity' => 'Delete',
                'description' => 'Deleted philhealth table information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Philhealth record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete philhealth record: ' . $e->getMessage());
        }
    }

    /**
     * Show specific philhealth record
     */
    public function show($id)
    {
        try {
            $data = DB::table('philhealths')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Philhealth record not found');
            }

            return $this->successResponse($data, 'Philhealth record retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve philhealth record: ' . $e->getMessage());
        }
    }
}
