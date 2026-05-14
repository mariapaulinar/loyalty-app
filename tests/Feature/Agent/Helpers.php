<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shared test helpers for Agent API tests.
 *
 * These functions create models with the exact attributes needed
 * for agent testing — partner with key, proper ownership chains,
 * and configurable scopes. Every agent test file should require
 * this helper file.
 *
 * Design principle: Each helper does ONE thing. Compose them
 * for complex scenarios. No god-helpers.
 */

use App\Models\Admin;
use App\Models\AgentKey;
use App\Models\Card;
use App\Models\Club;
use App\Models\Member;
use App\Models\Partner;
use App\Models\Reward;
use Illuminate\Support\Str;

// ═══════════════════════════════════════════════════════════════════════════
// PARTNER + KEY CREATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Create a partner with an agent key, ready for API testing.
 *
 * Returns [Partner, AgentKey] — the key's raw_key is available
 * immediately after creation for use in test requests.
 *
 * @param  array  $keyOverrides  Override default key attributes (scopes, rate_limit, etc.)
 * @param  array  $partnerOverrides  Override default partner attributes
 * @return array{0: Partner, 1: AgentKey}
 */
function createAgentPartner(array $keyOverrides = [], array $partnerOverrides = []): array
{
    $partner = Partner::factory()->create(array_replace_recursive([
        'is_active' => true,
        'meta' => [
            'agent_api_permission' => true,
        ],
    ], $partnerOverrides));

    $key = AgentKey::create(array_merge([
        'owner_type' => Partner::class,
        'owner_id' => $partner->id,
        'name' => 'Test Key',
        'scopes' => ['admin'],
    ], $keyOverrides));

    return [$partner, $key];
}

/**
 * Shorthand: create partner + key and return only the raw key string.
 *
 * For tests that just need to make API calls and don't need
 * the model references.
 */
function createAgentKey(array $keyOverrides = [], array $partnerOverrides = []): string
{
    [, $key] = createAgentPartner($keyOverrides, $partnerOverrides);

    return $key->raw_key;
}

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN + KEY CREATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Create an admin with an agent key.
 *
 * @return array{0: Admin, 1: AgentKey}
 */
function createAgentAdmin(array $keyOverrides = [], array $adminOverrides = []): array
{
    $admin = Admin::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Test Admin',
        'email' => 'admin' . Str::random(5) . '@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'locale' => 'en_US',
        'time_zone' => 'UTC',
        'currency' => 'USD',
        'is_active' => true,
    ], $adminOverrides));

    $key = AgentKey::create(array_merge([
        'owner_type' => Admin::class,
        'owner_id' => $admin->id,
        'name' => 'Admin Test Key',
        'scopes' => ['admin'],
    ], $keyOverrides));

    return [$admin, $key];
}

// ═══════════════════════════════════════════════════════════════════════════
// RESOURCE CREATION (for endpoint tests)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Create a club owned by a specific partner.
 */
function createAgentClub(string $partnerId, array $overrides = []): Club
{
    return Club::factory()->create(array_merge([
        'created_by' => $partnerId,
    ], $overrides));
}

/**
 * Create a loyalty card owned by a specific partner, in a specific club.
 */
function createAgentCard(string $partnerId, string $clubId, array $overrides = []): Card
{
    return Card::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Test Card',
        'head' => ['en' => 'Test Card'],
        'club_id' => $clubId,
        'created_by' => $partnerId,
        'currency' => 'USD',
        'currency_unit_amount' => 1,
        'points_per_currency' => 100,
        'min_points_per_purchase' => 1,
        'max_points_per_purchase' => 100000,
        'points_expiration_months' => 12,
        'is_active' => true,
    ], $overrides));
}

/**
 * Create a member (customer) for transaction tests.
 */
function createAgentMember(array $overrides = []): Member
{
    return Member::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Test Member',
        'email' => 'member' . Str::random(5) . '@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'locale' => 'en_US',
        'time_zone' => 'UTC',
        'currency' => 'USD',
        'is_active' => true,
    ], $overrides));
}

// ═══════════════════════════════════════════════════════════════════════════
// MEMBER + KEY CREATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Create a member with an agent key, ready for API testing.
 *
 * @return array{0: Member, 1: AgentKey}
 */
function createAgentMemberKey(array $keyOverrides = [], array $memberOverrides = []): array
{
    $member = createAgentMember($memberOverrides);

    $key = AgentKey::create(array_merge([
        'owner_type' => Member::class,
        'owner_id' => $member->id,
        'name' => 'Member Test Key',
        'scopes' => ['read'],
    ], $keyOverrides));

    return [$member, $key];
}

/**
 * Create a reward linked to a card for claim testing.
 */
function createAgentReward(string $partnerId, string $cardId, array $overrides = []): Reward
{
    $reward = Reward::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'test-reward-' . Str::random(5),
        'title' => ['en' => 'Test Reward'],
        'description' => ['en' => 'Test reward description'],
        'points' => 100,
        'created_by' => $partnerId,
        'is_active' => true,
    ], $overrides));

    // Link reward to card
    $reward->cards()->attach($cardId);

    return $reward;
}

// ═══════════════════════════════════════════════════════════════════════════
// REQUEST HELPERS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Build the headers array for an agent API request.
 */
function agentHeaders(string $rawKey, array $extra = []): array
{
    return array_merge([
        'X-Agent-Key' => $rawKey,
        'Accept' => 'application/json',
    ], $extra);
}
