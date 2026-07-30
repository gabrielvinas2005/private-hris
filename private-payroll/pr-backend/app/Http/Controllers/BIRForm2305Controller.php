<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BIRForm2305Controller extends Controller
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
     * Get initial data for BIR Form 2305
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

            // Get company data (employer info)
            $company = DB::table('companies')->first();

            return $this->successResponse([
                'employees' => $employees,
                'company' => $company,
            ], 'BIR Form 2305 data retrieved successfully');
        } catch (\Exception $e) {
            Log::error('BIR Form 2305 Index Error: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve BIR Form 2305 data: ' . $e->getMessage());
        }
    }

    /**
     * Get employee details for BIR Form 2305 auto-population
     */
    public function getEmployeeDetails($employeeId)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Get employee data with all relevant fields for BIR Form 2305
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
                    'e.tin_no',
                    'e.ra_postal_id',
                    'e.ra_house_no',
                    'e.ra_barangay',
                    'e.ra_street',
                    'e.ra_village',
                    'e.ra_city',
                    'e.ra_province',
                    'e.ra_region',
                    'e.end_date',
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

            // Format date_hired
            $dateHired = null;
            if ($employee->date_hired) {
                $dateHired = date('Y-m-d', strtotime($employee->date_hired));
            }

            $dateOfBirth = null;
            if ($employee->birthdate) {
                $dateOfBirth = date('Y-m-d', strtotime($employee->birthdate));
            }

            $dateResignation = null;
            if ($employee->end_date) {
                $dateResignation = date('Y-m-d', strtotime($employee->end_date));
            }

            $grossCompensation = DB::table('employees')
                ->where('id', $employeeId)
                ->value('salary');

            return $this->successResponse([
                'employee_id' => $employee->id,
                'last_name' => $employee->last_name ?? '',
                'first_name' => $employee->first_name ?? '',
                'middle_name' => $employee->middle_name ?? '',
                'date_hired' => $dateHired,
                'date_resignation' => $dateResignation,
                'date_of_birth' => $dateOfBirth,
                'sex' => $employee->gender ?? '',
                'civil_status' => $employee->civil_status ?? '',
                'employee_tin' => $employee->tin_no ?? '',
                'employee_rdo' => '', // Removed - column doesn't exist
                'employee_zip' => $employee->ra_postal_id ?? '',
                'employee_tel' => $employee->mobile_no ?? '',
                'employee_address' => trim($employee->employee_address ?? ''),
                'company_name' => $employee->company_name ?? '',
                'company_address' => trim($employee->company_address ?? ''),
                'company_tin' => '', // Removed - column doesn't exist
                'company_rdo' => '', // Removed - column doesn't exist
                'company_zip' => '', // Removed - column doesn't exist
                'company_phone' => '',
            ], 'Employee details retrieved successfully');
        } catch (\Exception $e) {
            Log::error('BIR Form 2305 Get Employee Details Error: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve employee details: ' . $e->getMessage());
        }
    }

    /**
     * Generate BIR Form 2305 PDF
     */
    public function print(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|integer|exists:employees,id',
                'exemption_status' => 'nullable|string|in:S,ME,S1',
                'exemption_code' => 'nullable|string|max:50',
                'dependent_count' => 'nullable|integer|min:0',
                'gross_compensation' => 'nullable|numeric|min:0',
                'date_hired' => 'nullable|date',
                'date_resignation' => 'nullable|date',
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
                ->select(
                    'e.id',
                    'e.employee_no',
                    'e.company_id',
                    'e.date_hired',
                    'e.mobile_no',
                    'e.tin_no',
                    'e.ra_postal_id',
                    'e.birthdate',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(RTRIM(e.last_name), ', ', RTRIM(e.first_name), ' ', RTRIM(ISNULL(e.middle_name,'')))
                        ELSE
                            CONCAT(
                                RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), ', ',
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), ' ',
                                RTRIM(ISNULL([dbo].[ufn_DecryptString](e.middle_name,'$app_key'),''))
                            )
                        END as employee_name"),
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
                        END as employee_address"),
                    'cs.name as civil_status',
                    'p.name as position',
                    'd.name as department',
                    'c.name as company_name',
                    'c.address as company_address'
                )
                ->where('e.id', $request->employee_id)
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found');
            }

            $company = DB::table('companies')
                ->where('id', $employee->company_id ?? null)
                ->first();

            if (!$company) {
                $company = DB::table('companies')->first();
            }

            $employer_name = $company->name ?? '';
            $employer_address = $employee->company_address ?? $company->address ?? '';
            $employer_tin = '';
            $employer_rdo = '';
            $employer_zip = '';
            $employer_tel = $company->telephone_no ?? $company->mobile_no ?? '';

            $employee_name = $employee->employee_name ?? '';
            $employee_address = trim($employee->employee_address ?? '');
            $employee_tin = $employee->tin_no ?? '';
            $employee_rdo = '';
            $employee_zip = '';
            $employee_tel = $employee->mobile_no ?? '';
            $ra_postal_id = $employee->ra_postal_id ?? '';
            
            // Format birthdate for display (MM/DD/YYYY)
            $employee_birthdate = '';
            if ($employee->birthdate) {
                $employee_birthdate = date('m/d/Y', strtotime($employee->birthdate));
            }

            // Exemption status and details - using request values only since columns don't exist
            $exemption_status = $request->exemption_status ?? '';
            $exemption_code = $request->exemption_code ?? '';
            $dependent_count = $request->dependent_count ?? 0;

            // Employer details (default empty rows)
            $employer_details = $request->employer_details ?? [
                ['line_no' => '14A', 'business_nature' => '', 'tax_rate' => '', 'm' => '', 's' => ''],
                ['line_no' => '14B', 'business_nature' => '', 'tax_rate' => '', 'm' => '', 's' => ''],
            ];

            $gross_compensation = $request->gross_compensation ?? '';
            $date_hired = $request->date_hired ?? ($employee->date_hired ? date('Y-m-d', strtotime($employee->date_hired)) : '');
            $date_resignation = $request->date_resignation ?? '';

            // Business address (for self-employed, usually empty for regular employees)
            $business_address = '';
            $business_zip = '';

            // Encode BIR logo image to base64
            $logoPath = public_path('dist/img/BIR-logo.png');
            $birLogo = '';
            if (file_exists($logoPath)) {
                $birLogo = base64_encode(file_get_contents($logoPath));
            } else {
                // Fallback: try frontend assets
                $logoPath = base_path('../pr-frontend/src/assets/BIR-logo.png');
                if (file_exists($logoPath)) {
                    $birLogo = base64_encode(file_get_contents($logoPath));
                }
            }

            // Generate PDF
            $pdf = PDF::loadView('govt_forms.BIR_Form_2305', compact(
                'employer_name',
                'employer_address',
                'employer_tin',
                'employer_rdo',
                'employer_zip',
                'employer_tel',
                'employee_name',
                'employee_address',
                'ra_postal_id',
                'employee_tin',
                'employee_rdo',
                'employee_zip',
                'employee_tel',
                'employee_birthdate',
                'business_address',
                'business_zip',
                'exemption_status',
                'exemption_code',
                'dependent_count',
                'employer_details',
                'gross_compensation',
                'date_hired',
                'date_resignation',
                'birLogo'
            ))->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper([0, 0, 612, 936], 'portrait'); // 8.5in x 13in

            $pdfContent = $pdf->output();

            $filename = 'BIR_Form_2305_' . str_replace([' ', ','], '_', $employee_name) . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            Log::error('BIR Form 2305 Print Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Request data: ' . json_encode($request->all()));
            return $this->serverErrorResponse('Failed to generate BIR Form 2305 PDF: ' . $e->getMessage());
        }
    }
}

