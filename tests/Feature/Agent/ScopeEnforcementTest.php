<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Scope Enforcement Tests
 *
 * Tests that every partner endpoint correctly requires the documented
 * scope and rejects requests missing it. Also tests the scope hierarchy:
 * - admin super-scope bypasses all checks
 * - any write scope implies read access
 * - read-only cannot write
 *
 * @see App\Models\AgentKey::hasAnyScope()
 * @see App\Http\Controllers\Api\Agent\Concerns\EnforcesPartnerGates::requireScope()
 */

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

require_once __DIR__ . '/Helpers.php';

/*
|--------------------------------------------------------------------------
| Scope Hierarchy
|--------------------------------------------------------------------------
*/

describe('Scope Hierarchy', function () {
    it('admin scope grants read access', function () {
        $key = createAgentKey(['scopes' => ['admin']]);

        $response = $this->getJson('/api/agent/v1/partner/clubs', agentHeaders($key));

        $response->assertStatus(200);
    });

    it('admin scope grants write access', function () {
        $key = createAgentKey(['scopes' => ['admin']]);

        $response = $this->postJson('/api/agent/v1/partner/clubs', [
            'name' => 'Admin Created Club',
        ], agentHeaders($key));

        $response->assertStatus(201);
    });

    it('read scope grants GET access', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $response = $this->getJson('/api/agent/v1/partner/clubs', agentHeaders($key));

        $response->assertStatus(200);
    });

    it('read scope cannot POST', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $response = $this->postJson('/api/agent/v1/partner/clubs', [
            'name' => 'Should Fail',
        ], agentHeaders($key));

        $response->assertStatus(403);
        $response->assertJson([
            'code' => 'AUTH_INSUFFICIENT_SCOPE',
            'retry_strategy' => 'no_retry',
        ]);
    });

    it('write scope implies read for the same resource', function () {
        $key = createAgentKey(['scopes' => ['write:clubs']]);

        // GET should work (write:clubs implies read access to clubs)
        $response = $this->getJson('/api/agent/v1/partner/clubs', agentHeaders($key));

        $response->assertStatus(200);
    });

    it('write scope for one resource does not grant write to another', function () {
        $key = createAgentKey(['scopes' => ['write:clubs']]);

        // Trying to write cards with a clubs-only key
        $response = $this->postJson('/api/agent/v1/partner/cards', [
            'name' => 'Should Fail',
        ], agentHeaders($key));

        $response->assertStatus(403);
        $response->assertJson(['code' => 'AUTH_INSUFFICIENT_SCOPE']);
    });
});

/*
|--------------------------------------------------------------------------
| Per-Resource Scope Enforcement
|--------------------------------------------------------------------------
*/

describe('Clubs Scope Enforcement', function () {
    it('read scope can list clubs', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $this->getJson('/api/agent/v1/partner/clubs', agentHeaders($key))
            ->assertStatus(200);
    });

    it('write:clubs can create clubs', function () {
        $key = createAgentKey(['scopes' => ['write:clubs']]);

        $this->postJson('/api/agent/v1/partner/clubs', [
            'name' => 'New Club',
        ], agentHeaders($key))
            ->assertStatus(201);
    });

    it('write:transactions cannot create clubs', function () {
        $key = createAgentKey(['scopes' => ['write:transactions']]);

        $this->postJson('/api/agent/v1/partner/clubs', [
            'name' => 'Should Fail',
        ], agentHeaders($key))
            ->assertStatus(403)
            ->assertJson(['code' => 'AUTH_INSUFFICIENT_SCOPE']);
    });
});

describe('Cards Scope Enforcement', function () {
    it('read scope can list cards', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $this->getJson('/api/agent/v1/partner/cards', agentHeaders($key))
            ->assertStatus(200);
    });

    it('write:cards scope required to create', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $this->postJson('/api/agent/v1/partner/cards', [
            'name' => 'Should Fail',
        ], agentHeaders($key))
            ->assertStatus(403)
            ->assertJson(['code' => 'AUTH_INSUFFICIENT_SCOPE']);
    });
});

describe('Rewards Scope Enforcement', function () {
    it('read scope can list rewards', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $this->getJson('/api/agent/v1/partner/rewards', agentHeaders($key))
            ->assertStatus(200);
    });

    it('write:rewards scope required to create', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $this->postJson('/api/agent/v1/partner/rewards', [], agentHeaders($key))
            ->assertStatus(403)
            ->assertJson(['code' => 'AUTH_INSUFFICIENT_SCOPE']);
    });
});

describe('Transactions Scope Enforcement', function () {
    it('write:transactions required for purchase', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $this->postJson('/api/agent/v1/partner/transactions/purchase', [], agentHeaders($key))
            ->assertStatus(403)
            ->assertJson(['code' => 'AUTH_INSUFFICIENT_SCOPE']);
    });

    it('write:transactions allows purchase endpoint', function () {
        $key = createAgentKey(['scopes' => ['write:transactions']]);

        // Will fail validation (no body), but NOT scope enforcement
        $response = $this->postJson('/api/agent/v1/partner/transactions/purchase', [], agentHeaders($key));

        expect($response->status())->not->toBe(403);
    });
});

describe('Staff Scope Enforcement', function () {
    it('read scope can list staff', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $this->getJson('/api/agent/v1/partner/staff', agentHeaders($key))
            ->assertStatus(200);
    });

    it('write:staff required to create', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $this->postJson('/api/agent/v1/partner/staff', [], agentHeaders($key))
            ->assertStatus(403)
            ->assertJson(['code' => 'AUTH_INSUFFICIENT_SCOPE']);
    });
});

describe('Tiers Scope Enforcement', function () {
    it('read scope can list tiers', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $this->getJson('/api/agent/v1/partner/tiers', agentHeaders($key))
            ->assertStatus(200);
    });

    it('write:tiers required to create', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $this->postJson('/api/agent/v1/partner/tiers', [], agentHeaders($key))
            ->assertStatus(403)
            ->assertJson(['code' => 'AUTH_INSUFFICIENT_SCOPE']);
    });
});

/*
|--------------------------------------------------------------------------
| Scope Error Format
|--------------------------------------------------------------------------
*/

describe('Scope Error Details', function () {
    it('includes the required scope in error details', function () {
        $key = createAgentKey(['scopes' => ['read']]);

        $response = $this->postJson('/api/agent/v1/partner/clubs', [
            'name' => 'Test',
        ], agentHeaders($key));

        $response->assertStatus(403);
        $response->assertJsonPath('details.required_scope', 'write:clubs');
    });
});
