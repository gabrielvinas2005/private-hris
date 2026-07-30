<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeePayslipReportController extends Controller
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
            $app_key = env("APP_KEY", "");

            $intervals = DB::table('payroll_intervals')->where('active', true)->get();
            $departments = DB::table('departments')->where('active', true)->get();

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
                ->paginate(10000);

            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name"),
                    'a.release_date'
                )
                ->orderBy('a.release_date', 'desc')
                ->get();

            return $this->successResponse([
                'employees' => $data,
                'pay_periods' => $pay_periods,
                'intervals' => $intervals,
                'departments' => $departments
            ], 'Payslip report data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payslip report data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'payroll_interval_id' => 'required|integer|exists:payroll_intervals,id',
                'payroll_period_id' => 'required|integer|exists:payroll_periods,id',
                'employee' => 'required|integer|exists:employees,id',
                'certified_correct' => 'nullable|string|max:100',
                'position' => 'nullable|string|max:100'
            ], [
                'payroll_interval_id.required' => 'Payroll Interval is required.',
                'payroll_interval_id.exists' => 'Selected payroll interval does not exist.',
                'payroll_period_id.required' => 'Payroll Period is required.',
                'payroll_period_id.exists' => 'Selected payroll period does not exist.',
                'employee.required' => 'Employee is required.',
                'employee.exists' => 'Selected employee does not exist.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $logo1 = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
            $logo2 = base64_encode(file_get_contents(public_path('/dist/img/reports/Pilipinas_logo.png')));

            $companies = DB::table('companies')->get();

            $payroll_id = $request->payroll_period_id;
            $id = $request->employee;

            $payrolls = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('departments as c', 'c.id', '=', 'b.department_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoin('employment_types as e', 'e.id', '=', 'b.employment_type_id')
                ->join('payroll_periods as h', 'a.payroll_period_id', '=', 'h.id')
                ->join('payroll_intervals as i', 'h.payroll_interval_id', '=', 'i.id')
                ->join('payroll_cutoffs as j', 'h.payroll_cutoff_id', '=', 'j.id')
                ->select(
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.last_name,', ',b.first_name,' ',substring(b.middle_name,1,1),'.')
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+', '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM(SUBSTRING([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'.'
                                END as name"),
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    'e.name as employment_type',
                    'a.salary',
                    'a.ot_amount as ot_pay',
                    'a.nd_amount as nd_pay',
                    'a.holiday_amount as holiday_pay',
                    'a.total_income',
                    'a.gross_amount',
                    'a.late_amount',
                    'a.ut_amount',
                    'a.absent_amount',
                    DB::raw("isnull(a.late_amount,0) + isnull(a.ut_amount,0) + isnull(a.absent_amount,0) as tardiness_amount"),
                    'a.gsis',
                    'a.sss',
                    'a.pagibig',
                    'a.philhealth',
                    'a.tax',
                    'a.total_deduction',
                    'a.lwop_amount',
                    'a.net_pay',
                    DB::raw("CONCAT(DATENAME(month, h.release_date),' ', DATENAME(year, h.release_date)) as payroll_period"),
                )
                ->where(['a.payroll_period_id' => $payroll_id, 'a.employee_id' => $id])
                ->orderBy('b.first_name', 'asc')
                ->get();

            if ($payrolls->isEmpty()) {
                return $this->notFoundResponse('No payslip data found for the selected criteria');
            }

            $deductions = DB::table('payroll_deductions as a')
                ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name as deduction',
                    DB::raw("isnull(a.amount,0) as amount")
                )
                ->where([
                    'a.payroll_period_id' => $payroll_id,
                    'a.employee_id' => $id
                ])
                ->where('a.amount', '>', 0)
                ->get();

            $incomes = DB::table("payroll_incomes as a")
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.amount',
                    'b.name as income'
                )
                ->where([
                    'a.payroll_period_id' => $payroll_id,
                    'a.employee_id' => $id
                ])
                ->where('a.amount', '>', 0)
                ->get();

            $signatories = [
                'name' => $request->input('certified_correct'),
                'position' => $request->input('position'),
            ];

            $pdf = PDF::loadView('payslips.payslip_print', compact(
                'payrolls',
                'deductions',
                'incomes',
                'logo1',
                'logo2',
                'signatories',
                'companies'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "payslip_{$id}_{$payroll_id}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate employee payslip PDF: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $employee = DB::table('employees as a')
                ->leftJoin('branches as b', 'b.id', '=', 'a.branch_id')
                ->leftJoin('departments as c', 'c.id', '=', 'a.department_id')
                ->leftJoin('positions as d', 'd.id', '=', 'a.position_id')
                ->leftJoin('employment_types as e', 'e.id', '=', 'a.employment_type_id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    'a.email',
                    'a.date_hired',
                    'a.salary',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as branch',
                    'c.name as department',
                    'd.name as position',
                    'e.name as employment_type'
                )
                ->where('a.id', $id)
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found');
            }

            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name"),
                    'a.release_date'
                )
                ->orderBy('a.release_date', 'desc')
                ->get();

            return $this->successResponse([
                'employee' => $employee,
                'pay_periods' => $pay_periods,
                'summary' => [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'employee_no' => $employee->employee_no,
                    'department' => $employee->department,
                    'position' => $employee->position,
                    'employment_type' => $employee->employment_type,
                    'salary' => $employee->salary,
                    'date_hired' => $employee->date_hired,
                    'pay_periods_count' => $pay_periods->count()
                ]
            ], 'Employee payslip data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee payslip data: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $app_key = env("APP_KEY", "");

            $intervals = DB::table('payroll_intervals')->where('active', true)->get();
            $departments = DB::table('departments')->where('active', true)->get();

            $employees = DB::table('employees as a')
                ->leftJoin('branches as b', 'b.id', '=', 'a.branch_id')
                ->leftJoin('departments as c', 'c.id', '=', 'a.department_id')
                ->leftJoin('positions as d', 'd.id', '=', 'a.position_id')
                ->leftJoin('employment_types as e', 'e.id', '=', 'a.employment_type_id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    'a.salary',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as branch',
                    'c.name as department',
                    'd.name as position',
                    'e.name as employment_type'
                )
                ->where(['a.is_employee' => true, 'a.active' => true])
                ->orderBy('a.first_name', 'asc')
                ->get();

            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name"),
                    'a.release_date'
                )
                ->orderBy('a.release_date', 'desc')
                ->get();

            return $this->successResponse([
                'employees' => $employees,
                'pay_periods' => $pay_periods,
                'intervals' => $intervals,
                'departments' => $departments,
                'fields' => [
                    'payroll_interval_id' => ['type' => 'select', 'required' => true, 'label' => 'Payroll Interval'],
                    'payroll_period_id' => ['type' => 'select', 'required' => true, 'label' => 'Payroll Period'],
                    'employee' => ['type' => 'select', 'required' => true, 'label' => 'Employee'],
                    'certified_correct' => ['type' => 'text', 'required' => false, 'label' => 'Certified Correct By'],
                    'position' => ['type' => 'text', 'required' => false, 'label' => 'Position']
                ]
            ], 'Create payslip report form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }
}
