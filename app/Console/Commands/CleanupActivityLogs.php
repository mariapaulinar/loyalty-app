<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Artisan command to clean up old activity logs. Helps maintain database
 * performance and comply with data retention policies (GDPR, etc.).
 *
 * Design Tenets:
 * - **Configurable Retention**: Days configurable via option or config
 * - **Safe Operation**: Requires confirmation before deletion
 * - **Batch Processing**: Deletes in chunks for large datasets
 * - **Audit Trail**: Logs the cleanup operation itself
 *
 * Usage:
 *   php artisan activity-log:cleanup              # Uses config default (365 days)
 *   php artisan activity-log:cleanup --days=90   # Custom retention period
 *   php artisan activity-log:cleanup --force     # Skip confirmation
 */

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-log:cleanup
                            {--days= : Number of days to retain (defaults to config value)}
                            {--force : Skip confirmation prompt}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete activity logs older than the specified number of days';

    /**
     * The chunk size for batch deletion.
     */
    protected int $chunkSize = 1000;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days')
            ?? config('activitylog.delete_records_older_than_days', 365);

        $days = (int) $days;

        if ($days < 1) {
            $this->error('Days must be a positive integer.');

            return self::FAILURE;
        }

        $cutoffDate = now()->subDays($days);
        $count = Activity::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info('No activity logs older than '.$days.' days found.');

            return self::SUCCESS;
        }

        $this->info("Found {$count} activity logs older than {$days} days (before {$cutoffDate->toDateString()}).");

        // Dry run - just show what would be deleted
        if ($this->option('dry-run')) {
            $this->warn('[DRY RUN] Would delete '.$count.' activity logs.');

            // Show breakdown by log_name
            $this->info('Breakdown by category:');
            $breakdown = Activity::where('created_at', '<', $cutoffDate)
                ->selectRaw('log_name, COUNT(*) as count')
                ->groupBy('log_name')
                ->pluck('count', 'log_name');

            foreach ($breakdown as $logName => $logCount) {
                $this->line("  - {$logName}: {$logCount}");
            }

            return self::SUCCESS;
        }

        // Confirm unless forced
        if (! $this->option('force')) {
            if (! $this->confirm("Delete {$count} activity logs older than {$days} days?")) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        // Perform deletion in chunks
        $this->info('Deleting activity logs...');
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $totalDeleted = 0;
        $startTime = microtime(true);

        do {
            $deleted = Activity::where('created_at', '<', $cutoffDate)
                ->limit($this->chunkSize)
                ->delete();

            $totalDeleted += $deleted;
            $bar->advance($deleted);
        } while ($deleted > 0);

        $bar->finish();
        $this->newLine();

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("Deleted {$totalDeleted} activity logs in {$duration} seconds.");

        // Log the cleanup operation
        Log::info('Activity logs cleanup completed', [
            'deleted_count' => $totalDeleted,
            'retention_days' => $days,
            'cutoff_date' => $cutoffDate->toDateString(),
            'duration_seconds' => $duration,
        ]);

        // Create an activity log entry for the cleanup itself
        activity('admin')
            ->event('cleanup')
            ->withProperties([
                'deleted_count' => $totalDeleted,
                'retention_days' => $days,
                'cutoff_date' => $cutoffDate->toDateString(),
            ])
            ->log("Activity logs cleanup: deleted {$totalDeleted} records older than {$days} days");

        return self::SUCCESS;
    }
}
