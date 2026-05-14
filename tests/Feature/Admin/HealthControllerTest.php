<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Feature tests for the Admin Health Center.
 * Tests the page route, JSON endpoint, service check structure,
 * and admin-only access control.
 */

use App\Models\Admin;
use App\Services\HealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function createHealthAdmin(array $attributes = []): Admin
{
    return Admin::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Health Admin',
        'email' => 'admin'.Str::random(5).'@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'locale' => 'en_US',
        'time_zone' => 'UTC',
        'currency' => 'USD',
        'is_active' => true,
    ], $attributes));
}

/*
|--------------------------------------------------------------------------
| HealthService Unit Tests
|--------------------------------------------------------------------------
*/

describe('HealthService', function () {
    it('returns structured check results', function () {
        $service = app(HealthService::class);
        $results = $service->runChecks();

        expect($results)->toHaveKeys(['checks', 'summary'])
            ->and($results['summary'])->toHaveKeys(['ok', 'warning', 'critical', 'total'])
            ->and($results['summary']['total'])->toBeGreaterThan(0)
            ->and($results['summary']['ok'] + $results['summary']['warning'] + $results['summary']['critical'])
                ->toBe($results['summary']['total']);
    });

    it('returns checks with required fields', function () {
        $service = app(HealthService::class);
        $results = $service->runChecks();

        foreach ($results['checks'] as $check) {
            expect($check)->toHaveKeys(['category', 'label', 'status', 'value', 'detail'])
                ->and($check['status'])->toBeIn(['ok', 'warning', 'critical'])
                ->and($check['category'])->toBeIn(['environment', 'application', 'database', 'storage', 'services']);
        }
    });

    it('detects correct PHP version', function () {
        $service = app(HealthService::class);
        $results = $service->runChecks();

        $phpCheck = collect($results['checks'])->firstWhere('label', 'PHP Version');

        expect($phpCheck)->not->toBeNull()
            ->and($phpCheck['value'])->toBe(PHP_VERSION)
            ->and($phpCheck['status'])->toBe(PHP_MAJOR_VERSION >= 8 && PHP_MINOR_VERSION >= 2 ? 'ok' : 'warning');
    });

    it('detects database connection', function () {
        $service = app(HealthService::class);
        $results = $service->runChecks();

        $dbCheck = collect($results['checks'])->firstWhere('label', 'Database Connection');

        expect($dbCheck)->not->toBeNull()
            ->and($dbCheck['status'])->toBe('ok')
            ->and($dbCheck['value'])->toBe('Connected');
    });

    it('checks writable directories', function () {
        $service = app(HealthService::class);
        $results = $service->runChecks();

        $storageCheck = collect($results['checks'])->firstWhere('label', 'Writable Directories');

        expect($storageCheck)->not->toBeNull()
            ->and($storageCheck['status'])->toBeIn(['ok', 'critical']);
    });

    it('does not include license checks', function () {
        $service = app(HealthService::class);
        $results = $service->runChecks();

        $categories = collect($results['checks'])->pluck('category')->unique()->all();

        expect($categories)->not->toContain('license');
    });

    it('reports scheduler ok when heartbeat is recent', function () {
        cache()->put('health:scheduler_last_run', now(), now()->addMinutes(10));

        $service = app(HealthService::class);
        $results = $service->runChecks();

        $cronCheck = collect($results['checks'])->firstWhere('label', 'Cron / Scheduler');

        expect($cronCheck)->not->toBeNull()
            ->and($cronCheck['status'])->toBe('ok')
            ->and($cronCheck['value'])->toContain('0m ago');

        cache()->forget('health:scheduler_last_run');
    });

    it('reports scheduler warning when heartbeat is stale', function () {
        cache()->put('health:scheduler_last_run', now()->subMinutes(15), now()->addMinutes(10));

        $service = app(HealthService::class);
        $results = $service->runChecks();

        $cronCheck = collect($results['checks'])->firstWhere('label', 'Cron / Scheduler');

        expect($cronCheck)->not->toBeNull()
            ->and($cronCheck['status'])->toBe('warning')
            ->and($cronCheck['value'])->toContain('15m ago');

        cache()->forget('health:scheduler_last_run');
    });

    it('reports scheduler warning when no heartbeat exists', function () {
        cache()->forget('health:scheduler_last_run');

        $service = app(HealthService::class);
        $results = $service->runChecks();

        $cronCheck = collect($results['checks'])->firstWhere('label', 'Cron / Scheduler');

        expect($cronCheck)->not->toBeNull()
            ->and($cronCheck['status'])->toBe('warning')
            ->and($cronCheck['value'])->toBe('Unknown');
    });

    it('reports sync queue as ok with guidance detail', function () {
        config(['queue.default' => 'sync']);

        $service = app(HealthService::class);
        $results = $service->runChecks();

        $queueCheck = collect($results['checks'])->firstWhere('label', 'Queue Driver');

        expect($queueCheck)->not->toBeNull()
            ->and($queueCheck['status'])->toBe('ok')
            ->and($queueCheck['value'])->toBe('Sync')
            ->and($queueCheck['detail'])->toContain('inline');
    });

    it('reports null queue as warning', function () {
        config(['queue.default' => 'null']);

        $service = app(HealthService::class);
        $results = $service->runChecks();

        $queueCheck = collect($results['checks'])->firstWhere('label', 'Queue Driver');

        expect($queueCheck)->not->toBeNull()
            ->and($queueCheck['status'])->toBe('warning')
            ->and($queueCheck['value'])->toBe('Null');
    });

    it('reports database queue as ok', function () {
        config(['queue.default' => 'database']);

        $service = app(HealthService::class);
        $results = $service->runChecks();

        $queueCheck = collect($results['checks'])->firstWhere('label', 'Queue Driver');

        expect($queueCheck)->not->toBeNull()
            ->and($queueCheck['status'])->toBe('ok')
            ->and($queueCheck['detail'])->toBeNull();
    });
});

/*
|--------------------------------------------------------------------------
| HTTP Route Tests
|--------------------------------------------------------------------------
*/

describe('Health Center Page', function () {
    it('renders for authenticated super admin', function () {
        $admin = createHealthAdmin();

        $this->actingAs($admin, 'admin');

        $url = route('admin.health.index', ['locale' => 'en-us']);
        $response = $this->get($url);

        $response->assertSuccessful()
            ->assertSee('Health Center');
    });

    it('redirects unauthenticated users', function () {
        $url = route('admin.health.index', ['locale' => 'en-us']);
        $response = $this->get($url);

        // Should redirect to login
        $response->assertRedirect();
    });
});

describe('Health Center JSON Endpoint', function () {
    it('returns JSON with check results for authenticated admin', function () {
        $admin = createHealthAdmin();

        $this->actingAs($admin, 'admin');

        $url = route('admin.health.json', ['locale' => 'en-us']);
        $response = $this->getJson($url);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'checks' => [
                        '*' => ['category', 'label', 'status', 'value', 'detail'],
                    ],
                    'summary' => ['ok', 'warning', 'critical', 'total'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    });

    it('includes all expected check categories', function () {
        $admin = createHealthAdmin();

        $this->actingAs($admin, 'admin');

        $url = route('admin.health.json', ['locale' => 'en-us']);
        $response = $this->getJson($url);

        $data = $response->json('data');
        $categories = collect($data['checks'])->pluck('category')->unique()->sort()->values()->all();

        expect($categories)->toContain('environment')
            ->and($categories)->toContain('application')
            ->and($categories)->toContain('database')
            ->and($categories)->toContain('storage')
            ->and($categories)->toContain('services')
            ->and($categories)->not->toContain('license');
    });

    it('returns valid summary counts', function () {
        $admin = createHealthAdmin();

        $this->actingAs($admin, 'admin');

        $url = route('admin.health.json', ['locale' => 'en-us']);
        $response = $this->getJson($url);

        $summary = $response->json('data.summary');
        $checks = $response->json('data.checks');

        expect($summary['total'])->toBe(count($checks))
            ->and($summary['ok'] + $summary['warning'] + $summary['critical'])->toBe($summary['total']);
    });
});
