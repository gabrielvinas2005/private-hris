<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailOBApplication extends Notification
{
    use Queueable;
    public $user_account;
    public $ob_attachments;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user_account, $ob_attachments)
    {
        $this->user_account = $user_account;
        $this->ob_attachments = $ob_attachments;
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

        $ot_mail = (new MailMessage)->subject('OB Application')->view(
            'emails.ob_application_notif',
            [
                'user_account' => $this->user_account,
                'ob_attachments' => $this->ob_attachments
            ]
        );

        foreach ($this->ob_attachments as $file) {
            $path = $file->attachment_path;
            $this->ob_attachments->attach($path, ['as' => $file->attachment_name]);
        }

        return $this->ob_attachments;
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
