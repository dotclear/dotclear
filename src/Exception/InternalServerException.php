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
 * @brief   Internal server Exception.
 *
 * This is the fallback for 5xx errors.
 *
 * @since   2.28
 */
class InternalServerException extends AbstractException
{
}
