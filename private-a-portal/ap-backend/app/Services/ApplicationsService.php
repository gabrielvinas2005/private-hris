<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ApplicationsService
{
	public function apply(int $applicantId, int $positionId, bool $isPlantilla): array
	{
		if ($isPlantilla) {
			$plantilla_data_insert = [
				'applicant_id' => $applicantId,
				'application_status_id' => 1,
				'position_applied_id' => $positionId,
				'is_plantilla' => $isPlantilla,
			];
			DB::table('applicant_details')->insert([$plantilla_data_insert]);
		} else {
			$non_plantilla_data_insert = [
				'applicant_id' => $applicantId,
				'application_status_id' => 1,
				'position_applied_id' => $positionId,
				'is_plantilla' => $isPlantilla,
			];
			DB::table('applicant_details')->insert([$non_plantilla_data_insert]);
		}

		return [
			'applicant_id' => $applicantId,
			'position_id' => $positionId,
			'is_plantilla' => $isPlantilla,
			'action' => 'applied',
		];
	}
}


