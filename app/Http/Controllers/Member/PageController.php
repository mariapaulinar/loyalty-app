<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\StampCard;
use App\Models\Voucher;
use App\Services\Card\CardService;
use App\Services\MarkdownService;
use App\Services\SettingsService;
use App\Services\TierService;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Handle the incoming request and display the home page.
     *
     * Homepage = Pure Discovery
     * Shows all publicly available programs to everyone (guests and logged-in members).
     * Sorted by urgency (ending soon) → freshness (newest) to maximize engagement.
     *
     * For personalized card management, members use the "My Cards" page.
     *
     * @return \Illuminate\View\View
     */
    public function index(string $locale, Request $request)
    {
        // ═════════════════════════════════════════════════════════════════════
        // LOYALTY CARDS
        // Discovery: Show all public, active, non-expired cards
        // Sort: Expiring soon → Newest → Evergreen
        // ═════════════════════════════════════════════════════════════════════

        $cards = Card::where('is_active', true)
            ->where('is_visible_by_default', true)
            ->where(function ($query) {
                // Card must not be expired (or no expiration set)
                $query->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>', now());
            })
            ->whereHas('partner', function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('network_id')
                            ->orWhereHas('network', fn ($nq) => $nq->where('is_active', true));
                    });
            })
            ->with(['club', 'partner'])
            ->orderByRaw('
                CASE 
                    WHEN expiration_date IS NULL THEN 1 
                    ELSE 0 
                END ASC
            ') // Non-expiring cards last
            ->orderBy('expiration_date', 'asc') // Expiring soon first (urgency)
            ->orderBy('issue_date', 'desc') // Then newest (freshness)
            ->limit(100)
            ->get();

        // ═════════════════════════════════════════════════════════════════════
        // VOUCHERS
        // Discovery: Show all public, active, valid, non-batch vouchers
        // Sort: Expiring soon → Newest → Evergreen
        // ═════════════════════════════════════════════════════════════════════

        $vouchers = Voucher::where('is_active', true)
            ->where('is_visible_by_default', true)
            ->whereNull('batch_id') // Exclude batch-generated vouchers
            ->where(function ($query) {
                // Voucher must be valid (started, not future-dated)
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                // Voucher must not be expired (or no expiration set)
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->whereHas('creator', function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('network_id')
                            ->orWhereHas('network', fn ($nq) => $nq->where('is_active', true));
                    });
            })
            ->with(['club'])
            ->orderByRaw('
                CASE 
                    WHEN valid_until IS NULL THEN 1 
                    ELSE 0 
                END ASC
            ') // Non-expiring vouchers last
            ->orderBy('valid_until', 'asc') // Expiring soon first (urgency)
            ->orderBy('created_at', 'desc') // Then newest (freshness)
            ->limit(100)
            ->get();

        // ═════════════════════════════════════════════════════════════════════
        // STAMP CARDS
        // Discovery: Show all public, active, valid stamp cards
        // Sort: Expiring soon → Newest → Evergreen
        // ═════════════════════════════════════════════════════════════════════

        $stampCards = StampCard::where('is_active', true)
            ->where('is_visible_by_default', true)
            ->where(function ($query) {
                // Stamp card must be valid (started, not future-dated)
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                // Stamp card must not be expired (or no expiration set)
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->whereHas('partner', function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('network_id')
                            ->orWhereHas('network', fn ($nq) => $nq->where('is_active', true));
                    });
            })
            ->with(['club', 'partner'])
            ->orderByRaw('
                CASE 
                    WHEN valid_until IS NULL THEN 1 
                    ELSE 0 
                END ASC
            ') // Non-expiring cards last
            ->orderBy('valid_until', 'asc') // Expiring soon first (urgency)
            ->orderBy('created_at', 'desc') // Then newest (freshness)
            ->limit(100)
            ->get();

        // ═════════════════════════════════════════════════════════════════════
        // LAYOUT SELECTION
        // Check the configured homepage layout and return the appropriate view.
        // - 'directory': Full catalog of all programs (default)
        // - 'showcase': Editorial single-business presentation
        // - 'portal': Minimal authentication-focused landing
        // ═════════════════════════════════════════════════════════════════════

        $homepageLayout = config('default.homepage_layout', 'directory');

        // Check for database-stored setting (overrides config)
        $layoutSetting = \App\Models\Setting::where('key', 'homepage_layout')->first();
        if ($layoutSetting && $layoutSetting->value) {
            $homepageLayout = $layoutSetting->value;
        }

        // Route to appropriate view based on layout
        switch ($homepageLayout) {
            case 'showcase':
                // Get showcase-specific settings
                $showHowItWorks = config('default.homepage_show_how_it_works', true);
                $showTiers = config('default.homepage_show_tiers', true);
                $showMemberCount = config('default.homepage_show_member_count', false);
                
                // Check database overrides
                $howItWorksSetting = \App\Models\Setting::where('key', 'homepage_show_how_it_works')->first();
                if ($howItWorksSetting !== null) {
                    $showHowItWorks = (bool) $howItWorksSetting->value;
                }
                $tiersSetting = \App\Models\Setting::where('key', 'homepage_show_tiers')->first();
                if ($tiersSetting !== null) {
                    $showTiers = (bool) $tiersSetting->value;
                }
                $memberCountSetting = \App\Models\Setting::where('key', 'homepage_show_member_count')->first();
                if ($memberCountSetting !== null) {
                    $showMemberCount = (bool) $memberCountSetting->value;
                }

                // Get hero background image if available
                $heroImageUrl = $layoutSetting?->getFirstMediaUrl('homepage_hero_image');

                // Get member count if enabled
                $memberCount = $showMemberCount ? \App\Models\Member::count() : 0;

                return view('member.home-showcase', compact(
                    'cards', 'vouchers', 'stampCards',
                    'showHowItWorks', 'showTiers', 'showMemberCount',
                    'heroImageUrl', 'memberCount'
                ));

            case 'portal':
                return view('member.home-portal');

            case 'directory':
            default:
                // Pass all programs to the view (original behavior)
                return view('member.home', compact('cards', 'vouchers', 'stampCards'));
        }
    }

    /**
     * Handle the incoming request and display the unified My Cards page.
     *
     * Consolidates the old dashboard + wallet into one consumer-grade experience.
     * Provides sorting, filtering, and complete card management in one place.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard(string $locale, Request $request, CardService $cardService, TierService $tierService, SettingsService $settingsService)
    {
        // Define allowed sort values
        $allowedSortValues = [
            'last_points_claimed_at,desc',
            'last_points_claimed_at,asc',
            'total_amount_purchased,desc',
            'total_amount_purchased,asc',
            'number_of_rewards_claimed,desc',
            'number_of_rewards_claimed,asc',
            'last_reward_claimed_at,desc',
            'last_reward_claimed_at,asc',
        ];

        // Extract query parameters or get from cookies
        $sort = $request->query('sort', $request->cookie('wallet_sort', 'last_points_claimed_at,desc'));
        $hide_expired = $request->query('hide_expired', $request->cookie('wallet_hide_expired', 'true'));

        // Validate sort parameter
        if (! in_array($sort, $allowedSortValues)) {
            $sort = 'last_points_claimed_at,desc';
        }

        // Validate hide_expired parameter
        if (! in_array($hide_expired, ['true', 'false'])) {
            $hide_expired = 'true';
        }

        $hide_expired_bool = filter_var($hide_expired, FILTER_VALIDATE_BOOLEAN);
        [$column, $direction] = explode(',', $sort);

        // Initialize tier data
        $memberTiers = collect();
        $tierProgress = [];

        // MY CARDS = ONLY followed cards (via card_member pivot)
        // Ignore transaction history - only show cards member explicitly added or auto-added on transaction
        if (auth('member')->check()) {
            $member = auth('member')->user();
            $memberId = $member->id;

            // Get ONLY followed cards (in card_member pivot table)
            $cards = $cardService->findActiveCardsFollowedByMember($memberId);

            // Pre-calculate balances with a single query
            $cardIds = $cards->pluck('id');
            $balances = \App\Models\Transaction::whereIn('card_id', $cardIds)
                ->where('member_id', $memberId)
                ->where('expires_at', '>', \Carbon\Carbon::now())
                ->selectRaw('card_id, SUM(points - points_used) as balance')
                ->groupBy('card_id')
                ->pluck('balance', 'card_id');

            $cards = $cards->sortByDesc(function ($card) use ($balances) {
                $balance = $balances[$card->id] ?? 0;

                return [$balance, $card->issue_date];
            });

            // Fetch tier data for each club the member has cards in
            if ($settingsService->get('tiers.enabled', true)) {
                $clubIds = $cards->pluck('club_id')->unique();

                foreach ($clubIds as $clubId) {
                    $club = \App\Models\Club::find($clubId);
                    if ($club) {
                        $memberTier = $member->memberTiers()
                            ->forClub($club)
                            ->active()
                            ->with('tier')
                            ->first();

                        // Only show tier if member has actually earned points in this club
                        if ($memberTier) {
                            // Get qualifying stats to check if member has earned anything
                            $qualifyingStats = $tierService->getQualifyingStats($member, $club);
                            $lifetimePoints = $qualifyingStats['lifetime_points'] ?? 0;

                            // Only show tier if member has earned points (not just default tier assignment)
                            if ($lifetimePoints > 0) {
                                $nextTier = $memberTier->tier->getNextTier();
                                $progress = [];

                                // If there's a next tier, calculate progress toward it
                                if ($nextTier) {
                                    $progress = [
                                        'points' => [
                                            'current' => $lifetimePoints,
                                            'threshold' => $nextTier->points_threshold,
                                            'percentage' => $nextTier->points_threshold > 0
                                                ? min(100, ($lifetimePoints / $nextTier->points_threshold) * 100)
                                                : 100,
                                        ],
                                    ];
                                }

                                // Find the primary card for this club (first card member has for this club)
                                $primaryCard = $cards->where('club_id', $clubId)->first();

                                $memberTiers->push([
                                    'memberTier' => $memberTier,
                                    'club' => $club,
                                    'card' => $primaryCard,
                                    'progress' => $progress,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // Eager load relationships to avoid N+1 for all users (guests and members)
        $cards->load('club', 'partner');

        // Fetch member's vouchers (if authenticated)
        // ONLY shows vouchers linked via member_voucher pivot (saved OR claimed)
        $vouchers = collect();
        if (auth('member')->check()) {
            $member = auth('member')->user();

            // Get ONLY vouchers linked to member via member_voucher pivot
            // This includes: claimed batch vouchers + manually saved vouchers
            $vouchers = $member->vouchers()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('valid_until')
                        ->orWhere('valid_until', '>', now());
                })
                ->whereHas('creator', function ($query) {
                    $query->where('is_active', true)
                        ->where(function ($q) {
                            $q->whereNull('network_id')
                                ->orWhereHas('network', fn ($nq) => $nq->where('is_active', true));
                        });
                })
                ->with(['club'])
                ->orderByDesc('member_voucher.created_at') // Most recently added first
                ->get();
        }

        // Fetch enrolled stamp cards for authenticated member
        $stampCards = collect();
        if (auth('member')->check()) {
            $stampCards = StampCard::where('is_active', true)
                ->whereHas('enrollments', function ($query) {
                    $query->where('member_id', auth('member')->id())
                        ->where('is_active', true);
                })
                ->whereHas('partner', function ($query) {
                    $query->where('is_active', true)
                        ->where(function ($q) {
                            $q->whereNull('network_id')
                                ->orWhereHas('network', fn ($nq) => $nq->where('is_active', true));
                        });
                })
                ->with(['club', 'partner', 'enrollments' => function ($query) {
                    $query->where('member_id', auth('member')->id());
                }])
                ->get();
        }

        // Define dashboard blocks with consistent Lucide icons
        // Icon philosophy: use intuitive, recognizable icons that communicate action clearly
        $dashboardBlocks = [
            [
                'link' => route('member.data.list', ['name' => 'account']),
                'icon' => 'user-circle',  // Profile/account - personal, friendly
                'title' => trans('common.account_settings'),
                'desc' => trans('common.memberDashboardBlocks.account_settings'),
            ],
            [
                'link' => route('member.data.list', ['name' => 'request-links']),
                'icon' => 'send',  // Request/send - action-oriented
                'title' => trans('common.request_points'),
                'desc' => trans('common.memberDashboardBlocks.request_points'),
            ],
            [
                'link' => route('member.code.enter'),
                'icon' => 'hash',  // Code/hash - represents alphanumeric input
                'title' => trans('common.enter_code'),
                'desc' => trans('common.memberDashboardBlocks.enter_code'),
            ],
            [
                'link' => route('member.referrals'),
                'icon' => 'user-plus',  // Referrals - adding friends
                'title' => trans('common.referral.refer_friend'),
                'desc' => trans('common.share_love'),
            ],
        ];

        // Get personalized greeting using member's timezone
        $greeting = getGreeting(auth('member')->user()?->time_zone);

        // Prepare unified My Cards view
        $view = view('member.my-cards', compact('cards', 'sort', 'hide_expired', 'vouchers', 'stampCards', 'dashboardBlocks', 'memberTiers', 'greeting'));

        // Create cookies for user preferences
        $sortCookie = cookie('wallet_sort', $sort, 43200, '/', null, false, true, false, 'lax');
        $hideExpiredCookie = cookie('wallet_hide_expired', $hide_expired, 43200, '/', null, false, true, false, 'lax');

        // Return response with cookies attached
        return response($view)->withCookie($sortCookie)->withCookie($hideExpiredCookie);
    }

    /**
     * Handle the incoming request and display a page.
     *
     * @param  string  $page  The markdown file to be displayed.
     * @return \Illuminate\View\View
     */
    private function displayPage(string $locale, Request $request, string $page, MarkdownService $markdownService)
    {
        $data = $markdownService->transWithMeta($page);
        $content = $data['content'];
        $meta = $data['meta'];

        return view("member.content.$page", compact('content', 'meta'));
    }

    public function about(string $locale, Request $request, MarkdownService $markdownService)
    {
        return $this->displayPage($locale, $request, 'about', $markdownService);
    }

    public function contact(string $locale, Request $request, MarkdownService $markdownService)
    {
        return $this->displayPage($locale, $request, 'contact', $markdownService);
    }

    public function faq(string $locale, Request $request)
    {
        return view('member.content.faq');
    }

    public function terms(string $locale, Request $request, MarkdownService $markdownService)
    {
        return $this->displayPage($locale, $request, 'terms', $markdownService);
    }

    public function privacy(string $locale, Request $request, MarkdownService $markdownService)
    {
        return $this->displayPage($locale, $request, 'privacy', $markdownService);
    }
}
