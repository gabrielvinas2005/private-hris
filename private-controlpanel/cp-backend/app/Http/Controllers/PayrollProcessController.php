<?php

namespace App\Http\Controllers;

use PDF;
use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollProcessController extends Controller
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
            $payrolls = DB::table('time_data as a')
                ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                ->join('payroll_intervals as c', 'c.id', '=', 'b.payroll_interval_id')
                ->join('payroll_cutoffs as d', 'd.id', '=', 'b.payroll_cutoff_id')
                ->select(
                    'b.*',
                    DB::raw("CONCAT(c.name,' (',DATENAME(MONTH,b.release_date),' ',DATEPART(YEAR,b.release_date),') ') as payroll")
                )
                ->orderBy('b.release_date', 'asc')
                ->distinct()
                ->get();

            return $this->successResponse($payrolls, 'Payroll processes retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll processes: ' . $e->getMessage());
        }
    }

    public function process($id)
    {
        try {
            // Check if has GSIS setup.
            $gsis_data = DB::table('gsis')->get();

            if ($gsis_data->isEmpty()) {
                return $this->errorResponse("No GSIS Setup. Unable to process payroll.");
            }

            $tax_data = DB::table('tax_tables')->get();

            if ($tax_data->isEmpty()) {
                return $this->errorResponse("No Tax Setup. Unable to process payroll.");
            }

            $payroll_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'b.id', '=', 'a.payroll_interval_id')
                ->select(
                    'a.*',
                    'b.month_frequency'
                )
                ->where('a.id', $id)->get();

            $payroll_items = DB::table('payroll_item_schedule_headers')
                ->where([
                    'payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                    'payroll_period_type_id' => $payroll_periods[0]->payroll_cutoff_id,
                ])
                ->get();

            // get mandatory contribution schedule.
            if ($payroll_items->isNotEmpty()) {
                $include_gsis = $payroll_items[0]->gsis;
                $include_sss = $payroll_items[0]->sss;
                $include_pagibig = $payroll_items[0]->pagibig;
                $include_philhealth = $payroll_items[0]->philhealth;
                $include_tax = $payroll_items[0]->tax;
            } else {
                $include_gsis = false;
                $include_sss = false;
                $include_pagibig = false;
                $include_philhealth = false;
                $include_tax = false;
            }

            // get release year.
            $release_year = date('Y', strtotime($payroll_periods[0]->release_date));

            // set month frequency.
            $month_frequency = $payroll_periods[0]->month_frequency;

            $employees = DB::table('employees as a')
                ->join('time_data as b', 'a.id', '=', 'b.employee_id')
                ->select(
                    'a.id',
                    'a.salary',
                    'a.gsis_amount',
                    'a.sss_amount',
                    'a.pagibig_amount',
                    'a.philhealth_amount',
                    'a.tax_amount',
                    'a.employment_type_id'
                )
                ->where([
                    'a.active' => true,
                    'a.is_employee' => true,
                    'b.payroll_period_id' => $id
                ])
                ->distinct()
                ->get();

            if ($employees->isEmpty()) {
                return $this->errorResponse('No payroll to process please make sure to process attendance first!', 400);
            } else {
                foreach ($employees as $emp) {

                    // contribution divisor.
                    $gsis_divisor = DB::table('payroll_item_schedule_headers as a')
                        ->join('payroll_intervals as b', 'a.payroll_interval_type_id', '=', 'b.id')
                        ->join('payroll_cutoffs as c', 'a.payroll_period_type_id', '=', 'c.id')
                        ->where([
                            'a.payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                            'a.employment_type_id' => $emp->employment_type_id,
                            'a.gsis' => true
                        ])
                        ->count();

                    $sss_divisor = DB::table('payroll_item_schedule_headers as a')
                        ->join('payroll_intervals as b', 'a.payroll_interval_type_id', '=', 'b.id')
                        ->join('payroll_cutoffs as c', 'a.payroll_period_type_id', '=', 'c.id')
                        ->where([
                            'a.payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                            'a.employment_type_id' => $emp->employment_type_id,
                            'a.sss' => true
                        ])
                        ->count();

                    $pagibig_divisor = DB::table('payroll_item_schedule_headers as a')
                        ->join('payroll_intervals as b', 'a.payroll_interval_type_id', '=', 'b.id')
                        ->join('payroll_cutoffs as c', 'a.payroll_period_type_id', '=', 'c.id')
                        ->where([
                            'a.payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                            'a.employment_type_id' => $emp->employment_type_id,
                            'a.pagibig' => true
                        ])
                        ->count();

                    $philhealth_divisor = DB::table('payroll_item_schedule_headers as a')
                        ->join('payroll_intervals as b', 'a.payroll_interval_type_id', '=', 'b.id')
                        ->join('payroll_cutoffs as c', 'a.payroll_period_type_id', '=', 'c.id')
                        ->where([
                            'a.payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                            'a.employment_type_id' => $emp->employment_type_id,
                            'a.philhealth' => true
                        ])
                        ->count();

                    $tax_divisor = DB::table('payroll_item_schedule_headers as a')
                        ->join('payroll_intervals as b', 'a.payroll_interval_type_id', '=', 'b.id')
                        ->join('payroll_cutoffs as c', 'a.payroll_period_type_id', '=', 'c.id')
                        ->where([
                            'a.payroll_interval_type_id' => $payroll_periods[0]->payroll_interval_id,
                            'a.employment_type_id' => $emp->employment_type_id,
                            'a.tax' => true
                        ])
                        ->count();

                    // get employee salary and basic pay.
                    $employee_id = $emp->id;
                    $salary = $emp->salary;
                    $basic_pay = $emp->salary;

                    // get time keeping setup.
                    $time_keeping = DB::table('time_keeping_setups')->where('employment_type_id', $emp->employment_type_id)->get();

                    if ($time_keeping->isNotEmpty()) {
                        $tk_days = $time_keeping[0]->work_days == 0 ? 22 : $time_keeping[0]->work_days;
                        $tk_hours = $time_keeping[0]->work_hours == 0 ? 8 : $time_keeping[0]->work_hours;
                    } else {
                        $tk_days = 22;
                        $tk_hours = 8;
                    }

                    // get all taxable incomes.
                    $taxable_incomes = DB::table('payroll_incomes as a')
                        ->join('incomes as b', 'b.id', '=', 'a.income_id')
                        ->select(
                            DB::raw("sum(a.amount) as income")
                        )
                        ->where(['a.payroll_period_id' => $id, 'a.employee_id' => $employee_id, 'is_taxable' => true])
                        ->groupBy('a.employee_id')
                        ->get();

                    if ($taxable_incomes->isEmpty()) {
                        $total_taxable_income = 0;
                    } else {
                        $total_taxable_income = $taxable_incomes[0]->income;
                    }

                    // get daily rate
                    $daily_rate = round(($salary / $tk_days), 2);

                    // get hourly rate
                    $hourly_rate = round(($daily_rate / $tk_hours), 2);

                    // get tardiness deduction
                    // get timekeeping and tardiness data.
                    $totals = DB::table('time_data as a')
                        ->join('employees as b', 'a.employee_id', '=', 'b.id')
                        ->select(
                            DB::raw("sum(a.ot_hours) as ot"),
                            DB::raw("sum(a.late) as late"),
                            DB::raw("sum(a.undertime) as undertime"),
                            DB::raw("sum(a.leave) as leave"),
                            DB::raw("sum(a.absent) as absent"),
                            DB::raw("sum(a.work_hours) as work_hours"),
                            DB::raw("sum(a.ot_pay) as ot_pay"),
                            DB::raw("sum(a.nd_pay) as nd_pay"),
                            DB::raw("sum(a.holiday_pay) as holiday_pay"),
                            DB::raw("sum(a.lwop) as lwop")
                        )
                        ->where([
                            'a.payroll_period_id' => $id,
                            'b.id' => $employee_id,
                            'b.active' => true,
                            'b.is_employee' => true
                        ])
                        ->groupBy('a.employee_id')
                        ->get();

                    if ($totals->isEmpty()) {
                        // get late amount.
                        $late_amount = 0;
                        // get undertime amount.
                        $ut_amount = 0;
                        // get absent amount.
                        $absent_amount = 0;
                        // get lwop
                        $lwop_amount = 0;
                        // get total tardiness.
                        $total_tardiness = 0;
                    } else {

                        // get calendar days.
                        $attendance_from = Carbon::parse($payroll_periods[0]->attendance_start_date);
                        $attendance_to = Carbon::parse($payroll_periods[0]->attendance_end_date);
                        $calendar_days = $attendance_from->diffInDays($attendance_to) + 1;

                        $daily_rate_calendar_days = round(($salary / $calendar_days), 2);
                        $hourly_rate_calendar_days = round(($daily_rate_calendar_days / $tk_hours), 2);

                        if (($totals[0]->late / $tk_hours) < 0) {
                            $totals_late =  $totals[0]->late;

                            // get late amount.
                            if ($totals_late > 10) {
                                $late_amount = ($hourly_rate * $totals_late);
                            } else {
                                $late_amount = ($hourly_rate_calendar_days * $totals_late);
                            }
                        } else {
                            $totals_late = ($totals[0]->late / $tk_hours);
                            // get late amount.
                            if ($totals_late > 10) {
                                $late_amount = ($daily_rate * $totals_late);
                            } else {
                                $late_amount = ($daily_rate_calendar_days * $totals_late);
                            }
                        }

                        if (($totals[0]->undertime / $tk_hours) < 0) {
                            $totals_undertime =  $totals[0]->undertime;
                            // get undertime amount.
                            if ($totals_undertime > 10) {
                                $ut_amount = ($hourly_rate * $totals_undertime);
                            } else {
                                $ut_amount = ($hourly_rate_calendar_days * $totals_undertime);
                            }
                        } else {
                            $totals_undertime = ($totals[0]->undertime / $tk_hours);
                            // get undertime amount.
                            if ($totals_undertime > 10) {
                                $ut_amount = ($daily_rate * $totals_undertime);
                            } else {
                                $ut_amount = ($daily_rate_calendar_days * $totals_undertime);
                            }
                        }

                        // get absent amount.
                        if ($totals[0]->absent > 10) {
                            $absent_amount = ($daily_rate * $totals[0]->absent);
                        } else {
                            $absent_amount = ($daily_rate_calendar_days * $totals[0]->absent);
                        }

                        // get lwop amount.
                        if ($totals[0]->absent > 10) {
                            $lwop_amount = ($daily_rate * $totals[0]->lwop);
                        } else {
                            $lwop_amount = ($daily_rate_calendar_days * $totals[0]->lwop);
                        }

                        // get total tardiness.
                        $total_tardiness = ($late_amount + $ut_amount + $absent_amount);
                    }

                    // re-compute employee contribution.
                    // get pagibig amount
                    $pagibig_payrolls = DB::table('pagibig_payroll_headers as a')
                        ->where([
                            'a.employee_id' => $employee_id
                        ])
                        ->where('a.payroll_period_id', '<=', $id)
                        ->orderBy('a.payroll_period_id', 'desc')
                        ->limit(1)
                        ->get();

                    $year = $payroll_periods[0]->payroll_start_date;
                    $pagibig_setup_amount = DB::table('pagibig_setups')->whereRaw("year = YEAR($year)")->get();

                    if ($pagibig_payrolls->isNotEmpty()) {
                        $pagibig_amount = $pagibig_payrolls[0]->amount;
                        $pagibig_tax_amount = $pagibig_amount;
                    } else {
                        if ($pagibig_setup_amount->isNotEmpty()) {
                            $pagibig_amount = $pagibig_setup_amount[0]->amount;
                            $pagibig_tax_amount = $pagibig_setup_amount[0]->amount;
                        } else {
                            $pagibig_amount = 200;
                            $pagibig_tax_amount = 200;
                        }
                    }

                    // get philhealth amount
                    $ph_data = DB::table('philhealths')->where('year', '<=', $release_year)->orderBy('year', 'desc')->get();

                    if ($ph_data->isEmpty()) { // if empty get topset-up.
                        $ph_data = DB::table('philhealths')->orderBy('year', 'desc')->get();
                    }

                    if ($ph_data->isEmpty()) {
                        return $this->errorResponse('Please Setup Philhealth Multipliers to continue processing salary adjustments.', 400);
                    }

                    $ph_multiplier = $ph_data[0]->multiplier;
                    $ph_income_ceilling = $ph_data[0]->income_ceiling;
                    $ph_income_floor = $ph_data[0]->income_floor;
                    $ph_fix_rate = $ph_data[0]->fix_rate;

                    if ($salary >= $ph_income_ceilling) {
                        $philhealth_amount = $ph_fix_rate;
                        $philhealth_amount_gs = $ph_fix_rate;
                    } elseif ($salary <= $ph_income_floor) {
                        $philhealth_amount = 0;
                        $philhealth_amount_gs = 0;
                    } else {
                        $philhealth_amount = (($salary * $ph_multiplier) / 2); // no round off
                        $philhealth_amount_gs = (($salary * $ph_multiplier) / 2); // with round off
                    }

                    // get gsis amount
                    $gsis_data = DB::table('gsis')->where('year', '<=', $release_year)->orderBy('year', 'desc')->get();

                    if ($gsis_data->isEmpty()) { // if empty get topset-up.
                        $gsis_data = DB::table('gsis')->orderBy('year', 'desc')->get();
                    }

                    if ($gsis_data->isEmpty()) {
                        return $this->errorResponse('Please Setup GSIS Multipliers to continue processing salary adjustments.', 400);
                    }

                    $gsis_amount = ($salary * $gsis_data[0]->multiplier);

                    // get sss amount
                    $sss_amount = 0;

                    // get employee manual tax adjustments
                    $employee_tax_adjustments = DB::table('payroll_tax_adjustments')
                        ->where([
                            'employee_id' => $employee_id,
                            'payroll_period_id' => $id
                        ])
                        ->get();

                    if ($employee_tax_adjustments->isNotEmpty()) {
                        $tax_amount = $employee_tax_adjustments[0]->tax_amount ?? 0;
                    } else {
                        // get tax amount
                        $tax_data = DB::table('tax_tables')->get();
                        $arr_len = DB::table('tax_tables')->count('id');

                        $taxable_amount = (($salary + $total_taxable_income) - ($gsis_amount + $philhealth_amount + $pagibig_tax_amount + $total_tardiness));
                        $tax_amount = 0;

                        for ($i = 0; $i < $arr_len; $i++) {
                            if ($taxable_amount >= $tax_data[$i]->min_amount && $taxable_amount <= $tax_data[$i]->max_amount) {
                                $tax_amount = ((($taxable_amount - $tax_data[$i]->min_amount) * $tax_data[$i]->percentage) + $tax_data[$i]->base_tax);
                                break;
                            } else {
                                $tax_amount = 0;
                            }
                        }
                    }

                    // get mandatory contributions.
                    if ($gsis_amount > 0) {
                        $gsis_divisor = $gsis_divisor == null ? 1 : $gsis_divisor;
                        $gsis = $include_gsis == 0 ? 0 : ($gsis_amount / $gsis_divisor);
                    } else {
                        $gsis = 0;
                    }

                    if ($sss_divisor > 0) {
                        $sss_divisor = $sss_divisor == null ? 1 : $sss_divisor;
                        $sss = $include_sss  == 0 ? 0 : ($sss_amount / $sss_divisor);
                    } else {
                        $sss = 0;
                    }

                    if ($pagibig_amount > 0) {
                        $pagibig_divisor = $pagibig_divisor == null ? 1 : $pagibig_divisor;
                        $pagibig = $include_pagibig  == 0 ? 0 : ($pagibig_amount / $pagibig_divisor);
                    } else {
                        $pagibig = 0;
                    }

                    if ($philhealth_amount > 0) {
                        $philhealth_divisor = $philhealth_divisor == null ? 1 : $philhealth_divisor;
                        $philhealth = $include_philhealth  == 0 ? 0 : ($philhealth_amount / $philhealth_divisor);
                    } else {
                        $philhealth = 0;
                    }

                    if ($tax_amount > 0) {
                        $tax_divisor = ($tax_divisor == null || $tax_divisor == 0) ? 1 : $tax_divisor;
                        $tax = $include_tax  == 0 ? 0 : ($tax_amount / $tax_divisor);
                    } else {
                        $tax = 0;
                    }

                    // get incomes.
                    $holiday_amount = $totals[0]->holiday_pay;
                    $ot_amount = $totals[0]->ot_pay;
                    $nd_amount = $totals[0]->nd_pay;

                    // get all income items.
                    $incomes = DB::table('payroll_incomes')
                        ->select(
                            DB::raw("sum(amount) as income")
                        )
                        ->where(['payroll_period_id' => $id, 'employee_id' => $employee_id])
                        ->groupBy('employee_id')
                        ->get();

                    // get all deduction items.
                    $deductions = DB::table('payroll_deductions')
                        ->select(
                            DB::raw("sum(amount) as deduction")
                        )
                        ->where(['payroll_period_id' => $id, 'employee_id' => $employee_id])
                        ->groupBy('employee_id')
                        ->get();

                    $total_income = $incomes->isEmpty() ? 0 : $incomes[0]->income;
                    $total_deduction = $deductions->isEmpty() ? 0 : $deductions[0]->deduction;

                    // Removal of OT amount in Payroll start.
                    // $gross_amount = ($basic_pay + $holiday_amount + $ot_amount + $nd_amount + $total_income);
                    // Removal of OT amount in Payroll end.

                    // Get Total Pending Amount.
                    // $pending_deduction_amount = DB::table('pending_deductions')
                    //     ->select(
                    //         DB::raw("sum(amount) as amount"),
                    //         DB::raw("sum(amount_paid) as amount_paid")
                    //     )
                    //     ->where('employee_id', $employee_id)
                    //     ->where('payroll_period_id', '<', $id)
                    //     ->get();

                    // if ($pending_deduction_amount->isNotEmpty()) {
                    //     $pending_amount = ($pending_deduction_amount[0]->amount - $pending_deduction_amount[0]->amount_paid);
                    // } else {
                    //     $pending_amount = 0;
                    // }

                    // if ($pending_amount < 0) {
                    //     $pending_amount = 0;
                    // }

                    // $pending_amount = 0;
                    $original_netpay = 0;
                    $net_pay = 0;

                    $gross_amount = ($basic_pay + $holiday_amount + $total_income);
                    // $original_netpay = round(($gross_amount - ($gsis + $sss + $pagibig + $philhealth + $tax + $late_amount + $ut_amount + $absent_amount + $total_deduction)), 2);
                    $original_netpay = ($gross_amount - ($gsis + $sss + $pagibig + $philhealth + $tax + $late_amount + $ut_amount + $absent_amount + $total_deduction));

                    if ($original_netpay < 0) {
                        $original_netpay = 0;
                    }

                    // check accumulated net pay if less than 5000.
                    // $net_pay = round(($gross_amount - ($gsis + $sss + $pagibig + $philhealth + $tax + $late_amount + $ut_amount + $absent_amount + $total_deduction)), 2);
                    $net_pay = ($gross_amount - ($gsis + $sss + $pagibig + $philhealth + $tax + $late_amount + $ut_amount + $absent_amount + $total_deduction));
                    $total_deduction = $total_deduction + $gsis + $sss + $pagibig + $philhealth + $tax + $late_amount + $ut_amount + $absent_amount;
                    // $pending_deduction_data = [];
                    // $with_pending_deductions = false;
                    // $total_deduction_base_on_priority = $total_deduction;

                    // if ($net_pay < 5000) {
                    //     // check priority setup before processing.
                    //     $deduction_priority = DB::table('deduction_priorities')
                    //         ->whereIn('deduction', ['Tardiness', 'Undertime', 'Absent'])
                    //         ->orderBy('priority', 'asc')
                    //         ->get();

                    //     if ($deduction_priority->isEmpty()) {
                    //         // while ($net_pay < 5000) {
                    //         if ($net_pay < 5000 && $late_amount != 0) { // less late amount first

                    //             $net_pay_without_late_amount = ($net_pay + $late_amount);

                    //             if ($net_pay_without_late_amount < 5000) {
                    //                 $net_pay = ($gross_amount - ($gsis + $sss + $pagibig + $philhealth + $tax + $ut_amount + $absent_amount + $total_deduction));
                    //                 $late_amount_to_pending = $late_amount;
                    //                 $late_amount = 0;
                    //             } else {
                    //                 $late_amount_to_include = $net_pay_without_late_amount - 5000;
                    //                 $late_amount_to_pending = $late_amount - $late_amount_to_include;
                    //                 $net_pay = $net_pay_without_late_amount - $late_amount_to_include;
                    //                 $late_amount = $late_amount_to_include;
                    //             }

                    //             $pending_deductions = DB::table('pending_deductions')
                    //                 ->where([
                    //                     'employee_id' => $employee_id,
                    //                     'payroll_period_id' => $id,
                    //                     'deduction_id' => 100
                    //                 ])
                    //                 ->get();

                    //             if ($pending_deductions->isEmpty()) {
                    //                 $pending_deduction_id = 1 + DB::table('pending_deductions')->max('id');
                    //             } else {
                    //                 $pending_deduction_id = $pending_deductions[0]->id;
                    //             }

                    //             $pending_deduction_data = [
                    //                 'employee_id' => $employee_id,
                    //                 'payroll_period_id' => $id,
                    //                 'deduction_id' => 100,
                    //                 'amount' => $late_amount_to_pending,
                    //                 'is_late' => true,
                    //                 'is_undertime' => false,
                    //                 'is_absent' => false,
                    //                 'is_paid' => false
                    //             ];

                    //             DB::unprepared('SET IDENTITY_INSERT pending_deductions ON');
                    //             DB::table('pending_deductions')->updateOrInsert(['id' => $pending_deduction_id], $pending_deduction_data);
                    //             DB::unprepared('SET IDENTITY_INSERT pending_deductions OFF');

                    //             $with_pending_deductions = true;
                    //         }

                    //         if ($net_pay < 5000 && $ut_amount != 0) { // less undertime amount first

                    //             $net_pay_without_ut_amount = ($net_pay + $ut_amount);

                    //             if (
                    //                 $net_pay_without_ut_amount < 5000
                    //             ) {
                    //                 $net_pay = ($gross_amount - ($gsis + $sss + $pagibig + $philhealth + $tax + $absent_amount + $total_deduction));
                    //                 $ut_amount_to_pending = $ut_amount;
                    //                 $ut_amount =  0;
                    //             } else {
                    //                 $ut_amount_to_include = $net_pay_without_ut_amount - 5000;
                    //                 $ut_amount_to_pending = $ut_amount - $ut_amount_to_include;
                    //                 $net_pay = $net_pay_without_ut_amount - $ut_amount_to_include;
                    //                 $ut_amount = $ut_amount_to_include;
                    //             }

                    //             $pending_deductions = DB::table('pending_deductions')
                    //                 ->where([
                    //                     'employee_id' => $employee_id,
                    //                     'payroll_period_id' => $id,
                    //                     'deduction_id' => 101
                    //                 ])
                    //                 ->get();

                    //             if ($pending_deductions->isEmpty()) {
                    //                 $pending_deduction_id = 1 + DB::table('pending_deductions')->max('id');
                    //             } else {
                    //                 $pending_deduction_id = $pending_deductions[0]->id;
                    //             }

                    //             $pending_deduction_data = [
                    //                 'employee_id' => $employee_id,
                    //                 'payroll_period_id' => $id,
                    //                 'deduction_id' => 101,
                    //                 'amount' => $ut_amount_to_pending,
                    //                 'is_late' => false,
                    //                 'is_undertime' => true,
                    //                 'is_absent' => false,
                    //                 'is_paid' => false
                    //             ];

                    //             DB::unprepared('SET IDENTITY_INSERT pending_deductions ON');
                    //             DB::table('pending_deductions')->updateOrInsert(['id' => $pending_deduction_id], $pending_deduction_data);
                    //             DB::unprepared('SET IDENTITY_INSERT pending_deductions OFF');

                    //             $with_pending_deductions = true;
                    //         }

                    //         if ($net_pay < 5000 && $absent_amount != 0) { // less absent amount first
                    //             $net_pay_without_absent_amount = ($net_pay + $absent_amount);

                    //             if (
                    //                 $net_pay_without_absent_amount < 5000
                    //             ) {
                    //                 $net_pay = ($gross_amount - ($gsis + $sss + $pagibig + $philhealth + $tax + $absent_amount + $total_deduction));
                    //                 $absent_amount_to_pending = $absent_amount;
                    //                 $absent_amount = 0;
                    //             } else {
                    //                 $absent_amount_to_include = $net_pay_without_absent_amount - 5000;
                    //                 $absent_amount_to_pending = $absent_amount - $absent_amount_to_include;
                    //                 $net_pay = $net_pay_without_absent_amount - $absent_amount_to_include;
                    //                 $absent_amount = $absent_amount_to_include;
                    //             }

                    //             $pending_deductions = DB::table('pending_deductions')
                    //                 ->where([
                    //                     'employee_id' => $employee_id,
                    //                     'payroll_period_id' => $id,
                    //                     'deduction_id' => 102
                    //                 ])
                    //                 ->get();

                    //             if ($pending_deductions->isEmpty()) {
                    //                 $pending_deduction_id = 1 + DB::table('pending_deductions')->max('id');
                    //             } else {
                    //                 $pending_deduction_id = $pending_deductions[0]->id;
                    //             }

                    //             $pending_deduction_data = [
                    //                 'employee_id' => $employee_id,
                    //                 'payroll_period_id' => $id,
                    //                 'deduction_id' => 102,
                    //                 'amount' => $absent_amount_to_pending,
                    //                 'is_late' => false,
                    //                 'is_undertime' => false,
                    //                 'is_absent' => true,
                    //                 'is_paid' => false
                    //             ];

                    //             DB::unprepared('SET IDENTITY_INSERT pending_deductions ON');
                    //             DB::table('pending_deductions')->updateOrInsert(['id' => $pending_deduction_id], $pending_deduction_data);
                    //             DB::unprepared('SET IDENTITY_INSERT pending_deductions OFF');

                    //             $with_pending_deductions = true;
                    //         }
                    //     } else {
                    //         foreach ($deduction_priority as $priority) {
                    //             if ($priority->deduction == 'Tardiness') {
                    //                 if ($net_pay < 5000 && $late_amount != 0) { // less late amount first

                    //                     $net_pay_without_late_amount = ($net_pay + $late_amount);

                    //                     if ($net_pay_without_late_amount < 5000) {
                    //                         $net_pay = ($gross_amount - ($gsis + $sss + $pagibig + $philhealth + $tax + $ut_amount + $absent_amount + $total_deduction));
                    //                         $late_amount_to_pending = $late_amount;
                    //                         $late_amount = 0;
                    //                     } else {
                    //                         $late_amount_to_include = $net_pay_without_late_amount - 5000;
                    //                         $late_amount_to_pending = $late_amount - $late_amount_to_include;
                    //                         $net_pay = $net_pay_without_late_amount - $late_amount_to_include;
                    //                         $late_amount = $late_amount_to_include;
                    //                     }

                    //                     $pending_deductions = DB::table('pending_deductions')
                    //                         ->where([
                    //                             'employee_id' => $employee_id,
                    //                             'payroll_period_id' => $id,
                    //                             'deduction_id' => 100
                    //                         ])
                    //                         ->get();

                    //                     if ($pending_deductions->isEmpty()) {
                    //                         $pending_deduction_id = 1 + DB::table('pending_deductions')->max('id');
                    //                     } else {
                    //                         $pending_deduction_id = $pending_deductions[0]->id;
                    //                     }

                    //                     $pending_deduction_data = [
                    //                         'employee_id' => $employee_id,
                    //                         'payroll_period_id' => $id,
                    //                         'deduction_id' => 100,
                    //                         'amount' => $late_amount_to_pending,
                    //                         'is_late' => true,
                    //                         'is_undertime' => false,
                    //                         'is_absent' => false,
                    //                         'is_paid' => false
                    //                     ];

                    //                     DB::unprepared('SET IDENTITY_INSERT pending_deductions ON');
                    //                     DB::table('pending_deductions')->updateOrInsert(['id' => $pending_deduction_id], $pending_deduction_data);
                    //                     DB::unprepared('SET IDENTITY_INSERT pending_deductions OFF');

                    //                     $with_pending_deductions = true;
                    //                 }
                    //             } elseif ($priority == 'Undertime') {
                    //                 if ($net_pay < 5000 && $ut_amount != 0) { // less undertime amount first

                    //                     $net_pay_without_ut_amount = ($net_pay + $ut_amount);

                    //                     if (
                    //                         $net_pay_without_ut_amount < 5000
                    //                     ) {
                    //                         $net_pay = ($gross_amount - ($gsis + $sss + $pagibig + $philhealth + $tax + $absent_amount + $total_deduction));
                    //                         $ut_amount_to_pending = $ut_amount;
                    //                         $ut_amount =  0;
                    //                     } else {
                    //                         $ut_amount_to_include = $net_pay_without_ut_amount - 5000;
                    //                         $ut_amount_to_pending = $ut_amount - $ut_amount_to_include;
                    //                         $net_pay = $net_pay_without_ut_amount - $ut_amount_to_include;
                    //                         $ut_amount = $ut_amount_to_include;
                    //                     }

                    //                     $pending_deductions = DB::table('pending_deductions')
                    //                         ->where([
                    //                             'employee_id' => $employee_id,
                    //                             'payroll_period_id' => $id,
                    //                             'deduction_id' => 101
                    //                         ])
                    //                         ->get();

                    //                     if ($pending_deductions->isEmpty()) {
                    //                         $pending_deduction_id = 1 + DB::table('pending_deductions')->max('id');
                    //                     } else {
                    //                         $pending_deduction_id = $pending_deductions[0]->id;
                    //                     }

                    //                     $pending_deduction_data = [
                    //                         'employee_id' => $employee_id,
                    //                         'payroll_period_id' => $id,
                    //                         'deduction_id' => 101,
                    //                         'amount' => $ut_amount_to_pending,
                    //                         'is_late' => false,
                    //                         'is_undertime' => true,
                    //                         'is_absent' => false,
                    //                         'is_paid' => false
                    //                     ];

                    //                     DB::unprepared('SET IDENTITY_INSERT pending_deductions ON');
                    //                     DB::table('pending_deductions')->updateOrInsert(['id' => $pending_deduction_id], $pending_deduction_data);
                    //                     DB::unprepared('SET IDENTITY_INSERT pending_deductions OFF');

                    //                     $with_pending_deductions = true;
                    //                 }
                    //             } else {
                    //                 if ($net_pay < 5000 && $absent_amount != 0) { // less absent amount first
                    //                     $net_pay_without_absent_amount = ($net_pay + $absent_amount);

                    //                     if (
                    //                         $net_pay_without_absent_amount < 5000
                    //                     ) {
                    //                         $net_pay = ($gross_amount - ($gsis + $sss + $pagibig + $philhealth + $tax + $absent_amount + $total_deduction));
                    //                         $absent_amount_to_pending = $absent_amount;
                    //                         $absent_amount = 0;
                    //                     } else {
                    //                         $absent_amount_to_include = $net_pay_without_absent_amount - 5000;
                    //                         $absent_amount_to_pending = $absent_amount - $absent_amount_to_include;
                    //                         $net_pay = $net_pay_without_absent_amount - $absent_amount_to_include;
                    //                         $absent_amount = $absent_amount_to_include;
                    //                     }

                    //                     $pending_deductions = DB::table('pending_deductions')
                    //                         ->where([
                    //                             'employee_id' => $employee_id,
                    //                             'payroll_period_id' => $id,
                    //                             'deduction_id' => 102
                    //                         ])
                    //                         ->get();

                    //                     if ($pending_deductions->isEmpty()) {
                    //                         $pending_deduction_id = 1 + DB::table('pending_deductions')->max('id');
                    //                     } else {
                    //                         $pending_deduction_id = $pending_deductions[0]->id;
                    //                     }

                    //                     $pending_deduction_data = [
                    //                         'employee_id' => $employee_id,
                    //                         'payroll_period_id' => $id,
                    //                         'deduction_id' => 102,
                    //                         'amount' => $absent_amount_to_pending,
                    //                         'is_late' => false,
                    //                         'is_undertime' => false,
                    //                         'is_absent' => true,
                    //                         'is_paid' => false
                    //                     ];

                    //                     DB::unprepared('SET IDENTITY_INSERT pending_deductions ON');
                    //                     DB::table('pending_deductions')->updateOrInsert(['id' => $pending_deduction_id], $pending_deduction_data);
                    //                     DB::unprepared('SET IDENTITY_INSERT pending_deductions OFF');

                    //                     $with_pending_deductions = true;
                    //                 }
                    //             }
                    //         }
                    //     }
                    // }

                    $pending_amount_to_pay = 0;

                    // if ($with_pending_deductions == false && $pending_amount > 0) {
                    //     if ($net_pay > 5000) {
                    //         $exist_net_pay = $net_pay - 5000;

                    //         if ($exist_net_pay <= $pending_amount) {
                    //             $pending_amount_to_pay = $exist_net_pay;
                    //         } else {
                    //             $pending_amount_to_pay = $pending_amount;
                    //         }

                    //         $net_pay = $net_pay - $pending_amount_to_pay;

                    //         // update pending deduction table.
                    //         $pending_deductions = DB::table('pending_deductions')
                    //             ->where([
                    //                 'employee_id' => $employee_id,
                    //                 'payroll_period_id' => $id,
                    //                 'deduction_id' => 102
                    //             ])
                    //             ->get();

                    //         if ($pending_deductions->isEmpty()) {
                    //             $pending_deduction_id = 1 + DB::table('pending_deductions')->max('id');
                    //         } else {
                    //             $pending_deduction_id = $pending_deductions[0]->id;
                    //         }

                    //         $pending_deduction_data = [
                    //             'employee_id' => $employee_id,
                    //             'payroll_period_id' => $id,
                    //             'deduction_id' => 102,
                    //             'amount_paid' => $pending_amount_to_pay,
                    //             'is_late' => false,
                    //             'is_undertime' => false,
                    //             'is_absent' => true,
                    //             'is_paid' => false
                    //         ];

                    //         DB::unprepared('SET IDENTITY_INSERT pending_deductions ON');
                    //         DB::table('pending_deductions')->updateOrInsert(['id' => $pending_deduction_id], $pending_deduction_data);
                    //         DB::unprepared('SET IDENTITY_INSERT pending_deductions OFF');
                    //     }
                    // }

                    if ($month_frequency == 0) {
                        $net_pay = $net_pay;
                    } else {
                        $net_pay = ($net_pay / $month_frequency);
                    }

                    // check if 2nd half
                    $payroll_second_half = DB::table('payroll_periods as a')
                        ->join('payroll_cutoffs as b', 'a.payroll_cutoff_id', '=', 'b.id')
                        ->whereRaw("b.name LIKE '%2nd%'")
                        ->where('a.id', $id)
                        ->get();

                    if ($payroll_second_half->isNotEmpty()) {
                        $is_second_half = true;
                    } else {
                        $is_second_half = false;
                    }

                    if ($is_second_half) {
                        $p_id = $id - 1;
                        $net_pay = DB::table('payroll_summaries')
                            ->where('payroll_period_id', $p_id)
                            ->where('employee_id', $employee_id)
                            ->value('net_pay') ?? $net_pay;
                    }

                    $payroll_data = array(
                        'payroll_period_id' => $id,
                        'employee_id' => $employee_id,
                        'salary' => $basic_pay,
                        'gsis' => $gsis,
                        'sss' => $sss,
                        'pagibig' => $pagibig,
                        'philhealth' => $philhealth,
                        // 'philhealth_gs' => $philhealth_amount_gs,
                        'tax' => $tax,
                        'late_amount' => $late_amount,
                        'ut_amount' => $ut_amount,
                        'absent_amount' => $absent_amount,
                        'holiday_amount' => $holiday_amount,
                        'ot_amount' => $ot_amount,
                        'nd_amount' => $nd_amount,
                        'total_income' => $total_income,
                        'total_deduction' => $total_deduction,
                        'gross_amount' => $gross_amount,
                        'net_pay' => $net_pay,
                        'original_netpay' => $original_netpay,
                        'pending_amount_payment' => $pending_amount_to_pay,
                        'lwop_amount' => $lwop_amount
                    );

                    DB::table('payroll_summaries')->updateOrInsert(['payroll_period_id' => $id, 'employee_id' => $employee_id], $payroll_data);
                }
            }

            // Save Audit Trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Process',
                'activity' => 'Process',
                'description' => 'Process Payroll information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'You have successfully process payroll!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process payroll: ' . $e->getMessage());
        }
    }

    public function summary($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $payrolls = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftjoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.ot_amount as ot_pay',
                    'a.nd_amount as nd_pay',
                    'a.holiday_amount as holiday_pay',
                    'a.total_income',
                    'a.gross_amount',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.lwop_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.net_pay',
                    'a.original_netpay',
                    'a.pending_amount_payment',
                    'b.is_hold',
                    'b.hold_remarks'
                )
                ->where('payroll_period_id', $id)
                ->orderBy('b.first_name', 'asc')
                ->get();

            $payrolls_less_netpay = DB::table('payroll_summaries as a')
                ->where('a.payroll_period_id', $id)
                ->where('a.net_pay', '<', 5000)
                ->get();

            $payrolls_adjust_late = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftjoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('branches as g', 'b.branch_id', '=', 'g.id')
                ->join('pending_deductions as e', function ($join) {
                    $join->on('e.payroll_period_id', '=', 'a.payroll_period_id');
                    $join->on('e.employee_id', '=', 'a.employee_id');
                })
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(b.first_name,' ',b.last_name))
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'g.name as branch',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.net_pay',
                    'a.original_netpay',
                    db::raw("'Late' as deduction"),
                    'e.amount'
                )
                ->where(['a.payroll_period_id' => $id, 'is_late' => true])
                ->where('a.original_netpay', '<', 5000);

            $payrolls_adjust_undertime = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftjoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('branches as g', 'b.branch_id', '=', 'g.id')
                ->join('pending_deductions as e', function ($join) {
                    $join->on('e.payroll_period_id', '=', 'a.payroll_period_id');
                    $join->on('e.employee_id', '=', 'a.employee_id');
                })
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(b.first_name,' ',b.last_name))
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'g.name as branch',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.net_pay',
                    'a.original_netpay',
                    db::raw("'Undertime' as deduction"),
                    'e.amount'
                )
                ->where(['a.payroll_period_id' => $id, 'is_undertime' => true])
                ->where('a.original_netpay', '<', 5000);

            $payrolls_adjust = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftjoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('branches as g', 'b.branch_id', '=', 'g.id')
                ->join('pending_deductions as e', function ($join) {
                    $join->on('e.payroll_period_id', '=', 'a.payroll_period_id');
                    $join->on('e.employee_id', '=', 'a.employee_id');
                })
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(b.first_name,' ',b.last_name))
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'g.name as branch',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.net_pay',
                    'a.original_netpay',
                    db::raw("'Absent' as deduction"),
                    'e.amount'
                )
                ->unionAll($payrolls_adjust_late)
                ->unionAll($payrolls_adjust_undertime)
                ->where(['a.payroll_period_id' => $id, 'is_absent' => true])
                ->where('a.original_netpay', '<', 5000)
                ->orderBy('name', 'asc')
                ->get();

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(c.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name")
                )
                ->where(['a.id' => $id])
                ->orderBy('a.release_date', 'desc')
                ->get();

            $payroll_period = $data[0]->name;

            // leave Earned details
            $leave_earned_details = DB::table('employee_leave_earned as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->select(
                    'a.employee_id',
                    'b.employee_no',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                UPPER(CONCAT(b.first_name,' ',b.last_name))
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'c.name as position',
                    db::raw("case when isnull(a.absent,0) <= 0 then 0 else isnull(a.absent,0) end as days_present"),
                    'a.vl_earned',
                    'a.sl_earned'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            $employee_for_tax_adjustments_data = DB::table('employees as a')
                ->join('payroll_summaries as b', 'a.id', '=', 'b.employee_id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            UPPER(CONCAT(a.first_name,' ',a.last_name))
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                        END as name"),
                    'b.tax'
                )
                ->where('b.payroll_period_id', $id);

            $employee_for_tax_adjustments = DB::table('employees as a')
                ->join('payroll_tax_adjustments as b', 'a.id', '=', 'b.employee_id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            UPPER(CONCAT(a.first_name,' ',a.last_name))
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                        END as name"),
                    'b.tax_amount as tax'
                )
                ->where('b.payroll_period_id', $id)
                ->union($employee_for_tax_adjustments_data)
                ->orderBy('name', 'asc')
                ->get();

            return $this->successResponse([
                'id' => $id,
                'payroll_period' => $payroll_period,
                'payrolls' => $payrolls,
                'data' => $data,
                'payrolls_adjust' => $payrolls_adjust,
                'payrolls_less_netpay' => $payrolls_less_netpay,
                'leave_earned_details' => $leave_earned_details,
                'employee_for_tax_adjustments' => $employee_for_tax_adjustments
            ], 'Payroll process summary data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll process summary: ' . $e->getMessage());
        }
    }

    public function posting($id, $type_id)
    {
        try {
            // loan application payment.
            $loans = DB::table('loan_applications as a')
                ->join('payroll_deductions as b', function ($join) {
                    $join->on('b.deduction_id', '=', 'a.deduction_id');
                    $join->on('b.employee_id', '=', 'a.employee_id');
                })
                ->select(
                    'a.id',
                    'b.amount',
                    'a.payment',
                    'a.balance',
                    'a.loan_amount',
                    'a.employee_id',
                    'a.deduction_id'
                )
                ->where([
                    'b.payroll_period_id' => $id,
                    'a.is_approve' => true,
                    'a.is_disapprove' => false
                ])
                ->where('a.balance', '>=', 0)
                ->where('b.amount', '>', 0)
                ->get();

            if ($type_id == 1) {

                if ($loans->isNotEmpty()) {

                    // process loans
                    $loan_data = [];

                    for ($i = 0; $i < count($loans); $i++) {

                        $balance = $loans[$i]->balance - $loans[$i]->amount;
                        $payment = $loans[$i]->payment + $loans[$i]->amount;

                        $loan_data = [
                            'payment' => $payment,
                            'balance' => $balance
                        ];

                        DB::table('loan_applications')->updateOrInsert(['id' => $loans[$i]->id], $loan_data);
                    }
                }

                // post payroll
                DB::table('payroll_periods')->where('id', $id)->update(['posted' => true]);

                // Save Audit Trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Process',
                    'activity' => 'Posting',
                    'description' => 'Posted Payroll information',
                );

                Audit::create($data_audit);
            } else {

                if ($loans->isNotEmpty()) {

                    // process loans
                    $loan_data = [];

                    for ($i = 0; $i < count($loans); $i++) {

                        $balance = $loans[$i]->balance + $loans[$i]->amount;
                        $payment = $loans[$i]->payment - $loans[$i]->amount;

                        $loan_data = [
                            'payment' => $payment,
                            'balance' => $balance
                        ];

                        DB::table('loan_applications')->updateOrInsert(['id' => $loans[$i]->id], $loan_data);
                    }
                }

                // post payroll
                DB::table('payroll_periods')->where('id', $id)->update(['posted' => false]);

                // Save Audit Trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Process',
                    'activity' => 'Posting',
                    'description' => 'Un-posted Payroll information',
                );

                Audit::create($data_audit);
            }

            return $this->successResponse(null, 'You have successfully process payroll!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process payroll posting: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $companies = DB::table('companies')->get();

            $payrolls = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftjoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.ot_amount as ot_pay',
                    'a.nd_amount as nd_pay',
                    'a.holiday_amount as holiday_pay',
                    'a.total_income',
                    'a.gross_amount',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.net_pay'
                )
                ->where('payroll_period_id', $id)
                ->orderBy('b.first_name', 'asc')
                ->get();

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name")
                )
                ->where(['a.id' => $id])
                ->orderBy('a.release_date', 'desc')
                ->get();

            $payroll_period = $data[0]->name;

            // get all income items.
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            // get all deduction items.
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            $pdf = PDF::loadView('payroll_processes.payroll_print', compact('companies', 'payrolls', 'payroll_period', 'incomes', 'deductions'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('Legal', 'landscape');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = "payroll_report_{$id}_" . date('Y-m-d') . ".pdf";

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payroll report: ' . $e->getMessage());
        }
    }

    public function printTabulate($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $companies = DB::table('companies')->get();

            $payrolls = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftjoin('departments as c', 'c.id', '=', 'b.department_id')
                ->leftjoin('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.payroll_period_id',
                    'b.employee_no',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.ot_amount as ot_pay',
                    'a.nd_amount as nd_pay',
                    'a.holiday_amount as holiday_pay',
                    'a.total_income',
                    'a.gross_amount',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.late_amount',
                    'a.ut_amount',
                    'absent_amount',
                    'a.net_pay'
                )
                ->where('payroll_period_id', $id)
                ->orderBy('b.first_name', 'asc')
                ->get();

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    'payroll_start_date',
                    'payroll_end_date',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name")
                )
                ->where(['a.id' => $id])
                ->orderBy('a.release_date', 'desc')
                ->get();

            $payroll_period = date('F d', strtotime($data[0]->payroll_start_date)) . ' - ' . date('d, Y', strtotime($data[0]->payroll_end_date));

            $income_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('incomes as c', 'b.income_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.income_id',
                    'c.name as income'
                )
                ->where('e.id', $id)
                ->where('b.active', true)
                ->distinct()
                ->get();

            $deduction_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('deductions as c', 'b.deduction_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.deduction_id',
                    'c.name as deduction'
                )
                ->where('e.id', $id)
                ->where('b.active', true)
                ->distinct()
                ->get();

            // get all income items.
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'b.id as income_id',
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            // get all deduction items.
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.id as deduction_id',
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            $pdf = PDF::loadView('payroll_processes.payroll_print_tabulate', compact(
                'payrolls',
                'payroll_period',
                'incomes',
                'deductions',
                'companies',
                'data',
                'income_headers',
                'deduction_headers',
                'companies'
            ))->setOptions(['defaultFont' => 'sans-serif']);

            $paper_size = array(0, 0, 500, 2200);
            $pdf->setPaper($paper_size, 'landscape');
            // $pdf->setPaper('Legal', 'landscape');
            $pdfContent = $pdf->output();

            $filename = "payroll_tabulate_report_{$id}_" . date('Y-m-d') . ".pdf";

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payroll tabulate report: ' . $e->getMessage());
        }
    }

    public function payrollSummary()
    {
        try {
            $payroll_intervals = DB::table('payroll_intervals')
                ->where('active', true)
                ->whereIn('id', function ($query) {
                    $query->select('payroll_interval_id')->from('payroll_periods')->where('posted', true)->get();
                })
                ->get();

            $companies = DB::table('companies')->get();
            $branches = DB::table('branches')->get();
            $departments = DB::table('departments')->where('active', true)->orderBy('name', 'asc')->get();

            return $this->successResponse([
                'payroll_intervals' => $payroll_intervals,
                'branches' => $branches,
                'departments' => $departments,
                'companies' => $companies
            ], 'Payroll process print data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll summary data: ' . $e->getMessage());
        }
    }

    public function payrollSummaryDetail()
    {
        try {
            $payroll_intervals = DB::table('payroll_intervals')
                ->where('active', true)
                ->whereIn('id', function ($query) {
                    $query->select('payroll_interval_id')->from('payroll_periods')->where('posted', true)->get();
                })
                ->get();

            $companies = DB::table('companies')->get();
            $branches = DB::table('branches')->get();
            $departments = DB::table('departments')->where('active', true)->orderBy('name', 'asc')->get();

            return $this->successResponse([
                'payroll_intervals' => $payroll_intervals,
                'branches' => $branches,
                'departments' => $departments,
                'companies' => $companies
            ], 'Payroll process detail print data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll summary detail data: ' . $e->getMessage());
        }
    }

    public function payrollSummaryPrint(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $validator = \Validator::make($request->all(), [
                'payroll_interval_id' => 'required',
                'payroll_period_id' => 'required'
            ], [
                'payroll_interval_id.required' => 'Payroll Interval is required.',
                'payroll_period_id.required' => 'Payroll Period is required.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $id = $request->payroll_period_id;
            $department_id = $request->department_id;
            $branch_id = $request->branch_id;

            $companies = DB::table('companies')->get();

            $payrolls = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('departments as c', 'c.id', '=', 'b.department_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.payroll_period_id',
                    'b.employee_no',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'b.department_id',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.ot_amount as ot_pay',
                    'a.nd_amount as nd_pay',
                    'a.holiday_amount as holiday_pay',
                    'a.total_income',
                    'a.gross_amount',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.lwop_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.late_amount',
                    'a.ut_amount',
                    'absent_amount',
                    'a.net_pay',
                    'a.pending_amount_payment'
                )
                ->where('payroll_period_id', $id)
                ->where('b.department_id', $department_id)
                ->orderBy('b.department_id', 'asc')
                ->get();

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    'attendance_start_date',
                    'attendance_end_date',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name")
                )
                ->where(['a.id' => $id])
                ->orderBy('a.release_date', 'desc')
                ->get();

            $payroll_period = date('F d', strtotime($data[0]->attendance_start_date)) . ' - ' . date('d, Y', strtotime($data[0]->attendance_end_date));

            $income_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('incomes as c', 'b.income_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.income_id',
                    'c.name as income'
                )
                ->where('e.id', $id)
                ->where('b.active', true)
                ->whereIn('c.id', function ($query) use ($id) {
                    $query->select('income_id')->from('payroll_incomes')
                        ->where('amount', '>', 0)
                        ->where('payroll_period_id', $id)
                        ->distinct();
                })
                ->distinct()
                ->get();

            $deduction_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('deductions as c', 'b.deduction_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.deduction_id',
                    'c.name as deduction'
                )
                ->where('e.id', $id)
                ->where('b.active', true)
                ->whereIn('c.id', function ($query) use ($id) {
                    $query->select('deduction_id')->from('payroll_deductions')
                        ->where('amount', '>', 0)
                        ->where('payroll_period_id', $id)
                        ->distinct();
                })
                ->distinct()
                ->orderBy('b.deduction_id', 'asc')
                ->get();

            // get all income items.
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'b.id as income_id',
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            // get all deduction items.
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.id as deduction_id',
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $signatories = [
                'signatory_1' => $request->signatory_1,
                'signatory_position_1' => $request->signatory_position_1,
                'signatory_2' => $request->signatory_2,
                'signatory_position_2' => $request->signatory_position_2,
                'signatory_3' => $request->signatory_3,
                'signatory_position_3' => $request->signatory_position_3,
                'signatory_4' => $request->signatory_4,
                'signatory_position_4' => $request->signatory_position_4,
                'signatory_5' => $request->signatory_5,
                'signatory_position_5' => $request->signatory_position_5,
            ];

            $pdf = PDF::loadView('payroll_processes.general_payroll_report', compact(
                'payrolls',
                'payroll_period',
                'incomes',
                'deductions',
                'companies',
                'data',
                'income_headers',
                'deduction_headers',
                'signatories',
                'image',
            ))->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('tabloid', 'landscape');
            $pdfContent = $pdf->output();

            return $this->successResponse([
                'pdf_content' => base64_encode($pdfContent),
                'filename' => 'payroll_summary_report.pdf',
                'content_type' => 'application/pdf',
                'file_size' => strlen($pdfContent)
            ], 'Payroll summary report generated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payroll summary report: ' . $e->getMessage());
        }
    }

    public function payrollSummaryDetailsPrint(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $validator = \Validator::make($request->all(), [
                'payroll_interval_id' => 'required',
                'payroll_period_id' => 'required'
            ], [
                'payroll_interval_id.required' => 'Payroll Interval is required.',
                'payroll_period_id.required' => 'Payroll Period is required.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $id = $request->payroll_period_id;
            $department_id = $request->department_id;
            $branch_id = $request->branch_id;

            $companies = DB::table('companies')->get();

            $payrolls = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('departments as c', 'c.id', '=', 'b.department_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.payroll_period_id',
                    'b.employee_no',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'b.employee_no',
                    'b.department_id',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.ot_amount as ot_pay',
                    'a.nd_amount as nd_pay',
                    'a.holiday_amount as holiday_pay',
                    'a.total_income',
                    'a.gross_amount',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    'a.lwop_amount',
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.late_amount',
                    'a.ut_amount',
                    'absent_amount',
                    'a.net_pay',
                    'a.pending_amount_payment'
                )
                ->where('payroll_period_id', $id)
                ->where('b.department_id', $department_id)
                ->orderBy('b.department_id', 'asc')
                ->get();

            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    'attendance_start_date',
                    'attendance_end_date',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name")
                )
                ->where(['a.id' => $id])
                ->orderBy('a.release_date', 'desc')
                ->get();

            $payroll_period = date('F d', strtotime($data[0]->attendance_start_date)) . ' - ' . date('d, Y', strtotime($data[0]->attendance_end_date));

            $income_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('incomes as c', 'b.income_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.income_id',
                    'c.name as income'
                )
                ->where('e.id', $id)
                ->where('b.active', true)
                ->whereIn('c.id', function ($query) use ($id) {
                    $query->select('income_id')->from('payroll_incomes')
                        ->where('amount', '>', 0)
                        ->where('payroll_period_id', $id)
                        ->distinct();
                })
                ->distinct()
                ->get();

            $deduction_headers = DB::table('payroll_item_schedule_headers as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.payroll_item_schedule_header_id')
                ->join('deductions as c', 'b.deduction_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'a.payroll_interval_type_id', '=', 'd.id')
                ->join('payroll_periods as e', 'd.id', '=', 'e.payroll_interval_id')
                ->select(
                    'b.deduction_id',
                    'c.mfo_pap',
                    'c.uacs',
                    'c.name as deduction'
                )
                ->where('e.id', $id)
                ->where('b.active', true)
                ->whereIn('c.id', function ($query) use ($id) {
                    $query->select('deduction_id')->from('payroll_deductions')
                        ->where('amount', '>', 0)
                        ->where('payroll_period_id', $id)
                        ->distinct();
                })
                ->distinct()
                ->orderBy('b.deduction_id', 'asc')
                ->get();

            // get all income items.
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'b.id as income_id',
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            // get all deduction items.
            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.id as deduction_id',
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.payroll_period_id', $id)
                ->get();

            // get all deduction items total.
            $deduction_totals = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.id as deduction_id',
                    'b.name',
                    DB::raw("SUM(a.amount) as amount")
                )
                ->where('a.payroll_period_id', $id)
                ->groupBy([
                    'b.id',
                    'b.name',
                ])
                ->get();

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $signatories = [
                'signatory_1' => $request->signatory_1,
                'signatory_position_1' => $request->signatory_position_1,
                'signatory_2' => $request->signatory_2,
                'signatory_position_2' => $request->signatory_position_2,
                'signatory_3' => $request->signatory_3,
                'signatory_position_3' => $request->signatory_position_3,
                'signatory_4' => $request->signatory_4,
                'signatory_position_4' => $request->signatory_position_4,
                'signatory_5' => $request->signatory_5,
                'signatory_position_5' => $request->signatory_position_5,
            ];

            $payroll_period_1 = date('F', strtotime($data[0]->attendance_start_date)) . ' 1-15 ' . date('Y', strtotime($data[0]->attendance_end_date));

            $paroll_cutoffs = DB::table('payroll_periods as a')
                ->join('payroll_cutoffs as b', 'a.payroll_cutoff_id', '=', 'b.id')
                ->where('a.id', $id)
                ->whereRaw("b.name LIKE '2nd%'")
                ->get();

            if ($paroll_cutoffs->isNotEmpty()) {
                $is_second_half = true;
            } else {
                $is_second_half = false;
            }

            $pdf = PDF::loadView('payroll_processes.general_payroll_with_deductions_report', compact(
                'payrolls',
                'payroll_period_1',
                'payroll_period',
                'incomes',
                'deductions',
                'companies',
                'data',
                'income_headers',
                'deduction_headers',
                'deduction_totals',
                'signatories',
                'image',
                'is_second_half'
            ))->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper([0, 0, 900.44, 1842.07], 'landscape');
            $pdfContent = $pdf->output();

            return $this->successResponse([
                'pdf_content' => base64_encode($pdfContent),
                'filename' => 'payroll_summary_details_report.pdf',
                'content_type' => 'application/pdf',
                'file_size' => strlen($pdfContent)
            ], 'Payroll summary details report generated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payroll summary details report: ' . $e->getMessage());
        }
    }

    public function adjustment(Request $request, $payroll_period_id)
    {
        try {
            $employee_data = $request->all();
            $data = [];

            for ($i = 0; $i < count($employee_data["employee_id"]); $i++) {
                $data = [
                    'tax_amount' => $employee_data['tax_amount'][$i],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                DB::table('payroll_tax_adjustments')->updateOrInsert([
                    'employee_id' => $employee_data['employee_id'][$i],
                    'payroll_period_id' => $payroll_period_id,
                ], $data);

                $payroll = DB::table('payroll_summaries')->where([
                    'employee_id' => $employee_data['employee_id'][$i],
                    'payroll_period_id' => $payroll_period_id,
                ])->get();

                if ($payroll->isNotEmpty()) {
                    $net_pay = ($payroll[0]->gross_amount - ($payroll[0]->gsis + $payroll[0]->sss + $payroll[0]->pagibig + $payroll[0]->philhealth + $employee_data['tax_amount'][$i] + $payroll[0]->late_amount + $payroll[0]->ut_amount + $payroll[0]->absent_amount + $payroll[0]->total_deduction));
                }

                DB::table('payroll_summaries')->where([
                    'employee_id' => $employee_data['employee_id'][$i],
                    'payroll_period_id' => $payroll_period_id,
                ])->update([
                    'tax' => $employee_data['tax_amount'][$i],
                    'net_pay' => $net_pay,
                ]);
            }

            return $this->successResponse(['payroll_period_id' => $payroll_period_id], 'Successfully Adjusted Tax Amounts.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to adjust tax amounts: ' . $e->getMessage());
        }
    }
}
