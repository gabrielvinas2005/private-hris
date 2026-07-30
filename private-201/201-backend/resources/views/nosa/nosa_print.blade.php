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

    <title>Notice of Salary Adjustment</title>

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

        .tab {
            display: inline-block;
            margin-left: 260px;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach ($nosa as $nosa)
        <div class="card p-4">
            <div style="margin-left: 1in;margin-right: 1in;">
                {{-- <!-- <div style="text-align: center;">
                <img src="data:image/png;base64,{{ $image }}" style="width:7.6in;height: 1.6in;">
            </div> --> --}}
                <div style="text-align: center;">
                    <h4 style="padding-top: 40px;">NOTICE OF SALARY ADJUSTMENT</h4>
                </div>
                <br>
                <div style="float: right;">
                    <p>Date: <b><u>{{ date('M d, Y', strtotime(now())) }}</u></b></p>
                </div>
                <br><br>
                <div>
                    <div>
                        <p><b><u>{{ $nosa->name }}</u></b></p>
                        <p><b>Office: <u>{{ strtoupper($nosa->department) }}</b></u></p>
                    </div>
                    <br>
                    <div>
                        <label for="">Sir / Madam:</label>
                    </div>
                    <br>
                    <div>
                        <p style="text-align: justify; text-indent: 50px;">
                            Pursuant to National Budget Circular__________, dated _________ implementing
                            Executive Order No.
                            {{ $nosa->enabling_law }} , your salary is hereby adjusted effective
                            <b><u>{{ date('M d, Y', strtotime($nosa->effectivity)) }}</u></b>
                            as follows:
                        </p>
                    </div>
                    <br>
                    <div>
                        <table>
                            <tr>
                                <td style="width: 300px;">
                                    <p style="text-align: justify;">1. Adjusted monthly basic salary
                                        effective
                                        <u>{{ date('M d, Y', strtotime(now())) }}</u> under the
                                        new Salary Schedule: <b><u>SG {{ $nosa->new_salary_grade_id }} Step
                                                {{ $nosa->new_salary_step_id }} </u></b>
                                    </p>
                                </td>
                                <td style="padding-left: 80px;">
                                    <b><u>P {{ number_format($nosa->new_salary, 2, '.', ',') }}</u></b></p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p style="text-align: justify;">2. Actual monthly basic salary as
                                        of <u>{{ date('M d, Y', strtotime(now())) }}</u> <b><u>SG
                                                {{ $nosa->old_salary_grade_id }}
                                                Step {{ $nosa->old_salary_step_id }} </u></b>
                                    </p>
                                </td>
                                <td style="padding-left: 80px;">
                                    <p style="text-align: justify;"><b><u>P
                                                {{ number_format($nosa->new_salary, 2, '.', ',') }}</u></b>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p style="text-align: justify;">3. Monthly salary adjustment effective
                                        <u>{{ date('M d, Y', strtotime(now())) }}</u>
                                    </p>
                                </td>
                                <td style="padding-left: 80px;">
                                    <p style="text-align: justify;"><b><u>P
                                                {{ number_format($nosa->new_salary, 2, '.', ',') }}</u></b></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <br>
                    <div>
                        <p style="text-indent: 50px;">
                            It is understood that this salary adjustment is subject to review and post-audit,
                            and to appropriate re-adjustment and refund if found not in order.
                        </p>
                    </div>
                    <br><br>
                    <div style="float: right;">
                        <P>Very truly yours,</P>
                        <br><br>
                        <p><b><u>{{ $signatories['signatory_admin'] }}</u></b></p>
                        <p style="text-align: center;">{{ $signatories['position_admin'] }}</p>
                        <br>
                        <P>By:</P>
                        <br><br>
                        <p><b><u>{{ $signatories['signatory'] }}</u></b></p>
                        <p style="text-align: center;">{{ $signatories['position'] }}</p>
                    </div>
                    <br>
                    <div style="padding-top: 250px;">
                        <p>Position: <b><u>{{ $nosa->position }}</u></b></p>
                        <p>Salary Grade: <b><u>{{ $nosa->new_salary_grade_id }} /
                                    {{ $nosa->new_salary_step_id }}</u></b></p>
                        <p>Item No./Unique Item No.,<b><u>{{ $nosa->code }}</u></b></p>
                        <p>and/or Plantilla of Personnel:<b><u>_______________</u></b></p>
                    </div>
                    <br>
                </div>
            </div>
        </div>
        <p style="text-align: right;margin-bottom: 0px;margin-right: 20px; font-weight: bold; margin-top: 60px">
            {{ $footer['document_no'] }}
        </p>
        <p style="text-align: right;margin-top: 0px;margin-right: 20px;font-weight: bold;">
            {{ $footer['revision'] }}
        </p>
    @endforeach
</body>

</html>
