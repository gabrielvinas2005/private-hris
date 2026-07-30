<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User OTP Code</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f8fb; color: #111827; }
        .card { max-width: 640px; margin: 24px auto; background: #ffffff; border-radius: 8px; padding: 24px; border: 1px solid #e5e7eb; }
        .otp { font-size: 28px; letter-spacing: 4px; font-weight: 700; color: #1f2937; }
        .muted { color: #6b7280; font-size: 14px; }
    </style>
  </head>
  <body>
    <div class="card">
      <h2>Verify your account</h2>
      <p>Hello {{ optional($user->first())->name ?? '' }},</p>
      <p>Please use the code below to verify your account:</p>
      <p class="otp">{{ $otp_code }}</p>
      <p class="muted">If you did not request this, you can safely ignore this email.</p>
    </div>
  </body>
</html>


