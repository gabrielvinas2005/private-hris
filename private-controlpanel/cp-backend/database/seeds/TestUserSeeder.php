<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create a test admin user
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'employee_no' => 'EMP001',
                'is_admin' => true,
                'locked' => false,
                'with_hrm_access' => true,
                'with_hrt_access' => true,
                'with_hrp_access' => true,
                'with_cpm_access' => true,
                'has_change_password' => true, // Skip OTP requirement
                'is_applicant' => false,
                'access_all_branches' => true,
                'with_expiration' => false,
                'expiration_date' => null,
                'otp_code' => null,
                'with_ld_access' => true,
                'with_mig_access' => true,
                'is_encrypted' => false,
            ]
        );

        // Create a test regular user
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Test User',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'employee_no' => 'EMP002',
                'is_admin' => false,
                'locked' => false,
                'with_hrm_access' => true,
                'with_hrt_access' => false,
                'with_hrp_access' => false,
                'with_cpm_access' => false,
                'has_change_password' => true, // Skip OTP requirement
                'is_applicant' => false,
                'access_all_branches' => false,
                'with_expiration' => false,
                'expiration_date' => null,
                'otp_code' => null,
                'with_ld_access' => false,
                'with_mig_access' => false,
                'is_encrypted' => false,
            ]
        );

        $this->command->info('Test users created successfully!');
        $this->command->info('Admin: admin@example.com / password');
        $this->command->info('User: user@example.com / password');
    }
}
