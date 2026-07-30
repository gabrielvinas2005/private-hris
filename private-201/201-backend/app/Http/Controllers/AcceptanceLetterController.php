<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;

class AcceptanceLetterController extends Controller
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
     * Get applicants with status "For Hiring" (application_status_id = 5)
     */
    public function applicants()
    {
        try {
            $applicants = DB::table('applicant_headers as ah')
                ->join('applicant_details as ad', 'ah.id', '=', 'ad.applicant_id')
                ->leftJoin('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                ->leftJoin('positions as pos', 'p.position_id', '=', 'pos.id')
                ->select(
                    'ah.id as id',
                    'ah.applicant_no',
                    'ah.first_name',
                    'ah.middle_name',
                    'ah.last_name',
                    'ah.address',
                    'ah.birth_date',
                    'ah.email',
                    'ah.mobile_no',
                    'p.id as plantilla_id',
                    'p.code as plantilla_code',
                    'pos.id as position_id',
                    'pos.name as position_name',
                    'ad.id as applicant_detail_id',
                    DB::raw("CONCAT(COALESCE(ah.first_name, ''), ' ', COALESCE(ah.middle_name, ''), ' ', COALESCE(ah.last_name, '')) as full_name")
                )
                ->where('ad.application_status_id', 5) // For Hiring
                ->where('ad.is_plantilla', 1) // Only plantilla positions
                ->whereNull('ah.employee_no') // Not yet converted to employee
                ->orderBy('ah.last_name', 'asc')
                ->orderBy('ah.first_name', 'asc')
                ->get()
                ->map(function ($applicant) {
                    // Clean up full name (remove extra spaces)
                    $applicant->full_name = trim(preg_replace('/\s+/', ' ', $applicant->full_name));
                    return $applicant;
                });

            return $this->successResponse($applicants, 'Applicants retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve applicants for acceptance letter', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve applicants: ' . $e->getMessage());
        }
    }

    /**
     * Get employees for signatory dropdown
     */
    public function signatories()
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('name_prefixes as np', 'np.id', '=', 'e.name_prefix_id')
                ->select(
                    'e.id',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                ISNULL(np.name, '') + ' ' + ISNULL(e.first_name, '') + ' ' + ISNULL(e.middle_name, '') + ' ' + ISNULL(e.last_name, '')
                            ELSE
                                ISNULL(np.name, '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), '')
                            END as name"),
                    'p.name as position_name'
                )
                ->where('e.is_employee', true)
                ->where('e.active', true)
                ->orderBy('e.last_name', 'asc')
                ->orderBy('e.first_name', 'asc')
                ->get()
                ->map(function ($employee) {
                    // Clean up name (remove extra spaces)
                    $employee->name = trim(preg_replace('/\s+/', ' ', $employee->name));
                    return $employee;
                });

            return $this->successResponse($employees, 'Signatories retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve signatories for acceptance letter', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve signatories: ' . $e->getMessage());
        }
    }

    /**
     * Get departments for "For Discussion" dropdown
     */
    public function departments()
    {
        try {
            // Query departments - match pattern used in IPCRController and ApplicantHiringController
            $departments = DB::table('departments')
                ->where('active', true)
                ->orderBy('name', 'asc')
                ->get()
                ->map(function ($dept) {
                    return [
                        'id' => $dept->id,
                        'name' => $dept->name,
                        'code' => $dept->code ?? null
                    ];
                });

            return $this->successResponse($departments, 'Departments retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve departments for acceptance letter', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse('Failed to retrieve departments: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF acceptance letter
     */
    public function print(Request $request)
    {
        try {
            $validated = $request->validate([
                'applicants' => 'required|array|min:1',
                'applicants.*.applicant_id' => 'required|integer|exists:applicant_headers,id',
                'applicants.*.applicant_detail_id' => 'required|integer|exists:applicant_details,id',
                'signatory_id' => 'required|integer|exists:employees,id',
                'submit_date' => 'required|date',
                'notify_date' => 'required|date',
                'for_discussion_id' => 'required|integer|exists:departments,id',
            ]);

            // Load header and footer images
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

            // Get signatory info from database
            $app_key = env("APP_KEY", "");
            $signatoryEmployee = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('name_prefixes as np', 'np.id', '=', 'e.name_prefix_id')
                ->select(
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                ISNULL(np.name, '') + ' ' + ISNULL(e.first_name, '') + ' ' + ISNULL(e.middle_name, '') + ' ' + ISNULL(e.last_name, '')
                            ELSE
                                ISNULL(np.name, '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), '')
                            END as name"),
                    'p.name as position_name'
                )
                ->where('e.id', $validated['signatory_id'])
                ->first();

            if (!$signatoryEmployee) {
                return $this->errorResponse('Signatory employee not found.', 404);
            }

            // Get department info
            $department = DB::table('departments')
                ->select('id', 'name', 'code')
                ->where('id', $validated['for_discussion_id'])
                ->first();

            if (!$department) {
                return $this->errorResponse('Department not found.', 404);
            }

            $signatory = trim(preg_replace('/\s+/', ' ', $signatoryEmployee->name));
            $position = $signatoryEmployee->position_name ?? '';
            $letterDate = Carbon::now()->format('Y-m-d');
            $submitDate = $validated['submit_date'];
            $notifyDate = $validated['notify_date'];

            // Process all applicants
            $applicantsData = [];
            foreach ($validated['applicants'] as $applicantRequest) {
                // Get applicant data with all related information
                $applicant = DB::table('applicant_headers as ah')
                    ->join('applicant_details as ad', 'ah.id', '=', 'ad.applicant_id')
                    ->leftJoin('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                    ->leftJoin('positions as pos', 'p.position_id', '=', 'pos.id')
                    ->leftJoin('departments as d', 'p.department_id', '=', 'd.id')
                    ->leftJoin('genders as g', 'g.id', '=', 'ah.gender')
                    ->leftjoin('employees as e', 'e.employee_no', '=', 'ah.applicant_no')
                    ->select(
                        'ah.id',
                        'ah.applicant_no',
                        'ah.first_name',
                        'ah.middle_name',
                        'ah.last_name',
                        'e.ra_house_no',
                        'e.ra_street',
                        'e.ra_barangay',
                        'e.ra_village',
                        'e.ra_city',
                        'ah.birth_date',
                        'ah.email',
                        'ah.mobile_no',
                        'ah.gender as gender_id',
                        'g.name as gender',
                        'p.id as plantilla_id',
                        'p.code as plantilla_code',
                        'pos.id as position_id',
                        'pos.name as position_name',
                        'd.name as department_name',
                        'ad.is_plantilla',
                        'p.salary_grade_id',
                        'p.salary_step_id',
                        'ad.id as applicant_detail_id'
                    )
                    ->where('ah.id', $applicantRequest['applicant_id'])
                    ->where('ad.id', $applicantRequest['applicant_detail_id'])
                    ->where('ad.application_status_id', 5)
                    ->first();

                if (!$applicant) {
                    continue; // Skip invalid applicants
                }

                // Get salary information from active salary schedule
                $salaryInfo = DB::table('salary_schedules as ss')
                    ->join('salary_schedules_details as ssd', function ($join) use ($applicant) {
                        $join->on('ss.id', '=', 'ssd.salary_schedule_id')
                            ->where('ssd.salary_grade_id', '=', $applicant->salary_grade_id)
                            ->where('ssd.salary_step_id', '=', $applicant->salary_step_id);
                    })
                    ->select(
                        'ssd.amount as salary',
                        'ss.name as salary_schedule_name'
                    )
                    ->where('ss.active', 1)
                    ->first();

                if (!$salaryInfo) {
                    continue; // Skip if salary info not found
                }

                // Convert salary to words
                $salaryAmount = (float)$salaryInfo->salary;
                $salaryInWords = \App\Helpers\NumberToWords::convert((int)$salaryAmount);
                $salaryInWords = ucwords($salaryInWords) . ' Pesos';

                $applicantsData[] = [
                    'applicant' => $applicant,
                    'salary_amount' => $salaryAmount,
                    'salary_in_words' => $salaryInWords,
                    'salary_grade_id' => $applicant->salary_grade_id,
                    'salary_step_id' => $applicant->salary_step_id,
                ];
            }

            if (empty($applicantsData)) {
                return $this->errorResponse('No valid applicants found for acceptance letter generation.', 404);
            }

            // Check if this is for preview (shows all applicants in one PDF)
            $isPreview = $request->input('preview', false);

            if ($isPreview && count($applicantsData) > 1) {
                // Generate combined PDF for preview (all applicants in one PDF)
                $data = [
                    'applicants_data' => $applicantsData,
                    'signatory' => $signatory,
                    'position' => $position,
                    'letter_date' => $letterDate,
                    'submit_date' => $submitDate,
                    'notify_date' => $notifyDate,
                    'for_discussion' => $department,
                    'header_img' => $headerImg,
                    'footer_img' => $footerImg,
                ];

                $pdf = PDF::loadView('employee_certificates.acceptance_letter_form_batch', $data)
                    ->setOptions(['defaultFont' => 'sans-serif']);
                $pdf->setPaper('A4', 'portrait');

                $pdfContent = $pdf->output();
                $filename = 'acceptance_letters_preview_' . count($applicantsData) . '_applicants_' . date('Ymd_His') . '.pdf';

                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                    ->header('Content-Length', strlen($pdfContent));
            }

            // Generate and return single PDF file (for download - one file per applicant)
            $applicant = $applicantsData[0]['applicant'];
            $salaryAmount = $applicantsData[0]['salary_amount'];
            $salaryInWords = $applicantsData[0]['salary_in_words'];
            $salaryGradeId = $applicantsData[0]['salary_grade_id'];
            $salaryStepId = $applicantsData[0]['salary_step_id'];

            $data = [
                'applicant' => $applicant,
                'signatory' => $signatory,
                'position' => $position,
                'letter_date' => $letterDate,
                'submit_date' => $submitDate,
                'notify_date' => $notifyDate,
                'for_discussion' => $department,
                'header_img' => $headerImg,
                'footer_img' => $footerImg,
                'salary_amount' => $salaryAmount,
                'salary_in_words' => $salaryInWords,
                'salary_grade_id' => $salaryGradeId,
                'salary_step_id' => $salaryStepId,
            ];

            // Generate single PDF
            $pdf = PDF::loadView('employee_certificates.acceptance_letter_form', $data)
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'portrait');

            $pdfContent = $pdf->output();
            $filename = 'acceptance_letter_' . $applicant->applicant_no . '_' . date('Ymd_His') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to generate acceptance letter PDF', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to generate acceptance letter PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate DOCX acceptance letter
     */
    public function word(Request $request)
    {
        try {
            $validated = $request->validate([
                'applicants' => 'required|array|min:1',
                'applicants.*.applicant_id' => 'required|integer|exists:applicant_headers,id',
                'applicants.*.applicant_detail_id' => 'required|integer|exists:applicant_details,id',
                'signatory_id' => 'required|integer|exists:employees,id',
                'submit_date' => 'required|date',
                'notify_date' => 'required|date',
                'for_discussion_id' => 'required|integer|exists:departments,id',
            ]);

            // Get signatory info from database
            $app_key = env("APP_KEY", "");
            $signatoryEmployee = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('name_prefixes as np', 'np.id', '=', 'e.name_prefix_id')
                ->select(
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                ISNULL(np.name, '') + ' ' + ISNULL(e.first_name, '') + ' ' + ISNULL(e.middle_name, '') + ' ' + ISNULL(e.last_name, '')
                            ELSE
                                ISNULL(np.name, '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), '')
                            END as name"),
                    'p.name as position_name'
                )
                ->where('e.id', $validated['signatory_id'])
                ->first();

            if (!$signatoryEmployee) {
                return $this->errorResponse('Signatory employee not found.', 404);
            }

            // Get department info
            $department = DB::table('departments')
                ->select('id', 'name', 'code')
                ->where('id', $validated['for_discussion_id'])
                ->first();

            if (!$department) {
                return $this->errorResponse('Department not found.', 404);
            }

            $signatory = trim(preg_replace('/\s+/', ' ', $signatoryEmployee->name));
            $position = $signatoryEmployee->position_name ?? '';
            $letterDate = Carbon::now()->format('Y-m-d');
            $submitDate = $validated['submit_date']; // Keep original datetime format
            $notifyDate = $validated['notify_date']; // Keep original date format

            // Process all applicants
            $applicantsData = [];
            foreach ($validated['applicants'] as $applicantRequest) {
                // Get applicant data with all related information
                $applicant = DB::table('applicant_headers as ah')
                    ->join('applicant_details as ad', 'ah.id', '=', 'ad.applicant_id')
                    ->leftJoin('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                    ->leftJoin('positions as pos', 'p.position_id', '=', 'pos.id')
                    ->leftJoin('departments as d', 'p.department_id', '=', 'd.id')
                    ->leftJoin('genders as g', 'g.id', '=', 'ah.gender')
                    ->leftjoin('employees as e', 'e.employee_no', '=', 'ah.applicant_no')
                    ->select(
                        'ah.id',
                        'ah.applicant_no',
                        'ah.first_name',
                        'ah.middle_name',
                        'ah.last_name',
                        'e.ra_house_no',
                        'e.ra_street',
                        'e.ra_barangay',
                        'e.ra_city',
                        'ah.birth_date',
                        'ah.email',
                        'ah.mobile_no',
                        'ah.gender as gender_id',
                        'g.name as gender',
                        'p.id as plantilla_id',
                        'p.code as plantilla_code',
                        'pos.id as position_id',
                        'pos.name as position_name',
                        'd.name as department_name',
                        'ad.is_plantilla',
                        'p.salary_grade_id',
                        'p.salary_step_id',
                        'ad.id as applicant_detail_id'
                    )
                    ->where('ah.id', $applicantRequest['applicant_id'])
                    ->where('ad.id', $applicantRequest['applicant_detail_id'])
                    ->where('ad.application_status_id', 5)
                    ->first();

                if (!$applicant) {
                    continue; // Skip invalid applicants
                }

                // Get salary information from active salary schedule
                $salaryInfo = DB::table('salary_schedules as ss')
                    ->join('salary_schedules_details as ssd', function ($join) use ($applicant) {
                        $join->on('ss.id', '=', 'ssd.salary_schedule_id')
                            ->where('ssd.salary_grade_id', '=', $applicant->salary_grade_id)
                            ->where('ssd.salary_step_id', '=', $applicant->salary_step_id);
                    })
                    ->select(
                        'ssd.amount as salary',
                        'ss.name as salary_schedule_name'
                    )
                    ->where('ss.active', 1)
                    ->first();

                if (!$salaryInfo) {
                    continue; // Skip if salary info not found
                }

                // Convert salary to words
                $salaryAmount = (float)$salaryInfo->salary;
                $salaryInWords = \App\Helpers\NumberToWords::convert((int)$salaryAmount);
                $salaryInWords = ucwords($salaryInWords) . ' Pesos';

                $applicantsData[] = [
                    'applicant' => $applicant,
                    'salary_amount' => $salaryAmount,
                    'salary_in_words' => $salaryInWords,
                    'salary_grade_id' => $applicant->salary_grade_id,
                    'salary_step_id' => $applicant->salary_step_id,
                ];
            }

            if (empty($applicantsData)) {
                return $this->errorResponse('No valid applicants found for acceptance letter generation.', 404);
            }

            // Generate and return single DOCX file (for first applicant if multiple)
            // For multiple applicants, the frontend will call this endpoint multiple times
            return $this->generateSingleWord($applicantsData[0], $signatory, $position, $department, $letterDate, $submitDate, $notifyDate);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to generate acceptance letter DOCX', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to generate acceptance letter DOCX: ' . $e->getMessage());
        }
    }

    /**
     * Generate single Word document for one applicant
     */
    private function generateSingleWord($letterData, $signatory, $position, $department, $letterDate, $submitDate, $notifyDate)
    {
        $phpWord = $this->createWordDocument($letterData, $signatory, $position, $department, $letterDate, $submitDate, $notifyDate);

        $tempFile = tempnam(sys_get_temp_dir(), 'acceptance_letter_');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);

        $wordContent = file_get_contents($tempFile);
        unlink($tempFile);

        $filename = 'acceptance_letter_' . $letterData['applicant']->applicant_no . '_' . date('Ymd_His') . '.docx';

        return response($wordContent)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($wordContent));
    }

    /**
     * Generate Word document content as string for ZIP
     */
    private function generateSingleWordContent($letterData, $signatory, $position, $department, $letterDate, $submitDate, $notifyDate)
    {
        $phpWord = $this->createWordDocument($letterData, $signatory, $position, $department, $letterDate, $submitDate, $notifyDate);

        $tempFile = tempnam(sys_get_temp_dir(), 'acceptance_letter_');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);

        $wordContent = file_get_contents($tempFile);
        unlink($tempFile);

        return $wordContent;
    }

    /**
     * Create Word document for an applicant
     */
    private function createWordDocument($letterData, $signatory, $position, $department, $letterDate, $submitDate, $notifyDate)
    {
        $applicant = $letterData['applicant'];
        $salaryAmount = $letterData['salary_amount'];
        $salaryInWords = $letterData['salary_in_words'];

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(9);

        // Remove header/footer margins and maximize image size
        $section = $phpWord->addSection([
            'marginTop' => Converter::inchToTwip(0),  // No header margin
            'marginRight' => Converter::inchToTwip(0.5),
            'marginBottom' => Converter::inchToTwip(0),  // No footer margin
            'marginLeft' => Converter::inchToTwip(0.5),
            'pageSizeW' => Converter::inchToTwip(8.27),
            'pageSizeH' => Converter::inchToTwip(11.69),
        ]);

        // Header and footer images - maximized size
        $headerPath = resource_path('img/report_header.jpg');
        $footerPath = resource_path('img/report_footer.jpg');

        if (file_exists($headerPath)) {
            $header = $section->addHeader();
            // Maximize image width (A4 width ~21cm, using 20.5cm for full width with minimal margins)
            $header->addImage($headerPath, [
                'width' => Converter::cmToPoint(12),
                'alignment' => 'center'
            ]);
        }
        if (file_exists($footerPath)) {
            $footer = $section->addFooter();
            // Maximize image width (A4 width ~21cm, using 20.5cm for full width with minimal margins)
            $footer->addImage($footerPath, [
                'width' => Converter::cmToPoint(12),
                'alignment' => 'center'
            ]);
        }

        // Determine title based on gender_id (1 = Ms., 2 = Mr.)
        $title = ($applicant->gender_id == 1) ? 'Ms.' : 'Mr.';

        // Format full name: First [Middle] Last (middle only if not null)
        $middlePart = !empty($applicant->middle_name) ? ' ' . $applicant->middle_name : '';
        $fullName = $applicant->first_name . $middlePart . ' ' . $applicant->last_name;

        // Format date as "d F Y" (e.g., "11 January 2024")
        $formattedDate = Carbon::parse($letterDate)->format('d F Y');

        // Reduced spacing between header and date
        $section->addText($formattedDate, [], ['alignment' => 'left', 'spaceAfter' => 240]);

        // Applicant full name with prefix (bolded) and address in one text run
        $nameAddressRun = $section->addTextRun(['spaceAfter' => 120]);
        $nameAddressRun->addText($title . ' ' . $fullName, ['bold' => true]);

        // Applicant address (if available) - handle multi-line
        // if ($applicant->address) {
        //     $addressLines = preg_split('/[\r\n]+/', $applicant->address, -1, PREG_SPLIT_NO_EMPTY);
        //     foreach ($addressLines as $line) {
        //         $line = trim($line);
        //         if (!empty($line)) {
        //             $nameAddressRun->addTextBreak(1);
        //             $nameAddressRun->addText($line);
        //         }
        //     }
        // }
        $nameAddressRun->addTextBreak(1);
        $house_no = $applicant->ra_house_no;
        $street = $applicant->ra_street;
        $barangay = $applicant->ra_barangay;
        $city = $applicant->ra_city;



        $nameAddressRun->addText(trim($house_no));
        $nameAddressRun->addTextBreak(1);
        $nameAddressRun->addText(trim($street) . ' St., Brgy. ' . trim($barangay));
        $nameAddressRun->addTextBreak(1);
        $nameAddressRun->addText(trim($city) . ' ' . 'City');
        $section->addTextBreak(1);


        // Greeting with title
        $section->addText('Dear ' . $title . ' ' . ($applicant->last_name ?? '') . ',', ['bold' => true], ['spaceAfter' => 240]);


        // Opening greeting
        $section->addText('Warm greetings from the DTI – ' . CompanyHelper::getName() . '!', [], ['spaceAfter' => 240]);


        // Second paragraph with salary information (indented)
        $bodyStyle = [
            'indentation' => [
                'firstLine' => Converter::inchToTwip(0.5),
                'left' => 0,
            ],
            'alignment' => WordJc::BOTH,
            'spaceAfter' => 240,
            'lineHeight' => 1.15,
        ];

        $phpWord->addParagraphStyle('bodyStyle', $bodyStyle);
        $bodyPara = $section->addTextRun('bodyStyle');
        $bodyPara->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
        $bodyPara->addText('After a careful and thorough evaluation of your qualifications and other competency requirements for the position of ');
        $bodyPara->addText($applicant->position_name ?? 'N/A', ['bold' => true]);
        $isPlantilla = (int) ($applicant->is_plantilla ?? 0) === 1;
        $compensationText = $isPlantilla
            ? ' under Plantilla with monthly salary of '
            : ' under Contract of Service (COS) with monthly service fee of ';
        $premiumText = $isPlantilla ? '' : ' plus 20% premium';

        $bodyPara->addText($compensationText);
        $bodyPara->addText($salaryInWords, ['bold' => true]);
        $bodyPara->addText(' (Php ' . number_format($salaryAmount, 2, '.', ',') . ') - SG ' . $letterData['salary_grade_id'] . ', Step ' . $letterData['salary_step_id'] . $premiumText . ', the Head of Agency of ' . CompanyHelper::getName() . ' has accepted you to the said position.');


        // Third paragraph with department and submit date
        $bodyPara2 = $section->addTextRun('bodyStyle');
        $bodyPara2->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
        $bodyPara2->addText('In this regard, the ');
        $bodyPara2->addText($department->name ?? 'Office of the Executive Director (OED)', ['bold' => true]);
        $bodyPara2->addText(' shall discuss with you the details of your duties and responsibilities as ');
        $bodyPara2->addText($applicant->position_name ?? 'Technical Assistant');
        $bodyPara2->addText('. Correspondingly, you are requested to submit the following pre-employment requirements to the ' . BranchHelper::getMainBranchCode() . '-AFMD Human Resource Section on or before ');
        $submitDateFormatted = Carbon::parse($submitDate)->format('F d, Y') . ' at ' . Carbon::parse($submitDate)->format('g:i A');
        $bodyPara2->addText($submitDateFormatted, ['bold' => true]);
        $bodyPara2->addText('. Your onboarding shall take effect upon verification of the submitted documents, both original and photocopies and signing of the service contract.');


        // Pre-employment requirements list
        $requirements = [
            'NBI Clearance (updated, original copy)',
            'Drug Test Result (original copy)',
            'Medical Certificate (CSC Form No. 211, Revised 2018) signed by a government physician with the attached laboratory results (all original copy)',
            'Vaccination Card',
            'Birth Certificate',
            'Marriage Certificate (if applicable)',
            'TIN ID or BIR Form No. 1902/1905/2316',
            'Authenticated Transcript of Records and Diploma',
            'Authenticated CSC Eligibility/PRC Board Rating (if applicable)',
            'Updated Personal Data Sheet (updated, CSC Form 212, Revised 2017 printed in legal size paper, 3 copies)',
            'CSC Form No. 212 Attachment - Work Experience Sheet (WES)',
            'Certificate of Employment from previous jobs',
            'Certificate of Training/seminars attended'
        ];

        $listStyle = [
            'indentation' => [
                'left' => Converter::inchToTwip(0.5),
                'hanging' => Converter::inchToTwip(0.25),
            ],
            'spaceAfter' => 30,
            'lineHeight' => 1.5,
        ];

        $phpWord->addParagraphStyle('listStyle', $listStyle);

        foreach ($requirements as $requirement) {
            $listPara = $section->addTextRun('listStyle');
            $listPara->addText('• ', ['bold' => true]);
            $listPara->addText($requirement);
        }


        // Acknowledgment paragraph (indented)
        $acknowledgePara = $section->addTextRun('bodyStyle');
        $acknowledgePara->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
        $acknowledgePara->addText('We would highly appreciate your acknowledgment and response on this offer ');
        $notifyDateFormatted = Carbon::parse($notifyDate)->format('F d, Y');
        $acknowledgePara->addText('on or before ' . $notifyDateFormatted, ['bold' => true]);
        $acknowledgePara->addText('.');


        // Congratulations paragraph (indented)
        $congratsPara = $section->addTextRun('bodyStyle');
        $congratsPara->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
        $congratsPara->addText('Congratulations, and we look forward to having you onboard!');


        // Sincerely
        $section->addText('Sincerely,', [], ['alignment' => 'left', 'spaceAfter' => 50]);

        // Signatory
        $section->addText($signatory, ['bold' => true], ['alignment' => 'left', 'spaceAfter' => 0]);
        $section->addText($position, [], ['alignment' => 'left']);

        return $phpWord;
    }

    /**
     * Send acceptance letter via email to selected applicants
     */
    public function sendEmail(Request $request)
    {
        try {
            DB::beginTransaction();

            $applicants = $request->input('applicants', []);
            $signatory_id = $request->input('signatory_id');
            $submit_date = $request->input('submit_date');
            $notify_date = $request->input('notify_date');
            $for_discussion_id = $request->input('for_discussion_id');

            if (empty($applicants)) {
                return $this->errorResponse('Please select at least one applicant', [], 400);
            }

            $sent = 0;
            $failed = 0;
            $errors = [];

            foreach ($applicants as $applicantData) {
                try {
                    $applicant_id = $applicantData['applicant_id'];
                    $applicant_detail_id = $applicantData['applicant_detail_id'];

                    // Get applicant details
                    $applicant = DB::table('applicant_headers as ah')
                        ->join('applicant_details as ad', 'ah.id', '=', 'ad.applicant_id')
                        ->leftJoin('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                        ->leftJoin('positions as pos', 'p.position_id', '=', 'pos.id')
                        ->select(
                            'ah.id as applicant_id',
                            'ah.applicant_no',
                            'ah.email',
                            'ah.first_name',
                            'ah.middle_name',
                            'ah.last_name',
                            'pos.name as position_name',
                            'ad.position_applied_id',
                            DB::raw("CONCAT(COALESCE(ah.first_name, ''), ' ', COALESCE(ah.middle_name, ''), ' ', COALESCE(ah.last_name, '')) as full_name")
                        )
                        ->where('ah.id', $applicant_id)
                        ->where('ad.id', $applicant_detail_id)
                        ->first();

                    if (!$applicant || !$applicant->email) {
                        $errors[] = "Applicant ID {$applicant_id} has no email address";
                        $failed++;
                        continue;
                    }

                    // Generate PDF using internal print method
                    $printRequest = new Request([
                        'applicants' => [['applicant_id' => $applicant_id, 'applicant_detail_id' => $applicant_detail_id]],
                        'signatory_id' => $signatory_id,
                        'submit_date' => $submit_date,
                        'notify_date' => $notify_date,
                        'for_discussion_id' => $for_discussion_id
                    ]);

                    $pdfResponse = $this->print($printRequest);
                    $pdfContent = $pdfResponse->getContent();

                    // Save PDF to temporary file
                    $pdfPath = storage_path('app/temp_acceptance_' . $applicant_id . '_' . time() . '.pdf');
                    file_put_contents($pdfPath, $pdfContent);

                    // Send email notification
                    $applicantEmailData = [
                        'applicant_name' => trim($applicant->full_name),
                        'position_name' => $applicant->position_name
                    ];

                    $notifiable = (object) ['email' => $applicant->email, 'name' => $applicantEmailData['applicant_name']];
                    \Notification::route('mail', $applicant->email)
                        ->notify(new \App\Notifications\EmailAcceptanceLetter($applicantEmailData, $pdfPath));

                    // Record in history table
                    DB::table('acceptance_letter_history')->insert([
                        'applicant_id' => $applicant->applicant_id,
                        'applicant_no' => $applicant->applicant_no,
                        'applicant_name' => trim($applicant->full_name),
                        'email' => $applicant->email,
                        'position_applied' => $applicant->position_name,
                        'position_applied_id' => $applicant->position_applied_id,
                        'status' => 'sent',
                        'sent_by' => \Auth::id(),
                        'sent_at' => DB::raw('GETDATE()')
                    ]);

                    // Clean up temp file
                    if (file_exists($pdfPath)) {
                        unlink($pdfPath);
                    }

                    $sent++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Failed to send email to applicant ID {$applicant_id}: " . $e->getMessage();
                    Log::error("Failed to send acceptance letter email", [
                        'applicant_id' => $applicant_id,
                        'error' => $e->getMessage()
                    ]);

                    // Record failure in history
                    if (isset($applicant)) {
                        DB::table('acceptance_letter_history')->insert([
                            'applicant_id' => $applicant->applicant_id ?? $applicant_id,
                            'applicant_no' => $applicant->applicant_no ?? null,
                            'applicant_name' => isset($applicant->full_name) ? trim($applicant->full_name) : null,
                            'email' => $applicant->email ?? null,
                            'position_applied' => $applicant->position_name ?? null,
                            'position_applied_id' => $applicant->position_applied_id ?? null,
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                            'sent_by' => \Auth::id(),
                            'sent_at' => DB::raw('GETDATE()')
                        ]);
                    }
                }
            }

            DB::commit();

            $message = "Successfully sent {$sent} acceptance letter(s).";
            if ($failed > 0) {
                $message .= " {$failed} failed.";
            }

            return $this->successResponse([
                'sent' => $sent,
                'failed' => $failed,
                'errors' => $errors
            ], $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send acceptance letters', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to send acceptance letters: ' . $e->getMessage());
        }
    }
}
