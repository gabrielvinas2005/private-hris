<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;600;700&display=swap">
    <title>Monetization Report</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            font-family: 'Source Sans 3', 'Arial', sans-serif;
            font-size: 12px;
            background: #fff;
            color: #1a1a1a;
        }

        .page {
            width: 8.5in;
            margin: 0 auto;
            padding: 0.35in 0.7in 0.45in 0.7in;
            background: #fff;
        }

        /* ── HEADER ── */
        .header {
            display: flex;
            align-items: center;
            gap: 18px;
            padding-bottom: 14px;
            border-bottom: 2.5px solid #1a3a6b;
            margin-bottom: 22px;
        }

        .header-logo {
            flex-shrink: 0;
        }

        .header-logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .header-text {
            flex: 1;
            text-align: center;
        }

        .header-text .republic {
            font-size: 10.5px;
            letter-spacing: 0.04em;
            color: #444;
            margin-bottom: 3px;
        }

        .header-text .agency-name {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 18px;
            font-weight: 700;
            color: #1a3a6b;
            line-height: 1.2;
            margin-bottom: 3px;
        }

        .header-text .agency-address {
            font-size: 10px;
            color: #555;
            font-style: italic;
        }

        /* spacer on the right to balance the logo */
        .header-spacer {
            flex-shrink: 0;
            width: 80px;
        }

        /* ── DOCUMENT TITLE ── */
        .doc-title-block {
            text-align: center;
            margin-bottom: 18px;
        }

        .doc-title {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #1a3a6b;
        }

        .doc-subtitle {
            font-size: 11px;
            color: #333;
            margin-top: 4px;
        }

        .doc-subtitle strong {
            color: #1a1a1a;
        }

        /* ── MAIN TABLE ── */
        .payroll-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .payroll-table thead tr {
            background-color: #1a3a6b;
            color: #fff;
        }

        .payroll-table th {
            padding: 9px 10px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-align: center;
            border: 1px solid #1a3a6b;
        }

        .payroll-table td {
            padding: 5px 8px;
            font-size: 11px;
            border: 1px solid #b0bec5;
            vertical-align: middle;
        }

        .payroll-table tbody tr:nth-child(even) {
            background-color: #f4f7fb;
        }

        .payroll-table tbody tr:hover {
            background-color: #e8eef6;
        }

        .payroll-table .col-id { text-align: center; }
        .payroll-table .col-name { text-align: left; }
        .payroll-table .col-days { text-align: right; }
        .payroll-table .col-amount { text-align: right; }

        .payroll-table tfoot tr {
            background-color: #1a3a6b;
            color: #fff;
            font-weight: 700;
        }

        .payroll-table tfoot td {
            padding: 9px 10px;
            border: 1px solid #1a3a6b;
            font-size: 12px;
        }

        /* ── SIGNATORIES ── */
        .signatories-section {
            page-break-inside: avoid;
            break-inside: avoid;
            border-top: 2px solid #1a3a6b;
            padding-top: 14px;
        }

        .sig-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .sig-table td.signatory-cell {
            width: 50%;
            padding: 12px 14px;
            vertical-align: top;
            border: 1px solid #ccc;
        }

        .cert-label {
            font-size: 10px;
            font-weight: 700;
            color: #1a3a6b;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .cert-text {
            font-size: 10.5px;
            color: #333;
            line-height: 1.5;
            margin-bottom: 22px;
        }

        .cert-text u {
            font-weight: 600;
            color: #1a1a1a;
        }

        .sig-name {
            font-size: 11.5px;
            font-weight: 700;
            color: #1a1a1a;
            padding-bottom: 2px;
            border-bottom: 1.5px solid #1a1a1a;
            display: inline-block;
            min-width: 80%;
        }

        .sig-position {
            font-size: 10px;
            color: #555;
            font-style: italic;
            margin-top: 5px;
            text-align: center;
        }

        .certified-list {
            margin: 10px 0 18px 0;
            padding-left: 14px;
        }

        .certified-list li {
            font-size: 10.5px;
            color: #333;
            line-height: 1.7;
        }

        @media print {
            body { margin: 0; }
            .page { margin: 0; padding: 0.35in 0.7in; box-shadow: none; }
            .signatories-section,
            .payroll-table { page-break-inside: avoid; }
        }
    </style>
</head>

<body>
<div class="page">

    <!-- HEADER -->
    <div class="header">
        <div class="header-logo">
            <img src="data:image/png;base64,{{ $image }}" alt="Agency Logo">
        </div>
        <div class="header-text">
            <p class="republic">Republic of the Philippines</p>
            <p class="agency-name">{{ $company[0]->name }}</p>
            <p class="agency-address">{{ $company[0]->address }}</p>
        </div>
        <div class="header-spacer"></div>
    </div>

    <!-- DOCUMENT TITLE -->
    <div class="doc-title-block">
        <p class="doc-title">Monetization Payroll</p>
        @if(!empty($monetization[0]->month) && !empty($monetization[0]->year_id))
            <p class="doc-subtitle">
                For the month of <strong>{{ $monetization[0]->month }}, {{ $monetization[0]->year_id }}</strong>
            </p>
        @endif
    </div>

    <!-- PAYROLL TABLE -->
    @php $totalAmount = 0; @endphp
    <table class="payroll-table">
        <thead>
            <tr>
                <th style="width:15%;">Employee ID</th>
                <th style="width:40%;">Name</th>
                <th style="width:20%;">Total Days</th>
                <th style="width:25%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($monetization as $mon)
                <tr>
                    <td class="col-id">{{ $mon->employee_no }}</td>
                    <td class="col-name">{{ $mon->employee_name }}</td>
                    <td class="col-days">{{ number_format($mon->total_days, 2, '.', ',') }}</td>
                    <td class="col-amount">{{ number_format($mon->amount, 2, '.', ',') }}</td>
                </tr>
                @php $totalAmount += $mon->amount; @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right; letter-spacing:0.06em;">TOTAL</td>
                <td style="text-align:right;">{{ number_format($totalAmount, 2, '.', ',') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- SIGNATORIES -->
    <div class="signatories-section">
        <table class="sig-table">
            <tr>
                <!-- Box 1 -->
                <td class="signatory-cell">
                    <p class="cert-label">(1) Certification of Services Rendered</p>
                    <p class="cert-text">
                        I CERTIFY on my official oath that the payroll is correct and that the
                        services have been duly rendered as stated.
                    </p>
                    <p class="sig-name">{{ $signatories[0]->signatory_1 }}</p>
                    <p class="sig-position">{{ $signatories[0]->signatory_position_1 }}</p>
                </td>
                <!-- Box 3 -->
                <td class="signatory-cell">
                    <p class="cert-label">(3) Certification of Payment</p>
                    <p class="cert-text">
                        I CERTIFY on my official oath that I have paid to each employee whose name
                        appears above, the amount set opposite his name, he having presented
                        Residence Certificate.
                    </p>
                    <p class="sig-name">{{ $signatories[0]->signatory_3 }}</p>
                    <p class="sig-position">{{ $signatories[0]->signatory_position_3 }}</p>
                </td>
            </tr>
            <tr>
                <!-- Box 2 -->
                <td class="signatory-cell">
                    <p class="cert-label">(2) Approval</p>
                    <p class="cert-text">
                        APPROVED, payable from appropriation for
                        <u>{{ $signatories[0]->approval_name ?? 'MS. BARNES, JANET COLASITO' }}</u>
                    </p>
                    <p class="sig-name">{{ $signatories[0]->signatory_2 }}</p>
                    <p class="sig-position">{{ $signatories[0]->signatory_position_2 }}</p>
                </td>
                <!-- Box 4 -->
                <td class="signatory-cell">
                    <p class="cert-label">(4) Certified</p>
                    <ol class="certified-list">
                        <li>Adequate available funds in the amount of <strong>{{ number_format($totalAmount, 2, '.', ',') }}</strong></li>
                        <li>Expenditure properly certified</li>
                        <li>Supporting documents appearing legal and proper</li>
                        <li>Account codes proper</li>
                    </ol>
                    <p class="sig-name">{{ $signatories[0]->signatory_4 }}</p>
                    <p class="sig-position">{{ $signatories[0]->signatory_position_4 }}</p>
                </td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>