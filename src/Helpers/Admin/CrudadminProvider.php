<?php

namespace Admin\Socialite\Helpers\Admin;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class CrudadminProvider extends AbstractProvider
{
    public const IDENTIFIER = 'CRUDADMIN';

    protected $scopes = ['profile'];

    public static function additionalConfigKeys(): array
    {
        return ['host'];
    }

    public function getBaseUrl()
    {
        return $this->getConfig('host').'/admin';
    }

    /**
     * Sends user to external CrudAdmin app to authorize the current app
     *
     * @param  mixed $state
     * @return string
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/oauth/authorize', $state);
    }

    /**
     * Path for obtaining temporary access token
     *
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl().'/oauth/token';
    }

    /**
     * Get logged admin user by token
     *
     * @param  mixed $token
     * @return void
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/api/user', [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
                // This is used to identify the model of currently logged user
                'Provider' => $this->credentialsResponseBody['provider'],
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $response)
    {
        $data = $response['data'];
        $user = $data['user'];

        return (new User)->setRaw($user)->map([
            'id'                => $data['driver'].'_'.$user['id'],
            'nickname'          => $user['username'],
            'name'              => $user['username'],
            'email'             => $user['email'],
            'avatar'            => $user['thumbnail'] ?? $user['avatarThumbnail'] ?? null,
        ]);
    }
}