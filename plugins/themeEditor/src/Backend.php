<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\themeEditor;

use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief   The module backend process.
 * @ingroup themeEditor
 */
class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('themeEditor');
        __('Theme Editor');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (self::status()) {
            App::behavior()->addBehaviors([
                'adminCurrentThemeDetailsV2'   => BackendBehaviors::adminCurrentThemeDetails(...),
                'adminBeforeUserOptionsUpdate' => BackendBehaviors::adminBeforeUserUpdate(...),
                'adminPreferencesFormV2'       => BackendBehaviors::adminPreferencesForm(...),
                'adminDashboardFavoritesV2'    => function (Favorites $favs): string {
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
            ]);
        }

        return self::status();
    }
}
