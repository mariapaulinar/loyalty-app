<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install {--output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the script. Clear cache and database, then migrate and seed data.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $output = $this->option('output');

        // https://stackoverflow.com/a/28898174
        if (! defined('STDIN')) {
            define('STDIN', fopen('php://stdin', 'rb'));
        }
        if (! defined('STDOUT')) {
            define('STDOUT', fopen('php://stdout', 'wb'));
        }
        if (! defined('STDERR')) {
            define('STDERR', fopen('php://stderr', 'wb'));
        }

        $startTime = Carbon::now();

        if ($output) {
            $this->info('Installation started at '.$startTime);
        }

        // $this->clearCache();
        $this->deleteUploads();

        $this->migrateDatabase($output);

        $finishTime = Carbon::now();
        $totalDuration = $finishTime->diff($startTime)->format('%H:%I:%S');

        if ($output) {
            $this->info('All done. Installation finished at '.$finishTime.' ('.$totalDuration.')');
        }
    }

    /**
     * Clear cache.
     *
     * @return void
     */
    private function clearCache()
    {
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('view:clear');
        $this->call('route:clear');
        $this->call('optimize:clear');
    }

    /**
     * Delete all uploads.
     *
     * @return void
     */
    private function deleteUploads()
    {
        $directory = public_path('files');

        $files = File::allFiles($directory);
        foreach ($files as $file) {
            if ($file->getFilename() !== '.gitignore') {
                File::delete($file->getPathname());
            }
        }

        $directories = File::directories($directory);
        foreach ($directories as $dir) {
            File::deleteDirectory($dir);
        }
    }

    /**
     * Migrate database.
     *
     * @param  bool  $output
     * @return void
     */
    private function migrateDatabase($output)
    {
        if ($output) {
            $this->info('Drop all tables and re-run all migrations...');
        }

        // Fix for "Specified key was too long" error
        Schema::defaultStringLength(191);

        $this->call('migrate:fresh', ['--force' => true]);

        if ($output) {
            $this->info('Seed the database with records...');
        }

        $this->call('db:seed', ['--force' => true]);
    }
}
