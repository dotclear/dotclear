<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Exception;

/**
 * @brief   Permissions and rights Exception.
 *
 * @since   2.28
 */
class UnauthorizedException extends GenericClientException
{
    public const CODE  = 401;
    public const LABEL = 'Unauthorized';
}
