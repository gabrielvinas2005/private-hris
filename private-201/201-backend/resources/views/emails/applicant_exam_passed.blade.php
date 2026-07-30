<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .container { max-width: 700px; margin: 0 auto; }
        .bold { font-weight: bold; }
    </style>
</head>

<body>

<div class="container">
    <p><strong>Dear {{ $emailData['applicant_name'] }},</strong></p>

    <p>
        We are pleased to inform you that you have <strong>passed</strong> the following examination:
    </p>

    <p class="bold">
        Examination: {{ $emailData['exam_set'] ?? 'Online Examination' }}<br>
        Your rating: {{ number_format($emailData['exam_rating'] ?? 0, 2) }}%<br>
        Passing criteria: {{ number_format($emailData['passing_criteria'] ?? 0, 2) }}%
    </p>

    <p>
        You have met the passing criteria and may proceed to the next stage of the hiring process. 
        We will notify you of the next steps in due course.
    </p>

    <p>
        Thank you for your interest and congratulations on your result.
    </p>

    <p>
        Sincerely,<br>
        <strong>{{ $orgBranchCode }} Team</strong>
    </p>
</div>

</body>

</html>
