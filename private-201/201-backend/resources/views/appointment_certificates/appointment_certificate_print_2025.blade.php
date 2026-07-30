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

    <title>Appointment Certificate</title>

    <style>
        html,
        body {
            margin: 20px;
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
        @php
            $appointeeName = strtoupper($appointment->name ?? '');
            $positionTitle = strtoupper(trim((string) ($appointment->position ?? ''))) ?: '____________________';
            $departmentName = trim((string) ($appointment->department ?? '')) ?: '____________________';
            $salaryGrade = trim((string) ($appointment->salary_grade_id ?? '')) !== ''
                ? ($appointment->salary_grade_id ?? '')
                : '____';
            $employmentType = trim((string) ($appointment->employment_type ?? '')) ?: '____________________';
            $salaryAmount = is_numeric($appointment->salary ?? null) ? (float) $appointment->salary : 0;
            $natureForm = isset($certificateNature) ? trim((string) $certificateNature) : '';
            $nature = $natureForm !== ''
                ? $natureForm
                : (trim((string) ($appointment->nature ?? '')) ?: 'Original');
            $plantillaCode = !empty($appointment->code) ? $appointment->code : '___________________';
        @endphp
        <div class="p-4 card">
            <div style="width: 100%; text-align: right; margin-bottom: 10px; position: relative; z-index: 10; bottom: 32px;">
                <div style="border: 1px solid black; padding: 5px 10px; display: inline-block; float: right;">
                    <p style="font-size: 12px; font-weight: bold; font-style: italic; margin: 0; text-align: center;">
                        For Accredited/Deregulated Agencies</p>
                </div>
            </div>
            <div style="border: 1px solid black; background-color: rgb(128, 128, 128); padding: 20px; position:absolute; top: 20px; left: 20px; right: 20px; bottom: 20px;">
                <div style=" border: 1px solid black; background-color: white;">
                    <div>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style=" padding: 15px;">
                                    <p style="font-size: 14px; font-weight: bold; font-style: italic; margin: 0;"><b>CS
                                            Form No. 33-B</b></p>
                                    <p style="font-size: 10px; font-style: italic; margin: 0;">Revised 2025</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 0;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="border: none; padding: 10px; width: 33.33%;"></td>
                                            <td style="border: none; padding: 10px; width: 33.33%;"></td>
                                            <td
                                                style="border: none; padding: 10px; width: 33.33%; text-align: center; vertical-align: middle;">
                                                <p style="font-size: 11px; margin: 0;"><i>(Stamp of Date of Receipt)</i>
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style=" padding: 15px; text-align: center; vertical-align: middle;">
                                    <p style="font-size: 16px; font-weight: bold;">Republic of the Philippines</p>
                                    <p style="font-size: 16px;">{{ $orgCompanyName }}</p>
                                    <p style="font-size: 16px;">{{ $orgCompanyAddress }}</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <br>
                    <div>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 15px;">
                                    <p style="font-size: 14px; font-weight:bold;">Mr./Mrs./Ms.: {{ $appointeeName }}</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-right: 15px; padding-left: 15px; font-size: 14px;">
                                    <div>
                                        <span style="margin-left: 25px; font-weight: bold;">
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top; font-weight: bold;">
                                                You are hereby appointed as
                                            </div>
                                            <div
                                                style="display: inline-block; font-weight: bold; font-size: 13px; text-align: center; vertical-align: top; margin-bottom: 5px;">
                                                <u>{{ $positionTitle }}</u> <br>
                                                <span
                                                    style="font-size: 12px; font-weight: normal; font-style: italic;">(Position
                                                    Title)</span>
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                (SG
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                /JG
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                <u><b>{{ $salaryGrade }}</b></u>
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                )
                                            </div>

                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                under
                                            </div>
                                            <div
                                                style="display: inline-block; font-weight: bold; font-size: 13px; text-align: center; vertical-align: top; margin-bottom: 5px;">
                                                {{ $employmentType }}<br>
                                                <span
                                                    style="font-size: 12px; font-weight: normal; font-style: italic; border-top: 1px solid black;">(Permanent,
                                                    Temporary, etc)</span>
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                status
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                at
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                the
                                            </div>
                                            <div
                                                style="display: inline-block; font-weight: bold; font-size: 13px; text-align: center; vertical-align: top; margin-bottom: 5px;">
                                                <u> {{ $departmentName }} </u> <br>
                                                <span
                                                    style="font-size: 12px; font-weight: normal; font-style: italic;">(Department)</span>
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                with
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                a
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                compensation
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                rate
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                of
                                            </div>
                                            <div
                                                style="display: inline-block; font-weight: normal; font-size: 12px; vertical-align: top; margin-bottom: 5px;">
                                                <u><b>{{ strtoupper($salary_word) }} </b></u> (Php <u><b>
                                                        {{ number_format($salaryAmount, 2, '.', ',') }} )</b></u>
                                            </div>
                                            <div style="display: inline-block; font-size: 14px; vertical-align: top;">
                                                pesos

                                                <div
                                                    style="display: inline-block; font-size: 14px; vertical-align: top;">
                                                    per
                                                </div>
                                                <div
                                                    style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                    month.
                                                </div>

                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="padding-right: 15px; padding-left: 15px;  padding-top:10px; font-size: 14px;">
                                    <br>
                                    <div>
                                        <span style="margin-left: 50px;">
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top; font-weight: bold;">
                                                The nature of this appointment is
                                            </div>
                                            <table
                                                style="display: inline-table; vertical-align: top; margin-bottom: 5px; border-collapse: collapse;">
                                                <tr>
                                                    <td
                                                        style="padding: 0; font-weight: bold; font-size: 14px; line-height: 1.25; text-align: center;">
                                                        <u>{{ $nature }}</u>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td
                                                        style="padding: 0; font-size: 12px; font-weight: normal; font-style: italic; line-height: 1.25; text-align: center; font-weight: bold;">
                                                        (Original, Promotion, etc)</td>
                                                </tr>
                                            </table>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top;">
                                                vice
                                            </div>
                                            <div
                                                style="display: inline-block; font-weight: bold; font-size: 14px; text-align: center; vertical-align: top; margin-bottom: 5px;">
                                                <u>{{ $signatories['vice'] == '' ? '____________________' : $signatories['vice'] }}</u>
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top; margin-left: 100px; font-weight: bold;">

                                                who
                                            </div>
                                            <table
                                                style="display: inline-table; vertical-align: top; margin-bottom: 5px; border-collapse: collapse;">
                                                <tr>
                                                    <td
                                                        style="padding: 0; font-weight: bold; font-size: 14px; line-height: 1.25; text-align: left; font-weight: bold;">
                                                        <u>{{ $signatories['who'] == '' ? '____________________' : $signatories['who'] }}</u>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td
                                                        style="padding: 0; font-size: 12px; font-weight: normal; font-style: italic; line-height: 1.25; text-align: left; font-weight: bold;">
                                                        (Transferred, Retired, etc.)</td>
                                                </tr>
                                            </table>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top; font-weight: bold;">
                                                with
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top; font-weight: bold;">
                                                Plantilla
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top; font-weight: bold;">
                                                Item
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top; font-weight: bold;">
                                                No.
                                            </div>
                                            <div
                                                style="display: inline-block; font-weight: bold; font-size: 14px; text-align: center; vertical-align: top; margin-bottom: 5px;">
                                                <u>{{ $plantillaCode }}</u>
                                            </div>
                                            <div
                                                style="display: inline-block; font-size: 14px; text-align: center; vertical-align: top; font-weight: bold;">
                                                Page <u><b>1.</b></u>
                                            </div>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 15px;">
                                    <p style="font-size: 14px; font-weight:bold;">
                                        This appointment shall take effect on the date of signing by the appointing
                                        officer/authority.
                                    </p>
                                </td>
                            </tr>
                            <tr>
                            <tr>
                                <td style="padding: 15px; font-size: 14px;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 33.33%;"></td>
                                            <td style="width: 33.33%;"></td>
                                            <td style="width: 33.33%; text-align: center; vertical-align: top;">
                                                <p style="text-align: left; margin: 0;">Very truly yours,</p> <br>
                                                <p style="margin: 0;"><b>{{ $signatories['signatory'] }}</b></p>
                                                <p style="margin: 0;">{{ $signatories['position'] }}</p>
                                                <p>____________________</p>
                                                <p>Appointing Officer/Authority</p>
                                                <p style="margin-top: 20px;">____________________</p>
                                                <p>Date of Signing</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 15px; text-align: left; vertical-align: middle;">
                                    <p style="margin: 0;">
                                        <b
                                            style="
                                                display: inline-block;
                                                font-size: 13px;
                                                width: 100%;
                                                white-space: normal;
                                                word-break: break-word;
                                                overflow-wrap: break-word;
                                                text-align: left;
                                            ">
                                            {{ $signatories['note'] }}
                                        </b>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 15px; font-size: 14px;">
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 15px; font-size: 13px; text-align: left; font-weight: bold;">
                                    <p style="margin: 0;">Accredited/Deregulated Pursuant to</p>
                                    <p style="margin: 0;">
                                        CSC Resolution No. <span
                                            style="text-decoration: underline;">{{ empty($signatories['cs_resolution_no']) ? '_________' : $signatories['cs_resolution_no'] }}</span>,
                                        s. <span
                                            style="text-decoration: underline;">{{ empty($signatories['cs_resolution_series']) ? '_______' : $signatories['cs_resolution_series'] }}</span>
                                    </p>
                                    <p style="margin: 0;">dated
                                        {{ $signatories['cs_date'] == '' ? '______________' : date('F j, Y', strtotime($signatories['cs_date'])) }}
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: middle; padding: 18px;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 33%; text-align: center; vertical-align: middle;">
                                                <div
                                                    style="position: relative; display: inline-block; width: 120px; height: 120px;">
                                                    <div
                                                        style="position: absolute; width: 120px; height: 120px; border: 2px solid #d3d3d3; border-radius: 50%; background-color: #fafafa;">
                                                    </div>
                                                    <span
                                                        style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-style: italic; color: #cccccc; font-size: 13px;">DRY
                                                        SEAL</span>
                                                </div>
                                            </td>
                                            <td style="width: 33%;"></td>
                                            <td style="width: 33%;"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; font-size: 14px;">
                                    <br>
                                </td>
                            </tr>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <!-- Page 2 -->
    <div class="p-4 card" style="page-break-before: always; width: 100%;">
        <div style="border: 1px solid black; background-color: rgb(128, 128, 128).; padding: 20px; ">
            <div style=" border: 1px solid black; background-color: white;">
                <div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 15px; text-align: center; vertical-align: middle;">
                                <p style="font-size: 16px; font-weight: bold; margin: 0;">Certification</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right: 15px; padding-left: 15px; font-size: 14px;">
                                <p style="text-align: justify; text-indent: 0.5in; line-height: 25px;">
                                    This is to certify that all requirements and supporting papers pursuant to <b>2025
                                        Omnibus Rules on Appointments and Other Human Resource Actions</b>, have been
                                    complied with, reviewed and found to be in order.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right: 15px; padding-left: 15px; font-size: 14px; border-t">
                                <p style="text-align: justify; text-indent: 0.5in; line-height: 25px;">
                                    The position was published at {!! $signatories['publish_at'] == ''
                                        ? '___________________________'
                                        : '<span style="font-size: 13px; font-weight: bold; text-decoration: underline;">' .
                                            $signatories['publish_at'] .
                                            '</span>' !!} from {!! $signatories['publish_from'] == ''
                                        ? '___________'
                                        : '<span style="font-size: 13px; font-weight: bold; text-decoration: underline;">' .
                                            $signatories['publish_from'] .
                                            '</span>' !!}
                                    to {!! $signatories['publish_to'] == ''
                                        ? '___________'
                                        : '<span style="font-size: 13px; font-weight: bold; text-decoration: underline;">' .
                                            $signatories['publish_to'] .
                                            '</span>' !!} and posted in three (3) conspicuous places {!! $signatories['posted_at'] == ''
                                        ? '_____________________________________'
                                        : '<span style="font-size: 13px; font-weight: bold; text-decoration: underline;">' .
                                            $signatories['posted_at'] .
                                            '</span>' !!} from
                                    {!! $signatories['posted_from'] == ''
                                        ? '___________'
                                        : '<span style="font-size: 13px; font-weight: bold; text-decoration: underline;">' .
                                            $signatories['posted_from'] .
                                            '</span>' !!} to {!! $signatories['posted_to'] == ''
                                        ? '___________'
                                        : '<span style="font-size: 13px; font-weight: bold; text-decoration: underline;">' .
                                            $signatories['posted_to'] .
                                            '</span>' !!} in consonance with RA No.7041.
                                    The assessment by the Human Resource Merit Promotion and Selection Board (HRMPSB)
                                    started on {!! $signatories['started_on'] == ''
                                        ? '______________'
                                        : '<span style="font-size: 13px; font-weight: bold; text-decoration: underline;">' .
                                            date('F j, Y', strtotime($signatories['started_on'])) .
                                            '</span>' !!}.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 15px; font-size: 14px;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="border: none; width: 33.33%;"></td>
                                        <td style="border: none; width: 33.33%;"></td>
                                        <td
                                            style="border: none; width: 33.33%; text-align: center; vertical-align: top;">
                                            <p style="text-align: center; margin: 0;">
                                                <b><u>{{ $signatories['hrmo'] == '' ? '___________________________' : $signatories['hrmo'] }}</u></b>
                                            </p>
                                            <p style="margin: 0;">HRMO</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <br>
            <div style="border: 1px solid black; background-color: white;">
                <div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 15px; text-align: center; vertical-align: middle;">
                                <p style="font-size: 16px; font-weight: bold; margin: 0;">Certification</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right: 15px; padding-left: 15px; font-size: 14px;">
                                <p style="text-align: justify; text-indent: 0.5in; line-height: 25px;">
                                    This is to certify that the appointee has been screened and found qualified by the
                                    majority of the HRMPSB/Placement Committee during the deliberation held on <span
                                        style="font-size: 13px; font-weight: bold; text-decoration: underline;">{{ $signatories['deliberation_on'] == '' ? '__________________' : date('F j, Y', strtotime($signatories['deliberation_on'])) }}</span>.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 15px; font-size: 14px;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="border: none; width: 50%;"></td>
                                        <td style="border: none; width: 50%; text-align: center; vertical-align: top;">
                                            <p style="text-align: center; margin: 0;">
                                                <b><u>{{ $signatories['hrmpsb'] == '' ? '___________________________________________' : $signatories['hrmpsb'] }}</u></b>
                                            </p>
                                            <p style="margin: 0;">Chairperson, HRMPSB/Placement Committee</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <br>
        <div style="border: 1px solid black; background-color: rgb(128, 128, 128).; padding: 8px 20px 20px 20px;">
            <p style="text-align: center;margin: 0;font-size: 16px;font-weight: bold; padding-bottom: 8px;">
                CSC/HRMO Notation
            </p>
            <div style=" border: 1px solid black; background-color: white; padding: 15px;">
                <div>
                    <table style="width: 100%;border-collapse: collapse;border: 1px solid black;">
                        <thead>
                            <tr>
                                <th colspan="3"
                                    style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; font-size: 14px; vertical-align: middle;">
                                    ACTION ON APPOINTMENTS
                                </th>
                                <th
                                    style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold; font-size: 14px; vertical-align: middle;">
                                    Recorded by
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Validated per RAI -->
                            <tr>
                                <td colspan="3" style="border: 1px solid black; padding: 6px; font-size: 13px;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: middle;">
                                                <div style="width: 10px; height: 10px; border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span style="font-weight: bold;">Validated per RAI for the month of
                                                </span>
                                                <span
                                                    style="border-bottom: 1px solid black; display: inline-block; min-width: 200px;"></span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black; padding: 7px;">
                                </td>
                            </tr>
                            <!-- Invalidated per CSCRO/FO -->
                            <tr>
                                <td colspan="3" style="border: 1px solid black; padding: 6px; font-size: 13px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: middle;">
                                                <div style="width: 10px; height: 10px; border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span style="font-weight: bold;">Invalidated per CSCRO/FO letter dated
                                                </span>
                                                <span
                                                    style="border-bottom: 1px solid black;display: inline-block;min-width: 200px;"></span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black; padding: 6px;">
                                </td>
                            </tr>
                            <!-- Appeal Header -->
                            <tr>
                                <td style="border: 1px solid black;padding: 6px;font-size: 13px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: middle;">
                                                <div style="width: 10px;height: 10px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span style="font-weight: bold;">Appeal</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td
                                    style="border: 1px solid black;padding: 6px;text-align: center;font-weight: bold;font-size: 13px; vertical-align: middle;">
                                    DATE FILED</td>
                                <td
                                    style="border: 1px solid black;padding: 6px;text-align: center;font-weight: bold;font-size: 13px; vertical-align: middle;">
                                    STATUS</td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                            </tr>
                            <!-- CSCRO/CSC-Commission -->
                            <tr>
                                <td style="border: 1px solid black;padding: 6px;font-size: 14px;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: middle;padding-left: 18px;">
                                                <div style="width: 10px;height: 10px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span>CSCRO/ CSC-Commission</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                            </tr>
                            <!-- Petition for Review Header -->
                            <tr>
                                <td style="border: 1px solid black;padding: 6px;font-size: 13px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: middle;">
                                                <div style="width: 10px;height: 10px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span style="font-weight: bold;">Petition for Review</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                            </tr>
                            <!-- CSC-Commission -->
                            <tr>
                                <td style="border: 1px solid black;padding: 6px;font-size: 14px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: middle;padding-left: 18px;">
                                                <div style="width: 10px;height: 10px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span>CSC-Commission</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                            </tr>
                            <!-- Court of Appeals -->
                            <tr>
                                <td style="border: 1px solid black;padding: 6px;font-size: 14px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: middle;padding-left: 18px;">
                                                <div style="width: 10px;height: 10px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span>Court of Appeals</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                            </tr>
                            <!-- Supreme Court -->
                            <tr>
                                <td style="border: 1px solid black;padding: 6px;font-size: 14px;">
                                    <table style="width: 100%;border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 18px;vertical-align: middle;padding-left: 18px;">
                                                <div style="width: 10px;height: 10px;border: 1px solid black;"></div>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span>Supreme Court</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                                <td style="border: 1px solid black;padding: 6px;">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><br>
        <div style="border: 1px solid black; background-color: rgb(128, 128, 128).; padding: 20px;">
            <div style=" border: 1px solid black; background-color: white;">
                <table style="background-color:white; width:100%; border-collapse:collapse;">
                    <tr>
                        <!-- Left column -->
                        <td style="vertical-align: middle; padding: 2px 15px; border-right: 1px solid black">
                            <p style="font-size:12px; margin:0;">Original Copy-for the Agency</p>
                            <p style="font-size:12px; margin:0;">Certified True Copy-for the Civil Service Commission
                            </p>
                            <p style="font-size:12px; margin:0;">Certified True Copy-for the Appointee</p>
                        </td>
                        <!-- Right column -->
                        <td style="vertical-align:middle; text-align:center; padding: 2px 15px;">
                            <p style="font-size:12px; margin-bottom: 5px;">
                                <b>Acknowledgement</b>
                            </p>
                            <p style="font-size:12px; margin:0;">
                                Received original/photocopy of appointment on _____________
                            </p>
                            <p style="font-size:12px; margin:0;">
                                ____________________________
                            </p>
                            <p style="font-size:12px; margin:0;">
                                Appointee
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
