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

use Dotclear\App;
use Dotclear\Core\Process;

class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (self::status()) {
            App::behavior()->addBehavior('publicHeadContent', function () {
                echo
                '<link rel="stylesheet" type="text/css" href="' .
                App::blog()->settings()->system->public_url .
                '/custom_style.css" media="screen">' . "\n";
            });
        }

        return self::status();
    }
}
