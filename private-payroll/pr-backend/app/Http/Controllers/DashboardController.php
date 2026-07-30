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

            if (!$employee) {
                return $this->notFoundResponse('Employee not found');
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

            // Get recent activity
            $recentActivity = $this->getRecentActivity($employeeId);

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
                $balance = $credit->credits - $credit->used;
                $totalBalance += $balance;
                
                $leaveTypes[] = [
                    'type' => $leaveType->name,
                    'credits' => $credit->credits,
                    'used' => $credit->used,
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
        // Count pending leave requests
        $pendingLeaves = DB::table('leave_applications')
            ->where('employee_id', $employeeId)
            ->where('status', 'Pending')
            ->count();

        // Count pending overtime requests
        $pendingOvertime = DB::table('overtime_applications')
            ->where('employee_id', $employeeId)
            ->where('status', 'Pending')
            ->count();

        // Count pending official business requests
        $pendingOfficialBusiness = DB::table('official_business_applications')
            ->where('employee_id', $employeeId)
            ->where('status', 'Pending')
            ->count();

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
        $currentMonth = date('Y-m');
        
        $overtimeHours = DB::table('overtime_applications')
            ->where('employee_id', $employeeId)
            ->where('status', 'Approved')
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$currentMonth])
            ->sum('hours');

        return round($overtimeHours, 1);
    }

    /**
     * Get recent activity for an employee
     */
    private function getRecentActivity($employeeId)
    {
        $activities = [];

        // Get recent leave activities
        $leaveActivities = DB::table('leave_applications as la')
            ->join('leave_types as lt', 'lt.id', '=', 'la.leave_type_id')
            ->where('la.employee_id', $employeeId)
            ->where('la.created_at', '>=', now()->subDays(30))
            ->orderBy('la.created_at', 'desc')
            ->limit(5)
            ->get(['la.*', 'lt.name as leave_type']);

        foreach ($leaveActivities as $leave) {
            $activities[] = [
                'type' => 'leave',
                'title' => "Leave request {$leave->status}",
                'description' => "{$leave->leave_type} for " . date('M d', strtotime($leave->date_from)) . " - " . date('M d', strtotime($leave->date_to)),
                'status' => $leave->status,
                'timestamp' => $leave->created_at,
                'time_ago' => $this->getTimeAgo($leave->created_at)
            ];
        }

        // Get recent overtime activities
        $overtimeActivities = DB::table('overtime_applications as oa')
            ->where('oa.employee_id', $employeeId)
            ->where('oa.created_at', '>=', now()->subDays(30))
            ->orderBy('oa.created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($overtimeActivities as $overtime) {
            $activities[] = [
                'type' => 'overtime',
                'title' => "Overtime request {$overtime->status}",
                'description' => "Overtime for " . date('M d', strtotime($overtime->date)),
                'status' => $overtime->status,
                'timestamp' => $overtime->created_at,
                'time_ago' => $this->getTimeAgo($overtime->created_at)
            ];
        }

        // Get recent official business activities
        $officialBusinessActivities = DB::table('official_business_applications as oba')
            ->where('oba.employee_id', $employeeId)
            ->where('oba.created_at', '>=', now()->subDays(30))
            ->orderBy('oba.created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($officialBusinessActivities as $ob) {
            $activities[] = [
                'type' => 'official_business',
                'title' => "Official business request {$ob->status}",
                'description' => "Official business for " . date('M d', strtotime($ob->date_from)) . " - " . date('M d', strtotime($ob->date_to)),
                'status' => $ob->status,
                'timestamp' => $ob->created_at,
                'time_ago' => $this->getTimeAgo($ob->created_at)
            ];
        }

        // Sort all activities by timestamp and limit to 10
        usort($activities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($activities, 0, 10);
    }

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
