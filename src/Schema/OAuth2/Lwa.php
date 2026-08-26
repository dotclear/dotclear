<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\OAuth2;

use Dotclear\Helper\OAuth2\Client\{Methods, Token, User };

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
    /**
     * @var string PROVIDER_ID
     */
    public const PROVIDER_ID = 'lwa';

    /**
     * @var string PROVIDER_NAME
     */
    public const PROVIDER_NAME = 'Amazon';

    /**
     * @var string PROVIDER_DESCRIPTION
     */
    public const PROVIDER_DESCRIPTION = 'Allow user connection using %s application.';

    /**
     * @var string CONSOLE_URL
     */
    public const CONSOLE_URL = 'https://developer.amazon.com';     // https://developer.amazon.com/settings/console/securityprofile

    /**
     * @var string AUTHORIZE_URL
     */
    public const AUTHORIZE_URL = 'https://www.amazon.com/ap/oa';

    /**
     * @var string ACCESS_TOKEN_URL
     */
    public const ACCESS_TOKEN_URL = 'https://api.amazon.com/auth/o2/token';

    /**
     * @var string REVOKE_TOKEN_URL
     */
    public const REVOKE_TOKEN_URL = '';

    /**
     * @var string REQUEST_URL
     */
    public const REQUEST_URL = 'https://api.amazon.com/';

    /**
     * @var string SCOPE-DELIMITER
     */
    public const SCOPE_DELIMITER = ' ';

    /**
     * @var string[] DEFAULT_SCOPE
     */
    public const DEFAULT_SCOPE = ['profile'];

    /**
     * @var BOOL REQUIRE_CHALLENGE
     */
    public const REQUIRE_CHALLENGE = true;

    protected function getAccessTokenParameters(string $code): string
    {
        $parameters = parent::getAccessTokenParameters($code);

        return http_build_query(is_array($parameters) ? $parameters : [$parameters]);
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
