<?php

namespace App\Http\Controllers;

use Auth;
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

    public function index()
    {
        try {
            $PayrollInterval = DB::table('payroll_intervals')->where('active', true)->get();
            $PayrollPeriodType = DB::table('payroll_periods as a')
                ->join('payroll_cutoffs as b', 'a.payroll_cutoff_id', '=', 'b.id')
                ->select('a.id', 'b.name')
                ->where('a.active', true)
                ->get();
            $Income = DB::table('incomes')->where('active', true)->orderBy('id', 'asc')->get();
            $Deduction = DB::table('deductions')->where('active', true)->orderBy('id', 'asc')->get();
            $EmploymentType = DB::table('employment_types')->where('active', true)->get();

            return $this->successResponse([
                'PayrollInterval' => $PayrollInterval,
                'PayrollPeriodType' => $PayrollPeriodType,
                'Income' => $Income,
                'Deduction' => $Deduction,
                'EmploymentType' => $EmploymentType
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

            $payroll_item_schedule_header = PayrollItemScheduleHeader::firstOrNew(['payroll_interval_type_id' => $payroll_interval_type_id, 'payroll_period_type_id' => $payroll_period_type_id, 'employment_type_id' => $employment_type_id]);

            $data = array(
                'payroll_interval_type_id' => $payroll_interval_type_id,
                'payroll_period_type_id' => $request->payroll_period_type_id,
                'employment_type_id' => $request->employment_type_id,
                'sss' => $request->has('sss') ? true : false,
                'tax' => $request->has('tax') ? true : false,
                'pagibig' => $request->has('pagibig') ? true : false,
                'gsis' => $request->has('gsis') ? true : false,
                'philhealth' => $request->has('philhealth') ? true : false
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
