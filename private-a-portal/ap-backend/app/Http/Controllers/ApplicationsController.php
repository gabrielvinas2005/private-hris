<?php

namespace App\Http\Controllers;

use App\Services\ApplicationsService;

class ApplicationsController extends Controller
{
	private ApplicationsService $applicationsService;

	public function __construct(ApplicationsService $applicationsService)
	{
		$this->applicationsService = $applicationsService;
	}

	public function apply(int $applicant_id, int $position_id, $is_plantilla)
	{
		try {
			$isPlantilla = filter_var($is_plantilla, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
			if ($isPlantilla === null) {
				$isPlantilla = (bool) $is_plantilla;
			}
			$result = $this->applicationsService->apply($applicant_id, $position_id, $isPlantilla);
			return $this->successResponse($result, 'Application submitted successfully');
		} catch (\Throwable $e) {
			return $this->serverErrorResponse('Failed to apply for position: ' . $e->getMessage());
		}
	}
}


