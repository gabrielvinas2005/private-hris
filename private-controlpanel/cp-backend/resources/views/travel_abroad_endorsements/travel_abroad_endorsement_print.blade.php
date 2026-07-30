<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Abroad Endorsement</title>
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
        <div class="title">Travel Abroad Endorsement</div>
    </div>
    <div class="employee-info">
        <strong>{{ $employees[0]->name ?? 'Employee Name' }}</strong><br>
        {{ $employees[0]->position ?? 'Position' }}<br>
        {{ $employees[0]->department ?? 'Department' }}
    </div>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Destination</th>
                <th>Purpose</th>
                <th>Date From</th>
                <th>Date To</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($travel_details as $index => $travel)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $travel->destination ?? 'N/A' }}</td>
                <td>{{ $travel->purpose ?? 'N/A' }}</td>
                <td>{{ $travel->date_from ?? 'N/A' }}</td>
                <td>{{ $travel->date_to ?? 'N/A' }}</td>
                <td style="text-align: center;">{{ $travel->status ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="signature-section">
        <div class="signature-line"></div>
        <div><strong>{{ $signatories['signatory'] ?? 'Authorized Signatory' }}</strong></div>
    </div>
</body>
</html> 