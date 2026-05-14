<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HandlesMariaDBBigIntegers
 *
 * Provides methods to handle BIGINT values in MariaDB when using Snowflake IDs.
 */
trait HandlesMariaDBBigIntegers
{
    /**
     * Insert the given attributes and set the ID on the model.
     * Overrides the default behavior to cast Snowflake ID to BIGINT for MariaDB compatibility.
     *
     * @param  mixed  $attributes
     */
    protected function insertAndSetId(Builder $query, $attributes): void
    {
        // If the 'id' attribute is set, cast it to BIGINT for MariaDB compatibility.
        if (isset($attributes['id'])) {
            $attributes['id'] = \DB::raw('CAST('.$attributes['id'].' AS UNSIGNED)');
        }

        // Insert the record and set the ID on the model.
        $id = $query->insertGetId($attributes, $keyName = $this->getKeyName());
        $this->setAttribute($keyName, $id);
    }

    /**
     * Perform an update on the model.
     * Overrides the default behavior to cast Snowflake ID to BIGINT for MariaDB compatibility during updates.
     */
    protected function performUpdate(Builder $query): bool
    {
        // If the 'id' attribute is set in the model's attributes...
        if (array_key_exists('id', $this->attributes)) {
            // Retrieve the current bindings of the query.
            $bindings = $query->getBindings();

            // Iterate through the bindings to find the 'id' and cast it to BIGINT for MariaDB compatibility.
            foreach ($bindings as &$binding) {
                if ($binding == $this->attributes['id']) {
                    $binding = \DB::raw('CAST('.$this->attributes['id'].' AS UNSIGNED)');
                    break;
                }
            }

            // Update the query's bindings.
            $query->setBindings($bindings);
        }

        // Proceed with the default update behavior.
        return parent::performUpdate($query);
    }
}
