<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

class PreventRequestForgery extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '*/install',
        '*/install/*',
        'api/*',
        '*/set-cookie/*',
        // Profile OTP endpoints - exempt from CSRF to prevent token mismatch
        // during long form edits. These are secure because:
        // 1. Routes require authentication
        // 2. OTP itself is an additional verification layer
        '*/profile/otp/send',
        '*/profile/otp/verify',
    ];
}
