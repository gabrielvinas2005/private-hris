<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use PDF;
use App\Traits\ApiResponse;
use App\Support\PayrollBenefitsEmployeeScope;
use App\Support\ReportDivisionFilter;

class UniformClothingController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $data = DB::table('uniform_clothing_header as a')
                ->leftJoin('branches as b', 'b.id', '=', 'a.branch_id')
                ->leftJoin('months as c', 'c.id', '=', 'a.month_id')
                ->select(
                    'a.id',
                    'b.name as branch',
                    'c.name as month',
                    'c.id as month_number',
                    'a.year',
                    'a.posted'
                )
                ->get();

            return $this->successResponse($data, 'Uniform clothing data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve uniform clothing data: ' . $e->getMessage());
        }
    }

    public function add(Request $request, $id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $branch = DB::table('branches')->get();
            $divisions = ReportDivisionFilter::activeDivisions();
            $months = DB::table('months')->get();
            $uniform_clothing_setup = DB::table('uniform_clothing_setup')
                ->get();
            // dd($array);
            $currDATE = Carbon::now();
            $divisionId = ReportDivisionFilter::resolveId($request);
            if ($id == 0) {
                $dummy_data = array(
                    'id' => 0,
                    'branch_id' => 0,
                    'department_id' => null,
                    'month_id' => 0,
                    'year' => null,
                    'posted' => null,
                    'active' => null
                );

                $data = (object)$dummy_data;
                $data = collect([$data]);

                $employees = DB::table('employees')
                    ->where('id', 0)
                    ->get();

                $t_employees = DB::table('employees')
                    ->where('id', 0)
                    ->get();
            } else {
                $data = DB::table('uniform_clothing_header as a')
                    ->select(
                        'id',
                        'branch_id',
                        'department_id',
                        'month_id',
                        'year',
                        'posted',
                        'active'
                    )
                    ->where('id', $id)->get();

                $t_employees = DB::table('employees')
                    ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                    ->leftJoin('divisions', 'divisions.id', '=', 'employees.division_id')
                    ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                    ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                    ->join('uniform_clothing_details', 'uniform_clothing_details.employee_id', '=', 'employees.id')
                    ->leftJoin('uniform_clothing_setup', 'uniform_clothing_setup.id', '=', 'uniform_clothing_details.uniform_clothing_setup_id')
                    ->select(
                        'employees.photo',
                        'employees.id',
                        'employees.employee_no',
                        'employees.email',
                        'employment_types.name as employment_type',
                        'positions.name as position',
                        'divisions.name as division',
                        'divisions.name as department',
                        'branches.name as branch',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                END as name"),
                        'uniform_clothing_details.id as uniform_clothing_details_id',
                        'uniform_clothing_setup.cloth_rate',
                        'uniform_clothing_setup.uniform_rate',
                    )
                    ->where([
                        'employees.is_employee' => true,
                        'employees.active' => true,
                        'uniform_clothing_details.uniform_clothing_header_id' => $id,
                    ])
                    ->orderBy('employees.first_name', 'asc')
                    ->get();

                $employeesQuery = DB::table('employees')
                    ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                    ->leftJoin('divisions', 'divisions.id', '=', 'employees.division_id')
                    ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                    ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                    ->select(
                        'employees.photo',
                        'employees.id',
                        'employees.employee_no',
                        'employees.email',
                        'employment_types.name as employment_type',
                        'positions.name as position',
                        'divisions.name as division',
                        'divisions.name as department',
                        'branches.name as branch',
                        DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                                END as name")
                    )
                    ->where([
                        'employees.is_employee' => true,
                        'employees.active' => true,
                    ])
                    ->when($divisionId, function ($query) use ($divisionId) {
                        return $query->where('employees.division_id', $divisionId);
                    })
                    ->whereNotIn('employees.id', DB::table('uniform_clothing_details')
                        ->select('employee_id')
                        ->where('uniform_clothing_header_id', $id)
                        ->pluck('employee_id'));
                PayrollBenefitsEmployeeScope::apply($employeesQuery, 'employees');
                $employees = $employeesQuery
                    ->orderBy('employees.first_name', 'asc')
                    ->get();
            }

            return $this->successResponse([
                'data' => $data,
                'branch' => $branch,
                'divisions' => $divisions,
                'departments' => $divisions,
                'months' => $months,
                'employees' => $employees,
                't_employees' => $t_employees
            ], 'Uniform clothing data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve uniform clothing data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $datas = $request->all();

            $divisionId = ReportDivisionFilter::resolveId($request);

            if ($id == 0) {
                $request->validate(
                    [
                        'division_id' => 'required|exists:divisions,id',
                        'year' => 'required',
                        'month_id' => [

                            'required',

                            Rule::unique('uniform_clothing_header')->where(function ($query) use ($request, $divisionId) {

                                return $query
                                    ->where('department_id', $divisionId)
                                    ->where('year', $request->year)
                                    ->where('month_id', $request->month_id);
                            }),
                        ],
                    ],
                    [
                        'month_id.unique' => __('Record Exist', [

                            'department_id' => $request->department_id,
                            'year'        => $request->year,
                            'month_id'        => $request->month_id
                        ]),
                    ]
                );
            }

            $data = array(
                'branch_id' => 0,
                'department_id' => $divisionId,
                'year' => $request->year,
                'month_id' => $request->month_id,
                'active' => true
            );


            if ($id == 0) {
                $record_id = DB::table('uniform_clothing_header')->insertGetId($data);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Uniform & Clothing Allowance',
                    'activity' => 'Update',
                    'description' => 'Updated Uniform & Clothing Allowance informations.',
                );
            } else {
                DB::table('uniform_clothing_header')->updateOrInsert(['id' => $id], $data);

                $record_id = $id;

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Uniform & Clothing Allowance',
                    'activity' => 'Update',
                    'description' => 'Updated Uniform & Clothing Allowance informations.',
                );
            }

            Audit::create($data_audit);

            return $this->successResponse(['record_id' => $record_id], 'You have successfully updated uniform & clothing allowance!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update uniform & clothing allowance: ' . $e->getMessage());
        }
    }

    public function post(Request $request, $id)
    {
        try {
            $data = array(
                'posted' => true
            );

            $with_setup = DB::table('uniform_clothing_setup')->get();

            if ($with_setup->isEmpty()) {
                return $this->errorResponse('There is no Uniform and Clothing Setup!', 400);
            }

            $with_employees = DB::table('uniform_clothing_details')->where('uniform_clothing_header_id', $id)->get();

            if ($with_employees->isEmpty()) {
                return $this->errorResponse('Please add atleast one Employee!', 400);
            }

            if ($id != 0) {
                DB::table('uniform_clothing_header')->updateOrInsert(['id' => $id], $data);

                $record_id = $id;

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Uniform & Clothing Allowance',
                    'activity' => 'Update',
                    'description' => 'Updated Uniform & Clothing Allowance informations.',
                );
            }

            Audit::create($data_audit);

            return $this->successResponse(['record_id' => $record_id], 'You have successfully posted uniform & clothing allowance!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to post uniform & clothing allowance: ' . $e->getMessage());
        }
    }

    public function unpost(Request $request, $id)
    {
        try {
            $data = array(
                'posted' => false
            );

            if ($id != 0) {
                DB::table('uniform_clothing_header')->updateOrInsert(['id' => $id], $data);

                $record_id = $id;

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Uniform & Clothing Allowance',
                    'activity' => 'Update',
                    'description' => 'Updated Uniform & Clothing Allowance informations.',
                );
            }

            Audit::create($data_audit);

            return $this->successResponse(['record_id' => $record_id], 'You have successfully unposted uniform & clothing allowance!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to unpost uniform & clothing allowance: ' . $e->getMessage());
        }
    }


    public function addEmployee(Request $request, $id)
    {
        try {
            $datas = $request->all();

            $uniform_clothing_setup = DB::table('uniform_clothing_setup')
                ->where('year', $datas['m_year'])
                ->get();

            $uniform_clothing_setup_id = 0;
            if (isset($uniform_clothing_setup[0]->id)) {
                $uniform_clothing_setup_id = $uniform_clothing_setup[0]->id;
            }


            $arr_len = count($datas['employee_id']);
            // dd($datas);
            $emp_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($datas['employee_id'][$i] != NULL) {
                    $emp_data = [
                        'uniform_clothing_header_id' => $datas['m_header_id'],
                        'employee_id' => $datas['employee_id'][$i],
                        'uniform_clothing_setup_id' => $uniform_clothing_setup_id,
                    ];

                    DB::table('uniform_clothing_details')->updateOrInsert($emp_data);
                }
            }

            return $this->successResponse(['record_id' => $datas['m_header_id']], 'You have successfully updated uniform & clothing allowance!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add employee to uniform & clothing allowance: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('uniform_clothing_details')->where('id', $id)->get();

            return $this->successResponse($data, 'Uniform clothing employee data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve uniform clothing employee data: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('uniform_clothing_details')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Uniform & Clothing Allowance',
                'activity' => 'Delete',
                'description' => 'Deleted Uniform & Clothing Allowance informations.',
            );
            Audit::create($data_audit);

            return $this->successResponse(null, 'Successfully deleted!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete uniform & clothing allowance: ' . $e->getMessage());
        }
    }

    public function destroyHeader($id)
    {
        try {
            $header = DB::table('uniform_clothing_header')->where('id', $id)->first();

            if (!$header) {
                return $this->errorResponse('Uniform & clothing allowance not found.', 404);
            }

            if ($header->posted) {
                return $this->errorResponse('Cannot delete a posted uniform & clothing allowance.', 400);
            }

            DB::beginTransaction();

            DB::table('uniform_clothing_details')
                ->where('uniform_clothing_header_id', $id)
                ->delete();

            DB::table('uniform_clothing_header')
                ->where('id', $id)
                ->delete();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Uniform & Clothing Allowance',
                'activity' => 'Delete',
                'description' => 'Deleted Uniform & Clothing Allowance.',
            );
            Audit::create($data_audit);

            DB::commit();

            return $this->successResponse(null, 'Uniform & clothing allowance deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete uniform & clothing allowance: ' . $e->getMessage());
        }
    }

    public function uniform_clothing_allowance_report()
    {
        try {
            $app_key = env("APP_KEY", "");

            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name"),
                    'a.release_date',
                    'c.name as cutoff_name'
                )
                ->orderBy('a.release_date', 'desc')
                ->get();

            $data = DB::table('branches')
                ->select('branches.name as branch')
                ->distinct()
                ->get();

            return $this->successResponse([
                'pay_periods' => $pay_periods,
                'data' => $data
            ], 'Uniform clothing allowance report data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve uniform clothing allowance report data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");
            // Frontend historically sends payroll_interval_id, but this is actually a payroll_period_id.
            // Accept both to avoid breaking older clients.
            $payroll_id = $request->payroll_period_id ?? $request->payroll_interval_id;

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $companies = DB::table('companies')->get();

            // Resolve selected branch id (UI sends branch name string)
            $branch_id = null;
            if ($request->branch_id) {
                if (is_numeric($request->branch_id)) {
                    $branch_id = (int)$request->branch_id;
                } else {
                    $branchName = trim((string)$request->branch_id);
                    $branch_id = DB::table('branches')
                        ->whereRaw('LTRIM(RTRIM(name)) = ?', [$branchName])
                        ->value('id');
                }
            }

            // Map the selected payroll period to month/year so we can target the correct allowance header
            $period = DB::table('payroll_periods')->where('id', $payroll_id)->select('release_date')->first();
            $period_month = $period ? (int)date('n', strtotime($period->release_date)) : null;
            $period_year = $period ? (int)date('Y', strtotime($period->release_date)) : null;

            // Find the allowance header for the selected month/year (do not require posted so preview works).
            $header_id = DB::table('uniform_clothing_header')
                ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
                ->when($period_month, fn($q) => $q->where('month_id', $period_month))
                ->when($period_year, fn($q) => $q->where('year', $period_year))
                ->orderBy('id', 'desc')
                ->value('id');

            // Fetch employees included in the selected allowance header
            $data = DB::table('uniform_clothing_details as u')
                ->join('uniform_clothing_header as uh', 'uh.id', '=', 'u.uniform_clothing_header_id')
                ->join('employees as b', 'b.id', '=', 'u.employee_id')
                ->join('branches as a', 'a.id', '=', 'uh.branch_id')
                ->leftJoin('divisions as dpt', 'dpt.id', '=', 'uh.department_id')
                ->leftJoin('departments as dept_legacy', 'dept_legacy.id', '=', 'uh.department_id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->leftJoin('uniform_clothing_setup as us', 'us.id', '=', 'u.uniform_clothing_setup_id')
                ->leftJoin('time_data as d', function ($join) use ($payroll_id) {
                    $join->on('d.employee_id', '=', 'b.id')
                        ->where('d.payroll_period_id', '=', $payroll_id);
                })
                ->leftJoin('employee_offboardings as e', 'e.employee_id', '=', 'b.id')
                ->select(
                    'b.employee_no',
                    DB::raw("CASE
                        WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                            CONCAT(b.last_name, ', ', b.first_name, ' ', LEFT(b.middle_name, 1))
                        ELSE
                            CONCAT(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')), ', ',
                                   RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')), ' ',
                                   LEFT(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key')), 1))
                    END as name"),
                    'c.name as position',
                    DB::raw('COALESCE(dpt.name, dept_legacy.name) as department'),
                    DB::raw('COALESCE(dpt.name, dept_legacy.name) as division'),
                    DB::raw("MAX(us.cloth_rate) as cloth_rate"),
                    DB::raw('COUNT(d.date) as actual_days')
                )
                ->when($branch_id, fn($q) => $q->where('uh.branch_id', $branch_id))
                ->when($period_month, fn($q) => $q->where('uh.month_id', $period_month))
                ->when($period_year, fn($q) => $q->where('uh.year', $period_year))
                ->when($header_id, fn($q) => $q->where('uh.id', $header_id))
                ->whereNotNull('us.cloth_rate')
                ->whereNull('e.id')
                ->groupBy(
                    'b.employee_no',
                    'b.is_encrypted',
                    'b.first_name',
                    'b.last_name',
                    'b.middle_name',
                    'c.name',
                    'a.name',
                    'dpt.name',
                    'dept_legacy.name'
                )
                ->orderBy('b.last_name', 'asc')
                ->get();

            $selected_pay_period = DB::table('payroll_periods as a')
                ->where('a.id', $payroll_id)
                ->select(DB::raw("YEAR(a.release_date) as year"))
                ->first();

            $year = $selected_pay_period ? $selected_pay_period->year : date('Y');
            
            // Validate that we have required data
            if ($companies->isEmpty()) {
                throw new \Exception('No company data found');
            }
            
            if ($data->isEmpty()) {
                throw new \Exception('No employee data found for the selected branch and payroll period');
            }
            // Collect signatory data from the request
            $signatories = [
                [
                    'signatory_1' => $request->signatory_1,
                    'signatory_position_1' => $request->signatory_position_1,

                    'signatory_2' => $request->signatory_2,
                    'signatory_position_2' => $request->signatory_position_2,

                    'signatory_3' => $request->signatory_3,
                    'signatory_position_3' => $request->signatory_position_3,

                    'signatory_4' => $request->signatory_4,
                    'signatory_position_4' => $request->signatory_position_4,

                    'signatory_5' => $request->signatory_5,
                    'signatory_position_5' => $request->signatory_position_5,
                ]
            ];

            $pdf = PDF::loadView('uniform_clothing_allowance_report.uniform_clothing_allowance_report_print', compact(
                'data',
                'image',
                'selected_pay_period',
                'signatories',
                'year',
                'companies'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('Legal', 'landscape');
            $pdfContent = $pdf->output();
            
            $filename = 'uniform_clothing_allowance_report_' . $request->branch_id . '_' . $payroll_id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to print uniform clothing allowance report: ' . $e->getMessage());
        }
    }
}