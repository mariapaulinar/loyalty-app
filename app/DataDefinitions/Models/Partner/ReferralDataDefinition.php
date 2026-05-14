<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for Partner management of Referral Program settings.
 * Enables partners to configure referral rewards for their clubs with
 * a clean, intuitive interface following the Universal Form Pattern.
 *
 * Design Tenets:
 * - **Simplicity First**: Referral settings are straightforward - enable/disable + rewards
 * - **Club-Scoped**: Each club has its own referral configuration
 * - **Progressive Disclosure**: Show only what matters when it matters
 * - **Partner Autonomy**: Full control over referral mechanics
 *
 * Architecture Notes:
 * This is a club-scoped configuration. Partners manage referral settings
 * per club. The form uses a simple two-section layout: Referrer Rewards
 * and Referee Rewards, making it crystal clear who gets what.
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

class ReferralDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'referrals';

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
        $this->model = new \App\Models\ReferralSetting;

        // Define the fields for the data definition
        $this->fields = [
            // ═════════════════════════════════════════════════════════════
            // CAMPAIGN INFO & CLUB ASSOCIATION
            // ═════════════════════════════════════════════════════════════
            // What: Campaign name, description, and club association
            // Why: Partners can create multiple campaigns per club with
            //      descriptive names (e.g., "Summer Bonus", "Holiday Special")
            // ═════════════════════════════════════════════════════════════
            'name' => [
                'text' => trans('common.name'),
                'help' => 'The name is for internal reference only and will not be displayed to members.',
                'type' => 'string',
                'highlight' => true,
                'searchable' => true,
                'sortable' => true,
                'validate' => ['required', 'string', 'max:255'],
                'actions' => ['list', 'insert', 'edit', 'view', 'export'],
            ],

            'description' => [
                'text' => trans('common.description'),
                'help' => 'Optional description to help you remember this campaign\'s purpose',
                'type' => 'text',
                'validate' => ['nullable', 'string', 'max:1000'],
                'actions' => [],
            ],

            'is_enabled' => ['text' => trans('common.active'),
                'help' => trans('common.referral_program_enabled_help'),
                'type' => 'boolean',
                'format' => 'icon',
                'default' => true,
                'validate' => ['boolean'],
                'actions' => ['list', 'insert', 'edit', 'view', 'export'],
            ],

            // ═════════════════════════════════════════════════════════════
            // REFERRER REWARDS - What the Person Sharing Gets
            // ═════════════════════════════════════════════════════════════
            // What: Points awarded to existing members who refer friends
            // Why: This incentivizes your existing customers to spread the word.
            //      Clear labeling helps partners understand this is for the
            //      "person doing the referring" not the new signup.
            // ═════════════════════════════════════════════════════════════
            'referrer_points' => [
                'text' => trans('common.referral.referrer_reward').' ('.trans('common.referral.points').')',
                'help' => trans('common.referral_referrer_points_help'),
                'type' => 'string',
                'format' => 'number',
                'default' => 0,
                'min' => 0,
                'max' => 100000,
                'step' => 1,
                'suffix' => strtolower(trans('common.referral.points')),
                'validate' => ['required', 'integer', 'min:0', 'max:100000'],
                'container_start::insert' => 'grid grid-cols-2 gap-4',
                'container_start::edit' => 'grid grid-cols-2 gap-4',
                'actions' => ['list', 'insert', 'edit', 'view', 'export'],
            ],

            'referrer_card_id' => [
                'text' => trans('common.referral.select_card').' ('.trans('common.referral.referrer_reward').')',
                'help' => trans('common.referral_referrer_card_help'),
                'type' => 'belongsTo',
                'relation' => 'referrerCard',
                'relationKey' => 'cards.id',
                'relationValue' => 'cards.name',
                'relationModel' => new \App\Models\Card,
                'relationMustBeOwned' => true,
                'validate' => ['required', 'uuid', 'exists:cards,id'],
                'container_end::insert' => true,
                'container_end::edit' => true,
                'actions' => ['insert', 'edit', 'view', 'export'],
            ],

            // ═════════════════════════════════════════════════════════════
            // REFEREE REWARDS - What the New Member Gets
            // ═════════════════════════════════════════════════════════════
            // What: Welcome bonus points for new members who sign up via referral
            // Why: This incentivizes new people to use the referral link instead
            //      of signing up directly. Clear labeling prevents confusion with
            //      the referrer reward above.
            // ═════════════════════════════════════════════════════════════
            'referee_points' => [
                'text' => trans('common.referral.referee_reward').' ('.trans('common.referral.points').')',
                'help' => trans('common.referral_referee_points_help'),
                'type' => 'string',
                'format' => 'number',
                'default' => 0,
                'min' => 0,
                'max' => 100000,
                'step' => 1,
                'suffix' => strtolower(trans('common.referral.points')),
                'validate' => ['required', 'integer', 'min:0', 'max:100000'],
                'container_start::insert' => 'grid grid-cols-2 gap-4',
                'container_start::edit' => 'grid grid-cols-2 gap-4',
                'actions' => ['list', 'insert', 'edit', 'view', 'export'],
            ],

            'referee_card_id' => [
                'text' => trans('common.referral.select_card').' ('.trans('common.referral.referee_reward').')',
                'help' => trans('common.referral_referee_card_help'),
                'type' => 'belongsTo',
                'relation' => 'refereeCard',
                'relationKey' => 'cards.id',
                'relationValue' => 'cards.name',
                'relationModel' => new \App\Models\Card,
                'relationMustBeOwned' => true,
                'validate' => ['required', 'uuid', 'exists:cards,id'],
                'container_end::insert' => true,
                'container_end::edit' => true,
                'actions' => ['insert', 'edit', 'view', 'export'],
            ],
        ];

        // Define the general settings for the data definition
        $this->settings = [
            // Icon
            'icon' => 'user-plus',
            // Title (plural of subject)
            'title' => trans('common.refer_earn'),
            // Guard of user that manages this data (member, staff, partner or admin)
            // This guard is also used for routes and to include the correct Blade layout
            'guard' => 'partner',
            // Used for updating forms like profile, where user has to enter current password in order to save
            'editRequiresPassword' => false,
            // Modern passwordless verification using OTP sent to email
            'editRequiresOtp' => false,
            // If true, the user id must match the created_by field
            'userMustOwnRecords' => true,
            // Should there be checkboxes for all rows
            'multiSelect' => true,
            // Default items per page for pagination
            'itemsPerPage' => 25,
            // Order by column
            'orderByColumn' => 'id',
            // Order direction, 'asc' or 'desc'
            'orderDirection' => 'desc',
            // Specify if the 'updated_by' column needs to be updated
            'updatedBy' => false,
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
