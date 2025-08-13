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
 * @brief   auth protocols enumeration.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum Protocols
{
    case OAUTH2;
    case OAUTH;
    case OPENID;
}
