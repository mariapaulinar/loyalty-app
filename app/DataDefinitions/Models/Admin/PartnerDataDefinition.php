<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\DataDefinitions\Models\Admin;

use App\DataDefinitions\DataDefinition;
use App\Models\Club;
use App\Models\Network;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PartnerDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'partners';

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
        $this->model = new Partner;

        // Define the fields for the data definition
        $this->fields = [
            'tab1' => [
                'title' => trans('common.general'),
                'fields' => [
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
                    'network_id' => [
                        'text' => trans('common.network'),
                        'highlight' => true,
                        'filter' => true,
                        'type' => 'belongsTo',
                        'relation' => 'network',
                        'relationKey' => 'networks.id',
                        'relationValue' => 'networks.name',
                        'relationModel' => new Network,
                        'relationUserRoleFilter' => [
                            2 => function ($model) {
                                // Get the network associated with the authenticated user
                                $userFilter = auth('admin')->user()->networks;

                                // Add a filter to the query based on the network relationship
                                return $model->query()->whereHas('admins', function ($q) use ($userFilter) {
                                    $q->whereIn('networks.id', $userFilter->pluck('id'));
                                });
                            },
                        ],
                        'validate' => ['required'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'name' => [
                        'text' => trans('common.name'),
                        'type' => 'string',
                        'searchable' => true,
                        'sortable' => true,
                        'validate' => ['required', 'max:120'],
                        'classes::list' => 'md-only:hidden',
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'email' => [
                        'text' => trans('common.email_address'),
                        'type' => 'string',
                        'format' => 'email',
                        'searchable' => true,
                        'sortable' => true,
                        'validate' => ['required', 'email', 'max:120', 'unique:partners,email,:id'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'password' => [
                        'text' => trans('common.password'),
                        'type' => 'password',
                        'generatePasswordButton' => true,
                        'mailUserPassword' => true,
                        'validate' => ['nullable', 'min:6', 'max:48'],
                        'help' => trans('common.new_password_text'),
                        'actions' => ['insert', 'edit'],
                    ],
                    'password::insert' => [
                        'text' => trans('common.password'),
                        'type' => 'password',
                        'generatePasswordButton' => true,
                        'mailUserPassword' => true,
                        'mailUserPasswordChecked' => true,
                        'validate' => ['required', 'min:6', 'max:48'],
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
                    'plan' => [
                        'text' => trans('common.plan'),
                        'type' => 'select',
                        'default' => collect(config('plans'))->filter(fn ($p) => $p['is_default'] ?? false)->keys()->first() ?? 'tier1',
                        'options' => collect(config('plans'))
                            ->filter(fn ($p) => $p['is_active'] ?? true)
                            ->mapWithKeys(fn ($p, $slug) => [$slug => $p['name']])
                            ->toArray(),
                        'sortable' => true,
                        'filter' => true,
                        'validate' => ['required', 'in::option_keys'],
                        'help' => trans('common.plan_help_text'),
                        'classes::list' => 'lg-only:hidden',
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'number_of_times_logged_in' => [
                        'text' => trans('common.logins'),
                        'type' => 'number',
                        'validate' => ['nullable', 'integer'],
                        'classes::list' => 'lg-only:hidden',
                        'actions' => ['view', 'export'],
                    ],
                    'last_login_at' => [
                        'text' => trans('common.last_login'),
                        'type' => 'date_time', // Fixed from previous 'datetime'
                        'validate' => ['nullable', 'date'],
                        'default' => trans('common.never'),
                        'classes::list' => 'lg-only:hidden',
                        'actions' => ['view', 'export'],
                    ],
                    'login_as' => [
                        'text' => trans('common.log_in'),
                        'type' => 'impersonate',
                        'guard' => 'partner',
                        'actions' => ['list', 'edit', 'view'],
                    ],
                    'loyalty_cards' => [
                        'text' => trans('common.loyalty_cards'),
                        'type' => 'number',
                        'default' => '-',
                        'classes::list' => 'lg-only:hidden',
                        'actions' => ['export'],
                        'format' => 'number',
                        'sql' => '(select count(*) from cards where cards.created_by = partners.id)',
                    ],
                    'created_at' => [
                        'text' => trans('common.created'),
                        'type' => 'date_time',
                        'validate' => ['nullable', 'date'],
                        'classes::list' => 'md-only:hidden',
                        'actions' => ['view', 'export'],
                    ],
                    'created_by' => [
                        'text' => trans('common.created_by'),
                        'type' => 'user.admin',
                        'classes::list' => 'md-only:hidden',
                        'actions' => ['view', 'export'],
                    ],
                    'updated_at' => [
                        'text' => trans('common.updated'),
                        'type' => 'date_time',
                        'validate' => ['nullable', 'date'],
                        'classes::list' => 'md-only:hidden',
                        'actions' => ['view', 'export'],
                    ],
                    'updated_by' => [
                        'text' => trans('common.updated_by'),
                        'type' => 'user.admin',
                        'classes::list' => 'md-only:hidden',
                        'actions' => ['view', 'export'],
                    ],
                ]
            ],
            'tab2' => [
                'title' => trans('common.permissions'),
                'fields' => [
                    'cards_on_homepage' => [
                        'text' => trans('common.can_display_cards_on_homepage'),
                        'type' => 'boolean',
                        'json' => 'meta',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon', // Boolean format
                        'default' => false,
                        'help' => trans('common.can_display_cards_on_homepage_text'),
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'loyalty_cards_permission' => [
                        'text' => trans('common.loyalty_cards'),
                        'type' => 'boolean',
                        'json' => 'meta',
                        'validate' => ['nullable', 'boolean'],
                        'default' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'loyalty_cards_limit' => [
                        'text' => trans('common.loyalty_cards') . ' (' . trans('common.limit') . ')',
                        'type' => 'number',
                        'json' => 'meta',
                        'validate' => ['nullable', 'numeric'],
                        'default' => -1,
                        'help' => '-1 is unlimited',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'rewards_limit' => [
                        'text' => trans('common.rewards') . ' (' . trans('common.limit') . ')',
                        'type' => 'number',
                        'json' => 'meta',
                        'validate' => ['nullable', 'numeric'],
                        'default' => -1,
                        'help' => '-1 is unlimited',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'stamp_cards_permission' => [
                        'text' => trans('common.stamp_cards'),
                        'type' => 'boolean',
                        'json' => 'meta',
                        'validate' => ['nullable', 'boolean'],
                        'default' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'stamp_cards_limit' => [
                        'text' => trans('common.stamp_cards') . ' (' . trans('common.limit') . ')',
                        'type' => 'number',
                        'json' => 'meta',
                        'validate' => ['nullable', 'numeric'],
                        'default' => -1,
                        'help' => '-1 is unlimited',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'vouchers_permission' => [
                        'text' => trans('common.vouchers'),
                        'type' => 'boolean',
                        'json' => 'meta',
                        'validate' => ['nullable', 'boolean'],
                        'default' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'voucher_batches_permission' => [
                        'text' => trans('common.voucher_batches'),
                        'type' => 'boolean',
                        'json' => 'meta',
                        'validate' => ['nullable', 'boolean'],
                        'default' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'vouchers_limit' => [
                        'text' => trans('common.vouchers') . ' (' . trans('common.limit') . ')',
                        'type' => 'number',
                        'json' => 'meta',
                        'validate' => ['nullable', 'numeric'],
                        'default' => -1,
                        'help' => '-1 is unlimited',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'staff_members_limit' => [
                        'text' => trans('common.staff_members') . ' (' . trans('common.limit') . ')',
                        'type' => 'number',
                        'json' => 'meta',
                        'validate' => ['nullable', 'numeric'],
                        'default' => -1,
                        'help' => '-1 is unlimited',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'email_campaigns_permission' => [
                        'text' => trans('common.email_campaigns'),
                        'type' => 'boolean',
                        'json' => 'meta',
                        'validate' => ['nullable', 'boolean'],
                        'default' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'activity_permission' => [
                        'text' => trans('common.activity'),
                        'type' => 'boolean',
                        'json' => 'meta',
                        'validate' => ['nullable', 'boolean'],
                        'default' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                ] + (config('default.feature_agent_api') ? [
                    'agent_api_permission' => [
                        'text' => trans('agent.agent_keys'),
                        'type' => 'boolean',
                        'json' => 'meta',
                        'validate' => ['nullable', 'boolean'],
                        'default' => true,
                        'help' => trans('agent.help_agent_api_permission'),
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'agent_keys_limit' => [
                        'text' => trans('agent.agent_keys') . ' (' . trans('common.limit') . ')',
                        'type' => 'number',
                        'json' => 'meta',
                        'validate' => ['nullable', 'numeric'],
                        'default' => 5,
                        'help' => trans('agent.help_agent_keys_limit'),
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                ] : [])
            ],
            'tab3' => [
                'title' => trans('common.localization'),
                'fields' => [
                    'locale' => [
                        'text' => trans('common.language'),
                        'type' => 'locale',
                        'default' => app()->make('i18n')->language->current->locale,
                        'validate' => ['required', 'string', 'size:5'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'time_zone' => [
                        'text' => trans('common.time_zone'),
                        'type' => 'time_zone',
                        'default' => auth('admin')->user()->time_zone,
                        'validate' => ['required', 'string', 'max:48'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'currency' => [
                        'text' => trans('common.currency'),
                        'type' => 'currency',
                        'default' => auth('admin')->user()->currency,
                        'validate' => ['required', 'string', 'size:3'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                ]
            ]
        ];

        // Define the general settings for the data definition
        $this->settings = [
            // Icon
            'icon' => 'store',
            // Title (plural of subject)
            'title' => trans('common.partners'),
            // Override title (this title is shown in every view, without sub title like "Edit item" or "View item")
            'overrideTitle' => null,
            // Guard of user that manages this data (member, staff, partner or admin)
            // This guard is also used for routes and to include the correct Blade layout
            'guard' => 'admin',
            // The guard used for sending the user password
            'mailUserPasswordGuard' => 'partner',
            // Used for updating forms like profile, where user has to enter current password in order to save
            'editRequiresPassword' => false,
            // If true, the visitor is redirected to the edit form
            'redirectListToEdit' => false,
            // This column has to match auth($guard)->user()->id if 'redirectListToEdit' == true (usually it will be 'id' or 'created_by')
            'redirectListToEditColumn' => null,
            // If true, the user id must match the created_by field
            'userMustOwnRecords' => false,
            // Filter with certain role
            'userFilterRole' => [
                2 => function ($model) {
                    // Get the network associated with the authenticated user
                    $userFilter = auth('admin')->user()->networks;

                    // Add a filter to the query based on the network relationship
                    return $model->query()->whereHas('network', function ($q) use ($userFilter) {
                        $q->whereIn('networks.id', $userFilter->pluck('id'));
                    });
                },
            ],
            // Should there be checkboxes for all rows
            'multiSelect' => true,
            // Default items per page for pagination
            'itemsPerPage' => 10,
            // Order by column
            'orderByColumn' => 'id',
            // Order direction, 'asc' or 'desc'
            'orderDirection' => 'desc',
            // Possible actions for the data
            'actions' => [
                'subject_column' => 'name', // This column is used for page titles and delete confirmations
                'list' => true,
                'insert' => true,
                'edit' => true,
                'delete' => true,
                'view' => true,
                'export' => true,
            ],
            // Callback after insert — create default club and derive meta from plan
            'afterInsert' => function ($model) {
                try {
                    // Insert default club record for newly created partner ($model)
                    $data = [
                        'name' => app('translator')->get('common.general', [], $model->locale),
                        'is_active' => true,
                    ];

                    // Create the Club
                    $model->clubs()->create($data);
                } catch (\Exception $e) {
                    report($e);
                    Log::error($e);
                }

                // Derive meta from the assigned plan so limits match config/plans.php.
                // Without this, the CRUD form defaults (e.g. vouchers_permission=true,
                // *_limit=-1) would persist, bypassing plan-based entitlements.
                // Uses HasPlan::syncMetaFromPlan() — single source of truth.
                $model->syncMetaFromPlan();
            },
            // When admin changes the plan tier, re-derive meta from config/plans.php
            // so that plan assignment is immediately effective. Admin overrides
            // applied after derivation take precedence (merge order).
            // Uses HasPlan::syncMetaFromPlan() — single source of truth.
            'afterUpdate' => function ($model) {
                if ($model->wasChanged('plan')) {
                    $model->syncMetaFromPlan();
                }
            },
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
