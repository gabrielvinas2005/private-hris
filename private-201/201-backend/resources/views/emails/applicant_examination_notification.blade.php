<DOCTYPE html>
    <html lang="en-US">

    <head>
        <meta charset="utf-8">
    </head>

    <body>
        <p>Hi {{ $applicants[0]->name }},</p>

        <p>
            You are receiving this email because your online examination has been posted.
        </p>
        <p>
            Your Online Examination Schedule is:
        <div>

            @if ($applicants[0]->exam_date_from == $applicants[0]->exam_date_to)
                <p>Date: <b>{{ date('M d, Y', strtotime($applicants[0]->exam_date_from)) }}</b></p>
            @else
                <p>Date:
                    <b>{{ date('M d, Y', strtotime($applicants[0]->exam_date_from)) .
                        ' to ' .
                        date('M d, Y', strtotime($applicants[0]->exam_date_to)) }}</b>
                </p>
            @endif

            @if ($applicants[0]->exam_time_from == $applicants[0]->exam_time_to)
                <p>Time: <b>{{ date('h:i A', strtotime($applicants[0]->exam_time_from)) }}</b></p>
            @else
                <p>Time:
                    <b>{{ date('h:i A', strtotime($applicants[0]->exam_time_from)) .
                        ' to ' .
                        date('h:i A', strtotime($applicants[0]->exam_time_to)) }}</b>
                </p>
            @endif

        </div>
        </p>

        @if(!empty($applicants[0]->exam_instruction))
        <p style="margin-top: 1em;">
            <strong>Instructions:</strong><br>
            {!! nl2br(e($applicants[0]->exam_instruction)) !!}
        </p>
        @endif

        <p>Go to the login page of HRMS by clicking this <span><a href="{{ $actionUrl }}">link.</a></span></p>
        <br>
        <br>
        <p>
            <hr>
            <span class="break-all">
                <strong>CONFIDENTIAL: This email and any files transmitted with it are confidential and intended
                    solely for the use of the
                    individual or entity to whom they are addressed. If you have received this email in error please
                    notify the system
                    manager. This message contains confidential information and is intended only for the individual
                    named. If you are not
                    the named addressee you should not disseminate, distribute or copy this email. Please notify the
                    sender immediately by
                    email if you have received this email by mistake and delete this email from your system. If you
                    are not the intended
                    recipient you are notified that disclosing, copying, distributing or taking any action in
                    reliance on the contents of
                    this information is strictly prohibited.</strong><br />
                <em>{{ $actionUrl }}</em>
        </p>

    </body>

    </html>
