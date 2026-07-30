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

class OffboardingCertificateReportController extends Controller
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
                ->join('employee_offboardings', 'employee_offboardings.employee_id', '=', 'employees.id')
                ->join('offboarding_natures', 'offboarding_natures.id', '=', 'employee_offboardings.nature_id')
                ->select(
                    'employees.id',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                            END as name"),
                    'offboarding_natures.name as nature',
                    'employee_offboardings.id as offboard_id',
                    'employee_offboardings.date_effectivity as effectivity'
                )
                ->orderBy('name', 'asc')
                ->where('employee_offboardings.reactivated_status_id', 0)
                ->distinct()
                ->get();

            return $this->successResponse($data, 'Offboarding certificates retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve offboarding certificates: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee' => 'required|exists:employee_offboardings,id',
                'signatory' => 'nullable|string',
                'position' => 'nullable|string'
            ], [
                'employee.required' => 'Please select employee.',
                'employee.exists' => 'Selected employee does not exist.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $companies = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->join('employee_offboardings as f', 'f.employee_id', '=', 'a.id')
                ->join('offboarding_natures as g', 'g.id', '=', 'f.nature_id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->select(
                    'f.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    'f.date_effectivity as effectivity',
                    'f.retirement_date',
                    DB::raw("CASE WHEN ISNULL(a.branch_id,0) <> 0 THEN
                                  CASE WHEN br.is_main_branch = 1 THEN
                                        CAST(1 as INT)
                                       ELSE
                                        CAST(0 as INT)
                                   END
                                  ELSE
                                   CAST(2 AS INT)
                             END AS from_branch"),
                    DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig")
                )
                ->where('f.id', $request->employee)
                ->distinct()
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $document_no = DB::table('document_numbers')->where('id', 9)->get();
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
                    $employees[0]->id
                )
                ->get();

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position
            );

            $pdf = PDF::loadView('offboarding_certificates.offboarding_certificate_print', compact(
                'employees',
                'incomes',
                'salary_word',
                'annual_salary',
                'signatories',
                'image',
                'companies',
                'footer'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'offboarding_certificate_' . $employees[0]->name . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate offboarding certificate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Download offboarding certificate as DOCX using PhpWord.
     */
    public function word(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee' => 'required|exists:employee_offboardings,id',
                'signatory' => 'nullable|string',
                'position' => 'nullable|string'
            ], [
                'employee.required' => 'Please select employee.',
                'employee.exists' => 'Selected employee does not exist.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $companies = DB::table('companies')->get();

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->join('employee_offboardings as f', 'f.employee_id', '=', 'a.id')
                ->join('offboarding_natures as g', 'g.id', '=', 'f.nature_id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->select(
                    'f.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    'f.date_effectivity as effectivity',
                    'f.retirement_date',
                    DB::raw("CASE WHEN ISNULL(a.branch_id,0) <> 0 THEN
                                  CASE WHEN br.is_main_branch = 1 THEN
                                        CAST(1 as INT)
                                       ELSE
                                        CAST(0 as INT)
                                   END
                                  ELSE
                                   CAST(2 AS INT)
                             END AS from_branch"),
                    DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig")
                )
                ->where('f.id', $request->employee)
                ->distinct()
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $employee = $employees->first();

            $document_no = DB::table('document_numbers')->where('id', 9)->get();
            if ($document_no->isNotEmpty()) {
                if ($employee->from_branch == 1) {
                    $footer = [
                        'document_no' => $document_no[0]->co_document_number,
                        'revision' => $document_no[0]->co_revision,
                    ];
                } elseif ($employee->from_branch == 0) {
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

            $signatory = $request->signatory ?? '';
            $position = $request->position ?? '';

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(18);

            // Create a section for each employee (though typically only one)
            foreach ($employees as $emp) {
                $section = $phpWord->addSection([
                    'marginTop' => Converter::inchToTwip(0.6),
                    'marginRight' => Converter::inchToTwip(1),
                    'marginBottom' => Converter::inchToTwip(0.6),
                    'marginLeft' => Converter::inchToTwip(1),
                    'pageSizeW' => Converter::inchToTwip(8.27),
                    'pageSizeH' => Converter::inchToTwip(11.69),
                ]);

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
                $section->addTextBreak(6);
                $section->addText('C E R T I F I C A T I O N', ['bold' => true], ['alignment' => 'center', 'spaceAfter' => 120]);
                $section->addTextBreak(2);

                // TO WHOM IT MAY CONCERN
                $section->addText('TO WHOM IT MAY CONCERN:', [], ['spaceAfter' => 120]);
                $section->addTextBreak(0.5);

                // First paragraph with manual indentation
                $p1 = $section->addTextRun('certBody');
                $p1->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
                $p1->addText('This is to certify that according to our records, ');
                $p1->addText($emp->name, ['bold' => true]);
                $p1->addText(', ');
                $p1->addText($emp->position ?? '', ['bold' => true]);
                $p1->addText(' (');
                $p1->addText($emp->department ?? '', ['bold' => true]);
                $p1->addText(') has been in the service since ');
                $dateHired = $emp->date_hired ? Carbon::parse($emp->date_hired)->format('M d, Y') : 'N/A';
                $p1->addText($dateHired);
                $p1->addText('. ');

                $genderText = $emp->gender_id == 1 ? 'Her' : 'His';
                $p1->addText($genderText);
                $p1->addText(' last day of service was on ');

                $effectivityDate = $emp->effectivity ? Carbon::parse($emp->effectivity)->format('M d, Y') : 'N/A';
                $p1->addText($effectivityDate);

                if ($emp->retirement_date) {
                    $p1->addText(' and compulsorily retired on ');
                    $retirementDate = Carbon::parse($emp->retirement_date)->format('M d, Y');
                    $p1->addText($retirementDate);
                    $p1->addText('.');
                } else {
                    $p1->addText('.');
                }

                $section->addTextBreak(0.5);

                // Second paragraph with manual indentation
                $p2 = $section->addTextRun('certBody');
                $p2->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
                $companyAddress = $companies[0]->address ?? 'CompanyHelper::getAddress()';
                $p2->addText($companyAddress);
                $p2->addText(', this ');
                $p2->addText(Carbon::now()->format('d'));
                $p2->addText(' day ');
                $p2->addText(Carbon::now()->format('F, Y'));
                $p2->addText('.');

                $section->addTextBreak(1);

                // Signatory (right aligned)
                if ($signatory) {
                    $section->addText($signatory, ['bold' => true], ['alignment' => 'right', 'spaceAfter' => 0]);
                }
                if ($position) {
                    $section->addText($position, [], ['alignment' => 'right']);
                }

                // Add footer document number and revision
                $section->addTextBreak(2);
                if (!empty($footer['document_no'])) {
                    $section->addText($footer['document_no'], ['bold' => true], ['alignment' => 'right', 'spaceAfter' => 0]);
                }
                if (!empty($footer['revision'])) {
                    $section->addText($footer['revision'], ['bold' => true], ['alignment' => 'right']);
                }
            }

            $filename = 'offboarding_certificate_' . $employee->name . '_' . date('Y-m-d') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate offboarding certificate DOCX: ' . $e->getMessage());
        }
    }
}
