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

namespace themes\berlin;

if (!defined('DC_RC_PATH')) {
    return;
}

\l10n::set(__DIR__ . '/locales/' . \dcCore::app()->lang . '/main');

\dcCore::app()->addBehavior('publicHeadContent', [__NAMESPACE__ . '\behaviorBerlinTheme', 'publicHeadContent']);

class behaviorBerlinTheme
{
    public static function publicHeadContent()
    {
        echo
        \dcUtils::jsJson('dotclear_berlin', [
            'show_menu'  => __('Show menu'),
            'hide_menu'  => __('Hide menu'),
            'navigation' => __('Main menu'),
        ]);
    }
}
