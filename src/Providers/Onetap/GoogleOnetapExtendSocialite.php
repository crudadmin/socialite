<?php

namespace Admin\Socialite\Providers\Onetap;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GoogleOnetapExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('onetap', GoogleOnetapProvider::class);
    }
}
