<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plantilla Report - Vacant Positions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 10px;
            line-height: 1.2;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 60px;
            height: auto;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 12px;
            margin: 3px 0;
        }
        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-info {
            margin: 15px 0;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
            font-size: 9px;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 30%;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 100%;
            margin-top: 20px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .page-break {
            page-break-before: always;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Plantilla Report - Vacant Positions</div>
        <div class="subtitle">As of {{ date('F d, Y') }}</div>
    </div>

    <div class="company-info">
        @if(isset($companies) && count($companies) > 0)
            <strong>{{ $orgCompanyName }}</strong><br>
            {{ $orgCompanyAddress }}
        @endif
    </div>

    <div class="report-info">
        <strong>Report Date:</strong> {{ date('F d, Y') }}<br>
        <strong>Total Vacant Positions:</strong> {{ $data->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Plantilla Code</th>
                <th>Position</th>
                <th>Salary Grade</th>
                <th>Salary Step</th>
                <th>Department</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $position)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $position->code ?? 'N/A' }}</td>
                <td>{{ $position->position ?? 'N/A' }}</td>
                <td style="text-align: center;">{{ $position->grade ?? 'N/A' }}</td>
                <td style="text-align: center;">{{ $position->step ?? 'N/A' }}</td>
                <td>{{ $position->department ?? 'N/A' }}</td>
                <td style="text-align: center;">{{ $position->status ?? 'Vacant' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                <strong>Authorized Signatory</strong><br>
                <em>Position</em>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                <strong>HR Manager</strong><br>
                <em>Human Resources</em>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                <strong>Date</strong><br>
                <em>{{ date('F d, Y') }}</em>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Document No: {{ $footer['document_no'] ?? 'N/A' }} | Revision: {{ $footer['revision'] ?? 'N/A' }}</p>
    </div>
</body>
</html> 