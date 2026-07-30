<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Citizenship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class CitizenshipsController extends Controller
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
            $data = Citizenship::all();

            return $this->successResponse($data, 'Citizenships retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve citizenships: ' . $e->getMessage());
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
            ], 'Citizenship form loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load citizenship form: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|min:3|unique:citizenships'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $form_data = array(
                'name' => $request->name,
                'active' => $request->has('active') ? true : false,
            );

            $citizenship = DB::table('citizenships')->insertGetId($form_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Citizenships',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on citizenships.',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'id' => $citizenship,
                'name' => $request->name,
                'active' => $request->has('active') ? true : false
            ], 'Citizenship added successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add citizenship: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $citizen = DB::table('citizenships')->where('id', $id)->first();

            if (!$citizen) {
                return $this->notFoundResponse('Citizenship not found');
            }

            return $this->successResponse($citizen, 'Citizenship data for editing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve citizenship for editing: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $citizenship = DB::table('citizenships')->where('id', $id)->first();

            if (!$citizenship) {
                return $this->notFoundResponse('Citizenship not found');
            }

            $validator = validator($request->all(), [
                'name' => 'required|string|min:3|unique:citizenships,name,' . $id
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $pos_data = array(
                'name' =>  $request->name,
                'active' => $request->has('active') ? true : false,
            );

            DB::table('citizenships')->where('id', $id)->update($pos_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Citizenship Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on citizenship setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'id' => $id,
                'name' => $request->name,
                'active' => $request->has('active') ? true : false
            ], 'Citizenship updated successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update citizenship: ' . $e->getMessage());
        }
    }
}
