<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certification of Salary Deductions</title>
    <style>
        @page { margin: 0; size: A4; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0 1in;
            font-size: 12pt;
        }
        header, footer {
            position: fixed;
            left: 0;
            right: 0;
            text-align: center;
            width: 100%;
        }
        header { top: 10px; }
        footer { bottom: 10px; }
        header img {
            display: block;
            margin: 0 auto;
            max-height: 120px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
        }
        footer img {
            display: block;
            margin: 0 auto;
            max-height: 110px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
        }
        .content { margin-top: 170px; margin-bottom: 150px; }
        p { text-align: justify; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin: 18px 0; font-size: 11pt; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <header>
        @if(!empty($headerImg))
            <img src="{{ $headerImg }}" alt="Header">
        @endif
    </header>

    <footer>
        @if(!empty($footerImg))
            <img src="{{ $footerImg }}" alt="Footer">
        @endif
    </footer>

    <div class="content">
        <h3 style="text-align:center; letter-spacing: 2px; margin-bottom: 12px;">CERTIFICATION OF SALARY DEDUCTIONS</h3>

        <p style="text-indent: 0.4in;">
            This is to certify that <strong>{{ strtoupper($employee->full_name) }}</strong>, former <strong>{{ $employee->position_name ?? 'employee' }}</strong> of the <strong>{{ $orgCompanyName }}</strong>, has the following Year-to-Date (YTD) salary deductions based on the last three months:
        </p>

        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    @foreach($columns as $col)
                        <th>{{ $col }}</th>
                    @endforeach
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['month'] }}</td>
                        @foreach($columns as $col)
                            <td>{{ number_format($row[$col] ?? 0, 2) }}</td>
                        @endforeach
                        <td>{{ number_format($row['TOTAL'] ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 2 }}">No deduction records found for the last 3 months.</td>
                    </tr>
                @endforelse
                <tr>
                    <th>Total</th>
                    @foreach($columns as $col)
                        <th>{{ number_format($columnTotals[$col] ?? 0, 2) }}</th>
                    @endforeach
                    <th>{{ number_format($columnTotals['TOTAL'] ?? 0, 2) }}</th>
                </tr>
            </tbody>
        </table>

        @if($loans->isNotEmpty())
            <p style="margin-top: 6px;"><strong>Active Loans:</strong></p>
            <ul style="margin-top: 4px;">
                @foreach($loans as $loan)
                    <li>{{ $loan->name }} — Amortization: ₱{{ number_format($loan->amount, 2) }} | Balance: ₱{{ number_format($loan->balance, 2) }}</li>
                @endforeach
            </ul>
        @endif

        <p style="text-indent: 0.4in; margin-top: 14px;">
            This certification is issued upon request for record purposes. The accuracy of this document may be verified by emailing the Human Resource Section at {{ $orgCompanyEmail }}.
        </p>

        <p style="text-indent: 0.4in; margin-top: 10px;">
            Issued this {{ $issueDate->format('jS') }} day of {{ $issueDate->format('F Y') }} at {{ $orgCompanyAddress }}
        </p>

        <div style="margin-top: 50px; width: 100%; text-align: right;">
            <div style="display: inline-block; text-align: right;">
                <p style="margin: 0 0 4px 0; font-weight: bold;">{{ $signatory }}</p>
                <p style="margin: 0;">{{ $signatory_position }}</p>
            </div>
        </div>
    </div>
</body>
</html>

