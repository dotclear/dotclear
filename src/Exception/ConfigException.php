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
 * @brief   Application configuration Exception.
 *
 * Used on configuration exception.
 *
 * @since   2.28
 */
class ConfigException extends InternalServerException
{
    public function __construct(string $message = 'Application configuration error', int $code = 551, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
