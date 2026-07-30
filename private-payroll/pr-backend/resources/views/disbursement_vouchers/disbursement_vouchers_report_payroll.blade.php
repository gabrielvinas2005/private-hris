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
        <div style="margin-right: 0.25in;margin-left: 0.25in;">
            <p style="font-size: 10px;text-align: right;margin-right: 20px;">ANNEX B</p>
            <table style="width: 100%;border: solid 2px black;padding: 10px;border-collapse: collapse;">
                <tr>
                    <td colspan="5">
                        <div style="padding-left: 5px; position: absolute;">
                            <img src="data:image/png;base64,{{ $image }}" width="50" height="50">
                        </div>
                        <div style="text-align: center;">
                            <p style="font-size: 10px;">Republic of the Philippines</p>
                            <p style="font-size: 12px;font-weight: bold;">Department of Justice</p>
                            <p style="font-size: 12px;font-weight: bold;">{{ strtoupper($company[0]->name ?? $orgCompanyName) }}</p>
                            <p style="font-size: 10px;"><i>{{ $company[0]->address ?? $orgCompanyAddress }}</i></p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="4">
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
                    <td colspan="4">
                        <div style="display: inline-block;margin: 5px;">
                            <input type="checkbox">
                            <p style="display: inline-block;margin: 5px;">Check</p>
                        </div>
                        <div style="display: inline-block;margin: 5px;">
                            <input type="checkbox">
                            <p style="display: inline-block;margin: 5px;">CASH</p>
                        </div>
                        <div style="display: inline-block;margin: 5px;">
                            <input type="checkbox">
                            <p style="display: inline-block;margin: 5px;">Others</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p>Payee</p>
                    </td>
                    <td colspan="2" style="text-align: left;font-weight: bold;">
                        <p>{{ strtoupper($company[0]->name ?? $orgCompanyName) }}</p>
                    </td>
                    <td>
                        <p>TIN/Employee No:</p>
                    </td>
                    <td>
                        <p>ORS/BURS No.:</p>
                    </td>
                </tr>
                <tr>
                    <td>Address</td>
                    <td colspan="4" style="text-align: left;font-weight: bold;">
                        {{ strtoupper($company[0]->address ?? $orgCompanyAddress) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="border-top: none;">
                        EXPLANATION
                    </td>
                    <td>
                        AMOUNT
                    </td>
                </tr>
                @php
                    $total_amount = 0;
                @endphp
                @foreach($data as $row)
                <tr>
                    <td colspan="4" style="text-align: center;">
                        <div>
                            <p>To Payment of {{ $row->particular ?? 'N/A' }} in the amount of . . . . . .</p>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        {{ number_format($row->amount ?? 0, 2, '.', ',') }}
                    </td>
                </tr>
                @php
                    $total_amount += $row->amount ?? 0;
                @endphp
                @endforeach
                @if(count($data) == 0)
                <tr>
                    <td colspan="4"></td>
                    <td></td>
                </tr>
                @endif
                <tr>
                    <td colspan="3" style="text-align: left;">
                        <p style="text-align: left;font-weight: bold;">A.</p>
                        <div style="width: 100%; font-family: Arial, sans-serif; padding: 10px; line-height: 1.5;">
                            <p style="font-size: 10px; margin-left: 20px; margin-top: -10px;">
                                Certified: Allotment obligated for the purpose as indicated above. Supporting documents complete
                            </p>
                            <div style="margin-top: 20px;">
                                <p style="margin-left: 20px;">Signature:__________________________</p>
                                <p style="margin-left: 20px;">Printed Name:__________________________</p>
                                <p style="margin-left: 20px;">Position:__________________________</p>
                                <p style="margin-left: 20px;">Date:__________________________</p>
                            </div>
                        </div>
                    </td>
                    <td colspan="2" style="text-align: left;">
                        <p style="text-align: left;font-weight: bold;">B.</p>
                        <div style="width: 100%; font-family: Arial, sans-serif; padding: 10px; line-height: 1.5;">
                            <p style="font-size: 10px; margin-left: 20px; margin-top: -10px;">
                                Certified: FUNDS AVAILABLE
                            </p>
                            <div style="margin-top: 20px;">
                                <p style="margin-left: 20px;">Signature:__________________________</p>
                                <p style="margin-left: 20px;">Printed Name:__________________________</p>
                                <p style="margin-left: 20px;">Position:__________________________</p>
                                <p style="margin-left: 20px;">Date:__________________________</p>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: left;">
                        <p style="text-align: left;font-weight: bold;">C.</p>
                        <div style="width: 100%; font-family: Arial, sans-serif; padding: 10px; line-height: 1.5;">
                            <p style="font-size: 10px; margin-left: 20px; margin-top: -10px;">
                                APPROVED FOR PAYMENT
                            </p>
                            <div style="margin-top: 20px;">
                                <p style="margin-left: 20px;">Signature:__________________________</p>
                                <p style="margin-left: 20px;">Printed Name:__________________________</p>
                                <p style="margin-left: 20px;">Position:__________________________</p>
                                <p style="margin-left: 20px;">Date:__________________________</p>
                            </div>
                        </div>
                    </td>
                    <td colspan="2" style="text-align: left;">
                        <p style="text-align: left;font-weight: bold;">D.</p>
                        <div style="width: 100%; font-family: Arial, sans-serif; padding: 10px; line-height: 1.5;">
                            <p style="font-size: 10px; margin-left: 20px; margin-top: -10px;">
                                RECEIVED PAYMENT
                            </p>
                            <div style="margin-top: 20px;">
                                <p style="margin-left: 20px;">Signature:__________________________</p>
                                <p style="margin-left: 20px;">Printed Name:__________________________</p>
                                <p style="margin-left: 20px;">Date:__________________________</p>
                                <p style="margin-left: 20px;">Check No.:__________________________</p>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>

