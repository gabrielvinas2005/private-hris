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
     * Accepts optional query parameter 'type' to specify table type
     * Types: 'monthly' (default), 'semi-monthly', 'annual'
     */
    public function index(Request $request)
    {
        try {
            $type = $request->query('type', 'monthly');
            $table = $this->getTableName($type);

            $data = DB::table($table)->orderby('percentage', 'asc')->get();

            return $this->successResponse($data, 'Tax records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve tax records: ' . $e->getMessage());
        }
    }

    /**
     * Get table name based on type
     */
    private function getTableName($type)
    {
        switch ($type) {
            case 'semi-monthly':
                return 'semi_monthly_tax_table';
            case 'annual':
                return 'annual_tax_table';
            case 'monthly':
            default:
                return 'tax_tables';
        }
    }

    /**
     * Store tax records
     * Accepts optional 'type' parameter to specify table type
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $type = $request->input('type', 'monthly');
            $table = $this->getTableName($type);

            if (!isset($data['id']) || !is_array($data['id'])) {
                return $this->errorResponse('Invalid data format. Expected array of tax records.');
            }

            $arr_len = count($data['id']);
            if ($arr_len > 9) {
                return $this->errorResponse('Maximum entry for tax table is 9 only.');
            }
            $tax_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['min_amount'][$i] != NULL && $data['max_amount'][$i] != NULL) {

                    $tax_data = [
                        'min_amount' => floatval($data['min_amount'][$i]),
                        'max_amount' => floatval($data['max_amount'][$i]),
                        'base_tax' => floatval($data['base_tax'][$i]),
                        'percentage' => floatval($data['percentage'][$i]),
                        'updated_at' => now()
                    ];

                    // If ID is null, insert new record; otherwise update existing
                    if ($data['id'][$i] == null || $data['id'][$i] == '') {
                        $tax_data['created_at'] = now();
                        DB::table($table)->insert($tax_data);
                    } else {
                        DB::table($table)->updateOrInsert(
                            ['id' => $data['id'][$i]],
                            $tax_data
                        );
                    }
                }
            }

            //Save audit trail
            $typeLabel = ucfirst(str_replace('-', ' ', $type));
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Tax Table Setup',
                'activity' => 'Update',
                'description' => "Updated {$typeLabel} tax table information",
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
    public function delete(Request $request, $id)
    {
        try {
            $type = $request->query('type', 'monthly');
            $table = $this->getTableName($type);

            $data = DB::table($table)->where('id', $id)->get();

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
    public function destroy(Request $request, $id)
    {
        try {
            $type = $request->query('type', 'monthly');
            $table = $this->getTableName($type);

            $record = DB::table($table)->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('Tax record not found');
            }

            DB::table($table)->where('id', $id)->delete();

            //Save audit trail
            $typeLabel = ucfirst(str_replace('-', ' ', $type));
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Tax Table Setup',
                'activity' => 'Delete',
                'description' => "Deleted {$typeLabel} tax table information",
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
    public function show(Request $request, $id)
    {
        try {
            $type = $request->query('type', 'monthly');
            $table = $this->getTableName($type);

            $data = DB::table($table)->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Tax record not found');
            }

            return $this->successResponse($data, 'Tax record retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve tax record: ' . $e->getMessage());
        }
    }
}
