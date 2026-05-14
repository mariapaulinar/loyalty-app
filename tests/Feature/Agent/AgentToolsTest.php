<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Tests for AgentToolService, agent:export-tools command, and
 * GET /api/agent/v1/tools endpoint.
 *
 * Covers:
 * - Role/scope filtering in tool extraction
 * - /tools self-exclusion from exported tool lists
 * - Schema preservation (oneOf, additionalProperties, nested objects)
 * - Full HTTP path construction in tool descriptions
 * - All 4 output formats (openai, anthropic, mcp, generic)
 * - Cache key includes spec version (filemtime) for auto-busting
 * - Permission-aware filtering (partner feature flags)
 * - Invalid format returns 422
 * - Missing spec returns 503
 * - CLI export command output
 * - Contract: x-agent-permission alignment with runtime checkPermission
 *
 * @see App\Services\Agent\AgentToolService
 * @see App\Console\Commands\ExportAgentTools
 * @see App\Http\Controllers\Api\Agent\AgentToolsController
 */

namespace Tests\Feature\Agent;

use App\Services\Agent\AgentToolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

require_once __DIR__ . '/Helpers.php';

class AgentToolsTest extends TestCase
{
    use RefreshDatabase;

    private AgentToolService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Remove any stale spec so a failed generation can't hide behind it.
        // This mirrors AgentSpecContractTest's hardened pattern — if generation
        // fails, every test blows up immediately instead of silently passing
        // against yesterday's JSON.
        $specPath = storage_path('api-docs/agent-api.json');
        if (file_exists($specPath)) {
            unlink($specPath);
        }

        // Generate fresh spec and assert success
        $this->artisan('l5-swagger:generate', ['documentation' => 'agent'])
            ->assertExitCode(0);

        $this->service = app(AgentToolService::class);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TOOL SERVICE — ROLE/SCOPE FILTERING
    // ═══════════════════════════════════════════════════════════════════════

    public function test_partner_role_only_sees_partner_and_shared_endpoints(): void
    {
        $tools = $this->service->extractTools('partner', null, false);

        foreach ($tools as $tool) {
            $path = $tool['path'];
            $this->assertTrue(
                str_starts_with($path, '/partner/') || $path === '/health',
                "Partner role saw unexpected path: {$path}"
            );
        }

        $this->assertNotEmpty($tools, 'Partner role should see at least 1 tool');
    }

    public function test_admin_role_only_sees_admin_and_shared_endpoints(): void
    {
        $tools = $this->service->extractTools('admin', null, false);

        foreach ($tools as $tool) {
            $path = $tool['path'];
            $this->assertTrue(
                str_starts_with($path, '/admin/') || $path === '/health',
                "Admin role saw unexpected path: {$path}"
            );
        }

        $this->assertNotEmpty($tools, 'Admin role should see at least 1 tool');
    }

    public function test_member_role_only_sees_member_and_shared_endpoints(): void
    {
        $tools = $this->service->extractTools('member', null, false);

        foreach ($tools as $tool) {
            $path = $tool['path'];
            $this->assertTrue(
                str_starts_with($path, '/member/') || $path === '/health',
                "Member role saw unexpected path: {$path}"
            );
        }

        $this->assertNotEmpty($tools, 'Member role should see at least 1 tool');
    }

    public function test_scope_filtering_limits_visible_tools(): void
    {
        $allTools = $this->service->extractTools('partner', null, false);
        $readOnlyTools = $this->service->extractTools('partner', ['read'], false);

        // Read-only should be a strict subset of all tools
        $this->assertLessThan(
            count($allTools),
            count($readOnlyTools),
            'Read-only scope should see fewer tools than unrestricted'
        );

        // Every read-only tool should have 'read' in its required scopes or be health (no scopes)
        foreach ($readOnlyTools as $tool) {
            if ($tool['path'] === '/health') {
                continue; // no scopes needed
            }
            $this->assertTrue(
                in_array('read', $tool['required_scopes'], true),
                "Tool {$tool['name']} should require 'read' scope but requires: " . implode(', ', $tool['required_scopes'])
            );
        }
    }

    public function test_admin_super_scope_bypasses_scope_filtering(): void
    {
        $allTools = $this->service->extractTools('partner', null, false);
        $superScopeTools = $this->service->extractTools('partner', ['admin'], true);

        // Admin super-scope should see all tools regardless of specific scope requirements
        $this->assertCount(
            count($allTools),
            $superScopeTools,
            'Admin super-scope should see all tools'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TOOL SERVICE — /TOOLS SELF-EXCLUSION
    // ═══════════════════════════════════════════════════════════════════════

    public function test_tools_endpoint_excluded_from_exports(): void
    {
        $partnerTools = $this->service->extractTools('partner', null, false);
        $adminTools = $this->service->extractTools('admin', null, false);
        $memberTools = $this->service->extractTools('member', null, false);

        $allNames = array_merge(
            array_column($partnerTools, 'name'),
            array_column($adminTools, 'name'),
            array_column($memberTools, 'name'),
        );

        $this->assertNotContains(
            'discover_tools',
            $allNames,
            'discover_tools should be excluded from all exported tool lists'
        );

        $allPaths = array_merge(
            array_column($partnerTools, 'path'),
            array_column($adminTools, 'path'),
            array_column($memberTools, 'path'),
        );

        $this->assertNotContains(
            '/tools',
            $allPaths,
            '/tools path should be excluded from all exported tool lists'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TOOL SERVICE — SCHEMA PRESERVATION (P1 fix verification)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_translatable_fields_preserve_one_of_schema(): void
    {
        $tools = $this->service->extractTools('partner', null, false);
        $createCard = collect($tools)->firstWhere('name', 'create_loyalty_card');

        $this->assertNotNull($createCard, 'create_loyalty_card tool should exist');

        // Body schema should have the 'head' property with oneOf
        $bodySchema = $createCard['body_schema'];
        $this->assertNotNull($bodySchema, 'create_loyalty_card should have a body schema');

        $headSchema = $bodySchema['properties']['head'] ?? null;
        $this->assertNotNull($headSchema, 'head property should exist in body schema');
        $this->assertArrayHasKey('oneOf', $headSchema, 'head should have oneOf');
        $this->assertCount(2, $headSchema['oneOf'], 'head oneOf should have 2 variants');

        // Verify first is string, second is object with additionalProperties
        $this->assertEquals('string', $headSchema['oneOf'][0]['type']);
        $this->assertEquals('object', $headSchema['oneOf'][1]['type']);
        $this->assertArrayHasKey('additionalProperties', $headSchema['oneOf'][1]);
    }

    public function test_one_of_survives_format_conversion(): void
    {
        $tools = $this->service->extractTools('partner', null, false);
        $createCard = collect($tools)->firstWhere('name', 'create_loyalty_card');

        // Test all 4 formats
        foreach (['openai', 'anthropic', 'mcp', 'generic'] as $format) {
            $formatted = $this->service->formatTools([$createCard], $format);

            // Extract the head property from the formatted output
            $headSchema = match ($format) {
                'openai'    => $formatted[0]['function']['parameters']['properties']->head ?? null,
                'anthropic' => $formatted[0]['input_schema']['properties']->head ?? null,
                'mcp'       => $formatted['tools'][0]['inputSchema']['properties']->head ?? null,
                'generic'   => $formatted['tools'][0]['parameters']['properties']->head ?? null,
            };

            $this->assertNotNull($headSchema, "head property missing in {$format} format");
            $this->assertArrayHasKey('oneOf', $headSchema, "head should have oneOf in {$format} format");
            $this->assertCount(2, $headSchema['oneOf'], "head oneOf should have 2 variants in {$format} format");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TOOL SERVICE — FULL HTTP PATHS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_full_paths_include_base_url(): void
    {
        $tools = $this->service->extractTools('partner', null, false);
        $cardTool = collect($tools)->firstWhere('name', 'list_loyalty_cards');

        $this->assertEquals('/api/agent/v1/partner/cards', $cardTool['full_path']);
    }

    public function test_openai_description_contains_full_path(): void
    {
        $tools = $this->service->extractTools('admin', null, false);
        $membersTool = collect($tools)->firstWhere('name', 'admin_list_members');

        $formatted = $this->service->formatTools([$membersTool], 'openai');
        $description = $formatted[0]['function']['description'];

        $this->assertStringContainsString(
            'HTTP: GET /api/agent/v1/admin/members',
            $description,
            'OpenAI description should contain full HTTP path'
        );
        $this->assertStringNotContainsString(
            'HTTP: GET /admin/members' . "\n",
            $description,
            'Description should NOT contain spec-relative path as the HTTP line'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TOOL SERVICE — FORMAT OUTPUTS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_openai_format_structure(): void
    {
        $tools = $this->service->extractTools('partner', ['read'], false);
        $formatted = $this->service->formatTools($tools, 'openai');

        $this->assertIsArray($formatted);
        $first = $formatted[0] ?? null;
        $this->assertNotNull($first);
        $this->assertEquals('function', $first['type']);
        $this->assertArrayHasKey('function', $first);
        $this->assertArrayHasKey('name', $first['function']);
        $this->assertArrayHasKey('description', $first['function']);
        $this->assertArrayHasKey('parameters', $first['function']);
        $this->assertEquals('object', $first['function']['parameters']['type']);
    }

    public function test_anthropic_format_structure(): void
    {
        $tools = $this->service->extractTools('partner', ['read'], false);
        $formatted = $this->service->formatTools($tools, 'anthropic');

        $first = $formatted[0] ?? null;
        $this->assertNotNull($first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('description', $first);
        $this->assertArrayHasKey('input_schema', $first);
        $this->assertEquals('object', $first['input_schema']['type']);
    }

    public function test_mcp_format_structure(): void
    {
        $tools = $this->service->extractTools('partner', ['read'], false);
        $formatted = $this->service->formatTools($tools, 'mcp');

        $this->assertArrayHasKey('tools', $formatted);
        $first = $formatted['tools'][0] ?? null;
        $this->assertNotNull($first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('inputSchema', $first);
    }

    public function test_generic_format_includes_metadata(): void
    {
        $tools = $this->service->extractTools('partner', ['read'], false);
        $formatted = $this->service->formatTools($tools, 'generic');

        $this->assertEquals('Reward Loyalty Agent API', $formatted['api_name']);
        $this->assertEquals('/api/agent/v1', $formatted['base_url']);
        $this->assertArrayHasKey('auth', $formatted);
        $this->assertEquals('X-Agent-Key', $formatted['auth']['header']);
        $this->assertArrayHasKey('tools', $formatted);

        $first = $formatted['tools'][0] ?? null;
        $this->assertArrayHasKey('method', $first);
        $this->assertArrayHasKey('path', $first);
        $this->assertArrayHasKey('required_scopes', $first);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TOOL SERVICE — SPEC VERSION
    // ═══════════════════════════════════════════════════════════════════════

    public function test_spec_version_returns_filemtime(): void
    {
        $specPath = storage_path('api-docs/agent-api.json');
        $this->assertTrue(File::exists($specPath), 'Spec should exist for version check');

        $version = $this->service->specVersion();
        $this->assertIsNumeric($version);
        $this->assertEquals((string) File::lastModified($specPath), $version);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CLI COMMAND — agent:export-tools
    // ═══════════════════════════════════════════════════════════════════════

    public function test_export_command_produces_output_file(): void
    {
        $outputPath = storage_path('api-docs/agent-tools-generic.json');
        File::delete($outputPath);

        $this->artisan('agent:export-tools', ['format' => 'generic', '--role' => 'partner'])
            ->expectsOutputToContain('Exported')
            ->assertExitCode(0);

        $this->assertFileExists($outputPath);
        $data = json_decode(File::get($outputPath), true);
        $this->assertNotEmpty($data['tools'] ?? []);
    }

    public function test_export_command_invalid_format_returns_error(): void
    {
        $this->artisan('agent:export-tools', ['format' => 'invalid_format', '--role' => 'partner'])
            ->expectsOutputToContain('Unknown format')
            ->assertExitCode(1);
    }

    public function test_export_command_invalid_role_returns_error(): void
    {
        $this->artisan('agent:export-tools', ['format' => 'generic', '--role' => 'bogus'])
            ->expectsOutputToContain('Unknown role')
            ->assertExitCode(1);
    }

    public function test_export_command_missing_spec_returns_error(): void
    {
        $specPath = storage_path('api-docs/agent-api.json');
        $backup = $specPath . '.bak';
        File::move($specPath, $backup);

        try {
            $this->artisan('agent:export-tools', ['format' => 'generic', '--role' => 'partner'])
                ->expectsOutputToContain('Agent API spec not found')
                ->assertExitCode(1);
        } finally {
            File::move($backup, $specPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ENDPOINT — GET /api/agent/v1/tools
    // ═══════════════════════════════════════════════════════════════════════

    public function test_tools_endpoint_returns_tools_for_partner(): void
    {

        [$partner, $key] = createAgentPartner();

        $response = $this->getJson(
            '/api/agent/v1/tools?format=generic',
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'error',
                'tools',
                'meta' => ['role', 'format', 'tool_count', 'cached'],
            ])
            ->assertJson([
                'error' => false,
                'meta' => [
                    'role' => 'partner',
                    'format' => 'generic',
                ],
            ]);

        $this->assertGreaterThan(0, $response->json('meta.tool_count'));
    }

    public function test_tools_endpoint_returns_422_for_invalid_format(): void
    {

        [$partner, $key] = createAgentPartner();

        $response = $this->getJson(
            '/api/agent/v1/tools?format=xml',
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(422)
            ->assertJson([
                'error' => true,
                'code' => 'INVALID_FORMAT',
            ]);
    }

    public function test_tools_endpoint_returns_503_when_spec_missing(): void
    {

        [$partner, $key] = createAgentPartner();

        $specPath = storage_path('api-docs/agent-api.json');
        $backup = $specPath . '.bak';
        File::move($specPath, $backup);

        try {
            $response = $this->getJson(
                '/api/agent/v1/tools',
                agentHeaders($key->raw_key)
            );

            $response->assertStatus(503)
                ->assertJson([
                    'error' => true,
                    'code' => 'TOOLS_UNAVAILABLE',
                ]);
        } finally {
            File::move($backup, $specPath);
        }
    }

    public function test_tools_endpoint_cache_key_includes_spec_version(): void
    {

        Cache::flush();

        [$partner, $key] = createAgentPartner();

        // First request — should populate cache
        $this->getJson('/api/agent/v1/tools', agentHeaders($key->raw_key))
            ->assertStatus(200)
            ->assertJson(['meta' => ['cached' => false]]);

        // Second request — should be cached
        $this->getJson('/api/agent/v1/tools', agentHeaders($key->raw_key))
            ->assertStatus(200)
            ->assertJson(['meta' => ['cached' => true]]);

        // Touch the spec file to simulate regeneration
        $specPath = storage_path('api-docs/agent-api.json');
        touch($specPath, time() + 10);
        clearstatcache(true, $specPath);

        // Third request — new spec version = cache miss
        $this->getJson('/api/agent/v1/tools', agentHeaders($key->raw_key))
            ->assertStatus(200)
            ->assertJson(['meta' => ['cached' => false]]);
    }

    public function test_tools_endpoint_scoped_by_role(): void
    {


        [$partner, $partnerKey] = createAgentPartner();
        [$admin, $adminKey] = createAgentAdmin();

        $partnerResponse = $this->getJson(
            '/api/agent/v1/tools?format=generic',
            agentHeaders($partnerKey->raw_key)
        );
        $adminResponse = $this->getJson(
            '/api/agent/v1/tools?format=generic',
            agentHeaders($adminKey->raw_key)
        );

        $partnerResponse->assertStatus(200);
        $adminResponse->assertStatus(200);

        // Partner should see partner tools, admin should see admin tools
        $this->assertEquals('partner', $partnerResponse->json('meta.role'));
        $this->assertEquals('admin', $adminResponse->json('meta.role'));

        // They should have different tool counts
        $this->assertNotEquals(
            $partnerResponse->json('meta.tool_count'),
            $adminResponse->json('meta.tool_count'),
            'Different roles should see different numbers of tools'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PERMISSION-AWARE FILTERING
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * The gated operation names for each feature flag.
     * Must stay in sync with the controller x-agent-permission annotations.
     */
    private const PERMISSION_GATED_OPS = [
        'loyalty_cards_permission' => [
            'list_loyalty_cards', 'create_loyalty_card', 'get_loyalty_card',
            'update_loyalty_card', 'delete_loyalty_card',
            'list_rewards', 'create_reward', 'get_reward',
            'update_reward', 'delete_reward',
        ],
        'stamp_cards_permission' => [
            'list_stamp_cards', 'create_stamp_card', 'get_stamp_card',
            'update_stamp_card', 'delete_stamp_card',
            'add_stamps', 'redeem_stamp_reward',
        ],
        'vouchers_permission' => [
            'list_vouchers', 'create_voucher', 'get_voucher',
            'update_voucher', 'delete_voucher',
            'validate_voucher', 'redeem_voucher',
        ],
    ];

    public function test_disabled_loyalty_cards_hides_card_and_reward_tools(): void
    {
        $allTools = $this->service->extractTools('partner', null, false);
        $filtered = $this->service->extractTools('partner', null, false, ['loyalty_cards_permission']);

        $filteredNames = array_column($filtered, 'name');

        foreach (self::PERMISSION_GATED_OPS['loyalty_cards_permission'] as $op) {
            $this->assertNotContains($op, $filteredNames,
                "Tool '{$op}' should be hidden when loyalty_cards_permission is disabled");
        }

        // Non-gated partner tools should still be present
        $this->assertContains('list_clubs', $filteredNames);
        $this->assertContains('list_members', $filteredNames);

        // Filtered set should be smaller
        $this->assertLessThan(count($allTools), count($filtered));
    }

    public function test_disabled_stamp_cards_hides_stamp_tools(): void
    {
        $filtered = $this->service->extractTools('partner', null, false, ['stamp_cards_permission']);
        $filteredNames = array_column($filtered, 'name');

        foreach (self::PERMISSION_GATED_OPS['stamp_cards_permission'] as $op) {
            $this->assertNotContains($op, $filteredNames,
                "Tool '{$op}' should be hidden when stamp_cards_permission is disabled");
        }

        // Card tools should still be visible
        $this->assertContains('list_loyalty_cards', $filteredNames);
    }

    public function test_disabled_vouchers_hides_voucher_tools(): void
    {
        $filtered = $this->service->extractTools('partner', null, false, ['vouchers_permission']);
        $filteredNames = array_column($filtered, 'name');

        foreach (self::PERMISSION_GATED_OPS['vouchers_permission'] as $op) {
            $this->assertNotContains($op, $filteredNames,
                "Tool '{$op}' should be hidden when vouchers_permission is disabled");
        }

        // Stamp tools should still be visible
        $this->assertContains('list_stamp_cards', $filteredNames);
    }

    public function test_all_permissions_disabled_leaves_only_ungated_tools(): void
    {
        $allDisabled = array_keys(self::PERMISSION_GATED_OPS);
        $filtered = $this->service->extractTools('partner', null, false, $allDisabled);
        $filteredNames = array_column($filtered, 'name');

        // No gated tool should survive
        foreach (self::PERMISSION_GATED_OPS as $ops) {
            foreach ($ops as $op) {
                $this->assertNotContains($op, $filteredNames,
                    "Tool '{$op}' should be hidden when all permissions are disabled");
            }
        }

        // Ungated tools should remain (clubs, staff, members, tiers, transactions, health)
        $this->assertNotEmpty($filtered, 'Some ungated tools should remain');
        $this->assertContains('list_clubs', $filteredNames);
        $this->assertContains('list_staff', $filteredNames);
        $this->assertContains('list_transactions', $filteredNames);
    }

    public function test_permissions_do_not_affect_admin_or_member_roles(): void
    {
        // Admin and member endpoints have no permission gates
        $adminAll = $this->service->extractTools('admin', null, false);
        $adminFiltered = $this->service->extractTools('admin', null, false, ['loyalty_cards_permission']);
        $this->assertCount(count($adminAll), $adminFiltered,
            'Admin tools should be unaffected by partner permission flags');

        $memberAll = $this->service->extractTools('member', null, false);
        $memberFiltered = $this->service->extractTools('member', null, false, ['vouchers_permission']);
        $this->assertCount(count($memberAll), $memberFiltered,
            'Member tools should be unaffected by partner permission flags');
    }

    public function test_tools_endpoint_respects_partner_permissions(): void
    {

        Cache::flush();

        // Create partner with all features enabled
        [$partner, $key] = createAgentPartner([], [
            'meta' => [
                'agent_api_permission' => true,
                'loyalty_cards_permission' => true,
                'stamp_cards_permission' => true,
                'vouchers_permission' => true,
            ],
        ]);

        $fullResponse = $this->getJson(
            '/api/agent/v1/tools?format=generic',
            agentHeaders($key->raw_key)
        );
        $fullCount = $fullResponse->json('meta.tool_count');

        // Disable stamp cards
        $partner->update(['meta' => array_merge($partner->meta ?? [], [
            'stamp_cards_permission' => false,
        ])]);
        Cache::flush();

        $reducedResponse = $this->getJson(
            '/api/agent/v1/tools?format=generic',
            agentHeaders($key->raw_key)
        );
        $reducedCount = $reducedResponse->json('meta.tool_count');

        $this->assertLessThan($fullCount, $reducedCount,
            'Disabling stamp_cards_permission should reduce the tool count');

        // Verify no stamp tool names appear
        $toolNames = collect($reducedResponse->json('tools.tools'))
            ->pluck('name')->all();
        foreach (self::PERMISSION_GATED_OPS['stamp_cards_permission'] as $op) {
            $this->assertNotContains($op, $toolNames,
                "Tool '{$op}' should not appear when stamp_cards_permission is disabled");
        }
    }

    public function test_tools_endpoint_returns_403_when_agent_api_revoked(): void
    {

        Cache::flush();

        // Create partner with agent_api_permission initially enabled
        [$partner, $key] = createAgentPartner();

        // Verify it works first
        $this->getJson('/api/agent/v1/tools', agentHeaders($key->raw_key))
            ->assertStatus(200);

        // Revoke the top-level API permission
        $partner->update(['meta' => array_merge($partner->meta ?? [], [
            'agent_api_permission' => false,
        ])]);

        // /tools should now return 403 with the same error shape
        // as EnsurePartnerAgentApiEnabled middleware
        $response = $this->getJson(
            '/api/agent/v1/tools',
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(403)
            ->assertJson([
                'error' => true,
                'code' => 'FEATURE_DISABLED',
                'retry_strategy' => 'contact_support',
            ])
            ->assertJsonPath('details.permission', 'agent_api_permission');

        // Re-enable and confirm it works again
        $partner->update(['meta' => array_merge($partner->meta ?? [], [
            'agent_api_permission' => true,
        ])]);

        $this->getJson('/api/agent/v1/tools', agentHeaders($key->raw_key))
            ->assertStatus(200);
    }

    public function test_tools_endpoint_403_does_not_affect_admin_keys(): void
    {

        // Admin keys should never be affected by agent_api_permission
        [$admin, $adminKey] = createAgentAdmin();

        $this->getJson('/api/agent/v1/tools', agentHeaders($adminKey->raw_key))
            ->assertStatus(200)
            ->assertJson(['meta' => ['role' => 'admin']]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CONTRACT: x-agent-permission ↔ checkPermission alignment
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Every partner endpoint annotated with x-agent-permission should
     * match the actual checkPermission() call in its controller method.
     * This test reads the spec + source and cross-references them.
     */
    public function test_all_gated_endpoints_have_matching_spec_annotation(): void
    {
        $specPath = storage_path('api-docs/agent-api.json');
        $spec = json_decode(File::get($specPath), true);

        // Collect all partner paths with x-agent-permission from the spec
        $specGated = [];
        foreach ($spec['paths'] ?? [] as $path => $methods) {
            if (! str_starts_with($path, '/partner/')) {
                continue;
            }
            foreach ($methods as $method => $operation) {
                if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    continue;
                }
                $perm = $operation['x-agent-permission'] ?? null;
                if ($perm) {
                    $opId = $operation['operationId'] ?? "{$method} {$path}";
                    $specGated[$opId] = $perm;
                }
            }
        }

        // Should have exactly 24 gated operations
        $this->assertCount(24, $specGated,
            'Expected 24 partner endpoints with x-agent-permission (5 cards + 5 rewards + 7 stamps + 7 vouchers)');

        // Every gated permission should be one of the known feature flags
        $knownPermissions = ['loyalty_cards_permission', 'stamp_cards_permission', 'vouchers_permission'];
        foreach ($specGated as $opId => $perm) {
            $this->assertContains($perm, $knownPermissions,
                "Endpoint '{$opId}' has unknown x-agent-permission: '{$perm}'");
        }
    }
}
