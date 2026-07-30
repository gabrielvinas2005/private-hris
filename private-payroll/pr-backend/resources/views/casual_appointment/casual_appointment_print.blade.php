<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plantilla of Casual Appointment</title>
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
        <div class="title">Plantilla of Casual Appointment</div>
    </div>
    <div class="company-info">
        @if(isset($company) && $company)
            <strong>{{ $company->name ?? $orgCompanyName }}</strong><br>
            {{ $company->address ?? $orgCompanyAddress }}
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Employee Name</th>
                <th>Position</th>
                <th>Department</th>
                <th>Employment Type</th>
                <th>Nature of Appointment</th>
                <th>New Salary</th>
                <th>Date Appointed</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dtl as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row->first_name ?? 'N/A' }} {{ $row->middle_name ?? '' }} {{ $row->last_name ?? '' }}</td>
                <td>{{ $row->position ?? 'N/A' }}</td>
                <td>{{ $row->department ?? 'N/A' }}</td>
                <td>{{ $row->employment_type ?? 'N/A' }}</td>
                <td>{{ $row->nature_of_appointment ?? 'N/A' }}</td>
                <td style="text-align: right;">₱{{ number_format($row->new_salary ?? 0, 2) }}</td>
                <td>{{ $row->date_position_appointed ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="signature-section">
        <div class="signature-line"></div>
        <div><strong>{{ $signatories['signatory1'] ?? 'Authorized Signatory' }}</strong></div>
    </div>
</body>
</html>