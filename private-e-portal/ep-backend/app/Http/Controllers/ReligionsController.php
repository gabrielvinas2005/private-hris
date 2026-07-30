<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Religion;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReligionsController extends Controller
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
     * Get all religions
     */
    public function index()
    {
        try {
            $data = Religion::all();

            return $this->successResponse($data, 'Religions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve religions: ' . $e->getMessage());
        }
    }

    /**
     * Show create form (for API, this returns empty data structure)
     */
    public function add()
    {
        return $this->successResponse([
            'fields' => [
                'name' => ['type' => 'text', 'required' => true],
                'active' => ['type' => 'boolean', 'required' => false]
            ]
        ], 'Create religion form structure');
    }

    /**
     * Store new religion
     */
    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|min:3|unique:religions'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $form_data = array(
                'name' => $request->name,
                'active' => $request->has('active') ? true : false,
            );

            $religion = DB::table('religions')->insert($form_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Religion Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on religion setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse($religion, 'Religion added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add religion: ' . $e->getMessage());
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        try {
            $religion = DB::table('religions')->where('id', $id)->first();

            if (!$religion) {
                return $this->notFoundResponse('Religion not found');
            }

            return $this->successResponse($religion, 'Religion retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve religion: ' . $e->getMessage());
        }
    }

    /**
     * Update religion
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|min:3'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $religion = DB::table('religions')->where('id', $id)->first();

            if (!$religion) {
                return $this->notFoundResponse('Religion not found');
            }

            $pos_data = array(
                'name' =>  $request->name,
                'active' => $request->has('active') ? true : false,
            );

            DB::table('religions')->where('id', $id)->update($pos_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Religion Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on religion setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Religion updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update religion: ' . $e->getMessage());
        }
    }

    /**
     * Show specific religion
     */
    public function show($id)
    {
        try {
            $religion = DB::table('religions')->where('id', $id)->first();

            if (!$religion) {
                return $this->notFoundResponse('Religion not found');
            }

            return $this->successResponse($religion, 'Religion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve religion: ' . $e->getMessage());
        }
    }

    /**
     * Delete religion
     */
    public function destroy($id)
    {
        try {
            $religion = DB::table('religions')->where('id', $id)->first();

            if (!$religion) {
                return $this->notFoundResponse('Religion not found');
            }

            DB::table('religions')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Religion Setup',
                'activity' => 'Delete',
                'description' => 'Deleted ' . $religion->name . ' from religion setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Religion deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete religion: ' . $e->getMessage());
        }
    }
}
