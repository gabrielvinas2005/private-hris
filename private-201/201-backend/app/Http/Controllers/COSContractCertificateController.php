<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use App\Traits\ApiResponse;
use App\Helpers\NumberToWords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;
use Carbon\Carbon;

class COSContractCertificateController extends Controller
{
    use ApiResponse;

    public function employees()
    {
        try {
            $app_key = env('APP_KEY', '');

            $employees = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    'e.position_id',
                    'e.salary',
                    'e.salary_grade_id',
                    'e.salary_step_id',
                    DB::raw("RTRIM(ISNULL(e.pa_house_no,'')) + ' ' +
                             RTRIM(ISNULL(e.pa_street,'')) + ' ' +
                             RTRIM(ISNULL(e.pa_village,'')) + ' ' +
                             RTRIM(ISNULL(e.pa_barangay,'')) as permanent_address"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                LTRIM(RTRIM(ISNULL(e.first_name, '') + ' ' + ISNULL(e.middle_name, '') + ' ' + ISNULL(e.last_name, '')))
                             ELSE
                                LTRIM(RTRIM(
                                    ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), '') + ' ' +
                                    ISNULL(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), '') + ' ' +
                                    ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), '')
                                ))
                             END as full_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                LTRIM(RTRIM(ISNULL(e.first_name, '')))
                             ELSE
                                LTRIM(RTRIM(ISNULL([dbo].[ufn_DecryptString](e.first_name,'$app_key'), '')))
                             END as first_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                LTRIM(RTRIM(ISNULL(e.middle_name, '')))
                             ELSE
                                LTRIM(RTRIM(ISNULL([dbo].[ufn_DecryptString](e.middle_name,'$app_key'), '')))
                             END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                LTRIM(RTRIM(ISNULL(e.last_name, '')))
                             ELSE
                                LTRIM(RTRIM(ISNULL([dbo].[ufn_DecryptString](e.last_name,'$app_key'), '')))
                             END as last_name"),
                    'p.name as position_name'
                )
                ->where('e.employment_type_id', 2) // COS
                ->where('e.is_employee', true)
                ->where('e.active', true)
                ->orderBy('full_name')
                ->get();

            $contracts = DB::table('cos_contract')
                ->whereIn('employee_id', $employees->pluck('id'))
                ->orderByDesc('Start_date')
                ->get()
                ->groupBy('employee_id');

            $employees = $employees->map(function ($employee) use ($contracts) {
                $employeeContracts = $contracts->get($employee->id, collect());
                $employee->contract = $this->resolveCosContractFromCollection($employeeContracts);
                $employee->full_name = $this->formatPersonNameWithMiddleInitial($employee);

                return $employee;
            });

            return $this->successResponse($employees, 'COS contract employees loaded successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve COS contract employees: ' . $e->getMessage());
        }
    }

    public function signatories()
    {
        try {
            $app_key = env('APP_KEY', '');

            $signatories = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                LTRIM(RTRIM(ISNULL(e.first_name, '') + ' ' + ISNULL(e.middle_name, '') + ' ' + ISNULL(e.last_name, '')))
                             ELSE
                                LTRIM(RTRIM(
                                    ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), '') + ' ' +
                                    ISNULL(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), '') + ' ' +
                                    ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), '')
                                ))
                             END as full_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                LTRIM(RTRIM(ISNULL(e.first_name, '')))
                             ELSE
                                LTRIM(RTRIM(ISNULL([dbo].[ufn_DecryptString](e.first_name,'$app_key'), '')))
                             END as first_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                LTRIM(RTRIM(ISNULL(e.middle_name, '')))
                             ELSE
                                LTRIM(RTRIM(ISNULL([dbo].[ufn_DecryptString](e.middle_name,'$app_key'), '')))
                             END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                LTRIM(RTRIM(ISNULL(e.last_name, '')))
                             ELSE
                                LTRIM(RTRIM(ISNULL([dbo].[ufn_DecryptString](e.last_name,'$app_key'), '')))
                             END as last_name"),
                    'p.name as position_name'
                )
                ->where('e.is_employee', true)
                ->where('e.active', true)
                ->orderBy('full_name')
                ->get()
                ->map(function ($signatory) {
                    $signatory->full_name = $this->formatPersonNameWithMiddleInitial($signatory);
                    return $signatory;
                });

            return $this->successResponse($signatories, 'COS contract signatories loaded successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve COS contract signatories: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $context = $this->prepareCertificateContext($request);
            if (isset($context['error'])) {
                return $context['error'];
            }

            $pdf = PDF::loadView('employee_certificates.cos_contract_certificate', $context)
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');

            $pdfContent = $pdf->output();
            $filename = 'cos_contract_' . $context['employee']->employee_no . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate COS contract certificate: ' . $e->getMessage());
        }
    }

    public function downloadDocx(Request $request)
    {
        try {
            $context = $this->prepareCertificateContext($request);
            if (isset($context['error'])) {
                return $context['error'];
            }

            $employee = $context['employee'];
            $employeeName = $context['employee_name'];
            $signatory = $context['signatory'];
            $signatoryName = $context['signatory_name'];
            $witness1 = $context['witness1'];
            $witness1Name = $context['witness1_name'];
            $witness2 = $context['witness2'];
            $witness2Name = $context['witness2_name'];
            $formattedDate = $context['contract_start_formatted'];
            $contractPeriod = $context['contract_period'];
            $contractEndFormatted = $context['contract_end_formatted'];
            $salaryInWords = $context['salary_in_words'];
            $salaryFormatted = $context['salary_amount_formatted'];
            $salaryGrade = $context['salary_grade'];
            $salaryStep = $context['salary_step'];
            $currentYear = $context['current_year'];
            $contractFunctions = $context['contract_functions'];
            $notaryCityDisplay = $context['notary_city'] !== ''
                ? $context['notary_city']
                : '_____________________';

            // Create PhpWord document
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(10);

            // Top = 1in, Right = 1in, Bottom = 0.75in, Left = 1.25in
            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(1),
                'marginRight' => Converter::inchToTwip(1),
                'marginBottom' => Converter::inchToTwip(0.75),
                'marginLeft' => Converter::inchToTwip(1.25),
                'pageSizeW' => Converter::inchToTwip(8.27),
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

            // Paragraph style (tight line spacing ~0.5)
            $phpWord->addParagraphStyle('contractBody', [
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 60,
                'lineHeight' => 1.1,
            ]);

            $phpWord->addParagraphStyle('contractIndent', [
                'indentation' => ['firstLine' => Converter::inchToTwip(0.25)],
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 60,
                'lineHeight' => 1.1,
            ]);

            // Numbered list style
            $phpWord->addParagraphStyle('numberedItem', [
                'indentation' => ['left' => Converter::inchToTwip(0.25), 'firstLine' => Converter::inchToTwip(-0.15)],
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 40,
                'lineHeight' => 1.1,
            ]);

            // Sub-list style (lettered)
            $phpWord->addParagraphStyle('subItem', [
                'indentation' => ['left' => Converter::inchToTwip(0.5), 'firstLine' => Converter::inchToTwip(-0.15)],
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 40,
                'lineHeight' => 1.1,
            ]);

            // Title
            $section->addText('CONTRACT OF SERVICE', ['bold' => true, 'size' => 12], ['alignment' => WordJc::CENTER, 'spaceAfter' => 120]);
            $section->addTextBreak(1);

            // KNOW ALL MEN BY THESE PRESENTS
            $section->addText('KNOW ALL MEN BY THESE PRESENTS:', ['bold' => true], ['spaceAfter' => 120]);
            $section->addTextBreak(0.5);

            // Introduction paragraph
            $introPara = $section->addTextRun('contractBody');
            $introPara->addText('This CONTRACT OF SERVICE is made and entered into this ' . $formattedDate . ', at the City of Pasay, by and between:');
            $section->addTextBreak(1);

            // Agency paragraph
            $pttcPara = $section->addTextRun('contractBody');
            $pttcPara->addText('The Department of Trade and Industry – ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . '), with office address at ' . CompanyHelper::getAddress() . ' represented in this act by ');
            $pttcPara->addText(strtoupper($signatory->position_name ?? 'OIC - Executive Director'), ['bold' => true]);
            $pttcPara->addText(' ');
            $pttcPara->addText(strtoupper($signatoryName ?: ($signatory->full_name ?? '')), ['bold' => true]);
            $pttcPara->addText(' and hereinafter referred to as the ' . BranchHelper::getMainBranchCode() . ';');
            $section->addTextBreak(1);

            // -and- separator
            $section->addText('-and-', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 120]);
            $section->addTextBreak(0.5);

            // Employee paragraph
            $empPara = $section->addTextRun('contractBody');
            $empPara->addText(strtoupper($employeeName ?: ($employee->full_name ?? 'N/A')), ['bold' => true]);
            $empPara->addText(', of legal age, Filipino, and with postal address at ');
            $empPara->addText(trim($employee->permanent_address ?? 'N/A'), ['bold' => true]);
            $empPara->addText(', and herein referred to as the ');
            $empPara->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $empPara->addText(';');
            $section->addTextBreak(1);

            // WITNESSETH
            $section->addText('WITNESSETH:', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 120]);
            $section->addTextBreak(0.5);

            // WHEREAS paragraphs
            $whereas1 = $section->addTextRun('contractBody');
            $whereas1->addText('WHEREAS, the ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ') is in need of personnel who will be responsible in assisting in administrative tasks and be trained to perform technical tasks related to training implementation of the Center;');
            $section->addTextBreak(0.5);

            $whereas2 = $section->addTextRun('contractBody');
            $whereas2->addText('WHEREAS, the ');
            $whereas2->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $whereas2->addText(' has signified intention, to which the ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ') accepted to provide the services needed by the latter;');
            $section->addTextBreak(0.5);

            $whereas3 = $section->addTextRun('contractBody');
            $whereas3->addText('WHEREAS, the ');
            $whereas3->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $whereas3->addText(', by virtue of the education, experience and skills he possesses, is qualified to act as such and is willing to enter into a Service Contract with the ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ');');
            $section->addTextBreak(0.5);

            $whereas4 = $section->addTextRun('contractBody');
            $whereas4->addText('WHEREAS, the ');
            $whereas4->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $whereas4->addText(' hereby attests that she is not related within the third degree of consanguinity to the hiring authority; that she has not been previously dismissed from government service by reason of an administrative offense;');
            $section->addTextBreak(0.5);

            $nowPara = $section->addTextRun('contractBody');
            $nowPara->addText('NOW, THEREFORE, for and in consideration of the foregoing premises, the parties hereby execute this Contract, subject to the following terms and conditions:');
            $section->addTextBreak(1);

            // Numbered list 1-5
            $list1 = $section->addTextRun('numberedItem');
            $list1->addText('1. ', ['bold' => true]);
            $list1->addText('' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ') hereby contracted the services of the ');
            $list1->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $list1->addText(' for the period ' . $contractPeriod . ' and may be renewed subject to performance evaluation.');
            $section->addTextBreak(0.3);

            $list2 = $section->addTextRun('numberedItem');
            $list2->addText('2. ', ['bold' => true]);
            $list2->addText('That the ');
            $list2->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $list2->addText(' shall be paid a monthly Service Fee of ' . $salaryInWords . ' (Php ' . $salaryFormatted . ') - SG ' . $salaryGrade . ', Step ' . $salaryStep . ' plus 20% premium, upon submission of monthly accomplishment reports, inclusive of applicable withholding taxes, certification of satisfactory performance and inclusive of the following deliverables, subject to accounting and auditing rules and regulations which shall be paid in two terms every 10th and 25th of each month.');
            $section->addTextBreak(0.3);

            $list3 = $section->addTextRun('numberedItem');
            $list3->addText('3. ', ['bold' => true]);
            $list3->addText('Any increments implemented by Department of Budget and Management (DBM) in a yearly schedule for salary increases applicable to civilian personnel in the National Government Agencies (NGA) shall also apply to the active Contract of Service (COS) personnel upon renewal of contract and subject to availability of funds.');
            $section->addTextBreak(0.3);

            $list4 = $section->addTextRun('numberedItem');
            $list4->addText('4. ', ['bold' => true]);
            $list4->addText('That the ');
            $list4->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $list4->addText(' shall report from Monday to Friday, flexible time from 7:00 AM – 9:00 AM to 4:00 PM – 6:00 PM (8-hour or 40-hour workweek) observing an alternative work arrangement as the Office shall implement.');
            $section->addTextBreak(0.3);

            $list5 = $section->addTextRun('numberedItem');
            $list5->addText('5. ', ['bold' => true]);
            $list5->addText('That the ');
            $list5->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $list5->addText(' shall perform the following functions under the ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . '):');
            $section->addTextBreak(0.3);

            foreach ($contractFunctions as $index => $functionText) {
                $subPara = $section->addTextRun('subItem');
                $subPara->addText(chr(97 + $index) . '. ' . $functionText);
                $section->addTextBreak(0.2);
            }

            $section->addTextBreak(0.5);

            // Numbered list 6-12
            $list6 = $section->addTextRun('numberedItem');
            $list6->addText('6. ', ['bold' => true]);
            $list6->addText('That in cases where the ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ') directs the ');
            $list6->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $list6->addText(' to conduct local travel, the existing rates of per diem, hotel allowances and other travel expenses under Executive Order 248, s. 1995 shall be made applicable.');
            $section->addTextBreak(0.3);

            $list7 = $section->addTextRun('numberedItem');
            $list7->addText('7. ', ['bold' => true]);
            $list7->addText('That in cases where the ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ') directs the ');
            $list7->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $list7->addText(' to provide the necessary support in urgent activities and during special events and projects, she will be entitled to collect overtime pay for her overtime services subject to existing applicable rules and regulations.');
            $section->addTextBreak(0.3);

            $list8 = $section->addTextRun('numberedItem');
            $list8->addText('8. ', ['bold' => true]);
            $list8->addText('That it is understood that there exists no employer-employee relationship between the parties to this agreement, that the services of the ');
            $list8->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $list8->addText(' under this agreement shall not be entitled to the benefits being enjoyed by the regular personnel of the Department.');
            $section->addTextBreak(0.3);

            $list9 = $section->addTextRun('numberedItem');
            $list9->addText('9. ', ['bold' => true]);
            $list9->addText('That in case of absences/tardiness/undertime, deductions in pay shall be made accordingly.');
            $section->addTextBreak(0.3);

            $list10 = $section->addTextRun('numberedItem');
            $list10->addText('10. ', ['bold' => true]);
            $list10->addText('That the ');
            $list10->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $list10->addText(' shall not at any time communicate to any person or entity any confidential information acquired in the course of the services as stipulated in the signed Confidentiality and Non-Disclosure Undertaking.');
            $section->addTextBreak(0.3);

            $list11 = $section->addTextRun('numberedItem');
            $list11->addText('11. ', ['bold' => true]);
            $list11->addText('That the ');
            $list11->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $list11->addText(' shall submit a copy of her drug test result conducted by a government testing laboratory once in every six-month contract.');
            $section->addTextBreak(0.3);

            $list12 = $section->addTextRun('numberedItem');
            $list12->addText('12. ', ['bold' => true]);
            $list12->addText('That this Contract may be terminated prior to ' . $contractEndFormatted . ' under the following circumstances:');
            $section->addTextBreak(0.3);

            // Sub-list a-f for item 12
            $sub12a = $section->addTextRun('subItem');
            $sub12a->addText('a. If the ');
            $sub12a->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $sub12a->addText(' elects to terminate her services with 30-day notice period;');
            $section->addTextBreak(0.2);

            $sub12b = $section->addTextRun('subItem');
            $sub12b->addText('b. If the program/project/activities for which the ');
            $sub12b->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $sub12b->addText(' was hired is cancelled or if there are no more funds to justify the continued hiring of the Project Management Specialist;');
            $section->addTextBreak(0.2);

            $sub12c = $section->addTextRun('subItem');
            $sub12c->addText('c. If the ');
            $sub12c->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $sub12c->addText(' falls short of the standards in terms of performing the assigned duties and responsibilities;');
            $section->addTextBreak(0.2);

            $sub12d = $section->addTextRun('subItem');
            $sub12d->addText('d. If the ');
            $sub12d->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $sub12d->addText(' fails to submit her drug test results within the period of this contract.');
            $section->addTextBreak(0.2);

            $sub12e = $section->addTextRun('subItem');
            $sub12e->addText('e. If the ');
            $sub12e->addText(strtoupper($employee->position_name ?? 'N/A'), ['bold' => true]);
            $sub12e->addText(' worker violates any policy being implemented by the ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . '); and');
            $section->addTextBreak(0.2);

            $sub12f = $section->addTextRun('subItem');
            $sub12f->addText('f. Any other justifiable reason.');
            $section->addTextBreak(0.2);

            $section->addTextBreak(1);

            // IN WITNESS WHEREOF
            $witnessPara = $section->addTextRun('contractBody');
            $witnessPara->addText('IN WITNESS WHEREOF, the Parties have hereunto affixed their signature on the date and place first above mentioned.');
            $section->addTextBreak(2);
            // Signature blocks (side by side)
            $signatureTable = $section->addTable([
                'borderSize' => 0,
                'cellMargin' => 80,
                'borderColor' => 'ffffff',
            ]);
            $signatureTable->addRow();
            $cell1 = $signatureTable->addCell(Converter::inchToTwip(3.5));
            $cell2 = $signatureTable->addCell(Converter::inchToTwip(3.5));

            $cell1->addText(strtoupper($signatoryName ?: ($signatory->full_name ?? '')), ['bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER, 'spaceAfter' => 40]);
            $cell1->addText($signatory->position_name ?? 'OIC - Executive Director', [], ['alignment' => WordJc::CENTER]);

            $cell2->addText(strtoupper($employeeName ?: ($employee->full_name ?? 'N/A')), ['bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER, 'spaceAfter' => 40]);
            $cell2->addText($employee->position_name ?? 'N/A', [], ['alignment' => WordJc::CENTER]);

            // SIGNED in the presence of
            $section->addText('SIGNED in the presence of:', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 120]);
            $section->addTextBreak(1);

            // Witness blocks
            $witnessTable = $section->addTable([
                'borderSize' => 0,
                'cellMargin' => 80,
                'borderColor' => 'ffffff',
            ]);
            $witnessTable->addRow();
            $wcell1 = $witnessTable->addCell(Converter::inchToTwip(3.5));
            $wcell2 = $witnessTable->addCell(Converter::inchToTwip(3.5));

            $wcell1->addText(strtoupper($witness1Name ?: ($witness1->full_name ?? 'N/A')), ['bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER, 'spaceAfter' => 40]);
            $wcell1->addText($witness1->position_name ?? 'WITNESS 1', [], ['alignment' => WordJc::CENTER]);

            $wcell2->addText(strtoupper($witness2Name ?: ($witness2->full_name ?? 'N/A')), ['bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER, 'spaceAfter' => 40]);
            $wcell2->addText($witness2->position_name ?? 'WITNESS 2', [], ['alignment' => WordJc::CENTER]);

            $section->addTextBreak(2);

            // Acknowledgement section
            $section->addText('ACKNOWLEDGEMENT', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 120]);
            $section->addTextBreak(1);

            $repPara = $section->addTextRun('contractBody');
            $repPara->addText('Republic of the Philippines     )');
            $repPara->addTextBreak();
            $repPara->addText('City of ' . $notaryCityDisplay . '     ) S.S');
            $section->addTextBreak(1);

            $beforePara = $section->addTextRun('contractIndent');
            $beforePara->addText('BEFORE ME, a Notary Public for and in the City of ' . $notaryCityDisplay . ', on this _______ day of _______, personally appeared the following with their competent evidence of identity:');
            $section->addTextBreak(1);

            // Acknowledgement table
            $ackTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 40,
                'borderColor' => 'ffffff',
            ]);
            $ackTable->addRow();
            $ackTable->addCell(Converter::inchToTwip(2.5))->addText('Name', ['bold' => true]);
            $ackTable->addCell(Converter::inchToTwip(2.5))->addText('Competent Evidence of Identity', ['bold' => true]);
            $ackTable->addCell(Converter::inchToTwip(2))->addText('Date/Place Issued', ['bold' => true]);

            $ackTable->addRow();
            $ackTable->addCell()->addText($signatoryName ?: ($signatory->full_name ?? ''), ['bold' => true]);
            $ackTable->addCell()->addText('____________________');
            $ackTable->addCell()->addText('___________________');

            $ackTable->addRow();
            $ackTable->addCell()->addText($employeeName ?: ($employee->full_name ?? 'N/A'), ['bold' => true]);
            $ackTable->addCell()->addText('____________________');
            $ackTable->addCell()->addText('___________________');

            $section->addTextBreak(1);

            $knownPara = $section->addTextRun('contractIndent');
            $knownPara->addText('Known to me and to me known to be the same persons who executed the foregoing instrument and acknowledged to me that the same is their free and voluntary act and deed.');
            $section->addTextBreak(0.5);

            $instrumentPara = $section->addTextRun('contractIndent');
            $instrumentPara->addText('This instrument, consisting of four (4) pages including this page whereon this acknowledgement is written has been signed by the parties and their instrumental witnesses on each and every page thereof.');
            $section->addTextBreak(0.5);

            $witnessPara = $section->addTextRun('contractIndent');
            $witnessPara->addText('WITNESS MY HAND AND SEAL, on the date and place first above written.');
            $section->addTextBreak(2);

            $section->addText('Notary Public', [], ['alignment' => WordJc::END, 'spaceAfter' => 120]);
            $section->addTextBreak(1);

            $docPara = $section->addTextRun('contractBody');
            $docPara->addText('Doc. No.  _____');
            $docPara->addTextBreak();
            $docPara->addText('Page No. _____');
            $docPara->addTextBreak();
            $docPara->addText('Book No. _____');
            $docPara->addTextBreak();
            $docPara->addText('Series of ' . $currentYear);

            // Save and return
            $filename = 'cos_contract_certificate_' . $employee->employee_no . '_' . date('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate COS contract certificate DOCX: ' . $e->getMessage());
        }
    }

    private function prepareCertificateContext(Request $request): array
    {
        $validator = validator($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'signatory_id' => 'required|integer|exists:employees,id',
            'witness1_id' => 'required|integer|exists:employees,id',
            'witness2_id' => 'required|integer|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return ['error' => $this->validationErrorResponse($validator->errors())];
        }

        $app_key = env('APP_KEY', '');

        $employee = $this->fetchCertificatePerson($request->employee_id, true);
        if (!$employee) {
            return ['error' => $this->notFoundResponse('COS contract employee not found')];
        }

        $contract = $this->resolveCosContract((int) $request->employee_id);
        if (!$contract) {
            return ['error' => $this->errorResponse('No COS contract record found for this employee. Please add a contract in the employee record first.', 400)];
        }

        $signatory = $this->fetchCertificatePerson($request->signatory_id);
        if (!$signatory) {
            return ['error' => $this->notFoundResponse('COS contract signatory not found')];
        }

        $witness1 = $this->fetchCertificatePerson($request->witness1_id);
        if (!$witness1) {
            return ['error' => $this->notFoundResponse('COS contract witness 1 not found')];
        }

        $witness2 = $this->fetchCertificatePerson($request->witness2_id);
        if (!$witness2) {
            return ['error' => $this->notFoundResponse('COS contract witness 2 not found')];
        }

        $contractStart = $contract->Start_date ? Carbon::parse($contract->Start_date) : Carbon::now();
        $contractEnd = $contract->End_date ? Carbon::parse($contract->End_date) : null;
        $salaryAmount = (float) ($contract->salary ?? $employee->salary ?? 0);
        $salaryGrade = (int) ($contract->salary_grade_id ?? $employee->salary_grade_id ?? 0);
        $salaryStep = (int) ($contract->salary_step_id ?? $employee->salary_step_id ?? 0);

        return [
            'employee' => $employee,
            'signatory' => $signatory,
            'signatory_name' => $this->formatPersonNameWithMiddleInitial($signatory),
            'employee_name' => $this->formatPersonNameWithMiddleInitial($employee),
            'witness1_name' => $this->formatPersonNameWithMiddleInitial($witness1),
            'witness2_name' => $this->formatPersonNameWithMiddleInitial($witness2),
            'witness1' => $witness1,
            'witness2' => $witness2,
            'contract' => $contract,
            'contract_functions' => $this->resolveContractFunctions($request),
            'contract_start_formatted' => $this->formatOrdinalDate($contractStart),
            'contract_period' => $this->formatContractPeriod($contractStart, $contractEnd),
            'contract_end_formatted' => $contractEnd ? $contractEnd->format('F j, Y') : 'the contract end date',
            'salary_amount' => $salaryAmount,
            'salary_amount_formatted' => number_format($salaryAmount, 2),
            'salary_in_words' => ucwords(NumberToWords::formatCurrency($salaryAmount)),
            'salary_grade' => $salaryGrade,
            'salary_step' => $salaryStep,
            'current_year' => date('Y'),
            'notary_city' => $this->resolveNotaryCity($request),
        ];
    }

    private function resolveNotaryCity(Request $request): string
    {
        return trim((string) $request->input('notary_city', ''));
    }

    private function formatPersonNameWithMiddleInitial(?object $person): string
    {
        if (!$person) {
            return '';
        }

        $first = trim((string) ($person->first_name ?? ''));
        $middle = trim((string) ($person->middle_name ?? ''));
        $last = trim((string) ($person->last_name ?? ''));

        if ($first === '' && $last === '') {
            return trim((string) ($person->full_name ?? ''));
        }

        $middleInitial = $middle !== '' ? strtoupper(substr($middle, 0, 1)) . '. ' : '';

        return trim($first . ' ' . $middleInitial . $last);
    }

    private function fetchCertificatePerson(int $id, bool $cosOnly = false): ?object
    {
        $app_key = env('APP_KEY', '');

        $query = DB::table('employees as e')
            ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
            ->select(
                'e.id',
                'e.employee_no',
                'e.salary',
                'e.salary_grade_id',
                'e.salary_step_id',
                'e.position_id',
                DB::raw("RTRIM(ISNULL(e.pa_house_no,'')) + ' ' +
                         RTRIM(ISNULL(e.pa_street,'')) + ' ' +
                         RTRIM(ISNULL(e.pa_village,'')) + ' ' +
                         RTRIM(ISNULL(e.pa_barangay,'')) as permanent_address"),
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            LTRIM(RTRIM(ISNULL(e.first_name, '') + ' ' + ISNULL(e.middle_name, '') + ' ' + ISNULL(e.last_name, '')))
                         ELSE
                            LTRIM(RTRIM(
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), '')
                            ))
                         END as full_name"),
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            LTRIM(RTRIM(ISNULL(e.first_name, '')))
                         ELSE
                            LTRIM(RTRIM(ISNULL([dbo].[ufn_DecryptString](e.first_name,'$app_key'), '')))
                         END as first_name"),
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            LTRIM(RTRIM(ISNULL(e.middle_name, '')))
                         ELSE
                            LTRIM(RTRIM(ISNULL([dbo].[ufn_DecryptString](e.middle_name,'$app_key'), '')))
                         END as middle_name"),
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            LTRIM(RTRIM(ISNULL(e.last_name, '')))
                         ELSE
                            LTRIM(RTRIM(ISNULL([dbo].[ufn_DecryptString](e.last_name,'$app_key'), '')))
                         END as last_name"),
                'p.name as position_name'
            )
            ->where('e.id', $id)
            ->where('e.is_employee', true)
            ->where('e.active', true);

        if ($cosOnly) {
            $query->where('e.employment_type_id', 2);
        }

        return $query->first();
    }

    private function resolveCosContract(int $employeeId): ?object
    {
        $contracts = DB::table('cos_contract')
            ->where('employee_id', $employeeId)
            ->orderByDesc('Start_date')
            ->get();

        return $this->resolveCosContractFromCollection($contracts);
    }

    private function resolveCosContractFromCollection($contracts): ?object
    {
        if ($contracts->isEmpty()) {
            return null;
        }

        $today = Carbon::today();

        foreach ($contracts->sortByDesc('Start_date') as $contract) {
            if ($this->isContractActiveOnDate($contract, $today)) {
                return $contract;
            }
        }

        return $contracts->sortByDesc('Start_date')->first();
    }

    private function isContractActiveOnDate(object $contract, Carbon $date): bool
    {
        if (!empty($contract->End_date)) {
            $endDate = Carbon::parse($contract->End_date)->startOfDay();
            if ($endDate->lt($date)) {
                return false;
            }
        }

        if (!empty($contract->Start_date)) {
            $startDate = Carbon::parse($contract->Start_date)->startOfDay();
            if ($startDate->gt($date)) {
                return false;
            }
        }

        return !empty($contract->Start_date) || !empty($contract->End_date);
    }

    private function formatOrdinalDate(Carbon $date): string
    {
        $day = (int) $date->format('j');
        $suffix = ($day % 10 === 1 && $day !== 11) ? 'st'
            : (($day % 10 === 2 && $day !== 12) ? 'nd'
            : (($day % 10 === 3 && $day !== 13) ? 'rd' : 'th'));

        return $day . $suffix . ' day of ' . $date->format('F Y');
    }

    private function formatContractPeriod(Carbon $start, ?Carbon $end): string
    {
        $startText = $start->format('F j, Y');
        if (!$end) {
            return $startText;
        }

        return $startText . ' – ' . $end->format('F j, Y');
    }

    private function defaultContractFunctions(): array
    {
        return [
            'Facilitate documentary requirements needed by the LDSD.',
            'Ensure timely and accurate recording and routing of document.',
            'Coordinate with the Section Heads on the instruction given by the DC.',
            'Maintain complete and updated files and records of the LDSD.',
            'Prepare minutes or highlights of the meetings.',
            'Assist in coordinating with other DTI agencies on the scheduling and implementation of requested training activities specifically in the regions.',
            'Coordinate/facilitate/undertake the secretariat services in the preparation for and conducts of training and ensures that the physical facilities are in order at all times.',
            'Organize, maintain and update databank of information, research and training materials on trade and industry, and other subjects relevant to the program of the division.',
            'Prepares and submit training reports and consolidates data bank of reports/documents pertaining to each training.',
            'Perform other related functions as may be assigned from time to time.',
        ];
    }

    private function resolveContractFunctions(Request $request): array
    {
        $input = $request->input('contract_functions');
        if (!is_array($input)) {
            return $this->defaultContractFunctions();
        }

        $functions = [];
        foreach ($input as $item) {
            $text = trim((string) $item);
            if ($text !== '') {
                $functions[] = $text;
            }
        }

        return !empty($functions) ? $functions : $this->defaultContractFunctions();
    }
}

