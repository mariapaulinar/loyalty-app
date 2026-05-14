<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a Loyalty Card in the application.
 * Cards define point-earning rules, expiration policies, and visual branding.
 */

namespace App\Models;

use App\Traits\HasIdentifier;
use App\Traits\HasSchemaAccessors;
use App\Traits\LogsModelActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Parser\DecimalMoneyParser;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Class Card
 *
 * Represents a Card in the application.
 */
class Card extends Model implements HasMedia
{
    use HasFactory, HasIdentifier, HasSchemaAccessors, HasTranslations, HasUuids, InteractsWithMedia, LogsModelActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cards';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'issue_date' => 'datetime',
        'expiration_date' => 'datetime',
        'last_points_issued_at' => 'datetime',
        'last_reward_redeemed_at' => 'datetime',
        'last_view' => 'datetime',
        'meta' => 'array',
        'created_at' => 'datetime',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should not be exposed by API and other public responses.
     *
     * @var array
     */
    protected $hiddenForPublic = [
        'type',
        'icon',
        'min_points_per_redemption',
        'max_points_per_redemption',
        'custom_rule1',
        'custom_rule2',
        'custom_rule3',
        'is_active',
        'is_undeletable',
        'is_uneditable',
        'deleted_at',
        'deleted_by',
        'created_by',
        'updated_by',
    ];

    public function hideForPublic()
    {
        $this->makeHidden($this->hiddenForPublic);

        return $this;
    }

    /**
     * Allow mass assignment of a model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Translatable fields.
     *
     * @var array
     */
    public $translatable = ['head', 'title', 'description', 'custom_rule1', 'custom_rule2', 'custom_rule3'];

    /**
     * Register media collections for the model.
     */
    public function registerMediaCollections(): void
    {
        $this->addCollectionWithConversions('logo', 300, 600);
        $this->addCollectionWithConversions('background', 960, 1024);
    }

    private function addCollectionWithConversions($collectionName, $smallDimension, $mediumDimension)
    {
        $this
            ->addMediaCollection($collectionName)
            ->singleFile()
            ->registerMediaConversions(function (Media $media) use ($smallDimension, $mediumDimension) {
                $this
                    ->addMediaConversion('sm')
                    ->fit(Fit::Max, $smallDimension, $smallDimension)
                    ->keepOriginalImageFormat();

                $this
                    ->addMediaConversion('md')
                    ->fit(Fit::Max, $mediumDimension, $mediumDimension)
                    ->keepOriginalImageFormat();
            });
    }

    /**
     * Retrieve the value of an attribute or a dynamically generated image URL.
     *
     * @param  string  $key  The attribute key or the image key with a specific conversion.
     * @return mixed The value of the attribute or the image conversion URL.
     *
     * @throws \Illuminate\Database\Eloquent\RelationNotFoundException If the relationship is not found.
     */
    public function __get($key)
    {
        $collectionNames = ['logo', 'background'];
        foreach ($collectionNames as $collectionName) {
            if (substr($key, 0, strlen($collectionName) + 1) === $collectionName.'-') {
                return $this->getImageUrl($collectionName, substr($key, strlen($collectionName) + 1, strlen($key)));
            }
        }

        return parent::__get($key);
    }

    /**
     * Get the URL of a collection with a specific conversion.
     *
     * @param  string|null  $conversion
     * @return string|null
     */
    public function getImageUrl($collection, $conversion = '')
    {
        if ($this->getFirstMediaUrl($collection) !== '') {
            $media = $this->getMedia($collection);

            // Get the resized image URL with the specified conversion
            return $media[0]->getFullUrl($conversion);
        } else {
            return null;
        }
    }

    /**
     * Get the logo URL.
     *
     * @return string|null
     */
    public function getLogoAttribute()
    {
        return $this->getImageUrl('logo');
    }

    /**
     * Get background image.
     *
     * @return string|null
     */
    public function getBackgroundAttribute()
    {
        return $this->getImageUrl('background');
    }

    /**
     * Determine if the card is expired.
     *
     * This attribute returns a boolean indicating whether the card's expiration date
     * has passed. It compares the current date and time with the card's expiration date.
     *
     * @return bool True if the card is expired, false otherwise.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiration_date ? Carbon::now()->greaterThan($this->expiration_date) : false;
    }

    /**
     * Calculate reward points based on purchase amount.
     *
     * This method calculates the reward points a customer should receive
     * based on their purchase amount. It uses the defined points_per_currency
     * and currency_unit_amount properties of the class to perform the calculation.
     * If the calculated points fall below the min_points_per_purchase, it sets
     * the points to the minimum. If they exceed the max_points_per_purchase, it sets
     * the points to the maximum. It then returns the final calculated points.
     *
     * @param  float  $purchaseAmount  The amount of the purchase.
     * @return int The number of reward points the customer should receive.
     */
    public function calculatePoints(float $purchaseAmount): int
    {
        $round_points_up = $this->meta && is_array($this->meta) && isset($this->meta['round_points_up']) ? (bool) $this->meta['round_points_up'] : true;

        // Calculate points based on purchase amount
        if ($round_points_up) {
            // Round up points calculation
            $pointsValue = round(($purchaseAmount / $this->currency_unit_amount) * $this->points_per_currency);
        } else {
            // Without rounding, ensure integer value
            $pointsValue = floor(($purchaseAmount / $this->currency_unit_amount) * $this->points_per_currency);
        }

        // If points are less than the minimum, set to the minimum
        if ($pointsValue < $this->min_points_per_purchase) {
            $pointsValue = $this->min_points_per_purchase;
        }

        // If points are more than the maximum, set to the maximum
        elseif ($pointsValue > $this->max_points_per_purchase) {
            $pointsValue = $this->max_points_per_purchase;
        }

        // Return calculated points (cast to int since round/floor return float)
        return (int) $pointsValue;
    }

    /**
     * Get the balance of the provided member or the authenticated member.
     *
     * This function returns the total points from the transactions of a specified member or the currently authenticated member.
     * If no member is specified or authenticated, it returns zero.
     *
     * @param  Member|null  $member  The member whose balance should be retrieved. Defaults to null.
     * @return int The balance of the member.
     */
    public function getMemberBalance(?Member $member): int
    {
        // Determine the member_id to use for the balance calculation.
        // If a member is provided, use its id. If not, check if a member is authenticated and use their id.
        $memberId = $member
            ? $member->id
            : auth('member')->id();

        // Calculate the balance if a member id was found.
        // If not, set balance to 0.
        $balance = 0;
        if ($memberId) {
            $balance = Transaction::where('member_id', $memberId)
                ->where('card_id', $this->id)
                ->where('expires_at', '>', Carbon::now())
                ->select(DB::raw('SUM(points - points_used) as balance'))
                ->pluck('balance')
                ->first();

            $balance = $balance ?? 0;
        }

        // Cast to int since DB::raw returns string
        return (int) $balance;
    }

    /**
     * Get the balance attribute for a card related to the authenticated member.
     *
     * @return int
     */
    public function getMemberBalanceAttribute()
    {
        $member = auth('member')->user();

        return $this->getMemberBalance($member);
    }

    /**
     * Get the date and time when points were last issued by the authenticated member for this card.
     * This includes events where points were issued as initial bonus points or for a purchase.
     */
    public function getLastPointsClaimedByMemberAtAttribute(): ?Carbon
    {
        $memberId = auth('member')->id();

        if ($memberId) {
            $lastPointsIssuedAt = Transaction::where('member_id', $memberId)
                ->where('card_id', $this->id)
                ->whereIn('event', ['initial_bonus_points', 'staff_credited_points_for_purchase'])
                ->latest('created_at')
                ->value('created_at');

            return $lastPointsIssuedAt ? Carbon::parse($lastPointsIssuedAt) : null;
        }

        return null;
    }

    /**
     * Get the number of points used by the authenticated member for this card.
     */
    public function getNumberOfPointsUsedByMemberAttribute(): int
    {
        $memberId = auth('member')->id();

        $pointsUsed = 0;
        if ($memberId) {
            $pointsUsed = Transaction::where('member_id', $memberId)
                ->where('card_id', $this->id)
                ->where('expires_at', '>', Carbon::now())
                ->select(DB::raw('SUM(points_used) as points_used'))
                ->pluck('points_used')
                ->first();

            $pointsUsed = $pointsUsed ?? 0;
        }

        return (int) $pointsUsed;
    }

    /**
     * Get the number of rewards claimed by the authenticated member for this card.
     */
    public function getNumberOfRewardsClaimedByMemberAttribute(): int
    {
        $memberId = auth('member')->id();

        $rewardsClaimed = 0;
        if ($memberId) {
            $rewardsClaimed = Transaction::where('member_id', $memberId)
                ->where('card_id', $this->id)
                ->where('event', 'staff_redeemed_points_for_reward')
                ->count();

            $rewardsClaimed = $rewardsClaimed ?? 0;
        }

        return $rewardsClaimed;
    }

    /**
     * Get the most recent reward claimed date by the authenticated member for this card.
     */
    public function getLastRewardClaimedByMemberAtAttribute(): ?Carbon
    {
        $memberId = auth('member')->id();

        if ($memberId) {
            $lastRewardClaimed = Transaction::where('member_id', $memberId)
                ->where('card_id', $this->id)
                ->where('event', 'staff_redeemed_points_for_reward')
                ->latest('created_at')
                ->value('created_at');

            return $lastRewardClaimed ? Carbon::parse($lastRewardClaimed) : null;
        }

        return null;
    }

    /**
     * Get the total amount purchased by the authenticated member for this card.
     */
    public function getTotalAmountPurchasedByMemberAttribute(): int
    {
        $memberId = auth('member')->id();

        $totalAmountPurchased = 0;
        if ($memberId) {
            $totalAmountPurchased = Transaction::where('member_id', $memberId)
                ->where('card_id', $this->id)
                ->where('event', '!=', 'initial_bonus_points')
                ->sum('purchase_amount');

            $totalAmountPurchased = $totalAmountPurchased ?? 0;
        }

        return (int) $totalAmountPurchased;
    }

    /**
     * Get the partner that created the card.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'created_by');
    }

    /**
     * Formats the given value as a currency string if it is not null.
     *
     * Uses the app's current locale and currency to format the value.
     * Note: The value is assumed to be stored as subunits of the currency (e.g., cents for USD).
     * Null is returned if the value is null.
     *
     * @param  int|null  $value  The value to be formatted, represented in the smallest currency unit (e.g., cents).
     */
    public function parseMoney(?int $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Set up the formatting tools
        $currencies = new ISOCurrencies;
        $locale = app()->make('i18n')->language->current->locale;
        $numberFormatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        // Get the currency's subunit (fraction digits)
        $currencyCode = $this->currency;
        $currency = new Currency($currencyCode);
        $subunit = $currencies->subunitFor($currency);

        // Convert the stored amount to its base unit (e.g., dollars for USD), parse it to a Money object, and format it
        $moneyParser = new DecimalMoneyParser($currencies);
        $amountInBaseUnit = $value / pow(10, $subunit);
        $amount = $moneyParser->parse((string) $amountInBaseUnit, $currency);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

        return $moneyFormatter->format($amount);
    }

    /**
     * Get the club associated with the card.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the members associated with the card.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function members()
    {
        return $this->belongsToMany(Member::class, 'card_member')->withTimestamps();
    }

    /**
     * Get the rewards associated with the card.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function rewards()
    {
        return $this->belongsToMany(Reward::class, 'card_reward')->withTimestamps();
    }

    /**
     * Get the active rewards for the card, ordered by points.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function activeRewards()
    {
        // Get the current time in UTC
        $now = Carbon::now('UTC');

        return $this->belongsToMany(Reward::class, 'card_reward')
            ->where('is_active', 1)
            ->where('expiration_date', '>', $now) // Add this line
            ->orderBy('points', 'desc');
    }

    /**
     * Get the transactions associated with the card.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
