<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table as TableStyle;

class PassSlipController extends Controller
{
    use ApiResponse;

    /**
     * Sanctum token guard for pass slip PDF/DOCX export (query ?token= or Bearer).
     *
     * @return \Illuminate\Http\Response|null
     */
    private function guardPassSlipExport(Request $request)
    {
        $token = $request->query('token') ?? $request->bearerToken();
        if ($token) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if (!$accessToken || !$accessToken->tokenable) {
                return response('Invalid or expired token', 401)->header('Content-Type', 'text/plain');
            }
        } else {
            return response('Unauthorized', 401)->header('Content-Type', 'text/plain');
        }

        return null;
    }

    /**
     * Same row shape as pass_slip_pdf.blade.php / generatePDF.
     */
    private function loadPassSlipForExport($id)
    {
        return DB::table('pass_slips as a')
            ->join('employees as e', 'e.id', '=', 'a.employee_id')
            ->leftJoin('employees as b', 'b.id', '=', 'a.approved_by')
            ->select(
                'a.id',
                'a.employee_id',
                'a.date',
                'a.time_out',
                'a.time_in',
                'a.destination',
                'a.purpose',
                'a.status',
                'a.remarks',
                'a.division_chief',
                'a.approved_at',
                'a.created_at',
                // Original (decrypting) employee_full_name kept for reference:
                // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.middle_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as employee_full_name"),
                DB::raw("CONCAT(e.first_name,' ',e.middle_name,' ',e.last_name) as employee_full_name"),
                // Original (decrypting) approved_by_name kept for reference:
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) END as approved_by_name")
                DB::raw("CONCAT(b.first_name,' ',b.last_name) as approved_by_name")
            )
            ->where('a.id', $id)
            ->where('a.active', true)
            ->first();
    }

    /**
     * Return pass slips for monitoring (for approval, approved, disapproved).
     * Referenced by Pass Slip Monitoring frontend.
     */
    public function monitoring()
    {
        $app_key = env('APP_KEY', '');

        $baseSelect = [
            'a.id',
            'a.employee_id',
            'a.date',
            'a.time_out',
            'a.time_in',
            'a.destination',
            'a.purpose',
            'a.status',
            'a.remarks',
            'a.division_chief',
            'a.approved_at',
            'a.created_at',
            'b.photo',
            'b.employee_no',
            'b.position_id',
            'b.department_id',
            // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')) END as first_name"),
            // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) END as last_name"),
            'pos.name as position',
            'dept.name as department',
            DB::raw("b.first_name as first_name"),
            DB::raw("b.last_name as last_name"),
            // Original (decrypting) approved_by_name selection kept for reference:
            // DB::raw("CASE WHEN ISNULL(appr.is_encrypted,0) = 0 THEN CONCAT(appr.first_name,' ',appr.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](appr.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](appr.last_name,'$app_key')) END as approved_by_name"),
            DB::raw("CONCAT(appr.first_name,' ',appr.last_name) as approved_by_name"),
        ];

        // For Approval: pending and not yet expired.
        // A pass slip should be considered "expired" after its intended end time (time_in if provided),
        // otherwise fall back to time_out, otherwise treat the whole day as valid.
        $endDateTimeRaw = "DATEADD(second, DATEDIFF(second, 0, CAST(COALESCE(a.time_in, a.time_out, CAST('23:59:59' AS time)) AS datetime)), CAST(a.date AS datetime))";
        $notExpiredRaw = "$endDateTimeRaw >= GETDATE()";
        $ForapprovalPassSlips = DB::table('pass_slips as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
            ->leftJoin('employees as appr', 'appr.id', '=', 'a.approved_by')
            ->where('a.active', true)
            ->where('a.status', 'pending')
            ->whereRaw($notExpiredRaw)
            ->select($baseSelect)
            ->orderBy('a.date', 'asc')
            ->orderBy('a.created_at', 'asc')
            ->get();

        // Expired: pending but end datetime has already passed (see endDateTimeRaw above)
        $expiredRaw = "$endDateTimeRaw < GETDATE()";
        $ExpiredPassSlips = DB::table('pass_slips as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
            ->leftJoin('employees as appr', 'appr.id', '=', 'a.approved_by')
            ->where('a.active', true)
            ->where('a.status', 'pending')
            ->whereRaw($expiredRaw)
            ->select($baseSelect)
            ->orderBy('a.date', 'desc')
            ->orderBy('a.created_at', 'desc')
            ->get();

        $ApprovedPassSlips = DB::table('pass_slips as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
            ->leftJoin('employees as appr', 'appr.id', '=', 'a.approved_by')
            ->where('a.active', true)
            ->where('a.status', 'approved')
            ->select($baseSelect)
            ->orderBy('a.date', 'desc')
            ->orderBy('a.approved_at', 'desc')
            ->get();

        $DisapprovedPassSlips = DB::table('pass_slips as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
            ->leftJoin('employees as appr', 'appr.id', '=', 'a.approved_by')
            ->where('a.active', true)
            ->where('a.status', 'disapproved')
            ->select($baseSelect)
            ->orderBy('a.date', 'desc')
            ->orderBy('a.approved_at', 'desc')
            ->get();

        $CancelledPassSlips = DB::table('pass_slips as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
            ->leftJoin('employees as appr', 'appr.id', '=', 'a.approved_by')
            ->where('a.active', true)
            ->where('a.status', 'cancelled')
            ->select($baseSelect)
            ->orderBy('a.date', 'desc')
            ->orderBy('a.created_at', 'desc')
            ->get();

        // Active: approved and currently in use (now between time_out and time_in on the slip date)
        $activeRaw = "a.status = 'approved' AND (
            (a.time_out IS NOT NULL AND DATEADD(second, DATEDIFF(second, 0, CAST(a.time_out AS datetime)), CAST(a.date AS datetime)) <= GETDATE() AND (a.time_in IS NULL OR DATEADD(second, DATEDIFF(second, 0, CAST(a.time_in AS datetime)), CAST(a.date AS datetime)) >= GETDATE()))
            OR (a.time_out IS NULL AND CAST(a.date AS date) = CAST(GETDATE() AS date) AND (a.time_in IS NULL OR DATEADD(second, DATEDIFF(second, 0, CAST(a.time_in AS datetime)), CAST(a.date AS datetime)) >= GETDATE()))
        )";
        $ActivePassSlips = DB::table('pass_slips as a')
            ->join('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('positions as pos', 'b.position_id', '=', 'pos.id')
            ->leftJoin('departments as dept', 'b.department_id', '=', 'dept.id')
            ->leftJoin('employees as appr', 'appr.id', '=', 'a.approved_by')
            ->where('a.active', true)
            ->whereRaw($activeRaw)
            ->select($baseSelect)
            ->orderBy('a.date', 'desc')
            ->orderBy('a.created_at', 'desc')
            ->get();

        return response()->json([
            'ForapprovalPassSlips' => $ForapprovalPassSlips,
            'ApprovedPassSlips' => $ApprovedPassSlips,
            'DisapprovedPassSlips' => $DisapprovedPassSlips,
            'CancelledPassSlips' => $CancelledPassSlips,
            'ExpiredPassSlips' => $ExpiredPassSlips,
            'ActivePassSlips' => $ActivePassSlips,
        ]);
    }

    /**
     * Generate PDF report for a pass slip using pass_slip_pdf.blade.php.
     */
    public function generatePDF(Request $request, $id)
    {
        try {
            if ($auth = $this->guardPassSlipExport($request)) {
                return $auth;
            }

            $pass_slip = $this->loadPassSlipForExport($id);

            if (!$pass_slip) {
                return response('Pass slip not found', 404)->header('Content-Type', 'text/plain');
            }

            $pdf = PDF::loadView('pass_slip_pdf', compact('pass_slip'));
            $pdf->setPaper('A4', 'landscape');

            return $pdf->stream('pass_slip_' . $id . '.pdf');
        } catch (\Exception $e) {
            \Log::error('Pass slip PDF generation error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response('Failed to generate PDF: ' . $e->getMessage(), 500)->header('Content-Type', 'text/plain');
        }
    }

    /**
     * Generate editable Word document mirroring pass_slip_pdf.blade.php (A4 landscape).
     */
    public function generateDocx(Request $request, $id)
    {
        try {
            if ($auth = $this->guardPassSlipExport($request)) {
                return $auth;
            }

            $pass_slip = $this->loadPassSlipForExport($id);

            if (!$pass_slip) {
                return response('Pass slip not found', 404)->header('Content-Type', 'text/plain');
            }

            $dateStr = $pass_slip->date ? Carbon::parse($pass_slip->date)->format('F d, Y') : '';
            $timeOutStr = $pass_slip->time_out ? Carbon::parse($pass_slip->time_out)->format('h:i A') : '';
            $timeInStr = $pass_slip->time_in ? Carbon::parse($pass_slip->time_in)->format('h:i A') : '';

            $approverName = '';
            if ($pass_slip->status === 'approved') {
                $approverName = trim((string) ($pass_slip->approved_by_name ?? ''))
                    ?: trim((string) ($pass_slip->division_chief ?? ''));
            }

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(11);

            $section = $phpWord->addSection([
                'orientation' => 'landscape',
                'marginTop' => 720,
                'marginRight' => 720,
                'marginBottom' => 720,
                'marginLeft' => 720,
            ]);

            $section->addText(
                'AFM-PER FR#09/REV.00/15-16-14',
                ['bold' => true, 'size' => 10],
                ['alignment' => Jc::RIGHT, 'spaceAfter' => 120]
            );

            $outerTable = $section->addTable([
                'borderSize' => 12,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => TblWidth::PERCENT,
                'layout' => TableStyle::LAYOUT_FIXED,
            ]);
            $outerRow = $outerTable->addRow();
            $outerCell = $outerRow->addCell(null, [
                'cellMarginTop' => Converter::pixelToTwip(15),
                'cellMarginRight' => Converter::pixelToTwip(20),
                'cellMarginBottom' => Converter::pixelToTwip(20),
                'cellMarginLeft' => Converter::pixelToTwip(20),
            ]);

            $outerCell->addText(
                'PHILIPPINE TRADE TRAINING CENTER',
                ['bold' => true, 'size' => 12],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
            );
            $outerCell->addText(
                'PASS SLIP',
                ['bold' => true, 'size' => 12],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 240]
            );

            // 60% names / 40% date–time (matches pass_slip_pdf name-date-table); fixed layout removes the wide middle gap.
            $indentPurposeDest = (int) Converter::pixelToTwip(120);
            $indentNamesLine2 = (int) Converter::pixelToTwip(60);
            $dateColumnPadLeft = (int) Converter::pixelToTwip(40);

            $nameDateTable = $outerCell->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'width' => 100 * 50,
                'unit' => TblWidth::PERCENT,
                'layout' => TableStyle::LAYOUT_FIXED,
            ]);
            $ndRow = $nameDateTable->addRow();
            $namesCell = $ndRow->addCell(9240, ['valign' => 'top']);
            $dateCell = $ndRow->addCell(6160, [
                'valign' => 'top',
                'cellMarginLeft' => $dateColumnPadLeft,
            ]);

            $nr = $namesCell->addTextRun();
            $nr->addText('NAMES: ', ['bold' => true]);
            $nr->addText($pass_slip->employee_full_name ?? '', ['underline' => 'single']);
            $namesCell->addTextBreak(1);
            $nr2 = $namesCell->addTextRun(['indentation' => ['left' => $indentNamesLine2]]);
            $nr2->addText(str_repeat("\u{00A0}", 42), ['underline' => 'single']);

            $fieldLinePara = ['alignment' => Jc::END, 'spaceAfter' => 120];
            $dr = $dateCell->addTextRun($fieldLinePara);
            $dr->addText('DATE: ', ['bold' => true]);
            $dr->addText($dateStr, ['underline' => 'single']);
            $dr2 = $dateCell->addTextRun($fieldLinePara);
            $dr2->addText('TIME OUT: ', ['bold' => true]);
            $dr2->addText($timeOutStr, ['underline' => 'single']);
            $dr3 = $dateCell->addTextRun($fieldLinePara);
            $dr3->addText('TIME IN: ', ['bold' => true]);
            $dr3->addText($timeInStr, ['underline' => 'single']);

            // DESTINATION / PURPOSE: full-width rows, indented value (matches .full-underline / .purpose-line margin-left: 120px).
            // Only bottom border visible; other sides white so inner tables don't pick up default/grid borders.
            $fieldUnderlineCellStyle = [
                'borderTopSize' => 6,
                'borderTopColor' => 'FFFFFF',
                'borderLeftSize' => 6,
                'borderLeftColor' => 'FFFFFF',
                'borderRightSize' => 6,
                'borderRightColor' => 'FFFFFF',
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
            ];

            $fullInvisibleBorder = [
                'borderTopSize' => 6,
                'borderTopColor' => 'FFFFFF',
                'borderLeftSize' => 6,
                'borderLeftColor' => 'FFFFFF',
                'borderRightSize' => 6,
                'borderRightColor' => 'FFFFFF',
                'borderBottomSize' => 6,
                'borderBottomColor' => 'FFFFFF',
            ];

            $outerCell->addText('DESTINATION:', ['bold' => true], ['spaceBefore' => 200, 'spaceAfter' => 80,]);

            $destTable = $outerCell->addTable([
                'borderSize' => 0,
                'layout' => TableStyle::LAYOUT_FIXED,
                'width' => 100 * 50,
                'unit' => TblWidth::PERCENT,
            ]);
            $destRow = $destTable->addRow();
            $destRow->addCell($indentPurposeDest, $fullInvisibleBorder)->addText('');
            $destRow->addCell(13500, $fieldUnderlineCellStyle)->addText($pass_slip->destination ?? '', [], ['spaceAfter' => 40]);

            $outerCell->addText('PURPOSE:', ['bold' => true], ['spaceBefore' => 200, 'spaceAfter' => 80]);

            $purposeTable = $outerCell->addTable([
                'borderSize' => 0,
                'layout' => TableStyle::LAYOUT_FIXED,
                'width' => 100 * 50,
                'unit' => TblWidth::PERCENT,
            ]);
            foreach ([$pass_slip->purpose ?? '', '', ''] as $purposeLine) {
                $pr = $purposeTable->addRow();
                $pr->addCell($indentPurposeDest, $fullInvisibleBorder)->addText('');
                $text = $purposeLine !== '' ? $purposeLine : "\u{00A0}";
                $pr->addCell(13500, $fieldUnderlineCellStyle)->addText($text, [], ['spaceAfter' => 100]);
            }

            $outerCell->addText('Approved:', ['bold' => true], ['alignment' => Jc::CENTER, 'spaceBefore' => 480, 'spaceAfter' => 320]);

            // Signature block floated right (~450px in PDF).
            // Wrapper cell must use explicit white borders; nested tables often draw a visible grid in Word.
            $sigTable = $outerCell->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'layout' => TableStyle::LAYOUT_FIXED,
                'width' => 100 * 50,
                'unit' => TblWidth::PERCENT,
            ]);
            $sigRow = $sigTable->addRow();
            $sigRow->addCell(8200, $fullInvisibleBorder)->addText('');
            $sigCol = $sigRow->addCell(7200, $fullInvisibleBorder);

            // Approver + single bottom rule (no inner table around the label).
            $sigLineTable = $sigCol->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'layout' => TableStyle::LAYOUT_FIXED,
                'width' => 100 * 50,
                'unit' => TblWidth::PERCENT,
            ]);
            $sigLineRow = $sigLineTable->addRow();
            $sigLineRow->addCell(7200, $fieldUnderlineCellStyle)->addText(
                $approverName,
                [],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
            );

            $sigCol->addText(
                'Division Chief/Authorized Representative',
                ['size' => 10],
                ['alignment' => Jc::CENTER, 'spaceBefore' => 100]
            );

            $tempFile = tempnam(sys_get_temp_dir(), 'pass_slip_docx_');
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            $filename = 'pass_slip_' . $id . '.docx';

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Pass slip DOCX generation error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return response('Failed to generate DOCX: ' . $e->getMessage(), 500)->header('Content-Type', 'text/plain');
        }
    }
}
