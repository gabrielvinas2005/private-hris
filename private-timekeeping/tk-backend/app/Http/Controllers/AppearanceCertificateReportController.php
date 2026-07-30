<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \NumberFormatter;
use App\Traits\GeneratesPdf;

class AppearanceCertificateReportController extends Controller
{
    use GeneratesPdf;

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

        return $this->successResponse($data, 'Appearance certificates retrieved successfully');
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.', 400);
            }

            // $image = base64_encode(file_get_contents(public_path('/dist/img/reports/header_img.jpg')));
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

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
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(e.name,' ',a.last_name)
                            ELSE
                                RTRIM(e.name)+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name_sig")
                )
                ->where('a.id', $request->employee)
                ->get();

            if ($employees->isEmpty()) {
                return $this->errorResponse('Employee not found.', 404);
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
                ->where(
                    'a.employee_id',
                    $request->employee
                )
                ->get();

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position
            );

            $pdf = PDF::loadView('appearance_certificates.appearance_certificate_print', compact('employees', 'incomes', 'salary_word', 'annual_salary', 'signatories', 'image'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'appearance_certificate_' . $request->employee . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate appearance certificate PDF: ' . $e->getMessage());
        }
    }
}
