<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\CustomEncryptionService;
use App\Helpers\EncryptionHelper;
use App\Traits\ApiResponse;

class PdsDisplayController extends Controller
{
    use ApiResponse;
    protected $encryptionService;
    
    public function __construct(CustomEncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    public function getPdsDisplayData($applicant_no)
    {
        try {
            $applicant = DB::table('applicant_headers')
                ->where('applicant_no', $applicant_no)
                ->first(['id', 'photo', 'user_id']);
            
            $employee = DB::table('employees')
                ->where('employee_no', $applicant_no)
                ->first();

            $pds = null;

            if ($employee) {
                $photo = (!empty($applicant->photo)) ? $applicant->photo : ($employee->photo ?? '');
                
                $pds = [
                    'id' => $employee->id,
                    'employee_no' => $employee->employee_no,
                    'photo' => $photo,
                    'access_no' => $employee->access_no,
                    'name_prefix_id' => $employee->name_prefix_id,
                    'first_name' => $employee->first_name,
                    'middle_name' => $employee->middle_name,
                    'last_name' => $employee->last_name,
                    'name_suffix_id' => $employee->name_suffix_id,
                    'birth_place' => $employee->birth_place,
                    'birthdate' => $employee->birthdate,
                    'age' => $employee->age,
                    'gender_id' => $employee->gender_id,
                    'height' => $employee->height,
                    'blood_type_id' => $employee->blood_type_id,
                    'weight' => $employee->weight,
                    'email' => $employee->email,
                    'mobile_no' => $employee->mobile_no,
                    'telephone_no' => $employee->telephone_no,
                    'citizenship_id' => $employee->citizenship_id,
                    'civil_status_id' => $employee->civil_status_id,
                    'religion_id' => $employee->religion_id,
                    'is_dual_citizent' => $employee->is_dual_citizent,
                    'by_birth' => $employee->by_birth,
                    'by_naturalization' => $employee->by_naturalization,
                    'indicate_country' => $employee->indicate_country,
                    'ra_region' => $employee->ra_region,
                    'ra_province' => $employee->ra_province,
                    'ra_city' => $employee->ra_city,
                    'ra_barangay' => $employee->ra_barangay,
                    'ra_house_no' => $employee->ra_house_no,
                    'ra_street' => $employee->ra_street,
                    'ra_village' => $employee->ra_village,
                    'pa_region' => $employee->pa_region,
                    'pa_province' => $employee->pa_province,
                    'pa_city' => $employee->pa_city,
                    'pa_barangay' => $employee->pa_barangay,
                    'pa_house_no' => $employee->pa_house_no,
                    'pa_street' => $employee->pa_street,
                    'pa_village' => $employee->pa_village,
                    'tin_no' => $employee->tin_no,
                    'gsis_no' => $employee->gsis_no,
                    'sss_no' => $employee->sss_no,
                    'pagibig_no' => $employee->pagibig_no,
                    'philhealth_no' => $employee->philhealth_no,
                    // FAMILY INFO
                    'father_name_prefix_id' => $employee->father_name_prefix_id ?? 0,
                    'father_first_name' => $employee->father_first_name ?? '',
                    'father_middle_name' => $employee->father_middle_name ?? '',
                    'father_last_name' => $employee->father_last_name ?? '',
                    'father_name_suffix_id' => $employee->father_name_suffix_id ?? 0,
                    'mother_name_prefix_id' => $employee->mother_name_prefix_id ?? 0,
                    'mother_first_name' => $employee->mother_first_name ?? '',
                    'mother_middle_name' => $employee->mother_middle_name ?? '',
                    'mother_last_name' => $employee->mother_last_name ?? '',
                    'mother_name_suffix_id' => $employee->mother_name_suffix_id ?? 0,
                    'spouse_name_prefix_id' => $employee->spouse_name_prefix_id ?? 0,
                    'spouse_first_name' => $employee->spouse_first_name ?? '',
                    'spouse_middle_name' => $employee->spouse_middle_name ?? '',
                    'spouse_last_name' => $employee->spouse_last_name ?? '',
                    'spouse_name_suffix_id' => $employee->spouse_name_suffix_id ?? 0,
                    'spouse_occupation' => $employee->spouse_occupation ?? '',
                    'spouse_employer' => $employee->spouse_employer ?? '',
                    'spouse_business_address' => $employee->spouse_business_address ?? '',
                ];

                $children = DB::table('employee_children')
                    ->where('employee_id', $employee->id)
                    ->get()
                    ->map(function($child) {
                        return [
                            'children_id' => $child->children_id ?? null,
                            'first_name' => $child->child_name ?? '',
                            'middle_name' => $child->child_middlename ?? '',
                            'last_name' => $child->child_lastname ?? '',
                            'birthdate' => $child->child_birthdate ?? '',
                            'gender' => $child->child_gender_id ?? 0,
                        ];
                    })
                    ->toArray();

                $pds['children'] = $children;

                try {
                    if (!empty($pds['father_first_name'])) {
                        $pds['father_first_name'] = EncryptionHelper::decrypt($pds['father_first_name']);
                    }
                    if (!empty($pds['father_middle_name'])) {
                        $pds['father_middle_name'] = EncryptionHelper::decrypt($pds['father_middle_name']);
                    }
                    if (!empty($pds['father_last_name'])) {
                        $pds['father_last_name'] = EncryptionHelper::decrypt($pds['father_last_name']);
                    }
                    if (!empty($pds['mother_first_name'])) {
                        $pds['mother_first_name'] = EncryptionHelper::decrypt($pds['mother_first_name']);
                    }
                    if (!empty($pds['mother_middle_name'])) {
                        $pds['mother_middle_name'] = EncryptionHelper::decrypt($pds['mother_middle_name']);
                    }
                    if (!empty($pds['mother_last_name'])) {
                        $pds['mother_last_name'] = EncryptionHelper::decrypt($pds['mother_last_name']);
                    }
                    if (!empty($pds['spouse_first_name'])) {
                        $pds['spouse_first_name'] = EncryptionHelper::decrypt($pds['spouse_first_name']);
                    }
                    if (!empty($pds['spouse_middle_name'])) {
                        $pds['spouse_middle_name'] = EncryptionHelper::decrypt($pds['spouse_middle_name']);
                    }
                    if (!empty($pds['spouse_last_name'])) {
                        $pds['spouse_last_name'] = EncryptionHelper::decrypt($pds['spouse_last_name']);
                    }
                } catch (\Exception $e) {
                }
            } else {
                $applicant = DB::table('applicant_headers')
                    ->where('applicant_no', $applicant_no)
                    ->first();

                if ($applicant) {
                    $employeeFromApplicant = DB::table('employees')
                        ->where('employee_no', $applicant->applicant_no)
                        ->first();
                    
                    $pds = [
                        'id' => $employeeFromApplicant ? $employeeFromApplicant->id : 0,
                        'employee_no' => $applicant->applicant_no,
                        'photo' => $applicant->photo ?? ($employeeFromApplicant->photo ?? ''),
                        'access_no' => $applicant->access_no ?? ($employeeFromApplicant->access_no ?? ''),
                        'name_prefix_id' => $applicant->name_prefix_id ?? 0,
                        'first_name' => $applicant->first_name ?? '',
                        'middle_name' => $applicant->middle_name ?? '',
                        'last_name' => $applicant->last_name ?? '',
                        'name_suffix_id' => $applicant->name_suffix_id ?? 0,
                        'birth_place' => $applicant->birth_place ?? '',
                        'birthdate' => $applicant->birth_date ?? '',
                        'age' => $applicant->age ?? '',
                        'gender_id' => $applicant->gender ?? 0,
                        'height' => $applicant->height ?? 0,
                        'blood_type_id' => $applicant->blood_type_id ?? 0,
                        'weight' => $applicant->weight ?? 0,
                        'email' => $applicant->email ?? '',
                        'mobile_no' => $applicant->mobile_no ?? '',
                        'telephone_no' => $applicant->telephone_no ?? '',
                        'citizenship_id' => $applicant->citizenship_id ?? 0,
                        'civil_status_id' => $applicant->civil_status_id ?? 0,
                        'religion_id' => $applicant->religion_id ?? 0,
                        'is_dual_citizent' => false,
                        'by_birth' => $applicant->by_birth ?? false,
                        'by_naturalization' => $applicant->by_naturalization ?? false,
                        'indicate_country' => $applicant->indicate_country ?? '',
                        'ra_region' => $applicant->ra_region ?? '',
                        'ra_province' => $applicant->ra_province ?? '',
                        'ra_city' => $applicant->ra_city ?? '',
                        'ra_barangay' => $applicant->ra_barangay ?? '',
                        'ra_house_no' => $applicant->ra_house_no ?? '',
                        'ra_street' => $applicant->ra_street ?? '',
                        'ra_village' => $applicant->ra_village ?? '',
                        'pa_region' => $applicant->pa_region ?? '',
                        'pa_province' => $applicant->pa_province ?? '',
                        'pa_city' => $applicant->pa_city ?? '',
                        'pa_barangay' => $applicant->pa_barangay ?? '',
                        'pa_house_no' => $applicant->pa_house_no ?? '',
                        'pa_street' => $applicant->pa_street ?? '',
                        'pa_village' => $applicant->pa_village ?? '',
                        'tin_no' => $applicant->tin_no ?? '',
                        'gsis_no' => $applicant->gsis_no ?? '',
                        'sss_no' => $applicant->sss_no ?? '',
                        'pagibig_no' => $applicant->pagibig_no ?? '',
                        'philhealth_no' => $applicant->philhealth_no ?? '',
                        'father_name_prefix_id' => $applicant->father_name_prefix_id ?? 0,
                        'father_first_name' => $applicant->father_first_name ?? '',
                        'father_middle_name' => $applicant->father_middle_name ?? '',
                        'father_last_name' => $applicant->father_last_name ?? '',
                        'father_name_suffix_id' => $applicant->father_name_suffix_id ?? 0,
                        'mother_name_prefix_id' => $applicant->mother_name_prefix_id ?? 0,
                        'mother_first_name' => $applicant->mother_first_name ?? '',
                        'mother_middle_name' => $applicant->mother_middle_name ?? '',
                        'mother_last_name' => $applicant->mother_last_name ?? '',
                        'mother_name_suffix_id' => $applicant->mother_name_suffix_id ?? 0,
                        'spouse_name_prefix_id' => $applicant->spouse_name_prefix_id ?? 0,
                        'spouse_first_name' => $applicant->spouse_first_name ?? '',
                        'spouse_middle_name' => $applicant->spouse_middle_name ?? '',
                        'spouse_last_name' => $applicant->spouse_last_name ?? '',
                        'spouse_name_suffix_id' => $applicant->spouse_name_suffix_id ?? 0,
                        'spouse_occupation' => $applicant->spouse_occupation ?? '',
                        'spouse_employer' => $applicant->spouse_employer ?? '',
                        'spouse_business_address' => $applicant->spouse_business_address ?? '',
                    ];

                    if ($employeeFromApplicant) {
                        $children = DB::table('employee_children')
                            ->where('employee_id', $employeeFromApplicant->id)
                            ->get()
                            ->map(function($child) {
                                return [
                                    'children_id' => $child->children_id ?? null,
                                    'first_name' => $child->child_name ?? '',
                                    'middle_name' => $child->child_middlename ?? '',
                                    'last_name' => $child->child_lastname ?? '',
                                    'gender' => $child->child_gender_id ?? 0,
                                    'birthdate' => $child->child_birthdate ?? '',
                                ];
                            })
                            ->toArray();

                        $pds['children'] = $children;

                        // Decrypt family fields if encrypted
                        try {
                            if (!empty($pds['father_first_name'])) {
                                $pds['father_first_name'] = EncryptionHelper::decrypt($pds['father_first_name']);
                            }
                            if (!empty($pds['father_middle_name'])) {
                                $pds['father_middle_name'] = EncryptionHelper::decrypt($pds['father_middle_name']);
                            }
                            if (!empty($pds['father_last_name'])) {
                                $pds['father_last_name'] = EncryptionHelper::decrypt($pds['father_last_name']);
                            }
                            if (!empty($pds['mother_first_name'])) {
                                $pds['mother_first_name'] = EncryptionHelper::decrypt($pds['mother_first_name']);
                            }
                            if (!empty($pds['mother_middle_name'])) {
                                $pds['mother_middle_name'] = EncryptionHelper::decrypt($pds['mother_middle_name']);
                            }
                            if (!empty($pds['mother_last_name'])) {
                                $pds['mother_last_name'] = EncryptionHelper::decrypt($pds['mother_last_name']);
                            }
                            if (!empty($pds['spouse_first_name'])) {
                                $pds['spouse_first_name'] = EncryptionHelper::decrypt($pds['spouse_first_name']);
                            }
                            if (!empty($pds['spouse_middle_name'])) {
                                $pds['spouse_middle_name'] = EncryptionHelper::decrypt($pds['spouse_middle_name']);
                            }
                            if (!empty($pds['spouse_last_name'])) {
                                $pds['spouse_last_name'] = EncryptionHelper::decrypt($pds['spouse_last_name']);
                            }
                        } catch (\Exception $e) {

                        }
                    } else {
                        $pds['children'] = [];
                    }
                }
            }

            if (!$pds) {
                return $this->errorResponse('No PDS data found for this applicant', 404);
            }

            return $this->successResponse($pds, 'Personal Information retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve PDS data: ' . $e->getMessage());
        }
    }

    public function updatePersonalInfo(Request $request, $employee_no) {
        
        $request->validate([
            'first_name' => 'required|string|max:20',
            'last_name' => 'required|string|max:20',
            'middle_name' => 'nullable|string|max:20',
            'suffix' => 'required|integer',
            'prefix' => 'required|integer',
            'civil_status' => 'required|integer',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:30',
            'nationality' => 'required|integer',
            'religion' => 'nullable|integer',
            'gender' => 'required|integer',
            'age' => 'required|integer',
            'height' => 'required|numeric|between:0,999.99',
            'blood_type' => 'required|integer',
            'weight' => 'required|numeric|between:0,999.99',
            
            'email' => 'required|email|max:500',           
            'mobile_no' => 'nullable|string|max:500',      
            'telephone_no' => 'nullable|string|max:500',   
            'tin_no' => 'nullable|string|max:50',
            'gsis_no' => 'nullable|string|max:50',
            'sss_no' => 'nullable|string|max:50',
            'pagibig_no' => 'nullable|string|max:50',
            'philhealth_no' => 'nullable|string|max:50',
            'ra_region' => 'required|string|max:50',
            'ra_province' => 'required|string|max:50',
            'ra_city' => 'required|string|max:50',
            'ra_barangay' => 'required|string|max:50',
            'ra_house_no' => 'nullable|string|max:50',
            'ra_street' => 'required|string|max:50',
            'ra_village' => 'required|string|max:50',
            'pa_region' => 'required|string|max:50',
            'pa_province' => 'required|string|max:50',
            'pa_city' => 'required|string|max:50',
            'pa_barangay' => 'required|string|max:50',
            'pa_house_no' => 'nullable|string|max:50',
            'pa_street' => 'required|string|max:50',
            'pa_village' => 'required|string|max:50',
            'is_dual_citizen' => 'nullable|boolean',
            'by_birth' => 'nullable|boolean',
            'by_naturalization' => 'nullable|boolean',
            'indicate_country' => 'nullable|string|max:20',
        ]);
    
        try {

            $updateData = [
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name ?? '',
                'last_name' => $request->last_name,
                'name_suffix_id' => $request->suffix,
                'name_prefix_id' => $request->prefix,
                'birth_place' => $request->birth_place,
                'birthdate' => $request->birth_date,
                'age' => $request->age,
                'gender_id' => $request->gender,
                'height' => $request->height,
                'weight' => $request->weight,
                'email' => $request->email,
                'mobile_no' => $request->mobile_no,
                'telephone_no' => $request->telephone_no,
                'citizenship_id' => $request->nationality,
                'civil_status_id' => $request->civil_status,
                'religion_id' => $request->religion,
                'tin_no' => $request->tin_no,
                'gsis_no' => $request->gsis_no,
                'sss_no' => $request->sss_no,
                'pagibig_no' => $request->pagibig_no,
                'philhealth_no' => $request->philhealth_no,
                'ra_region' => $request->ra_region,
                'ra_province' => $request->ra_province,
                'ra_city' => $request->ra_city,
                'ra_barangay' => $request->ra_barangay,
                'ra_house_no' => $request->ra_house_no,
                'ra_street' => $request->ra_street,
                'ra_village' => $request->ra_village,
                'pa_region' => $request->pa_region,
                'pa_province' => $request->pa_province,
                'pa_city' => $request->pa_city,
                'pa_barangay' => $request->pa_barangay,
                'pa_house_no' => $request->pa_house_no,
                'pa_street' => $request->pa_street,
                'pa_village' => $request->pa_village,
                'is_dual_citizent' => $request->is_dual_citizen ?? false,
                'by_birth' => $request->by_birth ?? false,
                'by_naturalization' => $request->by_naturalization ?? false,
                'indicate_country' => $request->indicate_country,
                'updated_at' => now(),
                'is_encrypted' => 0,
                'tin_no' => $request->tin_no,
                'gsis_no' => $request->gsis_no,
                'sss_no' => $request->sss_no,
                'pagibig_no' => $request->pagibig_no,
                'philhealth_no' => $request->philhealth_no,
            ];

            $upsertDefaults = [
                'employee_no' => $employee_no,
                'access_no' => random_int(100000, 999999),
                'application_status_id' => 1,
                'is_plantilla' => false,
                'is_employee' => false,
                'active' => true,
                'date_applied' => now(),
            ];

            $upsertData = array_merge($upsertDefaults, $updateData);

            $upserted = DB::table('employees')->updateOrInsert(
                ['employee_no' => $employee_no],
                $upsertData
            );

            if ($upserted === false) {
                return $this->errorResponse('No record updated. Check employee number.', 404);
            }

            return $this->successResponse([], 'Personal Information updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update personal information: ' . $e->getMessage());
        }
    }
    
    
}
