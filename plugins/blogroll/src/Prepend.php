<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module prepend process.
 * @ingroup blogroll
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::url()->register('xbel', 'xbel', '^xbel(?:\/?)$', FrontendUrl::xbel(...));

        return true;
    }
}
