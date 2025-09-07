<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Loyalty Program Configuration
    |--------------------------------------------------------------------------
    */

    'points_per_dollar' => env('LOYALTY_POINTS_PER_DOLLAR', 10),
    'cashback_percentage' => env('CASHBACK_PERCENTAGE', 2),

    'achievements' => [
        'first_purchase' => [
            'name' => 'First Purchase',
            'description' => 'Make your first purchase',
            'points_required' => 0,
        ],
        'loyal_customer' => [
            'name' => 'Loyal Customer',
            'description' => 'Earn 1000 loyalty points',
            'points_required' => 1000,
        ],
        'big_spender' => [
            'name' => 'Big Spender',
            'description' => 'Spend over $500 in a single transaction',
            'points_required' => 0,
        ],
        'frequent_buyer' => [
            'name' => 'Frequent Buyer',
            'description' => 'Make 10 purchases',
            'points_required' => 0,
        ],
        'point_master' => [
            'name' => 'Point Master',
            'description' => 'Earn 5000 loyalty points',
            'points_required' => 5000,
        ],
    ],

    'badges' => [
        'bronze' => [
            'name' => 'Bronze Member',
            'tier' => 1,
            'requirements' => ['points_minimum' => 0],
        ],
        'silver' => [
            'name' => 'Silver Member',
            'tier' => 2,
            'requirements' => ['points_minimum' => 2500],
        ],
        'gold' => [
            'name' => 'Gold Member',
            'tier' => 3,
            'requirements' => ['points_minimum' => 10000],
        ],
        'platinum' => [
            'name' => 'Platinum Member',
            'tier' => 4,
            'requirements' => [
                'points_minimum' => 25000,
                'purchases_minimum' => 50,
            ],
        ],
    ],
];
