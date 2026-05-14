<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for Partner management of Vouchers (discount codes).
 * Provides full CRUD interface with comprehensive configuration options,
 * AI-powered content generation, and intuitive field organization.
 *
 * Design Tenets:
 * - **Partner Autonomy**: Partners control every aspect of their vouchers
 * - **Smart Defaults**: Sensible defaults that can be overridden
 * - **Guided Creation**: AI assistance for naming and descriptions
 * - **Tabbed Organization**: Logical grouping of related fields
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class VoucherDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'vouchers';

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
            if (! app(\App\Services\EntitlementService::class)->can(auth('partner')->user(), 'vouchers')) {
                abort(403);
            }
        }

        // Set the model
        $this->model = new \App\Models\Voucher;

        // Define the fields for the data definition
        $this->fields = [
            // ═════════════════════════════════════════════════════════════
            // TAB 1: DETAILS - Core Configuration
            // ═════════════════════════════════════════════════════════════
            // What: Identity, code, status flags, behavior rules
            // Why: Pure configuration layer - club assignment, voucher code
            //      (the unique identifier), internal tracking name, visibility
            //      settings, and core behavior rules (stackable, source). This
            //      is "what the voucher IS" from an operational perspective,
            //      separated from "what customers SEE" (moved to Content tab).
            // ═════════════════════════════════════════════════════════════
            'tab1' => [
                'title' => trans('common.details'),
                'fields' => [
                    'club_id' => [
                        'text' => trans('common.club'),
                        'help' => trans('common.voucher_club_help'),
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
                        'text' => trans('common.internal_name'),
                        'type' => 'string',
                        'help' => trans('common.internal_name_help'),
                        'highlight' => true,
                        'searchable' => true,
                        'sortable' => true,
                        'validate' => ['required', 'max:128'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                        'ai' => [
                            'enabled' => true,
                            'autoFill' => true,
                            'autoFillPrompt' => 'Suggest a descriptive internal name for a discount voucher. Examples: "Summer Sale 2025", "Welcome New Members", "VIP Weekend Discount". Provide only the name, no quotes.',
                            'max_tokens' => 20,
                        ],
                    ],
                    'code' => [
                        'text' => trans('common.voucher_code'),
                        'type' => 'string',
                        'help' => trans('common.voucher_code_help'),
                        'searchable' => true,
                        'sortable' => true,
                        'validate' => ['required', 'max:32', 'regex:/^[A-Z0-9\-]+$/'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                        'classes::list' => 'font-mono font-bold',
                        'transform' => 'uppercase',
                    ],
                    'is_active' => [
                        'text' => trans('common.active'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'sortable' => true,
                        'default' => true,
                        'help' => trans('common.voucher_is_active_help'),
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
                        'help' => trans('common.voucher_homepage_help'),
                        'classes::list' => 'lg-only:hidden',
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'stackable' => [
                        'text' => trans('common.stackable'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'default' => false,
                        'help' => trans('common.voucher_stackable_help'),
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 2: CONTENT - Customer-Facing Copy
            // ═════════════════════════════════════════════════════════════
            // What: Translatable marketing text shown to members
            // Why: Separating customer-facing content from internal config
            //      aligns vouchers with the Universal Form Narrative Pattern™.
            //      Title and description are what members SEE - the marketing
            //      layer that makes them want to use the voucher. Both are
            //      translatable, supporting multi-language deployments. This
            //      matches Cards, Stamp Cards, and Rewards structure.
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
                        'validate' => ['required', 'max:255'], // Changed: Required field
                        'actions' => ['insert', 'edit', 'view', 'export'], // Added: Show in list
                        'ai' => [
                            'enabled' => true,
                            'autoFill' => true,
                            'autoFillPrompt' => trans('common.voucher_title_ai_prompt'),
                            'max_tokens' => 15,
                        ],
                    ],
                    'description' => [
                        'text' => trans('common.description'),
                        'type' => 'string',
                        'translatable' => true,
                        'validate' => ['required', 'max:1000'], // Changed: Required field
                        'actions' => ['insert', 'edit', 'view', 'export'], // Added: Show in list
                        'ai' => [
                            'enabled' => true,
                            'autoFill' => true,
                            'autoFillPrompt' => trans('common.voucher_description_ai_prompt'),
                            'max_tokens' => 50,
                        ],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 3: DISCOUNT - Value Mechanics
            // ═════════════════════════════════════════════════════════════
            // What: Type and amount of discount provided
            // Why: "Discount" (not generic "Rules") because it's the defining
            //      characteristic of vouchers. This specificity helps partners
            //      instantly understand the tab's purpose. Flexibility in naming
            //      (unlike rigid "Rules") is appropriate when the domain concept
            //      is more descriptive than the generic pattern.
            // ═════════════════════════════════════════════════════════════
            'tab3' => [
                'title' => trans('common.discount'),
                'fields' => [
                    'type' => [
                        'text' => trans('common.discount_type'),
                        'type' => 'select',
                        'options' => [
                            'percentage' => trans('common.voucher_type_percentage'),
                            'fixed_amount' => trans('common.voucher_type_fixed_amount'),
                            'free_product' => trans('common.voucher_type_free_product'),
                            'bonus_points' => trans('common.voucher_type_bonus_points'),
                        ],
                        'default' => 'percentage',
                        'validate' => ['required'],
                        'help' => trans('common.discount_type_help'),
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'value' => [
                        'text' => trans('common.discount_value'),
                        'type' => 'string',
                        'format' => 'number',
                        'suffix' => '%',
                        'min' => 0,
                        'step' => 0.01,
                        'minorUnits' => 100, // Store as cents: 10.50% → 1050
                        //'help' => trans('common.discount_value_help'),
                        'validate' => ['required_if:type,percentage,fixed_amount', 'nullable', 'numeric', 'min:0'],
                        'default_when_null' => 0,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    // Priority: partner currency → config default
                    // Only relevant for percentage/fixed_amount types
                    'currency' => [
                        'text' => trans('common.currency'),
                        'type' => 'currency',
                        'default' => auth('partner')->user()->currency ?? config('default.currency'),
                        'validate' => ['required_if:type,percentage,fixed_amount', 'nullable'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'max_discount_amount' => [
                        'text' => trans('common.max_discount_cap'),
                        'type' => 'string',
                        'format' => 'number',
                        'suffix' => auth('partner')->user()->currency ?? config('default.currency'),
                        'min' => 0,
                        'step' => 0.01,
                        'minorUnits' => 100, // Store as cents: 10.50 → 1050
                        'help' => trans('common.max_discount_help'),
                        'validate' => ['nullable', 'numeric', 'min:0'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'points_value' => [
                        'text' => trans('common.bonus_points'),
                        'type' => 'string',
                        'format' => 'number',
                        'suffix' => 'points',
                        'min' => 0,
                        'step' => 1,
                        'help' => trans('common.voucher_points_value_help'),
                        'validate' => ['required_if:type,bonus_points', 'nullable', 'integer', 'min:0'],
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'reward_card_id' => [
                        'text' => trans('common.reward_card'),
                        'help' => trans('common.voucher_reward_card_help'),
                        'type' => 'belongsTo',
                        'relation' => 'rewardCard',
                        'relationKey' => 'cards.id',
                        'relationValue' => 'cards.name',
                        'relationModel' => new \App\Models\Card,
                        'relationMustBeOwned' => true,
                        'validate' => ['required_if:type,bonus_points', 'nullable'],
                        'container_end::insert' => true,
                        'container_end::edit' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'free_product_name' => [
                        'text' => trans('common.free_product_name'),
                        'type' => 'string',
                        'translatable' => true,
                        'help' => trans('common.voucher_free_product_help'),
                        'validate' => ['required_if:type,free_product', 'nullable', 'max:256'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 4: CONDITIONS - Usage Rules & Restrictions
            // ═════════════════════════════════════════════════════════════
            // What: Limits, minimums, validity periods
            // Why: "Conditions" (vs "Rules") emphasizes the restrictive nature
            //      of these fields - when/how the discount can be used. The
            //      term mirrors commerce industry standards (terms & conditions)
            //      making it intuitive for retail-focused partners.
            // ═════════════════════════════════════════════════════════════
            'tab4' => [
                'title' => trans('common.conditions'),
                'fields' => [
                    'min_purchase_amount' => [
                        'text' => trans('common.minimum_purchase'),
                        'type' => 'string',
                        'format' => 'number',
                        'suffix' => auth('partner')->user()->currency ?? config('default.currency'),
                        'min' => 0,
                        'step' => 0.01,
                        'minorUnits' => 100, // Store as cents: 25.00 → 2500
                        'help' => trans('common.min_purchase_help'),
                        'validate' => ['nullable', 'numeric', 'min:0'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'max_uses_total' => [
                        'text' => trans('common.total_usage_limit'),
                        'type' => 'string',
                        'format' => 'number',
                        'suffix' => trans('common.total_uses'),
                        'help' => trans('common.max_uses_total_help'),
                        'validate' => ['nullable', 'integer', 'min:1'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'max_uses_per_member' => [
                        'text' => trans('common.per_member_limit'),
                        'type' => 'string',
                        'format' => 'number',
                        'suffix' => trans('common.uses_per_member'),
                        'help' => trans('common.max_uses_per_member_help'),
                        'validate' => ['nullable', 'integer', 'min:1'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'is_single_use' => [
                        'text' => trans('common.single_use_only'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'default' => false,
                        'help' => trans('common.is_single_use_help'),
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'valid_from' => [
                        'text' => trans('common.valid_from'),
                        'type' => 'string',
                        'format' => 'datetime-local',
                        'default' => Carbon::now(auth('partner')->user()->time_zone)->format('Y-m-d H:i'),
                        'help' => trans('common.voucher_valid_from_help'),
                        'validate' => ['nullable', 'date'],
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'valid_until' => [
                        'text' => trans('common.valid_until'),
                        'type' => 'string',
                        'format' => 'datetime-local',
                        'help' => trans('common.voucher_valid_until_help'),
                        'validate' => ['nullable', 'date'],
                        'container_end::insert' => true,
                        'container_end::edit' => true,
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 5: TARGETING - Audience Segmentation
            // ═════════════════════════════════════════════════════════════
            // What: Member filters and first-order restrictions
            // Why: "Targeting" reflects marketing automation best practices.
            //      This tab is about WHO can use the voucher, making it a
            //      segmentation tool. Separating targeting from conditions
            //      mirrors enterprise marketing platforms (Mailchimp, Klaviyo).
            // ═════════════════════════════════════════════════════════════
            'tab5' => [
                'title' => trans('common.targeting'),
                'fields' => [
                    'first_order_only' => [
                        'text' => trans('common.first_order_only'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'default' => false,
                        'help' => trans('common.first_order_only_help'),
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'new_members_only' => [
                        'text' => trans('common.new_members_only'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'default' => false,
                        'help' => trans('common.new_members_only_help'),
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'new_members_days' => [
                        'text' => trans('common.new_member_period'),
                        'type' => 'string',
                        'format' => 'number',
                        'suffix' => trans('common.days'),
                        'default' => 30,
                        'help' => trans('common.new_member_days_help'),
                        'validate' => ['nullable', 'integer', 'min:1'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                        'conditional' => [
                            'field' => 'new_members_only',
                            'values' => [true, 1, '1'],
                        ],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 6: DESIGN - Visual Presentation
            // ═════════════════════════════════════════════════════════════
            // What: Colors, opacity, images - the visual aesthetic layer
            // Why: With behavior defined in Details and mechanics in Discount/
            //      Conditions, this tab focuses purely on making vouchers
            //      visually appealing. Colors and images create brand consistency
            //      and make vouchers recognizable in member wallets. Pure design
            //      concerns, no business logic - aligns with universal pattern.
            // ═════════════════════════════════════════════════════════════
            'tab6' => [
                'title' => trans('common.design'),
                'fields' => [
                    'bg_color' => [
                        'text' => trans('common.background_color'),
                        'type' => 'string',
                        'format' => 'color',
                        'default' => '#7C3AED',
                        'help' => trans('common.voucher_bg_color_help'),
                        'validate' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
                        'container_start::insert' => 'grid grid-cols-2 gap-4',
                        'container_start::edit' => 'grid grid-cols-2 gap-4',
                        'actions' => ['insert', 'edit', 'export'],
                    ],
                    'bg_color_opacity' => [
                        'text' => trans('common.background_color_opacity'),
                        'type' => 'string',
                        'format' => 'range',
                        'default' => 85,
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
                        'help' => trans('common.voucher_text_color_help'),
                        'validate' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
                        'actions' => ['insert', 'edit', 'export'],
                    ],
                    'background' => [
                        'thumbnail' => 'sm',
                        'conversion' => 'md',
                        'text' => trans('common.background_image'),
                        'help' => trans('common.voucher_background_help'),
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
                        'help' => trans('common.voucher_logo_help'),
                        'type' => 'image',
                        'accept' => 'image/png, image/jpeg, image/gif',
                        'validate' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:1024', 'dimensions:min_width=20,min_height=20,max_width=800,max_height=800'],
                        'classes::list' => 'md-only:hidden',
                        'actions' => ['insert', 'edit', 'view'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // SYSTEM FIELDS - Analytics & Metadata (No Tab)
            // ═════════════════════════════════════════════════════════════
            // What: Redemption statistics, impact metrics, audit trail
            // Why: Vouchers generate rich analytics - times_used, discount_given,
            //      unique_members. These fields provide ROI visibility without
            //      cluttering the creation narrative. QR code enables offline
            //      redemption, bridging digital vouchers to physical spaces.
            // ═════════════════════════════════════════════════════════════
            'times_used' => [
                'text' => trans('common.times_used'),
                'type' => 'string',
                'format' => 'number',
                'suffix' => trans('common.redemptions'),
                'help' => trans('common.times_used_help'),
                'actions' => ['view', 'export'],
            ],
            'total_discount_given' => [
                'text' => trans('common.total_discount_given'),
                'type' => 'string',
                'format' => 'currency',
                'help' => trans('common.total_discount_given_help'),
                'actions' => ['view', 'export'],
            ],
            'unique_members_used' => [
                'text' => trans('common.unique_members'),
                'type' => 'string',
                'format' => 'number',
                'suffix' => trans('common.members'),
                'help' => trans('common.unique_members_used_help'),
                'actions' => ['view', 'export'],
            ],
            'qr' => [
                'text' => trans('common.qr_code'),
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
            'updated_at' => [
                'text' => trans('common.last_updated'),
                'type' => 'date_time',
                'actions' => ['view', 'export'],
            ],
        ];

        // Settings
        $this->settings = [
            // Query filter - only show custom/manual vouchers (NOT batch-generated)
            'queryFilter' => function ($query) {
                return $query->whereNull('batch_id');
            },
            // Icon
            'icon' => 'ticket',
            // Title (plural of subject)
            'title' => trans('common.vouchers_title'),
            // Override title
            'overrideTitle' => null,
            // Description
            'description' => trans('common.vouchers_description'),
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
            'multiSelect' => true,
            // Items per page
            'itemsPerPage' => 25,
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
            // Empty list redirect
            'onEmptyListRedirectTo' => null,
            // Custom JavaScript for form interactions
            // Supports: string (all views), or array with keys: 'insert', 'edit', 'list', 'view', 'all', or comma-separated
            'js' => [
                'insert, edit' => "
                    // Helper: Find the outermost field container for an element
                    // Travels up the DOM until we find an element that's a direct child of .space-y-6 (the form grid)
                    const getFieldContainer = (el) => {
                        if (!el) return null;
                        let current = el;
                        while (current && current.parentElement) {
                            // If parent has space-y-6, current is the field container
                            if (current.parentElement.classList?.contains('space-y-6')) {
                                return current;
                            }
                            current = current.parentElement;
                        }
                        return null;
                    };

                    // Type select (4 options, uses native select)
                    const typeSelect = document.querySelector('select[name=\"type\"]');
                    
                    // Currency: Searchable select uses hidden input, find by ID pattern or name
                    const currencyInput = document.querySelector('input[name=\"currency\"]') 
                                       || document.querySelector('select[name=\"currency\"]');

                    // Field containers - use helper function
                    const currencyField = getFieldContainer(currencyInput);
                    const valueField = getFieldContainer(document.querySelector('input[name=\"value\"]'));
                    const maxDiscountField = getFieldContainer(document.querySelector('input[name=\"max_discount_amount\"]'));
                    const minPurchaseField = getFieldContainer(document.querySelector('input[name=\"min_purchase_amount\"]'));
                    const pointsValueField = getFieldContainer(document.querySelector('input[name=\"points_value\"]'));
                    const rewardCardField = getFieldContainer(
                        document.querySelector('input[name=\"reward_card_id\"]') 
                        || document.querySelector('select[name=\"reward_card_id\"]')
                    );
                    const freeProductField = getFieldContainer(document.querySelector('input[name^=\"free_product_name\"]'))
                        || document.querySelector('fieldset:has(input[name^=\"free_product_name\"])');

                    // Suffix elements
                    const valueSuffix = document.getElementById('value_suffix');
                    const maxDiscountSuffix = document.getElementById('max_discount_amount_suffix');
                    const minPurchaseSuffix = document.getElementById('min_purchase_amount_suffix');

                    // Get currency value from either hidden input or native select
                    const getCurrency = () => currencyInput?.value || '';

                    // Show/hide fields based on type
                    const updateFieldVisibility = () => {
                        const type = typeSelect?.value;
                        const isMonetary = ['percentage', 'fixed_amount'].includes(type);
                        const isBonusPoints = type === 'bonus_points';
                        const isFreeProduct = type === 'free_product';

                        // Currency and monetary fields only for percentage/fixed_amount
                        if (currencyField) currencyField.style.display = isMonetary ? '' : 'none';
                        if (valueField) valueField.style.display = isMonetary ? '' : 'none';
                        if (maxDiscountField) maxDiscountField.style.display = isMonetary ? '' : 'none';
                        if (minPurchaseField) minPurchaseField.style.display = isMonetary ? '' : 'none';
                        
                        // Bonus points fields
                        if (pointsValueField) pointsValueField.style.display = isBonusPoints ? '' : 'none';
                        if (rewardCardField) rewardCardField.style.display = isBonusPoints ? '' : 'none';
                        
                        // Free product field
                        if (freeProductField) freeProductField.style.display = isFreeProduct ? '' : 'none';

                        // Update value suffix
                        if (valueSuffix) {
                            valueSuffix.textContent = type === 'fixed_amount' ? getCurrency() : '%';
                        }
                    };

                    // Update currency suffixes
                    const updateCurrencySuffixes = () => {
                        const currency = getCurrency();
                        if (maxDiscountSuffix) maxDiscountSuffix.textContent = currency;
                        if (minPurchaseSuffix) minPurchaseSuffix.textContent = currency;
                        if (typeSelect?.value === 'fixed_amount' && valueSuffix) {
                            valueSuffix.textContent = currency;
                        }
                    };

                    // Voucher code: uppercase only, alphanumeric only
                    const codeInput = document.querySelector('input[name=\"code\"]');
                    if (codeInput) {
                        codeInput.addEventListener('input', function(e) {
                            const start = this.selectionStart;
                            const end = this.selectionEnd;
                            // Transform to uppercase and remove non-alphanumeric characters
                            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                            // Restore cursor position
                            this.setSelectionRange(start, end);
                        });
                    }

                    // Bind events - native select uses 'change' event
                    typeSelect?.addEventListener('change', updateFieldVisibility);
                    
                    // For currency: handle both native select and Alpine searchable (hidden input)
                    if (currencyInput) {
                        if (currencyInput.tagName === 'SELECT') {
                            // Native select
                            currencyInput.addEventListener('change', updateCurrencySuffixes);
                        } else {
                            // Alpine searchable select - use MutationObserver on the hidden input
                            const currencyObserver = new MutationObserver(() => updateCurrencySuffixes());
                            currencyObserver.observe(currencyInput, { attributes: true, attributeFilter: ['value'] });
                            // Also listen for input events (Alpine triggers these)
                            currencyInput.addEventListener('input', updateCurrencySuffixes);
                        }
                    }

                    // Initialize
                    updateFieldVisibility();
                    updateCurrencySuffixes();
                ",
            ],
        ];

        // Check for limits via EntitlementService
        if (auth('partner')->check()) {
            $entitlements = app(\App\Services\EntitlementService::class);
            $partner = auth('partner')->user();
            if (! $entitlements->withinLimit($partner, 'vouchers')) {
                $this->settings['actions']['insert'] = false;
                $this->settings['limitReached'] = true;
                $this->settings['limitReachedMessage'] = trans('common.limit_reached_message', [
                    'limit' => $entitlements->limit($partner, 'vouchers'),
                    'item' => trans('common.vouchers'),
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
