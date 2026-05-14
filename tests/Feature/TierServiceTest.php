<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Comprehensive tests for the Tier & Membership Levels feature.
 * Tests tier qualification logic, progression, multipliers, events, and notifications.
 */

use App\Events\MemberTierChanged;
use App\Models\Card;
use App\Models\Club;
use App\Models\Member;
use App\Models\MemberTier;
use App\Models\Partner;
use App\Models\Tier;
use App\Models\Transaction;
use App\Services\TierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function createTestPartner(array $attributes = []): Partner
{
    return Partner::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Test Partner',
        'email' => 'partner'.Str::random(5).'@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'locale' => 'en_US',
        'time_zone' => 'UTC',
        'currency' => 'USD',
        'is_active' => true,
    ], $attributes));
}

function createTestMember(array $attributes = []): Member
{
    return Member::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Test Member',
        'email' => 'member'.Str::random(5).'@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'locale' => 'en_US',
        'time_zone' => 'UTC',
        'currency' => 'USD',
        'is_active' => true,
    ], $attributes));
}

function createTestClub(string $partnerId, array $attributes = []): Club
{
    return Club::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Test Club',
        'created_by' => $partnerId,
        'is_active' => true,
    ], $attributes));
}

function createTestCard(string $partnerId, string $clubId, array $attributes = []): Card
{
    return Card::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Test Card',
        'club_id' => $clubId,
        'created_by' => $partnerId,
        'currency' => 'USD',
        'currency_unit_amount' => 1,
        'points_per_currency' => 100,
        'min_points_per_purchase' => 1,
        'max_points_per_purchase' => 100000,
        'points_expiration_months' => 12,
        'is_active' => true,
        'head' => ['en' => 'Test Card'],
    ], $attributes));
}

function createTestTier(string $clubId, array $attributes = []): Tier
{
    return Tier::create(array_merge([
        'id' => Str::uuid()->toString(),
        'club_id' => $clubId,
        'name' => 'Test Tier',
        'display_name' => ['en' => 'Test Tier'],
        'description' => ['en' => 'A test tier'],
        'icon' => '🎖️',
        'color' => '#CD7F32',
        'level' => 0,
        'points_threshold' => 0,
        'spend_threshold' => 0,
        'transactions_threshold' => 0,
        'points_multiplier' => 1.00,
        'redemption_discount' => 0.00,
        'is_default' => false,
        'is_active' => true,
        'is_undeletable' => false,
    ], $attributes));
}

function createTestTransaction(string $memberId, string $cardId, array $attributes = []): Transaction
{
    return Transaction::create(array_merge([
        'id' => Str::uuid()->toString(),
        'member_id' => $memberId,
        'card_id' => $cardId,
        'event' => 'staff_credited_points_for_purchase',
        'points' => 100,
        'points_used' => 0,
        'purchase_amount' => 10000, // 100.00 in cents
        'expires_at' => now()->addYear(),
    ], $attributes));
}

function setupClubWithTiers(string $partnerId): array
{
    $club = createTestClub($partnerId);

    // ClubObserver no longer auto-creates tiers, create them manually
    $bronze = createTestTier($club->id, [
        'name' => 'Bronze',
        'display_name' => ['en' => 'Bronze'],
        'level' => 0,
        'points_threshold' => 0,
        'points_multiplier' => 1.00,
        'is_default' => true,
        'is_undeletable' => true,
    ]);

    $silver = createTestTier($club->id, [
        'name' => 'Silver',
        'display_name' => ['en' => 'Silver'],
        'level' => 1,
        'points_threshold' => 1000,
        'points_multiplier' => 1.25,
    ]);

    $gold = createTestTier($club->id, [
        'name' => 'Gold',
        'display_name' => ['en' => 'Gold'],
        'level' => 2,
        'points_threshold' => 5000,
        'points_multiplier' => 1.50,
        'redemption_discount' => 0.05,
    ]);

    $platinum = createTestTier($club->id, [
        'name' => 'Platinum',
        'display_name' => ['en' => 'Platinum'],
        'level' => 3,
        'points_threshold' => 15000,
        'points_multiplier' => 2.00,
        'redemption_discount' => 0.10,
    ]);

    return [
        'club' => $club,
        'bronze' => $bronze,
        'silver' => $silver,
        'gold' => $gold,
        'platinum' => $platinum,
    ];
}

/*
|--------------------------------------------------------------------------
| Tier Model Tests
|--------------------------------------------------------------------------
*/

describe('Tier Model', function () {
    it('creates a tier with required attributes', function () {
        $partner = createTestPartner();
        $club = createTestClub($partner->id);

        $tier = createTestTier($club->id, [
            'name' => 'Gold',
            'display_name' => ['en' => 'Gold Member'],
            'level' => 2,
            'points_threshold' => 5000,
        ]);

        expect($tier)->toBeInstanceOf(Tier::class)
            ->and($tier->name)->toBe('Gold')
            ->and($tier->getTranslation('display_name', 'en'))->toBe('Gold Member')
            ->and($tier->level)->toBe(2)
            ->and($tier->points_threshold)->toBe(5000);
    });

    it('derives default from lowest level tier', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);

        // Bronze (level 0) should be the default
        expect($tiers['bronze']->is_default)->toBeTrue()
            ->and($tiers['silver']->is_default)->toBeFalse()
            ->and($tiers['gold']->is_default)->toBeFalse();

        // Deactivating bronze makes silver (next lowest) the default
        $tiers['bronze']->update(['is_active' => false]);
        $tiers['silver']->refresh();

        expect($tiers['silver']->is_default)->toBeTrue();
    });

    it('returns the next tier in hierarchy', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);

        $nextTier = $tiers['bronze']->getNextTier();
        expect($nextTier->id)->toBe($tiers['silver']->id);

        $nextTier = $tiers['silver']->getNextTier();
        expect($nextTier->id)->toBe($tiers['gold']->id);

        $nextTier = $tiers['platinum']->getNextTier();
        expect($nextTier)->toBeNull();
    });

    it('returns the previous tier in hierarchy', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);

        $prevTier = $tiers['platinum']->getPreviousTier();
        expect($prevTier->id)->toBe($tiers['gold']->id);

        $prevTier = $tiers['bronze']->getPreviousTier();
        expect($prevTier)->toBeNull();
    });

    it('applies points multiplier correctly', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);

        $basePoints = 100;

        expect($tiers['bronze']->applyMultiplier($basePoints))->toBe(100)
            ->and($tiers['silver']->applyMultiplier($basePoints))->toBe(125)
            ->and($tiers['gold']->applyMultiplier($basePoints))->toBe(150)
            ->and($tiers['platinum']->applyMultiplier($basePoints))->toBe(200);
    });

    it('applies redemption discount correctly', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);

        $rewardCost = 1000;

        expect($tiers['bronze']->applyRedemptionDiscount($rewardCost))->toBe(1000) // 0% discount
            ->and($tiers['gold']->applyRedemptionDiscount($rewardCost))->toBe(950) // 5% discount
            ->and($tiers['platinum']->applyRedemptionDiscount($rewardCost))->toBe(900); // 10% discount
    });
});

/*
|--------------------------------------------------------------------------
| Tier Qualification Tests
|--------------------------------------------------------------------------
*/

describe('Tier Qualification Logic', function () {
    it('qualifies member for default tier with zero thresholds', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);

        // Member with no activity should qualify for default (Bronze)
        $qualifies = $tiers['bronze']->memberQualifies(0, 0, 0);
        expect($qualifies)->toBeTrue();

        // Should NOT qualify for Silver (needs 1000 points)
        $qualifies = $tiers['silver']->memberQualifies(0, 0, 0);
        expect($qualifies)->toBeFalse();
    });

    it('qualifies member based on points threshold', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);

        // Just under Silver threshold
        expect($tiers['silver']->memberQualifies(999, 0, 0))->toBeFalse();

        // Exactly at Silver threshold
        expect($tiers['silver']->memberQualifies(1000, 0, 0))->toBeTrue();

        // Above Silver threshold
        expect($tiers['silver']->memberQualifies(1500, 0, 0))->toBeTrue();

        // Gold threshold tests
        expect($tiers['gold']->memberQualifies(4999, 0, 0))->toBeFalse();
        expect($tiers['gold']->memberQualifies(5000, 0, 0))->toBeTrue();
    });

    it('qualifies member using any mode (default)', function () {
        $partner = createTestPartner();
        $club = createTestClub($partner->id);

        // Tier requiring either 1000 points OR 500 spend OR 10 transactions
        $tier = createTestTier($club->id, [
            'name' => 'Multi-Threshold',
            'level' => 1,
            'points_threshold' => 1000,
            'spend_threshold' => 50000, // $500 in cents
            'transactions_threshold' => 10,
        ]);

        // Meets points only
        expect($tier->memberQualifies(1000, 0, 0, 'any'))->toBeTrue();

        // Meets spend only
        expect($tier->memberQualifies(0, 50000, 0, 'any'))->toBeTrue();

        // Meets transactions only
        expect($tier->memberQualifies(0, 0, 10, 'any'))->toBeTrue();

        // Meets none
        expect($tier->memberQualifies(500, 25000, 5, 'any'))->toBeFalse();
    });

    it('qualifies member using all mode', function () {
        $partner = createTestPartner();
        $club = createTestClub($partner->id);

        $tier = createTestTier($club->id, [
            'name' => 'All-Required',
            'level' => 1,
            'points_threshold' => 1000,
            'spend_threshold' => 50000,
            'transactions_threshold' => 10,
        ]);

        // Meets only one criterion
        expect($tier->memberQualifies(1000, 0, 0, 'all'))->toBeFalse();

        // Meets two criteria
        expect($tier->memberQualifies(1000, 50000, 0, 'all'))->toBeFalse();

        // Meets all criteria
        expect($tier->memberQualifies(1000, 50000, 10, 'all'))->toBeTrue();
    });

    it('calculates progress percentages correctly', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);

        // 50% progress towards Silver (500/1000 points)
        $progress = $tiers['silver']->getProgressFor(500, 0, 0);
        expect($progress['points'])->toBe(50);

        // 100% progress (capped)
        $progress = $tiers['silver']->getProgressFor(2000, 0, 0);
        expect($progress['points'])->toBe(100);
    });
});

/*
|--------------------------------------------------------------------------
| TierService Tests
|--------------------------------------------------------------------------
*/

describe('TierService', function () {
    it('evaluates and assigns default tier for new member', function () {
        Event::fake([MemberTierChanged::class]);

        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);
        $member = createTestMember();
        $card = createTestCard($partner->id, $tiers['club']->id);

        $service = app(TierService::class);
        $memberTier = $service->evaluateMemberTier($member, $tiers['club']);

        expect($memberTier)->toBeInstanceOf(MemberTier::class)
            ->and($memberTier->tier_id)->toBe($tiers['bronze']->id)
            ->and($memberTier->is_active)->toBeTrue();

        Event::assertDispatched(MemberTierChanged::class);
    });

    it('upgrades member tier when threshold is met', function () {
        Event::fake([MemberTierChanged::class]);

        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);
        $member = createTestMember();
        $card = createTestCard($partner->id, $tiers['club']->id);

        // First assign bronze
        $service = app(TierService::class);
        $memberTier = $service->evaluateMemberTier($member, $tiers['club']);
        expect($memberTier->tier_id)->toBe($tiers['bronze']->id);

        // Create transactions to reach Silver threshold (1000+ points)
        for ($i = 0; $i < 10; $i++) {
            createTestTransaction($member->id, $card->id, [
                'points' => 150,
                'purchase_amount' => 15000,
            ]);
        }

        // Re-evaluate
        $memberTier = $service->evaluateMemberTier($member, $tiers['club']);

        expect($memberTier->tier_id)->toBe($tiers['silver']->id);
    });

    it('calculates multiplied points correctly', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);
        $member = createTestMember();

        // Manually assign Gold tier
        MemberTier::create([
            'id' => Str::uuid()->toString(),
            'member_id' => $member->id,
            'tier_id' => $tiers['gold']->id,
            'club_id' => $tiers['club']->id,
            'achieved_at' => now(),
            'is_active' => true,
        ]);

        $service = app(TierService::class);
        $multipliedPoints = $service->calculateMultipliedPoints(100, $member, $tiers['club']);

        // Gold has 1.50 multiplier
        expect($multipliedPoints)->toBe(150);
    });

    it('returns base points when tiers are disabled', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);
        $member = createTestMember();

        // Assign Gold tier
        MemberTier::create([
            'id' => Str::uuid()->toString(),
            'member_id' => $member->id,
            'tier_id' => $tiers['gold']->id,
            'club_id' => $tiers['club']->id,
            'achieved_at' => now(),
            'is_active' => true,
        ]);

        // Disable tiers for this club via meta
        $tiers['club']->meta = ['tiers' => ['enabled' => false]];
        $tiers['club']->save();

        $service = app(TierService::class);
        $multipliedPoints = $service->calculateMultipliedPoints(100, $member, $tiers['club']);

        expect($multipliedPoints)->toBe(100);
    });

    it('gets qualifying stats from transactions', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);
        $member = createTestMember();
        $card = createTestCard($partner->id, $tiers['club']->id);

        // Create test transactions
        createTestTransaction($member->id, $card->id, ['points' => 100, 'purchase_amount' => 10000]);
        createTestTransaction($member->id, $card->id, ['points' => 200, 'purchase_amount' => 20000]);
        createTestTransaction($member->id, $card->id, ['points' => 300, 'purchase_amount' => 30000]);

        $service = app(TierService::class);
        $stats = $service->getQualifyingStats($member, $tiers['club']);

        expect($stats['lifetime_points'])->toBe(600)
            ->and($stats['lifetime_spend'])->toBe(60000)
            ->and($stats['transaction_count'])->toBe(3);
    });

    it('does not auto-create tiers via ClubObserver (manual setup required)', function () {
        $partner = createTestPartner();
        $club = createTestClub($partner->id);

        // ClubObserver no longer auto-creates tiers - partners must set up manually
        $club->refresh();
        $tierCount = $club->tiers()->count();

        expect($tierCount)->toBe(0);
    });

    it('creates tiers via setupClubWithTiers helper (TierSeeder handles demo data)', function () {
        $partner = createTestPartner();
        $tierSetup = setupClubWithTiers($partner->id);

        $tierSetup['club']->refresh();
        $tierCount = $tierSetup['club']->tiers()->count();

        expect($tierCount)->toBe(4);

        // Verify hierarchy
        $tiers = $tierSetup['club']->tiers()->orderBy('level')->get();
        expect($tiers[0]->name)->toBe('Bronze')
            ->and($tiers[0]->is_default)->toBeTrue()
            ->and($tiers[1]->name)->toBe('Silver')
            ->and($tiers[2]->name)->toBe('Gold')
            ->and($tiers[3]->name)->toBe('Platinum');
    });
});

/*
|--------------------------------------------------------------------------
| MemberTier Model Tests
|--------------------------------------------------------------------------
*/

describe('MemberTier Model', function () {
    it('tracks membership expiration', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);
        $member = createTestMember();

        $memberTier = MemberTier::create([
            'id' => Str::uuid()->toString(),
            'member_id' => $member->id,
            'tier_id' => $tiers['gold']->id,
            'club_id' => $tiers['club']->id,
            'achieved_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        expect($memberTier->isExpired())->toBeFalse();

        // Update to expired
        $memberTier->update(['expires_at' => now()->subDay()]);
        expect($memberTier->isExpired())->toBeTrue();
    });

    it('calculates days until expiry', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);
        $member = createTestMember();

        $memberTier = MemberTier::create([
            'id' => Str::uuid()->toString(),
            'member_id' => $member->id,
            'tier_id' => $tiers['gold']->id,
            'club_id' => $tiers['club']->id,
            'achieved_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        $daysLeft = $memberTier->daysUntilExpiry();
        expect($daysLeft)->toBeGreaterThanOrEqual(29)
            ->and($daysLeft)->toBeLessThanOrEqual(31);
    });

    it('tracks previous tier on upgrade', function () {
        Event::fake();

        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);
        $member = createTestMember();
        $card = createTestCard($partner->id, $tiers['club']->id);

        // Assign Bronze
        $service = app(TierService::class);
        $service->evaluateMemberTier($member, $tiers['club']);

        // Create transactions to upgrade to Silver
        for ($i = 0; $i < 10; $i++) {
            createTestTransaction($member->id, $card->id, [
                'points' => 150,
                'purchase_amount' => 15000,
            ]);
        }

        // Upgrade
        $memberTier = $service->evaluateMemberTier($member, $tiers['club']);

        expect($memberTier->tier_id)->toBe($tiers['silver']->id)
            ->and($memberTier->previous_tier_id)->toBe($tiers['bronze']->id);
    });
});

/*
|--------------------------------------------------------------------------
| Events Tests
|--------------------------------------------------------------------------
*/

describe('Tier Events', function () {
    it('dispatches MemberTierChanged event on upgrade', function () {
        Event::fake([MemberTierChanged::class]);

        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);
        $member = createTestMember();
        $card = createTestCard($partner->id, $tiers['club']->id);

        // Assign initial tier
        $service = app(TierService::class);
        $service->evaluateMemberTier($member, $tiers['club']);

        Event::assertDispatched(MemberTierChanged::class, function ($event) use ($member, $tiers) {
            return $event->member->id === $member->id
                && $event->newTier->id === $tiers['bronze']->id
                && $event->club->id === $tiers['club']->id;
        });
    });

    it('includes previous tier in event', function () {
        Event::fake([MemberTierChanged::class]);

        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);
        $member = createTestMember();
        $card = createTestCard($partner->id, $tiers['club']->id);

        // Assign Bronze
        $service = app(TierService::class);
        $service->evaluateMemberTier($member, $tiers['club']);
        Event::fake([MemberTierChanged::class]); // Reset fake

        // Create transactions to upgrade
        for ($i = 0; $i < 10; $i++) {
            createTestTransaction($member->id, $card->id, [
                'points' => 150,
                'purchase_amount' => 15000,
            ]);
        }

        $service->evaluateMemberTier($member, $tiers['club']);

        Event::assertDispatched(MemberTierChanged::class, function ($event) use ($tiers) {
            return $event->previousTier !== null
                && $event->previousTier->id === $tiers['bronze']->id
                && $event->newTier->id === $tiers['silver']->id;
        });
    });
});

/*
|--------------------------------------------------------------------------
| Edge Case Tests
|--------------------------------------------------------------------------
*/

describe('Edge Cases', function () {
    it('handles member with multiple clubs independently', function () {
        $partner = createTestPartner();
        $member = createTestMember();

        // Setup two clubs with different tiers
        $club1Setup = setupClubWithTiers($partner->id);
        $club2Setup = setupClubWithTiers($partner->id);

        $card1 = createTestCard($partner->id, $club1Setup['club']->id);
        $card2 = createTestCard($partner->id, $club2Setup['club']->id);

        $service = app(TierService::class);

        // Assign tiers for both clubs
        $memberTier1 = $service->evaluateMemberTier($member, $club1Setup['club']);
        $memberTier2 = $service->evaluateMemberTier($member, $club2Setup['club']);

        // Create transactions only in club 1 to upgrade
        for ($i = 0; $i < 10; $i++) {
            createTestTransaction($member->id, $card1->id, [
                'points' => 150,
                'purchase_amount' => 15000,
            ]);
        }

        // Re-evaluate club 1
        $memberTier1 = $service->evaluateMemberTier($member, $club1Setup['club']);

        // Re-evaluate club 2 (should stay the same)
        $memberTier2 = $service->evaluateMemberTier($member, $club2Setup['club']);

        expect($memberTier1->tier_id)->toBe($club1Setup['silver']->id) // Upgraded
            ->and($memberTier2->tier_id)->toBe($club2Setup['bronze']->id); // Still Bronze
    });

    it('prevents deletion of tier with active members', function () {
        $partner = createTestPartner();
        $tiers = setupClubWithTiers($partner->id);
        $member = createTestMember();

        // Assign member to Silver tier
        MemberTier::create([
            'id' => Str::uuid()->toString(),
            'member_id' => $member->id,
            'tier_id' => $tiers['silver']->id,
            'club_id' => $tiers['club']->id,
            'achieved_at' => now(),
            'is_active' => true,
        ]);

        expect($tiers['silver']->getMemberCount())->toBe(1);
    });

    it('handles tier with fractional multiplier gracefully', function () {
        $partner = createTestPartner();
        $club = createTestClub($partner->id);

        $tier = createTestTier($club->id, [
            'name' => 'Basic Tier',
            'level' => 0,
            'points_multiplier' => 0.50, // Half points (e.g., for new/unverified members)
        ]);

        $basePoints = 100;
        $multiplied = $tier->applyMultiplier($basePoints);

        expect($multiplied)->toBe(50);
    });

    it('returns null when club has no tiers configured', function () {
        $partner = createTestPartner();
        $club = createTestClub($partner->id); // No tiers auto-created anymore
        $member = createTestMember();

        $service = app(TierService::class);
        $result = $service->evaluateMemberTier($member, $club);

        // Should return null since no tiers are configured
        expect($result)->toBeNull();
    });

    it('automatically assigns default tier to member when tiers exist', function () {
        $partner = createTestPartner();
        $tierSetup = setupClubWithTiers($partner->id);
        $member = createTestMember();

        $service = app(TierService::class);
        $result = $service->evaluateMemberTier($member, $tierSetup['club']);

        // Should assign default tier (Bronze)
        expect($result)->not->toBeNull()
            ->and($result->tier->is_default)->toBeTrue()
            ->and($result->tier->name)->toBe('Bronze');
    });
});
