<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Snowflake
    |--------------------------------------------------------------------------
    |
    | This setting determines whether the Snowflake ID generation is enabled.
    |
    | Available Settings: true or false
    |
    */
    'use_snowflake' => env('SNOWFLAKE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Snowflake Epoch
    |--------------------------------------------------------------------------
    |
    | This is the start date for Snowflake ID generation. Set this to the date
    | when the application development started. Do not set a future date.
    | Once the service is live, do not change this setting.
    |
    | Available Settings: Y-m-d H:i:s
    |
    */
    'epoch' => env('SNOWFLAKE_EPOCH', '2023-02-01 00:00:00'),

    /*
    |--------------------------------------------------------------------------
    | Snowflake Configuration
    |--------------------------------------------------------------------------
    |
    | These settings are used to configure the Snowflake ID generation.
    | If you are using multiple servers, assign a unique ID (1-31) to each
    | server for the worker_id and datacenter_id.
    |
    | Available Settings: 1-31
    |
    */
    'worker_id' => env('SNOWFLAKE_WORKER_ID', 1),

    'datacenter_id' => env('SNOWFLAKE_DATACENTER_ID', 1),
];
