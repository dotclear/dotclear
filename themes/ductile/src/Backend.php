<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
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

        App::behavior()->addBehavior('adminPageHTMLHead', function () {
            if (App::blog()->settings()->system->theme !== My::id()) {
                return;
            }

            echo "\n" . '<!-- Header directives for Ductile configuration -->' . "\n";
            if (!App::auth()->prefs()->accessibility->nodragdrop) {
                echo
                Page::jsLoad('js/jquery/jquery-ui.custom.js') .
                Page::jsLoad('js/jquery/jquery.ui.touch-punch.js');
                echo <<<EOT
                    <script>
                    /*global $ */
                    'use strict';

                    $(() => {
                        $('#stickerslist').sortable({'cursor':'move'});
                        $('#stickerslist tr').hover(function () {
                            $(this).css({'cursor':'move'});
                        }, function () {
                            $(this).css({'cursor':'auto'});
                        });
                        $('#theme_config').submit(() => {
                            const order=[];
                            $('#stickerslist tr td input.position').each(function() {
                                order.push(this.name.replace(/^order\[([^\]]+)\]$/,'$1'));
                            });
                            $('input[name=ds_order]')[0].value = order.join(',');
                            return true;
                        });
                        $('#stickerslist tr td input.position').hide();
                        $('#stickerslist tr td.handle').addClass('handler');
                    });
                    </script>
                    EOT;
            }
        });

        return true;
    }
}
