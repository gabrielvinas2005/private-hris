<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get dashboard data for a user
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($id)
    {
        try {
            // Get employee ID from user
            $employee = DB::table('users as u')
                ->join('employees as e', 'e.employee_no', '=', 'u.employee_no')
                ->where('u.id', $id)
                ->select('e.id as employee_id', 'e.employee_no')
                ->first();

            // Debug: Log the query result
            \Log::info('Dashboard query for user ID: ' . $id, [
                'employee_found' => $employee ? 'yes' : 'no',
                'employee_data' => $employee
            ]);

            if (!$employee) {
                return $this->notFoundResponse('Employee not found. Please ensure your user account is properly linked to an employee record.');
            }

            $employeeId = $employee->employee_id;

            // Get leave balance
            $leaveBalance = $this->getLeaveBalance($employeeId);

            // Get pending requests count
            $pendingRequests = $this->getPendingRequests($employeeId);

            // Get work hours (placeholder for now)
            $workHours = $this->getWorkHours($employeeId);

            // Get overtime hours this month
            $overtimeHours = $this->getOvertimeHours($employeeId);

            // Recent activity disabled per request
            $recentActivity = [];

            return $this->successResponse([
                'leave_balance' => $leaveBalance,
                'pending_requests' => $pendingRequests,
                'work_hours' => $workHours,
                'overtime_hours' => $overtimeHours,
                'recent_activity' => $recentActivity
            ], 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load dashboard data: ' . $e->getMessage());
        }
    }

    /**
     * Get leave balance for an employee
     */
    private function getLeaveBalance($employeeId)
    {
        // Some databases may not have a 'year' column; compute from balances instead
        $leaveCredits = DB::table('leave_credits')
            ->where('employee_id', $employeeId)
            ->get();

        $totalBalance = 0;
        $leaveTypes = [];

        foreach ($leaveCredits as $credit) {
            $leaveType = DB::table('leave_types')
                ->where('id', $credit->leave_type_id)
                ->first();

            if ($leaveType) {
                // Calculate used credits from leave applications
                // Note: Current schema shows `leave_details` with columns: id, leave_id, leave_date, with_pay, without_pay
                // and no direct `days` or `leave_type_id` fields. To avoid invalid-column errors, default to zero for now.
                $usedCredits = 0;

                $balance = $credit->credits - $usedCredits;
                $totalBalance += $balance;

                $leaveTypes[] = [
                    'type' => $leaveType->name,
                    'credits' => $credit->credits,
                    'used' => $usedCredits,
                    'balance' => $balance
                ];
            }
        }

        return [
            'total_balance' => round($totalBalance, 1),
            'leave_types' => $leaveTypes
        ];
    }

    /**
     * Get pending requests count for an employee
     */
    private function getPendingRequests($employeeId)
    {
        // Count pending leave requests using leave_headers bit flags
        // Pending = not approved, not disapproved, not canceled
        $pendingLeaves = DB::table('leave_headers as lh')
            ->where('lh.employee_id', $employeeId)
            ->where(function ($q) {
                $q->whereNull('lh.approved')->orWhere('lh.approved', 0);
            })
            ->where(function ($q) {
                $q->whereNull('lh.disapproved')->orWhere('lh.disapproved', 0);
            })
            ->where(function ($q) {
                $q->whereNull('lh.is_cancel')->orWhere('lh.is_cancel', 0);
            })
            ->count();

        // Count pending overtime requests (keep existing table if present; fallback to 0 if missing)
        try {
            $pendingOvertime = DB::table('overtime_applications')
                ->where('employee_id', $employeeId)
                ->where('status', 'Pending')
                ->count();
        } catch (\Throwable $e) {
            $pendingOvertime = 0;
        }

        // Count pending official business requests (fallback safe)
        try {
            $pendingOfficialBusiness = DB::table('official_business_applications')
                ->where('employee_id', $employeeId)
                ->where('status', 'Pending')
                ->count();
        } catch (\Throwable $e) {
            $pendingOfficialBusiness = 0;
        }

        $total = $pendingLeaves + $pendingOvertime + $pendingOfficialBusiness;

        return [
            'total' => $total,
            'leaves' => $pendingLeaves,
            'overtime' => $pendingOvertime,
            'official_business' => $pendingOfficialBusiness
        ];
    }

    /**
     * Get work hours for today (placeholder)
     */
    private function getWorkHours($employeeId)
    {
        // This is a placeholder - you can implement actual DTR logic here
        return [
            'hours_today' => 8.5,
            'status' => 'Working'
        ];
    }

    /**
     * Get overtime hours for current month
     */
    private function getOvertimeHours($employeeId)
    {
        // Use start/end of current month (SQL Server compatible)
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');

        try {
            $overtimeHours = DB::table('overtime_applications')
                ->where('employee_id', $employeeId)
                ->where('status', 'Approved')
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->sum('hours');
        } catch (\Throwable $e) {
            // Table may not exist in some environments
            $overtimeHours = 0;
        }

        return round($overtimeHours, 1);
    }

    // recent activity function removed per request

    /**
     * Get human readable time ago
     */
    private function getTimeAgo($timestamp)
    {
        $time = strtotime($timestamp);
        $now = time();
        $diff = $now - $time;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    }
}
