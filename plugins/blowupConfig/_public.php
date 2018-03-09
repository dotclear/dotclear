<?php
/**
 * @brief blowupConfig, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

if ($core->blog->settings->system->theme != 'default') {
    return;
}

require dirname(__FILE__) . '/lib/class.blowup.config.php';
$core->addBehavior('publicHeadContent', array('tplBlowupTheme', 'publicHeadContent'));

class tplBlowUpTheme
{
    public static function publicHeadContent($core)
    {
        $url = blowupConfig::publicCssUrlHelper();
        if ($url) {
            echo '<link rel="stylesheet" href="' . $url . '" type="text/css" />';
        }
    }
}
