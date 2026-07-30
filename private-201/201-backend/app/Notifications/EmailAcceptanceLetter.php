<?php

namespace App\Notifications;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailAcceptanceLetter extends Notification
{
    use Queueable;

    protected $applicantData;
    protected $pdfPath;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($applicantData, $pdfPath = null)
    {
        $this->applicantData = $applicantData;
        $this->pdfPath = $pdfPath;
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
        $applicantName = $this->applicantData['applicant_name'] ?? 'Applicant';
        $positionName = $this->applicantData['position_name'] ?? 'Position';

        $mail = (new MailMessage)
            ->subject('Acceptance Letter - ' . $positionName)
            ->greeting('Dear ' . $applicantName . ',')
            ->line('Congratulations! We are pleased to inform you that you have been selected for the position of ' . $positionName . '.')
            ->line('Please find your official acceptance letter attached to this email.')
            ->line('If you have any questions, please feel free to contact us.')
            ->line('We look forward to welcoming you to our team!')
            ->salutation('Best regards, ' . BranchHelper::getMainBranchCode() . ' Team');

        // Attach PDF if provided
        if ($this->pdfPath && file_exists($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as' => 'Acceptance_Letter.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
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
