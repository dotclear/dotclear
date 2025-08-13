<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\OAuth2\Client;

/**
 * @brief   oAuth2 grant types enumeration.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum GrantTypes: string
{
    case AUTHORIZATION_CODE = 'authorization_code';
    case CLIENT_CREDENTIALS = 'client_credentials';
    case REFRESH_TOKEN      = 'refresh_token';
    case PASSWORD           = 'password';
}
