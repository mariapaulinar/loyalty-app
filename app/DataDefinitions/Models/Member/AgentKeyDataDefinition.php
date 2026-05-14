<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * DataDefinition for Member management of Agent Keys.
 * Members manage their own API keys through the standard CRUD interface.
 *
 * Key differences from Partner version:
 * - **Guard**: member (not partner)
 * - **Key prefix**: rl_member_ (not rl_agent_)
 * - **Default expiration**: 90 days (not permanent) — consumer keys are time-limited
 * - **Anonymous restriction**: Anonymous members cannot create keys
 * - **Scope presets**: Simpler — read or read + redeem (no write:clubs etc.)
 * - **Key limit**: Fixed at 3 (no admin-configurable limit for members)
 *
 * @see RewardLoyalty-100d-phase4-advanced.md §2.5
 */

namespace App\DataDefinitions\Models\Member;

use App\DataDefinitions\DataDefinition;
use App\Models\AgentKey;
use App\Models\Member;
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
     * Maximum keys per member (non-configurable — fixed policy).
     */
    private const MEMBER_KEY_LIMIT = 3;

    /**
     * Default key expiration in days for member keys.
     */
    private const DEFAULT_EXPIRY_DAYS = 90;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Feature gate: Agent API must be enabled
        if (! config('default.feature_agent_api')) {
            abort(404);
        }

        // Block anonymous members from accessing key management
        if (auth('member')->check()) {
            $member = auth('member')->user();
            if ($member && $member->isAnonymous()) {
                abort(403, 'Agent keys require a verified account with an email address.');
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
                        'help' => trans('agent.help_member_key_name'),
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
                            'read' => trans('agent.member_scope_read'),
                            'wallet' => trans('agent.member_scope_wallet'),
                        ],
                        'default' => 'read',
                        'validate' => ['required'],
                        'help' => trans('agent.help_member_scopes'),
                        'actions' => ['insert'],
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
                        'help' => trans('agent.help_member_expires_at'),
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
            'guard' => 'member',
            'roles' => [1],
            'helpContent' => [
                'icon' => 'bot',
                'title' => trans('agent.member_help_title'),
                'content' => trans('agent.member_help_content'),
                'steps' => [
                    [
                        'title' => trans('agent.member_help_step1_title'),
                        'description' => trans('agent.member_help_step1_desc'),
                    ],
                    [
                        'title' => trans('agent.member_help_step2_title'),
                        'description' => trans('agent.member_help_step2_desc'),
                    ],
                    [
                        'title' => trans('agent.member_help_step3_title'),
                        'description' => trans('agent.member_help_step3_desc'),
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
                if (auth('member')->check()) {
                    $member = auth('member')->user();

                    return $query
                        ->where('owner_type', Member::class)
                        ->where('owner_id', $member->id);
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
                'export' => false, // Members don't need key export
            ],
            'beforeInsert' => function ($model) {
                // Set polymorphic ownership before save
                $model->owner_type = Member::class;
                $model->owner_id = auth('member')->user()->id;

                // Generate key credentials (saveQuietly skips model events)
                $model->generateKeyCredentials();
            },
            'afterInsert' => function ($model) {
                // Map scope presets to actual scopes arrays
                $scopePresets = [
                    'read'   => ['read'],
                    'wallet' => ['read', 'write:redeem', 'write:profile'],
                ];
                $selectedPreset = $model->scopes;
                $presetKey = is_array($selectedPreset) ? ($selectedPreset[0] ?? 'read') : ($selectedPreset ?? 'read');
                $model->scopes = $scopePresets[$presetKey] ?? $scopePresets['read'];

                // Default 90-day expiration for member keys
                if (! $model->expires_at) {
                    $model->expires_at = now()->addDays(self::DEFAULT_EXPIRY_DAYS);
                }

                $model->save();

                // Flash for one-time key display modal
                session()->flash('agent_key_raw', $model->raw_key);
            },
        ];

        // Check key limit (fixed at 3 for members)
        if (auth('member')->check()) {
            $member = auth('member')->user();
            $count = AgentKey::where('owner_type', Member::class)
                ->where('owner_id', $member->id)
                ->count();
            if ($count >= self::MEMBER_KEY_LIMIT) {
                $this->settings['actions']['insert'] = false;
                $this->settings['limitReached'] = true;
                $this->settings['limitReachedMessage'] = trans('agent.member_limit_reached', ['limit' => self::MEMBER_KEY_LIMIT]);
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
