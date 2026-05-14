<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Comprehensive tests for the Activity Logging feature.
 * Tests model logging, authentication events, service methods, and cleanup.
 */

use App\Models\Activity;
use App\Models\Admin;
use App\Models\Card;
use App\Models\Club;
use App\Models\Member;
use App\Models\Partner;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function createAdmin(array $attributes = []): Admin
{
    return Admin::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Test Admin',
        'email' => 'admin'.Str::random(5).'@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'locale' => 'en_US',
        'time_zone' => 'UTC',
        'currency' => 'USD',
        'is_active' => true,
    ], $attributes));
}

function createPartner(array $attributes = []): Partner
{
    return Partner::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Test Partner',
        'email' => 'partner'.Str::random(5).'@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'locale' => 'en_US',
        'time_zone' => 'UTC',
        'currency' => 'USD',
        'is_active' => true,
    ], $attributes));
}

function createMember(array $attributes = []): Member
{
    return Member::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Test Member',
        'email' => 'member'.Str::random(5).'@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'locale' => 'en_US',
        'time_zone' => 'UTC',
        'currency' => 'USD',
        'is_active' => true,
    ], $attributes));
}

function createClub(string $partnerId, array $attributes = []): Club
{
    return Club::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Test Club',
        'created_by' => $partnerId,
        'is_active' => true,
    ], $attributes));
}

function createCard(string $partnerId, string $clubId, array $attributes = []): Card
{
    return Card::create(array_merge([
        'id' => Str::uuid()->toString(),
        'name' => 'Test Card',
        'club_id' => $clubId,
        'created_by' => $partnerId,
        'currency' => 'USD',
        'currency_unit_amount' => 1,
        'points_per_currency' => 100,
        'min_points_per_purchase' => 1,
        'max_points_per_purchase' => 100000,
        'points_expiration_months' => 12,
        'is_active' => true,
        'head' => ['en' => 'Test Card'],
    ], $attributes));
}

function createActivity(array $attributes = []): Activity
{
    return Activity::create(array_merge([
        'id' => Str::uuid()->toString(),
        'log_name' => 'default',
        'description' => 'Test activity',
        'event' => 'created',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser',
        'created_at' => now(),
        'updated_at' => now(),
    ], $attributes));
}

/*
|--------------------------------------------------------------------------
| Model Activity Logging Tests
|--------------------------------------------------------------------------
*/

describe('Model Activity Logging', function () {
    it('logs card creation', function () {
        $partner = createPartner();
        $club = createClub($partner->id);

        // Clear previous activities
        Activity::query()->delete();

        $this->actingAs($partner, 'partner');

        $card = createCard($partner->id, $club->id, ['name' => 'Coffee Rewards']);

        $activity = Activity::query()
            ->forSubject($card)
            ->where('event', 'created')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->description)->toContain('Card')
            ->and($activity->log_name)->toBe('cards');
    });

    it('logs card updates with changes', function () {
        $partner = createPartner();
        $club = createClub($partner->id);
        $card = createCard($partner->id, $club->id, ['name' => 'Original Name']);

        Activity::query()->delete();

        $card->update(['name' => 'Updated Name']);

        $activity = Activity::query()
            ->forSubject($card)
            ->where('event', 'updated')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->properties->get('old')['name'])->toBe('Original Name')
            ->and($activity->properties->get('attributes')['name'])->toBe('Updated Name');
    });

    it('logs member creation', function () {
        Activity::query()->delete();

        $member = createMember(['name' => 'John Doe']);

        $activity = Activity::query()
            ->forSubject($member)
            ->where('event', 'created')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->description)->toContain('Member')
            ->and($activity->log_name)->toBe('members');
    });

    it('logs partner creation', function () {
        Activity::query()->delete();

        $partner = createPartner(['name' => 'Test Business']);

        $activity = Activity::query()
            ->forSubject($partner)
            ->where('event', 'created')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->description)->toContain('Partner')
            ->and($activity->log_name)->toBe('partners');
    });

    it('does not log unchanged updates', function () {
        $member = createMember(['name' => 'John']);

        Activity::query()->delete();

        $member->update(['name' => 'John']);

        $activity = Activity::query()
            ->forSubject($member)
            ->where('event', 'updated')
            ->first();

        expect($activity)->toBeNull();
    });
});

/*
|--------------------------------------------------------------------------
| ActivityLogService Tests
|--------------------------------------------------------------------------
*/

describe('ActivityLogService', function () {
    it('logs custom activities', function () {
        $service = app(ActivityLogService::class);
        $member = createMember();

        Activity::query()->delete();

        $activity = $service->log(
            'Custom activity description',
            $member,
            'custom_event',
            ['extra' => 'data'],
            'custom_log'
        );

        expect($activity->description)->toBe('Custom activity description')
            ->and($activity->event)->toBe('custom_event')
            ->and($activity->log_name)->toBe('custom_log')
            ->and($activity->properties->get('extra'))->toBe('data')
            ->and($activity->subject_id)->toBe($member->id);
    });

    it('logs authentication events', function () {
        $service = app(ActivityLogService::class);
        $admin = createAdmin();

        Activity::query()->delete();

        $activity = $service->logAuth('login', $admin);

        expect($activity->event)->toBe('login')
            ->and($activity->log_name)->toBe('authentication')
            ->and($activity->causer_id)->toBe($admin->id)
            ->and($activity->subject_id)->toBe($admin->id)
            ->and($activity->properties->get('guard'))->toBe('admin');
    });

    it('logs failed login attempts', function () {
        $service = app(ActivityLogService::class);

        Activity::query()->delete();

        $activity = $service->logFailedLogin(['email' => 'hacker@example.com']);

        expect($activity->event)->toBe('login_failed')
            ->and($activity->log_name)->toBe('authentication')
            ->and($activity->description)->toContain('hacker@example.com')
            ->and($activity->properties->get('email'))->toBe('hacker@example.com');
    });

    it('retrieves activities for a subject', function () {
        $service = app(ActivityLogService::class);
        $member = createMember();

        Activity::query()->delete();

        $service->log('Activity 1', $member);
        $service->log('Activity 2', $member);
        $service->log('Activity 3', $member);

        $activities = $service->getForSubject($member);

        expect($activities)->toHaveCount(3);
    });

    it('retrieves activities by causer', function () {
        $service = app(ActivityLogService::class);
        $admin = createAdmin();

        Activity::query()->delete();

        $this->actingAs($admin, 'admin');

        $service->log('Admin did something');
        $service->log('Admin did another thing');

        $activities = $service->getByCauser($admin);

        expect($activities)->toHaveCount(2);
    });

    it('searches activities with filters', function () {
        $service = app(ActivityLogService::class);

        Activity::query()->delete();

        $service->log('Auth activity', null, 'login', [], 'authentication');
        $service->log('Card activity', null, 'created', [], 'cards');
        $service->log('Another auth activity', null, 'logout', [], 'authentication');

        $authActivities = $service->search(['log_name' => 'authentication']);
        expect($authActivities->total())->toBe(2);

        $loginActivities = $service->search(['event' => 'login']);
        expect($loginActivities->total())->toBe(1);
    });

    it('gets activity statistics', function () {
        $service = app(ActivityLogService::class);

        Activity::query()->delete();

        $service->log('Activity 1', null, 'created', [], 'cards');
        $service->log('Activity 2', null, 'updated', [], 'cards');
        $service->log('Activity 3', null, 'login', [], 'authentication');

        $stats = $service->getStats();

        expect($stats['total'])->toBe(3)
            ->and($stats['by_log_name'])->toHaveKey('cards')
            ->and($stats['by_log_name']['cards'])->toBe(2)
            ->and($stats['by_event'])->toHaveKey('created')
            ->and($stats['by_event']['login'])->toBe(1);
    });
});

/*
|--------------------------------------------------------------------------
| Activity Model Tests
|--------------------------------------------------------------------------
*/

describe('Activity Model', function () {
    it('has correct event color attribute', function () {
        $activity = new Activity(['event' => 'created']);
        expect($activity->event_color)->toBe('success');

        $activity->event = 'updated';
        expect($activity->event_color)->toBe('info');

        $activity->event = 'deleted';
        expect($activity->event_color)->toBe('danger');

        $activity->event = 'login';
        expect($activity->event_color)->toBe('primary');
    });

    it('has correct event icon attribute', function () {
        $activity = new Activity(['event' => 'created']);
        expect($activity->event_icon)->toBe('plus-circle');

        $activity->event = 'deleted';
        expect($activity->event_icon)->toBe('trash-2');

        $activity->event = 'login';
        expect($activity->event_icon)->toBe('log-in');
    });

    it('provides old and changes attributes', function () {
        $member = createMember(['name' => 'Old Name']);
        Activity::query()->delete();

        $member->update(['name' => 'New Name']);

        $activity = Activity::query()->first();

        // Check that the expected values are present in old/new arrays
        // Note: Spatie may include other attributes that were dirty
        expect($activity->old['name'])->toBe('Old Name')
            ->and($activity->new_values['name'])->toBe('New Name');
    });

    it('has working query scopes', function () {
        $service = app(ActivityLogService::class);

        Activity::query()->delete();

        $service->log('Auth 1', null, 'login', [], 'authentication');
        $service->log('Card 1', null, 'created', [], 'cards');

        expect(Activity::query()->authentication()->count())->toBe(1)
            ->and(Activity::query()->forEvents('login')->count())->toBe(1)
            ->and(Activity::query()->today()->count())->toBe(2);
    });
});

/*
|--------------------------------------------------------------------------
| Cleanup Command Tests
|--------------------------------------------------------------------------
*/

describe('Cleanup Command', function () {
    it('reports when no old logs exist', function () {
        Activity::query()->delete();

        $this->artisan('activity-log:cleanup --days=30')
            ->expectsOutput('No activity logs older than 30 days found.')
            ->assertExitCode(0);
    });

    it('shows dry run output correctly', function () {
        Activity::query()->delete();

        createActivity(['created_at' => now()->subDays(100)]);

        $this->artisan('activity-log:cleanup --days=30 --dry-run')
            ->expectsOutput('[DRY RUN] Would delete 1 activity logs.')
            ->assertExitCode(0);

        expect(Activity::count())->toBe(1);
    });
});

/*
|--------------------------------------------------------------------------
| Security Tests
|--------------------------------------------------------------------------
*/

describe('Activity Log Security', function () {
    it('does not expose sensitive fields in logs', function () {
        Activity::query()->delete();

        $member = createMember(['password' => bcrypt('secret123')]);

        $activity = Activity::query()
            ->forSubject($member)
            ->first();

        expect($activity->properties->has('password'))->toBeFalse()
            ->and($activity->description)->not->toContain('secret123');
    });
});
