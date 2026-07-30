<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retirement Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .footer {
            background-color: #ecf0f1;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 5px 5px;
            font-size: 12px;
            color: #7f8c8d;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Retirement Reminder Notification</h1>
    </div>
    
    <div class="content">
        <p>{{ $emailMessage }}</p>
        
        <div class="highlight">
            <strong>Employee Name:</strong> {{ $employeeName }}<br>
            <strong>Retirement Date:</strong> {{ $retirementDate }}<br>
            <strong>Reminder Type:</strong> {{ $notificationType === 'one_year_before' ? '1 Year Before Retirement' : '4 Months Before Retirement' }}
        </div>
        
        @if($recipientType === 'employee')
            <p>Please ensure that you have submitted all required retirement documents and completed all necessary procedures. If you have any questions, please contact the HR Department.</p>
        @else
            <p>Please ensure that all retirement requirements for this employee are processed and submitted on time. Coordinate with the employee to complete all necessary documentation.</p>
        @endif
        
        <p>Thank you for your attention to this matter.</p>
        
        <p>Best regards,<br>
        <strong>HR Management System</strong></p>
    </div>
    
    <div class="footer">
        <p>This is an automated notification from the HR Management System. Please do not reply to this email.</p>
    </div>
</body>
</html>

