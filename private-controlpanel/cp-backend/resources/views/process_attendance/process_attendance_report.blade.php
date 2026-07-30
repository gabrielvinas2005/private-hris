<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Attendance Report</title>
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
        <div class="title">Process Attendance Report</div>
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
                <th>Employee No.</th>
                <th>Date</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($time_data as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row->name ?? 'N/A' }}</td>
                <td>{{ $row->employee_no ?? 'N/A' }}</td>
                <td>{{ $row->date ?? 'N/A' }}</td>
                <td>{{ $row->time_in ?? 'N/A' }}</td>
                <td>{{ $row->time_out ?? 'N/A' }}</td>
                <td>{{ $row->status ?? 'Present' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="signature-section">
        <div class="signature-line"></div>
        <div><strong>Authorized Signatory</strong></div>
    </div>
    <div class="footer">
        <p>Document No: {{ $footer['document_no'] ?? 'N/A' }} | Revision: {{ $footer['revision'] ?? 'N/A' }}</p>
    </div>
</body>
</html>