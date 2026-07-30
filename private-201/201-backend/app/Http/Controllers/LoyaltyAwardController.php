<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use DateTime;
use Illuminate\Validation\Rule;
use PDF;

class LoyaltyAwardController extends Controller
{
    use ApiResponse;
    public function index()
    {
        try {
            $data = DB::table('loyalty_award_header as a')
            ->leftJoin('branches as b', 'b.id', '=', 'a.branch_id')
            ->join('months as c', 'c.id', '=', 'a.month_id')
            ->select(
                'a.id',
                'b.name as branch',
                'c.name as month',
                'a.year',
                'a.posted'
            )
            ->get();

        return $this->successResponse($data, 'Loyalty awards retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve loyalty awards: ' . $e->getMessage());
        }
    }

    public function add(Request $request, $id)
    {
        try {
            $app_key = env("APP_KEY", "");

        $branch = DB::table('branches')->get();
        $months = DB::table('months')->get();

        $loyalty_award_setup = DB::table('loyalty_award_setup')
            ->get();

        $max_year = DB::table('loyalty_award_setup')->max('years_of_service');
        $min_year = DB::table('loyalty_award_setup')->min('years_of_service');
        $currDATE = Carbon::now();

        if ($id == 0) {
            $dummy_data = array(
                'id' => 0,
                'branch_id' => 0,
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
        } else {
            $data = DB::table('loyalty_award_header as a')
                ->select(
                    'id',
                    'branch_id',
                    'month_id',
                    'year',
                    'posted',
                    'active'
                )
                ->where('id', $id)->get();

            $month_after = $data[0]->month_id + 1;
            $Dateraw = $data[0]->year . '-' . $month_after  . '-1';
            $date = Carbon::createFromFormat('Y-m-d', $Dateraw);
            $newDate = $date->subDay();
            $Date = $newDate->format('Y-m-d');

            $employees = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->join('loyalty_award_details as ld', 'employees.id', '=', 'ld.employee_id')
                ->join('loyalty_award_header as lh', 'ld.loyalty_award_header_id', '=', 'lh.id')
                ->join('loyalty_award_setup as lw', 'ld.loyalty_award_setup_id', '=', 'lw.id')
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
                            END as name"),
                    DB::raw("DATEDIFF(YEAR, date_hired, '$Date') as years"),
                    DB::raw("ld.loyalty_award_setup_id"),
                    DB::raw("lw.cash_award")
                )
                ->where('lh.id', $id)
                ->orderBy('employees.first_name', 'asc')
                ->get();
        }

        return $this->successResponse([
            'data' => $data,
            'branch' => $branch,
            'months' => $months,
            'employees' => $employees,
            'loyalty_award_setup' => $loyalty_award_setup,
            'max_year' => $max_year,
            'min_year' => $min_year
        ], 'Loyalty award form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load loyalty award form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $datas = $request->all();

        if ($id == 0) {
            $request->validate(
                [
                    'branch_id' => 'required',
                    'year' => 'required',
                    'month_id' => [
                        'required',
                        Rule::unique('loyalty_award_header')->where(function ($query) use ($request) {
                            return $query
                                ->where('branch_id', $request->branch_id)
                                ->where('year', $request->year)
                                ->where('month_id', $request->month_id);
                        }),
                    ],
                ],
                [
                    'branch_id.required'  => "Branch is required.",
                    'year.required'       => "Year is required.",
                    'month_id.required'   => "Month is required.",
                    'month_id.unique' => "Loyalty record already exist for this branch,month and year.",
                ]
            );
        }

        if (!$request->has('employee_id')) {
            return $this->errorResponse('No employee to process.');
        }

        $data = array(
            'branch_id' => $request->branch_id,
            'year' => $request->year,
            'month_id' => $request->month_id,
            'active' => true
        );

        if ($id == 0) {
            DB::table('loyalty_award_header')->insert($data);

            $record_id = DB::table('loyalty_award_header')->max('id');

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Loyalty Award',
                'activity' => 'Update',
                'description' => 'Updated Loyalty Award informations.',
            );
        } else {
            DB::table('loyalty_award_header')->updateOrInsert(['id' => $id], $data);

            $record_id = $id;

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Loyalty Award',
                'activity' => 'Update',
                'description' => 'Updated Loyalty Award informations.',
            );
        }

        $arr_len = count($datas['employee_id']);

        DB::table('loyalty_award_details')->where('loyalty_award_header_id', $record_id)->delete();

        if (isset($datas['employee_id'])) {

            $emp_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($datas['employee_id'][$i] != NULL) {

                    $id = DB::table('loyalty_award_details')->max('id') + 1;

                    $emp_data = [
                        'loyalty_award_header_id' => $record_id,
                        'employee_id' => $datas['employee_id'][$i],
                        'loyalty_award_setup_id' => $datas['loyalty_award_setup_id'][$i],
                    ];

                    DB::table('loyalty_award_details')->updateOrInsert(['id' => $id], $emp_data);
                }
            }
        }

        Audit::create($data_audit);

        return $this->successResponse(['id' => $record_id], 'Loyalty awards updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update loyalty awards: ' . $e->getMessage());
        }
    }

    public function post($id)
    {
        $with_setup = DB::table('loyalty_award_setup')->get();

        if ($with_setup->isEmpty()) {
            return $this->errorResponse('There is no Loyalty Setup!', 400);
        }

        $with_employees = DB::table('loyalty_award_details')->where('loyalty_award_header_id', $id)->get();

        if ($with_employees->isEmpty()) {
            return $this->errorResponse('Please add atleast one Employee!', 400);
        }

        DB::table('loyalty_award_header')->where('id', $id)->update(['posted' => true]);

        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Payroll Module',
            'menu'    => 'Loyalty Award',
            'activity' => 'Update',
            'description' => 'Updated Loyalty Award informations.',
        );

        Audit::create($data_audit);

        return $this->successResponse(['id' => $id], 'You have successfully posted loyalty awards!');
        // return redirect()->to('loyalty_award_add/' . $record_id)->with('success', 'You have successfully posted loyalty awards!');
    }

    public function unpost(Request $request, $id)
    {
        $with_setup = DB::table('loyalty_award_setup')->get();

        if ($with_setup->isEmpty()) {
            return $this->errorResponse('There is no Loyalty Setup!', 400);
        }

        $with_employees = DB::table('loyalty_award_details')->where('loyalty_award_header_id', $id)->get();

        if ($with_employees->isEmpty()) {
            return $this->errorResponse('Please add atleast one Employee!', 400);
        }

        DB::table('loyalty_award_header')->where('id', $id)->update(['posted' => false]);

        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Payroll Module',
            'menu'    => 'Loyalty Award',
            'activity' => 'Update',
            'description' => 'Updated Loyalty Award informations.',
        );

        Audit::create($data_audit);

        return $this->successResponse(['id' => $id], 'You have successfully unposted loyalty awards!');
        // return redirect()->to('loyalty_award_add/' . $record_id)->with('success', 'You have successfully unposted loyalty awards!');
    }


    public function loyalty_award_report()
    {
        $Branch = DB::table('branches')->get();
        $PayrollPeriodType = DB::table('payroll_periods as a')
            ->leftjoin('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
            // ->leftjoin('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
            ->select(
                'a.id',
                DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name")
            )
            ->orderBy('a.release_date', 'desc')
            ->get();

        return $this->successResponse([
            'branches' => $Branch,
            'payroll_period_types' => $PayrollPeriodType
        ], 'Loyalty award report data retrieved successfully');
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

        $data = request()->all();
        $companies = DB::table('companies')->get();

        if ($request->branch_id == null) {
            return $this->errorResponse('Please select branch.', 400);
        }

        if (isset($data['branch_id'])) {
            $signatory_data = [
                'branch_id' => $data['branch_id'],
                'report_name' => 'Loyalty Award',
                'signatory_1' => $data['signatory_1'],
                'signatory_position_1' => $data['signatory_position_1'],
                'description_1' => $data['description_1'],
                'signatory_2' => $data['signatory_2'],
                'signatory_position_2' => $data['signatory_position_2'],
                'description_2' => $data['description_2'],
                'signatory_3' => $data['signatory_3'],
                'signatory_position_3' => $data['signatory_position_3'],
                'description_3' => $data['description_3'],
                'signatory_4' => $data['signatory_4'],
                'signatory_position_4' => $data['signatory_position_4'],
                'description_4' => $data['description_4'],
                'signatory_5' => $data['signatory_5'],
                'signatory_position_5' => $data['signatory_position_5'],
                'description_5' => $data['description_5'],
            ];

            DB::table('loyalty_award_signatories')->updateOrInsert(['branch_id' => $data['branch_id']], $signatory_data);
        }

        $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

        $loyalty_award = DB::table('loyalty_award_header as a')
            ->leftjoin('loyalty_award_details as b', 'a.id', '=', 'b.loyalty_award_header_id')
            ->where('branch_id', $request->branch_id)
            ->get();

            $PayrollPeriodType = DB::table('loyalty_award_header as a')
            ->join('months as b', 'a.month_id', '=', 'b.id')
            ->select(
                'a.id',
                DB::raw("CONCAT('Monthly',' (',b.name,' ',a.year,') ') as name")
            )
            ->where([
                'a.posted' => true,
                'a.id' => $request->payroll_period_id,
                ])

            ->orderBy('name', 'desc')
            ->get();

        $employees = DB::table('loyalty_award_header as a')
            ->join('loyalty_award_details as b', 'a.id', '=', 'b.loyalty_award_header_id')
            ->leftJoin('employees as c', 'c.id', '=', 'b.employee_id')
            ->leftJoin('positions as d', 'c.position_id', '=', 'd.id')
            ->leftJoin('name_suffixes as g', 'c.name_suffix_id', '=', 'g.id')
            ->leftJoin('loyalty_award_setup as h', 'h.id', '=', 'b.loyalty_award_setup_id')
            ->select(
                'b.id',
                'c.employee_no',
                DB::raw("CONCAT(
                    CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN c.last_name ELSE dbo.ufn_DecryptString(c.last_name,'$app_key') END,
                    ', ',
                    CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN c.first_name ELSE dbo.ufn_DecryptString(c.first_name,'$app_key') END,
                    ' ',
                    CASE
                        WHEN ISNULL(c.is_encrypted, 0) = 0 THEN LEFT(c.middle_name, 1)
                        ELSE LEFT(dbo.ufn_DecryptString(c.middle_name, '$app_key'), 1)
                    END,
                    CASE WHEN c.middle_name IS NOT NULL AND c.middle_name <> '' THEN '. ' ELSE ' ' END,
                    CASE WHEN g.name IS NOT NULL THEN g.name ELSE '' END
                ) as full_name"),
                'd.name as position',
                'h.cash_award',
                'h.cash_token',
                DB::raw("COALESCE(h.cash_award, 0) + COALESCE(h.cash_token, 0) as amount_received"),
                'h.years_of_service as years'
            )
            ->distinct()
            ->where('a.id', $request->payroll_period_id)
            ->where(function ($query) {
                $query->whereNotNull('h.cash_award')
                      ->where('h.cash_award', '>', 0);
            })
            ->orderBy('full_name', 'asc')
            ->get();

        $signatories = DB::table('loyalty_award_signatories')
            ->where('branch_id', $request->branch_id)
            ->get();

        $month = $PayrollPeriodType[0]->name ?? '';

        $pdf = PDF::loadView('loyalty_award_report.loyalty_award_report_print', compact('companies','employees', 'loyalty_award', 'signatories', 'image', 'month'))->setOptions(['defaultFont' => 'sans-serif']);
        $pdf->setPaper('tabloid', 'landscape');
        
        $pdf_content = $pdf->output();
        $filename = 'loyalty_award_report_' . $request->branch_id . '_' . date('Y-m-d') . '.pdf';

        return response($pdf_content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate loyalty award report: ' . $e->getMessage());
        }
    }

    public function loyalty_award_employee($month_id, $branch_id, $year)
    {
        $app_key = env("APP_KEY", "");

        $month = $month_id + 1;
        $DateRaw = $year . '-' . $month  . '-1';
        $date = Carbon::createFromFormat('Y-m-d', $DateRaw);
        $newDate = $date->subDay();
        $DateSelect = $newDate->format('Y-m-d');

        $max_year = DB::table('loyalty_award_setup')->max('years_of_service');
        $min_year = DB::table('loyalty_award_setup')->min('years_of_service');

        $employees = DB::table('employees')
            ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
            ->leftJoin('loyalty_award_setup', 'loyalty_award_setup.years_of_service', '=', DB::raw("(DATEDIFF(YEAR,employees.date_hired,'$DateSelect'))"))
            ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
            ->select(
                'employees.id',
                'employees.id as employee_id',
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
                            END as name"),
                DB::raw("DATEDIFF(YEAR,employees.date_hired, '$DateSelect') as years"),
                'loyalty_award_setup.cash_award',
                'loyalty_award_setup.id as loyalty_award_setup_id',
                'employees.date_hired',
                DB::raw("'$DateSelect' as dateyears"),
            )
            ->where([
                'employees.is_employee' => true,
                'employees.active' => true,
                'employees.branch_id' => $branch_id,
            ])
            ->whereMonth('date_hired', $month_id)
            ->whereNotIn('employees.id', function ($query) use ($month_id, $year, $branch_id) {
                $query->select('b.employee_id')->from('loyalty_award_header as a')
                    ->join('loyalty_award_details as b', 'b.loyalty_award_header_id', '=', 'a.id')
                    ->where([
                        'month_id' => $month_id,
                        'year' => $year,
                        'branch_id' => $branch_id,
                    ])
                    ->get();
            })
            ->orderBy('employees.first_name', 'asc')
            ->get();

        return $this->successResponse([
            'employees' => $employees,
            'max_year' => $max_year,
            'min_year' => $min_year
        ], 'Loyalty award table data retrieved successfully');
    }
}