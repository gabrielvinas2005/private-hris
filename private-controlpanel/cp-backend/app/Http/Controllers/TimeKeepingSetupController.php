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
            $employment_types = DB::table('employment_types as et')
                ->leftJoin('time_keeping_setups as tk', 'tk.employment_type_id', '=', 'et.id')
                ->select(
                    'et.*',
                    'tk.enable_web_clock as tk_enable_web_clock',
                    'tk.enable_biometric as tk_enable_biometric',
                    'tk.require_selfie as tk_require_selfie',
                    'tk.enforce_geofence as tk_enforce_geofence'
                )
                ->where('et.active', true)
                ->orderby('et.name', 'asc')
                ->get();

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
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = $request->all();
            $e_id = $data['employment_type_id'];

            $timekeeping_data = [
                'enable_web_clock' => $request->boolean('enable_web_clock', true),
                'enable_biometric' => $request->boolean('enable_biometric', true),
                'require_selfie'   => $request->boolean('require_selfie', true),
                'enforce_geofence' => $request->boolean('enforce_geofence', true),
            ];

            DB::table('time_keeping_setups')->updateOrInsert(['employment_type_id' => $e_id], $timekeeping_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Timekeeping Setup',
                'activity' => 'Update',
                'description' => 'Updated timekeeping information',
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
                ->select(
                    'employment_type_id',
                    'enable_web_clock',
                    'enable_biometric',
                    'require_selfie',
                    'enforce_geofence'
                )
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
                    'enable_web_clock'   => ['type' => 'switch', 'required' => false],
                    'enable_biometric'   => ['type' => 'switch', 'required' => false],
                    'require_selfie'     => ['type' => 'switch', 'required' => false],
                    'enforce_geofence'   => ['type' => 'switch', 'required' => false]
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
            $timekeeping_setup = DB::table('time_keeping_setups')->where('employment_type_id', $id)->first();

            if (!$timekeeping_setup) {
                return $this->notFoundResponse('Timekeeping setup not found');
            }

            $timekeeping_data = [
                'enable_web_clock' => $request->boolean('enable_web_clock', true),
                'enable_biometric' => $request->boolean('enable_biometric', true),
                'require_selfie'   => $request->boolean('require_selfie', true),
                'enforce_geofence' => $request->boolean('enforce_geofence', true),
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

    /**
     * Return portal-facing feature configuration (no auth required — called by Employee Portal).
     * Returns the first active employment type's settings, or system defaults if none exist.
     * The Employee Portal uses this to enable/disable Time & Attendance features at runtime.
     */
    public function getPortalConfig()
    {
        try {
            $config = DB::table('time_keeping_setups as tk')
                ->join('employment_types as et', 'et.id', '=', 'tk.employment_type_id')
                ->where('et.active', true)
                ->select(
                    'tk.enable_web_clock',
                    'tk.enable_biometric',
                    'tk.require_selfie',
                    'tk.enforce_geofence'
                )
                ->first();

            // Return sensible defaults when no setup has been saved yet
            return $this->successResponse([
                'enable_web_clock' => (bool) ($config->enable_web_clock ?? true),
                'enable_biometric' => (bool) ($config->enable_biometric ?? true),
                'require_selfie'   => (bool) ($config->require_selfie ?? true),
                'enforce_geofence' => (bool) ($config->enforce_geofence ?? true),
            ], 'Portal timekeeping configuration retrieved');
        } catch (\Exception $e) {
            // Graceful fallback — Portal still works with defaults if DB call fails
            return $this->successResponse([
                'enable_web_clock' => true,
                'enable_biometric' => true,
                'require_selfie'   => true,
                'enforce_geofence' => true,
            ], 'Portal timekeeping configuration (default fallback)');
        }
    }
}
