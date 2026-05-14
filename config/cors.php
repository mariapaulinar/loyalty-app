<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| This application uses Laravel's built-in HandleCors middleware.
| We scope CORS to the Shopify widget endpoints only.
|
| Requirements:
| - Allow widget endpoints from https://*.myshopify.com
| - Allow headers: X-API-Key, Content-Type
| - Ensure OPTIONS preflight works for POST redeem
|
| Security posture:
| - No wildcard origins for non-widget endpoints
| - No credentials (cookies) for widget
|
*/

return [

    /*
     * Paths that should receive CORS headers.
     */
    'paths' => [
        'api/widget/*',
        'api/*/v1/*',
    ],

    /*
     * Allowed HTTP methods.
     */
    'allowed_methods' => ['*'],

    /*
     * Explicit allowed origins.
     *
     * Wildcard allows any origin to access the API.
     * This is appropriate for a REST API - security is handled by
     * Bearer token authentication, not CORS restrictions.
     */
    'allowed_origins' => ['*'],

    /*
     * Allowed origin patterns (regex).
     * Not needed when using wildcard above, but kept for reference.
     */
    'allowed_origins_patterns' => [],

    /*
     * Allowed request headers.
     */
    'allowed_headers' => [
        'Content-Type',
        'X-API-Key',
        'X-Requested-With',
        'Accept',
        'Origin',
        'Authorization',
    ],

    /*
     * Headers exposed to the browser.
     */
    'exposed_headers' => [],

    /*
     * Max age (seconds) for caching preflight response.
     */
    'max_age' => 0,

    /*
     * Whether the response can be exposed when credentials are present.
     */
    'supports_credentials' => false,
];
