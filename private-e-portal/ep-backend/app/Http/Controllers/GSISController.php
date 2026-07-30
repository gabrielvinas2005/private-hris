<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\GSIS;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GSISController extends Controller
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
     * Get all GSIS records
     */
    public function index()
    {
        try {
            $data = DB::table('gsis')->orderby('year', 'desc')->get();

            return $this->successResponse($data, 'GSIS records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve GSIS records: ' . $e->getMessage());
        }
    }

    /**
     * Store GSIS records
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['id']) || !is_array($data['id'])) {
                return $this->errorResponse('Invalid data format. Expected array of GSIS records.');
            }

            $arr_len = count($data['id']);
            $gsis_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['year'][$i] != NULL) {
                    $gsis_data = [
                        'year' => $data['year'][$i],
                        'multiplier' => $data['multiplier'][$i],
                        'employer_share' => $data['employer_share'][$i],
                        'employer_share_admin' => $data['employer_share_admin'][$i],
                        'effectivity_date' => $data['effectivity_date'][$i],
                        'end_date' => $data['end_date'][$i]
                    ];

                    DB::table('gsis')->updateOrInsert(['year' => $data['year'][$i]], $gsis_data);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'GSIS Table Setup',
                'activity' => 'Update',
                'description' => 'Updated gsis table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'GSIS table updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update GSIS table: ' . $e->getMessage());
        }
    }

    /**
     * Get GSIS record for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('gsis')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('GSIS record not found');
            }

            return $this->successResponse($data, 'GSIS data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve GSIS record: ' . $e->getMessage());
        }
    }

    /**
     * Delete GSIS record
     */
    public function destroy($id)
    {
        try {
            $record = DB::table('gsis')->where('id', $id)->first();

            if (!$record) {
                return $this->notFoundResponse('GSIS record not found');
            }

            DB::table('gsis')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'GSIS Table Setup',
                'activity' => 'Delete',
                'description' => 'Deleted GSIS table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'GSIS record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete GSIS record: ' . $e->getMessage());
        }
    }

    /**
     * Get GSIS form data
     */
    public function add($id)
    {
        try {
            if ($id == 0) {
                $data = [
                    'id' => 0,
                    'year' => null,
                    'multiplier' => null,
                    'employer_share' => null,
                    'employer_share_admin' => null,
                    'effectivity_date' => null,
                    'end_date' => null
                ];

                $data = (object)$data;
                $data = collect([$data]);
            } else {
                $data = DB::table('gsis')->where('id', $id)->get();
                
                if ($data->isEmpty()) {
                    return $this->notFoundResponse('GSIS record not found');
                }
            }

            return $this->successResponse($data, 'GSIS form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load GSIS form data: ' . $e->getMessage());
        }
    }

    /**
     * Store individual GSIS record
     */
    public function storeGSIS(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'multiplier' => 'required|numeric|min:0',
                'employer_share' => 'required|numeric|min:0',
                'employer_share_admin' => 'required|numeric|min:0',
                'effectivity_date' => 'required',
                'end_date' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->effectivity_date > $request->end_date) {
                return $this->errorResponse('Invalid Effectivity Date and End Date!');
            }

            $validate_gsis = DB::table('gsis')
                ->whereDate('effectivity_date', '<=', $request->effectivity_date)
                ->whereDate('end_date', '>=', $request->effectivity_date)
                ->where('id', '<>', $id)
                ->get();

            if (count($validate_gsis) > 0) {
                return $this->errorResponse('Invalid Effectivity Date ellapse to existing GSIS Record!');
            }

            $effectivity_date = new Carbon($request->effectivity_date);
            $year = $effectivity_date->year;

            $data = [
                'year' => $year + 1,
                'multiplier' => $request->multiplier,
                'employer_share' => $request->employer_share,
                'employer_share_admin' => $request->employer_share_admin,
                'effectivity_date' => $request->effectivity_date,
                'end_date' => $request->end_date
            ];

            if ($id == 0) {
                $id = DB::table('gsis')->max('id') + 1;
            }

            DB::unprepared('SET IDENTITY_INSERT gsis ON');
            DB::table('gsis')->updateOrInsert(['id' => $id], $data);
            DB::unprepared('SET IDENTITY_INSERT gsis OFF');

            if ($id == 0) {
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'GSIS Table Setup',
                    'activity' => 'Save',
                    'description' => 'Saved gsis table informations.',
                );
            } else {
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'GSIS Table Setup',
                    'activity' => 'Update',
                    'description' => 'Updated gsis table informations.',
                );
            }

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'GSIS record saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save GSIS record: ' . $e->getMessage());
        }
    }

    /**
     * Show specific GSIS record
     */
    public function show($id)
    {
        try {
            $data = DB::table('gsis')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('GSIS record not found');
            }

            return $this->successResponse($data, 'GSIS record retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve GSIS record: ' . $e->getMessage());
        }
    }
}
