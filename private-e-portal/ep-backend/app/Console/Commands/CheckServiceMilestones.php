<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CheckServiceMilestones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service-milestones:check {--force : Force re-send service milestone notifications today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send 5-year service milestone notifications (dashboard only) to HR for employees based on date_hired';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking for employee service milestones...');

        // Ensure required tables/columns exist
        if (!Schema::hasTable('employees') || !Schema::hasColumn('employees', 'date_hired')) {
            $this->error('employees table or date_hired column does not exist.');
            return 1;
        }

        if (!Schema::hasTable('service_milestone_notifications')) {
            $this->error('service_milestone_notifications table does not exist. Please run migrations first.');
            return 1;
        }

        $today = Carbon::today();

        // Get all active employees with a hire date
        $employees = DB::table('employees')
            ->where('active', true)
            ->where('is_employee', true)
            ->whereNotNull('date_hired')
            ->get();

        if ($employees->isEmpty()) {
            $this->info('No active employees with date_hired found.');
            return 0;
        }

        $this->line("Found {$employees->count()} active employee(s) with date_hired.");

        // Get HR users (same logic as retirement notifications)
        $hrUsers = $this->getHrUsers();
        if ($hrUsers->isEmpty()) {
            $this->warn('No HR users found with with_hrm_access = 1. Skipping milestone notifications.');
            return 0;
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

        if (empty($validHrUsers)) {
            $this->warn('No HR users with valid employee records found. Skipping milestone notifications.');
            return 0;
        }

        $notifiedCount = 0;

        foreach ($employees as $employee) {
            try {
                if (empty($employee->date_hired)) {
                    continue;
                }

                $dateHired = Carbon::parse($employee->date_hired);

                // Only consider employees hired on or before today
                if ($dateHired->isFuture()) {
                    continue;
                }

                // Full years of service as of today
                $yearsOfService = $dateHired->diffInYears($today);

                // We only care about exact 5-year milestones: 5, 10, 15, ...
                if ($yearsOfService < 5 || $yearsOfService % 5 !== 0) {
                    continue;
                }

                // Ensure today is the exact anniversary date (match month/day)
                if ($today->format('m-d') !== $dateHired->format('m-d')) {
                    continue;
                }

                // Check if notification already sent for this employee & milestone this year
                if (!$this->option('force')) {
                    $existing = DB::table('service_milestone_notifications')
                        ->where('employee_id', $employee->id)
                        ->where('milestone_years', $yearsOfService)
                        ->whereDate('notification_date', $today->format('Y-m-d'))
                        ->first();

                    if ($existing) {
                        $this->line("Service milestone notification already sent today for employee ID {$employee->id} ({$yearsOfService} years).");
                        continue;
                    }
                }

                $employeeName = $this->buildEmployeeName($employee);

                // Create dashboard notifications for all HR users (no email)
                foreach ($validHrUsers as $hrUser) {
                    try {
                        $this->createDashboardNotificationForMilestone(
                            $hrUser->employee_id,
                            $employee,
                            $employeeName,
                            $yearsOfService,
                            $today
                        );
                    } catch (\Exception $e) {
                        $this->error("Failed to create service milestone notification for HR user ID {$hrUser->id}: " . $e->getMessage());
                    }
                }

                // Record in service_milestone_notifications table
                DB::table('service_milestone_notifications')->insert([
                    'employee_id' => $employee->id,
                    'milestone_years' => $yearsOfService,
                    'notification_date' => $today->format('Y-m-d'),
                    'sent_to_hr' => true,
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $notifiedCount++;
                $this->info("Created {$yearsOfService}-year service milestone notification for employee: {$employeeName} (ID: {$employee->id}).");

            } catch (\Exception $e) {
                $this->error("Error processing service milestone for employee ID {$employee->id}: " . $e->getMessage());
                continue;
            }
        }

        $this->info("Finished processing {$notifiedCount} service milestone notification(s).");
        return 0;
    }

    /**
     * Get all HR users (same logic as in CheckRetirementNotifications).
     *
     * @return \Illuminate\Support\Collection
     */
    private function getHrUsers()
    {
        $allUsersCount = DB::table('users')->count();
        $this->line("Total users in database: {$allUsersCount}");

        $hrUsersQuery = DB::table('users')
            ->where(function ($query) {
                $query->where('with_hrm_access', 1)
                    ->orWhere('with_hrm_access', true)
                    ->orWhere('with_hrm_access', '1');
            });

        if (Schema::hasColumn('users', 'active')) {
            $hrUsersQuery->where(function ($query) {
                $query->where('active', true)
                    ->orWhere('active', 1)
                    ->orWhereNull('active');
            });
        }

        $hrUsers = $hrUsersQuery->get();

        $this->line("Found {$hrUsers->count()} user(s) with with_hrm_access = 1 (potential HR).");

        return $hrUsers;
    }

    /**
     * Build a simple employee name (no encryption handling needed for this use-case).
     */
    private function buildEmployeeName($employee)
    {
        $first = $employee->first_name ?? ($employee->firstname ?? '');
        $middle = $employee->middle_name ?? ($employee->middlename ?? '');
        $last = $employee->last_name ?? ($employee->lastname ?? '');
        return trim("{$first} {$middle} {$last}");
    }

    /**
     * Create dashboard notification record for service milestone (HR only).
     *
     * @param int $notifiableEmployeeId HR employee_id used as notifiable_id
     */
    private function createDashboardNotificationForMilestone($notifiableEmployeeId, $employee, $employeeName, $yearsOfService, Carbon $today)
    {
        $title = 'Service Milestone';
        $message = "{$employeeName} has reached {$yearsOfService} years of service as of " . $today->format('F d, Y') . ".";

        $notificationId = \Illuminate\Support\Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'App\Notifications\ServiceMilestoneNotification',
            'notifiable_type' => 'App\User',
            'notifiable_id' => $notifiableEmployeeId, // HR employee_id
            'data' => json_encode([
                'employee_id' => $employee->id,
                'employee_name' => $employeeName,
                'years_of_service' => $yearsOfService,
                'date_hired' => $employee->date_hired,
                'message' => $message,
                'title' => $title,
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}


