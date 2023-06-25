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

use dcCore;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\Utility;
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Simple menu') . __('Simple menu for Dotclear');

        return (static::$init = My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'adminDashboardFavoritesV2' => function (Favorites $favs) {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => dcCore::app()->auth->makePermissions([
                        dcCore::app()->auth::PERMISSION_ADMIN,
                    ]),
                ]);
            },
            'initWidgets' => [Widgets::class, 'initWidgets'],
        ]);

        My::addBackendMenuItem(Utility::MENU_BLOG);

        return true;
    }
}
