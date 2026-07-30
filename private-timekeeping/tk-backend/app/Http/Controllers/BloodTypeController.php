<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\BloodType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class BloodTypeController extends Controller
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
            $data = DB::table('blood_types')->orderby('id', 'asc')->get();

            return $this->successResponse($data, 'Blood types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve blood types: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['name']) || !is_array($data['name'])) {
                return $this->errorResponse('Blood type names are required and must be an array.');
            }

            $arr_len = count($data['name']);
            $allowedBloodTypes = ['A', 'A+', 'A-', 'B+', 'B-', 'B', 'O+', 'O-', 'O', 'AB+', 'AB-', 'AB'];
            $createdCount = 0;
            $updatedCount = 0;
            $errors = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL) {
                    // Check if the blood type is allowed
                    if (!in_array($data['name'][$i], $allowedBloodTypes)) {
                        $errors[] = 'Unknown blood type: ' . $data['name'][$i] . '. Input must be of A, B, AB, O positive (+) or negative (-) blood type.';
                        continue;
                    }

                    // Check if the blood type already exists only when adding a new one
                    if ($data['id'][$i] == null || $data['id'][$i] == 0) {
                        $existingBloodType = DB::table('blood_types')->where('name', '=', $data['name'][$i])->get();

                        if ($existingBloodType->isNotEmpty()) {
                            $errors[] = 'Blood type ' . $data['name'][$i] . ' already exists.';
                            continue;
                        }

                        // Generate a new ID
                        $id = DB::table('blood_types')->max('id') + 1;

                        $blood_type_data = [
                            'id' => $id,
                            'name' => $data['name'][$i],
                        ];

                        DB::unprepared('SET IDENTITY_INSERT blood_types ON');
                        DB::table('blood_types')->insert($blood_type_data);
                        DB::unprepared('SET IDENTITY_INSERT blood_types OFF');
                        $createdCount++;
                    } else {
                        $blood_type_data = [
                            'name' => $data['name'][$i],
                        ];

                        DB::table('blood_types')->where('id', $data['id'][$i])->update($blood_type_data);
                        $updatedCount++;
                    }
                }
            }

            if (!empty($errors)) {
                return $this->errorResponse('Validation errors occurred: ' . implode('; ', $errors));
            }

            // Save audit trail
            $data_audit = [
                'user_id' => Auth::user()->id,
                'module' => 'Control Panel',
                'menu' => 'Blood Type Setup',
                'activity' => 'Update',
                'description' => 'Updated Blood Type informations.',
            ];

            Audit::create($data_audit);

            return $this->successResponse([
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'total_processed' => $createdCount + $updatedCount
            ], 'Blood types updated successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update blood types: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('blood_types')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Blood type not found.');
            }

            return $this->successResponse($data, 'Blood type data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve blood type data: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $bloodType = DB::table('blood_types')->where('id', $id)->first();

            if (!$bloodType) {
                return $this->notFoundResponse('Blood type not found.');
            }

            DB::table('blood_types')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Blood Type Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Blood Type informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'deleted_id' => $id,
                'deleted_name' => $bloodType->name
            ], 'Blood type deleted successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete blood type: ' . $e->getMessage());
        }
    }
}
