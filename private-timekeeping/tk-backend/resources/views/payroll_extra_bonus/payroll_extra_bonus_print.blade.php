<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Extra Bonus Report</title>
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
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Payroll Extra Bonus Report</div>
        <div class="subtitle">Year: {{ $extra_bonus_payrolls->first()->year_id ?? 'N/A' }}</div>
    </div>

    <div class="company-info">
        @if(isset($companies) && count($companies) > 0)
            <strong>{{ $orgCompanyName }}</strong><br>
            {{ $orgCompanyAddress }}
        @endif
    </div>

    <div class="report-info">
        <strong>Report Date:</strong> {{ date('F d, Y') }}<br>
        <strong>Total Employees:</strong> {{ $extra_bonus_payrolls->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Employee No.</th>
                <th>Name</th>
                <th>Position</th>
                <th>Department</th>
                <th>Bonus Type</th>
                <th>Monthly Salary</th>
                <th>Bonus Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($extra_bonus_payrolls as $index => $employee)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $employee->employee_no ?? 'N/A' }}</td>
                <td>{{ $employee->name ?? 'N/A' }}</td>
                <td>{{ $employee->position ?? 'N/A' }}</td>
                <td>{{ $employee->department ?? 'N/A' }}</td>
                <td>{{ $employee->extra_bonus_type ?? 'N/A' }}</td>
                <td style="text-align: right;">₱{{ number_format($employee->salary ?? 0, 2) }}</td>
                <td style="text-align: right;">₱{{ number_format($employee->amount ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6" style="text-align: right;"><strong>TOTAL:</strong></td>
                <td style="text-align: right;"><strong>₱{{ number_format($extra_bonus_payrolls->sum('salary'), 2) }}</strong></td>
                <td style="text-align: right;"><strong>₱{{ number_format($extra_bonus_payrolls->sum('amount'), 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                <strong>{{ $signatories['signatory_1'] ?? 'Authorized Signatory' }}</strong><br>
                <em>{{ $signatories['signatory_position_1'] ?? 'Position' }}</em>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                <strong>{{ $signatories['signatory_2'] ?? 'Accountant' }}</strong><br>
                <em>{{ $signatories['signatory_position_2'] ?? 'Accountant' }}</em>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div style="margin-top: 5px;">
                <strong>{{ $signatories['signatory_3'] ?? 'HR Manager' }}</strong><br>
                <em>{{ $signatories['signatory_position_3'] ?? 'Human Resources' }}</em>
            </div>
        </div>
    </div>
</body>
</html> 