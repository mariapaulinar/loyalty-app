<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Partner Authentication Service
 *
 * Handles partner authentication, registration, and password management.
 */

namespace App\Services\Partner;

use App\Models\Network;
use App\Models\Partner;
use App\Notifications\Partner\ResetPassword;
use App\Services\Auth\OtpService;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class AuthService
{
    /**
     * Retrieve active partner with login credentials.
     *
     * @param  array  $login  Login credentials.
     * @return object|null Partner object if active, otherwise null.
     */
    public function login(array $login)
    {
        $partnerService = resolve('App\Services\Partner\PartnerService');
        $partner = $partnerService->findActiveByEmail($login['email']);

        $authenticated = false;

        if ($partner) {
            if (Hash::check($login['password'], $partner->password)) {
                if ($partner->is_active == 1) {
                    if (! $partner->email_verified_at) {
                        $partner->email_verified_at = Carbon::now('UTC');
                    }

                    // Update login stats
                    $partner->number_of_times_logged_in = $partner->number_of_times_logged_in + 1;
                    $partner->last_login_at = Carbon::now('UTC');
                    $partner->save();

                    // Successfully authenticated
                    $authenticated = true;
                }
            }
        }

        if ($authenticated) {
            Auth::guard('partner')->login($partner, (bool) $login['remember']);

            return $partner;
        } else {
            return null;
        }
    }

    /**
     * Send login link.
     *
     * @deprecated This method is not currently in use. The Login notification class doesn't exist.
     *             Consider implementing a proper magic link notification or remove this method.
     *
     * @param  array  $login  Login credentials.
     * @return string Partner email address.
     */
    public function sendLoginLink(array $login): string
    {
        // TODO: Implement Partner\Login notification class if magic links are needed
        // For now, this method is deprecated and unused
        throw new \RuntimeException('sendLoginLink is not implemented. Use OTP authentication instead.');
    }

    /**
     * Send link to reset password.
     *
     * @param  string  $email  Email address.
     * @return object|null Partner object if active and found, otherwise null.
     */
    public function sendResetPasswordLink(string $email)
    {
        $partnerService = resolve('App\Services\Partner\PartnerService');
        $partner = $partnerService->findActiveByEmail($email);

        if ($partner) {
            // Set URL defaults to use the partner's preferred locale
            // This ensures the reset link includes the correct locale segment (e.g., /nl-nl/partner/reset-password)
            set_url_locale_for_user($partner);

            // Reset link
            $resetLink = URL::temporarySignedRoute(
                'partner.reset_password',
                now()->addMinutes(120),
                [
                    'email' => $email,
                ]
            );

            // Send reset link
            $partner->notify(new ResetPassword($resetLink));
        }

        return $partner;
    }

    /**
     * Update password.
     *
     * @param  string  $email  Email address.
     * @param  string  $password  New password.
     * @return object|null Partner object if active and found, otherwise null.
     */
    public function updatePassword(string $email, string $password)
    {
        $partnerService = resolve('App\Services\Partner\PartnerService');
        $partner = $partnerService->findActiveByEmail($email);

        if ($partner) {
            $partner->password = bcrypt($password);
            $partner->save();
        }

        return $partner;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRATION (OTP-BASED)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Register a new partner account with OTP verification (passwordless).
     *
     * Atomic: if OTP delivery fails, the partner row is rolled back.
     * No partial records survive a failed registration attempt.
     *
     * @param  array  $data  Registration data (name, email, etc.)
     * @return Partner|null The newly created partner, or null if OTP delivery failed
     */
    public function registerWithOtp(array $data): ?Partner
    {
        $i18n = app()->make('i18n');

        $defaultPlan = Partner::getDefaultPlan();

        // Resolve the default network for this partner. On fresh installs
        // the seeder creates a primary "Default" network. On upgraded installs
        // the admin must mark a network as primary before enabling registration.
        $defaultNetwork = Network::getPrimaryOrFirst();
        if ($defaultNetwork === null) {
            throw new \RuntimeException(
                'Partner self-registration blocked: no active primary network found. '
                . 'Mark a network as primary in Admin > Networks to enable registration.'
            );
        }

        $partnerData = [
            'role' => 1,
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'password' => null, // No password - OTP-only authentication
            'plan' => $defaultPlan,
            'network_id' => $defaultNetwork->id,
            'locale' => $i18n->language->current->locale,
            'currency' => $defaultNetwork->currency ?? $i18n->currency->id,
            'time_zone' => $data['time_zone'] ?? $i18n->time_zone,
            'is_active' => true,
            'email_verified_at' => null, // Will be set after OTP verification
            'meta' => Partner::deriveMetaFromPlan($defaultPlan),
        ];

        return \Illuminate\Support\Facades\DB::transaction(function () use ($partnerData) {
            $partnerService = resolve('App\Services\Partner\PartnerService');
            $partner = $partnerService->store($partnerData);

            // Send OTP — if delivery fails, the transaction rolls back
            $otpService = resolve(OtpService::class);
            $result = $otpService->send(
                identifier: $partner->email,
                identifierType: 'email',
                purpose: 'registration',
                guard: 'partner'
            );

            if (! $result['success']) {
                throw new \RuntimeException('OTP delivery failed: '.($result['message'] ?? 'unknown'));
            }

            // Store registration data in session (outside transaction scope is fine —
            // session is only useful if partner row commits)
            session([
                'otp_registration' => [
                    'partner_id' => $partner->id,
                    'email' => $partner->email,
                ],
            ]);

            return $partner;
        });
    }

    /**
     * Complete OTP registration by verifying the email.
     *
     * @param  Partner  $partner  The partner to verify
     * @return Partner The verified partner
     */
    public function completeOtpRegistration(Partner $partner): Partner
    {
        $partner->email_verified_at = Carbon::now('UTC');
        $partner->save();

        // Log in the partner (remember = true for seamless experience)
        Auth::guard('partner')->login($partner, true);

        return $partner;
    }

    /**
     * Check if partner registration is enabled.
     *
     * Checks the admin-managed setting first (database), falling back to
     * the env-backed config value when no admin setting exists.
     *
     * @return bool True if partners can self-register
     */
    public static function isRegistrationEnabled(): bool
    {
        $settingsService = app(SettingsService::class);
        $adminSetting = $settingsService->get('partners_can_register');

        // If admin has explicitly set this via Settings UI, honor that value
        if ($adminSetting !== null) {
            return (bool) $adminSetting;
        }

        // Fall back to env/config default
        return (bool) config('default.partners_can_register', false);
    }
}
