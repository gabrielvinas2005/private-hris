<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RATA Payroll Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 16px; font-weight: bold; }
        .company-info { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #000; padding: 5px; font-size: 10px; }
        th { background: #f0f0f0; }
        .signature-section { margin-top: 40px; text-align: right; }
        .signature-line { border-top: 1px solid #000; width: 200px; margin: 30px 0 0 auto; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">RATA Payroll Report</div>
    </div>
    <div class="company-info">
        @if(isset($company) && count($company) > 0)
            <strong>{{ $company[0]->name ?? 'Company Name' }}</strong><br>
            {{ $company[0]->address ?? 'Company Address' }}
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Employee Name</th>
                <th>Employee No.</th>
                <th>Position</th>
                <th>RA Amount</th>
                <th>TA Amount</th>
                <th>Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rata as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row->name ?? 'N/A' }}</td>
                <td>{{ $row->employee_no ?? 'N/A' }}</td>
                <td>{{ $row->position ?? 'N/A' }}</td>
                <td style="text-align: right;">₱{{ number_format($row->ra_amount ?? 0, 2) }}</td>
                <td style="text-align: right;">₱{{ number_format($row->ta_amount ?? 0, 2) }}</td>
                <td style="text-align: right;">₱{{ number_format($row->net_amount ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="signature-section">
        <div class="signature-line"></div>
        <div><strong>{{ $signatories['signatory_1'] ?? 'Authorized Signatory' }}</strong></div>
    </div>
</body>
</html>