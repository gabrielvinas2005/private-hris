<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoticeOfSalaryStepController extends Controller
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

            $data = DB::table('employees')
                ->join('step_increments as f', function ($join) {
                    $join->on('employees.id', '=', 'f.employee_id')
                        ->where('f.is_approved', '=', 1); // Include only approved step increments
                })
            ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
            ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
            ->select(
            'employees.photo',
            'f.id',
            'employees.employee_no',
            'employees.email',
            'employment_types.name as employment_type',
            'positions.name as position',
            'departments.name as department',
            'branches.name as branch',
            DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                            CONCAT(employees.first_name,' ',employees.last_name,' - ',positions.name,' (sg-',
                            f.new_salary_grade_id, ' / sp-', f.new_salary_step_id, ')')
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))+' - '+RTRIM(positions.name)+' (sg-'+ CONVERT(nvarchar(50), f.new_salary_grade_id) +' / sp-'+ CONVERT(nvarchar(50), f.new_salary_step_id) +')'
                        END as name"),
            'f.current_salary_grade_id',
            'f.current_salary_step_id',
            'f.current_salary',
            'f.new_salary_grade_id',
            'f.new_salary_step_id',
            'f.new_salary'
        )
        ->where('employees.is_employee', true)
        ->where('employees.active', true)
        ->orderBy('employees.first_name', 'asc')
        ->paginate(10000);

            return $this->successResponse($data, 'Notice of salary step data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve notice of salary step data: ' . $e->getMessage());
        }
    }

    public function generatePdf(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee' => 'required|exists:step_increments,id',
                'signatory' => 'nullable|string',
                'position' => 'nullable|string',
                'joint_no' => 'nullable|string',
                'joint_date' => 'nullable|date',
                'salary_as_of' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $nosi = DB::table('step_increments as a')
        ->join('employees as b', 'a.employee_id', '=', 'b.id')
        ->join('positions as c', 'b.position_id', '=', 'c.id')
        ->join('departments as d', 'b.department_id', '=', 'd.id')
        ->join('plantillas as p', 'p.employee_id', '=', 'b.id') // Joining plantillas table
        ->join('salary_grades as s', 'a.current_salary_grade_id', '=', 's.id')
        ->join('salary_steps as ss', 'a.current_salary_step_id', '=', 'ss.id')
        ->leftJoin('name_prefixes as e', 'b.name_prefix_id', '=', 'e.id')
        ->leftJoin('branches as br', 'br.id', '=', 'b.branch_id')
        ->select(
             DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                        CONCAT(b.first_name,' ',b.last_name)
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                    END as name"),
            DB::raw("(a.new_salary_step_id - a.current_salary_step_id) as step"),
            'c.name as position',
            'a.current_salary_grade_id',
            'a.current_salary_step_id',
            'a.current_salary',
            'a.new_salary_grade_id',
            'a.new_salary_step_id',
            'a.new_salary',
            DB::raw("(a.new_salary - a.current_salary) as salary2"),
            'd.name as department',
            'a.effectivity_date',
            'p.code',
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
        ->where('a.id', $request->employee)
        ->where('b.is_plantilla', 1) // Filtering employees who have is_plantilla = 1
        ->where('a.is_approved', 1)// Filter only approved step increments
        ->get();

            if ($nosi->isEmpty()) {
                return $this->notFoundResponse('Employee step increment not found or not approved');
            }

            $document_no = DB::table('document_numbers')->where('id', 5)->get();

            if ($document_no->isNotEmpty()) {
                if ($nosi[0]->from_branch == 1) {
                    $footer = [
                        'document_no' => $document_no[0]->co_document_number,
                        'revision' => $document_no[0]->co_revision,
                    ];
                } elseif ($nosi[0]->from_branch == 0) {
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
            );

            $dates = array(
                'joint_no' => $request->joint_no,
                'joint_date' => $request->joint_date,
                'salary_as_of'  => $request->salary_as_of,
            );

            $pdf = PDF::loadView('nosi.nosi_print', compact('nosi', 'signatories', 'footer', 'dates'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'notice_of_salary_step_' . $nosi[0]->name . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate notice of salary step PDF: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        // Keep the original print method for backward compatibility
        return $this->generatePdf($request);
    }
}