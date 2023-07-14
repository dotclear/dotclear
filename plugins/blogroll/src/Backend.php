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

use dcCore;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\Menus;
use Dotclear\Core\Process;
use initBlogroll;

class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Blogroll') . __('Manage your blogroll');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->auth->setPermissionType(initBlogroll::PERMISSION_BLOGROLL, __('manage blogroll'));

        dcCore::app()->addBehaviors([
            'adminDashboardFavoritesV2' => function (Favorites $favs) {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => dcCore::app()->auth->makePermissions([
                        initBlogroll::PERMISSION_BLOGROLL,
                        dcCore::app()->auth::PERMISSION_USAGE,
                        dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                    ]),
                ]);
            },
            'adminUsersActionsHeaders' => fn () => My::jsLoad('_users_actions'),

            'initWidgets'        => [Widgets::class, 'initWidgets'],
            'initDefaultWidgets' => [Widgets::class, 'initDefaultWidgets'],
        ]);

        My::addBackendMenuItem(Menus::MENU_BLOG);

        return true;
    }
}
