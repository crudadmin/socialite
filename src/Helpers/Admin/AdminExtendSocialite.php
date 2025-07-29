<?php

namespace Admin\Socialite\Helpers\Admin;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AdminExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('crudadmin', AdminSocialiteProvider::class);
    }
}