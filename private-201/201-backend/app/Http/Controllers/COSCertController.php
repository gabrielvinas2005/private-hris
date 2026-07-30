<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\NumberToWords;
use App\Traits\ApiResponse;
use PDF;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;

class COSCertController extends Controller
{
    use ApiResponse;

    public function employees()
    {
        try {
            $app_key = config('app.key');
            $hasKey = !empty($app_key);

            $employees = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->select(
                    'e.id',
                    'e.gender_id',
                    $hasKey
                        ? DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as full_name")
                        : DB::raw("CONCAT(e.first_name,' ',e.last_name) as full_name"),
                    'p.name as position'
                )
                ->where([
                    ['e.employment_type_id', '=', 2], // COS
                    ['e.is_employee', '=', true],
                    ['e.active', '=', true],
                ])
                ->orderBy('full_name', 'asc')
                ->get();

            return $this->successResponse($employees, 'COS employees loaded successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve COS employees: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee_id' => 'required|integer|exists:employees,id'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

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

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->select(
                    'a.id',
                    'a.employment_type_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                ISNULL(a.first_name, '') + ' ' + ISNULL(a.last_name, '')
                            ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), '')
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                ISNULL(e.name, '') + ' ' + ISNULL(a.last_name, '')
                            ELSE
                                ISNULL(RTRIM(e.name), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), '')
                            END as name_sig")
                )
                ->where('a.id', $request->employee_id)
                ->where('a.employment_type_id', 2) // Ensure it's a COS employee
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('COS employee not found');
            }

            // Use default signatories (can be configured later)
            $signatories = array(
                'signatory' => $request->input('signatory', 'Ma. Fe J. Avila'),
                'position' => $request->input('position', 'OIC-Executive Director')
            );

            // Get purpose text or use default
            $genderPronoun = $employees[0]->gender_id == 1 ? 'her' : 'his';
            $defaultPurpose = "as a confirmation of {$genderPronoun} engagement with the Center and as a requirement for {$genderPronoun} personal travel abroad";
            $purpose_text = $request->input('purpose_text', $defaultPurpose);

            $pdf = PDF::loadView('employee_certificates.employee_certificate_cos', compact(
                'employees',
                'signatories',
                'headerImg',
                'footerImg',
                'purpose_text'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "cos_certificate_{$request->employee_id}_" . date('Y-m-d') . ".pdf";

            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate COS certificate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Download COS certificate as DOCX using PhpWord.
     */
    public function downloadDocx(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee_id' => 'required|integer|exists:employees,id'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $headerPath = resource_path('img/report_header.jpg');
            $footerPath = resource_path('img/report_footer.jpg');

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->select(
                    'a.id',
                    'a.employment_type_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                ISNULL(a.first_name, '') + ' ' + ISNULL(a.last_name, '')
                            ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), '')
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                ISNULL(e.name, '') + ' ' + ISNULL(a.last_name, '')
                            ELSE
                                ISNULL(RTRIM(e.name), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), '')
                            END as name_sig")
                )
                ->where('a.id', $request->employee_id)
                ->where('a.employment_type_id', 2) // Ensure it's a COS employee
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('COS employee not found');
            }

            // Use default signatories (can be configured later)
            $signatories = array(
                'signatory' => $request->input('signatory', 'Ma. Fe J. Avila'),
                'position' => $request->input('position', 'OIC-Executive Director')
            );

            // Purpose text / pronouns
            $employee = $employees->first();
            $genderPronoun = $employee->gender_id == 1 ? 'her' : 'his';
            $genderSubject = $employee->gender_id == 1 ? 'She' : 'He';
            $defaultPurpose = "as a confirmation of {$genderPronoun} engagement with the Center and as a requirement for {$genderPronoun} personal travel abroad";
            $purpose_text = $request->input('purpose_text', $defaultPurpose);

            // Date suffix
            $issueDate = Carbon::now();
            $day = (int) $issueDate->format('j');
            $suffix = ($day == 1 || $day == 21 || $day == 31) ? 'st'
                : (($day == 2 || $day == 22) ? 'nd'
                : (($day == 3 || $day == 23) ? 'rd' : 'th'));

            // Build DOCX
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(12);

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(1),
                'marginRight' => Converter::inchToTwip(0.75),
                'marginBottom' => Converter::inchToTwip(1),
                'marginLeft' => Converter::inchToTwip(1),
                'pageSizeW' => Converter::inchToTwip(8.27),
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

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

            $phpWord->addParagraphStyle('cosCertificateBody', [
                'indentation' => ['firstLine' => Converter::inchToTwip(0.5)],
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 240,
                'lineHeight' => 1.4,
            ]);

            $section->addTextBreak(1.5);
            $section->addText('C E R T I F I C A T I O N', ['bold' => true, 'allCaps' => true, 'size' => 13], ['alignment' => WordJc::CENTER, 'spaceAfter' => 100]);

            // Paragraph 1
            $p1 = $section->addTextRun('cosCertificateBody');
            $p1->addText(str_repeat(chr(160), 8));
            $p1->addText('This is to certify that ');
            $p1->addText(strtoupper($employee->name ?? ''), ['bold' => true]);
            $p1->addText(' has been hired as a Contract of Service (COS) by ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . '), an attached agency of the Department of Trade and Industry (DTI).');

            // Paragraph 2
            $p2 = $section->addTextRun('cosCertificateBody');
            $p2->addText(str_repeat(chr(160), 8));
            $p2->addText($genderSubject . ' currently holds the position of ');
            $p2->addText($employee->position ?? '', ['bold' => true]);
            $p2->addText(' under the ');
            $p2->addText($employee->department ?? '', ['bold' => true]);
            $p2->addText(' since ' . date('F d, Y', strtotime($employee->date_hired)) . ' up to present. ' . $genderSubject . ' remains engaged with the Center based on performance.');

            // Paragraph 3
            $p3 = $section->addTextRun('cosCertificateBody');
            $p3->addText(str_repeat(chr(160), 8));
                $salaryMonthly = (float) ($employee->salary ?? 0);
                $salaryWords = ucwords(strtolower(NumberToWords::formatCurrency($salaryMonthly)));
                $salaryFigures = 'P ' . number_format($salaryMonthly, 2);
                $p3->addText($genderSubject . ' receives a monthly Service Fee of ');
                $p3->addText($salaryWords . ' (' . $salaryFigures . ')', ['bold' => true]);
                $p3->addText('.');

            // Paragraph 4
            $p4 = $section->addTextRun('cosCertificateBody');
            $p4->addText(str_repeat(chr(160), 8));
            $p4->addText('This certification is being issued upon the request of ' . ($employee->name_sig ?? '') . ' ' . $purpose_text . '. The accuracy of this document may be verified by emailing the Human Resource Section at CompanyHelper::getEmail().');

            // Issue date
            $p5 = $section->addTextRun('cosCertificateBody');
            $p5->addText(str_repeat(chr(160), 8));
            $p5->addText('Issued this ' . $day . $suffix . ' day of ' . $issueDate->format('F Y') . ' at ' . CompanyHelper::getAddress() . '.');

            // Signatory
            $section->addTextBreak(2);
            $section->addText($signatories['signatory'] ?? '', ['bold' => true], ['alignment' => WordJc::END, 'spaceAfter' => 0]);
            $section->addText($signatories['position'] ?? '', [], ['alignment' => WordJc::END]);

            $filename = "cos_certificate_{$request->employee_id}_" . date('Ymd_His') . ".docx";
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate COS certificate DOCX: ' . $e->getMessage());
        }
    }
}
