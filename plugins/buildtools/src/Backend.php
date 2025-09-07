<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\buildtools;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief   The module backend process.
 * @ingroup buildtools
 */
class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(App::task()->checkContext('BACKEND'));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('dcMaintenanceInit', Buildtools::maintenanceAdmin(...));

        return true;
    }
}
