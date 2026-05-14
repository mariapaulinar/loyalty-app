<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Tests for the Admin Agent API endpoints.
 *
 * Covers: partner management (list, show, permissions, activate/deactivate),
 * platform-wide member access, analytics, scope enforcement, role isolation.
 *
 * @see App\Http\Controllers\Api\Agent\Admin
 */

namespace Tests\Feature\Agent;

use App\Models\AgentKey;
use App\Models\Partner;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

require_once __DIR__ . '/Helpers.php';

class AdminEndpointTest extends TestCase
{
    use RefreshDatabase;

    // ═══════════════════════════════════════════════════════════════════════
    // PARTNERS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_admin_can_list_partners(): void
    {
        [$admin, $key] = createAgentAdmin();
        Partner::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson(
            '/api/agent/v1/admin/partners',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonStructure(['data', 'pagination'])
            ->assertJsonPath('pagination.total', 3);
    }

    public function test_admin_can_filter_partners_by_active(): void
    {
        [$admin, $key] = createAgentAdmin();
        Partner::factory()->count(2)->create(['is_active' => true]);
        Partner::factory()->count(1)->create(['is_active' => false]);

        $response = $this->getJson(
            '/api/agent/v1/admin/partners?is_active=true',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('pagination.total', 2);
    }

    public function test_admin_can_search_partners(): void
    {
        [$admin, $key] = createAgentAdmin();
        Partner::factory()->create(['name' => 'Coffee Corner', 'is_active' => true]);
        Partner::factory()->create(['name' => 'Pizza Palace', 'is_active' => true]);

        $response = $this->getJson(
            '/api/agent/v1/admin/partners?search=Coffee',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Coffee Corner');
    }

    public function test_admin_can_show_partner_with_permissions(): void
    {
        [$admin, $key] = createAgentAdmin();
        $partner = Partner::factory()->create(['is_active' => true]);

        $response = $this->getJson(
            "/api/agent/v1/admin/partners/{$partner->id}",
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $partner->id)
            ->assertJsonStructure(['data' => ['permissions', 'usage']]);
    }

    public function test_admin_show_returns_404_for_nonexistent_partner(): void
    {
        [$admin, $key] = createAgentAdmin();

        $response = $this->getJson(
            '/api/agent/v1/admin/partners/' . Str::uuid()->toString(),
            agentHeaders($key->raw_key)
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_admin_can_update_permissions(): void
    {
        [$admin, $key] = createAgentAdmin();
        $partner = Partner::factory()->create(['is_active' => true]);

        $response = $this->patchJson(
            "/api/agent/v1/admin/partners/{$partner->id}/permissions",
            ['agent_api_permission' => true, 'agent_keys_limit' => 10],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.agent_api_permission', true)
            ->assertJsonPath('data.agent_keys_limit', 10);

        // Verify persistence
        $partner->refresh();
        $this->assertTrue(($partner->meta ?? [])['agent_api_permission']);
        $this->assertEquals(10, ($partner->meta ?? [])['agent_keys_limit']);
    }

    public function test_admin_permissions_rejects_empty_body(): void
    {
        [$admin, $key] = createAgentAdmin();
        $partner = Partner::factory()->create(['is_active' => true]);

        $response = $this->patchJson(
            "/api/agent/v1/admin/partners/{$partner->id}/permissions",
            [],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonPath('details.errors.body.0', 'At least one permission field with a non-null value is required.');
    }

    public function test_admin_permissions_rejects_all_null_values(): void
    {
        [$admin, $key] = createAgentAdmin();
        $partner = Partner::factory()->create(['is_active' => true]);

        $response = $this->patchJson(
            "/api/agent/v1/admin/partners/{$partner->id}/permissions",
            ['loyalty_cards_permission' => null, 'agent_api_permission' => null],
            agentHeaders($key->raw_key)
        );

        // All values are null → no actual changes → 422
        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_admin_can_deactivate_partner(): void
    {
        [$admin, $key] = createAgentAdmin();
        $partner = Partner::factory()->create(['is_active' => true]);

        $response = $this->postJson(
            "/api/agent/v1/admin/partners/{$partner->id}/deactivate",
            [],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse((bool) $partner->fresh()->is_active);
    }

    public function test_admin_can_activate_partner(): void
    {
        [$admin, $key] = createAgentAdmin();
        $partner = Partner::factory()->create(['is_active' => false]);

        $response = $this->postJson(
            "/api/agent/v1/admin/partners/{$partner->id}/activate",
            [],
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertTrue((bool) $partner->fresh()->is_active);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MEMBERS (read-only)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_admin_can_list_members(): void
    {
        [$admin, $key] = createAgentAdmin();
        createAgentMember();
        createAgentMember();

        $response = $this->getJson(
            '/api/agent/v1/admin/members',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('pagination.total', 2);
    }

    public function test_admin_can_search_members(): void
    {
        [$admin, $key] = createAgentAdmin();
        createAgentMember(['name' => 'Jane Doe']);
        createAgentMember(['name' => 'John Smith']);

        $response = $this->getJson(
            '/api/agent/v1/admin/members?search=Jane',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Jane Doe');
    }

    public function test_admin_can_show_member_details(): void
    {
        [$admin, $key] = createAgentAdmin();
        $member = createAgentMember();

        $response = $this->getJson(
            "/api/agent/v1/admin/members/{$member->id}",
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonStructure(['data' => ['card_balances']]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ANALYTICS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_admin_can_view_analytics_overview(): void
    {
        [$admin, $key] = createAgentAdmin();
        Partner::factory()->count(2)->create(['is_active' => true]);
        createAgentMember();

        $response = $this->getJson(
            '/api/agent/v1/admin/analytics/overview',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonStructure(['data' => [
                'total_partners',
                'active_partners',
                'total_members',
                'total_cards',
                'transactions_today',
            ]])
            ->assertJsonPath('data.total_partners', 2)
            ->assertJsonPath('data.total_members', 1);
    }

    public function test_admin_can_view_partner_metrics(): void
    {
        [$admin, $key] = createAgentAdmin();
        [$partner] = createAgentPartner();
        $club = createAgentClub($partner->id);
        createAgentCard($partner->id, $club->id);

        $response = $this->getJson(
            "/api/agent/v1/admin/analytics/partners/{$partner->id}",
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.partner_id', $partner->id)
            ->assertJsonPath('data.loyalty_cards', 1)
            ->assertJsonStructure(['data' => [
                'partner_name',
                'loyalty_cards',
                'stamp_cards',
                'vouchers',
                'rewards',
                'staff_members',
                'total_transactions',
            ]]);
    }

    public function test_analytics_returns_404_for_nonexistent_partner(): void
    {
        [$admin, $key] = createAgentAdmin();

        $response = $this->getJson(
            '/api/agent/v1/admin/analytics/partners/' . Str::uuid()->toString(),
            agentHeaders($key->raw_key)
        );

        $response->assertNotFound();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPE ENFORCEMENT
    // ═══════════════════════════════════════════════════════════════════════

    public function test_read_scope_cannot_update_permissions(): void
    {
        [$admin, $key] = createAgentAdmin(['scopes' => ['read:partners']]);
        $partner = Partner::factory()->create(['is_active' => true]);

        $response = $this->patchJson(
            "/api/agent/v1/admin/partners/{$partner->id}/permissions",
            ['agent_api_permission' => true],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(403)
            ->assertJsonPath('code', 'AUTH_INSUFFICIENT_SCOPE');
    }

    public function test_read_scope_cannot_deactivate(): void
    {
        [$admin, $key] = createAgentAdmin(['scopes' => ['read:partners']]);
        $partner = Partner::factory()->create(['is_active' => true]);

        $response = $this->postJson(
            "/api/agent/v1/admin/partners/{$partner->id}/deactivate",
            [],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(403)
            ->assertJsonPath('code', 'AUTH_INSUFFICIENT_SCOPE');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ROLE ISOLATION
    // ═══════════════════════════════════════════════════════════════════════

    public function test_partner_key_cannot_access_admin_endpoints(): void
    {
        [, $key] = createAgentPartner();

        $response = $this->getJson(
            '/api/agent/v1/admin/partners',
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(403)
            ->assertJsonPath('code', 'AUTH_WRONG_ROLE');
    }

    public function test_member_key_cannot_access_admin_endpoints(): void
    {
        [, $key] = createAgentMemberKey();

        $response = $this->getJson(
            '/api/agent/v1/admin/partners',
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(403)
            ->assertJsonPath('code', 'AUTH_WRONG_ROLE');
    }

    public function test_admin_health_endpoint_works(): void
    {
        [, $key] = createAgentAdmin();

        $response = $this->getJson(
            '/api/agent/v1/health',
            agentHeaders($key->raw_key)
        );

        $response->assertOk()
            ->assertJsonPath('data.key.role', 'admin')
            ->assertJsonPath('data.status', 'ok');
    }
}
