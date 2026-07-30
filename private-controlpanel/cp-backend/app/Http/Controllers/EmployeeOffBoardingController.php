<?php

namespace App\Http\Controllers;

use App\Audit;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeOffBoardingController extends Controller
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

            if (Auth::user()->access_all_branches) {
                $data = DB::table('employees')
                    ->join('employee_offboardings', 'employee_offboardings.employee_id', '=', 'employees.id')
                    ->join('offboarding_natures', 'offboarding_natures.id', '=', 'employee_offboardings.nature_id')
                    ->select(
                        'employee_offboardings.id',
                        'employees.id as employee_id',
                        'employees.photo',
                        'employees.employee_no',
                        'offboarding_natures.name as nature',
                        'employee_offboardings.date_effectivity as effectivity',
                        'employee_offboardings.retirement_date',
                        'employees.active',
                        'employees.is_employee',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                END as name"),
                        'employee_offboardings.reactivated_status_id'
                    )
                    ->orderBy('employee_offboardings.date_effectivity', 'desc')
                    ->distinct()
                    ->get();
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->get();

                $data = DB::table('employees')
                    ->join('employee_offboardings', 'employee_offboardings.employee_id', '=', 'employees.id')
                    ->join('offboarding_natures', 'offboarding_natures.id', '=', 'employee_offboardings.nature_id')
                    ->select(
                        'employee_offboardings.id',
                        'employees.id as employee_id',
                        'employees.photo',
                        'employees.employee_no',
                        'offboarding_natures.name as nature',
                        'employee_offboardings.date_effectivity as effectivity',
                        'employee_offboardings.retirement_date',
                        'employees.active',
                        'employees.is_employee',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                END as name"),
                        'employee_offboardings.reactivated_status_id'
                    )
                    ->where('employees.branch_id', $user_branch_id[0]->branch_id)
                    ->orderBy('employee_offboardings.date_effectivity', 'desc')
                    ->distinct()
                    ->get();
            }

            return $this->successResponse($data, 'Off-boarding records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve off-boarding records: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            if (Auth::user()->access_all_branches) {
                if ($id == 0) {
                    $employees = DB::table('employees')
                        ->select(
                            'id',
                            'photo',
                            DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                        CONCAT(employees.first_name,' ',employees.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                    END as name")
                        )
                        ->where(['is_employee' => true, 'active' => true])
                        ->orderBy('first_name', 'asc')
                        ->get();
                } else {
                    $employees = DB::table('employees')
                        ->select(
                            'id',
                            'photo',
                            DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                        CONCAT(employees.first_name,' ',employees.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                    END as name")
                        )
                        ->orderBy('first_name', 'asc')
                        ->get();
                }
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->get();

                if ($id == 0) {
                    $employees = DB::table('employees')
                        ->select(
                            'id',
                            'photo',
                            DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                        CONCAT(employees.first_name,' ',employees.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                    END as name")
                        )
                        ->where(['is_employee' => true, 'active' => true, 'branch_id' => $user_branch_id[0]->branch_id])
                        ->orderBy('first_name', 'asc')
                        ->get();
                } else {
                    $employees = DB::table('employees')
                        ->select(
                            'id',
                            'photo',
                            DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                        CONCAT(employees.first_name,' ',employees.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                    END as name")
                        )
                        ->where(['branch_id' => $user_branch_id[0]->branch_id])
                        ->orderBy('first_name', 'asc')
                        ->get();
                }
            }

            $natures = DB::table('offboarding_natures')->where('active', true)->orderBy('id', 'asc')->get();

            if ($id == 0) {
                $dummy_off_boards = array(
                    'id' => 0,
                    'employee_id' => 0,
                    'nature_id' => 0,
                    'remarks' => null,
                    'date_effectivity' => null,
                    'retirement_date' => null
                );

                $off_boards = (object)$dummy_off_boards;
                $off_boards = collect([$off_boards]);
            } else {
                $off_boards = DB::table('employee_offboardings')->where('id', $id)->get();
            }

            return $this->successResponse([
                'natures' => $natures,
                'employees' => $employees,
                'off_boards' => $off_boards
            ], 'Off-boarding form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load off-boarding form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'employee_id' => 'required|integer|exists:employees,id',
                'nature_id' => 'required|integer|exists:offboarding_natures,id',
                'remarks' => 'nullable|string|max:500',
                'date_effectivity' => 'required|date',
                'retirement_date' => 'nullable|date|after_or_equal:date_effectivity'
            ], [
                'employee_id.required' => 'Employee is required.',
                'employee_id.exists' => 'Selected employee does not exist.',
                'nature_id.required' => 'Off-boarding nature is required.',
                'nature_id.exists' => 'Selected off-boarding nature does not exist.',
                'date_effectivity.required' => 'Date of effectivity is required.',
                'date_effectivity.date' => 'Date of effectivity must be a valid date.',
                'retirement_date.after_or_equal' => 'Retirement date must be on or after the date of effectivity.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $off_boarding = DB::table('employee_offboardings')->where([
                'id' => $id,
                'reactivated_status_id' => 1
            ])
                ->get();

            if ($off_boarding->isNotEmpty()) {
                $reactivated_status_id = 1;

                // Update Employee Info
                $data_employee = array(
                    'is_employee' => true,
                    'active' => true
                );
            } else {
                $reactivated_status_id = 0;

                // Update Employee Info
                $data_employee = array(
                    'is_employee' => false,
                    'active' => false
                );
            }

            // Save Promotion
            $data_offboard = array(
                'employee_id' => $request->employee_id,
                'nature_id' => $request->nature_id,
                'remarks' => $request->remarks,
                'date_effectivity' => $request->date_effectivity,
                'retirement_date' => $request->retirement_date,
                'reactivated_status_id' => $reactivated_status_id,
            );

            if ($id == 0) {
                $id = DB::table('employee_offboardings')->max('id') + 1;
            }

            DB::unprepared('SET IDENTITY_INSERT employee_offboardings ON');
            DB::table('employee_offboardings')->updateOrInsert(['id' => $id], $data_offboard);
            DB::unprepared('SET IDENTITY_INSERT employee_offboardings OFF');

            $employee_id = $request->employee_id;
            DB::table('employees')->where('id', $employee_id)->update($data_employee);

            // Update User Info
            $user_id = DB::table('employees')
                ->join('users', 'users.employee_no', '=', 'employees.employee_no')
                ->select('users.id', 'users.email')
                ->where('employees.id', $employee_id)
                ->get();

            if ($user_id->isNotEmpty()) {
                $data_user = array(
                    'locked' => true
                );

                DB::table('users')->where('id', $user_id[0]->id)->update($data_user);

                $request->merge([
                    'email' => $user_id[0]->email,
                    'password' => '',
                ]);

                if (env("ENABLE_INTEGRATION", false)) {
                    (new UserService)->update($request);
                }
            }

            // Update Plantilla
            $plantilla_id = DB::table('employees')
                ->select('plantilla_id')
                ->where('employees.id', $employee_id)
                ->get();

            if (($plantilla_id[0]->plantilla_id) <> 0) {
                $data_plantilla = array(
                    'employee_id' => 0
                );
                DB::table('plantillas')->where('id', $plantilla_id[0]->plantilla_id)->update($data_plantilla);
            }

            // Save Service Record
            $position_name = DB::table('employees')
                ->join('positions', 'positions.id', '=', 'employees.position_id')
                ->select('positions.name')
                ->where('employees.id', $employee_id)
                ->get();

            $employment_type_name = DB::table('employees')
                ->join('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select('name')
                ->where('employees.id', $employee_id)
                ->get();

            $anual_salary_data = DB::table('employees')->select('salary')->where('id', $employee_id)->get();
            $anual_salary = ($anual_salary_data[0]->salary * 12);
            $company_name = DB::table('companies')->select('name')->where('id', 1)->get();

            $branch_name = DB::table('employees')
                ->join('branches', 'branches.id', '=', 'employees.branch_id')
                ->select('name')
                ->where('employees.id', $employee_id)->get();

            $natures = DB::table('offboarding_natures')->select('name')->where('id', $request->nature_id)->get();

            $serv_data = [
                'employee_id' => $employee_id,
                'start_date' => $request->date_effectivity,
                'end_date' => $request->date_effectivity,
                'designation' => $position_name[0]->name,
                'employment_type' => isset($employment_type_name[0]->name) ? $employment_type_name[0]->name : '',
                'annual_salary' => $anual_salary,
                'place_of_assignment' => isset($company_name[0]->name) ? $company_name[0]->name : '',
                'leave_without_pay' => 0,
                'separation_date' => $request->date_effectivity,
                'cause' => isset($natures[0]->name) ? $natures[0]->name : '',
                'branch' => isset($branch_name[0]->name) ? $branch_name[0]->name : '',
            ];

            $serv_id = 0;
            $serv_id = 0 + DB::table('service_records')->max('service_record_id');
            $serv_id += 1;

            DB::unprepared('SET IDENTITY_INSERT service_records ON');
            DB::table('service_records')->updateOrInsert(['service_record_id' => $serv_id], $serv_data);
            DB::unprepared('SET IDENTITY_INSERT service_records OFF');

            //Save audit trail
            if ($id == 0) {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'Off-boarding',
                    'activity' => 'Add',
                    'description' => 'Added employee off-boarding information',
                );

                Audit::create($data_audit);

                return $this->successResponse([
                    'id' => $id,
                    'employee_id' => $request->employee_id,
                    'nature_id' => $request->nature_id,
                    'date_effectivity' => $request->date_effectivity,
                    'retirement_date' => $request->retirement_date,
                    'reactivated_status_id' => $reactivated_status_id,
                    'summary' => [
                        'action' => 'added',
                        'employee_id' => $request->employee_id,
                        'nature' => isset($natures[0]->name) ? $natures[0]->name : '',
                        'date_effectivity' => $request->date_effectivity,
                        'service_record_id' => $serv_id
                    ]
                ], 'Employee off-boarding information added successfully!');
            } else {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'Off-boarding',
                    'activity' => 'Update',
                    'description' => 'Updated employee off-boarding information',
                );

                Audit::create($data_audit);

                return $this->successResponse([
                    'id' => $id,
                    'employee_id' => $request->employee_id,
                    'nature_id' => $request->nature_id,
                    'date_effectivity' => $request->date_effectivity,
                    'retirement_date' => $request->retirement_date,
                    'reactivated_status_id' => $reactivated_status_id,
                    'summary' => [
                        'action' => 'updated',
                        'employee_id' => $request->employee_id,
                        'nature' => isset($natures[0]->name) ? $natures[0]->name : '',
                        'date_effectivity' => $request->date_effectivity,
                        'service_record_id' => $serv_id
                    ]
                ], 'Employee off-boarding information updated successfully!');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save employee off-boarding information: ' . $e->getMessage());
        }
    }

    public function info($id, $employee_id)
    {
        try {
            $app_key = env("APP_KEY", "");
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
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_occupation ELSE dbo.ufn_DecryptString(spouse_occupation,'$app_key') END as spouse_occupation"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_employer ELSE dbo.ufn_DecryptString(spouse_employer,'$app_key') END as spouse_employer"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_business_address ELSE dbo.ufn_DecryptString(spouse_business_address,'$app_key') END as spouse_business_address"),
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
                    'account_no',
                    'active'
                )
                ->where('id', $employee_id)->get();

            if ($employee_info->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

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
            $divisions = DB::table('divisions')->where('active', true)->orderBy('name', 'asc')->get();
            $sections = DB::table('sections')->where('active', true)->orderBy('name', 'asc')->get();
            $offboarding_id = $id;

            $reactivated = DB::table('employee_offboardings')->select('reactivated_status_id')
                ->where('employee_id', $employee_id)
                ->where('id', '>=', $id)
                ->where('reactivated_status_id', 1)
                ->get();

            if ($reactivated->isNotEmpty()) {
                $reactivated_status = true;
            } else {
                $reactivated_status = false;
            }

            return $this->successResponse([
                'reactivated_status' => $reactivated_status,
                'offboarding_id' => $offboarding_id,
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
                'divisions' => $divisions,
                'sections' => $sections
            ], 'Off-boarding info retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve off-boarding info: ' . $e->getMessage());
        }
    }

    public function activate($id, $employee_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('employees')
                ->select(
                    'id',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                            END as name")
                )
                ->where('id', $employee_id)
                ->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            return $this->successResponse([
                'data' => $data,
                'id' => $id
            ], 'Off-boarding reactivate data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve off-boarding reactivate data: ' . $e->getMessage());
        }
    }

    public function reactivate(Request $request, $id, $employee_id)
    {
        try {
            $validator = validator($request->all(), [
                'date_effectivity' => 'required|date'
            ], [
                'date_effectivity.required' => 'Date of effectivity is required.',
                'date_effectivity.date' => 'Date of effectivity must be a valid date.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            DB::table('employee_offboardings')->where('id', $id)->update(['reactivated_status_id' => 1]);

            $employee_id = $employee_id;

            // Update Employee Info
            $data_employee = array(
                'is_employee' => true,
                'active' => true
            );

            DB::table('employees')->where('id', $employee_id)->update($data_employee);

            $users = DB::table('users as a')
                ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('a.*')
                ->where('b.id', $employee_id)
                ->limit(1)
                ->get();

            if ($users->isNotEmpty()) {
                DB::table('users')->where('id', $users[0]->id)->update(['locked' => false, 'locked_date' => null]);

                $request->merge([
                    'email' => $users[0]->email,
                    'password' => '',
                ]);
            }

            // Save Service Record
            $position_name = DB::table('employees')
                ->join('positions', 'positions.id', '=', 'employees.position_id')
                ->select('positions.name')
                ->where('employees.id', $employee_id)
                ->get();

            $employment_type_name = DB::table('employees')
                ->join('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select('name')
                ->where('employees.id', $employee_id)
                ->get();

            $anual_salary_data = DB::table('employees')->select('salary')->where('id', $employee_id)->get();
            $anual_salary = ($anual_salary_data[0]->salary * 12);
            $company_name = DB::table('companies')->select('name')->where('id', 1)->get();

            $branch_name = DB::table('employees')
                ->join('branches', 'branches.id', '=', 'employees.branch_id')
                ->select('name')
                ->where('employees.id', $employee_id)->get();

            $serv_data = [
                'employee_id' => $employee_id,
                'start_date' => $request->date_effectivity,
                'end_date' => $request->date_effectivity,
                'designation' => isset($position_name[0]->name) ? $position_name[0]->name : '',
                'employment_type' => $employment_type_name[0]->name ?? '',
                'annual_salary' => $anual_salary,
                'place_of_assignment' => isset($company_name[0]->name) ? $company_name[0]->name : '',
                'leave_without_pay' => 0,
                'separation_date' => null,
                'cause' => 'Re-activate',
                'branch' => isset($branch_name[0]->name) ? $branch_name[0]->name : '',
            ];

            $serv_id = 0;
            $serv_id = 0 + DB::table('service_records')->max('service_record_id');
            $serv_id += 1;

            DB::unprepared('SET IDENTITY_INSERT service_records ON');
            DB::table('service_records')->updateOrInsert(['service_record_id' => $serv_id], $serv_data);
            DB::unprepared('SET IDENTITY_INSERT service_records OFF');

            $data = DB::table('employees')
                ->select(
                    'id',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                            END as name")
                )
                ->where('id', $employee_id)
                ->get();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Off-boarding Re-Activation',
                'activity' => 'Re-Activate',
                'description' => 'Re-activated employee ' . $data[0]->name ?? '',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'id' => $id,
                'employee_id' => $employee_id,
                'date_effectivity' => $request->date_effectivity,
                'service_record_id' => $serv_id,
                'employee_name' => $data[0]->name ?? '',
                'summary' => [
                    'action' => 'reactivated',
                    'employee_id' => $employee_id,
                    'employee_name' => $data[0]->name ?? '',
                    'date_effectivity' => $request->date_effectivity,
                    'service_record_id' => $serv_id
                ]
            ], 'Employee re-activated successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to reactivate employee: ' . $e->getMessage());
        }
    }
}
