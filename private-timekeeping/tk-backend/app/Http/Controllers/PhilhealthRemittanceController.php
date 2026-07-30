<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PhilhealthRemittanceController extends Controller
{
    use ApiResponse, GeneratesPdf;
use App\Traits\GeneratesPdf;

    public function report()
    {
        try {
            $app_key = env("APP_KEY", "");

            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name"),
                    'a.release_date'
                )
                ->orderBy('a.release_date', 'asc')
                ->get();

            $signatory = array(
                'signatory' => '',
                'position' =>  '',
            );

            return $this->successResponse([
                'pay_periods' => $pay_periods,
                'signatory' => $signatory
            ], 'Philhealth remittance data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve philhealth remittance data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $validator = Validator::make($request->all(), [
                'signatory' => 'required|string|max:255',
                'position' => 'required|string|max:255',
                'payroll_period' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $companies = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $dtl = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('payroll_periods as c', 'a.payroll_period_id', '=', 'c.id')
                ->leftJoin('name_suffixes as e', 'b.name_suffix_id', '=', 'e.id')
                ->select(
                    'a.payroll_period_id',
                    DB::raw("FORMAT(c.release_date, 'MMMM yyyy') as month_year"),
                    'a.id',
                    'c.release_date',
                    'b.employee_no',
                    DB::raw("CONCAT(
                        CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END,
                        ', ',
                        CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END,
                        ' ',
                        CASE
                            WHEN ISNULL(b.is_encrypted, 0) = 0 THEN LEFT(b.middle_name, 1)
                            ELSE LEFT(dbo.ufn_DecryptString(b.middle_name, '$app_key'), 1)
                        END,
                        CASE WHEN b.middle_name IS NOT NULL AND b.middle_name <> '' THEN '. ' ELSE ' ' END,
                        CASE WHEN e.name IS NOT NULL THEN e.name ELSE '' END
                    ) as full_name"),
                    'a.philhealth as philhealth_gs',
                    'a.philhealth as philhealth_ps',
                    'a.salary',
                )
                ->distinct()
                ->where('a.philhealth', '>', 0)
                ->where('a.payroll_period_id', $request->payroll_period)
                ->get();

            if ($dtl->isEmpty()) {
                return $this->errorResponse('No data found for the selected payroll period.');
            }

            $pdf = PDF::loadView('philhealth_remittance.philhealth_remittance_print', compact('dtl', 'image','companies'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'portrait');
            $pdfContent = $pdf->output();

            $filename = 'philhealth_remittance_report_' . $request->payroll_period . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate philhealth remittance report: ' . $e->getMessage());
        }
    }
}