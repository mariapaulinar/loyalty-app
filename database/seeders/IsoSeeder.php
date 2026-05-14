<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Fluent;

class IsoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Currencies...');
        Currency::query()->truncate();

        // Load Currency JSON
        $json = File::get(database_path().'\data\iso\currencies.json');
        $json = json_decode($json);

        collect($json)->each(function ($currency) {
            $currency = new Fluent($currency);

            Currency::create([
                'id' => $currency['code'],
                'name' => $currency['name'],
                'name_plural' => $currency['name_plural'],
                'symbol' => $currency['symbol'],
                'symbol_native' => $currency['symbol_native'],
                'decimal_digits' => $currency['decimal_digits'],
                'rounding' => $currency['rounding'],
            ]);
        });

        // ==================================================================

        $this->command->info('Seeding Languages...');
        Language::query()->truncate();

        // Load Currency JSON
        $json = File::get(database_path().'\data\iso\languages.json');
        $json = json_decode($json);

        collect($json)->each(function ($language) {
            $language = new Fluent($language);

            Language::create([
                'id' => $language['639-1'],
                'iso639_2' => $language['639-2'],
                'iso639_2b' => $language['639-2/B'],
                'name' => $language['name'],
                'native_name' => $language['nativeName'],
                'family' => $language['family'],
                'wiki_url' => $language['wikiUrl'],
            ]);
        });

        // ==================================================================

        $this->command->info('Seeding Countries...');
        Country::query()->truncate();
        DB::table('country_language')->truncate();
        DB::table('country_currency')->truncate();
        DB::table('country_country')->truncate();

        // Load countries and relationships as JSON from RestCountries API
        $json = File::get(database_path().'\data\iso\countries.json');
        $json = json_decode($json);

        collect($json)->each(function ($country) {
            $country = new Fluent($country);

            $country_model = Country::create([
                'id' => $country['alpha2Code'],
                'alpha_3' => $country['alpha3Code'],
                'name' => $country['name'],
                'native_name' => $country['nativeName'] ?? null,
                'capital' => $country['capital'] ?? null,
                'top_level_domain' => collect($country['topLevelDomain'])->first(),
                'calling_code' => collect($country['callingCodes'])->first(),
                'region' => $country['region'] ?? null,
                'subregion' => $country['subregion'] ?? null,
                'population' => $country['population'] ?? null,
                'lat' => collect($country['latlng'])->first(),
                'lon' => collect($country['latlng'])->last(),
                'demonym' => $country['demonym'] ?? null,
                'area' => $country['area'] ?? null,
                'gini' => $country['gini'] ?? null,
            ]);

            // Attach relations
            $country_model->languages()->attach(Language::find(collect($country['languages'])->pluck('iso639_1')));

            $country_model->currencies()->attach(Currency::find(collect($country['currencies'])->pluck('code')));

            if ($country['borders']) {
                $country_model->neighbours()->attach(Country::whereIn('alpha_3', $country['borders'])->get());
            }
        });

        // Download name translations
        $this->downloadTranslationsLocal(Country::class);
        $this->downloadTranslationsLocal(Language::class);
        $this->downloadTranslationsLocal(Currency::class);
    }

    public function downloadTranslationsLocal($model): void
    {
        $this->command->info('Downloading translations for '.$model);

        $locales = collect(config('app.locale'))->merge(config('app.fallback_locale'))->merge(config('iso-countries.locales'))->unique();

        foreach ($locales as $locale) {
            $urls = [
                Country::class => database_path().'/data/iso/country/'.$locale.'.json',
                Language::class => database_path().'/data/iso/language/'.$locale.'.json',
                Currency::class => database_path().'/data/iso/currency/'.$locale.'.json',
            ];

            $this->command->info('Loading names for locale "'.$locale.'"...');

            $json = File::get($urls[$model]);
            $json = json_decode($json);

            foreach ($json as $id => $name) {
                $item = app($model)::find($id);

                if ($item) {
                    $item->setTranslation('name', $locale, $name);
                    $item->save();
                }
            }
        }
    }

    public function downloadTranslationsOnline($model): void
    {
        $this->command->info('Downloading translations for '.$model);

        $locales = collect(config('app.locale'))->merge(config('app.fallback_locale'))->merge(config('iso-countries.locales'))->unique();

        foreach ($locales as $locale) {
            $urls = [
                Country::class => 'https://raw.githubusercontent.com/umpirsky/country-list/master/data/'.$locale.'/country.json',
                Language::class => 'https://raw.githubusercontent.com/umpirsky/language-list/master/data/'.$locale.'/language.json',
                Currency::class => 'https://raw.githubusercontent.com/umpirsky/currency-list/master/data/'.$locale.'/currency.json',
            ];

            $this->command->info('Loading names for locale "'.$locale.'"...');

            $response = Http::get($urls[$model]);

            File::put(database_path().'/data/iso/country/'.$locale.'.json', $response);
            File::put(database_path().'/data/iso/language/'.$locale.'.json', $response);
            File::put(database_path().'/data/iso/currency/'.$locale.'.json', $response);

            if ($response->successful()) {
                foreach ($response->json() as $id => $name) {
                    $item = app($model)::find($id);

                    if ($item) {
                        $item->setTranslation('name', $locale, $name);
                        $item->save();
                    }
                }
            } else {
                $this->command->warn('Locale not available for download!');
            }
        }
    }
}
