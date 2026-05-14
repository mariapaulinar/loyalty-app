<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Service for managing member operations including retrieval,
 * creation, and lookup by various identifiers.
 */

namespace App\Services\Member;

use App\Models\Member;

class MemberService
{
    /**
     * Find a member by ID.
     *
     * @param  int|string  $id  Member ID
     * @return Member|null Member object if found, otherwise null.
     */
    public function find(int|string $id): ?Member
    {
        return Member::find($id);
    }

    /**
     * Find a member by email address (regardless of active status).
     *
     * @param  string  $email  Email address.
     * @return Member|null Member object if found, otherwise null.
     */
    public function findByEmail(string $email): ?Member
    {
        return Member::where('email', strtolower(trim($email)))->first();
    }

    /**
     * Retrieve an active member by email address.
     *
     * @param  string  $email  Email address.
     * @param  bool  $authUserIsOwner  Indicates if the authenticated user has to be the owner.
     * @return Member|null Member object if found, otherwise null.
     */
    public function findActiveByEmail(string $email, bool $authUserIsOwner = false): ?Member
    {
        $query = Member::where('email', strtolower(trim($email)))
            ->where('is_active', true);

        if ($authUserIsOwner) {
            $query->where('created_by', auth()->user()->owner_id);
        }

        return $query->first();
    }

    /**
     * Retrieve an active member by any identifier.
     *
     * Searches by: unique_identifier, device_code (anonymous members),
     * email, or member_number. Essential for staff lookup.
     *
     * @param  string  $identifier  Any member identifier.
     * @return Member|null Member object if found, otherwise null.
     */
    public function findActiveByIdentifier(string $identifier): ?Member
    {
        $identifier = trim($identifier);

        // First try the efficient UUID lookup if it looks like a UUID
        if (preg_match('/^[a-f0-9-]{36}$/i', $identifier)) {
            $member = Member::where('id', $identifier)
                ->where('is_active', true)
                ->first();
            if ($member) {
                return $member;
            }
        }

        // Search by multiple identifier types
        return Member::query()
            ->where('is_active', true)
            ->where(function ($q) use ($identifier) {
                $q->where('unique_identifier', $identifier)
                    ->orWhere('device_code', strtoupper($identifier)) // Anonymous member code
                    ->orWhere('email', strtolower($identifier))
                    ->orWhere('member_number', $identifier);
            })
            ->first();
    }

    /**
     * Insert a new member.
     *
     * @param  array  $data  Member data.
     * @return Member The newly created member object.
     */
    public function store(array $data): Member
    {
        return Member::create($data);
    }
}
