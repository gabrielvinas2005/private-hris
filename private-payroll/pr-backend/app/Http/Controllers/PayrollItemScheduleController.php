<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\PayrollItemScheduleHeader;


class PayrollItemScheduleController extends Controller
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

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            DB::table('payroll_item_schedule_details')
                ->where('payroll_item_schedule_header_id', $id)
                ->delete();

            DB::table('payroll_item_schedule_headers')
                ->where('id', $id)
                ->delete();
                
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Item Schedule',
                'activity' => 'Delete',
                'description' => 'Delete Payroll Item Schedule',
            );
            Audit::create($data_audit);

            DB::commit();

            return $this->successResponse(null, 'Payroll item schedule deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete payroll item schedule: ' . $e->getMessage());
        }
    }

    public function index()
    {
        try {
            $PayrollInterval = DB::table('payroll_intervals')->where('active', true)->where('id', '>', 0)->get();
            $PayrollPeriodType = DB::table('payroll_periods as a')
                ->join('payroll_cutoffs as b', 'a.payroll_cutoff_id', '=', 'b.id')
                ->where('a.active', true)
                ->where('a.id', '>', 0)
                ->groupBy('b.name')
                ->select(DB::raw('MIN(a.id) as id'), 'b.name')
                ->orderBy('id', 'asc')
                ->get();
            $Income = DB::table('incomes')->where('active', true)->where('id', '>', 0)->orderBy('id', 'asc')->get();
            $Deduction = DB::table('deductions')->where('active', true)->where('id', '>', 0)->orderBy('id', 'asc')->get();
            $EmploymentType = DB::table('employment_types')->where('active', true)->where('id', '>', 0)->get();

            // Get existing schedules for table display
            $schedules = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_type_id', '=', 'b.id')
                ->join('payroll_periods as c', 'a.payroll_period_type_id', '=', 'c.id')
                ->join('payroll_cutoffs as d', 'c.payroll_cutoff_id', '=', 'd.id')
                ->join('employment_types as e', 'a.employment_type_id', '=', 'e.id')
                ->select(
                    'a.id',
                    'a.payroll_interval_type_id',
                    'b.name as payroll_interval_name',
                    'a.payroll_period_type_id',
                    'd.name as payroll_period_name',
                    'a.employment_type_id',
                    'e.name as employment_type_name',
                    'a.sss',
                    'a.gsis',
                    'a.tax',
                    'a.philhealth',
                    'a.pagibig'
                )
                ->where('a.id', '>', 0)
                ->get();

            foreach ($schedules as $schedule) {
                $incomeCount = DB::table('payroll_item_schedule_details')
                    ->where('payroll_item_schedule_header_id', $schedule->id)
                    ->where('income_id', '>', 0)
                    ->where('active', true)
                    ->count();

                $deductionCount = DB::table('payroll_item_schedule_details')
                    ->where('payroll_item_schedule_header_id', $schedule->id)
                    ->where('deduction_id', '>', 0)
                    ->where('active', true)
                    ->count();

                $schedule->income_count = $incomeCount;
                $schedule->deduction_count = $deductionCount;
            }

            return $this->successResponse([
                'PayrollInterval' => $PayrollInterval,
                'PayrollPeriodType' => $PayrollPeriodType,
                'Income' => $Income,
                'Deduction' => $Deduction,
                'EmploymentType' => $EmploymentType,
                'Schedules' => $schedules
            ], 'Payroll item schedule data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll item schedule data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $dataX = $request->all();

            $validator = Validator::make($request->all(), [
                'payroll_interval_id' => 'required',
                'payroll_period_type_id' => 'required',
                'employment_type_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if (!isset($dataX['income_id'])) {
                return $this->errorResponse('There is no Income setup.');
            }

            if (!isset($dataX['deduction_id'])) {
                return $this->errorResponse('There is no Deduction setup.');
            }

            $payroll_interval_type_id = $request->payroll_interval_id;
            $payroll_period_type_id = $request->payroll_period_type_id;
            $employment_type_id = $request->employment_type_id;

            $existingSchedule = PayrollItemScheduleHeader::where([
                'payroll_interval_type_id' => $payroll_interval_type_id,
                'payroll_period_type_id' => $payroll_period_type_id,
                'employment_type_id' => $employment_type_id
            ])->first();

            // Create mode: reject if schedule already exists
            if (!$request->filled('id') && $existingSchedule) {
                return $this->errorResponse(
                    'A payroll item schedule for this Interval, Period, and Employment Type already exists. Please use Edit to modify it.'
                );
            }

            // Update mode: reject if trying to move to a combination owned by a different schedule
            if ($request->filled('id')) {
                $scheduleId = $request->id;
                if ($existingSchedule && (int) $existingSchedule->id !== (int) $scheduleId) {
                    return $this->errorResponse(
                        'A different schedule already exists for this Interval, Period, and Employment Type.'
                    );
                }
            }

            // Enforce first-half vs second-half contribution rules (applies to both create and update)
            // Commented out to allow the same contribution settings for both 1st and 2nd payroll periods.
            // $period = DB::table('payroll_periods')->where('id', $payroll_period_type_id)->first();
            // $cutoffName = $period
            //     ? DB::table('payroll_cutoffs')->where('id', $period->payroll_cutoff_id)->value('name')
            //     : null;
            // $cutoffNameNormalized = is_string($cutoffName) ? strtolower($cutoffName) : '';
            // $is_first_half  = str_contains($cutoffNameNormalized, '1st') || str_contains($cutoffNameNormalized, 'first');
            // $is_second_half = str_contains($cutoffNameNormalized, '2nd') || str_contains($cutoffNameNormalized, 'second');
            //
            // if ($is_first_half && $request->boolean('tax')) {
            //     return $this->errorResponse(
            //         'Tax deduction is only allowed in the 2nd payroll period. For the 1st half, please leave Tax unchecked.'
            //     );
            // }
            // if ($is_second_half && ($request->boolean('sss') || $request->boolean('gsis') || $request->boolean('philhealth') || $request->boolean('pagibig'))) {
            //     return $this->errorResponse(
            //         'Mandatory contributions (SSS, GSIS, PhilHealth, Pag-IBIG) are only allowed in the 1st payroll period. For the 2nd half, please leave them unchecked.'
            //     );
            // }

            $payroll_item_schedule_header = $request->filled('id')
                ? PayrollItemScheduleHeader::findOrFail($request->id)
                : PayrollItemScheduleHeader::firstOrNew([
                    'payroll_interval_type_id' => $payroll_interval_type_id,
                    'payroll_period_type_id' => $payroll_period_type_id,
                    'employment_type_id' => $employment_type_id
                ]);

            // Use the actual values the user submitted for government contributions
            $data = array(
                'payroll_interval_type_id' => $payroll_interval_type_id,
                'payroll_period_type_id' => $request->payroll_period_type_id,
                'employment_type_id' => $request->employment_type_id,
                'sss' => $request->boolean('sss'),
                'tax' => $request->boolean('tax'),
                'pagibig' => $request->boolean('pagibig'),
                'gsis' => $request->boolean('gsis'),
                'philhealth' => $request->boolean('philhealth'),
            );

            $payroll_item_schedule_header->fill($data);
            $payroll_item_schedule_header->save();
            $payroll_item_schedule_header_id = $payroll_item_schedule_header->id;

            $income_processed = 0;
            $deduction_processed = 0;

            // Store Incomes
            $arr_len = count($dataX['income_id']);
            $payroll_item_schedule_details_income = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($dataX['income_id'][$i] != NULL) {

                    if (isset($dataX['income_status'])) {
                        if (in_array($dataX['income_id'][$i], $dataX['income_status'])) {
                            $status = true;
                        } else {
                            $status = false;
                        }
                    } else {
                        $status = false;
                    }

                    $payroll_item_schedule_details_income = [
                        'payroll_item_schedule_header_id'      => $payroll_item_schedule_header_id,
                        'income_id'     => $dataX['income_id'][$i],
                        'deduction_id'     => 0,
                        'active'     => $status,
                    ];

                    DB::table('payroll_item_schedule_details')->updateOrInsert(['payroll_item_schedule_header_id' => $payroll_item_schedule_header_id, 'income_id' => $dataX['income_id'][$i]], $payroll_item_schedule_details_income);
                    $income_processed++;
                }
            }

             // Store Deductions
             $arr_len2 = count($dataX['deduction_id']);
             $payroll_item_schedule_details_deduction = []; 

            for ($i = 0; $i < $arr_len2; $i++) {
                if ($dataX['deduction_id'][$i] != NULL) {

                    if (isset($dataX['deduction_status'])) {
                        if (in_array($dataX['deduction_id'][$i], $dataX['deduction_status'])) {
                            $status = true;
                        } else {
                            $status = false;
                        }
                    } else {
                        $status = false;
                    }

                    $payroll_item_schedule_details_deduction = [
                        'payroll_item_schedule_header_id'      => $payroll_item_schedule_header_id,
                        'deduction_id'     => $dataX['deduction_id'][$i],
                        'income_id'     => 0,
                        'active'     => $status,
                    ];

                    DB::table('payroll_item_schedule_details')->updateOrInsert(['payroll_item_schedule_header_id' => $payroll_item_schedule_header_id, 'deduction_id' => $dataX['deduction_id'][$i]], $payroll_item_schedule_details_deduction);
                    $deduction_processed++;
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Item Schedule',
                'activity' => 'Update',
                'description' => 'Update Payroll Item Schedule',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'payroll_item_schedule_header_id' => $payroll_item_schedule_header_id,
                'income_processed' => $income_processed,
                'deduction_processed' => $deduction_processed,
                'total_processed' => $income_processed + $deduction_processed
            ], 'You have successfully updated payroll item schedule!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update payroll item schedule: ' . $e->getMessage());
        }
    }

    public function getIncome($payroll_interval_type_id, $payroll_period_type_id, $employment_type_id)
    {
        try {
            $incomes = DB::table('incomes as a')
                ->select('a.id', 'a.name', DB::raw('0 as active'))
                ->where('a.id', '>', 0)
                ->where('a.active', true)
                ->whereNotIn('a.id', function ($query) use ($payroll_interval_type_id, $payroll_period_type_id, $employment_type_id) {
                    $query->select(DB::raw('b.income_id'))
                        ->from('payroll_item_schedule_details as b')
                        ->join('payroll_item_schedule_headers as c', 'b.payroll_item_schedule_header_id', '=', 'c.id')
                        ->where([
                            'c.payroll_interval_type_id' => $payroll_interval_type_id,
                            'c.payroll_period_type_id' => $payroll_period_type_id,
                            'c.employment_type_id' => $employment_type_id
                        ]);
                });

            $data = DB::table('incomes as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.income_id')
                ->join('payroll_item_schedule_headers as c', 'b.payroll_item_schedule_header_id', '=', 'c.id')
                ->select('a.id', 'a.name', 'b.active')
                ->where('a.id', '>', 0)
                ->where(['c.payroll_interval_type_id' => $payroll_interval_type_id, 'c.payroll_period_type_id' => $payroll_period_type_id, 'c.employment_type_id' => $employment_type_id])
                ->union($incomes)
                ->orderBy('id', 'asc')
                ->get();

            return $this->successResponse($data, 'Income data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve income data: ' . $e->getMessage());
        }
    }

    public function getDeduction($payroll_interval_type_id, $payroll_period_type_id, $employment_type_id)
    {
        try {
            $deduction = DB::table('deductions as a')
                ->select('a.id', 'a.name', DB::raw('0 as active'))
                ->where('a.active', true)
                ->where('a.id', '>', 0)
                ->whereNotIn('a.id', function ($query) use ($payroll_interval_type_id, $payroll_period_type_id, $employment_type_id) {
                    $query->select(DB::raw('b.deduction_id'))
                        ->from('payroll_item_schedule_details as b')
                        ->join('payroll_item_schedule_headers as c', 'b.payroll_item_schedule_header_id', '=', 'c.id')
                        ->where([
                            'c.payroll_interval_type_id' => $payroll_interval_type_id,
                            'c.payroll_period_type_id' => $payroll_period_type_id,
                            'c.employment_type_id' => $employment_type_id
                        ]);
                });

            $data = DB::table('deductions as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.deduction_id')
                ->join('payroll_item_schedule_headers as c', 'b.payroll_item_schedule_header_id', '=', 'c.id')
                ->select('a.id', 'a.name', 'b.active')
                ->where('a.id', '>', 0)
                ->where(['c.payroll_interval_type_id' => $payroll_interval_type_id, 'c.payroll_period_type_id' => $payroll_period_type_id, 'c.employment_type_id' => $employment_type_id])
                ->union($deduction)
                ->orderBy('id', 'asc')
                ->get();

            return $this->successResponse($data, 'Deduction data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve deduction data: ' . $e->getMessage());
        }
    }

    public function getHeader($payroll_interval_type_id, $payroll_period_type_id, $employment_type_id)
    {
        try {
            $data = DB::table('payroll_item_schedule_headers')
                ->where(['payroll_interval_type_id' => $payroll_interval_type_id, 'payroll_period_type_id' => $payroll_period_type_id, 'employment_type_id' => $employment_type_id])
                ->get();

            return $this->successResponse($data, 'Payroll item schedule header data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll item schedule header data: ' . $e->getMessage());
        }
    }
}
