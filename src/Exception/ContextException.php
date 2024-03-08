<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
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
class ContextException extends InternalServerException
{
}
