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
 * @brief   Permissions and rights Exception.
 *
 * @since   2.28
 */
class UnauthorizedException extends BadRequestException
{
    public function __construct(string $message = 'Unauthorized', int $code = 401, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
