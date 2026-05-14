<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Handles OTP authentication for the Admin guard.
 * Provides passwordless login via 6-digit email codes.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesOtpAuthentication;

class OtpController extends Controller
{
    use HandlesOtpAuthentication;

    /**
     * Get the guard name for this controller.
     */
    protected function getGuard(): string
    {
        return 'admin';
    }

    /**
     * Get the route prefix for this guard.
     */
    protected function getRoutePrefix(): string
    {
        return 'admin';
    }

    /**
     * Get the view prefix for this guard.
     */
    protected function getViewPrefix(): string
    {
        return 'admin';
    }

    /**
     * Get the dashboard route name for successful login.
     */
    protected function getDashboardRoute(): string
    {
        return 'admin.index';
    }
}
