<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to WTI HRMP</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f8fb; color: #111827; }
        .card { max-width: 640px; margin: 24px auto; background: #ffffff; border-radius: 8px; padding: 24px; border: 1px solid #e5e7eb; }
        .btn { display: inline-block; background: #2563eb; color: #fff; padding: 10px 16px; border-radius: 6px; text-decoration: none; }
        .muted { color: #6b7280; font-size: 14px; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    </style>
  </head>
  <body>
    <div class="card">
      <h2>Welcome to WTI HRMP - Your Account Details</h2>
      <p>Hello {{ optional($user->first())->name ?? '' }},</p>
      <p>Your account has been created successfully. Here are your login credentials:</p>
      <ul>
        <li>Username: <span class="mono">{{ $name }}</span></li>
        <li>Email: <span class="mono">{{ $email }}</span></li>
        <li>Password: <span class="mono">{{ $password }}</span></li>
      </ul>
      <p><a href="#" class="btn">Login to Your Account</a></p>
      <p class="muted">Please change your password after your first login for security purposes.</p>
    </div>
  </body>
</html>


