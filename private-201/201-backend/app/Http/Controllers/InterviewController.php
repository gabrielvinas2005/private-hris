<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Notifications\EmailInterviewSchedules;
use App\User;
use App\Traits\ApiResponse;

class InterviewController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $inteview_schedules = DB::table('applicant_interview_headers as a')
                ->join('interview_levels as b', 'a.panel_group_level', '=', 'b.id')
                ->select(
                    'a.*',
                    'b.interview_level as level',
                    // Derived counts for UI tabs (Done Interviews)
                    DB::raw("(
                        select count(*)
                        from interview_applicants ia
                        where ia.interview_id = a.id
                    ) as applicant_count"),
                    DB::raw("(
                        select count(*)
                        from interview_applicants ia
                        where ia.interview_id = a.id
                          and ISNULL(ia.is_cancelled_interview, 0) = 0
                          and ISNULL(ia.is_complete_interview, 0) = 1
                    ) as completed_applicant_count"),
                    DB::raw("(
                        select count(*)
                        from interview_applicants ia
                        where ia.interview_id = a.id
                          and ISNULL(ia.is_cancelled_interview, 0) = 0
                          and ISNULL(ia.is_complete_interview, 0) = 0
                    ) as pending_applicant_count"),
                    // NOTE: "Done Interviews" should reflect explicit completion (clicked Done),
                    // not an auto-derived state from applicant completion flags.
                    DB::raw("ISNULL(a.is_done, 0) as is_done")
                )
                ->get();

            $now = Carbon::now();
            $inteview_schedules = $inteview_schedules->map(function ($row) use ($now) {
                $row = (object) (array) $row;
                $row->is_expired = $this->isInterviewScheduleExpired($row, $now);
                return $row;
            });

            return $this->successResponse([
                'inteview_schedules' => $inteview_schedules
            ], 'Interview schedules loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve interview schedules: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $interview_data = DB::table('applicant_interview_headers')->where('id', $id)->get();
            $interview_levels = DB::table('interview_levels')->where('active', true)->orderBy('id', 'asc')->get();

            if ($interview_data->isEmpty()) {

                $interview_data = [
                    'id' => 0,
                    'panel_group' => null,
                    'interview_location' => null,
                    'description' => null,
                    'panel_group_level' => 0,
                    'start_date' => null,
                    'end_date' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'posted' => null,
                    'posted_by' => null,
                    'posted_date' => null
                ];

                $interview_data = (object)$interview_data;
                $interview_data = collect([$interview_data]);
            }

            $interview_panels = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->join('interview_panels as c', 'a.id', '=', 'c.employee_id')
                ->select(
                    'c.id',
                    'a.id as employee_id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            CONCAT(a.first_name,' ',a.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                        END as name"),
                    'b.name as position',
                    DB::raw('ISNULL(c.is_complete_rating, 0) as is_complete_rating')
                )
                ->where([
                    'a.active' => true,
                    'a.is_employee' => true,
                    'c.interview_id' => $id
                ])
                ->orderBy('name', 'asc')
                ->get();

            $interview_applicants = DB::table('interview_applicants as c')
                ->join('applicant_headers as b', 'c.applicant_id', '=', 'b.id')
                ->leftJoin('users as u', 'b.user_id', '=', 'u.id')
                ->select(
                    'c.id',
                    'b.id as applicant_id',
                    'b.applicant_no',
                    DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as name")
                )
                ->where('c.interview_id', $id)
                ->where($this->applicantUserEligibilityConstraint('u', 'b'))
                ->whereRaw('ISNULL(b.application_status_id, 0) <> 6')
                ->distinct()
                ->orderBy('name', 'asc')
                ->get();

            $interview_level_name = null;
            $firstInterview = $interview_data->first();
            if ($firstInterview && !empty($firstInterview->panel_group_level)) {
                $interview_level_name = DB::table('interview_levels')
                    ->where('id', $firstInterview->panel_group_level)
                    ->value('interview_level');
            }

            // Panel-by-applicant ratings (done interviews / HR review)
            $panel_rating_breakdown = [];
            if ((int) $id > 0) {
                $latestAttachmentByKey = [];
                if (Schema::hasTable('interview_panel_attachments')) {
                    $attachmentRows = DB::table('interview_panel_attachments')
                        ->where('interview_id', (int) $id)
                        ->select('id', 'interview_id', 'employee_id', 'applicant_id', 'original_name', 'stored_name', 'path')
                        ->orderBy('id', 'desc')
                        ->get();
                    foreach ($attachmentRows as $att) {
                        $k = ((int) ($att->interview_id ?? 0)) . '|' . ((int) ($att->employee_id ?? 0)) . '|' . ((int) ($att->applicant_id ?? 0));
                        if (!isset($latestAttachmentByKey[$k])) {
                            $latestAttachmentByKey[$k] = $att;
                        }
                    }
                }

                $panel_rating_breakdown = DB::table('interview_panel_ratings as r')
                    ->join('employees as e', 'r.employee_id', '=', 'e.id')
                    ->join('positions as pos', 'e.position_id', '=', 'pos.id')
                    ->join('applicant_headers as ah', 'r.applicant_id', '=', 'ah.id')
                    ->where('r.interview_id', (int) $id)
                    ->select(
                        'r.id as rating_id',
                        'r.interview_id',
                        'r.applicant_id',
                        'ah.applicant_no',
                        DB::raw("UPPER(CONCAT(ah.first_name,' ',ah.last_name)) as applicant_name"),
                        'r.employee_id',
                        'e.employee_no as panel_employee_no',
                        DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CONCAT(e.first_name,' ',e.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                            END as panel_name"),
                        'pos.name as panel_position',
                        DB::raw('ISNULL(r.bei_rating, 0) as bei_rating'),
                        DB::raw('ISNULL(r.competency_rating, 0) as competency_rating')
                    )
                    ->orderBy('applicant_name', 'asc')
                    ->orderBy('panel_name', 'asc')
                    ->get()
                    ->map(function ($row) use ($latestAttachmentByKey) {
                        $k = ((int) ($row->interview_id ?? 0)) . '|' . ((int) ($row->employee_id ?? 0)) . '|' . ((int) ($row->applicant_id ?? 0));
                        $att = $latestAttachmentByKey[$k] ?? null;
                        if ($att) {
                            $row->supporting_document_label = $att->original_name ?: ($att->stored_name ?: basename((string) ($att->path ?? 'document')));
                            $row->supporting_document_download_url = url('/api/interview-schedules/panel-rating-document/' . (int) $att->id);
                        } else {
                            $row->supporting_document_label = null;
                            $row->supporting_document_download_url = null;
                        }
                        return $row;
                    })
                    ->values()
                    ->all();
            }

            $panel_review_attachments = [];
            if ((int) $id > 0 && Schema::hasTable('interview_panel_attachments')) {
                $panel_review_attachments = DB::table('interview_panel_attachments as ipa')
                    ->join('applicant_headers as ah', 'ipa.applicant_id', '=', 'ah.id')
                    ->join('employees as e', 'ipa.employee_id', '=', 'e.id')
                    ->leftJoin('positions as pos', 'e.position_id', '=', 'pos.id')
                    ->where('ipa.interview_id', (int) $id)
                    ->select(
                        'ipa.id as attachment_id',
                        'ipa.applicant_id',
                        'ipa.employee_id',
                        'ah.applicant_no',
                        DB::raw("UPPER(CONCAT(ah.first_name,' ',ah.last_name)) as applicant_name"),
                        'e.employee_no as panel_employee_no',
                        DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CONCAT(e.first_name,' ',e.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                            END as panel_name"),
                        'pos.name as panel_position',
                        'ipa.original_name',
                        'ipa.stored_name',
                        'ipa.mime_type'
                    )
                    ->orderBy('ipa.id', 'desc')
                    ->get()
                    ->map(function ($row) {
                        $row->attachment_label = $row->original_name ?: ($row->stored_name ?: 'Attachment');
                        $row->download_url = url('/api/interview-schedules/panel-rating-document/' . (int) $row->attachment_id);
                        return $row;
                    })
                    ->values()
                    ->all();
            }

            $panel_select = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
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
                    'a.is_employee' => true
                ])
                ->whereNotIn('a.id', function ($query) use ($id) {
                    $query->select('employee_id')->from('interview_panels')->where('interview_id', $id);
                })
                ->orderBy('name', 'asc')
                ->get();

            $applicant_select = DB::table('applicant_headers as b')
                ->leftJoin('users as u', 'b.user_id', '=', 'u.id')
                ->leftJoin('applicant_details as ad', 'b.id', '=', 'ad.applicant_id')
                ->select(
                    'b.id as applicant_id',
                    'b.applicant_no',
                    DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as name")
                )
                ->where($this->applicantUserEligibilityConstraint('u', 'b'))
                ->whereRaw('ISNULL(b.application_status_id, 0) <> 6')
                ->whereRaw('ISNULL(ad.application_status_id, 0) <> 6')
                ->whereNotIn('b.id', function ($query) use ($id) {
                    $query->select('applicant_id')->from('interview_applicants')->where('interview_id', $id);
                })
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('applicant_examination_headers as a')
                        ->whereColumn('a.applicant_id', 'b.id')
                        ->where(function ($q) {
                            $q->where('a.is_complete', 1)->orWhere('a.is_complete', true);
                        });
                })
                ->distinct()
                ->orderBy('name', 'asc')
                ->get();

            $applicant_positions = DB::table('applicant_examination_headers as a')
                ->join('applicant_headers as b', 'a.applicant_id', '=', 'b.id')
                ->join('users as u', 'b.user_id', '=', 'u.id')
                ->join('applicant_details as c', 'b.id', '=', 'c.applicant_id')
                ->join('plantillas as d', 'c.position_applied_id', '=', 'd.id')
                ->join('positions as e', 'd.position_id', '=', 'e.id')
                ->select(
                    'c.applicant_id',
                    'e.name as position'
                )
                ->where('a.is_complete', true)
                ->where('u.is_applicant', 1) // Only include applicants where is_applicant = 1
                ->whereNotIn('b.application_status_id', [5, 6]) // Exclude For Hiring(5) and Hired(6)
                ->get();

            return $this->successResponse([
                'interview_levels' => $interview_levels,
                'interview_data' => $interview_data,
                'interview_panels' => $interview_panels,
                'interview_applicants' => $interview_applicants,
                'panel_select' => $panel_select,
                'applicant_select' => $applicant_select,
                'applicant_positions' => $applicant_positions,
                'interview_level_name' => $interview_level_name,
                'panel_rating_breakdown' => $panel_rating_breakdown,
                'panel_review_attachments' => $panel_review_attachments,
            ], 'Interview add data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load interview form: ' . $e->getMessage());
        }
    }

    public function panel_interview()
    {
        try {
            $id = Auth::user()->id;

            $emp_id_data = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->leftJoin('departments as c', 'b.id', '=', 'c.employee_id')
                ->selectRaw('case when b.id = null then 0 else b.id end as id')
                ->selectRaw('case when c.employee_id = null then 0 else c.employee_id end as supervisor_id')
                ->selectRaw('b.gender_id')
                ->where('a.id', $id)
                ->get();

            if (isset($emp_id_data[0]->id)) {
                $employee_id = $emp_id_data[0]->id;
            } else {
                $employee_id = 0;
            }

            $interviews = DB::table('applicant_interview_headers as a')
                ->join('interview_applicants as b', 'a.id', '=', 'b.interview_id')
                ->join('interview_levels as c', 'a.panel_group_level', '=', 'c.id')
                ->join('interview_panels as d', 'd.interview_id', '=', 'a.id')
                ->join('applicant_headers as e', 'e.id', '=', 'b.applicant_id')
                ->join('users as u', 'e.user_id', '=', 'u.id')
                ->select(
                    'a.*',
                    'c.interview_level as level',
                    DB::raw("case when (b.is_complete_interview = 0 and b.is_cancelled_interview = 0 and a.start_date <= getdate() and a.end_date >= getdate()) then 'Active'
                              when (b.is_complete_interview = 0 and b.is_cancelled_interview = 0 and a.end_date < getdate()) then 'Expired'
                              when (b.is_complete_interview = 0 and b.is_cancelled_interview = 0 and a.start_date > getdate()) then 'Pending'
                              when (b.is_cancelled_interview = 1) then 'Cancelled'
                              when (b.is_complete_interview = 1) then 'Completed'
                         else '' end as status"),
                    'b.applicant_id',
                    DB::raw("UPPER(CONCAT(e.first_name,' ',e.last_name)) as name"),
                    'e.applicant_no',
                    'd.employee_id'
                )
                ->where([
                    'd.employee_id' => $employee_id,
                    'u.is_applicant' => 1, // Only include applicants where is_applicant = 1
                    'a.posted' => true
                ])
                ->whereNotIn('e.application_status_id', [6]) // Exclude For Hiring(5) and Hired(6)
                ->orderBy('a.start_date', 'desc')
                ->get();

            $applicant_positions = DB::table('applicant_examination_headers as a')
                ->join('applicant_headers as b', 'a.applicant_id', '=', 'b.id')
                ->join('users as u', 'b.user_id', '=', 'u.id')
                ->join('applicant_details as c', 'b.id', '=', 'c.applicant_id')
                ->join('plantillas as d', 'c.position_applied_id', '=', 'd.id')
                ->join('positions as e', 'd.position_id', '=', 'e.id')
                ->select(
                    'c.applicant_id',
                    'e.name as position'
                )
                ->where('a.is_complete', true)
                ->where('u.is_applicant', 1) // Only include applicants where is_applicant = 1
                ->whereNotIn('b.application_status_id', [6]) // Exclude For Hiring(5) and Hired(6)
                ->get();

            return $this->successResponse([
                'interviews' => $interviews,
                'applicant_positions' => $applicant_positions
            ], 'Panel interview data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve panel interview data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'panel_group' => 'required|string',
                'interview_location' => 'required|string',
                'description' => 'nullable|string',
                'panel_group_level' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $interview_data = [
                'panel_group' => $request->panel_group,
                'interview_location' => $request->interview_location,
                'description' => $request->description,
                'panel_group_level' => $request->panel_group_level,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                // Any edit re-opens the schedule unless explicitly marked done again.
                'is_done' => 0
            ];

            if ($id == 0) {
                $interview_data['posted'] = 0;
                $id = DB::table('applicant_interview_headers')->insertGetId($interview_data);
            } else {
                DB::table('applicant_interview_headers')->where('id', $id)->update($interview_data);
            }

            return $this->successResponse(['interview_id' => $id], 'Successfully Saved.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save interview: ' . $e->getMessage());
        }
    }

    public function addPanel(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'select' => 'required|array|min:1',
                'id' => 'required|array'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $panel_data = $request->all();
            $data = [];

            for ($i = 0; $i < count($panel_data['id']); $i++) {
                if (in_array($panel_data['id'][$i], $panel_data['select'])) {
                    $data = [
                        'interview_id' => $id,
                        'employee_id' => $panel_data['id'][$i]
                    ];

                    if (Schema::hasColumn('interview_panels', 'is_complete_rating')) {
                        $data['is_complete_rating'] = 0;
                    }

                    DB::table('interview_panels')->insert($data);
                }
            }

            return $this->successResponse(null, 'Successfully Added Panel.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add panel: ' . $e->getMessage());
        }
    }

    public function addApplicant(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'select' => 'required|array|min:1',
                'id' => 'required|array'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $applicant_data = $request->all();
            $data = [];

            for ($i = 0; $i < count($applicant_data['id']); $i++) {
                if (in_array($applicant_data['id'][$i], $applicant_data['select'])) {
                    $applicantId = (int) $applicant_data['id'][$i];
                    $alreadyAssigned = DB::table('interview_applicants')
                        ->where('interview_id', $id)
                        ->where('applicant_id', $applicantId)
                        ->exists();
                    if ($alreadyAssigned) {
                        continue;
                    }

                    $data = [
                        'interview_id' => $id,
                        'applicant_id' => $applicantId
                    ];

                    // Ensure newly-tagged applicants start as pending (not completed),
                    // otherwise the schedule is immediately classified as "Done" in the UI.
                    if (Schema::hasColumn('interview_applicants', 'is_complete_interview')) {
                        $data['is_complete_interview'] = 0;
                    }
                    if (Schema::hasColumn('interview_applicants', 'is_cancelled_interview')) {
                        $data['is_cancelled_interview'] = 0;
                    }

                    DB::table('interview_applicants')->insert($data);
                }
            }

            return $this->successResponse(null, 'Successfully Added Applicant.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add applicant: ' . $e->getMessage());
        }
    }

    public function deletePanel($id)
    {
        try {
            DB::table('interview_panels')->where('id', $id)->delete();
            return $this->successResponse(null, 'Panel deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete panel: ' . $e->getMessage());
        }
    }

    public function deleteApplicant($id)
    {
        try {
            DB::table('interview_applicants')->where('id', $id)->delete();
            return $this->successResponse(null, 'Applicant deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete applicant: ' . $e->getMessage());
        }
    }

    /**
     * Delete an entire interview schedule (header + related panels/applicants).
     * Used from the "Done Interviews" and "Expired Interviews" tabs.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            DB::table('interview_applicants')->where('interview_id', $id)->delete();
            DB::table('interview_panels')->where('interview_id', $id)->delete();
            DB::table('applicant_interview_headers')->where('id', $id)->delete();

            DB::commit();

            return $this->successResponse(null, 'Interview schedule deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete interview schedule: ' . $e->getMessage());
        }
    }

    public function process($id, $type_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // type_id:
            // 1 = post (publish + notify)
            // 0 = unpost
            // 2 = done interview (mark all applicants as completed)
            $typeId = (int) $type_id;

            if ($typeId === 1) {
                $data = [
                    'posted' => true,
                    'posted_by' => Auth::user()->id,
                    'posted_date' => now(),
                    'is_done' => 0
                ];

                // email panelist and applicant.
                $interview_applicants = DB::table('applicant_interview_headers as a')
                    ->join('interview_applicants as b', 'a.id', '=', 'b.interview_id')
                    ->join('applicant_headers as c', 'b.applicant_id', '=', 'c.id')
                    ->select(
                        'a.id',
                        'c.id as applicant_id',
                        'c.applicant_no',
                        DB::raw("UPPER(CONCAT(c.first_name,' ',c.last_name)) as name")
                    )
                    ->where([
                        'a.id' => $id
                    ])
                    ->distinct()
                    ->orderBy('name', 'asc')
                    ->get();

                foreach ($interview_applicants as $applicant) {
                    $interview_data = DB::table('applicant_interview_headers as a')
                        ->join('interview_applicants as b', 'a.id', '=', 'b.interview_id')
                        ->join('applicant_headers as c', 'b.applicant_id', '=', 'c.id')
                        ->select(
                            'c.applicant_no',
                            'c.email',
                            'c.first_name',
                            'c.last_name',
                            DB::raw("UPPER(CONCAT(c.first_name,' ',c.last_name)) as name"),
                            'a.panel_group_level',
                            'a.interview_location',
                            'a.description',
                            'a.start_date',
                            'a.end_date',
                            'a.start_time',
                            'a.end_time',
                            DB::raw("(
                                SELECT TOP 1 COALESCE(pos.name, pos_from_plantilla.name)
                                FROM applicant_details ad
                                LEFT JOIN positions pos ON ad.position_applied_id = pos.id
                                LEFT JOIN plantillas p ON ad.position_applied_id = p.id
                                LEFT JOIN positions pos_from_plantilla ON p.position_id = pos_from_plantilla.id
                                WHERE ad.applicant_id = c.id
                                ORDER BY ad.id DESC
                            ) as position_applied"),
                            db::raw('cast(1 as int) as type')
                        )
                        ->where('a.id', $id)
                        ->where('c.id', $applicant->applicant_id)
                        ->get();

                    $user_account = User::where('employee_no', $applicant->applicant_no)->get();
                    Notification::send($user_account, new EmailInterviewSchedules($interview_data));
                }

                // email panelist / interviewer
                $interview_header = DB::table('applicant_interview_headers')->where('id', $id)->first();
                $applicants_by_position = $this->fetchInterviewApplicantsGroupedByPosition((int) $id);

                $interview_panels = DB::table('employees as a')
                    ->join('interview_panels as c', 'a.id', '=', 'c.employee_id')
                    ->select(
                        'c.id',
                        'a.id as employee_id',
                        'a.employee_no',
                        'a.email',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                                END as name")
                    )
                    ->where([
                        'a.active' => true,
                        'a.is_employee' => true,
                        'c.interview_id' => $id
                    ])
                    ->orderBy('name', 'asc')
                    ->get();

                foreach ($interview_panels as $panel) {
                    $interview_data = $this->buildPanelistInterviewMailPayload(
                        $interview_header,
                        $panel,
                        $applicants_by_position
                    );

                    $user_account = User::where('employee_no', $panel->employee_no)->get();
                    Notification::send($user_account, new EmailInterviewSchedules($interview_data));
                }
            } elseif ($typeId === 2) {
                // Mark all applicants for this interview as completed.
                DB::table('interview_applicants')
                    ->where('interview_id', $id)
                    ->update([
                        'is_complete_interview' => true
                    ]);

                // After completion, unpost the schedule.
                $data = [
                    'posted' => false,
                    'posted_by' => Auth::user()->id,
                    'posted_date' => now(),
                    'is_done' => 1
                ];
            } else {
                $data = [
                    'posted' => false,
                    'posted_by' => Auth::user()->id,
                    'posted_date' => now(),
                    'is_done' => 0
                ];
            }

            DB::table('applicant_interview_headers')->where('id', $id)->update($data);

            return $this->successResponse(null, $typeId === 2 ? 'Interview marked as done successfully' : 'Interview processed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process interview: ' . $e->getMessage());
        }
    }

    public function cancelInterview($id, $applicant_id)
    {
        try {
            DB::table('interview_applicants')->where([
                'interview_id' => $id,
                'applicant_id' => $applicant_id
            ])
                ->update([
                    'is_cancelled_interview' => true
                ]);

            return $this->successResponse(null, 'Interview cancelled successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to cancel interview: ' . $e->getMessage());
        }
    }

    public function panel_interview_pds($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $applicant = DB::table('applicant_headers as a')
                ->join('genders as b', 'a.gender', '=', 'b.id')
                ->select('a.*', 'b.name as gender')
                ->where('a.id', $id)
                ->get();

            $employee_data = DB::table('employees as a')->where(['employee_no' => $applicant[0]->applicant_no])->get();

            if ($employee_data->isEmpty()) {
                $emp_id = 0;
            } else {
                $emp_id = $employee_data[0]->id;
            }

            if ($emp_id == 0) {
                $dummy_employee_info =
                    array(
                        'id' => 0,
                        'photo' => '',
                        'employee_no' => '',
                        'access_no' => '',
                        'name_prefix_id' => 0,
                        'first_name' => '',
                        'middle_name' => '',
                        'last_name' => '',
                        'name_suffix_id' => 0,
                        'birth_place' => '',
                        'birthdate' => '',
                        'age' => '',
                        'gender_id' => 0,
                        'height' => '',
                        'weight' => '',
                        'blood_type_id' => 0,
                        'email' => '',
                        'mobile_no' => '',
                        'telephone_no' => '',
                        'citizenship_id' => 0,
                        'civil_status_id' => 0,
                        'religion_id' => 0,
                        'is_dual_citizent' => false,
                        'by_birth' => false,
                        'by_naturalization' => false,
                        'indicate_country' => '',
                        'ra_region' => '',
                        'ra_province' => '',
                        'ra_city' => '',
                        'ra_house_no' => '',
                        'ra_barangay' => '',
                        'ra_street' => '',
                        'ra_village' => '',
                        'pa_region' => '',
                        'pa_province' => '',
                        'pa_city' => '',
                        'pa_house_no' => '',
                        'pa_barangay' => '',
                        'pa_street' => '',
                        'pa_village' => '',
                        'father_name_prefix_id' => 0,
                        'father_first_name' => '',
                        'father_middle_name' => '',
                        'father_last_name' => '',
                        'father_name_suffix_id' => 0,
                        'mother_name_prefix_id' => '',
                        'mother_first_name' => '',
                        'mother_middle_name' => '',
                        'mother_last_name' => '',
                        'mother_name_suffix_id' => 0,
                        'spouse_name_prefix_id' => 0,
                        'spouse_first_name' => '',
                        'spouse_middle_name' => '',
                        'spouse_last_name' => '',
                        'spouse_name_suffix_id' => 0,
                        'spouse_occupation' => '',
                        'spouse_employer' => '',
                        'spouse_business_address' => '',
                        'company_id' => 1,
                        'branch_id' => 0,
                        'department_id' => 0,
                        'division_id' => 0,
                        'section_id' => 0,
                        'employment_type_id' => 0,
                        'position_id' => 0,
                        'plantilla_id' => 0,
                        'is_plantilla' => false,
                        'is_employee' => true,
                        'is_teaching' => false,
                        'date_hired' => '',
                        'tin_no' => '',
                        'gsis_no' => '',
                        'sss_no' => '',
                        'pagibig_no' => '',
                        'philhealth_no' => '',
                        'salary' => '',
                        'tax_amount' => '',
                        'gsis_amount' => '',
                        'sss_amount' => '',
                        'pagibig_amount' => '',
                        'philhealth_amount' => '',
                        'payroll_interval_id' => 0,
                        'end_date' => '',
                        'account_no' => ''
                    );

                $plantillas_selected = DB::table('plantillas')
                    ->select('salary_grade_id', 'salary_step_id')
                    ->where('id', 0)->get();

                $employee_info = (object)$dummy_employee_info;
                $employee_info =  collect([$employee_info]);
            } else {

                $employee_info = DB::table('employees')
                    ->select(
                        'id',
                        'photo',
                        'employee_no',
                        'access_no',
                        'name_prefix_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN first_name ELSE dbo.ufn_DecryptString(first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN middle_name ELSE dbo.ufn_DecryptString(middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN last_name ELSE dbo.ufn_DecryptString(last_name,'$app_key') END as last_name"),
                        'name_suffix_id',
                        'birth_place',
                        'birthdate',
                        'age',
                        'gender_id',
                        'height',
                        'weight',
                        'blood_type_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN email ELSE dbo.ufn_DecryptString(email,'$app_key') END as email"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mobile_no ELSE dbo.ufn_DecryptString(mobile_no,'$app_key') END as mobile_no"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN telephone_no ELSE dbo.ufn_DecryptString(telephone_no,'$app_key') END as telephone_no"),
                        'citizenship_id',
                        'civil_status_id',
                        'religion_id',
                        'is_dual_citizent',
                        'by_birth',
                        'by_naturalization',
                        'indicate_country',
                        'ra_postal_id',
                        'ra_region',
                        'ra_province',
                        'ra_city',
                        'ra_house_no',
                        'ra_barangay',
                        'ra_street',
                        'ra_village',
                        'pa_postal_id',
                        'pa_region',
                        'pa_province',
                        'pa_city',
                        'pa_house_no',
                        'pa_barangay',
                        'pa_street',
                        'pa_village',
                        'father_name_prefix_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_first_name ELSE dbo.ufn_DecryptString(father_first_name,'$app_key') END as father_first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_middle_name ELSE dbo.ufn_DecryptString(father_middle_name,'$app_key') END as father_middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_last_name ELSE dbo.ufn_DecryptString(father_last_name,'$app_key') END as father_last_name"),
                        'father_name_suffix_id',
                        'mother_name_prefix_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_first_name ELSE dbo.ufn_DecryptString(mother_first_name,'$app_key') END as mother_first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_middle_name ELSE dbo.ufn_DecryptString(mother_middle_name,'$app_key') END as mother_middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_last_name ELSE dbo.ufn_DecryptString(mother_last_name,'$app_key') END as mother_last_name"),
                        'mother_name_suffix_id',
                        'spouse_name_prefix_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_first_name ELSE dbo.ufn_DecryptString(spouse_first_name,'$app_key') END as spouse_first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_middle_name ELSE dbo.ufn_DecryptString(spouse_middle_name,'$app_key') END as spouse_middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_last_name ELSE dbo.ufn_DecryptString(spouse_last_name,'$app_key') END as spouse_last_name"),
                        'spouse_name_suffix_id',
                        // DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_occupation ELSE dbo.ufn_DecryptString(spouse_occupation,'$app_key') END as spouse_occupation"),
                        // DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_employer ELSE dbo.ufn_DecryptString(spouse_employer,'$app_key') END as spouse_employer"),
                        // DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_business_address ELSE dbo.ufn_DecryptString(spouse_business_address,'$app_key') END as spouse_business_address"),
                        'spouse_occupation',
                        'spouse_employer',
                        'spouse_business_address',
                        'spouse_mobile_no',
                        'company_id',
                        'branch_id',
                        'department_id',
                        'division_id',
                        'section_id',
                        'employment_type_id',
                        'position_id',
                        'plantilla_id',
                        'is_plantilla',
                        'is_employee',
                        'is_teaching',
                        'date_hired',
                        'tin_no',
                        'gsis_no',
                        'sss_no',
                        'pagibig_no',
                        'philhealth_no',
                        'salary',
                        'tax_amount',
                        'gsis_amount',
                        'sss_amount',
                        'pagibig_amount',
                        'philhealth_amount',
                        'payroll_interval_id',
                        'end_date',
                        'account_no'
                    )
                    ->where('id', $emp_id)
                    ->orderBy('employees.first_name', 'asc')
                    ->get();

                $plantillas_selected = DB::table('plantillas')
                    ->select('salary_grade_id', 'salary_step_id')
                    ->where('id', $employee_info[0]->plantilla_id)->get();
            }

            $data  = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->join('departments', 'departments.id', '=', 'plantillas.department_id')
                ->join('applicant_details as a', 'a.position_applied_id', '=', 'plantillas.id')
                ->join('applicant_headers as b', 'a.applicant_id', '=', 'b.id')
                ->join('application_status as f', 'a.application_status_id', '=', 'f.id')
                ->select('plantillas.id', 'plantillas.code', 'positions.name as position', 'salary_steps.name as step', 'salary_grades.name as grade', 'departments.name as department', 'plantillas.eligibility as eligibility', 'plantillas.experience as experience', 'plantillas.training as training', 'plantillas.education as education', 'plantillas.unit as unit', 'plantillas.publication_from as publication_from', 'plantillas.publication_to as publication_to', 'plantillas.status as status', 'plantillas.active', 'f.name as application_status')
                ->where('a.is_plantilla', true)
                ->where('b.id', $applicant[0]->id)
                ->orderBy('positions.name', 'asc')
                ->get();

            $non_plantillas = DB::table('non_plantillas as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->join('applicant_details as d', 'd.position_applied_id', '=', 'a.id')
                ->join('applicant_headers as e', 'd.applicant_id', '=', 'e.id')
                ->join('application_status as f', 'd.application_status_id', '=', 'f.id')
                ->select(
                    'a.id',
                    'a.position_id',
                    'b.name as position',
                    'a.salary',
                    'c.name as department',
                    'a.eligibility',
                    'a.experience',
                    'a.education',
                    'a.training',
                    'a.description',
                    'a.qualification',
                    'a.vacant',
                    'a.publication_from',
                    'a.publication_to',
                    'f.name as application_status',
                    DB::raw(
                        "case when a.status = 0 then 'Inactive' else 'Active' end as status"
                    )
                )
                ->where('d.is_plantilla', false)
                ->where('e.id', $applicant[0]->id)
                ->orderBy('b.name', 'asc')
                ->get();

            $prefixes = DB::table('name_prefixes')->where('active', true)->orderBy('id', 'asc')->get();
            $suffixes = DB::table('name_suffixes')->where('active', true)->orderBy('name', 'asc')->get();
            $genders = DB::table('genders')->where('active', true)->orderBy('name', 'asc')->get();
            $civil_status = DB::table('civil_status')->where('active', true)->orderBy('id', 'asc')->get();
            $citizenships = DB::table('citizenships')->where('active', true)->orderBy('id', 'asc')->get();
            $religions = DB::table('religions')->where('active', true)->orderBy('id', 'asc')->get();
            $blood_types = DB::table('blood_types')->where('active', true)->orderBy('name', 'asc')->get();
            $companies = DB::table('companies')->get();
            $branches = DB::table('branches')->orderBy('id', 'asc')->get();
            $departments = DB::table('departments')->where('active', true)->orderBy('name', 'asc')->get();
            $employment_types = DB::table('employment_types')->where('active', true)->orderBy('id', 'asc')->get();
            $positions = DB::table('positions')->where('active', true)->orderBy('name', 'asc')->get();

            // Get Plantilla
            $plantilla_emp = DB::table('plantillas')->where(['employee_id' => $emp_id, 'active' => true]);
            $plantillas = DB::table('plantillas')->where(['employee_id' => 0, 'active' => true])->union($plantilla_emp)->get();

            $salary_grades = DB::table('salary_grades')->where('active', true)->orderBy('id', 'asc')->get();
            $salary_steps = DB::table('salary_steps')->where('active', true)->orderBy('id', 'asc')->get();
            $blood_types = DB::table('blood_types')->where('active', true)->orderBy('id', 'asc')->get();
            $payroll_intervals = DB::table('payroll_intervals')->where('active', true)->orderBy('id', 'asc')->get();
            $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();
            $learnings = DB::table('learnings')->where('active', true)->orderBy('name', 'asc')->get();
            $divisions = DB::table('divisions')->where('active', true)->orderBy('name', 'asc')->get();
            $sections = DB::table('sections')->where('active', true)->orderBy('name', 'asc')->get();

            $applicant_id = $applicant[0]->id;

            $children = DB::table('employee_children')->where('employee_id', $emp_id)->get();
            $educations = DB::table('employee_educations')->where('employee_id', $emp_id)->orderBy('employee_educations.graduated_year', 'desc')->get();
            $service_records = DB::table('service_records')->where('employee_id', $emp_id)->get();
            $employments = DB::table('employee_employment_records')->where('employee_id', $emp_id)->get();
            $examinations = DB::table('employee_examinations')->where('employee_id', $emp_id)->get();
            $trainings = DB::table('employee_trainings')->where('employee_id', $emp_id)->get();
            $organizations = DB::table('employee_organizations')->where('employee_id', $emp_id)->get();
            $recognitions = DB::table('employee_recognations')->where('employee_id', $emp_id)->get();
            $skills = DB::table('employee_skills')->where('employee_id', $emp_id)->get();
            $memberships = DB::table('employee_memberships')->where('employee_id', $emp_id)->get();
            $references = DB::table('employee_references')->where('employee_id', $emp_id)->get();
            $dependents = DB::table('employee_dependents')->where('employee_id', $emp_id)->get();
            $documents = DB::table('employee_documents')->where('employee_id', $emp_id)->get();

            $loans = DB::table('loan_applications as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.name',
                    'a.loan_amount',
                    'a.payment',
                    'a.balance'
                )
                ->where([
                    'a.is_approve' => true,
                    'a.employee_id' => $emp_id
                ])
                ->get();

            $payroll_period_id = DB::table('payroll_incomes')->max('payroll_period_id');

            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'b.name',
                    'a.amount'
                )
                ->where([
                    'a.employee_id' => $emp_id,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->get();

            $salary_grade_steps = DB::table('salary_grades as a')
                ->crossJoin('salary_steps as b')
                ->select(
                    DB::raw("CONVERT(nvarchar(50),a.id) + ' - ' + CONVERT(nvarchar(50),b.id) as id"),
                    DB::raw("CONVERT(nvarchar(50),a.id) + ' - ' + CONVERT(nvarchar(50),b.id) as name")
                )
                ->get();

            $quesionaires = DB::table('employee_pds_answers as a')
                ->join('pds_questionaires as b', 'a.question_id', '=', 'b.id')
                ->select(
                    'b.id',
                    'b.code',
                    'b.questions',
                    'a.is_yes',
                    'a.is_no',
                    'a.yes_details',
                    'a.case_status',
                    'a.date_filed'
                )
                ->where('a.employee_id', $emp_id)
                ->orderBy('b.id', 'asc')
                ->get();

            if ($quesionaires->isEmpty()) {
                $quesionaires = DB::table('pds_questionaires')->orderBy('id', 'asc')->get();
            }

            $eete_ratings = DB::table('eete_ratings')->get();

            if ($eete_ratings->isEmpty()) {
                $eete_ratings = [
                    'id' => 0,
                    'education_rating' => null,
                    'experience_rating' => null,
                    'training_rating' => null,
                    'eligibility_rating' => null
                ];

                $eete_ratings = (object)$eete_ratings;
                $eete_ratings = collect([$eete_ratings]);
            }

            $applicant_eete_ratings = DB::table('applicant_eete_ratings')->where('applicant_id', $applicant_id)->get();

            if ($applicant_eete_ratings->isEmpty()) {
                $applicant_eete_ratings = [
                    'id' => 0,
                    'applicant_id' => $applicant_id,
                    'education_rating' => null,
                    'experience_rating' => null,
                    'training_rating' => null,
                    'eligibility_rating' => null,
                    'reviewed_status_id' => null
                ];

                $applicant_eete_ratings = (object)$applicant_eete_ratings;
                $applicant_eete_ratings = collect([$applicant_eete_ratings]);
            }

            return $this->successResponse([
                'applicant' => $applicant,
                'data' => $data,
                'non_plantillas' => $non_plantillas,
                'prefixes' => $prefixes,
                'suffixes' => $suffixes,
                'genders' => $genders,
                'civil_status' => $civil_status,
                'citizenships' => $citizenships,
                'religions' => $religions,
                'blood_types' => $blood_types,
                'companies' => $companies,
                'branches' => $branches,
                'departments' => $departments,
                'employment_types' => $employment_types,
                'positions' => $positions,
                'plantillas' => $plantillas,
                'salary_grades' => $salary_grades,
                'salary_steps' => $salary_steps,
                'payroll_intervals' => $payroll_intervals,
                'eligibilities' => $eligibilities,
                'learnings' => $learnings,
                'employee_info' => $employee_info,
                'plantillas_selected' => $plantillas_selected,
                'children' => $children,
                'educations' => $educations,
                'service_records' => $service_records,
                'employments' => $employments,
                'examinations' => $examinations,
                'trainings' => $trainings,
                'organizations' => $organizations,
                'recognitions' => $recognitions,
                'skills' => $skills,
                'memberships' => $memberships,
                'references' => $references,
                'loans' => $loans,
                'incomes' => $incomes,
                'divisions' => $divisions,
                'sections' => $sections,
                'dependents' => $dependents,
                'documents' => $documents,
                'salary_grade_steps' => $salary_grade_steps,
                'quesionaires' => $quesionaires,
                'eete_ratings' => $eete_ratings,
                'applicant_eete_ratings' => $applicant_eete_ratings
            ], 'Panel interview PDS data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load panel interview PDS data: ' . $e->getMessage());
        }
    }

    public function panel_interview_exam($id)
    {
        try {
            $applicant_examinations = DB::table('applicant_examination_headers')
                ->where([
                    'applicant_id' => $id,
                    'is_complete' => true
                ])
                ->get();

            if ($applicant_examinations->isNotEmpty()) {
                $applicant_examination_id = $applicant_examinations[0]->id;
            } else {
                $applicant_examination_id = 0;
            }

            $exam = DB::table('examination_schedule_header as a')
                ->join('examination_setup_header as b', 'a.exam_id', '=', 'b.id')
                ->join('applicant_examination_headers as c', 'c.exam_schedule_id', '=', 'a.id')
                ->join('exam_categories as d', 'b.category_id', '=', 'd.id')
                ->select(
                    'a.id',
                    'c.applicant_id',
                    'a.exam_date_from',
                    'a.exam_date_to',
                    'a.exam_time_from',
                    'a.exam_time_to',
                    'b.exam_set',
                    'b.exam_instruction',
                    'b.exam_duration',
                    'b.passing_criteria',
                    'd.id as category_id',
                    'd.name as category',
                    'd.description',
                    'c.id as applicant_examination_id',
                    'c.exam_rating'
                )
                ->where([
                    'a.posted' => 1,
                    'c.id' => $applicant_examination_id
                ])
                ->get();

            $exam_total_sub_categories = DB::table('applicant_examination_headers as a')
                ->join('applicant_examination_details as b', 'a.id', '=', 'b.applicant_examination_id')
                ->join('exam_questionaire_headers as c', 'b.question_id', '=', 'c.id')
                ->join('exam_sub_categories as d', 'c.sub_category_id', '=', 'd.id')
                ->join('exam_difficulty_levels as e', 'd.difficulty_level', '=', 'e.id')
                ->select(
                    'a.id',
                    'd.sub_category',
                    'e.difficulty_level',
                    DB::raw("count(b.question_id) as total_items"),
                    DB::raw("sum(b.correct) as total_correct")
                )
                ->where('a.id', $applicant_examination_id)
                ->groupBy(
                    'a.id',
                    'd.sub_category',
                    'e.difficulty_level'
                )
                ->get();

            return $this->successResponse([
                'exam' => $exam,
                'exam_total_sub_categories' => $exam_total_sub_categories
            ], 'Panel interview exam data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve panel interview exam data: ' . $e->getMessage());
        }
    }

    public function panel_interview_rating($applicant_id, $employee_id, $interview_id)
    {
        try {
            $applicants = DB::table('applicant_headers as a')
                ->select(
                    'a.id as applicant_id',
                    'a.applicant_no',
                    DB::raw("UPPER(CONCAT(a.first_name,' ',a.last_name)) as name")
                )
                ->where('a.id', $applicant_id)
                ->get();

            $ratings = DB::table('interview_panel_ratings')
                ->where([
                    'interview_id' => $interview_id,
                    'applicant_id' => $applicant_id,
                    'employee_id' => $employee_id
                ])
                ->get();

            if ($ratings->isEmpty()) {
                $ratings = [
                    'id' => 0,
                    'interview_id' => 0,
                    'employee_id' => 0,
                    'applicant_id' => 0,
                    'bei_rating' => 0,
                    'competency_rating' => 0,
                ];

                $ratings = (object)$ratings;
                $ratings = collect([$ratings]);
            }

            return $this->successResponse([
                'applicants' => $applicants,
                'ratings' => $ratings,
                'interview_id' => $interview_id,
                'employee_id' => $employee_id
            ], 'Panel interview rating data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve panel interview rating data: ' . $e->getMessage());
        }
    }

    public function interview_rating(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'interview_id' => 'required|integer',
                'employee_id' => 'required|integer',
                'applicant_id' => 'required|integer',
                'bei_rating' => 'required|integer|min:0|max:100',
                'competency_rating' => 'required|integer|min:0|max:100',
                'supporting_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = [
                'interview_id' => (int) $request->interview_id,
                'employee_id' => (int) $request->employee_id,
                'applicant_id' => (int) $request->applicant_id,
                'bei_rating' => (int) $request->bei_rating,
                'competency_rating' => (int) $request->competency_rating,
            ];

            $ratingId = (int) $id;

            if ($ratingId === 0) {
                $ratingId = (int) DB::table('interview_panel_ratings')->insertGetId($data);
            } else {
                DB::table('interview_panel_ratings')->where('id', $ratingId)->update($data);
            }

            if ($request->hasFile('supporting_document') && Schema::hasTable('interview_panel_attachments')) {
                $file = $request->file('supporting_document');
                $originalName = $file->getClientOriginalName();
                $safeStoredName = uniqid('ipa_', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $storedPath = $file->storeAs('interview_panel_attachments', $safeStoredName, 'local');

                $attachmentData = [
                    'interview_id' => (int) $request->interview_id,
                    'employee_id' => (int) $request->employee_id,
                    'applicant_id' => (int) $request->applicant_id,
                ];
                if (Schema::hasColumn('interview_panel_attachments', 'rated_by')) {
                    $attachmentData['rated_by'] = (int) Auth::id();
                }
                if (Schema::hasColumn('interview_panel_attachments', 'original_name')) {
                    $attachmentData['original_name'] = $originalName;
                }
                if (Schema::hasColumn('interview_panel_attachments', 'stored_name')) {
                    $attachmentData['stored_name'] = $safeStoredName;
                }
                if (Schema::hasColumn('interview_panel_attachments', 'path')) {
                    $attachmentData['path'] = $storedPath;
                }
                if (Schema::hasColumn('interview_panel_attachments', 'mime_type')) {
                    $attachmentData['mime_type'] = $file->getClientMimeType();
                }
                DB::table('interview_panel_attachments')->insert($attachmentData);
            }

            DB::table('interview_panels')
                ->where([
                    'interview_id' => $request->interview_id,
                    'employee_id' => $request->employee_id
                ])
                ->update([
                    'is_complete_rating' => true
                ]);

            DB::table('interview_applicants')->where([
                'interview_id' => $request->interview_id,
                'applicant_id' => $request->applicant_id,
            ])
                ->update([
                    'is_complete_interview' => true
                ]);

            return $this->successResponse(['rating_id' => $ratingId], 'Successfully Submitted Rating.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to submit rating: ' . $e->getMessage());
        }
    }

    /**
     * Download a supporting document uploaded with a panel member's interview rating.
     */
    public function downloadPanelRatingDocument($rating_id)
    {
        try {
            if (!Schema::hasTable('interview_panel_attachments')) {
                return $this->notFoundResponse('Panel attachment table is not available.');
            }
            $attachment = DB::table('interview_panel_attachments')
                ->where('id', (int) $rating_id)
                ->select('path', 'original_name', 'stored_name')
                ->first();
            if (!$attachment || empty($attachment->path)) {
                return $this->notFoundResponse('Document not found.');
            }
            $rawPath = trim(str_replace('\\', '/', (string) $attachment->path), '/');
            $storedName = trim((string) ($attachment->stored_name ?? ''));
            $downloadName = $attachment->original_name ?: ($storedName ?: basename($rawPath));

            // Support old and new path formats:
            // - full relative file path
            // - directory path + stored_name
            // - local/public disk storage
            $candidatePaths = [];
            if ($rawPath !== '') {
                $candidatePaths[] = $rawPath;
                if ($storedName !== '') {
                    $endsWithStored = substr($rawPath, -strlen($storedName)) === $storedName;
                    if (!$endsWithStored) {
                        $candidatePaths[] = rtrim($rawPath, '/') . '/' . $storedName;
                    }
                }
                if (strpos($rawPath, 'storage/') === 0) {
                    $candidatePaths[] = ltrim(substr($rawPath, strlen('storage/')), '/');
                }
            }
            if ($storedName !== '') {
                $candidatePaths[] = 'interview_panel_attachments/' . $storedName;
            }
            $candidatePaths = array_values(array_unique(array_filter($candidatePaths)));

            foreach (['local', 'public'] as $diskName) {
                foreach ($candidatePaths as $candidatePath) {
                    if (Storage::disk($diskName)->exists($candidatePath)) {
                        return Storage::disk($diskName)->download($candidatePath, $downloadName);
                    }
                }
            }

            // Final fallback: allow absolute paths stored in DB.
            if (preg_match('/^[a-zA-Z]:[\\\\\\/]/', (string) $attachment->path) && file_exists((string) $attachment->path)) {
                return response()->download((string) $attachment->path, $downloadName);
            }

            return $this->notFoundResponse('File is missing from storage.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download document: ' . $e->getMessage());
        }
    }

    /**
     * Applicant user rows eligible for interview assignment lists.
     */
    private function applicantUserEligibilityConstraint(string $userAlias, string $headerAlias): \Closure
    {
        return function ($query) use ($userAlias, $headerAlias) {
            $query->where($userAlias . '.is_applicant', 1)
                ->orWhereNull($headerAlias . '.user_id');
        };
    }

    /**
     * Unposted interview schedules whose start/end window has fully passed.
     */
    private function isInterviewScheduleExpired(object $row, ?Carbon $now = null): bool
    {
        $now = $now ?? Carbon::now();

        if ((int) ($row->is_done ?? 0) === 1) {
            return false;
        }

        $posted = $row->posted ?? null;
        if ($posted === true || $posted === 1 || $posted === '1') {
            return false;
        }

        $endDate = $row->end_date ?? null;
        $endTime = $row->end_time ?? null;
        if (!$endDate) {
            return false;
        }

        if ($endTime) {
            $end = Carbon::parse($endDate . ' ' . $endTime);
        } else {
            $end = Carbon::parse($endDate)->endOfDay();
        }

        return $end->lt($now);
    }

    /**
     * Applicants for an interview schedule, grouped by "Position (DEPT_CODE)" label.
     *
     * @return array<int, array{position_label: string, applicants: array<int, string>}>
     */
    protected function fetchInterviewApplicantsGroupedByPosition(int $interviewId): array
    {
        $rows = DB::table('applicant_interview_headers as a')
            ->join('interview_applicants as b', 'a.id', '=', 'b.interview_id')
            ->join('applicant_headers as c', 'b.applicant_id', '=', 'c.id')
            ->select(
                DB::raw("UPPER(CONCAT(c.first_name,' ',c.last_name)) as applicant_name"),
                DB::raw("(
                    SELECT TOP 1 COALESCE(pos.name, pos_from_plantilla.name)
                    FROM applicant_details ad
                    LEFT JOIN positions pos ON ad.position_applied_id = pos.id
                    LEFT JOIN plantillas p ON ad.position_applied_id = p.id
                    LEFT JOIN positions pos_from_plantilla ON p.position_id = pos_from_plantilla.id
                    WHERE ad.applicant_id = c.id
                    ORDER BY ad.id DESC
                ) as position_name"),
                DB::raw("(
                    SELECT TOP 1 COALESCE(dept_plantilla.code, '')
                    FROM applicant_details ad
                    LEFT JOIN plantillas p ON ad.position_applied_id = p.id
                    LEFT JOIN departments dept_plantilla ON p.department_id = dept_plantilla.id
                    WHERE ad.applicant_id = c.id
                    ORDER BY ad.id DESC
                ) as department_code")
            )
            ->where('a.id', $interviewId)
            ->where(function ($query) {
                $query->whereNull('b.is_cancelled_interview')
                    ->orWhere('b.is_cancelled_interview', 0);
            })
            ->orderBy('applicant_name', 'asc')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $positionName = trim((string) ($row->position_name ?? ''));
            if ($positionName === '') {
                $positionName = 'Unspecified Position';
            }
            $deptCode = trim((string) ($row->department_code ?? ''));
            $label = $deptCode !== '' ? $positionName . ' (' . strtoupper($deptCode) . ')' : $positionName;

            if (!isset($grouped[$label])) {
                $grouped[$label] = [];
            }
            $grouped[$label][] = $row->applicant_name;
        }

        $result = [];
        foreach ($grouped as $positionLabel => $applicants) {
            $result[] = [
                'position_label' => $positionLabel,
                'applicants' => $applicants,
            ];
        }

        return $result;
    }

    /**
     * Mail payload for a single panelist / interviewer (type = 2).
     */
    protected function buildPanelistInterviewMailPayload($interviewHeader, object $panel, array $applicantsByPosition)
    {
        $lastName = trim((string) ($panel->last_name ?? ''));
        if ($lastName === '') {
            $nameParts = preg_split('/\s+/', trim((string) ($panel->name ?? '')));
            $lastName = !empty($nameParts) ? end($nameParts) : '';
        }

        return collect([(object) [
            'email' => $panel->email ?? null,
            'name' => $panel->name ?? '',
            'first_name' => $panel->first_name ?? '',
            'last_name' => $lastName,
            'type' => 2,
            'panel_group_level' => (int) ($interviewHeader->panel_group_level ?? 0),
            'panel_group' => $interviewHeader->panel_group ?? '',
            'interview_location' => $interviewHeader->interview_location ?? '',
            'description' => $interviewHeader->description ?? '',
            'start_date' => $interviewHeader->start_date ?? null,
            'end_date' => $interviewHeader->end_date ?? null,
            'start_time' => $interviewHeader->start_time ?? null,
            'end_time' => $interviewHeader->end_time ?? null,
            'applicants_by_position' => $applicantsByPosition,
        ]]);
    }
}
