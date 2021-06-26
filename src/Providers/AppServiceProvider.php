<?php

namespace Admin\Socialite\Providers;

use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
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
    }
}