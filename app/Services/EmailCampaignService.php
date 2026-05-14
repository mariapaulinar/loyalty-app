<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * EmailCampaignService
 *
 * Purpose:
 * Core business logic for email campaigns. Handles segment queries,
 * campaign creation, sequential sending, and progress tracking.
 *
 * Architecture:
 * - Segment queries find reachable members based on loyalty data
 * - Members must have interacted with partner's cards AND opted in
 * - Sequential sending: one email per request for resume capability
 * - All queries are partner-isolated to prevent data leakage
 *
 * Design philosophy:
 * - Fail gracefully: one failed email doesn't stop the campaign
 * - Resume anywhere: browser can close and reopen without data loss
 * - Audit trail: every send attempt is logged for debugging
 */

namespace App\Services;

use App\Mail\CampaignEmail;
use App\Models\Card;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\Member;
use App\Models\Partner;
use App\Models\StampCard;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailCampaignService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected I18nService $i18nService
    ) {}

    // ═══════════════════════════════════════════════════════════════════
    // SEGMENT TYPES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Get available segment types with their config requirements.
     *
     * Each segment type defines:
     * - label: Display name for the UI
     * - description: Help text explaining the segment
     * - config: Required configuration fields
     *
     * Segments are filtered based on partner entitlements:
     * - Points/card segments require loyalty_cards entitlement
     * - Stamp segments require stamp_cards entitlement
     * - Voucher segments require vouchers entitlement
     *
     * @param  Partner|null  $partner  Optional partner for permission filtering
     * @return array<string, array{label: string, description: string, config: array<string>}>
     */
    public function getSegmentTypes(?Partner $partner = null): array
    {
        // Resolve partner from auth if not provided
        if ($partner === null && auth('partner')->check()) {
            $partner = auth('partner')->user();
        }

        $segments = [
            'all_members' => [
                'label' => trans('common.email_campaign.segment.all_members'),
                'description' => trans('common.email_campaign.segment.all_members_desc'),
                'config' => [],
            ],
            'inactive' => [
                'label' => trans('common.email_campaign.segment.inactive'),
                'description' => trans('common.email_campaign.segment.inactive_desc'),
                'config' => ['days'],
            ],
            'locale' => [
                'label' => trans('common.email_campaign.segment.locale'),
                'description' => trans('common.email_campaign.segment.locale_desc'),
                'config' => ['locale'],
            ],
        ];

        // Points/loyalty card segments (require loyalty_cards entitlement)
        if (! $partner || app(\App\Services\EntitlementService::class)->can($partner, 'loyalty_cards')) {
            $segments['card_members'] = [
                'label' => trans('common.email_campaign.segment.card_members'),
                'description' => trans('common.email_campaign.segment.card_members_desc'),
                'config' => ['card_id'],
            ];
            $segments['points_below'] = [
                'label' => trans('common.email_campaign.segment.points_below'),
                'description' => trans('common.email_campaign.segment.points_below_desc'),
                'config' => ['card_id', 'threshold'],
            ];
            $segments['points_above'] = [
                'label' => trans('common.email_campaign.segment.points_above'),
                'description' => trans('common.email_campaign.segment.points_above_desc'),
                'config' => ['card_id', 'threshold'],
            ];
            $segments['tier'] = [
                'label' => trans('common.email_campaign.segment.tier'),
                'description' => trans('common.email_campaign.segment.tier_desc'),
                'config' => ['club_id', 'tier_id'],
            ];
        }

        // Stamp card segments (require stamp_cards entitlement)
        if (! $partner || app(\App\Services\EntitlementService::class)->can($partner, 'stamp_cards')) {
            $segments['stamps_in_progress'] = [
                'label' => trans('common.email_campaign.segment.stamps_in_progress'),
                'description' => trans('common.email_campaign.segment.stamps_in_progress_desc'),
                'config' => ['stamp_card_id', 'stamps_remaining'],
            ];
        }

        // Voucher segments (require vouchers entitlement)
        if (! $partner || app(\App\Services\EntitlementService::class)->can($partner, 'vouchers')) {
            $segments['has_voucher'] = [
                'label' => trans('common.email_campaign.segment.has_voucher'),
                'description' => trans('common.email_campaign.segment.has_voucher_desc'),
                'config' => ['voucher_id'],
            ];
        }

        return $segments;
    }

    /**
     * Get locales where at least one reachable member exists.
     *
     * Only shows languages that have opted-in members for
     * the locale segment dropdown.
     *
     * @param  Partner  $partner  The partner to scope members to
     * @return array<array{locale: string, label: string, count: int}>
     */
    public function getAvailableLocales(Partner $partner): array
    {
        $memberIds = $this->getReachableMemberIds($partner);

        $locales = Member::whereIn('id', $memberIds)
            ->where('accepts_emails', true)
            ->whereNotNull('locale')
            ->where('locale', '!=', '')
            ->selectRaw('locale, COUNT(*) as member_count')
            ->groupBy('locale')
            ->orderBy('member_count', 'desc')
            ->get();

        // Get language info from the I18n service
        $translationsData = $this->i18nService->getAllTranslations();
        $languages = $translationsData['all'] ?? [];
        $languageMap = collect($languages)->keyBy('locale');

        return $locales->map(function ($row) use ($languageMap) {
            $langInfo = $languageMap[$row->locale] ?? null;

            return [
                'locale' => $row->locale,
                'label' => $langInfo
                    ? "{$langInfo['languageName']} ({$langInfo['countryCode']})"
                    : $row->locale,
                'count' => (int) $row->member_count,
            ];
        })->toArray();
    }

    // ═══════════════════════════════════════════════════════════════════
    // SEGMENT PREVIEW
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Preview segment results without creating a campaign.
     *
     * Used by the compose form to show recipient counts in real-time
     * as the partner configures their segment.
     *
     * @param  Partner  $partner  The partner scoping the query
     * @param  string  $segmentType  The type of segment
     * @param  array  $config  Segment configuration values
     * @return array{count: int, sample: array}
     */
    public function previewSegment(Partner $partner, string $segmentType, array $config = []): array
    {
        $query = $this->buildSegmentQuery($partner, $segmentType, $config);

        $count = $query->count();
        $sample = $query->take(5)->get(['id', 'name', 'email']);

        return [
            'count' => $count,
            'sample' => $sample->map(fn ($m) => [
                'name' => $m->name ?? trans('common.anonymous'),
                'email' => $this->maskEmail($m->email),
            ])->toArray(),
        ];
    }

    /**
     * Mask email for privacy in preview.
     *
     * Shows first 2 chars + *** + domain
     *
     * Example: jo***@example.com
     */
    private function maskEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return '***@***.***';
        }

        [$local, $domain] = explode('@', $email);
        $masked = substr($local, 0, 2).'***';

        return "{$masked}@{$domain}";
    }

    // ═══════════════════════════════════════════════════════════════════
    // CAMPAIGN CREATION
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Create a new email campaign and populate recipients.
     *
     * This is a transactional operation:
     * 1. Validate segment configuration
     * 2. Create campaign record
     * 3. Find all matching members
     * 4. Create recipient records for each
     *
     * @param  Partner  $partner  The campaign owner
     * @param  array  $subject  Translatable subject lines
     * @param  array  $body  Translatable body content
     * @param  string  $segmentType  The segment filter type
     * @param  array  $config  Segment configuration
     * @return EmailCampaign The created campaign
     *
     * @throws \InvalidArgumentException If segment config is invalid
     */
    /**
     * Create a new email campaign.
     *
     * @param  Partner  $partner  The partner creating the campaign
     * @param  array  $subject  Translatable subject lines
     * @param  array  $body  Translatable body content
     * @param  string  $segmentType  Type of audience segmentation
     * @param  array  $config  Segment configuration options
     * @param  bool  $isDraft  If true, saves as draft (no recipients created yet)
     */
    public function createCampaign(
        Partner $partner,
        array $subject,
        array $body,
        string $segmentType,
        array $config = [],
        bool $isDraft = false
    ): EmailCampaign {
        // Validate segment configuration
        $this->validateSegmentConfig($partner, $segmentType, $config);

        return DB::transaction(function () use ($partner, $subject, $body, $segmentType, $config, $isDraft) {
            // Create the campaign
            $campaign = EmailCampaign::create([
                'partner_id' => $partner->id,
                'subject' => $subject,
                'body' => $body,
                'segment_type' => $segmentType,
                'segment_config' => $config,
                'status' => $isDraft ? 'draft' : 'pending',
                'recipient_count' => 0,
                'sent_count' => 0,
                'failed_count' => 0,
            ]);

            // For drafts, don't create recipients yet
            if ($isDraft) {
                return $campaign;
            }

            // Get matching members
            $members = $this->buildSegmentQuery($partner, $segmentType, $config)
                ->get(['id', 'email']);

            // Create recipient records
            $recipients = $members->map(fn ($member) => [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'campaign_id' => $campaign->id,
                'member_id' => $member->id,
                'email' => $member->email,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Bulk insert for performance
            foreach ($recipients->chunk(500) as $chunk) {
                EmailCampaignRecipient::insert($chunk->toArray());
            }

            // Update campaign with recipient count
            $campaign->update(['recipient_count' => $recipients->count()]);

            return $campaign;
        });
    }

    /**
     * Validate segment configuration.
     *
     * Ensures:
     * - Required config fields are present
     * - Referenced entities exist
     * - Referenced entities belong to the partner
     *
     * @throws \InvalidArgumentException If validation fails
     */
    private function validateSegmentConfig(Partner $partner, string $segmentType, array $config): void
    {
        $types = $this->getSegmentTypes();

        if (! isset($types[$segmentType])) {
            throw new \InvalidArgumentException(
                trans('common.email_campaign.error.invalid_segment_type')
            );
        }

        $requiredFields = $types[$segmentType]['config'];

        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                throw new \InvalidArgumentException(
                    trans('common.email_campaign.error.missing_config', ['field' => $field])
                );
            }
        }

        // Validate ownership of referenced entities
        if (! empty($config['card_id'])) {
            $card = Card::find($config['card_id']);
            if (! $card || $card->created_by !== $partner->id) {
                throw new \InvalidArgumentException(
                    trans('common.email_campaign.error.card_not_found')
                );
            }
        }

        if (! empty($config['stamp_card_id'])) {
            $stampCard = StampCard::find($config['stamp_card_id']);
            if (! $stampCard || $stampCard->created_by !== $partner->id) {
                throw new \InvalidArgumentException(
                    trans('common.email_campaign.error.stamp_card_not_found')
                );
            }
        }

        if (! empty($config['voucher_id'])) {
            $voucher = Voucher::find($config['voucher_id']);
            if (! $voucher || $voucher->created_by !== $partner->id) {
                throw new \InvalidArgumentException(
                    trans('common.email_campaign.error.voucher_not_found')
                );
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // SEQUENTIAL SENDING
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Send the next pending email in a campaign.
     *
     * This is the heart of the sequential sending system:
     * 1. Find next pending recipient
     * 2. Send email using member's preferred locale
     * 3. Update recipient status
     * 4. Update campaign counters
     * 5. Check if campaign is complete
     *
     * Called repeatedly by the browser until no pending recipients remain.
     *
     * @param  EmailCampaign  $campaign  The campaign to process
     * @param  Partner  $partner  The campaign owner (for isolation check)
     * @return array{sent: int, failed: int, total: int, status: string, progress: float, done: bool}
     *
     * @throws \InvalidArgumentException If campaign doesn't belong to partner
     */
    public function sendNext(EmailCampaign $campaign, Partner $partner): array
    {
        // Verify partner ownership
        if ($campaign->partner_id !== $partner->id) {
            throw new \InvalidArgumentException(
                trans('common.email_campaign.error.not_owner')
            );
        }

        // Start campaign if pending
        if ($campaign->isPending()) {
            $campaign->update([
                'status' => 'sending',
                'started_at' => now(),
            ]);
        }

        // Get next pending recipient
        $recipient = $campaign->recipients()
            ->where('status', 'pending')
            ->with('member')
            ->first();

        // No more pending recipients
        if (! $recipient) {
            return $this->finalizeCampaign($campaign);
        }

        // Attempt to send
        try {
            $this->sendToRecipient($campaign, $recipient, $partner);

            // Update recipient and campaign
            $recipient->markAsSent();
            $campaign->increment('sent_count');
        } catch (\Throwable $e) {
            // Log error but don't stop the campaign
            Log::warning('Email campaign send failed', [
                'campaign_id' => $campaign->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);

            $recipient->markAsFailed($e->getMessage());
            $campaign->increment('failed_count');
        }

        // Check if complete
        if ($campaign->isComplete()) {
            return $this->finalizeCampaign($campaign);
        }

        return [
            'sent' => $campaign->fresh()->sent_count,
            'failed' => $campaign->fresh()->failed_count,
            'total' => $campaign->recipient_count,
            'status' => 'sending',
            'progress' => $campaign->fresh()->getProgressPercentage(),
            'done' => false,
        ];
    }

    /**
     * Send email to a specific recipient.
     *
     * Uses the member's preferred locale for content,
     * with fallback to default locale.
     *
     * @throws \Throwable If sending fails
     */
    private function sendToRecipient(
        EmailCampaign $campaign,
        EmailCampaignRecipient $recipient,
        Partner $partner
    ): void {
        // Skip if recipient can't receive
        if (! $recipient->canReceive()) {
            throw new \RuntimeException(
                trans('common.email_campaign.error.recipient_unavailable')
            );
        }

        $member = $recipient->member;
        $locale = $member->preferredLocale();

        Mail::to($recipient->email)->send(new CampaignEmail(
            campaign: $campaign,
            member: $member,
            partner: $partner,
            memberLocale: $locale
        ));
    }

    /**
     * Finalize a completed campaign.
     *
     * Sets status to 'sent' and records completion time.
     */
    private function finalizeCampaign(EmailCampaign $campaign): array
    {
        $campaign->update([
            'status' => 'sent',
            'completed_at' => now(),
        ]);

        return [
            'sent' => $campaign->sent_count,
            'failed' => $campaign->failed_count,
            'total' => $campaign->recipient_count,
            'status' => 'sent',
            'progress' => 100.0,
            'done' => true,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // CAMPAIGN PROGRESS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Get current campaign progress.
     *
     * Used by the show page to display stats and determine
     * if sending modal should be shown.
     */
    public function getProgress(EmailCampaign $campaign): array
    {
        // Campaign is "done" only when status is 'sent' or 'failed' (fully processed)
        // Draft and pending campaigns are NOT done, even if they have no recipients yet
        $isDone = in_array($campaign->status, ['sent', 'failed']);

        return [
            'sent' => $campaign->sent_count,
            'failed' => $campaign->failed_count,
            'total' => $campaign->recipient_count,
            'status' => $campaign->status,
            'progress' => $campaign->getProgressPercentage(),
            'done' => $isDone,
            'started_at' => $campaign->started_at?->toIso8601String(),
            'completed_at' => $campaign->completed_at?->toIso8601String(),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // DRAFT ACTIVATION
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Activate a draft campaign for sending.
     *
     * Creates recipient records and changes status to pending,
     * making the campaign ready to start sending.
     *
     * @throws \InvalidArgumentException If activation is not allowed
     */
    public function activateCampaign(EmailCampaign $campaign, Partner $partner): void
    {
        if ($campaign->partner_id !== $partner->id) {
            throw new \InvalidArgumentException(
                trans('common.email_campaign.error.not_owner')
            );
        }

        if (! $campaign->isDraft()) {
            throw new \InvalidArgumentException(
                trans('common.email_campaign.error.invalid_status')
            );
        }

        DB::transaction(function () use ($campaign, $partner) {
            // Get matching members for the segment
            $members = $this->buildSegmentQuery(
                $partner,
                $campaign->segment_type,
                $campaign->segment_config ?? []
            )->get(['id', 'email']);

            // Create recipient records
            $recipients = $members->map(fn ($member) => [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'campaign_id' => $campaign->id,
                'member_id' => $member->id,
                'email' => $member->email,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Bulk insert for performance
            foreach ($recipients->chunk(500) as $chunk) {
                EmailCampaignRecipient::insert($chunk->toArray());
            }

            // Update campaign status and recipient count
            $campaign->update([
                'status' => 'pending',
                'recipient_count' => $recipients->count(),
            ]);
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // CAMPAIGN DELETION
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Delete a campaign (soft delete).
     *
     * Only allows deletion if:
     * - Campaign belongs to partner
     * - Campaign is not currently sending
     *
     * @throws \InvalidArgumentException If deletion is not allowed
     */
    public function deleteCampaign(EmailCampaign $campaign, Partner $partner): void
    {
        if ($campaign->partner_id !== $partner->id) {
            throw new \InvalidArgumentException(
                trans('common.email_campaign.error.not_owner')
            );
        }

        if ($campaign->isSending()) {
            throw new \InvalidArgumentException(
                trans('common.email_campaign.error.cannot_delete_sending')
            );
        }

        $campaign->delete();
    }

    // ═══════════════════════════════════════════════════════════════════
    // SEGMENT QUERY BUILDING
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Build query for members matching a segment.
     *
     * All queries:
     * - Are scoped to partner's reachable members
     * - Only include opted-in members (accepts_emails = true)
     * - Only include members with valid email addresses
     *
     * @param  Partner  $partner  The partner to scope to
     * @param  string  $segmentType  The segment type
     * @param  array  $config  Segment configuration
     * @return Builder Query builder for Member model
     */
    private function buildSegmentQuery(Partner $partner, string $segmentType, array $config): Builder
    {
        // Base query: reachable, opted-in members with email
        $memberIds = $this->getReachableMemberIds($partner);

        $query = Member::whereIn('id', $memberIds)
            ->where('accepts_emails', true)
            ->whereNotNull('email')
            ->where('email', '!=', '');

        // Apply segment-specific filters
        return match ($segmentType) {
            'all_members' => $query,
            'card_members' => $this->applyCardMembersFilter($query, $config),
            'points_below' => $this->applyPointsBelowFilter($query, $config),
            'points_above' => $this->applyPointsAboveFilter($query, $config),
            'stamps_in_progress' => $this->applyStampsInProgressFilter($query, $config),
            'has_voucher' => $this->applyHasVoucherFilter($query, $config),
            'inactive' => $this->applyInactiveFilter($query, $partner, $config),
            'tier' => $this->applyTierFilter($query, $config),
            'locale' => $this->applyLocaleFilter($query, $config),
            default => $query,
        };
    }

    /**
     * Get IDs of members reachable by a partner.
     *
     * A member is "reachable" if they have interacted with
     * ANY card, stamp card, or voucher owned by the partner.
     *
     * This includes:
     * - Card transactions (card_member pivot)
     * - Stamp card progress (stamp_card_member pivot)
     * - Voucher wallet (member_voucher pivot)
     *
     * @return Collection<string> Member IDs
     */
    private function getReachableMemberIds(Partner $partner): Collection
    {
        // Members who have used partner's cards
        $cardMemberIds = DB::table('card_member')
            ->join('cards', 'cards.id', '=', 'card_member.card_id')
            ->where('cards.created_by', $partner->id)
            ->pluck('card_member.member_id');

        // Members who have used partner's stamp cards
        $stampMemberIds = DB::table('stamp_card_member')
            ->join('stamp_cards', 'stamp_cards.id', '=', 'stamp_card_member.stamp_card_id')
            ->where('stamp_cards.created_by', $partner->id)
            ->pluck('stamp_card_member.member_id');

        // Members who have partner's vouchers
        $voucherMemberIds = DB::table('member_voucher')
            ->join('vouchers', 'vouchers.id', '=', 'member_voucher.voucher_id')
            ->where('vouchers.created_by', $partner->id)
            ->pluck('member_voucher.member_id');

        // Combine and deduplicate
        return $cardMemberIds
            ->merge($stampMemberIds)
            ->merge($voucherMemberIds)
            ->unique()
            ->values();
    }

    // ═══════════════════════════════════════════════════════════════════
    // SEGMENT FILTERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Filter to members who have used a specific card.
     */
    private function applyCardMembersFilter(Builder $query, array $config): Builder
    {
        if (empty($config['card_id'])) {
            // No card selected = no results (not "all results")
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('cards', function ($q) use ($config) {
            $q->where('cards.id', $config['card_id']);
        });
    }

    /**
     * Filter to members with points below threshold on a card.
     */
    private function applyPointsBelowFilter(Builder $query, array $config): Builder
    {
        if (empty($config['card_id']) || ! isset($config['threshold'])) {
            // Missing required config = no results
            return $query->whereRaw('1 = 0');
        }

        return $this->applyPointsBalanceFilter($query, $config, '<');
    }

    /**
     * Filter to members with points above threshold on a card.
     */
    private function applyPointsAboveFilter(Builder $query, array $config): Builder
    {
        if (empty($config['card_id']) || ! isset($config['threshold'])) {
            // Missing required config = no results
            return $query->whereRaw('1 = 0');
        }

        return $this->applyPointsBalanceFilter($query, $config, '>=');
    }

    /**
     * Filter members by current balance on a specific card.
     *
     * Balance source of truth is transactions, not the card_member pivot.
     */
    private function applyPointsBalanceFilter(Builder $query, array $config, string $operator): Builder
    {
        $cardId = (string) $config['card_id'];
        $threshold = (int) $config['threshold'];
        $memberTable = $query->getModel()->getTable();

        return $query
            ->whereHas('cards', function ($q) use ($cardId) {
                $q->where('cards.id', $cardId);
            })
            ->whereRaw(
                "(SELECT COALESCE(SUM(t.points - t.points_used), 0)
                    FROM transactions t
                    WHERE t.member_id = {$memberTable}.id
                      AND t.card_id = ?
                      AND t.expires_at > ?
                      AND t.deleted_at IS NULL) {$operator} ?",
                [$cardId, now(), $threshold]
            );
    }

    /**
     * Filter to members close to completing a stamp card.
     */
    private function applyStampsInProgressFilter(Builder $query, array $config): Builder
    {
        if (empty($config['stamp_card_id']) || ! isset($config['stamps_remaining'])) {
            // Missing required config = no results
            return $query->whereRaw('1 = 0');
        }

        $stampsRemaining = (int) $config['stamps_remaining'];

        // Get the stamp card to know total stamps needed
        $stampCard = StampCard::find($config['stamp_card_id']);
        if (! $stampCard) {
            return $query->whereRaw('1 = 0');
        }

        $totalStamps = $stampCard->stamps_required ?? 10;
        $minStamps = max(0, $totalStamps - $stampsRemaining);

        return $query->whereHas('stampCards', function ($q) use ($config, $minStamps, $totalStamps) {
            $q->where('stamp_cards.id', $config['stamp_card_id'])
                ->where('stamp_card_member.current_stamps', '>=', $minStamps)
                ->where('stamp_card_member.current_stamps', '<', $totalStamps);
        });
    }

    /**
     * Filter to members who have a specific unredeemed voucher.
     */
    private function applyHasVoucherFilter(Builder $query, array $config): Builder
    {
        if (empty($config['voucher_id'])) {
            // No voucher selected = no results
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('vouchers', function ($q) use ($config) {
            $q->where('vouchers.id', $config['voucher_id'])
                ->whereNull('member_voucher.redeemed_at');
        });
    }

    /**
     * Filter to members with no recent activity.
     */
    private function applyInactiveFilter(Builder $query, Partner $partner, array $config): Builder
    {
        if (! isset($config['days']) || $config['days'] === '' || $config['days'] === null) {
            // No days specified = no results
            return $query->whereRaw('1 = 0');
        }

        $days = (int) $config['days'];
        if ($days <= 0) {
            return $query->whereRaw('1 = 0');
        }

        $cutoffDate = now()->subDays($days);

        // Get IDs of members who have been active recently
        $recentlyActiveIds = DB::table('transactions')
            ->join('cards', 'cards.id', '=', 'transactions.card_id')
            ->where('cards.created_by', $partner->id)
            ->where('transactions.created_at', '>=', $cutoffDate)
            ->pluck('transactions.member_id')
            ->unique();

        return $query->whereNotIn('id', $recentlyActiveIds);
    }

    /**
     * Filter to members at a specific tier in a club.
     *
     * Uses the member_tiers pivot table which tracks tier assignments.
     */
    private function applyTierFilter(Builder $query, array $config): Builder
    {
        if (empty($config['club_id']) || empty($config['tier_id'])) {
            // Missing required config = no results
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('memberTiers', function ($q) use ($config) {
            $q->where('member_tiers.club_id', $config['club_id'])
                ->where('member_tiers.tier_id', $config['tier_id'])
                ->where('member_tiers.is_active', true);
        });
    }

    /**
     * Filter by member locale/language preference.
     */
    private function applyLocaleFilter(Builder $query, array $config): Builder
    {
        if (empty($config['locale'])) {
            // No locale selected = no results
            return $query->whereRaw('1 = 0');
        }

        return $query->where('locale', $config['locale']);
    }
}
