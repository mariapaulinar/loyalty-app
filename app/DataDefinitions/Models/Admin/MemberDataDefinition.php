<?php

namespace App\DataDefinitions\Models\Admin;

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
                // 'classes::list' => 'lg-only:hidden',
                'actions' => ['export', 'insert', 'edit', 'view'],
            ],
            'locale' => [
                'text' => trans('common.language'),
                'type' => 'locale',
                'default' => app()->make('i18n')->language->current->locale,
                'validate' => ['required'],
                'actions' => ['view', 'edit', 'insert', 'export'],
            ],
            'time_zone' => [
                'text' => trans('common.time_zone'),
                'type' => 'time_zone',
                'default' => app()->make('i18n')->time_zone,
                'validate' => ['required', 'timezone'],
                'actions' => ['view', 'edit', 'insert', 'export'],
            ],
            'is_active' => [
                'text' => trans('common.active'),
                'type' => 'boolean',
                'validate' => ['nullable', 'boolean'],
                'format' => 'icon',
                'sortable' => true,
                'default' => true,
                'help' => trans('common.partner_is_active_text'),
                'classes::list' => 'lg-only:hidden',
                'actions' => ['insert', 'edit', 'view', 'export'],
            ],
            'number_of_times_logged_in' => [
                'text' => trans('common.logins'),
                'type' => 'number',
                'classes::list' => 'lg-only:hidden',
                'actions' => ['list', 'export'],
            ],
            'last_login_at' => [
                'text' => trans('common.last_login'),
                'type' => 'date_time',
                'default' => trans('common.never'),
                'classes::list' => 'lg-only:hidden',
                'actions' => ['list', 'export'],
            ],
            'login_as' => [
                'text' => trans('common.log_in'),
                'type' => 'impersonate',
                'guard' => 'member',
                'actions' => ['list'],
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
                    return $query->whereHas('card');
                },
                'relationThroughLink' => function ($row, $column, $transaction) {
                    return route('admin.transactions', [
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
                    return $query->whereHas('stampCard');
                },
                'relationThroughLink' => function ($row, $column, $transaction) {
                    return route('admin.stamp.transactions', [
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
                    return $query->whereHas('voucher');
                },
                'relationThroughLink' => function ($row, $column, $redemption) {
                    return route('admin.voucher.transactions', [
                        'member_identifier' => $row->unique_identifier,
                        'voucher_id' => $redemption->{$column['relationThroughPivot']}->id,
                    ]);
                },
                'actions' => ['list'],
            ],
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
            // Icon
            'icon' => 'users',
            // Title (plural of subject)
            'title' => trans('common.members'),
            // Override title (this title is shown in every view, without sub title like "Edit item" or "View item")
            'overrideTitle' => null,
            // Guard of user that manages this data (member, staff, partner or admin)
            // This guard is also used for routes and to include the correct Blade layout
            'guard' => 'admin',
            // If set, these role(s) are required
            'roles' => [1],
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
            'orderDirection' => 'asc',
            // Order by relation
            'orderRelation' => function ($query) {
                $query->from('transactions')
                    ->select('created_at')
                    ->whereColumn('member_id', 'members.id')
                    ->orderBy('created_at', 'desc')
                    ->limit(1);
            },
            // Custom link for purging ghost members
            'customLink' => [
                'url' => route('admin.members.purge'),
                'label' => trans('common.purge_ghost_members'),
                'icon' => 'user-minus',
                'variant' => 'danger',
            ],
            // Possible actions for the data
            'actions' => [
                'subject_column' => 'name', // This column is used for page titles and delete confirmations
                'list' => true,
                'insert' => false,
                'edit' => true,
                'delete' => true,
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
