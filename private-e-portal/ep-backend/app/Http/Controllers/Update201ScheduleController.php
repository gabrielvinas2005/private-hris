<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Update201ScheduleController extends Controller
{
    use ApiResponse;

    /**
     * Get all update 201 schedules
     */
    public function index()
    {
        try {
            $schedules = DB::table('update_201_schedule')->get();

            if ($schedules->isEmpty()) {
                $schedules_dummy = array(
                    'id' => 0,
                    'date_from' => '',
                    'date_to' => '',
                );

                $schedules = (object)$schedules_dummy;
                $schedules = collect([$schedules]);
            }

            return $this->successResponse($schedules, 'Update 201 schedules retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve update 201 schedules: ' . $e->getMessage());
        }
    }

    /**
     * Store update 201 schedule
     */
    public function store(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'date_from' => 'required|date',
                'date_to' => 'required|date'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->date_to < $request->date_from) {
                return $this->errorResponse('Invalid Date Range.');
            }

            $data = [
                'date_from' => $request->date_from,
                'date_to' => $request->date_to
            ];

            if ($id == 0) {
                DB::table('update_201_schedule')->insert($data);
                $message = 'Update 201 schedule created successfully';
            } else {
                DB::table('update_201_schedule')->where('id', $id)->update($data);
                $message = 'Update 201 schedule updated successfully';
            }

            return $this->successResponse(['id' => $id], $message);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save update 201 schedule: ' . $e->getMessage());
        }
    }

    /**
     * Show specific update 201 schedule
     */
    public function show($id)
    {
        try {
            $schedule = DB::table('update_201_schedule')->where('id', $id)->first();

            if (!$schedule) {
                return $this->notFoundResponse('Update 201 schedule not found');
            }

            return $this->successResponse($schedule, 'Update 201 schedule retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve update 201 schedule: ' . $e->getMessage());
        }
    }

    /**
     * Create new update 201 schedule form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'date_from' => ['type' => 'date', 'required' => true],
                    'date_to' => ['type' => 'date', 'required' => true]
                ]
            ], 'Create update 201 schedule form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit update 201 schedule form data
     */
    public function edit($id)
    {
        try {
            $schedule = DB::table('update_201_schedule')->where('id', $id)->first();

            if (!$schedule) {
                return $this->notFoundResponse('Update 201 schedule not found');
            }

            return $this->successResponse($schedule, 'Update 201 schedule retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve update 201 schedule: ' . $e->getMessage());
        }
    }

    /**
     * Update update 201 schedule
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'date_from' => 'required|date',
                'date_to' => 'required|date'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->date_to < $request->date_from) {
                return $this->errorResponse('Invalid Date Range.');
            }

            $schedule = DB::table('update_201_schedule')->where('id', $id)->first();

            if (!$schedule) {
                return $this->notFoundResponse('Update 201 schedule not found');
            }

            $data = [
                'date_from' => $request->date_from,
                'date_to' => $request->date_to
            ];

            DB::table('update_201_schedule')->where('id', $id)->update($data);

            return $this->successResponse(['id' => $id], 'Update 201 schedule updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update schedule: ' . $e->getMessage());
        }
    }

    /**
     * Delete update 201 schedule
     */
    public function destroy($id)
    {
        try {
            $schedule = DB::table('update_201_schedule')->where('id', $id)->first();

            if (!$schedule) {
                return $this->notFoundResponse('Update 201 schedule not found');
            }

            DB::table('update_201_schedule')->where('id', $id)->delete();

            return $this->successResponse(null, 'Update 201 schedule deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete update 201 schedule: ' . $e->getMessage());
        }
    }
}
