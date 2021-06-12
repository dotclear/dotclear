<?php
/**
 * @brief Custom, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace themes\customcss;

if (!defined('DC_RC_PATH')) {
    return;
}

$core->addBehavior('publicHeadContent', [__NAMESPACE__ . '\tplCustomTheme', 'publicHeadContent']);

class tplCustomTheme
{
    public static function publicHeadContent($core)
    {
        echo '<link rel="stylesheet" type="text/css" href="' . $core->blog->settings->system->public_url . '/custom_style.css" media="screen">' . "\n";
    }
}
