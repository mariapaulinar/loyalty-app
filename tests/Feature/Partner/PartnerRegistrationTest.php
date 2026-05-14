<?php

declare(strict_types=1);

/**
 * Partner Registration Feature Tests
 *
 * Tests the full partner self-registration flow including:
 * - Registration disabled/enabled modes
 * - Spam/abuse controls (honeypot + rate limiting)
 * - Duplicate email handling
 * - Plan assignment and entitlement enforcement
 * - Billing mode behavior
 */

use App\Models\Partner;
use App\Services\Partner\AuthService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

// Helper to build locale-aware route URLs
function registrationUrl(string $routeName = 'partner.register', array $extra = []): string
{
    return route($routeName, array_merge(['locale' => 'en-us'], $extra));
}

// Helper to ensure a primary network exists for registration
function ensurePrimaryNetwork(): \App\Models\Network
{
    return \App\Models\Network::firstOrCreate(
        ['is_primary' => true],
        [
            'name' => 'Test Primary Network',
            'is_active' => true,
            'is_primary' => true,
            'currency' => 'USD',
        ]
    );
}

// ─────────────────────────────────────────────────────────────────────────
// Registration Disabled Mode
// ─────────────────────────────────────────────────────────────────────────

describe('registration disabled mode', function () {

    it('GET /partner/register redirects to login with error when disabled', function () {
        // Ensure registration is disabled (default)
        config(['default.partners_can_register' => false]);

        $response = $this->get(registrationUrl());

        $response->assertRedirect(route('partner.login', ['locale' => 'en-us']));
        $response->assertSessionHas('error');
    });

    it('POST /partner/register returns 403 when disabled', function () {
        config(['default.partners_can_register' => false]);

        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Test Business',
            'email' => 'test@example.com',
            'consent' => true,
        ]);

        // RegistrationRequest::authorize() returns false → 403
        $response->assertForbidden();
    });

    it('POST /partner/register does not create a partner when disabled', function () {
        config(['default.partners_can_register' => false]);

        $countBefore = Partner::count();

        $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Test Business',
            'email' => 'newpartner@example.com',
            'consent' => true,
        ]);

        expect(Partner::count())->toBe($countBefore);
    });

    it('respects admin DB setting to disable registration', function () {
        // Config says enabled, but admin DB setting overrides to disabled
        config(['default.partners_can_register' => true]);

        $settingsService = app(SettingsService::class);
        $settingsService->set('partners_can_register', false, null, 'boolean');

        $response = $this->get(registrationUrl());

        $response->assertRedirect(route('partner.login', ['locale' => 'en-us']));
    });
});

// ─────────────────────────────────────────────────────────────────────────
// Registration Enabled Mode
// ─────────────────────────────────────────────────────────────────────────

describe('registration enabled mode', function () {

    beforeEach(function () {
        config(['default.partners_can_register' => true]);
        ensurePrimaryNetwork();
    });

    it('GET /partner/register shows registration form when enabled', function () {
        $response = $this->get(registrationUrl());

        $response->assertOk();
        $response->assertViewIs('partner.auth.register');
        $response->assertViewHas('plans');
        $response->assertViewHas('defaultPlan');
    });

    it('registration form does not show for authenticated partners', function () {
        $partner = Partner::factory()->create(['is_active' => true, 'email_verified_at' => now()]);

        $response = $this->actingAs($partner, 'partner')->get(registrationUrl());

        $response->assertRedirect(route('partner.index', ['locale' => 'en-us']));
    });

    it('POST /partner/register creates partner with default plan', function () {
        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'New Test Business',
            'email' => 'newbusiness@example.com',
            'consent' => true,
            'time_zone' => 'America/New_York',
        ]);

        $partner = Partner::where('email', 'newbusiness@example.com')->first();
        expect($partner)->not->toBeNull();
        expect($partner->plan)->toBe('tier1');
        expect($partner->is_active)->toBeTruthy();
        expect($partner->email_verified_at)->toBeNull(); // Not verified until OTP
    });

    it('new partner receives plan-derived meta from config', function () {
        $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Meta Test Business',
            'email' => 'metatest@example.com',
            'consent' => true,
        ]);

        $partner = Partner::where('email', 'metatest@example.com')->first();
        expect($partner)->not->toBeNull();

        $meta = $partner->meta;
        $planConfig = config('plans.tier1');

        // Plan-derived limits should be present (meta keys differ from config keys)
        expect($meta)->toHaveKeys([
            'loyalty_cards_limit',
            'staff_members_limit',
            'rewards_limit',
            'stamp_cards_limit',
            'vouchers_permission',
            'email_campaigns_permission',
            'agent_api_permission',
        ]);
        expect($meta['staff_members_limit'])->toBe($planConfig['max_staff']);
        expect($meta['loyalty_cards_limit'])->toBe($planConfig['max_cards']);
    });

    it('redirects to OTP verification after successful registration', function () {
        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'OTP Test Business',
            'email' => 'otptest@example.com',
            'consent' => true,
        ]);

        $response->assertRedirect(route('partner.register.otp.verify', ['locale' => 'en-us']));
        $response->assertSessionHas('success');
    });
});

// ─────────────────────────────────────────────────────────────────────────
// Spam & Abuse Controls — Honeypot
// ─────────────────────────────────────────────────────────────────────────

describe('honeypot spam protection', function () {

    beforeEach(function () {
        config(['default.partners_can_register' => true]);
        ensurePrimaryNetwork();
    });

    it('rejects submission when honeypot field is filled', function () {
        $countBefore = Partner::count();

        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Bot Business',
            'email' => 'bot@example.com',
            'consent' => true,
            'website_url' => 'http://spam.com', // honeypot field
        ]);

        // Should silently redirect (not create partner) — looks like success to bot
        $response->assertRedirect();
        expect(Partner::count())->toBe($countBefore);
        expect(Partner::where('email', 'bot@example.com')->exists())->toBeFalse();
    });

    it('accepts submission when honeypot field is empty', function () {
        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Real Business',
            'email' => 'real@example.com',
            'consent' => true,
            'website_url' => '', // honeypot field empty
        ]);

        $partner = Partner::where('email', 'real@example.com')->first();
        expect($partner)->not->toBeNull();
    });

    it('accepts submission when honeypot field is absent', function () {
        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'No Honeypot Business',
            'email' => 'nohoneypot@example.com',
            'consent' => true,
            // honeypot field not submitted at all
        ]);

        $partner = Partner::where('email', 'nohoneypot@example.com')->first();
        expect($partner)->not->toBeNull();
    });
});

// ─────────────────────────────────────────────────────────────────────────
// Spam & Abuse Controls — Rate Limiting
// ─────────────────────────────────────────────────────────────────────────

describe('registration rate limiting', function () {

    beforeEach(function () {
        config(['default.partners_can_register' => true]);
        ensurePrimaryNetwork();
        RateLimiter::clear('partner-registration:127.0.0.1');
    });

    it('allows registration within rate limit', function () {
        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Rate Test 1',
            'email' => 'ratetest1@example.com',
            'consent' => true,
        ]);

        // Should succeed (redirect to OTP or register page)
        $response->assertRedirect();
        expect(Partner::where('email', 'ratetest1@example.com')->exists())->toBeTrue();
    });

    it('blocks excessive registration attempts with 429', function () {
        // Exhaust the rate limit (5 attempts per 10 minutes)
        for ($i = 1; $i <= 5; $i++) {
            $this->post(registrationUrl('partner.register.post'), [
                'name' => "Rate Flood {$i}",
                'email' => "rateflood{$i}@example.com",
                'consent' => true,
            ]);
        }

        // 6th attempt should be rate-limited
        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Rate Flood 6',
            'email' => 'rateflood6@example.com',
            'consent' => true,
        ]);

        $response->assertStatus(429);
        expect(Partner::where('email', 'rateflood6@example.com')->exists())->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────
// Duplicate Handling
// ─────────────────────────────────────────────────────────────────────────

describe('duplicate email handling', function () {

    beforeEach(function () {
        config(['default.partners_can_register' => true]);
        ensurePrimaryNetwork();
    });

    it('rejects registration with an already-used email', function () {
        Partner::factory()->create(['email' => 'existing@example.com']);

        $response = $this->from(registrationUrl())->post(registrationUrl('partner.register.post'), [
            'name' => 'Duplicate Business',
            'email' => 'existing@example.com',
            'consent' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    });

    it('rejects duplicate email case-insensitively', function () {
        Partner::factory()->create(['email' => 'existing@example.com']);

        $response = $this->from(registrationUrl())->post(registrationUrl('partner.register.post'), [
            'name' => 'Case Test',
            'email' => 'EXISTING@example.com',
            'consent' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    });

    it('does not create partial partner rows on validation failure', function () {
        $countBefore = Partner::count();

        $this->post(registrationUrl('partner.register.post'), [
            'name' => '', // Invalid - too short
            'email' => 'valid@example.com',
            'consent' => true,
        ]);

        expect(Partner::count())->toBe($countBefore);
    });
});

// ─────────────────────────────────────────────────────────────────────────
// Billing Mode Behavior
// ─────────────────────────────────────────────────────────────────────────

describe('billing mode behavior during registration', function () {

    beforeEach(function () {
        config(['default.partners_can_register' => true]);
        ensurePrimaryNetwork();
    });

    it('registration works with billing disabled (null provider)', function () {
        config(['default.billing_provider' => null]);

        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Null Billing Business',
            'email' => 'nullbilling@example.com',
            'consent' => true,
        ]);

        $partner = Partner::where('email', 'nullbilling@example.com')->first();
        expect($partner)->not->toBeNull();
        expect($partner->plan)->toBe('tier1');
        expect($partner->stripe_id)->toBeNull();
    });

    it('registration works with stripe provider without requiring payment', function () {
        config(['default.billing_provider' => 'stripe']);

        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Stripe No Pay Business',
            'email' => 'stripenopay@example.com',
            'consent' => true,
        ]);

        $partner = Partner::where('email', 'stripenopay@example.com')->first();
        expect($partner)->not->toBeNull();
        expect($partner->plan)->toBe('tier1');
        // No Stripe customer created during registration
        expect($partner->stripe_id)->toBeNull();
    });

    it('does not invent payment success during registration', function () {
        config(['default.billing_provider' => 'stripe']);

        $this->post(registrationUrl('partner.register.post'), [
            'name' => 'No Fake Payment',
            'email' => 'nofake@example.com',
            'consent' => true,
        ]);

        $partner = Partner::where('email', 'nofake@example.com')->first();

        // Partner should have no billing artifacts
        expect($partner->stripe_id)->toBeNull();
        expect($partner->pm_type)->toBeNull();
        expect($partner->pm_last_four)->toBeNull();
        expect($partner->trial_ends_at)->toBeNull();
    });
});

// ─────────────────────────────────────────────────────────────────────────
// Validation
// ─────────────────────────────────────────────────────────────────────────

describe('registration validation', function () {

    beforeEach(function () {
        config(['default.partners_can_register' => true]);
        ensurePrimaryNetwork();
    });

    it('requires email field', function () {
        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'No Email Business',
            'consent' => true,
        ]);

        $response->assertSessionHasErrors('email');
    });

    it('requires name field', function () {
        $response = $this->post(registrationUrl('partner.register.post'), [
            'email' => 'noname@example.com',
            'consent' => true,
        ]);

        $response->assertSessionHasErrors('name');
    });

    it('requires consent', function () {
        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'No Consent Business',
            'email' => 'noconsent@example.com',
        ]);

        $response->assertSessionHasErrors('consent');
    });

    it('rejects invalid email format', function () {
        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Bad Email Business',
            'email' => 'not-an-email',
            'consent' => true,
        ]);

        $response->assertSessionHasErrors('email');
    });
});

// ─────────────────────────────────────────────────────────────────────────
// Atomicity — No Partial Records
// ─────────────────────────────────────────────────────────────────────────

describe('registration atomicity', function () {

    beforeEach(function () {
        config(['default.partners_can_register' => true]);
        ensurePrimaryNetwork();
    });

    it('rolls back partner when OTP delivery fails', function () {
        // Force mail to throw — simulates SMTP failure, DNS timeout, etc.
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new \Exception('SMTP connection refused'));

        $countBefore = Partner::count();

        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Ghost Partner',
            'email' => 'ghost@example.com',
            'consent' => true,
        ]);

        // No partner row should survive
        expect(Partner::count())->toBe($countBefore);
        expect(Partner::where('email', 'ghost@example.com')->exists())->toBeFalse();

        // User should see error, not success
        $response->assertRedirect(registrationUrl());
        $response->assertSessionHas('error');
        $response->assertSessionMissing('success');
    });

    it('does not leave OTP records when delivery fails', function () {
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new \Exception('Mail transport error'));

        $this->post(registrationUrl('partner.register.post'), [
            'name' => 'No OTP Business',
            'email' => 'nootp@example.com',
            'consent' => true,
        ]);

        // No OTP record should exist for this email
        expect(\App\Models\OtpCode::where('identifier', 'nootp@example.com')->exists())->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────
// Network Assignment — real POST-based registration
// ─────────────────────────────────────────────────────────────────────────

describe('network assignment during registration', function () {

    beforeEach(function () {
        config(['default.partners_can_register' => true]);
    });

    it('assigns primary network and inherits its currency', function () {
        $network = ensurePrimaryNetwork();

        $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Network Test Business',
            'email' => 'networktest@example.com',
            'consent' => true,
        ]);

        $partner = Partner::where('email', 'networktest@example.com')->first();
        expect($partner)->not->toBeNull();
        expect($partner->network_id)->toBe($network->id);
        expect($partner->currency)->toBe($network->currency);
    });

    it('blocks registration when no primary network exists', function () {
        // Ensure no primary network — delete all networks
        \App\Models\Network::query()->delete();

        $countBefore = Partner::count();

        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Networkless Business',
            'email' => 'networkless@example.com',
            'consent' => true,
        ]);

        // Registration should fail — no partner created
        expect(Partner::count())->toBe($countBefore);
        expect(Partner::where('email', 'networkless@example.com')->exists())->toBeFalse();

        // User should see the specific unavailable error
        $response->assertRedirect(registrationUrl());
        $response->assertSessionHas('error', trans('common.registration_unavailable'));
    });

    it('blocks registration when primary network is inactive', function () {
        // Delete all networks, then create only an inactive primary
        \App\Models\Network::query()->delete();
        \App\Models\Network::create([
            'name' => 'Inactive Primary',
            'is_active' => false,
            'is_primary' => true,
            'currency' => 'USD',
        ]);

        $countBefore = Partner::count();

        $response = $this->post(registrationUrl('partner.register.post'), [
            'name' => 'Inactive Network Business',
            'email' => 'inactivenet@example.com',
            'consent' => true,
        ]);

        // Registration should fail
        expect(Partner::count())->toBe($countBefore);
        expect(Partner::where('email', 'inactivenet@example.com')->exists())->toBeFalse();
        $response->assertRedirect(registrationUrl());
        $response->assertSessionHas('error', trans('common.registration_unavailable'));
    });
});
