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
 * @brief   Conflict Exception.
 *
 * Used on conflict actions.
 *
 * @since   2.28
 */
class ConflictException extends BadRequestException
{
    public function __construct(string $message = 'Conflict', int $code = 409, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
