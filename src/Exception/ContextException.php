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
 * @brief   Application context Exception.
 *
 * Used on singleton or utility process exception.
 *
 * @since   2.28
 */
class ContextException extends GenericServerException
{
    public const CODE  = 553;
    public const LABEL = 'Application context error';
}
