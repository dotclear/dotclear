<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pings;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup pings
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Pings');
        __('Ping services');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem(App::backend()->menus()::MENU_BLOG, [], '');

        App::behavior()->addBehaviors([
            'adminPostHeaders'     => fn (): string => My::jsLoad('post') . My::cssLoad('style'),
            'adminPostFormItems'   => BackendBehaviors::pingsFormItems(...),
            'adminAfterPostCreate' => BackendBehaviors::doPings(...),
            'adminAfterPostUpdate' => BackendBehaviors::doPings(...),

            'adminDashboardFavoritesV2' => function (Favorites $favs): string {
                $favs->register(My::id(), [
                    'title'      => My::name(),
                    'url'        => My::manageUrl(),
                    'small-icon' => My::icons(),
                    'large-icon' => My::icons(),
                ]);

                return '';
            },
            'adminPageHelpBlock' => function (ArrayObject $blocks): string {
                if (in_array('core_post', $blocks->getArrayCopy(), true)) {
                    $blocks->append('pings_post');
                }

                return '';
            },
        ]);

        return true;
    }
}
