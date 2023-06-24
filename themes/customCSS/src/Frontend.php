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
use Dotclear\Core\Process;

class Frontend extends Process
{
    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (static::$init) {
            dcCore::app()->addBehavior('publicHeadContent', function () {
                echo 
                '<link rel="stylesheet" type="text/css" href="' . 
                dcCore::app()->blog->settings->system->public_url . 
                '/custom_style.css" media="screen">' . "\n";
            });
        }

        return static::$init;
    }
}
