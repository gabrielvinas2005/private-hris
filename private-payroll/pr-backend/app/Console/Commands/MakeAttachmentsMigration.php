<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeAttachmentsMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:migration:attachments 
                            {name : The name of the migration}
                            {--create= : The table to be created}
                            {--table= : The table to migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration file for the attachments database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $migrationsPath = database_path('migrations/attachments');
        
        // Ensure the attachments migrations directory exists
        if (!File::exists($migrationsPath)) {
            File::makeDirectory($migrationsPath, 0755, true);
            $this->info('Created attachments migrations directory: ' . $migrationsPath);
        }

        $name = $this->argument('name');
        $create = $this->option('create');
        $table = $this->option('table');

        // Build the migration options
        $options = [
            'name' => $name,
            '--path' => 'database/migrations/attachments',
        ];

        if ($create) {
            $options['--create'] = $create;
        } elseif ($table) {
            $options['--table'] = $table;
        }

        $this->info('Creating attachments migration file...');
        
        // Call Laravel's make:migration command with the attachments path
        $this->call('make:migration', $options);

        // Get the created migration file to add the connection property
        // Find the most recently created migration file (should be the one we just created)
        $migrationFiles = File::glob($migrationsPath . '/*.php');
        
        if (!empty($migrationFiles)) {
            // Sort by modification time, get the most recent
            usort($migrationFiles, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $migrationFile = $migrationFiles[0];
            $content = File::get($migrationFile);
            
            // Add the attachments connection property if not already present
            if (strpos($content, "protected \$connection = 'attachments';") === false) {
                // Insert after the class declaration opening brace
                $content = preg_replace(
                    '/(class\s+\w+\s+extends\s+Migration\s*)(\{)/',
                    "$1$2\n    /**\n     * The database connection that should be used by the migration.\n     *\n     * @var string\n     */\n    protected \$connection = 'attachments';",
                    $content
                );
                
                // Fallback for the default Laravel stub where the "{" is on the next line
                if ($content !== null && strpos($content, "protected \$connection = 'attachments';") === false) {
                    $content = preg_replace(
                        '/(class\s+\w+\s+extends\s+Migration\s*\n\{)/',
                        "$1\n    /**\n     * The database connection that should be used by the migration.\n     *\n     * @var string\n     */\n    protected \$connection = 'attachments';",
                        $content
                    );
                }

                File::put($migrationFile, $content);
                $this->info('Added attachments connection to migration file.');
            }
        }

        $this->info('Attachments migration file created successfully!');
        $this->line('File location: ' . $migrationsPath);
        
        return 0;
    }
}

