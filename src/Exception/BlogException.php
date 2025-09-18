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
 * @brief   Blog handling Exception.
 *
 * Used on blog load fails, or blog offline...
 *
 * @since   2.28
 */
class BlogException extends InternalServerException
{
    public function __construct(string $message = 'Blog handling error', int $code = 570, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
