<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LoyaltyAwardSetupController extends Controller
{
    public function index()
    {
        try {
            $data = DB::table('loyalty_award_setup')->get();

        return $this->successResponse($data, 'Loyalty award setup retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve loyalty award setup: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
        $arr_len = count($data['years_of_service']);
        $award_data = [];
    
        for ($i = 1; $i < $arr_len; $i++) {
            if ($data['id'][$i] == 0) {
                $id = DB::table('loyalty_award_setup')->max('id') + 1;
            } else {
                $id = $data['id'][$i];
            }
    
            $rules = [
                'years_of_service.' . $i => 'required|numeric|min:0|unique:loyalty_award_setup,years_of_service' . ($id ? ",$id" : ''),
                'cash_award.' . $i => 'required|numeric|min:0',
            ];
    
            $messages = [
                'years_of_service.' . $i . '.required' => 'The Year field is required.',
                'years_of_service.' . $i . '.unique' => 'The Year field must be unique.',
                'years_of_service.' . $i . '.numeric' => 'The Year field must be a number.',
                'years_of_service.' . $i . '.min' => 'The Year field must be at least :min.',
                'cash_award.' . $i . '.required' => 'The Cash Award field is required.',
                'cash_award.' . $i . '.numeric' => 'The Cash Award field must be a number.',
                'cash_award.' . $i . '.min' => 'The Cash Award field must be at least :min.',
            ];
    
            $validator = Validator::make($data, $rules, $messages);
    
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }
    
            // Automatically set cash_token based on years_of_service
            $years_of_service = $data['years_of_service'][$i];
            $cash_token = 0;
    
            if ($years_of_service >= 1 && $years_of_service <= 15) {
                $cash_token = 2500;
            } elseif ($years_of_service >= 20 && $years_of_service <= 25) {
                $cash_token = 5000;
            } elseif ($years_of_service >= 30) {
                $cash_token = 30000;
            }
    
            $award_data = [
                'years_of_service' => $years_of_service,
                'cash_award' => $data['cash_award'][$i],
                'cash_token' => $cash_token, // Set the calculated cash token
            ];
    
            DB::table('loyalty_award_setup')->updateOrInsert(['id' => $id], $award_data);
        }
    
        // Save audit trail
        $data_audit = [
            'user_id' => Auth::user()->id,
            'module'  => 'Control Panel',
            'menu'    => 'Loyalty Award Setup',
            'activity' => 'Update',
            'description' => 'Updated loyalty award table informations.',
        ];
    
        Audit::create($data_audit);
    
        return $this->successResponse(null, 'Loyalty award setup updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update loyalty award setup: ' . $e->getMessage());
        }
    }
    

    public function delete($id)
    {
        try {
            $data = DB::table('loyalty_award_setup')->where('id', $id)->get();

        return $this->successResponse($data, 'Loyalty award setup data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve loyalty award setup for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('loyalty_award_setup')->where('id', $id)->delete();

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Control Panel',
            'menu'    => 'Loyalty Award Setup',
            'activity' => 'Delete',
            'description' => 'Deleted Loyalty Award informations.',
        );

        Audit::create($data_audit);

        return $this->successResponse(null, 'Loyalty award setup deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete loyalty award setup: ' . $e->getMessage());
        }
    }
}