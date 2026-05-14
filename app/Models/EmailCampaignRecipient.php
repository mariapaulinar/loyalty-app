<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * EmailCampaignRecipient Model
 *
 * Purpose:
 * Tracks individual email delivery for each member in a campaign.
 * Enables sequential sending with resume capability.
 *
 * Architecture:
 * - Email is snapshotted at campaign creation (survives member email changes)
 * - Member can be null if deleted during campaign (preserves delivery record)
 * - Status enables picking up where we left off if browser closes
 *
 * Status lifecycle:
 * pending → sent (on successful delivery)
 *       ↘ failed (on delivery error, error_message populated)
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaignRecipient extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'email_campaign_recipients';

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Get the campaign this recipient belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    /**
     * Get the member this recipient represents.
     *
     * May return null if member was deleted after campaign creation.
     * This is intentional — we preserve delivery records for analytics.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    // ═══════════════════════════════════════════════════════════════════
    // STATUS HELPERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Check if this recipient is pending delivery.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if email was sent successfully.
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if delivery failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if this recipient can receive the email.
     *
     * Validates that:
     * - Member still exists
     * - Member still accepts emails
     * - Email address is present
     */
    public function canReceive(): bool
    {
        // Member was deleted
        if (! $this->member) {
            return false;
        }

        // Member unsubscribed
        if (! $this->member->accepts_emails) {
            return false;
        }

        // No email address
        if (empty($this->email)) {
            return false;
        }

        return true;
    }

    // ═══════════════════════════════════════════════════════════════════
    // MUTATION HELPERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Mark this recipient as successfully sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Mark this recipient as failed with error message.
     *
     * @param  string  $errorMessage  Description of the failure
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Scope to get pending recipients.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get sent recipients.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get failed recipients.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
