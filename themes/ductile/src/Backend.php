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
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;

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

        dcCore::app()->behavior->addBehavior('adminPageHTMLHead', function () {
            if (dcCore::app()->blog->settings->system->theme !== My::id()) {
                return;
            }

            echo "\n" . '<!-- Header directives for Ductile configuration -->' . "\n";
            if (!dcCore::app()->auth->user_prefs->accessibility->nodragdrop) {
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
