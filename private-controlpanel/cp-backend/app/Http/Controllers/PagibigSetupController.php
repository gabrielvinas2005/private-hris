<?php

namespace App\Http\Controllers;

use App\Audit;
use App\Http\Controllers\Controller;
use App\PagibigSetup;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PagibigSetupController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $pagibig_setups = PagibigSetup::orderBy('year')->get();

            return $this->successResponse($pagibig_setups, 'Pagibig setup table retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve Pagibig setup table: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $arr_len = count($data['year']);
            $pagibig_setups = [];
            $created_count = 0;
            $updated_count = 0;
            $errors = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['year'][$i] != NULL || $data['amount'][$i] != null) {
                    $year = $data['year'][$i];
                    $amount = $data['amount'][$i];

                    $rules = [
                        'year.' . $i => 'required|numeric|min:0',
                        'amount.' . $i => 'required|numeric|min:0',
                    ];

                    $messages = [
                        'year.' . $i . '.required' => 'Year is required value.',
                        'year.' . $i . '.min' => 'The Year must be a non-negative value.',
                        'amount.' . $i . '.required' => 'Amount is required value.',
                        'amount.' . $i . '.min' => 'The Amount must be a non-negative value.',
                    ];

                    $validator = Validator::make($data, $rules, $messages);

                    if ($validator->fails()) {
                        $errors[] = [
                            'row' => $i + 1,
                            'errors' => $validator->errors()->toArray()
                        ];
                        continue;
                    }

                    if ($data['year'][$i] == null || $data['year'][$i] == 0) {
                        $year = DB::table('philhealths')->max('year') + 1;
                    } else {
                        $year = $data['year'][$i];
                    }

                    $pagibig_setups = [
                        'year' => $year,
                        'amount' => $amount,
                    ];

                    // Check if record exists
                    $existing = DB::table('pagibig_setups')
                        ->where('year', $data['year'][$i])
                        ->exists();

                    DB::table('pagibig_setups')->updateOrInsert(['year' => $data['year'][$i]], $pagibig_setups);

                    if ($existing) {
                        $updated_count++;
                    } else {
                        $created_count++;
                    }
                }
            }

            // If there are validation errors, return them
            if (!empty($errors)) {
                return $this->validationErrorResponse(['bulk_errors' => $errors]);
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Pag-Ibig Table Setup',
                'activity' => 'Update',
                'description' => 'Updated pagibig setup table information',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'created_count' => $created_count,
                'updated_count' => $updated_count,
                'total_processed' => $created_count + $updated_count
            ], 'You have successfully updated pagibig table!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update pagibig table: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('pagibig_setups')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Pagibig setup record not found');
            }

            return $this->successResponse($data, 'Pagibig setup data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve Pagibig setup data: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $record = DB::table('pagibig_setups')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Pagibig setup record not found');
            }

            DB::table('pagibig_setups')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Pag-Ibig Table Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Pag-Ibig table information',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'deleted_id' => $id,
                'deleted_year' => $record->year,
                'deleted_amount' => $record->amount
            ], 'Successfully deleted!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete Pagibig setup record: ' . $e->getMessage());
        }
    }
}
