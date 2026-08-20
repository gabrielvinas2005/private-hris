<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoticeOfSalaryAdjustmentController extends Controller
{
    use ApiResponse, GeneratesPdf;
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
            $salary_schedules = DB::table('salary_schedules')->where('active', true)->orderby('name', 'asc')->get();

            $data = DB::table('employees')
            ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
            ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
            ->select(
                'employees.photo',
                'employees.id',
                'employees.employee_no',
                'employees.email',
                'employment_types.name as employment_type',
                'positions.name as position',
                'departments.name as department',
                'branches.name as branch',
                DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                            CONCAT(employees.first_name,' ',employees.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                        END as name")
            )
            ->orderBy('employees.first_name', 'asc')
            ->where([
                'employees.is_employee' => true,
                'employees.active' => true,
                'employees.is_plantilla' => true
            ])
            ->get();

        return $this->successResponse([
            'data' => $data,
            'salary_schedules' => $salary_schedules
        ], 'Notice of salary adjustments retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve notice of salary adjustments: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee' => 'required|exists:employees,id',
                'salary_schedule_id' => 'required|exists:salary_schedules,id',
                'signatory' => 'required|string',
                'position' => 'required|string',
                'signatory_admin' => 'required|string',
                'position_admin' => 'required|string'
            ], [
                'employee.required' => 'Please select employee.',
                'employee.exists' => 'Selected employee does not exist.',
                'salary_schedule_id.required' => 'Please select salary schedule.',
                'salary_schedule_id.exists' => 'Selected salary schedule does not exist.',
                'signatory.required' => 'Signatory name is required.',
                'position.required' => 'Signatory position is required.',
                'signatory_admin.required' => 'Admin signatory name is required.',
                'position_admin.required' => 'Admin signatory position is required.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

        // $image = base64_encode(file_get_contents(public_path('/dist/img/reports/header_img.jpg')));

        $nosa = DB::table('salary_adjustments as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->join('positions as c', 'b.position_id', '=', 'c.id')
            ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
            ->leftJoin('name_prefixes as e', 'b.name_prefix_id', '=', 'e.id')
            ->join('salary_schedules as f', 'a.salary_schedule_id', '=', 'f.id')
            ->join('plantillas as g', 'b.plantilla_id', '=', 'g.id')
            ->leftJoin('branches as br', 'br.id', '=', 'b.branch_id')
            ->select(
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                            CONCAT(b.first_name,' ',b.last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                        END as name"),
                'c.name as position',
                'a.old_salary_grade_id',
                'a.old_salary_step_id',
                'a.old_salary',
                'a.new_salary_grade_id',
                'a.new_salary_step_id',
                'a.new_salary',
                'd.name as department',
                'f.enabling_law',
                'f.effectivity',
                'g.code',
                DB::raw("CASE WHEN ISNULL(b.branch_id,0) <> 0 THEN
                              CASE WHEN br.is_main_branch = 1 THEN
                                    CAST(1 as INT)
                                   ELSE
                                    CAST(0 as INT)
                               END
                              ELSE
                               CAST(2 AS INT)
                         END AS from_branch")
            )
            ->where([
                'a.employee_id' => $request->employee,
                'a.salary_schedule_id' => $request->salary_schedule_id
            ])
            ->get();

        if ($nosa->isEmpty()) {
            return $this->errorResponse('No data to print.');
        }

        $document_no = DB::table('document_numbers')->where('id', 6)->get();

        if ($document_no->isNotEmpty()) {
            if ($nosa[0]->from_branch == 1) {
                $footer = [
                    'document_no' => $document_no[0]->co_document_number,
                    'revision' => $document_no[0]->co_revision,
                ];
            } elseif ($nosa[0]->from_branch == 0) {
                $footer = [
                    'document_no' => $document_no[0]->rd_document_number,
                    'revision' => $document_no[0]->rd_revision,
                ];
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }
        } else {
            $footer = [
                'document_no' => '',
                'revision' => '',
            ];
        }

        $signatories =  array(
            'signatory' => $request->signatory,
            'position' => $request->position,
            'signatory_admin' => $request->signatory_admin,
            'position_admin' => $request->position_admin,
        );

        $pdf = PDF::loadView('nosa.nosa_print', compact('nosa', 'signatories', 'footer'))->setOptions(['defaultFont' => 'sans-serif']);
        $pdf->setPaper('A4');
        
        $pdfContent = $pdf->output();
        $base64Pdf = base64_encode($pdfContent);

        $filename = 'notice_of_salary_adjustment_' . $request->employee . '_' . date('Y-m-d') . '.pdf';
        
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate notice of salary adjustment PDF: ' . $e->getMessage());
        }
    }
}
