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
