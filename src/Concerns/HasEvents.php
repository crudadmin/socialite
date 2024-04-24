<?php

namespace Admin\Socialite\Concerns;

trait HasEvents
{
    /**
     * Available social auth events
     *
     * @var  array
     */
    protected static $events = [
        // 'SUCCESS_DATA_RESPONSE' => function(SocialAuth $auth){
        //     return [];
        // },
        // 'DRIVER_MUTATE' => function($driver, SocialAuth $auth){ },
        // 'DRIVER_MIDDLEWARE' => function($user, $driver, SocialAuth $auth){ },
        // 'USER_FETCH' => function($user, $driver, SocialAuth $auth){ },
        // 'USER_MUTATE' => function($user, $driver, SocialAuth $auth){ },
        // 'USER_UPDATE' => function($user, $driver, SocialAuth $auth){ },
    ];

    /**
     * Set auth event
     *
     * @param  string  $event
     * @param  callable  $callback
     */
    public static function setEvent(string $event, callable $callback)
    {
        static::$events[$event] = $callback;
    }

    public function onSuccess($callback)
    {
        static::$events['SUCCESS_DATA_RESPONSE'] = $callback;

        return $this;
    }
}