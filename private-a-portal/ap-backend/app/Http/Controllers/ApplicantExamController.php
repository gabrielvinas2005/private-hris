<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ExamService;

class ApplicantExamController extends Controller
{
	private ExamService $examService;

	public function __construct(ExamService $examService)
	{
		$this->examService = $examService;
	}

	public function intro(int $id)
	{
		$data = $this->examService->getIntroData($id);
		return $this->successResponse($data, 'Applicant examination intro data loaded successfully');
	}

	public function page(int $id)
	{
		$data = $this->examService->getPageData($id);
		return $this->successResponse($data, 'Applicant examination page data loaded successfully');
	}

	public function autoSave(Request $request, int $applicant_examination_id)
	{
		$this->examService->autoSave($request, $applicant_examination_id);
		return response()->json('success');
	}

	public function submit(Request $request, int $applicant_examination_id)
	{
		$this->examService->submit($request, $applicant_examination_id);
		return $this->successResponse(null, 'Successfully submitted examinations. To check your exam result go to examination tab.');
	}

	public function result(int $applicant_examination_id)
	{
		$data = $this->examService->getResultData($applicant_examination_id);
		return $this->successResponse($data, 'Applicant examination result data loaded successfully');
	}
}


