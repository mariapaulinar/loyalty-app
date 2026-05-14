<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Agent API: Partner Club Endpoint Tests
 *
 * Full CRUD test coverage for the clubs endpoint.
 * Tests ownership isolation, validation, and response format.
 * This is the template pattern for all partner resource tests.
 *
 * @see App\Http\Controllers\Api\Agent\Partner\AgentClubController
 */

use App\Models\Club;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

require_once __DIR__ . '/../Helpers.php';

/*
|--------------------------------------------------------------------------
| List Clubs
|--------------------------------------------------------------------------
*/

describe('GET /partner/clubs', function () {
    it('returns empty array when no clubs exist', function () {
        $key = createAgentKey();

        $response = $this->getJson('/api/agent/v1/partner/clubs', agentHeaders($key));

        $response->assertStatus(200);
        $response->assertJson(['error' => false]);
        $response->assertJsonPath('data', []);
    });

    it('returns only clubs owned by the partner', function () {
        [$partnerA, $keyA] = createAgentPartner();
        [$partnerB, $keyB] = createAgentPartner();

        createAgentClub($partnerA->id, ['name' => 'Partner A Club']);
        createAgentClub($partnerB->id, ['name' => 'Partner B Club']);

        $response = $this->getJson('/api/agent/v1/partner/clubs', agentHeaders($keyA->raw_key));

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Partner A Club');
    });

    it('includes pagination metadata', function () {
        [$partner, $key] = createAgentPartner();

        for ($i = 0; $i < 3; $i++) {
            createAgentClub($partner->id);
        }

        $response = $this->getJson('/api/agent/v1/partner/clubs', agentHeaders($key->raw_key));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
        $response->assertJsonPath('pagination.total', 3);
    });

    it('respects per_page parameter', function () {
        [$partner, $key] = createAgentPartner();

        for ($i = 0; $i < 5; $i++) {
            createAgentClub($partner->id);
        }

        $response = $this->getJson('/api/agent/v1/partner/clubs?per_page=2', agentHeaders($key->raw_key));

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(2);
        $response->assertJsonPath('pagination.per_page', 2);
        $response->assertJsonPath('pagination.total', 5);
    });

    it('clamps per_page to maximum 100', function () {
        $key = createAgentKey();

        $response = $this->getJson('/api/agent/v1/partner/clubs?per_page=999', agentHeaders($key));

        $response->assertStatus(200);
        $response->assertJsonPath('pagination.per_page', 100);
    });

    it('clamps per_page minimum to 1', function () {
        $key = createAgentKey();

        $response = $this->getJson('/api/agent/v1/partner/clubs?per_page=-5', agentHeaders($key));

        $response->assertStatus(200);
        $response->assertJsonPath('pagination.per_page', 1);
    });
});

/*
|--------------------------------------------------------------------------
| Show Club
|--------------------------------------------------------------------------
*/

describe('GET /partner/clubs/{id}', function () {
    it('returns club details', function () {
        [$partner, $key] = createAgentPartner();
        $club = createAgentClub($partner->id, ['name' => 'Downtown']);

        $response = $this->getJson(
            "/api/agent/v1/partner/clubs/{$club->id}",
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'data' => [
                'id' => $club->id,
                'name' => 'Downtown',
            ],
        ]);
    });

    it('returns NOT_FOUND for nonexistent club', function () {
        $key = createAgentKey();

        $response = $this->getJson(
            '/api/agent/v1/partner/clubs/nonexistent-uuid',
            agentHeaders($key)
        );

        $response->assertStatus(404);
        $response->assertJson([
            'code' => 'NOT_FOUND',
            'retry_strategy' => 'no_retry',
        ]);
    });

    it('returns NOT_FOUND for club owned by another partner', function () {
        [$partnerA, $keyA] = createAgentPartner();
        [$partnerB, $keyB] = createAgentPartner();

        $club = createAgentClub($partnerB->id, ['name' => 'Secret Club']);

        // Partner A tries to access Partner B's club → 404 (not 403, don't leak existence)
        $response = $this->getJson(
            "/api/agent/v1/partner/clubs/{$club->id}",
            agentHeaders($keyA->raw_key)
        );

        $response->assertStatus(404);
        $response->assertJson(['code' => 'NOT_FOUND']);
    });
});

/*
|--------------------------------------------------------------------------
| Create Club
|--------------------------------------------------------------------------
*/

describe('POST /partner/clubs', function () {
    it('creates a club successfully', function () {
        [$partner, $key] = createAgentPartner();

        $response = $this->postJson('/api/agent/v1/partner/clubs', [
            'name' => 'New Location',
        ], agentHeaders($key->raw_key));

        $response->assertStatus(201);
        $response->assertJson([
            'error' => false,
            'data' => [
                'name' => 'New Location',
            ],
        ]);

        // Verify DB state
        $this->assertDatabaseHas('clubs', [
            'name' => 'New Location',
            'created_by' => $partner->id,
        ]);
    });

    it('returns VALIDATION_FAILED for missing name', function () {
        $key = createAgentKey();

        $response = $this->postJson('/api/agent/v1/partner/clubs', [], agentHeaders($key));

        $response->assertStatus(422);
        $response->assertJson([
            'code' => 'VALIDATION_FAILED',
            'retry_strategy' => 'fix_request',
        ]);
        $response->assertJsonPath('details.errors.name.0', 'The name field is required.');
    });

    it('returns VALIDATION_FAILED for name too long', function () {
        $key = createAgentKey();

        $response = $this->postJson('/api/agent/v1/partner/clubs', [
            'name' => str_repeat('A', 200),
        ], agentHeaders($key));

        $response->assertStatus(422);
        $response->assertJson(['code' => 'VALIDATION_FAILED']);
    });
});

/*
|--------------------------------------------------------------------------
| Update Club
|--------------------------------------------------------------------------
*/

describe('PUT /partner/clubs/{id}', function () {
    it('updates a club successfully', function () {
        [$partner, $key] = createAgentPartner();
        $club = createAgentClub($partner->id, ['name' => 'Old Name']);

        $response = $this->putJson(
            "/api/agent/v1/partner/clubs/{$club->id}",
            ['name' => 'New Name'],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'New Name');

        $club->refresh();
        expect($club->name)->toBe('New Name');
    });

    it('cannot update another partner club', function () {
        [$partnerA, $keyA] = createAgentPartner();
        [$partnerB, $keyB] = createAgentPartner();

        $club = createAgentClub($partnerB->id);

        $response = $this->putJson(
            "/api/agent/v1/partner/clubs/{$club->id}",
            ['name' => 'Hijacked'],
            agentHeaders($keyA->raw_key)
        );

        $response->assertStatus(404);
    });
});

/*
|--------------------------------------------------------------------------
| Delete Club
|--------------------------------------------------------------------------
*/

describe('DELETE /partner/clubs/{id}', function () {
    it('deletes a club successfully', function () {
        [$partner, $key] = createAgentPartner();
        $club = createAgentClub($partner->id);

        $response = $this->deleteJson(
            "/api/agent/v1/partner/clubs/{$club->id}",
            [],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(200);
        $this->assertDatabaseMissing('clubs', ['id' => $club->id]);
    });

    it('cannot delete another partner club', function () {
        [$partnerA, $keyA] = createAgentPartner();
        [$partnerB, $keyB] = createAgentPartner();

        $club = createAgentClub($partnerB->id);

        $response = $this->deleteJson(
            "/api/agent/v1/partner/clubs/{$club->id}",
            [],
            agentHeaders($keyA->raw_key)
        );

        $response->assertStatus(404);
        // Club should still exist
        $this->assertDatabaseHas('clubs', ['id' => $club->id]);
    });

    it('returns NOT_FOUND for nonexistent club', function () {
        $key = createAgentKey();

        $response = $this->deleteJson(
            '/api/agent/v1/partner/clubs/nonexistent-uuid',
            [],
            agentHeaders($key)
        );

        $response->assertStatus(404);
    });

    it('cannot delete undeletable clubs', function () {
        [$partner, $key] = createAgentPartner();
        $club = createAgentClub($partner->id, ['is_undeletable' => true]);

        $response = $this->deleteJson(
            "/api/agent/v1/partner/clubs/{$club->id}",
            [],
            agentHeaders($key->raw_key)
        );

        $response->assertStatus(422);
        $this->assertDatabaseHas('clubs', ['id' => $club->id]);
    });
});

/*
|--------------------------------------------------------------------------
| Multi-Tenancy Isolation
|--------------------------------------------------------------------------
*/

describe('Multi-Tenancy Isolation', function () {
    it('partner A cannot see partner B clubs in list', function () {
        [$partnerA, $keyA] = createAgentPartner();
        [$partnerB, $keyB] = createAgentPartner();

        createAgentClub($partnerA->id, ['name' => 'Club A']);
        createAgentClub($partnerB->id, ['name' => 'Club B']);
        createAgentClub($partnerB->id, ['name' => 'Club C']);

        $response = $this->getJson('/api/agent/v1/partner/clubs', agentHeaders($keyA->raw_key));

        $data = $response->json('data');
        expect($data)->toHaveCount(1);

        $names = collect($data)->pluck('name')->toArray();
        expect($names)->toContain('Club A');
        expect($names)->not->toContain('Club B');
        expect($names)->not->toContain('Club C');
    });

    it('partner A cannot access partner B club by ID', function () {
        [$partnerA, $keyA] = createAgentPartner();
        [$partnerB, $keyB] = createAgentPartner();

        $clubB = createAgentClub($partnerB->id);

        $response = $this->getJson(
            "/api/agent/v1/partner/clubs/{$clubB->id}",
            agentHeaders($keyA->raw_key)
        );

        // Returns 404, NOT 403 — don't leak existence
        $response->assertStatus(404);
    });

    it('partner A cannot update partner B club', function () {
        [$partnerA, $keyA] = createAgentPartner();
        [$partnerB, $keyB] = createAgentPartner();

        $clubB = createAgentClub($partnerB->id, ['name' => 'Original']);

        $response = $this->putJson(
            "/api/agent/v1/partner/clubs/{$clubB->id}",
            ['name' => 'Hijacked'],
            agentHeaders($keyA->raw_key)
        );

        $response->assertStatus(404);
        $clubB->refresh();
        expect($clubB->name)->toBe('Original');
    });

    it('partner A cannot delete partner B club', function () {
        [$partnerA, $keyA] = createAgentPartner();
        [$partnerB, $keyB] = createAgentPartner();

        $clubB = createAgentClub($partnerB->id);

        $response = $this->deleteJson(
            "/api/agent/v1/partner/clubs/{$clubB->id}",
            [],
            agentHeaders($keyA->raw_key)
        );

        $response->assertStatus(404);
        $this->assertDatabaseHas('clubs', ['id' => $clubB->id]);
    });
});
