<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Traits\ApiResponse;
use PDF;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;

class AcceptanceLetterInternController extends Controller
{
    use ApiResponse;

    /**
        * Generate PDF report for Acceptance Letter Intern.
        */
    public function print(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_officer' => 'required|string|max:255',
            'school_name'    => 'required|string|max:255',
            'school_address' => 'nullable|string|max:500',
            'salutation'     => 'nullable|string|max:255',
            'letter_date'    => 'nullable|date',
            'date_of_start'  => 'required|date',
            'course_program' => 'required|string|max:500',
            'signatory'      => 'nullable|string|max:255',
            'position'       => 'nullable|string|max:255',
            'students'       => 'required|array|min:1',
            'students.*.first_name'  => 'required|string|max:255',
            'students.*.middle_name' => 'nullable|string|max:255',
            'students.*.last_name'   => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
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

            // Normalize dates to Y-m-d to avoid timezone/format issues
            $rawStart = $request->input('date_of_start');
            $rawLetter = $request->input('letter_date');
            $dateOfStart = $rawStart ? \Carbon\Carbon::parse($rawStart)->format('Y-m-d') : null;
            $letterDate = $rawLetter ? \Carbon\Carbon::parse($rawLetter)->format('Y-m-d') : null;

            $data = [
                'school_officer' => $request->input('school_officer'),
                'school_name'    => $request->input('school_name'),
                'school_address' => $request->input('school_address'),
                'salutation'     => $request->input('salutation'),
                'letter_date'    => $letterDate,
                'date_of_start'  => $dateOfStart,
                'course_program' => $request->input('course_program'),
                'signatory'      => $request->input('signatory', 'MA FE J. AVILA'),
                'position'       => $request->input('position', 'OIC Executive Director'),
                'students'       => $request->input('students', []),
                'header_img'     => $headerImg,
                'footer_img'     => $footerImg,
            ];

            $pdf = PDF::loadView('acceptance_letter_intern.acceptance_letter_intern_print', $data)
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');

            $pdfContent = $pdf->output();
            $filename = 'acceptance_letter_intern_' . now()->format('Ymd_His') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            Log::error('Failed to generate acceptance letter intern PDF', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to generate acceptance letter intern PDF.');
        }
    }

    /**
     * Download acceptance letter intern as DOCX using PhpWord.
     */
    public function word(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_officer' => 'required|string|max:255',
            'school_name'    => 'required|string|max:255',
            'school_address' => 'nullable|string|max:500',
            'salutation'     => 'nullable|string|max:255',
            'letter_date'    => 'nullable|date',
            'date_of_start'  => 'required|date',
            'course_program' => 'required|string|max:500',
            'signatory'      => 'nullable|string|max:255',
            'position'       => 'nullable|string|max:255',
            'students'       => 'required|array|min:1',
            'students.*.first_name'  => 'required|string|max:255',
            'students.*.middle_name' => 'nullable|string|max:255',
            'students.*.last_name'   => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $headerPath = resource_path('img/report_header.jpg');
            $footerPath = resource_path('img/report_footer.jpg');

            // Normalize dates to Y-m-d to avoid timezone/format issues
            $rawStart = $request->input('date_of_start');
            $rawLetter = $request->input('letter_date');
            $dateOfStart = $rawStart ? Carbon::parse($rawStart)->format('Y-m-d') : null;
            $letterDate = $rawLetter ? Carbon::parse($rawLetter)->format('Y-m-d') : null;

            $formattedStart = $dateOfStart ? Carbon::createFromFormat('Y-m-d', $dateOfStart)->format('d F Y') : '';
            $formattedLetter = $letterDate ? Carbon::createFromFormat('Y-m-d', $letterDate)->format('d F Y') : Carbon::now()->format('d F Y');

            $schoolOfficer = $request->input('school_officer');
            $schoolName = $request->input('school_name');
            $schoolAddress = $request->input('school_address');
            $schoolCity = ''; // Not used in this view
            $salutation = $request->input('salutation', $schoolOfficer);
            $courseProgram = $request->input('course_program');
            $signatory = $request->input('signatory', 'MA FE J. AVILA');
            $position = $request->input('position', 'OIC Executive Director');
            $students = $request->input('students', []);

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(9);

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
            $phpWord->addParagraphStyle('letterBody', [
                'indentation' => [
                    'firstLine' => Converter::inchToTwip(0.5),
                    'left' => 0,
                ],
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 120,
                'lineHeight' => 1.15,
            ]);

            // Date
            $section->addText($formattedLetter, [], ['alignment' => 'left', 'spaceAfter' => 120]);
            $section->addTextBreak(0.5);

            // School Officer (bold, uppercase)
            $section->addText(strtoupper($schoolOfficer), ['bold' => true], ['spaceAfter' => 60]);
            // School Name
            $section->addText($schoolName, [], ['spaceAfter' => 60]);
            // School Address or City
            if (!empty($schoolAddress)) {
                $section->addText($schoolAddress, [], ['spaceAfter' => 120]);
            } else {
                $section->addText($schoolCity, [], ['spaceAfter' => 120]);
            }
            $section->addTextBreak(0.5);

            // Salutation
            $section->addText('Dear ' . $salutation . ',', ['bold' => true], ['spaceAfter' => 120]);
            $section->addTextBreak(0.5);

            // Greetings paragraph
            $p1 = $section->addTextRun('letterBody');
            $p1->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
            $p1->addText('Greetings from the DTI – ' . CompanyHelper::getName() . '!');

            // Main paragraph with indentation
            $p2 = $section->addTextRun('letterBody');
            $p2->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
            $p2->addText('We are pleased to inform you that your school\'s ');
            if (!empty($schoolName)) {
                $p2->addText('(' . $schoolName . ')', ['bold' => true]);
                $p2->addText(' ');
            }
            $p2->addText('internship application for the following recommended students of ');
            $p2->addText($courseProgram, ['bold' => true]);
            $p2->addText(' have been approved and accepted as Student-Intern by our Agency. The Face-to-Face internship shall commence on ');
            $p2->addText($formattedStart, ['bold' => true]);
            $p2->addText(' and will be reporting for work on a Face-to-Face setup.');

            // Students table - split into left and right columns
            $left = [];
            $right = [];
            foreach ($students as $i => $s) {
                if ($i % 2 === 0) {
                    $left[] = $s;
                } else {
                    $right[] = $s;
                }
            }
            $maxRows = max(count($left), count($right));

            // Create table for students
            $table = $section->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 40,
                'cellMarginTop' => 40,
                'cellMarginRight' => 40,
                'cellMarginBottom' => 40,
                'cellMarginLeft' => 40,
            ]);

            for ($i = 0; $i < $maxRows; $i++) {
                $table->addRow();
                
                // Left column number
                $cell1 = $table->addCell(Converter::inchToTwip(0.5));
                $cell1->addText(isset($left[$i]) ? ($i*2)+1 . '.' : '', [], ['alignment' => 'right']);
                
                // Left column name
                $cell2 = $table->addCell(Converter::inchToTwip(3.5));
                if (isset($left[$i])) {
                    $leftName = strtoupper(
                        trim($left[$i]['first_name'] ?? '') . ' ' .
                        trim($left[$i]['middle_name'] ?? '') . ' ' .
                        trim($left[$i]['last_name'] ?? '')
                    );
                    $cell2->addText($leftName, ['bold' => true]);
                }
                
                // Right column number
                $cell3 = $table->addCell(Converter::inchToTwip(0.5));
                $cell3->addText(isset($right[$i]) ? ($i*2)+2 . '.' : '', [], ['alignment' => 'right']);
                
                // Right column name
                $cell4 = $table->addCell(Converter::inchToTwip(3.5));
                if (isset($right[$i])) {
                    $rightName = strtoupper(
                        trim($right[$i]['first_name'] ?? '') . ' ' .
                        trim($right[$i]['middle_name'] ?? '') . ' ' .
                        trim($right[$i]['last_name'] ?? '')
                    );
                    $cell4->addText($rightName, ['bold' => true]);
                }
            }

            $section->addTextBreak(0.5);

            // Requirements paragraph
            $p3 = $section->addTextRun('letterBody');
            $p3->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
            $p3->addText('The following ');
            $p3->addText('Internship Program', ['bold' => true]);
            $p3->addText(' requirements have been received by the Center\'s HR Office:');

            // Requirements list
            $requirements = [
                'Official Endorsement from the University/School (e.g., Dean, International Studies Department) addressed to ' . BranchHelper::getMainBranchCode() . "'s Head of the Agency",
                'Resume with an updated picture',
                'Certificate of Good Moral Character from the University/School',
                'Certificate of Registration'
            ];

            foreach ($requirements as $req) {
                $listItem = $section->addTextRun(['indentation' => ['left' => Converter::inchToTwip(0.5), 'firstLine' => Converter::inchToTwip(-0.25)]]);
                $listItem->addText('• ', ['bold' => true]);
                $listItem->addText($req);
                $section->addTextBreak(0.3);
            }

            $section->addTextBreak(0.5);

            // Additional paragraph
            $p4 = $section->addTextRun('letterBody');
            $p4->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
            $p4->addText('In line with the requirements for the Internship Program in the Center, kindly submit the pertinent documents upon successful onboard, which are as follows:');

            $section->addTextBreak(0.5);

            // Practicum Agreement list item
            $listItem2 = $section->addTextRun(['indentation' => ['left' => Converter::inchToTwip(0.5), 'firstLine' => Converter::inchToTwip(-0.25)]]);
            $listItem2->addText('• ', ['bold' => true]);
            $listItem2->addText('Practicum Agreement', ['bold' => true]);
            $listItem2->addText(' – format will be given by DTI-' . BranchHelper::getMainBranchCode() . ' upon successful onboard and we would need details of the ');
            $listItem2->addText(' Practicum Adviser – Full Name and contact information (email address) ', ['bold' => true]);
            $listItem2->addText('for us to prepare this agreement. This agreement must be agreed upon by both parties upon submission of the rest of the requirements ');
            $listItem2->addText('in lieu', ['bold' => true]);
            $listItem2->addText(' of the Memorandum of Agreement. To be established during the first two weeks of the student intern.');

            $section->addTextBreak(1);

            // Congratulations paragraph
            $section->addText('Congratulations and Welcome to ' . BranchHelper::getMainBranchCode() . '!', ['bold' => true], ['spaceAfter' => 120]);
            $section->addTextBreak(1);

            // Signatory
            $section->addText($signatory, ['bold' => true], ['alignment' => 'left', 'spaceAfter' => 0]);
            $section->addText($position, [], ['alignment' => 'left']);

            $filename = 'acceptance_letter_intern_' . now()->format('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to generate acceptance letter intern DOCX', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to generate acceptance letter intern DOCX: ' . $e->getMessage());
        }
    }

    /**
        * Placeholder Word preview (returns PDF stream inline).
        */
    public function wordPreview(Request $request)
    {
        $pdfResponse = $this->print($request);
        if ($pdfResponse->getStatusCode() !== 200) {
            return $pdfResponse;
        }

        $content = $pdfResponse->getContent();
        $filename = 'acceptance_letter_intern_preview_' . now()->format('Ymd_His') . '.pdf';

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Content-Length', strlen($content));
    }

    /**
     * Get all acceptance letter intern records.
     */
    public function index()
    {
        try {
            $records = DB::table('acceptance_letter_intern')
                ->orderBy('created_at', 'desc')
                ->get();

            // Get student count for each record
            foreach ($records as $record) {
                $studentCount = DB::table('acceptance_letter_intern_students')
                    ->where('acceptance_letter_intern_id', $record->id)
                    ->count();
                $record->student_count = $studentCount;
            }

            return $this->successResponse($records, 'Acceptance letter intern records retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve acceptance letter intern records', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve acceptance letter intern records.');
        }
    }

    /**
     * Get students for a specific acceptance letter intern record.
     */
    public function getStudents($id)
    {
        try {
            $record = DB::table('acceptance_letter_intern')
                ->where('id', $id)
                ->first();

            if (!$record) {
                return $this->notFoundResponse('Acceptance letter intern record not found.');
            }

            $students = DB::table('acceptance_letter_intern_students')
                ->where('acceptance_letter_intern_id', $id)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->map(function ($student) {
                    $student->is_active = (bool) $student->is_active;
                    return $student;
                });

            return $this->successResponse([
                'record' => $record,
                'students' => $students
            ], 'Students retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve students', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve students.');
        }
    }

    /**
     * Store acceptance letter intern record in the database.
     */
    public function store(Request $request)
    {
        $rules = [
            'id'             => 'nullable|integer|exists:acceptance_letter_intern,id',
            'school_officer' => 'required|string|max:255',
            'school_name'    => 'required|string|max:255',
            'school_address' => 'nullable|string|max:500',
            'date_of_start'  => 'required|date',
            'course_program' => 'required|string|max:500',
            'signatory'      => 'nullable|string|max:255',
            'position'       => 'nullable|string|max:255',
            'students'       => 'required|array|min:1',
            'students.*.first_name'  => 'required|string|max:255',
            'students.*.middle_name' => 'nullable|string|max:255',
            'students.*.last_name'   => 'required|string|max:255',
            'students.*.is_active'   => 'nullable|boolean',
        ];

        if ($request->filled('id')) {
            $rules['students.*.id'] = [
                'nullable',
                'integer',
                Rule::exists('acceptance_letter_intern_students', 'id')
                    ->where('acceptance_letter_intern_id', (int) $request->input('id')),
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // Normalize date to Y-m-d format
            $dateOfStart = $request->input('date_of_start')
                ? \Carbon\Carbon::parse($request->input('date_of_start'))->format('Y-m-d')
                : null;

            $mainPayload = [
                'school_officer' => $request->input('school_officer'),
                'school_name'    => $request->input('school_name'),
                'school_address' => $request->input('school_address'),
                'course_program' => $request->input('course_program'),
                'date_of_start'  => $dateOfStart,
                'signatory'      => $request->input('signatory', 'MA FE J. AVILA'),
                'position'       => $request->input('position', 'OIC Executive Director'),
                'updated_at'     => now(),
            ];

            $students = $request->input('students', []);
            $acceptanceLetterId = null;

            DB::transaction(function () use ($request, $mainPayload, $students, &$acceptanceLetterId) {
                if ($request->filled('id')) {
                    $acceptanceLetterId = (int) $request->input('id');
                    DB::table('acceptance_letter_intern')->where('id', $acceptanceLetterId)->update($mainPayload);

                    $keptIds = [];
                    foreach ($students as $student) {
                        $sid = isset($student['id']) ? (int) $student['id'] : 0;
                        $isActive = array_key_exists('is_active', $student)
                            ? (bool) $student['is_active']
                            : true;

                        if ($sid > 0) {
                            DB::table('acceptance_letter_intern_students')
                                ->where('id', $sid)
                                ->where('acceptance_letter_intern_id', $acceptanceLetterId)
                                ->update([
                                    'first_name'  => $student['first_name'],
                                    'middle_name' => $student['middle_name'] ?? null,
                                    'last_name'   => $student['last_name'],
                                    'is_active'   => $isActive,
                                    'updated_at'  => now(),
                                ]);
                            $keptIds[] = $sid;
                        } else {
                            $keptIds[] = (int) DB::table('acceptance_letter_intern_students')->insertGetId([
                                'acceptance_letter_intern_id' => $acceptanceLetterId,
                                'first_name'  => $student['first_name'],
                                'middle_name' => $student['middle_name'] ?? null,
                                'last_name'   => $student['last_name'],
                                'is_active'   => $isActive,
                                'created_at'  => now(),
                                'updated_at'  => now(),
                            ]);
                        }
                    }

                    DB::table('acceptance_letter_intern_students')
                        ->where('acceptance_letter_intern_id', $acceptanceLetterId)
                        ->whereNotIn('id', $keptIds)
                        ->delete();
                } else {
                    $acceptanceLetterId = DB::table('acceptance_letter_intern')->insertGetId(array_merge($mainPayload, [
                        'created_at' => now(),
                    ]));

                    $studentRows = [];
                    foreach ($students as $student) {
                        $studentRows[] = [
                            'acceptance_letter_intern_id' => $acceptanceLetterId,
                            'first_name'  => $student['first_name'],
                            'middle_name' => $student['middle_name'] ?? null,
                            'last_name'   => $student['last_name'],
                            'is_active'   => isset($student['is_active']) ? (bool) $student['is_active'] : true,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ];
                    }
                    if (!empty($studentRows)) {
                        DB::table('acceptance_letter_intern_students')->insert($studentRows);
                    }
                }
            });

            $record = DB::table('acceptance_letter_intern')
                ->where('id', $acceptanceLetterId)
                ->first();

            $studentsOut = DB::table('acceptance_letter_intern_students')
                ->where('acceptance_letter_intern_id', $acceptanceLetterId)
                ->orderBy('first_name')
                ->get()
                ->map(function ($student) {
                    $student->is_active = (bool) ($student->is_active ?? true);
                    return $student;
                });

            $record->students = $studentsOut;

            $status = $request->filled('id') ? 200 : 201;
            $msg = $request->filled('id')
                ? 'Acceptance letter intern record updated successfully.'
                : 'Acceptance letter intern record saved successfully.';

            return $this->successResponse($record, $msg, $status);
        } catch (\Exception $e) {
            Log::error('Failed to save acceptance letter intern record', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to save acceptance letter intern record.');
        }
    }

    /**
     * Update student is_active status.
     */
    public function updateStudentStatus(Request $request, $studentId)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $student = DB::table('acceptance_letter_intern_students')
                ->where('id', $studentId)
                ->first();

            if (!$student) {
                return $this->notFoundResponse('Student not found.');
            }

            DB::table('acceptance_letter_intern_students')
                ->where('id', $studentId)
                ->update([
                    'is_active' => $request->input('is_active'),
                    'updated_at' => now(),
                ]);

            $updatedStudent = DB::table('acceptance_letter_intern_students')
                ->where('id', $studentId)
                ->first();

            return $this->successResponse($updatedStudent, 'Student status updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update student status', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to update student status.');
        }
    }
}

