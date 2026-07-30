<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Paragraph;
use PhpOffice\PhpWord\Shared\Converter;


class GSISMemberInfoController extends Controller
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
     * Get initial data for GSIS Membership Information Sheet
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
            ], 'GSIS Membership Information Sheet data retrieved successfully');
        } catch (\Exception $e) {
            Log::error('GSIS Member Info Index Error: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve GSIS Member Info data: ' . $e->getMessage());
        }
    }

    /**
     * Get employee details for GSIS Membership Information Sheet auto-population
     */
    public function getEmployeeDetails($employeeId)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Get employee data with all relevant fields
            $employee = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                ->leftJoin('companies as c', 'c.id', '=', 'e.company_id')
                ->leftJoin('branches as b', 'b.id', '=', 'e.branch_id')
                ->leftJoin('civil_status as cs', 'cs.id', '=', 'e.civil_status_id')
                ->leftJoin('genders as g', 'g.id', '=', 'e.gender_id')
                ->leftJoin('employment_types as et', 'et.id', '=', 'e.employment_type_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    'e.company_id',
                    'e.date_hired',
                    'e.birthdate as birth_date',
                    'e.mobile_no',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.email ELSE dbo.ufn_DecryptString(e.email,'$app_key') END as email"),
                    'e.salary',
                    'cs.name as civil_status',
                    'g.name as gender',
                    'p.name as position',
                    'd.name as department',
                    'et.name as employment_type',
                    'c.name as company_name',
                    'b.name as branch_name',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.last_name
                        ELSE RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                        END as last_name"),
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.first_name
                        ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))
                        END as first_name"),
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.middle_name
                        ELSE RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key'))
                        END as middle_name"),
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.birth_place
                        ELSE RTRIM([dbo].[ufn_DecryptString](e.birth_place,'$app_key'))
                        END as birth_place"),
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

            // Format dates
            $dateOfBirth = null;
            if ($employee->birth_date) {
                $dateOfBirth = date('m/d/Y', strtotime($employee->birth_date));
            }

            $dateOfAppointment = null;
            if ($employee->date_hired) {
                $dateOfAppointment = date('m/d/Y', strtotime($employee->date_hired));
            }

            // Format salary
            $presentSalary = $employee->salary ? number_format($employee->salary, 2, '.', ',') : '';

            // Parse place of birth
            $placeOfBirth = $employee->birth_place ?? '';
            $birthParts = explode(',', $placeOfBirth);
            $birthTown = trim($birthParts[0] ?? '');
            $birthCityProvince = trim($birthParts[1] ?? '');

            return $this->successResponse([
                'employee_id' => $employee->id,
                'last_name' => $employee->last_name ?? '',
                'first_name' => $employee->first_name ?? '',
                'middle_name' => $employee->middle_name ?? '',
                'sex' => $employee->gender ?? '',
                'civil_status' => $employee->civil_status ?? '',
                'tin' => '',
                'date_of_birth' => $dateOfBirth,
                'place_of_birth' => $placeOfBirth,
                'birth_town' => $birthTown,
                'birth_city_province' => $birthCityProvince,
                'address_line1' => '',
                'address_line2' => '',
                'barangay' => '',
                'city' => '',
                'province' => '',
                'zip_code' => '',
                'office' => $employee->company_name ?? '',
                'date_of_appointment' => $dateOfAppointment,
                'office_address_no' => '',
                'office_address_street' => '',
                'office_address_city' => '',
                'office_address_province' => '',
                'position_title' => $employee->position ?? '',
                'status_of_appointment' => $employee->employment_type ?? '',
                'present_salary' => $presentSalary,
                'salary_effectivity_date' => $dateOfAppointment, // Using date_hired as default
                'division_no' => '',
                'station_no' => '',
                'employee_no' => $employee->employee_no ?? '',
                'home_tel' => '',
                'office_tel' => '',
                'cellphone' => $employee->mobile_no ?? '',
                'email' => $employee->email ?? '',
            ], 'Employee details retrieved successfully');
        } catch (\Exception $e) {
            Log::error('GSIS Member Info Get Employee Details Error: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve employee details: ' . $e->getMessage());
        }
    }

    /**
     * Generate GSIS Membership Information Sheet PDF
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
                ->leftJoin('branches as b', 'b.id', '=', 'e.branch_id')
                ->leftJoin('civil_status as cs', 'cs.id', '=', 'e.civil_status_id')
                ->leftJoin('genders as g', 'g.id', '=', 'e.gender_id')
                ->leftJoin('employment_types as et', 'et.id', '=', 'e.employment_type_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    'e.date_hired',
                    'e.birthdate as birth_date',
                    'e.mobile_no',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.email ELSE dbo.ufn_DecryptString(e.email,'$app_key') END as email"),
                    'e.salary',
                    'cs.name as civil_status',
                    'g.name as gender',
                    'p.name as position',
                    'd.name as department',
                    'et.name as employment_type',
                    'c.name as company_name',
                    'b.name as branch_name',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.last_name
                        ELSE RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                        END as last_name"),
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.first_name
                        ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))
                        END as first_name"),
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.middle_name
                        ELSE RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key'))
                        END as middle_name"),
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.birth_place
                        ELSE RTRIM([dbo].[ufn_DecryptString](e.birth_place,'$app_key'))
                        END as birth_place"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN RTRIM(ISNULL(e.ra_house_no,'')) ELSE RTRIM(ISNULL([dbo].[ufn_DecryptString](e.ra_house_no,'$app_key'),'')) END as ra_house_no"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN RTRIM(ISNULL(e.ra_street,'')) ELSE RTRIM(ISNULL([dbo].[ufn_DecryptString](e.ra_street,'$app_key'),'')) END as ra_street"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN RTRIM(ISNULL(e.ra_village,'')) ELSE RTRIM(ISNULL([dbo].[ufn_DecryptString](e.ra_village,'$app_key'),'')) END as ra_village"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN RTRIM(ISNULL(e.ra_barangay,'')) ELSE RTRIM(ISNULL([dbo].[ufn_DecryptString](e.ra_barangay,'$app_key'),'')) END as ra_barangay"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN RTRIM(ISNULL(e.ra_city,'')) ELSE RTRIM(ISNULL([dbo].[ufn_DecryptString](e.ra_city,'$app_key'),'')) END as ra_city"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN RTRIM(ISNULL(e.ra_province,'')) ELSE RTRIM(ISNULL([dbo].[ufn_DecryptString](e.ra_province,'$app_key'),'')) END as ra_province"),
                )
                ->where('e.id', $request->employee_id)
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found');
            }

            // Format dates
            $dateOfBirth = null;
            if ($employee->birth_date) {
                $dateOfBirth = date('m/d/Y', strtotime($employee->birth_date));
            }

            $dateOfAppointment = null;
            if ($employee->date_hired) {
                $dateOfAppointment = date('m/d/Y', strtotime($employee->date_hired));
            }

            // Format salary
            $presentSalary = $employee->salary ? number_format($employee->salary, 2, '.', ',') : '';

            // Parse place of birth
            $placeOfBirth = $employee->birth_place ?? '';
            $birthParts = explode(',', $placeOfBirth);
            $birthTown = trim($birthParts[0] ?? '');
            $birthCityProvince = trim($birthParts[1] ?? '');

            // Prepare data for the form
            $last_name = $employee->last_name ?? '';
            $first_name = $employee->first_name ?? '';
            $middle_name = $employee->middle_name ?? '';
            $sex = $employee->gender ?? '';
            $civil_status = $employee->civil_status ?? '';
            $tin = '';
            $date_of_birth = $dateOfBirth;
            $place_of_birth = $placeOfBirth;
            $birth_town = $birthTown;
            $birth_city_province = $birthCityProvince;
            
            // Prepare residence address
            $ra_house_no = trim($employee->ra_house_no ?? '');
            $ra_street = trim($employee->ra_street ?? '');
            $address_line1 = trim(($ra_house_no . ' ' . $ra_street));
            
            $ra_village = trim($employee->ra_village ?? '');
            $ra_barangay = trim($employee->ra_barangay ?? '');
            $barangay = '';
            if ($ra_village && $ra_barangay) {
                $barangay = $ra_village . ', ' . $ra_barangay;
            } elseif ($ra_village) {
                $barangay = $ra_village;
            } elseif ($ra_barangay) {
                $barangay = $ra_barangay;
            }
            
            $city = trim($employee->ra_city ?? '');
            $province = trim($employee->ra_province ?? '');
            $address_line2 = '';
            $zip_code = '';
            $office = $employee->company_name ?? '';
            $date_of_appointment = $dateOfAppointment;
            $office_address_no = '';
            $office_address_street = '';
            $office_address_city = '';
            $office_address_province = '';
            $position_title = $employee->position ?? '';
            $status_of_appointment = $employee->employment_type ?? '';
            $present_salary = $presentSalary;
            $salary_effectivity_date = $dateOfAppointment;
            $division_no = '';
            $station_no = '';
            $employee_no = $employee->employee_no ?? '';
            $home_tel = '';
            $office_tel = '';
            $cellphone = $employee->mobile_no ?? '';
            $email = $employee->email ?? '';

            // Encode logo image to base64
            $logoImage = '';
            $logoPath = $this->resolveGsisLogoPath();
            if ($logoPath !== null) {
                $logoImage = base64_encode(file_get_contents($logoPath));
            }

            // Generate PDF
            $pdf = PDF::loadView('govt_forms.GSIS_Member_Info', compact(
                'last_name',
                'first_name',
                'middle_name',
                'sex',
                'civil_status',
                'tin',
                'date_of_birth',
                'place_of_birth',
                'birth_town',
                'birth_city_province',
                'address_line1',
                'address_line2',
                'barangay',
                'city',
                'province',
                'zip_code',
                'office',
                'date_of_appointment',
                'office_address_no',
                'office_address_street',
                'office_address_city',
                'office_address_province',
                'position_title',
                'status_of_appointment',
                'present_salary',
                'salary_effectivity_date',
                'division_no',
                'station_no',
                'employee_no',
                'home_tel',
                'office_tel',
                'cellphone',
                'email',
                'logoImage'
            ))->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('letter', 'portrait');

            $pdfContent = $pdf->output();

            $filename = 'GSIS_Member_Info_' . str_replace([' ', ','], '_', $last_name . '_' . $first_name) . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            Log::error('GSIS Member Info Print Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Request data: ' . json_encode($request->all()));
            return $this->serverErrorResponse('Failed to generate GSIS Membership Information Sheet PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate GSIS Membership Information Sheet DOCX - Exact PDF Match
     */
    public function generateDocx(Request $request)
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
                ->leftJoin('branches as b', 'b.id', '=', 'e.branch_id')
                ->leftJoin('civil_status as cs', 'cs.id', '=', 'e.civil_status_id')
                ->leftJoin('genders as g', 'g.id', '=', 'e.gender_id')
                ->leftJoin('employment_types as et', 'et.id', '=', 'e.employment_type_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    'e.date_hired',
                    'e.birthdate as birth_date',
                    'e.mobile_no',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.email ELSE dbo.ufn_DecryptString(e.email,'$app_key') END as email"),
                    'e.salary',
                    'cs.name as civil_status',
                    'g.name as gender',
                    'p.name as position',
                    'd.name as department',
                    'et.name as employment_type',
                    'c.name as company_name',
                    'c.address as company_address',
                    'b.name as branch_name',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.last_name ELSE RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as last_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.first_name ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')) END as first_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.middle_name ELSE RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')) END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.birth_place ELSE RTRIM([dbo].[ufn_DecryptString](e.birth_place,'$app_key')) END as birth_place"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN RTRIM(ISNULL(e.ra_house_no,'')) ELSE RTRIM(ISNULL([dbo].[ufn_DecryptString](e.ra_house_no,'$app_key'),'')) END as ra_house_no"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN RTRIM(ISNULL(e.ra_street,'')) ELSE RTRIM(ISNULL([dbo].[ufn_DecryptString](e.ra_street,'$app_key'),'')) END as ra_street"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN RTRIM(ISNULL(e.ra_village,'')) ELSE RTRIM(ISNULL([dbo].[ufn_DecryptString](e.ra_village,'$app_key'),'')) END as ra_village"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN RTRIM(ISNULL(e.ra_barangay,'')) ELSE RTRIM(ISNULL([dbo].[ufn_DecryptString](e.ra_barangay,'$app_key'),'')) END as ra_barangay"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN RTRIM(ISNULL(e.ra_city,'')) ELSE RTRIM(ISNULL([dbo].[ufn_DecryptString](e.ra_city,'$app_key'),'')) END as ra_city"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN RTRIM(ISNULL(e.ra_province,'')) ELSE RTRIM(ISNULL([dbo].[ufn_DecryptString](e.ra_province,'$app_key'),'')) END as ra_province"),
                )
                ->where('e.id', $request->employee_id)
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found');
            }

            // Format dates
            $dateOfBirth = $employee->birth_date ? date('m/d/Y', strtotime($employee->birth_date)) : '';
            $dateOfAppointment = $employee->date_hired ? date('m/d/Y', strtotime($employee->date_hired)) : '';

            // Format salary
            $presentSalary = $employee->salary ? number_format($employee->salary, 2, '.', ',') : '';

            // Parse place of birth
            $placeOfBirth = $employee->birth_place ?? '';
            $birthParts = explode(',', $placeOfBirth);
            $birthTown = trim($birthParts[0] ?? '');
            $birthCityProvince = trim($birthParts[1] ?? '');

            // Prepare data
            $last_name = $employee->last_name ?? '';
            $first_name = $employee->first_name ?? '';
            $middle_name = $employee->middle_name ?? '';
            $sex = $employee->gender ?? '';
            $civil_status = $employee->civil_status ?? '';
            $tin = '';
            $date_of_birth = $dateOfBirth;
            $place_of_birth_town = $birthTown;
            $place_of_birth_city = $birthCityProvince;
            
            // Prepare residence address
            $ra_house_no = trim($employee->ra_house_no ?? '');
            $ra_street = trim($employee->ra_street ?? '');
            $address_no = trim(($ra_house_no . ' ' . $ra_street));
            
            $ra_village = trim($employee->ra_village ?? '');
            $ra_barangay = trim($employee->ra_barangay ?? '');
            $barangay = '';
            if ($ra_village && $ra_barangay) {
                $barangay = $ra_village . ', ' . $ra_barangay;
            } elseif ($ra_village) {
                $barangay = $ra_village;
            } elseif ($ra_barangay) {
                $barangay = $ra_barangay;
            }
            
            $city = trim($employee->ra_city ?? '');
            $province = trim($employee->ra_province ?? '');
            $zip_code = '';
            $office = $employee->company_name ?? '';
            $date_of_appointment = $dateOfAppointment;
            $office_address_no = '';
            $office_address_street = $employee->company_address ?? '';
            $office_address_city = '';
            $office_address_province = '';
            $position_title = $employee->position ?? '';
            $status_of_appointment = $employee->employment_type ?? '';
            $present_salary = $presentSalary;
            $salary_effectivity_date = $dateOfAppointment;
            $division_no = '';
            $station_no = '';
            $employee_no = $employee->employee_no ?? '';
            $home_tel = '';
            $office_tel = '';
            $cellphone = $employee->mobile_no ?? '';
            $email = $employee->email ?? '';

            // Generate DOCX
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(9);

            // Section with margins
            $section = $phpWord->addSection([
                'marginTop' => 1200,
                'marginBottom' => 1200,
                'marginLeft' => 1200,
                'marginRight' => 1200,
            ]);

            // Form number (top right)
            $section->addText('Form No. MIS-05-02', ['size' => 8], ['alignment' => 'left', 'spaceAfter' => 100]);

            // Header table with logo, text, and photo box (total: 9500 twips)
            $headerTable = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
            $headerRow = $headerTable->addRow();
            
            // Logo cell (left) - 1400 twips
            $logoCell = $headerRow->addCell(1400, ['valign' => 'center']);
            $logoPath = $this->resolveGsisLogoPath();
            if ($logoPath !== null) {
                $logoCell->addImage($logoPath, [
                    'width' => 60,
                    'height' => 60,
                    'alignment' => 'center'
                ]);
            }

            // Header text (center) - 6500 twips
            $headerCell = $headerRow->addCell(6500, ['valign' => 'center']);
            $headerCell->addText('PASEGURUHAN NG MGA NAGLILINGKOD SA PAMAHALAAN', 
                ['bold' => true, 'size' => 9], 
                ['alignment' => 'left', 'spaceAfter' => 0, 'spaceBefore' => 0]);
            $headerCell->addText('(Government Service Insurance System)', 
                ['bold' => true, 'size' => 8], 
                ['alignment' => 'left', 'spaceAfter' => 0, 'spaceBefore' => 0]);
            $headerCell->addText('Financial Center, Roxas Boulevard, Pasay City', 
                ['size' => 8], 
                ['alignment' => 'left', 'spaceAfter' => 0, 'spaceBefore' => 0]);

            // Photo box (right) - 1600 twips
            $photoCell = $headerRow->addCell(1600, [
                'valign' => 'center',
                'borderSize' => 6,
                'borderColor' => '000000'
            ]);
            $photoCell->addText('ID Picture', 
                ['bold' => true, 'size' => 7], 
                ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
            $photoCell->addText('(Taken within the', 
                ['size' => 7], 
                ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
            $photoCell->addText('last 3 months)', 
                ['size' => 7], 
                ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);

            // Title
            $section->addText('MEMBERSHIP INFORMATION SHEET', 
                ['bold' => true, 'size' => 11], 
                ['alignment' => 'center', 'spaceAfter' => 100, 'spaceBefore' => 100]);

            // Full-width border
            $section->addText(
                str_repeat('_', 56.5),
                ['name' => 'Arial', 'size' => 15, 'bold' => true],
                ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]
            );

            // PERSONAL DATA section
            $section->addText('PERSONAL DATA:', 
                ['bold' => true, 'size' => 9], 
                ['spaceAfter' => 20, 'spaceBefore' => 0]);

            // Name row (total: 9500 twips = 1000 + 2833 + 2833 + 2834)
            $nameTable = $section->addTable(['width' => 9500, 'unit' => 'dxa', 'cellMargin' => 40]);
            $nameRow = $nameTable->addRow();
            $nameRow->addCell(1000)->addText('Name:', ['size' => 9], ['spaceAfter' => 0]);
            $nameRow->addCell(2833, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($last_name, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $nameRow->addCell(2833, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($first_name, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $nameRow->addCell(2834, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($middle_name, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Labels row
            $labelRow = $nameTable->addRow();
            $labelRow->addCell(1000)->addText('');
            $labelRow->addCell(2833)->addText('Last Name', 
                ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);
            $labelRow->addCell(2833)->addText('First Name', 
                ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);
            $labelRow->addCell(2834)->addText('Middle Name', 
                ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Info rows
            $infoTable = $section->addTable(['width' => 9500, 'unit' => 'dxa', 'cellMargin' => 40]);

            // Row 1: Sex, Civil Status, TIN (total: 9500 twips = 500 + 1800 + 1200 + 2200 + 500 + 3300)
            $row1 = $infoTable->addRow();
            $row1->addCell(500)->addText('Sex:', ['size' => 9], ['spaceAfter' => 0]);
            $sexCell = $row1->addCell(2200, ['borderBottomSize' => 6, 'borderBottomColor' => '000000']);
            $sexCell->addText($sex ?: ' ', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);

            $row1->addCell(500)->addText('Civil Status:', ['size' => 9], ['spaceAfter' => 0]);
            $civilCell = $row1->addCell(2200, ['borderBottomSize' => 6, 'borderBottomColor' => '000000']);
            $civilCell->addText($civil_status ?: ' ', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);

            $row1->addCell(500)->addText('TIN:', ['size' => 9], ['spaceAfter' => 0]);
            $tinCell = $row1->addCell(2200, ['borderBottomSize' => 6, 'borderBottomColor' => '000000']);
            $tinCell->addText($tin ?: ' ', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Row 2: Date of Birth, Place of Birth (total: 9500 twips = 1000 + 2000 + 1500 + 2000 + 3000)
            $row2 = $infoTable->addRow();
            $row2->addCell(2000)->addText('Date of Birth:', ['size' => 9], ['spaceAfter' => 0]);
            $row2->addCell(2000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($date_of_birth, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $row2->addCell(2000)->addText('Place of Birth:', ['size' => 9], ['spaceAfter' => 0]);
            $row2->addCell(2000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($place_of_birth_town, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $row2->addCell(3000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($place_of_birth_city, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Row 3: Labels
            $row3 = $infoTable->addRow();
            $row3->addCell(1000)->addText('', [], ['spaceAfter' => 0]);
            $row3->addCell(2000)->addText('(Month/Day/Year)', 
                ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);
            $row3->addCell(1500)->addText('', [], ['spaceAfter' => 0]);
            $row3->addCell(2000)->addText('Town/District', 
                ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);
            $row3->addCell(3000)->addText('City/Province', 
                ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Residence/Mailing Address
            $section->addText('Residence/Mailing Address:', 
                ['size' => 9], ['spaceAfter' => 50, 'spaceBefore' => 100]);
            
            // Address row (total: 9500 twips = 2400 + 1775 + 1775 + 1775 + 1775)
            $addrTable = $section->addTable(['width' => 9500, 'unit' => 'dxa', 'cellMargin' => 40]);
            $addrRow = $addrTable->addRow();
            $addrRow->addCell(2400, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($address_no, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $addrRow->addCell(1775, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($barangay, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $addrRow->addCell(1775, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($city, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $addrRow->addCell(1775, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($province, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $addrRow->addCell(1775, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($zip_code, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            
            $addrLabelRow = $addrTable->addRow();
            $addrLabelRow->addCell(2400)->addText('House, Apt. or Bldg No./St. Name', 
                ['size' => 7], ['alignment' => 'center']);
            $addrLabelRow->addCell(1775)->addText('Barangay or Barrio', 
                ['size' => 7], ['alignment' => 'center']);
            $addrLabelRow->addCell(1775)->addText('Town/City', 
                ['size' => 7], ['alignment' => 'center']);
            $addrLabelRow->addCell(1775)->addText('Province', 
                ['size' => 7], ['alignment' => 'center']);
            $addrLabelRow->addCell(1775)->addText('Zip Code', 
                ['size' => 7], ['alignment' => 'center']);

            // Full-width border
            $section->addText(
                str_repeat('_', 56.5),
                ['name' => 'Arial', 'size' => 15, 'bold' => true],
                ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]
            );

            // EMPLOYMENT DATA section
            $section->addText('EMPLOYMENT DATA:', 
                ['bold' => true, 'size' => 9], 
                ['spaceAfter' => 100, 'spaceBefore' => 100]);

            // Office and Date with cells and borders
            $officeDateTable = $section->addTable(['width' => 9500, 'unit' => 'dxa', 'cellMargin' => 40, 'spaceAfter' => 100]);
            $officeDateRow = $officeDateTable->addRow();
            $officeDateRow->addCell(800)->addText('Office:', ['size' => 9], ['spaceAfter' => 0]);
            $officeDateRow->addCell(4200, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($office, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $officeDateRow->addCell(3200)->addText('Date of Original Appointment:', ['size' => 9], ['spaceAfter' => 0]);
            $officeDateRow->addCell(1300, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($date_of_appointment, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            
            // Label row for the date format
            $officeDateLabelRow = $officeDateTable->addRow();
            $officeDateLabelRow->addCell(800)->addText('', [], ['spaceAfter' => 0]);
            $officeDateLabelRow->addCell(4800)->addText('', [], ['spaceAfter' => 0]);
            $officeDateLabelRow->addCell(3200)->addText('', [], ['spaceAfter' => 0]);
            $officeDateLabelRow->addCell(1300)->addText('(Month/Day/Year)', 
                ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Office Address - single cell row for data, labels row below
            $section->addText('Office Address:', ['size' => 9], ['spaceAfter' => 50]);
            $officeAddrTable = $section->addTable(['width' => 9500, 'unit' => 'dxa', 'cellMargin' => 40]);

            // Single cell for the full address data
            $officeAddrRow = $officeAddrTable->addRow();
            $officeAddrRow->addCell(9500, ['gridSpan' => 4, 'borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($office_address_street, ['size' => 9], ['spaceAfter' => 0]);

            // Labels row with columns (total: 9500 twips = 1200 + 3100 + 2600 + 2600)
            $officeAddrLabelRow = $officeAddrTable->addRow();
            $officeAddrLabelRow->addCell(1200)->addText('No.', 
                ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);
            $officeAddrLabelRow->addCell(3100)->addText('Street', 
                ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);
            $officeAddrLabelRow->addCell(2600)->addText('Town/City', 
                ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);
            $officeAddrLabelRow->addCell(2600)->addText('Province', 
                ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Position and Status with cells and borders
            $positionTable = $section->addTable(['width' => 9500, 'unit' => 'dxa', 'cellMargin' => 40, 'spaceAfter' => 50, 'spaceBefore' => 100]);
            $positionRow = $positionTable->addRow();
            $positionRow->addCell(1500)->addText('Position Title:', ['size' => 9], ['spaceAfter' => 0]);
            $positionRow->addCell(4000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($position_title, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $positionRow->addCell(2000)->addText('Status of Appointment:', ['size' => 9], ['spaceAfter' => 0]);
            $positionRow->addCell(2000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($status_of_appointment, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Salary and Date with cells and borders
            $salaryTable = $section->addTable(['width' => 9500, 'unit' => 'dxa', 'cellMargin' => 40]);
            $salaryRow = $salaryTable->addRow();
            $salaryRow->addCell(1500)->addText('Present Salary:', ['size' => 9], ['spaceAfter' => 0]);
            $salaryRow->addCell(2000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($present_salary, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $salaryRow->addCell(3000)->addText('Date of Effectivity of Present Salary:', ['size' => 9], ['spaceAfter' => 0]);
            $salaryRow->addCell(2000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($salary_effectivity_date, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Label row for the date format
            $salaryLabelRow = $salaryTable->addRow();
            $salaryLabelRow->addCell(1500)->addText('', [], ['spaceAfter' => 0]);
            $salaryLabelRow->addCell(2000)->addText('', [], ['spaceAfter' => 0]);
            $salaryLabelRow->addCell(3000)->addText('', [], ['spaceAfter' => 0]);
            $salaryLabelRow->addCell(2000)->addText('(Month/Day/Year)', 
                ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Full-width border
            $section->addText(
                str_repeat('_', 56.5),
                ['name' => 'Arial', 'size' => 15, 'bold' => true],
                ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]
            );

            // DEPED section with cells and borders
            $depedTable = $section->addTable(['width' => 9500, 'unit' => 'dxa', 'cellMargin' => 40, 'spaceAfter' => 100, 'spaceBefore' => 50]);
            $depedRow = $depedTable->addRow();
            $depedRow->addCell(2000)->addText('For DEPED Employees only:', ['bold' => true, 'size' => 7], ['spaceAfter' => 0]);
            $depedRow->addCell(1000)->addText('Division No.:', ['size' => 7], ['spaceAfter' => 0]);
            $depedRow->addCell(1500, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($division_no, ['size' => 7], ['spaceAfter' => 0]);
            $depedRow->addCell(1000)->addText('Station No.:', ['size' => 7], ['spaceAfter' => 0]);
            $depedRow->addCell(1500, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($station_no, ['size' => 7], ['spaceAfter' => 0]);
            $depedRow->addCell(1000)->addText('Employee No.:', ['size' => 7], ['spaceAfter' => 0]);
            $depedRow->addCell(1500, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($employee_no, ['size' => 7], ['spaceAfter' => 0]);

            // Full-width border
            $section->addText(
                str_repeat('_', 56.5),
                ['name' => 'Arial', 'size' => 15, 'bold' => true],
                ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]
            );          
            // Full-width border
            $section->addText(
                str_repeat('_', 56.5),
                ['name' => 'Arial', 'size' => 15, 'bold' => true],
                ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]
            );

            // Contact info with cells and borders
            $contactTable = $section->addTable(['width' => 9500, 'unit' => 'dxa', 'cellMargin' => 40, 'spaceAfter' => 100, 'spaceBefore' => 100]);
            $contactRow1 = $contactTable->addRow();
            $contactRow1->addCell(2000)->addText('Home Tel. No.:', ['size' => 9], ['spaceAfter' => 0]);
            $contactRow1->addCell(3000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($home_tel, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $contactRow1->addCell(2000)->addText('Cellphone No.:', ['size' => 9], ['spaceAfter' => 0]);
            $contactRow1->addCell(2500, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($cellphone, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);

            $contactRow2 = $contactTable->addRow();
            $contactRow2->addCell(2000)->addText('Office Tel. No.:', ['size' => 9], ['spaceAfter' => 0]);
            $contactRow2->addCell(3000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($office_tel, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $contactRow2->addCell(2000)->addText('eMail Address:', ['size' => 9], ['spaceAfter' => 0]);
            $contactRow2->addCell(2500, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText($email, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Full-width border
            $section->addText(
                str_repeat('_', 56.5),
                ['name' => 'Arial', 'size' => 15, 'bold' => true],
                ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]
            );               

            // Signature section

            $sigTable = $section->addTable();
            $sigRow = $sigTable->addRow();
            $sigRow->addCell(3500, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText('', [], ['spaceAfter' => 200]);
            $section->addText('Signature of Member', ['size' => 9], ['spaceAfter' => 200]);

            $section->addText('Attested:', ['size' => 9], ['spaceAfter' => 50]);
            $sigTable2 = $section->addTable();
            $sigRow2 = $sigTable2->addRow();
            $sigRow2->addCell(5000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText('', [], ['spaceAfter' => 200]);
            $section->addText('Signature over Printed Name of Personnel/Administrative Officer', 
                ['size' => 9]);

            // Save to temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'gsis_member_info_');
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            // Read the file content
            $docxContent = file_get_contents($tempFile);
            unlink($tempFile);

            $filename = 'GSIS_Member_Info_' . str_replace([' ', ','], '_', $last_name . '_' . $first_name) . '_' . date('Y-m-d') . '.docx';

            return response($docxContent)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($docxContent));
        } catch (\Exception $e) {
            Log::error('GSIS Member Info DOCX Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Request data: ' . json_encode($request->all()));
            return $this->serverErrorResponse('Failed to generate GSIS Membership Information Sheet DOCX: ' . $e->getMessage());
        }
    }

    /**
     * Resolve path to GSIS logo (backend public first, then frontend assets for local dev).
     */
    private function resolveGsisLogoPath(): ?string
    {
        $logoPath = public_path('dist/img/gsis_logo.png');
        if (file_exists($logoPath)) {
            return $logoPath;
        }

        $logoPath = base_path('../pr-frontend/src/assets/gsis_logo.png');
        return file_exists($logoPath) ? $logoPath : null;
    }
}

