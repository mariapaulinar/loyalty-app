<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Member interface for viewing and managing their stamp cards.
 * Beautiful, engaging, and motivating user experience.
 *
 * Design Tenets:
 * - **Visual Delight**: Beautiful card designs with animations
 * - **Progress Clarity**: Clear visualization of progress
 * - **Mobile-first**: Perfect on all devices
 * - **Gamification**: Celebrate achievements
 */

namespace App\Http\Controllers\Member;

use App\Events\MemberUnenrolledFromStampCard;
use App\Http\Controllers\Controller;
use App\Models\StampCard;
use App\Services\Card\AnalyticsService;
use App\Services\StampService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StampCardController extends Controller
{
    public function __construct(
        private readonly StampService $stampService,
        private readonly AnalyticsService $analyticsService
    ) {}

    /**
     * Display a specific stamp card detail page.
     *
     * GET /stamp-card/{stamp_card_id}
     */
    public function show(string $locale, string $stamp_card_id, Request $request): View
    {
        // Find active stamp card
        $stampCard = StampCard::where(function ($query) use ($stamp_card_id) {
            $query->where('id', $stamp_card_id)
                ->orWhere('unique_identifier', $stamp_card_id);
        })
            ->where('is_active', true)
            ->whereHas('partner', function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('network_id')
                            ->orWhereHas('network', fn ($nq) => $nq->where('is_active', true));
                    });
            })
            ->first();

        if (! $stampCard) {
            return view('member.stamp-card.stamp-card-404');
        }

        // Increment views (tracks unique visitor views)
        $stampCard->load('club'); // Load club relationship for analytics
        $this->analyticsService->incrementViews($stampCard);

        // Get enrollment if member is logged in
        $enrollment = null;
        if (auth('member')->check()) {
            $enrollment = $stampCard->enrollments()
                ->where('member_id', auth('member')->id())
                ->first();
        }

        return view('member.stamp-card.index', [
            'stampCard' => $stampCard,
            'enrollment' => $enrollment,
        ]);
    }

    /**
     * Enroll member in stamp card program.
     *
     * GET /stamp-card/{stamp_card_id}/enroll
     */
    public function enroll(string $locale, string $stamp_card_id, Request $request): RedirectResponse
    {
        // If the member is not authenticated, store pending action and redirect to login
        if (! auth('member')->check()) {
            // Store pending action - will be executed after login
            $pendingActionService = resolve(\App\Services\Member\PendingCardActionService::class);
            $pendingActionService->store('stamp_card', $stamp_card_id);

            // Set redirect target to My Cards (action will add the card automatically)
            session()->put('from.member', route('member.cards'));

            return redirect()->route('member.login');
        }

        // Find active stamp card
        $stampCard = StampCard::where(function ($query) use ($stamp_card_id) {
            $query->where('id', $stamp_card_id)
                ->orWhere('unique_identifier', $stamp_card_id);
        })
            ->where('is_active', true)
            ->whereHas('partner', function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('network_id')
                            ->orWhereHas('network', fn ($nq) => $nq->where('is_active', true));
                    });
            })
            ->first();

        if (! $stampCard) {
            return redirect()->route('member.index');
        }

        // Enroll member and redirect to My Cards page with success toast
        $member = auth('member')->user();
        $this->stampService->enrollMember($stampCard, $member);

        return redirect()
            ->route('member.cards')
            ->with('toast', [
                'type' => 'success',
                'text' => trans('common.card_added'),
            ]);
    }

    /**
     * Unenroll member from stamp card program.
     *
     * GET /stamp-card/{stamp_card_id}/unenroll
     */
    public function unenroll(string $locale, string $stamp_card_id, Request $request): RedirectResponse
    {
        // If the member is not authenticated, store the current URL and redirect to the login page
        if (! auth('member')->check()) {
            session()->put('from.member', url()->current());

            return redirect()->route('member.login');
        }

        // Find active stamp card
        $stampCard = StampCard::where(function ($query) use ($stamp_card_id) {
            $query->where('id', $stamp_card_id)
                ->orWhere('unique_identifier', $stamp_card_id);
        })
            ->where('is_active', true)
            ->whereHas('partner', function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('network_id')
                            ->orWhereHas('network', fn ($nq) => $nq->where('is_active', true));
                    });
            })
            ->first();

        if (! $stampCard) {
            return redirect()->route('member.index');
        }

        // Hide card from "My Cards" (preserve all progress)
        $member = auth('member')->user();
        $enrollment = $stampCard->enrollments()
            ->where('member_id', $member->id)
            ->first();

        if ($enrollment) {
            // Set is_active = false to hide from My Cards (preserves all stamps & progress!)
            $enrollment->is_active = false;
            $enrollment->save();

            // Fire unenrollment event for audit logging (stamps NOT lost, just hidden)
            event(new MemberUnenrolledFromStampCard(
                card: $stampCard,
                member: $member,
                stampsLost: 0, // No stamps lost - preserved!
                pendingRewardsLost: 0, // No rewards lost - preserved!
                reason: 'Member requested - Hidden from My Cards'
            ));
        }

        return redirect()
            ->route('member.stamp-card', ['stamp_card_id' => $stampCard->id])
            ->with('toast', [
                'type' => 'success',
                'text' => trans('common.card_removed'),
            ]);
    }

    /**
     * Get stamp card data for API/AJAX.
     *
     * GET /api/member/stamp-cards
     */
    public function apiIndex(): JsonResponse
    {
        $member = auth('member')->user();
        $club = $member->club;

        $stampCards = $this->stampService->getMemberStampCards($member, $club);

        $stampCardsData = $stampCards->map(function ($card) use ($member) {
            $enrollment = $card->enrollments()
                ->where('member_id', $member->id)
                ->first();

            $progressPercentage = $enrollment
                ? round(($enrollment->current_stamps / $card->stamps_required) * 100, 1)
                : 0;

            return [
                'id' => $card->id,
                'title' => $card->title,
                'description' => $card->description,
                'reward_title' => $card->reward_title,
                'reward_description' => $card->reward_description,
                'current_stamps' => $enrollment->current_stamps ?? 0,
                'stamps_required' => $card->stamps_required,
                'progress_percentage' => $progressPercentage,
                'pending_rewards' => $enrollment->pending_rewards ?? 0,
                'completed_count' => $enrollment->completed_count ?? 0,
                'redeemed_count' => $enrollment->redeemed_count ?? 0,
                'enrolled_at' => $enrollment?->enrolled_at?->toIso8601String(),
                'last_stamp_at' => $enrollment?->last_stamp_at?->toIso8601String(),
                'last_completed_at' => $enrollment?->last_completed_at?->toIso8601String(),
                'next_stamp_expires_at' => $enrollment?->next_stamp_expires_at?->toIso8601String(),
                'stamp_icon' => $card->stamp_icon,
                'colors' => [
                    'bg' => $card->bg_color,
                    'text' => $card->text_color,
                    'stamp' => $card->stamp_color,
                    'empty_stamp' => $card->empty_stamp_color,
                ],
                'media' => [
                    'background' => $card->getFirstMediaUrl('background'),
                    'logo' => $card->getFirstMediaUrl('logo'),
                ],
                'settings' => [
                    'require_staff_for_redemption' => $card->require_staff_for_redemption,
                    'allow_view_history' => $card->allow_member_view_history,
                    'show_monetary_value' => $card->show_monetary_value,
                ],
                'reward_value' => $card->show_monetary_value ? $card->reward_value : null,
                'reward_points' => $card->reward_points,
            ];
        });

        $stats = $this->stampService->getMemberStats($member);

        return response()->json([
            'success' => true,
            'data' => [
                'stamp_cards' => $stampCardsData,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Get transaction history for a stamp card.
     *
     * GET /api/member/stamp-cards/{id}/history
     */
    public function apiHistory(string $locale, int $id, Request $request): JsonResponse
    {
        $member = $request->user('member_api');
        $card = StampCard::findOrFail($id);

        // Verify card belongs to member's club
        if ($card->club_id !== $member->club_id) {
            return response()->json([
                'success' => false,
                'message' => 'Stamp card not found',
            ], 404);
        }

        // Check if member can view history
        if (! $card->allow_member_view_history) {
            return response()->json([
                'success' => false,
                'message' => 'History viewing is not enabled for this stamp card',
            ], 403);
        }

        $transactions = $member->stampTransactions()
            ->where('stamp_card_id', $card->id)
            ->whereIn('event', ['earned', 'redeemed', 'completed', 'expired', 'adjusted'])
            ->latest()
            ->take(100)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'event' => $transaction->event,
                    'stamps' => $transaction->stamps,
                    'purchase_amount' => $transaction->purchase_amount,
                    'notes' => $transaction->notes,
                    'created_at' => $transaction->created_at->toIso8601String(),
                    'staff_name' => $transaction->staff?->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions,
            ],
        ]);
    }
}
