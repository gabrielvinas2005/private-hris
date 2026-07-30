<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:attachments 
                            {--fresh : Drop all tables and re-run all migrations}
                            {--status : Show the status of each migration}
                            {--rollback : Rollback the last batch of migrations}
                            {--step= : The number of migrations to rollback}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations for the attachments database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $migrationsPath = database_path('migrations/attachments');
        
        if (!File::exists($migrationsPath)) {
            $this->error('Attachments migrations directory not found at: ' . $migrationsPath);
            return 1;
        }

        // Check migration status
        if ($this->option('status')) {
            $this->info('Checking attachments database migration status...');
            $this->call('migrate:status', [
                '--path' => 'database/migrations/attachments',
                '--database' => 'attachments',
            ]);
            return 0;
        }

        // Rollback migrations
        if ($this->option('rollback')) {
            $this->info('Rolling back attachments database migrations...');
            
            $options = [
                '--path' => 'database/migrations/attachments',
                '--database' => 'attachments',
            ];
            
            if ($this->option('step')) {
                $options['--step'] = $this->option('step');
            }
            
            $this->call('migrate:rollback', $options);
            $this->info('Attachments migrations rollback completed!');
            return 0;
        }

        // Run migrations
        $this->info('Running attachments database migrations...');
        
        if ($this->option('fresh')) {
            $this->call('migrate:fresh', [
                '--path' => 'database/migrations/attachments',
                '--database' => 'attachments',
            ]);
        } else {
            $this->call('migrate', [
                '--path' => 'database/migrations/attachments',
                '--database' => 'attachments',
            ]);
        }
        
        $this->info('Attachments migrations completed!');
        return 0;
    }
}
