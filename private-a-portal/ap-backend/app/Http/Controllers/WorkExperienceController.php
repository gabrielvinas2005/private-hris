<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use PDF;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;

class WorkExperienceController extends Controller
{

    use ApiResponse;

    /**
     * CS Form 212 style: abbreviated month + full year; use "Present" when no end date.
     *
     * @param  object  $exp  Work_Experience row
     */
    protected function formatWorkExperienceInclusionPeriod($exp): string
    {
        try {
            $startRaw = $exp->Work_start_date ?? null;
            if ($startRaw === null || $startRaw === '') {
                return trim((string) ($exp->Duration ?? ''));
            }
            $start = \Carbon\Carbon::parse($startRaw);
            $startStr = $start->format('M Y');
            $endRaw = $exp->Work_end_date ?? null;
            if ($endRaw === null || $endRaw === '') {
                return $startStr . ' – Present';
            }
            $end = \Carbon\Carbon::parse($endRaw);

            return $startStr . ' – ' . $end->format('M Y');
        } catch (\Throwable $e) {
            return trim((string) ($exp->Duration ?? ''));
        }
    }

    /**
     * @param  \Illuminate\Support\Collection  $rows
     * @return \Illuminate\Support\Collection
     */
    protected function withDurationInclusionDisplay($rows)
    {
        return $rows->map(function ($row) {
            $row->duration_inclusion_display = $this->formatWorkExperienceInclusionPeriod($row);

            return $row;
        });
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
     * Get all work experiences
     */
    public function index()
    {
        try {
            $data = DB::table('Work_Experience')
                ->leftJoin('applicant_headers', 'Work_Experience.Reference_id', '=', 'applicant_headers.applicant_no')
                ->select(
                    'Work_Experience.*',
                    'applicant_headers.applicant_no',
                    DB::raw("CONCAT(
                        COALESCE(applicant_headers.first_name, ''),
                        CASE WHEN applicant_headers.middle_name IS NOT NULL AND applicant_headers.middle_name != ''
                             THEN CONCAT(' ', applicant_headers.middle_name)
                             ELSE ''
                        END,
                        CASE WHEN applicant_headers.last_name IS NOT NULL AND applicant_headers.last_name != ''
                             THEN CONCAT(' ', applicant_headers.last_name)
                             ELSE ''
                        END
                    ) as applicant_name")
                )
                ->get();

            return $this->successResponse($data, 'Work experiences retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve work experiences: ' . $e->getMessage());
        }
    }

    /**
     * Get work experience data for specific employee
     */
    public function getData($employeeId)
    {
        try {
            $data = DB::table('Work_Experience')
                ->where('employee_id', $employeeId)
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse($data, 'Work experience data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve work experience data: ' . $e->getMessage());
        }
    }

    /**
     * Generate work experience sheet preview
     */
    public function generatePreview(Request $request)
    {
        try {
            $workExperienceId = $request->input('work_experience_id');

            if (!$workExperienceId) {
                return $this->errorResponse('Work experience ID is required', 400);
            }

            // Get the specific work experience to find the applicant
            $selectedWorkExperience = DB::table('Work_Experience')
                ->where('id', $workExperienceId)
                ->first();

            if (!$selectedWorkExperience) {
                return $this->errorResponse('Work experience not found', 404);
            }

            // Get all work experiences for the same applicant
            $workExperienceData = DB::table('Work_Experience')
                ->leftJoin('applicant_headers', 'Work_Experience.Reference_id', '=', 'applicant_headers.applicant_no')
                ->select(
                    'Work_Experience.*',
                    DB::raw("CONCAT(
                        COALESCE(applicant_headers.first_name, ''),
                        CASE WHEN applicant_headers.middle_name IS NOT NULL AND applicant_headers.middle_name != ''
                             THEN CONCAT(' ', applicant_headers.middle_name)
                             ELSE ''
                        END,
                        CASE WHEN applicant_headers.last_name IS NOT NULL AND applicant_headers.last_name != ''
                             THEN CONCAT(' ', applicant_headers.last_name)
                             ELSE ''
                        END
                    ) as applicant_name")
                )
                ->where('Work_Experience.Reference_id', $selectedWorkExperience->Reference_id)
                ->orderBy('Work_Experience.Work_start_date', 'desc')
                ->get();

            $workExperienceData = $this->withDurationInclusionDisplay($workExperienceData);

            // Prepare data for the view
            $data = [
                'work_experience_id' => $workExperienceId,
                'work_experiences' => $workExperienceData, // Pass as array for template compatibility
                'is_word' => false
            ];

            // Check if PDF library is available
            if (!class_exists('PDF')) {
                return $this->errorResponse('PDF library not available', 500);
            }

            // Generate PDF
            $pdf = PDF::loadView('work_experience.WorkExperienceSheet', $data);
            $pdf->setPaper('A4', 'portrait');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Work_Experience_Sheet_' . $workExperienceId . '.pdf"'
            ]);
        } catch (\Exception $e) {
            \Log::error('Work Experience Preview Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return $this->serverErrorResponse('Failed to generate work experience sheet preview: ' . $e->getMessage());
        }
    }

    /**
     * Download work experience sheet PDF
     */
    public function downloadPDF(Request $request)
    {
        try {
            $workExperienceId = $request->input('work_experience_id');

            if (!$workExperienceId) {
                return $this->errorResponse('Work experience ID is required', 400);
            }

            // Get the specific work experience to find the applicant
            $selectedWorkExperience = DB::table('Work_Experience')
                ->where('id', $workExperienceId)
                ->first();

            if (!$selectedWorkExperience) {
                return $this->errorResponse('Work experience not found', 404);
            }

            // Get all work experiences for the same applicant
            $workExperienceData = DB::table('Work_Experience')
                ->leftJoin('applicant_headers', 'Work_Experience.Reference_id', '=', 'applicant_headers.applicant_no')
                ->select(
                    'Work_Experience.*',
                    DB::raw("CONCAT(
                        COALESCE(applicant_headers.first_name, ''),
                        CASE WHEN applicant_headers.middle_name IS NOT NULL AND applicant_headers.middle_name != ''
                             THEN CONCAT(' ', applicant_headers.middle_name)
                             ELSE ''
                        END,
                        CASE WHEN applicant_headers.last_name IS NOT NULL AND applicant_headers.last_name != ''
                             THEN CONCAT(' ', applicant_headers.last_name)
                             ELSE ''
                        END
                    ) as applicant_name")
                )
                ->where('Work_Experience.Reference_id', $selectedWorkExperience->Reference_id)
                ->orderBy('Work_Experience.Work_start_date', 'desc')
                ->get();

            $workExperienceData = $this->withDurationInclusionDisplay($workExperienceData);

            // Prepare data for the view
            $data = [
                'work_experience_id' => $workExperienceId,
                'work_experiences' => $workExperienceData, // Pass all work experiences for the applicant
                'is_word' => false
            ];

            // Check if PDF library is available
            if (!class_exists('PDF')) {
                return $this->errorResponse('PDF library not available', 500);
            }

            // Generate PDF
            $pdf = PDF::loadView('work_experience.WorkExperienceSheet', $data);
            $pdf->setPaper('A4', 'portrait');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Work_Experience_Sheet_' . $workExperienceId . '.pdf"'
            ]);
        } catch (\Exception $e) {
            \Log::error('Work Experience Download Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return $this->serverErrorResponse('Failed to download work experience sheet: ' . $e->getMessage());
        }
    }

    /**
     * Download work experience sheet as Word (.doc via HTML)
     */
    public function downloadWord(Request $request)
    {
        try {
            $workExperienceId = $request->input('work_experience_id');

            if (!$workExperienceId) {
                return $this->errorResponse('Work experience ID is required', 400);
            }

            // Get the specific work experience to find the applicant
            $selectedWorkExperience = DB::table('Work_Experience')
                ->where('id', $workExperienceId)
                ->first();

            if (!$selectedWorkExperience) {
                return $this->errorResponse('Work experience not found', 404);
            }

            // Get all work experiences for the same applicant
            $workExperienceData = DB::table('Work_Experience')
                ->leftJoin('applicant_headers', 'Work_Experience.Reference_id', '=', 'applicant_headers.applicant_no')
                ->select(
                    'Work_Experience.*',
                    DB::raw("CONCAT(
                    COALESCE(applicant_headers.first_name, ''),
                    CASE WHEN applicant_headers.middle_name IS NOT NULL AND applicant_headers.middle_name != ''
                         THEN CONCAT(' ', applicant_headers.middle_name)
                         ELSE ''
                    END,
                    CASE WHEN applicant_headers.last_name IS NOT NULL AND applicant_headers.last_name != ''
                         THEN CONCAT(' ', applicant_headers.last_name)
                         ELSE ''
                    END
                ) as applicant_name")
                )
                ->where('Work_Experience.Reference_id', $selectedWorkExperience->Reference_id)
                ->orderBy('Work_Experience.Work_start_date', 'desc')
                ->get();

            $workExperienceData = $this->withDurationInclusionDisplay($workExperienceData);

            $data = [
                'work_experience_id' => $workExperienceId,
                'work_experiences' => $workExperienceData,
                'is_word' => true
            ];

            // Render the blade template used by the PDF/preview
            $html = View::make('work_experience.WorkExperienceSheet', $data)->render();

            $applicantName = $workExperienceData->isNotEmpty()
                ? str_replace(' ', '_', $workExperienceData->first()->applicant_name ?? 'Unknown')
                : 'Unknown';

            $filename = 'Work_Experience_Sheet_' . $applicantName . '_' . date('Y-m-d') . '.doc';

            return response($html, 200, [
                'Content-Type' => 'application/msword; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
                'Pragma' => 'public'
            ]);
        } catch (\Exception $e) {
            \Log::error('Work Experience Word Download Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'work_experience_id' => $workExperienceId ?? null
            ]);
            return $this->serverErrorResponse('Failed to download work experience sheet (Word): ' . $e->getMessage());
        }
    }

    /**
     * Download work experience sheet as DOCX
     */
    public function downloadDocx(Request $request)
    {
        try {
            $workExperienceId = $request->input('work_experience_id');
            if (!$workExperienceId) {
                return $this->errorResponse('Work experience ID is required', 400);
            }

            $selectedWorkExperience = DB::table('Work_Experience')
                ->where('id', $workExperienceId)
                ->first();
            if (!$selectedWorkExperience) {
                return $this->errorResponse('Work experience not found', 404);
            }

            $workExperienceData = DB::table('Work_Experience')
                ->leftJoin('applicant_headers', 'Work_Experience.Reference_id', '=', 'applicant_headers.applicant_no')
                ->select(
                    'Work_Experience.*',
                    DB::raw("CONCAT(
                        COALESCE(applicant_headers.first_name, ''),
                        CASE WHEN applicant_headers.middle_name IS NOT NULL AND applicant_headers.middle_name != ''
                             THEN CONCAT(' ', applicant_headers.middle_name)
                             ELSE ''
                        END,
                        CASE WHEN applicant_headers.last_name IS NOT NULL AND applicant_headers.last_name != ''
                             THEN CONCAT(' ', applicant_headers.last_name)
                             ELSE ''
                        END
                    ) as applicant_name")
                )
                ->where('Work_Experience.Reference_id', $selectedWorkExperience->Reference_id)
                ->orderBy('Work_Experience.Work_start_date', 'desc')
                ->get();

            $workExperienceData = $this->withDurationInclusionDisplay($workExperienceData);

            $phpWord = new PhpWord();
            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(1),
                'marginRight' => Converter::inchToTwip(1),
                'marginBottom' => Converter::inchToTwip(1),
                'marginLeft' => Converter::inchToTwip(1),
                'pageSizeW' => Converter::inchToTwip(8.27), // A4 portrait
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

            // Header bar
            $headerTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 50]);
            $headerTable->addRow();
            $cell = $headerTable->addCell(Converter::inchToTwip(6.27), ['bgColor' => '808080']);
            $cell->addText('WORK EXPERIENCE SHEET', ['bold' => true, 'color' => 'FFFFFF', 'size' => 11], ['alignment' => 'center']);

            // Instructions box
            $instrTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);
            $instrTable->addRow();
            $iCell = $instrTable->addCell(Converter::inchToTwip(6.27), ['bgColor' => 'D0D0D0']);
            $iCell->addText('Instructions:', ['bold' => true, 'size' => 10]);
            $iCell->addText('1. Include only the work experiences relevant to the position being applied to.', ['size' => 9]);
            $iCell->addText('2. The duration should include start and finish dates, if known, month in abbreviated form, if known, and year in full. For the current position, use the word Present, e.g., 1998-Present. Work experience should be listed from most recent first.', ['size' => 9]);

            // Each Work Experience as a bordered table (box)
            foreach ($workExperienceData as $idx => $exp) {
                $box = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);
                $box->addRow();
                $bcell = $box->addCell(Converter::inchToTwip(6.27));

                $addBullet = function ($label, $value) use ($bcell) {
                    $run = $bcell->addTextRun(['spaceAfter' => 0]);
                    $run->addText('•  ', ['bold' => false, 'size' => 10]);
                    $run->addText($label . ' ', ['bold' => true, 'size' => 10]);
                    $run->addText($value ?? '', ['size' => 10]);
                };

                $addBullet('Duration:', $exp->duration_inclusion_display ?? $this->formatWorkExperienceInclusionPeriod($exp));
                $addBullet('Position:', $exp->Position ?? '');
                $addBullet('Name of Office/Unit:', $exp->Office_name ?? '');
                $addBullet('Immediate Supervisor:', $exp->Immediate_supervisor ?? '');
                $addBullet('Name of Agency/Organization and Location:', $exp->Office_Address ?? '');

                // Subsections with bullet and indent
                $bcell->addText('•  List of Accomplishments and Contributions (if any)', ['bold' => true, 'size' => 10], [
                    'spaceAfter' => 0,
                    'indentation' => ['left' => Converter::cmToTwip(0.75)]
                ]);
                if (!empty($exp->List_Of_Accomplishment)) {
                    $bcell->addText($exp->List_Of_Accomplishment, ['size' => 10], [
                        'indentation' => ['left' => Converter::cmToTwip(1.2)]
                    ]);
                }

                $bcell->addText('•  Summary of Actual Duties', ['bold' => true, 'size' => 10], [
                    'spaceAfter' => 0,
                    'indentation' => ['left' => Converter::cmToTwip(0.75)]
                ]);
                if (!empty($exp->Summary_of_Duties)) {
                    $bcell->addText($exp->Summary_of_Duties, ['size' => 10], [
                        'indentation' => ['left' => Converter::cmToTwip(1.2)]
                    ]);
                }

                if ($idx === count($workExperienceData) - 1) {
                    $bcell->addTextBreak(1);
                    $bcell->addText('Attachment to CS Form No. 212', ['bold' => true, 'size' => 9]);
                }
            }

            // Signature area
            $section->addTextBreak(2);
            $sigTable = $section->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 0
            ]);
            $sigTable->addRow();
            // Left spacer (push signature block to the right)
            $sigTable->addCell(Converter::inchToTwip(4.25), [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF'
            ]);
            // Right signature block (no border)
            $sigCell = $sigTable->addCell(Converter::inchToTwip(2.02), [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF'
            ]);
            // Name
            $sigCell->addText(strtoupper($workExperienceData->first()->applicant_name ?? ''), ['size' => 10], [
                'alignment' => 'center',
                'spaceAfter' => 0,
                'spaceBefore' => 0
            ]);
            // Single horizontal line directly below the name using paragraph bottom border
            $sigCell->addText('', [], [
                'alignment' => 'center',
                'borderBottomSize' => 8,
                'borderBottomColor' => '000000',
                'spaceAfter' => 0,
                'spaceBefore' => 0
            ]);
            $sigCell->addText('(Signature over Printed Name of Employee/Applicant)', ['size' => 8], ['alignment' => 'center']);
            $sigCell->addText('Date: ' . date('F d, Y'), ['size' => 8], ['alignment' => 'center']);

            $fileName = 'Work_Experience_Sheet_' . date('Y-m-d') . '.docx';
            $tempPath = storage_path('app/' . $fileName);
            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Work Experience DOCX Download Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->serverErrorResponse('Failed to download work experience sheet (DOCX): ' . $e->getMessage());
        }
    }

    /**
     * Print work experience sheet (legacy method)
     */
    public function print($id)
    {
        try {
            $data = DB::table('Work_Experience')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->errorResponse('Work experience not found', 404);
            }

            return $this->successResponse($data, 'Work experience retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve work experience: ' . $e->getMessage());
        }
    }
}
