<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pag-IBIG Loan Report</title>
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
        <div class="title">Pag-IBIG Loan Report</div>
        <div class="subtitle">Payroll Period: {{ $dtl->first()->month_year ?? 'N/A' }}</div>
    </div>

    <div class="company-info">
        @if(isset($companies) && count($companies) > 0)
            <strong>{{ $orgCompanyName }}</strong><br>
            {{ $orgCompanyAddress }}
        @endif
    </div>

    <div class="report-info">
        <strong>Report Date:</strong> {{ $date ?? date('F d, Y') }}<br>
        <strong>Total Employees:</strong> {{ $dtl->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Employee No.</th>
                <th>Name</th>
                <th>Position</th>
                <th>Pag-IBIG No.</th>
                <th>Monthly Salary</th>
                <th>Pag-IBIG Contribution</th>
                <th>Loan Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dtl as $index => $employee)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $employee->employee_no ?? 'N/A' }}</td>
                <td>{{ $employee->name ?? 'N/A' }}</td>
                <td>{{ $employee->position ?? 'N/A' }}</td>
                <td>{{ $employee->pagibig_no ?? 'N/A' }}</td>
                <td style="text-align: right;">₱{{ number_format($employee->salary ?? 0, 2) }}</td>
                <td style="text-align: right;">₱{{ number_format($employee->pagibig ?? 0, 2) }}</td>
                <td style="text-align: right;">₱{{ number_format($employee->mp2_amount ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" style="text-align: right;"><strong>TOTAL:</strong></td>
                <td style="text-align: right;"><strong>₱{{ number_format($dtl->sum('salary'), 2) }}</strong></td>
                <td style="text-align: right;"><strong>₱{{ number_format($dtl->sum('pagibig'), 2) }}</strong></td>
                <td style="text-align: right;"><strong>₱{{ number_format($dtl->sum('mp2_amount'), 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                <strong>{{ $signatory ?? 'Authorized Signatory' }}</strong><br>
                <em>{{ $position ?? 'Position' }}</em>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                <strong>{{ $accsignatory ?? 'Accountant' }}</strong><br>
                <em>Accountant</em>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                <strong>Date</strong><br>
                <em>{{ $date ?? date('F d, Y') }}</em>
            </div>
        </div>
    </div>
</body>
</html> 