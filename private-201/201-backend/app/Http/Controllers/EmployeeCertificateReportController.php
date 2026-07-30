<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use PDF;
use App\Traits\ApiResponse;
use App\Helpers\NumberToWords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;
use PhpOffice\PhpWord\SimpleType\Jc;

class EmployeeCertificateReportController extends Controller
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
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN employees.email ELSE dbo.ufn_DecryptString(employees.email,'$app_key') END as email"),
                    'employees.date_hired',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                ISNULL(employees.first_name, '') + ' ' + ISNULL(employees.last_name, '')
                            ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key')), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')), '')
                            END as name")
                )
                ->orderBy('employees.first_name', 'asc')
                ->where(['employees.is_employee' => true, 'employees.active' => true])
                ->get();

            return $this->successResponse($data, 'Employee certificates retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee certificates: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");
            $companies = DB::table('companies')->get();

            $validator = validator($request->all(), [
                'employee' => 'required|integer|exists:employees,id',
                'signatory' => 'required|string|min:1',
                'position' => 'required|string|min:1'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

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

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->leftJoin('salary_grades as sg', 'a.salary_grade_id', '=', 'sg.id')
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
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    'sg.name as salary_grade',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                ISNULL(e.name, '') + ' ' + ISNULL(a.last_name, '')
                            ELSE
                                ISNULL(RTRIM(e.name), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), '')
                            END as name_sig"),
                    DB::raw("CASE WHEN ISNULL(a.branch_id,0) <> 0 THEN
                                  CASE WHEN br.is_main_branch = 1 THEN
                                        CAST(1 as INT)
                                       ELSE
                                        CAST(0 as INT)
                                   END
                                  ELSE
                                   CAST(2 AS INT)
                             END AS from_branch")
                )
                ->where('a.id', $request->employee)
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $document_no = DB::table('document_numbers')->where('id', 3)->get();

            if ($document_no->isNotEmpty()) {
                if ($employees[0]->from_branch == 1) {
                    $footer = [
                        'document_no' => $document_no[0]->co_document_number,
                        'revision' => $document_no[0]->co_revision,
                    ];
                } elseif ($employees[0]->from_branch == 0) {
                    $footer = [
                        'document_no' => $document_no[0]->rd_document_number,
                        'revision' => $document_no[0]->rd_revision,
                    ];
                } else {
                    $footer = [
                        'document_no' => '',
                        'revision' => '',
                    ];
                }
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }

            // Use NumberFormatter if available, otherwise use our fallback
            if (class_exists('NumberFormatter')) {
                $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
                $salary_word = $f->format(($employees[0]->salary) * 12);
            } else {
                $salary_word = NumberToWords::formatCurrency(($employees[0]->salary) * 12);
            }
            $annual_salary = number_format(($employees[0]->salary) * 12);
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.employee_id', $request->employee)
                ->get();

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position
            );

            // Get purpose text or use default
            $genderPronoun = $employees[0]->gender_id == 1 ? 'her' : 'his';
            $defaultPurpose = "as a confirmation of {$genderPronoun} employment with the Center and as a requirement for {$genderPronoun} personal travel abroad";
            $purpose_text = $request->input('purpose_text', $defaultPurpose);

            // Select template: COS for employment_type_id = 2, else default template
            $view = ((int) $employees[0]->employment_type_id === 2)
                ? 'employee_certificates.employee_certificate_cos'
                : 'employee_certificates.employee_certificate_print';

            $pdf = PDF::loadView($view, compact(
                'employees',
                'incomes',
                'salary_word',
                'annual_salary',
                'signatories',
                'image',
                'companies',
                'footer',
                'headerImg',
                'footerImg',
                'purpose_text'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "employee_certificate_{$request->employee}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate employee certificate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Download certificate as DOCX using PhpWord.
     */
    public function downloadDocx(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $validator = validator($request->all(), [
                'employee' => 'required|integer|exists:employees,id',
                'signatory' => 'required|string|min:1',
                'position' => 'required|string|min:1'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $headerPath = resource_path('img/report_header.jpg');
            $footerPath = resource_path('img/report_footer.jpg');

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->leftJoin('salary_grades as sg', 'a.salary_grade_id', '=', 'sg.id')
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
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    'sg.name as salary_grade',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                ISNULL(e.name, '') + ' ' + ISNULL(a.last_name, '')
                            ELSE
                                ISNULL(RTRIM(e.name), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), '')
                            END as name_sig"),
                    DB::raw("CASE WHEN ISNULL(a.branch_id,0) <> 0 THEN
                                  CASE WHEN br.is_main_branch = 1 THEN
                                        CAST(1 as INT)
                                       ELSE
                                        CAST(0 as INT)
                                   END
                                  ELSE
                                   CAST(2 AS INT)
                             END AS from_branch")
                )
                ->where('a.id', $request->employee)
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $employee = $employees->first();

            // Purpose / pronouns
            $genderPronoun = $employee->gender_id == 1 ? 'her' : 'his';
            $genderSubject = $employee->gender_id == 1 ? 'She' : 'He';
            $defaultPurpose = "as a confirmation of {$genderPronoun} employment with the Center and as a requirement for {$genderPronoun} personal travel abroad";
            $purpose_text = $request->input('purpose_text', $defaultPurpose);

            // Issue date suffix (1st, 2nd, etc.)
            $issueDate = Carbon::now();
            $day = (int) $issueDate->format('j');
            $suffix = ($day == 1 || $day == 21 || $day == 31) ? 'st'
                : (($day == 2 || $day == 22) ? 'nd'
                : (($day == 3 || $day == 23) ? 'rd' : 'th'));

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
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

            $section->addTextBreak(1.5);

            // COS (employment_type_id = 2): match employee_certificate_cos.blade.php; salary bold in Word
            if ((int) $employee->employment_type_id === 2) {
                $phpWord->addParagraphStyle('cosCertificateBody', [
                    'indentation' => ['firstLine' => Converter::inchToTwip(0.5)],
                    'alignment' => WordJc::BOTH,
                    'spaceAfter' => 240,
                    'lineHeight' => 1.4,
                ]);

                $section->addText('C E R T I F I C A T I O N', ['bold' => true, 'allCaps' => true, 'size' => 13], ['alignment' => WordJc::CENTER, 'spaceAfter' => 100]);

                $cosP1 = $section->addTextRun('cosCertificateBody');
                $cosP1->addText(str_repeat(chr(160), 8));
                $cosP1->addText('This is to certify that ');
                $cosP1->addText(strtoupper($employee->name ?? ''), ['bold' => true]);
                $cosP1->addText(' has been hired as a Contract of Service (COS) by ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . '), an attached agency of the Department of Trade and Industry (DTI).');

                $cosP2 = $section->addTextRun('cosCertificateBody');
                $cosP2->addText(str_repeat(chr(160), 8));
                $cosP2->addText($genderSubject . ' currently holds the position of ');
                $cosP2->addText($employee->position ?? '', ['bold' => true]);
                $cosP2->addText(' under the ');
                $cosP2->addText($employee->department ?? '', ['bold' => true]);
                $cosP2->addText(' since ' . date('F d, Y', strtotime($employee->date_hired)) . ' up to present. ' . $genderSubject . ' remains engaged with the Center based on performance.');

                $salaryMonthly = (float) ($employee->salary ?? 0);
                $salaryWords = ucwords(strtolower(NumberToWords::formatCurrency($salaryMonthly)));
                $salaryFigures = 'P ' . number_format($salaryMonthly, 2);
                $cosP3 = $section->addTextRun('cosCertificateBody');
                $cosP3->addText(str_repeat(chr(160), 8));
                $cosP3->addText($genderSubject . ' receives a monthly Service Fee of ');
                $cosP3->addText($salaryWords . ' (' . $salaryFigures . ')', ['bold' => true]);
                $cosP3->addText('.');

                $cosP4 = $section->addTextRun('cosCertificateBody');
                $cosP4->addText(str_repeat(chr(160), 8));
                $cosP4->addText('This certification is being issued upon the request of ' . ($employee->name_sig ?? '') . ' ' . $purpose_text . '. The accuracy of this document may be verified by emailing the Human Resource Section at ' . CompanyHelper::getEmail() . '.');

                $cosP5 = $section->addTextRun('cosCertificateBody');
                $cosP5->addText(str_repeat(chr(160), 8));
                $cosP5->addText('Issued this ' . $day . $suffix . ' day of ' . $issueDate->format('F Y') . ' at Pasay City, Philippines.');

                $section->addTextBreak(2);
                $section->addText($request->signatory, ['bold' => true], ['alignment' => WordJc::END, 'spaceAfter' => 0]);
                $section->addText($request->position, [], ['alignment' => WordJc::END]);
            } else {
                $section->addText('CERTIFICATE OF EMPLOYMENT', ['bold' => true, 'underline' => 'single'], ['alignment' => 'center', 'spaceAfter' => 200]);
                $section->addTextBreak(1);

                // Paragraph style with explicit first-line indent
                $phpWord->addParagraphStyle('certificateBody', [
                    'indentation' => [
                        'firstLine' => Converter::inchToTwip(0.5),
                        'left' => 0,
                    ],
                    'alignment' => WordJc::BOTH,
                    'spaceAfter' => 240,
                    'lineHeight' => 1.4,
                ]);

                // Body paragraph 1
                $p1 = $section->addTextRun('certificateBody');
                $p1->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
                $p1->addText('This is to certify that ');
                $p1->addText(strtoupper($employee->name ?? ''), ['bold' => true]);
                $p1->addText(', is a ' . strtolower($employee->employment_type ?? '') . ' employee of the ');
                $p1->addText(CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ') - GMEA', ['bold' => true]);
                $p1->addText(', an attached agency of the ');
                $p1->addText('Department of Trade and Industry (DTI) ', ['bold' => true]);
                $p1->addText('since ' . date('F d, Y', strtotime($employee->date_hired)) . ', up to present. ' . $genderSubject . ' is currently holding the position of ');
                $p1->addText($employee->position ?? '', ['bold' => true]);
                if (!empty($employee->salary_grade)) {
                    $p1->addText(' with SG ' . $employee->salary_grade, ['bold' => true]);
                }
                $p1->addText(' under the ' . ($employee->department ?? '') . '.');

                // Body paragraph 2
                $p2 = $section->addTextRun('certificateBody');
                $p2->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
                $p2->addText('This certification is being issued upon the request of ' . ($employee->name_sig ?? '') . ' ' . $purpose_text . '. The accuracy of this document may verify by emailing the Human Resource Section at ' . CompanyHelper::getEmail() . '.');

                // Issue date
                $p3 = $section->addTextRun('certificateBody');
                $p3->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
                $p3->addText('Issued this ' . $day . $suffix . ' day of ' . $issueDate->format('F Y') . ' at Pasay City, Philippines.');

                // Signatory (right aligned)
                $section->addTextBreak(2);
                $section->addText($request->signatory, ['bold' => true], ['alignment' => 'right', 'spaceAfter' => 0]);
                $section->addText($request->position, [], ['alignment' => 'right']);
            }

            $filename = 'employee_certificate_' . $employee->id . '_' . date('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate employee certificate DOCX: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $employee = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    'a.email',
                    'a.date_hired',
                    'a.salary',
                    'a.gender_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                ISNULL(a.first_name, '') + ' ' + ISNULL(a.last_name, '')
                            ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), '')
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                ISNULL(e.name, '') + ' ' + ISNULL(a.last_name, '')
                            ELSE
                                ISNULL(RTRIM(e.name), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), '')
                            END as name_sig"),
                    DB::raw("CASE WHEN ISNULL(a.branch_id,0) <> 0 THEN
                                  CASE WHEN br.is_main_branch = 1 THEN
                                        CAST(1 as INT)
                                       ELSE
                                        CAST(0 as INT)
                                   END
                                  ELSE
                                   CAST(2 AS INT)
                             END AS from_branch")
                )
                ->where('a.id', $id)
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found');
            }

            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.employee_id', $id)
                ->get();

            // Use NumberFormatter if available, otherwise use our fallback
            if (class_exists('NumberFormatter')) {
                $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
                $salary_word = $f->format(($employee->salary) * 12);
            } else {
                $salary_word = NumberToWords::formatCurrency(($employee->salary) * 12);
            }
            $annual_salary = number_format(($employee->salary) * 12);

            return $this->successResponse([
                'employee' => $employee,
                'compensation_data' => [
                    'incomes' => $incomes
                ],
                'salary_details' => [
                    'monthly_salary' => $employee->salary,
                    'annual_salary' => $annual_salary,
                    'salary_in_words' => $salary_word
                ],
                'summary' => [
                    'total_incomes' => $incomes->sum('amount'),
                    'income_count' => $incomes->count()
                ]
            ], 'Employee certificate data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee certificate data: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                ISNULL(a.first_name, '') + ' ' + ISNULL(a.last_name, '')
                            ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), '')
                            END as name"),
                    'b.name as position',
                    'c.name as department'
                )
                ->where(['a.is_employee' => true, 'a.active' => true])
                ->orderBy('a.first_name', 'asc')
                ->get();

            return $this->successResponse([
                'employees' => $employees,
                'fields' => [
                    'employee' => ['type' => 'select', 'required' => true, 'label' => 'Employee'],
                    'signatory' => ['type' => 'text', 'required' => true, 'label' => 'Signatory Name'],
                    'position' => ['type' => 'text', 'required' => true, 'label' => 'Signatory Position']
                ]
            ], 'Create employee certificate form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }
}
