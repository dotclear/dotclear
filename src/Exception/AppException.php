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
        parent::__construct($message ?: static::label(), $code ?: static::code(), $previous);
    }

    public static function code(): int
    {
        return static::enum()->code();
    }

    public static function label(): string
    {
        return static::enum()->label();
    }

    /**
     * Find exception enumeration.
     *
     * @see     Dotclear.Exception.ExceptionEnum
     *
     * @return  ExceptionEnum   The exception enumeration
     */
    protected static function enum(): ExceptionEnum
    {
        return ExceptionEnum::tryFrom(static::class) ?? ExceptionEnum::from(self::class);
    }
}
