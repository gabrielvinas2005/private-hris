<?php

namespace App\Http\Controllers;

use App\Helpers\CompanyHelper;
use Auth;
use App\Position;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;

class PositionDescriptionWordController extends Controller
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
     * Download Position Description DOCX
     */
    public function downloadDocx(Request $request)
    {
        try {
            $pdfId = $request->input('pdf_id');
            
            // Use same data fetching logic as print method
            if ($pdfId) {
                $app_key = config('app.key');
                $pdfRecord = DB::table('PDF')
                    ->leftJoin('positions', 'PDF.position_id', '=', 'positions.id')
                    ->leftJoin('salary_grades', 'PDF.salarygrade_id', '=', 'salary_grades.id')
                    ->leftJoin('employees as emp', 'PDF.Employee_no', '=', 'emp.employee_no')
                    ->leftJoin('employees as sup', 'PDF.supervisor', '=', 'sup.employee_no')
                    ->select(
                        'PDF.*',
                        'positions.name as position_title',
                        'positions.description as position_description_from_position',
                        DB::raw('salary_grades.name as salary_grade_name'),
                        DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.first_name ELSE dbo.ufn_DecryptString(emp.first_name, '$app_key') END as emp_first"),
                        DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.middle_name ELSE dbo.ufn_DecryptString(emp.middle_name, '$app_key') END as emp_middle"),
                        DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.last_name ELSE dbo.ufn_DecryptString(emp.last_name, '$app_key') END as emp_last"),
                        DB::raw("CASE WHEN ISNULL(sup.is_encrypted,0) = 0 THEN sup.first_name ELSE dbo.ufn_DecryptString(sup.first_name, '$app_key') END as sup_first"),
                        DB::raw("CASE WHEN ISNULL(sup.is_encrypted,0) = 0 THEN sup.middle_name ELSE dbo.ufn_DecryptString(sup.middle_name, '$app_key') END as sup_middle"),
                        DB::raw("CASE WHEN ISNULL(sup.is_encrypted,0) = 0 THEN sup.last_name ELSE dbo.ufn_DecryptString(sup.last_name, '$app_key') END as sup_last")
                    )
                    ->where('PDF.id', $pdfId)
                    ->first();
                
                if (!$pdfRecord) {
                    return $this->errorResponse('PDF record not found', 404);
                }

                $sodarRecords = DB::table('SODAR')
                    ->leftJoin('Competency_level', 'SODAR.Competencylevel_id', '=', 'Competency_level.id')
                    ->where('SODAR.PDF_id', $pdfId)
                    ->select('SODAR.*', 'Competency_level.Level as competency_level')
                    ->orderBy('SODAR.id')
                    ->get();
                $coreCompetencies = DB::table('PDF_Corecompetencies')
                    ->leftJoin('Competency_level', 'PDF_Corecompetencies.CompetencyLevel_id', '=', 'Competency_level.id')
                    ->where('PDF_Corecompetencies.PDF_id', $pdfId)
                    ->select('PDF_Corecompetencies.*', 'Competency_level.Level as competency_level')
                    ->orderBy('PDF_Corecompetencies.id')
                    ->get();
                $leadershipCompetencies = DB::table('PDF_LeadershipCompetencies')
                    ->leftJoin('Competency_level', 'PDF_LeadershipCompetencies.CompetencyLevel_id', '=', 'Competency_level.id')
                    ->where('PDF_LeadershipCompetencies.PDF_id', $pdfId)
                    ->select('PDF_LeadershipCompetencies.*', 'Competency_level.Level as competency_level')
                    ->orderBy('PDF_LeadershipCompetencies.id')
                    ->get();

                $employeeFullname = trim(implode(' ', array_filter([
                    $pdfRecord->emp_first ?? '',
                    $pdfRecord->emp_middle ?? '',
                    $pdfRecord->emp_last ?? '',
                ])));

                $supervisorFullname = trim(implode(' ', array_filter([
                    $pdfRecord->sup_first ?? '',
                    $pdfRecord->sup_middle ?? '',
                    $pdfRecord->sup_last ?? '',
                ])));

                $hasEmployeeDateOverride = $request->exists('employee_date');
                $hasSupervisorDateOverride = $request->exists('supervisor_date');
                $employeeDateInput = $request->input('employee_date');
                $supervisorDateInput = $request->input('supervisor_date');

                $employeeDate = $hasEmployeeDateOverride
                    ? (!empty($employeeDateInput) ? date('F d, Y', strtotime($employeeDateInput)) : '')
                    : (!empty($pdfRecord->employee_date) ? date('F d, Y', strtotime($pdfRecord->employee_date)) : date('F d, Y'));
                $supervisorDate = $hasSupervisorDateOverride
                    ? (!empty($supervisorDateInput) ? date('F d, Y', strtotime($supervisorDateInput)) : '')
                    : (!empty($pdfRecord->supervisor_date) ? date('F d, Y', strtotime($pdfRecord->supervisor_date)) : date('F d, Y'));

                // Fetch supervised positions (up to 7 rows) from new table, with legacy fallback
                $supervisedPositionsList = [];

                try {
                    $supervisedRows = DB::table('PDF_SupervisedPositions')
                        ->leftJoin('positions', 'PDF_SupervisedPositions.supervised_positionTitle_ID', '=', 'positions.id')
                        ->where('PDF_SupervisedPositions.PDF_id', $pdfId)
                        ->select(
                            'PDF_SupervisedPositions.*',
                            'positions.name as title'
                        )
                        ->orderBy('PDF_SupervisedPositions.id')
                        ->limit(7)
                        ->get();

                    foreach ($supervisedRows as $row) {
                        $supervisedPositionsList[] = [
                            'title' => $row->title ?? 'N/A',
                            'item_number' => $row->supervised_item_number ?? 'N/A',
                        ];
                    }
                } catch (\Exception $e) {
                    // If anything goes wrong with the new table, fall back to legacy fields
                }

                if (count($supervisedPositionsList) === 0) {
                    $legacyTitle = 'N/A';
                    $legacyItem = 'N/A';

                    if ($pdfRecord->supervised_positionTitle_ID) {
                        $supervisedPosition = DB::table('positions')
                            ->where('id', $pdfRecord->supervised_positionTitle_ID)
                            ->first();
                        if ($supervisedPosition) {
                            $legacyTitle = $supervisedPosition->name ?? 'N/A';
                        }

                        $supervisedPlantilla = DB::table('plantillas')
                            ->where('position_id', $pdfRecord->supervised_positionTitle_ID)
                            ->where('active', true)
                            ->first();
                        if ($supervisedPlantilla) {
                            $legacyItem = $supervisedPlantilla->code ?? 'N/A';
                        } elseif ($pdfRecord->supervised_item_number) {
                            $legacyItem = $pdfRecord->supervised_item_number;
                        }
                    } elseif ($pdfRecord->supervised_item_number) {
                        $legacyItem = $pdfRecord->supervised_item_number;
                    }

                    $supervisedPositionsList[] = [
                        'title' => $legacyTitle,
                        'item_number' => $legacyItem,
                    ];
                }

                $firstSupervised = $supervisedPositionsList[0] ?? ['title' => 'N/A', 'item_number' => 'N/A'];

                $positionData = [
                    'position_title' => $pdfRecord->position_title ?? '',
                    'item_number' => $pdfRecord->item_number ?? '',
                    'salary_grade' => $pdfRecord->salary_grade_name ?? '',
                    'authorized_salary' => $pdfRecord->salary ?? '',
                    'other_compensation' => $pdfRecord->other_compensation ?? '',
                    'machines_tools' => $pdfRecord->equiptment ?? 'Computer/Laptop, Printer, Telephone, External Hard Drive, Switches, UTP Cable, Television, Headset and Microphone',
                    'unit_function' => $pdfRecord->unit_description ?? '',
                    'job_summary' => $pdfRecord->position_description ?? '',
                    'education' => $pdfRecord->education ?? '',
                    'experience' => $pdfRecord->experience ?? '',
                    'training' => $pdfRecord->training ?? '',
                    'eligibility' => $pdfRecord->eigibility ?? '',
                    'employee_name' => $employeeFullname,
                    'supervisor_name' => $supervisorFullname,
                    'employee_date' => $employeeDate,
                    'supervisor_date' => $supervisorDate,
                    // Section 15 - Supervised Position (first row + full list)
                    'supervised_position_title' => $firstSupervised['title'],
                    'supervised_item_number' => $firstSupervised['item_number'],
                    'supervised_positions' => $supervisedPositionsList,
                ];

                // Map SODAR records
                $index = 1;
                foreach ($sodarRecords as $sr) {
                    if ($index > 20) break;
                    $raw = is_null($sr->Percetage) ? '0' : (string)$sr->Percetage;
                    $num = (float)str_replace('%', '', trim($raw));
                    if (strpos($raw, '%') === false && $num > 0 && $num <= 1) {
                        $num = $num * 100;
                    }
                    if ($num <= 0) continue;
                    $formattedPercent = fmod($num, 1.0) > 0
                        ? rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.')
                        : number_format($num, 0, '.', '');
                    $positionData['duty_' . $index . '_percentage'] = $formattedPercent . '%';
                    $positionData['duty_' . $index . '_description'] = str_replace('&', 'and', $sr->Responsibilities ?? '');
                    $positionData['duty_' . $index . '_level'] = $sr->competency_level ?? '';
                    $index++;
                }

                if ($coreCompetencies->count() > 0) {
                    $positionData['core_competencies'] = str_replace('&', 'and', implode(' ', $coreCompetencies->pluck('Competency')->filter()->all()));
                    $coreLevels = $coreCompetencies->pluck('competency_level')->filter()->unique()->values()->all();
                    $positionData['core_level'] = count($coreLevels) ? implode("\n", $coreLevels) : 'INTERMEDIATE';
                } else {
                    $positionData['core_competencies'] = "Technologically Savvy Effective Communication Customer Focus Results Driven Team Player Knowledge Management";
                    $positionData['core_level'] = 'INTERMEDIATE';
                }

                if ($leadershipCompetencies->count() > 0) {
                    $positionData['leadership_competencies'] = str_replace('&', 'and', implode("\n", $leadershipCompetencies->map(function($lc){ return $lc->COmpetency ?? $lc->Competency; })->filter()->all()));
                    $levels = $leadershipCompetencies->pluck('competency_level')->filter()->unique()->values()->all();
                    $positionData['leadership_level'] = count($levels) ? implode("\n", $levels) : 'INTERMEDIATE';
                } else {
                    $positionData['leadership_competencies'] = "Building collaborative, inclusive working relationships\nManaging performance and coaching results\nLeading change\nThinking strategically and creatively\nCreating and nurturing a high performing organization";
                    $positionData['leadership_level'] = 'INTERMEDIATE';
                }

                // Handle supervisor position titles (sections 13 & 14)
                // Check request first, then database, then defaults
                $immediateSupervisorPositionId = $request->input('immediate_supervisor_position_id') ?? $pdfRecord->immediate_supervisor_position_id ?? null;
                $nextHigherSupervisorPositionId = $request->input('next_higher_supervisor_position_id') ?? $pdfRecord->next_higher_supervisor_position_id ?? null;

                if ($immediateSupervisorPositionId) {
                    $immediatePosition = DB::table('positions')->where('id', $immediateSupervisorPositionId)->first();
                    $positionData['immediate_supervisor'] = $immediatePosition->name ?? '';
                } else {
                    // Default hardcoded values if not provided
                    $positionData['immediate_supervisor'] = "SUPERVISING\nTRADE-INDUSTRY\nDEVELOPMENT SPECIALIST\n\nCHIEF TRADE-INDUSTRY\nDEVELOPMENT SPECIALIST";
                }

                if ($nextHigherSupervisorPositionId) {
                    $nextHigherPosition = DB::table('positions')->where('id', $nextHigherSupervisorPositionId)->first();
                    $positionData['next_higher_supervisor'] = $nextHigherPosition->name ?? '';
                } else {
                    // Default hardcoded values if not provided
                    $positionData['next_higher_supervisor'] = "DEPUTY EXECUTIVE DIRECTOR\n\nEXECUTIVE DIRECTOR";
                }
            } else {
                $positionData = $request->all();
            if (isset($positionData['position']) && $positionData['position']) {
                $position = Position::find($positionData['position']);
                if ($position) {
                    $positionData['position_title'] = $position->name;
                    $positionData['item_number'] = $position->code;
                }
            }
            $positionData = array_merge([
                'position_title' => '',
                'item_number' => '',
                'salary_grade' => '',
                    'authorized_salary' => '',
                    'other_compensation' => '',
                    'machines_tools' => 'Computer/Laptop, Printer, Telephone, External Hard Drive, Switches, UTP Cable, Television, Headset and Microphone',
                    'unit_function' => '',
                    'job_summary' => '',
                'education' => '',
                'experience' => '',
                'training' => '',
                'eligibility' => '',
                    'core_competencies' => "Technologically Savvy Effective Communication Customer Focus Results Driven Team Player Knowledge Management",
                    'core_level' => 'INTERMEDIATE',
                    'leadership_competencies' => "Building collaborative, inclusive working relationships\nManaging performance and coaching results\nLeading change\nThinking strategically and creatively\nCreating and nurturing a high performing organization",
                    'leadership_level' => 'INTERMEDIATE',
                    'employee_name' => '',
                    'supervisor_name' => '',
                    'employee_date' => date('F d, Y'),
                    'supervisor_date' => date('F d, Y'),
            ], $positionData);

            // Handle supervisor position titles (sections 13 & 14) for fallback
            $immediateSupervisorPositionId = $request->input('immediate_supervisor_position_id');
            $nextHigherSupervisorPositionId = $request->input('next_higher_supervisor_position_id');

            if ($immediateSupervisorPositionId) {
                $immediatePosition = DB::table('positions')->where('id', $immediateSupervisorPositionId)->first();
                $positionData['immediate_supervisor'] = $immediatePosition->name ?? '';
            } else {
                $positionData['immediate_supervisor'] = "SUPERVISING\nTRADE-INDUSTRY\nDEVELOPMENT SPECIALIST\n\nCHIEF TRADE-INDUSTRY\nDEVELOPMENT SPECIALIST";
            }

            if ($nextHigherSupervisorPositionId) {
                $nextHigherPosition = DB::table('positions')->where('id', $nextHigherSupervisorPositionId)->first();
                $positionData['next_higher_supervisor'] = $nextHigherPosition->name ?? '';
            } else {
                $positionData['next_higher_supervisor'] = "DEPUTY EXECUTIVE DIRECTOR\n\nEXECUTIVE DIRECTOR";
            }
            }

            // Sanitize all string values in positionData to replace "&" with "and"
            $positionData = array_map(function($value) {
                if (is_string($value)) {
                    return str_replace('&', 'and', $value);
                }
                return $value;
            }, $positionData);

            // Generate DOCX
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(9); // Minimized font size

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(0.4),
                'marginRight' => Converter::inchToTwip(0.6),
                'marginBottom' => Converter::inchToTwip(0.4),
                'marginLeft' => Converter::inchToTwip(0.35),
                'pageSizeW' => Converter::inchToTwip(8.27),
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

            // Paragraph styles with first line indentation
            $phpWord->addParagraphStyle('indent', [
                'indentation' => ['firstLine' => Converter::inchToTwip(0.2)],
                'spaceAfter' => 0,
            ]);

            // Calculate available page width
            $pageWidth = Converter::inchToTwip(8.27 - 0.47 - 0.47); // 7.33 inches

            // Position Description table wrapper
            $posDescTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMarginTop' => Converter::pixelToTwip(10),
                'cellMarginRight' => Converter::pixelToTwip(15),
                'cellMarginBottom' => Converter::pixelToTwip(10),
                'cellMarginLeft' => Converter::pixelToTwip(15),
            ]);

            // Wrapper row
            $posDescTable->addRow();
            $wrapperCell = $posDescTable->addCell($pageWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Inner table inside wrapper cell
            $innerTable = $wrapperCell->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Add first row
            $innerTable->addRow();

            // First column with rowspan 2 
            $cell1 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3, 
                'vMerge' => 'restart',
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $textRun1 = $cell1->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1.5,
                'spaceAfter' => 120,
                'spaceBefore' => 120,
            ]);

            $textRun1->addText('Republic of the Philippines', ['size' => 8, 'bold' => true]);
            $textRun1->addTextBreak();
            $textRun1->addText('POSITION DESCRIPTION FORM', ['size' => 8, 'bold' => true]);
            $textRun1->addTextBreak();
            $textRun1->addText('DBM-CSC Form No. 1', ['size' => 8, 'bold' => true]);
            $textRun1->addTextBreak();
            $textRun1->addText('(Revised version No. 1, 2017)', ['size' => 7, 'bold' => true, 'color' => 'A8A6A6']);

            // Second column (first row) - POSITION TITLE header
            $cell2 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'B9B8B8', // Gray background
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top',
            ]);

            $cell2->addText('1. POSITION TITLE (as approved by authorized agency) with parenthetical title', [ 'size' => 8,'bold' => true, ],
                ['alignment' => WordJc::LEFT,
                    'spaceAfter' => 0,
                    'spaceBefore' => 0,
                ]
            );

            // Add second row
            $innerTable->addRow();

            // First column continued (rowspan)
            $cell3 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'vMerge' => 'continue',
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Second column (second row) - POSITION TITLE value
            $cell4 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell4->addText(strtoupper($positionData['position_title'] ?? ''), [ 'size' => 7,],
                [
                    'alignment' => WordJc::CENTER,
                    'spaceAfter' => 0,
                    'spaceBefore' => 0,
                ]
            );

            // Add third row
            $innerTable->addRow();

            // Third row, first column - ITEM NUMBER header
            $cell5 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'B9B8B8', // Gray background
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell5->addText('2. ITEM NUMBER',[ 'size' => 8,'bold' => true,],
                [
                    'alignment' => WordJc::LEFT,
                    'spaceAfter' => 0,
                    'spaceBefore' => 0,
                ]
            );

            // Third row, second column - SALARY GRADE header
            $cell6 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 6,
                'bgColor' => 'B9B8B8', // Gray background
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell6->addText('3. SALARY GRADE', ['size' => 8,'bold' => true,],
                [
                    'alignment' => WordJc::LEFT,
                    'spaceAfter' => 0,
                    'spaceBefore' => 0,
                ]
            );

            // Add fourth row
            $innerTable->addRow();
            $cell7 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
            $cell7->addText($positionData['item_number'] ?? '', [ 'size' => 7,],
                [
                    'alignment' => WordJc::CENTER,
                    'spaceAfter' => 300, // Add some padding
                    'spaceBefore' => 300,
                ]
            );
            $cell8 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
            $cell8->addText($positionData['salary_grade'] ?? '', [ 'size' => 7,],
                [
                    'alignment' => WordJc::CENTER,
                    'spaceAfter' => 300, // Add some padding
                    'spaceBefore' => 300,
                ]
            );

            // Add fifth row - Local Government header
            $innerTable->addRow();

            $cell9 = $innerTable->addCell($pageWidth, [
                'gridSpan' => 6,
                'bgColor' => 'B9B8B8', // Gray background
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell9->addText('4. FOR LOCAL GOVERNMENT POSITION, ENUMERATE GOVERNMENTAL UNIT AND CLASS',['size' => 8,'bold' => true,],
                [
                    'alignment' => WordJc::LEFT,
                    'spaceAfter' => 0,
                    'spaceBefore' => 0,
                ]
            );

            // Add sixth row - Checkbox options
            $innerTable->addRow();

            $cell10 = $innerTable->addCell($pageWidth, [
                'gridSpan' => 6,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Create a nested table for the three columns of checkboxes
            $checkboxTable = $cell10->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            $checkboxTable->addRow();

            // First column - Province/City/Municipality
            $checkCol1 = $checkboxTable->addCell($pageWidth * 0.33, [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $checkCol1->addText('☐ Province', ['size' => 8], [
                'alignment' => WordJc::LEFT,
                'indentation' => ['left' => Converter::pixelToTwip(75)],
                'spaceAfter' => 50
            ]);
            $checkCol1->addText('☐ City', ['size' => 8], [
                'alignment' => WordJc::LEFT,
                'indentation' => ['left' => Converter::pixelToTwip(75)],
                'spaceAfter' => 50
            ]);
            $checkCol1->addText('☐ Municipality', ['size' => 8], [
                'alignment' => WordJc::LEFT,
                'indentation' => ['left' => Converter::pixelToTwip(75)],
                'spaceAfter' => 50
            ]);

            // Second column - 1st-4th Class
            $checkCol2 = $checkboxTable->addCell($pageWidth * 0.33, [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $checkCol2->addText('☐ 1st Class', ['size' => 8], [
                'alignment' => WordJc::LEFT,
                'indentation' => ['left' => Converter::pixelToTwip(75)],
                'spaceAfter' => 50
            ]);
            $checkCol2->addText('☐ 2nd Class', ['size' => 8], [
                'alignment' => WordJc::LEFT,
                'indentation' => ['left' => Converter::pixelToTwip(75)],
                'spaceAfter' => 50
            ]);
            $checkCol2->addText('☐ 3rd Class', ['size' => 8], [
                'alignment' => WordJc::LEFT,
                'indentation' => ['left' => Converter::pixelToTwip(75)],
                'spaceAfter' => 50
            ]);
            $checkCol2->addText('☐ 4th Class', ['size' => 8], [
                'alignment' => WordJc::LEFT,
                'indentation' => ['left' => Converter::pixelToTwip(75)],
                'spaceAfter' => 50
            ]);

            // Third column - 5th/6th/Special
            $checkCol3 = $checkboxTable->addCell($pageWidth * 0.33, [
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $checkCol3->addText('☐ 5th Class', ['size' => 8], [
                'alignment' => WordJc::LEFT,
                'indentation' => ['left' => Converter::pixelToTwip(75)],
                'spaceAfter' => 50
            ]);
            $checkCol3->addText('☐ 6th Class', ['size' => 8], [
                'alignment' => WordJc::LEFT,
                'indentation' => ['left' => Converter::pixelToTwip(75)],
                'spaceAfter' => 50
            ]);
            $checkCol3->addText('☐ Special', ['size' => 8], [
                'alignment' => WordJc::LEFT,
                'indentation' => ['left' => Converter::pixelToTwip(75)],
                'spaceAfter' => 50
            ]);

            // Add seventh row - Department and Bureau headers
            $innerTable->addRow();

            // Seventh row, first column - DEPARTMENT header
            $cell11 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'B9B8B8', // Gray background
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell11->addText('5. DEPARTMENT, CORPORATION OR AGENCY/ LOCAL GOVERNMENT', ['size' => 8,'bold' => true,],
                [
                    'alignment' => WordJc::LEFT,
                    'spaceAfter' => 0,
                    'spaceBefore' => 0,
                ]
            );

            // Seventh row, second column - BUREAU header
            $cell12 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'B9B8B8', // Gray background
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell12->addText('6. BUREAU OR OFFICE', ['size' => 8,'bold' => true,],
                [
                    'alignment' => WordJc::LEFT,
                    'spaceAfter' => 0,
                    'spaceBefore' => 0,
                ]
            );

            // Add eighth row - Department and Bureau values
            $innerTable->addRow();

            // Eighth row, first column - DEPARTMENT value
            $cell13 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell13->addText(strtoupper($positionData['department'] ?? 'DEPARTMENT OF TRADE AND INDUSTRY'),['size' => 7,],
                [
                    'alignment' => WordJc::CENTER,
                    'spaceAfter' => 300,
                    'spaceBefore' => 300,
                ]
            );

            // Eighth row, second column - BUREAU value
            $cell14 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell14->addText(strtoupper($positionData['office'] ?? CompanyHelper::getName()),['size' => 7,],
                [
                    'alignment' => WordJc::CENTER,
                    'spaceAfter' => 300,
                    'spaceBefore' => 300,
                ]
            );

            // Add ninth row - Division and Location headers
            $innerTable->addRow();

            // Ninth row, first column - DIVISION header
            $cell15 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'B9B8B8', // Gray background
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell15->addText('7. DEPARTMENT / BRANCH / DIVISION',['size' => 8,'bold' => true,],
                [
                    'alignment' => WordJc::LEFT,
                    'spaceAfter' => 0,
                    'spaceBefore' => 0,
                ]
            );

            // Ninth row, second column - WORKSTATION header
            $cell16 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'B9B8B8', // Gray background
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell16->addText('8. WORKSTATION / PLACE OF WORK',['size' => 8,'bold' => true,],
                [
                    'alignment' => WordJc::LEFT,
                    'spaceAfter' => 0,
                    'spaceBefore' => 0,
                ]
            );

            // Add tenth row - Division and Location values
            $innerTable->addRow();

            // Tenth row, first column - DIVISION value
            $cell17 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell17->addText(strtoupper($positionData['division'] ?? 'Office of the Executive Director (OED)'),['size' => 7,],
                [
                    'alignment' => WordJc::CENTER,
                    'spaceAfter' => 300,
                    'spaceBefore' => 300,
                ]
            );

            // Tenth row, second column - LOCATION value
            $cell18 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell18->addText(strtoupper($positionData['location'] ?? CompanyHelper::getAddress()),['size' => 7,],
                [
                    'alignment' => WordJc::CENTER,
                    'spaceAfter' => 300,
                    'spaceBefore' => 300,
                ]
            );

            // Add eleventh row - Appropriation and Salary headers (4 columns)
            $innerTable->addRow();

            // 9. PRESENT APPROP ACT
            $cell19 = $innerTable->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell19->addText('9. PRESENT APPROP ACT', ['size' => 7, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // 10. PREVIOUS APPROP ACT
            $cell20 = $innerTable->addCell($pageWidth * 0.25, [
                'gridSpan' => 2,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
            $cell20->addText('10. PREVIOUS APPROP ACT', ['size' => 7, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // 11. SALARY AUTHORIZED
            $cell21 = $innerTable->addCell($pageWidth * 0.25, [
                'gridSpan' => 2,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
            $cell21->addText('11. SALARY AUTHORIZED', ['size' => 7, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // 12. OTHER COMPENSATION
            $cell22 = $innerTable->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
            $cell22->addText('12. OTHER COMPENSATION', ['size' => 7, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add twelfth row - Values for rows 9-12
            $innerTable->addRow();

            // Present Approp value
            $cell23 = $innerTable->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell23->addText($positionData['present_approp'] ?? '', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 300,
                'spaceBefore' => 300,
            ]);

            // Previous Approp value
            $cell24 = $innerTable->addCell($pageWidth * 0.25, [
                'gridSpan' => 2,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);
            $cell24->addText($positionData['previous_approp'] ?? '', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 300,
                'spaceBefore' => 300,
            ]);

            // Salary Authorized value
            $cell25 = $innerTable->addCell($pageWidth * 0.25, [
                'gridSpan' => 2,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell25->addText($positionData['authorized_salary'] ?? '', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 300,
                'spaceBefore' => 300,
            ]);

            // Other Compensation value
            $cell26 = $innerTable->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell26->addText($positionData['other_compensation'] ?? '', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 300,
                'spaceBefore' => 300,
            ]);

            // Add section 13 and 14 - Supervisors
            $innerTable->addRow();

            // Header row with two columns
            // 13. Position Title of Immediate Supervisor
            $cell27 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top',
            ]);

            $cell27->addText('13. POSITION TITLE OF IMMEDIATE SUPERVISOR', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // 14. Position Title of Next Higher Supervisor
            $cell28 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top',
            ]);

            $cell28->addText('14. POSITION TITLE OF NEXT HIGHER SUPERVISOR', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add values row
            $innerTable->addRow();

            // Immediate Supervisor value
            $cell29 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $textRun29 = $cell29->addTextRun([
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 300,
                'spaceBefore' => 300,
            ]);

            $immediateSuper = $positionData['immediate_supervisor'] ?? "SUPERVISING\nTRADE-INDUSTRY\nDEVELOPMENT SPECIALIST\n\nCHIEF TRADE-INDUSTRY\nDEVELOPMENT SPECIALIST";
            $lines = explode("\n", $immediateSuper);
            foreach ($lines as $index => $line) {
                if ($index > 0) {
                    $textRun29->addTextBreak();
                }
                $textRun29->addText($line, ['size' => 7]);
            }

            // Next Higher Supervisor value
            $cell30 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $textRun30 = $cell30->addTextRun([
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 300,
                'spaceBefore' => 300,
            ]);

            $nextHigherSuper = $positionData['next_higher_supervisor'] ?? "DEPUTY EXECUTIVE DIRECTOR\n\nEXECUTIVE DIRECTOR";
            $lines2 = explode("\n", $nextHigherSuper);
            foreach ($lines2 as $index => $line) {
                if ($index > 0) {
                    $textRun30->addTextBreak();
                }
                $textRun30->addText($line, ['size' => 7]);
            }

            // Add section 15 - Position Title and Item of Those Directly Supervised
            $innerTable->addRow();

            // Header row
            $cell27 = $innerTable->addCell($pageWidth, [
                'gridSpan' => 6,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top',
            ]);

            $cell27->addText('15. POSITION TITLE AND ITEM OF THOSE DIRECTLY SUPERVISED', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add italic instruction row
            $innerTable->addRow();
            $cell28 = $innerTable->addCell($pageWidth, [
                'gridSpan' => 6,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell28->addText('(if more than seven (7) list only by their item numbers and titles)', ['size' => 7, 'italic' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add column headers row
            $innerTable->addRow();

            // Position Title header
            $cell29 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell29->addText('POSITION TITLE', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Item Number header
            $cell30 = $innerTable->addCell($pageWidth * 0.50, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell30->addText('ITEM NUMBER', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add supervised positions exactly as entered (up to 7 rows)
            $supervisedPositions = $positionData['supervised_positions'] ?? [];
            $rowCount = min(count($supervisedPositions), 7);

            if ($rowCount === 0) {
                $innerTable->addRow();

                $cell31 = $innerTable->addCell($pageWidth * 0.50, [
                    'gridSpan' => 3,
                    'bgColor' => 'FFFFFF',
                    'borderSize' => 6,
                    'borderColor' => '000000',
                    'valign' => 'center',
                ]);
                $cell31->addText('N/A', ['size' => 7], [
                    'alignment' => WordJc::CENTER,
                    'spaceAfter' => 200,
                    'spaceBefore' => 200,
                ]);

                $cell32 = $innerTable->addCell($pageWidth * 0.50, [
                    'gridSpan' => 3,
                    'bgColor' => 'FFFFFF',
                    'borderSize' => 6,
                    'borderColor' => '000000',
                    'valign' => 'center',
                ]);
                $cell32->addText('N/A', ['size' => 7], [
                    'alignment' => WordJc::CENTER,
                    'spaceAfter' => 200,
                    'spaceBefore' => 200,
                ]);
            } else {
                for ($i = 0; $i < $rowCount; $i++) {
                    $row = $supervisedPositions[$i] ?? ['title' => 'N/A', 'item_number' => 'N/A'];

                    $innerTable->addRow();

                    // Position Title value
                    $cell31 = $innerTable->addCell($pageWidth * 0.50, [
                        'gridSpan' => 3,
                        'bgColor' => 'FFFFFF',
                        'borderSize' => 6,
                        'borderColor' => '000000',
                        'valign' => 'center',
                    ]);

                    $cell31->addText($row['title'] ?? 'N/A', ['size' => 7], [
                        'alignment' => WordJc::CENTER,
                        'spaceAfter' => 0,
                        'spaceBefore' => 0,
                    ]);

                    // Item Number value
                    $cell32 = $innerTable->addCell($pageWidth * 0.50, [
                        'gridSpan' => 3,
                        'bgColor' => 'FFFFFF',
                        'borderSize' => 6,
                        'borderColor' => '000000',
                        'valign' => 'center',
                    ]);

                    $cell32->addText($row['item_number'] ?? 'N/A', ['size' => 7], [
                        'alignment' => WordJc::CENTER,
                        'spaceAfter' => 50,
                        'spaceBefore' => 50,
                    ]);
                }
            }

            // Add section 16 - Machine, Equipment, Tools
            $innerTable->addRow();

            // Header row
            $cell33 = $innerTable->addCell($pageWidth, [
                'gridSpan' => 6,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top',
            ]);

            $cell33->addText('16. MACHINE, EQUIPMENT, TOOLS, ETC., USED REGULARLY IN PERFORMANCE OF WORK', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add values row
            $innerTable->addRow();

            $cell34 = $innerTable->addCell($pageWidth, [
                'gridSpan' => 6,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell34->addText($positionData['machines_tools'] ?? 'Computer/Laptop, Printer, Telephone, External Hard Drive, Switches, UTP Cable, Television, Headset and Microphone', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 300,
                'spaceBefore' => 300,
            ]);

            // Add section 17 - Contacts/Clients/Stakeholders
            $innerTable->addRow();

            // Main header row
            $cell35 = $innerTable->addCell($pageWidth, [
                'gridSpan' => 6,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top',
            ]);

            $cell35->addText('17. CONTACTS / CLIENTS / STAKEHOLDERS', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add wrapper cell for nested table
            $innerTable->addRow();

            $cell36 = $innerTable->addCell($pageWidth, [
                'gridSpan' => 6,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Create nested table inside the wrapper cell
            $contactTable = $cell36->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Define equal column width (100% / 6 = 16.67%)
            $columnWidth = $pageWidth / 6;

            // Add column headers row
            $contactTable->addRow();

            // 17a. Internal
            $contactTable->addCell($columnWidth, [
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ])->addText('17a. Internal', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Occasional
            $contactTable->addCell($columnWidth, [
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ])->addText('Occasional', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Frequent
            $contactTable->addCell($columnWidth, [
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ])->addText('Frequent', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // 17b. External
            $contactTable->addCell($columnWidth, [
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ])->addText('17b. External', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Occasional
            $contactTable->addCell($columnWidth, [
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ])->addText('Occasional', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Frequent
            $contactTable->addCell($columnWidth, [
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ])->addText('Frequent', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Row 1: Executive/Managerial - General Public
            $contactTable->addRow();

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('Executive / Managerial', ['size' => 7], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('General Public', ['size' => 7], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // Row 2: Supervisors - Other Agencies
            $contactTable->addRow();

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('Supervisors', ['size' => 7], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('Other Agencies', ['size' => 7], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // Row 3: Non-Supervisors - Others
            $contactTable->addRow();

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('Non-Supervisors', ['size' => 7], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('Others (Please Specify):', ['size' => 7], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // Row 4: Staff - (empty cells)
            $contactTable->addRow();

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('Staff', ['size' => 7], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('', ['size' => 7], [
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('', ['size' => 7], [
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $contactTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('', ['size' => 7], [
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // Add section 18 - Working Condition
            $innerTable->addRow();

            // Main header row
            $cell66 = $innerTable->addCell($pageWidth, [
                'gridSpan' => 6,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top',
            ]);

            $cell66->addText('18. WORKING CONDITION', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add wrapper cell for nested table
            $innerTable->addRow();

            $cell67 = $innerTable->addCell($pageWidth, [
                'gridSpan' => 6,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Create nested table inside the wrapper cell
            $workingTable = $cell67->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Use the same column width as section 17
            $columnWidth = $pageWidth / 6;

            // Row 1: Office Work
            $workingTable->addRow();

            $workingTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('Office Work', ['size' => 7], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $workingTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $workingTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $workingTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('Other/s (Please Specify)', ['size' => 7], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $workingTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('', ['size' => 7], [
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $workingTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('', ['size' => 7], [
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // Row 2: Field Work
            $workingTable->addRow();

            $workingTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('Field Work', ['size' => 7], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $workingTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $workingTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('☐', ['size' => 8], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $workingTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('', ['size' => 7], [
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $workingTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('', ['size' => 7], [
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            $workingTable->addCell($columnWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ])->addText('', ['size' => 7], [
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // Close the first page table structure
            // (End of section 18)

            // Add page break
            $section->addPageBreak();

            // Create new wrapper table for page 2
            $posDescTable2 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMarginTop' => Converter::pixelToTwip(10),
                'cellMarginRight' => Converter::pixelToTwip(15),
                'cellMarginBottom' => Converter::pixelToTwip(10),
                'cellMarginLeft' => Converter::pixelToTwip(15),
            ]);

            // Wrapper row for page 2
            $posDescTable2->addRow();
            $wrapperCell2 = $posDescTable2->addCell($pageWidth, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            // Inner table inside wrapper cell for page 2
            $innerTable2 = $wrapperCell2->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Add section 19 - Brief Description of the General Function of the Unit or Section
            $innerTable2->addRow();

            // Header row
            $cell68 = $innerTable2->addCell($pageWidth, [
                'gridSpan' => 4,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top',
            ]);

            $cell68->addText('19. BRIEF DESCRIPTION OF THE GENERAL FUNCTION OF THE UNIT OR SECTION', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add values row
            $innerTable2->addRow();

            $cell69 = $innerTable2->addCell($pageWidth, [
                'gridSpan' => 4,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell69->addText($positionData['unit_function'] ?? '', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 350,
                'spaceBefore' => 350,
            ]);

            // Add section 20 - Job Summary
            $innerTable2->addRow();

            // Header row
            $cell70 = $innerTable2->addCell($pageWidth, [
                'gridSpan' => 4,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top',
            ]);

            $cell70->addText('20. BRIEF DESCRIPTION OF THE GENERAL FUNCTION OF THE POSITION (Job Summary)', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add values row
            $innerTable2->addRow();

            $cell71 = $innerTable2->addCell($pageWidth, [
                'gridSpan' => 4,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell71->addText($positionData['job_summary'] ?? '', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 350,
                'spaceBefore' => 350,
            ]);

            // Add section 21 - Qualification Standards
            $innerTable2->addRow();

            // Main header row
            $cell72 = $innerTable2->addCell($pageWidth, [
                'gridSpan' => 4,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top',
            ]);

            $cell72->addText('21. QUALIFICATION STANDARDS', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add column headers row (4 columns)
            $innerTable2->addRow();

            // 21a. Education
            $cell73 = $innerTable2->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell73->addText('21a. Education', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // 21b. Experience
            $cell74 = $innerTable2->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell74->addText('21b. Experience', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // 21c. Training
            $cell75 = $innerTable2->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell75->addText('21c. Training', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // 21d. Eligibility
            $cell76 = $innerTable2->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell76->addText('21d. Eligibility', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // Add values row
            $innerTable2->addRow();

            // Education value
            $cell77 = $innerTable2->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell77->addText($positionData['education'] ?? '', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 350,
                'spaceBefore' => 350,
            ]);

            // Experience value
            $cell78 = $innerTable2->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell78->addText($positionData['experience'] ?? '', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 350,
                'spaceBefore' => 350,
            ]);

            // Training value
            $cell79 = $innerTable2->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell79->addText($positionData['training'] ?? '', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 350,
                'spaceBefore' => 350,
            ]);

            // Eligibility value
            $cell80 = $innerTable2->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell80->addText($positionData['eligibility'] ?? '', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 350,
                'spaceBefore' => 350,
            ]);

            // Add section 21e - Core Competencies
            $innerTable2->addRow();

            // Header row with 2 columns
            // Core Competencies label
            $cell81 = $innerTable2->addCell($pageWidth * 0.75, [
                'gridSpan' => 3,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell81->addText('21e. Core Competencies', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // Competency Level label
            $cell82 = $innerTable2->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell82->addText('Competency Level', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // Add values row
            $innerTable2->addRow();

            // Core Competencies value
            $cell83 = $innerTable2->addCell($pageWidth * 0.75, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $textRun83 = $cell83->addTextRun([
                'alignment' => WordJc::CENTER,
            ]);

            $textRun83->addText('(Indicate the required Core Competencies here)', ['size' => 7, 'italic' => true]);
            $textRun83->addTextBreak();
            $textRun83->addTextBreak();

            $coreText = $positionData['core_competencies'] ?? '';
            $coreLines = preg_split('/\r\n|\r|\n/', (string) $coreText);
            foreach ($coreLines as $index => $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }
                if ($index > 0) {
                    $textRun83->addTextBreak();
                }
                $textRun83->addText($trimmed, ['size' => 7]);
            }

            // Competency Level value
            $cell84 = $innerTable2->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $textRun84 = $cell84->addTextRun([
                'alignment' => WordJc::CENTER,
            ]);

            $textRun84->addText('(Indicate the required Competency Level here)', ['size' => 7, 'italic' => true]);
            $textRun84->addTextBreak();
            $textRun84->addTextBreak();
            $coreLevelText = $positionData['core_level'] ?? 'INTERMEDIATE';
            $coreLevelLines = preg_split('/\r\n|\r|\n/', (string) $coreLevelText);
            foreach ($coreLevelLines as $index => $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }
                if ($index > 0) {
                    $textRun84->addTextBreak();
                }
                $textRun84->addText($trimmed, ['size' => 7]);
            }

            // Add section 21f - Leadership Competencies
            $innerTable2->addRow();

            // Header row with 2 columns
            // Leadership Competencies label
            $cell85 = $innerTable2->addCell($pageWidth * 0.75, [
                'gridSpan' => 3,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell85->addText('21f. Leadership Competencies', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // Competency Level label
            $cell86 = $innerTable2->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $cell86->addText('Competency Level', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 50,
                'spaceBefore' => 50,
            ]);

            // Add values row
            $innerTable2->addRow();

            // Leadership Competencies value
            $cell87 = $innerTable2->addCell($pageWidth * 0.75, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $textRun87 = $cell87->addTextRun([
                'alignment' => WordJc::CENTER,
            ]);

            $textRun87->addText('(Indicate the required Leadership Competencies here)', ['size' => 7, 'italic' => true]);
            $textRun87->addTextBreak();
            $textRun87->addTextBreak();

            $leadText = $positionData['leadership_competencies'] ?? '';
            $leadLines = preg_split('/\r\n|\r|\n/', (string) $leadText);
            foreach ($leadLines as $index => $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }
                if ($index > 0) {
                    $textRun87->addTextBreak();
                }
                $textRun87->addText($trimmed, ['size' => 7]);
            }

            // Competency Level value
            $cell88 = $innerTable2->addCell($pageWidth * 0.25, [
                'gridSpan' => 1,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $textRun88 = $cell88->addTextRun([
                'alignment' => WordJc::CENTER,
            ]);

            $textRun88->addText('(Indicate the required Competency Level here)', ['size' => 7, 'italic' => true]);
            $textRun88->addTextBreak();
            $textRun88->addTextBreak();
            $leadLevelText = $positionData['leadership_level'] ?? 'INTERMEDIATE';
            $leadLevelLines = preg_split('/\r\n|\r|\n/', (string) $leadLevelText);
            foreach ($leadLevelLines as $index => $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }
                if ($index > 0) {
                    $textRun88->addTextBreak();
                }
                $textRun88->addText($trimmed, ['size' => 7]);
            }


            // Add section 22 header row
            $innerTable2->addRow();

            $cell_22_header = $innerTable2->addCell($pageWidth, [
                'gridSpan' => 4,
                'bgColor' => 'B9B8B8',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'top',
            ]);

            $cell_22_header->addText('22. STATEMENT OF DUTIES AND RESPONSIBILITIES (Technical Competencies)', ['size' => 8, 'bold' => true], [
                'alignment' => WordJc::LEFT,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Add wrapper cell for nested table
            $innerTable2->addRow();

            $cell_22_wrapper = $innerTable2->addCell($pageWidth, [
                'gridSpan' => 4,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Create nested table inside the wrapper cell
            $dutiesTable = $cell_22_wrapper->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Nested table header row
            $dutiesTable->addRow();

            // Percentage column header
            $dutiesTable->addCell($pageWidth * 0.20, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ])->addText('Percentage of Working Time', ['size' => 7, 'italic' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Duties description column header
            $dutiesTable->addCell($pageWidth * 0.55, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ])->addText('(State the duties and responsibilities here)', ['size' => 7, 'italic' => true], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Competency Level column header (with rowspan)
            $competencyCell = $dutiesTable->addCell($pageWidth * 0.25, [
                'vMerge' => 'restart',
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            $textRunComp = $competencyCell->addTextRun([
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $textRunComp->addText('(Indicate the required Competency Level here)', ['size' => 7, 'italic' => true]);
            $textRunComp->addTextBreak();
            $textRunComp->addTextBreak();
            $textRunComp->addText($positionData['duty_1_level'] ?? 'Advanced', ['size' => 7]);

            // Data row for duty 1
            $dutiesTable->addRow();

            // Percentage value
            $dutiesTable->addCell($pageWidth * 0.20, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ])->addText($positionData['duty_1_percentage'] ?? '33.33%', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 250,
                'spaceBefore' => 250,
            ]);

            // Duties description value
            $dutiesTable->addCell($pageWidth * 0.55, [
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ])->addText($positionData['duty_1_description'] ?? 'Develops and design course content outline based on the results of the TNA survey;', ['size' => 7], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 250,
                'spaceBefore' => 250,
            ]);

            // Competency Level continued (vMerge)
            $dutiesTable->addCell($pageWidth * 0.25, [
                'vMerge' => 'continue',
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => '000000',
                'valign' => 'center',
            ]);

            // Add rows for duty_2 through duty_20 if they have data
            for ($i = 2; $i <= 20; $i++) {
                $percentageKey = 'duty_' . $i . '_percentage';
                $descriptionKey = 'duty_' . $i . '_description';
                
                // Only add row if duty has data
                if (!empty($positionData[$percentageKey]) || !empty($positionData[$descriptionKey])) {
                    $dutiesTable->addRow();

                    // Percentage value
                    $dutiesTable->addCell($pageWidth * 0.20, [
                        'bgColor' => 'FFFFFF',
                        'borderSize' => 6,
                        'borderColor' => '000000',
                        'valign' => 'center',
                    ])->addText($positionData[$percentageKey] ?? '', ['size' => 7], [
                        'alignment' => WordJc::CENTER,
                        'spaceAfter' => 250,
                        'spaceBefore' => 250,
                    ]);

                    // Duties description value
                    $dutiesTable->addCell($pageWidth * 0.55, [
                        'bgColor' => 'FFFFFF',
                        'borderSize' => 6,
                        'borderColor' => '000000',
                        'valign' => 'center',
                    ])->addText($positionData[$descriptionKey] ?? '', ['size' => 7], [
                        'alignment' => WordJc::CENTER,
                        'spaceAfter' => 250,
                        'spaceBefore' => 250,
                    ]);

                    // Competency Level continued (vMerge)
                    $dutiesTable->addCell($pageWidth * 0.25, [
                        'vMerge' => 'continue',
                        'bgColor' => 'FFFFFF',
                        'borderSize' => 6,
                        'borderColor' => '000000',
                        'valign' => 'center',
                    ]);
                }
            }

            // Add section 23 - Acknowledgment and Acceptance
$innerTable2->addRow();

// Header row
$cell_23_header = $innerTable2->addCell($pageWidth, [
    'gridSpan' => 4,
    'bgColor' => 'B9B8B8',
    'borderSize' => 6,
    'borderColor' => '000000',
    'valign' => 'top',
]);

$cell_23_header->addText('23. ACKNOWLEDGMENT AND ACCEPTANCE', ['size' => 8, 'bold' => true], [
    'alignment' => WordJc::LEFT,
    'spaceAfter' => 0,
    'spaceBefore' => 0,
]);

// Wrapper cell for nested table
$innerTable2->addRow();

$cell_23_wrapper = $innerTable2->addCell($pageWidth, [
    'gridSpan' => 4,
    'bgColor' => 'FFFFFF',
    'borderSize' => 6,
    'borderColor' => '000000',
    'valign' => 'center',
]);

// Create nested table for acknowledgment text and signatures
$acknowledgmentTable = $cell_23_wrapper->addTable([
    'borderSize' => 0,
    'borderColor' => 'FFFFFF',
    'width' => 100 * 50,
    'unit' => 'pct',
]);

// Row 1: Acknowledgment text (spans full width)
$acknowledgmentTable->addRow();

$ackTextCell = $acknowledgmentTable->addCell($pageWidth, [
    'gridSpan' => 2,
    'borderSize' => 0,
    'borderColor' => 'FFFFFF',
    'valign' => 'center',
]);

$ackTextCell->addText('          I have received a copy of this Position Description Form. It has been discussed with me and I have freely chosen to comply with the performance and behavior/conduct expectations contained herein.', ['size' => 7], [
    'alignment' => WordJc::BOTH,
    'spaceAfter' => 250,
    'spaceBefore' => 250,
    'indentation' => [
        'left' => Converter::pixelToTwip(15),
        'right' => Converter::pixelToTwip(15),
    ],
]);

// Row 2: Signatures (two columns)
$acknowledgmentTable->addRow();

// Employee signature column
$empSignCell = $acknowledgmentTable->addCell($pageWidth * 0.50, [
    'borderSize' => 0,
    'borderColor' => 'FFFFFF',
    'valign' => 'center',
]);

$empTextRun = $empSignCell->addTextRun([
    'alignment' => WordJc::CENTER,
    'spaceAfter' => 350,
    'spaceBefore' => 350,
]);

$empTextRun->addText($positionData['employee_name'] ?? '', ['size' => 7, 'bold' => true, 'underline' => 'single']);
$empTextRun->addTextBreak();
$empTextRun->addText($positionData['employee_date'] ?? '', ['size' => 7]);
$empTextRun->addTextBreak();
$empTextRun->addTextBreak();
$empTextRun->addText('Employee\'s Name, Date and Signature', ['size' => 7, 'bold' => true]);

// Supervisor signature column
$supSignCell = $acknowledgmentTable->addCell($pageWidth * 0.50, [
    'borderSize' => 0,
    'borderColor' => 'FFFFFF',
    'valign' => 'center',
]);

$supTextRun = $supSignCell->addTextRun([
    'alignment' => WordJc::CENTER,
    'spaceAfter' => 350,
    'spaceBefore' => 350,
]);

$supTextRun->addText($positionData['supervisor_name'] ?? '', ['size' => 7, 'bold' => true, 'underline' => 'single']);
$supTextRun->addTextBreak();
$supTextRun->addText($positionData['supervisor_date'] ?? '', ['size' => 7]);
$supTextRun->addTextBreak();
$supTextRun->addTextBreak();
$supTextRun->addText('Supervisor\'s Name, Date and Signature', ['size' => 7, 'bold' => true]);

            $filename = 'position_description_' . date('Y-m-d_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate DOCX: ' . $e->getMessage());
        }
    }
}