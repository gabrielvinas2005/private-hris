<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailApplicantExamPassed extends Notification implements ShouldQueue
{
    use Queueable;

    public $emailData;

    /**
     * Create a new notification instance.
     *
     * @param array $emailData
     */
    public function __construct($emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Examination Result – Passed')
            ->view('emails.applicant_exam_passed', ['emailData' => $this->emailData]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'applicant_name' => $this->emailData['applicant_name'] ?? null,
            'exam_set' => $this->emailData['exam_set'] ?? null,
            'exam_rating' => $this->emailData['exam_rating'] ?? null,
        ];
    }
}
