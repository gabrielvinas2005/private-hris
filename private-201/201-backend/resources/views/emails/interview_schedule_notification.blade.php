<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta charset="utf-8">
</head>

<body>
    <p>Hi {{ $interview_data[0]->name }},</p>

    @if ($interview_data[0]->type == 1)
    <p>You are receiving this email because your interview schedule has been posted.</p>
    @else
    <p>You are receiving this email because you are selected to participate as interview panelist.</p>
    @endif

    @if(!empty($interview_data[0]->description))
    <p style="margin: 1em 0;">
        {!! nl2br(e($interview_data[0]->description)) !!}
    </p>
    @endif

    <p><strong>Your Interview Schedule is:</strong></p>
    <div>
        @if ($interview_data[0]->start_date == $interview_data[0]->end_date)
        <p>Date: <b>{{ date('M d, Y', strtotime($interview_data[0]->start_date)) }}</b></p>
        @else
        <p>Date: <b>{{ date('M d, Y', strtotime($interview_data[0]->start_date)) . ' to ' . date('M d, Y', strtotime($interview_data[0]->end_date)) }}</b></p>
        @endif

        @if ($interview_data[0]->start_time == $interview_data[0]->end_time)
        <p>Time: <b>{{ date('h:i A', strtotime($interview_data[0]->start_time)) }}</b></p>
        @else
        <p>Time: <b>{{ date('h:i A', strtotime($interview_data[0]->start_time)) . ' to ' . date('h:i A', strtotime($interview_data[0]->end_time)) }}</b></p>
        @endif

        <p>Interview Location: <b>{{ $interview_data[0]->interview_location }}</b></p>
    </div>

    <p>Go to the login page of HRMS by clicking this <span><a href="{{ $actionUrl }}">link.</a></span></p>
    <br><br>
    <p><hr>
    <span class="break-all">
        <strong>CONFIDENTIAL: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed. If you have received this email in error please notify the system manager. This message contains confidential information and is intended only for the individual named. If you are not the named addressee you should not disseminate, distribute or copy this email. Please notify the sender immediately by email if you have received this email by mistake and delete this email from your system. If you are not the intended recipient you are notified that disclosing, copying, distributing or taking any action in reliance on the contents of this information is strictly prohibited.</strong><br />
        <em>{{ $actionUrl }}</em>
    </span>
    </p>
</body>

</html>