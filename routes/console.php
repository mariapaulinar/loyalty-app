<?php

use App\Jobs\ProcessExpiredStamps;
use Illuminate\Support\Facades\Schedule;

/**
 * Define the schedule.
 *
 * Schedule the install command to run daily at 04:45 if the APP_DEMO environment variable is set to true.
 */
Schedule::command('install')->dailyAt('04:45')->when(function () {
    return env('APP_DEMO', false);
})->description('Refresh demo data daily');

/**
 * OTP Code Cleanup
 *
 * Remove expired OTP codes hourly to keep the database clean.
 */
Schedule::command('otp:cleanup --hours=24')
    ->hourly()
    ->description('Clean up expired OTP codes');

/**
 * Stamp Card Expiration
 *
 * Process expired stamps daily based on per-card expiry rules.
 */
Schedule::job(new ProcessExpiredStamps)
    ->dailyAt('03:00')
    ->description('Process expired stamp cards');

/**
 * Health Center Heartbeat
 *
 * Write a timestamp to cache every minute so the Health Center
 * can verify the scheduler is running. TTL is 10 minutes —
 * if the scheduler stops, the key expires and the check reports a warning.
 */
Schedule::call(function () {
    cache()->put('health:scheduler_last_run', now(), now()->addMinutes(10));
})
    ->everyMinute()
    ->description('Health Center scheduler heartbeat');
