<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\InterviewScheduledMail;

class InterviewNotificationService
{
    /**
     * Send email notifications to all applicants assigned to an interview
     * Call this method after posting an interview (setting posted = true)
     *
     * @param int $interviewId The ID of the interview schedule
     * @return bool Returns true if at least one email was sent successfully
     */
    public static function sendInterviewNotifications($interviewId)
    {
        try {
            // Get interview details with level
            $interview = DB::table('applicant_interview_headers as a')
                ->join('interview_levels as b', 'a.panel_group_level', '=', 'b.id')
                ->select('a.*', 'b.interview_level')
                ->where('a.id', $interviewId)
                ->where('a.posted', true)
                ->first();

            if (!$interview) {
                Log::warning('Interview not found or not posted', ['interview_id' => $interviewId]);
                return false;
            }

            // Get all applicants assigned to this interview
            $assignedApplicants = DB::table('interview_applicants')
                ->where('interview_id', $interviewId)
                ->get();

            if ($assignedApplicants->isEmpty()) {
                Log::info('No applicants assigned to interview', ['interview_id' => $interviewId]);
                return false;
            }

            $sentCount = 0;
            $failedCount = 0;

            foreach ($assignedApplicants as $assignment) {
                // Get applicant details
                $applicant = DB::table('applicant_headers')
                    ->where('id', $assignment->applicant_id)
                    ->first();

                if (!$applicant) {
                    Log::warning('Applicant not found', [
                        'applicant_id' => $assignment->applicant_id,
                        'interview_id' => $interviewId
                    ]);
                    $failedCount++;
                    continue;
                }

                if (!$applicant->email) {
                    Log::warning('Applicant has no email address', [
                        'applicant_id' => $applicant->id,
                        'interview_id' => $interviewId
                    ]);
                    $failedCount++;
                    continue;
                }

                try {
                    // Send email
                    Mail::to($applicant->email)->send(
                        new InterviewScheduledMail($applicant, $interview, $interview->interview_level)
                    );

                    $sentCount++;
                    Log::info('Interview email sent successfully', [
                        'applicant_id' => $applicant->id,
                        'applicant_email' => $applicant->email,
                        'interview_id' => $interviewId,
                        'panel_group' => $interview->panel_group
                    ]);
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('Failed to send interview email', [
                        'applicant_id' => $applicant->id,
                        'applicant_email' => $applicant->email,
                        'interview_id' => $interviewId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            Log::info('Interview notification process completed', [
                'interview_id' => $interviewId,
                'total_applicants' => $assignedApplicants->count(),
                'emails_sent' => $sentCount,
                'emails_failed' => $failedCount
            ]);

            return $sentCount > 0;
        } catch (\Exception $e) {
            Log::error('Error in sendInterviewNotifications', [
                'interview_id' => $interviewId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}

