<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Scheduled</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h2 style="color: #2563eb; margin-top: 0;">Interview Scheduled</h2>
        <p>Dear {{ $applicant->first_name }} {{ $applicant->last_name }},</p>
        <p>We are pleased to inform you that an interview has been scheduled for you.</p>
    </div>

    <div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
        <h3 style="color: #1f2937; margin-top: 0;">Interview Details</h3>

        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; font-weight: bold; width: 150px;">Panel Group:</td>
                <td style="padding: 8px 0;">{{ $interview->panel_group }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Interview Level:</td>
                <td style="padding: 8px 0;">{{ $interviewLevel }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Date:</td>
                <td style="padding: 8px 0;">
                    {{ \Carbon\Carbon::parse($interview->start_date)->format('F d, Y') }}
                    @if($interview->end_date && $interview->end_date != $interview->start_date)
                        - {{ \Carbon\Carbon::parse($interview->end_date)->format('F d, Y') }}
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Time:</td>
                <td style="padding: 8px 0;">
                    {{ \Carbon\Carbon::parse($interview->start_time)->format('g:i A') }}
                    @if($interview->end_time)
                        - {{ \Carbon\Carbon::parse($interview->end_time)->format('g:i A') }}
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Location:</td>
                <td style="padding: 8px 0;">{{ $interview->interview_location }}</td>
            </tr>
        </table>

        @if($interview->description)
            <div style="margin-top: 15px; padding: 15px; background-color: #f9fafb; border-radius: 4px;">
                <strong>Additional Information:</strong>
                <p style="margin: 10px 0 0 0;">{{ $interview->description }}</p>
            </div>
        @endif
    </div>

    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; font-size: 14px; color: #6b7280;">
        <p style="margin: 0;">
            <strong>Important:</strong> Please make sure to be available at the scheduled time.
            If you need to reschedule or have any questions, please contact us as soon as possible.
        </p>
    </div>

    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; {{ date('Y') }} {{ $orgBranchCode }}-HRMP. All rights reserved.</p>
    </div>
</body>
</html>

