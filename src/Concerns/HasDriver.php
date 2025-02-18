<?php

namespace Admin\Socialite\Concerns;

use Admin\Socialite\Exceptions\SocialDriverException;
use Admin\Socialite\Exceptions\SocialMessageException;
use Exception;
use Laravel\Socialite\Facades\Socialite;
use Carbon\Carbon;

trait HasDriver
{
    public function getScopes()
    {
        if ( $this->driverType == 'instagrambasic' ) {
            return ['user_profile', 'user_media'];
        }
    }

    /**
     * Process social auth authorization or throw SocialDriverException exception
     *
     * @param  null|string  $token
     * @param  null|string|int $expiresIn
     *
     * @return  void
     */
    public function driverAuthorization($token = null, $expiresIn = null)
    {
        $this->getDriver($token, $expiresIn);

        if (
            //If is user logged already, we want update him
            $this->getLoggedUser()

            //Or if user has been found in db by credentials
            || $this->findByDriverUser()
        ) {
            $this->driverMiddleware();

            $this->updateExistingUser();
        }

        //Register new user
        else {
            $this->driverMiddleware();

            $this->registerUser();
        }

        $this->loginUser();
    }

    /**
     * Get driver by access token or throw SocialDriverException exception
     *
     * @param  null|string  $token
     * @param  null|string  $expiresIn
     *
     * @return  Object
     */
    public function getDriver($token = null, $expiresIn = null)
    {
        if ( $this->driver ) {
            return $this->driver;
        }

        try {
            $driver = Socialite::driver($this->driverType);

            if ( $this->isStateless() === true && $token ){
                $driver->stateless();
            }

            if ( is_callable(@static::$events['DRIVER_MUTATE']) ) {
                $data = static::$events['DRIVER_MUTATE']($this, $driver);
            }

            if ( $token ){
                $user = $driver->userFromToken($token);
            } else {
                $user = $driver->user();
            }

            if ( is_callable(@static::$events['USER_FETCH']) ) {
                $user = static::$events['USER_FETCH']($user, $driver, $this);
            }

            $this->driver = $user;

            $this->setLongLiveAccessToken($token, $expiresIn);

            return $this->driver;
        } catch(SocialMessageException $e){
            throw $e;
        } catch(Exception $e){
            throw new SocialDriverException($e->getMessage());
        }
    }

    /**
     * In some cases we want fetch and save long live access token
     *
     * @param  string|null  $token
     * @param  string|null  $expiresIn
     */
    protected function setLongLiveAccessToken($token = null, $expiresIn = null)
    {
        $token = $token ?: $this->driver->token;

        $longLiveAccessToken = $token;

        //Get long live access token for instagam
        if ( $this->driverType == 'instagrambasic' ) {
            $igTokenData = $this->getInstagramLongAccessToken($token);

            $longLiveAccessToken = $igTokenData->access_token;
            $expiresIn = $igTokenData->expires_in ?: $expiresIn;
        }

        //Expires in seconds
        $expiresIn = $expiresIn ?: $this->driver->expiresIn;

        //Set expires at date and fetch long live acccess token
        $this->driver->expiresAt = $expiresIn ? Carbon::now()->addSeconds($expiresIn) : null;
        $this->driver->accessToken = $longLiveAccessToken;
    }

    public static function getInstagramLongAccessToken($shortToken)
    {
        $url = 'https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret='.ENV('SOCIAL_INSTAGRAM_SECRET').'&access_token='.$shortToken;

        $data = file_get_contents($url);
        $data = json_decode($data);

        return $data;
    }

    private function driverMiddleware()
    {
        if ( is_callable(@static::$events['DRIVER_MIDDLEWARE']) ) {
            static::$events['DRIVER_MIDDLEWARE']($this->user, $this->driver, $this);
        }
    }
}