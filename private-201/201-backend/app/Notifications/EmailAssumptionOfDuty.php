<?php

namespace App\Notifications;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailAssumptionOfDuty extends Notification
{
    use Queueable;

    protected $recipientData;
    protected $pdfPath;

    public function __construct($recipientData, $pdfPath = null)
    {
        $this->recipientData = $recipientData;
        $this->pdfPath = $pdfPath;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $name = $this->recipientData['name'] ?? 'Applicant';
        $mail = (new MailMessage)
            ->subject('Assumption of Duty Certificate')
            ->greeting('Dear ' . $name . ',')
            ->line('Please find your Assumption of Duty certificate attached to this email.')
            ->line('For any concerns, please contact HR.')
            ->salutation('Best regards, ' . BranchHelper::getMainBranchCode() . ' Team');

        if ($this->pdfPath && file_exists($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as' => 'Assumption_of_Duty.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
