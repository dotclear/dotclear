<?php
/**
 * @package Dotclear
 * @subpackage Upgrade
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Process\Upgrade\Cli;

/**
 * Utility class for upgrade context.
 */
class Utility extends Process
{
    public static function init(): bool
    {
        // we need to pass CLI argument to App::load()
        if (isset($_SERVER['argv'][1])) {
            $_SERVER['DC_RC_PATH'] = $_SERVER['argv'][1];
        }

        return true;
    }

    public static function process(): bool
    {
        // Call utility process from here
        App::process(Cli::class);

        return true;
    }
}
