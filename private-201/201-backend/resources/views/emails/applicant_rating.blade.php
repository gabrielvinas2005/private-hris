<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 20px;
        }

        .header p {
            margin: 0;
        }

        .bold {
            font-weight: bold;
        }

        .underline {
            text-decoration: underline;
        }

        .spacing {
            margin-top: 20px;
        }

        hr {
            margin-top: 40px;
        }

        .confidential {
            font-size: 12px;
            color: gray;
        }
    </style>
</head>

<body>

    <div class="container">
        <p class="bold underline">DATE: {{ $emailData['date_sent'] }}</p>
        <p class="bold">{{ $emailData['employee_name'] }}</p>
        <p>{{ $orgCompanyAddress }}</p>
        <br>
        <p><strong>Dear {{ $emailData['applicant_name'] }},</strong></p>

        <p>
            This is with reference to your application for the job vacancy posting at the {{ strtoupper($orgCompanyName) }}.
        </p>

        <p class="bold">
            POSITION: {{ $emailData['position_name'] }} <br>
            OFFICE: {{ $emailData['department_name'] }}
        </p>

        <p>
            Your interest in applying for the position cited above is duly appreciated. However, upon thorough
            assessment of the applicants who passed the written examination and interview, we regret to inform you that
            you have not been selected for the position.
        </p>

        <p>
            We genuinely thank you for your time to meet with us. We wish you success in your career aspirations and
            encourage you to apply for future job vacancy opportunities to be posted.
        </p>

        <p>Sincerely,</p>

        <br>
        <p class="bold underline">{{ $emailData['employee_name'] }}</p>
        <p class="bold">{{ $emailData['employee_position'] }}</p>
        <p>Mayor's Office</p>
    </div>

</body>

</html>