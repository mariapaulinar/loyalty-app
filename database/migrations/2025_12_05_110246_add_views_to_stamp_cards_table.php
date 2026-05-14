<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->integer('views')->default(0)->after('is_visible_by_default');
            $table->dateTime('last_view')->nullable()->after('views');

            // Add index for performance on sorting by views
            $table->index('views', 'stamp_cards_views_idx');
            $table->index('last_view', 'stamp_cards_last_view_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->dropIndex('stamp_cards_views_idx');
            $table->dropIndex('stamp_cards_last_view_idx');
            $table->dropColumn(['views', 'last_view']);
        });
    }
};
