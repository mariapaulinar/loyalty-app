<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */
it('moneyFormat does not throw when currency is null', function () {
    $formatted = moneyFormat(10.5, null, 'en_US');

    expect($formatted)->toBeString()->not->toBeEmpty();
});

it('money does not throw when currency is null', function () {
    $formatted = money(1050, null, 'en_US');

    expect($formatted)->toBeString()->not->toBeEmpty();
});
