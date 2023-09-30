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
 * @brief   Database connection handling Exception.
 *
 * Used on database connection exception.
 *
 * @since   2.28
 */
class DatabaseException extends GenericServerException
{
    public const CODE  = 560;
    public const LABEL = 'Database connection error';
}
