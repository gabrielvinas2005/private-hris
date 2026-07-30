<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailOvertimeApplication extends Notification
{
    use Queueable;
    public $user_account;
    public $overtime_attachments;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user_account, $overtime_attachments)
    {
        $this->user_account = $user_account;
        $this->overtime_attachments = $overtime_attachments;
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

        $ot_mail = (new MailMessage)->subject('Overtime Application')->view(
            'emails.overtime_application_notif',
            [
                'user_account' => $this->user_account,
                'overtime_attachments' => $this->overtime_attachments
            ]
        );

        foreach ($this->overtime_attachments as $file) {
            $path = $file->attachment_path;
            $ot_mail->attach($path, ['as' => $file->attachment_name]);
        }

        return $ot_mail;
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
