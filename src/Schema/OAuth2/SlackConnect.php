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
 * @brief   Slack oAuth2 client provider class.
 *
 * @note    This provider is limited to connection stuff
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class SlackConnect extends Provider
{
    public const PROVIDER_ID          = 'slackconnect';
    public const PROVIDER_NAME        = 'Slack';
    public const PROVIDER_DESCRIPTION = 'Allow user connection using %s application.';
    public const CONSOLE_URL          = 'https://api.slack.com/apps';
    public const AUTHORIZE_URL        = 'https://slack.com/openid/connect/authorize';
    public const ACCESS_TOKEN_URL     = 'https://slack.com/api/openid.connect.token';
    public const REVOKE_TOKEN_URL     = 'https://slack.com/api/auth.revoke';
    public const REQUEST_URL          = 'https://slack.com/api/';
    public const DEFAULT_SCOPE        = ['openid', 'profile'];

    protected function getRevokeTokenParameters(Token $token): string|array
    {
        return [];
    }

    public function getUser(Token $token): User
    {
        return User::parseUser($this->request(Methods::GET, 'openid.connect.userInfo', [], $token), [
            'uid'         => 'sub',
            'displayname' => 'name',
            'avatar'      => 'picture',
        ]);
    }
}
