<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Artisan command to clean up expired OTP codes from the database.
 * Should be scheduled to run periodically (e.g., hourly).
 *
 * Usage:
 * php artisan otp:cleanup
 * php artisan otp:cleanup --hours=24   # Delete codes older than 24 hours
 */

namespace App\Console\Commands;

use App\Models\OtpCode;
use Illuminate\Console\Command;

class CleanupOtpCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:cleanup
                            {--hours=24 : Delete OTP codes older than this many hours}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired OTP codes from the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Cleaning up OTP codes older than {$hours} hours...");

        // Count codes to be deleted
        $query = OtpCode::query()
            ->where('created_at', '<', now()->subHours($hours));

        $count = $query->count();

        if ($count === 0) {
            $this->info('No expired OTP codes found.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("[DRY RUN] Would delete {$count} expired OTP code(s).");

            // Show breakdown by status
            $expired = OtpCode::query()
                ->where('expires_at', '<', now())
                ->where('created_at', '<', now()->subHours($hours))
                ->count();

            $verified = OtpCode::query()
                ->where('is_verified', true)
                ->where('created_at', '<', now()->subHours($hours))
                ->count();

            $this->table(
                ['Status', 'Count'],
                [
                    ['Expired', $expired],
                    ['Verified', $verified],
                    ['Total', $count],
                ]
            );

            return self::SUCCESS;
        }

        // Delete the codes
        $deleted = $query->delete();

        $this->info("Successfully deleted {$deleted} expired OTP code(s).");

        return self::SUCCESS;
    }
}
