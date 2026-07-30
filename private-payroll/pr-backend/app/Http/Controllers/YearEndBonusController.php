<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Auth;
use App\Audit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\PayrollBenefitsEmployeeScope;
use App\Support\ReportDivisionFilter;
use App\Traits\ApiResponse;

class YearEndBonusController extends Controller
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
            $branches = DB::table('branches')->get();
            $divisions = ReportDivisionFilter::activeDivisions();
            
            // Return empty array initially - no year-end bonus records until processed
            $yearend_records = collect([]);
            return $this->successResponse([
                'branches' => $branches,
                'yearend_records' => $yearend_records,
                'divisions' => $divisions,
                'departments' => $divisions,
            ], 'Year-end bonus data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve year-end bonus data: ' . $e->getMessage());
        }
    }


    // Function to get the year-end bonus records of each department/employee
    public function getRecords(Request $request)
    {
        try {
            $request->merge([
                'division_id' => $request->input('division_id', $request->input('department_id')),
            ]);

            $validator = validator($request->all(), [
                'branch_id' => 'nullable|integer|exists:branches,id',
                'department_id' => 'required',
                'years' => 'required|integer|min:2000|max:2100',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $isAllDivisions = $request->department_id === 'all';
            $divisionId = $isAllDivisions
                ? null
                : (ReportDivisionFilter::resolveId($request) ?? (int) $request->department_id);
            $legacyDivisionIds = ($isAllDivisions || !$divisionId)
                ? []
                : ReportDivisionFilter::legacyHeaderDepartmentIdsForDivision($divisionId);

            $attendanceSub = DB::table('time_data as td')
                ->leftJoin('holidays as h', function ($j) {
                    $j->on('td.holiday_id', '=', 'h.id')->where('td.is_holiday', '=', 1);
                })
                ->leftJoin('holiday_types as ht', 'h.holiday_type', '=', 'ht.id')
                ->whereRaw('YEAR(td.[date]) = ?', [$request->years])
                ->groupBy('td.employee_id')
                ->select(
                    'td.employee_id',
                    DB::raw("COUNT(CASE
                        WHEN td.work_hours > 0 THEN 1
                        WHEN td.leave > 0 THEN 1
                        WHEN td.is_ob = 1 THEN 1
                        WHEN td.is_holiday = 1 AND ISNULL(ht.absent_with_pay, 0) = 1 THEN 1
                    END) as days_present")
                );

            // Employees in selected division (with optional existing yearend_bonus rows)
            $query = DB::table('employees as b')
                ->leftJoin('divisions as div', 'div.id', '=', 'b.division_id')
                ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoinSub($attendanceSub, 'att', function ($join) {
                    $join->on('b.id', '=', 'att.employee_id');
                })
                ->leftJoin('yearend_bonus as a', function ($join) use ($request, $isAllDivisions, $legacyDivisionIds) {
                    $join->on('a.employee_id', '=', 'b.id')
                        ->where('a.years', '=', $request->years);

                    if (!empty($request->branch_id)) {
                        $join->where('a.branch_id', '=', $request->branch_id);
                    }

                    if (!$isAllDivisions && !empty($legacyDivisionIds)) {
                        $join->whereIn('a.department_id', $legacyDivisionIds);
                    }
                })
                ->select(
                    'a.id',
                    'b.id as employee_id',
                    DB::raw('COALESCE(a.branch_id, b.branch_id) as branch_id'),
                    DB::raw('COALESCE(a.department_id, b.division_id, b.department_id) as department_id'),
                    DB::raw($request->years . ' as years'),
                    'b.salary',
                    DB::raw('COALESCE(a.bonus_amount, 0) as bonus_amount'),
                    DB::raw('COALESCE(a.cash_gift_incentive, 0) as cash_gift_incentive'),
                    DB::raw('COALESCE(a.cash_gift_amount, 0) as cash_gift_amount'),
                    DB::raw('ISNULL(att.days_present, 0) as days_present'),
                    'b.photo',
                    'b.employee_no',
                    DB::raw('COALESCE(div.name, c.name) as department'),
                    'd.name as position',
                    DB::raw("CASE
                        WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                            CONCAT(b.last_name, ', ', b.first_name, ' ', LEFT(b.middle_name, 1))
                        ELSE
                            CONCAT(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')), ', ',
                                   RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')), ' ',
                                   LEFT(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key')), 1))
                        END as name"),
                    DB::raw('(COALESCE(a.bonus_amount, 0) + COALESCE(a.cash_gift_amount, 0)) as total_amount'),
                    DB::raw("CASE 
                        WHEN COALESCE(a.bonus_amount, 0) > 0 THEN 
                            CONCAT(ROUND((COALESCE(a.bonus_amount, 0) / b.salary) * 100, 2), '%')
                        ELSE '0%'
                        END as period_earned"),
                    DB::raw(
                        (!$isAllDivisions && $divisionId && !empty($legacyDivisionIds))
                            ? '(SELECT TOP 1 CASE WHEN ISNULL(hdr.posted, 0) = 1 THEN 1 ELSE 0 END
                                FROM yearend_bonus_header hdr
                                WHERE hdr.year_id = ' . (int) $request->years . '
                                AND hdr.department_id IN (' . implode(',', array_map('intval', $legacyDivisionIds)) . ')'
                                . (!empty($request->branch_id) ? ' AND hdr.branch_id = ' . (int) $request->branch_id : '') . '
                            ) as posted'
                            : '0 as posted'
                    )
                )
                ->where('b.active', true)
                ->where('b.is_employee', true);

            PayrollBenefitsEmployeeScope::apply($query, 'b');

            if (!empty($request->branch_id)) {
                $query->where('b.branch_id', $request->branch_id);
            }

            if (!$isAllDivisions && $divisionId) {
                $query->where('b.division_id', $divisionId);
            }

            $yearend_records = $query
                ->orderBy('name', 'asc')
                ->get();

            return $this->successResponse([
                'yearend_records' => $yearend_records
            ], 'Year-end bonus records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve year-end bonus records: ' . $e->getMessage());
        }
    }

    /**
     * Update a single year-end bonus record's bonus_amount and/or cash_gift_amount.
     */
    public function updateRecord(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'bonus_amount' => 'nullable|numeric|min:0',
                'cash_gift_amount' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $exists = DB::table('yearend_bonus')->where('id', $id)->exists();
            if (!$exists) {
                return $this->errorResponse('Year-end bonus record not found.', 404);
            }

            $updates = [];
            if ($request->has('bonus_amount')) {
                $updates['bonus_amount'] = (float) $request->bonus_amount;
            }
            if ($request->has('cash_gift_amount')) {
                $updates['cash_gift_amount'] = (float) $request->cash_gift_amount;
            }

            if (empty($updates)) {
                return $this->successResponse(null, 'No changes to apply.');
            }

            DB::table('yearend_bonus')->where('id', $id)->update($updates);

            return $this->successResponse(null, 'Year-end bonus record updated successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update year-end bonus record: ' . $e->getMessage());
        }
    }

    public function process(Request $request)
    {
        try {
            $request->merge([
                'division_id' => $request->input('division_id', $request->input('department_id')),
            ]);

            $validator = validator($request->all(), [
                'branch_id' => 'nullable|integer|exists:branches,id',
                'years' => 'required|integer|min:2000|max:2100',
                'department_id' => 'required',
                'cash_gift' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $departmentId = $request->department_id;

            // If "all" is selected, process every active division.
            if ($departmentId === 'all') {
                $divisions = ReportDivisionFilter::activeDivisions();
                foreach ($divisions as $division) {
                    $branchIds = $this->resolveBranchIdsForDivision((int) $division->id, $request->branch_id);
                    foreach ($branchIds as $branchId) {
                        $this->processDepartment(
                            $branchId,
                            (int) $division->id,
                            $request->years,
                            $request->cash_gift,
                            false
                        );
                    }
                }
                return $this->successResponse(null, 'Successfully Generated 13th Month Pay for all divisions!');
            }

            $divisionId = ReportDivisionFilter::resolveId($request) ?? (int) $departmentId;
            $branchIds = $this->resolveBranchIdsForDivision($divisionId, $request->branch_id);
            $processed = false;

            foreach ($branchIds as $branchId) {
                if ($this->processDepartment(
                    $branchId,
                    $divisionId,
                    $request->years,
                    $request->cash_gift,
                    false
                )) {
                    $processed = true;
                }
            }

            $result = $processed;

            if (!$result) {
                return $this->errorResponse('No Employee to process 13th Month Pay.', 400);
            }

            return $this->successResponse(null, 'Successfully Generated 13th Month Pay!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process year-end bonus: ' . $e->getMessage());
        }
    }

    /**
     * Process year-end bonus for a single department.
     *
     * @param int $branchId
     * @param int $departmentId
     * @param int $year
     * @param float|null $cashGift
     * @param bool $failIfNoEmployees
     * @return bool true if processed at least one employee, false otherwise
     */
    /**
     * Branch ids to use when processing a division (optional single branch from request).
     */
    protected function resolveBranchIdsForDivision(int $divisionId, $requestedBranchId = null)
    {
        if (!empty($requestedBranchId)) {
            return collect([(int) $requestedBranchId]);
        }

        $ids = DB::table('employees')
            ->where([
                'active' => true,
                'is_employee' => true,
                'division_id' => $divisionId,
            ])
            ->whereNotNull('branch_id')
            ->distinct()
            ->pluck('branch_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            $fallback = DB::table('branches')->orderBy('id')->value('id');
            if ($fallback) {
                $ids = collect([(int) $fallback]);
            }
        }

        return $ids;
    }

    /**
     * @param int $branchId
     * @param int $divisionId division id (stored in yearend_bonus.department_id for legacy headers)
     */
    protected function processDepartment($branchId, $divisionId, $year, $cashGift, $failIfNoEmployees = true)
    {
        $legacyDivisionIds = ReportDivisionFilter::legacyHeaderDepartmentIdsForDivision($divisionId);

        $yearend_header = DB::table('yearend_bonus_header')->where([
            'year_id' => $year,
            'branch_id' => $branchId,
        ])
            ->whereIn('department_id', $legacyDivisionIds)
            ->get();

        if ($yearend_header->isEmpty()) {
            $header_id = DB::table('yearend_bonus_header')->max('id');
            $header_id = $header_id + 1;
        } else {
            $header_id = $yearend_header[0]->id;
        }

        $attendanceSub = DB::table('time_data as td')
            ->leftJoin('holidays as h', function ($j) {
                $j->on('td.holiday_id', '=', 'h.id')->where('td.is_holiday', '=', 1);
            })
            ->leftJoin('holiday_types as ht', 'h.holiday_type', '=', 'ht.id')
            ->whereRaw('YEAR(td.[date]) = ?', [$year])
            ->groupBy('td.employee_id')
            ->select(
                'td.employee_id',
                DB::raw("COUNT(CASE
                    WHEN td.work_hours > 0 THEN 1
                    WHEN td.leave > 0 THEN 1
                    WHEN td.is_ob = 1 THEN 1
                    WHEN td.is_holiday = 1 AND ISNULL(ht.absent_with_pay, 0) = 1 THEN 1
                END) as days_present")
            );

        // Compute the equivalent months worked: FLOOR(days_present / 30).
        // 0–29 days → 0 months, 30–59 → 1 month, 60–89 → 2 months, etc.
        $monthsWorkedExpr = "(
            CASE
                WHEN ISNULL(att.days_present, 0) = 0 THEN 0
                WHEN FLOOR(ISNULL(att.days_present, 0) / 30.0) >= (SELECT MAX(months) FROM yearend_table)
                    THEN (SELECT MAX(months) FROM yearend_table)
                ELSE FLOOR(ISNULL(att.days_present, 0) / 30.0)
            END
        )";

        $employees = DB::table('employees as a')
            ->leftJoinSub($attendanceSub, 'att', function ($join) {
                $join->on('a.id', '=', 'att.employee_id');
            })
            ->leftJoin('yearend_table as e', DB::raw($monthsWorkedExpr), '=', 'e.months')
            ->select(
                'a.id',
                'a.first_name',
                'a.last_name',
                'a.salary',
                DB::raw('ISNULL(att.days_present, 0) as days_present'),
                DB::raw($monthsWorkedExpr . ' as months_worked'),
                DB::raw('ISNULL(e.percentage, 0) as incentive_percentage'),
                DB::raw('ISNULL(e.cash_gift, 0) as cash_gift_incentive_base')
            )
            ->where([
                'active' => true,
                'is_employee' => true,
                'branch_id' => $branchId,
                'division_id' => $divisionId,
            ]);
        PayrollBenefitsEmployeeScope::apply($employees, 'a');
        $employees = $employees->get();

        if ($employees->isEmpty()) {
            return !$failIfNoEmployees;
        }

        // Authoritative days_present: work_hours>0, leave>0, is_ob, or holiday with absent_with_pay (same logic as getRecords).
        $employeeIds = $employees->pluck('id')->toArray();
        $daysPresentRows = DB::table('time_data as td')
            ->leftJoin('holidays as h', function ($j) {
                $j->on('td.holiday_id', '=', 'h.id')->where('td.is_holiday', '=', 1);
            })
            ->leftJoin('holiday_types as ht', 'h.holiday_type', '=', 'ht.id')
            ->whereRaw('YEAR(td.[date]) = ?', [$year])
            ->whereIn('td.employee_id', $employeeIds)
            ->groupBy('td.employee_id')
            ->select(
                'td.employee_id',
                DB::raw("COUNT(CASE
                    WHEN td.work_hours > 0 THEN 1
                    WHEN td.leave > 0 THEN 1
                    WHEN td.is_ob = 1 THEN 1
                    WHEN td.is_holiday = 1 AND ISNULL(ht.absent_with_pay, 0) = 1 THEN 1
                END) as days_present")
            )
            ->get();
        $daysPresentByEmployee = $daysPresentRows->keyBy('employee_id')->map(fn ($row) => (int) $row->days_present);

        // Prefetch months=0 row for employees with 0 days present (ensure they only get 0‑month tier)
        $yearendZeroRow = DB::table('yearend_table')->where('months', 0)->first();

        foreach ($employees as $emp) {
            // Use authoritative days_present; employees not in time_data for the year = 0 days
            $daysPresent = (int) ($daysPresentByEmployee->get($emp->id, 0));

            // Employees with 0 days present MUST use only the months=0 tier from yearend_table.
            if ($daysPresent === 0) {
                $percentage = $yearendZeroRow ? (float)($yearendZeroRow->percentage ?? 0) : 0.0;
                $cashGiftBase = $yearendZeroRow ? (float)($yearendZeroRow->cash_gift ?? 0) : 0.0;
            } else {
                $percentage = (float)($emp->incentive_percentage ?? 0);
                $percentage = max(0.0, min(1.0, $percentage));
                // Source of truth: yearend_table.cash_gift (new schema).
                // Fallback: request cash_gift (old behavior) only if table value is missing/0.
                $cashGiftBase = (float)($emp->cash_gift_incentive_base ?? 0);
                if ($cashGiftBase <= 0 && is_numeric($cashGift)) {
                    $cashGiftBase = (float)$cashGift;
                }
            }

            $isFullEligible = $daysPresent >= 120;
            $bonusAmount = $daysPresent >= 120 ? ((float)($emp->salary ?? 0) * 1.0) : 0.0;
            $cashGiftAmount = $cashGiftBase * $percentage;

            $yearend_bonus = array(
                'employee_id' => $emp->id,
                'branch_id' => $branchId,
                'department_id' => $divisionId,
                'years' => $year,
                'salary' => $emp->salary,
                'bonus_amount' => $bonusAmount,
                'cash_gift_incentive' => $cashGiftBase,
                'cash_gift_amount' => $cashGiftAmount
            );

            DB::table('yearend_bonus')->updateOrInsert([
                'employee_id' => $emp->id,
                'branch_id' => $branchId,
                'department_id' => $divisionId,
                'years' => $year
            ], $yearend_bonus);
        }

        $yearend_bonus_header = array(
            'branch_id' => $branchId,
            'department_id' => $divisionId,
            'year_id' => $year,
            'posted' => false
        );

        DB::table('yearend_bonus_header')->updateOrInsert([
            'branch_id' => $branchId,
            'department_id' => $divisionId,
            'year_id' => $year
        ], $yearend_bonus_header);

        return true;
    }

    public function post(Request $request)
    {
        try {
            $request->merge([
                'division_id' => $request->input('division_id', $request->input('department_id')),
            ]);

            $validator = validator(
                $request->all(),
                [
                    'branch_id' => 'nullable|integer|exists:branches,id',
                    'years' => 'required|integer|min:2000|max:2100',
                    'department_id' => 'required',
                ],
                [
                    'department_id.required' => 'Division is required.',
                    'years.required' => 'Year is required.',
                ],
                [
                    'department_id' => 'Division',
                    'years' => 'Year',
                ]
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse(
                    $validator->errors(),
                    'Please select Division and Year before posting.'
                );
            }

            if ($request->department_id === 'all') {
                return $this->errorResponse(
                    'Select a specific division before posting. Bulk post for "All" is not supported.',
                    400
                );
            }

            $divisionId = ReportDivisionFilter::resolveId($request);
            if (!$divisionId) {
                return $this->errorResponse('Please select a valid Division.', 400);
            }

            $branchId = $request->branch_id
                ?: $this->resolveBranchIdsForDivision($divisionId)->first();

            if (!$branchId) {
                return $this->errorResponse(
                    'Could not determine branch for the selected division. Select a branch or ensure employees have branch assignments.',
                    400
                );
            }

            $legacyDivisionIds = ReportDivisionFilter::legacyHeaderDepartmentIdsForDivision($divisionId);

            DB::table('yearend_bonus_header')->updateOrInsert(
                [
                    'year_id' => (int) $request->years,
                    'branch_id' => (int) $branchId,
                    'department_id' => (int) $divisionId,
                ],
                ['posted' => true]
            );

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Payroll Module',
                'menu' => 'Year End Bonus',
                'activity' => 'Posted',
                'description' => 'Posted Year End Bonus for ' . ReportDivisionFilter::displayName($divisionId) . ' (' . $request->years . ').',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Year-end bonus posted successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to post year-end bonus: ' . $e->getMessage());
        }
    }

    public function report(Request $request)
    {
        try {
            $divisions = DB::table('yearend_bonus as a')
                ->join('divisions as b', 'a.department_id', '=', 'b.id')
                ->select(
                    'a.department_id as id',
                    'b.name'
                )
                ->distinct()
                ->get();

            $years = DB::table('yearend_bonus as a')
                ->select(
                    'a.years'
                )
                ->distinct()
                ->orderBy('years', 'asc')
                ->get();

            $signatories = DB::table('payroll_signatories')
                ->where(['branch_id' => $request->branch_id, 'report_name' => "13th Month Pay"])
                ->get();

            return $this->successResponse([
                'divisions' => $divisions,
                'departments' => $divisions,
                'years' => $years,
                'signatories' => $signatories
            ], 'Year-end bonus report data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve year-end bonus report data: ' . $e->getMessage());
        }
    }

    public function generatePdf(Request $request)
    {
        try {
            $departmentOrDivision = $request->input('department_id', $request->input('division_id'));
            $request->merge([
                'department_id' => $departmentOrDivision,
                'division_id' => $departmentOrDivision,
            ]);
            $divisionId = ReportDivisionFilter::resolveId($request);

            $validator = validator(
                array_merge($request->all(), ['department_id' => $departmentOrDivision]),
                [
                    'department_id' => [
                        'required',
                        function ($attribute, $value, $fail) {
                            if ($value === 'all') {
                                return;
                            }
                            if (DB::table('divisions')->where('id', $value)->exists()) {
                                return;
                            }
                            if (DB::table('departments')->where('id', $value)->exists()) {
                                return;
                            }
                            $fail('Please select a valid Division.');
                        },
                    ],
                    'years' => 'required|integer|min:2000|max:2100',
                    'signatory_1' => 'nullable|string|max:255',
                    'signatory_position_1' => 'nullable|string|max:255',
                    'signatory_2' => 'nullable|string|max:255',
                    'signatory_position_2' => 'nullable|string|max:255',
                    'signatory_3' => 'nullable|string|max:255',
                    'signatory_position_3' => 'nullable|string|max:255',
                    'signatory_4' => 'nullable|string|max:255',
                    'signatory_position_4' => 'nullable|string|max:255',
                    'signatory_5' => 'nullable|string|max:255',
                    'signatory_position_5' => 'nullable|string|max:255',
                ],
                [
                    'department_id.required' => 'Division is required.',
                    'years.required' => 'Year is required.',
                    'years.integer' => 'Year must be a valid number.',
                ],
                [
                    'department_id' => 'Division',
                    'years' => 'Year',
                ]
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse(
                    $validator->errors(),
                    'Please complete all required fields before generating the report.'
                );
            }

            if (!$divisionId && $departmentOrDivision !== 'all') {
                return $this->validationErrorResponse(
                    ['division_id' => ['Please select a Division.']],
                    'Please select a Division before generating the report.'
                );
            }

            $app_key = env("APP_KEY", "");
            $legacyDivisionIds = $divisionId
                ? ReportDivisionFilter::legacyHeaderDepartmentIdsForDivision($divisionId)
                : [];
            $divisionName = $divisionId
                ? ReportDivisionFilter::displayName($divisionId)
                : 'All Divisions';

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

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $yearend = DB::table('yearend_bonus_header as a')
                ->leftjoin('yearend_bonus as b', 'a.id', '=', 'b.yearend_header_id')
                ->where('a.year_id', $request->years)
                ->when(!empty($legacyDivisionIds), function ($query) use ($legacyDivisionIds) {
                    return $query->whereIn('a.department_id', $legacyDivisionIds);
                })
                ->get();

            $employees = DB::table('yearend_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('divisions as c', 'c.id', '=', 'b.division_id')
                ->leftJoin('departments as dept', 'dept.id', '=', 'b.department_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.id',
                    'a.branch_id',
                    'b.photo',
                    'b.employee_no',
                    DB::raw('COALESCE(c.name, dept.name) as department'),
                    'd.name as position',
                    'a.salary',
                    'a.cash_gift_incentive',
                    'a.bonus_amount as amount',
                    'a.cash_gift_amount',
                    DB::raw("(isnull(a.bonus_amount,0) + isnull(a.cash_gift_amount,0)) as total_amount"),
                    DB::raw("CASE 
                WHEN ISNULL(b.is_encrypted,0) = 0 THEN 
                    CONCAT(b.last_name, ', ', b.first_name, ' ', LEFT(b.middle_name, 1)) 
                ELSE 
                    CONCAT(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')), ', ', 
                           RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')), ' ', 
                           LEFT(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key')), 1)) 
            END as name")
                )
                ->where('a.years', $request->years)
                ->where('b.active', true)
                ->where('b.is_employee', true)
                ->when(!empty($legacyDivisionIds), function ($query) use ($legacyDivisionIds, $divisionId) {
                    return $query->where(function ($q) use ($legacyDivisionIds, $divisionId) {
                        $q->whereIn('a.department_id', $legacyDivisionIds)
                            ->orWhere('b.division_id', $divisionId);
                    });
                })
                ->distinct()
                ->orderBy('name', 'asc')
                ->get();

            if ($employees->isEmpty()) {
                return $this->errorResponse(
                    'No year-end bonus records found for ' . $divisionName . ' and year ' . $request->years . '. '
                    . 'Process year-end bonus in Payroll Benefits for that division and year first.',
                    400
                );
            }

            $department = $divisionName;

            // Use requested year directly; avoid relying on header rows that may be missing.
            $month = $request->years;

            $pdf = PDF::loadView('process_bonus.yearend_report_print', compact('employees', 'department', 'yearend', 'signatories', 'image', 'month'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'landscape');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'yearend_bonus_report_' . $request->years . '_' . $department . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate year-end bonus PDF: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        // Keep the original print method for backward compatibility
        return $this->generatePdf($request);
    }
}
