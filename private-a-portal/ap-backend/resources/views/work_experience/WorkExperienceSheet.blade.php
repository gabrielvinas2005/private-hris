<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Experience Sheet</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 15px;
            font-size: 11px;
            line-height: 1.3;
            background-color: white;
        }

        @if (!empty($is_word))
            body {
                font-size: 10.5px;
                padding: 0;
                margin: 0;
            }

            .container {
                border: 1px solid #000;
                max-width: none;
                margin: 0;
            }

            .header {
                padding: 6px;
                font-size: 11px;
            }

            .instructions {
                padding: 8px;
            }

            .field-item {
                margin-bottom: 4px;
            }

            .work-section {
                border-width: 1px;
            }

            .work-section-column {
                padding: 10px;
            }

            .signature-section {
                margin-left: 300px;
                padding-top: 60px;
            }
        @endif

        .container {
            border: 2px solid #000;
            background-color: white;
            max-width: 8.5in;
            margin: 0 auto;
        }

        .header {
            background-color: #808080;
            color: white;
            text-align: center;
            padding: 8px;
            font-weight: bold;
            font-size: 12px;
            border-bottom: 2px solid #000;
        }

        .instructions-container {
            background-color: rgba(182, 182, 182, 0.53);
            padding: 0;
        }

        .instructions {
            padding: 10px;
            font-size: 10px;
            line-height: 1.4;
            font-style: italic;
            min-height: 80px;
        }

        .divider-line {
            border-bottom: 1px solid #000;
            background-color: rgba(182, 182, 182, 0);
            width: 100%;
            height: 10px;
        }

        .instructions-title {
            font-weight: bold;
            font-style: normal;
            margin-bottom: 5px;
        }

        .instructions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .instructions-table td {
            padding: 10px;
            vertical-align: top;
        }

        .instructions-table td:first-child {
            width: 10%;
            font-weight: bold;
            font-style: normal;
        }

        .instructions-table td:last-child {
            width: 90%;
            font-style: italic;
        }

        .work-section {
            border-bottom: 2px solid #000;
            padding: 15px;
            min-height: 200px;
        }

        .work-section:last-of-type {
            border-bottom: none;
        }

        .work-experiences-container {
            display: flex;
            flex-wrap: wrap;
        }

        .work-section-column {
            flex: 1;
            min-width: 300px;
            border-right: 0;
            padding: 15px;
        }

        .work-section-column:last-child {
            border-right: 0;
        }

        .work-section-column:first-child {
            border-right: 0;
        }

        .work-item-divider {
            height: 0;
            border: 0;
            border-bottom: 1px solid #000;
            margin: 12px -14px 0 -15px;
        }

        .field-list {
            list-style: none;
            padding-left: 30px;
            /* indent bullets for Duration–Summary section */
        }

        .field-item {
            margin-bottom: 6px;
            position: relative;
            padding-left: 15px;
        }

        .field-item::before {
            content: "•";
            position: absolute;
            left: 0;
            font-weight: bold;
            font-size: 14px;
            line-height: 1;
        }

        .field-label {
            font-weight: bold;
        }

        .nested-list {
            margin-top: 10px;
            margin-left: 50px;
        }

        .nested-value-list {
            list-style-type: circle;
            /* hollow round bullet for values */
            margin: 5px 0 0 20px;
            padding-left: 0;
        }

        .nested-value-list li {
            margin-bottom: 3px;
        }

        .nested-item {
            margin-bottom: 15px;
            position: relative;
            padding-left: 15px;
        }

        .nested-item::before {
            content: "•";
            position: absolute;
            left: 0;
            font-weight: bold;
            font-size: 14px;
            line-height: 1;
        }

        .footer-container {
            padding: 15px;
            position: relative;
        }

        .form-reference {
            font-size: 10px;
            font-style: italic;
            position: absolute;
            bottom: 15px;
            left: 15px;
        }

        .signature-section {
            text-align: center;
            padding-top: 90px;
            margin-left: 390px;
            margin-top: 40px;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            width: 250px;
            height: 25px;
            margin: 0 auto 5px;
        }

        .signature-text {
            font-size: 10px;
            line-height: 1.2;
        }

        .date-section {
            margin-top: 20px;
            text-align: center;
        }

        .date-line {
            border-bottom: 1px solid #000;
            width: 120px;
            height: 20px;
            display: inline-block;
            margin-left: 5px;
        }

        .cs-form {
            padding: 50px;
            margin-right: 50px;
        }

        .form-reference-inline {
            font-size: 10px;
            font-style: italic;
            margin-top: 16px;
            margin-left: 15px;
            margin-bottom: 10px;

        }

        .sample-note {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            text-decoration: underline;
        }

        @media print {
            body {
                padding: 0;
            }

            .container {
                border: 1px solid #000;
            }
        }
    </style>
</head>

<body>
    <div class="form-reference-inline"><strong>Attachment to CS Form No. 212</strong></div>
    <div class="container">
        <!-- Header -->
        <div class="header">
            WORK EXPERIENCE SHEET
        </div>

        <!-- Instructions Container with Background -->
        <div class="instructions-container">
            <div class="instructions">
                <table class="instructions-table">
                    <tr>
                        <td>Instructions:</td>
                        <td>
                            1. Include only the work experiences relevant to the position being applied to.
                            <br><br>
                            2. The duration should include start and finish dates, if known, month in abbreviated form,
                            if known, and year in full. For the current position, use the word Present, e.g.,
                            1998-Present. Work experience should be listed from most recent first.
                        </td>
                    </tr>
                </table>
            </div>
            <!-- Divider line inside the gray background -->
            <div class="divider-line"></div>
        </div>

        @if (isset($work_experiences) && count($work_experiences) > 0)
            <div class="work-experiences-container">
                @foreach ($work_experiences as $index => $experience)
                    <!-- Work Experience Section Column -->
                    <div class="work-section-column">
                        @if ($loop->first)
                            <div class="sample-note">Sample: If applying to Supervising Administrative Officer (Human
                                Resource Management Officer IV)</div>
                        @endif
                        <ul class="field-list">
                            <li class="field-item">
                                <span class="field-label">Duration:</span>
                                {{ $experience->duration_inclusion_display ?? ($experience->Duration ?? '') }}
                            </li>
                            <li class="field-item">
                                <span class="field-label">Position:</span> {{ $experience->Position ?? '' }}
                            </li>
                            <li class="field-item">
                                <span class="field-label">Name of Office/Unit:</span>
                                {{ $experience->Office_name ?? '' }}
                            </li>
                            <li class="field-item">
                                <span class="field-label">Immediate Supervisor:</span>
                                {{ $experience->Immediate_supervisor ?? '' }}
                            </li>
                            <li class="field-item">
                                <span class="field-label">Name of Agency/Organization and Location:</span>
                                {{ $experience->Office_Address ?? '' }}
                            </li>
                        </ul>

                        <div class="nested-list">
                            <div class="nested-item">
                                <span class="field-label">List of Accomplishments and Contributions (if any)</span>
                                <ul class="nested-value-list">
                                    <li>{{ $experience->List_Of_Accomplishment ?? '' }}</li>
                                </ul>
                            </div>

                            <div class="nested-item">
                                <span class="field-label">Summary of Actual Duties</span>
                                <ul class="nested-value-list">
                                    <li>{{ $experience->Summary_of_Duties ?? '' }}</li>
                                </ul>
                            </div>
                        </div>

                        @if (!$loop->last)
                            <div class="work-item-divider"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty sections for manual filling -->
            @for ($i = 0; $i < 2; $i++)
                <div class="work-section">
                    <ul class="field-list">
                        <li class="field-item">
                            <span class="field-label">Duration:</span>
                        </li>
                        <li class="field-item">
                            <span class="field-label">Position:</span>
                        </li>
                        <li class="field-item">
                            <span class="field-label">Name of Office/Unit:</span>
                        </li>
                        <li class="field-item">
                            <span class="field-label">Immediate Supervisor:</span>
                        </li>
                        <li class="field-item">
                            <span class="field-label">Name of Agency/Organization and Location:</span>
                        </li>
                    </ul>

                    <div class="nested-list">
                        <div class="nested-item">
                            <span class="field-label">List of Accomplishments and Contributions (if any)</span>
                        </div>

                        <div class="nested-item">
                            <span class="field-label">Summary of Actual Duties</span>
                        </div>
                    </div>
                </div>
            @endfor
        @endif

        <!-- Footer -->

    </div>

    <div class="signature-section">
        <div class="signature-line">{{ isset($work_experiences[0]) ? $work_experiences[0]->applicant_name ?? '' : '' }}
        </div>
        <div class="signature-text">(Signature over Printed Name</div>
        <div class="signature-text">of Employee/Applicant)</div>

        <div class="date-section">
            <span class="signature-text">Date: {{ date('F d, Y') }}</span>
        </div>
    </div>
</body>

</html>
