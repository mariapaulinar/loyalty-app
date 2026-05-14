<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Core service for OTP (One-Time Password) authentication flow.
 * Handles generation, delivery, and verification of 6-digit login codes.
 *
 * Design Tenets:
 * - **Security**: Timing-safe comparisons, rate limiting, attempt tracking
 * - **Multi-Guard**: Works with member, staff, partner, and admin guards
 * - **Audit-Ready**: All operations are logged via ActivityLogService
 * - **Future-Proof**: Designed for SMS support (email only for now)
 *
 * Usage:
 * $otpService = app(OtpService::class);
 *
 * // Send OTP
 * $result = $otpService->send('user@example.com', 'email', 'login', 'member');
 *
 * // Verify OTP
 * $result = $otpService->verify('user@example.com', '123456', 'login', 'member');
 */

namespace App\Services\Auth;

use App\Mail\OtpCodeMail;
use App\Models\Admin;
use App\Models\Member;
use App\Models\OtpCode;
use App\Models\Partner;
use App\Models\Staff;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    /**
     * OTP expiration time in minutes.
     */
    protected const EXPIRATION_MINUTES = 10;

    /**
     * Maximum OTP requests per identifier within the rate limit window.
     */
    protected const RATE_LIMIT_MAX_ATTEMPTS = 5;

    /**
     * Rate limit window in minutes.
     */
    protected const RATE_LIMIT_WINDOW_MINUTES = 5;

    /**
     * Maximum verification attempts per OTP.
     */
    protected const MAX_VERIFICATION_ATTEMPTS = 5;

    /**
     * Cooldown period before resend is allowed (in seconds).
     */
    protected const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Weak patterns to exclude from generated codes.
     * These patterns are easily guessable and may look suspicious to users.
     *
     * @var array<string>
     */
    protected const EXCLUDED_PATTERNS = [
        '123456', // Sequential ascending
        '654321', // Sequential descending
        '111111', // All ones
        '000000', // All zeros
        '222222', // All twos
        '333333', // All threes
        '444444', // All fours
        '555555', // All fives
        '666666', // All sixes
        '777777', // All sevens
        '888888', // All eights
        '999999', // All nines
        '012345', // Sequential from zero
        '543210', // Sequential descending from five
    ];

    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Send an OTP code to the given identifier.
     *
     * @param  string  $identifier  Email address (or phone number in future)
     * @param  string  $identifierType  'email' or 'phone'
     * @param  string  $purpose  'login', 'verify_email', 'verify_phone', 'password_reset'
     * @param  string  $guard  'member', 'staff', 'partner', 'admin'
     * @return array{success: bool, message: string, cooldown_seconds?: int, expires_at?: string}
     */
    public function send(
        string $identifier,
        string $identifierType = 'email',
        string $purpose = 'login',
        string $guard = 'member'
    ): array {
        $identifier = strtolower(trim($identifier));

        // Check rate limit
        if ($this->isRateLimited($identifier)) {
            return [
                'success' => false,
                'message' => 'Too many OTP requests. Please wait before trying again.',
                'cooldown_seconds' => $this->getRemainingCooldown($identifier),
            ];
        }

        // Check for existing active OTP with cooldown
        $existingOtp = $this->getActiveOtp($identifier, $purpose, $guard);
        if ($existingOtp && ! $this->canResend($existingOtp)) {
            return [
                'success' => false,
                'message' => 'Please wait before requesting a new code.',
                'cooldown_seconds' => $this->getResendCooldownRemaining($existingOtp),
            ];
        }

        // Invalidate any existing active OTPs for this identifier/purpose
        $this->invalidateExistingOtps($identifier, $purpose, $guard);

        // Generate new OTP
        $plainCode = $this->generateCode();
        $otp = $this->createOtp($identifier, $identifierType, $plainCode, $purpose, $guard);

        // Send via appropriate channel
        $sent = match ($identifierType) {
            'email' => $this->sendViaEmail($identifier, $plainCode, $purpose, $guard),
            'phone' => $this->sendViaSms($identifier, $plainCode, $purpose, $guard),
            default => false,
        };

        if (! $sent) {
            $otp->delete();

            return [
                'success' => false,
                'message' => 'Failed to send verification code. Please try again.',
            ];
        }

        // Record rate limit hit
        $this->recordRateLimitHit($identifier);

        // Log the activity
        $this->logOtpSent($identifier, $purpose, $guard);

        return [
            'success' => true,
            'message' => 'Verification code sent successfully.',
            'expires_at' => $otp->expires_at->toIso8601String(),
            'cooldown_seconds' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    /**
     * Verify an OTP code.
     *
     * @return array{success: bool, message: string, user?: Model, remaining_attempts?: int}
     */
    public function verify(
        string $identifier,
        string $code,
        string $purpose = 'login',
        string $guard = 'member'
    ): array {
        $identifier = strtolower(trim($identifier));
        $code = trim($code);

        // Find active OTP
        $otp = $this->getActiveOtp($identifier, $purpose, $guard);

        if (! $otp) {
            $this->logOtpVerificationFailed($identifier, $purpose, $guard, 'No active OTP found');

            return [
                'success' => false,
                'message' => trans('otp.code_expired'),
                'expired' => true,
            ];
        }

        // Check if OTP is expired
        if ($otp->isExpired()) {
            $this->logOtpVerificationFailed($identifier, $purpose, $guard, 'OTP expired');

            return [
                'success' => false,
                'message' => trans('otp.code_expired'),
                'expired' => true,
            ];
        }

        // Check if OTP is locked
        if ($otp->isLocked()) {
            $this->logOtpVerificationFailed($identifier, $purpose, $guard, 'OTP locked');

            return [
                'success' => false,
                'message' => trans('otp.code_locked'),
                'locked' => true,
            ];
        }

        // Verify the code using timing-safe comparison
        if (! $this->verifyCode($otp, $code)) {
            $otp->incrementAttempts();
            $remaining = $otp->remainingAttempts();

            $this->logOtpVerificationFailed($identifier, $purpose, $guard, 'Invalid code');

            return [
                'success' => false,
                'message' => $remaining > 0
                    ? "Invalid code. {$remaining} attempt(s) remaining."
                    : 'Too many failed attempts. Please request a new code.',
                'remaining_attempts' => $remaining,
            ];
        }

        // Mark as verified
        $otp->markAsVerified();

        // Invalidate all other OTPs for this identifier/purpose
        $this->invalidateExistingOtps($identifier, $purpose, $guard);

        // Get the user
        $user = $this->findUser($identifier, $guard);

        $this->logOtpVerificationSuccess($identifier, $purpose, $guard, $user);

        return [
            'success' => true,
            'message' => 'Verification successful.',
            'user' => $user,
        ];
    }

    /**
     * Check if a user exists for the given identifier and guard.
     *
     * @return array{exists: bool, has_password: bool, user?: Model}
     */
    public function checkUser(string $identifier, string $guard = 'member'): array
    {
        $identifier = strtolower(trim($identifier));
        $user = $this->findUser($identifier, $guard);

        if (! $user) {
            return [
                'exists' => false,
                'has_password' => false,
            ];
        }

        return [
            'exists' => true,
            'has_password' => ! empty($user->password),
            'user' => $user,
        ];
    }

    /**
     * Get the resend cooldown remaining in seconds for an identifier.
     */
    public function getResendCooldown(string $identifier, string $purpose = 'login', string $guard = 'member'): int
    {
        $identifier = strtolower(trim($identifier));
        $otp = $this->getActiveOtp($identifier, $purpose, $guard);

        if (! $otp) {
            return 0;
        }

        return $this->getResendCooldownRemaining($otp);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CODE GENERATION & VERIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate a cryptographically secure 6-digit OTP code.
     * Excludes weak patterns (sequential, repeated digits) for security.
     */
    protected function generateCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (in_array($code, self::EXCLUDED_PATTERNS, true));

        return $code;
    }

    /**
     * Verify the code using timing-safe comparison.
     */
    protected function verifyCode(OtpCode $otp, string $code): bool
    {
        return Hash::check($code, $otp->code);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OTP CRUD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a new OTP record.
     */
    protected function createOtp(
        string $identifier,
        string $identifierType,
        string $plainCode,
        string $purpose,
        string $guard
    ): OtpCode {
        return OtpCode::create([
            'identifier' => $identifier,
            'identifier_type' => $identifierType,
            'code' => Hash::make($plainCode),
            'purpose' => $purpose,
            'guard' => $guard,
            'attempts' => 0,
            'max_attempts' => self::MAX_VERIFICATION_ATTEMPTS,
            'is_verified' => false,
            'expires_at' => now()->addMinutes(self::EXPIRATION_MINUTES),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get the currently active OTP for an identifier.
     */
    protected function getActiveOtp(string $identifier, string $purpose, string $guard): ?OtpCode
    {
        return OtpCode::query()
            ->forIdentifier($identifier)
            ->forPurpose($purpose)
            ->forGuard($guard)
            ->active()
            ->latest()
            ->first();
    }

    /**
     * Invalidate all existing OTPs for an identifier/purpose combination.
     */
    protected function invalidateExistingOtps(string $identifier, string $purpose, string $guard): void
    {
        OtpCode::query()
            ->forIdentifier($identifier)
            ->forPurpose($purpose)
            ->forGuard($guard)
            ->where('is_verified', false)
            ->delete();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELIVERY CHANNELS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Send OTP via email.
     */
    protected function sendViaEmail(string $email, string $code, string $purpose, string $guard): bool
    {
        try {
            // Look up user to get their preferred locale for email translations
            $user = $this->findUser($email, $guard);
            $locale = $user?->preferredLocale() ?? config('app.locale');

            Mail::to($email)->send(new OtpCodeMail($code, $purpose, $guard, self::EXPIRATION_MINUTES, $locale));

            return true;
        } catch (\Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Send OTP via SMS (placeholder for future implementation).
     */
    protected function sendViaSms(string $phone, string $code, string $purpose, string $guard): bool
    {
        // TODO: Implement SMS delivery when ready
        // For now, return false to indicate SMS is not supported
        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RATE LIMITING
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if the identifier is rate limited.
     */
    protected function isRateLimited(string $identifier): bool
    {
        $key = $this->getRateLimitKey($identifier);

        return RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_MAX_ATTEMPTS);
    }

    /**
     * Record a rate limit hit.
     */
    protected function recordRateLimitHit(string $identifier): void
    {
        $key = $this->getRateLimitKey($identifier);
        RateLimiter::hit($key, self::RATE_LIMIT_WINDOW_MINUTES * 60);
    }

    /**
     * Get remaining cooldown seconds for rate limiting.
     */
    protected function getRemainingCooldown(string $identifier): int
    {
        $key = $this->getRateLimitKey($identifier);

        return RateLimiter::availableIn($key);
    }

    /**
     * Get the rate limit key for an identifier.
     */
    protected function getRateLimitKey(string $identifier): string
    {
        return 'otp:'.sha1($identifier);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RESEND COOLDOWN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if an OTP can be resent (cooldown period passed).
     */
    protected function canResend(OtpCode $otp): bool
    {
        return $otp->created_at->addSeconds(self::RESEND_COOLDOWN_SECONDS)->isPast();
    }

    /**
     * Get remaining resend cooldown in seconds.
     */
    protected function getResendCooldownRemaining(OtpCode $otp): int
    {
        $availableAt = $otp->created_at->addSeconds(self::RESEND_COOLDOWN_SECONDS);

        if ($availableAt->isPast()) {
            return 0;
        }

        return (int) now()->diffInSeconds($availableAt, false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // USER LOOKUP
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Find a user by identifier and guard.
     */
    protected function findUser(string $identifier, string $guard): ?Model
    {
        $model = match ($guard) {
            'member' => Member::class,
            'staff' => Staff::class,
            'partner' => Partner::class,
            'admin' => Admin::class,
            default => null,
        };

        if (! $model) {
            return null;
        }

        return $model::query()
            ->where('email', $identifier)
            ->where('is_active', true)
            ->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACTIVITY LOGGING
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Log OTP sent event.
     */
    protected function logOtpSent(string $identifier, string $purpose, string $guard): void
    {
        $this->activityLog->log(
            "OTP sent to {$identifier} for {$purpose}",
            null,
            'otp_sent',
            [
                'identifier' => $identifier,
                'purpose' => $purpose,
                'guard' => $guard,
                'ip' => request()->ip(),
            ],
            'authentication'
        );
    }

    /**
     * Log OTP verification success.
     */
    protected function logOtpVerificationSuccess(string $identifier, string $purpose, string $guard, ?Model $user): void
    {
        if ($user) {
            $this->activityLog->logAuth('otp_login', $user, [
                'method' => 'otp',
                'purpose' => $purpose,
            ]);
        } else {
            $this->activityLog->log(
                "OTP verified for {$identifier}",
                null,
                'otp_verified',
                [
                    'identifier' => $identifier,
                    'purpose' => $purpose,
                    'guard' => $guard,
                    'ip' => request()->ip(),
                ],
                'authentication'
            );
        }
    }

    /**
     * Log OTP verification failure.
     */
    protected function logOtpVerificationFailed(string $identifier, string $purpose, string $guard, string $reason): void
    {
        $this->activityLog->log(
            "OTP verification failed for {$identifier}: {$reason}",
            null,
            'otp_verification_failed',
            [
                'identifier' => $identifier,
                'purpose' => $purpose,
                'guard' => $guard,
                'reason' => $reason,
                'ip' => request()->ip(),
            ],
            'authentication'
        );
    }
}
