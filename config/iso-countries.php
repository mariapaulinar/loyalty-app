<?php

return [

    // Supported locales for names (countries, languages, currencies)
    // IMPORTANT: After modifying, (re-)seed the database to apply changes
    'locales' => [
        'ar',
        'bg',
        'cs',
        'da',
        'de',
        'el',
        'en',
        'eo',
        'es',
        'et',
        'eu',
        'fi',
        'fr',
        'hu',
        'it',
        'ja',
        'ko',
        'lt',
        'nl',
        'no',
        'pl',
        'pt',
        'ro',
        'ru',
        'sk',
        'sv',
        'th',
        'uk',
        'zh',
    ],

    // Path for storing your own SQLITE database
    'database_path' => database_path('iso-countries.sqlite'),
];
