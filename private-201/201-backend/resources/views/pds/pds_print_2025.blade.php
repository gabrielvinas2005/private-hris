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

    <title>Personal Data Sheet</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        html,
        body {
            font-family: 'Arial ' sans-serif;
            margin: 0;
            padding: 0;
        }

        p {
            padding: 5px;
            margin: 0;
        }

        table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 1px;
            font-size: 6px;
        }

        input[type="checkbox"] {
            vertical-align: middle;
        }

        label,
        input {
            display: inline-block;
            vertical-align: middle;
        }

        .main {
            padding: 2px;
        }

        .page-break {
            /* Avoid double page breaks (page-break-after + page-break-before) causing blank pages in dompdf */
            page-break-before: auto;
        }

        /* Ensure each PDS block prints as a page */
        .pds-page {
            page-break-after: always;
        }

        .pds-page-last {
            page-break-after: auto;
        }

        /* Keep table header rows with the next row */
        tr {
            page-break-inside: avoid;
        }

        tr.pds-allow-break {
            page-break-inside: auto !important;
        }

        tbody.pds-footer-group {
            page-break-inside: avoid;
        }

        tbody.pds-footer-group tr,
        tbody.pds-page-end tr {
            page-break-inside: auto !important;
        }

        tbody.pds-page-end {
            page-break-inside: avoid;
        }

        .pds-page-number {
            text-align: right;
            margin: 1px 0 0 0;
            padding: 0;
            font-size: 6px;
            line-height: 1;
            border: none;
        }

        .pds-signature-row {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .pds-signature-row td {
            border: 1px solid black;
            vertical-align: middle;
        }

        .pds-signature-row .sig-label {
            width: 15%;
            background-color: lightgray;
            color: black;
            padding: 6px 4px;
            text-align: center;
        }

        .pds-signature-row .sig-field {
            width: 42%;
            color: red;
            padding: 6px 4px;
            text-align: center;
        }

        .pds-signature-row .date-label {
            width: 10%;
            background-color: lightgray;
            color: black;
            padding: 6px 4px;
            text-align: center;
        }

        .pds-signature-row .date-field {
            width: 33%;
            padding: 6px 4px;
            min-height: 18px;
        }

        /* dompdf has limited support for :last-child; keep after-break and trim in controller if needed */

        /* Prevent AdminLTE card spacing from causing overflow */
        .card {
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        .p-4 {
            padding: 0 !important;
        }

        /* Empty box shown in “Tick appropriate boxes …” instruction line */
        .instruction-checkbox {
            display: inline-block;
            width: 7px;
            height: 7px;
            border: 1px solid #000;
            vertical-align: middle;
            margin: 0 2px;
            background: #fff;
        }

        table.citizenship-box,
        table.citizenship-box td {
            border: none !important;
        }

        .citizenship-box {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .citizenship-box td {
            padding: 2px 4px;
            font-size: 6px;
            vertical-align: top;
        }

        .citizenship-box label {
            margin: 0;
            white-space: nowrap;
        }

        td.civil-status-label {
            padding: 3px 5px !important;
            vertical-align: top !important;
            line-height: 1;
        }

        td.civil-status-cell {
            padding: 2px 4px !important;
            vertical-align: top !important;
            line-height: 1;
            font-size: 5.3px;
        }

        .civil-status-cell label {
            margin: 0 4px 0 0;
            padding: 0;
            line-height: 0.95;
            white-space: nowrap;
            display: inline-block;
            vertical-align: middle;
        }

        .civil-status-cell br {
            line-height: 0.3;
            margin: 0;
        }

        td.res-addr-compact {
            padding: 0 2px !important;
            line-height: 0.95;
        }

        /* Address field pairs: no vertical divider between left/right columns */
        td.addr-col-left {
            border-right: none !important;
        }

        td.addr-col-right {
            border-left: none !important;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach ($info as $info)
        <div class="p-4 card pds-page">
            <div class="main" style="width: 100%;">
                <div>
                    <table style="width: 100%; padding:10px; border: none;">
                        <tr>
                            <td colspan="9"
                                style="border-bottom: none; font-size: 10px; font-style: italic; font-weight: bold;">CS
                                Form No. 212</td>
                        </tr>
                        <tr>
                            <td colspan="9" style="border-bottom: none; border-top: none; font-style: italic;">
                                Revised 2025</td>
                        </tr>
                        <tr>
                            <td colspan="9"
                                style="border-bottom: none; border-top: none; padding: 2px; font-size: 20px; font-weight: bold; ">
                                <center>PERSONAL DATA SHEET</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" style="border-bottom: none; border-top: none; font-style: italic;">
                                <b>WARNING: Any misrepresentation made in the Personal Data Sheet and the Work
                                    Experience Sheet shall cause the filing of administrative/criminal case/s against
                                    the person concerned.</b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" style="border-bottom: none;border-top: none;">
                                <b style="font-style: italic;">READ THE ATTACHED GUIDE TO FILLING OUT THE PERSONAL DATA
                                    SHEET (PDS) BEFORE
                                    ACCOMPLISHING THE PDS FORM.</b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" style="border-bottom: none;border-top: none;"><u>Print legibly if accomplished through own handwriting. Tick
                                appropriate boxes (<span class="instruction-checkbox"></span>) and use separate sheet if
                                necessary. Indicate N/A if not applicable.</u> <b>DO NOT ABBREVIATE.</b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9"
                                style="background-color: gray;color: white; font-size: 7px; font-weight: bold; font-style: italic;">
                                I. PERSONAL INFORMATION
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray; color: black; border-bottom: none; padding: 5px;">
                                1. SURNAME
                            </td>
                            <td colspan="8" style="padding-left: 5px;">
                                {{ $info->last_name ?: 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray; color: black; border-bottom: none; border-top: none ; padding: 5px;">
                                2. FIRST NAME
                            </td>
                            <td colspan="7" style="padding-left: 5px;">
                                {{ $info->first_name ?: 'N/A' }}
                            </td>
                            <td style="background-color: lightgray;color: black; padding-bottom: 10px;">
                                NAME EXTENSION (JR., SR) {{ $info->suffix }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray; color: black; border-bottom: none; border-top: none; padding: 5px 5px 5px 11px;">
                                MIDDLE NAME
                            </td>
                            <td colspan="8" style="padding-left: 5px;">
                                {{ $info->middle_name ?: 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray; color: black; border-bottom: none; padding: 5px;">
                                3. DATE OF BIRTH
                            </td>
                            <td colspan="2" style="border-bottom: none; border-top: none; padding: 5px;">
                                {{ date('m/d/Y', strtotime($info->birthdate)) ?: 'N/A' }}
                            </td>
                            <td colspan="3" rowspan="3"
                                style="background-color: lightgray; color: black; border-bottom: none; padding: 5px; vertical-align: top;">
                                <div>16. CITIZENSHIP</div>
                                <div style="margin-top: 28px; text-align: center; font-size: 6px;">If holder of dual citizenship,</div>
                            </td>
                            <td colspan="3" rowspan="3" style="padding: 4px 6px; border-bottom: none; vertical-align: top;">
                                <table class="citizenship-box">
                                    <tr>
                                        <td style="width: 42%;">
                                            <label>
                                                <input type="checkbox" style="transform: scale(0.8);"
                                                    {{ $info->is_dual_citizent == false ? 'checked' : '' }}>Filipino
                                            </label>
                                        </td>
                                        <td>
                                            <label>
                                                <input type="checkbox" style="transform: scale(0.8);"
                                                    {{ $info->is_dual_citizent == true ? 'checked' : '' }}>Dual Citizenship
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td>
                                            <label style="margin-right: 10px;">
                                                <input type="checkbox" style="transform: scale(0.8);"
                                                    {{ $info->by_birth == true ? 'checked' : '' }}>by Birth
                                            </label>
                                            <label>
                                                <input type="checkbox" style="transform: scale(0.8);"
                                                    {{ $info->by_naturalization == true ? 'checked' : '' }}>by Naturalization
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td>
                                            Pls. indicate country: {{ $info->indicate_country ?: 'N/A' }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray; color: black; border-bottom: none; border-top: none; padding-left: 15px; vertical-align: top;">
                                (mm/dd/yyyy)
                            </td>
                            <td colspan="2" style="border-bottom: none; border-top: none; padding: 5px;"></td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray; color: black; border-bottom: none; padding: 5px;">
                                4. PLACE OF BIRTH
                            </td>
                            <td colspan="2" style="border-bottom: none; padding: 5px;">
                                {{ $info->birth_place ?: 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray; color: black; border-bottom: none; padding: 5px;">
                                5. SEX
                            </td>
                            <td colspan="2" style="border-bottom: none; vertical-align: middle;">
                                <label style="  margin-bottom: -8px; margin-right: 19.5px;">
                                    <input type="checkbox" style="transform: scale(0.8); "
                                        {{ $info->gender == 'Male' ? 'checked' : '' }}>Male
                                </label>
                                <label style=" margin-bottom: -8px;">
                                    <input type="checkbox" style="transform: scale(0.8);"
                                        {{ $info->gender == 'Female' ? 'checked' : '' }}>Female
                                </label>
                                </label>
                            </td>
                            <td colspan="3"
                                style="background-color: lightgray; color: black; border-bottom: none; border-top: none; padding: 5px;">
                                <center>Please indicate the details.</center>
                            </td>
                            <td colspan="3">
                                <center>{{ $info->indicate_country ?: 'N/A' }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td rowspan="3" class="civil-status-label" style="background-color: lightgray;color: black; border-bottom: none;">
                                6. CIVIL STATUS
                            </td>
                            <td colspan="2" rowspan="3" class="civil-status-cell" style="border-bottom: none;">
                                <label>
                                    <input type="checkbox" style="transform: scale(0.6); vertical-align: middle;"
                                        {{ $info->civil_status == 'Single' ? 'checked' : '' }}>Single
                                </label>
                                <label>
                                    <input type="checkbox" style="transform: scale(0.6); vertical-align: middle;"
                                        {{ $info->civil_status == 'Married' ? 'checked' : '' }}>Married
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" style="transform: scale(0.6); vertical-align: middle;"
                                        {{ $info->civil_status == 'Widowed' ? 'checked' : '' }}>Widowed
                                </label>
                                <label>
                                    <input type="checkbox" style="transform: scale(0.6); vertical-align: middle;"
                                        {{ $info->civil_status == 'Separated' ? 'checked' : '' }}>Separated
                                </label>
                                <label>
                                    <input type="checkbox" style="transform: scale(0.6); vertical-align: middle;"
                                        {{ $info->civil_status == 'Other' ? 'checked' : '' }}>Other
                                </label>
                            </td>
                            <td colspan="2" class="res-addr-compact"
                                style="background-color: lightgray;color: black; border-bottom: none;">
                                17. RESIDENTIAL ADDRESS
                            </td>
                            <td colspan="2" class="addr-col-left res-addr-compact" style="border-bottom-color: lightgrey;">
                                <center>{{ $info->ra_house_no ?: 'N/A' }}</center>
                            </td>
                            <td colspan="2" class="addr-col-right res-addr-compact" style="border-bottom-color: lightgrey;">
                                <center>{{ $info->ra_street ?? 'N/A' }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="res-addr-compact"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td colspan="2" class="addr-col-left res-addr-compact" style="font-style: italic; border-top-color: lightgrey;">
                                <center>House/Block/Lot No.</center>
                            </td>
                            <td colspan="2" class="addr-col-right res-addr-compact" style="font-style: italic; border-top-color: lightgrey;">
                                <center>Street</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="res-addr-compact"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td colspan="2" class="addr-col-left res-addr-compact" style="border-bottom: none;">
                                <center>{{ $info->ra_village ?? 'N/A' }}</center>
                            </td>
                            <td colspan="2" class="addr-col-right res-addr-compact" style="border-bottom: none;">
                                <center>{{ $address['ra_brgy'] ?? '' ?: 'N/A' }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none; padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td colspan="2" class="addr-col-left" style="font-style: italic; border-top-color: lightgrey;">
                                <center>Subdivision/Village</center>
                            </td>
                            <td colspan="2" class="addr-col-right" style="font-style: italic; border-top-color: lightgrey;">
                                <center>Barangay</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black; border-bottom: none; padding: 5px;">
                                7. HEIGHT (m)
                            </td>
                            <td colspan="2" style="border-bottom: none; padding: 5px;">
                                {{ $info->height ?? 'N/A' }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                                <center></center>
                            </td>
                            <td colspan="2" class="addr-col-left" style="border-bottom: none;">
                                <center>{{ $address['ra_city'] ?? '' ?: 'N/A' }}</center>
                            </td>
                            <td colspan="2" class="addr-col-right" style="border-bottom: none;border-top: none;">
                                <center>{{ $address['ra_province'] ?? '' ?: 'N/A' }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none; padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none; padding: 5px;">
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;">
                            </td>
                            <td colspan="2" class="addr-col-left" style="font-style: italic; border-top-color: lightgrey;">
                                <center>City/Municipality</center>
                            </td>
                            <td colspan="2" class="addr-col-right" style="font-style: italic; border-top-color: lightgrey;">
                                <center>Province</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black; border-bottom: none; padding: 5px;">
                                8. WEIGHT (kg)
                            </td>
                            <td colspan="2" style="border-bottom: none; padding: 5px;">
                                {{ $info->weight ?? 'N/A' }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                                <center>ZIP CODE</center>
                            </td>
                            <td colspan="4">
                                <center>{{ $address['ra_zip_code'] ?? '' ?: 'N/A' }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black; border-bottom: none; padding: 5px;">
                                9. BLOOD TYPE
                            </td>
                            <td colspan="2" style="border-bottom: none; padding: 5px;">
                                {{ $info->blood_type ?? 'N/A' }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; padding: 5px;">
                                18. PERMANENT ADDRESS
                            </td>
                            <td colspan="2" class="addr-col-left" style="border-bottom-color: lightgrey;">
                                <center>{{ $info->pa_house_no ?? 'N/A' }}</center>
                            </td>
                            <td colspan="2" class="addr-col-right" style="border-bottom-color: lightgrey;">
                                <center>{{ $info->pa_street ?? 'N/A' }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none; border-top: none; padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td colspan="2" class="addr-col-left" style="font-style: italic; border-top-color: lightgrey;">
                                <center>House/Block/Lot No.</center>
                            </td>
                            <td colspan="2" class="addr-col-right" style="font-style: italic; border-top-color: lightgrey;">
                                <center>Street</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black; border-bottom: none; padding: 5px;">
                                10. GSIS ID NO.
                            </td>
                            <td colspan="2" style="border-bottom: none; padding: 5px;">
                                {{ $info->gsis_no ?? '' ?: 'N/A' }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td colspan="2" class="addr-col-left" style="border-bottom: none;">
                                <center>{{ $info->pa_village ?? '' ?: 'N/A' }}</center>
                            </td>
                            <td colspan="2" class="addr-col-right" style="border-bottom: none;">
                                <center>{{ $address['pa_brgy'] ?? '' ?: 'N/A' }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td colspan="2" class="addr-col-left" style="font-style: italic; border-top-color: lightgrey;">
                                <center>Subdivision/Village</center>
                            </td>
                            <td colspan="2" class="addr-col-right" style="font-style: italic; border-top-color: lightgrey;">
                                <center>Barangay</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black; border-bottom: none; padding: 5px;">
                                11. PAG-IBIG ID NO.
                            </td>
                            <td colspan="2" style="border-bottom: none; padding: 5px;">
                                {{ $info->pagibig_no ?? '' ?: 'N/A' }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td colspan="2" class="addr-col-left" style="border-bottom: none;">
                                <center>{{ $address['pa_city'] ?? '' ?: 'N/A' }}</center>
                            </td>
                            <td colspan="2" class="addr-col-right" style="border-bottom: none;">
                                <center>{{ $address['pa_province'] ?? '' ?: 'N/A' }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td colspan="2" class="addr-col-left" style="font-style: italic; border-top-color: lightgrey;">
                                <center>City/Municipality</center>
                            </td>
                            <td colspan="2" class="addr-col-right" style="font-style: italic; border-top-color: lightgrey;">
                                <center>Province</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">
                                12. PHILHEALTH NO.
                            </td>
                            <td colspan="2" style="border-bottom: none; padding: 5px;">
                                {{ $info->philhealth_no ?? '' ?: 'N/A' }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                                <center>ZIP CODE</center>
                            </td>
                            <td colspan="4" style="border-bottom: none;">
                                <center>{{ $address['pa_zip_code'] ?? '' ?: 'N/A' }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">
                                13. PhilSys Number (PSN)
                            </td>
                            <td colspan="2" style="border-bottom: none; padding: 5px;">
                                {{ $info->sss_no ?? 'N/A' }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; padding: 5px;">
                                19. TELEPHONE NO.
                            </td>
                            <td colspan="4" style="border-bottom: none;">
                                <center>{{ $info->telephone_no ?? 'N/A' }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black; border-bottom: none; padding: 5px;">
                                14. TIN NO.
                            </td>
                            <td colspan="2" style="border-bottom: none; padding: 5px;">
                                {{ $info->tin_no ?? 'N/A' }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; padding: 5px;">
                                20. MOBILE NO.
                            </td>
                            <td colspan="4" style="border-bottom: none;">
                                <center>{{ $info->mobile_no ?? 'N/A' }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black; border-bottom: none; padding: 5px; font-size: 5.8px;">
                                15. AGENCY EMPLOYEE NO.
                            </td>
                            <td colspan="2" style="border-bottom: none; padding: 5px;">
                                {{ $info->employee_no ?? 'N/A' }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray; color: black; border-bottom: none; padding: 5px;">
                                21. E-MAIL ADDRESS (if any)
                            </td>
                            <td colspan="4" style="border-bottom: none;">
                                <center>{{ $info->email ?? 'N/A' }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9"
                                style="background-color: gray;color: white; font-size: 7px; font-weight: bold; font-style: italic;">
                                II. FAMILY BACKGROUND
                            </td>
                        </tr>
                        <!-- Spouse Name -->
                        <tr>
                            <td style="background-color: lightgray; color: black; border-bottom: none; padding: 5px;">
                                22. SPOUSE'S SURNAME
                            </td>
                            <td colspan="4" style="padding: 5px;">
                                {{ $info->spouse_last_name ?: 'N/A' }}
                            </td>
                            <td colspan="3" style="background-color: lightgray;color: black; padding: 5px;">
                                23. NAME of CHILDREN (Write full name and list all)
                            </td>
                            <td style="background-color: lightgray;color: black; padding: 5px;">
                                DATE OF BIRTH (mm/dd/yyyy)
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black; border-bottom: none;border-top: none; padding: 5px 5px 5px 15px;">
                                FIRST NAME
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ $info->spouse_first_name ?: 'N/A' }}
                            </td>
                            <td style="background-color: lightgray;color: black; padding-bottom: 10px;">
                                NAME EXTENSION (JR., SR) {{ $info->spouse_suffix ?: 'N/A' }}
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ isset($children[0]->row) && $children[0]->row == 1 ? $children[0]->child_name : 'N/A' }}
                            </td>
                            <td style="padding: 5px;">
                                {{ isset($children[0]->row) && $children[0]->row == 1 ? date('m/d/Y', strtotime($children[0]->child_birthdate)) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px 5px 5px 15px;">
                                MIDDLE NAME
                            </td>
                            <td colspan="4" style="padding: 5px;">{{ $info->spouse_middle_name ?: 'N/A' }}</td>
                            <td colspan="3" style="padding: 5px;">
                                {{ isset($children[1]->row) && $children[1]->row == 2 ? $children[1]->child_name : 'N/A' }}
                            </td>
                            <td style="padding: 5px;">
                                {{ isset($children[1]->row) && $children[1]->row == 2 ? date('m/d/Y', strtotime($children[1]->child_birthdate)) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none; padding: 5px 5px 5px 15px;">
                                OCCUPATION</td>
                            <td colspan="4" style="padding: 5px;">
                                {{ $info->spouse_occupation ?? 'N/A' }}
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ isset($children[2]->row) && $children[2]->row == 3 ? $children[2]->child_name : 'N/A' }}
                            </td>
                            <td style="padding: 5px;">
                                {{ isset($children[2]->row) && $children[2]->row == 3 ? date('m/d/Y', strtotime($children[2]->child_birthdate)) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px 5px 5px 15px;">
                                EMPLOYER/BUSINESS NAME
                            </td>
                            <td colspan="4" style="padding: 5px;">
                                {{ $info->spouse_employer ?? 'N/A' }}
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ isset($children[3]->row) && $children[3]->row == 4 ? $children[3]->child_name : 'N/A' }}
                            </td>
                            <td style="padding: 5px;">
                                {{ isset($children[3]->row) && $children[3]->row == 4 ? date('m/d/Y', strtotime($children[3]->child_birthdate)) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px 5px 5px 15px;">
                                BUSINESS ADDRESS
                            </td>
                            <td colspan="4" style="padding: 5px;">
                                {{ $info->spouse_business_address ?? 'N/A' }}
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ isset($children[4]->row) && $children[4]->row == 5 ? $children[4]->child_name : 'N/A' }}
                            </td>
                            <td style="padding: 5px;">
                                {{ isset($children[4]->row) && $children[4]->row == 5 ? date('m/d/Y', strtotime($children[4]->child_birthdate)) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px 5px 5px 15px;">
                                TELEPHONE NO.
                            </td>
                            <td colspan="4" style="padding: 5px;">
                                {{ $info->spouse_business_telephone_no ?? 'N/A' }}
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ isset($children[5]->row) && $children[5]->row == 6 ? $children[5]->child_name : 'N/A' }}
                            </td>
                            <td style="padding: 5px;">
                                {{ isset($children[5]->row) && $children[5]->row == 6 ? date('m/d/Y', strtotime($children[5]->child_birthdate)) : 'N/A' }}
                            </td>
                        </tr>
                        <!-- Fathers Name -->
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">
                                24. FATHER'S SURNAME
                            </td>
                            <td colspan="4" style="padding: 5px;">
                                {{ $info->father_last_name ?: 'N/A' }}
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ isset($children[6]->row) && $children[6]->row == 7 ? $children[6]->child_name : 'N/A' }}
                            </td>
                            <td style="padding: 5px;">
                                {{ isset($children[6]->row) && $children[6]->row == 7 ? date('m/d/Y', strtotime($children[6]->child_birthdate)) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none; padding: 5px 5px 5px 15px;">
                                FIRST NAME
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ $info->father_first_name ?: 'N/A' }}
                            </td>
                            <td style="background-color: lightgray;color: black;padding-bottom: 10px;">
                                NAME EXTENSION(JR., SR) {{ $info->father_suffix ?: 'N/A' }}
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ isset($children[7]->row) && $children[7]->row == 8 ? $children[7]->child_name : 'N/A' }}
                            </td>
                            <td style="padding: 5px;">
                                {{ isset($children[7]->row) && $children[7]->row == 8 ? date('m/d/Y', strtotime($children[7]->child_birthdate)) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none; border-top: none; padding: 5px 5px 5px 15px;">
                                MIDDLE NAME</td>
                            <td colspan="4" style="padding: 5px;">
                                {{ $info->father_middle_name ?: 'N/A' }}
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ isset($children[8]->row) && $children[8]->row == 9 ? $children[8]->child_name : 'N/A' }}
                            </td>
                            <td style="padding: 5px;">
                                {{ isset($children[8]->row) && $children[8]->row == 9 ? date('m/d/Y', strtotime($children[8]->child_birthdate)) : 'N/A' }}
                            </td>
                        </tr>
                        <!-- Mother's Name -->
                        <tr>
                            <td colspan="5"
                                style="background-color: lightgray; color: black; border-bottom: none; padding: 5px;">
                                25. MOTHER'S MAIDEN NAME
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ isset($children[9]->row) && $children[9]->row == 10 ? $children[9]->child_name : 'N/A' }}
                            </td>
                            <td style="padding: 5px;">
                                {{ isset($children[9]->row) && $children[9]->row == 10 ? date('m/d/Y', strtotime($children[9]->child_birthdate)) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray; color: black; border-bottom: none; border-top: none; padding: 5px 5px 5px 15px;">
                                SURNAME
                            </td>
                            <td colspan="4" style="padding: 5px;">
                                {{ $info->mother_last_name ?: 'N/A' }}
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ isset($children[10]->row) && $children[10]->row == 11 ? $children[10]->child_name : 'N/A' }}
                            </td>
                            <td style="padding: 5px;">
                                {{ isset($children[10]->row) && $children[10]->row == 11 ? date('m/d/Y', strtotime($children[10]->child_birthdate)) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray; color: black; border-bottom: none; border-top: none; padding: 5px 5px 5px 15px;">
                                FIRST NAME
                            </td>
                            <td colspan="4" style="padding: 5px;">
                                {{ $info->mother_first_name ?: 'N/A' }}
                            </td>
                            <td colspan="3" style="padding: 5px;">
                                {{ isset($children[11]->row) && $children[11]->row == 12 ? $children[11]->child_name : 'N/A' }}
                            </td>
                            <td style="padding: 5px;">
                                {{ isset($children[11]->row) && $children[11]->row == 12 ? date('m/d/Y', strtotime($children[11]->child_birthdate)) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray; color: black; border-bottom: none; border-top: none; padding: 5px 5px 5px 15px;">
                                MIDDLE NAME
                            </td>
                            <td colspan="4" style="padding: 5px;">
                                {{ $info->mother_middle_name ?: 'N/A' }}
                            </td>
                            <td colspan="4" style="background-color: lightgray;color: red;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <!-- Education -->
                        <tr>
                            <td colspan="9"
                                style="background-color: gray;color: white; font-size: 7px; font-weight: bold; font-style: italic;">
                                III. EDUCATIONAL BACKGROUND
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>26. LEVEL</center>
                            </td>
                            <td
                                style="background-color: lightgray;color: black; white-space: nowrap; border-bottom: none;">
                                <center>NAME OF SCHOOL <br> (Write in full)</center>
                            </td>
                            <td
                                style="background-color: lightgray; color: black; white-space: nowrap; border-bottom: none;">
                                <center>BASIC EDUCATION/DEGREE/COURSE<br>(Write in full)</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>PERIOD OF ATTENDANCE</center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>HIGHEST LEVEL/ <br> UNITS EARNED <br> (if not graduated)</center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>YEAR <br> GRADUATED </center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>SCHOLARSHIP/ <br> ACADEMIC <br> HONORS <br> RECEIVED</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>From</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>To</center>
                            </td>
                            <td
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                            <td
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; padding: 5px 5px 5px 15px;">
                                ELEMENTARY
                            </td>
                            @if (!$educations_elem->isEmpty())
                                @foreach ($educations_elem as $education)
                                    <td>
                                        <center>{{ $education->school_name ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->program ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->from ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->to ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->units_earned ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->graduated_year ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->honors ?? '' }}</center>
                                    </td>
                                @endforeach
                            @else
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; padding: 5px 5px 5px 15px;">
                                SECONDARY
                            </td>
                            @if (!$educations_sec->isEmpty())
                                @foreach ($educations_sec as $education)
                                    <td>
                                        <center>{{ $education->school_name ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->program ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->from ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->to ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->units_earned ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->graduated_year ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->honors ?? '' }}</center>
                                    </td>
                                @endforeach
                            @else
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; padding: 5px 5px 5px 15px;">
                                VOCATIONAL / TRADE COURSE
                            </td>
                            @if (!$educations_voc->isEmpty())
                                @foreach ($educations_voc as $education)
                                    <td>
                                        <center>{{ $education->school_name ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->program ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->from ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->to ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->units_earned ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->graduated_year ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->honors ?? '' }}</center>
                                    </td>
                                @endforeach
                            @else
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; padding: 5px 5px 5px 15px;">
                                COLLEGE
                            </td>
                            @if (!$educations_col->isEmpty())
                                @foreach ($educations_col as $education)
                                    <td>
                                        <center>{{ $education->school_name ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->program ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->from ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->to ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->units_earned ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->graduated_year ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->honors ?? '' }}</center>
                                    </td>
                                @endforeach
                            @else
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; padding: 5px 5px 5px 15px;">
                                GRADUATE STUDIES
                            </td>
                            @if (!$educations_grad->isEmpty())
                                @foreach ($educations_grad as $education)
                                    <td>
                                        <center>{{ $education->school_name ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->program ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->from ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->to ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->units_earned ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->graduated_year ?? '' }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->honors ?? '' }}</center>
                                    </td>
                                @endforeach
                            @else
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                                <td>
                                    <center> N/A </center>
                                </td>
                            @endif
                        </tr>
                        <tbody class="pds-page-end">
                        <tr>
                            <td colspan="9" style="background-color: lightgray;color: red;padding: 0px;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" style="padding: 0; border: none;">
                                <table class="pds-signature-row">
                                    <tr>
                                        <td class="sig-label"><b>SIGNATURE</b></td>
                                        <td class="sig-field">(wet signature/e-signature/digital certificate)</td>
                                        <td class="date-label"><b>Date</b></td>
                                        <td class="date-field">&nbsp;</td>
                                    </tr>
                                </table>
                                <p class="pds-page-number"><i>CS FORM 212 (Revised 2025), Page 1 of 4</i></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Page 2 -->
        <div class="p-4 card page-break pds-page">
            <div class="main" style="width: 100%;">
                <div>
                    <table style="width: 100%; padding:10px; border: none;">
                        <!-- Eligibility -->
                        <tr>
                            <td colspan="8"
                                style="background-color: gray;color: white; font-size: 7px; font-weight: bold; font-style: italic;">
                                IV. CIVIL SERVICE ELIGIBILITY
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="background-color: lightgray;color: black; white-space: nowrap; padding: 5px 10px; border-bottom: none;">
                                <center>
                                    27. CES/CSEE/CAREER SERVICE/RA 1080 (BOARD/ <br> BAR)/UNDER SPECIAL LAWS/CATEGORY
                                    II/ IV
                                    <br>ELIGIBILITY and ELIGIBILITIES FOR UNIFORMED <br> PERSONNEL
                                </center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>RATING <br> (If Applicable)</center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>DATE OF <br> EXAMINATION /<br> CONFERMENT</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>PLACE OF EXAMINATION / CONFERMENT</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>LICENSE (if applicable)</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black; border-top: none;">
                            </td>
                            <td style="background-color: lightgray;color: black; border-top: none;"></td>
                            <td style="background-color: lightgray;color: black; border-top: none;"></td>
                            <td colspan="2" style="background-color: lightgray;color: black; border-top: none;">
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>NUMBER</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>VALID UNTIL</center>
                            </td>
                        </tr>
                        @foreach ($examinations as $examination)
                            <tr>
                                <td colspan="2" style="padding: 10px;">
                                    <center>{{ $examination->eligibility }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $examination->exam_rating }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($examination->exam_date)) }}</center>
                                </td>
                                <td colspan="2" style="padding: 10px;">
                                    <center>{{ $examination->place_of_exam }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $examination->license_number }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($examination->date_released)) }}</center>
                                </td>
                            </tr>
                        @endforeach
                        @if (count($examinations) < 7)
                            @for ($i = count($examinations); $i < 7; $i++)
                                <tr>
                                    <td colspan="2" style="padding: 8px;">
                                        <center>N/A</center>
                                    </td>
                                    <td>
                                        <center>N/A</center>
                                    </td>
                                    <td>
                                        <center>N/A</center>
                                    </td>
                                    <td colspan="2">
                                        <center>N/A</center>
                                    </td>
                                    <td>
                                        <center>N/A</center>
                                    </td>
                                    <td>
                                        <center>N/A</center>
                                    </td>
                                </tr>
                            @endfor
                        @endif
                        <tr>
                            <td colspan="8" style="background-color: lightgray;color: red;padding: 0px;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <!-- Work Experiences -->
                        <tr>
                            <td colspan="8"
                                style="background-color: gray;color: white; font-size: 7px; font-weight: bold; font-style: italic;">
                                V. WORK EXPERIENCE
                                <br> (Include private employment. Start from your recent work) Description of duties
                                should be indicated in the attached Work Experience sheet.
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black; padding: 8px 5px">
                                <center>28. INCLUSIVE DATES <br> (mm/dd/yyyy)</center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>POSITION TITLE <br> (Write in full/Do not abbreviate)</center>
                            </td>
                            <td colspan="3" style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>DEPARTMENT / AGENCY / OFFICE / COMPANY <br> (Write in full/Do not abbreviate)
                                </center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>STATUS OF <br> APPOINTMENT</center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>GOV'T SERVICE <br> (Y/ N)</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;">
                                <center>From</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>To</center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-top: none;"></td>
                            <td colspan="3" style="background-color: lightgray;color: black; border-top: none;">
                            </td>
                            <td style="background-color: lightgray;color: black; border-top: none;"></td>
                            <td style="background-color: lightgray;color: black; border-top: none;"></td>
                        </tr>
                        @foreach ($employments as $employment)
                            <tr>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($employment->work_start_date)) }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($employment->work_end_date)) }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $employment->position }}</center>
                                </td>
                                <td colspan="3" style="padding: 10px;">
                                    <center>{{ $employment->work_company }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $employment->status_of_appointment }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $employment->government_service_id == 0 ? 'No' : 'Yes' }}</center>
                                </td>
                            </tr>
                        @endforeach
                        @if (count($employments) < 28)
                            @for ($i = count($employments); $i < 28; $i++)
                                <tr>
                                    <td style="padding: 8px;">
                                        <center>N/A</center>
                                    </td>
                                    <td>
                                        <center>N/A</center>
                                    </td>
                                    <td>
                                        <center>N/A</center>
                                    </td>
                                    <td colspan="3">
                                        <center>N/A</center>
                                    </td>
                                    <td>
                                        <center>N/A</center>
                                    </td>
                                    <td>
                                        <center>N/A</center>
                                    </td>
                                </tr>
                            @endfor
                        @endif
                        <tbody class="pds-page-end">
                        <tr>
                            <td colspan="8" style="background-color: lightgray;color: red;padding: 0px;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8" style="padding: 0; border: none;">
                                <table class="pds-signature-row">
                                    <tr>
                                        <td class="sig-label"><b>SIGNATURE</b></td>
                                        <td class="sig-field">(wet signature/e-signature/digital certificate)</td>
                                        <td class="date-label"><b>Date</b></td>
                                        <td class="date-field">&nbsp;</td>
                                    </tr>
                                </table>
                                <p class="pds-page-number"><i>CS FORM 212 (Revised 2025), Page 2 of 4</i></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Page 3 -->
        <div class="p-4 card page-break pds-page">
            <div class="main" style="width: 100%;">
                <div>
                    <table style="width: 100%;padding:10px; border: none;">
                        <!-- Organization -->
                        <tr>
                            <td colspan="9"
                                style="background-color: gray;color: white; font-size: 7px; font-weight: bold; font-style: italic;">
                                VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY
                                ORGANIZATION/S</td>
                        </tr>
                        <tr>
                            <td colspan="4" style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>29. NAME & ADDRESS OF ORGANIZATION <br> (Write in full)</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black; padding: 8px 5px;">
                                <center>INCLUSIVE DATES <br> (mm/dd/yyyy)</center>
                            </td>
                            <td style="background-color: lightgray;color: black;  border-bottom: none;">
                                <center>NUMBER OF <br> HOURS</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>POSITION / NATURE OF WORK</center>
                            </td>
                        </tr>
                        <td colspan="4" style="background-color: lightgray;color: black; border-top: none;">
                        </td>
                        <td style="background-color: lightgray;color: black;">
                            <center>From</center>
                        </td>
                        <td style="background-color: lightgray;color: black;">
                            <center>To</center>
                        </td>
                        <td style="background-color: lightgray;color: black; border-top: none;">
                        </td>
                        <td colspan="2" style="background-color: lightgray;color: black; border-top: none;">
                        </td>
                        @foreach ($organizations as $organization)
                            <tr>
                                <td colspan="4" style="padding: 10px;">
                                    <center>{{ $organization->organization }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($organization->org_from)) }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($organization->org_to)) }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $organization->org_hours }}</center>
                                </td>
                                <td colspan="2" style="padding: 10px;">
                                    <center>{{ $organization->org_position }}</center>
                                </td>
                            </tr>
                        @endforeach
                        @if (count($organizations) < 6)
                            @for ($i = count($organizations); $i < 6; $i++)
                                <tr>
                                    <td colspan="4" style="padding: 8px;">
                                        <center>N/A</center>
                                    </td>
                                    <td style="padding: 8px;">
                                        <center>N/A </center>
                                    </td>
                                    <td style="padding: 8px;">
                                        <center>N/A</center>
                                    </td>
                                    <td style="padding: 8px;">
                                        <center>N/A</center>
                                    </td>
                                    <td colspan="2" style="padding: 8px;">
                                        <center>N/A</center>
                                    </td>
                                </tr>
                            @endfor
                        @endif
                        <tr>
                            <td colspan="9" style="background-color: lightgray;color: red;padding: 0px;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <!-- Learning and Development -->
                        <tr>
                            <td colspan="9"
                                style="background-color: gray;color: white; font-size: 7px; font-weight: bold; font-style: italic;">
                                VII. LEARNING AND DEVELOPMENT
                                (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED</td>
                        </tr>
                        <tr>
                            <td colspan="4" style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>30. TITLE OF LEARNING AND DEVELOPMENT INTERVENTIONS/TRAINING PROGRAMS <br>
                                    (Write in full)</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black; padding: 8px 5px;">
                                <center>INCLUSIVE DATES OF <br> ATTENDANCE <br> (mm/dd/yyyy)</center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>NUMBER OF <br> HOURS</center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>Type of LD <br> ( Managerial/ <br> Supervisory/ <br> Technical/etc)</center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-bottom: none;">
                                <center>CONDUCTED/ SPONSORED BY <br> (Write in full/Do not abbreviate)</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="background-color: lightgray;color: black; border-top: none;">
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>From</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>To</center>
                            </td>
                            <td style="background-color: lightgray;color: black; border-top: none;"></td>
                            <td style="background-color: lightgray;color: black; border-top: none;"></td>
                            <td style="background-color: lightgray;color: black; border-top: none;"></td>
                        </tr>
                        @foreach ($trainings as $training)
                            <tr>
                                <td colspan="4" style="padding: 10px;">
                                    <center>{{ $training->training }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($training->training_from)) }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($training->training_to)) }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $training->hours }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $training->learning }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $training->sponsored_by }}</center>
                                </td>
                            </tr>
                        @endforeach
                        @if (count($trainings) < 17)
                            @for ($i = count($trainings); $i < 17; $i++)
                                <tr>
                                    <td colspan="4" style="padding: 8px;">
                                        <center>N/A</center>
                                    </td>
                                    <td style="padding: 8px;">
                                        <center>N/A</center>
                                    </td>
                                    <td style="padding: 8px;">
                                        <center>N/A</center>
                                    </td>
                                    <td style="padding: 8px;">
                                        <center>N/A</center>
                                    </td>
                                    <td style="padding: 8px;">
                                        <center>N/A</center>
                                    </td>
                                    <td style="padding: 8px;">
                                        <center>N/A</center>
                                    </td>
                                </tr>
                            @endfor
                        @endif
                        <tr>
                            <td colspan="9" style="background-color: lightgray;color: red;padding: 0px;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <!-- Skills and Hobbies -->
                        <tr>
                            <td colspan="9"
                                style="background-color: gray;color: white; font-size: 7px; font-weight: bold; font-style: italic;">
                                VIII. OTHER INFORMATION
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>31. SPECIAL SKILLS and HOBBIES</center>
                            </td>
                            <td colspan="5" style="background-color: lightgray;color: black; padding: 8px 5px;">
                                <center>32. NON-ACADEMIC DISTINCTIONS / RECOGNITION <br> (Write in full)</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>33. MEMBERSHIP IN ASSOCIATION/ORGANIZATION <br> (Write in full)</center>
                            </td>
                        </tr>

                        @for ($i = 0; $i < 10; $i++)
                            <tr>
                                <td colspan="2" style="padding: 8px;">
                                    <center>
                                        {{ isset($skills[$i]) && $skills[$i]->row == $i + 1 ? $skills[$i]->skill : 'N/A' }}
                                    </center>
                                </td>
                                <td colspan="5" style="padding: 8px;">
                                    <center>
                                        {{ isset($recognitions[$i]) && $recognitions[$i]->row == $i + 1 ? $recognitions[$i]->recognation : 'N/A' }}
                                    </center>
                                </td>
                                <td colspan="2" style="padding: 8px;">
                                    <center>
                                        {{ isset($memberships[$i]) && $memberships[$i]->row == $i + 1 ? $memberships[$i]->membership : 'N/A' }}
                                    </center>
                                </td>
                            </tr>
                        @endfor
                        <tbody class="pds-page-end">
                        <tr>
                            <td colspan="9" style="background-color: lightgray;color: red;padding: 0px;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" style="padding: 0; border: none;">
                                <table class="pds-signature-row">
                                    <tr>
                                        <td class="sig-label"><b>SIGNATURE</b></td>
                                        <td class="sig-field">(wet signature/e-signature/digital certificate)</td>
                                        <td class="date-label"><b>Date</b></td>
                                        <td class="date-field">&nbsp;</td>
                                    </tr>
                                </table>
                                <p class="pds-page-number"><i>CS FORM 212 (Revised 2025), Page 3 of 4</i></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Page 4 -->
        <div class="p-4 card page-break pds-page-last">
            <div class="main" style="width: 100%;">
                <div>
                    <table style="width: 100%;padding:10px; border: none;">
                        <!-- Quistionaire -->
                        @php
                            $q = collect($quesionaires)->keyBy('id');

                            $q34a = $q->get('1');
                            $q34b = $q->get('2');
                            $q35a = $q->get('3');
                            $q35b = $q->get('4');
                            $q36 = $q->get('5');
                            $q37 = $q->get('6');
                            $q38a = $q->get('7');
                            $q38b = $q->get('8');
                            $q39 = $q->get('9');
                            $q40a = $q->get('10');
                            $q40b = $q->get('11');
                            $q40c = $q->get('12');
                        @endphp
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">
                                    34. Are you related by consanguinity or affinity to the appointing or recommending
                                    authority, or to the
                                    chief of bureau or office or to the person who has immediate supervision over you in
                                    the Office,
                                    Bureau or Department where you will be appointed,
                                    <br>a. within the third degree?
                                    <br>b. within the fourth degree (for Local Government Unit - Career Employees)?
                                </p>
                            </td>
                            <td colspan="2">
                                <div>
                                    <label><input type="checkbox" {{ optional($q34a)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q34a)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <label><input type="checkbox" {{ optional($q34b)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q34b)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <span
                                        style="display:inline-block;width:100%;border-bottom:1px solid black;min-height:12px;">
                                        {{ optional($q34a)->yes_details ?? (optional($q34b)->yes_details ?? '') }}
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;border-bottom: none;">
                                <p style="font-size: 8px;">35. a. Have you ever been found guilty of any administrative
                                    offense?</p>
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <div>
                                    <label><input type="checkbox" {{ optional($q35a)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q35a)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <span
                                        style="display:inline-block;width:100%;border-bottom:1px solid black;min-height:12px;">
                                        {{ optional($q35a)->yes_details ?? '' }}
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;border-top: none;">
                                <p style="font-size: 8px;">b. Have you been criminally charged before any court?</p>
                            </td>
                            <td colspan="2" style="border-top: none;">
                                <div>
                                    <label><input type="checkbox" {{ optional($q35b)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q35b)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    Date Filed:
                                    <span style="display:inline-block;width:120px;border-bottom:1px solid black;">
                                        {{ optional($q35b)->date_filed ?? '' }}
                                    </span>
                                    <br>
                                    Status of Case/s:
                                    <span style="display:inline-block;width:120px;border-bottom:1px solid black;">
                                        {{ optional($q35b)->case_status ?? '' }}
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">
                                    36. Have you ever been convicted of any crime or violation of any law, decree,
                                    ordinance or regulation by any court or tribunal?
                                </p>
                            </td>
                            <td colspan="2">
                                <div>
                                    <label><input type="checkbox" {{ optional($q36)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q36)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <span
                                        style="display:inline-block;width:100%;border-bottom:1px solid black;min-height:12px;">
                                        {{ optional($q36)->yes_details ?? '' }}
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">
                                    37. Have you ever been separated from the service in any of the following modes:
                                    resignation, retirement, dropped from the rolls, dismissal, termination, end of
                                    term,
                                    finished contract or phased out (abolition) in the public or private sector?
                                </p>
                            </td>
                            <td colspan="2">
                                <div>
                                    <label><input type="checkbox" {{ optional($q37)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q37)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <span
                                        style="display:inline-block;width:100%;border-bottom:1px solid black;min-height:12px;">
                                        {{ optional($q37)->yes_details ?? '' }}
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">
                                    38. a. Have you ever been a candidate in a national or local election held within
                                    the last year
                                    (except Barangay election)?
                                </p>
                                 <p style="font-size: 8px;">
                                    b. Have you resigned from the government service during the three (3)-month period
                                    before the last election
                                    to promote/actively campaign for a national or local candidate?
                                </p>
                            </td>
                            <td colspan="2">
                                <div>
                                    <label><input type="checkbox" {{ optional($q38a)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q38a)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <span
                                        style="display:inline-block;width:100%;min-height:10px;">
                                        {{ optional($q38a)->yes_details ?? '' }}
                                    </span>
                                </div>
                                <div>
                                    <label><input type="checkbox" {{ optional($q38b)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q38b)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <span
                                        style="display:inline-block;width:100%;min-height:10px;">
                                        {{ optional($q38b)->yes_details ?? '' }}
                                    </span>
                                </div>
                            </td>
                        </tr>

                        {{-- <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                {{-- <p style="font-size: 8px;">
                                    b. Have you resigned from the government service during the three (3)-month period
                                    before the last election
                                    to promote/actively campaign for a national or local candidate?
                                </p> --}}
                            {{-- </td>
                            <td colspan="2">
                                <div>
                                    <label><input type="checkbox" {{ optional($q38b)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q38b)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <span
                                        style="display:inline-block;width:100%;border-bottom:1px solid black;min-height:12px;">
                                        {{ optional($q38b)->yes_details ?? '' }}
                                    </span>
                                </div>
                            </td>
                        </tr> --}}

                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">
                                    39. Have you acquired the status of an immigrant or permanent resident of another
                                    country?
                                </p>
                            </td>
                            <td colspan="2">
                                <div>
                                    <label><input type="checkbox" {{ optional($q39)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q39)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <p>If YES, give details (country):</p>
                                    <span
                                        style="display:inline-block;width:100%;border-bottom:1px solid black;min-height:12px;">
                                        {{ optional($q39)->yes_details ?? '' }}
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black; border-bottom: none;">
                                <p style="font-size: 8px;">
                                    40. Pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled
                                    Persons (RA 7277); and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer
                                    the following items:
                                    <br>a. Are you a member of any indigenous group?
                                </p>
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <div>
                                    <label><input type="checkbox" {{ optional($q40a)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q40a)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <p>If YES, please specify:</p>
                                    <span
                                        style="display:inline-block;width:100%;border-bottom:1px solid black;min-height:12px;">
                                        {{ optional($q40a)->yes_details ?? '' }}
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="7"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                                <p style="font-size: 8px;">b. Are you a person with disability?</p>
                            </td>
                            <td colspan="2" style="border-bottom: none; border-top: none;">
                                <div>
                                    <label><input type="checkbox" {{ optional($q40b)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q40b)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <p>If YES, please specify ID No:</p>
                                    <span
                                        style="display:inline-block;width:100%;border-bottom:1px solid black;min-height:12px;">
                                        {{ optional($q40b)->yes_details ?? '' }}
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;border-top: none;">
                                <p style="font-size: 8px;">c. Are you a solo parent?</p>
                            </td>
                            <td colspan="2" style="border-top: none;">
                                <div>
                                    <label><input type="checkbox" {{ optional($q40c)->is_yes ? 'checked' : '' }}>
                                        Yes</label>
                                    <label><input type="checkbox" {{ optional($q40c)->is_no ? 'checked' : '' }}>
                                        No</label>
                                </div>
                                <div>
                                    <p>If YES, please specify ID No:</p>
                                    <span
                                        style="display:inline-block;width:100%;border-bottom:1px solid black;min-height:12px;">
                                        {{ optional($q40c)->yes_details ?? '' }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <!-- References -->
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">41. REFERENCES
                                    <label style="color: black;">
                                        <b>(Person not related by consanguinity or affinity to applicant /appointee)</b>
                                    </label>
                                </p>
                            </td>
                            <td colspan="2" rowspan="6" style="border-bottom: none;">
                                <div
                                    style="border: 1px solid black; width: 70px; height: 85px; margin: auto;padding: 4px; display: flex; align-items: center; justify-content: center;">
                                    @if (!empty($info->photo))
                                        <img src="data:image/jpeg;base64,{{ $info->photo }}" alt="Employee photo"
                                            style="max-width: 100%; max-height: 100%; object-fit: cover; display: block;">
                                    @else
                                        <div style="text-align: center; font-size: 8px; line-height: 1.2;">
                                            ID picture taken within<br>
                                            the last 6 months<br>
                                            4.5 cm. X 3.5 cm (passport size)<br>
                                            {{-- <span style="color: red;">Computer generated or photocopied picture is not
                                                acceptable</span> --}}
                                        </div>
                                    @endif
                                </div>
                                <p style="text-align: center; margin-top: 4px;">PHOTO</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="background-color: lightgray;color: black;">
                                <center>NAME</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>OFFICE / RESIDENTIAL ADDRESS</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>CONTACT NO. <br> AND/OR EMAIL</center>
                            </td>
                            <!-- <td colspan="2" style="border-bottom: none;border-top: none;"></td> -->
                        </tr>
                        @foreach ($references as $reference)
                            <tr>
                                <td colspan="3" style="padding: 5px;">
                                    <center>{{ $reference->ref_name }}</center>
                                </td>
                                <td colspan="2" style="padding: 5px;">
                                    <center>{{ $reference->ref_address }}</center>
                                </td>
                                <td colspan="2" style="padding: 5px;">
                                    <center>{{ $reference->ref_contact_no }}</center>
                                </td>
                                <!-- <td colspan="2" style="border-bottom: none;border-top: none;"></td> -->
                            </tr>
                        @endforeach
                        @if (count($references) < 3)
                            @for ($i = count($references); $i < 3; $i++)
                                <tr>
                                    <td colspan="3" style="padding: 5px;"></td>
                                    <td colspan="2" style="padding: 5px;"></td>
                                    <td colspan="2" style="padding: 5px;"></td>
                                    <!-- <td colspan="2" style="border-bottom: none;border-top: none;"></td> -->
                                </tr>
                            @endfor
                        @endif
                        <tbody class="pds-footer-group">
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">
                                    42. I declare under oath that I have personally accomplished this Personal Data
                                    Sheet which is a true, correct and
                                    <br> complete statement pursuant to the provisions of pertinent laws, rules and
                                    regulations of the Republic of the
                                    <br> Philippines. I authorize the agency head/authorized representative to
                                    verify/validate the contents stated herein.
                                    <br> I agree that any misrepresentation made in this document and its
                                    attachments
                                    shall cause the filing of
                                    <br> administrative/criminal case/s against me.
                                </p>
                            </td>
                            <!-- <td colspan="2" style="border-top: none;"></td> -->
                        </tr>
                        <tr class="pds-signature-section">
                            <td colspan="3" style="padding:3px; border-right:none;">
                                <div style="width: 200px; height: 62px;margin: auto;">
                                    <table style="height: 100%;">
                                        <tr>
                                            <td style="background-color: lightgray;color: black;">Government Issued ID
                                                (i.e.Passport, GSIS, SSS, PRC, Driver's License, etc.) PLEASE INDICATE
                                                ID Number and Date of Issuance</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom: 4px;">Government Issued ID:</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom: 4px;">ID/License/Passport No.: </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom: 4px;">Date/Place of Issuance:</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td colspan="3" style="padding:3px; border-right:none;border-left: none;">
                                <div style="width: 200px; height: 62px;margin: auto;">
                                    <table style="width: 100%;height: 100%;">
                                        <tr>
                                            <td style="padding: 8px 4px; color: red;">
                                                <center>(wet signature/e-signature/digital certificate)</center>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: lightgray;color: black;padding-bottom: 3px;">
                                                <center>Signature (Sign inside the box)</center>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom: 2px;"></td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: lightgray;color: black;padding-bottom: 3px;">
                                                <center>Date Accomplished</center>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td colspan="3" style="padding:3px; border-left: none;">
                                <div style="width: 100px; height: 62px;margin: auto;">
                                    <table style="width: 100%;height: 100%;">
                                        <tr>
                                            <td style="padding: 18px;"></td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: lightgray;color: black;padding-bottom: 3px;">
                                                <center>Right Thumbmark</center>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <tr class="pds-final-section">
                            <td colspan="9" style="border-top: none; padding: 3px 6px 2px 6px;">
                                <p style="text-align: center; font-size: 6px; margin: 0 0 3px 0; line-height: 1.2;">
                                    SUBSCRIBED AND SWORN to before me this ___________________________, affiant
                                    exhibiting his/her validly issued government ID as indicated above.
                                </p>
                                <div style="margin: 0 auto; width: 320px; border: 1px solid black;">
                                    <div style="min-height: 45px; padding: 6px 4px; color: red; text-align: center; font-size: 5px; line-height: 1.1;">
                                        (wet signature/e-signature/digital certificate except for notary public)
                                    </div>
                                    <div style="background-color: lightgray; color: black; text-align: center; font-size: 6px; padding: 3px 4px;">
                                        Person Administering Oath
                                    </div>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                        <tr>
                            <td colspan="9" style="border: none; text-align: right; padding: 2px 0 0 0;">
                                <i>CS FORM 212 (Revised 2025), Page 4 of 4</i>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
