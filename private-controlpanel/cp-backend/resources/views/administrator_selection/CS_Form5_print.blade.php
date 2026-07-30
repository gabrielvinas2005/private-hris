<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CS Form No. 5 - Certificate of Assumption to Duty</title>
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
        }
        .subtitle {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }
        .content {
            margin: 20px 0;
        }
        .signature-section {
            margin-top: 50px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin-top: 30px;
        }
        .date {
            margin-top: 20px;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="title">CERTIFICATE OF ASSUMPTION TO DUTY</div>
        <div class="subtitle">(CS Form No. 5)</div>
    </div>

    <div class="content">
        <p>This is to certify that <strong>{{ $position_title }}</strong> has assumed the duties and responsibilities of his/her position in this Office effective <strong>{{ $date }}</strong>.</p>
        
        <p>This certification is being issued upon the request of the above-named official for whatever legal purpose it may serve.</p>
        
        <p>Issued this <strong>{{ date('jS') }}</strong> day of <strong>{{ date('F Y') }}</strong> at <strong>{{ $location }}</strong>, Philippines.</p>
    </div>

    <div class="signature-section">
        <div class="signature-line"></div>
        <div style="text-align: center; margin-top: 5px;">
            <strong>{{ $author }}</strong><br>
            <em>{{ $agency_name }}</em>
        </div>
    </div>

    <div class="official-seal">
        <div class="seal-circle">
            OFFICIAL<br>SEAL
        </div>
    </div>
</body>
</html> 