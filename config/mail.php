<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array", "failover", "roundrobin"
    |
    */

    'mailers' => [

        /*
        |--------------------------------------------------------------------------
        | SMTP Mailer
        |--------------------------------------------------------------------------
        |
        | Standard SMTP configuration. Works with Gmail, Outlook, hosting providers,
        | or any SMTP server. Most universally compatible option.
        |
        | For Gmail: Use smtp.gmail.com, port 587, TLS encryption.
        | Requires an App Password if 2FA is enabled on your Google account.
        |
        */
        'smtp' => [
            'transport' => 'smtp',
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Mailgun Mailer
        |--------------------------------------------------------------------------
        |
        | Professional email delivery service by Mailchimp. Reliable and scalable
        | with detailed analytics. Good for growing businesses and high volume.
        |
        | Requires: MAILGUN_DOMAIN, MAILGUN_SECRET in .env
        | Optional: MAILGUN_ENDPOINT (defaults to US, use api.eu.mailgun.net for EU)
        |
        | The actual credentials are configured in config/services.php
        |
        */
        'mailgun' => [
            'transport' => 'mailgun',
        ],

        /*
        |--------------------------------------------------------------------------
        | Amazon SES Mailer
        |--------------------------------------------------------------------------
        |
        | Cost effective email at scale from AWS. Excellent deliverability.
        | Best for AWS users and large scale operations.
        |
        | Requires: AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION in .env
        |
        | The actual credentials are configured in config/services.php
        |
        */
        'ses' => [
            'transport' => 'ses',
        ],

        /*
        |--------------------------------------------------------------------------
        | Postmark Mailer
        |--------------------------------------------------------------------------
        |
        | Built specifically for transactional email. Industry leading delivery
        | speed. Best for speed critical applications.
        |
        | Requires: POSTMARK_TOKEN in .env
        |
        | The actual credentials are configured in config/services.php
        |
        */
        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Resend Mailer
        |--------------------------------------------------------------------------
        |
        | Modern email API built for developers. Simple, reliable, great DX.
        | Best for developer focused teams who want a clean API.
        |
        | Requires: RESEND_KEY in .env
        |
        | The actual credentials are configured via the resend/resend-laravel package.
        | See config/resend.php for additional options.
        |
        */
        'resend' => [
            'transport' => 'resend',
        ],

        /*
        |--------------------------------------------------------------------------
        | Sendmail Mailer
        |--------------------------------------------------------------------------
        |
        | Uses your server's built in mail system. No external service needed.
        | Best for simple setups on Linux servers with sendmail installed.
        |
        | Make sure sendmail is installed and properly configured on your server.
        |
        */
        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Mailpit Mailer (Development)
        |--------------------------------------------------------------------------
        |
        | Local development email testing. Catches all outgoing emails.
        | View captured emails at http://localhost:8025
        |
        | No real emails are sent. Perfect for local development.
        | Requires Mailpit to be running: https://mailpit.axllent.org/
        |
        */
        'mailpit' => [
            'transport' => 'smtp',
            'host' => '127.0.0.1',
            'port' => 1025,
            'encryption' => null,
            'username' => null,
            'password' => null,
        ],

        /*
        |--------------------------------------------------------------------------
        | Log Mailer (Development/Debugging)
        |--------------------------------------------------------------------------
        |
        | Writes emails to storage/logs/laravel.log instead of sending them.
        | Perfect for quick testing and debugging without external services.
        |
        | No real emails are sent.
        |
        */
        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Array Mailer (Testing)
        |--------------------------------------------------------------------------
        |
        | Stores emails in memory. Useful for automated testing where you want
        | to inspect sent emails programmatically.
        |
        */
        'array' => [
            'transport' => 'array',
        ],

        /*
        |--------------------------------------------------------------------------
        | Failover Mailer
        |--------------------------------------------------------------------------
        |
        | Attempts mailers in order. If the first fails, tries the next.
        | Good for high availability setups.
        |
        */
        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Round Robin Mailer
        |--------------------------------------------------------------------------
        |
        | Distributes emails across multiple mailers. Useful for load balancing
        | or staying within rate limits across multiple providers.
        |
        */
        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

];