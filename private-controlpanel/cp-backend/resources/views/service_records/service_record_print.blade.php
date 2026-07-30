<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Record</title>
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
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Service Record</div>
    </div>
    <div class="company-info">
        <strong>{{ $employees[0]->name ?? 'Employee Name' }}</strong><br>
        {{ $employees[0]->position ?? 'Position' }}<br>
        {{ $employees[0]->department ?? 'Department' }}<br>
    </div>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>From</th>
                <th>To</th>
                <th>Designation</th>
                <th>Status</th>
                <th>Salary</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($service_records as $index => $record)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $record->from ?? 'N/A' }}</td>
                <td>{{ $record->to ?? 'N/A' }}</td>
                <td>{{ $record->designation ?? 'N/A' }}</td>
                <td>{{ $record->status ?? 'N/A' }}</td>
                <td style="text-align: right;">₱{{ number_format($record->salary ?? 0, 2) }}</td>
                <td>{{ $record->remarks ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="signature-section">
        <div class="signature-line"></div>
        <div><strong>{{ $signatories['signatory'] ?? 'Authorized Signatory' }}</strong></div>
    </div>
    <div class="footer">
        <p>Document No: {{ $footer['document_no'] ?? 'N/A' }} | Revision: {{ $footer['revision'] ?? 'N/A' }}</p>
    </div>
</body>
</html>