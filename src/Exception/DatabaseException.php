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
 * @brief   Database connection handling Exception.
 *
 * Used on database connection exception.
 *
 * @since   2.28
 */
class DatabaseException extends InternalServerException
{
    public function __construct(string $message = 'Database connection error', int $code = 560, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
