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
 * @brief   Github oAuth2 client provider class.
 *
 * @note    This provider is limited to connection stuff
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class GithubConnect extends Provider
{
    public const PROVIDER_ID          = 'githubconnect';
    public const PROVIDER_NAME        = 'Github';
    public const PROVIDER_DESCRIPTION = 'Allow user connection using %s application.';
    public const CONSOLE_URL          = 'https://github.com/settings/applications/';
    public const AUTHORIZE_URL        = 'https://github.com/login/oauth/authorize';
    public const ACCESS_TOKEN_URL     = 'https://github.com/login/oauth/access_token';
    public const REVOKE_TOKEN_URL     = 'https://api.github.com/credentials/revoke';
    public const REQUEST_URL          = 'https://api.github.com/';
    public const DEFAULT_SCOPE        = ['read:user', 'user:email'];

    protected function getRevokeTokenParameters(Token $token): string|array
    {
        return (string) json_encode([
            'credentials' => [$token->get('access_token')],
        ]);
    }

    protected function getRevokeTokenHeaders(Token $token): array
    {
        return [
            'accept'        => 'application/vnd.github+json',
            'content-type'  => 'application/json',
        ];
    }

    public function getUser(Token $token): User
    {
        return User::parseUser($this->request(Methods::GET, 'user', [], $token), [
            'uid'         => 'id',
            'displayname' => 'name',
            'email'       => 'email',
            'avatar'      => 'avatar_url',
        ]);
    }
}
