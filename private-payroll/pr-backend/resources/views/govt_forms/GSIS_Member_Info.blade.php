<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>GSIS Member Information Sheet</title>
    <style>
        @page {
            margin: 0;
            size: letter;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.1;
            color: #000;
        }

        .content-wrapper {
            margin: 1in;
            padding: 0;
            position: relative;
        }

        .form-number {
            position: absolute;
            top: 0;
            left: 0;
            font-size: 8pt;
            height: 15px;
        }

        .header-container {
            position: relative;
            margin-bottom: 4px;
            padding-top: 90px;
        }

        .logo-container {
            position: absolute;
            left: 0;
            top: 18px;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7pt;
            color: #666;
            text-align: center;
            background-color: #ffffff;
        }

        .logo-placeholder {
            padding: 2px;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }

        .logo-placeholder img {
            width: 75px;
            height: 70px;
            position: absolute;
            bottom: 2px;
            right: 1px;
            border-radius: 5px;
            object-fit: contain;
        }

        .header-text {
            position: absolute;
            left: 78px;
            top: 18px;
            padding-right: 110px;
        }

        .org-name {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 0px;
            line-height: 1.2;
        }

        .org-name-en {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 0px;
            line-height: 1.2;
        }

        .org-address {
            font-size: 8pt;
            margin-bottom: 0px;
            line-height: 1.2;
        }

        .form-title {
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }

        .photo-box {
            position: absolute;
            right: 0;
            top: 18px;
            width: 100px;
            height: 90px;
            border: 1px solid #000;
            text-align: center;
            font-size: 7pt;
            line-height: 1.2;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .section-border {
            height: 4px;
            background-color: #000000;
            margin-top: 3px;
            margin-bottom: 2px;
            width: 100%;
        }

        .section-header {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 2px;
        }

        .field-row {
            margin-bottom: 3px;
            position: relative;
        }

        .field-label {
            font-size: 8pt;
            display: inline;
        }

        .underline {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-height: 12px;
            padding: 0 3px 1px 3px;
            vertical-align: bottom;
        }

        .field-hint {
            font-size: 7pt;
            color: #000;
            margin-top: 1px;
            margin-bottom: 1px;
        }

        .name-section {
            margin-top: 4px;
            margin-bottom: 4px;
        }

        .name-table {
            width: 100%;
            border-collapse: collapse;
        }

        .name-table .name-label-cell {
            font-size: 8pt;
            vertical-align: bottom;
            white-space: nowrap;
            padding: 6px 4px 2px 0;
        }

        .name-table .name-part-cell {
            text-align: center;
            vertical-align: bottom;
            font-size: 8pt;
            line-height: 1.2;
            padding: 0 4px 2px 4px;
            border-bottom: 1px solid #000;
        }

        .name-table .name-hint-cell {
            font-size: 7pt;
            font-style: italic;
            text-align: center;
            line-height: 1.1;
            padding: 2px 4px 0 4px;
        }


        .name-row {
            margin-bottom: 2px;
        }

        .name-hints {
            font-size: 7pt;
            margin-left: 50px;
            margin-top: 1px;
            margin-bottom: 2px;
        }

        .name-hint {
            display: inline-block;
            text-align: center;
        }

        .address-hint {
            font-size: 7pt;
            text-align: center;
            margin-top: 1px;
            margin-bottom: 1px;
        }

        .date-hint {
            font-size: 7pt;
            margin-left: 85px;
            margin-top: 1px;
            margin-bottom: 1px;
        }

        .border-line {
            height: 2px;
            background-color: #000000;
            margin: 2px 0;
            width: 100%;
        }

        .double-line-section {
            padding: 1px 0;
            margin: 0;
        }

        .signature-section {
            margin-top: 10px;
        }

        .signature-row {
            margin-bottom: 8px;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            height: 25px;
            margin-bottom: 2px;
            width: 320px;
        }

        .signature-label {
            font-size: 8pt;
        }

        .attested-label {
            font-size: 8pt;
            margin-bottom: 3px;
        }

        /* Width utilities */
        .w-50 { width: 50px; }
        .w-60 { width: 60px; }
        .w-70 { width: 70px; }
        .w-80 { width: 80px; }
        .w-100 { width: 100px; }
        .w-120 { width: 120px; }
        .w-150 { width: 150px; }
        .w-180 { width: 180px; }
        .w-200 { width: 200px; }
        .w-250 { width: 250px; }
        .w-300 { width: 300px; }
        .w-350 { width: 350px; }
        .w-400 { width: 400px; }
        .w-full { width: 100%; }

        .ml-10 { margin-left: 10px; }
        .ml-20 { margin-left: 20px; }

        .connected-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            column-gap: 10px;
            margin-bottom: 4px;
        }

        .connected-line {
            display: grid;
            border-bottom: 1px solid #000;
            min-height: 14px;
            padding: 0 3px 1px 3px;
        }
        .connected-line span {
            padding: 0 6px;
        }

        .connected-hints {
            display: grid;
            font-size: 7pt;
            text-align: center;
            margin-top: 1px;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
    <div class="header-container">
        <div class="form-number">Form No. MIS-05-02</div>

        <div class="logo-container">
            <div class="logo-placeholder">
                @if(isset($logoImage) && !empty($logoImage))
                    <img src="data:image/png;base64,{{ $logoImage }}" alt="GSIS Logo">
                @endif
            </div>
        </div>

        <div class="header-text">
            <div class="org-name">PASEGURUHAN NG MGA NAGLILINGKOD SA PAMAHALAAN</div>
            <div class="org-name-en">(Government Service Insurance System)</div>
            <div class="org-address">Financial Center, Roxas Boulevard, Pasay City</div>
        </div>

        <div class="photo-box">
            <strong>ID Picture</strong><br>(Taken within the<br>last 3 months)
        </div>

        <div class="form-title">MEMBERSHIP INFORMATION SHEET</div>
    </div>

    <div class="section-border"></div>
    <div class="section-header">PERSONAL DATA:</div>

    {{-- <div class="name-section">
        <div class="name-row">
            <span class="field-label">Name: </span>
            <span class="underline" style="width: 220px; display: inline-block;">{{ $last_name ?? '' }}</span>
            <span class="underline ml-10" style="width: 220px; display: inline-block;">{{ $first_name ?? '' }}</span>
            <span class="underline ml-10" style="width: 220px; display: inline-block;">{{ $middle_name ?? '' }}</span>
        </div>
        <div class="name-hints">
            <span class="name-hint" style="width: 220px;">Last Name</span>
            <span class="name-hint ml-10" style="width: 220px;">First Name</span>
            <span class="name-hint ml-10" style="width: 220px;">Middle Name</span>
        </div>
    </div> --}}

    <div class="name-section">
        <table class="name-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="name-label-cell" width="1%" nowrap="nowrap" style="padding-top: 6px;">Name:</td>
                <td class="name-part-cell" width="33%">{{ $last_name ?? '' }}</td>
                <td class="name-part-cell" width="33%">{{ $first_name ?? '' }}</td>
                <td class="name-part-cell" width="33%">{{ $middle_name ?? '' }}</td>
            </tr>
            <tr>
                <td width="1%" nowrap="nowrap">&nbsp;</td>
                <td class="name-hint-cell" width="33%">Last name</td>
                <td class="name-hint-cell" width="33%">First Name</td>
                <td class="name-hint-cell" width="33%">Middle Name</td>
            </tr>
        </table>
    </div>


    <div class="field-row">
        <span class="field-label">Sex: </span>
        <span class="underline w-120" style="text-align: center;">{{ $sex ?? '' }}</span>
        <span class="field-label ml-20">Civil Status: </span>
        <span class="underline w-150" style="text-align: center;">{{ $civil_status ?? '' }}</span>
        <span class="field-label ml-20">TIN: </span>
        <span class="underline w-150" style="text-align: center;">{{ $tin ?? '' }}</span>
    </div>

    <div class="field-row">
        <span class="field-label">Date of Birth: </span>
        <span class="underline w-150" style="text-align: center;">{{ $date_of_birth ?? '' }}</span>
        <span class="field-label ml-20">Place of Birth: </span>
        <span class="underline w-250" style="text-align: left;">{{ $birth_town ?? '' }} {{ $birth_city_province ?? '' }}</span>
    </div>

    <div class="date-hint">
        <span style="display: inline-block; width: 150px; text-align: center;">(Month/Day/Year)</span>
        <span style="display: inline-block; width: 200px; text-align: center; margin-left: 20px;">Town/District</span>
        <span style="display: inline-block; width: 150px; text-align: center; margin-left: 10px;">City/Province</span>
    </div>

    <div class="field-row">
        <span class="field-label">Residence/Mailing Address:</span>
    </div>
    {{-- <div class="field-row">
        <span class="underline w-full">{{ $address_line1 ?? '' }}</span>
    </div>
    <div class="address-hint">House, Apt. or Bldg No./St. Name</div> --}}

    {{-- <div class="field-row">
        <span class="underline w-200">{{ $barangay ?? '' }}</span>
        <span class="underline w-200 ml-10">{{ $city ?? '' }}</span>
        <span class="underline w-200 ml-10">{{ $province ?? '' }}</span>
        <span class="underline w-100 ml-10">{{ $zip_code ?? '' }}</span>
    </div>
    <div class="field-hint">
        <span style="display: inline-block; width: 200px; text-align: center;">Barangay or Barrio</span>
        <span style="display: inline-block; width: 200px; text-align: center; margin-left: 10px;">Town/City</span>
        <span style="display: inline-block; width: 200px; text-align: center; margin-left: 10px;">Province</span>
        <span style="display: inline-block; width: 100px; text-align: center; margin-left: 10px;">Zip Code</span>
    </div> --}}

    <div class="field-row">
        <div style="border-bottom: 1px solid #000; min-height: 14px; padding: 0 3px 1px 3px; display: grid; grid-template-columns: 1fr 1fr 1fr auto; column-gap: 20px;">
            <span>{{ $address_line1 ?? '' }}</span>
            <span>{{ $barangay ?? '' }}</span>
            <span>{{ $city ?? '' }}</span>
            <span>{{ $province ?? '' }}</span>
            <span style="width: 100px;">{{ $zip_code ?? '' }}</span>
        </div>
    </div>
    <div class="field-hint">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; text-align: center;">
            <span style="margin-right: 55px;">House, Apt. or Bldg No./St. Name</span>
            <span style="margin-right: 55px;">Barangay or Barrio</span>
            <span style="margin-right: 55px;">Town/City</span>
            <span style="margin-left: 35px;">Province</span>
            <span style="margin-left: 55px;">Zip Code</span>
        </div>
    </div>

    <div class="section-border"></div>
    <div class="section-header">EMPLOYMENT DATA:</div>

    <div class="field-row">
        <span class="field-label">Office: </span>
        <span class="underline w-250">{{ $office ?? '' }}</span>
        <span class="field-label ml-20">Date of Original Appointment: </span>
        <span class="underline w-150" style="text-align: center;">{{ $date_of_appointment ?? '' }}</span>
    </div>
    <div class="date-hint" style="margin-left: 520px;">
        (Month/Day/Year)
    </div>

    <div class="field-row">
        <span class="field-label">Office Address:</span>
    </div>
    <div class="field-row">
        <div style="border-bottom: 1px solid #000; min-height: 14px; padding: 0 3px 1px 3px; display: grid; grid-template-columns: 1fr 1fr 1fr auto; column-gap: 20px;">
            <span>{{ $office_address_no ?? '' }}</span>
            <span>{{ $office_address_street ?? '' }}</span>
            <span>{{ $office_address_city ?? '' }}</span>
            <span>{{ $office_address_province ?? '' }}</span>
        </div>
    </div>
    <div class="field-hint">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; text-align: center;">
            <span style="margin-right: 55px;">No.</span>
            <span style="margin-right: 55px;">Street</span>
            <span style="margin-right: 55px;">Town/City</span>
            <span style="margin-left: 35px;">Province</span>
        </div>
    </div>

    <div class="field-row">
        <span class="field-label">Position Title: </span>
        <span class="underline w-250" style="text-align: left;">{{ $position_title ?? '' }}</span>
        <span class="field-label ml-20">Status of Appointment: </span>
        <span class="underline w-150" style="text-align: center;">{{ $status_of_appointment ?? '' }}</span>
    </div>

    <div class="field-row">
        <span class="field-label">Present Salary: </span>
        <span class="underline w-150">{{ $present_salary ?? '' }}</span>
        <span class="field-label ml-20">Date of Effectivity of Present Salary: </span>
        <span class="underline w-150" style="text-align: center;">{{ $salary_effectivity_date ?? '' }}</span>
    </div>
    <div class="date-hint" style="margin-left: 450px;">
        (Month/Day/Year)
    </div>

    <div class="border-line"></div>
    <div class="double-line-section">
        <div class="field-row">
            <span class="field-label" style="font-style: italic;">For DEPED Employees only:</span>
            <span class="field-label ml-10">Division No.: </span>
            <span class="underline w-50">{{ $division_no ?? '' }}</span>
            <span class="field-label ml-10">Station No.: </span>
            <span class="underline w-80">{{ $station_no ?? '' }}</span>
            <span class="field-label ml-10">Employee No.: </span>
            <span class="underline w-80">{{ $employee_no ?? '' }}</span>
        </div>
    </div>
    <div class="border-line"></div>

    <div class="border-line"></div>
    <div class="double-line-section">
        <div class="field-row">
            <span class="field-label">Home Tel. No.: </span>
            <span class="underline w-200">{{ $home_tel ?? '' }}</span>
            <span class="field-label ml-20">Cellphone No.: </span>
            <span class="underline w-200">{{ $cellphone ?? '' }}</span>
        </div>

        <div class="field-row">
            <span class="field-label">Office Tel. No.: </span>
            <span class="underline w-200">{{ $office_tel ?? '' }}</span>
            <span class="field-label ml-20">eMail Address: </span>
            <span class="underline w-200">{{ $email ?? '' }}</span>
        </div>
    </div>
    <div class="border-line"></div>

    <div class="signature-section">
        <div class="signature-row">
            <div class="signature-line"></div>
            <div class="signature-label">Signature of Member</div>
        </div>

        <div class="signature-row">
            <div class="attested-label">Attested:</div>
            <div class="signature-line"></div>
            <div class="signature-label">Signature over Printed Name of Personnel/Administrative Officer</div>
        </div>
    </div>
    </div>
</body>
</html>
