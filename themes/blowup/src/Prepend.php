<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */

namespace Dotclear\Theme\blowup;

use Dotclear\Core\Process;

/**
 * @brief   The module prepend.
 * @ingroup blowup
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }
}
