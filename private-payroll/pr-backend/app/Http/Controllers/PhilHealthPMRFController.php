<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PhilHealthPMRFController extends Controller
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

    /**
     * Get initial data for PhilHealth PMRF
     */
    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            // Get employees
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
            ], 'PhilHealth PMRF data retrieved successfully');
        } catch (\Exception $e) {
            Log::error('PhilHealth PMRF Index Error: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve PhilHealth PMRF data: ' . $e->getMessage());
        }
    }

    /**
     * Get employee details for PhilHealth PMRF auto-population
     */
    public function getEmployeeDetails($employeeId)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Get employee data with all relevant fields for PhilHealth PMRF
            $employee = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                ->leftJoin('companies as c', 'c.id', '=', 'e.company_id')
                ->leftJoin('civil_status as cs', 'cs.id', '=', 'e.civil_status_id')
                ->leftJoin('genders as g', 'g.id', '=', 'e.gender_id')
                ->leftJoin('employee_children as ec', 'ec.employee_id', '=', 'e.id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    'e.company_id',
                    'e.date_hired',
                    'e.birthdate',
                    'e.mobile_no',
                    'e.email',
                    'e.mother_first_name',
                    'e.mother_middle_name',
                    'e.mother_last_name',
                    'e.spouse_first_name',
                    'e.spouse_middle_name',
                    'e.spouse_last_name',
                    'ec.child_name',
                    'ec.child_birthdate',
                    'ec.child_middlename',
                    'ec.child_lastname',
                    'e.tin_no',
                    'e.philhealth_no',
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
                        END as middle_name"),
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(
                                ISNULL(e.ra_house_no,''), ' ',
                                ISNULL(e.ra_barangay,''), ' ',
                                ISNULL(e.ra_street,''), ' ',
                                ISNULL(e.ra_village,''), ' ',
                                ISNULL(e.ra_city,''), ' ',
                                ISNULL(e.ra_province,''), ' ',
                                ISNULL(e.ra_region,'')
                            )
                        ELSE
                            CONCAT(
                                ISNULL([dbo].[ufn_DecryptString](e.ra_house_no,'$app_key'),''), ' ',
                                ISNULL([dbo].[ufn_DecryptString](e.ra_barangay,'$app_key'),''), ' ',
                                ISNULL([dbo].[ufn_DecryptString](e.ra_street,'$app_key'),''), ' ',
                                ISNULL([dbo].[ufn_DecryptString](e.ra_village,'$app_key'),''), ' ',
                                ISNULL([dbo].[ufn_DecryptString](e.ra_city,'$app_key'),''), ' ',
                                ISNULL([dbo].[ufn_DecryptString](e.ra_province,'$app_key'),''), ' ',
                                ISNULL([dbo].[ufn_DecryptString](e.ra_region,'$app_key'),'')
                            )
                        END as employee_address")
                )
                ->where('e.id', $employeeId)
                ->where([
                    'e.active' => true,
                    'e.is_employee' => true,
                ])
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found');
            }

            // Format birthdate
            $dateOfBirth = null;
            if ($employee->birthdate) {
                $dateOfBirth = date('m-d-Y', strtotime($employee->birthdate));
            }

            return $this->successResponse([
                'employee_id' => $employee->id,
                'last_name' => $employee->last_name ?? '',
                'first_name' => $employee->first_name ?? '',
                'middle_name' => $employee->middle_name ?? '',
                'date_of_birth' => $dateOfBirth,
                'place_of_birth' => $employee->birth_place ?? '',
                'sex' => $employee->gender ?? '',
                'civil_status' => $employee->civil_status ?? '',
                'citizenship' => 'FILIPINO', // Default
                'philhealth_no' => $employee->philhealth_no ?? '',
                'tin_no' => $employee->tin_no ?? '',
                'mobile_number' => $employee->mobile_no ?? '',
                'email' => $employee->email ?? '',
                'permanent_address' => trim($employee->employee_address ?? ''),
                'permanent_zip' => $employee->ra_postal_id ?? '',
                'mailing_address' => '', // Will use permanent if same
                'mailing_zip' => $employee->pa_postal_id ?? '',
                'mother_first_name' => $employee->mother_first_name ?? '',
                'mother_middle_name' => $employee->mother_middle_name ?? '',
                'mother_last_name' => $employee->mother_last_name ?? '',
                'spouse_first_name' => $employee->spouse_first_name ?? '',
                'spouse_middle_name' => $employee->spouse_middle_name ?? '',
                'spouse_last_name' => $employee->spouse_last_name ?? '',
                'child_name' => $employee->child_name ?? '',
                'child_birthdate' => $employee->child_birthdate ?? '',
                'child_middlename' => $employee->child_middlename ?? '',
                'child_lastname' => $employee->child_lastname ?? '',
            ], 'Employee details retrieved successfully');
        } catch (\Exception $e) {
            Log::error('PhilHealth PMRF Get Employee Details Error: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve employee details: ' . $e->getMessage());
        }
    }

    /**
     * Generate PhilHealth PMRF PDF
     */
    public function print(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|integer|exists:employees,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            // Get employee data
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
                    'e.mobile_no',
                    'e.email',
                    'e.tin_no',
                    'e.philhealth_no',
                    'e.birthdate',
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
                        END as middle_name"),
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(
                                ISNULL(e.ra_house_no,''), ' ',
                                ISNULL(e.ra_barangay,''), ' ',
                                ISNULL(e.ra_street,''), ' ',
                                ISNULL(e.ra_village,''), ' ',
                                ISNULL(e.ra_city,''), ' ',
                                ISNULL(e.ra_province,''), ' ',
                                ISNULL(e.ra_region,'')
                            )
                        ELSE
                            CONCAT(
                                ISNULL([dbo].[ufn_DecryptString](e.ra_house_no,'$app_key'),''), ' ',
                                ISNULL([dbo].[ufn_DecryptString](e.ra_barangay,'$app_key'),''), ' ',
                                ISNULL([dbo].[ufn_DecryptString](e.ra_street,'$app_key'),''), ' ',
                                ISNULL([dbo].[ufn_DecryptString](e.ra_village,'$app_key'),''), ' ',
                                ISNULL([dbo].[ufn_DecryptString](e.ra_city,'$app_key'),''), ' ',
                                ISNULL([dbo].[ufn_DecryptString](e.ra_province,'$app_key'),''), ' ',
                                ISNULL([dbo].[ufn_DecryptString](e.ra_region,'$app_key'),'')
                            )
                        END as employee_address")
                )
                ->where('e.id', $request->employee_id)
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found');
            }

            // Prepare data for the form
            $formData = [
                'last_name' => strtoupper($employee->last_name ?? ''),
                'first_name' => strtoupper($employee->first_name ?? ''),
                'middle_name' => strtoupper($employee->middle_name ?? ''),
                'name_extension' => $request->name_extension ?? '',
                'date_of_birth' => $employee->birthdate ? date('m-d-Y', strtotime($employee->birthdate)) : '',
                'place_of_birth' => $request->place_of_birth ?? '',
                'sex' => strtoupper($employee->gender ?? ''),
                'civil_status' => strtoupper($employee->civil_status ?? ''),
                'citizenship' => strtoupper($request->citizenship ?? 'FILIPINO'),
                'philhealth_no' => $employee->philhealth_no ?? '',
                'tin_no' => $employee->tin_no ?? '',
                'mobile_number' => $employee->mobile_no ?? '',
                'email' => $employee->email ?? '',
                'home_phone' => $request->home_phone ?? '',
                'business_phone' => $request->business_phone ?? '',
                'permanent_address' => trim($employee->employee_address ?? ''),
                'permanent_zip' => $employee->ra_postal_id ?? '',
                'mailing_address' => $request->mailing_address ?? trim($employee->employee_address ?? ''),
                'mailing_zip' => $request->mailing_zip ?? $employee->pa_postal_id ?? '',
                'purpose' => $request->purpose ?? 'registration',
                'member_type' => $request->member_type ?? '',
                'philsys_id' => $request->philsys_id ?? '',
                'konsulta_provider' => $request->konsulta_provider ?? '',
            ];

            // Encode PhilHealth logo image to base64
            $logoPath = public_path('dist/img/PhilHealth-logo.png');
            $philhealthLogo = '';
            if (file_exists($logoPath)) {
                $philhealthLogo = base64_encode(file_get_contents($logoPath));
            } else {
                // Fallback: try frontend assets
                $logoPath = base_path('../pr-frontend/src/assets/PhilHealth-logo.png');
                if (file_exists($logoPath)) {
                    $philhealthLogo = base64_encode(file_get_contents($logoPath));
                }
            }

            // Generate PDF
            $pdf = PDF::loadView('govt_forms.PhilHealth_PMRF', compact('formData', 'philhealthLogo'))
                ->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper([0, 0, 612, 792], 'portrait'); // 8.5in x 11in

            $pdfContent = $pdf->output();

            $employeeName = strtoupper(trim($employee->last_name . ' ' . $employee->first_name));
            $filename = 'PhilHealth_PMRF_' . str_replace([' ', ','], '_', $employeeName) . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            Log::error('PhilHealth PMRF Print Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Request data: ' . json_encode($request->all()));
            return $this->serverErrorResponse('Failed to generate PhilHealth PMRF PDF: ' . $e->getMessage());
        }
    }
}

