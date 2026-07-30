<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">
    <title>Overtime Payment Report</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 5mm;
        }
        .header {
            margin-top: 30px;
            margin-left: 0;
            margin-right: 0;
        }

        p {
            padding: 0;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        table,
        th,
        td {
            border: solid 1px black;
            border-collapse: collapse;
            padding: 2px;
            font-size: 8px;
            text-align: center;
        }
        .table-title {
            font-size: 10px;
            margin-left: 15px;
        }
    </style>
</head>

<body>
    <div class="header" style="position: relative;">
        <div style="position: absolute; top: 0px; left: 20px;">
            <img src="data:image/png;base64,{{ $image }}" width="65" height="65">
        </div>
        <div style="text-align: center;">
            <p>Republic of the Philippines</p>
            <p style="font-size:13px">{{ strtoupper($companies[0]->name) }}</p>
            <h3 style="margin-top: -1px; padding: 0;">G E N E R A L P A Y R O L L</h3>
            <p style="margin-top: -13px; padding: 0;">
                <strong>OVERTIME</strong>
            </p>
        </div>
    </div>

    <div class="table-title">General Form No.4 <br>
        Revised January 2022 <br>
        We acknowledge receipt of the sum shown opposite our name as full compensation for services rendered for the
        period stated:
    </div>

    <div>
        @php
            $dates = $employees->pluck('date')->unique()->sort();
        @endphp

        <table style="margin: 5px 0px 10px 0px;width: 100%;">
            <thead>
                <tr>
                    <th rowspan="3">No.</th>
                    <th rowspan="3">NAME</th>
                    <th rowspan="3">BASIC SALARY</th>
                    @foreach($dates as $date)
                        <th colspan="3">{{ date('M d', strtotime($date)) }}</th>
                    @endforeach
                    <th rowspan="3">TOTAL AMOUNT EARNED</th>
                    <th rowspan="3">No.</th>
                    <th rowspan="3">TOTAL AMOUNT RECEIVED</th>
                    <th rowspan="3">SIGNATURE OF EMPLOYEE</th>
                </tr>
                <tr>
                    @foreach($dates as $date)
                        <td>No. of Hours</td>
                        <td>Constant Factor</td>
                        <td>AMOUNT EARNED</td>
                    @endforeach
                </tr>
                <tr>
                    @foreach($dates as $date)
                        <th>{{ date('M d', strtotime($date)) }}</th>
                        <th>Total</th>
                        <th></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php
                    // $employees is per-employee per-date (and can include multiple rows if multiple OT rates/types).
                    // For the report rows, we want exactly one row per employee and aggregate per date inside the row.
                    $employee_rows = $employees->unique('employee_id')->sortBy('name')->values();
                @endphp

                @foreach ($employee_rows as $ot)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $ot->name }}</td>
                                <td>P {{ number_format($ot->salary, 2, '.', ',') }}</td>
                                @foreach($dates as $date)
                                                @php
                                                    $day_rows = $employees->where('date', $date)->where('employee_id', $ot->employee_id);
                                                    $day_hours = $day_rows->sum('total_hours');
                                                    $day_earned = $day_rows->sum('earned');
                                                    $day_rates = $day_rows->pluck('rate')->filter(fn($r) => $r !== null && $r !== '')->unique()->values();
                                                    $day_rate_display = $day_rates->count() === 1 ? $day_rates[0] : '-';
                                                @endphp
                                                <td>{{ $day_rows->count() ? number_format($day_hours, 2, '.', ',') : '-' }}</td>
                                                <td>{{ $day_rows->count() ? $day_rate_display : '-' }}</td>
                                                <td>{{ $day_rows->count() ? number_format($day_earned, 2, '.', ',') : '-' }}</td>
                                @endforeach
                                <td>P
                                    {{ number_format($employees->where('employee_id', $ot->employee_id)->sum('earned'), 2, '.', ',') }}
                                </td>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>P
                                        {{ number_format($employees->where('employee_id', $ot->employee_id)->sum('earned'), 2, '.', ',') }}</strong>
                                </td>
                                <td></td>
                            </tr>
                @endforeach

                <tr>
                    <td colspan="3"><strong>TOTAL</strong></td>
                    @foreach($dates as $date)
                        <td>{{ number_format($employees->where('date', $date)->sum('total_hours'), 2, '.', ',') }}</td>
                        <td></td>
                        <td>P {{ number_format($employees->where('date', $date)->sum('earned'), 2, '.', ',') }}
                        </td>
                    @endforeach
                    <td><strong>P {{ number_format($employees->sum('earned'), 2, '.', ',') }}</strong></td>
                    <td></td>
                    <td><strong>P
                            {{ number_format($employees->sum('earned'), 2, '.', ',') }}</strong>
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        <div class="footer">
            <div class="signatories">
                <table style="border:none; width:100%">
                    <tr>
                        <td style="text-align:left; border:none; width:20%; vertical-align: top; padding-top: 0;">CERTIFIED: Services have been
                            <br>duly rendered as stated above.<br>
                        </td>
                        <td style=" border:none; width:20%;text-align:left; vertical-align: top; padding-top: 0;">
                            <p><br>CERTIFIED: <span
                                    style="border: 1px solid black; padding: 3px 5px; display: inline-block;"></span>
                                Allotment obligated for the <br>
                                purpose as indicated above.<br>
                                <span style="border: 1px solid black; padding: 3px 5px; display: inline-block;"></span>
                                Supporting documents complete
                            </p>
                        </td>
                        <td style="border:none;  width:20%;text-align:left; vertical-align: top; padding-top: 0;">CERTIFIED: Funds available.</td>
                        <td style="border:none; width:20%;text-align:left; vertical-align: top; padding-top: 0;"><b>APPROVED FOR PAYMENT:</b></td>
                        <td style=" border:none; width:20%;text-align:left; vertical-align: top; padding-top: 0;"><br><b>CERTIFIED:</b> each
                            employee
                            whose name appears
                            above has
                            been paid the amount opposite his/her name.</td>
                    </tr>

                    <tr>
                        <td style="border:none; padding-top:5px;text-align:left; vertical-align: bottom;">
                            <b>{{ $signatories['signatory1'] }}</b><br>{{ $signatories['signatory_position_1'] }}
                        </td>
                        <td style="border:none; padding-top:5px;text-align:left; vertical-align: bottom">
                            <b>{{ $signatories['signatory2'] }}</b><br>{{ $signatories['signatory_position_2'] }}
                        </td>
                        <td style="border:none; padding-top:5px;text-align:left; vertical-align: bottom">
                            <b>{{ $signatories['signatory3'] }}</b><br>{{ $signatories['signatory_position_3'] }}
                        </td>
                        <td style="border:none; padding-top:5px;text-align:left; vertical-align: bottom">
                            <b>{{ $signatories['signatory4'] }}</b><br>{{ $signatories['signatory_position_4'] }}
                        </td>
                        <td style="border:none; padding-top:5px;text-align:left; vertical-align: bottom">
                            <b>{{ $signatories['signatory5'] }}</b><br>{{ $signatories['signatory_position_5'] }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border:none;text-align:left">Date: <u> {{ now()->format('F d, Y') }}</u>
                        </td>
                        <td style="border:none;text-align:left">Date: <u> {{ now()->format('F d, Y') }}
                        </td>
                        <td style="border:none;text-align:left">Date: <u> {{ now()->format('F d, Y') }}
                        </td>
                        <td style="border:none;text-align:left">Date: <u> {{ now()->format('F d, Y') }}
                        </td>
                        <td style="border:none;text-align:left">Date: <u> {{ now()->format('F d, Y') }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>

</html>