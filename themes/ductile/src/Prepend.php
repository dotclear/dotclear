<?php
/**
 * @brief Ductile, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

namespace Dotclear\Theme\ductile;

use dcCore;
use dcNsProcess;
use dcPage;

class Prepend extends dcNsProcess
{
    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehavior('adminPageHTMLHead', function () {
            if (dcCore::app()->blog->settings->system->theme !== My::id()) {
                return;
            }

            echo "\n" . '<!-- Header directives for Ductile configuration -->' . "\n";
            if (!dcCore::app()->auth->user_prefs->accessibility->nodragdrop) {
                echo
                dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
                dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js');
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
