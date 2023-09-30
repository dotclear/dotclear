<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Exception;

/**
 * @brief   Bad request Exception.
 *
 * Used on request/action processing fails.
 *
 * @since   2.28
 */
class BadRequestException extends GenericClientException
{
    public const CODE  = 400;
    public const LABEL = 'Bad Request';
}
