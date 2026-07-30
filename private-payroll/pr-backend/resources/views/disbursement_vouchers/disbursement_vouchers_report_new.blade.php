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

    <title>Disbursement Voucher</title>

    <style>
        html,
        body {
            height: 297mm;
            width: 210mm;
            margin-top: 15px;
            margin-bottom: 15px;
            margin-left: auto;
            margin-right: auto;
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
            padding: 5px;
            text-align: center;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    <div class="card p-4">
        @php($total = 0)
        @foreach ($data as $rec)
            <div style="margin-right: 0.25in;margin-left: 0.25in;margin-bottom: 175px;">
                <p style="font-size: 10px;text-align: right;margin-right: 100px;">ANNEX B</p>
                <table style="width: 100%;border: solid 2px black;padding: 10px;border-collapse: collapse;">
                    <tr>
                        <td colspan="5">
                            <div style="padding-left: 5px; position: absolute;">
                                <img src="data:image/png;base64,{{ $image }}" width="50" height="50">
                            </div>
                            <div style="text-align: center;">
                                <p style="font-size: 10px;">Republic of the Philippines</p>
                                <p style="font-size: 12px;font-weight: bold;"><u>{{ strtoupper($company[0]->name) }}</u></p>
                                <p style="font-size: 10px;">{{ $company[0]->address }}</p><br>
                            </div>
                        </td>
                        <td colspan="1" style="text-align: left; vertical-align: bottom;">
                            <P>GENERAL FUND</P>
                            <p style="font-size: 15px;"><b></b></p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <div style="text-align: center;">
                                <p style="font-size: 16px;font-weight: bold;">DISBURSEMENT VOUCHER</p>
                            </div>
                        </td>
                        <td style="text-align: left;">
                            <p>No.:</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Mode of Payment</p>
                        </td>
                        <td colspan="5">
                            <div style="display: inline-block;margin: 15px 5px 5px 5px;text-align: center;width: 30%;">
                                <input type="checkbox">
                                <p style="margin: 5px;">Check</p>
                            </div>
                            <div style="display: inline-block;margin: 15px 5px 5px 5px;text-align: center;width: 30%;">
                                <input type="checkbox">
                                <p style="margin: 5px;">Cash</p>
                            </div>
                            <div style="display: inline-block;margin: 15px 5px 5px 5px;text-align: center;width: 30%;">
                                <input type="checkbox">
                                <p style="margin: 5px;">Others</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">
                            <p>PAYEE:</p>
                        </td>
                        <td colspan="3" style="text-align: left;font-weight: bold;">
                            <p>{{ strtoupper($rec->name) }}</p>
                        </td>
                        <td>
                            <p>TIN/Employee No: <br>{{ $rec->employee_no }}</p>
                        </td>
                        <td>
                            <p>Obligation Req. No.:</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Address:</td>
                        <td colspan="3" style="text-align: left;font-weight: bold;">
                        {{ $orgCompanyAddress }}
                        </td>
                        <td>Office/Unit/Project</td>
                        <td>Code</td>
                    </tr>
                    <tr>
                        <td colspan="5"><b>EXPLANATION</b></td>
                        <td>Amount</td>
                    </tr>

                    <tr>
                        <td rowspan="2" colspan="5" style="padding: 10px;text-align: left;">
                            <p style="text-indent: 50px;">To payment of ten ({{ number_format($rec->total_days, 0) }})
                                days monetization
                                from accumulated
                                leave credits</p>
                            <p style="margin-bottom: 20px;">in the amount of . . . . . . . . . . . </p>
                            <p style="text-align: center;"><b>{{ strtoupper($rec->particular) }}</b></p>
                        </td>
                        <td style="padding: 20px;text-align: right;">
                            <b>{{ number_format($rec->amount, 2, '.', ',') }}</b>
                        </td>
                        @php($total = $total + $rec->amount)
                    </tr>
                    <tr>
                        <td style="text-align: right;padding: 20px;">
                            <p><b>{{ number_format($total, 2, '.', ',') }}</b></p>
                        </td>
                    </tr>
                    {{-- Dynamic Data --}}
                    <tr>
                        <td colspan="3">
                            <p style="text-align: left;"><b>A. CERTIFIED:</b> </p>
                            <p style="text-align: left;margin-left: 30px;">Allotment obligated for the purpose as </p>
                            <p style="text-align: left;margin-left: 30px;">indicated above.</p>
                            <p style="text-align: left;margin-left: 30px;">Supporting documents complete</p>
                        </td>
                        <td colspan="3">
                            <p style="text-align: left;"><b>B. CERTIFIED:</b> </p>
                            <p style="text-align: left;margin-left: 30px;">FUNDS AVAILABLE</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: left">
                            Signature
                        </td>
                        <td colspan="2">

                        </td>
                        <td style="text-align: left;width: 50px;">
                            Signature
                        </td>
                        <td colspan="2">

                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: left">
                            Printed Name
                        </td>
                        <td>
                            <p><b></b></p>
                        </td>
                        <td style="text-align: left">Date</td>
                        <td style="text-align: left">
                            Printed Name
                        </td>
                        <td>
                            <b></b>
                        </td>
                        <td style="text-align: left;width: 30px;">
                            Date
                        </td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="text-align: left">
                            Position
                        </td>
                        <td colspan="2">
                            <p> Municipal Accountant</p>
                        </td>
                        <td rowspan="2" style="text-align: left">
                            Position
                        </td>
                        <td colspan="2">
                            <p>Municipal Treasurer</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <p>Head, Accounting Unit/Authorized Representative</p>
                        </td>
                        <td colspan="2">
                            <p>Treasurer/Authorized Representative</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <p style="text-align: left;"><b>C. APPROVED FOR PAYMENT: </b></p>
                        </td>
                        <td colspan="3">
                            <p style="text-align: left;"><b>D. RECEIVED PAYMENT:: </b></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">
                            Signature
                        </td>
                        <td></td>
                        <td style="text-align: left;">Date</td>
                        <td style="text-align: left;">
                            Check No.
                        </td>
                        <td></td>
                        <td style="text-align: left;">
                            Date
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: left">Printed Name</td>
                        <td colspan="2">
                            <p><b>HON. BERNARD S. WACLIN</b></p>
                        </td>
                        <td colspan="3" style="text-align: left;">Signature</td>
                    </tr>
                    <tr>
                        <td style="text-align: left">Position</td>
                        <td colspan="2">
                            <p>Municipal Mayor</p>
                        </td>
                        <td style="text-align: left;">Printed Name</td>
                        <td></td>
                        <td style="text-align: left;">Date</td>
                    </tr>
                    <tr>
                        <td colspan="3">Agency Head/Authorized Representative</td>
                        <td>O.R./OTHER DOCUMENTS</td>
                        <td style="text-align: left">JEV No.</td>
                        <td style="">Date</td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>

</body>

</html>
