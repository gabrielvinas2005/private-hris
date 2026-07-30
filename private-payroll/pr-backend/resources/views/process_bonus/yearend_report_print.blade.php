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

    <title>Year End Slip</title>

    <style>
        html,
        body {
            height: 297mm;
            width: auto;
            margin-top: -15px;
            margin-bottom: 0px;
        }

        .header {
            align-items: center;
            margin-top: 50px;
            margin-left: 100px;
            margin-right: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 20px;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        table th {
            background-color: #f2f2f2;
        }

        .table-title {
            text-align: start;
            margin-top: 10px;
            font-size: 10px;
            margin-bottom: -10px;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    <div class="header">
        <div style="text-align: center;">
            <h3 style="margin-top: -10px; padding: 0;">G E N E R A L &nbsp; P A Y R O L L</h3>
            <p style="margin-top: -10px">{{ strtoupper($department ?? 'MUNICIPAL ACCOUNTING OFFICE') }}</p>
            <p style="margin-top: -13px;padding: 0;">Year-End Bonus and Cash Gift for CY {{ $month ?? 'N/A' }} </p>
        </div>
    </div>

    <div class="table-title">We acknowledge receipt of the sum shown opposite our <br>
        name as full compensation
        for servicesrendered for the period stated:<br></div>
    <table>
        <thead>
            <tr style="width: 100%">
                <th>No.</th>
                <th>PIN SL CODE</th>
                <th>NAME</th>
                <th>POSITION</th>
                <th>MONTHLY SALARY</th>
                <th>Year-End Bonus</th>
                <th>CASH GIFT</th>
                <th>No.</th>
                <th>Amount Received</th>
                <th>Signature</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employees as $index => $emp)
                <tr>
                    <td>{{ $index + 1 }}</td> <!-- No. -->
                    <td>{{ $emp->employee_no ?? '' }}</td> <!-- SL CODE -->
                    <td>{{ $emp->name }}</td> <!-- Employee Name -->
                    <td>{{ $emp->position ?? '' }}</td>
                    <td>{{ $emp->salary ?? '' }}</td>
                    <td>{{ $emp->amount ?? '' }}</td>
                    <td>{{ $emp->cash_gift_amount ?? ''  }}</td>
                    <td>{{ $index + 1 }}</td> <!-- No. -->
                    <td>{{ ($emp->amount ?? 0) + ($emp->cash_gift_amount ?? 0) }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4"><strong>Total</strong></td>
                <td><strong>{{ number_format($employees->sum('salary'), 2) }}</strong></td>
                <td><strong>{{ number_format($employees->sum('amount'), 2) }}</strong></td>
                <td><strong>{{ number_format($employees->sum('cash_gift_amount'), 2) }}</strong></td>
                <td></td>
                <td><strong>{{ number_format($employees->sum(function ($emp) {
    return ($emp->amount ?? 0) + ($emp->cash_gift_amount ?? 0); }), 2) }}</strong>
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <table style="width: 100%;">
        <tbody>
            <tr>
                <td width="20%" style="text-align: left; border:none">
                    <p>CERTIFIED: Services have been<br>
                        duly rendered as stated above.</p>
                    <br>
                    <p>{{ $signatories[0]['signatory_1'] ?? '' }}</p>
                    <p style="margin-top: -13px">{{ $signatories[0]['signatory_position_1'] ?? '' }}</p>
                    <p>DATE: _____________________</p>
                </td>
                <td width="20%" style="text-align: left; border:none">
                    <p>CERTIFIED: <span
                            style="border: 1px solid black; padding: 2px 5px; display: inline-block;"></span>
                        Allotment obligated for the <br>
                        purpose as indicated above.<br>
                        <span style="border: 1px solid black; padding: 2px 5px; display: inline-block;"></span>
                        Supporting documents complete
                    </p>
                    <br>
                    <p>{{ $signatories[0]['signatory_2'] ?? '' }}</p>
                    <p style="margin-top: -13px">{{ $signatories[0]['signatory_position_2'] ?? '' }}</p>
                    <p>DATE: _____________________</p>
                </td>
                <td width="20%" style="text-align: left; border:none">
                    <p>CERTIFIED: Funds available.</p>
                    <br>
                    <p>{{ $signatories[0]['signatory_3'] ?? '' }}</p>
                    <p style="margin-top: -13px">{{ $signatories[0]['signatory_position_3'] ?? '' }}</p>
                    <p>DATE: _____________________</p>
                </td>
                <td width="20%" style="text-align: left; border:none">
                    <p><strong>APPROVED FOR PAYMENT:</strong></p>
                    <br>
                    <p>{{ $signatories[0]['signatory_4'] ?? '' }}</p>
                    <p style="margin-top: -13px">{{ $signatories[0]['signatory_position_4'] ?? '' }}</p>
                    <p>DATE: _____________________</p>
                </td>
                <td width="20%" style="text-align: left; border:none">
                    <p><strong>CERTIFIED:</strong> each employee whose name appears above</p>
                    <br>
                    <p>{{ $signatories[0]['signatory_5'] ?? '' }}</p>
                    <p style="margin-top: -13px">{{ $signatories[0]['signatory_position_5'] ?? '' }}</p>
                    <p>DATE: _____________________</p>
                </td>
            </tr>
        </tbody>
    </table>

    </table>
</body>

</html>
