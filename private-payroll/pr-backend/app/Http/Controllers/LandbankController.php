<?php

namespace App\Http\Controllers;

use File;
use Response;
use Illuminate\Support\Facades\Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Support\PayrollBenefitsEmployeeScope;
use App\Support\ReportDivisionFilter;
use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class LandbankController extends Controller
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
     * Get initial data for Landbank Text Report
     */
    public function index()
    {
        try {
            $divisions = ReportDivisionFilter::activeDivisions();

            $payrolls = DB::table('time_data as a')
                ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                ->join('payroll_intervals as c', 'c.id', '=', 'b.payroll_interval_id')
                ->join('payroll_cutoffs as d', 'd.id', '=', 'b.payroll_cutoff_id')
                ->select(
                    'b.id',
                    'b.payroll_interval_id',
                    DB::raw("CONCAT(c.name,' (',d.name,' - ',CONVERT(NVARCHAR(50),b.release_date,110),') ') as name"),
                    'b.release_date',
                    'd.name as cutoff_name'
                )
                ->where(['b.active' => true, 'b.posted' => true])
                ->orderBy('b.release_date', 'asc')
                ->distinct()
                ->get();

            return $this->successResponse([
                'divisions' => $divisions,
                'departments' => $divisions,
                'payrolls' => $payrolls
            ], 'Landbank data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve Landbank data: ' . $e->getMessage());
        }
    }

    private function resolveLandbankTextFilters(Request $request): array
    {
        $request->merge([
            'division_id' => $request->input('division_id', $request->input('department_id')),
            'department_id' => $request->input('department_id', $request->input('division_id')),
        ]);

        $validator = Validator::make(
            $request->all(),
            [
                'payroll_period_id' => 'required',
                'department_id' => 'required',
            ],
            [
                'payroll_period_id.required' => 'Payroll period is required.',
                'department_id.required' => 'Division is required.',
            ],
            [
                'department_id' => 'Division',
                'payroll_period_id' => 'Payroll Period',
            ]
        );

        if ($validator->fails()) {
            return [
                'error' => $this->validationErrorResponse(
                    $validator->errors(),
                    'Please select Division and Payroll Period.'
                ),
            ];
        }

        $divisionId = ReportDivisionFilter::resolveId($request);
        if (!$divisionId) {
            return [
                'error' => $this->errorResponse('Please select a valid Division.', 400),
            ];
        }

        return [
            'division_id' => (int) $divisionId,
            'payroll_period_id' => $request->payroll_period_id,
            'division_name' => ReportDivisionFilter::displayName($divisionId),
        ];
    }

    /**
     * Preview Landbank Text Report data (returns structured data for table display)
     */
    public function previewTextReport(Request $request)
    {
        try {
            $filters = $this->resolveLandbankTextFilters($request);
            if (isset($filters['error'])) {
                return $filters['error'];
            }

            $app_key = env("APP_KEY", "");

            // Get structured data for preview
            $landbankData = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    'a.net_pay',
                    'b.account_no',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                rtrim(CONCAT(upper(b.last_name),', ',upper(b.first_name),' ',upper(b.middle_name)))
                          ELSE
                                UPPER(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+', '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')+' '+RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))))
                          END as employee_name"),
                    DB::raw("
                    CONCAT(
                        CASE WHEN COALESCE(b.account_no,'') = '' 
                             THEN '0000000000' 
                             ELSE RTRIM(b.account_no) 
                        END,
                        CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                  RTRIM(CONCAT(UPPER(b.last_name),', ',UPPER(b.first_name),' ',UPPER(b.middle_name)))
                             ELSE
                                  UPPER(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+', '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')+' '+RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))))
                        END,
                        REPLICATE(
                            ' ',
                            50 - LEN(CONCAT(
                                CASE WHEN COALESCE(b.account_no,'') = '' 
                                     THEN '0000000000' 
                                     ELSE RTRIM(b.account_no) 
                                END,
                                CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                          RTRIM(CONCAT(UPPER(b.last_name),', ',UPPER(b.first_name),' ',UPPER(b.middle_name)))
                                     ELSE
                                          UPPER(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+', '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')+' '+RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))))
                                END
                            ))
                        ),
                        SUBSTRING(REPLACE(CAST((1000000000000000 + a.net_pay) AS CHAR(19)),'.',''),2,16),
                        SUBSTRING(
                            CASE WHEN COALESCE(b.account_no,'') = '' 
                                 THEN '0000000000' 
                                 ELSE RTRIM(b.account_no) 
                            END,
                            1,
                            3
                        ),
                        '00002'
                    ) as text_line
                    ")
                )
                ->where([
                    'a.payroll_period_id' => $filters['payroll_period_id'],
                    'b.division_id' => $filters['division_id'],
                ])
                ->orderBy('b.last_name', 'asc')
                ->get();

            if ($landbankData->isEmpty()) {
                return $this->errorResponse('No Data Found!');
            }

            $payroll = DB::table('time_data as a')
                ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                ->join('payroll_intervals as c', 'c.id', '=', 'b.payroll_interval_id')
                ->join('payroll_cutoffs as d', 'd.id', '=', 'b.payroll_cutoff_id')
                ->select(
                    'b.id',
                    DB::raw("CONCAT(c.name,' (',d.name,' - ',b.release_date,') ') as name"),
                    'b.release_date'
                )
                ->where('b.id', $filters['payroll_period_id'])
                ->distinct()
                ->first();

            return $this->successResponse([
                'data' => $landbankData,
                'summary' => [
                    'department_name' => $filters['division_name'],
                    'division_name' => $filters['division_name'],
                    'payroll_period' => $payroll->name ?? '',
                    'total_records' => $landbankData->count()
                ]
            ], 'Landbank text report preview retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve preview: ' . $e->getMessage());
        }
    }

    /**
     * Export Landbank Text Report (replaces PACSVAL)
     */
    public function exportTextReport(Request $request)
    {
        try {
            $filters = $this->resolveLandbankTextFilters($request);
            if (isset($filters['error'])) {
                return $filters['error'];
            }

            $app_key = env("APP_KEY", "");

            // Generate Landbank text format (similar to PACSVAL but with Landbank-specific format)
            $landbankData = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    DB::raw("
                    CONCAT(
                        CASE WHEN COALESCE(b.account_no,'') = '' 
                             THEN '0000000000' 
                             ELSE RTRIM(b.account_no) 
                        END,
                        CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                  RTRIM(CONCAT(UPPER(b.last_name),', ',UPPER(b.first_name),' ',UPPER(b.middle_name)))
                             ELSE
                                  UPPER(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+', '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')+' '+RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))))
                        END,
                        REPLICATE(
                            ' ',
                            50 - LEN(CONCAT(
                                CASE WHEN COALESCE(b.account_no,'') = '' 
                                     THEN '0000000000' 
                                     ELSE RTRIM(b.account_no) 
                                END,
                                CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                          RTRIM(CONCAT(UPPER(b.last_name),', ',UPPER(b.first_name),' ',UPPER(b.middle_name)))
                                     ELSE
                                          UPPER(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+', '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')+' '+RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))))
                                END
                            ))
                        ),
                        SUBSTRING(REPLACE(CAST((1000000000000000 + a.net_pay) AS CHAR(19)),'.',''),2,16),
                        SUBSTRING(
                            CASE WHEN COALESCE(b.account_no,'') = '' 
                                 THEN '0000000000' 
                                 ELSE RTRIM(b.account_no) 
                            END,
                            1,
                            3
                        ),
                        '00002'
                    ) as content
                    ")
                )
                ->where([
                    'a.payroll_period_id' => $filters['payroll_period_id'],
                    'b.division_id' => $filters['division_id'],
                ])
                ->orderBy('b.last_name', 'asc')
                ->pluck('content');

            if ($landbankData->isEmpty()) {
                return $this->errorResponse('No Data Found!');
            }

            $payroll = DB::table('time_data as a')
                ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                ->join('payroll_intervals as c', 'c.id', '=', 'b.payroll_interval_id')
                ->join('payroll_cutoffs as d', 'd.id', '=', 'b.payroll_cutoff_id')
                ->select(
                    'b.id',
                    DB::raw("CONCAT(c.name,' (',d.name,' - ',b.release_date,') ') as name"),
                    'b.release_date'
                )
                ->where('b.id', $filters['payroll_period_id'])
                ->distinct()
                ->get();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Landbank Text Report',
                'activity' => 'Export',
                'description' => 'Exported Landbank Text Report File.',
            );

            Audit::create($data_audit);

            $data = array();

            foreach ($landbankData as $content) {
                $data[] = $content . PHP_EOL;
            }

            // Footer/summary line
            $totalNetPay = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->where([
                    'a.payroll_period_id' => $filters['payroll_period_id'],
                    'b.division_id' => $filters['division_id'],
                ])
                ->sum('a.net_pay');

            $recordCount = $landbankData->count();

            $footerAccount = '9999999999';
            $footerName = 'LANDBANK OF THE PHILIPPINES';
            // For footer line, do not pad with spaces so amount starts immediately after the name
            $footerLeft = $footerAccount . $footerName;

            $amountInCents = (int) round($totalNetPay * 100);
            $footerAmount = str_pad((string) $amountInCents, 16, '0', STR_PAD_LEFT);

            $footerCount = str_pad((string) $recordCount, 5, '0', STR_PAD_LEFT);
            $footerBranch = substr($footerAccount, 0, 3);

            $footer = $footerLeft . $footerAmount . $footerCount . $footerBranch . '00002';
            $data[] = $footer . PHP_EOL;

            $divisionLabel = preg_replace('/[^\w\-]+/', '_', $filters['division_name']);
            $fileName = $divisionLabel . '_' . $payroll[0]->name . '_landbank.txt';
            $fileContent = implode('', $data);
            $base64Content = base64_encode($fileContent);

            return $this->successResponse([
                'file_content' => $base64Content,
                'filename' => $fileName,
                'content_type' => 'text/plain',
                'file_size' => strlen($fileContent),
                'payroll' => $payroll,
                'summary' => [
                    'department_name' => $filters['division_name'],
                    'division_name' => $filters['division_name'],
                    'payroll_period' => $payroll[0]->name,
                    'total_records' => $landbankData->count()
                ]
            ], 'Landbank text report exported successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to export Landbank text report: ' . $e->getMessage());
        }
    }

    /**
     * Get initial data for ATM Letter
     */
    public function atmLetterIndex()
    {
        try {
            $app_key = env("APP_KEY", "");

            $divisions = ReportDivisionFilter::activeDivisions();

            $payrolls = DB::table('time_data as a')
                ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                ->join('payroll_intervals as c', 'c.id', '=', 'b.payroll_interval_id')
                ->join('payroll_cutoffs as d', 'd.id', '=', 'b.payroll_cutoff_id')
                ->select(
                    'b.id',
                    'b.payroll_interval_id',
                    DB::raw("CONCAT(c.name,' (',d.name,' - ',CONVERT(NVARCHAR(50),b.release_date,110),') ') as name"),
                    'b.release_date',
                    'd.name as cutoff_name'
                )
                ->where(['b.active' => true, 'b.posted' => true])
                ->orderBy('b.release_date', 'asc')
                ->distinct()
                ->get();

            // Get employee options for signatory dropdown
            $employees = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->select(
                    'e.id',
                    'e.division_id',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(RTRIM(e.last_name), ', ', RTRIM(e.first_name), ' ', LEFT(RTRIM(e.middle_name), 1))
                        ELSE
                            CONCAT(
                                RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), ', ',
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), ' ',
                                LEFT(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), 1)
                            )
                        END as name"),
                    'p.name as position'
                )
                ->where([
                    'e.active' => true,
                    'e.is_employee' => true,
                ]);
            PayrollBenefitsEmployeeScope::apply($employees, 'e');
            $employees = $employees->orderBy('name', 'asc')->get();

            // Default signatory and personnel information
            $defaultSignatory = [
                'signatory_name' => 'Default Signatory Name',
                'signatory_position' => 'Default Position',
                'personnel_name' => 'MR. ROGELIO L. OMBAY',
                'personnel_position' => 'Branch Manager'
            ];

            return $this->successResponse([
                'divisions' => $divisions,
                'departments' => $divisions,
                'payrolls' => $payrolls,
                'employee_options' => $employees,
                'default_signatory' => $defaultSignatory
            ], 'ATM Letter data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve ATM Letter data: ' . $e->getMessage());
        }
    }

    private function resolveAtmLetterFilters(Request $request): array
    {
        $request->merge([
            'division_id' => $request->input('division_id', $request->input('department_id')),
            'department_id' => $request->input('department_id', $request->input('division_id')),
        ]);

        $validator = Validator::make(
            $request->all(),
            [
                'payroll_period_id' => 'required',
                'department_id' => 'required',
                'signatory_name' => 'required|string|max:255',
                'signatory_position' => 'required|string|max:255',
                'personnel_name' => 'required|string|max:255',
                'personnel_position' => 'required|string|max:255',
            ],
            [
                'payroll_period_id.required' => 'Payroll period is required.',
                'department_id.required' => 'Division is required.',
            ],
            [
                'department_id' => 'Division',
                'payroll_period_id' => 'Payroll Period',
            ]
        );

        if ($validator->fails()) {
            return [
                'error' => $this->validationErrorResponse(
                    $validator->errors(),
                    'Please complete Division, Payroll Period, and signatory fields.'
                ),
            ];
        }

        $divisionId = ReportDivisionFilter::resolveId($request);
        if (!$divisionId) {
            return [
                'error' => $this->errorResponse('Please select a valid Division.', 400),
            ];
        }

        return [
            'division_id' => (int) $divisionId,
            'payroll_period_id' => $request->payroll_period_id,
            'division_name' => ReportDivisionFilter::displayName($divisionId),
            'signatory_name' => $request->signatory_name,
            'signatory_position' => $request->signatory_position,
            'personnel_name' => $request->personnel_name,
            'personnel_position' => $request->personnel_position,
        ];
    }

    /**
     * Generate ATM Letter in PDF format
     */
    public function generateAtmLetterPdf(Request $request)
    {
        try {
            $filters = $this->resolveAtmLetterFilters($request);
            if (isset($filters['error'])) {
                return $filters['error'];
            }

            $app_key = env("APP_KEY", "");

            // Get employee data for the ATM letter
            $employees = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('divisions as c', 'b.division_id', '=', 'c.id')
                ->select(
                    'a.net_pay',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                rtrim(CONCAT(upper(b.last_name),', ',upper(b.first_name),' ',upper(b.middle_name)))
                          ELSE
                                UPPER(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+', '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')+' '+RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))))
                          END as full_name"),
                    'b.account_no',
                    'c.name as department'
                )
                ->where([
                    'a.payroll_period_id' => $filters['payroll_period_id'],
                    'b.division_id' => $filters['division_id'],
                ])
                ->orderBy('b.last_name', 'asc')
                ->get();

            if ($employees->isEmpty()) {
                return $this->errorResponse('No employee data found for the specified criteria');
            }

            $department = (object) [
                'id' => $filters['division_id'],
                'name' => $filters['division_name'],
            ];
            $payroll = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.*',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as period_name")
                )
                ->where('a.id', $filters['payroll_period_id'])
                ->first();

            $companies = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/reports/atmletterheader.png')));
            
            
            $footerImagePath = public_path('/dist/img/reports/atmletterfooter.png');
            $image2 = file_exists($footerImagePath) 
                ? base64_encode(file_get_contents($footerImagePath)) 
                : '';

            $signatories = [
                'signatory_name' => $filters['signatory_name'],
                'signatory_position' => $filters['signatory_position'],
                'personnel_name' => $filters['personnel_name'],
                'personnel_position' => $filters['personnel_position'],
            ];

            $pdf = PDF::loadView('landbank.atm_letter_print', compact(
                'employees',
                'department',
                'payroll',
                'companies',
                'image',
                'image2',
                'signatories'
            ))->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('A4', 'portrait');
            $pdfContent = $pdf->output();

            $filename = 'atm_letter_landbank_' . $filters['division_id'] . '_' . $filters['payroll_period_id'] . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ATM Letter PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate ATM Letter in Word format
     */
    public function generateAtmLetterWord(Request $request)
    {
        try {
            $filters = $this->resolveAtmLetterFilters($request);
            if (isset($filters['error'])) {
                return $filters['error'];
            }

            $app_key = env("APP_KEY", "");

            $employees = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('divisions as c', 'b.division_id', '=', 'c.id')
                ->select(
                    'a.net_pay',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                rtrim(CONCAT(upper(b.last_name),', ',upper(b.first_name),' ',upper(b.middle_name)))
                          ELSE
                                UPPER(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+', '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')+' '+RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))))
                          END as full_name"),
                    'b.account_no',
                    'c.name as department'
                )
                ->where([
                    'a.payroll_period_id' => $filters['payroll_period_id'],
                    'b.division_id' => $filters['division_id'],
                ])
                ->orderBy('b.last_name', 'asc')
                ->get();

            if ($employees->isEmpty()) {
                return $this->errorResponse('No employee data found for the specified criteria');
            }

            $department = (object) [
                'id' => $filters['division_id'],
                'name' => $filters['division_name'],
            ];
            $payroll = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.*',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as period_name")
                )
                ->where('a.id', $filters['payroll_period_id'])
                ->first();

            $companies = DB::table('companies')->get();

            $signatories = [
                'signatory_name' => $filters['signatory_name'],
                'signatory_position' => $filters['signatory_position'],
                'personnel_name' => $filters['personnel_name'],
                'personnel_position' => $filters['personnel_position'],
            ];

            $wordContent = $this->generateWordContent($employees, $department, $payroll, $companies, $signatories);

            $filename = 'atm_letter_landbank_' . $filters['division_id'] . '_' . $filters['payroll_period_id'] . '_' . date('Y-m-d') . '.docx';

            return response($wordContent)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($wordContent));

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ATM Letter Word document: ' . $e->getMessage());
        }
    }

    /**
     * Generate Word document content
     */
    private function generateWordContent($employees, $department, $payroll, $companies, $signatories)
    {
        // HTML encode all user-provided data to handle special characters
        $companyName = htmlspecialchars($companies[0]->name ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
        $periodName = htmlspecialchars($payroll->period_name ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
        $departmentName = htmlspecialchars($department->name ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
        $signatoryName = htmlspecialchars($signatories['signatory_name'] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
        $signatoryPosition = htmlspecialchars($signatories['signatory_position'] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
        $personnelName = htmlspecialchars($signatories['personnel_name'] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
        $personnelPosition = htmlspecialchars($signatories['personnel_position'] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');

        $html = '<html>
        <head>
            <meta charset="UTF-8">
            <title>ATM Letter for LandBank</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .content { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .signature-section { margin-top: 40px; }
                .signature-line { border-bottom: 1px solid #000; width: 200px; display: inline-block; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Republic of the Philippines</h2>
                <h3>' . $companyName . '</h3>
                <h2>ATM LETTER FOR LANDBANK</h2>
                <p><strong>Payroll Period:</strong> ' . $periodName . '</p>
                <p><strong>Department:</strong> ' . $departmentName . '</p>
            </div>

            <div class="content">
                <p>Dear LandBank Personnel,</p>
                <p>Please find below the list of employees and their corresponding net pay for the payroll period ' . $periodName . '.</p>

                <table>
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Account Number</th>
                            <th>Net Pay</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($employees as $employee) {
            // HTML encode employee data
            $employeeName = htmlspecialchars($employee->full_name ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
            $accountNo = htmlspecialchars($employee->account_no ?: 'N/A', ENT_QUOTES | ENT_XML1, 'UTF-8');
            $netPay = number_format($employee->net_pay ?? 0, 2);
            
            $html .= '<tr>
                <td>' . $employeeName . '</td>
                <td>' . $accountNo . '</td>
                <td>₱' . $netPay . '</td>
            </tr>';
        }

        $html .= '</tbody>
                </table>

                <div class="signature-section">
                    <p><strong>Prepared by:</strong></p>
                    <div class="signature-line"></div>
                    <p>' . $signatoryName . '</p>
                    <p>' . $signatoryPosition . '</p>

                    <p style="margin-top: 30px;"><strong>Received by:</strong></p>
                    <div class="signature-line"></div>
                    <p>' . $personnelName . '</p>
                    <p>' . $personnelPosition . '</p>
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }
}
