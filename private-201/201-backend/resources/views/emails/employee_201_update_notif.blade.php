<DOCTYPE html>
    <html lang="en-US">

    <head>
        <meta charset="utf-8">
    </head>

    <body>
        <p>Hi {{ strtoupper($employee_hr) }},</p>

        <p>
            An update to <b>{{ strtoupper($employee_name) }}</b> 201 record has been processed.
        </p>

        <p>
            Best regards, <br>

            {{ config('app.name') }}
        </p>

    </body>

    </html>
