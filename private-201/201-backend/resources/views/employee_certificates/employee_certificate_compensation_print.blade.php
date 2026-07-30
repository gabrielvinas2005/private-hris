<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Employment and Compensation</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11.5pt;
            color: #000;
            background: #fff;
        }

        .certificate-page {
            position: relative;
            width: 210mm;
            min-height: 297mm;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        /* ── Header image (full width, no side padding) ── */
        .header-wrap {
            width: 100%;
            line-height: 0;
        }

        .header-wrap img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* ── Content area with side margins ── */
        .content-area {
            padding: 12mm 18mm 10mm 18mm;
            flex: 1;
        }

        /* ── "Global MSME Academy" sub-label ── */
        .global-msme {
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #555;
            margin-bottom: 12px;
            letter-spacing: 0.3px;
        }

        /* ── Document title ── */
        .doc-title {
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 6px 0 5px 0;
        }

        .title-rule {
            border: none;
            border-top: 1.5px solid #000;
            margin: 0 0 20px 0;
        }

        /* ── Body paragraphs ── */
        .body-text {
            text-align: justify;
            font-size: 11.5pt;
            line-height: 1.75;
            margin-bottom: 14px;
            text-indent: 40px;
        }

        /* ── Compensation table intro ── */
        .comp-intro {
            font-size: 11.5pt;
            line-height: 1.75;
            margin-bottom: 8px;
            text-indent: 40px;
        }

        /* ── Compensation table ── */
        .comp-table {
            width: 80%;
            margin: 0 auto 18px auto;
            border-collapse: collapse;
            font-size: 11.5pt;
        }

        .comp-table td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .comp-table .label-col {
            width: 65%;
        }

        .comp-table .currency-col {
            width: 8%;
            text-align: left;
            white-space: nowrap;
        }

        .comp-table .amount-col {
            width: 27%;
            text-align: right;
            white-space: nowrap;
        }

        .comp-table .total-row td {
            padding-top: 6px;
            font-weight: bold;
            border-top: 1px solid #000;
        }

        /* ── Signature block ── */
        .signature-block {
            margin-top: 40px;
            text-align: right;
        }

        .signature-name {
            font-family: Arial, Helvetica, sans-serif;
            font-weight: bold;
            font-size: 11.5pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .signature-position {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
        }

        /* ── Doc meta (document number / revision) ── */
        .doc-meta {
            text-align: right;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            font-weight: bold;
            margin-top: 6px;
        }

        /* ── Footer image ── */
        .footer-wrap {
            width: 100%;
            line-height: 0;
            margin-top: auto;
        }

        .footer-wrap img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* ── Page break between employees ── */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @foreach ($employees as $employee)
        @php
            $middleInitial = !empty($employee->middle_name)
                ? strtoupper(substr(trim($employee->middle_name), 0, 1)) . '. '
                : '';

            $firstName  = trim($employee->first_name ?? '');
            $lastName   = trim($employee->last_name  ?? '');
            $employeeFullName = strtoupper($firstName . ' ' . $middleInitial . $lastName);

            $honorific = !empty($employee->name_prefix)
                ? strtoupper(rtrim(trim($employee->name_prefix), '.')) . '.'
                : ($employee->gender_id == 1 ? 'MS.' : 'MR.');

            $pronoun      = $employee->gender_id == 1 ? 'She'  : 'He';
            $pronounLower = $employee->gender_id == 1 ? 'her'  : 'his';
            $possessive   = $employee->gender_id == 1 ? 'Ms.'  : 'Mr.';
            $lastNameOnly = strtoupper($lastName);

            $companyName     = $companies[0]->name ?? $orgCompanyName;
            $employmentStatus = strtolower($employee->employment_type ?? 'permanent');

            $positionLine = $employee->position ?? '';
            if (!empty($employee->salary_grade)) {
                $salaryGradeLabel = trim((string) $employee->salary_grade);
                $salaryGradeLabel = preg_replace('/^SG\s+/i', '', $salaryGradeLabel);
                if (preg_match('/^\d+$/', $salaryGradeLabel)) {
                    $salaryGradeLabel = 'Salary Grade ' . $salaryGradeLabel;
                }
                $positionLine .= ' – ' . $salaryGradeLabel;
            }

            /* Issue date */
            $issueDate = \Carbon\Carbon::now();
            $issueDay  = (int) $issueDate->format('j');
            $issueSuffix = match(true) {
                $issueDay % 10 == 1 && $issueDay != 11 => 'st',
                $issueDay % 10 == 2 && $issueDay != 12 => 'nd',
                $issueDay % 10 == 3 && $issueDay != 13 => 'rd',
                default => 'th',
            };

            /* Bonus label normalisation */
            $bonusLabelMap = [
                '14th Month Pay' => 'Mid-Year Bonus',
                '13th Month Pay' => 'Year-End Bonus',
            ];

            /* Build compensation lines */
            $compensationLines = [];
            $compensationLines[] = [
                'label'    => 'Basic Monthly Salary',
                'amount'   => $annual_salary ?? 0,
                'show_p'   => true,   // show peso sign on first line only
            ];

            foreach ($incomes as $income) {
                if ($employee->id == $income->employee_id) {
                    $compensationLines[] = [
                        'label'  => $income->item,
                        'amount' => $income->amount,
                        'show_p' => false,
                    ];
                }
            }

            if (!empty($bonus)) {
                foreach ($bonus as $bonusRow) {
                    if ($employee->id == $bonusRow->employee_id) {
                        $compensationLines[] = [
                            'label'  => $bonusLabelMap[$bonusRow->item] ?? $bonusRow->item,
                            'amount' => $bonusRow->amount,
                            'show_p' => false,
                        ];
                    }
                }
            }

            $totalCompensation = array_sum(array_column($compensationLines, 'amount'));
        @endphp

        <div class="certificate-page">

            {{-- ── Full-width header image ── --}}
            @if (!empty($header_img))
                <div class="header-wrap">
                    <img src="{{ $header_img }}" alt="Header">
                </div>
            @endif

            {{-- ── Content ── --}}
            <div class="content-area">

                {{-- <div class="global-msme">Global MSME Academy</div> --}}

                <h1 class="doc-title">Certificate of Employment and Compensation</h1>
                <hr class="title-rule">

                {{-- Paragraph 1 – employment fact --}}
                <p class="body-text">
                    This is to certify that <strong>{{ $honorific }} {{ $employeeFullName }}</strong>,
                    is a {{ $employmentStatus }} employee of the
                    <strong>{{ $companyName }}</strong>, an attached agency of the
                    <strong>Department of Trade and Industry (DTI)</strong> since
                    <strong>{{ date('F j, Y', strtotime($employee->date_hired)) }}</strong> up to present.
                    {{ $pronoun }} is currently holding the position of
                    <strong>{{ $positionLine }}</strong>@if (!empty($employee->department))
                        under the <strong>{{ $employee->department }}</strong>@endif.
                </p>

                {{-- Compensation intro --}}
                <p class="comp-intro">
                    {{ $pronoun == 'She' ? 'Her' : 'His' }} present gross annual compensation, broken down as follows:
                </p>

                {{-- Compensation table --}}
                <table class="comp-table">
                    <tbody>
                        @foreach ($compensationLines as $line)
                            <tr>
                                <td class="label-col">{{ $line['label'] }}</td>
                                <td class="currency-col">{{ $line['show_p'] ? 'P' : '' }}</td>
                                <td class="amount-col">{{ number_format($line['amount'], 2, '.', ',') }}</td>
                            </tr>
                        @endforeach

                        {{-- Total row --}}
                        <tr class="total-row">
                            <td class="label-col"></td>
                            <td class="currency-col">P</td>
                            <td class="amount-col">{{ number_format($totalCompensation, 2, '.', ',') }}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- Paragraph 2 – issuance purpose --}}
                <p class="body-text">
                    This certification is being issued upon the request of
                    <strong>{{ $possessive }} {{ $lastNameOnly }}</strong>
                    as a confirmation of {{ $pronounLower }} employment with the Center and as a reference for
                    whatever legal purpose it may serve.  The accuracy of this document may verify by emailing the Human Resources Section at
                    <strong>{{ $orgCompanyEmail }}</strong>
                </p>

                {{-- Paragraph 3 – date and place --}}
                <p class="body-text">
                    Issued this {{ $issueDay }}{{ $issueSuffix }} day of
                    {{ $issueDate->format('F, Y') }} at {{ $orgCompanyAddress }}
                </p>

                {{-- Signatory --}}
                <div class="signature-block">
                    <div class="signature-name">{{ strtoupper($signatories['signatory'] ?? '') }}</div>
                    <div class="signature-position">{{ $signatories['position'] ?? '' }}</div>
                </div>

                {{-- Document number / revision
                @if (!empty($footer['document_no']) || !empty($footer['revision']))
                    @if (!empty($footer['document_no']))
                        <p class="doc-meta">{{ $footer['document_no'] }}</p>
                    @endif
                    @if (!empty($footer['revision']))
                        <p class="doc-meta" style="margin-top:0;">{{ $footer['revision'] }}</p>
                    @endif
                @endif --}}

            </div>{{-- end .content-area --}}

            {{-- ── Full-width footer image ── --}}
            @if (!empty($footer_img))
                <div class="footer-wrap">
                    <img src="{{ $footer_img }}" alt="Footer">
                </div>
            @endif

        </div>{{-- end .certificate-page --}}

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif

    @endforeach
</body>

</html>
