<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Single source of truth for demo partner → business type mapping.
 * Used by CardSeeder, StampCardSeeder, VoucherSeeder, and RewardSeeder
 * to ensure each partner only gets content matching their business.
 *
 * Card/stamp/voucher key indices are deterministic ("best picks")
 * so screenshots and demo data are stable across resets.
 *
 * `visible_on_homepage` controls which partners' items appear on the
 * public homepage. This is explicit — not dependent on query order —
 * so demo screenshots are stable regardless of database engine or
 * insertion timing.
 */

namespace Database\Seeders\Support;

final class DemoPartnerConfig
{
    /**
     * Map partner emails to their business content type, curated content
     * picks, and homepage visibility.
     *
     * `type`               — Business vertical (subdirectory under database/data/demo/)
     * `card_key`           — Index into {type}/cards/{locale}.json
     * `stamp_key`          — Index into {type}/stamp-cards/{locale}.json (falls back to cards/)
     * `voucher_key`        — Index into {type}/vouchers/{locale}.json (null = no voucher)
     * `visible_on_homepage` — Whether this partner's cards/stamps/vouchers appear on the homepage
     *
     * @return array<string, array{type: string, card_key: int, stamp_key: int, voucher_key: int|null, visible_on_homepage: bool}>
     */
    public static function partnerConfigs(): array
    {
        return [
            // The Daily Grind — Barista Club + Latte Love + Pastry Perk
            'partner@example.com' => [
                'type' => 'cafes',
                'card_key' => 0,
                'stamp_key' => 0,
                'voucher_key' => 2,
                'visible_on_homepage' => true,
            ],
            // Basil & Thyme Kitchen — Gourmet Club + Breakfast Club + Dessert Delight
            'partner2@example.com' => [
                'type' => 'restaurants',
                'card_key' => 3,
                'stamp_key' => 18,
                'voucher_key' => 3,
                'visible_on_homepage' => true,
            ],
            // Glow Studio — Glamour Club + Style Card + First Visit Special
            'partner3@example.com' => [
                'type' => 'beauty',
                'card_key' => 3,
                'stamp_key' => 2,
                'voucher_key' => 2,
                'visible_on_homepage' => true,
            ],
            // Ironclad Fitness — Elite Fitness Club + Workout Warriors + Personal Training
            // Hidden from homepage to achieve 3-of-each grid balance.
            // Cards still exist and are accessible via direct link.
            'partner4@example.com' => [
                'type' => 'fitness',
                'card_key' => 3,
                'stamp_key' => 4,
                'voucher_key' => 1,
                'visible_on_homepage' => false,
            ],
        ];
    }

    /**
     * Get the business content type for a partner by email.
     */
    public static function businessTypeFor(string $email): string
    {
        return (self::partnerConfigs()[$email] ?? [])['type'] ?? 'restaurants';
    }

    /**
     * Get the curated card content key for a partner.
     */
    public static function cardKeyFor(string $email): int
    {
        return (self::partnerConfigs()[$email] ?? [])['card_key'] ?? 0;
    }

    /**
     * Get the curated stamp card content key for a partner.
     */
    public static function stampKeyFor(string $email): int
    {
        return (self::partnerConfigs()[$email] ?? [])['stamp_key'] ?? 0;
    }

    /**
     * Get the curated voucher content key for a partner (null = skip).
     */
    public static function voucherKeyFor(string $email): ?int
    {
        return (self::partnerConfigs()[$email] ?? [])['voucher_key'] ?? null;
    }

    /**
     * Whether this partner's items should be visible on the homepage.
     *
     * Explicit flag — not dependent on query order or partner index.
     */
    public static function isVisibleOnHomepage(string $email): bool
    {
        return (self::partnerConfigs()[$email] ?? [])['visible_on_homepage'] ?? false;
    }
}
