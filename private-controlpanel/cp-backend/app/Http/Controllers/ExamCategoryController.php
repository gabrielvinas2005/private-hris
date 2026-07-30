<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExamCategoryController extends Controller
{
    use ApiResponse;
    
    public function index()
    {
        try {
            $exam_categories = DB::table('exam_categories')->orderBy('category_code')->get();

            return $this->successResponse($exam_categories, 'Exam categories retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve exam categories: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            $exam_categories = DB::table('exam_categories')->where('id', $id)->get();

            if ($exam_categories->isEmpty()) {
                $exam_categories = array(
                    'id' => 0,
                    'category_code' => null,
                    'name' => null,
                    'description' => null
                );

                $exam_categories = (object)$exam_categories;
                $exam_categories = collect([$exam_categories]);
            }

            $exam_diff_levels = DB::table('exam_difficulty_levels')->where('active', 1)->orderBy('id', 'asc')->get();
            $sub_categories = DB::table('exam_sub_categories')->where('category_id', $id)->orderBy('sub_category_code', 'asc')->get();

            return $this->successResponse([
                'exam_categories' => $exam_categories,
                'exam_diff_levels' => $exam_diff_levels,
                'sub_categories' => $sub_categories
            ], 'Exam categories form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load exam categories form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'unique:exam_categories,name' . ($id ? ",$id" : ''),
            ]);

            $data = $request->all();

            $category_data = DB::table('exam_categories')->where('id', $id)->get();

            if ($category_data->isEmpty()) {
                $category_code = 'cat_' . (DB::table('exam_categories')->max('id') + 1);
            } else {
                $category_code = $category_data[0]->category_code;
            }

            $category = array(
                'category_code' => $category_code,
                'name' => $data['name'],
                'description' => $data['description'],
            );

            DB::transaction(function () use (&$id, $category, $data) {
                if ($id == 0) {
                    $id = DB::table('exam_categories')->insertGetId($category);
                } else {
                    DB::table('exam_categories')->where('id', $id)->update($category);
                }

                // Sync subcategories when payload includes sub_name (Control Panel always sends it).
                // - Upsert each row from the form
                // - Remove DB rows for this category that are not in the saved list (handles UI row delete)
                if (!isset($data['sub_name']) || !is_array($data['sub_name'])) {
                    return;
                }

                $subNames = $data['sub_name'];
                $subIds = isset($data['sub_id']) && is_array($data['sub_id']) ? $data['sub_id'] : [];
                $difficulties = isset($data['difficulty_level']) && is_array($data['difficulty_level']) ? $data['difficulty_level'] : [];
                $existingQuestions = isset($data['existing_questions']) && is_array($data['existing_questions']) ? $data['existing_questions'] : [];
                $isEssays = isset($data['is_essay']) && is_array($data['is_essay']) ? $data['is_essay'] : [];

                $keptIds = [];

                for ($i = 0; $i < count($subNames); $i++) {
                    $name = $subNames[$i];
                    if ($name === null || $name === '') {
                        continue;
                    }

                    $subIdInput = isset($subIds[$i]) ? (int) $subIds[$i] : 0;
                    if ($subIdInput === 0) {
                        $maxSubId = DB::table('exam_sub_categories')->max('id');
                        $sub_id = (int) $maxSubId + 1;
                    } else {
                        $sub_id = $subIdInput;
                    }

                    $sub_cat_code = 'sub_0' . $id . '.' . $i;

                    $sub_cat_data = [
                        'category_id' => $id,
                        'sub_category_code' => $sub_cat_code,
                        'sub_category' => $name,
                        'difficulty_level' => isset($difficulties[$i]) ? $difficulties[$i] : 1,
                        'existing_questions' => isset($existingQuestions[$i]) ? $existingQuestions[$i] : 0,
                        'is_essay' => isset($isEssays[$i]) && ($isEssays[$i] == 1 || $isEssays[$i] === true || $isEssays[$i] === 'true'),
                    ];

                    DB::unprepared('SET IDENTITY_INSERT exam_sub_categories ON');
                    DB::table('exam_sub_categories')->updateOrInsert(['id' => $sub_id], $sub_cat_data);
                    DB::unprepared('SET IDENTITY_INSERT exam_sub_categories OFF');

                    $keptIds[] = $sub_id;
                }

                if ($id > 0) {
                    $query = DB::table('exam_sub_categories')->where('category_id', $id);
                    if (count($keptIds) > 0) {
                        $query->whereNotIn('id', $keptIds);
                    }
                    $query->delete();
                }
            });

            return $this->successResponse(['id' => $id], 'Exam category saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save exam category: ' . $e->getMessage());
        }
    }

    public function deleteSubCategory($id)
    {
        try {
            DB::table('exam_sub_categories')->where('id', $id)->delete();

            return $this->successResponse(['id' => $id], 'Sub-category deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete sub-category: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $category = DB::table('exam_categories')->where('id', $id)->first();

            if (!$category) {
                return $this->notFoundResponse('Exam category not found');
            }

            DB::transaction(function () use ($id) {
                $this->deleteExaminationSetupReferences($id);

                $subCategoryIds = DB::table('exam_sub_categories')
                    ->where('category_id', $id)
                    ->pluck('id');

                foreach ($subCategoryIds as $subId) {
                    $this->deleteSubCategoryReferences($subId);
                }

                DB::table('exam_sub_categories')->where('category_id', $id)->delete();
                DB::table('exam_categories')->where('id', $id)->delete();
            });

            return $this->successResponse(['deleted_id' => (int) $id], 'Exam category deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete exam category: ' . $e->getMessage());
        }
    }

    private function deleteExaminationSetupReferences($categoryId)
    {
        $setupIds = DB::table('examination_setup_header')
            ->where('category_id', $categoryId)
            ->pluck('id');

        foreach ($setupIds as $setupId) {
            $scheduleIds = DB::table('examination_schedule_header')
                ->where('exam_id', $setupId)
                ->pluck('id');

            if ($scheduleIds->isNotEmpty()) {
                $applicantExamIds = DB::table('applicant_examination_headers')
                    ->whereIn('exam_schedule_id', $scheduleIds)
                    ->pluck('id');

                if ($applicantExamIds->isNotEmpty()) {
                    DB::table('applicant_examination_details')
                        ->whereIn('applicant_examination_id', $applicantExamIds)
                        ->delete();
                    DB::table('applicant_examination_headers')
                        ->whereIn('id', $applicantExamIds)
                        ->delete();
                }

                DB::table('examination_schedule_header')
                    ->whereIn('id', $scheduleIds)
                    ->delete();
            }

            DB::table('examination_positions')->where('exam_id', $setupId)->delete();
        }

        DB::table('examination_setup_header')->where('category_id', $categoryId)->delete();
    }

    private function deleteSubCategoryReferences($subCategoryId)
    {
        $questionIds = DB::table('exam_questionaire_headers')
            ->where('sub_category_id', $subCategoryId)
            ->pluck('id');

        if ($questionIds->isNotEmpty()) {
            DB::table('exam_questionaire_details')
                ->whereIn('question_id', $questionIds)
                ->delete();
            DB::table('exam_questionaire_headers')
                ->whereIn('id', $questionIds)
                ->delete();
        }

        DB::table('examination_positions')->where('exam_id', $subCategoryId)->delete();
    }

    public function subCategoryPositions($id)
    {
        try {
            $sub_categories = DB::table('exam_categories as a')
                ->join('exam_sub_categories as b', 'a.id', '=', 'b.category_id')
                ->join('exam_difficulty_levels as c', 'b.difficulty_level', '=', 'c.id')
                ->select(
                    'a.id as category_id',
                    'b.id',
                    'b.sub_category',
                    'c.difficulty_level',
                    'b.existing_questions',
                    'a.name as category',
                    'a.description as category_description'
                )
                ->where('b.id', $id)
                ->get();

            $positions =  DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->join('departments', 'departments.id', '=', 'plantillas.department_id')
                ->select(
                    'plantillas.id',
                    'plantillas.code',
                    'positions.name as position',
                    'salary_steps.name as step',
                    'salary_grades.name as grade',
                    'departments.name as department',
                    'plantillas.eligibility as eligibility',
                    'plantillas.experience as experience',
                    'plantillas.training as training',
                    'plantillas.education as education',
                    'plantillas.unit as unit',
                    'plantillas.publication_from as publication_from',
                    'plantillas.publication_to as publication_to',
                    'plantillas.status as status',
                    'plantillas.active'
                )
                ->where('plantillas.employee_id', '=', 0)
                ->whereRaw(
                    "isnull(plantillas.active,0) = 1 and
                    isnull(plantillas.approved,0) = 1 and
                    isnull(plantillas.cancelled,0) = 0"
                )
                ->whereNotIn('plantillas.id', function ($query) use ($id) {
                    $query->select('position_id')->from('examination_positions')->where('exam_id', $id)->get();
                })
                ->orderBy('positions.name', 'asc')
                ->get();

            $exam_positions =  DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->join('departments', 'departments.id', '=', 'plantillas.department_id')
                ->join('examination_positions as e', 'plantillas.id', '=', 'e.position_id')
                ->select(
                    'plantillas.id',
                    'plantillas.code',
                    'positions.name as position',
                    'salary_steps.name as step',
                    'salary_grades.name as grade',
                    'departments.name as department',
                    'plantillas.eligibility as eligibility',
                    'plantillas.experience as experience',
                    'plantillas.training as training',
                    'plantillas.education as education',
                    'plantillas.unit as unit',
                    'plantillas.publication_from as publication_from',
                    'plantillas.publication_to as publication_to',
                    'plantillas.status as status',
                    'plantillas.active',
                    'e.id as exam_id'
                )
                ->where('plantillas.employee_id', '=', 0)
                ->whereRaw(
                    "isnull(plantillas.active,0) = 1 and
                    isnull(plantillas.approved,0) = 1 and
                    isnull(plantillas.cancelled,0) = 0"
                )
                ->where('e.exam_id', $id)
                ->orderBy('positions.name', 'asc')
                ->get();

            return $this->successResponse([
                'sub_categories' => $sub_categories,
                'positions' => $positions,
                'exam_positions' => $exam_positions
            ], 'Exam sub-categories positions data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve exam sub-categories positions data: ' . $e->getMessage());
        }
    }

    public function addPosition(Request $request, $id)
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
                        'position_id' => $positions_data['id'][$i]
                    ];

                    DB::table('examination_positions')->insert($data);
                }
            }

            return $this->successResponse(['id' => $id], 'Positions added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add positions: ' . $e->getMessage());
        }
    }

    public function subCategoryQuestions($id)
    {
        try {
            $sub_categories = DB::table('exam_categories as a')
                ->join('exam_sub_categories as b', 'a.id', '=', 'b.category_id')
                ->join('exam_difficulty_levels as c', 'b.difficulty_level', '=', 'c.id')
                ->select(
                    'a.id as category_id',
                    'b.id',
                    'b.sub_category',
                    'c.difficulty_level',
                    'b.existing_questions',
                    'a.name as category',
                    'a.description as category_description'
                )
                ->where('b.id', $id)
                ->get();

            $questions = DB::table('exam_questionaire_headers as a')
                ->select(
                    'a.*',
                    DB::raw("(select isnull(choice_details,'') as choice_details from exam_questionaire_details where is_correct_answer = 1 and question_id = a.id) as correct_answer")
                )
                ->where('a.sub_category_id', $id)
                ->orderBy('a.id', 'asc')
                ->get();

            return $this->successResponse([
                'sub_categories' => $sub_categories,
                'questions' => $questions
            ], 'Exam sub-categories questions data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve exam sub-categories questions data: ' . $e->getMessage());
        }
    }

    public function addQuestions(Request $request, $id, $question_id)
    {
        try {
            $data_question = $request->all();

            // Enforce max questions per sub-category (existing_questions)
            // Only enforce on CREATE (question_id == 0). Allow updates even if already at limit.
            if ((int)$question_id === 0) {
                $sub = DB::table('exam_sub_categories')
                    ->select('existing_questions', 'is_essay')
                    ->where('id', $id)
                    ->first();

                if ($sub && $sub->existing_questions !== null) {
                    $limit = (int)$sub->existing_questions;
                    if ($limit > 0) {
                        $currentCount = (int)DB::table('exam_questionaire_headers')
                            ->where('sub_category_id', $id)
                            ->count();

                        if ($currentCount >= $limit) {
                            return $this->errorResponse("You can only create up to {$limit} questions for this subcategory.", 400);
                        }
                    }
                }
            }

            // Determine if this subcategory is an essay exam.
            // Essay questions do not require choices.
            $subType = DB::table('exam_sub_categories')
                ->select('is_essay')
                ->where('id', $id)
                ->first();
            $isEssay = $subType && (bool)$subType->is_essay;

            // Question Image
            if ($request->hasFile('question_image')) {

                $allowedfileExtension = ['jpg', 'png'];
                $files = $request->file('question_image');

                $file_name = $files->getClientOriginalName();
                $file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'question_header_attachments\\' . 'Q' . $question_id . '_' . $file_name;
                $extension = $files->getClientOriginalExtension();
                $check = in_array($extension, $allowedfileExtension);

                if ($check) {
                    // Save record of attachments to database.
                    $question_data_save = [
                        'sub_category_id' => $id,
                        'question_code' => $data_question['question_code'],
                        'question' => $data_question['question'],
                        'question_image' => $file_name,
                        'question_image_path' => $file_path
                    ];

                    if ($question_id == 0) {
                        $question_id = DB::table('exam_questionaire_headers')->insertGetId($question_data_save);
                    } else {
                        DB::table('exam_questionaire_headers')->where('id', $question_id)->update($question_data_save);
                    }

                    // Save attachment to path.
                    $request->question_image->storeAs('question_header_attachments', 'Q' . $question_id . '_' . $file_name);
                } else {
                    return $this->errorResponse('Invalid Question image file.', 400);
                }
            } else {
                $question_data_save = [
                    'sub_category_id' => $id,
                    'question_code' => $data_question['question_code'],
                    'question' => $data_question['question']
                ];

                if ($question_id == 0) {
                    $question_id = DB::table('exam_questionaire_headers')->insertGetId($question_data_save);
                } else {
                    DB::table('exam_questionaire_headers')->where('id', $question_id)->update($question_data_save);
                }
            }

            // If essay, skip choices processing. Also remove existing choices on update to avoid stale data.
            if ($isEssay) {
                DB::table('exam_questionaire_details')->where('question_id', $question_id)->delete();
                return $this->successResponse(['question_id' => $question_id], 'Question added successfully');
            }

            // insert choices
            $choices_data = [];

            for ($i = 0; $i < count($data_question['choice_details']); $i++) {
                if ($data_question['choice_details'][$i] != null) {

                    $raw_choice_id = $data_question['choice_id'][$i] ?? 0;
                    $numeric_choice_id = is_numeric($raw_choice_id) ? intval($raw_choice_id) : 0;

                    if ($numeric_choice_id === 0) {
                        $current_max = DB::table('exam_questionaire_details')->max('id');
                        $choice_id = ($current_max ? $current_max : 0) + 1;
                    } else {
                        $choice_id = $numeric_choice_id;
                    }

                    if (isset($data_question['is_correct_answer'])) {
                        $correct_answers = array_map('strval', $data_question['is_correct_answer']);
                        $comparison_key = (string)$raw_choice_id;
                        $is_correct_answer = in_array($comparison_key, $correct_answers, true) ||
                            ($numeric_choice_id !== 0 && in_array((string)$numeric_choice_id, $correct_answers, true));
                    } else {
                        $is_correct_answer = false;
                    };

                    if ($request->hasFile('choice_image')) {
                        $allowedfileExtension = ['jpg', 'png'];
                        $files = $request->file('choice_image');

                        if (isset($files[$i])) {
                            $file_name = $files[$i]->getClientOriginalName();
                            $file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'question_details_attachments\\' . 'Q' . $question_id . '_' . $file_name;
                            $extension = $files[$i]->getClientOriginalExtension();
                            $check = in_array($extension, $allowedfileExtension);

                            if ($check) {
                                // Save record of attachments to database.
                                $choices_data = [
                                    'question_id' => $question_id,
                                    'choice_details' => $data_question['choice_details'][$i],
                                    'choice_image' => $file_name,
                                    'choice_image_path' => $file_path,
                                    'is_correct_answer' => $is_correct_answer
                                ];

                                DB::unprepared('SET IDENTITY_INSERT exam_questionaire_details ON');
                                DB::table('exam_questionaire_details')->updateOrInsert(['id' => $choice_id], $choices_data);
                                DB::unprepared('SET IDENTITY_INSERT exam_questionaire_details OFF');

                                // Save attachment to path.
                                $request->choice_image[$i]->storeAs('question_details_attachments', 'Q' . $question_id . '_' . $file_name);
                            } else {
                                return $this->errorResponse('Invalid Question image file.', 400);
                            }
                        } else {
                            $choices_data = [
                                'question_id' => $question_id,
                                'choice_details' => $data_question['choice_details'][$i],
                                'is_correct_answer' => $is_correct_answer
                            ];

                            DB::unprepared('SET IDENTITY_INSERT exam_questionaire_details ON');
                            DB::table('exam_questionaire_details')->updateOrInsert(['id' => $choice_id], $choices_data);
                            DB::unprepared('SET IDENTITY_INSERT exam_questionaire_details OFF');
                        }
                    } else {
                        $choices_data = [
                            'question_id' => $question_id,
                            'choice_details' => $data_question['choice_details'][$i],
                            'is_correct_answer' => $is_correct_answer
                        ];

                        DB::unprepared('SET IDENTITY_INSERT exam_questionaire_details ON');
                        DB::table('exam_questionaire_details')->updateOrInsert(['id' => $choice_id], $choices_data);
                        DB::unprepared('SET IDENTITY_INSERT exam_questionaire_details OFF');
                    }
                }
            }

            return $this->successResponse(['question_id' => $question_id], 'Question added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add question: ' . $e->getMessage());
        }
    }

    public function deleteQuestion($id)
    {
        try {
            DB::table('exam_questionaire_details')->where('question_id', $id)->delete();
            DB::table('exam_questionaire_headers')->where('id', $id)->delete();

            return $this->successResponse(['id' => $id], 'Question deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete question: ' . $e->getMessage());
        }
    }

    public function deleteChoice($id)
    {
        try {
            DB::table('exam_questionaire_details')->where('id', $id)->delete();

            return $this->successResponse(['id' => $id], 'Choice deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete choice: ' . $e->getMessage());
        }
    }

    public function getDeleteChoice($id)
    {
        try {
            $data = DB::table('exam_questionaire_headers')->where('id', $id)->get();
            $choices = DB::table('exam_questionaire_details')->where('question_id', $id)->get();

            return $this->successResponse([
                'data' => $data,
                'choices' => $choices
            ], 'Exam sub-categories questions edit data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve exam sub-categories questions edit data: ' . $e->getMessage());
        }
    }
}
