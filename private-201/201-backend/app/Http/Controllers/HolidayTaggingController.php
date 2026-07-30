<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\HolidayTaggingHeader;


class HolidayTaggingController extends Controller
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
            $Company = DB::table('companies')->get();
            $Branch = DB::table('branches')->get();
            $HolidayType = DB::table('holiday_types')->orderBy('name', 'asc')->get();

            return $this->successResponse([
                'companies' => $Company,
                'branches' => $Branch,
                'holiday_types' => $HolidayType
            ], 'Holiday tagging data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve holiday tagging data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'branch_id' => 'required|exists:branches,id',
                'holiday_type_id' => 'required|exists:holiday_types,id',
                'holiday_tagging_year' => 'required|integer|min:2000|max:2100',
                'holiday_active' => 'required|array',
                'holiday_id' => 'required|array'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $dataX = $request->all();

            $bid = $request->branch_id;
            $htype_id = $request->holiday_type_id;
            $year = $request->holiday_tagging_year;
            $holiday_tagging = HolidayTaggingHeader::firstOrNew(['branch_id' => $bid, 'holiday_type_id' => $htype_id, 'year' => $year]);

            $data = array(
                'branch_id' => $bid,
                'holiday_type_id' => $request->holiday_type_id,
                'year' => $request->holiday_tagging_year
            );

            $holiday_tagging->fill($data);
            $holiday_tagging->save();
            $holiday_tagging_id = $holiday_tagging->id;

            // Store Holiday Tagging
            $arr_len = count($dataX['holiday_active']);
            $holiday = [];
            $processed_count = 0;

            for ($i = 0; $i < $arr_len; $i++) {
                if ($dataX['holiday_active'][$i] != NULL) {

                    $holiday = [
                        'holiday_tagging_id'      => $holiday_tagging_id,
                        'holiday_id'     => $dataX['holiday_id'][$i],
                    ];

                    DB::table('holiday_tagging_details')->Insert($holiday);
                    $processed_count++;
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel Module',
                'menu'    => 'Holiday Tagging Setup',
                'activity' => 'Add',
                'description' => 'Add Holiday Tagging to fix schedule',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'holiday_tagging_id' => $holiday_tagging_id,
                'processed_count' => $processed_count
            ], 'Holiday tagging saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save holiday tagging: ' . $e->getMessage());
        }
    }

    public function delete($id, $holiday_id)
    {
        try {
            $data = DB::table('holiday_tagging_details')
                ->select('holiday_tagging_details.holiday_tagging_id as id', 'holiday_tagging_details.holiday_id', 'holidays.name')
                ->join('holidays', 'holidays.id', '=', 'holiday_tagging_details.holiday_id')
                ->where(['holiday_tagging_id' => $id, 'holiday_id' => $holiday_id])->first();

            if (!$data) {
                return $this->notFoundResponse('Holiday tagging record not found');
            }

            return $this->successResponse($data, 'Holiday tagging data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve holiday tagging data for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($id, $holiday_id)
    {
        try {
            $data = DB::table('holiday_tagging_details')
                ->where(['holiday_tagging_id' => $id, 'holiday_id' => $holiday_id])
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Holiday tagging record not found');
            }

            DB::table('holiday_tagging_details')->where(['holiday_tagging_id' => $id, 'holiday_id' => $holiday_id])->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Holiday Tagging Setup',
                'activity' => 'Delete',
                'description' => 'Deleted holiday details informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['deleted_id' => $id, 'deleted_holiday_id' => $holiday_id], 'Holiday tagging deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete holiday tagging: ' . $e->getMessage());
        }
    }
}
