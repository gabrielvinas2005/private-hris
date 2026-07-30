<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;
use PhpOffice\PhpWord\SimpleType\JcTable as WordAlignTable;

class AssumptionOfDutyWordController extends Controller
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
     * Generate Assumption of Duty certificate as DOCX (copied from AssumptionOfDutyController).
     */
    public function downloadDocx(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");
            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.', 400);
            }

            $employeeId = (int) $request->employee;
            $appointments = collect();

            if ($employeeId < 0) {
                $applicantId = abs($employeeId);
                $appointments = DB::table('applicant_headers as ah')
                    ->leftJoin('applicant_details as ad', function ($join) {
                        $join->on('ad.applicant_id', '=', 'ah.id')
                            ->whereRaw("ad.id = (
                                SELECT TOP 1 ad2.id
                                FROM applicant_details as ad2
                                WHERE ad2.applicant_id = ah.id
                                ORDER BY CASE WHEN ISNULL(ad2.application_status_id, 0) IN (6,5) THEN 0 ELSE 1 END, ad2.id DESC
                            )");
                    })
                    ->leftJoin('plantillas as p', function ($join) {
                        $join->on('ad.position_applied_id', '=', 'p.id')
                            ->whereRaw('ISNULL(ad.is_plantilla, 1) = 1');
                    })
                    ->leftJoin('non_plantillas as np', function ($join) {
                        $join->on('ad.position_applied_id', '=', 'np.id')
                            ->whereRaw('ISNULL(ad.is_plantilla, 1) = 0');
                    })
                    ->leftJoin('positions as pos_p', 'p.position_id', '=', 'pos_p.id')
                    ->leftJoin('positions as pos_np', 'np.position_id', '=', 'pos_np.id')
                    ->leftJoin('departments as dep_p', 'p.department_id', '=', 'dep_p.id')
                    ->leftJoin('departments as dep_np', 'np.department_id', '=', 'dep_np.id')
                    ->leftJoin('employees as ae', function ($join) {
                        $join->on('ae.employee_no', '=', 'ah.employee_no')
                            ->where('ae.is_employee', true);
                    })
                    ->leftJoin('positions as pos_ae', 'ae.position_id', '=', 'pos_ae.id')
                    ->leftJoin('departments as dep_ae', 'ae.department_id', '=', 'dep_ae.id')
                    ->select(
                        DB::raw('-ah.id as id'),
                        DB::raw("CONCAT(ah.first_name,' ',ah.last_name) as name"),
                        DB::raw("ISNULL(COALESCE(pos_np.name, pos_p.name, pos_ae.name), '') as position"),
                        DB::raw("ISNULL(COALESCE(dep_np.name, dep_p.name, dep_ae.name), '') as department"),
                        DB::raw('GETDATE() as date_of_effectivity'),
                        DB::raw("ah.last_name as name_sig"),
                    )
                    ->where('ah.id', $applicantId)
                    ->get();
            } else {
                // Get appointment info (reuse logic)
                $appointments = DB::table('employee_promotions as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                    ->leftJoin('name_prefixes as f', 'b.name_prefix_id', '=', 'f.id')
                    ->select(
                        'a.id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) END as name"),
                        'c.name as position',
                        'd.name as department',
                        'a.date_of_effectivity',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN CONCAT(f.name, ' ', b.last_name) ELSE CONCAT(f.name, ' ', RTRIM([dbo].[ufn_DecryptString](b.last_name, '$app_key'))) END as name_sig"),
                    )
                    ->where('a.id', $employeeId)
                    ->get();

                if ($appointments->isEmpty()) {
                    $appointments = DB::table('employees as b')
                        ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                        ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                        ->leftJoin('name_prefixes as f', 'b.name_prefix_id', '=', 'f.id')
                        ->select(
                            'b.id',
                            DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) END as name"),
                            'c.name as position',
                            'd.name as department',
                            DB::raw('GETDATE() as date_of_effectivity'),
                            DB::raw("CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN CONCAT(f.name, ' ', b.last_name) ELSE CONCAT(f.name, ' ', RTRIM([dbo].[ufn_DecryptString](b.last_name, '$app_key'))) END as name_sig"),
                        )
                        ->where('b.id', $employeeId)
                        ->get();
                }
            }

            if ($appointments->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $signatories = [
                'signatory' => $request->signatory,
                'position' => $request->position,
                'assested_date' => $request->assested_date,
                'assested_signatory' => $request->assested_signatory,
                'assested_position' => $request->assested_position
            ];

            $ap = $appointments->first();
            $name = $ap->name ?? '';
            $position = $ap->position ?? '';
            $department = $ap->department ?? '';
            $eff = isset($ap->date_of_effectivity) ? date('M d, Y', strtotime($ap->date_of_effectivity)) : date('M d, Y');

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(10.5);

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(0.85),
                'marginRight' => Converter::inchToTwip(1.35),
                'marginBottom' => Converter::inchToTwip(1),
                'marginLeft' => Converter::inchToTwip(1.15),
            ]);

            // Calculate available page width (8.5" - 1" left - 1" right = 6.5")
            $pageWidth = Converter::inchToTwip(6.5);

            // Create Assumption of Duty table
            $AodTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => 'pct',
            ]);

            // Add first row - Identification Section
            $AodTable->addRow();
            $headerCell = $AodTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRunHeader = $headerCell->addTextRun([
                'alignment' => WordJc::LEFT,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            $textRunHeader->addText('CS Form No. 4', ['size' => 11, 'italic' => true, 'bold' => true]);
            $textRunHeader->addTextBreak();
            $textRunHeader->addText('Revised 2025', ['size' => 11, 'italic' => true]);

            // Add second row - Government Header
            $AodTable->addRow();
            $governmentCell = $AodTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRunGovernment = $governmentCell->addTextRun([
                'alignment' => WordJc::CENTER,
                'lineHeight' => 1,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            $textRunGovernment->addTextBreak(3);
            $textRunGovernment->addText('Republic of the Philippines', ['size' => 14, 'bold' => true]);
            $textRunGovernment->addTextBreak();
            $textRunGovernment->addText(CompanyHelper::getName(), ['size' => 12]);
            $textRunGovernment->addTextBreak();
            $textRunGovernment->addText(CompanyHelper::getAddress(), ['size' => 12]);
            $textRunGovernment->addTextBreak();
            $textRunGovernment->addText(CompanyHelper::getAddress(), ['size' => 12]);
            $textRunGovernment->addTextBreak(3);
            $textRunGovernment->addText('CERTIFICATION OF ASSUMPTION TO DUTY', ['size' => 14, 'bold' => true]);
            $textRunGovernment->addTextBreak(2);

            // Add third row - Main Section
            $AodTable->addRow();
            $mainCell = $AodTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRunMain = $mainCell->addTextRun([
                'alignment' => WordJc::BOTH,
                'lineHeight' => 1.5,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);
            
            $textRunMain->addText('                    This is to certify that Ms./Mr. ', ['size' => 12]);
            $textRunMain->addText(strtoupper($name), ['size' => 11, 'bold' => true, 'underline' => 'single']);
            $textRunMain->addText(' has assumed the duties and responsibilities as ', ['size' => 12]);
            $textRunMain->addText(strtoupper($position), ['size' => 11, 'bold' => true, 'underline' => 'single']);
            $textRunMain->addText(' of ', ['size' => 12]);
            $textRunMain->addText(strtoupper($department), ['size' => 11, 'bold' => true, 'underline' => 'single']);
            $textRunMain->addText(' effective ', ['size' => 12]);
            $textRunMain->addText(date('F d, Y', strtotime($eff)), ['size' => 11, 'bold' => true, 'underline' => 'single']);
            $textRunMain->addText('.', ['size' => 12]);
            
            // Add fourth row - Certification Section
            $AodTable->addRow();
            $certificationCell = $AodTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $textRunCertification = $certificationCell->addTextRun([
                'alignment' => WordJc::BOTH,
                'lineHeight' => 1.5,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
                'indentation' => ['firstLine' => Converter::inchToTwip(0.5)],
            ]);

            $textRunCertification->addTextBreak(1);
            $textRunCertification->addText('                    This certification is issued in connection with the issuance of the appointment of Ms./Mr. ', ['size' => 12]);
            $textRunCertification->addText(strtoupper($name), ['size' => 11, 'bold' => true, 'underline' => 'single']);
            $textRunCertification->addText(' as ', ['size' => 12]);
            $textRunCertification->addText(strtoupper($position), ['size' => 11, 'bold' => true, 'underline' => 'single']);

            // Add fifth row - Accomplished Section
            $AodTable->addRow();
            $accomplishedCell = $AodTable->addCell($pageWidth, [
                'gridSpan' => 3,
                'bgColor' => 'FFFFFF',
                'borderSize' => 6,
                'borderColor' => 'FFFFFF',
                'valign' => 'center',
            ]);
            $textRunAccomplished = $accomplishedCell->addTextRun([
                'alignment' => WordJc::BOTH,
                'lineHeight' => 1.5,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
                'indentation' => ['firstLine' => Converter::inchToTwip(0.5)],
            ]);
            $textRunAccomplished->addTextBreak(1);
            $textRunAccomplished->addText('                    Done this ', ['size' => 12]);   
            $textRunAccomplished->addText(date('j'), ['size' => 12, 'bold' => true, 'underline' => 'single']);
            $textRunAccomplished->addText(date('S'), ['size' => 9, 'bold' => true, 'underline' => 'single', 'superScript' => true]);
            $textRunAccomplished->addText(' day of ', ['size' => 12]);
            $textRunAccomplished->addText(date('F'), ['size' => 12, 'bold' => true, 'underline' => 'single']);
            $textRunAccomplished->addText(' in ', ['size' => 12]);
            $textRunAccomplished->addText(date('Y'), ['size' => 12, 'bold' => true, 'underline' => 'single']);

            // Add sixth row - two-column grid section (placeholder for future content)
            $AodTable->addRow();

            // Left column (50% width) - empty text to maintain spacing
            $leftCell = $AodTable->addCell($pageWidth * 0.5, [
                'bgColor'    => 'FFFFFF',
                'borderSize' => 6,
                'borderColor'=> 'FFFFFF',
                'valign'     => 'center',
            ]);
            
            // Right column (50% width) - signatory name and position
            $rightCell = $AodTable->addCell($pageWidth * 0.5, [
                'bgColor'    => 'FFFFFF',
                'borderSize' => 6,
                'borderColor'=> 'FFFFFF',
                'valign'     => 'center',
            ]);
            $rightRun = $rightCell->addTextRun([
                'alignment'   => WordJc::CENTER,
                'lineHeight'  => 1,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]);
            $rightRun->addTextBreak(3);
            $rightRun->addText(strtoupper($signatories['signatory'] ?? 'Sample signatory name'), [
                'size' => 11,
                'bold' => true,
                'underline' => 'single',
            ]);
            $rightCell->addText($signatories['position'] ?? 'Sample Signatory Position', [
                'size' => 11,
            ], [
                'alignment' => WordJc::CENTER,
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]);

            // Row 1: "Attested By" label (left column) with empty right column
            $AodTable->addRow();
            $attLabelLeftCell = $AodTable->addCell($pageWidth * 0.5, [
                'bgColor'    => 'FFFFFF',
                'borderSize' => 6,
                'borderColor'=> 'FFFFFF',
                'valign'     => 'center',
            ]);
            $attLabelRun = $attLabelLeftCell->addTextRun([
                'alignment'   => WordJc::LEFT,
                'lineHeight'  => 1,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]);
            $attLabelRun->addTextBreak(3);
            $attLabelRun->addText('Attested By:', ['size' => 12]);

            // empty right column
            $AodTable->addCell($pageWidth * 0.5, [
                'bgColor'    => 'FFFFFF',
                'borderSize' => 6,
                'borderColor'=> 'FFFFFF',
                'valign'     => 'center',
            ]);

            // Row 2: Attested signatory and position (centered in left column)
            $AodTable->addRow();
            $attSigLeftCell = $AodTable->addCell($pageWidth * 0.5, [
                'bgColor'    => 'FFFFFF',
                'borderSize' => 6,
                'borderColor'=> 'FFFFFF',
                'valign'     => 'center',
            ]);
            $attSigRun = $attSigLeftCell->addTextRun([
                'alignment'   => WordJc::CENTER,
                'lineHeight'  => 1.2,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]);
            $attSigRun->addTextBreak(1.5);
            $attSigRun->addText(strtoupper($signatories['assested_signatory'] ?? 'Sample attested signatory'), [
                'size' => 11,
                'bold' => true,
                'underline' => 'single',
            ]);
            $attSigRun->addTextBreak();
            $attSigRun->addText(strtoupper($signatories['assested_position'] ?? 'Sample attested position'), [
                'size' => 11,
            ]);
            $attSigRun->addTextBreak(1.5);

            // empty right column
            $AodTable->addCell($pageWidth * 0.5, [
                'bgColor'    => 'FFFFFF',
                'borderSize' => 6,
                'borderColor'=> 'FFFFFF',
                'valign'     => 'center',
            ]);

            // Row 3: Date line (left column) with empty right column
            $AodTable->addRow();
            $attDateLeftCell = $AodTable->addCell($pageWidth * 0.5, [
                'bgColor'    => 'FFFFFF',
                'borderSize' => 6,
                'borderColor'=> 'FFFFFF',
                'valign'     => 'center',
            ]);
            $attDateRun = $attDateLeftCell->addTextRun([
                'alignment'   => WordJc::LEFT,
                'lineHeight'  => 1.2,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]);
            $attDateRun->addText('Date: ', ['size' => 12]);

            // Safely parse assessed date that may come as various string formats from frontend
            $assestedDateRaw = $signatories['assested_date'] ?? null;
            $assestedDateFormatted = '';

            if (!empty($assestedDateRaw)) {
                $timestamp = false;

                // Remove timezone information if present
                $dateOnly = preg_replace('/\s*GMT.*$/i', '', (string)$assestedDateRaw);
                
                // Prefer a clear YYYY-MM-DD part if present (e.g. from ISO strings)
                if (preg_match('/\d{4}-\d{2}-\d{2}/', $dateOnly, $m)) {
                    $timestamp = strtotime($m[0]);
                } else {
                    $timestamp = strtotime($dateOnly);
                }

                if ($timestamp && $timestamp > 0) {
                    $assestedDateFormatted = date('F j, Y', $timestamp);
                } else {
                    // Fallback: if parsing failed, use the raw value or a placeholder
                    $assestedDateFormatted = $dateOnly;
                }
            } else {
                // If no date provided, show placeholder
                $assestedDateFormatted = '___________________';
            }

            $attDateRun->addText($assestedDateFormatted, ['size' => 12, 'underline' => 'single', 'bold' => true]);

            // empty right column
            $AodTable->addCell($pageWidth * 0.5, [
                'bgColor'    => 'FFFFFF',
                'borderSize' => 6,
                'borderColor'=> 'FFFFFF',
                'valign'     => 'center',
            ]);

            //add row for 201 file, admin, COA, CSC
            $AodTable->addRow();

            //left column for 201 file, admin, COA, CSC
            $twentyOneFileLeftCell = $AodTable->addCell($pageWidth * 0.5, [
                'bgColor'    => 'FFFFFF',
                'borderSize' => 6,
                'borderColor'=> 'FFFFFF',
                'valign'     => 'center',
            ]);
            $twentyOneFileLeftRun = $twentyOneFileLeftCell->addTextRun([
                'alignment'   => WordJc::LEFT,
                'lineHeight'  => 1,
                'spaceAfter'  => 0,
                'spaceBefore' => 0,
            ]);
            $twentyOneFileLeftRun->addText('201 file', ['size' => 10]);
            $twentyOneFileLeftRun->addTextBreak(1.5);
            $twentyOneFileLeftRun->addText('Admin', ['size' => 10]);
            $twentyOneFileLeftRun->addTextBreak(1.5);
            $twentyOneFileLeftRun->addText('COA', ['size' => 10]);
            $twentyOneFileLeftRun->addTextBreak(1.5);
            $twentyOneFileLeftRun->addText('CSC', ['size' => 10]);
            $twentyOneFileLeftRun->addTextBreak(1.5);
            
           //right column for Note section

            $noteRightCell = $AodTable->addCell($pageWidth * 0.5, [
                'bgColor'    => 'FFFFFF',
                'borderSize' => 6,
                'borderColor'=> 'FFFFFF',
                'valign'     => 'center',
            ]);

            $noteRightCell->addTextBreak(1.5);

            // Create nested table for the box
            $nestedTable = $noteRightCell->addTable([
                'borderSize' => 6,
                'borderColor'=> 'FFFFFF',
                'cellMargin' => 100, // Inner padding
                'alignment'  => WordAlignTable::CENTER,
                'width'      => 80 * 50, // Adjust width as needed
            ]);

            $nestedTable->addRow();
            $nestedCell = $nestedTable->addCell(null, [
                'borderSize' => 6,
                'borderColor'=> '000000',
                'valign'     => 'center',
            ]);

            $noteRightRun = $nestedCell->addTextRun([
                'alignment'  => WordJc::CENTER,
                'lineHeight' => 1.2,
                'spaceAfter' => 0,
                'spaceBefore'=> 0,
            ]);
            $noteRightRun->addText('For submission to CSC FO', [
                'name'  => 'Times New Roman',
                'size'  => 14,
                'italic'=> true,
            ]);
            $noteRightRun->addTextBreak();
            $noteRightRun->addText('within 30 days ', [
                'name'  => 'Times New Roman',
                'size'  => 14,
                'italic'=> true,
            ]);
            $noteRightRun->addText('from the', [
                'name'  => 'Times New Roman',
                'size'  => 14,
                'italic'=> true,
            ]);
            $noteRightRun->addTextBreak();
            $noteRightRun->addText('date of assumption', [
                'name'  => 'Times New Roman',
                'size'  => 14,
                'italic'=> true,
            ]);
            $noteRightRun->addText(' of the', [
                'name'  => 'Times New Roman',
                'size'  => 14,
                'italic'=> true,
            ]);
            $noteRightRun->addTextBreak();
            $noteRightRun->addText('appointee', [
                'name'  => 'Times New Roman',
                'size'  => 14,
                'italic'=> true,
            ]);


            $fileName = 'assumption_of_duty_' . ($name ?: 'employee') . '_' . date('Y-m-d') . '.docx';
            $tempPath = storage_path('app/' . $fileName);
            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);
            return response()->download($tempPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download assumption of duty DOCX: ' . $e->getMessage());
        }
    }
}
