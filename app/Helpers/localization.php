<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

/**
 * Transforms locale (e.g. en-us) to directory equivalent (e.g. en_US).
 *
 * @param  string  $locale  The locale string to be converted (e.g. 'en-us').
 * @return string The converted directory format (e.g. 'en_US').
 */
function locale_to_dir(string $locale): string
{
    $localeParts = explode('-', $locale);

    return $localeParts[0].'_'.strtoupper($localeParts[1]);
}

/**
 * Transforms locale directory format to URL segment format.
 *
 * This is the inverse of locale_to_dir(). Converts locale from directory/database
 * format (e.g., 'en_US', 'nl_NL') to URL segment format (e.g., 'en-us', 'nl-nl').
 * Routes in this application use lowercase hyphenated locale segments.
 *
 * @param  string  $locale  The locale identifier (e.g., 'en_US', 'nl_NL')
 * @return string The URL-formatted locale (e.g., 'en-us', 'nl-nl')
 */
function locale_for_url(string $locale): string
{
    return strtolower(str_replace('_', '-', $locale));
}
