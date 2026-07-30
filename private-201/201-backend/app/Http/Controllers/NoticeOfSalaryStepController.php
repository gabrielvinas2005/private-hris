<?php

namespace App\Http\Controllers;

use PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoticeOfSalaryStepController extends Controller
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

            $data = DB::table('employees')
                ->join('step_increments as f', 'employees.id', '=', 'f.employee_id')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select(
                    'employees.photo',
                    DB::raw('f.id as id'),
                    'employees.id as employee_id',
                    'f.id as step_increment_id',
                    'employees.employee_no',
                    'employees.email',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                            ISNULL(employees.first_name, '') + ' ' + ISNULL(employees.last_name, '') + ' - ' + ISNULL(positions.name, '') + ' (sg-' + CONVERT(nvarchar(50), f.new_salary_grade_id) + ' / sp-' + CONVERT(nvarchar(50), f.new_salary_step_id) + ')'
                        ELSE
                            ISNULL(RTRIM(dbo.ufn_DecryptString(employees.first_name,'$app_key')), '') + ' ' + ISNULL(RTRIM(dbo.ufn_DecryptString(employees.last_name,'$app_key')), '') + ' - ' + ISNULL(RTRIM(positions.name), '') + ' (sg-' + CONVERT(nvarchar(50), f.new_salary_grade_id) + ' / sp-' + CONVERT(nvarchar(50), f.new_salary_step_id) + ')'
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
                ->where('f.is_approved', 1) // Include only approved step increments
                ->where(function($query) {
                    $query->where('employees.is_hold', false)
                          ->orWhereNull('employees.is_hold')
                          ->orWhere('employees.is_hold', 0);
                })
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
                'employee' => 'required|integer', // Changed: accept employee ID instead of step_increment ID
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

            // Find step increment for the employee
            $nosi = DB::table('step_increments as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('plantillas as p', function ($join) {
                    $join->on('p.employee_id', '=', 'b.id')
                        ->where('p.active', true);
                })
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->join('departments as d', 'b.department_id', '=', 'd.id')
                ->join('salary_grades as s', 'a.current_salary_grade_id', '=', 's.id')
                ->leftJoin('salary_grades as sn', 'a.new_salary_grade_id', '=', 'sn.id')
                ->join('salary_steps as ss', 'a.current_salary_step_id', '=', 'ss.id')
                ->leftJoin('salary_steps as ssn', 'a.new_salary_step_id', '=', 'ssn.id')
                ->leftJoin('salary_grades as se', 'b.salary_grade_id', '=', 'se.id')
                ->leftJoin('salary_steps as sse', 'b.salary_step_id', '=', 'sse.id')
                ->leftJoin('name_prefixes as e', 'b.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'b.branch_id')
                ->select(
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                            ISNULL(b.first_name, '') + ' ' + ISNULL(b.last_name, '')
                        ELSE
                            ISNULL(RTRIM(dbo.ufn_DecryptString(b.first_name,'$app_key')), '') + ' ' + ISNULL(RTRIM(dbo.ufn_DecryptString(b.last_name,'$app_key')), '')
                        END as name"),
                    DB::raw("(a.new_salary_step_id - a.current_salary_step_id) as step"),
                    'c.name as position',
                    'a.current_salary_grade_id',
                    's.name as current_salary_grade_name',
                    'a.current_salary_step_id',
                    'ss.name as current_salary_step_name',
                    'b.salary_grade_id as employee_salary_grade_id',
                    'se.name as employee_salary_grade_name',
                    'b.salary_step_id as employee_salary_step_id',
                    'sse.name as employee_salary_step_name',
                    'a.current_salary',
                    'a.new_salary_grade_id',
                    'sn.name as new_salary_grade_name',
                    'a.new_salary_step_id',
                    'ssn.name as new_salary_step_name',
                    'a.new_salary',
                    DB::raw("(a.new_salary - a.current_salary) as salary2"),
                    'd.name as department',
                    'a.effectivity_date',
                    DB::raw("ISNULL(p.code, '') as code"), // Handle null plantilla code
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
                ->where('b.id', $request->employee) // Changed: filter by employee ID instead of step_increment ID
                ->where('b.is_plantilla', 1)
                ->where('a.is_approved', 1)
                ->orderBy('a.id', 'desc') // Get the most recent approved step increment
                ->first(); // Changed: use first() instead of get() since we only need one record

            if (!$nosi) {
                // Check if employee exists and has approved step increment
                $employeeCheck = DB::table('employees as b')
                    ->join('step_increments as a', 'a.employee_id', '=', 'b.id')
                    ->where('b.id', $request->employee)
                    ->where('b.is_plantilla', 1)
                    ->where('a.is_approved', 1)
                    ->exists();

                if (!$employeeCheck) {
                    return $this->notFoundResponse('Employee step increment not found. Please ensure the employee has an approved step increment and is marked as plantilla.');
                }

                return $this->notFoundResponse('Employee step increment not found or not approved');
            }

            // Convert single record to array format for compatibility with existing code
            $nosi = collect([$nosi]);

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

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position,
            );

            $dates = array(
                'joint_no' => $request->joint_no,
                'joint_date' => $request->joint_date,
                'salary_as_of' => $request->salary_as_of,
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
