<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appearance Certificate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 80px;
            height: auto;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
        }
        .content {
            margin: 20px 0;
        }
        .employee-info {
            margin: 20px 0;
        }
        .info-row {
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        .value {
            display: inline-block;
        }
        .salary-info {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }
        .signature-section {
            margin-top: 50px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin-top: 30px;
        }
        .official-seal {
            text-align: center;
            margin-top: 30px;
        }
        .seal-circle {
            width: 80px;
            height: 80px;
            border: 2px solid #000;
            border-radius: 50%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Appearance Certificate</div>
    </div>

    <div class="content">
        <p>This is to certify that <strong>{{ $employees[0]->name ?? 'Employee Name' }}</strong> is a bona fide employee of this office with the following details:</p>
        
        <div class="employee-info">
            <div class="info-row">
                <span class="label">Name:</span>
                <span class="value">{{ $employees[0]->name ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Position:</span>
                <span class="value">{{ $employees[0]->position ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Department:</span>
                <span class="value">{{ $employees[0]->department ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Employment Type:</span>
                <span class="value">{{ $employees[0]->employment_type ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Date Hired:</span>
                <span class="value">{{ $employees[0]->date_hired ?? 'N/A' }}</span>
            </div>
        </div>

        <div class="salary-info">
            <h4>Salary Information:</h4>
            <div class="info-row">
                <span class="label">Monthly Salary:</span>
                <span class="value">₱{{ number_format($employees[0]->salary ?? 0, 2) }}</span>
            </div>
            <div class="info-row">
                <span class="label">Annual Salary:</span>
                <span class="value">₱{{ $annual_salary ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Salary in Words:</span>
                <span class="value">{{ $salary_word ?? 'N/A' }} Pesos Only</span>
            </div>
        </div>

        @if(isset($incomes) && count($incomes) > 0)
        <div class="additional-incomes">
            <h4>Additional Incomes:</h4>
            <table>
                <thead>
                    <tr>
                        <th>Income Type</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($incomes as $income)
                    <tr>
                        <td>{{ $income->name }}</td>
                        <td>₱{{ number_format($income->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <p>This certification is being issued upon the request of the above-named employee for whatever legal purpose it may serve.</p>
        
        <p>Issued this <strong>{{ date('jS') }}</strong> day of <strong>{{ date('F Y') }}</strong>.</p>
    </div>

    <div class="signature-section">
        <div class="signature-line"></div>
        <div style="text-align: center; margin-top: 5px;">
            <strong>{{ $signatories['signatory'] ?? 'Authorized Signatory' }}</strong><br>
            <em>{{ $signatories['position'] ?? 'Position' }}</em>
        </div>
    </div>

    <div class="official-seal">
        <div class="seal-circle">
            OFFICIAL<br>SEAL
        </div>
    </div>
</body>
</html> 