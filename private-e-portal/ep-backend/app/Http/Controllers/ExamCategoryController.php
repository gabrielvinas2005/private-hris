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

            if ($id == 0) {
                $id = DB::table('exam_categories')->insertGetId($category);
            } else {
                DB::table('exam_categories')->where('id', $id)->update($category);

                $sub_cat_data = [];

                // Save Sub Categories
                for ($i = 0; $i < count($data['sub_name']); $i++) {
                    if ($data['sub_name'][$i] != NULL) {

                        if ($data['sub_id'][$i] == 0) {
                            $sub_id = (DB::table('exam_sub_categories')->max('id') +  1);
                        } else {
                            $sub_id = $data['sub_id'][$i];
                        }

                        $sub_cat_code = 'sub_0' . $id . '.' . $i;

                        $sub_cat_data = [
                            'category_id' => $id,
                            'sub_category_code' => $sub_cat_code,
                            'sub_category' => $data['sub_name'][$i],
                            'difficulty_level' => $data['difficulty_level'][$i],
                            'existing_questions' => $data['existing_questions'][$i]
                        ];

                        DB::unprepared('SET IDENTITY_INSERT exam_sub_categories ON');
                        DB::table('exam_sub_categories')->updateOrInsert(['id' => $sub_id], $sub_cat_data);
                        DB::unprepared('SET IDENTITY_INSERT exam_sub_categories OFF');
                    }
                }
            }

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

            // insert choices
            $choices_data = [];

            for ($i = 0; $i < count($data_question['choice_details']); $i++) {
                if ($data_question['choice_details'][$i] != null) {

                    if ($data_question['choice_id'][$i] == 0) {
                        $choice_id = (DB::table('exam_questionaire_details')->max('id') + 1);
                    } else {
                        $choice_id = $data_question['choice_id'][$i];
                    }

                    if (isset($data_question['is_correct_answer'])) {
                        if (in_array($data_question['choice_id'][$i], $data_question['is_correct_answer'])) {
                            $is_correct_answer = true;
                        } else {
                            $is_correct_answer = false;
                        }
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
