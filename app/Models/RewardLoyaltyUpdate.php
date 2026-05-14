<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RewardLoyaltyUpdate Model
 *
 * Tracks all update attempts for Reward Loyalty, providing a complete audit
 * trail of version upgrades including success/failure status, timing, and
 * rollback information.
 */
class RewardLoyaltyUpdate extends Model
{
    protected $table = 'reward_loyalty_updates';

    protected $fillable = [
        'from_version',
        'to_version',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'package_url',
        'package_hash',
        'package_size',
        'backup_path',
        'backup_kept',
        'error_message',
        'error_trace',
        'initiated_by',
        'ip_address',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_seconds' => 'integer',
            'package_size' => 'integer',
            'backup_kept' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the admin who initiated this update
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'initiated_by');
    }

    /**
     * Check if update was successful
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if update failed
     */
    public function hasFailed(): bool
    {
        return in_array($this->status, ['failed', 'rolled_back']);
    }

    /**
     * Check if update is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, ['pending', 'downloading', 'extracting', 'migrating']);
    }

    /**
     * Format the recorded duration into a human-readable interval.
     */
    public function humanDuration(): ?string
    {
        if (empty($this->duration_seconds)) {
            return null;
        }

        return CarbonInterval::seconds($this->duration_seconds)
            ->cascade()
            ->forHumans(['parts' => 2]);
    }
}
