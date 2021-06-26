<?php

return [
    'facebook' => [
        'client_id' => env('FACEBOOK_ID'),
        'client_secret' => env('FACEBOOK_SECRET'),
        'redirect' => (@$_SERVER['HTTPS'] ? 'https' : 'http').'://'.@$_SERVER['HTTP_HOST'].'/socialite/facebook/callback',
    ],

    'google' => [
        'client_id' => env('GOOGLE_ID'),
        'client_secret' => env('GOOGLE_SECRET'),
        'redirect' => (@$_SERVER['HTTPS'] ? 'https' : 'http').'://'.@$_SERVER['HTTP_HOST'].'/socialite/google/callback',
    ],

    'paypal_sandbox' => [
      'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID'),
      'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET'),
      'redirect' => (@$_SERVER['HTTPS'] ? 'https' : 'http').'://'.@$_SERVER['HTTP_HOST'].'/socialite/paypal/callback'
    ],

    'paypal' => [
      'client_id' => env('PAYPAL_CLIENT_ID'),
      'client_secret' => env('PAYPAL_CLIENT_SECRET'),
      'redirect' => (@$_SERVER['HTTPS'] ? 'https' : 'http').'://'.@$_SERVER['HTTP_HOST'].'/socialite/paypal/callback'
    ],

    'apple' => [
      'client_id' => env('APPLE_CLIENT_ID'),
      'client_secret' => env('APPLE_CLIENT_SECRET'),
      'redirect' => (@$_SERVER['HTTPS'] ? 'https' : 'http').'://'.@$_SERVER['HTTP_HOST'].'/socialite/apple/callback'
    ],

    'instagrambasic' => [
        'client_id' => env('INSTAGRAM_ID'),
        'client_secret' => env('INSTAGRAM_SECRET'),
        'redirect' => (@$_SERVER['HTTPS'] ? 'https' : 'http').'://'.@$_SERVER['HTTP_HOST'].'/socialite/instagrambasic/callback',
    ],
];