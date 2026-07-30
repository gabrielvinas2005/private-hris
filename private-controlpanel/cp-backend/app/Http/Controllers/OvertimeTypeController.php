<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;

class OvertimeTypeController extends Controller
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
            $data = DB::table('overtime_types')->orderby('name', 'asc')->get();

            return $this->successResponse($data, 'Overtime types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve overtime types: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $arr_len = count($data['name']);
            $created = 0;
            $updated = 0;

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL && $data['rate'][$i] != NULL) {
                    $id = $data['id'][$i];
                    $name = $data['name'][$i];
                    $rate = $data['rate'][$i];
                    $nd_rating = $data['nd_rating'][$i];
                    $min_ot = $data['min_ot'][$i];
                    $max_ot = $data['max_ot'][$i];

                    $rules = [
                        'name.' . $i => 'required|string|unique:overtime_types,name,' . $id,
                        'rate.' . $i => 'required',
                    ];

                    $messages = [
                        'name.' . $i . '.required' => 'The name field is required.',
                        'name.' . $i . '.string' => 'The name field must be a string.',
                        'name.' . $i . '.unique' => 'The name has already been taken.',
                        'rate.' . $i . '.required' => 'The rate field is required.',
                        'min_ot.' . $i . '.numeric' => 'The min OT field must be a number.',
                        'max_ot.' . $i . '.numeric' => 'The max OT field must be a number.',
                        'max_ot.' . $i . '.min' => 'The max OT field must be at least :min.',
                        'nd_rating.' . $i . '.numeric' => 'The ND rating field must be a number.',
                    ];

                    $validator = Validator::make($data, $rules, $messages);

                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    $overtime_data = [
                        'name' => $name,
                        'rate' => $rate,
                        'active' => isset($data['active'][$id]) ? true : false,
                        'nd_from' => $data['nd_from'][$i],
                        'nd_to' => $data['nd_to'][$i],
                        'nd_rating' => $nd_rating,
                        'min_ot' => $min_ot,
                        'max_ot' => $max_ot,
                    ];

                    if ($id == null || $id == 0) {
                        DB::table('overtime_types')->insert($overtime_data);
                        $created++;
                    } else {
                        DB::table('overtime_types')->where('id', $id)->update($overtime_data);
                        $updated++;
                    }
                }
            }

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Overtime Types Setup',
                'activity' => 'Update',
                'description' => 'Updated overtime table information.',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'created_count' => $created,
                'updated_count' => $updated
            ], 'You have successfully updated overtime!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update overtime types: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('overtime_types')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Overtime type not found');
            }

            return $this->successResponse($data, 'Overtime type data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve overtime type for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $overtime_type = DB::table('overtime_types')->where('id', $id)->first();

            if (!$overtime_type) {
                return $this->notFoundResponse('Overtime type not found');
            }

            DB::table('overtime_types')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Overtime Types Setup',
                'activity' => 'Delete',
                'description' => 'Deleted overtime information',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Successfully deleted!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete overtime type: ' . $e->getMessage());
        }
    }
}
