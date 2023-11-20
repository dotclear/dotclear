<?php
/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   Process to logout from upgrade.
 *
 * @since   2.29
 */
class Logout extends Process
{
    public static function init(): bool
    {
        return self::status(true);
    }

    public static function process(): bool
    {
        // Enable REST service if disabled, for next requests
        if (!App::rest()->serveRestRequests()) {
            App::rest()->enableRestServer(true);
        }
        // Kill admin session
        App::upgrade()->killAdminSession();
        // Logout
        App::upgrade()->url()->redirect('upgrade.auth');
        exit;
    }
}
