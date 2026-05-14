<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Cards Table
 *
 * Loyalty cards are the core product - digital savings cards that members use
 * to collect points and redeem rewards. Each card defines its own rules.
 *
 * Card features:
 * - Point earning rules (points per currency unit, min/max per purchase)
 * - Visual customization (colors, logo, background)
 * - Translatable content (head, title, description)
 * - Expiration management (card-level and points-level)
 *
 * Cards belong to clubs and can have multiple rewards attached.
 *
 * @see App\Models\Card
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            // UUID primary key for globally unique identification
            $table->uuid('id')->primary();

            // ─────────────────────────────────────────────────────────────────
            // CLUB ASSOCIATION
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('club_id')->nullable()->constrained('clubs')->cascadeOnDelete();

            // ─────────────────────────────────────────────────────────────────
            // CARD IDENTITY
            // ─────────────────────────────────────────────────────────────────

            // Internal name for partner dashboard
            $table->string('name', 250);
            // Card type for future expansion (loyalty, gift, membership)
            $table->string('type', 32)->default('loyalty');
            // Icon identifier for mobile app display
            $table->string('icon', 32)->nullable();
            // Translatable text fields (JSON for multi-language support)
            $table->json('head')->nullable()->comment('Translatable: card header text');
            $table->json('title')->nullable()->comment('Translatable: card title');
            $table->json('description')->nullable()->comment('Translatable: card description');
            // Unique identifier for QR codes and public URLs
            $table->string('unique_identifier', 32)->nullable()->unique();

            // ─────────────────────────────────────────────────────────────────
            // CARD LIFECYCLE
            // ─────────────────────────────────────────────────────────────────

            // Issue date - when card becomes available
            $table->dateTime('issue_date')->useCurrent();
            // Card expiration - after this date, card cannot earn/redeem points
            $table->dateTime('expiration_date')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // VISUAL DESIGN (for digital card display)
            // ─────────────────────────────────────────────────────────────────

            // Background color (hex format: #FFFFFF)
            $table->string('bg_color', 25)->nullable();
            // Background opacity (0-100)
            $table->tinyInteger('bg_color_opacity')->nullable();
            // Text colors for card content
            $table->string('text_color', 32)->nullable();
            $table->string('text_label_color', 32)->nullable();
            // QR code colors for branding
            $table->string('qr_color_light', 32)->nullable();
            $table->string('qr_color_dark', 32)->nullable();

            // ─────────────────────────────────────────────────────────────────
            // POINT EARNING RULES
            // ─────────────────────────────────────────────────────────────────

            // Currency for purchases (ISO 4217)
            $table->char('currency', 3)->nullable();
            // Bonus points given when member first activates card
            $table->unsignedInteger('initial_bonus_points')->nullable();
            // Months until earned points expire (0 = never)
            $table->unsignedInteger('points_expiration_months')->nullable();
            // Points calculation: (purchase / currency_unit_amount) * points_per_currency
            // Example: $10 purchase, unit=1, points_per_currency=10 = 100 points
            $table->unsignedInteger('currency_unit_amount')->nullable();
            $table->unsignedInteger('points_per_currency')->nullable();
            // Monetary value per point (for analytics/reporting)
            $table->decimal('point_value', 8, 4)->unsigned()->nullable();
            // Point limits per transaction
            $table->unsignedBigInteger('min_points_per_purchase')->nullable();
            $table->unsignedBigInteger('max_points_per_purchase')->nullable();
            // Redemption limits
            $table->unsignedBigInteger('min_points_per_redemption')->nullable();
            $table->unsignedBigInteger('max_points_per_redemption')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // CUSTOM RULES (translatable, for terms display)
            // ─────────────────────────────────────────────────────────────────

            $table->json('custom_rule1')->nullable();
            $table->json('custom_rule2')->nullable();
            $table->json('custom_rule3')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // CARD FLAGS
            // ─────────────────────────────────────────────────────────────────

            // Active flag controls if card can be used
            $table->boolean('is_active')->default(true);
            // Visibility controls for public card discovery
            $table->boolean('is_visible_by_default')->default(false);
            $table->boolean('is_visible_when_logged_in')->default(false);
            $table->boolean('is_undeletable')->default(false);
            $table->boolean('is_uneditable')->default(false);

            // ─────────────────────────────────────────────────────────────────
            // AGGREGATED STATISTICS
            // ─────────────────────────────────────────────────────────────────

            // These are denormalized for performance; updated on transactions
            $table->unsignedInteger('total_amount_purchased')->default(0);
            $table->unsignedInteger('number_of_points_issued')->default(0);
            $table->dateTime('last_points_issued_at')->nullable();
            $table->unsignedInteger('number_of_points_redeemed')->default(0);
            $table->unsignedInteger('number_of_rewards_redeemed')->default(0);
            $table->dateTime('last_reward_redeemed_at')->nullable();
            // View tracking for engagement analytics
            $table->unsignedInteger('views')->default(0);
            $table->dateTime('last_view')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // EXTENSIBLE METADATA
            // ─────────────────────────────────────────────────────────────────

            // Store: contact info, social links, advanced rules
            $table->json('meta')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // AUDIT TRAIL
            // ─────────────────────────────────────────────────────────────────

            $table->foreignUuid('created_by')->nullable()->constrained('partners')->cascadeOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('partners')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('partners')->nullOnDelete();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // ─────────────────────────────────────────────────────────────────
            // INDEXES
            // ─────────────────────────────────────────────────────────────────

            $table->index('club_id', 'cards_club_id_idx');
            $table->index('is_active', 'cards_is_active_idx');
            $table->index('type', 'cards_type_idx');
            // Combined for club-level card queries
            $table->index(['club_id', 'is_active'], 'cards_club_active_idx');
            // Expiration management
            $table->index(['is_active', 'expiration_date'], 'cards_expiration_idx');
            // Public card discovery
            $table->index(['is_active', 'is_visible_by_default'], 'cards_visibility_idx');
            // Audit indexes
            $table->index('created_by', 'cards_created_by_idx');
            $table->index('deleted_by', 'cards_deleted_by_idx');
            $table->index('updated_by', 'cards_updated_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('cards');
    }
};
