<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;

class AssumptionOfDutyController extends Controller
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

            $employees = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employee_promotions', 'employees.id', '=', 'employee_promotions.employee_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select(
                    'employees.photo',
                    DB::raw("CASE WHEN ISNULL(employee_promotions.id,0) = 0 THEN
                                    employees.id
                                ELSE
                                    employee_promotions.id
                                END as id"),
                    'employees.employee_no',
                    'employees.email',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',substring(ISNULL(employees.middle_name,''),1,1),'. ',employees.last_name,' - ',ISNULL(positions.name,''))
                                ELSE
                                    CONCAT(
                                        RTRIM(ISNULL([dbo].[ufn_DecryptString](employees.first_name,'$app_key'),'')),
                                        ' ',
                                        UPPER(SUBSTRING(ISNULL([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),''),1,1)),
                                        '. ',
                                        RTRIM(ISNULL([dbo].[ufn_DecryptString](employees.last_name,'$app_key'),'')),
                                        ' - ',
                                        RTRIM(ISNULL(positions.name,''))
                                    )
                                END as name"),
                )
                ->where(['employees.is_employee' => true, 'employees.active' => true]);

            $applicants = DB::table('applicant_headers')
                ->leftJoin('applicant_details as ad', function ($join) {
                    $join->on('ad.applicant_id', '=', 'applicant_headers.id')
                        ->whereRaw("ad.id = (
                            SELECT TOP 1 ad2.id
                            FROM applicant_details as ad2
                            WHERE ad2.applicant_id = applicant_headers.id
                            ORDER BY CASE WHEN ISNULL(ad2.application_status_id, 0) IN (6,5) THEN 0 ELSE 1 END, ad2.id DESC
                        )");
                })
                ->leftJoin('plantillas as p', function ($join) {
                    $join->on('ad.position_applied_id', '=', 'p.id')
                        ->whereRaw('ISNULL(ad.is_plantilla, 1) = 1');
                })
                ->leftJoin('non_plantillas as np', function ($join) {
                    $join->on('ad.position_applied_id', '=', 'np.id')
                        ->whereRaw('ISNULL(ad.is_plantilla, 1) = 0');
                })
                ->leftJoin('positions as pos_p', 'p.position_id', '=', 'pos_p.id')
                ->leftJoin('positions as pos_np', 'np.position_id', '=', 'pos_np.id')
                ->leftJoin('departments as dep_p', 'p.department_id', '=', 'dep_p.id')
                ->leftJoin('departments as dep_np', 'np.department_id', '=', 'dep_np.id')
                ->leftJoin('employees as ae', function ($join) {
                    $join->on('ae.employee_no', '=', 'applicant_headers.employee_no')
                        ->where('ae.is_employee', true);
                })
                ->leftJoin('branches as br_ae', 'br_ae.id', '=', 'ae.branch_id')
                ->leftJoin('departments as dep_ae', 'dep_ae.id', '=', 'ae.department_id')
                ->leftJoin('positions as pos_ae', 'pos_ae.id', '=', 'ae.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'ae.employment_type_id')
                ->select(
                    'applicant_headers.photo',
                    DB::raw('-applicant_headers.id as id'),
                    'applicant_headers.employee_no',
                    'applicant_headers.email',
                    'employment_types.name as employment_type',
                    DB::raw('COALESCE(pos_np.name, pos_p.name, pos_ae.name) as position'),
                    DB::raw('COALESCE(dep_np.name, dep_p.name, dep_ae.name) as department'),
                    'br_ae.name as branch',
                    DB::raw("CONCAT(applicant_headers.first_name,' ',substring(applicant_headers.middle_name,1,1),'. ',applicant_headers.last_name,' - ',ISNULL(COALESCE(pos_np.name, pos_p.name, pos_ae.name),'')) as name"),
                );

            $data = DB::query()
                ->fromSub($employees->unionAll($applicants), 'records')
                ->orderBy('name', 'asc')
                ->paginate(10000);

            return $this->successResponse($data, 'Assumption of duty records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve assumption of duty records: ' . $e->getMessage());
        }
    }

    public function sendEmail(Request $request)
    {
        try {
            $employeeId = (int) $request->input('employee');
            if ($employeeId === 0) {
                return $this->errorResponse('Please select employee.', 400);
            }

            if ($employeeId < 0) {
                $recipient = DB::table('applicant_headers')
                    ->select('email', DB::raw("CONCAT(first_name, ' ', last_name) as name"))
                    ->where('id', abs($employeeId))
                    ->first();
            } else {
                $app_key = env("APP_KEY", "");
                $recipient = DB::table('employee_promotions as ep')
                    ->join('employees as e', 'e.id', '=', 'ep.employee_id')
                    ->select(
                        'e.email',
                        DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                    CONCAT(e.first_name, ' ', e.last_name)
                                ELSE
                                    CONCAT(
                                        RTRIM(ISNULL([dbo].[ufn_DecryptString](e.first_name,'$app_key'),'')),
                                        ' ',
                                        RTRIM(ISNULL([dbo].[ufn_DecryptString](e.last_name,'$app_key'),''))
                                    )
                                END as name")
                    )
                    ->where('ep.id', $employeeId)
                    ->first();

                if (!$recipient) {
                    $recipient = DB::table('employees as e')
                        ->select(
                            'e.email',
                            DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                        CONCAT(e.first_name, ' ', e.last_name)
                                    ELSE
                                        CONCAT(
                                            RTRIM(ISNULL([dbo].[ufn_DecryptString](e.first_name,'$app_key'),'')),
                                            ' ',
                                            RTRIM(ISNULL([dbo].[ufn_DecryptString](e.last_name,'$app_key'),''))
                                        )
                                    END as name")
                        )
                        ->where('e.id', $employeeId)
                        ->first();
                }
            }

            if (!$recipient || empty($recipient->email)) {
                return $this->errorResponse('Selected employee/applicant has no email address.', 400);
            }

            $pdfResponse = $this->print(new Request($request->all()));
            $pdfContent = $pdfResponse->getContent();

            $pdfPath = storage_path('app/temp_assumption_' . abs($employeeId) . '_' . time() . '.pdf');
            file_put_contents($pdfPath, $pdfContent);

            $emailData = ['name' => trim($recipient->name ?? 'Applicant')];
            \Notification::route('mail', $recipient->email)
                ->notify(new \App\Notifications\EmailAssumptionOfDuty($emailData, $pdfPath));

            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            return $this->successResponse(
                ['email' => $recipient->email],
                'Assumption of duty email sent successfully.'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to send assumption of duty email: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.', 400);
            }

            $employeeId = (int) $request->employee;
            $appointments = collect();

            if ($employeeId < 0) {
                $applicantId = abs($employeeId);
                $appointments = DB::table('applicant_headers as ah')
                    ->leftJoin('applicant_details as ad', function ($join) {
                        $join->on('ad.applicant_id', '=', 'ah.id')
                            ->whereRaw("ad.id = (
                                SELECT TOP 1 ad2.id
                                FROM applicant_details as ad2
                                WHERE ad2.applicant_id = ah.id
                                ORDER BY CASE WHEN ISNULL(ad2.application_status_id, 0) IN (6,5) THEN 0 ELSE 1 END, ad2.id DESC
                            )");
                    })
                    ->leftJoin('plantillas as p', function ($join) {
                        $join->on('ad.position_applied_id', '=', 'p.id')
                            ->whereRaw('ISNULL(ad.is_plantilla, 1) = 1');
                    })
                    ->leftJoin('non_plantillas as np', function ($join) {
                        $join->on('ad.position_applied_id', '=', 'np.id')
                            ->whereRaw('ISNULL(ad.is_plantilla, 1) = 0');
                    })
                    ->leftJoin('positions as pos_p', 'p.position_id', '=', 'pos_p.id')
                    ->leftJoin('positions as pos_np', 'np.position_id', '=', 'pos_np.id')
                    ->leftJoin('departments as dep_p', 'p.department_id', '=', 'dep_p.id')
                    ->leftJoin('departments as dep_np', 'np.department_id', '=', 'dep_np.id')
                    ->leftJoin('employees as ae', function ($join) {
                        $join->on('ae.employee_no', '=', 'ah.employee_no')
                            ->where('ae.is_employee', true);
                    })
                    ->leftJoin('positions as pos_ae', 'ae.position_id', '=', 'pos_ae.id')
                    ->leftJoin('departments as dep_ae', 'ae.department_id', '=', 'dep_ae.id')
                    ->select(
                        DB::raw('-ah.id as id'),
                        DB::raw("CONCAT(ah.first_name,' ',ah.last_name) as name"),
                        DB::raw("ISNULL(COALESCE(pos_np.name, pos_p.name, pos_ae.name), '') as position"),
                        DB::raw("ISNULL(COALESCE(dep_np.name, dep_p.name, dep_ae.name), '') as department"),
                        DB::raw("'' as gender"),
                        DB::raw('GETDATE() as date_of_effectivity'),
                        DB::raw("ah.last_name as name_sig"),
                    )
                    ->where('ah.id', $applicantId)
                    ->get();
            } else {
                // First, try to get from promotions table
                $appointments = DB::table('employee_promotions as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                    ->leftJoin('genders as e', 'b.gender_id', '=', 'e.id')
                    ->leftJoin('name_prefixes as f', 'b.name_prefix_id', '=', 'f.id')
                    ->select(
                        'a.id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.first_name,' ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                                END as name"),
                        'c.name as position',
                        'd.name as department',
                        'e.name as gender',
                        'a.date_of_effectivity',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN
                                    CONCAT(f.name, ' ', b.last_name)
                                        ELSE CONCAT(f.name, ' ', RTRIM([dbo].[ufn_DecryptString](b.last_name, '$app_key')))
                                    END as name_sig"),
                    )
                    ->where('a.id', $employeeId)
                    ->get();

                // If no promotion found, get from employees table
                if ($appointments->isEmpty()) {
                    $appointments = DB::table('employees as b')
                        ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                        ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                        ->leftJoin('genders as e', 'b.gender_id', '=', 'e.id')
                        ->leftJoin('name_prefixes as f', 'b.name_prefix_id', '=', 'f.id')
                        ->select(
                            'b.id',
                            DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                        CONCAT(b.first_name,' ',b.last_name)
                                    ELSE
                                        RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                                    END as name"),
                            'c.name as position',
                            'd.name as department',
                            'e.name as gender',
                            DB::raw('GETDATE() as date_of_effectivity'),
                            DB::raw("CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN CONCAT(f.name, ' ', b.last_name) ELSE CONCAT(f.name, ' ', RTRIM([dbo].[ufn_DecryptString](b.last_name, '$app_key'))) END as name_sig"),
                        )
                        ->where('b.id', $employeeId)
                        ->get();
                }
            }

            if ($appointments->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position,
                'assested_date' => $request->assested_date,
                'assested_signatory' => $request->assested_signatory,
                'assested_position' => $request->assested_position
            );

            $pdf = PDF::loadView('assumption_of_duty.assumption_of_duty_print_2025', compact('appointments', 'signatories'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            
            // Generate PDF content as base64 for API response
            $pdfContent = $pdf->output();
            $base64Content = base64_encode($pdfContent);
            
            $filename = 'assumption_of_duty_' . $request->employee . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate assumption of duty PDF: ' . $e->getMessage());
        }
    }

    public function downloadDocx(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");
            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.', 400);
            }

            $employeeId = (int) $request->employee;
            $appointments = collect();

            if ($employeeId < 0) {
                $applicantId = abs($employeeId);
                $appointments = DB::table('applicant_headers as ah')
                    ->leftJoin('applicant_details as ad', function ($join) {
                        $join->on('ad.applicant_id', '=', 'ah.id')
                            ->whereRaw("ad.id = (
                                SELECT TOP 1 ad2.id
                                FROM applicant_details as ad2
                                WHERE ad2.applicant_id = ah.id
                                ORDER BY CASE WHEN ISNULL(ad2.application_status_id, 0) IN (6,5) THEN 0 ELSE 1 END, ad2.id DESC
                            )");
                    })
                    ->leftJoin('plantillas as p', function ($join) {
                        $join->on('ad.position_applied_id', '=', 'p.id')
                            ->whereRaw('ISNULL(ad.is_plantilla, 1) = 1');
                    })
                    ->leftJoin('non_plantillas as np', function ($join) {
                        $join->on('ad.position_applied_id', '=', 'np.id')
                            ->whereRaw('ISNULL(ad.is_plantilla, 1) = 0');
                    })
                    ->leftJoin('positions as pos_p', 'p.position_id', '=', 'pos_p.id')
                    ->leftJoin('positions as pos_np', 'np.position_id', '=', 'pos_np.id')
                    ->leftJoin('departments as dep_p', 'p.department_id', '=', 'dep_p.id')
                    ->leftJoin('departments as dep_np', 'np.department_id', '=', 'dep_np.id')
                    ->leftJoin('employees as ae', function ($join) {
                        $join->on('ae.employee_no', '=', 'ah.employee_no')
                            ->where('ae.is_employee', true);
                    })
                    ->leftJoin('positions as pos_ae', 'ae.position_id', '=', 'pos_ae.id')
                    ->leftJoin('departments as dep_ae', 'ae.department_id', '=', 'dep_ae.id')
                    ->select(
                        DB::raw('-ah.id as id'),
                        DB::raw("CONCAT(ah.first_name,' ',ah.last_name) as name"),
                        DB::raw("ISNULL(COALESCE(pos_np.name, pos_p.name, pos_ae.name), '') as position"),
                        DB::raw("ISNULL(COALESCE(dep_np.name, dep_p.name, dep_ae.name), '') as department"),
                        DB::raw('GETDATE() as date_of_effectivity'),
                        DB::raw("ah.last_name as name_sig"),
                    )
                    ->where('ah.id', $applicantId)
                    ->get();
            } else {
                // Get appointment info (reuse logic)
                $appointments = DB::table('employee_promotions as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                    ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                    ->leftJoin('name_prefixes as f', 'b.name_prefix_id', '=', 'f.id')
                    ->select(
                        'a.id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) END as name"),
                        'c.name as position',
                        'd.name as department',
                        'a.date_of_effectivity',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN CONCAT(f.name, ' ', b.last_name) ELSE CONCAT(f.name, ' ', RTRIM([dbo].[ufn_DecryptString](b.last_name, '$app_key'))) END as name_sig"),
                    )
                    ->where('a.id', $employeeId)
                    ->get();

                if ($appointments->isEmpty()) {
                    $appointments = DB::table('employees as b')
                        ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                        ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                        ->leftJoin('name_prefixes as f', 'b.name_prefix_id', '=', 'f.id')
                        ->select(
                            'b.id',
                            DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN CONCAT(b.first_name,' ',b.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) END as name"),
                            'c.name as position',
                            'd.name as department',
                            DB::raw('GETDATE() as date_of_effectivity'),
                            DB::raw("CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN CONCAT(f.name, ' ', b.last_name) ELSE CONCAT(f.name, ' ', RTRIM([dbo].[ufn_DecryptString](b.last_name, '$app_key'))) END as name_sig"),
                        )
                        ->where('b.id', $employeeId)
                        ->get();
                }
            }

            $signatories = [
                'signatory' => $request->signatory,
                'position' => $request->position,
                'assested_date' => $request->assested_date,
                'assested_signatory' => $request->assested_signatory,
                'assested_position' => $request->assested_position
            ];

            $ap = $appointments->first();
            $name = $ap->name ?? '';
            $position = $ap->position ?? '';
            $department = $ap->department ?? '';
            $eff = isset($ap->date_of_effectivity) ? date('M d, Y', strtotime($ap->date_of_effectivity)) : date('M d, Y');

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(10.5);
            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(1),
                'marginRight' => Converter::inchToTwip(1),
                'marginBottom' => Converter::inchToTwip(1),
                'marginLeft' => Converter::inchToTwip(1),
            ]);

            // Upper left small text
            $section->addText('CS Form No. 4', ['size' => 8], ['alignment' => 'left', 'spaceAfter' => 0]);
            $section->addText('Revised 2018', ['size' => 8], ['alignment' => 'left']);

            // Government header (center)
            $section->addText('Republic of the Philippines', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            $section->addText(CompanyHelper::getName(), ['size' => 10], ['alignment' => 'center']);
            $section->addText(CompanyHelper::getAddress(), ['size' => 9], ['alignment' => 'center']);
            $section->addText('Philippines', ['size' => 9], ['alignment' => 'center']);
            $section->addTextBreak(1);

            // Title
            $section->addText('CERTIFICATION OF ASSUMPTION TO DUTY', ['bold' => true], ['alignment' => 'center']);
            // Reduce space before body

            // Body paragraphs
            $p1 = $section->addTextRun([
                'alignment' => 'both',
                'spaceAfter' => 0,
                'indentation' => ['firstLine' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1.0)]
            ]);
            $p1->addText("\t");
            $p1->addText('This is to certify that Ms./Mr. ');
            $p1->addText(strtoupper($name), ['bold' => true, 'underline' => 'single']);
            $p1->addText(' has assumed the duties and responsibilities as ');
            $p1->addText(strtoupper($position), ['bold' => true, 'underline' => 'single']);
            $p1->addText(' of ');
            $p1->addText(strtoupper($department), ['bold' => true, 'underline' => 'single']);
            $p1->addText(' effective ');
            $p1->addText($eff, ['bold' => true, 'underline' => 'single']);
            $p1->addText('.');

            $p2 = $section->addTextRun([
                'alignment' => 'both',
                'spaceAfter' => 0,
                'indentation' => ['firstLine' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1.0)]
            ]);
            $p2->addText("\t");
            $p2->addText('This certification is issued in connection with the issuance of the appointment of ');
            $p2->addText(strtoupper($name), ['bold' => true, 'underline' => 'single']);
            $p2->addText(' as ');
            $p2->addText(strtoupper($position), ['bold' => true, 'underline' => 'single']);
            $p2->addText('.');

            $p3 = $section->addTextRun([
                'alignment' => 'both',
                'spaceAfter' => 0,
                'indentation' => ['firstLine' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1.0)]
            ]);
            $p3->addText("\t");
            $p3->addText('Done this ');
            $p3->addText(date('d'), ['bold' => true, 'underline' => 'single']);
            $p3->addText(' day of ');
            $p3->addText(date('M'), ['bold' => true, 'underline' => 'single']);
            $p3->addText(' in ');
            $p3->addText(date('Y'), ['bold' => true, 'underline' => 'single']);

            // Right-side sample (optional small text)
            // Removed sample boxed block

            $section->addTextBreak(1);

            // (Moved Date/Attested By block below signatory section)

            // Signature block on the right
            $section->addTextBreak(2);
            $section->addText(strtoupper($signatories['signatory'] ?? 'Sample signatory name'), ['size' => 10, 'bold' => true], [
                'alignment' => 'right', 'spaceAfter' => 0
            ]);
            // Short line between name and position using an underlined non-breaking space run (no table)
            $lineRun = $section->addTextRun(['alignment' => 'right', 'spaceAfter' => 0]);
            $posText = $signatories['position'] ?? 'Sample Signatory Position';
            $nbSpaces = max(16, (int)ceil(strlen($posText) * 1.4));
            $lineRun->addText(str_repeat(chr(160), $nbSpaces), ['underline' => 'single']);
            $section->addText($signatories['position'] ?? 'Sample Signatory Position', ['size' => 9], ['alignment' => 'right']);

            // Date and Attested by (left area) — shown after signatory per requirement
            $section->addTextBreak(1);
            // Date line with underlined date (left-aligned)
            $dateRun = $section->addTextRun();
            $dateRun->addText('Date: ', ['size' => 9]);
            $dateRun->addText(date('M d, Y'), ['size' => 9, 'underline' => 'single']);
            $section->addTextBreak(1);
            // Attested By block
            $section->addText('Attested By:', ['size' => 9]);
            $section->addTextBreak(1);
            $section->addText($signatories['assested_signatory'] ?? 'sample assessed signatory', ['size' => 9, 'bold' => true, 'underline' => 'single']);
            $section->addText($signatories['assested_position'] ?? 'sample Assessed position', ['size' => 9]);
            $section->addTextBreak(1);
            // Small list at lower-left
            $section->addText('201 file', ['size' => 8]);
            $section->addText('Admin', ['size' => 8]);
            $section->addText('COA', ['size' => 8]);
            $section->addText('CSC', ['size' => 8]);

            // Bottom note inside a small bordered box on the right
            $section->addTextBreak(0);
            $noteTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 120,
                'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::END
            ]);
            $noteTable->addRow();
            $noteCell = $noteTable->addCell(Converter::inchToTwip(2.2));
            $noteCell->addText('For submission to CSC FO within 30 days from the date of assumption of the appointee', ['size' => 8], ['alignment' => 'center']);

            $fileName = 'assumption_of_duty_' . ($name ?: 'employee') . '_' . date('Y-m-d') . '.docx';
            $tempPath = storage_path('app/' . $fileName);
            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);
            return response()->download($tempPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download assumption of duty DOCX: ' . $e->getMessage());
        }
    }
}
