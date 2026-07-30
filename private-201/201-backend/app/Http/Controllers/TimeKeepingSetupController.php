<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimeKeepingSetupController extends Controller
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
     * Get timekeeping setup data
     */
    public function index()
    {
        try {
            $employment_types = DB::table('employment_types')->where('active', true)->orderby('name', 'asc')->get();

            return $this->successResponse($employment_types, 'Employment types for timekeeping setup retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employment types: ' . $e->getMessage());
        }
    }

    /**
     * Store timekeeping setup
     */
    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employment_type_id' => 'required|exists:employment_types,id',
                'work_days' => 'required|numeric|min:0',
                'work_hours' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = $request->all();

            $e_id = $data['employment_type_id'];

            $timekeeping_data = [
                'work_days' => $data['work_days'],
                'work_hours' => $data['work_hours'],
                'with_holiday_pay' => $request->has('with_holiday_pay') ? true : false
            ];

            DB::table('time_keeping_setups')->updateOrInsert(['employment_type_id' => $e_id], $timekeeping_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Timekeeping Setup',
                'activity' => 'Update',
                'description' => 'Updated timekeeping informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['employment_type_id' => $e_id], 'Timekeeping setup updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update timekeeping setup: ' . $e->getMessage());
        }
    }

    /**
     * Get timekeeping setup data for specific employment type
     */
    public function getdata($id)
    {
        try {
            $data = DB::table('time_keeping_setups')
                ->select("employment_type_id", "work_days", "work_hours", "with_holiday_pay")
                ->where('employment_type_id', $id)
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Timekeeping setup not found for this employment type');
            }

            return $this->successResponse($data, 'Timekeeping setup data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve timekeeping setup data: ' . $e->getMessage());
        }
    }

    /**
     * Show specific timekeeping setup
     */
    public function show($id)
    {
        try {
            $data = DB::table('time_keeping_setups')
                ->join('employment_types', 'employment_types.id', '=', 'time_keeping_setups.employment_type_id')
                ->select(
                    'time_keeping_setups.*',
                    'employment_types.name as employment_type_name'
                )
                ->where('time_keeping_setups.employment_type_id', $id)
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Timekeeping setup not found');
            }

            return $this->successResponse($data, 'Timekeeping setup retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve timekeeping setup: ' . $e->getMessage());
        }
    }

    /**
     * Create new timekeeping setup form data
     */
    public function create()
    {
        try {
            $employment_types = DB::table('employment_types')->where('active', true)->orderby('name', 'asc')->get();

            return $this->successResponse([
                'employment_types' => $employment_types,
                'fields' => [
                    'employment_type_id' => ['type' => 'select', 'required' => true],
                    'work_days' => ['type' => 'number', 'required' => true],
                    'work_hours' => ['type' => 'number', 'required' => true],
                    'with_holiday_pay' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Create timekeeping setup form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit timekeeping setup form data
     */
    public function edit($id)
    {
        try {
            $data = DB::table('time_keeping_setups')
                ->join('employment_types', 'employment_types.id', '=', 'time_keeping_setups.employment_type_id')
                ->select(
                    'time_keeping_setups.*',
                    'employment_types.name as employment_type_name'
                )
                ->where('time_keeping_setups.employment_type_id', $id)
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Timekeeping setup not found');
            }

            $employment_types = DB::table('employment_types')->where('active', true)->orderby('name', 'asc')->get();

            return $this->successResponse([
                'timekeeping_setup' => $data,
                'employment_types' => $employment_types
            ], 'Timekeeping setup retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve timekeeping setup: ' . $e->getMessage());
        }
    }

    /**
     * Update timekeeping setup
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'work_days' => 'required|numeric|min:0',
                'work_hours' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $timekeeping_setup = DB::table('time_keeping_setups')->where('employment_type_id', $id)->first();

            if (!$timekeeping_setup) {
                return $this->notFoundResponse('Timekeeping setup not found');
            }

            $timekeeping_data = [
                'work_days' => $request->work_days,
                'work_hours' => $request->work_hours,
                'with_holiday_pay' => $request->has('with_holiday_pay') ? true : false
            ];

            DB::table('time_keeping_setups')->where('employment_type_id', $id)->update($timekeeping_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Timekeeping Setup',
                'activity' => 'Update',
                'description' => 'Updated timekeeping setup for employment type ID: ' . $id,
            );

            Audit::create($data_audit);

            return $this->successResponse(['employment_type_id' => $id], 'Timekeeping setup updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update timekeeping setup: ' . $e->getMessage());
        }
    }

    /**
     * Delete timekeeping setup
     */
    public function destroy($id)
    {
        try {
            $timekeeping_setup = DB::table('time_keeping_setups')->where('employment_type_id', $id)->first();

            if (!$timekeeping_setup) {
                return $this->notFoundResponse('Timekeeping setup not found');
            }

            DB::table('time_keeping_setups')->where('employment_type_id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Timekeeping Setup',
                'activity' => 'Delete',
                'description' => 'Deleted timekeeping setup for employment type ID: ' . $id,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Timekeeping setup deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete timekeeping setup: ' . $e->getMessage());
        }
    }
}
