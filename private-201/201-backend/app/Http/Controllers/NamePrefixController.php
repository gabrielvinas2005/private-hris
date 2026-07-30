<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\NamePrefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NamePrefixController extends Controller
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
            $data = NamePrefix::all();

            return $this->successResponse($data, 'Name prefixes retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve name prefixes: ' . $e->getMessage());
        }
    }

    public function add()
    {
        try {
            return $this->successResponse([], 'Name prefix form loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load name prefix form: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:name_prefixes'
            ]);

        $id = DB::table('name_prefixes')->max('id') + 1;
        $code = "EXT$id";

        $form_data = array(
            'code' => $code,
            'name' => $request->name,
            'active' => $request->has('active') ? true : false,
        );

        DB::table('name_prefixes')->insert($form_data);

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Control Panel',
            'menu'    => 'Name Prefix Setup',
            'activity' => 'Add',
            'description' => 'Added ' . $request->name . ' on name prefix setup.',
        );

        Audit::create($data_audit);

        return $this->successResponse(['id' => $id], 'Name prefix added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add name prefix: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $name_prefix = DB::table('name_prefixes')->where('id', $id)->get();

            if ($name_prefix->isEmpty()) {
                return $this->notFoundResponse('Name prefix not found.');
            }

            return $this->successResponse($name_prefix, 'Name prefix data for editing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve name prefix for editing: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:name_prefixes,name,' . $id,
            ]);

        $pos_data = array(
            'name' =>  $request->name,
            'active' => $request->has('active') ? true : false,
        );

        DB::table('name_prefixes')->where('id', $id)->update($pos_data);

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Control Panel',
            'menu'    => 'Name Prefix Setup',
            'activity' => 'Update',
            'description' => 'Updated ' . $request->name . ' on name prefix setup.',
        );

        Audit::create($data_audit);

        return $this->successResponse(['id' => $id], 'Name prefix updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update name prefix: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            DB::table('name_prefixes')->where('id', $id)->delete();

            return $this->successResponse(['id' => $id], 'Name prefix deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete name prefix: ' . $e->getMessage());
        }
    }
}
