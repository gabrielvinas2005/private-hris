<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJT Certificate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 80px;
            height: auto;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }
        .content {
            margin: 20px 0;
        }
        .certificate-info {
            margin: 20px 0;
        }
        .info-row {
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        .value {
            display: inline-block;
        }
        .signature-section {
            margin-top: 50px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin-top: 30px;
        }
        .official-seal {
            text-align: center;
            margin-top: 30px;
        }
        .seal-circle {
            width: 80px;
            height: 80px;
            border: 2px solid #000;
            border-radius: 50%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Certificate of Completion</div>
        <div class="subtitle">On-The-Job Training Program</div>
    </div>

    <div class="content">
        <p>This is to certify that <strong>{{ $employees[0]->name ?? 'Student Name' }}</strong> has successfully completed the On-The-Job Training Program with the following details:</p>
        
        <div class="certificate-info">
            <div class="info-row">
                <span class="label">Training Program:</span>
                <span class="value">{{ $employees[0]->name ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Start Date:</span>
                <span class="value">{{ $employees[0]->date_start ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">End Date:</span>
                <span class="value">{{ $employees[0]->date_end ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Training Hours:</span>
                <span class="value">{{ $employees[0]->hours ?? 'N/A' }} hours</span>
            </div>
        </div>

        <p>This certification is being issued upon the request of the above-named student for whatever legal purpose it may serve.</p>
        
        <p>Issued this <strong>{{ date('jS') }}</strong> day of <strong>{{ date('F Y') }}</strong>.</p>
    </div>

    <div class="signature-section">
        <div class="signature-line"></div>
        <div style="text-align: center; margin-top: 5px;">
            <strong>{{ $employees[0]->signatory_name ?? 'Authorized Signatory' }}</strong><br>
            <em>{{ $employees[0]->signatory_position ?? 'Position' }}</em>
        </div>
    </div>

    <div class="official-seal">
        <div class="seal-circle">
            OFFICIAL<br>SEAL
        </div>
    </div>

    <div class="footer">
        <p>Document No: {{ $footer['document_no'] ?? 'N/A' }} | Revision: {{ $footer['revision'] ?? 'N/A' }}</p>
    </div>
</body>
</html> 