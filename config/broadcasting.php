<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    */

    'default' => env('BROADCAST_CONNECTION', env('BROADCAST_DRIVER', 'null')),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT', 443),
                'scheme' => env('REVERB_SCHEME', 'https'),
                'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
            ],
            /*
             * Browser WebSocket endpoint (Echo). In local Herd dev, the
             * reverb.herd.test TLS proxy often fails WebSocket upgrades; connect
             * directly to the Reverb process on 127.0.0.1:8080 instead.
             * Server-side broadcasting keeps using "options" above.
             */
            'client' => [
                'key' => env('REVERB_APP_KEY'),
                'host' => env('REVERB_CLIENT_HOST', env('APP_ENV') === 'local' ? '127.0.0.1' : env('REVERB_HOST')),
                'port' => (int) env('REVERB_CLIENT_PORT', env('APP_ENV') === 'local' ? env('REVERB_SERVER_PORT', 8080) : env('REVERB_PORT', 443)),
                'scheme' => env('REVERB_CLIENT_SCHEME', env('APP_ENV') === 'local' ? 'http' : env('REVERB_SCHEME', 'https')),
            ],
            'client_options' => [
                //
            ],
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                //
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
