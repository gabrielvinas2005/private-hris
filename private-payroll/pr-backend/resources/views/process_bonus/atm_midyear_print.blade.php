<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATM Letter for LandBank</title>
    <style>
        @page {
            margin: 0.75in;
            size: A4;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            font-size: 12pt;
            color: #000;
        }
        .page-wrapper {
            position: relative;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
            margin-top: 0;
        }
        .header img {
            width: auto;
            max-width: 100%;
            max-height: 72px;
            height: auto;
            display: block;
            margin: 0 auto;
            object-fit: contain;
        }
        .letter-content {
            margin: 0;
            padding: 0;
            text-align: left;
        }
        .date {
            margin-bottom: 20px;
            margin-top: 0;
        }
        .date p {
            margin: 0;
            padding: 0;
        }
        .recipient {
            margin-bottom: 15px;
        }
        .recipient p {
            margin: 2px 0;
            padding: 0;
            line-height: 1.5;
        }
        .salutation {
            margin-bottom: 12px;
        }
        .salutation p {
            margin: 0;
            padding: 0;
        }
        .body-text {
            text-align: justify;
            margin-bottom: 12px;
            line-height: 1.8;
        }
        .body-text p {
            margin: 0 0 10px 0;
            padding: 0;
            text-align: justify;
        }
        .body-text p:last-child {
            margin-bottom: 0;
        }
        .body-text strong {
            font-weight: bold;
        }
        .closing {
            margin-top: 25px;
            margin-bottom: 0;
        }
        .closing p {
            margin: 0;
            padding: 0;
        }
        .signatory-section {
            margin-top: 30px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .signatory-name {
            font-weight: bold;
            margin: 0;
            padding: 0;
            margin-top: 5px;
        }
        .signatory-position {
            margin: 0;
            padding: 0;
            margin-top: 2px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            page-break-inside: avoid;
            page-break-before: avoid;
            padding-top: 10px;
            break-inside: avoid;
            break-before: avoid;
        }
        .footer img {
            width: auto;
            max-width: 100%;
            max-height: 85px;
            height: auto;
            display: block;
            margin: 0 auto;
            object-fit: contain;
            margin-top: 40px;
        }
        /* Prevent page breaks in critical sections */
        .letter-content {
            page-break-inside: auto;
        }
        .signatory-section + .footer {
            page-break-before: avoid;
        }
        /* Keep signatory and footer together */
        .signatory-section,
        .footer {
            orphans: 3;
            widows: 3;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="header">
            <img src="data:image/png;base64,{{ $image }}" alt="ATM Letter Header">
        </div>

        <div class="letter-content">
        <div class="date">
            <p>{{ date('j F Y') }}</p>
        </div>

        <div class="recipient">
            <p><strong>{{ strtoupper($signatories['personnel_name']) }}</strong></p>
            <p>{{ $signatories['personnel_position'] }}</p>
            <p>LANDBANK of the Philippines</p>
            <p>EDSA Extension Branch</p>
            <p>{{ $orgCompanyAddress }}</p>
        </div>

        <div class="salutation">
            <p>Dear {{ $signatories['personnel_name'] }}:</p>
        </div>

        <div class="body-text">
            @php
                $totalAmount = 0;
                foreach($employees as $employee) {
                    $totalAmount += $employee->net_pay ?? $employee->bonus_amount ?? 0;
                }
                
                $wholePart = (int)$totalAmount;
                $decimalPart = round(($totalAmount - $wholePart) * 100);
                
                if (class_exists('App\Helpers\NumberToWords')) {
                    $amountInWords = strtoupper(\App\Helpers\NumberToWords::convert($wholePart));
                    if ($amountInWords) {
                        $amountInWords .= ' PESOS';
                    } else {
                        $amountInWords = 'ZERO PESOS';
                    }
                    if ($decimalPart > 0) {
                        $amountInWords .= ' & ' . str_pad($decimalPart, 2, '0', STR_PAD_LEFT) . '/100';
                    } else {
                        $amountInWords .= ' & 00/100';
                    }
                } else {
                    $amountInWords = number_format($totalAmount, 2) . ' PESOS';
                }
                
                // Get the year for CY (Calendar Year)
                $bonusYear = '';
                if (isset($payroll->year_id)) {
                    $bonusYear = $payroll->year_id;
                } elseif (isset($payroll->years)) {
                    $bonusYear = $payroll->years;
                } elseif (isset($employees[0]->years)) {
                    $bonusYear = $employees[0]->years;
                } else {
                    // Try to get year from payroll period or default to current year
                    if (isset($payroll->release_date)) {
                        try {
                            $bonusYear = \Carbon\Carbon::parse($payroll->release_date)->format('Y');
                        } catch (\Exception $e) {
                            $bonusYear = date('Y');
                        }
                    } else {
                        $bonusYear = date('Y');
                    }
                }
            @endphp

            <p>We are submitting herewith the ATM Payroll of the officials and employees of the {{ $orgCompanyName }} for 
                <strong>payment on the first batch of Mid-Year Bonus CY {{ $bonusYear }}</strong> 
                with LDDAP-ADA No. ___________________________ dated ___________________________, {{ date('Y') }}, amounting to 
                <strong>{{ $amountInWords }} (Php {{ number_format($totalAmount, 2) }})</strong>. 
                We would like to request that payment of this payroll be on ___________________________, {{ date('Y') }}.</p>

            <p>Thank you very much for your accommodation.</p>
        </div>

        <div class="closing">
            <p>Very truly yours,</p>
        </div>

        <div class="signatory-section">
            <p class="signatory-name">{{ strtoupper($signatories['signatory_name']) }}</p>
            <p class="signatory-position">{{ $signatories['signatory_position'] }}</p>
        </div>

        @if(!empty($image2))
        <div class="footer">
            <img src="data:image/png;base64,{{ $image2 }}" alt="ATM Letter Footer">
        </div>
        @endif
    </div>
</div>
</body>
</html>
