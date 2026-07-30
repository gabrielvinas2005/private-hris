<?php

namespace App\Http\Controllers;

use PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PersonalDataSheetController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Safely load JSON data from file with fallback
     */
    private function loadJsonData($filename, $key = 'RECORDS')
    {
        try {
            if (file_exists($filename)) {
                $data = file_get_contents($filename);
                $decoded = json_decode($data, true);
                return isset($decoded[$key]) ? array_filter($decoded[$key]) : [];
            }
        } catch (\Exception $e) {
            // Log error but continue
            \Log::warning("Failed to load {$filename}: " . $e->getMessage());
        }
        return [];
    }

    /**
     * Format field value to show N/A for blank/null values
     */
    private function formatField($value, $default = 'N/A')
    {
        if (empty($value) || $value === null || $value === '') {
            return $default;
        }
        return $value;
    }

    /**
     * Format employee data to show N/A for blank fields
     */
    private function formatEmployeeData($employeeData)
    {
        foreach ($employeeData as $employee) {
            // Format personal information fields
            $employee->first_name = $this->formatField($employee->first_name);
            $employee->middle_name = $this->formatField($employee->middle_name);
            $employee->last_name = $this->formatField($employee->last_name);
            $employee->suffix = $this->formatField($employee->suffix);
            $employee->birth_place = $this->formatField($employee->birth_place);
            $employee->gender = $this->formatField($employee->gender);
            $employee->height = $this->formatField($employee->height);
            $employee->weight = $this->formatField($employee->weight);
            $employee->blood_type = $this->formatField($employee->blood_type);
            $employee->email = $this->formatField($employee->email);
            $employee->mobile_no = $this->formatField($employee->mobile_no);
            $employee->telephone_no = $this->formatField($employee->telephone_no);
            $employee->citizenship = $this->formatField($employee->citizenship);
            $employee->civil_status = $this->formatField($employee->civil_status);
            $employee->religion = $this->formatField($employee->religion);
            $employee->indicate_country = $this->formatField($employee->indicate_country);

            // Format address fields
            $employee->ra_house_no = $this->formatField($employee->ra_house_no);
            $employee->ra_barangay = $this->formatField($employee->ra_barangay);
            $employee->ra_street = $this->formatField($employee->ra_street);
            $employee->ra_village = $this->formatField($employee->ra_village);
            $employee->pa_house_no = $this->formatField($employee->pa_house_no);
            $employee->pa_barangay = $this->formatField($employee->pa_barangay);
            $employee->pa_street = $this->formatField($employee->pa_street);
            $employee->pa_village = $this->formatField($employee->pa_village);

            // Format family information
            $employee->father_name = $this->formatField($employee->father_name);
            $employee->mother_name = $this->formatField($employee->mother_name);
            $employee->spouse_name = $this->formatField($employee->spouse_name);
            $employee->spouse_occupation = $this->formatField($employee->spouse_occupation);
            $employee->spouse_employer = $this->formatField($employee->spouse_employer);
            $employee->spouse_business_address = $this->formatField($employee->spouse_business_address);
            $employee->spouse_mobile_no = $this->formatField($employee->spouse_mobile_no);

            // Format employment information
            $employee->company = $this->formatField($employee->company);
            $employee->branch = $this->formatField($employee->branch);
            $employee->department = $this->formatField($employee->department);
            $employee->division = $this->formatField($employee->division);
            $employee->section = $this->formatField($employee->section);
            $employee->employment_type = $this->formatField($employee->employment_type);
            $employee->position = $this->formatField($employee->position);
            $employee->plantilla = $this->formatField($employee->plantilla);
            $employee->grade = $this->formatField($employee->grade);
            $employee->step = $this->formatField($employee->step);
            $employee->date_hired = $this->formatField($employee->date_hired);

            // Format government IDs
            $employee->tin_no = $this->formatField($employee->tin_no);
            $employee->gsis_no = $this->formatField($employee->gsis_no);
            $employee->sss_no = $this->formatField($employee->sss_no);
            $employee->pagibig_no = $this->formatField($employee->pagibig_no);
            $employee->philhealth_no = $this->formatField($employee->philhealth_no);
            $employee->salary = $this->formatField($employee->salary);
        }

        return $employeeData;
    }

    /**
     * Format children data to show N/A for blank fields
     */
    private function formatChildrenData($childrenData)
    {
        foreach ($childrenData as $child) {
            $child->child_name = $this->formatField($child->child_name);
            $child->child_lastname = $this->formatField($child->child_lastname);
            $child->child_middlename = $this->formatField($child->child_middlename);
            $child->child_birthdate = $this->formatField($child->child_birthdate);
            $child->name = $this->formatField($child->name);
        }

        return $childrenData;
    }

    /**
     * Format education data to show N/A for blank fields
     */
    private function formatEducationData($educationData)
    {
        foreach ($educationData as $education) {
            $education->school_name = $this->formatField($education->school_name ?? null);
            $education->program = $this->formatField($education->program ?? null); // Changed from degree to program
            $education->from = $this->formatField($education->from ?? null); // Changed from period_from to from
            $education->to = $this->formatField($education->to ?? null); // Changed from period_to to to
            $education->units_earned = $this->formatField($education->units_earned ?? null);
            $education->graduated_year = $this->formatField($education->graduated_year ?? null); // Changed from year_graduated to graduated_year
            $education->honors = $this->formatField($education->honors ?? null); // Changed from scholarship to honors

            // Keep old property names for backward compatibility if the view template uses them
            $education->degree = $education->program;
            $education->period_from = $education->from;
            $education->period_to = $education->to;
            $education->year_graduated = $education->graduated_year;
            $education->scholarship = $education->honors;
        }

        return $educationData;
    }

    /**
     * Format employment data to show N/A for blank fields
     */
    private function formatEmploymentData($employmentData)
    {
        foreach ($employmentData as $employment) {
            // Use null-coalescing so we don't trigger undefined property notices
            // Map legacy view fields from available columns in employee_employment_records
            $employment->position_title = $this->formatField($employment->position_title ?? ($employment->position ?? null));
            $employment->department     = $this->formatField($employment->department ?? null);
            $employment->agency         = $this->formatField($employment->agency ?? ($employment->work_company ?? null));
            $employment->period_from    = $this->formatField($employment->period_from ?? ($employment->work_start_date ?? null));
            $employment->period_to      = $this->formatField($employment->period_to ?? ($employment->work_end_date ?? null));
            $employment->salary         = $this->formatField($employment->salary ?? ($employment->monthly_salary ?? null));
            $employment->status         = $this->formatField($employment->status ?? ($employment->status_of_appointment ?? null));
        }

        return $employmentData;
    }

    /**
     * Format examinations data to show N/A for blank fields
     */
    private function formatExaminationsData($examinationsData)
    {
        foreach ($examinationsData as $examination) {
            // Some records use exam_rating instead of rating; keep both for template compatibility.
            $examination->eligibility = $this->formatField($examination->eligibility ?? null);
            $examination->rating = $this->formatField($examination->rating ?? ($examination->exam_rating ?? null));
            $examination->exam_rating = $examination->rating;
            $examination->date_of_examination = $this->formatField($examination->date_of_examination ?? null);
            $examination->place_of_examination = $this->formatField($examination->place_of_examination ?? null);
            $examination->license_no = $this->formatField($examination->license_no ?? null);
            $examination->date_of_validity = $this->formatField($examination->date_of_validity ?? null);
        }

        return $examinationsData;
    }

    /**
     * Format trainings data to show N/A for blank fields
     */
    private function formatTrainingsData($trainingsData)
    {
        foreach ($trainingsData as $training) {
            $training->training = $this->formatField($training->training ?? null);
            $training->learning = $this->formatField($training->learning ?? null);

            $training->training_from = $this->formatField($training->training_from ?? null);
            $training->training_to = $this->formatField($training->training_to ?? null);
            $training->hours = $this->formatField($training->hours ?? null);

            $training->type = $this->formatField($training->training_type ?? null);
            $training->conducted_by = $this->formatField($training->conducted_by ?? null);
            $training->sponsored_by = $this->formatField($training->sponsored_by ?? null);

            // Optional aliases for compatibility
            $training->inclusive_dates_from = $training->training_from;
            $training->inclusive_dates_to = $training->training_to;
            $training->number_of_hours = $training->hours;
        }

        return $trainingsData;
    }

    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->join('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE ISNULL(dbo.ufn_DecryptString(a.first_name,'$app_key'), '') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            ISNULL(a.first_name, '') + ' ' + ISNULL(a.last_name, '')
                        ELSE
                            ISNULL(RTRIM(dbo.ufn_DecryptString(a.first_name,'$app_key')), '') + ' ' + ISNULL(RTRIM(dbo.ufn_DecryptString(a.last_name,'$app_key')), '')
                        END as name")
                )
                ->where(['a.active' => true, 'a.is_employee' => true])
                ->orderBy(DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE ISNULL(dbo.ufn_DecryptString(a.first_name,'$app_key'), '') END"), 'asc')
                ->get();

            return $this->successResponse($data, 'Personal data sheet list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve personal data sheet list: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            // Validate employee parameter first
            if (!$request->has('employee') || !$request->employee) {
                return $this->errorResponse('Employee ID is required.', 422);
            }

            $employeeId = (int) $request->employee;
            if ($employeeId <= 0) {
                return $this->errorResponse('Invalid employee ID.', 422);
            }

            $app_key = env("APP_KEY", "");

            // Initialize empty arrays for address data to prevent file_get_contents errors
            $region_data = [];
            $province_data = [];
            $city_data = [];
            $brgy_data = [];

            $info =  DB::table('employees as b')
                ->leftJoin('name_prefixes as c', 'c.id', '=', 'b.name_prefix_id')
                ->leftJoin('name_suffixes as d', 'd.id', '=', 'b.name_suffix_id')
                ->leftJoin('genders as e', 'e.id', '=', 'b.gender_id')
                ->leftJoin('citizenships as f', 'f.id', '=', 'b.citizenship_id')
                ->leftJoin('civil_status as g', 'g.id', '=', 'b.civil_status_id')
                ->leftJoin('religions as h', 'h.id', '=', 'b.religion_id')
                ->leftJoin('blood_types as i', 'i.id', '=', 'b.blood_type_id')
                ->leftJoin('name_prefixes as cf', 'cf.id', '=', 'b.father_name_prefix_id')
                ->leftJoin('name_suffixes as df', 'df.id', '=', 'b.father_name_suffix_id')
                ->leftJoin('name_prefixes as cm', 'cm.id', '=', 'b.mother_name_prefix_id')
                ->leftJoin('name_suffixes as dm', 'dm.id', '=', 'b.mother_name_suffix_id')
                ->leftJoin('name_prefixes as cs', 'cs.id', '=', 'b.spouse_name_prefix_id')
                ->leftJoin('name_suffixes as ds', 'ds.id', '=', 'b.spouse_name_suffix_id')
                ->leftJoin('companies as j', 'j.id', '=', 'b.company_id')
                ->leftJoin('branches as k', 'k.id', '=', 'b.branch_id')
                ->leftJoin('departments as l', 'l.id', '=', 'b.department_id')
                ->leftJoin('employment_types as m', 'm.id', '=', 'b.employment_type_id')
                ->leftJoin('positions as n', 'n.id', '=', 'b.position_id')
                ->leftJoin('plantillas as o', 'o.id', '=', 'b.plantilla_id')
                ->leftJoin('payroll_intervals as p', 'p.id', '=', 'b.payroll_interval_id')
                ->leftJoin('salary_grades as r', 'r.id', '=', 'b.salary_grade_id')
                ->leftJoin('salary_steps as q', 'q.id', '=', 'b.salary_step_id')
                ->leftJoin('divisions as t', 't.id', '=', 'b.division_id')
                ->leftJoin('sections as u', 'u.id', '=', 'b.section_id')
                ->select(
                    'b.id',
                    'b.employee_no',
                    'b.access_no',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                            CONCAT(b.first_name,' ',b.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                        END as name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'd.name as suffix',
                    'b.birth_place',
                    'b.birthdate',
                    'b.age',
                    'e.name as gender',
                    'b.height',
                    'b.weight',
                    'i.name as blood_type',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.email ELSE dbo.ufn_DecryptString(b.email,'$app_key') END as email"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.mobile_no ELSE dbo.ufn_DecryptString(b.mobile_no,'$app_key') END as mobile_no"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.telephone_no ELSE dbo.ufn_DecryptString(b.telephone_no,'$app_key') END as telephone_no"),
                    'f.name as citizenship',
                    'g.name as civil_status',
                    'h.name as religion',
                    'b.is_dual_citizent',
                    'b.by_birth',
                    'b.by_naturalization',
                    'b.indicate_country',
                    'b.ra_region',
                    'b.ra_province',
                    'b.ra_city',
                    'b.ra_house_no',
                    'b.ra_barangay',
                    'b.ra_street',
                    'b.ra_village',
                    'b.pa_house_no',
                    'b.pa_barangay',
                    'b.pa_street',
                    'b.pa_village',
                    'b.pa_region',
                    'b.pa_province',
                    'b.pa_city',
                    DB::raw("CONCAT(cf.name,' ',b.father_first_name,' ',substring(b.father_middle_name,1,1),'. ',b.father_last_name,' ',df.name) as father_name"),
                    DB::raw("CONCAT(cm.name,' ',b.mother_first_name,' ',substring(b.mother_middle_name,1,1),'. ',b.mother_last_name,' ',dm.name) as mother_name"),
                    DB::raw("CONCAT(cs.name,' ',b.spouse_first_name,' ',substring(b.spouse_middle_name,1,1),'. ',b.spouse_last_name,' ',ds.name) as spouse_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.father_first_name ELSE dbo.ufn_DecryptString(b.father_first_name,'$app_key') END as father_first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.father_middle_name ELSE dbo.ufn_DecryptString(b.father_middle_name,'$app_key') END as father_middle_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.father_last_name ELSE dbo.ufn_DecryptString(b.father_last_name,'$app_key') END as father_last_name"),
                    'df.name as father_suffix',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.mother_first_name ELSE dbo.ufn_DecryptString(b.mother_first_name,'$app_key') END as mother_first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.mother_middle_name ELSE dbo.ufn_DecryptString(b.mother_middle_name,'$app_key') END as mother_middle_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.mother_last_name ELSE dbo.ufn_DecryptString(b.mother_last_name,'$app_key') END as mother_last_name"),
                    'dm.name as mother_suffix',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.spouse_first_name ELSE dbo.ufn_DecryptString(b.spouse_first_name,'$app_key') END as spouse_first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.spouse_middle_name ELSE dbo.ufn_DecryptString(b.spouse_middle_name,'$app_key') END as spouse_middle_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.spouse_last_name ELSE dbo.ufn_DecryptString(b.spouse_last_name,'$app_key') END as spouse_last_name"),
                    // 'b.spouse_first_name',
                    // 'b.spouse_middle_name',
                    // 'b.spouse_last_name',
                    'ds.name as spouse_suffix',
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.spouse_occupation ELSE dbo.ufn_DecryptString(b.spouse_occupation,'$app_key') END as spouse_occupation"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.spouse_employer ELSE dbo.ufn_DecryptString(b.spouse_employer,'$app_key') END as spouse_employer"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.spouse_business_address ELSE dbo.ufn_DecryptString(b.spouse_business_address,'$app_key') END as spouse_business_address"),
                    'b.spouse_occupation',
                    'b.spouse_employer',
                    'b.spouse_business_address',
                    'b.spouse_mobile_no',
                    'j.name as company',
                    'k.name as branch',
                    'l.name as department',
                    't.name as division',
                    'u.name as section',
                    'b.work_schedule_id',
                    'm.name as employment_type',
                    'n.name as position',
                    'o.code as plantilla',
                    'r.name as grade',
                    'q.name as step',
                    'b.is_shifting',
                    'b.is_plantilla',
                    'b.is_employee',
                    'b.is_teaching',
                    'b.date_hired',
                    'b.tin_no',
                    'b.gsis_no',
                    'b.sss_no',
                    'b.pagibig_no',
                    'b.philhealth_no',
                    'b.salary',
                    'b.tax_amount',
                    'b.gsis_amount',
                    'b.sss_amount',
                    'b.pagibig_amount',
                    'b.philhealth_amount',
                    'p.name as payroll_interval',
                    'b.ra_postal_id',
                    'b.pa_postal_id'
                )
                ->where('b.id', $employeeId)
                ->get();

            if ($info->isEmpty()) {
                return $this->errorResponse('Employee record not found or inactive.');
            }

            // Format employee data to show N/A for blank fields
            $info = $this->formatEmployeeData($info);

            $emp_id = $info[0]->id;

            // Initialize empty arrays for address data to prevent file_get_contents errors
            $region_data = [];
            $province_data = [];
            $city_data = [];
            $brgy_data = [];

            // TODO: Add proper region/province/city/barangay data handling
            // For now, using empty arrays to prevent file_get_contents errors

            $ra_region = collect($region_data)->where("regCode", $info[0]->ra_region)->all();
            $pa_region = collect($region_data)->where("regCode", $info[0]->pa_region)->all();

            // set collection for address
            $ra_province = collect($province_data)->where("provCode", $info[0]->ra_province)->all();
            $pa_province = collect($province_data)->where("provCode", $info[0]->pa_province)->all();

            $ra_city = collect($city_data)->where("citymunCode", $info[0]->ra_city)->all();
            $pa_city = collect($city_data)->where("citymunCode", $info[0]->pa_city)->all();

            $ra_brgy = collect($brgy_data)->where("brgyCode", $info[0]->ra_barangay)->all();
            $pa_brgy = collect($brgy_data)->where("brgyCode", $info[0]->pa_barangay)->all();

            // Initialize address variables
            $ra_region_id = null;
            $pa_region_id = null;
            $ra_province_id = null;
            $pa_province_id = null;
            $ra_city_id = null;
            $pa_city_id = null;
            $ra_brgy_id = null;
            $pa_brgy_id = null;

            $address = collect(array(
                'ra_region' => '',
                'pa_region' => '',
                'ra_province' => '',
                'pa_province' => '',
                'ra_city' => '',
                'pa_city' => '',
                'ra_brgy' => '',
                'pa_brgy' => ''
            ));
            // end of getting address data

            $children = DB::table('employee_children as a')
                ->select(
                    DB::raw("ROW_NUMBER() OVER(ORDER BY a.child_birthdate ASC) AS row"),
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(a.child_middlename,'') = '' THEN
                                CONCAT(a.child_name,' ',a.child_lastname)
                            ELSE
                                CONCAT(a.child_name,' ',upper(substring(a.child_middlename,1,1)),'. ',a.child_lastname)
                            END as name")
                )
                ->where('employee_id', $emp_id)
                ->orderBy('a.child_birthdate', 'asc')
                ->limit(13)
                ->get();

            // Format children data to show N/A for blank fields
            $children = $this->formatChildrenData($children);

            $educations_elem = DB::table('employee_educations')
                ->select(
                    '*',
                    'program as degree',
                    'from as period_from',
                    'to as period_to',
                    'graduated_year as year_graduated',
                    'honors as scholarship'
                )
                ->where(['employee_id' => $emp_id, 'academic_level_id' => 0])
                ->limit(1)
                ->get();

            $educations_sec = DB::table('employee_educations')
                ->select(
                    '*',
                    'program as degree',
                    'from as period_from',
                    'to as period_to',
                    'graduated_year as year_graduated',
                    'honors as scholarship'
                )
                ->where(['employee_id' => $emp_id, 'academic_level_id' => 1])
                ->limit(1)
                ->get();

            $educations_voc = DB::table('employee_educations')
                ->select(
                    '*',
                    'program as degree',
                    'from as period_from',
                    'to as period_to',
                    'graduated_year as year_graduated',
                    'honors as scholarship'
                )
                ->where(['employee_id' => $emp_id, 'academic_level_id' => 2])
                ->limit(1)
                ->get();

            $educations_col = DB::table('employee_educations')
                ->select(
                    '*',
                    'program as degree',
                    'from as period_from',
                    'to as period_to',
                    'graduated_year as year_graduated',
                    'honors as scholarship'
                )
                ->where(['employee_id' => $emp_id, 'academic_level_id' => 3])
                ->limit(1)
                ->get();

            $educations_grad = DB::table('employee_educations')
                ->select(
                    '*',
                    'program as degree',
                    'from as period_from',
                    'to as period_to',
                    'graduated_year as year_graduated',
                    'honors as scholarship'
                )
                ->where(['employee_id' => $emp_id, 'academic_level_id' => 4])
                ->limit(1)
                ->get();

            // Format education data to show N/A for blank fields
            $educations_elem = $this->formatEducationData($educations_elem);
            $educations_sec = $this->formatEducationData($educations_sec);
            $educations_voc = $this->formatEducationData($educations_voc);
            $educations_col = $this->formatEducationData($educations_col);
            $educations_grad = $this->formatEducationData($educations_grad);

            $employments = DB::table('employee_employment_records')->where('employee_id', $emp_id)->limit(28)->get();

            // Format employment data to show N/A for blank fields
            $employments = $this->formatEmploymentData($employments);
            $examinations = DB::table('employee_examinations as a')
                ->join('eligibilities as b', 'b.id', '=', 'a.eligibility_id')
                ->select('a.*', 'b.name as eligibility')
                ->where('employee_id', $emp_id)->limit(14)->get();

            // Format examinations data to show N/A for blank fields
            $examinations = $this->formatExaminationsData($examinations);

            $trainings = DB::table('employee_trainings as a')
                ->join('learnings as b', 'b.id', '=', 'a.learning_id')
                ->select('a.*', 'b.name as learning')
                ->where('employee_id', $emp_id)->limit(22)->get();

            // Format trainings data to show N/A for blank fields
            $trainings = $this->formatTrainingsData($trainings);

            $organizations = DB::table('employee_organizations')->where('employee_id', $emp_id)->limit(7)->get();

            $recognitions = DB::table('employee_recognations')
                ->select(
                    DB::raw("ROW_NUMBER() OVER(ORDER BY recognation ASC) AS row"),
                    '*'
                )
                ->where('employee_id', $emp_id)
                ->limit(10)
                ->orderBy('recognation', 'asc')
                ->get();

            $skills = DB::table('employee_skills')
                ->select(
                    DB::raw("ROW_NUMBER() OVER(ORDER BY skill ASC) AS row"),
                    '*'
                )
                ->where('employee_id', $emp_id)
                ->limit(10)
                ->orderBy('skill', 'asc')
                ->get();

            $memberships = DB::table('employee_memberships')
                ->select(
                    DB::raw("ROW_NUMBER() OVER(ORDER BY membership ASC) AS row"),
                    '*'
                )
                ->where('employee_id', $emp_id)
                ->limit(10)
                ->orderBy('membership', 'asc')
                ->get();

            $references = DB::table('employee_references')->where('employee_id', $emp_id)->limit(3)->get();

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
                ->whereNotIn('a.id', function ($query) use ($emp_id) {
                    $query->select('question_id')->from('employee_pds_answers')->where('employee_id', $emp_id);
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
                ->where('a.employee_id', $emp_id)
                ->union($quesionaires_setup)
                ->orderBy('id', 'asc')
                ->get();

            if ($quesionaires->isEmpty()) {
                $quesionaires = DB::table('pds_questionaires')->orderBy('id', 'asc')->get();
            }

            $pdf = PDF::loadView('pds.pds_print_2025', compact(
                'info',
                'children',
                'educations_elem',
                'educations_sec',
                'educations_voc',
                'educations_col',
                'educations_grad',
                'employments',
                'examinations',
                'trainings',
                'organizations',
                'recognitions',
                'skills',
                'memberships',
                'references',
                'address',
                'quesionaires'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();

            $filename = "personal_data_sheet_{$request->employee}_" . date('Y-m-d') . ".pdf";

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            \Log::error('PDS print failed', [
                'employee_id' => $request->employee ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->serverErrorResponse('Failed to generate personal data sheet: ' . $e->getMessage());
        }
    }

    public function download($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Initialize empty arrays for address data to prevent file_get_contents errors
            $region_data = [];
            $province_data = [];
            $city_data = [];
            $brgy_data = [];

            $info =  DB::table('employees as b')
                ->leftJoin('name_prefixes as c', 'c.id', '=', 'b.name_prefix_id')
                ->leftJoin('name_suffixes as d', 'd.id', '=', 'b.name_suffix_id')
                ->leftJoin('genders as e', 'e.id', '=', 'b.gender_id')
                ->leftJoin('citizenships as f', 'f.id', '=', 'b.citizenship_id')
                ->leftJoin('civil_status as g', 'g.id', '=', 'b.civil_status_id')
                ->leftJoin('religions as h', 'h.id', '=', 'b.religion_id')
                ->leftJoin('blood_types as i', 'i.id', '=', 'b.blood_type_id')
                ->leftJoin('name_prefixes as cf', 'cf.id', '=', 'b.father_name_prefix_id')
                ->leftJoin('name_suffixes as df', 'df.id', '=', 'b.father_name_suffix_id')
                ->leftJoin('name_prefixes as cm', 'cm.id', '=', 'b.mother_name_prefix_id')
                ->leftJoin('name_suffixes as dm', 'dm.id', '=', 'b.mother_name_suffix_id')
                ->leftJoin('name_prefixes as cs', 'cs.id', '=', 'b.spouse_name_prefix_id')
                ->leftJoin('name_suffixes as ds', 'ds.id', '=', 'b.spouse_name_suffix_id')
                ->leftJoin('companies as j', 'j.id', '=', 'b.company_id')
                ->leftJoin('branches as k', 'k.id', '=', 'b.branch_id')
                ->leftJoin('departments as l', 'l.id', '=', 'b.department_id')
                ->leftJoin('employment_types as m', 'm.id', '=', 'b.employment_type_id')
                ->leftJoin('positions as n', 'n.id', '=', 'b.position_id')
                ->leftJoin('plantillas as o', 'o.id', '=', 'b.plantilla_id')
                ->leftJoin('payroll_intervals as p', 'p.id', '=', 'b.payroll_interval_id')
                ->leftJoin('salary_grades as r', 'r.id', '=', 'b.salary_grade_id')
                ->leftJoin('salary_steps as q', 'q.id', '=', 'b.salary_step_id')
                ->leftJoin('divisions as t', 't.id', '=', 'b.division_id')
                ->leftJoin('sections as u', 'u.id', '=', 'b.section_id')
                ->select(
                    'b.id',
                    'b.employee_no',
                    'b.access_no',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                            CONCAT(b.first_name,' ',b.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                        END as name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    'd.name as suffix',
                    'b.birth_place',
                    'b.birthdate',
                    'b.age',
                    'e.name as gender',
                    'b.height',
                    'b.weight',
                    'i.name as blood_type',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.email ELSE dbo.ufn_DecryptString(b.email,'$app_key') END as email"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.mobile_no ELSE dbo.ufn_DecryptString(b.mobile_no,'$app_key') END as mobile_no"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.telephone_no ELSE dbo.ufn_DecryptString(b.telephone_no,'$app_key') END as telephone_no"),
                    'f.name as citizenship',
                    'g.name as civil_status',
                    'h.name as religion',
                    'b.is_dual_citizent',
                    'b.by_birth',
                    'b.by_naturalization',
                    'b.indicate_country',
                    'b.ra_region',
                    'b.ra_province',
                    'b.ra_city',
                    'b.ra_house_no',
                    'b.ra_barangay',
                    'b.ra_street',
                    'b.ra_village',
                    'b.pa_house_no',
                    'b.pa_barangay',
                    'b.pa_street',
                    'b.pa_village',
                    'b.pa_region',
                    'b.pa_province',
                    'b.pa_city',
                    DB::raw("CONCAT(cf.name,' ',b.father_first_name,' ',substring(b.father_middle_name,1,1),'. ',b.father_last_name,' ',df.name) as father_name"),
                    DB::raw("CONCAT(cm.name,' ',b.mother_first_name,' ',substring(b.mother_middle_name,1,1),'. ',b.mother_last_name,' ',dm.name) as mother_name"),
                    DB::raw("CONCAT(cs.name,' ',b.spouse_first_name,' ',substring(b.spouse_middle_name,1,1),'. ',b.spouse_last_name,' ',ds.name) as spouse_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.father_first_name ELSE dbo.ufn_DecryptString(b.father_first_name,'$app_key') END as father_first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.father_middle_name ELSE dbo.ufn_DecryptString(b.father_middle_name,'$app_key') END as father_middle_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.father_last_name ELSE dbo.ufn_DecryptString(b.father_last_name,'$app_key') END as father_last_name"),
                    'df.name as father_suffix',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.mother_first_name ELSE dbo.ufn_DecryptString(b.mother_first_name,'$app_key') END as mother_first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.mother_middle_name ELSE dbo.ufn_DecryptString(b.mother_middle_name,'$app_key') END as mother_middle_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.mother_last_name ELSE dbo.ufn_DecryptString(b.mother_last_name,'$app_key') END as mother_last_name"),
                    'dm.name as mother_suffix',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.spouse_first_name ELSE dbo.ufn_DecryptString(b.spouse_first_name,'$app_key') END as spouse_first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.spouse_middle_name ELSE dbo.ufn_DecryptString(b.spouse_middle_name,'$app_key') END as spouse_middle_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.spouse_last_name ELSE dbo.ufn_DecryptString(b.spouse_last_name,'$app_key') END as spouse_last_name"),
                    // 'b.spouse_first_name',
                    // 'b.spouse_middle_name',
                    // 'b.spouse_last_name',
                    'ds.name as spouse_suffix',
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.spouse_occupation ELSE dbo.ufn_DecryptString(b.spouse_occupation,'$app_key') END as spouse_occupation"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.spouse_employer ELSE dbo.ufn_DecryptString(b.spouse_employer,'$app_key') END as spouse_employer"),
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.spouse_business_address ELSE dbo.ufn_DecryptString(b.spouse_business_address,'$app_key') END as spouse_business_address"),
                    'b.spouse_occupation',
                    'b.spouse_employer',
                    'b.spouse_business_address',
                    'b.spouse_mobile_no',
                    'j.name as company',
                    'k.name as branch',
                    'l.name as department',
                    't.name as division',
                    'u.name as section',
                    'b.work_schedule_id',
                    'm.name as employment_type',
                    'n.name as position',
                    'o.code as plantilla',
                    'r.name as grade',
                    'q.name as step',
                    'b.is_shifting',
                    'b.is_plantilla',
                    'b.is_employee',
                    'b.is_teaching',
                    'b.date_hired',
                    'b.tin_no',
                    'b.gsis_no',
                    'b.sss_no',
                    'b.pagibig_no',
                    'b.philhealth_no',
                    'b.salary',
                    'b.tax_amount',
                    'b.gsis_amount',
                    'b.sss_amount',
                    'b.pagibig_amount',
                    'b.philhealth_amount',
                    'p.name as payroll_interval',
                    'b.ra_postal_id',
                    'b.pa_postal_id'
                )
                ->where('b.id', $id)
                ->get();

            if ($info->isEmpty()) {
                return $this->errorResponse('Employee record not found or inactive.');
            }

            // Format employee data to show N/A for blank fields
            $info = $this->formatEmployeeData($info);

            $emp_id = $info[0]->id;

            // Initialize empty arrays for address data to prevent file_get_contents errors
            $region_data = [];
            $province_data = [];
            $city_data = [];
            $brgy_data = [];

            // TODO: Add proper region/province/city/barangay data handling
            // For now, using empty arrays to prevent file_get_contents errors

            $ra_region = collect($region_data)->where("regCode", $info[0]->ra_region)->all();
            $pa_region = collect($region_data)->where("regCode", $info[0]->pa_region)->all();

            // set collection for address
            $ra_province = collect($province_data)->where("provCode", $info[0]->ra_province)->all();
            $pa_province = collect($province_data)->where("provCode", $info[0]->pa_province)->all();

            $ra_city = collect($city_data)->where("citymunCode", $info[0]->ra_city)->all();
            $pa_city = collect($city_data)->where("citymunCode", $info[0]->pa_city)->all();

            $ra_brgy = collect($brgy_data)->where("brgyCode", $info[0]->ra_barangay)->all();
            $pa_brgy = collect($brgy_data)->where("brgyCode", $info[0]->pa_barangay)->all();

            // Initialize address variables
            $ra_region_id = null;
            $pa_region_id = null;
            $ra_province_id = null;
            $pa_province_id = null;
            $ra_city_id = null;
            $pa_city_id = null;
            $ra_brgy_id = null;
            $pa_brgy_id = null;

            $address = collect(array(
                'ra_region' => '',
                'pa_region' => '',
                'ra_province' => '',
                'pa_province' => '',
                'ra_city' => '',
                'pa_city' => '',
                'ra_brgy' => '',
                'pa_brgy' => ''
            ));
            // end of getting address data

            $children = DB::table('employee_children as a')
                ->select(
                    DB::raw("ROW_NUMBER() OVER(ORDER BY a.child_birthdate ASC) AS row"),
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(a.child_middlename,'') = '' THEN
                                CONCAT(a.child_name,' ',a.child_lastname)
                            ELSE
                                CONCAT(a.child_name,' ',upper(substring(a.child_middlename,1,1)),'. ',a.child_lastname)
                            END as name")
                )
                ->where('employee_id', $emp_id)
                ->orderBy('a.child_birthdate', 'asc')
                ->limit(13)
                ->get();

            // Format children data to show N/A for blank fields
            $children = $this->formatChildrenData($children);

            $educations_elem = DB::table('employee_educations')
                ->select(
                    '*',
                    'program as degree',
                    'from as period_from',
                    'to as period_to',
                    'graduated_year as year_graduated',
                    'honors as scholarship'
                )
                ->where(['employee_id' => $emp_id, 'academic_level_id' => 0])
                ->limit(1)
                ->get();

            $educations_sec = DB::table('employee_educations')
                ->select(
                    '*',
                    'program as degree',
                    'from as period_from',
                    'to as period_to',
                    'graduated_year as year_graduated',
                    'honors as scholarship'
                )
                ->where(['employee_id' => $emp_id, 'academic_level_id' => 1])
                ->limit(1)
                ->get();

            $educations_voc = DB::table('employee_educations')
                ->select(
                    '*',
                    'program as degree',
                    'from as period_from',
                    'to as period_to',
                    'graduated_year as year_graduated',
                    'honors as scholarship'
                )
                ->where(['employee_id' => $emp_id, 'academic_level_id' => 2])
                ->limit(1)
                ->get();

            $educations_col = DB::table('employee_educations')
                ->select(
                    '*',
                    'program as degree',
                    'from as period_from',
                    'to as period_to',
                    'graduated_year as year_graduated',
                    'honors as scholarship'
                )
                ->where(['employee_id' => $emp_id, 'academic_level_id' => 3])
                ->limit(1)
                ->get();

            $educations_grad = DB::table('employee_educations')
                ->select(
                    '*',
                    'program as degree',
                    'from as period_from',
                    'to as period_to',
                    'graduated_year as year_graduated',
                    'honors as scholarship'
                )
                ->where(['employee_id' => $emp_id, 'academic_level_id' => 4])
                ->limit(1)
                ->get();

            // Format education data to show N/A for blank fields
            $educations_elem = $this->formatEducationData($educations_elem);
            $educations_sec = $this->formatEducationData($educations_sec);
            $educations_voc = $this->formatEducationData($educations_voc);
            $educations_col = $this->formatEducationData($educations_col);
            $educations_grad = $this->formatEducationData($educations_grad);

            $employments = DB::table('employee_employment_records')->where('employee_id', $emp_id)->limit(28)->get();

            // Format employment data to show N/A for blank fields
            $employments = $this->formatEmploymentData($employments);

            $examinations = DB::table('employee_examinations as a')
                ->join('eligibilities as b', 'b.id', '=', 'a.eligibility_id')
                ->select('a.*', 'b.name as eligibility')
                ->where('employee_id', $emp_id)
                ->distinct()
                ->limit(14)->get();

            // Format examinations data to show N/A for blank fields
            $examinations = $this->formatExaminationsData($examinations);

            $trainings = DB::table('employee_trainings as a')
                ->join('learnings as b', 'b.id', '=', 'a.learning_id')
                ->select('a.*', 'b.name as learning')
                ->where('employee_id', $emp_id)->limit(22)->get();

            // Format trainings data to show N/A for blank fields
            $trainings = $this->formatTrainingsData($trainings);

            $organizations = DB::table('employee_organizations')->where('employee_id', $emp_id)->limit(7)->get();

            $recognitions = DB::table('employee_recognations')
                ->select(
                    DB::raw("ROW_NUMBER() OVER(ORDER BY recognation ASC) AS row"),
                    '*'
                )
                ->where('employee_id', $emp_id)
                ->limit(10)
                ->orderBy('recognation', 'asc')
                ->get();

            $skills = DB::table('employee_skills')
                ->select(
                    DB::raw("ROW_NUMBER() OVER(ORDER BY skill ASC) AS row"),
                    '*'
                )
                ->where('employee_id', $emp_id)
                ->limit(10)
                ->orderBy('skill', 'asc')
                ->get();

            $memberships = DB::table('employee_memberships')
                ->select(
                    DB::raw("ROW_NUMBER() OVER(ORDER BY membership ASC) AS row"),
                    '*'
                )
                ->where('employee_id', $emp_id)
                ->limit(10)
                ->orderBy('membership', 'asc')
                ->get();

            $references = DB::table('employee_references')->where('employee_id', $emp_id)->limit(3)->get();

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
                ->whereNotIn('a.id', function ($query) use ($emp_id) {
                    $query->select('question_id')->from('employee_pds_answers')->where('employee_id', $emp_id);
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
                ->where('a.employee_id', $emp_id)
                ->union($quesionaires_setup)
                ->orderBy('id', 'asc')
                ->get();

            if ($quesionaires->isEmpty()) {
                $quesionaires = DB::table('pds_questionaires')->orderBy('id', 'asc')->get();
            }

            $pdf = PDF::loadView('pds.pds_print_2025', compact(
                'info',
                'children',
                'educations_elem',
                'educations_sec',
                'educations_voc',
                'educations_col',
                'educations_grad',
                'employments',
                'examinations',
                'trainings',
                'organizations',
                'recognitions',
                'skills',
                'memberships',
                'references',
                'address',
                'quesionaires'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();

            $filename = strtoupper($info[0]->name) . ' - PDSFile.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            \Log::error('PDS download failed', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse('Failed to download personal data sheet: ' . $e->getMessage());
        }
    }
}
