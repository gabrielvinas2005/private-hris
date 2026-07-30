<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ExamService
{
	public function getIntroData(int $scheduleId)
	{
		$applicant = DB::table('applicant_headers as a')
			->join('genders as b', 'a.gender', '=', 'b.id')
			->select('a.*', 'b.name as gender')
			->where('user_id', Auth::user()->id)
			->get();

		$applicant_id = $applicant[0]->id;

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
				'd.description'
			)
			->where([
				'a.posted' => 1,
				'c.applicant_id' => $applicant_id,
				'a.id' => $scheduleId
			])
			->get();

		$exam_sub_categories = DB::table('exam_sub_categories as a')
			->join('exam_difficulty_levels as b', 'a.difficulty_level', '=', 'b.id')
			->select('a.sub_category', 'b.difficulty_level', 'a.existing_questions')
			->where('a.category_id', $exam[0]->category_id)
			->get();

		return [
			'applicant' => $applicant,
			'exam' => $exam,
			'exam_sub_categories' => $exam_sub_categories,
		];
	}

	public function getPageData(int $scheduleId)
	{
		$applicant = DB::table('applicant_headers as a')
			->join('genders as b', 'a.gender', '=', 'b.id')
			->select('a.*', 'b.name as gender')
			->where('user_id', Auth::user()->id)
			->get();

		$applicant_id = $applicant[0]->id;

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
				DB::raw("ISNULL(c.last_duration,0) as last_duration")
			)
			->where([
				'a.posted' => 1,
				'c.applicant_id' => $applicant_id,
				'a.id' => $scheduleId
			])
			->get();

		$exam_sub_categories = DB::table('exam_sub_categories as a')
			->join('exam_difficulty_levels as b', 'a.difficulty_level', '=', 'b.id')
			->join('exam_questionaire_headers as c', 'a.id', '=', 'c.sub_category_id')
			->select('a.id', 'a.sub_category', 'b.difficulty_level', 'a.existing_questions')
			->where('a.category_id', $exam[0]->category_id)
			->distinct()
			->get();

		$exam_questions = DB::table('exam_sub_categories as a')
			->join('exam_difficulty_levels as b', 'a.difficulty_level', '=', 'b.id')
			->join('exam_questionaire_headers as c', 'a.id', '=', 'c.sub_category_id')
			->select('a.id', 'c.sub_category_id', 'c.id as question_id', 'c.question', 'c.question_image')
			->where('a.category_id', $exam[0]->category_id)
			->distinct()
			->get();

		$exam_choices = DB::table('exam_sub_categories as a')
			->join('exam_difficulty_levels as b', 'a.difficulty_level', '=', 'b.id')
			->join('exam_questionaire_headers as c', 'a.id', '=', 'c.sub_category_id')
			->join('exam_questionaire_details as d', 'c.id', '=', 'd.question_id')
			->select(
				'a.id',
				'c.id as question_id',
				'd.id as choice_id',
				'd.choice_details',
				'd.choice_image',
				DB::raw("(select TOP 1 ISNULL(f.choice_id,0) from applicant_examination_details f 
				where f.applicant_examination_id = {$exam[0]->applicant_examination_id} 
				and f.question_id = c.id and f.choice_id = d.id) as selected_choice")
			)
			->where('a.category_id', $exam[0]->category_id)
			->distinct()
			->get();

		return [
			'applicant' => $applicant,
			'exam' => $exam,
			'exam_sub_categories' => $exam_sub_categories,
			'exam_questions' => $exam_questions,
			'exam_choices' => $exam_choices,
		];
	}

	public function autoSave(Request $request, int $applicantExaminationId)
	{
		$data_answers = $request->all();
		$answers = [];

		for ($i = 0; $i < count($data_answers['question_id']); $i++) {
			$get_answer = DB::table('exam_questionaire_details')
				->select('id')
				->where([
					'is_correct_answer' => 1,
					'question_id' => $data_answers['question_id'][$i]
				])
				->get();

			$correct_answer_id = $get_answer->isNotEmpty() ? $get_answer[0]->id : 0;

			if (isset($data_answers['choices' . $data_answers['question_id'][$i]])) {
				$choice_answer_id = $data_answers['choices' . $data_answers['question_id'][$i]][$data_answers['question_id'][$i]];
				$is_correct_answer = DB::table('exam_questionaire_details')
					->select('is_correct_answer', 'id as correct_answer_id')
					->where([
						'is_correct_answer' => true,
						'id' => $choice_answer_id
					])
					->get();
				if ($is_correct_answer->isEmpty()) {
					$correct = 0; $wrong = 1; $unanswered = 0;
				} else {
					$correct = 1; $wrong = 0; $unanswered = 0;
				}
			} else {
				$choice_answer_id = 0; $correct = 0; $wrong = 0; $unanswered = 1;
			}

			$answers = [
				'applicant_examination_id' => $applicantExaminationId,
				'question_id' => $data_answers['question_id'][$i],
				'choice_id' => $choice_answer_id,
				'correct_answer_id' => $correct_answer_id,
				'correct' => $correct,
				'wrong' => $wrong,
				'unanswered' => $unanswered,
				'date_submitted' => now(),
			];

			$is_exist = DB::table('applicant_examination_details')->where([
				'applicant_examination_id' => $applicantExaminationId,
				'question_id' => $data_answers['question_id'][$i],
			])->get();

			if ($is_exist->isNotEmpty()) {
				DB::table('applicant_examination_details')->where([
					'applicant_examination_id' => $applicantExaminationId,
					'question_id' => $data_answers['question_id'][$i],
				])->update($answers);
			} else {
				DB::table('applicant_examination_details')->insert($answers);
			}
		}

		$exam_data = DB::table('applicant_examination_details')
			->select(
				DB::raw('count(question_id) as total_item'),
				DB::raw('sum(correct) as total_correct'),
				DB::raw('sum(wrong) as total_wrong'),
				DB::raw('sum(unanswered) as total_unanswered')
			)
			->where('applicant_examination_id', $applicantExaminationId)
			->get();

		$allocation_data = DB::table('applicant_examination_headers as a')
			->join('examination_schedule_header as b', 'a.exam_schedule_id', '=', 'b.id')
			->join('examination_setup_header as c', 'b.exam_id', '=', 'c.id')
			->select(DB::raw('isnull(c.weighted_allocation,0) as weighted_allocation'))
			->where('a.id', $applicantExaminationId)
			->get();

		$weighted_allocation = $allocation_data[0]->weighted_allocation;
		$total_items = $exam_data[0]->total_item;
		$total_correct = $exam_data[0]->total_correct;
		$exam_rating = ((($total_correct / $total_items) * $weighted_allocation) * 100);

		DB::table('applicant_examination_headers')
			->where(['id' => $applicantExaminationId])
			->update([
				'is_complete' => false,
				'date_completed' => null,
				'exam_rating' => $exam_rating,
				'total_items' => $total_items,
				'total_score' => $total_correct,
				'last_duration' => $request->last_duration,
			]);

		return 'success';
	}

	public function submit(Request $request, int $applicantExaminationId)
	{
		$data_answers = $request->all();
		$answers = [];

		for ($i = 0; $i < count($data_answers['question_id']); $i++) {
			$get_answer = DB::table('exam_questionaire_details')
				->select('id')
				->where([
					'is_correct_answer' => 1,
					'question_id' => $data_answers['question_id'][$i]
				])
				->get();
			$correct_answer_id = $get_answer->isNotEmpty() ? $get_answer[0]->id : 0;

			if (isset($data_answers['choices' . $data_answers['question_id'][$i]])) {
				$choice_answer_id = $data_answers['choices' . $data_answers['question_id'][$i]][$data_answers['question_id'][$i]];
				$is_correct_answer = DB::table('exam_questionaire_details')
					->select('is_correct_answer', 'id as correct_answer_id')
					->where([
						'is_correct_answer' => true,
						'id' => $choice_answer_id
					])
					->get();
				if ($is_correct_answer->isEmpty()) {
					$correct = 0; $wrong = 1; $unanswered = 0;
				} else {
					$correct = 1; $wrong = 0; $unanswered = 0;
				}
			} else {
				$choice_answer_id = 0; $correct = 0; $wrong = 0; $unanswered = 1;
			}

			$answers = [
				'applicant_examination_id' => $applicantExaminationId,
				'question_id' => $data_answers['question_id'][$i],
				'choice_id' => $choice_answer_id,
				'correct_answer_id' => $correct_answer_id,
				'correct' => $correct,
				'wrong' => $wrong,
				'unanswered' => $unanswered,
				'date_submitted' => now(),
			];

			$is_exist = DB::table('applicant_examination_details')->where([
				'applicant_examination_id' => $applicantExaminationId,
				'question_id' => $data_answers['question_id'][$i],
			])->get();

			if ($is_exist->isNotEmpty()) {
				DB::table('applicant_examination_details')->where([
					'applicant_examination_id' => $applicantExaminationId,
					'question_id' => $data_answers['question_id'][$i],
				])->update($answers);
			} else {
				DB::table('applicant_examination_details')->insert($answers);
			}
		}

		$exam_data = DB::table('applicant_examination_details')
			->select(
				DB::raw('count(question_id) as total_item'),
				DB::raw('sum(correct) as total_correct'),
				DB::raw('sum(wrong) as total_wrong'),
				DB::raw('sum(unanswered) as total_unanswered')
			)
			->where('applicant_examination_id', $applicantExaminationId)
			->get();

		$allocation_data = DB::table('applicant_examination_headers as a')
			->join('examination_schedule_header as b', 'a.exam_schedule_id', '=', 'b.id')
			->join('examination_setup_header as c', 'b.exam_id', '=', 'c.id')
			->select(DB::raw('isnull(c.weighted_allocation,0) as weighted_allocation'))
			->where('a.id', $applicantExaminationId)
			->get();

		$weighted_allocation = $allocation_data[0]->weighted_allocation;
		$total_items = $exam_data[0]->total_item;
		$total_correct = $exam_data[0]->total_correct;
		$exam_rating = ((($total_correct / $total_items) * $weighted_allocation) * 100);

		DB::table('applicant_examination_headers')
			->where(['id' => $applicantExaminationId])
			->update([
				'is_complete' => true,
				'date_completed' => now(),
				'exam_rating' => $exam_rating,
				'total_items' => $total_items,
				'total_score' => $total_correct,
				'last_duration' => $request->last_duration ?? 0
			]);

		return null;
	}

	public function getResultData(int $applicantExaminationId)
	{
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
				'c.exam_rating',
				'c.total_items',
				'c.total_score',
				'c.date_completed',
				'c.last_duration'
			)
			->where([
				'a.posted' => 1,
				'c.id' => $applicantExaminationId
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
				DB::raw('count(b.question_id) as total_items'),
				DB::raw('sum(b.correct) as total_correct')
			)
			->where('a.id', $applicantExaminationId)
			->groupBy('a.id', 'd.sub_category', 'e.difficulty_level')
			->get();

		return [
			'exam' => $exam,
			'exam_total_sub_categories' => $exam_total_sub_categories,
		];
	}
}


