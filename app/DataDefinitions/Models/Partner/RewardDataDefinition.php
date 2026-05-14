<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for Partner management of Rewards (points-redeemable items).
 * Members exchange loyalty points for these rewards, creating the incentive
 * that drives the entire loyalty ecosystem.
 *
 * Design Tenets:
 * - **Progressive Disclosure**: Universal Form Narrative Pattern™
 * - **Simplicity First**: Rewards are simpler than cards, tabs reflect this
 * - **Visual Priority**: Images critical to reward appeal, hence dedicated tab
 * - **Consistency**: Same 4-act structure (Details → Rules → Content → Design)
 *
 * Architecture Notes:
 * Rewards differ from cards in that they are simpler (no complex mechanics)
 * but more visual (images drive redemption decisions). The tab structure
 * honors both: streamlined rules, expanded imagery.
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class RewardDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'rewards';

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
        // Permission check via EntitlementService (Rewards depend on Loyalty Cards feature)
        if (auth('partner')->check()) {
            if (! app(\App\Services\EntitlementService::class)->can(auth('partner')->user(), 'loyalty_cards')) {
                abort(403);
            }
        }

        // Set the model
        $this->model = new \App\Models\Reward;

        // Define the fields for the data definition
        $this->fields = [
            // ═════════════════════════════════════════════════════════════
            // TAB 1: DETAILS - Core Configuration
            // ═════════════════════════════════════════════════════════════
            // What: Identity, cost (in points), and lifecycle
            // Why: Partners first define what the reward is (name), its cost
            //      in the points economy, and when it's available. This
            //      establishes the reward's position in the loyalty hierarchy.
            // ═════════════════════════════════════════════════════════════
            'tab1' => [
                'title' => trans('common.details'),
                'fields' => [
                    'name' => [
                        'text' => trans('common.name'),
                        'help' => trans('common.reward_name_text'),
                        'type' => 'string',
                        'highlight' => true,
                        'searchable' => true,
                        'sortable' => true,
                        'validate' => ['required', 'max:120'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                            'autoFill' => false,
                            'autoFillPrompt' => 'Suggest a short name for a reward of a loyalty card, this can be a discount, a free item, etc.. Use a name with separate words. Provide only one name, without any introductory phrases, quotation marks, or additional comments.',
                        ],
                    ],
                    'points' => [
                        'text' => trans('common.points_required'),
                        'help' => trans('common.reward_points_text'),
                        'type' => 'string',
                        'format' => 'number',
                        'sortable' => true,
                        'default' => null,
                        'min' => 0,
                        'max' => 10000000,
                        'step' => 1,
                        'validate' => ['required', 'numeric', 'min:0', 'max:10000000'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'active_from' => [
                        'text' => trans('common.active_from'),
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
                        'sortable' => true,
                        'default' => Carbon::now(auth('partner')->user()->time_zone)->addYears(1)->format('Y-m-d H:i'),
                        'validate' => ['required', 'date'],
                        'container_end::insert' => true,
                        'container_end::edit' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'is_active' => [
                        'text' => trans('common.active'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'sortable' => true,
                        'default' => true,
                        'help' => trans('common.reward_is_active_text'),
                        'classes::list' => 'lg-only:hidden',
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 2: CONTENT - Customer-Facing Copy
            // ═════════════════════════════════════════════════════════════
            // What: Translatable marketing text that sells the reward
            // Why: With cost established, partners write persuasive copy that
            //      makes members want to save for this reward. Title and
            //      description work together to create desire.
            // ═════════════════════════════════════════════════════════════
            'tab2' => [
                'title' => trans('common.content'),
                'fields' => [
                    'title' => [
                        'text' => trans('common.title'),
                        'type' => 'string',
                        'translatable' => true,
                        'searchable' => true,
                        'validate' => ['required', 'max:120'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                        ],
                    ],
                    'description' => [
                        'text' => trans('common.description'),
                        'type' => 'textarea',
                        'translatable' => true,
                        'validate' => ['nullable', 'max:800'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                        ],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 3: DESIGN - Visual Presentation
            // ═════════════════════════════════════════════════════════════
            // What: Product imagery showcasing the reward
            // Why: Rewards are inherently visual - members redeem based on
            //      what they SEE. Three image slots allow partners to show
            //      the reward from multiple angles, lifestyle contexts, or
            //      variants. This tab IS the sales floor display case.
            // ═════════════════════════════════════════════════════════════
            'tab3' => [
                'title' => trans('common.design'),
                'fields' => [
                    'image1' => [
                        'thumbnail' => 'xs', // Image conversion used for list
                        'conversion' => 'md', // Image conversion used for view/edit
                        'text' => trans('common.main_image'),
                        'type' => 'image',
                        'accept' => 'image/png, image/jpeg, image/gif',
                        'validate' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'dimensions:min_width=320,min_height=240,max_width=1920,max_height=1440'],
                        'classes::list' => 'md-only:hidden',
                        'actions' => ['list', 'insert', 'edit', 'view'],
                    ],
                    'image2' => [
                        'thumbnail' => 'xs', // Image conversion used for list
                        'conversion' => 'md', // Image conversion used for view/edit
                        'text' => trans('common.image_no', ['number' => 2]),
                        'type' => 'image',
                        'accept' => 'image/png, image/jpeg, image/gif',
                        'validate' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'dimensions:min_width=320,min_height=240,max_width=1920,max_height=1440'],
                        'actions' => ['insert', 'edit', 'view'],
                    ],
                    'image3' => [
                        'thumbnail' => 'xs', // Image conversion used for list
                        'conversion' => 'md', // Image conversion used for view/edit
                        'text' => trans('common.image_no', ['number' => 3]),
                        'type' => 'image',
                        'accept' => 'image/png, image/jpeg, image/gif',
                        'validate' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'dimensions:min_width=320,min_height=240,max_width=1920,max_height=1440'],
                        'actions' => ['insert', 'edit', 'view'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // SYSTEM FIELDS - Analytics & Metadata (No Tab)
            // ═════════════════════════════════════════════════════════════
            // What: System-generated engagement and audit data
            // Why: Provides insight into reward appeal and lifecycle history
            //      without cluttering the creation experience.
            // ═════════════════════════════════════════════════════════════
            'views' => [
                'text' => trans('common.views'),
                'type' => 'number',
                'sortable' => true,
                'actions' => ['list', 'view', 'export'],
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

        // ═════════════════════════════════════════════════════════════════
        // TAB 4: E-COMMERCE - Online Store Integration Settings (Alpha)
        // ═════════════════════════════════════════════════════════════════
        // What: Configuration for auto-applying rewards in Shopify,
        //       WooCommerce, Magento, and other e-commerce platforms.
        // Why: Partners configure discount details HERE, not in each
        //      integration dashboard. Single source of truth.
        //
        // Visibility: Only shown when FEATURE_SHOPIFY is enabled.
        // This is an alpha feature — not yet ready for general availability.
        // ═════════════════════════════════════════════════════════════════
        if (config('default.feature_shopify')) {
            $this->fields['tab4'] = [
                'title' => trans('common.ecommerce'),
                'fields' => [
                    'ecommerce_settings.shopify.enabled' => [
                        'text' => trans('common.enable_shopify_discount'),
                        'help' => trans('common.enable_shopify_discount_help'),
                        'type' => 'boolean',
                        'default' => false,
                        'validate' => ['nullable', 'boolean'],
                        'actions' => ['insert', 'edit', 'view'],
                    ],
                    'ecommerce_settings.shopify.discount_type' => [
                        'text' => trans('common.shopify_discount_type'),
                        'help' => trans('common.shopify_discount_type_help'),
                        'type' => 'select',
                        'options' => [
                            'percentage' => trans('common.percentage_off'),
                            'fixed_amount' => trans('common.fixed_amount_off'),
                            'free_shipping' => trans('common.free_shipping'),
                        ],
                        'default' => 'percentage',
                        'validate' => ['nullable', 'in:percentage,fixed_amount,free_shipping'],
                        'actions' => ['insert', 'edit', 'view'],
                    ],
                    'ecommerce_settings.shopify.discount_value' => [
                        'text' => trans('common.shopify_discount_value'),
                        'help' => trans('common.shopify_discount_value_help'),
                        'type' => 'string',
                        'format' => 'number',
                        'min' => 0,
                        'max' => 10000,
                        'step' => 1,
                        'default' => 10,
                        'validate' => ['nullable', 'numeric', 'min:0', 'max:10000'],
                        'actions' => ['insert', 'edit', 'view'],
                    ],
                    'ecommerce_settings.shopify.discount_code_prefix' => [
                        'text' => trans('common.shopify_code_prefix'),
                        'help' => trans('common.shopify_code_prefix_help'),
                        'type' => 'string',
                        'default' => 'REWARD',
                        'validate' => ['nullable', 'max:20', 'alpha_dash'],
                        'actions' => ['insert', 'edit', 'view'],
                    ],
                    'ecommerce_settings.shopify.use_automatic_discount' => [
                        'text' => trans('common.use_automatic_discount'),
                        'help' => trans('common.use_automatic_discount_help'),
                        'type' => 'boolean',
                        'default' => true,
                        'validate' => ['nullable', 'boolean'],
                        'actions' => ['insert', 'edit', 'view'],
                    ],
                ],
            ];
        }

        // Define the general settings for the data definition
        $this->settings = [
            // Icon
            'icon' => 'gift',
            // Title (plural of subject)
            'title' => trans('common.rewards'),
            // Override title (this title is shown in every view, without sub title like "Edit item" or "View item")
            'overrideTitle' => null,
            // Description
            'description' => trans('common.partner_rewards_description'),
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
            'itemsPerPage' => 20,
            // Order by column
            'orderByColumn' => 'points',
            // Order direction, 'asc' or 'desc'
            'orderDirection' => 'asc',
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
            if (! $entitlements->withinLimit($partner, 'rewards')) {
                $this->settings['actions']['insert'] = false;
                $this->settings['limitReached'] = true;
                $this->settings['limitReachedMessage'] = trans('common.limit_reached_message', [
                    'limit' => $entitlements->limit($partner, 'rewards'),
                    'item' => trans('common.rewards'),
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
