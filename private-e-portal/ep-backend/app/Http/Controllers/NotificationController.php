<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get notifications for the authenticated user
     * GET /api/notifications
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Check if notifications table exists
            if (!Schema::hasTable('notifications')) {
                return $this->successResponse([], 'Notifications table not found');
            }

            // Get employee_id from user's employee_no
            $employeeId = null;
            if ($user && isset($user->employee_no) && !empty($user->employee_no)) {
                $employeeRecord = DB::table('employees')
                    ->where('employee_no', $user->employee_no)
                    ->first();
                
                if ($employeeRecord) {
                    $employeeId = $employeeRecord->id;
                } else {
                    \Log::warning('NotificationController: User has employee_no but no matching employee record', [
                        'user_id' => $user->id,
                        'employee_no' => $user->employee_no,
                    ]);
                }
            } else {
                \Log::warning('NotificationController: User has no employee_no', [
                    'user_id' => $user->id,
                    'email' => $user->email ?? 'N/A',
                ]);
            }

            // Get notifications for the current user's employee_id
            // Note: notifiable_id now stores employee_id (from employees table), not user_id
            // By default, only show non-archived notifications (unless 'show_archived' parameter is true)
            $showArchived = request()->input('show_archived', false);
            
            $notifications = collect([]);
            if ($employeeId) {
                $query = DB::table('notifications')
                    ->where('notifiable_type', 'App\User')
                    ->where('notifiable_id', $employeeId);
                
                // Filter out archived notifications unless explicitly requested
                if (!$showArchived) {
                    $query->whereNull('archived_at');
                }
                
                $notifications = $query->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get();
            }

            // Debug logging - also check all retirement notifications in DB
            $totalNotifications = DB::table('notifications')
                ->where('notifiable_type', 'App\User')
                ->count();
            
            // Get all retirement notifications to see what employee_ids they have
            $allRetirementNotifications = DB::table('notifications')
                ->where('type', 'App\Notifications\RetirementReminderNotification')
                ->where('notifiable_type', 'App\User')
                ->get();
            
            // Check for retirement notifications specifically
            $retirementNotifications = $notifications->filter(function($notification) {
                return strpos($notification->type, 'RetirementReminder') !== false;
            });

            // Get all retirement notification notifiable_ids for comparison
            $allRetirementNotifiableIds = $allRetirementNotifications->pluck('notifiable_id')->unique()->toArray();
            
            \Log::info('NotificationController@index', [
                'logged_in_user_id' => $user->id,
                'user_email' => $user->email ?? 'N/A',
                'user_employee_no' => $user->employee_no ?? 'N/A',
                'employee_id' => $employeeId ?? 'N/A',
                'notifications_for_current_employee' => $notifications->count(),
                'retirement_notifications_count' => $retirementNotifications->count(),
                'total_notifications_in_db' => $totalNotifications,
                'all_retirement_notifiable_ids' => $allRetirementNotifiableIds,
                'employee_id_in_list' => $employeeId ? in_array($employeeId, $allRetirementNotifiableIds) : false,
            ]);

            // Format notifications for frontend
            $formattedNotifications = $notifications->map(function ($notification) {
                // Parse JSON data from the database
                $data = json_decode($notification->data, true);
                
                // Handle case where data might be null or invalid JSON
                if (!is_array($data)) {
                    // If JSON decode failed, try to get raw data
                    $rawData = $notification->data;
                    \Log::warning('NotificationController: Failed to decode JSON for notification', [
                        'notification_id' => $notification->id,
                        'raw_data' => $rawData,
                    ]);
                    $data = [];
                }
                
                // Format notification for frontend
                return [
                    'id' => $notification->id,
                    'type' => $this->getNotificationType($notification->type),
                    'title' => $this->getNotificationTitle($notification->type, $data),
                    'message' => $data['message'] ?? ($data['title'] ?? 'New notification'),
                    'is_read' => !is_null($notification->read_at),
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'data' => $data,
                    'action_url' => $this->getActionUrl($notification->type, $data),
                ];
            })->values()->toArray(); // Convert to array

            // Log debug info for troubleshooting
            \Log::info('NotificationController@index response', [
                'logged_in_user_id' => $user->id,
                'user_employee_no' => $user->employee_no ?? 'N/A',
                'employee_id' => $employeeId ?? 'N/A',
                'notifications_found' => $notifications->count(),
                'formatted_count' => count($formattedNotifications),
            ]);
            
            return $this->successResponse($formattedNotifications, 'Notifications retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('NotificationController@index error: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve notifications: ' . $e->getMessage());
        }
    }

    /**
     * Mark notification as read
     * POST /api/notifications/{id}/read
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Get employee_id from user's employee_no
            $employeeId = null;
            if ($user && isset($user->employee_no) && !empty($user->employee_no)) {
                $employeeRecord = DB::table('employees')
                    ->where('employee_no', $user->employee_no)
                    ->first();
                
                if ($employeeRecord) {
                    $employeeId = $employeeRecord->id;
                }
            }

            if (!$employeeId) {
                return $this->errorResponse('Employee record not found for user', 404);
            }

            // Update notification read_at (using employee_id)
            $updated = DB::table('notifications')
                ->where('id', $id)
                ->where('notifiable_type', 'App\User')
                ->where('notifiable_id', $employeeId)
                ->update(['read_at' => now()]);

            if ($updated) {
                return $this->successResponse(null, 'Notification marked as read');
            }

            return $this->errorResponse('Notification not found', 404);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to mark notification as read: ' . $e->getMessage());
        }
    }

    /**
     * Mark all notifications as read
     * POST /api/notifications/read-all
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Get employee_id from user's employee_no
            $employeeId = null;
            if ($user && isset($user->employee_no) && !empty($user->employee_no)) {
                $employeeRecord = DB::table('employees')
                    ->where('employee_no', $user->employee_no)
                    ->first();
                
                if ($employeeRecord) {
                    $employeeId = $employeeRecord->id;
                }
            }

            if (!$employeeId) {
                return $this->errorResponse('Employee record not found for user', 404);
            }

            // Update all unread notifications (using employee_id)
            $updated = DB::table('notifications')
                ->where('notifiable_type', 'App\User')
                ->where('notifiable_id', $employeeId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return $this->successResponse(['updated' => $updated], 'All notifications marked as read');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to mark all notifications as read: ' . $e->getMessage());
        }
    }

    /**
     * Archive a notification
     * POST /api/notifications/{id}/archive
     */
    public function archive($id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Get employee_id from user's employee_no
            $employeeId = null;
            if ($user && isset($user->employee_no) && !empty($user->employee_no)) {
                $employeeRecord = DB::table('employees')
                    ->where('employee_no', $user->employee_no)
                    ->first();
                
                if ($employeeRecord) {
                    $employeeId = $employeeRecord->id;
                }
            }

            if (!$employeeId) {
                return $this->errorResponse('Employee record not found for user', 404);
            }

            // Update notification (using employee_id)
            $updated = DB::table('notifications')
                ->where('id', $id)
                ->where('notifiable_type', 'App\User')
                ->where('notifiable_id', $employeeId)
                ->update(['archived_at' => now()]);

            if ($updated) {
                return $this->successResponse(['archived' => true], 'Notification archived successfully');
            }

            return $this->errorResponse('Notification not found', 404);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to archive notification: ' . $e->getMessage());
        }
    }

    /**
     * Archive all read notifications
     * POST /api/notifications/archive-all-read
     */
    public function archiveAllRead()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Get employee_id from user's employee_no
            $employeeId = null;
            if ($user && isset($user->employee_no) && !empty($user->employee_no)) {
                $employeeRecord = DB::table('employees')
                    ->where('employee_no', $user->employee_no)
                    ->first();
                
                if ($employeeRecord) {
                    $employeeId = $employeeRecord->id;
                }
            }

            if (!$employeeId) {
                return $this->errorResponse('Employee record not found for user', 404);
            }

            // Archive all read notifications (using employee_id)
            $updated = DB::table('notifications')
                ->where('notifiable_type', 'App\User')
                ->where('notifiable_id', $employeeId)
                ->whereNotNull('read_at')
                ->whereNull('archived_at')
                ->update(['archived_at' => now()]);

            return $this->successResponse(['archived' => $updated], 'All read notifications archived');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to archive notifications: ' . $e->getMessage());
        }
    }

    /**
     * Get notification type for frontend
     */
    private function getNotificationType($type)
    {
        if (strpos($type, 'RetirementReminder') !== false) {
            return 'retirement_reminder';
        }
        if (strpos($type, 'BirthdayMonthNotification') !== false) {
            return 'birthday_month';
        }
        if (strpos($type, 'ServiceMilestoneNotification') !== false) {
            return 'service_milestone';
        }
        if (strpos($type, 'CosContractExpirationNotification') !== false) {
            return 'cos_contract_expiration';
        }
        return 'announcement';
    }

    /**
     * Get notification title
     */
    private function getNotificationTitle($type, $data)
    {
        if (strpos($type, 'RetirementReminder') !== false) {
            // Use the title from data if available (it's more specific and includes retirement type)
            if (isset($data['title']) && !empty($data['title'])) {
                return $data['title'];
            }
            // Fallback to notification_type parsing
            $notificationType = $data['notification_type'] ?? '';
            if (strpos($notificationType, 'one_year_before') !== false) {
                return 'Retirement Reminder - 1 Year Before';
            } elseif (strpos($notificationType, 'four_months_before') !== false) {
                return 'Retirement Reminder - 4 Months Before';
            }
            return 'Retirement Reminder';
        }

        if (strpos($type, 'BirthdayMonthNotification') !== false) {
            return $data['title'] ?? 'Birthday of the Month';
        }

        return $data['title'] ?? 'Notification';
    }

    /**
     * Get action URL for notification
     */
    private function getActionUrl($type, $data)
    {
        if (strpos($type, 'RetirementReminder') !== false) {
            // Could link to employee profile or retirement page
            return null; // or '/employees/' . ($data['employee_id'] ?? '');
        }
        return $data['action_url'] ?? null;
    }
}
