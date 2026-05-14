<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Authentication Pipeline Tests
 *
 * Tests the full authentication pipeline from X-Agent-Key header
 * through to controller dispatch. Covers every error code and edge
 * case defined in the error catalog.
 *
 * These tests are the safety net for the most security-critical
 * path in the application. Every authentication failure mode
 * must have a corresponding test.
 *
 * @see App\Http\Middleware\AuthenticateAgent
 * @see App\Http\Middleware\EnforceAgentRole
 * @see App\Http\Middleware\AgentRateLimiter
 */

use App\Models\Admin;
use App\Models\AgentKey;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

require_once __DIR__ . '/Helpers.php';

/*
|--------------------------------------------------------------------------
| Missing & Malformed Keys
|--------------------------------------------------------------------------
*/

describe('Missing & Malformed Keys', function () {
    it('returns AUTH_MISSING_KEY when no header is sent', function () {
        $response = $this->getJson('/api/agent/v1/health');

        $response->assertStatus(401);
        $response->assertJson([
            'error' => true,
            'code' => 'AUTH_MISSING_KEY',
            'retry_strategy' => 'no_retry',
        ]);
    });

    it('returns AUTH_INVALID_KEY for completely wrong key format', function () {
        $response = $this->getJson('/api/agent/v1/health', [
            'X-Agent-Key' => 'this_is_not_a_valid_key_at_all',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['code' => 'AUTH_INVALID_KEY']);
    });

    it('returns AUTH_INVALID_KEY for correct prefix but wrong secret', function () {
        // Create a real key, then tamper with it
        [$partner, $key] = createAgentPartner();

        $tamperedKey = $key->key_prefix . 'WRONG_SECRET_THAT_DOESNT_MATCH';

        $response = $this->getJson('/api/agent/v1/health', [
            'X-Agent-Key' => $tamperedKey,
        ]);

        $response->assertStatus(401);
        $response->assertJson(['code' => 'AUTH_INVALID_KEY']);
    });

    it('returns AUTH_INVALID_KEY for unknown prefix', function () {
        $response = $this->getJson('/api/agent/v1/health', [
            'X-Agent-Key' => 'rl_agent_nonexistentprefix' . str_repeat('x', 40),
        ]);

        $response->assertStatus(401);
        $response->assertJson(['code' => 'AUTH_INVALID_KEY']);
    });
});

/*
|--------------------------------------------------------------------------
| Valid Key Authentication
|--------------------------------------------------------------------------
*/

describe('Valid Key Authentication', function () {
    it('authenticates with a valid partner key', function () {
        [$partner, $key] = createAgentPartner();

        $response = $this->getJson('/api/agent/v1/health', agentHeaders($key->raw_key));

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'data' => [
                'status' => 'ok',
                'key' => [
                    'prefix' => $key->key_prefix,
                    'role' => 'partner',
                ],
                'owner' => [
                    'id' => $partner->id,
                ],
            ],
        ]);
    });

    it('returns scopes in health response', function () {
        $key = createAgentKey(['scopes' => ['read', 'write:transactions']]);

        $response = $this->getJson('/api/agent/v1/health', agentHeaders($key));

        $response->assertStatus(200);
        $response->assertJsonPath('data.key.scopes', ['read', 'write:transactions']);
    });

    it('returns rate_limit in health response', function () {
        $key = createAgentKey(['rate_limit' => 120]);

        $response = $this->getJson('/api/agent/v1/health', agentHeaders($key));

        $response->assertStatus(200);
        $response->assertJsonPath('data.key.rate_limit', 120);
    });
});

/*
|--------------------------------------------------------------------------
| Key Lifecycle States
|--------------------------------------------------------------------------
*/

describe('Key Lifecycle States', function () {
    it('returns AUTH_KEY_REVOKED for deactivated key', function () {
        [$partner, $key] = createAgentPartner();

        // Deactivate the key after creation (so we still have raw_key)
        $key->update(['is_active' => false]);

        $response = $this->getJson('/api/agent/v1/health', agentHeaders($key->raw_key));

        $response->assertStatus(401);
        $response->assertJson([
            'code' => 'AUTH_KEY_REVOKED',
            'retry_strategy' => 'no_retry',
        ]);
    });

    it('returns AUTH_KEY_EXPIRED for expired key', function () {
        [$partner, $key] = createAgentPartner([
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/agent/v1/health', agentHeaders($key->raw_key));

        $response->assertStatus(401);
        $response->assertJson([
            'code' => 'AUTH_KEY_EXPIRED',
            'retry_strategy' => 'no_retry',
        ]);
    });

    it('accepts key that expires in the future', function () {
        [$partner, $key] = createAgentPartner([
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->getJson('/api/agent/v1/health', agentHeaders($key->raw_key));

        $response->assertStatus(200);
        $response->assertJson(['error' => false]);
    });

    it('accepts key with no expiration date', function () {
        [$partner, $key] = createAgentPartner([
            'expires_at' => null,
        ]);

        $response = $this->getJson('/api/agent/v1/health', agentHeaders($key->raw_key));

        $response->assertStatus(200);
    });
});

/*
|--------------------------------------------------------------------------
| Owner Activity
|--------------------------------------------------------------------------
*/

describe('Owner Activity', function () {
    it('returns AUTH_OWNER_INACTIVE when partner is deactivated', function () {
        [$partner, $key] = createAgentPartner([], ['is_active' => false]);

        $response = $this->getJson('/api/agent/v1/health', agentHeaders($key->raw_key));

        $response->assertStatus(403);
        $response->assertJson([
            'code' => 'AUTH_OWNER_INACTIVE',
            'retry_strategy' => 'contact_support',
        ]);
    });

    it('accepts request when partner is active', function () {
        [$partner, $key] = createAgentPartner([], ['is_active' => true]);

        $response = $this->getJson('/api/agent/v1/health', agentHeaders($key->raw_key));

        $response->assertStatus(200);
    });
});

/*
|--------------------------------------------------------------------------
| Role Enforcement
|--------------------------------------------------------------------------
*/

describe('Role Enforcement', function () {
    it('allows partner key on partner routes', function () {
        [$partner, $key] = createAgentPartner();

        $response = $this->getJson('/api/agent/v1/partner/clubs', agentHeaders($key->raw_key));

        $response->assertStatus(200);
    });

    it('returns 404 for unregistered admin routes', function () {
        [$partner, $key] = createAgentPartner();

        // Admin route group exists but is empty (Phase 4).
        // No routes match → 404 before role enforcement runs.
        $response = $this->getJson('/api/agent/v1/admin/anything', agentHeaders($key->raw_key));

        // Expect 404 because there are no endpoints registered in the admin group
        expect($response->status())->toBeIn([404, 405]);
    });

    it('blocks health endpoint without any agent key', function () {
        // Health endpoint requires agent.auth but no specific role
        $response = $this->getJson('/api/agent/v1/health');

        $response->assertStatus(401);
        $response->assertJson(['code' => 'AUTH_MISSING_KEY']);
    });

    it('partner key can access health endpoint (role-agnostic)', function () {
        [$partner, $key] = createAgentPartner();

        // Health endpoint has no role requirement — any authenticated key works
        $response = $this->getJson('/api/agent/v1/health', agentHeaders($key->raw_key));

        $response->assertStatus(200);
    });
});

/*
|--------------------------------------------------------------------------
| Rate Limit Headers
|--------------------------------------------------------------------------
*/

describe('Rate Limit Headers', function () {
    it('includes rate limit headers on every response', function () {
        $key = createAgentKey(['rate_limit' => 100]);

        $response = $this->getJson('/api/agent/v1/health', agentHeaders($key));

        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Limit', '100');
        $response->assertHeader('X-RateLimit-Remaining');
        $response->assertHeader('X-RateLimit-Reset');
    });

    it('decrements remaining count on each request', function () {
        $key = createAgentKey(['rate_limit' => 100]);

        $response1 = $this->getJson('/api/agent/v1/health', agentHeaders($key));
        $remaining1 = (int) $response1->headers->get('X-RateLimit-Remaining');

        $response2 = $this->getJson('/api/agent/v1/health', agentHeaders($key));
        $remaining2 = (int) $response2->headers->get('X-RateLimit-Remaining');

        expect($remaining2)->toBeLessThan($remaining1);
    });
});

/*
|--------------------------------------------------------------------------
| Error Envelope Format
|--------------------------------------------------------------------------
*/

describe('Error Envelope Format', function () {
    it('always returns the standard error envelope', function () {
        $response = $this->getJson('/api/agent/v1/health');

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'error',
            'code',
            'message',
            'retry_strategy',
        ]);
        $response->assertJson(['error' => true]);
    });

    it('always returns the standard success envelope', function () {
        $key = createAgentKey();

        $response = $this->getJson('/api/agent/v1/health', agentHeaders($key));

        $response->assertStatus(200);
        $response->assertJsonStructure(['error', 'data']);
        $response->assertJson(['error' => false]);
    });
});

/*
|--------------------------------------------------------------------------
| Last Used Tracking
|--------------------------------------------------------------------------
*/

describe('Last Used Tracking', function () {
    it('updates last_used_at on successful request', function () {
        [$partner, $key] = createAgentPartner();

        expect($key->last_used_at)->toBeNull();

        $this->getJson('/api/agent/v1/health', agentHeaders($key->raw_key));

        $key->refresh();
        expect($key->last_used_at)->not->toBeNull();
    });
});
