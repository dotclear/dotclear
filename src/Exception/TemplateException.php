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
 * @brief   Template handling Exception.
 *
 * Used on template creation fails...
 *
 * @since   2.28
 */
class TemplateException extends InternalServerException
{
    public function __construct(string $message = 'Template handling error', int $code = 571, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
