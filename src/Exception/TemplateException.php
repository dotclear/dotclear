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
 * @brief   Template handling Exception.
 *
 * Used on template creation fails...
 *
 * @since   2.28
 */
class TemplateException extends AbstractException
{
    public const CODE  = 571;
    public const LABEL = 'Template handling error';
}
