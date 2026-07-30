<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Email201Update extends VerifyEmail implements ShouldQueue
{
    use Queueable;
    public $employee_hr;
    public $employee_name;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($employee_hr, $employee_name)
    {
        $this->employee_hr = $employee_hr;
        $this->employee_name = $employee_name;
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

        // $actionUrl  = $this->verificationUrl($notifiable);     //verificationUrl required for the verification link
        // $actionText  = 'Click here to verify your email';
        return (new MailMessage)->subject('Employee 201 Update')->view(
            'emails.employee_201_update_notif',
            [
                'employee_hr' => $this->employee_hr,
                'employee_name' => $this->employee_name
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
