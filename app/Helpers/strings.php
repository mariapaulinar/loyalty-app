<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

use Illuminate\Support\Str;

/**
 * Sanitize a string by removing HTML tags, double quotes, breaks, and white spaces.
 * Useful for processing attributes like meta description and title data.
 *
 * @param  null|string  $string  The input string to be sanitized.
 * @param  int  $maxLength  The maximum length of the resulting string. Set to 0 for no limit.
 * @param  string  $suffix  The suffix to append if the string is trimmed. Default is '...'.
 * @return string The sanitized string.
 */
function parse_attr($string, int $maxLength = 0, string $suffix = '...'): string
{
    // Sanitize string
    $string = html_entity_decode($string);
    $string = strip_tags($string);
    $string = preg_replace('/\r|\n/', ' ', $string);
    $string = preg_replace('/\s+/', ' ', $string);
    $string = str_replace('"', '&quot;', $string);
    $string = trim($string);

    // Trim
    if ($maxLength > 0) {
        $string = Str::limit($string, $maxLength, $suffix);
    }

    return $string;
}

/**
 * Partially hide an email address.
 *
 * @param  string  $email  The email address to be partially hidden.
 * @return string|null The partially hidden email address, or the input string if not a valid email address.
 */
function hideEmailAddress(string $email): ?string
{
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        [$first, $last] = explode('@', $email);

        // Ensure the first part of the email is at least 3 characters long
        if (strlen($first) > 3) {
            $first = substr($first, 0, 3).str_repeat('*', strlen($first) - 3);
        } else {
            $first = str_repeat('*', strlen($first));
        }

        $last = explode('.', $last);
        $last_domain = $last[0];

        // Ensure the last part of the domain is at least 1 character long
        if (strlen($last_domain) > 1) {
            $last_domain = substr($last_domain, 0, 1).str_repeat('*', strlen($last_domain) - 1);
        } else {
            $last_domain = str_repeat('*', strlen($last_domain));
        }

        return $first.'@'.$last_domain.'.'.$last[1];
    }

    return $email;
}

/**
 * Generate a unique code.
 *
 * @param  int  $limit  The length of the unique code to generate.
 * @return string The unique code.
 */
function unique_code(int $limit): string
{
    if ($limit <= 0) {
        return '';
    }

    return Str::lower(Str::random($limit));
}

/**
 * Generate a short, url-friendly code based on date time (Y-m-d H:i:s).
 *
 * @param  string  $publishedAt  A string representing a date and time, formatted as 'Y-m-d H:i:s'.
 * @return string The base32 encoded string based on the input date and time.
 */
function atomSlug(string $publishedAt): string
{
    $timestamp = strtotime($publishedAt);
    $hex = dechex($timestamp);
    $base32_chars = 'abcdefghijklmnopqrstuvwxyz234567';
    $result = '';
    $n = 0;
    $l = 0;

    for ($i = 0, $len = strlen($hex); $i < $len; $i++) {
        $n = $n << 4;
        $n = $n + hexdec($hex[$i]);
        $l += 4;

        while ($l >= 5) {
            $l -= 5;
            $result .= $base32_chars[$n >> $l];
            $n &= (1 << $l) - 1;
        }
    }

    if ($l > 0) {
        $result .= $base32_chars[$n << (5 - $l)];
    }

    return $result;
}

/**
 * Shorten a UUID by removing dashes.
 *
 * This function takes a UUID as input and removes the dashes to create a shortened version.
 * The shortened UUID can be recognized by applications that are aware of the UUID format.
 * The original UUID can be reconstructed by adding dashes at the original positions.
 *
 * Note: This function does not encrypt the UUID, and it is still possible to obtain the
 * original UUID by knowing the format.
 *
 * @param  string  $uuid  The input UUID to shorten.
 * @return string The shortened UUID.
 */
function shortenUUID(string $uuid): string
{
    return substr($uuid, 0, 8).substr($uuid, 9, 4).substr($uuid, 14, 4).substr($uuid, 19, 4).substr($uuid, 24);
}

/**
 * Expand a shortened UUID by adding dashes.
 *
 * This function takes a shortened UUID as input and adds dashes to create the original UUID format.
 * The function adds dashes at the same positions as in the original UUID.
 *
 * Note: This function only expands the UUID; it does not validate if the input is a valid shortened UUID.
 *
 * @param  string  $shortUUID  The input shortened UUID to expand.
 * @return string The expanded UUID.
 */
function expandUUID(string $shortUUID): string
{
    return substr($shortUUID, 0, 8).'-'.substr($shortUUID, 8, 4).'-'.substr($shortUUID, 12, 4).'-'.substr($shortUUID, 16, 4).'-'.substr($shortUUID, 20);
}
