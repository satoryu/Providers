<?php

namespace SocialiteProviders\VKontakte;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    protected $fields = ['uid', 'email', 'first_name', 'last_name', 'screen_name', 'photo'];

    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'VKONTAKTE';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['email'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://oauth.vk.com/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://oauth.vk.com/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $lang = $this->getConfig('lang');
        $lang = $lang ? '&language='.$lang : '';
        $response = $this->getHttpClient()->get(
            'https://api.vk.com/method/users.get?access_token='.$token.'&fields='.implode(',', $this->fields).$lang
        );

        $response = json_decode($response->getBody()->getContents(), true)['response'][0];

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => Arr::get($user, 'uid'),
            'nickname' => Arr::get($user, 'screen_name'),
            'name' => trim(Arr::get($user, 'first_name').' '.Arr::get($user, 'last_name')),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'photo'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * Set the user fields to request from Vkontakte.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['lang'];
    }
}
