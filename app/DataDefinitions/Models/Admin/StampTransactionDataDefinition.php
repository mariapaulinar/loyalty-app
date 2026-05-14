<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for Admin viewing and exporting of ALL stamp Transactions.
 * Provides READ-ONLY aggregate access across all partners for compliance
 * and platform-level reporting. Supports CSV/TSV/JSON export.
 *
 * Design Tenets:
 * - **Admin-Only**: Restricted to super-admin (role 1)
 * - **Aggregate View**: All stamp transactions across all partners
 * - **Ledger Integrity**: Read-only — no insert, edit, or delete
 * - **Before/After State**: Exports include stamps_before and stamps_after for audit trail
 * - **Visible Limits**: Export capped at EXPORT_LIMIT rows with explicit
 *   truncation metadata — no silent data loss
 */

namespace App\DataDefinitions\Models\Admin;

use App\DataDefinitions\DataDefinition;
use App\DataDefinitions\Models\Partner\StampTransactionDataDefinition as PartnerStampTransactionDD;
use Illuminate\Database\Eloquent\Model;

class StampTransactionDataDefinition extends DataDefinition
{
    /**
     * Maximum rows returned in a single export.
     * The base DD materializes all rows via get() before streaming,
     * so this cap prevents memory exhaustion on large aggregate tables.
     * Truncation is surfaced explicitly in export metadata.
     */
    public static int $exportLimit = 50000;

    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'stamp-transactions';

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
        $this->model = new \App\Models\StampTransaction;

        // Define the fields for the data definition
        $this->fields = [
            'event' => [
                'text' => trans('common.event'),
                'type' => 'query',
                'query' => function ($row) {
                    $key = 'common.' . $row->event;
                    $label = trans($key);

                    return $label !== $key ? $label : ucwords(str_replace('_', ' ', $row->event));
                },
                'searchable' => true,
                'sortable' => true,
                'actions' => ['list', 'view'],
                'search_map' => function (string $term): array {
                    return PartnerStampTransactionDD::matchEventSlugs($term);
                },
            ],
            'event_code' => [
                'text' => trans('common.event'),
                'type' => 'query',
                'query' => function ($row) {
                    return $row->event;
                },
                'actions' => ['export'],
            ],
            'stamp_card_name' => [
                'text' => trans('common.stamp_card'),
                'type' => 'query',
                'default' => '-',
                'query' => function ($row) {
                    return $row->stampCard?->name ?? '-';
                },
                'sql' => '(SELECT name FROM stamp_cards WHERE stamp_cards.id = stamp_transactions.stamp_card_id)',
                'searchable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
            'partner_name' => [
                'text' => trans('common.partner'),
                'type' => 'query',
                'default' => '-',
                'query' => function ($row) {
                    return $row->stampCard?->partner?->name ?? '-';
                },
                'searchable' => false,
                'actions' => ['list', 'view', 'export'],
            ],
            'member_name' => [
                'text' => trans('common.member'),
                'type' => 'query',
                'default' => '-',
                'query' => function ($row) {
                    return $row->member?->name ?? '-';
                },
                'sql' => '(SELECT name FROM members WHERE members.id = stamp_transactions.member_id)',
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
                'sql' => '(SELECT email FROM members WHERE members.id = stamp_transactions.member_id)',
                'searchable' => true,
                'actions' => ['export'],
            ],
            'stamps' => [
                'text' => trans('common.stamps'),
                'type' => 'number',
                'sortable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
            'stamps_before' => [
                'text' => trans('common.stamps_before'),
                'type' => 'number',
                'actions' => ['view', 'export'],
            ],
            'stamps_after' => [
                'text' => trans('common.stamps_after'),
                'type' => 'number',
                'actions' => ['view', 'export'],
            ],
            'purchase_amount' => [
                'text' => trans('common.purchase_amount'),
                'type' => 'number',
                'sortable' => true,
                'actions' => ['view', 'export'],
            ],
            'currency' => [
                'text' => trans('common.currency'),
                'type' => 'string',
                'actions' => ['export'],
            ],
            'staff_name' => [
                'text' => trans('common.staff'),
                'type' => 'query',
                'default' => 'System',
                'query' => function ($row) {
                    return $row->staff?->name ?? 'System';
                },
                'actions' => ['view', 'export'],
            ],
            'note' => [
                'text' => trans('common.note'),
                'type' => 'string',
                'actions' => ['view', 'export'],
            ],
            'created_at' => [
                'text' => trans('common.date'),
                'type' => 'date_time',
                'sortable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
        ];

        // Define the general settings for the data definition
        $this->settings = [
            // Query filter — all stamp transactions, eager-load relations
            'queryFilter' => function ($query) {
                $query->with(['stampCard.partner', 'member', 'staff']);

                // Ensure FK columns are selected for eager loading
                $query->addSelect(['stamp_card_id', 'member_id', 'staff_id', 'event']);

                // Count total rows before applying limit so truncation
                // can be surfaced in export metadata (not silently dropped)
                $this->exportTotalCount = (clone $query)->count();

                // Cap export size — the base DD materializes all rows
                // via get() before streaming, so unbounded queries on
                // aggregate tables can exhaust memory
                $query->limit(static::$exportLimit);

                return $query;
            },
            // Icon
            'icon' => 'scroll-text',
            // Title (plural of subject)
            'title' => trans('common.stamp_transactions'),
            // Override title
            'overrideTitle' => null,
            // Guard of user that manages this data
            'guard' => 'admin',
            // If set, these role(s) are required (super-admin only)
            'roles' => [1],
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
            'orderByColumn' => 'created_at',
            // Order direction
            'orderDirection' => 'desc',
            // Possible actions — read-only with export
            'actions' => [
                'subject_column' => 'event',
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
        return ['event', 'stamp_card_id', 'member_id', 'partner_name'];
    }

    /**
     * Compose: Event · Partner · Stamp Card · Member.
     */
    protected function getSuggestionLabel(Model $row): string
    {
        $eventKey = 'common.' . $row->event;
        $eventLabel = trans($eventKey);
        $event = $eventLabel !== $eventKey
            ? $eventLabel
            : ucwords(str_replace('_', ' ', $row->event));

        $partner = $row->partner_name ?? '';
        $stampCard = $row->stampCard?->name ?? '';
        $member = $row->member?->name ?? '';

        return implode(' · ', array_filter([$event, $partner, $stampCard, $member]));
    }
}
