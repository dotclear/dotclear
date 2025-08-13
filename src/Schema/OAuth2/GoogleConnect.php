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
 * @brief   Google oAuth2 client provider class.
 *
 * @note    This provider is limited to connection stuff
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class GoogleConnect extends Provider
{
    public const PROVIDER_ID          = 'googleconnect';
    public const PROVIDER_NAME        = 'Google';
    public const PROVIDER_DESCRIPTION = 'Allow user connection using %s application.';
    public const CONSOLE_URL          = 'https://console.cloud.google.com/apis/dashboard';
    public const AUTHORIZE_URL        = 'https://accounts.google.com/o/oauth2/v2/auth';
    public const ACCESS_TOKEN_URL     = 'https://oauth2.googleapis.com/token';
    public const REVOKE_TOKEN_URL     = 'https://oauth2.googleapis.com/revoke';
    public const REQUEST_URL          = 'https://www.googleapis.com';
    public const DEFAULT_SCOPE        = ['https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/userinfo.email', 'openid'];
    public const SCOPE_DELIMITER      = ' ';

    public function getUser(Token $token): User
    {
        return User::parseUser($this->request(Methods::GET, '/oauth2/v1/userinfo', ['alt' => 'json'], $token), [
            'uid'         => 'id',
            'displayname' => 'name',
            'email'       => 'email',
            'avatar'      => 'picture',
        ]);
    }
}
