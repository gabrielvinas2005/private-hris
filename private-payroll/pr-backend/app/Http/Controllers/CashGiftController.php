<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\CashGiftTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Unique;
use App\Traits\ApiResponse;

class CashGiftController extends Controller
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
            $data = DB::table('cashgift_table')->orderby('months', 'asc')->get();

            return $this->successResponse($data, 'Cash gift table retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve cash gift table: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            $arr_len = count($data['id']);

            $cashgift_data = [];

            for ($i = 0; $i < $arr_len; $i++) {

                if ($data['months'][$i] == null && $data['percentage'][$i] != null) {
                    return $this->errorResponse('No of Aggregate months of service is required.', 400);
                }

                if ($data['percentage'][$i] == null && $data['months'][$i] != null) {
                    return $this->errorResponse('Percentage of Basic monthly Salary is required.', 400);
                }

                if ($data['months'][$i] != NULL && $data['percentage'][$i] != NULL) {
                    if ($data['id'][$i] == 0) {
                        $id = DB::table('cashgift_table')->max('id') + 1;
                    } else {
                        $id = $data['id'][$i];
                    }

                    $rules = [
                        'months.' . $i => 'numeric|min:0|unique:cashgift_table,months' . ($id ? ",$id" : ''),
                        'percentage.' . $i => 'numeric|min:0',
                    ];
                    $messages = [
                        'months.' . $i . '.unique' => 'The month field must be unique.',
                        'months.' . $i . '.numeric' => 'The month field must be a number.',
                        'months.' . $i . '.min' => 'The month field must be at least :min.',
                        'percentage.' . $i . '.numeric' => 'The percentage field must be a number.',
                        'percentage.' . $i . '.min' => 'The percentage field must be at least :min.',
                    ];

                    $validator = Validator::make($data, $rules, $messages);

                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    $cashgift_data = [
                        'months' => isset($data['months'][$i]) ? $data['months'][$i] : 0,
                        'percentage' => isset($data['percentage'][$i]) ? $data['percentage'][$i] : 0,
                    ];

                    DB::unprepared('SET IDENTITY_INSERT cashgift_table ON');
                    DB::table('cashgift_table')->updateOrInsert(['id' => $id], $cashgift_data);
                    DB::unprepared('SET IDENTITY_INSERT cashgift_table OFF');
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Cash gift Table Setup',
                'activity' => 'Update',
                'description' => 'Updated cashgift table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'You have successfully updated cash gift table!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update cash gift table: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $cashgift = DB::table('cashgift_table')->where('id', $id)->first();
            
            if (!$cashgift) {
                return $this->notFoundResponse('Cash gift record not found');
            }

            return $this->successResponse($cashgift, 'Cash gift record retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve cash gift record: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            return $this->successResponse(null, 'Create cash gift form data');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get create form: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $cashgift = DB::table('cashgift_table')->where('id', $id)->first();
            
            if (!$cashgift) {
                return $this->notFoundResponse('Cash gift record not found');
            }

            return $this->successResponse($cashgift, 'Cash gift record edit data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve cash gift record for editing: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'months' => 'required|numeric|min:0|unique:cashgift_table,months,' . $id,
                'percentage' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $cashgift = DB::table('cashgift_table')->where('id', $id)->first();
            
            if (!$cashgift) {
                return $this->notFoundResponse('Cash gift record not found');
            }

            DB::table('cashgift_table')->where('id', $id)->update([
                'months' => $request->months,
                'percentage' => $request->percentage,
            ]);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Cash gift Table Setup',
                'activity' => 'Update',
                'description' => 'Updated cash gift record information.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Cash gift record updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update cash gift record: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('cashgift_table')->where('id', $id)->first();
            
            if (!$data) {
                return $this->notFoundResponse('Cash gift record not found');
            }

            return $this->successResponse($data, 'Cash gift table data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve cash gift record for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $cashgift = DB::table('cashgift_table')->where('id', $id)->first();
            
            if (!$cashgift) {
                return $this->notFoundResponse('Cash gift record not found');
            }

            DB::table('cashgift_table')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Cash gift Table Setup',
                'activity' => 'Delete',
                'description' => 'Deleted cashgift table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Successfully deleted!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete cash gift record: ' . $e->getMessage());
        }
    }
}
