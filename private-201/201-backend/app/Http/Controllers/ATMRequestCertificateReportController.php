<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \NumberFormatter;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;

class ATMRequestCertificateReportController extends Controller
{
    use ApiResponse;

    private const DEFAULT_RECIPIENT_NAME = 'MS. ESTRELITA S. GERONIMO';
    private const DEFAULT_SIGNATORY = 'NELLY NITA N. DILLERA, CESO III';
    private const DEFAULT_SIGNATORY_POSITION = 'Executive Director';

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

            return $this->successResponse($data, 'ATM request certificates retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve ATM request certificates: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validated = validator($request->all(), [
                'employee' => 'required|exists:employees,id',
                'recipient_name' => 'nullable|string|max:250',
                'salutation' => 'nullable|string|max:250',
                'signatory' => 'nullable|string|max:250',
                'position' => 'nullable|string|max:250',
            ]);

            if ($validated->fails()) {
                return $this->validationErrorResponse($validated->errors());
            }

            $recipient_name = $this->resolveRecipientName($request);
            $salutation = $this->resolveSalutation($request, $recipient_name);
            $signatories = [
                'signatory' => trim((string) $request->input('signatory', '')) ?: self::DEFAULT_SIGNATORY,
                'position' => trim((string) $request->input('position', '')) ?: self::DEFAULT_SIGNATORY_POSITION,
            ];

            $app_key = env("APP_KEY", "");
            $companies = DB::table('companies')->get();

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',COALESCE(a.middle_name,''),' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+COALESCE(RTRIM([dbo].[ufn_DecryptString](a.middle_name,'$app_key')),'')+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    'e.name as name_prefix'
                )
                ->where('a.id', $request->employee)
                ->distinct()
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

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

            // Load header image
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

            // Format employee name with prefix if available
            $employee = $employees[0];
            $prefix = $employee->name_prefix ? $employee->name_prefix . ' ' : '';
            $middleInitial = $employee->middle_name ? substr(trim($employee->middle_name), 0, 1) . '. ' : '';
            $employeeName = trim($prefix . $employee->first_name . ' ' . $middleInitial . $employee->last_name);
            $employeeName = preg_replace('/\s+/', ' ', $employeeName); // Remove extra spaces

            $pdf = PDF::loadView('atm_request_certificates.atm_request_certificate_print', compact(
                'employees',
                'employeeName',
                'incomes',
                'salary_word',
                'annual_salary',
                'signatories',
                'headerImg',
                'footerImg',
                'companies',
                'recipient_name',
                'salutation'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            
            // Generate PDF content as base64 for API response
            $pdfContent = $pdf->output();
            $base64Content = base64_encode($pdfContent);
            
            $filename = 'atm_request_certificate_' . $request->employee . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ATM request certificate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Download certificate as DOCX using PhpWord.
     */
    public function downloadDocx(Request $request)
    {
        try {
            $validated = validator($request->all(), [
                'employee' => 'required|exists:employees,id',
                'recipient_name' => 'nullable|string|max:250',
                'salutation' => 'nullable|string|max:250',
                'signatory' => 'nullable|string|max:250',
                'position' => 'nullable|string|max:250',
            ]);

            if ($validated->fails()) {
                return $this->validationErrorResponse($validated->errors());
            }

            $recipient_name = $this->resolveRecipientName($request);
            $salutation = $this->resolveSalutation($request, $recipient_name);
            $signatory = trim((string) $request->input('signatory', '')) ?: self::DEFAULT_SIGNATORY;
            $position = trim((string) $request->input('position', '')) ?: self::DEFAULT_SIGNATORY_POSITION;

            $app_key = env("APP_KEY", "");

            $headerPath = resource_path('img/report_header.jpg');
            $footerPath = resource_path('img/report_footer.jpg');

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',COALESCE(a.middle_name,''),' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+COALESCE(RTRIM([dbo].[ufn_DecryptString](a.middle_name,'$app_key')),'')+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    'e.name as name_prefix'
                )
                ->where('a.id', $request->employee)
                ->distinct()
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            // Format employee name with prefix if available
            $employee = $employees->first();
            $prefix = $employee->name_prefix ? $employee->name_prefix . ' ' : '';
            $middleInitial = $employee->middle_name ? substr(trim($employee->middle_name), 0, 1) . '. ' : '';
            $employeeName = trim($prefix . $employee->first_name . ' ' . $middleInitial . $employee->last_name);
            $employeeName = preg_replace('/\s+/', ' ', $employeeName); // Remove extra spaces

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

            $section->addTextBreak(1);

            // Date
            $section->addText(Carbon::now()->format('j F Y'), [], ['spaceAfter' => 120]);

            // Recipient Information
            $section->addText(strtoupper($recipient_name), ['bold' => true], ['spaceAfter' => 60]);
            $section->addText('Branch Manager', [], ['spaceAfter' => 60]);
            $section->addText('LANDBANK of the Philippines', [], ['spaceAfter' => 60]);
            $section->addText('PEZA Branch', [], ['spaceAfter' => 60]);
            $section->addText(CompanyHelper::getAddress(), [], ['spaceAfter' => 120]);

            // Salutation
            $section->addText($salutation, [], ['spaceAfter' => 120]);

            // Paragraph style with explicit first-line indent and minimized spacing
            $phpWord->addParagraphStyle('letterBody', [
                'indentation' => [
                    'firstLine' => Converter::inchToTwip(0.5),
                    'left' => 0,
                ],
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 120,
                'lineHeight' => 1.15,
            ]);

            // Letter Body - Paragraph 1
            $p1 = $section->addTextRun('letterBody');
            $p1->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
            $p1->addText('This is to request for the inclusion of ');
            $p1->addText($employeeName, ['bold' => true]);
            $p1->addText(', new employee of ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . '), in the ATM Payroll of the Center.');

            // Letter Body - Paragraph 2
            $p2 = $section->addTextRun('letterBody');
            $p2->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
            $p2->addText('Your immediate and favorable action on this request will be highly appreciated.');

            // Closing
            $section->addTextBreak(1.5);
            $section->addText('Very truly yours,', [], ['spaceAfter' => 200]);

            // Signature Section
            $section->addTextBreak(2);
            $section->addText(strtoupper($signatory), ['bold' => true], ['spaceAfter' => 0]);
            $section->addText($position, [], []);

            $filename = 'atm_request_certificate_' . $employee->id . '_' . date('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ATM request certificate DOCX: ' . $e->getMessage());
        }
    }

    private function resolveRecipientName(Request $request): string
    {
        $name = trim((string) $request->input('recipient_name', ''));

        return $name !== '' ? $name : self::DEFAULT_RECIPIENT_NAME;
    }

    private function resolveSalutation(Request $request, string $recipientName): string
    {
        $salutation = trim((string) $request->input('salutation', ''));
        if ($salutation !== '') {
            return $salutation;
        }

        return $this->buildSalutationFromRecipientName($recipientName);
    }

    private function buildSalutationFromRecipientName(string $recipientName): string
    {
        $parts = preg_split('/\s+/', trim($recipientName));
        $parts = array_values(array_filter($parts, fn ($part) => $part !== ''));

        if (empty($parts)) {
            return 'Dear Ms. Geronimo:';
        }

        $titleToken = strtoupper(rtrim($parts[0], '.'));
        $title = 'Ms.';
        if (str_starts_with($titleToken, 'MR')) {
            $title = 'Mr.';
        } elseif (str_starts_with($titleToken, 'MRS')) {
            $title = 'Mrs.';
        } elseif (str_starts_with($titleToken, 'MS')) {
            $title = 'Ms.';
        }

        $lastName = $parts[count($parts) - 1];
        $formattedLastName = ucfirst(strtolower($lastName));

        return 'Dear ' . $title . ' ' . $formattedLastName . ':';
    }
}
