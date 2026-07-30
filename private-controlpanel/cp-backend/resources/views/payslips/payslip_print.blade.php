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
            margin-left: auto;
            margin-right: auto;
            font-size: 14px;
            font-family: 'Arial', sans-serif;
        }

        p {
            padding: 0;
            margin: 2px;
        }

        .bold-text {
            font-weight: bold;
        }

        table {
            width: 75%;
            border: none;
            margin-bottom: 20px;
            margin-left: 35px;
        }

        .header {
            font-size: 18px;
            font-weight: bold;
        }

        .label-width {
            width: 100px;
        }

        .amount-label-width {
            width: 320px;
        }

        .amount-label {
            width: 100px;
            text-align: right;
        }

        .indent {
            margin-left: 10px
        }

        .child {
            display: inline-block;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach ($payrolls as $payroll)
        <div>
            <div style="margin: 0px 130px 0px 130px;">
                <div style="text-align: center;">
                    <div class="child">
                        <img src="data:image/png;base64,{{ $logo1 }}"
                            onerror=this.src="../../dist/img/employee_profile.png" width="60" height="60">
                    </div>
                    <div class="child" style="margin-left: 25px;margin-right: 25px;">
                        <p>Republic of the Philippines</p>
                        <p>{{ $companies[0]->name }}</p>
                    </div>
                    <div class="child">
                        <img src="data:image/png;base64,{{ $logo2 }}"
                            onerror=this.src="../../dist/img/employee_profile.png" width="60" height="60">
                    </div>
                </div>
                <div style="text-align: center">
                    <h4 class="header">PAYSLIP</h4>
                </div>
                <div>
                    <p class="child label-width">NAME:</p>
                    <p class="child bold-text">{{ strtoupper($payroll->name) }}</p>
                </div>
                <div>
                    <p class="child label-width">OFFICE:</p>
                    <p class="child">{{ strtoupper($payroll->department) }}</p>
                </div>
                <div>
                    <p class="child label-width">PERIOD:</p>
                    <p class="child">{{ strtoupper($payroll->payroll_period) }}</p>
                </div>
                <div>
                    <p class="child amount-label-width">MONTHLY RATE:</p>
                    <p class="child amount-label">{{ number_format($payroll->salary, 2, '.', ',') }}</p>
                </div>
                <div>
                    <p class="child amount-label-width">HOLIDAY PAY:</p>
                    <p class="child amount-label">{{ number_format($payroll->holiday_pay, 2, '.', ',') }}</p>
                </div>
                @foreach ($incomes as $income)
                    <div>
                        <p class="child amount-label-width">{{ strtoupper($income->income) }}:</p>
                        <p class="child amount-label">{{ number_format($income->amount, 2, '.', ',') }}</p>
                    </div>
                @endforeach
                <div>
                    <p class="child amount-label-width">TOTAL</p>
                    <p class="child amount-label bold-text" style="border-top: 1px solid black;">
                        {{ number_format($payroll->total_income + $payroll->holiday_pay + $payroll->salary, 2, '.', ',') }}
                    </p>
                    <p class="child amount-label-width">DEDUCTIONS</p>
                </div>
                <div>
                    @php($counter = 5)
                    <table>
                        <tbody>
                            <tr>
                                <td style="width: 20px;">1</td>
                                <td style="width: 100px">LATE/UT/LWOP</td>
                                <td style="text-align: right;width: 100px">
                                    {{ number_format($payroll->tardiness_amount + $payroll->lwop_amount, 2, '.', ',') }}
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 20px;">2</td>
                                <td style="width: 100px">WTAX</td>
                                <td style="text-align: right;width: 100px">
                                    {{ number_format($payroll->tax, 2, '.', ',') }}</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>GSIS</td>
                                <td style="text-align: right">{{ number_format($payroll->gsis, 2, '.', ',') }}</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>HDMF P/S</td>
                                <td style="text-align: right">{{ number_format($payroll->pagibig, 2, '.', ',') }}</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>PHIC P/S</td>
                                <td style="text-align: right">{{ number_format($payroll->philhealth, 2, '.', ',') }}
                                </td>
                            </tr>
                            @foreach ($deductions as $deduction)
                                <tr>
                                    <td>{{ ++$counter }}</td>
                                    <td>{{ strtoupper($deduction->deduction) }}</td>
                                    <td style="text-align: right">{{ number_format($deduction->amount, 2, '.', ',') }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="2" style="border-top: 1px solid black">Total Deduction: </td>
                                <td style="text-align: right;border-top: 1px solid black">
                                    {{ number_format($payroll->total_deduction + $payroll->tax + $payroll->gsis + $payroll->pagibig + $payroll->philhealth + $payroll->tardiness_amount + $payroll->lwop_amount, 2, '.', ',') }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot style="border: 1px solid black">
                            <tr>
                                <td colspan="2">NET AMOUNT RECEIVED</td>
                                <td class="bold-text" style="text-align: right">
                                    {{ number_format($payroll->net_pay, 2, '.', ',') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div style="margin-top: 35px;width: 60%;margin-left: 30%;">
                    <p>CERTIFIED CORRECT:</p>
                    <div style="margin-top: 40px;text-align: center;">
                        <p class="bold-text">
                            {{ strtoupper($signatories['name'] ?? 'ATTY. FARIDA D. ROMILLO-MATEO') }}</p>
                        <p>{{ $signatories['position'] ?? 'Municipal Accountant' }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
