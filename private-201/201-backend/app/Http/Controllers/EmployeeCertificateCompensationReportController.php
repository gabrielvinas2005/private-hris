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

class EmployeeCertificateCompensationReportController extends Controller
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
                    'employees.email',
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

            return $this->successResponse($data, 'Employee certificate compensation records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee certificate compensation records: ' . $e->getMessage());
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

            $image = null;
            $logoPath = public_path('/dist/img/logo.png');
            if (file_exists($logoPath)) {
                $image = base64_encode(file_get_contents($logoPath));
            }

            $header_img = resource_path('img/report_header.jpg');
            $footer_img = resource_path('img/report_footer.jpg');
            $headerPath = resource_path('img/report_header.jpg');
            $footerPath = resource_path('img/report_footer.jpg');
            if (file_exists($headerPath)) {
                $header_img = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($headerPath));
            }
            if (file_exists($footerPath)) {
                $footer_img = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($footerPath));
            }

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('salary_grades as sg', 'a.salary_grade_id', '=', 'sg.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                ISNULL(a.first_name, '') + ' ' + ISNULL(a.last_name, '')
                            ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), '')
                            END as name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    'e.name as name_prefix',
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'sg.name as salary_grade',
                    'a.salary',
                    DB::raw("isnull(a.salary,0) * 12 as annual_salary"),
                    'a.date_hired',
                    'a.gender_id',
                    DB::raw("ISNULL(e.name, '') + ' ' + ISNULL(a.last_name, '') as name_sig"),
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

            $document_no = DB::table('document_numbers')->where('id', 4)->get();

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
            $annual_salary = ($employees[0]->annual_salary);
            $clothing_allowance = DB::table('uniform_clothing_details');
            $midyear_period = DB::table('midyear_bonus')->max('years');
            $yearend_period = DB::table('yearend_bonus')->max('years');

            $midyear_bonus = DB::table('midyear_bonus as a')
                ->select(
                    'a.employee_id',
                    DB::raw("CAST('14th Month Pay' as varchar(255)) as item"),
                    'a.bonus_amount as amount'
                )
                ->where([
                    'a.employee_id' => $request->employee,
                    'a.years' => $midyear_period
                ])->get();

            if (count($midyear_bonus) > 0) {
                $midyear_bonus1 = DB::table('midyear_bonus as a')
                    ->select(
                        'a.employee_id',
                        DB::raw("CAST('14th Month Pay' as varchar(255)) as item"),
                        'a.bonus_amount as amount'
                    )
                    ->where([
                        'a.employee_id' => $request->employee,
                        'a.years' => $midyear_period
                    ])->where('a.bonus_amount', '>', 0);
            } else {
                $midyear_bonus1 = DB::raw("
                    SELECT
                    0 AS employee_id,
                    '' AS item,
                    0 AS amount
                ");
            }

            $yearend_bonus = DB::table('yearend_bonus as a')
                ->select(
                    'a.employee_id',
                    DB::raw("CAST('13th Month Pay' as varchar(255)) as item"),
                    'a.bonus_amount as amount'
                )
                ->where([
                    'a.employee_id' => $request->employee,
                    'a.years' => $yearend_period
                ])->get();

            $cash_gift = DB::table('yearend_bonus as a')
                ->select(
                    'a.employee_id',
                    DB::raw("CAST('Cash Gift' as varchar(255)) as item"),
                    'a.cash_gift_amount as amount'
                )
                ->where([
                    'a.employee_id' => $request->employee,
                    'a.years' => $yearend_period
                ])->get();

            if (count($yearend_bonus) > 0) {
                $yearend_bonus1 = DB::table('yearend_bonus as a')
                    ->select(
                        'a.employee_id',
                        DB::raw("CAST('13th Month Pay' as varchar(255)) as item"),
                        'a.bonus_amount as amount'
                    )
                    ->where([
                        'a.employee_id' => $request->employee,
                        'a.years' => $yearend_period
                    ])->where('a.bonus_amount', '>', 0);
            } else {
                $yearend_bonus1 = DB::raw("
                    SELECT
                    0 AS employee_id,
                    '' AS item,
                    0 AS amount
                ");
            }

            if (count($cash_gift) > 0) {
                $cash_gift1 = DB::table('yearend_bonus as a')
                    ->select(
                        'a.employee_id',
                        DB::raw("CAST('Cash Gift' as varchar(255)) as item"),
                        'a.cash_gift_amount as amount'
                    )
                    ->where([
                        'a.employee_id' => $request->employee,
                        'a.years' => $yearend_period
                    ])->where('a.cash_gift_amount', '>', 0);
            } else {
                $cash_gift1 = DB::raw("
                    SELECT
                    0 AS employee_id,
                    '' AS item,
                    0 AS amount
                ");
            }

            $payroll_period_id = DB::table('payroll_incomes')->max('payroll_period_id');

            $compensation_details = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name as type',
                    'a.amount',
                    DB::raw("'Monthly' as period")
                )
                ->where([
                    'a.employee_id' => $request->employee,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->where('a.amount', '>', 0)
                ->get();

            $incomes1 = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name as item',
                    'a.amount'
                )
                ->where([
                    'a.employee_id' => $request->employee,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->where('a.amount', '>', 0);

            // materialize incomes for the blade template that expects `$incomes`
            $incomes = $incomes1->get();

            if (!$midyear_bonus && !$yearend_bonus && !$cash_gift) {
                $bonus = $midyear_bonus1
                    ->union($yearend_bonus1)
                    ->union($cash_gift1)
                    ->get();

                $bonus1 = $midyear_bonus1
                    ->union($yearend_bonus1)
                    ->union($cash_gift1)
                    ->union($incomes1);
                $total_bonus = DB::query()->fromSub($bonus1, 'a');
                $total_bonus1 = $total_bonus
                    ->select(
                        'a.employee_id',
                        DB::raw("SUM(a.amount) as total"),
                    )
                    ->groupBy(
                        'a.employee_id'
                    )
                    ->get();
            } else {
                $bonus = [];
                $total_bonus1 = [];
            }

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position
            );

            $pdf = PDF::loadView('employee_certificates.employee_certificate_compensation_print', compact(
                'employees',
                'compensation_details',
                'salary_word',
                'annual_salary',
                'signatories',
                'image',
                'header_img',
                'footer_img',
                'bonus',
                'total_bonus1',
                'incomes',
                'companies',
                'footer'
            ))->setOptions(['defaultFont' => 'helvetica']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "compensation_certificate_{$request->employee}_" . date('Y-m-d') . ".pdf";

            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate employee compensation certificate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Download compensation certificate as DOCX using PhpWord.
     */
    public function downloadDocx(Request $request)
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

            // Reuse same data fetching logic as print method
            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                ISNULL(a.first_name, '') + ' ' + ISNULL(a.last_name, '')
                            ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), '')
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    DB::raw("isnull(a.salary,0) * 12 as annual_salary"),
                    'a.date_hired',
                    'a.gender_id',
                    DB::raw("ISNULL(e.name, '') + ' ' + ISNULL(a.last_name, '') as name_sig"),
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

            $document_no = DB::table('document_numbers')->where('id', 4)->get();
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
                    $footer = ['document_no' => '', 'revision' => ''];
                }
            } else {
                $footer = ['document_no' => '', 'revision' => ''];
            }

            $employee = $employees->first();
            $annual_salary = ($employee->annual_salary);

            // Get bonus and income data (same as print method)
            $midyear_period = DB::table('midyear_bonus')->max('years');
            $yearend_period = DB::table('yearend_bonus')->max('years');
            $payroll_period_id = DB::table('payroll_incomes')->max('payroll_period_id');

            $midyear_bonus = DB::table('midyear_bonus as a')
                ->select('a.employee_id', DB::raw("CAST('14th Month Pay' as varchar(255)) as item"), 'a.bonus_amount as amount')
                ->where(['a.employee_id' => $request->employee, 'a.years' => $midyear_period])
                ->get();

            $yearend_bonus = DB::table('yearend_bonus as a')
                ->select('a.employee_id', DB::raw("CAST('13th Month Pay' as varchar(255)) as item"), 'a.bonus_amount as amount')
                ->where(['a.employee_id' => $request->employee, 'a.years' => $yearend_period])
                ->get();

            $cash_gift = DB::table('yearend_bonus as a')
                ->select('a.employee_id', DB::raw("CAST('Cash Gift' as varchar(255)) as item"), 'a.cash_gift_amount as amount')
                ->where(['a.employee_id' => $request->employee, 'a.years' => $yearend_period])
                ->get();

            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select('a.employee_id', 'b.name as item', 'a.amount')
                ->where(['a.employee_id' => $request->employee, 'a.payroll_period_id' => $payroll_period_id])
                ->where('a.amount', '>', 0)
                ->get();

            // Combine bonuses
            $bonus = collect();
            if ($midyear_bonus->isNotEmpty()) $bonus = $bonus->merge($midyear_bonus);
            if ($yearend_bonus->isNotEmpty()) $bonus = $bonus->merge($yearend_bonus);
            if ($cash_gift->isNotEmpty()) $bonus = $bonus->merge($cash_gift);

            // Calculate totals
            $total_income = $annual_salary;
            foreach ($incomes as $income) {
                if ($income->employee_id == $employee->id) {
                    $total_income += $income->amount;
                }
            }
            foreach ($bonus as $b) {
                if ($b->employee_id == $employee->id) {
                    $total_income += $b->amount;
                }
            }

            // Build DOCX
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(12);

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(1.5),
                'marginRight' => Converter::inchToTwip(1),
                'marginBottom' => Converter::inchToTwip(1),
                'marginLeft' => Converter::inchToTwip(1),
                'pageSizeW' => Converter::inchToTwip(8.27),
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

            // Title
            $section->addText('CERTIFICATION', ['bold' => true, 'size' => 18, 'name' => 'Times New Roman'], ['alignment' => WordJc::CENTER, 'spaceAfter' => 240]);

            // TO WHOM IT MAY CONCERN
            $section->addText('TO WHOM IT MAY CONCERN:', ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'], ['spaceAfter' => 120]);
            $section->addTextBreak(1);

            // Paragraph style with first-line indent
            $phpWord->addParagraphStyle('compensationBody', [
                'indentation' => ['firstLine' => Converter::inchToTwip(0.5)],
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 162,
                'lineHeight' => 1.4,
            ]);

            // First paragraph
            $p1 = $section->addTextRun('compensationBody');
            $p1->addText(str_repeat(chr(160), 10)); // manual first-line indent
            $p1->addText('This is to certify that according to our records, ');
            $p1->addText($employee->name ?? '', ['bold' => true]);
            $p1->addText(' is an employee of the ');
            $p1->addText($companies->isNotEmpty() ? $companies[0]->name : 'strtoupper(CompanyHelper::getName())', ['bold' => true]);
            $p1->addText('. ');
            $p1->addText($employee->gender_id == 1 ? 'She' : 'He');
            $p1->addText(' is presently holding the position of ');
            $p1->addText($employee->position ?? '', ['bold' => true]);
            $p1->addText(' and has been with this authority since ');
            $p1->addText(date('M d, Y', strtotime($employee->date_hired)));
            $p1->addText('.');

            $section->addTextBreak(1);

            // Salary table heading
            $genderPronoun = $employee->gender_id == 1 ? 'Her' : 'His';
            $section->addText($genderPronoun . ' annual salary and allowances are as follows:', ['size' => 12, 'name' => 'Times New Roman'], ['spaceAfter' => 120]);

            // Compensation table
            $table = $section->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'alignment' => WordJc::START,
            ]);

            // Salary per annum row
            $table->addRow();
            $cell1 = $table->addCell(Converter::inchToTwip(3.5));
            $cell2 = $table->addCell(Converter::inchToTwip(2), ['alignment' => WordJc::END]);
            $cell1->addText('Salary per annum', ['size' => 12, 'name' => 'Times New Roman']);
            $cell2->addText(number_format($annual_salary, 2, '.', ','), ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'], ['alignment' => WordJc::END]);

            // Incomes
            foreach ($incomes as $income) {
                if ($income->employee_id == $employee->id) {
                    $table->addRow();
                    $cell1 = $table->addCell(Converter::inchToTwip(3.5));
                    $cell2 = $table->addCell(Converter::inchToTwip(2), ['alignment' => WordJc::END]);
                    $cell1->addText($income->item, ['size' => 12, 'name' => 'Times New Roman']);
                    $cell2->addText(number_format($income->amount, 2, '.', ','), ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'], ['alignment' => WordJc::END]);
                }
            }

            // Bonuses
            foreach ($bonus as $b) {
                if ($b->employee_id == $employee->id) {
                    $table->addRow();
                    $cell1 = $table->addCell(Converter::inchToTwip(3.5));
                    $cell2 = $table->addCell(Converter::inchToTwip(2), ['alignment' => WordJc::END]);
                    $cell1->addText($b->item, ['size' => 12, 'name' => 'Times New Roman']);
                    $cell2->addText(number_format($b->amount, 2, '.', ','), ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'], ['alignment' => WordJc::END]);
                }
            }

            // Total row with border
            $table->addRow();
            $cell1 = $table->addCell(Converter::inchToTwip(3.5), ['borderTopSize' => 12, 'borderTopColor' => '000000']);
            $cell2 = $table->addCell(Converter::inchToTwip(2), ['borderTopSize' => 12, 'borderTopColor' => '000000', 'alignment' => WordJc::END]);
            $cell1->addText('Total', ['bold' => true, 'size' => 12, 'name' => 'Times New Roman']);
            $cell2->addText(number_format($total_income, 2, '.', ','), ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'], ['alignment' => WordJc::END]);

            $section->addTextBreak(1);

            // Second paragraph
            $p2 = $section->addTextRun('compensationBody');
            $p2->addText(str_repeat(chr(160), 10)); // manual first-line indent
            $p2->addText('This certification is issued upon request of ');
            $p2->addText($employee->name ?? '', ['bold' => true]);
            $p2->addText(' for whatever legal purpose it may serve ');
            $p2->addText($employee->gender_id == 1 ? 'her' : 'him');
            $p2->addText('.');

            $section->addTextBreak(1);

            // Third paragraph
            $p3 = $section->addTextRun('compensationBody');
            $p3->addText(str_repeat(chr(160), 10)); // manual first-line indent
            $companyAddress = $companies->isNotEmpty() && $companies[0]->address
                ? $companies[0]->address
                : 'CompanyHelper::getAddress()';
            $p3->addText($companyAddress . ', ');
            $p3->addText(date('d', strtotime(now())));
            $p3->addText(' of ');
            $p3->addText(date('M Y', strtotime(now())));
            $p3->addText('.');

            // Signatory (right aligned)
            $section->addTextBreak(2);
            $section->addText($request->signatory, ['size' => 12, 'name' => 'Times New Roman'], ['alignment' => WordJc::END, 'spaceAfter' => 0]);
            $section->addText($request->position, ['size' => 12, 'name' => 'Times New Roman'], ['alignment' => WordJc::END]);

            // Footer (document number and revision)
            if (!empty($footer['document_no']) || !empty($footer['revision'])) {
                $section->addTextBreak(3);
                if (!empty($footer['document_no'])) {
                    $section->addText($footer['document_no'], ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'], ['alignment' => WordJc::END]);
                }
                if (!empty($footer['revision'])) {
                    $section->addText($footer['revision'], ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'], ['alignment' => WordJc::END]);
                }
            }

            $filename = 'compensation_certificate_' . $employee->id . '_' . date('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate compensation certificate DOCX: ' . $e->getMessage());
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
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), '') + ' ' + ISNULL(RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), '')
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    DB::raw("ISNULL(e.name, '') + ' ' + ISNULL(a.last_name, '') as name_sig"),
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

            $payroll_period_id = DB::table('payroll_incomes')->max('payroll_period_id');
            $midyear_period = DB::table('midyear_bonus')->max('years');
            $yearend_period = DB::table('yearend_bonus')->max('years');

            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name as item',
                    'a.amount'
                )
                ->where([
                    'a.employee_id' => $id,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->where('a.amount', '>', 0)
                ->get();

            $midyear_bonus = DB::table('midyear_bonus as a')
                ->select(
                    'a.employee_id',
                    DB::raw("CAST('14th Month Pay' as varchar(255)) as item"),
                    'a.bonus_amount as amount'
                )
                ->where([
                    'a.employee_id' => $id,
                    'a.years' => $midyear_period
                ])->get();

            $yearend_bonus = DB::table('yearend_bonus as a')
                ->select(
                    'a.employee_id',
                    DB::raw("CAST('13th Month Pay' as varchar(255)) as item"),
                    'a.bonus_amount as amount'
                )
                ->where([
                    'a.employee_id' => $id,
                    'a.years' => $yearend_period
                ])->get();

            $cash_gift = DB::table('yearend_bonus as a')
                ->select(
                    'a.employee_id',
                    DB::raw("CAST('Cash Gift' as varchar(255)) as item"),
                    'a.cash_gift_amount as amount'
                )
                ->where([
                    'a.employee_id' => $id,
                    'a.years' => $yearend_period
                ])->get();

            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
            $salary_word = $f->format(($employee->salary) * 12);
            $annual_salary = ($employee->salary) * 12;

            return $this->successResponse([
                'employee' => $employee,
                'compensation_data' => [
                    'incomes' => $incomes,
                    'midyear_bonus' => $midyear_bonus,
                    'yearend_bonus' => $yearend_bonus,
                    'cash_gift' => $cash_gift
                ],
                'salary_details' => [
                    'monthly_salary' => $employee->salary,
                    'annual_salary' => $annual_salary,
                    'salary_in_words' => $salary_word
                ],
                'summary' => [
                    'total_incomes' => $incomes->sum('amount'),
                    'income_count' => $incomes->count(),
                    'total_bonus' => $midyear_bonus->sum('amount') + $yearend_bonus->sum('amount'),
                    'total_cash_gift' => $cash_gift->sum('amount'),
                    'midyear_period' => $midyear_period,
                    'yearend_period' => $yearend_period
                ]
            ], 'Employee compensation data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee compensation data: ' . $e->getMessage());
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
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))
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
            ], 'Create employee compensation certificate form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }
}
