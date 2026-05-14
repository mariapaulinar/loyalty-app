<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for Partner management of Stamp Cards (digital punch cards).
 * Provides full CRUD interface with live preview, visual design tools, and
 * comprehensive configuration options.
 *
 * Design Tenets:
 * - **Partner Autonomy**: Partners control every aspect of their stamp cards
 * - **Visual Preview**: Live card preview as partners design
 * - **Smart Defaults**: Sensible defaults that can be overridden
 * - **Guided Creation**: AI assistance for content generation
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StampCardDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'stamp-cards';

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
            if (! app(\App\Services\EntitlementService::class)->can(auth('partner')->user(), 'stamp_cards')) {
                abort(403);
            }
        }

        // Set the model
        $this->model = new \App\Models\StampCard;

        // Define the fields for the data definition
        $this->fields = [
            // ═════════════════════════════════════════════════════════════
            // TAB 1: DETAILS - Internal Configuration
            // ═════════════════════════════════════════════════════════════
            // What: Core identity, lifecycle, and visibility settings
            // Why: Following the Universal Form Narrative Pattern™, partners
            //      first define "what this stamp card is" - its club, name,
            //      active period, and discovery settings - before configuring
            //      the mechanics of stamp collection.
            // ═════════════════════════════════════════════════════════════
            'tab1' => [
                'title' => trans('common.details'),
                'fields' => [
                    'club_id' => [
                        'text' => trans('common.club'),
                        'help' => trans('common.stamp_card_club_help'),
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
                        'help' => trans('common.stamp_card_name_help'),
                        'highlight' => true,
                        'searchable' => true,
                        'sortable' => true,
                        'validate' => ['required', 'max:128'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                            'autoFill' => true,
                            'autoFillPrompt' => 'Suggest a catchy internal name for a stamp card loyalty program. Examples: "Coffee Lovers Card", "Beauty VIP Stamps", "Fitness Rewards". Provide only the name without quotes or explanations.',
                            'max_tokens' => 20,
                        ],
                    ],
                    'valid_from' => [
                        'text' => trans('common.valid_from'),
                        'type' => 'string',
                        'format' => 'datetime-local',
                        'default' => Carbon::now(auth('partner')->user()->time_zone)->format('Y-m-d H:i'),
                        'help' => trans('common.stamp_card_valid_from_help'),
                        'validate' => ['nullable', 'date'],
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'valid_until' => [
                        'text' => trans('common.valid_until'),
                        'type' => 'string',
                        'format' => 'datetime-local',
                        'help' => trans('common.stamp_card_valid_until_help'),
                        'validate' => ['nullable', 'date'],
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
                        'help' => trans('common.stamp_card_is_active_help'),
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'is_visible_by_default' => ((auth('partner')->user()->cards_on_homepage ? 1 : 0) == 0) ? null : [
                        'text' => trans('common.visible_on_homepage_for_all_visitors'),
                        'text::list' => trans('common.on_homepage'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'sortable' => true,
                        'default' => false,
                        'help' => trans('common.stamp_card_is_visible_by_default_help'),
                        'classes::list' => 'lg-only:hidden',
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'show_monetary_value' => [
                        'text' => trans('common.show_reward_value_to_members'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'default' => false,
                        'help' => trans('common.stamp_card_show_monetary_value_help'),
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 2: CONTENT - Customer-Facing Copy
            // ═════════════════════════════════════════════════════════════
            // What: Translatable marketing text shown on the stamp card
            // Why: With identity established, partners craft the persuasive
            //      narrative. "Content" is more intuitive than "Card" and
            //      maintains consistency with loyalty cards and rewards.
            //      This is what members read when deciding to join.
            // ═════════════════════════════════════════════════════════════
            'tab2' => [
                'title' => trans('common.content'),
                'fields' => [
                    'title' => [
                        'text' => trans('common.title'),
                        'type' => 'string',
                        'translatable' => true,
                        'searchable' => true,
                        'help' => trans('common.stamp_card_card_title_help'),
                        'validate' => ['required', 'max:128'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                            'autoFill' => true,
                            'autoFillPrompt' => 'Suggest a short, appealing title for a stamp card loyalty program. Be concise and engaging. Examples: "Coffee Rewards", "Beauty Club", "Fitness Stamps". Provide only the title, no quotes.',
                            'max_tokens' => 15,
                        ],
                    ],
                    'description' => [
                        'text' => trans('common.description'),
                        'type' => 'string',
                        'translatable' => true,
                        'searchable' => true,
                        'help' => trans('common.stamp_card_card_description_help'),
                        'validate' => ['required', 'max:250'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                            'autoFill' => true,
                            'autoFillPrompt' => 'Write a short, engaging call-to-action message for a stamp card that members will see. Be conversational and motivating. Examples: "Save for your favorite coffee!", "Your next meal is just 8 stamps away!", "Collect stamps, unlock rewards!". Provide only the message, no quotes.',
                            'max_tokens' => 30,
                        ],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 3: RULES - Stamp Collection Mechanics
            // ═════════════════════════════════════════════════════════════
            // What: Business logic governing stamp earning and expiration
            // Why: After narrative comes mechanics. "Rules" (vs "Stamp Rules")
            //      maintains cross-product consistency while remaining clear.
            //      This tab contains the mathematical engine that powers
            //      stamp accumulation - limits, thresholds, and expiry.
            // ═════════════════════════════════════════════════════════════
            'tab3' => [
                'title' => trans('common.rules'),
                'fields' => [
                    'stamps_required' => [
                        'text' => trans('common.stamps_required'),
                        'type' => 'select',
                        'options' => [
                            5 => trans('common.stamps_option_5'),
                            6 => trans('common.stamps_option_6'),
                            8 => trans('common.stamps_option_8'),
                            9 => trans('common.stamps_option_9'),
                            10 => trans('common.stamps_option_10'),
                            12 => trans('common.stamps_option_12'),
                        ],
                        'default' => 10,
                        'help' => trans('common.stamp_card_stamps_required_help'),
                        'validate' => ['required', 'integer', 'in:5,6,8,9,10,12'],
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'stamps_per_purchase' => [
                        'text' => trans('common.stamps_per_purchase'),
                        'suffix' => trans('common.stamps'),
                        'type' => 'string',
                        'format' => 'number',
                        'default' => 1,
                        'min' => 1,
                        'max' => 10,
                        'step' => 1,
                        'help' => trans('common.stamp_card_stamps_per_purchase_help'),
                        'validate' => ['required', 'integer', 'min:1', 'max:10'],
                        'container_end::insert' => true,
                        'container_end::edit' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'max_stamps_per_day' => [
                        'text' => trans('common.max_stamps_per_day'),
                        'suffix' => trans('common.stamps'),
                        'type' => 'string',
                        'format' => 'number',
                        'min' => 1,
                        'max' => 100,
                        'step' => 1,
                        'help' => trans('common.stamp_card_max_stamps_per_day_help'),
                        'validate' => ['nullable', 'integer', 'min:1', 'max:100'],
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'max_stamps_per_transaction' => [
                        'text' => trans('common.max_per_transaction'),
                        'suffix' => trans('common.stamps'),
                        'type' => 'string',
                        'format' => 'number',
                        'min' => 1,
                        'max' => 50,
                        'step' => 1,
                        'help' => trans('common.stamp_card_max_stamps_per_transaction_help'),
                        'validate' => ['nullable', 'integer', 'min:1', 'max:50'],
                        'container_end::insert' => true,
                        'container_end::edit' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'min_purchase_amount' => [
                        'text' => trans('common.minimum_purchase'),
                        'type' => 'string',
                        'format' => 'number',
                        'suffix' => auth('partner')->user()->currency ?? 'USD',
                        'min' => 0,
                        'step' => 0.01,
                        'help' => trans('common.stamp_card_min_purchase_amount_help'),
                        'validate' => ['nullable', 'numeric', 'min:0'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'stamps_expire_days' => [
                        'text' => trans('common.stamps_expire_after'),
                        'suffix' => trans('common.days'),
                        'type' => 'string',
                        'format' => 'number',
                        'min' => 1,
                        'max' => 365,
                        'step' => 1,
                        'help' => trans('common.stamp_card_stamps_expire_days_help'),
                        'validate' => ['nullable', 'integer', 'min:1', 'max:365'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 4: REWARDS - Completion Benefits
            // ═════════════════════════════════════════════════════════════
            // What: What members receive upon completing the card
            // Why: After defining how to earn, partners configure what members
            //      win. "Rewards" (plural) aligns with loyalty cards and
            //      acknowledges that completion can trigger multiple benefits:
            //      monetary value, bonus points, physical items.
            // ═════════════════════════════════════════════════════════════
            'tab4' => [
                'title' => trans('common.rewards'),
                'fields' => [
                    'reward_value' => [
                        'text' => trans('common.reward_value'),
                        'type' => 'string',
                        'format' => 'number',
                        'min' => 0,
                        'step' => 0.01,
                        'help' => trans('common.stamp_card_reward_value_help'),
                        'validate' => ['nullable', 'numeric', 'min:0'],
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'currency' => [
                        'text' => trans('common.currency'),
                        'type' => 'currency',
                        'default' => auth('partner')->user()->currency,
                        'validate' => ['nullable'],
                        'container_end::insert' => true,
                        'container_end::edit' => true,
                        'actions' => ['view', 'edit', 'insert', 'export'],
                    ],
                    ...((auth('partner')->check() && app(\App\Services\EntitlementService::class)->can(auth('partner')->user(), 'loyalty_cards')) ? [
                    'reward_points' => [
                        'text' => trans('common.also_reward_points'),
                        'suffix' => trans('common.points'),
                        'type' => 'string',
                        'format' => 'number',
                        'min' => 0,
                        'step' => 1,
                        'help' => trans('common.stamp_card_reward_points_help'),
                        'validate' => ['nullable', 'integer', 'min:0'],
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'reward_card_id' => [
                        'text' => trans('common.reward_card'),
                        'help' => trans('common.stamp_card_reward_card_help'),
                        'type' => 'belongsTo',
                        'relation' => 'rewardCard',
                        'relationKey' => 'cards.id',
                        'relationValue' => 'cards.name',
                        'relationModel' => new \App\Models\Card,
                        'relationMustBeOwned' => true,
                        'validate' => ['required_with:reward_points'],
                        'container_end::insert' => true,
                        'container_end::edit' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    ] : []),
                    'requires_physical_claim' => [
                        'text' => trans('common.requires_physical_claim'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'default' => false,
                        'help' => trans('common.requires_physical_claim_help'),
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'reward_title' => [
                        'text' => trans('common.reward_title'),
                        'type' => 'string',
                        'translatable' => true,
                        'help' => trans('common.stamp_card_reward_title_help'),
                        'validate' => ['required', 'max:128'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                            'autoFill' => true,
                            'autoFillPrompt' => 'Suggest an appealing reward title for completing a stamp card. Be specific and exciting. Examples: "Free Premium Coffee", "50% Off Next Service", "Free Dessert". Provide only the title.',
                            'max_tokens' => 20,
                        ],
                    ],
                    'reward_description' => [
                        'text' => trans('common.reward_description'),
                        'type' => 'string',
                        'translatable' => true,
                        'help' => trans('common.stamp_card_reward_description_help'),
                        'validate' => ['nullable', 'max:250'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                        ],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 5: DESIGN - Visual Presentation
            // ═════════════════════════════════════════════════════════════
            // What: Colors, icons, images - the sensory experience
            // Why: The final flourish. After logic and copy, partners make
            //      it beautiful. Stamp-specific visuals (stamp colors, icons)
            //      live here alongside universal design elements (background,
            //      logo) creating a complete aesthetic palette.
            // ═════════════════════════════════════════════════════════════
            'tab5' => [
                'title' => trans('common.design'),
                'fields' => [
                    'bg_color' => [
                        'text' => trans('common.background_color'),
                        'type' => 'string',
                        'format' => 'color',
                        'default' => '#047857',
                        'validate' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
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
                    'stamp_color' => [
                        'text' => trans('common.filled_stamp_color'),
                        'type' => 'string',
                        'format' => 'color',
                        'default' => '#FCD34D',
                        'validate' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
                        'actions' => ['insert', 'edit', 'export'],
                    ],
                    'empty_stamp_color' => [
                        'text' => trans('common.empty_stamp_color'),
                        'type' => 'string',
                        'format' => 'color',
                        'default' => '#D1FAE5',
                        'validate' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
                        'container_end::insert' => true,
                        'container_end::edit' => true,
                        'actions' => ['insert', 'edit', 'export'],
                    ],
                    'stamp_icon' => [
                        'text' => trans('common.stamp_icon'),
                        'type' => 'icon-picker',
                        'default' => '⭐',
                        'help' => trans('common.stamp_icon_help'),
                        'validate' => ['required', 'max:64'],
                        'actions' => ['insert', 'edit', 'export'],
                    ],
                    'background' => [
                        'thumbnail' => 'sm',
                        'conversion' => 'md',
                        'text' => trans('common.background_image'),
                        'help' => trans('common.stamp_card_background_help'),
                        'type' => 'image',
                        'accept' => 'image/png, image/jpeg, image/gif',
                        'validate' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'dimensions:min_width=320,min_height=240,max_width=1920,max_height=1440'],
                        'classes::list' => 'md-only:hidden',
                        'actions' => ['insert', 'edit', 'view'],
                    ],
                    'logo' => [
                        'thumbnail' => 'sm',
                        'conversion' => 'md',
                        'text' => trans('common.logo'),
                        'help' => trans('common.stamp_card_logo_help'),
                        'type' => 'image',
                        'accept' => 'image/png, image/jpeg, image/gif',
                        'validate' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:1024', 'dimensions:min_width=20,min_height=20,max_width=800,max_height=800'],
                        'classes::list' => 'md-only:hidden',
                        'actions' => ['insert', 'edit', 'view'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // SYSTEM FIELDS (non-tab)
            // ═════════════════════════════════════════════════════════════
            'unique_identifier' => [
                'text' => trans('common.identifier'),
                'type' => 'string',
                'help' => trans('common.stamp_card_unique_identifier_help'),
                'actions' => ['view', 'export'],
            ],
            'total_stamps_issued' => [
                'text' => trans('common.stamps_issued'),
                'type' => 'number',
                'help' => trans('common.stamp_card_total_stamps_issued_help'),
                'sortable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
            'total_completions' => [
                'text' => trans('common.completions'),
                'type' => 'number',
                'help' => trans('common.stamp_card_total_completions_help'),
                'sortable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
            'total_redemptions' => [
                'text' => trans('common.redemptions'),
                'type' => 'number',
                'help' => trans('common.stamp_card_total_redemptions_help'),
                'sortable' => true,
                'actions' => ['view', 'export'],
            ],
            'qr' => [
                'text' => trans('common.qr_code'),
                'type' => 'qr',
                'titleColumn' => 'name',
                'url' => route('member.stamp-card', ['stamp_card_id' => ':id']),
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
            // Icon (using ticket icon for stamp cards)
            'icon' => 'stamp',
            // Title (plural of subject)
            'title' => trans('common.stamp_cards'),
            // Override title
            'overrideTitle' => null,
            // Guard of user that manages this data
            'guard' => 'partner',
            // Required role(s)
            'roles' => [1],
            // Requires password for editing
            'editRequiresPassword' => false,
            // Redirect list to edit
            'redirectListToEdit' => false,
            // Redirect column
            'redirectListToEditColumn' => null,
            // User must own records
            'userMustOwnRecords' => true,
            // Multi-select checkboxes
            'multiSelect' => true,
            // Items per page
            'itemsPerPage' => 10,
            // Order by column
            'orderByColumn' => 'created_at',
            // Order direction
            'orderDirection' => 'desc',
            // Possible actions
            'actions' => [
                'subject_column' => 'name',
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
            if (! $entitlements->withinLimit($partner, 'stamp_cards')) {
                $this->settings['actions']['insert'] = false;
                $this->settings['limitReached'] = true;
                $this->settings['limitReachedMessage'] = trans('common.limit_reached_message', [
                    'limit' => $entitlements->limit($partner, 'stamp_cards'),
                    'item' => trans('common.stamp_cards'),
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
