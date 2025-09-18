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
 * @brief   Bad request Exception.
 *
 * This is the fallback for 4xx errors.
 *
 * Used on request/action processing fails.
 *
 * @since   2.28
 */
class BadRequestException extends AppException
{
    public function __construct(string $message = 'Bad Request', int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
