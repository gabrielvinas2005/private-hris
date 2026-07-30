<DOCTYPE html>
    <html lang="en-US">

    <head>
        <meta charset="utf-8">
    </head>

    <body>
        <p>Hi {{ strtoupper($user_account[0]->name) }},</p>

        <p>
            An Overtime application by <b>{{ strtoupper($overtime_attachments[0]->name) }}</b> has been requested and
            needs to be review. Please login to HRIS to review the request.
        </p>

        <p>
            Best regards, <br>

            {{ config('app.name') }}
        </p>

    </body>

    </html>
