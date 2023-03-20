<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use dcAdmin;
use dcAuth;
use dcCore;
use dcFavorites;
use dcPage;
use dcNsProcess;
use initBlogroll;

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

        dcCore::app()->auth->setPermissionType(initBlogroll::PERMISSION_BLOGROLL, __('manage blogroll'));

        dcCore::app()->addBehaviors([
            'adminDashboardFavoritesV2' => function (dcFavorites $favs) {
                $favs->register('blogroll', [
                    'title'       => __('Blogroll'),
                    'url'         => dcCore::app()->adminurl->get('admin.plugin.blogroll'),
                    'small-icon'  => [dcPage::getPF('blogroll/icon.svg'), dcPage::getPF('blogroll/icon-dark.svg')],
                    'large-icon'  => [dcPage::getPF('blogroll/icon.svg'), dcPage::getPF('blogroll/icon-dark.svg')],
                    'permissions' => dcCore::app()->auth->makePermissions([
                        dcAuth::PERMISSION_USAGE,
                        dcAuth::PERMISSION_CONTENT_ADMIN,
                    ]),
                ]);
            },
            'adminUsersActionsHeaders' => fn () => dcPage::jsModuleLoad('blogroll/js/_users_actions.js'),

            'initWidgets'        => [Widgets::class, 'initWidgets'],
            'initDefaultWidgets' => [Widgets::class, 'initDefaultWidgets'],
        ]);

        dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
            __('Blogroll'),
            dcCore::app()->adminurl->get('admin.plugin.blogroll'),
            [dcPage::getPF('blogroll/icon.svg'), dcPage::getPF('blogroll/icon-dark.svg')],
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.blogroll')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_USAGE,
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id)
        );

        return true;
    }
}
