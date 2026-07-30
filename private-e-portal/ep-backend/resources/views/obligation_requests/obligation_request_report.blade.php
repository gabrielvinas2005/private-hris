<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obligation Request Report</title>
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
        <div class="title">Obligation Request Report</div>
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
                <th>Particular</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row->particular ?? 'N/A' }}</td>
                <td style="text-align: right;">₱{{ number_format($row->amount ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="signature-section">
        <div class="signature-line"></div>
        <div><strong>Authorized Signatory</strong></div>
    </div>
</body>
</html>