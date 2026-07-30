<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Incomes;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeController extends Controller
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
            $data = DB::table('incomes')
                ->orderBy('name', 'asc')
                ->get();

            return $this->successResponse($data, 'Income records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve income records: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['name']) || !is_array($data['name'])) {
                return $this->errorResponse('Invalid data format. Expected array of income records.');
            }

            $processed_count = 0;
            $created_count = 0;
            $updated_count = 0;

            for ($i = 0; $i < count($data['name']); $i++) {
                if ($data['name'][$i] != NULL) {

                    if ($data['id'][$i] == null || $data['id'][$i] == 0) {

                        $is_exist = DB::table('incomes')->where('name', $data['name'][$i])->get();

                        if ($is_exist->isNotEmpty()) {
                            return $this->errorResponse('"' . $data['name'][$i] . '" already exists');
                        }

                        $id = 0 + DB::table('incomes')->max('id');
                        $id += 1;
                        $created_count++;
                    } else {
                        $id = $data['id'][$i];
                        $updated_count++;
                    }

                    $isTaxable = false;
                    if (isset($data['is_taxable']) && is_array($data['is_taxable'])) {
                        if (isset($data['is_taxable'][$i])) {
                            $isTaxable = filter_var($data['is_taxable'][$i], FILTER_VALIDATE_BOOLEAN);
                        } elseif (isset($data['is_taxable'][$data['id'][$i]])) {
                            $isTaxable = filter_var($data['is_taxable'][$data['id'][$i]], FILTER_VALIDATE_BOOLEAN);
                        }
                    }

                    $isTimeRelated = false;
                    if (isset($data['is_time_related']) && is_array($data['is_time_related'])) {
                        if (isset($data['is_time_related'][$i])) {
                            $isTimeRelated = filter_var($data['is_time_related'][$i], FILTER_VALIDATE_BOOLEAN);
                        } elseif (isset($data['is_time_related'][$data['id'][$i]])) {
                            $isTimeRelated = filter_var($data['is_time_related'][$data['id'][$i]], FILTER_VALIDATE_BOOLEAN);
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

                    $income_data = [
                        'name' => $data['name'][$i],
                        'is_taxable' => $isTaxable,
                        'is_time_related' => $isTimeRelated,
                        'active' => $isActive,
                    ];

                    DB::unprepared('SET IDENTITY_INSERT incomes ON');
                    DB::table('incomes')->updateOrInsert(['id' => $id], $income_data);
                    DB::unprepared('SET IDENTITY_INSERT incomes OFF');

                    $processed_count++;
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Income Types Setup',
                'activity' => 'Update',
                'description' => 'Updated income table information',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'processed_count' => $processed_count,
                'created_count' => $created_count,
                'updated_count' => $updated_count
            ], 'Income records updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update income records: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $income = DB::table('incomes')->where('id', $id)->first();

            if (!$income) {
                return $this->notFoundResponse('Income record not found');
            }

            DB::table('incomes')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Income Types Setup',
                'activity' => 'Delete',
                'description' => 'Deleted income table information',
            );

            Audit::create($data_audit);

            return $this->successResponse(['deleted_id' => $id, 'deleted_name' => $income->name], 'Income record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete income record: ' . $e->getMessage());
        }
    }
}
