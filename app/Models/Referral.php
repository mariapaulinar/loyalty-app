<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Referral Tracking Record
 *
 * This model tracks the lifecycle of a single referral from signup to completion.
 *
 * State Machine:
 * --------------
 * pending → completed (when referee makes first purchase)
 * pending → expired (if campaign ends or other business logic)
 *
 * The referral is "completed" when the referee makes their FIRST points-earning
 * transaction on ANY card. This proves they're an active, engaged member.
 *
 * Audit Trail:
 * ------------
 * We store references to the actual transactions that awarded points, creating
 * a complete audit trail for compliance and debugging.
 */
class Referral extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'signed_up_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * The referral code that was used.
     */
    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(MemberReferralCode::class, 'referral_code_id');
    }

    /**
     * The member who referred (the sharer).
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'referrer_id');
    }

    /**
     * The member who was referred (the new signup).
     */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'referee_id');
    }

    /**
     * The transaction that awarded points to the referrer.
     */
    public function referrerTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'referrer_transaction_id');
    }

    /**
     * The transaction that awarded points to the referee.
     */
    public function refereeTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'referee_transaction_id');
    }

    /**
     * Check if this referral is still pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if this referral has been completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
