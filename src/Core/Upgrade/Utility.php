<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Core.Upgrade
 * @brief       Dotclear application upgrade utilities.
 */

namespace Dotclear\Core\Upgrade;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Process\Upgrade\Cli;

/**
 * @brief   Utility class for upgrade context.
 *
 * This utility is only used in CLI mode
 * and has only one process.
 *
 * @since   2.27
 */
class Utility extends Process
{
    public static function init(): bool
    {
        // We need to pass CLI argument to App::task()->run()
        if (isset($_SERVER['argv'][1])) {
            $_SERVER['DC_RC_PATH'] = $_SERVER['argv'][1];
        }

        return true;
    }

    public static function process(): bool
    {
        // Call utility process from here
        App::task()->loadProcess(Cli::class);

        return true;
    }
}
