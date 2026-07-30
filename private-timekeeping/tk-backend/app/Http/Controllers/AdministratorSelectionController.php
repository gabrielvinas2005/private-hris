<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Notification;
use App\Notifications\EmailApplicantHired;
use App\Traits\GeneratesPdf;

class AdministratorSelectionController extends Controller
{
    use GeneratesPdf;


    public function administrator_selection_per_position()
    {
        $positions = DB::table('plantillas')
            ->join('positions', 'positions.id', '=', 'plantillas.position_id')
            ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
            ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
            ->join('departments', 'departments.id', '=', 'plantillas.department_id')
            ->join('applicant_details as a', 'a.position_applied_id', '=', 'plantillas.id')
            ->leftJoin('interview_applicants as b', 'a.applicant_id', '=', 'b.applicant_id')
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
            ->where('a.application_status_id', 1)
            ->where('plantillas.approved', true)
            ->whereIn('a.applicant_id', function ($query) {
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

        return $this->successResponse($positions, 'Administrator selection positions retrieved successfully');
    }

    public function index($position_id)
    {
        $interviews =
            // DB::table('applicant_interview_headers as a')
            // ->join('interview_applicants as b', 'a.id', '=', 'b.interview_id')
            // ->join('interview_levels as c', 'a.panel_group_level', '=', 'c.id')
            // ->join('interview_panels as d', 'd.interview_id', '=', 'a.id')
            DB::table('applicant_headers as e')
                ->join('applicants_for_administrator_selections as f', 'e.id', '=', 'f.applicant_id')
                ->leftJoin('applicant_examination_headers as g', 'e.id', '=', 'g.applicant_id')
                ->join('applicant_details as h', 'e.id', '=', 'h.applicant_id')
                ->select(
                    'e.id as applicant_id',
                    DB::raw("UPPER(CONCAT(e.first_name,' ',e.last_name)) as name"),
                    'e.applicant_no',
                    'e.application_status_id',
                    'f.hr_performance_rating',
                    'g.exam_rating',
                )
                ->where([
                    // 'b.is_complete_interview' => true,
                    // 'a.posted' => true,
                    'f.is_forwarded' => true,
                    // 'g.is_complete' => true,
                    'h.position_applied_id' => $position_id,
                ])
                ->orderBy('name', 'asc')
                ->distinct()
                ->get();

        $applicant_positions =
            // DB::table('applicant_examination_headers as a')
            DB::table('applicant_headers as b')
                ->join('applicant_details as c', 'b.id', '=', 'c.applicant_id')
                ->join('plantillas as d', 'c.position_applied_id', '=', 'd.id')
                ->join('positions as e', 'd.position_id', '=', 'e.id')
                ->select(
                    'c.applicant_id',
                    'e.name as position',
                )
                // ->where('a.is_complete', true)
                ->get();

        $bi_documents = DB::table('applicant_background_investigation_documents')->get();

        return $this->successResponse([
            'interviews' => $interviews,
            'applicant_positions' => $applicant_positions,
            'bi_documents' => $bi_documents
        ], 'Administrator selection list data loaded successfully');
    }

    public function administrator_selection_pds($id)
    {
        $app_key = env("APP_KEY", "");

        $applicant = DB::table('applicant_headers as a')
            ->join('genders as b', 'a.gender', '=', 'b.id')
            ->select('a.*', 'b.name as gender')
            ->where('a.id', $id)
            ->get();

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

            $employee_info = (object) $dummy_employee_info;
            $employee_info = collect([$employee_info]);
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
                    // DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_occupation ELSE dbo.ufn_DecryptString(spouse_occupation,'$app_key') END as spouse_occupation"),
                    // DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_employer ELSE dbo.ufn_DecryptString(spouse_employer,'$app_key') END as spouse_employer"),
                    // DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_business_address ELSE dbo.ufn_DecryptString(spouse_business_address,'$app_key') END as spouse_business_address"),
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

        $data = DB::table('plantillas')
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

            $eete_ratings = (object) $eete_ratings;
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

            $applicant_eete_ratings = (object) $applicant_eete_ratings;
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
        ], 'Administrator selection PDS data loaded successfully');
    }

    public function administrator_selection_exam($id)
    {

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
        ], 'Administrator selection exam data loaded successfully');
    }

    public function hrdd_rating($id)
    {
        $applicant = DB::table('applicant_headers as a')
            ->join('genders as b', 'a.gender', '=', 'b.id')
            ->select('a.*', 'b.name as gender')
            ->where('a.id', $id)
            ->get();

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
        ], 'Administrator selection HRDD data loaded successfully');
    }

    public function br_upload(Request $request, $id)
    {
        try {
            $applicants = DB::table('applicant_headers')->where('id', $id)->get();

            if ($applicants->isNotEmpty()) {
                $applicant_no = $applicants[0]->applicant_no;
            } else {
                $applicant_no = '';
            }

            $is_exist = DB::table('applicants_for_administrator_selections')->where('applicant_id', $id)->get();

            if ($is_exist->isNotEmpty()) {
                $selection_id = $is_exist[0]->id;
            } else {
                $selection_id = 0;
            }

            // Save BR Attachments
            if ($request->hasfile('board_resolution_document')) {
                $allowedfileExtension = ['pdf', 'jpg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
                $files_br = $request->file('board_resolution_document');
                $ctr = 0;
                $attachment_data = [];

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
                    }

                    $ctr++;
                }
            }

            return $this->successResponse(['applicant_id' => $id], 'Successfully Uploaded Background Investigation Documents.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to upload board resolution documents: ' . $e->getMessage());
        }
    }

    public function download($id, $type_id)
    {
        try {
            if ($type_id == 1) {
                $documents = DB::table('applicant_headers as a')
                    ->join('applicant_background_investigation_documents as b', 'a.id', '=', 'b.applicant_id')
                    ->select(
                        'a.id',
                        'a.applicant_no',
                        'b.bi_document as attachment_name'
                    )
                    ->where('b.id', $id)
                    ->get();

                if ($documents->isEmpty()) {
                    return $this->errorResponse('Document not found.', 404);
                }

                $pathToFile = storage_path('app/bi_document/' . $documents[0]->applicant_no . '_' . $documents[0]->attachment_name);
            } else {
                $documents = DB::table('applicant_headers as a')
                    ->join('applicant_board_resolution_documents as b', 'a.id', '=', 'b.applicant_id')
                    ->select(
                        'a.id',
                        'a.applicant_no',
                        'b.board_resolution_document as attachment_name'
                    )
                    ->where('b.id', $id)
                    ->get();

                if ($documents->isEmpty()) {
                    return $this->errorResponse('Document not found.', 404);
                }

                $pathToFile = storage_path('app/board_resolution_document/' . $documents[0]->applicant_no . '_' . $documents[0]->attachment_name);
            }

            if (!file_exists($pathToFile)) {
                return $this->errorResponse('File not found on server.', 404);
            }

            // Return file info in JSON instead of direct download
            return $this->successResponse([
                'file_path' => $pathToFile,
                'file_name' => $documents[0]->attachment_name,
                'download_url' => url('/api/download/' . $id . '/' . $type_id)
            ], 'File information retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve file information: ' . $e->getMessage());
        }
    }

    public function appoint($id)
    {
        try {
            $app_key = config('app.key'); // Get the app key for decryption

            // ✅ Get applicant info from applicant_headers using applicant_id
            $applicant_data = DB::table('applicant_headers')->where('id', $id)->get();
            $applicant_details = DB::table('applicant_details')->where('applicant_id', $id)->get();

            if ($applicant_data->isEmpty() || $applicant_details->isEmpty()) {
                return $this->errorResponse('Applicant not found.', 404);
            }

            $applicant_no = $applicant_data[0]->applicant_no;
            $user_id = $applicant_data[0]->user_id;
            $applied_position = $applicant_details[0]->position_applied_id;
            $employees = DB::table('employees')->where('employee_no', $applicant_no)->get();
            $plantillas = DB::table('plantillas')->where('id', $applied_position)->get();

            if ($employees->isNotEmpty()) {
                $employee_id = $employees[0]->id;
            } else {
                $employee_id = 0;
            }

            $position_id = $plantillas[0]->position_id;

            // ✅ Data to update
            $applicant_header_data = [
                'application_status_id' => 5,
            ];

            DB::table('applicant_headers')->where('id', $id)->update($applicant_header_data);
            DB::table('applicant_details')->where([
                'applicant_id' => $id,
                'position_applied_id' => $applied_position
            ])
                ->update(['application_status_id' => 5]);

            $employee_data = [
                'is_employee' => 1,
                'is_plantilla' => 1,
                'plantilla_id' => $applied_position,
                'application_status_id' => 5,
                'position_id' => $position_id,
                'department_id' => 1,
            ];

            DB::table('employees')->where('id', $employee_id)->update($employee_data);

            $plantilla_data = [
                'employee_id' => $employee_id,
                'status' => 'Occupied',
            ];

            DB::table('plantillas')->where('id', $applied_position)->update($plantilla_data);

            $user_data = [
                'is_applicant' => 0,
            ];

            DB::table('users')->where('id', $user_id)->update($user_data);

            // ✅ Get position info for the applicant
            $positionInfo = DB::table('positions')
                ->where('id', $position_id)
                ->first();

            // ✅ Get department info for the applicant
            $departmentInfo = DB::table('departments')
                ->where('id', $employee_data['department_id'])
                ->first();

            // ✅ Get the employee with position_id = 181 and decrypt the name
            $employee = DB::table('employees as a')
                ->join('positions as p', 'a.position_id', '=', 'p.id')
                ->select([
                    DB::raw("
                        CASE 
                            WHEN ISNULL(a.is_encrypted, 0) = 0 THEN a.last_name 
                            ELSE dbo.ufn_DecryptString(a.last_name, '$app_key') 
                        END as last_name
                    "),
                    DB::raw("
                        CASE 
                            WHEN ISNULL(a.is_encrypted, 0) = 0 THEN a.first_name 
                            ELSE dbo.ufn_DecryptString(a.first_name, '$app_key') 
                        END as first_name
                    "),
                    DB::raw("
                        CASE 
                            WHEN ISNULL(a.is_encrypted, 0) = 0 THEN a.middle_name 
                            ELSE dbo.ufn_DecryptString(a.middle_name, '$app_key') 
                        END as middle_name
                    "),
                    'a.position_id',
                    'a.pa_region_name',
                    'a.pa_province_name',
                    'a.pa_city_name',
                ])
                ->where('p.name', 'MUNICIPAL MAYOR')  // Match by position name
                ->first();

            // ✅ Get position name for the employee
            $employeePosition = DB::table('positions')
                ->where('id', $employee->position_id ?? null)
                ->first();

            // ✅ Prepare email data with decrypted employee info
            $emailData = [
                'applicant_name' => trim($applicant_data[0]->first_name . ' ' . $applicant_data[0]->middle_name . ' ' . $applicant_data[0]->last_name),
                'position_name' => $positionInfo->name ?? 'Unknown Position',
                'department_name' => $departmentInfo->name ?? 'Unknown Department',
                'employee_name' => trim(($employee->first_name ?? '') . ' ' . ($employee->middle_name ?? '') . ' ' . ($employee->last_name ?? '')),
                'employee_position' => $employeePosition->name ?? 'Unknown Position',
                'pa_region_name' => $employee->pa_region_name ?? 'Unknown Region',
                'pa_province_name' => $employee->pa_province_name ?? 'Unknown Province',
                'pa_city_name' => $employee->pa_city_name ?? 'Unknown City',
                'date_sent' => now()->format('Y-m-d H:i:s'),
                'reviewed_status_id' => 5, // 5 indicates "Appointed"
            ];

            // ✅ Send email only if applicant has a valid email
            if ($applicant_data[0] && !empty($applicant_data[0]->email)) {
                Notification::route('mail', $applicant_data[0]->email)
                    ->notify(new EmailApplicantHired($emailData));
            }

            return $this->successResponse([
                'applicant_id' => $id,
                'employee_id' => $employee_id,
                'position_id' => $position_id,
                'email_sent' => !empty($applicant_data[0]->email)
            ], 'Applicant appointed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to appoint applicant: ' . $e->getMessage());
        }
    }


    public function print(Request $request)
    {
        try {
            // Validate the request
            $validator = validator($request->all(), [
                'position_title' => 'required|string|max:255',
                'agency_name' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'author' => 'required|string|max:255',
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Retrieve the selected position and related data
            $position = DB::table('positions')->get();

            if (!$position) {
                return $this->errorResponse('Position not found.', 404);
            }

            // Gather additional data for the PDF
            $data = [
                'position_title' => $request->position_title,
                'agency_name' => $request->agency_name,
                'location' => $request->location,
                'author' => $request->author,
                'date' => $request->date,
            ];

            // Generate the PDF
            $pdf = PDF::loadView('administrator_selection.CS_Form5_print', $data)
                ->setOptions(['defaultFont' => 'sans-serif']);
            
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'CS_Form5_Certificate_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate PDF: ' . $e->getMessage());
        }
    }
}
