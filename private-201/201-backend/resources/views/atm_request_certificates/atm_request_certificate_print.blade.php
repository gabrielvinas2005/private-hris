<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATM Request Certificate</title>
    <style>
        @page { 
            margin: 0;
            size: A4;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body { 
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000;
            line-height: 1.5;
            padding: 15mm 20mm 120mm 20mm;
            position: relative;
        }
        .letter-container {
            position: relative;
            min-height: 100%;
        }
        .header-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 10px;
            text-align: center;
        }
        .header-logo {
            max-height: 120px;
            width: 500px;
            margin: 0 auto;
            display: block;
        }
        .global-msme {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin: 10px 0 20px 0;
        }
        .date {
            margin-bottom: 20px;
            font-size: 12pt;
        }
        .recipient {
            margin-bottom: 15px;
            font-size: 12pt;
        }
        .recipient-name {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .salutation {
            margin-bottom: 15px;
            font-size: 12pt;
        }
        .letter-body {
            text-align: justify;
            margin: 20px 0;
            line-height: 1.6;
            font-size: 12pt;
        }
        .letter-body p {
            margin-bottom: 15px;
            text-indent: 0;
        }
        .letter-body p:last-child {
            margin-bottom: 0;
        }
        .employee-name {
            font-weight: bold;
        }
        .closing {
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 12pt;
        }
        .signature-section {
            margin-top: 50px;
        }
        .signature-name {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-size: 12pt;
        }
        .signature-position {
            font-size: 11pt;
        }
        .footer-section {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            padding: 20px 0;
            font-size: 9pt;
            text-align: center;
            color: #666;
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .footer-section img {
            max-width: 100%;
            max-height: 100px;
            height: auto;
            display: block;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    @foreach ($employees as $employee)
        <div class="letter-container">
            <!-- Header with Three Logos -->
            <div class="header-logos">
                @if(!empty($headerImg))
                    <img src="{{ $headerImg }}" alt="Header Logos" class="header-logo">
                @endif
            </div>

            <!-- Date -->
            <div class="date">{{ \Carbon\Carbon::now()->format('j F Y') }}</div>

            <!-- Recipient Information -->
            <div class="recipient">
                <div class="recipient-name">{{ $recipient_name ?? 'MS. ESTRELITA S. GERONIMO' }}</div>
                <div>Branch Manager</div>
                <div>LANDBANK of the Philippines</div>
                <div>PEZA Branch</div>
                <div>{{ $orgCompanyAddress }}</div>
            </div>

            <!-- Salutation -->
            <div class="salutation">{{ $salutation ?? 'Dear Ms. Geronimo:' }}</div>

            <!-- Letter Body -->
            <div class="letter-body">
                <p>This is to request for the inclusion of <span class="employee-name">{{ $employeeName }}</span>, new employee of the {{ $orgCompanyName }} ({{ $orgBranchCode }}), in the ATM Payroll of the Center.</p>
                <p>Your immediate and favorable action on this request will be highly appreciated.</p>
            </div>

            <!-- Closing -->
            <div class="closing">Very truly yours,</div>

            <!-- Signatory Section -->
            <div class="signature-section">
                <div class="signature-name">{{ $signatories['signatory'] }}</div>
                <div class="signature-position">{{ $signatories['position'] }}</div>
            </div>

            <!-- Footer -->
            @if(!empty($footerImg))
            <div class="footer-section">
                <img src="{{ $footerImg }}" alt="Footer">
            </div>
            @endif
        </div>
    @endforeach
</body>
</html>
