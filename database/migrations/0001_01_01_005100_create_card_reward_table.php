<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Card-Reward Pivot Table
 *
 * Associates rewards with cards. A reward can be available on multiple cards,
 * and each card can offer multiple rewards at different point levels.
 *
 * This allows:
 * - Sharing rewards across multiple cards (same coffee shop reward on all cards)
 * - Different rewards per card (premium cards get better rewards)
 * - Flexible reward catalog management
 *
 * @see App\Models\Card::rewards()
 * @see App\Models\Reward::cards()
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_reward', function (Blueprint $table) {
            // The card offering this reward
            $table->foreignUuid('card_id')->constrained('cards')->cascadeOnDelete();
            // The reward being offered
            $table->foreignUuid('reward_id')->constrained('rewards')->cascadeOnDelete();

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            // Foreign key indexes
            $table->index('card_id', 'card_reward_card_id_idx');
            $table->index('reward_id', 'card_reward_reward_id_idx');

            // Prevent duplicate card-reward associations
            $table->unique(['card_id', 'reward_id'], 'card_reward_unique');
        });
    }

    public function down(): void
    {
        Schema::table('card_reward', function (Blueprint $table) {
            $table->dropForeign(['card_id']);
            $table->dropForeign(['reward_id']);
        });
        Schema::dropIfExists('card_reward');
    }
};
