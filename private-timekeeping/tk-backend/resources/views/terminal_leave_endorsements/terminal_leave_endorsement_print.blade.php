<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Leave Endorsement</title>
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
        <div class="title">Terminal Leave Endorsement</div>
    </div>
    <div class="employee-info">
        <strong>{{ $employees[0]->name ?? 'Employee Name' }}</strong><br>
        {{ $employees[0]->position ?? 'Position' }}<br>
        {{ $employees[0]->department ?? 'Department' }}<br>
        <strong>Annual Salary:</strong> ₱{{ $annual_salary ?? 'N/A' }}<br>
        <strong>Salary in Words:</strong> {{ $salary_word ?? 'N/A' }} Pesos Only
    </div>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Income Type</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incomes as $index => $income)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $income->name ?? 'N/A' }}</td>
                <td style="text-align: right;">₱{{ number_format($income->amount ?? 0, 2) }}</td>
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