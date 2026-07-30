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

                    $isSSS = false;
                    if (isset($data['is_sss']) && is_array($data['is_sss'])) {
                        if (isset($data['is_sss'][$i])) {
                            $isSSS = filter_var($data['is_sss'][$i], FILTER_VALIDATE_BOOLEAN);
                        } elseif (isset($data['is_sss'][$data['id'][$i]])) {
                            $isSSS = filter_var($data['is_sss'][$data['id'][$i]], FILTER_VALIDATE_BOOLEAN);
                        }
                    }

                    $isGSIS = false;
                    if (isset($data['is_gsis']) && is_array($data['is_gsis'])) {
                        if (isset($data['is_gsis'][$i])) {
                            $isGSIS = filter_var($data['is_gsis'][$i], FILTER_VALIDATE_BOOLEAN);
                        } elseif (isset($data['is_gsis'][$data['id'][$i]])) {
                            $isGSIS = filter_var($data['is_gsis'][$data['id'][$i]], FILTER_VALIDATE_BOOLEAN);
                        }
                    }

                    $isPH = false;
                    if (isset($data['is_philhealth']) && is_array($data['is_philhealth'])) {
                        if (isset($data['is_philhealth'][$i])) {
                            $isPH = filter_var($data['is_philhealth'][$i], FILTER_VALIDATE_BOOLEAN);
                        } elseif (isset($data['is_philhealth'][$data['id'][$i]])) {
                            $isPH = filter_var($data['is_philhealth'][$data['id'][$i]], FILTER_VALIDATE_BOOLEAN);
                        }
                    }

                    $isPagibig = false;
                    if (isset($data['is_pagibig']) && is_array($data['is_pagibig'])) {
                        if (isset($data['is_pagibig'][$i])) {
                            $isPagibig = filter_var($data['is_pagibig'][$i], FILTER_VALIDATE_BOOLEAN);
                        } elseif (isset($data['is_pagibig'][$data['id'][$i]])) {
                            $isPagibig = filter_var($data['is_pagibig'][$data['id'][$i]], FILTER_VALIDATE_BOOLEAN);
                        }
                    }

                    $isBank = false;
                    if (isset($data['is_bank']) && is_array($data['is_bank'])) {
                        if (isset($data['is_bank'][$i])) {
                            $isBank = filter_var($data['is_bank'][$i], FILTER_VALIDATE_BOOLEAN);
                        } elseif (isset($data['is_bank'][$data['id'][$i]])) {
                            $isBank = filter_var($data['is_bank'][$data['id'][$i]], FILTER_VALIDATE_BOOLEAN);
                        }
                    }

                    $isActive = false;
                    if (isset($data['active']) && is_array($data['active'])) {
                        if (isset($data['active'][$i])) {
                            $isActive = filter_var($data['active'][$i], FILTER_VALIDATE_BOOLEAN);
                        } elseif (isset($data['active'][$data['id'][$i]])) {
                            $isActive = filter_var($data['active'][$data['id'][$i]], FILTER_VALIDATE_BOOLEAN);
                        }
                    }

                    $deduction_data = [
                        'name' => $data['name'][$i],
                        'uacs' => $data['uacs'][$i],
                        'mfo_pap' => $data['mfo_pap'][$i],
                        'is_sss' => $isSSS,
                        'is_gsis' => $isGSIS,
                        'is_philhealth' => $isPH,
                        'is_pagibig' => $isPagibig,
                        'is_bank' => $isBank,
                        'active' => $isActive,
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
                'description' => 'Updated deduction table information',
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
                'description' => 'Deleted deduction information',
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
