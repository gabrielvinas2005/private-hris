<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reimbursement Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 11px;
            line-height: 1.3;
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
            padding: 5px;
            text-align: left;
            font-size: 10px;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Reimbursement Report</div>
        <div class="subtitle">Communication Expenses</div>
    </div>

    <div class="company-info">
        @if(isset($company) && $company)
            <strong>{{ $company->name ?? 'Company Name' }}</strong><br>
            {{ $company->address ?? 'Company Address' }}
        @endif
    </div>

    <div class="report-info">
        <strong>Report Date:</strong> {{ date('F d, Y') }}<br>
        <strong>Total Employees:</strong> {{ $data->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Employee Name</th>
                <th>Position</th>
                <th>Department</th>
                <th>Month/Year</th>
                <th>Prepaid Invoice</th>
                <th>Prepaid Amount</th>
                <th>Postpaid Invoice</th>
                <th>Postpaid Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $employee)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $employee->full_name ?? 'N/A' }}</td>
                <td>{{ $employee->position ?? 'N/A' }}</td>
                <td>{{ $employee->department ?? 'N/A' }}</td>
                <td>{{ $employee->month_year ?? 'N/A' }}</td>
                <td style="text-align: center;">{{ $employee->prepaid_invoice_no ?? 'N/A' }}</td>
                <td style="text-align: right;">₱{{ number_format($employee->prepaid_amount ?? 0, 2) }}</td>
                <td style="text-align: center;">{{ $employee->postpaid_invoice_no ?? 'N/A' }}</td>
                <td style="text-align: right;">₱{{ number_format($employee->postpaid_amount ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" style="text-align: right;"><strong>TOTAL:</strong></td>
                <td></td>
                <td style="text-align: right;"><strong>₱{{ number_format($data->sum('prepaid_amount'), 2) }}</strong></td>
                <td></td>
                <td style="text-align: right;"><strong>₱{{ number_format($data->sum('postpaid_amount'), 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                <strong>{{ $signatories['signatory1'] ?? 'Authorized Signatory' }}</strong><br>
                <em>Position</em>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                <strong>{{ $signatories['signatory2'] ?? 'Accountant' }}</strong><br>
                <em>Accountant</em>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                <strong>{{ $signatories['signatory3'] ?? 'HR Manager' }}</strong><br>
                <em>Human Resources</em>
            </div>
        </div>
    </div>
</body>
</html> 