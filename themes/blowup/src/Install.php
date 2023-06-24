<?php
/**
 * @brief Blowup, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Theme\blowup;

use dcCore;
use Dotclear\Core\Process;

class Install extends Process
{
    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (static::$init) {
            dcCore::app()->blog->settings->themes->put('blowup_style', '', 'string', 'Blow Up custom style', false);
        }

        return static::$init;
    }
}
