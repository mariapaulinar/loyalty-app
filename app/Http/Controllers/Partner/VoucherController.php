<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Models\VoucherRedemption;
use App\Services\VoucherBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VoucherController extends Controller
{
    /**
     * Show voucher redemption transactions
     */
    public function transactions(): View
    {
        $partner = auth('partner')->user();

        // Get all redemptions for partner's vouchers (via clubs they created)
        $redemptions = VoucherRedemption::whereHas('voucher.club', function ($query) use ($partner) {
            $query->where('created_by', $partner->id);
        })
            ->with(['voucher', 'member', 'staff', 'club'])
            ->orderBy('redeemed_at', 'desc')
            ->paginate(50);

        return view('partner.vouchers.history', compact('redemptions'));
    }

    /**
     * Show voucher transactions page for a member and voucher.
     *
     * GET /{locale}/partner/voucher-transactions/{member_identifier}/{voucher_id}
     */
    public function showVoucherTransactions(string $locale, string $member_identifier, string $voucher_id): View
    {
        $partner = auth('partner')->user();

        // Find member
        $member = \App\Models\Member::where('unique_identifier', $member_identifier)
            ->orWhere('id', $member_identifier)
            ->firstOrFail();

        // Find voucher
        $voucher = Voucher::findOrFail($voucher_id);

        // Check if voucher belongs to partner's clubs
        if ($voucher->club && ! in_array($voucher->club_id, $partner->clubs->pluck('id')->toArray())) {
            abort(403, 'This voucher does not belong to your clubs');
        }

        return view('partner.vouchers.history', compact('member', 'voucher'));
    }

    /**
     * Delete the last voucher redemption.
     *
     * GET /{locale}/partner/delete-last-voucher-redemption/{member_identifier}/{voucher_id}
     */
    public function deleteLastRedemption(string $locale, string $member_identifier, string $voucher_id): RedirectResponse
    {
        $partner = auth('partner')->user();

        // Find member
        $member = \App\Models\Member::where('unique_identifier', $member_identifier)
            ->orWhere('id', $member_identifier)
            ->first();

        // Find voucher
        $voucher = Voucher::find($voucher_id);

        // Check if voucher belongs to partner's clubs
        if ($voucher && $voucher->club && ! in_array($voucher->club_id, $partner->clubs->pluck('id')->toArray())) {
            abort(403, 'This voucher does not belong to your clubs');
        }

        // Get the last voucher redemption for this member and voucher
        if ($member && $voucher) {
            $lastRedemption = VoucherRedemption::where('voucher_id', $voucher->id)
                ->where('member_id', $member->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastRedemption) {
                // Delete the redemption
                $lastRedemption->delete();

                session()->flash('success', 'Last voucher redemption deleted successfully');
            } else {
                session()->flash('error', 'No voucher redemption found to delete');
            }
        }

        return redirect()->route('partner.voucher.transactions', [
            'member_identifier' => $member_identifier,
            'voucher_id' => $voucher_id,
        ]);
    }

    /**
     * Void/rollback a voucher redemption
     */
    public function voidTransaction(Request $request, string $transaction_id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $partner = auth('partner')->user();

        $redemption = VoucherRedemption::whereHas('voucher.club', function ($query) use ($partner) {
            $query->where('created_by', $partner->id);
        })
            ->findOrFail($transaction_id);

        // Check if already voided
        if ($redemption->is_voided) {
            return response()->json([
                'success' => false,
                'message' => __('common.already_voided'),
            ], 422);
        }

        DB::transaction(function () use ($redemption, $request) {
            // Mark as voided
            $redemption->update([
                'is_voided' => true,
                'void_reason' => $request->reason,
                'voided_at' => now(),
                'voided_by_partner_id' => auth('partner')->id(),
            ]);

            // Increment voucher current uses back
            $redemption->voucher->decrement('current_uses');

            // If bonus points were awarded, reverse them
            if ($redemption->bonus_points_awarded && $redemption->member) {
                // This would require integration with your points system
                // You may want to create a reversal transaction
            }
        });

        return response()->json([
            'success' => true,
            'message' => __('common.redemption_voided'),
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // BATCH MANAGEMENT (NEW)
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Show batch wizard form (template-based approach)
     */
    public function showBatchWizard(): View
    {
        $partner = auth('partner')->user();

        // Get partner's clubs
        $clubs = Club::where('created_by', $partner->id)->get();

        // Get all available voucher templates (manual/custom vouchers only)
        // These are vouchers the partner created manually (batch_id IS NULL)
        // Each becomes a potential template for batch generation
        // Only show active and non-expired vouchers
        $voucherTemplates = Voucher::where('created_by', $partner->id)
            ->whereNull('batch_id')
            ->where('is_active', true) // Only active vouchers
            ->where(function ($query) {
                // Not expired: no expiry date OR expiry date in future
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->with(['club'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('partner.vouchers.batch-wizard', compact('clubs', 'voucherTemplates'));
    }

    /**
     * Generate batch using template-based wizard
     *
     * Design Philosophy:
     * - Use an existing voucher as a template
     * - Copy ALL settings from template (design, discounts, conditions)
     * - Override only batch-specific values (quantity, codes, club)
     * - Ignore member-specific settings (target_member_id, is_auto_apply)
     *
     * What's Copied from Template:
     * - type, value, currency
     * - min_purchase_amount, max_discount_amount
     * - valid_from, valid_until
     * - max_uses_total, max_uses_per_member
     * - first_order_only, new_members_only, new_members_days
     * - target_tiers
     * - title, description (translations)
     * - bg_color, bg_color_opacity, text_color, background, logo
     * - free_product_name, points_value, reward_card_id
     * - is_public, is_visible_by_default
     *
     * What's Ignored (Batch-Specific Behavior):
     * - code (each voucher gets unique code)
     * - target_member_id (batches aren't member-specific)
     * - is_auto_apply (batch codes require manual entry)
     * - batch_id (will be set to new batch)
     */
    public function generateBatchWizard(Request $request, VoucherBatchService $batchService): RedirectResponse
    {
        // Validate batch-specific fields
        $request->validate([
            'template_id' => 'required|exists:vouchers,id',
            'club_id' => 'nullable|exists:clubs,id',
            'batch_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1|max:10000',
            'code_prefix' => 'nullable|string|max:20',
        ]);

        $partner = auth('partner')->user();

        // Load template voucher
        $template = Voucher::where('id', $request->template_id)
            ->where('created_by', $partner->id)
            ->whereNull('batch_id') // Only manual vouchers can be templates
            ->with(['club'])
            ->firstOrFail();

        // Determine club (override or from template)
        // NOTE: A batch must belong to a club (VoucherBatchService::createBatch requires it).
        $clubId = $request->club_id ?: $template->club_id;
        if (! $clubId) {
            return back()
                ->withErrors(['club_id' => trans('common.please_select_club')])
                ->withInput();
        }

        $club = Club::where('id', $clubId)
            ->where('created_by', $partner->id)
            ->firstOrFail();

        try {
            // Build configuration from template + batch overrides
            $config = [
                // === BATCH-SPECIFIC ===
                'batch_name' => $request->batch_name,
                'name' => $request->batch_name, // Used for individual voucher names
                'quantity' => $request->quantity,
                'code_prefix' => $request->code_prefix,
                'template_id' => $template->id,

                // === COPIED FROM TEMPLATE ===
                // Core Discount
                'type' => $template->type,
                'value' => $template->value, // Already in cents
                'currency' => $template->currency,

                // Conditions
                'min_purchase_amount' => $template->min_purchase_amount,
                'max_discount_amount' => $template->max_discount_amount,
                'valid_from' => $template->valid_from,
                'valid_until' => $template->valid_until,

                // Usage Limits
                'max_uses_total' => $template->max_uses_total,
                'max_uses_per_member' => $template->max_uses_per_member,

                // Targeting Rules
                'first_order_only' => $template->first_order_only,
                'new_members_only' => $template->new_members_only,
                'new_members_days' => $template->new_members_days,
                'target_tiers' => $template->target_tiers,

                // Content (Translations - get ALL translations as array to preserve all locales)
                // Using getTranslations() returns array which Laravel will properly encode to JSON
                // getRawOriginal() returns JSON string which causes double-encoding
                'title' => $template->getTranslations('title'),
                'description' => $template->getTranslations('description'),

                // Design
                'bg_color' => $template->bg_color,
                'bg_color_opacity' => $template->bg_color_opacity,
                'text_color' => $template->text_color,

                // Type-Specific Fields (free_product_name is also translatable)
                'free_product_name' => $template->getTranslations('free_product_name'),
                'points_value' => $template->points_value,
                'reward_card_id' => $template->reward_card_id,

                // Visibility
                'is_public' => $template->is_public,

                // === BATCH-SPECIFIC OVERRIDES ===
                // IMPORTANT: Batch vouchers are ALWAYS active and NEVER auto-visible
                // - is_active: true (ready to be claimed immediately)
                // - is_visible_by_default: false (must be claimed via QR code/link, not discovered in wallet)
                'is_active' => true,
                'is_visible_by_default' => false,

                // === IGNORED (Batch Behavior) ===
                // 'code' - each voucher gets unique code
                // 'target_member_id' - batches aren't member-specific
                // 'is_auto_apply' - batch codes require manual entry
                // 'batch_id' - will be set by service
            ];

            Log::info('✅ Configuration built', [
                'config_keys' => array_keys($config),
                'batch_name' => $config['batch_name'],
                'quantity' => $config['quantity'],
                'code_prefix' => $config['code_prefix'],
            ]);

            // Generate batch (pass template for media copying)
            $result = $batchService->createBatch(
                club: $club,
                config: $config,
                partner: $partner,
                templateVoucher: $template
            );

            return redirect()->route('partner.vouchers.batches')
                ->with('created_batch_id', $result['batch']->id)
                ->with('success', trans('common.batch_generated_successfully', [
                    'count' => $result['vouchers']->count(),
                    'name' => $request->batch_name,
                ]));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Show all batches
     */
    public function showBatches(VoucherBatchService $batchService): View
    {
        $partner = auth('partner')->user();
        $batches = $batchService->getPartnerBatches($partner);

        // Calculate stats
        $stats = [
            'total_batches' => $batches->count(),
            'total_codes' => $batches->sum('vouchers_created'),
            'codes_used' => Voucher::whereIn('batch_id', $batches->pluck('id'))->where('times_used', '>', 0)->count(),
            'usage_rate' => 0,
        ];

        if ($stats['total_codes'] > 0) {
            $stats['usage_rate'] = round(($stats['codes_used'] / $stats['total_codes']) * 100, 1);
        }

        $batches = VoucherBatch::where('partner_id', $partner->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Check if partner has any vouchers (templates) to use for batch generation
        // Only count manual/custom vouchers (batch_id IS NULL)
        $hasVouchers = Voucher::where('created_by', $partner->id)
            ->whereNull('batch_id')
            ->exists();

        return view('partner.vouchers.batches', compact('batches', 'stats', 'hasVouchers'));
    }

    /**
     * Export batch to CSV
     */
    public function exportBatch(string $batchId, VoucherBatchService $batchService)
    {
        $partner = auth('partner')->user();
        $batch = VoucherBatch::findOrFail($batchId);

        // Verify ownership
        if ($batch->partner_id !== $partner->id) {
            abort(403);
        }

        $csv = $batchService->exportBatchToCsv($batch, [
            'filter' => request('filter'), // unused, used, active, expired
        ]);

        $filename = Str::slug($batch->name).'-'.now()->format('Y-m-d').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Toggle batch status (pause/resume)
     *
     * Pausing a batch:
     * - Claim page will show "paused" state (no claim button)
     * - Existing claimed vouchers remain usable
     * - No new claims can be made until resumed
     */
    public function toggleBatch(string $locale, VoucherBatch $batch, VoucherBatchService $batchService): RedirectResponse
    {
        $partner = auth('partner')->user();

        // Authorization check
        if ($batch->partner_id !== $partner->id) {
            abort(403);
        }

        // Toggle between 'active' and 'paused'
        $newStatus = $batch->status === 'active' ? 'paused' : 'active';
        $batch->update(['status' => $newStatus]);

        // Also update vouchers active status
        $batchService->setBatchActive($batch, $newStatus === 'active');

        $message = $newStatus === 'active'
            ? trans('common.batch_resumed_successfully')
            : trans('common.batch_paused_successfully');

        return back()->with('success', $message);
    }

    /**
     * Delete entire batch and all its vouchers
     */
    public function deleteBatch(string $locale, VoucherBatch $batch): RedirectResponse
    {
        $partner = auth('partner')->user();

        // Verify ownership
        if ($batch->partner_id !== $partner->id) {
            abort(403);
        }

        // Delete all vouchers in this batch
        Voucher::where('batch_id', $batch->id)->delete();

        // Delete the batch itself
        $batch->delete();

        return redirect()->route('partner.vouchers.batches')
            ->with('success', trans('common.batch_deleted_successfully'));
    }

    /**
     * Extend batch expiry
     */
    public function extendBatch(string $batchId, Request $request, VoucherBatchService $batchService): RedirectResponse
    {
        $request->validate([
            'valid_until' => 'required|date|after:today',
        ]);

        $partner = auth('partner')->user();
        $batch = VoucherBatch::findOrFail($batchId);

        if ($batch->partner_id !== $partner->id) {
            abort(403);
        }

        $batchService->extendBatchExpiry($batch, new \DateTime($request->valid_until));

        return back()->with('success', trans('common.batch_expiry_extended'));
    }

    /**
     * Delete unused vouchers from batch
     */
    public function deleteUnusedVouchers(string $batchId, VoucherBatchService $batchService): RedirectResponse
    {
        $partner = auth('partner')->user();
        $batch = VoucherBatch::findOrFail($batchId);

        if ($batch->partner_id !== $partner->id) {
            abort(403);
        }

        $deleted = $batchService->deleteUnusedVouchers($batch);

        return back()->with('success', trans('common.deleted_unused_vouchers', ['count' => $deleted]));
    }

    /**
     * Show batch analytics
     */
    public function showBatchAnalytics(string $batchId, VoucherBatchService $batchService): View
    {
        $partner = auth('partner')->user();
        $batch = VoucherBatch::findOrFail($batchId);

        if ($batch->partner_id !== $partner->id) {
            abort(403);
        }

        $stats = $batchService->getBatchStatistics($batch);

        return view('partner.vouchers.batch-analytics', compact('batch', 'stats'));
    }

    // ═════════════════════════════════════════════════════════════════════════
    // CSV IMPORT
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Show CSV import form
     */
    public function showImport(): View
    {
        $partner = auth('partner')->user();
        $clubs = Club::where('created_by', $partner->id)->get();

        return view('partner.vouchers.import', compact('clubs'));
    }

    /**
     * Process CSV import
     */
    public function processImport(Request $request, VoucherBatchService $batchService): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'club_id' => 'required|exists:clubs,id',
            'dry_run' => 'nullable|boolean',
            'skip_duplicates' => 'nullable|boolean',
        ]);

        $partner = auth('partner')->user();
        $club = Club::findOrFail($request->club_id);

        // Verify ownership
        if ($club->created_by !== $partner->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $batchService->importFromCsv(
                club: $club,
                file: $request->file('file'),
                options: [
                    'dry_run' => $request->boolean('dry_run', false),
                    'skip_duplicates' => $request->boolean('skip_duplicates', true),
                    'partner_id' => $partner->id,
                ]
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    // Template Management routes were removed (feature was incomplete / unused).
}
