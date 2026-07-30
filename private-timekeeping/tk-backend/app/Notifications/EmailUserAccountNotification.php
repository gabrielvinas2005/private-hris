<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailUserAccountNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;
    public $user;
    public $email;
    public $name;
    public $password;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, $email, $name, $password)
    {
        $this->user =  $user;
        $this->email =  $email;
        $this->name =  $name;
        $this->password =  $password;
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

        // dump($this->user);
        // dump($this->email);
        // dump($this->password);
        // dd($notifiable);

        $actionUrl  = $this->verificationUrl($notifiable);     //verificationUrl required for the verification link
        $actionText  = 'Click here to verify your email';

        return (new MailMessage)->subject('Verify your account')->view(
            'emails.user_verify',
            [
                'user' => $this->user,
                'email' => $this->email,
                'name'  => $this->name,
                'password' => $this->password,
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
