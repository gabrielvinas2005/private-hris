<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OathOfOfficeController extends Controller
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
                                    CONCAT(employees.first_name,' ',substring(employees.middle_name,1,1),'. ',employees.last_name,' - ',positions.name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')+' - '+RTRIM(positions.name)) 
                                END as name"),
                )
                ->orderBy('employees.first_name', 'asc')
                ->where(['employees.is_employee' => true, 'employees.active' => true])
                ->paginate(10000);

            return $this->successResponse($data, 'Oath of office records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve oath of office records: ' . $e->getMessage());
        }
    }

    public function generatePdf(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee' => 'required|exists:employees,id',
                'signatory' => 'nullable|string',
                'position' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $oath_of_offices =
                DB::table('employees as a')
                ->leftJoin('positions as c', 'a.position_id', '=', 'c.id')
                ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
                ->leftJoin('genders as e', 'a.gender_id', '=', 'e.id')
                ->leftJoin('name_prefixes as f', 'a.name_prefix_id', '=', 'f.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                    'a.ra_region',
                    'a.ra_province',
                    'a.ra_city',
                    'a.ra_barangay',
                    'a.pa_region',
                    'a.pa_province',
                    'a.pa_city',
                    'a.pa_barangay',
                    'c.name as position',
                    'd.name as department',
                    'e.name as gender',
                    DB::raw("CONCAT(f.name,' ',a.last_name) as name_sig"),
                )
                ->where('a.id', $request->employee)
                ->get();

            if ($oath_of_offices->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

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

            $ra_region = collect($region_data)->where("regCode", $oath_of_offices[0]->ra_region)->all();
            $pa_region = collect($region_data)->where("regCode", $oath_of_offices[0]->pa_region)->all();

            // set collection for address
            $ra_province = collect($province_data)->where("provCode", $oath_of_offices[0]->ra_province)->all();
            $pa_province = collect($province_data)->where("provCode", $oath_of_offices[0]->pa_province)->all();

            $ra_city = collect($city_data)->where("citymunCode", $oath_of_offices[0]->ra_city)->all();
            $pa_city = collect($city_data)->where("citymunCode", $oath_of_offices[0]->pa_city)->all();

            $ra_brgy = collect($brgy_data)->where("brgyCode", $oath_of_offices[0]->ra_barangay)->all();
            $pa_brgy = collect($brgy_data)->where("brgyCode", $oath_of_offices[0]->pa_barangay)->all();

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

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position,
            );

            $pdf = PDF::loadView('oath_of_office.oath_of_office_print', compact('oath_of_offices', 'signatories', 'address'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'oath_of_office_' . $oath_of_offices[0]->name . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate oath of office PDF: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        // Keep the original print method for backward compatibility
        return $this->generatePdf($request);
    }
}
