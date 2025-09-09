<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    */

    'default_provider' => env('PAYMENT_PROVIDER', 'paystack'),

    'providers' => [
        'paystack' => [
            'enabled' => env('PAYSTACK_ENABLED', false),
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'base_url' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
        ],
        'flutterwave' => [
            'enabled' => env('FLUTTERWAVE_ENABLED', false),
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Limits
    |--------------------------------------------------------------------------
    */

    'limits' => [
        'minimum_amount' => env('PAYMENT_MIN_AMOUNT', 1.0),
        'maximum_amount' => env('PAYMENT_MAX_AMOUNT', 1000000.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */

    'settings' => [
        'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'NGN'),
        'supported_currencies' => ['NGN', 'USD', 'GBP', 'EUR'],
        'callback_url' => env('PAYMENT_CALLBACK_URL', '/payment/callback'),
        'webhook_url' => env('PAYMENT_WEBHOOK_URL', '/payment/webhook'),
    ],
];
