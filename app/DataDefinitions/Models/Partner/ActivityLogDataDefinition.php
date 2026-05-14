<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Data definition for the Activity Log management in the Partner dashboard.
 * Partners can only see activities related to their own resources (cards,
 * staff, rewards, members associated with their clubs).
 *
 * Design Tenets:
 * - **Scoped Access**: Partners only see their own activities
 * - **Read-Only**: Activity logs cannot be modified
 * - **Privacy-Compliant**: Respects data boundaries between partners
 * - **Business Insights**: Helps partners monitor their loyalty programs
 */

namespace App\DataDefinitions\Models\Partner;

use App\DataDefinitions\DataDefinition;
use App\Models\Activity;
use App\Models\AgentKey;
use App\Models\Card;
use App\Models\Club;
use App\Models\Member;
use App\Models\Partner;
use App\Models\Reward;
use App\Models\Staff;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

class ActivityLogDataDefinition extends DataDefinition
{
    /**
     * Unique for data definitions, url-friendly name for CRUD purposes.
     */
    public $name = 'activity-logs';

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
     * Constructor.
     */
    public function __construct()
    {
        // Permission check via EntitlementService
        if (auth('partner')->check()) {
            if (! app(\App\Services\EntitlementService::class)->can(auth('partner')->user(), 'activity_log')) {
                abort(403);
            }
        }

        // Set the model
        $this->model = new Activity;

        // Define the fields for the data definition
        $this->fields = [
            'log_name' => [
                'text' => trans('common.category'),
                'type' => 'select',
                'options' => [
                    'default' => 'Default',
                    'authentication' => 'Authentication',
                    'privacy' => 'Privacy',
                    'transactions' => 'Transactions',
                    'staff' => 'Staff',
                    'members' => 'Members',
                    'member_tiers' => 'Member Tiers',
                    'tiers' => 'Tiers',
                    'cards' => 'Cards',
                    'rewards' => 'Rewards',
                    'clubs' => 'Clubs',
                    'vouchers' => 'Vouchers',
                    'voucher_redemptions' => 'Voucher Redemptions',
                    'voucher_voids' => 'Voucher Voids',
                    'voucher_exhausted' => 'Voucher Exhausted',
                    'stamp_cards' => 'Stamp Cards',
                    'stamp_transactions' => 'Stamp Transactions',
                    'stamp_enrollments' => 'Stamp Enrollments',
                    'stamp_completions' => 'Stamp Completions',
                    'stamp_redemptions' => 'Stamp Redemptions',
                    'agent_api' => trans('agent.agent_keys'),
                ],
                'filter' => true,
                'sortable' => true,
                'highlight' => true,
                'actions' => ['list', 'view', 'export'],
            ],
            'description' => [
                'text' => trans('common.description'),
                'type' => 'string',
                'searchable' => true,
                'allowHtml' => false,
                'actions' => ['list', 'view', 'export'],
            ],
            'event' => [
                'text' => trans('common.event'),
                'type' => 'select',
                'options' => [
                    'created' => 'Created',
                    'updated' => 'Updated',
                    'deleted' => 'Deleted',
                    'login' => 'Login',
                    'logout' => 'Logout',
                    'otp_sent' => 'OTP Sent',
                    'otp_login' => 'OTP Login',
                    'data_export' => 'Data Export',
                    'partner_data_deleted' => 'Partner Data Deleted',
                    'member_enrolled' => 'Member Enrolled',
                    'stamp_earned' => 'Stamp Earned',
                    'stamp_card_completed' => 'Stamp Card Completed',
                    'stamp_reward_redeemed' => 'Stamp Reward Redeemed',
                    'points_credited' => 'Points Credited',
                    'tier_assigned' => 'Tier Assigned',
                    'tier_upgraded' => 'Tier Upgraded',
                    'tier_downgraded' => 'Tier Downgraded',
                    'tier_changed' => 'Tier Changed',
                    'voucher_created' => 'Voucher Created',
                    'voucher_redeemed' => 'Voucher Redeemed',
                    'voucher_voided' => 'Voucher Voided',
                    'voucher_exhausted' => 'Voucher Exhausted',
                    'agent_read' => 'Agent Read',
                    'agent_write' => 'Agent Write',
                    'agent_delete' => 'Agent Delete',
                ],
                'filter' => true,
                'sortable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
            'causer_type' => [
                'text' => trans('common.user_type'),
                'type' => 'select',
                'options' => [
                    Partner::class => 'Partner',
                    Staff::class => 'Staff',
                ],
                'filter' => true,
                'actions' => ['view', 'export'],
            ],
            'causer_name' => [
                'text' => trans('common.user'),
                'type' => 'query',
                'default' => 'System',
                'query' => function ($row) {
                    return $row->causer?->name ?? $row->causer?->email ?? 'System';
                },
                'actions' => ['list', 'view', 'export'],
            ],
            'subject_type' => [
                'text' => trans('common.subject_type'),
                'type' => 'select',
                'options' => [
                    Partner::class => 'Partner',
                    Staff::class => 'Staff',
                    Member::class => 'Member',
                    Card::class => 'Card',
                    Reward::class => 'Reward',
                    Club::class => 'Club',
                    Transaction::class => 'Transaction',
                    AgentKey::class => trans('agent.agent_key'),
                ],
                'filter' => true,
                'actions' => ['view', 'export'],
            ],
            'subject_name' => [
                'text' => trans('common.subject'),
                'type' => 'query',
                'default' => '-',
                'query' => function ($row) {
                    if (! $row->subject) {
                        return '-';
                    }

                    return $row->subject->name ?? $row->subject->email ?? class_basename($row->subject).' #'.$row->subject_id;
                },
                'actions' => ['list', 'view', 'export'],
            ],
            'properties' => [
                'text' => trans('common.changes'),
                'type' => 'string',
                'format' => 'json',
                'actions' => ['view'],
            ],
            'ip_address' => [
                'text' => trans('common.ip_address'),
                'type' => 'string',
                'actions' => ['view', 'export'],
            ],
            'created_at' => [
                'text' => trans('common.date'),
                'type' => 'date_time',
                'sortable' => true,
                'actions' => ['list', 'view', 'export'],
            ],
        ];

        // Get current partner ID for scoping
        $partnerId = auth('partner')->id();

        // Define the general settings for the data definition
        $this->settings = [
            // Icon
            'icon' => 'activity',
            // Title (plural of subject)
            'title' => trans('common.activity_logs'),
            // Override title
            'overrideTitle' => null,
            // Description
            'description' => trans('common.activity_logs_description'),
            // Guard of user that manages this data
            'guard' => 'partner',
            // Required role(s) - Business Owner only
            'roles' => [1],
            // Password not required for viewing
            'editRequiresPassword' => false,
            // Don't redirect to edit
            'redirectListToEdit' => false,
            // Column for redirect
            'redirectListToEditColumn' => null,
            // User ownership - Partners can see their own activities
            'userMustOwnRecords' => false,
            // Custom query filter to scope to partner's activities
            'queryFilter' => function ($query) use ($partnerId) {
                // Eager load causer and subject to prevent N+1 and lazy loading violations
                $query->with(['causer', 'subject']);

                // Ensure morph keys are selected for eager loading (causer/subject are MorphTo relations).
                $query->addSelect([
                    'causer_type',
                    'causer_id',
                    'subject_type',
                    'subject_id',
                ]);

                // Show activities where:
                // 1. The causer is the partner
                // 2. The causer is staff created by the partner
                // 3. The subject is owned by the partner (cards, clubs, rewards, staff)
                return $query->where(function ($q) use ($partnerId) {
                    // Partner's own actions
                    $q->where(function ($q2) use ($partnerId) {
                        $q2->where('causer_type', Partner::class)
                            ->where('causer_id', $partnerId);
                    })
                    // Partner's staff actions
                        ->orWhere(function ($q2) use ($partnerId) {
                            $q2->where('causer_type', Staff::class)
                                ->whereIn('causer_id', function ($sub) use ($partnerId) {
                                    $sub->select('id')
                                        ->from('staff')
                                        ->where('created_by', $partnerId);
                                });
                        })
                    // Actions on partner's resources
                        ->orWhere(function ($q2) use ($partnerId) {
                            // Cards
                            $q2->where('subject_type', Card::class)
                                ->whereIn('subject_id', function ($sub) use ($partnerId) {
                                    $sub->select('id')
                                        ->from('cards')
                                        ->where('created_by', $partnerId);
                                });
                        })
                        ->orWhere(function ($q2) use ($partnerId) {
                            // Clubs
                            $q2->where('subject_type', Club::class)
                                ->whereIn('subject_id', function ($sub) use ($partnerId) {
                                    $sub->select('id')
                                        ->from('clubs')
                                        ->where('created_by', $partnerId);
                                });
                        })
                        ->orWhere(function ($q2) use ($partnerId) {
                            // Rewards
                            $q2->where('subject_type', Reward::class)
                                ->whereIn('subject_id', function ($sub) use ($partnerId) {
                                    $sub->select('id')
                                        ->from('rewards')
                                        ->where('created_by', $partnerId);
                                });
                        })
                        ->orWhere(function ($q2) use ($partnerId) {
                            // Staff
                            $q2->where('subject_type', Staff::class)
                                ->whereIn('subject_id', function ($sub) use ($partnerId) {
                                    $sub->select('id')
                                        ->from('staff')
                                        ->where('created_by', $partnerId);
                                });
                        })
                        ->orWhere(function ($q2) use ($partnerId) {
                            // Agent Keys owned by this partner
                            $q2->where('subject_type', AgentKey::class)
                                ->whereIn('subject_id', function ($sub) use ($partnerId) {
                                    $sub->select('id')
                                        ->from('agent_keys')
                                        ->where('owner_type', Partner::class)
                                        ->where('owner_id', $partnerId);
                                });
                        });
                });
            },
            // No multi-select (read-only)
            'multiSelect' => false,
            // Items per page
            'itemsPerPage' => 25,
            // Order by column
            'orderByColumn' => 'created_at',
            // Order direction
            'orderDirection' => 'desc',
            // Possible actions - view only (no insert, edit, delete)
            'actions' => [
                'subject_column' => 'description',
                'list' => true,
                'insert' => false,
                'edit' => false,
                'delete' => false,
                'view' => true,
                'export' => true,
            ],
        ];
    }

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
