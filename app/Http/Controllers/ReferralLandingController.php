<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers;

use App\Services\I18nService;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Referral Landing Controller
 *
 * Handles the two-stage referral link flow:
 * 1. Short, shareable URL (/r/CODE) - detects locale and redirects
 * 2. Localized landing page (/{locale}/r/CODE) - shows campaign details
 *
 * This architecture ensures:
 * - Clean, shareable URLs for social media
 * - Full i18n support for the landing experience
 * - Proper locale detection and routing
 */
class ReferralLandingController extends Controller
{
    public function __construct(
        protected ReferralService $referralService,
        protected I18nService $i18nService
    ) {}

    /**
     * Redirect from short URL to localized landing page.
     *
     * This method handles the initial /r/CODE request, detects the user's
     * preferred language, and redirects to the localized landing page.
     *
     * @param  string  $code  The referral code
     * @return RedirectResponse Always redirects to localized version
     */
    public function redirect(string $code): RedirectResponse
    {
        // Detect user's preferred locale (e.g., "en-us")
        $locale = $this->i18nService->getPreferredLocale($parsedForUrl = true);

        // Redirect to localized landing page: /{locale}/r/{code}
        return redirect()->route('referral.landing', [
            'locale' => $locale,
            'code' => $code,
        ]);
    }

    /**
     * Display the localized referral landing page.
     *
     * This method handles the /{locale}/r/CODE request and either:
     * - Shows the landing page (for unauthenticated users)
     * - Applies the referral and redirects (for authenticated users)
     *
     * @param  string  $locale  The locale from URL (automatically injected by Laravel)
     * @param  string  $code  The referral code
     * @return View|RedirectResponse Landing page or redirect
     */
    public function show(string $locale, string $code): View|RedirectResponse
    {
        $referralCode = $this->referralService->findByCode($code);

        // Get current locale from app (already set by SetLocale middleware)
        $localeFormatted = str_replace('_', '-', strtolower(app()->getLocale()));

        // 1. Invalid or unknown code
        if (! $referralCode) {
            return redirect()
                ->route('member.register', ['locale' => $localeFormatted])
                ->with('error', trans('common.referral.invalid_code'));
        }

        $campaign = $referralCode->referralSetting;

        // 2. Campaign disabled or not found
        if (! $campaign || ! $campaign->is_enabled) {
            return redirect()
                ->route('member.register', ['locale' => $localeFormatted])
                ->with('info', trans('common.referral.program_disabled_redirect'));
        }

        // 3. Store referral context in session for registration flow
        // Store referral context in session for registration flow
        session([
            'referral_code_id' => $referralCode->id,
            'referral_campaign_id' => $campaign->id,
            'referral_referee_card_id' => $campaign->referee_card_id, // Card to auto-follow
        ]);

        // 4. Handle Authenticated Members (Fast-track)
        $member = auth('member')->user();
        if ($member) {
            // Self-referral check
            if ($member->id === $referralCode->member_id) {
                return redirect()
                    ->route('member.referrals', ['locale' => $localeFormatted])
                    ->with('info', trans('common.referral.cannot_refer_yourself'));
            }

            // Create pending referral
            $referral = $this->referralService->createPendingReferral($referralCode, $member);

            if ($referral) {
                // Auto-follow the referee card so it appears in "My Cards"
                if (!$member->cards()->where('card_id', $campaign->referee_card_id)->exists()) {
                    $member->cards()->attach($campaign->referee_card_id);
                }

                return redirect()
                    ->route('member.referrals', ['locale' => $localeFormatted])
                    ->with('success', trans('common.referral.referral_applied'));
            } else {
                return redirect()
                    ->route('member.referrals', ['locale' => $localeFormatted])
                    ->with('info', trans('common.referral.already_referred'));
            }
        }

        // 5. Unauthenticated: Show Landing Page
        return view('referral.landing', [
            'referralCode' => $referralCode,
            'campaign' => $campaign,
            'referrer' => $referralCode->member,
            'referrerCard' => $campaign->referrerCard,
            'refereeCard' => $campaign->refereeCard,
            'referrerPoints' => $campaign->referrer_points,
            'refereePoints' => $campaign->referee_points,
        ]);
    }
}
