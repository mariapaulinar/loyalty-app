<?php

namespace App\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;

/**
 * Custom query builder for the Partner model.
 */
class PartnerQueryBuilder extends Builder
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
}
