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

namespace Dotclear\Theme\customCSS;

use dcCore;
use dcNsProcess;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_RC_PATH')) {
            self::$init = true;
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehavior('publicHeadContent', function () {
            echo '<link rel="stylesheet" type="text/css" href="' . dcCore::app()->blog->settings->system->public_url . '/custom_style.css" media="screen">' . "\n";
        });

        return true;
    }
}
