<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailUserAccountNotification extends Notification
{
    use Queueable;

    public $userAccount;
    public $email;
    public $username;
    public $password;

    /**
     * Create a new notification instance.
     */
    public function __construct($userAccount, $email, $username, $password)
    {
        $this->userAccount = $userAccount;
        $this->email = $email;
        $this->username = $username;
        $this->password = $password;
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
            ->subject('Welcome to WTI HRMP - Your Account Details')
            ->greeting('Hello!')
            ->line('Thank you for registering with WTI HRMP Applicant Portal.')
            ->line('Your account has been created successfully.')
            ->line('Here are your login credentials:')
            ->line('Username: ' . $this->username)
            ->line('Password: ' . $this->password)
            ->line('Email: ' . $this->email)
            ->action('Login to Your Account', url('/login'))
            ->line('Please change your password after your first login for security purposes.')
            ->line('If you have any questions, please contact our support team.')
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
            'email' => $this->email,
            'username' => $this->username,
        ];
    }
}
