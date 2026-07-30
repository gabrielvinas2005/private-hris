<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Rendered Service</title>
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
        header img, footer img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            height: auto;
            object-fit: contain;
        }
        header img { max-height: 120px; }
        footer img { max-height: 120px; }
        .content { margin-top: 170px; margin-bottom: 170px; }
        p { line-height: 1.5; margin: 0 0 12px 0; text-align: justify; }
        .title { text-align: center; font-weight: bold; margin: 16px 0; text-transform: uppercase; }
        .sig { margin-top: 50px; }
        .sig-line { margin: 0 0 4px 0; border-bottom: 1px solid #000; width: 220px; }
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
        <p style="text-align:right; color:rgb(17, 16, 16); margin-bottom: 40px;">
            {{ $issueDate->format('j F Y') }}
        </p>

        <div class="title">CERTIFICATION</div>

        <p>
            This is to certify that <strong>{{ $employee->full_name }}</strong>, <strong>{{ $employee->position_name ?? 'Employee' }}</strong> under the <strong>{{ $employee->department_name ?? 'Department' }}</strong>, had rendered very satisfactory services covering the period <strong>{{ $coverStart->format('F d, Y') }} to {{ $coverEnd->format('F d, Y') }}</strong>.
        </p>

        <p>
            This certification is being issued for accounting and auditing purposes.
        </p>

        <div class="sig" style="margin-top: 50px;">
            <p><strong>Noted By:</strong></p>
            <div style="margin-top: 40px;">
                <div class="sig-line"></div>
                <p style="margin: 0;"><strong>{{ $notedBy }}</strong></p>
                <p style="margin: 0;">{{ $notedByPosition }}</p>
            </div>
        </div>

        <div class="sig" style="margin-top: 60px;">
            <p><strong>Approved By:</strong></p>
            <div style="margin-top: 40px;">
                <div class="sig-line"></div>
                <p style="margin: 0;"><strong>{{ $approvedBy }}</strong></p>
                <p style="margin: 0;">{{ $approvedByPosition }}</p>
            </div>
        </div>
    </div>
</body>
</html>

