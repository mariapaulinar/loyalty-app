<?php

/*
 |--------------------------------------------------------------------------
 | Defaults
 |--------------------------------------------------------------------------
 |
 | The values below are defaults for the app.
 | Can be overridden in the .env file.
 |
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Force SSL
    |--------------------------------------------------------------------------
    */

    'force_ssl' => env('FORCE_SSL', false),

    /*
    |--------------------------------------------------------------------------
    | SEO
    |--------------------------------------------------------------------------
    */

    'page_title_delimiter' => env('PAGE_TITLE_DELIMITER', ' - '),

    /*
     |--------------------------------------------------------------------------
     | App
     |--------------------------------------------------------------------------
     */

    'app_name' => env('APP_NAME', 'Lealmi'),
    'app_logo' => env('APP_LOGO', ''),
    'app_logo_dark' => env('APP_LOGO_DARK', ''),
    'app_url' => env('APP_URL', 'https://localhost'),
    'app_is_installed' => env('APP_IS_INSTALLED', false),
    'app_admin_email' => env('APP_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@example.com')),
    'app_demo' => env('APP_DEMO', false),

    /*
     |--------------------------------------------------------------------------
     | Brand Color (White-Label Primary)
     |--------------------------------------------------------------------------
     | The primary brand color used throughout the application. This single
     | color generates a complete 11-shade palette (50-950) for the primary
     | color scale. Admins can customize this via Settings > Branding.
     |
     | Format: Hex color code (e.g., '#2563EB' for blue)
     | Default: Blue (#2563EB) - Tailwind blue-600, deeper and more premium
     */
    'brand_color' => env('BRAND_COLOR', '#2563EB'),

    /*
     |--------------------------------------------------------------------------
     | Homepage Layout
     |--------------------------------------------------------------------------
     | Controls the public homepage layout. Options:
     | - 'directory': Smart Wallet - all programs in one unified wallet (default)
     | - 'showcase': Editorial presentation for single businesses
     | - 'portal': Minimal authentication-focused landing
     */
    'homepage_layout' => env('HOMEPAGE_LAYOUT', 'directory'),
    'homepage_show_how_it_works' => env('HOMEPAGE_SHOW_HOW_IT_WORKS', true),
    'homepage_show_tiers' => env('HOMEPAGE_SHOW_TIERS', true),
    'homepage_show_member_count' => env('HOMEPAGE_SHOW_MEMBER_COUNT', false),

    'cookie_consent' => env('APP_COOKIE_CONSENT', false),

    /*
     |--------------------------------------------------------------------------
     | PWA (Progressive Web App)
     |--------------------------------------------------------------------------
     | Complete PWA configuration with all settings separated from main app.
     | This allows independent branding for the installed PWA app.
     */

    'pwa_app_name' => env('PWA_APP_NAME', env('APP_NAME', 'Lealmi')),
    'pwa_short_name' => env('PWA_SHORT_NAME', 'Lealmi'),
    'pwa_description' => env('PWA_DESCRIPTION', 'Your digital loyalty cards'),
    'pwa_theme_color' => env('PWA_THEME_COLOR', '#F39C12'),
    'pwa_background_color' => env('PWA_BACKGROUND_COLOR', '#ffffff'),

    /*
     |--------------------------------------------------------------------------
     | Partner signup
     |--------------------------------------------------------------------------
     */

    'partners_can_register' => env('APP_PARTNERS_CAN_REGISTER', false),

    /*
     |--------------------------------------------------------------------------
     | E-mail
     |--------------------------------------------------------------------------
     */

    'registration_email_link' => env('APP_REGISTRATION_EMAIL_LINK', true),
    'mail_from_name' => env('MAIL_FROM_NAME', 'Lealmi'),
    'mail_from_address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),

    /*
     |--------------------------------------------------------------------------
     | Localization
     |--------------------------------------------------------------------------
     */

    'time_zone' => env('DEFAULT_TIMEZONE', 'America/Los_Angeles'),
    'currency' => env('DEFAULT_CURRENCY', 'USD'),

    /*
     |--------------------------------------------------------------------------
     | Request Points
     |--------------------------------------------------------------------------
     | The maximum amount of point request links a member can generate.
     */
    'max_member_request_links' => (int) env('MAX_MEMBER_REQUEST_LINKS', 5),

    /*
     |--------------------------------------------------------------------------
     | Reward Claim QR Valid Minutes
     |--------------------------------------------------------------------------
     | Determines how long a member's reward claim QR code is valid (in minutes).
     | This is for in-person redemptions where the member shows their phone to staff.
     | Shorter validity is recommended for security (prevents QR code screenshots being reused).
     */
    'reward_claim_qr_valid_minutes' => (int) env('REWARD_CLAIM_QR_VALID_MINUTES', 15),

    /*
     |--------------------------------------------------------------------------
     | Code to Redeem Points Valid Minutes
     |--------------------------------------------------------------------------
     | Determines how long a staff-generated 4-digit redemption code is valid (in minutes).
     | Example: 60 means the code is valid for 1 hour. 60 * 24 is 1 day.
     */
    'code_to_redeem_points_valid_minutes' => (int) env('CODE_TO_REDEEM_POINTS_VALID_MINUTES', (60 * 24) * 3),

    /*
     |--------------------------------------------------------------------------
     | Number of days that a staff member can see a member he/she interacted with
     |--------------------------------------------------------------------------
     */

    'staff_transaction_days_ago' => (int) env('APP_STAFF_TRANSACTION_DAYS_AGO', 7),

    /*
     |--------------------------------------------------------------------------
     | Stamp Cards Feature
     |--------------------------------------------------------------------------
     | System-wide feature toggle for digital punch cards.
     | Individual card settings are configured per partner via DataDefinition.
     */

    'stamps_enabled' => env('STAMPS_ENABLED', true),

    /*
     |--------------------------------------------------------------------------
     | Feature Flags (Alpha / Testing Features)
     |--------------------------------------------------------------------------
     | System-wide toggles for features in early development or testing.
     | These default to false (hidden) until ready for general availability.
     |
     | FEATURE_SHOPIFY: Shopify e-commerce integration (Alpha)
     |   - Connects loyalty cards to Shopify stores
     |   - Awards points on purchases, enables reward redemption
     |   - Not officially supported — for testing only
     |
     | FEATURE_AGENT_API: Agent API / Agentic Layer
     |   - Machine-to-machine API for AI agents, POS systems, and automation
     |   - Enables agent key management for Partners, Members, and Admins
     |   - Activates /api/agent/v1/* routes and Agent Keys navigation
     |   - Set to true to enable, false to hide the entire feature
     */

    'feature_shopify' => env('FEATURE_SHOPIFY', false),
    'feature_agent_api' => env('FEATURE_AGENT_API', false),

    /*
     |--------------------------------------------------------------------------
     | Anonymous Member Mode
     |--------------------------------------------------------------------------
     | When enabled, visitors can use loyalty features without registering.
     | Members are identified by a device-bound code stored in localStorage.
     | Email notifications are disabled for anonymous members.
     |
     | This creates a frictionless "Brawl Stars" experience where users
     | play first and optionally link an account later.
     */
    'anonymous_members_enabled' => env('ANONYMOUS_MEMBERS_ENABLED', false),

    /*
     |--------------------------------------------------------------------------
     | Anonymous Member Code Length
     |--------------------------------------------------------------------------
     | Length of the device code assigned to anonymous members.
     | Shorter codes are easier to type but have fewer combinations.
     |
     | Uses safe characters (A-Z excluding I,L,O + 2-9 excluding 0,1):
     | - 4 chars: ~923,000 unique codes (suitable for small-medium deployments)
     | - 6 chars: ~887 million codes (recommended for larger platforms)
     | - 8 chars: ~852 billion codes (enterprise scale)
     */
    'anonymous_member_code_length' => (int) env('ANONYMOUS_MEMBER_CODE_LENGTH', 6),

    /*
     |--------------------------------------------------------------------------
     | Billing Provider (v4 SaaS)
     |--------------------------------------------------------------------------
     | Controls how partner subscriptions are managed.
     |
     | Supported values:
     |   null   — Manual/offline billing. Admin assigns plans via the CRUD.
     |   stripe — Stripe via Laravel Cashier. Partners self-serve upgrades.
     |
     | PayPal and other providers are deferred post-v4.0 but the provider
     | interface is designed to be extensible.
     */

    'billing_provider' => env('BILLING_PROVIDER', null),

    /*
     |--------------------------------------------------------------------------
     | Stripe Price IDs (SaaS Tier Mapping)
     |--------------------------------------------------------------------------
     | Maps plan tiers to their Stripe Price IDs. These are loaded from .env
     | at config-cache time. Never use env() at runtime for these values.
     |
     | Convention: STRIPE_PRICE_{TIER}_{INTERVAL}
     | Example:    STRIPE_PRICE_TIER2_MONTHLY=price_1Abc...
     |
     | Tier 1 is the free tier — no price ID needed unless you offer a
     | paid "free trial" via Stripe.
     */

    'stripe_prices' => [
        'tier1' => [
            'monthly' => env('STRIPE_PRICE_TIER1_MONTHLY'),
            'yearly'  => env('STRIPE_PRICE_TIER1_YEARLY'),
        ],
        'tier2' => [
            'monthly' => env('STRIPE_PRICE_TIER2_MONTHLY'),
            'yearly'  => env('STRIPE_PRICE_TIER2_YEARLY'),
        ],
        'tier3' => [
            'monthly' => env('STRIPE_PRICE_TIER3_MONTHLY'),
            'yearly'  => env('STRIPE_PRICE_TIER3_YEARLY'),
        ],
        'tier4' => [
            'monthly' => env('STRIPE_PRICE_TIER4_MONTHLY'),
            'yearly'  => env('STRIPE_PRICE_TIER4_YEARLY'),
        ],
    ],
];
