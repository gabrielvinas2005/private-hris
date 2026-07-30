<?php

namespace App\Http\Controllers;

use PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \NumberFormatter;

class TerminalLeaveEndorsementReportController extends Controller
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
     * Get all employees for terminal leave endorsement
     */
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
                    'employees.email',
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

            return $this->successResponse($data, 'Employees for terminal leave endorsement retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employees: ' . $e->getMessage());
        }
    }

    /**
     * Get terminal leave endorsement data for specific employee
     */
    public function getEndorsementData(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if (!$request->employee) {
                return $this->errorResponse('Please select employee.');
            }

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
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
                    DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig")
                )
                ->where('a.id', $request->employee)
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
            $salary_word = $f->format(($employees[0]->salary) * 12);
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

            return $this->successResponse([
                'employees' => $employees,
                'incomes' => $incomes,
                'salary_word' => $salary_word,
                'annual_salary' => $annual_salary
            ], 'Terminal leave endorsement data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve terminal leave endorsement data: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF for terminal leave endorsement (separate method for PDF generation)
     */
    public function generatePdf(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if (!$request->employee) {
                return $this->errorResponse('Please select employee.');
            }

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
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
                    DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig")
                )
                ->where('a.id', $request->employee)
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
            $salary_word = $f->format(($employees[0]->salary) * 12);
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

            $pdf = PDF::loadView('terminal_leave_endorsements.terminal_leave_endorsement_print', compact('employees', 'incomes', 'salary_word', 'annual_salary', 'signatories'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            
            // Return PDF as base64 for API
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'terminal_leave_endorsement_' . $request->employee . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate terminal leave endorsement PDF: ' . $e->getMessage());
        }
    }

    /**
     * Show specific employee terminal leave endorsement data
     */
    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            
            $data = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->select(
                    'a.id',
                    'a.photo',
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
                    DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig")
                )
                ->where('a.id', $id)
                ->where(['a.is_employee' => true, 'a.active' => true])
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Employee not found');
            }

            return $this->successResponse($data, 'Employee terminal leave endorsement data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee data: ' . $e->getMessage());
        }
    }
}
