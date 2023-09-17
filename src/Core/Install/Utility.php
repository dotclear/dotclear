<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Core.Install
 * @brief       Dotclear application install utilities.
 */
namespace Dotclear\Core\Install;

use Dotclear\App;
use Dotclear\Process\Install\Install;
use Dotclear\Process\Install\Wizard;
use Dotclear\Core\Process;

/**
 * @brief   Utility class for install context.
 *
 * This utility calls itself Wizard or Intall process.
 */
class Utility extends Process
{
    public static function init(): bool
    {
        return true;
    }

    public static function process(): bool
    {
        // Call utility process from here
        App::task()->loadProcess(is_file(App::config()->configPath()) ? Install::class : Wizard::class);

        return true;
    }
}
