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

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @foreach ($employees as $emp)

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
                                <p style="font-size: 12px;font-weight: bold;">{{ strtoupper($company[0]->name) }}</p>
                                <p style="font-size: 10px;"><i>{{ $company[0]->address }}</i></p>
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
                            <p>{{ strtoupper($emp->name) }}</p>
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
                            {{ strtoupper($company[0]->address) }}
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
                    <tr>
                        <td colspan="4">
                            <div>
                                <p>To Payment of OVERTIME for the month of With supporting papers attached in the amountof .
                                    . . . . . .</p><br>
                                <div style="width: 100%; font-family: Arial, sans-serif; padding: 10px;">
                                    <h3 style="margin-bottom: 10px;"><strong>OVERTIME:</strong></h3>

                                    <table style="width: 90%; border: none; border-collapse: collapse;">
                                        @php
                                            $total_earned = 0;
                                        @endphp
                                        @foreach ($data as $ot)
                                                                    @if($emp->employee_id == $ot->employee_id)
                                                                                                @php
                                                                                                    $total_earned += $ot->earned;
                                                                                                @endphp
                                                                                                <tr>
                                                                                                    <td style="padding: 5px; border: none;">
                                                                                                        {{ date('F d, Y', strtotime($ot->date)) }}
                                                                                                    </td>
                                                                                                    <td style="padding: 5px; border: none;">
                                                                                                        <span
                                                                                                            style="text-decoration: underline;">{{ number_format($ot->salary, 2, '.', ',') }}</span>
                                                                                                        × {{ $ot->rate }} × {{ $ot->total_hours }} hrs. =
                                                                                                        <p style="margin-left: -100px;">176</p>
                                                                                                    </td>
                                                                                                    <td style="padding: 5px; text-align: right; border: none;">
                                                                                                        {{ number_format($ot->earned, 2, '.', ',') }}
                                                                                                    </td>
                                                                                                </tr>
                                                                    @endif
                                        @endforeach
                                        <!-- Add the total row -->
                                        <tr>
                                            <td colspan="2" style="border: none;">
                                                Total Earned:
                                            </td>
                                            <td style="padding: 5px; text-align: right; font-weight: bold; border: none;">
                                                {{ number_format($total_earned, 2, '.', ',') }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </td>
                        <td>P
                            {{ number_format($total_earned, 2, '.', ',') }}
                        </td>
                    </tr>
                    {{-- Dynamic Data --}}
                    <tr>
                        <td colspan="3">
                            <p style="text-align: left;">A.</p>
                            <div style="width: 100%; font-family: Arial, sans-serif; padding: 10px; line-height: 1.5;">
                                <h4 style="margin-left: -300px; margin-top: -25px;"><strong>CERTIFIED:</strong></h4>

                                <div style="margin-bottom: 10px; margin-top: -10px;">
                                    <input type="checkbox" style="margin-left: -93px;"> Allotment obligated for the purpose
                                    as indicated above<br>
                                    <input type="checkbox" style="margin-left: -200px;"> Supporting documents complete
                                </div>

                                <div style="margin-top: 20px; display: flex; justify-content: space-between; width: 100%;">
                                    <div style="text-align: left;">
                                        <span style="text-decoration: underline; font-weight: bold;">YOLANDA D.
                                            CARANTES</span><br>
                                        OIC-Municipal Accountant
                                    </div>
                                    <div style="text-align: right;  margin-right: 100px;">
                                        Date: ___________
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td colspan="2">
                            <p style="text-align: left;">B.</p>
                            <div style="width: 100%; font-family: Arial, sans-serif; padding: 10px; line-height: 1.5;">
                                <h4 style="margin-left: -200px; margin-top: -25px;"><strong>CERTIFIED:</strong></h4>

                                <div style="margin-bottom: 10px; margin-top: -10px;">
                                    <input style="margin-left: -43px;"> FUNDS AVAILABLE
                                </div>

                                <div style="margin-top: 20px; display: flex; justify-content: space-between; width: 100%;">
                                    <div style="text-align: left;">
                                        <span style="text-decoration: underline; font-weight: bold;">IRENE F.
                                            FERNANDO</span><br>
                                        Municipal Treasurer
                                    </div>
                                    <div style="text-align: right;  margin-right: 100px;">
                                        Date: ___________
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <td colspan="3">
                        <p style="text-align: left;">C.</p>
                        <div style="width: 100%; font-family: Arial, sans-serif; padding: 10px; line-height: 1.5;">
                            <h4 style="margin-left: -300px; margin-top: -25px;"><strong>CERTIFIED:</strong></h4>

                            <div style="margin-bottom: 10px; margin-top: -10px;">
                            </div>

                            <div style="margin-top: 20px; display: flex; justify-content: space-between; width: 100%;">
                                <div style="text-align: left;">
                                    <span style="text-decoration: underline; font-weight: bold;">HON. BERNARD S.
                                        WACLIN</span><br>
                                    Municipal Mayor
                                </div>
                                <div style="text-align: right;  margin-right: 100px;">
                                    Date: ___________
                                </div>
                            </div>
                        </div>
                    </td>
                    <td colspan="2">
                        <p style="text-align: left;">D.</p>
                        <h4 style="margin-left: -100px; margin-top: -25px;"><strong>RECEIVED PAYMNENT:</strong></h4>
                        <table style="width: 100%; border-collapse: collapse;">
                            <!-- Check No. and Date Row -->
                            <tr>
                                <td style="border: 1px solid #000; padding: 5px;">Check No.</td>
                                <td style="border: 1px solid #000; padding: 5px; width: 60%;">&nbsp;</td>
                                <td style="border: 1px solid #000; padding: 5px; width: 15%;">Date</td>
                            </tr>

                            <!-- Signature Row -->
                            <tr>
                                <td style="border: 1px solid #000; padding: 5px;" colspan="3">Signature</td>
                            </tr>

                            <!-- Printed Name, Name, and Date Row -->
                            <tr>
                                <td style="border: 1px solid #000; padding: 5px;">Printed Name</td>
                                <td style="border: 1px solid #000; padding: 5px; text-align: center;">
                                    <strong><u>{{ strtoupper($emp->name) }}</u></strong>
                                </td>
                                <td style="border: 1px solid #000; padding: 5px;">Date</td>
                            </tr>

                            <!-- O.R./Other Documents, JEV No., and Date Row -->
                            <tr>
                                <td style="border: 1px solid #000; padding: 5px;">O.R./OTHER Documents</td>
                                <td style="border: 1px solid #000; padding: 5px;">JEV No.</td>
                                <td style="border: 1px solid #000; padding: 5px;">Date</td>
                            </tr>
                        </table>
            </div>

            </td>
            </tr>
            </table>
        </div>
        <div class="page-break"></div>
    @endforeach
</body>

</html>