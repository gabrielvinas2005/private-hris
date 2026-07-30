<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class RetirementReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $employee;
    public $employeeName;
    public $retirementDate;
    public $notificationType;
    public $recipientType; // 'hr' or 'employee'
    public $retirementType; // 'optional' (60 years) or 'mandatory' (65 years)

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($employee, $employeeName, $retirementDate, $notificationType, $recipientType, $retirementType = 'mandatory')
    {
        $this->employee = $employee;
        $this->employeeName = $employeeName;
        $this->retirementDate = $retirementDate;
        $this->notificationType = $notificationType;
        $this->recipientType = $recipientType;
        $this->retirementType = $retirementType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Only send via mail - dashboard notifications are created manually in CheckRetirementNotifications
        // This prevents duplicate notifications (one with user_id, one with employee_id)
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
        $retirementDateFormatted = $this->retirementDate instanceof Carbon 
            ? $this->retirementDate->format('F d, Y')
            : Carbon::parse($this->retirementDate)->format('F d, Y');
        
        $retirementTypeLabel = $this->retirementType === 'optional' ? 'optional retirement age (60 years)' : 'mandatory retirement age (65 years)';
        
        if ($this->notificationType === 'one_year_before') {
            $subject = $this->recipientType === 'hr' 
                ? "Retirement Reminder: {$this->employeeName} - 1 Year Before {$this->retirementType} Retirement"
                : "Retirement Reminder: Your {$this->retirementType} Retirement is Approaching";
            
            $message = $this->recipientType === 'hr'
                ? "This is to inform you that employee {$this->employeeName} will reach {$retirementTypeLabel} in 1 year. Retirement Date: {$retirementDateFormatted}. Please ensure all retirement requirements are submitted."
                : "Dear {$this->employeeName}, This is to remind you that you will reach {$retirementTypeLabel} in 1 year. Retirement Date: {$retirementDateFormatted}. Please ensure all retirement requirements are submitted.";
        } else {
            $subject = $this->recipientType === 'hr'
                ? "Retirement Reminder: {$this->employeeName} - 4 Months Before {$this->retirementType} Retirement"
                : "Retirement Reminder: Your {$this->retirementType} Retirement is Approaching - Follow-up";
            
            $message = $this->recipientType === 'hr'
                ? "This is a follow-up reminder that employee {$this->employeeName} will reach {$retirementTypeLabel} in 4 months. Retirement Date: {$retirementDateFormatted}. Please ensure all retirement requirements are submitted."
                : "Dear {$this->employeeName}, This is a follow-up reminder that you will reach {$retirementTypeLabel} in 4 months. Retirement Date: {$retirementDateFormatted}. Please ensure all retirement requirements are submitted.";
        }

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.retirement_reminder', [
                'employeeName' => $this->employeeName,
                'retirementDate' => $retirementDateFormatted,
                'notificationType' => $this->notificationType,
                'retirementType' => $this->retirementType,
                'recipientType' => $this->recipientType,
                'emailMessage' => $message, // Changed from 'message' to avoid conflict with Laravel's $message variable
            ]);
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $retirementDateFormatted = $this->retirementDate instanceof Carbon 
            ? $this->retirementDate->format('F d, Y')
            : Carbon::parse($this->retirementDate)->format('F d, Y');
        
        $retirementTypeLabel = $this->retirementType === 'optional' ? 'optional retirement age (60 years)' : 'mandatory retirement age (65 years)';
        
        if ($this->notificationType === 'one_year_before') {
            $message = $this->recipientType === 'hr'
                ? "Employee {$this->employeeName} will reach {$retirementTypeLabel} in 1 year (Retirement Date: {$retirementDateFormatted}). Please ensure all requirements are submitted."
                : "You will reach {$retirementTypeLabel} in 1 year (Retirement Date: {$retirementDateFormatted}). Please ensure all retirement requirements are submitted.";
        } else {
            $message = $this->recipientType === 'hr'
                ? "Employee {$this->employeeName} will reach {$retirementTypeLabel} in 4 months (Retirement Date: {$retirementDateFormatted}). This is a follow-up reminder."
                : "You will reach {$retirementTypeLabel} in 4 months (Retirement Date: {$retirementDateFormatted}). This is a follow-up reminder.";
        }

        $retirementDateFormatted = $this->retirementDate instanceof Carbon 
            ? $this->retirementDate->format('Y-m-d')
            : Carbon::parse($this->retirementDate)->format('Y-m-d');
            
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employeeName,
            'retirement_date' => $retirementDateFormatted,
            'notification_type' => $this->notificationType . '_' . $this->retirementType,
            'retirement_type' => $this->retirementType,
            'recipient_type' => $this->recipientType,
            'message' => $message,
            'title' => $this->notificationType === 'one_year_before' 
                ? ($this->retirementType === 'optional' ? 'Optional Retirement Reminder - 1 Year Before (60 years)' : 'Mandatory Retirement Reminder - 1 Year Before (65 years)')
                : ($this->retirementType === 'optional' ? 'Optional Retirement Reminder - 4 Months Before (60 years)' : 'Mandatory Retirement Reminder - 4 Months Before (65 years)'),
        ];
    }
}
