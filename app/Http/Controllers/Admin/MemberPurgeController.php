<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Handles purging of inactive anonymous ("ghost") members.
 * These are members auto-created by anonymous mode who never
 * interacted with the platform (no points, stamps, cards, or vouchers).
 *
 * Design Tenets:
 * - **Safe**: Only deletes members with zero interactions
 * - **Transparent**: Shows exactly what will be deleted before confirmation
 * - **Audited**: Logs every purge action for accountability
 * - **Performant**: Uses chunked deletion with media cleanup
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberPurgeController extends Controller
{
    /**
     * Available retention periods in months.
     */
    protected const RETENTION_OPTIONS = [1, 3, 6, 12, 24];

    /**
     * Default retention period.
     */
    protected const DEFAULT_RETENTION_MONTHS = 6;

    /**
     * Show the purge configuration and preview page.
     */
    public function show(Request $request)
    {
        $retentionMonths = (int) $request->get('months', self::DEFAULT_RETENTION_MONTHS);

        // Validate retention period
        if (! in_array($retentionMonths, self::RETENTION_OPTIONS)) {
            $retentionMonths = self::DEFAULT_RETENTION_MONTHS;
        }

        $cutoffDate = now()->subMonths($retentionMonths);

        // Count purgeable members (ghost + anonymous + older than retention)
        $purgeableCount = Member::purgeable($retentionMonths)->count();

        // Get breakdown stats for transparency
        $totalMembers = Member::count();
        $totalAnonymous = Member::anonymous()->count();
        $totalGhosts = Member::ghost()->count();
        $anonymousGhosts = Member::ghost()->anonymous()->count();

        // Per-period preview counts (so admin can compare options)
        $periodCounts = [];
        foreach (self::RETENTION_OPTIONS as $months) {
            $periodCounts[$months] = Member::purgeable($months)->count();
        }

        return view('admin.members.purge', [
            'retentionMonths' => $retentionMonths,
            'retentionOptions' => self::RETENTION_OPTIONS,
            'cutoffDate' => $cutoffDate,
            'purgeableCount' => $purgeableCount,
            'totalMembers' => $totalMembers,
            'totalAnonymous' => $totalAnonymous,
            'totalGhosts' => $totalGhosts,
            'anonymousGhosts' => $anonymousGhosts,
            'periodCounts' => $periodCounts,
        ]);
    }

    /**
     * Execute the purge of ghost members.
     */
    public function purge(Request $request): RedirectResponse
    {
        $request->validate([
            'retention_months' => ['required', 'integer', 'in:' . implode(',', self::RETENTION_OPTIONS)],
        ]);

        $retentionMonths = (int) $request->input('retention_months');
        $cutoffDate = now()->subMonths($retentionMonths);

        // Count before deletion
        $purgeableCount = Member::purgeable($retentionMonths)->count();

        if ($purgeableCount === 0) {
            return redirect()
                ->route('admin.data.list', ['name' => 'members'])
                ->with('toast', [
                    'type' => 'info',
                    'text' => trans('common.purge_members_none'),
                ]);
        }

        // Delete in chunks to avoid memory issues
        // We use proper model deletion (not mass delete) so media gets cleaned up
        $chunkSize = 200;
        $totalDeleted = 0;

        do {
            $members = Member::purgeable($retentionMonths)
                ->limit($chunkSize)
                ->get();

            $deleted = $members->count();

            foreach ($members as $member) {
                // Clear any media (avatars, etc.)
                $member->clearMediaCollection('avatar');
                $member->delete();
            }

            $totalDeleted += $deleted;
        } while ($deleted > 0);

        // Log the cleanup action
        activity('admin')
            ->event('cleanup')
            ->causedBy(auth('admin')->user())
            ->withProperties([
                'action' => 'member_purge',
                'deleted_count' => $totalDeleted,
                'retention_months' => $retentionMonths,
                'cutoff_date' => $cutoffDate->toDateString(),
            ])
            ->log("Ghost members purged: deleted {$totalDeleted} inactive anonymous members older than {$retentionMonths} months");

        return redirect()
            ->route('admin.data.list', ['name' => 'members'])
            ->with('toast', [
                'type' => 'success',
                'text' => trans('common.purge_members_success', ['count' => number_format($totalDeleted)]),
            ]);
    }
}
