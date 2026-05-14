<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Rename Plan Slugs From Metal Names to Tier IDs
 *
 * Pre-release migration: converts existing partner plan values from
 * legacy metal names (bronze, silver, gold, platinum) to neutral
 * tier-based IDs (tier1, tier2, tier3, tier4).
 *
 * Also updates the column default from 'bronze' to 'tier1'.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Metal → tier slug mapping.
     */
    private const SLUG_MAP = [
        'bronze'   => 'tier1',
        'silver'   => 'tier2',
        'gold'     => 'tier3',
        'platinum' => 'tier4',
    ];

    public function up(): void
    {
        foreach (self::SLUG_MAP as $old => $new) {
            DB::table('partners')
                ->where('plan', $old)
                ->update(['plan' => $new]);
        }

        // Update column default for new partners
        Schema::table('partners', function ($table) {
            $table->string('plan', 32)->default('tier1')->change();
        });
    }

    public function down(): void
    {
        // Reverse mapping: tier → metal
        foreach (self::SLUG_MAP as $old => $new) {
            DB::table('partners')
                ->where('plan', $new)
                ->update(['plan' => $old]);
        }

        // Restore original default
        Schema::table('partners', function ($table) {
            $table->string('plan', 32)->default('bronze')->change();
        });
    }
};
