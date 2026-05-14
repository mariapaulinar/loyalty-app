<?php

namespace App\Traits;

trait EmptyStringToNull
{
    /**
     * Set the attribute value, converting empty strings to null.
     *
     * @param  string  $key  The attribute key.
     * @param  mixed  $value  The attribute value.
     * @return mixed The processed attribute value.
     */
    public function setAttribute($key, $value)
    {
        $value = $this->emptyStringToNull($value);

        return parent::setAttribute($key, $value);
    }

    /**
     * Convert an empty string to null.
     *
     * @param  mixed  $value  The value to be processed.
     * @return mixed|null The processed value, or null if the value is an empty string.
     */
    private function emptyStringToNull($value)
    {
        if (is_scalar($value) && ! is_bool($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }
        }

        return $value;
    }
}
