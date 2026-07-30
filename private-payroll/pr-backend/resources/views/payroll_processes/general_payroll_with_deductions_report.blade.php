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

    <title>General Payroll with Deduction Details Report</title>

    <style>
        html,
        body {
            margin-top: 5px;
            margin-bottom: 15px;
            margin-left: auto;
            margin-right: auto;
            font-family: 'Arial';
            font-size: 12px;
        }

        p {
            padding: 0;
            margin: 0;
        }

        .main {
            margin: 0.2in;
        }

        th,
        td {
            border: solid 1px black;
            border-collapse: collapse;
            padding: 5px;
        }

        .bold-text {
            font-weight: bold;
        }

        .parent {
            display: block;
            /* border: 1px dotted black; */
        }

        .child {
            display: inline-block;
            box-sizing: border-box;
            vertical-align: top;
            /* border: 1px solid black; */
            margin: 5px;
            padding: 5px;
            min-width: 288px;
            max-width: 288px;
        }

        .box {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid black;
            margin-right: 5px;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    <div class="main">
        <div>
            <div style="padding-left: 5px; position: absolute;text-align: right;">
                <img src="data:image/png;base64,{{ $image }}" width="100" height="100">
            </div>
            <div style="text-align: center;">
                <h3 style="margin: 2px;font-weight: normal;">Republic of the Philippines</h3>
                <h3 style="margin: 2px;font-weight: normal;">{{ $companies[0]->name }}</h3>
                <h1 style="margin: 2px;font-weight: normal;">GENERAL PAYROLL</h1>
                <h2 style="margin: 0;padding: 0;font-weight: normal;">{{ $payroll_period ?? '' }}</h2>
                <h2 style="margin: 0;padding: 0;"><u>{{ $payrolls[0]->department ?? '' }}</u></h2>
            </div>
        </div>
        <div style="margin-top: 40px">
            <p>General Form No. 4</p>
            <p>Revised January 1922</p>
            <div style="margin-left: 25px;">
                <p>We acknowledge receipt of the sum shown opposite our name as full compensation</p>
                <p style="margin-bottom: 30px;">for services rendered for the period stated:</p>
            </div>
        </div>

        <table style="margin: auto;border: none; border-collapse: collapse;width: 100%;">
            <thead>
                <tr>
                    <td rowspan="2" style="text-align: center;">No.</td>
                    <td colspan="2">{{ $payrolls[0]->department ?? '' }}</td>
                    <td rowspan="2" style="text-align: center;" class="bold-text">Position</td>
                    <td rowspan="2" style="text-align: center;" class="bold-text"><b>Monthly</b> <br> <b>Salary</b>
                    </td>
                    <td rowspan="2" style="text-align: center;" class="bold-text">PERA</td>
                    <td rowspan="2" style="text-align: center;" class="bold-text"><b>AMOUNT</b> <br><b>EARNED</b>
                    </td>
                    <td rowspan="2" style="text-align: center;">731 <br> <small>20201020-001-001</small> <br> <span
                            class="bold-text">GSIS
                            12%</span></td>
                    <td rowspan="2" style="text-align: center;">734 <br> <small>20201020-002</small> <br> <b>ECC</b>
                    </td>
                    <td rowspan="2" style="text-align: center;">732 <br> <small>20201030-001</small> <br> <b>HDMF
                            g/s</b></td>
                    <td rowspan="2" style="text-align: center;">733 <br> <small>20201040-001</small> <br> <b>PHIC
                            g/s</b></td>
                    @foreach ($deduction_headers as $header)
                        <td rowspan="2" style="text-align: center;">{{ $header->mfo_pap }}<br>
                            <small>{{ $header->uacs }}</small> <br>
                            <b>{{ $header->deduction }}</b>
                        </td>
                    @endforeach
                    <td rowspan="2" style="text-align: center;width: 80px;">(see supporting attachments)
                        <br><b>MONTHLY</b> <br>
                        <b>DEDUCTIONS</b>
                    </td>
                    <td rowspan="2" style="text-align: center;"><b>NET</b> <br> <b>AMOUNT</b></td>
                    <td rowspan="2"></td>
                    <td rowspan="2" style="text-align: center;{{ $is_second_half == true ? '' : 'display:none;' }}">
                        AMOUNT
                        <br> RECEIVED <br>
                        {{ $payroll_period_1 ?? '' }}
                    </td>
                    <td rowspan="2" style="text-align: center;">AMOUNT <br> RECEIVED <br>
                        {{ $payroll_period ?? '' }}
                    </td>
                    <td rowspan="2" style="text-align: center;width: 100px;">Signature</td>
                </tr>
                <tr>
                    <td style="text-align: center;">PIN <br>SL CODE</td>
                    <td style="text-align: center;">Employee Name <br><b>Description</b></td>
                </tr>
            </thead>
            <tbody>
                @php($counter = 0)
                @php($total_salary = 0)
                @php($total_gross = 0)
                @php($total_gsis = 0)
                @php($total_pagibig = 0)
                @php($total_ecc = 0)
                @php($total_philhealth = 0)
                @php($total_income = 0)
                @php($total_deduction = 0)
                @php($total_net = 0)

                @foreach ($payrolls as $payroll)
                    @php(++$counter)
                    @php($total_salary += $payroll->salary)
                    @php($total_gross += $payroll->gross_amount)
                    @php($total_gsis += $payroll->gsis)
                    @php($total_pagibig += $payroll->pagibig)
                    @php($total_ecc += 100)
                    @php($total_philhealth += $payroll->philhealth)
                    @php($total_income += $payroll->total_income)
                    @php($total_deduction += $payroll->total_deduction)
                    @php($total_net += $payroll->net_pay)
                    <tr>
                        <td style="text-align: center;">{{ $counter . '.' }}</td>
                        <td style="text-align: left;">{{ $payroll->employee_no }}</td>
                        <td style="text-align: left;">{{ $payroll->name }}</b></td>
                        <td style="text-align: left;">{{ $payroll->position }}</td>
                        <td style="text-align: right;">{{ number_format($payroll->salary, 2, '.', ',') }}</td>
                        <td style="text-align: right;">{{ number_format($payroll->total_income, 2, '.', ',') }}</td>
                        <td style="text-align: right;">{{ number_format($payroll->gross_amount, 2, '.', ',') }}</td>
                        <td style="text-align: right;">{{ number_format($payroll->gsis, 2, '.', ',') }}</td>
                        <td style="text-align: right;">{{ number_format(100, 2, '.', ',') }}</td>
                        <td style="text-align: right;">{{ number_format($payroll->pagibig, 2, '.', ',') }}</td>
                        <td style="text-align: right;">{{ number_format($payroll->philhealth, 2, '.', ',') }}</td>
                        @foreach ($deduction_headers as $header)
                            <td style="text-align: right;">
                                @foreach ($deductions as $deduction)
                                    @if ($header->deduction_id == $deduction->deduction_id)
                                        @if ($payroll->employee_id == $deduction->employee_id)
                                            {{ number_format($deduction->amount, 2, '.', ',') }}
                                        @endif
                                    @endif
                                @endforeach
                            </td>
                        @endforeach
                        <td style="text-align: right;">{{ number_format($payroll->total_deduction, 2, '.', ',') }}
                        </td>
                        <td style="text-align: right;">{{ number_format($payroll->net_pay * 2, 2, '.', ',') }}</td>
                        <td style="text-align: center;">{{ $counter . '.' }}</td>
                        <td style="text-align: right;{{ $is_second_half == true ? '' : 'display:none;' }}">
                            {{ number_format($payroll->net_pay, 2, '.', ',') }}
                        </td>
                        <td style="text-align: right;">{{ number_format($payroll->net_pay, 2, '.', ',') }}</td>
                        <td style="text-align: center;"></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot style="border: none;">
                <tr>
                    <td style="border: none;"></td>
                    <td colspan="3" style="text-align: left;border: none;width: 140px;">
                        <b>TOTAL</b>
                    </td>
                    <td style="text-align: right;border: none;" class="bold-text">
                        {{ number_format($total_salary, 2, '.', ',') }}
                    </td>
                    <td style="text-align: right;border: none;width: 30px;" class="bold-text">
                        {{ number_format($total_income, 2, '.', ',') }}
                    </td>
                    <td style="text-align: right;border: none;width: 20px;" class="bold-text">
                        {{ number_format($total_gross, 2, '.', ',') }}
                    </td>
                    <td style="text-align: right;border: none;" class="bold-text">
                        {{ number_format($total_gsis, 2, '.', ',') }}
                    </td>
                    <td style="text-align: right;border: none;" class="bold-text">
                        {{ number_format($total_ecc, 2, '.', ',') }}
                    </td>
                    <td style="text-align: right;border: none;" class="bold-text">
                        {{ number_format($total_pagibig, 2, '.', ',') }}
                    </td>
                    <td style="text-align: right;border: none;" class="bold-text">
                        {{ number_format($total_philhealth, 2, '.', ',') }}
                    </td>
                    @foreach ($deduction_headers as $header)
                        <td style="text-align: right;;border: none;" class="bold-text">
                            @foreach ($deduction_totals as $deduction)
                                @if ($header->deduction_id == $deduction->deduction_id)
                                    {{ number_format($deduction->amount, 2, '.', ',') }}
                                @endif
                            @endforeach
                        </td>
                    @endforeach
                    <td style="text-align: right;border: none;" class="bold-text">
                        {{ number_format($total_deduction, 2, '.', ',') }}
                    </td>
                    <td style="text-align: right;border: none;" class="bold-text">
                        {{ number_format($total_net * 2, 2, '.', ',') }}
                    </td>
                    <td style="border: none;"></td>
                    <td style="text-align: right;border: none;{{ $is_second_half == true ? '' : 'display:none;' }}"
                        class="bold-text">
                        {{ number_format($total_net, 2, '.', ',') }}
                    </td>
                    <td style="text-align: right;border: none;" class="bold-text">
                        {{ number_format($total_net, 2, '.', ',') }}
                    </td>
                    <td style="border: none;"></td>
                </tr>
            </tfoot>
        </table>
        {{-- <table style="border: none;width: 100%;margin-top: 2px;">

        </table> --}}
        <hr>
        {{-- signatories sections --}}
        <div class="parent">
            <div class="child">
                <p>CERTIFIED: Services have been duly rendered as stated above.</p>
                <div style="margin-top: 60px;">
                    <p class="bold-text">{{ $signatories['signatory_1'] ?? '' }}</p>
                    <p>{{ $signatories['signatory_position_1'] ?? '' }}</p>
                    <p>Name & Signature of Supervisor</p>
                    <p>Date:____________________</p>
                </div>
            </div>
            <div class="child">
                <p>CERTIFIED:</p>
                <p><span class="box"></span> Allotment obligated for the purpose as indicated above.</p>
                <p><span class="box"></span> Supporting documents complete.</p>

                <div style="margin-top: 35px;">
                    <p class="bold-text">{{ $signatories['signatory_2'] ?? '' }}</p>
                    <p>{{ $signatories['signatory_position_2'] ?? '' }}</p>
                    <br>
                    <p>Date:____________________</p>
                </div>
            </div>
            <div class="child">
                <p>CERTIFIED: Funds available.</p>
                <div style="margin-top: 75px;">
                    <p class="bold-text">{{ $signatories['signatory_3'] ?? '' }}</p>
                    <p>{{ $signatories['signatory_position_3'] ?? '' }}</p>
                    <br>
                    <p>Date:____________________</p>
                </div>
            </div>
            <div class="child">
                <p class="bold-text">APPROVED FOR PAYMENT:</p>
                <div style="margin-top: 75px;">
                    <p class="bold-text">{{ $signatories['signatory_4'] ?? '' }}</p>
                    <p>{{ $signatories['signatory_position_4'] ?? '' }}</p>
                    <p>Name & Signature of Officer</p>
                    <p>Date:____________________</p>
                </div>
            </div>
            <div class="child">
                <p><span class="bold-text">CERTIFIED:</span> each employee whose name appears above has been paid the
                    amount opposite
                    his/her name.</p>
                <div style="margin-top: 45px;">
                    <p class="bold-text">{{ $signatories['signatory_5'] ?? '' }}</p>
                    <p>{{ $signatories['signatory_position_5'] ?? '' }}</p>
                    <br>
                    <p>Date:____________________</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
