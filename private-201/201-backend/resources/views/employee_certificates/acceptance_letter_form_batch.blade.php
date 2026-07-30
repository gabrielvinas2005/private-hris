<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Acceptance Letters</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0 1in;
            font-size: 9pt;
        }

        header,
        footer {
            position: fixed;
            left: 0;
            right: 0;
            text-align: center;
            width: 100%;
        }

        header {
            top: 0;
        }

        footer {
            bottom: 0;
        }

        header img,
        footer img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            height: auto;
            object-fit: contain;
        }

        header img {
            max-height: 100px;
        }

        footer img {
            max-height: 80px;
        }

        .letter-page {
            page-break-after: always;
            margin-top: 140px;
            margin-bottom: 120px;
            padding-bottom: 20px;
        }

        .letter-page:last-child {
            page-break-after: auto;
        }

        p {
            line-height: 1.6;
            margin: 0 0 12px 0;
            text-align: justify;
        }

        .title {
            text-align: center;
            font-weight: bold;
            margin: 16px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .indent {
            text-indent: 0.5in;
        }

        .signature {
            margin-top: 50px;
            text-align: left;
        }

        .signature .name {
            font-weight: bold;
        }

        .date-line {
            margin-bottom: 10px;
            text-align: left;
        }

        .requirements-list {
            margin: 6px 0;
            padding-left: 0.5in;
        }

        .requirements-list li {
            line-height: 1.6;
            text-align: justify;
        }
    </style>
</head>

<body>
    <header>
        @if (!empty($header_img))
            <img src="{{ $header_img }}" alt="Header">
        @endif
    </header>

    <footer>
        @if (!empty($footer_img))
            <img src="{{ $footer_img }}" alt="Footer">
        @endif
    </footer>

    @foreach ($applicants_data as $letterData)
        @php
            $applicant = $letterData['applicant'];

            // Determine title based on gender_id (1 = Ms., 2 = Mr.)
            $title = $applicant->gender_id == 1 ? 'Ms.' : 'Mr.';

            // Format full name: First [Middle] Last (middle only if not null)
            $middlePart = !empty($applicant->middle_name) ? ' ' . $applicant->middle_name : '';
            $fullName = $applicant->first_name . $middlePart . ' ' . $applicant->last_name;
            $isPlantilla = (int) ($applicant->is_plantilla ?? 0) === 1;
            $appointmentText = $isPlantilla ? 'under Plantilla with monthly salary of' : 'under Contract of Service (COS) with monthly service fee of';
            $premiumText = $isPlantilla ? '' : ' plus 20% premium';
        @endphp

        <div class="letter-page">
            <div class="date-line">
                {{ \Carbon\Carbon::parse($letter_date)->format('d F Y') }}
            </div>

            <div>
                <strong>{{ $title }} {{ $fullName }}</strong>
                @if ($applicant->address)
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
                    @foreach ($addressLines as $line)
                        @if (!empty($line))
                            <br>{{ $line }}
                        @endif
                    @endforeach
                @endif
            </div>

            <p><strong>Dear {{ $title }} {{ $applicant->last_name ?? '' }},</strong></p>

            <p>Warm greetings from the DTI – {{ $orgCompanyName }}!</p>

            <p class="indent">
                After a careful and thorough evaluation of your qualifications and other competency requirements for the
                position of <strong>{{ $applicant->position_name ?? 'N/A' }} {{ $appointmentText }}
                    {{ $letterData['salary_in_words'] }} (Php
                    {{ number_format($letterData['salary_amount'], 2, '.', ',') }}) - SG
                    {{ $letterData['salary_grade_id'] }}, Step {{ $letterData['salary_step_id'] }}{{ $premiumText }},
                </strong> the Head of Agency of the {{ $orgCompanyName }} has accepted
                you to the said position.
            </p>

            <p class="indent">
                In this regard, the
                <strong>{{ $for_discussion->name ?? 'Office of the Executive Director (OED)' }}</strong> shall discuss
                with you the details of your duties and responsibilities as
                {{ $applicant->position_name ?? 'Technical Assistant' }}. Correspondingly, you are requested to submit
                the following pre-employment requirements to the {{ $orgBranchCode }}-AFMD Human Resource Section on or before
                <strong>{{ \Carbon\Carbon::parse($submit_date)->format('F d, Y') }} at
                    {{ \Carbon\Carbon::parse($submit_date)->format('g:i A') }}</strong>. Your onboarding shall take
                effect upon verification of the submitted documents, both original and photocopies and signing of the
                service contract.
            </p>

            <ul class="requirements-list">
                <li>NBI Clearance (updated, original copy)</li>
                <li>Drug Test Result (original copy)</li>
                <li>Medical Certificate (CSC Form No. 211, Revised 2025) signed by a government physician with the
                    attached laboratory results (all original copy)</li>
                <li>Vaccination Card</li>
                <li>Birth Certificate</li>
                <li>Marriage Certificate (if applicable)</li>
                <li>TIN ID or BIR Form No. 1902/1905/2316</li>
                <li>Authenticated Transcript of Records and Diploma</li>
                <li>Authenticated CSC Eligibility/PRC Board Rating (if applicable)</li>
                <li>Updated Personal Data Sheet (updated, CSC Form 212, Revised 2025 printed in legal size paper, 3
                    copies)</li>
                <li>CSC Form No. 212 Attachment - Work Experience Sheet (WES)</li>
                <li>Certificate of Employment from previous jobs</li>
                <li>Certificate of Training/seminars attended</li>
            </ul>

            <p class="indent">
                We would highly appreciate your acknowledgment and response on this offer <strong> on or before
                    {{ \Carbon\Carbon::parse($notify_date)->format('F d, Y') }}</strong>.
            </p>

            <p class="indent">
                Congratulations, and we look forward to having you onboard!
            </p>

            <div>
                <p>Sincerely,</p>
                <div class="signature">
                    <div class="name">{{ $signatory }}</div>
                    <div>{{ $position }}</div>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
