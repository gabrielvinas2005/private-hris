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

    <title>Assumption of Duty</title>

    <style>
        html,
        body {
            height: 297mm;
            width: 210mm;
            margin: 15px auto;
            text-align: justify;
            /* Default justification for the whole body */
        }

        p {
            text-align: justify;
            text-indent: 50px;
            /* Add indentation for paragraphs */
            margin: 0;
        }

        .content p {
            text-align: justify;
        }

        .date-section {
            text-align: left;
            /* Align specific sections differently if needed */
        }
    </style>

</head>

<body>
    <div class="card p-4">
        <div style="margin-left: 1in;margin-right: 1in;">
            <div style="margin-left: -.5in;">
                <p style="font: 12px, arial;"><i>CS Form No. 4</i></p>
                <p style="font: 12px, arial;"><i>Revised 2018</i></p>
            </div>
            <div>
                <p style="padding-top: 40px;text-align: center; font:  18px, arial;">Republic of the Philippines</p>
                <p style="margin: 8px;"><strong>{{ strtoupper($orgCompanyName) }}</strong></p>
                <p style="margin-top: -10px;padding: 0;">{{ $orgCompanyAddress }}</p>
            </div>
            <div>
                <p style="padding-top: 40px;padding-bottom: 20px;text-align: center; font: 18px, arial;">CERTIFICATION</p>
            </div>
            <div class="content">
                <p style="text-align: justify; text-indent: 50px;">
                    This is to certify that based on the records of this Office, <strong>there is no applicant who meets all the qualification requirements</strong> to the
                    <strong>{{ $position_title }}</strong> position in the
                    <strong>{{ $agency_name }}</strong>, <strong>{{ $location }}</strong>.
                </p><br>
                <p style="text-align: justify; text-indent: 50px;">
                    This certification is issued pursuant to Section 5 (k), Rule II of CSC Memorandum No. 24, s. 2017 (2017 Omnibus Rules on Appointments and Other Human Resource Actions), as amended.
                </p><br>
                <p style="text-align: justify;">
                    I agree that any misrepresentation made in this certification shall cause the filing of administrative/criminal case/s against me.
                </p>
            </div><br><br><br><br>
            <div class="signature-section" style="text-align: center; float:right;">
                <p style="border-bottom: 1px solid; ">{{$author}}</p>
                <strong>Appointing Officer/Authority</strong>
            </div><br><br>
            <div class="date-section" style="text-align: justify;">
                <strong>Date:</strong>{{ $date }}
            </div>
        </div>
</body>

</html>