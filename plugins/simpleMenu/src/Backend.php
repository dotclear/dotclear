<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup simpleMenu
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Simple menu');
        __('Simple menu for Dotclear');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            'adminDashboardFavoritesV2' => function (Favorites $favs): string {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => App::auth()->makePermissions([
                        App::auth()::PERMISSION_ADMIN,
                    ]),
                ]);

                return '';
            },
            'initWidgets' => Widgets::initWidgets(...),
        ]);

        My::addBackendMenuItem(App::backend()->menus()::MENU_BLOG);

        return true;
    }
}
