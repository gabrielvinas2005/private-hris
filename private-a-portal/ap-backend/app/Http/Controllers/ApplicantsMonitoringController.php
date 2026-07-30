<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;

class ApplicantsMonitoringController extends Controller
{
    use ApiResponse;

    /**
     * Recruitment: View progress of an applicant in a fixed sequence.
     *
     * Sequence:
     * - Initial pre-examination (qualification/review)
     * - Initial interview (level id 1)
     * - Technical interview (level id 3)
     * - Panel interview (level id 2)
     * - Technical examination (exam category contains "technical")
     * - Psychological examination (exam category contains "psychological")
     * - Final interview (level id 5)
     */
    public function progress($applicant_id, $position_applied_id = null)
    {
        try {
            $applicantId = (int) $applicant_id;
            $positionAppliedId = $position_applied_id !== null ? (int) $position_applied_id : null;
            $app_key = env("APP_KEY", "");

            // 1) Initial pre-examination: check for actual pre-examination exam completion
            // First, get all exams to check for pre-examination type
            $allExams = DB::table('applicant_examination_headers as aeh')
                ->join('examination_schedule_header as esh', 'aeh.exam_schedule_id', '=', 'esh.id')
                ->join('examination_setup_header as exh', 'esh.exam_id', '=', 'exh.id')
                ->leftJoin('exam_types as et', 'exh.exam_type_id', '=', 'et.id')
                ->select(
                    'aeh.applicant_id',
                    'aeh.id as applicant_examination_id',
                    DB::raw("ISNULL(aeh.is_complete, 0) as is_complete"),
                    'exh.exam_type_id',
                    'et.code as exam_type_code',
                    'esh.exam_date_from',
                    'esh.exam_time_from',
                    DB::raw("ISNULL(aeh.exam_rating, 0) as exam_rating"),
                    DB::raw("ISNULL(aeh.total_score, 0) as total_score"),
                    DB::raw("ISNULL(aeh.total_items, 0) as total_items")
                )
                ->where('aeh.applicant_id', $applicantId)
                ->get();
            
            // Check for pre-examination exam type
            $preExamRows = $allExams->filter(fn ($r) => strtolower((string) ($r->exam_type_code ?? '')) === 'pre-examination');
            $preExamDone = false;
            $preExamScore = null;
            $preExamDate = null;
            $preExamTime = null;
            
            if ($preExamRows->isNotEmpty()) {
                $completed = $preExamRows->firstWhere('is_complete', 1) || $preExamRows->firstWhere('is_complete', true);
                $latest = $preExamRows->sortByDesc(fn ($r) => ($r->exam_date_from ?? '') . ' ' . ($r->exam_time_from ?? ''))->first();
                
                $preExamDone = $completed;
                $preExamDate = $latest->exam_date_from ?? null;
                $preExamTime = $latest->exam_time_from ?? null;
                
                // Return score in the same format as other exams (with total_score/total_items)
                if ($latest && $latest->exam_rating && $completed) {
                    $preExamScore = [
                        'rating' => number_format((float) $latest->exam_rating, 2),
                        'total_score' => (int) ($latest->total_score ?? 0),
                        'total_items' => (int) ($latest->total_items ?? 0)
                    ];
                }
            }

            // 2) Interviews: scheduled/completed by interview level with panels and attachments
            $interviews = DB::table('interview_applicants as ia')
                ->join('applicant_interview_headers as ih', 'ia.interview_id', '=', 'ih.id')
                ->select(
                    'ia.applicant_id',
                    'ia.interview_id',
                    'ih.panel_group_level',
                    DB::raw("ISNULL(ia.is_complete_interview, 0) as is_complete_interview"),
                    'ih.start_date',
                    'ih.start_time'
                )
                ->where('ia.applicant_id', $applicantId)
                ->where(function ($q) use ($positionAppliedId) {
                    // If per-position tagging exists in your interview tables later, extend here.
                    $q->whereRaw('1=1');
                })
                ->get();

            $interviewStatusForLevel = function (int $levelId) use ($interviews, $applicantId, $app_key) {
                $rows = $interviews->filter(fn ($r) => (int) ($r->panel_group_level ?? 0) === $levelId);
                if ($rows->isEmpty()) {
                    return [
                        'state' => 'pending', 
                        'date' => null, 
                        'time' => null,
                        'panels' => [],
                        'attachments' => []
                    ];
                }
                $completed = $rows->firstWhere('is_complete_interview', 1) || $rows->firstWhere('is_complete_interview', true);
                $latest = $rows->sortByDesc(fn ($r) => ($r->start_date ?? '') . ' ' . ($r->start_time ?? ''))->first();
                $interviewId = $latest->interview_id ?? null;
                
                // Get panel members for this interview
                $panels = [];
                if ($interviewId) {
                    $panels = DB::table('employees as a')
                        ->join('positions as b', 'a.position_id', '=', 'b.id')
                        ->join('interview_panels as c', 'a.id', '=', 'c.employee_id')
                        ->select(
                            'a.id as employee_id',
                            'a.employee_no',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                                END as name"),
                            'b.name as position'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true,
                            'c.interview_id' => $interviewId
                        ])
                        ->orderBy('name', 'asc')
                        ->get()
                        ->map(function ($p) {
                            return [
                                'name' => $p->name,
                                'position' => $p->position,
                                'employee_no' => $p->employee_no
                            ];
                        })
                        ->toArray();
                }
                
                // Get HRDD review documents (BI and BR) for this applicant as interview attachments
                $attachments = [];
                
                // Get Background Investigation documents (type_id = 1)
                $biDocs = DB::table('applicant_background_investigation_documents')
                    ->where('applicant_id', $applicantId)
                    ->select('id', 'bi_document as attachment_name')
                    ->get();
                
                foreach ($biDocs as $doc) {
                    $fileName = $doc->attachment_name ?? 'document';
                    // Use document ID for download
                    $downloadUrl = url("/api/administrator-selection/{$doc->id}/download/1");
                    
                    $attachments[] = [
                        'label' => $fileName,
                        'type' => 'Background Investigation',
                        'url' => $downloadUrl,
                        'id' => $doc->id
                    ];
                }
                
                // Get Board Resolution documents (type_id = 2)
                $brDocs = DB::table('applicant_board_resolution_documents')
                    ->where('applicant_id', $applicantId)
                    ->select('id', 'board_resolution_document as attachment_name')
                    ->get();
                
                foreach ($brDocs as $doc) {
                    $fileName = $doc->attachment_name ?? 'document';
                    // Use document ID for download
                    $downloadUrl = url("/api/administrator-selection/{$doc->id}/download/2");
                    
                    $attachments[] = [
                        'label' => $fileName,
                        'type' => 'Board Resolution',
                        'url' => $downloadUrl,
                        'id' => $doc->id
                    ];
                }
                
                return [
                    'state' => $completed ? 'done' : 'scheduled',
                    'date' => $latest->start_date ?? null,
                    'time' => $latest->start_time ?? null,
                    'panels' => $panels,
                    'attachments' => $attachments
                ];
            };

            // 3) Examinations: scheduled/completed by exam_type_id with scores
            $exams = DB::table('applicant_examination_headers as aeh')
                ->join('examination_schedule_header as esh', 'aeh.exam_schedule_id', '=', 'esh.id')
                ->join('examination_setup_header as exh', 'esh.exam_id', '=', 'exh.id')
                ->leftJoin('exam_types as et', 'exh.exam_type_id', '=', 'et.id')
                ->select(
                    'aeh.applicant_id',
                    'aeh.id as applicant_examination_id',
                    DB::raw("ISNULL(aeh.is_complete, 0) as is_complete"),
                    'exh.exam_type_id',
                    'et.code as exam_type_code',
                    'esh.exam_date_from',
                    'esh.exam_time_from',
                    DB::raw("ISNULL(aeh.exam_rating, 0) as exam_rating"),
                    DB::raw("ISNULL(aeh.total_score, 0) as total_score"),
                    DB::raw("ISNULL(aeh.total_items, 0) as total_items")
                )
                ->where('aeh.applicant_id', $applicantId)
                ->get();

            $examStatusForTypeCode = function (string $typeCode) use ($exams) {
                $rows = $exams->filter(fn ($r) => strtolower((string) ($r->exam_type_code ?? '')) === strtolower($typeCode));
                if ($rows->isEmpty()) {
                    return [
                        'state' => 'pending', 
                        'date' => null, 
                        'time' => null,
                        'score' => null
                    ];
                }
                $completed = $rows->firstWhere('is_complete', 1) || $rows->firstWhere('is_complete', true);
                $latest = $rows->sortByDesc(fn ($r) => ($r->exam_date_from ?? '') . ' ' . ($r->exam_time_from ?? ''))->first();
                
                $score = null;
                if ($latest && $latest->exam_rating) {
                    $score = [
                        'rating' => number_format((float) $latest->exam_rating, 2),
                        'total_score' => (int) ($latest->total_score ?? 0),
                        'total_items' => (int) ($latest->total_items ?? 0)
                    ];
                }
                
                return [
                    'state' => $completed ? 'done' : 'scheduled',
                    'date' => $latest->exam_date_from ?? null,
                    'time' => $latest->exam_time_from ?? null,
                    'score' => $score
                ];
            };

            $techExamStatus = $examStatusForTypeCode('technical');
            $psychExamStatus = $examStatusForTypeCode('psychological');

            // Display order matches recruitment pipeline presentation (Application Progress modal).
            $steps = [
                array_merge([
                    'key' => 'pre_exam',
                    'label' => 'Initial pre-examination',
                    'state' => $preExamDone ? 'done' : 'pending',
                    'date' => $preExamDate,
                    'time' => $preExamTime,
                ], $preExamScore ? ['score' => $preExamScore] : []),
                array_merge(['key' => 'initial_interview', 'label' => 'Initial interview'], $interviewStatusForLevel(1)),
                array_merge(['key' => 'technical_interview', 'label' => 'Technical interview'], $interviewStatusForLevel(3)),
                array_merge(['key' => 'technical_exam', 'label' => 'Technical examination'], $techExamStatus),
                array_merge(['key' => 'psych_exam', 'label' => 'Psychological examination'], $psychExamStatus),
                array_merge(['key' => 'panel_interview', 'label' => 'Panel interview'], $interviewStatusForLevel(2)),
                array_merge(['key' => 'final_interview', 'label' => 'Final interview'], $interviewStatusForLevel(5)),
            ];

            return $this->successResponse([
                'applicant_id' => $applicantId,
                'position_applied_id' => $positionAppliedId,
                'steps' => $steps,
            ], 'Applicant progress loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load applicant progress: ' . $e->getMessage());
        }
    }

    /**
     * Applicant Portal: progress for the currently authenticated applicant.
     *
     * This wraps the generic progress() method but derives the applicant_id
     * from the logged-in user, mirroring how ApplicantsController::applicant_page
     * locates the applicant header.
     */
    public function progressForCurrentApplicant()
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return $this->errorResponse('Unauthenticated.', [], 401);
            }

            $applicant = DB::table('applicant_headers')
                ->where('user_id', $userId)
                ->first();

            if (!$applicant) {
                return $this->errorResponse('No applicant profile found for the current user.', [], 404);
            }

            return $this->progress($applicant->id, null);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load applicant progress: ' . $e->getMessage());
        }
    }

    public function applicationStatuses()
    {
        try {
            $statuses = DB::table('application_status')
                ->select('id', 'name')
                ->orderBy('id', 'asc')
                ->get();

            return $this->successResponse([
                'statuses' => $statuses
            ], 'Application statuses loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load application statuses: ' . $e->getMessage());
        }
    }

    public function updateStatus(\Illuminate\Http\Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'position_applied_id' => 'nullable|integer',
                'application_status_id' => 'required|integer',
            ]);

            $applicantId = (int) $request->input('applicant_id');
            $positionAppliedId = $request->input('position_applied_id') !== null ? (int) $request->input('position_applied_id') : null;
            $statusId = (int) $request->input('application_status_id');

            $detailsQuery = DB::table('applicant_details')->where('applicant_id', $applicantId);
            if ($positionAppliedId !== null && $positionAppliedId > 0) {
                $detailsQuery->where('position_applied_id', $positionAppliedId);
            }
            $updated = $detailsQuery->update(['application_status_id' => $statusId]);

            // Sync header as well (some parts of system read from applicant_headers)
            DB::table('applicant_headers')->where('id', $applicantId)->update(['application_status_id' => $statusId]);

            // If status is "Hired" (id = 6), ensure the linked employee record is marked as an employee
            if ($statusId === 6) {
                // Get applicant number to find corresponding employee record
                $applicantHeader = DB::table('applicant_headers')
                    ->select('applicant_no')
                    ->where('id', $applicantId)
                    ->first();

                if ($applicantHeader && $applicantHeader->applicant_no) {
                    DB::table('employees')
                        ->where('employee_no', $applicantHeader->applicant_no)
                        ->update([
                            'is_employee' => true,
                            'application_status_id' => $statusId,
                        ]);
                }
            }

            return $this->successResponse([
                'applicant_id' => $applicantId,
                'position_applied_id' => $positionAppliedId,
                'application_status_id' => $statusId,
                'updated_rows' => $updated,
            ], 'Applicant status updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update applicant status: ' . $e->getMessage());
        }
    }

    /**
     * Recruitment: Applicants Monitoring
     * Returns applicants with position applied and current application status.
     */
    public function index()
    {
        try {
            $data = DB::table('applicant_details as ad')
                ->join('applicant_headers as ah', 'ad.applicant_id', '=', 'ah.id')
                ->leftJoin('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                ->leftJoin('positions as pos', 'p.position_id', '=', 'pos.id')
                ->leftJoin('departments as dep', 'p.department_id', '=', 'dep.id')
                ->leftJoin('application_status as st', 'ad.application_status_id', '=', 'st.id')
                ->leftJoin(DB::raw('(SELECT applicant_id, MAX(CASE WHEN ISNULL(is_complete,0)=1 THEN 1 ELSE 0 END) as has_exam FROM applicant_examination_headers GROUP BY applicant_id) as aeh'), 'aeh.applicant_id', '=', 'ah.id')
                ->leftJoin(DB::raw('(SELECT applicant_id, MAX(CASE WHEN ISNULL(is_cancelled_interview,0)=0 THEN 1 ELSE 0 END) as has_interview FROM interview_applicants GROUP BY applicant_id) as ia'), 'ia.applicant_id', '=', 'ah.id')
                ->select(
                    'ah.id as applicant_id',
                    'ah.applicant_no',
                    'ah.first_name',
                    'ah.middle_name',
                    'ah.last_name',
                    'ah.email',
                    'ah.mobile_no',
                    'ah.photo',
                    'ad.position_applied_id',
                    'pos.name as position',
                    'dep.name as department',
                    'ad.application_status_id',
                    'st.name as application_status',
                    'ad.created_at as applied_at',
                    DB::raw("ISNULL(aeh.has_exam, 0) as has_exam_result"),
                    DB::raw("ISNULL(ia.has_interview, 0) as has_interview_schedule")
                )
                ->orderBy('applied_at', 'desc')
                ->get();

            return $this->successResponse([
                'applicants' => $data
            ], 'Applicants monitoring loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load applicants monitoring: ' . $e->getMessage());
        }
    }
}

