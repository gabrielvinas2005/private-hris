<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class YearEndController extends Controller
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
     * Get all year end table records
     */
    public function index()
    {
        try {
            $data = DB::table('yearend_table')->orderby('months', 'asc')->get();
            return $this->successResponse($data, 'Year end table records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve year end table records: ' . $e->getMessage());
        }
    }

    /**
     * Store year end table records
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['id']) || !is_array($data['id'])) {
                return $this->errorResponse('Invalid data format. Expected array of year end records.');
            }

            $arr_len = count($data['id']);

            $yearend_data = [];
            
            for ($i = 1; $i < $arr_len; $i++) {
                if ($data['months'][$i] != NULL && $data['percentage'][$i] != NULL) {
                    $id = $data['id'][$i];
                    $months = $data['months'][$i];
                    $percentage = $data['percentage'][$i];

                    $rules = [
                        'months.' . $i => 'numeric|min:0',
                        'percentage.' . $i => 'numeric|min:0',
                    ];
                    $messages = [
                        'months.' . $i . '.numeric' => 'The months field must be a number.',
                        'months.' . $i . '.min' => 'The months field must be at least :min.',
                        'percentage.' . $i . '.numeric' => 'The percentage field must be a number.',
                        'percentage.' . $i . '.min' => 'The percentage field must be at least :min.',
                    ];
                    $validator = Validator::make($data, $rules, $messages);
                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    $yearend_data = [
                        'months' => $months,
                        'percentage' => $percentage,
                    ];
                    DB::table('yearend_table')->updateOrInsert(['months' => $data['months'][$i]], $yearend_data);
                } else {
                    return $this->errorResponse('No. of Agregate Months of Service and Percentage of Basic Monthly Salary are required.');
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Year End Table Setup',
                'activity' => 'Update',
                'description' => 'Updated yearend table informations.',
            );
            Audit::create($data_audit);

            return $this->successResponse(null, 'Year end table updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update year end table: ' . $e->getMessage());
        }
    }

    /**
     * Get year end table record for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('yearend_table')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Year end table record not found');
            }

            return $this->successResponse($data, 'Year end table record retrieved for deletion');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve year end table record: ' . $e->getMessage());
        }
    }

    /**
     * Delete year end table record
     */
    public function destroy($id)
    {
        try {
            $record = DB::table('yearend_table')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Year end table record not found');
            }

            DB::table('yearend_table')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Year End Table Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Year End table informations.',
            );
            Audit::create($data_audit);

            return $this->successResponse(null, 'Year end table record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete year end table record: ' . $e->getMessage());
        }
    }

    /**
     * Show specific year end table record
     */
    public function show($id)
    {
        try {
            $data = DB::table('yearend_table')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Year end table record not found');
            }

            return $this->successResponse($data, 'Year end table record retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve year end table record: ' . $e->getMessage());
        }
    }

    /**
     * Create new year end table form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'months' => ['type' => 'number', 'required' => true],
                    'percentage' => ['type' => 'number', 'required' => true]
                ]
            ], 'Create year end table form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit year end table form data
     */
    public function edit($id)
    {
        try {
            $data = DB::table('yearend_table')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Year end table record not found');
            }

            return $this->successResponse($data, 'Year end table record retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve year end table record: ' . $e->getMessage());
        }
    }

    /**
     * Update year end table record
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'months' => 'required|numeric|min:0',
                'percentage' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $record = DB::table('yearend_table')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Year end table record not found');
            }

            $yearend_data = [
                'months' => $request->months,
                'percentage' => $request->percentage,
            ];

            DB::table('yearend_table')->where('id', $id)->update($yearend_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Year End Table Setup',
                'activity' => 'Update',
                'description' => 'Updated Year End table: ' . $request->months . ' months, ' . $request->percentage . '%',
            );
            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Year end table record updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update year end table record: ' . $e->getMessage());
        }
    }
}