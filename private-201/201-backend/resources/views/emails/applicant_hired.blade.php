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
            Congratulations! We are pleased to inform you that you have been successfully hired/appointed for the
            position you applied for at the {{ strtoupper($orgCompanyName) }}.
        </p>

        <p class="bold">
            POSITION: {{ $emailData['position_name'] }} <br>
            OFFICE: {{ $emailData['department_name'] }}
        </p>

        <p>
            You are expected to appear and complete the required appointment formalities. Your employment shall be
            effective on the same date, and you must report to your office immediately after.
        </p>

        <p>
            Please make sure to coordinate with the Human Resource Management Section for your guidance on the next
            steps and appointment ceremony details.
        </p>

        <p>
            We congratulate you once again and wish you a long and successful career with us.
        </p>

        <p>Sincerely,</p>

        <br>
        <p class="bold underline">{{ $emailData['employee_name'] }}</p>
        <p class="bold">{{ $emailData['employee_position'] }}</p>
        <p>Mayor's Office</p>
    </div>

</body>

</html>
