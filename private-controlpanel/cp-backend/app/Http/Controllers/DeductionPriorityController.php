<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class DeductionPriorityController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $data = DB::table('deduction_priorities')->orderBy('priority', 'asc')->get();

            return $this->successResponse($data, 'Deduction priorities retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve deduction priorities: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['name']) || !is_array($data['name'])) {
                return $this->errorResponse('Invalid data format. Expected array of deduction priorities.');
            }

            $arr_len = count($data['name']);
            $createdCount = 0;
            $updatedCount = 0;
            $errors = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL) {

                    if ($data['id'][$i] == null || $data['id'][$i] == 0) {
                        $id = 0 + DB::table('deduction_priorities')->max('id');
                        $id += 1;
                        $createdCount++;
                    } else {
                        $id = $data['id'][$i];
                        $updatedCount++;
                    }

                    // Validate priority value
                    if (!is_numeric($data['priority'][$i]) || $data['priority'][$i] < 1) {
                        $errors[] = 'Priority must be a positive number for deduction "' . $data['name'][$i] . '".';
                        continue;
                    }

                    // Check for duplicate priorities
                    $existingPriority = DB::table('deduction_priorities')
                        ->where('priority', $data['priority'][$i])
                        ->where('id', '!=', $id)
                        ->first();

                    if ($existingPriority) {
                        $errors[] = 'Priority ' . $data['priority'][$i] . ' is already assigned to another deduction.';
                        continue;
                    }

                    $deduction_data = [
                        'deduction' => $data['deduction'][$i],
                        'priority' => $data['priority'][$i]
                    ];

                    DB::unprepared('SET IDENTITY_INSERT deduction_priorities ON');
                    DB::table('deduction_priorities')->updateOrInsert(['id' => $id], $deduction_data);
                    DB::unprepared('SET IDENTITY_INSERT deduction_priorities OFF');
                }
            }

            if (!empty($errors)) {
                return $this->errorResponse('Validation errors occurred: ' . implode('; ', $errors));
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Deduction Priority Setup',
                'activity' => 'Update',
                'description' => 'Updated deduction priority table information',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'total_processed' => $createdCount + $updatedCount
            ], 'Deduction priorities saved successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save deduction priorities: ' . $e->getMessage());
        }
    }
}
