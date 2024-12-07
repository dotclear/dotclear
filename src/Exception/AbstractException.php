<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Exception
 * @brief       Dotclear exceptions collection
 */

namespace Dotclear\Exception;

use Throwable;

/**
 * @brief   Application exception.
 *
 * Abstract exceptpion class will provide a default label and description if none given
 * Useful when used by Dotclear.Fault parser.
 *
 * @see     Dotclear.Fault
 *
 * @since   2.28
 */
abstract class AbstractException extends AppException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message ?: static::label(), $code ?: static::code(), $previous);
    }
}
