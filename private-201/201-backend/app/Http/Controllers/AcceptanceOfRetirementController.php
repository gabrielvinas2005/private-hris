<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

class AcceptanceOfRetirementController extends Controller
{
    use ApiResponse;

    /**
     * Resolve and load reference JSON files robustly.
     */
    protected function loadJsonRecords(string $filename, string $recordsKey = 'RECORDS') : array
    {
        $candidates = [
            base_path($filename),
            resource_path($filename),
            resource_path('data/' . $filename),
            public_path($filename),
            base_path('public/' . $filename),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $json = json_decode(file_get_contents($path), true);
                return is_array($json) && isset($json[$recordsKey]) ? $json[$recordsKey] : ($json ?? []);
            }
        }

        throw new \RuntimeException("Reference JSON not found: " . $filename);
    }

    /**
     * Load one retirement employee for print/word (avoids duplicate rows from joins).
     */
    protected function getRetirementEmployeeForPrint(int $employeeId): ?object
    {
        $app_key = env("APP_KEY", "");

        return DB::table('employees as a')
            ->join('employee_offboardings as b', 'a.id', '=', 'b.employee_id')
            ->leftJoin('positions as c', 'a.position_id', '=', 'c.id')
            ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
            ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
            ->leftJoin('genders as g', 'g.id', '=', 'a.gender_id')
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
                'a.gender_id',
                'g.name as gender',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                'b.date_effectivity',
                'c.name as position',
                'd.name as department',
                'e.name as name_prefix',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            CONCAT(a.first_name,' ',a.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                        END as name"),
                'cp.name as company'
            )
            ->where('a.id', $employeeId)
            ->where('b.reactivated_status_id', 0)
            ->where('b.nature_id', 2)
            ->orderByDesc('b.id')
            ->first();
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

    public function index(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('employees')
                ->join('employee_offboardings', 'employee_offboardings.employee_id', '=', 'employees.id')
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
                ->where('employee_offboardings.nature_id', 2) // Only Retirement (nature_id = 2)
                ->paginate(10000);

            if (!$data || $data->isEmpty()) {
                return $this->errorResponse('Employee not found.', 404);
            }

            return $this->successResponse($data, 'Acceptance of retirement data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve acceptance of retirement data: ' . $e->getMessage());
        }
    }

    public function generatePdf(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'date' => 'required|date',
                'effectivity_date' => 'required|date|after_or_equal:received_date|after_or_equal:date',
                'signatory' => 'required|string',
                'position1' => 'required|string',
                'received_date' => 'required|date',
                'received_signatory' => 'required|string',
                'employee' => 'required|exists:employees,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $employee = $this->getRetirementEmployeeForPrint((int) $request->employee);
            if (!$employee) {
                return $this->errorResponse('Employee not found.', 404);
            }
            $employees = collect([$employee]);

            // Load reference JSON data with robust path resolution
            $region_data = array_filter($this->loadJsonRecords('refregion.json'));
            $province_data = array_filter($this->loadJsonRecords('refprovince.json'));
            $city_data = array_filter($this->loadJsonRecords('refcitymun.json'));
            $brgy_data = array_filter($this->loadJsonRecords('refbrgy.json'));

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
                'effectivity_date' => $request->effectivity_date ?? $employees[0]->date_effectivity ?? now(),
            ];

            $pdf = PDF::loadView(
                'acceptance_of_retirement.acceptance_of_retirement_print',
                compact('employees', 'signatories',
                'companies', 'address')
            )
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'acceptance_of_retirement_' . $request->employee . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate acceptance of retirement PDF: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        // Keep the original print method for backward compatibility
        return $this->generatePdf($request);
    }

    public function generateWord(Request $request)
{
    try {
        $validator = validator($request->all(), [
            'date' => 'required|date',
            'effectivity_date' => 'required|date|after_or_equal:received_date|after_or_equal:date',
            'signatory' => 'required|string',
            'position1' => 'required|string',
            'received_date' => 'required|date',
            'received_signatory' => 'required|string',
            'employee' => 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $employee = $this->getRetirementEmployeeForPrint((int) $request->employee);
        if (!$employee) {
            return $this->errorResponse('Employee not found');
        }
        $employees = collect([$employee]);

        // Load address data with error handling
        try {
            $region_data = $this->loadJsonRecords('refregion.json');
            $province_data = $this->loadJsonRecords('refprovince.json');
            $city_data = $this->loadJsonRecords('refcitymun.json');
            $brgy_data = $this->loadJsonRecords('refbrgy.json');

            $emp = $employee;
            $address = [
                'ra_region' => optional(collect($region_data)->firstWhere('regCode', $emp->ra_region))['regDesc'] ?? '',
                'pa_region' => optional(collect($region_data)->firstWhere('regCode', $emp->pa_region))['regDesc'] ?? '',
                'ra_province' => optional(collect($province_data)->firstWhere('provCode', $emp->ra_province))['provDesc'] ?? '',
                'pa_province' => optional(collect($province_data)->firstWhere('provCode', $emp->pa_province))['provDesc'] ?? '',
                'ra_city' => optional(collect($city_data)->firstWhere('citymunCode', $emp->ra_city))['citymunDesc'] ?? '',
                'pa_city' => optional(collect($city_data)->firstWhere('citymunCode', $emp->pa_city))['citymunDesc'] ?? '',
                'ra_brgy' => optional(collect($brgy_data)->firstWhere('brgyCode', $emp->ra_barangay))['brgyDesc'] ?? '',
                'pa_brgy' => optional(collect($brgy_data)->firstWhere('brgyCode', $emp->pa_barangay))['brgyDesc'] ?? ''
            ];
        } catch (\Exception $e) {
            \Log::warning('Address data loading failed: ' . $e->getMessage());
            $address = [
                'ra_region' => '', 'pa_region' => '', 'ra_province' => '', 'pa_province' => '',
                'ra_city' => '', 'pa_city' => '', 'ra_brgy' => '', 'pa_brgy' => ''
            ];
        }

        $companies = DB::table('companies')->get();
        $signatories = [
            'signatory' => $request->signatory,
            'position1' => $request->position1,
            'received_date' => $request->received_date,
            'received_signatory' => $request->received_signatory,
            'date' => $request->date,
            'effectivity_date' => $request->effectivity_date ?? $employees[0]->date_effectivity ?? now(),
        ];

        // Create Word document directly using PhpWord (NO HTML)
        $phpWord = $this->createAcceptanceWordDocument($employees[0], $signatories, $companies, $address);

        $filename = 'acceptance_of_retirement_' . $request->employee . '_' . date('Y-m-d') . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'acceptance_retirement_');
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        if (!file_exists($tempFile)) {
            throw new \Exception('Failed to create Word document file');
        }

        $wordContent = file_get_contents($tempFile);
        unlink($tempFile);

        return response($wordContent)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($wordContent));

    } catch (\Exception $e) {
        \Log::error('Word document generation failed: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return $this->serverErrorResponse('Failed to generate acceptance of retirement Word document: ' . $e->getMessage());
    }
}

/**
 * Create the Word document directly using PhpWord methods
 */
private function createAcceptanceWordDocument($employee, $signatories, $companies, $address)
{
    $phpWord = new PhpWord();
    
    // Set document properties
    $section = $phpWord->addSection([
        'marginTop' => 720,    // Move header up (~1 inch)
        'marginBottom' => 1134,
        'marginLeft' => 1134,
        'marginRight' => 1134,
    ]);

    // Define text styles (force black color to avoid link-like blue text)
    // Smaller size for CS Form No. 10 / Series of 2017 to match sample
    $italicStyle = ['italic' => true, 'size' => 9, 'name' => 'Arial', 'color' => '000000'];
    $normalStyle = ['size' => 12, 'name' => 'Arial', 'color' => '000000'];
    $boldStyle = ['bold' => true, 'size' => 12, 'name' => 'Arial', 'color' => '000000'];
    $centerBoldStyle = ['bold' => true, 'size' => 14, 'name' => 'Arial', 'color' => '000000'];
    $underlineStyle = ['underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE, 'size' => 12, 'name' => 'Arial', 'color' => '000000'];
    $boldUnderlineStyle = ['bold' => true, 'underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE, 'size' => 12, 'name' => 'Arial', 'color' => '000000'];

    // Alignment styles
    $centerAlign = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];
    $rightAlign = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::END];
    $justifyAlign = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH];

    // 1. CS Form header (top-left)
    $section->addText('CS Form No. 10', $italicStyle);
    $section->addText('Series of 2017', $italicStyle);
    $section->addTextBreak(3);

    // 2. Company header - centered
    $section->addText('Republic of the Philippines', ['bold' => true, 'size' => 12, 'name' => 'Arial', 'color' => '000000'], $centerAlign);
    $section->addText($companies->isNotEmpty() ? $companies[0]->name : 'CompanyHelper::getName()', ['bold' => true, 'size' => 12, 'name' => 'Arial', 'color' => '000000'], $centerAlign);
    $section->addTextBreak(2);

    // 3. Title - centered
    $section->addText('ACCEPTANCE OF RETIREMENT', ['bold' => true, 'size' => 12, 'name' => 'Arial', 'color' => '000000'], $centerAlign);
    $section->addTextBreak(2);

    // 4. Date - right aligned
    $section->addText('Date: ' . date('F d, Y', strtotime($signatories['date'])), $underlineStyle, $rightAlign);
    $section->addTextBreak(1);

    // 5. Employee name - bold and underlined
    $employeeName = strtoupper(($employee->name_prefix ?? 'MRS.') . ' ' . $employee->name);
    $section->addText($employeeName, $boldUnderlineStyle);

    // 6. Employee address - underlined
    $fullAddress = '';
    $fullAddress .= ($employee->pa_house_no ? $employee->pa_house_no . ', ' : '');
    $fullAddress .= ($employee->pa_village ? $employee->pa_village . ', ' : '');
    $fullAddress .= ($employee->pa_street ? $employee->pa_street . ', ' : '');
    $fullAddress .= 'Brgy. ' . ($address['pa_brgy'] ?: '') . ', ';
    $fullAddress .= ($address['pa_city'] ?: '') . ', ';
    $fullAddress .= ($address['pa_province'] ?: '') . ', ';
    $fullAddress .= ($address['pa_region'] ?: '');
    
    $section->addText($fullAddress, $underlineStyle);
    $section->addTextBreak(1);

    // 7. Salutation


    // 8. First paragraph - justified with first-line indent
    $paragraph1 = $section->addTextRun(array_merge($justifyAlign, ['indentation' => ['firstLine' => 720]]));
    $paragraph1->addText('In reply to your letter dated ', $normalStyle);
    $paragraph1->addText(date('F d, Y', strtotime($signatories['date'])), $underlineStyle);
    $paragraph1->addText(' tendering your retirement from the position of ', $normalStyle);
    $paragraph1->addText(strtoupper($employee->position ?? 'COA-AUDITOR'), $underlineStyle);
    $paragraph1->addText(' in ', $normalStyle);
    $paragraph1->addText(strtoupper($employee->department ?? 'MTSICT'), $underlineStyle);
    $paragraph1->addText(', may I inform you that the same is hereby accepted to take effect on ', $normalStyle);
    $paragraph1->addText(date('F d, Y', strtotime($signatories['effectivity_date'] ?? $employee->date_effectivity ?? now())), $underlineStyle);
    $paragraph1->addText('.', $normalStyle);

    // 9. Second paragraph - justified with first-line indent
    $paragraph2 = $section->addTextRun(array_merge($justifyAlign, ['indentation' => ['firstLine' => 720]]));
    $paragraph2->addText('Your services while employed from this Office have been rated as ', $normalStyle);
    $paragraph2->addText($employee->adjectival_rating ?? '', $underlineStyle);
    $paragraph2->addText(' for your reference.', $normalStyle);

    $table = $section->addTable([
        'borderSize' => 0,
        'borderColor' => 'FFFFFF',
        'borderInsideHSize' => 0,
        'borderInsideVSize' => 0,
        'borderInsideHColor' => 'FFFFFF',
        'borderInsideVColor' => 'FFFFFF',
        'cellMargin' => 0,
        'cellMarginLeft' => 0,
        'cellMarginRight' => 0,
        'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
    ]);

    $table->addRow();
    $cellStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF'];
    $leftCell = $table->addCell(7000, $cellStyle);
    $rightCell = $table->addCell(7000, $cellStyle);

    // Left cell: Acknowledgement (add padding at top of cell to sit lower than signatory)
    $leftCell->addTextBreak(5);
    $rbRun = $leftCell->addTextRun();
    $rbRun->addText('Received By: ', $boldStyle);
    $rbRun->addText(($signatories['received_signatory'] ?? ''), $boldUnderlineStyle);
    $leftCell->addText('Signature over Printed Name', ['size' => 10, 'name' => 'Arial']);
    $dateRun = $leftCell->addTextRun();
    $dateRun->addText('Date: ', $normalStyle);
    $dateRun->addText(date('F d, Y', strtotime($signatories['received_date'])), $underlineStyle);
  

    // Right cell: Signatory block aligned right
    $rightCell->addText('Very truly yours,', $normalStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::END]);
    $rightCell->addTextBreak(1);
    $rightCell->addText($signatories['signatory'] ?? '', $boldUnderlineStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::END]);
    $rightCell->addText($signatories['position1'] ?? '', $normalStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::END]);

    return $phpWord;
}


public function previewWord(Request $request)
{
    try {
        $validator = validator($request->all(), [
            'date' => 'required|date',
            'effectivity_date' => 'required|date|after_or_equal:received_date|after_or_equal:date',
            'signatory' => 'required|string',
            'position1' => 'required|string',
            'received_date' => 'required|date',
            'received_signatory' => 'required|string',
            'employee' => 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $employee = $this->getRetirementEmployeeForPrint((int) $request->employee);
        if (!$employee) {
            return $this->errorResponse('Employee not found');
        }
        $employees = collect([$employee]);

        // Your existing address processing code...
        try {
            $region_data = $this->loadJsonRecords('refregion.json');
            $province_data = $this->loadJsonRecords('refprovince.json');
            $city_data = $this->loadJsonRecords('refcitymun.json');
            $brgy_data = $this->loadJsonRecords('refbrgy.json');

            $emp = $employee;
            $address = [
                'ra_region' => optional(collect($region_data)->firstWhere('regCode', $emp->ra_region))['regDesc'] ?? '',
                'pa_region' => optional(collect($region_data)->firstWhere('regCode', $emp->pa_region))['regDesc'] ?? '',
                'ra_province' => optional(collect($province_data)->firstWhere('provCode', $emp->ra_province))['provDesc'] ?? '',
                'pa_province' => optional(collect($province_data)->firstWhere('provCode', $emp->pa_province))['provDesc'] ?? '',
                'ra_city' => optional(collect($city_data)->firstWhere('citymunCode', $emp->ra_city))['citymunDesc'] ?? '',
                'pa_city' => optional(collect($city_data)->firstWhere('citymunCode', $emp->pa_city))['citymunDesc'] ?? '',
                'ra_brgy' => optional(collect($brgy_data)->firstWhere('brgyCode', $emp->ra_barangay))['brgyDesc'] ?? '',
                'pa_brgy' => optional(collect($brgy_data)->firstWhere('brgyCode', $emp->pa_barangay))['brgyDesc'] ?? ''
            ];
        } catch (\Exception $e) {
            $address = [
                'ra_region' => '', 'pa_region' => '', 'ra_province' => '', 'pa_province' => '',
                'ra_city' => '', 'pa_city' => '', 'ra_brgy' => '', 'pa_brgy' => ''
            ];
        }

        $companies = DB::table('companies')->get();
        $signatories = [
            'signatory' => $request->signatory,
            'position1' => $request->position1,
            'received_date' => $request->received_date,
            'received_signatory' => $request->received_signatory,
            'date' => $request->date,
            'effectivity_date' => $request->effectivity_date ?? $employees[0]->date_effectivity ?? now(),
        ];

        // Create Word document directly without HTML
        $phpWord = $this->createAcceptanceWordDocument($employees[0], $signatories, $companies, $address);

        $filename = 'acceptance_of_retirement_preview_' . $request->employee . '_' . date('Y-m-d') . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'acceptance_retirement_');
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        $wordContent = file_get_contents($tempFile);
        unlink($tempFile);

        return response($wordContent)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Content-Length', strlen($wordContent));

    } catch (\Exception $e) {
        \Log::error('Word preview generation failed: ' . $e->getMessage());
        return $this->serverErrorResponse('Failed to generate Word preview: ' . $e->getMessage());
    }
}

}
