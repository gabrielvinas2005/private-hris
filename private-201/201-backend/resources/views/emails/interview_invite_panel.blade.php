<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
    </style>
</head>
<body>
    <p>{{ $salutation }}</p>

    <p style="margin-top: 20px;">Greetings from DTI-{{ $orgBranchCode }}, HR Unit!</p>

    <p style="margin-top: 10px;">
        DTI-{{ $orgBranchCode }} is inviting you for a Face-to-Face Panel Interview with the {{  }}'s Managerial Levels
        regarding your application for the {{ $position_applied }} position on {{ $date_formatted }} at
        {{ $time_formatted }} at {{ $interview_location }}.
    </p>

    <p style="margin-top: 10px;">Our office address is located at:</p>

    <p style="margin-top: 10px;">
        {{ $orgCompanyName }} ({{ $orgBranchCode }}), {{ $orgCompanyAddress }}
    </p>

    <p style="margin-top: 10px;">Please note of the following reminders:</p>

    <ol style="margin-top: 10px; padding-left: 22px;">
        <li style="margin-top: 10px;">Wear an office or appropriate (formal) attire.</li>
        <li style="margin-top: 10px;">
            Bring a valid ID, must be presented at the {{ $orgBranchCode }} lobby guard on duty upon entering the building.
            Please look for Ms. Anne/Ms. Cheska/Ms. Hazel of HR. The HR office is located at the 3rd Floor
            (straight ahead after the glass doors) of the Administrative and Financial Management Division (AFMD-HR)
        </li>
        <li style="margin-top: 10px;">
            Bring five (5) printed copies of your Letter of Intent, CV/Resume, Personal Data Sheet, and Work
            Experience Sheet, which might be requested by the interviewer/s.
        </li>
        <li style="margin-top: 10px;">
            Be on time and be at {{ $orgCompanyAddress }} at least 15 minutes before your scheduled interview.
        </li>
    </ol>

    <p style="margin-top: 20px;">
        Thank you and kindly confirm your attendance for the F2F interview on or before 5:00pm, on
        {{ $day_before_formatted}}.
    </p>

    <p style="margin-top: 20px;">See you there!</p>
</body>
</html>
