<!DOCTYPE html>
<html>

<head>
    <title>Applicant Confirmation</title>
</head>

<body>
    <p><u><strong>Dear {{ $applicant->fullname }},</strong></u></p>

    <p>
        Congratulations! This is with reference to your application for the job vacancy at the
        {{ strtoupper($orgCompanyName) }}.
    </p>

    <p>
        <strong>STATUS:</strong> For Hiring<br>
        <strong>POSITION:</strong> {{ $applicant->position }}<br>
        <strong>OFFICE:</strong>
    </p>

    <p>
        We are pleased to inform you that you have been selected for the <strong>For Hiring</strong> status
        for the position cited above. The Human Resource Management Section will provide further instructions
        regarding the next steps in the hiring process.
    </p>

    <p>
        Please look out for the schedule and other requirements that will be communicated to you.
    </p>

    <p>We congratulate you and wish you success as you move forward with the hiring process.</p>

    <p>Sincerely,</p>

    {{-- <p><strong>{{ $applicant->sender_name }}</strong><br>
        <em>{{ $applicant->designation }}</em><br>
        <em>{{ $applicant->office_sender }}</em> --}}

    <p>Best regards, <br>
        {{ config('app.name') }}</p>
</body>

</html>
