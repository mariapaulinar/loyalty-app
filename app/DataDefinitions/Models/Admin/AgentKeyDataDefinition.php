<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * DataDefinition for Admin management of Agent Keys.
 * Admins (role=1) manage platform-level API keys through the standard CRUD interface.
 *
 * Key differences from Partner/Member versions:
 * - **Guard**: admin
 * - **Key prefix**: rl_admin_ (9 chars)
 * - **Scope presets**: Platform-level — partners, members, analytics
 * - **No default expiry**: Admin keys are permanent unless explicitly set
 * - **Key limit**: Fixed at 10 (platform-level keys are fewer, more powerful)
 * - **Role restriction**: Only role=1 (super admin) can manage agent keys
 *
 * @see App\DataDefinitions\Models\Partner\AgentKeyDataDefinition (partner pattern)
 */

namespace App\DataDefinitions\Models\Admin;

use App\DataDefinitions\DataDefinition;
use App\Models\Admin;
use App\Models\AgentKey;
use Illuminate\Database\Eloquent\Model;

class AgentKeyDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     */
    public $name = 'agent-keys';

    /**
     * The model associated with the definition.
     */
    public $model;

    /**
     * Settings.
     */
    public $settings;

    /**
     * Model fields for list, insert, edit, view.
     */
    public $fields;

    /**
     * Maximum keys per admin platform (fixed policy).
     */
    private const ADMIN_KEY_LIMIT = 10;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Feature gate: Agent API must be enabled
        if (! config('default.feature_agent_api')) {
            abort(404);
        }

        // Only super admins (role=1) can manage agent keys
        if (auth('admin')->check()) {
            $admin = auth('admin')->user();
            if ($admin && $admin->role != 1) {
                abort(403);
            }
        }

        // Set the model
        $this->model = new AgentKey;

        // Define the fields
        $this->fields = [
            // ═════════════════════════════════════════════════════════════
            // TAB 1: KEY CONFIGURATION
            // List order: Name → Key Prefix → Active → Expiry → Last Used → Created
            // Identity first, status second, temporal last.
            // ═════════════════════════════════════════════════════════════
            'tab1' => [
                'title' => trans('common.details'),
                'fields' => [
                    'name' => [
                        'text' => trans('common.name'),
                        'help' => trans('agent.help_admin_key_name'),
                        'type' => 'string',
                        'highlight' => true,
                        'searchable' => true,
                        'sortable' => true,
                        'validate' => ['required', 'max:100'],
                        'actions' => ['list', 'insert', 'edit', 'export'],
                    ],
                    'key_prefix' => [
                        'text' => trans('agent.key_prefix'),
                        'type' => 'string',
                        'sortable' => false,
                        'actions' => ['list', 'export'],
                    ],
                    'scopes' => [
                        'text' => trans('common.permissions'),
                        'type' => 'select',
                        'options' => [
                            'read' => trans('agent.admin_scope_read'),
                            'standard' => trans('agent.admin_scope_standard'),
                            'admin' => trans('agent.admin_scope_admin'),
                        ],
                        'default' => 'read',
                        'validate' => ['required'],
                        'help' => trans('agent.help_admin_scopes'),
                        'actions' => ['insert'],
                    ],
                    'rate_limit' => [
                        'text' => trans('agent.rate_limit_rpm'),
                        'type' => 'string',
                        'format' => 'number',
                        'default' => 60,
                        'min' => 1,
                        'max' => 1000,
                        'step' => 1,
                        'validate' => ['nullable', 'numeric', 'min:1', 'max:1000'],
                        'help' => trans('agent.help_rate_limit'),
                        'actions' => ['insert', 'edit'],
                    ],
                    'is_active' => [
                        'text' => trans('common.active'),
                        'type' => 'boolean',
                        'validate' => ['nullable', 'boolean'],
                        'format' => 'icon',
                        'sortable' => true,
                        'default' => true,
                        'help' => trans('agent.help_is_active'),
                        'classes::list' => 'lg-only:hidden',
                        'actions' => ['list', 'edit', 'export'],
                    ],
                    'expires_at' => [
                        'text' => trans('common.expiration_date'),
                        'type' => 'string',
                        'format' => 'datetime-local',
                        'validate' => ['nullable', 'date'],
                        'help' => trans('agent.help_expires_at'),
                        'actions' => ['list', 'insert', 'edit', 'export'],
                    ],
                    'last_used_at' => [
                        'text' => trans('agent.last_used'),
                        'type' => 'date_time',
                        'sortable' => true,
                        'actions' => ['list', 'export'],
                    ],
                    'created_at' => [
                        'text' => trans('common.created'),
                        'type' => 'date_time',
                        'sortable' => true,
                        'actions' => ['list', 'export'],
                    ],
                ],
            ],
        ];

        // Settings
        $this->settings = [
            'icon' => 'bot',
            'title' => trans('agent.agent_keys'),
            'overrideTitle' => null,
            'description' => null,
            'guard' => 'admin',
            'roles' => [1],
            'helpContent' => [
                'icon' => 'bot',
                'title' => trans('agent.admin_help_title'),
                'content' => trans('agent.admin_help_content'),
                'steps' => [
                    [
                        'title' => trans('agent.admin_help_step1_title'),
                        'description' => trans('agent.admin_help_step1_desc'),
                    ],
                    [
                        'title' => trans('agent.admin_help_step2_title'),
                        'description' => trans('agent.admin_help_step2_desc'),
                    ],
                    [
                        'title' => trans('agent.admin_help_step3_title'),
                        'description' => trans('agent.admin_help_step3_desc'),
                    ],
                ],
            ],
            'editRequiresPassword' => false,
            'redirectListToEdit' => false,
            'redirectListToEditColumn' => null,
            'userMustOwnRecords' => false, // Ownership enforced via queryFilter
            'multiSelect' => true,
            'itemsPerPage' => 10,
            'orderByColumn' => 'created_at',
            'orderDirection' => 'desc',
            'queryFilter' => function ($query) {
                if (auth('admin')->check()) {
                    $admin = auth('admin')->user();

                    return $query
                        ->where('owner_type', Admin::class)
                        ->where('owner_id', $admin->id);
                }

                return $query;
            },
            'actions' => [
                'subject_column' => 'name',
                'list' => true,
                'insert' => true,
                'edit' => true,
                'delete' => true,
                'view' => false,
                'export' => true,
            ],
            'beforeInsert' => function ($model) {
                // Set polymorphic ownership before save
                // Must be set before generateKeyCredentials (which uses owner_type for prefix)
                $model->owner_type = Admin::class;
                $model->owner_id = auth('admin')->user()->id;

                // Generate key credentials (key_hash, key_prefix, raw_key)
                // Required because DataService uses saveQuietly() for JSON models,
                // which skips the creating event where this normally happens
                $model->generateKeyCredentials();
            },
            'afterInsert' => function ($model) {
                // Map scope presets to actual scopes arrays
                $scopePresets = [
                    'read'     => ['read:partners', 'read:members', 'read:analytics'],
                    'standard' => ['read:partners', 'read:members', 'read:analytics', 'write:partners'],
                    'admin'    => ['admin'],
                ];
                $selectedPreset = $model->scopes;
                $presetKey = is_array($selectedPreset) ? ($selectedPreset[0] ?? 'read') : ($selectedPreset ?? 'read');
                $model->scopes = $scopePresets[$presetKey] ?? $scopePresets['read'];
                $model->save();

                // Flash for one-time key display modal
                session()->flash('agent_key_raw', $model->raw_key);
            },
        ];

        // Check key limit (fixed at 10 for admin platform keys)
        if (auth('admin')->check()) {
            $admin = auth('admin')->user();
            $count = AgentKey::where('owner_type', Admin::class)
                ->where('owner_id', $admin->id)
                ->count();
            if ($count >= self::ADMIN_KEY_LIMIT) {
                $this->settings['actions']['insert'] = false;
                $this->settings['limitReached'] = true;
                $this->settings['limitReachedMessage'] = trans('agent.admin_limit_reached', ['limit' => self::ADMIN_KEY_LIMIT]);
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
