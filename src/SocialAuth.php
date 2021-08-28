<?php

namespace Admin\Socialite;

use Admin;
use Admin\Socialite\Exceptions\SocialDriverException;
use Admin\Socialite\Exceptions\SocialMessageException;
use Carbon\Carbon;
use Crypt;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class SocialAuth
{
    const SESSION_PREFIX = 'socialite';
    const SESSION_URL_PREVIOUS_KEY = 'previous';

    /*
     * Driver type
     */
    private $driverType;

    /*
     * Socialite driver
     */
    private $driver;

    /*
     * User model
     */
    private $user;

    /*
     * Error mesage key in error message
     */
    static $errorMessageKey = 'errorMessage';

    /*
     * Success message key
     */
    static $successMessageKey = 'successMessage';

    /**
     * Photo column for stored photo
     *
     * @var  string
     */
    protected $photoColumn = 'photo';

    /*
     * Driver user columns
     */
    protected $driverColumns = [
        'facebook' => 'fb',
        'google' => 'gp',
        'instagrambasic' => 'ig',
        'apple' => 'apple',
        'onetap' => 'gp',
        'paypal' => 'pp',
        'paypal_sandbox' => 'pp',
    ];

    /**
     * Available supported drivers
     *
     * @var  array
     */
    protected $socialiteDrivers = [
        'google', 'facebook', 'apple', 'instagrambasic', 'paypal', 'paypal_sandbox',
    ];

    /**
     * Access token data
     *
     * @var  array
     */
    protected $accessTokenData = [
        'token' => null,
        'expires_in' => null,
    ];

    /**
     * This query params should be stored redirect request
     *
     * @var  array
     */
    protected $queryParamsToStore = ['access_token'];

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

    /*
     * Set driver type
     */
    public function __construct(string $driverType)
    {
        $this->setDriverType($driverType);
    }

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
     * Client eloquent
     *
     * @return  AdminModel
     */
    public function getUserModel()
    {
        return Admin::getModelByTable(config('admin_socialite.users_table'));
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
     * Set user model
     *
     * @param  Admin\Eloquent\AdminModel|null  $user
     */
    public function setUser($user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Returns user model
     *
     * @return  AdminModel|null
     */
    public function getUser()
    {
        return $this->user;
    }

    public function getScopes()
    {
        if ( $this->driverType == 'instagrambasic' ) {
            return ['user_profile', 'user_media'];
        }
    }

    /**
     * Redirect to google/facebook
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
     * Returns callback response
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
            $response = $this->makeErrorResponse();
        } catch (SocialMessageException $error) {
            $response = $this->makeErrorResponse($error->getMessage());
        }

        return $this->flushSessionOnResponse($response);
    }

    /**
     * Returns callback response
     *
     * @param  string  $token
     * @param  int|string  $expiresIn
     *
     * @return  redirect
     */
    public function tokenResponse(string $token = null, $expiresIn = null)
    {
        return $this->callbackResponse($token, $expiresIn);
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

        $this->logUser();
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

    private function driverMiddleware()
    {
        if ( is_callable(@static::$events['DRIVER_MIDDLEWARE']) ) {
            static::$events['DRIVER_MIDDLEWARE']($this->user, $this->driver, $this);
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

    /**
     * Retreive logged user
     *
     * @return  AdminModel|null
     */
    private function getLoggedUser($token = null)
    {
        //Use access token from session
        if ( $token || ($token = $this->getStorage('access_token')) || ($token = request('access_token')) ){
            request()->headers->set('Authorization', 'Bearer '.$token);
        }

        $this->setUser(
            auth()->guard($this->getGuardName())->user()
        );

        return $this->user;
    }

    /**
     * Build client model
     *
     * @param  AdminModel   $user
     * @param  bool         $update
     *
     * @return  AdminModel
     */
    public function assignSocialData($user, $update = false)
    {
        //When avatar has been downloaded
        if (
            $this->photoColumn
            && ($update === false || !($existingPhoto = $this->user->{$this->photoColumn}))
            && $avatar = $this->saveAvatar($this->user)
        ) {
            //We need remove existing photo
            if ( $existingPhoto ?? null ) {
                $existingPhoto->remove();
            }

            $this->user->{$this->photoColumn} = $avatar->filename;
        }

        //Get email address by user if is not set
        if ( !empty($this->driver->email) && empty($user->email) ) {
            $user->email = $this->driver->email;
        }

        //Asign username if not set
        if ( !$user->username && $username = $this->driver->name ){
            $user->username = $username;
        }

        if ( $user->getField($this->getDriverColumn('id')) ) {
            $user->{$this->getDriverColumn('id')} = $this->driver->id;
        }

        //Save specific platform email
        if ( !empty($this->driver->email) && $user->getField($this->getDriverColumn('email')) ) {
            $user->{$this->getDriverColumn('email')} = $this->driver->email;
        }

        //Update token value
        if ( $user->getField($this->getDriverColumn('token')) ) {
            $user->{$this->getDriverColumn('token')} = Crypt::encryptString($this->driver->accessToken);
        }

        //Update token expires at value
        if ( $user->getField($this->getDriverColumn('token_expires_at')) ) {
            $user->{$this->getDriverColumn('token_expires_at')} = $this->driver->expiresAt;
        }

        if ( is_callable(@static::$events['USER_MUTATE']) ) {
            $user = static::$events['USER_MUTATE']($user, $this->driver, $this);
        }

        return $user;
    }

    /**
     * Returns driver user column identifier
     *
     * @return  string
     */
    private function getDriverColumn($postfix)
    {
        return $this->driverColumns[$this->driverType].'_'.$postfix;
    }

    /**
     * Save avatar
     *
     * @return  string|bool
     */
    private function saveAvatar($user)
    {
        $image = $this->driver->avatar;

        if ( $image && $filename = $user->upload($this->photoColumn, $image) ) {
            return $filename;
        }
    }

    /**
     * Find user by driver email or identifier
     *
     * @return  AdminModel|null
     */
    private function findByDriverUser()
    {
        $user = $this->getUserModel()->where(function($query){
            //Find by email
            if ( !empty($this->driver->email) ) {
                $query->where('email', $this->driver->email);
            }

            //Find by driver identifier column
            $query->orWhere($this->getDriverColumn('id'), $this->driver->id);
        })->first();

        $this->setUser($user);

        return $user;
    }

    /*
     * Register user
     */
    private function registerUser()
    {
        $this->user = clone $this->getUserModel();

        $this->assignSocialData($this->user);

        $password = $this->user->password = str_random(6);

        $this->user->save();
        $this->user->fresh();

        if ( is_callable(@static::$events['USER_CREATED']) ) {
            static::$events['USER_CREATED']($this->user, $password, $this->getDriver(), $this);
        }

        return $this->user;
    }

    /**
     * Update existing user
     *
     * @return  void
     */
    private function updateExistingUser()
    {
        $this->assignSocialData($this->user, true);

        if ( is_callable(@static::$events['USER_UPDATE']) ) {
            static::$events['USER_UPDATE']($this->user, $this->getDriver(), $this);
        }

        $this->user->save();
    }

    /*
     * Log user into session
     */
    private function logUser()
    {
        if ( $this->isStateless() == false && !$this->user ) {
            auth()->guard($this->getGuardName())->login(
                $this->user,
                true
            );
        }
    }

    private function getSuccessData()
    {
        if ( $this->isStateless() === false ) {
            return [];
        }

        $token = $this->user->createToken('driver-'.$this->driverType);

        //On rest we want pass token data
        return [
            'auth_token' => $token->accessToken,
            'expires_in' => $token->token->expires_at->timestamp,
            'previous_path' => $this->getPrevious() ?: '/',
        ];
    }

    /**
     * Returns success authorization response, supporting rest, redirect...
     *
     * @return  Response|array
     */
    private function makeSuccessResponse()
    {
        if ( is_callable(@static::$events['SUCCESS_DATA_RESPONSE']) ) {
            $data = static::$events['SUCCESS_DATA_RESPONSE']($this);
        } else {
            $data = $this->getSuccessData();
        }

        //Redirect on previous page with session, but only when statless is disabled
        if ( $this->isStateless() == false ){
            //We want pass data into flash session
            foreach ($data as $key => $value) {
                session()->flash($data, $value);
            }

            return redirect($this->getPrevious() ? $this->getPrevious() : '/');
        }

        //If rest is disabled, we can redirect on given page with query params. This is stateless method
        else if ( $this->isRest() == false ) {
            //Data will be in REST passed as request query param
            $path = $this->addQueryParam($this->getPrevious(), $data);

            return redirect($path);
        }

        //If rest is enabled, and stateles also, we need reply with JSON response
        else {
            return autoAjax()->data($data);
        }
    }

    /**
     * Error redirect with message
     *
     * @param  string  $message
     *
     * @return  Redirect
     */
    public function makeErrorResponse($message = null)
    {
        $message = $message ?: sprintf(config('admin_socialite.messages.error'), $this->driverType);

        if ( $this->isStateless() ) {
            $path = $this->getPrevious() ?: $this->getBaseDir();

            $path = $this->addQueryParam($path, [
                self::$errorMessageKey => $message
            ]);

            if ( $this->isRest() == false ) {
                return redirect($path);
            } else {
                return autoAjax()->error($message, 500);
            }
        }

        if ( $this->isRest() == false ) {
            return redirect()->back()->withErrors([
                self::$errorMessageKey => $message
            ]);
        } else {
            return autoAjax()->error($message, 500);
        }
    }

    private function buildUrl(array $parts) {
        return (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') .
            ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') .
            (isset($parts['user']) ? "{$parts['user']}" : '') .
            (isset($parts['pass']) ? ":{$parts['pass']}" : '') .
            (isset($parts['user']) ? '@' : '') .
            (isset($parts['host']) ? "{$parts['host']}" : '') .
            (isset($parts['port']) ? ":{$parts['port']}" : '') .
            (isset($parts['path']) ? "{$parts['path']}" : '') .
            (isset($parts['query']) ? "?{$parts['query']}" : '') .
            (isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
    }

    private function addQueryParam($url, $params = [])
    {
        $params = array_filter(array_wrap($params));

        $url = parse_url($url);

        //Receive existing query from URL
        if ( isset($url['query']) ) {
            parse_str($url['query'], $query);
        }

        $query = @is_array($query) ? $query : [];

        //Bind and rewrite given params into existing query
        foreach ($params as $key => $value) {
            $query[$key] = $value;
        }

        $url['query'] = http_build_query($query);

        return $this->buildUrl($url);
    }

    public function getPrevious()
    {
        $previous = $this->getStorage(self::SESSION_URL_PREVIOUS_KEY);
        $current = url()->current();

        //If is same current path
        if ( substr($previous, 0, strlen($current)) == $current ){
            return;
        }

        return $previous;
    }

    private function setPreviousState()
    {
        $this->setStorage(self::SESSION_URL_PREVIOUS_KEY, request('url_previous', url()->previous()));

        //Use access token of logged user
        if ( $this->isStateless() === true ) {
            $paramsToStore = array_merge($this->queryParamsToStore, array_filter(explode(',', request('store'))));

            foreach ($paramsToStore as $key) {
                if ( $paramValue = request($key) ) {
                    $this->setStorage($key, $paramValue);
                }
            }
        }
    }

    private function flushSessionOnResponse($response)
    {
        session()->forget(self::SESSION_PREFIX);

        return $response;
    }

    public function setStorage($key, $value)
    {
        session()->put(self::SESSION_PREFIX.'.'.$key, $value);
    }

    public function getStorage($key)
    {
        return session()->get(self::SESSION_PREFIX.'.'.$key);
    }
}