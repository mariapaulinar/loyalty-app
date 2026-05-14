<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Partner Service
 *
 * Handles partner CRUD operations and queries.
 */

namespace App\Services\Partner;

use App\Models\Partner;

class PartnerService
{
    /**
     * Find a partner by ID.
     *
     * @param  string  $id  Partner UUID
     * @return Partner|null Partner object if found, otherwise null.
     */
    public function find(string $id): ?Partner
    {
        return Partner::find($id);
    }

    /**
     * Find a partner by email address (any status).
     *
     * @param  string  $email  Email address.
     * @return Partner|null Partner object if found, otherwise null.
     */
    public function findByEmail(string $email): ?Partner
    {
        return Partner::where('email', strtolower(trim($email)))->first();
    }

    /**
     * Get an active partner by email address.
     *
     * @param  string  $email  Email address.
     * @param  bool  $authUserIsOwner  Indicates whether the authenticated user has to be the owner.
     * @return Partner|null Partner object if found, otherwise null.
     */
    public function findActiveByEmail(string $email, bool $authUserIsOwner = false): ?Partner
    {
        $query = Partner::query()
            ->whereActive(true)
            ->where('email', $email);

        if ($authUserIsOwner) {
            $query->where('created_by', auth()->user()->owner_id);
        }

        return $query->first();
    }

    /**
     * Insert a new partner.
     *
     * @param  array  $data  Partner data.
     * @return Partner The created partner object.
     */
    public function store(array $data): Partner
    {
        return Partner::create($data);
    }

    /**
     * Update a partner's details.
     */
    public function update(Partner $partner, array $data): Partner
    {
        $partner->update($data);

        return $partner->fresh();
    }
}
