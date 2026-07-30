<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subsistence Report</title>
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
        <div class="title">Subsistence Report</div>
    </div>
    <div class="company-info">
        @if(isset($companies) && count($companies) > 0)
            <strong>{{ $orgCompanyName }}</strong><br>
            {{ $orgCompanyAddress }}
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Employee Name</th>
                <th>Position</th>
                <th>Full Time Days</th>
                <th>Full Time Service</th>
                <th>Part Time Days</th>
                <th>Part Time Service</th>
                <th>Prorate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row->name ?? 'N/A' }}</td>
                <td>{{ $row->position ?? 'N/A' }}</td>
                <td style="text-align: right;">{{ $row->full_time_days ?? 0 }}</td>
                <td style="text-align: right;">₱{{ number_format($row->full_time_service ?? 0, 2) }}</td>
                <td style="text-align: right;">{{ $row->part_time_days ?? 0 }}</td>
                <td style="text-align: right;">₱{{ number_format($row->part_time_service ?? 0, 2) }}</td>
                <td style="text-align: right;">₱{{ number_format($row->prorate ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="signature-section">
        <div class="signature-line"></div>
        <div><strong>{{ $signatories[0]['signatory_1'] ?? 'Authorized Signatory' }}</strong></div>
    </div>
</body>
</html>