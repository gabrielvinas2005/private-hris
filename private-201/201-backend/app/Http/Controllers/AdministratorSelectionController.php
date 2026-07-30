<?php

namespace App\Http\Controllers;


use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\EmailApplicantHired;
use App\Traits\ApiResponse;

class AdministratorSelectionController extends Controller
{
    use ApiResponse;


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
                DB::raw('COUNT(DISTINCT a.applicant_id) as total')
            )
            ->where('plantillas.employee_id', 0)
            ->where('plantillas.active', 1)
            ->where('a.is_plantilla', true)
            ->where('a.application_status_id', 5)
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
            ->get()
            ->map(function ($row) {
                $row->is_plantilla = true;

                return $row;
            });

        $nonPlantillaPositions = DB::table('non_plantillas as np')
            ->join('applicant_details as a', function ($join) {
                $join->on('a.position_applied_id', '=', 'np.id')
                    ->whereRaw('ISNULL(a.is_plantilla, 1) = 0');
            })
            ->leftJoin('positions as pos', 'np.position_id', '=', 'pos.id')
            ->leftJoin('departments as dep', 'np.department_id', '=', 'dep.id')
            ->select(
                'np.id',
                DB::raw('NULL as code'),
                'pos.name as position',
                DB::raw("'—' as step"),
                DB::raw("'—' as grade"),
                'np.salary',
                'dep.name as department',
                'np.eligibility as eligibility',
                'np.experience as experience',
                'np.training as training',
                'np.education as education',
                DB::raw('NULL as unit'),
                'np.publication_from as publication_from',
                'np.publication_to as publication_to',
                'np.status as status',
                DB::raw('1 as active'),
                DB::raw('COUNT(DISTINCT a.applicant_id) as total')
            )
            ->where('np.vacant', '>', 0)
            ->where('np.status', 1)
            ->where('np.is_approved', 1)
            ->where('a.application_status_id', 5)
            ->whereIn('a.applicant_id', function ($query) {
                $query->select('applicant_id')->from('applicants_for_administrator_selections')->where('is_forwarded', true);
            })
            ->groupBy(
                'np.id',
                'pos.name',
                'dep.name',
                'np.salary',
                'np.eligibility',
                'np.experience',
                'np.training',
                'np.education',
                'np.publication_from',
                'np.publication_to',
                'np.status'
            )
            ->orderBy('np.publication_from', 'asc')
            ->get()
            ->map(function ($row) {
                $row->is_plantilla = false;

                return $row;
            });

        $combined = $positions->concat($nonPlantillaPositions)
            ->sortBy(function ($r) {
                return $r->publication_from ?? '';
            })
            ->values();

        return $this->successResponse($combined, 'Administrator selection positions retrieved successfully');
    }

    public function index(Request $request, $position_id)
    {
        $isNonPlantilla = $request->query('type') === 'non_plantilla';

        $interviewsQuery = DB::table('applicant_headers as e')
            ->join('applicants_for_administrator_selections as f', 'e.id', '=', 'f.applicant_id')
            ->join('applicant_details as h', 'e.id', '=', 'h.applicant_id')
            ->select(
                'e.id as applicant_id',
                DB::raw("UPPER(CONCAT(e.first_name,' ',e.last_name)) as name"),
                'e.applicant_no',
                'e.application_status_id',
                'f.hr_performance_rating',
                DB::raw("(
                    SELECT TOP 1 exam_rating
                    FROM applicant_examination_headers
                    WHERE applicant_id = e.id
                    AND is_complete = 1
                    ORDER BY id DESC
                ) as exam_rating")
            )
            ->where('f.is_forwarded', true)
            ->where('h.position_applied_id', $position_id)
            ->whereIn('h.application_status_id', [5, 6]);

        if ($isNonPlantilla) {
            $interviewsQuery->whereRaw('ISNULL(h.is_plantilla, 1) = 0');
        } else {
            $interviewsQuery->whereRaw('ISNULL(h.is_plantilla, 1) = 1');
        }

        $interviews = $interviewsQuery->orderBy('name', 'asc')->get();

        $applicant_positions = DB::table('applicant_headers as b')
            ->join('applicant_details as c', 'b.id', '=', 'c.applicant_id')
            ->leftJoin('plantillas as pl', function ($join) {
                $join->on('c.position_applied_id', '=', 'pl.id')
                    ->whereRaw('ISNULL(c.is_plantilla, 1) = 1');
            })
            ->leftJoin('non_plantillas as np', function ($join) {
                $join->on('c.position_applied_id', '=', 'np.id')
                    ->whereRaw('ISNULL(c.is_plantilla, 1) = 0');
            })
            ->leftJoin('positions as e_pl', 'pl.position_id', '=', 'e_pl.id')
            ->leftJoin('positions as e_np', 'np.position_id', '=', 'e_np.id')
            ->select(
                'c.applicant_id',
                DB::raw('COALESCE(e_np.name, e_pl.name) as position')
            )
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

        // Get applicant from applicant_headers to find the employee_no
        $applicant = DB::table('applicant_headers as a')
            ->leftJoin('genders as b', 'a.gender', '=', 'b.id')
            ->select('a.*', 'b.name as gender')
            ->where('a.id', $id)
            ->get();

        if ($applicant->isEmpty()) {
            return $this->notFoundResponse('Applicant not found');
        }

        // Applicant stub employee: prefer users.is_applicant = 1, then any employee row by applicant_no
        // (non-plantilla / edge cases may not satisfy the join, which left examinations & trainings empty)
        $employee_data = DB::table('employees as a')
            ->join('users as u', 'u.employee_no', '=', 'a.employee_no')
            ->where([
                'a.employee_no' => $applicant[0]->applicant_no,
                'u.is_applicant' => 1,
            ])
            ->select('a.*')
            ->get();

        if ($employee_data->isEmpty()) {
            $employee_data = DB::table('employees as a')
                ->where('a.employee_no', $applicant[0]->applicant_no)
                ->select('a.*')
                ->get();
        }

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

            $employee_info = DB::table('employees as a')
                ->leftJoin('genders as g', 'g.id', '=', 'a.gender_id')
                ->leftJoin('civil_status as cs', 'cs.id', '=', 'a.civil_status_id')
                ->leftJoin('religions as r', 'r.id', '=', 'a.religion_id')
                ->leftJoin('blood_types as bt', 'bt.id', '=', 'a.blood_type_id')
                ->leftJoin('citizenships as c', 'c.id', '=', 'a.citizenship_id')
                ->select(
                    'a.id',
                    'a.photo',
                    'a.employee_no',
                    'a.access_no',
                    'a.name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    'a.name_suffix_id',
                    'a.birth_place',
                    'a.birthdate',
                    'a.age',
                    'a.gender_id',
                    'g.name as gender',
                    'a.height',
                    'a.weight',
                    'a.blood_type_id',
                    'bt.name as blood_type',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.email ELSE dbo.ufn_DecryptString(a.email,'$app_key') END as email"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.mobile_no ELSE dbo.ufn_DecryptString(a.mobile_no,'$app_key') END as mobile_no"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.telephone_no ELSE dbo.ufn_DecryptString(a.telephone_no,'$app_key') END as telephone_no"),
                    'a.citizenship_id',
                    'c.name as citizenship',
                    'a.civil_status_id',
                    'cs.name as civil_status',
                    'a.religion_id',
                    'r.name as religion',
                    'a.is_dual_citizent',
                    'a.by_birth',
                    'a.by_naturalization',
                    'a.indicate_country',
                    'a.ra_postal_id',
                    'a.ra_region',
                    'a.ra_province',
                    'a.ra_city',
                    'a.ra_house_no',
                    'a.ra_barangay',
                    'a.ra_street',
                    'a.ra_village',
                    'a.pa_postal_id',
                    'a.pa_region',
                    'a.pa_province',
                    'a.pa_city',
                    'a.pa_house_no',
                    'a.pa_barangay',
                    'a.pa_street',
                    'a.pa_village',
                    'a.father_name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.father_first_name ELSE dbo.ufn_DecryptString(a.father_first_name,'$app_key') END as father_first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.father_middle_name ELSE dbo.ufn_DecryptString(a.father_middle_name,'$app_key') END as father_middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.father_last_name ELSE dbo.ufn_DecryptString(a.father_last_name,'$app_key') END as father_last_name"),
                    'a.father_name_suffix_id',
                    'a.mother_name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.mother_first_name ELSE dbo.ufn_DecryptString(a.mother_first_name,'$app_key') END as mother_first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.mother_middle_name ELSE dbo.ufn_DecryptString(a.mother_middle_name,'$app_key') END as mother_middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.mother_last_name ELSE dbo.ufn_DecryptString(a.mother_last_name,'$app_key') END as mother_last_name"),
                    'a.mother_name_suffix_id',
                    'a.spouse_name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.spouse_first_name ELSE dbo.ufn_DecryptString(a.spouse_first_name,'$app_key') END as spouse_first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.spouse_middle_name ELSE dbo.ufn_DecryptString(a.spouse_middle_name,'$app_key') END as spouse_middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.spouse_last_name ELSE dbo.ufn_DecryptString(a.spouse_last_name,'$app_key') END as spouse_last_name"),
                    'a.spouse_name_suffix_id',
                    // DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.spouse_occupation ELSE dbo.ufn_DecryptString(a.spouse_occupation,'$app_key') END as spouse_occupation"),
                    // DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.spouse_employer ELSE dbo.ufn_DecryptString(a.spouse_employer,'$app_key') END as spouse_employer"),
                    // DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.spouse_business_address ELSE dbo.ufn_DecryptString(a.spouse_business_address,'$app_key') END as spouse_business_address"),
                    'a.spouse_occupation',
                    'a.spouse_employer',
                    'a.spouse_business_address',
                    'a.spouse_mobile_no',
                    'a.company_id',
                    'a.branch_id',
                    'a.department_id',
                    'a.division_id',
                    'a.section_id',
                    'a.employment_type_id',
                    'a.position_id',
                    'a.plantilla_id',
                    'a.is_plantilla',
                    'a.is_employee',
                    'a.is_teaching',
                    'a.date_hired',
                    'a.tin_no',
                    'a.gsis_no',
                    'a.sss_no',
                    'a.pagibig_no',
                    'a.philhealth_no',
                    'a.salary',
                    'a.tax_amount',
                    'a.gsis_amount',
                    'a.sss_amount',
                    'a.pagibig_amount',
                    'a.philhealth_amount',
                    'a.payroll_interval_id',
                    'a.end_date',
                    'a.account_no'
                )
                ->where('a.id', $emp_id)
                ->orderBy('a.first_name', 'asc')
                ->get();

            if ($employee_info->isEmpty()) {
                // If employee not found, fall back to dummy data
                $dummy_employee_info = array(
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
                    'ra_postal_id' => 0,
                    'ra_region' => '',
                    'ra_province' => '',
                    'ra_city' => '',
                    'ra_house_no' => '',
                    'ra_barangay' => '',
                    'ra_street' => '',
                    'ra_village' => '',
                    'pa_postal_id' => 0,
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
                    'mother_name_prefix_id' => 0,
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
                    'spouse_mobile_no' => '',
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
                $employee_info = collect([(object) $dummy_employee_info]);
            }

            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', $employee_info->isNotEmpty() ? $employee_info[0]->plantilla_id : 0)->get();
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

        // Work experience: employee_employment_records (by employee_id); Work_Experience links applicants via Reference_id (= applicant_no only — no employee_id column on this table)
        $employmentMerged = collect();
        if ($emp_id > 0) {
            foreach (DB::table('employee_employment_records')->where('employee_id', $emp_id)->orderByDesc('work_start_date')->get() as $row) {
                $employmentMerged->push([
                    'position' => $row->position ?? '',
                    'company' => $row->work_company ?? '',
                    'date_from' => $row->work_start_date ?? '',
                    'date_to' => $row->work_end_date ?? '',
                    'salary' => $row->monthly_salary ?? null,
                ]);
            }
        }
        foreach (
            DB::table('Work_Experience')
                ->where('Reference_id', $applicant[0]->applicant_no)
                ->orderByDesc('Work_start_date')
                ->get() as $we
        ) {
            $employmentMerged->push([
                'position' => $we->Position ?? '',
                'company' => $we->Office_name ?? '',
                'date_from' => $we->Work_start_date ?? '',
                'date_to' => $we->Work_end_date ?? '',
                'salary' => $we->Salary ?? $we->salary ?? null,
            ]);
        }
        $employments = $employmentMerged->unique(function ($item) {
            return ($item['position'] ?? '') . '|' . ($item['company'] ?? '') . '|' . (string) ($item['date_from'] ?? '');
        })->values();

        // Modal expects: examination_title, rating, examination_date, examination_place
        $examinations = $emp_id > 0
            ? DB::table('employee_examinations as a')
            ->leftJoin('eligibilities as b', 'b.id', '=', 'a.eligibility_id')
            ->select('a.exam_rating', 'a.exam_date', 'a.place_of_exam', 'b.name as eligibility_name')
            ->where('a.employee_id', $emp_id)
            ->get()
            ->map(function ($r) {
                return [
                    'examination_title' => $r->eligibility_name ?: 'Eligibility',
                    'rating' => $r->exam_rating ?? '',
                    'examination_date' => $r->exam_date ?? '',
                    'examination_place' => $r->place_of_exam ?? '',
                ];
            })
            ->values()
            : collect();

        // Modal expects: training_title, training_institution, training_date_from, training_date_to, training_hours
        $trainings = $emp_id > 0
            ? DB::table('employee_trainings as a')
            ->leftJoin('learnings as b', 'b.id', '=', 'a.learning_id')
            ->select('a.training', 'a.training_from', 'a.training_to', 'a.hours', 'a.sponsored_by', 'b.name as learning_name')
            ->where('a.employee_id', $emp_id)
            ->get()
            ->map(function ($r) {
                $title = $r->training ?? $r->learning_name ?? '';

                return [
                    'training_title' => $title,
                    'training_institution' => $r->sponsored_by ?? '',
                    'training_date_from' => $r->training_from ?? '',
                    'training_date_to' => $r->training_to ?? '',
                    'training_hours' => $r->hours ?? '',
                ];
            })
            ->values()
            : collect();

        $organizations = DB::table('employee_organizations')->where('employee_id', $emp_id)->get();
        $recognitions = DB::table('employee_recognations')->where('employee_id', $emp_id)->get();
        $skills = DB::table('employee_skills')->where('employee_id', $emp_id)->get();
        $memberships = DB::table('employee_memberships')->where('employee_id', $emp_id)->get();
        $references = DB::table('employee_references')->where('employee_id', $emp_id)->get();
        $dependents = DB::table('employee_dependents')->where('employee_id', $emp_id)->get();

        // PDS / employee documents: primary store is attachments DB (same as ApplicantHiringController@Info).
        // Avoid cross-DB joins: load types from default DB when document_type_id is present.
        $documents = collect();
        if ($emp_id > 0) {
            try {
                $documents = DB::connection('attachments')
                    ->table('employee_documents as a')
                    ->select(
                        'a.employee_document_id',
                        'a.employee_id',
                        'a.name',
                        'a.description',
                        'a.attachment_name',
                        'a.path',
                        'a.extension',
                        'a.document_type_id',
                        'a.created_at',
                        'a.updated_at'
                    )
                    ->where('a.employee_id', $emp_id)
                    ->orderByDesc('a.employee_document_id')
                    ->get();
            } catch (\Exception $e) {
                $documents = DB::table('employee_documents as a')
                    ->leftJoin('document_types as b', 'a.document_type_id', '=', 'b.id')
                    ->select(
                        'a.employee_document_id',
                        'a.employee_id',
                        'a.name',
                        'a.description',
                        'a.attachment_name',
                        'a.path',
                        'a.extension',
                        'a.document_type_id',
                        'a.created_at',
                        'a.updated_at',
                        'b.name as document_type'
                    )
                    ->where('a.employee_id', $emp_id)
                    ->orderByDesc('a.employee_document_id')
                    ->get();
            }
            if ($documents->isNotEmpty()) {
                $typeIds = $documents->pluck('document_type_id')->filter()->unique()->values()->all();
                $typeNames = [];
                if (!empty($typeIds)) {
                    $typeNames = DB::table('document_types')->whereIn('id', $typeIds)->pluck('name', 'id')->all();
                }
                $documents = $documents->map(function ($row) use ($typeNames) {
                    $row = (array) $row;
                    $hasType = isset($row['document_type']) && $row['document_type'] !== null && $row['document_type'] !== '';
                    if (!$hasType && !empty($row['document_type_id']) && isset($typeNames[$row['document_type_id']])) {
                        $row['document_type'] = $typeNames[$row['document_type_id']];
                    }

                    return (object) $row;
                });
            }
        }

        // Applicant-uploaded files (resume, etc.) — not tied to stub employee row
        $applicant_attachments = collect();
        try {
            $applicant_attachments = DB::connection('attachments')
                ->table('applicant_attachments')
                ->where('applicant_id', $applicant_id)
                ->select('id', 'applicant_id', 'attachment_name', 'file_type', 'file_size', 'path', 'created_at')
                ->orderByDesc('id')
                ->get();
        } catch (\Exception $e) {
            try {
                $applicant_attachments = DB::table('applicant_attachments')
                    ->where('applicant_id', $applicant_id)
                    ->select('id', 'applicant_id', 'attachment_name', 'file_type', 'file_size', 'path', 'created_at')
                    ->orderByDesc('id')
                    ->get();
            } catch (\Exception $e2) {
                $applicant_attachments = collect();
            }
        }

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
            'applicant_attachments' => $applicant_attachments,
            'salary_grade_steps' => $salary_grade_steps,
            'quesionaires' => $quesionaires,
            'eete_ratings' => $eete_ratings,
            'applicant_eete_ratings' => $applicant_eete_ratings
        ], 'Administrator selection PDS data loaded successfully');
    }

    public function administrator_selection_exam($id)
    {
        // Get all completed exams for this applicant
        $applicant_examinations = DB::table('applicant_examination_headers')
            ->where([
                'applicant_id' => $id,
                'is_complete' => true
            ])
            ->orderBy('id', 'desc')
            ->get();

        if ($applicant_examinations->isEmpty()) {
            return $this->successResponse([
                'exam' => [],
                'exam_total_sub_categories' => []
            ], 'No examination data found for this applicant');
        }

        // Get all exam IDs
        $applicant_examination_ids = $applicant_examinations->pluck('id')->toArray();

        // Get all exams with their details
        // Order: pre-examination first, then technical examination
        $exam = DB::table('examination_schedule_header as a')
            ->join('examination_setup_header as b', 'a.exam_id', '=', 'b.id')
            ->join('applicant_examination_headers as c', 'c.exam_schedule_id', '=', 'a.id')
            ->join('exam_categories as d', 'b.category_id', '=', 'd.id')
            ->leftJoin('exam_types as et', 'b.exam_type_id', '=', 'et.id')
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
                'c.exam_rating',
                'et.code as exam_type_code',
                'et.name as exam_type_name'
            )
            ->where([
                'a.posted' => 1,
                'c.applicant_id' => $id
            ])
            ->whereIn('c.id', $applicant_examination_ids)
            ->orderByRaw("
                CASE
                    WHEN LOWER(et.code) = 'pre-examination' THEN 1
                    WHEN LOWER(et.code) = 'technical' THEN 2
                    ELSE 3
                END ASC,
                c.id ASC
            ")
            ->get();

        // Get exam sub-categories for all exams
        $exam_total_sub_categories = DB::table('applicant_examination_headers as a')
            ->join('applicant_examination_details as b', 'a.id', '=', 'b.applicant_examination_id')
            ->join('exam_questionaire_headers as c', 'b.question_id', '=', 'c.id')
            ->join('exam_sub_categories as d', 'c.sub_category_id', '=', 'd.id')
            ->join('exam_difficulty_levels as e', 'd.difficulty_level', '=', 'e.id')
            ->select(
                'a.id as applicant_examination_id',
                'd.sub_category',
                'e.difficulty_level',
                DB::raw("count(b.question_id) as total_items"),
                DB::raw("sum(b.correct) as total_correct")
            )
            ->whereIn('a.id', $applicant_examination_ids)
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

        return $this->successResponse([
            'applicant' => $applicant,
            'bi_documents' => $bi_documents,
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
                    // Help static analyzers infer the disk adapter type so `path()` is recognized.
                    /** @var \Illuminate\Filesystem\FilesystemAdapter $localDisk */
                    $localDisk = Storage::disk('local');
                    $BR_file_path = $localDisk->path('bi_document\\' . $applicant_no . '_' . $BR_file_name);
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

            // Return the file for preview (inline) instead of download
            $mimeType = mime_content_type($pathToFile);
            if (!$mimeType) {
                // Fallback MIME types based on extension
                $extension = strtolower(pathinfo($pathToFile, PATHINFO_EXTENSION));
                $mimeTypes = [
                    'pdf' => 'application/pdf',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'doc' => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'xls' => 'application/vnd.ms-excel',
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ];
                $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
            }

            return response()->file($pathToFile, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $documents[0]->attachment_name . '"'
            ]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve file information: ' . $e->getMessage());
        }
    }

    public function appoint(Request $request, $id)
    {
        try {
            $app_key = config('app.key'); // Get the app key for decryption

            $applicant_data = DB::table('applicant_headers')->where('id', $id)->get();
            if ($applicant_data->isEmpty()) {
                return $this->errorResponse('Applicant not found.', 404);
            }

            $positionAppliedParam = (int) $request->query('position_applied_id', 0);
            $isNonPlantilla = $request->query('type') === 'non_plantilla';

            $detailQuery = DB::table('applicant_details')->where('applicant_id', $id);
            if ($positionAppliedParam > 0) {
                $detailQuery->where('position_applied_id', $positionAppliedParam);
            }
            if ($isNonPlantilla) {
                $detailQuery->whereRaw('ISNULL(is_plantilla, 1) = 0');
            } elseif ($positionAppliedParam > 0) {
                $detailQuery->whereRaw('ISNULL(is_plantilla, 1) = 1');
            }
            $applicant_details = $detailQuery->get();

            if ($applicant_details->isEmpty()) {
                $applicant_details = DB::table('applicant_details')->where('applicant_id', $id)->get();
            }

            if ($applicant_details->isEmpty()) {
                return $this->errorResponse('Applicant not found.', 404);
            }

            $applied_position = (int) $applicant_details[0]->position_applied_id;
            if (! $isNonPlantilla) {
                $isNonPlantilla = ((int) ($applicant_details[0]->is_plantilla ?? 1)) === 0;
            }

            $applicant_no = $applicant_data[0]->applicant_no;
            $user_id = $applicant_data[0]->user_id;
            $employees = DB::table('employees')->where('employee_no', $applicant_no)->get();

            if ($employees->isNotEmpty()) {
                $employee_id = $employees[0]->id;
            } else {
                $employee_id = 0;
            }

            $applicant_header_data = [
                'application_status_id' => 6,
            ];

            DB::table('applicant_headers')->where('id', $id)->update($applicant_header_data);

            $detailsUpdate = DB::table('applicant_details')
                ->where('applicant_id', $id)
                ->where('position_applied_id', $applied_position);
            if ($isNonPlantilla) {
                $detailsUpdate->whereRaw('ISNULL(is_plantilla, 1) = 0');
            } else {
                $detailsUpdate->whereRaw('ISNULL(is_plantilla, 1) = 1');
            }
            $detailsUpdate->update(['application_status_id' => 6]);

            if ($isNonPlantilla) {
                $np = DB::table('non_plantillas')->where('id', $applied_position)->first();
                if (! $np) {
                    return $this->errorResponse('Non-plantilla vacancy not found.', 404);
                }

                $position_id = (int) $np->position_id;
                $department_id = (int) ($np->department_id ?? 0);

                $employee_data = [
                    'is_employee' => 1,
                    'is_plantilla' => 0,
                    'plantilla_id' => 0,
                    'application_status_id' => 6,
                    'position_id' => $position_id,
                    'department_id' => $department_id,
                ];

                if ($employee_id > 0) {
                    if (isset($np->employee_type_id)) {
                        $employee_data['employment_type_id'] = $np->employee_type_id;
                    }
                    DB::table('employees')->where('id', $employee_id)->update($employee_data);
                }

                DB::table('non_plantillas')->where('id', $applied_position)->decrement('vacant');
            } else {
                $plantillas = DB::table('plantillas')->where('id', $applied_position)->get();
                if ($plantillas->isEmpty()) {
                    return $this->errorResponse('Plantilla item not found.', 404);
                }

                $position_id = $plantillas[0]->position_id;
                $plantillaDepartmentId = $plantillas[0]->department_id ?? null;

                // Use plantilla employment type as source-of-truth.
                $plantillaEmploymentTypeId = $plantillas[0]->employment_type_Id ?? null;

                // Backward-compatible fallback if some environments still use employee_type_id in plantillas.
                if (is_null($plantillaEmploymentTypeId) && isset($plantillas[0]->employee_type_Id)) {
                    $plantillaEmploymentTypeId = $plantillas[0]->employee_type_Id ?? null;
                }

                // employees.employment_type_id is NOT NULL; never send null to UPDATE.
                if (is_null($plantillaEmploymentTypeId)) {
                    $currentEmploymentType = DB::table('employees')
                        ->where('id', $employee_id)
                        ->value('employment_type_id');
                    $plantillaEmploymentTypeId = $currentEmploymentType ?? 0;
                }

                $employee_data = [
                    'is_employee' => 1,
                    'is_plantilla' => 1,
                    'plantilla_id' => $applied_position,
                    'application_status_id' => 6,
                    'position_id' => $position_id,
                    'date_hired' => now(),
                    // Mount assignment fields from the selected plantilla
                    'department_id' => $plantillaDepartmentId,
                    'employment_type_id' => $plantillaEmploymentTypeId,
                ];

                if ($employee_id > 0) {
                    DB::table('employees')->where('id', $employee_id)->update($employee_data);
                }

                // Safety sync: ensure the employee row tied to this applicant_no also gets plantilla-assignment fields.
                // This covers cases where employee lookup by id path misses the intended row.
                DB::table('employees')
                    ->where('employee_no', $applicant_no)
                    ->update($employee_data);

                $plantilla_data = [
                    'employee_id' => $employee_id,
                    'status' => 'Occupied',
                ];

                DB::table('plantillas')->where('id', $applied_position)->update($plantilla_data);
            }

            if (!empty($user_id)) {
                DB::table('users')->where('id', $user_id)->delete();
            } else {
                DB::table('users')
                    ->where('employee_no', $applicant_no)
                    ->where('is_applicant', 1)
                    ->delete();
            }

            $positionInfo = DB::table('positions')
                ->where('id', $position_id)
                ->first();

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

            $employeePosition = null;
            if ($employee) {
                $employeePosition = DB::table('positions')
                    ->where('id', $employee->position_id ?? null)
                    ->first();
            }

            // ✅ Prepare email data with decrypted employee info
            $emailData = [
                'applicant_name' => trim($applicant_data[0]->first_name . ' ' . $applicant_data[0]->middle_name . ' ' . $applicant_data[0]->last_name),
                'position_name' => $positionInfo->name ?? 'Unknown Position',
                'department_name' => $departmentInfo->name ?? 'Unknown Department',
                'employee_name' => $employee ? trim(($employee->first_name ?? '') . ' ' . ($employee->middle_name ?? '') . ' ' . ($employee->last_name ?? '')) : '',
                'employee_position' => $employeePosition->name ?? 'Unknown Position',
                'pa_region_name' => $employee ? ($employee->pa_region_name ?? 'Unknown Region') : 'Unknown Region',
                'pa_province_name' => $employee ? ($employee->pa_province_name ?? 'Unknown Province') : 'Unknown Province',
                'pa_city_name' => $employee ? ($employee->pa_city_name ?? 'Unknown City') : 'Unknown City',
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
