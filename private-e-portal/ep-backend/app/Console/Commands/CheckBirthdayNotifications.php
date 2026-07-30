<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\User;
use App\Notifications\BirthdayMonthNotification;

class CheckBirthdayNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'birthday:check {--force : Force re-send birthday notifications for the current month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send birthday-of-the-month notifications to HR and employees (dashboard + email)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking birthday-of-the-month notifications...');

        // Ensure required tables exist
        if (!Schema::hasTable('employees') || !Schema::hasColumn('employees', 'birthdate')) {
            $this->error('employees table or birthdate column does not exist.');
            return 1;
        }

        if (!Schema::hasTable('birthday_notifications')) {
            $this->error('birthday_notifications table does not exist. Please run migrations first.');
            return 1;
        }

        $today = Carbon::today();
        $currentYear = (int) $today->format('Y');
        $currentMonth = (int) $today->format('m');
        $currentMonthName = $today->format('F');
        $app_key = env('APP_KEY', '');

        $this->line("Processing birthdays for {$currentMonthName} {$currentYear}.");

        // Get all active employees with a birthdate in the current month
        $employees = DB::table('employees')
            ->where('active', true)
            ->where('is_employee', true)
            ->whereNotNull('birthdate')
            ->whereMonth('birthdate', $currentMonth)
            ->get();

        if ($employees->isEmpty()) {
            $this->info('No employees with birthdays this month.');
            return 0;
        }

        $this->line("Found {$employees->count()} employee(s) with birthdays this month.");

        // Get HR users (re-use same criteria as retirement notifications: with_hrm_access = 1)
        $hrUsers = DB::table('users')
            ->where(function ($query) {
                $query->where('with_hrm_access', 1)
                    ->orWhere('with_hrm_access', true)
                    ->orWhere('with_hrm_access', '1');
            })
            ->get();

        if ($hrUsers->isEmpty()) {
            $this->warn('No HR users found with with_hrm_access = 1. Skipping HR notifications.');
        }

        // Map HR users to employee IDs (for dashboard notifications)
        $validHrUsers = [];
        foreach ($hrUsers as $hrUser) {
            if (empty($hrUser->employee_no) || $hrUser->employee_no === '0') {
                continue;
            }

            $employeeRecord = DB::table('employees')
                ->where('employee_no', $hrUser->employee_no)
                ->first();

            if ($employeeRecord) {
                $validHrUsers[] = (object) array_merge((array) $hrUser, ['employee_id' => $employeeRecord->id]);
            }
        }

        // We'll build a single summary message listing all celebrants for this month
        $celebrantNames = [];
        $firstEmployee = null;
        $notifiedCount = 0;

        foreach ($employees as $employee) {
            try {
                if (empty($employee->birthdate)) {
                    continue;
                }

                $birthdate = Carbon::parse($employee->birthdate);
                // Only month name, no exact day
                $birthMonth = $birthdate->format('F'); // e.g., "September"

                // Check if we've already sent birthday notification for this employee in this year+month
                if (!$this->option('force')) {
                    $existing = DB::table('birthday_notifications')
                        ->where('employee_id', $employee->id)
                        ->where('year', $currentYear)
                        ->where('month', $currentMonth)
                        ->first();

                    if ($existing) {
                        $this->line("Birthday notification already sent this month for employee ID {$employee->id} ({$birthMonthDay}).");
                        continue;
                    }
                }

                // Get proper employee name (handle encryption and prefixes/suffixes)
                $employeeName = $this->getEmployeeNameForBirthday($employee, $app_key);

                // Collect celebrant names for the monthly summary (skip empty names)
                if (!empty($employeeName)) {
                    $celebrantNames[] = $employeeName;
                    if ($firstEmployee === null) {
                        $firstEmployee = $employee;
                    }
                }

                // Record in birthday_notifications table
                DB::table('birthday_notifications')->insert([
                    'employee_id' => $employee->id,
                    'year' => $currentYear,
                    'month' => $currentMonth,
                    'notification_date' => $today->format('Y-m-d'),
                    // For monthly summary we treat HR/employee as notified when summary is sent;
                    // these flags mainly prevent re-processing within the month.
                    'sent_to_hr' => true,
                    'sent_to_employee' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $notifiedCount++;
            } catch (\Exception $e) {
                $this->error("Error processing birthday notification for employee ID {$employee->id}: " . $e->getMessage());
                continue;
            }
        }

        if (empty($celebrantNames) || $firstEmployee === null) {
            $this->info('No new birthday notifications to send this month.');
            return 0;
        }

        // Build summary string of all celebrants: "Employee1, Employee2"
        $namesSummary = implode(', ', $celebrantNames);

        // Send summary notification to HR (email + dashboard)
        foreach ($validHrUsers as $hrUser) {
            $userModel = User::find($hrUser->id);
            if (!$userModel) {
                continue;
            }

            // Email notification to HR
            try {
                $userModel->notify(new BirthdayMonthNotification(
                    $firstEmployee,
                    $namesSummary,
                    $currentMonthName,
                    'hr'
                ));
            } catch (\Exception $e) {
                $this->warn("Email to HR user ID {$hrUser->id} failed for birthday summary notification: " . $e->getMessage());
            }

            // Dashboard notification to HR (use HR employee_id)
            try {
                $this->createDashboardNotification(
                    $hrUser->employee_id,
                    $firstEmployee,
                    $namesSummary,
                    $currentMonthName,
                    'hr'
                );
            } catch (\Exception $e) {
                $this->error("Failed to create dashboard birthday summary notification for HR user ID {$hrUser->id}: " . $e->getMessage());
            }
        }

        // Dashboard notification for ALL active employees (everyone sees the monthly birthdays list)
        $allActiveEmployees = DB::table('employees')
            ->where('active', true)
            ->where('is_employee', true)
            ->get(['id']);

        foreach ($allActiveEmployees as $viewer) {
            try {
                $this->createDashboardNotification(
                    $viewer->id,
                    $firstEmployee,
                    $namesSummary,
                    $currentMonthName,
                    'employee'
                );
            } catch (\Exception $e) {
                // Log but do not stop other notifications
                $this->error("Failed to create dashboard birthday summary notification for viewer employee ID {$viewer->id}: " . $e->getMessage());
            }
        }

        $this->info("Finished sending birthday-of-the-month summary notification for {$notifiedCount} celebrant(s).");
        return 0;
    }

    /**
     * Create a dashboard notification record for birthday-of-the-month.
     *
     * @param int $notifiableEmployeeId The employee_id used as notifiable_id (HR or employee)
     * @param object $birthdayEmployee The employee whose birthday we are notifying about
     * @param string $employeeName
     * @param string $birthMonthDay   e.g. 'September 06'
     * @param string $recipientType   'hr' or 'employee'
     */
    private function createDashboardNotification($notifiableEmployeeId, $birthdayEmployee, $employeeName, $birthMonthDay, $recipientType)
    {
        // Avoid duplicate birthday notifications for the same employee/month/notifiable on the same day
        $existing = DB::table('notifications')
            ->where('notifiable_type', 'App\User')
            ->where('notifiable_id', $notifiableEmployeeId)
            ->where('type', 'App\Notifications\BirthdayMonthNotification')
            ->whereDate('created_at', Carbon::today())
            ->get()
            ->filter(function ($notification) use ($birthdayEmployee) {
                $data = json_decode($notification->data, true);
                return isset($data['employee_id']) && $data['employee_id'] == $birthdayEmployee->id;
            })
            ->first();

        if ($existing) {
            return;
        }

        $title = 'Birthday of the Month';
        // employeeName may be a single name or a comma-separated list
        $message = "{$employeeName} have birthdays this {$birthMonthDay}. Wish them a happy birthday!";

        $notificationId = \Illuminate\Support\Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'App\Notifications\BirthdayMonthNotification',
            'notifiable_type' => 'App\User',
            'notifiable_id' => $notifiableEmployeeId, // employee_id from employees table
            'data' => json_encode([
                'employee_id' => $birthdayEmployee->id,
                'employee_name' => $employeeName,
                'birth_month_day' => $birthMonthDay,
                'recipient_type' => $recipientType,
                'message' => $message,
                'title' => $title,
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get employee name for birthday notifications (handles encryption and prefixes/suffixes).
     * This is a simplified copy of the helper used in retirement notifications.
     */
    private function getEmployeeNameForBirthday($employee, $app_key)
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
            // Fallback to unencrypted/basic name if decryption fails or columns differ
            $prefix = $employee->name_prefix_id ? DB::table('name_prefixes')->where('id', $employee->name_prefix_id)->value('name') : '';
            $suffix = $employee->name_suffix_id ? DB::table('name_suffixes')->where('id', $employee->name_suffix_id)->value('name') : '';
            $first = $employee->first_name ?? ($employee->firstname ?? '');
            $middle = $employee->middle_name ?? ($employee->middlename ?? '');
            $last = $employee->last_name ?? ($employee->lastname ?? '');
            return trim("{$prefix} {$first} {$middle} {$last} {$suffix}");
        }
    }
}


