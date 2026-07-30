<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use PDF;
use Carbon\Carbon;

class RenderedServiceCertificateController extends Controller
{
    use ApiResponse;

    public function employees()
    {
        $app_key = config('app.key');

        $employees = DB::table('employees as e')
            ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
            ->select(
                'e.id',
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as full_name"),
                'p.name as position'
            )
            ->where([
                ['e.employment_type_id', '=', 2], // COS
                ['e.is_employee', '=', true],
                ['e.active', '=', true],
            ])
            ->orderBy('full_name', 'asc')
            ->get();

        return $this->successResponse($employees, 'Employees loaded');
    }

    public function print(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'cover_start' => 'required|date',
            'cover_end' => 'required|date|after_or_equal:cover_start',
            'noted_by' => 'required|string|min:1',
            'noted_by_position' => 'required|string|min:1',
            'approved_by' => 'required|string|min:1',
            'approved_by_position' => 'required|string|min:1',
        ]);

        $app_key = config('app.key');

        $employee = DB::table('employees as e')
            ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->select(
                'e.id',
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as full_name"),
                'p.name as position_name',
                'd.name as department_name'
            )
            ->where([
                ['e.id', '=', $request->employee_id],
                ['e.employment_type_id', '=', 2],
                ['e.is_employee', '=', true],
                ['e.active', '=', true],
            ])
            ->first();

        if (!$employee) {
            return $this->notFoundResponse('Employee not found or not COS');
        }

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

        $issueDate = Carbon::now();
        $coverStart = Carbon::parse($request->cover_start);
        $coverEnd = Carbon::parse($request->cover_end);

        $pdf = PDF::loadView('certificates.rendered_service_certificate', [
            'employee' => $employee,
            'coverStart' => $coverStart,
            'coverEnd' => $coverEnd,
            'issueDate' => $issueDate,
            'notedBy' => $request->noted_by,
            'notedByPosition' => $request->noted_by_position,
            'approvedBy' => $request->approved_by,
            'approvedByPosition' => $request->approved_by_position,
            'headerImg' => $headerImg,
            'footerImg' => $footerImg
        ])->setOptions(['defaultFont' => 'sans-serif']);

        $pdf->setPaper('A4');
        $pdf_content = $pdf->output();
        $filename = 'rendered_service_certificate_' . str_replace(' ', '_', $employee->full_name) . '.pdf';

        return response($pdf_content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdf_content));
    }
}

