<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagIbigMDFController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(RTRIM(e.last_name), ', ', RTRIM(e.first_name), ' ', LEFT(RTRIM(ISNULL(e.middle_name,'')), 1))
                        ELSE
                            CONCAT(
                                RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), ', ',
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), ' ',
                                LEFT(RTRIM(ISNULL([dbo].[ufn_DecryptString](e.middle_name,'$app_key'),'')), 1)
                            )
                        END as name"),
                    'p.name as position',
                    'd.name as department'
                )
                ->where([
                    'e.active' => true,
                    'e.is_employee' => true,
                ])
                ->orderBy('e.last_name', 'asc')
                ->get();

            return $this->successResponse([
                'employees' => $employees,
            ], 'Pag-IBIG MDF data retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Pag-IBIG MDF Index Error: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve Pag-IBIG MDF data: ' . $e->getMessage());
        }
    }

    public function getEmployeeDetails($employeeId)
    {
        try {
            $app_key = env("APP_KEY", "");

            $employee = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                ->leftJoin('companies as c', 'c.id', '=', 'e.company_id')
                ->leftJoin('civil_status as cs', 'cs.id', '=', 'e.civil_status_id')
                ->leftJoin('genders as g', 'g.id', '=', 'e.gender_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    'e.company_id',
                    'e.date_hired',
                    'e.birthdate',
                    'e.mobile_no',
                    'e.email',
                    'e.birth_place',
                    'e.ra_house_no',
                    'e.ra_barangay',
                    'e.ra_street',
                    'e.ra_village',
                    'e.ra_city',
                    'e.ra_province',
                    'e.ra_region',
                    'e.ra_postal_id',
                    'e.pa_house_no',
                    'e.pa_barangay',
                    'e.pa_street',
                    'e.pa_village',
                    'e.pa_city',
                    'e.pa_province',
                    'e.pa_region',
                    'e.pa_postal_id',
                    'e.salary',
                    'cs.name as civil_status',
                    'g.name as gender',
                    'p.name as position',
                    'd.name as department',
                    'c.name as company_name',
                    'c.address as company_address',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            RTRIM(e.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                        END as last_name"),
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            RTRIM(e.first_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))
                        END as first_name"),
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            RTRIM(ISNULL(e.middle_name,''))
                        ELSE
                            RTRIM(ISNULL([dbo].[ufn_DecryptString](e.middle_name,'$app_key'),''))
                        END as middle_name")
                )
                ->where('e.id', $employeeId)
                ->where([
                    'e.active' => true,
                    'e.is_employee' => true,
                ])
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee not found', 404);
            }

            $additionalFields = [];
            try {
                $additional = DB::table('employees')
                    ->where('id', $employeeId)
                    ->select(
                        'tin_no',
                        'sss_no',
                        'gsis_no',
                        'height',
                        'weight',
                        'mother_first_name',
                        'mother_middle_name',
                        'mother_last_name',
                        'father_first_name',
                        'father_middle_name',
                        'father_last_name',
                        'spouse_first_name',
                        'spouse_middle_name',
                        'spouse_last_name'
                    )
                    ->first();
                
                if ($additional) {
                    $additionalFields = (array) $additional;
                }
            } catch (\Exception $e) {
                Log::info('Pag-IBIG MDF: Some additional fields not available: ' . $e->getMessage());
            }

            $permanentAddress = trim(implode(' ', array_filter([
                $employee->pa_house_no ?? '',
                $employee->pa_street ?? '',
                $employee->pa_barangay ?? '',
                $employee->pa_city ?? '',
                $employee->pa_province ?? ''
            ])));

            $presentAddress = trim(implode(' ', array_filter([
                $employee->ra_house_no ?? '',
                $employee->ra_street ?? '',
                $employee->ra_barangay ?? '',
                $employee->ra_city ?? '',
                $employee->ra_province ?? ''
            ])));

            return $this->successResponse([
                'last_name' => $employee->last_name ?? '',
                'first_name' => $employee->first_name ?? '',
                'middle_name' => $employee->middle_name ?? '',
                'name_extension' => '',
                'date_of_birth' => $employee->birthdate ? date('Y-m-d', strtotime($employee->birthdate)) : '',
                'place_of_birth' => $employee->birth_place ?? '',
                'sex' => strtolower($employee->gender ?? ''),
                'civil_status' => strtolower($employee->civil_status ?? ''),
                'citizenship' => 'Filipino',
                'tin_no' => $additionalFields['tin_no'] ?? '',
                'sss_no' => $additionalFields['sss_no'] ?? '',
                'gsis_no' => $additionalFields['gsis_no'] ?? '',
                'employee_no' => $employee->employee_no ?? '',
                'height' => $additionalFields['height'] ?? '',
                'weight' => $additionalFields['weight'] ?? '',
                'mobile_no' => $employee->mobile_no ?? '',
                'email' => $employee->email ?? '',
                'permanent_address_unit_room_floor' => '',
                'permanent_address_building_name' => '',
                'permanent_address_lot_block_phase_house' => $employee->pa_house_no ?? '',
                'permanent_address_street_name' => $employee->pa_street ?? '',
                'permanent_address_subdivision' => $employee->pa_village ?? '',
                'permanent_address_barangay' => $employee->pa_barangay ?? '',
                'permanent_address_municipality_city' => $employee->pa_city ?? '',
                'permanent_address_province_state_country' => $employee->pa_province ?? '',
                'permanent_address_zip' => $employee->pa_postal_id ?? '',
                'permanent_address' => $permanentAddress,
                'permanent_zip' => $employee->pa_postal_id ?? '',
                'present_address_unit_room_floor' => '',
                'present_address_building_name' => '',
                'present_address_lot_block_phase_house' => $employee->ra_house_no ?? '',
                'present_address_street_name' => $employee->ra_street ?? '',
                'present_address_subdivision' => $employee->ra_village ?? '',
                'present_address_barangay' => $employee->ra_barangay ?? '',
                'present_address_municipality_city' => $employee->ra_city ?? '',
                'present_address_province_state_country' => $employee->ra_province ?? '',
                'present_address_zip' => $employee->ra_postal_id ?? '',
                'present_address' => $presentAddress,
                'present_zip' => $employee->ra_postal_id ?? '',
                'employer_name' => $employee->company_name ?? '',
                'employer_address' => $employee->company_address ?? '',
                'employer_zip' => '',
                'employer_tin' => '',
                'monthly_compensation' => $employee->salary ?? '',
                'date_employed' => $employee->date_hired ? date('F, Y', strtotime($employee->date_hired)) : '',
                'father_last_name' => $additionalFields['father_last_name'] ?? '',
                'father_first_name' => $additionalFields['father_first_name'] ?? '',
                'father_middle_name' => $additionalFields['father_middle_name'] ?? '',
                'mother_last_name' => $additionalFields['mother_last_name'] ?? '',
                'mother_first_name' => $additionalFields['mother_first_name'] ?? '',
                'mother_middle_name' => $additionalFields['mother_middle_name'] ?? '',
                'spouse_last_name' => $additionalFields['spouse_last_name'] ?? '',
                'spouse_first_name' => $additionalFields['spouse_first_name'] ?? '',
                'spouse_middle_name' => $additionalFields['spouse_middle_name'] ?? '',
                'spouse_tin' => '',
                'dependent_1_last_name' => '',
                'dependent_1_first_name' => '',
                'dependent_1_middle_name' => '',
                'dependent_1_birthdate' => '',
            ], 'Employee details retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Pag-IBIG MDF Employee Details Error: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve employee details: ' . $e->getMessage());
        }
    }
}
