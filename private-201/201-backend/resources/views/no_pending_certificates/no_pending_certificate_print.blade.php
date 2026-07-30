<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>No Pending Certificates</title>

    <style>
        @page {
            margin: 0;
            size: A4;
        }
        html,
        body {
            height: 297mm;
            width: 210mm;
            margin: 0;
            padding: 0;
            font-family: "Times", serif;
            position: relative;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 150px;
            text-align: center;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 130px;
            text-align: center;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .content {
            margin-top: 160px;
            margin-bottom: 140px;
            padding: 0 20px;
            min-height: calc(100vh - 300px);
        }

        .date-header {
            text-align: right;
            margin-bottom: 20px;
            font-size: 12pt;
        }

        .certification-title {
            text-align: center;
            font-size: 20pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0 30px 0;
            letter-spacing: 2px;
        }

        .salutation {
            font-size: 12pt;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .certification-body {
            text-align: justify;
            font-size: 12pt;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .certification-body p {
            margin-bottom: 15px;
        }

        .employee-intro {
            margin-bottom: 15px;
        }

        .employee-name {
            font-weight: bold;
            text-transform: uppercase;
        }

        .numbered-list {
            margin: 15px 0 15px 40px;
            padding: 0;
        }

        .numbered-list li {
            margin-bottom: 10px;
            font-size: 12pt;
            line-height: 1.8;
        }

        .purpose-sentence {
            margin-top: 15px;
        }

        .signature-section {
            margin-top: 50px;
            text-align: right;
        }

        .signature-img {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 15px;
            margin-left: auto;
            margin-right: 0;
        }

        .signature-name {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .signature-position {
            font-size: 11pt;
        }

    </style>
</head>

<body>
    <header>
        @if(!empty($headerImg))
            <img src="{{ $headerImg }}" alt="Header" style="display: block; max-height: 140px; width: auto; max-width: 100%; object-fit: contain; margin: 0 auto;">
        @endif
    </header>

    <footer>
        @if(!empty($footerImg))
            <img src="{{ $footerImg }}" alt="Footer" style="max-width: 100%; max-height: 120px; width: auto; height: auto; object-fit: contain; display: block; margin: 0 auto;">
        @endif
    </footer>

    <!-- Main content -->
    @foreach ($employees as $employee)
        <div class="content">
            <div style="margin-left: 1in; margin-right: 1in; padding-top: 0.3in;">
                <!-- Date -->
                <div class="date-header">
                    {{ date('d F Y', strtotime(now())) }}
                </div>

                <!-- Certification Title -->
                <div class="certification-title">
                    CERTIFICATION
                </div>

                <!-- Salutation -->
                <div class="salutation">
                    To whom it may concern:
                </div>

                <!-- Body -->
                <div class="certification-body">
                    <p class="employee-intro">
                        This is to certify that <span class="employee-name">{{ $employee->name }}</span> {{ $employee->position }} of the {{ $orgCompanyName }}:
                    </p>

                    <ol class="numbered-list" type="1">
                        <li>has no pending administrative and/or criminal case;</li>
                        <li>has filed her Sworn Statement of Assets, Liabilities and Net worth as of <b>December 31, {{ date('Y', strtotime('-1 year')) }};</b></li>
                        <li>and, is not included in the list of notoriously undesirable employees.</li>
                    </ol>

                    <p class="purpose-sentence">
                        This certification is being issued upon the request of {{ $employee->gender_id == 2 ? 'Ms.' : ($employee->gender_id == 1 ? 'Mr.' : '') }} {{ $employee->last_name ?? '' }} {{ $purpose_text ?? 'in connection with the renewal of fidelity bond.' }}
                    </p>
                </div>

                <!-- Signature Section -->
                <div class="signature-section">
                    {{-- @if(!empty($signatureImg))
                        <div style="margin-bottom: -20px; margin-right:70px; text-align: right;">
                            <img src="{{ $signatureImg }}" alt="Signature" class="signature-img" style="display: inline-block;">
                        </div>
                    @endif --}}
                    <div class="signature-name">{{ $signatories['signatory'] }}</div>
                    <div class="signature-position">{{ $signatories['position'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
