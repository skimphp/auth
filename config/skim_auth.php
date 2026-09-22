<?php declare(strict_types=1);

return [
    'table_prefix' => 'skim_',

    'after_login'    => '/dashboard',
    'after_register' => '/dashboard',
    'after_logout'   => '/login',

    'verify_email' => true,
    'remember_me'  => true,
    'remember_days'=> 30,

    'ui' => [
        'layout'    => null,
        'published' => false,
    ],

    'social' => [
        'google' => [
            'client_id'     => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'enabled'       => env('GOOGLE_CLIENT_ID') !== null,
        ],
        'github' => [
            'client_id'     => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'enabled'       => env('GITHUB_CLIENT_ID') !== null,
        ],
        'facebook' => [
            'client_id'     => env('FACEBOOK_CLIENT_ID'),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'enabled'       => env('FACEBOOK_CLIENT_ID') !== null,
        ],
    ],
];
