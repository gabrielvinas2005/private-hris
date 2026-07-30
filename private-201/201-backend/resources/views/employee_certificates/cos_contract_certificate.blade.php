<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contract of Service</title>
    <style>
        /* Top = 1in, Right = 1in, Bottom = 0.75in, Left = 1.25in */
        @page {
            margin: 1in 1in 0.75in 1.25in;
            size: A4 portrait;
        }

        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            color: #000;
        }

        .page {
            page-break-after: always;
        }

        .page-ack {
            page-break-before: always;
            page-break-after: auto;
        }

        .page-3 p,
        .page-3 .numbered-clauses li,
        .page-3 .sub-clauses li {
            line-height: 1.1;
            margin-bottom: 3px;
        }

        .page-3 .numbered-clauses {
            margin-bottom: 4px;
        }

        .page-3 .sub-clauses li {
            font-size: 10pt;
            margin-bottom: 2px;
        }

        .signature-section {
            margin-top: 10px;
            width: 100%;
            page-break-inside: avoid;
        }

        .signature-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-section td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 8px;
        }

        .signature-section .witness-label {
            text-align: center;
            padding: 10px 0 4px;
        }

        .signature-line {
            margin-top: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
            line-height: 1.1;
        }

        .signature-section .witness-line {
            margin-top: 8px;
        }

        .signature-title {
            font-size: 10pt;
            line-height: 1.1;
        }

        p {
            line-height: 1.15;
            margin: 0 0 6px 0;
            text-align: justify;
        }

        .center { text-align: center; }

        .numbered-clauses {
            margin: 0 0 6px 30pt;
            padding-left: 15pt;
        }

        .numbered-clauses li {
            margin-bottom: 4px;
            line-height: 1.15;
            text-align: justify;
        }

        .sub-clauses {
            margin-top: 3px;
            margin-bottom: 3px;
            padding-left: 18pt;
        }

        .sub-clauses li {
            margin-bottom: 4px;
            line-height: 1.15;
            text-align: justify;
        }

        .ack-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }

        .ack-table td {
            padding: 4px 6px;
            font-size: 10pt;
            vertical-align: top;
        }

        .indent { text-indent: 30pt; }
    </style>
</head>
<body>
    {{-- PAGE 1 --}}
    <div class="page">
        <p class="center"><strong>CONTRACT OF SERVICE</strong></p>
        <p><strong>KNOW ALL MEN BY THESE PRESENTS:</strong></p>
        <p>
            This CONTRACT OF SERVICE is made and entered into this _________________________,
            at the City of Pasay, by and between:
        </p>
        <p>
            The Department of Trade and Industry – {{ $orgCompanyName }} ({{ $orgBranchCode }}),
            with office address at {{ $orgCompanyAddress }} represented in this act by
            <strong>{{ strtoupper($signatory->position_name ?? 'OIC - Executive Director') }}</strong>
            <strong>{{ strtoupper($signatory_name ?? $signatory->full_name ?? '') }}</strong>
            and hereinafter referred to as the {{ $orgBranchCode }};
        </p>
        <p class="center"><strong>-and-</strong></p>
        <p>
            <strong>{{ strtoupper($employee_name ?? $employee->full_name ?? 'N/A') }}</strong>, of legal age, Filipino, and with postal address at
            <strong>{{ trim(preg_replace('/\s+/', ' ', $employee->permanent_address ?? 'N/A')) }}</strong>, and herein referred to as the
            <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong>;
        </p>
        <p class="center"><strong>WITNESSETH:</strong></p>
        <p>
            WHEREAS, the {{ $orgCompanyName }} ({{ $orgBranchCode }}) is in need of personnel who will
            be responsible in assisting in administrative tasks and be trained to perform technical
            tasks related to training implementation of the Center;
        </p>
        <p>
            WHEREAS, the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> has signified intention, to which the
            {{ $orgCompanyName }} ({{ $orgBranchCode }}) accepted to provide the services needed by the latter;
        </p>
        <p>
            WHEREAS, the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong>, by virtue of the education, experience and
            skills he possesses, is qualified to act as such and is willing to enter into a Service
            Contract with the {{ $orgCompanyName }} ({{ $orgBranchCode }});
        </p>
        <p>
            WHEREAS, the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> hereby attests that she is not related within
            the third degree of consanguinity to the hiring authority; that she has not been previously
            dismissed from government service by reason of an administrative offense;
        </p>
        <p>
            NOW, THEREFORE, for and in consideration of the foregoing premises, the parties hereby
            execute this Contract, subject to the following terms and conditions:
        </p>
        <ol class="numbered-clauses">
            <li>
                {{ $orgCompanyName }} ({{ $orgBranchCode }}) hereby contracted the services of the
                <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> for the period
                {{ $contract_period }} and may be renewed subject to performance evaluation.
            </li>
            <li>
                That the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> shall be paid a monthly Service Fee of
                {{ $salary_in_words }} (Php {{ $salary_amount_formatted }}) - SG {{ $salary_grade }}, Step {{ $salary_step }} plus 20% premium,
                upon submission of monthly accomplishment reports, inclusive of applicable withholding taxes,
                certification of satisfactory performance and inclusive of the following deliverables, subject
                to accounting and auditing rules and regulations which shall be paid in two terms every 10th
                and 25th of each month.
            </li>
        </ol>
    </div>

    {{-- PAGE 2 --}}
    <div class="page">
        <ol class="numbered-clauses" start="3">
            <li>
                Any increments implemented by Department of Budget and Management (DBM) in a yearly schedule
                for salary increases applicable to civilian personnel in the National Government Agencies (NGA)
                shall also apply to the active Contract of Service (COS) personnel upon renewal of contract and
                subject to availability of funds.
            </li>
            <li>
                That the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> shall report from Monday to Friday, flexible time from
                7:00 AM – 9:00 AM to 4:00 PM – 6:00 PM (8-hour or 40-hour workweek) observing an alternative work
                arrangement as the Office shall implement.
            </li>
            <li>
                That the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> shall perform the following functions under the {{ $orgCompanyName }}
                ({{ $orgBranchCode }}):
                <ol class="sub-clauses" type="a">
                    @foreach($contract_functions as $function)
                        <li>{{ $function }}</li>
                    @endforeach
                </ol>
            </li>
            <li>
                That in cases where the {{ $orgCompanyName }} ({{ $orgBranchCode }}) directs the
                <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> to conduct local travel, the existing rates of per diem, hotel allowances and other
                travel expenses under Executive Order 248, s. 1995 shall be made applicable.
            </li>
            <li>
                That in cases where the {{ $orgCompanyName }} ({{ $orgBranchCode }}) directs the
                <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> to provide the necessary support in urgent activities and during special events and
                projects, she will be entitled to collect overtime pay for her overtime services subject to
                existing applicable rules and regulations.
            </li>
        </ol>
    </div>

    {{-- PAGE 3 --}}
    <div class="page-3">
        <ol class="numbered-clauses" start="8">
            <li>
                That it is understood that there exists no employer-employee relationship between the parties
                to this agreement, that the services of the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> under this agreement shall
                not be entitled to the benefits being enjoyed by the regular personnel of the Department.
            </li>
            <li>That in case of absences/tardiness/undertime, deductions in pay shall be made accordingly.</li>
            <li>
                That the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> shall not at any time communicate to any person or entity any
                confidential information acquired in the course of the services as stipulated in the signed
                Confidentiality and Non-Disclosure Undertaking.
            </li>
            <li>
                That the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> shall submit a copy of her drug test result conducted by a
                government testing laboratory once in every six-month contract.
            </li>
            <li>
                That this Contract may be terminated prior to {{ $contract_end_formatted }} under the following circumstances:
                <ol class="sub-clauses" type="a">
                    <li>If the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> elects to terminate her services with 30-day notice period;</li>
                    <li>If the program/project/activities for which the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> was hired is cancelled or if there are no more funds to justify the continued hiring of the Project Management Specialist;</li>
                    <li>If the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> falls short of the standards in terms of performing the assigned duties and responsibilities;</li>
                    <li>If the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> fails to submit her drug test results within the period of this contract.</li>
                    <li>If the <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> worker violates any policy being implemented by the {{ $orgCompanyName }} ({{ $orgBranchCode }}); and</li>
                    <li>Any other justifiable reason.</li>
                </ol>
            </li>
        </ol>
        <p style="margin-top: 6px;">
            IN WITNESS WHEREOF, the Parties have hereunto affixed their signature on the date and place first
            above mentioned.
        </p>
        <div class="signature-section">
            <table>
                <tr>
                    <td>
                        <div class="signature-line"><strong>{{ strtoupper($signatory_name ?? $signatory->full_name ?? '') }}</strong></div>
                        <div class="signature-title"><strong>{{ $signatory->position_name ?? 'OIC - Executive Director' }}</strong></div>
                    </td>
                    <td>
                        <div class="signature-line"><strong>{{ strtoupper($employee_name ?? $employee->full_name ?? 'N/A') }}</strong></div>
                        <div class="signature-title"><strong>{{ $employee->position_name ?? 'N/A' }}</strong></div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="witness-label"><strong>SIGNED in the presence of:</strong></td>
                </tr>
                <tr>
                    <td>
                        <div class="signature-line witness-line"><strong>{{ strtoupper($witness1_name ?? $witness1->full_name ?? 'N/A') }}</strong></div>
                        <div class="signature-title"><strong>{{ $witness1->position_name ?? 'WITNESS 1' }}</strong></div>
                    </td>
                    <td>
                        <div class="signature-line witness-line"><strong>{{ strtoupper($witness2_name ?? $witness2->full_name ?? 'N/A') }}</strong></div>
                        <div class="signature-title"><strong>{{ $witness2->position_name ?? 'WITNESS 2' }}</strong></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- PAGE 4 --}}
    <div class="page-ack">
        <p class="center"><strong>ACKNOWLEDGEMENT</strong></p>
        <p>
            Republic of the Philippines&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)<br>
            City of {{ $notary_city !== '' ? $notary_city : '_____________________' }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;) S.S
        </p>
        <p class="indent">
            BEFORE ME, a Notary Public for and in the City of {{ $notary_city !== '' ? $notary_city : '_____________________' }}, on this _______ day of _______, personally
            appeared the following with their competent evidence of identity:
        </p>
        <table class="ack-table">
            <tr>
                <td width="35%"><strong>Name</strong></td>
                <td width="35%"><strong>Competent Evidence of Identity</strong></td>
                <td width="30%"><strong>Date/Place Issued</strong></td>
            </tr>
            <tr>
                <td><strong>{{ $signatory_name ?? $signatory->full_name ?? '' }}</strong></td>
                <td>____________________</td>
                <td>___________________</td>
            </tr>
            <tr>
                <td><strong>{{ $employee_name ?? $employee->full_name ?? 'N/A' }}</strong></td>
                <td>____________________</td>
                <td>___________________</td>
            </tr>
        </table>
        <p class="indent">
            Known to me and to me known to be the same persons who executed the foregoing instrument and acknowledged
            to me that the same is their free and voluntary act and deed.
        </p>
        <p class="indent">
            This instrument, consisting of four (4) pages including this page whereon this acknowledgement is written
            has been signed by the parties and their instrumental witnesses on each and every page thereof.
        </p>
        <p class="indent">
            WITNESS MY HAND AND SEAL, on the date and place first above written.
        </p>
        <p style="text-align: right; margin-top: 40px;">Notary Public</p>
        <p style="margin-top: 20px;">
            Doc. No.  _____<br>
            Page No. _____<br>
            Book No. _____<br>
            Series of {{ $current_year }}
        </p>
    </div>
</body>
</html>
