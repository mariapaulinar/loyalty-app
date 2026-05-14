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
            $table->char('voucher_id', 36)->nullable()->after('stamp_card_id');

            // Add indexes for performance
            $table->index('voucher_id', 'analytics_voucher_id_idx');
            $table->index(['voucher_id', 'event', 'created_at'], 'analytics_voucher_event_date_idx');

            // Add foreign key constraint
            $table->foreign('voucher_id', 'analytics_voucher_id_foreign')
                ->references('id')
                ->on('vouchers')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics', function (Blueprint $table) {
            $table->dropForeign('analytics_voucher_id_foreign');
            $table->dropIndex('analytics_voucher_event_date_idx');
            $table->dropIndex('analytics_voucher_id_idx');
            $table->dropColumn('voucher_id');
        });
    }
};
