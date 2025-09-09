<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => env('APP_NAME', 'Laravel API'),
        'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),
    ],
    'api_keys' => [
        'web' => env('WEB_API_KEY'),
        'mobile' => env('MOBILE_API_KEY'),
    ],
    'notifications' => [
        'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'from_name' => env('MAIL_FROM_NAME', 'Orbit API'),
        'support_email' => env('SUPPORT_EMAIL', 'support@example.com'),
    ],
    'pagination' => [
        'default_per_page' => env('DEFAULT_PER_PAGE', 10),
        'max_per_page' => env('MAX_PER_PAGE', 100),
    ],
    'rate_limiting' => [
        'api' => env('RATE_LIMIT_API', 10000),
        'short' => env('RATE_LIMIT_SHORT', 100),
        'medium' => env('RATE_LIMIT_MEDIUM', 1000),
        'long' => env('RATE_LIMIT_LONG', 10000),
    ],
    'super_admin_email' => env('SUPER_ADMIN', 'superadmin').'@'.env('APP_DOMAIN', 'localhost'),
    'super_admin_password' => env('SUPER_ADMIN_PASSWORD', 'P@ssword!'),
    'urls' => [
        'login' => env('LOGIN_URL', '/auth/login'),
        'reset_password' => env('RESET_PASSWORD_URL', '/auth/reset-password'),
        'dashboard' => env('DASHBOARD_URL', '/dashboard'),
        'support' => env('SUPPORT_URL', '/support'),
        'payment_callback' => env('PAYMENT_CALLBACK_URL', '/dashboard?payment=success'),
    ],
];
