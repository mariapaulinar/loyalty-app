<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for Partner viewing of Members (end customers).
 * Provides READ-ONLY access to member profiles, allowing partners to view
 * their customer base and linked loyalty activity across cards, stamp cards,
 * and vouchers. Partners cannot edit member data (privacy/security).
 *
 * Design Tenets:
 * - **Privacy First**: Read-only access protects member data sovereignty
 * - **Relationship View**: Shows which loyalty programs members engage with
 * - **Recent Activity Filter**: Auto-filters to last 365 days of activity
 * - **No Tabs Needed**: Simple profile view, complexity not warranted
 *
 * Architecture Notes:
 * Members are customers - they belong to themselves, not partners. Partners
 * can VIEW members who have interacted with their loyalty programs, but cannot
 * create or modify member accounts. This maintains trust and data ownership
 * boundaries critical for consumer privacy regulations (GDPR, CCPA).
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

class MemberDataDefinition extends DataDefinition
{
    /**
     * Activity window in days - members must have activity within this period to appear.
     * Change this value to adjust how far back we look for member engagement.
     */
    private const ACTIVITY_WINDOW_DAYS = 365;

    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'members';

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
     * Constructor.
     */
    public function __construct()
    {
        // Set the model
        $this->model = new \App\Models\Member;

        // Define the fields for the data definition
        $this->fields = [
            // ═════════════════════════════════════════════════════════════
            // IDENTITY SECTION - Core Member Profile
            // ═════════════════════════════════════════════════════════════
            // What: Basic member identification and contact information
            // Why: Partners need to see WHO their customers are. Avatar, name,
            //      and email provide human-recognizable identity. No tabs needed
            //      for this simple read-only view - flat structure is clearer.
            // ═════════════════════════════════════════════════════════════
            'avatar' => [
                'thumbnail' => 'small', // Image conversion used for list
                'conversion' => 'medium', // Image conversion used for view/edit
                'text' => trans('common.avatar'),
                'type' => 'avatar',
                'textualAvatarBasedOnColumn' => 'name',
                'accept' => 'image/svg+xml, image/png, image/jpeg, image/gif',
                'validate' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:1024', 'dimensions:min_width=60,min_height=60,max_width=1024,max_height=1024'],
                'classes::list' => 'md-only:hidden',
                'actions' => ['insert', 'edit', 'view'],
            ],
            'name' => [
                'text' => trans('common.name'),
                'type' => 'string',
                'searchable' => true,
                'sortable' => true,
                'validate' => ['required', 'max:120'],
                'actions' => ['export', 'list', 'insert', 'edit', 'view'],
            ],
            'email' => [
                'text' => trans('common.email_address'),
                'type' => 'string',
                'searchable' => true,
                'sortable' => true,
                'validate' => ['nullable', 'email', 'max:120', 'unique:members,email,:id'],
                'actions' => ['export', 'list', 'insert', 'edit', 'view'],
            ],
            'accepts_emails' => [
                'text' => trans('common.allows_promotional_emails'),
                'type' => 'boolean',
                'validate' => ['nullable', 'boolean'],
                'format' => 'icon',
                'sortable' => true,
                'default' => true,
                'actions' => ['view', 'export'],
            ],

            // ═════════════════════════════════════════════════════════════
            // ENGAGEMENT SECTION - Loyalty Program Participation
            // ═════════════════════════════════════════════════════════════
            // What: Member's active participation in loyalty programs
            // Why: Partners need to see WHICH programs this member engages with.
            //      Linked cards, stamp cards, and vouchers show the relationship
            //      depth. Complex manyToMany relations filter to partner's own
            //      programs and recent activity (365 days) for relevance.
            // ═════════════════════════════════════════════════════════════
            'linked_cards' => [
                'text' => trans('common.loyalty_cards'),
                'filter' => false,
                'type' => 'manyToMany',
                'relationThrough' => 'transactions',
                'relationThroughPivot' => 'card',
                'relationThroughValue' => 'head',
                'relationThroughOrderByColumn' => 'transactions.created_at',
                'relationThroughOrderDirection' => 'desc',
                'relationThrough' => 'transactions',
                'relationThroughModel' => new \App\Models\Transaction,
                'relationThroughFilter' => function ($query) {
                    $partnerId = auth('partner')->user()->id;
                    $cutoffDate = \Carbon\Carbon::now()->subDays(self::ACTIVITY_WINDOW_DAYS);

                    return $query->whereHas('card', function ($cardQuery) use ($partnerId) {
                        $cardQuery->where('created_by', $partnerId);
                    })->where('created_at', '>=', $cutoffDate);
                },
                'relationThroughLink' => function ($row, $column, $transaction) {
                    return route('partner.transactions', [
                        'member_identifier' => $row->unique_identifier,
                        'card_identifier' => $transaction->{$column['relationThroughPivot']}->unique_identifier,
                    ]);
                },
                'actions' => ['list'],
            ],
            'linked_stamp_cards' => [
                'text' => trans('common.stamp_cards'),
                'filter' => false,
                'type' => 'manyToMany',
                'relationThrough' => 'stampTransactions',
                'relationThroughPivot' => 'stampCard',
                'relationThroughValue' => 'name',
                'relationThroughOrderByColumn' => 'stamp_transactions.created_at',
                'relationThroughOrderDirection' => 'desc',
                'relationThroughModel' => new \App\Models\StampTransaction,
                'relationThroughFilter' => function ($query) {
                    $partnerId = auth('partner')->user()->id;
                    $cutoffDate = \Carbon\Carbon::now()->subDays(self::ACTIVITY_WINDOW_DAYS);

                    return $query->whereHas('stampCard', function ($cardQuery) use ($partnerId) {
                        $cardQuery->where('created_by', $partnerId);
                    })->where('created_at', '>=', $cutoffDate);
                },
                'relationThroughLink' => function ($row, $column, $transaction) {
                    return route('partner.stamp.transactions', [
                        'member_identifier' => $row->unique_identifier,
                        'stamp_card_id' => $transaction->{$column['relationThroughPivot']}->id,
                    ]);
                },
                'actions' => ['list'],
            ],

            'linked_vouchers' => [
                'text' => trans('common.vouchers'),
                'filter' => false,
                'type' => 'manyToMany',
                'relationThrough' => 'voucherRedemptions',
                'relationThroughPivot' => 'voucher',
                'relationThroughValue' => 'code',
                'relationThroughOrderByColumn' => 'voucher_redemptions.created_at',
                'relationThroughOrderDirection' => 'desc',
                'relationThroughModel' => new \App\Models\VoucherRedemption,
                'relationThroughFilter' => function ($query) {
                    $partnerId = auth('partner')->user()->id;
                    $cutoffDate = \Carbon\Carbon::now()->subDays(self::ACTIVITY_WINDOW_DAYS);

                    return $query->whereHas('voucher.club', function ($clubQuery) use ($partnerId) {
                        $clubQuery->where('created_by', $partnerId);
                    })->where('created_at', '>=', $cutoffDate);
                },
                'relationThroughLink' => function ($row, $column, $redemption) {
                    return route('partner.voucher.transactions', [
                        'member_identifier' => $row->unique_identifier,
                        'voucher_id' => $redemption->{$column['relationThroughPivot']}->id,
                    ]);
                },
                'actions' => ['list'],
            ],

            // ═════════════════════════════════════════════════════════════
            // SYSTEM SECTION - Audit Trail & Metadata
            // ═════════════════════════════════════════════════════════════
            // What: Creation timestamps and unique identifiers
            // Why: For record-keeping and deep-linking. Unique identifier
            //      enables partner-to-member URL routing without exposing
            //      sequential IDs (security through obscurity supplement).
            // ═════════════════════════════════════════════════════════════
            'created_at' => [
                'text' => trans('common.created'),
                'type' => 'date_time',
                'actions' => ['export', 'view'],
            ],
            'created_by' => [
                'text' => trans('common.created_by'),
                'type' => 'user.admin',
                'actions' => ['view'],
            ],
            'unique_identifier' => [
                'hidden' => true,
                'type' => 'dummy',
                'text' => trans('common.identifier'),
                'actions' => ['export', 'list'],
            ],
        ];

        // Define the general settings for the data definition
        $this->settings = [
            // Query filter - Include members who have interacted with ANY loyalty program type
            // (loyalty cards, stamp cards, or vouchers) from this partner within ACTIVITY_WINDOW_DAYS
            'queryFilter' => function ($query) {
                $partnerId = auth('partner')->user()->id;
                $cutoffDate = \Carbon\Carbon::now()->subDays(self::ACTIVITY_WINDOW_DAYS);

                return $query->where(function ($q) use ($partnerId, $cutoffDate) {
                    // Members with loyalty card transactions
                    $q->whereHas('transactions', function ($transactionQuery) use ($partnerId, $cutoffDate) {
                        $transactionQuery->whereHas('staff', function ($staffQuery) use ($partnerId) {
                            $staffQuery->where('created_by', $partnerId);
                        })->where('created_at', '>=', $cutoffDate);
                    })
                    // OR members with stamp card transactions
                    ->orWhereHas('stampTransactions', function ($stampQuery) use ($partnerId, $cutoffDate) {
                        $stampQuery->whereHas('stampCard', function ($cardQuery) use ($partnerId) {
                            $cardQuery->where('created_by', $partnerId);
                        })->where('created_at', '>=', $cutoffDate);
                    })
                    // OR members with voucher redemptions
                    ->orWhereHas('voucherRedemptions', function ($voucherQuery) use ($partnerId, $cutoffDate) {
                        $voucherQuery->whereHas('voucher.club', function ($clubQuery) use ($partnerId) {
                            $clubQuery->where('created_by', $partnerId);
                        })->where('created_at', '>=', $cutoffDate);
                    });
                });
            },
            // Icon
            'icon' => 'users',
            // Title (plural of subject)
            'title' => trans('common.members'),
            // Override title (this title is shown in every view, without sub title like "Edit item" or "View item")
            'overrideTitle' => null,
            // Guard of user that manages this data (member, staff, partner or admin)
            // This guard is also used for routes and to include the correct Blade layout
            'guard' => 'partner',
            // Used for updating forms like profile, where user has to enter current password in order to save
            'editRequiresPassword' => false,
            // If true, the visitor is redirected to the edit form
            'redirectListToEdit' => false,
            // This column has to match auth($guard)->user()->id if 'redirectListToEdit' == true (usually it will be 'id' or 'created_by')
            'redirectListToEditColumn' => null,
            // If true, the user id must match the created_by field
            'userMustOwnRecords' => false,
            // Should there be checkboxes for all rows
            'multiSelect' => false,
            // Default items per page for pagination
            'itemsPerPage' => 10,
            // Order by column
            'orderByColumn' => 'id',
            // Order direction, 'asc' or 'desc'
            'orderDirection' => 'desc',
            // Order by relation
            'orderRelation' => function ($query) {
                $query->from('transactions')
                    ->select('created_at')
                    ->whereColumn('member_id', 'members.id')
                    ->orderBy('created_at', 'desc')
                    ->limit(1);
            },
            // Possible actions for the data
            'actions' => [
                'subject_column' => 'name', // This column is used for page titles and delete confirmations
                'list' => true,
                'insert' => false,
                'edit' => false,
                'delete' => false,
                'view' => false,
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
