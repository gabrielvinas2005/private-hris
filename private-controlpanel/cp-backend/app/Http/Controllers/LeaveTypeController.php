<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LeaveTypeController extends Controller
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
     * Get all leave types
     */
    public function index()
    {
        try {
            $data = DB::table('leave_types')->orderby('name', 'asc')->get();

            return $this->successResponse($data, 'Leave types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave types: ' . $e->getMessage());
        }
    }

    /**
     * Store leave types
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name.*"  => "distinct",
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Leave Type name must be unique.');
            }

            $data = $request->all();

            if (!isset($data['name']) || !is_array($data['name'])) {
                return $this->errorResponse('Invalid data format. Expected array of leave types.');
            }

            $arr_len = count($data['name']);

            $leave_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if (isset($data['name'][$i]) && $data['name'][$i] != '' && isset($data['accrual_amount'][$i]) && $data['accrual_amount'][$i] != '') {

                    $id = $data['id'][$i];
                    $accrual_amount = $data['accrual_amount'][$i];

                    $rules = [
                        'accrual_amount.' . $i => 'numeric|min:0',
                    ];

                    $messages = [
                        'accrual_amount.' . $i . '.numeric' => 'The accrual amount field must be a number.',
                        'accrual_amount.' . $i . '.min' => 'The accrual amount field must be at least :min.',
                    ];

                    $validator = Validator::make($data, $rules, $messages);

                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    if (isset($data['service_credit'])) {
                        if (in_array($data['id'][$i], $data['service_credit'])) {
                            $service_credit = true;
                        } else {
                            $service_credit = false;
                        }
                    } else {
                        $service_credit = false;
                    };

                    if (isset($data['is_el'])) {
                        if (in_array($data['id'][$i], $data['is_el'])) {
                            $is_el = true;
                        } else {
                            $is_el = false;
                        }
                    } else {
                        $is_el = false;
                    };

                    $leave_data = [
                        'name' => $data['name'][$i],
                        'accrued_id' => $data['accrued_id'][$i],
                        'accrual_amount' => $accrual_amount,
                        'accrual_frequency_id' => $data['accrual_frequency_id'][$i],
                        'leave_balance_policy_id' => $data['leave_balance_policy_id'][$i],
                        'is_editable_id' => $data['is_editable_id'][$i],
                        'service_credit' =>  $service_credit,
                        'is_el' => $is_el,
                        'active' => isset($data['active'][$data['id'][$i]]) ? true : false,
                    ];

                    if ($data['id'][$i] == null || $data['id'][$i] == 0) {
                        DB::table('leave_types')->insert($leave_data);
                    } else {
                        $id = $data['id'][$i];
                        DB::table('leave_types')->where('id', $id)->update($leave_data);
                    }
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Leave Types Setup',
                'activity' => 'Update',
                'description' => 'Updated leave type table information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Leave types updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update leave types: ' . $e->getMessage());
        }
    }

    /**
     * Get leave type for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('leave_types')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Leave type not found');
            }

            return $this->successResponse($data, 'Leave type data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave type: ' . $e->getMessage());
        }
    }

    /**
     * Delete leave type
     */
    public function destroy($id)
    {
        try {
            $leave_type = DB::table('leave_types')->where('id', $id)->first();

            if (!$leave_type) {
                return $this->notFoundResponse('Leave type not found');
            }

            DB::table('leave_types')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Leave Types Setup',
                'activity' => 'Delete',
                'description' => 'Deleted leave information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Leave type deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete leave type: ' . $e->getMessage());
        }
    }

    /**
     * Show specific leave type
     */
    public function show($id)
    {
        try {
            $data = DB::table('leave_types')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Leave type not found');
            }

            return $this->successResponse($data, 'Leave type retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave type: ' . $e->getMessage());
        }
    }

    /**
     * Create new leave type form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'accrued_id' => ['type' => 'select', 'required' => false],
                    'accrual_amount' => ['type' => 'number', 'required' => true],
                    'accrual_frequency_id' => ['type' => 'select', 'required' => false],
                    'leave_balance_policy_id' => ['type' => 'select', 'required' => false],
                    'is_editable_id' => ['type' => 'select', 'required' => false],
                    'service_credit' => ['type' => 'checkbox', 'required' => false],
                    'is_el' => ['type' => 'checkbox', 'required' => false],
                    'active' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Create leave type form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit leave type form data
     */
    public function edit($id)
    {
        try {
            $data = DB::table('leave_types')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Leave type not found');
            }

            return $this->successResponse($data, 'Leave type retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave type: ' . $e->getMessage());
        }
    }

    /**
     * Update leave type
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|unique:leave_types,name,' . $id,
                'accrual_amount' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $leave_type = DB::table('leave_types')->where('id', $id)->first();

            if (!$leave_type) {
                return $this->notFoundResponse('Leave type not found');
            }

            $leave_data = [
                'name' => $request->name,
                'accrued_id' => $request->accrued_id,
                'accrual_amount' => $request->accrual_amount,
                'accrual_frequency_id' => $request->accrual_frequency_id,
                'leave_balance_policy_id' => $request->leave_balance_policy_id,
                'is_editable_id' => $request->is_editable_id,
                'service_credit' => $request->has('service_credit') ? true : false,
                'is_el' => $request->has('is_el') ? true : false,
                'active' => $request->has('active') ? true : false,
            ];

            DB::table('leave_types')->where('id', $id)->update($leave_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Leave Types Setup',
                'activity' => 'Update',
                'description' => 'Updated leave type: ' . $request->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Leave type updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update leave type: ' . $e->getMessage());
        }
    }
}
