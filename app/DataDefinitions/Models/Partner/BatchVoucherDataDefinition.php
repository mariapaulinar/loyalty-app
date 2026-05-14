<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for viewing and managing batch-generated vouchers.
 * Filters to show ONLY vouchers from a specific batch (batch_id = X).
 *
 * Design Tenets:
 * - **Batch Context**: Always scoped to a single batch
 * - **Read-Mostly**: Limited editing (codes are auto-generated)
 * - **Architectural Separation**: Distinct from manual vouchers
 * - **Analytics Focus**: Shows batch performance and redemption stats
 *
 * Relationship:
 * - VoucherDataDefinition → Custom vouchers (batch_id IS NULL)
 * - BatchVoucherDataDefinition → Batch vouchers (batch_id = X)
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

class BatchVoucherDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'batch-vouchers';

    /**
     * The model associated with the definition.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    /**
     * Settings.
     *
     * @var array
     */
    public $settings;

    /**
     * Model fields for list, insert, edit, view.
     *
     * @var array
     */
    public $fields;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Permission check via EntitlementService
        if (auth('partner')->check()) {
            if (! app(\App\Services\EntitlementService::class)->can(auth('partner')->user(), 'voucher_batches')) {
                abort(403);
            }
        }

        // Set the model
        $this->model = new \App\Models\Voucher;

        // Define the fields for the data definition
        // NOTE: Simplified field set for batch vouchers - focused on viewing/analytics
        $this->fields = [
            // ═════════════════════════════════════════════════════════════
            // TAB 1: DETAILS - Core Configuration (Read-Only for Batch)
            // ═════════════════════════════════════════════════════════════
            'tab1' => [
                'title' => trans('common.details'),
                'fields' => [
                    'club_id' => [
                        'text' => trans('common.club'),
                        'help' => trans('common.voucher_club_help'),
                        'classes::list' => 'md-only:hidden',
                        'highlight' => true,
                        'filter' => false,
                        'type' => 'belongsTo',
                        'relation' => 'club',
                        'relationKey' => 'clubs.id',
                        'relationValue' => 'clubs.name',
                        'relationModel' => new \App\Models\Club,
                        'relationMustBeOwned' => true,
                        'actions' => ['list', 'view', 'export'],
                    ],
                    'name' => [
                        'text' => trans('common.internal_name'),
                        'type' => 'string',
                        'help' => trans('common.batch_voucher_name_help'),
                        'searchable' => true,
                        'sortable' => true,
                        'actions' => ['view', 'export'], // Removed from list
                    ],
                    'code' => [
                        'text' => trans('common.voucher_code'),
                        'type' => 'string',
                        'help' => trans('common.batch_code_help'),
                        'searchable' => true,
                        'sortable' => true,
                        'actions' => ['list', 'view', 'export'],
                        'classes::list' => 'font-mono font-bold',
                    ],
                    'is_active' => [
                        'text' => trans('common.active'),
                        'type' => 'boolean',
                        'format' => 'icon',
                        'sortable' => true,
                        'help' => trans('common.voucher_is_active_help'),
                        'actions' => ['edit', 'view', 'export'],
                    ],
                    'claimed_via' => [
                        'text' => trans('common.claimed_via'),
                        'type' => 'select',
                        'options' => [
                            'qr' => trans('common.qr_code'),
                            'email' => trans('common.email'),
                            'staff' => trans('common.staff'),
                            'web' => trans('common.website'),
                        ],
                        'help' => trans('common.claimed_via_help'),
                        'actions' => ['list', 'view', 'export'],
                    ],
                    'claimed_at' => [
                        'text' => trans('common.claimed_at'),
                        'type' => 'date_time',
                        'sortable' => true,
                        'help' => 'When this voucher was claimed',
                        'actions' => ['list', 'view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 2: CONTENT - Customer-Facing Copy
            // ═════════════════════════════════════════════════════════════
            'tab2' => [
                'title' => trans('common.content'),
                'fields' => [
                    'title' => [
                        'text' => trans('common.title'),
                        'type' => 'string',
                        'translatable' => true,
                        'highlight' => true, // Added: Show prominently in list
                        'searchable' => true, // Added: Make searchable
                        'sortable' => true, // Added: Make sortable
                        'actions' => ['view', 'export'], // Added: Show in list
                    ],
                    'description' => [
                        'text' => trans('common.description'),
                        'type' => 'string',
                        'translatable' => true,
                        'actions' => ['view', 'export'], // Added: Show in list
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 3: DISCOUNT - Value Mechanics
            // ═════════════════════════════════════════════════════════════
            'tab3' => [
                'title' => trans('common.discount'),
                'fields' => [
                    'type' => [
                        'text' => trans('common.discount_type'),
                        'type' => 'select',
                        'options' => [
                            'percentage' => trans('common.batch_voucher_type_percentage'),
                            'fixed_amount' => trans('common.batch_voucher_type_fixed_amount'),
                            'free_product' => trans('common.batch_voucher_type_free_product'),
                            'bonus_points' => trans('common.batch_voucher_type_bonus_points'),
                        ],
                        'actions' => ['view', 'export'],
                    ],
                    'value' => [
                        'text' => trans('common.discount_value'),
                        'type' => 'string',
                        'format' => 'number',
                        'help' => trans('common.batch_discount_value_help'),
                        'actions' => ['view', 'export'],
                    ],
                    'free_product_name' => [
                        'text' => trans('common.free_product_name'),
                        'type' => 'string',
                        'translatable' => true,
                        'help' => trans('common.voucher_free_product_help'),
                        'validate' => ['nullable', 'max:256'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                        'conditional' => [
                            'field' => 'type',
                            'values' => ['free_product'],
                        ],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 4: CONDITIONS - Usage Rules & Restrictions
            // ═════════════════════════════════════════════════════════════
            'tab4' => [
                'title' => trans('common.conditions'),
                'fields' => [
                    'valid_from' => [
                        'text' => trans('common.valid_from'),
                        'type' => 'string',
                        'format' => 'datetime-local',
                        'help' => trans('common.voucher_valid_from_help'),
                        'actions' => ['view', 'export'],
                    ],
                    'valid_until' => [
                        'text' => trans('common.valid_until'),
                        'type' => 'string',
                        'format' => 'datetime-local',
                        'help' => trans('common.voucher_valid_until_help'),
                        'actions' => ['view', 'export'],
                    ],
                    'max_uses_per_member' => [
                        'text' => trans('common.per_member_limit'),
                        'type' => 'string',
                        'format' => 'number',
                        'suffix' => 'uses',
                        'actions' => ['view', 'export'],
                    ],
                    'is_single_use' => [
                        'text' => trans('common.single_use_only'),
                        'type' => 'boolean',
                        'format' => 'icon',
                        'actions' => ['view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // SYSTEM FIELDS - Analytics & Metadata
            // ═════════════════════════════════════════════════════════════
            'times_used' => [
                'text' => trans('common.times_used'),
                'type' => 'string',
                'format' => 'number',
                'suffix' => 'redemptions',
                'help' => trans('common.batch_times_used_help'),
                'actions' => ['list', 'view', 'export'],
            ],
            'target_member_id' => [
                'text' => trans('common.assigned_to'),
                'type' => 'belongsTo',
                'relation' => 'targetMember',
                'relationKey' => 'members.id',
                'relationValue' => 'members.name',
                'relationModel' => new \App\Models\Member,
                'help' => trans('common.batch_claimed_by_help'),
                'actions' => ['list', 'view', 'export'],
            ],
            'qr' => [
                'text' => 'QR Code',
                'type' => 'qr',
                'titleColumn' => 'name',
                'url' => route('member.voucher', ['voucher_id' => ':id']),
                'actions' => ['list', 'view'],
            ],
            'created_at' => [
                'text' => trans('common.created'),
                'type' => 'date_time',
                'sortable' => true,
                'actions' => ['view', 'export'],
            ],
        ];

        // Settings
        $this->settings = [
            // Query filter - only show vouchers from specific batch
            // For list views, batch_id is required to scope results.
            // For view/edit of individual records, skip batch filtering
            // so the record can be found by its primary key.
            'queryFilter' => function ($query) {
                $batchId = request()->get('batch_id');
                if ($batchId) {
                    return $query->where('batch_id', $batchId);
                }

                // If viewing/editing an individual record (route has 'id' param),
                // skip batch filtering — the record is looked up by primary key.
                if (request()->route('id')) {
                    return $query;
                }

                // Fallback: show no results if batch_id missing on list view
                return $query->where('batch_id', 'INVALID_NO_RESULTS');
            },
            // Icon
            'icon' => 'tickets',
            // Title (plural of subject)
            'title' => trans('common.batch_vouchers_title'),
            // Override title
            'overrideTitle' => null,
            // Description
            'description' => trans('common.batch_vouchers_description'),
            // Eager load relationships to prevent N+1 and null errors
            'with' => ['club', 'rewardCard', 'targetMember'],
            // Guard of user that manages this data
            'guard' => 'partner',
            // Required role(s)
            'roles' => [1, 2, 3],
            // Requires password for editing
            'editRequiresPassword' => false,
            // Redirect list to edit
            'redirectListToEdit' => false,
            // Redirect column
            'redirectListToEditColumn' => null,
            // User must own records
            'userMustOwnRecords' => true,
            // Multi-select checkboxes
            'multiSelect' => false,
            // Items per page
            'itemsPerPage' => 50,
            // Order by column
            'orderByColumn' => 'created_at',
            // Order direction
            'orderDirection' => 'desc',
            // Possible actions
            'actions' => [
                'subject_column' => 'code',
                'list' => true,
                'insert' => false, // Batch vouchers created via batch wizard, not manually
                'edit' => false, // Allow editing status/activation
                'delete' => false,
                'view' => true,
                'export' => true,
            ],
            // Empty list redirect
            'onEmptyListRedirectTo' => null,
        ];
    }

    /**
     * Do not modify below this line.
     *
     * ---------------------------------
     */

    /**
     * Retrieve data based on fields.
     */
    public function getData(?string $dateDefinitionName = null, string $dateDefinitionView = 'list', array $options = [], ?Model $model = null, array $settings = [], array $fields = []): array
    {
        return parent::getData($this->name, $dateDefinitionView, $options, $this->model, $this->settings, $this->fields);
    }

    /**
     * Parse settings.
     */
    public function getSettings(array $settings): array
    {
        return parent::getSettings($this->settings);
    }
}
