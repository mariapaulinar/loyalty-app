<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for Partner self-service profile management.
 * Enables partners to edit their own account settings: identity, credentials,
 * localization preferences. Single-record form (edit own profile only).
 *
 * Design Tenets:
 * - **Self-Service**: Partners control their own identity
 * - **Security First**: OTP verification required (passwordless modern auth)
 * - **Localization Native**: Language and timezone are first-class settings
 * - **No Tabs Needed**: Simple profile, 6 fields don't warrant complexity
 *
 * Architecture Notes:
 * This is a special DataDefinition that ALWAYS redirects to edit mode for
 * the logged-in partner's own record. It's not a "list of partners" - it's
 * "MY profile." The `redirectListToEdit: true` + `redirectListToEditColumn: 'id'`
 * configuration creates this single-record behavior. OTP verification
 * (editRequiresOtp) provides passwordless security - modern, secure, user-friendly.
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

class ProfileDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'account';

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
        $this->model = new \App\Models\Partner;

        // Define the fields for the data definition
        $this->fields = [
            // ═════════════════════════════════════════════════════════════
            // IDENTITY SECTION - Who You Are
            // ═════════════════════════════════════════════════════════════
            // What: Visual identity and name
            // Why: Avatar provides human recognition in UI (nav bar, comments).
            //      Name is how you're addressed throughout the platform. These
            //      are "who you are" to other users and to yourself.
            // ═════════════════════════════════════════════════════════════
            /*
            'avatar' => [
                'thumbnail' => 'small', // Image conversion used for list
                'conversion' => 'medium', // Image conversion used for view/edit
                'text' => trans('common.avatar'),
                'type' => 'avatar',
                'textualAvatarBasedOnColumn' => 'name',
                'accept' => 'image/svg+xml, image/png, image/jpeg, image/gif',
                'validate' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:1024', 'dimensions:min_width=60,min_height=60,max_width=1024,max_height=1024'],
                'classes::list' => 'md-only:hidden',
                'actions' => ['list', 'insert', 'edit', 'view'],
            ],*/
            'name' => [
                'text' => trans('common.full_name'),
                'type' => 'string',
                'highlight' => true,
                'searchable' => true,
                'sortable' => true,
                'validate' => ['required', 'max:120'],
                'classes::list' => 'md-only:hidden',
                'actions' => ['list', 'insert', 'edit', 'view', 'export'],
            ],

            // ═════════════════════════════════════════════════════════════
            // AUTHENTICATION SECTION - Access Credentials
            // ═════════════════════════════════════════════════════════════
            // What: Email (username) and password
            // Why: Email is the login identifier AND the OTP delivery target.
            //      Password is optional-to-change (nullable) - only update when
            //      user wants to change it. This section is about "how you
            //      prove you're you."
            // ═════════════════════════════════════════════════════════════
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
                'validate' => ['nullable', 'min:6', 'max:48'],
                'help' => trans('common.new_password_text'),
                'placeholder' => trans('common.new_password_text'),
                'actions' => ['insert', 'edit'],
            ],
            'password::insert' => [
                'text' => trans('common.password'),
                'type' => 'password',
                'validate' => ['required', 'min:6', 'max:48'],
            ],

            // ═════════════════════════════════════════════════════════════
            // LOCALIZATION SECTION - Regional Preferences
            // ═════════════════════════════════════════════════════════════
            // What: Language and timezone settings
            // Why: Partners are global - platform must adapt to THEIR context.
            //      Locale determines UI language, dates, number formats. Timezone
            //      ensures timestamps display in partner's local time. These are
            //      foundational to user experience quality - not afterthoughts.
            // ═════════════════════════════════════════════════════════════
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
            'currency' => [
                'text' => trans('common.currency'),
                'type' => 'currency',
                'default' => auth('partner')->user()->currency,
                'validate' => ['required', 'string', 'size:3'],
                'actions' => ['insert', 'edit', 'view', 'export'],
            ],
        ];

        // Define the general settings for the data definition
        $this->settings = [
            // Icon
            'icon' => 'user-circle',
            // Title (plural of subject)
            'title' => null,
            // Override title (this title is shown in every view, without sub title like "Edit item" or "View item")
            'overrideTitle' => trans('common.account_settings'),
            // Guard of user that manages this data (member, staff, partner or admin)
            // This guard is also used for routes and to include the correct Blade layout
            'guard' => 'partner',
            // Used for updating forms like profile, where user has to enter current password in order to save
            'editRequiresPassword' => false,
            // Modern passwordless verification using OTP sent to email
            'editRequiresOtp' => true,
            // If true, the visitor is redirected to the edit form
            'redirectListToEdit' => true,
            // This column has to match auth($guard)->user()->id if 'redirectListToEdit' == true (usually it will be 'id' or 'created_by')
            'redirectListToEditColumn' => 'id',
            // If true, the user id must match the created_by field
            'userMustOwnRecords' => false,
            // Should there be checkboxes for all rows
            'multiSelect' => true,
            // Default items per page for pagination
            'itemsPerPage' => 10,
            // Order by column
            'orderByColumn' => 'id',
            // Order direction, 'asc' or 'desc'
            'orderDirection' => 'desc',
            // Specify if the 'updated_by' column needs to
            'updatedBy' => false,
            // Possible actions for the data
            'actions' => [
                'subject_column' => 'name', // This column is used for page titles and delete confirmations
                'list' => false,
                'insert' => false,
                'edit' => true,
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
