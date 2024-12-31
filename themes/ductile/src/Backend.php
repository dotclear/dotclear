<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Theme\ductile;

use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup ductile
 */
class Backend extends Process
{
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
                Page::jsLoad('js/jquery/jquery-ui.custom.js') .
                Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
                My::jsLoad('admin.js');
            }
        });

        return true;
    }
}
