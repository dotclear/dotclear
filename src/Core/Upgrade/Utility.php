<?php
/**
 * @package Dotclear
 * @subpackage Upgrade
 *
 * Utility class for upgrade context.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use DOtclear\App;
use Dotclear\Core\Process;
use Dotclear\Upgrade\Cli;
use Exception;

class Utility extends Process
{
    private static $instance;

    public static function init(): bool
    {
        define('DC_CONTEXT_UPGRADE', true);

        // we need to pass CLI argument to App::init()
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
