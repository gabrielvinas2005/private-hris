<?php

namespace App\Http\Controllers;

use Auth;
use Image;
use App\Audit;
use App\Notifications\Email201Update;
use App\User;
use Carbon\Carbon;
use Notification;
use App\Support\PayrollBenefitsEmployeeScope;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;


class EmployeesController extends Controller
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

    public function index(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if (Auth::user()->access_all_branches) {
                $query = DB::table('employees')
                    ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                    ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                    ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                    ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                    ->select(
                        'employees.photo',
                        'employees.id',
                        'employees.employee_no',
                        'employees.email',
                        'employment_types.name as employment_type',
                        'positions.name as position',
                        'departments.name as department',
                        'branches.name as branch',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                    )
                    ->where([
                        'employees.is_employee' => true,
                        'employees.active' => true
                    ]);
                if ($request->filled('department_id')) {
                    $query->where('employees.department_id', $request->department_id);
                }
                if ($request->boolean('plantilla_only') || $request->boolean('payroll_benefits_only')) {
                    PayrollBenefitsEmployeeScope::apply($query, 'employees');
                }
                $data = $query->orderBy('employees.first_name', 'asc')->get();
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->get();

                $query = DB::table('employees')
                    ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                    ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                    ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                    ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                    ->select(
                        'employees.photo',
                        'employees.id',
                        'employees.employee_no',
                        'employees.email',
                        'employment_types.name as employment_type',
                        'positions.name as position',
                        'departments.name as department',
                        'branches.name as branch',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                    )
                    ->where([
                        'employees.is_employee' => true,
                        'employees.active' => true,
                        'employees.branch_id' => $user_branch_id[0]->branch_id
                    ]);
                if ($request->filled('department_id')) {
                    $query->where('employees.department_id', $request->department_id);
                }
                if ($request->boolean('plantilla_only') || $request->boolean('payroll_benefits_only')) {
                    PayrollBenefitsEmployeeScope::apply($query, 'employees');
                }
                $data = $query->orderBy('employees.first_name', 'asc')->get();
            }

        return $this->successResponse($data, 'Employees retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employees: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            $app_key = env("APP_KEY", "");
        $prefixes = DB::table('name_prefixes')->where('active', true)->orderBy('id', 'asc')->get();
        $suffixes = DB::table('name_suffixes')->where('active', true)->orderBy('name', 'asc')->get();
        $genders = DB::table('genders')->where('active', true)->orderBy('name', 'asc')->get();
        $civil_status = DB::table('civil_status')->where('active', true)->orderBy('id', 'asc')->get();
        $citizenships = DB::table('citizenships')->where('active', true)->orderBy('id', 'asc')->get();
        $religions = DB::table('religions')->where('active', true)->orderBy('id', 'asc')->get();
        $blood_types = DB::table('blood_types')->orderBy('name', 'asc')->get();
        $companies = DB::table('companies')->get();
        $branches = DB::table('branches')->orderBy('id', 'asc')->get();
        $departments = DB::table('departments')->where('active', true)->orderBy('name', 'asc')->get();
        $employment_types = DB::table('employment_types')->where('active', true)->orderBy('id', 'asc')->get();
        $positions = DB::table('positions')->where('active', true)->orderBy('name', 'asc')->get();

        // Get Plantilla
        $plantilla_emp = DB::table('plantillas')
            ->join('positions', 'positions.id', '=', 'plantillas.position_id')
            ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
            ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
            ->select('plantillas.*')
            ->where(['plantillas.employee_id' => $id, 'plantillas.active' => true]);

        $plantillas = DB::table('plantillas')
            ->join('positions', 'positions.id', '=', 'plantillas.position_id')
            ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
            ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
            ->select('plantillas.*')
            ->where(['plantillas.employee_id' => 0, 'plantillas.active' => true])->union($plantilla_emp)->get();

        $salary_grades = DB::table('salary_grades')->where('active', true)->orderBy('id', 'asc')->get();
        $salary_steps = DB::table('salary_steps')->where('active', true)->orderBy('id', 'asc')->get();
        $payroll_intervals = DB::table('payroll_intervals')->where('active', true)->orderBy('id', 'asc')->get();
        $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();
        $learnings = DB::table('learnings')->where('active', true)->orderBy('name', 'asc')->get();
        $divisions = DB::table('divisions')->where('active', true)->orderBy('name', 'asc')->get();
        $sections = DB::table('sections')->where('active', true)->orderBy('name', 'asc')->get();

        if ($id == 0) {
            $employee_info = array(
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
                'is_hold' => false,
                'date_hired' => '',
                'tin_no' => '',
                'gsis_no' => '',
                'sss_no' => '',
                'pagibig_no' => '',
                'philhealth_no' => '',
                'crn_no' => '',
                'salary' => '',
                'tax_amount' => '',
                'gsis_amount' => '',
                'sss_amount' => '',
                'pagibig_amount' => '',
                'philhealth_amount' => '',
                'payroll_interval_id' => 0,
                'end_date' => '',
                'account_no' => null,
                'hold_remarks' => ''
            );

            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', 0)->get();

            $employee_info = (object)$employee_info;
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
                    'crn_no',
                    'salary',
                    'tax_amount',
                    'gsis_amount',
                    'sss_amount',
                    'pagibig_amount',
                    'philhealth_amount',
                    'payroll_interval_id',
                    'end_date',
                    'account_no',
                    'is_hold',
                    'hold_remarks'
                )
                ->where('id', $id)
                ->orderBy('employees.first_name', 'asc')
                ->get();

            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', $employee_info[0]->plantilla_id)->get();
        }

        $children = DB::table('employee_children')->where('employee_id', $id)->get();
        $educations = DB::table('employee_educations')->where('employee_id', $id)->orderBy('employee_educations.graduated_year', 'desc')->get();
        $service_records = DB::table('service_records')->where('employee_id', $id)->get();
        $employments = DB::table('employee_employment_records')->where('employee_id', $id)->orderBy('work_start_date', 'asc')->get();
        $examinations = DB::table('employee_examinations')->where('employee_id', $id)->get();
        $trainings = DB::table('employee_trainings')->where('employee_id', $id)->get();
        $organizations = DB::table('employee_organizations')->where('employee_id', $id)->get();
        $recognitions = DB::table('employee_recognations')->where('employee_id', $id)->get();
        $skills = DB::table('employee_skills')->where('employee_id', $id)->get();
        $memberships = DB::table('employee_memberships')->where('employee_id', $id)->get();
        $references = DB::table('employee_references')->where('employee_id', $id)->get();
        $dependents = DB::table('employee_dependents')->where('employee_id', $id)->get();
        $documents = DB::table('employee_documents')->where('employee_id', $id)->get();

        $employee_documents = DB::table('employee_documents as a')
            ->leftJoin('document_types as b', 'a.document_type_id', '=', 'b.id')
            ->select(
                'a.employee_document_id',
                'a.description',
                'a.attachment_name',
                'a.created_at',
                'a.document_type_id',
                'b.name as document_type'
            )
            ->where('a.employee_id', $id)
            ->get();

        $document_types = DB::table('document_types')->where('active', true)->orderBy('name', 'asc')->get();

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

        $quesionaires_setup = DB::table('pds_questionaires as a')
            ->select(
                'a.id',
                'a.code',
                'a.questions',
                'a.is_yes',
                'a.is_no',
                'a.yes_details',
                'a.case_status',
                'a.date_filed'
            )
            ->whereNotIn('a.id', function ($query) use ($id) {
                $query->select('question_id')->from('employee_pds_answers')->where('employee_id', $id);
            });

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
            ->union($quesionaires_setup)
            ->orderBy('id', 'asc')
            ->get();

        if ($quesionaires->isEmpty()) {
            $quesionaires = DB::table('pds_questionaires')->orderBy('id', 'asc')->get();
        }

        $is_employee_portal = 0;

        return $this->successResponse([
            'employee_id' => $id,
            'form_data' => [
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
                'plantillas_selected' => $plantillas_selected,
                'salary_grade_steps' => $salary_grade_steps,
                'quesionaires' => $quesionaires,
                'divisions' => $divisions,
                'sections' => $sections,
                'document_types' => $document_types,
            ],
            'employee_info' => $employee_info,
            'related_data' => [
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
                'dependents' => $dependents,
                'documents' => $documents,
                'employee_documents' => $employee_documents,
            ],
            'is_employee_portal' => $is_employee_portal,
        ], $id == 0 ? 'Employee form data loaded for new employee' : 'Employee form data loaded for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employee form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id, $is_employee_portal)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($id == 0) {
                // Validations
                $emp_email = DB::table('employees')->whereRaw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN email ELSE dbo.ufn_DecryptString(email,'$app_key') END = '$request->email'")->get();

                if ($emp_email->isNotEmpty()) {
                    return $this->errorResponse('Email already been taken.', 400);
                }

            $request->validate([
                'employee_no' => 'required|unique:employees',
                'email' => 'required|unique:employees',
                'name_prefix_id' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'birthdate' => 'required|date|date_format:Y-m-d',
                'age' => 'required',
                'gender_id' => 'required',
                'civil_status_id' => 'required',
                'citizenship_id' => 'required',
                'religion_id' => 'required',
                'department_id' => 'required',
                'employment_type_id' => 'required',
                'position_id' => 'required',
                'payroll_interval_id' => 'required',
                'salary' => 'required',

                'ra_postal_id' => 'required',
                'pa_postal_id' => 'required',
                'ra_region' => 'required',
                'pa_region' => 'required',
            ]);
        } else {
            // Validations
            $request->validate([
                'photo' => 'image|max:3000',
                'employee_no' => 'required|unique:employees,employee_no' . ($id ? ",$id" : ''),
                'email' => 'required|unique:employees,email' . ($id ? ",$id" : ''),
                'name_prefix_id' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'birthdate' => 'required|date|date_format:Y-m-d',
                'age' => 'required',
                'gender_id' => 'required',
                'civil_status_id' => 'required',
                'citizenship_id' => 'required',
                'religion_id' => 'required',
                'department_id' => 'required',
                'employment_type_id' => 'required',
                'position_id' => 'required',
                'payroll_interval_id' => 'required',
                'salary' => 'required',

                'ra_postal_id' => 'required',
                'pa_postal_id' => 'required',
                'ra_region' => 'required',
                'pa_region' => 'required',
            ]);
        }

        if ($request->department_id == 0) {
            return $this->errorResponse('Department is required!', 400);
        }

        if ($request->employment_type_id == 0) {
            return $this->errorResponse('Employment Type is required!', 400);
        }

        if ($request->position_id == 0) {
            return $this->errorResponse('Employee Position is required!', 400);
        }

        if ($request->height == 0 || $request->height == null || $request->height == '') {
            $height = 0;
        } else {
            $height = $request->height;
        }

        if ($request->weight == 0 || $request->weight == null || $request->weight == '') {
            $weight = 0;
        } else {
            $weight = $request->weight;
        }

        if ($request->plantilla_id == 0 || $request->plantilla_id == null || $request->plantilla_id == '') {
            $plantilla_id = 0;
        } else {
            $plantilla_id = $request->plantilla_id;
        }

        if ($request->age > 100) {
            return $this->errorResponse('Invalid Birth Date!', 400);
        }

        // Employee Info Insert
        $is_encrypted = DB::table('employees')->where('id', $id)->get();

        if ($is_encrypted->isNotEmpty()) {
            if ($is_encrypted[0]->is_encrypted == true) {
                $encrypted = [
                    'first_name' => DB::select("select dbo.ufn_EncryptString('$request->first_name') as first_name", [1])[0],
                    'middle_name' => DB::select("select dbo.ufn_EncryptString('$request->middle_name') as middle_name", [1])[0],
                    'last_name' => DB::select("select dbo.ufn_EncryptString('$request->last_name') as last_name", [1])[0],
                    'email' => DB::select("select dbo.ufn_EncryptString('$request->email') as email", [1])[0],
                    'telephone_no' => DB::select("select dbo.ufn_EncryptString('$request->telephone_no') as telephone_no", [1])[0],
                    'mobile_no' => DB::select("select dbo.ufn_EncryptString('$request->mobile_no') as mobile_no", [1])[0],
                    'father_first_name' => DB::select("select dbo.ufn_EncryptString('$request->father_first_name') as father_first_name", [1])[0],
                    'father_middle_name' => DB::select("select dbo.ufn_EncryptString('$request->father_middle_name') as father_middle_name", [1])[0],
                    'father_last_name' => DB::select("select dbo.ufn_EncryptString('$request->father_last_name') as father_last_name", [1])[0],
                    'mother_first_name' => DB::select("select dbo.ufn_EncryptString('$request->mother_first_name') as mother_first_name", [1])[0],
                    'mother_middle_name' => DB::select("select dbo.ufn_EncryptString('$request->mother_middle_name') as mother_middle_name", [1])[0],
                    'mother_last_name' => DB::select("select dbo.ufn_EncryptString('$request->mother_last_name') as mother_last_name", [1])[0],
                    'spouse_first_name' => DB::select("select dbo.ufn_EncryptString('$request->spouse_first_name') as spouse_first_name", [1])[0],
                    'spouse_middle_name' => DB::select("select dbo.ufn_EncryptString('$request->spouse_middle_name') as spouse_middle_name", [1])[0],
                    'spouse_last_name' => DB::select("select dbo.ufn_EncryptString('$request->spouse_last_name') as spouse_last_name", [1])[0],
                ];

                $first_name = $encrypted['first_name']->first_name;
                $middle_name = $encrypted['middle_name']->middle_name;
                $last_name = $encrypted['last_name']->last_name;
                $email = $encrypted['email']->email;
                $telephone_no = $encrypted['telephone_no']->telephone_no;
                $mobile_no = $encrypted['mobile_no']->mobile_no;
                $father_first_name = $encrypted['father_first_name']->father_first_name;
                $father_middle_name = $encrypted['father_middle_name']->father_middle_name;
                $father_last_name = $encrypted['father_last_name']->father_last_name;
                $mother_first_name = $encrypted['mother_first_name']->mother_first_name;
                $mother_middle_name = $encrypted['mother_middle_name']->mother_middle_name;
                $mother_last_name = $encrypted['mother_last_name']->mother_last_name;
                $spouse_first_name = $encrypted['spouse_first_name']->spouse_first_name;
                $spouse_middle_name = $encrypted['spouse_middle_name']->spouse_middle_name;
                $spouse_last_name = $encrypted['spouse_last_name']->spouse_last_name;
            } else {
                $first_name = $request->first_name;
                $middle_name = $request->middle_name;
                $last_name = $request->last_name;
                $email = $request->email;
                $telephone_no = $request->telephone_no;
                $mobile_no = $request->mobile_no;
                $father_first_name = $request->father_first_name;
                $father_middle_name = $request->father_middle_name;
                $father_last_name = $request->father_last_name;
                $mother_first_name = $request->mother_first_name;
                $mother_middle_name = $request->mother_middle_name;
                $mother_last_name = $request->mother_last_name;
                $spouse_first_name = $request->spouse_first_name;
                $spouse_middle_name = $request->spouse_middle_name;
                $spouse_last_name = $request->spouse_last_name;
            }
        } else {
            $first_name = $request->first_name;
            $middle_name = $request->middle_name;
            $last_name = $request->last_name;
            $email = $request->email;
            $telephone_no = $request->telephone_no;
            $mobile_no = $request->mobile_no;
            $father_first_name = $request->father_first_name;
            $father_middle_name = $request->father_middle_name;
            $father_last_name = $request->father_last_name;
            $mother_first_name = $request->mother_first_name;
            $mother_middle_name = $request->mother_middle_name;
            $mother_last_name = $request->mother_last_name;
            $spouse_first_name = $request->spouse_first_name;
            $spouse_middle_name = $request->spouse_middle_name;
            $spouse_last_name = $request->spouse_last_name;
        }

        $same_email = DB::table('employees')
            ->where('id', '<>', $id)
            ->where('email', $email)
            ->count();

        if ($same_email > 0) {
            return $this->errorResponse('Same email already exist in the record. Emails must be unique!');
        }

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
            $photo = base64_encode($image);
        } else {
            $photo = DB::table('employees')
                ->select(
                    db::raw("ISNULL(photo,'') AS photo")
                )
                ->where('id', $id)
                ->get();
        }

        if ($request->hasFile('photo')) {
            // Employee Infor Insert
            $employee_info = [
                // basic info
                'photo' => $photo,
                'employee_no' => $request->employee_no,
                'access_no' => $request->access_no,
                'email' => $email,
                'mobile_no' => $mobile_no,
                'telephone_no' => $telephone_no,
                'tin_no' => $request->tin_no,
                'gsis_no' => $request->gsis_no,
                'sss_no' => $request->sss_no,
                'pagibig_no' => $request->pagibig_no,
                'philhealth_no' => $request->philhealth_no,
                'crn_no' => $request->crn_no,
                'name_prefix_id' => $request->name_prefix_id,
                'first_name' =>  $first_name,
                'middle_name' =>  $middle_name,
                'last_name' =>  $last_name,
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
                'blood_type_id' => ($request->blood_type_id == null) ? 0 : $request->blood_type_id,
                // address info
                'ra_postal_id' => $request->ra_postal_id,
                'ra_region' => $request->ra_region,
                'ra_province' => $request->ra_province,
                'ra_city' => $request->ra_city,
                'ra_barangay' => $request->ra_barangay,
                'ra_house_no' => $request->ra_house_no,
                'ra_street' => $request->ra_street,
                'ra_village' => $request->ra_village,
                'pa_postal_id' => $request->pa_postal_id,
                'pa_region' => $request->pa_region,
                'pa_province' => $request->pa_province,
                'pa_city' => $request->pa_city,
                'pa_barangay' => $request->pa_barangay,
                'pa_house_no' => $request->pa_house_no,
                'pa_street' => $request->pa_street,
                'pa_village' => $request->pa_village,
                // family info
                'father_name_prefix_id' => $request->father_name_prefix_id,
                'father_first_name' => $father_first_name,
                'father_middle_name' => $father_middle_name,
                'father_last_name' => $father_last_name,
                'father_name_suffix_id' => $request->father_name_suffix_id,
                'mother_name_prefix_id' => $request->mother_name_prefix_id,
                'mother_first_name' => $mother_first_name,
                'mother_middle_name' => $mother_middle_name,
                'mother_last_name' => $mother_last_name,
                'mother_name_suffix_id' => $request->mother_name_suffix_id,
                'spouse_name_prefix_id' => $request->spouse_name_prefix_id,
                'spouse_first_name' => $spouse_first_name,
                'spouse_middle_name' => $spouse_middle_name,
                'spouse_last_name' => $spouse_last_name,
                'spouse_name_suffix_id' => $request->spouse_name_suffix_id,
                'spouse_occupation' => $request->spouse_occupation,
                'spouse_employer' => $request->spouse_employer,
                'spouse_business_address' => $request->spouse_business_address,
                'spouse_mobile_no' => $request->spouse_mobile_no,
                // dual citizenship info
                'is_dual_citizent' => $request->has('is_dual_citizent') ? true : false,
                'by_birth' => $request->customRadio == 'by_birth' ? true : false,
                'by_naturalization' => $request->customRadio == 'by_nat' ? true : false,
                'indicate_country' => $request->indicate_country,
                // work info
                'company_id' => $request->company_id,
                'branch_id' => 0,
                'department_id' => $request->department_id,
                'division_id' => 0,
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
                'payroll_interval_id' => isset($request->payroll_interval_id) ? $request->payroll_interval_id : 0,
                'salary' => isset($request->salary) ? $request->salary : 0,
                'tax_amount' => isset($request->tax_amount) ? $request->tax_amount : 0,
                'gsis_amount' => isset($request->gsis_amount) ? $request->gsis_amount : 0,
                'sss_amount' => isset($request->sss_amount) ? $request->sss_amount : 0,
                'pagibig_amount' => isset($request->pagibig_amount) ? $request->pagibig_amount : 0,
                'philhealth_amount' => isset($request->philhealth_amount) ? $request->philhealth_amount : 0,
                // default data
                'active' => true,
                'is_employee' => true,
                'account_no' => $request->account_no,
                'updated_at' => now()
            ];
        } else {
            $employee_info = [
                // basic info
                'employee_no' => $request->employee_no,
                'access_no' => $request->access_no,
                'email' => $email,
                'mobile_no' => $mobile_no,
                'telephone_no' => $telephone_no,
                'tin_no' => $request->tin_no,
                'gsis_no' => $request->gsis_no,
                'sss_no' => $request->sss_no,
                'pagibig_no' => $request->pagibig_no,
                'philhealth_no' => $request->philhealth_no,
                'crn_no' => $request->crn_no,
                'name_prefix_id' => $request->name_prefix_id,
                'first_name' =>  $first_name,
                'middle_name' =>  $middle_name,
                'last_name' =>  $last_name,
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
                'blood_type_id' => ($request->blood_type_id == null) ? 0 : $request->blood_type_id,
                // address info
                'ra_postal_id' => $request->ra_postal_id,
                'ra_region' => $request->ra_region,
                'ra_province' => $request->ra_province,
                'ra_city' => $request->ra_city,
                'ra_barangay' => $request->ra_barangay,
                'ra_house_no' => $request->ra_house_no,
                'ra_street' => $request->ra_street,
                'ra_village' => $request->ra_village,
                'pa_postal_id' => $request->pa_postal_id,
                'pa_region' => $request->pa_region,
                'pa_province' => $request->pa_province,
                'pa_city' => $request->pa_city,
                'pa_barangay' => $request->pa_barangay,
                'pa_house_no' => $request->pa_house_no,
                'pa_street' => $request->pa_street,
                'pa_village' => $request->pa_village,
                // family info
                'father_name_prefix_id' => $request->father_name_prefix_id,
                'father_first_name' => $father_first_name,
                'father_middle_name' => $father_middle_name,
                'father_last_name' => $father_last_name,
                'father_name_suffix_id' => $request->father_name_suffix_id,
                'mother_name_prefix_id' => $request->mother_name_prefix_id,
                'mother_first_name' => $mother_first_name,
                'mother_middle_name' => $mother_middle_name,
                'mother_last_name' => $mother_last_name,
                'mother_name_suffix_id' => $request->mother_name_suffix_id,
                'spouse_name_prefix_id' => $request->spouse_name_prefix_id,
                'spouse_first_name' => $spouse_first_name,
                'spouse_middle_name' => $spouse_middle_name,
                'spouse_last_name' => $spouse_last_name,
                'spouse_name_suffix_id' => $request->spouse_name_suffix_id,
                'spouse_occupation' => $request->spouse_occupation,
                'spouse_employer' => $request->spouse_employer,
                'spouse_business_address' => $request->spouse_business_address,
                'spouse_mobile_no' => $request->spouse_mobile_no,
                // dual citizenship info
                'is_dual_citizent' => $request->has('is_dual_citizent') ? true : false,
                'by_birth' => $request->customRadio == 'by_birth' ? true : false,
                'by_naturalization' => $request->customRadio == 'by_nat' ? true : false,
                'indicate_country' => $request->indicate_country,
                // work info
                'company_id' => $request->company_id,
                'branch_id' => 0,
                'department_id' => $request->department_id,
                'division_id' => 0,
                'section_id' => $request->section_id,
                'employment_type_id' => $request->employment_type_id,
                'date_hired' => $request->date_hired,
                'is_plantilla' => $request->has('is_plantilla') ? true : false,
                'is_teaching' => $request->has('is_teaching') ? true : false,
                'is_hold' => $request->has('is_hold') ? true : false,
                'hold_remarks' => $request->hold_remarks,
                'plantilla_id' => $plantilla_id,
                'salary_grade_id' => $request->salary_grade_id,
                'salary_step_id' => $request->salary_step_id,
                'position_id' => $request->position_id,
                'end_date' => $request->end_dates,
                // payroll related info
                'payroll_interval_id' => isset($request->payroll_interval_id) ? $request->payroll_interval_id : 0,
                'salary' => isset($request->salary) ? $request->salary : 0,
                'tax_amount' => isset($request->tax_amount) ? $request->tax_amount : 0,
                'gsis_amount' => isset($request->gsis_amount) ? $request->gsis_amount : 0,
                'sss_amount' => isset($request->sss_amount) ? $request->sss_amount : 0,
                'pagibig_amount' => isset($request->pagibig_amount) ? $request->pagibig_amount : 0,
                'philhealth_amount' => isset($request->philhealth_amount) ? $request->philhealth_amount : 0,
                // default data
                'active' => true,
                'is_employee' => true,
                'account_no' => $request->account_no,
                'updated_at' => now()
            ];
        }

        if ($id == 0 || $id == null) {
            $id = DB::table('employees')->max('id') + 1;
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
                    'child_birthdate' => $data_child["child_birthdate"][$i],
                    'child_gender_id' => $data_child["child_gender_id"][$i]
                ];

                DB::unprepared('SET IDENTITY_INSERT employee_children ON');
                DB::table('employee_children')->updateOrInsert(['children_id' => $child_id], $children_data);
                DB::unprepared('SET IDENTITY_INSERT employee_children OFF');
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

                DB::unprepared('SET IDENTITY_INSERT employee_educations ON');
                DB::table('employee_educations')->updateOrInsert(['education_id' => $educ_id], $educ_data);
                DB::unprepared('SET IDENTITY_INSERT employee_educations OFF');
            }
        }

        // Save Service Records
        $data_serv = $request->all();
        $arr_len_serv = count($data_serv["designation"]);
        $serv_data = [];

        for ($i = 0; $i < $arr_len_serv; $i++) {
            if ($data_serv["designation"][$i] != NULL) {

                // Parse date to validate
                $serv_start_date = Carbon::parse($data_serv["start_date"][$i]);
                $serv_end_date = Carbon::parse($data_serv["end_date"][$i]);

                if ($serv_start_date > $serv_end_date) {
                    return $this->errorResponse('Invalid date range in Service Record Tab.');
                }

                if ($data_serv["service_record_id"][$i] == null) {
                    $serv_id = DB::table('service_records')->max('service_record_id') + 1;
                } else {
                    $serv_id = $data_serv["service_record_id"][$i];
                }

                $serv_data = [
                    'employee_id' => $id,
                    'start_date' => $data_serv["start_date"][$i],
                    'end_date' => $data_serv["end_date"][$i],
                    'designation' => $data_serv["designation"][$i],
                    'position' => $data_serv["position"][$i],
                    'employment_type' => $data_serv["employment_type"][$i],
                    'annual_salary' => $data_serv["annual_salary"][$i],
                    'place_of_assignment' => $data_serv["place_of_assignment"][$i],
                    'leave_without_pay' => $data_serv["leave_without_pay"][$i],
                    'separation_date' => $data_serv["separation_date"][$i],
                    'cause' => $data_serv["cause"][$i],
                    'branch' => $data_serv["branch"][$i],
                    'plantilla' => $data_serv["plantilla"][$i],
                    'company' => $data_serv["company"][$i],
                    'department' => $data_serv["department"][$i],
                    'division' => $data_serv["division"][$i],
                    'section' => $data_serv["section"][$i],
                ];

                DB::unprepared('SET IDENTITY_INSERT service_records ON');
                DB::table('service_records')->updateOrInsert(['service_record_id' => $serv_id], $serv_data);
                DB::unprepared('SET IDENTITY_INSERT service_records OFF');
            }
        }

        // Save Employment Records
        $data_emp = $request->all();
        $arr_len_emp = count($data_emp["work_company"]);
        $emp_data = [];

        for ($i = 0; $i < $arr_len_emp; $i++) {
            if (isset($data_emp["work_company"][$i]) && $data_emp["work_company"][$i] != NULL) {

                // Parse date to validate
                if (isset($data_emp["work_end_date"][$i])) {
                    $we_start_date = Carbon::parse($data_emp["work_start_date"][$i]);
                    $we_end_date = Carbon::parse($data_emp["work_end_date"][$i]);

                    if ($we_start_date > $we_end_date) {
                        return $this->errorResponse('Invalid date range in Work Experience Tab.');
                    }
                }

                if ($data_emp["employment_record_id"][$i] == null || $data_emp["employment_record_id"][$i] == 0) {
                    $emp_id = DB::table('employee_employment_records')->max('employment_record_id') + 1;
                } else {
                    $emp_id = $data_emp["employment_record_id"][$i];
                }

                if (isset($data_emp['is_present'])) {
                    if ($data_emp['employment_record_id'][$i] == $data_emp['is_present'][0]) {
                        $is_present = true;
                    } else {
                        $is_present = false;
                    }
                } else {
                    $is_present = false;
                };

                if ($is_present) {
                    DB::table('employee_employment_records')->where('employee_id', $id)->update(['is_present' => false]);
                }

                $emp_data = [
                    'employee_id' => $id,
                    'work_start_date' => $data_emp["work_start_date"][$i],
                    'work_end_date' => isset($data_emp["work_end_date"][$i]) == false ? NULL : $data_emp["work_end_date"][$i],
                    'work_company' => $data_emp["work_company"][$i],
                    'monthly_salary' => $data_emp["monthly_salary"][$i],
                    'salary_grade_step' => $data_emp["salary_grade_step"][$i],
                    'status_of_appointment' => $data_emp["status_of_appointment"][$i],
                    'position' => $data_emp["position_we"][$i],
                    'government_service_id' => $data_emp["government_service_id"][$i],
                    'is_present' => $is_present,
                    'work_specialization_id' => $data_emp["work_specialization_id"][$i]
                ];

                DB::unprepared('SET IDENTITY_INSERT employee_employment_records ON');
                DB::table('employee_employment_records')->updateOrInsert(['employment_record_id' => $emp_id], $emp_data);
                DB::unprepared('SET IDENTITY_INSERT employee_employment_records OFF');
            }
        }

        // Save Eaminations
        $data_exam = $request->all();
        $arr_len_exam = count($data_exam["eligibility_id"]);
        $exam_data = [];

        for ($i = 0; $i < $arr_len_exam; $i++) {
            if ($data_exam["eligibility_id"][$i] != NULL && $data_exam["eligibility_id"][$i] != 0) {

                if ($data_exam["examination_id"][$i] == null) {
                    $exam_id = 0 + DB::table('employee_examinations')->max('examination_id');
                    $exam_id += 1;
                } else {
                    $exam_id = $data_exam["examination_id"][$i];
                }

                $eligibility_id = $data_exam["eligibility_id"][$i];
                $exam_rating = $data_exam["exam_rating"][$i];
                $exam_date = $data_exam["exam_date"][$i];
                $place_of_exam = $data_exam["place_of_exam"][$i];
                $license_number = $data_exam["license_number"][$i];
                $date_released = $data_exam["date_released"][$i];
                $eligibility_description = $data_exam["eligibility_description"][$i];

                $rules = [
                    'exam_rating.' . $i => 'numeric|min:0|max:999999.99',
                    'exam_date.' . $i => 'date|after_or_equal:1980-01-01|before_or_equal:2050-12-31',
                ];

                $messages = [
                    'exam_rating.' . $i . '.numeric' => 'The Exam Rating must be a number.',
                    'exam_rating.' . $i . '.min' => 'The Exam Rating must be at least 0.',
                    'exam_rating.' . $i . '.max' => 'The Exam Rating may not be greater than 999999.99.',
                    'exam_date.' . $i . '.date' => 'The Exam Date is not a valid date.',
                    'exam_date.' . $i . '.after_or_equal' => 'The Exam Date must be a date after or equal to 1980-01-01.',
                    'exam_date.' . $i . '.before_or_equal' => 'The Exam Date must be a date before or equal to 2050-12-31.',
                ];

                $validator = Validator::make($exam_data, $rules, $messages);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $exam_data = [
                    'employee_id' => $id,
                    'eligibility_id' => $eligibility_id,
                    'exam_rating' => $exam_rating,
                    'exam_date' => $exam_date,
                    'place_of_exam' => $place_of_exam,
                    'license_number' => $license_number,
                    'date_released' => $date_released,
                    'eligibility_description' => $eligibility_description,
                ];

                DB::unprepared('SET IDENTITY_INSERT employee_examinations ON');
                DB::table('employee_examinations')->updateOrInsert(['examination_id' => $exam_id], $exam_data);
                DB::unprepared('SET IDENTITY_INSERT employee_examinations OFF');
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

                DB::unprepared('SET IDENTITY_INSERT employee_trainings ON');
                DB::table('employee_trainings')->updateOrInsert(['training_id' => $training_id], $training_data);
                DB::unprepared('SET IDENTITY_INSERT employee_trainings OFF');
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
                    'organization_address' => $data_org["organization_address"][$i],
                ];

                DB::unprepared('SET IDENTITY_INSERT employee_organizations ON');
                DB::table('employee_organizations')->updateOrInsert(['organization_id' => $organization_id], $org_data);
                DB::unprepared('SET IDENTITY_INSERT employee_organizations OFF');
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

                DB::unprepared('SET IDENTITY_INSERT employee_recognations ON');
                DB::table('employee_recognations')->updateOrInsert(['recognation_id' => $recognation_id], $recog_data);
                DB::unprepared('SET IDENTITY_INSERT employee_recognations OFF');
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

                DB::unprepared('SET IDENTITY_INSERT employee_skills ON');
                DB::table('employee_skills')->updateOrInsert(['skill_id' => $skill_id], $skill_data);
                DB::unprepared('SET IDENTITY_INSERT employee_skills OFF');
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

                DB::unprepared('SET IDENTITY_INSERT employee_memberships ON');
                DB::table('employee_memberships')->updateOrInsert(['membership_id' => $membership_id], $mem_data);
                DB::unprepared('SET IDENTITY_INSERT employee_memberships OFF');
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

                DB::unprepared('SET IDENTITY_INSERT employee_references ON');
                DB::table('employee_references')->updateOrInsert(['reference_id' => $reference_id], $ref_data);
                DB::unprepared('SET IDENTITY_INSERT employee_references OFF');
            }
        }

        // Save Dependents
        $data_dep = $request->all();
        $arr_len_dep = isset($data_dep["dep_name"]) ? count($data_dep["dep_name"]) : 0;
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

        // Save Questionaires
        $data_question = $request->all();
        $arr_len_question = count($data_question["question_id"]);
        $question_data = [];

        DB::table('employee_pds_answers')->where('employee_id', $id)->delete();

        for ($i = 0; $i < $arr_len_question; $i++) {
            if ($data_question["question_id"][$i] != NULL) {

                if (isset($data_question['answer'][$data_question['question_id'][$i]])) {
                    if ($data_question['answer'][$data_question['question_id'][$i]] == 'yes' . $data_question["question_id"][$i]) {
                        $yes = true;
                    } else {
                        $yes = false;
                    }

                    if ($data_question['answer'][$data_question['question_id'][$i]] == 'no' . $data_question["question_id"][$i]) {
                        $no = true;
                    } else {
                        $no = false;
                    }
                } else {
                    $yes = false;
                    $no = false;
                }

                $question_data = [
                    'employee_id' => $id,
                    'question_id' => $data_question["question_id"][$i],
                    'is_yes' =>  $yes,
                    'is_no' => $no,
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
                'module'  => 'HR Module',
                'menu'    => 'Employee Records',
                'activity' => 'Add',
                'description' => 'Added employee informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'You have successfully added employee information!');
        } else {

            if ($is_employee_portal) {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => '201 File',
                    'activity' => 'Update',
                    'description' => 'Updated employee informations.',
                );

                Audit::create($data_audit);

                $users = User::where('is_notify', true)->get();
                $employee_name = $request->first_name . ' ' . $request->last_name;

                foreach ($users as $user) {
                    $employee_hr = $user->name;
                    // send email verification here
                    Notification::send($user, new Email201Update($employee_hr, $employee_name));
                }

                return $this->successResponse(null, 'You have successfully update employee information!');
            } else {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'Employee Records',
                    'activity' => 'Update',
                    'description' => 'Updated employee informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(null, 'You have successfully update employee information!');
            }
        }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to store employee information: ' . $e->getMessage());
        }
    }

    public function delete($type_id, $id)
    {
        try {
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

        return $this->successResponse($data, 'Employee data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee data for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($type_id, $id)
    {
        try {
            if ($type_id == 1) {
            DB::table('employee_children')->where('children_id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted Child table informations.',
            );
        } elseif ($type_id == 2) {
            // delete child data
            DB::table('employee_educations')->where('education_id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted Education table informations.',
            );
        } elseif ($type_id == 3) {
            // delete child data
            DB::table('service_records')->where('service_record_id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted Service Record table informations.',
            );
        } elseif ($type_id == 4) {
            // delete child data
            DB::table('employee_employment_records')->where('employment_record_id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted Employment Record table informations.',
            );
        } elseif ($type_id == 5) {
            // delete child data
            DB::table('employee_examinations')->where('examination_id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted Eligibility table informations.',
            );
        } elseif ($type_id == 6) {
            // delete child data
            DB::table('employee_trainings')->where('training_id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted Training table informations.',
            );
        } elseif ($type_id == 7) {
            // delete child data
            DB::table('employee_organizations')->where('organization_id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted Organization table informations.',
            );
        } elseif ($type_id == 8) {
            // delete child data
            DB::table('employee_recognations')->where('recognation_id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted Recognition table informations.',
            );
        } elseif ($type_id == 9) {
            // delete child data
            DB::table('employee_skills')->where('skill_id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted Skill table informations.',
            );
        } elseif ($type_id == 10) {
            // delete child data
            DB::table('employee_memberships')->where('membership_id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted Membership table informations.',
            );
        } elseif ($type_id == 11) {
            // delete child data
            DB::table('employee_references')->where('reference_id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted References table informations.',
            );
        } elseif ($type_id == 12) {
            // delete child data
            DB::table('employee_dependents')->where('id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted Dependents table informations.',
            );
        } elseif ($type_id == 13) {
            // delete child data
            $employee_document = DB::table('employee_documents')->where('employee_document_id', $id)->get();
            $file = $employee_document[0]->path . '\V_1.zip';

            if (Storage::exists($file)) {
                Storage::deleteDirectory($employee_document[0]->path);
            }

            DB::table('employee_documents')->where('employee_document_id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Employee Record',
                'activity' => 'Delete',
                'description' => 'Deleted Document table informations.',
            );
        }

        Audit::create($data_audit);

        return $this->successResponse(null, 'Successfully deleted!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete employee data: ' . $e->getMessage());
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
                ->first();

            if (!$documents) {
                return $this->notFoundResponse('Document not found');
            }

            $pathToFile = storage_path('app/employee_documents/' . 'DOCS' . $documents->employee_id . '_' . $documents->attachment_name);

            if (!file_exists($pathToFile)) {
                return $this->notFoundResponse('File not found on server');
            }

            $fileContent = file_get_contents($pathToFile);
            $base64Content = base64_encode($fileContent);

            return $this->successResponse([
                'file_content' => $base64Content,
                'filename' => $documents->attachment_name,
                'content_type' => mime_content_type($pathToFile)
            ], 'Document downloaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download document: ' . $e->getMessage());
        }
    }
}
