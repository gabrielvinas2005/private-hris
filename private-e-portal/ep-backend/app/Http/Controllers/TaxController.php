<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Tax;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
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
     * Get all tax records
     */
    public function index()
    {
        try {
            $data = DB::table('taxes')->orderby('min_income', 'asc')->get();

            return $this->successResponse($data, 'Tax records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve tax records: ' . $e->getMessage());
        }
    }

    /**
     * Store tax records
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['id']) || !is_array($data['id'])) {
                return $this->errorResponse('Invalid data format. Expected array of tax records.');
            }

            $arr_len = count($data['id']);
            $tax_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['min_income'][$i] != NULL && $data['max_income'][$i] != NULL) {

                    $tax_data = [
                        'min_income' => $data['min_income'][$i],
                        'max_income' => $data['max_income'][$i],
                        'fixed_tax' => $data['fixed_tax'][$i],
                        'percentage' => $data['percentage'][$i],
                    ];

                    DB::table('taxes')->updateOrInsert(['id' => $data['id'][$i]], $tax_data);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Tax Table Setup',
                'activity' => 'Update',
                'description' => 'Updated tax table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Tax table updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update tax table: ' . $e->getMessage());
        }
    }

    /**
     * Get tax record for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('taxes')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Tax record not found');
            }

            return $this->successResponse($data, 'Tax record retrieved for deletion');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve tax record: ' . $e->getMessage());
        }
    }

    /**
     * Delete tax record
     */
    public function destroy($id)
    {
        try {
            $record = DB::table('taxes')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Tax record not found');
            }

            DB::table('taxes')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Tax Table Setup',
                'activity' => 'Delete',
                'description' => 'Deleted tax table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Tax record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete tax record: ' . $e->getMessage());
        }
    }

    /**
     * Show specific tax record
     */
    public function show($id)
    {
        try {
            $data = DB::table('taxes')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Tax record not found');
            }

            return $this->successResponse($data, 'Tax record retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve tax record: ' . $e->getMessage());
        }
    }
}
