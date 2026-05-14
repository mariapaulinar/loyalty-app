<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Resource schemas for Agent API OpenAPI spec.
 *
 * Each schema represents a resource returned by agent endpoints.
 * Translatable fields use oneOf (string | locale-keyed object) to
 * reflect dual-mode serialization: single locale via Accept-Language
 * header, or all translations when no header is sent.
 *
 * These schemas are referenced via $ref in endpoint annotations.
 *
 * @see AgentOpenApiSchemas.php for common schemas (Pagination, AgentError)
 * @see RewardLoyalty-102-api-agents.md §6.4
 */

namespace App\Http\Controllers\Api\Agent;

use OpenApi\Attributes as OA;

// ═══════════════════════════════════════════════════════════════════════════
// TRANSLATABLE HELPER
// ═══════════════════════════════════════════════════════════════════════════

#[OA\Schema(
    schema: 'TranslatableString',
    description: 'A translatable value. String when Accept-Language is set, object of locale→value when not.',
    oneOf: [
        new OA\Schema(type: 'string'),
        new OA\Schema(
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
            example: ['en' => 'English text', 'nl' => 'Nederlandse tekst'],
        ),
    ],
)]

// ═══════════════════════════════════════════════════════════════════════════
// PARTNER RESOURCES
// ═══════════════════════════════════════════════════════════════════════════

#[OA\Schema(
    schema: 'Club',
    description: 'A club groups loyalty cards, stamp cards, and vouchers under one brand.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]

#[OA\Schema(
    schema: 'LoyaltyCard',
    description: 'A loyalty card defines a points economy: currency, earning rate, limits, and expiry.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'club_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', description: 'Internal name'),
        new OA\Property(property: 'head', ref: '#/components/schemas/TranslatableString', nullable: true),
        new OA\Property(property: 'title', ref: '#/components/schemas/TranslatableString', nullable: true),
        new OA\Property(property: 'description', ref: '#/components/schemas/TranslatableString', nullable: true),
        new OA\Property(property: 'currency', type: 'string', minLength: 3, maxLength: 3, example: 'EUR'),
        new OA\Property(property: 'points_per_currency', type: 'number'),
        new OA\Property(property: 'currency_unit_amount', type: 'number'),
        new OA\Property(property: 'min_points_per_purchase', type: 'number'),
        new OA\Property(property: 'max_points_per_purchase', type: 'number'),
        new OA\Property(property: 'initial_bonus_points', type: 'number', nullable: true),
        new OA\Property(property: 'points_expiration_months', type: 'number'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'bg_color', type: 'string', nullable: true, example: '#4F46E5'),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]

#[OA\Schema(
    schema: 'Reward',
    description: 'A reward that members can claim by spending loyalty points. Linked to cards via pivot table — no card_id on the resource itself.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', description: 'Internal name'),
        new OA\Property(property: 'title', ref: '#/components/schemas/TranslatableString'),
        new OA\Property(property: 'description', ref: '#/components/schemas/TranslatableString', nullable: true),
        new OA\Property(property: 'points', type: 'integer', description: 'Points required to claim'),
        new OA\Property(property: 'active_from', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'expiration_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]

#[OA\Schema(
    schema: 'PartnerTransaction',
    description: 'A transaction as seen from the partner perspective. Includes staff and reward attribution.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'event', type: 'string', description: 'Transaction event type (initial_bonus_points, purchase, redemption, etc.)'),
        new OA\Property(property: 'points', type: 'integer'),
        new OA\Property(property: 'purchase_amount', type: 'number', nullable: true),
        new OA\Property(property: 'currency', type: 'string', nullable: true),
        new OA\Property(property: 'member_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'card_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'reward_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'staff_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'staff_name', type: 'string', nullable: true),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]

#[OA\Schema(
    schema: 'MemberTransaction',
    description: 'A transaction as seen from the member perspective. Omits internal IDs, includes display fields.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'card_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'card_title', type: 'string', nullable: true),
        new OA\Property(property: 'event', type: 'string', description: 'Transaction event type'),
        new OA\Property(property: 'points', type: 'integer'),
        new OA\Property(property: 'points_used', type: 'integer', nullable: true),
        new OA\Property(property: 'purchase_amount', type: 'number', nullable: true),
        new OA\Property(property: 'currency', type: 'string', nullable: true),
        new OA\Property(property: 'reward_title', type: 'string', nullable: true),
        new OA\Property(property: 'reward_points', type: 'integer', nullable: true),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]

#[OA\Schema(
    schema: 'MemberSummary',
    description: 'A member as seen from the admin perspective. Used by admin list/detail endpoints.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'unique_identifier', type: 'string', nullable: true),
        new OA\Property(property: 'locale', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'is_anonymous', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]

#[OA\Schema(
    schema: 'PartnerMemberSummary',
    description: 'A member as seen from the partner perspective. Includes more profile fields than the admin view.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'unique_identifier', type: 'string', nullable: true),
        new OA\Property(property: 'name', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'locale', type: 'string', nullable: true),
        new OA\Property(property: 'currency', type: 'string', nullable: true),
        new OA\Property(property: 'time_zone', type: 'string', nullable: true),
        new OA\Property(property: 'last_login_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'avatar', type: 'string', nullable: true),
        new OA\Property(property: 'is_anonymous', type: 'boolean'),
    ],
)]

#[OA\Schema(
    schema: 'StampCard',
    description: 'A stamp card with a fixed number of stamp slots and a reward on completion.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'club_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'head', ref: '#/components/schemas/TranslatableString', nullable: true),
        new OA\Property(property: 'title', ref: '#/components/schemas/TranslatableString', nullable: true),
        new OA\Property(property: 'description', ref: '#/components/schemas/TranslatableString', nullable: true),
        new OA\Property(property: 'reward_title', ref: '#/components/schemas/TranslatableString', nullable: true),
        new OA\Property(property: 'stamps_required', type: 'integer', minimum: 1, maximum: 100, description: 'Number of stamps needed to complete the card'),
        new OA\Property(property: 'stamps_per_purchase', type: 'integer', nullable: true, minimum: 1, maximum: 100),
        new OA\Property(property: 'max_stamps_per_transaction', type: 'integer', nullable: true),
        new OA\Property(property: 'max_stamps_per_day', type: 'integer', nullable: true),
        new OA\Property(property: 'stamps_expire_days', type: 'integer', nullable: true, minimum: 1, maximum: 365),
        new OA\Property(property: 'min_purchase_amount', type: 'number', nullable: true),
        new OA\Property(property: 'currency', type: 'string', nullable: true),
        new OA\Property(property: 'requires_physical_claim', type: 'boolean'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'bg_color', type: 'string', nullable: true),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]

#[OA\Schema(
    schema: 'Voucher',
    description: 'A voucher with a code, type (discount/reward), and optional usage limits.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'club_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'title', ref: '#/components/schemas/TranslatableString', nullable: true),
        new OA\Property(property: 'description', ref: '#/components/schemas/TranslatableString', nullable: true),
        new OA\Property(property: 'code', type: 'string'),
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'value', type: 'number', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'valid_from', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'valid_until', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]

#[OA\Schema(
    schema: 'Tier',
    description: 'A loyalty tier for VIP levels based on points thresholds.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'club_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', ref: '#/components/schemas/TranslatableString'),
        new OA\Property(property: 'description', ref: '#/components/schemas/TranslatableString', nullable: true),
        new OA\Property(property: 'level', type: 'integer', minimum: 0, description: 'Sort/rank order of the tier'),
        new OA\Property(property: 'points_threshold', type: 'integer', nullable: true, minimum: 0),
        new OA\Property(property: 'points_multiplier', type: 'number', nullable: true, minimum: 1, maximum: 100),
        new OA\Property(property: 'color', type: 'string', nullable: true, example: '#FFD700'),
        new OA\Property(property: 'icon', type: 'string', nullable: true),
        new OA\Property(property: 'is_default', type: 'boolean'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]

#[OA\Schema(
    schema: 'StaffMember',
    description: 'A staff member who can perform loyalty operations at the point of sale.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'role', type: 'integer', description: '1=Owner, 2=Manager, 3=Staff'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'created_by', type: 'string', format: 'uuid'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]

// ═══════════════════════════════════════════════════════════════════════════
// TRANSACTION RESPONSE SCHEMAS
// ═══════════════════════════════════════════════════════════════════════════

#[OA\Schema(
    schema: 'PurchaseResult',
    description: 'Result of a purchase transaction with points awarded.',
    properties: [
        new OA\Property(property: 'transaction_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'points_awarded', type: 'integer'),
        new OA\Property(property: 'member_balance', type: 'integer'),
        new OA\Property(property: 'purchase_amount', type: 'number'),
        new OA\Property(property: 'card_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'member_id', type: 'string', format: 'uuid'),
    ],
)]

#[OA\Schema(
    schema: 'RedemptionResult',
    description: 'Result of a reward redemption with points deducted.',
    properties: [
        new OA\Property(property: 'transaction_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'points_deducted', type: 'integer'),
        new OA\Property(property: 'member_balance', type: 'integer'),
        new OA\Property(property: 'reward_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'card_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'member_id', type: 'string', format: 'uuid'),
    ],
)]

// ═══════════════════════════════════════════════════════════════════════════
// MEMBER-FACING SCHEMAS
// ═══════════════════════════════════════════════════════════════════════════

#[OA\Schema(
    schema: 'MemberProfile',
    description: 'The authenticated member\'s own profile, including interaction status.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'locale', type: 'string', nullable: true),
        new OA\Property(property: 'unique_identifier', type: 'string', nullable: true),
        new OA\Property(property: 'avatar', type: 'string', nullable: true),
        new OA\Property(property: 'is_anonymous', type: 'boolean'),
        new OA\Property(property: 'has_interacted', type: 'boolean'),
        new OA\Property(property: 'first_interaction_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]

#[OA\Schema(
    schema: 'MemberCard',
    description: 'A loyalty card as seen from the member perspective, including their balance.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'title', ref: '#/components/schemas/TranslatableString'),
        new OA\Property(property: 'description', ref: '#/components/schemas/TranslatableString', nullable: true),
        new OA\Property(property: 'currency', type: 'string', example: 'points'),
        new OA\Property(property: 'balance', type: 'integer', description: 'Member\'s current point balance on this card'),
        new OA\Property(property: 'bg_color', type: 'string', nullable: true),
        new OA\Property(property: 'text_color', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
    ],
)]

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN SCHEMAS
// ═══════════════════════════════════════════════════════════════════════════

#[OA\Schema(
    schema: 'PartnerSummary',
    description: 'A partner summary as seen from the admin perspective (list endpoint).',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'locale', type: 'string', nullable: true),
        new OA\Property(property: 'currency', type: 'string', nullable: true),
        new OA\Property(property: 'time_zone', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'avatar', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]

#[OA\Schema(
    schema: 'PartnerPermissions',
    description: 'Feature flags and resource limits for a partner. Returned by detail and permission update endpoints.',
    properties: [
        new OA\Property(property: 'loyalty_cards_permission', type: 'boolean'),
        new OA\Property(property: 'loyalty_cards_limit', type: 'integer', description: '-1 = unlimited'),
        new OA\Property(property: 'stamp_cards_permission', type: 'boolean'),
        new OA\Property(property: 'stamp_cards_limit', type: 'integer'),
        new OA\Property(property: 'vouchers_permission', type: 'boolean'),
        new OA\Property(property: 'voucher_batches_permission', type: 'boolean'),
        new OA\Property(property: 'vouchers_limit', type: 'integer'),
        new OA\Property(property: 'rewards_limit', type: 'integer'),
        new OA\Property(property: 'staff_members_limit', type: 'integer'),
        new OA\Property(property: 'email_campaigns_permission', type: 'boolean'),
        new OA\Property(property: 'activity_permission', type: 'boolean'),
        new OA\Property(property: 'agent_api_permission', type: 'boolean'),
        new OA\Property(property: 'agent_keys_limit', type: 'integer'),
        new OA\Property(property: 'cards_on_homepage', type: 'boolean'),
    ],
)]

#[OA\Schema(
    schema: 'PartnerDetail',
    description: 'Full partner details including permissions and usage counts (detail endpoint).',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PartnerSummary'),
        new OA\Schema(properties: [
            new OA\Property(property: 'permissions', ref: '#/components/schemas/PartnerPermissions'),
            new OA\Property(property: 'usage', properties: [
                new OA\Property(property: 'loyalty_cards', type: 'integer'),
                new OA\Property(property: 'stamp_cards', type: 'integer'),
                new OA\Property(property: 'vouchers', type: 'integer'),
                new OA\Property(property: 'rewards', type: 'integer'),
                new OA\Property(property: 'staff_members', type: 'integer'),
            ], type: 'object'),
        ], type: 'object'),
    ],
)]

#[OA\Schema(
    schema: 'AdminMemberDetail',
    description: 'Full member details with card balances (admin detail endpoint).',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/MemberSummary'),
        new OA\Schema(properties: [
            new OA\Property(property: 'card_balances', type: 'array', items: new OA\Items(
                properties: [
                    new OA\Property(property: 'card_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'card_title', type: 'string'),
                    new OA\Property(property: 'club_name', type: 'string', nullable: true),
                    new OA\Property(property: 'balance', type: 'integer'),
                    new OA\Property(property: 'currency', type: 'string'),
                ],
                type: 'object',
            )),
        ], type: 'object'),
    ],
)]

#[OA\Schema(
    schema: 'AnalyticsOverview',
    description: 'Platform-wide analytics metrics.',
    properties: [
        new OA\Property(property: 'total_partners', type: 'integer'),
        new OA\Property(property: 'active_partners', type: 'integer'),
        new OA\Property(property: 'total_members', type: 'integer'),
        new OA\Property(property: 'total_cards', type: 'integer'),
        new OA\Property(property: 'total_stamp_cards', type: 'integer'),
        new OA\Property(property: 'total_vouchers', type: 'integer'),
        new OA\Property(property: 'transactions_today', type: 'integer'),
        new OA\Property(property: 'transactions_this_week', type: 'integer'),
        new OA\Property(property: 'transactions_this_month', type: 'integer'),
    ],
)]

// ═══════════════════════════════════════════════════════════════════════════
// HEALTH / TOOLS SCHEMAS
// ═══════════════════════════════════════════════════════════════════════════

#[OA\Schema(
    schema: 'HealthResponse',
    description: 'Health check response with key identity and capabilities.',
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'ok'),
        new OA\Property(property: 'key', properties: [
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'prefix', type: 'string'),
            new OA\Property(property: 'role', type: 'string', enum: ['partner', 'admin', 'member']),
            new OA\Property(property: 'scopes', type: 'array', items: new OA\Items(type: 'string')),
            new OA\Property(property: 'rate_limit', type: 'integer'),
            new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'last_used_at', type: 'string', format: 'date-time', nullable: true),
        ], type: 'object'),
        new OA\Property(property: 'owner', properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string', nullable: true),
            new OA\Property(property: 'type', type: 'string'),
        ], type: 'object'),
    ],
)]

class AgentOpenApiResourceSchemas {}
