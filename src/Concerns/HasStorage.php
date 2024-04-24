<?php

namespace Admin\Socialite\Concerns;

trait HasStorage
{
    const SESSION_PREFIX = 'socialite';
    const SESSION_URL_PREVIOUS_KEY = 'previous';

    protected function setPreviousState()
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

    protected function flushSessionOnResponse($response)
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