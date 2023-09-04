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

use Dotclear\App;
use Dotclear\Core\Process;

class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (self::status()) {
            App::blog()->settings()->themes->put('blowup_style', '', 'string', 'Blow Up custom style', false);
        }

        return self::status();
    }
}
