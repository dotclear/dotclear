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

namespace themes\ductile;

use dcCore;
use dcPage;

if (!defined('DC_RC_PATH')) {
    return;
}
// public part below

if (!defined('DC_CONTEXT_ADMIN')) {
    return false;
}
// admin part below

# Behaviors
dcCore::app()->addBehavior('adminPageHTMLHead', [__NAMESPACE__ . '\tplDuctileThemeAdmin', 'adminPageHTMLHead']);

class tplDuctileThemeAdmin
{
    public static function adminPageHTMLHead()
    {
        if (dcCore::app()->blog->settings->system->theme !== basename(dirname(__FILE__))) {
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
    }
}
