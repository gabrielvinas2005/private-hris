<?php

namespace App\Http\Controllers;

use Auth;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class EmployeeFileController extends Controller
{
    use ApiResponse;

    public function index($id = null)
    {
        try {
            $app_key = env("APP_KEY", "");

            // If no ID provided, use a default ID for testing
            if ($id === null) {
                $id = 1; // Default user ID for testing
            }

            $emp_id_data = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->selectRaw('case when b.id = null then 0 else b.id end as id')
                ->where('a.id', $id)
                ->get();

            if ($emp_id_data->isEmpty()) {
                $dummy_info =
                    array(
                        'id' => 0,
                        'employee_no' => '',
                        'access_no' => '',
                        'photo' => '',
                        'name' => '',
                        'birth_place' => '',
                        'birthdate' => '',
                        'age' => null,
                        'gender' => '',
                        'height' => null,
                        'weight' => null,
                        'blood_type' => '',
                        'email' => '',
                        'mobile_no' => '',
                        'telephone_no' => '',
                        'citizenship' => '',
                        'civil_status' => '',
                        'religion' => '',
                        'is_dual_citizent' => '',
                        'by_birth' => null,
                        'by_naturalization' => null,
                        'indicate_country' => '',
                        'ra_region' => '',
                        'ra_province' => '',
                        'ra_city' => '',
                        'ra_house_no' => '',
                        'ra_barangay' => '',
                        'ra_street' => '',
                        'ra_village' => '',
                        'pa_house_no' => '',
                        'pa_barangay' => '',
                        'pa_street' => '',
                        'pa_village' => '',
                        'pa_region' => '',
                        'pa_province' => '',
                        'pa_city' => '',
                        'father_name' => '',
                        'mother_name' => '',
                        'spouse_name' => '',
                        'spouse_occupation' => '',
                        'spouse_employer' => '',
                        'spouse_business_address' => '',
                        'company' => '',
                        'branch' => '',
                        'department' => '',
                        'division' => '',
                        'section' => '',
                        'work_schedule_id' => '',
                        'employment_type' => '',
                        'position' => '',
                        'plantilla' => '',
                        'grade' => '',
                        'step' => '',
                        'is_shifting' => null,
                        'is_plantilla' => null,
                        'is_employee' => null,
                        'is_teaching' => null,
                        'date_hired' => null,
                        'tin_no' => '',
                        'gsis_no' => '',
                        'sss_no' => '',
                        'pagibig_no' => '',
                        'philhealth_no' => '',
                        'salary' => null,
                        'tax_amount' => null,
                        'gsis_amount' => null,
                        'sss_amount' => null,
                        'pagibig_amount' => null,
                        'philhealth_amount' => null,
                        'payroll_interval' => 0,
                        'updated_at' => null,
                        'is_hold' => false,
                    );
                $info = (object) $dummy_info;
                $info = collect([$info]);
                $emp_id = 0;

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
            } else {

                $info = DB::table('users as a')
                    ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
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
                                   CONCAT(c.name,' ',b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name,' ',d.name)
                                ELSE
                                   RTRIM(c.name)+' '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                                END as name"),
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
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(cf.name,' ',b.father_first_name,' ',substring(b.father_middle_name,1,1),'. ',b.father_last_name,' ',df.name)
                                ELSE
                                    RTRIM(ISNULL(cf.name,''))+' '+RTRIM([dbo].[ufn_DecryptString](b.father_first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.father_middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.father_last_name,'$app_key')+' '+RTRIM(ISNULL(df.name,''))) 
                                END as father_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(cm.name,' ',b.mother_first_name,' ',substring(b.mother_middle_name,1,1),'. ',b.mother_last_name,' ',df.name)
                                ELSE
                                    RTRIM(ISNULL(cm.name,''))+' '+RTRIM([dbo].[ufn_DecryptString](b.mother_first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.mother_middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.mother_last_name,'$app_key')+' '+RTRIM(ISNULL(dm.name,''))) 
                                END as mother_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(cs.name,' ',b.spouse_first_name,' ',substring(b.spouse_middle_name,1,1),'. ',b.spouse_last_name,' ',df.name)
                                ELSE
                                    RTRIM(ISNULL(cs.name,''))+' '+RTRIM([dbo].[ufn_DecryptString](b.spouse_first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.spouse_middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.spouse_last_name,'$app_key')+' '+RTRIM(ISNULL(ds.name,''))) 
                                END as spouse_name"),
                        'b.spouse_occupation',
                        'b.spouse_employer',
                        'b.spouse_business_address',
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
                        'b.updated_at',
                        'is_hold'
                    )
                    ->where(['b.is_employee' => true, 'b.active' => true, 'b.id' => $emp_id_data[0]->id])
                    ->get();

                $emp_id = isset($info[0]->id) ? $info[0]->id : 0;

                // get region data
                $region_url = base_path('refregion.json');
                $region_datos = file_get_contents($region_url);
                $region_data = json_decode($region_datos, true);
                $region_data = array_filter($region_data["RECORDS"]);

                // get province data
                $province_url = base_path('refprovince.json');
                $province_datos = file_get_contents($province_url);
                $province_data = json_decode($province_datos, true);
                $province_data = array_filter($province_data["RECORDS"]);

                // get city data
                $city_url = base_path('refcitymun.json');
                $city_datos = file_get_contents($city_url);
                $city_data = json_decode($city_datos, true);
                $city_data = array_filter($city_data["RECORDS"]);

                // get barangay data
                $brgy_url = base_path('refbrgy.json');
                $brgy__datos = file_get_contents($brgy_url);
                $brgy_data = json_decode($brgy__datos, true);
                $brgy_data = array_filter($brgy_data["RECORDS"]);

                $ra_region = collect($region_data)->where("regCode", $info[0]->ra_region)->all();
                $pa_region = collect($region_data)->where("regCode", $info[0]->pa_region)->all();

                // set collection for address
                $ra_province = collect($province_data)->where("provCode", $info[0]->ra_province)->all();
                $pa_province = collect($province_data)->where("provCode", $info[0]->pa_province)->all();

                $ra_city = collect($city_data)->where("citymunCode", $info[0]->ra_city)->all();
                $pa_city = collect($city_data)->where("citymunCode", $info[0]->pa_city)->all();

                $ra_brgy = collect($brgy_data)->where("brgyCode", $info[0]->ra_barangay)->all();
                $pa_brgy = collect($brgy_data)->where("brgyCode", $info[0]->pa_barangay)->all();

                // loop address to get indexes
                for ($i = 0; $i <= count($region_data); $i++) {
                    if (isset($ra_region[$i]['regDesc'])) {
                        $ra_region_id = $i;
                    }
                }

                for ($i = 0; $i <= count($region_data); $i++) {
                    if (isset($pa_region[$i]['regDesc'])) {
                        $pa_region_id = $i;
                    }
                }

                for ($i = 0; $i <= count($province_data); $i++) {
                    if (isset($ra_province[$i]['provDesc'])) {
                        $ra_province_id = $i;
                    }
                }

                for ($i = 0; $i <= count($province_data); $i++) {
                    if (isset($pa_province[$i]['provDesc'])) {
                        $pa_province_id = $i;
                    }
                }

                for ($i = 0; $i <= count($city_data); $i++) {
                    if (isset($ra_city[$i]['citymunDesc'])) {
                        $ra_city_id = $i;
                    }
                }

                for ($i = 0; $i <= count($city_data); $i++) {
                    if (isset($pa_city[$i]['citymunDesc'])) {
                        $pa_city_id = $i;
                    }
                }

                for ($i = 0; $i <= count($brgy_data); $i++) {
                    if (isset($ra_brgy[$i]['brgyDesc'])) {
                        $ra_brgy_id = $i;
                    }
                }

                for ($i = 0; $i <= count($brgy_data); $i++) {
                    if (isset($pa_brgy[$i]['brgyDesc'])) {
                        $pa_brgy_id = $i;
                    }
                }

                $address = collect(array(
                    'ra_region' => !isset($ra_region_id) ? '' : $ra_region[$ra_region_id]['regDesc'],
                    'pa_region' => !isset($pa_region_id) ? '' : $pa_region[$pa_region_id]['regDesc'],
                    'ra_province' => !isset($ra_province_id) ? '' : $ra_province[$ra_province_id]['provDesc'],
                    'pa_province' => !isset($pa_province_id) ? '' : $pa_province[$pa_province_id]['provDesc'],
                    'ra_city' => !isset($ra_city_id) ? '' : $ra_city[$ra_city_id]['citymunDesc'],
                    'pa_city' => !isset($pa_city_id) ? '' : $pa_city[$pa_city_id]['citymunDesc'],
                    'ra_brgy' => !isset($ra_brgy_id) ? '' : $ra_brgy[$ra_brgy_id]['brgyDesc'],
                    'pa_brgy' => !isset($pa_brgy_id) ? '' : $pa_brgy[$pa_brgy_id]['brgyDesc']
                ));
                // end of getting address data
            }

            $children = DB::table('employee_children')->where('employee_id', $emp_id)->get();
            $educations = DB::table('employee_educations')->where('employee_id', $emp_id)->orderBy('employee_educations.graduated_year', 'desc')->get();
            $service_records = DB::table('service_records')->where('employee_id', $emp_id)->get();
            $employments = DB::table('employee_employment_records')->where('employee_id', $emp_id)->get();
            $examinations = DB::table('employee_examinations as a')
                ->join('eligibilities as b', 'b.id', '=', 'a.eligibility_id')
                ->select('a.*', 'b.name as eligibility')
                ->where('employee_id', $emp_id)->get();
            $trainings = DB::table('employee_trainings as a')
                ->join('learnings as b', 'b.id', '=', 'a.learning_id')
                ->select('a.*', 'b.name as learning')
                ->where('employee_id', $emp_id)->get();
            $organizations = DB::table('employee_organizations')->where('employee_id', $emp_id)->get();
            $recognitions = DB::table('employee_recognations')->where('employee_id', $emp_id)->get();
            $skills = DB::table('employee_skills')->where('employee_id', $emp_id)->get();
            $memberships = DB::table('employee_memberships')->where('employee_id', $emp_id)->get();
            $references = DB::table('employee_references')->where('employee_id', $emp_id)->get();
            $ipcr_details = DB::table('ipcr_details')
                ->where('employee_id', $emp_id)
                ->where('rating', '>', 0)
                ->get();

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

            $with_access = DB::table('update_201_schedule')->where('date_to', '>=', now())->where('date_from', '<=', now())->count();

            $schedule = DB::table('update_201_schedule')
                ->select(
                    DB::raw('DATEDIFF(D,getdate(),date_to) as remaining_days')
                )
                ->where('date_to', '>=', now())
                ->where('date_from', '<=', now())
                ->get();

            if ($schedule->isNotEmpty()) {
                $days_remaining = $schedule[0]->remaining_days;
            } else {
                $days_remaining = 0;
            }

            return $this->successResponse([
                'info' => $info,
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
                'address' => $address,
                'ipcr_details' => $ipcr_details,
                'loans' => $loans,
                'incomes' => $incomes,
                'with_access' => $with_access,
                'days_remaining' => $days_remaining
            ], 'Employee file data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee file data: ' . $e->getMessage());
        }
    }

    public function ipcr_view($id)
    {
        try {
            $data = DB::table('ipcr_details')->find($id);

            if (!$data) {
                return $this->notFoundResponse('IPCR details not found');
            }

            if (!File::exists(public_path('/storage/ipcr_file/' . $data->employee_id . '_' . $data->attachment))) {
                return $this->notFoundResponse('IPCR File not found or no longer exists');
            }

            return $this->successResponse($data, 'IPCR view data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve IPCR view data: ' . $e->getMessage());
        }
    }

    public function export()
    {
        try {
            $app_key = env("APP_KEY", "");

            if (Auth::user()->access_all_branches) {
                $info = DB::table('employees as b')
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
                    ->select(
                        'b.id',
                        'b.employee_no',
                        'b.access_no',
                        'b.photo',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                   CONCAT(c.name,' ',b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name,' ',d.name)
                                ELSE
                                    RTRIM(c.name)+' '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                                END as name"),
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
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(cf.name,' ',b.father_first_name,' ',substring(b.father_middle_name,1,1),'. ',b.father_last_name,' ',df.name)
                                ELSE
                                    RTRIM(cf.name)+' '+RTRIM([dbo].[ufn_DecryptString](b.father_first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.father_middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.father_last_name,'$app_key')+' '+df.name) 
                                END as father_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(cm.name,' ',b.mother_first_name,' ',substring(b.mother_middle_name,1,1),'. ',b.mother_last_name,' ',df.name)
                                ELSE
                                    RTRIM(cm.name)+' '+RTRIM([dbo].[ufn_DecryptString](b.mother_first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.mother_middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.mother_last_name,'$app_key')+' '+dm.name) 
                                END as mother_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(cs.name,' ',b.spouse_first_name,' ',substring(b.spouse_middle_name,1,1),'. ',b.spouse_last_name,' ',df.name)
                                ELSE
                                    RTRIM(cs.name)+' '+RTRIM([dbo].[ufn_DecryptString](b.spouse_first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.spouse_middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.spouse_last_name,'$app_key')+' '+ds.name) 
                                END as spouse_name"),
                        'b.spouse_occupation',
                        'b.spouse_employer',
                        'b.spouse_business_address',
                        'j.name as company',
                        'k.name as branch',
                        'l.name as department',
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
                        'b.active',
                        'b.ra_postal_id',
                        'b.pa_postal_id'
                    )
                    ->where([
                        'b.is_employee' => true,
                        'b.active' => true,
                    ])
                    ->orderBy('b.last_name', 'asc')
                    ->get();
            } else {
                $user_branch_id = DB::table('users as a')
                    ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                    ->select('b.branch_id')
                    ->where('a.id', Auth::user()->id)
                    ->get();

                $info = DB::table('employees as b')
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
                    ->select(
                        'b.id',
                        'b.employee_no',
                        'b.access_no',
                        'b.photo',
                        DB::raw("CONCAT(c.name,' ',b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name,' ',d.name) as name"),
                        'b.birth_place',
                        'b.birthdate',
                        'b.age',
                        'e.name as gender',
                        'b.height',
                        'b.weight',
                        'i.name as blood_type',
                        'b.email',
                        'b.mobile_no',
                        'b.telephone_no',
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
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(cf.name,' ',b.father_first_name,' ',substring(b.father_middle_name,1,1),'. ',b.father_last_name,' ',df.name)
                                ELSE
                                    RTRIM(cf.name)+' '+RTRIM([dbo].[ufn_DecryptString](b.father_first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.father_middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.father_last_name,'$app_key')+' '+df.name) 
                                END as father_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(cm.name,' ',b.mother_first_name,' ',substring(b.mother_middle_name,1,1),'. ',b.mother_last_name,' ',df.name)
                                ELSE
                                    RTRIM(cm.name)+' '+RTRIM([dbo].[ufn_DecryptString](b.mother_first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.mother_middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.mother_last_name,'$app_key')+' '+dm.name) 
                                END as mother_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(cs.name,' ',b.spouse_first_name,' ',substring(b.spouse_middle_name,1,1),'. ',b.spouse_last_name,' ',df.name)
                                ELSE
                                    RTRIM(cs.name)+' '+RTRIM([dbo].[ufn_DecryptString](b.spouse_first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.spouse_middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.spouse_last_name,'$app_key')+' '+ds.name) 
                                END as spouse_name"),
                        'b.spouse_occupation',
                        'b.spouse_employer',
                        'b.spouse_business_address',
                        'j.name as company',
                        'k.name as branch',
                        'l.name as department',
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
                        'b.active'
                    )
                    ->where([
                        'b.is_employee' => true,
                        'b.active' => true,
                        'b.branch_id' => $user_branch_id[0]->branch_id
                    ])
                    ->orderBy('b.last_name', 'asc')
                    ->get();
            }

            return $this->successResponse($info, 'Export employee data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to export employee data: ' . $e->getMessage());
        }
    }

    public function update($id)
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
            $blood_types = DB::table('blood_types')->orderBy('id', 'asc')->get();
            $payroll_intervals = DB::table('payroll_intervals')->where('active', true)->orderBy('id', 'asc')->get();
            $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();
            $learnings = DB::table('learnings')->where('active', true)->orderBy('name', 'asc')->get();
            $divisions = DB::table('divisions')->where('active', true)->orderBy('name', 'asc')->get();
            $sections = DB::table('sections')->where('active', true)->orderBy('name', 'asc')->get();

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
                        'is_hold' => false,
                        'hold_remarks' => '',
                        'is_employee' => true,
                        'is_teaching' => false,
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
                        'is_hold',
                        'hold_remarks',
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
                        'is_hold',
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

            $is_employee_portal = 1;

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
                'loans' => $loans,
                'incomes' => $incomes,
                'divisions' => $divisions,
                'sections' => $sections,
                'dependents' => $dependents,
                'documents' => $documents,
                'salary_grade_steps' => $salary_grade_steps,
                'quesionaires' => $quesionaires,
                'is_employee_portal' => $is_employee_portal,
                'employee_documents' => $employee_documents,
                'document_types' => $document_types
            ], 'Employee file update data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee file update data: ' . $e->getMessage());
        }
    }
}
