<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Tests for the Member Discover API endpoints.
 *
 * Covers: browsing visible cards, URL/identifier resolution,
 * follow/unfollow, scope enforcement, and role isolation.
 *
 * @see App\Http\Controllers\Api\Agent\Member\AgentDiscoverController
 */

namespace Tests\Feature\Agent;

use App\Models\AgentKey;
use App\Models\Card;
use App\Models\Member;
use App\Models\StampCard;
use App\Models\StampCardMember;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

require_once __DIR__ . '/Helpers.php';

class DiscoverEndpointTest extends TestCase
{
    use RefreshDatabase;

    // ═══════════════════════════════════════════════════════════════════════
    // BROWSE (GET /discover)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_discover_returns_visible_cards(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);

        // Visible card — should appear
        $visible = createAgentCard($partner->id, $club->id, [
            'is_visible_by_default' => true,
            'issue_date' => now()->subDay(),
            'expiration_date' => now()->addYear(),
        ]);
        $member->cards()->attach($visible->id);

        // Hidden card — should NOT appear
        createAgentCard($partner->id, $club->id, [
            'is_visible_by_default' => false,
            'issue_date' => now()->subDay(),
            'expiration_date' => now()->addYear(),
        ]);

        $response = $this->getJson(
            '/api/agent/v1/member/discover',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data.cards')
            ->assertJsonPath('data.cards.0.type', 'loyalty_card')
            ->assertJsonPath('data.cards.0.id', $visible->id)
            ->assertJsonPath('data.cards.0.is_following', true)
            ->assertJsonMissingPath('data.cards.0.club_name');
    }

    public function test_discover_honors_hyphenated_accept_language_header(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);

        createAgentCard($partner->id, $club->id, [
            'is_visible_by_default' => true,
            'issue_date' => now()->subDay(),
            'expiration_date' => now()->addYear(),
            'title' => ['en_US' => 'Coffee Rewards', 'fr_FR' => 'Recompenses Cafe'],
            'description' => ['en_US' => 'Earn points', 'fr_FR' => 'Gagnez des points'],
        ]);

        $response = $this->getJson(
            '/api/agent/v1/member/discover',
            agentHeaders($key->raw_key, ['Accept-Language' => 'fr-FR,fr;q=0.9'])
        );

        $response->assertOk()
            ->assertJsonPath('data.cards.0.title', 'Recompenses Cafe')
            ->assertJsonPath('data.cards.0.description', 'Gagnez des points');
    }

    public function test_discover_returns_visible_stamp_cards(): void
    {
        [$member, $key] = createAgentMemberKey();

        // Create visible stamp card
        $sc = StampCard::create([
            'id' => Str::uuid()->toString(),
            'club_id' => $this->createMinimalClub(),
            'name' => 'Test Stamp Card',
            'title' => ['en' => 'Coffee Card'],
            'stamps_required' => 10,
            'is_active' => true,
            'is_visible_by_default' => true,
        ]);

        // Enroll member
        StampCardMember::create([
            'stamp_card_id' => $sc->id,
            'member_id' => $member->id,
            'current_stamps' => 5,
            'lifetime_stamps' => 5,
            'completed_count' => 0,
            'redeemed_count' => 0,
            'pending_rewards' => 0,
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        $response = $this->getJson(
            '/api/agent/v1/member/discover',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data.stamp_cards')
            ->assertJsonPath('data.stamp_cards.0.type', 'stamp_card')
            ->assertJsonPath('data.stamp_cards.0.is_enrolled', true)
            ->assertJsonPath('data.stamp_cards.0.current_stamps', 5);
    }

    public function test_discover_returns_visible_vouchers(): void
    {
        [$member, $key] = createAgentMemberKey();

        $clubId = $this->createMinimalClub();

        Voucher::create([
            'id' => Str::uuid()->toString(),
            'club_id' => $clubId,
            'code' => 'WELCOME10',
            'name' => 'welcome-voucher',
            'title' => ['en' => '10% Welcome Discount'],
            'type' => 'percentage',
            'value' => 10,
            'currency' => 'USD',
            'is_active' => true,
            'is_visible_by_default' => true,
        ]);

        $response = $this->getJson(
            '/api/agent/v1/member/discover',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data.vouchers')
            ->assertJsonPath('data.vouchers.0.type', 'voucher')
            ->assertJsonPath('data.vouchers.0.code', 'WELCOME10');
    }

    public function test_discover_browse_requires_read_scope(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['write:profile']]);

        $response = $this->getJson(
            '/api/agent/v1/member/discover',
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(403)
            ->assertJsonPath('code', 'AUTH_INSUFFICIENT_SCOPE');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RESOLVE (POST /discover/resolve)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_resolve_by_card_url(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        $response = $this->postJson(
            '/api/agent/v1/member/discover/resolve',
            ['url' => "https://example.com/en-us/card/{$card->id}"],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'loyalty_card')
            ->assertJsonPath('data.id', $card->id)
            ->assertJsonMissingPath('data.club_name');
    }

    public function test_resolve_by_stamp_card_url(): void
    {
        [$member, $key] = createAgentMemberKey();

        $sc = StampCard::create([
            'id' => Str::uuid()->toString(),
            'club_id' => $this->createMinimalClub(),
            'name' => 'Test Stamp Card',
            'title' => ['en' => 'Stamp Test'],
            'stamps_required' => 8,
            'is_active' => true,
            'is_visible_by_default' => false,
        ]);

        $response = $this->postJson(
            '/api/agent/v1/member/discover/resolve',
            ['url' => "https://example.com/en-us/stamp-card/{$sc->id}"],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'stamp_card')
            ->assertJsonPath('data.id', $sc->id)
            ->assertJsonPath('data.stamps_required', 8);
    }

    public function test_resolve_by_voucher_url(): void
    {
        [$member, $key] = createAgentMemberKey();

        $voucher = Voucher::create([
            'id' => Str::uuid()->toString(),
            'club_id' => $this->createMinimalClub(),
            'code' => 'RESOLVE_ME',
            'name' => 'resolve-voucher',
            'title' => ['en' => 'Voucher Test'],
            'type' => 'fixed',
            'value' => 5,
            'currency' => 'USD',
            'is_active' => true,
            'is_visible_by_default' => false,
        ]);

        $response = $this->postJson(
            '/api/agent/v1/member/discover/resolve',
            ['url' => "https://example.com/en-us/voucher/{$voucher->id}"],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'voucher')
            ->assertJsonPath('data.id', $voucher->id);
    }

    public function test_resolve_by_follow_url(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        $response = $this->postJson(
            '/api/agent/v1/member/discover/resolve',
            ['url' => "https://example.com/en-us/follow/{$card->id}"],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'loyalty_card')
            ->assertJsonPath('data.id', $card->id);
    }

    public function test_resolve_by_raw_uuid(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        $response = $this->postJson(
            '/api/agent/v1/member/discover/resolve',
            ['identifier' => $card->id],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'loyalty_card')
            ->assertJsonPath('data.id', $card->id);
    }

    public function test_resolve_by_unique_identifier(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        $uid = $card->unique_identifier;
        $this->assertNotNull($uid, 'Card should have a unique_identifier');

        $response = $this->postJson(
            '/api/agent/v1/member/discover/resolve',
            ['identifier' => $uid],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $card->id);
    }

    public function test_resolve_returns_422_without_input(): void
    {
        [$member, $key] = createAgentMemberKey();

        $response = $this->postJson(
            '/api/agent/v1/member/discover/resolve',
            [],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(422)
            ->assertJsonPath('code', 'MISSING_INPUT');
    }

    public function test_resolve_returns_404_for_unknown_url(): void
    {
        [$member, $key] = createAgentMemberKey();

        $response = $this->postJson(
            '/api/agent/v1/member/discover/resolve',
            ['url' => 'https://example.com/en-us/card/' . Str::uuid()->toString()],
            agentHeaders($key->raw_key)
        );

        $response->assertNotFound();
    }

    public function test_resolve_returns_404_for_invalid_url_format(): void
    {
        [$member, $key] = createAgentMemberKey();

        $response = $this->postJson(
            '/api/agent/v1/member/discover/resolve',
            ['url' => 'https://example.com/some/random/path'],
            agentHeaders($key->raw_key)
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 'UNRESOLVABLE_INPUT');
    }

    public function test_resolve_returns_404_for_inactive_card(): void
    {
        [$member, $key] = createAgentMemberKey();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id, ['is_active' => false]);

        $response = $this->postJson(
            '/api/agent/v1/member/discover/resolve',
            ['identifier' => $card->id],
            agentHeaders($key->raw_key)
        );

        // Inactive cards should not be resolvable
        $response->assertNotFound();
    }

    public function test_resolve_returns_404_for_expired_stamp_card(): void
    {
        [$member, $key] = createAgentMemberKey();

        $sc = StampCard::create([
            'id' => Str::uuid()->toString(),
            'club_id' => $this->createMinimalClub(),
            'name' => 'Expired Stamp Card',
            'title' => ['en' => 'Expired'],
            'stamps_required' => 8,
            'is_active' => true,
            'valid_until' => now()->subDay(),
        ]);

        $response = $this->postJson(
            '/api/agent/v1/member/discover/resolve',
            ['identifier' => $sc->id],
            agentHeaders($key->raw_key)
        );

        $response->assertNotFound();
    }

    public function test_resolve_returns_404_for_future_voucher(): void
    {
        [$member, $key] = createAgentMemberKey();

        $voucher = Voucher::create([
            'id' => Str::uuid()->toString(),
            'club_id' => $this->createMinimalClub(),
            'code' => 'FUTURE10',
            'name' => 'future-voucher',
            'title' => ['en' => 'Future Voucher'],
            'type' => 'percentage',
            'value' => 10,
            'currency' => 'USD',
            'is_active' => true,
            'valid_from' => now()->addDay(),
        ]);

        $response = $this->postJson(
            '/api/agent/v1/member/discover/resolve',
            ['identifier' => $voucher->id],
            agentHeaders($key->raw_key)
        );

        $response->assertNotFound();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // FOLLOW / UNFOLLOW (POST /discover/follow, /discover/unfollow)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_follow_saves_card_to_my_cards(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['read', 'write:profile']]);
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        // Verify member is NOT following
        $this->assertFalse($card->members()->where('members.id', $member->id)->exists());

        $response = $this->postJson(
            '/api/agent/v1/member/discover/follow',
            ['card_id' => $card->id],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'followed')
            ->assertJsonPath('data.card_id', $card->id);

        // Verify member IS now following
        $this->assertTrue($card->members()->where('members.id', $member->id)->exists());
    }

    public function test_follow_is_idempotent(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['read', 'write:profile']]);
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        // Follow twice
        $this->postJson('/api/agent/v1/member/discover/follow',
            ['card_id' => $card->id], agentHeaders($key->raw_key));

        $response = $this->postJson('/api/agent/v1/member/discover/follow',
            ['card_id' => $card->id], agentHeaders($key->raw_key));

        $response->assertOk()
            ->assertJsonPath('data.status', 'followed');

        // Should only have one pivot row
        $this->assertEquals(1, $card->members()->where('members.id', $member->id)->count());
    }

    public function test_unfollow_removes_card_from_my_cards(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['read', 'write:profile']]);
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id);

        // First follow
        $card->members()->attach($member->id);

        $response = $this->postJson(
            '/api/agent/v1/member/discover/unfollow',
            ['card_id' => $card->id],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'unfollowed');

        $this->assertFalse($card->members()->where('members.id', $member->id)->exists());
    }

    public function test_follow_requires_write_profile_scope(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['read']]);

        $response = $this->postJson(
            '/api/agent/v1/member/discover/follow',
            ['card_id' => 'some-id'],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(403)
            ->assertJsonPath('code', 'AUTH_INSUFFICIENT_SCOPE');
    }

    public function test_follow_returns_422_without_card_id(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['write:profile']]);

        $response = $this->postJson(
            '/api/agent/v1/member/discover/follow',
            [],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(422)
            ->assertJsonPath('code', 'MISSING_CARD_ID');
    }

    public function test_follow_returns_404_for_nonexistent_card(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['write:profile']]);

        $response = $this->postJson(
            '/api/agent/v1/member/discover/follow',
            ['card_id' => Str::uuid()->toString()],
            agentHeaders($key->raw_key)
        );

        $response->assertNotFound();
    }

    public function test_follow_returns_404_for_expired_card(): void
    {
        [$member, $key] = createAgentMemberKey(['scopes' => ['write:profile']]);
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        $card = createAgentCard($partner->id, $club->id, [
            'expiration_date' => now()->subDay(),
        ]);

        $this->postJson(
            '/api/agent/v1/member/discover/follow',
            ['card_id' => $card->id],
            agentHeaders($key->raw_key)
        )->assertNotFound();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ROLE ISOLATION
    // ═══════════════════════════════════════════════════════════════════════

    public function test_partner_key_cannot_access_discover(): void
    {
        [, $key] = createAgentPartner();

        $response = $this->getJson(
            '/api/agent/v1/member/discover',
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(403)
            ->assertJsonPath('code', 'AUTH_WRONG_ROLE');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Create a minimal club for stamp cards/vouchers that need a club_id.
     */
    private function createMinimalClub(): string
    {
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);

        return $club->id;
    }
}
