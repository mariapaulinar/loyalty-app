<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Fix the initiated_by column in reward_loyalty_updates table to support UUIDs.
 * The Admin model uses UUIDs as primary keys, so initiated_by must be able to
 * store UUID strings (36 characters), not integers.
 *
 * Background:
 * Previously, initiated_by was defined as unsignedBigInteger, causing data
 * truncation errors when trying to insert Admin UUIDs.
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
        Schema::table('reward_loyalty_updates', function (Blueprint $table) {
            // Change initiated_by from unsignedBigInteger to uuid
            $table->uuid('initiated_by')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_loyalty_updates', function (Blueprint $table) {
            // Revert back to unsignedBigInteger
            // Note: This will fail if there are UUID values in the column
            $table->unsignedBigInteger('initiated_by')->nullable()->change();
        });
    }
};
