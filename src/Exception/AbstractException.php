<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Exception
 * @brief       Dotclear exceptions collection
 */

namespace Dotclear\Exception;

use Exception;
use Throwable;

/**
 * @brief   Application exception.
 *
 * Abstract exceptpion class adds a layer with a new Exception
 * in order to have a label and a description of an Exception in
 * Dotclear.Fault parser.
 *
 * @see     Dotclear.Fault
 *
 * @since   2.28
 */
abstract class AbstractException extends AppException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(static::label(), static::code(), new Exception($message ?: static::label(), $code ?: static::code(), $previous));
    }
}
