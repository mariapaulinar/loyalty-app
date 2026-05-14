<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Observes Club model events for lifecycle handling.
 *
 * Note: Auto-tier creation was removed to prevent confusion for new users
 * who may not understand tier thresholds before configuring them properly.
 * Tiers should be created manually via the partner dashboard.
 */

namespace App\Observers;

use App\Models\Club;

class ClubObserver
{
    /**
     * Handle the Club "created" event.
     *
     * Note: Tier auto-creation was intentionally removed.
     * Partners should manually configure tiers after understanding
     * how qualification thresholds work with their loyalty card rules.
     */
    public function created(Club $club): void
    {
        // Reserved for future club lifecycle events
        // Tiers are now created manually by partners via dashboard
    }
}
