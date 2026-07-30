<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Terminal Leave Endorsement</title>

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
                <div style="text-align: center;padding-bottom: 0.5in;width:7.6in;height: 1.6in;">
                    {{-- <!-- <img src="data:image/png;base64,{{ $image }}" style="width:7.6in;height: 1.6in;"> --> --}}
                </div>
                <div>
                    <h2 style="text-align: center;padding-bottom: 0.5in;margin: 0; font: normal 24px Times, arial;">
                        <u>1st Indorsement</u>
                    </h2>
                </div>
                <div>
                    <!-- <p style="padding-bottom: 0.1in; font: normal 18px Times, arial;">TO WHOM IT MAY CONCERN:</p><br> -->
                    <p
                        style="text-indent: 0.5in;text-align: justify; font: normal 18px Times, arial; line-height: 1.8;">
                        Respectfully returned to <u>{{ $employee->name }}</u>, of this Authority (___________________)
                        with address at ____________________
                        hereby approving the enclosed application for _______________ <b>days</b> terminal vacation and
                        sick leave of absence with pay. The commutation and payment of the money value of said
                        accumulated leave
                        is hereby authorized, subject to the availability of funds and the usual accounting and auditing
                        requirements.
                    </p>
                </div>
                <br>
                <br>
                <div style="padding-top: 1in;float: right;">
                    <p style="text-align: center; padding:0;">{{ $signatories['signatory'] }}</p>
                    <p style="text-align: center; padding:0;">{{ $signatories['position'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
