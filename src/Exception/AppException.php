<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Exception;

use Dotclear\Interface\ExceptionInterface;
use Exception;
use Throwable;

/**
 * @brief   Application exception.
 *
 * @since   2.28
 */
class AppException extends Exception implements ExceptionInterface
{
    public function __construct(string $message = 'Site temporarily unavailable', int $code = 550, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
