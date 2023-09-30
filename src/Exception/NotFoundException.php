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
 * @brief   Not found exception.
 *
 * Used as classic 404
 *
 * @since   2.28
 */
class NotFoundException extends GenericClientException
{
    public const CODE  = 404;
    public const LABEL = 'Not Found';
}
