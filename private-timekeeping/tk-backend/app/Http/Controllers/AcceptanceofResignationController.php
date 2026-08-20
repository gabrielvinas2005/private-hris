<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;

class AcceptanceofResignationController extends Controller
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

    public function index(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('employees')
                ->join('employee_offboardings', 'employee_offboardings.employee_id', '=', 'employees.id') // Ensuring the employee is off-boarded
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->select(
                    'employees.id',
                    'positions.name as position',
                    'departments.name as department',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                END as name"),
                )
                ->orderBy('employees.first_name', 'asc')
                ->where('employee_offboardings.reactivated_status_id', 0)
                ->paginate(10000);

            if (!$data || $data->isEmpty()) {
                return $this->errorResponse('Employee not found.', 404);
            }

            return $this->successResponse($data, 'Acceptance of resignation data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve acceptance of resignation data: ' . $e->getMessage());
        }
    }

    public function generatePdf(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'date' => 'required|date',
                'signatory' => 'required|string',
                'position1' => 'required|string',
                'received_date' => 'required|date',
                'received_signatory' => 'required|string',
                'employee' => 'required|exists:employees,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $employees = DB::table('employees as a')
                ->leftJoin('employee_offboardings as b', 'a.id', '=', 'b.employee_id')
                ->leftJoin('positions as c', 'a.position_id', '=', 'c.id')
                ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('ipcr as i', 'a.id', '=', 'i.employee_id')
                ->leftJoin('companies as cp', 'cp.id', '=', 'a.company_id')
                ->select(
                    'a.id',
                    'a.ra_region',
                    'a.ra_province',
                    'a.ra_city',
                    'a.ra_barangay',
                    'a.pa_region',
                    'a.pa_province',
                    'a.pa_city',
                    'a.pa_barangay',
                    'a.pa_house_no',
                    'a.pa_street',
                    'a.pa_village',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    'b.date_effectivity',
                    'c.name as position',
                    'd.name as department',
                    'i.adjectival_rating',
                    'e.name as name_prefix',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'cp.name as company'
                )
                ->where('a.id', $request->employee)
                ->orderBy('first_name', 'asc')
                ->get();

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

            $ra_region = collect($region_data)->where("regCode", $employees[0]->ra_region)->all();
            $pa_region = collect($region_data)->where("regCode", $employees[0]->pa_region)->all();

            // set collection for address
            $ra_province = collect($province_data)->where("provCode", $employees[0]->ra_province)->all();
            $pa_province = collect($province_data)->where("provCode", $employees[0]->pa_province)->all();

            $ra_city = collect($city_data)->where("citymunCode", $employees[0]->ra_city)->all();
            $pa_city = collect($city_data)->where("citymunCode", $employees[0]->pa_city)->all();

            $ra_brgy = collect($brgy_data)->where("brgyCode", $employees[0]->ra_barangay)->all();
            $pa_brgy = collect($brgy_data)->where("brgyCode", $employees[0]->pa_barangay)->all();

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

            $companies = DB::table('companies')->get();

            $signatories = [
                'signatory' => $request->signatory,
                'position1' => $request->position1,
                'received_date' => $request->received_date,
                'received_signatory' => $request->received_signatory,
                'date' => $request->date,
            ];

            $pdf = PDF::loadView(
                'acceptance_of_resignation.acceptance_of_resignation_print',
                compact('employees', 'signatories',
                'companies', 'address')
            )
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'acceptance_of_resignation_' . $request->employee . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate acceptance of resignation PDF: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        // Keep the original print method for backward compatibility
        return $this->generatePdf($request);
    }
}
