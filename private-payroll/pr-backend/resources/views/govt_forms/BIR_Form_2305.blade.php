<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIR Form 2305</title>
    <style>
        @page {
            size: 8.5in 13in;
            margin: 0.15in;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 7.5pt;
            line-height: 1.0;
            color: #000;
            background: white;
            padding-top: 10px;
        }

        .form-container {
            width: 100%;
            max-width: 8in;
            margin: 20px auto 0 auto;
            border: 2px solid #000;
            background: white;
        }

        /* Header Section */
        .form-header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #000;
        }

        .header-cell {
            display: table-cell;
            vertical-align: middle;
            padding: 3px;
        }

        .header-cell:last-child {
            border-right: none;
        }

        .bir-logo {
            width: 60px;
            text-align: center;
        }

        .logo-box {
            width: 40px;
            height: 40px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: white;
        }

        .logo-box img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .header-text {
            font-size: 9pt;
            line-height: 1.3;
            width: 35%;
            font-weight: normal;
        }

        .form-title {
            text-align: left;
            font-size: 13pt;
            font-weight: normal;
            width: 65%;
        }

        .form-number {
            text-align: center;
            font-size: 22pt;
            font-weight: bold;
            width: 100px;
        }

        .form-number small {
            display: block;
            font-size: 6pt;
            font-weight: normal;
            margin-top: 2px;
        }

        /* Info Bar */
        .info-bar {
            display: table;
            width: 100%;
            border-bottom: 1px solid #000;
            font-size: 7pt;
        }

        .info-cell {
            display: table-cell;
            padding: 2px 4px;
            vertical-align: middle;
        }

        .info-cell.dln {
            width: 70%;
            border-right: 1px solid #000;
        }

        .info-cell.stamp {
            text-align: center;
            font-size: 6.5pt;
        }

        /* Instructions */
        .instructions {
            padding: 2px 4px;
            border-bottom: 1px solid #000;
            font-size: 6.5pt;
            font-style: italic;
        }

        /* Part Headers */
        .part-header {
            background-color: #000;
            color: white;
            padding: 2px 5px;
            font-weight: bold;
            font-size: 6.5pt;
            border-bottom: 1px solid #000;
        }

        /* Row System */
        .form-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #000;
        }

        .form-cell {
            display: table-cell;
            border-right: 1px solid #000;
            padding: 0;
            position: relative;
            vertical-align: top;
        }

        .form-cell:last-child {
            border-right: none;
        }

        /* Field Styling */
        .field-number {
            position: absolute;
            top: 2px;
            left: 3px;
            font-weight: bold;
            font-size: 7pt;
        }

        .field-label {
            position: absolute;
            top: 2px;
            left: 15px;
            font-size: 6pt;
        }

        .field-input {
            padding: 8px 4px 2px 4px;
            min-height: 14px;
            margin-top: 0;
            line-height: 1.1;
        }

        .data-table tr {
            height: 20px;
        }

        .data-table td {
            vertical-align: middle;
            padding: 1px 2px;
            line-height: 1.1;
        }

        .data-table table td {
            height: 18px;
            padding: 0;
        }

        .data-table tbody tr {
            height: 22px;
            max-height: 22px;
        }

        /* Checkbox */
        .checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            margin-right: 3px;
            vertical-align: middle;
            text-align: center;
            line-height: 10px;
            font-size: 8pt;
        }

        /* TIN Boxes */
        .tin-container {
            display: block;
            padding-top: 2px;
            white-space: nowrap;
            font-size: 0;
        }

        .tin-box {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            line-height: 18px;
            font-size: 8pt;
            margin-right: 1px;
        }

        .tin-box:last-child {
            margin-right: 0;
        }

        /* Address Grid */
        .address-grid {
            display: table;
            width: 100%;
            border: none;
        }

        .address-col {
            display: table-cell;
            padding: 1px;
            text-align: center;
            font-size: 5.5pt;
        }

        .address-col:last-child {
            border-right: none;
        }

        /* Table for Children/Employers */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            font-size: 6.5pt;
        }

        .data-table th {
            background-color: #d0d0d0;
            font-weight: bold;
            text-align: center;
            font-size: 6pt;
        }

        /* Signature Section */
        .signature-row {
            display: flex;
            width: 100%;
            justify-content: space-between;
            padding: 10px 0;
        }

        .signature-cell {
            width: 48%;
            padding: 4px;
            border-right: 1px solid #000;
            vertical-align: middle;
            text-align: center;
        }

        .signature-cell:last-child {
            border-right: none;
        }

        .sig-line {
            border-top: 1px solid #000;
            margin-top: 20px;
            padding-top: 2px;
            text-align: center;
            font-size: 6.5pt;
        }

        .date-line {
            margin-top: 8px;
            font-size: 7pt;
        }

        /* Declaration Text */
        .declaration {
            padding: 3px 4px;
            font-size: 6pt;
            font-style: italic;
            line-height: 1.2;
            text-align: center;
        }

        /* Section Title */
        .section-title {
            padding: 2px 4px;
            font-weight: bold;
            font-size: 6.5pt;
            border-bottom: 1px solid #000;
        }

        .signature-number {
            float: left;
            margin-right: 13px;
            margin-top: 10px;
            font-size: 7pt;
            font-weight: bold;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .form-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- DLN Field - Outside Form Border -->
    <div style="width: 100%; max-width: 8in; font-size: 7pt; position: relative; left: 20px; top: 15px;">
        To be filled-up by BIR&nbsp;&nbsp;&nbsp;<strong>DLN:</strong> ________________
    </div>
    
    <div class="form-container">
        
        <!-- HEADER -->
        <div class="form-header">
            <div class="header-cell bir-logo">
                <div class="logo-box">
                    @if(isset($birLogo) && !empty($birLogo))
                        <img src="data:image/png;base64,{{ $birLogo }}" alt="BIR Logo" style="max-width: 38px; max-height: 38px;">
                    @endif
                </div>
            </div>
            <div class="header-cell header-text">
                <strong>Republika ng Pilipinas</strong><br>
                <strong>Kagawaran ng Pananalapi</strong><br>
                <strong>Kawanihan ng Rentas Internas</strong>
            </div>
            <div class="header-cell form-title">
                Certificate of Update of<br>
                Exemption and of Employer's<br>
                and Employee's Information
            </div>
            <div class="header-cell form-number">
                2305
                <small>BIR Form No.<br>July 2008 (ENCS)</small>
            </div>
        </div>

        <!-- INSTRUCTIONS -->
        <div class="instructions">
            Fill in all applicable spaces. Mark all appropriate boxes with an "X".
        </div>

        <!-- PART I: TAXPAYER/EMPLOYEE INFORMATION -->
        <div class="part-header">PART I&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;TAXPAYER / EMPLOYEE INFORMATION</div>

        <!-- Row 1: Type of Filer & Effective Date -->
        <div class="form-row">
            <div class="form-cell" style="width: 65%;">
                <span class="field-number">1</span>
                <span class="field-label">Type of Filer</span>
                <div class="field-input" style="margin-top: 10px;">
                    <label><span class="checkbox">{{ isset($type_employee) && $type_employee ? 'X' : '' }}</span> Employee (for update of "Exemption" and other employer's and employee's information)</label><br>
                    <label><span class="checkbox">{{ isset($type_self_employed) && $type_self_employed ? 'X' : '' }}</span> Self-employed (for update of "Exemption")</label>
                </div>
            </div>
            <div class="form-cell" style="width: 35%;">
                <span class="field-number">2</span>
                <span class="field-label">Effective Date</span>
                <div class="field-input" style="text-align: center;">
                    {{ $effective_date ?? '' }}<br>
                    <span style="font-size: 6pt;">(MM/ DD/ YYYY)</span>
                </div>
            </div>
        </div>

        <!-- Row 2: TIN, RDO, Sex -->
        <div class="form-row">
            <div class="form-cell" style="width: 50%;">
                <span class="field-number">3</span>
                <span class="field-label">TIN</span>
                <div class="field-input" style="margin-top: 10px;">
                    <div class="tin-container">
                        @php
                            $tin = str_pad($employee_tin ?? '', 15, ' ', STR_PAD_RIGHT);
                            $tin_arr = str_split(str_replace('-', '', $tin));
                        @endphp
                        @for($i = 0; $i < 15; $i++)
                            @if($i == 3 || $i == 6 || $i == 9)
                                <div class="tin-box">-</div>
                            @endif
                            <div class="tin-box">{{ $tin_arr[$i] ?? '' }}</div>
                        @endfor
                    </div>
                </div>
            </div>
            <div class="form-cell" style="width: 25%;">
                <span class="field-number">4</span>
                <span class="field-label">RDO Code</span>
                <div class="field-input">{{ $rdo_code ?? '' }}</div>
            </div>
            <div class="form-cell" style="width: 25%;">
                <span class="field-number">5</span>
                <span class="field-label">Sex</span>
                <div class="field-input" style="margin-top: 10px;">
                    <label><span class="checkbox">{{ isset($sex) && $sex == 'M' ? 'X' : '' }}</span> Male</label>
                    <label><span class="checkbox">{{ isset($sex) && $sex == 'F' ? 'X' : '' }}</span> Female</label>
                </div>
            </div>
        </div>

        <!-- Row 3: Name & Birth Date -->
        <div class="form-row">
            <div class="form-cell" style="width: 70%;">
                <span class="field-number">6</span>
                <span class="field-label">Taxpayer's Name (Last Name, First Name, Middle Name)</span>
                <div class="field-input"style="margin-top: 10px;">{{ $employee_name ?? '' }}</div>
            </div>
            <div class="form-cell" style="width: 30%;">
                <span class="field-number">6A</span>
                <span class="field-label">Date of Birth</span>
                <div class="field-input" style="text-align: center;">
                    {{ $employee_birthdate ?? '' }}<br>
                    <span style="font-size: 6pt;">(MM/ DD/ YYYY)</span>
                </div>
            </div>
        </div>

        <!-- Row 4: Residence Address (7A & 7B) -->
        <div class="form-row">
            <div class="form-cell" style="width: 70%;">
                <span class="field-number">7</span>
                <span class="field-label">Residence Address</span>
                <span class="field-number">7A</span>
                <div class="field-input" style="margin-top: 10px;">{{ $employee_address ?? '' }}</div>
            </div>
            <div class="form-cell" style="width: 15%;">
                <span class="field-number">7B</span>
                <span class="field-label">Zip Code</span>
                <div class="field-input" style="text-align: center;">{{ $ra_postal_id ?? '' }}</div>
            </div>
        </div>

        <!-- Row 5: Business Address (7C, 7D & 7E) -->
        <div class="form-row">
            <div class="form-cell" style="width: 70%;">
                <span class="field-number">7C</span>
                <span class="field-label">Business Address (for Self-Employed)</span>
                <div class="field-input">{{ $business_address ?? '' }}</div>
            </div>
            <div class="form-cell" style="width: 15%;">
                <span class="field-number">7D</span>
                <span class="field-label">Zip Code</span>
                <div class="field-input" style="text-align: center;">{{ $business_zip ?? '' }}</div>
            </div>
        </div>

        <!-- Declaration -->
        <div class="declaration">
            I declare, under the penalties of perjury, that this certificate has been made in good faith, verified by me, and to the best of my knowledge and belief,
            is true and correct, pursuant to the National Internal Revenue Code, as amended, and the regulations issued under authority thereof.
        </div>

        <!-- Row 8: Signature -->
        <div class="form-row">
            <div class="form-cell" style="text-align: center;">
                <div class="field-input" style="min-height: 30px; width: 50%; display: inline-block;">
                    <span class="signature-number">8</span>
                   <div class="sig-line">Taxpayer/Authorized Agent Signature over Printed Name</div>
                </div>
            </div>
        </div>

        <!-- PART II: PERSONAL EXEMPTIONS -->
        <div class="part-header">PART II&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PERSONAL EXEMPTIONS</div>

        <!-- Row 9-10: Civil Status & Employment Status -->
        <div class="form-row" style="background-color: #f4f4f4; border-bottom: 1px solid #fff;">
            <div class="form-cell" style="width: 50%; border-right: 1px solid #fff;"> <!-- Added border-right -->
                <span class="field-number">9</span>
                <span class="field-label">Civil Status</span>
                <div class="field-input" style="margin-top: 10px;">
                    <label><span class="checkbox">{{ isset($civil_status) && $civil_status == 'S' ? 'X' : '' }}</span> Single</label>&nbsp;&nbsp;
                    <label><span class="checkbox">{{ isset($civil_status) && $civil_status == 'W' ? 'X' : '' }}</span> Widow/Widower</label><br>
                    <label><span class="checkbox">{{ isset($civil_status) && $civil_status == 'LS' ? 'X' : '' }}</span> Legally separated</label>&nbsp;&nbsp;
                    <label><span class="checkbox">{{ isset($civil_status) && $civil_status == 'M' ? 'X' : '' }}</span> Married</label>
                    <br>
                    <br>
                    <label><span class="checkbox">{{ isset($with_children) && $with_children ? 'X' : '' }}</span> with qualified dependent child/ren</label>&nbsp;&nbsp;
                    <label><span class="checkbox">{{ isset($without_children) && $without_children ? 'X' : '' }}</span> without qualified dependent child/ren</label>
                </div>
            </div>
            <div class="form-cell" style="width: 50%; border-top: 1px solid #fff"> <!-- Removed border-right -->
                <span class="field-number">10</span>
                <span class="field-label">Employment Status of Spouse:</span>
                <div class="field-input" style="font-size: 7pt; margin-top: 10px;">
                    <label><span class="checkbox">{{ isset($spouse_employment) && $spouse_employment == 'U' ? 'X' : '' }}</span> Unemployed</label><br>
                    <label><span class="checkbox">{{ isset($spouse_employment) && $spouse_employment == 'EL' ? 'X' : '' }}</span> Employed Locally</label><br>
                    <label><span class="checkbox">{{ isset($spouse_employment) && $spouse_employment == 'EA' ? 'X' : '' }}</span> Employed Abroad</label><br>
                    <label><span class="checkbox">{{ isset($spouse_employment) && $spouse_employment == 'BP' ? 'X' : '' }}</span> Engaged in Business/Practice of Profession</label>
                </div>
            </div>
        </div>

        <!-- Row 11: Additional Exemptions (without separating borders) -->
        <div class="form-row" style="background-color: #f4f4f4; border-bottom: 1px solid #fff;">
            <div class="form-cell" style="width: 100%;"> <!-- Takes up full width -->
                <span class="field-number">11</span>
                <span class="field-label">Claims for Additional Exemptions / Premium Deductions for husband and wife whose aggregate family income does not exceed P250,000.00 per annum.</span>
                <div class="field-input" style="padding-top: 8px; margin-top: 10px;">
                    <label><span class="checkbox">{{ isset($husband_claims) && $husband_claims ? 'X' : '' }}</span> Husband claims additional exemption and premium deductions</label>&nbsp;&nbsp;
                    <label><span class="checkbox">{{ isset($wife_claims) && $wife_claims ? 'X' : '' }}</span> Wife claims additional exemption and premium deductions (Attach Waiver of the Husband)</label>
                </div>
            </div>
        </div>


        <!-- Row 12: Spouse Information Header -->
        <div class="form-row">
            <div class="form-cell">
                <span class="field-number">12</span>
                <span class="field-label">Spouse Information</span>
            </div>
        </div>

        <!-- Row 12A: Spouse TIN -->
        <div class="form-row" style="margin-top: 15px;">
            <div class="form-cell">
                <span class="field-number">12A</span>
                <span class="field-label" style="margin-left: 18px;">Spouse Taxpayer Identification Number</span>
                <div class="field-input">
                    <div class="tin-container" style="margin-left: 27px; margin-top: 10px;">
                        @php
                            $spouse_tin = str_pad($spouse_tin ?? '', 15, ' ', STR_PAD_RIGHT);
                            $spouse_tin_arr = str_split(str_replace('-', '', $spouse_tin));
                        @endphp
                        @for($i = 0; $i < 15; $i++)
                            @if($i == 3 || $i == 6 || $i == 9)
                                <div class="tin-box">-</div>
                            @endif
                            <div class="tin-box">{{ $spouse_tin_arr[$i] ?? '' }}</div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 12B: Spouse Name -->
        <div class="form-row">
            <div class="form-cell">
                <span class="field-number">12B</span>
                <span class="field-label" style="margin-left: 18px;">Spouse Name (if wife, indicate maiden name) (Last Name, First Name, Middle Name)</span>
                <div class="field-input" style="padding-top: 10px;">
                    <div class="address-grid" style="margin-left: 27px;">
                        <div class="address-col">
                            <span style="font-size: 7pt;">{{ $spouse_last_name ?? '' }}</span>
                        </div>
                        <div class="address-col">
                            <span style="font-size: 7pt;">{{ $spouse_first_name ?? '' }}</span>
                        </div>
                        <div class="address-col">
                            <span style="font-size: 7pt;">{{ $spouse_middle_name ?? '' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 12C: Spouse Employer -->
        <div class="form-row">
            <div class="form-cell" style="width: 40%;">
                <span class="field-number">12C</span>
                <span class="field-label" style="margin-left: 18px;">Spouse Employer's Taxpayer Identification Number</span>
                <div class="field-input" style="margin-top: 10px;">
                    <div class="tin-container">
                        @php
                            $spouse_emp_tin = str_pad($spouse_employer_tin ?? '', 11, ' ', STR_PAD_RIGHT);
                            $spouse_emp_tin_arr = str_split(str_replace('-', '', $spouse_emp_tin));
                        @endphp
                        @for($i = 0; $i < 11; $i++)
                            @if($i == 3 || $i == 6)
                                <div class="tin-box">-</div>
                            @endif
                            <div class="tin-box">{{ $spouse_emp_tin_arr[$i] ?? '' }}</div>
                        @endfor
                    </div>
                </div>
            </div>
            <div class="form-cell" style="width: 60%;">
                <span class="field-label">Spouse Employer's Name</span>
                <div class="field-input">{{ $spouse_employer_name ?? '' }}</div>
            </div>
        </div>

        <!-- PART III: ADDITIONAL EXEMPTIONS -->
        <div class="part-header">PART III&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ADDITIONAL EXEMPTIONS</div>

        <!-- Row 13: Description -->
        <div class="form-row">
            <div class="form-cell">
                <span class="field-number">13</span>
                <div class="field-input" style="padding: 2px; font-size: 6pt; line-height: 1.1; text-align: center; margin-left: 10px;">
                    <strong>Names of Qualified Dependent Child/ren</strong> (refers to a legitimate, illegitimate, or legally adopted child chiefly dependent upon & living with the taxpayer; not more than 21 years of age, unmarried, and not gainfully employed; or regardless of age, is incapable of self-support due to mental or physical defect).
                </div>
            </div>
        </div>

        <!-- Children Table -->
        <table class="data-table" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width: 35px; padding: 2px;"></th>
                    <th style="width: 22%; padding: 2px;">Last Name</th>
                    <th style="width: 22%; padding: 2px;">First Name</th>
                    <th style="width: 22%; padding: 2px;">Middle Name</th>
                    <th style="width: 19%; padding: 2px;">Date of Birth<br><span style="font-size: 5pt; font-weight: normal;">(MM / DD / YYYY)</span></th>
                    <th style="width: 15%; padding: 2px;">Mark if Mentally/<br>Physically<br>Incapacitated</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $children = $dependent_children ?? [];
                    $rowNumbers = ['13A', '14A', '15A', '16A'];
                    $fieldNumbers = ['14A', '14B', '14C', '14D','15A', '15B', '15C', '15D', '16A', '16B', '16C', '16D'];
                @endphp
                @for($i = 0; $i < 4; $i++)
                    <tr>
                        <td style="background-color: #d0d0d0; font-weight: bold; text-align: center; padding: 2px; vertical-align: middle;">{{ $rowNumbers[$i] }}</td>
                        <td style="padding: 1px 2px; position: relative; vertical-align: middle;">
                            <span style="font-weight: bold; font-size: 6.5pt; margin-right: 3px;">{{ $fieldNumbers[$i*3] }}</span><span style="font-size: 6.5pt;">{{ $children[$i]['last_name'] ?? '' }}</span>
                        </td>
                        <td style="padding: 1px 2px; position: relative; vertical-align: middle;">
                            <span style="font-weight: bold; font-size: 6.5pt; margin-right: 3px;">{{ $fieldNumbers[$i*3 + 1] }}</span><span style="font-size: 6.5pt;">{{ $children[$i]['first_name'] ?? '' }}</span>
                        </td>
                        <td style="padding: 1px 2px; position: relative; vertical-align: middle;">
                            <span style="font-weight: bold; font-size: 6.5pt; margin-right: 3px;">{{ $fieldNumbers[$i*3 + 2] }}</span><span style="font-size: 6.5pt;">{{ $children[$i]['middle_name'] ?? '' }}</span>
                        </td>
                        <td style="padding: 0; vertical-align: middle;">
                            @php
                                $birthDate = $children[$i]['birth_date'] ?? '';
                                $dateParts = $birthDate ? explode('/', $birthDate) : ['', '', '', ''];
                                while(count($dateParts) < 4) {
                                    $dateParts[] = '';
                                }
                            @endphp
                            <table style="width: 100%; border-collapse: collapse; border: none; table-layout: fixed;">
                                <tr>
                                    <td style="width: 25%; border: none; border-right: 1px solid #000; text-align: center; padding: 1px; font-size: 6.5pt;">{{ $dateParts[0] }}</td>
                                    <td style="width: 25%; border: none; border-right: 1px solid #000; text-align: center; padding: 1px; font-size: 6.5pt;">{{ $dateParts[1] }}</td>
                                    <td style="width: 25%; border: none; border-right: 1px solid #000; text-align: center; padding: 1px; font-size: 6.5pt;">{{ $dateParts[2] }}</td>
                                    <td style="width: 25%; border: none; text-align: center; padding: 1px; font-size: 6.5pt;">{{ $dateParts[3] }}</td>
                                </tr>
                            </table>
                        </td>
                        <td style="padding: 1px 2px; text-align: center; vertical-align: middle;">
                            <span style="font-weight: bold; font-size: 6.5pt; margin-right: 3px;">{{ chr(69 + $i) }}E</span><span class="checkbox" style="width: 10px; height: 10px; line-height: 10px;">{{ isset($children[$i]['incapacitated']) && $children[$i]['incapacitated'] ? 'X' : '' }}</span>
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <!-- PART IV: MULTIPLE EMPLOYMENTS -->
        <div class="part-header">PART IV&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;FOR EMPLOYEE WITH TWO OR MORE EMPLOYERS (MULTIPLE EMPLOYMENTS) WITHIN THE CALENDAR YEAR</div>

        <!-- Row 17: Type -->
        <div class="form-row">
            <div class="form-cell">
                <span class="field-number">17</span>
                <span class="field-label">Type of multiple employments</span>
                <div class="field-input" style="padding-top: 12px;">
                    <label><span class="checkbox">{{ isset($employment_type) && $employment_type == 'successive' ? 'X' : '' }}</span> Successive employments</label>&nbsp;&nbsp;&nbsp;
                    <label><span class="checkbox">{{ isset($employment_type) && $employment_type == 'concurrent' ? 'X' : '' }}</span> Concurrent employments</label><br>
                    <span style="font-size: 5.5pt; font-style: italic;">(If successive, enter previous employer(s); if concurrent, enter main employer)</span>
                </div>
            </div>
        </div>

        <!-- Section Title -->
        <div class="section-title">Previous and Concurrent Employments During the Calendar Year</div>

        <!-- Employers Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 120px;">TIN</th>
                    <th>Employer's Name</th>
                    <th style="width: 150px;">Period of Employment</th>
                    <th style="width: 120px;">Gross Compensation Income</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $previous_employers = $previous_employers ?? [];
                @endphp
                @forelse($previous_employers as $employer)
                    <tr>
                        <td>{{ $employer['tin'] ?? '' }}</td>
                        <td>{{ $employer['name'] ?? '' }}</td>
                        <td style="text-align: center;">{{ $employer['period'] ?? '' }}</td>
                        <td style="text-align: right;">{{ $employer['gross_income'] ?? '' }}</td>
                    </tr>
                @empty
                    @for($i = 0; $i < 3; $i++)
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    @endfor
                @endforelse
            </tbody>
        </table>

        <!-- PART V: EMPLOYER INFORMATION -->
        <div class="part-header">PART V&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;EMPLOYER INFORMATION</div>
        <div style="padding: 3px 5px; font-size: 6.5pt; font-style: italic; border-bottom: 1px solid #000;">
            (If self-employed, please do not accomplish this part)
        </div>

        <!-- Row 18-19: Employer TIN & RDO -->
        <div class="form-row">
            <div class="form-cell" style="width: 70%;">
                <span class="field-number">18</span>
                <span class="field-label">TIN</span>
                <div class="field-input" style="margin-top: 10px;">
                    <div class="tin-container">
                        @php
                            $employer_tin = str_pad($employer_tin ?? '', 15, ' ', STR_PAD_RIGHT);
                            $employer_tin_arr = str_split(str_replace('-', '', $employer_tin));
                        @endphp
                        @for($i = 0; $i < 15; $i++)
                            @if($i == 3 || $i == 6 || $i == 9)
                                <div class="tin-box">-</div>
                            @endif
                            <div class="tin-box">{{ $employer_tin_arr[$i] ?? '' }}</div>
                        @endfor
                    </div>
                </div>
            </div>
            <div class="form-cell" style="width: 30%;">
                <span class="field-number">19</span>
                <span class="field-label" style="margin-left: 5px;">RDO Code</span>
                <div class="field-input">{{ $employer_rdo_code ?? '' }}</div>
            </div>
        </div>

        <!-- Row 20: Employer Name (Non-Individual) -->
        <div class="form-row">
            <div class="form-cell">
                <span class="field-number">20</span>
                <span class="field-label">Employer's Name (For Non-Individuals)</span>
                <div class="field-input" style="margin-top: 10px;">{{ $employer_name ?? '' }}</div>
            </div>
        </div>

        <!-- Row 21: Employer Name (Individual) -->
        <div class="form-row">
            <div class="form-cell">
                <span class="field-number">21</span>
                <span class="field-label">Employer's Name (For Individuals) (Last Name, First Name, Middle Name)</span>
                <div class="field-input" style="padding-top: 30px;">
                    <div class="address-grid">
                        <div class="address-col">
                            Last Name<br>
                            <span style="font-size: 7pt;">{{ $employer_last_name ?? '' }}</span>
                        </div>
                        <div class="address-col">
                            First Name<br>
                            <span style="font-size: 7pt;">{{ $employer_first_name ?? '' }}</span>
                        </div>
                        <div class="address-col">
                            Middle Name<br>
                            <span style="font-size: 7pt;">{{ $employer_middle_name ?? '' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 22: Registered Address -->
        <div class="form-row">
            <div class="form-cell">
                <span class="field-number">22</span>
                <span class="field-label">Registered Address</span>
                <div class="field-input" style="padding-top: 30px;">
                    <div class="address-grid">
                        <div class="address-col" style="width: 18%;">
                            No. (Include Building Name)<br>
                            <span style="font-size: 7pt;">{{ $employer_building_no ?? '' }}</span>
                        </div>
                        <div class="address-col" style="width: 14%;">
                            Street<br>
                            <span style="font-size: 7pt;">{{ $employer_street ?? '' }}</span>
                        </div>
                        <div class="address-col" style="width: 16%;">
                            Subdivision<br>
                            <span style="font-size: 7pt;">{{ $employer_subdivision ?? '' }}</span>
                        </div>
                        <div class="address-col" style="width: 14%;">
                            Barangay<br>
                            <span style="font-size: 7pt;">{{ $employer_barangay ?? '' }}</span>
                        </div>
                        <div class="address-col" style="width: 20%;">
                            District/Municipality<br>
                            <span style="font-size: 7pt;">{{ $employer_municipality ?? '' }}</span>
                        </div>
                        <div class="address-col" style="width: 18%;">
                            City/Province<br>
                            <span style="font-size: 7pt;">{{ $employer_city ?? '' }}</span>
                        </div>
                        <div class="form-cell" style="width: 30%;">
                            <span class="field-label">Zip Code</span>
                            <div class="field-input" style="padding-top: 6px;">{{ $employer_zip_code ?? '' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-row" style="display: table; width: 100%;">
            <div class="form-cell" style="width: 65%; padding: 0; position: relative; vertical-align: top;">
                <div style="border-bottom: 1px solid #000; padding: 2px;">
                    <span class="field-number">23</span>
                    <span class="field-label">Date of Certification</span>
                    <div class="field-input" style="text-align: center; padding: 2px; margin-top: 2px;">
                        <div style=" width: 100%; margin-bottom: 2px;"></div>
                        {{ $certification_date ?? '' }}<br>
                        <span style="font-size: 6pt;">(MM / DD / YYYY)</span>
                    </div>
                </div>
                
                <div style="padding: 2px;">
                    <div style="padding: 5px 0 4px 0; font-size: 6pt; font-style: italic; line-height: 1.1; text-align: left;">
                        I declare, under the penalties of perjury, that this certificate has been made in good faith, verified by me and to the best of my knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof.
                    </div>
                    
                    <div style="margin-top: 5px; display: table; width: 100%;">
                        <div style="display: table-cell; width: 50%; padding-right: 10px; position: relative; vertical-align: top;">
                            <span style="position: absolute; left: 0; top: 5px; font-weight: bold; font-size: 7pt;">24</span>
                            <div style="margin-left: 15px; padding-top: 5px;">
                                <div style="border-top: 1px solid #000; width: 100%; margin-bottom: 2px;"></div>
                                <div style="font-size: 6.5pt; text-align: center;">Employer/Authorized Agent Signature</div>
                            </div>
                        </div>
                        
                        <div style="display: table-cell; width: 50%; padding-left: 10px; position: relative; vertical-align: top;">
                            <span style="position: absolute; left: 0; top: 5px; font-weight: bold; font-size: 7pt;">25</span>
                            <div style="margin-left: 15px; padding-top: 5px;">
                                <div style="border-top: 1px solid #000; width: 100%; margin-bottom: 2px;"></div>
                                <div style="font-size: 6.5pt; text-align: center;">Title/Position of Signatory</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-cell" style="width: 35%; padding: 2px; vertical-align: top; border-left: 1px solid #000;">
                <div style="font-size: 6.5pt; text-align: center; margin-bottom: 2px;">Stamp of Receiving Office<br>and Date of Receipt</div>
                <div style="min-height: 80px; margin-top: 2px;"></div>
            </div>
        </div>


    </div>
</body>
</html>