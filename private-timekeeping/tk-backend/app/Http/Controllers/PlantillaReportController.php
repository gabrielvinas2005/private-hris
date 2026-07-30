<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \NumberFormatter;
use App\Traits\ApiResponse;

class PlantillaReportController extends Controller
{
    use ApiResponse, GeneratesPdf;
use App\Traits\GeneratesPdf;

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
            $data  = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->leftJoin('departments', 'departments.id', '=', 'plantillas.department_id')
                ->select('plantillas.id', 'plantillas.code', 'positions.name as position', 'salary_steps.name as step', 'salary_grades.name as grade', 'departments.name as department', 'plantillas.eligibility as eligibility', 'plantillas.experience as experience', 'plantillas.training as training', 'plantillas.education as education', 'plantillas.unit as unit', 'plantillas.publication_from as publication_from', 'plantillas.publication_to as publication_to', DB::raw("case when plantillas.employee_id = 0 then 'Vacant' else 'Occupied' end as status"), 'plantillas.active')
                ->orderBy('plantillas.code', 'asc')
                ->get();

            return $this->successResponse($data, 'Plantilla report data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve plantilla report data: ' . $e->getMessage());
        }
    }

    public function generatePdf(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'status_id' => 'required|integer|in:0,1',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $companies = DB::table('companies')->get();

            if ($request->status_id == 0) {
                $data  = DB::table('plantillas')
                    ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                    ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                    ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                    ->leftJoin('departments', 'departments.id', '=', 'plantillas.department_id')
                    ->select('plantillas.id', 'plantillas.code', 'positions.name as position', 'salary_steps.name as step', 'salary_grades.name as grade', 'departments.name as department', 'plantillas.eligibility as eligibility', 'plantillas.experience as experience', 'plantillas.training as training', 'plantillas.education as education', 'plantillas.unit as unit', 'plantillas.publication_from as publication_from', 'plantillas.publication_to as publication_to', DB::raw("case when plantillas.employee_id = 0 then 'Vacant' else 'Occupied' end as status"), DB::raw('ROW_NUMBER() OVER(ORDER BY plantillas.code ASC) AS row'), 'plantillas.active')
                    ->where('plantillas.employee_id', 0)
                    ->orderBy('plantillas.code', 'asc')
                    ->get();

                $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

                $pdf = PDF::loadView('plantillas.plantilla_report_print', compact('data', 'image', 'companies'))->setOptions(['defaultFont' => 'sans-serif']);
                $pdf->setPaper('A4', 'landscape');
                $pdfContent = $pdf->output();
                $base64Pdf = base64_encode($pdfContent);

                $filename = 'plantilla_report_vacant_' . date('Y-m-d') . '.pdf';
                
                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->header('Content-Length', strlen($pdfContent));
            } else {
                $data  = DB::table('plantillas')
                    ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                    ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                    ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                    ->join('employees', 'plantillas.employee_id', '=', 'employees.id')
                    ->leftJoin('departments', 'departments.id', '=', 'plantillas.department_id')
                    ->select(
                        'plantillas.id',
                        'plantillas.code',
                        'positions.name as position',
                        'salary_steps.name as step',
                        'salary_grades.name as grade',
                        'departments.name as department',
                        'plantillas.eligibility as eligibility',
                        'plantillas.experience as experience',
                        'plantillas.training as training',
                        'plantillas.education as education',
                        'plantillas.unit as unit',
                        'plantillas.publication_from as publication_from',
                        'plantillas.publication_to as publication_to',
                        DB::raw("case when plantillas.employee_id = 0 then 'Vacant' else 'Occupied' end as status"),
                        DB::raw('ROW_NUMBER() OVER(ORDER BY plantillas.code ASC) AS row'),
                        'plantillas.active',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                    )
                    ->where('plantillas.employee_id', '<>', 0)
                    ->orderBy('plantillas.code', 'asc')
                    ->get();

                $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

                $pdf = PDF::loadView('plantillas.plantilla_report_occupied_print', compact('data', 'image', 'companies'))->setOptions(['defaultFont' => 'sans-serif']);
                $pdf->setPaper('A4', 'landscape');
                $pdfContent = $pdf->output();
                $base64Pdf = base64_encode($pdfContent);

                $filename = 'plantilla_report_occupied_' . date('Y-m-d') . '.pdf';
                
                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->header('Content-Length', strlen($pdfContent));
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate plantilla report PDF: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        // Keep the original print method for backward compatibility
        return $this->generatePdf($request);
    }
}
