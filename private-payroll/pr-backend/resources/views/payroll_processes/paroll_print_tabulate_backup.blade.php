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
                width: 900px;
            }

            table,
            th,
            td {
                border: solid 1px black;
                border-collapse: collapse;
                padding: 5px;
                font-size: 8px;
                text-align: center;
            }

            .right-align {
                text-align: right;
            }
        </style>
    </head>

    <body>
        <div>
            <div style="text-align: center;">
                <h2 style="margin-bottom: 0px;">GENERAL PAYROLL</h2>
                <h2 style="margin-bottom: 2px;margin-top: 0px;">{{ strtoupper($companies[0]->name) }}</h2>
                <p style="font-size: 12;"><b>SALARY AND PERA</b> {{ strtoupper($payroll_period) }}</p>
            </div>
            <div>
                <p style="text-align: left;margin-top: 50px;margin-left: 42px;">We acknowledge receipt of cash shown
                    opposite our name as
                    full compensation for services rendered for the period
                    covered.</p>
            </div>
            <div>
                <table style="margin: 5px 0px 10px 0px;width: 100%;">
                    <thead>
                        <tr>
                            <th rowspan="2">NO.</th>
                            <th rowspan="2" style="width: 100px;">NAME</th>
                            <th rowspan="2" style="width: 100px;">DESIGNATION</th>
                            <th rowspan="2" style="width: 40px;">EMPLOYEE NUMBER</th>
                            <th rowspan="2" style="width: 40px;">PRESENT MONTHLY SALARY</th>
                            @foreach ($income_headers as $inc_hdr)
                            <th rowspan="2">{{ strtoupper($inc_hdr->income) }}</th>
                            @endforeach
                            <th rowspan="2" style="width: 40px;">GROSS AMOUNT</th>
                            <th colspan="{{ 4 + count($deduction_headers) }}">DEDUCTIONS</th>
                            <th rowspan="2" style="width: 40px;">OTHER DEDUCTIONS</th>
                            <th colspan="2">NET AMOUNT RECEIVED</th>
                            <th rowspan="2">NO.</th>
                        </tr>
                        <tr>
                            <th style="width: 40px;">GSIS PREMIUM</th>
                            <th style="width: 40px;">HDMF PREMIUM</th>
                            <th style="width: 40px;">TAX WITHHELD</th>
                            <th style="width: 40px;">PHILHEALTH</th>
                            @foreach ($deduction_headers as $deduc_hdr)
                            <th style="width: 60px;">{{ strtoupper($deduc_hdr->deduction) }}</th>
                            @endforeach
                            <th style="width: 100px;"><b>15TH</b></th>
                            <th style="width: 100px;"><b>{{ date('d', strtotime($data[0]->payroll_end_date)) }}TH</b>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($ctr = 1)
                        @php($ctr_1 = 1)
                        {{-- @php($basic = 0)
                        @php($gross = 0)
                        @php($income = 0)
                        @php($tax = 0)
                        @php($gsis = 0)
                        @php($pagibig = 0)
                        @php($philhealth = 0)
                        @php($deduction = 0)
                        @php($net = 0) --}}
                        @foreach ($payrolls as $pay)
                        <tr>
                            <td>{{ $ctr++ }}</td>
                            <td style="text-align: left;">{{ strtoupper($pay->name) }}</td>
                            <td style="text-align: left;">{{ strtoupper($pay->position) }}</td>
                            <td style="text-align: left;">{{ $pay->employee_no }}</td>
                            <td class="right-align">{{ number_format($pay->salary, 2, '.', ',') }}</td>
                            {{-- dynamic income start --}}
                            @foreach ($income_headers as $inc_hdr)
                            @php($x = false)
                            @foreach ($incomes as $inc)
                            @if ($inc->employee_id == $pay->employee_id && $inc->income_id == $inc_hdr->income_id &&
                            $inc->amount > 0)
                            <td class="right-align">{{ number_format($inc->amount, 2, '.', ',') }}</td>
                            @php($x = true)
                            @break

                            @elseif($inc->employee_id == $pay->employee_id && $inc->income_id == $inc_hdr->income_id &&
                            $inc->amount == 0)
                            <td class="right-align">{{ number_format($inc->amount, 2, '.', ',') }}</td>
                            @php($x = true)
                            @break
                            @endif
                            @endforeach
                            @if ($x == false)
                            <td class="right-align"></td>
                            @endif
                            @endforeach
                            {{-- dynamic income end --}}
                            <td class="right-align">{{ number_format($pay->gross_amount, 2, '.', ',') }}</td>
                            <td class="right-align">{{ number_format($pay->gsis, 2, '.', ',') }}</td>
                            <td class="right-align">{{ number_format($pay->pagibig, 2, '.', ',') }}</td>
                            <td class="right-align">{{ number_format($pay->tax, 2, '.', ',') }}</td>
                            <td class="right-align">{{ number_format($pay->philhealth, 2, '.', ',') }}</td>
                            {{-- dynamic deduction start --}}
                            @foreach ($deduction_headers as $deduc_hdr)
                            @php($y = false)
                            @foreach ($deductions as $deduc)
                            @if ($deduc->employee_id == $pay->employee_id && $deduc->deduction_id ==
                            $deduc_hdr->deduction_id)
                            <td class="right-align">{{ number_format($deduc->amount, 2, '.', ',') }}</td>
                            @php($y = true)
                            @break

                            @elseif($deduc->employee_id == $pay->employee_id && $deduc->deduction_id ==
                            $deduc_hdr->deduction_id)
                            <td class="right-align">{{ number_format($deduc->amount, 2, '.', ',') }}</td>
                            @php($y = true)
                            @break
                            @endif
                            @endforeach
                            @if ($y == false)
                            <td class="right-align"></td>
                            @endif
                            @endforeach
                            {{-- dynamic deduction end --}}
                            <td class="right-align">{{ number_format($pay->late_amount + $pay->ut_amount +
                                $pay->absent_amount, 2, '.', ',')
                                }}
                            </td>
                            <td class="right-align">{{ number_format($pay->net_pay / 2, 2, '.', ',') }}</td>
                            <td class="right-align">{{ number_format($pay->net_pay / 2, 2, '.', ',') }}</td>
                            <td>{{ $ctr_1++ }}</td>
                        </tr>
                        {{-- @php($basic += $pay->salary)
                        @php($gross += $pay->gross_amount)
                        @php($income += $pay->total_income)
                        @php($tax += $pay->tax)
                        @php($gsis += $pay->gsis)
                        @php($pagibig += $pay->pagibig)
                        @php($philhealth += $pay->philhealth)
                        @php($deduction += $pay->total_deduction)
                        @php($net += $pay->net_pay) --}}
                        @endforeach
                        {{-- <tr>
                            <td style="text-align: left;font-weight: bold;" colspan="3">Total</td>
                            <td style="font-weight: bold;">{{ number_format($basic,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($income,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($gross,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($tax,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($gsis,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($pagibig,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($philhealth,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($deduction,2,'.',',') }}</td>
                            <td style="font-weight: bold;"></td>
                            <td style="font-weight: bold;"></td>
                            <td style="font-weight: bold;">{{ number_format($net,2,'.',',') }}</td>
                            <td style="font-weight: bold;">{{ number_format($net,2,'.',',') }}</td>
                            <td style="font-weight: bold;"></td>
                        </tr> --}}
                    </tbody>
                </table>
                <p style="float: right;font-size: 8px;">{{ $footer['document_no'] }}</p>
                <p style="float: right;font-size: 8px;">{{ $footer['revision'] }}</p>
            </div>
        </div>
    </body>

</html>
