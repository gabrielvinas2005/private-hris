<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certification of Last Salary Received</title>
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
            font-family: 'Montserrat', 'Helvetica', Arial, sans-serif;
            font-size: 12pt;
            color: #000;
            line-height: 1.8;
            padding: 20mm 25mm 20mm 25mm;
            position: relative;
            min-height: 100vh;
        }
        .certificate-container {
            position: relative;
            min-height: calc(100vh - 40mm);
            display: flex;
            flex-direction: column;
        }
        .header-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
            text-align: center;
        }
        .header-logo {
            max-height: 120px;
            width: 500px;
            margin: 0 auto;
            display: block;
        }
        .content-wrapper {
            flex: 1;
        }
        .footer-section {
            margin-top: auto;
            padding-top: 20px;
            text-align: center;
            position: absolute;
            bottom: -20mm;
            left: -25mm;
            right: -25mm;
            width: calc(100% + 50mm);
            padding-bottom: 10mm;
        }
        .footer-img {
            max-width: 100%;
            height: auto;
        }
        .certification-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 20px 0 30px 0;
            letter-spacing: 1px;
        }
        .certification-body {
            text-align: justify;
            margin: 25px 0;
            line-height: 2;
            font-size: 12pt;
        }
        .certification-body p {
            margin-bottom: 20px;
            text-indent: 0;
        }
        .employee-name {
            font-weight: bold;
            text-transform: uppercase;
        }
        .amount-words {
            font-weight: bold;
        }
        .amount-number {
            font-weight: bold;
        }
        .final-settlement {
            font-weight: bold;
        }
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .date-section {
            text-align: left;
        }
        .signature-name-section {
            text-align: right;
        }
        .signature-name {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 12pt;
            text-transform: uppercase;
        }
        .signature-position {
            font-size: 11pt;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="content-wrapper">
            <!-- Header with Logos -->
            @if(!empty($header_img))
            <div class="header-logos">
                <img src="{{ $header_img }}" alt="Header Logos" class="header-logo">
            </div>
            @endif

            <!-- Certification Title -->
            <div class="certification-title">Certification of Last Salary Received</div>

            <!-- Main Body -->
            <div class="certification-body">
            <p>
                This is to certify <span class="employee-name">{{ strtoupper($employee->name_prefix ?? 'MR.') }} {{ strtoupper($employee->full_name) }}</span>, 
                former <span class="employee-name">{{ strtoupper($employee->position_name ?? 'EMPLOYEE') }}</span> 
                who was separated from the <span class="employee-name">{{ strtoupper($organization_name) }}</span> 
                effective <strong>{{ \Carbon\Carbon::parse($employee->separation_date)->format('F d, Y') }}</strong> 
                will receive the amount of <strong><span class="amount-words">{{ $amount_in_words }}</span> 
                <span class="amount-number">(P{{ number_format($employee->net_pay, 2, '.', ',') }})</span></strong>, 
                in full and <strong><span class="final-settlement">final settlement</span></strong> with the Center.
            </p>

            @if($include_bonus_info)
            <p>
                This is also to certify further that {{ $employee->name_prefix ?? 'Mr.' }} {{ explode(' ', $employee->full_name)[count(explode(' ', $employee->full_name)) - 1] }} received his 13th month pay (Mid-Year Bonus) and 14th month pay (Year-End Bonus) both for CY {{ $bonus_year }}.
            </p>
            @endif

            @if($include_transfer_info && !empty($transfer_organization))
            <p>
                This certification is being issued upon the request of {{ $employee->name_prefix ?? 'Mr.' }} {{ explode(' ', $employee->full_name)[count(explode(' ', $employee->full_name)) - 1] }} in connection with his transfer to the {{ strtoupper($transfer_organization) }}@if($transfer_date) effective {{ \Carbon\Carbon::parse($transfer_date)->format('F d, Y') }}@endif.
            </p>
            @endif
            </div>

            <!-- Signature and Date Section -->
            <div class="signature-section">
            <div class="date-section">
                @php
                    $issueDate = \Carbon\Carbon::now();
                    $day = $issueDate->format('j');
                    $suffix = $day == 1 || $day == 21 || $day == 31 ? 'st' : ($day == 2 || $day == 22 ? 'nd' : ($day == 3 || $day == 23 ? 'rd' : 'th'));
                @endphp
                {{ $day }}{{ $suffix }} {{ $issueDate->format('F Y') }}
            </div>
            <div class="signature-name-section">
                <div class="signature-name">{{ strtoupper($signatory) }}</div>
                <div class="signature-position">{{ $position }}</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    @if(!empty($footer_img))
    <div class="footer-section">
        <img src="{{ $footer_img }}" alt="Footer" class="footer-img">
    </div>
    @endif
</body>
</html>
