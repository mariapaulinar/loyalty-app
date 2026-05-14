<?php

namespace App\DataDefinitions\Models\Staff;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

class MemberDataDefinition extends DataDefinition
{
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
                'actions' => ['list', 'insert', 'edit', 'view'],
            ],
            'email' => [
                'text' => trans('common.email_address'),
                'type' => 'string',
                'format' => 'hideEmail',
                'searchable' => false,
                'sortable' => true,
                'validate' => ['required', 'email', 'max:120', 'unique:members,email,:id'],
                'actions' => ['insert', 'edit', 'view'],
            ],
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
                    $staffId = auth('staff')->user()->id;

                    return $query->whereHas('card', function ($cardQuery) use ($staffId) {
                        $cardQuery->whereHas('transactions', function ($transactionQuery) use ($staffId) {
                            $transactionQuery->where('staff_id', $staffId)
                                ->where('created_at', '>=', \Carbon\Carbon::now()->subDays(config('default.staff_transaction_days_ago')));
                        });
                    });
                },
                'relationThroughLink' => function ($row, $column, $transaction) {
                    return route('staff.transactions', [
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
                    $staffId = auth('staff')->user()->id;

                    return $query->whereHas('stampCard', function ($cardQuery) use ($staffId) {
                        $cardQuery->whereHas('transactions', function ($transactionQuery) use ($staffId) {
                            $transactionQuery->where('staff_id', $staffId)
                                ->where('created_at', '>=', \Carbon\Carbon::now()->subDays(config('default.staff_transaction_days_ago')));
                        });
                    });
                },
                'relationThroughLink' => function ($row, $column, $transaction) {
                    return route('staff.stamp.transactions', [
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
                'relationThroughOrderByColumn' => 'voucher_redemptions.redeemed_at',
                'relationThroughOrderDirection' => 'desc',
                'relationThroughModel' => new \App\Models\VoucherRedemption,
                'relationThroughFilter' => function ($query) {
                    $staffId = auth('staff')->user()->id;

                    return $query->where('staff_id', $staffId)
                        ->where('redeemed_at', '>=', \Carbon\Carbon::now()->subDays(config('default.staff_transaction_days_ago')));
                },
                'relationThroughLink' => function ($row, $column, $redemption) {
                    return route('staff.voucher.transactions', [
                        'member_identifier' => $row->unique_identifier,
                        'voucher_id' => $redemption->voucher_id,
                    ]);
                },
                'actions' => ['list'],
            ],
            'created_at' => [
                'text' => trans('common.created'),
                'type' => 'date_time',
                'actions' => ['view'],
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
                'actions' => ['list'],
            ],
        ];

        // Define the general settings for the data definition
        $this->settings = [
            // Query filter - Include members who have interacted with ANY loyalty program type
            // (loyalty cards, stamp cards, or vouchers) processed by this staff member
            'queryFilter' => function ($query) {
                $staffId = auth('staff')->user()->id;
                $cutoffDate = \Carbon\Carbon::now()->subDays(config('default.staff_transaction_days_ago'));

                return $query->where(function ($q) use ($staffId, $cutoffDate) {
                    // Members with loyalty card transactions processed by this staff
                    $q->whereHas('transactions', function ($transactionQuery) use ($staffId, $cutoffDate) {
                        $transactionQuery->where('staff_id', $staffId)
                            ->where('created_at', '>=', $cutoffDate);
                    })
                    // OR members with stamp card transactions processed by this staff
                    ->orWhereHas('stampTransactions', function ($stampQuery) use ($staffId, $cutoffDate) {
                        $stampQuery->where('staff_id', $staffId)
                            ->where('created_at', '>=', $cutoffDate);
                    })
                    // OR members with voucher redemptions processed by this staff
                    ->orWhereHas('voucherRedemptions', function ($voucherQuery) use ($staffId, $cutoffDate) {
                        $voucherQuery->where('staff_id', $staffId)
                            ->where('redeemed_at', '>=', $cutoffDate);
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
            'guard' => 'staff',
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
                'export' => false,
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
