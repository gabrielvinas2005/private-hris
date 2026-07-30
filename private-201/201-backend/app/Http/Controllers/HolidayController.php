<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HolidayController extends Controller
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
     * Get all holidays
     */
    public function index()
    {
        try {
            $data = DB::table('holidays')->orderby('name', 'asc')->get();
            $HolidayType = DB::table('holiday_types')->where('active', true)->orderby('name', 'asc')->get();
            $branches = DB::table('branches')->orderBy('name', 'asc')->get();

            return $this->successResponse([
                'holidays' => $data,
                'holiday_types' => $HolidayType,
                'branches' => $branches
            ], 'Holidays retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve holidays: ' . $e->getMessage());
        }
    }

    /**
     * Store holidays
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['name']) || !is_array($data['name'])) {
                return $this->errorResponse('Invalid data format. Expected array of holidays.');
            }

            $arr_len = count($data['name']);

            $holiday_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL) {

                    if (isset($data['active']) && in_array($data['id'][$i], $data['active'])) {
                        $active = true;
                    } else {
                        $active = false;
                    }

                    if ($data['id'][$i] == null || $data['id'][$i] == 0) {

                        $is_exist = DB::table('holidays')
                            ->where([
                                'name' => $data['name'][$i],
                                'branch' => isset($data['branch_id'][$i]) ? $data['branch_id'][$i] : 0,
                            ])
                            ->get();

                        if ($is_exist->isNotEmpty()) {
                            return $this->errorResponse('"' . $data['name'][$i] . '" already exists.');
                        }
                        $id = DB::table('holidays')->max('id') + 1;
                    } else {
                        $id = $data['id'][$i];
                    }

                    $holiday_data = [
                        'name' => $data['name'][$i],
                        'holiday_type' => isset($data['holiday_type'][$i]) ? $data['holiday_type'][$i] : 0,
                        'branch' => isset($data['branch_id'][$i]) ? $data['branch_id'][$i] : 0,
                        'date' => isset($data['date'][$i]) ? $data['date'][$i] : null,
                        'active' => $active,
                    ];

                    DB::unprepared('SET IDENTITY_INSERT holidays ON');
                    DB::table('holidays')->updateOrInsert(['id' => $id], $holiday_data);
                    DB::unprepared('SET IDENTITY_INSERT holidays OFF');
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Holiday Setup',
                'activity' => 'Update',
                'description' => 'Updated holiday table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Holidays updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update holidays: ' . $e->getMessage());
        }
    }

    /**
     * Get holiday for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('holidays')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Holiday not found');
            }

            return $this->successResponse($data, 'Holiday data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve holiday: ' . $e->getMessage());
        }
    }

    /**
     * Delete holiday
     */
    public function destroy($id)
    {
        try {
            $holiday = DB::table('holidays')->where('id', $id)->first();

            if (!$holiday) {
                return $this->notFoundResponse('Holiday not found');
            }

            DB::table('holidays')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Holiday Setup',
                'activity' => 'Delete',
                'description' => 'Deleted holiday informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Holiday deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete holiday: ' . $e->getMessage());
        }
    }

    /**
     * Show specific holiday
     */
    public function show($id)
    {
        try {
            $data = DB::table('holidays')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Holiday not found');
            }

            return $this->successResponse($data, 'Holiday retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve holiday: ' . $e->getMessage());
        }
    }

    /**
     * Create new holiday form data
     */
    public function create()
    {
        try {
            $holiday_types = DB::table('holiday_types')->where('active', true)->orderby('name', 'asc')->get();
            $branches = DB::table('branches')->orderBy('name', 'asc')->get();

            return $this->successResponse([
                'holiday_types' => $holiday_types,
                'branches' => $branches,
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'holiday_type' => ['type' => 'select', 'required' => false],
                    'branch' => ['type' => 'select', 'required' => false],
                    'date' => ['type' => 'date', 'required' => false],
                    'active' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Create holiday form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit holiday form data
     */
    public function edit($id)
    {
        try {
            $data = DB::table('holidays')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Holiday not found');
            }

            $holiday_types = DB::table('holiday_types')->where('active', true)->orderby('name', 'asc')->get();
            $branches = DB::table('branches')->orderBy('name', 'asc')->get();

            return $this->successResponse([
                'holiday' => $data,
                'holiday_types' => $holiday_types,
                'branches' => $branches
            ], 'Holiday retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve holiday: ' . $e->getMessage());
        }
    }

    /**
     * Update holiday
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|unique:holidays,name,' . $id,
                'date' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $holiday = DB::table('holidays')->where('id', $id)->first();

            if (!$holiday) {
                return $this->notFoundResponse('Holiday not found');
            }

            $holiday_data = [
                'name' => $request->name,
                'holiday_type' => $request->holiday_type ?? 0,
                'branch' => $request->branch ?? 0,
                'date' => $request->date,
                'active' => $request->has('active') ? true : false,
            ];

            DB::table('holidays')->where('id', $id)->update($holiday_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Holiday Setup',
                'activity' => 'Update',
                'description' => 'Updated holiday: ' . $request->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Holiday updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update holiday: ' . $e->getMessage());
        }
    }
}
