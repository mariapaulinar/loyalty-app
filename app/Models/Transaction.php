<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Represents a Transaction in the application.
 * Transactions record point earnings, redemptions, and transfers in the ledger.
 */

namespace App\Models;

use App\Traits\HasSchemaAccessors;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
 * Class Transaction
 *
 * Represents a Transaction in the application.
 */
class Transaction extends Model implements HasMedia
{
    use HasFactory, HasSchemaAccessors, HasTranslations, HasUuids, InteractsWithMedia, LogsModelActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions';

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
        'expires_at' => 'datetime',
        'meta' => 'array',
        'created_at' => 'datetime',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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
    public $translatable = ['card_title', 'reward_title'];

    /**
     * Get the value of the amount attribute.
     *
     * @param  string  $value
     * @return string
     */
    public function getPurchaseAmountFormattedAttribute($value)
    {
        $currencies = new ISOCurrencies;

        // Use the app's locale
        $locale = app()->make('i18n')->language->current->locale;

        $numberFormatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

        // Get the currency's subunit (fraction digits)
        $currencyCode = $this->currency;
        $currency = new Currency($currencyCode);
        $subunit = $currencies->subunitFor($currency);

        // Parse value to a Money object for formatting
        $moneyParser = new DecimalMoneyParser($currencies);

        // Adjust the purchase amount based on the currency's subunit
        $amount = $moneyParser->parse((string) ($this->purchase_amount / pow(10, $subunit)), $currency);

        // Format the amount as a currency string
        return $moneyFormatter->format($amount);
    }

    /**
     * Register media collections for the model.
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('image')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                // Conversion: sm
                $this
                    ->addMediaConversion('sm')
                    ->fit(Fit::Max, 80, 80)
                    ->keepOriginalImageFormat();

                // Conversion: md
                $this
                    ->addMediaConversion('md')
                    ->fit(Fit::Max, 800, 800)
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
        $collectionNames = ['image'];
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
     * Get the image URL.
     *
     * @return string|null
     */
    public function getImageAttribute()
    {
        return $this->getImageUrl('image');
    }

    /**
     * Get the staff associated with the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id')->withDefault([
            'name' => 'Deleted Account',
        ]);
    }

    /**
     * Get the member associated with the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the card associated with the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Get the reward associated with the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }

    /**
     * Get the partner associated with the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Reward::class, 'created_by');
    }
}
