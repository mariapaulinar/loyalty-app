<?php

namespace App\Traits;

/**
 * Trait Encryptable
 *
 * Provides encryption and decryption functionality for specified Eloquent model attributes.
 *
 * In model add:
 *   use App\Traits\Encryptable;
 *
 *   ---
 *   use Encryptable;
 *   ---
 *
 *   protected $encryptable = [
 *       'attribute1',
 *       'attribute2',
 *       // ...
 *   ];
 */
trait Encryptable
{
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->encryptable)) {
            $value = ($value != null && trim($value) != '') ? decrypt($value) : null;
        }

        return $value;
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptable)) {
            $value = ($value != null && trim($value) != '') ? encrypt($value) : null;
        }

        return parent::setAttribute($key, $value);
    }
}
