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
 * @brief   Session handling Exception.
 *
 * Used on session handling exception.
 *
 * @since   2.28
 */
class SessionException extends InternalServerException
{
    public function __construct(string $message = 'Session handling error', int $code = 561, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
