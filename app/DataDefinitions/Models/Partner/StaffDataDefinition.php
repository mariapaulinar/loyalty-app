<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * DataDefinition for Partner management of Staff (employees who award points).
 * Provides full CRUD interface for creating, configuring, and managing staff
 * accounts who operate point-of-sale loyalty transactions. Partners create
 * staff, assign them to clubs, and control their access.
 *
 * Design Tenets:
 * - **Delegation Model**: Partners create staff who handle daily operations
 * - **Club Assignment**: Staff belong to specific clubs (location-based access)
 * - **Password Assistance**: Auto-generate & email passwords (UX convenience)
 * - **Activity Monitoring**: Login tracking shows staff engagement
 * - **No Tabs Needed**: 11 fields organized in logical sections, no complexity
 *
 * Architecture Notes:
 * Staff are employees - they're owned by partners but are separate accounts
 * with their own authentication. The `mailUserPassword` feature sends
 * credentials via email, reducing friction in staff onboarding. Club assignment
 * (club_id) determines which loyalty programs a staff member can transact
 * against - it's access control AND organizational structure. The 'impersonate'
 * field allows partners to log in AS staff for training/troubleshooting.
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

class StaffDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'staff';

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
        $this->model = new \App\Models\Staff;

        // Define the fields for the data definition
        $this->fields = [
            // ═════════════════════════════════════════════════════════════
            // IDENTITY SECTION - Core Staff Profile
            // ═════════════════════════════════════════════════════════════
            // What: Visual identity, name, club assignment
            // Why: Avatar provides human recognition, name is how staff are
            //      addressed. Club assignment is CRITICAL - it determines which
            //      loyalty programs this staff member can transact against.
            //      It's both access control and organizational structure.
            // ═════════════════════════════════════════════════════════════
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
            ],
            'club_id' => [
                'text' => trans('common.club'),
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
                'searchable' => true,
                'sortable' => true,
                'validate' => ['required', 'max:120'],
                'classes::list' => 'md-only:hidden',
                'actions' => ['list', 'insert', 'edit', 'view', 'export'],
            ],

            // ═════════════════════════════════════════════════════════════
            // AUTHENTICATION SECTION - Access Credentials
            // ═════════════════════════════════════════════════════════════
            // What: Email (login) and password with generation & mailing
            // Why: Email is the staff login. Password generation + email delivery
            //      removes onboarding friction - partner creates account, system
            //      emails credentials, staff logs in immediately. No back-and-forth
            //      with temporary passwords. `mailUserPasswordChecked: true` on
            //      insert means "send email" is checked by default.
            // ═════════════════════════════════════════════════════════════
            'email' => [
                'text' => trans('common.email_address'),
                'type' => 'string',
                'format' => 'email',
                'searchable' => true,
                'sortable' => true,
                'validate' => ['required', 'email', 'max:120', 'unique:staff,email,:id'],
                'actions' => ['insert', 'edit', 'view', 'export'],
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

            // ═════════════════════════════════════════════════════════════
            // LOCALIZATION SECTION - Regional Settings
            // ═════════════════════════════════════════════════════════════
            // What: Timezone and currency preferences
            // Why: Staff may work in different timezones/locations than partner.
            //      Timezone ensures timestamps display correctly for staff.
            //      Currency determines default transaction currency at POS.
            //      Defaults inherit from partner but can be overridden per-staff.
            // ═════════════════════════════════════════════════════════════
            'time_zone' => [
                'text' => trans('common.time_zone'),
                'type' => 'time_zone',
                'default' => auth('partner')->user()->time_zone,
                'validate' => ['required', 'timezone'],
                'actions' => ['view', 'edit', 'insert', 'export'],
            ],
            'currency' => [
                'text' => trans('common.currency'),
                'type' => 'currency',
                'default' => auth('partner')->user()->currency,
                'validate' => ['required'],
                'actions' => ['view', 'edit', 'insert', 'export'],
            ],

            // ═════════════════════════════════════════════════════════════
            // STATUS SECTION - Account Control
            // ═════════════════════════════════════════════════════════════
            // What: Active/inactive flag
            // Why: Soft-delete functionality - deactivating staff removes access
            //      without deleting records (preserves transaction history).
            //      Useful when staff leave or are temporarily suspended. Boolean
            //      toggle is easier than hard delete + restore workflows.
            // ═════════════════════════════════════════════════════════════
            'is_active' => [
                'text' => trans('common.active'),
                'type' => 'boolean',
                'validate' => ['nullable', 'boolean'],
                'format' => 'icon',
                'sortable' => true,
                'default' => true,
                'help' => trans('common.user_is_active_text'),
                'classes::list' => 'lg-only:hidden',
                'actions' => ['list', 'insert', 'edit', 'view', 'export'],
            ],

            // ═════════════════════════════════════════════════════════════
            // ACTIVITY SECTION - Engagement Monitoring
            // ═════════════════════════════════════════════════════════════
            // What: Login count, last login timestamp, impersonate button
            // Why: Partners need visibility into staff engagement. High login
            //      count = active staff. Last login timestamp identifies inactive
            //      accounts (security risk). Impersonate allows partners to
            //      log in AS staff for training or troubleshooting without
            //      needing to know staff passwords.
            // ═════════════════════════════════════════════════════════════
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
                'guard' => 'staff',
                'actions' => ['list'],
            ],

            // ═════════════════════════════════════════════════════════════
            // SYSTEM SECTION - Audit Trail & Metadata
            // ═════════════════════════════════════════════════════════════
            // What: Creation/update timestamps and attribution
            // Why: Full audit trail for compliance and troubleshooting. Tracks
            //      who created/modified staff accounts and when. Important for
            //      security audits and resolving access disputes.
            // ═════════════════════════════════════════════════════════════
            'created_at' => [
                'text' => trans('common.created'),
                'type' => 'date_time',
                'actions' => ['view', 'export'],
            ],
            'created_by' => [
                'text' => trans('common.created_by'),
                'type' => 'user.admin',
                'actions' => ['view', 'export'],
            ],
            'updated_at' => [
                'text' => trans('common.updated'),
                'type' => 'date_time',
                'actions' => ['view', 'export'],
            ],
            'updated_by' => [
                'text' => trans('common.updated_by'),
                'type' => 'user.admin',
                'actions' => ['view', 'export'],
            ],
        ];

        // Define the general settings for the data definition
        $this->settings = [
            // Icon
            'icon' => 'briefcase',
            // Title (plural of subject)
            'title' => trans('common.staff_members'),
            // Override title (this title is shown in every view, without sub title like "Edit item" or "View item")
            'overrideTitle' => null,
            // Guard of user that manages this data (member, staff, partner or admin)
            // This guard is also used for routes and to include the correct Blade layout
            'guard' => 'partner',
            // The guard used for sending the user password
            'mailUserPasswordGuard' => 'staff',
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
            if (! $entitlements->withinLimit($partner, 'staff')) {
                $this->settings['actions']['insert'] = false;
                $this->settings['limitReached'] = true;
                $this->settings['limitReachedMessage'] = trans('common.limit_reached_message', [
                    'limit' => $entitlements->limit($partner, 'staff'),
                    'item' => trans('common.staff_members'),
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
