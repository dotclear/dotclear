<?php
/**
 * @package Dotclear
 * @subpackage install
 *
 * Utility class for install context.
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

class Utility extends Process
{
    public static function init(): bool
    {
        define('DC_CONTEXT_INSTALL', true);

        return true;
    }

    public static function process(): bool
    {
        // Call utility process from here
        App::process(defined('DC_RC_PATH') && is_file(DC_RC_PATH) ? Install::class : Wizard::class);

        return true;
    }
}
