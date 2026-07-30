<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Helpers\CompanyHelper;

class BirthdayMonthNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $employee;
    private $employeeName;
    private $birthMonthDay; // e.g. 'September 06'
    private $recipientType; // 'hr' or 'employee'

    public function __construct($employee, string $employeeName, string $birthMonthDay, string $recipientType)
    {
        $this->employee = $employee;
        $this->employeeName = $employeeName;
        $this->birthMonthDay = $birthMonthDay;
        $this->recipientType = $recipientType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Send only via mail; dashboard notifications are created manually
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
        $subject = 'Birthday of the Month';
        $emailMessage = $this->getMessage();

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.birthday_month', [
                'employeeName' => $this->employeeName,
                'birthMonthDay' => $this->birthMonthDay,
                'recipientType' => $this->recipientType,
                'emailMessage' => $emailMessage,
                'companyName' => $this->getCompanyPortalName(),
            ]);
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
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employeeName,
            'birth_month_day' => $this->birthMonthDay,
            'recipient_type' => $this->recipientType,
            'message' => $this->getMessage(),
            'title' => 'Birthday of the Month',
        ];
    }

    private function getMessage(): string
    {
        // $employeeName may be a single name or a comma-separated list of names
        // Example: "Employee1, Employee2 have birthdays this January. Wish them a happy birthday!"
        return "{$this->employeeName} have birthdays this {$this->birthMonthDay}. Wish them a happy birthday!";
    }

    private function getCompanyPortalName(): string
    {
        return CompanyHelper::getPortalName();
    }
}
