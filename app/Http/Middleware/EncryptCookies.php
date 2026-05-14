<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * These cookies are set by JavaScript and must be readable by PHP
     * without going through Laravel's encryption/decryption.
     *
     * @var array<int, string>
     */
    protected $except = [
        'member_device_uuid', // Set by JS for anonymous member identity
        'member_time_zone',   // Set by JS for browser timezone capture
    ];
}
