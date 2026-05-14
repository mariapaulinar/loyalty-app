<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable AI
    |--------------------------------------------------------------------------
    |
    | This option allows you to enable or disable the AI features in your
    | application. Set the 'OPENAI_ENABLED' environment variable to 'true'
    | to enable, or 'false' to disable.
    */
    'enabled' => env('OPENAI_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    |
    | These settings configure the API key, organization, and model used for
    | authenticating and interacting with the OpenAI API. Your API key and 
    | organization can be found in your OpenAI dashboard. The default model 
    | is 'gpt-4-32k', but you can change it to any supported model.
    |
    | Supported models:
    | - gpt-4o
    | - gpt-4-turbo
    | - gpt-3.5-turbo
    */
    'api_key' => env('OPENAI_API_KEY', null),
    'organization' => env('OPENAI_ORGANIZATION', null),
    'model' => env('OPENAI_MODEL', 'gpt-4'),

    /*
    |--------------------------------------------------------------------------
    | Base URI
    |--------------------------------------------------------------------------
    |
    | This is the base URI for the OpenAI API. If you are using an OpenAI API 
    | compatible service, you can specify its base URL here.
    */
    'base_uri' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),

];
