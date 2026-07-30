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

        <title>Payroll Report</title>

        <style>
            p {
                padding: 0;
                margin: 0;
            }

            .body {
                margin: 0.5in;
            }

            table,
            th,
            td {
                border: solid 1px black;
                border-collapse: collapse;
                padding: 5px;
                font-size: 10;
                text-align: center;
            }

            .signatory-table {
                width: 100%;
                margin-top: 50px;
                border: none;
            }

            .signatory-table td {
                width: 50%;
                border: none;
                padding: 0 20px;
                vertical-align: top;
            }

            .signatory-wrapper {
                text-align: center;
                margin-top: 10px;
            }

            .signatory-name {
                text-align: center;
                font-weight: bold;
                margin-top: 12px;
                margin-bottom: 2px;
            }

            .signatory-position {
                text-align: center;
                font-size: 13px;
                margin-bottom: 10px;
            }

            .signatory-line {
                border-top: solid 0.5px black;
                width: 70%;
                margin: 20px auto 8px;
            }

            .signatory-note {
                text-align: center;
                font-style: italic;
            }
        </style>
    </head>

    <body>
        <div>
            <div style="text-align: center;">
                <h1 style="margin-bottom: 2px;">PAYROLL SUMMARY</h1>
                <p style="font-size: 12;">For the period: <b>{{ $payroll_period }}</b></p>
            </div>
            <div>
                <p style="text-align: left;margin-top: 50px;">{{ $companies[0]->name }}</p>
            </div>
            <div>
                <table style="margin: 15px 0px 10px 0px;width: 100%;">
                    <tr>
                        <th>No.</th>
                        <th>Employee</th>
                        <th>Designation</th>
                        <th>Basic Pay</th>
                        <th>Incomes</th>
                        <th>Gross Pay</th>
                        <th>W/Tax</th>
                        <th>GSIS</th>
                        <th>Pag-Ibig</th>
                        <th>Philhealth</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                    </tr>
                    <tbody>
                        @php($ctr = 1)
                        @php($basic = 0)
                        @php($gross = 0)
                        @php($income = 0)
                        @php($tax = 0)
                        @php($gsis = 0)
                        @php($pagibig = 0)
                        @php($philhealth = 0)
                        @php($deduction = 0)
                        @php($net = 0)
                        @foreach($payrolls as $pay)
                        <tr>
                            <td>{{ $ctr++ }}</td>
                            <td style="text-align: left;">{{ $pay->name }}</td>
                            <td style="text-align: left;">{{ $pay->position }}</td>
                            <td>{{ number_format($pay->salary,2,'.',',') }}</td>
                            <td>
                                @foreach($incomes as $inc)
                                @if($inc->employee_id == $pay->employee_id)
                                <div style="text-align: right;">
                                    <p>
                                        {{ $inc->name }} &nbsp;&nbsp;&nbsp; {{ number_format($inc->amount,2,'.',',') }}
                                    </p>
                                </div>
                                @endif
                                @endforeach
                            </td>
                            <td>{{ number_format($pay->gross_amount,2,'.',',') }}</td>
                            <td>{{ number_format($pay->tax,2,'.',',') }}</td>
                            <td>{{ number_format($pay->gsis,2,'.',',') }}</td>
                            <td>{{ number_format($pay->pagibig,2,'.',',') }}</td>
                            <td>{{ number_format($pay->philhealth,2,'.',',') }}</td>
                            <td>
                                @php($hasDeductionDetails = false)
                                @foreach($deductions as $inc)
                                    @if($inc->employee_id == $pay->employee_id)
                                        @php($hasDeductionDetails = true)
                                        <div style="text-align: right;">
                                            <p>
                                                {{ $inc->name }} &nbsp;&nbsp;&nbsp; {{ number_format($inc->amount,2,'.',',') }}
                                            </p>
                                        </div>
                                    @endif
                                @endforeach
                                @if(!$hasDeductionDetails)
                                    <div style="text-align: right;">
                                        <p>{{ number_format($pay->total_deduction, 2, '.', ',') }}</p>
                                    </div>
                                @endif
                            </td>
                            <td>{{ number_format($pay->net_pay,2,'.',',') }}</td>
                        </tr>
                        @php($basic += $pay->salary)
                        @php($gross += $pay->gross_amount)
                        @php($income += $pay->total_income)
                        @php($tax += $pay->tax)
                        @php($gsis += $pay->gsis)
                        @php($pagibig += $pay->pagibig)
                        @php($philhealth += $pay->philhealth)
                        @php($deduction += $pay->total_deduction)
                        @php($net += $pay->net_pay)
                        @endforeach
                        <tr>
                            <td style="text-align: left;font-weight: bold;" colspan="3">Total</td>
                            <td style="font-weight: bold;">{{ number_format($basic,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($income,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($gross,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($tax,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($gsis,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($pagibig,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($philhealth,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($deduction,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($net,2,'.',',') }}</td>
                        </tr>
                    </tbody>
                </table>
                <table class="signatory-table">
                    <tr>
                        <td>
                            <p><b>Prepared by:</b></p>
                            <div class="signatory-wrapper">
                                @if(!empty($preparedBy))
                                    <p class="signatory-name">{{ strtoupper($preparedBy) }}</p>
                                @endif
                                @if(!empty($preparedByPosition))
                                    <p class="signatory-position">{{ $preparedByPosition }}</p>
                                @endif
                                <div class="signatory-line"></div>
                                <p class="signatory-note">(Signature Over Printed Name)</p>
                            </div>
                        </td>
                        <td>
                            <p><b>Approved by:</b></p>
                            <div class="signatory-wrapper">
                                @if(!empty($approvedBy))
                                    <p class="signatory-name">{{ strtoupper($approvedBy) }}</p>
                                @endif
                                @if(!empty($approvedByPosition))
                                    <p class="signatory-position">{{ $approvedByPosition }}</p>
                                @endif
                                <div class="signatory-line"></div>
                                <p class="signatory-note">(Signature Over Printed Name)</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </body>

</html>
