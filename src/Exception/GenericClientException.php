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
 * @brief   Generic client Exception.
 *
 * @since   2.28
 */
class GenericClientException extends AbstractException
{
    public const CODE  = 400;
    public const LABEL = 'Bad Request';
}
