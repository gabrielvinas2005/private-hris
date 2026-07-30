<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \NumberFormatter;

class EmployeeCertificateCompensationReportController extends Controller
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

            return $this->successResponse($data, 'Employee certificate compensation records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee certificate compensation records: ' . $e->getMessage());
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
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    DB::raw("isnull(a.salary,0) * 12 as annual_salary"),
                    'a.date_hired',
                    'a.gender_id',
                    DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig"),
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

            $document_no = DB::table('document_numbers')->where('id', 4)->get();

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

            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
            $salary_word = $f->format(($employees[0]->salary) * 12);
            $annual_salary = ($employees[0]->annual_salary);
            $midyear_period = DB::table('midyear_bonus')->max('years');
            $yearend_period = DB::table('yearend_bonus')->max('years');

            $midyear_bonus = DB::table('midyear_bonus as a')
                ->select(
                    'a.employee_id',
                    DB::raw("CAST('14th Month Pay' as varchar(255)) as item"),
                    'a.bonus_amount as amount'
                )
                ->where([
                    'a.employee_id' => $request->employee,
                    'a.years' => $midyear_period
                ])->get();

            if (count($midyear_bonus) > 0) {
                $midyear_bonus1 = DB::table('midyear_bonus as a')
                    ->select(
                        'a.employee_id',
                        DB::raw("CAST('14th Month Pay' as varchar(255)) as item"),
                        'a.bonus_amount as amount'
                    )
                    ->where([
                        'a.employee_id' => $request->employee,
                        'a.years' => $midyear_period
                    ])->where('a.bonus_amount', '>', 0);
            } else {
                $midyear_bonus1 = DB::raw("
                    SELECT
                    0 AS employee_id,
                    '' AS item,
                    0 AS amount
                ");
            }

            $yearend_bonus = DB::table('yearend_bonus as a')
                ->select(
                    'a.employee_id',
                    DB::raw("CAST('13th Month Pay' as varchar(255)) as item"),
                    'a.bonus_amount as amount'
                )
                ->where([
                    'a.employee_id' => $request->employee,
                    'a.years' => $yearend_period
                ])->get();

            $cash_gift = DB::table('yearend_bonus as a')
                ->select(
                    'a.employee_id',
                    DB::raw("CAST('Cash Gift' as varchar(255)) as item"),
                    'a.cash_gift_amount as amount'
                )
                ->where([
                    'a.employee_id' => $request->employee,
                    'a.years' => $yearend_period
                ])->get();

            if (count($yearend_bonus) > 0) {
                $yearend_bonus1 = DB::table('yearend_bonus as a')
                    ->select(
                        'a.employee_id',
                        DB::raw("CAST('13th Month Pay' as varchar(255)) as item"),
                        'a.bonus_amount as amount'
                    )
                    ->where([
                        'a.employee_id' => $request->employee,
                        'a.years' => $yearend_period
                    ])->where('a.bonus_amount', '>', 0);
            } else {
                $yearend_bonus1 = DB::raw("
                    SELECT
                    0 AS employee_id,
                    '' AS item,
                    0 AS amount
                ");
            }

            if (count($cash_gift) > 0) {
                $cash_gift1 = DB::table('yearend_bonus as a')
                    ->select(
                        'a.employee_id',
                        DB::raw("CAST('Cash Gift' as varchar(255)) as item"),
                        'a.cash_gift_amount as amount'
                    )
                    ->where([
                        'a.employee_id' => $request->employee,
                        'a.years' => $yearend_period
                    ])->where('a.cash_gift_amount', '>', 0);
            } else {
                $cash_gift1 = DB::raw("
                    SELECT
                    0 AS employee_id,
                    '' AS item,
                    0 AS amount
                ");
            }

            $payroll_period_id = DB::table('payroll_incomes')->max('payroll_period_id');

            $compensation_details = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name as type',
                    'a.amount',
                    DB::raw("'Monthly' as period")
                )
                ->where([
                    'a.employee_id' => $request->employee,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->where('a.amount', '>', 0)
                ->get();

            $incomes1 = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name as item',
                    'a.amount'
                )
                ->where([
                    'a.employee_id' => $request->employee,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->where('a.amount', '>', 0);

            if (!$midyear_bonus && !$yearend_bonus && !$cash_gift) {
                $bonus = $midyear_bonus1
                    ->union($yearend_bonus1)
                    ->union($cash_gift1)
                    ->get();

                $bonus1 = $midyear_bonus1
                    ->union($yearend_bonus1)
                    ->union($cash_gift1)
                    ->union($incomes1);
                $total_bonus = DB::query()->fromSub($bonus1, 'a');
                $total_bonus1 = $total_bonus
                    ->select(
                        'a.employee_id',
                        DB::raw("SUM(a.amount) as total"),
                    )
                    ->groupBy(
                        'a.employee_id'
                    )
                    ->get();
            } else {
                $bonus = [];
                $total_bonus1 = [];
            }

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position
            );

            $pdf = PDF::loadView('employee_certificates.employee_certificate_compensation_print', compact(
                'employees',
                'compensation_details',
                'salary_word',
                'annual_salary',
                'signatories',
                'image',
                'bonus',
                'total_bonus1',
                'companies',
                'footer'
            ))->setOptions(['defaultFont' => 'arial']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "compensation_certificate_{$request->employee}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate employee compensation certificate PDF: ' . $e->getMessage());
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
                                RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig"),
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

            $payroll_period_id = DB::table('payroll_incomes')->max('payroll_period_id');
            $midyear_period = DB::table('midyear_bonus')->max('years');
            $yearend_period = DB::table('yearend_bonus')->max('years');

            $incomes = DB::table('payroll_incomes as a')
                ->join('incomes as b', 'a.income_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    'b.name as item',
                    'a.amount'
                )
                ->where([
                    'a.employee_id' => $id,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->where('a.amount', '>', 0)
                ->get();

            $midyear_bonus = DB::table('midyear_bonus as a')
                ->select(
                    'a.employee_id',
                    DB::raw("CAST('14th Month Pay' as varchar(255)) as item"),
                    'a.bonus_amount as amount'
                )
                ->where([
                    'a.employee_id' => $id,
                    'a.years' => $midyear_period
                ])->get();

            $yearend_bonus = DB::table('yearend_bonus as a')
                ->select(
                    'a.employee_id',
                    DB::raw("CAST('13th Month Pay' as varchar(255)) as item"),
                    'a.bonus_amount as amount'
                )
                ->where([
                    'a.employee_id' => $id,
                    'a.years' => $yearend_period
                ])->get();

            $cash_gift = DB::table('yearend_bonus as a')
                ->select(
                    'a.employee_id',
                    DB::raw("CAST('Cash Gift' as varchar(255)) as item"),
                    'a.cash_gift_amount as amount'
                )
                ->where([
                    'a.employee_id' => $id,
                    'a.years' => $yearend_period
                ])->get();

            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
            $salary_word = $f->format(($employee->salary) * 12);
            $annual_salary = ($employee->salary) * 12;

            return $this->successResponse([
                'employee' => $employee,
                'compensation_data' => [
                    'incomes' => $incomes,
                    'midyear_bonus' => $midyear_bonus,
                    'yearend_bonus' => $yearend_bonus,
                    'cash_gift' => $cash_gift
                ],
                'salary_details' => [
                    'monthly_salary' => $employee->salary,
                    'annual_salary' => $annual_salary,
                    'salary_in_words' => $salary_word
                ],
                'summary' => [
                    'total_incomes' => $incomes->sum('amount'),
                    'income_count' => $incomes->count(),
                    'total_bonus' => $midyear_bonus->sum('amount') + $yearend_bonus->sum('amount'),
                    'total_cash_gift' => $cash_gift->sum('amount'),
                    'midyear_period' => $midyear_period,
                    'yearend_period' => $yearend_period
                ]
            ], 'Employee compensation data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee compensation data: ' . $e->getMessage());
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
            ], 'Create employee compensation certificate form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }
}
