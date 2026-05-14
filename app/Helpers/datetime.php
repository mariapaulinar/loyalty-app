<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Date/time helper functions.
 */

if (! function_exists('getGreeting')) {
    /**
     * Get a time-based greeting using the user's timezone.
     *
     * Returns a translated greeting string (Good morning / afternoon / evening / night)
     * based on the current hour in the given timezone.
     *
     * @param  string|null  $timezone  IANA timezone (e.g. 'America/New_York'). Falls back to app timezone.
     */
    function getGreeting(?string $timezone = null): string
    {
        $hour = (int) \Carbon\Carbon::now($timezone ?: config('app.timezone'))->format('H');

        if ($hour >= 5 && $hour < 12) {
            return trans('common.good_morning');
        } elseif ($hour >= 12 && $hour < 17) {
            return trans('common.good_afternoon');
        } elseif ($hour >= 17 && $hour < 21) {
            return trans('common.good_evening');
        } else {
            return trans('common.good_night');
        }
    }
}
