<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Learning;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningsController extends Controller
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
            $data = Learning::all();

            return $this->successResponse($data, 'Learning records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve learning records: ' . $e->getMessage());
        }
    }

    public function add()
    {
        try {
            return $this->successResponse([], 'Learning form loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load learning form: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|min:3|unique:learnings'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $form_data = array(
                'name' => $request->name,
                'active' => $request->has('active') ? true : false,
            );

            $id = DB::table('learnings')->insertGetId($form_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Learning Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on learning setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Learning added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add learning: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $learning = DB::table('learnings')->where('id', $id)->first();

            if (!$learning) {
                return $this->notFoundResponse('Learning not found');
            }

            return $this->successResponse($learning, 'Learning data for editing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve learning data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|min:3'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $learning = DB::table('learnings')->where('id', $id)->first();

            if (!$learning) {
                return $this->notFoundResponse('Learning not found');
            }

            $pos_data = array(
                'name' =>  $request->name,
                'active' => $request->has('active') ? true : false,
            );

            DB::table('learnings')->where('id', $id)->update($pos_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Learning Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on learning setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Learning updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update learning: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $learning = DB::table('learnings')->where('id', $id)->first();

            if (!$learning) {
                return $this->notFoundResponse('Learning not found');
            }

            DB::table('learnings')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Learning Setup',
                'activity' => 'Delete',
                'description' => 'Deleted learning: ' . $learning->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Learning deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete learning: ' . $e->getMessage());
        }
    }
}
