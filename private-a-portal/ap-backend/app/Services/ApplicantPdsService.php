<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Helpers\EncryptionHelper;

class ApplicantPdsService
{
    /**
     * Store PDS (Personal Data Sheet) information for applicants
     * 
     * @param Request $request
     * @param int $id Applicant ID
     * @return array
     */
    public function store(Request $request, int $id): array
    {
        $section = $request->input('section', 'personal');

        // Handle section-specific updates
        switch ($section) {
            case 'personal':
                return $this->storePersonalSection($request, $id);
            case 'education':
                return $this->storeEducationSection($request, $id);
            case 'family':
                return $this->storeFamilySection($request, $id);
            case 'work':
                return $this->storeWorkSection($request, $id);
            case 'eligibility':
                return $this->storeEligibilitySection($request, $id);
            case 'trainings':
                return $this->storeTrainingSection($request, $id);
            case 'voluntary':
            case 'organizations':
                return $this->storeVoluntaryWorkSection($request, $id);
            case 'recognitions':
                return $this->storeRecognitionsSection($request, $id);
            case 'skills':
                return $this->storeSkillsSection($request, $id);
            case 'references':
                return $this->storeReferencesSection($request, $id);
            case 'dependents':
                return $this->storeDependentsSection($request, $id);
            case 'documents':
                return $this->storeDocumentsSection($request, $id);
            default:
                return $this->storePersonalSection($request, $id);
        }
    }

    /**
     * Store personal information section for applicants
     */
    private function storePersonalSection(Request $request, int $id): array
    {
        try {
            Log::info('ApplicantPdsService - Personal Section Request', [
                'applicant_id' => $id,
                'request_data' => $request->all(),
                'has_file_photo' => $request->hasFile('photo')
            ]);

            $request->validate([
                'photo' => 'nullable|image|max:3000',
                'email' => 'nullable|email',
                'first_name' => 'nullable|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'middlename' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'surname' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date',
                'birthdate' => 'nullable|date',
                'birth_place' => 'nullable|string|max:255',
                'age' => 'nullable|integer',
                'gender' => 'nullable',
                'mobile_no' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'prefix' => 'nullable|integer',
                'suffix' => 'nullable|integer',
                'citizenship' => 'nullable|integer',
                'civil_status' => 'nullable|integer',
                'religion' => 'nullable|integer',
                'height' => 'nullable|numeric',
                'weight' => 'nullable|numeric',
                'blood_type' => 'nullable|integer',
                'telephone_no' => 'nullable|string|max:255',
                'tin_no' => 'nullable|string|max:255',
                'gsis_no' => 'nullable|string|max:255',
                'sss_no' => 'nullable|string|max:255',
                'pagibig_no' => 'nullable|string|max:255',
                'philhealth_no' => 'nullable|string|max:255',
                'ra_region' => 'required|string|max:255',
                'ra_province' => 'required|string|max:255',
                'ra_city' => 'required|string|max:255',
                'ra_barangay' => 'required|string|max:255',
                'ra_house_no' => 'nullable|string|max:255',
                'ra_street' => 'required|string|max:255',
                'ra_village' => 'required|string|max:255',
                'pa_region' => 'required|string|max:255',
                'pa_province' => 'required|string|max:255',
                'pa_city' => 'required|string|max:255',
                'pa_barangay' => 'required|string|max:255',
                'pa_house_no' => 'nullable|string|max:255',
                'pa_street' => 'required|string|max:255',
                'pa_village' => 'required|string|max:255',
                'is_dual_citizen' => 'nullable|boolean',
                'by_birth' => 'nullable|boolean',
                'by_naturalization' => 'nullable|boolean',
                'indicate_country' => 'nullable|string|max:255',
            ]);

            $personal_data = [
                'first_name' => $request->input('first_name', ''),
                'middle_name' => $request->input('middle_name') ?: $request->input('middlename', ''),
                'last_name' => $request->input('last_name') ?: $request->input('surname', ''),
                'email' => $request->input('email', ''),
                'mobile_no' => $request->input('mobile_no', ''),
                'address' => $request->input('address', ''),
                'birth_date' => $request->input('birth_date') ?: $request->input('birthdate') ?: null,
                'age' => (int) $request->input('age', 0),
                'gender' => $this->mapGenderToId($request->input('gender', 0)),
                'updated_at' => now(),
            ];

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoData = base64_encode(file_get_contents($photo->getPathname()));
                $personal_data['photo'] = $photoData;
            }

            Log::info('ApplicantPdsService - Updating personal data', [
                'applicant_id' => $id,
                'personal_data' => $personal_data
            ]);

            $applicantExists = DB::table('applicant_headers')
                ->where('id', $id)
                ->exists();

            if (!$applicantExists) {
                Log::error('ApplicantPdsService - Applicant not found', [
                    'applicant_id' => $id
                ]);
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Applicant not found in database'
                ];
            }

            $applicant = DB::table('applicant_headers')
                ->where('id', $id)
                ->first(['user_id', 'applicant_no']);
            
            $user_id = $applicant->user_id ?? null;
            $applicant_no = $applicant->applicant_no ?? null;

            $addressParts = [];
            if ($request->input('ra_house_no')) $addressParts[] = $request->input('ra_house_no');
            if ($request->input('ra_street')) $addressParts[] = $request->input('ra_street');
            if ($request->input('ra_barangay')) $addressParts[] = $request->input('ra_barangay');
            if ($request->input('ra_village')) $addressParts[] = $request->input('ra_village');
            if ($request->input('ra_city')) $addressParts[] = $request->input('ra_city');
            if ($request->input('ra_province')) $addressParts[] = $request->input('ra_province');
            if ($request->input('ra_region')) $addressParts[] = $request->input('ra_region');
            
            if (!empty($addressParts)) {
                $personal_data['address'] = implode(', ', array_filter($addressParts));
            } elseif ($request->input('address')) {
                $personal_data['address'] = $request->input('address');
            }

            $updated = DB::table('applicant_headers')
                ->where('id', $id)
                ->update($personal_data);

            if ($user_id && isset($personal_data['photo'])) {
                DB::table('users')
                    ->where('id', $user_id)
                    ->update([
                        'photo' => $personal_data['photo'],
                        'updated_at' => now()
                    ]);
                
                Log::info('ApplicantPdsService - Photo also updated in users table', [
                    'user_id' => $user_id,
                    'applicant_id' => $id
                ]);
            }

            if ($applicant_no) {
                $employee_data = [
                    'first_name' => $request->input('first_name', ''),
                    'middle_name' => $request->input('middle_name') ?: $request->input('middlename', ''),
                    'last_name' => $request->input('last_name') ?: $request->input('surname', ''),
                    'name_prefix_id' => (int) $request->input('prefix', 0),
                    'name_suffix_id' => (int) $request->input('suffix', 0),
                    'birth_place' => $request->input('birth_place', ''),
                    'birthdate' => $request->input('birth_date') ?: $request->input('birthdate') ?: null,
                    'age' => (int) $request->input('age', 0),
                    'gender_id' => $this->mapGenderToId($request->input('gender', 0)),
                    'citizenship_id' => (int) $request->input('citizenship', 0),
                    'civil_status_id' => (int) $request->input('civil_status', 0),
                    'religion_id' => (int) $request->input('religion', 0),
                    'height' => $request->input('height') ? (float) $request->input('height') : 0,
                    'weight' => $request->input('weight') ? (float) $request->input('weight') : 0,
                    'email' => $request->input('email', ''),
                    'mobile_no' => $request->input('mobile_no', ''),
                    'telephone_no' => $request->input('telephone_no', ''),
                    'tin_no' => $request->input('tin_no', ''),
                    'gsis_no' => $request->input('gsis_no', ''),
                    'sss_no' => $request->input('sss_no', ''),
                    'pagibig_no' => $request->input('pagibig_no', ''),
                    'philhealth_no' => $request->input('philhealth_no', ''),
                    'ra_region' => $request->input('ra_region', ''),
                    'ra_province' => $request->input('ra_province', ''),
                    'ra_city' => $request->input('ra_city', ''),
                    'ra_barangay' => $request->input('ra_barangay', ''),
                    'ra_house_no' => $request->input('ra_house_no', ''),
                    'ra_street' => $request->input('ra_street', ''),
                    'ra_village' => $request->input('ra_village', ''),
                    'pa_region' => $request->input('pa_region', ''),
                    'pa_province' => $request->input('pa_province', ''),
                    'pa_city' => $request->input('pa_city', ''),
                    'pa_barangay' => $request->input('pa_barangay', ''),
                    'pa_house_no' => $request->input('pa_house_no', ''),
                    'pa_street' => $request->input('pa_street', ''),
                    'pa_village' => $request->input('pa_village', ''),
                    'is_dual_citizent' => $request->input('is_dual_citizen') == '1' || $request->input('is_dual_citizen') === true || $request->input('is_dual_citizen') === 1,
                    'by_birth' => $request->input('by_birth') == '1' || $request->input('by_birth') === true || $request->input('by_birth') === 1,
                    'by_naturalization' => $request->input('by_naturalization') == '1' || $request->input('by_naturalization') === true || $request->input('by_naturalization') === 1,
                    'indicate_country' => $request->input('indicate_country', ''),
                    'updated_at' => now(),
                ];

                if ($request->hasFile('photo')) {
                    $photo = $request->file('photo');
                    $photoData = base64_encode(file_get_contents($photo->getPathname()));
                    $employee_data['photo'] = $photoData;
                } elseif (isset($personal_data['photo'])) {
                    $employee_data['photo'] = $personal_data['photo'];
                }

                if ($request->has('blood_type')) {
                    $employee_data['blood_type_id'] = (int) $request->input('blood_type', 0);
                }

                $employee = DB::table('employees')
                    ->where('employee_no', $applicant_no)
                    ->first();

                if ($employee) {
                    DB::table('employees')
                        ->where('employee_no', $applicant_no)
                        ->update($employee_data);
                    
                    Log::info('ApplicantPdsService - Employee record updated', [
                        'employee_no' => $applicant_no,
                        'applicant_id' => $id
                    ]);
                } else {
                    $employee_data['employee_no'] = $applicant_no;
                    $employee_data['active'] = true;
                    $employee_data['is_employee'] = false;
                    $employee_data['application_status_id'] = 1;
                    $employee_data['created_at'] = now();
                    
                    DB::table('employees')->insert($employee_data);
                    
                    Log::info('ApplicantPdsService - New employee record created', [
                        'employee_no' => $applicant_no,
                        'applicant_id' => $id
                    ]);
                }
            }

            Log::info('ApplicantPdsService - Update result', [
                'applicant_id' => $id,
                'updated' => $updated
            ]);

            if ($updated) {
                return [
                    'success' => true,
                    'data' => ['id' => $id, 'updated' => $updated],
                    'message' => 'Personal information updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'No changes made to personal information'
                ];
            }

        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Personal Section Error', [
                'applicant_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to update personal information: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Store education section for applicants
     */
    private function storeEducationSection(Request $request, int $id): array
    {
        try {
            // Validate expected fields coming from the frontend
            $request->validate([
                'academic_level_id' => 'nullable|integer',
                'level' => 'nullable|string|max:255', // kept for backwards compatibility
                'school_name' => 'nullable|string|max:255',
                'course' => 'nullable|string|max:255',
                'start_date' => 'nullable|string',
                'end_date' => 'nullable|string',
                'honors' => 'nullable|string|max:255',
                'units_earned' => 'nullable|string|max:255',
                'year_graduated' => 'nullable|string|max:255',
                'id' => 'nullable|integer',
                'education_id' => 'nullable|integer',
            ]);

            // Convert incoming dates (YYYY or YYYY-MM-DD) to year integers
            $fromYear = $this->extractYear($request->input('start_date'));
            $toYear = $this->extractYear($request->input('end_date'));
            $graduatedYear = $this->extractYear($request->input('year_graduated'));

            // Prepare payload that matches the employee_educations table schema
            $education_data = [
                'employee_id' => $id,
                'academic_level_id' => (int) $request->input('academic_level_id', 0),
                'school_name' => $request->input('school_name', ''),
                'program' => $request->input('course', ''),
                'from' => $fromYear,
                'to' => $toYear,
                'graduated_year' => $graduatedYear,
                'units_earned' => $request->input('units_earned', ''),
                'honors' => $request->input('honors', ''),
                'updated_at' => now(),
            ];

            // Determine whether we're updating or inserting
            $educationId = $request->input('education_id') ?: $request->input('id');

            if ($educationId) {
                $updated = DB::table('employee_educations')
                    ->where('education_id', $educationId)
                    ->where('employee_id', $id)
                    ->update($education_data);

                if ($updated) {
                    return [
                        'success' => true,
                        'data' => ['id' => $educationId, 'updated' => $updated],
                        'message' => 'Education record updated successfully'
                    ];
                }

                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'No changes made to education record'
                ];
            }

            $education_data['created_at'] = now();
            $newId = DB::table('employee_educations')->insertGetId($education_data);

            return [
                'success' => true,
                'data' => ['id' => $newId, 'created' => true],
                'message' => 'Education record created successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Education Section Error', [
                'applicant_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to update education record: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract a 4-digit year from various date inputs.
     */
    private function extractYear($value): ?int
    {
        if (empty($value)) {
            return null;
        }

        // If already numeric, cast to int
        if (is_numeric($value)) {
            return (int) $value;
        }

        try {
            return (int) date('Y', strtotime($value));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Store family section for applicants
     */
    private function storeFamilySection(Request $request, int $id): array
    {
        try {
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
                'message' => 'Family information saved successfully',
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Family Section Error', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Let the controller handle the error response
        }
    }

    /**
     * Upsert children data for an employee
     */
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

    /**
     * Store work section for applicants → Work_Experience table
     * Columns: id, Reference_id, Position, Work_start_date, Work_end_date, Duration,
     *          Office_name, Office_Address, Immediate_supervisor, List_Of_Accomplishment,
     *          Summary_of_Duties, Created_at
     */
    private function storeWorkSection(Request $request, int $id): array
    {
        try {
            $request->validate([
                'office_name' => 'nullable|string|max:255',
                'office_address' => 'nullable|string|max:500',
                'position' => 'nullable|string|max:255',
                'work_start_date' => 'nullable|string',
                'work_end_date' => 'nullable|string',
                'is_present' => 'nullable|boolean',
                'immediate_supervisor' => 'nullable|string|max:255',
                'list_of_accomplishment' => 'nullable|string',
                'summary_of_duties' => 'nullable|string',
                'work_experience_id' => 'nullable|integer',
            ]);

            $employee = DB::table('employees')->where('id', $id)->first(['employee_no']);
            if (!$employee || empty($employee->employee_no)) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Employee not found or missing employee_no (Reference_id).',
                ];
            }

            $referenceId = $employee->employee_no;
            $workStartDate = $request->input('work_start_date');
            $workEndDate = $request->input('work_end_date');
            $isPresent = $request->boolean('is_present') || ($workEndDate === '' || $workEndDate === null);

            if (!empty($workStartDate)) {
                $workStartDate = date('Y-m-d', strtotime($workStartDate));
            } else {
                $workStartDate = null;
            }

            if (!$isPresent && !empty($workEndDate)) {
                $workEndDate = date('Y-m-d', strtotime($workEndDate));
            } else {
                $workEndDate = null;
            }

            $duration = $this->computeWorkDuration($workStartDate, $workEndDate, $isPresent);

            $payload = [
                'Reference_id' => $referenceId,
                'Position' => $request->input('position', ''),
                'Work_start_date' => $workStartDate,
                'Work_end_date' => $workEndDate,
                'Duration' => $duration,
                'Office_name' => $request->input('office_name', ''),
                'Office_Address' => $request->input('office_address', ''),
                'Immediate_supervisor' => $request->input('immediate_supervisor', ''),
                'List_Of_Accomplishment' => $request->input('list_of_accomplishment', ''),
                'Summary_of_Duties' => $request->input('summary_of_duties', ''),
            ];

            $workExperienceId = $request->input('work_experience_id') ?: $request->input('id');

            if ($workExperienceId) {
                $updated = DB::table('Work_Experience')
                    ->where('id', $workExperienceId)
                    ->where('Reference_id', $referenceId)
                    ->update($payload);

                if ($updated) {
                    return [
                        'success' => true,
                        'data' => ['id' => $workExperienceId, 'updated' => $updated],
                        'message' => 'Work experience record updated successfully',
                    ];
                }

                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'No changes made to work experience record',
                ];
            }

            $payload['Created_at'] = now();
            $newId = DB::table('Work_Experience')->insertGetId($payload);

            return [
                'success' => true,
                'data' => ['id' => $newId, 'created' => true],
                'message' => 'Work experience record created successfully',
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Work Section Error', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to update work experience record: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Compute duration string (e.g. "1 year", "6 months") from work dates.
     */
    private function computeWorkDuration(?string $start, ?string $end, bool $isPresent): string
    {
        if (empty($start)) {
            return '';
        }
        $startDate = \DateTime::createFromFormat('Y-m-d', $start);
        if (!$startDate) {
            return '';
        }
        $endDate = $isPresent || empty($end) ? new \DateTime() : \DateTime::createFromFormat('Y-m-d', $end);
        if (!$endDate || $endDate < $startDate) {
            return '';
        }
        $interval = $startDate->diff($endDate);
        $years = $interval->y + $interval->m / 12 + $interval->d / 365.25;
        if ($years >= 1) {
            $y = (int) floor($years);
            $m = (int) round(($years - $y) * 12);
            if ($m === 0) {
                return $y === 1 ? '1 year' : $y . ' years';
            }
            return $y . ' year' . ($y !== 1 ? 's' : '') . ', ' . $m . ' month' . ($m !== 1 ? 's' : '');
        }
        $months = (int) round($years * 12);
        return $months === 1 ? '1 month' : $months . ' months';
    }

    /**
     * Store eligibility section for applicants
     */
    private function storeEligibilitySection(Request $request, int $id): array
    {
        try {
            $request->validate([
                'eligibility_id' => 'nullable|integer',
                'exam_rating' => 'nullable|string|max:255',
                'exam_date' => 'nullable|string',
                'place_of_exam' => 'nullable|string|max:255',
                'license_number' => 'nullable|string|max:255',
                'date_released' => 'nullable|string',
                'examination_id' => 'nullable|integer',
            ]);

            $exam_date = $request->input('exam_date');
            $date_released = $request->input('date_released');

            if (!empty($exam_date)) {
                $exam_date = date('Y-m-d', strtotime($exam_date));
            } else {
                $exam_date = null;
            }

            if (!empty($date_released)) {
                $date_released = date('Y-m-d', strtotime($date_released));
            } else {
                $date_released = null;
            }

            $payload = [
                'employee_id' => $id,
                'eligibility_id' => $request->input('eligibility_id', 0),
                'exam_rating' => $request->input('exam_rating', ''),
                'exam_date' => $exam_date,
                'place_of_exam' => $request->input('place_of_exam', ''),
                'license_number' => $request->input('license_number', ''),
                'date_released' => $date_released,
                'updated_at' => now(),
            ];

            $examinationId = $request->input('examination_id') ?: $request->input('id');

            if ($examinationId) {
                $updated = DB::table('employee_examinations')
                    ->where('examination_id', $examinationId)
                    ->where('employee_id', $id)
                    ->update($payload);

                if ($updated) {
                    return [
                        'success' => true,
                        'data' => ['id' => $examinationId, 'updated' => $updated],
                        'message' => 'Eligibility record updated successfully'
                    ];
                }

                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'No changes made to eligibility record'
                ];
            }

            $payload['created_at'] = now();
            $newId = DB::table('employee_examinations')->insertGetId($payload);

            return [
                'success' => true,
                'data' => ['id' => $newId, 'created' => true],
                'message' => 'Eligibility record created successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Eligibility Section Error', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to update eligibility record: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Map gender string to gender ID
     */
    private function mapGenderToId($gender): int
    {
        if (is_numeric($gender)) {
            return (int) $gender;
        }

        $genderMap = [
            'Male' => 1,
            'Female' => 2,
            'male' => 1,
            'female' => 2,
            'M' => 1,
            'F' => 2,
        ];

        return $genderMap[$gender] ?? 1; // Default to Male (1) if not found
    }

    /**
     * Store training section for applicants
     */
    private function storeTrainingSection(Request $request, int $id): array
    {
        try {
            // Handle trainings data - check if sent as JSON string or array
            $trainingsInput = $request->input('trainings');
            
            // If it's a JSON string, decode it
            if (is_string($trainingsInput)) {
                $trainings = json_decode($trainingsInput, true) ?? [];
            } else {
                $trainings = is_array($trainingsInput) ? $trainingsInput : [];
            }

            // Also check for array format like PdsService (training[0], training[1], etc.)
            if (empty($trainings) && $request->has('training')) {
                $data = $request->all();
                $len = isset($data['training']) ? count($data['training']) : 0;
                $trainings = [];
                
                for ($i = 0; $i < $len; $i++) {
                    if (!empty($data['training'][$i])) {
                        $trainings[] = [
                            'training_id' => $data['training_id'][$i] ?? null,
                            'training' => $data['training'][$i],
                            'dateFrom' => $data['training_from'][$i] ?? $data['dateFrom'][$i] ?? '',
                            'dateTo' => $data['training_to'][$i] ?? $data['dateTo'][$i] ?? '',
                            'hours' => $data['hours'][$i] ?? 0,
                            'sponsoredBy' => $data['sponsored_by'][$i] ?? $data['sponsoredBy'][$i] ?? '',
                            'learning_id' => $data['learning_id'][$i] ?? 0,
                        ];
                    }
                }
            }

            // Get all existing training IDs for this employee
            $existingTrainingIds = DB::table('employee_trainings')
                ->where('employee_id', $id)
                ->pluck('training_id')
                ->toArray();
            
            $processedTrainingIds = [];
            
            foreach ($trainings as $training) {
                if (empty($training['seminar']) && empty($training['training'])) {
                    continue; // Skip empty trainings
                }
                
                // IMPORTANT: Only use persistent training_id from DB.
                // Frontend also sends a local "id" used for UI that must NOT be treated as training_id.
                $trainingId = $training['training_id'] ?? null;
                $trainingFrom = !empty($training['dateFrom']) ? date('Y-m-d', strtotime($training['dateFrom'])) : null;
                $trainingTo = !empty($training['dateTo']) ? date('Y-m-d', strtotime($training['dateTo'])) : null;
                
                $payload = [
                    'employee_id' => $id,
                    'training' => $training['seminar'] ?? $training['training'] ?? '',
                    'training_from' => $trainingFrom,
                    'training_to' => $trainingTo,
                    'hours' => $training['hours'] ?? 0,
                    'sponsored_by' => $training['sponsoredBy'] ?? $training['sponsored_by'] ?? '',
                    'learning_id' => $training['learning_id'] ?? $training['learningId'] ?? 0,
                    // Map optional skills/competencies field if provided
                    'skills_development' => $training['skills_development'] ?? $training['skillsDevelopment'] ?? '',
                    'updated_at' => now(),
                ];
                
                if ($trainingId) {
                    // Update existing training
                    DB::table('employee_trainings')
                        ->where('training_id', $trainingId)
                        ->where('employee_id', $id)
                        ->update($payload);
                    $processedTrainingIds[] = $trainingId;
                } else {
                    // Insert new training
                    $payload['created_at'] = now();
                    $newId = DB::table('employee_trainings')->insertGetId($payload);
                    $processedTrainingIds[] = $newId;
                }
            }
            
            // Delete trainings that were removed from the form
            $trainingsToDelete = array_diff($existingTrainingIds, $processedTrainingIds);
            if (!empty($trainingsToDelete)) {
                DB::table('employee_trainings')
                    ->where('employee_id', $id)
                    ->whereIn('training_id', $trainingsToDelete)
                    ->delete();
            }

            return [
                'success' => true,
                'data' => [
                    'employee_id' => $id,
                    'action' => 'saved',
                    'section' => 'trainings',
                    'processed' => count($processedTrainingIds),
                    'deleted' => count($trainingsToDelete)
                ],
                'message' => 'Training information saved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Training Section Error', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to save training information: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Store voluntary work/organizations section for applicants
     */
    private function storeVoluntaryWorkSection(Request $request, int $id): array
    {
        try {
            // Handle voluntary work data - check if sent as JSON string or array
            $organizationsInput = $request->input('organizations');
            
            // If it's a JSON string, decode it
            if (is_string($organizationsInput)) {
                $organizations = json_decode($organizationsInput, true) ?? [];
            } else {
                $organizations = is_array($organizationsInput) ? $organizationsInput : [];
            }

            // Also check for array format like PdsService (organization[0], organization[1], etc.)
            if (empty($organizations) && $request->has('organization')) {
                $data = $request->all();
                $len = isset($data['organization']) ? count($data['organization']) : 0;
                $organizations = [];
                
                for ($i = 0; $i < $len; $i++) {
                    if (!empty($data['organization'][$i])) {
                        $organizations[] = [
                            'organization_id' => $data['organization_id'][$i] ?? null,
                            'organization' => $data['organization'][$i],
                            'organizationAddress' => $data['organization_address'][$i] ?? $data['organizationAddress'][$i] ?? '',
                            'dateFrom' => $data['org_from'][$i] ?? $data['dateFrom'][$i] ?? '',
                            'dateTo' => $data['org_to'][$i] ?? $data['dateTo'][$i] ?? '',
                            'hours' => $data['org_hours'][$i] ?? $data['hours'][$i] ?? 0,
                            'position' => $data['org_position'][$i] ?? $data['position'][$i] ?? '',
                        ];
                    }
                }
            }

            // Get all existing organization IDs for this employee
            $existingOrganizationIds = DB::table('employee_organizations')
                ->where('employee_id', $id)
                ->pluck('organization_id')
                ->toArray();
            
            $processedOrganizationIds = [];
            
            foreach ($organizations as $org) {
                if (empty($org['organization']) && empty($org['organization'])) {
                    continue; // Skip empty organizations
                }
                
                $organizationId = $org['organization_id'] ?? $org['id'] ?? null;
                $orgFrom = !empty($org['dateFrom']) ? date('Y-m-d', strtotime($org['dateFrom'])) : null;
                $orgTo = null;
                
                // Handle ongoing flag - if isOngoing is true, set org_to to null
                if (!empty($org['isOngoing']) && $org['isOngoing']) {
                    $orgTo = null;
                } elseif (!empty($org['dateTo'])) {
                    $orgTo = date('Y-m-d', strtotime($org['dateTo']));
                }
                
                $payload = [
                    'employee_id' => $id,
                    'organization' => $org['organization'] ?? '',
                    'organization_address' => $org['organizationAddress'] ?? $org['organization_address'] ?? '',
                    'org_from' => $orgFrom,
                    'org_to' => $orgTo,
                    'org_hours' => $org['hours'] ?? $org['org_hours'] ?? 0,
                    'org_position' => $org['position'] ?? $org['org_position'] ?? '',
                    'updated_at' => now(),
                ];
                
                if ($organizationId) {
                    // Update existing organization
                    DB::table('employee_organizations')
                        ->where('organization_id', $organizationId)
                        ->where('employee_id', $id)
                        ->update($payload);
                    $processedOrganizationIds[] = $organizationId;
                } else {
                    // Insert new organization
                    $payload['created_at'] = now();
                    $newId = DB::table('employee_organizations')->insertGetId($payload);
                    $processedOrganizationIds[] = $newId;
                }
            }
            
            // Delete organizations that were removed from the form
            $organizationsToDelete = array_diff($existingOrganizationIds, $processedOrganizationIds);
            if (!empty($organizationsToDelete)) {
                DB::table('employee_organizations')
                    ->where('employee_id', $id)
                    ->whereIn('organization_id', $organizationsToDelete)
                    ->delete();
            }

            return [
                'success' => true,
                'data' => [
                    'employee_id' => $id,
                    'action' => 'saved',
                    'section' => 'voluntary',
                    'processed' => count($processedOrganizationIds),
                    'deleted' => count($organizationsToDelete)
                ],
                'message' => 'Voluntary work information saved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Voluntary Work Section Error', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to save voluntary work information: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Store recognitions section for applicants
     */
    private function storeRecognitionsSection(Request $request, int $id): array
    {
        try {
            // Handle recognitions data - check if sent as JSON string or array
            $recognitionsInput = $request->input('recognitions');
            
            // If it's a JSON string, decode it
            if (is_string($recognitionsInput)) {
                $recognitions = json_decode($recognitionsInput, true) ?? [];
            } else {
                $recognitions = is_array($recognitionsInput) ? $recognitionsInput : [];
            }

            // Get all existing recognition IDs for this employee
            $existingRecognitionIds = DB::table('employee_recognations')
                ->where('employee_id', $id)
                ->pluck('recognation_id')
                ->toArray();
            
            $processedRecognitionIds = [];
            
            foreach ($recognitions as $recognition) {
                // Get the recognition text from either 'details' or 'recognation' field FIRST
                $recognitionText = trim($recognition['details'] ?? $recognition['recognation'] ?? '');
                
                // Skip empty recognitions (only whitespace)
                if (empty($recognitionText)) {
                    continue;
                }
                
                $recognitionId = $recognition['recognition_id'] ?? $recognition['recognation_id'] ?? null;
                
                $payload = [
                    'employee_id' => $id,
                    'recognation' => $recognitionText,
                    'updated_at' => now(),
                ];
                
                if ($recognitionId) {
                    // Update existing recognition
                    DB::table('employee_recognations')
                        ->where('recognation_id', $recognitionId)
                        ->where('employee_id', $id)
                        ->update($payload);
                    $processedRecognitionIds[] = $recognitionId;
                } else {
                    // Insert new recognition
                    $payload['created_at'] = now();
                    $newId = DB::table('employee_recognations')->insertGetId($payload);
                    $processedRecognitionIds[] = $newId;
                }
            }
            
            // Delete recognitions that were removed from the form
            $recognitionsToDelete = array_diff($existingRecognitionIds, $processedRecognitionIds);
            if (!empty($recognitionsToDelete)) {
                DB::table('employee_recognations')
                    ->where('employee_id', $id)
                    ->whereIn('recognation_id', $recognitionsToDelete)
                    ->delete();
            }

            return [
                'success' => true,
                'data' => [
                    'employee_id' => $id,
                    'action' => 'saved',
                    'section' => 'recognitions',
                    'processed' => count($processedRecognitionIds),
                    'deleted' => count($recognitionsToDelete)
                ],
                'message' => 'Recognitions information saved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Recognitions Section Error', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to save recognitions information: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Store skills section for applicants
     */
    private function storeSkillsSection(Request $request, int $id): array
    {
        try {
            // Log incoming request for debugging
            Log::info('ApplicantPdsService - Skills Section Request', [
                'employee_id' => $id,
                'has_skills' => $request->has('skills'),
                'has_skillsText' => $request->has('skillsText'),
                'skillsText_value' => $request->input('skillsText', ''),
            ]);

            // Handle skills data - can be sent as JSON string, array, or single text field
            $skillsInput = $request->input('skills');
            $skillsText = $request->input('skillsText', '');
            
            $skills = [];
            
            // If it's a JSON string (array of skill objects)
            if (is_string($skillsInput) && !empty($skillsInput)) {
                $decodedSkills = json_decode($skillsInput, true) ?? [];
                if (is_array($decodedSkills) && !empty($decodedSkills)) {
                    $skills = $decodedSkills;
                }
            } elseif (is_array($skillsInput) && !empty($skillsInput)) {
                $skills = $skillsInput;
            }
            
            // If skills array is empty but we have skillsText, split by newlines
            if (empty($skills) && !empty($skillsText)) {
                // Split by newlines (handle different line ending formats)
                $lines = preg_split('/\r\n|\r|\n/', trim($skillsText));
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    
                    // Skip empty lines
                    if (empty($line)) {
                        continue;
                    }
                    
                    // Remove bullet points if present (•, -, *, etc.)
                    $line = trim($line);
                    // Remove common bullet characters from start of line
                    $line = ltrim($line, "•-* \t");
                    // Use regex for Unicode bullets
                    $line = preg_replace('/^[\x{2022}\x{2023}\x{25E6}\x{2043}\x{2219}]\s*/u', '', $line);
                    $line = trim($line);
                    
                    // Only add if not empty after cleaning
                    if (!empty($line)) {
                        $skills[] = ['skill' => $line];
                    }
                }
            }
            
            // Also check for array format like PdsService (skill[0], skill[1], etc.)
            if (empty($skills) && $request->has('skill')) {
                $data = $request->all();
                $len = isset($data['skill']) ? count($data['skill']) : 0;
                $skills = [];
                
                for ($i = 0; $i < $len; $i++) {
                    if (!empty($data['skill'][$i])) {
                        $skills[] = [
                            'skill_id' => $data['skill_id'][$i] ?? null,
                            'skill' => $data['skill'][$i],
                        ];
                    }
                }
            }
            
            Log::info('ApplicantPdsService - Processed Skills', [
                'employee_id' => $id,
                'skills_count' => count($skills),
                'skills' => $skills
            ]);

            // Get all existing skill IDs for this employee
            $existingSkillIds = DB::table('employee_skills')
                ->where('employee_id', $id)
                ->pluck('skill_id')
                ->toArray();
            
            $processedSkillIds = [];
            
            // If no skills provided, delete all existing skills
            if (empty($skills)) {
                if (!empty($existingSkillIds)) {
                    DB::table('employee_skills')
                        ->where('employee_id', $id)
                        ->whereIn('skill_id', $existingSkillIds)
                        ->delete();
                }
                
                return [
                    'success' => true,
                    'data' => [
                        'employee_id' => $id,
                        'action' => 'saved',
                        'section' => 'skills',
                        'processed' => 0,
                        'deleted' => count($existingSkillIds)
                    ],
                    'message' => 'Skills information saved successfully'
                ];
            }
            
            foreach ($skills as $skill) {
                // Get the skill text
                $skillText = trim($skill['skill'] ?? $skill['text'] ?? '');
                
                // Skip empty skills
                if (empty($skillText)) {
                    continue;
                }
                
                $skillId = $skill['skill_id'] ?? null;
                
                $payload = [
                    'employee_id' => $id,
                    'skill' => $skillText,
                    'updated_at' => now(),
                ];
                
                if ($skillId) {
                    // Update existing skill
                    DB::table('employee_skills')
                        ->where('skill_id', $skillId)
                        ->where('employee_id', $id)
                        ->update($payload);
                    $processedSkillIds[] = $skillId;
                } else {
                    // Insert new skill
                    $payload['created_at'] = now();
                    $newId = DB::table('employee_skills')->insertGetId($payload);
                    
                    if ($newId) {
                        $processedSkillIds[] = $newId;
                        Log::info('ApplicantPdsService - Inserted new skill', [
                            'employee_id' => $id,
                            'skill_id' => $newId,
                            'skill' => $skillText
                        ]);
                    } else {
                        Log::error('ApplicantPdsService - Failed to insert skill', [
                            'employee_id' => $id,
                            'payload' => $payload
                        ]);
                    }
                }
            }
            
            // Delete skills that were removed from the form
            $skillsToDelete = array_diff($existingSkillIds, $processedSkillIds);
            if (!empty($skillsToDelete)) {
                DB::table('employee_skills')
                    ->where('employee_id', $id)
                    ->whereIn('skill_id', $skillsToDelete)
                    ->delete();
            }

            Log::info('ApplicantPdsService - Skills Save Complete', [
                'employee_id' => $id,
                'processed' => count($processedSkillIds),
                'deleted' => count($skillsToDelete)
            ]);

            return [
                'success' => true,
                'data' => [
                    'employee_id' => $id,
                    'action' => 'saved',
                    'section' => 'skills',
                    'processed' => count($processedSkillIds),
                    'deleted' => count($skillsToDelete)
                ],
                'message' => 'Skills information saved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Skills Section Error', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to save skills information: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Store references section
     */
    private function storeReferencesSection(Request $request, int $id): array
    {
        try {
            Log::info('ApplicantPdsService - References Section Request', [
                'employee_id' => $id,
                'request_data' => $request->all()
            ]);

            // Get references from request (JSON string from FormData)
            $referencesJson = $request->input('references');
            if (empty($referencesJson)) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'No references data provided'
                ];
            }

            $references = json_decode($referencesJson, true);
            if (!is_array($references)) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Invalid references data format'
                ];
            }

            Log::info('ApplicantPdsService - Decoded References', [
                'employee_id' => $id,
                'references_count' => count($references),
                'references' => $references
            ]);

            // Get existing reference IDs for this employee
            $existingReferences = DB::table('employee_references')
                ->where('employee_id', $id)
                ->pluck('reference_id')
                ->toArray();

            $processedReferenceIds = [];

            // Process each reference
            foreach ($references as $reference) {
                $referenceId = $reference['reference_id'] ?? null;
                
                // Map frontend field names to backend field names
                $payload = [
                    'employee_id' => $id,
                    'ref_name' => $reference['name'] ?? '',
                    'ref_address' => $reference['address'] ?? '',
                    'ref_occupation' => $reference['occupation'] ?? '',
                    'ref_contact_no' => $reference['contactNumber'] ?? '',
                    'ref_email' => $reference['email'] ?? '',
                    'updated_at' => now(),
                ];
                
                if ($referenceId) {
                    // Update existing reference
                    DB::table('employee_references')
                        ->where('reference_id', $referenceId)
                        ->where('employee_id', $id)
                        ->update($payload);
                    $processedReferenceIds[] = $referenceId;
                } else {
                    // Insert new reference
                    $payload['created_at'] = now();
                    $newId = DB::table('employee_references')->insertGetId($payload);
                    
                    if ($newId) {
                        $processedReferenceIds[] = $newId;
                        Log::info('ApplicantPdsService - Inserted new reference', [
                            'employee_id' => $id,
                            'reference_id' => $newId,
                            'ref_name' => $payload['ref_name']
                        ]);
                    } else {
                        Log::error('ApplicantPdsService - Failed to insert reference', [
                            'employee_id' => $id,
                            'payload' => $payload
                        ]);
                    }
                }
            }
            
            // Delete references that were removed from the form
            $referencesToDelete = array_diff($existingReferences, $processedReferenceIds);
            if (!empty($referencesToDelete)) {
                DB::table('employee_references')
                    ->where('employee_id', $id)
                    ->whereIn('reference_id', $referencesToDelete)
                    ->delete();
            }

            Log::info('ApplicantPdsService - References Save Complete', [
                'employee_id' => $id,
                'processed' => count($processedReferenceIds),
                'deleted' => count($referencesToDelete)
            ]);

            return [
                'success' => true,
                'data' => [
                    'employee_id' => $id,
                    'action' => 'saved',
                    'section' => 'references',
                    'processed' => count($processedReferenceIds),
                    'deleted' => count($referencesToDelete)
                ],
                'message' => 'References information saved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - References Section Error', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to save references information: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Store dependents section
     */
    private function storeDependentsSection(Request $request, int $id): array
    {
        try {
            Log::info('ApplicantPdsService - Dependents Section Request', [
                'employee_id' => $id,
                'request_data' => $request->all()
            ]);

            // Get dependents from request (JSON string from FormData)
            $dependentsJson = $request->input('dependents');
            if (empty($dependentsJson)) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'No dependents data provided'
                ];
            }

            $dependents = json_decode($dependentsJson, true);
            if (!is_array($dependents)) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Invalid dependents data format'
                ];
            }

            Log::info('ApplicantPdsService - Decoded Dependents', [
                'employee_id' => $id,
                'dependents_count' => count($dependents),
                'dependents' => $dependents
            ]);

            // Get existing dependent IDs for this employee
            $existingDependents = DB::table('employee_dependents')
                ->where('employee_id', $id)
                ->pluck('id')
                ->toArray();

            $processedDependentIds = [];

            // Process each dependent
            foreach ($dependents as $dependent) {
                $dependentId = $dependent['id'] ?? null;
                
                // Map frontend field names to backend field names
                $payload = [
                    'employee_id' => $id,
                    'name' => $dependent['name'] ?? '',
                    'relationship' => $dependent['relationship'] ?? '',
                    'course' => $dependent['courseSought'] ?? '',
                    'updated_at' => now(),
                ];
                
                if ($dependentId) {
                    // Update existing dependent
                    DB::table('employee_dependents')
                        ->where('id', $dependentId)
                        ->where('employee_id', $id)
                        ->update($payload);
                    $processedDependentIds[] = $dependentId;
                } else {
                    // Insert new dependent
                    $payload['created_at'] = now();
                    $newId = DB::table('employee_dependents')->insertGetId($payload);
                    
                    if ($newId) {
                        $processedDependentIds[] = $newId;
                        Log::info('ApplicantPdsService - Inserted new dependent', [
                            'employee_id' => $id,
                            'dependent_id' => $newId,
                            'name' => $payload['name']
                        ]);
                    } else {
                        Log::error('ApplicantPdsService - Failed to insert dependent', [
                            'employee_id' => $id,
                            'payload' => $payload
                        ]);
                    }
                }
            }
            
            // Delete dependents that were removed from the form
            $dependentsToDelete = array_diff($existingDependents, $processedDependentIds);
            if (!empty($dependentsToDelete)) {
                DB::table('employee_dependents')
                    ->where('employee_id', $id)
                    ->whereIn('id', $dependentsToDelete)
                    ->delete();
            }

            Log::info('ApplicantPdsService - Dependents Save Complete', [
                'employee_id' => $id,
                'processed' => count($processedDependentIds),
                'deleted' => count($dependentsToDelete)
            ]);

            return [
                'success' => true,
                'data' => [
                    'employee_id' => $id,
                    'action' => 'saved',
                    'section' => 'dependents',
                    'processed' => count($processedDependentIds),
                    'deleted' => count($dependentsToDelete)
                ],
                'message' => 'Dependents information saved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Dependents Section Error', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to save dependents information: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Store documents section
     */
    private function storeDocumentsSection(Request $request, int $id): array
    {
        try {
            Log::info('ApplicantPdsService - Documents Section Request', [
                'employee_id' => $id,
                'has_files' => $request->hasFile('document'),
                'request_keys' => array_keys($request->all())
            ]);

            // Check if personal information has been saved first
            // $id is employee_id, so we need to check both employees and applicant_headers tables
            $hasPersonalInfo = false;
            
            if ($id > 0) {
                // If employee_id exists, check employees table for personal info
                $employee = DB::table('employees')
                    ->where('id', $id)
                    ->first(['first_name', 'last_name', 'employee_no']);
                
                if ($employee && !empty(trim($employee->first_name ?? '')) && !empty(trim($employee->last_name ?? ''))) {
                    $hasPersonalInfo = true;
                } else {
                    // Also check applicant_headers via employee_no
                    if ($employee && $employee->employee_no) {
                        $applicant = DB::table('applicant_headers')
                            ->where('applicant_no', $employee->employee_no)
                            ->where('user_id', Auth::id())
                            ->first(['first_name', 'last_name']);
                        
                        if ($applicant && !empty(trim($applicant->first_name ?? '')) && !empty(trim($applicant->last_name ?? ''))) {
                            $hasPersonalInfo = true;
                        }
                    }
                }
            } else {
                // If employee_id is 0, check applicant_headers directly from authenticated user
                $applicant = DB::table('applicant_headers')
                    ->where('user_id', Auth::id())
                    ->first(['first_name', 'last_name']);
                
                if ($applicant && !empty(trim($applicant->first_name ?? '')) && !empty(trim($applicant->last_name ?? ''))) {
                    $hasPersonalInfo = true;
                }
            }

            // Only block if personal information is truly missing
            if (!$hasPersonalInfo) {
                return [
                    'success' => false,
                    'data' => [
                        'employee_id' => $id,
                        'action' => 'error',
                        'section' => 'documents',
                    ],
                    'message' => 'Please save your Personal Information first before uploading documents. Go to Personal Information section and save your details.'
                ];
            }

            // Get document data from request
            $documentNames = $request->input('document_name', []);
            $documentDescriptions = $request->input('document_description', []);
            $documentTypeIds = $request->input('document_type_id', []);
            $documentIds = $request->input('document_id', []);

            // Handle array inputs - convert to array if single value
            if (!is_array($documentNames)) {
                $documentNames = [$documentNames];
            }
            if (!is_array($documentDescriptions)) {
                $documentDescriptions = [$documentDescriptions];
            }
            if (!is_array($documentTypeIds)) {
                $documentTypeIds = [$documentTypeIds];
            }
            if (!is_array($documentIds)) {
                $documentIds = [$documentIds];
            }

            $processedDocumentIds = [];
            $files = $request->hasFile('document') ? $request->file('document') : [];

            // Handle array of files - convert to array if single file
            if (!is_array($files)) {
                $files = [$files];
            }

            // Validate maximum 5 documents can be uploaded at once
            $newFileCount = count(array_filter($files, function($file) {
                return $file !== null;
            }));
            if ($newFileCount > 5) {
                return [
                    'success' => false,
                    'data' => [
                        'employee_id' => $id,
                        'action' => 'error',
                        'section' => 'documents',
                    ],
                    'message' => 'You can upload a maximum of 5 documents at once. Please select 5 or fewer documents.'
                ];
            }

            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
            $maxSize = 10 * 1024 * 1024; // 10MB

            // Process each document
            for ($i = 0; $i < count($documentNames); $i++) {
                $documentName = $documentNames[$i] ?? null;
                $documentDescription = $documentDescriptions[$i] ?? '';
                $documentTypeId = isset($documentTypeIds[$i]) && !empty($documentTypeIds[$i]) ? (int)$documentTypeIds[$i] : 0;
                $documentId = isset($documentIds[$i]) && !empty($documentIds[$i]) ? $documentIds[$i] : null;
                $file = isset($files[$i]) ? $files[$i] : null;

                if (!$documentName) {
                    continue;
                }

                // If updating existing document without new file, just update metadata
                if ($documentId && !$file) {
                    $payload = [
                        'employee_id' => $id,
                        'name' => $documentName,
                        'document_type_id' => $documentTypeId,
                        'description' => $documentDescription,
                        'updated_at' => now(),
                    ];

                    DB::connection('attachments')
                        ->table('employee_documents')
                        ->where('employee_document_id', $documentId)
                        ->where('employee_id', $id)
                        ->update($payload);
                    
                    $processedDocumentIds[] = $documentId;
                    continue;
                }

                // If no file provided, skip
                if (!$file) {
                    continue;
                }

                // Validate file
                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    Log::warning('ApplicantPdsService - Invalid file extension', [
                        'employee_id' => $id,
                        'extension' => $extension,
                        'filename' => $file->getClientOriginalName()
                    ]);
                    continue;
                }

                if ($file->getSize() > $maxSize) {
                    Log::warning('ApplicantPdsService - File too large', [
                        'employee_id' => $id,
                        'size' => $file->getSize(),
                        'filename' => $file->getClientOriginalName()
                    ]);
                    continue;
                }

                $fileName = $file->getClientOriginalName();
                $storageFileName = 'DOCS' . $id . '_' . $fileName;
                $filePath = storage_path('app/employee_documents/' . $storageFileName);

                // Read file content and encode as base64 for attachments database
                $fileContent = file_get_contents($file->getRealPath());
                $encodedContent = base64_encode($fileContent);
                $fileSize = $file->getSize();
                $fileType = $file->getClientMimeType();

                // Attachments DB payload (this is now the source of truth)
                $attachmentsPayloadBase = [
                    'employee_id' => $id,
                    'name' => $documentName,
                    'document_type_id' => $documentTypeId,
                    'description' => $documentDescription,
                    'attachment_name' => $fileName,
                    'extension' => $extension,
                    // Optional metadata for convenience / parity with main DB
                    'path' => $filePath,
                    'file_content' => $encodedContent,
                    'file_size' => $fileSize,
                    'file_type' => $fileType,
                    'updated_at' => now(),
                ];

                if ($documentId) {
                    // Update existing document in attachments DB only
                    DB::connection('attachments')
                        ->table('employee_documents')
                        ->where('employee_document_id', $documentId)
                        ->where('employee_id', $id)
                        ->update($attachmentsPayloadBase);
                    $processedDocumentIds[] = $documentId;
                } else {
                    // Insert new document in attachments DB only (ID is generated by identity)
                    $attachmentsPayload = $attachmentsPayloadBase;
                    $attachmentsPayload['created_at'] = now();
                    $newId = DB::connection('attachments')
                        ->table('employee_documents')
                        ->insertGetId($attachmentsPayload);
                    
                    if ($newId) {
                        $processedDocumentIds[] = $newId;

                        Log::info('ApplicantPdsService - Inserted new document', [
                            'employee_id' => $id,
                            'document_id' => $newId,
                            'filename' => $fileName
                        ]);
                    }
                }

                // Store the file on disk (optional backup)
                $file->storeAs('employee_documents', $storageFileName);
            }

            Log::info('ApplicantPdsService - Documents Save Complete', [
                'employee_id' => $id,
                'processed' => count($processedDocumentIds)
            ]);

            return [
                'success' => true,
                'data' => [
                    'employee_id' => $id,
                    'action' => 'saved',
                    'section' => 'documents',
                    'processed' => count($processedDocumentIds)
                ],
                'message' => 'Documents information saved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Documents Section Error', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to save documents information: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a record by type_id and id
     */
    public function destroyRecord(int $type_id, int $id): array
    {
        try {
            $tableMap = [
                1 => 'employee_educations',
                2 => 'employee_employments',
                3 => 'employee_eligibilities',
                6 => 'employee_trainings', // type_id 6 for trainings (matching ApplicantsController)
                7 => 'employee_organizations', // type_id 7 for organizations/voluntary work
                8 => 'employee_recognations', // type_id 8 for recognitions
                9 => 'employee_skills', // type_id 9 for skills
                11 => 'employee_references', // type_id 11 for references
                12 => 'employee_dependents', // type_id 12 for dependents
                13 => 'employee_documents', // type_id 13 for documents
                14 => 'Work_Experience', // type_id 14 for Work_Experience table
            ];
            
            $idColumnMap = [
                1 => 'education_id',
                2 => 'employment_record_id',
                3 => 'examination_id',
                6 => 'training_id',
                7 => 'organization_id',
                8 => 'recognation_id',
                9 => 'skill_id',
                11 => 'reference_id',
                12 => 'id',
                13 => 'employee_document_id',
                14 => 'id', // Work_Experience primary key
            ];

            if (!isset($tableMap[$type_id])) {
                throw new \Exception('Invalid type_id provided');
            }

            $table = $tableMap[$type_id];
            $idColumn = $idColumnMap[$type_id] ?? 'id';
            // Attachments DB is the source of truth for employee_documents (type_id 13)
            if ($type_id === 13) {
                $deleted = DB::connection('attachments')->table($table)->where($idColumn, $id)->delete();
            } else {
                $deleted = DB::table($table)->where($idColumn, $id)->delete();
            }

            if ($deleted) {
                return [
                    'success' => true,
                    'data' => ['id' => $id, 'deleted' => $deleted],
                    'message' => 'Record deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Record not found or already deleted'
                ];
            }

        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Delete Error', [
                'type_id' => $type_id,
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to delete record: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get PDS questions with answers for an employee
     * 
     * @param int $id Employee ID
     * @return array
     */
    public function getQuestions(int $id): array
    {
        try {
            // Get all questions
            $questions = DB::table('pds_questionaires')
                ->orderBy('id', 'asc')
                ->get();

            // Get existing answers
            $answers = DB::table('employee_pds_answers')
                ->where('employee_id', $id)
                ->get()
                ->keyBy('question_id');

            // Merge questions with answers
            $questionsWithAnswers = $questions->map(function ($question) use ($answers) {
                $answer = $answers->get($question->id);
                return [
                    'id' => $question->id,
                    'code' => $question->code,
                    'que_id' => $question->que_id,
                    'questions' => $question->questions,
                    'is_yes' => $answer ? (bool)$answer->is_yes : false,
                    'is_no' => $answer ? (bool)$answer->is_no : false,
                    'yes_details' => $answer ? $answer->yes_details : null,
                    'date_filed' => $answer ? $answer->date_filed : null,
                    'case_status' => $answer ? $answer->case_status : null,
                ];
            });

            return [
                'success' => true,
                'data' => $questionsWithAnswers,
                'message' => 'Questions retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Get Questions Error', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => [],
                'message' => 'Failed to retrieve questions: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Store PDS questionnaire answers
     * 
     * @param Request $request
     * @param int $id Employee ID
     * @return array
     */
    public function storeQuestionnaire(Request $request, int $id): array
    {
        try {
            $data = $request->all();
            
            // Get question IDs and answers
            $questionIds = $request->input('question_id', []);
            $isYes = $request->input('is_yes', []);
            $isNo = $request->input('is_no', []);
            $yesDetails = $request->input('yes_details', []);
            $dateFiled = $request->input('date_filed', []);
            $caseStatus = $request->input('case_status', []);

            // Ensure arrays
            if (!is_array($questionIds)) {
                $questionIds = [$questionIds];
            }
            if (!is_array($isYes)) {
                $isYes = [];
            }
            if (!is_array($isNo)) {
                $isNo = [];
            }
            if (!is_array($yesDetails)) {
                $yesDetails = [];
            }
            if (!is_array($dateFiled)) {
                $dateFiled = [];
            }
            if (!is_array($caseStatus)) {
                $caseStatus = [];
            }

            // Delete existing answers
            DB::table('employee_pds_answers')
                ->where('employee_id', $id)
                ->delete();

            // Insert new answers
            $processed = 0;
            foreach ($questionIds as $index => $questionId) {
                if ($questionId === null || $questionId === '') {
                    continue;
                }

                $payload = [
                    'employee_id' => $id,
                    'question_id' => $questionId,
                    'is_yes' => isset($isYes[$questionId]) ? 1 : 0,
                    'is_no' => isset($isNo[$questionId]) ? 1 : 0,
                    'yes_details' => $yesDetails[$index] ?? null,
                    'date_filed' => !empty($dateFiled[$index]) ? $dateFiled[$index] : null,
                    'case_status' => $caseStatus[$index] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                DB::table('employee_pds_answers')->insert($payload);
                $processed++;
            }

            Log::info('ApplicantPdsService - Questionnaire Saved', [
                'employee_id' => $id,
                'processed' => $processed
            ]);

            return [
                'success' => true,
                'data' => [
                    'employee_id' => $id,
                    'processed' => $processed
                ],
                'message' => 'Questionnaire answers saved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ApplicantPdsService - Store Questionnaire Error', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to save questionnaire answers: ' . $e->getMessage()
            ];
        }
    }
}
