<?php
/**
 * @brief widgets, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\widgets;

use ArrayObject;
use dcAdmin;
use dcCore;
use dcFavorites;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Widgets') . __('Widgets for your blog sidebars');

        return (static::$init = My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'adminDashboardFavoritesV2' => function (dcFavorites $favs) {
                $favs->register(My::id(), [
                    'title'      => My::name(),
                    'url'        => My::manageUrl(),
                    'small-icon' => My::icons(),
                    'large-icon' => My::icons(),
                ]);
            },
            'adminRteFlagsV2' => function (ArrayObject $rte) {
                $rte['widgets_text'] = [true, __('Widget\'s textareas')];
            },
        ]);

        My::addBackendMenuItem(dcAdmin::MENU_BLOG);

        return true;
    }
}
