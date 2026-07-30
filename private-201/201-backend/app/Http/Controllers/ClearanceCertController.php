<?php

namespace App\Http\Controllers;

use PDF;
use Illuminate\Http\Request;             // HTTP request data
use Illuminate\Support\Facades\DB;       // raw queries or DB::statement
use Illuminate\Support\Facades\Log;      // logging
use Illuminate\Support\Facades\Auth;     // current user
use App\Traits\ApiResponse;             // API response trait
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;

class ClearanceCertController extends Controller
{

    use ApiResponse;

    /**
     * List clearance certificates with basic employee info.
     */
    public function index()
    {
        try {
            $records = DB::table('clearance_cert as cc')
                ->leftJoin('employees as e', 'e.id', '=', 'cc.employee_id')
                ->leftJoin('clearance_purpose as p', 'p.id', '=', 'cc.purpose_id')
                ->select(
                    'cc.id',
                    'cc.employee_id',
                    DB::raw("CONCAT(e.first_name, ' ', e.last_name) as employee_name"),
                    'cc.Date_of_filing',
                    'cc.Date_of_effectivity',
                    'p.name as purpose_name',
                    'cc.Other_purpose',
                    'cc.is_cleared',
                    'cc.with_pending_administrative',
                    'cc.with_ongoing_investigation',
                    'cc.created_at'
                )
                ->orderByDesc('cc.created_at')
                ->get();

            return $this->successResponse($records, 'Clearance certificates retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to list clearance certificates', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve clearance certificates.');
        }
    }

    /**
     * Load employees for the clearance certificate dropdown.
     */
    public function employees()
    {
        try {
            $employees = DB::table('employees as a')
                ->select(
                    'a.id',
                    'a.first_name as name',
                    'b.name as position_name',
                    'c.name as salary_grade_name',
                    'd.name as salary_step_name'
                )
                ->leftJoin('positions as b', 'b.id', '=', 'a.position_id')
                ->leftJoin('salary_grades as c', 'c.id', '=', 'a.salary_grade_id')
                ->leftJoin('salary_steps as d', 'd.id', '=', 'a.salary_step_id')
                ->orderBy('a.id')
                ->get();

            return $this->successResponse($employees, 'Employees retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to load employees for clearance certificate', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve employees for clearance certificate.');
        }
    }

    /**
     * Load clearing officers (employees where active = 1 and is_employee = 1).
     */
    public function clearingOfficers()
    {
        try {
            $app_key = env("APP_KEY", "");
            
            $officers = DB::table('employees as e')
                ->select(
                    'e.id',
                    DB::raw("CASE 
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(ISNULL(e.first_name,''),' ',ISNULL(e.middle_name,''),' ',ISNULL(e.last_name,''))
                        ELSE
                            ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')),'')+' '+
                            ISNULL(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')),'')+' '+
                            ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')),'')
                        END as name"),
                    'pos.name as position_name'
                )
                ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
                ->where('e.active', 1)
                ->where('e.is_employee', 1)
                ->orderBy('e.first_name')
                ->orderBy('e.last_name')
                ->get();

            return $this->successResponse($officers, 'Clearing officers retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to load clearing officers', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve clearing officers.');
        }
    }

    /**
     * Load purposes for the clearance certificate dropdown.
     */
    public function purposes()
    {
        try {
            $purposes = DB::table('clearance_purpose')
                ->select('id', 'name', 'active')
                ->where('active', 1)
                ->orderBy('name')
                ->get();

            return $this->successResponse($purposes, 'Clearance purposes retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to load clearance purposes', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve clearance purposes.');
        }
    }

    /**
     * Store a new clearance certificate record.
     */
    public function store(Request $request)
    {
        // Basic validation
        $validated = $request->validate([
            'employee_id'                => 'required|integer|exists:employees,id',
            'Date_of_filing'            => 'required|date',
            'Date_of_effectivity'       => 'required|date',
            'purpose_id'                => 'nullable|integer|exists:clearance_purpose,id',
            'Other_purpose'             => 'nullable|string|max:250',
            'is_cleared'                => 'boolean',
            'with_pending_administrative' => 'boolean',
            'with_ongoing_investigation'  => 'boolean',
        ]);

        try {
            $id = DB::table('clearance_cert')->insertGetId([
                'employee_id'                => $validated['employee_id'],
                'Date_of_filing'            => $validated['Date_of_filing'],
                'Date_of_effectivity'       => $validated['Date_of_effectivity'],
                'purpose_id'                => $validated['purpose_id'] ?? null,
                'Other_purpose'             => $validated['Other_purpose'] ?? null,
                'is_cleared'                => $validated['is_cleared'] ?? false,
                'with_pending_administrative' => $validated['with_pending_administrative'] ?? false,
                'with_ongoing_investigation'  => $validated['with_ongoing_investigation'] ?? false,
                'created_at'                => now(),
                'updated_at'                => now(),
            ]);

            $record = DB::table('clearance_cert')->where('id', $id)->first();

            return $this->successResponse($record, 'Clearance certificate created successfully.', 201);
        } catch (\Exception $e) {
            Log::error('Failed to create clearance certificate', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return $this->serverErrorResponse('Failed to create clearance certificate.');
        }
    }

    /**
     * Generate clearance certificate PDF for preview or download.
     */
    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if (!$request->id) {
                return $this->errorResponse('Clearance certificate ID is required.', 400);
            }

            // Get clearance certificate data
            $clearance = DB::table('clearance_cert as cc')
                ->leftJoin('employees as e', 'e.id', '=', 'cc.employee_id')
                ->leftJoin('clearance_purpose as p', 'p.id', '=', 'cc.purpose_id')
                ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
                ->leftJoin('salary_grades as sg', 'sg.id', '=', 'e.salary_grade_id')
                ->leftJoin('salary_steps as ss', 'ss.id', '=', 'e.salary_step_id')
                ->leftJoin('companies as c', 'c.id', '=', 'e.company_id')
                ->leftJoin('departments as dept', 'dept.id', '=', 'e.department_id')
                ->select(
                    'cc.id',
                    'cc.employee_id',
                    'cc.Date_of_filing',
                    'cc.Date_of_effectivity',
                    'cc.purpose_id',
                    'p.name as purpose_name',
                    'cc.Other_purpose',
                    'cc.is_cleared',
                    'cc.with_pending_administrative',
                    'cc.with_ongoing_investigation',
                    DB::raw("CASE 
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(ISNULL(e.first_name,''),' ',ISNULL(e.middle_name,''),' ',ISNULL(e.last_name,''))
                        ELSE
                            ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')),'')+' '+
                            ISNULL(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')),'')+' '+
                            ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')),'')
                        END as employee_name"),
                    'pos.name as position',
                    'sg.name as salary_grade',
                    'ss.name as salary_step',
                    'e.department_id',
                    'e.salary_grade_id',
                    'e.salary_step_id',
                    'c.name as company_name',
                    'dept.code as department_code'
                )
                ->where('cc.id', $request->id)
                ->first();

            if (!$clearance) {
                return $this->errorResponse('Clearance certificate not found.', 404);
            }

            // Get employee name (already decrypted in query)
            $employee_name = trim($clearance->employee_name ?? '');

            $company = DB::table('companies')->where('id', 0)->first();
            $company_name = $company->name ?? ($clearance->company_name ?? '');

            // Get division head name separately
            $division_head = '';
            if ($clearance->department_id) {
                try {
                    $dept = DB::table('departments')->where('id', $clearance->department_id)->first();
                    if ($dept && isset($dept->division_id) && $dept->division_id) {
                        $division = DB::table('divisions')->where('id', $dept->division_id)->first();
                        $division_head = $division->name ?? '';
                    }
                } catch (\Exception $e) {
                    // Division not found, leave empty
                    Log::warning('Failed to get division head', ['error' => $e->getMessage()]);
                }
            }

            // Format dates
            $date_of_filing = $clearance->Date_of_filing ? date('F d, Y', strtotime($clearance->Date_of_filing)) : '';
            $date_of_effectivity = $clearance->Date_of_effectivity ? date('F d, Y', strtotime($clearance->Date_of_effectivity)) : '';

            // Format Position/SG/Step
            $position_sg_step_parts = [];
            if ($clearance->position) {
                $position_sg_step_parts[] = $clearance->position;
            }
            if ($clearance->salary_grade_id) {
                $position_sg_step_parts[] = $clearance->salary_grade_id;
            }
            if ($clearance->salary_step_id) {
                $position_sg_step_parts[] = $clearance->salary_step_id;
            }
            $position_sg_step = implode(' / ', $position_sg_step_parts);

            // Get clearing officer names from request (passed from frontend localStorage cache)
            $clearing_officer_supply_property = $request->input('clearing_officer_supply_property', '');
            $clearing_officer_hr_welfare = $request->input('clearing_officer_hr_welfare', '');
            $clearing_officer_union_cooperative = $request->input('clearing_officer_union_cooperative', '');
            $clearing_officer_library = $request->input('clearing_officer_library', '');
            $clearing_officer_legal_library = $request->input('clearing_officer_legal_library', '');
            $clearing_officer_library_services = $request->input('clearing_officer_library_services', '');
            $clearing_officer_financial_services = $request->input('clearing_officer_financial_services', '');
            $clearing_officer_transaction_billing = $request->input('clearing_officer_transaction_billing', '');
            $clearing_officer_payroll_remittance = $request->input('clearing_officer_payroll_remittance', '');
            $clearing_officer_professional_development = $request->input('clearing_officer_professional_development', '');
            $clearing_officer_scholarship = $request->input('clearing_officer_scholarship', '');
            $clearing_officer_internal_affairs = $request->input('clearing_officer_internal_affairs', '');

            // Prepare data for blade template
            $data = [
                'date_of_filing' => $date_of_filing,
                'date_of_effectivity' => $date_of_effectivity,
                'purpose_id' => $clearance->purpose_id,
                'purpose_name' => $clearance->purpose_name,
                'other_purpose' => $clearance->Other_purpose,
                'employee_name' => $employee_name,
                'position' => $clearance->position ?? '',
                'salary_grade' => $clearance->salary_grade ?? '',
                'salary_step' => $clearance->salary_step ?? '',
                'is_cleared' => $clearance->is_cleared ?? false,
                'with_pending_administrative' => $clearance->with_pending_administrative ?? false,
                'with_ongoing_investigation' => $clearance->with_ongoing_investigation ?? false,
                'division_head' => $division_head,
                'head_of_office' => 'CLARE MARI S. TORRALBA',
                'company_name' => $company_name,
                'office_assignment' => $clearance->department_code ?? '',
                'position_sg_step' => $position_sg_step,
                // Clearing officers (from request parameters)
                'clearing_officer_supply_property' => $clearing_officer_supply_property,
                'clearing_officer_hr_welfare' => $clearing_officer_hr_welfare,
                'clearing_officer_union_cooperative' => $clearing_officer_union_cooperative,
                'clearing_officer_library' => $clearing_officer_library,
                'clearing_officer_legal_library' => $clearing_officer_legal_library,
                'clearing_officer_library_services' => $clearing_officer_library_services,
                'clearing_officer_financial_services' => $clearing_officer_financial_services,
                'clearing_officer_transaction_billing' => $clearing_officer_transaction_billing,
                'clearing_officer_payroll_remittance' => $clearing_officer_payroll_remittance,
                'clearing_officer_professional_development' => $clearing_officer_professional_development,
                'clearing_officer_scholarship' => $clearing_officer_scholarship,
                'clearing_officer_internal_affairs' => $clearing_officer_internal_affairs,
            ];

            $pdf = PDF::loadView('employee_certificates.employee_clearance_form', $data)
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'portrait');
            $pdfContent = $pdf->output();

            $filename = 'clearance_form_' . $clearance->employee_id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            Log::error('Failed to generate clearance certificate PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id ?? null
            ]);
            return $this->serverErrorResponse('Failed to generate clearance certificate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate clearance certificate as DOCX using PhpWord.
     */
    public function word(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if (!$request->id) {
                return $this->errorResponse('Clearance certificate ID is required.', 400);
            }

            // Get clearance certificate data (same as in print)
            $clearance = DB::table('clearance_cert as cc')
                ->leftJoin('employees as e', 'e.id', '=', 'cc.employee_id')
                ->leftJoin('clearance_purpose as p', 'p.id', '=', 'cc.purpose_id')
                ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
                ->leftJoin('salary_grades as sg', 'sg.id', '=', 'e.salary_grade_id')
                ->leftJoin('salary_steps as ss', 'ss.id', '=', 'e.salary_step_id')
                ->leftJoin('companies as c', 'c.id', '=', 'e.company_id')
                ->leftJoin('departments as dept', 'dept.id', '=', 'e.department_id')
                ->select(
                    'cc.id',
                    'cc.employee_id',
                    'cc.Date_of_filing',
                    'cc.Date_of_effectivity',
                    'cc.purpose_id',
                    'p.name as purpose_name',
                    'cc.Other_purpose',
                    'cc.is_cleared',
                    'cc.with_pending_administrative',
                    'cc.with_ongoing_investigation',
                    DB::raw("CASE 
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(ISNULL(e.first_name,''),' ',ISNULL(e.middle_name,''),' ',ISNULL(e.last_name,''))
                        ELSE
                            ISNULL(RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')),'')+' '+
                            ISNULL(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')),'')+' '+
                            ISNULL(RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')),'')
                        END as employee_name"),
                    'pos.name as position',
                    'sg.name as salary_grade',
                    'ss.name as salary_step',
                    'e.department_id',
                    'e.salary_grade_id',
                    'e.salary_step_id',
                    'c.name as company_name',
                    'dept.code as department_code'
                )
                ->where('cc.id', $request->id)
                ->first();

            if (!$clearance) {
                return $this->errorResponse('Clearance certificate not found.', 404);
            }

            $employee_name = trim($clearance->employee_name ?? '');

            // Get division head name
            $division_head = '';
            if ($clearance->department_id) {
                try {
                    $dept = DB::table('departments')->where('id', $clearance->department_id)->first();
                    if ($dept && isset($dept->division_id) && $dept->division_id) {
                        $division = DB::table('divisions')->where('id', $dept->division_id)->first();
                        $division_head = $division->name ?? '';
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to get division head for DOCX', ['error' => $e->getMessage()]);
                }
            }

            // Format dates
            $date_of_filing = $clearance->Date_of_filing ? Carbon::parse($clearance->Date_of_filing)->format('F d, Y') : '';
            $date_of_effectivity = $clearance->Date_of_effectivity ? Carbon::parse($clearance->Date_of_effectivity)->format('F d, Y') : '';

            // Position / SG / Step
            $position_sg_step_parts = [];
            if ($clearance->position) {
                $position_sg_step_parts[] = $clearance->position;
            }
            if ($clearance->salary_grade_id) {
                $position_sg_step_parts[] = $clearance->salary_grade_id;
            }
            if ($clearance->salary_step_id) {
                $position_sg_step_parts[] = $clearance->salary_step_id;
            }
            $position_sg_step = implode(' / ', $position_sg_step_parts);

            // Clearing officers (mirrors print() request inputs)
            $clearing_officer_supply_property = $request->input('clearing_officer_supply_property', '');
            $clearing_officer_hr_welfare = $request->input('clearing_officer_hr_welfare', '');
            $clearing_officer_union_cooperative = $request->input('clearing_officer_union_cooperative', '');
            $clearing_officer_legal_library = $request->input('clearing_officer_legal_library', '');
            $clearing_officer_library_services = $request->input('clearing_officer_library_services', '');
            $clearing_officer_financial_services = $request->input('clearing_officer_financial_services', '');
            $clearing_officer_transaction_billing = $request->input('clearing_officer_transaction_billing', '');
            $clearing_officer_payroll_remittance = $request->input('clearing_officer_payroll_remittance', '');
            $clearing_officer_scholarship = $request->input('clearing_officer_scholarship', '');
            $clearing_officer_internal_affairs = $request->input('clearing_officer_internal_affairs', '');

            $company = DB::table('companies')->where('id', 0)->first();
            $company_name = $company->name ?? ($clearance->company_name ?? '');
            $office_assignment = $clearance->department_code ?? '';
            $head_of_office = 'CLARE MARI S. TORRALBA';

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(8);

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(0.6),
                'marginRight' => Converter::inchToTwip(0.75),
                'marginBottom' => Converter::inchToTwip(0.6),
                'marginLeft' => Converter::inchToTwip(0.75),
                'pageSizeW' => Converter::inchToTwip(8.27),
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

            // Basic styles
            $phpWord->addParagraphStyle('center', ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            $phpWord->addParagraphStyle('left', ['alignment' => WordJc::START, 'spaceAfter' => 0]);
            $phpWord->addParagraphStyle('sectionTitle', ['alignment' => WordJc::START, 'spaceAfter' => 120]);

            // CS Form header
            $section->addText('CS Form No. 7', [], 'left');
            $section->addText('Revised 2018', [], 'left');

            // Title block
            $section->addText($company_name, ['bold' => true, 'size' => 10], 'center');
            $section->addText('CLEARANCE FORM', ['bold' => true, 'size' => 10], 'center');
            $section->addText('(Instructions at the back)', ['size' => 8], 'center');
            $section->addTextBreak(1);

            // Create main table with all sections
            $mainTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 0,
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 10,
                'cellMarginRight' => 10,
            ]);

            // I. PURPOSE
            $mainTable->addRow();
            $cellI = $mainTable->addCell();
            $cellI->addText('I.  PURPOSE', ['bold' => true], 'sectionTitle');

            $purposeLine = $cellI->addTextRun('left');
            $purposeLine->addText('TO: ');
            $purposeLine->addText($company_name, ['bold' => true]);

            $cellI->addText(
                'I hereby request clearance from money, property and work-related accountabilities for:',
                [],
                'left'
            );

            // Purpose checkboxes
            $purposeLine1 = $cellI->addTextRun('left');
            $purposeLine1->addText('Purpose:  ');
            $purposeLine1->addText('[' . ($clearance->purpose_name === 'Transfer' ? 'X' : ' ') . '] Transfer  ');
            $purposeLine1->addText('[' . ($clearance->purpose_name === 'Resignation' ? 'X' : ' ') . '] Resignation  ');
            $purposeLine1->addText('[' . (!empty($clearance->Other_purpose) ? 'X' : ' ') . '] Other Mode of Separation');

            $cellI->addTextBreak(0.3);

            $purposeLine2 = $cellI->addTextRun('left');
            $purposeLine2->addText('          ');
            $purposeLine2->addText('[' . ($clearance->purpose_name === 'Retirement' ? 'X' : ' ') . '] Retirement  ');
            $purposeLine2->addText('[' . ($clearance->purpose_name === 'Leave' ? 'X' : ' ') . '] Leave  ');
            $purposeLine2->addText('Please specify: ');
            $purposeLine2->addText($clearance->Other_purpose ?? '', ['underline' => 'single']);

            $cellI->addTextBreak(0.5);

            $cellI->addText('Date of Filing: ' . $date_of_filing, [], 'left');
            $cellI->addText('Date of Effectivity: ' . $date_of_effectivity, [], 'left');

            // Employee and office info
            $tableInfo = $cellI->addTable([
                'borderSize' => 0,
                'cellMargin' => 0,
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 10,
                'cellMarginRight' => 10,
            ]);
            $tableInfo->addRow();
            $cellOffice = $tableInfo->addCell(Converter::inchToTwip(3.5));
            $cellEmp = $tableInfo->addCell(Converter::inchToTwip(3.5));

            $cellOffice->addText('Office of Assignment: ' . $office_assignment);
            $cellOffice->addTextBreak(0.5);
            $cellOffice->addText('Position/SG/Step: ' . $position_sg_step);

            $cellEmp->addText($employee_name, ['bold' => true], ['alignment' => WordJc::CENTER]);
            $cellEmp->addText('Name and Signature of Employee', [], ['alignment' => WordJc::CENTER]);

            // II. CLEARANCE FROM WORK-RELATED ACCOUNTABILITIES
            $mainTable->addRow();
            $cellII = $mainTable->addCell();
            $cellII->addText(
                'II.  CLEARANCE FROM WORK-RELATED ACCOUNTABILITIES',
                ['bold' => true],
                'sectionTitle'
            );

            $workLine = $cellII->addTextRun('left');
            $workLine->addText('We hereby certify that this employee is ');
            $workLine->addText('Cleared [' . ($clearance->is_cleared ? 'X' : ' ') . ']  ');
            $workLine->addText('Not Cleared [' . ($clearance->is_cleared ? ' ' : 'X') . '] ');
            $workLine->addText('from work-related accountabilities from this Unit/Office/Dept.');

            // Signatories (Immediate Supervisor / Head of Office)
            $signTable = $cellII->addTable([
                'borderSize' => 0,
                'cellMargin' => 0,
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 10,
                'cellMarginRight' => 10,
            ]);
            $signTable->addRow();
            $cellDivHead = $signTable->addCell(Converter::inchToTwip(3.5));
            $cellHeadOffice = $signTable->addCell(Converter::inchToTwip(3.5));

            $cellDivHead->addText($division_head, ['bold' => true], ['alignment' => WordJc::CENTER]);
            $cellHeadOffice->addText($head_of_office, ['bold' => true], ['alignment' => WordJc::CENTER]);
            $signTable->addRow();
            $cellDivHead2 = $signTable->addCell(Converter::inchToTwip(3.5));
            $cellHeadOffice2 = $signTable->addCell(Converter::inchToTwip(3.5));
            $cellDivHead2->addText('Immediate Supervisor', [], ['alignment' => WordJc::CENTER]);
            $cellHeadOffice2->addText('Head of Office', [], ['alignment' => WordJc::CENTER]);

            // III. CLEARANCE FROM MONEY AND PROPERTY ACCOUNTABILITIES
            $mainTable->addRow();
            $cellIII = $mainTable->addCell();
            $cellIII->addText(
                'III.  CLEARANCE FROM MONEY AND PROPERTY ACCOUNTABILITIES',
                ['bold' => true],
                'sectionTitle'
            );

            $mpTable = $cellIII->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 0,
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 10,
                'cellMarginRight' => 10,
            ]);
            $mpTable->addRow();
            $mpTable->addCell(Converter::inchToTwip(2.5))->addText('Name of Unit/Office/Department', ['bold' => true]);
            $mpTable->addCell(Converter::inchToTwip(0.8))->addText('Cleared', ['bold' => true]);
            $mpTable->addCell(Converter::inchToTwip(1))->addText('Not Cleared', ['bold' => true]);
            $mpTable->addCell(Converter::inchToTwip(2.2))->addText('Name of Clearing Officer/Official', ['bold' => true]);
            $mpTable->addCell(Converter::inchToTwip(1))->addText('Signature', ['bold' => true]);

            // 1. Administrative Services
            $mpTable->addRow();
            $mpTable->addCell()->addText('1. Administrative Services', ['bold' => true]);
            $cell1 = $mpTable->addCell();
            $cell1->getStyle()->setBorderRightSize(0);
            $cell2 = $mpTable->addCell();
            $cell2->getStyle()->setBorderRightSize(0);
            $cell3 = $mpTable->addCell();
            $cell3->getStyle()->setBorderRightSize(0);
            $cell4 = $mpTable->addCell();
            $cell4->getStyle()->setBorderRightSize(0);


            $mpTable->addRow();
            $mpTable->addCell()->addText('   a. Supply and Property Procurement and Management Services');
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell()->addText($clearing_officer_supply_property);
            $mpTable->addCell();

            $mpTable->addRow();
            $mpTable->addCell()->addText('   b. Human Resources Welfare and Assistance');
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell()->addText($clearing_officer_hr_welfare);
            $mpTable->addCell();

            $mpTable->addRow();
            $mpTable->addCell()->addText('   c. Agency-Accredited Union/Cooperative');
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell()->addText($clearing_officer_union_cooperative);
            $mpTable->addCell();

            // 2. Library
            $mpTable->addRow();
            $mpTable->addCell()->addText('2. Library', ['bold' => true]);
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell();

            $mpTable->addRow();
            $mpTable->addCell()->addText('   a. Legal Office Library');
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell()->addText($clearing_officer_legal_library);
            $mpTable->addCell();

            $mpTable->addRow();
            $mpTable->addCell()->addText('   b. Library Services');
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell()->addText($clearing_officer_library_services);
            $mpTable->addCell();

            // 3. Finance and Assets Management
            $mpTable->addRow();
            $mpTable->addCell()->addText('3. Finance and Assets Management', ['bold' => true]);
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell();

            $mpTable->addRow();
            $mpTable->addCell()->addText('   a. Financial Services');
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell()->addText($clearing_officer_financial_services);
            $mpTable->addCell();

            $mpTable->addRow();
            $mpTable->addCell()->addText('   b. Transaction, Processing and Billing Services');
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell()->addText($clearing_officer_transaction_billing);
            $mpTable->addCell();

            $mpTable->addRow();
            $mpTable->addCell()->addText('   c. Payroll and Remittance Services');
            $mpTable->addCell();    
            $mpTable->addCell();
            $mpTable->addCell()->addText($clearing_officer_payroll_remittance);
            $mpTable->addCell();

            // 4. Professional and Institutional Development
            $mpTable->addRow();
            $mpTable->addCell()->addText('4. Professional and Institutional Development', ['bold' => true]);
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell();

            $mpTable->addRow();
            $mpTable->addCell()->addText('   a. Scholarship Services');
            $mpTable->addCell();
            $mpTable->addCell();
            $mpTable->addCell()->addText($clearing_officer_scholarship);
            $mpTable->addCell();

            // IV. CERTIFICATION OF NO PENDING ADMINISTRATIVE CASE
            $mainTable->addRow();
            $cellIV = $mainTable->addCell();
            $cellIV->addText(
                'IV.  CERTIFICATION OF NO PENDING ADMINISTRATIVE CASE',
                ['bold' => true],
                'sectionTitle'
            );

            $adminTable = $cellIV->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 0,
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 10,
                'cellMarginRight' => 10,
            ]);
            $adminTable->addRow();
            $adminTable->addCell(Converter::inchToTwip(3.5))->addText('Name of Unit/Office/Department', ['bold' => true]);
            $adminTable->addCell(Converter::inchToTwip(2.5))->addText('Name of Clearing Officer/Official', ['bold' => true]);
            $adminTable->addCell(Converter::inchToTwip(2.5))->addText('Signature', ['bold' => true]);

            $adminTable->addRow();
            $adminTable->addCell()->addText('a. Internal Affairs Office/Legal Affairs Office');
            $adminTable->addCell()->addText($clearing_officer_internal_affairs);
            $adminTable->addCell();

            $cellIV->addText(
                '[' . ($clearance->with_pending_administrative ? 'X' : ' ') . '] with no pending administrative case',
                [],
                'left'
            );
            $cellIV->addText(
                '[' . ($clearance->with_ongoing_investigation ? 'X' : ' ') . '] with ongoing investigation (no formal charge yet)',
                [],
                'left'
            );

            // V. CERTIFICATION
            $mainTable->addRow();
            $cellV = $mainTable->addCell();
            $cellV->addText('V.  CERTIFICATION', ['bold' => true], 'sectionTitle');

            $certText = 'I hereby certify that this employee is cleared of work-related, money and property '
                . 'accountabilities from this agency. This certification includes no pending administrative case '
                . 'from this agency.';
            $cellV->addText($certText, [], [
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 120,
            ]);

            $cellV->addTextBreak(1);

            // Final signatory
            $cellV->addText($head_of_office, ['bold' => true], ['alignment' => WordJc::CENTER]);
            $cellV->addText('Signature over Printed Name of Agency Head', [], ['alignment' => WordJc::CENTER]);

            $filename = 'clearance_form_' . $clearance->employee_id . '_' . date('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to generate clearance certificate DOCX', [
                'error' => $e->getMessage(),
                'request_id' => $request->id ?? null,
            ]);
            return $this->serverErrorResponse('Failed to generate clearance certificate DOCX: ' . $e->getMessage());
        }
    }

    /**
     * Delete a clearance certificate record.
     */
    public function destroy($id)
    {
        try {
            $clearance = DB::table('clearance_cert')->where('id', $id)->first();
            
            if (!$clearance) {
                return $this->notFoundResponse('Clearance certificate not found');
            }

            DB::table('clearance_cert')->where('id', $id)->delete();

            // Save audit trail
            try {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'Clearance Certificate',
                    'activity' => 'Delete',
                    'description' => 'Deleted clearance certificate record.',
                );
                DB::table('audit_trail')->insert($data_audit);
            } catch (\Exception $auditError) {
                // Don't block deletion if audit logging fails
                Log::warning('Failed to record audit trail for clearance delete', [
                    'error' => $auditError->getMessage(),
                    'id' => $id
                ]);
            }

            return $this->successResponse(['id' => $id], 'Clearance certificate deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete clearance certificate', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return $this->serverErrorResponse('Failed to delete clearance certificate.');
        }
    }
}
