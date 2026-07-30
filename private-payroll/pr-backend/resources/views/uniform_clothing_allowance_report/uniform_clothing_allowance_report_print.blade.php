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
        <div style="position: absolute;left: 0;">
            <img src="data:image/png;base64,{{ $image }}" width="100" height="100">
        </div>
        <div style="text-align: center;">
            <p>Republic of the Philippines</p>
            <p style="margin: 0;padding: 0; margin-top: -15px">{{ isset($companies[0]) && $companies[0] ? $companies[0]->name : 'N/A' }}</p>
            <p style="margin-top: -1px">{{ isset($data[0]) && $data[0] ? $data[0]->department : 'N/A' }}</p>
            <h3 style="margin-top: -10px; padding: 0;">GENERAL PAYROLL</h3>
            <p style="margin-top: -13px;padding: 0;">CLOTHING/UNIFORM ALLOWANCE FOR {{ $year ?? 'N/A' }} </p>
        </div>
    </div>

    <div class="table-title">General Form No.4 <br>
        Revised January 1922 <br>
        We acknowledge receipt of the sum shown opposite our name as full compensation
        for services rendered for the period stated:</div>
    <table>
        <thead>
            <tr>
                <th></th>
                <th>Sl Code</th>
                <th>NAME</th>
                <th>DEPARTMENT</th>
                <th>POSITION</th>
                <th>UNIFORM ALLOWANCE</th>
                <th>Net Amount</th>
                <th>AMOUNT RECEIVED</th>
                <th>Signature</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $emp)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $emp->employee_no ?? '' }}</td> 
                <td>{{ $emp->name }}</td> 
                <td>{{ $emp->department ?? '' }}</td> 
                <td>{{ $emp->position ?? '' }}</td> 
                <td>{{ $emp->cloth_rate ?? '' }}</td>
                <td>{{ $emp->cloth_rate ?? '' }}</td>
                <td>{{ $emp->cloth_rate ?? '' }}</td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align: right;"><strong>Total</strong></td>
                <td><strong>{{ number_format($data->sum('cloth_rate'), 2) }}</strong></td>
                <td></td>
                <td><strong>{{ number_format($data->sum('cloth_rate'), 2) }}</strong></td>
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
                    <p>{{ isset($signatories[0]) ? ($signatories[0]['signatory_1'] ?? '') : '' }}</p>
                    <p style="margin-top: -13px; vertical-align: bottom;">{{ isset($signatories[0]) ? ($signatories[0]['signatory_position_1'] ?? '') : '' }}</p>
                </td>
                <td width="20%" style="text-align: left; border:none">
                    <p>CERTIFIED: <span style="border: 1px solid black; padding: 2px 5px; display: inline-block;"></span>
                        Allotment obligated for the <br>
                        purpose as indicated above.<br>
                        <span style="border: 1px solid black; padding: 2px 5px; display: inline-block;"></span>
                        Supporting documents complete
                    </p>
                    <br>
                    <p>{{ isset($signatories[0]) ? ($signatories[0]['signatory_2'] ?? '') : '' }}</p>
                    <p style="margin-top: -13px; vertical-align: bottom;">{{ isset($signatories[0]) ? ($signatories[0]['signatory_position_2'] ?? '') : '' }}</p>
                </td>
                <td width="20%" style="text-align: left; border:none">
                    <p>CERTIFIED: Funds available.</p>
                    <br>
                    <p>{{ isset($signatories[0]) ? ($signatories[0]['signatory_3'] ?? '') : '' }}</p>
                    <p style="margin-top: -13px; vertical-align: bottom;">{{ isset($signatories[0]) ? ($signatories[0]['signatory_position_3'] ?? '') : '' }}</p>
                </td>
                <td width="20%" style="text-align: left; border:none">
                    <p><strong>APPROVED FOR PAYMENT:</strong></p>
                    <br>
                    <p>{{ isset($signatories[0]) ? ($signatories[0]['signatory_4'] ?? '') : '' }}</p>
                    <p style="margin-top: -13px; vertical-align: bottom;">{{ isset($signatories[0]) ? ($signatories[0]['signatory_position_4'] ?? '') : '' }}</p>
                </td>
                <td width="20%" style="text-align: left; border:none">
                    <p><strong>CERTIFIED:</strong> each employee whose name appears above</p>
                    <br>
                    <p>{{ isset($signatories[0]) ? ($signatories[0]['signatory_5'] ?? '') : '' }}</p>
                    <p style="margin-top: -13px; vertical-align: bottom;">{{ isset($signatories[0]) ? ($signatories[0]['signatory_position_5'] ?? '') : '' }}</p>
                </td>
            </tr>
            <tr>
                <td style="border:none;text-align:left; font-size: 12px;">Date: <u> {{ now()->format('F d, Y') }}</u>
                </td>
                <td style="border:none;text-align:left; font-size: 12px;">Date: <u> {{ now()->format('F d, Y') }}</u>
                </td>
                <td style="border:none;text-align:left; font-size: 12px;">Date: <u> {{ now()->format('F d, Y') }}</u>
                </td>
                <td style="border:none;text-align:left; font-size: 12px;">Date: <u> {{ now()->format('F d, Y') }}</u>
                </td>
                <td style="border:none;text-align:left; font-size: 12px;">Date: <u> {{ now()->format('F d, Y') }}</u>
                </td>
            </tr>
        </tbody>
    </table>

    </table>
</body>

</html>
