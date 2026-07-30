<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\NameSuffix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NameSuffixController extends Controller
{
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
            $data = NameSuffix::all();

            return $this->successResponse($data, 'Name suffixes retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve name suffixes: ' . $e->getMessage());
        }
    }

    public function add()
    {
        try {
            return $this->successResponse([], 'Name suffix form loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load name suffix form: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:name_suffixes'
            ]);

        $form_data = array(
            'name' => $request->name,
            'active' => $request->has('active') ? true : false,
        );

        DB::table('name_suffixes')->insert($form_data);

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Control Panel',
            'menu'    => 'Name Suffix Setup',
            'activity' => 'Add',
            'description' => 'Added ' . $request->name . ' on name suffix setup.',
        );

        Audit::create($data_audit);

        return $this->successResponse(['id' => DB::getPdo()->lastInsertId()], 'Name suffix added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add name suffix: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $name_suffix = DB::table('name_suffixes')->where('id', $id)->get();

            if ($name_suffix->isEmpty()) {
                return $this->notFoundResponse('Name suffix not found.');
            }

            return $this->successResponse($name_suffix, 'Name suffix data for editing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve name suffix for editing: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string'
            ]);

        $pos_data = array(
            'name' =>  $request->name,
            'active' => $request->has('active') ? true : false,
        );

        DB::table('name_suffixes')->where('id', $id)->update($pos_data);

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Control Panel',
            'menu'    => 'Name Suffix Setup',
            'activity' => 'Update',
            'description' => 'Updated ' . $request->name . ' on name suffix setup.',
        );

        Audit::create($data_audit);

        return $this->successResponse(['id' => $id], 'Name suffix updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update name suffix: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            DB::table('name_suffixes')->where('id', $id)->delete();

            return $this->successResponse(['id' => $id], 'Name suffix deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete name suffix: ' . $e->getMessage());
        }
    }
}
