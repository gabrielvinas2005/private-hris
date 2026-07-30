<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RetirementReminderNotification;
use Carbon\Carbon;
use App\User;

class CheckRetirementNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retirement:check {--force : Force re-send notifications even if already sent today} {--status : Show which users have retirement notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for employees approaching retirement and send notifications to HR and employees';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // If --status flag, show which users have notifications
        if ($this->option('status')) {
            return $this->showNotificationStatus();
        }

        $this->info('Checking for employees approaching retirement...');

        try {
            // Check if employees table exists and has birthdate column
            if (!Schema::hasTable('employees') || !Schema::hasColumn('employees', 'birthdate')) {
                $this->error('Employees table or birthdate column does not exist.');
                return 1;
            }

            // Check if retirement_notifications table exists
            if (!Schema::hasTable('retirement_notifications')) {
                $this->warn('retirement_notifications table does not exist. Please run migrations first.');
                return 1;
            }

            $today = Carbon::today();
            $app_key = env("APP_KEY", "");

            // Get all active employees
            $employees = DB::table('employees')
                ->where('active', true)
                ->where('is_employee', true)
                ->whereNotNull('birthdate')
                ->get();

            $notifiedCount = 0;

            foreach ($employees as $employee) {
                try {
                    if (empty($employee->birthdate)) {
                        continue;
                    }
                    $birthdate = Carbon::parse($employee->birthdate);
                    
                    // Check for OPTIONAL RETIREMENT (60 years old)
                    $optionalRetirementDate = $birthdate->copy()->addYears(60);
                    
                    // Skip if already past optional retirement
                    if (!$optionalRetirementDate->isPast()) {
                        // Calculate days until optional retirement
                        $daysUntilOptionalRetirement = $today->diffInDays($optionalRetirementDate, false);
                        
                        // Enhanced debug logging - show all employees near retirement window
                        if ($daysUntilOptionalRetirement <= 400) {
                            $this->line("DEBUG: Employee ID {$employee->id} - Birthdate: {$birthdate->format('Y-m-d')}, Optional Retirement: {$optionalRetirementDate->format('Y-m-d')}, Days: {$daysUntilOptionalRetirement} " . ($daysUntilOptionalRetirement >= 115 && $daysUntilOptionalRetirement <= 125 ? '✓ IN 4-MONTH WINDOW' : ''));
                        }
                        
                        // Debug logging for troubleshooting
                        if ($daysUntilOptionalRetirement >= 115 && $daysUntilOptionalRetirement <= 125) {
                            $this->line("DEBUG: Employee ID {$employee->id} - Birthdate: {$birthdate->format('Y-m-d')}, Optional Retirement: {$optionalRetirementDate->format('Y-m-d')}, Days: {$daysUntilOptionalRetirement}");
                        }

                        // Check for one year before optional retirement (365 days) - allow 1 day window
                        if ($daysUntilOptionalRetirement >= 364 && $daysUntilOptionalRetirement <= 366) {
                            $this->sendRetirementNotification($employee, $optionalRetirementDate, 'one_year_before', 'optional', $app_key);
                            $notifiedCount++;
                        }

                        // Check for four months before optional retirement (approximately 120 days) - allow 5 day window for flexibility
                        // Four months = approximately 120 days, but we allow 115-125 days to account for month length variations
                        if ($daysUntilOptionalRetirement >= 115 && $daysUntilOptionalRetirement <= 125) {
                            $this->sendRetirementNotification($employee, $optionalRetirementDate, 'four_months_before', 'optional', $app_key);
                            $notifiedCount++;
                        }
                    }
                    
                    // Check for MANDATORY RETIREMENT (65 years old)
                    $mandatoryRetirementDate = $birthdate->copy()->addYears(65);
                    
                    // Skip if already past mandatory retirement
                    if (!$mandatoryRetirementDate->isPast()) {
                        // Calculate days until mandatory retirement
                        $daysUntilMandatoryRetirement = $today->diffInDays($mandatoryRetirementDate, false);
                        
                        // Enhanced debug logging - show all employees near retirement window
                        if ($daysUntilMandatoryRetirement <= 400) {
                            $this->line("DEBUG: Employee ID {$employee->id} - Birthdate: {$birthdate->format('Y-m-d')}, Mandatory Retirement: {$mandatoryRetirementDate->format('Y-m-d')}, Days: {$daysUntilMandatoryRetirement} " . ($daysUntilMandatoryRetirement >= 115 && $daysUntilMandatoryRetirement <= 125 ? '✓ IN 4-MONTH WINDOW' : ''));
                        }
                        
                        // Debug logging for troubleshooting
                        if ($daysUntilMandatoryRetirement >= 115 && $daysUntilMandatoryRetirement <= 125) {
                            $this->line("DEBUG: Employee ID {$employee->id} - Birthdate: {$birthdate->format('Y-m-d')}, Mandatory Retirement: {$mandatoryRetirementDate->format('Y-m-d')}, Days: {$daysUntilMandatoryRetirement}");
                        }

                        // Check for one year before mandatory retirement (365 days) - allow 1 day window
                        if ($daysUntilMandatoryRetirement >= 364 && $daysUntilMandatoryRetirement <= 366) {
                            $this->sendRetirementNotification($employee, $mandatoryRetirementDate, 'one_year_before', 'mandatory', $app_key);
                            $notifiedCount++;
                        }

                        // Check for four months before mandatory retirement (approximately 120 days) - allow 5 day window for flexibility
                        // Four months = approximately 120 days, but we allow 115-125 days to account for month length variations
                        if ($daysUntilMandatoryRetirement >= 115 && $daysUntilMandatoryRetirement <= 125) {
                            $this->sendRetirementNotification($employee, $mandatoryRetirementDate, 'four_months_before', 'mandatory', $app_key);
                            $notifiedCount++;
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Error processing employee ID {$employee->id}: " . $e->getMessage());
                    continue;
                }
            }

            $this->info("Processed {$notifiedCount} retirement notification(s).");
            
            // Show summary for specific birthdate if found
            $testEmployee = $employees->first(function($emp) {
                return Carbon::parse($emp->birthdate)->format('Y-m-d') === '1961-09-06';
            });
            
            if ($testEmployee) {
                $testBirthdate = Carbon::parse($testEmployee->birthdate);
                $testMandatoryRetirement = $testBirthdate->copy()->addYears(65);
                $daysUntil = $today->diffInDays($testMandatoryRetirement, false);
                $this->line("");
                $this->line("=== SUMMARY FOR BIRTHDATE 1961-09-06 ===");
                $this->line("Employee ID: {$testEmployee->id}");
                $this->line("Birthdate: {$testBirthdate->format('Y-m-d')}");
                $this->line("Mandatory Retirement Date (65 years): {$testMandatoryRetirement->format('Y-m-d')}");
                $this->line("Days until retirement: {$daysUntil}");
                $this->line("4-month window (115-125 days): " . ($daysUntil >= 115 && $daysUntil <= 125 ? 'YES ✓' : 'NO ✗'));
                if ($daysUntil < 115) {
                    $this->line("→ Too soon! Need to wait " . (115 - $daysUntil) . " more days");
                } elseif ($daysUntil > 125) {
                    $this->line("→ Too late! Already passed by " . ($daysUntil - 125) . " days");
                }
                $this->line("=== END SUMMARY ===");
                $this->line("");
                $this->line("To trigger 4-month notification today, use birthdate: " . $today->copy()->addDays(120)->subYears(65)->format('Y-m-d'));
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error checking retirement notifications: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Get all HR users (users with with_hrm_access = 1)
     * This function scans ALL users in the users table and returns those with with_hrm_access = 1
     * 
     * @return \Illuminate\Support\Collection
     */
    private function getHrUsers()
    {
        // Get ALL users first to see what we're working with
        $allUsersCount = DB::table('users')->count();
        $this->line("Total users in database: {$allUsersCount}");
        
        // Build query to get ALL users with with_hrm_access = 1 (boolean true)
        // Check both integer 1 and boolean true values
        $hrUsersQuery = DB::table('users')
            ->where(function($query) {
                $query->where('with_hrm_access', 1)
                      ->orWhere('with_hrm_access', true)
                      ->orWhere('with_hrm_access', '1');
            });
        
        // Check if active column exists and filter by it
        if (Schema::hasColumn('users', 'active')) {
            $hrUsersQuery->where(function($query) {
                $query->where('active', true)
                      ->orWhere('active', 1)
                      ->orWhereNull('active'); // Include users where active is null
            });
        }
        
        // Get all HR users - NO LIMIT, get ALL of them
        $hrUsers = $hrUsersQuery->get();
        
        // Log all HR users found for debugging
        $this->line("Scanning users table for HR access (with_hrm_access = 1)...");
        $this->line("Found {$hrUsers->count()} user(s) with with_hrm_access = 1:");
        foreach ($hrUsers as $hrUser) {
            $employeeNo = $hrUser->employee_no ?? 'NULL';
            $withHrmAccess = $hrUser->with_hrm_access ?? 'NULL';
            $this->line("  → User ID: {$hrUser->id}, Email: {$hrUser->email}, Employee No: {$employeeNo}, with_hrm_access: {$withHrmAccess}");
        }
        
        return $hrUsers;
    }

    /**
* Send retirement notification to HR and employee
     * 
     * @param object $employee The employee record
     * @param Carbon $retirementDate The retirement date (60th or 65th birthday)
     * @param string $notificationType 'one_year_before' or 'four_months_before'
     * @param string $retirementType 'optional' (60 years) or 'mandatory' (65 years)
     * @param string $app_key Application key for decryption
     */
    private function sendRetirementNotification($employee, $retirementDate, $notificationType, $retirementType, $app_key)
    {
        try {
            // Check if notification already sent today for this type and retirement type (unless --force flag)
            if (!$this->option('force')) {
                // Build notification type key: e.g., 'one_year_before_optional' or 'four_months_before_mandatory'
                $notificationTypeKey = $notificationType . '_' . $retirementType;
                
                $existingNotification = DB::table('retirement_notifications')
                    ->where('employee_id', $employee->id)
                    ->where('notification_type', $notificationTypeKey)
                    ->whereDate('notification_date', Carbon::today())
                    ->first();

                if ($existingNotification) {
                    $this->line("Notification already sent today for employee ID {$employee->id} ({$notificationTypeKey})");
                    return;
                }
            }

            // Get employee name (handle encryption)
            $employeeName = $this->getEmployeeName($employee, $app_key);
            $employeeEmail = $this->getEmployeeEmail($employee, $app_key);

            // Get all HR users (users with with_hrm_access = 1)
            $hrUsers = $this->getHrUsers();

            if ($hrUsers->isEmpty()) {
                $this->warn("No HR users found with with_hrm_access = 1");
                return;
            }

            $this->line("Found {$hrUsers->count()} HR user(s) with with_hrm_access = 1");
            
            // Filter to only include HR users who have valid employee_no and matching employee records
            $validHrUsers = [];
            foreach ($hrUsers as $hrUser) {
                // Skip if no employee_no
                if (empty($hrUser->employee_no) || $hrUser->employee_no == '0') {
                    $this->warn("  - HR User ID: {$hrUser->id} has no employee_no - skipping");
                    continue;
                }
                
                // Check if employee record exists
                $employeeRecord = DB::table('employees')
                    ->where('employee_no', $hrUser->employee_no)
                    ->first();
                
                if ($employeeRecord) {
                    $validHrUsers[] = (object) array_merge((array) $hrUser, ['employee_id' => $employeeRecord->id]);
                    $this->line("  - HR User ID: {$hrUser->id}, Employee ID: {$employeeRecord->id}, Email: {$hrUser->email}");
                } else {
                    $this->warn("  - HR User ID: {$hrUser->id} has employee_no '{$hrUser->employee_no}' but no matching employee record - skipping");
                }
            }

            if (empty($validHrUsers)) {
                $this->warn("No HR users found with valid employee_no and employee records");
                return;
            }

            $this->line("Processing notifications for " . count($validHrUsers) . " valid HR user(s)");

            // Send notifications to HR personnel (only those with valid employee records)
            $hrNotificationSent = false;
            foreach ($validHrUsers as $hrUser) {
                $user = User::find($hrUser->id);
                if ($user) {
                    try {
                        $employeeId = $hrUser->employee_id; // Already validated above
                        $this->line("  → Processing notification for HR user ID: {$hrUser->id} (Employee ID: {$employeeId}, Email: {$hrUser->email})");
                        
                        // Try to send email notification (but don't fail if it doesn't work)
                        $emailSent = false;
                        if ($hrUser->email) {
                            try {
                                $user->notify(new RetirementReminderNotification(
                                    $employee,
                                    $employeeName,
                                    $retirementDate,
                                    $notificationType,
                                    'hr',
                                    $retirementType
                                ));
                                $emailSent = true;
                                $this->line("    ✓ Email sent successfully to HR user ID: {$hrUser->id}");
                                
                                // Add small delay to avoid email rate limiting (especially for Mailtrap)
                                usleep(500000); // 0.5 second delay between emails
                            } catch (\Exception $emailError) {
                                $this->warn("    ⚠ Email failed for HR user {$hrUser->id} (SMTP issue): " . $emailError->getMessage());
                                $this->line("    → Continuing with dashboard notification despite email failure");
                                // Continue - email failure is not critical, dashboard notification is primary
                            }
                        } else {
                            $this->warn("    ⚠ HR user ID {$hrUser->id} has no email address - skipping email, creating dashboard notification only");
                        }
                        
                        // Create dashboard notification (this is the primary notification method)
                        // Dashboard notification should always work, even if email fails
                        try {
                            $this->createDashboardNotification($employeeId, $employee, $employeeName, $retirementDate, $notificationType, $retirementType);
                            $hrNotificationSent = true; // Mark as sent if dashboard notification is created
                            $this->line("    ✓ Dashboard notification created for HR user ID: {$hrUser->id}");
                            $this->line("  → Successfully processed notification for HR user ID: {$hrUser->id} (Employee ID: {$employeeId})");
                        } catch (\Exception $dashboardError) {
                            $this->error("    ✗ Failed to create dashboard notification for HR user {$hrUser->id}: " . $dashboardError->getMessage());
                            // If dashboard fails, this is a real problem - but continue with other HR users
                        }
                    } catch (\Exception $e) {
                        $this->error("Failed to process notification for HR user {$hrUser->id}: " . $e->getMessage());
                        // Continue with other HR users even if one fails
                    }
                } else {
                    $this->warn("  → HR user ID {$hrUser->id} not found in User model - skipping");
                }
            }

            // Send notification to employee if they have a user account
            // Note: For the retiring employee, we use their own employee_id
            $employeeUserQuery = DB::table('users')
                ->where('employee_no', $employee->employee_no);
            
            // Check if active column exists
            if (Schema::hasColumn('users', 'active')) {
                $employeeUserQuery->where('active', true);
            }
            
            $employeeUser = $employeeUserQuery->first();
            $sentToEmployee = false;

            if ($employeeUser) {
                try {
                    $user = User::find($employeeUser->id);
                    if ($user) {
                        $this->line("  → Processing notification for employee: {$employeeName} (Employee ID: {$employee->id})");
                        
                        // Try to send email notification (but don't fail if it doesn't work)
                        $emailSent = false;
                        if ($employeeEmail) {
                            try {
                                $user->notify(new RetirementReminderNotification(
                                    $employee,
                                    $employeeName,
                                    $retirementDate,
                                    $notificationType,
                                    'employee',
                                    $retirementType
                                ));
                                $emailSent = true;
                                $this->line("    ✓ Email sent successfully to employee: {$employeeName}");
                            } catch (\Exception $emailError) {
                                $this->warn("    ⚠ Email failed for employee {$employee->id} (SMTP issue): " . $emailError->getMessage());
                                $this->line("    → Continuing with dashboard notification despite email failure");
                                // Continue - email failure is not critical, dashboard notification is primary
                            }
                        } else {
                            $this->warn("    ⚠ Employee {$employeeName} (ID: {$employee->id}) has no email address - skipping email, creating dashboard notification only");
                        }
                        
                        // Create dashboard notification (this is the primary notification method)
                        // Dashboard notification should always work, even if email fails
                        try {
                            $this->createDashboardNotification($employee->id, $employee, $employeeName, $retirementDate, $notificationType, $retirementType);
                            $sentToEmployee = true; // Mark as sent if dashboard notification is created
                            $this->line("    ✓ Dashboard notification created for employee: {$employeeName}");
                            $this->line("  → Successfully processed notification for employee: {$employeeName} (Employee ID: {$employee->id})");
                        } catch (\Exception $dashboardError) {
                            $this->error("    ✗ Failed to create dashboard notification for employee {$employee->id}: " . $dashboardError->getMessage());
                            // If dashboard fails, this is a real problem
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("  → Failed to process notification for employee {$employee->id}: " . $e->getMessage());
                }
            } else {
                $this->line("  → Employee {$employeeName} (ID: {$employee->id}) does not have a user account - notification not sent to employee");
            }

            // Record notification in database
            DB::table('retirement_notifications')->insert([
                'employee_id' => $employee->id,
                'notification_type' => $notificationType,
                'retirement_date' => $retirementDate->format('Y-m-d'),
                'notification_date' => Carbon::today(),
                'sent_to_hr' => $hrNotificationSent, // Only true if at least one HR notification was sent
                'sent_to_employee' => $sentToEmployee, // Only true if notification was actually sent
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info("Sent {$notificationType} notification for employee: {$employeeName} (ID: {$employee->id})");
        } catch (\Exception $e) {
            $this->error("Error sending notification for employee ID {$employee->id}: " . $e->getMessage());
        }
    }

    /**
     * Get employee name (handle encryption)
     */
    private function getEmployeeName($employee, $app_key)
    {
        try {
            if (isset($employee->is_encrypted) && $employee->is_encrypted == 1) {
                // Decrypt name fields using SQL Server function
                $firstNameResult = DB::selectOne("SELECT [dbo].[ufn_DecryptString](?, ?) as decrypted", [$employee->first_name ?? '', $app_key]);
                $firstName = $firstNameResult->decrypted ?? ($employee->first_name ?? '');
                
                $middleNameResult = DB::selectOne("SELECT [dbo].[ufn_DecryptString](?, ?) as decrypted", [$employee->middle_name ?? '', $app_key]);
                $middleName = $middleNameResult->decrypted ?? ($employee->middle_name ?? '');
                
                $lastNameResult = DB::selectOne("SELECT [dbo].[ufn_DecryptString](?, ?) as decrypted", [$employee->last_name ?? '', $app_key]);
                $lastName = $lastNameResult->decrypted ?? ($employee->last_name ?? '');
                
                // Get prefix and suffix
                $prefix = $employee->name_prefix_id ? DB::table('name_prefixes')->where('id', $employee->name_prefix_id)->value('name') : '';
                $suffix = $employee->name_suffix_id ? DB::table('name_suffixes')->where('id', $employee->name_suffix_id)->value('name') : '';
                
                return trim("{$prefix} {$firstName} {$middleName} {$lastName} {$suffix}");
            } else {
                $prefix = $employee->name_prefix_id ? DB::table('name_prefixes')->where('id', $employee->name_prefix_id)->value('name') : '';
                $suffix = $employee->name_suffix_id ? DB::table('name_suffixes')->where('id', $employee->name_suffix_id)->value('name') : '';
                
                return trim("{$prefix} " . ($employee->first_name ?? '') . " " . ($employee->middle_name ?? '') . " " . ($employee->last_name ?? '') . " {$suffix}");
            }
        } catch (\Exception $e) {
            // Fallback to unencrypted name if decryption fails
            $prefix = $employee->name_prefix_id ? DB::table('name_prefixes')->where('id', $employee->name_prefix_id)->value('name') : '';
            $suffix = $employee->name_suffix_id ? DB::table('name_suffixes')->where('id', $employee->name_suffix_id)->value('name') : '';
            return trim("{$prefix} " . ($employee->first_name ?? '') . " " . ($employee->middle_name ?? '') . " " . ($employee->last_name ?? '') . " {$suffix}");
        }
    }

    /**
     * Get employee email (handle encryption)
     */
    private function getEmployeeEmail($employee, $app_key)
    {
        try {
            if (isset($employee->is_encrypted) && $employee->is_encrypted == 1) {
                $result = DB::selectOne("SELECT [dbo].[ufn_DecryptString](?, ?) as decrypted", [$employee->email ?? '', $app_key]);
                return $result->decrypted ?? ($employee->email ?? null);
            }
            return $employee->email ?? null;
        } catch (\Exception $e) {
            // Fallback to unencrypted email if decryption fails
            return $employee->email ?? null;
        }
    }

    /**
     * Create dashboard notification
     */
    private function createDashboardNotification($employeeId, $employee, $employeeName, $retirementDate, $notificationType, $retirementType)
    {
        try {
            // Build notification type key for checking duplicates
            $notificationTypeKey = $notificationType . '_' . $retirementType;
            
            // Check if notification already exists for this employee, employee being notified about, and type today
            // Note: $employeeId is the HR employee's ID (from employees table), not user_id
            $existingNotification = DB::table('notifications')
                ->where('notifiable_type', 'App\User')
                ->where('notifiable_id', $employeeId)
                ->where('type', 'App\Notifications\RetirementReminderNotification')
                ->whereDate('created_at', Carbon::today())
                ->get()
                ->filter(function ($notification) use ($employee, $notificationTypeKey) {
                    $data = json_decode($notification->data, true);
                    return isset($data['employee_id']) && 
                           $data['employee_id'] == $employee->id && 
                           isset($data['notification_type']) && 
                           $data['notification_type'] == $notificationTypeKey;
                })
                ->first();

            if ($existingNotification) {
                $this->line("Dashboard notification already exists for employee ID {$employeeId}, retirement employee {$employee->id}, type {$notificationTypeKey}");
                return;
            }

            $retirementTypeLabel = $retirementType === 'optional' ? 'optional retirement age (60 years)' : 'mandatory retirement age (65 years)';
            $message = $notificationType === 'one_year_before' 
                ? "Employee {$employeeName} will reach {$retirementTypeLabel} in 1 year (Retirement Date: {$retirementDate->format('F d, Y')}). Please ensure all requirements are submitted."
                : "Employee {$employeeName} will reach {$retirementTypeLabel} in 4 months (Retirement Date: {$retirementDate->format('F d, Y')}). This is a follow-up reminder.";

            $notificationId = \Illuminate\Support\Str::uuid();
            
            DB::table('notifications')->insert([
                'id' => $notificationId,
                'type' => 'App\Notifications\RetirementReminderNotification',
                'notifiable_type' => 'App\User',
                'notifiable_id' => $employeeId, // This is now employee_id from employees table, not user_id
                'data' => json_encode([
                    'employee_id' => $employee->id,
                    'employee_name' => $employeeName,
                    'retirement_date' => $retirementDate->format('Y-m-d'),
                    'notification_type' => $notificationTypeKey,
                    'retirement_type' => $retirementType, // 'optional' or 'mandatory'
                    'message' => $message,
                    'title' => $notificationType === 'one_year_before' 
                        ? ($retirementType === 'optional' ? 'Optional Retirement Reminder - 1 Year Before (60 years)' : 'Mandatory Retirement Reminder - 1 Year Before (65 years)')
                        : ($retirementType === 'optional' ? 'Optional Retirement Reminder - 4 Months Before (60 years)' : 'Mandatory Retirement Reminder - 4 Months Before (65 years)'),
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->line("    ✓ Created dashboard notification ID: {$notificationId} for employee ID: {$employeeId}");
            $this->line("    → Notification details: notifiable_id={$employeeId}, employee_id={$employee->id}, type={$notificationTypeKey}");
        } catch (\Exception $e) {
            $this->error("Failed to create dashboard notification: " . $e->getMessage());
        }
    }

    /**
     * Show which users have retirement notifications
     */
    private function showNotificationStatus()
    {
        $this->info('Checking retirement notification status...');
        
        // Get all HR users using the helper function
        $hrUsers = $this->getHrUsers();
        
        $this->info("Found {$hrUsers->count()} HR user(s) with with_hrm_access = 1");
        $this->line('');
        
        // Check notifications for each HR user (using employee_id)
        foreach ($hrUsers as $hrUser) {
            // Get employee_id from employee_no
            $employeeId = null;
            if (!empty($hrUser->employee_no)) {
                $employeeRecord = DB::table('employees')
                    ->where('employee_no', $hrUser->employee_no)
                    ->first();
                
                if ($employeeRecord) {
                    $employeeId = $employeeRecord->id;
                }
            }
            
            $notificationCount = 0;
            $unreadCount = 0;
            
            if ($employeeId) {
                $notificationCount = DB::table('notifications')
                    ->where('notifiable_type', 'App\User')
                    ->where('notifiable_id', $employeeId)
                    ->where('type', 'App\Notifications\RetirementReminderNotification')
                    ->count();
                
                $unreadCount = DB::table('notifications')
                    ->where('notifiable_type', 'App\User')
                    ->where('notifiable_id', $employeeId)
                    ->where('type', 'App\Notifications\RetirementReminderNotification')
                    ->whereNull('read_at')
                    ->count();
            }
            
            $employeeInfo = $employeeId ? "Employee ID: {$employeeId}" : "No employee record";
            $this->line("User ID: {$hrUser->id} | {$employeeInfo} | Email: {$hrUser->email} | Total Notifications: {$notificationCount} | Unread: {$unreadCount}");
        }
        
        // Show total retirement notifications in database
        $totalNotifications = DB::table('notifications')
            ->where('type', 'App\Notifications\RetirementReminderNotification')
            ->count();
        
        $this->line('');
        $this->info("Total retirement notifications in database: {$totalNotifications}");
        
        return 0;
    }
}
