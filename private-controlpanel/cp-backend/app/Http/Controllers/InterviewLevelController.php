<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InterviewLevelController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        // Keep consistent with other HR setup controllers in this project
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $data = DB::table('interview_levels')->orderBy('id', 'asc')->get();
            return $this->successResponse($data, 'Interview levels retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve interview levels: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $level = DB::table('interview_levels')->where('id', $id)->first();

            if (!$level) {
                return $this->notFoundResponse('Interview level not found');
            }

            return $this->successResponse($level, 'Interview level data for editing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load interview level edit data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'interview_level' => 'required|string|min:2|max:255|unique:interview_levels,interview_level',
                'active' => 'nullable'
            ], [
                'interview_level.required' => 'Interview level is required.',
                'interview_level.min' => 'Interview level must be at least 2 characters.',
                'interview_level.max' => 'Interview level cannot exceed 255 characters.',
                'interview_level.unique' => 'Interview level has already been taken.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $active = filter_var($request->input('active', false), FILTER_VALIDATE_BOOLEAN);

            $id = DB::table('interview_levels')->insertGetId([
                'interview_level' => $request->interview_level,
                'active' => $active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Audit::create([
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Interview Setup',
                'activity' => 'Add',
                'description' => 'Added interview level: ' . $request->interview_level,
            ]);

            return $this->successResponse(['id' => $id], 'Interview level added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add interview level: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'interview_level' => 'required|string|min:2|max:255|unique:interview_levels,interview_level,' . $id,
                'active' => 'nullable'
            ], [
                'interview_level.required' => 'Interview level is required.',
                'interview_level.min' => 'Interview level must be at least 2 characters.',
                'interview_level.max' => 'Interview level cannot exceed 255 characters.',
                'interview_level.unique' => 'Interview level has already been taken.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $level = DB::table('interview_levels')->where('id', $id)->first();
            if (!$level) {
                return $this->notFoundResponse('Interview level not found');
            }

            $active = filter_var($request->input('active', false), FILTER_VALIDATE_BOOLEAN);

            DB::table('interview_levels')->where('id', $id)->update([
                'interview_level' => $request->interview_level,
                'active' => $active,
                'updated_at' => now(),
            ]);

            Audit::create([
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Interview Setup',
                'activity' => 'Update',
                'description' => 'Updated interview level: ' . $request->interview_level,
            ]);

            return $this->successResponse(['id' => $id], 'Interview level updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update interview level: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $level = DB::table('interview_levels')->where('id', $id)->first();

            if (!$level) {
                return $this->notFoundResponse('Interview level not found');
            }

            DB::table('interview_levels')->where('id', $id)->delete();

            Audit::create([
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Interview Setup',
                'activity' => 'Delete',
                'description' => 'Deleted interview level: ' . $level->interview_level,
            ]);

            return $this->successResponse(['deleted_id' => $id], 'Interview level deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete interview level: ' . $e->getMessage());
        }
    }
}

