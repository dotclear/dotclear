<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   Process to logout from backend.
 *
 * @since   2.28
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
        App::backend()->killAdminSession();
        // Logout
        App::backend()->url()->redirect('admin.auth');
        exit;

        return true;
    }
}
