<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Handles member authentication operations including login, registration,
 * password reset, and OTP-based passwordless authentication.
 */

namespace App\Services\Member;

use App\Models\Member;
use App\Notifications\Member\Registration;
use App\Notifications\Member\ResetPassword;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class AuthService
{
    /**
     * Authenticate member and return member object if successful.
     *
     * @param  array  $login  Login credentials.
     * @return object|null Member object if authenticated, otherwise null.
     */
    public function login(array $login): ?object
    {
        $memberService = resolve('App\Services\Member\MemberService');
        $member = $memberService->findActiveByEmail($login['email']);

        if ($member && Hash::check($login['password'], $member->password) && ($member->is_active == 1 && $member->is_active !== 0)) {
            // Update login stats
            $member->email_verified_at = $member->email_verified_at ?? Carbon::now('UTC');
            $member->number_of_times_logged_in++;
            $member->last_login_at = Carbon::now('UTC');
            $member->save();

            Auth::guard('member')->login($member, (bool) ($login['remember'] ?? false));

            return $member;
        }

        return null;
    }

    /**
     * Send login link to the member's email address.
     *
     * @param  array  $login  Login credentials.
     * @return string Member email address.
     */
    public function sendLoginLink(array $login): string
    {
        $url = URL::temporarySignedRoute(
            'member.login.link',
            now()->addMinutes(30),
            [
                'email' => $login['email'],
                'intended' => redirect()->intended(route('member.cards'))->getTargetUrl(),
            ]
        );

        Notification::route('mail', $login['email'])->notify(new Login($url));

        return $login['email'];
    }

    /**
     * Register a new member account (legacy - sends password email).
     *
     * @param  array  $member  Member details.
     * @return object|null Newly created member or null on failure.
     *
     * @deprecated Use registerWithOtp() for new registrations
     */
    public function register(array $member): ?object
    {
        // The 'from' value can be null or a url the user will be redirected to after registration
        $from = $member['from'] ?? null;
        $password = implode('', Arr::random(range(0, 9), 6));
        $i18n = app()->make('i18n');

        $member = array_merge($member, [
            'role' => 1,
            'name' => $member['name'],
            'password' => bcrypt($password),
            'locale' => $i18n->language->current->locale,
            'currency' => $i18n->currency->id,
            'time_zone' => $member['time_zone'] ?? $i18n->time_zone,
            'accepts_emails' => $member['accepts_emails'],
        ]);

        $member = Arr::except($member, ['consent', 'from']);

        $memberService = resolve('App\Services\Member\MemberService');
        $newMember = $memberService->store($member);

        $newMember->notify(new Registration($member['email'], $password, 'member', $from));

        return $newMember;
    }

    /**
     * Register a new member account with OTP verification (passwordless).
     *
     * Creates a member without a password. The member will verify their email
     * via OTP and can optionally set a password later from their profile.
     *
     * @param  array  $data  Registration data (name, email, accepts_emails, time_zone)
     * @return Member The newly created member (unverified)
     */
    public function registerWithOtp(array $data): Member
    {
        $i18n = app()->make('i18n');

        $memberData = [
            'role' => 1,
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'password' => null, // No password - OTP-only authentication
            'locale' => $i18n->language->current->locale,
            'currency' => $i18n->currency->id,
            'time_zone' => $data['time_zone'] ?? $i18n->time_zone,
            'accepts_emails' => $data['accepts_emails'] ?? false,
            'is_active' => true,
            'email_verified_at' => null, // Will be set after OTP verification
        ];

        $memberService = resolve('App\Services\Member\MemberService');

        return $memberService->store($memberData);
    }

    /**
     * Complete OTP registration by verifying the email.
     *
     * @param  Member  $member  The member to verify
     * @return Member The verified member
     */
    public function completeOtpRegistration(Member $member): Member
    {
        $member->email_verified_at = Carbon::now('UTC');
        $member->number_of_times_logged_in = 1;
        $member->last_login_at = Carbon::now('UTC');
        $member->save();

        return $member;
    }

    /**
     * Send a password reset link to the member's email address.
     *
     * @param  string  $email  Email address.
     * @return object|null Member object if active and found, otherwise null.
     */
    public function sendResetPasswordLink(string $email): ?object
    {
        $memberService = resolve('App\Services\Member\MemberService');
        $member = $memberService->findActiveByEmail($email);

        if ($member) {
            // Set URL defaults to use the member's preferred locale
            // This ensures the reset link includes the correct locale segment (e.g., /nl-nl/reset-password)
            set_url_locale_for_user($member);

            $resetLink = URL::temporarySignedRoute(
                'member.reset_password',
                now()->addMinutes(120),
                [
                    'email' => $email,
                ]
            );

            // Send reset link
            $member->notify(new ResetPassword($resetLink));
        }

        return $member;
    }

    /**
     * Update the member's password.
     *
     * @param  string  $email  Email address.
     * @param  string  $password  New password.
     * @return object|null Member object if active and found, otherwise null.
     */
    public function updatePassword(string $email, string $password): ?object
    {
        $memberService = resolve('App\Services\Member\MemberService');
        $member = $memberService->findActiveByEmail($email);

        if ($member) {
            $member->password = bcrypt($password);
            $member->save();
        }

        return $member;
    }
}
