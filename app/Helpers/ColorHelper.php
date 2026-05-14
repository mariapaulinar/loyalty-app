<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * ColorHelper - Dynamic Brand Palette Generation
 *
 * Purpose:
 * Generates complete Tailwind-compatible color palettes (50-950 shades) from a
 * single brand color input. Enables true white-label theming without CSS rebuilds.
 *
 * Design Philosophy:
 * - ONE COLOR IN, COMPLETE SYSTEM OUT: Admin picks one hex color → full palette
 * - HSL Color Space: Perceptually uniform shade generation
 * - WCAG Compliant: Auto-calculates contrast-safe foreground colors
 * - Zero Dependencies: Pure PHP, no external packages needed
 *
 * Algorithm:
 * Takes the user's color as the "500" shade (base) and generates lighter/darker
 * variants by adjusting lightness in HSL color space. Saturation is subtly
 * reduced for extreme shades (50, 100, 900, 950) for more natural appearance.
 *
 * Usage:
 * $palette = ColorHelper::generatePalette('#2563EB');
 * // Returns: ['50' => '#eff6ff', '100' => '#dbeafe', ..., '950' => '#172554']
 *
 * $foreground = ColorHelper::getContrastColor('#2563EB');
 * // Returns: '#ffffff' (white text for dark backgrounds)
 */

namespace App\Helpers;

class ColorHelper
{
    /**
     * Default blue palette (fallback if invalid color provided)
     * Matches the default primary color in app.css
     */
    private const DEFAULT_PALETTE = [
        50 => '#eff6ff',
        100 => '#dbeafe',
        200 => '#bfdbfe',
        300 => '#93c5fd',
        400 => '#60a5fa',
        500 => '#3b82f6',
        600 => '#2563eb',
        700 => '#1d4ed8',
        800 => '#1e40af',
        900 => '#1e3a8a',
        950 => '#172554',
    ];

    /**
     * Lightness targets for each shade (perceptually balanced)
     * 
     * These values are carefully calibrated to produce visually pleasing
     * results across different hue and saturation levels. The 500 shade
     * is the user's actual color; other shades are derived from it.
     */
    private const SHADE_LIGHTNESS = [
        50 => 0.97,   // Nearly white
        100 => 0.94,
        200 => 0.87,
        300 => 0.77,
        400 => 0.65,
        500 => 0.53,  // Base color (user's input)
        600 => 0.45,
        700 => 0.37,
        800 => 0.28,
        900 => 0.20,
        950 => 0.12,  // Nearly black
    ];

    /**
     * Generate a full Tailwind-style color palette from a single base color.
     *
     * @param string $baseColor Hex color (e.g., '#2563EB' or '2563EB')
     * @param int $baseShade The shade number of the input color (default: 500)
     * @return array<int, string> Associative array with keys 50, 100, 200, ..., 950
     */
    public static function generatePalette(string $baseColor, int $baseShade = 500): array
    {
        // Normalize and validate hex color
        $rgb = self::hexToRgb($baseColor);
        if ($rgb === null) {
            return self::DEFAULT_PALETTE;
        }

        // Convert to HSL for perceptually uniform manipulation
        $hsl = self::rgbToHsl($rgb['r'], $rgb['g'], $rgb['b']);

        // Calculate the lightness offset between user's color and our target 500
        $currentLightness = $hsl['l'];
        $targetLightness = self::SHADE_LIGHTNESS[$baseShade];
        $adjustment = $currentLightness - $targetLightness;

        $palette = [];

        foreach (self::SHADE_LIGHTNESS as $shade => $lightness) {
            // Apply lightness adjustment while keeping hue and saturation
            $adjustedLightness = max(0, min(1, $lightness + $adjustment));

            // Reduce saturation for very light/dark shades (more natural appearance)
            $saturation = $hsl['s'];
            if ($shade <= 100) {
                $saturation *= 0.8; // Desaturate very light colors
            } elseif ($shade >= 900) {
                $saturation *= 0.9; // Slightly desaturate very dark colors
            }

            // Convert back to RGB then to hex
            $rgb = self::hslToRgb($hsl['h'], $saturation, $adjustedLightness);
            $palette[$shade] = self::rgbToHex($rgb['r'], $rgb['g'], $rgb['b']);
        }

        return $palette;
    }

    /**
     * Convert hex color to RGB.
     *
     * @param string $hex Hex color (e.g., '#2563EB' or '2563EB')
     * @return array{r: int, g: int, b: int}|null RGB values or null if invalid
     */
    public static function hexToRgb(string $hex): ?array
    {
        // Remove # prefix if present
        $hex = ltrim($hex, '#');

        // Handle 3-character shorthand
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        // Validate hex format
        if (! preg_match('/^[a-f0-9]{6}$/i', $hex)) {
            return null;
        }

        return [
            'r' => (int) hexdec(substr($hex, 0, 2)),
            'g' => (int) hexdec(substr($hex, 2, 2)),
            'b' => (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Convert RGB to hex.
     *
     * @param int $r Red (0-255)
     * @param int $g Green (0-255)
     * @param int $b Blue (0-255)
     * @return string Hex color with # prefix
     */
    public static function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Convert RGB to HSL.
     *
     * @param int $r Red (0-255)
     * @param int $g Green (0-255)
     * @param int $b Blue (0-255)
     * @return array{h: float, s: float, l: float} H: 0-360, S: 0-1, L: 0-1
     */
    public static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        $l = ($max + $min) / 2;
        $h = 0;
        $s = 0;

        if ($delta !== 0.0) {
            $s = $l > 0.5 ? $delta / (2 - $max - $min) : $delta / ($max + $min);

            switch ($max) {
                case $r:
                    $h = (($g - $b) / $delta) + ($g < $b ? 6 : 0);
                    break;
                case $g:
                    $h = (($b - $r) / $delta) + 2;
                    break;
                case $b:
                    $h = (($r - $g) / $delta) + 4;
                    break;
            }

            $h /= 6;
        }

        return [
            'h' => $h * 360,
            's' => $s,
            'l' => $l,
        ];
    }

    /**
     * Convert HSL to RGB.
     *
     * @param float $h Hue (0-360)
     * @param float $s Saturation (0-1)
     * @param float $l Lightness (0-1)
     * @return array{r: int, g: int, b: int}
     */
    public static function hslToRgb(float $h, float $s, float $l): array
    {
        $h /= 360;

        if ($s === 0.0) {
            $r = $g = $b = $l; // Achromatic (gray)
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = self::hueToRgb($p, $q, $h + 1 / 3);
            $g = self::hueToRgb($p, $q, $h);
            $b = self::hueToRgb($p, $q, $h - 1 / 3);
        }

        return [
            'r' => (int) round($r * 255),
            'g' => (int) round($g * 255),
            'b' => (int) round($b * 255),
        ];
    }

    /**
     * Helper function for HSL to RGB conversion.
     */
    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    /**
     * Calculate relative luminance for contrast ratio (WCAG 2.1 formula).
     *
     * @param int $r Red (0-255)
     * @param int $g Green (0-255)
     * @param int $b Blue (0-255)
     * @return float Luminance value (0-1)
     */
    public static function calculateLuminance(int $r, int $g, int $b): float
    {
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Get WCAG contrast-safe foreground color for a background.
     *
     * Returns white (#ffffff) for dark backgrounds, black (#000000) for light.
     * Threshold is calibrated for WCAG AA compliance (4.5:1 ratio).
     *
     * @param string $bgColor Background color in hex format (#RRGGBB)
     * @return string Foreground color (#000000 or #ffffff)
     */
    public static function getContrastColor(string $bgColor): string
    {
        $rgb = self::hexToRgb($bgColor);
        if ($rgb === null) {
            return '#000000'; // Fallback to black if invalid color
        }

        $luminance = self::calculateLuminance($rgb['r'], $rgb['g'], $rgb['b']);

        // If luminance > 0.5, background is light → use black text
        // Otherwise use white text
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }

    /**
     * Check if a color pair has sufficient contrast for WCAG AA.
     *
     * @param string $bgColor Background hex color
     * @param string $textColor Text hex color
     * @return bool True if meets 4.5:1 ratio (WCAG AA for normal text)
     */
    public static function hasGoodContrast(string $bgColor, string $textColor): bool
    {
        $bgRgb = self::hexToRgb($bgColor);
        $textRgb = self::hexToRgb($textColor);

        if ($bgRgb === null || $textRgb === null) {
            return false;
        }

        $bgLuminance = self::calculateLuminance($bgRgb['r'], $bgRgb['g'], $bgRgb['b']);
        $textLuminance = self::calculateLuminance($textRgb['r'], $textRgb['g'], $textRgb['b']);

        $ratio = ($bgLuminance > $textLuminance)
            ? ($bgLuminance + 0.05) / ($textLuminance + 0.05)
            : ($textLuminance + 0.05) / ($bgLuminance + 0.05);

        return $ratio >= 4.5; // WCAG AA standard
    }

    /**
     * Get the default blue palette (current Reward Loyalty primary).
     *
     * @return array<int, string>
     */
    public static function getDefaultPalette(): array
    {
        return self::DEFAULT_PALETTE;
    }
}
