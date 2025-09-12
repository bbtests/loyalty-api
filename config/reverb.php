<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reverb Application ID
    |--------------------------------------------------------------------------
    |
    | This value is the unique identifier for your Reverb application. This
    | value is used when creating the in-memory server instance. You should
    | set this to a UUID that is unique to your application.
    |
    */

    'app_id' => env('REVERB_APP_ID', 'bumpa-app-id'),

    /*
    |--------------------------------------------------------------------------
    | Reverb Application Key
    |--------------------------------------------------------------------------
    |
    | This value is the public key for your Reverb application. This key is
    | used to authenticate with the Reverb server when establishing a
    | connection. You should set this to a random 32-character string.
    |
    */

    'app_key' => env('REVERB_APP_KEY', 'bumpa-app-key'),

    /*
    |--------------------------------------------------------------------------
    | Reverb Application Secret
    |--------------------------------------------------------------------------
    |
    | This value is the secret key for your Reverb application. This key is
    | used to authenticate with the Reverb server when establishing a
    | connection. You should set this to a random 32-character string.
    |
    */

    'app_secret' => env('REVERB_APP_SECRET', 'bumpa-app-secret'),

    /*
    |--------------------------------------------------------------------------
    | Reverb Host
    |--------------------------------------------------------------------------
    |
    | This value is the host address of the Reverb server. You should set
    | this to the hostname or IP address of the server where Reverb is
    | running. For local development, this is typically "127.0.0.1" or
    | "localhost".
    |
    */

    'host' => env('REVERB_HOST', '127.0.0.1'),

    /*
    |--------------------------------------------------------------------------
    | Reverb Port
    |--------------------------------------------------------------------------
    |
    | This value is the port number that the Reverb server will listen on.
    | You should set this to an available port on your server. The default
    | port is 8000, but you can change this to any available port.
    |
    */

    'port' => env('REVERB_PORT', 8000),

    /*
    |--------------------------------------------------------------------------
    | Reverb Scheme
    |--------------------------------------------------------------------------
    |
    | This value determines the scheme used to connect to the Reverb server.
    | You should set this to "http" for local development or "https" for
    | production. The default is "http".
    |
    */

    'scheme' => env('REVERB_SCHEME', 'http'),

    /*
    |--------------------------------------------------------------------------
    | Reverb SSL Configuration
    |--------------------------------------------------------------------------
    |
    | These values are used to configure SSL for the Reverb server. You should
    | set these values if you want to use SSL/TLS encryption for your Reverb
    | server. The default values are for local development without SSL.
    |
    */

    'ssl' => [
        'local_cert' => env('REVERB_SSL_LOCAL_CERT'),
        'local_pk' => env('REVERB_SSL_LOCAL_PK'),
        'passphrase' => env('REVERB_SSL_PASSPHRASE'),
        'verify_peer' => env('REVERB_SSL_VERIFY_PEER', false),
        'allow_self_signed' => env('REVERB_SSL_ALLOW_SELF_SIGNED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reverb Options
    |--------------------------------------------------------------------------
    |
    | These values are additional options that can be passed to the Reverb
    | server. You can use these to customize the behavior of your Reverb
    | server instance.
    |
    */

    'options' => [
        'host' => env('REVERB_HOST', '127.0.0.1'),
        'port' => env('REVERB_PORT', 8081),
        'scheme' => env('REVERB_SCHEME', 'http'),
    ],

];
