<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Philhealth;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SSSController extends Controller
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

    /**
     * Get all SSS records
     */
    public function index()
    {
        try {
            $data = DB::table('sss')->orderby('min_income', 'asc')->get();

            return $this->successResponse($data, 'SSS records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve SSS records: ' . $e->getMessage());
        }
    }

    /**
     * Store SSS records
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['id']) || !is_array($data['id'])) {
                return $this->errorResponse('Invalid data format. Expected array of SSS records.');
            }

            $arr_len = count($data['id']);
            $sss_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['min_income'][$i] != NULL && $data['max_income'][$i] != NULL) {

                    $sss_data = [
                        'min_income' => $data['min_income'][$i],
                        'max_income' => $data['max_income'][$i],
                        'ER' => $data['ER'][$i],
                        'EE' => $data['EE'][$i],
                        'MPF_ER' => $data['MPF_ER'][$i],
                        'MPF_EE' => $data['MPF_EE'][$i],
                    ];

                    DB::table('sss')->updateOrInsert(['id' => $data['id'][$i]], $sss_data);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'SSS Table Setup',
                'activity' => 'Update',
                'description' => 'Updated SSS table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'SSS table updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update SSS table: ' . $e->getMessage());
        }
    }

    /**
     * Get SSS record for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('sss')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('SSS record not found');
            }

            return $this->successResponse($data, 'SSS record retrieved for deletion');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve SSS record: ' . $e->getMessage());
        }
    }

    /**
     * Delete SSS record
     */
    public function destroy($id)
    {
        try {
            $record = DB::table('sss')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('SSS record not found');
            }

            DB::table('sss')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'SSS Table Setup',
                'activity' => 'Delete',
                'description' => 'Deleted sss table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'SSS record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete SSS record: ' . $e->getMessage());
        }
    }

    /**
     * Show specific SSS record
     */
    public function show($id)
    {
        try {
            $data = DB::table('sss')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('SSS record not found');
            }

            return $this->successResponse($data, 'SSS record retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve SSS record: ' . $e->getMessage());
        }
    }
}
