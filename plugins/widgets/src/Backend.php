<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\widgets;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup widgets
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Widgets');
        __('Widgets for your blog sidebars');

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
                    'title'      => My::name(),
                    'url'        => My::manageUrl(),
                    'small-icon' => My::icons(),
                    'large-icon' => My::icons(),
                ]);

                return '';
            },
            'adminRteFlagsV2' => function (ArrayObject $rte): string {
                $rte['widgets_text'] = [true, __('Widget\'s textareas')];

                return '';
            },
        ]);

        My::addBackendMenuItem(App::backend()->menus()::MENU_BLOG);

        return true;
    }
}
