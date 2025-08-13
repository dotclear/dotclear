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
 * @brief   auth HTTP methods enumeration.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum Methods
{
    case HEAD;
    case GET;
    case POST;
    case DELETE;
    case PATCH;
    case OPTIONS;
    case PUT;
}
