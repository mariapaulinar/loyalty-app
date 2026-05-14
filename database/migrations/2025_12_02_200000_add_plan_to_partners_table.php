<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Add Plan Column to Partners Table
 *
 * Partners are assigned a subscription plan. The plan key references
 * config/plans.php (config-based, no plans database table).
 *
 * Default plan is 'tier1' (free tier).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            // Plan key references config/plans.php (tier1, tier2, tier3, tier4)
            // Default to 'tier1' (free tier) for new partners
            $table->string('plan', 32)->default('tier1')->after('remember_token');

            // Index for plan-based queries and analytics
            $table->index('plan', 'partners_plan_idx');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropIndex('partners_plan_idx');
            $table->dropColumn('plan');
        });
    }
};
