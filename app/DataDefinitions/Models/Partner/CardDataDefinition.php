<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for Partner management of Loyalty Cards (points-based rewards).
 * Provides full CRUD interface with intelligent field organization following
 * the Universal Form Narrative Pattern™.
 *
 * Design Tenets:
 * - **Progressive Disclosure**: Information revealed in natural cognitive order
 * - **Narrative Flow**: Each tab tells part of the story (What → How → Say → Show)
 * - **Consistency First**: Same tab structure across all campaign types
 * - **Partner Autonomy**: Full control without overwhelming complexity
 *
 * Tab Architecture Philosophy:
 * Tab 1 (Details):   Internal configuration - "What is this thing?"
 * Tab 2 (Rules):      Business mechanics - "How does it work?"
 * Tab 3 (Content):    Customer-facing copy - "What do people see?"
 * Tab 4 (Design):     Visual presentation - "How does it look?"
 *
 * This pattern mirrors how partners think: first define what you're creating,
 * then configure its rules, write the copy, and finally make it beautiful.
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CardDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'cards';

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
            if (! app(\App\Services\EntitlementService::class)->can(auth('partner')->user(), 'loyalty_cards')) {
                abort(403);
            }
        }

        // Set the model
        $this->model = new \App\Models\Card;

        // Define the fields for the data definition
        $this->fields = [
            // ═════════════════════════════════════════════════════════════
            // TAB 1: DETAILS - Internal Configuration
            // ═════════════════════════════════════════════════════════════
            // What: Core identity and lifecycle settings
            // Why: Partners need to establish "what this card is" before
            //      configuring mechanics. Club assignment comes first because
            //      it affects permissions and display context.
            // ═════════════════════════════════════════════════════════════
            'tab1' => [
                'title' => trans('common.details'),
                'fields' => [
                    'club_id' => [
                        'text' => trans('common.club'),
                        'help' => trans('common.card_club_text'),
                        'classes::list' => 'md-only:hidden',
                        'highlight' => true,
                        'filter' => true,
                        'type' => 'belongsTo',
                        'relation' => 'club',
                        'relationKey' => 'clubs.id',
                        'relationValue' => 'clubs.name',
                        'relationModel' => new \App\Models\Club,
                        'relationMustBeOwned' => true,
                        'validate' => ['required'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'name' => [
                        'text' => trans('common.name'),
                        'type' => 'string',
                        'help' => trans('common.card_name_text'),
                        'highlight' => true,
                        'searchable' => true,
                        'sortable' => true,
                        'validate' => ['required', 'max:250'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                            'autoFill' => true,
                            'autoFillPrompt' => 'Suggest a catchy name (used for internal use, not public) for a customer loyalty card. Provide only the name, use names with separate words, without any introductory phrases, quotation marks, or additional comments.',
                            'max_tokens' => 30,
                        ],
                    ],
                    'issue_date' => [
                        'text' => trans('common.issue_date'),
                        'type' => 'string',
                        'format' => 'datetime-local',
                        'default' => Carbon::now(auth('partner')->user()->time_zone)->format('Y-m-d H:i'),
                        'validate' => ['required', 'date'],
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'expiration_date' => [
                        'text' => trans('common.expiration_date'),
                        'type' => 'string',
                        'format' => 'datetime-local',
                        'default' => Carbon::now(auth('partner')->user()->time_zone)->addYears(4)->format('Y-m-d H:i'),
                        'validate' => ['required', 'date'],
                        'container_end::insert' => true,
                        'container_end::edit' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'rewards' => [
                        'text' => trans('common.rewards'),
                        'help' => trans('common.card_rewards_text'),
                        'type' => 'belongsToMany',
                        'relation' => 'rewards',
                        'relationKey' => 'rewards.id',
                        'relationValue' => 'rewards.name',
                        'relationModel' => new \App\Models\Reward,
                        'relationMustBeOwned' => true,
                        'validate' => ['nullable'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'is_active' => [
                        'text' => trans('common.active'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'sortable' => true,
                        'default' => true,
                        'help' => trans('common.card_is_active_text'),
                        'classes::list' => 'lg-only:hidden',
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'is_visible_by_default' => (auth('partner')->user()->cards_on_homepage) ? [
                        'text' => trans('common.visible_on_homepage_for_all_visitors'),
                        'text::list' => trans('common.on_homepage'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'sortable' => true,
                        'default' => false,
                        'help' => trans('common.card_is_visible_by_default'),
                        'classes::list' => 'lg-only:hidden',
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ] : null,
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 2: RULES - Business Mechanics
            // ═════════════════════════════════════════════════════════════
            // What: Points earning logic, currency conversion, limits
            // Why: After defining identity, partners configure the mathematics
            //      that governs how members earn and spend points. This is the
            //      "engine" that powers the loyalty experience.
            // ═════════════════════════════════════════════════════════════
            'tab2' => [
                'title' => trans('common.rules'),
                'fields' => [
                    'initial_bonus_points' => [
                        'text' => trans('common.initial_bonus_points'),
                        'help' => trans('common.card_initial_bonus_points_text'),
                        'type' => 'string',
                        'format' => 'number',
                        'default' => 0,
                        'min' => 0,
                        'max' => 10000000,
                        'step' => 1,
                        'validate' => ['required', 'numeric', 'min:0', 'max:10000000'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'points_expiration_months' => [
                        'text' => trans('common.points_expiration_months'),
                        'suffix' => strtolower(trans('common.months')),
                        'type' => 'string',
                        'format' => 'number',
                        'default' => 12,
                        'min' => 1,
                        'max' => 1200,
                        'step' => 1,
                        'validate' => ['required', 'numeric', 'min:1', 'max:1200'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'currency_unit_amount' => [
                        'text' => trans('common.for'),
                        'type' => 'string',
                        'format' => 'number',
                        'default' => 1,
                        'min' => 1,
                        'max' => 1000000,
                        'step' => 1,
                        'container_start::insert' => 'grid grid-cols-3 gap-4',
                        'container_start::edit' => 'grid grid-cols-3 gap-4',
                        'validate' => ['required', 'numeric', 'min:1', 'max:1000000'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'currency' => [
                        'text' => trans('common.currency'),
                        'type' => 'currency',
                        'default' => auth('partner')->user()->currency,
                        'validate' => ['required'],
                        'actions' => ['view', 'edit', 'insert', 'export'],
                    ],
                    'points_per_currency' => [
                        'text' => trans('common.points_per_currency'),
                        'suffix' => strtolower(trans('common.points')),
                        'type' => 'string',
                        'format' => 'number',
                        'default' => 100,
                        'min' => 0,
                        'max' => 100000,
                        'step' => 1,
                        'container_end::insert' => true,
                        'container_end::edit' => true,
                        'validate' => ['required', 'numeric', 'min:0', 'max:100000'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'round_points_up' => [
                        'text' => trans('common.round_points_up'),
                        'type' => 'boolean',
                        'json' => 'meta',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'sortable' => true,
                        'default' => true,
                        'help' => trans('common.round_points_up_text'),
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'min_points_per_purchase' => [
                        'text' => trans('common.min_points_per_purchase'),
                        'suffix' => strtolower(trans('common.points')),
                        'help' => trans('common.min_points_per_purchase_text'),
                        'type' => 'string',
                        'format' => 'number',
                        'default' => 1,
                        'min' => 0,
                        'max' => 10000000,
                        'step' => 1,
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
                        'validate' => ['required', 'numeric', 'min:0', 'max:10000000'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'max_points_per_purchase' => [
                        'text' => trans('common.max_points_per_purchase'),
                        'suffix' => strtolower(trans('common.points')),
                        'help' => trans('common.max_points_per_purchase_text'),
                        'type' => 'string',
                        'format' => 'number',
                        'default' => 100000,
                        'min' => 0,
                        'max' => 10000000,
                        'step' => 1,
                        'container_end::insert' => true,
                        'container_end::edit' => true,
                        'validate' => ['required', 'numeric', 'min:0', 'max:10000000'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ], /*
                  'point_value' => [
                      'text' => trans('common.point_value'),
                      'suffix' => trans('common.points'),
                      'help' => trans('common.point_value_text'),
                      'type' => 'string',
                      'format' => 'number',
                      'default' => 0,
                      'min' => 0,
                      'max' => 10000000,
                      'step' => 1,
                      'validate' => ['required', 'numeric', 'min:0', 'max:10000000'],
                      'actions' => ['insert', 'edit', 'view', 'export'],
                  ],*/
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 3: CONTENT - Customer-Facing Copy
            // ═════════════════════════════════════════════════════════════
            // What: Translatable marketing text displayed to members
            // Why: With mechanics defined, partners craft the narrative that
            //      sells the program. This is the "voice" of the loyalty card.
            //      All translatable fields grouped together for efficient
            //      multi-language content management.
            // ═════════════════════════════════════════════════════════════
            'tab3' => [
                'title' => trans('common.content'),
                'fields' => [
                    'head' => [
                        'text' => trans('common.head'),
                        'type' => 'string',
                        'translatable' => true,
                        'searchable' => true,
                        'sortable' => true,
                        'validate' => ['required', 'max:250'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                        ],
                    ],
                    'title' => [
                        'text' => trans('common.title'),
                        'type' => 'string',
                        'translatable' => true,
                        'searchable' => true,
                        'sortable' => true,
                        'validate' => ['nullable', 'max:250'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                        ],
                    ],
                    'description' => [
                        'text' => trans('common.description'),
                        'type' => 'string',
                        'translatable' => true,
                        'searchable' => true,
                        'validate' => ['nullable', 'max:250'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                        ],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 4: DESIGN - Visual Presentation & Contact
            // ═════════════════════════════════════════════════════════════
            // What: Colors, images, branding, contact information
            // Why: The final act - making it beautiful. Contact info lives
            //      here because it's part of how the card is "presented" to
            //      customers, not a business rule. This tab is about the
            //      sensory experience: what members see, touch, and interact with.
            // ═════════════════════════════════════════════════════════════
            'tab4' => [
                'title' => trans('common.design'),
                'fields' => [
                    'bg_color' => [
                        'text' => trans('common.background_color'),
                        'type' => 'string',
                        'format' => 'color',
                        'default' => '#1E293B',
                        'validate' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
                        'classes::insert' => '',
                        'actions' => ['insert', 'edit', 'export'],
                    ],
                    'bg_color_opacity' => [
                        'text' => trans('common.background_color_opacity'),
                        'type' => 'string',
                        'format' => 'range',
                        'default' => 75,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'validate' => ['required', 'numeric', 'min:0', 'max:100'],
                        'classes::insert' => null,
                        'container_end::insert' => true,
                        'container_end::edit' => true,
                        'actions' => ['insert', 'edit', 'export'],
                    ],
                    'text_color' => [
                        'text' => trans('common.text_color'),
                        'type' => 'string',
                        'format' => 'color',
                        'default' => '#FFFFFF',
                        'validate' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
                        'actions' => ['insert', 'edit', 'export'],
                    ],
                    'background' => [
                        'thumbnail' => 'sm', // Image conversion used for list
                        'conversion' => 'md', // Image conversion used for view/edit
                        'text' => trans('common.background_image'),
                        'help' => trans('common.card_background_image'),
                        'type' => 'image',
                        'accept' => 'image/png, image/jpeg, image/gif',
                        'validate' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:1024', 'dimensions:min_width=320,min_height=240,max_width=1920,max_height=1440'],
                        'classes::list' => 'md-only:hidden',
                        'actions' => ['insert', 'edit'],
                    ],
                    'logo' => [
                        'thumbnail' => 'sm', // Image conversion used for list
                        'conversion' => 'md', // Image conversion used for view/edit
                        'text' => trans('common.logo'),
                        'type' => 'image',
                        'accept' => 'image/png, image/jpeg, image/gif',
                        'validate' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:1024', 'dimensions:min_width=20,min_height=20,max_width=1920,max_height=1440'],
                        'classes::list' => 'md-only:hidden',
                        'actions' => ['insert', 'edit'],
                    ],
                    'website' => [
                        'text' => trans('common.website'),
                        'json' => 'meta',
                        'type' => 'string',
                        'placeholder' => 'https://',
                        'searchable' => true,
                        'validate' => ['nullable', 'max:160'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'route' => [
                        'text' => trans('common.route'),
                        'json' => 'meta',
                        'type' => 'string',
                        'placeholder' => 'https://goo.gl/maps/xxxxxxxxxxxx',
                        'searchable' => true,
                        'validate' => ['nullable', 'max:240'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'phone' => [
                        'text' => trans('common.phone_number'),
                        'json' => 'meta',
                        'type' => 'string',
                        'placeholder' => '+XX',
                        'searchable' => true,
                        'validate' => ['nullable', 'max:40'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // SYSTEM FIELDS - Analytics & Metadata (No Tab)
            // ═════════════════════════════════════════════════════════════
            // What: System-generated data, QR codes, timestamps
            // Why: Read-only fields that provide insight but aren't part of
            //      the creation narrative. Displayed in list/view only.
            // ═════════════════════════════════════════════════════════════
            'unique_identifier' => [
                'text' => trans('common.identifier'),
                'type' => 'string',
                'actions' => ['view', 'export'],
            ],
            'views' => [
                'text' => trans('common.views'),
                'type' => 'number',
                'sortable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
            'qr' => [
                'text' => trans('common.view'),
                'type' => 'qr',
                'titleColumn' => 'name',
                'url' => route('member.card', ['card_id' => ':id']),
                'actions' => ['list', 'view'],
            ],
            'created_at' => [
                'text' => trans('common.created'),
                'type' => 'date_time',
                'actions' => ['view', 'export'],
            ],
            'created_by' => [
                'text' => trans('common.created_by'),
                'type' => 'user.partner',
                'actions' => ['view', 'export'],
            ],
            'updated_at' => [
                'text' => trans('common.updated'),
                'type' => 'date_time',
                'actions' => ['view', 'export'],
            ],
            'updated_by' => [
                'text' => trans('common.updated_by'),
                'type' => 'user.partner',
                'actions' => ['view', 'export'],
            ],
        ];

        // Define the general settings for the data definition
        $this->settings = [
            // Icon
            'icon' => 'credit-card',
            // Title (plural of subject)
            'title' => trans('common.loyalty_cards'),
            // Override title (this title is shown in every view, without sub title like "Edit item" or "View item")
            'overrideTitle' => null,
            // Description
            'description' => null,
            // Guard of user that manages this data (member, staff, partner or admin)
            // This guard is also used for routes and to include the correct Blade layout
            'guard' => 'partner',
            // If set, these role(s) are required
            'roles' => [1],
            // Used for updating forms like profile, where user has to enter current password in order to save
            'editRequiresPassword' => false,
            // If true, the visitor is redirected to the edit form
            'redirectListToEdit' => false,
            // This column has to match auth($guard)->user()->id if 'redirectListToEdit' == true (usually it will be 'id' or 'created_by')
            'redirectListToEditColumn' => null,
            // If true, the user id must match the created_by field
            'userMustOwnRecords' => true,
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
        ];
        // Check for limits via EntitlementService
        if (auth('partner')->check()) {
            $entitlements = app(\App\Services\EntitlementService::class);
            $partner = auth('partner')->user();
            if (! $entitlements->withinLimit($partner, 'cards')) {
                $this->settings['actions']['insert'] = false;
                $this->settings['limitReached'] = true;
                $this->settings['limitReachedMessage'] = trans('common.limit_reached_message', [
                    'limit' => $entitlements->limit($partner, 'cards'),
                    'item' => trans('common.loyalty_cards'),
                ]);
            }
        }
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
