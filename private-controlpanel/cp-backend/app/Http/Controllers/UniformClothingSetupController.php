<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UniformClothingSetupController extends Controller
{
    use ApiResponse;

    /**
     * Get all uniform clothing setup records
     */
    public function index()
    {
        try {
            $data = DB::table('uniform_clothing_setup')->get();

            return $this->successResponse($data, 'Uniform clothing setup records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve uniform clothing setup records: ' . $e->getMessage());
        }
    }

    /**
     * Store uniform clothing setup records
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['description']) || !is_array($data['description'])) {
                return $this->errorResponse('Invalid data format. Expected array of uniform clothing records.');
            }

            $arr_len = count($data['description']);

            $uniform_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['description'][$i] != NULL) {

                    $description = $data['description'][$i];

                    if ($data['id'][$i] == 0) {
                        $id = DB::table('uniform_clothing_setup')->max('id') + 1;
                    } else {
                        $id = $data['id'][$i];
                    }

                    $description = $data['description'][$i];
                    $cloth_rate = $data['cloth_rate'][$i];
                    $year = $data['year'][$i];

                    $rules = [
                        'description.' . $i => 'required|string|unique:uniform_clothing_setup,description' . ($id ? ",$id" : ''),
                        'cloth_rate.' . $i => 'required|numeric|min:0',
                        'year.' . $i => 'required|numeric|min:0|max:9999',
                    ];

                    $messages = [
                        'description.' . $i . '.unique' => 'The clothing name field must be unique.',
                        'description.' . $i . '.required' => 'The clothing name field is required.',
                        'cloth_rate.' . $i . '.required' => 'The cloth rate field is required.',
                        'cloth_rate.' . $i . '.numeric' => 'The cloth rate field must be a number.',
                        'cloth_rate.' . $i . '.min' => 'The cloth rate field must be at least 0.',
                        'year.' . $i . '.required' => 'The year field is required.',
                        'year.' . $i . '.numeric' => 'The year field must be a number.',
                        'year.' . $i . '.min' => 'The year field must be at least 0.',
                        'year.' . $i . '.max' => 'The year field may not be greater than 9999.',
                    ];

                    $validator = Validator::make($data, $rules, $messages);

                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    $uniform_data = [
                        'description' => $description,
                        'cloth_rate' => $cloth_rate,
                        'year' => $year,
                    ];

                    DB::table('uniform_clothing_setup')->updateOrInsert(['id' => $id], $uniform_data);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Uniform & Clothing Setup',
                'activity' => 'Update',
                'description' => 'Updated Uniform & Clothing table information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Uniform & clothing table updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update uniform & clothing table: ' . $e->getMessage());
        }
    }

    /**
     * Get uniform clothing setup record for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('uniform_clothing_setup')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Uniform clothing setup record not found');
            }

            return $this->successResponse($data, 'Uniform clothing setup record retrieved for deletion');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve uniform clothing setup record: ' . $e->getMessage());
        }
    }

    /**
     * Delete uniform clothing setup record
     */
    public function destroy($id)
    {
        try {
            $record = DB::table('uniform_clothing_setup')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Uniform clothing setup record not found');
            }

            DB::table('uniform_clothing_setup')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Uniform & Clothing Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Uniform & Clothing information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Uniform clothing setup record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete uniform clothing setup record: ' . $e->getMessage());
        }
    }

    /**
     * Show specific uniform clothing setup record
     */
    public function show($id)
    {
        try {
            $data = DB::table('uniform_clothing_setup')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Uniform clothing setup record not found');
            }

            return $this->successResponse($data, 'Uniform clothing setup record retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve uniform clothing setup record: ' . $e->getMessage());
        }
    }

    /**
     * Create new uniform clothing setup form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'description' => ['type' => 'text', 'required' => true],
                    'cloth_rate' => ['type' => 'number', 'required' => true],
                    'year' => ['type' => 'number', 'required' => true]
                ]
            ], 'Create uniform clothing setup form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit uniform clothing setup form data
     */
    public function edit($id)
    {
        try {
            $data = DB::table('uniform_clothing_setup')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Uniform clothing setup record not found');
            }

            return $this->successResponse($data, 'Uniform clothing setup record retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve uniform clothing setup record: ' . $e->getMessage());
        }
    }

    /**
     * Update uniform clothing setup record
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'description' => 'required|string|unique:uniform_clothing_setup,description,' . $id,
                'cloth_rate' => 'required|numeric|min:0',
                'year' => 'required|numeric|min:0|max:9999',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $record = DB::table('uniform_clothing_setup')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Uniform clothing setup record not found');
            }

            $uniform_data = [
                'description' => $request->description,
                'cloth_rate' => $request->cloth_rate,
                'year' => $request->year,
            ];

            DB::table('uniform_clothing_setup')->where('id', $id)->update($uniform_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Uniform & Clothing Setup',
                'activity' => 'Update',
                'description' => 'Updated Uniform & Clothing: ' . $request->description,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Uniform clothing setup record updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update uniform clothing setup record: ' . $e->getMessage());
        }
    }
}
