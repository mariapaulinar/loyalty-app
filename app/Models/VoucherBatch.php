<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a batch of vouchers generated together with shared configuration.
 * Provides batch-level operations and analytics for bulk voucher management.
 *
 * Design Tenets:
 * - **Batch Identity**: Unique identifier for grouping related vouchers
 * - **Configuration Storage**: Preserves original settings used for generation
 * - **Analytics Ready**: Tracks batch-level statistics and performance
 * - **Relationship Rich**: Links to club, partner, and vouchers
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class VoucherBatch extends Model
{
    use HasTranslations;

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'club_id',
        'partner_id',
        'name',
        'description',
        'quantity',
        'code_prefix',
        'config',
        'status',
        'vouchers_created',
        'claim_token',
        'meta',
    ];

    /**
     * Translatable fields.
     *
     * Only description is translatable for batches.
     * Batch name is an internal identifier (not translatable).
     * For display purposes, we use the template voucher's translatable title.
     *
     * @var array<string>
     */
    public array $translatable = [
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'meta' => 'array',
            'quantity' => 'integer',
            'vouchers_created' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get the club that owns this batch.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the partner who created this batch.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get all vouchers in this batch.
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'batch_id', 'id');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Check if batch is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if batch is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get formatted batch name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: "Batch {$this->id}";
    }

    // ═════════════════════════════════════════════════════════════════════════
    // QR CODE & CLAIM METHODS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Generate a unique claim token for this batch.
     * Used for QR code claims - prevents unauthorized access.
     */
    public function generateClaimToken(): string
    {
        $token = hash('sha256', $this->id.now()->timestamp.rand());

        $this->update(['claim_token' => $token]);

        return $token;
    }

    /**
     * Get the claim URL for this batch.
     * Members scan QR code pointing to this URL to claim a voucher.
     */
    public function getClaimUrlAttribute(): string
    {
        $token = $this->claim_token ?? $this->generateClaimToken();

        return route('member.vouchers.claim', [
            'batchId' => $this->id,
            'token' => $token,
        ]);
    }

    /**
     * Get QR code data URL (base64 encoded PNG).
     * Can be embedded directly in HTML or downloaded.
     */
    public function getQrCodeAttribute(): string
    {
        // Using a simple QR code generator
        // In production, use a proper package like SimpleSoftwareIO/simple-qrcode
        $url = $this->claim_url;

        // For now, return a placeholder data URL
        // TODO: Install QR code package and generate real QR code
        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext x='50' y='50' text-anchor='middle' font-size='8'%3EQR Code%3C/text%3E%3C/svg%3E";
    }

    /**
     * Get count of unclaimed vouchers in this batch.
     */
    public function getUnclaimedCountAttribute(): int
    {
        return $this->vouchers()
            ->whereNull('claimed_by_member_id')
            ->count();
    }

    /**
     * Get count of claimed vouchers in this batch.
     */
    public function getClaimedCountAttribute(): int
    {
        return $this->vouchers()
            ->whereNotNull('claimed_by_member_id')
            ->count();
    }

    /**
     * Check if batch has available vouchers to claim.
     */
    public function hasAvailableVouchers(): bool
    {
        return $this->unclaimed_count > 0 && $this->is_active;
    }
}
