<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $orgBranchCode }}-HRMP - Loan Application Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            background-color: white;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 20px;
        }
        .logo {
            max-height: 100px;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            line-height: 1.2;
        }
        .system-title {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .report-title {
            font-size: 18px;
            color: #1e3a8a;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th {
            background-color: #1e3a8a;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #1e3a8a;
            font-size: 12px;
        }
        td {
            padding: 12px 10px;
            border: 1px solid #e5e7eb;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        tr:nth-child(odd) {
            background-color: white;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        .status-approved {
            color: #059669;
            font-weight: bold;
            background-color: #d1fae5;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .status-pending {
            color: #d97706;
            font-weight: bold;
            background-color: #fef3c7;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }
        .summary {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #1e3a8a;
        }
        .summary h3 {
            margin: 0 0 10px 0;
            color: #1e3a8a;
            font-size: 16px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            padding: 5px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .summary-item:last-child {
            border-bottom: none;
            font-weight: bold;
            color: #1e3a8a;
        }

        @media print {
            body { margin: 0; padding: 15px; }
            .header { border-bottom: 2px solid #1e3a8a; }
            table { box-shadow: none; }
            .summary { background-color: #f8fafc; }
        }
    </style>
</head>
<body>
    <div class="header">
        @if($image)
            <img src="data:image/png;base64,{{ $image }}" alt="Logo" class="logo">
        @endif
        <div class="company-name">{{ strtoupper($orgCompanyName) }}</div>
        <div class="system-title">{{ $orgBranchCode }}-HRMP</div>
        <div class="report-title">LOAN APPLICATION REPORT</div>
    </div>

    <div class="summary">
        <h3>Report Summary</h3>
        <div class="summary-item">
            <span>Total Loan Applications:</span>
            <span>{{ $data->count() }}</span>
        </div>
        <div class="summary-item">
            <span>Approved Applications:</span>
            <span>{{ $data->where('status', 'Approved')->count() }}</span>
        </div>
        <div class="summary-item">
            <span>Pending Applications:</span>
            <span>{{ $data->where('status', 'Pending')->count() }}</span>
        </div>
        <div class="summary-item">
            <span>Total Loan Amount:</span>
            <span>{{ number_format($data->sum('loan_amount'), 2) }}</span>
        </div>
        <div class="summary-item">
            <span>Total Outstanding Balance:</span>
            <span>{{ number_format($data->sum('balance'), 2) }}</span>
        </div>
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
        <p><strong>This report was generated automatically by the {{ $orgBranchCode }}-HRMP System</strong></p>
        <p>Total Records: {{ $data->count() }} | Generated on: {{ date('F d, Y h:i A') }}</p>
        <p>For inquiries, please contact the HR Department</p>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>
