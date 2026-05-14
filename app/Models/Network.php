<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a Network in the application.
 * Networks are multi-tenant containers for agencies and white-label deployments.
 */

namespace App\Models;

use App\Traits\HasSchemaAccessors;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Network
 *
 * Represents a Network in the application.
 */
class Network extends Model
{
    use HasFactory, HasSchemaAccessors, HasUuids, LogsModelActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'networks';

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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'is_undeletable' => 'boolean',
        'is_uneditable' => 'boolean',
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
     * Get the admins associated with the network.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function admins()
    {
        return $this->belongsToMany(Admin::class, 'admin_network');
    }

    /**
     * Get the partners associated with the network.
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    /**
     * Get the clubs associated with the network.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clubs()
    {
        return $this->hasMany(Club::class);
    }

    /**
     * Get the affiliates associated with the network.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function affiliates()
    {
        return $this->hasMany(Affiliate::class);
    }
    /**
     * Boot the model — enforce single-primary invariant.
     *
     * When a network is marked as primary, all other networks have their
     * is_primary flag cleared. This ensures at most one primary network
     * exists at any time, making default registration routing deterministic.
     */
    protected static function booted(): void
    {
        static::saving(function (self $network) {
            if ($network->is_primary && $network->isDirty('is_primary')) {
                static::where('id', '!=', $network->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }

    /**
     * Resolve the default network for automatic assignment.
     *
     * Used during self-registration to deterministically place new partners
     * into a network. Returns the single active primary network, or null
     * when no active primary exists.
     *
     * On fresh installs the seeder creates a "Default" primary network.
     * On upgraded installs the admin must mark one network as primary
     * via the Networks CRUD before enabling self-registration.
     *
     * @return self|null
     */
    public static function getPrimaryOrFirst(): ?self
    {
        return static::where('is_active', true)
            ->where('is_primary', true)
            ->first();
    }
}
