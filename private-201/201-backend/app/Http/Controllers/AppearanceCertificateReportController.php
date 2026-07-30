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

class AppearanceCertificateReportController extends Controller
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
                            CONCAT(employees.first_name,' ',employees.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                        END as name")
            )
            ->orderBy('employees.first_name', 'asc')
            ->where(['employees.is_employee' => true, 'employees.active' => true])
            ->get();

        return $this->successResponse($data, 'Appearance certificates retrieved successfully');
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.', 400);
            }

            // $image = base64_encode(file_get_contents(public_path('/dist/img/reports/header_img.jpg')));
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

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
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(e.name,' ',a.last_name)
                            ELSE
                                RTRIM(e.name)+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name_sig")
                )
                ->where('a.id', $request->employee)
                ->get();

            if ($employees->isEmpty()) {
                return $this->errorResponse('Employee not found.', 404);
            }

            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
            $salary_word = $f->format(($employees[0]->salary) * 12);
            $annual_salary = number_format(($employees[0]->salary) * 12);
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

            $pdf = PDF::loadView('appearance_certificates.appearance_certificate_print', compact('employees', 'incomes', 'salary_word', 'annual_salary', 'signatories', 'image'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'appearance_certificate_' . $request->employee . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate appearance certificate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Download appearance certificate as DOCX using PhpWord.
     */
    public function word(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.', 400);
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
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(e.name,' ',a.last_name)
                            ELSE
                                RTRIM(e.name)+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name_sig")
                )
                ->where('a.id', $request->employee)
                ->get();

            if ($employees->isEmpty()) {
                return $this->errorResponse('Employee not found.', 404);
            }

            $employee = $employees->first();
            $signatory = $request->signatory ?? '';
            $position = $request->position ?? '';

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(13);

            // Create a section for each employee (though typically only one)
            foreach ($employees as $emp) {
                $section = $phpWord->addSection([
                    'marginTop' => Converter::inchToTwip(0.6),
                    'marginRight' => Converter::inchToTwip(1.5),
                    'marginBottom' => Converter::inchToTwip(0.6),
                    'marginLeft' => Converter::inchToTwip(1.5),
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
                $section->addText('C E R T I F I C A T I O N', ['bold' => true, 'underline' => 'single'], ['alignment' => 'center', 'spaceAfter' => 120]);
                $section->addTextBreak(2);

                // TO WHOM IT MAY CONCERN
                $section->addText('TO WHOM IT MAY CONCERN:', [], ['spaceAfter' => 120]);
                $section->addTextBreak(0.5);

                // First paragraph with manual indentation
                $p1 = $section->addTextRun('certBody');
                $p1->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
                $p1->addText('This is to certify that ');
                $p1->addText($emp->name, ['underline' => 'single']);
                $p1->addText(', ');
                $p1->addText($emp->position ?? '');
                $p1->addText(' appeared in this Authority on ');
                $dateHired = $emp->date_hired ? Carbon::parse($emp->date_hired)->format('F d, Y') : 'N/A';
                $p1->addText($dateHired);
                $p1->addText('.');

                $section->addTextBreak(0.5);

                // Second paragraph with manual indentation
                $p2 = $section->addTextRun('certBody');
                $p2->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
                $p2->addText('Issued upon the request of ');
                $p2->addText($emp->name_sig ?? $emp->name, ['underline' => 'single']);
                $p2->addText(' for whatever legal purpose it may serve ');
                $genderPronoun = $emp->gender_id == 1 ? 'her' : 'him';
                $p2->addText($genderPronoun);
                $p2->addText('.');

                $section->addTextBreak(0.5);

                // Third paragraph with manual indentation
                $p3 = $section->addTextRun('certBody');
                $p3->addText(str_repeat(chr(160), 10)); // manual first-line indent fallback
                $p3->addText(CompanyHelper::getAddress(), this );
                $p3->addText(Carbon::now()->format('d'));
                $p3->addText(' day of ');
                $p3->addText(Carbon::now()->format('F'));
                $p3->addText(', ');
                $p3->addText(Carbon::now()->format('Y'));
                $p3->addText('.');

                $section->addTextBreak(1);

                // Signatory (right aligned)
                if ($signatory) {
                    $section->addText($signatory, [], ['alignment' => 'right', 'spaceAfter' => 0]);
                }
                if ($position) {
                    $section->addText($position, [], ['alignment' => 'right']);
                }
            }

            $filename = 'appearance_certificate_' . $request->employee . '_' . date('Y-m-d') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate appearance certificate DOCX: ' . $e->getMessage());
        }
    }
}
