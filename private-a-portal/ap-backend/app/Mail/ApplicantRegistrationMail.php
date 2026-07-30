<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicantRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $applicantName;
    public $applicationData;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($applicantName, $applicationData)
    {
        $this->applicantName = $applicantName;
        $this->applicationData = $applicationData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('view.name');
    }
}
