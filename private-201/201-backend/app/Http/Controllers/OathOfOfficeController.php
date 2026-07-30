<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;
use Carbon\Carbon;

class OathOfOfficeController extends Controller
{
    use ApiResponse;

    protected function loadJsonRecords(string $filename, string $recordsKey = 'RECORDS') : array
    {
        $candidates = [
            // Frontend assets folder (primary — source of truth)
            base_path('../201-frontend/src/assets/' . $filename),
            base_path($filename),
            resource_path($filename),
            resource_path('data/' . $filename),
            public_path($filename),
            base_path('public/' . $filename),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                // Increase memory limit for large files (e.g. refbrgy.json ~5MB)
                $currentLimit = ini_get('memory_limit');
                ini_set('memory_limit', '512M');

                $content = file_get_contents($path);
                $json    = $content !== false ? json_decode($content, true) : null;

                ini_set('memory_limit', $currentLimit);

                if ($json === null) {
                    continue;
                }

                $records = is_array($json) && isset($json[$recordsKey]) ? $json[$recordsKey] : ($json ?? []);
                return $records;
            }
        }

        throw new \RuntimeException('Reference JSON not found: ' . $filename);
    }

    /**
     * Convert an address code to its human-readable name using a reference list.
     * Tries: exact code match → description match → returns raw value as-is.
     */
    protected function convertAddressCode(string $value, array $data, string $codeKey, string $descKey): string
    {
        $value = trim((string) $value);
        if (empty($value)) return '';

        // 1. Try exact match by code field
        foreach ($data as $item) {
            if (isset($item[$codeKey]) && (string) $item[$codeKey] === $value) {
                return $item[$descKey] ?? $value;
            }
        }

        // 2. Try case-insensitive match by description (if value is already a name)
        $valueLower = strtolower($value);
        foreach ($data as $item) {
            if (isset($item[$descKey]) && strtolower((string) $item[$descKey]) === $valueLower) {
                return $item[$descKey] ?? $value;
            }
        }

        // 3. Return raw value as-is (it may already be a human-readable name)
        return $value;
    }

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

            $data = DB::table('applicant_headers as ah')
                ->join('applicant_details as ad', 'ah.id', '=', 'ad.applicant_id')
                ->leftJoin('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                ->leftJoin('positions as pos', 'p.position_id', '=', 'pos.id')
                ->leftJoin('departments as d', 'p.department_id', '=', 'd.id')
                ->select(
                    'ah.id',
                    'ah.applicant_no',
                    'ah.email',
                    'ah.first_name',
                    'ah.middle_name',
                    'ah.last_name',
                    'pos.name as position',
                    'd.name as department',
                    'p.code as plantilla_code',
                    DB::raw("CASE WHEN ISNULL(ah.middle_name,'') = '' THEN
                                CONCAT(ah.first_name,' ',ah.last_name,' - ',pos.name)
                            ELSE
                                CONCAT(ah.first_name,' ',substring(ah.middle_name,1,1),'. ',ah.last_name,' - ',pos.name)
                                END as name"),
                    DB::raw("CONCAT(COALESCE(ah.first_name, ''), ' ', COALESCE(ah.middle_name, ''), ' ', COALESCE(ah.last_name, '')) as full_name")
                )
                ->where('ad.application_status_id', 5) // For Hiring
                ->where('ad.is_plantilla', 1) // Only plantilla positions
                ->whereNull('ah.employee_no') // Not yet converted to employee
                ->orderBy('ah.last_name', 'asc')
                ->orderBy('ah.first_name', 'asc')
                ->get()
                ->map(function ($applicant) {
                    // Clean up full name (remove extra spaces)
                    $applicant->full_name = trim(preg_replace('/\s+/', ' ', $applicant->full_name));
                    return $applicant;
                });

            return $this->successResponse($data, 'Oath of office records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve oath of office records: ' . $e->getMessage());
        }
    }

    public function generatePdf(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee' => 'required',
                'signatory' => 'nullable|string',
                'position' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            // First, try to get from employees table (for applicants who have been converted to employees)
            // Get the position from applicant_details (the position they applied for), not from employees.position_id
            // Try matching by employee_no first, then by applicant_no if employee_no doesn't match
            $oath_of_offices = DB::table('employees as e')
                ->join('users as u', 'e.employee_no', '=', 'u.employee_no')
                ->leftJoin('applicant_headers as ah', function($join) use ($request) {
                    $join->on(function($q) {
                        $q->on('e.employee_no', '=', 'ah.employee_no')
                          ->orOn('e.employee_no', '=', 'ah.applicant_no');
                    });
                })
                ->leftJoin('applicant_details as ad', 'ah.id', '=', 'ad.applicant_id')
                ->leftJoin('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                ->leftJoin('positions as c', 'p.position_id', '=', 'c.id')
                ->leftJoin('positions as c_fallback', 'e.position_id', '=', 'c_fallback.id') // Fallback to employee position
                ->leftJoin('departments as d', 'p.department_id', '=', 'd.id')
                ->leftJoin('departments as d_fallback', 'e.department_id', '=', 'd_fallback.id') // Fallback to employee department
                ->leftJoin('genders as g', 'e.gender_id', '=', 'g.id')
                ->select(
                    'e.id',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.first_name ELSE dbo.ufn_DecryptString(e.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.middle_name ELSE dbo.ufn_DecryptString(e.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.last_name ELSE dbo.ufn_DecryptString(e.last_name,'$app_key') END as last_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) ELSE CONCAT(COALESCE(dbo.ufn_DecryptString(e.first_name,'$app_key'), ''), ' ', COALESCE(dbo.ufn_DecryptString(e.middle_name,'$app_key'), ''), ' ', COALESCE(dbo.ufn_DecryptString(e.last_name,'$app_key'), '')) END as name"),
                    'e.ra_region',
                    'e.ra_province',
                    'e.ra_city',
                    'e.ra_barangay',
                    DB::raw("COALESCE(c.name, c_fallback.name) as position"), // Position from applicant_details -> plantillas -> positions, fallback to employee position
                    DB::raw("COALESCE(d.name, d_fallback.name) as department"), // Department from plantillas, fallback to employee department
                    'g.name as gender',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.last_name ELSE dbo.ufn_DecryptString(e.last_name,'$app_key') END as name_sig"),
                )
                ->where('u.is_applicant', 1)
                ->where(function($query) use ($request) {
                    // Try to match by employee ID, employee_no, or applicant_id
                    $query->where('e.id', $request->employee)
                          ->orWhere('e.employee_no', $request->employee)
                          ->orWhere('ah.id', $request->employee); // Also try matching by applicant_id
                })
                ->get();

            // If not found in employees, fallback to applicant_headers (this handles applicants not yet converted)
            if ($oath_of_offices->isEmpty()) {
                $oath_of_offices =
                    DB::table('applicant_headers as ah')
                    ->join('applicant_details as ad', 'ah.id', '=', 'ad.applicant_id')
                    ->leftJoin('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                    ->leftJoin('positions as c', 'p.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'p.department_id', '=', 'd.id')
                    ->leftJoin('genders as e', 'ah.gender', '=', 'e.id')
                    ->select(
                        'ah.id',
                        'ah.first_name',
                        'ah.middle_name',
                        'ah.last_name',
                        'ah.address',
                        'ah.ra_region',
                        'ah.ra_province',
                        'ah.ra_city',
                        'ah.ra_barangay',
                        DB::raw("CONCAT(COALESCE(ah.first_name, ''), ' ', COALESCE(ah.middle_name, ''), ' ', COALESCE(ah.last_name, '')) as name"),
                        'c.name as position',
                        'd.name as department',
                        'e.name as gender',
                        DB::raw("ah.last_name as name_sig"),
                    )
                    ->where('ah.id', $request->employee)
                    ->where('ad.application_status_id', 5) // For Hiring
                    ->get();
            }

            if ($oath_of_offices->isEmpty()) {
                return $this->notFoundResponse('Applicant not found');
            }

            $applicant = $oath_of_offices[0];
            
            // Validate required fields - use trim to handle empty strings
            // Only validate if the fields are truly missing (not just whitespace or null)
            $missingFields = [];
            
            $firstName = trim($applicant->first_name ?? '');
            $lastName = trim($applicant->last_name ?? '');
            $position = trim($applicant->position ?? '');
            $raRegion = trim($applicant->ra_region ?? '');
            $raCity = trim($applicant->ra_city ?? '');
            $address = trim($applicant->address ?? '');
            
            if (empty($firstName)) $missingFields[] = 'First Name';
            if (empty($lastName)) $missingFields[] = 'Last Name';
            
            // Check address - need at least region OR city OR address text
            $hasAddress = !empty($raRegion) || !empty($raCity) || !empty($address);
            if (!$hasAddress) {
                $missingFields[] = 'Address';
            }
            
            // Check position - only if truly empty
            if (empty($position)) {
                $missingFields[] = 'Position';
            }
            
            if (!empty($missingFields)) {
                $fieldsList = implode(', ', $missingFields);
                return $this->errorResponse('There is insufficient data of this applicant, please fill up these fields: ' . $fieldsList, 400);
            }

            // Load JSON reference data and convert address codes to names
            try {
                $province_data = $this->loadJsonRecords('refprovince.json');
                $city_data     = $this->loadJsonRecords('refcitymun.json');
                $brgy_data     = $this->loadJsonRecords('refbrgy.json');

                $ra_province = $this->convertAddressCode(
                    (string)($applicant->ra_province ?? ''), $province_data, 'provCode', 'provDesc'
                );
                $ra_city = $this->convertAddressCode(
                    (string)($applicant->ra_city ?? ''), $city_data, 'citymunCode', 'citymunDesc'
                );
                $ra_brgy = $this->convertAddressCode(
                    (string)($applicant->ra_barangay ?? ''), $brgy_data, 'brgyCode', 'brgyDesc'
                );

                $address = [
                    'ra_province' => $ra_province,
                    'pa_province' => $ra_province,
                    'ra_city'     => $ra_city,
                    'pa_city'     => $ra_city,
                    'ra_brgy'     => $ra_brgy,
                    'pa_brgy'     => $ra_brgy,
                ];
            } catch (\Exception $e) {
                // Use raw values as fallback so PDF still generates
                $address = [
                    'ra_province' => $applicant->ra_province ?? '',
                    'pa_province' => $applicant->ra_province ?? '',
                    'ra_city'     => $applicant->ra_city ?? '',
                    'pa_city'     => $applicant->ra_city ?? '',
                    'ra_brgy'     => $applicant->ra_barangay ?? '',
                    'pa_brgy'     => $applicant->ra_barangay ?? '',
                ];
            }

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position,
            );

            $pdf = PDF::loadView('oath_of_office.oath_of_office_print_2025', compact('oath_of_offices', 'signatories', 'address'))->setOptions(['defaultFont' => 'times new roman']);
            $pdf->setPaper('A4');
            
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'oath_of_office_' . $oath_of_offices[0]->name . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            // Check if it's a SQL error (missing columns) or data validation error
            if (strpos($e->getMessage(), 'Invalid column name') !== false ||
                strpos($e->getMessage(), 'Reference JSON not found') !== false || 
                strpos($e->getMessage(), 'Undefined array key') !== false ||
                strpos($e->getMessage(), 'Trying to get property') !== false ||
                strpos($e->getMessage(), 'SQLSTATE') !== false) {
                return $this->errorResponse('There is insufficient data of this applicant, please fill up these fields: Address, First Name, Last Name, and Position', 400);
            }
            return $this->serverErrorResponse('Failed to generate oath of office PDF: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        // Keep the original print method for backward compatibility
        return $this->generatePdf($request);
    }

    /**
     * Generate oath of office as DOCX using PhpWord.
     */
    public function word(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee' => 'required',
                'signatory' => 'nullable|string',
                'position' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            // First, try to get from employees table (for applicants who have been converted to employees)
            // Get the position from applicant_details (the position they applied for), not from employees.position_id
            // Try matching by employee_no first, then by applicant_no if employee_no doesn't match
            $oath_of_offices = DB::table('employees as e')
                ->join('users as u', 'e.employee_no', '=', 'u.employee_no')
                ->leftJoin('applicant_headers as ah', function($join) use ($request) {
                    $join->on(function($q) {
                        $q->on('e.employee_no', '=', 'ah.employee_no')
                          ->orOn('e.employee_no', '=', 'ah.applicant_no');
                    });
                })
                ->leftJoin('applicant_details as ad', 'ah.id', '=', 'ad.applicant_id')
                ->leftJoin('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                ->leftJoin('positions as c', 'p.position_id', '=', 'c.id')
                ->leftJoin('positions as c_fallback', 'e.position_id', '=', 'c_fallback.id') // Fallback to employee position
                ->leftJoin('departments as d', 'p.department_id', '=', 'd.id')
                ->leftJoin('departments as d_fallback', 'e.department_id', '=', 'd_fallback.id') // Fallback to employee department
                ->leftJoin('genders as g', 'e.gender_id', '=', 'g.id')
                ->select(
                    'e.id',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.first_name ELSE dbo.ufn_DecryptString(e.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.middle_name ELSE dbo.ufn_DecryptString(e.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.last_name ELSE dbo.ufn_DecryptString(e.last_name,'$app_key') END as last_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) ELSE CONCAT(COALESCE(dbo.ufn_DecryptString(e.first_name,'$app_key'), ''), ' ', COALESCE(dbo.ufn_DecryptString(e.middle_name,'$app_key'), ''), ' ', COALESCE(dbo.ufn_DecryptString(e.last_name,'$app_key'), '')) END as name"),
                    'e.ra_region',
                    'e.ra_province',
                    'e.ra_city',
                    'e.ra_barangay',
                    DB::raw("COALESCE(c.name, c_fallback.name) as position"), // Position from applicant_details -> plantillas -> positions, fallback to employee position
                    DB::raw("COALESCE(d.name, d_fallback.name) as department"), // Department from plantillas, fallback to employee department
                    'g.name as gender',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.last_name ELSE dbo.ufn_DecryptString(e.last_name,'$app_key') END as name_sig"),
                )
                ->where('u.is_applicant', 1)
                ->where(function($query) use ($request) {
                    // Try to match by employee ID, employee_no, or applicant_id
                    $query->where('e.id', $request->employee)
                          ->orWhere('e.employee_no', $request->employee)
                          ->orWhere('ah.id', $request->employee); // Also try matching by applicant_id
                })
                ->get();

            // If not found in employees, fallback to applicant_headers (this handles applicants not yet converted)
            if ($oath_of_offices->isEmpty()) {
                $oath_of_offices =
                    DB::table('applicant_headers as ah')
                    ->join('applicant_details as ad', 'ah.id', '=', 'ad.applicant_id')
                    ->leftJoin('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                    ->leftJoin('positions as c', 'p.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'p.department_id', '=', 'd.id')
                    ->leftJoin('genders as e', 'ah.gender', '=', 'e.id')
                    ->select(
                        'ah.id',
                        'ah.first_name',
                        'ah.middle_name',
                        'ah.last_name',
                        'ah.address',
                        'ah.ra_region',
                        'ah.ra_province',
                        'ah.ra_city',
                        'ah.ra_barangay',
                        DB::raw("CONCAT(COALESCE(ah.first_name, ''), ' ', COALESCE(ah.middle_name, ''), ' ', COALESCE(ah.last_name, '')) as name"),
                        'c.name as position',
                        'd.name as department',
                        'e.name as gender',
                        DB::raw("ah.last_name as name_sig"),
                    )
                    ->where('ah.id', $request->employee)
                    ->where('ad.application_status_id', 5) // For Hiring
                    ->get();
            }

            if ($oath_of_offices->isEmpty()) {
                return $this->notFoundResponse('Applicant not found');
            }

            $applicant = $oath_of_offices[0];
            
            // Validate required fields - use trim to handle empty strings
            // Only validate if the fields are truly missing (not just whitespace or null)
            $missingFields = [];
            
            $firstName = trim($applicant->first_name ?? '');
            $lastName = trim($applicant->last_name ?? '');
            $position = trim($applicant->position ?? '');
            $raRegion = trim($applicant->ra_region ?? '');
            $raCity = trim($applicant->ra_city ?? '');
            $address = trim($applicant->address ?? '');
            
            if (empty($firstName)) $missingFields[] = 'First Name';
            if (empty($lastName)) $missingFields[] = 'Last Name';
            
            // Check address - need at least region OR city OR address text
            $hasAddress = !empty($raRegion) || !empty($raCity) || !empty($address);
            if (!$hasAddress) {
                $missingFields[] = 'Address';
            }
            
            // Check position - only if truly empty
            if (empty($position)) {
                $missingFields[] = 'Position';
            }
            
            if (!empty($missingFields)) {
                $fieldsList = implode(', ', $missingFields);
                return $this->errorResponse('There is insufficient data of this applicant, please fill up these fields: ' . $fieldsList, 400);
            }

            // Load JSON reference data and convert address codes to names
            try {
                $province_data = $this->loadJsonRecords('refprovince.json');
                $city_data     = $this->loadJsonRecords('refcitymun.json');
                $brgy_data     = $this->loadJsonRecords('refbrgy.json');

                $ra_province = $this->convertAddressCode(
                    (string)($applicant->ra_province ?? ''), $province_data, 'provCode', 'provDesc'
                );
                $ra_city = $this->convertAddressCode(
                    (string)($applicant->ra_city ?? ''), $city_data, 'citymunCode', 'citymunDesc'
                );
                $ra_brgy = $this->convertAddressCode(
                    (string)($applicant->ra_barangay ?? ''), $brgy_data, 'brgyCode', 'brgyDesc'
                );

                $address = [
                    'ra_province' => $ra_province,
                    'pa_province' => $ra_province,
                    'ra_city'     => $ra_city,
                    'pa_city'     => $ra_city,
                    'ra_brgy'     => $ra_brgy,
                    'pa_brgy'     => $ra_brgy,
                ];
            } catch (\Exception $e) {
                // Use raw values as fallback
                $address = [
                    'ra_province' => $applicant->ra_province ?? '',
                    'pa_province' => $applicant->ra_province ?? '',
                    'ra_city'     => $applicant->ra_city ?? '',
                    'pa_city'     => $applicant->ra_city ?? '',
                    'ra_brgy'     => $applicant->ra_barangay ?? '',
                    'pa_brgy'     => $applicant->ra_barangay ?? '',
                ];
            }

            $signatories = array(
                'signatory' => $request->signatory ?? '',
                'position' => $request->position ?? '',
            );

            $employee_name = strtoupper($oath_of_offices[0]->name ?? '');
            $position = $oath_of_offices[0]->position ?? '';
            $address_city = $address['ra_city'] ?? '';
            $address_province = $address['ra_province'] ?? '';
            $signatory_name = $signatories['signatory'] ?? '';

            // Format current date
            $current_day = date('d');
            $current_month_year = date('M Y');

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(10); // Minimized font size

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(0.6),
                'marginRight' => Converter::inchToTwip(1),
                'marginBottom' => Converter::inchToTwip(0.6),
                'marginLeft' => Converter::inchToTwip(1),
                'pageSizeW' => Converter::inchToTwip(8.27),
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

            // Paragraph styles with first line indentation
            $phpWord->addParagraphStyle('left', [
                'alignment' => WordJc::START,
                'spaceAfter' => 0,
            ]);

            $phpWord->addParagraphStyle('center', [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
            ]);

            $phpWord->addParagraphStyle('justifyIndent', [
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 0,
                'indentation' => ['firstLine' => Converter::inchToTwip(0.3)],
            ]);

            $phpWord->addParagraphStyle('leftIndent', [
                'alignment' => WordJc::START,
                'spaceAfter' => 0,
                'indentation' => ['firstLine' => Converter::inchToTwip(0.3)],
            ]);

            // CS Form header
            $section->addText('CS Form No. 32', ['italic' => true, 'size' => 10], 'left');
            $section->addText('Revised 2018', ['italic' => true, 'size' => 10], 'left');
            $section->addTextBreak(1);

            // Header section
            $section->addText('Republic of the Philippines', ['size' => 10], 'center');
            $section->addText(CompanyHelper::getName(), ['size' => 10], 'center');
            $section->addText(CompanyHelper::getAddress(), ['size' => 10], 'center');
            $section->addTextBreak(1);

            // Title
            $section->addText('OATH OF OFFICE', ['size' => 10, 'bold' => true], 'center');
            $section->addTextBreak(1);

            // Main oath text with indentation
            $oathText = "I, {$employee_name} of {$address_city}, {$address_province} having been appointed to the position {$position} hereby solemnly swear, that I will faithfully discharge to the best of my ability, the duties of my present position and of all others that I may hereafter hold under the Republic of the Philippines; that I will bear true faith and allegiance to the same; that I will obey the laws, legal orders, and decrees promulgated by the duly constituted authorities of the Republic of the Philippines; and that I impose this obligation upon myself voluntarily, without mental reservation or purpose of evasion.";

            $oathRun = $section->addTextRun('justifyIndent');
            $oathRun->addText('I, ', ['size' => 10]);
            $oathRun->addText($employee_name, ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $oathRun->addText(' of ', ['size' => 10]);
            $oathRun->addText("{$address_city}, {$address_province}", ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $oathRun->addText(' having been appointed to the position ', ['size' => 10]);
            $oathRun->addText($position, ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $oathRun->addText(' hereby solemnly swear, that I will faithfully discharge to the best of my ability, the duties of my present position and of all others that I may hereafter hold under the Republic of the Philippines; that I will bear true faith and allegiance to the same; that I will obey the laws, legal orders, and decrees promulgated by the duly constituted authorities of the Republic of the Philippines; and that I impose this obligation upon myself voluntarily, without mental reservation or purpose of evasion.', ['size' => 10]);

            $section->addTextBreak(1);

            // SO HELP ME GOD with indentation
            $section->addText('SO HELP ME GOD.', ['size' => 10], 'leftIndent');
            $section->addTextBreak(1);

            // Signature section (right aligned)
            $signatureTable = $section->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 0,
            ]);
            $signatureTable->addRow();
            $signatureTable->addCell(Converter::inchToTwip(3.5));
            $signatureCell = $signatureTable->addCell(Converter::inchToTwip(3.5));
            $signatureCell->addText($employee_name, ['size' => 10, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER]);
            $signatureCell->addText('(Signature over Printed Name', ['size' => 10], ['alignment' => WordJc::CENTER]);
            $signatureCell->addText('of the Appointee)', ['size' => 10], ['alignment' => WordJc::CENTER]);

            $section->addTextBreak(2);

            // Government ID section
            $section->addText('Government ID: ______________', ['size' => 10], 'left');
            $section->addText('ID Number: _________________', ['size' => 10], 'left');
            $section->addText('Date Issued: ________________', ['size' => 10], 'left');
            $section->addTextBreak(1);

            // Subscribed and sworn section with border
            $swornTable = $section->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => Converter::inchToTwip(0.05),
            ]);
            $swornTable->addRow();
            $swornCell = $swornTable->addCell();
            $swornRun = $swornCell->addTextRun('leftIndent');
            $swornRun->addText('Subscribed and sworn before me this ', ['size' => 10]);
            $swornRun->addText($current_day, ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $swornRun->addText(' day of ', ['size' => 10]);
            $swornRun->addText($current_month_year, ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $swornRun->addText(' in ', ['size' => 10]);
            $swornRun->addText(date('Y'), ['size' => 10, 'bold' => true, 'underline' => 'single']);

            $section->addTextBreak(1);

            // Signatory section (right aligned)
            $signatoryTable = $section->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 0,
            ]);
            $signatoryTable->addRow();
            $signatoryTable->addCell(Converter::inchToTwip(3.5));
            $signatoryCell = $signatoryTable->addCell(Converter::inchToTwip(3.5));
            $signatoryCell->addText($signatory_name, ['size' => 10, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER]);
            $signatoryCell->addText('(Signature over Printed Name', ['size' => 10], ['alignment' => WordJc::CENTER]);
            $signatoryCell->addText('of Person Administering the Oath)', ['size' => 10], ['alignment' => WordJc::CENTER]);

            $filename = 'oath_of_office_' . $employee_name . '_' . date('Y-m-d') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Check if it's a SQL error (missing columns) or data validation error
            if (strpos($e->getMessage(), 'Invalid column name') !== false ||
                strpos($e->getMessage(), 'Reference JSON not found') !== false || 
                strpos($e->getMessage(), 'Undefined array key') !== false ||
                strpos($e->getMessage(), 'Trying to get property') !== false ||
                strpos($e->getMessage(), 'SQLSTATE') !== false) {
                return $this->errorResponse('There is insufficient data of this applicant, please fill up these fields: Address, First Name, Last Name, and Position', 400);
            }
            return $this->serverErrorResponse('Failed to generate oath of office DOCX: ' . $e->getMessage());
        }
    }
}
