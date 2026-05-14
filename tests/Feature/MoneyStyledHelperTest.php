<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Tests for moneyStyled() and moneyFormatStyled() helpers.
 *
 * Validates ISO 4217-aware formatting across:
 * - Standard 2-decimal currencies (USD, EUR)
 * - Zero-decimal currencies (JPY, KRW)
 * - 3-decimal currencies (KWD)
 * - Non-ASCII digit locales (ar_SA)
 * - Round vs. non-round amounts
 */

describe('moneyStyled — minor units to styled HTML', function () {

    it('strips .00 for round USD amounts', function () {
        $result = moneyStyled(2900, 'USD', 'en_US');
        expect($result)->toBe('$29');
    });

    it('wraps non-round USD cents in <sup>', function () {
        $result = moneyStyled(1999, 'USD', 'en_US');
        expect($result)->toContain('<sup class="money-cents">');
        expect($result)->toContain('.99');
        expect($result)->toContain('$19');
    });

    it('handles zero-decimal JPY without decimals', function () {
        $result = moneyStyled(2900, 'JPY', 'en_US');
        expect($result)->not->toContain('.');
        expect($result)->toContain('2,900');
    });

    it('handles 3-decimal KWD correctly', function () {
        $result = moneyStyled(1999, 'KWD', 'en_US');
        expect($result)->toContain('<sup class="money-cents">');
        expect($result)->toContain('.999');
    });

    it('strips round KWD decimals', function () {
        $result = moneyStyled(2000, 'KWD', 'en_US');
        expect($result)->not->toContain('<sup');
        expect($result)->not->toContain('.000');
    });

    it('strips EUR ,00 in de_DE locale', function () {
        $result = moneyStyled(7900, 'EUR', 'de_DE');
        expect($result)->toContain('79');
        expect($result)->not->toContain(',00');
    });

    it('styles EUR non-round in de_DE locale', function () {
        $result = moneyStyled(1999, 'EUR', 'de_DE');
        expect($result)->toContain('<sup class="money-cents">');
        expect($result)->toContain(',99');
    });

    it('strips Arabic-Indic decimals for round USD in ar_SA', function () {
        $result = moneyStyled(2900, 'USD', 'ar_SA');
        // Must NOT contain the Arabic decimal separator + zero digits
        expect($result)->not->toContain('٫٠٠');
        // Should contain Arabic-Indic 29
        expect($result)->toContain('٢٩');
    });

    it('styles Arabic-Indic decimals for non-round USD in ar_SA', function () {
        $result = moneyStyled(1999, 'USD', 'ar_SA');
        expect($result)->toContain('<sup class="money-cents">');
        // Arabic decimal separator + Arabic digits
        expect($result)->toContain('٫٩٩');
    });

    it('returns $0 for null amount', function () {
        $result = moneyStyled(null, 'USD', 'en_US');
        expect($result)->toBe('$0');
    });

    it('returns $0 for zero amount', function () {
        $result = moneyStyled(0, 'USD', 'en_US');
        expect($result)->toBe('$0');
    });
});

describe('moneyFormatStyled — currency units to styled HTML', function () {

    it('strips .00 for round amounts', function () {
        $result = moneyFormatStyled(29, 'USD', 'en_US');
        expect($result)->toBe('$29');
    });

    it('styles non-round amounts', function () {
        $result = moneyFormatStyled(19.99, 'USD', 'en_US');
        expect($result)->toContain('<sup class="money-cents">');
        expect($result)->toContain('.99');
    });

    it('handles Arabic locale for round amounts', function () {
        $result = moneyFormatStyled(29, 'USD', 'ar_SA');
        expect($result)->not->toContain('٫٠٠');
    });

    it('handles Arabic locale for non-round amounts', function () {
        $result = moneyFormatStyled(19.99, 'USD', 'ar_SA');
        expect($result)->toContain('<sup class="money-cents">');
        expect($result)->toContain('٫٩٩');
    });
});
