<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for Partner management of Membership Tiers (status levels).
 * Tiers create gamification layers where members unlock escalating benefits
 * as they engage more deeply with the loyalty program.
 *
 * Design Tenets:
 * - **Progressive Disclosure**: Universal Form Narrative Pattern™
 * - **Gamification Engine**: Tiers power status-based motivation
 * - **Automatic Progression**: Members advance based on objective thresholds
 * - **Benefit Stacking**: Each tier adds to (not replaces) base program
 *
 * Architecture Notes:
 * Tiers differ from other campaign types in their relational nature - they
 * modify how OTHER features work (point multipliers, redemption discounts).
 * The 4-tab structure reflects this: identity, entry requirements, benefits
 * granted, and visual status symbols.
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

class TierDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'tiers';

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
        // Set the model
        $this->model = new \App\Models\Tier;

        // Define the fields for the data definition
        $this->fields = [
            // ═════════════════════════════════════════════════════════════
            // TAB 1: DETAILS - Identity & Status Configuration
            // ═════════════════════════════════════════════════════════════
            // What: Tier identity, level hierarchy, and default/active status
            // Why: Tiers exist in a hierarchy - partners first define where
            //      this tier sits in the status ladder (level), what it's
            //      called (name/display_name), and whether it's the starting
            //      point (is_default) for new members.
            // ═════════════════════════════════════════════════════════════
            'tab1' => [
                'title' => trans('common.details'),
                'fields' => [
                    'club_id' => [
                        'text' => trans('common.club'),
                        'help' => trans('common.tier_club_text'),
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
                        'help' => trans('common.tier_name_text'),
                        'highlight' => true,
                        'searchable' => true,
                        'sortable' => true,
                        'validate' => ['required', 'max:64'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'display_name' => [
                        'text' => trans('common.display_name'),
                        'type' => 'string',
                        'translatable' => true,
                        'help' => trans('common.tier_display_name_text'),
                        'searchable' => true,
                        'validate' => ['nullable', 'max:64'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'description' => [
                        'text' => trans('common.description'),
                        'type' => 'string',
                        'translatable' => true,
                        'help' => trans('common.tier_description_text'),
                        'validate' => ['nullable', 'max:500'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'level' => [
                        'text' => trans('common.level'),
                        'type' => 'string',
                        'format' => 'number',
                        'help' => trans('common.tier_level_text'),
                        'default' => 0,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'sortable' => true,
                        'validate' => ['required', 'integer', 'min:0', 'max:100'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'is_default' => [
                        'text' => trans('common.default_tier'),
                        'text::list' => trans('common.default'),
                        'type' => 'boolean',
                        'format' => 'icon',
                        'sortable' => true,
                        'actions' => ['list', 'view'],
                    ],
                    'is_active' => [
                        'text' => trans('common.active'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'sortable' => true,
                        'default' => true,
                        'help' => trans('common.tier_is_active_text'),
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 2: QUALIFICATION - Entry Requirements
            // ═════════════════════════════════════════════════════════════
            // What: Thresholds members must cross to reach this tier
            // Why: After defining identity, partners set the bar. Qualification
            //      rules determine who "earns" this status. Multiple threshold
            //      types (points, spend, transactions) allow flexible tier
            //      progression strategies - reward volume, value, or frequency.
            // ═════════════════════════════════════════════════════════════
            'tab2' => [
                'title' => trans('common.qualification'),
                'fields' => [
                    'points_threshold' => [
                        'text' => trans('common.points_threshold'),
                        'suffix' => strtolower(trans('common.points')),
                        'type' => 'string',
                        'format' => 'number',
                        'help' => trans('common.tier_points_threshold_text'),
                        'default' => null,
                        'min' => 0,
                        'max' => 100000000,
                        'step' => 1,
                        'validate' => ['nullable', 'integer', 'min:0', 'max:100000000'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'spend_threshold' => [
                        'text' => trans('common.spend_threshold'),
                        'type' => 'string',
                        'format' => 'number',
                        'suffix' => auth('partner')->user()->currency ?? 'USD',
                        'help' => trans('common.tier_spend_threshold_text'),
                        'default' => null,
                        'min' => 0,
                        'max' => 100000000,
                        'step' => 0.01,
                        'minorUnits' => 100, // Store as cents: 500.00 → 50000
                        'validate' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                    'transactions_threshold' => [
                        'text' => trans('common.transactions_threshold'),
                        'type' => 'string',
                        'format' => 'number',
                        'help' => trans('common.tier_transactions_threshold_text'),
                        'default' => null,
                        'min' => 0,
                        'max' => 100000,
                        'step' => 1,
                        'validate' => ['nullable', 'integer', 'min:0', 'max:100000'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 3: BENEFITS - Member Advantages
            // ═════════════════════════════════════════════════════════════
            // What: Modifiers that enhance the base loyalty program
            // Why: This is the "why bother" tab - what members GET for
            //      reaching this tier. Benefits are multiplicative (earn more
            //      points) or discounted (spend fewer points), making higher
            //      tiers exponentially more valuable and driving engagement.
            // ═════════════════════════════════════════════════════════════
            'tab3' => [
                'title' => trans('common.benefits'),
                'fields' => [
                    'points_multiplier' => [
                        'text' => trans('common.points_multiplier'),
                        'text::list' => trans('common.multiplier'),
                        'suffix' => 'x',
                        'type' => 'string',
                        'format' => 'number',
                        'help' => trans('common.tier_points_multiplier_text'),
                        'default' => 1.00,
                        'min' => 0.1,
                        'max' => 10,
                        'step' => 0.01,
                        'validate' => ['required', 'numeric', 'min:0.1', 'max:10'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'redemption_discount' => [
                        'text' => trans('common.redemption_discount'),
                        'suffix' => '%',
                        'type' => 'string',
                        'format' => 'number',
                        'help' => trans('common.tier_redemption_discount_text'),
                        'default' => 0,
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.01,
                        'validate' => ['nullable', 'numeric', 'min:0', 'max:1'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // TAB 4: DESIGN - Visual Status Symbols
            // ═════════════════════════════════════════════════════════════
            // What: Icons and colors that denote tier status
            // Why: Tiers are about STATUS - visible markers of achievement.
            //      The icon and color become the member's badge of honor,
            //      displayed throughout the app. Design consistency renamed
            //      from "Appearance" to match other campaign types.
            // ═════════════════════════════════════════════════════════════
            'tab4' => [
                'title' => trans('common.design'),
                'fields' => [
                    'icon' => [
                        'text' => trans('common.icon'),
                        'type' => 'icon-picker',
                        'default' => '🥇', // Default to gold medal emoji
                        'help' => trans('common.tier_icon_text'),
                        'validate' => ['nullable', 'max:64'],
                        'actions' => ['list', 'insert', 'edit', 'view', 'export'],
                    ],
                    'color' => [
                        'text' => trans('common.color'),
                        'type' => 'string',
                        'format' => 'color',
                        'help' => trans('common.tier_color_text'),
                        'default' => '#FFD700',
                        'validate' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
                        'actions' => ['insert', 'edit', 'view', 'export'],
                    ],
                ],
            ],

            // ═════════════════════════════════════════════════════════════
            // SYSTEM FIELDS - Analytics & Metadata (No Tab)
            // ═════════════════════════════════════════════════════════════
            // What: Member count, audit trail, timestamps
            // Why: Provides insight into tier distribution and popularity
            //      without cluttering the creation flow.
            // ═════════════════════════════════════════════════════════════
            'member_count' => [
                'text' => trans('common.members'),
                'type' => 'query',
                'query' => function ($row) {
                    return \App\Models\MemberTier::where('tier_id', $row->id)
                        ->where('is_active', true)
                        ->count();
                },
                'sortable' => false,
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
            'icon' => 'award',
            // Title (plural of subject)
            'title' => trans('common.membership_tiers'),
            // Override title (this title is shown in every view, without sub title like "Edit item" or "View item")
            'overrideTitle' => null,
            // Description
            'description' => trans('common.tiers_description'),
            // Help content for the list view (dismissable accordion)
            'helpContent' => [
                'icon' => 'award',
                'title' => trans('common.tiers_help_title'),
                'content' => trans('common.tiers_help_content'),
                'steps' => [
                    [
                        'title' => trans('common.tiers_help_step1_title'),
                        'description' => trans('common.tiers_help_step1_desc'),
                    ],
                    [
                        'title' => trans('common.tiers_help_step2_title'),
                        'description' => trans('common.tiers_help_step2_desc'),
                    ],
                    [
                        'title' => trans('common.tiers_help_step3_title'),
                        'description' => trans('common.tiers_help_step3_desc'),
                    ],
                    [
                        'title' => trans('common.tiers_help_step4_title'),
                        'description' => trans('common.tiers_help_step4_desc'),
                    ],
                ],
            ],
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
            'orderByColumn' => 'level',
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
            // Enforce unique level per club before save (mirrors Tier::saving hook)
            // Required because DataService uses saveQuietly() for JSON-cast models,
            // which skips Eloquent model events
            'beforeInsert' => function ($tier) {
                $duplicate = \App\Models\Tier::where('club_id', $tier->club_id)
                    ->where('level', $tier->level)
                    ->where('id', '!=', $tier->id ?? '')
                    ->exists();

                if ($duplicate) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'level' => [trans('common.tier_level_must_be_unique')],
                    ]);
                }
            },
            'beforeUpdate' => function ($tier) {
                $duplicate = \App\Models\Tier::where('club_id', $tier->club_id)
                    ->where('level', $tier->level)
                    ->where('id', '!=', $tier->id)
                    ->exists();

                if ($duplicate) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'level' => [trans('common.tier_level_must_be_unique')],
                    ]);
                }
            },
            // Recalculate affected members when a tier's active status changes (mirrors Tier::updated hook)
            'afterUpdate' => function ($tier) {
                if ($tier->wasChanged('is_active')) {
                    if (! $tier->is_active) {
                        // Deactivated: move members off this tier
                        \App\Models\Tier::recalculateAffectedMembers($tier);
                    } else {
                        // Reactivated: check if lower-tier members now qualify
                        \App\Models\Tier::recalculateEligibleMembers($tier);
                    }
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
