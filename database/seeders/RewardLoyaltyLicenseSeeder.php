<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2026 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Seed default license settings for Reward Loyalty license system.
 * Creates all required settings entries in the settings table.
 *
 * Design Tenets:
 * - Idempotent: Uses insertOrIgnore so reruns are safe
 * - Simple: All license data stored in existing settings table
 * - Encrypted: Sensitive data flagged for encryption via SettingsService
 *
 * Integration:
 * - Used by: Installation wizard (CompleteController)
 * - Storage: Existing settings table with encryption support
 * - Called during: Installation step 5 (complete)
 */
class RewardLoyaltyLicenseSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'rewardloyalty.license_token',
                'value' => null,
                'type' => 'string',
                'default_value' => null,
                'category' => 'license',
                'group' => 'rewardloyalty',
                'sort_order' => 1,
                'label' => 'License Token',
                'description' => 'Encrypted license token from activation',
                'help_url' => null,
                'validation_rules' => null,
                'allowed_values' => null,
                'is_public' => false,
                'is_editable' => true,
                'is_encrypted' => true,
                'required_permissions' => json_encode(['manage-system']),
                'is_cached' => false,
                'cache_ttl' => 0,
                'last_modified_by' => null,
                'last_modified_at' => null,
            ],
            [
                'key' => 'rewardloyalty.purchase_code',
                'value' => null,
                'type' => 'string',
                'default_value' => null,
                'category' => 'license',
                'group' => 'rewardloyalty',
                'sort_order' => 2,
                'label' => 'Purchase Code',
                'description' => 'CodeCanyon purchase code',
                'help_url' => null,
                'validation_rules' => null,
                'allowed_values' => null,
                'is_public' => false,
                'is_editable' => true,
                'is_encrypted' => true,
                'required_permissions' => json_encode(['manage-system']),
                'is_cached' => false,
                'cache_ttl' => 0,
                'last_modified_by' => null,
                'last_modified_at' => null,
            ],
            [
                'key' => 'rewardloyalty.license_status',
                'value' => json_encode('inactive'),
                'type' => 'string',
                'default_value' => json_encode('inactive'),
                'category' => 'license',
                'group' => 'rewardloyalty',
                'sort_order' => 3,
                'label' => 'License Status',
                'description' => 'Current license status',
                'help_url' => null,
                'validation_rules' => null,
                'allowed_values' => json_encode(['inactive', 'active', 'expired', 'invalid']),
                'is_public' => false,
                'is_editable' => true,
                'is_encrypted' => false,
                'required_permissions' => json_encode(['manage-system']),
                'is_cached' => true,
                'cache_ttl' => 3600,
                'last_modified_by' => null,
                'last_modified_at' => null,
            ],
            [
                'key' => 'rewardloyalty.support_expires_at',
                'value' => null,
                'type' => 'string',
                'default_value' => null,
                'category' => 'license',
                'group' => 'rewardloyalty',
                'sort_order' => 4,
                'label' => 'Support Expiry Date',
                'description' => 'When support and updates expire',
                'help_url' => null,
                'validation_rules' => null,
                'allowed_values' => null,
                'is_public' => false,
                'is_editable' => true,
                'is_encrypted' => false,
                'required_permissions' => json_encode(['manage-system']),
                'is_cached' => true,
                'cache_ttl' => 3600,
                'last_modified_by' => null,
                'last_modified_at' => null,
            ],
            [
                'key' => 'rewardloyalty.last_validated_at',
                'value' => null,
                'type' => 'string',
                'default_value' => null,
                'category' => 'license',
                'group' => 'rewardloyalty',
                'sort_order' => 5,
                'label' => 'Last Validation',
                'description' => 'When license was last validated',
                'help_url' => null,
                'validation_rules' => null,
                'allowed_values' => null,
                'is_public' => false,
                'is_editable' => true,
                'is_encrypted' => false,
                'required_permissions' => json_encode(['manage-system']),
                'is_cached' => false,
                'cache_ttl' => 0,
                'last_modified_by' => null,
                'last_modified_at' => null,
            ],
            [
                'key' => 'rewardloyalty.previous_version',
                'value' => null,
                'type' => 'string',
                'default_value' => null,
                'category' => 'license',
                'group' => 'rewardloyalty',
                'sort_order' => 6,
                'label' => 'Previous Version',
                'description' => 'Version before last update (for rollback)',
                'help_url' => null,
                'validation_rules' => null,
                'allowed_values' => null,
                'is_public' => false,
                'is_editable' => true,
                'is_encrypted' => false,
                'required_permissions' => json_encode(['manage-system']),
                'is_cached' => false,
                'cache_ttl' => 0,
                'last_modified_by' => null,
                'last_modified_at' => null,
            ],
            [
                'key' => 'rewardloyalty.buyer_email',
                'value' => null,
                'type' => 'string',
                'default_value' => null,
                'category' => 'license',
                'group' => 'rewardloyalty',
                'sort_order' => 7,
                'label' => 'Buyer Email',
                'description' => 'Email from Envato purchase',
                'help_url' => null,
                'validation_rules' => null,
                'allowed_values' => null,
                'is_public' => false,
                'is_editable' => true,
                'is_encrypted' => false,
                'required_permissions' => json_encode(['manage-system']),
                'is_cached' => true,
                'cache_ttl' => 86400,
                'last_modified_by' => null,
                'last_modified_at' => null,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }
}
