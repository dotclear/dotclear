<?php

/**
 * @brief Ductile 2026, Refresh of ductile Dotclear 2 theme
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Kozlika, Franck Paul and contributors
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Theme\Ductile2026;

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
                $media_selector = App::backend()->popup === 1 && App::backend()->plugin_id === 'admin.blog.theme';
                if (!$media_selector) {
                    // Not on module configuration page (taking care of media item selector popup opened)
                    return;
                }
            }

            if (!App::auth()->prefs()->accessibility->nodragdrop) {
                echo
                App::backend()->page()->jsLoad('js/jquery/jquery-ui.custom.js') .
                App::backend()->page()->jsLoad('js/jquery/jquery.ui.touch-punch.js');
            }

            echo
            My::jsLoad('admin/config.js') . "\n" .
            My::jsLoad('admin/popup_media.js') . "\n" .
            My::cssLoad('admin/config.css') . "\n" ;
        });

        return true;
    }
}
