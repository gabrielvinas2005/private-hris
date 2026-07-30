<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CheckCosContractNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cos-contract:check {--force : Force re-send COS contract notifications even if already sent today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send COS contract expiration notifications (4 months before to HR) and 15 days before to HR and employee (dashboard only)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking for COS contract expirations (4 months before) and 15 days before...');

        // Ensure required tables exist
        if (!Schema::hasTable('cos_contract')) {
            $this->error('cos_contract table does not exist.');
            return 1;
        }

        if (!Schema::hasTable('cos_contract_notifications')) {
            $this->error('cos_contract_notifications table does not exist. Please run migrations first.');
            return 1;
        }

        $today = Carbon::today();

        // Get all COS contracts with End_date in the future
        // Check for contracts expiring in approximately 4 months (115-125 days)
        // Left join with employees table to get employee names
        $contracts = DB::table('cos_contract')
            ->leftJoin('employees', 'cos_contract.employee_id', '=', 'employees.id')
            ->select(
                'cos_contract.*',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.employee_no',
                'employees.name_prefix_id',
                'employees.name_suffix_id'
            )
            ->whereNotNull('cos_contract.End_date')
            ->where('cos_contract.End_date', '>=', $today->format('Y-m-d'))
            ->get();

        if ($contracts->isEmpty()) {
            $this->info('No COS contracts with future expiration dates found.');
            return 0;
        }

        $this->line("Found {$contracts->count()} COS contract(s) with future expiration dates.");

        // Get HR users
        $hrUsers = $this->getHrUsers();
        if ($hrUsers->isEmpty()) {
            $this->warn('No HR users found with with_hrm_access = 1. Skipping COS contract notifications.');
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
            $this->warn('No HR users with valid employee records found. Skipping COS contract notifications.');
            return 0;
        }

        $notifiedCount = 0;

        foreach ($contracts as $contract) {
            try {
                if (empty($contract->End_date)) {
                    continue;
                }

                $expirationDate = Carbon::parse($contract->End_date);
                
                // Calculate days until expiration
                $daysUntilExpiration = $today->diffInDays($expirationDate, false);

                // Build employee info once per contract
                $employee = null;
                $employeeName = 'Unknown Employee';
                if ($contract->employee_id) {
                    $employeeRecord = DB::table('employees')
                        ->where('id', $contract->employee_id)
                        ->first();
                    
                    if ($employeeRecord) {
                        $app_key = config('app.key');
                        $firstName = '';
                        $middleName = '';
                        $lastName = '';
                        
                        if (!empty($employeeRecord->first_name)) {
                            try {
                                $firstNameResult = DB::selectOne("SELECT [dbo].[ufn_DecryptString](?, ?) as decrypted", [$employeeRecord->first_name, $app_key]);
                                $firstName = $firstNameResult->decrypted ?? $employeeRecord->first_name ?? '';
                            } catch (\Exception $e) {
                                $firstName = $employeeRecord->first_name ?? '';
                            }
                        }
                        
                        if (!empty($employeeRecord->middle_name)) {
                            try {
                                $middleNameResult = DB::selectOne("SELECT [dbo].[ufn_DecryptString](?, ?) as decrypted", [$employeeRecord->middle_name, $app_key]);
                                $middleName = $middleNameResult->decrypted ?? $employeeRecord->middle_name ?? '';
                            } catch (\Exception $e) {
                                $middleName = $employeeRecord->middle_name ?? '';
                            }
                        }
                        
                        if (!empty($employeeRecord->last_name)) {
                            try {
                                $lastNameResult = DB::selectOne("SELECT [dbo].[ufn_DecryptString](?, ?) as decrypted", [$employeeRecord->last_name, $app_key]);
                                $lastName = $lastNameResult->decrypted ?? $employeeRecord->last_name ?? '';
                            } catch (\Exception $e) {
                                $lastName = $employeeRecord->last_name ?? '';
                            }
                        }
                        
                        $prefix = '';
                        if (!empty($employeeRecord->name_prefix_id)) {
                            $prefixRecord = DB::table('name_prefixes')->where('id', $employeeRecord->name_prefix_id)->first();
                            $prefix = $prefixRecord->name ?? '';
                        } elseif (isset($employeeRecord->prefix)) {
                            $prefix = $employeeRecord->prefix ?? '';
                        }
                        
                        $suffix = '';
                        if (!empty($employeeRecord->name_suffix_id)) {
                            $suffixRecord = DB::table('name_suffixes')->where('id', $employeeRecord->name_suffix_id)->first();
                            $suffix = $suffixRecord->name ?? '';
                        } elseif (isset($employeeRecord->suffix)) {
                            $suffix = $employeeRecord->suffix ?? '';
                        }
                        
                        $nameParts = array_filter([$prefix, $firstName, $middleName, $lastName, $suffix]);
                        $employeeName = trim(implode(' ', $nameParts));
                        if (empty($employeeName)) {
                            $employeeName = 'Employee ID: ' . $contract->employee_id;
                        }
                        $employee = $employeeRecord;
                    } else {
                        $employeeName = 'Employee ID: ' . $contract->employee_id;
                    }
                }

                // Check if expiration is in approximately 4 months (115-125 days)
                if ($daysUntilExpiration >= 115 && $daysUntilExpiration <= 125) {
                    // Check if notification already sent today (unless --force)
                    if (!$this->option('force')) {
                        $existing = DB::table('cos_contract_notifications')
                            ->where('cos_contract_id', $contract->id)
                            ->whereDate('notification_date', $today)
                            ->where(function($q){
                                $q->whereNull('notes')->orWhere('notes','4_months');
                            })
                            ->first();

                        if ($existing) {
                            $this->line("COS contract notification already sent today for contract ID {$contract->id} (Employee ID: {$contract->employee_id}).");
                            continue;
                        }
                    }

                    $hrSent = false;

                    // Send dashboard notifications to all HR users
                    foreach ($validHrUsers as $hrUser) {
                        try {
                            $this->createDashboardNotification(
                                $hrUser->employee_id,
                                $contract,
                                $employee,
                                $employeeName,
                                $expirationDate
                            );
                            $hrSent = true;
                        } catch (\Exception $e) {
                            $this->error("Failed to create dashboard notification for HR user ID {$hrUser->id}: " . $e->getMessage());
                        }
                    }

                    // Record notification in cos_contract_notifications table
                    DB::table('cos_contract_notifications')->insert([
                        'cos_contract_id' => $contract->id,
                        'employee_id' => $contract->employee_id,
                        'expiration_date' => $expirationDate->format('Y-m-d'),
                        'notification_date' => $today->format('Y-m-d'),
                        'sent_to_hr' => $hrSent,
                        'sent_to_employee' => false,
                        'notes' => '4_months',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $notifiedCount++;
                    $this->info("Sent COS contract expiration notification for contract ID {$contract->id} (Employee: {$employeeName}, Expires: {$expirationDate->format('Y-m-d')}).");
                }

                // Check if expiration is ~15 days away (allow 14-15 days window)
                if ($daysUntilExpiration >= 14 && $daysUntilExpiration <= 15) {
                    // Check if notification already sent today (unless --force)
                    if (!$this->option('force')) {
                        $existing = DB::table('cos_contract_notifications')
                            ->where('cos_contract_id', $contract->id)
                            ->whereDate('notification_date', $today)
                            ->where(function($q){
                                $q->where('notes','15_days');
                            })
                            ->first();

                        if ($existing) {
                            $this->line("15-day COS contract notification already sent today for contract ID {$contract->id} (Employee ID: {$contract->employee_id}).");
                            continue;
                        }
                    }

                    $employeeSent = false;
                    $hrSent15 = false;

                    // Notify HR users (dashboard)
                    foreach ($validHrUsers as $hrUser) {
                        try {
                            $this->createDashboardNotification(
                                $hrUser->employee_id,
                                $contract,
                                $employee,
                                $employeeName,
                                $expirationDate,
                                'COS contract ending in 15 days',
                                "{$employeeName}'s contract will end on {$expirationDate->format('F d, Y')} (15 days left)"
                            );
                            $hrSent15 = true;
                        } catch (\Exception $e) {
                            $this->error("Failed to create 15-day dashboard notification for HR user ID {$hrUser->id}: " . $e->getMessage());
                        }
                    }

                    // Notify the employee (dashboard)
                    if (!empty($contract->employee_id)) {
                        try {
                            $this->createDashboardNotification(
                                $contract->employee_id,
                                $contract,
                                $employee,
                                $employeeName,
                                $expirationDate,
                                'Your COS contract ends in 15 days',
                                "Your contract will end on {$expirationDate->format('F d, Y')} (15 days left)"
                            );
                            $employeeSent = true;
                        } catch (\Exception $e) {
                            $this->error("Failed to create 15-day dashboard notification for Employee ID {$contract->employee_id}: " . $e->getMessage());
                        }
                    }

                    // Record notification in cos_contract_notifications table
                    DB::table('cos_contract_notifications')->insert([
                        'cos_contract_id' => $contract->id,
                        'employee_id' => $contract->employee_id,
                        'expiration_date' => $expirationDate->format('Y-m-d'),
                        'notification_date' => $today->format('Y-m-d'),
                        'sent_to_hr' => $hrSent15,
                        'sent_to_employee' => $employeeSent,
                        'notes' => '15_days',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $notifiedCount++;
                    $this->info("Sent 15-day COS contract notification for contract ID {$contract->id} (Employee: {$employeeName}, Expires: {$expirationDate->format('Y-m-d')}).");
                }
            } catch (\Exception $e) {
                $this->error("Error processing COS contract ID {$contract->id}: " . $e->getMessage());
                continue;
            }
        }

        $this->info("Finished sending {$notifiedCount} COS contract expiration notification(s).");
        return 0;
    }

    /**
     * Get all HR users (users with with_hrm_access = 1)
     */
    private function getHrUsers()
    {
        $hrUsersQuery = DB::table('users')
            ->where(function($query) {
                $query->where('with_hrm_access', 1)
                      ->orWhere('with_hrm_access', true)
                      ->orWhere('with_hrm_access', '1');
            });
        
        if (Schema::hasColumn('users', 'active')) {
            $hrUsersQuery->where(function($query) {
                $query->where('active', true)
                      ->orWhere('active', 1)
                      ->orWhereNull('active');
            });
        }
        
        return $hrUsersQuery->get();
    }

    /**
     * Create a dashboard notification record for COS contract expiration.
     *
     * @param int $notifiableEmployeeId The employee's ID (from employees table)
     * @param object $contract The COS contract record
     * @param object|null $employee The employee record (if available)
     * @param string $employeeName
     * @param Carbon $expirationDate
     * @param string|null $titleOverride
     * @param string|null $messageOverride
     */
    private function createDashboardNotification($notifiableEmployeeId, $contract, $employee, $employeeName, $expirationDate, $titleOverride = null, $messageOverride = null)
    {
        // Avoid duplicate notifications for the same contract/HR user on the same day
        $existing = DB::table('notifications')
            ->where('notifiable_type', 'App\User')
            ->where('notifiable_id', $notifiableEmployeeId)
            ->where('type', 'App\Notifications\CosContractExpirationNotification')
            ->whereDate('created_at', Carbon::today())
            ->get()
            ->filter(function ($notification) use ($contract) {
                $data = json_decode($notification->data, true);
                return isset($data['cos_contract_id']) && $data['cos_contract_id'] == $contract->id;
            })
            ->first();

        if ($existing) {
            return;
        }

        $title = $titleOverride ?: 'COS contract expiration reminder';
        $message = $messageOverride ?: "{$employeeName}'s contract is gonna end in {$expirationDate->format('F d, Y')}";

        $notificationId = \Illuminate\Support\Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'App\Notifications\CosContractExpirationNotification',
            'notifiable_type' => 'App\User',
            'notifiable_id' => $notifiableEmployeeId, // HR employee's ID from employees table
            'data' => json_encode([
                'cos_contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
                'employee_name' => $employeeName,
                'expiration_date' => $expirationDate->format('Y-m-d'),
                'start_date' => $contract->Start_date ?? null,
                'message' => $message,
                'title' => $title,
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

