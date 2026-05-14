<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Tests for the Member Agent API endpoints.
 *
 * Covers: profile read/update, balance, cards, transactions,
 * rewards, claim flow, scope enforcement, role isolation.
 *
 * @see App\Http\Controllers\Api\Agent\Member
 */

namespace Tests\Feature\Agent;

use App\Models\AgentKey;
use App\Models\Card;
use App\Models\Member;
use App\Models\Partner;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

require_once __DIR__ . '/Helpers.php';

class MemberEndpointTest extends TestCase
{
    use RefreshDatabase;

    // ═══════════════════════════════════════════════════════════════════════
    // PROFILE ENDPOINTS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_member_can_read_own_profile(): void
    {
        [$member, $key] = createAgentMemberKey();

        $response = $this->getJson(
            '/api/agent/v1/member/profile',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.name', $member->name)
            ->assertJsonPath('data.email', $member->email)
            ->assertJsonPath('data.is_anonymous', false)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'locale', 'unique_identifier', 'avatar', 'created_at']])
            ->assertJsonMissingPath('data.display_name');
    }

    public function test_member_can_update_profile_name(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['read', 'write:profile']]);

        $response = $this->putJson(
            '/api/agent/v1/member/profile',
            ['name' => 'Updated Name'],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
        $response->assertJsonMissingPath('data.display_name');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_member_can_update_profile_locale_using_installed_locale_code(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['read', 'write:profile']]);

        $response = $this->putJson(
            '/api/agent/v1/member/profile',
            ['locale' => 'fr_FR'],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.locale', 'fr_FR');
        $response->assertJsonMissingPath('data.display_name');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'locale' => 'fr_FR',
        ]);
    }

    public function test_profile_update_rejects_invalid_short_locale(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['write:profile']]);

        $response = $this->putJson(
            '/api/agent/v1/member/profile',
            ['locale' => 'fr'],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_profile_update_requires_write_scope(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['read']]);

        $response = $this->putJson(
            '/api/agent/v1/member/profile',
            ['name' => 'Should Fail'],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(403)
            ->assertJsonPath('code', 'AUTH_INSUFFICIENT_SCOPE');
    }

    public function test_profile_update_validates_input(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['write:profile']]);

        $response = $this->putJson(
            '/api/agent/v1/member/profile',
            ['name' => str_repeat('x', 201)],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_profile_update_rejects_empty_body(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['write:profile']]);

        $response = $this->putJson(
            '/api/agent/v1/member/profile',
            [],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonPath('details.errors.body.0', 'At least one updatable field (name, locale) is required.');
    }

    public function test_profile_update_rejects_only_unknown_fields(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['write:profile']]);

        $response = $this->putJson(
            '/api/agent/v1/member/profile',
            ['unknown_field' => 'value', 'email' => 'hacky@example.com'],
            agentHeaders($key->raw_key)
        );

        // 'email' and 'unknown_field' are not in the validator → validated is empty → 422
        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // BALANCE & CARDS ENDPOINTS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_member_can_view_balance(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        // Enroll member in card
        $member->cards()->attach($card->id);

        $response = $this->getJson(
            '/api/agent/v1/member/balance',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonStructure(['data' => [['card_id', 'card_title', 'balance', 'currency']]])
            ->assertJsonMissingPath('data.0.club_name');
    }

    public function test_balance_returns_empty_when_no_cards(): void
    {
        [$member, $key] = createAgentMemberKey();

        $response = $this->getJson(
            '/api/agent/v1/member/balance',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_member_can_list_cards(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        $member->cards()->attach($card->id);

        $response = $this->getJson(
            '/api/agent/v1/member/cards',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonStructure(['data', 'pagination'])
            ->assertJsonMissingPath('data.0.club_name');
    }

    public function test_member_can_show_single_card(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        $member->cards()->attach($card->id);

        $response = $this->getJson(
            "/api/agent/v1/member/cards/{$card->id}",
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $card->id);
    }

    public function test_member_cannot_see_unenrolled_card(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        // Deliberately NOT enrolling

        $response = $this->getJson(
            "/api/agent/v1/member/cards/{$card->id}",
            agentHeaders($key->raw_key)
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TRANSACTION HISTORY
    // ═══════════════════════════════════════════════════════════════════════

    public function test_member_can_view_transactions(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        $member->cards()->attach($card->id);

        // Create a transaction
        Transaction::create([
            'id' => Str::uuid()->toString(),
            'member_id' => $member->id,
            'card_id' => $card->id,
            'staff_id' => null,
            'event' => 'purchase',
            'points' => 50,
            'points_used' => 0,
            'purchase_amount' => 5000,
            'currency' => 'USD',
            'expires_at' => now()->addYear(),
            'created_by' => $partner->id,
        ]);

        $response = $this->getJson(
            '/api/agent/v1/member/transactions',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data', 'pagination']);
    }

    public function test_member_can_view_card_transactions(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        $member->cards()->attach($card->id);

        Transaction::create([
            'id' => Str::uuid()->toString(),
            'member_id' => $member->id,
            'card_id' => $card->id,
            'staff_id' => null,
            'event' => 'purchase',
            'points' => 25,
            'points_used' => 0,
            'purchase_amount' => 2500,
            'currency' => 'USD',
            'expires_at' => now()->addYear(),
            'created_by' => $partner->id,
        ]);

        $response = $this->getJson(
            "/api/agent/v1/member/transactions/{$card->id}",
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_card_transactions_returns_404_for_unenrolled_card(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        $response = $this->getJson(
            "/api/agent/v1/member/transactions/{$card->id}",
            agentHeaders($key->raw_key)
        );

        $response->assertNotFound();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REWARDS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_member_can_list_rewards(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        $member->cards()->attach($card->id);
        createAgentReward($partner->id, $card->id, ['points' => 50]);

        $response = $this->getJson(
            '/api/agent/v1/member/rewards',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_rewards_returns_empty_when_no_cards(): void
    {
        [$member, $key] = createAgentMemberKey();

        $response = $this->getJson(
            '/api/agent/v1/member/rewards',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_claim_requires_write_redeem_scope(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['read']]);

        $response = $this->postJson(
            '/api/agent/v1/member/rewards/fake-id/claim',
            [],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(403)
            ->assertJsonPath('code', 'AUTH_INSUFFICIENT_SCOPE');
    }

    public function test_claim_returns_not_found_for_missing_reward(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['write:redeem']]);

        $response = $this->postJson(
            '/api/agent/v1/member/rewards/' . Str::uuid()->toString() . '/claim',
            [],
            agentHeaders($key->raw_key)
        );

        $response->assertNotFound();
    }

    public function test_claim_returns_insufficient_balance(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['read', 'write:redeem']]);
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        $member->cards()->attach($card->id);
        $reward = createAgentReward($partner->id, $card->id, ['points' => 100]);

        // No transactions → 0 balance

        $response = $this->postJson(
            "/api/agent/v1/member/rewards/{$reward->id}/claim",
            [],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INSUFFICIENT_BALANCE')
            ->assertJsonPath('details.balance', 0)
            ->assertJsonPath('details.required', 100);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ROLE ISOLATION
    // ═══════════════════════════════════════════════════════════════════════

    public function test_partner_key_cannot_access_member_endpoints(): void
    {
        [, $key] = createAgentPartner();

        $response = $this->getJson(
            '/api/agent/v1/member/profile',
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(403)
            ->assertJsonPath('code', 'AUTH_WRONG_ROLE');
    }

    public function test_member_key_cannot_access_partner_endpoints(): void
    {
        [, $key] = createAgentMemberKey();

        $response = $this->getJson(
            '/api/agent/v1/partner/clubs',
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(403)
            ->assertJsonPath('code', 'AUTH_WRONG_ROLE');
    }

    public function test_member_health_endpoint_works(): void
    {
        [, $key] = createAgentMemberKey();

        $response = $this->getJson(
            '/api/agent/v1/health',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.key.role', 'member')
            ->assertJsonPath('data.status', 'ok');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AGENT KEY MODEL
    // ═══════════════════════════════════════════════════════════════════════

    public function test_member_key_has_correct_prefix(): void
    {
        [$member, $key] = createAgentMemberKey();

        $this->assertStringStartsWith('rl_member_', $key->raw_key);
    }

    public function test_get_member_returns_owner(): void
    {
        [$member, $key] = createAgentMemberKey();

        $this->assertNotNull($key->getMember());
        $this->assertEquals($member->id, $key->getMember()->id);
    }

    public function test_get_member_returns_null_for_partner_key(): void
    {
        [, $key] = createAgentPartner();

        $this->assertNull($key->getMember());
    }
}
