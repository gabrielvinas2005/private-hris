<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">

    <title>Congratulatory Letter</title>

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
            padding-bottom: 0;
            margin-bottom: 0;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach($congratulatory_letters as $congratulatory_letter)
    <div class="card p-4">
        <div style="margin-left: 1in;margin-right: 1in;">
            <div>
                <label for="">Date: <u>{{ date('M d, Y',strtotime(now())) }}</u></label>
            </div>
            <div>
                <label for="">NAME: <u>{{ strtoupper($congratulatory_letter->name) }}</u></label>
            </div>
            <div>
                <div>
                    <P>Dear Ms./Mr.:</P><br>
                    <p style="text-indent: 60px; text-align: justify;">This refers to your application for the vacant position of <b><u>{{ $congratulatory_letter->position }}</u></b>
                        under the <b><u>{{ $congratulatory_letter->department }}</u></b> ,this Office.</p>
                    <br>
                    <p style="text-align: justify;">After evaluation of the Selection and Promotion Board (SPB), please be informed that you are considered for the said position.</p>
                    <br>
                    <p style="text-align: justify;">Please accomplished the following necessary documents for the issuance of your appointment paper not later than ___________________ :</p>
                    <br>
                    <div style="padding-left: 60px; padding-bottom: 30px;">
                        <p>1. Personal Date Sheet (2 copies & handwritten (print type), back to back with thumbmark and latest photo. Please use blue ball pen) </p>
                        <p>2. Certified True Copy (CTC) of Diploma (1 copy)</p>
                        <p>3. CTC Transcipt of Records/Masteral Degree TOR (1 copy), if applicable </p>
                        <p>4. CTC of CS Eligibility/Board Rating, if applicable</p>
                        <p>5. Photocopy of Certificate of Training Atteded for the last 5 years, if any</p>
                        <p>6. NBI Clearance</p>
                        <p>7. Medical Certificate (to be issued by DOH)</p>
                        <p>8. Certified True Copy (CTC) of Valid PRC ID</p>
                        <p>9. Tax Identification Number (TIN)</p>
                        <p>10. 1x1 ID (1 pc.) </p>
                    </div>
                    <p>Thank you,</p>
                    <br>
                    <p><b>{{ $signatories['signatory'] }}</b></p>
                    <p style="padding-top: 0; margin-top: 0;"><b>{{ $signatories['position'] }}</b></p>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</body>

</html>