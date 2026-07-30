<?php

namespace App\Http\Controllers;

use App\Helpers\CompanyHelper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;

class OathOfOfficeWordController extends Controller
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
     * Generate oath of office as DOCX using PhpWord.
     */
    public function word(Request $request)
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

            // Load reference data via robust path resolution
            $region_data = array_filter($this->loadJsonRecords('refregion.json'));
            $province_data = array_filter($this->loadJsonRecords('refprovince.json'));
            $city_data = array_filter($this->loadJsonRecords('refcitymun.json'));
            $brgy_data = array_filter($this->loadJsonRecords('refbrgy.json'));

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
                'signatory' => $request->signatory ?? '',
                'position' => $request->position ?? '',
            );

            $employee_name = strtoupper($oath_of_offices[0]->name ?? '');
            $position = $oath_of_offices[0]->position ?? '';
            // Use ->get() method to properly access Collection values
            $address_city = $address->get('ra_city', '');
            $address_province = $address->get('ra_province', '');
            $signatory_name = $signatories['signatory'] ?? '';

            // Format current date
            $current_day = date('d');
            $current_month_year = date('M Y');
            $current_year = date('Y');
            
            // Tagalog month conversion
            $months = [
                'January' => 'Enero',
                'February' => 'Pebrero',
                'March' => 'Marso',
                'April' => 'Abril',
                'May' => 'Mayo',
                'June' => 'Hunyo',
                'July' => 'Hulyo',
                'August' => 'Agosto',
                'September' => 'Setyembre',
                'October' => 'Oktubre',
                'November' => 'Nobyembre',
                'December' => 'Disyembre'
            ];
            $currentMonth = date('F');
            $monthTagalog = $months[$currentMonth] ?? $currentMonth;

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(10); // Minimized font size

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(0.37),
                'marginRight' => Converter::inchToTwip(0.70),
                'marginBottom' => Converter::inchToTwip(0.37),
                'marginLeft' => Converter::inchToTwip(.90),
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

            // Calculate available page width (page width minus left and right margins)
            $pageWidth = Converter::inchToTwip(8.27 - 1 - 0.95); // 6.32 inches available

            // Create a table with border
            $OathTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Add a row - Identification Section
            $OathTable->addRow();

            // First cell with the text content (34% width)
            $cell1 = $OathTable->addCell($pageWidth * 0.34, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRun1 = $cell1->addTextRun([
                'alignment' => WordJc::LEFT,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun1->addText('SS Porma Blg. 32', ['size' => 10, 'bold' => true, 'italic' => true]);
            $textRun1->addTextBreak();
            $textRun1->addText('CS Form No. 32', ['size' => 9, 'italic' => true]);
            $textRun1->addTextBreak(2);
            $textRun1->addText('Narcbisa 2025', ['size' => 10, 'bold' => true, 'italic' => true]);
            $textRun1->addTextBreak();
            $textRun1->addText('Revised 2025', ['size' => 9, 'italic' => true]);

            // Empty cell (33% width)
            $cell2 = $OathTable->addCell($pageWidth * 0.33, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Empty cell (33% width)
            $cell3 = $OathTable->addCell($pageWidth * 0.33, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Add a row - Header Section
            $OathTable->addRow();

            $cell4 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRun4 = $cell4->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $textRun4->addText('REPUBLIC OF THE PHILIPPINES', ['size' => 13]);
            $textRun4->addTextBreak();
            $textRun4->addText('Republic of the Philippines', ['size' => 9, 'italic' => true]);
            $textRun4->addTextBreak();
            $textRun4->addTextBreak();
            $textRun4->addText(CompanyHelper::getName(), ['size' => 13]);
            $textRun4->addTextBreak();
            $textRun4->addText(CompanyHelper::getAddress(), ['size' => 9, 'italic' => true]);
            $textRun4->addTextBreak();
            $textRun4->addTextBreak();
            $textRun4->addText('PANUNUMPA SA KATUNGKULAN', ['size' => 16, 'bold' => true]);
            $textRun4->addTextBreak();
            $textRun4->addText('OATH OF OFFICE', ['size' => 9, 'italic' => true]);

            $textRun4->addTextBreak(2);

            // Add a row - Main Section
            $OathTable->addRow();
            $cell5 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
                
            ]);

            // Create a nested table inside cell5
            $nestedTable5 = $cell5->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Add 1 row with 4 columns
            $nestedTable5->addRow();

            //add cell/column 1 (10% width)
            $nestedCell1 = $nestedTable5->addCell($pageWidth * 0.10, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRun_cell1 = $nestedCell1->addTextRun([
                'alignment' => WordJc::CENTER,  // Change to center for right alignment
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $textRun_cell1->addText('Ako si', ['size' => 12]);

            // Center only the "I," text
            $nestedCell1->addText('I,', ['size' => 9, 'italic' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
                'cellMarginBottom' => Converter::inchToTwip(50),
            ]);
            
            //add cell/column 2 (30% width)
            $nestedCell2 = $nestedTable5->addCell($pageWidth * 0.30, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRun_cell2 = $nestedCell2->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $textRun_cell2->addText(strtoupper($employee_name ?? '________________________'), ['size' => 11, 'bold' => true, 'underline' => 'single']);
    
            $nestedCell2->addText('(Name of Appointee)', ['size' => 9, 'italic' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

           //add cell/column 3 (40% width)
            $nestedCell3 = $nestedTable5->addCell($pageWidth * 0.50, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRun_cell3 = $nestedCell3->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add "ng" before the address
            $textRun_cell3->addText(', ng ', ['size' => 12]); // Regular text for "ng"

            // Build address with proper placeholder handling (matching PDF view logic)
            if (!empty($address_city) || !empty($address_province)) {
                // Build address string exactly like the PDF view: city + (comma if both exist) + province
                $address_text = $address_city;
                if (!empty($address_city) && !empty($address_province)) {
                    $address_text .= ', ' . $address_province;
                } elseif (!empty($address_province)) {
                    $address_text = $address_province; // If only province exists
                }
                $textRun_cell3->addText(strtoupper($address_text), ['size' => 11, 'bold' => true, 'underline' => 'single']);
            } else {
                $textRun_cell3->addText('______________________________________,', ['size' => 11, 'bold' => true, 'underline' => 'single']);
            }

            // Add label below
            $nestedCell3->addText('(Address)', ['size' => 9, 'italic' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            //add cell/column 4 (20% width)
            $nestedCell4 = $nestedTable5->addCell($pageWidth * 0.10, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRun_cell4 = $nestedCell4->addTextRun([
                'alignment' => WordJc::LEFT,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $textRun_cell4->addText(' na', ['size' => 12]);

            // Add label below
            $nestedCell4->addText('having', ['size' => 9, 'italic' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            //Add row 2 - Main Section
            $OathTable->addRow();
            $cell6 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
                'cellMarginTop' => Converter::pixelToTwip(-10),
                'cellMarginRight' => Converter::pixelToTwip(-10),
                'cellMarginBottom' => Converter::pixelToTwip(-10),
                'cellMarginLeft' => Converter::pixelToTwip(-10),
            ]);

            // Create a nested table inside cell6
            $nestedTable6 = $cell6->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Add 1 row with 3 columns (but 3rd column spans 2)
            $nestedTable6->addRow();

            // Column 1 (10% width)
            $nestedCell6= $nestedTable6->addCell($pageWidth * 0.14, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRun6 = $nestedCell6->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun6->addTextBreak();
            $textRun6->addText('itinalaga bilang', ['size' => 12, ]);
            $textRun6->addTextBreak();
            $textRun6->addText('been appointed to', [ 'size' => 9, 'italic' => true, ]);
            
            
            // Column 2 (30% width)
            $nestedCell6_2 = $nestedTable6->addCell($pageWidth * 0.30, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            
            $textRun6_2 = $nestedCell6_2->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            
            // Position value (uppercase, bold, underlined)
            $textRun6_2->addTextBreak();
            $textRun6_2->addText(strtoupper($position ?? '________________________'), ['size' => 10, 'bold' => true,'underline' => 'single', ]);
            $textRun6_2->addTextBreak();
            $textRun6_2->addText('Position', [ 'size' => 9,'italic' => true, ]);
            

            // Column 3 (60% width - spans columns 3 and 4 from previous row)
            $nestedCell6_3 = $nestedTable6->addCell($pageWidth * 0.40, [
                'gridSpan' => 2,  // This makes it span 2 columns
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            
            $textRun6_3 = $nestedCell6_3->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $textRun6_3->addTextBreak();
            $textRun6_3->addText('ay taimtim na nanunumpa na tutuparin ko nang', [ 'size' => 12,]);
            $textRun6_3->addTextBreak();
            $textRun6_3->addText('hereby solemnly swear, that I will faithfully discharge',[ 'size' => 9, 'italic' => true,  ] );
            
            //Add row 3 - Main Section
            $OathTable->addRow();
            $cell7 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Create a nested table inside cell7 
            $nestedTable7 = $cell7->addTable([ 
                'borderSize' => 0, 
                'borderColor' => 'FFFFFF', 
                'width' => 100 * 50, 
                'unit' => 'pct', 
            ]);

            // Add a row
            $nestedTable7->addRow();

            // First column with colspan 2
            $nestedCell7_1 = $nestedTable7->addCell($pageWidth * 0.56, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun7_1 = $nestedCell7_1->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun7_1->addTextBreak();
            $textRun7_1->addText('buong husay at katapatan, sa abot ng aking kakayahan, ', ['size' => 12]);
            $textRun7_1->addTextBreak();
            $textRun7_1->addText('to the best of my ability, ', ['size' => 9, 'italic' => true]);                                                                      

            // Second column with colspan 2
            $nestedCell7_2 = $nestedTable7->addCell($pageWidth * 0.44, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRun7_2 = $nestedCell7_2->addTextRun([
                'alignment' => WordJc::LEFT,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun7_2->addTextBreak();
            $textRun7_2->addText(' ang mga katungkulang pinagtalagahan ', ['size' => 12]);
            $textRun7_2->addTextBreak();
            $textRun7_2->addText('hereby solemnly swear, that I will faithfully discharge', ['size' => 9, 'italic' => true]);

            //Add row 4 - Main Section
            $OathTable->addRow();
            $cell8 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Create a nested table inside cell8
            $nestedTable8 = $cell8->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);     

            // Add a row
            $nestedTable8->addRow();

            // First column with colspan 2
            $nestedCell8_1 = $nestedTable8->addCell($pageWidth * 0.76, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRun8_1 = $nestedCell8_1->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun8_1->addTextBreak();
            $textRun8_1->addText('sa akin at sa dapat gampanan sa iba pang pagkaraan nito’y gagampanan ko ', ['size' => 12]);
            $textRun8_1->addTextBreak();
            $textRun8_1->addText('and of all others that I may hereafter hold', ['size' => 9, 'italic' => true]);


            // Second column with colspan 2
            $nestedCell8_2 = $nestedTable8->addCell($pageWidth * 0.24, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun8_2 = $nestedCell8_2->addTextRun([
                'alignment' => WordJc::LEFT,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun8_2->addTextBreak();
            $textRun8_2->addText('sa ilalim ng ', ['size' => 12]);
            $textRun8_2->addTextBreak();
            $textRun8_2->addText('     under the ', ['size' => 9, 'italic' => true]);

            //Add row 5 - Main Section
            $OathTable->addRow();
            $cell9 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Create a nested table inside cell9
            $nestedTable9 = $cell9->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);
            

            // Add a row
            $nestedTable9->addRow();

            // First column with colspan 2
            $nestedCell9_1 = $nestedTable9->addCell($pageWidth * 0.24, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun9_1 = $nestedCell9_1->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun9_1->addTextBreak();
            $textRun9_1->addText('Republika ng Pilipinas', ['size' => 12]);
            $textRun9_1->addTextBreak();
            $textRun9_1->addText('Republic of the Philippines;', ['size' => 9, 'italic' => true]);

            // Second column with colspan 2
            $nestedCell9_2 = $nestedTable9->addCell($pageWidth * 0.76, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun9_2 = $nestedCell9_2->addTextRun([
                'alignment' => WordJc::LEFT,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun9_2->addTextBreak();
            $textRun9_2->addText('na aking itataguyod at ipagtatanggol ang Saligang Batas ng Pilipinas; ', ['size' => 12]);

            // Center only this line
            $nestedCell9_2->addText('to uphold and defend the Constitution, ', ['size' => 9, 'italic' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            //Add row 6 - Main Section
            $OathTable->addRow();
            $cell10 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Create a nested table inside cell10
            $nestedTable10 = $cell10->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            
            // Add a row
            $nestedTable10->addRow();

            // First column with colspan 2
            $nestedCell10_1 = $nestedTable10->addCell($pageWidth * 0.44, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun10_1 = $nestedCell10_1->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun10_1->addTextBreak();
            $textRun10_1->addText('na tunay na mananalig at tatalima ako rito; ', ['size' => 12]);
            $textRun10_1->addTextBreak();
            $textRun10_1->addText('that I will bear true faith and allegiance to the same; ', ['size' => 9, 'italic' => true]);

            // Second column with colspan 2
            $nestedCell10_2 = $nestedTable10->addCell($pageWidth * 0.56, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun10_2 = $nestedCell10_2->addTextRun([
                'alignment' => WordJc::LEFT,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun10_2->addTextBreak();
            $textRun10_2->addText('na susundin ko ang mga batas at mga kautusang ', ['size' => 12]);
            $textRun10_2->addTextBreak();
            $textRun10_2->addText('     that I will obey the laws, legal orders, and ', ['size' => 9, 'italic' => true]);

            //Add row 7 - Main Section
            $OathTable->addRow();
            $cell11 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Create a nested table inside cell11
            $nestedTable11 = $cell11->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Add a row
            $nestedTable11->addRow();

            // First column with colspan 4
            $nestedCell11_1 = $nestedTable11->addCell($pageWidth * 0.95, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun11_1 = $nestedCell11_1->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun11_1->addTextBreak();
            $textRun11_1->addText('legal, at mga dekretong pinaiiral ng mga sadyang itinakdang maykapangyarihan ng Republika', ['size' => 12]);
            $textRun11_1->addTextBreak();
            $textRun11_1->addText('decrees promulgated by the duly constituted authorities of the Republic', ['size' => 9, 'italic' => true]);

            // Second column with colspan 2
            $nestedCell11_2 = $nestedTable11->addCell($pageWidth * 0.05, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $nestedCell11_2->addText('', ['size' => 12]); // Empty text to maintain spacing

            //Add row 8 - Main Section
            $OathTable->addRow();
            $cell12 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Create a nested table inside cell12
            $nestedTable12 = $cell12->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Add a row

            $nestedTable12->addRow();

            // First column with colspan 2
            $nestedCell12_1 = $nestedTable12->addCell($pageWidth * 0.94, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun12_1 = $nestedCell12_1->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun12_1->addTextBreak();
            $textRun12_1->addText('ng Pilipinas; at kusa kong babalikatin ang pananagutang ito nang walang ano mang pasubali', ['size' => 12]);
            $textRun12_1->addTextBreak();
            $textRun12_1->addText('of the Philippines; and that I impose this obligation upon myself voluntarily, without mental reservation', ['size' => 9, 'italic' => true]);

            // Second column with colspan 2
            $nestedCell12_2 = $nestedTable12->addCell($pageWidth * 0.06, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $nestedCell12_2->addText('', ['size' => 12]); // Empty text to maintain spacing


            //Add row 9 - Main Section
            $OathTable->addRow();
            $cell13 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Create a nested table inside cell13
            $nestedTable13 = $cell13->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);
            // Add a row
            $nestedTable13->addRow();

            // First column with colspan 2
            $nestedCell13_1 = $nestedTable13->addCell($pageWidth * 0.23, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun13_1 = $nestedCell13_1->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun13_1->addTextBreak();
            $textRun13_1->addText('o hangaring umiwas.', ['size' => 12]);
            $textRun13_1->addTextBreak();
            $textRun13_1->addText('or purpose of evasion.', ['size' => 9, 'italic' => true]);

            // Second column with colspan 2
            $nestedCell13_2 = $nestedTable13->addCell($pageWidth * 0.77, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $nestedCell13_2->addText('', ['size' => 12]); // Empty text to maintain spacing

            //Add row 10 - Main Section
            $OathTable->addRow();
            $cell14 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Create a nested table inside cell14
            $nestedTable14 = $cell14->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Add a row
            $nestedTable14->addRow();

            // 1st column 
            $nestedCell14_1 = $nestedTable14->addCell($pageWidth * 0.08, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun14_1 = $nestedCell14_1->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun14_1->addTextBreak();
            $nestedCell14_1->addText('', ['size' => 12]); // Empty text to maintain spacing

            // 2nd column 
            $nestedCell14_2 = $nestedTable14->addCell($pageWidth * 0.40, [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun14_2 = $nestedCell14_2->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun14_2->addTextBreak();
            $textRun14_2->addText('KASIHAN NAWA AKO NG DIYOS.', ['size' => 12]);
            $textRun14_2->addTextBreak();
            $textRun14_2->addText('SO HELP ME GOD.', ['size' => 9, 'italic' => true]);

            // 3rd column with colspan 2
            $nestedCell14_3= $nestedTable14->addCell($pageWidth * 0.52, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $nestedCell14_3->addText('', ['size' => 12]); // Empty text to maintain spacing

            //Add row 11 - Signature Section
            $OathTable->addRow();
            $cell15 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Create a nested table inside cell15
            $nestedTable15 = $cell15->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);
            // Add a row
            $nestedTable15->addRow();

            // 1st column with colspan 2
            $nestedCell15_1 = $nestedTable15->addCell($pageWidth * 0.60, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun15_1 = $nestedCell15_1->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun15_1->addTextBreak();
            $textRun15_1->addText('', ['size' => 12]); // Empty text to maintain spacing
            

            // 2nd column with colspan 2
            $nestedCell15_2 = $nestedTable15->addCell($pageWidth * 0.40, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun15_2 = $nestedCell15_2->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            
            $textRun15_2->addTextBreak();
            $textRun15_2->addText(strtoupper($employee_name), ['size' => 12, 'bold' => true, 'underline' => 'single']);
            $textRun15_2->addTextBreak();
            $textRun15_2->addText('(Lagda sa itaas ng pangalan ng hinirang)', ['size' => 11,]);


            //Add row 12 - Government ID Section
            $OathTable->addRow();
            $cell16 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Create a nested table inside cell16
            $nestedTable16 = $cell16->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);
            // Add a row
            $nestedTable16->addRow();

            // 1st column
            $nestedCell16_1 = $nestedTable16->addCell($pageWidth * 0.50, [
                'gridSpan' =>4,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRun16_1 = $nestedCell16_1->addTextRun([    
                'alignment' => WordJc::LEFT,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun16_1->addTextBreak();
            $textRun16_1->addText('  Government ID: ______________', ['size' => 10]);
            $textRun16_1->addTextBreak();
            $textRun16_1->addText('  Numero ng ID:       ______________', ['size' => 10]);
            $textRun16_1->addTextBreak();
            $textRun16_1->addText('  Araw ng Pagkakaloob:      ______________', ['size' => 10]);     
            $textRun16_1->addTextBreak();
            
            // Add row 13 - Line Section
            $OathTable->addRow();
            $cell17 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'top',
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            
            $cell17->addText('════════════════════════════════════════════════════════════════════', ['size' => 10], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            
            // Subscribed and sworn text with actual date values
            $textRun17 = $cell17->addTextRun([
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun17->addTextBreak();
            $textRun17->addText('               Nilagdaan at pinanumpaan sa harap ko ngayong ika ', ['size' => 12]);
            $textRun17->addText($current_day, ['size' => 12, 'bold' => true, 'underline' => 'single']);
            $textRun17->addText(' ng ', ['size' => 12]);
            $textRun17->addText($monthTagalog, ['size' => 12, 'bold' => true, 'underline' => 'single']);
            $textRun17->addText(' ng ', ['size' => 12]);
            $textRun17->addText($current_year, ['size' => 12, 'bold' => true, 'underline' => 'single']);
            $textRun17->addText(', sa ' . CompanyHelper::getAddress() . '.', ['size' => 12]);
            $textRun17->addTextBreak(1.5);


            //Add row 14 - Signature Section
            $OathTable->addRow();
            $cell18 = $OathTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Create a nested table inside cell18
            $nestedTable18 = $cell18->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Add a row
            $nestedTable18->addRow();

            // 1st column
            $nestedCell18_1 = $nestedTable18->addCell($pageWidth * 0.60, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRun18_1 = $nestedCell18_1->addTextRun([
                'alignment' => WordJc::LEFT,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRun18_1->addTextBreak();
            $textRun18_1->addText('', ['size' => 12]); // Empty text to maintain spacing
            

            // 2nd column
            $nestedCell18_2 = $nestedTable18->addCell($pageWidth * 0.40, [
                'gridSpan' => 2,
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRun18_2 = $nestedCell18_2->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            
            $textRun18_2->addTextBreak();
            $textRun18_2->addText(strtoupper($signatories['signatory']), ['size' => 12, 'bold' => true, 'underline' => 'single']);
            // Display signatory position if available
            if (!empty($signatories['position'])) {
                $textRun18_2->addTextBreak();
                $textRun18_2->addText($signatories['position'], ['size' => 11]);
            }
            

            $filename = 'oath_of_office_' . $employee_name . '_' . date('Y-m-d') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate oath of office DOCX: ' . $e->getMessage());
        }
    }
}
