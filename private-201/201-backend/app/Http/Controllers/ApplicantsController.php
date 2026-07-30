<?php

namespace App\Http\Controllers;

use Auth;
use Image;
use DateTime;
use App\User;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Notification;
use App\Notifications\EmailUserAccountNotification;
use App\Notifications\EmailApplicantExamPassed;

class ApplicantsController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function index($plantilla_id)
    {
        try {
            $data = DB::table('employees')
                ->join('application_status', 'application_status.id', '=', 'employees.application_status_id')
                // ->join('plantillas', 'plantillas.id', '=', 'employees.position_applied_id')
                ->select(
                    'employees.id',
                    DB::raw("CONCAT(employees.first_name,' ',employees.last_name) as name"),
                    'employees.date_applied',
                    'employees.position_applied_id',
                    'application_status.name as status'
                )
                ->orderBy('employees.first_name', 'asc')
                ->where(['employees.is_employee' => false, 'employees.active' => true, 'employees.position_applied_id' => $plantilla_id])
                ->paginate(20);

            return $this->successResponse($data, 'Applicants retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve applicants: ' . $e->getMessage());
        }
    }

    public function add($id, $plantilla_id)
    {

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
        $plantilla_emp = DB::table('plantillas')->where(['employee_id' => $id, 'active' => true]);
        $plantillas = DB::table('plantillas')->where(['employee_id' => 0, 'active' => true])->union($plantilla_emp)->orderBy('code', 'asc')->get();

        $salary_grades = DB::table('salary_grades')->where('active', true)->orderBy('id', 'asc')->get();
        $salary_steps = DB::table('salary_steps')->where('active', true)->orderBy('id', 'asc')->get();
        $blood_types = DB::table('blood_types')->where('active', true)->orderBy('id', 'asc')->get();
        $payroll_intervals = DB::table('payroll_intervals')->where('active', true)->orderBy('id', 'asc')->get();
        $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();
        $learnings = DB::table('learnings')->where('active', true)->orderBy('name', 'asc')->get();

        $plantilla = DB::table('plantillas')
            ->join('positions', 'positions.id', '=', 'plantillas.position_id')
            ->select('plantillas.id', 'positions.name as position', 'positions.description as description')
            ->where('plantillas.id', $plantilla_id)
            ->get();

        if ($id == 0) {
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
                    'employment_type_id' => 0,
                    'position_id' => 0,
                    'plantilla_id' => 0,
                    'is_plantilla' => false,
                    'is_employee' => false,
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
                    'end_date' => ''
                );

            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', 0)->get();

            $employee_info = (object) $dummy_employee_info;
            $employee_info = collect([$employee_info]);
        } else {

            $employee_info = DB::table('employees')
                ->where('id', $id)
                ->orderBy('employees.first_name', 'asc')
                ->get();

            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', 0)->get();
        }

        $children = DB::table('employee_children')->where('employee_id', $id)->get();
        $educations = DB::table('employee_educations')->where('employee_id', $id)->get();
        $service_records = DB::table('service_records')->where('employee_id', $id)->get();
        $employments = DB::table('employee_employment_records')->where('employee_id', $id)->get();
        $examinations = DB::table('employee_examinations')->where('employee_id', $id)->get();
        $trainings = DB::table('employee_trainings')->where('employee_id', $id)->get();
        $organizations = DB::table('employee_organizations')->where('employee_id', $id)->get();
        $recognitions = DB::table('employee_recognations')->where('employee_id', $id)->get();
        $skills = DB::table('employee_skills')->where('employee_id', $id)->get();
        $memberships = DB::table('employee_memberships')->where('employee_id', $id)->get();
        $references = DB::table('employee_references')->where('employee_id', $id)->get();

        return $this->successResponse([
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
            'plantilla' => $plantilla
        ], 'Applicant add form data loaded successfully');
    }

    public function info($id, $plantilla_id)
    {
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
        $plantilla_emp = DB::table('plantillas')->where(['employee_id' => $id, 'active' => true]);
        $plantillas = DB::table('plantillas')->where(['employee_id' => 0, 'active' => true])->union($plantilla_emp)->orderBy('code', 'asc')->get();

        $salary_grades = DB::table('salary_grades')->where('active', true)->orderBy('id', 'asc')->get();
        $salary_steps = DB::table('salary_steps')->where('active', true)->orderBy('id', 'asc')->get();
        $blood_types = DB::table('blood_types')->where('active', true)->orderBy('id', 'asc')->get();
        $payroll_intervals = DB::table('payroll_intervals')->where('active', true)->orderBy('id', 'asc')->get();
        $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();
        $learnings = DB::table('learnings')->where('active', true)->orderBy('name', 'asc')->get();

        $plantilla = DB::table('plantillas')
            ->join('positions', 'positions.id', '=', 'plantillas.position_id')
            ->select('plantillas.id', 'positions.name as position', 'positions.description as description')
            ->where('plantillas.id', $plantilla_id)
            ->get();

        if ($id == 0) {
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
                    'employment_type_id' => 0,
                    'position_id' => 0,
                    'plantilla_id' => 0,
                    'is_plantilla' => false,
                    'is_employee' => false,
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
                    'end_date' => ''
                );

            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', 0)->get();

            $employee_info = (object) $dummy_employee_info;
            $employee_info = collect([$employee_info]);
        } else {

            $employee_info = DB::table('employees')
                ->where('id', $id)
                ->orderBy('employees.first_name', 'asc')
                ->get();

            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', $plantilla_id)->get();
        }

        $children = DB::table('employee_children')->where('employee_id', $id)->get();
        $educations = DB::table('employee_educations')->where('employee_id', $id)->get();
        $service_records = DB::table('service_records')->where('employee_id', $id)->get();
        $employments = DB::table('employee_employment_records')->where('employee_id', $id)->get();
        $examinations = DB::table('employee_examinations')->where('employee_id', $id)->get();
        $trainings = DB::table('employee_trainings')->where('employee_id', $id)->get();
        $organizations = DB::table('employee_organizations')->where('employee_id', $id)->get();
        $recognitions = DB::table('employee_recognations')->where('employee_id', $id)->get();
        $skills = DB::table('employee_skills')->where('employee_id', $id)->get();
        $memberships = DB::table('employee_memberships')->where('employee_id', $id)->get();
        $references = DB::table('employee_references')->where('employee_id', $id)->get();

        return $this->successResponse([
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
            'plantilla' => $plantilla
        ], 'Applicant info data loaded successfully');
    }

    public function store(Request $request, $id, $plantilla_id)
    {
        try {
            // Validations
            $request->validate([
                'photo' => 'image|max:3000',
                'email' => 'required|unique:employees,email' . ($id ? ",$id" : ''),
                'name_prefix_id' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'birthdate' => 'required',
                'gender_id' => 'required',
                'civil_status_id' => 'required',
                'citizenship_id' => 'required',
                'religion_id' => 'required',
            ]);

            if ($id == 0) {
                $same_person = DB::table('employees')->where(['first_name' => $request->first_name, 'last_name' => $request->last_name, 'birthdate' => $request->birthdate])->count();
                if ($same_person > 0) {
                    return $this->errorResponse('Same person already exist in the database!');
                }
            }


            if ($request->hasFile('photo')) {
                $image_file = $request->photo;
                $image = Image::make($image_file);

                Response::make($image->encode('jpeg'));

                // Employee Infor Insert
                $employee_info = array(
                    // basic info
                    'photo' => base64_encode($image),
                    'employee_no' => "APP - " . random_int(100000, 999999),
                    'access_no' => random_int(100000, 999999),
                    'email' => $request->email,
                    'mobile_no' => $request->mobile_no,
                    'telephone_no' => $request->telephone_no,
                    'tin_no' => $request->tin_no,
                    'gsis_no' => $request->gsis_no,
                    'sss_no' => $request->sss_no,
                    'pagibig_no' => $request->pagibig_no,
                    'philhealth_no' => $request->philhealth_no,
                    'name_prefix_id' => $request->name_prefix_id,
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_name,
                    'last_name' => $request->last_name,
                    'name_suffix_id' => $request->name_suffix_id,
                    'birth_place' => $request->birth_place,
                    'birthdate' => $request->birthdate,
                    'age' => $request->age,
                    'height' => $request->height,
                    'weight' => $request->weight,
                    'gender_id' => $request->gender_id,
                    'civil_status_id' => $request->civil_status_id,
                    'citizenship_id' => $request->citizenship_id,
                    'religion_id' => $request->religion_id,
                    'blood_type_id' => $request->blood_type_id,
                    // address info
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
                    // family info
                    'father_name_prefix_id' => $request->father_name_prefix_id,
                    'father_first_name' => $request->father_first_name,
                    'father_middle_name' => $request->father_middle_name,
                    'father_last_name' => $request->father_last_name,
                    'father_name_suffix_id' => $request->father_name_suffix_id,
                    'mother_name_prefix_id' => $request->mother_name_prefix_id,
                    'mother_first_name' => $request->mother_first_name,
                    'mother_middle_name' => $request->mother_middle_name,
                    'mother_last_name' => $request->mother_last_name,
                    'mother_name_suffix_id' => $request->mother_name_suffix_id,
                    'spouse_name_prefix_id' => $request->spouse_name_prefix_id,
                    'spouse_first_name' => $request->spouse_first_name,
                    'spouse_middle_name' => $request->spouse_middle_name,
                    'spouse_last_name' => $request->spouse_last_name,
                    'spouse_name_suffix_id' => $request->spouse_name_suffix_id,
                    'spouse_occupation' => $request->spouse_occupation,
                    'spouse_employer' => $request->spouse_employer,
                    'spouse_business_address' => $request->spouse_business_address,

                    // dual citizenship info
                    'is_dual_citizent' => $request->has('is_dual_citizent') ? true : false,
                    'by_birth' => $request->customRadio == 'by_birth' ? true : false,
                    'by_naturalization' => $request->customRadio == 'by_nat' ? true : false,
                    'indicate_country' => $request->indicate_country,
                    // work info
                    'position_applied_id' => $plantilla_id,
                    'date_applied' => now(),
                    'application_status_id' => 1,
                    'is_plantilla' => false,
                    'is_teaching' => false,

                    // default data
                    'active' => true,
                    'is_employee' => false,
                );
            } else {
                // Employee Infor Insert
                $employee_info = array(
                    // basic info
                    'employee_no' => "APP - " . random_int(100000, 999999),
                    'access_no' => random_int(100000, 999999),
                    'email' => $request->email,
                    'mobile_no' => $request->mobile_no,
                    'telephone_no' => $request->telephone_no,
                    'tin_no' => $request->tin_no,
                    'gsis_no' => $request->gsis_no,
                    'sss_no' => $request->sss_no,
                    'pagibig_no' => $request->pagibig_no,
                    'philhealth_no' => $request->philhealth_no,
                    'name_prefix_id' => $request->name_prefix_id,
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_name,
                    'last_name' => $request->last_name,
                    'name_suffix_id' => $request->name_suffix_id,
                    'birth_place' => $request->birth_place,
                    'birthdate' => $request->birthdate,
                    'age' => $request->age,
                    'height' => $request->height,
                    'weight' => $request->weight,
                    'gender_id' => $request->gender_id,
                    'civil_status_id' => $request->civil_status_id,
                    'citizenship_id' => $request->citizenship_id,
                    'religion_id' => $request->religion_id,
                    'blood_type_id' => $request->blood_type_id,
                    // address info
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
                    // family info
                    'father_name_prefix_id' => $request->father_name_prefix_id,
                    'father_first_name' => $request->father_first_name,
                    'father_middle_name' => $request->father_middle_name,
                    'father_last_name' => $request->father_last_name,
                    'father_name_suffix_id' => $request->father_name_suffix_id,
                    'mother_name_prefix_id' => $request->mother_name_prefix_id,
                    'mother_first_name' => $request->mother_first_name,
                    'mother_middle_name' => $request->mother_middle_name,
                    'mother_last_name' => $request->mother_last_name,
                    'mother_name_suffix_id' => $request->mother_name_suffix_id,
                    'spouse_name_prefix_id' => $request->spouse_name_prefix_id,
                    'spouse_first_name' => $request->spouse_first_name,
                    'spouse_middle_name' => $request->spouse_middle_name,
                    'spouse_last_name' => $request->spouse_last_name,
                    'spouse_name_suffix_id' => $request->spouse_name_suffix_id,
                    'spouse_occupation' => $request->spouse_occupation,
                    'spouse_employer' => $request->spouse_employer,
                    'spouse_business_address' => $request->spouse_business_address,
                    // dual citizenship info
                    'is_dual_citizent' => $request->has('is_dual_citizent') ? true : false,
                    'by_birth' => $request->customRadio == 'by_birth' ? true : false,
                    'by_naturalization' => $request->customRadio == 'by_nat' ? true : false,
                    'indicate_country' => $request->indicate_country,
                    // work info
                    'position_applied_id' => $plantilla_id,
                    'date_applied' => now(),
                    'application_status_id' => 1,
                    'is_plantilla' => true,
                    'is_teaching' => false,
                    'plantilla_id' => $request->$plantilla_id,
                    // default data
                    'active' => true,
                    'is_employee' => false,
                );
            }

            if ($id == 0) {
                $id = 0 + DB::table('employees')->max('id');
                $id += 1;
            }

            DB::table('employees')->updateOrInsert(['id' => $id], $employee_info);

            // Update plantilla
            if ($request->has('is_plantilla')) {

                $plantilla_data_reset = array(
                    'employee_id' => 0
                );

                DB::table('plantillas')->where('employee_id', $id)->update($plantilla_data_reset);

                $plantilla_data = array(
                    'employee_id' => $id
                );

                DB::table('plantillas')->where('id', $request->plantilla_id)->update($plantilla_data);
            } else {
                $plantilla_data = array(
                    'employee_id' => 0
                );

                DB::table('plantillas')->where('employee_id', $id)->update($plantilla_data);
            }

            // Save Children
            $data_child = $request->all();
            $arr_len_children = count($data_child["child_name"]);
            $children_data = [];

            for ($i = 0; $i < $arr_len_children; $i++) {
                if ($data_child["child_name"][$i] != NULL) {

                    if ($data_child["children_id"][$i] == null) {
                        $child_id = 0 + DB::table('employee_children')->max('children_id');
                        $child_id += 1;
                    } else {
                        $child_id = $data_child["children_id"][$i];
                    }

                    $children_data = [
                        'employee_id' => $id,
                        'child_name' => $data_child["child_name"][$i],
                        'child_birthdate' => $data_child["child_birthdate"][$i]
                    ];

                    DB::table('employee_children')->updateOrInsert(['children_id' => $child_id], $children_data);
                }
            }

            // Save Education
            $data_educ = $request->all();
            $arr_len_educ = count($data_educ["school_name"]);
            $educ_data = [];

            for ($i = 0; $i < $arr_len_educ; $i++) {
                if ($data_educ["school_name"][$i] != NULL) {

                    if ($data_educ["education_id"][$i] == null) {
                        $educ_id = 0 + DB::table('employee_educations')->max('education_id');
                        $educ_id += 1;
                    } else {
                        $educ_id = $data_educ["education_id"][$i];
                    }

                    $educ_data = [
                        'employee_id' => $id,
                        'school_name' => $data_educ["school_name"][$i],
                        'academic_level_id' => $data_educ["academic_level_id"][$i],
                        'program' => $data_educ["program"][$i],
                        'from' => $data_educ["from"][$i],
                        'to' => $data_educ["to"][$i],
                        'graduated_year' => $data_educ["graduated_year"][$i],
                        'units_earned' => $data_educ["units_earned"][$i],
                        'honors' => $data_educ["honors"][$i]
                    ];

                    DB::table('employee_educations')->updateOrInsert(['education_id' => $educ_id], $educ_data);
                }
            }

            // Save Employment Records
            $data_emp = $request->all();
            $arr_len_emp = count($data_emp["work_company"]);
            $emp_data = [];

            for ($i = 0; $i < $arr_len_emp; $i++) {
                if ($data_emp["work_company"][$i] != NULL) {

                    if ($data_emp["employment_record_id"][$i] == null) {
                        $emp_id = 0 + DB::table('employee_employment_records')->max('employment_record_id');
                        $emp_id += 1;
                    } else {
                        $emp_id = $data_emp["employment_record_id"][$i];
                    }

                    $emp_data = [
                        'employee_id' => $id,
                        'work_start_date' => $data_emp["work_start_date"][$i],
                        'work_end_date' => $data_emp["work_end_date"][$i],
                        'work_company' => $data_emp["work_company"][$i],
                        'monthly_salary' => $data_emp["monthly_salary"][$i],
                        'salary_grade_step' => $data_emp["salary_grade_step"][$i],
                        'status_of_appointment' => $data_emp["status_of_appointment"][$i],
                        'position' => $data_emp["position"][$i],
                        'government_service_id' => $data_emp["government_service_id"][$i],
                    ];

                    DB::table('employee_employment_records')->updateOrInsert(['employment_record_id' => $emp_id], $emp_data);
                }
            }

            // Save Eaminations
            $data_exam = $request->all();
            $arr_len_exam = count($data_exam["place_of_exam"]);
            $exam_data = [];

            for ($i = 0; $i < $arr_len_exam; $i++) {
                if ($data_exam["place_of_exam"][$i] != NULL) {

                    if ($data_exam["examination_id"][$i] == null) {
                        $exam_id = 0 + DB::table('employee_examinations')->max('examination_id');
                        $exam_id += 1;
                    } else {
                        $exam_id = $data_exam["examination_id"][$i];
                    }

                    $exam_data = [
                        'employee_id' => $id,
                        'eligibility_id' => $data_exam["eligibility_id"][$i],
                        'exam_rating' => $data_exam["exam_rating"][$i],
                        'exam_date' => $data_exam["exam_date"][$i],
                        'place_of_exam' => $data_exam["place_of_exam"][$i],
                        'license_number' => $data_exam["license_number"][$i],
                        'date_released' => $data_exam["date_released"][$i],
                    ];

                    DB::table('employee_examinations')->updateOrInsert(['examination_id' => $exam_id], $exam_data);
                }
            }

            // Save Training
            $data_training = $request->all();
            $arr_len_training = count($data_training["training"]);
            $training_data = [];

            for ($i = 0; $i < $arr_len_training; $i++) {
                if ($data_training["training"][$i] != NULL) {

                    if ($data_training["training_id"][$i] == null) {
                        $training_id = 0 + DB::table('employee_trainings')->max('training_id');
                        $training_id += 1;
                    } else {
                        $training_id = $data_training["training_id"][$i];
                    }

                    $training_data = [
                        'employee_id' => $id,
                        'training' => $data_training["training"][$i],
                        'training_from' => $data_training["training_from"][$i],
                        'training_to' => $data_training["training_to"][$i],
                        'hours' => $data_training["hours"][$i],
                        'sponsored_by' => $data_training["sponsored_by"][$i],
                        'learning_id' => $data_training["learning_id"][$i],
                    ];

                    DB::table('employee_trainings')->updateOrInsert(['training_id' => $training_id], $training_data);
                }
            }

            // Save Organizations
            $data_org = $request->all();
            $arr_len_org = count($data_org["organization"]);
            $org_data = [];

            for ($i = 0; $i < $arr_len_org; $i++) {
                if ($data_org["organization"][$i] != NULL) {

                    if ($data_org["organization_id"][$i] == null) {
                        $organization_id = 0 + DB::table('employee_organizations')->max('organization_id');
                        $organization_id += 1;
                    } else {
                        $organization_id = $data_org["organization_id"][$i];
                    }

                    $org_data = [
                        'employee_id' => $id,
                        'organization' => $data_org["organization"][$i],
                        'org_from' => $data_org["org_from"][$i],
                        'org_to' => $data_org["org_to"][$i],
                        'org_hours' => $data_org["org_hours"][$i],
                        'org_position' => $data_org["org_position"][$i],
                    ];

                    DB::table('employee_organizations')->updateOrInsert(['organization_id' => $organization_id], $org_data);
                }
            }

            // Save Recognitions
            $data_recog = $request->all();
            $arr_len_recog = count($data_recog["recognation"]);
            $recog_data = [];

            for ($i = 0; $i < $arr_len_recog; $i++) {
                if ($data_recog["recognation"][$i] != NULL) {

                    if ($data_recog["recognation_id"][$i] == null) {
                        $recognation_id = 0 + DB::table('employee_recognations')->max('recognation_id');
                        $recognation_id += 1;
                    } else {
                        $recognation_id = $data_recog["recognation_id"][$i];
                    }

                    $recog_data = [
                        'employee_id' => $id,
                        'recognation' => $data_recog["recognation"][$i],
                    ];

                    DB::table('employee_recognations')->updateOrInsert(['recognation_id' => $recognation_id], $recog_data);
                }
            }

            // Save Skills
            $data_skill = $request->all();
            $arr_len_skill = count($data_skill["skill"]);
            $skill_data = [];

            for ($i = 0; $i < $arr_len_skill; $i++) {
                if ($data_skill["skill"][$i] != NULL) {

                    if ($data_skill["skill_id"][$i] == null) {
                        $skill_id = 0 + DB::table('employee_skills')->max('skill_id');
                        $skill_id += 1;
                    } else {
                        $skill_id = $data_skill["skill_id"][$i];
                    }

                    $skill_data = [
                        'employee_id' => $id,
                        'skill' => $data_skill["skill"][$i],
                    ];

                    DB::table('employee_skills')->updateOrInsert(['skill_id' => $skill_id], $skill_data);
                }
            }

            // Save Memberships
            $data_mem = $request->all();
            $arr_len_mem = count($data_mem["membership"]);
            $mem_data = [];

            for ($i = 0; $i < $arr_len_mem; $i++) {
                if (
                    $data_mem["membership"][$i] != NULL
                ) {

                    if ($data_mem["membership_id"][$i] == null) {
                        $membership_id = 0 + DB::table('employee_memberships')->max('membership_id');
                        $membership_id += 1;
                    } else {
                        $membership_id = $data_mem["membership_id"][$i];
                    }

                    $mem_data = [
                        'employee_id' => $id,
                        'membership' => $data_mem["membership"][$i],
                    ];

                    DB::table('employee_memberships')->updateOrInsert(['membership_id' => $membership_id], $mem_data);
                }
            }

            // Save References
            $data_ref = $request->all();
            $arr_len_ref = count($data_ref["ref_name"]);
            $ref_data = [];

            for ($i = 0; $i < $arr_len_ref; $i++) {
                if (
                    $data_ref["ref_name"][$i] != NULL
                ) {

                    if ($data_ref["reference_id"][$i] == null) {
                        $reference_id = 0 + DB::table('employee_references')->max('reference_id');
                        $reference_id += 1;
                    } else {
                        $reference_id = $data_ref["reference_id"][$i];
                    }

                    $ref_data = [
                        'employee_id' => $id,
                        'ref_name' => $data_ref["ref_name"][$i],
                        'ref_address' => $data_ref["ref_address"][$i],
                        'ref_occupation' => $data_ref["ref_occupation"][$i],
                        'ref_contact_no' => $data_ref["ref_contact_no"][$i],
                    ];

                    DB::table('employee_references')->updateOrInsert(['reference_id' => $reference_id], $ref_data);
                }
            }

            return $this->successResponse([
                'employee_id' => $id,
                'plantilla_id' => $plantilla_id,
                'action' => 'application_submitted'
            ], 'Application submitted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to store application: ' . $e->getMessage());
        }
    }

    public function notqualified(Request $request, $id)
    {
        try {
            $employee_id = $id;

            // Update Employee Info
            $data_employee = array(
                'application_status_id' => 2,
                'active' => false
            );

            DB::table('employees')->where('id', $employee_id)->update($data_employee);

            $data = DB::table('employees')
                ->select('id', DB::raw("CONCAT(employees.first_name,' ',employees.last_name) as name"))
                ->where('id', $id)
                ->get();

            if ($data->isEmpty()) {
                return $this->errorResponse('Employee not found.', 404);
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'HR Module',
                'menu' => 'Applicant Records',
                'activity' => 'Update status of application',
                'description' => 'Set application status to not qualified ' . $data[0]->name,
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'employee_id' => $id,
                'status' => 'not_qualified',
                'employee_name' => $data[0]->name
            ], 'Application status updated to not qualified successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update application status: ' . $e->getMessage());
        }
    }

    public function willnotproceed(Request $request, $id)
    {
        try {
            $employee_id = $id;

            // Update Employee Info
            $data_employee = array(
                'application_status_id' => 3,
                'active' => false
            );

            DB::table('employees')->where('id', $employee_id)->update($data_employee);

            $data = DB::table('employees')
                ->select('id', DB::raw("CONCAT(employees.first_name,' ',employees.last_name) as name"))
                ->where('id', $id)
                ->get();

            if ($data->isEmpty()) {
                return $this->errorResponse('Employee not found.', 404);
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'HR Module',
                'menu' => 'Applicant Records',
                'activity' => 'Update status of application',
                'description' => 'Set application status to not proceed ' . $data[0]->name,
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'employee_id' => $id,
                'status' => 'will_not_proceed',
                'employee_name' => $data[0]->name
            ], 'Application status updated to will not proceed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update application status: ' . $e->getMessage());
        }
    }

    public function proceed(Request $request, $id)
    {
        try {
            $employee_id = $id;

            // Update Employee Info
            $data_employee = array(
                'application_status_id' => 4,
                'active' => true
            );

            DB::table('employees')->where('id', $employee_id)->update($data_employee);

            $data = DB::table('employees')
                ->select('id', DB::raw("CONCAT(employees.first_name,' ',employees.last_name) as name"))
                ->where('id', $id)
                ->get();

            if ($data->isEmpty()) {
                return $this->errorResponse('Employee not found.', 404);
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'HR Module',
                'menu' => 'Applicant Records',
                'activity' => 'Update status of application',
                'description' => 'Set application status to proceed ' . $data[0]->name,
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'employee_id' => $id,
                'status' => 'proceed',
                'employee_name' => $data[0]->name
            ], 'Application status updated to proceed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update application status: ' . $e->getMessage());
        }
    }

    public function forhiring(Request $request, $id, $plantilla_id)
    {

        $employee_id = $id;

        // Update Employee Info
        $data_employee = array(
            'application_status_id' => 5,
            'active' => true
        );

        DB::table('employees')->where('id', $employee_id)->update($data_employee);

        $data_plantilla = array(
            'status' => "complete",
            'active' => true
        );

        DB::table('plantillas')->where('id', $plantilla_id)->update($data_plantilla);

        $data = DB::table('employees')
            ->select('id', DB::raw("CONCAT(employees.first_name,' ',employees.last_name) as name"))
            ->where('id', $id)
            ->get();

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module' => 'HR Module',
            'menu' => 'Applicant Records',
            'activity' => 'Update status of application',
            'description' => 'Set application status to hiring' . $data[0]->name,
        );

        Audit::create($data_audit);
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
        $plantilla_emp = DB::table('plantillas')->where(['employee_id' => $id, 'active' => true]);
        $plantillas = DB::table('plantillas')->where(['employee_id' => 0, 'active' => true])->union($plantilla_emp)->orderBy('code', 'asc')->get();

        $salary_grades = DB::table('salary_grades')->where('active', true)->orderBy('id', 'asc')->get();
        $salary_steps = DB::table('salary_steps')->where('active', true)->orderBy('id', 'asc')->get();
        $blood_types = DB::table('blood_types')->where('active', true)->orderBy('id', 'asc')->get();
        $payroll_intervals = DB::table('payroll_intervals')->where('active', true)->orderBy('id', 'asc')->get();
        $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();
        $learnings = DB::table('learnings')->where('active', true)->orderBy('name', 'asc')->get();

        if ($id == 0) {
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
                    'end_date' => ''
                );

            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', 0)->get();

            $employee_info = (object) $dummy_employee_info;
            $employee_info = collect([$employee_info]);
        } else {

            $employee_info = DB::table('employees')
                ->where('id', $id)
                ->orderBy('employees.first_name', 'asc')
                ->get();

            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', $employee_info[0]->plantilla_id)->get();
        }

        $children = DB::table('employee_children')->where('employee_id', $id)->get();
        $educations = DB::table('employee_educations')->where('employee_id', $id)->get();
        $service_records = DB::table('service_records')->where('employee_id', $id)->get();
        $employments = DB::table('employee_employment_records')->where('employee_id', $id)->get();
        $examinations = DB::table('employee_examinations')->where('employee_id', $id)->get();
        $trainings = DB::table('employee_trainings')->where('employee_id', $id)->get();
        $organizations = DB::table('employee_organizations')->where('employee_id', $id)->get();
        $recognitions = DB::table('employee_recognations')->where('employee_id', $id)->get();
        $skills = DB::table('employee_skills')->where('employee_id', $id)->get();
        $memberships = DB::table('employee_memberships')->where('employee_id', $id)->get();
        $references = DB::table('employee_references')->where('employee_id', $id)->get();

        return $this->successResponse([
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
            'references' => $references
        ], 'Employee add form data loaded successfully');
    }

    public function delete($type_id, $id)
    {

        if ($type_id == 1) {
            // delete child data
            $data = DB::table('employee_children')
                ->select('children_id as id', 'child_name as name', DB::raw('1 as type_id'))
                ->where('children_id', $id)->get();
        } elseif ($type_id == 2) {
            // delete child data
            $data = DB::table('employee_educations')
                ->select('education_id as id', 'school_name as name', DB::raw('2 as type_id'))
                ->where('education_id', $id)->get();
        } elseif ($type_id == 3) {
            // delete child data
            $data = DB::table('service_records')
                ->select('service_record_id as id', 'designation as name', DB::raw('3 as type_id'))
                ->where('service_record_id', $id)->get();
        } elseif ($type_id == 4) {
            // delete child data
            $data = DB::table('employee_employment_records')
                ->select('employment_record_id as id', 'work_company as name', DB::raw('4 as type_id'))
                ->where('employment_record_id', $id)->get();
        } elseif ($type_id == 5) {
            // delete child data
            $data = DB::table('employee_examinations')
                ->select('examination_id as id', 'place_of_exam as name', DB::raw('5 as type_id'))
                ->where('examination_id', $id)->get();
        } elseif ($type_id == 6) {
            // delete child data
            $data = DB::table('employee_trainings')
                ->select('training_id as id', 'training as name', DB::raw('6 as type_id'))
                ->where('training_id', $id)->get();
        } elseif ($type_id == 7) {
            // delete child data
            $data = DB::table('employee_organizations')
                ->select('organization_id as id', 'organization as name', DB::raw('7 as type_id'))
                ->where('organization_id', $id)->get();
        } elseif ($type_id == 8) {
            // delete child data
            $data = DB::table('employee_recognations')
                ->select('recognation_id as id', 'recognation as name', DB::raw('8 as type_id'))
                ->where('recognation_id', $id)->get();
        } elseif ($type_id == 9) {
            // delete child data
            $data = DB::table('employee_skills')
                ->select('skill_id as id', 'skill as name', DB::raw('9 as type_id'))
                ->where('skill_id', $id)->get();
        } elseif ($type_id == 10) {
            // delete child data
            $data = DB::table('employee_memberships')
                ->select('membership_id as id', 'membership as name', DB::raw('10 as type_id'))
                ->where('membership_id', $id)->get();
        } elseif ($type_id == 11) {
            // delete child data
            $data = DB::table('employee_references')
                ->select('reference_id as id', 'ref_name as name', DB::raw('11 as type_id'))
                ->where('reference_id', $id)->get();
        }

        return $this->successResponse($data, 'Employee delete data retrieved successfully');
    }

    public function destroy($type_id, $id)
    {
        try {
            if ($type_id == 1) {
                DB::table('employee_children')->where('children_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Child table informations.',
                );
            } elseif ($type_id == 2) {
                // delete child data
                DB::table('employee_educations')->where('education_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Education table informations.',
                );
            } elseif ($type_id == 3) {
                // delete child data
                DB::table('service_records')->where('service_record_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Service Record table informations.',
                );
            } elseif ($type_id == 4) {
                // delete child data
                DB::table('employee_employment_records')->where('employment_record_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Employment Record table informations.',
                );
            } elseif ($type_id == 5) {
                // delete child data
                DB::table('employee_examinations')->where('examination_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Eligibility table informations.',
                );
            } elseif ($type_id == 6) {
                // delete child data
                DB::table('employee_trainings')->where('training_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Training table informations.',
                );
            } elseif ($type_id == 7) {
                // delete child data
                DB::table('employee_organizations')->where('organization_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Organization table informations.',
                );
            } elseif ($type_id == 8) {
                // delete child data
                DB::table('employee_recognations')->where('recognation_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Recognition table informations.',
                );
            } elseif ($type_id == 9) {
                // delete child data
                DB::table('employee_skills')->where('skill_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Skill table informations.',
                );
            } elseif ($type_id == 10) {
                // delete child data
                DB::table('employee_memberships')->where('membership_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Membership table informations.',
                );
            } elseif ($type_id == 11) {
                // delete child data
                DB::table('employee_references')->where('reference_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted References table informations.',
                );
            }

            Audit::create($data_audit);

            return $this->successResponse([
                'type_id' => $type_id,
                'id' => $id,
                'action' => 'deleted'
            ], 'Record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete record: ' . $e->getMessage());
        }
    }

    public function register()
    {
        $data = DB::table('plantillas')
            ->join('positions', 'positions.id', '=', 'plantillas.position_id')
            ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
            ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
            ->leftJoin('departments', 'departments.id', '=', 'plantillas.department_id')
            ->select('plantillas.id', 'plantillas.code', 'positions.name as position', 'salary_steps.name as step', 'salary_grades.name as grade', 'departments.name as department', 'plantillas.eligibility as eligibility', 'plantillas.experience as experience', 'plantillas.training as training', 'plantillas.education as education', 'plantillas.unit as unit', 'plantillas.publication_from as publication_from', 'plantillas.publication_to as publication_to', 'plantillas.status as status', 'plantillas.active')
            ->where('plantillas.employee_id', 0)
            ->where('plantillas.publication_from', '<=', now())
            ->where('plantillas.publication_to', '>=', now())
            ->where('plantillas.active', 1)
            ->orderBy('positions.name', 'asc')
            ->get();

        $non_plantillas =
            $non_plantillas = DB::table('non_plantillas as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->join('departments as c', 'a.department_id', '=', 'c.id')
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
                DB::raw(
                    "case when a.status = 0 then 'Inactive' else 'Active' end as status"
                )
            )
            ->where('a.publication_from', '<=', now())
            ->where('a.publication_to', '>=', now())
            ->where('vacant', '>', 0)
            ->where('a.status', 1)
            ->orderBy('b.name', 'asc')
            ->get();

        $genders = DB::table('genders')->where('active', true)->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'plantillas' => $data,
                'non_plantillas' => $non_plantillas,
                'genders' => $genders
            ]
        ]);
    }

    public function register_store(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'birth_date' => 'required',
            'gender' => 'required',
            'age' => 'required',
            'address' => 'required',
            'mobile_no' => 'required',
            'email' => 'required|unique:applicant_headers',
            'email' => 'required|unique:users',
            'resume' => 'required|max:2048' //|mimes:doc,docx,pdf
        ]);

        // same applicant record exist validation.
        $applicant_record = DB::table('applicant_headers')->where([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name
        ])
            ->get();

        if (count($applicant_record) > 0) {
            return $this->errorResponse('Applicant Record already exists.', 400);
        }

        $applicant_no = "APP-" . random_int(100000, 999999);

        if ($request->hasFile('photo')) {
            $image_file = $request->photo;
            $image = Image::make($image_file);

            Response::make($image->encode('jpeg'));
            $image = base64_encode($image);
        } else {
            $image = '';
        }

        // Change selection of position to apply.
        // $data_plantilla = $request->plantilla;
        // $data_non_plantilla = $request->non_plantilla;

        // if ($data_plantilla <> null) {
        //     $arr_len_plantilla = count($data_plantilla);
        // } else {
        //     $arr_len_plantilla = 0;
        // }

        // if ($data_non_plantilla <> null) {
        //     $arr_len_non_plantilla = count($data_non_plantilla);
        // } else {
        //     $arr_len_non_plantilla = 0;
        // }

        // if ($arr_len_plantilla == 0 && $arr_len_non_plantilla == 0) {
        //     return back()->with('error', 'Please select atleast 1 position to apply.');
        // }

        $name = $request->first_name . ' ' . $request->middle_name . ' ' . $request->last_name;
        $username = str_replace(' ', '', $request->last_name) . '.' . str_replace(' ', '', $request->first_name) . '.' . str_replace(' ', '', $request->middle_name);
        $now = new DateTime();
        $password = Str::random(8);

        // insert applicant user account info.
        $user = [
            'name' => $username,
            'email' => $request->email,
            'password' => Hash::make($password),
            'photo' => $image,
            'email_verified_at' => $now->format('Y-m-d H:i:s'),
            'locked' => false,
            'employee_no' => $applicant_no,
            'has_change_password' => false,
            'is_applicant' => true
        ];

        DB::table('users')->insert($user);

        $user_id = DB::table('users')->max('id');

        // insert applicant header data.
        $data = array(
            'applicant_no' => $applicant_no,
            'photo' => $image,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'address' => $request->address,
            'birth_date' => $request->birth_date,
            'age' => $request->age,
            'gender' => $request->gender,
            'mobile_no' => $request->mobile_no,
            'email' => $request->email,
            'employee_no' => $request->employee_no,
            'resume' => '',
            'application_status_id' => 1,
            'application_date' => now(),
            'user_id' => $user_id,
        );

        DB::table('applicant_headers')->insert($data);

        $applicant_id = DB::table('applicant_headers')->max('id');

        // Save Attachments
        if ($request->hasfile('resume')) {
            $allowedfileExtension = ['pdf', 'jpg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
            $files = $request->file('resume');
            $ctr = 0;

            foreach ($files as $file) {
                $file_name = $file->getClientOriginalName();
                $file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'resume\\' . $applicant_no . '_' . $file_name;
                $extension = $file->getClientOriginalExtension();
                $check = in_array($extension, $allowedfileExtension);

                if ($check) {
                    // Save record of attachments to database.
                    $applicant_attachment_data = [
                        'applicant_id' => $applicant_id,
                        'attachment_name' => $file_name,
                        'path' => $file_path
                    ];

                    DB::table('applicant_attachments')->insert($applicant_attachment_data);

                    // Save attachment to path.
                    $request->resume[$ctr]->storeAs('resume', $applicant_no . '_' . $file_name);
                    $ctr++;
                }
            }
        }

        // // insert applicant deatils data.
        // if ($arr_len_plantilla > 0) {
        //     $plantilla_data = $request->all();

        //     $plantilla_data_insert = [];

        //     for ($i = 0; $i < count($plantilla_data['plantilla_id']); $i++) {
        //         if (isset($plantilla_data['plantilla'][$plantilla_data['plantilla_id'][$i]])) {
        //             $plantilla_data_insert = [
        //                 'applicant_id' => $applicant_id,
        //                 'application_status_id' => 1,
        //                 'position_applied_id' =>  $plantilla_data['plantilla_id'][$i],
        //                 'is_plantilla' => true,
        //             ];

        //             DB::table('applicant_details')->insert([$plantilla_data_insert]);
        //         }
        //     }
        // }


        // if ($arr_len_non_plantilla > 0) {
        //     $non_plantilla_data = $request->all();

        //     $non_plantilla_data_insert = [];

        //     for ($i = 0; $i < count($non_plantilla_data['non_plantilla_id']); $i++) {
        //         if (isset($non_plantilla_data['non_plantilla'][$non_plantilla_data['non_plantilla_id'][$i]])) {
        //             $non_plantilla_data_insert = [
        //                 'applicant_id' => $applicant_id,
        //                 'application_status_id' => 1,
        //                 'position_applied_id' => $non_plantilla_data['non_plantilla_id'][$i],
        //                 'is_plantilla' => false,
        //             ];

        //             DB::table('applicant_details')->insert([$non_plantilla_data_insert]);
        //         }
        //     }
        // }

        $user_account = User::where('id', $user_id)->get();

        // send email verification here
        Notification::send($user_account, new EmailUserAccountNotification($user_account, $request->email, $username, $password));

        return $this->successResponse($user_account, 'Vacancy congratulation data retrieved successfully');
    }

    public function applicant_page()
    {
        $app_key = env("APP_KEY", "");

        $applicant = DB::table('applicant_headers as a')
            ->join('genders as b', 'a.gender', '=', 'b.id')
            ->select('a.*', 'b.name as gender')
            ->where('user_id', Auth::user()->id)
            ->get();

        $employee_data = DB::table('employees as a')->where(['employee_no' => $applicant[0]->applicant_no])->get();

        if ($employee_data->isEmpty()) {
            $id = 0;
        } else {
            $id = $employee_data[0]->id;
        }

        if ($id == 0) {
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
                    'salary' => '0',
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
                ->where('id', $id)
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
        $plantilla_emp = DB::table('plantillas')->where(['employee_id' => $id, 'active' => true]);
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

        $vacant_data = DB::table('plantillas')
            ->join('positions', 'positions.id', '=', 'plantillas.position_id')
            ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
            ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
            ->join('departments', 'departments.id', '=', 'plantillas.department_id')
            ->select('plantillas.id', 'plantillas.code', 'positions.name as position', 'salary_steps.name as step', 'salary_grades.name as grade', 'departments.name as department', 'plantillas.eligibility as eligibility', 'plantillas.experience as experience', 'plantillas.training as training', 'plantillas.education as education', 'plantillas.unit as unit', 'plantillas.publication_from as publication_from', 'plantillas.publication_to as publication_to', 'plantillas.status as status', 'plantillas.active')
            ->where('plantillas.employee_id', '=', 0)
            ->where('plantillas.publication_from', '<=', now())
            ->where('plantillas.publication_to', '>=', now())
            ->whereRaw(
                "isnull(plantillas.active,0) = 1 and
                isnull(plantillas.approved,0) = 1 and
                isnull(plantillas.cancelled,0) = 0"
            )
            ->whereNotIn('plantillas.id', function ($query) use ($applicant_id) {
                $query->select('position_applied_id')->from('applicant_details')->where(['is_plantilla' => true, 'applicant_id' => $applicant_id]);
            })
            ->orderBy('positions.name', 'asc')
            ->get();

        $vacant_non_plantillas = DB::table('non_plantillas as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->join('departments as c', 'a.department_id', '=', 'c.id')
            ->leftJoin('employment_types as d', 'a.employee_type_id', '=', 'd.id')
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
                DB::raw(
                    "case when a.status = 0 then 'Inactive' else 'Active' end as status"
                ),
                'd.name as employment_type',
                'a.number_of_months'
            )
            ->where('a.publication_from', '<=', now())
            ->where('a.publication_to', '>=', now())
            ->where('vacant', '>', 0)
            ->where('a.status', 1)
            ->whereNotIn('a.id', function ($query) use ($applicant_id) {
                $query->select('position_applied_id')->from('applicant_details')->where(['is_plantilla' => false, 'applicant_id' => $applicant_id]);
            })
            ->orderBy('b.name', 'asc')
            ->get();

        $children = DB::table('employee_children')->where('employee_id', $id)->get();
        $educations = DB::table('employee_educations')->where('employee_id', $id)->orderBy('employee_educations.graduated_year', 'desc')->get();
        $service_records = DB::table('service_records')->where('employee_id', $id)->get();
        $employments = DB::table('employee_employment_records')->where('employee_id', $id)->get();
        $examinations = DB::table('employee_examinations')->where('employee_id', $id)->get();
        $trainings = DB::table('employee_trainings')->where('employee_id', $id)->get();
        $organizations = DB::table('employee_organizations')->where('employee_id', $id)->get();
        $recognitions = DB::table('employee_recognations')->where('employee_id', $id)->get();
        $skills = DB::table('employee_skills')->where('employee_id', $id)->get();
        $memberships = DB::table('employee_memberships')->where('employee_id', $id)->get();
        $references = DB::table('employee_references')->where('employee_id', $id)->get();
        $dependents = DB::table('employee_dependents')->where('employee_id', $id)->get();
        $documents = DB::table('employee_documents')->where('employee_id', $id)->get();

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
                'a.employee_id' => $id
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
                'a.employee_id' => $id,
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
            ->where('a.employee_id', $id)
            ->orderBy('b.id', 'asc')
            ->get();

        if ($quesionaires->isEmpty()) {
            $quesionaires = DB::table('pds_questionaires')->orderBy('id', 'asc')->get();
        }

        $examination_schedules = DB::table('examination_schedule_header as a')
            ->join('examination_setup_header as b', 'a.exam_id', '=', 'b.id')
            ->join('applicant_examination_headers as c', 'c.exam_schedule_id', '=', 'a.id')
            ->select(
                'a.id',
                'c.applicant_id',
                'a.exam_date_from',
                'a.exam_date_to',
                'a.exam_time_from',
                'a.exam_time_to',
                'b.exam_set',
                'b.exam_duration',
                'b.passing_criteria',
                'c.is_complete',
                'c.date_completed',
                DB::raw("case when (c.is_complete = 0 and c.is_expired = 0 and (a.exam_date_from <= cast(getdate() as date) and a.exam_date_to >= cast(getdate() as date))) then
                                   case when (a.exam_time_from <= cast(getdate() as time(0)) and a.exam_time_to >= cast(getdate() as time(0))) then
                                        'Active'
                                   else
                                        'Expired'
                                   end
                            when (c.is_complete = 0 and c.is_expired = 0 and a.exam_date_to < cast(getdate() as date) and a.exam_time_to < cast(getdate() as time(0))) then 'Expired'
                            when (c.is_complete = 0 and c.is_expired = 0 and a.exam_date_from <= cast(getdate() as date) and a.exam_time_from > cast(getdate() as time(0))) then 'Pending'
                            when (c.is_complete = 1) then 'Completed'
                        else 'Expired' end as status"),
                'c.id as applicant_examination_id',
                DB::raw("cast(getdate() as time(0))"),
                DB::raw("cast(getdate() as date)"),
            )
            ->where([
                'a.posted' => 1,
                'c.applicant_id' => $applicant_id
            ])
            ->get();

        $interview_schedules = DB::table('applicant_interview_headers as a')
            ->join('interview_applicants as b', 'a.id', '=', 'b.interview_id')
            ->join('interview_levels as c', 'a.panel_group_level', '=', 'c.id')
            ->select(
                'a.*',
                'c.interview_level as level',
                DB::raw("case
                              when (b.is_complete_interview = 0 and b.is_cancelled_interview = 0 and (a.start_date <= getdate() and a.end_date >= getdate() or a.start_time <= cast(getdate() as time) and a.end_time >= cast(getdate() as time))) then 'Active'
                              when (b.is_complete_interview = 0 and b.is_cancelled_interview = 0 and a.end_date < getdate() and a.end_time < cast(getdate() as time)) then 'Expired'
                              when (b.is_complete_interview = 0 and b.is_cancelled_interview = 0 and a.start_date <= getdate() and a.start_time > cast(getdate() as time)) then 'Pending'
                              when (b.is_cancelled_interview = 1) then 'Cancelled'
                              when (b.is_complete_interview = 1) then 'Completed'
                         else '' end as status"),
                'b.applicant_id'
            )
            ->where([
                'b.applicant_id' => $applicant_id,
                'a.posted' => true
            ])
            ->get();

        return $this->successResponse([
            'applicant' => $applicant,
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
            'examination_schedules' => $examination_schedules,
            'interview_schedules' => $interview_schedules
        ], 'Applicant page data loaded successfully');
    }

    public function apply($applicant_id, $position_id, $is_plantilla)
    {
        try {
            if ($is_plantilla) {
                $plantilla_data_insert = [
                    'applicant_id' => $applicant_id,
                    'application_status_id' => 1,
                    'position_applied_id' => $position_id,
                    'is_plantilla' => $is_plantilla,
                ];

                DB::table('applicant_details')->insert([$plantilla_data_insert]);
            } else {
                $non_plantilla_data_insert = [
                    'applicant_id' => $applicant_id,
                    'application_status_id' => 1,
                    'position_applied_id' => $position_id,
                    'is_plantilla' => $is_plantilla,
                ];

                DB::table('applicant_details')->insert([$non_plantilla_data_insert]);
            }

            return $this->successResponse([
                'applicant_id' => $applicant_id,
                'position_id' => $position_id,
                'is_plantilla' => $is_plantilla,
                'action' => 'applied'
            ], 'Application submitted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to apply for position: ' . $e->getMessage());
        }
    }

    public function pds_store(Request $request, $id)
    {
        try {
            // Validations
            $request->validate([
                'photo' => 'image|max:3000',
                'employee_no' => 'required|unique:employees,employee_no' . ($id ? ",$id" : ''),
                'email' => 'required|unique:employees,email' . ($id ? ",$id" : ''),
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

            if ($id == 0) {
                $same_person = DB::table('employees')->where(['first_name' => $request->first_name, 'last_name' => $request->last_name, 'birthdate' => $request->birthdate])->count();
                if ($same_person > 0) {
                    return $this->errorResponse('Same person already exist in the database!');
                }
            }

            if (
                $request->height == 0 || $request->height == null || $request->height == ''
            ) {
                $height = 0;
            } else {
                $height = $request->height;
            }

            if (
                $request->weight == 0 || $request->weight == null || $request->weight == ''
            ) {
                $weight = 0;
            } else {
                $weight = $request->weight;
            }

            if ($request->plantilla_id == 0 || $request->plantilla_id == null || $request->plantilla_id == '') {
                $plantilla_id = 0;
            } else {
                $plantilla_id = $request->plantilla_id;
            }

            if ($request->hasFile('photo')) {
                $image_file = $request->photo;
                $image = Image::make($image_file);

                Response::make($image->encode('jpeg'));

                // Employee Infor Insert
                $employee_info = array(
                    // basic info
                    'photo' => base64_encode($image),
                    'employee_no' => $request->employee_no,
                    'access_no' => $request->access_no,
                    'email' => $request->email,
                    'mobile_no' => $request->mobile_no,
                    'telephone_no' => $request->telephone_no,
                    'tin_no' => $request->tin_no,
                    'gsis_no' => $request->gsis_no,
                    'sss_no' => $request->sss_no,
                    'pagibig_no' => $request->pagibig_no,
                    'philhealth_no' => $request->philhealth_no,
                    'name_prefix_id' => $request->name_prefix_id,
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_name,
                    'last_name' => $request->last_name,
                    'name_suffix_id' => $request->name_suffix_id,
                    'birth_place' => $request->birth_place,
                    'birthdate' => $request->birthdate,
                    'age' => $request->age,
                    'height' => $height,
                    'weight' => $weight,
                    'gender_id' => $request->gender_id,
                    'civil_status_id' => $request->civil_status_id,
                    'citizenship_id' => $request->citizenship_id,
                    'religion_id' => $request->religion_id,
                    'blood_type_id' => (isset($request->blood_type_id) || $request->blood_type_id == null) ? 0 : $request->blood_type_id,
                    // address info
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
                    // family info
                    'father_name_prefix_id' => $request->father_name_prefix_id,
                    'father_first_name' => $request->father_first_name,
                    'father_middle_name' => $request->father_middle_name,
                    'father_last_name' => $request->father_last_name,
                    'father_name_suffix_id' => $request->father_name_suffix_id,
                    'mother_name_prefix_id' => $request->mother_name_prefix_id,
                    'mother_first_name' => $request->mother_first_name,
                    'mother_middle_name' => $request->mother_middle_name,
                    'mother_last_name' => $request->mother_last_name,
                    'mother_name_suffix_id' => $request->mother_name_suffix_id,
                    'spouse_name_prefix_id' => $request->spouse_name_prefix_id,
                    'spouse_first_name' => $request->spouse_first_name,
                    'spouse_middle_name' => $request->spouse_middle_name,
                    'spouse_last_name' => $request->spouse_last_name,
                    'spouse_name_suffix_id' => $request->spouse_name_suffix_id,
                    'spouse_occupation' => $request->spouse_occupation,
                    'spouse_employer' => $request->spouse_employer,
                    'spouse_business_address' => $request->spouse_business_address,
                    // dual citizenship info
                    'is_dual_citizent' => $request->has('is_dual_citizent') ? true : false,
                    'by_birth' => $request->customRadio == 'by_birth' ? true : false,
                    'by_naturalization' => $request->customRadio == 'by_nat' ? true : false,
                    'indicate_country' => $request->indicate_country,
                    // work info
                    'company_id' => $request->company_id,
                    'branch_id' => $request->branch_id,
                    'department_id' => $request->department_id,
                    'division_id' => $request->division_id,
                    'section_id' => $request->section_id,
                    'employment_type_id' => $request->employment_type_id,
                    'date_hired' => $request->date_hired,
                    'is_plantilla' => $request->has('is_plantilla') ? true : false,
                    'is_teaching' => $request->has('is_teaching') ? true : false,
                    'plantilla_id' => $plantilla_id,
                    'salary_grade_id' => $request->salary_grade_id,
                    'salary_step_id' => $request->salary_step_id,
                    'position_id' => $request->position_id,
                    'end_date' => $request->end_dates,
                    // payroll related info
                    'payroll_interval_id' => $request->payroll_interval_id,
                    'salary' => $request->salary,
                    'tax_amount' => $request->tax_amount,
                    'gsis_amount' => $request->gsis_amount,
                    'sss_amount' => $request->sss_amount,
                    'pagibig_amount' => $request->pagibig_amount,
                    'philhealth_amount' => $request->philhealth_amount,
                    // default data
                    'active' => true,
                    'is_employee' => false,
                    'application_status_id' => 1,
                    'account_no' => $request->account_no,
                    'updated_at' => now()
                );
            } else {
                // Employee Info Insert
                $employee_info = array(
                    // basic info
                    'employee_no' => $request->employee_no,
                    'access_no' => $request->access_no,
                    'email' => $request->email,
                    'mobile_no' => $request->mobile_no,
                    'telephone_no' => $request->telephone_no,
                    'tin_no' => $request->tin_no,
                    'gsis_no' => $request->gsis_no,
                    'sss_no' => $request->sss_no,
                    'pagibig_no' => $request->pagibig_no,
                    'philhealth_no' => $request->philhealth_no,
                    'name_prefix_id' => $request->name_prefix_id,
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_name,
                    'last_name' => $request->last_name,
                    'name_suffix_id' => $request->name_suffix_id,
                    'birth_place' => $request->birth_place,
                    'birthdate' => $request->birthdate,
                    'age' => $request->age,
                    'height' => $height,
                    'weight' => $weight,
                    'gender_id' => $request->gender_id,
                    'civil_status_id' => $request->civil_status_id,
                    'citizenship_id' => $request->citizenship_id,
                    'religion_id' => $request->religion_id,
                    'blood_type_id' => (isset($request->blood_type_id) || $request->blood_type_id == null) ? 0 : $request->blood_type_id,
                    // address info
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
                    // family info
                    'father_name_prefix_id' => $request->father_name_prefix_id,
                    'father_first_name' => $request->father_first_name,
                    'father_middle_name' => $request->father_middle_name,
                    'father_last_name' => $request->father_last_name,
                    'father_name_suffix_id' => $request->father_name_suffix_id,
                    'mother_name_prefix_id' => $request->mother_name_prefix_id,
                    'mother_first_name' => $request->mother_first_name,
                    'mother_middle_name' => $request->mother_middle_name,
                    'mother_last_name' => $request->mother_last_name,
                    'mother_name_suffix_id' => $request->mother_name_suffix_id,
                    'spouse_name_prefix_id' => $request->spouse_name_prefix_id,
                    'spouse_first_name' => $request->spouse_first_name,
                    'spouse_middle_name' => $request->spouse_middle_name,
                    'spouse_last_name' => $request->spouse_last_name,
                    'spouse_name_suffix_id' => $request->spouse_name_suffix_id,
                    'spouse_occupation' => $request->spouse_occupation,
                    'spouse_employer' => $request->spouse_employer,
                    'spouse_business_address' => $request->spouse_business_address,
                    // dual citizenship info
                    'is_dual_citizent' => $request->has('is_dual_citizent') ? true : false,
                    'by_birth' => $request->customRadio == 'by_birth' ? true : false,
                    'by_naturalization' => $request->customRadio == 'by_nat' ? true : false,
                    'indicate_country' => $request->indicate_country,
                    // work info
                    'company_id' => $request->company_id,
                    'branch_id' => $request->branch_id,
                    'department_id' => $request->department_id,
                    'division_id' => $request->division_id,
                    'section_id' => $request->section_id,
                    'employment_type_id' => $request->employment_type_id,
                    'date_hired' => $request->date_hired,
                    'is_plantilla' => $request->has('is_plantilla') ? true : false,
                    'is_teaching' => $request->has('is_teaching') ? true : false,
                    'plantilla_id' => $plantilla_id,
                    'salary_grade_id' => $request->salary_grade_id,
                    'salary_step_id' => $request->salary_step_id,
                    'position_id' => $request->position_id,
                    'end_date' => $request->end_dates,
                    // payroll related info
                    'payroll_interval_id' => $request->payroll_interval_id,
                    'salary' => $request->salary,
                    'tax_amount' => $request->tax_amount,
                    'gsis_amount' => $request->gsis_amount,
                    'sss_amount' => $request->sss_amount,
                    'pagibig_amount' => $request->pagibig_amount,
                    'philhealth_amount' => $request->philhealth_amount,
                    // default data
                    'active' => true,
                    'is_employee' => false,
                    'application_status_id' => 1,
                    'account_no' => $request->account_no,
                    'updated_at' => now()
                );
            }

            if ($id == 0) {
                $id = 0 + DB::table('employees')->max('id');
                $id += 1;
            }
            DB::unprepared('SET IDENTITY_INSERT employees ON');
            DB::table('employees')->updateOrInsert(['id' => $id], $employee_info);
            DB::unprepared('SET IDENTITY_INSERT employees OFF');
            // Update plantilla
            if ($request->has('is_plantilla')) {

                $plantilla_data_reset = array(
                    'employee_id' => 0
                );

                DB::table('plantillas')->where('employee_id', $id)->update($plantilla_data_reset);

                $plantilla_data = array(
                    'employee_id' => $id
                );

                DB::table('plantillas')->where('id', $request->plantilla_id)->update($plantilla_data);
            } else {
                $plantilla_data = array(
                    'employee_id' => 0
                );

                DB::table('plantillas')->where('employee_id', $id)->update($plantilla_data);
            }

            // Save Children
            $data_child = $request->all();
            $arr_len_children = count($data_child["child_name"]);
            $children_data = [];

            for ($i = 0; $i < $arr_len_children; $i++) {
                if ($data_child["child_name"][$i] != NULL) {

                    if ($data_child["children_id"][$i] == null) {
                        $child_id = 0 + DB::table('employee_children')->max('children_id');
                        $child_id += 1;
                    } else {
                        $child_id = $data_child["children_id"][$i];
                    }

                    $children_data = [
                        'employee_id' => $id,
                        'child_name' => $data_child["child_name"][$i],
                        'child_middlename' => $data_child["child_middlename"][$i],
                        'child_lastname' => $data_child["child_lastname"][$i],
                        'child_birthdate' => $data_child["child_birthdate"][$i]
                    ];

                    DB::table('employee_children')->updateOrInsert(['children_id' => $child_id], $children_data);
                }
            }

            // Save Education
            $data_educ = $request->all();
            $arr_len_educ = count($data_educ["school_name"]);
            $educ_data = [];

            for ($i = 0; $i < $arr_len_educ; $i++) {
                if ($data_educ["school_name"][$i] != NULL) {

                    if ($data_educ["education_id"][$i] == null) {
                        $educ_id = 0 + DB::table('employee_educations')->max('education_id');
                        $educ_id += 1;
                    } else {
                        $educ_id = $data_educ["education_id"][$i];
                    }

                    $educ_data = [
                        'employee_id' => $id,
                        'school_name' => $data_educ["school_name"][$i],
                        'academic_level_id' => $data_educ["academic_level_id"][$i],
                        'program' => $data_educ["program"][$i],
                        'from' => $data_educ["from"][$i],
                        'to' => $data_educ["to"][$i],
                        'graduated_year' => $data_educ["graduated_year"][$i],
                        'units_earned' => $data_educ["units_earned"][$i],
                        'honors' => $data_educ["honors"][$i]
                    ];

                    DB::table('employee_educations')->updateOrInsert(['education_id' => $educ_id], $educ_data);
                }
            }

            // Save Service Records
            $data_serv = $request->all();
            $arr_len_serv = count($data_serv["designation"]);
            $serv_data = [];

            for ($i = 0; $i < $arr_len_serv; $i++) {
                if ($data_serv["designation"][$i] != NULL) {

                    if ($data_serv["service_record_id"][$i] == null) {
                        $serv_id = 0 + DB::table('service_records')->max('service_record_id');
                        $serv_id += 1;
                    } else {
                        $serv_id = $data_serv["service_record_id"][$i];
                    }

                    $serv_data = [
                        'employee_id' => $id,
                        'start_date' => $data_serv["start_date"][$i],
                        'end_date' => $data_serv["end_date"][$i],
                        'designation' => $data_serv["designation"][$i],
                        'employment_type' => $data_serv["employment_type"][$i],
                        'annual_salary' => $data_serv["annual_salary"][$i],
                        'place_of_assignment' => $data_serv["place_of_assignment"][$i],
                        'leave_without_pay' => $data_serv["leave_without_pay"][$i],
                        'separation_date' => $data_serv["separation_date"][$i],
                        'cause' => $data_serv["cause"][$i],
                        'branch' => $data_serv["branch"][$i],
                    ];

                    DB::table('service_records')->updateOrInsert(['service_record_id' => $serv_id], $serv_data);
                }
            }

            // Save Employment Records
            $data_emp = $request->all();
            $arr_len_emp = count($data_emp["work_company"]);
            $emp_data = [];

            for ($i = 0; $i < $arr_len_emp; $i++) {
                if ($data_emp["work_company"][$i] != NULL) {

                    if ($data_emp["employment_record_id"][$i] == null) {
                        $emp_id = 0 + DB::table('employee_employment_records')->max('employment_record_id');
                        $emp_id += 1;
                    } else {
                        $emp_id = $data_emp["employment_record_id"][$i];
                    }

                    $emp_data = [
                        'employee_id' => $id,
                        'work_start_date' => $data_emp["work_start_date"][$i],
                        'work_end_date' => $data_emp["work_end_date"][$i],
                        'work_company' => $data_emp["work_company"][$i],
                        'monthly_salary' => $data_emp["monthly_salary"][$i],
                        'salary_grade_step' => $data_emp["salary_grade_step"][$i],
                        'status_of_appointment' => $data_emp["status_of_appointment"][$i],
                        'position' => $data_emp["position"][$i],
                        'government_service_id' => $data_emp["government_service_id"][$i],
                    ];

                    DB::table('employee_employment_records')->updateOrInsert(['employment_record_id' => $emp_id], $emp_data);
                }
            }

            // Save Eaminations
            $data_exam = $request->all();
            $arr_len_exam = count($data_exam["place_of_exam"]);
            $exam_data = [];

            for ($i = 0; $i < $arr_len_exam; $i++) {
                if ($data_exam["place_of_exam"][$i] != NULL) {

                    if ($data_exam["examination_id"][$i] == null) {
                        $exam_id = 0 + DB::table('employee_examinations')->max('examination_id');
                        $exam_id += 1;
                    } else {
                        $exam_id = $data_exam["examination_id"][$i];
                    }

                    $exam_data = [
                        'employee_id' => $id,
                        'eligibility_id' => $data_exam["eligibility_id"][$i],
                        'exam_rating' => $data_exam["exam_rating"][$i],
                        'exam_date' => $data_exam["exam_date"][$i],
                        'place_of_exam' => $data_exam["place_of_exam"][$i],
                        'license_number' => $data_exam["license_number"][$i],
                        'date_released' => $data_exam["date_released"][$i],
                    ];

                    DB::table('employee_examinations')->updateOrInsert(['examination_id' => $exam_id], $exam_data);
                }
            }

            // Save Training
            $data_training = $request->all();
            $arr_len_training = count($data_training["training"]);
            $training_data = [];

            for ($i = 0; $i < $arr_len_training; $i++) {
                if ($data_training["training"][$i] != NULL) {

                    if ($data_training["training_id"][$i] == null) {
                        $training_id = 0 + DB::table('employee_trainings')->max('training_id');
                        $training_id += 1;
                    } else {
                        $training_id = $data_training["training_id"][$i];
                    }

                    $training_data = [
                        'employee_id' => $id,
                        'training' => $data_training["training"][$i],
                        'training_from' => $data_training["training_from"][$i],
                        'training_to' => $data_training["training_to"][$i],
                        'hours' => $data_training["hours"][$i],
                        'sponsored_by' => $data_training["sponsored_by"][$i],
                        'learning_id' => $data_training["learning_id"][$i],
                    ];

                    DB::table('employee_trainings')->updateOrInsert(['training_id' => $training_id], $training_data);
                }
            }

            // Save Organizations
            $data_org = $request->all();
            $arr_len_org = count($data_org["organization"]);
            $org_data = [];

            for ($i = 0; $i < $arr_len_org; $i++) {
                if ($data_org["organization"][$i] != NULL) {

                    if ($data_org["organization_id"][$i] == null) {
                        $organization_id = 0 + DB::table('employee_organizations')->max('organization_id');
                        $organization_id += 1;
                    } else {
                        $organization_id = $data_org["organization_id"][$i];
                    }

                    $org_data = [
                        'employee_id' => $id,
                        'organization' => $data_org["organization"][$i],
                        'org_from' => $data_org["org_from"][$i],
                        'org_to' => $data_org["org_to"][$i],
                        'org_hours' => $data_org["org_hours"][$i],
                        'org_position' => $data_org["org_position"][$i],
                    ];

                    DB::table('employee_organizations')->updateOrInsert(['organization_id' => $organization_id], $org_data);
                }
            }

            // Save Recognitions
            $data_recog = $request->all();
            $arr_len_recog = count($data_recog["recognation"]);
            $recog_data = [];

            for ($i = 0; $i < $arr_len_recog; $i++) {
                if ($data_recog["recognation"][$i] != NULL) {

                    if ($data_recog["recognation_id"][$i] == null) {
                        $recognation_id = 0 + DB::table('employee_recognations')->max('recognation_id');
                        $recognation_id += 1;
                    } else {
                        $recognation_id = $data_recog["recognation_id"][$i];
                    }

                    $recog_data = [
                        'employee_id' => $id,
                        'recognation' => $data_recog["recognation"][$i],
                    ];

                    DB::table('employee_recognations')->updateOrInsert(['recognation_id' => $recognation_id], $recog_data);
                }
            }

            // Save Skills
            $data_skill = $request->all();
            $arr_len_skill = count($data_skill["skill"]);
            $skill_data = [];

            for ($i = 0; $i < $arr_len_skill; $i++) {
                if ($data_skill["skill"][$i] != NULL) {

                    if ($data_skill["skill_id"][$i] == null) {
                        $skill_id = 0 + DB::table('employee_skills')->max('skill_id');
                        $skill_id += 1;
                    } else {
                        $skill_id = $data_skill["skill_id"][$i];
                    }

                    $skill_data = [
                        'employee_id' => $id,
                        'skill' => $data_skill["skill"][$i],
                    ];

                    DB::table('employee_skills')->updateOrInsert(['skill_id' => $skill_id], $skill_data);
                }
            }

            // Save Memberships
            $data_mem = $request->all();
            $arr_len_mem = count($data_mem["membership"]);
            $mem_data = [];

            for ($i = 0; $i < $arr_len_mem; $i++) {
                if (
                    $data_mem["membership"][$i] != NULL
                ) {

                    if ($data_mem["membership_id"][$i] == null) {
                        $membership_id = 0 + DB::table('employee_memberships')->max('membership_id');
                        $membership_id += 1;
                    } else {
                        $membership_id = $data_mem["membership_id"][$i];
                    }

                    $mem_data = [
                        'employee_id' => $id,
                        'membership' => $data_mem["membership"][$i],
                    ];

                    DB::table('employee_memberships')->updateOrInsert(['membership_id' => $membership_id], $mem_data);
                }
            }

            // Save References
            $data_ref = $request->all();
            $arr_len_ref = count($data_ref["ref_name"]);
            $ref_data = [];

            for ($i = 0; $i < $arr_len_ref; $i++) {
                if (
                    $data_ref["ref_name"][$i] != NULL
                ) {

                    if ($data_ref["reference_id"][$i] == null) {
                        $reference_id = 0 + DB::table('employee_references')->max('reference_id');
                        $reference_id += 1;
                    } else {
                        $reference_id = $data_ref["reference_id"][$i];
                    }

                    $ref_data = [
                        'employee_id' => $id,
                        'ref_name' => $data_ref["ref_name"][$i],
                        'ref_address' => $data_ref["ref_address"][$i],
                        'ref_occupation' => $data_ref["ref_occupation"][$i],
                        'ref_contact_no' => $data_ref["ref_contact_no"][$i],
                        'ref_email' => $data_ref["ref_email"][$i],
                    ];

                    DB::table('employee_references')->updateOrInsert(['reference_id' => $reference_id], $ref_data);
                }
            }

            // Save Dependents
            $data_dep = $request->all();
            $arr_len_dep = count($data_dep["dep_name"]);
            $dep_data = [];

            for ($i = 0; $i < $arr_len_dep; $i++) {
                if (
                    $data_dep["dep_name"][$i] != NULL
                ) {

                    if ($data_dep["dependent_id"][$i] == null) {
                        $dependent_id = 0 + DB::table('employee_dependents')->max('id');
                        $dependent_id += 1;
                    } else {
                        $dependent_id = $data_dep["dependent_id"][$i];
                    }

                    $dep_data = [
                        'employee_id' => $id,
                        'name' => $data_dep["dep_name"][$i],
                        'relationship' => $data_dep["dep_relationship"][$i],
                        'course' => $data_dep["dep_course"][$i],
                    ];
                    DB::unprepared('SET IDENTITY_INSERT employee_dependents ON');
                    DB::table('employee_dependents')->updateOrInsert(['id' => $dependent_id], $dep_data);
                    DB::unprepared('SET IDENTITY_INSERT employee_dependents OFF');
                }
            }

            // Save Documents
            $data_doc = $request->all();
            $arr_len_doc = count($data_doc["document_name"]);
            $doc_data = [];

            for ($i = 0; $i < $arr_len_doc; $i++) {
                if ($data_doc["document_name"][$i] != NULL) {

                    if ($data_doc["document_id"][$i] == null) {
                        $document_id = 0 + DB::table('employee_documents')->max('employee_document_id');
                        $document_id += 1;
                    } else {
                        $document_id = $data_doc["document_id"][$i];
                    }

                    // Save Leave Attachments
                    if ($request->hasFile('document')) {

                        $allowedfileExtension = ['pdf', 'jpg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
                        $files = $request->file('document');

                        if (isset($files[$i])) {
                            $file_name = $files[$i]->getClientOriginalName();
                            $file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'employee_documents\\' . 'DOCS' . $id . '_' . $file_name;
                            $extension = $files[$i]->getClientOriginalExtension();
                            $check = in_array($extension, $allowedfileExtension);

                            if ($check) {
                                // Save record of attachments to database.
                                $doc_data = [
                                    'employee_id' => $id,
                                    'name' => $data_doc["document_name"][$i],
                                    'description' => $data_doc["document_description"][$i],
                                    'attachment_name' => $file_name,
                                    'path' => $file_path,
                                    'extension' => $extension
                                ];

                                DB::unprepared('SET IDENTITY_INSERT employee_documents ON');
                                DB::table('employee_documents')->updateOrInsert(['employee_document_id' => $document_id], $doc_data);
                                DB::unprepared('SET IDENTITY_INSERT employee_documents OFF');

                                // Save attachment to path.
                                $request->document[$i]->storeAs('employee_documents', 'DOCS' . $id . '_' . $file_name);
                            }
                        }
                    }
                }
            }

            // Save Questionaires
            $data_question = $request->all();
            $arr_len_question = count($data_question["question_id"]);
            $question_data = [];

            // dd($data_question);

            DB::table('employee_pds_answers')->where('employee_id', $id)->delete();

            for ($i = 0; $i < $arr_len_question; $i++) {
                if ($data_question["question_id"][$i] != NULL) {
                    $question_data = [
                        'employee_id' => $id,
                        'question_id' => $data_question["question_id"][$i],
                        'is_yes' => isset($data_question['is_yes'][$data_question['question_id'][$i]]) ? true : false,
                        'is_no' => isset($data_question['is_no'][$data_question['question_id'][$i]]) ? true : false,
                        'yes_details' => $data_question["yes_details"][$i],
                        'date_filed' => $data_question["date_filed"][$i],
                        'case_status' => $data_question["case_status"][$i],
                    ];

                    DB::table('employee_pds_answers')->insert($question_data);
                }
            }

            //Save audit trail
            if ($request->id == 0) {

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Records',
                    'activity' => 'Add',
                    'description' => 'Added employee informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse([
                    'employee_id' => $request->id,
                    'action' => 'added'
                ], 'Employee information added successfully');
            } else {

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Records',
                    'activity' => 'Update',
                    'description' => 'Updated employee informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse([
                    'employee_id' => $request->id,
                    'action' => 'updated'
                ], 'Employee information updated successfully');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to store employee information: ' . $e->getMessage());
        }
    }

    public function pds_delete($type_id, $id)
    {

        if ($type_id == 1) {
            // delete child data
            $data = DB::table('employee_children')
                ->select('children_id as id', 'child_name as name', DB::raw('1 as type_id'))
                ->where('children_id', $id)->get();
        } elseif ($type_id == 2) {
            // delete child data
            $data = DB::table('employee_educations')
                ->select('education_id as id', 'school_name as name', DB::raw('2 as type_id'))
                ->where('education_id', $id)->get();
        } elseif ($type_id == 3) {
            // delete child data
            $data = DB::table('service_records')
                ->select('service_record_id as id', 'designation as name', DB::raw('3 as type_id'))
                ->where('service_record_id', $id)->get();
        } elseif ($type_id == 4) {
            // delete child data
            $data = DB::table('employee_employment_records')
                ->select('employment_record_id as id', 'work_company as name', DB::raw('4 as type_id'))
                ->where('employment_record_id', $id)->get();
        } elseif ($type_id == 5) {
            // delete child data
            $data = DB::table('employee_examinations')
                ->select('examination_id as id', 'place_of_exam as name', DB::raw('5 as type_id'))
                ->where('examination_id', $id)->get();
        } elseif ($type_id == 6) {
            // delete child data
            $data = DB::table('employee_trainings')
                ->select('training_id as id', 'training as name', DB::raw('6 as type_id'))
                ->where('training_id', $id)->get();
        } elseif ($type_id == 7) {
            // delete child data
            $data = DB::table('employee_organizations')
                ->select('organization_id as id', 'organization as name', DB::raw('7 as type_id'))
                ->where('organization_id', $id)->get();
        } elseif ($type_id == 8) {
            // delete child data
            $data = DB::table('employee_recognations')
                ->select('recognation_id as id', 'recognation as name', DB::raw('8 as type_id'))
                ->where('recognation_id', $id)->get();
        } elseif ($type_id == 9) {
            // delete child data
            $data = DB::table('employee_skills')
                ->select('skill_id as id', 'skill as name', DB::raw('9 as type_id'))
                ->where('skill_id', $id)->get();
        } elseif ($type_id == 10) {
            // delete child data
            $data = DB::table('employee_memberships')
                ->select('membership_id as id', 'membership as name', DB::raw('10 as type_id'))
                ->where('membership_id', $id)->get();
        } elseif ($type_id == 11) {
            // delete child data
            $data = DB::table('employee_references')
                ->select('reference_id as id', 'ref_name as name', DB::raw('11 as type_id'))
                ->where('reference_id', $id)->get();
        } elseif ($type_id == 12) {
            // delete child data
            $data = DB::table('employee_dependents')
                ->select('id', 'name', DB::raw('12 as type_id'))
                ->where('id', $id)->get();
        } elseif ($type_id == 13) {
            // delete child data
            $data = DB::table('employee_documents')
                ->select('employee_document_id as id', 'name', DB::raw('13 as type_id'))
                ->where('employee_document_id', $id)->get();
        }

        return $this->successResponse($data, 'Employee delete data retrieved successfully');
    }

    public function pds_destroy($type_id, $id)
    {
        try {
            if ($type_id == 1) {
                DB::table('employee_children')->where('children_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Child table informations.',
                );
            } elseif ($type_id == 2) {
                // delete child data
                DB::table('employee_educations')->where('education_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Education table informations.',
                );
            } elseif ($type_id == 3) {
                // delete child data
                DB::table('service_records')->where('service_record_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Service Record table informations.',
                );
            } elseif ($type_id == 4) {
                // delete child data
                DB::table('employee_employment_records')->where('employment_record_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Employment Record table informations.',
                );
            } elseif ($type_id == 5) {
                // delete child data
                DB::table('employee_examinations')->where('examination_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Eligibility table informations.',
                );
            } elseif ($type_id == 6) {
                // delete child data
                DB::table('employee_trainings')->where('training_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Training table informations.',
                );
            } elseif ($type_id == 7) {
                // delete child data
                DB::table('employee_organizations')->where('organization_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Organization table informations.',
                );
            } elseif ($type_id == 8) {
                // delete child data
                DB::table('employee_recognations')->where('recognation_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Recognition table informations.',
                );
            } elseif ($type_id == 9) {
                // delete child data
                DB::table('employee_skills')->where('skill_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Skill table informations.',
                );
            } elseif ($type_id == 10) {
                // delete child data
                DB::table('employee_memberships')->where('membership_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Membership table informations.',
                );
            } elseif ($type_id == 11) {
                // delete child data
                DB::table('employee_references')->where('reference_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted References table informations.',
                );
            } elseif ($type_id == 12) {
                // delete child data
                DB::table('employee_dependents')->where('id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Dependents table informations.',
                );
            } elseif ($type_id == 13) {
                // delete child data
                DB::table('employee_documents')->where('employee_document_id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'HR Module',
                    'menu' => 'Employee Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Document table informations.',
                );
            }

            Audit::create($data_audit);

            return $this->successResponse([
                'type_id' => $type_id,
                'id' => $id,
                'action' => 'deleted'
            ], 'Record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete record: ' . $e->getMessage());
        }
    }

    public function download($id)
    {
        try {
            $documents = DB::table('employee_documents as a')
                ->select(
                    'a.employee_id',
                    'a.attachment_name'
                )
                ->where('a.employee_document_id', $id)
                ->get();

            if ($documents->isEmpty()) {
                return $this->errorResponse('Document not found.', 404);
            }

            $pathToFile = storage_path('app/employee_documents/' . 'DOCS' . $documents[0]->employee_id . '_' . $documents[0]->attachment_name);

            if (!file_exists($pathToFile)) {
                return $this->errorResponse('File not found on server.', 404);
            }

            // Return file info in JSON instead of direct download
            return $this->successResponse([
                'file_path' => $pathToFile,
                'file_name' => $documents[0]->attachment_name,
                'employee_id' => $documents[0]->employee_id,
                'download_url' => url('/api/download-document/' . $id)
            ], 'File information retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve file information: ' . $e->getMessage());
        }
    }

    public function examIntro($id)
    {
        $applicant = DB::table('applicant_headers as a')
            ->join('genders as b', 'a.gender', '=', 'b.id')
            ->select('a.*', 'b.name as gender')
            ->where('user_id', Auth::user()->id)
            ->get();

        $applicant_id = $applicant[0]->id;

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
                'd.description'
            )
            ->where([
                'a.posted' => 1,
                'c.applicant_id' => $applicant_id,
                'a.id' => $id
            ])
            ->get();

        $exam_sub_categories = DB::table('exam_sub_categories as a')
            ->join('exam_difficulty_levels as b', 'a.difficulty_level', '=', 'b.id')
            ->select(
                'a.sub_category',
                'b.difficulty_level',
                'a.existing_questions'
            )
            ->where('a.category_id', $exam[0]->category_id)
            ->get();

        return $this->successResponse([
            'applicant' => $applicant,
            'exam' => $exam,
            'exam_sub_categories' => $exam_sub_categories
        ], 'Applicant examination intro data loaded successfully');
    }

    public function examPage($id)
    {
        $applicant = DB::table('applicant_headers as a')
            ->join('genders as b', 'a.gender', '=', 'b.id')
            ->select('a.*', 'b.name as gender')
            ->where('user_id', Auth::user()->id)
            ->get();

        $applicant_id = $applicant[0]->id;

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
                DB::raw("ISNULL(c.last_duration,0) as last_duration")
            )
            ->where([
                'a.posted' => 1,
                'c.applicant_id' => $applicant_id,
                'a.id' => $id
            ])
            ->get();

        $exam_sub_categories = DB::table('exam_sub_categories as a')
            ->join('exam_difficulty_levels as b', 'a.difficulty_level', '=', 'b.id')
            ->join('exam_questionaire_headers as c', 'a.id', '=', 'c.sub_category_id')
            ->select(
                'a.id',
                'a.sub_category',
                'b.difficulty_level',
                'a.existing_questions'
            )
            ->where('a.category_id', $exam[0]->category_id)
            ->distinct()
            ->get();

        $exam_questions = DB::table('exam_sub_categories as a')
            ->join('exam_difficulty_levels as b', 'a.difficulty_level', '=', 'b.id')
            ->join('exam_questionaire_headers as c', 'a.id', '=', 'c.sub_category_id')
            ->select(
                'a.id',
                'c.sub_category_id',
                'c.id as question_id',
                'c.question',
                'c.question_image'
            )
            ->where('a.category_id', $exam[0]->category_id)
            ->distinct()
            ->get();

        $exam_choices = DB::table('exam_sub_categories as a')
            ->join('exam_difficulty_levels as b', 'a.difficulty_level', '=', 'b.id')
            ->join('exam_questionaire_headers as c', 'a.id', '=', 'c.sub_category_id')
            ->join('exam_questionaire_details as d', 'c.id', '=', 'd.question_id')
            ->select(
                'a.id',
                'c.id as question_id',
                'd.id as choice_id',
                'd.choice_details',
                'd.choice_image',
                DB::raw("(select ISNULL(f.choice_id,0) from applicant_examination_headers as e inner join
                        applicant_examination_details f on e.id = f.applicant_examination_id
                        where e.applicant_id = $applicant_id and f.question_id = c.id and f.choice_id = d.id) as selected_choice"),
            )
            ->where('a.category_id', $exam[0]->category_id)
            ->distinct()
            ->get();

        return $this->successResponse([
            'applicant' => $applicant,
            'exam' => $exam,
            'exam_sub_categories' => $exam_sub_categories,
            'exam_questions' => $exam_questions,
            'exam_choices' => $exam_choices
        ], 'Applicant examination page data loaded successfully');
    }

    public function examAutoSave(Request $request, $applicant_examination_id)
    {
        $data_answers = $request->all();
        $answers = [];

        for ($i = 0; $i < count($data_answers["question_id"]); $i++) {

            $get_answer = DB::table('exam_questionaire_details')
                ->select('id')
                ->where([
                    'is_correct_answer' => 1,
                    'question_id' => $data_answers["question_id"][$i]
                ])
                ->get();

            if ($get_answer->isNotEmpty()) {
                $correct_answer_id = $get_answer[0]->id;
            } else {
                $correct_answer_id = 0;
            }

            if (isset($data_answers["choices" . $data_answers["question_id"][$i]])) {
                $choice_answer_id = $data_answers["choices" . $data_answers["question_id"][$i]][$data_answers["question_id"][$i]];

                $is_correct_answer = DB::table('exam_questionaire_details')
                    ->select('is_correct_answer', 'id as correct_answer_id')
                    ->where([
                        'is_correct_answer' => true,
                        'id' => $choice_answer_id
                    ])
                    ->get();

                if ($is_correct_answer->isEmpty()) {
                    $correct = 0;
                    $wrong = 1;
                    $unanswered = 0;
                } else {
                    $correct = 1;
                    $wrong = 0;
                    $unanswered = 0;
                }
            } else {
                $choice_answer_id = 0;
                $correct = 0;
                $wrong = 0;
                $unanswered = 1;
            }

            $answers = [
                'applicant_examination_id' => $applicant_examination_id,
                'question_id' => $data_answers["question_id"][$i],
                'choice_id' => $choice_answer_id,
                'correct_answer_id' => $correct_answer_id,
                'correct' => $correct,
                'wrong' => $wrong,
                'unanswered' => $unanswered,
                'date_submitted' => now()
            ];

            // dump($answers);
            $is_exist = DB::table('applicant_examination_details')->where([
                'applicant_examination_id' => $applicant_examination_id,
                'question_id' => $data_answers["question_id"][$i],
            ])
                ->get();

            if ($is_exist->isNotEmpty()) {
                DB::table('applicant_examination_details')->where([
                    'applicant_examination_id' => $applicant_examination_id,
                    'question_id' => $data_answers["question_id"][$i],
                ])->update($answers);
            } else {
                DB::table('applicant_examination_details')->insert($answers);
            }
        }

        // compute examination rating.
        $exam_data = DB::table('applicant_examination_details')
            ->select(
                DB::raw("count(question_id) as total_item"),
                DB::raw("sum(correct) as total_correct"),
                DB::raw("sum(wrong) as total_wrong"),
                DB::raw("sum(unanswered) as total_unanswered"),
            )
            ->where('applicant_examination_id', $applicant_examination_id)
            ->get();

        $total_items = $exam_data[0]->total_item;
        $total_correct = $exam_data[0]->total_correct;

        $exam_rating = $total_items > 0 ? (($total_correct / $total_items) * 100) : 0;

        DB::table('applicant_examination_headers')
            ->where([
                'id' => $applicant_examination_id
            ])
            ->update([
                'is_complete' => false,
                'date_completed' => null,
                'exam_rating' => $exam_rating,
                'total_items' => $total_items,
                'total_score' => $total_correct,
                'last_duration' => $request->last_duration,
            ]);

        return json_encode('success');
    }

    public function examSubmit(Request $request, $applicant_examination_id)
    {
        $data_answers = $request->all();
        $answers = [];

        for ($i = 0; $i < count($data_answers["question_id"]); $i++) {

            $get_answer = DB::table('exam_questionaire_details')
                ->select('id')
                ->where([
                    'is_correct_answer' => 1,
                    'question_id' => $data_answers["question_id"][$i]
                ])
                ->get();

            if ($get_answer->isNotEmpty()) {
                $correct_answer_id = $get_answer[0]->id;
            } else {
                $correct_answer_id = 0;
            }

            if (isset($data_answers["choices" . $data_answers["question_id"][$i]])) {
                $choice_answer_id = $data_answers["choices" . $data_answers["question_id"][$i]][$data_answers["question_id"][$i]];

                $is_correct_answer = DB::table('exam_questionaire_details')
                    ->select('is_correct_answer', 'id as correct_answer_id')
                    ->where([
                        'is_correct_answer' => true,
                        'id' => $choice_answer_id
                    ])
                    ->get();

                if ($is_correct_answer->isEmpty()) {
                    $correct = 0;
                    $wrong = 1;
                    $unanswered = 0;
                } else {
                    $correct = 1;
                    $wrong = 0;
                    $unanswered = 0;
                }
            } else {
                $choice_answer_id = 0;
                $correct = 0;
                $wrong = 0;
                $unanswered = 1;
            }

            $answers = [
                'applicant_examination_id' => $applicant_examination_id,
                'question_id' => $data_answers["question_id"][$i],
                'choice_id' => $choice_answer_id,
                'correct_answer_id' => $correct_answer_id,
                'correct' => $correct,
                'wrong' => $wrong,
                'unanswered' => $unanswered,
                'date_submitted' => now()
            ];

            // dump($answers);
            $is_exist = DB::table('applicant_examination_details')->where([
                'applicant_examination_id' => $applicant_examination_id,
                'question_id' => $data_answers["question_id"][$i],
            ])
                ->get();

            if ($is_exist->isNotEmpty()) {
                DB::table('applicant_examination_details')->where([
                    'applicant_examination_id' => $applicant_examination_id,
                    'question_id' => $data_answers["question_id"][$i],
                ])->update($answers);
            } else {
                DB::table('applicant_examination_details')->insert($answers);
            }
        }

        // compute examination rating.
        $exam_data = DB::table('applicant_examination_details')
            ->select(
                DB::raw("count(question_id) as total_item"),
                DB::raw("sum(correct) as total_correct"),
                DB::raw("sum(wrong) as total_wrong"),
                DB::raw("sum(unanswered) as total_unanswered"),
            )
            ->where('applicant_examination_id', $applicant_examination_id)
            ->get();

        $total_items = $exam_data[0]->total_item;
        $total_correct = $exam_data[0]->total_correct;

        $exam_rating = $total_items > 0 ? (($total_correct / $total_items) * 100) : 0;

        DB::table('applicant_examination_headers')
            ->where([
                'id' => $applicant_examination_id
            ])
            ->update([
                'is_complete' => true,
                'date_completed' => now(),
                'exam_rating' => $exam_rating,
                'total_items' => $total_items,
                'total_score' => $total_correct
            ]);

        // Send "exam passed" email when applicant meets passing criteria
        $header = DB::table('applicant_examination_headers')
            ->where('id', $applicant_examination_id)
            ->first();
        if ($header) {
            $applicant = DB::table('applicant_headers')
                ->where('id', $header->applicant_id)
                ->first();
            $examSetup = DB::table('examination_schedule_header as a')
                ->join('examination_setup_header as b', 'a.exam_id', '=', 'b.id')
                ->where('a.id', $header->exam_schedule_id)
                ->select('b.exam_set', 'b.passing_criteria')
                ->first();
            $passingCriteria = $examSetup ? (float) ($examSetup->passing_criteria ?? 0) : 0;
            $passedExam = $total_correct >= $passingCriteria;
            if ($applicant && $examSetup && !empty(trim($applicant->email ?? '')) && $passedExam) {
                $emailData = [
                    'applicant_name' => trim(($applicant->first_name ?? '') . ' ' . ($applicant->middle_name ?? '') . ' ' . ($applicant->last_name ?? '')),
                    'exam_set' => $examSetup->exam_set ?? 'Online Examination',
                    'exam_rating' => $exam_rating,
                    'passing_criteria' => $passingCriteria,
                ];
                try {
                    Notification::route('mail', $applicant->email)
                        ->notify(new EmailApplicantExamPassed($emailData));
                } catch (\Exception $e) {
                    \Log::warning('Exam passed email failed for applicant_examination ' . $applicant_examination_id . ': ' . $e->getMessage());
                }
            }

            // Auto-unpost the examination schedule when a tagged applicant submits; schedule moves to Done
            if ($header && $header->exam_schedule_id) {
                DB::table('examination_schedule_header')
                    ->where('id', $header->exam_schedule_id)
                    ->update(['posted' => false]);
            }
        }

        return $this->successResponse(null, 'Successfully submitted examinations. To check your exam result go to examination tab.');
    }

    public function examResult($applicant_examination_id)
    {
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
        ], 'Applicant examination result data loaded successfully');
    }

    /**
     * Get applicant exam answers (for HR to view in schedule details).
     */
    public function examAnswers($applicant_examination_id)
    {
        $header = DB::table('applicant_examination_headers')
            ->where('id', $applicant_examination_id)
            ->first();
        if (!$header) {
            return $this->notFoundResponse('Applicant examination record not found');
        }

        // Get unique questions only - if duplicates exist, get the latest answer for each question
        // First, get the latest record ID for each question_id
        $latestAnswerIds = DB::table('applicant_examination_details')
            ->select('question_id', DB::raw('MAX(id) as latest_id'))
            ->where('applicant_examination_id', $applicant_examination_id)
            ->groupBy('question_id')
            ->pluck('latest_id', 'question_id')
            ->toArray();

        // Then get the full answer details only for the latest records
        $answers = DB::table('applicant_examination_details as a')
            ->join('exam_questionaire_headers as b', 'a.question_id', '=', 'b.id')
            ->leftJoin('exam_sub_categories as sc', 'b.sub_category_id', '=', 'sc.id')
            ->leftJoin('exam_questionaire_details as c', 'a.choice_id', '=', 'c.id')
            ->leftJoin('exam_questionaire_details as d', 'a.correct_answer_id', '=', 'd.id')
            ->select(
                'a.question_id',
                'a.choice_id',
                'a.correct_answer_id',
                'a.essay_score',
                'a.correct',
                'a.wrong',
                'a.unanswered',
                'a.answer_text',
                'b.question as question_text',
                'sc.sub_category',
                'c.choice_details as applicant_choice_text',
                'd.choice_details as correct_answer_text'
            )
            ->where('a.applicant_examination_id', $applicant_examination_id)
            ->whereIn('a.id', array_values($latestAnswerIds))
            ->orderBy('a.question_id')
            ->get();

        // Ensure correct/wrong/unanswered are integers (0 or 1) for consistent API response
        $answers = $answers->map(function ($row) {
            $row = (object) (array) $row;
            $row->essay_score = ($row->essay_score === null || $row->essay_score === '')
                ? null
                : (int) $row->essay_score;
            $row->correct = (int) ($row->correct ?? 0);
            $row->wrong = (int) ($row->wrong ?? 0);
            $row->unanswered = (int) ($row->unanswered ?? 0);
            return $row;
        });

        $applicant = DB::table('applicant_headers as a')
            ->join('applicant_examination_headers as b', 'a.id', '=', 'b.applicant_id')
            ->where('b.id', $applicant_examination_id)
            ->select(
                'a.id',
                'a.applicant_no',
                DB::raw("CONCAT(a.first_name,' ',a.middle_name,' ',a.last_name) as name")
            )
            ->first();

        return $this->successResponse([
            'applicant' => $applicant,
            'applicant_examination_id' => (int) $applicant_examination_id,
            'answers' => $answers,
        ], 'Applicant exam answers retrieved successfully');
    }

    /**
     * HR review for essay answers.
     * Payload:
     * - answers: [{ question_id: number, grade: 0..100 }]
     * Recomputes total score and exam rating after review.
     */
    public function examReviewAnswers(Request $request, $applicant_examination_id)
    {
        $header = DB::table('applicant_examination_headers')
            ->where('id', $applicant_examination_id)
            ->first();
        if (!$header) {
            return $this->notFoundResponse('Applicant examination record not found');
        }

        $validated = $request->validate([
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer',
            'answers.*.grade' => 'required|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['answers'] as $item) {
                $questionId = (int) $item['question_id'];
                $grade = (float) $item['grade'];

                $latestAnswerId = DB::table('applicant_examination_details')
                    ->where('applicant_examination_id', $applicant_examination_id)
                    ->where('question_id', $questionId)
                    ->max('id');

                if (!$latestAnswerId) {
                    continue;
                }

                // Grade-based review:
                // - Grade >= 75 counts as Correct
                // - Grade < 75 counts as Wrong
                // Existing schema stores binary correctness, so we map grade into these flags.
                $update = [
                    'essay_score' => (int) round($grade),
                    'correct' => $grade >= 75 ? 1 : 0,
                    'wrong' => $grade < 75 ? 1 : 0,
                    'unanswered' => 0,
                ];

                DB::table('applicant_examination_details')
                    ->where('id', $latestAnswerId)
                    ->update($update);
            }

            // Recompute examination rating after review updates
            $examData = DB::table('applicant_examination_details')
                ->select(
                    DB::raw("count(question_id) as total_item"),
                    DB::raw("sum(correct) as total_correct")
                )
                ->where('applicant_examination_id', $applicant_examination_id)
                ->first();

            $totalItems = (int) ($examData->total_item ?? 0);
            $totalCorrect = (int) ($examData->total_correct ?? 0);
            $examRating = $totalItems > 0 ? (($totalCorrect / $totalItems) * 100) : 0;

            DB::table('applicant_examination_headers')
                ->where('id', $applicant_examination_id)
                ->update([
                    'exam_rating' => $examRating,
                    'total_items' => $totalItems,
                    'total_score' => $totalCorrect,
                ]);

            DB::commit();

            return $this->successResponse([
                'applicant_examination_id' => (int) $applicant_examination_id,
                'exam_rating' => $examRating,
                'total_items' => $totalItems,
                'total_score' => $totalCorrect,
            ], 'Essay answers reviewed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to review essay answers: ' . $e->getMessage());
        }
    }

    /**
     * Manually tag psych exam result as passed/failed.
     */
    public function examTagResult(Request $request, $applicant_examination_id)
    {
        $header = DB::table('applicant_examination_headers')
            ->where('id', $applicant_examination_id)
            ->first();
        if (!$header) {
            return $this->notFoundResponse('Applicant examination record not found');
        }

        $validated = $request->validate([
            'result' => 'required|in:passed,failed',
        ]);

        $isPassed = strtolower((string) $validated['result']) === 'passed';
        $examRating = $isPassed ? 100 : 0;

        DB::table('applicant_examination_headers')
            ->where('id', $applicant_examination_id)
            ->update([
                'exam_rating' => $examRating,
                'is_complete' => 1,
                'total_items' => 1,
                'total_score' => $isPassed ? 1 : 0,
            ]);

        return $this->successResponse([
            'applicant_examination_id' => (int) $applicant_examination_id,
            'result' => $isPassed ? 'passed' : 'failed',
            'exam_rating' => $examRating,
        ], 'Examinee result tagged successfully.');
    }
}
