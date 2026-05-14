<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\View\View;

/**
 * Member Referral Dashboard Controller
 *
 * This controller powers the member-facing referral experience - the place where
 * members see their campaigns, get their referral codes, and track their success.
 *
 * The UI is designed to be magical, like walking into Disneyland for the first time.
 * Every campaign is presented with its reward cards front and center, making it
 * crystal clear what members will earn.
 */
class ReferralController extends Controller
{
    public function __construct(
        protected ReferralService $referralService
    ) {}

    /**
     * Display the member's referral dashboard.
     *
     * Shows all active campaigns the member can participate in, along with their
     * referral codes, stats, and recent activity.
     *
     * Campaign Discovery Logic:
     * -------------------------
     * Members see campaigns based on the cards they hold. If they have a card that's
     * used as a reward in any active campaign, they can participate.
     *
     * This is intentionally broad - we want to maximize participation and give
     * members every opportunity to earn rewards.
     */
    public function index(): View
    {
        $member = auth('member')->user();

        // Get all active campaigns this member can participate in (as referrer)
        $activeCampaigns = $this->referralService->getActiveCampaignsForMember($member);

        $programs = [];

        foreach ($activeCampaigns as $campaign) {
            // Get or generate the referral code and stats
            $stats = $this->referralService->getMemberStats($member, $campaign);

            // Get recent referral history
            $referrals = $this->referralService->getMemberReferrals($member, $campaign, 5);

            $programs[] = [
                'campaign' => $campaign,
                'code' => $stats['code'] ?? '',
                'share_url' => $stats['share_url'] ?? '',
                'stats' => $stats,
                'referrals' => $referrals,
                'settings' => $campaign, // For backward compatibility with view
            ];
        }

        // Get programs where member is a referee (was referred)
        $refereePrograms = \App\Models\Referral::where('referee_id', $member->id)
            ->with(['referralCode.referralSetting.refereeCard'])
            ->get()
            ->map(function ($referral) {
                return [
                    'referral' => $referral,
                    'campaign' => $referral->referralCode->referralSetting,
                    'card' => $referral->referralCode->referralSetting->refereeCard,
                    'status' => $referral->status,
                ];
            })
            ->filter(fn($item) => $item['campaign'] && $item['card']);

        return view('member.referrals.index', [
            'programs' => $programs,
            'refereePrograms' => $refereePrograms,
        ]);
    }
}
