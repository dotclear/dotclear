<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Theme\customCSS;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module frontend process.
 * @ingroup customCSS
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (self::status()) {
            App::behavior()->addBehavior('publicHeadContent', function (): void {
                echo
                '<link rel="stylesheet" type="text/css" href="' .
                App::blog()->settings()->system->public_url . DIRECTORY_SEPARATOR . My::id() . '.css' .
                '" media="screen">' . "\n";
            });
        }

        return self::status();
    }
}
