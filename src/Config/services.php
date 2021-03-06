<?php

return [
    'facebook' => [
        'client_id' => env('FACEBOOK_ID'),
        'client_secret' => env('FACEBOOK_SECRET'),
        'redirect' => (env('SOCIALITE_APP_URL') ?: env('APP_URL')).'/socialite/facebook/callback',
    ],

    'google' => [
        'client_id' => env('GOOGLE_ID'),
        'client_secret' => env('GOOGLE_SECRET'),
        'redirect' => (env('SOCIALITE_APP_URL') ?: env('APP_URL')).'/socialite/google/callback',
    ],

    'onetap' => [
        'client_id' => env('GOOGLE_ID'),
        'client_secret' => env('GOOGLE_SECRET'),
        'redirect' => (env('SOCIALITE_APP_URL') ?: env('APP_URL')).'/socialite/onetap/callback',
    ],

    'paypal_sandbox' => [
        'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID'),
        'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET'),
        'redirect' => (env('SOCIALITE_APP_URL') ?: env('APP_URL')).'/socialite/paypal/callback'
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_LIVE_CLIENT_ID'),
        'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET'),
        'redirect' => (env('SOCIALITE_APP_URL') ?: env('APP_URL')).'/socialite/paypal/callback'
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => (env('SOCIALITE_APP_URL') ?: env('APP_URL')).'/socialite/apple/callback',
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'),
    ],

    'instagrambasic' => [
        'client_id' => env('INSTAGRAM_ID'),
        'client_secret' => env('INSTAGRAM_SECRET'),
        'redirect' => (env('SOCIALITE_APP_URL') ?: env('APP_URL')).'/socialite/instagrambasic/callback',
    ],
];