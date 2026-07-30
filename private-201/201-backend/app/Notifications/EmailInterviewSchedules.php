<?php

namespace App\Notifications;

use App\Helpers\BranchHelper;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class EmailInterviewSchedules extends VerifyEmail implements ShouldQueue
{
    use Queueable;
    public $interview_data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($interview_data)
    {
        $this->interview_data = $interview_data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $actionUrl = $this->verificationUrl($notifiable);
        $actionText = 'Click here to verify your email';
        $d = $this->interview_data[0];

        // Applicant invitation (type 1): use level-specific email content
        if (isset($d->type) && (int) $d->type === 1) {
            $level = (int) ($d->panel_group_level ?? 0);
            $lastName = $d->last_name ?? trim(preg_replace('/\s+/', ' ', $d->name ?? ''));
            $salutation = 'Dear Mr./Ms. ' . ucfirst(strtolower(trim($lastName))) . ',';

            $startDate = $d->start_date ?? now()->toDateString();
            $startTime = $d->start_time ?? '09:00:00';
            $dateFormatted = strtoupper(Carbon::parse($startDate)->format('l F j, Y'));
            $dateLongFormatted = Carbon::parse($startDate)->format('l, F j, Y');
            $timeFormatted = Carbon::parse($startTime)->format('g:i a');
            $dayBefore = Carbon::parse($startDate)->subDay();
            $dayBeforeFormatted = $dayBefore->format('l, F j, Y');
            $interviewLocation = trim((string) ($d->interview_location ?? (BranchHelper::getMainBranchCode() . ' Office')));
            $positionApplied = trim((string) ($d->position_applied ?? 'the position'));

            $confirmationLine = $this->applicantConfirmationLine($level, $dayBefore);

            $viewMap = [
                1 => 'emails.interview_invite_initial',
                2 => 'emails.interview_invite_panel',
                3 => 'emails.interview_invite_technical',
                5 => 'emails.interview_invite_final',
            ];
            $subjectMap = [
                1 => 'Initial HR Interview Schedule',
                2 => 'Panel Interview Schedule',
                3 => 'Technical Interview Schedule',
                5 => 'Final Interview Schedule',
            ];

            $viewName = $viewMap[$level] ?? 'emails.interview_schedule_notification';
            $subject = $subjectMap[$level] ?? 'Interview Schedule';

            $viewData = [
                'interview_data' => $this->interview_data,
                'actionText' => $actionText,
                'actionUrl' => $actionUrl,
                'salutation' => $salutation,
                'date_formatted' => $dateFormatted,
                'date_long_formatted' => $dateLongFormatted,
                'time_formatted' => $timeFormatted,
                'confirmation_line' => $confirmationLine,
                'day_before_formatted' => $dayBeforeFormatted,
                'interview_location' => $interviewLocation !== '' ? $interviewLocation : BranchHelper::getMainBranchCode() . ' Office',
                'position_applied' => $positionApplied !== '' ? $positionApplied : 'the position',
            ];

            return (new MailMessage)
                ->subject($subject)
                ->view($viewName, $viewData);
        }

        // Panelist / interviewer (type 2): level-specific calendar format
        $level = (int) ($d->panel_group_level ?? 0);
        $startDate = $d->start_date ?? now()->toDateString();
        $startTime = $d->start_time ?? '09:00:00';
        $dateFormatted = Carbon::parse($startDate)->format('l, F j, Y');
        $timeFormatted = Carbon::parse($startTime)->format('g:i A');
        $applicantsByPosition = $d->applicants_by_position ?? [];

        if (in_array($level, [1, 3], true)) {
            $lastName = trim((string) ($d->last_name ?? ''));
            if ($lastName === '') {
                $nameParts = preg_split('/\s+/', trim((string) ($d->name ?? '')));
                $lastName = !empty($nameParts) ? end($nameParts) : '';
            }
            $salutation = 'Dear Mr./Ms. ' . ucfirst(strtolower($lastName)) . ',';
            $interviewTypeLabel = $level === 1
                ? 'Virtual HR Initial Interview'
                : 'Virtual Technical Interview';
            $subject = $level === 1
                ? 'Virtual HR Initial Interview Schedule'
                : 'Virtual Technical Interview Schedule';

            return (new MailMessage)
                ->subject($subject)
                ->view('emails.interview_interviewer_initial_technical', [
                    'interview_data' => $this->interview_data,
                    'salutation' => $salutation,
                    'interview_type_label' => $interviewTypeLabel,
                    'date_formatted' => $dateFormatted,
                    'time_formatted' => $timeFormatted,
                    'applicants_by_position' => $applicantsByPosition,
                    'actionText' => $actionText,
                    'actionUrl' => $actionUrl,
                ]);
        }

        if ($level === 2) {
            $panelGroup = trim((string) ($d->panel_group ?? ''));
            $panelInterviewTitle = $panelGroup !== ''
                ? $panelGroup
                : 'Panel Interview - ' . Carbon::parse($startDate)->format('M j, Y');

            return (new MailMessage)
                ->subject('Panel Interview Schedule')
                ->view('emails.interview_interviewer_panel', [
                    'interview_data' => $this->interview_data,
                    'date_formatted' => $dateFormatted,
                    'time_formatted' => $timeFormatted,
                    'panel_interview_title' => $panelInterviewTitle,
                    'applicants_by_position' => $applicantsByPosition,
                    'actionText' => $actionText,
                    'actionUrl' => $actionUrl,
                ]);
        }

        // Final and other levels: existing schedule notification view
        return (new MailMessage)->subject('Interview Schedule')->view(
            'emails.interview_schedule_notification',
            [
                'interview_data' => $this->interview_data,
                'actionText' => $actionText,
                'actionUrl' => $actionUrl,
            ]
        );
    }

    /**
     * Confirmation line text for applicant invite by interview level.
     * Initial (1): 1pm day before. Technical (3): no deadline. Panel (2) & Final (5): 10am day before.
     */
    private function applicantConfirmationLine(int $level, Carbon $dayBefore): string
    {
        $dayBeforeFormatted = $dayBefore->format('l, F j, Y');
        $dayBeforeLower = strtolower($dayBefore->format('l')) . ', ' . $dayBefore->format('F j, Y');

        if ($level === 1) {
            return 'Please confirm your virtual attendance by replying to this email on or before 1pm of ' . $dayBeforeFormatted . '. Late confirmations will not be sent a MS Teams link.';
        }
        if ($level === 3) {
            return 'Please confirm your virtual attendance by replying to this email.';
        }
        if ($level === 2 || $level === 5) {
            return 'Please confirm your virtual attendance by replying to this email on or before 10am of ' . $dayBeforeLower . '. Late confirmations will not be sent a MS Teams link.';
        }
        return 'Please confirm your virtual attendance by replying to this email.';
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
