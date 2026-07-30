<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Certificate of Appearance</title>

    <style>
        html,
        body {
            height: 297mm;
            width: 210mm;
            margin-top: 15px;
            margin-bottom: 15px;
            margin-left: auto;
            margin-right: auto;
            /* background-color: gray; */
        }

        p {
            padding-bottom: 9px;
            margin: 0;
            font: normal 18px "Times", sans-serif;
        }

        td {
            font: normal 18px "Times", sans-serif;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach ($employees as $employee)
        <div class="card p-4">
            <div style="margin-left: 1.5in;margin-right: 1.5in;">
                <!-- <div style="padding-left: 20px; padding-top: 15px; position: absolute;">
                <img src="data:image/png;base64,{{ $image }}" width="70" height="70">
            </div>
            <div style="text-align: center;padding-top: 15px; padding-bottom: 30px;">
                <p>Republic of the Philippines</p>
                <h4 style="margin: 0;padding: 0;">Land Registration Authority</h4>
                <p><i>East Avenue, Cor. NIA Road, Quezon City</i></p>
            </div> -->
                <div>
                    <h2
                        style="text-align: center; padding-top: 50px; padding-bottom: 0.5in;margin: 0; font: normal 24px Times, arial;">
                        <u>C E R T I F I C A T I O N</u>
                    </h2>
                </div>
                <div>
                    <p style="padding-bottom: 0.1in; font: normal 18px Times, arial;">TO WHOM IT MAY CONCERN:</p><br>
                    <p
                        style="text-indent: 0.5in;text-align: justify; font: normal 18px Times, arial; line-height: 1.8;">
                        This is to certify that <u>{{ $employee->name }}</u>, {{ $employee->position }} appeared in this
                        Authority on
                        {{ date('F d, Y', strtotime($employee->date_hired)) }}.
                    </p>
                </div>
                <br>
                <div>
                    <p style="text-indent: 0.5in;text-align: justify;">Issued upon the request of
                        <u>{{ $employee->name_sig }}</u> for
                        whatever legal purpose it may serve {{ $employee->gender_id == 1 ? 'her' : 'him' }}.
                    </p>
                </div>
                <br>
                <div>
                    <p  
                        style="text-indent: 0.5in;text-align: justify; font: normal 18px Times, arial; line-height: 1.8;">
                        {{ $orgCompanyAddress }}, this {{ date('d', strtotime(now())) }} day of
                        {{ date('F', strtotime(now())) }}, {{ date('Y', strtotime(now())) }}.</p>
                </div>
                <div style="padding-top: 1in;float: right;">
                    <p style="text-align: center; padding:0;">{{ $signatories['signatory'] }}</p>
                    <p style="text-align: center; padding:0;">{{ $signatories['position'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
