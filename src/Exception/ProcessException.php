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
 * @brief   Application process Exception.
 *
 * Occured when something went wrong during normal process.
 *
 * @since   2.28
 */
class ProcessException extends InternalServerException
{
    public function __construct(string $message = 'Application process error', int $code = 552, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
