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

    <title>Bank Remittance Report</title>

    <style>
        html,
        body {
            height: 297mm;
            margin-top: 15px;
            margin-bottom: 15px;
            padding-left: 40px;
            padding-right: 40px;
            font-size: 12px;
        }

        p {
            padding: 0;
            margin: 0;
        }

        .main_table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 3px;
            text-align: center;
            font-size: 11px;
        }

        .signatories {
            border: none;
            width: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            text-align: left;
        }

        th {
            text-align: center;
        }

        table .signatories {
            border-collapse: collapse;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    <div class="card p-4">
        <div class="main" style="width: 100%;">
            <div>
                <div style="width: 100%;">
                    <div style="padding-left: 110px; position: absolute; margin-top:-20px;">
                        <img src="data:image/png;base64,{{ $image }}" width="50" height="50">
                    </div>
                    <div style="text-align: center;">
                        <p>Republic of the Philippines</p>
                        <p style="font-size:13px">{{ strtoupper($companies[0]->name) }}</p>
                        <p>{{ $bank[0]->department ?? '' }}</p>
                        <br>
                    </div>
                    <div>
                        <p style="font-size: 12px; text-align: center"><b>REMITTANCE TO THE DEVELOPMENT BANK OF THE
                                PHILIPPINES</b><br><b>FOR {{ strtoupper($bank[0]->month_year ?? '') }}</b></p>
                        <br>
                    </div>
                </div>
                <div>
                    <table class="main_table" style="width: 100%">
                        <tr>
                            <th hidden></th>
                            <th>SL Code</th>
                            <th>Description</th>
                            <th>{{ $bank[0]->deduction ?? '' }}</th>
                        </tr>
                        @php
                            $ctr = 1;
                            $tlLoan = 0;
                        @endphp
                        @foreach ($bank as $dtl)
                            @php
                                $tlLoan += $dtl->amount;
                            @endphp
                            <tr>
                                <td>{{ $ctr++ }}</td>
                                <td>{{ $dtl->employee_no }}</td>
                                <td>{{ $dtl->full_name }}</td>
                                <td>{{ number_format($dtl->amount, 2, '.', ',') }}</td>

                            </tr>
                        @endforeach

                        <tr style="border: none; font-weight:bold;">
                            <td style="border: none; text-align: left;"></td>
                            <td style="text-align: right; border: none;"></td>
                            <td style="text-align: center; border: none;"></td>
                            <td style="text-align: center; border: none;">{{ number_format($tlLoan, 2, '.', ',') }}
                            </td>
                        </tr>
                    </table>

                </div>
                <div class="footer">
                    <p>CERTIFIED CORRECT:</p>
                    <div style="text-align: center; margin-top: 20px;">
                        <p style="font-size:13px"><b>{{ $signatory['signatory'] }}</b></p>
                        <p style="font-size:11px">{{ $signatory['position'] }}</p>
                        <p style="font-size:10px">{{ \Carbon\Carbon::parse($signatory['date'])->format('F j, Y') }}
                        </p>
                    </div>

                </div>
            </div>
</body>

</html>
