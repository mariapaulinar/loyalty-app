<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasCustomUuidPrimary
{
    /**
     * Boot method for the trait.
     *
     * This method is automatically invoked by Laravel during the booting process of a model.
     * It sets up a model event listener to generate a numeric UUID for the model's primary key
     * whenever a new model instance is being created.
     */
    public static function bootHasCustomUuidPrimary()
    {
        static::creating(function ($model) {
            // Assign a generated numeric UUID to the model's primary key
            $model->{$model->getKeyName()} = self::generateNumericUuid();
        });
    }

    /**
     * Generate a numeric UUID.
     *
     * This method creates a UUID string using Laravel's Str::uuid() method,
     * converts it to a hash, and then truncates it to fit within a 64-bit integer range.
     * This numeric UUID is used as a primary key value for models using this trait.
     *
     * @return int The generated numeric UUID as an integer.
     */
    protected static function generateNumericUuid(): int
    {
        // Generate a standard UUID string
        $uuid = (string) Str::uuid();

        // Convert the UUID to an MD5 hash to ensure a consistent length
        // and then change the base from hexadecimal (16) to decimal (10).
        // This process does not guarantee the uniqueness of the resulting number.
        $hash = base_convert(md5($uuid), 16, 10);

        // Truncate the hash to the first 18 digits to ensure it fits
        // within the range of a 64-bit integer. This length is chosen to
        // balance between the maximum possible size and the risk of integer overflow.
        $numericUuid = substr($hash, 0, 18);

        return (int) $numericUuid;
    }
}
