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
if (!defined('DC_RC_PATH')) {
    return;
}

if (dcCore::app()->blog->settings->system->theme != 'default') {
    // It's not Blowup
    return;
}

require __DIR__ . '/lib/class.blowup.config.php';
dcCore::app()->addBehavior('publicHeadContent', ['tplBlowupTheme', 'publicHeadContent']);

class tplBlowUpTheme
{
    public static function publicHeadContent()
    {
        $url = blowupConfig::publicCssUrlHelper();
        if ($url) {
            echo '<link rel="stylesheet" href="' . $url . '" type="text/css" />';
        }
    }
}
