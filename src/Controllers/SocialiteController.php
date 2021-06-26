<?php

namespace Admin\Socialite\Controllers;

use Admin\Socialite\SocialAuth;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SocialiteController extends Controller
{
    /**
     * Redirect
     *
     * @param  mixed  $driver
     *
     * @return  Response
     */
    public function redirect($driverType)
    {
        return (new SocialAuth($driverType))->authRedirect();
    }

    public function callback($driverType)
    {
        return (new SocialAuth($driverType))->callbackResponse();
    }
}
