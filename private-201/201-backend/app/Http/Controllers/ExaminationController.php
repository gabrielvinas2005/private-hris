<?php

namespace App\Http\Controllers;

use Auth;
use App\User;
use App\Traits\ApiResponse;
use Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Notifications\EmailApplicantForExamination;

class ExaminationController extends Controller
{
    use ApiResponse;

    public function categoryItemsCount($categoryId)
    {
        try {
            $categoryId = (int) $categoryId;

            $totalItemsInCategory = (int) DB::table('exam_sub_categories as sc')
                ->join('exam_questionaire_headers as qh', 'sc.id', '=', 'qh.sub_category_id')
                ->where('sc.category_id', $categoryId)
                ->distinct('qh.id')
                ->count('qh.id');

            return $this->successResponse([
                'category_id' => $categoryId,
                'total_items' => $totalItemsInCategory
            ], 'Category items count retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve category items count: ' . $e->getMessage());
        }
    }

    /**
     * Check if applicant_shortlisted has position_applied_id column (per-position shortlisting).
     */
    private function hasShortlistPositionColumn(): bool
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlsrv') {
            $result = DB::selectOne(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'applicant_shortlisted' AND COLUMN_NAME = 'position_applied_id'"
            );
            return $result !== null;
        }
        return \Schema::hasColumn('applicant_shortlisted', 'position_applied_id');
    }

    public function index()
    {
        try {
            $exams = DB::table('examination_setup_header')->get();

            return $this->successResponse($exams, 'Examination setup list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve examination setup list: ' . $e->getMessage());
        }
    }

    public function add(Request $request, $id)
    {
        try {
            $exams = DB::table('examination_setup_header')->where('id', $id)->get();

            if ($exams->isEmpty()) {
                $exams = [
                    'id' => 0,
                    'exam_set' => null,
                    'exam_instruction' => null,
                    'exam_duration' => null,
                    'passing_criteria' => null,
                    'weighted_allocation' => null,
                    'with_video_recording' => false,
                    'category_id' => 0,
                    'exam_type_id' => null
                ];

                $exams = (object)$exams;
                $exams = collect([$exams]);
            }

            $exam_categories = DB::table('exam_categories')->orderBy('category_code', 'asc')->get();
            $exam_types = DB::table('exam_types')->select('id', 'code', 'name')->orderBy('id', 'asc')->get();

            return $this->successResponse([
                'exams' => $exams,
                'exam_categories' => $exam_categories,
                'exam_types' => $exam_types
            ], 'Examination setup form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load examination setup form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $examTypeId = $request->exam_type_id ? (int) $request->exam_type_id : null;
            $examTypeCode = null;
            if ($examTypeId) {
                $examTypeCode = DB::table('exam_types')->where('id', $examTypeId)->value('code');
            }
            $isPsychologicalExam = strtolower((string) $examTypeCode) === 'psychological';

            $validated = $request->validate([
                'exam_set' => [
                    'required',
                    'string',
                    Rule::unique('examination_setup_header', 'exam_set')->ignore((int) $id, 'id'),
                ],
                'exam_instruction' => 'nullable|string',
                'exam_duration' => $isPsychologicalExam ? 'nullable|numeric|min:0' : 'required|numeric|min:1',
                'passing_criteria' => $isPsychologicalExam ? 'nullable|integer|min:0' : 'required|integer|min:0',
                'weighted_allocation' => 'nullable',
                'category_id' => $isPsychologicalExam ? 'nullable|integer|min:0' : 'required|integer|exists:exam_categories,id',
                'exam_type_id' => 'nullable|integer',
            ]);

            if (!$isPsychologicalExam) {
                // Passing score cannot exceed the total number of question items under the selected category
                $categoryId = (int) $validated['category_id'];
                $totalItemsInCategory = (int) DB::table('exam_sub_categories as sc')
                    ->join('exam_questionaire_headers as qh', 'sc.id', '=', 'qh.sub_category_id')
                    ->where('sc.category_id', $categoryId)
                    ->distinct('qh.id')
                    ->count('qh.id');

                $passingCriteria = (int) $validated['passing_criteria'];
                if ($passingCriteria > $totalItemsInCategory) {
                    return $this->validationErrorResponse([
                        'passing_criteria' => [
                            "Passing score cannot be greater than the total items in the selected category ({$totalItemsInCategory})."
                        ]
                    ]);
                }
            }

            if ($id == 0) {
                $id = 0 + DB::table('examination_setup_header')->max('id');
                $id += 1;
            }

            $data = [
                'exam_set' => $validated['exam_set'],
                'exam_instruction' => $validated['exam_instruction'] ?? null,
                'exam_duration' => $isPsychologicalExam ? 0 : $validated['exam_duration'],
                'passing_criteria' => $isPsychologicalExam ? 0 : $validated['passing_criteria'],
                'weighted_allocation' => $validated['weighted_allocation'] ?? null,
                'with_video_recording' => false,
                'category_id' => $isPsychologicalExam ? 0 : $validated['category_id'],
                'exam_type_id' => $validated['exam_type_id'] ?? null
            ];

            DB::unprepared('SET IDENTITY_INSERT examination_setup_header ON');
            DB::table('examination_setup_header')->updateOrInsert(['id' => $id], $data);
            DB::unprepared('SET IDENTITY_INSERT examination_setup_header OFF');

            return $this->successResponse(['id' => $id], 'Examination setup saved successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save examination setup: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $exists = DB::table('examination_setup_header')->where('id', $id)->exists();
            if (!$exists) {
                return $this->notFoundResponse('Examination setup not found.');
            }

            DB::beginTransaction();

            // Cascade delete schedules + tagged/submitted exam records under those schedules
            $scheduleIds = DB::table('examination_schedule_header')
                ->where('exam_id', $id)
                ->pluck('id')
                ->toArray();

            if (!empty($scheduleIds)) {
                $applicantExamHeaderIds = DB::table('applicant_examination_headers')
                    ->whereIn('exam_schedule_id', $scheduleIds)
                    ->pluck('id')
                    ->toArray();

                if (!empty($applicantExamHeaderIds)) {
                    // Delete answers/details first
                    DB::table('applicant_examination_details')
                        ->whereIn('applicant_examination_id', $applicantExamHeaderIds)
                        ->delete();
                }

                // Delete tagged examinees / exam attempt headers
                DB::table('applicant_examination_headers')
                    ->whereIn('exam_schedule_id', $scheduleIds)
                    ->delete();

                // Delete schedules
                DB::table('examination_schedule_header')
                    ->whereIn('id', $scheduleIds)
                    ->delete();
            }

            // Remove related positions (if any)
            DB::table('examination_positions')->where('exam_id', $id)->delete();

            // Remove the exam setup header
            DB::table('examination_setup_header')->where('id', $id)->delete();

            DB::commit();

            return $this->successResponse(['id' => $id], 'Examination setup deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete examination setup: ' . $e->getMessage());
        }
    }

    public function add_position(Request $request, $id)
    {
        try {
            $positions_data = $request->all();
            $data = [];

            if (!isset($request->select)) {
                return $this->errorResponse('Please select atleast one position.', 400);
            }

            for ($i = 0; $i < count($positions_data['id']); $i++) {

                if (in_array($positions_data['id'][$i], $positions_data['select'])) {
                    $select = true;
                } else {
                    $select = false;
                }

                if ($select == true) {
                    $data = [
                        'exam_id' => $id,
                        'position_id' => $positions_data['select'][$i]
                    ];

                    DB::table('examination_positions')->insert($data);
                }
            }

            return $this->successResponse(['id' => $id], 'Positions added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add positions: ' . $e->getMessage());
        }
    }

    public function delete_position($id)
    {
        try {
            DB::table('examination_positions')->where('id', $id)->delete();
            return $this->successResponse(['id' => $id], 'Position deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete position: ' . $e->getMessage());
        }
    }

    public function questionaire(Request $request, $id, $type_id)
    {
        try {
            return $this->successResponse($type_id, 'Examination setup questionnaires data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve examination setup questionnaires data: ' . $e->getMessage());
        }
    }

    public function schedules()
    {
        try {
            $exam_schedules = DB::table('examination_schedule_header as a')
                ->join('examination_setup_header as b', 'a.exam_id', '=', 'b.id')
                ->leftJoin('exam_categories as c', 'b.category_id', '=', 'c.id')
                ->leftJoin('exam_types as et', 'b.exam_type_id', '=', 'et.id')
                ->select(
                    'a.*',
                    'b.exam_set',
                    'b.exam_duration',
                    'b.passing_criteria',
                    DB::raw("COALESCE(c.name, et.name, 'N/A') as exam_category")
                )
                ->get();

            $scheduleIds = $exam_schedules->pluck('id')->toArray();
            $completedCounts = [];
            if (!empty($scheduleIds)) {
                $completedCounts = DB::table('applicant_examination_headers')
                    ->whereIn('exam_schedule_id', $scheduleIds)
                    ->where(function ($q) {
                        $q->where('is_complete', true)->orWhere('is_complete', 1);
                    })
                    ->select('exam_schedule_id', DB::raw('count(*) as completed_count'))
                    ->groupBy('exam_schedule_id')
                    ->pluck('completed_count', 'exam_schedule_id')
                    ->toArray();
            }

            $now = Carbon::now();
            $exam_schedules = $exam_schedules->map(function ($row) use ($now, $completedCounts) {
                $row = (object) (array) $row;
                $endDate = $row->exam_date_to ?? null;
                $endTime = $row->exam_time_to ?? null;
                $periodPassed = false;
                if ($endDate && $endTime) {
                    $end = Carbon::parse($endDate . ' ' . $endTime);
                    $periodPassed = $end->lt($now);
                }
                $completedCount = (int) ($completedCounts[$row->id] ?? 0);
                // Expired: date/time from-to has passed AND no tagged applicant took the exam
                $row->is_expired = $periodPassed && ($completedCount === 0);
                // Done: at least one tagged applicant has submitted (schedule moves to Done when anyone submits)
                $row->is_done = $completedCount > 0;
                return $row;
            });

            return $this->successResponse($exam_schedules, 'Examination schedules retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve examination schedules: ' . $e->getMessage());
        }
    }

    public function schedules_add($id)
    {
        try {
            $hasShortlistPositionColumn = $this->hasShortlistPositionColumn();

            $exam_schedules = DB::table('examination_schedule_header as a')
                ->where('a.id', $id)
                ->get();

            if ($exam_schedules->isEmpty()) {
                $exam_schedules = [
                    'id' => 0,
                    'exam_id' => null,
                    'exam_date_from' => null,
                    'exam_date_to' => null,
                    'exam_time_from' => null,
                    'exam_time_to' => null,
                    'posted' => null,
                    'posted_by' => null,
                    'posted_date' => null,
                    'is_expired' => null
                ];

                $exam_schedules = (object)$exam_schedules;
                $exam_schedules = collect([$exam_schedules]);
            }

            if ($id == 0) {
                $exams = DB::table('examination_setup_header')
                    ->whereNotIn('id', function ($query) {
                        $query->select('exam_id')->from('examination_schedule_header');
                    })
                    ->get();
            } else {
                $exams_selected = DB::table('examination_setup_header')
                    ->whereNotIn('id', function ($query) {
                        $query->select('exam_id')->from('examination_schedule_header');
                    });
                $exams = DB::table('examination_setup_header')->where('id', $id)
                    ->unionAll($exams_selected)
                    ->get();
            }

            // Get exam_details with actual count of questions answered per subcategory
            $exam_details = DB::table('examination_setup_header as a')
                ->join('exam_categories as b', 'a.category_id', '=', 'b.id')
                ->join('exam_sub_categories as c', 'b.id', '=', 'c.category_id')
                ->leftJoin('examination_positions as d', 'c.id', '=', 'd.exam_id')
                ->join('examination_schedule_header as e', 'a.id', '=', 'e.exam_id')
                ->leftJoin('plantillas as f', 'd.position_id', '=', 'f.id')
                ->leftJoin('positions as g', 'f.position_id', '=', 'g.id')
                ->join('exam_difficulty_levels as h', 'c.difficulty_level', '=', 'h.id')
                ->leftJoin('exam_questionaire_headers as q', 'c.id', '=', 'q.sub_category_id')
                ->leftJoin('applicant_examination_headers as aeh', 'aeh.exam_schedule_id', '=', 'e.id')
                ->leftJoin('applicant_examination_details as aed', function ($join) {
                    $join->on('aed.applicant_examination_id', '=', 'aeh.id')
                        ->on('aed.question_id', '=', 'q.id');
                })
                ->select(
                    'a.id',
                    'e.id as exam_schedule_id',
                    'b.name as category',
                    'b.description',
                    'c.sub_category',
                    'f.code',
                    'g.name as position',
                    'h.difficulty_level',
                    DB::raw('COUNT(DISTINCT aed.question_id) as existing_questions')
                )
                ->where('e.id', $id)
                ->groupBy(
                    'a.id',
                    'e.id',
                    'b.name',
                    'b.description',
                    'c.sub_category',
                    'f.code',
                    'g.name',
                    'h.difficulty_level'
                )
                ->get();

            // Get exam_id from the schedule
            $examSchedule = DB::table('examination_schedule_header')
                ->where('id', $id)
                ->first();

            $examId = $examSchedule ? $examSchedule->exam_id : null;

            // Get position IDs for this exam
            $examPositionIds = [];
            if ($examId) {
                $examPositionIds = DB::table('examination_positions')
                    ->where('exam_id', $examId)
                    ->pluck('position_id')
                    ->toArray();
            }

            // Query shortlisted applicants
            $applicantsQuery = DB::table('applicant_headers as a')
                ->join('applicant_details as b', 'a.id', '=', 'b.applicant_id')
                ->join('applicant_shortlisted as c', function ($join) use ($hasShortlistPositionColumn) {
                    $join->on('a.id', '=', 'c.applicant_id');
                    if ($hasShortlistPositionColumn) {
                        $join->where(function ($q) {
                            $q->whereColumn('c.position_applied_id', 'b.position_applied_id')
                                ->orWhereNull('c.position_applied_id');
                        });
                    }
                });

            // Filter by positions if exam has positions assigned
            if (!empty($examPositionIds)) {
                $applicantsQuery->whereIn('b.position_applied_id', $examPositionIds);
            }

            $applicants = $applicantsQuery
                ->select(
                    'a.id',
                    'a.applicant_no',
                    DB::raw("UPPER(CONCAT(a.first_name,' ',a.last_name)) as name")
                )
                // Hide hired applicants from examination tagging list (header + per-position status guards).
                ->whereRaw('ISNULL(a.application_status_id, 0) <> 6')
                ->whereRaw('ISNULL(b.application_status_id, 0) <> 6')
                ->whereNotIn('a.id', function ($query) use ($id) {
                    $query->select('applicant_id')->from('applicant_examination_headers')->where('exam_schedule_id', $id);
                })
                ->distinct()
                ->get();

            $applicant_positions = DB::table('applicant_details as a')
                ->join('applicant_shortlisted as sl', function ($join) use ($hasShortlistPositionColumn) {
                    $join->on('a.applicant_id', '=', 'sl.applicant_id');
                    if ($hasShortlistPositionColumn) {
                        $join->where(function ($q) {
                            $q->whereColumn('sl.position_applied_id', 'a.position_applied_id')
                                ->orWhereNull('sl.position_applied_id');
                        });
                    }
                })
                ->leftJoin('plantillas as b', function ($join) {
                    $join->on('a.position_applied_id', '=', 'b.id')
                        ->whereRaw('ISNULL(a.is_plantilla, 1) = 1');
                })
                ->leftJoin('non_plantillas as np', function ($join) {
                    $join->on('a.position_applied_id', '=', 'np.id')
                        ->whereRaw('ISNULL(a.is_plantilla, 1) = 0');
                })
                ->leftJoin('positions as cp', 'b.position_id', '=', 'cp.id')
                ->leftJoin('positions as cnp', 'np.position_id', '=', 'cnp.id')
                ->select(
                    'a.applicant_id',
                    DB::raw('ISNULL(b.code, NULL) as code'),
                    DB::raw('COALESCE(cp.name, cnp.name) as position')
                )
                ->distinct()
                ->get();

            $examinees = DB::table('applicant_examination_headers as a')
                ->join('examination_schedule_header as b', 'b.id', '=', 'a.exam_schedule_id')
                ->join('applicant_headers as c', 'a.applicant_id', '=', 'c.id')
                ->leftJoin('applicant_shortlisted as sl', function ($join) use ($hasShortlistPositionColumn) {
                    $join->on('sl.applicant_id', '=', 'c.id');
                    if ($hasShortlistPositionColumn) {
                        $join->whereNotNull('sl.position_applied_id');
                    }
                })
                ->leftJoin('applicant_details as ad', function ($join) use ($hasShortlistPositionColumn) {
                    $join->on('ad.applicant_id', '=', 'c.id');
                    if ($hasShortlistPositionColumn) {
                        $join->on('ad.position_applied_id', '=', 'sl.position_applied_id');
                    }
                })
                // Position resolution
                ->leftJoin('plantillas as pl', function ($join) {
                    $join->on('ad.position_applied_id', '=', 'pl.id')
                        ->whereRaw('ISNULL(ad.is_plantilla, 1) = 1');
                })
                ->leftJoin('non_plantillas as np', function ($join) {
                    $join->on('ad.position_applied_id', '=', 'np.id')
                        ->whereRaw('ISNULL(ad.is_plantilla, 1) = 0');
                })
                ->leftJoin('positions as posp', 'pl.position_id', '=', 'posp.id')
                ->leftJoin('positions as posnp', 'np.position_id', '=', 'posnp.id')
                // Essay review completeness
                ->leftJoin('applicant_examination_details as aed', 'aed.applicant_examination_id', '=', 'a.id')
                ->leftJoin('exam_questionaire_headers as qh', 'aed.question_id', '=', 'qh.id')
                ->leftJoin('exam_sub_categories as sc', 'qh.sub_category_id', '=', 'sc.id')
                ->select(
                    'a.id',
                    'a.is_complete',
                    'a.exam_rating',
                    'c.id as applicant_id',
                    'c.applicant_no',
                    DB::raw("UPPER(CONCAT(c.first_name,' ',c.last_name)) as name"),
                    DB::raw('COALESCE(posp.name, posnp.name) as position'),
                    // essay_reviewed: 1 when there are no essay items OR at least one essay item reviewed correct/wrong
                    DB::raw("CASE 
                        WHEN SUM(CASE WHEN LOWER(ISNULL(sc.sub_category,'')) LIKE '%essay%' THEN 1 ELSE 0 END) = 0 
                            THEN 1
                        WHEN SUM(CASE WHEN LOWER(ISNULL(sc.sub_category,'')) LIKE '%essay%' AND (ISNULL(aed.correct,0)+ISNULL(aed.wrong,0)) > 0 THEN 1 ELSE 0 END) > 0 
                            THEN 1
                        ELSE 0 END as essay_reviewed")
                )
                ->where('a.exam_schedule_id', $id)
                ->when(!empty($examPositionIds), function ($q) use ($examPositionIds) {
                    $q->whereIn('ad.position_applied_id', $examPositionIds);
                })
                ->when($hasShortlistPositionColumn, function ($q) {
                    $q->whereNotNull('sl.position_applied_id');
                })
                ->groupBy('a.id','a.is_complete','a.exam_rating','c.id','c.applicant_no','c.first_name','c.last_name','posp.name','posnp.name')
                ->distinct()
                ->get();

            return $this->successResponse([
                'exam_schedules' => $exam_schedules,
                'exams' => $exams,
                'exam_details' => $exam_details,
                'applicants' => $applicants,
                'applicant_positions' => $applicant_positions,
                'examinees' => $examinees
            ], 'Examination schedule form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load examination schedule form data: ' . $e->getMessage());
        }
    }

    public function schedules_store(Request $request, $id)
    {
        try {
            $request->validate([
                'exam_id' => 'required',
                'exam_date_from' => 'required',
                'exam_date_to' => 'required',
                'exam_time_from' => 'required',
                'exam_time_to' => 'required',
            ]);

            if ($id == 0) {
                $id = 0 + DB::table('examination_schedule_header')->max('id');
                $id += 1;
            }

            $data = [
                'exam_id' => $request->exam_id,
                'exam_date_from' => $request->exam_date_from,
                'exam_date_to' => $request->exam_date_to,
                'exam_time_from' => $request->exam_time_from,
                'exam_time_to' => $request->exam_time_to
            ];

            DB::unprepared('SET IDENTITY_INSERT examination_schedule_header ON');
            DB::table('examination_schedule_header')->updateOrInsert(['id' => $id], $data);
            DB::unprepared('SET IDENTITY_INSERT examination_schedule_header OFF');

            return $this->successResponse(['id' => $id], 'Examination schedule saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save examination schedule: ' . $e->getMessage());
        }
    }

    public function schedule_process($id, $type_id)
    {
        try {
            if ($type_id == 1) {
                DB::table('examination_schedule_header')->where('id', $id)->update([
                    'posted' => true,
                    'posted_by' => Auth::user()->id,
                    'posted_date' => now()
                ]);

                // send email to applicants
                $applicants = DB::table('examination_schedule_header as a')
                    ->join('applicant_examination_headers as b', 'a.id', '=', 'b.exam_schedule_id')
                    ->join('applicant_headers as c', 'b.applicant_id', '=', 'c.id')
                    ->select(
                        'c.id',
                        'c.applicant_no',
                        'c.email',
                        DB::raw("UPPER(CONCAT(c.first_name,' ',c.last_name)) as name"),
                        'a.exam_date_from',
                        'a.exam_date_to',
                        'a.exam_time_from',
                        'a.exam_time_to'
                    )
                    ->where('a.id', $id)
                    ->get();

                foreach ($applicants as $applicant) {

                    $applicant_data = DB::table('examination_schedule_header as a')
                        ->join('examination_setup_header as d', 'a.exam_id', '=', 'd.id')
                        ->join('applicant_examination_headers as b', 'a.id', '=', 'b.exam_schedule_id')
                        ->join('applicant_headers as c', 'b.applicant_id', '=', 'c.id')
                        ->select(
                            'c.applicant_no',
                            'c.email',
                            DB::raw("UPPER(CONCAT(c.first_name,' ',c.last_name)) as name"),
                            'a.exam_date_from',
                            'a.exam_date_to',
                            'a.exam_time_from',
                            'a.exam_time_to',
                            'd.exam_instruction'
                        )
                        ->where('a.id', $id)
                        ->where('c.id', $applicant->id)
                        ->get();

                    $user_account = User::where('employee_no', $applicant->applicant_no)->get();

                    Notification::send($user_account, new EmailApplicantForExamination($applicant_data));
                }
            } else {
                DB::table('examination_schedule_header')->where('id', $id)->update(['posted' => false]);
            }

            return $this->successResponse(['id' => $id, 'type_id' => $type_id], 'Examination schedule processed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process examination schedule: ' . $e->getMessage());
        }
    }

    public function schedules_result($id)
    {
        try {
            return $this->successResponse([], 'Examination schedule result data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve examination schedule result data: ' . $e->getMessage());
        }
    }

    public function addExaminees(Request $request, $id)
    {
        try {
            $examinees_data = $request->all();
            $data = [];

            if (!isset($request->select)) {
                return $this->errorResponse('Please select atleast one applicant.', 400);
            }

            for ($i = 0; $i < count($examinees_data['id']); $i++) {

                if (in_array($examinees_data['id'][$i], $examinees_data['select'])) {
                    $select = true;
                } else {
                    $select = false;
                }

                if ($select == true) {
                    $data = [
                        'applicant_id' => $examinees_data['id'][$i],
                        'exam_schedule_id' => $id
                    ];

                    DB::table('applicant_examination_headers')->insert($data);
                }
            }

            return $this->successResponse(['id' => $id], 'Examinees added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add examinees: ' . $e->getMessage());
        }
    }

    public function deleteExaminees($id)
    {
        try {
            DB::table('applicant_examination_headers')->where('id', $id)->delete();

            return $this->successResponse(['id' => $id], 'Examinee deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete examinee: ' . $e->getMessage());
        }
    }

    /**
     * Delete an examination schedule and its tagged examinees / answers.
     */
    public function schedules_destroy($id)
    {
        try {
            $exists = DB::table('examination_schedule_header')->where('id', $id)->exists();
            if (!$exists) {
                return $this->notFoundResponse('Examination schedule not found.');
            }

            DB::beginTransaction();

            $applicantExamHeaderIds = DB::table('applicant_examination_headers')
                ->where('exam_schedule_id', $id)
                ->pluck('id')
                ->toArray();

            if (!empty($applicantExamHeaderIds)) {
                DB::table('applicant_examination_details')
                    ->whereIn('applicant_examination_id', $applicantExamHeaderIds)
                    ->delete();

                DB::table('applicant_examination_headers')
                    ->whereIn('id', $applicantExamHeaderIds)
                    ->delete();
            }

            DB::table('examination_schedule_header')->where('id', $id)->delete();

            DB::commit();

            return $this->successResponse(['id' => $id], 'Examination schedule deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete examination schedule: ' . $e->getMessage());
        }
    }

    public function examTypes()
    {
        try {
            $examTypes = DB::table('exam_types')
                ->select('id', 'code', 'name')
                ->orderBy('id', 'asc')
                ->get();

            return $this->successResponse([
                'exam_types' => $examTypes
            ], 'Exam types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve exam types: ' . $e->getMessage());
        }
    }
}
