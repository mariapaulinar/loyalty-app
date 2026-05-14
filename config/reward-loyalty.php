<?php

declare(strict_types=1);

/**
 * Reward Loyalty - License & Update Configuration
 *
 * Philosophy:
 * - Code always works (no license required for functionality)
 * - Updates require active Envato support period
 * - License data stored encrypted in database via SettingsService
 * - Simple, straightforward, no complexity
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Installation Status
    |--------------------------------------------------------------------------
    |
    | Determines if Reward Loyalty is installed. Uses the existing APP_IS_INSTALLED
    | check from config/default.php for PaaS deployments to bypass the installation
    | wizard.
    |
    */

    'installed' => env('APP_IS_INSTALLED', false),

    /*
    |--------------------------------------------------------------------------
    | License Server
    |--------------------------------------------------------------------------
    |
    | The license server handles purchase code validation, license activation,
    | and automatic update distribution for CodeCanyon customers.
    |
    */

    'license_server' => [
        'url' => env('LICENSE_SERVER_URL', 'https://rewardloyalty.co'),
        'timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Product
    |--------------------------------------------------------------------------
    |
    | CodeCanyon product information for license validation.
    |
    */

    'product' => [
        'id' => env('REWARD_LOYALTY_PRODUCT_ID', '46120964'),
        'name' => 'Reward Loyalty',
    ],

    /*
    |--------------------------------------------------------------------------
    | Update Check Cache
    |--------------------------------------------------------------------------
    |
    | Prevents hammering the license server on every button click.
    | Default: 3 days (259200 seconds)
    |
    */

    'update_check_cache_ttl' => 259200,

    /*
    |--------------------------------------------------------------------------
    | Update Process
    |--------------------------------------------------------------------------
    |
    | Configuration for the automatic update process.
    |
    */

    'updates' => [
        'download_timeout' => 300, // 5 minutes for large packages
        'verify_checksum' => true, // Always verify package integrity
        'keep_backup' => true, // Allows rollback if something goes wrong
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Paths
    |--------------------------------------------------------------------------
    |
    | These paths are NEVER deleted during updates - they are backed up and
    | restored to preserve user data and configuration.
    |
    | Users can add additional paths via PROTECTED_PATHS env variable
    | (comma-separated), and translation directories via PROTECTED_TRANSLATIONS
    | env variable (comma-separated, relative to lang/).
    |
    | Example .env:
    |   PROTECTED_PATHS="custom/config.php,my-module/"
    |   PROTECTED_TRANSLATIONS="de_DE,fr_FR,custom_XX"
    |
    */

    'protected_paths' => array_merge(
        // Core protected paths (always protected)
        [
            '.env',
            '.htaccess',
            'config/plans.php',
            'storage/app',
            'storage/logs',
            'bootstrap/cache',
            'public/files',
            'public/.htaccess',
            'public/favicon.ico',
            'database/database.sqlite',
        ],
        // Additional paths from environment (comma-separated)
        array_filter(
            array_map('trim', explode(',', env('PROTECTED_PATHS', '')))
        ),
        // Protected translation directories (comma-separated, prefixed with lang/)
        array_filter(
            array_map(
                fn ($dir) => 'lang/'.trim($dir),
                array_filter(
                    array_map('trim', explode(',', env('PROTECTED_TRANSLATIONS', '')))
                )
            )
        )
    ),

];
