<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Orchestrates batch voucher operations including generation, import, export,
 * template management, and batch-level analytics. This is the command center
 * for high-volume voucher operations.
 *
 * Design Tenets:
 * - **Batch-First**: Optimized for operations on 100-10,000 vouchers
 * - **Template-Driven**: Reusable campaign templates for consistency
 * - **Import-Friendly**: CSV/Excel import with validation and dry-run
 * - **Export-Ready**: Multiple formats (CSV, Excel, PDF) with filtering
 * - **Analytics-Rich**: Batch-level metrics and performance tracking
 *
 * Why This Matters:
 * Partners need to manage thousands of voucher codes efficiently. This service
 * provides the infrastructure for campaigns, imports from legacy systems,
 * and bulk operations that would be tedious one-by-one.
 *
 * Example Usage:
 * ```php
 * // Generate 500 welcome codes
 * $batch = $batchService->createBatch($club, [
 *     'name' => 'Welcome Campaign Q1 2025',
 *     'quantity' => 500,
 *     'template' => 'welcome_gift',
 * ]);
 *
 * // Import codes from CSV
 * $result = $batchService->importFromCsv($club, $file, [
 *     'dry_run' => true // Validate first
 * ]);
 *
 * // Export unused codes
 * $csv = $batchService->exportBatch($batch, [
 *     'filter' => 'unused',
 *     'format' => 'csv'
 * ]);
 * ```
 */

namespace App\Services;

use App\Events\VoucherBatchCreated;
use App\Events\VoucherBatchImported;
use App\Models\Club;
use App\Models\Partner;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherBatchService
{
    public function __construct(
        protected VoucherService $voucherService
    ) {}

    // ═════════════════════════════════════════════════════════════════════════
    // BATCH CREATION
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Create a batch of vouchers with consistent configuration.
     *
     * This is the primary method for generating multiple vouchers at once.
     * All vouchers in a batch share the same discount settings but have unique codes.
     *
     * Storage Optimization:
     * If a template voucher is provided, its media (logo/background) will be copied
     * ONLY to the first voucher in the batch. Other vouchers reference this shared media.
     *
     * @param  Club  $club  The club these vouchers belong to
     * @param  array  $config  Batch configuration
     * @param  Partner|null  $partner  Partner creating the batch
     * @param  Voucher|null  $templateVoucher  Template voucher to copy media from
     * @return array{batch: VoucherBatch, vouchers: Collection<int, Voucher>, codes: array<string>}
     */
    public function createBatch(
        Club $club,
        array $config,
        ?Partner $partner = null,
        ?Voucher $templateVoucher = null
    ): array {
        // Validate quantity
        $quantity = (int) ($config['quantity'] ?? 10);
        if ($quantity < 1 || $quantity > 10000) {
            throw new \InvalidArgumentException('Quantity must be between 1 and 10,000');
        }

        // Generate batch identifier
        $batchId = 'BATCH-'.strtoupper(Str::random(8));

        // Prepare voucher configuration (shared by all vouchers in batch)
        // Currency priority: explicit config > partner currency > club currency > config default
        $currency = $config['currency']
            ?? $partner?->currency
            ?? $club->currency
            ?? config('default.currency');

        $voucherConfig = [
            'name' => $config['name'] ?? "Batch Voucher {$batchId}",
            'title' => $config['title'] ?? null,
            'description' => $config['description'] ?? null,
            'type' => $config['type'] ?? 'percentage',
            'value' => $config['value'] ?? 0,
            'currency' => $currency,
            'min_purchase_amount' => $config['min_purchase_amount'] ?? null,
            'max_discount_amount' => $config['max_discount_amount'] ?? null,
            'max_uses_total' => $config['max_uses_total'] ?? null,
            'max_uses_per_member' => $config['max_uses_per_member'] ?? 1,
            'is_single_use' => $config['is_single_use'] ?? true,
            'valid_from' => $config['valid_from'] ?? now(),
            'valid_until' => $config['valid_until'] ?? null,
            'is_active' => $config['is_active'] ?? true,
            'is_public' => $config['is_public'] ?? false,
            'is_visible_by_default' => $config['is_visible_by_default'] ?? false,
            'source' => 'batch',
            'batch_id' => $batchId,
            'created_by' => $partner?->id,
            // Design settings
            'bg_color' => $config['bg_color'] ?? '#7C3AED',
            'bg_color_opacity' => $config['bg_color_opacity'] ?? 85,
            'text_color' => $config['text_color'] ?? '#FFFFFF',
            // Points for bonus_points type
            'points_value' => $config['points_value'] ?? null,
            'reward_card_id' => $config['reward_card_id'] ?? null,
        ];

        // Code generation settings
        $codePrefix = strtoupper($config['code_prefix'] ?? '');
        $codeLength = (int) ($config['code_length'] ?? 8);

        // Generate batch
        return DB::transaction(function () use ($club, $voucherConfig, $quantity, $codePrefix, $codeLength, $batchId, $config, $partner, $templateVoucher) {
            // Create batch record
            $batch = VoucherBatch::create([
                'id' => $batchId,
                'club_id' => $club->id,
                'partner_id' => $partner?->id,
                'name' => $config['batch_name'] ?? $voucherConfig['name'],
                'description' => $config['batch_description'] ?? null,
                'quantity' => $quantity,
                'code_prefix' => $codePrefix ?: null,
                'config' => $voucherConfig,
                'status' => 'active',
                'meta' => [
                    'template_id' => $config['template_id'] ?? null,
                    'created_via' => 'web',
                ],
            ]);

            // Generate vouchers (pass template for media copying to first voucher only)
            $vouchers = $this->voucherService->generateBatch(
                club: $club,
                voucherConfig: $voucherConfig,
                quantity: $quantity,
                codePrefix: $codePrefix ?: null,
                codeLength: $codeLength,
                templateVoucher: $templateVoucher
            );

            // Extract codes for easy export
            $codes = $vouchers->pluck('code')->toArray();

            // Update batch statistics
            $batch->update([
                'vouchers_created' => $vouchers->count(),
            ]);

            // Fire event
            event(new VoucherBatchCreated($batch, $vouchers));

            return [
                'batch' => $batch,
                'vouchers' => $vouchers,
                'codes' => $codes,
            ];
        });
    }

    // ═════════════════════════════════════════════════════════════════════════
    // CSV IMPORT
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Import vouchers from CSV file.
     *
     * Supports both dry-run (validation only) and actual import.
     * CSV format: code,type,value,valid_until,max_uses_per_member
     *
     * @param  Club  $club  Target club
     * @param  UploadedFile  $file  CSV file
     * @param  array  $options  Import options
     * @return array{success: bool, imported: int, skipped: int, errors: array, dry_run: bool, preview: array}
     */
    public function importFromCsv(
        Club $club,
        UploadedFile $file,
        array $options = []
    ): array {
        $dryRun = $options['dry_run'] ?? false;
        $skipDuplicates = $options['skip_duplicates'] ?? true;

        // Validate file
        if ($file->getClientMimeType() !== 'text/csv' && ! str_ends_with($file->getClientOriginalName(), '.csv')) {
            throw new \InvalidArgumentException('File must be CSV format');
        }

        // Read CSV
        $csv = array_map('str_getcsv', file($file->getRealPath()));
        $headers = array_shift($csv);

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $preview = [];

        foreach ($csv as $index => $row) {
            $lineNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed

            try {
                // Parse row
                $data = array_combine($headers, $row);

                // Required fields
                if (empty($data['code'])) {
                    $errors[] = "Line {$lineNumber}: Code is required";
                    $skipped++;

                    continue;
                }

                $code = strtoupper(trim($data['code']));

                // Check for duplicates
                $exists = Voucher::where('club_id', $club->id)
                    ->where('code', $code)
                    ->exists();

                if ($exists) {
                    if ($skipDuplicates) {
                        $preview[] = [
                            'line' => $lineNumber,
                            'code' => $code,
                            'status' => 'skipped',
                            'reason' => 'Duplicate code',
                        ];
                        $skipped++;

                        continue;
                    } else {
                        $errors[] = "Line {$lineNumber}: Duplicate code '{$code}'";
                        $skipped++;

                        continue;
                    }
                }

                // Prepare voucher data
                // Currency priority: CSV value > partner option > club currency > config default
                $voucherCurrency = $data['currency']
                    ?? ($options['partner_currency'] ?? null)
                    ?? $club->currency
                    ?? config('default.currency');

                $voucherData = [
                    'club_id' => $club->id,
                    'code' => $code,
                    'name' => $data['name'] ?? "Imported: {$code}",
                    'type' => $data['type'] ?? 'percentage',
                    'value' => isset($data['value']) ? (int) ($data['value'] * 100) : 0, // Convert to cents
                    'currency' => $voucherCurrency,
                    'min_purchase_amount' => isset($data['min_purchase_amount']) ? (int) ($data['min_purchase_amount'] * 100) : null,
                    'max_uses_total' => isset($data['max_uses_total']) ? (int) $data['max_uses_total'] : null,
                    'max_uses_per_member' => isset($data['max_uses_per_member']) ? (int) $data['max_uses_per_member'] : 1,
                    'valid_from' => $data['valid_from'] ?? now(),
                    'valid_until' => $data['valid_until'] ?? null,
                    'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                    'is_public' => isset($data['is_public']) ? (bool) $data['is_public'] : false,
                    'source' => 'imported',
                    'created_by' => $options['partner_id'] ?? null,
                ];

                // Add to preview
                $preview[] = [
                    'line' => $lineNumber,
                    'code' => $code,
                    'type' => $voucherData['type'],
                    'value' => $voucherData['value'],
                    'status' => 'ready',
                ];

                // Create voucher (if not dry run)
                if (! $dryRun) {
                    Voucher::create($voucherData);
                }

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Line {$lineNumber}: {$e->getMessage()}";
                $skipped++;
            }
        }

        // Fire event if actual import
        if (! $dryRun && $imported > 0) {
            event(new VoucherBatchImported($club, $imported));
        }

        return [
            'success' => empty($errors),
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'dry_run' => $dryRun,
            'preview' => array_slice($preview, 0, 10), // First 10 for preview
        ];
    }

    // ═════════════════════════════════════════════════════════════════════════
    // EXPORT
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Export batch vouchers to CSV.
     *
     * @param  VoucherBatch|string  $batch  Batch model or batch ID
     * @param  array  $options  Export options (filter, format)
     * @return string CSV content
     */
    public function exportBatchToCsv($batch, array $options = []): string
    {
        if (is_string($batch)) {
            $batch = VoucherBatch::findOrFail($batch);
        }

        // Get vouchers
        $query = Voucher::where('batch_id', $batch->id);

        // Apply filters
        if (isset($options['filter'])) {
            switch ($options['filter']) {
                case 'unused':
                    $query->where('times_used', 0);
                    break;
                case 'used':
                    $query->where('times_used', '>', 0);
                    break;
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'expired':
                    $query->where('valid_until', '<', now());
                    break;
            }
        }

        $vouchers = $query->get();

        // Generate CSV
        $csv = "code,type,value,min_purchase,valid_from,valid_until,times_used,status\n";

        foreach ($vouchers as $voucher) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%d,%s\n",
                $voucher->code,
                $voucher->type,
                $voucher->value / 100, // Convert from cents
                $voucher->min_purchase_amount ? $voucher->min_purchase_amount / 100 : '',
                $voucher->valid_from?->format('Y-m-d') ?? '',
                $voucher->valid_until?->format('Y-m-d') ?? '',
                $voucher->times_used,
                $voucher->is_active ? 'active' : 'inactive'
            );
        }

        return $csv;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // BATCH OPERATIONS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get batch statistics and analytics.
     *
     * @param  VoucherBatch|string  $batch  Batch model or ID
     * @return array<string, mixed> Statistics
     */
    public function getBatchStatistics($batch): array
    {
        if (is_string($batch)) {
            $batch = VoucherBatch::findOrFail($batch);
        }

        $vouchers = Voucher::where('batch_id', $batch->id)->get();

        $totalGenerated = $vouchers->count();
        $totalUsed = $vouchers->where('times_used', '>', 0)->count();
        $totalUnused = $totalGenerated - $totalUsed;
        $totalRedemptions = $vouchers->sum('times_used');
        $totalDiscountGiven = $vouchers->sum('total_discount_given');
        $uniqueMembers = $vouchers->sum('unique_members_used');

        // Expiry status
        $expired = $vouchers->filter(fn ($v) => $v->is_expired)->count();
        $active = $vouchers->filter(fn ($v) => ! $v->is_expired && $v->is_active)->count();

        return [
            'batch_id' => $batch->id,
            'batch_name' => $batch->name,
            'total_generated' => $totalGenerated,
            'total_used' => $totalUsed,
            'total_unused' => $totalUnused,
            'usage_rate' => $totalGenerated > 0 ? round(($totalUsed / $totalGenerated) * 100, 2) : 0,
            'total_redemptions' => $totalRedemptions,
            'total_discount_given' => $totalDiscountGiven,
            'unique_members' => $uniqueMembers,
            'active_vouchers' => $active,
            'expired_vouchers' => $expired,
            'average_uses_per_voucher' => $totalGenerated > 0 ? round($totalRedemptions / $totalGenerated, 2) : 0,
            'created_at' => $batch->created_at,
        ];
    }

    /**
     * Bulk activate/deactivate batch vouchers.
     *
     * @param  VoucherBatch|string  $batch  Batch model or ID
     * @param  bool  $active  Activation status
     * @return int Number of vouchers updated
     */
    public function setBatchActive($batch, bool $active): int
    {
        if (is_string($batch)) {
            $batch = VoucherBatch::findOrFail($batch);
        }

        return Voucher::where('batch_id', $batch->id)
            ->update(['is_active' => $active]);
    }

    /**
     * Bulk extend expiry date for batch vouchers.
     *
     * @param  VoucherBatch|string  $batch  Batch model or ID
     * @param  \DateTimeInterface  $newExpiry  New expiry date
     * @return int Number of vouchers updated
     */
    public function extendBatchExpiry($batch, \DateTimeInterface $newExpiry): int
    {
        if (is_string($batch)) {
            $batch = VoucherBatch::findOrFail($batch);
        }

        return Voucher::where('batch_id', $batch->id)
            ->update(['valid_until' => $newExpiry]);
    }

    /**
     * Delete unused vouchers from batch.
     *
     * @param  VoucherBatch|string  $batch  Batch model or ID
     * @return int Number of vouchers deleted
     */
    public function deleteUnusedVouchers($batch): int
    {
        if (is_string($batch)) {
            $batch = VoucherBatch::findOrFail($batch);
        }

        return Voucher::where('batch_id', $batch->id)
            ->where('times_used', 0)
            ->delete();
    }

    /**
     * Get all batches for a partner.
     *
     * @param  Partner  $partner  The partner
     * @return Collection<int, VoucherBatch>
     */
    public function getPartnerBatches(Partner $partner): Collection
    {
        return VoucherBatch::where('partner_id', $partner->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
