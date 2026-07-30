<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .position-block { margin-top: 16px; }
        .position-title { font-weight: bold; margin-bottom: 4px; }
        .applicant-list { margin: 0; padding-left: 24px; }
        .panel-title { font-weight: bold; margin-top: 20px; margin-bottom: 8px; }
        .break-all { word-break: break-all; }
    </style>
</head>
<body>
    <p>Dear Panelists,</p>

    <p style="margin-top: 16px;">Greetings from the HR Unit!</p>

    <p style="margin-top: 16px;">
        This is to calendar the Panel Interview of the following shortlisted Plantilla candidates on
        <b>{{ $date_formatted }} at {{ $time_formatted }} onwards</b>:
    </p>

    <p class="panel-title">{{ $panel_interview_title }}</p>

    @foreach ($applicants_by_position as $group)
        <div class="position-block">
            <p class="position-title">{{ $group['position_label'] }} :</p>
            <ol class="applicant-list">
                @foreach ($group['applicants'] as $applicantName)
                    <li>{{ $applicantName }}</li>
                @endforeach
            </ol>
        </div>
    @endforeach

    @if (empty($applicants_by_position))
        <p style="margin-top: 16px;"><i>No applicants are currently assigned to this interview schedule.</i></p>
    @endif

    <p style="margin-top: 20px;">
        The Candidates' Profiles, Panel Interview Rating Forms and Behavior Event Interview (BEI) guide are all attached for your advance reference.
    </p>

    <p style="margin-top: 16px;">Please, for your guidance.</p>

    <p style="margin-top: 20px;">Thank you,</p>

    <p><hr>
    <span class="break-all">
        <strong>CONFIDENTIAL: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed. If you have received this email in error please notify the system manager. This message contains confidential information and is intended only for the individual named. If you are not the named addressee you should not disseminate, distribute or copy this email. Please notify the sender immediately by email if you have received this email by mistake and delete this email from your system. If you are not the intended recipient you are notified that disclosing, copying, distributing or taking any action in reliance on the contents of this information is strictly prohibited.</strong>
    </span>
    </p>
</body>
</html>
