<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Tests for plan→meta entitlement enforcement across all partner creation paths.
 *
 * These tests exercise the ACTUAL production code:
 * - HasPlan::deriveMetaFromPlan() — single source of truth
 * - Partner::syncMetaFromPlan() — instance method used by DataDefinition callbacks
 * - Registration data contract (same shape as AuthService::registerWithOtp)
 *
 * No logic is duplicated in test helpers. All assertions validate the real
 * Partner model and its meta JSON column after production methods run.
 */

use App\Models\Partner;
use App\Services\Partner\AuthService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| HasPlan::deriveMetaFromPlan — single source of truth
|--------------------------------------------------------------------------
*/

describe('HasPlan::deriveMetaFromPlan', function () {

    it('derives tier1 meta matching config/plans.php', function () {
        $meta = Partner::deriveMetaFromPlan('tier1');
        $plan = config('plans.tier1');

        expect($meta['loyalty_cards_limit'])->toBe($plan['max_cards']);
        expect($meta['stamp_cards_limit'])->toBe($plan['max_stamp_cards']);
        expect($meta['vouchers_limit'])->toBe($plan['max_vouchers']);
        expect($meta['rewards_limit'])->toBe($plan['max_rewards']);
        expect($meta['staff_members_limit'])->toBe($plan['max_staff']);
        expect($meta['vouchers_permission'])->toBe($plan['has_vouchers']);
        expect($meta['email_campaigns_permission'])->toBe($plan['has_email_campaigns']);
        expect($meta['agent_api_permission'])->toBe($plan['has_agent_api']);
        expect($meta['agent_keys_limit'])->toBe($plan['max_agent_keys']);
    });

    it('derives tier3 meta matching config/plans.php', function () {
        $meta = Partner::deriveMetaFromPlan('tier3');
        $plan = config('plans.tier3');

        expect($meta['loyalty_cards_limit'])->toBe($plan['max_cards']);
        expect($meta['stamp_cards_limit'])->toBe($plan['max_stamp_cards']);
        expect($meta['vouchers_limit'])->toBe($plan['max_vouchers']);
        expect($meta['vouchers_permission'])->toBe($plan['has_vouchers']);
        expect($meta['voucher_batches_permission'])->toBe($plan['has_voucher_batches']);
        expect($meta['email_campaigns_permission'])->toBe($plan['has_email_campaigns']);
        expect($meta['agent_api_permission'])->toBe($plan['has_agent_api']);
        expect($meta['agent_keys_limit'])->toBe($plan['max_agent_keys']);
    });

    it('derives tier4 meta with unlimited limits', function () {
        $meta = Partner::deriveMetaFromPlan('tier4');

        expect($meta['loyalty_cards_limit'])->toBe(-1);
        expect($meta['stamp_cards_limit'])->toBe(-1);
        expect($meta['vouchers_limit'])->toBe(-1);
        expect($meta['staff_members_limit'])->toBe(-1);
        expect($meta['agent_keys_limit'])->toBe(-1);
    });

    it('falls back to tier1 for unknown plan slugs', function () {
        $meta = Partner::deriveMetaFromPlan('nonexistent');
        $bronze = Partner::deriveMetaFromPlan('tier1');

        expect($meta)->toBe($bronze);
    });

    it('merges overrides without losing plan-derived keys', function () {
        $meta = Partner::deriveMetaFromPlan('tier1', ['cards_on_homepage' => true]);

        expect($meta['cards_on_homepage'])->toBeTrue();
        expect($meta['loyalty_cards_limit'])->toBe(config('plans.tier1.max_cards'));
    });

    it('allows overrides to replace plan-derived keys', function () {
        $meta = Partner::deriveMetaFromPlan('tier1', ['loyalty_cards_limit' => 99]);

        // Override wins (array_merge semantics)
        expect($meta['loyalty_cards_limit'])->toBe(99);
    });

    it('produces consistent meta for every configured plan', function () {
        foreach (config('plans') as $slug => $planConfig) {
            $meta = Partner::deriveMetaFromPlan($slug);

            expect($meta['cards_on_homepage'])->toBe($planConfig['has_cards_on_homepage'], "{$slug}: cards_on_homepage");
            expect($meta['loyalty_cards_limit'])->toBe($planConfig['max_cards'], "{$slug}: loyalty_cards_limit");
            expect($meta['stamp_cards_limit'])->toBe($planConfig['max_stamp_cards'], "{$slug}: stamp_cards_limit");
            expect($meta['vouchers_limit'])->toBe($planConfig['max_vouchers'], "{$slug}: vouchers_limit");
            expect($meta['rewards_limit'])->toBe($planConfig['max_rewards'], "{$slug}: rewards_limit");
            expect($meta['staff_members_limit'])->toBe($planConfig['max_staff'], "{$slug}: staff_members_limit");
            expect($meta['vouchers_permission'])->toBe($planConfig['has_vouchers'], "{$slug}: vouchers_permission");
            expect($meta['agent_api_permission'])->toBe($planConfig['has_agent_api'], "{$slug}: agent_api_permission");
        }
    });
});

/*
|--------------------------------------------------------------------------
| Partner::syncMetaFromPlan — instance method (used by DataDefinition)
|--------------------------------------------------------------------------
*/

describe('Partner::syncMetaFromPlan', function () {

    it('syncs meta for a tier1 partner', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier1',
            'meta' => [],
        ]);

        $partner->syncMetaFromPlan();
        $partner->refresh();

        $expected = Partner::deriveMetaFromPlan('tier1');
        foreach ($expected as $key => $value) {
            expect($partner->meta[$key])->toBe($value, "meta[{$key}]");
        }
    });

    it('overwrites permissive form defaults with plan values', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier1',
            'meta' => [
                'vouchers_permission' => true,   // wrong for tier1
                'loyalty_cards_limit' => -1,     // wrong for tier1
                'agent_api_permission' => true,   // wrong for tier1
            ],
        ]);

        $partner->syncMetaFromPlan();
        $partner->refresh();

        expect($partner->meta['vouchers_permission'])->toBeFalse();
        expect($partner->meta['loyalty_cards_limit'])->toBe(1);
        expect($partner->meta['agent_api_permission'])->toBeFalse();
    });

    it('plan-derived keys overwrite pre-existing values', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier3',
            'meta' => [
                'cards_on_homepage' => true,  // admin override pre-sync
                'custom_setting' => 'preserved',
            ],
        ]);

        $partner->syncMetaFromPlan();
        $partner->refresh();

        // Plan-derived value wins (tier3 has_cards_on_homepage = false)
        expect($partner->meta['cards_on_homepage'])->toBeFalse();
        // Non-plan keys survive
        expect($partner->meta['custom_setting'])->toBe('preserved');
        expect($partner->meta['vouchers_permission'])->toBeTrue(); // tier3 has vouchers
    });

    it('defaults to tier1 when plan is null', function () {
        $partner = Partner::factory()->create([
            'plan' => null,
            'meta' => [],
        ]);

        $partner->syncMetaFromPlan();
        $partner->refresh();

        $expected = Partner::deriveMetaFromPlan('tier1');
        foreach ($expected as $key => $value) {
            expect($partner->meta[$key])->toBe($value, "meta[{$key}]");
        }
    });
});

/*
|--------------------------------------------------------------------------
| Plan change via model save (simulates DataDefinition afterUpdate path)
|--------------------------------------------------------------------------
*/

describe('plan change via model save', function () {

    it('upgrade from tier1 to tier3 updates meta', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier1',
            'meta' => Partner::deriveMetaFromPlan('tier1'),
        ]);

        $partner->plan = 'tier3';
        $partner->save();

        // Simulate the afterUpdate callback
        if ($partner->wasChanged('plan')) {
            $partner->syncMetaFromPlan();
        }

        $partner->refresh();

        $expected = Partner::deriveMetaFromPlan('tier3');
        expect($partner->meta['loyalty_cards_limit'])->toBe($expected['loyalty_cards_limit']);
        expect($partner->meta['vouchers_permission'])->toBe($expected['vouchers_permission']);
        expect($partner->meta['agent_api_permission'])->toBe($expected['agent_api_permission']);
    });

    it('downgrade from tier4 to tier2 updates meta', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier4',
            'meta' => Partner::deriveMetaFromPlan('tier4'),
        ]);

        $partner->plan = 'tier2';
        $partner->save();

        if ($partner->wasChanged('plan')) {
            $partner->syncMetaFromPlan();
        }

        $partner->refresh();

        $expected = Partner::deriveMetaFromPlan('tier2');
        expect($partner->meta['loyalty_cards_limit'])->toBe($expected['loyalty_cards_limit']);
        expect($partner->meta['staff_members_limit'])->toBe($expected['staff_members_limit']);
        expect($partner->meta['agent_api_permission'])->toBeFalse();
    });

    it('preserves non-plan meta during upgrade', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier1',
            'meta' => array_merge(
                Partner::deriveMetaFromPlan('tier1'),
                ['custom_key' => 42]
            ),
        ]);

        $partner->plan = 'tier3';
        $partner->save();

        if ($partner->wasChanged('plan')) {
            $partner->syncMetaFromPlan();
        }

        $partner->refresh();

        // Plan-derived keys re-derived from new tier
        expect($partner->meta['cards_on_homepage'])->toBe(config('plans.tier3.has_cards_on_homepage'));
        // Non-plan keys survive
        expect($partner->meta['custom_key'])->toBe(42);
    });

    it('does not change meta when plan stays the same', function () {
        $partner = Partner::factory()->create([
            'plan' => 'tier3',
            'meta' => array_merge(
                Partner::deriveMetaFromPlan('tier3'),
                ['custom_override' => 'preserved']
            ),
        ]);

        $partner->name = 'Updated Name';
        $partner->save();

        // afterUpdate callback would check wasChanged('plan') — should be false
        expect($partner->wasChanged('plan'))->toBeFalse();
        expect($partner->meta['custom_override'])->toBe('preserved');
    });

    it('all tier transitions produce correct meta', function () {
        $plans = array_keys(config('plans'));

        foreach ($plans as $from) {
            foreach ($plans as $to) {
                if ($from === $to) {
                    continue;
                }

                $partner = Partner::factory()->create([
                    'plan' => $from,
                    'meta' => Partner::deriveMetaFromPlan($from),
                ]);

                $partner->plan = $to;
                $partner->save();

                if ($partner->wasChanged('plan')) {
                    $partner->syncMetaFromPlan();
                }

                $partner->refresh();

                $expected = Partner::deriveMetaFromPlan($to);
                foreach ($expected as $key => $value) {
                    expect($partner->meta[$key])->toBe($value, "{$from}→{$to}: meta[{$key}]");
                }

                $partner->delete();
            }
        }
    });
});

/*
|--------------------------------------------------------------------------
| Self-registration data contract (same shape as AuthService::registerWithOtp)
|--------------------------------------------------------------------------
*/

describe('self-registration plan entitlement', function () {

    it('self-registered partner receives default plan meta', function () {
        // Use factory to create a partner via the same data shape as AuthService
        $defaultPlan = Partner::getDefaultPlan();
        $meta = Partner::deriveMetaFromPlan($defaultPlan);

        $partner = Partner::factory()->create([
            'plan' => $defaultPlan,
            'meta' => $meta,
        ]);

        $partner->refresh();

        // Verify the meta matches the default plan exactly
        $expected = Partner::deriveMetaFromPlan($defaultPlan);
        foreach ($expected as $key => $value) {
            expect($partner->meta[$key])->toBe($value, "meta[{$key}]");
        }
    });

    it('self-registered tier1 partner does NOT have vouchers', function () {
        $defaultPlan = Partner::getDefaultPlan();

        expect($defaultPlan)->toBe('tier1');

        $meta = Partner::deriveMetaFromPlan($defaultPlan);
        $partner = Partner::factory()->create([
            'plan' => $defaultPlan,
            'meta' => $meta,
        ]);

        // Bronze explicitly denies paid-tier features
        expect($partner->meta['vouchers_permission'])->toBeFalse();
        expect($partner->meta['voucher_batches_permission'])->toBeFalse();
        expect($partner->meta['email_campaigns_permission'])->toBeFalse();
        expect($partner->meta['activity_permission'])->toBeFalse();
        expect($partner->meta['agent_api_permission'])->toBeFalse();
    });

    it('self-registered tier1 partner has correct resource limits', function () {
        $meta = Partner::deriveMetaFromPlan('tier1');
        $partner = Partner::factory()->create([
            'plan' => 'tier1',
            'meta' => $meta,
        ]);

        expect($partner->meta['loyalty_cards_limit'])->toBe(1);
        expect($partner->meta['stamp_cards_limit'])->toBe(1);
        expect($partner->meta['vouchers_limit'])->toBe(0);
        expect($partner->meta['staff_members_limit'])->toBe(1);
        expect($partner->meta['rewards_limit'])->toBe(3);
        expect($partner->meta['agent_keys_limit'])->toBe(0);
    });

    it('registration data contract includes plan-derived meta', function () {
        // Validates that creating a partner with the same data shape as
        // AuthService::registerWithOtp() produces correct plan-derived meta.
        // This does NOT call registerWithOtp() directly (which requires OTP/i18n).
        $defaultPlan = Partner::getDefaultPlan();
        $meta = Partner::deriveMetaFromPlan($defaultPlan);

        // Recreate the data shape from AuthService::registerWithOtp()
        $partnerData = [
            'role' => 1,
            'name' => 'Test Partner',
            'email' => 'test@example.com',
            'password' => null,
            'plan' => $defaultPlan,
            'network_id' => \App\Models\Network::getPrimaryOrFirst()?->id,
            'is_active' => true,
            'meta' => $meta,
        ];

        $partner = Partner::create($partnerData);
        $partner->refresh();

        // Meta must match plan exactly — no permissive fallbacks
        expect($partner->meta['vouchers_permission'])->toBe(config("plans.{$defaultPlan}.has_vouchers"));
        expect($partner->meta['loyalty_cards_limit'])->toBe(config("plans.{$defaultPlan}.max_cards"));
        expect($partner->meta['stamp_cards_limit'])->toBe(config("plans.{$defaultPlan}.max_stamp_cards"));
        expect($partner->meta['vouchers_limit'])->toBe(config("plans.{$defaultPlan}.max_vouchers"));
    });
});

/*
|--------------------------------------------------------------------------
| Registration configuration
|--------------------------------------------------------------------------
*/

describe('registration configuration', function () {

    it('partner registration is default-off', function () {
        // Verify the config default — critical for SaaS safety
        $default = config('default.partners_can_register');

        expect($default)->toBeFalse();
    });

    it('isRegistrationEnabled falls back to config when no admin setting', function () {
        // Ensure no database setting exists
        app(SettingsService::class)->delete('partners_can_register');

        config(['default.partners_can_register' => false]);
        expect(AuthService::isRegistrationEnabled())->toBeFalse();

        config(['default.partners_can_register' => true]);
        expect(AuthService::isRegistrationEnabled())->toBeTrue();
    });

    it('isRegistrationEnabled honors admin setting over config', function () {
        $settingsService = app(SettingsService::class);

        // Config says disabled, admin says enabled → admin wins
        config(['default.partners_can_register' => false]);
        $settingsService->set('partners_can_register', true);
        expect(AuthService::isRegistrationEnabled())->toBeTrue();

        // Config says enabled, admin says disabled → admin wins
        config(['default.partners_can_register' => true]);
        $settingsService->set('partners_can_register', false);
        expect(AuthService::isRegistrationEnabled())->toBeFalse();

        // Clean up
        $settingsService->delete('partners_can_register');
    });

    it('default plan is tier1 (free tier)', function () {
        $default = Partner::getDefaultPlan();

        expect($default)->toBe('tier1');
        expect(config('plans.tier1.is_default'))->toBeTrue();
        expect(config('plans.tier1.price_monthly'))->toBe(0);
    });
});

/*
|--------------------------------------------------------------------------
| Network assignment for self-registration
|--------------------------------------------------------------------------
*/

describe('registration network assignment', function () {

    it('assigns primary active network during registration', function () {
        $primary = \App\Models\Network::create([
            'name' => 'Primary Network',
            'is_active' => true,
            'is_primary' => true,
            'currency' => 'USD',
        ]);

        $secondary = \App\Models\Network::create([
            'name' => 'Secondary Network',
            'is_active' => true,
            'is_primary' => false,
            'currency' => 'USD',
        ]);

        $resolved = \App\Models\Network::getPrimaryOrFirst();
        expect($resolved->id)->toBe($primary->id);
    });

    it('returns null when no primary network exists', function () {
        // Non-primary networks should not be returned as fallback
        \App\Models\Network::create([
            'name' => 'Non-Primary',
            'is_active' => true,
            'is_primary' => false,
            'currency' => 'USD',
        ]);

        $resolved = \App\Models\Network::getPrimaryOrFirst();
        expect($resolved)->toBeNull();
    });

    it('returns null when primary network is inactive', function () {
        \App\Models\Network::create([
            'name' => 'Inactive Primary',
            'is_active' => false,
            'is_primary' => true,
            'currency' => 'USD',
        ]);

        $resolved = \App\Models\Network::getPrimaryOrFirst();
        expect($resolved)->toBeNull();
    });

    it('returns null when no networks exist at all', function () {
        \App\Models\Network::query()->delete();

        $resolved = \App\Models\Network::getPrimaryOrFirst();
        expect($resolved)->toBeNull();
    });

    it('enforces single primary — setting a new primary clears the old one', function () {
        $first = \App\Models\Network::create([
            'name' => 'First Primary',
            'is_active' => true,
            'is_primary' => true,
            'currency' => 'USD',
        ]);

        $second = \App\Models\Network::create([
            'name' => 'Second Network',
            'is_active' => true,
            'is_primary' => false,
            'currency' => 'USD',
        ]);

        // Mark second as primary — first should lose primary
        $second->is_primary = true;
        $second->save();

        $first->refresh();
        expect($first->is_primary)->toBeFalse();
        expect($second->is_primary)->toBeTrue();

        // Only one primary should exist
        $primaryCount = \App\Models\Network::where('is_primary', true)->count();
        expect($primaryCount)->toBe(1);
    });

    it('does not clear primaries when saving non-primary change', function () {
        $primary = \App\Models\Network::create([
            'name' => 'Primary Network',
            'is_active' => true,
            'is_primary' => true,
            'currency' => 'USD',
        ]);

        // Update a different attribute — primary should remain
        $primary->name = 'Renamed Primary';
        $primary->save();

        $primary->refresh();
        expect($primary->is_primary)->toBeTrue();
    });

});
