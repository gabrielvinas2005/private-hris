<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Request for Publication of Vacant Positions</title>
    <style>
        @page { margin: 18px 16px; size: A4 landscape; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #000;
        }
        table { border-collapse: collapse; width: 100%; }
        .w-full { width: 100%; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .mt-8 { margin-top: 8px; }
        .mt-12 { margin-top: 12px; }
        .mb-6 { margin-bottom: 6px; }
        .underline { text-decoration: underline; }
        .small { font-size: 9pt; }
        .tbl th, .tbl td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
        }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>
    <table class="w-full small">
        <tr>
            <td>CS Form No. 9<br>Revised 2018</td>
            <td class="text-right">Electronic copy to be submitted to the CSC FO<br>must be in MS Excel format</td>
        </tr>
    </table>

    <div class="text-center mt-8">
        <div>Republic of the Philippines</div>
        <div class="fw-bold underline" style="margin: 2px 0;">{{ strtoupper($orgCompanyName) }}</div>
        <div class="fw-bold">Request for Publication of Vacant Positions</div>
    </div>

    <table class="w-full mt-12">
        <tr>
            <td class="fw-bold" style="width: 90px;">To:</td>
            <td class="fw-bold">CIVIL SERVICE COMMISSION (CSC)</td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top: 6px;">
                &nbsp;&nbsp;&nbsp;We hereby request the publication of the following vacant positions, which are authorized to be filled, at the {{ $orgCompanyName }} ({{ $orgBranchCode }}) in the CSC website:
            </td>
        </tr>
    </table>

    <div class="mt-8" style="text-align: right;">
        <table style="table-layout: fixed; width: 320px; margin-left: auto;">
            <tr>
                <td colspan="4" style="border-bottom: 1px solid #000; text-align: center; font-weight: bold;">&nbsp;</td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: center; font-weight: bold;">HRMO</td>
            </tr>
            <tr>
                <td class="fw-bold" style="width: 60px;">Date:</td>
                <td style="width: 240px; white-space: nowrap;">
                    <span style="display: inline-block; border-bottom: 1px solid #000; padding: 0 12px;">
                        {{ \Carbon\Carbon::parse($printedDate)->format('F d, Y') }}
                    </span>
                </td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>

    <table class="tbl mt-12">
        <tr class="text-center fw-bold">
            <th rowspan="2" style="width: 35px;">No.</th>
            <th rowspan="2" style="width: 150px;">Position Title<br>(Parenthetical Title, if applicable)</th>
            <th rowspan="2" style="width: 80px;">Plantilla Item No.</th>
            <th rowspan="2" style="width: 70px;">Salary/ Job/ Pay Grade</th>
            <th rowspan="2" style="width: 80px;">Monthly Salary</th>
            <th colspan="5">Qualification Standards</th>
            <th rowspan="2" style="width: 90px;">Place of Assignment</th>
        </tr>
        <tr class="text-center fw-bold">
            <th style="width: 100px;">Education</th>
            <th style="width: 90px;">Training</th>
            <th style="width: 90px;">Experience</th>
            <th style="width: 90px;">Eligibility</th>
            <th style="width: 90px;">Competency<br>(if applicable)</th>
        </tr>
        @foreach($plantillas as $index => $p)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $p->position_title }}</td>
                <td>{{ $p->plantilla_item_no }}</td>
                <td class="text-center">
                    {{ $p->salary_grade_name }}@if($p->salary_step_name)/{{ $p->salary_step_name }}@endif
                </td>
                <td class="text-right">{{ $p->monthly_salary ? number_format($p->monthly_salary, 2) : '' }}</td>
                <td>{{ $p->education }}</td>
                <td>{{ $p->training }}</td>
                <td>{{ $p->experience }}</td>
                <td>{{ $p->eligibility }}</td>
                <td>{{ $p->competency }}</td>
                <td>{{ $p->place_of_assignment }}</td>
            </tr>
        @endforeach
    </table>

    <div class="mt-12 small">
        Interested and qualified applicants should signify their interest in writing. Attach the following documents to the application letter and send to the address below not later than <u>___________________</u>.
    </div>

    <div class="small mt-8">
        1. Fully accomplished Personal Data Sheet (PDS) with recent passport-sized picture (CS Form No. 212, Revised 2017) which can be downloaded<br>
        &nbsp;&nbsp;&nbsp;&nbsp;at www.csc.gov.ph;<br>
        2. Performance rating in the last rating period (if applicable);<br>
        3. Photocopy of certificate of eligibility/rating/license; and<br>
        4. Photocopy of Transcript of Records.
    </div>

    <div class="fw-bold mt-12 small">
        QUALIFIED APPLICANTS are advised to hand in or send through courier/email their application to:
    </div>

    <div class="mt-6 small" style="max-width: 260px; margin-left: 40px;">
        <div style="text-decoration: underline; text-align: center;">HRMO</div>
        <div style="text-decoration: underline; text-align: center;">(Position Title)</div>
        <div style="text-decoration: underline; text-align: center;">(Office Address)</div>
        <div style="text-decoration: underline; text-align: center;">mail@agency.gov.ph</div>
    </div>

    <div class="fw-bold mt-12 small">
        APPLICATIONS WITH INCOMPLETE DOCUMENTS SHALL NOT BE ENTERTAINED.
    </div>
</body>
</html>

