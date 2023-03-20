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
use dcNsProcess;

class Install extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN') && dcCore::app()->newVersion(
            basename(dirname(__DIR__)),
            dcCore::app()->plugins->moduleInfo(basename(dirname(__DIR__)), 'version')
        );

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->blog->settings->themes->put('blowup_style', '', 'string', 'Blow Up custom style', false);

        return true;
    }
}
