<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;

class OvertimeTaxController extends Controller
{
    use ApiResponse;
    public function index()
    {
        try {
            $year = Carbon::now()->format('Y');

            $data = DB::table('overtime_taxes')->where('fiscal_year', $year)->orderBy('amount_from', 'desc')->get();

            return $this->successResponse($data, 'Overtime tax table retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve overtime tax table: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $arr_len = count($data['id']);
            $errors = [];
            $created = 0;
            $updated = 0;

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['amount_from'][$i] != NULL && $data['amount_to'][$i] != NULL && $data['percentage'][$i] != NULL) {

                    $id = $data['id'][$i];
                    $amount_from = $data['amount_from'][$i];
                    $amount_to = $data['amount_to'][$i];
                    $percentage = $data['percentage'][$i];

                    if ($id == 0) {
                        $data_exists = DB::table('overtime_taxes')->where([
                            'amount_from' => $amount_from,
                            'amount_to' => $amount_to,
                            'fiscal_year' => $data['overtime_fiscal_year']
                        ])->get();

                        if ($data_exists->isNotEmpty()) {
                            $errors[] = 'Overtime tax record already exists for amount range ' . $amount_from . ' - ' . $amount_to;
                            continue;
                        }
                        $created++;
                    } else {
                        $data_exists = DB::table('overtime_taxes')->where([
                            'amount_from' => $amount_from,
                            'amount_to' => $amount_to,
                            'fiscal_year' => $data['overtime_fiscal_year']
                        ])
                            ->whereNotIn('id', [$id])
                            ->get();

                        if ($data_exists->isNotEmpty()) {
                            $errors[] = 'Overtime tax record already exists for amount range ' . $amount_from . ' - ' . $amount_to;
                            continue;
                        }
                        $updated++;
                    }

                    $rules = [
                        'amount_from.' . $i => 'numeric|min:0',
                        'amount_to.' . $i => 'numeric|min:0',
                        'percentage.' . $i => 'numeric|min:0',
                    ];

                    $messages = [
                        'amount_from.' . $i . '.numeric' => 'The amount_from field must be a number.',
                        'amount_from.' . $i . '.min' => 'The amount_from field must be at least :min.',
                        'amount_to.' . $i . '.numeric' => 'The amount_to field must be a number.',
                        'amount_to.' . $i . '.min' => 'The amount_to field must be at least :min.',
                        'percentage.' . $i . '.numeric' => 'The percentage field must be a number.',
                        'percentage.' . $i . '.min' => 'The percentage field must be at least :min.',
                    ];

                    $validator = Validator::make($data, $rules, $messages);

                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    if ($data['id'][$i] == null) {
                        $id = 0 + DB::table('overtime_taxes')->max('id');
                        $id += 1;
                    } else {
                        $id = $data['id'][$i];
                    }

                    $rata_data = [
                        'amount_from' => $amount_from,
                        'amount_to' => $amount_to,
                        'percentage' => $percentage,
                        'fiscal_year' => $data['overtime_fiscal_year']
                    ];

                    DB::unprepared('SET IDENTITY_INSERT overtime_taxes ON');

                    DB::table('overtime_taxes')->updateOrInsert([
                        'id' => $id, 'fiscal_year' => $data['overtime_fiscal_year']
                    ], $rata_data);
                    DB::unprepared('SET IDENTITY_INSERT overtime_taxes OFF');
                }
            }

            if (!empty($errors)) {
                return $this->validationErrorResponse(['errors' => $errors]);
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Overtime Tax Setup',
                'activity' => 'Update',
                'description' => 'Updated Overtime Tax informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'created_count' => $created,
                'updated_count' => $updated
            ], 'You have successfully updated Overtime Tax Table!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update overtime tax table: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('overtime_taxes')->where('id', $id)->delete();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Overtime Tax Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Overtime Tax informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Overtime tax record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete overtime tax record: ' . $e->getMessage());
        }
    }

    public function loadOTTax($year)
    {
        try {
            $data = DB::table('overtime_taxes')->where('fiscal_year', $year)->get();

            return $this->successResponse($data, 'Overtime tax data for year ' . $year . ' retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load overtime tax data: ' . $e->getMessage());
        }
    }
}
