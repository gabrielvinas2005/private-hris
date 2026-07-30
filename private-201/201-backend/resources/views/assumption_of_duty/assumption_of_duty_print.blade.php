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
        html,
        body {
            height: 297mm;
            width: 210mm;
            margin-top: 15px;
            margin-bottom: 15px;
            margin-left: auto;
            margin-right: auto;
            text-align: justify;
        }

        p {
            padding: 0;
            margin: 0;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach ($appointments as $appointment)
    <div class="card p-4">
        <div style="margin-left: 1.5in;margin-right: 1.5in;">
            <div>
                <p style="font: 12px, arial;"><i>CS Form No. 4</i></p>
                <p style="font: 12px, arial;"><i>Revised 2018</i></p>
            </div>
            <div>
                <p style="padding-top: 40px;text-align: center; font:  18px, arial;">Republic of the Philippines</p>
                <p style="padding-top:-10px;text-align: center; font:  15px, arial;">{{ $orgCompanyName }}</p>
                <p style="padding-top:-10px;text-align: center; font:  15px, arial;">{{ $orgCompanyAddress }}</p>
            </div>
            <div>
                <p style="padding-top: 40px;padding-bottom: 20px;text-align: center; font: 18px, arial;">
                    CERTIFICATION OF ASSUMPTION TO DUTY</p>
            </div>
            <div>
                <div>
                    <p style="text-align: justify ;text-indent: 60px; line-height: 1.8; font: normal 16px, arial;">
                        This is to certify that Ms./Mr. <b><u>{{ strtoupper($appointment->name) }}</u></b> has
                        assumed the duties and
                        responsibilities as <u>{{ $appointment->position }}</u> of
                        <u>{{ $appointment->department }}</u> effective
                        <u>{{ date('M d, Y', strtotime($appointment->date_of_effectivity)) }}</u>.
                    </p>
                </div>
                <br>
                <div>
                    <p style="text-align: justify ;text-indent: 60px; line-height: 1.8; font: normal 16px, arial;">
                        This certification is issued in connection with the issuance of the appointment of
                        <u>{{ $appointment->name_sig }}</u> as <u>{{ $appointment->position }}</u>.
                    </p>
                </div>
                <br>
                <div>
                    <p style="text-align: justify ;text-indent: 60px; line-height: 1.8; font: normal 16px, arial;">
                        Done this <u>{{ date('d', strtotime(now())) }}</u> day of
                        <u>{{ date('M', strtotime(now())) }} in {{ date('Y', strtotime(now())) }}</u> .
                    </p>
                </div>
                <div style="padding-top: 30px;float: right;">
                    <br>
                    <p style="text-align: center; font-family: arial;">
                        <u><b>{{ $signatories['signatory'] }}</b></u>
                    </p>
                    <p style="text-align: center; font-family: arial;">{{ $signatories['position'] }}</p>
                </div>
                <div style="padding-top: 200px;">
                    <label style="padding: 0; font-family: arial;">Date:
                        <u>{{ date('M d, Y', strtotime($signatories['assested_date'])) }}</u></label>
                    <br>
                    <br><br>
                    <label style="padding-bottom: 50px;padding-left: 0; font-family: arial;">Attested By:</label>
                    <br><br><br>
                    <p style="font-family: arial;"><u><b>{{ $signatories['assested_signatory'] }}</b></u></p>
                    <p style="font-family: arial;">{{ $signatories['assested_position'] }}</p>
                    <br><br><br>
                </div>
                <div style="float: left;">
                    <p style="font: italic 12px, arial;">201 file</p>
                    <p style="font: italic 12px, arial;">Admin</p>
                    <p style="font: italic 12px, arial;">COA</p>
                    <p style="font: italic 12px, arial;">CSC</p>
                </div>
                <div
                    style="border: 1px solid black; width: 200px; padding-top: -170px; padding: 10px; margin-top: 20px; font-family: Arial, sans-serif; font-size: 12px; text-align: center; float: right;">
                    <p style="margin: 0;">For submission to CSC FO</p>
                    <p style="margin: 0;">within 30 days from the</p>
                    <p style="margin: 0;">date of assumption of the</p>
                    <p style="margin: 0;">appointee</p>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</body>

</html>