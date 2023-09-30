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
 * @brief   Application configuration Exception.
 *
 * Used on configuration exception.
 *
 * @since   2.28
 */
class ConfigException extends GenericServerException
{
    public const CODE  = 551;
    public const LABEL = 'Application configuration error';
}
