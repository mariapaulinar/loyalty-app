<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Handles activity log management actions for admins, including
 * purging old records to maintain database performance and comply
 * with data retention policies.
 *
 * Design Tenets:
 * - **GDPR Compliant**: Respects minimum retention period
 * - **Safe**: Requires confirmation and logs the action
 * - **Performant**: Uses chunked deletion for large datasets
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Minimum retention period in days (GDPR compliance).
     * Activity logs must be kept for at least this many days.
     */
    protected const MIN_RETENTION_DAYS = 90;

    /**
     * Default retention period from config or fallback.
     */
    protected function getRetentionDays(): int
    {
        return (int) config('activitylog.delete_records_older_than_days', 365);
    }

    /**
     * Show purge confirmation page.
     */
    public function showPurge(Request $request)
    {
        $retentionDays = max(self::MIN_RETENTION_DAYS, $this->getRetentionDays());
        $cutoffDate = now()->subDays($retentionDays);
        $recordCount = Activity::where('created_at', '<', $cutoffDate)->count();

        // Get breakdown by category
        $breakdown = Activity::where('created_at', '<', $cutoffDate)
            ->selectRaw('log_name, COUNT(*) as count')
            ->groupBy('log_name')
            ->pluck('count', 'log_name')
            ->toArray();

        return view('admin.activity-logs.purge', [
            'retentionDays' => $retentionDays,
            'cutoffDate' => $cutoffDate,
            'recordCount' => $recordCount,
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * Execute the purge of old activity logs.
     */
    public function purge(Request $request): RedirectResponse
    {
        $retentionDays = max(self::MIN_RETENTION_DAYS, $this->getRetentionDays());
        $cutoffDate = now()->subDays($retentionDays);

        // Count before deletion
        $recordCount = Activity::where('created_at', '<', $cutoffDate)->count();

        if ($recordCount === 0) {
            return redirect()
                ->route('admin.data.list', ['name' => 'activity-logs'])
                ->with('toast', json_encode([
                    'type' => 'info',
                    'message' => trans('common.no_records_to_purge'),
                ]));
        }

        // Delete in chunks to avoid memory issues
        $chunkSize = 1000;
        $totalDeleted = 0;

        do {
            $deleted = Activity::where('created_at', '<', $cutoffDate)
                ->limit($chunkSize)
                ->delete();

            $totalDeleted += $deleted;
        } while ($deleted > 0);

        // Log the cleanup action itself
        activity('admin')
            ->event('cleanup')
            ->causedBy(auth('admin')->user())
            ->withProperties([
                'deleted_count' => $totalDeleted,
                'retention_days' => $retentionDays,
                'cutoff_date' => $cutoffDate->toDateString(),
            ])
            ->log("Activity logs purged: deleted {$totalDeleted} records older than {$retentionDays} days");

        return redirect()
            ->route('admin.data.list', ['name' => 'activity-logs'])
            ->with('toast', json_encode([
                'type' => 'success',
                'message' => trans('common.records_purged', ['count' => number_format($totalDeleted)]),
            ]));
    }
}
