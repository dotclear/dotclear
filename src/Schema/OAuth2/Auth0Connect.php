<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\OAuth2;

use Dotclear\Helper\OAuth2\Client\{ Methods, Provider, Token, User };

/**
 * @brief   Auth0 oAuth2 client provider class.
 *
 * @note    This provider is limited to connection stuff
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Auth0Connect extends Provider
{
    public const PROVIDER_ID          = 'auth0connect';
    public const PROVIDER_NAME        = 'Auth0';
    public const PROVIDER_DESCRIPTION = 'Allow user connection using %s application.';
    public const CONSOLE_URL          = 'https://manage.auth0.com/dashboard/'; // https://manage.auth0.com/dashboard/eu/xxx/applications
    public const AUTHORIZE_URL        = '/authorize';
    public const ACCESS_TOKEN_URL     = '/oauth/token';
    public const REVOKE_TOKEN_URL     = '/oauth/revoke';
    public const REQUEST_URL          = '/';
    public const SCOPE_DELIMITER      = ' ';
    public const DEFAULT_SCOPE        = ['profile', 'openid'];
    public const REQUIRE_DOMAIN       = true;

    protected function getAccessTokenHeaders(string $code): array
    {
        return [
            'Accept'       => 'application/json',
            'content-type' => 'application/json',
        ];
    }

    protected function getAccessTokenParameters(string $code): string
    {
        return (string) json_encode(parent::getAccessTokenParameters($code));
    }

    protected function getRevokeTokenHeaders(Token $token): array
    {
        return [
            'content-type' => 'application/json',
        ];
    }

    protected function getRevokeTokenParameters(Token $token): string
    {
        return (string) json_encode([
            'client_id'     => $this->consumer->get('key'),
            'client_secret' => $this->consumer->get('secret'),
            'token'         => $token->get('refresh_token'),
        ]);
    }

    public function getUser(Token $token): User
    {
        return User::parseUser($this->request(Methods::GET, 'userinfo', [], $token), [
            'uid'         => 'sub',
            'displayname' => 'nickname',
            'email'       => 'email',
            'avatar'      => 'picture',
        ]);
    }
}
