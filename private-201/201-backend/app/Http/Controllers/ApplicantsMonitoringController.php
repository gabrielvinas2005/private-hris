<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
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
                    DB::raw("ISNULL(aeh.total_items, 0) as total_items"),
                    DB::raw("ISNULL(exh.passing_criteria, 0) as passing_criteria")
                )
                ->where('aeh.applicant_id', $applicantId)
                ->get();

            // Check for pre-examination exam type
            $preExamRows = $allExams->filter(fn($r) => strtolower((string) ($r->exam_type_code ?? '')) === 'pre-examination');
            $preExamDone = false;
            $preExamScore = null;
            $preExamDate = null;
            $preExamTime = null;

            if ($preExamRows->isNotEmpty()) {
                $completed = $preExamRows->firstWhere('is_complete', 1) || $preExamRows->firstWhere('is_complete', true);
                $latest = $preExamRows->sortByDesc(fn($r) => ($r->exam_date_from ?? '') . ' ' . ($r->exam_time_from ?? ''))->first();

                $preExamDone = $completed;
                $preExamDate = $latest->exam_date_from ?? null;
                $preExamTime = $latest->exam_time_from ?? null;

                // Passing score on examination setup is a minimum number of correct items (not exam_rating %).
                if ($latest && $completed) {
                    $passingCriteria = (float) ($latest->passing_criteria ?? 0);
                    $rating = (float) ($latest->exam_rating ?? 0);
                    $totalScore = (int) ($latest->total_score ?? 0);
                    $preExamScore = [
                        'rating' => number_format($rating, 2),
                        'total_score' => $totalScore,
                        'total_items' => (int) ($latest->total_items ?? 0),
                        'passing_criteria' => $passingCriteria,
                        'passed' => $totalScore >= $passingCriteria,
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
                $rows = $interviews->filter(fn($r) => (int) ($r->panel_group_level ?? 0) === $levelId);
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
                $latest = $rows->sortByDesc(fn($r) => ($r->start_date ?? '') . ' ' . ($r->start_time ?? ''))->first();
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
                    DB::raw("ISNULL(aeh.total_items, 0) as total_items"),
                    DB::raw("ISNULL(exh.passing_criteria, 0) as passing_criteria")
                )
                ->where('aeh.applicant_id', $applicantId)
                ->get();

            $examStatusForTypeCode = function (string $typeCode) use ($exams) {
                $rows = $exams->filter(fn($r) => strtolower((string) ($r->exam_type_code ?? '')) === strtolower($typeCode));
                if ($rows->isEmpty()) {
                    return [
                        'state' => 'pending',
                        'date' => null,
                        'time' => null,
                        'score' => null
                    ];
                }
                $completed = $rows->firstWhere('is_complete', 1) || $rows->firstWhere('is_complete', true);
                $latest = $rows->sortByDesc(fn($r) => ($r->exam_date_from ?? '') . ' ' . ($r->exam_time_from ?? ''))->first();

                $score = null;
                if ($latest && $completed) {
                    $passingCriteria = (float) ($latest->passing_criteria ?? 0);
                    $rating = (float) ($latest->exam_rating ?? 0);
                    $isPsychological = strtolower($typeCode) === 'psychological';
                    $totalScore = (int) ($latest->total_score ?? 0);
                    $passed = $isPsychological
                        ? ($totalScore >= 1 || $rating >= 50)
                        : ($totalScore >= $passingCriteria);
                    $score = [
                        'rating' => number_format($rating, 2),
                        'total_score' => $totalScore,
                        'total_items' => (int) ($latest->total_items ?? 0),
                        'passing_criteria' => $passingCriteria,
                        'passed' => $passed
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
            // position_applied_id references plantillas.id OR non_plantillas.id depending on ad.is_plantilla.
            // Join both with flags so id collisions (e.g. plantilla #2 vs non-plantilla #2) never mix.
            $data = DB::table('applicant_details as ad')
                ->join('applicant_headers as ah', 'ad.applicant_id', '=', 'ah.id')
                ->leftJoin('plantillas as p', function ($join) {
                    $join->on('ad.position_applied_id', '=', 'p.id')
                        ->whereRaw('ISNULL(ad.is_plantilla, 1) = 1');
                })
                ->leftJoin('non_plantillas as np', function ($join) {
                    $join->on('ad.position_applied_id', '=', 'np.id')
                        ->whereRaw('ISNULL(ad.is_plantilla, 1) = 0');
                })
                ->leftJoin('positions as pos_p', 'p.position_id', '=', 'pos_p.id')
                ->leftJoin('departments as dep_p', 'p.department_id', '=', 'dep_p.id')
                ->leftJoin('positions as pos_np', 'np.position_id', '=', 'pos_np.id')
                ->leftJoin('departments as dep_np', 'np.department_id', '=', 'dep_np.id')
                ->leftJoin('application_status as st', function ($join) {
                    // Use header status as the UI source of truth when available to avoid stale detail/header mismatches.
                    $join->on('st.id', '=', DB::raw('ISNULL(ah.application_status_id, ad.application_status_id)'));
                })
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
                    'ad.is_plantilla',
                    DB::raw('COALESCE(pos_np.name, pos_p.name) as position'),
                    DB::raw('COALESCE(dep_np.name, dep_p.name) as department'),
                    DB::raw('ISNULL(ah.application_status_id, ad.application_status_id) as application_status_id'),
                    'st.name as application_status',
                    'ad.created_at as applied_at',
                    DB::raw("ISNULL(aeh.has_exam, 0) as has_exam_result"),
                    DB::raw("ISNULL(ia.has_interview, 0) as has_interview_schedule")
                )
                ->orderBy('applied_at', 'desc')
                ->get();

            $data = $this->attachExamAndInterviewSummaries($data);

            return $this->successResponse([
                'applicants' => $data
            ], 'Applicants monitoring loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load applicants monitoring: ' . $e->getMessage());
        }
    }

    private function attachExamAndInterviewSummaries($applicants)
    {
        if ($applicants->isEmpty()) {
            return $applicants;
        }

        $applicantIds = $applicants->pluck('applicant_id')->unique()->filter()->values()->all();
        if (empty($applicantIds)) {
            return $applicants;
        }

        $examRows = DB::table('applicant_examination_headers as aeh')
            ->join('examination_schedule_header as esh', 'aeh.exam_schedule_id', '=', 'esh.id')
            ->join('examination_setup_header as exh', 'esh.exam_id', '=', 'exh.id')
            ->leftJoin('exam_types as et', 'exh.exam_type_id', '=', 'et.id')
            ->leftJoin('exam_categories as ec', 'exh.category_id', '=', 'ec.id')
            ->whereIn('aeh.applicant_id', $applicantIds)
            ->select(
                'aeh.applicant_id',
                'et.code as exam_type_code',
                DB::raw('ISNULL(aeh.is_complete, 0) as is_complete'),
                DB::raw("COALESCE(et.name, ec.name, exh.exam_set, 'Exam') as exam_name"),
                'esh.exam_date_from',
                'esh.exam_time_from'
            )
            ->orderBy('aeh.applicant_id')
            ->orderBy('aeh.id')
            ->get()
            ->groupBy('applicant_id');

        $interviewRows = DB::table('interview_applicants as ia')
            ->join('applicant_interview_headers as ih', 'ia.interview_id', '=', 'ih.id')
            ->leftJoin('interview_levels as il', 'ih.panel_group_level', '=', 'il.id')
            ->whereIn('ia.applicant_id', $applicantIds)
            ->whereRaw('ISNULL(ia.is_cancelled_interview, 0) = 0')
            ->select(
                'ia.applicant_id',
                'ih.panel_group_level',
                DB::raw("COALESCE(il.interview_level, 'Interview') as interview_name"),
                DB::raw('ISNULL(ia.is_complete_interview, 0) as is_complete'),
                'ih.start_date',
                'ih.start_time'
            )
            ->orderBy('ia.applicant_id')
            ->orderBy('ih.panel_group_level')
            ->get()
            ->groupBy('applicant_id');

        $examStepDefs = [
            ['type_code' => 'pre-examination', 'fallback' => 'Initial pre-examination'],
            ['type_code' => 'technical', 'fallback' => 'Technical examination'],
            ['type_code' => 'psychological', 'fallback' => 'Psychological examination'],
        ];

        $interviewStepDefs = [
            ['level' => 1, 'fallback' => 'Initial interview'],
            ['level' => 3, 'fallback' => 'Technical interview'],
            ['level' => 2, 'fallback' => 'Panel interview'],
            ['level' => 5, 'fallback' => 'Final interview'],
        ];

        return $applicants->map(function ($row) use ($examRows, $interviewRows, $examStepDefs, $interviewStepDefs) {
            $applicantId = $row->applicant_id;
            $exams = $examRows->get($applicantId) ?? collect();
            $interviews = $interviewRows->get($applicantId) ?? collect();

            $examSteps = collect($examStepDefs)->map(function ($def) use ($exams) {
                return [
                    'label' => $this->examLabelForType($exams, $def['type_code'], $def['fallback']),
                    'state' => $this->examStateForType($exams, $def['type_code']),
                ];
            })->all();

            $interviewSteps = collect($interviewStepDefs)->map(function ($def) use ($interviews) {
                return [
                    'label' => $this->interviewLabelForLevel($interviews, $def['level'], $def['fallback']),
                    'state' => $this->interviewStateForLevel($interviews, $def['level']),
                ];
            })->all();

            $row->current_exam = $this->resolveCurrentProgressStep($examSteps);
            $row->current_interview = $this->resolveCurrentProgressStep($interviewSteps);

            return $row;
        });
    }

    private function resolveCurrentProgressStep(array $steps): ?array
    {
        $lastDone = null;

        foreach ($steps as $step) {
            $state = $step['state'] ?? 'pending';

            if ($state === 'scheduled') {
                return [
                    'name' => $step['label'],
                    'done' => false,
                ];
            }

            if ($state === 'done') {
                $lastDone = [
                    'name' => $step['label'],
                    'done' => true,
                ];
            }
        }

        return $lastDone;
    }

    private function examStateForType($exams, string $typeCode): string
    {
        $rows = $exams->filter(function ($row) use ($typeCode) {
            return strtolower((string) ($row->exam_type_code ?? '')) === strtolower($typeCode);
        });

        if ($rows->isEmpty()) {
            return 'pending';
        }

        if ($rows->contains(function ($row) {
            return (int) $row->is_complete === 1;
        })) {
            return 'done';
        }

        return 'scheduled';
    }

    private function examLabelForType($exams, string $typeCode, string $fallback): string
    {
        $rows = $exams->filter(function ($row) use ($typeCode) {
            return strtolower((string) ($row->exam_type_code ?? '')) === strtolower($typeCode);
        });

        if ($rows->isEmpty()) {
            return $fallback;
        }

        $latest = $rows->sortByDesc(function ($row) {
            return ($row->exam_date_from ?? '') . ' ' . ($row->exam_time_from ?? '');
        })->first();

        return $latest->exam_name ?: $fallback;
    }

    private function interviewStateForLevel($interviews, int $levelId): string
    {
        $rows = $interviews->filter(function ($row) use ($levelId) {
            return (int) ($row->panel_group_level ?? 0) === $levelId;
        });

        if ($rows->isEmpty()) {
            return 'pending';
        }

        if ($rows->contains(function ($row) {
            return (int) $row->is_complete === 1;
        })) {
            return 'done';
        }

        return 'scheduled';
    }

    private function interviewLabelForLevel($interviews, int $levelId, string $fallback): string
    {
        $rows = $interviews->filter(function ($row) use ($levelId) {
            return (int) ($row->panel_group_level ?? 0) === $levelId;
        });

        if ($rows->isEmpty()) {
            return $fallback;
        }

        $latest = $rows->sortByDesc(function ($row) {
            return ($row->start_date ?? '') . ' ' . ($row->start_time ?? '');
        })->first();

        return $latest->interview_name ?: $fallback;
    }
}
