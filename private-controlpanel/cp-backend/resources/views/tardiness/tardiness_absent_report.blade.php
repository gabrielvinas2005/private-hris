<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tardiness Absent Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 16px; font-weight: bold; }
        .employee-info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #000; padding: 5px; font-size: 10px; }
        th { background: #f0f0f0; }
        .signature-section { margin-top: 40px; text-align: right; }
        .signature-line { border-top: 1px solid #000; width: 200px; margin: 30px 0 0 auto; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Tardiness Absent Report</div>
    </div>
    <div class="employee-info">
        <strong>Date Range:</strong> {{ $date_from }} to {{ $date_to }}
    </div>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Employee Name</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tardiness as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row->name ?? 'N/A' }}</td>
                <td>{{ $row->date ?? 'N/A' }}</td>
                <td style="text-align: center;">{{ $row->status ?? 'Absent' }}</td>
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