<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use PDF;

class CertOfLastSalaryController extends Controller
{
    use ApiResponse;

    /**
     * Get offboarded employees with their last salary information.
     */
    public function getEmployees()
    {
        try {
            $app_key = env('APP_KEY', '');

            $employees = DB::table('employee_offboardings as c')
                ->leftJoin('employees as b', 'c.employee_id', '=', 'b.id')
                ->leftJoin(DB::raw('(SELECT ps1.employee_id, ps1.id
                                    FROM payroll_summaries ps1
                                    INNER JOIN (
                                        SELECT employee_id, MAX(created_at) as max_created_at
                                        FROM payroll_summaries
                                        GROUP BY employee_id
                                    ) ps2 ON ps1.employee_id = ps2.employee_id AND ps1.created_at = ps2.max_created_at
                                    WHERE ps1.id = (
                                        SELECT MAX(id)
                                        FROM payroll_summaries
                                        WHERE employee_id = ps1.employee_id
                                        AND created_at = ps2.max_created_at
                                    )) as ps_latest'), 'c.employee_id', '=', 'ps_latest.employee_id')
                ->leftJoin('payroll_summaries as a', 'a.id', '=', DB::raw('ps_latest.id'))
                ->leftJoin('payroll_periods as d', 'a.payroll_period_id', '=', 'd.id')
                ->leftJoin('positions as p', 'b.position_id', '=', 'p.id')
                ->leftJoin('companies as cp', 'b.company_id', '=', 'cp.id')
                ->leftJoin('name_prefixes as np', 'b.name_prefix_id', '=', 'np.id')
                ->select(
                    'c.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name, ' ', COALESCE(b.middle_name, ''), ' ', b.last_name)
                            ELSE
                               RTRIM([dbo].[ufn_DecryptString](b.first_name,'" . $app_key . "')) + ' ' +
                               COALESCE(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'" . $app_key . "')), '') + ' ' +
                               RTRIM([dbo].[ufn_DecryptString](b.last_name,'" . $app_key . "'))
                            END as full_name"),
                    'a.net_pay',
                    'c.date_effectivity as separation_date',
                    'a.created_at',
                    'c.employee_id',
                    'p.name as position_name',
                    'cp.name as company_name',
                    'np.name as name_prefix'
                )
                ->whereNotNull('a.net_pay')
                ->distinct()
                ->orderBy('c.id')
                ->get();

            return $this->successResponse($employees, 'Offboarded employees with salary data retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve offboarded employees', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve offboarded employees.');
        }
    }

    /**
     * Get employee last salary details by offboarding ID.
     */
    public function getEmployeeDetails($id)
    {
        try {
            $app_key = env('APP_KEY', '');

            $employee = DB::table('employee_offboardings as c')
                ->leftJoin('employees as b', 'c.employee_id', '=', 'b.id')
                ->leftJoin(DB::raw('(SELECT ps1.employee_id, ps1.id
                                    FROM payroll_summaries ps1
                                    INNER JOIN (
                                        SELECT employee_id, MAX(created_at) as max_created_at
                                        FROM payroll_summaries
                                        GROUP BY employee_id
                                    ) ps2 ON ps1.employee_id = ps2.employee_id AND ps1.created_at = ps2.max_created_at
                                    WHERE ps1.id = (
                                        SELECT MAX(id)
                                        FROM payroll_summaries
                                        WHERE employee_id = ps1.employee_id
                                        AND created_at = ps2.max_created_at
                                    )) as ps_latest'), 'c.employee_id', '=', 'ps_latest.employee_id')
                ->leftJoin('payroll_summaries as a', 'a.id', '=', DB::raw('ps_latest.id'))
                ->leftJoin('payroll_periods as d', 'a.payroll_period_id', '=', 'd.id')
                ->leftJoin('positions as p', 'b.position_id', '=', 'p.id')
                ->leftJoin('companies as cp', 'b.company_id', '=', 'cp.id')
                ->leftJoin('name_prefixes as np', 'b.name_prefix_id', '=', 'np.id')
                ->select(
                    'c.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name, ' ', COALESCE(b.middle_name, ''), ' ', b.last_name)
                            ELSE
                               RTRIM([dbo].[ufn_DecryptString](b.first_name,'" . $app_key . "')) + ' ' +
                               COALESCE(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'" . $app_key . "')), '') + ' ' +
                               RTRIM([dbo].[ufn_DecryptString](b.last_name,'" . $app_key . "'))
                            END as full_name"),
                    'a.net_pay',
                    'c.date_effectivity as separation_date',
                    'a.created_at',
                    'c.employee_id',
                    'p.name as position_name',
                    'cp.name as company_name',
                    'np.name as name_prefix'
                )
                ->where('c.id', $id)
                ->whereNotNull('a.net_pay')
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found or has no salary data.');
            }

            return $this->successResponse($employee, 'Employee details retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve employee details', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve employee details.');
        }
    }

    /**
     * Generate certificate of last salary PDF.
     */
    public function print(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offboarding_id' => 'required|integer|exists:employee_offboardings,id',
            'signatory' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'include_bonus_info' => 'nullable|boolean',
            'bonus_year' => 'nullable|string|max:10',
            'include_transfer_info' => 'nullable|boolean',
            'transfer_organization' => 'nullable|string|max:255',
            'transfer_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $app_key = env('APP_KEY', '');
            $offboardingId = $request->input('offboarding_id');

            // Get employee details with position and company
            $employee = DB::table('employee_offboardings as c')
                ->leftJoin('employees as b', 'c.employee_id', '=', 'b.id')
                ->leftJoin(DB::raw('(SELECT ps1.employee_id, ps1.id
                                    FROM payroll_summaries ps1
                                    INNER JOIN (
                                        SELECT employee_id, MAX(created_at) as max_created_at
                                        FROM payroll_summaries
                                        GROUP BY employee_id
                                    ) ps2 ON ps1.employee_id = ps2.employee_id AND ps1.created_at = ps2.max_created_at
                                    WHERE ps1.id = (
                                        SELECT MAX(id)
                                        FROM payroll_summaries
                                        WHERE employee_id = ps1.employee_id
                                        AND created_at = ps2.max_created_at
                                    )) as ps_latest'), 'c.employee_id', '=', 'ps_latest.employee_id')
                ->leftJoin('payroll_summaries as a', 'a.id', '=', DB::raw('ps_latest.id'))
                ->leftJoin('payroll_periods as d', 'a.payroll_period_id', '=', 'd.id')
                ->leftJoin('positions as p', 'b.position_id', '=', 'p.id')
                ->leftJoin('companies as cp', 'b.company_id', '=', 'cp.id')
                ->leftJoin('name_prefixes as np', 'b.name_prefix_id', '=', 'np.id')
                ->select(
                    'c.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name, ' ', COALESCE(b.middle_name, ''), ' ', b.last_name)
                            ELSE
                               RTRIM([dbo].[ufn_DecryptString](b.first_name,'" . $app_key . "')) + ' ' +
                               COALESCE(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'" . $app_key . "')), '') + ' ' +
                               RTRIM([dbo].[ufn_DecryptString](b.last_name,'" . $app_key . "'))
                            END as full_name"),
                    'a.net_pay',
                    'c.date_effectivity as separation_date',
                    'a.created_at',
                    'c.employee_id',
                    'p.name as position_name',
                    'cp.name as company_name',
                    'np.name as name_prefix'
                )
                ->where('c.id', $offboardingId)
                ->whereNotNull('a.net_pay')
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found or has no salary data.');
            }

            // Prepare data for PDF
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

            // Get company name or use default
            $companyName = $employee->company_name ?? 'strtoupper(CompanyHelper::getName())';
            $organizationName = $request->input('organization_name', $companyName);

            // Use NumberToWords helper for amount in words
            // Format: "Thirty-Three Thousand Nine Hundred Forty-Three Pesos and 38/100"
            $netPay = (float)$employee->net_pay;
            $wholePart = floor($netPay);
            $decimalPart = round(($netPay - $wholePart) * 100);

            // Ensure decimal part is between 0-99
            if ($decimalPart >= 100) {
                $wholePart += 1;
                $decimalPart = 0;
            }

            // Format decimal part with leading zero if needed (e.g., 06/100 instead of 6/100)
            $decimalPartFormatted = str_pad($decimalPart, 2, '0', STR_PAD_LEFT);

            $amountInWords = \App\Helpers\NumberToWords::convert($wholePart);
            $amountInWordsFormatted = ucwords($amountInWords) . ' Pesos and ' . $decimalPartFormatted . '/100';

            $data = [
                'employee' => $employee,
                'signatory' => $request->input('signatory', 'EDUARDO A. PUYAOAN JR.'),
                'position' => $request->input('position', 'Chief Administrative Officer'),
                'organization_name' => $organizationName,
                'amount_in_words' => $amountInWordsFormatted,
                'include_bonus_info' => $request->input('include_bonus_info', false),
                'bonus_year' => $request->input('bonus_year', date('Y')),
                'include_transfer_info' => $request->input('include_transfer_info', false),
                'transfer_organization' => $request->input('transfer_organization', ''),
                'transfer_date' => $request->input('transfer_date', ''),
                'header_img' => $headerImg,
                'footer_img' => $footerImg,
            ];

            // Generate PDF
            $pdf = PDF::loadView('cert_of_last_salary.cert_of_last_salary_print', $data)
                ->setOptions(['defaultFont' => 'helvetica']);
            $pdf->setPaper('A4');

            $pdfContent = $pdf->output();
            $filename = 'certificate_of_last_salary_' . now()->format('Ymd_His') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            Log::error('Failed to generate certificate of last salary PDF', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to generate certificate of last salary PDF.');
        }
    }
}
