<?php

namespace App\DataDefinitions\Models\Admin;

use App\DataDefinitions\DataDefinition;
use Illuminate\Database\Eloquent\Model;

class AdminDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     *
     * @var string
     */
    public $name = 'admins';

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
        $this->model = new \App\Models\Admin;

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
                'actions' => ['list', 'insert', 'edit', 'view'],
            ],
            'name' => [
                'text' => trans('common.name'),
                'type' => 'string',
                'highlight' => true,
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
                'classes::list' => 'lg-only:hidden',
                'validate' => ['required', 'email', 'max:120', 'unique:admins,email,:id'],
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
            'time_zone' => [
                'text' => trans('common.time_zone'),
                'type' => 'time_zone',
                'default' => auth('admin')->user()->time_zone,
                'validate' => ['required', 'timezone'],
                'actions' => ['view', 'edit', 'insert', 'export'],
            ],
            'currency' => [
                'text' => trans('common.currency'),
                'type' => 'currency',
                'default' => auth('admin')->user()->currency,
                'validate' => ['required'],
                'actions' => ['view', 'edit', 'insert', 'export'],
            ],
            'role' => [
                'text' => trans('common.role'),
                'type' => 'select',
                'default' => 2,
                'help' => trans('common.admin_role_text'),
                'options' => [
                    1 => trans('common.administrator'),
                    2 => trans('common.manager'),
                ],
                'sortable' => true,
                'validate' => ['required', 'in::option_keys'],
                'classes::list' => 'xl-only:hidden',
                'actions' => ['list', 'insert', 'edit', 'view', 'export'],
            ],
            'networks' => [
                'text' => trans('common.networks'),
                'type' => 'belongsToMany',
                'relation' => 'networks',
                'relationKey' => 'networks.id',
                'relationValue' => 'networks.name',
                'relationModel' => new \App\Models\Network,
                'classes::list' => 'lg-only:hidden',
                'validate' => ['nullable'],
                'actions' => ['list', 'insert', 'edit', 'view', 'export'],
            ],
            'is_active' => [
                'text' => trans('common.active'),
                'type' => 'boolean',
                'validate' => ['nullable', 'boolean'],
                'format' => 'icon',
                'sortable' => true,
                'default' => true,
                'help' => trans('common.user_is_active_text'),
                'classes::list' => 'xl-only:hidden',
                'actions' => ['list', 'insert', 'edit', 'view', 'export'],
            ],
            'number_of_times_logged_in' => [
                'text' => trans('common.logins'),
                'type' => 'number',
                'classes::list' => 'xl-only:hidden',
                'actions' => ['list', 'export'],
            ],
            'last_login_at' => [
                'text' => trans('common.last_login'),
                'type' => 'date_time',
                'default' => trans('common.never'),
                'classes::list' => 'xl-only:hidden',
                'actions' => ['list', 'export'],
            ],
            'login_as' => [
                'text' => trans('common.log_in'),
                'type' => 'impersonate',
                'guard' => 'admin',
                'actions' => ['list'],
            ],
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
            'icon' => 'shield',
            // Title (plural of subject)
            'title' => trans('common.administrators'),
            // Override title (this title is shown in every view, without sub title like "Edit item" or "View item")
            'overrideTitle' => null,
            // Guard of user that manages this data (member, staff, partner or admin)
            // This guard is also used for routes and to include the correct Blade layout
            'guard' => 'admin',
            // The guard used for sending the user password
            'mailUserPasswordGuard' => 'admin',
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
