<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;

class TradionessReportController extends Controller
{
    use ApiResponse, GeneratesPdf;
    public function index()
    {
        try {
            $branches = DB::table('branches')->get();

            return $this->successResponse($branches, 'Branches retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve branches: ' . $e->getMessage());
        }
    }

    public function generatePdf(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'branch_id' => 'required|exists:branches,id',
                'employee_id' => 'required|exists:employees,id',
                'report_type_id' => 'required|integer|in:1,2,3',
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from'
            ], [
                'branch_id.required' => 'Branch is required.',
                'employee_id.required' => 'Employee is required.',
                'report_type_id.required' => 'Report Type is required.',
                'date_from.required' => 'Date From is required.',
                'date_to.required' => 'Date To is required.',
                'date_to.after_or_equal' => 'Date To must be after or equal to Date From.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $company = DB::table('companies')->get();

            $date_from = $request->date_from;
            $date_to = $request->date_to;

            if ($request->report_type_id == 1) {
                $tardiness = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->join('positions as c', 'b.position_id', '=', 'c.id')
                    ->select(
                        'b.id',
                        'b.employee_no',
                        // Original (decrypting) name expression kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        //             UPPER(CONCAT(b.first_name,' ',b.last_name))
                        //         ELSE
                        //             RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                        //         END as name"),
                        DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as name"),
                        'a.date',
                        'a.late',
                        'a.undertime',
                        'a.absent',
                        'c.name as position'
                    )
                    ->where('a.employee_id', $request->employee_id)
                    ->where('a.late', '>', 0)
                    ->whereBetween('a.date', [$request->date_from, $request->date_to])
                    ->orderBy('a.date', 'asc')
                    ->get();

                if ($tardiness->isEmpty()) {
                    return $this->errorResponse('No data Found!', 404);
                }

                $pdf = PDF::loadView('tardiness.tardiness_late_report', compact('tardiness', 'date_from', 'date_to', 'company', 'image'))->setOptions(['defaultFont' => 'sans-serif']);
                $pdf->setPaper('A4');
                $pdfContent = $pdf->output();
                $base64Pdf = base64_encode($pdfContent);

                $filename = 'tardiness_late_report_' . $request->employee_id . '_' . $date_from . '_' . $date_to . '_' . date('Y-m-d') . '.pdf';
                
                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->header('Content-Length', strlen($pdfContent));
            } elseif ($request->report_type_id == 2) {
                $tardiness = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->join('positions as c', 'b.position_id', '=', 'c.id')
                    ->select(
                        'b.id',
                        'b.employee_no',
                        // Original (decrypting) name expression kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        //             UPPER(CONCAT(b.first_name,' ',b.last_name))
                        //         ELSE
                        //             RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                        //         END as name"),
                        DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as name"),
                        'a.date',
                        'a.late',
                        'a.undertime',
                        'a.absent',
                        'c.name as position'
                    )
                    ->where('a.employee_id', $request->employee_id)
                    ->where('a.undertime', '>', 0)
                    ->whereBetween('a.date', [$request->date_from, $request->date_to])
                    ->orderBy('a.date', 'asc')
                    ->get();

                if ($tardiness->isEmpty()) {
                    return $this->errorResponse('No data Found!', 404);
                }

                $pdf = PDF::loadView('tardiness.tardiness_ut_report', compact('tardiness', 'date_from', 'date_to', 'company', 'image'))->setOptions(['defaultFont' => 'sans-serif']);
                $pdf->setPaper('A4');
                $pdfContent = $pdf->output();
                $base64Pdf = base64_encode($pdfContent);

                $filename = 'tardiness_undertime_report_' . $request->employee_id . '_' . $date_from . '_' . $date_to . '_' . date('Y-m-d') . '.pdf';
                
                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->header('Content-Length', strlen($pdfContent));
            } else {
                $tardiness = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->join('positions as c', 'b.position_id', '=', 'c.id')
                    ->select(
                        'b.id',
                        'b.employee_no',
                        // Original (decrypting) name expression kept for reference:
                        // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        //             UPPER(CONCAT(b.first_name,' ',b.last_name))
                        //         ELSE
                        //             RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                        //         END as name"),
                        DB::raw("UPPER(CONCAT(b.first_name,' ',b.last_name)) as name"),
                        'a.date',
                        'a.late',
                        'a.undertime',
                        'a.absent',
                        'c.name as position'
                    )
                    ->where('a.employee_id', $request->employee_id)
                    ->where('a.absent', '>', 0)
                    ->whereBetween('a.date', [$request->date_from, $request->date_to])
                    ->orderBy('a.date', 'asc')
                    ->get();

                if ($tardiness->isEmpty()) {
                    return $this->errorResponse('No data Found!', 404);
                }

                $pdf = PDF::loadView('tardiness.tardiness_absent_report', compact('tardiness', 'date_from', 'date_to', 'company', 'image'))->setOptions(['defaultFont' => 'sans-serif']);
                $pdf->setPaper('A4');
                $pdfContent = $pdf->output();
                $base64Pdf = base64_encode($pdfContent);

                $filename = 'tardiness_absent_report_' . $request->employee_id . '_' . $date_from . '_' . $date_to . '_' . date('Y-m-d') . '.pdf';
                
                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->header('Content-Length', strlen($pdfContent));
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate tardiness report PDF: ' . $e->getMessage());
        }
    }

    public function view(Request $request)
    {
        // Keep the original view method for backward compatibility
        return $this->generatePdf($request);
    }
}
