<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Request for Publication of Vacant Positions</title>
    <style>
        @page {
            margin: 18px 16px;
            size: A4 landscape;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #000;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td {
            vertical-align: middle;
        }

        p {
            margin: 0;
        }

        .page-break {
            page-break-before: always;
        }

        .instructions-section {
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .instructions-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .instructions-content {
            font-size: 11px;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .instructions-list {
            margin-left: 20px;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <div>
        <div>
            <table style="width: 100%; border: none;">
                <tr>
                    <td colspan="2" style="width: 85%; border: padding: 0px;">
                        <span style="font-size: 12px; font-style: italic;">CS Form No. 9</span><br>
                        <spans style="font-size: 10px; font-style: italic;">Revised 2025</span>
                    </td>
                    <td style="width: 15%; padding: 0px; text-align: center; border: 1px solid black;">
                        <span style="font-size: 8px; font-style: italic; ">
                            Electronic copy to be submitted to the <br>
                            CSC FO must be in MS Excel format
                            </spans>
                    </td>
                </tr>

                <tr>
                    <td colspan="3">
                        <p style="text-align: center; font-size: 12px; ">Republic of the Philippines</p>
                        <p style="text-align: center; font-size: 12px; font-weight: bold;">{{ $orgCompanyName }}</p>
                        <p style="text-align: center; font-size: 12px; font-weight: bold;">Request for Publication of
                            Vacant Positions</p>
                    </td>
                </tr>

                <tr>
                    <td colspan="3">
                        <p style="font-size: 12px; ">To: CIVIL SERVICE COMMISSION (CSC)</p>
                    </td>
                </tr>

                <tr>
                    <td colspan="3">
                        <p style="font-size: 12px; text-indent: 20px;">We hereby request the publication of the
                            following vacant positions, which are authorized to be filled, at the {{ $orgCompanyName }}
                            ({{ $orgBranchCode }}) - GMEA in the CSC website:</p>
                    </td>
                </tr>
            </table>
        </div>

        <div>
            <table>
                <tr>
                    <td colspan="2" style=" width: 70%;"></td>
                    <td style="width: 30%; padding-top: 30px;">
                        <div
                            style="font-size: 12px; border-bottom: 1px solid black; text-align: center; padding-bottom: 2px;">
                            {{ $hrmoName ?? 'HRMO' }}</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="width:70%;"></td>
                    <td style="width: 30%;">
                        <p style="font-size: 12px;">Date: <span
                                style="text-decoration: underline;">{{ \Carbon\Carbon::parse($printedDate)->format('F d, Y') }}</span>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <br>

        <div>
            <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                <tr>
                    <td rowspan="2"
                        style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                        <p>No.</p>
                    </td>
                    <td rowspan="2"
                        style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                        <p>Position Title (Parenthetical <br> Title, if applicable)</p>
                    </td>
                    <td rowspan="2"
                        style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                        <p>Plantilla Item<br>No.</p>
                    </td>
                    <td rowspan="2"
                        style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                        <p>Salary/<br>Job/ Pay<br>Grade</p>
                    </td>
                    <td rowspan="2"
                        style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                        <p>Monthly<br>Salary</p>
                    </td>
                    <td colspan="5" style="border: 1px solid black; text-align: center; padding: 2px;">
                        <p>Qualification Standards</p>
                    </td>
                    <td rowspan="2"
                        style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                        <p>Place of<br>Assignment</p>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid black; text-align: center; padding: 2px;">
                        <p>Education</p>
                    </td>
                    <td style="border: 1px solid black; text-align: center; padding: 2px;">
                        <p>Training</p>
                    </td>
                    <td style="border: 1px solid black; text-align: center; padding: 2px;">
                        <p>Experience</p>
                    </td>
                    <td style="border: 1px solid black; text-align: center; padding: 2px;">
                        <p>Eligibility</p>
                    </td>
                    <td style="border: 1px solid black; text-align: center; padding: 2px;">
                        <p style="font-size: 10px; font-style: italic;">
                            Competency/<br>
                            Area of<br>
                            Specialization/<br>
                            Residency<br>
                            Requirement<br>
                            (if applicable)
                        </p>
                    </td>
                </tr>

                @foreach ($plantillas as $index => $p)
                    <tr>
                        <td style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                            {{ $index + 1 }}</td>
                        <td style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                            {{ $p->position_title }}</td>
                        <td style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                            {{ $p->plantilla_item_no }}</td>
                        <td style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                            {{ \App\Support\PositionDescriptionChecks::salaryGradeNumber($p->salary_grade_name ?? '') }}
                        </td>
                        <td style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                            @if ($p->monthly_salary !== null && $p->monthly_salary !== '')
                                @php
                                    $salaryNum = (float) $p->monthly_salary;
                                    $salaryFormatted =
                                        (float) (int) $salaryNum === $salaryNum
                                            ? number_format($salaryNum, 0, '.', '')
                                            : rtrim(rtrim(number_format($salaryNum, 2, '.', ''), '0'), '.');
                                @endphp
                                {{ $salaryFormatted }}
                            @endif
                        </td>
                        <td style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                            {{ $p->education }}</td>
                        <td style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                            {{ $p->training }}</td>
                        <td style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                            {{ $p->experience }}</td>
                        <td style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                            {{ $p->eligibility }}</td>
                        <td style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                            {{ $p->competency }}</td>
                        <td style="border: 1px solid black; text-align: center; padding: 2px; vertical-align: middle;">
                            {{ $p->place_of_assignment }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
        <br>

        <div>
            <table>
                <tr>
                    <td colspan="3">
                        <p style="font-size: 12px;">Interested and qualified applicants should signify their interest in
                            writing through an application letter addressed to the head of office. Applicants must
                            attach the following documents to the application letter and send these to the address below
                            not later than <span style="text-decoration: underline;">December 30, 2025</span></p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="padding-left: 20px;">
                        <p style="font-size: 12px; margin-top: 10px;">1. Fully accomplished Personal Data Sheet (PDS)
                            with Work Experience Sheet and recent passport-sized or unfiltered digital picture (CS Form
                            No. 212, Revised 2025); digitally signed or electronically signed;</p>
                        <p style="font-size: 12px;">2. Hard copy or electronic copy of Performance rating in the last
                            rating period (if applicable);</p>
                        <p style="font-size: 12px;">3. Hard copy or electronic copy of proof of
                            eligibility/rating/license; and</p>
                        <p style="font-size: 12px;">4. Hard copy or electronic copy of Transcript of Records.</p>
                    </td>
                </tr>

                <tr>
                    <td colspan="3">
                        <p style="font-size: 12px; font-weight: bold; margin-top: 12px;">QUALIFIED APPLICANTS are
                            advised to hand in or send through courier/email their application to:</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="padding-left: 40px;">
                        <div style="font-size: 12px; max-width: 260px;">
                            <p style="text-decoration: underline; text-align: center; margin: 0;">
                                {{ $hrmoName ?? 'HRMO' }}</p>
                            <p style="text-decoration: underline; text-align: center; margin: 0;">
                                {{ $hrmoPosition ?? '(Position Title)' }}</p>
                            <p style="text-decoration: underline; text-align: center; margin: 0;">
                                {{ $companyAddress ?? '(Office Address)' }}</p>
                            <p style="text-decoration: underline; text-align: center; margin: 0;">
                                {{ $hrmoEmail ?? 'mail@agency.gov.ph' }}</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <p style="font-size: 12px; font-weight: bold; margin-top: 12px;">APPLICATIONS WITH INCOMPLETE
                            DOCUMENTS SHALL NOT BE ENTERTAINED.</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Instructions Page -->
    <div class="page-break">
        <div>
            <div class="instructions-section">
                <div class="instructions-title">Instructions</div>
                <div class="instructions-content">
                    <p class="instructions-list">1. This form is required for requesting the publication of vacant
                        positions.</p>
                    <p class="instructions-list">2. Use the provided table to indicate position titles and other
                        pertinent information.</p>
                    <p class="instructions-list">3. The HRMO (Human Resource Management Officer) must verify the
                        accuracy and completeness of the information and active contact details.</p>
                    <p class="instructions-list">4. For vacant division chief and executive/managerial positions,
                        agencies must submit a "Position Description Form (DBM-CSC Form No. 1)."</p>
                </div>
            </div>

            <div class="instructions-section">
                <div class="instructions-title">Important things to remember before accomplishing the form</div>
                <div class="instructions-content">
                    <p class="instructions-list">1. Rows 1 to 17 of "Sheet1" must remain intact, and no row insertions
                        are allowed.</p>
                    <p class="instructions-list">2. Do not rename sheet tabs ("Sheet1," "Sheet2," and "Instructions").
                    </p>
                    <p class="instructions-list">3. Do not delete any sheets.</p>
                    <p class="instructions-list">4. Do not delete the first row of "Sheet2," as it serves as column
                        headers.</p>
                    <p class="instructions-list">5. Do not make changes to the column headers of "Sheet2."</p>
                    <p class="instructions-list">6. Do not remove the "Generate Sheet2" command button in "Sheet1,"
                        which captures data from "Sheet1" to "Sheet2" and is not printable.</p>
                    <p class="instructions-list">7. You may insert rows from row 18 onwards for job vacancies, but an
                        empty row must be between the last item and the "Interested..." statement.</p>
                    <p class="instructions-list">8. The closing date must be entered in the space after "not later
                        than."</p>
                    <p class="instructions-list">9. Merged cells should NOT be unmerged.</p>
                    <p class="instructions-list">10. Do not enter multiple positions in one row; each row is for one
                        position.</p>
                    <p class="instructions-list">11. For Salary Grade, enter only the number, not the acronym "SG."</p>
                    <p class="instructions-list">12. For Monthly Salary, enter only numbers (no peso sign or commas).
                    </p>
                </div>
            </div>

            <div class="instructions-section">
                <div class="instructions-title">How to accomplish the form</div>
                <div class="instructions-content">
                    <p class="instructions-list">1. In row 4, select the agency name from a dropdown list. The selected
                        agency name is automatically inserted in row 9, so no changes are needed there.</p>
                    <p class="instructions-list">2. Fill in "HRMO" in row 11 and the "Date" of request in row 14.</p>
                    <p class="instructions-list">3. From row 18, enter the list of job vacancies following the column
                        headers, with one position per row.</p>
                    <p class="instructions-list">4. Make necessary changes to the closing date, found in the statement
                        containing "Interested..." and "not later than" words. (Refer to reminder No. 8)</p>
                    <p class="instructions-list">5. Accomplish four rows below the "QUALIFIED APPLICANTS..." statement:
                    </p>
                    <div style="margin-left: 40px; margin-top: 5px; margin-bottom: 5px;">
                        <p style="margin-bottom: 3px;">• 1st row: Name of the designated person to whom documents must
                            be sent.</p>
                        <p style="margin-bottom: 3px;">• 2nd row: Position of the designated person.</p>
                        <p style="margin-bottom: 3px;">• 3rd row: Address of the Agency.</p>
                        <p style="margin-bottom: 3px;">• 4th row: Agency's email address.</p>
                    </div>
                    <p class="instructions-list">6. Insert rows for job vacancies if needed, and delete unused rows.
                    </p>
                </div>
            </div>

            <div class="instructions-section">
                <div class="instructions-title">After accomplishing the form</div>
                <div class="instructions-content">
                    <p class="instructions-list">When all entries are done and no revisions are needed, click the
                        "Generate Sheet2" command button. This executes VBA code to generate data for upload to the CSC
                        Job Portal database.</p>
                    <p class="">After the command button has been clicked, select Sheet2 and check if the correct
                        data has been captured. Having Sheet2 as the active sheet, save the file as a CSV file (Comma
                        Delimited). This CSV file will then be uploaded to the Job Portal database.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
