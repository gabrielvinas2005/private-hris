<?php

namespace App\Http\Controllers;

use Auth;
use Image;
use App\User;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use App\Notifications\Email201Update;
use Illuminate\Support\Facades\Notification;

class EmployeeRequestController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($id)
    {
        try {
            $requests = DB::table('employee_requests as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->join('employees_temps as c','a.id','=','c.request_id')
            ->select(
                'a.id',
                'a.employee_id',
                'a.request_date',
                'a.remarks',
                'a.status_id'
            )
            ->where('a.employee_id', $id)
            ->get();

        $with_pending = DB::table('employee_requests as a')
        ->join('employees_temps as c','a.id','=','c.request_id')
        ->where([
            'a.employee_id' => $id,
            'a.status_id' => 1
        ])
            ->count();

        return $this->successResponse([
            'requests' => $requests,
            'id' => $id,
            'with_pending' => $with_pending
        ], 'Employee request list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee request list: ' . $e->getMessage());
        }
    }

    public function add($id, $employee_id)
    {
        try {
            $request_id = $id;
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
            $employee_info = DB::table('employees')
                ->where('id', $employee_id)
                ->orderBy('employees.first_name', 'asc')
                ->get();

            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', $employee_info[0]->plantilla_id)->get();

            $children = DB::table('employee_children')->where('employee_id', $employee_id)->get();
            $educations = DB::table('employee_educations')->where('employee_id', $employee_id)->get();
            $service_records = DB::table('service_records')->where('employee_id', $employee_id)->get();
            $employments = DB::table('employee_employment_records')->where('employee_id', $employee_id)->get();
            $examinations = DB::table('employee_examinations')->where('employee_id', $employee_id)->get();
            $trainings = DB::table('employee_trainings')->where('employee_id', $employee_id)->get();
            $organizations = DB::table('employee_organizations')->where('employee_id', $employee_id)->get();
            $recognitions = DB::table('employee_recognations')->where('employee_id', $employee_id)->get();
            $skills = DB::table('employee_skills')->where('employee_id', $employee_id)->get();
            $memberships = DB::table('employee_memberships')->where('employee_id', $employee_id)->get();
            $references = DB::table('employee_references')->where('employee_id', $employee_id)->get();

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
                    'a.employee_id' => $employee_id
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
                    'a.employee_id' => $employee_id,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->get();
        } else {

            $employee_info = DB::table('employees_temps')
                ->where([
                    'request_id' => $id
                ])
                ->get();

            if($employee_info->isEmpty()){
                return $this->errorResponse('Record has been cancelled.', 400);
            }

            if ($employee_info->isNotEmpty()) {
                $plantillas_selected = DB::table('plantillas')
                    ->select('salary_grade_id', 'salary_step_id')
                    ->where('id', $employee_info[0]->plantilla_id)->get();
            } else {
                $plantillas_selected_dummy = array(
                    'salary_grade_id' => 0,
                    'salary_step_id' => 0
                );

                $plantillas_selected = (object)$plantillas_selected_dummy;
                $plantillas_selected = collect([$plantillas_selected_dummy]);
            }

            $children = DB::table('employee_children_temps')->where('request_id', $id)->get();
            $educations = DB::table('employee_educations_temps')->where('request_id', $id)->get();
            $service_records = DB::table('service_records_temps')->where('request_id', $id)->get();
            $employments = DB::table('employee_employment_records_temps')->where('request_id', $id)->get();
            $examinations = DB::table('employee_examinations_temps')->where('request_id', $id)->get();
            $trainings = DB::table('employee_trainings_temps')->where('request_id', $id)->get();
            $organizations = DB::table('employee_organizations_temps')->where('request_id', $request_id)->get();
            $recognitions = DB::table('employee_recognations_temps')->where('request_id', $request_id)->get();
            $skills = DB::table('employee_skills_temps')->where('request_id', $request_id)->get();
            $memberships = DB::table('employee_memberships_temps')->where('request_id', $request_id)->get();
            $references = DB::table('employee_references_temps')->where('request_id', $request_id)->get();

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
                    'a.employee_id' => $employee_id
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
                    'a.employee_id' => $employee_id,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->get();
        }

        return $this->successResponse([
            'request_id' => $request_id,
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
            'incomes' => $incomes
        ], 'Employee request form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employee request form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id, $request_id)
    {
        try {
            $validator = validator($request->all(), [
                'photo' => 'image|max:3000',
                'employee_no' => 'required',
                'email' => 'required',
                'name_prefix_id' => 'required',
                'first_name' => 'required',
                'middle_name' => 'required',
                'last_name' => 'required',
                'birthdate' => 'required',
                'age' => 'required',
                'gender_id' => 'required',
                'civil_status_id' => 'required',
                'citizenship_id' => 'required',
                'religion_id' => 'required',
                'branch_id' => 'required',
                'department_id' => 'required',
                'employment_type_id' => 'required',
                'date_hired' => 'required',
                'position_id' => 'required',
                'payroll_interval_id' => 'required',
                'salary' => 'required'
            ], [
                'photo.image' => 'Photo must be an image file.',
                'photo.max' => 'Photo size must not exceed 3MB.',
                'employee_no.required' => 'Employee number is required.',
                'email.required' => 'Email is required.',
                'name_prefix_id.required' => 'Name prefix is required.',
                'first_name.required' => 'First name is required.',
                'middle_name.required' => 'Middle name is required.',
                'last_name.required' => 'Last name is required.',
                'birthdate.required' => 'Birthdate is required.',
                'age.required' => 'Age is required.',
                'gender_id.required' => 'Gender is required.',
                'civil_status_id.required' => 'Civil status is required.',
                'citizenship_id.required' => 'Citizenship is required.',
                'religion_id.required' => 'Religion is required.',
                'branch_id.required' => 'Branch is required.',
                'department_id.required' => 'Department is required.',
                'employment_type_id.required' => 'Employment type is required.',
                'date_hired.required' => 'Date hired is required.',
                'position_id.required' => 'Position is required.',
                'payroll_interval_id.required' => 'Payroll interval is required.',
                'salary.required' => 'Salary is required.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->position_id == 0) {
                return $this->errorResponse('Employee Position is required!');
            }

        if ($request_id == 0) {
            $photo_data = DB::table('employees')
                ->select('photo')
                ->where('id', $id)
                ->get();

            $photo = $photo_data[0]->photo;
        } else {
            if ($request->hasFile('photo')) {
                $image_file = $request->photo;
                $image = Image::make($image_file);

                Response::make($image->encode('jpeg'));

                $photo = base64_encode($image);
            } else {
                $photo_data = DB::table('employees_temps')
                    ->select('photo')
                    ->where('employee_id', $id)
                    ->get();

                $photo = $photo_data[0]->photo;
            }
        }

        // Employee Infor Insert
        $employee_info = array(
            // basic info
            'photo' => $photo,
            'employee_id' => $id,
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
            'company_id' => $request->company_id,
            'branch_id' => $request->branch_id,
            'department_id' => $request->department_id,
            'employment_type_id' => $request->employment_type_id,
            'date_hired' => $request->date_hired,
            'is_plantilla' => $request->has('is_plantilla') ? true : false,
            'is_teaching' => $request->has('is_teaching') ? true : false,
            'plantilla_id' => $request->plantilla_id,
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
            'is_employee' => true,
        );

        if ($request_id == 0) {
            $request_id = 0 + DB::table('employee_requests')->max('id');
            $request_id += 1;
        }

        $employee_requests_info = array(
            'employee_id' => $id,
            'status_id' => 1,
            'request_date' => now()
        );

        // Save Request log
        DB::unprepared('SET IDENTITY_INSERT employee_requests ON');
        DB::table('employee_requests')->updateOrInsert(['id' => $request_id], $employee_requests_info);
        DB::unprepared('SET IDENTITY_INSERT employee_requests OFF');

        // Save Employee Temp Info
        DB::table('employees_temps')->updateOrInsert(['request_id' => $request_id], $employee_info);

        // Save Children Info.
        $data_child = $request->all();
        $arr_len_children = count($data_child["child_name"]);
        $children_data = [];

        DB::table('employee_children_temps')->where('request_id', $request_id)->delete();

        for ($i = 0; $i < $arr_len_children; $i++) {
            if ($data_child["child_name"][$i] != NULL) {

                if ($data_child["children_id"][$i] == null) {
                    $child_id = 0 + DB::table('employee_children_temps')->max('children_id');
                    $child_id += 1;
                } else {
                    $child_id = $data_child["children_id"][$i];
                }

                $children_data = [
                    'request_id' => $request_id,
                    'employee_id' => $id,
                    'child_name' => $data_child["child_name"][$i],
                    'child_middlename' => $data_child["child_middlename"][$i],
                    'child_lastname' => $data_child["child_lastname"][$i],
                    'child_birthdate' => $data_child["child_birthdate"][$i]
                ];

                DB::table('employee_children_temps')->Insert($children_data);
            }
        }

        // Save Education
        $data_educ = $request->all();
        $arr_len_educ = count($data_educ["school_name"]);
        $educ_data = [];

        DB::table('employee_educations_temps')->where('request_id', $request_id)->delete();

        for ($i = 0; $i < $arr_len_educ; $i++) {
            if ($data_educ["school_name"][$i] != NULL) {

                if ($data_educ["education_id"][$i] == null) {
                    $educ_id = 0 + DB::table('employee_educations_temps')->max('education_id');
                    $educ_id += 1;
                } else {
                    $educ_id = $data_educ["education_id"][$i];
                }

                $educ_data = [
                    'request_id' => $request_id,
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

                DB::table('employee_educations_temps')->insert($educ_data);
            }
        }

        // Save Service Records
        $data_serv = $request->all();
        $arr_len_serv = count($data_serv["designation"]);
        $serv_data = [];

        DB::table('service_records_temps')->where('request_id', $request_id)->delete();

        for ($i = 0; $i < $arr_len_serv; $i++) {
            if ($data_serv["designation"][$i] != NULL) {

                if ($data_serv["service_record_id"][$i] == null) {
                    $serv_id = 0 + DB::table('service_records_temps')->max('service_record_id');
                    $serv_id += 1;
                } else {
                    $serv_id = $data_serv["service_record_id"][$i];
                }

                $serv_data = [
                    'request_id' => $request_id,
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

                DB::table('service_records_temps')->insert($serv_data);
            }
        }

        // Save Employment Records
        $data_emp = $request->all();
        $arr_len_emp = count($data_emp["work_company"]);
        $emp_data = [];

        DB::table('employee_employment_records_temps')->where('request_id', $request_id)->delete();

        for ($i = 0; $i < $arr_len_emp; $i++) {
            if ($data_emp["work_company"][$i] != NULL) {

                if ($data_emp["employment_record_id"][$i] == null) {
                    $emp_id = 0 + DB::table('employee_employment_records_temps')->max('employment_record_id');
                    $emp_id += 1;
                } else {
                    $emp_id = $data_emp["employment_record_id"][$i];
                }

                $emp_data = [
                    'request_id' => $request_id,
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

                DB::table('employee_employment_records_temps')->insert($emp_data);
            }
        }

        // Save Eaminations
        $data_exam = $request->all();
        $arr_len_exam = count($data_exam["place_of_exam"]);
        $exam_data = [];

        DB::table('employee_examinations_temps')->where('request_id', $request_id)->delete();

        for ($i = 0; $i < $arr_len_exam; $i++) {
            if ($data_exam["place_of_exam"][$i] != NULL) {

                if ($data_exam["examination_id"][$i] == null) {
                    $exam_id = 0 + DB::table('employee_examinations_temps')->max('examination_id');
                    $exam_id += 1;
                } else {
                    $exam_id = $data_exam["examination_id"][$i];
                }

                $exam_data = [
                    'request_id' => $request_id,
                    'employee_id' => $id,
                    'eligibility_id' => $data_exam["eligibility_id"][$i],
                    'exam_rating' => $data_exam["exam_rating"][$i],
                    'exam_date' => $data_exam["exam_date"][$i],
                    'place_of_exam' => $data_exam["place_of_exam"][$i],
                    'license_number' => $data_exam["license_number"][$i],
                    'date_released' => $data_exam["date_released"][$i],
                ];

                DB::table('employee_examinations_temps')->insert($exam_data);
            }
        }

        // Save Training
        $data_training = $request->all();
        $arr_len_training = count($data_training["training"]);
        $training_data = [];

        DB::table('employee_trainings_temps')->where('request_id', $request_id)->delete();

        for ($i = 0; $i < $arr_len_training; $i++) {
            if ($data_training["training"][$i] != NULL) {

                if ($data_training["training_id"][$i] == null) {
                    $training_id = 0 + DB::table('employee_trainings_temps')->max('training_id');
                    $training_id += 1;
                } else {
                    $training_id = $data_training["training_id"][$i];
                }

                $training_data = [
                    'request_id' => $request_id,
                    'employee_id' => $id,
                    'training' => $data_training["training"][$i],
                    'training_from' => $data_training["training_from"][$i],
                    'training_to' => $data_training["training_to"][$i],
                    'hours' => $data_training["hours"][$i],
                    'sponsored_by' => $data_training["sponsored_by"][$i],
                    'learning_id' => $data_training["learning_id"][$i],
                ];

                DB::table('employee_trainings_temps')->insert($training_data);
            }
        }

        // Save Organizations
        $data_org = $request->all();
        $arr_len_org = count($data_org["organization"]);
        $org_data = [];

        DB::table('employee_organizations_temps')->where('request_id', $request_id)->delete();

        for ($i = 0; $i < $arr_len_org; $i++) {
            if ($data_org["organization"][$i] != NULL) {

                if ($data_org["organization_id"][$i] == null) {
                    $organization_id = 0 + DB::table('employee_organizations_temps')->max('organization_id');
                    $organization_id += 1;
                } else {
                    $organization_id = $data_org["organization_id"][$i];
                }

                $org_data = [
                    'request_id' => $request_id,
                    'employee_id' => $id,
                    'organization' => $data_org["organization"][$i],
                    'org_from' => $data_org["org_from"][$i],
                    'org_to' => $data_org["org_to"][$i],
                    'org_hours' => $data_org["org_hours"][$i],
                    'org_position' => $data_org["org_position"][$i],
                ];

                DB::table('employee_organizations_temps')->insert($org_data);
            }
        }

        // Save Recognitions
        $data_recog = $request->all();
        $arr_len_recog = count($data_recog["recognation"]);
        $recog_data = [];

        DB::table('employee_recognations_temps')->where('request_id', $request_id)->delete();

        for ($i = 0; $i < $arr_len_recog; $i++) {
            if ($data_recog["recognation"][$i] != NULL) {

                if ($data_recog["recognation_id"][$i] == null) {
                    $recognation_id = 0 + DB::table('employee_recognations_temps')->max('recognation_id');
                    $recognation_id += 1;
                } else {
                    $recognation_id = $data_recog["recognation_id"][$i];
                }

                $recog_data = [
                    'request_id' => $request_id,
                    'employee_id' => $id,
                    'recognation' => $data_recog["recognation"][$i],
                ];

                DB::table('employee_recognations_temps')->insert($recog_data);
            }
        }

        // Save Skills
        $data_skill = $request->all();
        $arr_len_skill = count($data_skill["skill"]);
        $skill_data = [];

        DB::table('employee_skills_temps')->where('request_id', $request_id)->delete();

        for ($i = 0; $i < $arr_len_skill; $i++) {
            if ($data_skill["skill"][$i] != NULL) {

                if ($data_skill["skill_id"][$i] == null) {
                    $skill_id = 0 + DB::table('employee_skills_temps')->max('skill_id');
                    $skill_id += 1;
                } else {
                    $skill_id = $data_skill["skill_id"][$i];
                }

                $skill_data = [
                    'request_id' => $request_id,
                    'employee_id' => $id,
                    'skill' => $data_skill["skill"][$i],
                ];

                DB::table('employee_skills_temps')->insert($skill_data);
            }
        }

        // Save Memberships
        $data_mem = $request->all();
        $arr_len_mem = count($data_mem["membership"]);
        $mem_data = [];

        DB::table('employee_memberships_temps')->where('request_id', $request_id)->delete();

        for ($i = 0; $i < $arr_len_mem; $i++) {
            if (
                $data_mem["membership"][$i] != NULL
            ) {

                if ($data_mem["membership_id"][$i] == null) {
                    $membership_id = 0 + DB::table('employee_memberships_temps')->max('membership_id');
                    $membership_id += 1;
                } else {
                    $membership_id = $data_mem["membership_id"][$i];
                }

                $mem_data = [
                    'request_id' => $request_id,
                    'employee_id' => $id,
                    'membership' => $data_mem["membership"][$i],
                ];

                DB::table('employee_memberships_temps')->insert($mem_data);
            }
        }

        // Save References
        $data_ref = $request->all();
        $arr_len_ref = count($data_ref["ref_name"]);
        $ref_data = [];

        DB::table('employee_references_temps')->where('request_id', $request_id)->delete();

        for ($i = 0; $i < $arr_len_ref; $i++) {
            if (
                $data_ref["ref_name"][$i] != NULL
            ) {

                if ($data_ref["reference_id"][$i] == null) {
                    $reference_id = 0 + DB::table('employee_references_temps')->max('reference_id');
                    $reference_id += 1;
                } else {
                    $reference_id = $data_ref["reference_id"][$i];
                }

                $ref_data = [
                    'request_id' => $request_id,
                    'employee_id' => $id,
                    'ref_name' => $data_ref["ref_name"][$i],
                    'ref_address' => $data_ref["ref_address"][$i],
                    'ref_occupation' => $data_ref["ref_occupation"][$i],
                    'ref_contact_no' => $data_ref["ref_contact_no"][$i],
                    'ref_email' => $data_ref["ref_email"][$i],
                ];

                DB::table('employee_references_temps')->insert($ref_data);
            }
        }

        if ($request_id == 0) {
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Human Resource Module',
                'menu'    => 'Review 201 Updates',
                'activity' => 'Requested',
                'description' => 'Requested 201 File Update.',
            );
        } else {
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Human Resource Module',
                'menu'    => 'Review 201 Updates',
                'activity' => 'Edit',
                'description' => 'Edited Request 201 File Update.',
            );
        }

        Audit::create($data_audit);

        // Get email info.
        $employee_updates = DB::table('access as a')
            ->join('menus as b', 'a.menu_id', '=', 'b.id')
            ->join('users as c', 'a.user_id', '=', 'c.id')
            ->select('c.id', 'c.email', 'c.name')
            ->where([
                'b.menu_key' => 'review_201_updates'
            ])
            ->get();

        $employee_name = $request->first_name . ' ' . $request->last_name;

        // dump($employee_updates);
        // dd($employee_name);

        foreach ($employee_updates as $employee) {
            $users = User::where('id', $employee->id)->get();
            $employee_hr = $employee->name;
            // send email verification here
            Notification::send($users, new Email201Update($employee_hr, $employee_name));
        }

        return $this->successResponse(null, 'You have successfully update employee information!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save employee request: ' . $e->getMessage());
        }
    }

    public function list()
    {
        try {
            $requests = DB::table('employee_requests as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->join('employees_temps as c','a.id','=','c.request_id')
            ->select(
                'a.id',
                'a.employee_id',
                'b.employee_no',
                'b.photo',
                DB::raw("CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name) as name"),
                'a.request_date',
                'a.remarks',
                'a.status_id'
            )
            ->orderBy('request_date', 'asc')
            ->get();

        return $this->successResponse($requests, 'Employee request review list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee request review list: ' . $e->getMessage());
        }
    }

    public function review($id)
    {
        try {
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

        $employee_info = DB::table('employees_temps')->where('request_id', $id)->get();

        $employee_info_original = DB::table('employees')->where('id', $employee_info[0]->employee_id)->get();

        $plantillas_selected = DB::table('plantillas')
            ->select('salary_grade_id', 'salary_step_id')
            ->where('id', $employee_info[0]->plantilla_id)->get();

        $children = DB::table('employee_children_temps')->where('request_id', $id)->get();
        $educations = DB::table('employee_educations_temps')->where('request_id', $id)->get();
        $service_records = DB::table('service_records_temps')->where('request_id', $id)->get();
        $employments = DB::table('employee_employment_records_temps')->where('request_id', $id)->get();
        $examinations = DB::table('employee_examinations_temps')->where('request_id', $id)->get();
        $trainings = DB::table('employee_trainings_temps')->where('request_id', $id)->get();
        $organizations = DB::table('employee_organizations_temps')->where('request_id', $id)->get();
        $recognitions = DB::table('employee_recognations_temps')->where('request_id', $id)->get();
        $skills = DB::table('employee_skills_temps')->where('request_id', $id)->get();
        $memberships = DB::table('employee_memberships_temps')->where('request_id', $id)->get();
        $references = DB::table('employee_references_temps')->where('request_id', $id)->get();

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
            'employee_info_original' => $employee_info_original
        ], 'Employee request review data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employee request review data: ' . $e->getMessage());
        }
    }

    public function approval($id, $type_id)
    {
        try {
            // Treat admins as having access to all branches
            if (Auth::user()->access_all_branches || (Auth::user() && Auth::user()->is_admin)) {
            $data = DB::table('employee_requests as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name) as name"),
                    'a.request_date',
                )
                ->get();
        } else {
            $user_branch_id = DB::table('users as a')
                ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('b.branch_id')
                ->where('a.id', Auth::user()->id)
                ->get();

            $data = DB::table('employee_requests as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name) as name"),
                    'a.request_date',
                )
                ->where('b.branch_id', $user_branch_id[0]->branch_id)
                ->get();
        }

        return $this->successResponse([
            'data' => $data,
            'type_id' => $type_id
        ], 'Employee request approval data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee request approval data: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, $id, $type_id)
    {
        try {
            // Absolute approver bypass: admin can force approve/disapprove
            if (Auth::user() && Auth::user()->is_admin) {
                if ($type_id == 1) {
                    DB::table('employee_requests')->where('id', $id)->update(['status_id' => 2, 'remarks' => $request->remarks]);
                    return $this->successResponse(null, 'You have successfully approved a request!');
                } else {
                    DB::table('employee_requests')->where('id', $id)->update(['status_id' => 3, 'remarks' => $request->remarks]);
                    return $this->successResponse(null, 'You have successfully disapproved a request!');
                }
            }

            if ($type_id == 1) {

            DB::table('employee_requests')->where('id', $id)->update(['status_id' => 2]);

            // Update Employee Records
            $employee_data = DB::table('employees_temps')->where('request_id', $id)->get();

            // Load Employee Current Data
            $employee_current_data = DB::table('employees')->where('id', $employee_data[0]->employee_id)->get();

            // dd($request->all());

            $employee_info = array(
                // basic info
                'photo' => $request->has('approve_photo') ? $employee_data[0]->photo : $employee_current_data[0]->photo,
                'email' => $request->has('approve_email') ? $employee_data[0]->email : $employee_current_data[0]->email,
                'mobile_no' => $request->has('approve_mobile_no') ? $employee_data[0]->mobile_no : $employee_current_data[0]->mobile_no,
                'telephone_no' => $request->has('approve_telephone_no') ? $employee_data[0]->telephone_no : $employee_current_data[0]->telephone_no,
                'tin_no' => $request->has('approve_tin_no') ? $employee_data[0]->tin_no : $employee_current_data[0]->tin_no,
                'gsis_no' => $request->has('approve_gsis_no') ? $employee_data[0]->gsis_no : $employee_current_data[0]->gsis_no,
                'sss_no' => $request->has('approve_sss_no') ? $employee_data[0]->sss_no : $employee_current_data[0]->sss_no,
                'pagibig_no' => $request->has('approve_pagibig_no') ? $employee_data[0]->pagibig_no : $employee_current_data[0]->pagibig_no,
                'philhealth_no' => $request->has('approve_philhealth_no') ? $employee_data[0]->philhealth_no : $employee_current_data[0]->philhealth_no,
                'name_prefix_id' => $request->has('approve_name_prefix_id') ? $employee_data[0]->name_prefix_id : $employee_current_data[0]->name_prefix_id,
                'first_name' => $request->has('approve_first_name') ? $employee_data[0]->first_name : $employee_current_data[0]->first_name,
                'middle_name' => $request->has('approve_middle_name') ? $employee_data[0]->middle_name : $employee_current_data[0]->middle_name,
                'last_name' => $request->has('approve_last_name') ? $employee_data[0]->last_name : $employee_current_data[0]->last_name,
                'name_suffix_id' => $request->has('approve_name_suffix_id') ? $employee_data[0]->name_suffix_id : $employee_current_data[0]->name_suffix_id,
                'birth_place' => $request->has('approve_birth_place') ? $employee_data[0]->birth_place : $employee_current_data[0]->birth_place,
                'birthdate' => $request->has('approve_birthdate') ? $employee_data[0]->birthdate : $employee_current_data[0]->birthdate,
                'age' => $request->has('approve_age') ? $employee_data[0]->age : $employee_current_data[0]->age,
                'height' => $request->has('approve_height') ? $employee_data[0]->height : $employee_current_data[0]->height,
                'weight' => $request->has('approve_weight') ? $employee_data[0]->weight : $employee_current_data[0]->weight,
                'gender_id' => $request->has('approve_gender_id') ? $employee_data[0]->gender_id : $employee_current_data[0]->gender_id,
                'civil_status_id' => $request->has('approve_civil_status_id') ? $employee_data[0]->civil_status_id : $employee_current_data[0]->civil_status_id,
                'citizenship_id' => $request->has('approve_citizenship_id') ? $employee_data[0]->citizenship_id : $employee_current_data[0]->citizenship_id,
                'religion_id' => $request->has('approve_religion_id') ? $employee_data[0]->religion_id : $employee_current_data[0]->religion_id,
                'blood_type_id' => $request->has('approve_blood_type_id') ? $employee_data[0]->blood_type_id : $employee_current_data[0]->blood_type_id,
                // address info
                'ra_region' => $request->has('approve_ra_region') ? $employee_data[0]->ra_region : $employee_current_data[0]->ra_region,
                'ra_province' => $request->has('approve_ra_province') ? $employee_data[0]->ra_province : $employee_current_data[0]->ra_province,
                'ra_city' => $request->has('approve_ra_city') ? $employee_data[0]->ra_city : $employee_current_data[0]->ra_city,
                'ra_barangay' => $request->has('approve_ra_barangay') ? $employee_data[0]->ra_barangay : $employee_current_data[0]->ra_barangay,
                'ra_house_no' => $request->has('approve_ra_house_no') ? $employee_data[0]->ra_house_no : $employee_current_data[0]->ra_house_no,
                'ra_street' => $request->has('approve_ra_street') ? $employee_data[0]->ra_street : $employee_current_data[0]->ra_street,
                'ra_village' => $request->has('approve_ra_village') ? $employee_data[0]->ra_village : $employee_current_data[0]->ra_village,
                'pa_region' => $request->has('approve_pa_region') ? $employee_data[0]->pa_region : $employee_current_data[0]->pa_region,
                'pa_province' => $request->has('approve_pa_province') ? $employee_data[0]->pa_province : $employee_current_data[0]->pa_province,
                'pa_city' => $request->has('approve_pa_city') ? $employee_data[0]->pa_city : $employee_current_data[0]->pa_city,
                'pa_barangay' => $request->has('approve_pa_barangay') ? $employee_data[0]->pa_barangay : $employee_current_data[0]->pa_barangay,
                'pa_house_no' => $request->has('approve_pa_house_no') ? $employee_data[0]->pa_house_no : $employee_current_data[0]->pa_house_no,
                'pa_street' => $request->has('approve_pa_street') ? $employee_data[0]->pa_street : $employee_current_data[0]->pa_street,
                'pa_village' => $request->has('approve_pa_village') ? $employee_data[0]->pa_village : $employee_current_data[0]->pa_village,
                // family info
                'father_name_prefix_id' => $request->has('approve_father_name_prefix_id') ? $employee_data[0]->father_name_prefix_id : $employee_current_data[0]->father_name_prefix_id,
                'father_first_name' => $request->has('approve_father_first_name') ? $employee_data[0]->father_first_name : $employee_current_data[0]->father_first_name,
                'father_middle_name' => $request->has('approve_father_middle_name') ? $employee_data[0]->father_middle_name : $employee_current_data[0]->father_middle_name,
                'father_last_name' => $request->has('approve_father_last_name') ? $employee_data[0]->father_last_name : $employee_current_data[0]->father_last_name,
                'father_name_suffix_id' => $request->has('approve_father_name_suffix_id') ? $employee_data[0]->father_name_suffix_id : $employee_current_data[0]->father_name_suffix_id,
                'mother_name_prefix_id' => $request->has('approve_mother_name_prefix_id') ? $employee_data[0]->mother_name_prefix_id : $employee_current_data[0]->mother_name_prefix_id,
                'mother_first_name' => $request->has('approve_mother_first_name') ? $employee_data[0]->mother_first_name : $employee_current_data[0]->mother_first_name,
                'mother_middle_name' => $request->has('approve_mother_middle_name') ? $employee_data[0]->mother_middle_name : $employee_current_data[0]->mother_middle_name,
                'mother_last_name' => $request->has('approve_mother_last_name') ? $employee_data[0]->mother_last_name : $employee_current_data[0]->mother_last_name,
                'mother_name_suffix_id' => $request->has('approve_mother_name_suffix_id') ? $employee_data[0]->mother_name_suffix_id : $employee_current_data[0]->mother_name_suffix_id,
                'spouse_name_prefix_id' => $request->has('approve_spouse_name_prefix_id') ? $employee_data[0]->spouse_name_prefix_id : $employee_current_data[0]->spouse_name_prefix_id,
                'spouse_first_name' => $request->has('approve_spouse_first_name') ? $employee_data[0]->spouse_first_name : $employee_current_data[0]->spouse_first_name,
                'spouse_middle_name' => $request->has('approve_spouse_middle_name') ? $employee_data[0]->spouse_middle_name : $employee_current_data[0]->spouse_middle_name,
                'spouse_last_name' => $request->has('approve_spouse_last_name') ? $employee_data[0]->spouse_last_name : $employee_current_data[0]->spouse_last_name,
                'spouse_name_suffix_id' => $request->has('approve_spouse_name_suffix_id') ? $employee_data[0]->spouse_name_suffix_id : $employee_current_data[0]->spouse_name_suffix_id,
                'spouse_occupation' => $request->has('approve_spouse_occupation') ? $employee_data[0]->spouse_occupation : $employee_current_data[0]->spouse_occupation,
                'spouse_employer' => $request->has('approve_spouse_employer') ? $employee_data[0]->spouse_employer : $employee_current_data[0]->spouse_employer,
                'spouse_business_address' => $request->has('approve_spouse_business_address') ? $employee_data[0]->spouse_business_address : $employee_current_data[0]->spouse_business_address,
                // dual citizenship info
                'is_dual_citizent' => $request->has('approve_is_dual_citizent') ? $employee_data[0]->is_dual_citizent : $employee_current_data[0]->is_dual_citizent,
                'by_birth' => $request->has('approve_by_birth') ? $employee_data[0]->by_birth : $employee_current_data[0]->by_birth,
                'by_naturalization' => $request->has('approve_by_naturalization') ? $employee_data[0]->by_naturalization : $employee_current_data[0]->by_naturalization,
                'indicate_country' => $request->has('approve_indicate_country') ? $employee_data[0]->indicate_country : $employee_current_data[0]->indicate_country,
            );

            // Update Approved Request log
            DB::table('employees')->where('id', $employee_data[0]->employee_id)->update($employee_info);

            // Save Children Info.
            $data_child = DB::table('employee_children_temps')->where('request_id', $id)->get();

            $arr_len_children = count($data_child);
            $children_data = [];

            DB::table('employee_children')->where('employee_id', $employee_data[0]->employee_id)->delete();

            for ($i = 0; $i < $arr_len_children; $i++) {
                if ($data_child[$i]->child_name != NULL) {

                    $child_id = 1 + DB::table('employee_children')->max('children_id');

                    $children_data = [
                        'employee_id' => $employee_data[0]->employee_id,
                        'child_name' => $data_child[$i]->child_name,
                        'child_middlename' => $data_child[$i]->child_middlename,
                        'child_lastname' => $data_child[$i]->child_lastname,
                        'child_birthdate' => $data_child[$i]->child_birthdate
                    ];

                    // DB::table('employee_children')->Insert($children_data);
                    DB::table('employee_children')->updateOrInsert(['children_id' => $child_id], $children_data);
                }
            }

            // Save Education
            $data_educ = DB::table('employee_educations_temps')->where('request_id', $id)->get();
            $arr_len_educ = count($data_educ);
            $educ_data = [];

            DB::table('employee_educations')->where('employee_id', $employee_data[0]->employee_id)->delete();

            for ($i = 0; $i < $arr_len_educ; $i++) {
                if ($data_educ[$i]->school_name != NULL) {

                    $educ_id = 1 + DB::table('employee_educations')->max('education_id');

                    $educ_data = [
                        'employee_id' => $employee_data[0]->employee_id,
                        'school_name' => $data_educ[$i]->school_name,
                        'academic_level_id' => $data_educ[$i]->academic_level_id,
                        'program' => $data_educ[$i]->program,
                        'from' => $data_educ[$i]->from,
                        'to' => $data_educ[$i]->to,
                        'graduated_year' => $data_educ[$i]->graduated_year,
                        'units_earned' => $data_educ[$i]->units_earned,
                        'honors' => $data_educ[$i]->honors
                    ];

                    // DB::table('employee_educations')->insertOrIgnore($educ_data);
                    DB::table('employee_educations')->updateOrInsert(['education_id' => $educ_id], $educ_data);
                }
            }

            // Save Service Records
            $data_serv = DB::table('service_records_temps')->where('request_id', $id)->get();
            $arr_len_serv = count($data_serv);
            $serv_data = [];

            DB::table('service_records')->where('employee_id', $employee_data[0]->employee_id)->delete();

            for ($i = 0; $i < $arr_len_serv; $i++) {
                if ($data_serv[$i]->designation != NULL) {

                    $serv_id = 1 + DB::table('service_records')->max('service_record_id');

                    $serv_data = [
                        'employee_id' => $employee_data[0]->employee_id,
                        'start_date' => $data_serv[$i]->start_date,
                        'end_date' => $data_serv[$i]->end_date,
                        'designation' => $data_serv[$i]->designation,
                        'employment_type' => $data_serv[$i]->employment_type,
                        'annual_salary' => $data_serv[$i]->annual_salary,
                        'place_of_assignment' => $data_serv[$i]->place_of_assignment,
                        'leave_without_pay' => $data_serv[$i]->leave_without_pay,
                        'separation_date' => $data_serv[$i]->separation_date,
                        'cause' => $data_serv[$i]->cause,
                        'branch' => $data_serv[$i]->branch,
                    ];

                    // DB::table('service_records')->insert($serv_data);
                    DB::table('service_records')->updateOrInsert(['service_record_id' => $serv_id], $serv_data);
                }
            }

            // Save Employment Records
            $data_emp = DB::table('employee_employment_records_temps')->where('request_id', $id)->get();
            $arr_len_emp = count($data_emp);
            $emp_data = [];

            DB::table('employee_employment_records')->where('employee_id', $employee_data[0]->employee_id)->delete();

            for ($i = 0; $i < $arr_len_emp; $i++) {
                if ($data_emp[$i]->work_company != NULL) {

                    $emp_id = 1 + DB::table('employee_employment_records')->max('employment_record_id');

                    $emp_data = [
                        'employee_id' => $employee_data[0]->employee_id,
                        'work_start_date' => $data_emp[$i]->work_start_date,
                        'work_end_date' => $data_emp[$i]->work_end_date,
                        'work_company' => $data_emp[$i]->work_company,
                        'monthly_salary' => $data_emp[$i]->monthly_salary,
                        'salary_grade_step' => $data_emp[$i]->salary_grade_step,
                        'status_of_appointment' => $data_emp[$i]->status_of_appointment,
                        'position' => $data_emp[$i]->position,
                        'government_service_id' => $data_emp[$i]->government_service_id,
                    ];

                    // DB::table('employee_employment_records')->insertOrIgnore($emp_data);
                    DB::table('employee_employment_records')->updateOrInsert(['employment_record_id' => $emp_id], $emp_data);
                }
            }

            // Save Eaminations
            $data_exam = DB::table('employee_examinations_temps')->where('request_id', $id)->get();
            $arr_len_exam = count($data_exam);
            $exam_data = [];

            DB::table('employee_examinations')->where('employee_id', $employee_data[0]->employee_id)->delete();

            for ($i = 0; $i < $arr_len_exam; $i++) {
                if ($data_exam[$i]->place_of_exam != NULL) {

                    $exam_id = 1 + DB::table('employee_examinations')->max('examination_id');

                    $exam_data = [
                        'employee_id' => $employee_data[0]->employee_id,
                        'eligibility_id' => $data_exam[$i]->eligibility_id,
                        'exam_rating' => $data_exam[$i]->exam_rating,
                        'exam_date' => $data_exam[$i]->exam_date,
                        'place_of_exam' => $data_exam[$i]->place_of_exam,
                        'license_number' => $data_exam[$i]->license_number,
                        'date_released' => $data_exam[$i]->date_released,
                    ];

                    // DB::table('employee_examinations')->insertOrIgnore($exam_data);
                    DB::table('employee_examinations')->updateOrInsert(['examination_id' => $exam_id], $exam_data);
                }
            }

            // Save Training
            $data_training = DB::table('employee_trainings_temps')->where('request_id', $id)->get();
            $arr_len_training = count($data_training);
            $training_data = [];

            DB::table('employee_trainings')->where('employee_id', $employee_data[0]->employee_id)->delete();

            for ($i = 0; $i < $arr_len_training; $i++) {
                if ($data_training[$i]->training != NULL) {

                    $training_id = 1 + DB::table('employee_trainings')->max('training_id');

                    $training_data = [
                        'employee_id' => $employee_data[0]->employee_id,
                        'training' => $data_training[$i]->training,
                        'training_from' => $data_training[$i]->training_from,
                        'training_to' => $data_training[$i]->training_to,
                        'hours' => $data_training[$i]->hours,
                        'sponsored_by' => $data_training[$i]->sponsored_by,
                        'learning_id' => $data_training[$i]->learning_id,
                    ];

                    // DB::table('employee_trainings')->insertOrIgnore($training_data);
                    DB::table('employee_trainings')->updateOrInsert(['training_id' => $training_id], $training_data);
                }
            }

            // Save Organizations
            $data_org = DB::table('employee_organizations_temps')->where('request_id', $id)->get();
            $arr_len_org = count($data_org);
            $org_data = [];

            DB::table('employee_organizations')->where('employee_id', $employee_data[0]->employee_id)->delete();

            for ($i = 0; $i < $arr_len_org; $i++) {
                if ($data_org[$i]->organization != NULL) {

                    $organization_id = 1 + DB::table('employee_organizations')->max('organization_id');

                    $org_data = [
                        'employee_id' => $employee_data[0]->employee_id,
                        'organization' => $data_org[$i]->organization,
                        'org_from' => $data_org[$i]->org_from,
                        'org_to' => $data_org[$i]->org_to,
                        'org_hours' => $data_org[$i]->org_hours,
                        'org_position' => $data_org[$i]->org_position,
                    ];

                    // DB::table('employee_organizations')->insertOrIgnore($org_data);
                    DB::table('employee_organizations')->updateOrInsert(['organization_id' => $organization_id], $org_data);
                }
            }

            // Save Recognitions
            $data_recog = DB::table('employee_recognations_temps')->where('request_id', $id)->get();
            $arr_len_recog = count($data_recog);
            $recog_data = [];

            DB::table('employee_recognations')->where('employee_id', $employee_data[0]->employee_id)->delete();

            for ($i = 0; $i < $arr_len_recog; $i++) {
                if ($data_recog[$i]->recognation != NULL) {

                    $recognation_id = 1 + DB::table('employee_recognations')->max('recognation_id');

                    $recog_data = [
                        'employee_id' => $employee_data[0]->employee_id,
                        'recognation' => $data_recog[$i]->recognation,
                    ];

                    // DB::table('employee_recognations')->insertOrIgnore($recog_data);
                    DB::table('employee_recognations')->updateOrInsert(['recognation_id' => $recognation_id], $recog_data);
                }
            }

            // Save Skills
            $data_skill = DB::table('employee_skills_temps')->where('request_id', $id)->get();
            $arr_len_skill = count($data_skill);
            $skill_data = [];

            DB::table('employee_skills')->where('employee_id', $employee_data[0]->employee_id)->delete();

            for ($i = 0; $i < $arr_len_skill; $i++) {
                if ($data_skill[$i]->skill != NULL) {

                    $skill_id = 1 + DB::table('employee_skills')->max('skill_id');

                    $skill_data = [
                        'employee_id' => $employee_data[0]->employee_id,
                        'skill' => $data_skill[$i]->skill,
                    ];

                    // DB::table('employee_skills')->insertOrIgnore($skill_data);
                    DB::table('employee_skills')->updateOrInsert(['skill_id' => $skill_id], $skill_data);
                }
            }

            // Save Memberships
            $data_mem = DB::table('employee_memberships_temps')->where('request_id', $id)->get();
            $arr_len_mem = count($data_mem);
            $mem_data = [];

            DB::table('employee_memberships')->where('employee_id', $employee_data[0]->employee_id)->delete();

            for ($i = 0; $i < $arr_len_mem; $i++) {
                if (
                    $data_mem[$i]->membership != NULL
                ) {

                    $membership_id = 1 + DB::table('employee_memberships')->max('membership_id');

                    $mem_data = [
                        'employee_id' => $employee_data[0]->employee_id,
                        'membership' => $data_mem[$i]->membership,
                    ];

                    // DB::table('employee_memberships')->insertOrIgnore($mem_data);
                    DB::table('employee_memberships')->updateOrInsert(['membership_id' => $membership_id], $mem_data);
                }
            }

            // Save References
            $data_ref = DB::table('employee_references_temps')->where('request_id', $id)->get();
            $arr_len_ref = count($data_ref);
            $ref_data = [];

            DB::table('employee_references')->where('employee_id', $employee_data[0]->employee_id)->delete();

            for ($i = 0; $i < $arr_len_ref; $i++) {
                if (
                    $data_ref[$i]->ref_name != NULL
                ) {

                    $reference_id = 1 + DB::table('employee_references')->max('reference_id');

                    $ref_data = [
                        'employee_id' => $employee_data[0]->employee_id,
                        'ref_name' => $data_ref[$i]->ref_name,
                        'ref_address' => $data_ref[$i]->ref_address,
                        'ref_occupation' => $data_ref[$i]->ref_occupation,
                        'ref_contact_no' => $data_ref[$i]->ref_contact_no,
                        'ref_email' => $data_ref[$i]->ref_email,
                    ];

                    // DB::table('employee_references')->insertOrIgnore($ref_data);
                    DB::table('employee_references')->updateOrInsert(['reference_id' => $reference_id], $ref_data);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Human Resource Module',
                'menu'    => 'Review 201 Updates',
                'activity' => 'Approved',
                'description' => 'Approved Requested 201 File Update.',
            );

            Audit::create($data_audit);
        } else {
            DB::table('employee_requests')->where('id', $id)->update(['status_id' => 3, 'remarks' => $request->remarks]);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Human Resource Module',
                'menu'    => 'Review 201 Updates',
                'activity' => 'Dispproved',
                'description' => 'Dispproved Requested 201 File Update.',
            );

            Audit::create($data_audit);
        }

        $requests = DB::table('employee_requests as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->select(
                'a.id',
                'a.employee_id',
                'b.employee_no',
                'b.photo',
                DB::raw("CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name) as name"),
                'a.request_date',
                'a.remarks',
                'a.status_id'
            )
            ->orderBy('request_date', 'asc')
            ->get();

        if ($type_id == 1) {
            return $this->successResponse(null, 'You have successfully approved a request!');
        } else {
            return $this->successResponse(null, 'You have successfully disapproved a request!');
        }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process employee request approval: ' . $e->getMessage());
        }
    }
}
