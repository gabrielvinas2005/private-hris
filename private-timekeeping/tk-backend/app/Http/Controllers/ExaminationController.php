<?php

namespace App\Http\Controllers;

use Auth;
use App\User;
use App\Traits\ApiResponse;
use Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\EmailApplicantForExamination;

class ExaminationController extends Controller
{
    use ApiResponse;
    
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
                    'category_id' => 0
                ];

                $exams = (object)$exams;
                $exams = collect([$exams]);
            }

            $exam_categories = DB::table('exam_categories')->orderBy('category_code', 'asc')->get();

            return $this->successResponse([
                'exams' => $exams,
                'exam_categories' => $exam_categories
            ], 'Examination setup form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load examination setup form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            if ($id == 0) {
                $id = 0 + DB::table('examination_setup_header')->max('id');
                $id += 1;
            }

            $data = [
                'exam_set' => $request->exam_set,
                'exam_instruction' => $request->exam_instruction,
                'exam_duration' => $request->exam_duration,
                'passing_criteria' => $request->passing_criteria,
                'weighted_allocation' => $request->weighted_allocation,
                'with_video_recording' => false,
                'category_id' => $request->category_id
            ];

            DB::unprepared('SET IDENTITY_INSERT examination_setup_header ON');
            DB::table('examination_setup_header')->updateOrInsert(['id' => $id], $data);
            DB::unprepared('SET IDENTITY_INSERT examination_setup_header OFF');

            return $this->successResponse(['id' => $id], 'Examination setup saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save examination setup: ' . $e->getMessage());
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
                ->join('exam_categories as c', 'b.category_id', '=', 'c.id')
                ->select(
                    'a.*',
                    'b.exam_set',
                    'b.exam_duration',
                    'b.passing_criteria',
                    'c.name as exam_category'
                )
                ->get();

            return $this->successResponse($exam_schedules, 'Examination schedules retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve examination schedules: ' . $e->getMessage());
        }
    }

    public function schedules_add($id)
    {
        try {
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

            $exam_details = DB::table('examination_setup_header as a')
                ->join('exam_categories as b', 'a.category_id', '=', 'b.id')
                ->join('exam_sub_categories as c', 'b.id', '=', 'c.category_id')
                ->leftJoin('examination_positions as d', 'c.id', '=', 'd.exam_id')
                ->join('examination_schedule_header as e', 'a.id', '=', 'e.exam_id')
                ->leftJoin('plantillas as f', 'd.position_id', '=', 'f.id')
                ->leftJoin('positions as g', 'f.position_id', '=', 'g.id')
                ->join('exam_difficulty_levels as h', 'c.difficulty_level', '=', 'h.id')
                ->select(
                    'a.id',
                    'e.id as exam_schedule_id',
                    'b.name as category',
                    'b.description',
                    'c.sub_category',
                    'f.code',
                    'g.name as position',
                    'h.difficulty_level',
                    'c.existing_questions'
                )
                ->where('e.id', $id)
                ->get();

            $applicants = DB::table('applicant_headers as a')
                ->join('applicant_details as b', 'a.id', '=', 'b.applicant_id')
                ->join('applicant_shortlisted as c', 'a.id', '=', 'c.applicant_id')
                ->whereIn('b.position_applied_id', function ($query) use ($id) {
                    $query->select('e.position_id')->from('examination_schedule_header as a')
                        ->join('examination_setup_header as b', 'a.exam_id', '=', 'b.id')
                        ->join('exam_categories as c', 'b.category_id', '=', 'c.id')
                        ->join('exam_sub_categories as d', 'd.category_id', '=', 'c.id')
                        ->join('examination_positions as e', 'e.exam_id', '=', 'd.id')
                        ->where('a.id', $id);
                })
                ->select(
                    'a.id',
                    'a.applicant_no',
                    DB::raw("UPPER(CONCAT(a.first_name,' ',a.last_name)) as name"),
                )
                ->whereNotIn('a.id', function ($query) use ($id) {
                    $query->select('applicant_id')->from('applicant_examination_headers')->where('exam_schedule_id', $id);
                })
                ->distinct()
                ->get();

            $applicant_positions = DB::table('applicant_details as a')
                ->join('plantillas as b', 'a.position_applied_id', '=', 'b.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->select(
                    'a.applicant_id',
                    'b.code',
                    'c.name as position'
                )
                ->get();

            $examinees = DB::table('applicant_examination_headers as a')
                ->join('examination_schedule_header as b', 'b.id', '=', 'a.exam_schedule_id')
                ->join('applicant_headers as c', 'a.applicant_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'c.id as applicant_id',
                    'c.applicant_no',
                    DB::raw("UPPER(CONCAT(c.first_name,' ',c.last_name)) as name")
                )
                ->where('a.exam_schedule_id', $id)
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
                        ->join('applicant_examination_headers as b', 'a.id', '=', 'b.exam_schedule_id')
                        ->join('applicant_headers as c', 'b.applicant_id', '=', 'c.id')
                        ->select(
                            'c.applicant_no',
                            'c.email',
                            DB::raw("UPPER(CONCAT(c.first_name,' ',c.last_name)) as name"),
                            'a.exam_date_from',
                            'a.exam_date_to',
                            'a.exam_time_from',
                            'a.exam_time_to'
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
}
