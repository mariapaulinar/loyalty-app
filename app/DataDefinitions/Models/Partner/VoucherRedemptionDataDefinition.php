<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for Partner viewing and exporting of voucher redemption history.
 * Provides READ-ONLY access to the voucher redemption ledger for vouchers owned
 * by this partner's clubs. Supports CSV/TSV/JSON export for reporting and compliance.
 *
 * Design Tenets:
 * - **Partner Isolation**: Only redemptions for this partner's vouchers are visible
 * - **Ledger Integrity**: Read-only — no insert, edit, or delete
 * - **Export-First**: Primary purpose is data extraction for reporting
 * - **Visible Limits**: Export capped at EXPORT_LIMIT rows with explicit
 *   truncation metadata — no silent data loss
 * - **Attribution**: Staff vs Self-Service clearly indicated
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

class VoucherRedemptionDataDefinition extends DataDefinition
{
    /**
     * Maximum rows returned in a single export.
     * The base DD materializes all rows via get() before streaming,
     * so this cap prevents memory exhaustion on large ledger tables.
     * Truncation is surfaced explicitly in export metadata.
     */
    public static int $exportLimit = 50000;

    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'voucher-redemptions';

    /**
     * The model associated with the definition.
     *
     * @var Model
     */
    public $model;

    /**
     * Settings.
     *
     * @var array
     */
    public $settings;

    /**
     * Model fields for list, edit, view.
     *
     * @var array
     */
    public $fields;

    /**
     * Total rows available before the export limit is applied.
     * Set during queryFilter execution; read during getData() to
     * inject truncation metadata into the export result.
     *
     * @var int|null
     */
    private ?int $exportTotalCount = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Set the model
        $this->model = new \App\Models\VoucherRedemption;

        // Define the fields for the data definition
        $this->fields = [
            'voucher_code' => [
                'text' => trans('common.voucher_code'),
                'type' => 'query',
                'default' => '-',
                'query' => function ($row) {
                    return $row->voucher?->code ?? '-';
                },
                'sql' => '(SELECT code FROM vouchers WHERE vouchers.id = voucher_redemptions.voucher_id)',
                'searchable' => true,
                'sortable' => false,
                'actions' => ['list', 'view', 'export'],
            ],
            'voucher_name' => [
                'text' => trans('common.voucher'),
                'type' => 'query',
                'default' => '-',
                'query' => function ($row) {
                    return $row->voucher?->name ?? '-';
                },
                'sql' => '(SELECT name FROM vouchers WHERE vouchers.id = voucher_redemptions.voucher_id)',
                'searchable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
            'member_name' => [
                'text' => trans('common.member'),
                'type' => 'query',
                'default' => '-',
                'query' => function ($row) {
                    return $row->member?->name ?? '-';
                },
                'sql' => '(SELECT name FROM members WHERE members.id = voucher_redemptions.member_id)',
                'searchable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
            'member_email' => [
                'text' => trans('common.email_address'),
                'type' => 'query',
                'default' => '-',
                'query' => function ($row) {
                    return $row->member?->email ?? '-';
                },
                'sql' => '(SELECT email FROM members WHERE members.id = voucher_redemptions.member_id)',
                'searchable' => true,
                'actions' => ['export'],
            ],
            'status' => [
                'text' => trans('common.status'),
                'type' => 'string',
                'searchable' => true,
                'sortable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
            'discount_amount' => [
                'text' => trans('common.discount_amount'),
                'type' => 'number',
                'sortable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
            'original_amount' => [
                'text' => trans('common.purchase_amount'),
                'type' => 'number',
                'actions' => ['view', 'export'],
            ],
            'final_amount' => [
                'text' => trans('common.final_amount'),
                'type' => 'number',
                'actions' => ['view', 'export'],
            ],
            'currency' => [
                'text' => trans('common.currency'),
                'type' => 'string',
                'actions' => ['export'],
            ],
            'points_awarded' => [
                'text' => trans('common.points'),
                'type' => 'number',
                'actions' => ['view', 'export'],
            ],
            'order_reference' => [
                'text' => trans('common.order_reference'),
                'type' => 'string',
                'searchable' => true,
                'actions' => ['view', 'export'],
            ],
            'staff_name' => [
                'text' => trans('common.staff'),
                'type' => 'query',
                'default' => trans('common.self_service'),
                'query' => function ($row) {
                    return $row->staff?->name ?? trans('common.self_service');
                },
                'actions' => ['view', 'export'],
            ],
            'redeemed_at' => [
                'text' => trans('common.redeemed_at'),
                'type' => 'date_time',
                'sortable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
        ];

        // Get current partner ID for scoping
        $partnerId = auth('partner')->id();

        // Define the general settings for the data definition
        $this->settings = [
            // Query filter — only redemptions for this partner's vouchers
            'queryFilter' => function ($query) use ($partnerId) {
                $query->with(['voucher', 'member', 'staff']);

                // Ensure FK columns are selected for eager loading
                // (the DD system only selects declared fields)
                $query->addSelect(['voucher_id', 'member_id', 'staff_id']);

                $query->whereHas('voucher', function ($voucherQuery) use ($partnerId) {
                    $voucherQuery->whereHas('club', function ($clubQuery) use ($partnerId) {
                        $clubQuery->where('created_by', $partnerId);
                    });
                });

                // Count total rows before applying limit so truncation
                // can be surfaced in export metadata (not silently dropped)
                $this->exportTotalCount = (clone $query)->count();

                // Cap export size — the base DD materializes all rows
                // via get() before streaming, so unbounded queries on
                // large ledger tables can exhaust memory
                $query->limit(static::$exportLimit);

                return $query;
            },
            // Icon
            'icon' => 'scroll-text',
            // Title (plural of subject)
            'title' => trans('common.voucher_redemption_history'),
            // Override title
            'overrideTitle' => null,
            // Guard of user that manages this data
            'guard' => 'partner',
            // Password not required for viewing
            'editRequiresPassword' => false,
            // Don't redirect to edit
            'redirectListToEdit' => false,
            // Column for redirect
            'redirectListToEditColumn' => null,
            // User ownership
            'userMustOwnRecords' => false,
            // No multi-select (read-only)
            'multiSelect' => false,
            // Items per page
            'itemsPerPage' => 25,
            // Order by column
            'orderByColumn' => 'redeemed_at',
            // Order direction
            'orderDirection' => 'desc',
            // Possible actions — read-only with export
            'actions' => [
                'subject_column' => 'status',
                'list' => true,
                'insert' => false,
                'edit' => false,
                'delete' => false,
                'view' => true,
                'export' => true,
            ],
        ];
    }

    /**
     * Do not modify below this line.
     *
     * ---------------------------------
     */

    /**
     * Retrieve data based on fields.
     *
     * Overridden to inject truncation metadata when the export limit
     * is exceeded. This makes the cap visible to ExportController
     * so it can surface it in JSON meta / CSV footer.
     */
    public function getData(?string $dateDefinitionName = null, string $dateDefinitionView = 'list', array $options = [], ?Model $model = null, array $settings = [], array $fields = []): array
    {
        $result = parent::getData($this->name, $dateDefinitionView, $options, $this->model, $this->settings, $this->fields);

        // Surface truncation metadata when the export limit was hit
        if ($this->exportTotalCount !== null && $this->exportTotalCount > static::$exportLimit) {
            $result['meta'] = [
                'total_available' => $this->exportTotalCount,
                'truncated' => true,
                'export_limit' => static::$exportLimit,
            ];
        }

        return $result;
    }

    /**
     * Parse settings.
     */
    public function getSettings(array $settings): array
    {
        return parent::getSettings($this->settings);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Search suggestions — multi-field labels for autocomplete
    // ─────────────────────────────────────────────────────────────────────

    protected function getSuggestionSelectColumns(Model $model): array
    {
        return ['voucher_id', 'member_id'];
    }

    /**
     * Compose: Voucher Code · Voucher Name · Member.
     *
     * Status is excluded — it's almost always "completed"
     * and adds noise rather than signal in suggestions.
     */
    protected function getSuggestionLabel(Model $row): string
    {
        $code = $row->voucher?->code ?? '';
        $name = $row->voucher?->name ?? '';
        $member = $row->member?->name ?? '';

        return implode(' · ', array_filter([$code, $name, $member]));
    }
}
