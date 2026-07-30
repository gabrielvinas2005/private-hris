<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailUserVerificationNotification extends VerifyEmail
{
    public $user;
    public $otp_code;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, $otp_code)
    {
        $this->user = $user;
        $this->otp_code = $otp_code;
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

        $actionUrl  = $this->verificationUrl($notifiable);     //verificationUrl required for the verification link
        $actionText  = 'Click here to verify your email';
        return (new MailMessage)->subject('User OTP Code')->view(
            'emails.user_otp',
            [
                'user' => $this->user,
                'otp_code' => $this->otp_code
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
