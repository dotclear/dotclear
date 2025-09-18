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
 * @brief   Post form precondition Exception.
 *
 * @since   2.28
 */
class PreconditionException extends BadRequestException
{
    public function __construct(string $message = 'Precondition Failed', int $code = 412, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
