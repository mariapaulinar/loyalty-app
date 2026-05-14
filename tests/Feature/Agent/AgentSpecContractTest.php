<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Contract test: verifies the generated agent-api.json spec matches
 * the canonical endpoint catalog in RewardLoyalty-102-api-agents.md.
 *
 * This prevents operationId drift, missing endpoints, and schema
 * regressions. Run after any controller annotation change.
 *
 * @see App\Http\Controllers\Api\Agent (controller namespace)
 */

namespace Tests\Feature\Agent;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AgentSpecContractTest extends TestCase
{
    /**
     * The canonical operationId catalog — authoritative list from §3 of
     * RewardLoyalty-102-api-agents.md. If you add or rename an endpoint,
     * update BOTH the spec doc AND this array.
     */
    private const CANONICAL_OPERATION_IDS = [
        // §3.1 Shared
        'check_health',
        'discover_tools',

        // §3.2 Partner — Clubs
        'list_clubs', 'get_club', 'create_club', 'update_club', 'delete_club',

        // §3.2 Partner — Loyalty Cards
        'list_loyalty_cards', 'get_loyalty_card', 'create_loyalty_card', 'update_loyalty_card', 'delete_loyalty_card',

        // §3.2 Partner — Rewards
        'list_rewards', 'get_reward', 'create_reward', 'update_reward', 'delete_reward',

        // §3.2 Partner — Transactions
        'list_transactions', 'record_purchase', 'redeem_reward',

        // §3.2 Partner — Members
        'list_members', 'get_member', 'get_member_balance',

        // §3.2 Partner — Stamp Cards
        'list_stamp_cards', 'get_stamp_card', 'create_stamp_card', 'update_stamp_card', 'delete_stamp_card',
        'add_stamps', 'redeem_stamp_reward',

        // §3.2 Partner — Vouchers
        'list_vouchers', 'get_voucher', 'create_voucher', 'update_voucher', 'delete_voucher',
        'validate_voucher', 'redeem_voucher',

        // §3.2 Partner — Tiers
        'list_tiers', 'get_tier', 'create_tier', 'update_tier', 'delete_tier',

        // §3.2 Partner — Staff
        'list_staff', 'get_staff_member', 'create_staff_member', 'update_staff_member', 'delete_staff_member',

        // §3.3 Admin — Partners
        'admin_list_partners', 'admin_get_partner', 'admin_update_partner_permissions',
        'admin_activate_partner', 'admin_deactivate_partner',

        // §3.3 Admin — Members
        'admin_list_members', 'admin_get_member',

        // §3.3 Admin — Analytics
        'admin_analytics_overview', 'admin_partner_metrics',

        // §3.4 Member — Profile
        'member_get_profile', 'member_update_profile',

        // §3.4 Member — Wallet
        'member_get_balance', 'member_list_cards', 'member_get_card',
        'member_list_transactions', 'member_card_transactions',

        // §3.4 Member — Rewards
        'member_list_rewards', 'member_claim_reward',

        // §3.4 Member — Discover
        'member_discover', 'member_resolve_card', 'member_follow_card', 'member_unfollow_card',
    ];

    /**
     * Generate a fresh spec before every test so we never validate
     * stale output. This closes the "passing against old JSON" risk.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Remove any stale spec so a failed generation can't hide behind it
        $specPath = storage_path('api-docs/agent-api.json');
        if (file_exists($specPath)) {
            unlink($specPath);
        }

        // Generate fresh spec and assert success
        $this->artisan('l5-swagger:generate', ['documentation' => 'agent'])
            ->assertExitCode(0);
    }

    private function loadSpec(): array
    {
        $path = storage_path('api-docs/agent-api.json');

        $this->assertFileExists($path, 'agent-api.json must exist after generation');

        $spec = json_decode(file_get_contents($path), true);
        $this->assertNotNull($spec, 'agent-api.json is not valid JSON');

        return $spec;
    }

    private function extractOperationIds(array $spec): array
    {
        $ids = [];
        foreach ($spec['paths'] ?? [] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    $ids[] = $operation['operationId'] ?? "MISSING:{$method}:{$path}";
                }
            }
        }

        return $ids;
    }

    // ─────────────────────────────────────────────────────────────────────
    // TESTS
    // ─────────────────────────────────────────────────────────────────────

    public function test_all_canonical_operation_ids_exist_in_spec(): void
    {
        $spec = $this->loadSpec();
        $generated = $this->extractOperationIds($spec);
        $missing = array_diff(self::CANONICAL_OPERATION_IDS, $generated);

        $this->assertEmpty(
            $missing,
            'Canonical operationIds missing from agent-api.json: ' . implode(', ', $missing)
        );
    }

    public function test_no_extra_operation_ids_in_spec(): void
    {
        $spec = $this->loadSpec();
        $generated = $this->extractOperationIds($spec);
        $extra = array_diff($generated, self::CANONICAL_OPERATION_IDS);

        $this->assertEmpty(
            $extra,
            'Unexpected operationIds in agent-api.json (not in canonical catalog): ' . implode(', ', $extra)
        );
    }

    public function test_no_duplicate_operation_ids(): void
    {
        $spec = $this->loadSpec();
        $ids = $this->extractOperationIds($spec);
        $counts = array_count_values($ids);
        $duplicates = array_filter($counts, fn ($count) => $count > 1);

        $this->assertEmpty(
            $duplicates,
            'Duplicate operationIds in agent-api.json: ' . implode(', ', array_keys($duplicates))
        );
    }

    public function test_all_endpoints_have_agent_key_security(): void
    {
        $spec = $this->loadSpec();

        foreach ($spec['paths'] ?? [] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    continue;
                }

                $opId = $operation['operationId'] ?? "{$method}:{$path}";
                $security = $operation['security'] ?? [];
                $hasAgentKey = false;

                foreach ($security as $scheme) {
                    if (array_key_exists('AgentKey', $scheme)) {
                        $hasAgentKey = true;
                        break;
                    }
                }

                $this->assertTrue(
                    $hasAgentKey,
                    "Endpoint {$opId} ({$method} {$path}) missing AgentKey security scheme"
                );
            }
        }
    }

    public function test_all_endpoints_have_x_agent_scopes(): void
    {
        $spec = $this->loadSpec();

        foreach ($spec['paths'] ?? [] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    continue;
                }

                $opId = $operation['operationId'] ?? "{$method}:{$path}";

                // /health and /tools have no scope requirement — skip them
                if (in_array($path, ['/health', '/tools'], true)) {
                    continue;
                }

                $this->assertArrayHasKey(
                    'x-agent-scopes',
                    $operation,
                    "Endpoint {$opId} ({$method} {$path}) missing x-agent-scopes extension"
                );

                $this->assertNotEmpty(
                    $operation['x-agent-scopes'],
                    "Endpoint {$opId} ({$method} {$path}) has empty x-agent-scopes"
                );
            }
        }
    }

    public function test_admin_endpoints_use_admin_prefix(): void
    {
        $spec = $this->loadSpec();

        foreach ($spec['paths'] ?? [] as $path => $methods) {
            if (! str_starts_with($path, '/admin/')) {
                continue;
            }

            foreach ($methods as $method => $operation) {
                if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    continue;
                }

                $opId = $operation['operationId'] ?? '';
                $this->assertStringStartsWith(
                    'admin_',
                    $opId,
                    "Admin endpoint {$method} {$path} must use admin_ prefix, got: {$opId}"
                );
            }
        }
    }

    public function test_member_endpoints_use_member_prefix(): void
    {
        $spec = $this->loadSpec();

        foreach ($spec['paths'] ?? [] as $path => $methods) {
            if (! str_starts_with($path, '/member/')) {
                continue;
            }

            foreach ($methods as $method => $operation) {
                if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    continue;
                }

                $opId = $operation['operationId'] ?? '';
                $this->assertStringStartsWith(
                    'member_',
                    $opId,
                    "Member endpoint {$method} {$path} must use member_ prefix, got: {$opId}"
                );
            }
        }
    }

    public function test_partner_endpoints_have_no_role_prefix(): void
    {
        $spec = $this->loadSpec();

        foreach ($spec['paths'] ?? [] as $path => $methods) {
            if (! str_starts_with($path, '/partner/')) {
                continue;
            }

            foreach ($methods as $method => $operation) {
                if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    continue;
                }

                $opId = $operation['operationId'] ?? '';
                $this->assertStringDoesNotContain(
                    'partner_',
                    $opId,
                    "Partner endpoint {$method} {$path} must NOT use partner_ prefix, got: {$opId}"
                );
            }
        }
    }

    /**
     * Verify specific schema field corrections are in place.
     * Guards against regression of all P1 schema issues.
     */
    public function test_schema_field_correctness(): void
    {
        $spec = $this->loadSpec();
        $schemas = $spec['components']['schemas'] ?? [];

        // ── Removed schemas must not exist ──────────────────────────────
        $this->assertArrayNotHasKey('Transaction', $schemas, 'Generic Transaction schema must be removed — split into PartnerTransaction + MemberTransaction');
        $this->assertArrayNotHasKey('MemberBalance', $schemas, 'MemberBalance schema must be removed — balance is inline array');

        // ── Required schemas must exist ─────────────────────────────────
        foreach (['PartnerTransaction', 'MemberTransaction', 'PartnerMemberSummary', 'MemberProfile', 'MemberSummary', 'MemberCard', 'PartnerPermissions', 'PartnerDetail', 'AdminMemberDetail'] as $required) {
            $this->assertArrayHasKey($required, $schemas, "Schema {$required} must exist");
        }

        // ── Reward: no card_id (linked via pivot) ───────────────────────
        $rewardProps = array_keys($schemas['Reward']['properties'] ?? []);
        $this->assertNotContains('card_id', $rewardProps, 'Reward schema must not have card_id');
        $this->assertContains('name', $rewardProps, 'Reward schema must have name');
        $this->assertContains('active_from', $rewardProps, 'Reward must have active_from');

        // ── StampCard: stamps_required not max_stamps ───────────────────
        $scProps = array_keys($schemas['StampCard']['properties'] ?? []);
        $this->assertNotContains('max_stamps', $scProps, 'StampCard must not have max_stamps');
        $this->assertContains('stamps_required', $scProps, 'StampCard must have stamps_required');
        $this->assertContains('stamps_per_purchase', $scProps);
        $this->assertContains('requires_physical_claim', $scProps);

        // ── Tier: club_id + level, not card_id + points_from ───────────
        $tierProps = array_keys($schemas['Tier']['properties'] ?? []);
        $this->assertNotContains('card_id', $tierProps, 'Tier must not have card_id');
        $this->assertNotContains('points_from', $tierProps, 'Tier must not have points_from');
        $this->assertContains('club_id', $tierProps, 'Tier must have club_id');
        $this->assertContains('level', $tierProps, 'Tier must have level');
        $this->assertContains('points_threshold', $tierProps);
        $this->assertContains('is_default', $tierProps);

        // ── MemberProfile: actual serializer fields ─────────────────────
        $mpProps = array_keys($schemas['MemberProfile']['properties'] ?? []);
        $this->assertNotContains('member_number', $mpProps, 'MemberProfile must not have member_number');
        $this->assertNotContains('is_active', $mpProps, 'MemberProfile must not have is_active');
        $this->assertContains('avatar', $mpProps, 'MemberProfile must have avatar');
        $this->assertContains('is_anonymous', $mpProps, 'MemberProfile must have is_anonymous');
        $this->assertContains('has_interacted', $mpProps, 'MemberProfile must have has_interacted');
        $this->assertContains('first_interaction_at', $mpProps, 'MemberProfile must have first_interaction_at');

        // ── MemberSummary (admin): actual admin serializer ──────────────
        $msProps = array_keys($schemas['MemberSummary']['properties'] ?? []);
        $this->assertNotContains('member_number', $msProps, 'MemberSummary must not have member_number');
        $this->assertContains('is_anonymous', $msProps, 'MemberSummary must have is_anonymous');
        $this->assertContains('is_active', $msProps, 'MemberSummary must have is_active');

        // ── PartnerMemberSummary: actual partner serializer ─────────────
        $pmsProps = array_keys($schemas['PartnerMemberSummary']['properties'] ?? []);
        $this->assertContains('currency', $pmsProps, 'PartnerMemberSummary must have currency');
        $this->assertContains('time_zone', $pmsProps, 'PartnerMemberSummary must have time_zone');
        $this->assertContains('last_login_at', $pmsProps, 'PartnerMemberSummary must have last_login_at');
        $this->assertContains('avatar', $pmsProps, 'PartnerMemberSummary must have avatar');
        $this->assertContains('is_anonymous', $pmsProps, 'PartnerMemberSummary must have is_anonymous');

        // ── PartnerTransaction: includes staff + reward attribution ─────
        $ptProps = array_keys($schemas['PartnerTransaction']['properties'] ?? []);
        $this->assertContains('currency', $ptProps, 'PartnerTransaction must have currency');
        $this->assertContains('reward_id', $ptProps, 'PartnerTransaction must have reward_id');
        $this->assertContains('staff_name', $ptProps, 'PartnerTransaction must have staff_name');
        $this->assertContains('expires_at', $ptProps, 'PartnerTransaction must have expires_at');

        // ── MemberTransaction: includes display fields ──────────────────
        $mtProps = array_keys($schemas['MemberTransaction']['properties'] ?? []);
        $this->assertContains('card_title', $mtProps, 'MemberTransaction must have card_title');
        $this->assertContains('points_used', $mtProps, 'MemberTransaction must have points_used');
        $this->assertContains('reward_title', $mtProps, 'MemberTransaction must have reward_title');
        $this->assertContains('reward_points', $mtProps, 'MemberTransaction must have reward_points');

        // ── PartnerSummary: full serializer fields ──────────────────────
        $psProps = array_keys($schemas['PartnerSummary']['properties'] ?? []);
        $this->assertContains('locale', $psProps, 'PartnerSummary must have locale');
        $this->assertContains('currency', $psProps, 'PartnerSummary must have currency');
        $this->assertContains('time_zone', $psProps, 'PartnerSummary must have time_zone');
        $this->assertContains('avatar', $psProps, 'PartnerSummary must have avatar');

        // ── MemberCard: member-facing card with balance ─────────────────
        $mcProps = array_keys($schemas['MemberCard']['properties'] ?? []);
        $this->assertContains('name', $mcProps, 'MemberCard must have name');
        $this->assertContains('title', $mcProps, 'MemberCard must have title');
        $this->assertContains('balance', $mcProps, 'MemberCard must have balance');
        $this->assertContains('currency', $mcProps, 'MemberCard must have currency');
        $this->assertContains('bg_color', $mcProps, 'MemberCard must have bg_color');
        $this->assertContains('text_color', $mcProps, 'MemberCard must have text_color');
        $this->assertContains('is_active', $mcProps, 'MemberCard must have is_active');
    }

    /**
     * Verify the admin_update_partner_permissions request body
     * documents all 14 writable fields from the validator.
     */
    public function test_admin_permissions_request_body_is_complete(): void
    {
        $spec = $this->loadSpec();

        $patchOp = null;
        foreach ($spec['paths'] ?? [] as $path => $methods) {
            if (str_contains($path, '/admin/partners/') && str_contains($path, '/permissions')) {
                $patchOp = $methods['patch'] ?? null;
                break;
            }
        }

        $this->assertNotNull($patchOp, 'PATCH /admin/partners/{id}/permissions must exist');

        $bodySchema = $patchOp['requestBody']['content']['application/json']['schema'] ?? [];
        $bodyProps = array_keys($bodySchema['properties'] ?? []);

        $requiredFields = [
            'loyalty_cards_permission', 'loyalty_cards_limit',
            'stamp_cards_permission', 'stamp_cards_limit',
            'vouchers_permission', 'voucher_batches_permission', 'vouchers_limit',
            'rewards_limit', 'staff_members_limit',
            'email_campaigns_permission', 'activity_permission',
            'agent_api_permission', 'agent_keys_limit',
            'cards_on_homepage',
        ];

        foreach ($requiredFields as $field) {
            $this->assertContains(
                $field,
                $bodyProps,
                "admin_update_partner_permissions request body must document '{$field}'"
            );
        }
    }

    // Helper for the partner prefix test
    private function assertStringDoesNotContain(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertFalse(
            str_starts_with($haystack, $needle),
            $message ?: "String '{$haystack}' unexpectedly starts with '{$needle}'"
        );
    }
}
