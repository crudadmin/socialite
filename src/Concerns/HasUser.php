<?php

namespace Admin\Socialite\Concerns;

use Admin;

trait HasUser
{
    /**
     * Photo column for stored photo
     *
     * @var  string
     */
    public static $photoColumn = 'photo';

    /*
     * Driver user columns
     */
    public static $driverColumns = [
        'facebook' => 'fb',
        'google' => 'gp',
        'instagrambasic' => 'ig',
        'apple' => 'apple',
        'onetap' => 'gp',
        'paypal' => 'pp',
        'paypal_sandbox' => 'pp',
        'azure' => 'az',
    ];

    /*
     * User model
     */
    protected $user;

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

    /**
     * Returns aut class
     *
     * @return  Auth
     */
    public function getAuth()
    {
        return auth()->guard($this->getGuardName());
    }

    /*
     * Log user into session
     */
    protected function loginUser()
    {
        if ( $this->isStateless() == false && $this->user ) {
            $this->getAuth()->login(
                $this->user,
                true
            );
        }
    }

    /**
     * Find user by driver email or identifier
     *
     * @return  AdminModel|null
     */
    protected function findByDriverUser()
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

    /**
     * Retreive logged user
     *
     * @return  AdminModel|null
     */
    protected function getLoggedUser($token = null)
    {
        //Use access token from session
        if ( $token || ($token = $this->getStorage('access_token')) || ($token = request('access_token')) ){
            request()->headers->set('Authorization', 'Bearer '.$token);
        }

        $this->setUser(
            $this->getAuth()->user()
        );

        return $this->user;
    }

    /*
     * Register user
     */
    protected function registerUser()
    {
        $this->user = clone $this->getUserModel();

        $this->assignSocialData($this->user);

        if ( $this->user->getField('password') ) {
            $password = $this->user->password = str_random(6);
        } else {
            $password = null;
        }

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
    protected function updateExistingUser()
    {
        $this->assignSocialData($this->user, true);

        if ( is_callable(@static::$events['USER_UPDATE']) ) {
            static::$events['USER_UPDATE']($this->user, $this->getDriver(), $this);
        }

        $this->user->save();
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
            self::$photoColumn
            && $this->user->getField(self::$photoColumn)
            && ($update === false || !($existingPhoto = $this->user->{self::$photoColumn}))
            && $avatar = $this->saveAvatar($this->user)
        ) {
            //We need remove existing photo
            if ( $existingPhoto ?? null ) {
                $existingPhoto->remove();
            }

            $this->user->{self::$photoColumn} = $avatar->filename;
        }

        //Get email address by user if is not set
        if ( !empty($this->driver->email) && empty($user->email) ) {
            $user->email = $this->driver->email;
        }

        //Asign username if not set
        if ( !$user->username ){
            //If no name has been set, find username query param. (For Apple sign-in is passed)
            $username = $this->driver->name ?: trim(request('username', ''));

            //Generate username if is required and no username has been set.
            if ( $user->hasFieldParam('username', 'required') && !$username ){
                $username = $this->generateUsername($user);
            }

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
     * Save avatar
     *
     * @return  string|bool
     */
    protected function saveAvatar($user)
    {
        $image = $this->driver->avatar;

        if ( $image && $filename = $user->upload(self::$photoColumn, $image) ) {
            return $filename;
        }
    }

    /**
     * Returns driver user column identifier
     *
     * @return  string
     */
    private function getDriverColumn($postfix)
    {
        return self::$driverColumns[$this->driverType].'_'.$postfix;
    }

    /**
     * When no username is provided, return generated one.
     *
     * @param  string  $user
     * @return  string
     */
    public function generateUsername($user)
    {
        if ( method_exists($user, 'generateUsername') ){
            return $user->generateUsername();
        }

        return 'Anonym '.rand(0000, 9999);
    }
}