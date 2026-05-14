<?php

namespace App\Traits;

use App\Models\Club;

/**
 * Trait HasClub
 *
 * Provides club association functionality for models.
 *
 * https://stackoverflow.com/questions/60029955/when-to-use-repository-vs-service-vs-trait-in-laravel
 * https://dev.to/dalelantowork/laravel-8-traits-4ai
 */
trait HasClub
{
    /**
     * Associate a club with the model.
     *
     * @return $this
     */
    public function setClub(Club $club)
    {
        $this->club()->associate($club);

        return $this;
    }

    /**
     * Get the associated club.
     */
    public function getClub(): ?Club
    {
        return $this->getAttribute('club');
    }

    /**
     * Define a club relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function club()
    {
        return $this->belongsTo(Club::class, 'club_id', 'id');
    }
}
