<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

/**
 * Format bytes to human-readable units (KB, MB, GB, TB).
 *
 * @param  int  $size  The size in bytes to be formatted.
 * @param  int  $precision  The number of decimal places to display (default: 2).
 * @return string The formatted size with the appropriate unit.
 */
function formatBytes(int $size, int $precision = 2): string
{
    if ($size > 0) {
        $base = log($size, 1024);
        $suffixes = ['bytes', 'KB', 'MB', 'GB', 'TB'];

        return round(pow(1024, $base - floor($base)), $precision).$suffixes[floor($base)];
    } else {
        return '0 bytes';
    }
}

/**
 * Calculate the greatest common divisor (GCD) of two numbers.
 *
 * This function uses the Euclidean algorithm to find the largest number
 * that divides both of the given numbers without leaving a remainder.
 *
 * @param  int  $a  The first number.
 * @param  int  $b  The second number.
 * @return int The greatest common divisor of $a and $b.
 */
function gcd($a, $b)
{
    return ($a % $b) ? gcd($b, $a % $b) : $b;
}

/**
 * Get the native symbol for a currency code.
 *
 * Returns the native symbol (e.g., '€', 'R', '¥') for display purposes.
 * Uses a static cache to avoid repeated lookups on listing pages.
 *
 * @param  string|null  $currency  ISO 4217 currency code. Falls back to config default.
 * @return string The native currency symbol, or the ISO code itself as fallback.
 */
function currencySymbol(?string $currency = null): string
{
    $currency = $currency ?? config('default.currency', 'USD');

    static $cache = [];
    if (! isset($cache[$currency])) {
        $cache[$currency] = \App\Models\Currency::find($currency)?->symbol_native ?? $currency;
    }

    return $cache[$currency];
}

/**
 * Format a monetary amount with currency symbol.
 *
 * Converts an amount (in cents/smallest currency unit) to a formatted
 * string with the appropriate currency symbol and decimal places.
 *
 * @param  int|float|null  $amount  The amount in cents (smallest currency unit).
 * @param  string|null  $currency  The ISO 4217 currency code (e.g., 'USD', 'EUR', 'GBP'). Falls back to 'USD' if null.
 * @param  string|null  $locale  The locale to use for formatting (defaults to app locale).
 * @return string The formatted monetary amount with currency symbol.
 */
function money(int|float|null $amount, ?string $currency = 'USD', ?string $locale = null): string
{
    if ($amount === null) {
        $amount = 0;
    }

    // Convert from cents to actual amount
    $actualAmount = $amount / 100;

    // Fall back to USD if currency is null
    $currency = $currency ?? 'USD';

    // Use app locale if none provided
    $locale = $locale ?? app()->getLocale();

    // Format using NumberFormatter for proper currency display
    $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

    $formatted = $formatter->formatCurrency($actualAmount, $currency);

    // Replace ISO code with native symbol when formatter falls back
    if (str_contains($formatted, $currency)) {
        $formatted = str_replace($currency, currencySymbol($currency), $formatted);
    }

    return $formatted;
}

/**
 * Format a monetary amount (already in currency units) with currency symbol.
 *
 * Formats a decimal currency amount without conversion. Use this when your
 * database stores values as DECIMAL (e.g., 10.50) instead of cents (1050).
 *
 * @param  int|float|null  $amount  The amount in currency units (e.g., 10.50 for $10.50).
 * @param  string|null  $currency  The ISO 4217 currency code (e.g., 'USD', 'EUR', 'GBP'). Falls back to config default.
 * @param  string|null  $locale  The locale to use for formatting (defaults to app locale).
 * @return string The formatted monetary amount with currency symbol.
 */
function moneyFormat(int|float|null $amount, ?string $currency = null, ?string $locale = null): string
{
    if ($amount === null) {
        $amount = 0;
    }
    // Fall back to config default, then USD as safety net
    $currency = $currency ?? config('default.currency');
    // Use app locale if none provided
    $locale = $locale ?? app()->getLocale();
    // Format using NumberFormatter for proper currency display
    $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
    $formatted = $formatter->formatCurrency($amount, $currency);

    // Replace ISO code with native symbol when formatter falls back
    if (str_contains($formatted, $currency)) {
        $formatted = str_replace($currency, currencySymbol($currency), $formatted);
    }

    // Replace non-breaking space with regular space for proper text wrapping
    return str_replace("\u{00A0}", ' ', $formatted);
}

/**
 * Format a monetary amount with styled cents (HTML output).
 *
 * Wraps the decimal portion in a <sup> element for premium typography
 * where cents appear smaller and top-aligned.
 *
 * Output contains raw HTML. Use {!! !!} in Blade templates.
 *
 * @param  int|float|null  $amount  The amount in currency units (e.g., 10.50).
 * @param  string|null  $currency  ISO 4217 currency code.
 * @param  string|null  $locale  Locale for formatting (defaults to app locale).
 * @return string HTML string with cents wrapped in <sup class="money-cents">.
 */
function moneyFormatStyled(int|float|null $amount, ?string $currency = null, ?string $locale = null): string
{
    $formatted = moneyFormat($amount, $currency, $locale);

    // Determine the locale's decimal separator
    $locale = $locale ?? app()->getLocale();
    $fmt = new NumberFormatter($locale, NumberFormatter::DECIMAL);
    $sep = $fmt->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

    // Match decimal separator + following digits using \p{Nd} (Unicode decimal
    // digits) so locales with non-ASCII numerals (ar_SA → ٠١٢٣, hi_IN → ०१२३)
    // are handled correctly alongside Latin 0-9.
    $pattern = '/' . preg_quote($sep, '/') . '(\p{Nd}+)/u';

    return preg_replace_callback($pattern, function ($matches) {
        // Transliterate to ASCII for the zero-check — PHP's (int) cast only
        // parses Latin digits, so "٠٠" would become 0 by accident but "٩٩"
        // would also become 0. Use transliterator for correctness.
        $asciiDigits = transliterator_transliterate('Any-Latin; Latin-ASCII', $matches[1]);

        // Strip .00 / ,00 / ٫٠٠ entirely for cleaner round-amount display
        if ((int) $asciiDigits === 0) {
            return '';
        }

        return '<sup class="money-cents">' . $matches[0] . '</sup>';
    }, $formatted);
}

/**
 * Format a monetary amount from minor units (cents) with styled cents (HTML output).
 *
 * Combines the cents→units conversion of money() with the styled HTML output
 * of moneyFormatStyled(). Use this for pricing surfaces where amounts are
 * stored in cents (config/plans.php) and need premium typography.
 *
 * ISO 4217-aware: respects fraction digits for zero-decimal currencies
 * (e.g., JPY, KRW) and non-standard subdivisions.
 *
 * Output contains raw HTML. Use {!! !!} in Blade templates.
 *
 * @param  int|float|null  $amount   The amount in minor units (cents).
 * @param  string|null     $currency ISO 4217 currency code (e.g., 'USD', 'JPY').
 * @param  string|null     $locale   Locale for formatting (defaults to app locale).
 * @return string HTML string with round amounts cleaned and cents in <sup>.
 */
function moneyStyled(int|float|null $amount, ?string $currency = null, ?string $locale = null): string
{
    if ($amount === null) {
        $amount = 0;
    }

    // Determine ISO 4217 fraction digits for the currency
    $currency = $currency ?? config('default.currency', 'USD');
    $locale = $locale ?? app()->getLocale();
    $fmt = new NumberFormatter($locale . '@currency=' . $currency, NumberFormatter::CURRENCY);
    $fractionDigits = $fmt->getAttribute(NumberFormatter::FRACTION_DIGITS);

    // Convert from minor units to major units using ISO fraction digits
    // e.g., USD: 2900 / 10^2 = 29.00; JPY: 2900 / 10^0 = 2900
    $divisor = pow(10, $fractionDigits);
    $actualAmount = $amount / $divisor;

    return moneyFormatStyled($actualAmount, $currency, $locale);
}
