<?php

return [
    /*
     * Bind routes automatically
     */
    'routes' => true,

    /**
     * Access keys and results will be passed into query_params, because we does not use session
     * If rest is true, stateless need's to be true as well.
     */
    'stateless' => false,

    /**
     * Can user be registred when is missing?
     */
    'register' => true,

    /**
     * Guard used for authentication.
     * If we use restfull API, we should use guard 'api'
     */
    'guard' => 'web',

    /**
     * If we use stateless redirect. We can specific basedir path
     *
     * for example: env('APP_URL')
     */
    'app_url' => env('APP_NUXT_URL') ?: env('APP_URL'),

    /**
     * Get admin model users eloquent
     */
    'users_table' => 'clients',

    /**
     * If we should save avatars
     */
    'avatars' => true,

    /**
     * Messages
     */
    'messages' => [
        'error' => _('Prihlásenie cez %s neprebehlo v poriadku, skúste opäť neskôr prosím.'),
    ],
];