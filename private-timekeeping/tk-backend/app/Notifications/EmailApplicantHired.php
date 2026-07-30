<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailApplicantHired extends Notification implements ShouldQueue
{
    use Queueable;

    public $user;
    public $reviewed_status_id;



    /**
     * Create a new notification instance.
     *
     * @param mixed $user
     * @param int $reviewed_status_id
     */
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
            ->subject('Application Review Status Update')
            ->view(
                'emails.applicant_hired',
                [
                    'emailData' => $this->emailData,
                ]
            );
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
            'reviewed_status_id' => $this->reviewed_status_id,
        ];
    }
}