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
            font-family: 'Arial', sans-serif;
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
            color: #0f056b;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 18px;
            margin: 10px 0;
            font-family: Arial, sans-serif;
            letter-spacing: 0.5px;
        }

        .payslip-title {
            color: #0f056b;
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
            color: #0f056b;
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
            color: #0f056b;
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
            color: #0f056b;
        }

        .label2 {
            padding: 4px 8px;
            text-align: left;
            color: #0f056b;
        }
        .label3 {
            text-align: left;
            padding: 3px 0;
            color: #0f056b;
        }
        .label4 {
            text-align: left;
            padding: 3px 0;
            color: #0f056b;
        }
        .label5 {
            font-weight: bold;
            color: #0f056b;
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
                <p class="company-header">{{ $companies[0]->name ?? 'PAYSLIP' }}</p>
                <p class="payslip-title">PERMANENT EMPLOYEE PAY SLIP</p>
            </div>

            <!-- Period and Employee Info -->
            <div style="margin-bottom: 15px;">
                <p>For the month of {{ $payroll->payroll_period ?? date('F, Y', strtotime($payroll->release_date ?? 'now')) }}</p>
                <p class="employee-name">{{ strtoupper($payroll->name) }}</p>
                <p>{{ strtoupper($payroll->department ?? '') }}</p>
            </div>

            <!-- Earnings Section -->
            <div>
                @php
                    // Extract specific income amounts from incomes array
                    $pera_amount = 0;
                    $step_increment_amount = 0;
                    $differential_amount = 0;
                    $additional_compensation_amount = 0;
                    $other_incomes = []; // catch-all for incomes that don't match a fixed row

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
                            } else {
                                // Any other income type gets its own line
                                $other_incomes[] = [
                                    'name'   => $income->income ?? '',
                                    'amount' => $income->amount ?? 0,
                                ];
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
                        + ($payroll->total_income ?? 0)
                        + ($payroll->ot_pay ?? 0)
                        + ($payroll->nd_pay ?? 0)
                    );
                @endphp
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <!-- Earnings Column-->
                        <td style="width: 60%; vertical-align: top;">
                            <table style="border-collapse: collapse:">
                                <tr>
                                    <td class="label">BASIC</td>
                                    <td style="text-align: right; padding: 4px 8px;">{{ number_format($basic_pay, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">STEP INC.</td>
                                    <td style="text-align: right; padding: 4px 8px;">{{ number_format($step_increment_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">DIFFERENTIAL</td>
                                    <td style="text-align: right; padding: 4px 8px;">{{ number_format($differential_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">PERA</td>
                                    <td style="text-align: right; padding: 4px 8px;">{{ number_format($pera_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">ADD'L COMP.</td>
                                    <td style="text-align: right; padding: 4px 8px;">{{ number_format($additional_compensation_amount, 2, '.', ',') }}</td>
                                </tr>
                                @foreach($other_incomes as $oi)
                                <tr>
                                    <td class="label">{{ $oi['name'] }}</td>
                                    <td style="text-align: right; padding: 4px 8px;">{{ number_format($oi['amount'], 2, '.', ',') }}</td>
                                </tr>
                                @endforeach
                                <tr style="border-top: 1px solid #000;">
                                    <td style="text-align: left; padding: 4px 8px; font-weight: bold; color: #0f056b; font-style: italic;">TOTAL</td>
                                    <td style="text-align: right; padding: 4px 8px; font-weight: bold;">{{ number_format($total_earnings, 2, '.', ',') }}</td>
                                </tr>
                            </table>
                        </td>

                        <!-- Late / Undertime Column-->
                        <td style="width: 70%; vertical-align: top; padding-left: 20px;">
                            <table style="border-collapse: collapse;">
                                <tr>
                                    <td class="label2">DAY</td>
                                    <td style="text-align: right; padding: 3px 0; width: 100px;">0</td>
                                </tr>
                                <tr>
                                    <td class="label2">HOUR</td>
                                    <td style="text-align: right; padding: 3px 0; width: 100px;">0</td>
                                </tr>
                                <tr><td style="padding: 4px 8px;"></td><td style="padding: 4px 8px;"></td></tr>
                                <tr><td style="padding: 4px 8px;"></td><td style="padding: 4px 8px;"></td></tr>
                                <tr>
                                    <td class="label2">MINUTE</td>
                                    <td style="text-align: right; padding: 3px 0; width: 100px;">0</td>
                                </tr>
                                <tr>
                                    <td class="label2">LATE</td>
                                    <td style="text-align: right; padding: 3px 0; width: 100px;">{{ number_format($payroll->late_amount ?? 0, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label2">UNDERTIME</td>
                                    <td style="text-align: right; padding: 3px 0; width: 100px;">{{ number_format($payroll->ut_amount ?? 0, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 8px; text-align: left; font-weight: bold; color: #0f056b; font-style:italic;">NET SALARY</td>
                                    <td style="text-align: right; padding: 3px 0; width: 150px; font-weight: bold;">{{ number_format($payroll->net_pay ?? 0, 2, '.', ',') }}</td>
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
                    $pagcal_amount = getDeductionAmount($deductions ?? [], 'PAGCAL');
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
                @endphp
                <p class="section-title">Deductions:</p>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <!-- Left Column -->
                        <td style="width: 50%; vertical-align: top; padding-right: 15px;">
                            <table style="border-collapse: collapse;">
                                <tr>
                                    <td class="label3">LIFE and RET.</td>
                                    <td style="text-align: right; padding: 3px 0; width: 180px;">{{ number_format($payroll->gsis ?? 0, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">PHILHEALTH</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($payroll->philhealth ?? 0, 2, '.', ',') }}</td>
                                </tr>
                                @php
                                    $first_half_absent = $payroll->absent_amount ?? 0;
                                    $lwop_amount = $payroll->lwop_amount ?? 0;
                                @endphp
                                @if($lwop_amount > 0)
                                <tr>
                                    <td class="label3">LWOP</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($lwop_amount, 2, '.', ',') }}</td>
                                </tr>
                                @endif
                                @if($first_half_absent > 0)
                                <tr>
                                    <td class="label3">ABSENT</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($first_half_absent, 2, '.', ',') }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="label3">G E</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($ge_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">CPL</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($cpl_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">PAG-IBIG PREM.</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($payroll->pagibig ?? 0, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">PAG-IBIG LOAN</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($pagibig_loan_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">OPT LIFE INS.</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($opt_life_ins_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">GSIS MPLite</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($gsis_mplite_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">CONSO LOAN</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($conso_loan_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">POL LOAN</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($pol_loan_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">P-BIG HSG</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($pbig_hsg_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label3">HMO</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($hmo_amount, 2, '.', ',') }}</td>
                                </tr>
                            </table>
                        </td>

                        <!-- Right Column -->
                        <td style="width: 55%; vertical-align: top; padding-left: 15px;">
                            <table style="border-collapse: collapse;">
                                <tr>
                                    <td class="label4">PAGCAL</td>
                                    <td style="text-align: right; padding: 3px 0; width: 110px;">{{ number_format($pagcal_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">GSIS LC HSG LOAN</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($gsis_lc_hsg_loan_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">GSIS PABAHAY</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($gsis_pabahay_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">E-CASH Plus</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($ecash_plus_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">COOP</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($coop_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">ESP</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($esp_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">PAGIBIG MP2</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($pagibig_mp2_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">W/TAX</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($payroll->tax ?? 0, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">AP</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($ap_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">RES SAL LOAN</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($res_sal_loan_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">EMERGENCY LOAN</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($emergency_loan_amount, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td class="label4">E A</td>
                                    <td style="text-align: right; padding: 3px 0;">{{ number_format($ea_amount, 2, '.', ',') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Additional deductions from database (non-predefined categories) -->
                @if(isset($additional_deductions_filtered) && count($additional_deductions_filtered) > 0)
                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                        <tr>
                            <td style="width: 50%; vertical-align: top; padding-right: 15px;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    @foreach ($additional_deductions_filtered as $index => $deduction)
                                        @if($index % 2 == 0)
                                            <tr>
                                                <td style="text-align: left; padding: 3px 0; color: #0f056b;">{{ strtoupper($deduction->deduction) }}</td>
                                                <td style="text-align: right; padding: 3px 0; width: 100px;">{{ number_format($deduction->amount, 2, '.', ',') }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </table>
                            </td>
                            <td style="width: 50%; vertical-align: top; padding-left: 15px; border-left: 1px solid #000;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    @foreach ($additional_deductions_filtered as $index => $deduction)
                                        @if($index % 2 == 1)
                                            <tr>
                                                <td style="text-align: left; padding: 3px 0; color: #0f056b;">{{ strtoupper($deduction->deduction) }}</td>
                                                <td style="text-align: right; padding: 3px 0; width: 100px;">{{ number_format($deduction->amount, 2, '.', ',') }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    </table>
                @endif
            </div>

            <!-- Summary Section -->
            <div class="summary-section">
                @php
                    /**
                     * SUMMARY VALUES
                     *  - All values come from the current PayslipController::print() select()
                     *    which only provides gross_amount, total_deduction and net_pay.
                     *  - FIRST PAY and SECOND PAY are derived from net_pay for display only.
                     */

                    // 1) Core values from payroll_summaries
                    $gross_amount     = (float) ($payroll->gross_amount ?? 0);
                    $total_deductions = (float) ($payroll->total_deduction ?? 0);

                    // 2) Net pay – prefer stored net_pay; if missing/zero, derive from gross - deductions
                    $net_pay = $total_earnings - $total_deductions;
                    if ($net_pay <= 0 && $gross_amount > 0 && $total_deductions >= 0) {
                        $net_pay = max(0, $gross_amount - $total_deductions);
                    }

                    // 3) First and Second pay – purely derived from net_pay

                    // $total_pay = $net_pay - $first_half_absent;



                    $salary_pay = $payroll->net_pay / 2  ?? 0;


                    // Split net pay so that FIRST PAY has no centavos (whole pesos only)
                    // and all centavos (decimal portion) end up in SECOND PAY.
                    // Example: 1000.01 -> first_pay = 500.00, second_pay = 500.01
                    $net_pay_value = (float) ($payroll->net_pay ?? 0);
                    // First pay: integer division by 2 (no centavos)
                    $first_pay     = floor($net_pay_value / 2);
                    // Second pay: remainder including all centavos
                    $second_pay    = round($net_pay_value - $first_pay, 2);

                    // if ($total_pay > 0) {
                    //     // Split net pay into two parts (you can adjust this rule later if needed)
                    //     $first_pay  = round($total_pay / 2);
                    //     $second_pay = max(0, $total_pay - $first_pay);
                    // } else {
                    //     $first_pay  = 0;
                    //     $second_pay = 0;
                    // }
                @endphp

                <div class="summary-row">
                    <span class="summary-label">TOTAL DEDUCTIONS</span>
                    <span class="summary-amount">{{ number_format($total_deductions, 2, '.', ',') }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">NET PAY</span>
                    <span class="summary-amount">{{ number_format($net_pay, 2, '.', ',') }}</span>
                </div>

                <!-- Pay Distribution -->
                <div class="pay-distribution">
                    <div class="pay-item">
                        <div class="pay-item-row">
                            <span class="label5">FIRST PAY</span>
                            <span style="font-weight: bold;">{{ number_format($first_pay, 2, '.', ',') }}</span>
                        </div>
                    </div>
                    <div class="pay-item">
                        <div class="pay-item-row">
                            <span class="label5">SECOND PAY</span>
                            <span style="font-weight: bold;">{{ number_format($second_pay, 2, '.', ',') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
