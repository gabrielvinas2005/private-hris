<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Intervention\Image\Facades\Image;
use App\Helpers\EncryptionHelper;

class PdsService
{
	public function store(Request $request, int $id): array
	{
		$section = $request->input('section', 'all');

		// Handle section-specific validation and updates
		switch ($section) {
			case 'personal':
				return $this->storePersonalSection($request, $id);
			case 'family':
				return $this->storeFamilySection($request, $id);
			case 'education':
				return $this->storeEducationSection($request, $id);
			case 'work':
				return $this->storeWorkSection($request, $id);
			case 'eligibility':
				return $this->storeEligibilitySection($request, $id);
			case 'trainings':
				return $this->storeTrainingsSection($request, $id);
			case 'voluntary':
				return $this->storeVoluntarySection($request, $id);
			case 'other':
				return $this->storeOtherSection($request, $id);
			default:
				return $this->storeAllSections($request, $id);
		}
	}

	/**
	 * Store personal information section only
	 */
	private function storePersonalSection(Request $request, int $id): array
	{
		$request->validate([
			'photo' => 'nullable|image|max:3000',
			'employee_no' => 'nullable|unique:employees,employee_no' . ($id ? ",{$id}" : ''),
			'email' => 'nullable|email|unique:employees,email' . ($id ? ",{$id}" : ''),
			'name_prefix_id' => 'nullable|integer',
			'first_name' => 'nullable|string',
			'last_name' => 'nullable|string',
			'birthdate' => 'nullable|date',
			'age' => 'nullable|integer',
			'gender_id' => 'nullable|integer',
			'civil_status_id' => 'nullable|integer',
			'citizenship_id' => 'nullable|integer',
			'religion_id' => 'nullable|integer'
		]);

		// Check for duplicate person only if creating new record
		if ($id === 0) {
			$same_person = DB::table('employees')->where([
				'first_name' => $request->first_name,
				'last_name' => $request->last_name,
				'birthdate' => $request->birthdate,
			])->count();
			if ($same_person > 0) {
				throw new \RuntimeException('Same person already exist in the database!');
			}
		}

		$height = ($request->height == 0 || $request->height == null || $request->height == '') ? 0 : $request->height;
		$weight = ($request->weight == 0 || $request->weight == null || $request->weight == '') ? 0 : $request->weight;

		// Prepare personal information data only
		$personal_data = [
			'employee_no' => $request->employee_no ?: '',
			'access_no' => $request->access_no ?: '',
			'email' => $request->email ?: '',
			'mobile_no' => $request->mobile_no ?: '',
			'telephone_no' => $request->telephone_no ?: '',
			'tin_no' => $request->tin_no ?: '',
			'gsis_no' => $request->gsis_no ?: '',
			'sss_no' => $request->sss_no ?: '',
			'pagibig_no' => $request->pagibig_no ?: '',
			'philhealth_no' => $request->philhealth_no ?: '',
			'name_prefix_id' => $request->name_prefix_id ?: 0,
			'first_name' => $request->first_name ?: '',
			'middle_name' => $request->middle_name ?: '',
			'last_name' => $request->last_name ?: '',
			'name_suffix_id' => $request->name_suffix_id ?: 0,
			'birth_place' => $request->birth_place ?: '',
			'birthdate' => $request->birthdate ?: '1900-01-01',
			'age' => $request->age ?: 0,
			'height' => $height,
			'weight' => $weight,
			'gender_id' => $request->gender_id ?: 0,
			'civil_status_id' => $request->civil_status_id ?: 0,
			'citizenship_id' => $request->citizenship_id ?: 0,
			'religion_id' => $request->religion_id ?: 0,
			'blood_type_id' => $request->blood_type_id ?: 0,
			'ra_region' => $request->ra_region ?: '',
			'ra_province' => $request->ra_province ?: '',
			'ra_city' => $request->ra_city ?: '',
			'ra_barangay' => $request->ra_barangay ?: '',
			'ra_house_no' => $request->ra_house_no ?: '',
			'ra_street' => $request->ra_street ?: '',
			'ra_village' => $request->ra_village ?: '',
			'pa_region' => $request->pa_region ?: '',
			'pa_province' => $request->pa_province ?: '',
			'pa_city' => $request->pa_city ?: '',
			'pa_barangay' => $request->pa_barangay ?: '',
			'pa_house_no' => $request->pa_house_no ?: '',
			'pa_street' => $request->pa_street ?: '',
			'pa_village' => $request->pa_village ?: '',
			'updated_at' => now(),
		];

		// Handle photo upload
		if ($request->hasFile('photo')) {
			$image_file = $request->photo;
			$image = Image::make($image_file);
			Response::make($image->encode('jpeg'));
			$personal_data['photo'] = base64_encode($image);
		}

		// Insert or update
		if ($id === 0) {
			$personal_data['active'] = true;
			$personal_data['is_employee'] = false;
			$personal_data['application_status_id'] = 1;
			$personal_data['created_at'] = now();
			$id = DB::table('employees')->insertGetId($personal_data);
		} else {
			$existingRecord = DB::table('employees')->where('id', $id)->first();
			if ($existingRecord) {
				DB::table('employees')->where('id', $id)->update($personal_data);
			} else {
				$personal_data['active'] = true;
				$personal_data['is_employee'] = false;
				$personal_data['application_status_id'] = 1;
				$personal_data['created_at'] = now();
				$id = DB::table('employees')->insertGetId($personal_data);
			}
		}

		return [
			'data' => [
				'employee_id' => $id,
				'action' => 'saved',
				'section' => 'personal',
			],
			'message' => 'Personal information saved successfully',
		];
	}

	/**
	 * Store family background section only
	 */
	private function storeFamilySection(Request $request, int $id): array
	{
		$request->validate([
			'father_name_prefix_id' => 'nullable|integer',
			'father_first_name' => 'nullable|string|max:255',
			'father_middle_name' => 'nullable|string|max:255',
			'father_last_name' => 'nullable|string|max:255',
			'father_name_suffix_id' => 'nullable|integer',
			'mother_name_prefix_id' => 'nullable|integer',
			'mother_first_name' => 'nullable|string|max:255',
			'mother_middle_name' => 'nullable|string|max:255',
			'mother_last_name' => 'nullable|string|max:255',
			'mother_name_suffix_id' => 'nullable|integer',
			'spouse_name_prefix_id' => 'nullable|integer',
			'spouse_first_name' => 'nullable|string|max:255',
			'spouse_middle_name' => 'nullable|string|max:255',
			'spouse_last_name' => 'nullable|string|max:255',
			'spouse_name_suffix_id' => 'nullable|integer',
			'spouse_occupation' => 'nullable|string|max:255',
			'spouse_employer' => 'nullable|string|max:255',
			'spouse_business_address' => 'nullable|string|max:500'
		]);

		// Prepare family information data only
		$family_data = [
			'father_name_prefix_id' => $request->father_name_prefix_id ?: 0,
			'father_first_name' => $request->father_first_name ?: '',
			'father_middle_name' => $request->father_middle_name ?: '',
			'father_last_name' => $request->father_last_name ?: '',
			'father_name_suffix_id' => $request->father_name_suffix_id ?: 0,
			'mother_name_prefix_id' => $request->mother_name_prefix_id ?: 0,
			'mother_first_name' => $request->mother_first_name ?: '',
			'mother_middle_name' => $request->mother_middle_name ?: '',
			'mother_last_name' => $request->mother_last_name ?: '',
			'mother_name_suffix_id' => $request->mother_name_suffix_id ?: 0,
			'spouse_name_prefix_id' => $request->spouse_name_prefix_id ?: 0,
			'spouse_first_name' => $request->spouse_first_name ?: '',
			'spouse_middle_name' => $request->spouse_middle_name ?: '',
			'spouse_last_name' => $request->spouse_last_name ?: '',
			'spouse_name_suffix_id' => $request->spouse_name_suffix_id ?: 0,
			'spouse_occupation' => $request->spouse_occupation ?: '',
			'spouse_employer' => $request->spouse_employer ?: '',
			'spouse_business_address' => $request->spouse_business_address ?: '',
			'updated_at' => now(),
		];
		
		// If creating new record (id === 0), include employee_no if provided
		if ($id === 0 && $request->has('employee_no') && !empty($request->employee_no)) {
			$family_data['employee_no'] = $request->employee_no;
			$family_data['access_no'] = random_int(100000, 999999);
		}


		// If id is 0, try to find existing employee by employee_no
		if ($id === 0 && $request->has('employee_no') && !empty($request->employee_no)) {
			$existingEmployee = DB::table('employees')
				->where('employee_no', $request->employee_no)
				->first();
			if ($existingEmployee) {
				$id = $existingEmployee->id;
			}
		}

		// Insert or update
		if ($id === 0) {
			$family_data['active'] = true;
			$family_data['is_employee'] = false;
			$family_data['application_status_id'] = 1;
			$family_data['created_at'] = now();
			$id = DB::table('employees')->insertGetId($family_data);
		} else {
			$existingRecord = DB::table('employees')->where('id', $id)->first();
			if ($existingRecord) {
				DB::table('employees')->where('id', $id)->update($family_data);
			} else {
				// If record doesn't exist by ID, try to find by employee_no
				if ($request->has('employee_no') && !empty($request->employee_no)) {
					$existingByNo = DB::table('employees')
						->where('employee_no', $request->employee_no)
						->first();
					if ($existingByNo) {
						DB::table('employees')->where('id', $existingByNo->id)->update($family_data);
						$id = $existingByNo->id;
					} else {
						// Create new record
						$family_data['active'] = true;
						$family_data['is_employee'] = false;
						$family_data['application_status_id'] = 1;
						$family_data['created_at'] = now();
						$id = DB::table('employees')->insertGetId($family_data);
					}
				} else {
					// Create new record
					$family_data['active'] = true;
					$family_data['is_employee'] = false;
					$family_data['application_status_id'] = 1;
					$family_data['created_at'] = now();
					$id = DB::table('employees')->insertGetId($family_data);
				}
			}
		}

		// Handle children data
		$this->upsertChildren($request, $id);

		return [
			'data' => [
				'employee_id' => $id,
				'action' => 'saved',
				'section' => 'family',
			],
			'message' => 'Family background information saved successfully',
		];
	}

	/**
	 * Store education section only
	 */
	private function storeEducationSection(Request $request, int $id): array
	{
		// Debug logging
		\Log::info('storeEducationSection called:', [
			'employee_id' => $id,
			'request_data' => $request->all(),
			'request_keys' => array_keys($request->all())
		]);
		
		// Handle education data
		$this->upsertEducations($request, $id);

		\Log::info('storeEducationSection completed successfully');

		return [
			'data' => [
				'employee_id' => $id,
				'action' => 'saved',
				'section' => 'education',
			],
			'message' => 'Education information saved successfully',
		];
	}

	/**
	 * Store work experience section only
	 */
	private function storeWorkSection(Request $request, int $id): array
	{
		// Handle work experience data
		$this->upsertEmploymentRecords($request, $id);

		return [
			'data' => [
				'employee_id' => $id,
				'action' => 'saved',
				'section' => 'work',
			],
			'message' => 'Work experience information saved successfully',
		];
	}

	/**
	 * Store eligibility section only
	 */
	private function storeEligibilitySection(Request $request, int $id): array
	{
		// Handle eligibility data
		$this->upsertExaminations($request, $id);

		return [
			'data' => [
				'employee_id' => $id,
				'action' => 'saved',
				'section' => 'eligibility',
			],
			'message' => 'Eligibility information saved successfully',
		];
	}

	/**
	 * Store trainings section only
	 */
	private function storeTrainingsSection(Request $request, int $id): array
	{
		// Handle trainings data
		$this->upsertTrainings($request, $id);

		return [
			'data' => [
				'employee_id' => $id,
				'action' => 'saved',
				'section' => 'trainings',
			],
			'message' => 'Training information saved successfully',
		];
	}

	/**
	 * Store voluntary work section only
	 */
	private function storeVoluntarySection(Request $request, int $id): array
	{
		// Handle voluntary work data
		$this->upsertOrganizations($request, $id);

		return [
			'data' => [
				'employee_id' => $id,
				'action' => 'saved',
				'section' => 'voluntary',
			],
			'message' => 'Voluntary work information saved successfully',
		];
	}

	/**
	 * Store other information section only
	 */
	private function storeOtherSection(Request $request, int $id): array
	{
		// Handle other data (skills, recognitions, memberships, references)
		$this->upsertSkills($request, $id);
		$this->upsertRecognitions($request, $id);
		$this->upsertMemberships($request, $id);
		$this->upsertReferences($request, $id);

		return [
			'data' => [
				'employee_id' => $id,
				'action' => 'saved',
				'section' => 'other',
			],
			'message' => 'Other information saved successfully',
		];
	}

	/**
	 * Store all sections (legacy method for complete PDS submission)
	 */
	private function storeAllSections(Request $request, int $id): array
	{
		$request->validate([
			'photo' => 'image|max:3000',
			'employee_no' => 'required|unique:employees,employee_no' . ($id ? ",{$id}" : ''),
			'email' => 'required|unique:employees,email' . ($id ? ",{$id}" : ''),
			'name_prefix_id' => 'required',
			'first_name' => 'required',
			'last_name' => 'required',
			'birthdate' => 'required',
			'age' => 'required',
			'gender_id' => 'required',
			'civil_status_id' => 'required',
			'citizenship_id' => 'required',
			'religion_id' => 'required'
		]);

		// Check for duplicate person only if creating new record
		if ($id === 0) {
			$same_person = DB::table('employees')->where([
				'first_name' => $request->first_name,
				'last_name' => $request->last_name,
				'birthdate' => $request->birthdate,
			])->count();
			if ($same_person > 0) {
				throw new \RuntimeException('Same person already exist in the database!');
			}
		}

		$height = ($request->height == 0 || $request->height == null || $request->height == '') ? 0 : $request->height;
		$weight = ($request->weight == 0 || $request->weight == null || $request->weight == '') ? 0 : $request->weight;
		$plantilla_id = ($request->plantilla_id == 0 || $request->plantilla_id == null || $request->plantilla_id == '') ? 0 : $request->plantilla_id;

		// Prepare complete employee data
		$employee_info = [
			'employee_no' => $request->employee_no ?: '',
			'access_no' => $request->access_no ?: '',
			'email' => $request->email ?: '',
			'mobile_no' => $request->mobile_no ?: '',
			'telephone_no' => $request->telephone_no ?: '',
			'tin_no' => $request->tin_no ?: '',
			'gsis_no' => $request->gsis_no ?: '',
			'sss_no' => $request->sss_no ?: '',
			'pagibig_no' => $request->pagibig_no ?: '',
			'philhealth_no' => $request->philhealth_no ?: '',
			'name_prefix_id' => $request->name_prefix_id ?: 0,
			'first_name' => $request->first_name ?: '',
			'middle_name' => $request->middle_name ?: '',
			'last_name' => $request->last_name ?: '',
			'name_suffix_id' => $request->name_suffix_id ?: 0,
			'birth_place' => $request->birth_place ?: '',
			'birthdate' => $request->birthdate ?: '1900-01-01',
			'age' => $request->age ?: 0,
			'height' => $height,
			'weight' => $weight,
			'gender_id' => $request->gender_id ?: 0,
			'civil_status_id' => $request->civil_status_id ?: 0,
			'citizenship_id' => $request->citizenship_id ?: 0,
			'religion_id' => $request->religion_id ?: 0,
			'blood_type_id' => $request->blood_type_id ?: 0,
			'ra_region' => $request->ra_region ?: '',
			'ra_province' => $request->ra_province ?: '',
			'ra_city' => $request->ra_city ?: '',
			'ra_barangay' => $request->ra_barangay ?: '',
			'ra_house_no' => $request->ra_house_no ?: '',
			'ra_street' => $request->ra_street ?: '',
			'ra_village' => $request->ra_village ?: '',
			'pa_region' => $request->pa_region ?: '',
			'pa_province' => $request->pa_province ?: '',
			'pa_city' => $request->pa_city ?: '',
			'pa_barangay' => $request->pa_barangay ?: '',
			'pa_house_no' => $request->pa_house_no ?: '',
			'pa_street' => $request->pa_street ?: '',
			'pa_village' => $request->pa_village ?: '',
			'father_name_prefix_id' => $request->father_name_prefix_id ?: 0,
			'father_first_name' => $request->father_first_name ?: '',
			'father_middle_name' => $request->father_middle_name ?: '',
			'father_last_name' => $request->father_last_name ?: '',
			'father_name_suffix_id' => $request->father_name_suffix_id ?: 0,
			'mother_name_prefix_id' => $request->mother_name_prefix_id ?: 0,
			'mother_first_name' => $request->mother_first_name ?: '',
			'mother_middle_name' => $request->mother_middle_name ?: '',
			'mother_last_name' => $request->mother_last_name ?: '',
			'mother_name_suffix_id' => $request->mother_name_suffix_id ?: 0,
			'spouse_name_prefix_id' => $request->spouse_name_prefix_id ?: 0,
			'spouse_first_name' => $request->spouse_first_name ?: '',
			'spouse_middle_name' => $request->spouse_middle_name ?: '',
			'spouse_last_name' => $request->spouse_last_name ?: '',
			'spouse_name_suffix_id' => $request->spouse_name_suffix_id ?: 0,
			'spouse_occupation' => $request->spouse_occupation ?: '',
			'spouse_employer' => $request->spouse_employer ?: '',
			'spouse_business_address' => $request->spouse_business_address ?: '',
			'is_dual_citizent' => $request->has('is_dual_citizent') ? true : false,
			'by_birth' => $request->customRadio == 'by_birth' ? true : false,
			'by_naturalization' => $request->customRadio == 'by_nat' ? true : false,
			'indicate_country' => $request->indicate_country ?: '',
			'company_id' => $request->company_id ?: 0,
			'branch_id' => $request->branch_id ?: 0,
			'department_id' => $request->department_id ?: 0,
			'division_id' => $request->division_id ?: 0,
			'section_id' => $request->section_id ?: 0,
			'employment_type_id' => $request->employment_type_id ?: 0,
			'date_hired' => $request->date_hired ?: null,
			'is_plantilla' => $request->has('is_plantilla') ? true : false,
			'is_teaching' => $request->has('is_teaching') ? true : false,
			'plantilla_id' => $plantilla_id,
			'salary_grade_id' => $request->salary_grade_id ?: 0,
			'salary_step_id' => $request->salary_step_id ?: 0,
			'position_id' => $request->position_id ?: 0,
			'end_date' => $request->end_dates ?: null,
			'payroll_interval_id' => $request->payroll_interval_id ?: 0,
			'salary' => $request->salary ?: 0,
			'tax_amount' => $request->tax_amount ?: 0,
			'gsis_amount' => $request->gsis_amount ?: 0,
			'sss_amount' => $request->sss_amount ?: 0,
			'pagibig_amount' => $request->pagibig_amount ?: 0,
			'philhealth_amount' => $request->philhealth_amount ?: 0,
			'active' => true,
			'is_employee' => false,
			'application_status_id' => 1,
			'account_no' => $request->account_no ?: '',
			'updated_at' => now(),
		];

		// Handle photo upload
		if ($request->hasFile('photo')) {
			$image_file = $request->photo;
			$image = Image::make($image_file);
			Response::make($image->encode('jpeg'));
			$employee_info['photo'] = base64_encode($image);
		}

		// Insert or update
		if ($id === 0) {
			$employee_info['created_at'] = now();
			$id = DB::table('employees')->insertGetId($employee_info);
		} else {
			$existingRecord = DB::table('employees')->where('id', $id)->first();
			if ($existingRecord) {
				DB::table('employees')->where('id', $id)->update($employee_info);
			} else {
				$employee_info['created_at'] = now();
				$id = DB::table('employees')->insertGetId($employee_info);
			}
		}

		// Handle plantilla
		if ($request->has('is_plantilla')) {
			DB::table('plantillas')->where('employee_id', $id)->update(['employee_id' => 0]);
			DB::table('plantillas')->where('id', $request->plantilla_id)->update(['employee_id' => $id]);
		} else {
			DB::table('plantillas')->where('employee_id', $id)->update(['employee_id' => 0]);
		}

		// Handle all related data
		$this->upsertChildren($request, $id);
		$this->upsertEducations($request, $id);
		$this->upsertServiceRecords($request, $id);
		$this->upsertEmploymentRecords($request, $id);
		$this->upsertExaminations($request, $id);
		$this->upsertTrainings($request, $id);
		$this->upsertOrganizations($request, $id);
		$this->upsertRecognitions($request, $id);
		$this->upsertSkills($request, $id);
		$this->upsertMemberships($request, $id);
		$this->upsertReferences($request, $id);
		$this->upsertDependents($request, $id);
		$this->upsertDocuments($request, $id);
		$this->replaceQuestionAnswers($request, $id);

		return [
			'data' => [
				'employee_id' => $id,
				'action' => $request->id == 0 ? 'added' : 'updated',
			],
			'message' => $request->id == 0 ? 'Employee information added successfully' : 'Employee information updated successfully',
		];
	}

	public function getDeleteData(int $typeId, int $id)
	{
		if ($typeId == 1) {
			return DB::table('employee_children')
				->select('children_id as id', 'child_name as name', DB::raw('1 as type_id'))
				->where('children_id', $id)->get();
		} elseif ($typeId == 2) {
			return DB::table('employee_educations')
				->select('education_id as id', 'school_name as name', DB::raw('2 as type_id'))
				->where('education_id', $id)->get();
		} elseif ($typeId == 3) {
			return DB::table('service_records')
				->select('service_record_id as id', 'designation as name', DB::raw('3 as type_id'))
				->where('service_record_id', $id)->get();
		} elseif ($typeId == 4) {
			return DB::table('employee_employment_records')
				->select('employment_record_id as id', 'work_company as name', DB::raw('4 as type_id'))
				->where('employment_record_id', $id)->get();
		} elseif ($typeId == 5) {
			return DB::table('employee_examinations')
				->select('examination_id as id', 'place_of_exam as name', DB::raw('5 as type_id'))
				->where('examination_id', $id)->get();
		} elseif ($typeId == 6) {
			return DB::table('employee_trainings')
				->select('training_id as id', 'training as name', DB::raw('6 as type_id'))
				->where('training_id', $id)->get();
		} elseif ($typeId == 7) {
			return DB::table('employee_organizations')
				->select('organization_id as id', 'organization as name', DB::raw('7 as type_id'))
				->where('organization_id', $id)->get();
		} elseif ($typeId == 8) {
			return DB::table('employee_recognations')
				->select('recognation_id as id', 'recognation as name', DB::raw('8 as type_id'))
				->where('recognation_id', $id)->get();
		} elseif ($typeId == 9) {
			return DB::table('employee_skills')
				->select('skill_id as id', 'skill as name', DB::raw('9 as type_id'))
				->where('skill_id', $id)->get();
		} elseif ($typeId == 10) {
			return DB::table('employee_memberships')
				->select('membership_id as id', 'membership as name', DB::raw('10 as type_id'))
				->where('membership_id', $id)->get();
		} elseif ($typeId == 11) {
			return DB::table('employee_references')
				->select('reference_id as id', 'ref_name as name', DB::raw('11 as type_id'))
				->where('reference_id', $id)->get();
		} elseif ($typeId == 12) {
			return DB::table('employee_dependents')
				->select('id', 'name', DB::raw('12 as type_id'))
				->where('id', $id)->get();
		}
		return DB::connection('attachments')->table('employee_documents')
			->select('employee_document_id as id', 'name', DB::raw('13 as type_id'))
			->where('employee_document_id', $id)->get();
	}

	public function destroyRecord(int $typeId, int $id): array
	{
		if ($typeId == 1) {
			DB::table('employee_children')->where('children_id', $id)->delete();
			$desc = 'Deleted Child table informations.';
		} elseif ($typeId == 2) {
			DB::table('employee_educations')->where('education_id', $id)->delete();
			$desc = 'Deleted Education table informations.';
		} elseif ($typeId == 3) {
			DB::table('service_records')->where('service_record_id', $id)->delete();
			$desc = 'Deleted Service Record table informations.';
		} elseif ($typeId == 4) {
			DB::table('employee_employment_records')->where('employment_record_id', $id)->delete();
			$desc = 'Deleted Employment Record table informations.';
		} elseif ($typeId == 5) {
			DB::table('employee_examinations')->where('examination_id', $id)->delete();
			$desc = 'Deleted Eligibility table informations.';
		} elseif ($typeId == 6) {
			DB::table('employee_trainings')->where('training_id', $id)->delete();
			$desc = 'Deleted Training table informations.';
		} elseif ($typeId == 7) {
			DB::table('employee_organizations')->where('organization_id', $id)->delete();
			$desc = 'Deleted Organization table informations.';
		} elseif ($typeId == 8) {
			DB::table('employee_recognations')->where('recognation_id', $id)->delete();
			$desc = 'Deleted Recognition table informations.';
		} elseif ($typeId == 9) {
			DB::table('employee_skills')->where('skill_id', $id)->delete();
			$desc = 'Deleted Skill table informations.';
		} elseif ($typeId == 10) {
			DB::table('employee_memberships')->where('membership_id', $id)->delete();
			$desc = 'Deleted Membership table informations.';
		} elseif ($typeId == 11) {
			DB::table('employee_references')->where('reference_id', $id)->delete();
			$desc = 'Deleted References table informations.';
		} elseif ($typeId == 12) {
			DB::table('employee_dependents')->where('id', $id)->delete();
			$desc = 'Deleted Dependents table informations.';
		} else {
			DB::connection('attachments')
				->table('employee_documents')
				->where('employee_document_id', $id)
				->delete();
			$desc = 'Deleted Document table informations.';
		}

		// Optional: Add audit trail here if model exists
		return [
			'data' => [
				'type_id' => $typeId,
				'id' => $id,
				'action' => 'deleted',
			],
			'message' => 'Record deleted successfully',
		];
	}

	public function getDocumentDownloadInfo(int $employeeDocumentId): array
	{
		// Attachments DB is source of truth
		$attachmentDoc = DB::connection('attachments')
			->table('employee_documents')
			->where('employee_document_id', $employeeDocumentId)
			->first();
		
		if (!$attachmentDoc) {
			throw new \RuntimeException('Document not found.');
		}

		$pathToFile = null;
		if ($attachmentDoc && !empty($attachmentDoc->file_content)) {
			// File exists in attachments DB - download URL will handle it
			$pathToFile = 'attachments_db'; // Marker that file is in DB
		} else {
			// Fallback to disk storage
			$pathToFile = storage_path('app/employee_documents/' . 'DOCS' . $attachmentDoc->employee_id . '_' . $attachmentDoc->attachment_name);
			if (!file_exists($pathToFile)) {
				throw new \RuntimeException('File not found on server.');
			}
		}

		return [
			'file_path' => $pathToFile,
			'file_name' => $attachmentDoc->attachment_name,
			'employee_id' => $attachmentDoc->employee_id,
			'download_url' => url('/api/download-document/' . $employeeDocumentId),
		];
	}

	private function upsertChildren(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['child_name']) ? count($data['child_name']) : 0;
		
		// Get all existing children IDs for this employee
		$existingChildrenIds = DB::table('employee_children')
			->where('employee_id', $employeeId)
			->pluck('children_id')
			->toArray();
		
		$processedChildrenIds = [];
		
		for ($i = 0; $i < $len; $i++) {
			if ($data['child_name'][$i] != null && $data['child_name'][$i] != '') {
				$child_id_from_frontend = $data['children_id'][$i] ?? null;
				$payload = [
					'employee_id' => $employeeId,
					'child_name' => $data['child_name'][$i],
					'child_middlename' => $data['child_middlename'][$i] ?? '',
					'child_gender_id' => $data['child_gender'][$i] ?? 0,
					'child_lastname' => $data['child_lastname'][$i] ?? '',
					'child_birthdate' => $data['child_birthdate'][$i] ?? null,
					'updated_at' => now(),
				];
				
				if ($child_id_from_frontend) {
					// Update existing child - don't set created_at
					DB::table('employee_children')
						->where('children_id', $child_id_from_frontend)
						->update($payload);
					$processedChildrenIds[] = $child_id_from_frontend;
				} else {
					// Insert new child - set both created_at and updated_at
					$payload['created_at'] = now();
					DB::table('employee_children')->insertGetId($payload);
				}
			}
		}
		
		// Delete children that were removed from the form
		$childrenToDelete = array_diff($existingChildrenIds, $processedChildrenIds);
		if (!empty($childrenToDelete)) {
			DB::table('employee_children')
				->where('employee_id', $employeeId)
				->whereIn('children_id', $childrenToDelete)
				->delete();
		}
	}

	private function upsertEducations(Request $request, int $employeeId): void
	{
		$data = $request->all();
		
		// Handle FormData array structure from frontend
		$educations = [];
		
		// Check for educations array structure
		if (isset($data['educations']) && is_array($data['educations'])) {
			$educations = $data['educations'];
		} else {
			// Parse FormData array structure (educations[0][field], educations[1][field], etc.)
			foreach ($data as $key => $value) {
				if (preg_match('/^educations\[(\d+)\]\[(.+)\]$/', $key, $matches)) {
					$index = (int)$matches[1];
					$field = $matches[2];
					
					if (!isset($educations[$index])) {
						$educations[$index] = [];
					}
					$educations[$index][$field] = $value;
				}
			}
		}
		
		if (empty($educations)) {
			return;
		}
		
		foreach ($educations as $index => $education) {
			if (!empty($education['school_name'])) {
				$education_id_from_frontend = $education['education_id'] ?? null;
				
				// Extract year from date strings for from, to, and graduated_year fields
				$from_year = null;
				$to_year = null;
				$graduated_year = null;
				
				if (!empty($education['from'])) {
					$from_year = is_numeric($education['from']) ? (int)$education['from'] : (int)date('Y', strtotime($education['from']));
				}
				
				if (!empty($education['to'])) {
					$to_year = is_numeric($education['to']) ? (int)$education['to'] : (int)date('Y', strtotime($education['to']));
				}
				
				if (!empty($education['graduated_year'])) {
					$graduated_year = is_numeric($education['graduated_year']) ? (int)$education['graduated_year'] : (int)date('Y', strtotime($education['graduated_year']));
				}
				
				$payload = [
					'employee_id' => $employeeId,
					'school_name' => $education['school_name'] ?? '',
					'academic_level_id' => $education['academic_level_id'] ?? null,
					'program' => $education['program'] ?? '',
					'from' => $from_year,
					'to' => $to_year,
					'graduated_year' => $graduated_year,
					'units_earned' => $education['units_earned'] ?? '',
					'honors' => $education['honors'] ?? '',
				];
				
				if ($education_id_from_frontend) {
					DB::table('employee_educations')
						->where('education_id', $education_id_from_frontend)
						->update($payload);
				} else {
					DB::table('employee_educations')->insertGetId($payload);
				}
			}
		}
	}

	private function upsertServiceRecords(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['designation']) ? count($data['designation']) : 0;
		for ($i = 0; $i < $len; $i++) {
			if ($data['designation'][$i] != null) {
				$rec_id_from_frontend = $data['service_record_id'][$i] ?? null;
				$payload = [
					'employee_id' => $employeeId,
					'start_date' => $data['start_date'][$i],
					'end_date' => $data['end_date'][$i],
					'designation' => $data['designation'][$i],
					'employment_type' => $data['employment_type'][$i],
					'annual_salary' => $data['annual_salary'][$i],
					'place_of_assignment' => $data['place_of_assignment'][$i],
					'leave_without_pay' => $data['leave_without_pay'][$i],
					'separation_date' => $data['separation_date'][$i],
					'cause' => $data['cause'][$i],
					'branch' => $data['branch'][$i],
				];
				
				if ($rec_id_from_frontend) {
					DB::table('service_records')
						->where('service_record_id', $rec_id_from_frontend)
						->update($payload);
				} else {
					DB::table('service_records')->insertGetId($payload);
				}
			}
		}
	}

	private function upsertEmploymentRecords(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['work_company']) ? count($data['work_company']) : 0;
		for ($i = 0; $i < $len; $i++) {
			if ($data['work_company'][$i] != null) {
				$rec_id_from_frontend = $data['employment_record_id'][$i] ?? null;
				
				// Handle date fields properly
				$work_start_date = null;
				$work_end_date = null;
				
				if (!empty($data['work_start_date'][$i])) {
					$work_start_date = date('Y-m-d', strtotime($data['work_start_date'][$i]));
				}
				
				if (!empty($data['work_end_date'][$i])) {
					$work_end_date = date('Y-m-d', strtotime($data['work_end_date'][$i]));
				}
				
				$payload = [
					'employee_id' => $employeeId,
					'work_start_date' => $work_start_date,
					'work_end_date' => $work_end_date,
					'work_company' => $data['work_company'][$i],
					'monthly_salary' => $data['monthly_salary'][$i],
					'salary_grade_step' => $data['salary_grade_step'][$i],
					'status_of_appointment' => $data['status_of_appointment'][$i],
					'position' => $data['position'][$i],
					'government_service_id' => $data['government_service_id'][$i],
				];
				
				if ($rec_id_from_frontend) {
					DB::table('employee_employment_records')
						->where('employment_record_id', $rec_id_from_frontend)
						->update($payload);
				} else {
					DB::table('employee_employment_records')->insertGetId($payload);
				}
			}
		}
	}

	private function upsertExaminations(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['place_of_exam']) ? count($data['place_of_exam']) : 0;
		for ($i = 0; $i < $len; $i++) {
			if ($data['place_of_exam'][$i] != null) {
				$rec_id_from_frontend = $data['examination_id'][$i] ?? null;
				
				// Handle date fields properly
				$exam_date = null;
				$date_released = null;
				
				if (!empty($data['exam_date'][$i])) {
					$exam_date = date('Y-m-d', strtotime($data['exam_date'][$i]));
				}
				
				if (!empty($data['date_released'][$i])) {
					$date_released = date('Y-m-d', strtotime($data['date_released'][$i]));
				}
				
				$payload = [
					'employee_id' => $employeeId,
					'eligibility_id' => $data['eligibility_id'][$i],
					'exam_rating' => $data['exam_rating'][$i],
					'exam_date' => $exam_date,
					'place_of_exam' => $data['place_of_exam'][$i],
					'license_number' => $data['license_number'][$i],
					'date_released' => $date_released,
				];
				
				if ($rec_id_from_frontend) {
					DB::table('employee_examinations')
						->where('examination_id', $rec_id_from_frontend)
						->update($payload);
				} else {
					DB::table('employee_examinations')->insertGetId($payload);
				}
			}
		}
	}

	private function upsertTrainings(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['training']) ? count($data['training']) : 0;
		for ($i = 0; $i < $len; $i++) {
			if ($data['training'][$i] != null) {
				$rec_id_from_frontend = $data['training_id'][$i] ?? null;
				
				// Handle date fields properly
				$training_from = null;
				$training_to = null;
				
				if (!empty($data['training_from'][$i])) {
					$training_from = date('Y-m-d', strtotime($data['training_from'][$i]));
				}
				
				if (!empty($data['training_to'][$i])) {
					$training_to = date('Y-m-d', strtotime($data['training_to'][$i]));
				}
				
				$payload = [
					'employee_id' => $employeeId,
					'training' => $data['training'][$i],
					'training_from' => $training_from,
					'training_to' => $training_to,
					'hours' => $data['hours'][$i],
					'sponsored_by' => $data['sponsored_by'][$i],
					'learning_id' => $data['learning_id'][$i],
				];
				
				if ($rec_id_from_frontend) {
					DB::table('employee_trainings')
						->where('training_id', $rec_id_from_frontend)
						->update($payload);
				} else {
					DB::table('employee_trainings')->insertGetId($payload);
				}
			}
		}
	}

	private function upsertOrganizations(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['organization']) ? count($data['organization']) : 0;
		for ($i = 0; $i < $len; $i++) {
			if ($data['organization'][$i] != null) {
				$rec_id_from_frontend = $data['organization_id'][$i] ?? null;
				
				// Handle date fields properly
				$org_from = null;
				$org_to = null;
				
				if (!empty($data['org_from'][$i])) {
					$org_from = date('Y-m-d', strtotime($data['org_from'][$i]));
				}
				
				if (!empty($data['org_to'][$i])) {
					$org_to = date('Y-m-d', strtotime($data['org_to'][$i]));
				}
				
				$payload = [
					'employee_id' => $employeeId,
					'organization' => $data['organization'][$i],
					'org_from' => $org_from,
					'org_to' => $org_to,
					'org_hours' => $data['org_hours'][$i],
					'org_position' => $data['org_position'][$i],
				];
				
				if ($rec_id_from_frontend) {
					DB::table('employee_organizations')
						->where('organization_id', $rec_id_from_frontend)
						->update($payload);
				} else {
					DB::table('employee_organizations')->insertGetId($payload);
				}
			}
		}
	}

	private function upsertRecognitions(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['recognation']) ? count($data['recognation']) : 0;
		for ($i = 0; $i < $len; $i++) {
			if ($data['recognation'][$i] != null) {
				$rec_id_from_frontend = $data['recognation_id'][$i] ?? null;
				$payload = [
					'employee_id' => $employeeId,
					'recognation' => $data['recognation'][$i],
				];
				
				if ($rec_id_from_frontend) {
					DB::table('employee_recognations')
						->where('recognation_id', $rec_id_from_frontend)
						->update($payload);
				} else {
					DB::table('employee_recognations')->insertGetId($payload);
				}
			}
		}
	}

	private function upsertSkills(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['skill']) ? count($data['skill']) : 0;
		for ($i = 0; $i < $len; $i++) {
			if ($data['skill'][$i] != null) {
				$rec_id_from_frontend = $data['skill_id'][$i] ?? null;
				$payload = [
					'employee_id' => $employeeId,
					'skill' => $data['skill'][$i],
				];
				
				if ($rec_id_from_frontend) {
					DB::table('employee_skills')
						->where('skill_id', $rec_id_from_frontend)
						->update($payload);
				} else {
					DB::table('employee_skills')->insertGetId($payload);
				}
			}
		}
	}

	private function upsertMemberships(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['membership']) ? count($data['membership']) : 0;
		for ($i = 0; $i < $len; $i++) {
			if ($data['membership'][$i] != null) {
				$rec_id_from_frontend = $data['membership_id'][$i] ?? null;
				$payload = [
					'employee_id' => $employeeId,
					'membership' => $data['membership'][$i],
				];
				
				if ($rec_id_from_frontend) {
					DB::table('employee_memberships')
						->where('membership_id', $rec_id_from_frontend)
						->update($payload);
				} else {
					DB::table('employee_memberships')->insertGetId($payload);
				}
			}
		}
	}

	private function upsertReferences(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['ref_name']) ? count($data['ref_name']) : 0;
		for ($i = 0; $i < $len; $i++) {
			if ($data['ref_name'][$i] != null) {
				$rec_id_from_frontend = $data['reference_id'][$i] ?? null;
				$payload = [
					'employee_id' => $employeeId,
					'ref_name' => $data['ref_name'][$i],
					'ref_address' => $data['ref_address'][$i],
					'ref_occupation' => $data['ref_occupation'][$i],
					'ref_contact_no' => $data['ref_contact_no'][$i],
					'ref_email' => $data['ref_email'][$i],
				];
				
				if ($rec_id_from_frontend) {
					DB::table('employee_references')
						->where('reference_id', $rec_id_from_frontend)
						->update($payload);
				} else {
					DB::table('employee_references')->insertGetId($payload);
				}
			}
		}
	}

	private function upsertDependents(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['dep_name']) ? count($data['dep_name']) : 0;
		for ($i = 0; $i < $len; $i++) {
			if ($data['dep_name'][$i] != null) {
				$rec_id_from_frontend = $data['dependent_id'][$i] ?? null;
				$payload = [
					'employee_id' => $employeeId,
					'name' => $data['dep_name'][$i],
					'relationship' => $data['dep_relationship'][$i],
					'course' => $data['dep_course'][$i],
				];
				
				if ($rec_id_from_frontend) {
					DB::table('employee_dependents')
						->where('id', $rec_id_from_frontend)
						->update($payload);
				} else {
					DB::table('employee_dependents')->insertGetId($payload);
				}
			}
		}
	}

	private function upsertDocuments(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['document_name']) ? count($data['document_name']) : 0;
		for ($i = 0; $i < $len; $i++) {
			if ($data['document_name'][$i] != null) {
				$rec_id_from_frontend = $data['document_id'][$i] ?? null;
				if ($request->hasFile('document')) {
					$files = $request->file('document');
					if (isset($files[$i])) {
						$file_name = $files[$i]->getClientOriginalName();
						$file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'employee_documents\\' . 'DOCS' . $employeeId . '_' . $file_name;
						$extension = $files[$i]->getClientOriginalExtension();
						$allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
						if (in_array($extension, $allowed)) {
							$fileContent = file_get_contents($files[$i]->getRealPath());
							$encodedContent = base64_encode($fileContent);
							$fileSize = $files[$i]->getSize();
							$fileType = $files[$i]->getClientMimeType();

							$payload = [
								'employee_id' => $employeeId,
								'name' => $data['document_name'][$i],
								'description' => $data['document_description'][$i],
								'attachment_name' => $file_name,
								'path' => $file_path,
								'extension' => $extension,
								'file_content' => $encodedContent,
								'file_size' => $fileSize,
								'file_type' => $fileType,
								'updated_at' => now(),
							];
							
							if ($rec_id_from_frontend) {
								DB::connection('attachments')->table('employee_documents')
									->where('employee_document_id', $rec_id_from_frontend)
									->update($payload);
							} else {
								$payload['created_at'] = now();
								DB::connection('attachments')->table('employee_documents')->insertGetId($payload);
							}
							$request->document[$i]->storeAs('employee_documents', 'DOCS' . $employeeId . '_' . $file_name);
						}
					}
				}
			}
		}
	}

	private function replaceQuestionAnswers(Request $request, int $employeeId): void
	{
		$data = $request->all();
		$len = isset($data['question_id']) ? count($data['question_id']) : 0;
		DB::table('employee_pds_answers')->where('employee_id', $employeeId)->delete();
		for ($i = 0; $i < $len; $i++) {
			if ($data['question_id'][$i] != null) {
				$payload = [
					'employee_id' => $employeeId,
					'question_id' => $data['question_id'][$i],
					'is_yes' => isset($data['is_yes'][$data['question_id'][$i]]) ? true : false,
					'is_no' => isset($data['is_no'][$data['question_id'][$i]]) ? true : false,
					'yes_details' => $data['yes_details'][$i],
					'date_filed' => $data['date_filed'][$i],
					'case_status' => $data['case_status'][$i],
				];
				DB::table('employee_pds_answers')->insert($payload);
			}
		}
	}
}


