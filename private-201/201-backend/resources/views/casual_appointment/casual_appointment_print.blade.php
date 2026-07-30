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

    <title>Plantilla of Casual Appointments</title>

    <style>
        html,
        body {
            height: 297mm;
            width: 100%;
            margin-top: 10px;
            margin-bottom: 15px;
            text-align: justify;
        }

        p {
            padding: 0;
            margin: 0;
        }

        .container {
            border: 1px solid black;
            width: 200px;
            font-family: Arial, sans-serif;
            font-size: 10px;
            text-align: center;
            float: right;
            padding-top: 5px;
            padding-bottom: 5px;
            padding-left: 5px;
            padding-right: 5px;
            margin-top: -20px;
            margin-left: 70px;
        }

        table {
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10px;
            width: 100%;

        }

        th,
        td {
            border: 1px solid black;
            padding: 3px;
            text-align: center;
            font-weight: normal;
        }

        .instructions {
            font-size: 11px;
            margin-top: 5px;
        }

        .certification {
            margin-top: 20px;
            font-size: 14px;
        }

        .appointing-officer {
            margin-top: 30px;
            font-weight: bold;
            font-size: 14px;
        }

        .page-break {
            page-break-before: always;
        }

        .end {
            text-align: center;
            font-weight: bold;
            margin-top: 20px;
            letter-spacing: 5px;
        }

        .footer {
            position: fixed;
            text-align: center;
            left: 0;
            bottom: 20px;
            width: 100%;
        }
    </style>
</head>

<body>
    @php
        $appointmentsPerPage = 15;
        $totalAppointments = $dtl->count();
        $totalPages = ceil($totalAppointments / $appointmentsPerPage);
        $chunks = $dtl->chunk($appointmentsPerPage);
    @endphp

    @foreach ($chunks as $pageIndex => $appointmentChunk)
        <!-- Main content -->
        <div class="card p-4">
            <div>
                <div>
                    <p style="font: 11px, arial;"><i>CS Form No. 34-A</i></p>
                    <p style="font: 11px, arial;"><i>Revised 2017</i></p>

                    <div class="container">
                        <p style="margin: 0;">National Government Agencies/</p>
                        <p style="margin: 0;">Government-Owned or Controlled</p>
                        <p style="margin: 0;">Corporation/State Universities and Colleges</p>
                        <p
                            style="font-family: Arial, sans-serif;font-size: 12px;text-align: left;float: right; margin-left: -180px;margin-right: 25px;margin-top:10px;">
                            <i>(State of Date of Receipt)</i>
                        </p>
                    </div>
                </div>
            </div>
            <div>
                <p style="margin-top: 30px; text-align: center; font: 12px Arial;">Republic of the Philippines</p><br>
                <p style="margin-top: -15px; text-align: center; font: 12px Arial;">
                    <span
                        style="display: inline-block; border-bottom: 1px solid black; width: 200px; text-align: center;">
                        {{ $company[0]->name ?? '' }}
                    </span>
                </p>

                <p style="padding-top: -10px; text-align: center; font: 12px Arial;">
                    <span
                        style="display: inline-block; border-bottom: 1px solid black; width: 200px; text-align: center;">
                        {{ $company[0]->address ?? '' }}
                    </span>
                </p>
                <p style="padding-top: -10px; text-align: center; font: 12px Arial;">(Name of Agency)</p>
            </div>
            <div>
                <p style="padding-top: 20px;padding-bottom: 10px;text-align: center; font: 16px, arial;">
                    PLANTILLA OF CASUAL APPOINTMENT</p>
            </div>
            <div>
                <p style="font-size: 12px;  ">Department / Office: <u>{{ $appointmentChunk[0]->department ?? '' }}</u>
                </p>
                </p>
                <p style="margin-top:-20px; font-size: 12px; float: right; margin-right:70px;">Source of
                    Funds:<u>{{ $data['source_of_funds'] }}</u></p>
            </div>
            <div class="instructions">
                <strong>INSTRUCTIONS:</strong><br>
                (1) Only a maximum of fifteen (15) appointees must be listed on each page of the Plantilla of Casual
                Appointments.<br>
                (2) Indicate ‘NOTHING FOLLOWS’ on the row following the name of the last appointee on the last page
                of the Plantilla.<br>
                (3) Provide proper pagination (Page n of n page/s).<br>
            </div>

            <table>
                <thead>
                    <tr>
                        <th rowspan="2"></th>
                        <th colspan="4">NAME OF APPOINTEE/S</th>
                        <th rowspan="2">POSITION TITLE <br> (Do not abbreviate)</th>
                        <th rowspan="2">EQUIVALENT SALARY/ JOB/ PAY GRADE</th>
                        <th rowspan="2">DAILY WAGE</th>
                        <th colspan="2">PERIOD OF EMPLOYMENT</th>
                        <th rowspan="2">NATURE OF APPOINTMENT <br> (Original/Reappointment/Reemployment)</th>
                        <th colspan="2">ACKNOWLEDGEMENT OF APPOINTEE</th>
                        <th colspan="2">CSCFO ACTION</th>
                    </tr>
                    <tr>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Name Extension <br> (Jr/III)</th>
                        <th>Middle Name</th>
                        <th>From <br> (mm/dd/yyyy)</th>
                        <th>To <br> (mm/dd/yyyy)</th>
                        <th>Signature</th>
                        <th>Date Received</th>
                        <th>A-Approved<br>D-Disapproved</th>
                        <th>Date of Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $ctr = 1;
                    @endphp
                    @foreach ($appointmentChunk as $index => $dtl)
                        <tr>
                            <td>{{ $ctr++ }}</td>
                            <td>{{ $dtl->last_name ?? '' }}</td>
                            <td>{{ $dtl->first_name ?? '' }}</td>
                            <td>{{ $dtl->suffix ?? '' }}</td>
                            <td>{{ $dtl->middle_name ?? '' }}</td>
                            <td>{{ $dtl->position ?? '' }}</td>
                            <td>{{ number_format($dtl->new_salary, 2, '.', ',') ?? '' }}</td>
                            <td>{{ number_format(($dtl->new_salary * 12) / 261, 2, '.', ',') ?? '' }}</td>
                            <td>{{ \Carbon\Carbon::parse($dtl->date_position_appointed)->format('M d, Y') ?? '' }}</td>
                            <td>{{ $currentDate }}</td>
                            <td>{{ $dtl->nature_of_appointment ?? '' }}</td>
                            <td></td>
                            <td>{{ \Carbon\Carbon::parse($data['date_received'])->format('M d, Y') ?? '' }}</td>
                            <td></td>
                            <td>{{ \Carbon\Carbon::parse($data['date_action'])->format('M d, Y') ?? '' }}</td>
                        </tr>
                    @endforeach
                    @php($ctr = 0)
                    @for ($i = 1; $i <= 15 - $totalAppointments; $i++)
                        <tr>
                            <td>{{ $ctr = $i + 1 }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor
                    <tr>
                        <td colspan="15" style="text-align: left">
                            The abovenamed personnel are hereby hired/appointed as casuals at the rate of
                            compensation stated opposite their names for the period indicated. It is understood that
                            such
                            employment will cease automatically at the end of the period stated unless renewed.
                            Any or all of them may be laid-off any time before the expiration of the employment
                            period
                            when
                            their services are no longer needed or funds are no longer available or the project
                            has already been completed/finished or their performance are below par.
                        </td>
                    </tr>

                </tbody>
            </table>
            <div class="end">
                @if ($pageIndex == $totalPages - 1)
                    <h5>--NOTHING FOLLOWS--</h5>
                @endif
            </div>
            <div class="certification">
                <table class="fixed-table" style="width: 100%; font-size: 12px; ">
                    <th colspan="2" class="cert-text" style="text-align: left; border:none; width: 40px">
                        <strong>CERTIFICATION:</strong> <br>
                        This is to certify that all requirements and supporting papers pursuant to <strong>CSC MC No.
                            24, s. 2017</strong>, as amended, have been complied with, reviewed, and found in order.

                    <th colspan="2" class="cert-sign" style="text-align:center; padding-left:30px; border:none;">
                        APPOINTING
                        OFFICER / AUTHORITY</th>
                    </th>
                    <th colspan="2" style="width:80px; border:none;  "></th>
                </table>
            </div>
            <div class="appointing-officer">
                <table style="font-size: 12px;">
                    <tbody>
                        <tr>
                            <td colspan="2" style="border:none; position:fixed;">
                                <p><u>{{ $signatories['signatory1'] }}</u></p>
                                <p>HRMO</p><br>
                                <p style="margin-right: 90px">Date:
                                    <u>{{ \Carbon\Carbon::parse($signatories['date1'])->format('M d, Y') }}</u>
                                </p>
                                <br>
                            </td>
                            <td colspan="2" style="float: center; border:none; position:fixed; padding-right: 50px;">
                                <p><u>{{ $signatories['signatory2'] }}</u></p>
                                <p>Appointing Officer / Authority</p><br>
                                <p>Date: <u>{{ \Carbon\Carbon::parse($signatories['date2'])->format('M d, Y') }}</u>
                                </p>
                                <br>
                            </td>
                            <td colspan="2" style=" border:none; position:fixed;">
                                <p><u>{{ $signatories['signatory3'] }}</u></p>
                                <p>CSC Officer</p><br>
                                <p style="margin-right: 40px">Date:
                                    <u>{{ \Carbon\Carbon::parse($signatories['date3'])->format('M d, Y') }}</u>
                                </p>
                                <br>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="footer">
                <p>Page
                    {{ $pageIndex + 1 }} of {{ $totalPages }}</p>
            </div>
            @if ($pageIndex < $totalPages - 1)
                <div class="page-break"></div>
            @endif
    @endforeach
    </div>
</body>

</html>
