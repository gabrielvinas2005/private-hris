<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use App\Audit;
use App\Notifications\Email201Update;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\UserService;
use App\Services\CosPayrollHoldService;


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

    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            // Fetch all employees (both active and inactive) regardless of branch
            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.employee_no',
                    'employees.department_id',
                    'employees.birthdate',
                    'employees.active',
                    'employees.is_hold',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN employees.email ELSE dbo.ufn_DecryptString(employees.email,'$app_key') END as email"),
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN employees.first_name ELSE dbo.ufn_DecryptString(employees.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN employees.middle_name ELSE dbo.ufn_DecryptString(employees.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN employees.last_name ELSE dbo.ufn_DecryptString(employees.last_name,'$app_key') END as last_name"),
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
                ->orderBy('employees.first_name', 'asc')
                ->where([
                    'employees.is_employee' => true
                ])
                ->get();

            return $this->successResponse($data, 'Employees retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employees: ' . $e->getMessage());
        }
    }

    // Fetch all applicants
    public function indexApplicants()
    {
        try {
            $app_key = env("APP_KEY", "");

            // Fetch all applicants (is_employee = false) with application_status_id = 5
            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->leftJoin('applicant_headers', 'applicant_headers.applicant_no', '=', 'employees.employee_no')
                ->leftJoin('applicant_details', 'applicant_details.applicant_id', '=', 'applicant_headers.id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.employee_no',
                    'employees.department_id',
                    'employees.birthdate',
                    'employees.active',
                    'employees.is_hold',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN employees.email ELSE dbo.ufn_DecryptString(employees.email,'$app_key') END as email"),
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN employees.first_name ELSE dbo.ufn_DecryptString(employees.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN employees.middle_name ELSE dbo.ufn_DecryptString(employees.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN employees.last_name ELSE dbo.ufn_DecryptString(employees.last_name,'$app_key') END as last_name"),
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
                ->orderBy('first_name', 'asc')
                ->where([
                    'employees.is_employee' => false
                ])
                ->where('applicant_details.application_status_id', 5)
                ->distinct()
                ->get();

            return $this->successResponse($data, 'Applicants retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve applicants: ' . $e->getMessage());
        }
    }

    //search employee
    public function search(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");
            $query = $request->get('q', '');

            if (empty($query)) {
                return $this->successResponse([], 'No search query provided');
            }

            // Always search across all branches
            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->leftJoin('salary_grades', 'salary_grades.id', '=', 'employees.salary_grade_id')
                ->leftJoin('salary_steps', 'salary_steps.id', '=', 'employees.salary_step_id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.employee_no',
                    'employees.department_id',
                    'employees.email',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    'employees.salary',
                    'salary_grades.id as salary_grade_id',
                    'salary_steps.id as salary_step_id',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                            END as name")
                )
                ->where(function ($q) use ($query, $app_key) {
                    $q->where('employees.employee_no', 'like', "%{$query}%")
                        ->orWhereRaw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                      CONCAT(employees.first_name,' ',employees.last_name)
                                  ELSE
                                      RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                  END LIKE ?", ["%{$query}%"])
                        ->orWhere('departments.name', 'like', "%{$query}%")
                        ->orWhere('positions.name', 'like', "%{$query}%");
                })
                ->where([
                    'employees.is_employee' => true,
                    'employees.active' => true
                ])
                ->orderBy('employees.first_name', 'asc')
                ->limit(50)
                ->get();

            return $this->successResponse($data, 'Employees search completed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to search employees: ' . $e->getMessage());
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

            // Get Plantilla with joined names
            // NOTE: Temporarily swapped - salary_step joins on salary_grade_id column and vice versa
            $plantilla_emp = DB::table('plantillas')
                ->leftJoin('positions', 'positions.id', '=', 'plantillas.position_id')
                ->leftJoin('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_grade_id')
                ->leftJoin('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_step_id')
                ->select(
                    'plantillas.*',
                    'positions.name as position_name',
                    'salary_steps.name as salary_step_name',
                    'salary_grades.name as salary_grade_name'
                )
                ->where(['plantillas.employee_id' => $id, 'plantillas.active' => true]);

            $plantillas = DB::table('plantillas')
                ->leftJoin('positions', 'positions.id', '=', 'plantillas.position_id')
                ->leftJoin('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_grade_id')
                ->leftJoin('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_step_id')
                ->select(
                    'plantillas.*',
                    'positions.name as position_name',
                    'salary_steps.name as salary_step_name',
                    'salary_grades.name as salary_grade_name'
                )
                ->where(['plantillas.employee_id' => 0, 'plantillas.active' => true])->union($plantilla_emp)->get();

            $salary_grades = DB::table('salary_grades')->where('active', true)->orderBy('id', 'asc')->get();
            $salary_steps = DB::table('salary_steps')->where('active', true)->orderBy('id', 'asc')->get();
            $salary_schedule_amounts = [];
            $salary_schedule_rows = DB::table('salary_schedules_details as scd')
                ->join('salary_schedules as sch', 'sch.id', '=', 'scd.salary_schedule_id')
                ->where('sch.active', true)
                ->select('scd.salary_grade_id', 'scd.salary_step_id', 'scd.amount', 'sch.effectivity')
                ->orderBy('sch.effectivity', 'desc')
                ->get();

            foreach ($salary_schedule_rows as $scheduleRow) {
                $scheduleKey = $scheduleRow->salary_grade_id . '-' . $scheduleRow->salary_step_id;
                if (!array_key_exists($scheduleKey, $salary_schedule_amounts)) {
                    $salary_schedule_amounts[$scheduleKey] = (float) $scheduleRow->amount;
                }
            }

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

            // Load COS contracts (only for COS employees - employment_type_id = 2)
            $cos_contracts = collect();
            if ($id > 0) {
                $employee = DB::table('employees')->where('id', $id)->first();
                if ($employee && $employee->employment_type_id == 2) {
                    $cos_contracts = DB::table('cos_contract')->where('employee_id', $id)->orderBy('Start_date', 'desc')->get();

                    if ($cos_contracts->isNotEmpty()) {
                        $contractFiles = DB::connection('attachments')
                            ->table('cos_contractfile')
                            ->whereIn('cos_contract_id', $cos_contracts->pluck('id'))
                            ->get()
                            ->keyBy('cos_contract_id');

                        $cos_contracts = $cos_contracts->map(function ($contract) use ($contractFiles) {
                            $file = $contractFiles->get($contract->id);
                            $contract->contract_file = $file ? [
                                'id' => $file->id,
                                'file_name' => $file->file_name,
                                'file_size' => $file->file_size,
                                'file_type' => $file->file_type,
                            ] : null;

                            return $contract;
                        });
                    }
                }
            }

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

            // Get address lookup data with error handling
            $regions = [];
            $provinces = [];
            $cities = [];
            $barangays = [];

            try {
                // Load address data from frontend assets folder (complete data)
                $frontendAssetsPath = base_path('../201-frontend/src/assets');

                // Load regions
                $regionsPath = $frontendAssetsPath . '/refregion.json';
                if (file_exists($regionsPath)) {
                    $regionsData = json_decode(file_get_contents($regionsPath), true);
                    $regions = $regionsData['RECORDS'] ?? [];
                    \Log::info('Loaded regions from frontend assets', ['count' => count($regions)]);
                } else {
                    \Log::error('refregion.json not found at: ' . $regionsPath);
                }

                // Load provinces
                $provincesPath = $frontendAssetsPath . '/refprovince.json';
                if (file_exists($provincesPath)) {
                    $provincesData = json_decode(file_get_contents($provincesPath), true);
                    $provinces = $provincesData['RECORDS'] ?? [];
                    \Log::info('Loaded provinces from frontend assets', ['count' => count($provinces)]);
                } else {
                    \Log::error('refprovince.json not found at: ' . $provincesPath);
                }

                // Load cities from frontend assets (complete data including RIZAL)
                $citiesPath = $frontendAssetsPath . '/refcitymun.json';
                if (file_exists($citiesPath)) {
                    $citiesData = json_decode(file_get_contents($citiesPath), true);
                    $cities = $citiesData['RECORDS'] ?? [];

                    // Log cities data for debugging
                    $uniqueProvCodes = array_values(array_unique(array_column($cities, 'provCode')));
                    $hasRizal = in_array('0458', $uniqueProvCodes);
                    \Log::info('Loaded cities from frontend assets', [
                        'total_cities' => count($cities),
                        'unique_provinces' => count($uniqueProvCodes),
                        'has_rizal' => $hasRizal,
                        'sample_province_codes' => array_slice($uniqueProvCodes, 0, 10)
                    ]);
                } else {
                    \Log::error('refcitymun.json not found at: ' . $citiesPath);
                }

                // Load barangays from frontend assets
                $barangaysPath = $frontendAssetsPath . '/refbrgy.json';
                if (file_exists($barangaysPath)) {
                    $barangaysData = json_decode(file_get_contents($barangaysPath), true);
                    $barangays = $barangaysData['RECORDS'] ?? [];
                    \Log::info('Loaded barangays from frontend assets', ['count' => count($barangays)]);
                } else {
                    \Log::error('refbrgy.json not found at: ' . $barangaysPath);
                }
            } catch (\Exception $e) {
                \Log::error('Error loading address reference data: ' . $e->getMessage());
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
                    'salary_schedule_amounts' => $salary_schedule_amounts,
                    'payroll_intervals' => $payroll_intervals,
                    'eligibilities' => $eligibilities,
                    'learnings' => $learnings,
                    'plantillas_selected' => $plantillas_selected,
                    'salary_grade_steps' => $salary_grade_steps,
                    'quesionaires' => $quesionaires,
                    'divisions' => $divisions,
                    'sections' => $sections,
                    'document_types' => $document_types,
                    'regions' => $regions,
                    'provinces' => $provinces,
                    'cities' => $cities,
                    'barangays' => $barangays,
                ],
                'employee_info' => $employee_info,
                'related_data' => [
                    'children' => $children,
                    'educations' => $educations,
                    'service_records' => $service_records,
                    'cos_contracts' => $cos_contracts,
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
                // Validations - Only essential fields required for initial creation
                if ($request->has('email') && !empty($request->email)) {
                    $emp_email = DB::table('employees')->whereRaw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN email ELSE dbo.ufn_DecryptString(email,'$app_key') END = '$request->email'")->get();

                    if ($emp_email->isNotEmpty()) {
                        return $this->errorResponse('Email already been taken.', 400);
                    }
                }

                $request->validate([
                    'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp|mimetypes:image/jpeg,image/png,image/jpg,image/webp|max:4096',
                    'employee_no' => 'required|unique:employees',
                    'email' => 'nullable|unique:employees',
                    'name_prefix_id' => 'nullable',
                    'first_name' => 'required',
                    'last_name' => 'required',
                    'birthdate' => 'nullable|date',
                    'age' => 'nullable|numeric|min:0|max:100',
                    'gender_id' => 'nullable',
                    'civil_status_id' => 'nullable',
                    'citizenship_id' => 'nullable',
                    'religion_id' => 'nullable',
                    'department_id' => 'nullable',
                    'employment_type_id' => 'nullable',
                    'position_id' => 'nullable',
                    'payroll_interval_id' => 'nullable',
                    'salary' => 'nullable|numeric|min:0',

                    'ra_postal_id' => 'nullable',
                    'pa_postal_id' => 'nullable',
                    'ra_region' => 'nullable',
                    'pa_region' => 'nullable',
                ], [
                    'photo.file' => 'The photo must be a valid file.',
                    'photo.mimes' => 'The photo must be a file of type: jpeg, png, jpg, webp.',
                    'photo.max' => 'The photo may not be greater than 4MB.',
                    'employee_no.required' => 'Employee number is required.',
                    'employee_no.unique' => 'This employee number is already taken.',
                    'email.unique' => 'This email address is already taken.',
                    'first_name.required' => 'First name is required.',
                    'last_name.required' => 'Last name is required.',
                    'birthdate.date' => 'The birthdate field must be a valid date (format: YYYY-MM-DD).',
                    'age.numeric' => 'The age must be a number.',
                    'age.min' => 'The age must be at least 0.',
                    'age.max' => 'The age must not be greater than 100.',
                    'salary.numeric' => 'The salary must be a number.',
                    'salary.min' => 'The salary must be at least 0.',
                ]);
            } else {
                // Validations for updates - Only essential fields required
                $request->validate([
                    'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp|mimetypes:image/jpeg,image/png,image/jpg,image/webp|max:4096',
                    'employee_no' => 'required|unique:employees,employee_no' . ($id ? ",$id" : ''),
                    'email' => 'nullable|unique:employees,email' . ($id ? ",$id" : ''),
                    'name_prefix_id' => 'nullable',
                    'first_name' => 'required',
                    'last_name' => 'required',
                    'birthdate' => 'nullable|date',
                    'age' => 'nullable|numeric|min:0|max:100',
                    'gender_id' => 'nullable',
                    'civil_status_id' => 'nullable',
                    'citizenship_id' => 'nullable',
                    'religion_id' => 'nullable',
                    'department_id' => 'nullable',
                    'employment_type_id' => 'nullable',
                    'position_id' => 'nullable',
                    'payroll_interval_id' => 'nullable',
                    'salary' => 'nullable|numeric|min:0',

                    'ra_postal_id' => 'nullable',
                    'pa_postal_id' => 'nullable',
                    'ra_region' => 'nullable',
                    'pa_region' => 'nullable',
                ], [
                    'photo.file' => 'The photo must be a valid file.',
                    'photo.mimes' => 'The photo must be a file of type: jpeg, png, jpg, webp.',
                    'photo.max' => 'The photo may not be greater than 4MB.',
                    'employee_no.required' => 'Employee number is required.',
                    'employee_no.unique' => 'This employee number is already taken.',
                    'email.unique' => 'This email address is already taken.',
                    'first_name.required' => 'First name is required.',
                    'last_name.required' => 'Last name is required.',
                    'birthdate.date' => 'The birthdate field must be a valid date (format: YYYY-MM-DD).',
                    'age.numeric' => 'The age must be a number.',
                    'age.min' => 'The age must be at least 0.',
                    'age.max' => 'The age must not be greater than 100.',
                    'salary.numeric' => 'The salary must be a number.',
                    'salary.min' => 'The salary must be at least 0.',
                ]);
            }

            // Allow 0 values - these can be filled in later
            // Removed the checks that returned errors for 0 values

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

            // Handle plantilla_id - ensure it's properly converted to integer
            // Check if plantilla_id is provided and valid
            if ($request->has('plantilla_id') && $request->plantilla_id !== null && $request->plantilla_id !== '') {
                // Convert to integer, defaulting to 0 if not numeric
                $plantilla_id = is_numeric($request->plantilla_id) ? (int)$request->plantilla_id : 0;
            } else {
                $plantilla_id = 0;
            }

            // If is_plantilla is false, ensure plantilla_id is 0
            if (!$request->has('is_plantilla') || !$request->is_plantilla) {
                $plantilla_id = 0;
            }

            // Handle division_id - ensure it's properly converted to integer
            $division_id = 0;
            if ($request->has('division_id') && $request->division_id !== null && $request->division_id !== '') {
                $division_id = is_numeric($request->division_id) ? (int)$request->division_id : 0;
            }

            if ($request->filled('age') && $request->age > 100) {
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

            // Map frontend address fields (ca_*) to backend fields (ra_*)
            // Also map zip codes to postal_id fields
            // Store zipcode value directly in postal_id (treating it as the zipcode value, not an ID reference)
            $ra_postal_id = 0;
            if ($request->has('ra_postal_id') && $request->ra_postal_id) {
                // If ra_postal_id is provided directly, use it
                $ra_postal_id = is_numeric($request->ra_postal_id) ? (int)$request->ra_postal_id : 0;
            } elseif ($request->has('ca_zip') && $request->ca_zip) {
                // Store zipcode value directly in postal_id (as integer if numeric, otherwise 0)
                $zipValue = trim($request->ca_zip);
                $ra_postal_id = is_numeric($zipValue) ? (int)$zipValue : 0;
            }

            $pa_postal_id = 0;
            if ($request->has('pa_postal_id') && $request->pa_postal_id) {
                // If pa_postal_id is provided directly, use it
                $pa_postal_id = is_numeric($request->pa_postal_id) ? (int)$request->pa_postal_id : 0;
            } elseif ($request->has('pa_zip') && $request->pa_zip) {
                // Store zipcode value directly in postal_id (as integer if numeric, otherwise 0)
                $zipValue = trim($request->pa_zip);
                $pa_postal_id = is_numeric($zipValue) ? (int)$zipValue : 0;
            }

            $ra_region = $request->has('ra_region') ? $request->ra_region : ($request->has('ca_region') ? $request->ca_region : '');
            $ra_province = $request->has('ra_province') ? $request->ra_province : ($request->has('ca_province') ? $request->ca_province : '');
            $ra_city = $request->has('ra_city') ? $request->ra_city : ($request->has('ca_city') ? $request->ca_city : '');
            $ra_barangay = $request->has('ra_barangay') ? $request->ra_barangay : ($request->has('ca_barangay') ? $request->ca_barangay : '');
            $ra_house_no = $request->has('ra_house_no') ? $request->ra_house_no : ($request->has('ca_house_no') ? $request->ca_house_no : '');
            $ra_street = $request->has('ra_street') ? $request->ra_street : ($request->has('ca_street') ? $request->ca_street : '');
            $ra_village = $request->has('ra_village') ? $request->ra_village : ($request->has('ca_village') ? $request->ca_village : '');

            $pa_region = $request->has('pa_region') ? $request->pa_region : '';
            $pa_province = $request->has('pa_province') ? $request->pa_province : '';
            $pa_city = $request->has('pa_city') ? $request->pa_city : '';
            $pa_barangay = $request->has('pa_barangay') ? $request->pa_barangay : '';
            $pa_house_no = $request->has('pa_house_no') ? $request->pa_house_no : '';
            $pa_street = $request->has('pa_street') ? $request->pa_street : '';
            $pa_village = $request->has('pa_village') ? $request->pa_village : '';

            // Calculate tax amount automatically if not provided or is 0
            $salary = isset($request->salary) ? (float) $request->salary : 0;
            $gsis_amount = isset($request->gsis_amount) ? (float) $request->gsis_amount : 0;
            $philhealth_amount = isset($request->philhealth_amount) ? (float) $request->philhealth_amount : 0;
            $pagibig_amount = isset($request->pagibig_amount) ? (float) $request->pagibig_amount : 0;
            $tax_amount = isset($request->tax_amount) ? (float) $request->tax_amount : 0;

            // Auto-calculate tax if tax_amount is 0 and salary > 0
            if ($tax_amount == 0 && $salary > 0) {
                try {
                    // Formula: tax = percentage * (basic_salary - min_amount) + base_tax
                    // Use basic salary directly, not taxable amount after deductions
                    if ($salary > 0) {
                        $tax_data = DB::table('tax_tables')->orderBy('min_amount')->get();
                        foreach ($tax_data as $tax) {
                            if ($salary >= $tax->min_amount && $salary <= $tax->max_amount) {
                                $tax_amount = ((($salary - $tax->min_amount) * $tax->percentage) + $tax->base_tax);
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // If tax calculation fails, keep tax_amount as 0
                    // Log error but don't fail the entire save operation
                    \Log::warning('Failed to calculate tax for employee: ' . $e->getMessage());
                }
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
                    // address info - mapped from ca_* to ra_* and pa_*
                    'ra_postal_id' => $ra_postal_id,
                    'ra_region' => $ra_region,
                    'ra_province' => $ra_province,
                    'ra_city' => $ra_city,
                    'ra_barangay' => $ra_barangay,
                    'ra_house_no' => $ra_house_no,
                    'ra_street' => $ra_street,
                    'ra_village' => $ra_village,
                    'pa_postal_id' => $pa_postal_id,
                    'pa_region' => $pa_region,
                    'pa_province' => $pa_province,
                    'pa_city' => $pa_city,
                    'pa_barangay' => $pa_barangay,
                    'pa_house_no' => $pa_house_no,
                    'pa_street' => $pa_street,
                    'pa_village' => $pa_village,
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
                    'division_id' => $division_id,
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
                    'salary' => $salary,
                    'tax_amount' => $tax_amount,
                    'gsis_amount' => $gsis_amount,
                    'sss_amount' => isset($request->sss_amount) ? $request->sss_amount : 0,
                    'pagibig_amount' => $pagibig_amount,
                    'philhealth_amount' => $philhealth_amount,
                    // default data
                    'active' => true,
                    'is_employee' => true,
                    'account_no' => $request->account_no,
                    'is_hold' => $request->has('is_hold') && ($request->is_hold === true || $request->is_hold === 1 || $request->is_hold === '1' || $request->is_hold === 'true') ? true : false,
                    'hold_remarks' => $request->hold_remarks ?? '',
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
                    // address info - mapped from ca_* to ra_* and pa_*
                    'ra_postal_id' => $ra_postal_id,
                    'ra_region' => $ra_region,
                    'ra_province' => $ra_province,
                    'ra_city' => $ra_city,
                    'ra_barangay' => $ra_barangay,
                    'ra_house_no' => $ra_house_no,
                    'ra_street' => $ra_street,
                    'ra_village' => $ra_village,
                    'pa_postal_id' => $pa_postal_id,
                    'pa_region' => $pa_region,
                    'pa_province' => $pa_province,
                    'pa_city' => $pa_city,
                    'pa_barangay' => $pa_barangay,
                    'pa_house_no' => $pa_house_no,
                    'pa_street' => $pa_street,
                    'pa_village' => $pa_village,
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
                    'division_id' => $division_id,
                    'section_id' => $request->section_id,
                    'employment_type_id' => $request->employment_type_id,
                    'date_hired' => $request->date_hired,
                    'is_plantilla' => $request->has('is_plantilla') && ($request->is_plantilla === true || $request->is_plantilla === 1 || $request->is_plantilla === '1' || $request->is_plantilla === 'true') ? true : false,
                    'is_teaching' => $request->has('is_teaching') && ($request->is_teaching === true || $request->is_teaching === 1 || $request->is_teaching === '1' || $request->is_teaching === 'true') ? true : false,
                    'is_hold' => $request->has('is_hold') && ($request->is_hold === true || $request->is_hold === 1 || $request->is_hold === '1' || $request->is_hold === 'true') ? true : false,
                    'hold_remarks' => $request->hold_remarks ?? '',
                    'plantilla_id' => $plantilla_id,
                    'salary_grade_id' => $request->salary_grade_id,
                    'salary_step_id' => $request->salary_step_id,
                    'position_id' => $request->position_id,
                    'end_date' => $request->end_dates,
                    // payroll related info
                    'payroll_interval_id' => isset($request->payroll_interval_id) ? $request->payroll_interval_id : 0,
                    'salary' => $salary,
                    'tax_amount' => $tax_amount,
                    'gsis_amount' => $gsis_amount,
                    'sss_amount' => isset($request->sss_amount) ? $request->sss_amount : 0,
                    'pagibig_amount' => $pagibig_amount,
                    'philhealth_amount' => $philhealth_amount,
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

            if ($id > 0 && $request->filled('email')) {
                $userEmailError = $this->validateLinkedUserEmailChange(
                    (int) $id,
                    $request->employee_no,
                    $request->email
                );
                if ($userEmailError) {
                    return $this->errorResponse($userEmailError, 400);
                }
            }

            DB::unprepared('SET IDENTITY_INSERT employees ON');
            DB::table('employees')->updateOrInsert(['id' => $id], $employee_info);
            DB::unprepared('SET IDENTITY_INSERT employees OFF');

            $this->syncLinkedUserEmail((int) $id, $request->employee_no, $request->email);

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

            // Check if children data exists and is an array
            if (isset($data_child["child_name"]) && is_array($data_child["child_name"])) {
                $arr_len_children = count($data_child["child_name"]);
                $children_data = [];

                for ($i = 0; $i < $arr_len_children; $i++) {
                    // Only process if child_name is not empty
                    if (isset($data_child["child_name"][$i]) && $data_child["child_name"][$i] != NULL && trim($data_child["child_name"][$i]) != '') {

                        if (!isset($data_child["children_id"][$i]) || $data_child["children_id"][$i] == null) {
                            $max_id = DB::table('employee_children')->max('children_id');
                            $child_id = ($max_id ? $max_id : 0) + 1;
                        } else {
                            $child_id = $data_child["children_id"][$i];
                        }

                        $children_data = [
                            'employee_id' => $id,
                            'child_name' => $data_child["child_name"][$i] ?? '',
                            'child_middlename' => $data_child["child_middlename"][$i] ?? '',
                            'child_lastname' => $data_child["child_lastname"][$i] ?? '',
                            'child_birthdate' => $data_child["child_birthdate"][$i] ?? null,
                            'child_gender_id' => $data_child["child_gender_id"][$i] ?? 0
                        ];

                        DB::unprepared('SET IDENTITY_INSERT employee_children ON');
                        DB::table('employee_children')->updateOrInsert(['children_id' => $child_id], $children_data);
                        DB::unprepared('SET IDENTITY_INSERT employee_children OFF');
                    }
                }
            }

            // Save Education
            $data_educ = $request->all();

            // Check if education data exists
            if (isset($data_educ["school_name"]) && is_array($data_educ["school_name"])) {
                $arr_len_educ = count($data_educ["school_name"]);
                $educ_data = [];

                for ($i = 0; $i < $arr_len_educ; $i++) {
                    if (isset($data_educ["school_name"][$i]) && $data_educ["school_name"][$i] != NULL) {

                        if (!isset($data_educ["education_id"][$i]) || $data_educ["education_id"][$i] == null) {
                            $max_id = DB::table('employee_educations')->max('education_id');
                            $educ_id = ($max_id ? $max_id : 0) + 1;
                        } else {
                            $educ_id = $data_educ["education_id"][$i];
                        }

                        $educ_data = [
                            'employee_id' => $id,
                            'school_name' => $data_educ["school_name"][$i] ?? '',
                            'academic_level_id' => $data_educ["academic_level_id"][$i] ?? 0,
                            'program' => $data_educ["program"][$i] ?? '',
                            'from' => $data_educ["from"][$i] ?? null,
                            'to' => $data_educ["to"][$i] ?? null,
                            'graduated_year' => $data_educ["graduated_year"][$i] ?? null,
                            'units_earned' => $data_educ["units_earned"][$i] ?? '',
                            'honors' => $data_educ["honors"][$i] ?? ''
                        ];

                        DB::unprepared('SET IDENTITY_INSERT employee_educations ON');
                        DB::table('employee_educations')->updateOrInsert(['education_id' => $educ_id], $educ_data);
                        DB::unprepared('SET IDENTITY_INSERT employee_educations OFF');
                    }
                }
            }

            // Save Service Records
            $data_serv = $request->all();

            // Check if service record data exists
            if (isset($data_serv["designation"]) && is_array($data_serv["designation"])) {
                $arr_len_serv = count($data_serv["designation"]);
                $serv_data = [];

                for ($i = 0; $i < $arr_len_serv; $i++) {
                    if (isset($data_serv["designation"][$i]) && $data_serv["designation"][$i] != NULL) {

                        // Parse date to validate only if both dates are provided and not empty
                        if (!empty($data_serv["start_date"][$i]) && !empty($data_serv["end_date"][$i])) {
                            try {
                                $serv_start_date = Carbon::parse($data_serv["start_date"][$i]);
                                $serv_end_date = Carbon::parse($data_serv["end_date"][$i]);

                                // Allow same dates (start_date == end_date) and only reject if start is after end
                                if ($serv_start_date->gt($serv_end_date)) {
                                    return $this->errorResponse('Invalid date range in Service Record Tab. Start date cannot be later than end date.');
                                }
                            } catch (\Exception $e) {
                                // If date parsing fails, skip validation (will be handled by database)
                            }
                        }
                        // If end_date is empty, it's allowed (for ongoing/present positions)

                        if (!isset($data_serv["service_record_id"][$i]) || $data_serv["service_record_id"][$i] == null) {
                            $max_id = DB::table('service_records')->max('service_record_id');
                            $serv_id = ($max_id ? $max_id : 0) + 1;
                        } else {
                            $serv_id = $data_serv["service_record_id"][$i];
                        }

                        $serv_data = [
                            'employee_id' => $id,
                            'start_date' => $data_serv["start_date"][$i] ?? null,
                            'end_date' => $data_serv["end_date"][$i] ?? null,
                            'designation' => $data_serv["designation"][$i] ?? '',
                            'position' => $data_serv["position"][$i] ?? '',
                            'employment_type' => $data_serv["employment_type"][$i] ?? '',
                            'annual_salary' => $data_serv["annual_salary"][$i] ?? 0,
                            'place_of_assignment' => $data_serv["place_of_assignment"][$i] ?? '',
                            'leave_without_pay' => $data_serv["leave_without_pay"][$i] ?? '',
                            'separation_date' => $data_serv["separation_date"][$i] ?? null,
                            'cause' => $data_serv["cause"][$i] ?? '',
                            'branch' => $data_serv["branch"][$i] ?? '',
                            'plantilla' => $data_serv["plantilla"][$i] ?? '',
                            'company' => $data_serv["company"][$i] ?? '',
                            'department' => $data_serv["department"][$i] ?? '',
                            'division' => $data_serv["division"][$i] ?? '',
                            'section' => $data_serv["section"][$i] ?? '',
                        ];

                        DB::unprepared('SET IDENTITY_INSERT service_records ON');
                        DB::table('service_records')->updateOrInsert(['service_record_id' => $serv_id], $serv_data);
                        DB::unprepared('SET IDENTITY_INSERT service_records OFF');
                    }
                }
            }

            // Save Employment Records
            $data_emp = $request->all();

            // Check if employment record data exists
            if (isset($data_emp["work_company"]) && is_array($data_emp["work_company"])) {
                $arr_len_emp = count($data_emp["work_company"]);
                $emp_data = [];

                for ($i = 0; $i < $arr_len_emp; $i++) {
                    if (isset($data_emp["work_company"][$i]) && $data_emp["work_company"][$i] != NULL) {

                        // Parse date to validate only if both dates are provided
                        if (
                            isset($data_emp["work_end_date"][$i]) && isset($data_emp["work_start_date"][$i]) &&
                            $data_emp["work_end_date"][$i] && $data_emp["work_start_date"][$i]
                        ) {
                            $we_start_date = Carbon::parse($data_emp["work_start_date"][$i]);
                            $we_end_date = Carbon::parse($data_emp["work_end_date"][$i]);

                            if ($we_start_date > $we_end_date) {
                                return $this->errorResponse('Invalid date range in Work Experience Tab.');
                            }
                        }

                        if (!isset($data_emp["employment_record_id"][$i]) || $data_emp["employment_record_id"][$i] == null || $data_emp["employment_record_id"][$i] == 0) {
                            $max_id = DB::table('employee_employment_records')->max('employment_record_id');
                            $emp_id = ($max_id ? $max_id : 0) + 1;
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
                            'work_start_date' => $data_emp["work_start_date"][$i] ?? null,
                            'work_end_date' => isset($data_emp["work_end_date"][$i]) == false ? NULL : $data_emp["work_end_date"][$i],
                            'work_company' => $data_emp["work_company"][$i] ?? '',
                            'monthly_salary' => $data_emp["monthly_salary"][$i] ?? 0,
                            'salary_grade_step' => $data_emp["salary_grade_step"][$i] ?? '',
                            'status_of_appointment' => $data_emp["status_of_appointment"][$i] ?? '',
                            'position' => $data_emp["position_we"][$i] ?? '',
                            'government_service_id' => $data_emp["government_service_id"][$i] ?? 0,
                            'is_present' => $is_present,
                            'work_specialization_id' => $data_emp["work_specialization_id"][$i] ?? 0
                        ];

                        DB::unprepared('SET IDENTITY_INSERT employee_employment_records ON');
                        DB::table('employee_employment_records')->updateOrInsert(['employment_record_id' => $emp_id], $emp_data);
                        DB::unprepared('SET IDENTITY_INSERT employee_employment_records OFF');
                    }
                }
            }

            // Save Examinations
            $data_exam = $request->all();

            // Check if examination data exists
            if (isset($data_exam["eligibility_id"]) && is_array($data_exam["eligibility_id"])) {
                $arr_len_exam = count($data_exam["eligibility_id"]);
                $exam_data = [];

                for ($i = 0; $i < $arr_len_exam; $i++) {
                    if (isset($data_exam["eligibility_id"][$i]) && $data_exam["eligibility_id"][$i] != NULL && $data_exam["eligibility_id"][$i] != 0) {

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
            }

            // Save Training
            $data_training = $request->all();

            // Check if training data exists
            if (isset($data_training["training"]) && is_array($data_training["training"])) {
                $arr_len_training = count($data_training["training"]);
                $training_data = [];

                for ($i = 0; $i < $arr_len_training; $i++) {
                    if (isset($data_training["training"][$i]) && $data_training["training"][$i] != NULL) {

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
            }

            // Save Organizations
            $data_org = $request->all();

            // Check if organization data exists
            if (isset($data_org["organization"]) && is_array($data_org["organization"])) {
                $arr_len_org = count($data_org["organization"]);
                $org_data = [];

                for ($i = 0; $i < $arr_len_org; $i++) {
                    if (isset($data_org["organization"][$i]) && $data_org["organization"][$i] != NULL) {

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
            }

            // Save Recognitions
            $data_recog = $request->all();

            // Check if recognition data exists
            if (isset($data_recog["recognation"]) && is_array($data_recog["recognation"])) {
                $arr_len_recog = count($data_recog["recognation"]);
                $recog_data = [];

                for ($i = 0; $i < $arr_len_recog; $i++) {
                    if (isset($data_recog["recognation"][$i]) && $data_recog["recognation"][$i] != NULL) {

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
            }

            // Save Skills
            $data_skill = $request->all();

            // Check if skills data exists
            if (isset($data_skill["skill"]) && is_array($data_skill["skill"])) {
                $arr_len_skill = count($data_skill["skill"]);
                $skill_data = [];

                for ($i = 0; $i < $arr_len_skill; $i++) {
                    if (isset($data_skill["skill"][$i]) && $data_skill["skill"][$i] != NULL) {

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
            }

            // Save Memberships
            $data_mem = $request->all();

            // Check if membership data exists
            if (isset($data_mem["membership"]) && is_array($data_mem["membership"])) {
                $arr_len_mem = count($data_mem["membership"]);
                $mem_data = [];

                for ($i = 0; $i < $arr_len_mem; $i++) {
                    if (isset($data_mem["membership"][$i]) && $data_mem["membership"][$i] != NULL) {

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
            }

            // Save References
            $data_ref = $request->all();

            // Check if references data exists
            if (isset($data_ref["ref_name"]) && is_array($data_ref["ref_name"])) {
                $arr_len_ref = count($data_ref["ref_name"]);
                $ref_data = [];

                for ($i = 0; $i < $arr_len_ref; $i++) {
                    if (isset($data_ref["ref_name"][$i]) && $data_ref["ref_name"][$i] != NULL) {

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
            }

            // Save Dependents
            $data_dep = $request->all();

            // Check if dependents data exists
            if (isset($data_dep["dep_name"]) && is_array($data_dep["dep_name"])) {
                $arr_len_dep = count($data_dep["dep_name"]);
                $dep_data = [];

                for ($i = 0; $i < $arr_len_dep; $i++) {
                    if (isset($data_dep["dep_name"][$i]) && $data_dep["dep_name"][$i] != NULL) {

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
            }

            // Save COS Contracts (only for COS employees - employment_type_id = 2)
            $data_contract = $request->all();
            if (isset($data_contract["contract_start_date"]) && is_array($data_contract["contract_start_date"])) {
                $arr_len_contract = count($data_contract["contract_start_date"]);
                $savedContractIds = [];

                for ($i = 0; $i < $arr_len_contract; $i++) {
                    if (isset($data_contract["contract_start_date"][$i]) && !empty($data_contract["contract_start_date"][$i])) {
                        $contract_id = $data_contract["contract_id"][$i] ?? null;

                        // Validate date range
                        if (!empty($data_contract["contract_start_date"][$i]) && !empty($data_contract["contract_end_date"][$i])) {
                            $start_date = \Carbon\Carbon::parse($data_contract["contract_start_date"][$i]);
                            $end_date = \Carbon\Carbon::parse($data_contract["contract_end_date"][$i]);

                            if ($start_date > $end_date) {
                                return $this->errorResponse('Invalid date range in Contract Tab. Start date cannot be later than end date.');
                            }
                        }

                        $contract_data = [
                            'employee_id' => $id,
                            'Start_date' => $data_contract["contract_start_date"][$i] ?? null,
                            'End_date' => $data_contract["contract_end_date"][$i] ?? null,
                            'salary' => isset($data_contract['contract_salary'][$i]) && $data_contract['contract_salary'][$i] !== ''
                                ? (float) $data_contract['contract_salary'][$i]
                                : 0,
                            'salary_grade_id' => !empty($data_contract['contract_salary_grade_id'][$i])
                                ? (int) $data_contract['contract_salary_grade_id'][$i]
                                : 0,
                            'salary_step_id' => !empty($data_contract['contract_salary_step_id'][$i])
                                ? (int) $data_contract['contract_salary_step_id'][$i]
                                : 0,
                            'is_active' => !empty($data_contract['contract_is_active'][$i]) ? 1 : 0,
                        ];

                        if ($contract_id) {
                            DB::table('cos_contract')->where('id', $contract_id)->update($contract_data);
                        } else {
                            $contract_id = DB::table('cos_contract')->insertGetId($contract_data);
                        }

                        $savedContractIds[] = (int) $contract_id;

                        $this->handleCosContractFileUpload($request, $i, (int) $contract_id);
                    }
                }

                // Delete contracts removed from the form (use saved IDs so newly inserted rows are kept)
                if (!empty($savedContractIds)) {
                    $removedContractIds = DB::table('cos_contract')
                        ->where('employee_id', $id)
                        ->whereNotIn('id', $savedContractIds)
                        ->pluck('id');

                    foreach ($removedContractIds as $removedContractId) {
                        $this->deleteCosContractFile((int) $removedContractId);
                    }

                    DB::table('cos_contract')
                        ->where('employee_id', $id)
                        ->whereNotIn('id', $savedContractIds)
                        ->delete();
                }
            } else {
                // If no contracts submitted but employee is COS, delete all existing contracts
                $employee = DB::table('employees')->where('id', $id)->first();
                if ($employee && $employee->employment_type_id == 2) {
                    $existingContractIds = DB::table('cos_contract')->where('employee_id', $id)->pluck('id');
                    foreach ($existingContractIds as $existingContractId) {
                        $this->deleteCosContractFile((int) $existingContractId);
                    }
                    DB::table('cos_contract')->where('employee_id', $id)->delete();
                }
            }

            $this->normalizeCosContractActiveStatus((int) $id);

            app(CosPayrollHoldService::class)->syncEmployee((int) $id);
            app(CosPayrollHoldService::class)->syncEmployeeSalaryFromActiveContract((int) $id);

            // Save Questionaires
            $data_question = $request->all();

            // Check if questionnaire data exists
            if (isset($data_question["question_id"]) && is_array($data_question["question_id"])) {
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
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
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
                    'a.attachment_name',
                    'a.path'
                )
                ->where('a.employee_document_id', $id)
                ->first();

            if (!$documents) {
                return $this->notFoundResponse('Document not found');
            }

            $pathToFile = null;

            // Check if path field contains a path that looks like zipped format
            // Zipped format paths typically contain: \employee_documents\{employee_no}\HRMS\{document_type_id}\{employee_document_id}
            if (!empty($documents->path) && strpos($documents->path, 'HRMS') !== false) {
                // This is zipped format - should use EmployeeDocumentController
                return $this->notFoundResponse('File stored in zipped format. Please use /employee-documents/{id}/download endpoint.');
            }

            // Try multiple path options
            $pathsToTry = [];

            // 1. Use the path from database if it exists
            if (!empty($documents->path)) {
                $pathsToTry[] = $documents->path;
                // Also try with normalized slashes
                $pathsToTry[] = str_replace('\\', '/', $documents->path);
            }

            // 2. Construct path using DOCS format (standard format)
            $constructedPath = storage_path('app/employee_documents/' . 'DOCS' . $documents->employee_id . '_' . $documents->attachment_name);
            $pathsToTry[] = $constructedPath;

            // 3. Try with backslashes for Windows
            $pathsToTry[] = str_replace('/', '\\', $constructedPath);

            // Try each path until we find one that exists
            foreach ($pathsToTry as $path) {
                if (file_exists($path)) {
                    $pathToFile = $path;
                    break;
                }
            }

            if (!$pathToFile || !file_exists($pathToFile)) {
                return $this->notFoundResponse('File not found on server. Tried paths: ' . implode(', ', array_unique($pathsToTry)));
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

    /**
     * Ensure a new employee email is not already used by another user account.
     */
    protected function validateLinkedUserEmailChange(int $employeeId, ?string $employeeNo, ?string $plainEmail): ?string
    {
        $plainEmail = trim((string) $plainEmail);
        if ($plainEmail === '') {
            return null;
        }

        $employeeNo = trim((string) ($employeeNo ?? ''));
        if ($employeeNo === '') {
            $employeeNo = (string) (DB::table('employees')->where('id', $employeeId)->value('employee_no') ?? '');
        }
        if ($employeeNo === '') {
            return null;
        }

        $user = DB::table('users')->where('employee_no', $employeeNo)->first();
        if (!$user) {
            return null;
        }

        $currentEmail = trim((string) ($user->email ?? ''));
        if (strcasecmp($currentEmail, $plainEmail) === 0) {
            return null;
        }

        $emailTaken = DB::table('users')
            ->where('email', $plainEmail)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailTaken) {
            return 'This email address is already taken by another user account.';
        }

        return null;
    }

    /**
     * Keep users.email in sync when employees.email is saved from Employee Records.
     */
    protected function syncLinkedUserEmail(int $employeeId, ?string $employeeNo, ?string $plainEmail): void
    {
        $plainEmail = trim((string) $plainEmail);
        if ($plainEmail === '') {
            return;
        }

        $employeeNo = trim((string) ($employeeNo ?? ''));
        if ($employeeNo === '') {
            $employeeNo = (string) (DB::table('employees')->where('id', $employeeId)->value('employee_no') ?? '');
        }
        if ($employeeNo === '') {
            return;
        }

        $user = DB::table('users')->where('employee_no', $employeeNo)->first();
        if (!$user) {
            return;
        }

        $currentEmail = trim((string) ($user->email ?? ''));
        if (strcasecmp($currentEmail, $plainEmail) === 0) {
            return;
        }

        DB::table('users')->where('id', $user->id)->update([
            'email' => $plainEmail,
            'updated_at' => now(),
        ]);

        if (env('ENABLE_INTEGRATION', false)) {
            try {
                (new UserService)->update(new Request([
                    'email' => $plainEmail,
                    'password' => '',
                ]));
            } catch (\Throwable $e) {
                // Employee save should still succeed; integration sync is best-effort.
            }
        }
    }

    private function handleCosContractFileUpload($request, int $index, int $contractId): void
    {
        $removeFlags = $request->input('contract_remove_file', []);
        $shouldRemove = is_array($removeFlags)
            && array_key_exists($index, $removeFlags)
            && in_array($removeFlags[$index], [1, '1', true, 'true'], true);

        if ($shouldRemove) {
            $this->deleteCosContractFile($contractId);
            return;
        }

        $files = $request->file('contract_file');
        if (!is_array($files) || !isset($files[$index]) || !$files[$index]) {
            return;
        }

        $this->saveCosContractFile($contractId, $files[$index]);
    }

    private function saveCosContractFile(int $contractId, $file): void
    {
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $maxSize = 10 * 1024 * 1024;

        $fileName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension() ?? '');

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \InvalidArgumentException("Invalid contract file type for: {$fileName}");
        }

        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException("Contract file too large: {$fileName}. Maximum size is 10MB.");
        }

        $payload = [
            'cos_contract_id' => $contractId,
            'file_name' => $fileName,
            'file_content' => base64_encode(file_get_contents($file->getRealPath())),
            'file_size' => $file->getSize(),
            'file_type' => $file->getClientMimeType(),
            'path' => null,
            'updated_at' => now(),
        ];

        $existing = DB::connection('attachments')
            ->table('cos_contractfile')
            ->where('cos_contract_id', $contractId)
            ->first();

        if ($existing) {
            DB::connection('attachments')
                ->table('cos_contractfile')
                ->where('id', $existing->id)
                ->update($payload);
            return;
        }

        $payload['created_at'] = now();
        DB::connection('attachments')->table('cos_contractfile')->insert($payload);
    }

    private function normalizeCosContractActiveStatus(int $employeeId): void
    {
        app(CosPayrollHoldService::class)->syncContractActiveStatus($employeeId);
    }

    private function deleteCosContractFile(int $contractId): void
    {
        DB::connection('attachments')
            ->table('cos_contractfile')
            ->where('cos_contract_id', $contractId)
            ->delete();
    }
}