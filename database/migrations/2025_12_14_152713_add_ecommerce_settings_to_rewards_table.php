<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Adds e-commerce integration settings directly to the rewards table.
 * This enables partners to configure Shopify, WooCommerce, Magento discount
 * settings in one place when creating/editing rewards.
 *
 * Design Rationale:
 * - Single source of truth: Reward configuration happens in ONE place
 * - Progressive disclosure: E-Commerce tab only shows when relevant
 * - Future-proof: Structure supports multiple platforms
 *
 * JSON Structure:
 * {
 *   "shopify": {
 *     "enabled": true,
 *     "discount_type": "percentage|fixed_amount|free_shipping",
 *     "discount_value": 10,
 *     "discount_code_prefix": "REWARD",
 *     "use_automatic_discount": true
 *   },
 *   "woocommerce": { ... },
 *   "magento": { ... }
 * }
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
        Schema::table('rewards', function (Blueprint $table) {
            // E-commerce settings stored as JSON for flexibility
            // Each platform (shopify, woocommerce, magento) gets its own key
            $table->json('ecommerce_settings')->nullable()->after('meta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropColumn('ecommerce_settings');
        });
    }
};
