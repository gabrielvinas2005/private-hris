<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;
use Carbon\Carbon;

class COSNonDisclosureAgreementController extends Controller
{
    use ApiResponse;

    public function employees()
    {
        try {
            $app_key = env('APP_KEY', '');

            $employees = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('divisions as div', 'div.id', '=', 'e.division_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    'e.position_id',
                    DB::raw("RTRIM(ISNULL(e.pa_house_no,'')) + ' ' +
                             RTRIM(ISNULL(e.pa_street,'')) + ' ' +
                             RTRIM(ISNULL(e.pa_village,'')) + ' ' +
                             RTRIM(ISNULL(e.pa_barangay,'')) as permanent_address"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                ISNULL(e.first_name, '') + ' ' + ISNULL(e.last_name, '')
                             ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), '')
                             END as full_name"),
                    'p.name as position_name',
                    'div.name as division_name',
                    'div.code as division_code'
                )
                ->where('e.employment_type_id', 2) // COS
                ->where('e.is_employee', true)
                ->where('e.active', true)
                ->orderBy('full_name')
                ->get();

            return $this->successResponse($employees, 'COS NDA employees loaded successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve COS NDA employees: ' . $e->getMessage());
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
                                ISNULL(e.first_name, '') + ' ' + ISNULL(e.last_name, '')
                             ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), '')
                             END as full_name"),
                    'p.name as position_name'
                )
                ->where('e.is_employee', true)
                ->where('e.active', true)
                ->orderBy('full_name')
                ->get();

            return $this->successResponse($signatories, 'COS NDA signatories loaded successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve COS NDA signatories: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee_id' => 'required|integer|exists:employees,id',
                'signatory_id' => 'required|integer|exists:employees,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env('APP_KEY', '');

            $employee = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('divisions as div', 'div.id', '=', 'e.division_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    'e.position_id',
                    DB::raw("RTRIM(ISNULL(e.pa_house_no,'')) + ' ' +
                             RTRIM(ISNULL(e.pa_street,'')) + ' ' +
                             RTRIM(ISNULL(e.pa_village,'')) + ' ' +
                             RTRIM(ISNULL(e.pa_barangay,'')) as permanent_address"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                ISNULL(e.first_name, '') + ' ' + ISNULL(e.last_name, '')
                             ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), '')
                             END as full_name"),
                    'p.name as position_name',
                    'div.name as division_name',
                    'div.code as division_code'
                )
                ->where('e.id', $request->employee_id)
                ->where('e.employment_type_id', 2)
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('COS NDA employee not found');
            }

            $signatory = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                ISNULL(e.first_name, '') + ' ' + ISNULL(e.last_name, '')
                             ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), '')
                             END as full_name"),
                    'p.name as position_name'
                )
                ->where('e.id', $request->signatory_id)
                ->where('e.is_employee', true)
                ->where('e.active', true)
                ->first();

            if (!$signatory) {
                return $this->notFoundResponse('COS NDA signatory not found');
            }

            $data = [
                'employee' => $employee,
                'signatory' => $signatory,
                'current_date' => Carbon::now(),
            ];

            $pdf = PDF::loadView('employee_certificates.cos_non_disclosure_agreement', $data)
                ->setOptions([
                    'defaultFont' => 'sans-serif',
                    'isPhpEnabled' => true
                ]);
            $pdf->setPaper('A4');

            $pdfContent = $pdf->output();
            $filename = 'cos_non_disclosure_agreement_' . $employee->employee_no . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate COS NDA: ' . $e->getMessage());
        }
    }

    public function downloadDocx(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee_id' => 'required|integer|exists:employees,id',
                'signatory_id' => 'required|integer|exists:employees,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env('APP_KEY', '');

            $employee = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('divisions as div', 'div.id', '=', 'e.division_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    'e.position_id',
                    DB::raw("RTRIM(ISNULL(e.pa_house_no,'')) + ' ' +
                             RTRIM(ISNULL(e.pa_street,'')) + ' ' +
                             RTRIM(ISNULL(e.pa_village,'')) + ' ' +
                             RTRIM(ISNULL(e.pa_barangay,'')) as permanent_address"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                ISNULL(e.first_name, '') + ' ' + ISNULL(e.last_name, '')
                             ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), '')
                             END as full_name"),
                    'p.name as position_name',
                    'div.name as division_name',
                    'div.code as division_code'
                )
                ->where('e.id', $request->employee_id)
                ->where('e.employment_type_id', 2)
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('COS NDA employee not found');
            }

            $signatory = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                ISNULL(e.first_name, '') + ' ' + ISNULL(e.last_name, '')
                             ELSE
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), '') + ' ' +
                                ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), '')
                             END as full_name"),
                    'p.name as position_name'
                )
                ->where('e.id', $request->signatory_id)
                ->where('e.is_employee', true)
                ->where('e.active', true)
                ->first();

            if (!$signatory) {
                return $this->notFoundResponse('COS NDA signatory not found');
            }

            $currentDate = Carbon::now();
            $day = $currentDate->format('j');
            $suffix = ($day == 1 || $day == 21 || $day == 31) ? 'st'
                : (($day == 2 || $day == 22) ? 'nd'
                : (($day == 3 || $day == 23) ? 'rd' : 'th'));
            $formattedDate = $day . $suffix . ' of ' . $currentDate->format('F Y');

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

            // Paragraph styles
            $phpWord->addParagraphStyle('contractBody', [
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

            // Sub-sub-list style (roman numerals)
            $phpWord->addParagraphStyle('subSubItem', [
                'indentation' => ['left' => Converter::inchToTwip(0.75), 'firstLine' => Converter::inchToTwip(-0.15)],
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 40,
                'lineHeight' => 1.1,
            ]);

            // Title
            $section->addText('CONFIDENTIALITY AND NON-DISCLOSURE UNDERTAKING', ['bold' => true, 'size' => 12], ['alignment' => WordJc::CENTER, 'spaceAfter' => 240]);
            $section->addTextBreak(1);

            // Introduction paragraph
            // Sanitize dynamic data - escape XML special characters
            $employeeName = htmlspecialchars(strtoupper((string)($employee->full_name ?? 'N/A')), ENT_XML1, 'UTF-8');
            $employeeName = str_replace(["\0", "\r"], '', $employeeName);
            $employeeAddress = htmlspecialchars(trim((string)($employee->permanent_address ?? 'N/A')), ENT_XML1, 'UTF-8');
            $employeeAddress = str_replace(["\0", "\r"], '', $employeeAddress);
            
            $introPara = $section->addTextRun('contractBody');
            $introPara->addText('I, ');
            $introPara->addText($employeeName, ['bold' => true]);
            $introPara->addText(', a Filipino citizen, of legal age and with residence at ');
            $introPara->addText($employeeAddress, ['bold' => true]);
            $introPara->addText(', after being sworn in accordance with law, hereby declare that:');
            $section->addTextBreak(1);

            // Item 1
            // Sanitize dynamic data - escape XML special characters
            $positionName = htmlspecialchars(strtoupper((string)($employee->position_name ?? 'N/A')), ENT_XML1, 'UTF-8');
            $positionName = str_replace(["\0", "\r"], '', $positionName);
            $divisionName = htmlspecialchars(strtoupper((string)($employee->division_name ?? 'N/A')), ENT_XML1, 'UTF-8');
            $divisionCode = !empty($employee->division_code) ? htmlspecialchars(strtoupper((string)$employee->division_code), ENT_XML1, 'UTF-8') : '';
            $divisionName = str_replace(["\0", "\r"], '', $divisionName);
            $divisionCode = str_replace(["\0", "\r"], '', $divisionCode);
            
            $item1 = $section->addTextRun('numberedItem');
            $item1->addText('1. ', ['bold' => true]);
            $item1->addText('I am ');
            $item1->addText($positionName, ['bold' => true]);
            $item1->addText(' OF THE ');
            $item1->addText($divisionName, ['bold' => true]);
            if (!empty($divisionCode)) {
                $item1->addText(' (');
                $item1->addText($divisionCode, ['bold' => true]);
                $item1->addText(')');
            }
            $item1->addText(' of ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ') OR I am performing services for ' . CompanyHelper::getName() . ' under a Contract of Service, and am executing this undertaking in favor of ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ')');
            $section->addTextBreak(0.3);

            // Item 2
            $item2 = $section->addTextRun('numberedItem');
            $item2->addText('2. ', ['bold' => true]);
            $item2->addText('In the course of performing services for ' . BranchHelper::getMainBranchCode() . ', I may have access to or some across confidential information in the possession of, or being maintained by, ' . BranchHelper::getMainBranchCode() . ' which includes confidential information its officers, personnel, partner agencies or collaborators or other third persons. Confidential information is information that would be reasonably understood as confidential as the same is non-public information about a person or an entity that, if disclosed, could reasonably be expected to place either the person or the entity at risk of criminal or civil liability, or damage the person or entity\'s financial interests or standing, employability, privacy or reputation etc.  such that access thereto is limited only to those with a need to know by reason of the performance of their functions whether or not the information is in writing or in a material form or has or has not been marked as confidential.  It includes but is not limited to:');
            $section->addTextBreak(0.3);

            // Item 2.a
            $item2a = $section->addTextRun('subItem');
            $item2a->addText('a. personal information as defined under the Philippine Data Privacy Act (DPA). It is any information whether recorded in a material form or not, from which the identity of an individual is apparent or can be reasonably and directly ascertained by the entity holding the information, or when put together with other information would directly and certainly identify an individual e.g. home addresses and other contact details of participants, personnel or persons who have contracts with ' . BranchHelper::getMainBranchCode() . ';');
            $section->addTextBreak(0.3);

            // Item 2.b
            $item2b = $section->addTextRun('subItem');
            $item2b->addText('b. sensitive personal information as defined under the DPA which includes personal information;');
            $section->addTextBreak(0.2);

            // Item 2.b.i-iv (roman numerals)
            $item2bi = $section->addTextRun('subSubItem');
            $item2bi->addText('i. About an individual\'s race, ethnic origin, marital status, age, color, and religious, philosophical or political affiliations;');
            $section->addTextBreak(0.2);

            $item2bii = $section->addTextRun('subSubItem');
            $item2bii->addText('ii. About an individual\'s health, education, genetic or sexual life of a person, or to any proceeding for any offense committed or alleged to have been committed by such person, the disposal of such proceedings, or the sentence of any court in such proceedings;');
            $section->addTextBreak(0.2);

            $item2biii = $section->addTextRun('subSubItem');
            $item2biii->addText('iii. Issued by government agencies peculiar to an individual which includes, but not limited to, social security numbers, previous or current health records, licenses or its denials, suspension or revocation, and tax returns; and;');
            $section->addTextBreak(0.2);

            $item2biv = $section->addTextRun('subSubItem');
            $item2biv->addText('iv. Specifically established by an executive order or an act of Congress to be kept classified.');
            $section->addTextBreak(0.3);

            // Item 2.c
            $item2c = $section->addTextRun('subItem');
            $item2c->addText('c. Privileged information refers to any and all forms of data which under the Rules of Court and other pertinent laws constitute privileged communication;');
            $section->addTextBreak(0.3);

            // Item 2.d
            $item2d = $section->addTextRun('subItem');
            $item2d->addText('d. proprietary information such as trade secrets, confidential research data, information the disclosure of which would prejudice intellectual property rights;');
            $section->addTextBreak(0.3);

            // Item 2.e
            $item2e = $section->addTextRun('subItem');
            $item2e->addText('e. confidential information pertaining to ' . BranchHelper::getMainBranchCode() . ' operations such as transcripts/recordings of meetings, internal reports, internal memoranda, drafts of decisions as well as other information that are exceptions to the right to freedom of information under the IRR of RA 6713;');
            $section->addTextBreak(0.3);

            // Item 2.f
            $item2f = $section->addTextRun('subItem');
            $item2f->addText('f. usernames, passwords, access codes and the like;');
            $section->addTextBreak(0.3);

            // Item 2.g
            $item2g = $section->addTextRun('subItem');
            $item2g->addText('g. information that is confidential under other applicable laws;');
            $section->addTextBreak(0.3);

            // Item 2.h
            $item2h = $section->addTextRun('subItem');
            $item2h->addText('h. information obtained by the agency from third parties under non-disclosure agreements or any other contract that designates third party information as confidential');
            $section->addTextBreak(0.5);

            // Item 3
            $item3 = $section->addTextRun('numberedItem');
            $item3->addText('3. ', ['bold' => true]);
            $item3->addText('I undertake that I shall:');
            $section->addTextBreak(0.3);

            // Item 3.a
            $item3a = $section->addTextRun('subItem');
            $item3a->addText('a. process or perform operations on confidential information including, but not limited to access, collection, reproduction, recording, organization, storage, updating or modification, retrieval, consultation, use, disclosure, consolidation, blocking, erasure or destruction only if reasonably necessary to fulfill my duties and the processing is allowed under applicable laws such as the DPA and the Code of Conduct and Ethical Standards for Public Officials and Employees;');
            $section->addTextBreak(0.3);

            // Item 3.b
            $item3b = $section->addTextRun('subItem');
            $item3b->addText('b. Under the DPA, the processing of personal information shall be permitted only if not otherwise prohibited by law, and when at least one of the following conditions exists:');
            $section->addTextBreak(0.2);

            // Item 3.b.i-vi (roman numerals)
            $item3bi = $section->addTextRun('subSubItem');
            $item3bi->addText('i. The data subject has given his or her consent;');
            $section->addTextBreak(0.2);

            $item3bii = $section->addTextRun('subSubItem');
            $item3bii->addText('ii. The processing of personal information is necessary and is related to the fulfillment of a contract with the data subject or in order to take steps at the request of the data subject prior to entering into a contract;');
            $section->addTextBreak(0.2);

            $item3biii = $section->addTextRun('subSubItem');
            $item3biii->addText('iii. The processing is necessary for compliance with a legal obligation to which the personal information controller is subject;');
            $section->addTextBreak(0.2);

            $item3biv = $section->addTextRun('subSubItem');
            $item3biv->addText('iv. The processing is necessary to protect vitally important interests of the data subject, including life and health;');
            $section->addTextBreak(0.2);

            $item3bv = $section->addTextRun('subSubItem');
            $item3bv->addText('v. The processing is necessary in order to respond to national emergency, to comply with the requirements of public order and safety, or to fulfill functions of public authority which necessarily includes the processing of personal data for the fulfillment of its mandate; or');
            $section->addTextBreak(0.2);

            $item3bvi = $section->addTextRun('subSubItem');
            $item3bvi->addText('vi. The processing is necessary for the purposes of the legitimate interests pursued by the personal information controller or by a third party or parties to whom the data is disclosed, except where such interests are overridden by fundamental rights and freedoms of the data subject which require protection under the Philippine Constitution.');
            $section->addTextBreak(0.3);

            // Item 3.c
            $item3c = $section->addTextRun('subItem');
            $item3c->addText('c. Under the DPA, the processing of sensitive personal information and privileged information shall be prohibited, except in the following cases:');
            $section->addTextBreak(0.2);

            // Item 3.c.i-vi (roman numerals)
            $item3ci = $section->addTextRun('subSubItem');
            $item3ci->addText('i. The data subject has given his or her consent, specific to the purpose prior to the processing, or in the case of privileged information, all parties to the exchange have given their consent prior to processing;');
            $section->addTextBreak(0.2);

            $item3cii = $section->addTextRun('subSubItem');
            $item3cii->addText('ii. The processing of the same is provided for by existing laws and regulations: Provided, that such regulatory enactments guarantee the protection of the sensitive personal information and the privileged information: Provided, further, That the consent of the data subjects are not required by law or regulation permitting the processing of the sensitive personal information or the privileged information;');
            $section->addTextBreak(0.2);

            $item3ciii = $section->addTextRun('subSubItem');
            $item3ciii->addText('iii. The processing is necessary to protect the life and health of the data subject or another person, and the data subject is not legally or physically able to express his or her consent prior to the processing;');
            $section->addTextBreak(0.2);

            $item3civ = $section->addTextRun('subSubItem');
            $item3civ->addText('iv. The processing is necessary to achieve the lawful and noncommercial objectives of public organizations and their associations: Provided, That such processing is only confined and related to the bona fide members of these organizations or their associations: Provided, further, That the sensitive personal information are not transferred to third parties: Provided, finally, That consent of the data subject was obtained prior to processing;');
            $section->addTextBreak(0.2);

            $item3cv = $section->addTextRun('subSubItem');
            $item3cv->addText('v. The processing is necessary for purposes of medical treatment, is carried out by a medical practitioner or a medical treatment institution, and an adequate level of protection of personal information is ensured; or');
            $section->addTextBreak(0.2);

            $item3cvi = $section->addTextRun('subSubItem');
            $item3cvi->addText('vi. The processing concerns such personal information as is necessary for the protection of lawful rights and interests of natural or legal persons in court proceedings, or the establishment, exercise or defense of legal claims, or when provided to government or public authority.');
            $section->addTextBreak(0.3);

            // Item 3.d
            $item3d = $section->addTextRun('subItem');
            $item3d->addText('d. consult and seek guidance from relevant ' . BranchHelper::getMainBranchCode() . ' offices in the event I am unsure of whether I am authorized to process or perform operations (access, copy use, disclose etc as stated in 3.a. above) on confidential information;');
            $section->addTextBreak(0.3);

            // Item 3.e
            $item3e = $section->addTextRun('subItem');
            $item3e->addText('e. exercise due diligence in safeguarding the confidentiality of such information by preventing unauthorized processing of  such information by others such as by locking or logging off the computer when not in use, not leaving the  office unattended or unlocked, keeping  hard copies of Confidential Information in a secure place (e.g., locked drawer or cabinet) when not in active use, shredding  such hard copies when no longer needed in accordance with instructions given by the proper official,  Agency policy, or any applicable contractual agreement or law;');
            $section->addTextBreak(0.3);

            // Item 3.f
            $item3f = $section->addTextRun('subItem');
            $item3f->addText('f. report any unauthorized or accidental processing of Confidential Information to the proper office;');
            $section->addTextBreak(0.3);

            // Item 3.g
            $item3g = $section->addTextRun('subItem');
            $item3g->addText('g. report the unlawful or accidental processing of personal or sensitive personal information to the proper head of office and data protection officer;');
            $section->addTextBreak(0.3);

            // Item 3.h
            $item3h = $section->addTextRun('subItem');
            $item3h->addText('h. return and/or destroy all Confidential Information and make the appropriate certification regarding the return and/or destruction of such information when requested by ' . BranchHelper::getMainBranchCode() . ' to do so;');
            $section->addTextBreak(0.3);

            // Item 3.i
            $item3i = $section->addTextRun('subItem');
            $item3i->addText('i. comply with all agency policies and procedures applicable to Confidential Information such as the Acceptable IT Use Policy for Information Technology of the ' . BranchHelper::getMainBranchCode() . ';');
            $section->addTextBreak(0.3);

            // Item 3.j - Last item (Blade template shows 9 items a-i, but this is the 10th item)
            $item3j = $section->addTextRun('subItem');
            $item3j->addText('j. not act for personal gain or to the detriment of ' . BranchHelper::getMainBranchCode() . ' based on Confidential Information to which I have access or which is in my possession.');
            $section->addTextBreak(0.5);

            // Item 4
            $item4 = $section->addTextRun('numberedItem');
            $item4->addText('4. ', ['bold' => true]);
            $item4->addText('I agree that my obligations pursuant to this undertaking apply to Confidential Information that I came across or had access to from the time my employment or engagement with ' . BranchHelper::getMainBranchCode() . ' commenced and that such obligations will survive the tenure of my employment/engagement with ' . BranchHelper::getMainBranchCode() . ';');
            $section->addTextBreak(0.3);

            // Item 5
            $item5 = $section->addTextRun('numberedItem');
            $item5->addText('5. ', ['bold' => true]);
            $item5->addText('I agree that in the event I previously executed a confidentiality or non-disclosure agreement or undertaking in favor of ' . BranchHelper::getMainBranchCode() . ' that the obligations contained in this undertaking are in addition to those contained in such prior agreement or undertaking.');
            $section->addTextBreak(0.3);

            // Item 6
            $item6 = $section->addTextRun('numberedItem');
            $item6->addText('6. ', ['bold' => true]);
            $item6->addText('I understand that if I fail to comply with this undertaking, such violation may be a ground for ' . BranchHelper::getMainBranchCode() . ' to take appropriate disciplinary and/or legal action against me. I am also aware that the DPA provides for criminal penalties (imprisonment and a fine) for unauthorized processing of personal and sensitive personal information.');
            $section->addTextBreak(1);

            // IN WITNESS WHEREOF
            $witnessPara = $section->addTextRun('contractBody');
            $witnessPara->addText('IN WITNESS WHEREOF, I have affixed my signature to this Agreement this ');
            $witnessPara->addText($formattedDate);
            $witnessPara->addText(' at ' . CompanyHelper::getAddress() . '.');
            $section->addTextBreak(2);

            // Employee signature - sanitize data
            $signatureEmployeeName = htmlspecialchars(strtoupper((string)($employee->full_name ?? 'N/A')), ENT_XML1, 'UTF-8');
            $signatureEmployeeName = str_replace(["\0", "\r"], '', $signatureEmployeeName);
            $signatureEmployeePosition = htmlspecialchars((string)($employee->position_name ?? 'N/A'), ENT_XML1, 'UTF-8');
            $signatureEmployeePosition = str_replace(["\0", "\r"], '', $signatureEmployeePosition);
            
            $section->addText($signatureEmployeeName, ['bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER, 'spaceAfter' => 40]);
            $section->addText($signatureEmployeePosition, [], ['alignment' => WordJc::CENTER]);
            $section->addTextBreak(2);

            // WITNESSED BY
            $section->addText('WITNESSED BY:', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 240]);
            $section->addTextBreak(1);

            // Signatory signature - sanitize data
            $signatureSignatoryName = htmlspecialchars(strtoupper((string)($signatory->full_name ?? 'N/A')), ENT_XML1, 'UTF-8');
            $signatureSignatoryPosition = htmlspecialchars((string)($signatory->position_name ?? 'N/A'), ENT_XML1, 'UTF-8');
            $signatureSignatoryName = str_replace(["\0", "\r"], '', $signatureSignatoryName);
            $signatureSignatoryPosition = str_replace(["\0", "\r"], '', $signatureSignatoryPosition);
            
            $section->addText($signatureSignatoryName, ['bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER, 'spaceAfter' => 40]);
            $section->addText($signatureSignatoryPosition, [], ['alignment' => WordJc::CENTER]);

            // Save and return
            $filename = 'cos_non_disclosure_agreement_' . $employee->employee_no . '_' . date('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate COS NDA DOCX: ' . $e->getMessage());
        }
    }
}
