<?php
/**
 * @package Dotclear
 * @subpackage install
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Install;

use Dotclear\App;
use Dotclear\Process\Install\Install;
use Dotclear\Process\Install\Wizard;
use Dotclear\Core\Process;

/**
 * Utility class for install context.
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
        App::process(is_file(App::config()->configPath()) ? Install::class : Wizard::class);

        return true;
    }
}
