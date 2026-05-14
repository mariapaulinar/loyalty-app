<?php

namespace App\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;

/**
 * Custom query builder for the Member model.
 */
class MemberQueryBuilder extends Builder
{
    /**
     * Add a basic where clause to the query for the "is_active" column.
     *
     * @param  bool  $isActive
     * @return $this
     */
    public function whereActive($isActive = true)
    {
        if ($isActive) {
            return $this->whereNotNull('is_active');
        } else {
            return $this->whereNull('is_active');
        }
    }

    /**
     * Add a basic where clause to the query for the "email_verified_at" column.
     *
     * @param  bool  $isEmailVerified
     * @return $this
     */
    public function whereEmailVerified($isEmailVerified = true)
    {
        if ($isEmailVerified) {
            return $this->whereNotNull('email_verified_at');
        } else {
            return $this->whereNull('email_verified_at');
        }
    }
}
