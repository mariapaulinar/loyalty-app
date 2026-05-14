<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Card-Member Pivot Table
 *
 * Tracks which members have activated which loyalty cards.
 * A member can have multiple cards, and a card can have multiple members.
 *
 * When member scans a new card for the first time:
 * 1. Record is created in this pivot table
 * 2. Initial bonus points are credited (if configured)
 * 3. Member can now earn/redeem points on this card
 *
 * @see App\Models\Card::members()
 * @see App\Models\Member::cards()
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_member', function (Blueprint $table) {
            // The card that was activated
            $table->foreignUuid('card_id')->constrained('cards')->cascadeOnDelete();
            // The member who activated the card
            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();

            // Timestamps track when card was activated
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            // Foreign key indexes
            $table->index('card_id', 'card_member_card_id_idx');
            $table->index('member_id', 'card_member_member_id_idx');

            // Prevent duplicate card-member relationships
            $table->unique(['card_id', 'member_id'], 'card_member_unique');

            // Recent enrollments for analytics
            $table->index('created_at', 'card_member_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('card_member', function (Blueprint $table) {
            $table->dropForeign(['card_id']);
            $table->dropForeign(['member_id']);
        });
        Schema::dropIfExists('card_member');
    }
};
