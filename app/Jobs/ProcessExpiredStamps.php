<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Scheduled job to process expired stamps across all stamp cards.
 * Runs daily to expire stamps based on card-specific expiry rules.
 *
 * Design Tenets:
 * - **Batch Processing**: Handles large volumes efficiently
 * - **Granular**: Per-card expiry rules respected
 * - **Auditable**: Uses StampService for proper event dispatching
 * - **Safe**: Fails gracefully with error logging
 *
 * Scheduling:
 * Add to routes/console.php:
 * Schedule::job(new ProcessExpiredStamps)->daily();
 */

namespace App\Jobs;

use App\Services\StampService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessExpiredStamps implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Execute the job.
     */
    public function handle(StampService $stampService): void
    {
        Log::info('ProcessExpiredStamps job started');

        try {
            $expiredCount = $stampService->processExpiredStamps();

            Log::info('ProcessExpiredStamps job completed', [
                'expired_count' => $expiredCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Fatal error in ProcessExpiredStamps job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger job retry
        }
    }
}
