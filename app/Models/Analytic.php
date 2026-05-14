<?php

namespace App\Models;

use App\Traits\HasSchemaAccessors;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Parser\DecimalMoneyParser;

/**
 * Class Analytic
 *
 * Represents a Transaction in the application.
 */
class Analytic extends Model
{
    use HasFactory, HasSchemaAccessors, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analytics';

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
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Allow mass assignment of a model.
     *
     * @var array
     */
    protected $guarded = [];

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

        // Parse value to a Money object for formatting
        $moneyParser = new DecimalMoneyParser($currencies);
        $amount = $moneyParser->parse($this->purchase_amount / 100, new Currency($this->currency));

        // Format the amount as a currency string
        return $moneyFormatter->format($amount);
    }

    /**
     * Get the staff associated with the analytic.
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
     * Get the member associated with the analytic.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the card associated with the analytic.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Get the reward associated with the analytic.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }

    /**
     * Get the partner associated with the analytic.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
