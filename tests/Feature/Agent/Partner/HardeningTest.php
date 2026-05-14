<?php

declare(strict_types=1);

use App\Models\StampCard;
use App\Models\Staff;
use App\Models\Tier;
use App\Models\Transaction;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

require_once __DIR__ . '/../Helpers.php';

describe('Partner Runtime Gate', function () {
    it('blocks partner routes when agent api access is disabled', function () {
        [$partner, $key] = createAgentPartner([], [
            'meta' => [
                'agent_api_permission' => false,
            ],
        ]);

        $this->getJson('/api/agent/v1/partner/clubs', agentHeaders($key->raw_key))
            ->assertStatus(403)
            ->assertJsonPath('code', 'FEATURE_DISABLED')
            ->assertJsonPath('details.permission', 'agent_api_permission');
    });
});

describe('Partner Payload Hardening', function () {
    it('returns a stable public member payload', function () {
        [$partner, $key] = createAgentPartner(['scopes' => ['read']]);
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);
        $member = createAgentMember([
            'member_number' => 'M-1001',
            'meta' => ['internal' => 'secret'],
        ]);

        $member->cards()->attach($card->id);

        $response = $this->getJson(
            "/api/agent/v1/partner/members/{$member->id}",
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.unique_identifier', $member->unique_identifier)
            ->assertJsonPath('data.email', $member->email)
            ->assertJsonMissingPath('data.member_number')
            ->assertJsonMissingPath('data.meta')
            ->assertJsonMissingPath('data.created_by');
    });

    it('returns a stable public staff payload', function () {
        [$partner, $key] = createAgentPartner(['scopes' => ['read']]);
        $club = createAgentClub($partner->id, ['name' => 'Downtown']);
        $staff = Staff::create([
            'name' => 'Alex Staff',
            'email' => 'alex.staff@example.test',
            'password' => bcrypt('password'),
            'club_id' => $club->id,
            'created_by' => $partner->id,
            'meta' => ['internal' => 'secret'],
        ]);

        $response = $this->getJson(
            "/api/agent/v1/partner/staff/{$staff->id}",
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $staff->id)
            ->assertJsonPath('data.club_id', $club->id)
            ->assertJsonPath('data.club_name', 'Downtown')
            ->assertJsonPath('data.email', $staff->email)
            ->assertJsonMissingPath('data.meta')
            ->assertJsonMissingPath('data.created_by')
            ->assertJsonMissingPath('data.password');
    });
});

describe('System Purchase Flow', function () {
    it('records purchase amount and first purchase bonus without staff delegation', function () {
        [$partner, $key] = createAgentPartner(['scopes' => ['write:transactions']]);
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id, [
            'initial_bonus_points' => 25,
        ]);
        $member = createAgentMember();

        $response = $this->postJson('/api/agent/v1/partner/transactions/purchase', [
            'card_id' => $card->id,
            'member_identifier' => $member->id,
            'purchase_amount' => 5.00,
            'note' => 'POS order #1001',
        ], agentHeaders($key->raw_key));

        $response->assertStatus(201)
            ->assertJsonPath('data.points_awarded', 500)
            ->assertJsonPath('data.member_balance', 525);

        $this->assertDatabaseHas('transactions', [
            'member_id' => $member->id,
            'card_id' => $card->id,
            'event' => 'initial_bonus_points',
            'points' => 25,
        ]);

        $this->assertDatabaseHas('transactions', [
            'member_id' => $member->id,
            'card_id' => $card->id,
            'event' => 'staff_credited_points_for_purchase',
            'points' => 500,
            'purchase_amount' => 500,
            'staff_id' => null,
            'staff_name' => 'System',
        ]);
    });

    it('honors manual points override while still recording purchase amount', function () {
        [$partner, $key] = createAgentPartner(['scopes' => ['write:transactions']]);
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id, [
            'initial_bonus_points' => 0,
        ]);
        $member = createAgentMember();

        $response = $this->postJson('/api/agent/v1/partner/transactions/purchase', [
            'card_id' => $card->id,
            'member_identifier' => $member->id,
            'purchase_amount' => 10.00,
            'points' => 42,
        ], agentHeaders($key->raw_key));

        $response->assertStatus(201)
            ->assertJsonPath('data.points_awarded', 42)
            ->assertJsonPath('data.member_balance', 42);

        $transaction = Transaction::where('member_id', $member->id)
            ->where('card_id', $card->id)
            ->where('event', 'staff_credited_points_for_purchase')
            ->firstOrFail();

        expect($transaction->purchase_amount)->toBe(1000);
        expect($transaction->points)->toBe(42);
        expect($transaction->meta['manual_points_override'] ?? false)->toBeTrue();
    });

    it('rejects inactive members for system purchases', function () {
        [$partner, $key] = createAgentPartner(['scopes' => ['write:transactions']]);
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);
        $member = createAgentMember([
            'is_active' => false,
        ]);

        $this->postJson('/api/agent/v1/partner/transactions/purchase', [
            'card_id' => $card->id,
            'member_identifier' => $member->id,
            'purchase_amount' => 5.00,
        ], agentHeaders($key->raw_key))
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    });
});

describe('Canonical Field Contract', function () {
    it('rejects deprecated tier field aliases', function () {
        [$partner, $key] = createAgentPartner(['scopes' => ['write:tiers']]);
        $club = createAgentClub($partner->id);

        $this->postJson('/api/agent/v1/partner/tiers', [
            'club_id' => $club->id,
            'name' => 'Gold',
            'level' => 2,
            'points_required' => 1500,
        ], agentHeaders($key->raw_key))
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonPath('details.errors.points_required.0', 'The points_required field is not supported. Use points_threshold instead.');
    });

    it('rejects deprecated staff club_ids alias', function () {
        [$partner, $key] = createAgentPartner(['scopes' => ['write:staff']]);
        $club = createAgentClub($partner->id);

        $this->postJson('/api/agent/v1/partner/staff', [
            'name' => 'Legacy Staff',
            'email' => 'legacy.staff@example.test',
            'password' => 'secret123',
            'club_ids' => [$club->id],
        ], agentHeaders($key->raw_key))
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonPath('details.errors.club_ids.0', 'The club_ids field is not supported. Use club_id instead.');
    });

    it('rejects deprecated stamp card aliases', function () {
        [$partner, $key] = createAgentPartner(['scopes' => ['write:stamps']]);
        $club = createAgentClub($partner->id);

        $this->postJson('/api/agent/v1/partner/stamp-cards', [
            'club_id' => $club->id,
            'name' => 'Legacy Stamp Card',
            'stamps_required' => 10,
            'stamp_expiry_days' => 30,
            'require_staff_for_redemption' => true,
        ], agentHeaders($key->raw_key))
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonPath('details.errors.stamp_expiry_days.0', 'The stamp_expiry_days field is not supported. Use stamps_expire_days instead.')
            ->assertJsonPath('details.errors.require_staff_for_redemption.0', 'The require_staff_for_redemption field is not supported. Use requires_physical_claim instead.');
    });

    it('rejects deprecated voucher aliases', function () {
        [$partner, $key] = createAgentPartner(['scopes' => ['write:vouchers']], [
            'meta' => ['agent_api_permission' => true, 'vouchers_permission' => true, 'vouchers_limit' => -1],
        ]);
        $club = createAgentClub($partner->id);

        $this->postJson('/api/agent/v1/partner/vouchers', [
            'club_id' => $club->id,
            'name' => 'Legacy Voucher',
            'discount_type' => 'fixed',
            'discount_value' => 500,
            'max_uses' => 10,
            'issue_date' => now()->subDay()->toDateString(),
            'expiration_date' => now()->addDay()->toDateString(),
        ], agentHeaders($key->raw_key))
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonPath('details.errors.discount_type.0', 'The discount_type field is not supported. Use type instead.')
            ->assertJsonPath('details.errors.discount_value.0', 'The discount_value field is not supported. Use value instead.')
            ->assertJsonPath('details.errors.max_uses.0', 'The max_uses field is not supported. Use max_uses_total instead.')
            ->assertJsonPath('details.errors.issue_date.0', 'The issue_date field is not supported. Use valid_from instead.')
            ->assertJsonPath('details.errors.expiration_date.0', 'The expiration_date field is not supported. Use valid_until instead.');
    });
});
