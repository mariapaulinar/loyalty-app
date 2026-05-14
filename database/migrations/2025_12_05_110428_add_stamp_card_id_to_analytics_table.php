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
        Schema::table('analytics', function (Blueprint $table) {
            $table->char('stamp_card_id', 36)->nullable()->after('reward_id');

            // Add indexes for performance
            $table->index('stamp_card_id', 'analytics_stamp_card_id_idx');
            $table->index(['stamp_card_id', 'event', 'created_at'], 'analytics_stamp_card_event_date_idx');

            // Add foreign key constraint
            $table->foreign('stamp_card_id', 'analytics_stamp_card_id_foreign')
                ->references('id')
                ->on('stamp_cards')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics', function (Blueprint $table) {
            $table->dropForeign('analytics_stamp_card_id_foreign');
            $table->dropIndex('analytics_stamp_card_event_date_idx');
            $table->dropIndex('analytics_stamp_card_id_idx');
            $table->dropColumn('stamp_card_id');
        });
    }
};
