<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Traits\ApiResponse;

class LeaveCreditCardController extends Controller
{
    use ApiResponse;
    /**
     * Get all active leave types
     */
    public function getLeaveTypes()
    {
        $leaveTypes = DB::table('leave_types')
            ->where('active', 1)
            ->where('id', '<>', 13) // LWOP — not offered in leave credit card UI
            ->select(
                'id',
                'name',
                'active',
                'service_credit',
                'accrued_id',
                'accrual_amount',
                'accrual_frequency_id',
                'leave_balance_policy_id'
            )
            ->orderBy('id', 'asc')
            ->get();
            
        return $this->successResponse($leaveTypes, 'Leave types retrieved successfully');
    }

    /**
     * Build bulk current-month preview rows in one request.
     */
    public function bulkPreview(Request $request)
    {
        $year = (int) $request->input('year', Carbon::now()->year);
        $currentMonth = (int) $request->input('current_month', Carbon::now()->month);
        $employeeIds = $request->input('employee_ids', []);

        if (!is_array($employeeIds) || empty($employeeIds)) {
            return $this->errorResponse('employee_ids is required and must be a non-empty array', 422);
        }

        $employeeIds = array_values(array_filter(array_map('intval', $employeeIds), fn ($id) => $id > 0));
        if (empty($employeeIds)) {
            return $this->errorResponse('No valid employee IDs provided', 422);
        }

        $leaveTypes = DB::table('leave_types')
            ->where('active', 1)
            ->select(
                'id',
                'name'
            )
            ->orderBy('id', 'asc')
            ->get();

        if ($leaveTypes->isEmpty()) {
            return $this->successResponse([
                'rows' => [],
                'processed_employees' => 0,
                'requested_employees' => count($employeeIds),
            ], 'No active leave types found');
        }

        $rows = [];
        $processedEmployees = 0;

        foreach ($employeeIds as $employeeId) {
            $processedEmployees++;
            foreach ($leaveTypes as $leaveType) {
                try {
                    $detailResponse = $this->details($employeeId, new Request([
                        'year' => $year,
                        'leave_type_id' => $leaveType->id,
                    ]));

                    $decoded = $detailResponse->getData(true);
                    if (!($decoded['success'] ?? false)) {
                        continue;
                    }

                    $detailData = $decoded['data'] ?? [];
                    $beginningBalances = $detailData['beginning_balances'] ?? [];
                    if (!is_array($beginningBalances) || empty($beginningBalances)) {
                        continue;
                    }

                    $targetRow = null;
                    foreach ($beginningBalances as $row) {
                        if ((int)($row['month_id'] ?? 0) === $currentMonth) {
                            $targetRow = $row;
                            break;
                        }
                    }

                    if (!$targetRow) {
                        continue;
                    }

                    $rows[] = [
                        'employee_id' => $employeeId,
                        'leave_type_id' => (int)$leaveType->id,
                        'leave_type_name' => $leaveType->name,
                        'credit' => (float)($targetRow['earned'] ?? 0),
                    ];
                } catch (\Throwable $e) {
                    // Skip employee/leave type pair on error.
                    continue;
                }
            }
        }

        return $this->successResponse([
            'rows' => $rows,
            'processed_employees' => $processedEmployees,
            'requested_employees' => count($employeeIds),
        ], 'Bulk leave credit preview retrieved successfully');
    }

    public function index(Request $request)
    {
        $app_key = env("APP_KEY", "");

        // Get pagination and filter parameters
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $positionId = $request->get('position_id');
        $departmentId = $request->get('department_id');

        // Build the base query
        $query = DB::table('employees as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
            ->select(
                'a.id',
                'a.photo',
                'a.employee_no',
                'a.position_id',
                'a.department_id',
                // Original (decrypting) name expression kept for reference:
                // DB::raw("CASE 
                //     WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                //         CASE 
                //             WHEN ISNULL(a.middle_name,'') = '' THEN CONCAT(a.last_name, ', ', a.first_name)
                //             ELSE CONCAT(a.last_name, ', ', a.first_name, ' ', UPPER(SUBSTRING(a.middle_name, 1, 1)), '.')
                //         END
                //     ELSE
                //         CASE 
                //             WHEN ISNULL([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),'') = '' 
                //                 THEN CONCAT(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), ', ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')))
                //             ELSE CONCAT(
                //                 RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), ', ',
                //                 RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), ' ',
                //                 UPPER(SUBSTRING([dbo].[ufn_DecryptString](a.middle_name,'$app_key'), 1, 1)), '.'
                //             )
                //         END
                // END as name"),
                // Replacement (non-decrypting):
                DB::raw("CASE 
                    WHEN ISNULL(a.middle_name,'') = '' THEN CONCAT(a.last_name, ', ', a.first_name)
                    ELSE CONCAT(a.last_name, ', ', a.first_name, ' ', UPPER(SUBSTRING(a.middle_name, 1, 1)), '.')
                END as name"),
                'b.name as position',
                DB::raw('c.name as department_name'),
                DB::raw('c.name as department')
            )
            ->where([
                'a.active' => true,
                'a.is_employee' => true
            ]);

        // Add search functionality
        if (!empty($search)) {
            $query->where(function($q) use ($search, $app_key) {
                $q->where('a.employee_no', 'like', "%{$search}%")
                  // Original (decrypting) name search kept for reference:
                  // ->orWhereRaw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                  //                 CONCAT(a.first_name,' ',a.last_name)
                  //             ELSE
                  //                 RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                  //             END LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(a.first_name,' ',a.last_name) LIKE ?", ["%{$search}%"])
                  ->orWhere('b.name', 'like', "%{$search}%")
                  ->orWhere('c.name', 'like', "%{$search}%");
            });
        }

        // Add filter conditions
        if (!empty($positionId)) {
            $query->where('a.position_id', $positionId);
        }

        if (!empty($departmentId)) {
            $query->where('a.department_id', $departmentId);
        }

        // Get total count for pagination
        $total = $query->count();

        // Apply pagination
        // Default sort: last_name (not first_name). Name already starts with last_name,
        // but we also sort explicitly by last_name to be unambiguous for encrypted rows.
        // Original (decrypting) sort kept for reference:
        // $employees = $query->orderByRaw("CASE 
        //         WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name
        //         ELSE RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
        //     END ASC")
        $employees = $query->orderBy('a.last_name', 'asc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        // Calculate pagination metadata
        $lastPage = ceil($total / $perPage);
        $hasMorePages = $page < $lastPage;

        return $this->successResponse([
            'data' => $employees,
            'pagination' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'has_more_pages' => $hasMorePages,
                'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
                'to' => min($page * $perPage, $total)
            ]
        ], 'Leave credit card list retrieved successfully');
    }

    public function details($id, Request $request)
    {
        $app_key = env("APP_KEY", "");

        $year = (int) $request->get('year', Carbon::now()->format('Y'));
        $leaveTypeId = $request->get('leave_type_id');
        $employeeId = (int) $id;

        // Match index(): display name includes middle initial; include photo for credit card viewer
        $data = DB::table('employees as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
            ->select(
                'a.id',
                'a.photo',
                'a.employee_no',
                // Original (decrypting) name expression kept for reference:
                // DB::raw("CASE 
                //     WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                //         CASE 
                //             WHEN ISNULL(a.middle_name,'') = '' THEN CONCAT(a.last_name, ', ', a.first_name)
                //             ELSE CONCAT(a.last_name, ', ', a.first_name, ' ', UPPER(SUBSTRING(a.middle_name, 1, 1)), '.')
                //         END
                //     ELSE
                //         CASE 
                //             WHEN ISNULL([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),'') = '' 
                //                 THEN CONCAT(RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), ', ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')))
                //             ELSE CONCAT(
                //                 RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), ', ',
                //                 RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), ' ',
                //                 UPPER(SUBSTRING([dbo].[ufn_DecryptString](a.middle_name,'$app_key'), 1, 1)), '.'
                //             )
                //         END
                // END as name"),
                // Replacement (non-decrypting):
                DB::raw("CASE 
                    WHEN ISNULL(a.middle_name,'') = '' THEN CONCAT(a.last_name, ', ', a.first_name)
                    ELSE CONCAT(a.last_name, ', ', a.first_name, ' ', UPPER(SUBSTRING(a.middle_name, 1, 1)), '.')
                END as name"),
                'b.name as position',
                DB::raw('c.name as department_name'),
                DB::raw('c.name as department')
            )
            ->where('a.id', $id)
            ->get();

        $leave_credit_cards = DB::select("
        select
            CASE WHEN (CAST((DATEDIFF(DAY,
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2 - 1, 0)),
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2, 0))
                    )) as int) - (SUM(a.absent) + SUM(a.late) + SUM(a.undertime))) <= 0 THEN CAST(0 AS INT) 
                ELSE 
                    (CAST((DATEDIFF(DAY,
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2 - 1, 0)),
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2, 0))
                    )) as int) - (SUM(a.absent) + SUM(a.late) + SUM(a.undertime)))
                END as days_present,
            DATEPART(M,a.date) as month_id,
            DATEname(M,a.date) as month,
            SUM(a.leave) as leave,
            SUM(a.absent) as absent,
            SUM(a.late) as late,
            SUM(a.undertime) as undertime,
            isnull((select leave_earned from leave_earnings where days_present = (DATEDIFF(DAY,
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2 - 1, 0)),
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2, 0))
                    ) - (SUM(a.absent) + SUM(a.late) + SUM(a.undertime)))),0) as leave_earned
        from time_data a 
        where a.employee_id = $id and YEAR(a.date) = $year
        group by DATEPART(M,a.date),DATEname(M,a.date)
        order by DATEPART(M,a.date) asc
        ");

        // Merge time_data + time_data_adj for days present/absent (prefer time_data_adj when same date exists)
        $timeDataFromMain = DB::table('time_data')
            ->where('employee_id', $id)
            ->whereYear('date', $year)
            ->select('date', 'work_hours', 'late', 'undertime', 'absent', 'leave')
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($r) => \Carbon\Carbon::parse($r->date)->format('Y-m-d'));

        $timeDataByDate = $timeDataFromMain->toArray();

        if (Schema::hasTable('time_data_adj')) {
            $timeDataAdj = DB::table('time_data_adj')
                ->where('employee_id', $id)
                ->whereYear('date', $year)
                ->select('date', 'work_hours', 'late', 'undertime', 'absent', 'leave')
                ->orderBy('date')
                ->get();
            foreach ($timeDataAdj as $adj) {
                $dateKey = \Carbon\Carbon::parse($adj->date)->format('Y-m-d');
                $timeDataByDate[$dateKey] = $adj;
            }
        }

        $timeDataMerged = collect(array_values($timeDataByDate))->sortBy('date')->values()->toArray();

        // Get all active leave types if no specific leave_type_id is provided
        if (!$leaveTypeId) {
            // Get first active leave type as default
            $defaultLeaveType = DB::table('leave_types')
                ->where('active', 1)
                ->orderBy('id', 'asc')
                ->first();
            $leaveTypeId = $defaultLeaveType ? $defaultLeaveType->id : null;
        }
        
        if (!$leaveTypeId) {
            return $this->errorResponse('No active leave types found', 404);
        }
        
        // Get the leave type information
        $leaveType = DB::table('leave_types')
            ->where('id', $leaveTypeId)
            ->where('active', 1)
            ->first();
            
        if (!$leaveType) {
            return $this->errorResponse('Leave type not found or inactive', 404);
        }

        // SPECIAL CASE: Service-credit based leave types (e.g., CTO)
        // For these, balances come from overtime_applications instead of standard accruals.
        // Guard by BOTH service_credit flag and known CTO IDs to be robust against legacy data.
        if (intval($leaveType->service_credit ?? 0) === 1 || in_array((int)$leaveTypeId, [4, 10], true)) {
            $accrualData = $this->buildServiceCreditLeaveBalances($employeeId, $year, $leaveType);

            return $this->successResponse([
                'data' => $data,
                'leave_credit_cards' => $leave_credit_cards,
                'beginning_balances' => $accrualData,
                'leave_type' => [
                    'id' => $leaveType->id,
                    'name' => $leaveType->name,
                    'accrual_amount' => $leaveType->accrual_amount ?? 0,
                    'accrued_id' => $leaveType->accrued_id ?? null,
                ],
                'time_data' => $timeDataMerged
            ], 'Leave credit card data retrieved successfully');
        }
        
        // Get beginning balance from leave_beginning_balances table for the selected leave type
        $beginningBalanceRecord = DB::table('leave_beginning_balances')
            ->where('employee_id', $id)
            ->where('leave_type_id', $leaveTypeId)
            ->orderBy('year', 'asc')
            ->orderBy('month_id', 'asc')
            ->first();
            
        // Determine the starting point
        $startYear = null;
        $startMonth = null;
        $beginningBalance = 0;
        
        if ($beginningBalanceRecord) {
            $startYear = (int)$beginningBalanceRecord->year;
            $startMonth = (int)$beginningBalanceRecord->month_id;
            $beginningBalance = (float)$beginningBalanceRecord->balance_amount;
        }
        
        $accrualAmount = (float)($leaveType->accrual_amount ?? 0);
        $accrualFrequencyId = (int)($leaveType->accrual_frequency_id ?? 1); // 0=None, 1=Monthly, 2=Yearly
        $leaveBalancePolicyId = (int)($leaveType->leave_balance_policy_id ?? 0); // 1=Reset yearly, 3=Carry yearly
        $isResetYearlyPolicy = ($leaveBalancePolicyId === 1);
        $isNoAccrualFrequency = ($accrualFrequencyId === 0);
        $isYearlyAccrualFrequency = ($accrualFrequencyId === 2);
        
        // Calculate accrual for each month from the actual starting point
        $accrualData = [];
        $currentBalance = 0; // Track running balance month by month
        
        // If no beginning balance is set, return all zeros
        if (!$startYear || !$startMonth) {
            for ($month = 1; $month <= 12; $month++) {
                $monthDate = \Carbon\Carbon::create($year, $month, 1);
                $accrualData[] = [
                    'month_id' => $month,
                    'month' => $monthDate->format('F'),
                    'leave_type_id' => $leaveTypeId,
                    'leave_type_name' => $leaveType->name,
                    'balance_previous' => 0,
                    'accrued' => 0,
                    'earned' => 0,
                    'balance' => 0,
                    'with_pay' => 0,
                    'without_pay' => 0
                ];
            }
        } elseif ($startYear && $startMonth) {
            // If requested year is before the beginning balance year, return all zeros
            if ($year < $startYear) {
                for ($month = 1; $month <= 12; $month++) {
                    $monthDate = \Carbon\Carbon::create($year, $month, 1);
                    $accrualData[] = [
                        'month_id' => $month,
                        'month' => $monthDate->format('F'),
                        'leave_type_id' => $leaveTypeId,
                        'leave_type_name' => $leaveType->name,
                        'balance_previous' => 0,
                        'accrued' => 0,
                        'earned' => 0,
                        'balance' => 0,
                        'with_pay' => 0,
                        'without_pay' => 0
                    ];
                }
            } else {
            // Initialize balance for months before start
            if ($year == $startYear && $startMonth > 1) {
                // For months before start month, balance is 0
                for ($m = 1; $m < $startMonth; $m++) {
                    $currentBalance = 0;
                }
            }
            
            // If this is the start year and we're at or after start month, initialize with beginning balance
            if ($year == $startYear) {
                $currentBalance = $beginningBalance;
            } elseif ($year > $startYear) {
                if ($isResetYearlyPolicy) {
                    // Reset policy: do not carry previous year's ending balance.
                    // For yearly accrual types, start each year from the configured annual amount.
                    // For non-accrual/monthly types, start from zero and let monthly logic apply.
                    $currentBalance = $isYearlyAccrualFrequency ? $accrualAmount : 0;
                } else {
                    // Carry policy: compute balance at start of requested year from previous years.
                    $totalAccrualMonths = 0;
                    if ($startYear == $year - 1) {
                        // Previous year is the calculation start year
                        $monthsInStartYear = 12 - $startMonth + 1;
                        $totalAccrualMonths += $monthsInStartYear;
                    } else {
                        // Multiple years between start and current year
                        $monthsInStartYear = 12 - $startMonth + 1;
                        $totalAccrualMonths += $monthsInStartYear;
                        $fullYears = ($year - 1) - $startYear;
                        $totalAccrualMonths += ($fullYears * 12);
                    }
                    
                    $currentBalance = $beginningBalance + ($totalAccrualMonths * $accrualAmount);
                    
                    // Apply deductions from start year onwards
                    for ($prevYear = $startYear; $prevYear < $year; $prevYear++) {
                        $startMonthForYear = ($prevYear == $startYear) ? $startMonth : 1;
                        for ($prevMonth = $startMonthForYear; $prevMonth <= 12; $prevMonth++) {
                            $currentBalance = $this->deductLeaves($id, $prevYear, $prevMonth, $currentBalance, $leaveTypeId);
                        }
                    }
                }
            }
            
            // Generate accrual data for each month of the requested year
            for ($month = 1; $month <= 12; $month++) {
                $monthDate = \Carbon\Carbon::create($year, $month, 1);
                
                // Skip months before start month in start year
                if ($year == $startYear && $month < $startMonth) {
                    $accrualData[] = [
                        'month_id' => $month,
                        'month' => $monthDate->format('F'),
                        'leave_type_id' => $leaveTypeId,
                        'leave_type_name' => $leaveType->name,
                        'balance_previous' => 0,
                        'accrued' => 0,
                        'earned' => 0,
                        'balance' => 0,
                        'with_pay' => 0,
                        'without_pay' => 0
                    ];
                    continue;
                }
                
                // Balance from previous month (Balance - Earned previously)
                $balancePrevious = $currentBalance;
                    
                // Accrued amount for this month based on leave type frequency
                if ($isNoAccrualFrequency) {
                    $accrued = 0;
                } elseif ($isYearlyAccrualFrequency) {
                    $accrued = ($month === 1) ? $accrualAmount : 0;
                } else {
                    $accrued = $accrualAmount;
                }

                // Reset-yearly + yearly accrual: yearly grant is already set as January opening balance.
                // Keep January accrued at 0 to avoid double counting the annual grant.
                if ($isResetYearlyPolicy && $isYearlyAccrualFrequency && $month === 1 && $year > $startYear) {
                    $accrued = 0;
                }
                
                // Get leave deductions for this month (needed for Earned calculation)
                $leaveData = $this->getLeaveDeductions($id, $year, $month, $leaveTypeId);
                $taken = $leaveData['with_pay'];
                
                // Earned = (Balance - Taken) + Accrued
                $earned = ($balancePrevious - $taken) + $accrued;
                
                // Final balance: Earned minus without_pay (since with_pay is already accounted for in Earned)
                $withoutPay = $leaveData['without_pay'];
                $currentBalance = max(0, $earned - $withoutPay);
                
                $accrualData[] = [
                    'month_id' => $month,
                    'month' => $monthDate->format('F'),
                    'leave_type_id' => $leaveTypeId,
                    'leave_type_name' => $leaveType->name,
                    'balance_previous' => $balancePrevious, // Balance from previous month
                    'accrued' => $accrued, // Accrued amount for this month
                    'earned' => $earned, // Earned = (Balance - Taken) + Accrued
                    'balance' => $currentBalance, // Final balance after without_pay deductions
                    'with_pay' => $leaveData['with_pay'],
                    'without_pay' => $leaveData['without_pay']
                ];
            }
            }
        }
        // Note: If no beginning balance found, accrualData will be empty array from initialization

        return $this->successResponse([
            'data' => $data,
            'leave_credit_cards' => $leave_credit_cards,
            'beginning_balances' => $accrualData,
            'leave_type' => [
                'id' => $leaveType->id,
                'name' => $leaveType->name,
                'accrual_amount' => $accrualAmount
            ],
            'time_data' => $timeDataMerged
        ], 'Leave credit card data retrieved successfully');
    }

    public function getLeaveCredits(int $id, int $year)
    {
        $leave_credit_cards = DB::select("
        select
            CASE WHEN (CAST((DATEDIFF(DAY,
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2 - 1, 0)),
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2, 0))
                    )) as int) - (SUM(a.absent) + SUM(a.late) + SUM(a.undertime))) <= 0 THEN CAST(0 AS INT) 
                ELSE 
                    (CAST((DATEDIFF(DAY,
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2 - 1, 0)),
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2, 0))
                    )) as int) - (SUM(a.absent) + SUM(a.late) + SUM(a.undertime)))
                END as days_present,
            DATEPART(M,a.date) as month_id,
            DATEname(M,a.date) as month,
            SUM(a.leave) as leave,
            SUM(a.absent) as absent,
            SUM(a.late) as late,
            SUM(a.undertime) as undertime,
            isnull((select leave_earned from leave_earnings where days_present = (DATEDIFF(DAY,
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2 - 1, 0)),
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2, 0))
                    ) - (SUM(a.absent) + SUM(a.late) + SUM(a.undertime)))),0) as leave_earned
        from time_data a 
        where a.employee_id = $id and YEAR(a.date) = $year
        group by DATEPART(M,a.date),DATEname(M,a.date)
        order by DATEPART(M,a.date) asc
        ");

        // Get beginning balances from leave_credits table
        // Get the earliest credit record for each leave type (VL=16, SL=3, CTO=4)
        $vlCredit = DB::table('leave_credits')
            ->where('employee_id', $id)
            ->where('leave_type_id', 16) // Vacation Leave
            ->orderBy('created_at', 'asc')
            ->first();
            
        $slCredit = DB::table('leave_credits')
            ->where('employee_id', $id)
            ->where('leave_type_id', 3) // Sick Leave
            ->orderBy('created_at', 'asc')
            ->first();
            
        $ctoCredit = DB::table('leave_credits')
            ->where('employee_id', $id)
            ->where('leave_type_id', 4) // CTO Leave
            ->orderBy('created_at', 'asc')
            ->first();
        
        // Determine the starting point (earliest created_at date)
        $startDates = [];
        if ($vlCredit && $vlCredit->created_at) {
            $startDates[] = \Carbon\Carbon::parse($vlCredit->created_at);
        }
        if ($slCredit && $slCredit->created_at) {
            $startDates[] = \Carbon\Carbon::parse($slCredit->created_at);
        }
        if ($ctoCredit && $ctoCredit->created_at) {
            $startDates[] = \Carbon\Carbon::parse($ctoCredit->created_at);
        }
        
        // Find the earliest date
        $startDate = null;
        if (!empty($startDates)) {
            $startDate = $startDates[0];
            foreach ($startDates as $date) {
                if ($date->lt($startDate)) {
                    $startDate = $date;
                }
            }
        }
        
        $startYear = $startDate ? $startDate->year : null;
        $startMonth = $startDate ? $startDate->month : null;
        
        // Create a starting balances object similar to the old structure
        $startingBalances = (object)[
            'vl_balance' => $vlCredit ? (float)$vlCredit->credits : 0,
            'sl_balance' => $slCredit ? (float)$slCredit->credits : 0,
            'cto_balance' => $ctoCredit ? (float)$ctoCredit->credits : 0,
            'month_id' => $startMonth,
            'year' => $startYear
        ];
            
        // Get accrual amounts from leave types
        $leaveTypes = DB::table('leave_types')
            ->whereIn('id', [3, 4, 16]) // Sick Leave (3), CTO Leave (4), Vacation Leave (16)
            ->select('id', 'name', 'accrual_amount')
            ->get();
            
        // Extract accrual amounts
        $vlAccrual = $leaveTypes->where('id', 16)->first()->accrual_amount ?? 0; // Vacation Leave: 3.00
        $slAccrual = $leaveTypes->where('id', 3)->first()->accrual_amount ?? 0;  // Sick Leave: 1.25
        $ctoAccrual = $leaveTypes->where('id', 4)->first()->accrual_amount ?? 0; // CTO Leave: 0.00
        
        // Calculate accrual for each month from the actual starting point
        $accrualData = [];
        if ($startingBalances) {
            $startYear = $startingBalances->year;
            $startMonth = $startingBalances->month_id;
            $startDate = \Carbon\Carbon::create($startYear, $startMonth, 1);
            
            // Generate accrual data for each month of the requested year
            for ($month = 1; $month <= 12; $month++) {
                $monthDate = \Carbon\Carbon::create($year, $month, 1);
                
                if ($year == $startYear) {
                    // For the starting year, show beginning balance for months before start, then accrual from start month onwards
                    if ($month < $startMonth) {
                        // Months before starting point: show 0 (no balance yet)
                        $monthlyVlBalance = 0;
                        $monthlySlBalance = 0;
                        $monthlyCtoBalance = 0;
                    } elseif ($month == $startMonth) {
                        // Starting month: show the beginning balance
                        $monthlyVlBalance = $startingBalances->vl_balance;
                        $monthlySlBalance = $startingBalances->sl_balance;
                        $monthlyCtoBalance = $startingBalances->cto_balance;
                    } else {
                        // Months after starting point: add accrual for months from start month to current month
                        $monthsFromStart = $month - $startMonth;
                        $monthlyVlBalance = $startingBalances->vl_balance + ($monthsFromStart * $vlAccrual);
                        $monthlySlBalance = $startingBalances->sl_balance + ($monthsFromStart * $slAccrual);
                        $monthlyCtoBalance = $startingBalances->cto_balance + ($monthsFromStart * $ctoAccrual);
                    }
                } elseif ($year < $startYear) {
                    // For years before the starting year, show 0 for all months
                    $monthlyVlBalance = 0;
                    $monthlySlBalance = 0;
                    $monthlyCtoBalance = 0;
                } else {
                    // For years after the starting year, calculate total accrual from starting point to the end of previous year
                    $totalAccrualMonths = 0;
                    
                    // Calculate months from start month to end of start year (accrual starts from month after starting month)
                    $monthsInStartYear = 12 - $startMonth; // From month after start month to December
                    $totalAccrualMonths += $monthsInStartYear;
                    
                    // Add full years between start year and requested year
                    $fullYears = $year - $startYear - 1;
                    $totalAccrualMonths += ($fullYears * 12);
                    
                    // Calculate base balances with total accrual up to the start of the selected year
                    $baseVlBalance = $startingBalances->vl_balance + ($totalAccrualMonths * $vlAccrual);
                    $baseSlBalance = $startingBalances->sl_balance + ($totalAccrualMonths * $slAccrual);
                    $baseCtoBalance = $startingBalances->cto_balance + ($totalAccrualMonths * $ctoAccrual);
                    
                    // Add accrual for months from January to current month in the selected year
                    $monthlyVlBalance = $baseVlBalance + ($month * $vlAccrual);
                    $monthlySlBalance = $baseSlBalance + ($month * $slAccrual);
                    $monthlyCtoBalance = $baseCtoBalance + ($month * $ctoAccrual);
                }
                
                // Get leave deductions for this month
                $vlLeaveData = $this->getLeaveDeductions($id, $year, $month, 16); // Vacation Leave ID = 16
                $slLeaveData = $this->getLeaveDeductions($id, $year, $month, 3);  // Sick Leave ID = 3
                $ctoLeaveData = $this->getLeaveDeductions($id, $year, $month, 4);  // CTO Leave ID = 4
                
                // Deduct approved leaves for this month
                $monthlyVlBalance = $this->deductLeaves($id, $year, $month, $monthlyVlBalance, 16); // Vacation Leave ID = 16
                $monthlySlBalance = $this->deductLeaves($id, $year, $month, $monthlySlBalance, 3);  // Sick Leave ID = 3
                $monthlyCtoBalance = $this->deductLeaves($id, $year, $month, $monthlyCtoBalance, 4);  // CTO Leave ID = 4
                
                $accrualData[] = [
                    'month_id' => $month,
                    'month' => $monthDate->format('F'),
                    'vl_balance' => $monthlyVlBalance,
                    'sl_balance' => $monthlySlBalance,
                    'cto_balance' => $monthlyCtoBalance,
                    'vl_earned' => $vlAccrual,
                    'sl_earned' => $slAccrual,
                    'cto_earned' => $ctoAccrual,
                    'vl_with_pay' => $vlLeaveData['with_pay'],
                    'vl_without_pay' => $vlLeaveData['without_pay'],
                    'sl_with_pay' => $slLeaveData['with_pay'],
                    'sl_without_pay' => $slLeaveData['without_pay'],
                    'cto_with_pay' => $ctoLeaveData['with_pay'],
                    'cto_without_pay' => $ctoLeaveData['without_pay']
                ];
            }
        }

        return $this->successResponse([
            'data' => [
                'leave_credit_cards' => $leave_credit_cards,
                'beginning_balances' => $accrualData
            ]
        ], 'Leave credit card table retrieved successfully');
    }
    
    /**
     * Deduct approved leaves from the balance for a specific month
     */
    private function deductLeaves($employeeId, $year, $month, $currentBalance, $leaveTypeId)
    {
        // Get approved leaves for this employee, year, month, and leave type
        // Only leaves with approved_1 = 1, approved_2 = 1 AND (approved_3 = 1 OR approved_3 IS NULL)
        // are counted as taken leave. Exclude cancelled leaves (is_cancel, is_cancel_2, is_cancel_3 should all be 0).
        $approvedLeaves = DB::table('leave_headers as lh')
            ->join('leave_details as ld', 'lh.id', '=', 'ld.leave_id')
            ->where('lh.employee_id', $employeeId)
            ->where('lh.leave_type_id', $leaveTypeId)
            ->where('lh.approved', 1)
            ->where('lh.approved_2', 1)
            ->where(function($query) {
                $query->where('lh.approved_3', 1)->orWhereNull('lh.approved_3');
            })
            ->where(function($query) {
                $query->where('lh.disapproved', 0)->orWhereNull('lh.disapproved');
            })
            ->where(function($query) {
                $query->where('lh.disapproved_2', 0)->orWhereNull('lh.disapproved_2');
            })
            ->where(function($query) {
                $query->where('lh.disapproved_3', 0)->orWhereNull('lh.disapproved_3');
            })
            ->where(function($query) {
                $query->where('lh.is_cancel', 0)->orWhereNull('lh.is_cancel');
            })
            ->where(function($query) {
                $query->where('lh.is_cancel_2', 0)->orWhereNull('lh.is_cancel_2');
            })
            ->where(function($query) {
                $query->where('lh.is_cancel_3', 0)->orWhereNull('lh.is_cancel_3');
            })
            ->whereYear('ld.leave_date', $year)
            ->whereMonth('ld.leave_date', $month)
            ->select('ld.with_pay', 'ld.without_pay')
            ->get();

        $totalDeduction = 0;
        foreach ($approvedLeaves as $leave) {
            // Add both with_pay and without_pay days to the deduction
            $totalDeduction += ($leave->with_pay + $leave->without_pay);
        }
        
        // Deduct from balance (ensure it doesn't go below 0)
        $newBalance = max(0, $currentBalance - $totalDeduction);

        return $newBalance;
    }
    
    /**
     * Get leave deductions for a specific month and leave type
     */
    private function getLeaveDeductions($employeeId, $year, $month, $leaveTypeId)
    {
        $leaveTypeId = (int) $leaveTypeId;
        $monthStart = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // CTO (leave_type_id = 10): "Taken" from leave_headers date_from/date_to when leave_details may be missing
        if ($leaveTypeId === 10) {
            $headers = DB::table('leave_headers as lh')
                ->where('lh.employee_id', $employeeId)
                ->where('lh.leave_type_id', 10)
                ->where('lh.approved', 1)
                ->where('lh.approved_2', 1)
                ->where(function ($q) {
                    $q->where('lh.approved_3', 1)->orWhereNull('lh.approved_3');
                })
                ->where(function ($q) {
                    $q->where('lh.disapproved', 0)->orWhereNull('lh.disapproved');
                })
                ->where(function ($q) {
                    $q->where('lh.disapproved_2', 0)->orWhereNull('lh.disapproved_2');
                })
                ->where(function ($q) {
                    $q->where('lh.disapproved_3', 0)->orWhereNull('lh.disapproved_3');
                })
                ->where(function ($q) {
                    $q->where('lh.is_cancel', 0)->orWhereNull('lh.is_cancel');
                })
                ->where(function ($q) {
                    $q->where('lh.is_cancel_2', 0)->orWhereNull('lh.is_cancel_2');
                })
                ->where(function ($q) {
                    $q->where('lh.is_cancel_3', 0)->orWhereNull('lh.is_cancel_3');
                })
                ->where('lh.date_from', '<=', $monthEnd->format('Y-m-d'))
                ->where('lh.date_to', '>=', $monthStart->format('Y-m-d'))
                ->select('lh.date_from', 'lh.date_to')
                ->get();

            $totalWithPay = 0;
            foreach ($headers as $h) {
                $from = \Carbon\Carbon::parse($h->date_from)->startOfDay();
                $to = \Carbon\Carbon::parse($h->date_to)->startOfDay();
                $start = $from->copy()->max($monthStart);
                $end = $to->copy()->min($monthEnd);
                if ($start->lte($end)) {
                    $totalWithPay += $start->diffInDays($end) + 1;
                }
            }
            $totalWithoutPay = 0;
            return ['with_pay' => round((float) $totalWithPay, 3), 'without_pay' => round((float) $totalWithoutPay, 3)];
        }

        // Other leave types: from leave_details
        $approvedLeaves = DB::table('leave_headers as lh')
            ->join('leave_details as ld', 'lh.id', '=', 'ld.leave_id')
            ->where('lh.employee_id', $employeeId)
            ->where('lh.leave_type_id', $leaveTypeId)
            ->where('lh.approved', 1)
            ->where('lh.approved_2', 1)
            ->where(function($query) {
                $query->where('lh.approved_3', 1)->orWhereNull('lh.approved_3');
            })
            ->where(function($query) {
                $query->where('lh.disapproved', 0)->orWhereNull('lh.disapproved');
            })
            ->where(function($query) {
                $query->where('lh.disapproved_2', 0)->orWhereNull('lh.disapproved_2');
            })
            ->where(function($query) {
                $query->where('lh.disapproved_3', 0)->orWhereNull('lh.disapproved_3');
            })
            ->where(function($query) {
                $query->where('lh.is_cancel', 0)->orWhereNull('lh.is_cancel');
            })
            ->where(function($query) {
                $query->where('lh.is_cancel_2', 0)->orWhereNull('lh.is_cancel_2');
            })
            ->where(function($query) {
                $query->where('lh.is_cancel_3', 0)->orWhereNull('lh.is_cancel_3');
            })
            ->whereYear('ld.leave_date', $year)
            ->whereMonth('ld.leave_date', $month)
            ->select('ld.with_pay', 'ld.without_pay')
            ->get();

        $totalWithPay = 0;
        $totalWithoutPay = 0;
        
        foreach ($approvedLeaves as $leave) {
            $totalWithPay += $leave->with_pay;
            $totalWithoutPay += $leave->without_pay;
        }
        
        // CLIENT REQUIREMENT (2026-03): For Vacation Leave (leave_types.id = 1) only,
        // "Taken" must include:
        //   Approved leave of the month
        //   + SUM(late_offset) of the month (time_data + time_data_adj)
        //   + SUM(undertime_offset) of the month (time_data + time_data_adj)
        //
        // Keep the original offset logic commented for reference.
        // // For Vacation Leave, add offsets from time_data
        // // Check if this is Vacation Leave by querying the leave_types table
        // $leaveType = DB::table('leave_types')
        //     ->where('id', $leaveTypeId)
        //     ->where('name', 'Vacation Leave')
        //     ->first();
        //
        // if ($leaveType) {
        //     $offsetResult = DB::table('time_data')
        //         ->where('employee_id', $employeeId)
        //         ->whereYear('date', $year)
        //         ->whereMonth('date', $month)
        //         ->selectRaw('SUM(ISNULL(absent_offset, 0) + ISNULL(undertime_offset, 0) + ISNULL(late_offset, 0)) as total_offset')
        //         ->first();
        //
        //     $offsetSum = $offsetResult ? (float)($offsetResult->total_offset ?? 0) : 0;
        //     $totalWithPay += $offsetSum;
        // }

        // Apply new rule strictly for Vacation Leave (id = 1)
        $vlType = DB::table('leave_types')
            ->where('id', 1)
            ->where('active', 1)
            ->first();

        if ($vlType && (int)$leaveTypeId === 1) {
            // Use payroll periods (release_date) to align offsets with the "VL posting month"
            // instead of the raw time_data/time_data_adj dates.
            //
            // IMPORTANT: To match the UI and client expectations exactly, we:
            // - Compute per-record totals (late_offset + undertime_offset), rounded to 3 decimals
            // - Then sum those rounded per-record totals, instead of summing raw decimals first.

            // Main period offsets from time_data
            $offsetMainRows = DB::table('time_data as td')
                ->join('payroll_periods as pp', 'pp.id', '=', 'td.payroll_period_id')
                ->where('td.employee_id', $employeeId)
                ->whereYear('pp.release_date', $year)
                ->whereMonth('pp.release_date', $month)
                ->select('td.late_offset', 'td.undertime_offset')
                ->get();

            $offsetMainSum = 0.0;
            foreach ($offsetMainRows as $row) {
                $late = (float)($row->late_offset ?? 0);
                $undertime = (float)($row->undertime_offset ?? 0);
                $offsetMainSum += round($late + $undertime, 3);
            }

            // Preceding-period offsets from time_data_adj
            $offsetAdjSum = 0.0;
            if (Schema::hasTable('time_data_adj')) {
                $offsetAdjRows = DB::table('time_data_adj as tda')
                    ->join('payroll_periods as pp', 'pp.id', '=', 'tda.target_payroll_period_id')
                    ->where('tda.employee_id', $employeeId)
                    ->whereYear('pp.release_date', $year)
                    ->whereMonth('pp.release_date', $month)
                    ->select('tda.late_offset', 'tda.undertime_offset')
                    ->get();

                foreach ($offsetAdjRows as $row) {
                    $late = (float)($row->late_offset ?? 0);
                    $undertime = (float)($row->undertime_offset ?? 0);
                    $offsetAdjSum += round($late + $undertime, 3);
                }
            }

            $offsetTotal = $offsetMainSum + $offsetAdjSum;

            // Add offsets (late + undertime only) to with_pay taken for VL
            $totalWithPay += $offsetTotal;
        }
        
        return [
            'with_pay' => $totalWithPay,
            'without_pay' => $totalWithoutPay
        ];
    }

    /**
     * Build beginning_balances rows for service-credit-based leave types (e.g. CTO, leave_type_id = 10).
     * For these leave types, leave_types.accrual_amount = 0: there is no standard accrual.
     * Balance and "accrued" come only from overtime_applications.total_hours (service_credits = 1),
     * converted to days (8 hours = 1 day). leave_types.accrual_amount is never used here.
     * Only OT that has been approved (approved, approved_2, approved_3 or null) is counted.
     *
     * Behavior by accrued_id:
     *  - accrued_id = 1: no carry-over; each month's balance is only that month's approved CTO OT (total_hours/8).
     *  - accrued_id = 2: carry-over; running balance across months.
     */
    private function buildServiceCreditLeaveBalances(int $employeeId, int $year, $leaveType)
    {
        $leaveTypeId = (int)($leaveType->id ?? 0);
        $accruedId = (int)($leaveType->accrued_id ?? 0);

        $accrualData = [];
        $runningBalance = 0.0;

        for ($month = 1; $month <= 12; $month++) {
            $monthDate = \Carbon\Carbon::create($year, $month, 1);
            $monthStart = $monthDate->copy()->startOfMonth()->format('Y-m-d 00:00:00');
            $monthEnd = $monthDate->copy()->endOfMonth()->format('Y-m-d 23:59:59');

            // Sum total_hours for CTO/COC-type OT for this month: service_credits = 1.
            // Approval is dynamic: only require approval levels that are configured in approver_headers.
            $totalHours = (float) DB::table('overtime_applications as oa')
                ->where('oa.employee_id', $employeeId)
                ->whereBetween('oa.date', [$monthStart, $monthEnd])
                ->where('oa.service_credits', 1)
                ->where(function ($q) {
                    $q->where('oa.payroll', 0)->orWhereNull('oa.payroll');
                })
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('approver_details as ad')
                        ->join('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
                        ->whereColumn('ad.employee_id', 'oa.employee_id')
                        ->where(function ($qq) {
                            $qq->whereNull('ah.approver_id_1')->orWhere('oa.approved', 1);
                        })
                        ->where(function ($qq) {
                            $qq->whereNull('ah.approver_id_2')->orWhere('oa.approved_2', 1);
                        })
                        ->where(function ($qq) {
                            $qq->whereNull('ah.approver_id_3')->orWhere('oa.approved_3', 1);
                        });
                })
                ->where(function ($q) {
                    $q->where('oa.disapproved', 0)->orWhereNull('oa.disapproved');
                })
                ->where(function ($q) {
                    $q->where('oa.disapproved_2', 0)->orWhereNull('oa.disapproved_2');
                })
                ->where(function ($q) {
                    $q->where('oa.disapproved_3', 0)->orWhereNull('oa.disapproved_3');
                })
                ->where(function ($q) {
                    $q->where('oa.is_cancel', 0)->orWhereNull('oa.is_cancel');
                })
                ->sum('oa.total_hours');

            // Convert hours to days: 8 hours = 1 day (e.g. 4 hours = 0.5)
            $monthlyCtoFraction = round($totalHours / 8, 3);

            // Approved CTO leaves (taken) for this month
            $leaveData = $this->getLeaveDeductions($employeeId, $year, $month, $leaveTypeId);
            $taken = $leaveData['with_pay'];
            $withoutPay = $leaveData['without_pay'];

            // Accrued is only from leave_types.accrual_amount; for CTO it is 0 (no standard accrual).
            $accrued = (float) ($leaveType->accrual_amount ?? 0);

            if ($accruedId === 1) {
                // No carry-over: balance is based only on this month's CTO OT
                $balancePrevious = 0.0;
                $earned = ($balancePrevious - $taken) + $accrued + $monthlyCtoFraction;
                $balance = max(0, $earned - $withoutPay);

                // Do not carry balance to next month
                $runningBalance = 0.0;
            } else {
                // accrued_id != 1  => carry-over (running balance)
                $balancePrevious = $runningBalance;
                $earned = ($balancePrevious - $taken) + $accrued + $monthlyCtoFraction;
                $balance = max(0, $earned - $withoutPay);
                $runningBalance = $balance;
            }

            $accrualData[] = [
                'month_id' => $month,
                'month' => $monthDate->format('F'),
                'leave_type_id' => $leaveTypeId,
                'leave_type_name' => $leaveType->name,
                'balance_previous' => $balancePrevious,
                'accrued' => $accrued,
                'earned' => $earned,
                'balance' => $balance,
                'with_pay' => $taken,
                'without_pay' => $withoutPay,
            ];
        }

        return $accrualData;
    }
}
