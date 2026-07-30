<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HRDDReviewController extends Controller
{
    use ApiResponse;

    public function hrdd_review_per_position()
    {
        try {
            $positions  = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->join('departments', 'departments.id', '=', 'plantillas.department_id')
                ->join('applicant_details as a', 'a.position_applied_id', '=', 'plantillas.id')
                ->leftjoin('interview_applicants as b', 'a.applicant_id', '=', 'b.applicant_id')
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
                    DB::raw("count(a.applicant_id) as total")
                )
                ->where('plantillas.employee_id', 0)
                ->where('plantillas.active', 1)
                ->where('a.is_plantilla', true)
                ->where('plantillas.approved', true)
                ->whereNotIn('a.applicant_id', function ($query) {
                    $query->select('applicant_id')->from('applicants_for_administrator_selections')->where('is_forwarded', true);
                })
                ->groupBy(
                    'plantillas.id',
                    'plantillas.code',
                    'positions.name',
                    'salary_steps.name',
                    'salary_grades.name',
                    'departments.name',
                    'plantillas.eligibility',
                    'plantillas.experience',
                    'plantillas.training',
                    'plantillas.education',
                    'plantillas.unit',
                    'plantillas.publication_from',
                    'plantillas.publication_to',
                    'plantillas.status',
                    'plantillas.active'
                )
                ->orderBy('plantillas.publication_from', 'asc')
                ->get();

            return $this->successResponse($positions, 'HRDD review positions loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load HRDD review positions: ' . $e->getMessage());
        }
    }

    public function index($position_id)
    {
        try {
            $interviews = DB::table('applicant_headers as e')
                ->join('applicant_details as d', 'e.id', '=', 'd.applicant_id')
                ->leftJoin('applicants_for_administrator_selections as f', 'e.id', '=', 'f.applicant_id')
                ->select(
                    'e.id as applicant_id',
                    DB::raw("UPPER(CONCAT(e.first_name,' ',e.last_name)) as name"),
                    'e.applicant_no',
                    'f.hr_performance_rating',
                )
                ->where([
                    'd.position_applied_id' => $position_id,
                    'd.application_status_id' => 1,
                ])
                ->whereNotIn('e.id', function ($query) {
                    $query->select('applicant_id')->from('applicants_for_administrator_selections')->where('is_forwarded', true);
                })
                ->whereIn('e.id', function ($query) {
                    $query->select('applicant_id')->from('applicant_shortlisted');
                })
                ->distinct()
                ->orderBy('name', 'asc')
                ->get();

            $applicant_positions = DB::table('applicant_headers as b')
                ->join('applicant_details as c', 'b.id', '=', 'c.applicant_id')
                ->join('plantillas as d', 'c.position_applied_id', '=', 'd.id')
                ->join('positions as e', 'd.position_id', '=', 'e.id')
                ->select(
                    'c.applicant_id',
                    'e.name as position'
                )
                ->get();

            $bi_documents = DB::table('applicant_background_investigation_documents')->get();

            return $this->successResponse([
                'interviews' => $interviews,
                'applicant_positions' => $applicant_positions,
                'bi_documents' => $bi_documents
            ], 'HRDD review list data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load HRDD review list data: ' . $e->getMessage());
        }
    }

    public function hrdd_review_pds($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $applicant = DB::table('applicant_headers as a')
                ->join('genders as b', 'a.gender', '=', 'b.id')
                ->select('a.*', 'b.name as gender')
                ->where('a.id', $id)
                ->get();

            if ($applicant->isEmpty()) {
                return $this->notFoundResponse('Applicant not found');
            }

            $employee_data = DB::table('employees as a')->where(['employee_no' => $applicant[0]->applicant_no])->get();

            if ($employee_data->isEmpty()) {
                $emp_id = 0;
            } else {
                $emp_id = $employee_data[0]->id;
            }

            if ($emp_id == 0) {
                $dummy_employee_info =
                    array(
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
                        'salary' => '',
                        'tax_amount' => '',
                        'gsis_amount' => '',
                        'sss_amount' => '',
                        'pagibig_amount' => '',
                        'philhealth_amount' => '',
                        'payroll_interval_id' => 0,
                        'end_date' => '',
                        'account_no' => ''
                    );

                $plantillas_selected = DB::table('plantillas')
                    ->select('salary_grade_id', 'salary_step_id')
                    ->where('id', 0)->get();

                $employee_info = (object)$dummy_employee_info;
                $employee_info =  collect([$employee_info]);
            } else {

                $employee_info = DB::table('employees')
                    ->select(
                        'id',
                        'photo',
                        'employee_no',
                        'access_no',
                        'name_prefix_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN first_name ELSE dbo.ufn_DecryptString(first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN middle_name ELSE dbo.ufn_DecryptString(middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN last_name ELSE dbo.ufn_DecryptString(last_name,'$app_key') END as last_name"),
                        'name_suffix_id',
                        'birth_place',
                        'birthdate',
                        'age',
                        'gender_id',
                        'height',
                        'weight',
                        'blood_type_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN email ELSE dbo.ufn_DecryptString(email,'$app_key') END as email"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mobile_no ELSE dbo.ufn_DecryptString(mobile_no,'$app_key') END as mobile_no"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN telephone_no ELSE dbo.ufn_DecryptString(telephone_no,'$app_key') END as telephone_no"),
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
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_first_name ELSE dbo.ufn_DecryptString(father_first_name,'$app_key') END as father_first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_middle_name ELSE dbo.ufn_DecryptString(father_middle_name,'$app_key') END as father_middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_last_name ELSE dbo.ufn_DecryptString(father_last_name,'$app_key') END as father_last_name"),
                        'father_name_suffix_id',
                        'mother_name_prefix_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_first_name ELSE dbo.ufn_DecryptString(mother_first_name,'$app_key') END as mother_first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_middle_name ELSE dbo.ufn_DecryptString(mother_middle_name,'$app_key') END as mother_middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_last_name ELSE dbo.ufn_DecryptString(mother_last_name,'$app_key') END as mother_last_name"),
                        'mother_name_suffix_id',
                        'spouse_name_prefix_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_first_name ELSE dbo.ufn_DecryptString(spouse_first_name,'$app_key') END as spouse_first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_middle_name ELSE dbo.ufn_DecryptString(spouse_middle_name,'$app_key') END as spouse_middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_last_name ELSE dbo.ufn_DecryptString(spouse_last_name,'$app_key') END as spouse_last_name"),
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
                    ->where('id', $emp_id)
                    ->orderBy('employees.first_name', 'asc')
                    ->get();

                $plantillas_selected = DB::table('plantillas')
                    ->select('salary_grade_id', 'salary_step_id')
                    ->where('id', $employee_info[0]->plantilla_id)->get();
            }

            $data  = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->join('departments', 'departments.id', '=', 'plantillas.department_id')
                ->join('applicant_details as a', 'a.position_applied_id', '=', 'plantillas.id')
                ->join('applicant_headers as b', 'a.applicant_id', '=', 'b.id')
                ->join('application_status as f', 'a.application_status_id', '=', 'f.id')
                ->select('plantillas.id', 'plantillas.code', 'positions.name as position', 'salary_steps.name as step', 'salary_grades.name as grade', 'departments.name as department', 'plantillas.eligibility as eligibility', 'plantillas.experience as experience', 'plantillas.training as training', 'plantillas.education as education', 'plantillas.unit as unit', 'plantillas.publication_from as publication_from', 'plantillas.publication_to as publication_to', 'plantillas.status as status', 'plantillas.active', 'f.name as application_status')
                ->where('a.is_plantilla', true)
                ->where('b.id', $applicant[0]->id)
                ->orderBy('positions.name', 'asc')
                ->get();

            $non_plantillas = DB::table('non_plantillas as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->join('applicant_details as d', 'd.position_applied_id', '=', 'a.id')
                ->join('applicant_headers as e', 'd.applicant_id', '=', 'e.id')
                ->join('application_status as f', 'd.application_status_id', '=', 'f.id')
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
                    DB::raw(
                        "case when a.status = 0 then 'Inactive' else 'Active' end as status"
                    )
                )
                ->where('d.is_plantilla', false)
                ->where('e.id', $applicant[0]->id)
                ->orderBy('b.name', 'asc')
                ->get();

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

            // Get Plantilla
            $plantilla_emp = DB::table('plantillas')->where(['employee_id' => $emp_id, 'active' => true]);
            $plantillas = DB::table('plantillas')->where(['employee_id' => 0, 'active' => true])->union($plantilla_emp)->get();

            $salary_grades = DB::table('salary_grades')->where('active', true)->orderBy('id', 'asc')->get();
            $salary_steps = DB::table('salary_steps')->where('active', true)->orderBy('id', 'asc')->get();
            $blood_types = DB::table('blood_types')->where('active', true)->orderBy('id', 'asc')->get();
            $payroll_intervals = DB::table('payroll_intervals')->where('active', true)->orderBy('id', 'asc')->get();
            $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();
            $learnings = DB::table('learnings')->where('active', true)->orderBy('name', 'asc')->get();
            $divisions = DB::table('divisions')->where('active', true)->orderBy('name', 'asc')->get();
            $sections = DB::table('sections')->where('active', true)->orderBy('name', 'asc')->get();

            $applicant_id = $applicant[0]->id;

            $children = DB::table('employee_children')->where('employee_id', $emp_id)->get();
            $educations = DB::table('employee_educations')->where('employee_id', $emp_id)->orderBy('employee_educations.graduated_year', 'desc')->get();
            $service_records = DB::table('service_records')->where('employee_id', $emp_id)->get();
            $employments = DB::table('employee_employment_records')->where('employee_id', $emp_id)->get();
            $examinations = DB::table('employee_examinations')->where('employee_id', $emp_id)->get();
            $trainings = DB::table('employee_trainings')->where('employee_id', $emp_id)->get();
            $organizations = DB::table('employee_organizations')->where('employee_id', $emp_id)->get();
            $recognitions = DB::table('employee_recognations')->where('employee_id', $emp_id)->get();
            $skills = DB::table('employee_skills')->where('employee_id', $emp_id)->get();
            $memberships = DB::table('employee_memberships')->where('employee_id', $emp_id)->get();
            $references = DB::table('employee_references')->where('employee_id', $emp_id)->get();
            $dependents = DB::table('employee_dependents')->where('employee_id', $emp_id)->get();
            $documents = DB::table('employee_documents')->where('employee_id', $emp_id)->get();

            $loans = DB::table('loan_applications as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'b.name',
                    'a.loan_amount',
                    'a.payment',
                    'a.balance'
                )
                ->where([
                    'a.is_approve' => true,
                    'a.employee_id' => $emp_id
                ])
                ->get();

            $payroll_period_id = DB::table('payroll_incomes')->max('payroll_period_id');

            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'b.name',
                    'a.amount'
                )
                ->where([
                    'a.employee_id' => $emp_id,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->get();

            $salary_grade_steps = DB::table('salary_grades as a')
                ->crossJoin('salary_steps as b')
                ->select(
                    DB::raw("CONVERT(nvarchar(50),a.id) + ' - ' + CONVERT(nvarchar(50),b.id) as id"),
                    DB::raw("CONVERT(nvarchar(50),a.id) + ' - ' + CONVERT(nvarchar(50),b.id) as name")
                )
                ->get();

            $quesionaires = DB::table('employee_pds_answers as a')
                ->join('pds_questionaires as b', 'a.question_id', '=', 'b.id')
                ->select(
                    'b.id',
                    'b.code',
                    'b.questions',
                    'a.is_yes',
                    'a.is_no',
                    'a.yes_details',
                    'a.case_status',
                    'a.date_filed'
                )
                ->where('a.employee_id', $emp_id)
                ->orderBy('b.id', 'asc')
                ->get();

            if ($quesionaires->isEmpty()) {
                $quesionaires = DB::table('pds_questionaires')->orderBy('id', 'asc')->get();
            }

            $eete_ratings = DB::table('eete_ratings')->get();

            if ($eete_ratings->isEmpty()) {
                $eete_ratings = [
                    'id' => 0,
                    'education_rating' => null,
                    'experience_rating' => null,
                    'training_rating' => null,
                    'eligibility_rating' => null
                ];

                $eete_ratings = (object)$eete_ratings;
                $eete_ratings = collect([$eete_ratings]);
            }

            $applicant_eete_ratings = DB::table('applicant_eete_ratings')->where('applicant_id', $applicant_id)->get();

            if ($applicant_eete_ratings->isEmpty()) {
                $applicant_eete_ratings = [
                    'id' => 0,
                    'applicant_id' => $applicant_id,
                    'education_rating' => null,
                    'experience_rating' => null,
                    'training_rating' => null,
                    'eligibility_rating' => null,
                    'reviewed_status_id' => null
                ];

                $applicant_eete_ratings = (object)$applicant_eete_ratings;
                $applicant_eete_ratings = collect([$applicant_eete_ratings]);
            }

            return $this->successResponse([
                'applicant' => $applicant,
                'data' => $data,
                'non_plantillas' => $non_plantillas,
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
                'plantillas_selected' => $plantillas_selected,
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
                'eete_ratings' => $eete_ratings,
                'applicant_eete_ratings' => $applicant_eete_ratings
            ], 'HRDD review PDS data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load HRDD review PDS data: ' . $e->getMessage());
        }
    }

    public function hrdd_review_exam($id)
    {
        try {
            $applicant_examinations = DB::table('applicant_examination_headers')
                ->where([
                    'applicant_id' => $id,
                    'is_complete' => true
                ])
                ->get();

            if ($applicant_examinations->isNotEmpty()) {
                $applicant_examination_id = $applicant_examinations[0]->id;
            } else {
                $applicant_examination_id = 0;
            }

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
                    'c.exam_rating'
                )
                ->where([
                    'a.posted' => 1,
                    'c.id' => $applicant_examination_id
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
                    DB::raw("count(b.question_id) as total_items"),
                    DB::raw("sum(b.correct) as total_correct")
                )
                ->where('a.id', $applicant_examination_id)
                ->groupBy(
                    'a.id',
                    'd.sub_category',
                    'e.difficulty_level'
                )
                ->get();

            return $this->successResponse([
                'exam' => $exam,
                'exam_total_sub_categories' => $exam_total_sub_categories
            ], 'HRDD review exam data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load HRDD review exam data: ' . $e->getMessage());
        }
    }

    public function forwardAdmin($id)
    {
        try {
            $applicant = DB::table('applicant_headers as a')
                ->join('genders as b', 'a.gender', '=', 'b.id')
                ->select('a.*', 'b.name as gender')
                ->where('a.id', $id)
                ->get();

            if ($applicant->isEmpty()) {
                return $this->notFoundResponse('Applicant not found');
            }

            $admin_selections = DB::table('applicants_for_administrator_selections')->where('applicant_id', $id)->get();

            if ($admin_selections->isNotEmpty()) {
                $selection_id = $admin_selections[0]->id;
                $rating = $admin_selections[0]->hr_performance_rating;
            } else {
                $selection_id = 0;
                $rating = null;
            }

            $bi_documents = DB::table('applicant_background_investigation_documents')->where('selection_id', $selection_id)->get();
            $br_documents = DB::table('applicant_board_resolution_documents')->where('selection_id', $selection_id)->get();

            return $this->successResponse([
                'applicant' => $applicant,
                'bi_documents' => $bi_documents,
                'br_documents' => $br_documents,
                'rating' => $rating
            ], 'HRDD review admin data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load HRDD review admin data: ' . $e->getMessage());
        }
    }

    public function submitToAdmin(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'hr_performance_rating' => 'required|numeric|min:0|max:100',
                'bi_document.*' => 'nullable|file|mimes:pdf,jpg,png,doc,docx,xlsx,xls|max:10240',
                'board_resolution_document.*' => 'nullable|file|mimes:pdf,jpg,png,doc,docx,xlsx,xls|max:10240'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $applicants = DB::table('applicant_headers')->where('id', $id)->get();

            if ($applicants->isEmpty()) {
                return $this->notFoundResponse('Applicant not found');
            }

            $applicant_no = $applicants[0]->applicant_no;

            $data = [
                'applicant_id' => $id,
                'forwarded_by' => Auth::user()->id,
                'forwarded_date' => now(),
                'is_forwarded' => true,
                'hr_performance_rating' => $request->hr_performance_rating
            ];

            $is_exist = DB::table('applicants_for_administrator_selections')->where('applicant_id', $id)->get();

            if ($is_exist->isEmpty()) {
                $selection_id = DB::table('applicants_for_administrator_selections')->insertGetId($data);
            } else {
                $selection_id = $is_exist[0]->id;
                DB::table('applicants_for_administrator_selections')->where('id', $selection_id)->update($data);
            }

            $processed_files = [];

            // Save BI Attachments
            if ($request->hasfile('bi_document')) {
                $allowedfileExtension = ['pdf', 'jpg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
                $files = $request->file('bi_document');
                $ctr = 0;

                foreach ($files as $file) {
                    $BI_file_name = $file->getClientOriginalName();
                    $BI_file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'bi_document\\' . $applicant_no . '_' . $BI_file_name;
                    $extension = $file->getClientOriginalExtension();
                    $check = in_array($extension, $allowedfileExtension);

                    if ($check) {
                        // Save record of attachments to database.
                        $attachment_data = [
                            'selection_id' => $selection_id,
                            'applicant_id' => $id,
                            'bi_document' => $BI_file_name,
                            'bi_document_path' => $BI_file_path,
                        ];

                        DB::table('applicant_background_investigation_documents')->insert($attachment_data);
                        // Save attachment to path.
                        $request->bi_document[$ctr]->storeAs('bi_document', $applicant_no . '_' . $BI_file_name);
                        
                        $processed_files[] = [
                            'file_name' => $BI_file_name,
                            'file_type' => 'bi_document',
                            'file_size' => $file->getSize()
                        ];
                    }

                    $ctr++;
                }
            }

            // Save BR Attachments
            if ($request->hasfile('board_resolution_document')) {
                $allowedfileExtension = ['pdf', 'jpg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
                $files_br = $request->file('board_resolution_document');
                $ctr = 0;

                foreach ($files_br as $file) {
                    $BR_file_name = $file->getClientOriginalName();
                    $BR_file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'bi_document\\' . $applicant_no . '_' . $BR_file_name;
                    $extension = $file->getClientOriginalExtension();
                    $check = in_array($extension, $allowedfileExtension);

                    if ($check) {
                        // Save record of attachments to database.
                        $attachment_data = [
                            'selection_id' => $selection_id,
                            'applicant_id' => $id,
                            'board_resolution_document' => $BR_file_name,
                            'board_resolution_document_path' => $BR_file_path,
                        ];

                        DB::table('applicant_board_resolution_documents')->insert($attachment_data);
                        // Save attachment to path.
                        $request->board_resolution_document[$ctr]->storeAs('board_resolution_document', $applicant_no . '_' . $BR_file_name);
                        
                        $processed_files[] = [
                            'file_name' => $BR_file_name,
                            'file_type' => 'board_resolution_document',
                            'file_size' => $file->getSize()
                        ];
                    }

                    $ctr++;
                }
            }

            return $this->successResponse([
                'selection_id' => $selection_id,
                'processed_files' => $processed_files,
                'total_files' => count($processed_files)
            ], 'Successfully submitted applicant to administrator');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to submit applicant to administrator: ' . $e->getMessage());
        }
    }

    public function deleteDocs($id, $type_id)
    {
        try {
            if ($type_id == 1) {
                $document = DB::table('applicant_background_investigation_documents')->where('id', $id)->first();
                if (!$document) {
                    return $this->notFoundResponse('Document not found');
                }
                DB::table('applicant_background_investigation_documents')->where('id', $id)->delete();
            } else {
                $document = DB::table('applicant_board_resolution_documents')->where('id', $id)->first();
                if (!$document) {
                    return $this->notFoundResponse('Document not found');
                }
                DB::table('applicant_board_resolution_documents')->where('id', $id)->delete();
            }

            return $this->successResponse(['deleted_id' => $id], 'Document deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete document: ' . $e->getMessage());
        }
    }

    public function hrdd_review_store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'id' => 'required|array',
                'hrdd_rating.*' => 'nullable|numeric|min:0|max:100',
                'attachments.*.*' => 'nullable|file|mimes:pdf,jpg,png,doc,docx,xlsx,xls|max:10240'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = $request->all();
            $processed_count = 0;
            $forwarded_count = 0;

            for ($i = 0; $i < count($data["id"]); $i++) {

                if (isset($data['select'])) {
                    if (in_array($data['id'][$i], $data['select'])) {
                        $is_forwarded = true;
                        $forwarded_count++;
                    } else {
                        $is_forwarded = false;
                    }
                } else {
                    $is_forwarded = false;
                }

                $hrdd_data = [
                    'applicant_id' => $data["id"][$i],
                    'forwarded_by' => Auth::user()->id,
                    'forwarded_date' => now(),
                    'is_forwarded' => $is_forwarded,
                    'hr_performance_rating' => isset($data["hrdd_rating"][$i]) ? $data["hrdd_rating"][$i] : 0,
                ];

                $is_exist = DB::table('applicants_for_administrator_selections')->where('applicant_id', $data["id"][$i])->get();

                if ($is_exist->isEmpty()) {
                    $selection_id = DB::table('applicants_for_administrator_selections')->insertGetId($hrdd_data);
                } else {
                    $selection_id = $is_exist[0]->id;
                    DB::table('applicants_for_administrator_selections')->where('id', $selection_id)->update($hrdd_data);
                }

                $applicants = DB::table('applicant_headers')->where('id', $data["id"][$i])->get();

                if ($applicants->isNotEmpty()) {
                    $applicant_no = $applicants[0]->applicant_no;
                } else {
                    $applicant_no = '';
                }

                // Save BI Attachments
                if (isset($data['attachments'][$data["id"][$i]])) {
                    $allowedfileExtension = ['pdf', 'jpg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
                    $files = $data['attachments'][$data["id"][$i]];
                    $ctr = 0;

                    foreach ($files as $file) {
                        $BI_file_name = $file->getClientOriginalName();
                        $BI_file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'bi_document\\' . $applicant_no . '_' . $BI_file_name;
                        $extension = $file->getClientOriginalExtension();
                        $check = in_array($extension, $allowedfileExtension);

                        if ($check) {
                            // Save record of attachments to database.
                            $attachment_data = [
                                'selection_id' => $selection_id,
                                'applicant_id' => $data["id"][$i],
                                'bi_document' => $BI_file_name,
                                'bi_document_path' => $BI_file_path,
                            ];

                            DB::table('applicant_background_investigation_documents')->insert($attachment_data);
                            // Save attachment to path.
                            $request->attachments[$data["id"][$i]][$ctr]->storeAs('bi_document', $applicant_no . '_' . $BI_file_name);
                        }

                        $ctr++;
                    }
                }
                
                $processed_count++;
            }

            return $this->successResponse([
                'processed_count' => $processed_count,
                'forwarded_count' => $forwarded_count
            ], 'HRDD review data saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save HRDD review data: ' . $e->getMessage());
        }
    }
}
