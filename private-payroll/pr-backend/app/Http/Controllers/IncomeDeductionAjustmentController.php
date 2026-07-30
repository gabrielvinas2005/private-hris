<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class IncomeDeductionAjustmentController extends Controller
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
            $PayrollPeriodType = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.payroll_interval_id',
                    'a.payroll_cutoff_id',
                    DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name"),
                    'a.release_date',
                    'c.name as cutoff_name'
                )
                ->where('a.active', true)
                ->where('a.posted', false)
                // Only show periods that can load employees (time_data_summary exists)
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('time_data_summary as tds')
                        ->whereColumn('tds.payroll_period_id', 'a.id');
                })
                ->orderBy('a.release_date', 'desc')
                ->orderBy('a.id', 'desc')
                ->get();

            $EmploymentType = DB::table('employment_types')->where('active', true)->get();

            return $this->successResponse([
                'payroll_period_types' => $PayrollPeriodType,
                'employment_types' => $EmploymentType
            ], 'Income deduction adjustment data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve income deduction adjustment data: ' . $e->getMessage());
        }
    }

    public function storeIncome(Request $request)
    {
        try {
            if ($request->boolean('monthly_split_pera') && $request->filled('secondary_payroll_period_id')) {
                return $this->storeIncomeMonthlySplitPera($request);
            }

            $validator = validator($request->all(), [
                'income_id' => 'required',
                'payroll_period_type_id' => 'required',
                'employment_type_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $dataX = $request->all();

            $payroll_period_type_id = $request->payroll_period_type_id;
            $employment_type_id = $request->employment_type_id;
            $income_id = $request->income_id;

            // Store Incomes
            $arr_len = count($dataX['employee_id']);
            $payroll_income = [];
            $processed_count = 0;

            for ($i = 0; $i < $arr_len; $i++) {
                if ($dataX['employee_id'][$i] != NULL && $dataX['amount'][$i] >= 0) {

                    $payroll_income = [
                        'payroll_period_id'      => $payroll_period_type_id,
                        'employment_id' => $employment_type_id,
                        'income_id' => $income_id,
                        'employee_id'     => $dataX['employee_id'][$i],
                        'amount'     => $dataX['amount'][$i],
                    ];

                    DB::table('payroll_incomes')->updateOrInsert(['payroll_period_id' => $payroll_period_type_id, 'employment_id' => $employment_type_id, 'income_id' => $income_id, 'employee_id' => $dataX['employee_id'][$i]], $payroll_income);
                    $processed_count++;
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Income and Deductions',
                'activity' => 'Update',
                'description' => 'Update Payroll Income',
            );

            Audit::create($data_audit);

            return $this->successResponse(['processed_count' => $processed_count], 'Payroll incomes updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update payroll incomes: ' . $e->getMessage());
        }
    }

    protected function storeIncomeMonthlySplitPera(Request $request)
    {
        $validator = validator($request->all(), [
            'income_id' => 'required',
            'payroll_period_type_id' => 'required',
            'secondary_payroll_period_id' => 'required',
            'employment_type_id' => 'required',
            'employee_id' => 'required|array',
            'amount' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $income = DB::table('incomes')->where('id', $request->income_id)->first();
        if (!$income || !$this->isPeraIncomeName($income->name ?? '')) {
            return $this->validationErrorResponse([
                'income_id' => ['Monthly split is only allowed for PERA income.']
            ]);
        }

        $primaryId = (int) $request->payroll_period_type_id;
        $secondaryId = (int) $request->secondary_payroll_period_id;
        $employmentId = (int) $request->employment_type_id;
        $incomeId = (int) $request->income_id;
        $retainEnabled = $request->boolean('retain_next_month');

        $primary = DB::table('payroll_periods')->where('id', $primaryId)->first();
        $secondary = DB::table('payroll_periods')->where('id', $secondaryId)->first();
        if (!$primary || !$secondary) {
            return $this->notFoundResponse('Payroll period not found');
        }

        if ((int) $primary->payroll_interval_id !== (int) $secondary->payroll_interval_id) {
            return $this->validationErrorResponse([
                'payroll_period_type_id' => ['Both periods must belong to the same payroll interval.']
            ]);
        }

        $primaryYear = (int) date('Y', strtotime($primary->release_date));
        $primaryMonth = (int) date('n', strtotime($primary->release_date));
        $secondaryYear = (int) date('Y', strtotime($secondary->release_date));
        $secondaryMonth = (int) date('n', strtotime($secondary->release_date));
        if ($primaryYear !== $secondaryYear || $primaryMonth !== $secondaryMonth) {
            return $this->validationErrorResponse([
                'payroll_period_type_id' => ['Both periods must share the same release month.']
            ]);
        }

        $employeeIds = $request->employee_id ?? [];
        $amounts = $request->amount ?? [];
        $processedCount = 0;

        for ($i = 0; $i < count($employeeIds); $i++) {
            $employeeId = $employeeIds[$i] ?? null;
            $monthlyAmount = (float) ($amounts[$i] ?? 0);
            if (!$employeeId || $monthlyAmount < 0) {
                continue;
            }

            [$half1, $half2] = $this->splitMonthlyAmountIntoHalves($monthlyAmount);

            foreach ([[$primaryId, $half1], [$secondaryId, $half2]] as $pair) {
                DB::table('payroll_incomes')->updateOrInsert(
                    [
                        'payroll_period_id' => $pair[0],
                        'employment_id' => $employmentId,
                        'income_id' => $incomeId,
                        'employee_id' => $employeeId
                    ],
                    [
                        'payroll_period_id' => $pair[0],
                        'employment_id' => $employmentId,
                        'income_id' => $incomeId,
                        'employee_id' => $employeeId,
                        'amount' => $pair[1],
                        'retain_next_month' => $retainEnabled,
                    ]
                );
            }
            $processedCount++;
        }

        if ($retainEnabled) {
            $this->retainPeraSplitToNextMonth(
                $primary,
                $secondary,
                $employmentId,
                $incomeId,
                $employeeIds,
                $amounts
            );
        }

        Audit::create([
            'user_id' => Auth::user()->id,
            'module'  => 'Payroll Module',
            'menu'    => 'Payroll Income and Deductions',
            'activity' => 'Update',
            'description' => 'Update Payroll Income (monthly PERA split)',
        ]);

        return $this->successResponse(['processed_count' => $processedCount], 'Payroll incomes updated successfully');
    }

    protected function isPeraIncomeName(string $name): bool
    {
        $normalized = strtolower($name);
        return str_contains($normalized, 'pera') || str_contains($normalized, 'personal economic');
    }

    protected function splitMonthlyAmountIntoHalves(float $monthly): array
    {
        $firstHalf = round($monthly / 2, 2);
        $secondHalf = round($monthly - $firstHalf, 2);
        return [$firstHalf, $secondHalf];
    }

    protected function retainPeraSplitToNextMonth($primary, $secondary, int $employmentId, int $incomeId, array $employeeIds, array $amounts): void
    {
        $nextMonthAnchor = (new \DateTime($primary->release_date))->modify('first day of next month');
        $year = (int) $nextMonthAnchor->format('Y');
        $month = (int) $nextMonthAnchor->format('n');

        $nextPrimary = DB::table('payroll_periods')
            ->where('payroll_interval_id', $primary->payroll_interval_id)
            ->where('payroll_cutoff_id', $primary->payroll_cutoff_id)
            ->whereYear('release_date', $year)
            ->whereMonth('release_date', $month)
            ->where('active', true)
            ->where('posted', false)
            ->orderBy('release_date', 'asc')
            ->first();

        $nextSecondary = DB::table('payroll_periods')
            ->where('payroll_interval_id', $secondary->payroll_interval_id)
            ->where('payroll_cutoff_id', $secondary->payroll_cutoff_id)
            ->whereYear('release_date', $year)
            ->whereMonth('release_date', $month)
            ->where('active', true)
            ->where('posted', false)
            ->orderBy('release_date', 'asc')
            ->first();

        if (!$nextPrimary || !$nextSecondary) {
            return;
        }

        for ($i = 0; $i < count($employeeIds); $i++) {
            $employeeId = $employeeIds[$i] ?? null;
            $monthlyAmount = (float) ($amounts[$i] ?? 0);
            if (!$employeeId || $monthlyAmount < 0) {
                continue;
            }

            [$half1, $half2] = $this->splitMonthlyAmountIntoHalves($monthlyAmount);

            foreach ([[(int) $nextPrimary->id, $half1], [(int) $nextSecondary->id, $half2]] as $pair) {
                DB::table('payroll_incomes')->updateOrInsert(
                    [
                        'payroll_period_id' => $pair[0],
                        'employment_id' => $employmentId,
                        'income_id' => $incomeId,
                        'employee_id' => $employeeId
                    ],
                    [
                        'payroll_period_id' => $pair[0],
                        'employment_id' => $employmentId,
                        'income_id' => $incomeId,
                        'employee_id' => $employeeId,
                        'amount' => $pair[1],
                        'retain_next_month' => true,
                    ]
                );
            }
        }
    }

    public function storeDeduction(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'deduction_id' => 'required',
                'payroll_period_id' => 'required',
                'employment_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $dataX = $request->all();

            $payroll_period_type_id = $request->payroll_period_id;
            $employment_type_id = $request->employment_id;
            $deduction_id = $request->deduction_id;
            $deduction = DB::table('deductions')->where('id', $deduction_id)->first();
            $isEaDeduction = $deduction && $this->isEaDeductionName((string) ($deduction->name ?? ''));
            $retainEnabled = $isEaDeduction ? $request->boolean('retain_next_month') : false;

            // Store Incomes
            $arr_len = count($dataX['employee_id']);
            $payroll_deduction = [];
            $processed_count = 0;

            for ($i = 0; $i < $arr_len; $i++) {
                if ($dataX['employee_id'][$i] != NULL && $dataX['amount'][$i] >= 0) {

                    $payroll_deduction = [
                        'payroll_period_id' => $payroll_period_type_id,
                        'employment_id' => $employment_type_id,
                        'deduction_id' => $deduction_id,
                        'employee_id'     => $dataX['employee_id'][$i],
                        'amount'     => $dataX['amount'][$i],
                        'retain_next_month' => $retainEnabled,
                    ];

                    DB::table('payroll_deductions')->updateOrInsert(['payroll_period_id' => $payroll_period_type_id, 'employment_id' => $employment_type_id, 'deduction_id' => $deduction_id, 'employee_id' => $dataX['employee_id'][$i]], $payroll_deduction);
                    $processed_count++;
                }
            }

            if ($isEaDeduction && $retainEnabled) {
                $this->retainEaToNextMonthByItemSchedule(
                    (int) $payroll_period_type_id,
                    (int) $employment_type_id,
                    (int) $deduction_id,
                    $dataX['employee_id'] ?? [],
                    $dataX['amount'] ?? []
                );
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Income and Deductions',
                'activity' => 'Update',
                'description' => 'Update Payroll Deduction',
            );

            Audit::create($data_audit);

            return $this->successResponse(['processed_count' => $processed_count], 'Payroll deductions updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update payroll deductions: ' . $e->getMessage());
        }
    }

    protected function isEaDeductionName(string $name): bool
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/', '', $name));
        return $normalized === 'ea' || str_contains(strtolower($name), 'emergency allowance');
    }

    protected function retainEaToNextMonthByItemSchedule(
        int $currentPeriodId,
        int $employmentId,
        int $deductionId,
        array $employeeIds,
        array $amounts
    ): void {
        $current = DB::table('payroll_periods')->where('id', $currentPeriodId)->first();
        if (!$current) {
            return;
        }

        $nextMonthAnchor = (new \DateTime($current->release_date))->modify('first day of next month');
        $year = (int) $nextMonthAnchor->format('Y');
        $month = (int) $nextMonthAnchor->format('n');

        $nextPeriods = DB::table('payroll_periods')
            ->where('payroll_interval_id', $current->payroll_interval_id)
            ->whereYear('release_date', $year)
            ->whereMonth('release_date', $month)
            ->where('active', true)
            ->where('posted', false)
            ->orderBy('release_date', 'asc')
            ->get();

        if ($nextPeriods->isEmpty()) {
            return;
        }

        foreach ($nextPeriods as $nextPeriod) {
            $isEaActiveForPeriod = DB::table('payroll_item_schedule_headers as h')
                ->join('payroll_item_schedule_details as d', 'h.id', '=', 'd.payroll_item_schedule_header_id')
                ->where('h.payroll_interval_type_id', $nextPeriod->payroll_interval_id)
                ->where('h.payroll_period_type_id', $nextPeriod->id)
                ->where('h.employment_type_id', $employmentId)
                ->where('d.deduction_id', $deductionId)
                ->where('d.active', true)
                ->exists();

            if (!$isEaActiveForPeriod) {
                continue;
            }

            for ($i = 0; $i < count($employeeIds); $i++) {
                $employeeId = $employeeIds[$i] ?? null;
                $amount = (float) ($amounts[$i] ?? 0);
                if (!$employeeId || $amount < 0) {
                    continue;
                }

                DB::table('payroll_deductions')->updateOrInsert(
                    [
                        'payroll_period_id' => (int) $nextPeriod->id,
                        'employment_id' => $employmentId,
                        'deduction_id' => $deductionId,
                        'employee_id' => (int) $employeeId,
                    ],
                    [
                        'payroll_period_id' => (int) $nextPeriod->id,
                        'employment_id' => $employmentId,
                        'deduction_id' => $deductionId,
                        'employee_id' => (int) $employeeId,
                        'amount' => $amount,
                        'retain_next_month' => true,
                    ]
                );
            }
        }
    }

    public function getIncomeList($payroll_period_type_id, $employment_type_id)
    {
        try {
            $interval = DB::table('payroll_periods')->select('payroll_interval_id')->where('id', $payroll_period_type_id)->get();

            if ($interval->isEmpty()) {
                return $this->notFoundResponse('Payroll period not found');
            }

            $data = DB::table('incomes as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.income_id')
                ->join('payroll_item_schedule_headers as c', 'b.payroll_item_schedule_header_id', '=', 'c.id')
                ->select('a.id', 'a.name')
                ->where(['c.payroll_interval_type_id' => $interval[0]->payroll_interval_id, 'c.employment_type_id' => $employment_type_id, 'b.active' => true])
                ->orderBy('a.id', 'asc')
                ->distinct()
                ->get();

            return $this->successResponse($data, 'Income list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve income list: ' . $e->getMessage());
        }
    }

    public function getDeductionList($payroll_period_type_id, $employment_type_id)
    {
        try {
            $interval = DB::table('payroll_periods')->select('payroll_interval_id')->where('id', $payroll_period_type_id)->get();

            if ($interval->isEmpty()) {
                return $this->notFoundResponse('Payroll period not found');
            }

            $data = DB::table('deductions as a')
                ->join('payroll_item_schedule_details as b', 'a.id', '=', 'b.deduction_id')
                ->join('payroll_item_schedule_headers as c', 'b.payroll_item_schedule_header_id', '=', 'c.id')
                ->select('a.id', 'a.name')
                ->where(['c.payroll_interval_type_id' => $interval[0]->payroll_interval_id, 'c.employment_type_id' => $employment_type_id, 'b.active' => true])
                ->orderBy('a.id', 'asc')
                ->distinct()
                ->get();

            return $this->successResponse($data, 'Deduction list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve deduction list: ' . $e->getMessage());
        }
    }

    public function getEmployeeList($payroll_period_type_id, $employment_type_id, $type_id, $item_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($type_id == 1) { //if deduction

                $loans = DB::table('loan_applications as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->join('time_data_summary as tds', 'b.id', '=', 'tds.employee_id')
                    ->leftjoin('name_suffixes as c', 'b.name_suffix_id', '=', 'c.id')
                    ->leftjoin('companies as d', 'b.company_id', '=', 'd.id')
                    ->leftjoin('branches as e', 'b.branch_id', '=', 'e.id')
                    ->select(
                        'b.id as employee_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                        db::raw("upper(c.name) as suffix"),
                        'd.name as company',
                        db::raw("case when (a.loan_amortization > a.balance) then a.balance
                                when (a.loan_amortization <= a.balance) then a.loan_amortization
                                else cast(0 as decimal(18,2))
                        end as amount")
                    )
                    ->where('a.balance', '>', 0)
                    ->where([
                        'b.active' => true,
                        'b.is_employee' => true,
                        'a.deduction_id' => $item_id,
                        'tds.payroll_period_id' => $payroll_period_type_id
                    ])
                    ->distinct();

                $data = DB::table('employees as a')
                    ->join('time_data_summary as tds', 'a.id', '=', 'tds.employee_id')
                    ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                    ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id as employee_id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                        db::raw("upper(c.name) as suffix"),
                        'd.name as company',
                        db::raw("cast(0 as decimal(18,2)) as amount")
                    )
                    ->where([
                        'a.employment_type_id' => $employment_type_id,
                        'a.active' => true,
                        'a.is_employee' => true,
                        'tds.payroll_period_id' => $payroll_period_type_id
                    ])
                    ->whereNotIn('a.id', function ($query) {
                        $query->select('employee_id')->from('loan_applications')->where('balance', '>', 0)->get();
                    })
                    ->distinct()
                    ->unionAll($loans)
                    ->orderBy('last_name', 'asc')
                    ->get();
            } else { //if income
                $data = DB::table('employees as a')
                    ->join('time_data_summary as tds', 'a.id', '=', 'tds.employee_id')
                    ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                    ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id as employee_id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                        db::raw("upper(c.name) as suffix"),
                        'd.name as company'
                    )
                    ->where([
                        'a.employment_type_id' => $employment_type_id,
                        'a.active' => true,
                        'a.is_employee' => true,
                        'tds.payroll_period_id' => $payroll_period_type_id
                    ])
                    ->distinct()
                    ->orderBy('a.last_name', 'asc')
                    ->get();
            }

            return $this->successResponse($data, 'Employee list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee list: ' . $e->getMessage());
        }
    }

    public function getEmployeeIncome($payroll_period_type_id, $employment_type_id, $income_id)
    {
        try {
            $data = $this->fetchEmployeeIncomeRows($payroll_period_type_id, $employment_type_id, $income_id);

            return $this->successResponse($data, 'Employee income data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee income data: ' . $e->getMessage());
        }
    }

    public function getEmployeeIncomeMerged($payroll_period_type_id, $employment_type_id, $income_id, $secondary_payroll_period_id)
    {
        try {
            $this->applyPersistentRetainedPeraForMonthPair(
                (int) $payroll_period_type_id,
                (int) $secondary_payroll_period_id,
                (int) $employment_type_id,
                (int) $income_id
            );

            $first = $this->fetchEmployeeIncomeRows($payroll_period_type_id, $employment_type_id, $income_id)->keyBy('employee_id');
            $second = $this->fetchEmployeeIncomeRows($secondary_payroll_period_id, $employment_type_id, $income_id)->keyBy('employee_id');

            $employeeIds = $first->keys()->merge($second->keys())->unique()->values();

            $merged = $employeeIds->map(function ($employeeId) use ($first, $second) {
                $base = $first->get($employeeId) ?: $second->get($employeeId);
                if (!$base) {
                    return null;
                }
                $amount1 = (float) (($first->get($employeeId)->amount ?? 0));
                $amount2 = (float) (($second->get($employeeId)->amount ?? 0));
                $base->amount = round($amount1 + $amount2, 2);
                $retain1 = (bool) (($first->get($employeeId)->retain_next_month ?? false));
                $retain2 = (bool) (($second->get($employeeId)->retain_next_month ?? false));
                $base->retain_next_month = $retain1 || $retain2;
                return $base;
            })->filter()->sortBy('last_name')->values();

            return $this->successResponse($merged, 'Merged employee income data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve merged employee income data: ' . $e->getMessage());
        }
    }

    protected function fetchEmployeeIncomeRows($payroll_period_type_id, $employment_type_id, $income_id)
    {
        $app_key = env("APP_KEY", "");

        $data_new = DB::table('employees as a')
            ->join('time_data_summary as tds', 'a.id', '=', 'tds.employee_id')
            ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
            ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
            ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
            ->leftjoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->select(
                'a.id as employee_id',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                db::raw("upper(c.name) as suffix"),
                db::raw("ISNULL(d.name,'') as company"),
                db::raw("ISNULL(dept.name,'') as department"),
                db::raw("CAST(0 AS DECIMAL(18,2)) as amount"),
                db::raw("CAST(0 AS BIT) as retain_next_month")
            )
            ->where([
                'a.employment_type_id' => $employment_type_id,
                'a.active' => true,
                'a.is_employee' => true,
                'tds.payroll_period_id' => $payroll_period_type_id
            ])
            ->whereNotIn('a.id', function ($query) use ($payroll_period_type_id, $income_id) {
                $query->select('employee_id')->from('payroll_incomes')->where([
                    'payroll_period_id' => $payroll_period_type_id,
                    'income_id' => $income_id
                ]);
            })
            ->distinct();

        return DB::table('employees as a')
            ->join('payroll_incomes as b', 'a.id', '=', 'b.employee_id')
            ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
            ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
            ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
            ->leftjoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->select(
                'a.id as employee_id',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                db::raw("upper(c.name) as suffix"),
                db::raw("ISNULL(d.name,'') as company"),
                db::raw("ISNULL(dept.name,'') as department"),
                'b.amount',
                db::raw("ISNULL(b.retain_next_month, 0) as retain_next_month")
            )
            ->where([
                'a.employment_type_id' => $employment_type_id,
                'b.payroll_period_id' => $payroll_period_type_id,
                'a.active' => true,
                'a.is_employee' => true,
                'b.income_id' => $income_id
            ])
            ->union($data_new)
            ->orderBy('last_name', 'asc')
            ->get();
    }

    protected function applyPersistentRetainedPeraForMonthPair(int $primaryId, int $secondaryId, int $employmentId, int $incomeId): void
    {
        $income = DB::table('incomes')->where('id', $incomeId)->first();
        if (!$income || !$this->isPeraIncomeName($income->name ?? '')) {
            return;
        }

        $primary = DB::table('payroll_periods')->where('id', $primaryId)->first();
        $secondary = DB::table('payroll_periods')->where('id', $secondaryId)->first();
        if (!$primary || !$secondary) {
            return;
        }
        if ((int) $primary->payroll_interval_id !== (int) $secondary->payroll_interval_id) {
            return;
        }

        $prevMonthAnchor = (new \DateTime($primary->release_date))->modify('first day of previous month');
        $year = (int) $prevMonthAnchor->format('Y');
        $month = (int) $prevMonthAnchor->format('n');

        $prevPrimary = DB::table('payroll_periods')
            ->where('payroll_interval_id', $primary->payroll_interval_id)
            ->where('payroll_cutoff_id', $primary->payroll_cutoff_id)
            ->whereYear('release_date', $year)
            ->whereMonth('release_date', $month)
            ->where('active', true)
            ->orderBy('release_date', 'asc')
            ->first();

        $prevSecondary = DB::table('payroll_periods')
            ->where('payroll_interval_id', $secondary->payroll_interval_id)
            ->where('payroll_cutoff_id', $secondary->payroll_cutoff_id)
            ->whereYear('release_date', $year)
            ->whereMonth('release_date', $month)
            ->where('active', true)
            ->orderBy('release_date', 'asc')
            ->first();

        if (!$prevPrimary || !$prevSecondary) {
            return;
        }

        $retainedRows = DB::table('payroll_incomes')
            ->where('employment_id', $employmentId)
            ->where('income_id', $incomeId)
            ->whereIn('payroll_period_id', [(int) $prevPrimary->id, (int) $prevSecondary->id])
            ->where('retain_next_month', true)
            ->select('employee_id', DB::raw('SUM(ISNULL(amount, 0)) as monthly_amount'))
            ->groupBy('employee_id')
            ->get();

        foreach ($retainedRows as $row) {
            $employeeId = (int) $row->employee_id;
            $monthlyAmount = (float) ($row->monthly_amount ?? 0);
            if ($employeeId <= 0 || $monthlyAmount < 0) {
                continue;
            }

            $isInCurrentPeriod = DB::table('time_data_summary')
                ->where('payroll_period_id', $primaryId)
                ->where('employee_id', $employeeId)
                ->exists();
            if (!$isInCurrentPeriod) {
                continue;
            }

            $existsCurrent = DB::table('payroll_incomes')
                ->where('employment_id', $employmentId)
                ->where('income_id', $incomeId)
                ->where('employee_id', $employeeId)
                ->whereIn('payroll_period_id', [$primaryId, $secondaryId])
                ->exists();
            if ($existsCurrent) {
                continue;
            }

            [$half1, $half2] = $this->splitMonthlyAmountIntoHalves($monthlyAmount);

            foreach ([[$primaryId, $half1], [$secondaryId, $half2]] as $pair) {
                DB::table('payroll_incomes')->updateOrInsert(
                    [
                        'payroll_period_id' => $pair[0],
                        'employment_id' => $employmentId,
                        'income_id' => $incomeId,
                        'employee_id' => $employeeId
                    ],
                    [
                        'payroll_period_id' => $pair[0],
                        'employment_id' => $employmentId,
                        'income_id' => $incomeId,
                        'employee_id' => $employeeId,
                        'amount' => $pair[1],
                        'retain_next_month' => true,
                    ]
                );
            }
        }
    }

    public function getEmployeePreviousIncome($payroll_period_type_id, $employment_type_id, $income_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $payroll_period = DB::table('payroll_periods')
                ->where('id', $payroll_period_type_id)
                ->get();

            if ($payroll_period->isEmpty()) {
                return $this->notFoundResponse('Payroll period not found');
            }

            $payroll_interval_id = $payroll_period[0]->payroll_interval_id;
            $payroll_cutoff_id = $payroll_period[0]->payroll_cutoff_id;
            $payroll_start_date = $payroll_period[0]->payroll_start_date;

            $payroll_period2 = DB::table('payroll_periods')
                ->where(['payroll_interval_id' => $payroll_interval_id, 'payroll_cutoff_id' => $payroll_cutoff_id])
                ->where('payroll_start_date', '<', $payroll_start_date)
                ->orderBy('payroll_start_date', 'desc')
                ->get();

            if ($payroll_period2->isNotEmpty()) {
                $payroll_period_type_id2 = $payroll_period2[0]->id;

                $data_new = DB::table('employees as a')
                    ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                    ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id as employee_id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                        db::raw("upper(c.name) as suffix"),
                        db::raw("ISNULL(d.name,'') as company"),
                        db::raw("CAST(0 AS DECIMAL(18,2)) as amount")
                    )
                    ->where([
                        'a.employment_type_id' => $employment_type_id,
                        'a.active' => true,
                        'a.is_employee' => true
                    ])
                    ->whereNotIn('a.id', function ($query) use ($payroll_period_type_id) {
                        $query->select('employee_id')->from('payroll_incomes')->where('payroll_period_id', $payroll_period_type_id);
                    });

                $data = DB::table('employees as a')
                    ->join('payroll_incomes as b', 'a.id', '=', 'b.employee_id')
                    ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                    ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id as employee_id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                        db::raw("upper(c.name) as suffix"),
                        db::raw("ISNULL(d.name,'') as company"),
                        'b.amount'
                    )
                    ->where(['a.employment_type_id' => $employment_type_id, 'b.payroll_period_id' => $payroll_period_type_id2, 'a.active' => true, 'b.income_id' => $income_id])
                    ->unionAll($data_new)
                    ->orderBy('last_name', 'asc')
                    ->get();

                return $this->successResponse($data, 'Employee previous income data retrieved successfully');
            } else {
                return $this->successResponse([], 'No previous payroll period found');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee previous income data: ' . $e->getMessage());
        }
    }

    public function getEmployeeDeduction($payroll_period_type_id, $employment_type_id, $deduction_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $loans = DB::table('loan_applications as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('time_data_summary as tds', 'b.id', '=', 'tds.employee_id')
                ->leftjoin('name_suffixes as c', 'b.name_suffix_id', '=', 'c.id')
                ->leftjoin('companies as d', 'b.company_id', '=', 'd.id')
                ->leftjoin('branches as e', 'b.branch_id', '=', 'e.id')
                ->leftjoin('departments as dept', 'b.department_id', '=', 'dept.id')
                ->select(
                    'b.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.first_name ELSE dbo.ufn_DecryptString(b.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.middle_name ELSE dbo.ufn_DecryptString(b.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN b.last_name ELSE dbo.ufn_DecryptString(b.last_name,'$app_key') END as last_name"),
                    db::raw("upper(isnull(c.name,'')) as suffix"),
                    db::raw("ISNULL(d.name,'') as company"),
                    db::raw("ISNULL(dept.name,'') as department"),
                    db::raw("case when (a.loan_amortization > a.balance) then a.balance
                                when (a.loan_amortization <= a.balance) then a.loan_amortization
                                else cast(0 as decimal(18,2))
                        end as amount"),
                    db::raw("CAST(0 AS BIT) as retain_next_month")
                )
                ->where('a.balance', '>', 0)
                ->whereNotIn(
                    'b.id',
                    function ($query) use ($payroll_period_type_id, $deduction_id) {
                        $query->select('employee_id')->from('payroll_deductions')->where([
                            'payroll_period_id' => $payroll_period_type_id,
                            'deduction_id' => $deduction_id
                        ])->get();
                    }
                )
                ->where([
                    'b.active' => true,
                    'b.is_employee' => true,
                    'a.deduction_id' => $deduction_id,
                    'tds.payroll_period_id' => $payroll_period_type_id
                ])
                ->distinct();

            $data_new = DB::table('employees as a')
                ->join('time_data_summary as tds', 'a.id', '=', 'tds.employee_id')
                ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                ->leftjoin('departments as dept', 'a.department_id', '=', 'dept.id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    db::raw("upper(isnull(c.name,'')) as suffix"),
                    db::raw("ISNULL(d.name,'') as company"),
                    db::raw("ISNULL(dept.name,'') as department"),
                    db::raw("cast(0 as decimal(18,2)) as amount"),
                    db::raw("CAST(0 AS BIT) as retain_next_month")
                )
                ->where([
                    'a.employment_type_id' => $employment_type_id,
                    'a.active' => true,
                    'a.is_employee' => true,
                    'tds.payroll_period_id' => $payroll_period_type_id
                ])
                ->whereNotIn('a.id', function ($query) use ($deduction_id) {
                    $query->select('employee_id')->from('loan_applications')->where('balance', '>', 0)->where('deduction_id', $deduction_id)->get();
                })
                ->whereNotIn('a.id', function ($query) use ($payroll_period_type_id, $deduction_id) {
                    $query->select('employee_id')->from('payroll_deductions')->where([
                        'payroll_period_id' => $payroll_period_type_id,
                        'deduction_id' => $deduction_id
                    ])->get();
                })
                ->distinct()
                ->unionAll($loans);

            $data = DB::table('employees as a')
                ->join('payroll_deductions as b', 'a.id', '=', 'b.employee_id')
                ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                ->leftjoin('departments as dept', 'a.department_id', '=', 'dept.id')
                ->select(
                    'a.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    db::raw("upper(isnull(c.name,'')) as suffix"),
                    db::raw("ISNULL(d.name,'') as company"),
                    db::raw("ISNULL(dept.name,'') as department"),
                    'b.amount',
                    db::raw("ISNULL(b.retain_next_month, 0) as retain_next_month")
                )
                ->where([
                    'a.employment_type_id' => $employment_type_id,
                    'b.payroll_period_id' => $payroll_period_type_id,
                    'a.active' => true,
                    'a.is_employee' => true,
                    'b.deduction_id' => $deduction_id
                ])
                ->unionAll($data_new)
                ->orderBy('last_name', 'asc')
                ->get();

            return $this->successResponse($data, 'Employee deduction data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee deduction data: ' . $e->getMessage());
        }
    }

    public function getEmployeePreviousDeduction($payroll_period_type_id, $employment_type_id, $deduction_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $payroll_period = DB::table('payroll_periods')
                ->where('id', $payroll_period_type_id)
                ->get();

            if ($payroll_period->isEmpty()) {
                return $this->notFoundResponse('Payroll period not found');
            }

            $payroll_interval_id = $payroll_period[0]->payroll_interval_id;
            $payroll_cutoff_id = $payroll_period[0]->payroll_cutoff_id;
            $payroll_start_date = $payroll_period[0]->payroll_start_date;

            $payroll_period2 = DB::table('payroll_periods')
                ->where(['payroll_interval_id' => $payroll_interval_id, 'payroll_cutoff_id' => $payroll_cutoff_id])
                ->where('payroll_start_date', '<', $payroll_start_date)
                ->orderBy('payroll_start_date', 'desc')
                ->get();

            if (count($payroll_period2) > 0) {
                $payroll_period_type_id2 = $payroll_period2[0]->id;

                $data = DB::table('employees as a')
                    ->join('payroll_deductions as b', 'a.id', '=', 'b.employee_id')
                    ->leftjoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->leftjoin('companies as d', 'a.company_id', '=', 'd.id')
                    ->leftjoin('branches as e', 'a.branch_id', '=', 'e.id')
                    ->select(
                        'a.id as employee_id',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                        db::raw("upper(c.name) as suffix"),
                        db::raw("ISNULL(d.name,'') as company"),
                        'b.amount'
                    )
                    ->where(['a.employment_type_id' => $employment_type_id, 'b.payroll_period_id' => $payroll_period_type_id2, 'a.active' => true, 'b.deduction_id' => $deduction_id])
                    ->orderBy('a.last_name', 'asc')
                    ->get();

                return $this->successResponse($data, 'Employee previous deduction data retrieved successfully');
            } else {
                return $this->successResponse([], 'No previous payroll period found');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee previous deduction data: ' . $e->getMessage());
        }
    }
}
