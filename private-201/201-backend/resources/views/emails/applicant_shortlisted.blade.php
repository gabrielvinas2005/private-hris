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
            We are pleased to inform you that you have been <strong>shortlisted</strong> for the position cited above.
            Your qualifications have been reviewed and you have been selected to proceed in the hiring process.
        </p>

        <p>
            You will be notified of the next steps (e.g., examination, interview) in due course. We thank you for your
            interest and look forward to connecting with you.
        </p>

        <p>Sincerely,</p>

        <br>
        <p class="bold underline">{{ $emailData['employee_name'] }}</p>
        <p class="bold">{{ $emailData['employee_position'] }}</p>
        <p>Mayor's Office</p>
    </div>

</body>

</html>
