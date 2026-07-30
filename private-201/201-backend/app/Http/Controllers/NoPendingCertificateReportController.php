<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \NumberFormatter;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class NoPendingCertificateReportController extends Controller
{
    use ApiResponse;

    private const DEFAULT_PURPOSE_TEXT = 'in connection with the renewal of fidelity bond.';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('employees')
            ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
            ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
            ->select(
                'employees.photo',
                'employees.id',
                'employees.employee_no',
                'employees.email',
                'employees.date_hired',
                'employment_types.name as employment_type',
                'positions.name as position',
                'departments.name as department',
                'branches.name as branch',
                DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                            CONCAT(employees.first_name,' ',employees.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                        END as name")
            )
            ->orderBy('employees.first_name', 'asc')
            ->where(['employees.is_employee' => true, 'employees.active' => true])
            ->paginate(10000);

        return $this->successResponse($data, 'No pending certificates retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve no pending certificates: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee' => 'required|exists:employees,id',
                'signatory' => 'required|string',
                'position' => 'required|string',
                'purpose_text' => 'nullable|string|max:1000',
            ], [
                'employee.required' => 'Please select employee.',
                'employee.exists' => 'Selected employee does not exist.',
                'signatory.required' => 'Signatory name is required.',
                'position.required' => 'Signatory position is required.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

        $purpose_text = $this->resolvePurposeText($request);

        $app_key = env("APP_KEY", "");
        $companies = DB::table('companies')->get();

        $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
        
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
        
        // Load signature image
        $signatureImg = null;
        $signaturePath = resource_path('img/Signature.png');
        if (file_exists($signaturePath)) {
            $signatureImg = 'data:image/png;base64,' . base64_encode(file_get_contents($signaturePath));
        }

        $employees = DB::table('employees as a')
            ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
            ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
            ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
            ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
            ->select(
                'a.id',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            CONCAT(a.first_name,' ',a.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                        END as name"),
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            RTRIM(a.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                        END as last_name"),
                'b.name as position',
                'c.name as department',
                'd.name as employment_type',
                'a.salary',
                'a.date_hired',
                'a.gender_id',
                DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig")
            )
            ->where('a.id', $request->employee)
            ->distinct()
            ->get();

           

        $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
        $salary_word = $f->format(($employees[0]->salary) * 12);
        $annual_salary = ($employees[0]->salary) * 12;

        $incomes = DB::table('payroll_incomes as a')
            ->join('incomes as b', 'a.income_id', '=', 'b.id')
            ->select(
                'a.employee_id',
                'b.name',
                'a.amount'
            )
            ->where(
                'a.employee_id',
                $request->employee
            )
            ->get();

        $signatories = array(
            'signatory' => $request->signatory,
            'position' => $request->position
        );

        $pdf = PDF::loadView('no_pending_certificates.no_pending_certificate_print', compact(
            'employees',
            'incomes',
            'salary_word',
            'annual_salary',
            'signatories',
            'image',
            'companies',
            'headerImg',
            'footerImg',
            'signatureImg',
            'purpose_text'
        ))->setOptions(['defaultFont' => 'sans-serif']);
        $pdf->setPaper('A4');
        
        $pdfContent = $pdf->output();
        $base64Pdf = base64_encode($pdfContent);

        $filename = 'no_pending_certificate_' . $request->employee . '_' . date('Y-m-d') . '.pdf';
        
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate no pending certificate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Download certificate as DOCX using PhpWord.
     */
    public function downloadDocx(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee' => 'required|exists:employees,id',
                'signatory' => 'required|string',
                'position' => 'required|string',
                'purpose_text' => 'nullable|string|max:1000',
            ], [
                'employee.required' => 'Please select employee.',
                'employee.exists' => 'Selected employee does not exist.',
                'signatory.required' => 'Signatory name is required.',
                'position.required' => 'Signatory position is required.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $purpose_text = $this->resolvePurposeText($request);

            $app_key = env("APP_KEY", "");
            $headerPath = resource_path('img/report_header.jpg');
            $footerPath = resource_path('img/report_footer.jpg');
            $signaturePath = resource_path('img/Signature.png');

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                RTRIM(a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key']))
                            END as last_name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig")
                )
                ->where('a.id', $request->employee)
                ->distinct()
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $employee = $employees->first();

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(12);

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


            // Date (right aligned)
            $section->addText(Carbon::now()->format('d F Y'), [], ['alignment' => 'right', 'spaceAfter' => 120]);

            // Certification Title
            $section->addText('CERTIFICATION', ['bold' => true, 'underline' => 'single', 'size' => 20], ['alignment' => 'center', 'spaceAfter' => 200, 'spaceBefore' => 120]);

            // Salutation
            $section->addText('To whom it may concern:', ['bold' => true], ['spaceAfter' => 120]);

            // Paragraph style with explicit first-line indent
            $phpWord->addParagraphStyle('certificateBody', [
                'indentation' => [
                    'firstLine' => Converter::inchToTwip(0.5),
                    'left' => 0,
                ],
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 120,
                'lineHeight' => 1.15,
            ]);

            // Body paragraph 1 - Employee intro
            $p1 = $section->addTextRun('certificateBody');
            $p1->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
            $p1->addText('This is to certify that ');
            $p1->addText(strtoupper($employee->name ?? ''), ['bold' => true]);
            $p1->addText(' ' . ($employee->position ?? '') . ' of ' . CompanyHelper::getName() . ':');

            // Numbered list
            $section->addTextBreak(0.5);
            $section->addText('1. has no pending administrative and/or criminal case;', [], ['indentation' => ['left' => Converter::inchToTwip(0.5)], 'spaceAfter' => 120, 'lineHeight' => 1.15]);
            
            $yearLast = date('Y', strtotime('-1 year'));
            $p2 = $section->addTextRun(['indentation' => ['left' => Converter::inchToTwip(0.5)], 'spaceAfter' => 120, 'lineHeight' => 1.15]);
            $p2->addText('2. has filed her Sworn Statement of Assets, Liabilities and Net worth as of ');
            $p2->addText('December 31, ' . $yearLast, ['bold' => true]);
            $p2->addText(';');
            
            $section->addText('3. and, is not included in the list of notoriously undesirable employees.', [], ['indentation' => ['left' => Converter::inchToTwip(0.5)], 'spaceAfter' => 120, 'lineHeight' => 1.15]);

            // Purpose sentence
            $section->addTextBreak(0.5);
            $p3 = $section->addTextRun('certificateBody');
            $p3->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
            $genderPrefix = $employee->gender_id == 2 ? 'Ms.' : ($employee->gender_id == 1 ? 'Mr.' : '');
            $lastName = $this->resolveEmployeeLastName($employee);
            $p3->addText('This certification is being issued upon the request of ' . $genderPrefix . ' ' . $lastName . ' ' . $purpose_text);

            // Signature Section
            $section->addTextBreak(2);
            
            if (file_exists($signaturePath)) {
                $signature = $section->addImage($signaturePath, [
                    'width' => Converter::cmToPoint(6),
                    'alignment' => 'right'
                ]);
            }
            
            $section->addText(strtoupper($request->signatory), ['bold' => true], ['alignment' => 'right', 'spaceAfter' => 0]);
            $section->addText($request->position, [], ['alignment' => 'right']);

            $filename = 'no_pending_certificate_' . $employee->id . '_' . date('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate no pending certificate DOCX: ' . $e->getMessage());
        }
    }

    /**
     * Download certificate as Excel (XLSX) using PhpSpreadsheet.
     */
    public function downloadExcel(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee' => 'required|exists:employees,id',
                'signatory' => 'required|string',
                'position' => 'required|string',
                'purpose_text' => 'nullable|string|max:1000',
            ], [
                'employee.required' => 'Please select employee.',
                'employee.exists' => 'Selected employee does not exist.',
                'signatory.required' => 'Signatory name is required.',
                'position.required' => 'Signatory position is required.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $purpose_text = $this->resolvePurposeText($request);

            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                RTRIM(a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key']))
                            END as last_name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig")
                )
                ->where('a.id', $request->employee)
                ->distinct()
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $employee = $employees->first();

            // Create new Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('No Pending Certificate');

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(50);

            // Header - Date
            $sheet->setCellValue('B1', Carbon::now()->format('d F Y'));
            $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('B1')->getFont()->setSize(12);

            // Title
            $sheet->setCellValue('B2', 'CERTIFICATION');
            $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B2')->getFont()->setUnderline(true);

            // Salutation
            $sheet->setCellValue('B4', 'To whom it may concern:');
            $sheet->getStyle('B4')->getFont()->setBold(true)->setSize(12);

            // Body - Employee intro
            $row = 6;
            $introText = 'This is to certify that ' . strtoupper($employee->name ?? '') . ' ' . ($employee->position ?? '') . ' of ' . CompanyHelper::getName() . ':';
            $sheet->setCellValue('B' . $row, $introText);
            $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);
            $sheet->getStyle('B' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('B' . $row)->getFont()->setSize(12);

            // Numbered list
            $row++;
            $sheet->setCellValue('B' . $row, '1. has no pending administrative and/or criminal case;');
            $sheet->getStyle('B' . $row)->getFont()->setSize(12);
            $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);

            $row++;
            $yearLast = date('Y', strtotime('-1 year'));
            $sheet->setCellValue('B' . $row, '2. has filed her Sworn Statement of Assets, Liabilities and Net worth as of December 31, ' . $yearLast . ';');
            $sheet->getStyle('B' . $row)->getFont()->setSize(12);
            $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);

            $row++;
            $sheet->setCellValue('B' . $row, '3. and, is not included in the list of notoriously undesirable employees.');
            $sheet->getStyle('B' . $row)->getFont()->setSize(12);
            $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);

            // Purpose sentence
            $row += 2;
            $genderPrefix = $employee->gender_id == 2 ? 'Ms.' : ($employee->gender_id == 1 ? 'Mr.' : '');
            $lastName = $this->resolveEmployeeLastName($employee);
            $purposeText = 'This certification is being issued upon the request of ' . $genderPrefix . ' ' . $lastName . ' ' . $purpose_text;
            $sheet->setCellValue('B' . $row, $purposeText);
            $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);
            $sheet->getStyle('B' . $row)->getFont()->setSize(12);

            // Signature Section
            $row += 4;
            $sheet->setCellValue('B' . $row, strtoupper($request->signatory));
            $sheet->getStyle('B' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $row++;
            $sheet->setCellValue('B' . $row, $request->position);
            $sheet->getStyle('B' . $row)->getFont()->setSize(12);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Set row heights for better readability
            for ($i = 1; $i <= $row; $i++) {
                $sheet->getRowDimension($i)->setRowHeight(-1);
            }
            $sheet->getRowDimension(6)->setRowHeight(40); // Employee intro row
            $sheet->getRowDimension($row - 4)->setRowHeight(30); // Purpose row

            $filename = 'no_pending_certificate_' . $employee->id . '_' . date('Ymd_His') . '.xlsx';
            $tempPath = storage_path('app/' . $filename);

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate no pending certificate Excel: ' . $e->getMessage());
        }
    }

    private function resolvePurposeText(Request $request): string
    {
        $text = trim((string) $request->input('purpose_text', ''));

        return $text !== '' ? $text : self::DEFAULT_PURPOSE_TEXT;
    }

    private function resolveEmployeeLastName(object $employee): string
    {
        $lastName = trim((string) ($employee->last_name ?? ''));
        if ($lastName !== '') {
            return $lastName;
        }

        $parts = preg_split('/\s+/', trim((string) ($employee->name ?? '')));
        $parts = array_values(array_filter($parts, fn ($part) => $part !== ''));

        return !empty($parts) ? (string) end($parts) : '';
    }
}
