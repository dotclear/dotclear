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
 * @brief   Generic server Exception.
 *
 * @since   2.28
 */
class GenericServerException extends AbstractException
{
    public const CODE  = 500;
    public const LABEL = 'Internal Server Error';
}
