<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">

    <title>Payroll Payment Slip</title>

    <style>
        html,
        body {
            height: 297mm;
            width: 210mm;
            margin: 0 auto;
            font-size: 14px;
            font-family:Georgia, 'Times New Roman', Times, serif;
            padding: 10mm;
        }

        p {
            padding: 0;
            margin: 2px 0;
        }

        .bold-text {
            font-weight: bold;
        }

        .company-header {
            color: #000080;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 18px;
            margin: 10px 0;
            font-family: Arial, sans-serif;
            letter-spacing: 0.5px;
        }

        .payslip-title {
            color: #000080;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 18px;
            margin: 5px 0 15px 0;
            font-family: Arial, sans-serif;
            letter-spacing: 0.3px;
        }

        .employee-name {
            color: #cc0000;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 16px;
            margin: 10px 0 5px 0;
        }

        .section-title {
            font-weight: bold;
            margin: 15px 0 10px 0;
            color: #000080;
            font-size: 16px;
            font-style: italic;
        }

        .earnings-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .earnings-table td {
            padding: 4px 8px;
            text-align: left;
        }

        .earnings-table .amount {
            text-align: right;
        }

        .earnings-table .time-columns {
            text-align: center;
            width: 60px;
        }

        .earnings-table .total-row {
            border-top: 1px solid #000;
            font-weight: bold;
            padding-top: 5px;
        }

        .summary-section {
            margin: 20px 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-weight: bold;
        }

        .summary-label {
            flex: 1;
            color: #000080;
            font-style: italic;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 4.5px;
            font-family:Georgia, 'Times New Roman', Times, serif;
        }

        .summary-amount {
            text-align: right;
            min-width: 120px;
            margin-left: 30px;
            font-family: 'Arial', sans-serif;
        }

        .pay-distribution {
            width: 100%;
            margin-top: 15px;
        }

        .pay-item {
            display: inline-block;
            width: 48%;
            vertical-align: top;
            margin-right: 2%;
        }

        .pay-item:last-child {
            margin-right: 0;
        }

        .pay-item-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-weight: bold;
        }
        .label {
            text-align: left;
            padding: 4px 8px;
            width: 200px;
            min-width: 200px;
            color: #000080;
            font-style: italic;
            font-size: 16px;
        }

        .label2 {
            padding: 4px 8px;
            text-align: left;
            color: #000080;
            font-style: italic;
            font-size: 16px;
        }
        .label3 {
            text-align: left; 
            padding: 3px 0;
            color: #000080;
            font-style: italic;
            font-size: 16px;
        }
        .label4 {
            text-align: left; 
            padding: 3px 0;
            color: #000080;
            font-style: italic;
            font-size: 16px;
        }
        .label5 {
            color: #000080; 
            font-style: italic;
            font-size: 16px;
            letter-spacing: 4.5px;
            font-family:Georgia, 'Times New Roman', Times, serif;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @php
        if (!function_exists('getDeductionAmount')) {
            function getDeductionAmount($deductions, $searchTerm) {
                if(!isset($deductions) || count($deductions) == 0) {
                    return 0;
                }
                $total = 0;
                $searchUpper = strtoupper(trim($searchTerm));
                foreach($deductions as $deduction) {
                    $deductionName = strtoupper(trim($deduction->deduction ?? ''));
                    if($deductionName === $searchUpper || strpos($deductionName, $searchUpper) !== false) {
                        $total += $deduction->amount ?? 0;
                    }
                }
                return $total;
            }
        }
    @endphp
    @foreach ($payrolls as $payroll)
        <div>
            <!-- Header Section -->
            <div style="font-style: italic; margin-bottom: 15px;">
                <p class="company-header">{{ $orgCompanyName }}</p>
                <p class="payslip-title">PERMANENT EMPLOYEE PAY SLIP</p>
            </div>

            <!-- Period and Employee Info -->
            <div style="margin-bottom: 15px;">
                <p>For the month of {{ date('F, Y', strtotime($payroll->payroll_period ?? 'now')) }}</p>
                <p class="employee-name">{{ strtoupper($payroll->name) }}</p>
                <p>{{ strtoupper($payroll->department ?? '') }}</p>
            </div>

            <!-- Earnings Section -->
            <div>
                @php

                $display_gross = $payroll->gross_amount ?? (
                        ($payroll->salary ?? 0)
                        + ($payroll->holiday_pay ?? 0)
                        + ($payroll->total_income ?? 0)
                        + ($payroll->ot_pay ?? 0)
                        + ($payroll->nd_pay ?? 0)
                    );

                     $net_pay = (float) ($payroll->net_pay ?? 0);

                    // Base total deductions primarily on the actual arithmetic: Gross - Net.
                    // This guarantees that: TOTAL EARNINGS - TOTAL DEDUCTIONS = NET PAY (up to rounding).
                    $computed_total_deductions = max(0, (float) $display_gross - (float) $net_pay);


                    // Extract specific income amounts from incomes array
                    $pera_amount = 0;
                    $step_increment_amount = 0;
                    $differential_amount = 0;
                    $additional_compensation_amount = 0;
                    
                    if(isset($incomes) && count($incomes) > 0) {
                        foreach($incomes as $income) {
                            $income_name = strtoupper(trim($income->income ?? ''));
                            if(strpos($income_name, 'PERA') !== false) {
                                $pera_amount += $income->amount ?? 0;
                            } elseif(strpos($income_name, 'STEP') !== false || strpos($income_name, 'INCREMENT') !== false) {
                                $step_increment_amount += $income->amount ?? 0;
                            } elseif(strpos($income_name, 'DIFFERENTIAL') !== false) {
                                $differential_amount += $income->amount ?? 0;
                            } elseif(strpos($income_name, 'ADDITIONAL') !== false || strpos($income_name, 'ADD\'L') !== false || strpos($income_name, 'ADD\'L COMP') !== false) {
                                $additional_compensation_amount += $income->amount ?? 0;
                            }
                        }
                    }
                    
                    // Calculate total earnings
                    // Use gross_amount from payroll_summaries so that TOTAL matches
                    // the gross used in payroll processing (basic + incomes + OT + holiday + ND + adjustments).
                    $basic_pay = $payroll->salary ?? 0;
                    $total_earnings = $payroll->gross_amount ?? (
                        ($payroll->salary ?? 0)
                        + ($payroll->holiday_pay ?? 0)
                        + ($payroll->ot_pay ?? 0)
                        + ($payroll->nd_pay ?? 0)
                    );

                    // Current period only (merged month when two halves); preceding shown as PRECEDING DEDUCTION.
                    $late_period = (float) ($payroll->late_amount ?? 0);
                    $ut_period = (float) ($payroll->ut_amount ?? 0);
                    $preceding_deduction_total = (float) ($payroll->preceding_deduction_total ?? (
                        (float) ($payroll->preceding_late_amount ?? 0)
                        + (float) ($payroll->preceding_ut_amount ?? 0)
                        + (float) ($payroll->preceding_absent_amount ?? 0)
                    ));
                    $lwop_amount = (float) ($payroll->lwop_amount ?? 0);

                @endphp
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <!-- Earnings Column-->
                        <td style="width: 60%; vertical-align: top;">
                            <table style="border-collapse: collapse:">
                                <tr>
                                    <td class="label">BASIC</td>
                                    <td style="text-align: right; padding: 4px 8px; font-family: 'Arial', sans-serif;">{{ number_format($basic_pay, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">STEP INC.</td>
                                    <td style="text-align: right; padding: 4px 8px; font-family: 'Arial', sans-serif;">{{ number_format($step_increment_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">DIFFERENTIAL</td>
                                    <td style="text-align: right; padding: 4px 8px; font-family: 'Arial', sans-serif;">{{ number_format($differential_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">PERA</td>
                                    <td style="text-align: right; padding: 4px 8px; font-family: 'Arial', sans-serif;">{{ number_format($pera_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">ADD'L COMP.</td>
                                    <td style="text-align: right; padding: 4px 8px; font-family: 'Arial', sans-serif;">{{ number_format($additional_compensation_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr style="border-top: 1px solid #000;">
                                    <td style="text-align: left; padding: 4px 8px; font-weight: bold; color: #000080; font-style: italic; font-size: 16px;">TOTAL</td>
                                    <td style="text-align: right; padding: 4px 8px; font-weight: bold; font-family: 'Arial', sans-serif;">{{ number_format($total_earnings, 2, '.', ',') }}</td>
                                </tr>
                            </table>
                        </td>

                        <!-- Late / Undertime Column-->
                        <td style="width: 70%; vertical-align: top; padding-left: 20px;">
                            <table style="border-collapse: collapse;">
                                <tr>
                                    <td class="label2">DAY</td>
                                    <td style="text-align: right; padding: 3px 0; width: 100px; font-family: 'Arial', sans-serif;">0</td>
                                </tr>
                                <tr>
                                    <td class="label2">HOUR</td>
                                    <td style="text-align: right; padding: 3px 0; width: 100px; font-family: 'Arial', sans-serif;">0</td>
                                </tr>
                                <tr><td style="padding: 4px 8px;"></td><td style="padding: 4px 8px;"></td></tr>
                                <tr><td style="padding: 4px 8px;"></td><td style="padding: 4px 8px;"></td></tr>
                                <tr>
                                    <td class="label2">MINUTE</td>
                                    <td style="text-align: right; padding: 3px 0; width: 100px; font-family: 'Arial', sans-serif;">0</td>
                                </tr>
                                <tr>
                                    <td class="label2">LATE</td>
                                    <td style="text-align: right; padding: 3px 0; width: 100px; font-family: 'Arial', sans-serif;">{{ number_format($late_period, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label2">UNDERTIME</td>
                                    <td style="text-align: right; padding: 3px 0; width: 100px; font-family: 'Arial', sans-serif;">{{ number_format($ut_period, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 8px; text-align: left; font-weight: bold; color: #000080; font-style:italic; font-size: 16px;">NET SALARY</td>
                                    <td style="text-align: right; padding: 3px 0; width: 150px; font-weight: bold; font-family: 'Arial', sans-serif;">{{ number_format($net_pay, 2, '.', ',') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>


            <!-- Deductions Section -->
            <div>
                @php
                    // Predefined deduction categories to match
                    $predefined_deduction_terms = [
                        'G E', 'CPL', 'PAG-IBIG LOAN', 'OPT LIFE INS', 'GSIS MPLite',
                        'CONSO LOAN', 'POL LOAN', 'P-BIG HSG', 'HMO', 'PAGCAL',
                        'PAGIBIG CALAMITY', 'CALAMITY LOAN',
                        'GSIS LC HSG LOAN', 'GSIS PABAHAY', 'E-CASH Plus', 'COOP',
                        'ESP', 'PAGIBIG MP2', 'AP', 'RES SAL LOAN', 'EMERGENCY LOAN', 'E A'
                    ];
                    
                    // Map deduction names to search terms
                    $ge_amount = getDeductionAmount($deductions ?? [], 'G E');
                    $cpl_amount = getDeductionAmount($deductions ?? [], 'CPL');
                    $pagibig_loan_amount = getDeductionAmount($deductions ?? [], 'PAG-IBIG LOAN');
                    $opt_life_ins_amount = getDeductionAmount($deductions ?? [], 'OPT LIFE INS');
                    $gsis_mplite_amount = getDeductionAmount($deductions ?? [], 'GSIS MPLite');
                    $conso_loan_amount = getDeductionAmount($deductions ?? [], 'CONSO LOAN');
                    $pol_loan_amount = getDeductionAmount($deductions ?? [], 'POL LOAN');
                    $pbig_hsg_amount = getDeductionAmount($deductions ?? [], 'P-BIG HSG');
                    $hmo_amount = getDeductionAmount($deductions ?? [], 'HMO');
                    $pagcal_amount = getDeductionAmount($deductions ?? [], 'PAGCAL') 
                    ?: getDeductionAmount($deductions ?? [], 'PAGIBIG CALAMITY')
                    ?: getDeductionAmount($deductions ?? [], 'CALAMITY LOAN');
                    $gsis_lc_hsg_loan_amount = getDeductionAmount($deductions ?? [], 'GSIS LC HSG LOAN');
                    $gsis_pabahay_amount = getDeductionAmount($deductions ?? [], 'GSIS PABAHAY');
                    $ecash_plus_amount = getDeductionAmount($deductions ?? [], 'E-CASH Plus');
                    $coop_amount = getDeductionAmount($deductions ?? [], 'COOP');
                    $esp_amount = getDeductionAmount($deductions ?? [], 'ESP');
                    $pagibig_mp2_amount = getDeductionAmount($deductions ?? [], 'PAGIBIG MP2');
                    $ap_amount = getDeductionAmount($deductions ?? [], 'AP');
                    $res_sal_loan_amount = getDeductionAmount($deductions ?? [], 'RES SAL LOAN');
                    $emergency_loan_amount = getDeductionAmount($deductions ?? [], 'EMERGENCY LOAN');
                    $ea_amount = getDeductionAmount($deductions ?? [], 'E A');
                    
                    // Filter out deductions that match predefined categories for additional section
                    $additional_deductions_filtered = [];
                    if(isset($deductions) && count($deductions) > 0) {
                        foreach($deductions as $deduction) {
                            $deductionName = strtoupper(trim($deduction->deduction ?? ''));
                            $is_predefined = false;
                            foreach($predefined_deduction_terms as $term) {
                                $termUpper = strtoupper(trim($term));
                                if($deductionName === $termUpper || strpos($deductionName, $termUpper) !== false) {
                                    $is_predefined = true;
                                    break;
                                }
                            }
                            if(!$is_predefined) {
                                $additional_deductions_filtered[] = $deduction;
                            }
                        }
                    }
                    $additional_deductions_filtered = array_values($additional_deductions_filtered);
                    $additional_midpoint = (int) ceil(count($additional_deductions_filtered) / 2);
                    $additional_deductions_left = array_slice($additional_deductions_filtered, 0, $additional_midpoint);
                    $additional_deductions_right = array_slice($additional_deductions_filtered, $additional_midpoint);
                @endphp
                <p class="section-title">Deductions:</p>
                <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                    <tr>
                        <!-- Left Column -->
                        <td style="width: 50%; vertical-align: top; padding-right: 15px;">
                            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                                <tr>
                                    <td class="label3">LIFE and RET.</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($payroll->gsis ?? 0, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">PHILHEALTH</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($payroll->philhealth ?? 0, 2, '.', ',') }}</td>
                                </tr>
                                @php
                                    $period_absent_amount = (float) ($payroll->absent_amount ?? 0);
                                    $lwop_amount = $payroll->lwop_amount ?? 0;
                                @endphp
                                @if($lwop_amount > 0)
                                <tr>
                                    <td class="label3">LWOP</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($lwop_amount, 2, '.', ',') }}</td>
                                </tr>
                                @endif
                                @if(abs($period_absent_amount) > 0.00001)
                                <tr>
                                    <td class="label3">ABSENT</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($period_absent_amount, 2, '.', ',') }}</td>
                                </tr>
                                @endif
                                @if(abs($preceding_deduction_total) > 0.00001)
                                <tr>
                                    <td class="label3" style="white-space: nowrap;">PRECEDING DEDUCTION</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($preceding_deduction_total, 2, '.', ',') }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="label3">G E</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($ge_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">CPL</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($cpl_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">PAG-IBIG PREM.</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($payroll->pagibig ?? 0, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">PAG-IBIG LOAN</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($pagibig_loan_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">OPT LIFE INS.</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($opt_life_ins_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">GSIS MPLite</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($gsis_mplite_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">CONSO LOAN</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($conso_loan_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">POL LOAN</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($pol_loan_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">P-BIG HSG</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($pbig_hsg_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">HMO</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($hmo_amount, 2, '.', ',') }}</td>
                                </tr>
                                @foreach ($additional_deductions_left as $deduction)
                                    <tr>
                                        <td class="label3" style="width: 70%; white-space: nowrap;">{{ strtoupper($deduction->deduction) }}</td>
                                        <td style="text-align: left; width: 30%; font-family: 'Arial', sans-serif; white-space: nowrap;">{{ number_format($deduction->amount, 2, '.', ',') }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                        
                        <!-- Right Column -->
                        <td style="width: 50%; vertical-align: top;">
                            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                                <tr>
                                    <td class="label4">PAGCAL</td>
                                    <td style="text-align: left; padding: 3px 0; width: 110px; font-family: 'Arial', sans-serif;">{{ number_format($pagcal_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">GSIS LC HSG LOAN</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($gsis_lc_hsg_loan_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">GSIS PABAHAY</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($gsis_pabahay_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">E-CASH Plus</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($ecash_plus_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">COOP</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($coop_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">ESP</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($esp_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">PAGIBIG MP2</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($pagibig_mp2_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">W/TAX</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($payroll->tax ?? 0, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">AP</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($ap_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">RES SAL LOAN</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($res_sal_loan_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">EMERGENCY LOAN</td>
                                    <td style="text-align: left; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($emergency_loan_amount, 2, '.', ',') }}</td>
                                </tr>
                                {{-- <tr>
                                    <td class="label4">E A</td>
                                    <td style="text-align: right; padding: 3px 0; font-family: 'Arial', sans-serif;">{{ number_format($ea_amount, 2, '.', ',') }}</td>
                                </tr> --}}
                                @foreach ($additional_deductions_right as $deduction)
                                    <tr>
                                        <td class="label4" style="width: 70%; white-space: nowrap;">{{ strtoupper($deduction->deduction) }}</td>
                                        <td style="text-align: left;  width: 30%; font-family: 'Arial', sans-serif; white-space: nowrap;">{{ number_format($deduction->amount, 2, '.', ',') }}</td>
                                    </tr>
                                @endforeach
                            
                            </table>
                        </td>
                    </tr>
                </table>

                {{-- <!-- Additional deductions from database (non-predefined categories) -->
                @if(isset($additional_deductions_filtered) && count($additional_deductions_filtered) > 0)
                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                        <tr>
                            <td style="width: 50%; vertical-align: top; padding-right: 15px;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    @foreach ($additional_deductions_filtered as $index => $deduction)
                                        @if($index % 2 == 0)
                                            <tr>
                                                <td style="text-align: left; padding: 3px 0; color: #000080; font-style: italic; font-weight: bold; font-size: 16px;">{{ strtoupper($deduction->deduction) }}</td>
                                                <td style="text-align: right; padding: 3px 0; width: 100px; font-family: 'Arial', sans-serif;">{{ number_format($deduction->amount, 2, '.', ',') }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </table>
                            </td>
                            @php $has_right_col = collect($additional_deductions_filtered)->keys()->contains(fn($i) => $i % 2 == 1); @endphp
                            <td style="width: 50%; vertical-align: top; padding-left: 15px; {{ $has_right_col ? 'border-left: 1px solid #000;' : '' }}">
                                <table style="width: 100%; border-collapse: collapse;">
                                    @foreach ($additional_deductions_filtered as $index => $deduction)
                                        @if($index % 2 == 1)
                                            <tr>
                                                <td style="text-align: left; padding: 3px 0; color: #000080; font-style: italic; font-weight: bold; font-size: 16px;">{{ strtoupper($deduction->deduction) }}</td>
                                                <td style="text-align: right; padding: 3px 0; width: 100px; font-family: 'Arial', sans-serif;">{{ number_format($deduction->amount, 2, '.', ',') }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    </table>
                @endif --}}
            </div>

            <!-- Summary Section -->
            <div class="summary-section">
                @php
                    // Reconstruct the gross used for net pay to keep the arithmetic consistent on the payslip.
                    $display_gross = $payroll->gross_amount ?? (
                        ($payroll->salary ?? 0)
                        + ($payroll->holiday_pay ?? 0)
                        + ($payroll->total_income ?? 0)
                        + ($payroll->ot_pay ?? 0)
                        + ($payroll->nd_pay ?? 0)
                    );

                    $standard_deductions = ($payroll->gsis ?? 0) + 
                                         ($payroll->sss ?? 0) + 
                                         ($payroll->pagibig ?? 0) + 
                                         ($payroll->philhealth ?? 0) + 
                                         ($payroll->tax ?? 0);

                    $first_half_tardiness_total = property_exists($payroll, 'first_half_tardiness_total')
                        ? ($payroll->first_half_tardiness_total ?? 0)
                        : (($payroll->late_amount ?? 0) + ($payroll->ut_amount ?? 0) + ($payroll->absent_amount ?? 0));
                    
                    $additional_deductions_total = 0;
                    if(isset($deductions) && count($deductions) > 0) {
                        foreach($deductions as $deduction) {
                            $additional_deductions_total += $deduction->amount ?? 0;
                        }
                    }

                    // Use backend-computed net pay (already includes all deduction logic like
                    // preceding period adjustment and attendance-based deductions).
                    $net_pay = (float) ($payroll->net_pay ?? 0);

                    // Base total deductions primarily on the actual arithmetic: Gross - Net.
                    // This guarantees that: TOTAL EARNINGS - TOTAL DEDUCTIONS = NET PAY (up to rounding).
                    $computed_total_deductions = max(0, (float) $display_gross - (float) $net_pay);

                    $computed_total_deduction = $standard_deductions;

                    // Display TOTAL DEDUCTIONS consistently with the backend net pay.
                    $total_deductions = $computed_total_deductions;

                    // if ($computed_total_deductions > 0) {
                    //     $total_deductions = $computed_total_deductions;
                    // } else {
                    //     $total_deductions = $component_total_deductions;
                    // }
                    
                    $hasFirstPay = is_object($payroll) && property_exists($payroll, 'first_pay');
                    $hasSecondPay = is_object($payroll) && property_exists($payroll, 'second_pay');
                    $first_pay = $hasFirstPay ? ($payroll->first_pay ?? 0) : min(15000, $net_pay);
                    $second_pay = $hasSecondPay ? ($payroll->second_pay ?? 0) : max(0, $net_pay - $first_pay);
                @endphp

                <div class="summary-row">
                    <span class="summary-label">TOTAL DEDUCTIONS</span>
                    <span class="summary-amount">{{ number_format($total_deductions, 2, '.', ',') }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">NET PAY:</span>
                    {{-- <span>Total Gross - Total Deductions</span> --}}
                    <span class="summary-amount">{{ number_format($net_pay, 2, '.', ',') }}</span>
                </div>

                <!-- Pay Distribution -->
                <div class="pay-distribution">
                    <div class="pay-item">
                        <div class="pay-item-row">
                            <span class="label5">FIRST PAY:</span>
                            {{-- <span>Net Pay / 2</span> --}}
                            <span style="margin-left: 30px; font-family: 'Arial', sans-serif;">{{ number_format($first_pay, 2, '.', ',') }}</span>
                        </div>
                    </div>
                    <div class="pay-item">
                        <div class="pay-item-row">
                            <span class="label5">SECOND PAY</span>
                            <span style="margin-left: 30px; font-family: 'Arial', sans-serif;">{{ number_format($second_pay, 2, '.', ',') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
