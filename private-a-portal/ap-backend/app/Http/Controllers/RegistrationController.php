<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RegistrationService;
use App\Traits\ApiResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
	use ApiResponse;
	
	private RegistrationService $registrationService;

	public function __construct(RegistrationService $registrationService)
	{
		$this->registrationService = $registrationService;
	}

	public function register()
	{
		$data = $this->registrationService->getRegistrationData();
		return response()->json([
			'success' => true,
			'data' => $data,
		]);
	}

	public function store(Request $request)
	{
		try {
			$result = $this->registrationService->registerApplicant($request);
			return $this->successResponse($result, 'Registration completed successfully');
		} catch (ValidationException $e) {
			// Return detailed validation errors for the frontend
			return $this->validationErrorResponse($e->errors());
		} catch (\Throwable $e) {
			// Log full exception (may contain non-UTF8 data) but do not expose it in JSON
			Log::error('Registration failed', ['exception' => $e]);

			// Return a safe, generic message that is guaranteed to be UTF-8
			return $this->serverErrorResponse('Registration failed. Please try again or contact support.');
		}
	}
}


