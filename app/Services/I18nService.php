<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class I18nService
{
    /**
     * Determines the preferred locale (language / country) of a visitor based on their browser language.
     * If a translation is available and the language is active, the locale is determined and returned.
     * If a translation is not available or the language is inactive, the application's default locale is returned instead.
     *
     * @param  bool  $parsedForUrl  When false, returns the locale in the format 'en_US', when true returns 'en-us'.
     * @return string The determined preferred locale of a visitor (always includes country code, e.g., 'en_US' or 'en-us').
     */
    public function getPreferredLocale(bool $parsedForUrl = false): string
    {
        $defaultLocale = $this->ensureFullLocale(config('app.locale'));
        $defaultLanguage = explode('_', $defaultLocale)[0];

        // Retrieve the visitor's language or default to the application's default language
        $language = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && trim($_SERVER['HTTP_ACCEPT_LANGUAGE']) != '')
            ? locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'])
            : $defaultLanguage;

        // Format the language code for consistency
        $language = strtolower(str_replace('-', '_', $language));

        // If language code has region (e.g., en_US), keep only the language part (e.g., en)
        if (strpos($language, '_') !== false) {
            $language = explode('_', $language)[0];
        }

        $languageModel = Language::where('id', $language)->first();

        if (! $languageModel) {
            // If the language model is not found, default to the application's default locale
            return $parsedForUrl ? strtolower(str_replace('_', '-', $defaultLocale)) : $defaultLocale;
        }

        $countries = $languageModel->countries->sortByDesc('population');

        $locale = $this->checkAvailableTranslation($countries, $language) ?? $defaultLocale;

        // Ensure the locale always has a country code
        $locale = $this->ensureFullLocale($locale);

        // Return the locale, formatted for use in a URL if requested
        return $parsedForUrl ? strtolower(str_replace('_', '-', $locale)) : $locale;
    }

    /**
     * Ensures a locale string always includes a country code.
     * If only a language code is provided (e.g., 'en'), it will find the first country
     * that has an available translation directory, sorted by population.
     *
     * @param  string  $locale  The locale to validate (e.g., 'en' or 'en_US').
     * @return string A full locale with country code (e.g., 'en_US').
     */
    private function ensureFullLocale(string $locale): string
    {
        // Normalize format to use underscore
        $locale = str_replace('-', '_', $locale);

        // Check if locale already has a country code
        if (strpos($locale, '_') !== false) {
            return $locale;
        }

        // Only language code provided, find a country with an available translation
        $language = strtolower($locale);
        $languageModel = Language::where('id', $language)->first();

        if ($languageModel && $languageModel->countries->isNotEmpty()) {
            $countries = $languageModel->countries->sortByDesc('population');

            // First, try to find a country that has an actual translation directory
            foreach ($countries as $country) {
                $candidateLocale = $language.'_'.$country->id;
                if (File::exists(lang_path().'/'.$candidateLocale.'/')) {
                    return $candidateLocale;
                }
            }

            // No translation directory found, fall back to most populous country
            $topCountry = $countries->first();

            return $language.'_'.$topCountry->id;
        }

        // Fallback: uppercase the language code as country (e.g., 'en' -> 'en_EN')
        // This is a last resort and should rarely happen with proper data
        return $language.'_'.strtoupper($language);
    }

    /**
     * Check if an active translation is available for the given language.
     *
     * Iterates through countries (sorted by population) and returns the first
     * locale that has an active translation directory.
     *
     * @param  \Illuminate\Support\Collection  $countries  Countries associated with the language, sorted by population.
     * @param  string  $language  The two-letter language code (e.g., 'ar', 'en').
     * @return string|null The first active locale found (e.g., 'ar_EG'), or null if none.
     */
    private function checkAvailableTranslation($countries, $language): ?string
    {
        foreach ($countries as $country) {
            $locale = $language.'_'.$country->id;

            // Check if translation directory exists
            if (! File::exists(lang_path().'/'.$locale.'/')) {
                continue;
            }

            // Check if config file exists
            if (! file_exists(lang_path($locale.'/config.php'))) {
                continue;
            }

            // In demo mode, all translations are available regardless of active status
            if (config('default.app_demo')) {
                return $locale;
            }

            // Check if translation is active
            $config = require lang_path($locale.'/config.php');

            // If no 'active' key exists, default to active (for backwards compatibility)
            if (! array_key_exists('active', $config)) {
                return $locale;
            }

            // Only return if explicitly active, otherwise continue checking other countries
            if ($config['active']) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * Get a list of all time zones with replaced '/' and '_'.
     *
     * @return array List of all time zones available in the system.
     */
    public function getAllTimezones(): array
    {
        $timezones = timezone_identifiers_list();

        return array_reduce($timezones, static function ($carry, $timezone) {
            $carry[$timezone] = str_replace('_', ' ', str_replace('/', ' / ', $timezone));

            return $carry;
        }, []);
    }

    /**
     * Get a list of all available translations.
     *
     * @param  string|null  $language  Two-letter language code. If not provided, each locale is translated to its own language.
     * @param  Request|null  $request  The incoming HTTP request.
     * @return array List of all available translations translated to the requested language.
     */
    public function getAllTranslations(?string $language = null, ?Request $request = null): array
    {
        $languages = [];
        $currentLanguage = null;

        foreach (File::directories(lang_path()) as $languageDir) {
            $locale = basename($languageDir);

            // Check if language is active
            $this_language_is_active = true;
            if (file_exists(lang_path($locale.'/config.php'))) {
                if (config('default.app_demo')) {
                    $this_language_is_active = true;
                } else {
                    $config = require lang_path($locale.'/config.php');
                    if (array_key_exists('active', $config)) {
                        $this_language_is_active = $config['active'];
                    }
                }
            }

            if ($this_language_is_active) {
                [$localeLanguage, $localeCountry] = explode('_', $locale) + ['en', 'US'];
                $languageName = $this->getLocaleLanguageName($locale, $language);
                $countryName = $this->getLocaleCountryName($locale, $language);
                $urlLocale = strtolower(str_replace('_', '-', $locale));

                $routeParameters = $request ? ($request->route() ? $request->route()->parameters() : []) : [];
                $routeParameters['locale'] = $urlLocale;

                $routeName = $request && $request->route() ? $request->route()->getName() : '{role}.index';

                if ($routeName) {
                    $indexRoutes = [
                        'canonical' => route(str_replace('{role}', 'admin', $routeName), $routeParameters),
                        'adminIndex' => route(str_replace('{role}', 'admin', $routeName), $routeParameters),
                        'staffIndex' => route(str_replace('{role}', 'staff', $routeName), $routeParameters),
                        'memberIndex' => route(str_replace('{role}', 'member', $routeName), $routeParameters),
                        'partnerIndex' => route(str_replace('{role}', 'partner', $routeName), $routeParameters),
                    ];
                } else {
                    $indexRoutes = [];
                }

                $current = (app()->getLocale() === $locale);

                $lang = array_merge($indexRoutes, [
                    'current' => $current,
                    'locale' => $locale,
                    'localeSlug' => $urlLocale,
                    'languageCode' => $localeLanguage,
                    'languageName' => mb_ucfirst($languageName),
                    'countryCode' => $localeCountry,
                    'countryName' => $countryName,
                ]);

                $languages[] = $lang;
                if ($current) {
                    $currentLanguage = $lang;
                }
            }
        }

        // No current language found, set to first found language to prevent errors
        if (! $currentLanguage) {
            $currentLanguage = $languages[0];
        }

        return [
            'current' => $currentLanguage,
            'all' => $languages,
        ];
    }

    /**
     * Get a list of all available locales.
     *
     * @param  string  $language  Optional. Two letter language code. If not provided, each locale is translated to its own language.
     * @return array List of all available locales translated to the requested language.
     */
    public function getAllLocales($language = null)
    {
        $locales = [];

        if (class_exists('ResourceBundle')) {
            $allLocales = \ResourceBundle::getLocales('') ?: [];

            foreach ($allLocales as $locale) {
                $localeParts = explode('_', $locale);

                // Language part
                $localeLanguage = $localeParts[0];

                $lang = locale_get_display_language($locale, $language ?? $localeLanguage);
                $country = locale_get_display_region($locale, $language ?? $localeLanguage);

                if ($country != '') {
                    $country = ' ('.$country.')';
                }
                $locales[$locale] = $lang.$country;
            }
        }

        return $locales;
    }

    /**
     * Get the full name of a locale code.
     *
     * @param  string  $locale  Locale e.g. en_US.
     * @param  string  $language  Optional. Two letter language code. If not provided, the locale name is translated to its own language.
     * @return string Full locale name, by default in native language.
     */
    public function getLocaleName($locale, $language = null)
    {
        $localeParts = explode('_', $locale);

        // Language part
        $localeLanguage = $localeParts[0];

        $lang = locale_get_display_language($locale, $language ?? $localeLanguage);
        $country = locale_get_display_region($locale, $language ?? $localeLanguage);

        if ($country != '') {
            $country = ' ('.$country.')';
        }

        return $lang.$country;
    }

    /**
     * Get the language name of a locale code.
     *
     * @param  string  $locale  Locale e.g. en_US.
     * @param  string  $language  Optional. Two letter language code. If not provided, the locale name is translated to its own language.
     * @return string Language name, by default in native language.
     */
    public function getLocaleLanguageName($locale, $language = null)
    {
        $localeParts = explode('_', $locale);

        // Language part
        $localeLanguage = $localeParts[0];

        $lang = locale_get_display_language($locale, $language ?? $localeLanguage);

        return $lang;
    }

    /**
     * Get the country name of a locale code.
     *
     * @param  string  $locale  Locale e.g. en_US.
     * @param  string  $language  Optional. Two letter language code. If not provided, the locale name is translated to its own language.
     * @return string Country name, by default in native language.
     */
    public function getLocaleCountryName($locale, $language = null)
    {
        $localeParts = explode('_', $locale);

        // Language part
        $localeLanguage = $localeParts[0];

        $country = locale_get_display_region($locale, $language ?? $localeLanguage);

        return $country;
    }

    /**
     * Get a list of all currencies.
     *
     * @param  string|null  $language  Two-letter language code.
     * @param  array|null  $allowedCurrencies  A list of allowed currencies.
     * @return array List of all available currencies.
     */
    public function getAllCurrencies(?string $language = null, ?array $allowedCurrencies = null): array
    {
        $oldLocale = app()->getLocale();

        if ($language) {
            app()->setLocale($language);
        } else {
            $language = explode('_', $oldLocale)[0];
            app()->setLocale($language);
        }

        $currencies = Currency::all()->all();

        $return = [];

        foreach ($currencies as $currency) {
            if (is_null($allowedCurrencies) || in_array($currency->id, $allowedCurrencies)) {
                $return[$currency->id] = $currency->name.' ('.$currency->id.')';
            }
        }

        // Go back to original locale
        app()->setLocale($oldLocale);

        return $return;
    }

    /**
     * Fetches detailed information about a specific currency.
     *
     * The details include its attributes (like the name, symbol, etc.), the step size for its smallest unit, and a
     * placeholder for formatting it in its localized form.
     *
     * @param  string  $currency_code  The ISO 4217 currency code (e.g., 'USD', 'EUR', etc.)
     * @return array|null The currency details or null if the currency could not be found
     */
    public function getCurrencyDetails(string $currency_code): ?array
    {
        $currency = Currency::find($currency_code);

        if (! $currency) {
            return null;
        }

        $currencyDetails = $currency->attributesToArray();

        // The step size for the smallest unit of this currency
        $currencyDetails['step'] = pow(10, -$currencyDetails['decimal_digits']);

        // A placeholder for formatting this currency in its localized form
        $currencyDetails['placeholder'] = number_format(0, $currencyDetails['decimal_digits'], '.', '');

        return $currencyDetails;
    }
}
