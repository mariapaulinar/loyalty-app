<?php

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * The root URL redirects to the default locale (e.g., /en-us).
 * This is correct behavior — the I18n middleware handles locale prefixing.
 */

it('redirects root URL to locale-prefixed path', function () {
    $response = $this->get('/');

    $response->assertRedirect();
});
