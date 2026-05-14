<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Data definition for the Activity Log management in the Admin dashboard.
 * Provides full visibility into all system activities for Super Admins.
 *
 * Design Tenets:
 * - **Full Visibility**: Admins see all activities across the platform
 * - **Read-Only**: Activity logs cannot be modified, only viewed
 * - **Filterable**: Multiple filter options for investigation
 * - **Performance**: Pagination and indexing for large datasets
 */

namespace App\DataDefinitions\Models\Admin;

use App\DataDefinitions\DataDefinition;
use App\Models\Activity;
use App\Models\Admin;
use App\Models\AgentKey;
use App\Models\Card;
use App\Models\Club;
use App\Models\Member;
use App\Models\Network;
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
                    'api' => 'API',
                    'admins' => 'Admins',
                    'partners' => 'Partners',
                    'staff' => 'Staff',
                    'members' => 'Members',
                    'member_tiers' => 'Member Tiers',
                    'tiers' => 'Tiers',
                    'cards' => 'Cards',
                    'rewards' => 'Rewards',
                    'clubs' => 'Clubs',
                    'networks' => 'Networks',
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
                    'login_failed' => 'Login Failed',
                    'lockout' => 'Lockout',
                    'password_reset' => 'Password Reset',
                    'api_request' => 'API Request',
                    'otp_sent' => 'OTP Sent',
                    'otp_login' => 'OTP Login',
                    'data_export' => 'Data Export',
                    'partner_data_deleted' => 'Partner Data Deleted',
                    'account_deleted' => 'Account Deleted',
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
                    Admin::class => 'Admin',
                    Partner::class => 'Partner',
                    Staff::class => 'Staff',
                    Member::class => 'Member',
                ],
                'filter' => true,
                'actions' => ['list', 'view', 'export'],
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
                    Admin::class => 'Admin',
                    Partner::class => 'Partner',
                    Staff::class => 'Staff',
                    Member::class => 'Member',
                    Card::class => 'Card',
                    Reward::class => 'Reward',
                    Club::class => 'Club',
                    Network::class => 'Network',
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
                'searchable' => true,
                'actions' => ['view', 'export'],
            ],
            'user_agent' => [
                'text' => trans('common.user_agent'),
                'type' => 'string',
                'actions' => ['view'],
            ],
            'batch_uuid' => [
                'text' => trans('common.batch'),
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

        // Define the general settings for the data definition
        $this->settings = [
            // Icon
            'icon' => 'activity',
            // Eager load morph relations to prevent lazy loading violations
            'queryFilter' => function ($query) {
                $query->with(['causer', 'subject']);

                // Ensure morph keys are selected for eager loading (MorphTo).
                $query->addSelect([
                    'causer_type',
                    'causer_id',
                    'subject_type',
                    'subject_id',
                ]);

                return $query;
            },
            // Title (plural of subject)
            'title' => trans('common.activity_logs'),
            // Override title
            'overrideTitle' => null,
            // Description
            'description' => trans('common.activity_logs_description'),
            // Guard of user that manages this data
            'guard' => 'admin',
            // Required role(s) - Super Admin only
            'roles' => [1],
            // Password not required for viewing
            'editRequiresPassword' => false,
            // Don't redirect to edit
            'redirectListToEdit' => false,
            // Column for redirect
            'redirectListToEditColumn' => null,
            // User ownership - admins can see all
            'userMustOwnRecords' => false,
            // No multi-select (read-only)
            'multiSelect' => false,
            // Items per page
            'itemsPerPage' => 50,
            // Order by column
            'orderByColumn' => 'created_at',
            // Order direction
            'orderDirection' => 'desc',
            // Custom link for purging old logs
            'customLink' => [
                'url' => route('admin.activity-logs.purge'),
                'label' => trans('common.purge_old_logs'),
                'icon' => 'trash-2',
                'variant' => 'danger',
            ],
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
