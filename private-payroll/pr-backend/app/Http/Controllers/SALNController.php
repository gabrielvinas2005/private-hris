<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use App\SalnRealProperty;
use App\SalnLiability;
use App\SalnPersonalProperty;
use App\SalnBusinessInterest;
use App\SalnBusinessRelatives;
use Illuminate\Support\Facades\File;
use App\Traits\ApiResponse;

class SALNController extends Controller
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

    public function index($id)
    {
        $app_key = env("APP_KEY", "");

        $prefixes = DB::table('name_prefixes')->where('active', true)->orderBy('id', 'asc')->get();

        $emp_id_data = DB::table('users as a')
            ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
            ->selectRaw('case when b.id = null then 0 else b.id end as id')
            ->where('a.id', $id)
            ->get();

        // Initialize empty arrays for address data to prevent file_get_contents errors
        $region_data = [];
        $province_data = [];
        $city_data = [];
        $brgy_data = [];

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
                               CONCAT(c.name,' ',b.last_name,' ',b.first_name,' ',substring(b.middle_name,1,1),'. ',d.name)
                            ELSE
                               RTRIM(c.name)+'
                               '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+'
                               '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+'
                               '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))
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
                                RTRIM(ISNULL(cs.name,''))+'
                                '+RTRIM([dbo].[ufn_DecryptString](b.spouse_last_name,'$app_key'))+'
                                '+RTRIM([dbo].[ufn_DecryptString](b.spouse_first_name,'$app_key')+'
                                '+UPPER(substring([dbo].[ufn_DecryptString](b.spouse_middle_name,'$app_key'),1,1))+'
                                '+RTRIM(ISNULL(ds.name,'')))
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
                )
                ->where(['b.is_employee' => true, 'b.active' => true, 'b.id' => $emp_id_data[0]->id])
                ->get();

            $emp_id = isset($info[0]->id) ? $info[0]->id : 0;

            // get region data
            // $region_url = base_path('refregion.json');
            // $region_datos = file_get_contents($region_url);
            // $region_data = json_decode($region_datos, true);
            // $region_data = array_filter($region_data["RECORDS"]);
            $region_data = [];

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
        $realProperties = SalnRealProperty::where('user_id', auth()->id())->get();
        $personalProperties = SalnPersonalProperty::where('user_id', auth()->id())->get();
        $liabilities = SalnLiability::where('user_id', auth()->id())->get();
        $business = SalnBusinessInterest::where('user_id', auth()->id())->get();
        $relatives = SalnBusinessRelatives::where('user_id', auth()->id())->get();
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

        return $this->successResponse(compact(
            'info',
            'children',
            'prefixes',
            'educations',
            'service_records',
            'employments',
            'examinations',
            'trainings',
            'organizations',
            'recognitions',
            'skills',
            'memberships',
            'references',
            'address',
            'ipcr_details',
            'loans',
            'incomes',
            'with_access',
            'realProperties',
            'personalProperties',
            'liabilities',
            'business',
            'relatives',
            'days_remaining'
        ), 'SALN data retrieved successfully');
    }

    public function destroy($id)
    {
        try {
            $property = SalnRealProperty::find($id);

            if (!$property) {
                return $this->notFoundResponse('Property not found.');
            }

            $property->delete();

            return $this->successResponse(null, 'Property deleted successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete property: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $properties = $request->input('properties'); // This is now an array of arrays

            if (!is_array($properties)) {
                return $this->validationErrorResponse('No properties data received.');
            }

            foreach ($properties as $id => $property) {
                // Validate the entry is not empty
                if (!empty($property['description'])) {
                    $data = [
                        'user_id' => auth()->id(),
                        'description' => $property['description'],
                        'kind' => $property['kind'],
                        'exact_location' => $property['exact_location'],
                        'assessed_value' => $property['assessed_value'],
                        'current_fair_market_value' => $property['current_fair_market_value'],
                        'acquisition_year' => $property['acquisition_year'],
                        'acquisition_mode' => $property['acquisition_mode'],
                        'acquisition_cost' => $property['acquisition_cost'],
                    ];

                    // Check if it's an existing record
                    if (isset($property['id'])) {
                        $existing = SalnRealProperty::find($property['id']);
                        if ($existing) {
                            $existing->update($data);
                            continue;
                        }
                    }

                    // Otherwise, create new
                    SalnRealProperty::create($data);
                }
            }

            return $this->successResponse(null, 'Real properties saved successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save real properties: ' . $e->getMessage());
        }
    }
    public function destroypersonal($id)
    {
        try {
            $property = SalnPersonalProperty::find($id);

            if (!$property) {
                return $this->notFoundResponse('Personal property not found.');
            }

            $property->delete();

            return $this->successResponse(null, 'Personal property deleted successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete personal property: ' . $e->getMessage());
        }
    }


    public function storepersonal(Request $request)
    {
        try {
            $items = $request->input('personal_properties');

            if (!is_array($items)) {
                return $this->validationErrorResponse('No personal property data received.');
            }

            foreach ($items as $item) {
                if (!empty($item['description'])) {
                    $data = [
                        'user_id' => auth()->id(),
                        'description' => $item['description'],
                        'year_acquired' => $item['year_acquired'],
                        'acquisition_cost' => $item['acquisition_cost'],
                    ];

                    if (!empty($item['id'])) {
                        $existing = SalnPersonalProperty::find($item['id']);
                        if ($existing) {
                            $existing->update($data);
                            continue;
                        }
                    }

                    SalnPersonalProperty::create($data);
                }
            }

            return $this->successResponse(null, 'Personal properties saved successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save personal properties: ' . $e->getMessage());
        }
    }

    public function destroyliabilities($id)
    {
        try {
            $liability = SalnLiability::find($id);

            if (!$liability) {
                return $this->notFoundResponse('Liability not found.');
            }

            $liability->delete();

            return $this->successResponse(null, 'Liability deleted successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete liability: ' . $e->getMessage());
        }
    }

    public function storeliabilities(Request $request)
    {
        try {
            $items = $request->input('liabilities');

            if (!is_array($items)) {
                return $this->validationErrorResponse('No liability data received.');
            }

            foreach ($items as $item) {
                if (!empty($item['nature'])) {
                    $data = [
                        'user_id' => auth()->id(),
                        'nature' => $item['nature'],
                        'creditor_name' => $item['creditor_name'],
                        'outstanding_balance' => $item['outstanding_balance'],
                    ];

                    if (!empty($item['id'])) {
                        $existing = SalnLiability::find($item['id']);
                        if ($existing) {
                            $existing->update($data);
                            continue;
                        }
                    }

                    SalnLiability::create($data);
                }
            }

            return $this->successResponse(null, 'Liabilities saved successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save liabilities: ' . $e->getMessage());
        }
    }

    public function destroybusiness($id)
    {
        try {
            $business = SalnBusinessInterest::find($id);

            if (!$business) {
                return $this->notFoundResponse('Business Interest not found.');
            }

            $business->delete();

            return $this->successResponse(null, 'Business Interest deleted successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete business interest: ' . $e->getMessage());
        }
    }


    public function storebusiness(Request $request)
    {
        try {
            $items = $request->input('business');

            if (!is_array($items)) {
                return $this->validationErrorResponse('No business interest data received.');
            }

            foreach ($items as $item) {
                if (!empty($item['entity_name'])) {
                    $data = [
                        'user_id' => auth()->id(),
                        'entity_name' => $item['entity_name'],
                        'business_address' => $item['business_address'],
                        'nature_of_business' => $item['nature_of_business'],
                        'date_acquired' => $item['date_acquired'],
                    ];

                    if (!empty($item['id'])) {
                        $existing = SalnBusinessInterest::find($item['id']);
                        if ($existing) {
                            $existing->update($data);
                            continue;
                        }
                    }

                    SalnBusinessInterest::create($data);
                }
            }

            return $this->successResponse(null, 'Business Interests saved successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save business interests: ' . $e->getMessage());
        }
    }

    public function destroyrelatives($id)
    {
        try {
            $relative = SalnBusinessRelatives::find($id);

            if (!$relative) {
                return $this->notFoundResponse('Relative not found.');
            }

            $relative->delete();

            return $this->successResponse(null, 'Relative deleted successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete relative: ' . $e->getMessage());
        }
    }


    public function storerelatives(Request $request)
    {
        try {
            $items = $request->input('relatives');

            if (!is_array($items)) {
                return $this->validationErrorResponse('No relatives data received.');
            }

            foreach ($items as $item) {
                if (!empty($item['relatives_name'])) {
                    $data = [
                        'user_id' => auth()->id(),
                        'relatives_name' => $item['relatives_name'],
                        'relationship' => $item['relationship'],
                        'position' => $item['position'],
                        'office_address' => $item['office_address'],
                    ];

                    if (!empty($item['id'])) {
                        $existing = SalnBusinessRelatives::find($item['id']);
                        if ($existing) {
                            $existing->update($data);
                            continue;
                        }
                    }

                    SalnBusinessRelatives::create($data);
                }
            }

            return $this->successResponse(null, 'Business Relatives saved successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save business relatives: ' . $e->getMessage());
        }
    }
    public function download($id)
    {
        try {
            $app_key = env("APP_KEY", "");

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
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(cs.name,' ',b.spouse_first_name,' ',substring(b.spouse_middle_name,1,1),'. ',b.spouse_last_name,' ',df.name)
                            ELSE
                                RTRIM(ISNULL(cs.name,''))+'
                                '+RTRIM([dbo].[ufn_DecryptString](b.spouse_last_name,'$app_key'))+'
                                '+RTRIM([dbo].[ufn_DecryptString](b.spouse_first_name,'$app_key')+'
                                '+UPPER(substring([dbo].[ufn_DecryptString](b.spouse_middle_name,'$app_key'),1,1))+'
                                '+RTRIM(ISNULL(ds.name,'')))
                            END as spouse_name"),
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
                ->where(['b.is_employee' => true, 'b.active' => true, 'b.id' => $id])
                ->get();

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
                    db::raw("CASE WHEN ISNULL(a.child_middlename,'') = '' THEN
                                CONCAT(a.child_name,' ',a.child_lastname)
                            ELSE
                                CONCAT(a.child_name,' ',upper(substring(a.child_middlename,1,1)),'. ',a.child_lastname)
                            END as name")
                )
                ->where('employee_id', $emp_id)
                ->orderBy('a.child_birthdate', 'asc')
                ->limit(13)
                ->get();

            $educations_elem = DB::table('employee_educations')->where(['employee_id' => $emp_id, 'academic_level_id' => 0])->limit(1)->get();
            $educations_sec = DB::table('employee_educations')->where(['employee_id' => $emp_id, 'academic_level_id' => 1])->limit(1)->get();
            $educations_voc = DB::table('employee_educations')->where(['employee_id' => $emp_id, 'academic_level_id' => 2])->limit(1)->get();
            $educations_col = DB::table('employee_educations')->where(['employee_id' => $emp_id, 'academic_level_id' => 3])->limit(1)->get();
            $educations_grad = DB::table('employee_educations')->where(['employee_id' => $emp_id, 'academic_level_id' => 4])->limit(1)->get();

            $employments = DB::table('employee_employment_records')->where('employee_id', $emp_id)->limit(28)->get();
            $examinations = DB::table('employee_examinations as a')
                ->join('eligibilities as b', 'b.id', '=', 'a.eligibility_id')
                ->select('a.*', 'b.name as eligibility')
                ->where('employee_id', $emp_id)
                ->distinct()
                ->limit(14)->get();

            $trainings = DB::table('employee_trainings as a')
                ->join('learnings as b', 'b.id', '=', 'a.learning_id')
                ->select('a.*', 'b.name as learning')
                ->where('employee_id', $emp_id)->limit(22)->get();

            $organizations = DB::table('employee_organizations')->where('employee_id', $emp_id)->limit(7)->get();
            
            $recognitions = DB::table('employee_recognations')
                ->select(
                    DB::raw("ROW_NUMBER() OVER(ORDER BY recognation ASC) AS row"),
                    '*',
                )
                ->where('employee_id', $emp_id)
                ->limit(10)
                ->orderBy('recognation', 'asc')
                ->get();

            $skills = DB::table('employee_skills')
                ->select(
                    DB::raw("ROW_NUMBER() OVER(ORDER BY skill ASC) AS row"),
                    '*',
                )
                ->where('employee_id', $emp_id)
                ->limit(10)
                ->orderBy('skill', 'asc')
                ->get();

            $memberships = DB::table('employee_memberships')
                ->select(
                    DB::raw("ROW_NUMBER() OVER(ORDER BY membership ASC) AS row"),
                    '*',
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
            $realProperties = SalnRealProperty::where('user_id', $id)->get();
            $personalProperties = SalnPersonalProperty::where('user_id', $id)->get();
            $liabilities = SalnLiability::where('user_id', $id)->get();
            $business = SalnBusinessInterest::where('user_id', $id)->get();
            $relatives = SalnBusinessRelatives::where('user_id', auth()->id())->get();

            if ($quesionaires->isEmpty()) {
                $quesionaires = DB::table('pds_questionaires')->orderBy('id', 'asc')->get();
            }

            $pdf = PDF::loadView('SALN.saln_print_new', compact(
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
                'realProperties',
                'personalProperties',
                'liabilities',
                'business',
                'relatives',
                'skills',
                'memberships',
                'references',
                'address',
                'quesionaires'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);
            $filename = 'saln_' . $id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate SALN PDF: ' . $e->getMessage());
        }
    }
}
