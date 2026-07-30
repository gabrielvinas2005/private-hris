<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\EmploymentType;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmploymentTypesController extends Controller
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
     * Get all employment types
     */
    public function index()
    {
        try {
            $data = EmploymentType::all();

            return $this->successResponse($data, 'Employment types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employment types: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for adding employment type
     */
    public function add()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'with_end_contract' => ['type' => 'checkbox', 'required' => false],
                    'active' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Employment type form loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employment type form: ' . $e->getMessage());
        }
    }

    /**
     * Store employment type
     */
    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|min:3|unique:employment_types'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $form_data = array(
                'name' => $request->name,
                'with_end_contract' => $request->has('with_end_contract') ? true : false,
                'active' => $request->has('active') ? true : false,
            );

            $employment_type = EmploymentType::create($form_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Employment Types',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on employment type.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $employment_type->id], 'Employment type added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add employment type: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for editing employment type
     */
    public function edit($id)
    {
        try {
            $employments = DB::table('employment_types')->where('id', $id)->first();

            if (!$employments) {
                return $this->notFoundResponse('Employment type not found');
            }

            return $this->successResponse($employments, 'Employment type data for editing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employment type: ' . $e->getMessage());
        }
    }

    /**
     * Update employment type
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|min:3|unique:employment_types,name,' . $id
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $employment_type = DB::table('employment_types')->where('id', $id)->first();

            if (!$employment_type) {
                return $this->notFoundResponse('Employment type not found');
            }

            $pos_data = array(
                'name' =>  $request->name,
                'with_end_contract' => $request->has('with_end_contract') ? true : false,
                'active' => $request->has('active') ? true : false,
            );

            DB::table('employment_types')->where('id', $id)->update($pos_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Employment Type',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on employment type.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Employment type updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update employment type: ' . $e->getMessage());
        }
    }

    /**
     * Show specific employment type
     */
    public function show($id)
    {
        try {
            $data = DB::table('employment_types')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Employment type not found');
            }

            return $this->successResponse($data, 'Employment type retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employment type: ' . $e->getMessage());
        }
    }

    /**
     * Create new employment type form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'with_end_contract' => ['type' => 'checkbox', 'required' => false],
                    'active' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Create employment type form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Delete employment type
     */
    public function destroy($id)
    {
        try {
            $employment_type = DB::table('employment_types')->where('id', $id)->first();

            if (!$employment_type) {
                return $this->notFoundResponse('Employment type not found');
            }

            DB::table('employment_types')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Employment Type',
                'activity' => 'Delete',
                'description' => 'Deleted employment type: ' . $employment_type->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Employment type deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete employment type: ' . $e->getMessage());
        }
    }
}
