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

if (!defined('DC_RC_PATH')) {return;}

\l10n::set(dirname(__FILE__) . '/locales/' . $_lang . '/main');
//__('Show menu').__('Hide menu').__('Navigation');

$core->addBehavior('publicHeadContent', array(__NAMESPACE__ . '\behaviorBerlinTheme', 'publicHeadContent'));

class behaviorBerlinTheme
{
    public static function publicHeadContent()
    {
        echo \dcUtils::jsVars(array(
            'dotclear_berlin_show_menu'  => __('Show menu'),
            'dotclear_berlin_hide_menu'  => __('Hide menu'),
            'dotclear_berlin_navigation' => __('Navigation')
        ));
    }
}
