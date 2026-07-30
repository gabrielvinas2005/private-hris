<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Travel Abroad Endorsement</title>

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
                        Respectfully forward to the HONORABLE _____________________, SECRETARY, DEPARTMENT OF JUSTICE,
                        <b>recommending approval</b>,
                        the within request of <u>{{ $employee->name }}</u>, of this Authority (___________________) to
                        travel abroad for the purpose stated in ____ letter dated ____________ pursuant to Executive
                        Order No. 6 dated March 12, 1986.
                    </p>
                </div>
                <br>
                <div>
                    <!-- <p style="padding-bottom: 0.1in; font: normal 18px Times, arial;">TO WHOM IT MAY CONCERN:</p><br> -->
                    <p
                        style="text-indent: 0.5in;text-align: justify; font: normal 18px Times, arial; line-height: 1.8;">
                        It may be stated that the leave of absence of <u>{{ $employee->name }}</u> for the period of
                        ______________________,
                        is covered by an application for leave and that his/her travel abroad will not entail any
                        expense on the part of the government and that her absence will not hamper the operational
                        efficiency of this Authority as
                        directed in the Memorandum from the Executive Secretary dated 03 January 2018. Furthermore,
                        he/she has complied with the requirements provided for in Department Order No. 385 dated 14
                        September 1998 as amended
                        by DOJ Department Circular No. 59 dated 08 August 2003.
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
