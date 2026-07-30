<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\LeaveCredit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveCreditController extends Controller
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
     * Get all leave credit types
     */
    public function index()
    {
        try {
            $leave_types = DB::table('leave_types')->where('active', true)->get();

            return $this->successResponse($leave_types, 'Leave credit types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave credit types: ' . $e->getMessage());
        }
    }

    /**
     * Store leave credits
     */
    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'leave_type_id' => 'required|exists:leave_types,id',
                'id' => 'required|array',
                'id.*' => 'required|exists:employees,id',
                'credits' => 'required|array',
                'credits.*' => 'numeric|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = $request->all();
            $leave_credits_data = [];

            for ($i = 0; $i < count($data["id"]); $i++) {
                $leave_credits_data = [
                    'employee_id' => $data["id"][$i],
                    'leave_type_id' => $request->leave_type_id,
                    'credits' => isset($data["credits"][$i]) && $data["credits"][$i] > 0 ? $data["credits"][$i] : 0,
                ];

                DB::table('leave_credits')->updateOrInsert(['employee_id' => $data["id"][$i], 'leave_type_id' => $request->leave_type_id], $leave_credits_data);
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Leave Credits',
                'activity' => 'Update',
                'description' => 'Updated Leave Credit information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Leave credits updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update leave credits: ' . $e->getMessage());
        }
    }

    /**
     * Show specific leave credit
     */
    public function show($id)
    {
        try {
            $data = DB::table('leave_credits')
                ->join('employees', 'leave_credits.employee_id', '=', 'employees.id')
                ->join('leave_types', 'leave_credits.leave_type_id', '=', 'leave_types.id')
                ->select(
                    'leave_credits.*',
                    'employees.first_name',
                    'employees.last_name',
                    'leave_types.name as leave_type_name'
                )
                ->where('leave_credits.id', $id)
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Leave credit not found');
            }

            return $this->successResponse($data, 'Leave credit retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave credit: ' . $e->getMessage());
        }
    }

    /**
     * Create new leave credit form data
     */
    public function create()
    {
        try {
            $leave_types = DB::table('leave_types')->where('active', true)->get();
            $employees = DB::table('employees')
                ->where(['active' => true, 'is_employee' => true])
                ->select('id', 'first_name', 'last_name')
                ->get();

            return $this->successResponse([
                'leave_types' => $leave_types,
                'employees' => $employees,
                'fields' => [
                    'leave_type_id' => ['type' => 'select', 'required' => true],
                    'employee_id' => ['type' => 'select', 'required' => true],
                    'credits' => ['type' => 'number', 'required' => true, 'min' => 0]
                ]
            ], 'Create leave credit form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit leave credit form data
     */
    public function edit($id)
    {
        try {
            $data = DB::table('leave_credits')
                ->join('employees', 'leave_credits.employee_id', '=', 'employees.id')
                ->join('leave_types', 'leave_credits.leave_type_id', '=', 'leave_types.id')
                ->select(
                    'leave_credits.*',
                    'employees.first_name',
                    'employees.last_name',
                    'leave_types.name as leave_type_name'
                )
                ->where('leave_credits.id', $id)
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Leave credit not found');
            }

            $leave_types = DB::table('leave_types')->where('active', true)->get();
            $employees = DB::table('employees')
                ->where(['active' => true, 'is_employee' => true])
                ->select('id', 'first_name', 'last_name')
                ->get();

            return $this->successResponse([
                'leave_credit' => $data,
                'leave_types' => $leave_types,
                'employees' => $employees
            ], 'Leave credit retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave credit: ' . $e->getMessage());
        }
    }

    /**
     * Update leave credit
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'leave_type_id' => 'required|exists:leave_types,id',
                'credits' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $leave_credit = DB::table('leave_credits')->where('id', $id)->first();

            if (!$leave_credit) {
                return $this->notFoundResponse('Leave credit not found');
            }

            $leave_credits_data = [
                'employee_id' => $request->employee_id,
                'leave_type_id' => $request->leave_type_id,
                'credits' => $request->credits,
            ];

            DB::table('leave_credits')->where('id', $id)->update($leave_credits_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Leave Credits',
                'activity' => 'Update',
                'description' => 'Updated Leave Credit for employee ID: ' . $request->employee_id,
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Leave credit updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update leave credit: ' . $e->getMessage());
        }
    }

    /**
     * Delete leave credit
     */
    public function destroy($id)
    {
        try {
            $leave_credit = DB::table('leave_credits')->where('id', $id)->first();

            if (!$leave_credit) {
                return $this->notFoundResponse('Leave credit not found');
            }

            DB::table('leave_credits')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Leave Credits',
                'activity' => 'Delete',
                'description' => 'Deleted Leave Credit for employee ID: ' . $leave_credit->employee_id,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Leave credit deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete leave credit: ' . $e->getMessage());
        }
    }
}
