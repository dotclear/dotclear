<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\OAuth2;

use Dotclear\Helper\OAuth2\Client\{ GrantTypes, Methods, Provider, Token, User };

/**
 * @brief   Lwa (Login With Amazon) oAuth2 client provider class.
 *
 * @note    This provider is limited to connection stuff
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Lwa extends Provider
{
    public const PROVIDER_ID          = 'lwa';
    public const PROVIDER_NAME        = 'Amazon';
    public const PROVIDER_DESCRIPTION = 'Allow user connection using %s application.';
    public const CONSOLE_URL          = 'https://developer.amazon.com'; // https://developer.amazon.com/settings/console/securityprofile
    public const AUTHORIZE_URL        = 'https://www.amazon.com/ap/oa';
    public const ACCESS_TOKEN_URL     = 'https://api.amazon.com/auth/o2/token';
    public const REVOKE_TOKEN_URL     = '';
    public const REQUEST_URL          = 'https://api.amazon.com/';
    public const SCOPE_DELIMITER      = ' ';
    public const DEFAULT_SCOPE        = ['profile'];
    public const REQUIRE_CHALLENGE    = true;


    protected function getAccessTokenParameters(string $code): string|array
    {
        return (string) http_build_query(parent::getAccessTokenParameters($code));
    }

    protected function getAccessTokenHeaders(string $code): array
    {
        return [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
        ];
    }

    public function getUser(Token $token): User
    {
        return User::parseUser($this->request(Methods::GET, 'user/profile', [], $token), [
            'uid'         => 'user_id',
            'displayname' => 'name',
            'email'       => 'email',
        ]);
    }
}
