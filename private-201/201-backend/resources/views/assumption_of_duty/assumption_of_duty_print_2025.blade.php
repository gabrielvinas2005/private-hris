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

    <title>Assumption of Duty</title>

    <style>
        body {
            margin: 30px 50px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 10px;
            vertical-align: top;
        }

        p {
            font-family: Arial;
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach ($appointments as $appointment)
    <div class="card p-4">
        <table>
            <!-- CS Form Header Row -->
            <tr>
                <td colspan="2">
                    <p style="font-size: 15px; font-weight: bold; font-style: italic;">CS Form No. 4</p>
                    <p style="font-size: 15px; font-style: italic;">Revised 2025</p>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <p style="padding-top: 40px;text-align: center; font-size: 19px; ">Republic of the Philippines</p>
                    <p style="padding-top:-10px;text-align: center; font-size: 16px;">{{ $orgCompanyName }}</p>
                    <p style="padding-top:-10px;text-align: center; font-size: 16px;">{{ $orgCompanyAddress }}</p>

                    <p style="padding-top: 40px; text-align: center; font-size: 19px; font-weight: bold;">
                        CERTIFICATION OF ASSUMPTION TO DUTY
                    </p>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <p style="text-align: justify; text-indent: 60px; line-height: 2; font-size: 16px;">
                        This is to certify that
                        <b><u><span style="font-size: 15px;">
                            {{ $appointment->gender === 'Male' ? 'Mr. ' : 'Ms. ' }}{{ strtoupper($appointment->name) }}
                        </span></u></b> has
                        assumed the duties and
                        responsibilities as <b><u><span style="font-size: 15px;">{{ $appointment->position }}</span></u></b> of
                        <b><u><span style="font-size: 15px;">{{ $appointment->department }}</span></u></b> effective
                        <b><u><span style="font-size: 15px;">{{ date('F d, Y', strtotime($appointment->date_of_effectivity)) }}</span></u></b>.
                    </p>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <p style="text-align: justify; text-indent: 60px; line-height: 2; font-size: 16px;">
                        This certification is issued in connection with the issuance of the appointment of
                        <b><u><span style="font-size: 15px;">{{ $appointment->gender === 'Male' ? 'Mr. ' : 'Ms. ' }}{{ strtoupper($appointment->name) }}</span></u></b> as
                        <b><u><span style="font-size: 15px;">{{ $appointment->position }}</span></u></b>.
                    </p>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <p style="text-align: justify; text-indent: 60px; line-height: 2; font-size: 16px;">
                        Done this
                        <u>{{ date('j', strtotime(now())) }}</u><sup style="font-size: 11px;">{{ date('S', strtotime(now())) }}</sup>
                        day of
                        <u>{{ date('F', strtotime(now())) }}</u> in <u><span style="font-size: 15px;">{{ date('Y', strtotime(now())) }}</span></u>.
                    </p>
                </td>
            </tr>

            <tr>
                <td style="width: 50%;"></td>
                <td style="width: 50%;">
                    <p style="text-align: center; margin-top: 30px;">
                        <b><u><span style="font-size: 15px;">{{ $signatories['signatory'] }}</span></u></b>
                    </p>
                    <p style="text-align: center;">______________________</p>
                    <p style="text-align: center;">Head of Office/Department/Unit</p>
                </td>
            </tr>

            <tr>
                <td style="width: 50%; text-align: center;">
                    <p style="margin-bottom: 20px; text-align: center;">
                        <span style="font-size: 15px;">Attested By:</span>
                    </p>
                    <p style="margin-bottom: 0px; text-align: center;">
                        <span style="font-size: 15px; font-weight: bold; ">{{ $signatories['assested_signatory'] }}</span>
                    </p>
                    <p style="text-align: center;">______________________</p>
                    <p style="text-align: center;">HRMO</p>
                    <p style="position:relative; right: 35px; margin-top: 10px;">
                        <span style="font-size: 15px;">Date:</span>
                        <span style="font-size: 15px; font-weight: bold;">{{ date('F d, Y', strtotime($signatories['assested_date'])) }}</span>
                    </p>
                </td>
                <td style="width: 50%;"></td>
            </tr>

            <tr>
                <td style="width: 50%;">
                    <p style="margin-top: 30px;">
                        <span style="font-size: 13px; font-style: italic;">201 file <br> Admin <br> COA <br> CSC</span>
                    </p>
                </td>
                <td style="width: 50%; text-align: center; vertical-align: middle;">
                    <div style="margin-top: 55px; font-size: 16px; border:1px solid black; padding: 8px; font-style: italic; line-height: 1.2;">
                        For submission to CSC FO<br>
                        within 30 days <span>from the</span><br>
                        <span>date of assumption</span> of the<br>
                        appointee
                    </div>
                </td>
            </tr>
        </table>
    </div>
    @endforeach
</body>

</html>
