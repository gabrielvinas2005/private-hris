<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .break-all { word-break: break-all; }
    </style>
</head>
<body>
    <p>{{ $salutation }}</p>
    <p style="margin-top: 20px;">Greetings from DTI-{{ $orgBranchCode }}!</p>
    <p style="margin-top: 10px;">This is to invite you for a <b>virtual technical interview on {{ $date_formatted }} at {{ $time_formatted }} <b style="text-decoration: underline; font-style: italic;"> onwards</b> via MS Teams platform.</b></p>
    <p style="margin-top: 10px;">MS Teams link details will be sent upon confirmation of virtual attendance.</p>
    <p style="margin-top: 10px;"><strong>Reminders :</strong></p>
    <ol style="margin-top: 20px;">
        <li style="margin-top: 10px;">Please wear an office or appropriate (formal) attire.</li>
        <li style="margin-top: 10px;">Enter the link 15 minutes prior the schedule. You will be placed in the waiting room and will be admitted in the interview room once the applicant scheduled ahead of you is done. Wait patiently for your turn to be admitted.</li>
        <li style="margin-top: 10px;">Before you enter the virtual meeting room, please rename your profile with your Full Name.</li>
        <li style="margin-top: 10px;">This will be <b style="text-decoration: underline;">a consecutive set of interviews</b> and you might not be admitted inside at the exact time of your schedule but please wait patiently to be admitted inside the virtual interview room.</li>
        <li style="margin-top: 10px;">Also, please ensure strong internet connection and avoid a noisy environment once you have been admitted in the interview room.</li>
    </ol>
    <p style="margin-top: 10px;">{{ $confirmation_line }}</p>
    <p style="margin-top: 20px; text-decoration: underline; font-style: italic;"><b>Also, we do not encourage rescheduling as interview schedules are being done based on the availability of the interviewers.</b></p>
    <p style="margin-top: 20px;">Thank you.</p>
    <p><hr>
    <span class="break-all">
        <strong>CONFIDENTIAL: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed. If you have received this email in error please notify the system manager. This message contains confidential information and is intended only for the individual named. If you are not the named addressee you should not disseminate, distribute or copy this email. Please notify the sender immediately by email if you have received this email by mistake and delete this email from your system. If you are not the intended recipient you are notified that disclosing, copying, distributing or taking any action in reliance on the contents of this information is strictly prohibited.</strong>
    </span>
    </p>
</body>
</html>
