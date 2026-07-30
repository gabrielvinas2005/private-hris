<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhilHealth Member Registration Form (PMRF)</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            line-height: 1.2;
            padding: 10px;
            background: white;
        }

        .form-container {
            width: 100%;
            MAX-WIDTH: 7.5in;
            margin: 0 auto;
            background: white;
            border: 2px solid #000;
            padding: 0;
        }

        .form-container > *:first-child {
            padding-top: 5px;
        }

        .form-container > *:last-child {
            padding-bottom: 5px;
        }

        .form-header {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            padding-bottom: 5px;
            margin-left: 2px;
        }

        .form-header-row {
            display: table-row;
        }

        .form-header-left {
            display: table-cell;
            vertical-align: top;
            width: 30%;
            padding-right: 10px;
        }

        .form-header-center {
            vertical-align: top;
            width: 50%;
            text-align: center;
            position: relative;
            left: 50%;
        }

        .form-header-right {
            display: table-cell;
            vertical-align: top;
            width: 30%;
            text-align: right;
            padding-left: 10px;
        }

        .form-header h1 {
            font-size: 10px;
            font-weight: bold;
            margin: 0;
            margin-bottom: 2px;
            text-align: center;
        }

        .form-header .form-code {
            font-size: 8px;
            text-align: left;
            margin: 0;
            line-height: 1.3;
            font-weight: bold;
        }

        .form-header .pmrf-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .philhealth-logo {
            width: 170px;
            height: auto;
            max-height: 60px;
            margin-bottom: 5px;
            object-fit: contain;
            display: block;
        }

        .logo-placeholder {
            width: 80px;
            height: 60px;
            border: 1px dashed #ccc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 7px;
            color: #999;
            margin-bottom: 5px;
        }

        .logo-text {
            font-size: 10px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 2px;
        }

        .logo-tagline {
            font-size: 7px;
            color: #666;
            font-style: italic;
        }

        .reminders-section {
            padding: 5px;
            margin-bottom: 8px;
            width: 380px;
        }

        .header-content {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .header-content-row {
            display: table-row;
        }

        .header-content-left {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            padding-right: 10px;
        }

        .header-content-right {
            position: relative;
            left: -25px;
            bottom: 2%;
            vertical-align: top;
            padding-left: 10px;
        }

        .reminders-section h3 {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .reminders-section ol {
            margin-left: 15px;
            font-size: 11px;
        }

        .section-header {
            background: #e0e6cc;
            border: 1px solid #000;
            color: black;
            padding: 3px 5px;
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 0;
            margin-left: 1px;
            margin-right: 1px;
            text-align: center;
        }

        .form-row {
            display: flex;
            gap: 3px;
            margin-bottom: 3px;
            align-items: flex-start;
        }

        .personal-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border-top: none;

        }

        .personal-details-table td {

            padding: 4px;
            font-size: 7px;
            vertical-align: middle;
        }

        .personal-details-table thead td {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
            padding: 3px;
        }

        .personal-details-table .label-cell {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
            padding: 4px;
        }

        .personal-details-table .input-cell {
            padding: 3px;
            height: 20px;
        }

        .personal-details-table .input-field {
            border: none;
            border-bottom: 1px solid #000;
            width: 100%;
            font-size: 8px;
            padding: 2px;
            background: transparent;
        }

        .date-boxes {
            display: flex !important;
            flex-direction: row !important;
            gap: 2px;
            align-items: center;
            margin-top: 3px;
            flex-wrap: nowrap !important;
            white-space: nowrap !important;
            width: auto;
            min-width: 0;
        }

        .date-box {
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            text-align: center;
            font-size: 9px;
            line-height: 14px;
            font-weight: bold;
            flex-shrink: 0;
            min-width: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .date-separator {
            font-size: 10px;
            padding: 0;
            flex-shrink: 0;
            margin: 0 2px;
            line-height: 1;
        }

        .form-group {
            flex: 1;
            min-width: 0;
        }

        .form-group label {
            display: block;
            font-size: 7px;
            font-weight: bold;
            margin-bottom: 1px;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="email"],
        .form-group select {
            width: 100%;
            border: none;
            border-bottom: 1px solid #000;
            padding: 2px;
            font-size: 9px;
            background: transparent;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 3px;
            margin-bottom: 3px;
        }

        .purpose-section .checkbox-group {
            margin-bottom: 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 10px;
            height: 10px;
        }

        .checkbox-group label {
            font-size: 8px;
            font-weight: normal;
        }

        .inline-checkboxes {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .date-input {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .date-box {
            width: 25px;
            text-align: center;
            border: 1px solid #000;
            padding: 2px;
            font-size: 9px;
        }

        .purpose-section {
            padding: 5px;
        }

        .purpose-section > label {
            display: block;
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 5px;
        }

        .purpose-checkboxes {
            display: table;
            width: 100%;
        }

        .purpose-checkboxes .checkbox-group {
            display: table-cell;
            vertical-align: middle;
            white-space: nowrap;
            padding-right: 20px;
        }

        .purpose-section .checkbox-group {
            display: flex;
            align-items: center;
            gap: 3px;
            margin: 0;
        }

        .purpose-section .checkbox-group span {
            display: inline-block;
            vertical-align: middle;
        }

        .purpose-section .checkbox-group label {
            display: inline-block;
            vertical-align: middle;
            margin: 0;
        }

        .pin-section {
            padding: 5px;
            margin-bottom: 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-left: 12%;
        }

        .pin-section label {
            font-weight: bold;
            font-size: 9px;
            display: block;
            margin-bottom: 3px;
        }

        .pin-boxes {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2px;
            flex-wrap: nowrap;
            margin-bottom: 5px;
        }

        .pin-box {
            width: 16px;
            height: 16px;
            border: 1px solid #000;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            line-height: 16px;
            flex-shrink: 0;
        }

        .pin-separator {
            font-size: 12px;
            padding: 0 1px;
            line-height: 16px;
            vertical-align: top;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        table td, table th {
            border: 1px solid #000;
            padding: 3px;
            font-size: 7px;
        }

        table th {
            background: #d0d0d0;
            font-weight: bold;
            text-align: center;
        }

        .member-type-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5px;
            border: 1px solid #000;
            padding: 8px;
        }

        .member-type-column {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .member-type-title {
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 5px;
            text-decoration: underline;
        }

        .signature-section {
            margin-top: 15px;
            border: 1px solid #000;
            padding: 10px;
        }

        .signature-text {
            font-size: 7px;
            line-height: 1.4;
            margin-bottom: 10px;
        }

        .signature-box {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .signature-left {
            flex: 1;
        }

        .signature-right {
            flex: 1;
            border-left: 1px solid #000;
            padding-left: 10px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 3px;
            text-align: center;
            font-size: 8px;
        }

        .instructions-section {
            margin-top: 10px;
            border: 1px solid #000;
            padding: 8px;
            background: #f9f9f9;
        }

        .instructions-section h3 {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .instructions-section ol {
            margin-left: 15px;
            font-size: 7px;
            line-height: 1.5;
        }

        .instructions-section ol li {
            margin-bottom: 3px;
        }

        .name-format-example {
            display: flex;
            gap: 10px;
            margin: 5px 0;
            padding: 5px;
            background: white;
            border: 1px solid #ccc;
        }

        .name-format-example div {
            flex: 1;
            text-align: center;
            font-size: 8px;
        }

        .name-format-example .label {
            font-weight: bold;
            margin-bottom: 2px;
        }

        @media print {
            @page {
                size: A4;
                margin: 0.5cm;
            }
            
            body {
                padding: 0;
                margin: 0;
            }
            
            .form-container {
                border: 1px solid #000;
                width: 100%;
                max-width: 100%;
                padding: 3px;
            }
        }

        .small-text {
            font-size: 6px;
        }

        .address-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 3px;
            margin-bottom: 5px;
        }

        .checkbox-inline {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            margin-right: 10px;
        }

        .updating-section {
            border: 1px solid #000;
            padding: 8px;
            margin-top: 10px;
        }

        .from-to-section {
            display: flex;
            gap: 10px;
            margin-top: 5px;
        }

        .from-to-section > div {
            flex: 1;
        }

        /* Personal info block (Date of birth, sex, civil status, citizenship, IDs) */
        .personal-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .personal-info-table td {
            border: 1px solid #000;
            vertical-align: top;
            padding: 4px;
            font-size: 7px;
        }

        .personal-info-section-title {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 3px;
            display: block;
        }

        .personal-info-subsection {
            margin-top: 4px;
        }

        .personal-info-inner-table {
            width: 100%;
            border-collapse: collapse;
        }

        .personal-info-inner-table th,
        .personal-info-inner-table td {
            border: 1px solid #000;
            padding: 2px;
            font-size: 7px;
            vertical-align: top;
        }

        .personal-info-inner-table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: left;
        }

        /* PHILSYS / TIN digit boxes (horizontal like official form) */
        .id-table {
            border-collapse: collapse;
            margin-top: 2px;
        }

        .id-table td {
            width: 14px;
            height: 18px;
            border: 1px solid #000;
            text-align: center;
            line-height: 18px;
            font-size: 8px;
            padding: 0;
        }

        .id-table-separator {
            width: 6px;
            border: none;
        }

        .full-width-section {
            margin-left: -1px;
            margin-right: -1px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <!-- Form Header -->
        <div class="form-header">
            <div class="form-header-row">
                <div class="form-header-left">
                    @if(!empty($philhealthLogo))
                        <img src="data:image/png;base64,{{ $philhealthLogo }}" alt="PhilHealth" class="philhealth-logo">
                    @else
                        <div class="logo-placeholder">
                            <div class="logo-text">PhilHealth</div>
                            <div class="logo-tagline">Your Partner in Health</div>
                        </div>
                    @endif
                </div>
                <div class="form-header-center">
                    <div style="font-size: 17px; font-weight: bold;">PMRF</div>
                    <h1>PHILHEALTH MEMBER REGISTRATION FORM</h1>
                    <div>UHC v1.0 January 2020</div>
                </div>
            </div>
        </div>

        <!-- Header Content with Reminders and PIN -->
        <div class="header-content">
            <div class="header-content-row">
                <div class="header-content-left">
                    <!-- Reminders Section -->
                    <div class="reminders-section">
                        <h3>REMINDERS:</h3>
                        <ol>
                            <li>Your PhilHealth Identification Number (PIN) is your unique and permanent number.</li>
                            <li>Always use your PIN in all transactions with PhilHealth.</li>
                            <li>For Updating/Amendment check the appropriate box and provide details to be accomplished and submit corresponding supporting documents.</li>
                            <li>Please read instructions at the back before filling-out this form.</li>
                        </ol>
                    </div>
                </div>
                <div class="header-content-right">
                    <div class="pin-section">
                        <div class="pin-boxes">
                            @php
                                $pin = $formData['philhealth_no'] ?? '';
                                $pinParts = explode('-', $pin);
                                $pin1 = isset($pinParts[0]) ? str_pad($pinParts[0], 2, '0', STR_PAD_LEFT) : '';
                                $pin2 = isset($pinParts[1]) ? str_pad($pinParts[1], 7, '0', STR_PAD_LEFT) : '';
                                $pin3 = isset($pinParts[2]) ? str_pad($pinParts[2], 3, '0', STR_PAD_LEFT) : '';
                                $pin1Digits = str_split($pin1);
                                $pin2Digits = str_split($pin2);
                                $pin3Digits = str_split($pin3);
                            @endphp
                            @for($i = 0; $i < 2; $i++)
                                <div class="pin-box">{{ isset($pin1Digits[$i]) ? $pin1Digits[$i] : '' }}</div>
                            @endfor
                            <span class="pin-separator">-</span>
                            @for($i = 0; $i < 7; $i++)
                                <div class="pin-box">{{ isset($pin2Digits[$i]) ? $pin2Digits[$i] : '' }}</div>
                            @endfor
                            <span class="pin-separator">-</span>
                            @for($i = 0; $i < 3; $i++)
                                <div class="pin-box">{{ isset($pin3Digits[$i]) ? $pin3Digits[$i] : '' }}</div>
                            @endfor
                        </div>
                        <label style="font-weight: bold; font-size: 8px; display: block; text-align: center; margin-top: 3px;">PHILHEALTH IDENTIFICATION NUMBER (PIN)</label>
                    </div>

                    <!-- Purpose Section -->
                    <div class="purpose-section" style="margin-top: -20px;">
                        <label style="font-weight: bold; font-size: 9px; margin-left: 60px;">PURPOSE:</label>
                        <div class="purpose-checkboxes">
                            <div class="checkbox-group">
                                <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin: 0 0 0 60px; text-align: center; line-height: 12px; font-size: 10px;">{{ ($formData['purpose'] ?? '') === 'registration' ? '✔' : '' }}</span>
                                <label for="registration" style="font-size: 11px; font-weight: bold; margin: 0;">REGISTRATION</label>
                            </div>
                            <div class="checkbox-group">
                                <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin-right: 3px; text-align: center; line-height: 12px; font-size: 10px;">{{ ($formData['purpose'] ?? '') === 'updating' ? '✔' : '' }}</span>
                                <label for="updating" style="font-size: 11px; font-weight: bold; margin: 0;">UPDATING/AMENDMENT</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-row" style="margin: 8px 0 0 60px;">
                        <div class="form-group">
                            <label style="font-weight: bold; font-size: 9px;">Preferred KonSulTa Provider</label>
                            <div style="border: 1px solid #000; width: 88%; padding: 2px; font-size: 8px; min-height: 18px;">{{ $formData['konsulta_provider'] ?? '' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section I: Personal Details -->
        <div class="full-width-section">
            <div class="section-header" style="margin-top: -20px;">I. PERSONAL DETAILS</div>

            <!-- Personal Details Table -->
            <table class="personal-details-table">
                <thead>
                    <tr>
                        <td style="width: 12%;"></td>
                        <td style="width: 18%;">LAST NAME</td>
                        <td style="width: 18%;">FIRST NAME</td>
                        <td style="width: 12%;">NAME EXTENSION<br>(Jr./Sr./III)</td>
                        <td style="width: 15%;">MIDDLE NAME</td>
                        <td style="width: 12%;">NO MIDDLE NAME<br>(Check if applicable only)</td>
                        <td style="width: 13%;">MONONYM<br>(Check if applicable only)</td>
                    </tr>
                </thead>
                <tbody>
                    <!-- MEMBER Row -->
                    <tr>
                        <td class="label-cell" style="font-weight: bold; font-size: 8px;">MEMBER</td>
                        <td class="input-cell">
                            <div style="font-size: 8px; min-height: 18px; padding: 2px 0; text-align: center;">{{ $formData['last_name'] ?? '' }}</div>
                        </td>
                        <td class="input-cell">
                            <div style="font-size: 8px; min-height: 18px; padding: 2px 0; text-align: center;">{{ $formData['first_name'] ?? '' }}</div>
                        </td>
                        <td class="input-cell">
                            <div style="font-size: 8px; min-height: 18px; padding: 2px 0; text-align: center;">{{ $formData['name_extension'] ?? '' }}</div>
                        </td>
                        <td class="input-cell">
                            <div style="font-size: 8px; min-height: 18px; padding: 2px 0; text-align: center;">{{ $formData['middle_name'] ?? '' }}</div>
                        </td>
                        <td class="input-cell" style="text-align: center; vertical-align: middle;">
                            <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; text-align: center; line-height: 12px;"></span>
                        </td>
                        <td class="input-cell" style="text-align: center; vertical-align: middle;">
                            <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; text-align: center; line-height: 12px;"></span>
                        </td>
                    </tr>
                    <!-- MOTHER's MAIDEN NAME Row -->
                    <tr>
                        <td class="label-cell" style="font-weight: bold; font-size: 8px;">MOTHER's<br>MAIDEN NAME</td>
                        <td class="input-cell">
                            <div style="font-size: 8px; min-height: 18px; padding: 2px 0;"></div>
                        </td>
                        <td class="input-cell">
                            <div style="font-size: 8px; min-height: 18px; padding: 2px 0;"></div>
                        </td>
                        <td class="input-cell">
                            <div style="font-size: 8px; min-height: 18px; padding: 2px 0;"></div>
                        </td>
                        <td class="input-cell">
                            <div style="font-size: 8px; min-height: 18px; padding: 2px 0;"></div>
                        </td>
                        <td class="input-cell" style="text-align: center; vertical-align: middle;">
                            <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; text-align: center; line-height: 12px;"></span>
                        </td>
                        <td class="input-cell" style="text-align: center; vertical-align: middle;">
                            <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; text-align: center; line-height: 12px;"></span>
                        </td>
                    </tr>
                    <!-- SPOUSE Row -->
                    <tr>
                        <td class="label-cell" style="font-weight: bold; font-size: 8px;">SPOUSE<br>(If Married)</td>
                        <td class="input-cell">
                            <div style="font-size: 8px; min-height: 18px; padding: 2px 0;"></div>
                        </td>
                        <td class="input-cell">
                            <div style="font-size: 8px; min-height: 18px; padding: 2px 0;"></div>
                        </td>
                        <td class="input-cell">
                            <div style="font-size: 8px; min-height: 18px; padding: 2px 0;"></div>
                        </td>
                        <td class="input-cell">
                            <div style="font-size: 8px; min-height: 18px; padding: 2px 0;"></div>
                        </td>
                        <td class="input-cell" style="text-align: center; vertical-align: middle;">
                            <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; text-align: center; line-height: 12px;"></span>
                        </td>
                        <td class="input-cell" style="text-align: center; vertical-align: middle;">
                            <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; text-align: center; line-height: 12px;"></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Date of Birth, Place of Birth, Sex, Civil Status, Citizenship, IDs -->
        <table class="personal-info-table">
            <tr>
                <!-- Left block header: Date of Birth -->
                <td colspan="2" style="vertical-align: top; border-right: 1px solid #000; min-width: 160px;">
                    <span class="personal-info-section-title">DATE OF BIRTH (mm-dd-yyyy)</span>
                    <div class="date-boxes" style="margin-top: 2px; display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important; width: auto !important; min-width: 0 !important;">
                        @php
                            $dob = $formData['date_of_birth'] ?? '';
                            $dobParts = explode('-', $dob);
                            $month = isset($dobParts[0]) ? str_pad($dobParts[0], 2, '0', STR_PAD_LEFT) : '';
                            $day = isset($dobParts[1]) ? str_pad($dobParts[1], 2, '0', STR_PAD_LEFT) : '';
                            $year = isset($dobParts[2]) ? $dobParts[2] : '';
                            $monthDigits = str_split($month);
                            $dayDigits = str_split($day);
                            $yearDigits = str_split($year);
                        @endphp
                        @for($i = 0; $i < 2; $i++)
                            <div class="date-box">{{ $monthDigits[$i] ?? '' }}</div>
                        @endfor
                        <span class="date-separator">-</span>
                        @for($i = 0; $i < 2; $i++)
                            <div class="date-box">{{ $dayDigits[$i] ?? '' }}</div>
                        @endfor
                        <span class="date-separator">-</span>
                        @for($i = 0; $i < 4; $i++)
                            <div class="date-box">{{ $yearDigits[$i] ?? '' }}</div>
                        @endfor
                    </div>
                    <div style="text-align: center; font-size: 6px; margin-top: 2px;">m m - d d - y y y y</div>
                </td>

                <!-- PLACE OF BIRTH header (first row only); CITIZENSHIP will appear directly beneath -->
                <td style="width: 32%; vertical-align: top; border-right: 1px solid #000;">
                    <span class="personal-info-section-title">PLACE OF BIRTH (City/Municipality/Province/Country)</span>
                    <div class="small-text" style="margin-top: 2px;">
                        (Please indicate country if born outside the Philippines)
                    </div>
                    <div style="margin-top: 10px; margin-left: 5px; font-size: 10px;">
                        {{ $formData['place_of_birth'] ?? '' }}
                    </div>
                </td>
                
                <!-- PHILSYS / TIN block spans both rows on far right -->
                <td rowspan="2" style="width: 28%; vertical-align: top; margin-top: 25px;">
                    <div style="margin-top: 40px;">
                        @php
                            $philsysRaw = preg_replace('/\D/', '', $formData['philsys_id'] ?? '');
                            $philsysDigits = str_split($philsysRaw);
                            $tinRaw = preg_replace('/\D/', '', $formData['tin_no'] ?? '');
                            $tinDigits = str_split($tinRaw);
                        @endphp

                        <span class="personal-info-section-title">PHILSYS ID NUMBER (Optional)</span>
                        <table class="id-table">
                            <tr>
                                @for($i = 0; $i < 4; $i++)
                                    <td>{{ $philsysDigits[$i] ?? '' }}</td>
                                @endfor
                                <td class="id-table-separator"></td>
                                @for($i = 4; $i < 8; $i++)
                                    <td>{{ $philsysDigits[$i] ?? '' }}</td>
                                @endfor
                                <td class="id-table-separator"></td>
                                @for($i = 8; $i < 12; $i++)
                                    <td>{{ $philsysDigits[$i] ?? '' }}</td>
                                @endfor
                            </tr>
                        </table>

                        <div style="margin-top: 8px;">
                            <span class="personal-info-section-title">
                                TAX PAYER IDENTIFICATION NUMBER (TIN) (Optional)
                            </span>
                            <table class="id-table">
                                <tr>
                                    @for($i = 0; $i < 3; $i++)
                                        <td>{{ $tinDigits[$i] ?? '' }}</td>
                                    @endfor
                                    <td class="id-table-separator"></td>
                                    @for($i = 3; $i < 6; $i++)
                                        <td>{{ $tinDigits[$i] ?? '' }}</td>
                                    @endfor
                                    <td class="id-table-separator"></td>
                                    @for($i = 6; $i < 9; $i++)
                                        <td>{{ $tinDigits[$i] ?? '' }}</td>
                                    @endfor
                                </tr>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>

            <!-- Second row: under DATE OF BIRTH -> SEX / CIVIL STATUS; under PLACE OF BIRTH -> CITIZENSHIP -->
            <tr>
                <td style="width: 14%; vertical-align: top; border-right: 1px solid #000;">
                    <span class="personal-info-section-title">SEX</span>
                    <div style="margin-top: 4px;">
                        <div class="checkbox-group" style="margin-bottom: 2px;">
                            <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin-right: 4px; text-align: center; line-height: 12px; font-size: 10px;">
                                {{ (strtoupper($formData['sex'] ?? '') === 'MALE' || strtoupper($formData['sex'] ?? '') === 'M') ? '✓' : '' }}
                            </span>
                            <label style="font-size: 8px; font-weight: normal; position: relative; top: -2px;">Male</label>
                        </div>
                        <div class="checkbox-group">
                            <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin-right: 4px; text-align: center; line-height: 12px; font-size: 10px;">
                                {{ (strtoupper($formData['sex'] ?? '') === 'FEMALE' || strtoupper($formData['sex'] ?? '') === 'F') ? '✓' : '' }}
                            </span>
                            <label style="font-size: 8px; font-weight: normal; position: relative; top: -2px;">Female</label>
                        </div>
                    </div>
                </td>

                <td style="width: 22%; vertical-align: top; border-right: 1px solid #000;">
                    <span class="personal-info-section-title">CIVIL STATUS</span>
                    @php $civilStatus = strtoupper($formData['civil_status'] ?? ''); @endphp
                    <div style="margin-top: 4px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="padding: 0 4px 2px 0; border: none;">
                                    <div class="checkbox-group">
                                        <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin-right: 4px; text-align: center; line-height: 12px; font-size: 10px;">
                                            {{ strpos($civilStatus, 'SINGLE') !== false ? '✓' : '' }}
                                        </span>
                                        <label style="font-size: 8px; font-weight: normal; position: relative; top: -2px;">Single</label>
                                    </div>
                                </td>
                                <td style="padding: 0 0 2px 4px; border: none;">
                                    <div class="checkbox-group">
                                        <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin-right: 4px; text-align: center; line-height: 12px; font-size: 10px;">
                                            {{ strpos($civilStatus, 'ANNULLED') !== false ? '✓' : '' }}
                                        </span>
                                        <label style="font-size: 8px; font-weight: normal; position: relative; top: -2px;">Annulled</label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 0 4px 2px 0; border: none;">
                                    <div class="checkbox-group">
                                        <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin-right: 4px; text-align: center; line-height: 12px; font-size: 10px;">
                                            {{ strpos($civilStatus, 'MARRIED') !== false ? '✓' : '' }}
                                        </span>
                                        <label style="font-size: 8px; font-weight: normal; position: relative; top: -2px;">Married</label>
                                    </div>
                                </td>
                                <td style="padding: 0 0 2px 4px; border: none;">
                                    <div class="checkbox-group">
                                        <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin-right: 4px; text-align: center; line-height: 12px; font-size: 10px;">
                                            {{ strpos($civilStatus, 'WIDOW') !== false ? '✓' : '' }}
                                        </span>
                                        <label style="font-size: 8px; font-weight: normal; position: relative; top: -2px;">Widow/er</label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 0; border: none;">
                                    <div class="checkbox-group">
                                        <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin-right: 4px; text-align: center; line-height: 12px; font-size: 10px;">
                                            {{ strpos($civilStatus, 'SEPARATED') !== false ? '✓' : '' }}
                                        </span>
                                        <label style="font-size: 8px; font-weight: normal; position: relative; top: -2px;">Legally Separated</label>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>

                <td style="width: 32%; vertical-align: top; border-right: 1px solid #000;">
                    <span class="personal-info-section-title">CITIZENSHIP</span>
                    @php $citizenship = strtoupper($formData['citizenship'] ?? 'FILIPINO'); @endphp
                    <div style="margin-top: 4px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="padding: 0 4px 2px 0; border: none;">
                                    <div class="checkbox-group">
                                        <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin-right: 4px; text-align: center; line-height: 12px; font-size: 10px;">
                                            {{ strpos($citizenship, 'FILIPINO') !== false ? '✓' : '' }}
                                        </span>
                                        <label style="font-size: 8px; font-weight: normal; position: relative; top: -2px;">FILIPINO</label>
                                    </div>
                                </td>
                                <td style="padding: 0 0 2px 4px; border: none;">
                                    <div class="checkbox-group">
                                        <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin-right: 4px; text-align: center; line-height: 12px; font-size: 10px;">
                                            {{ strpos($citizenship, 'FOREIGN') !== false ? '✓' : '' }}
                                        </span>
                                        <label style="font-size: 8px; font-weight: normal; position: relative; top: -2px;">FOREIGN NATIONAL</label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 0 4px 0 0; border: none;">
                                    <div class="checkbox-group">
                                        <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin-right: 4px; text-align: center; line-height: 12px; font-size: 10px;">
                                            {{ strpos($citizenship, 'DUAL') !== false ? '✓' : '' }}
                                        </span>
                                        <label style="font-size: 8px; font-weight: normal; position: relative; top: -2px;">DUAL CITIZEN</label>
                                    </div>
                                </td>
                                <td style="border: none;"></td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>


        <!-- Section II: Address and Contact Details -->
        <div class="section-header" style="margin-top: -20px;">II. ADDRESS and CONTACT DETAILS</div>

        <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 8px;">
            <tr>
                <!-- Left column: Permanent Home Address -->
                <td style="width: 65%; vertical-align: top; border-right: 1px solid #000; padding: 4px;">
                    <div style="font-weight: bold; margin-bottom: 2px;">PERMANENT HOME ADDRESS</div>
                    <div style="font-size: 7px; margin-bottom: 1px;">
                        <span style="display: inline-block; margin-right: 40px;">Unit/Room No./Floor</span>
                        <span style="display: inline-block; margin-right: 40px;">Building Name</span>
                        <span style="display: inline-block; margin-right: 40px;">Lot/Block/Phase/House Number</span>
                        <span style="display: inline-block;">Street Name</span>
                    </div>
                    <div style="border-bottom: 1px solid #000; padding: 2px; font-size: 9px; min-height: 16px;">
                        {{ $formData['permanent_address'] ?? '' }}
                    </div>
                    <div style="margin-top: 3px; font-size: 7px;">
                        <span style="display: inline-block; margin-right: 40px;">Subdivision</span>
                        <span style="display: inline-block; margin-right: 40px;">Barangay</span>
                        <span style="display: inline-block; margin-right: 40px;">Municipality/City</span>
                        <span style="display: inline-block; margin-right: 40px;">Province/State/Country (If abroad)</span>
                        <span style="display: inline-block;">ZIP Code</span>
                    </div>
                    <div style="margin-top: 1px; text-align: right; margin-right: 45px;">
                        {{ $formData['permanent_zip'] ?? '' }}
                    </div>
                </td>

                <!-- Right column: Contact Numbers (rowspan over Permanent + Mailing) -->
                <td rowspan="2" style="width: 35%; vertical-align: top; padding: 4px;">
                    <div style="font-weight: bold; margin-bottom: 2px;">Home Phone Number</div>
                    <div style="border: 1px solid #000; padding: 2px; font-size: 9px; min-height: 16px;">
                        {{ $formData['home_phone'] ?? '' }}
                    </div>
                    <div style="font-size: 7px; margin-top: 2px; margin-bottom: 4px;">
                        (COUNTRY CODE + AREA CODE + TELEPHONE NUMBER)
                    </div>

                    <div style="font-weight: bold; margin-bottom: 2px; margin-top: 2px;">Mobile Number (Required)</div>
                    <div style="border: 1px solid #000; padding: 2px; font-size: 9px; min-height: 16px;">
                        {{ $formData['mobile_number'] ?? '' }}
                    </div>

                    <div style="font-weight: bold; margin-bottom: 2px; margin-top: 6px;">Business (Direct Line)</div>
                    <div style="border: 1px solid #000; padding: 2px; font-size: 9px; min-height: 16px;">
                        {{ $formData['business_phone'] ?? '' }}
                    </div>

                    <div style="font-weight: bold; margin-bottom: 2px; margin-top: 6px;">E-mail Address (Required for OFW)</div>
                    <div style="border: 1px solid #000; padding: 2px; font-size: 9px; min-height: 16px;">
                        {{ $formData['email'] ?? '' }}
                    </div>
                </td>
            </tr>

            <tr>
                <!-- Left column second row: Mailing Address -->
                <td style="vertical-align: top; border-right: 1px solid #000; padding: 4px;">
                    <div style="font-weight: bold; margin-bottom: 2px;">
                        MAILING ADDRESS
                        <span style="margin-left: 12px; font-weight: normal;">
                            <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid #000; margin-right: 3px; text-align: center; line-height: 12px; font-size: 10px;">
                                {{ ($formData['mailing_address'] ?? '') === ($formData['permanent_address'] ?? '') && !empty($formData['permanent_address']) ? '✓' : '' }}
                            </span>
                            <span style="font-size: 8px;">SAME AS ABOVE</span>
                        </span>
                    </div>
                    <div style="font-size: 7px; margin-bottom: 1px;">
                        <span style="display: inline-block; margin-right: 40px;">Unit/Room No./Floor</span>
                        <span style="display: inline-block; margin-right: 40px;">Building Name</span>
                        <span style="display: inline-block; margin-right: 40px;">Lot/Block/Phase/House Number</span>
                        <span style="display: inline-block;">Street Name</span>
                    </div>
                    <div style="border-bottom: 1px solid #000; padding: 2px; font-size: 9px; min-height: 16px;">
                        {{ $formData['mailing_address'] ?? ($formData['permanent_address'] ?? '') }}
                    </div>
                    <div style="margin-top: 3px; font-size: 7px;">
                        <span style="display: inline-block; margin-right: 40px;">Subdivision</span>
                        <span style="display: inline-block; margin-right: 40px;">Barangay</span>
                        <span style="display: inline-block; margin-right: 40px;">Municipality/City</span>
                        <span style="display: inline-block; margin-right: 40px;">Province/State/Country (If abroad)</span>
                        <span style="display: inline-block;">ZIP Code</span>
                    </div>
                    <div style="margin-top: 1px;">
                        <div style="style=margin-top: 1px; text-align: right; margin-right: 45px;">
                            {{ $formData['mailing_zip'] ?? ($formData['permanent_zip'] ?? '') }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Section III: Declaration of Dependents -->
        <div class="section-header" style="margin-top: -17px;">III. DECLARATION OF DEPENDENTS (Use additional form if necessary)</div>

        <table style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
            <thead>
                <tr>
                    <th style="width: 15%; border: 1px solid #000; padding: 4px; font-size: 8px; text-align: center;">LAST NAME</th>
                    <th style="width: 15%; border: 1px solid #000; padding: 4px; font-size: 8px; text-align: center;">FIRST NAME</th>
                    <th style="width: 8%; border: 1px solid #000; padding: 4px; font-size: 8px; text-align: center;">NAME EXTENSION<br>(Jr./Sr./II)</th>
                    <th style="width: 12%; border: 1px solid #000; padding: 4px; font-size: 8px; text-align: center;">MIDDLE NAME</th>
                    <th style="width: 10%; border: 1px solid #000; padding: 4px; font-size: 8px; text-align: center;">RELATIONSHIP</th>
                    <th style="width: 12%; border: 1px solid #000; padding: 4px; font-size: 8px; text-align: center;">DATE OF BIRTH<br>(mm-dd-yyyy)</th>
                    <th style="width: 10%; border: 1px solid #000; padding: 4px; font-size: 8px; text-align: center;">CITIZENSHIP</th>
                    <th style="width: 18%; padding: 0; font-size: 8px; text-align: center; border: 1px solid #000; vertical-align: top;">
                        <table style="width: 100%; border-collapse: collapse; margin: 0; border: none;">
                            <tr>
                                <th style="border-right: 1px solid #000; padding: 4px 2px; font-size: 7px; text-align: center; font-weight: bold; vertical-align: middle;">NO MIDDLE NAME</th>
                                <th style="border-right: 1px solid #000; padding: 4px 2px; font-size: 7px; text-align: center; font-weight: bold; vertical-align: middle;">MONONYM</th>
                                <th style="padding: 4px 2px; font-size: 7px; text-align: center; font-weight: bold; vertical-align: middle;">Check if with Permanent Disability</th>
                            </tr>
                            <tr>
                                <td colspan="3" style="border-top: 1px solid #000; padding: 3px 2px; font-size: 6px; text-align: center; font-weight: normal; vertical-align: middle;">(Check if applicable only)</td>
                            </tr>
                        </table>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 5px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="padding: 0; border: 1px solid #000; vertical-align: middle;">
                        <table style="width: 100%; border-collapse: collapse; margin: 0; border: none;">
                            <tr>
                                <td style="border-right: 1px solid #000; padding: 6px 2px; text-align: center; vertical-align: middle;"><input type="checkbox" style="width: 12px; height: 12px; margin: 0; display: block; margin-left: auto; margin-right: auto;"></td>
                                <td style="border-right: 1px solid #000; padding: 6px 2px; text-align: center; vertical-align: middle;"><input type="checkbox" style="width: 12px; height: 12px; margin: 0; display: block; margin-left: auto; margin-right: auto;"></td>
                                <td style="padding: 6px 2px; text-align: center; vertical-align: middle;"><input type="checkbox" style="width: 12px; height: 12px; margin: 0; display: block; margin-left: auto; margin-right: auto;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 5px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="padding: 0; border: 1px solid #000; vertical-align: middle;">
                        <table style="width: 100%; border-collapse: collapse; margin: 0; border: none;">
                            <tr>
                                <td style="border-right: 1px solid #000; padding: 6px 2px; text-align: center; vertical-align: middle;"><input type="checkbox" style="width: 12px; height: 12px; margin: 0; display: block; margin-left: auto; margin-right: auto;"></td>
                                <td style="border-right: 1px solid #000; padding: 6px 2px; text-align: center; vertical-align: middle;"><input type="checkbox" style="width: 12px; height: 12px; margin: 0; display: block; margin-left: auto; margin-right: auto;"></td>
                                <td style="padding: 6px 2px; text-align: center; vertical-align: middle;"><input type="checkbox" style="width: 12px; height: 12px; margin: 0; display: block; margin-left: auto; margin-right: auto;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 5px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="padding: 0; border: 1px solid #000; vertical-align: middle;">
                        <table style="width: 100%; border-collapse: collapse; margin: 0; border: none;">
                            <tr>
                                <td style="border-right: 1px solid #000; padding: 6px 2px; text-align: center; vertical-align: middle;"><input type="checkbox" style="width: 12px; height: 12px; margin: 0; display: block; margin-left: auto; margin-right: auto;"></td>
                                <td style="border-right: 1px solid #000; padding: 6px 2px; text-align: center; vertical-align: middle;"><input type="checkbox" style="width: 12px; height: 12px; margin: 0; display: block; margin-left: auto; margin-right: auto;"></td>
                                <td style="padding: 6px 2px; text-align: center; vertical-align: middle;"><input type="checkbox" style="width: 12px; height: 12px; margin: 0; display: block; margin-left: auto; margin-right: auto;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 5px;"></td>
                    <td style="border: 1px solid #000; padding: 2px;"><input type="text" style="width: 100%; border: none; font-size: 7px;"></td>
                    <td style="padding: 0; border: 1px solid #000; vertical-align: middle;">
                        <table style="width: 100%; border-collapse: collapse; margin: 0; border: none;">
                            <tr>
                                <td style="border-right: 1px solid #000; padding: 6px 2px; text-align: center; vertical-align: middle;"><input type="checkbox" style="width: 12px; height: 12px; margin: 0; display: block; margin-left: auto; margin-right: auto;"></td>
                                <td style="border-right: 1px solid #000; padding: 6px 2px; text-align: center; vertical-align: middle;"><input type="checkbox" style="width: 12px; height: 12px; margin: 0; display: block; margin-left: auto; margin-right: auto;"></td>
                                <td style="padding: 6px 2px; text-align: center; vertical-align: middle;"><input type="checkbox" style="width: 12px; height: 12px; margin: 0; display: block; margin-left: auto; margin-right: auto;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Section IV: Member Type -->
        <div class="section-header" style="margin-top: -15px;">IV. MEMBER TYPE</div>

        <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 8px;">
            <tr>
                <!-- DIRECT CONTRIBUTOR block (left and middle) -->
                <td style="width: 65%; vertical-align: top; border-right: 1px solid #000; padding: 4px;">
                    <div style="font-weight: bold; text-align: center; margin-bottom: 3px;">DIRECT CONTRIBUTOR</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="vertical-align: top; width: 50%; padding-right: 8px;">
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Employed Private</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Employed Government</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Professional Practitioner</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Self-Earning Individual</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Individual</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Sole Proprietor</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Group Enrollment Scheme</span>
                                </div>
                                <div style="border-bottom: 1px solid #000; margin-top: 4px;"></div>
                            </td>
                            <td style="vertical-align: top; width: 50%; padding-left: 8px;">
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Kasambahay</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Migrant Worker</span>
                                </div>
                                <div style="margin-left: 14px;">
                                    <div class="checkbox-group">
                                        <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                        <span>Land-Based</span>
                                    </div>
                                    <div class="checkbox-group">
                                        <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                        <span>Sea-Based</span>
                                    </div>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Lifetime Member</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Filipinos with Dual Citizenship / Living Abroad</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Foreign National</span>
                                </div>
                                <div style="margin-top: 4px; font-size: 7px;">
                                    PRA SRRV No. _______________________<br>
                                    ACR I-Card No. ______________________
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>

                <!-- INDIRECT CONTRIBUTOR block (right) -->
                <td style="width: 35%; vertical-align: top; padding: 4px;">
                    <div style="font-weight: bold; text-align: center; margin-bottom: 3px;">INDIRECT CONTRIBUTOR</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="vertical-align: top; width: 50%; padding-right: 6px;">
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Listahanan</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>4Ps/MCCT</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Senior Citizen</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>PAMANA</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>KIA/KIPO</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Bangsamoro/Normalization</span>
                                </div>
                            </td>
                            <td style="vertical-align: top; width: 50%; padding-left: 6px;">
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>LGU-sponsored</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>NGA-sponsored</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Private-sponsored</span>
                                </div>
                                <div class="checkbox-group">
                                    <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                                    <span>Person with Disability</span>
                                </div>
                                <div style="margin-left: 14px; font-size: 7px; margin-top: 2px;">
                                    PWD ID No. __________________
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Bottom row: Profession / Monthly Income / Proof of Income / PhilHealth use -->
            <tr>
                <td colspan="2" style="border-top: 1px solid #000; border-right: 1px solid #000; padding: 4px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                        <tr>
                            <td style="width: 40%; border-right: 1px solid #000; padding-right: 4px;">
                                <span style="font-weight: bold;">PROFESSION:</span>
                                <span style="font-size: 7px;"> (Except Employed, Lifetime Members and Sea-based Migrant Worker)</span>
                                <div style="border-bottom: 1px solid #000; margin-top: 2px; min-height: 14px;"></div>
                            </td>
                            <td style="width: 30%; border-right: 1px solid #000; padding: 0 4px;">
                                <span style="font-weight: bold;">MONTHLY INCOME:</span>
                                <div style="border-bottom: 1px solid #000; margin-top: 2px; min-height: 14px;"></div>
                            </td>
                            <td style="width: 30%; padding-left: 4px;">
                                <span style="font-weight: bold;">PROOF OF INCOME:</span>
                                <div style="border-bottom: 1px solid #000; margin-top: 2px; min-height: 14px;"></div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="border-top: 1px solid #000; padding: 4px; vertical-align: top;">
                    <div style="font-weight: bold; margin-bottom: 2px;">For PhilHealth Use only:</div>
                    <div class="checkbox-group">
                        <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                        <span>Point of Service (POS) Financially Incapable</span>
                    </div>
                    <div class="checkbox-group">
                        <span style="display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 3px;"></span>
                        <span>Financially Incapable</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Section V: Updating/Amendment -->
        <div class="section-header">V. UPDATING/AMENDMENT</div>

        <div class="updating-section">
            <div class="checkbox-group">
                <input type="checkbox" id="change_name" name="update_type">
                <label for="change_name">Change/Correction of Name (Last Name, First Name, Name Extension (Jr./Sr./III) Middle Name)</label>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" id="change_dob" name="update_type">
                <label for="change_dob">Correction of Date of Birth</label>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" id="change_sex" name="update_type">
                <label for="change_sex">Correction of Sex</label>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" id="change_civil" name="update_type">
                <label for="change_civil">Change of Civil Status</label>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" id="update_info" name="update_type">
                <label for="update_info">Updating of Personal Information/Address/Telephone Number/Mobile Number/e-mail Address</label>
            </div>

            <div style="font-weight: bold; font-size: 8px; margin-top: 10px; margin-bottom: 5px;">Please check:</div>
            
            <div class="from-to-section">
                <div>
                    <div style="font-weight: bold; font-size: 8px; margin-bottom: 3px;">FROM</div>
                    <input type="text" name="from_value" style="width: 100%; border: 1px solid #000; padding: 5px; min-height: 40px;">
                </div>
                <div>
                    <div style="font-weight: bold; font-size: 8px; margin-bottom: 3px;">TO</div>
                    <input type="text" name="to_value" style="width: 100%; border: 1px solid #000; padding: 5px; min-height: 40px;">
                </div>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-text">
                <strong>Under penalty of law, I hereby attest that the information provided, including the documents I have attached to this form, are true and accurate to the best of my knowledge.</strong> I agree and authorize PhilHealth for the subsequent validation, verification and for other data sharing purposes only under the following circumstances:
                <ul style="margin-left: 20px; margin-top: 5px;">
                    <li>As necessary for the proper execution of processes related to the legitimate and declared purpose;</li>
                    <li>The use or disclosure is reasonably necessary, required or authorized by or under the law; and,</li>
                    <li>Adequate security measures are employed to protect my information.</li>
                </ul>
            </div>

            <div class="signature-box">
                <div class="signature-left">
                    <div style="font-size: 7px; margin-bottom: 5px;">Please affix right thumbmark if unable to write</div>
                    <div class="signature-line">
                        Member's Signature over Printed Name
                    </div>
                    <div style="margin-top: 20px; font-size: 8px;">
                        <label>Date: </label>
                        <input type="text" style="border: none; border-bottom: 1px solid #000; width: 150px;">
                    </div>
                </div>

                <div class="signature-right">
                    <div style="font-weight: bold; font-size: 8px; margin-bottom: 10px;">For PhilHealth Use only:</div>
                    <div style="font-weight: bold; font-size: 9px; margin-bottom: 5px;">RECEIVED BY:</div>
                    <div style="margin-bottom: 5px;">
                        <label style="font-size: 7px;">Full Name:</label>
                        <input type="text" style="width: 100%; border: none; border-bottom: 1px solid #000; padding: 2px;">
                    </div>
                    <div style="margin-bottom: 5px;">
                        <label style="font-size: 7px;">PRO/LHIO/Branch:</label>
                        <input type="text" style="width: 100%; border: none; border-bottom: 1px solid #000; padding: 2px;">
                    </div>
                    <div>
                        <label style="font-size: 7px;">Date & Time:</label>
                        <input type="text" style="width: 100%; border: none; border-bottom: 1px solid #000; padding: 2px;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructions Section -->
        <div class="instructions-section">
            <h3>INSTRUCTIONS</h3>
            <ol>
                <li>All information should be written in UPPER CASE/CAPITAL LETTERS. If the information is not applicable, write "N/A."</li>
                <li>All fields are mandatory unless indicated as optional. By affixing your signature, you certify the truthfulness and accuracy of all information provided.</li>
                <li>A properly accomplished PMRF shall be accompanied by a valid proof of identity for first time registrants, and supporting documents to establish relationship between member and dependent/s for updating or request for amendment.</li>
                <li>On the PURPOSE, check the appropriate box if for Registration or for Updating/Amendment of information.</li>
                <li>Indicate preferred KonSulTa provider near the place of work or residence.</li>
                <li>For PERSONAL DETAILS, all name entries should follow the format given below. Check the appropriate box if registrant has no middle name and/or with single name (mononym).</li>
            </ol>

            <div class="name-format-example">
                <div>
                    <div class="label">LAST NAME</div>
                    <div>SANTOS</div>
                </div>
                <div></div>
                    <div class="label">FIRST NAME</div>
                    <div>JUAN</div>
                </div>
                <div></div>
                    <div class="label">NAME EXTENSION (Jr./Sr./III)</div>
                    <div>III</div>
                </div>
                <div>
                    <div class="label">MIDDLE NAME</div>
                    <div>DELA CRUZ</div>
                </div>
            </div>

            <ol start="7">
                <li>Indicate registrant's/member's name as it appears in the birth certificate.</li>
                <li>The full mother's maiden name of registrant/member must be indicated as it appears in the birth certificate.</li>
                <li>Indicate the full name of spouse if registrant/member is married.</li>
                <li>Indicate the complete permanent and mailing addresses and contact numbers.</li>
                <li>For updating/amendment, check the appropriate box to be updated/amended and indicate the correct data.</li>
                <li>For MEMBER TYPE, check the appropriate box which best describes your current membership status.</li>
                <li>For Direct Contributors, except employed, sea-based migrant workers and lifetime members, indicate the profession, monthly income and proof of income to be submitted.</li>
                <li>For Self-earning individuals, Kasambahays and Family Drivers, indicate the actual monthly income in the space provided.</li>
                <li>In declaring dependents, provide the full name of the living spouse, children below 21 years old, and parents who are 60 years old and above totally dependent to the member.</li>
                <li>Dependents with disability shall be registered as principal members in accordance with Republic Act 11228 on mandatory PhilHealth coverage for all persons with disability (PWD).</li>
                <li>The registrant must affix his/her signature over printed name (or right thumbmark if unable to write) and indicate the date when the PMRF was signed.</li>
            </ol>
        </div>
    </div>
</body>
</html>