<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Models;

use App\Traits\HasSchemaAccessors;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Model representing a 4-digit code that grants points to a member.
 *
 * @property int $id
 * @property int $staff_id
 * @property string $code
 * @property int $points
 * @property Carbon $expires_at
 * @property int|null $used_by
 * @property Carbon|null $used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property \App\Models\Staff $staff
 * @property \App\Models\Member $usedMember
 * @property \App\Models\Card $card
 */
class PointCode extends Model
{
    use HasSchemaAccessors, HasUuids;

    /**
     * @var string
     */
    protected $table = 'point_codes';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @var array
     */
    protected $casts = [
        'points' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Allow mass assignment of a model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Relationship: which staff member created this code.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Relationship: which card this code will add points to.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Relationship: which member redeemed this code (if any).
     */
    public function usedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'used_by');
    }

    /**
     * Check if the code has expired based on 'expires_at'.
     */
    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    /**
     * Check if the code has already been used.
     */
    public function isUsed(): bool
    {
        return ! is_null($this->used_at);
    }
}
