<?php

namespace App\Http\Controllers;

use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \NumberFormatter;
use App\Traits\ApiResponse;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;

class AppointmentCertificateWordController extends Controller
{
    use ApiResponse;

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

        throw new \RuntimeException('Reference JSON not found: ' . $filename);
    }

    /**
     * Convert '&' symbol to 'and' word in text
     */
    protected function convertAmpersand($text)
    {
        if (is_null($text) || $text === '') {
            return $text;
        }
        return str_replace('&', 'and', $text);
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

    /**
     * Generate appointment certificate as DOCX using PhpWord.
     */
    public function word(Request $request)
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
                    DB::raw("COALESCE(g.name, 'Original') as nature"),
                    'h.name as employment_type',
                    'j.code'
                )
                ->where(function($query) use ($request) {
                    $query->where('b.id', $request->employee)
                        ->orWhere('a.id', $request->employee);
                })
                ->get();

            if ($appointments->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
            $salary_word = $f->format(isset($appointments[0]->salary) ? $appointments[0]->salary : 0);

            // Use the first matched record for output fields (including the computed `name`)
            $appointment = $appointments->first();

            $natureInput = trim((string) $request->input('nature', ''));
            if ($natureInput !== '') {
                foreach ($appointments as $appt) {
                    $appt->nature = $natureInput;
                }
            }

            // Load reference data
            $region_data = array_filter($this->loadJsonRecords('refregion.json'));
            $province_data = array_filter($this->loadJsonRecords('refprovince.json'));
            $city_data = array_filter($this->loadJsonRecords('refcitymun.json'));
            $brgy_data = array_filter($this->loadJsonRecords('refbrgy.json'));

            $ra_region = collect($region_data)->where("regCode", isset($appointments[0]->ra_region) ? $appointments[0]->ra_region : '')->all();
            $pa_region = collect($region_data)->where("regCode", isset($appointments[0]->pa_region) ? $appointments[0]->pa_region : '')->all();
            $ra_province = collect($province_data)->where("provCode", isset($appointments[0]->ra_province) ? $appointments[0]->ra_province : '')->all();
            $pa_province = collect($province_data)->where("provCode", isset($appointments[0]->pa_province) ? $appointments[0]->pa_province : '')->all();
            $ra_city = collect($city_data)->where("citymunCode", isset($appointments[0]->ra_city) ? $appointments[0]->ra_city : '')->all();
            $pa_city = collect($city_data)->where("citymunCode", isset($appointments[0]->pa_city) ? $appointments[0]->pa_city : '')->all();
            $ra_brgy = collect($brgy_data)->where("brgyCode", isset($appointments[0]->ra_barangay) ? $appointments[0]->ra_barangay : '')->all();
            $pa_brgy = collect($brgy_data)->where("brgyCode", isset($appointments[0]->pa_barangay) ? $appointments[0]->pa_barangay : '')->all();

            // Get address indexes
            for ($i = 0; $i <= count($region_data); $i++) {
                if (isset($ra_region[$i]['regDesc'])) $ra_region_id = $i;
            }
            for ($i = 0; $i <= count($region_data); $i++) {
                if (isset($pa_region[$i]['regDesc'])) $pa_region_id = $i;
            }
            for ($i = 0; $i <= count($province_data); $i++) {
                if (isset($ra_province[$i]['provDesc'])) $ra_province_id = $i;
            }
            for ($i = 0; $i <= count($province_data); $i++) {
                if (isset($pa_province[$i]['provDesc'])) $pa_province_id = $i;
            }
            for ($i = 0; $i <= count($city_data); $i++) {
                if (isset($ra_city[$i]['citymunDesc'])) $ra_city_id = $i;
            }
            for ($i = 0; $i <= count($city_data); $i++) {
                if (isset($pa_city[$i]['citymunDesc'])) $pa_city_id = $i;
            }
            for ($i = 0; $i <= count($brgy_data); $i++) {
                if (isset($ra_brgy[$i]['brgyDesc'])) $ra_brgy_id = $i;
            }
            for ($i = 0; $i <= count($brgy_data); $i++) {
                if (isset($pa_brgy[$i]['brgyDesc'])) $pa_brgy_id = $i;
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
                'signatory' => $request->signatory ?? '',
                'position' => $request->position ?? '',
                'vice' => $request->vice ?? '',
                'who' => $request->who ?? '',
                'note' => $request->note ?? '',
                'cs_date' => $request->cs_date ?? '',
                'hrmo' => $request->hrmo ?? '',
                'hrmpsb' => $request->hrmpsb ?? '',
                'publish_at' => $request->publish_at ?? '',
                'publish_from' => $request->publish_from ?? '',
                'publish_to' => $request->publish_to ?? '',
                'posted_at' => $request->posted_at ?? '',
                'posted_from' => $request->posted_from ?? '',
                'posted_to' => $request->posted_to ?? '',
                'started_on' => $request->started_on ?? '',
                'deliberation_on' => $request->deliberation_on ?? '',
            );

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('times new roman');
            $phpWord->setDefaultFontSize(9); // Minimized font size

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(0.5),      // 0.5 inches
                'marginRight' => Converter::inchToTwip(0.5),    // 0.5 inches
                'marginBottom' => Converter::inchToTwip(0.5),   // 0.5 inches
                'marginLeft' => Converter::inchToTwip(0.5),     // 0.5 inches
                'pageSizeW' => Converter::inchToTwip(8.27),
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

            // Paragraph styles with first line indentation
            $phpWord->addParagraphStyle('indent', [
                'indentation' => ['firstLine' => Converter::inchToTwip(0.5)],
                'spaceAfter' => 0,
            ]);

            // Paragraph style to center Authorized Official text relative to underline (text is longer, needs right shift)
            $phpWord->addParagraphStyle('authLabelCenter', [
                'indentation' => ['left' => Converter::inchToTwip(0.5)],
                'spaceAfter' => 0,
            ]);

            // Paragraph style to center Date text relative to underline (text is shorter, needs right shift)
            $phpWord->addParagraphStyle('dateLabelCenter', [
                'indentation' => ['left' => Converter::inchToTwip(1.0)],
                'spaceAfter' => 0,
            ]);



            // 1st PAGE SECTION ---------------------------------------------------------------
            // --------------------------------------------------------------------------------
            // --------------------------------------------------------------------------------

            // "For Accredited/Deregulated Agencies" textbox at top right
            $regAgenciesTable = $section->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 0,
                'width' => 100 * 50, // Full width
                'unit' => 'pct',
            ]);
            $regAgenciesTable->addRow();

            // Left empty cell to push content to the right
            $regAgenciesTable->addCell(Converter::inchToTwip(8)); // Adjust width as needed

            // Right cell containing the bordered box with text
            $regAgenciesCell = $regAgenciesTable->addCell(Converter::inchToTwip(2), [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
            ]);
            
            $regAgenciesCell = $regAgenciesTable->addCell(Converter::inchToTwip(5), [
                'borderTopSize' => 0,
                'borderTopColor' => 'FFFFFF',
                'borderLeftSize' => 0,
                'borderLeftColor' => 'FFFFFF',
                'borderRightSize' => 0,
                'borderRightColor' => 'FFFFFF',
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
                'valign' => 'center', // Vertically center the content
            ]);
            
            $regAgenciesCell->addText(
                'For Accredited/Deregulated Agencies', 
                ['size' => 8, 'italic' => true, 'bold' => true], 
                [
                    'alignment' => WordJc::CENTER,
                    'spaceAfter' => Converter::inchToTwip(0.05),
                    'spaceBefore' => Converter::inchToTwip(0.05),
                    'spacing' => 0,
                ]
            );

            // Add another row with 1 column that spans all columns
            $regAgenciesTable->addRow();
            $mainCell = $regAgenciesTable->addCell(null, [
                'borderSize' => 6,
                'borderColor' => '000000',
                'bgColor' => '808080',
                'gridSpan' => 3, // Merge across 3 columns
                'cellMargin' => Converter::pixelToTwip(15), // 10px padding
            ]);

            // Add a new table inside the mainCell with margin
            $innerTable = $mainCell->addTable([
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMargin' => Converter::pixelToTwip(15), // 15px padding on inner table cells
            ]);

            // Add rows to the inner table
            $innerTable->addRow();
            $innerCell1 = $innerTable->addCell(null, [
                'bgColor' => '808080', // Gray background
            ]);

            // Add another table inside innerCell1
            $nestedTable = $innerCell1->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMargin' => Converter::pixelToTwip(10), // 15px padding on inner table cells
            ]);

            // Add rows to the nested table
            $nestedTable->addRow();
            $nestedCell = $nestedTable->addCell(null, [
                'bgColor' => 'FFFFFF', // White background
                
            ]);
            $textRun = $nestedCell->addTextRun(['lineHeight' => 1]);
            $textRun->addText('CS Form No. 33-B', ['size' => 10, 'bold' => true, 'italic' => true]);
            $textRun->addTextBreak();
            $textRun->addText('Revised 2025', ['size' => 9, 'italic' => true, 'bold' => false]);

            // Add some space
            $nestedCell->addTextBreak(0.5);

            // Add the stamp text aligned to the right
            $nestedCell->addText(
                '(Stamp of Date of Receipt)', 
                ['size' => 8, 'italic' => true], 
                [
                    'alignment' => WordJc::END, // Right align
                    'indentation' => ['right' => Converter::pixelToTwip(10)] // 10px padding right
                ]
            );
            
            // Add some space
            $nestedCell->addTextBreak(1);

            // Add Republic of the Philippines section - centered using TextRun
            $textRun2 = $nestedCell->addTextRun(['alignment' => WordJc::CENTER, 'lineHeight' => 1]);
            $textRun2->addText('Republic of the Philippines', ['size' => 12, 'bold' => true]);
            $textRun2->addTextBreak();
            $textRun2->addText(CompanyHelper::getName(), ['size' => 12]);
            $textRun2->addTextBreak();
            $textRun2->addText(CompanyHelper::getAddress(), ['size' => 12]);

            // Add some space
            $nestedCell->addTextBreak(2);

            $textRun3 = $nestedCell->addTextRun(['lineHeight' => 1]);
            $textRun3->addText('Mr./Mrs./Ms.: ', ['size' => 10]);
            $textRun3->addText(strtoupper($this->convertAmpersand($appointment->name ?? '')), ['size' => 10, 'bold' => true]);

            // Add some space
            $nestedCell->addTextBreak(0.5);

            $totalWidth = Converter::inchToTwip(7.27);

            $appointmentTable = $nestedCell->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 0,
                'cellMarginRight' => 0,
            ]);

            $appointmentTable->addRow();

            $appt_c1 = $appointmentTable->addCell($totalWidth * 0.27, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt_c1->addText('     You are hereby appointed as', ['size' => 10], ['alignment' => WordJc::LEFT]);

            $appt_c2 = $appointmentTable->addCell($totalWidth * 0.57, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt_c2->addText($this->convertAmpersand($appointment->position ?? ''), ['size' => 9, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            $appt_c2->addText('(Position Title)', ['size' => 8, 'italic' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);

            $appt_c3 = $appointmentTable->addCell($totalWidth * 0.11, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt_c3->addText('(SG/JG/PG)', ['size' => 10], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);

            $appt_c4 = $appointmentTable->addCell($totalWidth * 0.05, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt_c4->addText(($appointment->salary_grade_id ?? '') . ' / ' . ($appointment->salary_step_id ?? ''), ['size' => 9, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);

            $appointmentTable2 = $nestedCell->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 0,
                'cellMarginRight' => 0,
            ]);

            $appointmentTable2->addRow();

            $appt2_c1 = $appointmentTable2->addCell($totalWidth * 0.06, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt2_c1->addText('under', ['size' => 10], ['alignment' => WordJc::LEFT]);

            $appt2_c2 = $appointmentTable2->addCell($totalWidth * 0.20, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt2_c2->addText($appointment->employment_type ?? '', ['size' => 9, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            $appt2_c2->addText('(Permanent, Temporary, etc)', ['size' => 8, 'italic' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);

            $appt2_c3 = $appointmentTable2->addCell($totalWidth * 0.11, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt2_c3->addText('status at the', ['size' => 10], ['alignment' => WordJc::CENTER]);

            $appt2_c4 = $appointmentTable2->addCell($totalWidth * 0.63, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt2_c4->addText($appointment->department ?? '', ['size' => 9, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            $appt2_c4->addText('(Department)', ['size' => 8, 'italic' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);

            $appointmentTable3 = $nestedCell->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 0,
                'cellMarginRight' => 0,
            ]);

            $appointmentTable3->addRow();

            $appt3_c1 = $appointmentTable3->addCell($totalWidth * 0.24, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt3_c1->addText('with a compensation rate of', ['size' => 10], ['alignment' => WordJc::LEFT]);

            $appt3_c2 = $appointmentTable3->addCell($totalWidth * 0.60, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt3_c2->addText(strtoupper($salary_word), ['size' => 9, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER]);

            $appt3_c3 = $appointmentTable3->addCell($totalWidth * 0.04, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt3_c3->addText('Php', ['size' => 10], ['alignment' => WordJc::CENTER]);

            $appt3_c4 = $appointmentTable3->addCell($totalWidth * 0.12, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt3_c4->addText(number_format($appointment->salary ?? 0, 2, '.', ','), ['size' => 9, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER]);

            $appointmentTable3->addRow();

            $appt3_r2c1 = $appointmentTable3->addCell($totalWidth, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'gridSpan' => 4,
            ]);
            $appt3_r2c1->addText('pesos per month.', ['size' => 10], ['alignment' => WordJc::LEFT]);

            $appointmentTable3->addRow();

            $appt3_r3c1 = $appointmentTable3->addCell($totalWidth, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'gridSpan' => 4,
            ]);
            $appt3_r3c1->addText('', ['size' => 10], ['alignment' => WordJc::LEFT, 'spaceAfter' => 0, 'spaceBefore' => 0]); //empty cell

            // Add some space
            $nestedCell->addTextBreak(0.5);

            $appointmentTable4 = $nestedCell->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 0,
                'cellMarginRight' => 0,
            ]);

            $appointmentTable4->addRow();

            $appt4_c1 = $appointmentTable4->addCell($totalWidth * 0.31, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt4_c1->addText('     The nature of this appointment is', ['size' => 10], ['alignment' => WordJc::LEFT]);
            
            $appt4_c2 = $appointmentTable4->addCell($totalWidth * 0.32, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt4_c2->addText($this->convertAmpersand($appointment->nature ?? ''), ['size' => 9, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            $appt4_c2->addText('(Original, Promotion, etc)', ['size' => 7, 'italic' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            
            $appt4_c3 = $appointmentTable4->addCell($totalWidth * 0.05, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt4_c3->addText('vice', ['size' => 10], ['alignment' => WordJc::CENTER]);
            
            $appt4_c4 = $appointmentTable4->addCell($totalWidth * 0.32, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt4_c4->addText($this->convertAmpersand($signatories['vice'] ?? ''), ['size' => 9, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER]);

            $appointmentTable5 = $nestedCell->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 0,
                'cellMarginRight' => 0,
            ]);
            
            $appointmentTable5->addRow();
            
            $appt5_c1 = $appointmentTable5->addCell($totalWidth * 0.04, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt5_c1->addText('who', ['size' => 10], ['alignment' => WordJc::LEFT]);
            
            $appt5_c2 = $appointmentTable5->addCell($totalWidth * 0.33, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt5_c2->addText($this->convertAmpersand($signatories['who'] ?? ''), ['size' => 9, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER]);
            
            $appt5_c3 = $appointmentTable5->addCell($totalWidth * 0.20, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $appt5_c3->addText('with Plantilla Item No.', ['size' => 10], ['alignment' => WordJc::CENTER]);
            
            $appt5_c4 = $appointmentTable5->addCell($totalWidth * 0.33, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $plantillaCode = !empty($appointment->code) ? $this->convertAmpersand($appointment->code) : '___________________';
            $appt5_c4->addText($plantillaCode, ['size' => 9, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER]);
            
            $appt5_c5 = $appointmentTable5->addCell($totalWidth * 0.10, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $pageTextRun = $appt5_c5->addTextRun(['alignment' => WordJc::LEFT]);
            $pageTextRun->addText('Page ', ['size' => 10]);
            $pageTextRun->addText('1.', ['size' => 10, 'bold' => true, 'underline' => 'single']);

            // Add some space
            $nestedCell->addTextBreak(1.5);

            $textRun6 = $nestedCell->addTextRun([
                'lineHeight' => 1.5,
                'alignment' => WordJc::CENTER // Center alignment
            ]);
            $textRun6->addText('This appointment shall take effect on the date of signing by the appointing officer/authority.', ['size' => 10, 'bold' => true]);

            // Add some space
            $nestedCell->addTextBreak(0.5);

            $textRun7 = $nestedCell->addTextRun([
                'lineHeight' => 1.5,
                'alignment' => WordJc::CENTER // Center alignment
            ]);
            $textRun7->addText($this->convertAmpersand($signatories['note'] ?? ''), ['size' => 10, 'bold' => true]);

            // Add some space
            $nestedCell->addTextBreak(0.5);

            // Create a table with 3 columns for the signature section
            $signatureTable = $nestedCell->addTable([
                'borderSize' => 0,
                'borderColor' => 'ffffff',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            $signatureTable->addRow();

            // First column - empty
            $firstCell = $signatureTable->addCell(Converter::inchToTwip(2.33), [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);

            // Second column - empty
            $secondCell = $signatureTable->addCell(Converter::inchToTwip(2.33), [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);

            // Third column - Very truly yours and Date of Signing
            $thirdCell = $signatureTable->addCell(Converter::inchToTwip(2.33), [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
            ]);

            $thirdCell->addText('Very truly yours,', ['size' => 10], ['alignment' => WordJc::LEFT]);
            $thirdCell->addTextBreak(1);

            $textRunSignature = $thirdCell->addTextRun(['alignment' => WordJc::CENTER, 'lineHeight' => 1]);
            $textRunSignature->addText($this->convertAmpersand($signatories['signatory'] ?? ''), ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $textRunSignature->addTextBreak();
            $textRunSignature->addText($this->convertAmpersand($signatories['position'] ?? ''), ['size' => 10]);

            $thirdCell->addTextBreak(0.3);
            $thirdCell->addText('______________________', ['size' => 10], ['alignment' => WordJc::CENTER]);
            $thirdCell->addText('Date of Signing', ['size' => 10], ['alignment' => WordJc::CENTER]);

            // Add Accredited/Deregulated text
            $textRunAccredited = $nestedCell->addTextRun(['lineHeight' => 1]);
            $textRunAccredited->addText('Accredited/Deregulated Pursuant to', ['size' => 10, 'bold' => true]);
            $textRunAccredited->addTextBreak();
            $textRunAccredited->addText('CSC Resolution No. ', ['size' => 10, 'bold' => true]);
            $textRunAccredited->addText('__________', ['size' => 10, 'bold' => true]);
            $textRunAccredited->addText(', s. ', ['size' => 10, 'bold' => true]);
            $textRunAccredited->addText('__________', ['size' => 10, 'bold' => true]);
            $textRunAccredited->addTextBreak();
            $textRunAccredited->addText('dated ', ['size' => 10, 'bold' => true]);
            $textRunAccredited->addText('__________', ['size' => 10, 'bold' => true]);


            // Add some space
            $nestedCell->addTextBreak(0.5);

            // Create a table with 3 columns for the seal section
            $sealContainerTable = $nestedCell->addTable([
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMargin' => Converter::inchToTwip(0.4), // Add padding to all cells
            ]);

            $sealContainerTable->addRow();

            // First column - DRY SEAL
            $sealFirstCell = $sealContainerTable->addCell(Converter::inchToTwip(2.33), [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
            ]);

            // Add DRY SEAL text in a bordered box inside first column
            $sealTable = $sealFirstCell->addTable([
                'borderSize' => 6,
                'borderColor' => 'D3D3D3',
                'width' => Converter::pixelToTwip(120),
                'alignment' => WordJc::CENTER,
            ]);

            $sealTable->addRow(Converter::pixelToTwip(120));
            $sealCell = $sealTable->addCell(Converter::pixelToTwip(120), [
                'borderSize' => 6,
                'borderColor' => 'D3D3D3',
                'bgColor' => 'FAFAFA',
                'valign' => 'center',
            ]);

            $sealCell->addText('DRY SEAL', ['size' => 10, 'italic' => true, 'color' => 'CCCCCC'], ['alignment' => WordJc::CENTER]);

            // Second column - empty
            $sealSecondCell = $sealContainerTable->addCell(Converter::inchToTwip(2.33), [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
            ]);

            // Third column - empty
            $sealThirdCell = $sealContainerTable->addCell(Converter::inchToTwip(2.33), [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
            ]);

            // END OF 1ST PAGE SECTION --------------------------------------------------------
            // --------------------------------------------------------------------------------
            // --------------------------------------------------------------------------------

            // 2ND PAGE SECTION ---------------------------------------------------------------
            // --------------------------------------------------------------------------------
            // --------------------------------------------------------------------------------

            // Add another row with 1 column that spans all columns - SECOND PAGE
            $regAgenciesTable->addRow();
            $mainCell2 = $regAgenciesTable->addCell(null, [
                'borderSize' => 6,
                'borderColor' => '000000',
                'bgColor' => '808080',
                'gridSpan' => 3, // Merge across 3 columns
                'cellMargin' => Converter::pixelToTwip(10), // 10px padding
            ]);

            // Add a new table inside the mainCell2 with margin
            $innerTable2 = $mainCell2->addTable([
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMargin' => Converter::pixelToTwip(15), // 15px padding on inner table cells
            ]);

            // Add rows to the inner table
            $innerTable2->addRow();
            $innerCell2 = $innerTable2->addCell(null, [
                'bgColor' => '808080', // Gray background
            ]);

            // Add cert1 table inside innerCell2
            $cert1Table = $innerCell2->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMargin' => Converter::pixelToTwip(15), // 10px padding on inner table cells
            ]);

            // Add rows to the cert1 table
            $cert1Table->addRow();
            $cert1Cell = $cert1Table->addCell(null, [
                'bgColor' => 'FFFFFF', // White background
            ]);

            // Add Certification 1 content
            $cert1Cell->addText('Certification', ['size' => 12, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $cert1Cell->addTextBreak(0.5);

            $textRunCert1_1 = $cert1Cell->addTextRun(['alignment' => WordJc::BOTH, 'lineHeight' => 1.5]);
            $textRunCert1_1->addText('          This is to certify that all requirements and supporting papers pursuant to the ', ['size' => 10]);
            $textRunCert1_1->addText('2025 Omnibus Rules on Appointments and Other Human Resource Actions', ['size' => 10, 'bold' => true]);
            $textRunCert1_1->addText(', have been complied with, reviewed, and found to be in order.', ['size' => 10]);

            $cert1Cell->addTextBreak(0.5);

            $textRunCert1_2 = $cert1Cell->addTextRun(['alignment' => WordJc::BOTH, 'lineHeight' => 1.5]);
            $textRunCert1_2->addText('          The position was published at ', ['size' => 10]);
            $textRunCert1_2->addText($this->convertAmpersand($signatories['publish_at'] ?? ''), ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $textRunCert1_2->addText(' from ', ['size' => 10]);
            $textRunCert1_2->addText($this->convertAmpersand($signatories['publish_from'] ?? ''), ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $textRunCert1_2->addText(' to ', ['size' => 10]);
            $textRunCert1_2->addText($this->convertAmpersand($signatories['publish_to'] ?? ''), ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $textRunCert1_2->addText(' and posted in ', ['size' => 10]);
            $textRunCert1_2->addText($this->convertAmpersand($signatories['posted_at'] ?? ''), ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $textRunCert1_2->addText(' from ', ['size' => 10]);
            $textRunCert1_2->addText($this->convertAmpersand($signatories['posted_from'] ?? ''), ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $textRunCert1_2->addText(' to ', ['size' => 10]);
            $textRunCert1_2->addText($this->convertAmpersand($signatories['posted_to'] ?? ''), ['size' => 10, 'bold' => true, 'underline' => 'single']);
            $textRunCert1_2->addText(' in consonance with Republic Act No. 7041. The assessment by the Human Resource Merit Promotion and Selection Board (HRMPSB) started on ', ['size' => 10]);
            $startedOn = $signatories['started_on'] == '' ? '______________' : date('F j, Y', strtotime($signatories['started_on']));
            if ($signatories['started_on'] != '') {
                $textRunCert1_2->addText($startedOn, ['size' => 10, 'bold' => true, 'underline' => 'single']);
            } else {
                $textRunCert1_2->addText($startedOn, ['size' => 10]);
            }
            $textRunCert1_2->addText('.', ['size' => 10]);
        
            $cert1Cell->addTextBreak(0.5);
            
           // Create a table with 3 columns for HRMO signature
            $hrmoTable = $cert1Cell->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width'      => 100 * 50,
                'unit'       => 'pct',

                // REMOVE table-level padding
                'cellMarginTop'    => 0,
                'cellMarginRight'  => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft'   => 0,
            ]);

            $hrmoTable->addRow();

            // First column - empty
            $hrmoFirstCell = $hrmoTable->addCell(
                Converter::inchToTwip(2.33),
                [
                    'borderSize' => 0,
                    'borderColor' => 'FFFFFF',

                    // REMOVE cell padding
                    'cellMarginTop'    => 0,
                    'cellMarginRight'  => 0,
                    'cellMarginBottom' => 0,
                    'cellMarginLeft'   => 0,
                ]
            );

            // Second column - empty
            $hrmoSecondCell = $hrmoTable->addCell(
                Converter::inchToTwip(2.33),
                [
                    'borderSize' => 0,
                    'borderColor' => 'FFFFFF',
                    'cellMarginTop'    => 0,
                    'cellMarginRight'  => 0,
                    'cellMarginBottom' => 0,
                    'cellMarginLeft'   => 0,
                ]
            );

            // Third column - HRMO signature
            $hrmoThirdCell = $hrmoTable->addCell(
                Converter::inchToTwip(2.33),
                [
                    'borderSize' => 0,
                    'borderColor' => 'FFFFFF',
                    'cellMarginTop'    => 0,
                    'cellMarginRight'  => 0,
                    'cellMarginBottom' => 0,
                    'cellMarginLeft'   => 0,
                ]
            );

            // HRMO text
            $textRunHRMO = $hrmoThirdCell->addTextRun([
                'alignment'  => WordJc::CENTER,
                'lineHeight' => 1,
            ]);

            $textRunHRMO->addText(
                $this->convertAmpersand($signatories['hrmo'] ?? ''),
                [
                    'size'      => 10,
                    'bold'      => true,
                    'underline' => 'single',
                    'cellMarginTop'    => 0,
                ]
            );
            $textRunHRMO->addTextBreak();
            $textRunHRMO->addText( 'HRMO', ['size' => 10, 'bold' => false]);

            $innerCell2->addTextBreak(1);

            // Add cert2 table inside innerCell2
            $cert2Table = $innerCell2->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMargin' => Converter::pixelToTwip(15), // 15px padding on inner table cells
            ]);

            $cert2Table->addRow();
            $cert2Cell = $cert2Table->addCell(null, [
                'bgColor' => 'FFFFFF', // White background
            ]);

            // Add Certification 2 content
            $cert2Cell->addText('Certification', ['size' => 12, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $cert2Cell->addTextBreak(0.5);

            $textRunCert2_1 = $cert2Cell->addTextRun(['alignment' => WordJc::BOTH, 'lineHeight' => 1.5]);
            $textRunCert2_1->addText('          This is to certify that the appointee has been screened and found qualified by the majority of the HRMPSB/Placement Committee during the deliberation held on ', ['size' => 10]);
            $deliberationOn = $signatories['deliberation_on'] == '' ? '__________________' : date('F j, Y', strtotime($signatories['deliberation_on']));
            if ($signatories['deliberation_on'] != '') {
                $textRunCert2_1->addText($deliberationOn, ['size' => 10, 'bold' => true, 'underline' => 'single']);
            } else {
                $textRunCert2_1->addText($deliberationOn, ['size' => 10]);
            }
            $textRunCert2_1->addText('.', ['size' => 10]);

            $cert2Cell->addTextBreak(0.5);

            // Create a table with 2 columns for Chairperson signature (70 / 30)
            $chairpersonTable = $cert2Cell->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMarginTop' => 0,
                'cellMarginRight' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 0,
            ]);

            $chairpersonTable->addRow();

            // 70% column (empty spacer)
            $chairFirstCell = $chairpersonTable->addCell(
                Converter::inchToTwip(4.2), // 70%
                [
                    'borderSize' => 0,
                    'borderColor' => 'FFFFFF',
                    'cellMarginTop' => 0,
                    'cellMarginRight' => 0,
                    'cellMarginBottom' => 0,
                    'cellMarginLeft' => 0,
                ]
            );

            // 30% column (Chairperson signature)
            $chairSecondCell = $chairpersonTable->addCell(
                Converter::inchToTwip(2.8), // 30%
                [
                    'borderSize' => 0,
                    'borderColor' => 'FFFFFF',
                    'cellMarginTop' => 0,
                    'cellMarginRight' => 0,
                    'cellMarginBottom' => 0,
                    'cellMarginLeft' => 0,
                ]
            );

            // Chairperson text
            $textRunChair = $chairSecondCell->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
            ]);

            $textRunChair->addText(
                $this->convertAmpersand($signatories['hrmpsb'] ?? ''),
                [
                    'size' => 10,
                    'bold' => true,
                    'underline' => 'single',
                ]
            );

            $textRunChair->addTextBreak();

            $textRunChair->addText(
                'Chairperson, HRMPSB/Placement Committee',
                [
                    'size' => 10,
                ]
            );

            
            // Add another row with 1 column that spans all columns - SECOND PAGE - 3rd section
            $regAgenciesTable->addRow();
            $blankCell = $regAgenciesTable->addCell(null, [
                'borderSize' => 6,
                'borderColor' => 'ffffff',
                'bgColor' => 'ffffff',
                'gridSpan' => 3, // Merge across 3 columns
                'cellMargin' => 0,
                'cellPadding' => 0,
                'cellSpacing' => 0,
                'cellBorderSize' => 0,
                'cellBorderColor' => 'ffffff',
                'cellBorderStyle' => 'solid',
            ]);


            $regAgenciesTable->addRow();
            $mainCell3 = $regAgenciesTable->addCell(null, [
                'borderSize' => 6,
                'borderColor' => '000000',
                'bgColor' => '808080',
                'gridSpan' => 3, // Merge across 3 columns
                'cellMargin' => Converter::pixelToTwip(15), // 15px padding
            ]);
            
            // Add a new table inside the mainCell3 with margin
            $innerTable3 = $mainCell3->addTable([
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMargin' => Converter::pixelToTwip(15), // 15px padding on inner table cells
            ]);
            
            // Add rows to the inner table
            $innerTable3->addRow();
            $innerCell3 = $innerTable3->addCell(null, [
                'bgColor' => '808080', // Gray background
            ]);
            
            // Add CSC/HRMO Notation text
            $innerCell3->addText('CSC/HRMO Notation', ['size' => 12, 'bold' => true], ['alignment' => WordJc::CENTER]);
            
            // Add nestedTable3 inside innerCell3 (black border container)
            $nestedTable3 = $innerCell3->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMargin' => Converter::pixelToTwip(10), // This creates the white padding space
            ]);
            
            // Add row to nestedTable3
            $nestedTable3->addRow();
            $nestedCell3_1 = $nestedTable3->addCell(null, [
                'bgColor' => 'FFFFFF', // White background (creates the white border/padding)
            ]);
            
            // NOW create the actionTable inside the white cell
            $actionTable = $nestedCell3_1->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMargin' => Converter::pixelToTwip(4), // This creates the padding all the cells inside the actionTable
            ]);
            
            // Now you can add rows to actionTable for your content
            $actionTable->addRow();

            // First cell - "ACTION ON APPOINTMENTS" with colspan 3
            $actionCell = $actionTable->addCell(null, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
            $actionCell->addText('ACTION ON APPOINTMENTS', ['size' => 10, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Second cell - "Recorded by"
            $recordedByCell = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
            $recordedByCell->addText('Recorded by', ['size' => 10, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            
            // Add second row
            $actionTable->addRow();

            // First cell - Checkbox and text with colspan 3
            $validatedCell = $actionTable->addCell(null, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $validatedRun = $validatedCell->addTextRun([
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $validatedRun->addText('☐ ', ['size' => 12]);
            $validatedRun->addText('Validated per RAI for the month of ', ['size' => 10, 'bold' => true]);
            $validatedRun->addText('__________________________________', ['size' => 10]);

            // Second cell - Empty "Recorded by" cell
            $recordedByCell2 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
                        
            // Add third row
            $actionTable->addRow();

            // First cell - Checkbox and text with colspan 3
            $invalidatedCell = $actionTable->addCell(null, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $invalidatedRun = $invalidatedCell->addTextRun([
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $invalidatedRun->addText('☐ ', ['size' => 12]);
            $invalidatedRun->addText('Invalidated per CSCRO/FO letter dated ', ['size' => 10, 'bold' => true]);
            $invalidatedRun->addText('__________________________________', ['size' => 10]);

            // Second cell - Empty "Recorded by" cell
            $recordedByCell3 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Add fourth row - Appeal with DATE FILED and STATUS
            $actionTable->addRow();

            // First cell - Checkbox and "Appeal"
            $appealCell = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $appealRun = $appealCell->addTextRun([
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $appealRun->addText('☐ ', ['size' => 12]);
            $appealRun->addText('Appeal', ['size' => 10, 'bold' => true]);

            // Second cell - "DATE FILED"
            $dateFiledCell = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
            $dateFiledCell->addText('DATE FILED', ['size' => 9, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Third cell - "STATUS"
            $statusCell = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
            $statusCell->addText('STATUS', ['size' => 9, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Fourth cell - Empty "Recorded by" cell
            $recordedByCell4 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Add fifth row - CSCRO/CSC-Commission
            $actionTable->addRow();

            // First cell - Checkbox and "CSCRO/CSC-Commission"
            $cscroCell = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cscroRun = $cscroCell->addTextRun([
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $cscroRun->addText('     ☐ ', ['size' => 12]);
            $cscroRun->addText('CSCRO/ CSC-Commission', ['size' => 10]);

            // Second cell - Empty
            $emptyCell1 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Third cell - Empty
            $emptyCell2 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Fourth cell - Empty "Recorded by" cell
            $recordedByCell5 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Add sixth row - Petition for Review
            $actionTable->addRow();

            // First cell - Checkbox and "Petition for Review"
            $petitionCell = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $petitionRun = $petitionCell->addTextRun([
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $petitionRun->addText('☐ ', ['size' => 12]);
            $petitionRun->addText('Petition for Review', ['size' => 10, 'bold' => true]);

            // Second cell - Empty
            $emptyCell3 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Third cell - Empty
            $emptyCell4 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Fourth cell - Empty "Recorded by" cell
            $recordedByCell6 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Add seventh row - Court of Appeals
            $actionTable->addRow();
            // First cell - Checkbox and "Court of Appeals"
            $courtCell = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
            $courtRun = $courtCell->addTextRun([
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $courtRun->addText('☐ ', ['size' => 12]);
            $courtRun->addText('Court of Appeals', ['size' => 10, 'bold' => true]);
            
            // Second cell - Empty "Recorded by" cell
            $recordedByCell7 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]); 

            // Third cell - Empty
            $emptyCell5 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Fourth cell - Empty "Recorded by" cell
            $recordedByCell8 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);     
            
            // Add eighth row - Supreme Court
            $actionTable->addRow();
            // First cell - Checkbox and "Supreme Court"
            $supremeCell = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $supremeRun = $supremeCell->addTextRun([
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $supremeRun->addText('☐ ', ['size' => 12]);
            $supremeRun->addText('Supreme Court', ['size' => 10, 'bold' => true]);
            
            // Second cell - Empty "Recorded by" cell
            $recordedByCell9 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]); 
            
            // Third cell - Empty
            $emptyCell6 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
            
            // Fourth cell - Empty "Recorded by" cell
            $recordedByCell10 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);     

            // Add another row with 1 column that spans all columns - SECOND PAGE - 3rd section
            $regAgenciesTable->addRow();
            $blankCell = $regAgenciesTable->addCell(null, [
                'borderSize' => 6,
                'borderColor' => 'ffffff',
                'bgColor' => 'ffffff',
                'gridSpan' => 3, // Merge across 3 columns
                'cellMargin' => 0,
                'cellPadding' => 0,
                'cellSpacing' => 0,
                'cellBorderSize' => 0,
                'cellBorderColor' => 'ffffff',
                'cellBorderStyle' => 'solid',
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $regAgenciesTable->addRow();
            $mainCell4 = $regAgenciesTable->addCell(null, [
                'borderSize' => 6,
                'borderColor' => '000000',
                'bgColor' => '808080',
                'gridSpan' => 3, // Merge across 3 columns
                'cellMargin' => Converter::pixelToTwip(15), // 15px padding
            ]);

            // Add a new table inside the mainCell4 with margin
            $innerTable4 = $mainCell4->addTable([
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMargin' => Converter::pixelToTwip(15), // 15px padding on inner table cells
            ]);

            // Add rows to the inner table
            $innerTable4->addRow();
            $innerCell4 = $innerTable4->addCell(null, [
                'bgColor' => '808080', // Gray background
            ]);

            // NOW create the actionTable directly inside innerCell4
            $actionTable = $innerCell4->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMargin' => Converter::pixelToTwip(15), // Padding for cells
            ]);

            // Now you can add rows to actionTable for your content
            $actionTable->addRow();

            // First column
            $actionCell = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $actionCell->addText('Original Copy-for the Appointee', ['size' => 8.5], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $actionCell->addText('Original Copy-for the Civil Service Commission', ['size' => 8.5], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $actionCell->addText('Original Copy-for the Agency', ['size' => 8.5], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Second column
            $actionCell2 = $actionTable->addCell(null, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $actionCell2->addText('Acknowledgement', ['size' => 8.5, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $ackRun = $actionCell2->addTextRun([
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $ackRun->addText('Received original /photocopy of appointment on ', ['size' => 8.5]);
            $ackRun->addText('______________', ['size' => 8.5]);

            $actionCell2->addText('Appointee __________________________', ['size' => 8.5], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // END OF 2ND PAGE SECTION --------------------------------------------------------
            // --------------------------------------------------------------------------------
            // --------------------------------------------------------------------------------



            $filename = 'appointment_certificate_' . $request->employee . '_' . date('Y-m-d_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate appointment certificate DOCX: ' . $e->getMessage());
        }
    }
}
