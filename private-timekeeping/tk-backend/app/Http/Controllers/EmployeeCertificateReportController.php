<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;
use App\Helpers\NumberToWords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class EmployeeCertificateReportController extends Controller
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
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.employee_no',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN employees.email ELSE dbo.ufn_DecryptString(employees.email,'$app_key') END as email"),
                    'employees.date_hired',
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
                ->where(['employees.is_employee' => true, 'employees.active' => true])
                ->get();

            return $this->successResponse($data, 'Employee certificates retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee certificates: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");
            $companies = DB::table('companies')->get();

            $validator = validator($request->all(), [
                'employee' => 'required|integer|exists:employees,id',
                'signatory' => 'required|string|min:1',
                'position' => 'required|string|min:1'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    'a.gender_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(e.name,' ',a.last_name)
                            ELSE
                                RTRIM(e.name)+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name_sig"),
                    DB::raw("CASE WHEN ISNULL(a.branch_id,0) <> 0 THEN
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
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $document_no = DB::table('document_numbers')->where('id', 3)->get();

            if ($document_no->isNotEmpty()) {
                if ($employees[0]->from_branch == 1) {
                    $footer = [
                        'document_no' => $document_no[0]->co_document_number,
                        'revision' => $document_no[0]->co_revision,
                    ];
                } elseif ($employees[0]->from_branch == 0) {
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

            // Use NumberFormatter if available, otherwise use our fallback
            if (class_exists('NumberFormatter')) {
                $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
                $salary_word = $f->format(($employees[0]->salary) * 12);
            } else {
                $salary_word = NumberToWords::formatCurrency(($employees[0]->salary) * 12);
            }
            $annual_salary = number_format(($employees[0]->salary) * 12);
            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.employee_id', $request->employee)
                ->get();

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position
            );

            $pdf = PDF::loadView('employee_certificates.employee_certificate_print', compact(
                'employees',
                'incomes',
                'salary_word',
                'annual_salary',
                'signatories',
                'image',
                'companies',
                'footer'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "employee_certificate_{$request->employee}_" . date('Y-m-d') . ".pdf";

            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate employee certificate PDF: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $employee = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    'a.email',
                    'a.date_hired',
                    'a.salary',
                    'a.gender_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(e.name,' ',a.last_name)
                            ELSE
                                RTRIM(e.name)+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name_sig"),
                    DB::raw("CASE WHEN ISNULL(a.branch_id,0) <> 0 THEN
                                  CASE WHEN br.is_main_branch = 1 THEN
                                        CAST(1 as INT)
                                       ELSE
                                        CAST(0 as INT)
                                   END
                                  ELSE
                                   CAST(2 AS INT)
                             END AS from_branch")
                )
                ->where('a.id', $id)
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found');
            }

            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name',
                    'a.amount'
                )
                ->where('a.employee_id', $id)
                ->get();

            // Use NumberFormatter if available, otherwise use our fallback
            if (class_exists('NumberFormatter')) {
                $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
                $salary_word = $f->format(($employee->salary) * 12);
            } else {
                $salary_word = NumberToWords::formatCurrency(($employee->salary) * 12);
            }
            $annual_salary = number_format(($employee->salary) * 12);

            return $this->successResponse([
                'employee' => $employee,
                'compensation_data' => [
                    'incomes' => $incomes
                ],
                'salary_details' => [
                    'monthly_salary' => $employee->salary,
                    'annual_salary' => $annual_salary,
                    'salary_in_words' => $salary_word
                ],
                'summary' => [
                    'total_incomes' => $incomes->sum('amount'),
                    'income_count' => $incomes->count()
                ]
            ], 'Employee certificate data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee certificate data: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))
                            END as name"),
                    'b.name as position',
                    'c.name as department'
                )
                ->where(['a.is_employee' => true, 'a.active' => true])
                ->orderBy('a.first_name', 'asc')
                ->get();

            return $this->successResponse([
                'employees' => $employees,
                'fields' => [
                    'employee' => ['type' => 'select', 'required' => true, 'label' => 'Employee'],
                    'signatory' => ['type' => 'text', 'required' => true, 'label' => 'Signatory Name'],
                    'position' => ['type' => 'text', 'required' => true, 'label' => 'Signatory Position']
                ]
            ], 'Create employee certificate form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }
}
