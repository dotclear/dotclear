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
 * @brief   Not found exception.
 *
 * Used as classic 404
 *
 * @since   2.28
 */
class NotFoundException extends BadRequestException
{
    public function __construct(string $message = 'Not found', int $code = 404, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
