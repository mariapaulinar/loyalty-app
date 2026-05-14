<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Handles operations related to transactions including point issuance,
 * reward redemption, and code redemption.
 *
 * Design Tenets:
 * - **Tier Integration**: Applies tier multipliers to point calculations
 * - **Event-driven**: Triggers tier evaluation after transactions
 */

namespace App\Services\Card;

use App\Models\Analytic;
use App\Models\Card;
use App\Models\Member;
use App\Models\Partner;
use App\Models\PointCode;
use App\Models\PointRequest;
use App\Models\Reward;
use App\Models\Staff;
use App\Models\Transaction;
use App\Notifications\Member\PointsReceived;
use App\Notifications\Member\PointsReceivedFromMember;
use App\Notifications\Member\RewardClaimed;
use App\Services\Member\MemberService;
use App\Services\TierService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Parser\DecimalMoneyParser;

/**
 * Class TransactionService
 *
 * Handles operations related to transactions.
 */
class TransactionService
{
    /**
     * @var MemberService
     */
    protected $memberService;

    /**
     * @var CardService
     */
    protected $cardService;

    /**
     * @var RewardService
     */
    protected $rewardService;

    /**
     * @var AnalyticsService
     */
    protected $analyticsService;

    /**
     * @var TierService
     */
    protected $tierService;

    /**
     * TransactionService constructor.
     */
    public function __construct(
        MemberService $memberService,
        CardService $cardService,
        RewardService $rewardService,
        AnalyticsService $analyticsService,
        TierService $tierService
    ) {
        $this->memberService = $memberService;
        $this->cardService = $cardService;
        $this->rewardService = $rewardService;
        $this->analyticsService = $analyticsService;
        $this->tierService = $tierService;
    }

    /**
     * Retrieves transactions of a given member for a specific card.
     *
     * This function fetches all transactions by default. However,
     * if $showExpiredAndUsedTransactions is set to false, the returned collection will
     * exclude transactions of events 'initial_bonus_points', 'staff_credited_points_for_purchase'
     * and `staff_credited_points` where points have either expired or have been fully used.
     *
     * @param  Member  $member  The member associated with the transactions.
     * @param  Card  $card  The card associated with the transactions.
     * @param  bool  $showExpiredAndUsedTransactions  Determines whether to include transactions
     *                                                where points have expired or been fully used. Default is true.
     * @return Collection The collection of relevant Transaction instances.
     */
    public function findTransactionsOfMemberForCard(
        Member $member,
        Card $card,
        bool $showExpiredAndUsedTransactions = true,
        int $limit = 50
    ): Collection {
        // Define the query to retrieve all transactions for the given member and card.
        $query = Transaction::where('member_id', $member->id)
            ->where('card_id', $card->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        // If expired and fully used transactions should be excluded, adjust the query.
        if ($showExpiredAndUsedTransactions == false) {
            $query->where(function ($query) use ($member, $card) {
                // Select transactions where the expiry date is in the future
                // and not all points have been used.
                $query->where('expires_at', '>=', Carbon::now())
                    ->whereColumn('points', '>', 'points_used')
                    ->where('member_id', $member->id)
                    ->where('card_id', $card->id);
                // Only apply the above conditions to these specific event types.
            })->whereIn('event', ['initial_bonus_points', 'staff_credited_points_for_purchase', 'staff_credited_points'])
                ->orWhereNotIn('event', ['initial_bonus_points', 'staff_credited_points_for_purchase', 'staff_credited_points'])
                ->where('member_id', $member->id)
                ->where('card_id', $card->id);
        }

        // Execute the query and return the collection of transactions.
        return $query->with('staff')->get();
    }

    /**
     * Adds a purchase or points to the system, creating a new Transaction record.
     *
     * @param  string  $member_identifier  The identifier for the member.
     * @param  string  $card_identifier  The identifier for the card.
     * @param  Staff  $staff  The staff user adding the purchase.
     * @param  float|null  $purchase_amount  The amount of the purchase, or null.
     * @param  float|null  $points  The number of points to add, or null.
     * @param  UploadedFile|null  $image  The uploaded image file associated with the transaction.
     * @param  string|null  $note  Any notes to attach to the transaction.
     * @param  bool  $points_only  Determines if the transaction is points-only.
     * @param  string|null  $created_at  The date and time of the transaction.
     * @return Transaction The transaction that was created.
     */
    public function addPurchase(
        string $member_identifier,
        string $card_identifier,
        Staff $staff,
        ?float $purchase_amount,
        ?float $points,
        ?UploadedFile $image,
        ?string $note,
        bool $points_only,
        ?string $created_at = null
    ): Transaction {
        // Fetch member and card details
        $member = $this->memberService->findActiveByIdentifier($member_identifier);
        $card = $this->cardService->findActiveCardByIdentifier($card_identifier);
        $partner = $card->partner;
        $created_at = $created_at ?? Carbon::now('UTC');

        // Check if staff has access to card
        if (! $staff->isRelatedToCard($card)) {
            abort(401);
        }

        // Set expiration date based on date of creation (use UTC to avoid DST issues)
        $expires_at = (! $created_at instanceof Carbon) ? Carbon::parse($created_at, 'UTC') : $created_at->copy()->setTimezone('UTC');
        $expires_at = $expires_at->addMonths((int) $card->points_expiration_months)->startOfDay()->addHours(12);

        // Data for transaction record
        $data = [
            'staff_id' => $staff->id,
            'member_id' => $member->id,
            'card_id' => $card->id,
            'partner_name' => $partner->name,
            'partner_email' => $partner->email,
            'staff_name' => $staff->name,
            'staff_email' => $staff->email,
            'card_title' => $card->getTranslations('head'),
            'currency' => $card->currency,
            'points_per_currency' => $card->points_per_currency,
            'meta' => [
                'round_points_up' => $card->meta && is_array($card->meta) && isset($card->meta['round_points_up']) ? (bool) $card->meta['round_points_up'] : true,
            ],
            'min_points_per_purchase' => $card->min_points_per_purchase,
            'max_points_per_purchase' => $card->max_points_per_purchase,
            'expires_at' => $expires_at->format('Y-m-d H:i:s'),
            'created_by' => $partner->id,
        ];

        // Get tier multiplier for this member and club
        $club = $card->club;
        $tierMultiplier = 1.00;
        $basePoints = 0;

        if ($club) {
            // Ensure member has a tier assigned
            $this->tierService->ensureMemberHasTier($member, $club);
            $tierMultiplier = $this->tierService->getMultiplierForMember($member, $club);
        }

        if ($points_only) {
            $basePoints = (int) $points;
            // Apply tier multiplier to manually issued points
            $points = (int) round($basePoints * $tierMultiplier);
            $number_of_points_issued = $points;
            $data['purchase_amount'] = null;
            $purchase_amount_parsed = 0;
        } else {
            $purchase_amount_parsed = $this->parsePurchaseAmount($card, (float) $purchase_amount);
            $basePoints = $points !== null
                ? (int) $points
                : $card->calculatePoints((float) $purchase_amount);
            // Apply tier multiplier
            $points = (int) round($basePoints * $tierMultiplier);
            $number_of_points_issued = $points;
            $data['purchase_amount'] = $purchase_amount_parsed;

            if ($points !== null) {
                $data['meta']['manual_points_override'] = true;
            }
        }

        // Store tier info in meta for audit trail
        if ($tierMultiplier > 1.00) {
            $data['meta']['tier_multiplier'] = $tierMultiplier;
            $data['meta']['base_points'] = $basePoints;
        }

        // Check if this is first transaction and if bonus points are configured
        if ($card->initial_bonus_points && ! Transaction::where('member_id', $member->id)->where('card_id', $card->id)->exists()) {
            $bonusData = array_merge($data, [
                'points' => $card->initial_bonus_points,
                'event' => 'initial_bonus_points',
                'created_at' => $created_at,
                'updated_at' => $created_at,
            ]);
            $transaction = Transaction::create($bonusData);

            $number_of_points_issued += $card->initial_bonus_points;

            // Add analytics (convert Carbon to string if needed)
            $bonusAnalyticsCreatedAt = $created_at instanceof Carbon ? $created_at->format('Y-m-d H:i:s') : $created_at;
            $this->analyticsService->addIssueAnalytic($card, $staff, $member, (int) $card->initial_bonus_points, $card->currency, 0, $bonusAnalyticsCreatedAt);

            // Add a second to the created_at timestamp for sorting purposes
            if (! $created_at instanceof Carbon) {
                $created_at = Carbon::parse($created_at);
            }
            $created_at->addSecond();
        }

        // Prepare data for new transaction record
        $purchaseData = array_merge($data, [
            'points' => $points,
            'event' => $points_only ? 'staff_credited_points' : 'staff_credited_points_for_purchase',
            'note' => $note,
            'created_at' => $created_at,
            'updated_at' => $created_at,
        ]);

        // Create a new transaction record
        $transaction = Transaction::create($purchaseData);

        // Auto-follow card when first transaction is made
        if (! $card->members()->where('member_id', $member->id)->exists()) {
            $card->members()->syncWithoutDetaching([$member->id]);
        }

        // Attach image if present
        if ($image) {
            $transaction->addMedia($image)->toMediaCollection('image');
        }

        // Update stats
        if (! $points_only) {
            $card->total_amount_purchased += $purchase_amount_parsed;
        }
        $card->number_of_points_issued += $number_of_points_issued;
        $card->last_points_issued_at = Carbon::now('UTC');
        $card->save();

        // Add analytics (convert Carbon to string if needed)
        $analyticsCreatedAt = $created_at instanceof Carbon ? $created_at->format('Y-m-d H:i:s') : $created_at;
        $this->analyticsService->addIssueAnalytic($card, $staff, $member, $points, $card->currency, $purchase_amount_parsed, $analyticsCreatedAt);

        // Send mail (PointsReceived expects points as string for formatting)
        $member->notify(new PointsReceived($member, (string) $points, $card));

        // Evaluate member's tier after transaction (may trigger upgrade)
        if ($club) {
            $this->tierService->evaluateMemberTier($member, $club);
        }

        return $transaction;
    }

    /**
     * Record a purchase without a staff actor.
     *
     * This mirrors addPurchase() but attributes the action to "System" so
     * headless agent integrations still get the same purchase accounting,
     * first-purchase bonus, tier evaluation, analytics, and notifications.
     */
    public function addAgentPurchase(
        Member $member,
        Card $card,
        ?float $purchase_amount,
        ?int $points,
        ?string $note = null,
    ): Transaction {
        $partner = $card->partner;
        $created_at = Carbon::now('UTC');
        $points_only = $purchase_amount === null;

        if ($points_only && $points === null) {
            throw new \InvalidArgumentException('Points are required when purchase amount is omitted.');
        }

        $expires_at = $created_at->copy()
            ->addMonths((int) $card->points_expiration_months)
            ->startOfDay()
            ->addHours(12);

        $data = [
            'staff_id' => null,
            'member_id' => $member->id,
            'card_id' => $card->id,
            'partner_name' => $partner->name,
            'partner_email' => $partner->email,
            'staff_name' => 'System',
            'staff_email' => null,
            'card_title' => $card->getTranslations('head'),
            'currency' => $card->currency,
            'points_per_currency' => $card->points_per_currency,
            'meta' => [
                'round_points_up' => $card->meta && is_array($card->meta) && isset($card->meta['round_points_up'])
                    ? (bool) $card->meta['round_points_up']
                    : true,
            ],
            'min_points_per_purchase' => $card->min_points_per_purchase,
            'max_points_per_purchase' => $card->max_points_per_purchase,
            'expires_at' => $expires_at->format('Y-m-d H:i:s'),
            'created_by' => $partner->id,
        ];

        $club = $card->club;
        $tierMultiplier = 1.00;
        $basePoints = 0;

        if ($club) {
            $this->tierService->ensureMemberHasTier($member, $club);
            $tierMultiplier = $this->tierService->getMultiplierForMember($member, $club);
        }

        if ($points_only) {
            $basePoints = (int) $points;
            $points = (int) round($basePoints * $tierMultiplier);
            $number_of_points_issued = $points;
            $data['purchase_amount'] = null;
            $purchase_amount_parsed = 0;
        } else {
            $purchase_amount_parsed = $this->parsePurchaseAmount($card, (float) $purchase_amount);
            $basePoints = $points !== null
                ? $points
                : $card->calculatePoints((float) $purchase_amount);
            $points = (int) round($basePoints * $tierMultiplier);
            $number_of_points_issued = $points;
            $data['purchase_amount'] = $purchase_amount_parsed;

            if ($points !== null) {
                $data['meta']['manual_points_override'] = true;
            }
        }

        if ($tierMultiplier > 1.00) {
            $data['meta']['tier_multiplier'] = $tierMultiplier;
            $data['meta']['base_points'] = $basePoints;
        }

        if ($card->initial_bonus_points && ! Transaction::where('member_id', $member->id)->where('card_id', $card->id)->exists()) {
            $bonusData = array_merge($data, [
                'points' => $card->initial_bonus_points,
                'event' => 'initial_bonus_points',
                'created_at' => $created_at,
                'updated_at' => $created_at,
            ]);
            $transaction = Transaction::create($bonusData);

            $number_of_points_issued += $card->initial_bonus_points;

            $this->analyticsService->addIssueAnalytic(
                $card,
                null,
                $member,
                (int) $card->initial_bonus_points,
                $card->currency,
                0,
                $created_at->format('Y-m-d H:i:s')
            );

            $created_at = $created_at->copy()->addSecond();
        }

        $purchaseData = array_merge($data, [
            'points' => $points,
            'event' => $points_only ? 'staff_credited_points' : 'staff_credited_points_for_purchase',
            'note' => $note,
            'created_at' => $created_at,
            'updated_at' => $created_at,
        ]);

        $transaction = Transaction::create($purchaseData);

        if (! $card->members()->where('member_id', $member->id)->exists()) {
            $card->members()->syncWithoutDetaching([$member->id]);
        }

        if (! $points_only) {
            $card->total_amount_purchased += $purchase_amount_parsed;
        }

        $card->number_of_points_issued += $number_of_points_issued;
        $card->last_points_issued_at = Carbon::now('UTC');
        $card->save();

        $this->analyticsService->addIssueAnalytic(
            $card,
            null,
            $member,
            $points,
            $card->currency,
            $purchase_amount_parsed,
            $created_at->format('Y-m-d H:i:s')
        );

        $member->notify(new PointsReceived($member, (string) $points, $card));

        if ($club) {
            $this->tierService->evaluateMemberTier($member, $club);
        }

        return $transaction;
    }

    /**
     * Adds points to the system from a system event (e.g. referral), creating a new Transaction record.
     */
    public function addSystemPoints(
        Member $member,
        Card $card,
        int $points,
        string $event = 'system_award',
        ?string $note = null
    ): Transaction {
        $partner = $card->partner;
        $created_at = Carbon::now('UTC');

        // Set expiration date based on date of creation (use UTC to avoid DST issues)
        $expires_at = $created_at->copy()->addMonths((int) $card->points_expiration_months)->startOfDay()->addHours(12);

        // Get tier multiplier for this member and club
        $club = $card->club;
        $tierMultiplier = 1.00;
        $basePoints = $points;

        if ($club) {
            // Ensure member has a tier assigned
            $this->tierService->ensureMemberHasTier($member, $club);
            $tierMultiplier = $this->tierService->getMultiplierForMember($member, $club);
        }

        // Apply tier multiplier
        $points = (int) round($basePoints * $tierMultiplier);
        $number_of_points_issued = $points;

        // Data for transaction record
        $data = [
            'staff_id' => null,
            'member_id' => $member->id,
            'card_id' => $card->id,
            'partner_name' => $partner->name,
            'partner_email' => $partner->email,
            'staff_name' => 'System',
            'staff_email' => null,
            'card_title' => $card->getTranslations('head'),
            'currency' => $card->currency,
            'points_per_currency' => $card->points_per_currency,
            'meta' => [
                'round_points_up' => $card->meta && is_array($card->meta) && isset($card->meta['round_points_up']) ? (bool) $card->meta['round_points_up'] : true,
            ],
            'min_points_per_purchase' => $card->min_points_per_purchase,
            'max_points_per_purchase' => $card->max_points_per_purchase,
            'expires_at' => $expires_at->format('Y-m-d H:i:s'),
            'created_by' => $partner->id,
            'points' => $points,
            'event' => $event,
            'note' => $note,
            'created_at' => $created_at,
            'updated_at' => $created_at,
        ];

        // Store tier info in meta for audit trail
        if ($tierMultiplier > 1.00) {
            $data['meta']['tier_multiplier'] = $tierMultiplier;
            $data['meta']['base_points'] = $basePoints;
        }

        // Create a new transaction record
        $transaction = Transaction::create($data);

        // Auto-follow card
        if (! $card->members()->where('member_id', $member->id)->exists()) {
            $card->members()->syncWithoutDetaching([$member->id]);
        }

        // Update stats
        $card->number_of_points_issued += $number_of_points_issued;
        $card->last_points_issued_at = Carbon::now('UTC');
        $card->save();

        // Add analytics
        $this->analyticsService->addIssueAnalytic($card, null, $member, $points, $card->currency, 0, $created_at->format('Y-m-d H:i:s'));

        // Send mail (PointsReceived expects points as string for formatting)
        $member->notify(new PointsReceived($member, (string) $points, $card));

        // Evaluate member's tier after transaction (may trigger upgrade)
        if ($club) {
            $this->tierService->evaluateMemberTier($member, $club);
        }

        return $transaction;
    }

    private function parsePurchaseAmount(Card $card, float $purchase_amount): int
    {
        $currencies = new ISOCurrencies;
        $moneyParser = new DecimalMoneyParser($currencies);

        return (int) $moneyParser->parse((string) $purchase_amount, new Currency($card->currency))->getAmount();
    }

    /**
     * Creates a new Transaction when a member redeems a code for points.
     * You can extend this further with analytics, notifications, etc.
     *
     * @param  PointCode  $codeEntry  The code model containing points and metadata.
     * @param  Member  $member  The member who redeems the code.
     */
    public function addCodeRedemption(PointCode $codeEntry, Member $member): Transaction
    {
        // Determine the card associated with code creation
        $card = $codeEntry->card;

        // Determine the staff associated with code creation
        $staff = $codeEntry->staff;
        $partner = $staff->partner ?? null;

        $note = trans('common.code_redeemed_note', ['code' => $codeEntry->code]);

        // Set expiration date based on date of creation (use UTC to avoid DST issues)
        $created_at = $created_at ?? Carbon::now('UTC');
        $expires_at = (! $created_at instanceof Carbon) ? Carbon::parse($created_at, 'UTC') : $created_at->copy()->setTimezone('UTC');
        $expires_at = $expires_at->addMonths((int) $card->points_expiration_months)->startOfDay()->addHours(12);

        // Get tier multiplier for this member and club
        $club = $card->club;
        $tierMultiplier = 1.00;
        $basePoints = $codeEntry->points;

        if ($club) {
            // Ensure member has a tier assigned
            $this->tierService->ensureMemberHasTier($member, $club);
            $tierMultiplier = $this->tierService->getMultiplierForMember($member, $club);
        }

        // Apply tier multiplier to code points
        $points = (int) round($basePoints * $tierMultiplier);
        $number_of_points_issued = $points;

        // Data for transaction record
        $meta = [
            'round_points_up' => $card->meta && is_array($card->meta) && isset($card->meta['round_points_up']) ? (bool) $card->meta['round_points_up'] : true,
        ];

        // Store tier info in meta for audit trail
        if ($tierMultiplier > 1.00) {
            $meta['tier_multiplier'] = $tierMultiplier;
            $meta['base_points'] = $basePoints;
        }

        $data = [
            'staff_id' => $staff->id,
            'member_id' => $member->id,
            'card_id' => $card->id,
            'partner_name' => $partner->name,
            'partner_email' => $partner->email,
            'staff_name' => $staff->name,
            'staff_email' => $staff->email,
            'card_title' => $card->getTranslations('head'),
            'currency' => $card->currency,
            'points_per_currency' => $card->points_per_currency,
            'meta' => $meta,
            'min_points_per_purchase' => $card->min_points_per_purchase,
            'max_points_per_purchase' => $card->max_points_per_purchase,
            'expires_at' => $expires_at->format('Y-m-d H:i:s'),
            'created_by' => $partner->id,
        ];

        // Check if this is first transaction and if bonus points are configured
        if ($card->initial_bonus_points && ! Transaction::where('member_id', $member->id)->where('card_id', $card->id)->exists()) {
            $bonusData = array_merge($data, [
                'points' => $card->initial_bonus_points,
                'event' => 'initial_bonus_points',
                'created_at' => $created_at,
                'updated_at' => $created_at,
            ]);
            $transaction = Transaction::create($bonusData);

            $number_of_points_issued += $card->initial_bonus_points;

            // Add analytics (convert Carbon to string if needed)
            $analyticsCreatedAt = $created_at instanceof Carbon ? $created_at->format('Y-m-d H:i:s') : $created_at;
            $this->analyticsService->addIssueAnalytic($card, $staff, $member, (int) $card->initial_bonus_points, $card->currency, 0, $analyticsCreatedAt);

            // Add a second to the created_at timestamp for sorting purposes
            if (! $created_at instanceof Carbon) {
                $created_at = Carbon::parse($created_at);
            }
            $created_at->addSecond();
        }

        // Prepare data for new transaction record
        $redeemData = array_merge($data, [
            'points' => $points,
            'event' => 'member_redeemed_code_for_points',
            'note' => $note,
            'created_at' => $created_at,
            'updated_at' => $created_at,
        ]);

        // Create a new transaction record
        $transaction = Transaction::create($redeemData);

        // Auto-follow card when code is redeemed
        if (! $card->members()->where('member_id', $member->id)->exists()) {
            $card->members()->syncWithoutDetaching([$member->id]);
        }

        // Add analytics
        $this->analyticsService->addRedeemCodeAnalytic($card, $staff, $member, $codeEntry->points);

        // Update stats
        $card->number_of_points_issued += $number_of_points_issued;
        $card->last_points_issued_at = Carbon::now('UTC');
        $card->save();

        // Evaluate member's tier after transaction (may trigger upgrade)
        if ($club) {
            $this->tierService->evaluateMemberTier($member, $club);
        }

        return $transaction;
    }

    /**
     * Processes a point request redemption.
     *
     * This method transfers points from the sender's account to the receiver (the creator of the point request)
     * using a FIFO deduction mechanism similar to claimReward. The receiver's transaction will inherit the expiration
     * date from the sender's earliest transaction used for the deduction.
     *
     * @param  PointRequest  $pointRequest  The point request record.
     * @param  Member  $sender  The member sending points.
     * @param  Member  $receiver  The member receiving points.
     * @param  string  $senderCardId  The ID of the card from which points will be deducted.
     * @param  int  $points  The number of points to transfer.
     * @return array An array containing the created receiver and sender transaction records.
     */
    public function addPointRequest(PointRequest $pointRequest, Member $sender, Member $receiver, string $senderCardId, int $points)
    {
        // Determine which card to use: if the point request is specific, use that card; otherwise, use the sender's selected card.
        $card = $pointRequest->card ?: Card::find($senderCardId);

        // Set a baseline time for the operation.
        $now = Carbon::now('UTC');

        // --- Deduct points from the sender using FIFO ---
        // Get sender's transactions for this card that have not expired and still have available points.
        $senderTransactions = Transaction::where('member_id', $sender->id)
            ->where('card_id', $card->id)
            ->where('expires_at', '>', $now)
            ->orderBy('created_at', 'asc')
            ->get();

        $remainingToDeduct = $points;
        $expirationForReceiver = null;

        foreach ($senderTransactions as $transaction) {
            // Calculate how many points remain available in this transaction.
            $available = $transaction->points - $transaction->points_used;
            if ($available <= 0) {
                continue;
            }
            // Determine how many points to deduct from this transaction.
            $deduct = min($remainingToDeduct, $available);
            $transaction->points_used += $deduct;
            $transaction->save();

            // Use the expiration date of the first transaction used.
            if ($expirationForReceiver === null) {
                $expirationForReceiver = $transaction->expires_at;
            }
            $remainingToDeduct -= $deduct;
            if ($remainingToDeduct <= 0) {
                break;
            }
        }

        if ($remainingToDeduct > 0) {
            // Not enough available points
            abort(400, 'Insufficient points available for transfer.');
        }

        // --- Create receiver's transaction ---
        // The receiver gets points with the expiration date from the sender's deduction.
        $receiverData = [
            'staff_id' => null,  // No staff involved in a peer-to-peer transfer.
            'member_id' => $receiver->id,
            'card_id' => $card->id,
            'note' => null,  // Will be updated to reference the sender transaction.
            'points' => $points,
            'event' => 'member_received_points_request',
            'card_title' => $card->getTranslations('head'),
            'currency' => $card->currency,
            'points_per_currency' => $card->points_per_currency,
            'meta' => [
                'round_points_up' => ($card->meta && is_array($card->meta) && isset($card->meta['round_points_up']))
                                        ? (bool) $card->meta['round_points_up']
                                        : true,
            ],
            'min_points_per_purchase' => $card->min_points_per_purchase,
            'max_points_per_purchase' => $card->max_points_per_purchase,
            // Use the sender's expiration date for the receiver's points.
            'expires_at' => $expirationForReceiver ? $expirationForReceiver->format('Y-m-d H:i:s') : null,
            'partner_name' => $card->partner->name,
            'partner_email' => $card->partner->email,
            'staff_name' => $sender->name,
            'staff_email' => $sender->email,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $card->partner->id,
        ];
        $receiverTransaction = Transaction::create($receiverData);

        // Auto-follow card when points are received
        if (! $card->members()->where('member_id', $receiver->id)->exists()) {
            $card->members()->syncWithoutDetaching([$receiver->id]);
        }

        // --- Create sender's transaction log ---
        // This logs the deduction from the sender as a negative transaction.
        $senderData = [
            'staff_id' => null,
            'member_id' => $sender->id,
            'card_id' => $card->id,
            'note' => $receiverTransaction->id,  // Reference the receiver's transaction.
            'points' => -$points,
            'event' => 'member_sent_points_request',
            'card_title' => $card->getTranslations('head'),
            'currency' => $card->currency,
            'points_per_currency' => $card->points_per_currency,
            'meta' => [
                'round_points_up' => ($card->meta && is_array($card->meta) && isset($card->meta['round_points_up']))
                                        ? (bool) $card->meta['round_points_up']
                                        : true,
            ],
            'min_points_per_purchase' => $card->min_points_per_purchase,
            'max_points_per_purchase' => $card->max_points_per_purchase,
            'expires_at' => null,
            'partner_name' => $card->partner->name,
            'partner_email' => $card->partner->email,
            // Optionally, you can set sender-specific fields here.
            'staff_name' => $receiver->name,
            'staff_email' => $receiver->email,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $card->partner->id,
        ];
        $senderTransaction = Transaction::create($senderData);

        // Update the receiver transaction's note to reference the sender transaction.
        $receiverTransaction->note = $senderTransaction->id;
        $receiverTransaction->save();

        // Add analytics entries.
        $this->analyticsService->addRequestPointsAnalytic('points_request_received', $card, $receiver, $points);
        $this->analyticsService->addRequestPointsAnalytic('points_request_sent', $card, $sender, -$points);

        // Send mail
        $receiver->notify(new PointsReceivedFromMember($sender, $receiver, (string) $points, $card));

        return [
            'receiverTransaction' => $receiverTransaction,
            'senderTransaction' => $senderTransaction,
        ];
    }

    /**
     * Redeem points for reward, creating a new Transaction record.
     */
    public function claimReward(
        string $card_id,
        string $reward_id,
        string $member_identifier,
        Staff $staff,
        ?UploadedFile $image = null,
        ?string $note = null,
        ?string $created_at = null
    ): Transaction|bool {
        // Fetch member and card details
        $card = $this->cardService->findActiveCard($card_id);
        $reward = $this->rewardService->findActiveReward($reward_id);
        $member = $this->memberService->findActiveByIdentifier($member_identifier);
        $partner = $card->partner;

        // Check if staff has access to card
        if (! $staff->isRelatedToCard($card)) {
            abort(401);
        }

        if ($card->getMemberBalance($member) < $reward->points) {
            return false;
        }

        /**
         * Updates a member's points balance based on transactions that haven't yet expired.
         * This method iterates through all valid transactions and credits reward points.
         * Points are used from older transactions first (First-In-First-Out)
         */
        $transactions = Transaction::where('member_id', $member->id)
            ->where('card_id', $card->id)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'asc')
            ->get();

        $remainingRewardPoints = $reward->points;

        foreach ($transactions as $transaction) {
            $unusedTransactionPoints = $transaction->points - $transaction->points_used;

            // Skip the transaction if all points are used or no more reward points left to credit
            if ($unusedTransactionPoints <= 0 || $remainingRewardPoints <= 0) {
                continue;
            }

            // Calculate the points to be used from the current transaction
            $pointsToUse = min($remainingRewardPoints, $unusedTransactionPoints);

            // Update the transaction's used points and persist the changes
            $transaction->points_used += $pointsToUse;
            $transaction->save();

            // Decrease the remaining reward points
            $remainingRewardPoints -= $pointsToUse;

            // Break the loop if all reward points are credited
            if ($remainingRewardPoints <= 0) {
                break;
            }
        }

        // Data for transaction record
        $data = [
            'staff_id' => $staff->id,
            'member_id' => $member->id,
            'card_id' => $card->id,
            'reward_id' => $reward->id,
            'partner_name' => $partner->name,
            'partner_email' => $partner->email,
            'staff_name' => $staff->name,
            'staff_email' => $staff->email,
            'card_title' => $card->getTranslations('head'),
            'reward_title' => $reward->getTranslations('title'),
            'reward_points' => $reward->points,
            'currency' => $card->currency,
            'event' => 'staff_redeemed_points_for_reward',
            'points' => -$reward->points,
            'note' => $note,
            'points_per_currency' => $card->points_per_currency,
            'min_points_per_purchase' => $card->min_points_per_purchase,
            'max_points_per_purchase' => $card->max_points_per_purchase,
            'created_by' => $partner->id,
            'created_at' => $created_at ?? Carbon::now('UTC'),
            'updated_at' => $created_at ?? Carbon::now('UTC'),
        ];

        // Create a new transaction record
        $transaction = Transaction::create($data);

        // Auto-follow card when reward is claimed (member used points)
        if (! $card->members()->where('member_id', $member->id)->exists()) {
            $card->members()->syncWithoutDetaching([$member->id]);
        }

        // Attach image if present
        if ($image) {
            $transaction->addMedia($image)->toMediaCollection('image');
        }

        // Update Card stats
        $card->number_of_points_redeemed += $reward->points;
        $card->number_of_rewards_redeemed += 1;
        $card->last_reward_redeemed_at = Carbon::now('UTC');
        $card->save();

        // Update Reward stats
        $reward->offsetUnset('images');
        $reward->number_of_times_redeemed += 1;
        $reward->save();

        // Add analytics
        $this->analyticsService->addClaimRewardAnalytic($card, $staff, $member, $reward, $created_at);

        // Send mail
        if (! $created_at) {
            $member->notify(new RewardClaimed($member, (string) $reward->points, $card, $reward));
        }

        return $transaction;
    }

    /**
     * Redeem points for reward WITHOUT requiring a Staff member.
     *
     * Mirrors claimReward() but attributes the action to the system/agent
     * rather than a staff member. This allows headless POS integrations
     * (Shopify, WooCommerce, agent API without staff delegation) to
     * redeem rewards.
     *
     * Uses the same FIFO point deduction, stats tracking, analytics,
     * and member notification as claimReward().
     *
     * @param  Card  $card  The loyalty card.
     * @param  Reward  $reward  The reward to claim.
     * @param  Member  $member  The member claiming the reward.
     * @param  string|null  $note  Optional note.
     * @return Transaction|bool The created transaction, or false if insufficient points.
     */
    public function systemClaimReward(
        Card $card,
        Reward $reward,
        Member $member,
        ?string $note = null,
    ): Transaction|bool {
        $partner = $card->partner;

        if ($card->getMemberBalance($member) < $reward->points) {
            return false;
        }

        // FIFO point deduction — identical to claimReward()
        $transactions = Transaction::where('member_id', $member->id)
            ->where('card_id', $card->id)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'asc')
            ->get();

        $remainingRewardPoints = $reward->points;

        foreach ($transactions as $transaction) {
            $unusedTransactionPoints = $transaction->points - $transaction->points_used;

            if ($unusedTransactionPoints <= 0 || $remainingRewardPoints <= 0) {
                continue;
            }

            $pointsToUse = min($remainingRewardPoints, $unusedTransactionPoints);
            $transaction->points_used += $pointsToUse;
            $transaction->save();
            $remainingRewardPoints -= $pointsToUse;

            if ($remainingRewardPoints <= 0) {
                break;
            }
        }

        $now = Carbon::now('UTC');

        // Transaction record — staff fields set to 'System'
        $data = [
            'staff_id' => null,
            'member_id' => $member->id,
            'card_id' => $card->id,
            'reward_id' => $reward->id,
            'partner_name' => $partner->name,
            'partner_email' => $partner->email,
            'staff_name' => 'System',
            'staff_email' => null,
            'card_title' => $card->getTranslations('head'),
            'reward_title' => $reward->getTranslations('title'),
            'reward_points' => $reward->points,
            'currency' => $card->currency,
            'event' => 'staff_redeemed_points_for_reward',
            'points' => -$reward->points,
            'note' => $note,
            'points_per_currency' => $card->points_per_currency,
            'min_points_per_purchase' => $card->min_points_per_purchase,
            'max_points_per_purchase' => $card->max_points_per_purchase,
            'created_by' => $partner->id,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $transaction = Transaction::create($data);

        // Auto-follow card
        if (! $card->members()->where('member_id', $member->id)->exists()) {
            $card->members()->syncWithoutDetaching([$member->id]);
        }

        // Update Card stats
        $card->number_of_points_redeemed += $reward->points;
        $card->number_of_rewards_redeemed += 1;
        $card->last_reward_redeemed_at = $now;
        $card->save();

        // Update Reward stats
        $reward->offsetUnset('images');
        $reward->number_of_times_redeemed += 1;
        $reward->save();

        // Add analytics (staff=null handled by nullable parameter)
        $this->analyticsService->addClaimRewardAnalytic($card, null, $member, $reward);

        // Send mail
        $member->notify(new RewardClaimed($member, (string) $reward->points, $card, $reward));

        return $transaction;
    }

    /**
     * Deletes the last transaction for a given partner, member and card combination.
     *
     * @param  Partner  $partner  The partner who created the transaction.
     * @param  Member  $member  The member associated with the transaction.
     * @param  Card  $card  The card.
     * @return bool True if the deletion was successful, false otherwise.
     */
    public function deleteLastTransaction(Partner $partner, Member $member, Card $card): bool
    {
        // Fetch the last transaction
        $transaction = Transaction::where('card_id', $card->id)
            ->where('member_id', $member->id)
            ->where('created_by', $partner->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $transaction) {
            return false;
        }

        // Special handling for point request transactions
        if (in_array($transaction->event, ['member_received_points_request', 'member_sent_points_request'])) {
            // The note field contains the ID of the corresponding transaction
            $correspondingTransactionId = $transaction->note;
            if ($correspondingTransactionId) {
                $correspondingTransaction = Transaction::find($correspondingTransactionId);
                if ($correspondingTransaction) {
                    // Determine which transaction is the sender's and which is the receiver's
                    $senderTransaction = $transaction->event === 'member_sent_points_request' ?
                        $transaction : $correspondingTransaction;
                    $receiverTransaction = $transaction->event === 'member_received_points_request' ?
                        $transaction : $correspondingTransaction;

                    // Reverse the point deductions for the sender
                    if ($senderTransaction->points < 0) { // This is the sender's negative record
                        $this->reversePointsCalculation(
                            Member::find($senderTransaction->member_id),
                            Card::find($senderTransaction->card_id),
                            abs($senderTransaction->points)
                        );
                    }

                    // Delete analytics for corresponding transaction
                    Analytic::where('card_id', $correspondingTransaction->card_id)
                        ->where('member_id', $correspondingTransaction->member_id)
                        ->where('partner_id', $partner->id)
                        ->where('created_at', $correspondingTransaction->created_at)
                        ->delete();

                    // Re-evaluate tier for the corresponding member
                    $correspondingMember = Member::find($correspondingTransaction->member_id);
                    $correspondingCard = Card::find($correspondingTransaction->card_id);

                    // Delete the corresponding transaction
                    $correspondingTransaction->delete();

                    // Re-evaluate the other member's tier after deletion
                    if ($correspondingMember && $correspondingCard) {
                        $correspondingClub = $correspondingCard->club;
                        if ($correspondingClub) {
                            $this->tierService->evaluateMemberTier($correspondingMember, $correspondingClub, forceDowngrade: true);
                        }
                    }
                }
            }
        }

        // Delete analytics for the original transaction
        $analytic = Analytic::where('card_id', $transaction->card_id)
            ->where('member_id', $member->id)
            ->where('partner_id', $partner->id)
            ->where('staff_id', $transaction->staff_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($analytic) {
            // Handle reward redemption
            if ($transaction->event == 'staff_redeemed_points_for_reward') {
                $card->number_of_points_redeemed += $transaction->points;
                $card->number_of_rewards_redeemed -= 1;
                $card->save();

                $reward = $analytic->reward;
                if ($reward) {
                    $reward->offsetUnset('images');
                    $reward->number_of_times_redeemed -= 1;
                    $reward->save();
                }
            }

            // Handle point issuance
            if (in_array($transaction->event, ['initial_bonus_points', 'staff_credited_points_for_purchase', 'staff_credited_points'])) {
                $card->number_of_points_issued -= $transaction->points;
                if ($transaction->event != 'initial_bonus_points') {
                    $card->total_amount_purchased -= $transaction->purchase_amount;
                }
                $card->save();
            }

            $analytic->delete();
        }

        // Delete the original transaction
        $transaction->delete();

        // Handle reward redemption point calculation reversal
        if ($transaction->event == 'staff_redeemed_points_for_reward') {
            $this->reversePointsCalculation($member, $card, abs($transaction->points));
        }

        // Re-evaluate tier after transaction deletion (force downgrade to correct erroneous assignments)
        $club = $card->club;
        if ($club) {
            $this->tierService->evaluateMemberTier($member, $club, forceDowngrade: true);
        }

        return true;
    }

    /**
     * Deletes the last transaction for an admin (no creator filter).
     *
     * @param  mixed  $admin  The admin performing the deletion.
     * @param  Member  $member  The member associated with the transaction.
     * @param  Card  $card  The card.
     * @return bool True if the deletion was successful, false otherwise.
     */
    public function adminDeleteLastTransaction($admin, Member $member, Card $card): bool
    {
        // Fetch the last transaction (no created_by filter for admin)
        $transaction = Transaction::where('card_id', $card->id)
            ->where('member_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $transaction) {
            return false;
        }

        // Special handling for point request transactions
        if (in_array($transaction->event, ['member_received_points_request', 'member_sent_points_request'])) {
            $correspondingTransactionId = $transaction->note;
            if ($correspondingTransactionId) {
                $correspondingTransaction = Transaction::find($correspondingTransactionId);
                if ($correspondingTransaction) {
                    $senderTransaction = $transaction->event === 'member_sent_points_request' ?
                        $transaction : $correspondingTransaction;

                    if ($senderTransaction->points < 0) {
                        $this->reversePointsCalculation(
                            Member::find($senderTransaction->member_id),
                            Card::find($senderTransaction->card_id),
                            abs($senderTransaction->points)
                        );
                    }

                    Analytic::where('card_id', $correspondingTransaction->card_id)
                        ->where('member_id', $correspondingTransaction->member_id)
                        ->where('created_at', $correspondingTransaction->created_at)
                        ->delete();

                    // Re-evaluate tier for the corresponding member
                    $correspondingMember = Member::find($correspondingTransaction->member_id);
                    $correspondingCard = Card::find($correspondingTransaction->card_id);

                    $correspondingTransaction->delete();

                    // Re-evaluate the other member's tier after deletion
                    if ($correspondingMember && $correspondingCard) {
                        $correspondingClub = $correspondingCard->club;
                        if ($correspondingClub) {
                            $this->tierService->evaluateMemberTier($correspondingMember, $correspondingClub, forceDowngrade: true);
                        }
                    }
                }
            }
        }

        // Delete analytics for the original transaction
        $analytic = Analytic::where('card_id', $transaction->card_id)
            ->where('member_id', $member->id)
            ->where('staff_id', $transaction->staff_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($analytic) {
            if ($transaction->event == 'staff_redeemed_points_for_reward') {
                $card->number_of_points_redeemed += $transaction->points;
                $card->number_of_rewards_redeemed -= 1;
                $card->save();

                $reward = $analytic->reward;
                if ($reward) {
                    $reward->offsetUnset('images');
                    $reward->number_of_times_redeemed -= 1;
                    $reward->save();
                }
            }

            if (in_array($transaction->event, ['initial_bonus_points', 'staff_credited_points_for_purchase', 'staff_credited_points'])) {
                $card->number_of_points_issued -= $transaction->points;
                if ($transaction->event != 'initial_bonus_points') {
                    $card->total_amount_purchased -= $transaction->purchase_amount;
                }
                $card->save();
            }

            $analytic->delete();
        }

        $transaction->delete();

        if ($transaction->event == 'staff_redeemed_points_for_reward') {
            $this->reversePointsCalculation($member, $card, abs($transaction->points));
        }

        // Re-evaluate tier after transaction deletion (force downgrade to correct erroneous assignments)
        $club = $card->club;
        if ($club) {
            $this->tierService->evaluateMemberTier($member, $club, forceDowngrade: true);
        }

        return true;
    }

    /**
     * Reverses the points calculation for a given member and card.
     *
     * @param  Member  $member  The member associated with the transactions.
     * @param  Card  $card  The card.
     * @param  int  $points  The points to reverse.
     */
    private function reversePointsCalculation(Member $member, Card $card, int $points): void
    {
        // Fetch the transactions that were affected by the last transaction
        $transactions = Transaction::where('member_id', $member->id)
            ->where('card_id', $card->id)
            ->where('points_used', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $pointsToReverse = $points;

        foreach ($transactions as $transaction) {
            // Calculate the points that can be reversed from the current transaction
            $pointsToDeduct = min($pointsToReverse, $transaction->points_used);

            // Update the transaction's used points and persist the changes
            $transaction->points_used -= $pointsToDeduct;
            $transaction->save();

            // Decrease the points that need to be reversed
            $pointsToReverse -= $pointsToDeduct;

            // Break the loop if all points are reversed
            if ($pointsToReverse <= 0) {
                break;
            }
        }
    }
}
