<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Gender;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GendersController extends Controller
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
            $data = Gender::all();

            return $this->successResponse($data, 'Genders retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve genders: ' . $e->getMessage());
        }
    }

    public function add()
    {
        try {
            return $this->successResponse([], 'Gender form loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load gender form: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|min:3|unique:genders'
            ]);

            $form_data = array(
                'name' => $request->name,
                'active' => $request->has('active') ? true : false,
            );

            $id = DB::table('genders')->insertGetId($form_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Gender Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on gender setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Gender added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add gender: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $gender = DB::table('genders')->where('id', $id)->first();

            if (!$gender) {
                return $this->notFoundResponse('Gender not found');
            }

            return $this->successResponse($gender, 'Gender data for editing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve gender data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|min:3'
            ]);

            $gender = DB::table('genders')->where('id', $id)->first();

            if (!$gender) {
                return $this->notFoundResponse('Gender not found');
            }

            $pos_data = array(
                'name' =>  $request->name,
                'active' => $request->has('active') ? true : false,
            );

            DB::table('genders')->where('id', $id)->update($pos_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Gender Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on gender setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Gender updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update gender: ' . $e->getMessage());
        }
    }
}
