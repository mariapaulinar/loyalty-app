<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Enums;

enum IntegrationStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case ERROR = 'error';
    case DISCONNECTED = 'disconnected';

    public function canProcess(): bool
    {
        return $this === self::ACTIVE;
    }
}
