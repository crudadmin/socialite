<?php

namespace Admin\Socialite\Providers;

use Admin\Socialite\Events\OnSocialiteCallback;
use Admin\Socialite\Listeners\OnAppleSignInCallback;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    protected $listen = [
        \SocialiteProviders\Manager\SocialiteWasCalled::class => [
            // add your listeners (aka providers) here
            'Admin\\Socialite\\Providers\\Onetap\\GoogleOnetapExtendSocialite@handle',
        ],
        OnSocialiteCallback::class => [
            OnAppleSignInCallback::class,
        ],
    ];
}
