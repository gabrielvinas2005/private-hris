<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\CivilStatus;
use Dotenv\Validator;
use FontLib\Table\Type\name;
use Illuminate\Auth\Events\Validated;
use Illuminate\Contracts\Validation\Validator as ContractsValidationValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Traits\ApiResponse;

class CivilStatusController extends Controller
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
            $data = DB::table('civil_status')->get();

            return $this->successResponse($data, 'Civil statuses retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve civil statuses: ' . $e->getMessage());
        }
    }

    public function add()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true, 'min_length' => 3],
                    'active' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Civil status form loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load civil status form: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'min:3',
                    'unique:civil_status',
                    // Rule::in(['Single', 'Married', 'Widowed', 'Divorced'])
                ]
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $id = DB::table('civil_status')->max('id') + 1;
            $code = "CS$id";

            $form_data = [
                'code' => $code,
                'name' => $request->name,
                'active' => $request->has('active') ? true : false,
            ];

            $civil_status = DB::table('civil_status')->insertGetId($form_data);

            //Save audit trail
            $data_audit = [
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Civil Status',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on civil status.',
            ];

            Audit::create($data_audit);

            return $this->successResponse([
                'id' => $civil_status,
                'code' => $code,
                'name' => $request->name,
                'active' => $request->has('active') ? true : false
            ], 'Civil status added successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add civil status: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $civil_status = DB::table('civil_status')->where('id', $id)->first();

            if (!$civil_status) {
                return $this->notFoundResponse('Civil status not found');
            }

            return $this->successResponse($civil_status, 'Civil status data for editing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve civil status for editing: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $civil_status = DB::table('civil_status')->where('id', $id)->first();

            if (!$civil_status) {
                return $this->notFoundResponse('Civil status not found');
            }

            $validator = validator($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'min:3',
                    'unique:civil_status,name,' . $id
                    // Rule::in(['Single', 'Married', 'Widowed', 'Divorced'])
                ]
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $pos_data = [
                'name' =>  $request->name,
                'active' => $request->has('active') ? true : false,
            ];

            DB::table('civil_status')->where('id', $id)->update($pos_data);

            //Save audit trail
            $data_audit = [
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Civil Status',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on civil status.',
            ];

            Audit::create($data_audit);

            return $this->successResponse([
                'id' => $id,
                'code' => $civil_status->code,
                'name' => $request->name,
                'active' => $request->has('active') ? true : false
            ], 'Civil status updated successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update civil status: ' . $e->getMessage());
        }
    }
}
