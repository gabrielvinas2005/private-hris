<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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

        // Find the most recently created migration file (should be the one we just created)
        $migrationFiles = File::glob($migrationsPath . '/*.php');

        if (!empty($migrationFiles)) {
            usort($migrationFiles, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $migrationFile = $migrationFiles[0];
            $content = File::get($migrationFile);

            // Add the attachments connection property if not already present
            if (strpos($content, "protected \$connection = 'attachments';") === false) {
                $insertion = "\\1\n    /**\n     * The database connection that should be used by the migration.\n     *\n     * @var string\n     */\n    protected \$connection = 'attachments';";

                // Support both classic and anonymous migrations
                $updated = preg_replace('/(class\s+\w+\s+extends\s+Migration\s*\{)/', $insertion, $content, 1);
                if ($updated === $content) {
                    $updated = preg_replace('/(return\s+new\s+class\s+extends\s+Migration\s*\{)/', $insertion, $content, 1);
                }

                if ($updated !== null && $updated !== $content) {
                    File::put($migrationFile, $updated);
                    $this->info('Added attachments connection to migration file.');
                } else {
                    $this->warn('Could not automatically add attachments connection to migration file. Please add it manually.');
                }
            }
        }

        $this->info('Attachments migration file created successfully!');
        $this->line('File location: ' . $migrationsPath);

        return 0;
    }
}

