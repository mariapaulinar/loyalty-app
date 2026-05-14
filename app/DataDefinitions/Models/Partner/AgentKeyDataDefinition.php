<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * DataDefinition for Partner management of Agent Keys.
 * Partners manage their own API keys through the standard CRUD interface.
 *
 * Design Tenets:
 * - **Security First**: Raw key shown only once after creation via session flash
 * - **Self-Service**: Partners create/revoke their own keys within admin-set limits
 * - **Transparency**: Prefix, scopes, usage, and expiry visible in list view
 *
 * This leverages the existing DataDefinition system — no custom controllers needed.
 * The AgentKey model's booted() lifecycle handles key generation, hashing, and
 * prefix extraction automatically on creation.
 *
 * @see App\Services\EntitlementService
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use App\Models\AgentKey;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Model;

class AgentKeyDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     */
    public $name = 'agent-keys';

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
        // Feature gate: Agent API must be enabled
        if (! config('default.feature_agent_api')) {
            abort(404);
        }

        // Permission check via EntitlementService: only partners with agent_api can access
        if (auth('partner')->check()) {
            if (! app(\App\Services\EntitlementService::class)->can(auth('partner')->user(), 'agent_api')) {
                abort(403);
            }
        }

        // Set the model
        $this->model = new AgentKey;

        // Define the fields for the data definition
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
                        'help' => trans('agent.help_name'),
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
                            'read' => trans('agent.scope_read'),
                            'pos' => trans('agent.scope_pos'),
                            'standard' => trans('agent.scope_standard'),
                            'admin' => trans('agent.scope_admin'),
                        ],
                        'default' => 'pos',
                        'validate' => ['required'],
                        'help' => trans('agent.help_scopes'),
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

        // Define the general settings for the data definition
        $this->settings = [
            'icon' => 'bot',
            'title' => trans('agent.agent_keys'),
            'overrideTitle' => null,
            'description' => null,
            'guard' => 'partner',
            'roles' => [1],
            'helpContent' => [
                'icon' => 'bot',
                'title' => trans('agent.partner_help_title'),
                'content' => trans('agent.partner_help_content'),
                'steps' => [
                    [
                        'title' => trans('agent.partner_help_step1_title'),
                        'description' => trans('agent.partner_help_step1_desc'),
                    ],
                    [
                        'title' => trans('agent.partner_help_step2_title'),
                        'description' => trans('agent.partner_help_step2_desc'),
                    ],
                    [
                        'title' => trans('agent.partner_help_step3_title'),
                        'description' => trans('agent.partner_help_step3_desc'),
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
                if (auth('partner')->check()) {
                    $partner = auth('partner')->user();

                    return $query
                        ->where('owner_type', Partner::class)
                        ->where('owner_id', $partner->id);
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
            // The AgentKey model's booted() lifecycle handles key_hash,
            // key_prefix, and raw_key generation. Owner fields are pre-set
            // in the constructor. We map the scope preset and flash the raw key.
            'beforeInsert' => function ($model) {
                // Set polymorphic ownership before save
                $model->owner_type = Partner::class;
                $model->owner_id = auth('partner')->user()->id;

                // Generate key credentials (saveQuietly skips model events)
                $model->generateKeyCredentials();
            },
            'afterInsert' => function ($model) {
                // Map the scope preset (select dropdown value) to actual scopes array
                $scopePresets = [
                    'read'     => ['read'],
                    'pos'      => ['read', 'write:transactions', 'write:rewards'],
                    'standard' => ['read', 'write:cards', 'write:rewards', 'write:stamps', 'write:vouchers', 'write:clubs'],
                    'admin'    => ['admin'],
                ];
                $selectedPreset = $model->scopes;
                // The scopes cast (array) wraps the string → ['pos']. Extract the raw value.
                $presetKey = is_array($selectedPreset) ? ($selectedPreset[0] ?? 'pos') : ($selectedPreset ?? 'pos');
                $model->scopes = $scopePresets[$presetKey] ?? $scopePresets['pos'];
                $model->save();

                // Flash the raw key for the one-time display modal.
                // raw_key is available in-memory from the creating event.
                session()->flash('agent_key_raw', $model->raw_key);
            },
        ];

        // Check for agent key limits via EntitlementService
        if (auth('partner')->check()) {
            $entitlements = app(\App\Services\EntitlementService::class);
            $partner = auth('partner')->user();
            if (! $entitlements->withinLimit($partner, 'agent_keys')) {
                $this->settings['actions']['insert'] = false;
                $this->settings['limitReached'] = true;
                $this->settings['limitReachedMessage'] = trans('agent.limit_reached', [
                    'limit' => $entitlements->limit($partner, 'agent_keys'),
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
