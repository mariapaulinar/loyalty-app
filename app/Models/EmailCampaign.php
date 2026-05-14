<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * EmailCampaign Model
 *
 * Purpose:
 * Represents an email campaign sent by a partner to opted-in members.
 * Tracks sending progress and delivery statistics.
 *
 * Architecture:
 * - Subject and body are translatable (JSON columns with locale keys)
 * - Members receive emails in their preferredLocale() with fallback
 * - Sequential sending enables resume capability if browser closes
 *
 * Status lifecycle:
 * draft → pending → sending → sent
 *                         ↘ failed (on critical error)
 *
 * Future: scheduled → pending (via scheduler)
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\Translatable\HasTranslations;

class EmailCampaign extends Model
{
    use HasFactory;
    use HasTranslations;
    use HasUuids;


    /**
     * The table associated with the model.
     */
    protected $table = 'email_campaigns';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Translatable attributes.
     *
     * Subject and body are stored as JSON with locale keys:
     * {"en_US": "Hello!", "pt_BR": "Olá!", "ar_SA": "مرحبا!"}
     */
    public array $translatable = ['subject', 'body'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject' => 'array',
            'body' => 'array',
            'segment_config' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',

            'recipient_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Get the partner that owns this campaign.
     *
     * Every campaign belongs to exactly one partner.
     * Partner isolation is enforced at all query levels.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get all recipients for this campaign.
     *
     * Recipients are created when the campaign is submitted,
     * representing each member who will receive the email.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class, 'campaign_id');
    }

    // ═══════════════════════════════════════════════════════════════════
    // STATUS HELPERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Check if the campaign is a draft (saved for later).
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the campaign is pending (ready to send).
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the campaign is scheduled for future sending.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if the campaign is currently sending.
     */
    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    /**
     * Check if the campaign has completed successfully.
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if the campaign failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the campaign can be sent (not draft/scheduled).
     */
    public function canSend(): bool
    {
        return in_array($this->status, ['pending', 'sending']);
    }

    /**
     * Check if the campaign needs to continue sending.
     *
     * True if status is pending or sending and there are unsent recipients.
     * Used to determine if the sending modal should auto-open.
     */
    public function needsSending(): bool
    {
        if (! $this->canSend()) {
            return false;
        }

        return $this->recipients()
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Check if sending is complete (all recipients processed).
     */
    public function isComplete(): bool
    {
        return ! $this->recipients()
            ->where('status', 'pending')
            ->exists();
    }

    // ═══════════════════════════════════════════════════════════════════
    // PROGRESS TRACKING
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Get the sending progress percentage (0-100).
     *
     * @return float Progress percentage rounded to 1 decimal
     */
    public function getProgressPercentage(): float
    {
        if ($this->recipient_count === 0) {
            return 0.0;
        }

        $processed = $this->sent_count + $this->failed_count;

        return round(($processed / $this->recipient_count) * 100, 1);
    }

    /**
     * Get the number of processed recipients (sent + failed).
     */
    public function getProcessedCount(): int
    {
        return $this->sent_count + $this->failed_count;
    }

    /**
     * Get the number of remaining recipients to process.
     */
    public function getRemainingCount(): int
    {
        return max(0, $this->recipient_count - $this->getProcessedCount());
    }

    // ═══════════════════════════════════════════════════════════════════
    // CONTENT HELPERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Get the subject for a specific locale with fallback.
     *
     * @param  string  $locale  The desired locale
     * @return string The subject text
     */
    public function getSubjectForLocale(string $locale): string
    {
        $subject = $this->getTranslation('subject', $locale, false);

        if (! $subject) {
            $subject = $this->getTranslation('subject', config('app.locale'), false);
        }

        return $subject ?? '';
    }

    /**
     * Get the body for a specific locale with fallback.
     *
     * @param  string  $locale  The desired locale
     * @return string The body HTML
     */
    public function getBodyForLocale(string $locale): string
    {
        $body = $this->getTranslation('body', $locale, false);

        if (! $body) {
            $body = $this->getTranslation('body', config('app.locale'), false);
        }

        return $body ?? '';
    }

    /**
     * Get the segment type label for display.
     */
    public function getSegmentLabel(): string
    {
        return trans('common.email_campaign.segment.'.$this->segment_type) ?? $this->segment_type;
    }

    // ═══════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Scope to filter campaigns by partner.
     *
     * CRITICAL: Always use this scope for partner-facing queries.
     * Never expose campaigns across partner boundaries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  Partner|string  $partner  Partner model or ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPartner($query, $partner)
    {
        $partnerId = $partner instanceof Partner ? $partner->id : $partner;

        return $query->where('partner_id', $partnerId);
    }

    /**
     * Scope to filter by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
