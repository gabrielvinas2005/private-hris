<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regret Letter</title>
    <style>
        @page {
            margin: 1in;
            size: A4;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            line-height: 1.6;
        }
        header, footer {
            position: fixed;
            left: 1in;
            right: 1in;
            text-align: center;
            width: calc(100% - 2in);
        }
        header {
            top: 0;
            z-index: 1000;
        }
        footer {
            bottom: 0;
            z-index: 1000;
        }
        header img, footer img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            height: auto;
            object-fit: contain;
        }
        .content {
            margin-top: 160px;
            margin-bottom: 170px;
            text-align: justify;
            page-break-inside: avoid;
        }
        .date-line {
            margin-bottom: 15px;
            text-align: left;
        }
        .recipient-block {
            margin-bottom: 15px;
            text-align: left;
        }
        .recipient-block strong {
            font-size: 11pt;
        }
        .recipient-address {
            margin-top: 3px;
            line-height: 1.3;
            text-align: left;
        }
        p {
            line-height: 1.6;
            margin: 0 0 10px 0;
            text-align: justify;
            text-justify: inter-word;
        }
        .signature {
            margin-top: 25px;
            text-align: left;
        }
        .signature p {
            text-align: left;
        }
        .signature .name {
            font-weight: bold;
            margin-top: 10px;
        }
        .signature .position {
            margin-top: 2px;
        }
    </style>
</head>
<body>
    @if(!empty($headerImg))
    <header>
        <img src="{{ $headerImg }}" alt="Header">
    </header>
    @endif

    @if(!empty($footerImg))
    <footer>
        <img src="{{ $footerImg }}" alt="Footer">
    </footer>
    @endif

    <div class="content">
        @php
            // Format date as "d F Y" (e.g., "16 July 2024")
            $formattedDate = \Carbon\Carbon::now()->format('d F Y');

            // Determine title based on gender_id (1 = Ms., 2 = Mr.)
            $title = ($applicant->gender_id == 1) ? 'Ms.' : 'Mr.';

            // Format full name: First [Middle] Last (middle only if not null)
            $middlePart = !empty($applicant->middle_name) ? ' ' . $applicant->middle_name : '';
            $fullName = $applicant->first_name . $middlePart . ' ' . $applicant->last_name;

            // Format position with department if available
            // Format: "for the [Position] position of the [Department] of the Center"
            $positionText = '';
            if ($applicant->position_name) {
                $positionText = $applicant->position_name;
                if ($applicant->department_name) {
                    $positionText .= ' position of the ' . $applicant->department_name;
                } else {
                    $positionText .= ' position';
                }
            }
        @endphp

        <div class="date-line">
            {{ $formattedDate }}
        </div>

        <div class="recipient-block">
            <strong>{{ strtoupper($title . ' ' . $fullName) }}</strong>
            @if($applicant->address)
                @php
                    // Format address - preserve line breaks and split by newlines or commas
                    $address = $applicant->address;
                    // Replace newlines with a placeholder first
                    $address = str_replace(["\r\n", "\n\r", "\n", "\r"], '|||', $address);
                    // Split by placeholder or comma
                    $addressLines = preg_split('/\|\|\||,/', $address, -1, PREG_SPLIT_NO_EMPTY);
                    $addressLines = array_map('trim', $addressLines);
                    $addressLines = array_filter($addressLines); // Remove empty lines
                @endphp
                <div class="recipient-address">
                    @foreach($addressLines as $line)
                        @if(!empty($line))
                            {{ $line }}<br>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <p><strong>Dear {{ $title }} {{ $applicant->last_name }},</strong></p>

        <p>Greetings from the DTI {{ $orgCompanyName }}!</p>

        <p>We would like to thank you for your application{{ $positionText ? ' for the ' . $positionText : '' }} of the Center. We greatly appreciated your interest
            in joining our agency and the time you have invested in applying for the said role.</p>

        <p>After careful consideration, we have decided to pursue other candidates whom we have reviewed to have best meet our needs at this time.</p>

        <p>Once more, thank you for putting the Center on your list of priorities. Please feel free to apply again with us for open positions in the future.</p>

        <p>We wish you success in all your undertakings.</p>

        <div class="signature">
            <p>Very truly yours,</p>
            <div class="name">MARIA ANTONIETTE S. ZOILO</div>
            <div class="position">Administrative Officer V</div>
        </div>
    </div>
</body>
</html>
