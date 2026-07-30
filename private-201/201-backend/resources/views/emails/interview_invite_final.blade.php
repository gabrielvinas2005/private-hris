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
        DTI-{{ $orgBranchCode }} is inviting you for a <b>Face-to-Face Final Interview with the {{  }}'s Head of Agency on
        {{ $date_long_formatted }}, {{ strtoupper($time_formatted) }} at the {{ $orgBranchCode }} OED.</b>
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
            Please look for Ms. Anne/Hazel/Cheska of HR. The HR office is located at the 3rd Floor (straight
            ahead after the glass doors) of the Administrative, Financial, and Management Division (AFMD-HR).
        </li>
        <li style="margin-top: 10px;">
            Bring printed copies of your Letter of Intent, CV/Resume, Personal Data Sheet, Work Experience
            Sheet, which might be requested by the interviewer/s.
        </li>
        <li style="margin-top: 10px;">
            Be on time and be at {{ $orgCompanyAddress }} at least 15 minutes before your scheduled interview.
        </li>
        <li style="margin-top: 10px;">
            The final interview is strictly conducted face-to-face. Online interviews may not be accommodated,
            in line with the preference of the Head of Agency to personally assess candidates.
        </li>
        <li style="margin-top: 10px;">
            Requests for rescheduling may not be accommodated as this may cause delays in the recruitment
            process and we also consider the availability of the interviewer/s.
        </li>
    </ol>

    <p style="margin-top: 20px;">
        Thank you and kindly confirm your attendance for the F2F interview on or before 11:59pm,
        {{ $day_before_formatted }}.
    </p>

    <p style="margin-top: 20px;">See you there!</p>
</body>
</html>
