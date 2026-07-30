<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion</title>
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
            line-height: 1.6;
            padding: 15mm 30mm;
        }

        .certificate-container {
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
            margin: 10px 0 15px 0;
        }

        .certification-title {
            text-align: center;
            font-size: 26pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 25px 0 35px 0;
            letter-spacing: 3px;
        }

        .certification-body {
            text-align: justify;
            margin: 25px 0;
            line-height: 1.9;
            font-size: 11.5pt;
        }

        .certification-body p {
            margin-bottom: 15px;
            text-align: justify;
            text-indent: 0.5in;
        }

        .certification-body .date-location {
            text-align: justify;
            text-indent: 0.5in;
        }

        .student-name {
            font-weight: bold;
            text-transform: uppercase;
        }

        .date-location {
            margin-top: 20px;
            font-size: 11.5pt;
        }

        .signature-section {
            margin-top: 60px;
            text-align: right;
        }

        .signature-name {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 12pt;
        }

        .signature-position {
            font-size: 10.5pt;
        }

        .footer-section {
            margin-top: 80px;
            padding-top: 60px;
        }
    </style>
</head>

<body>
    @foreach ($students as $student)
        <div class="certificate-container">
            <!-- Header with Three Logos Centered -->
            <div class="header-logos">
                @if (!empty($header_img))
                    <img src="{{ $header_img }}" alt="Header Logos" class="header-logo">
                @endif
            </div>


            <!-- Certification Title -->
            <div class="certification-title">CERTIFICATION</div>

            <!-- Main Body -->
            <div class="certification-body">
                <p>
                    This is to certify that <span class="student-name">{{ $honorific }}
                        {{ strtoupper($student->first_name . ' ' . ($student->middle_name ? $student->middle_name . ' ' : '') . $student->last_name) }}</span>
                    @if (!empty($course))
                        a student of <b>{{ $course }}
                    @endif
                    from </b><strong>{{ $student->school_name }}</strong>
                    has successfully completed the {{ $hours }}-hour of Internship
                    @if ($student->start_date && $student->end_date)
                        from <strong>{{ $student->start_date->format('F d, Y') }}</strong> to
                        <strong>{{ $student->end_date->format('F d, Y') }}</strong>.
                    @endif
                    {{ $rendered_intro }} services under the <strong>{{ $unit }}</strong> of the Department of
                    Trade and Industry - {{ $orgCompanyName }} ({{ $orgBranchCode }} GMEA).
                </p>

                <p>
                    {{ $thanks_body }}
                </p>

                <p>
                    This certificate is being issued to <span>{{ $honorific }}
                        {{ strtoupper($student->last_name) }}</span> as a requirement of {{ $course_possessive }}
                    course.
                </p>

                <div class="date-location">
                    @php
                        $endDate = $student->end_date ? $student->end_date : \Carbon\Carbon::parse($completion_date);
                        $day = $endDate->format('j');
                        $suffix =
                            $day == 1 || $day == 21 || $day == 31
                                ? 'st'
                                : ($day == 2 || $day == 22
                                    ? 'nd'
                                    : ($day == 3 || $day == 23
                                        ? 'rd'
                                        : 'th'));
                    @endphp
                    Given this {{ $day }}{{ $suffix }} day of {{ $endDate->format('F, Y') }} at {{ $orgCompanyAddress }}.
                </div>
            </div>

            <!-- Signature Section -->
            <div class="signature-section">
                <div class="signature-name">{{ $signatory }}</div>
                <div class="signature-position">{{ $position }}</div>
            </div>

            <!-- Footer -->
            <div class="footer-section">
                @if (!empty($footer_img))
                    <div style="text-align: center; margin-top: 40px; margin-bottom: 20px;">
                        <img src="{{ $footer_img }}" alt="Footer" style="max-width: 100%; height: auto;">
                    </div>
                @endif
            </div>
        </div>

        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
</body>

</html>
