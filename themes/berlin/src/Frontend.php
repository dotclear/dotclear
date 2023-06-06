<?php
/**
 * @brief Berlin, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Theme\berlin;

use dcCore;
use dcNsProcess;
use dcUtils;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        My::l10n('main');

        dcCore::app()->addBehavior('publicHeadContent', function () {
            echo
            dcUtils::jsJson('dotclear_berlin', [
                'show_menu'  => __('Show menu'),
                'hide_menu'  => __('Hide menu'),
                'navigation' => __('Main menu'),
            ]);
        });

        return true;
    }
}
