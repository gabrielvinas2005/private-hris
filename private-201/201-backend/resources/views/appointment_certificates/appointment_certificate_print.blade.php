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

    <title>Appointment Certificate</title>

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
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach($appointments as $appointment)
    <div class="card p-4">
        <div style="float: right; border: 1px solid black; padding: 5px;margin-right: 0.5in;">
            <p><i><b>For Accredited/Deregulated Agencies</b></i></p>
        </div>
        <div style="border: 1px solid black;margin-left: 0.5in;margin-right: 0.5in;margin-top: 0.4in;background-color: lightgray;">
            <div style="border: 1px solid black;margin-left: 0.4in;margin-right: 0.4in;margin-top: 0.2in;margin-bottom: 0.2in;background-color: white;padding:15px;">
                <div>
                    <p style="font-size: 12px;"><b>CS Form No. 33-A</b></p>
                    <p style="font: bold 12px;">Revised 2018</p>
                </div>
                <div style="padding-left: 5px;padding-top: 25px;position: absolute;">
                </div>
                <div style="text-align: center;">
                    <h4 style="padding-top: 0.5in;margin: 0;">Republic of the Philippines</h4>
                    <p style="padding-top:-10px;text-align: center; font:  15px, arial;">{{ $orgCompanyName }}</p>
                    <p style="padding-top:-10px;text-align: center; font:  15px, arial;">{{ $orgCompanyAddress }}</p>
                </div>
                <br>
                <div style="padding-bottom: 150px;">
                    <div style="padding-top: 0.5in; padding-right: 15px;">
                        <p><b>Mr./Mrs./Ms.: {{ strtoupper($appointment->name) }}</b></p>
                        <br>
                        <br>
                        <p style="text-align: justify;text-indent: 0.5in;line-height: 25px;">
                            You are hereby appointed as <b><u>{{ $appointment->position }} </u></b> SG <b><u>{{ $appointment->salary_grade_id }} / {{ $appointment->salary_step_id }}</u></b>
                            under <b><u>{{ $appointment->employment_type }}</u></b> status at the <b><u>{{ $appointment->department }}</u></b>
                            with a compensation rate of <b><u>{{ strtoupper($salary_word) }}</u></b> P <b><u>{{ number_format($appointment->salary,2,'.',',') }}</u></b> pesos per month.
                        </p>
                        <br>
                        <p style="text-align: justify;text-indent: 0.5in;line-height: 25px;">
                            The nature of this appointment is <b><u>{{ $appointment->nature }}</u></b> vice <b><u>{{ $signatories['vice'] == '' ? '____________________' : $signatories['vice'] }}</u></b>
                            who <b><u>{{ $signatories['who'] == '' ? '____________________' : $signatories['who'] }}</u></b> with Plantilla Item No. <b><u>{{ $appointment->code }}</u></b> Page 1.
                        </p>
                        <br>
                        <p style="text-align: justify;text-indent: 0.5in;line-height: 25px;">
                            <b>{{ $signatories['note'] }}</b>
                        </p>
                    </div>
                    <br>
                    <div style="float: right;padding:15px;">
                        <p>Very truly yours,</p>
                        <br>
                        <br>
                        <p><b><u>{{ $signatories['signatory'] }}</u></b></p>
                        <p style="text-align: center;">{{ $signatories['position'] }}</p>
                        <p style="text-align: center;">______________________</p>
                        <p style="text-align: center;">Date of Signing</p>
                    </div>
                </div>
            </div>
            <div style="border: 1px solid black;margin-left: 0.4in;margin-right: 0.4in;margin-top: 0.2in;margin-bottom: 0.4in;background-color: white;padding:15px;">
                <div style="width: 200px;">
                    <p><b>CSC ACTION:</b></p><br><br>
                    <p>__________________________</p>
                    <P>
                        <center><b>Authorized Official</b></center>
                    </P><br>
                    <p>__________________________</p>
                    <p>
                        <center><b>Date</b></center>
                    </p>
                </div>
                <p style="float: right;font-size: 12px;"><i>(Stamp of Date of Release)</i></p><br>
            </div>
        </div>
    </div>
    @endforeach
    <!-- Page 2 -->
    <div class="card p-4">
        <div style="border: 1px solid black;margin-left: 0.5in;margin-right: 0.5in;margin-top: 0.4in;background-color: lightgray;">
            <div style="border: 1px solid black;margin-left: 0.4in;margin-right: 0.4in;margin-top: 0.2in;margin-bottom: 0.2in;background-color: white;padding:15px;">
                <div>
                    <p style="font-size: 25px;">
                        <center><b>Certification</b></center>
                    </p>
                    <br>
                </div>
                <div style="padding-bottom: 35px;">
                    <p style="text-align: justify; text-indent: 0.5in;line-height: 25px;">
                        This is to certify that all requirements and supporting papers pursuant to <b>CSC MC No. 24, s. 2017, as amended</b>, have been complied with, reviewed and found to be in order.
                    </p><br>
                    <p style="text-align: justify; text-indent: 0.5in;line-height: 25px;">
                        The position was published at {{ $signatories['publish_at'] == '' ? '___________________________' : $signatories['publish_at'] }} from {{ $signatories['publish_from'] == '' ? '___________' : $signatories['publish_from'] }} to {{ $signatories['publish_to'] == '' ? '___________' : $signatories['publish_to'] }}
                        and posted in {{ $signatories['posted_at'] == '' ? '_____________________________________' : $signatories['posted_at'] }} from {{ $signatories['posted_from'] == '' ? '___________' : $signatories['posted_from'] }} to {{ $signatories['posted_to'] == '' ? '___________' : $signatories['posted_to'] }} in consonance with RA No.7041.
                        The assessment by the Human Resource Merit Promotion and Selection Board (HRMPSB) started on <b><u>{{ $signatories['started_on'] == '' ? '______________' : date('M d, Y', strtotime($signatories['started_on'])) }}</u></b>.
                    </p>
                    <br>
                    <div style="float: right;">
                        <p><b><u>{{ $signatories['hrmo'] == '' ? '___________________________' : $signatories['hrmo'] }}</u></b></p>
                        <p>
                            <center><b>Highest Ranking HRMO</b></center>
                        </p>
                    </div>
                </div>
            </div>
            <div style="border: 1px solid black;margin-left: 0.4in;margin-right: 0.4in;margin-top: 0.2in;margin-bottom: 0.2in;background-color: white;padding:15px;">
                <div>
                    <p style="font-size: 25px;">
                        <center><b>Certification</b></center>
                    </p>
                    <br>
                </div>
                <div style="padding-bottom: 35px;">
                    <p style="text-align: justify; text-indent: 0.5in;line-height: 25px;">
                        This is to certify that the appointee has been screened and found qualified by the majority of the HRMPSB/Placement Committeeduring the deliberation held on <b><u>{{ $signatories['deliberation_on'] == '' ? '__________________' : date('M d, Y', strtotime($signatories['deliberation_on'])) }}</u></b>.
                    </p>
                    <div style="float: right;text-align: center;">
                        <p><b><u>{{ $signatories['hrmpsb'] == '' ? '___________________________________________' : $signatories['hrmpsb'] }}</u></b></p>
                        <p>
                            <center><b>Chairperson, HRMPSB/Placement Committee</b></center>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div style="border: 1px solid black;margin-left: 0.5in;margin-right: 0.5in;margin-top: 0.2in;margin-bottom: 0.2in;background-color: lightgray;">
            <div style="border: 0px;margin-left: 0.4in;margin-right: 0.4in;margin-top: 0.2in;margin-bottom: 0.2in;background-color: white;padding:0;">
                <div style="background-color: lightgray;padding: 10px;border-bottom: 1px solid black;">
                    <p style="text-align: center;margin: 0;font-size: 12px;font-weight: bold;">
                        CSC/HRMO Notation
                    </p>
                </div>
                <div style="padding: 15px;">
                    <table style="width: 100%;border-collapse: collapse;border: 1px solid black;">
                        <thead>
                            <tr>
                                <th colspan="3" style="border: 1px solid black;padding: 8px;text-align: center;font-weight: bold;font-size: 11px;background-color: #f0f0f0;vertical-align: middle;">ACTION ON APPOINTMENTS</th>
                                <th style="border: 1px solid black;padding: 8px;text-align: center;font-weight: bold;font-size: 11px;background-color: #f0f0f0;vertical-align: middle;">Recorded by</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Validated per RAI -->
                            <tr>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: top;">
                                                <div style="width: 12px;height: 12px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: top;">
                                                <span>Validated per RAI for the month of </span>
                                                <span style="border-bottom: 1px solid black;display: inline-block;min-width: 100px;">&nbsp;</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border-top: 1px solid black;border-bottom: 1px solid black;border-left: 0;border-right: 0;padding: 8px;font-size: 12px;">&nbsp;</td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;">&nbsp;</td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                            </tr>
                            <!-- Invalidated per CSCRO/FO -->
                            <tr>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: top;">
                                                <div style="width: 12px;height: 12px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: top;">
                                                <span>Invalidated per CSCRO/FO letter dated </span>
                                                <span style="border-bottom: 1px solid black;display: inline-block;min-width: 100px;">&nbsp;</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border-top: 1px solid black;border-bottom: 1px solid black;border-left: 0;border-right: 0;padding: 8px;font-size: 12px;">&nbsp;</td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;">&nbsp;</td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                            </tr>
                            <!-- Appeal Header -->
                            <tr>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: top;">
                                                <div style="width: 12px;height: 12px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: top;">
                                                <span>Appeal</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;text-align: center;font-weight: bold;font-size: 12px;background-color: #f0f0f0;vertical-align: middle;">DATE FILED</td>
                                <td style="border: 1px solid black;padding: 8px;text-align: center;font-weight: bold;font-size: 12px;background-color: #f0f0f0;vertical-align: middle;">STATUS</td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                            </tr>
                            <!-- CSCRO/CSC-Commission -->
                            <tr>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: top;padding-left: 18px;">
                                                <div style="width: 12px;height: 12px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: top;">
                                                <span>CSCRO/ CSC-Commission</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;text-align: center;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;text-align: center;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                            </tr>
                            <!-- Petition for Review Header -->
                            <tr>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: top;">
                                                <div style="width: 12px;height: 12px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: top;">
                                                <span>Petition for Review</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;">&nbsp;</td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;">&nbsp;</td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                            </tr>
                            <!-- CSC-Commission -->
                            <tr>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: top;padding-left: 18px;">
                                                <div style="width: 12px;height: 12px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: top;">
                                                <span>CSC-Commission</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;text-align: center;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;text-align: center;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                            </tr>
                            <!-- Court of Appeals -->
                            <tr>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: top;padding-left: 18px;">
                                                <div style="width: 12px;height: 12px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: top;">
                                                <span>Court of Appeals</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;text-align: center;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;text-align: center;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                            </tr>
                            <!-- Supreme Court -->
                            <tr>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: top;padding-left: 18px;">
                                                <div style="width: 12px;height: 12px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: top;">
                                                <span>Supreme Court</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;text-align: center;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;text-align: center;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                                <td style="border: 1px solid black;padding: 8px;font-size: 12px;vertical-align: middle;">
                                    <span style="border-bottom: 1px solid black;display: inline-block;width: 100%;">&nbsp;</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div style="border: 1px solid black;margin-left: 0.5in;margin-right: 0.5in;background-color: lightgray;">
            <div style="border: 1px solid black;margin-left: 0.4in;margin-right: 0.4in;margin-bottom: 0.2in;margin-top: 0.2in;background-color: white;padding:15px;">
                <div style="border-right: 1px solid black;margin: 0; padding: 10px;display:inline-block;position:relative;">
                    <p style="font-size: 12px;">Original Copy-for the Appointee</p>
                    <p style="font-size: 12px;">OriginalCopy-for the Civil Service Commission</p>
                    <p style="font-size: 12px;">Original Copy-for the Agency</p>
                </div>
                <div style="display:inline-block;position:relative;padding-top: 15px;padding-left: 10px;">
                    <p style=" font-size: 12px;text-align: center;">
                        <b>Acknowledgement</b>
                    </p><br>
                    <p style="font-size: 12px;text-align: center;padding-bottom: 5px;">
                        Received original/photocopy of appointment on _____________
                    </p>
                    <p style="font-size: 12px;text-align: center;">
                        ____________________________
                    </p>
                    <p style="font-size: 12px;text-align: center;">
                        Appointee
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
