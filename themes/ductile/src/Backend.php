<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Theme\ductile;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief   The module backend process.
 * @ingroup ductile
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

        App::behavior()->addBehavior('adminPageHTMLHead', function (): void {
            if (App::blog()->settings()->system->theme !== My::id()) {
                return;
            }

            if (!App::task()->checkContext('MODULE')) {
                // Not on module configuration page
                return;
            }

            echo "\n" . '<!-- Header directives for Ductile configuration -->' . "\n";
            if (!App::auth()->prefs()->accessibility->nodragdrop) {
                echo
                App::backend()->page()->jsLoad('js/jquery/jquery-ui.custom.js') .
                App::backend()->page()->jsLoad('js/jquery/jquery.ui.touch-punch.js') .
                My::jsLoad('admin.js');
            }
        });

        return true;
    }
}
