<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Exception;

use Exception;
use Throwable;

/**
 * @brief   Application exception.
 *
 * @since   2.28
 */
class AppException extends Exception
{
    public function __construct(string $message = 'Site temporarily unavailable', int $code = 503, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
