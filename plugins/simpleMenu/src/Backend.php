<?php
/**
 * @brief simpleMenu, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

use dcAdmin;
use dcAuth;
use dcCore;
use dcFavorites;
use dcNsProcess;
use dcPage;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            static::$init = true;
        }

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'adminDashboardFavoritesV2' => function (dcFavorites $favs) {
                $favs->register('simpleMenu', [
                    'title'       => __('Simple menu'),
                    'url'         => dcCore::app()->adminurl->get('admin.plugin.simpleMenu'),
                    'small-icon'  => dcPage::getPF('simpleMenu/icon.svg'),
                    'large-icon'  => dcPage::getPF('simpleMenu/icon.svg'),
                    'permissions' => dcCore::app()->auth->makePermissions([
                        dcAuth::PERMISSION_USAGE,
                        dcAuth::PERMISSION_CONTENT_ADMIN,
                    ]),
                ]);
            },
            'initWidgets' => [Widgets::class, 'initWidgets'],
        ]);

        dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
            __('Simple menu'),
            dcCore::app()->adminurl->get('admin.plugin.simpleMenu'),
            dcPage::getPF('simpleMenu/icon.svg'),
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.simpleMenu')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_USAGE,
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id)
        );

        return true;
    }
}
