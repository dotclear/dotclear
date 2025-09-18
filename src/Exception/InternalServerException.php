<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Exception;

use Throwable;

/**
 * @brief   Internal server Exception.
 *
 * This is the fallback for 5xx errors.
 *
 * @since   2.28
 */
class InternalServerException extends AppException
{
    public function __construct(string $message = 'Internal Server Error', int $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
