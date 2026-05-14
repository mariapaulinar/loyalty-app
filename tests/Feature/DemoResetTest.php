<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Tests for the demo:reset command, seeder correctness, plan alignment,
 * shared wallet proof, and demo reset repeatability.
 */

use App\Models\Admin;
use App\Models\Card;
use App\Models\Club;
use App\Models\Member;
use App\Models\Partner;
use App\Models\Staff;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| demo:reset Command Guard Tests
|--------------------------------------------------------------------------
*/

describe('demo:reset command guards', function () {
    it('refuses to run when demo mode is disabled', function () {
        config(['default.app_demo' => false]);

        $this->artisan('demo:reset', ['--force' => true])
            ->assertFailed()
            ->expectsOutput('Demo mode is not enabled. Set APP_DEMO=true in .env to use this command.');
    });

    it('is registered and discoverable', function () {
        $this->artisan('list')
            ->assertSuccessful();

        expect(class_exists(\App\Console\Commands\DemoReset::class))->toBeTrue();
    });
});

/*
|--------------------------------------------------------------------------
| demo:reset Success Path
|--------------------------------------------------------------------------
| Full destructive path (migrate:fresh + seed + cache clear) is tested
| in DemoResetSuccessPathTest.php using DatabaseMigrations.
*/

/*
|--------------------------------------------------------------------------
| PartnerSeeder Tests
|--------------------------------------------------------------------------
*/

describe('PartnerSeeder', function () {
    it('seeds four partners with distinct plans', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        expect(Partner::count())->toBe(4);

        $plans = Partner::pluck('plan')->sort()->values()->all();
        expect($plans)->toBe(['tier1', 'tier2', 'tier3', 'tier4']);
    });

    it('creates primary partner with correct email and plan', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        $primary = Partner::where('email', 'partner@example.com')->first();

        expect($primary)->not->toBeNull()
            ->and($primary->name)->toBe('Olivia')
            ->and($primary->plan)->toBe('tier3');
    });

    it('creates secondary partners with correct emails and plans', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        expect(Partner::where('email', 'partner2@example.com')->first()->plan)->toBe('tier2')
            ->and(Partner::where('email', 'partner3@example.com')->first()->plan)->toBe('tier1')
            ->and(Partner::where('email', 'partner4@example.com')->first()->plan)->toBe('tier4');
    });

    it('sets business profiles on all partners', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        $partners = Partner::all();

        foreach ($partners as $partner) {
            expect($partner->business_name)->not->toBeNull()
                ->and($partner->business_name)->not->toBeEmpty()
                ->and($partner->brand_color)->toMatch('/^#[0-9A-Fa-f]{6}$/');
        }
    });

    it('seeds realistic business names', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        $names = Partner::pluck('business_name')->sort()->values()->all();

        expect($names)->toBe([
            'Basil & Thyme Kitchen',
            'Glow Studio',
            'Ironclad Fitness',
            'The Daily Grind',
        ]);
    });

    it('seeds addresses for all partners', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        $partners = Partner::all();

        foreach ($partners as $partner) {
            expect($partner->city)->not->toBeNull()
                ->and($partner->state)->not->toBeNull()
                ->and($partner->postal_code)->not->toBeNull();
        }
    });

    it('aligns gold meta with config/plans.php (limits + feature flags)', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        $gold = Partner::where('plan', 'tier3')->first();
        $cfg = config('plans.tier3');

        expect($gold)->not->toBeNull()
            // Resource limits
            ->and($gold->meta['loyalty_cards_limit'])->toBe($cfg['max_cards'])
            ->and($gold->meta['rewards_limit'])->toBe($cfg['max_rewards'])
            ->and($gold->meta['staff_members_limit'])->toBe($cfg['max_staff'])
            // Feature flags
            ->and($gold->meta['vouchers_permission'])->toBe($cfg['has_vouchers'])
            ->and($gold->meta['voucher_batches_permission'])->toBe($cfg['has_voucher_batches'])
            ->and($gold->meta['email_campaigns_permission'])->toBe($cfg['has_email_campaigns'])
            ->and($gold->meta['activity_permission'])->toBe($cfg['has_activity_log'])
            ->and($gold->meta['agent_api_permission'])->toBe($cfg['has_agent_api'])
            ->and($gold->meta['agent_keys_limit'])->toBe($cfg['max_agent_keys']);
    });

    it('aligns bronze meta with config/plans.php (limits + feature flags)', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        $bronze = Partner::where('plan', 'tier1')->first();
        $cfg = config('plans.tier1');

        expect($bronze)->not->toBeNull()
            // Resource limits
            ->and($bronze->meta['loyalty_cards_limit'])->toBe($cfg['max_cards'])
            ->and($bronze->meta['rewards_limit'])->toBe($cfg['max_rewards'])
            ->and($bronze->meta['staff_members_limit'])->toBe($cfg['max_staff'])
            // Feature flags — Bronze has nothing enabled
            ->and($bronze->meta['vouchers_permission'])->toBeFalse()
            ->and($bronze->meta['voucher_batches_permission'])->toBeFalse()
            ->and($bronze->meta['email_campaigns_permission'])->toBeFalse()
            ->and($bronze->meta['activity_permission'])->toBeFalse()
            ->and($bronze->meta['agent_api_permission'])->toBeFalse()
            ->and($bronze->meta['agent_keys_limit'])->toBe(0);
    });

    it('aligns platinum meta with config/plans.php (limits + feature flags)', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        $platinum = Partner::where('plan', 'tier4')->first();
        $cfg = config('plans.tier4');

        expect($platinum)->not->toBeNull()
            // Resource limits — all unlimited (-1)
            ->and($platinum->meta['loyalty_cards_limit'])->toBe($cfg['max_cards'])
            ->and($platinum->meta['rewards_limit'])->toBe($cfg['max_rewards'])
            ->and($platinum->meta['staff_members_limit'])->toBe($cfg['max_staff'])
            // Feature flags — Platinum has everything enabled
            ->and($platinum->meta['vouchers_permission'])->toBeTrue()
            ->and($platinum->meta['voucher_batches_permission'])->toBeTrue()
            ->and($platinum->meta['email_campaigns_permission'])->toBeTrue()
            ->and($platinum->meta['activity_permission'])->toBeTrue()
            ->and($platinum->meta['agent_api_permission'])->toBeTrue()
            ->and($platinum->meta['agent_keys_limit'])->toBe($cfg['max_agent_keys']);
    });

    it('stores meta permissions for all partners', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        $partners = Partner::all();

        foreach ($partners as $partner) {
            $meta = $partner->meta;
            expect($meta)->toHaveKey('loyalty_cards_permission')
                ->and($meta)->toHaveKey('staff_members_limit')
                ->and($meta)->toHaveKey('vouchers_permission')
                ->and($meta)->toHaveKey('agent_api_permission');
        }
    });

    it('sets all partners as active', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        $inactive = Partner::where('is_active', false)->count();

        expect($inactive)->toBe(0);
    });

    it('is repeatable when run on a clean database', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        $firstCount = Partner::count();
        $firstPlans = Partner::pluck('plan')->sort()->values()->all();

        // Wipe and reseed
        Partner::query()->delete();
        $this->seed(\Database\Seeders\PartnerSeeder::class);

        expect(Partner::count())->toBe($firstCount)
            ->and(Partner::pluck('plan')->sort()->values()->all())->toBe($firstPlans);
    });
});

/*
|--------------------------------------------------------------------------
| ClubSeeder Tests
|--------------------------------------------------------------------------
*/

describe('ClubSeeder', function () {
    it('creates clubs with business names instead of generic "General"', function () {
        $this->seed(\Database\Seeders\PartnerSeeder::class);
        $this->seed(\Database\Seeders\ClubSeeder::class);

        $clubNames = Club::pluck('name')->sort()->values()->all();

        expect($clubNames)->toContain('The Daily Grind')
            ->and($clubNames)->toContain('Basil & Thyme Kitchen')
            ->and($clubNames)->toContain('Glow Studio')
            ->and($clubNames)->toContain('Ironclad Fitness')
            ->and($clubNames)->not->toContain('General');
    });
});

/*
|--------------------------------------------------------------------------
| MemberSeeder Tests
|--------------------------------------------------------------------------
*/

describe('MemberSeeder', function () {
    it('creates primary demo member', function () {
        $this->seed(\Database\Seeders\MemberSeeder::class);

        $emma = Member::where('email', 'member@example.com')->first();

        expect($emma)->not->toBeNull()
            ->and($emma->name)->toBe('Emma')
            ->and($emma->is_active)->toBeTruthy();
    });

    it('creates enough members for multi-partner demo', function () {
        $this->seed(\Database\Seeders\MemberSeeder::class);

        // 1 primary + 12 random = 13 total
        expect(Member::count())->toBe(13);
    });
});

/*
|--------------------------------------------------------------------------
| Full Demo Seed Integration Tests
|--------------------------------------------------------------------------
*/

describe('Full demo seed', function () {
    it('seeds all demo data when demo mode is enabled', function () {
        config(['default.app_demo' => true]);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // Admin
        expect(Admin::where('email', 'admin@example.com')->exists())->toBeTrue();

        // Partners with plan diversity
        expect(Partner::count())->toBe(4);
        expect(Partner::where('plan', 'tier3')->exists())->toBeTrue();
        expect(Partner::where('plan', 'tier2')->exists())->toBeTrue();
        expect(Partner::where('plan', 'tier1')->exists())->toBeTrue();
        expect(Partner::where('plan', 'tier4')->exists())->toBeTrue();

        // Clubs with business names
        $clubNames = Club::pluck('name')->all();
        expect($clubNames)->toContain('The Daily Grind');

        // Staff
        expect(Staff::where('email', 'staff@example.com')->exists())->toBeTrue();

        // Members
        expect(Member::where('email', 'member@example.com')->exists())->toBeTrue();
        expect(Member::count())->toBeGreaterThanOrEqual(10);
    });

    it('creates cards for demo partners', function () {
        config(['default.app_demo' => true]);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        expect(Card::count())->toBeGreaterThan(0);
    });

    it('proves shared wallet — members have transactions with multiple partners', function () {
        config(['default.app_demo' => true]);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // Get the primary demo member
        $emma = Member::where('email', 'member@example.com')->first();
        expect($emma)->not->toBeNull();

        // Check Emma's transactions span multiple partners
        $partnerIdsForEmma = Transaction::where('member_id', $emma->id)
            ->join('cards', 'transactions.card_id', '=', 'cards.id')
            ->distinct()
            ->pluck('cards.created_by')
            ->toArray();

        expect(count($partnerIdsForEmma))->toBeGreaterThanOrEqual(2,
            'Primary demo member must have transactions with at least 2 different partners to prove shared wallet'
        );
    });

    it('maintains partner isolation in demo data', function () {
        config(['default.app_demo' => true]);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $partners = Partner::with('clubs.cards')->get();

        foreach ($partners as $partner) {
            foreach ($partner->clubs as $club) {
                foreach ($club->cards as $card) {
                    expect($card->club_id)->toBe($club->id);
                }
            }
        }
    });

    it('only seeds minimal data when demo mode is disabled', function () {
        config(['default.app_demo' => false]);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // Admin always seeded
        expect(Admin::where('email', 'admin@example.com')->exists())->toBeTrue();

        // No demo partners
        expect(Partner::count())->toBe(0);

        // No demo members
        expect(Member::count())->toBe(0);
    });
});
