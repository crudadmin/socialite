<?php

namespace Admin\Socialite;

use Admin\Socialite\Concerns\HasDriver;
use Admin\Socialite\Concerns\HasEvents;
use Admin\Socialite\Concerns\HasResponse;
use Admin\Socialite\Concerns\HasStorage;
use Admin\Socialite\Concerns\HasUser;
use Admin\Socialite\Exceptions\SocialDriverException;
use Admin\Socialite\Exceptions\SocialMessageException;
use Crypt;
use Exception;
use Log;

class SocialAuth
{
    use HasResponse,
        HasStorage,
        HasEvents,
        HasDriver,
        HasUser;

    /*
     * Driver type
     */
    private $driverType;

    /*
     * Socialite driver
     */
    private $driver;

    /*
     * Set driver type
     */
    public function __construct(string $driverType)
    {
        $this->setDriverType($driverType);
    }

    public function isRest()
    {
        return config('admin_socialite.rest');
    }

    public function isStateless()
    {
        return config('admin_socialite.stateless');
    }

    public function getGuardName()
    {
        return config('admin_socialite.guard');
    }

    /*
     * Stateless basedir
     */
    private function getBaseDir()
    {
        return config('admin_socialite.app_url');
    }

    /**
     * Returns driver type
     *
     * @return  string
     */
    public function getDriverType()
    {
        return $this->driverType;
    }

    /**
     * Driver type (google/facebook)
     *
     * @param  string  $type
     */
    public function setDriverType($type)
    {
        if ( $type == 'paypal' && env('PAYPAL_MODE') == 'sandbox' ){
            $type = 'paypal_sandbox';
        }

        $this->driverType = $type;
    }

    /**
     * Redirect to google/facebook for obtaining token
     *
     * @return  redirect
     */
    public function authRedirect()
    {
        $this->setPreviousState();

        $driver = Socialite::driver($this->getDriverType());

        if ( $scopes = $this->getScopes() ){
            $driver->scopes($scopes);
        }

        return $driver->redirect();
    }

    /**
     * Returns callback response from redirected response or by obtained token
     *
     * @param  null|string  $token
     * @param  int|string  $expiresIn
     *
     * @return  redirect
     */
    public function callbackResponse($token = null, $expiresIn = null)
    {
        try {
            $this->driverAuthorization($token, $expiresIn);

            $response = $this->makeSuccessResponse();
        } catch (SocialDriverException $error) {
            Log::error($error);

            $response = $this->makeErrorResponse();
        } catch (SocialMessageException $error) {
            Log::error($error);

            $response = $this->makeErrorResponse($error->getMessage());
        }

        $this->flushSessionOnResponse();

        return $response;
    }
}