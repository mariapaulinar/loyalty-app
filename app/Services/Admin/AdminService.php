<?php

namespace App\Services\Admin;

use App\Models\Admin;

class AdminService
{
    /**
     * Get an active admin by email address.
     *
     * @param  string  $email  Email address.
     * @param  bool  $authUserIsOwner  Indicates whether the authenticated user has to be the owner.
     * @return Admin|null Admin object if found, otherwise null.
     */
    public function findActiveByEmail(string $email, bool $authUserIsOwner = false): ?Admin
    {
        $query = Admin::query()
            ->where('email', $email);

        if (! session()->get('impersonate.admin')) {
            $query->where('is_active', true);
        }

        if ($authUserIsOwner) {
            $query->where('created_by', auth()->user()->owner_id);
        }

        return $query->first();
    }

    /**
     * Insert a new admin.
     *
     * @param  array  $data  Admin data.
     * @return Admin The created admin object.
     */
    public function store(array $data): Admin
    {
        return Admin::create($data);
    }
}
