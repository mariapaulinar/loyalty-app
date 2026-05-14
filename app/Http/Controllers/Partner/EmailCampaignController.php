<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * EmailCampaignController
 *
 * Purpose:
 * Handles all partner email campaign operations including compose,
 * preview, send, and campaign management.
 *
 * Architecture:
 * - All actions scoped to authenticated partner
 * - Uses EmailCampaignService for business logic
 * - Sequential sending via AJAX for progress tracking
 *
 * Routes:
 * - GET  /partner/email-campaigns/compose     → compose()
 * - POST /partner/email-campaigns/preview     → preview()
 * - POST /partner/email-campaigns             → send()
 * - GET  /partner/email-campaigns             → index()
 * - GET  /partner/email-campaigns/{campaign}  → show()
 * - POST /partner/email-campaigns/{id}/next   → sendNext()
 * - DELETE /partner/email-campaigns/{id}      → destroy()
 */

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\EmailCampaign;
use App\Models\StampCard;
use App\Models\Tier;
use App\Models\Voucher;
use App\Services\EmailCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailCampaignController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        protected EmailCampaignService $campaignService
    ) {
        $this->middleware(function ($request, $next) {
            // Permission check via EntitlementService
            if (auth('partner')->check() && ! app(\App\Services\EntitlementService::class)->can(auth('partner')->user(), 'email_campaigns')) {
                abort(403);
            }

            return $next($request);
        });
    }

    /**
     * Show the compose form for creating a new campaign.
     *
     * Loads all partner's cards, stamp cards, vouchers, and tiers
     * for segment configuration dropdowns.
     */
    public function compose(): View
    {
        $partner = auth('partner')->user();

        // Get partner's active cards for segment dropdowns
        $cards = Card::where('created_by', $partner->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get partner's active stamp cards
        $stampCards = StampCard::where('created_by', $partner->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'stamps_required']);

        // Get partner's active vouchers (not batch vouchers)
        $vouchers = Voucher::where('created_by', $partner->id)
            ->where('is_active', true)
            ->whereNull('batch_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get partner's clubs for tier segment
        $clubs = $partner->clubs()->get(['id', 'name']);

        // Get all tiers (grouped by club)
        $tiers = Tier::whereIn('club_id', $clubs->pluck('id'))
            ->where('is_active', true)
            ->orderBy('level')
            ->get(['id', 'name', 'club_id']);

        // Get segment types and available locales
        $segmentTypes = $this->campaignService->getSegmentTypes();
        $availableLocales = $this->campaignService->getAvailableLocales($partner);

        // Note: $languages is already shared to all views by I18nMiddleware

        return view('partner.email-campaigns.compose', compact(
            'cards',
            'stampCards',
            'vouchers',
            'clubs',
            'tiers',
            'segmentTypes',
            'availableLocales',
            'partner'
        ));
    }

    /**
     * Preview segment count OR email content (AJAX).
     *
     * Two modes:
     * - mode=count: Returns recipient count for segment
     * - mode=email: Returns rendered email HTML for preview
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'mode' => 'required|in:count,email',
            'segment_type' => 'required_if:mode,count|string',
            'segment_config' => 'nullable|array',
            'subject' => 'required_if:mode,email|array',
            'body' => 'required_if:mode,email|array',
            'locale' => 'nullable|string',
        ]);

        $partner = auth('partner')->user();

        if ($request->mode === 'count') {
            // Segment recipient count preview
            $preview = $this->campaignService->previewSegment(
                $partner,
                $request->segment_type,
                $request->segment_config ?? []
            );

            return response()->json($preview);
        }

        // Email content preview - render with sample member data
        $locale = $request->locale ?? config('app.locale');
        $subject = $request->subject[$locale] ?? $request->subject[config('app.locale')] ?? '';
        $body = $request->body[$locale] ?? $request->body[config('app.locale')] ?? '';

        // Sample data for personalization preview
        $sampleName = trans('common.email_campaign.preview_sample_name');
        $sampleEmail = 'preview@example.com';

        // Apply sample personalization to both subject and body
        $subject = str_replace(
            ['{name}', '{email}'],
            [$sampleName, $sampleEmail],
            $subject
        );

        $body = str_replace(
            ['{name}', '{email}'],
            [$sampleName, $sampleEmail],
            $body
        );

        // Render email preview template (without mail component dependencies)
        $html = view('emails.campaign.preview', [
            'body' => $body,
            'partner' => $partner,
            'locale' => $locale,
        ])->render();

        return response()->json([
            'subject' => $subject,
            'html' => $html,
        ]);
    }

    /**
     * Create campaign and redirect to show page.
     *
     * Validates all inputs including translatable content,
     * saves sender settings, and creates the campaign with recipients.
     */
    public function send(Request $request): RedirectResponse
    {
        $defaultLocale = config('app.locale');
        $isDraft = $request->input('send_action') === 'save_draft';

        // Validation rules are relaxed for drafts
        $subjectRule = $isDraft ? 'nullable|string|max:255' : 'required|string|max:255';
        $bodyRule = $isDraft ? 'nullable|string|max:50000' : 'required|string|max:50000';

        $validated = $request->validate([
            // Action type
            'send_action' => 'required|in:send_now,save_draft',
            // Translatable fields - default locale required for send_now
            'subject' => 'required|array',
            'subject.'.$defaultLocale => $subjectRule,
            'subject.*' => 'nullable|string|max:255',
            'body' => 'required|array',
            'body.'.$defaultLocale => $bodyRule,
            'body.*' => 'nullable|string|max:50000',
            // Sender settings
            'sender_name' => 'nullable|string|max:100',
            'reply_to' => 'nullable|email|max:255',
            // Segment
            'segment_type' => 'required|string',
            'segment_config' => 'nullable|array',
            // Validate segment-specific config
            'segment_config.card_id' => 'nullable|uuid|exists:cards,id',
            'segment_config.stamp_card_id' => 'nullable|uuid|exists:stamp_cards,id',
            'segment_config.voucher_id' => 'nullable|uuid|exists:vouchers,id',
            'segment_config.club_id' => 'nullable|uuid|exists:clubs,id',
            'segment_config.tier_id' => 'nullable|uuid|exists:tiers,id',
            'segment_config.threshold' => 'nullable|integer|min:1',
            'segment_config.stamps_remaining' => 'nullable|integer|min:1|max:20',
            'segment_config.days' => 'nullable|integer|min:7|max:365',
            'segment_config.locale' => 'nullable|string|max:10',
        ]);

        $partner = auth('partner')->user();

        // Save sender settings to partner meta (persists for future campaigns)
        if ($request->filled('sender_name') || $request->filled('reply_to')) {
            $partner->updateCampaignSenderSettings(
                $request->sender_name,
                $request->reply_to
            );
        }

        try {
            // Filter out empty translations
            $subject = array_filter($validated['subject'], fn ($v) => ! empty($v));
            $body = array_filter($validated['body'], fn ($v) => ! empty($v));

            $campaign = $this->campaignService->createCampaign(
                $partner,
                $subject,
                $body,
                $validated['segment_type'],
                $validated['segment_config'] ?? [],
                $isDraft
            );

            if ($isDraft) {
                return redirect()
                    ->route('partner.email-campaigns.index')
                    ->with('success', trans('common.email_campaign.draft_saved'));
            }

            return redirect()
                ->route('partner.email-campaigns.show', $campaign)
                ->with('success', trans('common.email_campaign.created'));
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * List all campaigns for the partner.
     *
     * Ordered by most recent first with pagination.
     */
    public function index(): View
    {
        $partner = auth('partner')->user();

        $campaigns = EmailCampaign::forPartner($partner)
            ->orderByDesc('created_at')
            ->paginate(15);

        // Get stats for the overview cards
        $stats = [
            'total' => EmailCampaign::forPartner($partner)->count(),
            'sent' => EmailCampaign::forPartner($partner)->status('sent')->count(),
            'sending' => EmailCampaign::forPartner($partner)->status('sending')->count(),
            'total_recipients' => EmailCampaign::forPartner($partner)->sum('recipient_count'),
            'total_delivered' => EmailCampaign::forPartner($partner)->sum('sent_count'),
        ];

        return view('partner.email-campaigns.index', compact('campaigns', 'stats'));
    }

    /**
     * Show a specific campaign with sending progress.
     *
     * If campaign needs sending, the modal will auto-open
     * to continue/start the sequential sending process.
     */
    public function show(string $locale, EmailCampaign $campaign): View
    {
        $partner = auth('partner')->user();

        // Verify ownership
        if ($campaign->partner_id !== $partner->id) {
            abort(403);
        }

        // Get progress data
        $progress = $this->campaignService->getProgress($campaign);

        // Get recent recipients for the table
        $recipients = $campaign->recipients()
            ->with('member:id,name,email')
            ->orderByDesc('sent_at')
            ->orderByDesc('updated_at')
            ->take(50)
            ->get();

        // Note: $languages is already shared to all views by I18nMiddleware

        return view('partner.email-campaigns.show', compact(
            'campaign',
            'progress',
            'recipients'
        ));
    }

    /**
     * Send the next email in sequence (AJAX).
     *
     * Called repeatedly by the browser until campaign is complete.
     * Returns progress data for the UI to update.
     */
    public function sendNext(string $locale, EmailCampaign $campaign): JsonResponse
    {
        $partner = auth('partner')->user();

        try {
            $result = $this->campaignService->sendNext($campaign, $partner);

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 403);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => trans('common.email_campaign.error.send_failed'),
            ], 500);
        }
    }

    /**
     * Activate a draft campaign for sending.
     *
     * Creates recipients and changes status to pending,
     * then redirects to the show page where sending can begin.
     */
    public function activate(string $locale, EmailCampaign $campaign): RedirectResponse
    {
        $partner = auth('partner')->user();

        // Ensure campaign belongs to this partner
        if ($campaign->partner_id !== $partner->id) {
            abort(403, trans('common.email_campaign.error.not_owner'));
        }

        // Can only activate drafts
        if (! $campaign->isDraft()) {
            return back()->withErrors([
                'error' => trans('common.email_campaign.error.invalid_status'),
            ]);
        }

        try {
            $this->campaignService->activateCampaign($campaign, $partner);

            return redirect()
                ->route('partner.email-campaigns.show', $campaign)
                ->with('success', trans('common.email_campaign.activated'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete a campaign.
     *
     * Cannot delete campaigns that are currently sending.
     */
    public function destroy(string $locale, EmailCampaign $campaign): RedirectResponse
    {
        $partner = auth('partner')->user();

        try {
            $this->campaignService->deleteCampaign($campaign, $partner);

            return redirect()
                ->route('partner.email-campaigns.index')
                ->with('success', trans('common.email_campaign.deleted'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
