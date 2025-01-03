<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup blogroll
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Blogroll');
        __('Manage your blogroll');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::auth()->setPermissionType(Blogroll::PERMISSION_BLOGROLL, __('manage blogroll'));

        App::behavior()->addBehaviors([
            'adminDashboardFavoritesV2' => function (Favorites $favs): string {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => App::auth()->makePermissions([
                        Blogroll::PERMISSION_BLOGROLL,
                        App::auth()::PERMISSION_USAGE,
                        App::auth()::PERMISSION_CONTENT_ADMIN,
                    ]),
                ]);

                return '';
            },
            'adminUsersActionsHeaders' => fn (): string => My::jsLoad('_users_actions'),

            'initWidgets'        => Widgets::initWidgets(...),
            'initDefaultWidgets' => Widgets::initDefaultWidgets(...),
        ]);

        My::addBackendMenuItem(App::backend()->menus()::MENU_BLOG);

        return true;
    }
}
