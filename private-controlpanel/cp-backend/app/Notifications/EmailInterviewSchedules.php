<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        // return (new MailMessage)
        //     ->line('The introduction to the notification.')
        //     ->action('Notification Action', url('/'))
        //     ->line('Thank you for using our application!');

        $actionUrl  = $this->verificationUrl($notifiable); //verificationUrl required for the verification link
        $actionText  = 'Click here to verify your email';
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
