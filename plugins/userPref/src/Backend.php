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
use dcPage;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->menu[dcAdmin::MENU_SYSTEM]->addItem(
            'user:preferences',
            dcCore::app()->adminurl->get('admin.plugin.userPref'),
            dcPage::getPF('userPref/icon.svg'),
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.userPref')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->isSuperAdmin()
        );

        return true;
    }
}
