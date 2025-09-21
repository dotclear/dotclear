<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Theme\customCSS;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief   The module backend process.
 * @ingroup customCSS
 */
class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('adminPageHTMLHead', function (): string {
            if (App::blog()->settings()->system->theme !== My::id()) {
                return '';
            }

            if (!App::task()->checkContext('MODULE')) {
                // Not on module configuration page
                return '';
            }

            if (App::auth()->prefs()->interface->colorsyntax) {
                echo App::backend()->page()->jsLoadCodeMirror(App::auth()->prefs()->interface->colorsyntax_theme);
            }

            return '';
        });

        return true;
    }
}
