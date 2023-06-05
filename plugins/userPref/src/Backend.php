<?php
/**
 * @brief userPref, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\userPref;

use dcAdmin;
use dcCore;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (static::$init) {
            My::backendSidebarMenuIcon(dcAdmin::MENU_SYSTEM);
        }

        return static::$init;
    }
}
