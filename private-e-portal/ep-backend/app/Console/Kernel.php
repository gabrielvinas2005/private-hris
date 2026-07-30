<?php

namespace App\Console;

use App\Console\Commands\UserCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\EncryptEnvValues',
        'App\Console\Commands\SecEnvConsoleCommand',
        UserCommand::class,
        \App\Console\Commands\CheckRetirementNotifications::class,
        \App\Console\Commands\CheckBirthdayNotifications::class,
        \App\Console\Commands\CheckServiceMilestones::class,
        \App\Console\Commands\CheckCosContractNotifications::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        
        // Check for retirement notifications daily at 8:00 AM
        $schedule->command('retirement:check')
                 ->dailyAt('08:00');

        // Check birthdays-of-the-month once a day (run on the 1st of each month at 8:10 AM)
        $schedule->command('birthday:check')
                 ->monthlyOn(1, '08:10');

        // Check for service milestones daily at 8:15 AM
        $schedule->command('service-milestones:check')
                 ->dailyAt('08:15');

        // Check for COS contract expirations daily at 8:20 AM
        $schedule->command('cos-contract:check')
                 ->dailyAt('08:20');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
