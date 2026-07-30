<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ApplicantPdsService;
use App\Traits\ApiResponse;

class ApplicantPdsController extends Controller
{
	use ApiResponse;
	
	private ApplicantPdsService $applicantPdsService;

	public function __construct(ApplicantPdsService $applicantPdsService)
	{
		$this->applicantPdsService = $applicantPdsService;
	}

	/**
	 * Store PDS (Personal Data Sheet) information including family background
	 * 
	 * @param Request $request
	 * @param int $id Employee ID
	 * @return \Illuminate\Http\JsonResponse
	 * 
	 * Usage Examples:
	 * 
	 * 1. Store family background only:
	 * POST /api/applicant-pds-store/{id}
	 * {
	 *   "section": "family",
	 *   "father_first_name": "John",
	 *   "father_last_name": "Doe",
	 *   "mother_first_name": "Jane",
	 *   "mother_last_name": "Doe",
	 *   "spouse_first_name": "Mary",
	 *   "spouse_last_name": "Doe"
	 * }
	 * 
	 * 2. Store personal information only:
	 * POST /api/applicant-pds-store/{id}
	 * {
	 *   "section": "personal",
	 *   "first_name": "John",
	 *   "last_name": "Doe",
	 *   "email": "john@example.com"
	 * }
	 * 
	 * 3. Store complete PDS (all sections):
	 * POST /api/applicant-pds-store/{id}
	 * {
	 *   "section": "all",
	 *   // ... all PDS fields
	 * }
	 */
	public function store(Request $request, int $id): JsonResponse
	{
		try {
			// Log the incoming request for debugging
			\Log::info('PDS Store Request', [
				'employee_id' => $id,
				'section' => $request->input('section', 'all'),
				'has_family_data' => $request->has('father_first_name') || $request->has('mother_first_name') || $request->has('spouse_first_name')
			]);

			$result = $this->applicantPdsService->store($request, $id);
			
			// Log successful operation
			\Log::info('PDS Store Success', [
				'employee_id' => $id,
				'result' => $result
			]);
			
			return $this->successResponse($result['data'], $result['message']);
		} catch (\Illuminate\Validation\ValidationException $e) {
			\Log::error('PDS Store Validation Error', [
				'employee_id' => $id,
				'errors' => $e->errors()
			]);
			return $this->validationErrorResponse($e->errors(), 'Validation failed');
		} catch (\Throwable $e) {
			\Log::error('PDS Store Error', [
				'employee_id' => $id,
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
			return $this->serverErrorResponse('Failed to store employee information: ' . $e->getMessage());
		}
	}

	public function delete(int $type_id, int $id): JsonResponse
	{
		// For now, just return success - can be implemented later if needed
		return $this->successResponse(['type_id' => $type_id, 'id' => $id], 'Delete data retrieved successfully');
	}

	public function destroy(int $type_id, int $id)
	{
		try {
			$result = $this->applicantPdsService->destroyRecord($type_id, $id);
			
			if ($result['success']) {
				return $this->successResponse($result['data'], $result['message']);
			} else {
				return $this->errorResponse($result['message'], 400);
			}
		} catch (\Throwable $e) {
			return $this->serverErrorResponse('Failed to delete record: ' . $e->getMessage());
		}
	}

	public function download(int $id)
	{
		try {
			return $this->successResponse(['id' => $id], 'File information retrieved successfully');
		} catch (\Throwable $e) {
			return $this->serverErrorResponse('Failed to retrieve file information: ' . $e->getMessage());
		}
	}

	/**
	 * Get PDS questions with answers for an employee
	 * 
	 * @param int $id Employee ID
	 * @return JsonResponse
	 */
	public function getQuestions(int $id): JsonResponse
	{
		try {
			$result = $this->applicantPdsService->getQuestions($id);
			return $this->successResponse($result['data'], $result['message']);
		} catch (\Throwable $e) {
			\Log::error('Get Questions Error', [
				'employee_id' => $id,
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
			return $this->serverErrorResponse('Failed to retrieve questions: ' . $e->getMessage());
		}
	}

	/**
	 * Store PDS questionnaire answers
	 * 
	 * @param Request $request
	 * @param int $id Employee ID
	 * @return JsonResponse
	 */
	public function storeQuestionnaire(Request $request, int $id): JsonResponse
	{
		try {
			$result = $this->applicantPdsService->storeQuestionnaire($request, $id);
			return $this->successResponse($result['data'], $result['message']);
		} catch (\Throwable $e) {
			\Log::error('Store Questionnaire Error', [
				'employee_id' => $id,
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
			return $this->serverErrorResponse('Failed to store questionnaire answers: ' . $e->getMessage());
		}
	}
}


