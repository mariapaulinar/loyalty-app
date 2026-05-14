<?php

namespace App\Models;

use App\Traits\HasIdentifier;
use App\Traits\HasSchemaAccessors;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PointRequest
 *
 * Represents a member-generated request link for receiving points.
 *
 * The link can be "wildcard" (if card_id is null) or specific to a particular card.
 * The unique_identifier is auto-generated in the format xxx-xxx-xxx-xxx by the HasIdentifier trait.
 *
 * @property int $id
 * @property int $member_id The member who created the request.
 * @property int|null $card_id The ID of the card (if specific), or null for a wildcard request.
 * @property string|null $unique_identifier Unique identifier for the request link.
 * @property bool $is_active Indicates if the request link is active.
 * @property int|null $max_uses Global maximum number of redemptions allowed.
 * @property int $usage_count Total times the link has been redeemed.
 * @property int $per_member_limit Maximum redemptions allowed per member.
 * @property \Illuminate\Support\Carbon|null $expires_at Expiration time of the link.
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \App\Models\Member $member The member who created the request.
 * @property \App\Models\Card|null $card The card associated with this request (if any).
 */
class PointRequest extends Model
{
    use HasIdentifier, HasSchemaAccessors, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'points_requests';

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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Cast attributes to native types.
     *
     * @var array
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the member who created this request.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by');
    }

    /**
     * Get the card associated with this request (if any).
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Determine if the request link has expired.
     *
     * @return bool True if the current time is past the expiration time, false otherwise.
     */
    public function isExpired(): bool
    {
        return $this->expires_at ? Carbon::now()->greaterThan($this->expires_at) : false;
    }
}
