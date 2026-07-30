<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Deductions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;

class DeductionController extends Controller
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
            $data = DB::table('deductions')->orderby('name', 'asc')->get();

            return $this->successResponse($data, 'Deductions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve deductions: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['name']) || !is_array($data['name'])) {
                return $this->errorResponse('Invalid data format. Expected array of deductions.');
            }

            $arr_len = count($data['name']);
            $createdCount = 0;
            $updatedCount = 0;
            $errors = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL) {

                    if ($data['id'][$i] == null || $data['id'][$i] == 0) {
                        $id = DB::table('deductions')->max('id') + 1;
                        $createdCount++;
                    } else {
                        $id = $data['id'][$i];
                        $updatedCount++;
                    }

                    $validate = Validator::make(
                        $request->all(),
                        [
                            'name.' . $i => 'required|unique:deductions,name' . ($id ? ",$id" : ''),
                        ],
                        [
                            'name.' . $i . '.unique' => 'Deduction must be unique.',
                        ],
                    );

                    if ($validate->fails()) {
                        $errors[] = 'Deduction name "' . $data['name'][$i] . '" must be unique.';
                        continue;
                    }

                    $deduction_data = [
                        'name' => $data['name'][$i],
                        'uacs' => $data['uacs'][$i],
                        'mfo_pap' => $data['mfo_pap'][$i],
                        'is_sss' => isset($data['is_sss'][$data['id'][$i]]) ? true : false,
                        'is_gsis' => isset($data['is_gsis'][$data['id'][$i]]) ? true : false,
                        'is_philhealth' => isset($data['is_philhealth'][$data['id'][$i]]) ? true : false,
                        'is_pagibig' => isset($data['is_pagibig'][$data['id'][$i]]) ? true : false,
                        'is_bank' => isset($data['is_bank'][$data['id'][$i]]) ? true : false,
                        'active' => isset($data['active'][$data['id'][$i]]) ? true : false,
                    ];

                    DB::unprepared('SET IDENTITY_INSERT deductions ON');
                    DB::table('deductions')->updateOrInsert(['id' => $id], $deduction_data);
                    DB::unprepared('SET IDENTITY_INSERT deductions OFF');
                }
            }

            if (!empty($errors)) {
                return $this->errorResponse('Validation errors occurred: ' . implode('; ', $errors));
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Deduction Types Setup',
                'activity' => 'Update',
                'description' => 'Updated deduction table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'total_processed' => $createdCount + $updatedCount
            ], 'Deductions updated successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update deductions: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('deductions')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Deduction not found');
            }

            return $this->successResponse($data, 'Deduction data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve deduction for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $deduction = DB::table('deductions')->where('id', $id)->first();

            if (!$deduction) {
                return $this->notFoundResponse('Deduction not found');
            }

            DB::table('deductions')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Deduction Types Setup',
                'activity' => 'Delete',
                'description' => 'Deleted deduction informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'deleted_id' => $id,
                'deleted_name' => $deduction->name
            ], 'Deduction deleted successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete deduction: ' . $e->getMessage());
        }
    }
}
