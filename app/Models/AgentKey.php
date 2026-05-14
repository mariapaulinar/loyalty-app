<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents an Agent API key for the Agent Layer.
 * Keys are polymorphically owned by Admin, Partner, or Member users.
 * Authentication uses a prefix-based candidate lookup + bcrypt verification.
 *
 * Design Tenets:
 * - **Security First**: Keys hashed with bcrypt, raw key shown once
 * - **Polymorphic**: Single table, three owner types
 * - **Debounced**: last_used_at updated at most every 5 minutes
 * - **Scoped**: JSON scopes array with "admin" super-scope
 *
 * @property string $id
 * @property string $owner_type
 * @property string $owner_id
 * @property string $name
 * @property string $key_prefix
 * @property string $key_hash
 * @property array $scopes
 * @property int $rate_limit
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $expires_at
 * @property bool $is_active
 * @property array|null $meta
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model $owner
 *
 * @see RewardLoyalty-100-agent.md §2
 */

namespace App\Models;

use App\Traits\HasSchemaAccessors;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentKey extends Model
{
    use HasSchemaAccessors;
    use HasUuids;

    protected $table = 'agent_keys';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * Model-level defaults — ensures the in-memory model instance
     * has correct values immediately after create() without needing
     * a DB refresh. DB defaults remain as a safety net.
     */
    protected $attributes = [
        'is_active' => true,
        'rate_limit' => 60,
    ];

    /**
     * Attribute not persisted — holds raw key immediately after creation.
     * Available only during the request that created the key.
     */
    public ?string $raw_key = null;

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'rate_limit' => 'integer',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'meta' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Never expose the key hash in JSON serialization.
     */
    protected $hidden = [
        'key_hash',
    ];

    // ═════════════════════════════════════════════════════════════════════════
    // KEY GENERATION CONSTANTS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Key prefix by owner type.
     * Partner uses rl_agent_ for backward compatibility with v1 naming.
     */
    public const PREFIXES = [
        Admin::class   => 'rl_admin_',   // 9 chars
        Partner::class => 'rl_agent_',   // 9 chars
        Member::class  => 'rl_member_',  // 10 chars
    ];

    /**
     * Prefix lengths stored in DB for lookup (role prefix + 8 random chars).
     * These lengths are used by AuthenticateAgent to extract the lookup prefix.
     */
    public const PREFIX_LENGTHS = [
        Admin::class   => 17,  // 9 + 8
        Partner::class => 17,  // 9 + 8
        Member::class  => 18,  // 10 + 8
    ];

    /**
     * Length of the random portion of the raw key.
     * Total key length = role prefix + RANDOM_LENGTH.
     */
    public const RANDOM_LENGTH = 40;

    // ═════════════════════════════════════════════════════════════════════════
    // MODEL LIFECYCLE
    // ═════════════════════════════════════════════════════════════════════════

    protected static function booted(): void
    {
        static::creating(function (self $agentKey) {
            // Generate credentials if not already set (e.g., by beforeInsert callback)
            // saveQuietly() skips this event, so DataDefinitions call generateKeyCredentials() directly
            if (empty($agentKey->key_hash)) {
                $agentKey->generateKeyCredentials();
            }
        });
    }

    /**
     * Generate key_prefix, key_hash, and raw_key for this agent key.
     *
     * Must be called AFTER owner_type is set on the model.
     * Called by:
     *   - The `creating` model event (normal save)
     *   - DataDefinition `beforeInsert` callback (saveQuietly path)
     *
     * @throws \InvalidArgumentException If owner_type is missing or unknown.
     */
    public function generateKeyCredentials(): void
    {
        $rolePrefix = self::PREFIXES[$this->owner_type]
            ?? throw new \InvalidArgumentException(
                "Unknown owner type for AgentKey: {$this->owner_type}. "
                . 'Expected one of: ' . implode(', ', array_keys(self::PREFIXES))
            );

        // Generate the raw key (shown to owner ONCE)
        $rawKey = $rolePrefix . Str::random(self::RANDOM_LENGTH);

        // Store prefix for display + efficient DB lookup
        $prefixLength = self::PREFIX_LENGTHS[$this->owner_type];
        $this->key_prefix = substr($rawKey, 0, $prefixLength);

        // Hash the full key for storage (like password hashing)
        $this->key_hash = Hash::make($rawKey);

        // Attach the raw key to the model instance (not persisted)
        // This is the ONLY moment the raw key exists — show it to the user
        $this->raw_key = $rawKey;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Polymorphic owner (Admin, Partner, or Member).
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    // ═════════════════════════════════════════════════════════════════════════
    // AUTH HELPERS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Convenience: get the partner for partner-scoped operations.
     * Returns null if the key is not owned by a Partner.
     */
    public function getPartner(): ?Partner
    {
        return match ($this->owner_type) {
            Partner::class => $this->owner,
            default => null,
        };
    }

    /**
     * Convenience: get the member for member-scoped operations.
     * Returns null if the key is not owned by a Member.
     */
    public function getMember(): ?Member
    {
        return match ($this->owner_type) {
            Member::class => $this->owner,
            default => null,
        };
    }

    /**
     * Detect the role prefix length from a raw key string.
     * Used by AuthenticateAgent middleware for indexed lookup.
     */
    public static function detectPrefixLength(string $rawKey): ?int
    {
        foreach (self::PREFIXES as $class => $prefix) {
            if (str_starts_with($rawKey, $prefix)) {
                return self::PREFIX_LENGTHS[$class];
            }
        }

        return null;
    }

    /**
     * Detect the owner type class from a raw key prefix.
     */
    public static function detectOwnerType(string $rawKey): ?string
    {
        foreach (self::PREFIXES as $class => $prefix) {
            if (str_starts_with($rawKey, $prefix)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Debounced touch — only updates DB if >5 minutes since last use.
     * Prevents DB writes on every request from high-frequency agents.
     *
     * Uses quietly() to avoid triggering model events on every touch.
     */
    public function touchLastUsed(): void
    {
        if (! $this->last_used_at || $this->last_used_at->diffInMinutes(now()) >= 5) {
            static::withoutEvents(fn () => $this->update(['last_used_at' => now()]));
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    // SCOPE & VALIDATION HELPERS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Check if this key has any of the required scopes.
     * The 'admin' scope grants access to everything within the role.
     *
     * @param  array<string>  $requiredScopes  Scopes to check — pass multiple for OR logic
     */
    public function hasAnyScope(array $requiredScopes): bool
    {
        $keyScopes = $this->scopes ?? [];

        // Super-scope: 'admin' grants all permissions within the role
        if (in_array('admin', $keyScopes, true)) {
            return true;
        }

        // Standard check: key must have at least one of the required scopes.
        // "Write implies read on that resource" is handled at the controller
        // level — e.g. cards index() checks ['read', 'write:cards'] — so
        // a write:cards key can read cards via the normal intersection.
        return ! empty(array_intersect($requiredScopes, $keyScopes));
    }

    /**
     * Check if this key is valid (active, not expired).
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the key's owner is still active.
     * A deactivated partner/admin/member invalidates all their agent keys.
     */
    public function ownerIsActive(): bool
    {
        $owner = $this->owner;
        if (! $owner) {
            return false;
        }

        // All owner types have an is_active attribute
        return (bool) ($owner->is_active ?? true);
    }

    /**
     * Get a human-readable role name for this key's owner type.
     */
    public function getRoleName(): string
    {
        return match ($this->owner_type) {
            Admin::class   => 'admin',
            Partner::class => 'partner',
            Member::class  => 'member',
            default        => 'unknown',
        };
    }
}
