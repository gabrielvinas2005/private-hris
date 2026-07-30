<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PayrollPeriodController extends Controller
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
            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'b.name as payroll_interval',
                    'c.name as payroll_cutoff',
                    'a.attendance_start_date',
                    'a.attendance_end_date',
                    'a.payroll_start_date',
                    'a.payroll_end_date',
                    'a.release_date',
                    DB::raw("case when a.active = 'true' then 'YES' else 'NO' end as active"),
                    DB::raw("case when a.posted = 'true' then 'YES' else 'NO' end as posted")
                )
                ->orderby('a.release_date', 'desc')
                ->get();

            return $this->successResponse($data, 'Payroll periods retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll periods: ' . $e->getMessage());
        }
    }

    public function add(Request $request, $id)
    {
        try {
            $intervals = DB::table('payroll_intervals')->where('active', true)->get();
            $cutoffs = DB::table('payroll_cutoffs')->get();

            if ($id == 0) {
                $dummy_period = array(
                    'id' => 0,
                    'payroll_interval_id' => 0,
                    'payroll_cutoff_id' => 0,
                    'attendance_start_date' => null,
                    'attendance_end_date' => null,
                    'payroll_start_date' => null,
                    'payroll_end_date' => null,
                    'release_date' => null,
                    'posted' => null,
                    'active' => null
                );

                $period = (object)$dummy_period;
                $period = collect([$period]);
                $release_date = null;
            } else {
                $period = DB::table('payroll_periods')
                    ->select(
                        'id',
                        'payroll_interval_id',
                        'payroll_cutoff_id',
                        'attendance_start_date',
                        'attendance_end_date',
                        'payroll_start_date',
                        'payroll_end_date',
                        'release_date',
                        'posted',
                        'active'
                    )
                    ->where('id', $id)->get();

                if ($period->isEmpty()) {
                    return $this->notFoundResponse('Payroll period not found');
                }

                $release_date = Carbon::parse($period[0]->release_date)->format('Y-m');
            }

            return $this->successResponse([
                'intervals' => $intervals,
                'cutoffs' => $cutoffs,
                'period' => $period,
                'release_date' => $release_date
            ], 'Payroll period form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll period form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payroll_interval_id' => 'required',
                'payroll_cutoff_id' => 'required',
                'attendance_start_date' => 'required|date',
                'attendance_end_date' => 'required|date',
                'payroll_start_date' => 'required|date',
                'payroll_end_date' => 'required|date',
                'release_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // $payroll_date = Carbon::parse($request->release_date)->endOfMonth()->subMonth()->toDateString();
            $payroll_date = Carbon::parse($request->release_date);

            $is_exist = DB::table('payroll_periods')->where([
                'payroll_interval_id' => $request->payroll_interval_id,
                'attendance_start_date' => $request->attendance_start_date,
                'attendance_end_date' => $request->attendance_end_date,
                'payroll_start_date' => $request->payroll_start_date,
                'payroll_end_date' => $request->payroll_end_date,
                'release_date' => $payroll_date,
            ])
                ->get();

            if ($is_exist->isNotEmpty() && $id == 0) {
                return $this->errorResponse('Payroll Period already exists.');
            }

            if ($request->payroll_start_date < $request->attendance_start_date || $request->payroll_end_date < $request->attendance_start_date) {
                return $this->errorResponse('Payout Date should not be less than attendance period');
            }

            $data = array(
                'payroll_interval_id' => $request->payroll_interval_id,
                'payroll_cutoff_id' => $request->payroll_cutoff_id,
                'attendance_start_date' => $request->attendance_start_date,
                'attendance_end_date' => $request->attendance_end_date,
                'payroll_start_date' => $request->payroll_start_date,
                'payroll_end_date' => $request->payroll_end_date,
                'release_date' => $payroll_date,
                'active' => $request->has('active') ? true : false
            );

            if ($id == 0) {
                DB::table('payroll_periods')->insert($data);
                $new_id = DB::table('payroll_periods')->max('id');

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Periods',
                    'activity' => 'Add',
                    'description' => 'Added Payroll Periods information',
                );

                return $this->successResponse([
                    'id' => $new_id,
                    'payroll_interval_id' => $request->payroll_interval_id,
                    'payroll_cutoff_id' => $request->payroll_cutoff_id,
                    'attendance_start_date' => $request->attendance_start_date,
                    'attendance_end_date' => $request->attendance_end_date,
                    'payroll_start_date' => $request->payroll_start_date,
                    'payroll_end_date' => $request->payroll_end_date,
                    'release_date' => $payroll_date,
                    'active' => $request->has('active') ? true : false
                ], 'You have successfully added payroll period!');
            } else {
                DB::table('payroll_periods')->updateOrInsert(['id' => $id], $data);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Periods',
                    'activity' => 'Update',
                    'description' => 'Updated Payroll Periods information',
                );

                return $this->successResponse([
                    'id' => $id,
                    'payroll_interval_id' => $request->payroll_interval_id,
                    'payroll_cutoff_id' => $request->payroll_cutoff_id,
                    'attendance_start_date' => $request->attendance_start_date,
                    'attendance_end_date' => $request->attendance_end_date,
                    'payroll_start_date' => $request->payroll_start_date,
                    'payroll_end_date' => $request->payroll_end_date,
                    'release_date' => $payroll_date,
                    'active' => $request->has('active') ? true : false
                ], 'You have successfully updated payroll period!');
            }

            Audit::create($data_audit);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save payroll period: ' . $e->getMessage());
        }
    }
}
