<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InterviewScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $applicant;
    public $interview;
    public $interviewLevel;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($applicant, $interview, $interviewLevel)
    {
        $this->applicant = $applicant;
        $this->interview = $interview;
        $this->interviewLevel = $interviewLevel;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Interview Scheduled - ' . $this->interview->panel_group)
                    ->view('emails.interview-scheduled');
    }
}

