<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Birthday of the Month</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f7; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.1); }
        h1 { color: #1f2937; font-size: 20px; margin-bottom: 16px; }
        p { color: #4b5563; font-size: 14px; line-height: 1.5; }
        .highlight { font-weight: bold; color: #111827; }
        .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Birthday of the Month</h1>
        <p>{{ $emailMessage }}</p>

        <p>
            <span class="highlight">Employee:</span>
            {{ $employeeName }}
        </p>
        <p>
            <span class="highlight">Birthday:</span>
            {{ $birthMonthDay }}
        </p>

        @if ($recipientType === 'hr')
            <p>Please extend your greetings to the celebrant.</p>
        @else
            <p>We wish you a wonderful birthday celebration!</p>
        @endif

        <div class="footer">
            This is an automated notification from the {{ $companyName }}.
        </div>
    </div>
</body>
</html>
