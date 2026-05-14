<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Integration Status Enumeration
 *
 * Defines the lifecycle states for a ClubIntegration. This enum acts as a
 * state machine controlling whether webhooks are processed and points are
 * issued for connected e-commerce stores.
 *
 * State Machine:
 * ─────────────────────────────────────────────────────────────────────────────────
 *
 *                    ┌─────────────┐
 *          OAuth     │   PENDING   │
 *          Start ───▶│  (initial)  │
 *                    └──────┬──────┘
 *                           │ OAuth Success
 *                           ▼
 *                    ┌─────────────┐
 *                    │   ACTIVE    │◀──────────────┐
 *                    │ (processes) │               │
 *                    └──────┬──────┘               │
 *                           │                      │
 *           ┌───────────────┼───────────────┐      │
 *           │               │               │      │
 *           ▼               ▼               ▼      │
 *    ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
 *    │   PAUSED    │ │    ERROR    │ │DISCONNECTED │
 *    │  (manual)   │ │  (auto)     │ │ (uninstall) │
 *    └──────┬──────┘ └──────┬──────┘ └─────────────┘
 *           │               │
 *           └───────────────┴─────────▶ Reactivate
 *
 * Processing Rules:
 * ─────────────────────────────────────────────────────────────────────────────────
 * - ONLY the ACTIVE state processes webhooks and issues points
 * - PENDING: Awaiting OAuth completion, no processing
 * - PAUSED: Partner manually paused, webhooks logged but not processed
 * - ERROR: Automatic pause due to repeated failures, requires intervention
 * - DISCONNECTED: Store uninstalled the app, requires re-authorization
 *
 * @see App\Models\ClubIntegration::canProcess()
 * @see App\Models\ClubIntegration::scopeCanProcess()
 */

namespace App\Enums;

enum IntegrationStatus: string
{
    /*
    |──────────────────────────────────────────────────────────────────────────────
    | PENDING
    |──────────────────────────────────────────────────────────────────────────────
    |
    | Initial state. Integration record created, awaiting OAuth completion.
    | No webhooks are registered, no processing occurs.
    |
    | Transitions to: ACTIVE (on OAuth success)
    |
    */
    case PENDING = 'pending';

    /*
    |──────────────────────────────────────────────────────────────────────────────
    | ACTIVE
    |──────────────────────────────────────────────────────────────────────────────
    |
    | Fully operational state. This is the ONLY state that processes webhooks
    | and issues/deducts loyalty points.
    |
    | Transitions to: PAUSED, ERROR, DISCONNECTED
    |
    */
    case ACTIVE = 'active';

    /*
    |──────────────────────────────────────────────────────────────────────────────
    | PAUSED
    |──────────────────────────────────────────────────────────────────────────────
    |
    | Manually paused by the partner. Webhooks are received and logged
    | (for audit trail) but NOT processed. Useful for:
    | - Temporary maintenance
    | - Testing without affecting live data
    | - Seasonal business closures
    |
    | Transitions to: ACTIVE (manual reactivation)
    |
    */
    case PAUSED = 'paused';

    /*
    |──────────────────────────────────────────────────────────────────────────────
    | ERROR
    |──────────────────────────────────────────────────────────────────────────────
    |
    | Automatic error state. Set when:
    | - OAuth token refresh fails repeatedly
    | - Webhook processing fails repeatedly
    | - API rate limits exceeded
    |
    | Requires manual intervention to diagnose and resolve.
    |
    | Transitions to: ACTIVE (after issue resolved)
    |
    */
    case ERROR = 'error';

    /*
    |──────────────────────────────────────────────────────────────────────────────
    | DISCONNECTED
    |──────────────────────────────────────────────────────────────────────────────
    |
    | Store uninstalled the app or revoked access. The integration is
    | effectively dead until re-authorized.
    |
    | Transitions to: PENDING (new OAuth flow) or deletion
    |
    */
    case DISCONNECTED = 'disconnected';

    /**
     * Determine if this status allows webhook processing and point issuance.
     *
     * This is the critical gate for all integration operations. Only ACTIVE
     * integrations should process webhooks, issue points, or create discounts.
     *
     * @return bool True if integration can process events, false otherwise.
     */
    public function canProcess(): bool
    {
        return $this === self::ACTIVE;
    }
}
