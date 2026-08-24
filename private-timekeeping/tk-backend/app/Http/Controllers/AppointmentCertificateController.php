<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \NumberFormatter;
use Svg\Tag\Rect;
use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;

class AppointmentCertificateController extends Controller
{
    use ApiResponse, GeneratesPdf;
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

            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->join('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employee_promotions', 'employees.id', '=', 'employee_promotions.employee_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->leftJoin('promotion_natures', 'promotion_natures.id', '=', 'employee_promotions.nature_of_appointment_id')
                ->select(
                    'employees.photo',
                    DB::raw("CASE WHEN ISNULL(employee_promotions.id,0) = 0 THEN employees.id ELSE employee_promotions.id END as id"),
                    'employees.employee_no',
                    'employees.email',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',substring(employees.middle_name,1,1),'. ',employees.last_name,' - ',positions.name,' - ',promotion_natures.name,' - ',employee_promotions.date_of_effectivity)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+UPPER(SUBSTRING([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))+' - '+RTRIM(ISNULL(positions.name,''))+' - '+RTRIM(ISNULL(promotion_natures.name,'Original'))+' - '+RTRIM(ISNULL(employee_promotions.date_of_effectivity,''))
                                END as name"),
                )
                ->orderBy('employees.first_name', 'asc')
                ->where([
                    'employees.active' => true,
                    'employees.is_employee' => true,
                ])
                ->get();

            return $this->successResponse($data, 'Appointment certificates retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve appointment certificates: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.', 400);
            }

            $appointments = DB::table('employees as b')
                ->leftJoin('employee_promotions as a', 'a.employee_id', '=', 'b.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                ->leftJoin('genders as e', 'b.gender_id', '=', 'e.id')
                ->leftJoin('name_prefixes as f', 'b.name_prefix_id', '=', 'f.id')
                ->leftJoin('promotion_natures as g', 'g.id', '=', 'a.nature_of_appointment_id')
                ->leftJoin('employment_types as h', 'h.id', '=', 'b.employment_type_id')
                ->leftJoin('plantillas as j', 'b.id', '=', 'j.employee_id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                    'c.name as position',
                    'd.name as department',
                    'b.ra_region',
                    'b.ra_province',
                    'b.ra_city',
                    'b.ra_barangay',
                    'b.pa_region',
                    'b.pa_province',
                    'b.pa_city',
                    'b.pa_barangay',
                    'b.salary_grade_id',
                    'b.salary_step_id',
                    'b.salary',
                    'g.name as nature',
                    'h.name as employment_type',
                    'j.code'
                )
                ->Where('b.id', $request->employee)
                ->orWhere('a.id', $request->employee)
                ->get();

            if ($appointments->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
            $salary_word = $f->format(isset($appointments[0]->salary) ? $appointments[0]->salary : 0);

            // get region data
            $region_url = 'refregion.json';
            $region_datos = file_get_contents($region_url);
            $region_data = json_decode($region_datos, true);
            $region_data = array_filter($region_data["RECORDS"]);

            // get province data
            $province_url = 'refprovince.json';
            $province_datos = file_get_contents($province_url);
            $province_data = json_decode($province_datos, true);
            $province_data = array_filter($province_data["RECORDS"]);

            // get city data
            $city_url = 'refcitymun.json';
            $city_datos = file_get_contents($city_url);
            $city_data = json_decode($city_datos, true);
            $city_data = array_filter($city_data["RECORDS"]);

            // get barangay data
            $brgy_url = 'refbrgy.json';
            $brgy__datos = file_get_contents($brgy_url);
            $brgy_data = json_decode($brgy__datos, true);
            $brgy_data = array_filter($brgy_data["RECORDS"]);

            $ra_region = collect($region_data)->where("regCode", isset($appointments[0]->ra_region) ? $appointments[0]->ra_region : '')->all();
            $pa_region = collect($region_data)->where("regCode", isset($appointments[0]->pa_region) ? $appointments[0]->pa_region : '')->all();

            // set collection for address
            $ra_province = collect($province_data)->where("provCode", isset($appointments[0]->ra_province) ? $appointments[0]->ra_province : '')->all();
            $pa_province = collect($province_data)->where("provCode", isset($appointments[0]->pa_province) ? $appointments[0]->pa_province : '')->all();

            $ra_city = collect($city_data)->where("citymunCode", isset($appointments[0]->ra_city) ? $appointments[0]->ra_city : '')->all();
            $pa_city = collect($city_data)->where("citymunCode", isset($appointments[0]->pa_city) ? $appointments[0]->pa_city : '')->all();

            $ra_brgy = collect($brgy_data)->where("brgyCode", isset($appointments[0]->ra_barangay) ? $appointments[0]->ra_barangay : '')->all();
            $pa_brgy = collect($brgy_data)->where("brgyCode", isset($appointments[0]->pa_barangay) ? $appointments[0]->pa_barangay : '')->all();

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

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position,
                'vice' => $request->vice,
                'who' => $request->who,
                'note' => $request->note,
                'cs_date' => $request->cs_date,
                'hrmo' => $request->hrmo,
                'hrmpsb' => $request->hrmpsb,
                'publish_at' => $request->publish_at,
                'publish_from' => $request->publish_from,
                'publish_to' => $request->publish_to,
                'posted_at' => $request->posted_at,
                'posted_from' => $request->posted_from,
                'posted_to' => $request->posted_to,
                'started_on' => $request->started_on,
                'deliberation_on' => $request->deliberation_on,
            );

            $pdf = PDF::loadView('appointment_certificates.appointment_certificate_print', compact(
                'appointments',
                'signatories',
                'address',
                'salary_word'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            
            // Generate PDF content as base64 for API response
            $pdfContent = $pdf->output();
            $base64Content = base64_encode($pdfContent);
            
            $filename = 'appointment_certificate_' . $request->employee . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate appointment certificate: ' . $e->getMessage());
        }
    }
}
