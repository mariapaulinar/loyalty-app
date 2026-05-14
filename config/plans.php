<?php

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Partner Subscription Plans Configuration
 *
 * This file defines all available subscription plans for partners.
 * Plans are config-based (no database table needed) for simplicity.
 *
 * Pricing Notes:
 * - Prices are stored in CENTS (integers) to avoid floating point issues
 * - Limit of -1 means UNLIMITED
 * - is_default determines which plan new partners receive
 * - is_popular highlights a plan on pricing pages
 * - sort_order determines plan hierarchy for hasPlanOrHigher() checks
 *
 * Feature Flags:
 * - has_cards_on_homepage:  Can display cards on the public homepage
 * - has_vouchers:          Can create voucher campaigns
 * - has_voucher_batches:   Can batch-generate voucher codes
 * - has_email_campaigns:   Can send email campaigns
 * - has_activity_log:      Can view activity/analytics dashboard
 * - has_agent_api:         Can create Agent API keys
 * - max_agent_keys:        Maximum number of Agent API keys (-1 = unlimited)
 *
 * The EntitlementService reads these flags directly. Seeders and UI
 * must use config values, never derive features from sort_order.
 */

return [

    // ─────────────────────────────────────────────────────────────────────────
    // TIER 1 — FREE
    // ─────────────────────────────────────────────────────────────────────────

    'tier1' => [
        'name' => 'Bronze',
        'name_key' => 'common.plan_tier1_name',
        'description' => 'Get started with the basics',
        'description_key' => 'common.plan_tier1_desc',
        'price_monthly' => 0,
        'price_yearly' => 0,
        'currency' => 'USD',
        'is_active' => true,
        'is_default' => true,
        'is_visible' => true,
        'is_popular' => false,
        'sort_order' => 0,
        'max_clubs' => 1,
        'max_members' => 100,
        'max_staff' => 1,
        'max_locations' => 1,
        'max_cards' => 1,
        'max_stamp_cards' => 1,
        'max_vouchers' => 0,
        'max_rewards' => 3,
        'max_promoted_cards' => 0,
        // Feature flags
        'has_cards_on_homepage' => false,
        'has_vouchers' => false,
        'has_voucher_batches' => false,
        'has_email_campaigns' => false,
        'has_activity_log' => false,
        'has_agent_api' => false,
        'max_agent_keys' => 0,
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // TIER 2 — STARTER PAID
    // ─────────────────────────────────────────────────────────────────────────

    'tier2' => [
        'name' => 'Silver',
        'name_key' => 'common.plan_tier2_name',
        'description' => 'Perfect for small businesses',
        'description_key' => 'common.plan_tier2_desc',
        'price_monthly' => 2900,    // $29.00/month
        'price_yearly' => 29000,    // $290.00/year (2 months free)
        'currency' => 'USD',
        'is_active' => true,
        'is_default' => false,
        'is_visible' => true,
        'is_popular' => false,
        'sort_order' => 1,
        'max_clubs' => 1,
        'max_members' => 1000,
        'max_staff' => 5,
        'max_locations' => 1,
        'max_cards' => 3,
        'max_stamp_cards' => 3,
        'max_vouchers' => 3,
        'max_rewards' => 15,
        'max_promoted_cards' => 1,
        // Feature flags
        'has_cards_on_homepage' => false,
        'has_vouchers' => true,
        'has_voucher_batches' => false,
        'has_email_campaigns' => false,
        'has_activity_log' => true,
        'has_agent_api' => false,
        'max_agent_keys' => 0,
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // TIER 3 — GROWTH
    // ─────────────────────────────────────────────────────────────────────────

    'tier3' => [
        'name' => 'Gold',
        'name_key' => 'common.plan_tier3_name',
        'description' => 'For growing businesses with multiple locations',
        'description_key' => 'common.plan_tier3_desc',
        'price_monthly' => 7900,    // $79.00/month
        'price_yearly' => 79000,    // $790.00/year (2 months free)
        'currency' => 'USD',
        'is_active' => true,
        'is_default' => false,
        'is_visible' => true,
        'is_popular' => true,       // Highlighted on pricing page
        'sort_order' => 2,
        'max_clubs' => 3,
        'max_members' => 10000,
        'max_staff' => 25,
        'max_locations' => 10,
        'max_cards' => 10,
        'max_stamp_cards' => 10,
        'max_vouchers' => 10,
        'max_rewards' => 50,
        'max_promoted_cards' => 5,
        // Feature flags
        'has_cards_on_homepage' => false,
        'has_vouchers' => true,
        'has_voucher_batches' => true,
        'has_email_campaigns' => true,
        'has_activity_log' => true,
        'has_agent_api' => true,
        'max_agent_keys' => 5,
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // TIER 4 — ENTERPRISE
    // ─────────────────────────────────────────────────────────────────────────

    'tier4' => [
        'name' => 'Platinum',
        'name_key' => 'common.plan_tier4_name',
        'description' => 'Unlimited power for agencies and enterprises',
        'description_key' => 'common.plan_tier4_desc',
        'price_monthly' => 19900,   // $199.00/month
        'price_yearly' => 199000,   // $1,990.00/year (2 months free)
        'currency' => 'USD',
        'is_active' => true,
        'is_default' => false,
        'is_visible' => true,
        'is_popular' => false,
        'sort_order' => 3,
        'max_clubs' => -1,          // Unlimited
        'max_members' => -1,        // Unlimited
        'max_staff' => -1,          // Unlimited
        'max_locations' => -1,      // Unlimited
        'max_cards' => -1,          // Unlimited
        'max_stamp_cards' => -1,    // Unlimited
        'max_vouchers' => -1,       // Unlimited
        'max_rewards' => -1,        // Unlimited
        'max_promoted_cards' => -1, // Unlimited
        // Feature flags
        'has_cards_on_homepage' => false,
        'has_vouchers' => true,
        'has_voucher_batches' => true,
        'has_email_campaigns' => true,
        'has_activity_log' => true,
        'has_agent_api' => true,
        'max_agent_keys' => -1,     // Unlimited
    ],

];
