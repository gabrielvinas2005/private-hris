<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ApplicantPortalService
{
    public function getApplicantPageData(int $userId): array
    {
        $appKey = env('APP_KEY', '');

        $applicant = DB::table('applicant_headers as a')
            ->join('genders as b', 'a.gender', '=', 'b.id')
            ->select('a.id', 'a.applicant_no', 'a.photo', 'a.first_name', 'a.middle_name', 'a.last_name', 'a.gender', 'a.birth_date', 'a.age', 'a.address', 'a.mobile_no', 'a.email', 'a.resume', 'a.application_status_id', 'a.application_date', 'a.user_id', 'b.name as gender')
            ->where('user_id', $userId)
            ->get();

        // Check if applicant exists before trying to get employee data
        $employee_data = collect();
        if ($applicant->isNotEmpty() && !empty($applicant[0]->applicant_no)) {
            $employee_data = DB::table('employees as a')
                ->select(
                    'a.id',
                    'a.photo',
                    'a.employee_no',
                    'a.access_no',
                    'a.name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$appKey') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$appKey') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$appKey') END as last_name"),
                    'a.name_suffix_id',
                    'a.birth_place',
                    'a.birthdate',
                    'a.age',
                    'a.gender_id',
                    'a.height',
                    'a.weight',
                    'a.blood_type_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.email ELSE dbo.ufn_DecryptString(a.email,'$appKey') END as email"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.mobile_no ELSE dbo.ufn_DecryptString(a.mobile_no,'$appKey') END as mobile_no"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.telephone_no ELSE dbo.ufn_DecryptString(a.telephone_no,'$appKey') END as telephone_no"),
                    'a.citizenship_id',
                    'a.civil_status_id',
                    'a.religion_id',
                    'a.ra_region',
                    'a.ra_province',
                    'a.ra_city',
                    'a.ra_barangay',
                    'a.ra_house_no',
                    'a.ra_street',
                    'a.ra_village',
                    'a.pa_region',
                    'a.pa_province',
                    'a.pa_city',
                    'a.pa_barangay',
                    'a.pa_house_no',
                    'a.pa_street',
                    'a.pa_village',
                    'a.father_name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.father_first_name ELSE dbo.ufn_DecryptString(a.father_first_name,'$appKey') END as father_first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.father_middle_name ELSE dbo.ufn_DecryptString(a.father_middle_name,'$appKey') END as father_middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.father_last_name ELSE dbo.ufn_DecryptString(a.father_last_name,'$appKey') END as father_last_name"),
                    'a.father_name_suffix_id',
                    'a.mother_name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.mother_first_name ELSE dbo.ufn_DecryptString(a.mother_first_name,'$appKey') END as mother_first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.mother_middle_name ELSE dbo.ufn_DecryptString(a.mother_middle_name,'$appKey') END as mother_middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.mother_last_name ELSE dbo.ufn_DecryptString(a.mother_last_name,'$appKey') END as mother_last_name"),
                    'a.mother_name_suffix_id',
                    'a.spouse_name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.spouse_first_name ELSE dbo.ufn_DecryptString(a.spouse_first_name,'$appKey') END as spouse_first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.spouse_middle_name ELSE dbo.ufn_DecryptString(a.spouse_middle_name,'$appKey') END as spouse_middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.spouse_last_name ELSE dbo.ufn_DecryptString(a.spouse_last_name,'$appKey') END as spouse_last_name"),
                    'a.spouse_name_suffix_id',
                    'a.spouse_occupation',
                    'a.spouse_employer',
                    'a.spouse_business_address'
                )
                ->where(['a.employee_no' => $applicant[0]->applicant_no])
                ->get();
        }

        if ($employee_data->isEmpty()) {
            $id = 0;
            $pds_data = null;
            $dummy_employee_info = [
                'id' => 0,
                'photo' => '',
                'employee_no' => '',
                'access_no' => '',
                'name_prefix_id' => 0,
                'first_name' => '',
                'middle_name' => '',
                'last_name' => '',
                'name_suffix_id' => 0,
                'birth_place' => '',
                'birthdate' => '',
                'age' => '',
                'gender_id' => 0,
                'height' => '',
                'weight' => '',
                'blood_type_id' => 0,
                'email' => '',
                'mobile_no' => '',
                'telephone_no' => '',
                'citizenship_id' => 0,
                'civil_status_id' => 0,
                'religion_id' => 0,
                'is_dual_citizent' => false,
                'by_birth' => false,
                'by_naturalization' => false,
                'indicate_country' => '',
                'ra_region' => '',
                'ra_province' => '',
                'ra_city' => '',
                'ra_house_no' => '',
                'ra_barangay' => '',
                'ra_street' => '',
                'ra_village' => '',
                'pa_region' => '',
                'pa_province' => '',
                'pa_city' => '',
                'pa_house_no' => '',
                'pa_barangay' => '',
                'pa_street' => '',
                'pa_village' => '',
                'father_name_prefix_id' => 0,
                'father_first_name' => '',
                'father_middle_name' => '',
                'father_last_name' => '',
                'father_name_suffix_id' => 0,
                'mother_name_prefix_id' => '',
                'mother_first_name' => '',
                'mother_middle_name' => '',
                'mother_last_name' => '',
                'mother_name_suffix_id' => 0,
                'spouse_name_prefix_id' => 0,
                'spouse_first_name' => '',
                'spouse_middle_name' => '',
                'spouse_last_name' => '',
                'spouse_name_suffix_id' => 0,
                'spouse_occupation' => '',
                'spouse_employer' => '',
                'spouse_business_address' => '',
                'company_id' => 1,
                'branch_id' => 0,
                'department_id' => 0,
                'division_id' => 0,
                'section_id' => 0,
                'employment_type_id' => 0,
                'position_id' => 0,
                'plantilla_id' => 0,
                'is_plantilla' => false,
                'is_employee' => true,
                'is_teaching' => false,
                'date_hired' => '',
                'tin_no' => '',
                'gsis_no' => '',
                'sss_no' => '',
                'pagibig_no' => '',
                'philhealth_no' => '',
                'salary' => '0',
                'tax_amount' => '',
                'gsis_amount' => '',
                'sss_amount' => '',
                'pagibig_amount' => '',
                'philhealth_amount' => '',
                'payroll_interval_id' => 0,
                'end_date' => '',
                'account_no' => ''
            ];
            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', 0)->get();
            $employee_info = collect([(object) $dummy_employee_info]);
        } else {
            $id = $employee_data[0]->id;
            $pds_data = $employee_data[0];
            $employee_info = DB::table('employees')
                ->select(
                    'id',
                    'photo',
                    'employee_no',
                    'access_no',
                    'name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN first_name ELSE dbo.ufn_DecryptString(first_name,'$appKey') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN middle_name ELSE dbo.ufn_DecryptString(middle_name,'$appKey') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN last_name ELSE dbo.ufn_DecryptString(last_name,'$appKey') END as last_name"),
                    'name_suffix_id',
                    'birth_place',
                    'birthdate',
                    'age',
                    'gender_id',
                    'height',
                    'weight',
                    'blood_type_id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN email ELSE dbo.ufn_DecryptString(email,'$appKey') END as email"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mobile_no ELSE dbo.ufn_DecryptString(mobile_no,'$appKey') END as mobile_no"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN telephone_no ELSE dbo.ufn_DecryptString(telephone_no,'$appKey') END as telephone_no"),
                    'citizenship_id',
                    'civil_status_id',
                    'religion_id',
                    'is_dual_citizent',
                    'by_birth',
                    'by_naturalization',
                    'indicate_country',
                    'ra_postal_id',
                    'ra_region',
                    'ra_province',
                    'ra_city',
                    'ra_house_no',
                    'ra_barangay',
                    'ra_street',
                    'ra_village',
                    'pa_postal_id',
                    'pa_region',
                    'pa_province',
                    'pa_city',
                    'pa_house_no',
                    'pa_barangay',
                    'pa_street',
                    'pa_village',
                    'father_name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_first_name ELSE dbo.ufn_DecryptString(father_first_name,'$appKey') END as father_first_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_middle_name ELSE dbo.ufn_DecryptString(father_middle_name,'$appKey') END as father_middle_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_last_name ELSE dbo.ufn_DecryptString(father_last_name,'$appKey') END as father_last_name"),
                    'father_name_suffix_id',
                    'mother_name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_first_name ELSE dbo.ufn_DecryptString(mother_first_name,'$appKey') END as mother_first_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_middle_name ELSE dbo.ufn_DecryptString(mother_middle_name,'$appKey') END as mother_middle_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_last_name ELSE dbo.ufn_DecryptString(mother_last_name,'$appKey') END as mother_last_name"),
                    'mother_name_suffix_id',
                    'spouse_name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_first_name ELSE dbo.ufn_DecryptString(spouse_first_name,'$appKey') END as spouse_first_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_middle_name ELSE dbo.ufn_DecryptString(spouse_middle_name,'$appKey') END as spouse_middle_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_last_name ELSE dbo.ufn_DecryptString(spouse_last_name,'$appKey') END as spouse_last_name"),
                    'spouse_name_suffix_id',
                    'spouse_occupation',
                    'spouse_employer',
                    'spouse_business_address',
                    'spouse_mobile_no',
                    'company_id',
                    'branch_id',
                    'department_id',
                    'division_id',
                    'section_id',
                    'employment_type_id',
                    'position_id',
                    'plantilla_id',
                    'is_plantilla',
                    'is_employee',
                    'is_teaching',
                    'date_hired',
                    'tin_no',
                    'gsis_no',
                    'sss_no',
                    'pagibig_no',
                    'philhealth_no',
                    'salary',
                    'tax_amount',
                    'gsis_amount',
                    'sss_amount',
                    'pagibig_amount',
                    'philhealth_amount',
                    'payroll_interval_id',
                    'end_date',
                    'account_no'
                )
                ->where('id', $id)
                ->orderBy('employees.first_name', 'asc')
                ->get();
            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', $employee_info[0]->plantilla_id)->get();
        }

        $data = collect();
        if ($applicant->isNotEmpty()) {
            $data = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->join('departments', 'departments.id', '=', 'plantillas.department_id')
                ->join('applicant_details as a', 'a.position_applied_id', '=', 'plantillas.id')
                ->join('applicant_headers as b', 'a.applicant_id', '=', 'b.id')
                ->join('application_status as f', 'a.application_status_id', '=', 'f.id')
                // Track applicant response to job offers (accepted/declined/expired)
                ->leftJoin('applicant_jo as jo', function ($join) {
                    $join->on('jo.applicant_detail_id', '=', 'a.id')
                        ->on('jo.applicant_id', '=', 'b.id');
                })
                ->leftJoin('salary_schedules', function ($join) {
                    $join->where('salary_schedules.active', '=', 1);
                })
                ->leftJoin('salary_schedules_details', function ($join) {
                    $join->on('salary_schedules_details.salary_grade_id', '=', 'plantillas.salary_grade_id')
                        ->on('salary_schedules_details.salary_step_id', '=', 'plantillas.salary_step_id')
                        ->on('salary_schedules_details.salary_schedule_id', '=', 'salary_schedules.id');
                })
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
                    'f.name as application_status',
                    // Applicant job-offer response flags
                    'jo.is_accepted as jo_is_accepted',
                    'jo.is_rejected as jo_is_rejected',
                    'jo.is_expired as jo_is_expired',
                    // Salary
                    'salary_schedules_details.amount as salary',
                    'a.created_at'
                )
                ->where('a.is_plantilla', true)
                ->where('b.id', $applicant[0]->id)
                ->orderBy('positions.name', 'asc')
                ->get();

            $data = $this->enrichPlantillaRequirements($data);
        }

        $non_plantillas = collect();
        if ($applicant->isNotEmpty()) {
            $non_plantillas = DB::table('non_plantillas as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->join('applicant_details as d', 'd.position_applied_id', '=', 'a.id')
                ->join('applicant_headers as e', 'd.applicant_id', '=', 'e.id')
                ->join('application_status as f', 'd.application_status_id', '=', 'f.id')
                // Track applicant response to non-plantilla job offers
                ->leftJoin('applicant_jo as jo', function ($join) {
                    $join->on('jo.applicant_detail_id', '=', 'd.id')
                        ->on('jo.applicant_id', '=', 'e.id');
                })
                ->select(
                    'a.id',
                    'a.position_id',
                    'b.name as position',
                    'a.salary',
                    'c.name as department',
                    'a.eligibility',
                    'a.experience',
                    'a.education',
                    'a.training',
                    'a.description',
                    'a.qualification',
                    'a.vacant',
                    'a.publication_from',
                    'a.publication_to',
                    'f.name as application_status',
                    // Applicant job-offer response flags
                    'jo.is_accepted as jo_is_accepted',
                    'jo.is_rejected as jo_is_rejected',
                    'jo.is_expired as jo_is_expired',
                    DB::raw("case when a.status = 0 then 'Inactive' else 'Active' end as status")
                )
                ->where('d.is_plantilla', false)
                ->where('e.id', $applicant[0]->id)
                ->orderBy('b.name', 'asc')
                ->get();
        }

        $prefixes = DB::table('name_prefixes')->where('active', true)->orderBy('id', 'asc')->get();
        $suffixes = DB::table('name_suffixes')->where('active', true)->orderBy('name', 'asc')->get();
        $genders = DB::table('genders')->where('active', true)->orderBy('name', 'asc')->get();
        $civil_status = DB::table('civil_status')->where('active', true)->orderBy('id', 'asc')->get();
        $citizenships = DB::table('citizenships')->where('active', true)->orderBy('id', 'asc')->get();
        $religions = DB::table('religions')->where('active', true)->orderBy('id', 'asc')->get();
        $blood_types = DB::table('blood_types')->where('active', true)->orderBy('name', 'asc')->get();
        $companies = DB::table('companies')->get();
        $branches = DB::table('branches')->orderBy('id', 'asc')->get();
        $departments = DB::table('departments')->where('active', true)->orderBy('name', 'asc')->get();
        $employment_types = DB::table('employment_types')->where('active', true)->orderBy('id', 'asc')->get();
        $positions = DB::table('positions')->where('active', true)->orderBy('name', 'asc')->get();

        $plantilla_emp = DB::table('plantillas')->where(['employee_id' => $id, 'active' => true]);
        $plantillas = DB::table('plantillas')->where(['employee_id' => 0, 'active' => true])->union($plantilla_emp)->get();

        $salary_grades = DB::table('salary_grades')->where('active', true)->orderBy('id', 'asc')->get();
        $salary_steps = DB::table('salary_steps')->where('active', true)->orderBy('id', 'asc')->get();
        $blood_types_2 = DB::table('blood_types')->where('active', true)->orderBy('id', 'asc')->get();
        $payroll_intervals = DB::table('payroll_intervals')->where('active', true)->orderBy('id', 'asc')->get();
        $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();
        $learnings = DB::table('learnings')->where('active', true)->orderBy('name', 'asc')->get();
        $divisions = DB::table('divisions')->where('active', true)->orderBy('name', 'asc')->get();
        $sections = DB::table('sections')->where('active', true)->orderBy('name', 'asc')->get();

        $applicant_id = $applicant->isNotEmpty() ? $applicant[0]->id : 0;

        $vacant_data = DB::table('plantillas')
            ->join('positions', 'positions.id', '=', 'plantillas.position_id')
            ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
            ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
            ->join('departments', 'departments.id', '=', 'plantillas.department_id')
            ->select('plantillas.id', 'plantillas.code', 'positions.name as position', 'salary_steps.name as step', 'salary_grades.name as grade', 'departments.name as department', 'plantillas.eligibility as eligibility', 'plantillas.experience as experience', 'plantillas.training as training', 'plantillas.education as education', 'plantillas.unit as unit', 'plantillas.publication_from as publication_from', 'plantillas.publication_to as publication_to', 'plantillas.status as status', 'plantillas.active')
            ->where('plantillas.employee_id', '=', 0)
            ->where('plantillas.publication_from', '<=', now())
            ->where('plantillas.publication_to', '>=', now())
            ->whereRaw("isnull(plantillas.active,0) = 1 and isnull(plantillas.approved,0) = 1 and isnull(plantillas.cancelled,0) = 0")
            ->whereNotIn('plantillas.id', function ($query) use ($applicant_id) {
                $query->select('position_applied_id')->from('applicant_details')->where(['is_plantilla' => true, 'applicant_id' => $applicant_id]);
            })
            ->orderBy('positions.name', 'asc')
            ->get();

        $vacant_data = $this->enrichPlantillaRequirements($vacant_data);

        $vacant_non_plantillas = DB::table('non_plantillas as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->join('departments as c', 'a.department_id', '=', 'c.id')
            ->leftJoin('employment_types as d', 'a.employee_type_id', '=', 'd.id')
            ->select('a.id', 'a.position_id', 'b.name as position', 'a.salary', 'c.name as department', 'a.eligibility', 'a.experience', 'a.education', 'a.training', 'a.description', 'a.qualification', 'a.vacant', 'a.publication_from', 'a.publication_to', DB::raw("case when a.status = 0 then 'Inactive' else 'Active' end as status"), 'd.name as employment_type', 'a.number_of_months')
            ->where('a.publication_from', '<=', now())
            ->where('a.publication_to', '>=', now())
            ->where('vacant', '>', 0)
            ->where('a.status', 1)
            ->whereNotIn('a.id', function ($query) use ($applicant_id) {
                $query->select('position_applied_id')->from('applicant_details')->where(['is_plantilla' => false, 'applicant_id' => $applicant_id]);
            })
            ->orderBy('b.name', 'asc')
            ->get();

        $children = $id > 0 ? DB::table('employee_children')->where('employee_id', $id)->get() : collect();
        $educations = $id > 0 ? DB::table('employee_educations')->where('employee_id', $id)->orderBy('employee_educations.graduated_year', 'desc')->get() : collect();
        $service_records = $id > 0 ? DB::table('service_records')->where('employee_id', $id)->get() : collect();
        $employments = $id > 0 ? DB::table('employee_employment_records')->where('employee_id', $id)->get() : collect();
        $examinations = $id > 0 ? DB::table('employee_examinations')->where('employee_id', $id)->get() : collect();
        $trainings = $id > 0 ? DB::table('employee_trainings')->where('employee_id', $id)->get() : collect();
        $organizations = $id > 0 ? DB::table('employee_organizations')->where('employee_id', $id)->get() : collect();
        $recognitions = DB::table('employee_recognations')->where('employee_id', $id)->get();
        $skills = $id > 0 ? DB::table('employee_skills')->where('employee_id', $id)->get() : collect();
        $memberships = $id > 0 ? DB::table('employee_memberships')->where('employee_id', $id)->get() : collect();
        $references = $id > 0 ? DB::table('employee_references')->where('employee_id', $id)->get() : collect();
        $dependents = $id > 0 ? DB::table('employee_dependents')->where('employee_id', $id)->get() : collect();
        // Only query documents when $id > 0 (employee exists)
        // For new applicants without employee records, return empty collection
        $documents = $id > 0
            ? DB::connection('attachments')->table('employee_documents')->where('employee_id', $id)->get()
            : collect();

        // Payroll-related queries removed - not needed for applicant portal
        $loans = collect();
        $incomes = collect();

        $salary_grade_steps = DB::table('salary_grades as a')
            ->crossJoin('salary_steps as b')
            ->select(DB::raw("CONVERT(nvarchar(50),a.id) + ' - ' + CONVERT(nvarchar(50),b.id) as id"), DB::raw("CONVERT(nvarchar(50),a.id) + ' - ' + CONVERT(nvarchar(50),b.id) as name"))
            ->get();

        $quesionaires = $id > 0 ? DB::table('employee_pds_answers as a')
            ->join('pds_questionaires as b', 'a.question_id', '=', 'b.id')
            ->select('b.id', 'b.code', 'b.questions', 'a.is_yes', 'a.is_no', 'a.yes_details', 'a.case_status', 'a.date_filed')
            ->where('a.employee_id', $id)
            ->orderBy('b.id', 'asc')
            ->get() : collect();
        if ($quesionaires->isEmpty()) {
            $quesionaires = DB::table('pds_questionaires')->orderBy('id', 'asc')->get();
        }

        $examination_schedules = collect();
        if ($applicant->isNotEmpty()) {
            $examination_schedules = DB::table('examination_schedule_header as a')
                ->join('examination_setup_header as b', 'a.exam_id', '=', 'b.id')
                ->join('applicant_examination_headers as c', 'c.exam_schedule_id', '=', 'a.id')
                ->select('a.id', 'c.applicant_id', 'a.exam_date_from', 'a.exam_date_to', 'a.exam_time_from', 'a.exam_time_to', 'b.exam_set', 'b.exam_duration', 'b.passing_criteria', 'c.is_complete', 'c.date_completed', DB::raw("case when (c.is_complete = 0 and c.is_expired = 0 and (a.exam_date_from <= cast(getdate() as date) and a.exam_date_to >= cast(getdate() as date))) then  case when (a.exam_time_from <= cast(getdate() as time(0)) and a.exam_time_to >= cast(getdate() as time(0))) then  'Active' else  'Expired' end  when (c.is_complete = 0 and c.is_expired = 0 and a.exam_date_to < cast(getdate() as date) and a.exam_time_to < cast(getdate() as time(0))) then 'Expired' when (c.is_complete = 0 and c.is_expired = 0 and a.exam_date_from <= cast(getdate() as date) and a.exam_time_from > cast(getdate() as time(0))) then 'Pending' when (c.is_complete = 1) then 'Completed' else 'Expired' end as status"), DB::raw("cast(getdate() as time(0))"), DB::raw("cast(getdate() as date)"))
                ->where(['a.posted' => 1, 'c.applicant_id' => $applicant[0]->id])
                ->get();
        }

        $interview_schedules = collect();
        if ($applicant->isNotEmpty()) {
            $interview_schedules = DB::table('applicant_interview_headers as a')
                ->join('interview_applicants as b', 'a.id', '=', 'b.interview_id')
                ->join('interview_levels as c', 'a.panel_group_level', '=', 'c.id')
                ->select('a.*', 'c.interview_level as level', DB::raw("case  when (b.is_complete_interview = 0 and b.is_cancelled_interview = 0 and (a.start_date <= getdate() and a.end_date >= getdate() or a.start_time <= cast(getdate() as time) and a.end_time >= cast(getdate() as time))) then 'Active'  when (b.is_complete_interview = 0 and b.is_cancelled_interview = 0 and a.end_date < getdate() and a.end_time < cast(getdate() as time)) then 'Expired'  when (b.is_complete_interview = 0 and b.is_cancelled_interview = 0 and a.start_date <= getdate() and a.start_time > cast(getdate() as time)) then 'Pending'  when (b.is_cancelled_interview = 1) then 'Cancelled'  when (b.is_complete_interview = 1) then 'Completed' else '' end as status"), 'b.applicant_id')
                ->where(['b.applicant_id' => $applicant[0]->id, 'a.posted' => true])
                ->get();
        }

        return [
            'applicant' => $applicant,
            'pds_data' => $pds_data,
            'data' => $data,
            'non_plantillas' => $non_plantillas,
            'vacant_data' => $vacant_data,
            'vacant_non_plantillas' => $vacant_non_plantillas,
            'prefixes' => $prefixes,
            'suffixes' => $suffixes,
            'genders' => $genders,
            'civil_status' => $civil_status,
            'citizenships' => $citizenships,
            'religions' => $religions,
            'blood_types' => $blood_types,
            'companies' => $companies,
            'branches' => $branches,
            'departments' => $departments,
            'employment_types' => $employment_types,
            'positions' => $positions,
            'plantillas' => $plantillas,
            'salary_grades' => $salary_grades,
            'salary_steps' => $salary_steps,
            'payroll_intervals' => $payroll_intervals,
            'eligibilities' => $eligibilities,
            'learnings' => $learnings,
            'employee_info' => $employee_info,
            // For the applicant portal, treat $data (applications with status,
            // salary, and job-offer response flags) as the list of selected
            // plantilla applications.
            'plantillas_selected' => $data,
            'children' => $children,
            'educations' => $educations,
            'service_records' => $service_records,
            'employments' => $employments,
            'examinations' => $examinations,
            'trainings' => $trainings,
            'organizations' => $organizations,
            'recognitions' => $recognitions,
            'skills' => $skills,
            'memberships' => $memberships,
            'references' => $references,
            'loans' => $loans,
            'incomes' => $incomes,
            'divisions' => $divisions,
            'sections' => $sections,
            'dependents' => $dependents,
            'documents' => $documents,
            'salary_grade_steps' => $salary_grade_steps,
            'quesionaires' => $quesionaires,
            'examination_schedules' => $examination_schedules,
            'interview_schedules' => $interview_schedules,
        ];
    }

    /**
     * Accept a job offer
     *
     * @param int $applicationId The application detail ID
     * @param int $userId The authenticated user ID
     * @return array|bool
     */
    public function acceptJobOffer(int $applicationId, int $userId)
    {
        // Verify the application belongs to the user
        $applicantId = DB::table('applicant_headers')
            ->where('user_id', $userId)
            ->value('id');

        if (!$applicantId) {
            throw new \Exception('Applicant not found');
        }

        // Verify the application belongs to this applicant
        $application = DB::table('applicant_details')
            ->where('id', $applicationId)
            ->where('applicant_id', $applicantId)
            ->first();

        if (!$application) {
            throw new \Exception('Application not found');
        }

        // Prevent multiple responses to the same job offer
        $existingResponse = DB::table('applicant_jo')
            ->where('applicant_id', $applicantId)
            ->where('applicant_detail_id', $applicationId)
            ->first();

        if ($existingResponse && (
            $existingResponse->is_accepted ||
            $existingResponse->is_rejected ||
            $existingResponse->is_expired
        )) {
            throw new \Exception('You have already responded to this job offer.');
        }

        // Check if status is "For Hiring" (status_id = 5)
        if ($application->application_status_id != 5) {
            throw new \Exception('This application cannot be accepted at this time');
        }

        // Do NOT change application_status_id here.
        // Status 6 ("Hired") will only be set by HR after verifying requirements.
        // We only update the timestamp so HR can still see recent activity.
        $updated = DB::table('applicant_details')
            ->where('id', $applicationId)
            ->update([
                'updated_at' => now(),
            ]);

        if ($updated) {
            // Record job offer response in applicant_jo
            DB::table('applicant_jo')->updateOrInsert(
                [
                    'applicant_id' => $applicantId,
                    'applicant_detail_id' => $applicationId,
                ],
                [
                    'is_accepted' => true,
                    'is_rejected' => false,
                    'is_expired' => false,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            return [
                'success' => true,
                'application_id' => $applicationId,
                'new_status' => 'Accepted'
            ];
        }

        return false;
    }

    /**
     * Reject a job offer
     *
     * @param int $applicationId The application detail ID
     * @param int $userId The authenticated user ID
     * @return array|bool
     */
    public function rejectJobOffer(int $applicationId, int $userId)
    {
        // Verify the application belongs to the user
        $applicantId = DB::table('applicant_headers')
            ->where('user_id', $userId)
            ->value('id');

        if (!$applicantId) {
            throw new \Exception('Applicant not found');
        }

        // Verify the application belongs to this applicant
        $application = DB::table('applicant_details')
            ->where('id', $applicationId)
            ->where('applicant_id', $applicantId)
            ->first();

        if (!$application) {
            throw new \Exception('Application not found');
        }

        // Prevent multiple responses to the same job offer
        $existingResponse = DB::table('applicant_jo')
            ->where('applicant_id', $applicantId)
            ->where('applicant_detail_id', $applicationId)
            ->first();

        if ($existingResponse && (
            $existingResponse->is_accepted ||
            $existingResponse->is_rejected ||
            $existingResponse->is_expired
        )) {
            throw new \Exception('You have already responded to this job offer.');
        }

        // Check if status is "For Hiring" (status_id = 5)
        if ($application->application_status_id != 5) {
            throw new \Exception('This application cannot be declined at this time');
        }

        // Do NOT change application_status_id here. HR will handle final hiring status.
        $updated = DB::table('applicant_details')
            ->where('id', $applicationId)
            ->update([
                'updated_at' => now()
            ]);

        if ($updated) {
            // Record job offer response in applicant_jo
            DB::table('applicant_jo')->updateOrInsert(
                [
                    'applicant_id' => $applicantId,
                    'applicant_detail_id' => $applicationId,
                ],
                [
                    'is_accepted' => false,
                    'is_rejected' => true,
                    'is_expired' => false,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            return [
                'success' => true,
                'application_id' => $applicationId,
                'new_status' => 'Declined'
            ];
        }

        return false;
    }

    /**
     * Merge plantilla requirement fields from related setup tables so applications
     * and vacancies expose the same requirement data to the applicant portal.
     */
    private function enrichPlantillaRequirements($plantillas)
    {
        if ($plantillas->isEmpty()) {
            return $plantillas;
        }

        $plantillaIds = $plantillas->pluck('id')->toArray();

        $workExperiences = DB::table('plantilla_work_experience')
            ->whereIn('plantilla_id', $plantillaIds)
            ->select('plantilla_id', 'position')
            ->get()
            ->groupBy('plantilla_id');

        $trainings = DB::table('plantilla_trainings')
            ->whereIn('plantilla_id', $plantillaIds)
            ->select('plantilla_id', 'training', 'hours')
            ->get()
            ->groupBy('plantilla_id');

        $eligibilities = DB::table('plantilla_eligibility')
            ->join('eligibilities', 'eligibilities.id', '=', 'plantilla_eligibility.examination_id')
            ->whereIn('plantilla_eligibility.plantilla_id', $plantillaIds)
            ->select('plantilla_eligibility.plantilla_id as plantilla_id', 'eligibilities.name as eligibility')
            ->get()
            ->groupBy('plantilla_id');

        $educations = DB::table('plantilla_education')
            ->whereIn('plantilla_id', $plantillaIds)
            ->select('plantilla_id', 'program', 'academic_level_id')
            ->get()
            ->groupBy('plantilla_id');

        $remarks = DB::table('plantilla_remarks')
            ->whereIn('plantilla_id', $plantillaIds)
            ->select('plantilla_id', 'requirement')
            ->get()
            ->groupBy('plantilla_id');

        $competencies = DB::table('plantilla_competencies')
            ->leftJoin('subcompetencies', 'subcompetencies.id', '=', 'plantilla_competencies.subcompetency_id')
            ->whereIn('plantilla_competencies.plantilla_id', $plantillaIds)
            ->select('plantilla_competencies.plantilla_id as plantilla_id', 'subcompetencies.name as competency')
            ->get()
            ->groupBy('plantilla_id');

        return $plantillas->map(function ($plantilla) use ($workExperiences, $trainings, $eligibilities, $educations, $remarks, $competencies) {
            $id = $plantilla->id;

            $experienceList = $workExperiences->get($id, collect());
            $plantilla->work_experience_list = $experienceList->pluck('position')->filter()->toArray();
            $plantilla->experience = !empty($plantilla->experience)
                ? $plantilla->experience
                : ($experienceList->isNotEmpty() ? implode(', ', $experienceList->pluck('position')->filter()->toArray()) : null);

            $trainingList = $trainings->get($id, collect());
            $plantilla->training_list = $trainingList->map(function ($t) {
                $hours = $t->hours ? " ({$t->hours} hours)" : '';
                return $t->training . $hours;
            })->filter()->toArray();
            $plantilla->training = !empty($plantilla->training)
                ? $plantilla->training
                : ($trainingList->isNotEmpty() ? implode(', ', $trainingList->pluck('training')->filter()->toArray()) : null);

            $eligibilityList = $eligibilities->get($id, collect());
            $plantilla->eligibility_list = $eligibilityList->pluck('eligibility')->filter()->toArray();
            $plantilla->eligibility = !empty($plantilla->eligibility)
                ? $plantilla->eligibility
                : ($eligibilityList->isNotEmpty() ? implode(', ', $eligibilityList->pluck('eligibility')->filter()->toArray()) : null);

            $educationList = $educations->get($id, collect());
            $plantilla->education_list = $educationList->pluck('program')->filter()->toArray();
            $plantilla->education = !empty($plantilla->education)
                ? $plantilla->education
                : ($educationList->isNotEmpty() ? implode(', ', $educationList->pluck('program')->filter()->toArray()) : null);
            $plantilla->education_details = $educationList->map(function ($edu) {
                return [
                    'program' => $edu->program,
                    'academic_level_id' => $edu->academic_level_id,
                ];
            })->filter(function ($edu) {
                return !empty($edu['program']);
            })->values()->toArray();

            $remarksList = $remarks->get($id, collect());
            $plantilla->requirements_list = $remarksList->pluck('requirement')->filter()->toArray();

            $competenciesList = $competencies->get($id, collect());
            $plantilla->competencies_list = $competenciesList->pluck('competency')->filter()->toArray();

            return $plantilla;
        });
    }
}
