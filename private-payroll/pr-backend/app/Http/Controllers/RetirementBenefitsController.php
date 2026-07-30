<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Table;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Color;

class RetirementBenefitsController extends Controller
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

    /**
     * Get leave type IDs by name
     *
     * @return array ['vl_id' => int, 'sl_id' => int]
     */
    /**
     * Get leave type IDs by name
     *
     * @return array ['vl_id' => int, 'sl_id' => int]
     */
    private function getLeaveTypeIds()
    {
        static $cachedIds = null;

        if ($cachedIds === null) {
            // Try exact match first, then partial match
            $vlId = DB::table('leave_types')
                ->where(function($query) {
                    $query->where('name', 'LIKE', '%Vacation Leave%')
                          ->orWhere('name', 'LIKE', '%Vacation%');
                })
                ->where('active', 1)
                ->value('id');

            $slId = DB::table('leave_types')
                ->where(function($query) {
                    $query->where('name', 'LIKE', '%Sick Leave%')
                          ->orWhere('name', 'LIKE', '%Sick%');
                })
                ->where('active', 1)
                ->value('id');

            // Fallback to default IDs if not found
            $cachedIds = [
                'vl_id' => $vlId ?? 1, // Default to 1 if not found
                'sl_id' => $slId ?? 2, // Default to 2 if not found
            ];

            // Log for debugging
            Log::info('Leave Type IDs - VL: ' . $cachedIds['vl_id'] . ', SL: ' . $cachedIds['sl_id']);
        }

        return $cachedIds;
    }

    public function index()
    {
        try {
            $departments = DB::table('departments')->where('active', true)->orderBy('name', 'asc')->get();

            return $this->successResponse([
                'departments' => $departments,
            ], 'Retirement benefits data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve retirement benefits data: ' . $e->getMessage());
        }
    }

    /**
     * Get retirees data for a specific fiscal year
     */
    public function getRetirees(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'fiscal_year' => 'required|integer|min:2000|max:2100',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $fiscal_year = $request->fiscal_year;

            // Get leave type IDs dynamically
            $leaveTypeIds = $this->getLeaveTypeIds();
            $vlTypeId = $leaveTypeIds['vl_id'];
            $slTypeId = $leaveTypeIds['sl_id'];

            // Get retirees data from employee_offboardings with retirement_date
            $retirees = DB::table('employee_offboardings as eo')
                ->join('employees as e', 'e.id', '=', 'eo.employee_id')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                ->leftJoin('plantillas as pl', 'pl.id', '=', 'e.plantilla_id')
                ->whereNotNull('eo.retirement_date')
                ->whereYear('eo.retirement_date', $fiscal_year)
                ->select(
                    'e.id',
                    'e.employee_no',
                    'eo.retirement_date',
                    'p.name as position',
                    'd.name as department',
                    'pl.code as plantilla_code',
                    'e.gsis_no',
                    'e.sss_no',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(RTRIM(ISNULL(e.last_name,'')), ', ', RTRIM(ISNULL(e.first_name,'')), ' ', CASE WHEN e.middle_name IS NOT NULL AND e.middle_name <> '' THEN LEFT(RTRIM(e.middle_name), 1) ELSE '' END)
                        ELSE
                            CONCAT(
                                RTRIM([dbo].[ufn_DecryptString](ISNULL(e.last_name,''),'$app_key')), ', ',
                                RTRIM([dbo].[ufn_DecryptString](ISNULL(e.first_name,''),'$app_key')), ' ',
                                CASE WHEN e.middle_name IS NOT NULL AND e.middle_name <> '' THEN LEFT(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), 1) ELSE '' END
                            )
                        END as name"),
                    'e.birthdate as birth_date',
                    'e.date_hired as original_appointment_date',
                    'e.salary as highest_monthly_salary'
                )
                ->orderBy('eo.retirement_date', 'asc')
                ->get();

            // Process each retiree to calculate required fields
            $processedRetirees = $retirees->map(function($retiree) use ($app_key, $vlTypeId, $slTypeId) {
                try {
                    // Get leave credits using dynamic leave type IDs
                    $vlCredits = floatval(DB::table('leave_credits')
                        ->where('employee_id', $retiree->id)
                        ->where('leave_type_id', $vlTypeId) // Vacation Leave - dynamic lookup
                        ->value('credits') ?? 0);

                    $slCredits = floatval(DB::table('leave_credits')
                        ->where('employee_id', $retiree->id)
                        ->where('leave_type_id', $slTypeId) // Sick Leave - dynamic lookup
                        ->value('credits') ?? 0);

                    $highestMonthlySalary = floatval($retiree->highest_monthly_salary ?? 0);

                    // Calculate Column 9 (AMOUNT): HIGHEST_MONTHLY_SALARY * (VACATION_LEAVE + SICK_LEAVE) * 0.0478087
                    $terminalLeaveAmount = $highestMonthlySalary * ($vlCredits + $slCredits) * 0.0478087;

                    // Calculate Column 10 (TOTAL CREDITABLE SERVICE): (retirement_date - date_hired) / 365
                    if ($retiree->original_appointment_date && $retiree->retirement_date) {
                        $originalAppointment = Carbon::parse($retiree->original_appointment_date);
                        $retirementDate = Carbon::parse($retiree->retirement_date);
                        $totalCreditableService = $originalAppointment->diffInDays($retirementDate) / 365.0;
                    } else {
                        $totalCreditableService = 0;
                    }

                    // Calculate Column 11 (No of Gratuity Amounts): HIGHEST_MONTHLY_SALARY * TOTAL_CREDITABLE_SERVICE
                    $gratuityAmount = $highestMonthlySalary * $totalCreditableService;

                    // Calculate Column 12 (AMOUNT): Column 9 + Column 11
                    $retirementGratuityAmount = $terminalLeaveAmount + $gratuityAmount;

                    $isGsisMember = !empty($retiree->gsis_no);

                    return [
                        'id' => $retiree->id,
                        'employee_no' => $retiree->employee_no,
                        'name' => $retiree->name,
                        'position' => $retiree->position ?? '',
                        'department' => $retiree->department ?? '',
                        'birth_date' => $retiree->birth_date,
                        'original_appointment_date' => $retiree->original_appointment_date,
                        'retirement_date' => $retiree->retirement_date,
                        'highest_monthly_salary' => $highestMonthlySalary,
                        'vl_credits' => $vlCredits,
                        'sl_credits' => $slCredits,
                        'terminal_leave_amount' => $terminalLeaveAmount,
                        'creditable_service' => $totalCreditableService,
                        'gratuity_amount' => $gratuityAmount,
                        'retirement_gratuity_amount' => $retirementGratuityAmount,
                        'is_gsis_member' => $isGsisMember,
                        'retirement_law' => $isGsisMember ? 'RA 8291' : 'Other Retirement Laws',
                    ];
                } catch (\Exception $e) {
                    Log::error('Error processing retiree ID ' . ($retiree->id ?? 'unknown') . ': ' . $e->getMessage());
                    return null;
                }
            })->filter();

            return $this->successResponse([
                'retirees' => $processedRetirees->values(),
                'count' => $processedRetirees->count(),
            ], 'Retirees data retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get Retirees Error: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve retirees data: ' . $e->getMessage());
        }
    }

    /**
     * Generate BP FORM 205 PDF
     */
    public function generatePdf(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'fiscal_year' => 'required|integer|min:2000|max:2100',
                'prepared_by_name' => 'nullable|string|max:255',
                'prepared_by_position' => 'nullable|string|max:255',
                'approved_by_name' => 'nullable|string|max:255',
                'approved_by_position' => 'nullable|string|max:255',
                'date' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $fiscal_year = $request->fiscal_year;

            // Get leave type IDs dynamically
            $leaveTypeIds = $this->getLeaveTypeIds();
            $vlTypeId = $leaveTypeIds['vl_id'];
            $slTypeId = $leaveTypeIds['sl_id'];

            // Get company/department info
            $company = DB::table('companies')->first();
            $department = $company ? $company->name : 'DEPARTMENT OF TRADE AND INDUSTRY';
            $agency = $company ? $company->name : 'PHILIPPINE TRADE TRAINING CENTER';

            // Get retirees data from employee_offboardings with retirement_date
            // Only include employees who have retirement_date set and matches the fiscal year
            try {
                Log::info('Retirement Benefits Query - Fiscal Year: ' . $request->fiscal_year);

                // First, let's check what we have in employee_offboardings
                $allOffboardings = DB::table('employee_offboardings')
                    ->whereNotNull('retirement_date')
                    ->get();
                Log::info('Total offboardings with retirement_date: ' . $allOffboardings->count());

                $offboardingsForYear = DB::table('employee_offboardings')
                    ->whereNotNull('retirement_date')
                    ->whereYear('retirement_date', $request->fiscal_year)
                    ->get();
                Log::info('Offboardings with retirement_date for year ' . $request->fiscal_year . ': ' . $offboardingsForYear->count());
                if ($offboardingsForYear->count() > 0) {
                    Log::info('Employee IDs from offboardings: ' . $offboardingsForYear->pluck('employee_id')->implode(', '));
                }

                // Now get the retirees with employee data
                $retirees = DB::table('employee_offboardings as eo')
                    ->join('employees as e', 'e.id', '=', 'eo.employee_id')
                    ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                    ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                    ->leftJoin('plantillas as pl', 'pl.id', '=', 'e.plantilla_id')
                    ->whereNotNull('eo.retirement_date')
                    ->whereYear('eo.retirement_date', $request->fiscal_year)
                ->select(
                    'e.id',
                    'e.employee_no',
                    'eo.retirement_date',
                    'p.name as position',
                    'd.name as department',
                    'pl.code as plantilla_code',
                    'e.gsis_no',
                    'e.sss_no',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(RTRIM(ISNULL(e.last_name,'')), ', ', RTRIM(ISNULL(e.first_name,'')), ' ', CASE WHEN e.middle_name IS NOT NULL AND e.middle_name <> '' THEN LEFT(RTRIM(e.middle_name), 1) ELSE '' END)
                        ELSE
                            CONCAT(
                                RTRIM([dbo].[ufn_DecryptString](ISNULL(e.last_name,''),'$app_key')), ', ',
                                RTRIM([dbo].[ufn_DecryptString](ISNULL(e.first_name,''),'$app_key')), ' ',
                                CASE WHEN e.middle_name IS NOT NULL AND e.middle_name <> '' THEN LEFT(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), 1) ELSE '' END
                            )
                        END as name"),
                    'e.birthdate as birth_date',
                    'e.date_hired as original_appointment_date',
                    'e.salary as highest_monthly_salary'
                )
                    ->orderBy('eo.retirement_date', 'asc')
                    ->get();

                Log::info('Retirees found after join: ' . $retirees->count());
                if ($retirees->count() > 0) {
                    Log::info('Retirees employee IDs: ' . $retirees->pluck('id')->implode(', '));
                } else {
                    // Check if employees exist for the offboardings
                    $employeeIds = $offboardingsForYear->pluck('employee_id');
                    $existingEmployees = DB::table('employees')
                        ->whereIn('id', $employeeIds)
                        ->pluck('id');

                    Log::info('Employee IDs from offboardings: ' . $employeeIds->implode(', '));
                    Log::info('Existing employee IDs: ' . $existingEmployees->implode(', '));
                    Log::info('Missing employee IDs: ' . $employeeIds->diff($existingEmployees)->implode(', '));
                }
            } catch (\Exception $queryError) {
                Log::error('Retirement Benefits Query Error: ' . $queryError->getMessage());
                Log::error('Retirement Benefits Query Stack: ' . $queryError->getTraceAsString());
                $retirees = collect([]);
            }

            // Ensure retirees is a collection
            if (!is_object($retirees) || !method_exists($retirees, 'map')) {
                $retirees = collect($retirees ?? []);
            }

            // Process each retiree to calculate required fields
            $processedRetirees = $retirees->map(function($retiree) use ($app_key, $vlTypeId, $slTypeId) {
                try {
                    // Get leave credits (VL and SL) using dynamic leave type IDs
                    $vlCredits = DB::table('leave_credits')
                        ->where('employee_id', $retiree->id)
                        ->where('leave_type_id', $vlTypeId) // Vacation Leave - dynamic lookup
                        ->value('credits');
                    $vlCredits = floatval($vlCredits ?? 0);

                    $slCredits = DB::table('leave_credits')
                        ->where('employee_id', $retiree->id)
                        ->where('leave_type_id', $slTypeId) // Sick Leave - dynamic lookup
                        ->value('credits');
                    $slCredits = floatval($slCredits ?? 0);

                    $highestMonthlySalary = floatval($retiree->highest_monthly_salary ?? 0);

                    // Calculate Column 9 (AMOUNT): HIGHEST_MONTHLY_SALARY * (VACATION_LEAVE + SICK_LEAVE) * 0.0478087
                    $terminalLeaveAmount = $highestMonthlySalary * ($vlCredits + $slCredits) * 0.0478087;

                    // Calculate Column 10 (TOTAL CREDITABLE SERVICE): (retirement_date - date_hired) / 365
                    if ($retiree->original_appointment_date && $retiree->retirement_date) {
                        $originalAppointment = Carbon::parse($retiree->original_appointment_date);
                        $retirementDate = Carbon::parse($retiree->retirement_date);
                        // Calculate difference in days and convert to years
                        $totalCreditableService = $originalAppointment->diffInDays($retirementDate) / 365.0;
                    } else {
                        $totalCreditableService = 0;
                    }

                    // Calculate Column 11 (No of Gratuity Amounts): HIGHEST_MONTHLY_SALARY * TOTAL_CREDITABLE_SERVICE
                    $gratuityAmount = $highestMonthlySalary * $totalCreditableService;

                    // Calculate Column 12 (AMOUNT): Column 9 + Column 11
                    $retirementGratuityAmount = $terminalLeaveAmount + $gratuityAmount;
                } catch (\Exception $e) {
                    Log::error('Error processing retiree ID ' . ($retiree->id ?? 'unknown') . ': ' . $e->getMessage());
                    // If there's an error processing a retiree, set defaults
                    $vlCredits = 0;
                    $slCredits = 0;
                    $highestMonthlySalary = floatval($retiree->highest_monthly_salary ?? 0);
                    $terminalLeaveAmount = 0;
                    $totalCreditableService = 0;
                    $gratuityAmount = 0;
                    $retirementGratuityAmount = 0;
                }

                // Determine if GSIS member (has GSIS number) or Non-GSIS (has SSS number)
                $isGsisMember = !empty($retiree->gsis_no);
                $retirementLaw = $isGsisMember ? 'RA 8291' : 'Other Retirement Laws';

                return (object)[
                    'id' => $retiree->id,
                    'name' => $retiree->name,
                    'position' => $retiree->position ?? '',
                    'plantilla_code' => $retiree->plantilla_code ?? '',
                    'birth_date' => $retiree->birth_date,
                    'original_appointment_date' => $retiree->original_appointment_date,
                    'retirement_date' => $retiree->retirement_date,
                    'highest_monthly_salary' => $highestMonthlySalary,
                    'vl_credits' => $vlCredits,
                    'sl_credits' => $slCredits,
                    'terminal_leave_amount' => $terminalLeaveAmount, // Column 9 (AMOUNT)
                    'creditable_service' => $totalCreditableService, // Column 10 (TOTAL CREDITABLE SERVICE)
                    'gratuity_amount' => $gratuityAmount ?? 0, // Column 11 (No of Gratuity Amounts)
                    'retirement_gratuity_amount' => $retirementGratuityAmount, // Column 12 (AMOUNT) = Column 9 + Column 11
                    'retirement_law' => $retirementLaw,
                    'is_gsis_member' => $isGsisMember,
                ];
            });

            // Ensure processedRetirees is a collection
            if (!is_object($processedRetirees) || !method_exists($processedRetirees, 'filter')) {
                $processedRetirees = collect($processedRetirees ?? []);
            }

            // Group retirees by retirement law type (GSIS vs Non-GSIS)
            $gsisMembers = $processedRetirees->filter(function($retiree) {
                return isset($retiree->is_gsis_member) && $retiree->is_gsis_member === true;
            })->values();

            $nonGsisMembers = $processedRetirees->filter(function($retiree) {
                return !isset($retiree->is_gsis_member) || $retiree->is_gsis_member === false;
            })->values();

            // Calculate subtotals
            $gsisSubtotalTerminalLeave = $gsisMembers->sum('terminal_leave_amount');
            $gsisSubtotalRetirementGratuity = $gsisMembers->sum('retirement_gratuity_amount');
            $nonGsisSubtotalTerminalLeave = $nonGsisMembers->sum('terminal_leave_amount');
            $nonGsisSubtotalRetirementGratuity = $nonGsisMembers->sum('retirement_gratuity_amount');

            // Calculate totals
            $totalTerminalLeave = $gsisSubtotalTerminalLeave + $nonGsisSubtotalTerminalLeave;
            $totalRetirementGratuity = $gsisSubtotalRetirementGratuity + $nonGsisSubtotalRetirementGratuity;

            // Prepare signatories
            $signatories = [
                'prepared_by_name' => $request->prepared_by_name ?? 'MARIA ANTONIETTE S. ZOILO',
                'prepared_by_position' => $request->prepared_by_position ?? 'Administrative Officer V',
                'approved_by_name' => $request->approved_by_name ?? 'NELLY NITA N. DILLERA',
                'approved_by_position' => $request->approved_by_position ?? 'Executive Director',
                'date' => $request->date ? Carbon::parse($request->date)->format('d M y') : Carbon::now()->format('d M y'),
            ];

            $pdf = PDF::loadView('retirement_benefits.terminal_leave_retirement', compact(
                'processedRetirees',
                'gsisMembers',
                'nonGsisMembers',
                'department',
                'agency',
                'fiscal_year',
                'signatories',
                'totalTerminalLeave',
                'totalRetirementGratuity',
                'gsisSubtotalTerminalLeave',
                'gsisSubtotalRetirementGratuity',
                'nonGsisSubtotalTerminalLeave',
                'nonGsisSubtotalRetirementGratuity'
            ))->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('legal', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'bp_form_205_retirement_benefits_' . $request->fiscal_year . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            Log::error('Retirement Benefits PDF Generation Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Request data: ' . json_encode($request->all()));
            return $this->serverErrorResponse('Failed to generate BP FORM 205 PDF: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        // Keep the original print method for backward compatibility
        return $this->generatePdf($request);
    }

    /**
     * Generate BP FORM 205 DOCX
     */
    public function generateDocx(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'fiscal_year' => 'required|integer|min:2000|max:2100',
                'prepared_by_name' => 'nullable|string|max:255',
                'prepared_by_position' => 'nullable|string|max:255',
                'approved_by_name' => 'nullable|string|max:255',
                'approved_by_position' => 'nullable|string|max:255',
                'date' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $fiscal_year = $request->fiscal_year;

            // Get leave type IDs dynamically
            $leaveTypeIds = $this->getLeaveTypeIds();
            $vlTypeId = $leaveTypeIds['vl_id'];
            $slTypeId = $leaveTypeIds['sl_id'];

            // Get company/department info
            $company = DB::table('companies')->first();
            $department = $company ? $company->name : 'DEPARTMENT OF TRADE AND INDUSTRY';
            $agency = $company ? $company->name : 'PHILIPPINE TRADE TRAINING CENTER';

            // Get retirees data (same logic as generatePdf)
            try {
                $retirees = DB::table('employee_offboardings as eo')
                    ->join('employees as e', 'e.id', '=', 'eo.employee_id')
                    ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                    ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                    ->leftJoin('plantillas as pl', 'pl.id', '=', 'e.plantilla_id')
                    ->whereNotNull('eo.retirement_date')
                    ->whereYear('eo.retirement_date', $fiscal_year)
                    ->select(
                        'e.id',
                        'e.employee_no',
                        'eo.retirement_date',
                        'p.name as position',
                        'd.name as department',
                        'pl.code as plantilla_code',
                        'e.gsis_no',
                        'e.sss_no',
                        DB::raw("CASE
                            WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CONCAT(RTRIM(ISNULL(e.last_name,'')), ', ', RTRIM(ISNULL(e.first_name,'')), ' ', CASE WHEN e.middle_name IS NOT NULL AND e.middle_name <> '' THEN LEFT(RTRIM(e.middle_name), 1) ELSE '' END)
                            ELSE
                                CONCAT(
                                    RTRIM([dbo].[ufn_DecryptString](ISNULL(e.last_name,''),'$app_key')), ', ',
                                    RTRIM([dbo].[ufn_DecryptString](ISNULL(e.first_name,''),'$app_key')), ' ',
                                    CASE WHEN e.middle_name IS NOT NULL AND e.middle_name <> '' THEN LEFT(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), 1) ELSE '' END
                                )
                            END as name"),
                        'e.birthdate as birth_date',
                        'e.date_hired as original_appointment_date',
                        'e.salary as highest_monthly_salary'
                    )
                    ->orderBy('eo.retirement_date', 'asc')
                    ->get();
            } catch (\Exception $queryError) {
                Log::error('Retirement Benefits Query Error: ' . $queryError->getMessage());
                $retirees = collect([]);
            }

            // Ensure retirees is a collection
            if (!is_object($retirees) || !method_exists($retirees, 'map')) {
                $retirees = collect($retirees ?? []);
            }

            // Process each retiree to calculate required fields
            $processedRetirees = $retirees->map(function($retiree) use ($app_key, $vlTypeId, $slTypeId) {
                try {
                    $vlCredits = DB::table('leave_credits')
                        ->where('employee_id', $retiree->id)
                        ->where('leave_type_id', $vlTypeId)
                        ->value('credits');
                    $vlCredits = floatval($vlCredits ?? 0);

                    $slCredits = DB::table('leave_credits')
                        ->where('employee_id', $retiree->id)
                        ->where('leave_type_id', $slTypeId)
                        ->value('credits');
                    $slCredits = floatval($slCredits ?? 0);

                    $highestMonthlySalary = floatval($retiree->highest_monthly_salary ?? 0);
                    $terminalLeaveAmount = $highestMonthlySalary * ($vlCredits + $slCredits) * 0.0478087;

                    if ($retiree->original_appointment_date && $retiree->retirement_date) {
                        $originalAppointment = Carbon::parse($retiree->original_appointment_date);
                        $retirementDate = Carbon::parse($retiree->retirement_date);
                        $totalCreditableService = $originalAppointment->diffInDays($retirementDate) / 365.0;
                    } else {
                        $totalCreditableService = 0;
                    }

                    $gratuityAmount = $highestMonthlySalary * $totalCreditableService;
                    $retirementGratuityAmount = $terminalLeaveAmount + $gratuityAmount;
                } catch (\Exception $e) {
                    Log::error('Error processing retiree ID ' . ($retiree->id ?? 'unknown') . ': ' . $e->getMessage());
                    $vlCredits = 0;
                    $slCredits = 0;
                    $highestMonthlySalary = floatval($retiree->highest_monthly_salary ?? 0);
                    $terminalLeaveAmount = 0;
                    $totalCreditableService = 0;
                    $gratuityAmount = 0;
                    $retirementGratuityAmount = 0;
                }

                $isGsisMember = !empty($retiree->gsis_no);
                $retirementLaw = $isGsisMember ? 'RA 8291' : 'Other Retirement Laws';

                return (object)[
                    'id' => $retiree->id,
                    'name' => $retiree->name,
                    'position' => $retiree->position ?? '',
                    'plantilla_code' => $retiree->plantilla_code ?? '',
                    'birth_date' => $retiree->birth_date,
                    'original_appointment_date' => $retiree->original_appointment_date,
                    'retirement_date' => $retiree->retirement_date,
                    'highest_monthly_salary' => $highestMonthlySalary,
                    'vl_credits' => $vlCredits,
                    'sl_credits' => $slCredits,
                    'terminal_leave_amount' => $terminalLeaveAmount,
                    'creditable_service' => $totalCreditableService,
                    'gratuity_amount' => $gratuityAmount ?? 0,
                    'retirement_gratuity_amount' => $retirementGratuityAmount,
                    'retirement_law' => $retirementLaw,
                    'is_gsis_member' => $isGsisMember,
                ];
            });

            if (!is_object($processedRetirees) || !method_exists($processedRetirees, 'filter')) {
                $processedRetirees = collect($processedRetirees ?? []);
            }

            // Group retirees
            $gsisMembers = $processedRetirees->filter(function($retiree) {
                return isset($retiree->is_gsis_member) && $retiree->is_gsis_member === true;
            })->values();

            $nonGsisMembers = $processedRetirees->filter(function($retiree) {
                return !isset($retiree->is_gsis_member) || $retiree->is_gsis_member === false;
            })->values();

            // Calculate subtotals
            $gsisSubtotalTerminalLeave = $gsisMembers->sum('terminal_leave_amount');
            $gsisSubtotalRetirementGratuity = $gsisMembers->sum('retirement_gratuity_amount');
            $nonGsisSubtotalTerminalLeave = $nonGsisMembers->sum('terminal_leave_amount');
            $nonGsisSubtotalRetirementGratuity = $nonGsisMembers->sum('retirement_gratuity_amount');

            // Calculate totals
            $totalTerminalLeave = $gsisSubtotalTerminalLeave + $nonGsisSubtotalTerminalLeave;
            $totalRetirementGratuity = $gsisSubtotalRetirementGratuity + $nonGsisSubtotalRetirementGratuity;

            // Prepare signatories
            $signatories = [
                'prepared_by_name' => $request->prepared_by_name ?? 'MARIA ANTONIETTE S. ZOILO',
                'prepared_by_position' => $request->prepared_by_position ?? 'Administrative Officer V',
                'approved_by_name' => $request->approved_by_name ?? 'NELLY NITA N. DILLERA',
                'approved_by_position' => $request->approved_by_position ?? 'Executive Director',
                'date' => $request->date ? Carbon::parse($request->date)->format('d M y') : Carbon::now()->format('d M y'),
            ];

            // Generate DOCX
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(10);

            // Section with landscape orientation and legal size (14" x 8.5" in landscape)
            $section = $phpWord->addSection([
                'orientation' => 'landscape',
                'pageSizeW' => 20160, // 14 inches in twips (legal height as width in landscape)
                'pageSizeH' => 12240, // 8.5 inches in twips (legal width as height in landscape)
                'marginTop' => 720,   // 0.5 inch
                'marginBottom' => 720,
                'marginLeft' => 720,
                'marginRight' => 720,
            ]);

            // BP FORM 205 header (right aligned)
            $section->addText('BP FORM 205', ['size' => 11, 'bold' => true], ['alignment' => 'right', 'spaceAfter' => 100]);

            // Title Section
            $section->addText('LIST OF RETIREES', ['bold' => true, 'size' => 12], ['alignment' => 'center', 'spaceAfter' => 0]);
            $section->addText('FOR PAYMENT OF TERMINAL LEAVE AND RETIREMENT GRATUITY BENEFITS', ['bold' => true, 'size' => 11], ['alignment' => 'center', 'spaceAfter' => 0]);
            $section->addText('FY ' . $fiscal_year, ['bold' => true, 'size' => 11], ['alignment' => 'center', 'spaceAfter' => 200]);

            // Department and Agency info
            $infoTable = $section->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 50]);
            $infoRow1 = $infoTable->addRow();
            $infoRow1->addCell(6000)->addText('DEPARTMENT: ' . $department, ['size' => 11, 'bold' => true], ['spaceAfter' => 0]);
            $infoRow1->addCell(4000, ['valign' => 'center'])->addText('☐ Mandatory    ☐ Optional', ['size' => 10], ['alignment' => 'right', 'spaceAfter' => 0]);
            
            $infoRow2 = $infoTable->addRow();
            $infoRow2->addCell(6000)->addText('AGENCY: ' . $agency, ['size' => 11, 'bold' => true], ['spaceAfter' => 0]);
            $infoRow2->addCell(4000)->addText('', [], ['spaceAfter' => 0]);

            // Main Data Table - Define column widths for 12 columns (total width for landscape legal: ~19300 twips = 13.4 inches)
            // Column widths: 3600, 1600, 1000, 1000, 1000, 1600, 900, 900, 1300, 1300, 1700, 1500
            $mainTable = $section->addTable([
                'width' => 19300,
                'unit' => 'dxa',
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 50
            ]);

            // Header Row 1 - Complex headers with colspan
            $headerRow1 = $mainTable->addRow();
            $headerRow1->addCell(3600, ['vMerge' => 'restart', 'valign' => 'center'])->addText('NAME OF RETIREES AND RETIREMENT LAW', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow1->addCell(1600, ['vMerge' => 'restart', 'valign' => 'center'])->addText('Position at Retirement (Item No.)', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow1->addCell(4500, ['gridSpan' => 3, 'valign' => 'center'])->addText('Date (Mo/Day/Year)', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow1->addCell(1600, ['vMerge' => 'restart', 'valign' => 'center'])->addText('Highest Monthly Salary Per NOSAA', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow1->addCell(2700, ['gridSpan' => 3, 'valign' => 'center'])->addText('TERMINAL LEAVE', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow1->addCell(4500, ['gridSpan' => 3, 'valign' => 'center'])->addText('RETIREMENT GRATUITY', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Header Row 2
            $headerRow2 = $mainTable->addRow();
            $headerRow2->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 1
            $headerRow2->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 2
            $headerRow2->addCell(1500, ['vMerge' => 'restart', 'valign' => 'center'])->addText('Birth', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow2->addCell(1500, ['vMerge' => 'restart', 'valign' => 'center'])->addText('Original Appointment', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow2->addCell(1500, ['vMerge' => 'restart', 'valign' => 'center'])->addText('Retirement', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow2->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 6
            $headerRow2->addCell(1800, ['gridSpan' => 2, 'valign' => 'center'])->addText('No. of Leave Credits Earned', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow2->addCell(1300, ['vMerge' => 'restart', 'valign' => 'center'])->addText('Amount', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow2->addCell(1300, ['vMerge' => 'restart', 'valign' => 'center'])->addText('Total Creditable Service', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow2->addCell(1700, ['vMerge' => 'restart', 'valign' => 'center'])->addText('No of Gratuity Amounts (YR)', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow2->addCell(1500, ['vMerge' => 'restart', 'valign' => 'center'])->addText('Amount', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Header Row 3
            $headerRow3 = $mainTable->addRow();
            $headerRow3->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 1
            $headerRow3->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 2
            $headerRow3->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 3
            $headerRow3->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 4
            $headerRow3->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 5
            $headerRow3->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 6
            $headerRow3->addCell(900, ['valign' => 'center'])->addText('VL', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow3->addCell(900, ['valign' => 'center'])->addText('SL', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow3->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 9
            $headerRow3->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 10
            $headerRow3->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 11
            $headerRow3->addCell(null, ['vMerge' => 'continue']); // Continue merge for column 12

            // Column number row
            $headerRow4 = $mainTable->addRow();
            $headerRow4->addCell(3600)->addText('(1)', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow4->addCell(1600)->addText('(2)', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow4->addCell(1000)->addText('(3)', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow4->addCell(1000)->addText('(4)', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow4->addCell(1000)->addText('(5)', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow4->addCell(1600)->addText('(6)', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow4->addCell(900)->addText('(7)', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow4->addCell(900)->addText('(8)', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow4->addCell(1300)->addText('(9)', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow4->addCell(1300)->addText('(10)', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow4->addCell(1700)->addText('(11)', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $headerRow4->addCell(1500)->addText('(12)', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);

            // GSIS Members Section
            $gsisHeaderRow = $mainTable->addRow();
            $gsisHeaderCell = $gsisHeaderRow->addCell(3600, ['valign' => 'top']);
            $gsisHeaderCell->addText('For GSIS Members :', ['bold' => true, 'size' => 10], ['spaceAfter' => 0]);
            $gsisHeaderCell->addText('I. Under RA NO. 1616', ['size' => 10], ['spaceAfter' => 0]);
            $gsisHeaderCell->addText('II. Other Retirement Laws (pls. specify, e.g. RA 8291)', ['size' => 10], ['spaceAfter' => 0]);
            for ($i = 0; $i < 11; $i++) {
                $gsisHeaderRow->addCell()->addText('', [], ['spaceAfter' => 0]);
            }

            // GSIS Members Data Rows
            foreach ($gsisMembers as $retiree) {
                $birthDate = $retiree->birth_date ? Carbon::parse($retiree->birth_date)->format('m/d/Y') : '';
                $originalAppointment = $retiree->original_appointment_date ? Carbon::parse($retiree->original_appointment_date)->format('m/d/Y') : '';
                $retirementDate = $retiree->retirement_date ? Carbon::parse($retiree->retirement_date)->format('m/d/Y') : '';
                
                $dataRow = $mainTable->addRow();
                $nameCell = $dataRow->addCell(3600);
                $nameCell->addText($retiree->name, ['size' => 9], ['spaceAfter' => 0]);
                $nameCell->addText($retiree->retirement_law, ['size' => 8], ['spaceAfter' => 0]);
                
                $dataRow->addCell(1600)->addText($retiree->position, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(1000)->addText($birthDate, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(1000)->addText($originalAppointment, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(1000)->addText($retirementDate, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(1600)->addText(number_format($retiree->highest_monthly_salary, 2), ['size' => 9], ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(900)->addText(number_format($retiree->vl_credits, 2), ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(900)->addText(number_format($retiree->sl_credits, 2), ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(1300)->addText(number_format($retiree->terminal_leave_amount, 2), ['size' => 9], ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(1300)->addText(number_format($retiree->creditable_service, 2), ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(1700)->addText(number_format($retiree->gratuity_amount, 2), ['size' => 9], ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(1500)->addText(number_format($retiree->retirement_gratuity_amount, 2), ['size' => 9], ['alignment' => 'right', 'spaceAfter' => 0]);
            }

            // GSIS Subtotal Row
            $gsisSubtotalRow = $mainTable->addRow();
            $gsisSubtotalRow->addCell(3600)->addText('Sub Total:', ['bold' => true, 'size' => 10], ['spaceAfter' => 0]);
            for ($i = 0; $i < 7; $i++) {
                $gsisSubtotalRow->addCell()->addText('', [], ['spaceAfter' => 0]);
            }
            $gsisSubtotalRow->addCell(1300)->addText(number_format($gsisSubtotalTerminalLeave, 2), ['bold' => true, 'size' => 10], ['alignment' => 'right', 'spaceAfter' => 0]);
            $gsisSubtotalRow->addCell(1300)->addText('', [], ['spaceAfter' => 0]);
            $gsisSubtotalRow->addCell(1700)->addText('', [], ['spaceAfter' => 0]);
            $gsisSubtotalRow->addCell(1500)->addText(number_format($gsisSubtotalRetirementGratuity, 2), ['bold' => true, 'size' => 10], ['alignment' => 'right', 'spaceAfter' => 0]);

            // Non-GSIS Members Section
            $nonGsisHeaderRow = $mainTable->addRow();
            $nonGsisHeaderCell = $nonGsisHeaderRow->addCell(3600, ['valign' => 'top']);
            $nonGsisHeaderCell->addText('For Non-GSIS Members : (e.g. Military/Uniformed)', ['bold' => true, 'size' => 10], ['spaceAfter' => 0]);
            $nonGsisHeaderCell->addText('Retirement Laws (pls. specify)', ['size' => 10], ['spaceAfter' => 0]);
            for ($i = 0; $i < 11; $i++) {
                $nonGsisHeaderRow->addCell()->addText('', [], ['spaceAfter' => 0]);
            }

            // Non-GSIS Members Data Rows
            foreach ($nonGsisMembers as $retiree) {
                $birthDate = $retiree->birth_date ? Carbon::parse($retiree->birth_date)->format('m/d/Y') : '';
                $originalAppointment = $retiree->original_appointment_date ? Carbon::parse($retiree->original_appointment_date)->format('m/d/Y') : '';
                $retirementDate = $retiree->retirement_date ? Carbon::parse($retiree->retirement_date)->format('m/d/Y') : '';
                
                $dataRow = $mainTable->addRow();
                $nameCell = $dataRow->addCell(3600);
                $nameCell->addText($retiree->name, ['size' => 9], ['spaceAfter' => 0]);
                $nameCell->addText($retiree->retirement_law, ['size' => 8], ['spaceAfter' => 0]);
                
                $dataRow->addCell(1600)->addText($retiree->position, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(1000)->addText($birthDate, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(1000)->addText($originalAppointment, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(1000)->addText($retirementDate, ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(1600)->addText(number_format($retiree->highest_monthly_salary, 2), ['size' => 9], ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(900)->addText(number_format($retiree->vl_credits, 2), ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(900)->addText(number_format($retiree->sl_credits, 2), ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(1300)->addText(number_format($retiree->terminal_leave_amount, 2), ['size' => 9], ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(1300)->addText(number_format($retiree->creditable_service, 2), ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataRow->addCell(1700)->addText(number_format($retiree->gratuity_amount, 2), ['size' => 9], ['alignment' => 'right', 'spaceAfter' => 0]);
                $dataRow->addCell(1500)->addText(number_format($retiree->retirement_gratuity_amount, 2), ['size' => 9], ['alignment' => 'right', 'spaceAfter' => 0]);
            }

            // Non-GSIS Subtotal Row
            $nonGsisSubtotalRow = $mainTable->addRow();
            $nonGsisSubtotalRow->addCell(3600)->addText('Sub Total:', ['bold' => true, 'size' => 10], ['spaceAfter' => 0]);
            for ($i = 0; $i < 7; $i++) {
                $nonGsisSubtotalRow->addCell()->addText('', [], ['spaceAfter' => 0]);
            }
            $nonGsisSubtotalRow->addCell(1300)->addText(number_format($nonGsisSubtotalTerminalLeave, 2), ['bold' => true, 'size' => 10], ['alignment' => 'right', 'spaceAfter' => 0]);
            $nonGsisSubtotalRow->addCell(1300)->addText('', [], ['spaceAfter' => 0]);
            $nonGsisSubtotalRow->addCell(1700)->addText('', [], ['spaceAfter' => 0]);
            $nonGsisSubtotalRow->addCell(1500)->addText(number_format($nonGsisSubtotalRetirementGratuity, 2), ['bold' => true, 'size' => 10], ['alignment' => 'right', 'spaceAfter' => 0]);

            // TOTAL Row
            $totalRow = $mainTable->addRow();
            $totalRow->addCell(3600)->addText('TOTAL:', ['bold' => true, 'size' => 10], ['spaceAfter' => 0]);
            for ($i = 0; $i < 7; $i++) {
                $totalRow->addCell()->addText('', [], ['spaceAfter' => 0]);
            }
            $totalRow->addCell(1300)->addText(number_format($totalTerminalLeave, 2), ['bold' => true, 'size' => 10], ['alignment' => 'right', 'spaceAfter' => 0]);
            $totalRow->addCell(1300)->addText('', [], ['spaceAfter' => 0]);
            $totalRow->addCell(1700)->addText('', [], ['spaceAfter' => 0]);
            $totalRow->addCell(1500)->addText(number_format($totalRetirementGratuity, 2), ['bold' => true, 'size' => 10], ['alignment' => 'right', 'spaceAfter' => 0]);

            // Signature Section - each spans 4 columns
            // Columns 1-4: 7200, Columns 5-8: 4400, Columns 9-12: 5800
            $sigRow = $mainTable->addRow();
            $preparedCell = $sigRow->addCell(7200, ['gridSpan' => 4, 'valign' => 'bottom']);
            $preparedCell->addText('PREPARED BY:', ['bold' => true, 'size' => 10], ['spaceAfter' => 400]);
            $preparedSigTable = $preparedCell->addTable(['width' => 100 * 50, 'unit' => 'pct']);
            $preparedSigRow = $preparedSigTable->addRow();
            $preparedSigCell = $preparedSigRow->addCell(null, ['borderBottomSize' => 6, 'borderBottomColor' => '000000', 'height' => 200]);
            $preparedSigCell->addText($signatories['prepared_by_name'], ['bold' => true, 'size' => 11], ['alignment' => 'center', 'spaceAfter' => 0]);
            $preparedCell->addText($signatories['prepared_by_position'], ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            
            $approvedCell = $sigRow->addCell(4400, ['gridSpan' => 4, 'valign' => 'bottom']);
            $approvedCell->addText('APPROVED BY:', ['bold' => true, 'size' => 10], ['spaceAfter' => 400]);
            $approvedSigTable = $approvedCell->addTable(['width' => 100 * 50, 'unit' => 'pct']);
            $approvedSigRow = $approvedSigTable->addRow();
            $approvedSigCell = $approvedSigRow->addCell(null, ['borderBottomSize' => 6, 'borderBottomColor' => '000000', 'height' => 200]);
            $approvedSigCell->addText($signatories['approved_by_name'], ['bold' => true, 'size' => 11], ['alignment' => 'center', 'spaceAfter' => 0]);
            $approvedCell->addText($signatories['approved_by_position'], ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            
            $dateCell = $sigRow->addCell(5800, ['gridSpan' => 4, 'valign' => 'bottom']);
            $dateCell->addText('DATE:', ['bold' => true, 'size' => 10], ['spaceAfter' => 400]);
            $dateSigTable = $dateCell->addTable(['width' => 100 * 50, 'unit' => 'pct']);
            $dateSigRow = $dateSigTable->addRow();
            $dateSigCell = $dateSigRow->addCell(null, ['borderBottomSize' => 6, 'borderBottomColor' => '000000', 'height' => 200]);
            $dateSigCell->addText($signatories['date'], ['bold' => true, 'size' => 11], ['alignment' => 'center', 'spaceAfter' => 0]);
            $dateCell->addText('Day/Mo/Year', ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);

            // Save to temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'bp_form_205_');
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            // Read the file content
            $docxContent = file_get_contents($tempFile);
            unlink($tempFile);

            $filename = 'bp_form_205_retirement_benefits_' . $fiscal_year . '_' . date('Y-m-d') . '.docx';

            return response($docxContent)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($docxContent));
        } catch (\Exception $e) {
            Log::error('Retirement Benefits DOCX Generation Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Request data: ' . json_encode($request->all()));
            return $this->serverErrorResponse('Failed to generate BP FORM 205 DOCX: ' . $e->getMessage());
        }
    }

    /**
     * Generate BP FORM 205 Excel
     */
    public function generateExcel(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'fiscal_year' => 'required|integer|min:2000|max:2100',
                'prepared_by_name' => 'nullable|string|max:255',
                'prepared_by_position' => 'nullable|string|max:255',
                'approved_by_name' => 'nullable|string|max:255',
                'approved_by_position' => 'nullable|string|max:255',
                'date' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $fiscal_year = $request->fiscal_year;

            // Get leave type IDs dynamically
            $leaveTypeIds = $this->getLeaveTypeIds();
            $vlTypeId = $leaveTypeIds['vl_id'];
            $slTypeId = $leaveTypeIds['sl_id'];

            // Get company/department info
            $company = DB::table('companies')->first();
            $department = $company ? $company->name : 'DEPARTMENT OF TRADE AND INDUSTRY';
            $agency = $company ? $company->name : 'PHILIPPINE TRADE TRAINING CENTER';

            // Get retirees data (same logic as generatePdf and generateDocx)
            try {
                $retirees = DB::table('employee_offboardings as eo')
                    ->join('employees as e', 'e.id', '=', 'eo.employee_id')
                    ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                    ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                    ->leftJoin('plantillas as pl', 'pl.id', '=', 'e.plantilla_id')
                    ->whereNotNull('eo.retirement_date')
                    ->whereYear('eo.retirement_date', $fiscal_year)
                    ->select(
                        'e.id',
                        'e.employee_no',
                        'eo.retirement_date',
                        'p.name as position',
                        'd.name as department',
                        'pl.code as plantilla_code',
                        'e.gsis_no',
                        'e.sss_no',
                        DB::raw("CASE
                            WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CONCAT(RTRIM(ISNULL(e.last_name,'')), ', ', RTRIM(ISNULL(e.first_name,'')), ' ', CASE WHEN e.middle_name IS NOT NULL AND e.middle_name <> '' THEN LEFT(RTRIM(e.middle_name), 1) ELSE '' END)
                            ELSE
                                CONCAT(
                                    RTRIM([dbo].[ufn_DecryptString](ISNULL(e.last_name,''),'$app_key')), ', ',
                                    RTRIM([dbo].[ufn_DecryptString](ISNULL(e.first_name,''),'$app_key')), ' ',
                                    CASE WHEN e.middle_name IS NOT NULL AND e.middle_name <> '' THEN LEFT(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), 1) ELSE '' END
                                )
                            END as name"),
                        'e.birthdate as birth_date',
                        'e.date_hired as original_appointment_date',
                        'e.salary as highest_monthly_salary'
                    )
                    ->orderBy('eo.retirement_date', 'asc')
                    ->get();
            } catch (\Exception $queryError) {
                Log::error('Retirement Benefits Query Error: ' . $queryError->getMessage());
                $retirees = collect([]);
            }

            // Ensure retirees is a collection
            if (!is_object($retirees) || !method_exists($retirees, 'map')) {
                $retirees = collect($retirees ?? []);
            }

            // Process each retiree to calculate required fields
            $processedRetirees = $retirees->map(function($retiree) use ($app_key, $vlTypeId, $slTypeId) {
                try {
                    $vlCredits = DB::table('leave_credits')
                        ->where('employee_id', $retiree->id)
                        ->where('leave_type_id', $vlTypeId)
                        ->value('credits');
                    $vlCredits = floatval($vlCredits ?? 0);

                    $slCredits = DB::table('leave_credits')
                        ->where('employee_id', $retiree->id)
                        ->where('leave_type_id', $slTypeId)
                        ->value('credits');
                    $slCredits = floatval($slCredits ?? 0);

                    $highestMonthlySalary = floatval($retiree->highest_monthly_salary ?? 0);
                    $terminalLeaveAmount = $highestMonthlySalary * ($vlCredits + $slCredits) * 0.0478087;

                    if ($retiree->original_appointment_date && $retiree->retirement_date) {
                        $originalAppointment = Carbon::parse($retiree->original_appointment_date);
                        $retirementDate = Carbon::parse($retiree->retirement_date);
                        $totalCreditableService = $originalAppointment->diffInDays($retirementDate) / 365.0;
                    } else {
                        $totalCreditableService = 0;
                    }

                    $gratuityAmount = $highestMonthlySalary * $totalCreditableService;
                    $retirementGratuityAmount = $terminalLeaveAmount + $gratuityAmount;
                } catch (\Exception $e) {
                    Log::error('Error processing retiree ID ' . ($retiree->id ?? 'unknown') . ': ' . $e->getMessage());
                    $vlCredits = 0;
                    $slCredits = 0;
                    $highestMonthlySalary = floatval($retiree->highest_monthly_salary ?? 0);
                    $terminalLeaveAmount = 0;
                    $totalCreditableService = 0;
                    $gratuityAmount = 0;
                    $retirementGratuityAmount = 0;
                }

                $isGsisMember = !empty($retiree->gsis_no);
                $retirementLaw = $isGsisMember ? 'RA 8291' : 'Other Retirement Laws';

                return (object)[
                    'id' => $retiree->id,
                    'name' => $retiree->name,
                    'position' => $retiree->position ?? '',
                    'plantilla_code' => $retiree->plantilla_code ?? '',
                    'birth_date' => $retiree->birth_date,
                    'original_appointment_date' => $retiree->original_appointment_date,
                    'retirement_date' => $retiree->retirement_date,
                    'highest_monthly_salary' => $highestMonthlySalary,
                    'vl_credits' => $vlCredits,
                    'sl_credits' => $slCredits,
                    'terminal_leave_amount' => $terminalLeaveAmount,
                    'creditable_service' => $totalCreditableService,
                    'gratuity_amount' => $gratuityAmount ?? 0,
                    'retirement_gratuity_amount' => $retirementGratuityAmount,
                    'retirement_law' => $retirementLaw,
                    'is_gsis_member' => $isGsisMember,
                ];
            });

            if (!is_object($processedRetirees) || !method_exists($processedRetirees, 'filter')) {
                $processedRetirees = collect($processedRetirees ?? []);
            }

            // Group retirees
            $gsisMembers = $processedRetirees->filter(function($retiree) {
                return isset($retiree->is_gsis_member) && $retiree->is_gsis_member === true;
            })->values();

            $nonGsisMembers = $processedRetirees->filter(function($retiree) {
                return !isset($retiree->is_gsis_member) || $retiree->is_gsis_member === false;
            })->values();

            // Calculate subtotals
            $gsisSubtotalTerminalLeave = $gsisMembers->sum('terminal_leave_amount');
            $gsisSubtotalRetirementGratuity = $gsisMembers->sum('retirement_gratuity_amount');
            $nonGsisSubtotalTerminalLeave = $nonGsisMembers->sum('terminal_leave_amount');
            $nonGsisSubtotalRetirementGratuity = $nonGsisMembers->sum('retirement_gratuity_amount');

            // Calculate totals
            $totalTerminalLeave = $gsisSubtotalTerminalLeave + $nonGsisSubtotalTerminalLeave;
            $totalRetirementGratuity = $gsisSubtotalRetirementGratuity + $nonGsisSubtotalRetirementGratuity;

            // Prepare signatories
            $signatories = [
                'prepared_by_name' => $request->prepared_by_name ?? 'MARIA ANTONIETTE S. ZOILO',
                'prepared_by_position' => $request->prepared_by_position ?? 'Administrative Officer V',
                'approved_by_name' => $request->approved_by_name ?? 'NELLY NITA N. DILLERA',
                'approved_by_position' => $request->approved_by_position ?? 'Executive Director',
                'date' => $request->date ? Carbon::parse($request->date)->format('d M y') : Carbon::now()->format('d M y'),
            ];

            // Create Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('BP FORM 205');

            // Set page orientation to landscape
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LEGAL);

            $currentRow = 1;

            // BP FORM 205 header (right aligned in column L)
            $sheet->setCellValue('L' . $currentRow, 'BP FORM 205');
            $sheet->getStyle('L' . $currentRow)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('L' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $currentRow++;

            // Title Section
            $currentRow++;
            $sheet->setCellValue('A' . $currentRow, 'LIST OF RETIREES');
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('A' . $currentRow . ':L' . $currentRow);
            $currentRow++;

            $sheet->setCellValue('A' . $currentRow, 'FOR PAYMENT OF TERMINAL LEAVE AND RETIREMENT GRATUITY BENEFITS');
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('A' . $currentRow . ':L' . $currentRow);
            $currentRow++;

            $sheet->setCellValue('A' . $currentRow, 'FY ' . $fiscal_year);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('A' . $currentRow . ':L' . $currentRow);
            $currentRow += 2;

            // Department and Agency info
            $sheet->setCellValue('A' . $currentRow, 'DEPARTMENT: ' . $department);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(11);
            $sheet->setCellValue('I' . $currentRow, '☐ Mandatory    ☐ Optional');
            $sheet->getStyle('I' . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle('I' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $currentRow++;

            $sheet->setCellValue('A' . $currentRow, 'AGENCY: ' . $agency);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(11);
            $currentRow += 2;

            // Main Data Table Headers - Row 1 (merged headers)
            $headerRow1 = $currentRow;
            $sheet->setCellValue('A' . $headerRow1, 'NAME OF RETIREES AND RETIREMENT LAW');
            $sheet->mergeCells('A' . $headerRow1 . ':A' . ($headerRow1 + 3));
            $sheet->getStyle('A' . $headerRow1)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('A' . $headerRow1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            $sheet->setCellValue('B' . $headerRow1, 'Position at Retirement (Item No.)');
            $sheet->mergeCells('B' . $headerRow1 . ':B' . ($headerRow1 + 3));
            $sheet->getStyle('B' . $headerRow1)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('B' . $headerRow1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            $sheet->setCellValue('C' . $headerRow1, 'Date (Mo/Day/Year)');
            $sheet->mergeCells('C' . $headerRow1 . ':E' . $headerRow1);
            $sheet->getStyle('C' . $headerRow1)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('C' . $headerRow1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('F' . $headerRow1, 'Highest Monthly Salary Per NOSAA');
            $sheet->mergeCells('F' . $headerRow1 . ':F' . ($headerRow1 + 3));
            $sheet->getStyle('F' . $headerRow1)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('F' . $headerRow1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            $sheet->setCellValue('G' . $headerRow1, 'TERMINAL LEAVE');
            $sheet->mergeCells('G' . $headerRow1 . ':I' . $headerRow1);
            $sheet->getStyle('G' . $headerRow1)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('G' . $headerRow1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('J' . $headerRow1, 'RETIREMENT GRATUITY');
            $sheet->mergeCells('J' . $headerRow1 . ':L' . $headerRow1);
            $sheet->getStyle('J' . $headerRow1)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('J' . $headerRow1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            // Header Row 2
            $headerRow2 = $currentRow + 1;
            $sheet->setCellValue('C' . $headerRow2, 'Birth');
            $sheet->mergeCells('C' . $headerRow2 . ':C' . ($headerRow2 + 2));
            $sheet->getStyle('C' . $headerRow2)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('C' . $headerRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('D' . $headerRow2, 'Original Appointment');
            $sheet->mergeCells('D' . $headerRow2 . ':D' . ($headerRow2 + 2));
            $sheet->getStyle('D' . $headerRow2)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('D' . $headerRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            $sheet->setCellValue('E' . $headerRow2, 'Retirement');
            $sheet->mergeCells('E' . $headerRow2 . ':E' . ($headerRow2 + 2));
            $sheet->getStyle('E' . $headerRow2)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('E' . $headerRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('G' . $headerRow2, 'No. of Leave Credits Earned');
            $sheet->mergeCells('G' . $headerRow2 . ':H' . $headerRow2);
            $sheet->getStyle('G' . $headerRow2)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('G' . $headerRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            $sheet->setCellValue('I' . $headerRow2, 'Amount');
            $sheet->mergeCells('I' . $headerRow2 . ':I' . ($headerRow2 + 2));
            $sheet->getStyle('I' . $headerRow2)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('I' . $headerRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('J' . $headerRow2, 'Total Creditable Service');
            $sheet->mergeCells('J' . $headerRow2 . ':J' . ($headerRow2 + 2));
            $sheet->getStyle('J' . $headerRow2)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('J' . $headerRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            $sheet->setCellValue('K' . $headerRow2, 'No of Gratuity Amounts (YR)');
            $sheet->mergeCells('K' . $headerRow2 . ':K' . ($headerRow2 + 2));
            $sheet->getStyle('K' . $headerRow2)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('K' . $headerRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            $sheet->setCellValue('L' . $headerRow2, 'Amount');
            $sheet->mergeCells('L' . $headerRow2 . ':L' . ($headerRow2 + 2));
            $sheet->getStyle('L' . $headerRow2)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('L' . $headerRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            // Header Row 3
            $headerRow3 = $currentRow + 2;
            $sheet->setCellValue('G' . $headerRow3, 'VL');
            $sheet->getStyle('G' . $headerRow3)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('G' . $headerRow3)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('H' . $headerRow3, 'SL');
            $sheet->getStyle('H' . $headerRow3)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('H' . $headerRow3)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            // Column number row
            $headerRow4 = $currentRow + 3;
            $sheet->setCellValue('A' . $headerRow4, '(1)');
            $sheet->setCellValue('B' . $headerRow4, '(2)');
            $sheet->setCellValue('C' . $headerRow4, '(3)');
            $sheet->setCellValue('D' . $headerRow4, '(4)');
            $sheet->setCellValue('E' . $headerRow4, '(5)');
            $sheet->setCellValue('F' . $headerRow4, '(6)');
            $sheet->setCellValue('G' . $headerRow4, '(7)');
            $sheet->setCellValue('H' . $headerRow4, '(8)');
            $sheet->setCellValue('I' . $headerRow4, '(9)');
            $sheet->setCellValue('J' . $headerRow4, '(10)');
            $sheet->setCellValue('K' . $headerRow4, '(11)');
            $sheet->setCellValue('L' . $headerRow4, '(12)');
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'] as $col) {
                $sheet->getStyle($col . $headerRow4)->getFont()->setSize(9);
                $sheet->getStyle($col . $headerRow4)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Apply borders to header rows
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ];
            $sheet->getStyle('A' . $headerRow1 . ':L' . $headerRow4)->applyFromArray($borderStyle);

            // Set header row background
            $headerFill = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D3D3D3'],
                ],
            ];
            $sheet->getStyle('A' . $headerRow1 . ':L' . $headerRow4)->applyFromArray($headerFill);

            $currentRow = $headerRow4 + 1;

            // GSIS Members Section
            $gsisHeaderRow = $currentRow;
            $sheet->setCellValue('A' . $gsisHeaderRow, 'For GSIS Members :');
            $sheet->getStyle('A' . $gsisHeaderRow)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('A' . $gsisHeaderRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->mergeCells('A' . $gsisHeaderRow . ':L' . $gsisHeaderRow);
            $sheet->getStyle('A' . $gsisHeaderRow . ':L' . $gsisHeaderRow)->applyFromArray($borderStyle);
            $currentRow++;

            $sheet->setCellValue('A' . $currentRow, 'I. Under RA NO. 1616');
            $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
            $sheet->mergeCells('A' . $currentRow . ':L' . $currentRow);
            $sheet->getStyle('A' . $currentRow . ':L' . $currentRow)->applyFromArray($borderStyle);
            $currentRow++;

            $sheet->setCellValue('A' . $currentRow, 'II. Other Retirement Laws (pls. specify, e.g. RA 8291)');
            $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
            $sheet->mergeCells('A' . $currentRow . ':L' . $currentRow);
            $sheet->getStyle('A' . $currentRow . ':L' . $currentRow)->applyFromArray($borderStyle);
            $currentRow++;

            // GSIS Members Data Rows
            foreach ($gsisMembers as $retiree) {
                $birthDate = $retiree->birth_date ? Carbon::parse($retiree->birth_date)->format('m/d/Y') : '';
                $originalAppointment = $retiree->original_appointment_date ? Carbon::parse($retiree->original_appointment_date)->format('m/d/Y') : '';
                $retirementDate = $retiree->retirement_date ? Carbon::parse($retiree->retirement_date)->format('m/d/Y') : '';

                $sheet->setCellValue('A' . $currentRow, $retiree->name . "\n" . $retiree->retirement_law);
                $sheet->getStyle('A' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('A' . $currentRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

                $sheet->setCellValue('B' . $currentRow, $retiree->position);
                $sheet->getStyle('B' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('B' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('C' . $currentRow, $birthDate);
                $sheet->getStyle('C' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('C' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('D' . $currentRow, $originalAppointment);
                $sheet->getStyle('D' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('D' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('E' . $currentRow, $retirementDate);
                $sheet->getStyle('E' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('E' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('F' . $currentRow, number_format($retiree->highest_monthly_salary, 2));
                $sheet->getStyle('F' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('F' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->setCellValue('G' . $currentRow, number_format($retiree->vl_credits, 2));
                $sheet->getStyle('G' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('G' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('H' . $currentRow, number_format($retiree->sl_credits, 2));
                $sheet->getStyle('H' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('H' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('I' . $currentRow, number_format($retiree->terminal_leave_amount, 2));
                $sheet->getStyle('I' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('I' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->setCellValue('J' . $currentRow, number_format($retiree->creditable_service, 2));
                $sheet->getStyle('J' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('J' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('K' . $currentRow, number_format($retiree->gratuity_amount, 2));
                $sheet->getStyle('K' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('K' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->setCellValue('L' . $currentRow, number_format($retiree->retirement_gratuity_amount, 2));
                $sheet->getStyle('L' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('L' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Apply borders
                $sheet->getStyle('A' . $currentRow . ':L' . $currentRow)->applyFromArray($borderStyle);
                $currentRow++;
            }

            // GSIS Subtotal Row
            $gsisSubtotalRow = $currentRow;
            $sheet->setCellValue('A' . $gsisSubtotalRow, 'Sub Total:');
            $sheet->getStyle('A' . $gsisSubtotalRow)->getFont()->setBold(true)->setSize(10);
            $sheet->setCellValue('I' . $gsisSubtotalRow, number_format($gsisSubtotalTerminalLeave, 2));
            $sheet->getStyle('I' . $gsisSubtotalRow)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('I' . $gsisSubtotalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('L' . $gsisSubtotalRow, number_format($gsisSubtotalRetirementGratuity, 2));
            $sheet->getStyle('L' . $gsisSubtotalRow)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('L' . $gsisSubtotalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('A' . $gsisSubtotalRow . ':L' . $gsisSubtotalRow)->applyFromArray($borderStyle);
            $currentRow++;

            // Non-GSIS Members Section
            $nonGsisHeaderRow = $currentRow;
            $sheet->setCellValue('A' . $nonGsisHeaderRow, 'For Non-GSIS Members : (e.g. Military/Uniformed)');
            $sheet->getStyle('A' . $nonGsisHeaderRow)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('A' . $nonGsisHeaderRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->mergeCells('A' . $nonGsisHeaderRow . ':L' . $nonGsisHeaderRow);
            $sheet->getStyle('A' . $nonGsisHeaderRow . ':L' . $nonGsisHeaderRow)->applyFromArray($borderStyle);
            $currentRow++;

            $sheet->setCellValue('A' . $currentRow, 'Retirement Laws (pls. specify)');
            $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
            $sheet->mergeCells('A' . $currentRow . ':L' . $currentRow);
            $sheet->getStyle('A' . $currentRow . ':L' . $currentRow)->applyFromArray($borderStyle);
            $currentRow++;

            // Non-GSIS Members Data Rows
            foreach ($nonGsisMembers as $retiree) {
                $birthDate = $retiree->birth_date ? Carbon::parse($retiree->birth_date)->format('m/d/Y') : '';
                $originalAppointment = $retiree->original_appointment_date ? Carbon::parse($retiree->original_appointment_date)->format('m/d/Y') : '';
                $retirementDate = $retiree->retirement_date ? Carbon::parse($retiree->retirement_date)->format('m/d/Y') : '';

                $sheet->setCellValue('A' . $currentRow, $retiree->name . "\n" . $retiree->retirement_law);
                $sheet->getStyle('A' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('A' . $currentRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

                $sheet->setCellValue('B' . $currentRow, $retiree->position);
                $sheet->getStyle('B' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('B' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('C' . $currentRow, $birthDate);
                $sheet->getStyle('C' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('C' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('D' . $currentRow, $originalAppointment);
                $sheet->getStyle('D' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('D' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('E' . $currentRow, $retirementDate);
                $sheet->getStyle('E' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('E' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('F' . $currentRow, number_format($retiree->highest_monthly_salary, 2));
                $sheet->getStyle('F' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('F' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->setCellValue('G' . $currentRow, number_format($retiree->vl_credits, 2));
                $sheet->getStyle('G' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('G' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('H' . $currentRow, number_format($retiree->sl_credits, 2));
                $sheet->getStyle('H' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('H' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('I' . $currentRow, number_format($retiree->terminal_leave_amount, 2));
                $sheet->getStyle('I' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('I' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->setCellValue('J' . $currentRow, number_format($retiree->creditable_service, 2));
                $sheet->getStyle('J' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('J' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('K' . $currentRow, number_format($retiree->gratuity_amount, 2));
                $sheet->getStyle('K' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('K' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->setCellValue('L' . $currentRow, number_format($retiree->retirement_gratuity_amount, 2));
                $sheet->getStyle('L' . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle('L' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Apply borders
                $sheet->getStyle('A' . $currentRow . ':L' . $currentRow)->applyFromArray($borderStyle);
                $currentRow++;
            }

            // Non-GSIS Subtotal Row
            $nonGsisSubtotalRow = $currentRow;
            $sheet->setCellValue('A' . $nonGsisSubtotalRow, 'Sub Total:');
            $sheet->getStyle('A' . $nonGsisSubtotalRow)->getFont()->setBold(true)->setSize(10);
            $sheet->setCellValue('I' . $nonGsisSubtotalRow, number_format($nonGsisSubtotalTerminalLeave, 2));
            $sheet->getStyle('I' . $nonGsisSubtotalRow)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('I' . $nonGsisSubtotalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('L' . $nonGsisSubtotalRow, number_format($nonGsisSubtotalRetirementGratuity, 2));
            $sheet->getStyle('L' . $nonGsisSubtotalRow)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('L' . $nonGsisSubtotalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('A' . $nonGsisSubtotalRow . ':L' . $nonGsisSubtotalRow)->applyFromArray($borderStyle);
            $currentRow++;

            // TOTAL Row
            $totalRow = $currentRow;
            $sheet->setCellValue('A' . $totalRow, 'TOTAL:');
            $sheet->getStyle('A' . $totalRow)->getFont()->setBold(true)->setSize(10);
            $sheet->setCellValue('I' . $totalRow, number_format($totalTerminalLeave, 2));
            $sheet->getStyle('I' . $totalRow)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('I' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('L' . $totalRow, number_format($totalRetirementGratuity, 2));
            $sheet->getStyle('L' . $totalRow)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('L' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('A' . $totalRow . ':L' . $totalRow)->applyFromArray($borderStyle);
            $currentRow += 2;

            // Signature Section
            $sigRow = $currentRow;
            $sheet->setCellValue('A' . $sigRow, 'PREPARED BY:');
            $sheet->getStyle('A' . $sigRow)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('A' . $sigRow)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
            $sheet->mergeCells('A' . $sigRow . ':D' . $sigRow);
            $sheet->getStyle('A' . $sigRow . ':D' . $sigRow)->applyFromArray($borderStyle);
            $sheet->getRowDimension($sigRow)->setRowHeight(60);

            $sheet->setCellValue('E' . $sigRow, 'APPROVED BY:');
            $sheet->getStyle('E' . $sigRow)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('E' . $sigRow)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
            $sheet->mergeCells('E' . $sigRow . ':H' . $sigRow);
            $sheet->getStyle('E' . $sigRow . ':H' . $sigRow)->applyFromArray($borderStyle);

            $sheet->setCellValue('I' . $sigRow, 'DATE:');
            $sheet->getStyle('I' . $sigRow)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('I' . $sigRow)->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
            $sheet->mergeCells('I' . $sigRow . ':L' . $sigRow);
            $sheet->getStyle('I' . $sigRow . ':L' . $sigRow)->applyFromArray($borderStyle);
            $currentRow++;

            // Signature names with underline effect (using border bottom)
            $sheet->setCellValue('A' . $currentRow, $signatories['prepared_by_name']);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('A' . $currentRow . ':D' . $currentRow);
            $preparedNameStyle = $sheet->getStyle('A' . $currentRow . ':D' . $currentRow);
            $preparedNameStyle->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $preparedNameStyle->getBorders()->getBottom()->setColor(new Color('000000'));
            $preparedNameStyle->applyFromArray($borderStyle);

            $sheet->setCellValue('E' . $currentRow, $signatories['approved_by_name']);
            $sheet->getStyle('E' . $currentRow)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('E' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('E' . $currentRow . ':H' . $currentRow);
            $approvedNameStyle = $sheet->getStyle('E' . $currentRow . ':H' . $currentRow);
            $approvedNameStyle->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $approvedNameStyle->getBorders()->getBottom()->setColor(new Color('000000'));
            $approvedNameStyle->applyFromArray($borderStyle);

            $sheet->setCellValue('I' . $currentRow, $signatories['date']);
            $sheet->getStyle('I' . $currentRow)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('I' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('I' . $currentRow . ':L' . $currentRow);
            $dateStyle = $sheet->getStyle('I' . $currentRow . ':L' . $currentRow);
            $dateStyle->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $dateStyle->getBorders()->getBottom()->setColor(new Color('000000'));
            $dateStyle->applyFromArray($borderStyle);
            $currentRow++;

            // Signature positions
            $sheet->setCellValue('A' . $currentRow, $signatories['prepared_by_position']);
            $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('A' . $currentRow . ':D' . $currentRow);
            $sheet->getStyle('A' . $currentRow . ':D' . $currentRow)->applyFromArray($borderStyle);

            $sheet->setCellValue('E' . $currentRow, $signatories['approved_by_position']);
            $sheet->getStyle('E' . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle('E' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('E' . $currentRow . ':H' . $currentRow);
            $sheet->getStyle('E' . $currentRow . ':H' . $currentRow)->applyFromArray($borderStyle);

            $sheet->setCellValue('I' . $currentRow, 'Day/Mo/Year');
            $sheet->getStyle('I' . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle('I' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('I' . $currentRow . ':L' . $currentRow);
            $sheet->getStyle('I' . $currentRow . ':L' . $currentRow)->applyFromArray($borderStyle);

            // Set column widths to match DOCX proportions and prevent overlaps
            $sheet->getColumnDimension('A')->setWidth(32);  // Name and Retirement Law
            $sheet->getColumnDimension('B')->setWidth(18);  // Position
            $sheet->getColumnDimension('C')->setWidth(12);  // Birth Date
            $sheet->getColumnDimension('D')->setWidth(16);  // Original Appointment
            $sheet->getColumnDimension('E')->setWidth(12);  // Retirement Date
            $sheet->getColumnDimension('F')->setWidth(18);  // Highest Monthly Salary
            $sheet->getColumnDimension('G')->setWidth(11);  // VL Credits
            $sheet->getColumnDimension('H')->setWidth(11);  // SL Credits
            $sheet->getColumnDimension('I')->setWidth(17);  // Terminal Leave Amount
            $sheet->getColumnDimension('J')->setWidth(18);  // Total Creditable Service
            $sheet->getColumnDimension('K')->setWidth(24);  // No of Gratuity Amounts (increased to prevent overlap)
            $sheet->getColumnDimension('L')->setWidth(20);  // Retirement Gratuity Amount

            // Set row heights for better visibility
            $sheet->getRowDimension($headerRow1)->setRowHeight(30);
            $sheet->getRowDimension($headerRow2)->setRowHeight(30);
            $sheet->getRowDimension($headerRow3)->setRowHeight(20);
            $sheet->getRowDimension($headerRow4)->setRowHeight(20);

            // Save to temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'bp_form_205_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            // Read the file content
            $excelContent = file_get_contents($tempFile);
            unlink($tempFile);

            $filename = 'bp_form_205_retirement_benefits_' . $fiscal_year . '_' . date('Y-m-d') . '.xlsx';

            return response($excelContent)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($excelContent));

        } catch (\Exception $e) {
            Log::error('Retirement Benefits Excel Generation Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Request data: ' . json_encode($request->all()));
            return $this->serverErrorResponse('Failed to generate BP FORM 205 Excel: ' . $e->getMessage());
        }
    }
}

