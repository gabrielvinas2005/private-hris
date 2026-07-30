<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailUserVerificationNotification extends Notification
{
    use Queueable;

    public $userAccount;
    public $otpCode;

    /**
     * Create a new notification instance.
     */
    public function __construct($userAccount, $otpCode)
    {
        $this->userAccount = $userAccount;
        $this->otpCode = $otpCode;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Account Verification - WTI HRMP')
            ->greeting('Hello!')
            ->line('Welcome to WTI HRMP Applicant Portal.')
            ->line('To complete your login, please use the verification code below:')
            ->line('')
            ->line('**Verification Code: ' . $this->otpCode . '**')
            ->line('')
            ->line('This code will expire in 15 minutes for security purposes.')
            ->line('If you did not request this code, please ignore this email.')
            ->action('Login to Portal', url('/login'))
            ->line('Thank you for using WTI HRMP!')
            ->salutation('Best regards, WTI HRMP Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_account' => $this->userAccount,
            'otp_code' => $this->otpCode,
        ];
    }
}
