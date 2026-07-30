<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;

class TransferLeaveCreditsController extends Controller
{
    use ApiResponse;

    /**
     * Get employees for dropdown from employee_offboardings table.
     */
    public function employees()
    {
        try {
            $app_key = env('APP_KEY', '');
            
            $employees = DB::table('employee_offboardings as eo')
                ->join('employees as a', 'a.id', '=', 'eo.employee_id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name, ' ', a.last_name)
                            ELSE
                               RTRIM([dbo].[ufn_DecryptString](a.first_name,'" . $app_key . "')) + ' ' + RTRIM([dbo].[ufn_DecryptString](a.last_name,'" . $app_key . "'))
                            END as name"),
                    'b.name as position_name',
                    'c.name as salary_grade_name',
                    'd.name as salary_step_name'
                )
                ->leftJoin('positions as b', 'b.id', '=', 'a.position_id')
                ->leftJoin('salary_grades as c', 'c.id', '=', 'a.salary_grade_id')
                ->leftJoin('salary_steps as d', 'd.id', '=', 'a.salary_step_id')
                ->where('eo.reactivated_status_id', 0)
                ->distinct()
                ->orderBy('a.id')
                ->get();

            return $this->successResponse($employees, 'Employees retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to load employees for transfer leave credits', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve employees.');
        }
    }

    /**
     * Get leave credits for a specific employee (Vacation Leave and Sick Leave).
     */
    public function getLeaveCredits($employeeId)
    {
        try {
            // Get Vacation Leave and Sick Leave type IDs
            $vacationLeave = DB::table('leave_types')
                ->where(function($query) {
                    $query->where('name', 'LIKE', '%Vacation%')
                          ->orWhere('name', 'LIKE', '%Vacation Leave%');
                })
                ->first();

            $sickLeave = DB::table('leave_types')
                ->where(function($query) {
                    $query->where('name', 'LIKE', '%Sick%')
                          ->orWhere('name', 'LIKE', '%Sick Leave%');
                })
                ->first();

            $vacationLeaveId = $vacationLeave->id ?? null;
            $sickLeaveId = $sickLeave->id ?? null;

            // Get leave credits for the employee
            $leaveCreditsQuery = DB::table('leave_credits as lc')
                ->join('leave_types as lt', 'lt.id', '=', 'lc.leave_type_id')
                ->select(
                    'lc.leave_type_id',
                    'lt.name as leave_type_name',
                    'lc.credits'
                )
                ->where('lc.employee_id', $employeeId);

            // Filter by Vacation Leave and/or Sick Leave if they exist
            $ids = array_filter([$vacationLeaveId, $sickLeaveId], function($id) {
                return $id !== null;
            });
            
            if (!empty($ids)) {
                $leaveCreditsQuery->whereIn('lc.leave_type_id', $ids);
            } else {
                // If no leave types found, return empty result
                $leaveCredits = collect([]);
            }

            if (!isset($leaveCredits)) {
                $leaveCredits = $leaveCreditsQuery->get();
            }

            // Format response with Vacation Leave and Sick Leave
            $result = [
                'vacation_leave' => [
                    'leave_type_id' => $vacationLeaveId,
                    'leave_type_name' => $vacationLeave->name ?? 'Vacation Leave',
                    'credits' => 0
                ],
                'sick_leave' => [
                    'leave_type_id' => $sickLeaveId,
                    'leave_type_name' => $sickLeave->name ?? 'Sick Leave',
                    'credits' => 0
                ]
            ];

            // Map the credits to the result
            foreach ($leaveCredits as $credit) {
                if ($vacationLeaveId && $credit->leave_type_id == $vacationLeaveId) {
                    $result['vacation_leave']['credits'] = $credit->credits;
                } elseif ($sickLeaveId && $credit->leave_type_id == $sickLeaveId) {
                    $result['sick_leave']['credits'] = $credit->credits;
                }
            }

            return $this->successResponse($result, 'Leave credits retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to load leave credits for employee', [
                'error' => $e->getMessage(),
                'employee_id' => $employeeId
            ]);
            return $this->serverErrorResponse('Failed to retrieve leave credits.');
        }
    }

    /**
     * Store transfer leave credits record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'vacation_leave_credits' => 'nullable|numeric|min:0',
            'sick_leave_credits' => 'nullable|numeric|min:0',
            'transfer_to_employee_id' => 'nullable|integer|exists:employees,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $id = DB::table('transfer_leave_credits')->insertGetId([
                'employee_id' => $validated['employee_id'],
                'vacation_leave_credits' => $validated['vacation_leave_credits'] ?? 0,
                'sick_leave_credits' => $validated['sick_leave_credits'] ?? 0,
                'transfer_to_employee_id' => $validated['transfer_to_employee_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $record = DB::table('transfer_leave_credits')->where('id', $id)->first();

            return $this->successResponse($record, 'Transfer leave credits created successfully.', 201);
        } catch (\Exception $e) {
            Log::error('Failed to create transfer leave credits', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return $this->serverErrorResponse('Failed to create transfer leave credits.');
        }
    }

    /**
     * List all transfer leave credits records.
     */
    public function index()
    {
        try {
            $records = DB::table('transfer_leave_credits as tlc')
                ->join('employees as e', 'e.id', '=', 'tlc.employee_id')
                ->leftJoin('employees as e2', 'e2.id', '=', 'tlc.transfer_to_employee_id')
                ->select(
                    'tlc.id',
                    'tlc.employee_id',
                    'e.gender_id',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                               CONCAT(e.first_name, ' ', e.last_name)
                            ELSE
                               RTRIM([dbo].[ufn_DecryptString](e.first_name,'" . env('APP_KEY', '') . "')) + ' ' + RTRIM([dbo].[ufn_DecryptString](e.last_name,'" . env('APP_KEY', '') . "'))
                            END as employee_name"),
                    'tlc.vacation_leave_credits',
                    'tlc.sick_leave_credits',
                    'tlc.transfer_to_employee_id',
                    DB::raw("CASE WHEN tlc.transfer_to_employee_id IS NOT NULL AND ISNULL(e2.is_encrypted,0) = 0 THEN
                               CONCAT(e2.first_name, ' ', e2.last_name)
                            WHEN tlc.transfer_to_employee_id IS NOT NULL THEN
                               RTRIM([dbo].[ufn_DecryptString](e2.first_name,'" . env('APP_KEY', '') . "')) + ' ' + RTRIM([dbo].[ufn_DecryptString](e2.last_name,'" . env('APP_KEY', '') . "'))
                            ELSE NULL
                            END as transfer_to_employee_name"),
                    'tlc.remarks',
                    'tlc.created_at'
                )
                ->orderByDesc('tlc.created_at')
                ->get();

            return $this->successResponse($records, 'Transfer leave credits retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve transfer leave credits', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve transfer leave credits.');
        }
    }

    /**
     * Generate printable PDF for a transfer leave credits record.
     */
    public function print(Request $request, $id)
    {
        try {
            $appKey = env('APP_KEY', '');

            $record = DB::table('transfer_leave_credits as tlc')
                ->join('employees as e', 'e.id', '=', 'tlc.employee_id')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('employee_offboardings as eo', 'eo.employee_id', '=', 'e.id')
                ->select(
                    'tlc.id',
                    'tlc.vacation_leave_credits',
                    'tlc.sick_leave_credits',
                    'tlc.created_at',
                    'eo.date_effectivity as separation_date',
                    'e.gender_id',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                               CONCAT(e.first_name, ' ', e.last_name)
                            ELSE
                               RTRIM([dbo].[ufn_DecryptString](e.first_name,'" . $appKey . "')) + ' ' + RTRIM([dbo].[ufn_DecryptString](e.last_name,'" . $appKey . "'))
                            END as employee_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                               ISNULL(p.name, '')
                            ELSE
                               ISNULL(p.name, '')
                            END as position_name")
                )
                ->where('tlc.id', $id)
                ->first();

            if (!$record) {
                return $this->errorResponse('Transfer leave credits record not found.', 404);
            }

            $vac = (float) ($record->vacation_leave_credits ?? 0);
            $sick = (float) ($record->sick_leave_credits ?? 0);
            $total = $vac + $sick;

            // Load header/footer images
            $headerImg = $this->loadImageBase64(resource_path('img/report_header.jpg'))
                ?? $this->loadImageBase64(resource_path('images/report_header.jpg'));
            $footerImg = $this->loadImageBase64(resource_path('img/report_footer.jpg'))
                ?? $this->loadImageBase64(resource_path('images/report_footer.jpg'));

            $reportContent = $this->resolveReportContent($request, $record);

            $data = array_merge([
                'record' => $record,
                'vac_total' => $vac,
                'sick_total' => $sick,
                'grand_total' => $total,
                'headerImg' => $headerImg,
                'footerImg' => $footerImg,
            ], $reportContent);

            $pdf = app('dompdf.wrapper');
            $pdf->loadView('certificates.transfer_leave_credits', $data)
                ->setPaper('A4', 'portrait');

            $filename = 'transfer_leave_credits_' . $record->id . '.pdf';
            $pdfContent = $pdf->output();

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            Log::error('Failed to print transfer leave credits', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);
            return $this->serverErrorResponse('Failed to generate report.');
        }
    }

    /**
     * Download transfer leave credits as DOCX using PhpWord.
     */
    public function word(Request $request, $id)
    {
        try {
            $appKey = env('APP_KEY', '');

            $record = DB::table('transfer_leave_credits as tlc')
                ->join('employees as e', 'e.id', '=', 'tlc.employee_id')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('employee_offboardings as eo', 'eo.employee_id', '=', 'e.id')
                ->select(
                    'tlc.id',
                    'tlc.vacation_leave_credits',
                    'tlc.sick_leave_credits',
                    'tlc.created_at',
                    'eo.date_effectivity as separation_date',
                    'e.gender_id',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                               CONCAT(e.first_name, ' ', e.last_name)
                            ELSE
                               RTRIM([dbo].[ufn_DecryptString](e.first_name,'" . $appKey . "')) + ' ' + RTRIM([dbo].[ufn_DecryptString](e.last_name,'" . $appKey . "'))
                            END as employee_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                               ISNULL(p.name, '')
                            ELSE
                               ISNULL(p.name, '')
                            END as position_name")
                )
                ->where('tlc.id', $id)
                ->first();

            if (!$record) {
                return $this->errorResponse('Transfer leave credits record not found.', 404);
            }

            $vac = (float) ($record->vacation_leave_credits ?? 0);
            $sick = (float) ($record->sick_leave_credits ?? 0);
            $total = $vac + $sick;
            $reportContent = $this->resolveReportContent($request, $record);
            $purposeText = $reportContent['purpose_text'];
            $reasons = $reportContent['reasons'];
            $leaveRows = $reportContent['leave_rows'];
            $printedDate = $reportContent['printed_date'];

            $headerPath = resource_path('img/report_header.jpg');
            $footerPath = resource_path('img/report_footer.jpg');

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(1),
                'marginRight' => Converter::inchToTwip(0.75),
                'marginBottom' => Converter::inchToTwip(0.9),
                'marginLeft' => Converter::inchToTwip(1),
                'pageSizeW' => Converter::inchToTwip(8.27),
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

            // Header and footer images, if available
            if (file_exists($headerPath)) {
                $header = $section->addHeader();
                $header->addImage($headerPath, [
                    'width' => Converter::cmToPoint(18),
                    'alignment' => 'center'
                ]);
            }
            if (file_exists($footerPath)) {
                $footer = $section->addFooter();
                $footer->addImage($footerPath, [
                    'width' => Converter::cmToPoint(18),
                    'alignment' => 'center'
                ]);
            }

            // Paragraph style with minimized spacing
            $phpWord->addParagraphStyle('certBody', [
                'indentation' => [
                    'firstLine' => Converter::inchToTwip(0.5),
                    'left' => 0,
                ],
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 120,
                'lineHeight' => 1.15,
            ]);

            // Title
            $section->addText('CERTIFICATION OF TRANSFER OF LEAVE CREDITS', ['bold' => true], ['alignment' => 'center', 'spaceAfter' => 120]);
            $section->addTextBreak(0.5);

            // First paragraph
            $p1 = $section->addTextRun('certBody');
            $p1->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
            $p1->addText('This is to certify that ');
            $p1->addText(strtoupper($record->employee_name), ['bold' => true]);
            $p1->addText(', ' . ($record->position_name ?? '') . ' who was separated from the ');
            $p1->addText('strtoupper(CompanyHelper::getName())', ['bold' => true]);
            $p1->addText(' effective ');
            $separationDate = $record->separation_date ? Carbon::parse($record->separation_date)->format('F d, Y') : 'N/A';
            $p1->addText($separationDate);
            $p1->addText(', has the following unused leave credits as of the date of separation:');

            $section->addTextBreak(0.5);

            // Leave credits
            $leaveCreditsPara = $section->addTextRun(['spaceAfter' => 120]);
            $leaveCreditsPara->addText('Vacation Leave : ', ['bold' => true]);
            $leaveCreditsPara->addText(number_format($vac, 3) . ' days');
            $section->addTextBreak(0.3);
            $leaveCreditsPara2 = $section->addTextRun(['spaceAfter' => 120]);
            $leaveCreditsPara2->addText('Sick Leave : ', ['bold' => true]);
            $leaveCreditsPara2->addText(number_format($sick, 3) . ' days');
            $section->addTextBreak(0.3);
            $leaveCreditsPara3 = $section->addTextRun(['spaceAfter' => 120]);
            $leaveCreditsPara3->addText('Total : ', ['bold' => true]);
            $leaveCreditsPara3->addText(number_format($total, 3) . ' days');

            $section->addTextBreak(0.5);

            // Second paragraph
            $p2 = $section->addTextRun('certBody');
            $p2->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
            $p2->addText('This is to certify further that as of ');
            $p2->addText($separationDate);
            $p2->addText(', ');
            $firstName = explode(' ', $record->employee_name)[0];
            $p2->addText($firstName);
            $p2->addText(':');

            $section->addTextBreak(0.5);

            // Numbered list items
            $listItem1 = $section->addTextRun(['indentation' => ['left' => Converter::inchToTwip(0.5), 'firstLine' => Converter::inchToTwip(-0.25)], 'spaceAfter' => 80]);
            $listItem1->addText('1. ', ['bold' => true]);
            $listItem1->addText($reasons['reason_1']);

            $section->addTextBreak(0.3);

            $listItem2 = $section->addTextRun(['indentation' => ['left' => Converter::inchToTwip(0.5), 'firstLine' => Converter::inchToTwip(-0.25)], 'spaceAfter' => 80]);
            $listItem2->addText('2. ', ['bold' => true]);
            $listItem2->addText($reasons['reason_2']);

            $section->addTextBreak(0.5);

            // Table for leave types
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 40,
                'cellMarginTop' => 40,
                'cellMarginRight' => 40,
                'cellMarginBottom' => 40,
                'cellMarginLeft' => 40,
            ]);

            // Table header
            $table->addRow();
            $headerCell1 = $table->addCell(Converter::inchToTwip(2.5));
            $headerCell1->addText('Type of Leave', ['bold' => true]);
            $headerCell2 = $table->addCell(Converter::inchToTwip(1.5));
            $headerCell2->addText('No. of days availed', ['bold' => true]);
            $headerCell3 = $table->addCell(Converter::inchToTwip(2.5));
            $headerCell3->addText('Date/s leave was availed', ['bold' => true]);

            foreach ($leaveRows as $leaveRow) {
                $table->addRow();
                $cell1 = $table->addCell();
                $cell1->addText($leaveRow['type'] ?? '');
                $cell2 = $table->addCell();
                $cell2->addText($leaveRow['days'] ?? 'N/A');
                $cell3 = $table->addCell();
                $cell3->addText($leaveRow['dates'] ?? 'N/A');
            }

            $section->addTextBreak(0.5);

            // Continue numbered list
            $listItem3 = $section->addTextRun(['indentation' => ['left' => Converter::inchToTwip(0.5), 'firstLine' => Converter::inchToTwip(-0.25)], 'spaceAfter' => 80]);
            $listItem3->addText('3. ', ['bold' => true]);
            $listItem3->addText($reasons['reason_3']);

            $section->addTextBreak(0.3);

            $listItem4 = $section->addTextRun(['indentation' => ['left' => Converter::inchToTwip(0.5), 'firstLine' => Converter::inchToTwip(-0.25)], 'spaceAfter' => 120]);
            $listItem4->addText('4. ', ['bold' => true]);
            $listItem4->addText($reasons['reason_4']);

            $section->addTextBreak(0.5);

            $p3 = $section->addTextRun('certBody');
            $p3->addText(str_repeat(chr(160), 10));
            $p3->addText($purposeText);

            $section->addTextBreak(2);

            $section->addText('Date: ' . $printedDate->format('j F Y'), ['size' => 12], ['alignment' => 'left', 'spaceAfter' => 120]);

            // Signature section
            $section->addText('MARIA ANTONIETTE S. ZOILO', ['bold' => true], ['alignment' => 'right', 'spaceAfter' => 0]);
            $section->addText('Administrative Officer V', ['size' => 11], ['alignment' => 'right']);

            $filename = 'transfer_leave_credits_' . $record->id . '_' . date('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to generate transfer leave credits DOCX', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);
            return $this->serverErrorResponse('Failed to generate transfer leave credits DOCX: ' . $e->getMessage());
        }
    }

    /**
     * Resolve customizable report text from the request with sensible defaults.
     */
    private function resolveReportContent(Request $request, $record): array
    {
        $pronoun = 'their';
        if (($record->gender_id ?? null) == 1) {
            $pronoun = 'his';
        } elseif (($record->gender_id ?? null) == 2) {
            $pronoun = 'her';
        }

        $defaultPurpose = sprintf(
            'This certification is being issued upon the request of %s in connection with %s transfer to the CITEM (Center for International Trade Expositions and Missions).',
            $record->employee_name ?? '',
            $pronoun
        );

        $purposeText = trim((string) $request->input('purpose_text', ''));
        if ($purposeText === '') {
            $purposeText = $defaultPurpose;
        }

        $defaultReasons = [
            'reason_1' => 'Has not requested for the commutation of the aforesaid leave credits;',
            'reason_2' => 'Has availed of the following leaves during the current year:',
            'reason_3' => 'Incurred zero (0) vacation leave w/o pay (VLWOP) days from January this year.',
            'reason_4' => 'Incurred zero (0) days absent without official leave (AWOL) from January this year.',
        ];

        $reasons = [];
        foreach ($defaultReasons as $key => $default) {
            $value = trim((string) $request->input($key, ''));
            $reasons[$key] = $value !== '' ? $value : $default;
        }

        $defaultLeaveRows = [
            ['type' => 'Forced/Mandatory Leave', 'days' => 'N/A', 'dates' => 'N/A'],
            ['type' => 'Special Privilege Leave', 'days' => 'N/A', 'dates' => 'N/A'],
        ];

        $leaveRows = $defaultLeaveRows;
        $rawLeaveRows = $request->input('leave_rows');
        if ($rawLeaveRows !== null && $rawLeaveRows !== '') {
            $decoded = is_array($rawLeaveRows) ? $rawLeaveRows : json_decode($rawLeaveRows, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $leaveRows = array_values(array_map(function ($row) {
                    return [
                        'type' => trim((string) ($row['type'] ?? '')),
                        'days' => trim((string) ($row['days'] ?? 'N/A')) ?: 'N/A',
                        'dates' => trim((string) ($row['dates'] ?? 'N/A')) ?: 'N/A',
                    ];
                }, $decoded));
            }
        }

        $printedDate = Carbon::parse($request->input('printed_date', $record->created_at ?? now()));

        return [
            'purpose_text' => $purposeText,
            'reasons' => $reasons,
            'leave_rows' => $leaveRows,
            'printed_date' => $printedDate,
        ];
    }

    /**
     * Load an image as base64 if it exists.
     */
    private function loadImageBase64($path)
    {
        $candidates = [
            $path,
            public_path('images/' . basename($path)),
            storage_path('app/public/' . basename($path)),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                $type = pathinfo($candidate, PATHINFO_EXTENSION);
                $data = file_get_contents($candidate);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        return null;
    }
}

