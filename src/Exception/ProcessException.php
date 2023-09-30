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
 * @brief   Application process Exception.
 *
 * Occured when something went wrong during normal process.
 *
 * @since   2.28
 */
class ProcessException extends AbstractException
{
    public const CODE  = 552;
    public const LABEL = 'Application process error';
}
