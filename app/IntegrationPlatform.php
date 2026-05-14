<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Enums;

enum IntegrationPlatform: string
{
    case SHOPIFY = 'shopify';
    case WOOCOMMERCE = 'woocommerce';
}
