<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use PDF;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;

class CertOfCompletionController extends Controller
{
    use ApiResponse;

    /**
     * Honorific + matching pronouns for certificate body (PDF / Word).
     */
    protected function studentTitleLanguage(?string $studentTitle): array
    {
        $isMs = strtolower((string) $studentTitle) === 'ms';

        if ($isMs) {
            return [
                'honorific' => 'Ms.',
                'rendered_intro' => 'She rendered her',
                'thanks_body' => 'We take this opportunity to thank her and wish her all the best in her future endeavors.',
                'course_possessive' => 'her',
            ];
        }

        return [
            'honorific' => 'Mr.',
            'rendered_intro' => 'He rendered his',
            'thanks_body' => 'We take this opportunity to thank him and wish him all the best in his future endeavors.',
            'course_possessive' => 'his',
        ];
    }

    /**
     * Get all active students from acceptance_letter_intern_students.
     */
    public function getActiveStudents()
    {
        try {
            $students = DB::table('acceptance_letter_intern_students as s')
                ->join('acceptance_letter_intern as a', 'a.id', '=', 's.acceptance_letter_intern_id')
                ->where('s.is_active', 1)
                ->select(
                    's.id',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.acceptance_letter_intern_id',
                    'a.school_name',
                    'a.school_officer',
                    'a.date_of_start'
                )
                ->orderBy('a.school_name')
                ->orderBy('s.last_name')
                ->orderBy('s.first_name')
                ->get();

            return $this->successResponse($students, 'Active students retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve active students', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve active students.');
        }
    }

    /**
     * Generate certificate of completion PDF and update is_active to 0.
     */
    public function print(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:acceptance_letter_intern_students,id',
            'completion_date' => 'required|date',
            'hours' => 'nullable|integer',
            'course' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
            'signatory' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'student_title' => 'nullable|string|in:mr,ms',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $studentIds = $request->input('student_ids');
            $titleLang = $this->studentTitleLanguage($request->input('student_title', 'mr'));

            // Get students with their school information
            $students = DB::table('acceptance_letter_intern_students as s')
                ->join('acceptance_letter_intern as a', 'a.id', '=', 's.acceptance_letter_intern_id')
                ->whereIn('s.id', $studentIds)
                ->where('s.is_active', 1)
                ->select(
                    's.id',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    'a.school_name',
                    'a.school_officer',
                    'a.date_of_start'
                )
                ->get();

            if ($students->isEmpty()) {
                return $this->validationErrorResponse(['student_ids' => ['No active students found for the selected IDs.']]);
            }

            // Normalize completion date
            $completionDate = $request->input('completion_date')
                ? \Carbon\Carbon::parse($request->input('completion_date'))->format('Y-m-d')
                : null;

            // Prepare data for PDF
            $headerImg = null;
            $footerImg = null;
            $headerPath = resource_path('img/report_header.jpg');
            $footerPath = resource_path('img/report_footer.jpg');
            if (file_exists($headerPath)) {
                $headerImg = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($headerPath));
            }
            if (file_exists($footerPath)) {
                $footerImg = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($footerPath));
            }

            // Calculate date range for each student
            $studentsWithDetails = $students->map(function ($student) use ($completionDate) {
                $startDate = $student->date_of_start ? \Carbon\Carbon::parse($student->date_of_start) : null;
                $endDate = $completionDate ? \Carbon\Carbon::parse($completionDate) : null;
                return (object) array_merge((array) $student, [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);
            });

            $data = array_merge($titleLang, [
                'students' => $studentsWithDetails,
                'completion_date' => $completionDate,
                'hours' => $request->input('hours', 300),
                'course' => $request->input('course', ''),
                'unit' => $request->input('unit', 'Human Resources Unit under the Administrative and Financial Management Division (AFMD)'),
                'signatory' => $request->input('signatory', 'EDUARDO A. PUYAOAN JR.'),
                'position' => $request->input('position', 'Chief Administrative Officer'),
                'header_img' => $headerImg,
                'footer_img' => $footerImg,
            ]);

            // Generate PDF
            $pdf = PDF::loadView('cert_of_completion.cert_of_completion_print', $data)
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');

            $pdfContent = $pdf->output();
            $filename = 'certificate_of_completion_' . now()->format('Ymd_His') . '.pdf';

            // Update is_active to 0 for selected students
            // TEMPORARILY DISABLED - Uncomment below to enable

            DB::table('acceptance_letter_intern_students')
                ->whereIn('id', $studentIds)
                ->update([
                    'is_active' => 0,
                    'updated_at' => now(),
                ]);


            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            Log::error('Failed to generate certificate of completion PDF', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to generate certificate of completion PDF.');
        }
    }

    /**
     * Download certificate as DOCX using PhpWord.
     */
    public function downloadDocx(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:acceptance_letter_intern_students,id',
            'completion_date' => 'required|date',
            'hours' => 'nullable|integer',
            'course' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
            'signatory' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'student_title' => 'nullable|string|in:mr,ms',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $studentIds = $request->input('student_ids');
            $titleLang = $this->studentTitleLanguage($request->input('student_title', 'mr'));

            // Get students with their school information
            $students = DB::table('acceptance_letter_intern_students as s')
                ->join('acceptance_letter_intern as a', 'a.id', '=', 's.acceptance_letter_intern_id')
                ->whereIn('s.id', $studentIds)
                ->where('s.is_active', 1)
                ->select(
                    's.id',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    'a.school_name',
                    'a.school_officer',
                    'a.date_of_start'
                )
                ->get();

            if ($students->isEmpty()) {
                return $this->validationErrorResponse(['student_ids' => ['No active students found for the selected IDs.']]);
            }

            // Normalize completion date
            $completionDate = $request->input('completion_date')
                ? Carbon::parse($request->input('completion_date'))->format('Y-m-d')
                : null;

            // Prepare data
            $headerPath = resource_path('img/report_header.jpg');
            $footerPath = resource_path('img/report_footer.jpg');

            // Calculate date range for each student
            $studentsWithDetails = $students->map(function ($student) use ($completionDate) {
                $startDate = $student->date_of_start ? Carbon::parse($student->date_of_start) : null;
                $endDate = $completionDate ? Carbon::parse($completionDate) : null;
                return (object) array_merge((array) $student, [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);
            })->values(); // Reset collection keys to ensure proper iteration

            $hours = $request->input('hours', 300);
            $course = $request->input('course', '');
            $unit = $request->input('unit', 'Human Resources Unit under the Administrative and Financial Management Division (AFMD)');
            $signatory = $request->input('signatory', 'EDUARDO A. PUYAOAN JR.');
            $position = $request->input('position', 'Chief Administrative Officer');
            $honorific = $titleLang['honorific'];
            $renderedIntro = $titleLang['rendered_intro'];
            $thanksBody = $titleLang['thanks_body'];
            $coursePossessive = $titleLang['course_possessive'];

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(12);

            // Paragraph style with explicit first-line indent and minimized spacing (defined once)
            $phpWord->addParagraphStyle('certBody', [
                'indentation' => [
                    'firstLine' => Converter::inchToTwip(0.5),
                    'left' => 0,
                ],
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 120,
                'lineHeight' => 1.15,
            ]);

            // Process each student - create a section for each
            // Convert to array to ensure proper iteration
            $studentsArray = $studentsWithDetails->all();

            // Log for debugging (can be removed later)
            Log::info('Generating DOCX for ' . count($studentsArray) . ' students');

            foreach ($studentsArray as $index => $student) {
                // Create new section for each student
                // Each new section automatically starts on a new page in PhpWord
                $section = $phpWord->addSection([
                    'marginTop' => Converter::inchToTwip(1),
                    'marginRight' => Converter::inchToTwip(0.75),
                    'marginBottom' => Converter::inchToTwip(0.9),
                    'marginLeft' => Converter::inchToTwip(1),
                    'pageSizeW' => Converter::inchToTwip(8.27),
                    'pageSizeH' => Converter::inchToTwip(11.69),
                ]);

                // Header and footer images, if available
                // Create fresh header and footer for each section using unique settings
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

                // Log each student being processed (can be removed later)
                Log::info('Processing student ' . ($index + 1) . ': ' . $student->first_name . ' ' . $student->last_name . ' from ' . $student->school_name);

                $section->addTextBreak(1);

                // Global MSME Academy
                $section->addText('Global MSME Academy', ['bold' => true, 'size' => 14], ['alignment' => 'center', 'spaceAfter' => 120]);

                // Certification Title
                $section->addText('CERTIFICATION', ['bold' => true, 'underline' => 'single', 'size' => 26], ['alignment' => 'center', 'spaceAfter' => 200, 'spaceBefore' => 120]);

                // Body - Paragraph 1
                $p1 = $section->addTextRun('certBody');
                $p1->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
                $p1->addText('This is to certify that ');
                $studentFullName = strtoupper($student->first_name . ' ' . ($student->middle_name ? $student->middle_name . ' ' : '') . $student->last_name);
                $p1->addText($honorific . ' ' . $studentFullName, ['bold' => true]);
                if (!empty($course)) {
                    $p1->addText(' a student of ' . $course);
                }
                $p1->addText(' from ');
                $p1->addText($student->school_name, ['bold' => true]);
                $p1->addText(' has successfully completed the ' . $hours . '-hour of Internship');
                if ($student->start_date && $student->end_date) {
                    $p1->addText(' from ');
                    $p1->addText($student->start_date->format('F d, Y'), ['bold' => true]);
                    $p1->addText(' to ');
                    $p1->addText($student->end_date->format('F d, Y'), ['bold' => true]);
                    $p1->addText('.');
                }
                $p1->addText(' ' . $renderedIntro . ' services under the ');
                $p1->addText($unit, ['bold' => true]);
                $p1->addText(' of the Department of Trade and Industry - ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ').');

                // Body - Paragraph 2
                $section->addTextBreak(0.5);
                $p2 = $section->addTextRun('certBody');
                $p2->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
                $p2->addText($thanksBody);

                // Body - Paragraph 3
                $section->addTextBreak(0.5);
                $p3 = $section->addTextRun('certBody');
                $p3->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
                $p3->addText('This certificate is being issued to ');
                $p3->addText($honorific . ' ' . strtoupper($student->last_name), ['bold' => true]);
                $p3->addText(' as a requirement of ' . $coursePossessive . ' course.');

                // Date and Location
                $section->addTextBreak(1);
                $endDate = $student->end_date ? $student->end_date : Carbon::parse($completionDate);
                $day = (int) $endDate->format('j');
                $suffix = ($day == 1 || $day == 21 || $day == 31) ? 'st'
                    : (($day == 2 || $day == 22) ? 'nd'
                        : (($day == 3 || $day == 23) ? 'rd' : 'th'));
                $dateLocation = 'Given this ' . $day . $suffix . ' day of ' . $endDate->format('F, Y') . ' at ' . CompanyHelper::getAddress() . '.';
                $section->addText($dateLocation, ['size' => 11.5], ['spaceAfter' => 200]);

                // Signature Section
                $section->addTextBreak(2);
                $section->addText($signatory, ['bold' => true], ['alignment' => 'right', 'spaceAfter' => 0]);
                $section->addText($position, ['size' => 10.5], ['alignment' => 'right']);
            }

            $filename = 'certificate_of_completion_' . now()->format('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to generate certificate of completion DOCX', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to generate certificate of completion DOCX.');
        }
    }
}
