<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Eligibility;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EligibilityController extends Controller
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
            $data = Eligibility::all();

            return $this->successResponse($data, 'Eligibilities retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve eligibilities: ' . $e->getMessage());
        }
    }

    public function add()
    {
        try {
            return $this->successResponse([], 'Eligibility form loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load eligibility form: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|min:3|max:255|unique:eligibilities'
            ], [
                'name.required' => 'Eligibility name is required.',
                'name.string' => 'Eligibility name must be a string.',
                'name.min' => 'Eligibility name must be at least 3 characters.',
                'name.max' => 'Eligibility name cannot exceed 255 characters.',
                'name.unique' => 'Eligibility name has already been taken.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $form_data = array(
                'name' => $request->name,
                'active' => $request->has('active') ? true : false,
            );

            $eligibility = Eligibility::create($form_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Eligibility Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on eligibility setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $eligibility->id], 'Eligibility added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add eligibility: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $eligibility = DB::table('eligibilities')->where('id', $id)->first();

            if (!$eligibility) {
                return $this->notFoundResponse('Eligibility not found');
            }

            return $this->successResponse($eligibility, 'Eligibility data for editing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load eligibility edit data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|min:3|max:255|unique:eligibilities,name,' . $id
            ], [
                'name.required' => 'Eligibility name is required.',
                'name.string' => 'Eligibility name must be a string.',
                'name.min' => 'Eligibility name must be at least 3 characters.',
                'name.max' => 'Eligibility name cannot exceed 255 characters.',
                'name.unique' => 'Eligibility name has already been taken.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $eligibility = DB::table('eligibilities')->where('id', $id)->first();

            if (!$eligibility) {
                return $this->notFoundResponse('Eligibility not found');
            }

            $pos_data = array(
                'name' =>  $request->name,
                'active' => $request->has('active') ? true : false,
            );

            DB::table('eligibilities')->where('id', $id)->update($pos_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Eligibility Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on eligibility setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Eligibility updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update eligibility: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $eligibility = Eligibility::find($id);

            if (!$eligibility) {
                return $this->notFoundResponse('Eligibility not found');
            }

            return $this->successResponse($eligibility, 'Eligibility retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve eligibility: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true, 'label' => 'Eligibility Name'],
                    'active' => ['type' => 'checkbox', 'required' => false, 'label' => 'Active Status']
                ]
            ], 'Create eligibility form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $eligibility = DB::table('eligibilities')->where('id', $id)->first();

            if (!$eligibility) {
                return $this->notFoundResponse('Eligibility not found');
            }

            DB::table('eligibilities')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Eligibility Setup',
                'activity' => 'Delete',
                'description' => 'Deleted eligibility: ' . $eligibility->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(['deleted_id' => $id, 'deleted_name' => $eligibility->name], 'Eligibility deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete eligibility: ' . $e->getMessage());
        }
    }
}
