<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shopify Widget Service
 *
 * Powers the embedded loyalty widget on Shopify storefronts. This service handles:
 * - Widget configuration for storefront display
 * - Reward redemption with discount code/automatic discount creation
 * - Member balance and reward eligibility queries
 *
 * Widget Flow:
 * ─────────────────────────────────────────────────────────────────────────────────
 *
 *   Shopify Storefront                    This Service                    Shopify
 *         │                                     │                            │
 *    1. Widget loads ──────────────────────────►│                            │
 *         │                            getWidgetConfig()                     │
 *         │ ◄─────────── branding, rewards, earn rate ──│                    │
 *         │                                     │                            │
 *    2. Member clicks "Redeem" ────────────────►│                            │
 *         │                           redeemReward()                         │
 *         │                                     │                            │
 *    3.   │                      Create discount ───────────────────────────►│
 *         │                                     │ ◄──────────────────────────│
 *    4.   │                      Deduct points (FIFO)                        │
 *         │                                     │                            │
 *    5.   │ ◄───── discount code + apply URL ───│                            │
 *         │                                     │                            │
 *    6. Redirect to checkout                    │                            │
 *
 * Architecture Note (December 2024):
 * ─────────────────────────────────────────────────────────────────────────────────
 * Reward e-commerce settings are stored directly on the Reward model in
 * the `ecommerce_settings` JSON column. This provides a single source of truth
 * for reward configuration, eliminating the need for a separate mapping table.
 *
 * Structure: reward.ecommerce_settings.shopify = {
 *   enabled: bool,
 *   discount_type: 'percentage'|'fixed_amount'|'free_shipping',
 *   discount_value: int,
 *   discount_code_prefix: string,
 *   use_automatic_discount: bool
 * }
 *
 * @see App\Models\Reward::$ecommerce_settings
 * @see App\Models\ClubIntegration
 * @see App\Services\Integration\Shopify\ShopifyClient
 */

namespace App\Services\Integration\Shopify;

use App\Models\Card;
use App\Models\ClubIntegration;
use App\Models\Member;
use App\Models\Reward;
use App\Models\Transaction;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WidgetService
{
    public function __construct(
        private readonly ActivityLogService $activityLog
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // WIDGET CONFIGURATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get widget configuration for storefront embedding.
     *
     * Returns all configuration needed by the JavaScript widget to:
     * - Initialize and display correctly
     * - Calculate and show earn rates
     * - Display available rewards
     * - Communicate with our API
     *
     * @param  ClubIntegration  $integration  The Shopify integration
     * @param  Member|null  $member  The logged-in member (if any)
     * @return array Widget configuration
     */
    public function getWidgetConfig(ClubIntegration $integration, ?Member $member = null): array
    {
        $appUrl = config('integrations.shopify.app_url', '');

        // Build API base URL
        $apiBase = rtrim($appUrl, '/').'/api/widget';

        // Build discount apply URL template
        $shopDomain = $integration->store_identifier;
        $discountApplyUrlTemplate = "https://{$shopDomain}/discount/{code}?redirect=/checkout";

        // Get branding settings
        $branding = [
            'program_name' => $integration->getWidgetProgramName(),
            'primary_color' => $integration->getWidgetPrimaryColor(),
            'mode' => $integration->getWidgetMode(),
            'position' => $integration->getWidgetPosition(),
        ];

        // Calculate earn rate
        $earnRate = $this->calculateEarnRate($integration);

        // Get available rewards
        $rewards = $this->getAvailableRewards($integration, $member);

        // Member-specific data
        $memberData = null;
        if ($member) {
            $card = $this->getDefaultCard($integration);
            $memberData = [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'balance' => $card ? $card->getMemberBalance($member) : 0,
            ];
        }

        return [
            'api_base' => $apiBase,
            'shop_domain' => $shopDomain,
            'discount_apply_url_template' => $discountApplyUrlTemplate,
            'branding' => $branding,
            'earn_rate' => $earnRate,
            'rewards' => $rewards,
            'member' => $memberData,
            'integration_id' => $integration->id,
            'public_api_key' => $integration->public_api_key,
        ];
    }

    /**
     * Calculate the earn rate for display in the widget.
     *
     * Returns points earned per currency unit (e.g., "10 points per $1").
     *
     * @param  ClubIntegration  $integration  The integration
     * @return array{points_per_currency: int, currency: string, description: string}
     */
    private function calculateEarnRate(ClubIntegration $integration): array
    {
        $card = $this->getDefaultCard($integration);

        // Determine points per currency
        if ($integration->useCardRules() && $card) {
            $pointsPerCurrency = (int) $card->points_per_currency;
            $currency = $card->currency ?? 'USD';
        } else {
            $pointsPerCurrency = $integration->getFallbackPointsPerCurrency();
            $currency = $card?->currency ?? 'USD';
        }

        return [
            'points_per_currency' => $pointsPerCurrency,
            'currency' => $currency,
            'description' => "Earn {$pointsPerCurrency} points per {$currency} spent",
        ];
    }

    /**
     * Get available rewards for the widget.
     *
     * Returns rewards that:
     * - Belong to the card linked to this integration
     * - Have Shopify e-commerce settings enabled
     * - Are currently active
     * - Member can afford (if member provided)
     *
     * @param  ClubIntegration  $integration  The integration
     * @param  Member|null  $member  The member (for balance filtering)
     * @return array List of rewards with redemption info
     */
    private function getAvailableRewards(ClubIntegration $integration, ?Member $member = null): array
    {
        $card = $this->getDefaultCard($integration);
        if (! $card) {
            return [];
        }

        $memberBalance = ($member && $card) ? $card->getMemberBalance($member) : 0;

        // Get rewards linked to this card that have Shopify enabled
        $cardRewards = $card->rewards()
            ->where('is_active', true)
            ->where('active_from', '<=', now())
            ->where('expiration_date', '>', now())
            ->get();

        $rewards = [];

        foreach ($cardRewards as $reward) {
            // Check if Shopify is enabled for this reward
            $shopifySettings = $reward->ecommerce_settings['shopify'] ?? null;
            if (! $shopifySettings || ! ($shopifySettings['enabled'] ?? false)) {
                continue;
            }

            $canAfford = $memberBalance >= $reward->points;

            $rewards[] = [
                'id' => $reward->id,
                'title' => $reward->title,
                'description' => $reward->description,
                'points_required' => $reward->points,
                'discount_type' => $shopifySettings['discount_type'] ?? 'percentage',
                'discount_value' => $shopifySettings['discount_value'] ?? 0,
                'can_afford' => $canAfford,
                'image' => $reward->image1,
            ];
        }

        return $rewards;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REWARD REDEMPTION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Redeem a reward for a member, creating a discount and deducting points.
     *
     * Flow:
     * 1. Validate member has sufficient points
     * 2. Create Shopify discount (automatic or code)
     * 3. Deduct points using FIFO from oldest transactions
     * 4. Create redemption transaction record
     * 5. Audit the redemption
     *
     * On failure at step 3-4, the discount is deleted to maintain consistency.
     *
     * @param  ClubIntegration  $integration  The Shopify integration
     * @param  Member  $member  The member redeeming
     * @param  Reward  $reward  The reward being redeemed
     * @return array{success: bool, discount?: array, error?: string}
     *
     * @throws \Exception On unrecoverable errors
     */
    public function redeemReward(
        ClubIntegration $integration,
        Member $member,
        Reward $reward
    ): array {
        // Get Shopify settings from the reward
        $shopifySettings = $reward->ecommerce_settings['shopify'] ?? null;
        if (! $shopifySettings || ! ($shopifySettings['enabled'] ?? false)) {
            return ['success' => false, 'error' => 'Reward not configured for Shopify'];
        }

        $card = $this->getDefaultCard($integration);
        if (! $card) {
            return ['success' => false, 'error' => 'No card configured for this integration'];
        }

        // Check member balance
        $balance = $card->getMemberBalance($member);
        if ($balance < $reward->points) {
            return [
                'success' => false,
                'error' => 'Insufficient points',
                'required' => $reward->points,
                'available' => $balance,
            ];
        }

        // Determine if we should use automatic discount
        $useAutomatic = $integration->useAutomaticDiscounts()
            && ($shopifySettings['use_automatic_discount'] ?? true);

        $discountRef = null;
        $discountCode = null;
        $applyUrl = null;

        try {
            // Create Shopify client
            $client = new ShopifyClient($integration);

            // Create the discount on Shopify
            $discountRef = $this->createDiscount(
                $client,
                $shopifySettings,
                $reward,
                $useAutomatic
            );

            // Build the discount code and apply URL
            if ($discountRef['kind'] === 'code') {
                $discountCode = $discountRef['code'];
                $applyUrl = "https://{$integration->store_identifier}/discount/{$discountCode}?redirect=/checkout";
            } else {
                // Automatic discounts don't need a code
                $discountCode = null;
                $applyUrl = "https://{$integration->store_identifier}/checkout";
            }

            // Deduct points and create transaction
            DB::beginTransaction();

            try {
                $transaction = $this->deductPointsForReward($member, $card, $reward, $discountRef);

                DB::commit();

                // Audit successful redemption
                $this->activityLog->log(
                    "Widget reward redeemed: {$reward->title}",
                    $integration,
                    'integration.shopify.widget.redeem',
                    [
                        'member_id' => $member->id,
                        'reward_id' => $reward->id,
                        'points_deducted' => $reward->points,
                        'discount_kind' => $discountRef['kind'],
                        'discount_code' => $discountCode,
                    ]
                );

                $this->log('info', 'Reward redeemed via widget', [
                    'integration_id' => $integration->id,
                    'member_id' => $member->id,
                    'reward_id' => $reward->id,
                    'discount_kind' => $discountRef['kind'],
                ]);

                return [
                    'success' => true,
                    'discount' => [
                        'kind' => $discountRef['kind'],
                        'code' => $discountCode,
                        'apply_url' => $applyUrl,
                        'type' => $shopifySettings['discount_type'],
                        'value' => $shopifySettings['discount_value'],
                    ],
                    'transaction_id' => $transaction->id,
                    'new_balance' => $card->getMemberBalance($member),
                ];

            } catch (\Exception $e) {
                DB::rollBack();

                // Delete the discount since points weren't deducted
                $this->deleteDiscountSafely($client, $discountRef);

                throw $e;
            }

        } catch (ShopifyApiException $e) {
            $this->log('error', 'Failed to create discount for redemption', [
                'integration_id' => $integration->id,
                'member_id' => $member->id,
                'reward_id' => $reward->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create discount: '.$e->getMessage(),
            ];

        } catch (\Exception $e) {
            $this->log('error', 'Reward redemption failed', [
                'integration_id' => $integration->id,
                'member_id' => $member->id,
                'reward_id' => $reward->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Redemption failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Create a Shopify discount for the reward.
     *
     * @param  ShopifyClient  $client  The API client
     * @param  array  $shopifySettings  The reward's Shopify settings
     * @param  Reward  $reward  The core reward
     * @param  bool  $useAutomatic  Whether to use automatic discount
     * @return array Discount reference
     *
     * @throws ShopifyApiException On API failure
     */
    private function createDiscount(
        ShopifyClient $client,
        array $shopifySettings,
        Reward $reward,
        bool $useAutomatic
    ): array {
        $discountType = $shopifySettings['discount_type'] ?? 'percentage';
        $discountValue = $shopifySettings['discount_value'] ?? 10;
        $codePrefix = $shopifySettings['discount_code_prefix'] ?? 'REWARD';

        // For percentage discounts, value is stored as integer (10 = 10%)
        // For fixed_amount discounts, value is stored in cents
        $value = $discountType === 'percentage'
            ? (float) $discountValue
            : (float) $discountValue / 100; // Convert cents to dollars

        // Generate a unique title/code
        $uniqueId = strtoupper(Str::random(8));
        $title = "{$codePrefix}-{$uniqueId}";

        // Set discount to expire in 24 hours
        $expiresAt = now()->addHours(24);

        if ($useAutomatic) {
            return $client->createAutomaticDiscount(
                title: $title,
                type: $discountType,
                value: $value,
                startsAt: now(),
                endsAt: $expiresAt
            );
        }

        return $client->createDiscountCode(
            code: $title,
            type: $discountType,
            value: $value,
            usageLimit: 1,
            startsAt: now(),
            endsAt: $expiresAt
        );
    }

    /**
     * Safely delete a discount, ignoring errors.
     *
     * Used for cleanup when point deduction fails.
     *
     * @param  ShopifyClient  $client  The API client
     * @param  array  $discountRef  The discount reference
     */
    private function deleteDiscountSafely(ShopifyClient $client, array $discountRef): void
    {
        try {
            $client->deleteDiscount($discountRef);
        } catch (\Exception $e) {
            $this->log('warning', 'Failed to cleanup discount after failed redemption', [
                'discount_ref' => $discountRef,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Deduct points from member's balance for reward redemption.
     *
     * Uses FIFO (First-In-First-Out) to deduct from oldest transactions first,
     * then creates a redemption transaction record.
     *
     * @param  Member  $member  The member
     * @param  Card  $card  The card
     * @param  Reward  $reward  The reward being redeemed
     * @param  array  $discountRef  Reference to the created discount
     * @return Transaction The created transaction
     */
    private function deductPointsForReward(
        Member $member,
        Card $card,
        Reward $reward,
        array $discountRef
    ): Transaction {
        $now = Carbon::now('UTC');

        // FIFO deduction from oldest transactions
        $transactions = Transaction::where('member_id', $member->id)
            ->where('card_id', $card->id)
            ->where('expires_at', '>', $now)
            ->orderBy('created_at', 'asc')
            ->get();

        $remainingPoints = $reward->points;

        foreach ($transactions as $transaction) {
            $available = $transaction->points - $transaction->points_used;

            if ($available <= 0) {
                continue;
            }

            $toDeduct = min($remainingPoints, $available);
            $transaction->points_used += $toDeduct;
            $transaction->save();

            $remainingPoints -= $toDeduct;

            if ($remainingPoints <= 0) {
                break;
            }
        }

        if ($remainingPoints > 0) {
            throw new \RuntimeException('Insufficient points available for redemption');
        }

        // Create the redemption transaction
        $transactionData = [
            'member_id' => $member->id,
            'card_id' => $card->id,
            'reward_id' => $reward->id,
            'points' => -$reward->points,
            'event' => 'shopify_widget_redemption',
            'note' => "Shopify widget redemption: {$reward->title}",
            'card_title' => $card->getTranslations('head'),
            'reward_title' => $reward->getTranslations('title'),
            'reward_points' => $reward->points,
            'currency' => $card->currency,
            'points_per_currency' => $card->points_per_currency,
            'min_points_per_purchase' => $card->min_points_per_purchase,
            'max_points_per_purchase' => $card->max_points_per_purchase,
            'meta' => [
                'source' => 'shopify_widget',
                'discount_kind' => $discountRef['kind'],
                'discount_id' => $discountRef['id'] ?? $discountRef['discount_code_id'] ?? null,
                'discount_code' => $discountRef['code'] ?? null,
            ],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $transaction = Transaction::create($transactionData);

        // Update card stats
        $card->number_of_points_redeemed += $reward->points;
        $card->number_of_rewards_redeemed += 1;
        $card->last_reward_redeemed_at = $now;
        $card->save();

        // Update reward stats
        $reward->offsetUnset('images');
        $reward->number_of_times_redeemed += 1;
        $reward->save();

        return $transaction;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MEMBER OPERATIONS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get member's current balance for the widget.
     *
     * @param  ClubIntegration  $integration  The integration
     * @param  Member  $member  The member
     * @return array{balance: int, currency: string}
     */
    public function getMemberBalance(ClubIntegration $integration, Member $member): array
    {
        $card = $this->getDefaultCard($integration);

        return [
            'balance' => $card ? $card->getMemberBalance($member) : 0,
            'currency' => $card?->currency ?? 'USD',
        ];
    }

    /**
     * Get member's transaction history for the widget.
     *
     * @param  ClubIntegration  $integration  The integration
     * @param  Member  $member  The member
     * @param  int  $limit  Maximum transactions to return
     * @return array List of transactions
     */
    public function getMemberTransactions(
        ClubIntegration $integration,
        Member $member,
        int $limit = 20
    ): array {
        $card = $this->getDefaultCard($integration);

        if (! $card) {
            return [];
        }

        $transactions = Transaction::where('member_id', $member->id)
            ->where('card_id', $card->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'points' => $transaction->points,
                'event' => $transaction->event,
                'note' => $transaction->note,
                'created_at' => $transaction->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the default card for this integration's club.
     *
     * Uses the card_id from integration settings if set,
     * otherwise falls back to the first active card.
     *
     * @param  ClubIntegration  $integration  The integration
     * @return Card|null The default card or null
     */
    private function getDefaultCard(ClubIntegration $integration): ?Card
    {
        // First try the explicitly configured card
        $cardId = $integration->settings['card_id'] ?? null;
        if ($cardId) {
            $card = Card::find($cardId);
            if ($card && $card->is_active) {
                return $card;
            }
        }

        // Fall back to first active card in the club
        $club = $integration->club;

        if (! $club) {
            return null;
        }

        return Card::where('club_id', $club->id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Log a message with service context.
     */
    private function log(string $level, string $message, array $context = []): void
    {
        Log::log($level, "[Shopify Widget] {$message}", $context);
    }
}
