<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Order</title>
    <style>
        @page {
            size: A4;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            /* Reduced margins to minimize white space above header and below footer */
            margin: 0.5in 0.8in 0.4in 0.8in;
        }
        .page-wrapper {
            height: calc(297mm - 0.6in);
            max-height: calc(297mm - 0.6in);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .header {
            text-align: center;
            margin-bottom: 4px;
        }
        .header img {
            width: 70%;
            height: auto;
        }
        .memo-info {
            margin-bottom: 0;
        }
        .memo-number {
            font-weight: bold;
        }
        .series-year {
            margin-bottom: 0;
        }
        .date-line {
            text-align: right;
            margin-bottom: 0px;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 20px;
        }
        .content {
            flex: 1;
        }
        .section {
            margin-bottom: 4px;
            text-align: justify;
        }
        .section-label {
            font-weight: bold;
        }
        .underline-text {
            text-decoration: underline;
            font-weight: bold;
        }
        .travel-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px 0;
            font-size: 10pt;
        }
        .travel-table th,
        .travel-table td {
            border: 1px solid #000;
            padding: 8px 10px;
            text-align: center;
            vertical-align: middle;
        }
        .travel-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .travel-table td {
            min-height: 40px;
        }
        .signatory-section {
            margin-top: 30px;
        }
        .funds-section {
            margin-top: 24px;
            margin-bottom: 8px;
        }
        .funds-label {
            font-weight: bold;
        }
        .signatory-block {
            margin-top: 10px; /* space between label and name */
            margin-bottom: 12px;
        }
        .signatory-name {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }
        .signatory-title {
            font-size: 10pt;
        }
        .approval-section {
            margin-top: 12px;
        }
        .approval-label {
            font-weight: bold;
            margin-bottom: 6px;
        }
        .date-approval {
            margin-top: 20px;
        }
        .date-approval-line {
            display: inline-block;
            min-width: 150px;
            border-bottom: 1px solid #000;
            text-align: center;
            font-weight: bold;
        }
        .footer {
            margin-top: auto;
            text-align: center;
            padding-top: 38px;
            width: 100%;
        }
        .footer img {
            width: 100%;
            height: auto;
            max-height: 85px;
            object-fit: contain;
        }
        .indent {
            margin-left: 20px;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        {{-- Header with logos --}}
        <div class="header">
            @if(!empty($headerImage))
                <img src="data:image/png;base64,{{ $headerImage }}" alt="{{ $orgCompanyName }} Header">
            @else
                <img src="{{ asset('dist/img/travel_order_header.png') }}" alt="{{ $orgCompanyName }} Header">
            @endif
        </div>

        {{-- Memo info --}}
        <div class="memo-info">
            <div class="memo-number">{{ $orgBranchCode }} Memorandum Order No. {{ $memo_number ?? '____' }}</div>
            <div class="series-year">Series of {{ $series_year ?? date('Y') }}</div>
        </div>

        {{-- Date --}}
        <div class="date-line">
            Date: <u>{{ $date ?? '' }}</u>
        </div>

        {{-- Title --}}
        <div class="title">TRAVEL ORDER</div>

        {{-- Content sections --}}
        <div class="content">
            {{-- Section A --}}
            <div class="section">
                <span class="section-label">A.</span> The following official or personnel are hereby authorized to travel to the destination indicated opposite their respective names:
            </div>

            {{-- Travel Table --}}
            <table class="travel-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">Name<br>Designation/Official Station</th>
                        <th style="width: 25%;">Inclusive Dates of Travel</th>
                        <th style="width: 45%;">Destination</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div style="font-weight: bold;">{{ $employee_name ?? '' }}</div>
                            <div>{{ $designation ?? '' }}</div>
                        </td>
                        <td>{{ $travel_dates ?? '' }}</td>
                        <td>{{ $destination ?? '' }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- Section B --}}
            <div class="section">
                <span class="section-label">B.</span> Purpose of the Travel. The official or personnel are authorized to travel on <span class="underline-text">{{ $purpose ?? 'an approved personal leave' }}</span>.
            </div>

            {{-- Section C --}}
            <div class="section">
                <span class="section-label">C.</span> It is understood that the above-mentioned personnel will shoulder all expenses to be incurred during the travel thus relieving the Agency of any financial obligations.
            </div>

            {{-- Section D --}}
            <div class="section">
                <span class="section-label">D.</span> In the interest of service, {{ $employee_name ?? '[NAME]' }} will turn-over whatever pending matters and assignments to {{ $pronoun ?? 'her' }} designated Officer-In-Charge to ensure smooth function of the Agency during {{ $pronoun ?? 'her' }} absence.
            </div>

            {{-- Section E --}}
            <div class="section">
                <span class="section-label">E.</span> The travel documentation is being undertaken in accordance with the provision of Department Order No. 25-63, s. 2025.
            </div>

            {{-- Section F --}}
            <div class="section">
                <span class="section-label">F.</span> This Order takes effect immediately.
            </div>

            {{-- Funds Available --}}
            <div class="funds-section">
                <div class="funds-label">Funds Available:</div>
                <div class="signatory-block">
                    <div class="signatory-name">{{ !empty(trim($budget_officer ?? '')) ? $budget_officer : '________________________' }}</div>
                    <div class="signatory-title">{{ !empty(trim($budget_officer_position ?? '')) ? $budget_officer_position : 'Budget Officer' }}</div>
                </div>
            </div>

            {{-- Approval Section --}}
            <div class="approval-section">
                <div class="approval-label">Approved:</div>
                <div class="signatory-block">
                    <div class="signatory-name">{{ !empty(trim($approver_name ?? '')) ? $approver_name : '________________________' }}</div>
                    <div class="signatory-title">{{ !empty(trim($approver_position ?? '')) ? $approver_position : 'Designation' }}</div>
                </div>
            </div>

            {{-- Date of Approval --}}
            <div class="date-approval">
                Date of Approval: <span class="date-approval-line">{{ $date_of_approval ?? '' }}</span>
            </div>
        </div>

        {{-- Footer with logos --}}
        <div class="footer">
            @if(!empty($footerImage))
                <img src="data:image/png;base64,{{ $footerImage }}" alt="{{ $orgCompanyName }} Footer">
            @else
                <img src="{{ asset('dist/img/travel_order_footer.png') }}" alt="{{ $orgCompanyName }} Footer">
            @endif
        </div>
    </div>
</body>
</html>

