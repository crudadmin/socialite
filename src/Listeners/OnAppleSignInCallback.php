<?php

namespace Admin\Socialite\Listeners;

use Admin\Socialite\Helpers\Apple\AppleToken;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class OnAppleSignInCallback
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if ( $event->driverType !== 'apple' ){
            return;
        }

        $appleToken = new AppleToken(
            app(Configuration::class)
        );

        $token = $appleToken->generate();

        config()->set('services.apple.client_secret', $token);
    }
}
