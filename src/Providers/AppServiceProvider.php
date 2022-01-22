<?php

namespace Admin\Socialite\Providers;

use Admin\Socialite\Providers\EventsServiceProvider;
use App\Services\AppleToken;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        app()->register(EventsServiceProvider::class);

        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'admin_socialite'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../Config/services.php', 'services'
        );
    }

    public function boot()
    {
        //Load routes
        $this->loadRoutesFrom(__DIR__.'/../Routes/routes.php');

        $this->prepareAppleSignIn();
    }

    private function prepareAppleSignIn()
    {
        //Apple sign in is not enabled
        if ( !config('services.apple.client_id') ){
            return;
        }

        $this->app->bind(Configuration::class, fn () => Configuration::forSymmetricSigner(
            Sha256::create(),
            InMemory::plainText(config('services.apple.private_key')),
        ));
    }
}