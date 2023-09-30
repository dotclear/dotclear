<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Exception;

use Dotclear\Interface\Exception\AppExceptionInterface;
use Exception;
use Throwable;

/**
 * @brief   Application exception.
 *
 * @since   2.28
 */
class AppException extends Exception implements AppExceptionInterface
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        // Fallback to interface default label and code
        parent::__construct($message ?: self::LABEL, $code ?: self::CODE, $previous);
    }
}
