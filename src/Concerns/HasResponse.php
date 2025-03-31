<?php

namespace Admin\Socialite\Concerns;

trait HasResponse
{
    /*
     * Error mesage key in error message
     */
    static $errorMessageKey = 'errorMessage';

    /**
     * Build sample token response for sanctrum/scout
     *
     * @return  array
     */
    public function getDefaultSuccessData()
    {
        $tokenData = [];

        // Generate new token data if token is not in request
        if ( $this->user && is_null($this->getTokenFromRequest()) ) {
            $token = $this->user->createToken('driver-'.$this->driverType);

            //Sanctrum
            if ( $token instanceof \Laravel\Sanctum\NewAccessToken ) {
                $tokenData['auth_token'] = $token->plainTextToken;
                $tokenData['expires_in'] = $token->accessToken?->expired_at;
            }

            //Scoun
            else {
                $tokenData['auth_token'] = $token->accessToken;
                $tokenData['expires_in'] = $token->token?->expires_at?->timestamp;
            }
        }

        return [
            ...$tokenData,
            'previous_path' => $this->getPrevious(),
        ];
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
            $path = $this->getPrevious();

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

    /**
     * Returns success authorization response, supporting rest, redirect...
     *
     * @return  Response|array
     */
    public function makeSuccessResponse()
    {
        if ( is_callable(@static::$events['SUCCESS_DATA_RESPONSE']) ) {
            $data = static::$events['SUCCESS_DATA_RESPONSE']($this);
        } else if ( $this->isStateless() === true ) {
            $data = $this->getDefaultSuccessData();
        } else {
            $data = [];
        }

        //Redirect on previous page with session, but only when statless is disabled
        if ( $this->isStateless() == false ){
            //We want pass data into flash session
            foreach ($data as $key => $value) {
                session()->flash($data, $value);
            }

            return redirect($this->getPrevious());
        }

        //If rest is disabled, we can redirect on given page with query params. This is stateless method
        else if ( $this->isRest() == false ) {
            //Data will be in REST passed as request query param
            $path = $this->addQueryParam($this->getPrevious(), $data);

            return redirect($path);
        }

        //If rest is enabled, and stateles also, we need reply with JSON response
        else {
            if ( is_array($data) ) {
                return autoAjax()->data($data);
            }

            return $data;
        }
    }

    public function getPrevious()
    {
        $previous = $this->getStorage(self::SESSION_URL_PREVIOUS_KEY);
        $current = url()->current();

        //If is same current path
        if ( substr($previous, 0, strlen($current)) == $current ){
            return $this->getBaseDir();
        }

        return $previous ?: $this->getBaseDir();
    }

    protected function addQueryParam($url, $params = [])
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
}