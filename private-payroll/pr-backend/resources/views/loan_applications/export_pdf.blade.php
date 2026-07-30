<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $orgBranchCode }}-HRMP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 15px;
            background-color: white;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-height: 80px;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            line-height: 1.2;
        }
        .system-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th {
            background-color: #1e3a8a;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #1e3a8a;
            font-size: 11px;
        }
        td {
            padding: 10px 8px;
            border: 1px solid #e5e7eb;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        tr:nth-child(odd) {
            background-color: white;
        }
        tr:hover {
            background-color: #f3f4f6;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        .status-approved {
            color: #059669;
            font-weight: bold;
        }
        .status-pending {
            color: #d97706;
            font-weight: bold;
        }
        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($image)
            <img src="data:image/png;base64,{{ $image }}" alt="Logo" class="logo">
        @endif
        <div class="company-name">{{ strtoupper($orgCompanyName) }}</div>
        <div class="system-title">LOAN APPLICATION REPORT</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Loan Type</th>
                <th>Voucher No.</th>
                <th>Amount</th>
                <th>Amortization</th>
                <th>Payment</th>
                <th>Balance</th>
                <th>Effectivity Date</th>
                <th>End Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $loan)
                <tr>
                    <td>{{ $loan->employee_name }}</td>
                    <td>{{ $loan->loan_type }}</td>
                    <td>{{ $loan->voucher_number }}</td>
                    <td class="amount">{{ number_format($loan->loan_amount, 2) }}</td>
                    <td class="amount">{{ number_format($loan->loan_amortization, 2) }}</td>
                    <td class="amount">{{ number_format($loan->payment ?? 0, 2) }}</td>
                    <td class="amount">{{ number_format($loan->balance, 2) }}</td>
                    <td>{{ \Carbon\Carbon::parse($loan->effectivity_date)->format('m-d-Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($loan->end_date)->format('m-d-Y') }}</td>
                    <td class="{{ $loan->status === 'Approved' ? 'status-approved' : 'status-pending' }}">
                        {{ $loan->status }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This report was generated automatically by the {{ $orgBranchCode }}-HRMP System.</p>
        <p>Total Records: {{ $data->count() }} | Generated on: {{ date('F d, Y h:i A') }}</p>
    </div>
</body>
</html>
