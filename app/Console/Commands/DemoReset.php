<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DemoReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:reset
        {--force : Skip the confirmation prompt}
        {--fresh : Wipe database and re-run all migrations before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the demo installation to a clean state with fresh seed data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Guard: only run in demo mode
        if (! config('default.app_demo')) {
            $this->error('Demo mode is not enabled. Set APP_DEMO=true in .env to use this command.');

            return self::FAILURE;
        }

        // Guard: never run in production without explicit force
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Refusing to reset in production without --force flag.');

            return self::FAILURE;
        }

        // Confirmation
        if (! $this->option('force') && ! $this->confirm('This will destroy all data and reseed the demo. Continue?')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $this->info('Resetting demo installation...');
        $startTime = microtime(true);

        try {
            if ($this->option('fresh')) {
                $this->freshMigrate();
            } else {
                $this->wipeAndMigrate();
            }

            $this->seed();
            $this->clearCaches();

            $elapsed = round(microtime(true) - $startTime, 2);
            $this->newLine();
            $this->info("Demo reset complete in {$elapsed}s.");
            $this->newLine();
            $this->table(['Role', 'Email', 'Password'], [
                ['Admin', 'admin@example.com', env('APP_DEMO_PASSWORD', 'welcome3210')],
                ['Partner (Gold)', 'partner@example.com', env('APP_DEMO_PASSWORD', 'welcome3210')],
                ['Partner (Silver)', 'partner2@example.com', env('APP_DEMO_PASSWORD', 'welcome3210')],
                ['Partner (Bronze)', 'partner3@example.com', env('APP_DEMO_PASSWORD', 'welcome3210')],
                ['Partner (Platinum)', 'partner4@example.com', env('APP_DEMO_PASSWORD', 'welcome3210')],
                ['Staff', 'staff@example.com', env('APP_DEMO_PASSWORD', 'welcome3210')],
                ['Member', 'member@example.com', env('APP_DEMO_PASSWORD', 'welcome3210')],
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Demo reset failed: '.$e->getMessage());
            $this->newLine();
            $this->warn('Check storage/logs/laravel.log for details.');

            return self::FAILURE;
        }
    }

    /**
     * Wipe all tables and re-run migrations.
     */
    private function wipeAndMigrate(): void
    {
        $this->components->task('Wiping database', function () {
            Artisan::call('db:wipe', ['--force' => true]);
        });

        $this->components->task('Running migrations', function () {
            Artisan::call('migrate', ['--force' => true]);
        });
    }

    /**
     * Drop all tables, re-run all migrations from scratch.
     */
    private function freshMigrate(): void
    {
        $this->components->task('Fresh migration (drop + migrate)', function () {
            Artisan::call('migrate:fresh', ['--force' => true]);
        });
    }

    /**
     * Run the database seeder.
     */
    private function seed(): void
    {
        $this->components->task('Seeding demo data', function () {
            Artisan::call('db:seed', ['--force' => true]);
        });
    }

    /**
     * Clear all caches after reset.
     */
    private function clearCaches(): void
    {
        $this->components->task('Clearing caches', function () {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
        });
    }
}
